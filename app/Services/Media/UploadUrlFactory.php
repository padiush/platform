<?php

namespace App\Services\Media;

/**
 * Issues a presigned direct-to-storage upload URL. Abstracted so the media
 * endpoints don't depend on a live S3/MinIO connection (and so tests can bind a
 * deterministic fake). See docs/contracts/companion-api.md — large files are
 * PUT straight to object storage, never through the app server.
 */
interface UploadUrlFactory
{
    /**
     * @return array{url:string, headers:array<string,string>, expires_at:string}
     */
    public function create(string $disk, string $key, string $contentType, int $ttlMinutes): array;
}
