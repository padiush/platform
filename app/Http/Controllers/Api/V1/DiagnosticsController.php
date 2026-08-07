<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\StoreDiagnosticsRequest;
use App\Models\DeviceDiagnostic;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Integrity events from the companion app (docs/contracts/companion-api.md).
 *
 * Not tied to a project: the events that matter most are the ones where the
 * local store was destroyed, and at that point the device may no longer know
 * which project the lost work belonged to. Account scope is enough, and it is
 * what the token already establishes.
 *
 * Accepting a batch is idempotent on `client_id`, so a device that never saw
 * our response can retry the same events without duplicating them. The reply
 * lists what is now safely stored, and only those get cleared from the phone.
 */
class DiagnosticsController extends ApiController
{
    public function store(StoreDiagnosticsRequest $request): JsonResponse
    {
        $user = $request->user();
        $accepted = [];

        foreach ($request->validated('events') as $event) {
            $diagnostic = DeviceDiagnostic::firstOrCreate(
                ['client_id' => $event['client_id']],
                [
                    'user_id' => $user->id,
                    'code' => $event['code'],
                    'occurred_at' => $event['occurred_at'],
                    'app_version' => $event['app_version'] ?? null,
                    'platform' => $event['platform'] ?? null,
                    'os_version' => $event['os_version'] ?? null,
                ]
            );

            // Surface the destructive ones where they will actually be seen.
            // wasRecentlyCreated keeps a retried batch from re-alerting.
            if ($diagnostic->wasRecentlyCreated && $diagnostic->isSevere()) {
                Log::warning('Companion reported a capture integrity event', [
                    'code' => $diagnostic->code,
                    'user_id' => $user->id,
                    'occurred_at' => $diagnostic->occurred_at?->toIso8601String(),
                    'platform' => $diagnostic->platform,
                    'app_version' => $diagnostic->app_version,
                ]);
            }

            $accepted[] = $diagnostic->client_id;
        }

        return response()->json(['accepted' => $accepted], 202);
    }
}
