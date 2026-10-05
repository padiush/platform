<?php

namespace App\Http\Middleware;

use App\Services\ActiveProject;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Remember the project a page belongs to as the one its user is working in,
 * so the sidebar opens on it next time, wherever they sign in from.
 * Runs after route model binding, which is where the project is found.
 */
class RememberActiveProject
{
    public function __construct(private readonly ActiveProject $active) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $request->isMethod('GET')) {
            $project = $this->active->fromRoute($request);

            if ($project) {
                $this->active->remember($user, $project);
            }
        }

        return $next($request);
    }
}
