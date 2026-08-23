<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\SyncFieldRecordsRequest;
use App\Models\Project;
use App\Services\FieldRecordSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * POST /api/v1/projects/{project}/records:sync — one idempotent, batched upsert
 * of field records captured in the field
 * (docs/decisions/0011-companion-field-records.md). Shaped like
 * `instances:sync`: each element carries its own result
 * (created / updated / unchanged / rejected), so partial success is normal and
 * the device retries only what it must.
 *
 * Results are keyed by `client_id`, since a record created offline has no
 * server id until this call answers with one — unlike an interview, whose id
 * the device mints itself.
 *
 * Push interviews before records. A record that names the answer it came out of
 * is refused while that answer is unknown here, so that the link survives
 * rather than being dropped to let the record through.
 */
class FieldRecordSyncController extends ApiController
{
    public function sync(
        SyncFieldRecordsRequest $request,
        Project $project,
        FieldRecordSyncService $service
    ): JsonResponse {
        $user = $request->user();

        // The same capability that admits an interview: recording is recording,
        // whatever was in front of the researcher.
        $this->requireCapability($user, $project, 'record_data');

        $cacheKey = $this->idempotencyCacheKey($request->header('Idempotency-Key'), $user->id, $project->id);

        if ($cacheKey !== null && ($cached = Cache::get($cacheKey)) !== null) {
            return response()->json($cached);
        }

        $results = [];

        foreach ($request->input('records') as $recordPayload) {
            $results[] = $service->syncRecord($user, $project, $recordPayload);
        }

        $body = ['results' => $results];

        if ($cacheKey !== null) {
            Cache::put($cacheKey, $body, now()->addMinutes(10));
        }

        return response()->json($body);
    }

    /**
     * Namespaced apart from the interview sync's key, so the same
     * Idempotency-Key used for both batches cannot have one return the other's
     * results.
     */
    private function idempotencyCacheKey(?string $header, int $userId, int $projectId): ?string
    {
        $header = trim((string) $header);

        return $header === '' ? null : "api:records-sync:{$userId}:{$projectId}:{$header}";
    }
}
