<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\StoreTokenRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Personal access tokens for the mobile companion apps. Devices can't use the
 * web session cookie, so they exchange credentials for a Sanctum bearer token.
 *
 * Tokens are issued with a single `capture` ability; the per-project gate is
 * still enforced by ProjectPolicy on every request, so the token is user-scoped,
 * not project-scoped (docs/decisions register, 2026-07-12).
 */
class TokenController extends ApiController
{
    public function store(StoreTokenRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email'))->first();

        // One uniform failure for a bad email or a bad password — no enumeration.
        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            $this->fail('api.tokens.invalid_credentials', 422);
        }

        $token = $user->createToken($request->string('device_name'), ['capture']);

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Revoke the token this request authenticated with — "sign this device out".
     * The web manages the rest of a user's device tokens via Sanctum.
     */
    public function destroyCurrent(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'api.tokens.revoked',
            'message_type' => 'success',
        ]);
    }
}
