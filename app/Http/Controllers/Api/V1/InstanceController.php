<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\InterviewInstance;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/instances/{instance} — how a device learns about a captured
 * interview after the fact: its media and, for audio, the transcription status
 * and text once ready. Transcripts arrive here on a later pull, never blocking
 * capture or push (docs/contracts/sync-protocol.md).
 */
class InstanceController extends ApiController
{
    public function show(Request $request, InterviewInstance $instance): JsonResponse
    {
        $project = $this->projectForInstance($instance);
        $this->requireCapability($request->user(), $project, 'record_data');

        $instance->load('media');

        return response()->json([
            'id' => $instance->id,
            'interview_form_id' => $instance->interview_form_id,
            'captured_at' => $instance->captured_at?->toIso8601String(),
            'location' => $this->location($instance),
            'answers_count' => $instance->answers()->count(),
            'media' => $instance->media->map(fn ($media) => $this->mediaPayload($media))->values(),
        ]);
    }

    private function location(InterviewInstance $instance): ?array
    {
        if ($instance->location_lat === null || $instance->location_lng === null) {
            return null;
        }

        return [
            'lat' => $instance->location_lat,
            'lng' => $instance->location_lng,
            'accuracy_m' => $instance->location_accuracy_m,
            'captured_at' => $instance->location_captured_at?->toIso8601String(),
        ];
    }

    private function mediaPayload(Media $media): array
    {
        return [
            'id' => $media->id,
            'client_id' => $media->client_id,
            'kind' => $media->kind,
            'status' => $media->status,
            'content_type' => $media->content_type,
            'duration_s' => $media->duration_s,
            'transcription' => $media->isAudio() ? [
                'status' => $media->transcription_status,
                'text' => $media->transcription_status === 'done'
                    ? $media->transcription_text
                    : null,
            ] : null,
        ];
    }
}
