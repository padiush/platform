<?php

namespace App\Http\Controllers;

use App\Services\WfoNameResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class WfoController extends Controller
{
    public function query(Request $request, WfoNameResolver $resolver): JsonResponse
    {
        $validated = $request->validate([
            'genus' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'authority' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $result = $resolver->resolve(
                $validated['genus'],
                $validated['name'],
                $validated['authority'] ?? null,
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json(['error' => 'wfo_unreachable'], 502);
        }

        return response()->json($result);
    }
}
