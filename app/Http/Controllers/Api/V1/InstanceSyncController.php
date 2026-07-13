<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\SyncInstancesRequest;
use App\Models\Project;
use App\Services\InstanceSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * POST /api/v1/projects/{project}/instances:sync — one idempotent, batched
 * upsert of captured interviews. Each element carries its own result
 * (created / updated / unchanged / rejected), so partial success is normal and
 * the device retries only what it must.
 *
 * An optional Idempotency-Key header dedupes an entire batch within a short
 * window — covering the "did my POST land before the socket died?" case on top
 * of the per-record client_id idempotency.
 */
class InstanceSyncController extends ApiController
{
    public function sync(
        SyncInstancesRequest $request,
        Project $project,
        InstanceSyncService $service
    ): JsonResponse {
        $user = $request->user();

        $this->requireCapability($user, $project, 'record_data');

        $cacheKey = $this->idempotencyCacheKey($request->header('Idempotency-Key'), $user->id, $project->id);

        if ($cacheKey !== null && ($cached = Cache::get($cacheKey)) !== null) {
            return response()->json($cached);
        }

        $results = [];

        foreach ($request->input('instances') as $instancePayload) {
            $results[] = $service->syncInstance($user, $project, $instancePayload);
        }

        $body = ['results' => $results];

        if ($cacheKey !== null) {
            Cache::put($cacheKey, $body, now()->addMinutes(10));
        }

        return response()->json($body);
    }

    private function idempotencyCacheKey(?string $header, int $userId, int $projectId): ?string
    {
        $header = trim((string) $header);

        return $header === '' ? null : "api:sync:{$userId}:{$projectId}:{$header}";
    }
}
