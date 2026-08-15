<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hides the public marketing pages unless this deployment has opted into them.
 *
 * The landing, about and contact pages describe a particular operator, and the
 * privacy policy and terms make legal claims on their behalf. Serving those
 * from someone else's installation would be a false statement about their
 * service, so a fresh install answers 404 until it has published its own.
 */
class EnsurePublicSiteEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('padiush.public_site_enabled'), 404);

        return $next($request);
    }
}
