<?php

namespace App\Services\Media;

use Illuminate\Support\Facades\Storage;

/**
 * Presigned uploads backed by the S3/MinIO disk. The device PUTs the file
 * directly to the returned URL (resumable/chunked), independent of the JSON sync.
 */
class S3UploadUrlFactory implements UploadUrlFactory
{
    public function create(string $disk, string $key, string $contentType, int $ttlMinutes): array
    {
        $expiresAt = now()->addMinutes($ttlMinutes);

        $presigned = Storage::disk($disk)->temporaryUploadUrl($key, $expiresAt);

        return [
            'url' => $presigned['url'],
            'headers' => array_merge(
                ['Content-Type' => $contentType],
                $presigned['headers'] ?? []
            ),
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }
}
