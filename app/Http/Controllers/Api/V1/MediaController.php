<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\CompleteMediaRequest;
use App\Http\Requests\Api\StoreMediaIntentRequest;
use App\Jobs\TranscribeAudio;
use App\Models\FieldRecord;
use App\Models\InterviewInstance;
use App\Models\Media;
use App\Models\Project;
use App\Services\Media\StoredObjectInspector;
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
 */
class MediaController extends ApiController
{
    private const DISK = 's3';

    private const UPLOAD_TTL_MINUTES = 15;

    private const OWNER_INSTANCE = 'interview_instance_id';

    private const OWNER_FIELD_RECORD = 'field_record_id';

    public function intent(
        StoreMediaIntentRequest $request,
        InterviewInstance $instance,
        UploadUrlFactory $urls
    ): JsonResponse {
        $project = $this->projectForInstance($instance);
        $this->requireCapability($request->user(), $project, 'record_data');

        return $this->registerIntent(
            $request,
            $urls,
            self::OWNER_INSTANCE,
            $instance->id,
            "projects/{$project->id}/instances/{$instance->id}/media"
        );
    }

    public function complete(
        CompleteMediaRequest $request,
        InterviewInstance $instance,
        StoredObjectInspector $objects
    ): JsonResponse {
        $project = $this->projectForInstance($instance);
        $this->requireCapability($request->user(), $project, 'record_data');

        return $this->completeUpload($request, $objects, self::OWNER_INSTANCE, $instance->id, true);
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
        UploadUrlFactory $urls
    ): JsonResponse {
        $project = $this->projectForRecord($record);
        $this->requireCapability($request->user(), $project, 'record_data');

        // The same prefix the web's own uploads use for this record.
        return $this->registerIntent(
            $request,
            $urls,
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
        StoredObjectInspector $objects
    ): JsonResponse {
        $project = $this->projectForRecord($record);
        $this->requireCapability($request->user(), $project, 'record_data');

        return $this->completeUpload($request, $objects, self::OWNER_FIELD_RECORD, $record->id, false);
    }

    /**
     * Register (or re-register) the device's intent to upload, and issue a
     * presigned URL for it. Idempotent on `client_id`: a retried intent for
     * the same owner gets the same storage key back.
     */
    private function registerIntent(
        StoreMediaIntentRequest $request,
        UploadUrlFactory $urls,
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

        $media->fill([
            $ownerColumn => $ownerId,
            'kind' => $kind,
            'storage_disk' => self::DISK,
            'storage_key' => $key,
            'content_type' => $contentType,
            'byte_size' => $request->integer('byte_size'),
            'status' => Media::STATUS_PENDING,
        ])->save();

        $presigned = $urls->create(self::DISK, $key, $contentType, self::UPLOAD_TTL_MINUTES);

        return response()->json([
            'upload_url' => $presigned['url'],
            'headers' => $presigned['headers'],
            'storage_key' => $key,
            'expires_at' => $presigned['expires_at'],
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
        string $ownerColumn,
        int|string $ownerId,
        bool $transcribe
    ): JsonResponse {
        $media = Media::where('client_id', $request->input('client_id'))
            ->where($ownerColumn, $ownerId)
            ->first();

        if (! $media) {
            $this->fail('api.media.not_found', 404);
        }

        // The completing key must be the one we issued at intent.
        if ($media->storage_key !== $request->input('storage_key')) {
            $this->fail('api.media.storage_key_mismatch', 422);
        }

        $stored = $objects->inspect($media->storage_disk, $media->storage_key);

        if ($stored === null) {
            $this->fail('api.media.upload_missing', 422);
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
