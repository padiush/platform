<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksProjectDataAccess;
use App\Models\InterviewInstance;
use App\Models\Media;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Looking at what the companion captured.
 *
 * Photographs and audio have been syncing against interviews since the capture
 * app shipped, and until now nothing on the web could show them — a researcher
 * could read the answers but not see or hear the evidence behind them.
 *
 * Read-only on purpose. The device authors this material and owns its lifecycle
 * (docs/decisions/0004-offline-sync-model.md); deleting an informant's
 * recording from a browser is a different decision from viewing it, and is not
 * one this makes.
 */
class InterviewMediaController extends Controller
{
    use ChecksProjectDataAccess;

    /**
     * What the interview carries, as the viewer needs it.
     *
     * Gated on the same capability as the answers themselves: this is interview
     * data, and a photograph of someone's plot is no less theirs than what they
     * said about it.
     */
    public function index(
        Request $request,
        Project $project,
        InterviewInstance $instance
    ): JsonResponse {
        $this->checkPermission($project, true);

        abort_unless($this->belongsToProject($instance, $project), 404);

        return response()->json([
            'media' => $instance->media()->orderBy('captured_at')->get()->map(fn (Media $medium) => [
                'id' => $medium->id,
                'kind' => $medium->kind,
                'content_type' => $medium->content_type,
                'captured_at' => $medium->captured_at?->toIso8601String(),
                'duration_s' => $medium->duration_s,
                // Null until a real queue and a transcriber are provisioned
                // (docs/decisions/0005-interview-transcription-whisper.md), so
                // the viewer shows it only when it is there.
                'transcription_status' => $medium->transcription_status,
                'transcription_text' => $medium->transcription_text,
                'url' => route('data.media.show', [
                    'project' => $project->id,
                    'instance' => $instance->id,
                    'medium' => $medium->id,
                ]),
            ])->values(),
        ]);
    }

    /**
     * Stream the bytes.
     *
     * Checked per request against the project rather than handed out as a
     * signed URL, so removing someone from a study removes their access to its
     * recordings at that moment.
     */
    public function show(
        Project $project,
        InterviewInstance $instance,
        Media $medium
    ): StreamedResponse|RedirectResponse {
        $this->checkPermission($project);

        abort_unless(
            $this->belongsToProject($instance, $project)
                && $medium->interview_instance_id === $instance->id,
            404
        );

        $disk = Storage::disk($medium->storage_disk);

        abort_unless(
            $medium->storage_key !== null && $disk->exists($medium->storage_key),
            404
        );

        return $disk->response(
            $medium->storage_key,
            null,
            ['Content-Type' => $medium->content_type]
        );
    }

    private function belongsToProject(InterviewInstance $instance, Project $project): bool
    {
        return $instance->form?->project_id === $project->id;
    }
}
