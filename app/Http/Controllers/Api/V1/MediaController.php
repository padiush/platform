<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\CompleteMediaRequest;
use App\Http\Requests\Api\MediaPartsRequest;
use App\Http\Requests\Api\StoreMediaIntentRequest;
use App\Jobs\TranscribeAudio;
use App\Models\FieldRecord;
use App\Models\InterviewInstance;
use App\Models\Media;
use App\Models\Project;
use App\Services\Media\MultipartUploads;
use App\Services\Media\StoredObjectInspector;
use App\Services\Media\UploadGone;
use App\Services\Media\UploadUrlFactory;
use Illuminate\Http\JsonResponse;

/**
 * Audio and photo capture. Large files should not stream through the app server,
 * so the flow is: register intent (get a presigned direct-to-storage URL), the
 * device PUTs the file to object storage, then complete registers it. For audio,
 * complete enqueues transcription when it is enabled. See
 * docs/contracts/companion-api.md.
 *
 * Media belongs to an interview or to a field record, and both go through the
 * same handshake: only the owner, its access check and the storage prefix
 * differ, so the handshake itself is written once below.
 *
 * A device that asks for it sends a large file as a multipart upload instead,
 * with one more step between intent and complete: `parts` lists what storage
 * already holds and signs only what is missing, so a dropped connection costs
 * one part rather than the file. The server keeps the upload; the device keeps
 * nothing new (docs/decisions/0012-resumable-media-upload.md).
 */
class MediaController extends ApiController
{
    private const DISK = 's3';

    private const UPLOAD_TTL_MINUTES = 15;

    /**
     * 8 MiB: above S3's 5 MiB floor for every part but the last, two of the
     * device's 4 MiB store chunks, and at most 63 parts for the largest file
     * intent accepts.
     */
    public const PART_BYTES = 8 * 1024 * 1024;

    private const OWNER_INSTANCE = 'interview_instance_id';

    private const OWNER_FIELD_RECORD = 'field_record_id';

    public function intent(
        StoreMediaIntentRequest $request,
        InterviewInstance $instance,
        UploadUrlFactory $urls,
        MultipartUploads $multipart
    ): JsonResponse {
        $project = $this->projectForInstance($instance);
        $this->requireCapability($request->user(), $project, 'record_data');

        return $this->registerIntent(
            $request,
            $urls,
            $multipart,
            self::OWNER_INSTANCE,
            $instance->id,
            "projects/{$project->id}/instances/{$instance->id}/media"
        );
    }

    public function parts(
        MediaPartsRequest $request,
        InterviewInstance $instance,
        MultipartUploads $multipart
    ): JsonResponse {
        $project = $this->projectForInstance($instance);
        $this->requireCapability($request->user(), $project, 'record_data');

        return $this->missingParts($request, $multipart, self::OWNER_INSTANCE, $instance->id);
    }

    public function complete(
        CompleteMediaRequest $request,
        InterviewInstance $instance,
        StoredObjectInspector $objects,
        MultipartUploads $multipart
    ): JsonResponse {
        $project = $this->projectForInstance($instance);
        $this->requireCapability($request->user(), $project, 'record_data');

        return $this->completeUpload($request, $objects, $multipart, self::OWNER_INSTANCE, $instance->id, true);
    }

    /**
     * A field record's photographs and audio, addressed by the server id that
     * records:sync returned — the device has no other way to name the record.
     * For a record of something never collected, these are the whole of the
     * evidence (docs/decisions/0010-field-records-and-basis.md).
     */
    public function recordIntent(
        StoreMediaIntentRequest $request,
        FieldRecord $record,
        UploadUrlFactory $urls,
        MultipartUploads $multipart
    ): JsonResponse {
        $project = $this->projectForRecord($record);
        $this->requireCapability($request->user(), $project, 'record_data');

        // The same prefix the web's own uploads use for this record.
        return $this->registerIntent(
            $request,
            $urls,
            $multipart,
            self::OWNER_FIELD_RECORD,
            $record->id,
            "projects/{$project->id}/field-records/{$record->id}/media"
        );
    }

    /**
     * Never queues transcription. That pipeline serves interview audio
     * (docs/decisions/0005-interview-transcription-whisper.md), and its result
     * reaches the device through GET /instances/{instance}, which a record has
     * no counterpart to — a transcript would be produced for nobody to read.
     */
    public function recordComplete(
        CompleteMediaRequest $request,
        FieldRecord $record,
        StoredObjectInspector $objects,
        MultipartUploads $multipart
    ): JsonResponse {
        $project = $this->projectForRecord($record);
        $this->requireCapability($request->user(), $project, 'record_data');

        return $this->completeUpload($request, $objects, $multipart, self::OWNER_FIELD_RECORD, $record->id, false);
    }

    public function recordParts(
        MediaPartsRequest $request,
        FieldRecord $record,
        MultipartUploads $multipart
    ): JsonResponse {
        $project = $this->projectForRecord($record);
        $this->requireCapability($request->user(), $project, 'record_data');

        return $this->missingParts($request, $multipart, self::OWNER_FIELD_RECORD, $record->id);
    }

    /**
     * Register (or re-register) the device's intent to upload, and issue a
     * presigned URL for it, or start a multipart upload when the device asked
     * to resume and the file is larger than one part. Idempotent on
     * `client_id`: a retried intent for the same owner gets the same storage
     * key back, and the same multipart upload while the file is unchanged.
     */
    private function registerIntent(
        StoreMediaIntentRequest $request,
        UploadUrlFactory $urls,
        MultipartUploads $multipart,
        string $ownerColumn,
        int|string $ownerId,
        string $keyPrefix
    ): JsonResponse {
        $clientId = $request->input('client_id');
        $kind = $request->input('kind');
        $contentType = $request->input('content_type');
        $this->assertContentTypeMatchesKind($kind, $contentType);

        $media = Media::firstOrNew(['client_id' => $clientId]);

        // A client_id already used by another owner — another interview,
        // another record, or the other kind of owner altogether — is a conflict.
        if ($media->exists && ! $this->ownedBy($media, $ownerColumn, $ownerId)) {
            $this->fail('api.media.client_id_conflict', 409);
        }

        $key = $media->storage_key
            ?? "{$keyPrefix}/{$clientId}.{$this->extension($contentType)}";
        $byteSize = $request->integer('byte_size');
        $resumable = $request->boolean('resumable');
        $multipartWanted = $resumable && $byteSize > self::PART_BYTES;

        // An open upload is resumed only for the same file split the same way.
        // Anything else — another size, another type, a single PUT this time —
        // leaves its parts unusable, so they are discarded now rather than
        // left for cleanup.
        $resumes = $multipartWanted
            && $media->isMultipart()
            && $media->byte_size === $byteSize
            && $media->content_type === $contentType
            && $media->upload_part_size === self::PART_BYTES;

        if ($media->isMultipart() && ! $resumes) {
            $multipart->abort($media->storage_disk, $media->storage_key, $media->upload_id);
            $media->upload_id = null;
            $media->upload_part_size = null;
        }

        $media->fill([
            $ownerColumn => $ownerId,
            'kind' => $kind,
            'storage_disk' => self::DISK,
            'storage_key' => $key,
            'content_type' => $contentType,
            'byte_size' => $byteSize,
            'status' => Media::STATUS_PENDING,
        ]);

        if ($multipartWanted) {
            if (! $resumes) {
                $media->upload_id = $multipart->start(self::DISK, $key, $contentType);
                $media->upload_part_size = self::PART_BYTES;
            }

            $media->save();

            return response()->json([
                'storage_key' => $key,
                'upload' => [
                    'mode' => 'multipart',
                    'part_size' => $media->upload_part_size,
                    'part_count' => $media->partCount(),
                ],
            ]);
        }

        $media->save();

        $presigned = $urls->create(self::DISK, $key, $contentType, self::UPLOAD_TTL_MINUTES);

        $response = [
            'upload_url' => $presigned['url'],
            'headers' => $presigned['headers'],
            'storage_key' => $key,
            'expires_at' => $presigned['expires_at'],
        ];

        // Said only to a device that asked, so older clients see exactly the
        // response they were written against.
        if ($resumable) {
            $response['upload'] = ['mode' => 'single'];
        }

        return response()->json($response);
    }

    /**
     * Sign the parts of a multipart upload that storage does not hold yet, or
     * holds at the wrong size. An empty list means every part is there and the
     * device should complete. Asked as often as the device likes: the URLs
     * expire, the upload does not, so a file sent over hours simply asks again.
     */
    private function missingParts(
        MediaPartsRequest $request,
        MultipartUploads $multipart,
        string $ownerColumn,
        int|string $ownerId
    ): JsonResponse {
        $media = $this->mediaForUpload($request->input('client_id'), $request->input('storage_key'), $ownerColumn, $ownerId);

        // Already assembled: nothing left to send, and complete will say so.
        if ($media->status === Media::STATUS_STORED) {
            return response()->json(['parts' => [], 'expires_at' => null]);
        }

        if (! $media->isMultipart()) {
            $this->fail('api.media.upload_expired', 410);
        }

        try {
            $stored = $multipart->parts($media->storage_disk, $media->storage_key, $media->upload_id);
        } catch (UploadGone) {
            $this->forgetUpload($media);
        }

        $expiresAt = now()->addMinutes(self::UPLOAD_TTL_MINUTES);
        $parts = [];

        foreach (range(1, $media->partCount()) as $number) {
            if (($stored[$number]['size'] ?? null) === $media->expectedPartSize($number)) {
                continue;
            }

            $signed = $multipart->partUrl($media->storage_disk, $media->storage_key, $media->upload_id, $number, $expiresAt);

            $parts[] = [
                'number' => $number,
                'url' => $signed['url'],
                'headers' => $signed['headers'],
            ];
        }

        return response()->json([
            'parts' => $parts,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    /**
     * Confirm an upload landed as announced, and mark it stored. The object is
     * inspected rather than taken on the device's word: a missing, truncated or
     * mislabelled upload is refused here instead of surfacing later as a
     * broken file nobody can account for.
     */
    private function completeUpload(
        CompleteMediaRequest $request,
        StoredObjectInspector $objects,
        MultipartUploads $multipart,
        string $ownerColumn,
        int|string $ownerId,
        bool $transcribe
    ): JsonResponse {
        $media = $this->mediaForUpload($request->input('client_id'), $request->input('storage_key'), $ownerColumn, $ownerId);

        $wasMultipart = $media->isMultipart();

        if ($wasMultipart) {
            $this->assembleParts($media, $multipart);
        }

        $stored = $objects->inspect($media->storage_disk, $media->storage_key);

        if ($stored === null) {
            // A multipart file with no upload and no object has lost its parts;
            // the device starts again from intent rather than retrying this.
            $this->fail($wasMultipart ? 'api.media.upload_expired' : 'api.media.upload_missing', $wasMultipart ? 410 : 422);
        }

        if ($media->byte_size !== null && $stored['byte_size'] !== $media->byte_size) {
            $this->fail('api.media.byte_size_mismatch', 422);
        }

        if (
            $stored['content_type'] === null
            || $this->normalizedContentType($stored['content_type']) !== $this->normalizedContentType($media->content_type)
        ) {
            $this->fail('api.media.content_type_mismatch', 422);
        }

        $media->status = Media::STATUS_STORED;

        if ($request->filled('duration_s')) {
            $media->duration_s = $request->integer('duration_s');
        }

        $queued = $transcribe && $media->isAudio() && config('services.transcription.enabled');

        if ($queued) {
            $media->transcription_status = 'queued';
        }

        $media->save();

        if ($queued) {
            TranscribeAudio::dispatch($media);
        }

        $response = ['id' => $media->id, 'status' => $media->status];

        if ($queued) {
            $response['transcription'] = 'queued';
        }

        return response()->json($response);
    }

    /**
     * Turn a complete set of parts into the object, refusing an incomplete
     * one. If storage no longer has the upload, the object may already have
     * been assembled by an earlier complete whose answer never reached the
     * device, so the caller goes on to inspect storage either way.
     */
    private function assembleParts(Media $media, MultipartUploads $multipart): void
    {
        try {
            $stored = $multipart->parts($media->storage_disk, $media->storage_key, $media->upload_id);
        } catch (UploadGone) {
            $this->clearUpload($media);

            return;
        }

        $etags = [];

        foreach (range(1, $media->partCount()) as $number) {
            if (($stored[$number]['size'] ?? null) !== $media->expectedPartSize($number)) {
                $this->fail('api.media.upload_incomplete', 422);
            }

            $etags[$number] = $stored[$number]['etag'];
        }

        try {
            $multipart->complete($media->storage_disk, $media->storage_key, $media->upload_id, $etags);
        } catch (UploadGone) {
            // Gone between the listing and now; inspection decides.
        }

        $this->clearUpload($media);
    }

    /**
     * The media row an upload step names, by the device's `client_id` under
     * this owner, with the storage key the intent issued.
     */
    private function mediaForUpload(string $clientId, string $storageKey, string $ownerColumn, int|string $ownerId): Media
    {
        $media = Media::where('client_id', $clientId)
            ->where($ownerColumn, $ownerId)
            ->first();

        if (! $media) {
            $this->fail('api.media.not_found', 404);
        }

        // The key must be the one we issued at intent.
        if ($media->storage_key !== $storageKey) {
            $this->fail('api.media.storage_key_mismatch', 422);
        }

        return $media;
    }

    /** Forget a multipart upload storage no longer has. */
    private function clearUpload(Media $media): void
    {
        $media->upload_id = null;
        $media->upload_part_size = null;
        $media->save();
    }

    /** Forget an upload found gone, and tell the device to start again. */
    private function forgetUpload(Media $media): never
    {
        $this->clearUpload($media);

        $this->fail('api.media.upload_expired', 410);
    }

    /**
     * Whether this media row belongs to the given owner. Compared as strings:
     * an interview id is a uuid, a record id an integer, and the database
     * driver decides which type a column comes back as.
     */
    private function ownedBy(Media $media, string $ownerColumn, int|string $ownerId): bool
    {
        return $media->{$ownerColumn} !== null
            && (string) $media->{$ownerColumn} === (string) $ownerId;
    }

    /** The project a record belongs to, or 404 if it is orphaned. */
    private function projectForRecord(FieldRecord $record): Project
    {
        $project = $record->project;

        if (! $project) {
            $this->fail('api.not_found', 404);
        }

        return $project;
    }

    private function assertContentTypeMatchesKind(string $kind, string $contentType): void
    {
        $prefix = $kind === Media::KIND_AUDIO ? 'audio/' : 'image/';

        if (! str_starts_with($contentType, $prefix)) {
            $this->fail('api.validation_failed', 422, [
                'content_type' => ['api.media.content_type_mismatch'],
            ]);
        }
    }

    private function extension(string $contentType): string
    {
        return match ($contentType) {
            'audio/mp4', 'audio/x-m4a', 'audio/aac' => 'm4a',
            'audio/mpeg' => 'mp3',
            'audio/ogg', 'audio/opus' => 'ogg',
            'audio/wav', 'audio/x-wav' => 'wav',
            'audio/webm' => 'webm',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/heic', 'image/heif' => 'heic',
            default => 'bin',
        };
    }

    private function normalizedContentType(string $contentType): string
    {
        return strtolower(trim(explode(';', $contentType, 2)[0]));
    }
}
