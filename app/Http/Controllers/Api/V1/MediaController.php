<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\CompleteMediaRequest;
use App\Http\Requests\Api\StoreMediaIntentRequest;
use App\Jobs\TranscribeAudio;
use App\Models\InstanceMedia;
use App\Models\InterviewInstance;
use App\Services\Media\UploadUrlFactory;
use Illuminate\Http\JsonResponse;

/**
 * Audio and photo capture. Large files should not stream through the app server,
 * so the flow is: register intent (get a presigned direct-to-storage URL), the
 * device PUTs the file to object storage, then complete registers it. For audio,
 * complete enqueues transcription when it is enabled. See
 * docs/contracts/companion-api.md.
 */
class MediaController extends ApiController
{
    private const DISK = 's3';

    private const UPLOAD_TTL_MINUTES = 15;

    public function intent(
        StoreMediaIntentRequest $request,
        InterviewInstance $instance,
        UploadUrlFactory $urls
    ): JsonResponse {
        $project = $this->projectForInstance($instance);
        $this->requireCapability($request->user(), $project, 'record_data');

        $clientId = $request->input('client_id');
        $kind = $request->input('kind');
        $contentType = $request->input('content_type');
        $this->assertContentTypeMatchesKind($kind, $contentType);

        $media = InstanceMedia::firstOrNew(['client_id' => $clientId]);

        // A client_id already used on another instance is a conflict.
        if ($media->exists && $media->interview_instance_id !== $instance->id) {
            $this->fail('api.media.client_id_conflict', 409);
        }

        $key = $media->storage_key
            ?? $this->storageKey($project->id, $instance->id, $clientId, $contentType);

        $media->fill([
            'interview_instance_id' => $instance->id,
            'kind' => $kind,
            'storage_disk' => self::DISK,
            'storage_key' => $key,
            'content_type' => $contentType,
            'byte_size' => $request->integer('byte_size'),
            'status' => InstanceMedia::STATUS_PENDING,
        ])->save();

        $presigned = $urls->create(self::DISK, $key, $contentType, self::UPLOAD_TTL_MINUTES);

        return response()->json([
            'upload_url' => $presigned['url'],
            'headers' => $presigned['headers'],
            'storage_key' => $key,
            'expires_at' => $presigned['expires_at'],
        ]);
    }

    public function complete(CompleteMediaRequest $request, InterviewInstance $instance): JsonResponse
    {
        $project = $this->projectForInstance($instance);
        $this->requireCapability($request->user(), $project, 'record_data');

        $media = InstanceMedia::where('client_id', $request->input('client_id'))
            ->where('interview_instance_id', $instance->id)
            ->first();

        if (! $media) {
            $this->fail('api.media.not_found', 404);
        }

        // The completing key must be the one we issued at intent.
        if ($media->storage_key !== $request->input('storage_key')) {
            $this->fail('api.media.storage_key_mismatch', 422);
        }

        $media->status = InstanceMedia::STATUS_STORED;

        if ($request->filled('duration_s')) {
            $media->duration_s = $request->integer('duration_s');
        }

        $queued = $media->isAudio() && config('services.transcription.enabled');

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

    private function assertContentTypeMatchesKind(string $kind, string $contentType): void
    {
        $prefix = $kind === InstanceMedia::KIND_AUDIO ? 'audio/' : 'image/';

        if (! str_starts_with($contentType, $prefix)) {
            $this->fail('api.validation_failed', 422, [
                'content_type' => ['api.media.content_type_mismatch'],
            ]);
        }
    }

    private function storageKey(int $projectId, string $instanceId, string $clientId, string $contentType): string
    {
        $extension = $this->extension($contentType);

        return "projects/{$projectId}/instances/{$instanceId}/media/{$clientId}.{$extension}";
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
}
