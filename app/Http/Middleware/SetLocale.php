<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aligns the server's locale with the one the visitor is actually reading.
 *
 * A signed-in user's language is a preference stored on the account, chosen
 * from the language menu (`AccountController::updateLanguage`). It wins over
 * the browser, so the application speaks the same language on every device,
 * and a queued notification, which runs with no request behind it, is written
 * in it too. See `User::preferredLocale()`.
 *
 * Guests have only the cookie i18next keeps on the client. An account that
 * has never chosen adopts that cookie once, so it starts in the language its
 * owner was already reading; after that only an explicit choice changes it.
 * The cookie alone must not, because i18next writes it from the browser's own
 * language as readily as from a choice.
 */
class SetLocale
{
    public const SUPPORTED = ['es', 'en', 'pt'];

    /** i18next's default cookie name; keep in step with resources/js/i18n.js. */
    private const COOKIE = 'i18next';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $stored = $this->supported($user?->locale);
        $cookie = $this->fromCookie($request);

        if ($user && $stored === null && $cookie !== null) {
            $user->forceFill(['locale' => $cookie])->saveQuietly();
            $stored = $cookie;
        }

        $locale = $stored ?? $cookie;

        if ($locale !== null) {
            App::setLocale($locale);
        }

        return $next($request);
    }

    private function fromCookie(Request $request): ?string
    {
        // i18next writes region-qualified values such as "pt-BR"; only the
        // language part is meaningful here.
        $cookie = $request->cookie(self::COOKIE);

        return is_string($cookie)
            ? $this->supported(strtolower(explode('-', $cookie)[0]))
            : null;
    }

    private function supported(?string $language): ?string
    {
        return in_array($language, self::SUPPORTED, true) ? $language : null;
    }
}
