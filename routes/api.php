<?php

use App\Http\Controllers\Api\V1\BundleController;
use App\Http\Controllers\Api\V1\DiagnosticsController;
use App\Http\Controllers\Api\V1\FieldRecordSyncController;
use App\Http\Controllers\Api\V1\InstanceController;
use App\Http\Controllers\Api\V1\InstanceSyncController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\MediaController;
use App\Http\Controllers\Api\V1\TokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Companion capture API — v1
|--------------------------------------------------------------------------
|
| The HTTP surface the mobile companion apps build against. Contract:
| docs/contracts/companion-api.md; machine-readable spec: docs/api/openapi.yaml.
| Every authenticated route requires a Sanctum bearer token carrying the
| `capture` ability; per-project authorization is enforced by ProjectPolicy in
| the controllers. Breaking changes bump the version prefix.
|
*/
Route::prefix('v1')->name('api.v1.')->group(function () {
    // Public: exchange credentials for a device token (throttled hard).
    Route::post('tokens', [TokenController::class, 'store'])
        ->middleware('throttle:api-tokens')
        ->name('tokens.store');

    Route::middleware(['auth:sanctum', 'abilities:capture'])->group(function () {
        Route::delete('tokens/current', [TokenController::class, 'destroyCurrent'])
            ->name('tokens.destroy-current');

        // Pull — cache what the device needs offline.
        Route::get('me', [MeController::class, 'show'])->name('me');
        Route::get('projects/{project}/bundle', [BundleController::class, 'show'])
            ->name('projects.bundle');

        // Push — sync captured interviews (idempotent batch upsert).
        Route::post('projects/{project}/instances:sync', [InstanceSyncController::class, 'sync'])
            ->name('projects.instances.sync');

        // Push — sync field records (idempotent batch upsert). Interviews
        // first: a record that names the answer it came out of is refused
        // while that answer is unknown here.
        Route::post('projects/{project}/records:sync', [FieldRecordSyncController::class, 'sync'])
            ->name('projects.records.sync');

        // A captured interview after the fact — media and transcription status.
        Route::get('instances/{instance}', [InstanceController::class, 'show'])
            ->name('instances.show');

        // Media — audio & photos, uploaded direct to object storage.
        Route::post('instances/{instance}/media/intent', [MediaController::class, 'intent'])
            ->name('instances.media.intent');
        Route::post('instances/{instance}/media/complete', [MediaController::class, 'complete'])
            ->name('instances.media.complete');

        // Integrity events from the device — a closed set of codes, no payload.
        // Account-scoped rather than per-project: the events worth reporting
        // are the ones where the local store is gone, and with it any record
        // of which project the lost work belonged to.
        Route::post('diagnostics', [DiagnosticsController::class, 'store'])
            ->name('diagnostics.store');
    });
});
