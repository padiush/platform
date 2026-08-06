<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aligns the server's locale with the one the visitor is actually reading.
 *
 * The interface is translated client-side by i18next, which persists the
 * chosen language in a cookie. Without this, `app()->getLocale()` stayed on the
 * configured default forever, so validation messages and — more visibly —
 * every notification email came out in Spanish no matter what the recipient
 * had selected.
 *
 * For signed-in users the choice is also stored on the account, because a
 * queued notification runs with no request and no cookie behind it. See
 * `User::preferredLocale()`.
 */
class SetLocale
{
    public const SUPPORTED = ['es', 'en', 'pt'];

    /** i18next's default cookie name; keep in step with resources/js/i18n.js. */
    private const COOKIE = 'i18next';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request);

        if ($locale === null) {
            return $next($request);
        }

        App::setLocale($locale);

        $user = $request->user();

        if ($user && $user->locale !== $locale) {
            $user->forceFill(['locale' => $locale])->saveQuietly();
        }

        return $next($request);
    }

    private function resolve(Request $request): ?string
    {
        // i18next writes region-qualified values such as "pt-BR"; only the
        // language part is meaningful here.
        $cookie = $request->cookie(self::COOKIE);

        if (is_string($cookie)) {
            $language = strtolower(explode('-', $cookie)[0]);

            if (in_array($language, self::SUPPORTED, true)) {
                return $language;
            }
        }

        return $request->user()?->locale;
    }
}
