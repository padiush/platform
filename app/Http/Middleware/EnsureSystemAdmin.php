<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureSystemAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::user() || ! Auth::user()->system_admin) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
