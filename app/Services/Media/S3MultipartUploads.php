<?php

namespace App\Services\Media;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use DateTimeInterface;
use Illuminate\Support\Facades\Storage;

/**
 * Multipart uploads on the S3 disk, through the same client (and so the same
 * endpoint) that signs the single-PUT URLs.
 */
class S3MultipartUploads implements MultipartUploads
{
    public function start(string $disk, string $key, string $contentType): string
    {
        return $this->client($disk)->createMultipartUpload([
            'Bucket' => $this->bucket($disk),
            'Key' => $key,
            // Set here, by the server, because a part carries no content type.
            'ContentType' => $contentType,
        ])['UploadId'];
    }

    public function parts(string $disk, string $key, string $uploadId): array
    {
        $parts = [];

        $pages = $this->client($disk)->getPaginator('ListParts', [
            'Bucket' => $this->bucket($disk),
            'Key' => $key,
            'UploadId' => $uploadId,
        ]);

        $this->whileUploadExists(function () use ($pages, &$parts) {
            foreach ($pages as $page) {
                foreach ($page['Parts'] ?? [] as $part) {
                    $parts[(int) $part['PartNumber']] = [
                        'size' => (int) $part['Size'],
                        'etag' => (string) $part['ETag'],
                    ];
                }
            }
        });

        ksort($parts);

        return $parts;
    }

    public function partUrl(string $disk, string $key, string $uploadId, int $partNumber, DateTimeInterface $expiresAt): array
    {
        $client = $this->client($disk);

        $command = $client->getCommand('UploadPart', [
            'Bucket' => $this->bucket($disk),
            'Key' => $key,
            'UploadId' => $uploadId,
            'PartNumber' => $partNumber,
        ]);

        // A part has no signed headers besides Host, which the client sets
        // from the URL itself (see S3UploadUrlFactory).
        return [
            'url' => (string) $client->createPresignedRequest($command, $expiresAt)->getUri(),
            'headers' => [],
        ];
    }

    public function complete(string $disk, string $key, string $uploadId, array $etags): void
    {
        ksort($etags);

        $this->whileUploadExists(fn () => $this->client($disk)->completeMultipartUpload([
            'Bucket' => $this->bucket($disk),
            'Key' => $key,
            'UploadId' => $uploadId,
            'MultipartUpload' => [
                'Parts' => array_map(
                    fn (int $number, string $etag) => ['PartNumber' => $number, 'ETag' => $etag],
                    array_keys($etags),
                    array_values($etags)
                ),
            ],
        ]));
    }

    public function abort(string $disk, string $key, string $uploadId): void
    {
        try {
            $this->whileUploadExists(fn () => $this->client($disk)->abortMultipartUpload([
                'Bucket' => $this->bucket($disk),
                'Key' => $key,
                'UploadId' => $uploadId,
            ]));
        } catch (UploadGone) {
            // Already aborted or expired: the outcome asked for.
        }
    }

    public function incomplete(string $disk, string $prefix): iterable
    {
        $pages = $this->client($disk)->getPaginator('ListMultipartUploads', [
            'Bucket' => $this->bucket($disk),
            'Prefix' => $prefix,
        ]);

        foreach ($pages as $page) {
            foreach ($page['Uploads'] ?? [] as $upload) {
                yield [
                    'key' => (string) $upload['Key'],
                    'upload_id' => (string) $upload['UploadId'],
                    'initiated' => $upload['Initiated'],
                ];
            }
        }
    }

    /**
     * Run a call against one upload, turning storage's "no such upload" into
     * UploadGone so callers never see an SDK exception for the expected case.
     */
    private function whileUploadExists(callable $call): mixed
    {
        try {
            return $call();
        } catch (S3Exception $e) {
            if ($e->getAwsErrorCode() === 'NoSuchUpload') {
                throw new UploadGone($e->getMessage(), 0, $e);
            }

            throw $e;
        }
    }

    private function client(string $disk): S3Client
    {
        return Storage::disk($disk)->getClient();
    }

    private function bucket(string $disk): string
    {
        return (string) config("filesystems.disks.{$disk}.bucket");
    }
}
