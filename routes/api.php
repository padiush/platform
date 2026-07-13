<?php

use App\Http\Controllers\Api\V1\BundleController;
use App\Http\Controllers\Api\V1\MeController;
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
    });
});
