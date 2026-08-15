<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Spatie\Honeypot\Honeypot;
use Tighten\Ziggy\Ziggy;

/**
 * @codeCoverageIgnore
 */
class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        // Accesses (not owned projects): membership is what unlocks the app's
        // sections, and the nav gates each item by the matching capability.
        $accesses = $user
            ? $user->projectAccesses()->with('capability')->get()
            : collect();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'projects' => $accesses->count(),
                'capabilities' => [
                    'manage_forms' => $accesses->contains(
                        fn ($access) => (bool) $access->capability?->manage_forms
                    ),
                    'record_data' => $accesses->contains(
                        fn ($access) => (bool) $access->capability?->record_data
                    ),
                    'data' => $accesses->contains(
                        fn ($access) => $access->capability?->manage_data ||
                            $access->capability?->generate_reports
                    ),
                    'view_catalog' => $accesses->contains(
                        fn ($access) => (bool) $access->capability?->view_catalog
                    ),
                ],
            ],
            'honeypot' => new Honeypot(config('honeypot')),
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'flash' => [
                'message' => $request->session()->get('message'),
                'message_type' => $request->session()->get('message_type'),
            ],
            'environment' => config('app.env'),
            // The public pages point their call to action at registration when
            // it is open and at the contact form when it is not, so the button
            // never promises a sign-up that would just bounce to login.
            'registrationEnabled' => (bool) config('padiush.registration_enabled'),
            // The public pages are optional, so anything outside them that
            // links back to the site — the sign-in screens, chiefly — has to
            // know whether there is a site to link to.
            'publicSiteEnabled' => (bool) config('padiush.public_site_enabled'),
            // AGPL section 13. Shared everywhere because the offer of source
            // has to hold on every page a user of the service can reach.
            'sourceUrl' => config('padiush.source_url'),
        ];
    }
}
