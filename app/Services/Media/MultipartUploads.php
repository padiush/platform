<?php

namespace App\Services\Media;

use DateTimeInterface;

/**
 * The object-storage half of a resumable upload: an S3 multipart upload the
 * server starts, lists and finishes, while the device sends the parts straight
 * to storage. Abstracted, as UploadUrlFactory is, so the media endpoints can be
 * tested without a live bucket. See
 * docs/decisions/0012-resumable-media-upload.md.
 */
interface MultipartUploads
{
    /** Start a multipart upload for the key, and return its upload id. */
    public function start(string $disk, string $key, string $contentType): string;

    /**
     * The parts storage holds so far, by part number.
     *
     * @return array<int, array{size:int, etag:string}>
     *
     * @throws UploadGone when the upload was aborted or never existed
     */
    public function parts(string $disk, string $key, string $uploadId): array;

    /**
     * A presigned URL the device PUTs one part's bytes to.
     *
     * @return array{url:string, headers:array<string,string>}
     */
    public function partUrl(string $disk, string $key, string $uploadId, int $partNumber, DateTimeInterface $expiresAt): array;

    /**
     * Assemble the object from its parts.
     *
     * @param  array<int, string>  $etags  part number => ETag, as parts() listed them
     *
     * @throws UploadGone when the upload was aborted or never existed
     */
    public function complete(string $disk, string $key, string $uploadId, array $etags): void;

    /** Abort the upload and discard its parts. An upload already gone is not an error. */
    public function abort(string $disk, string $key, string $uploadId): void;

    /**
     * Every multipart upload under the key prefix that was started and never
     * completed or aborted.
     *
     * @return iterable<array{key:string, upload_id:string, initiated:DateTimeInterface}>
     */
    public function incomplete(string $disk, string $prefix): iterable;
}
