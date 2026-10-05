<?php

namespace Tests\Fakes;

use App\Services\Media\MultipartUploads;
use App\Services\Media\UploadGone;
use DateTimeInterface;
use Illuminate\Support\Str;

/**
 * Multipart storage in memory: uploads are started, given parts, listed,
 * completed and aborted, and the test can look at what happened to each.
 */
class FakeMultipartUploads implements MultipartUploads
{
    /** @var array<string, array{key:string, content_type:string, parts:array<int, int>, initiated:DateTimeInterface}> */
    public array $uploads = [];

    /** @var array<string, array<int, string>> upload id => the ETags it was completed with */
    public array $completed = [];

    /** @var list<string> */
    public array $aborted = [];

    public function start(string $disk, string $key, string $contentType): string
    {
        $uploadId = 'upload-'.Str::random(8);

        $this->uploads[$uploadId] = [
            'key' => $key,
            'content_type' => $contentType,
            'parts' => [],
            'initiated' => now()->toImmutable(),
        ];

        return $uploadId;
    }

    /** What the device's PUT of one part would leave in storage. */
    public function receivePart(string $uploadId, int $number, int $size): void
    {
        $this->uploads[$uploadId]['parts'][$number] = $size;
    }

    /** Add an upload storage holds, as if started at the given time. */
    public function seed(string $uploadId, string $key, DateTimeInterface $initiated): void
    {
        $this->uploads[$uploadId] = ['key' => $key, 'content_type' => 'audio/mp4', 'parts' => [], 'initiated' => $initiated];
    }

    public function parts(string $disk, string $key, string $uploadId): array
    {
        $upload = $this->existing($key, $uploadId);

        $parts = [];
        foreach ($upload['parts'] as $number => $size) {
            $parts[$number] = ['size' => $size, 'etag' => "\"etag-{$number}\""];
        }
        ksort($parts);

        return $parts;
    }

    public function partUrl(string $disk, string $key, string $uploadId, int $partNumber, DateTimeInterface $expiresAt): array
    {
        return ['url' => "https://storage.test/{$key}?uploadId={$uploadId}&partNumber={$partNumber}", 'headers' => []];
    }

    public function complete(string $disk, string $key, string $uploadId, array $etags): void
    {
        $this->existing($key, $uploadId);

        $this->completed[$uploadId] = $etags;
        unset($this->uploads[$uploadId]);
    }

    public function abort(string $disk, string $key, string $uploadId): void
    {
        $this->aborted[] = $uploadId;
        unset($this->uploads[$uploadId]);
    }

    public function incomplete(string $disk, string $prefix): iterable
    {
        foreach ($this->uploads as $uploadId => $upload) {
            if (str_starts_with($upload['key'], $prefix)) {
                yield ['key' => $upload['key'], 'upload_id' => $uploadId, 'initiated' => $upload['initiated']];
            }
        }
    }

    /** @return array{key:string, content_type:string, parts:array<int, int>, initiated:DateTimeInterface} */
    private function existing(string $key, string $uploadId): array
    {
        $upload = $this->uploads[$uploadId] ?? null;

        if ($upload === null || $upload['key'] !== $key) {
            throw new UploadGone("No such upload {$uploadId}");
        }

        return $upload;
    }
}
