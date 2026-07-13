<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/me — the identity + project list the device caches to know what
 * it may do offline. Only projects where the user can record data are returned;
 * the capabilities map lets the app hide what the user can't do.
 */
class MeController extends ApiController
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $projects = $user->projectAccesses()
            ->with(['project', 'capability'])
            ->get()
            ->filter(fn ($access) => $access->project && $access->capability?->record_data)
            ->map(fn ($access) => [
                'id' => $access->project->id,
                'name' => $access->project->name,
                'capabilities' => $access->capability->flags(),
                'updated_at' => $access->project->updated_at?->toIso8601String(),
            ])
            ->values();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'projects' => $projects,
        ]);
    }
}
