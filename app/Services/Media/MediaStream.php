<?php

namespace App\Services\Media;

use App\Models\Media;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sends a stored file to a browser, whole or in the byte range it asked for.
 *
 * Ranges are not an optimization here. Safari and iOS refuse to play audio from
 * a server that cannot answer one, and other browsers play it but cannot seek.
 * Access is still checked per request by the caller, which is why this streams
 * through the application instead of handing out a signed storage URL.
 */
class MediaStream
{
    private const CHUNK_BYTES = 1024 * 1024;

    public function respond(Request $request, Media $medium): StreamedResponse
    {
        $disk = Storage::disk($medium->storage_disk);
        $key = $medium->storage_key;
        $size = $disk->size($key);

        $headers = [
            'Accept-Ranges' => 'bytes',
            'Content-Type' => $medium->content_type,
            'Content-Disposition' => HeaderUtils::makeDisposition('inline', basename($key)),
        ];

        $range = $this->range($request, $size);

        if ($range === false) {
            return new StreamedResponse(null, 416, $headers + [
                'Content-Range' => "bytes */{$size}",
                'Content-Length' => 0,
            ]);
        }

        [$start, $end] = $range ?? [0, $size - 1];
        $length = $end - $start + 1;

        $headers['Content-Length'] = $length;

        if ($range !== null) {
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
        }

        $status = $range === null ? 200 : 206;

        if ($request->isMethod('HEAD') || $length <= 0) {
            return new StreamedResponse(null, $status, $headers);
        }

        // Opened before the headers go out, so a storage failure is an error
        // response rather than a truncated file.
        $stream = $this->open($disk, $key, $start, $end, $size);

        return new StreamedResponse(
            fn () => $this->send($stream, $length),
            $status,
            $headers
        );
    }

    /**
     * The inclusive byte range asked for: null to send the whole file, false
     * when the range lies outside it.
     *
     * Only a single range is answered. A request for several, or one this
     * cannot parse, gets the whole file, which the HTTP spec allows. So does
     * `If-Range`, since responses carry no validator it could be compared to.
     *
     * @return array{0: int, 1: int}|false|null
     */
    private function range(Request $request, int $size): array|false|null
    {
        $header = $request->headers->get('Range');

        if ($header === null || $size === 0 || $request->headers->has('If-Range')) {
            return null;
        }

        if (! preg_match('/^bytes=(\d*)-(\d*)$/', trim($header), $matches)) {
            return null;
        }

        [, $first, $last] = $matches;

        if ($first === '' && $last === '') {
            return null;
        }

        if ($first === '') {
            $suffix = (int) $last;

            return $suffix === 0 ? false : [max(0, $size - $suffix), $size - 1];
        }

        $start = (int) $first;

        if ($start >= $size) {
            return false;
        }

        if ($last === '') {
            return [$start, $size - 1];
        }

        return (int) $last < $start ? null : [$start, min((int) $last, $size - 1)];
    }

    /**
     * A stream positioned at the start of the range. Object storage is asked
     * for just those bytes, so seeking in a long recording does not download
     * everything before the point sought.
     *
     * @return resource
     */
    private function open(FilesystemAdapter $disk, string $key, int $start, int $end, int $size)
    {
        if ($disk instanceof AwsS3V3Adapter && ($start > 0 || $end < $size - 1)) {
            return $disk->getClient()->getObject([
                'Bucket' => $disk->getConfig()['bucket'],
                'Key' => $disk->path($key),
                'Range' => "bytes={$start}-{$end}",
                '@http' => ['stream' => true],
            ])['Body']->detach();
        }

        $stream = $disk->readStream($key);

        if ($start > 0) {
            fseek($stream, $start);
        }

        return $stream;
    }

    /** @param resource $stream */
    private function send($stream, int $length): void
    {
        $remaining = $length;

        while ($remaining > 0 && ! feof($stream) && ! connection_aborted()) {
            $chunk = fread($stream, min(self::CHUNK_BYTES, $remaining));

            if ($chunk === false || $chunk === '') {
                break;
            }

            echo $chunk;
            flush();

            $remaining -= strlen($chunk);
        }

        fclose($stream);
    }
}
