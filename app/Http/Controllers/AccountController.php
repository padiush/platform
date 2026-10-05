<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BrowserSessions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Mi cuenta: where the user is signed in, on the web and on the field app,
 * and signing any of it out — a browser left open somewhere, or a phone that
 * was lost.
 */
class AccountController extends Controller
{
    public function __construct(private readonly BrowserSessions $sessions) {}

    public function show(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Account/Show', [
            'account' => ['name' => $user->name, 'email' => $user->email],
            // Null when this installation keeps sessions where they cannot be
            // listed; the page says so instead of showing an empty list.
            'sessions' => $this->sessions->available()
                ? $this->sessions->for($user, $request->session()->getId())
                : null,
            // Each token is one device the companion signed in on, named by
            // the device when it asked for one.
            'devices' => $user->tokens()
                ->latest()
                ->get()
                ->map(fn (PersonalAccessToken $token) => [
                    'id' => $token->id,
                    'name' => $token->name,
                    'created_at' => $token->created_at?->toIso8601String(),
                    'last_used_at' => $token->last_used_at?->toIso8601String(),
                ])
                ->all(),
        ]);
    }

    /** Sign out one other browser. This one signs out from the sidebar. */
    public function destroySession(Request $request, string $session): RedirectResponse
    {
        $user = $request->user();

        if (! $this->sessions->available()
            || ! $this->sessions->destroy($user, $session, $request->session()->getId())) {
            return back()
                ->with('message', 'account.sessions.not_found')
                ->with('message_type', 'error');
        }

        $this->forgetRememberedBrowsers($request, $user);

        return back()
            ->with('message', 'account.sessions.signed_out')
            ->with('message_type', 'success');
    }

    /**
     * Sign out every other browser. Asks for the password, as signing out
     * everywhere is what someone does when they fear the account is not only
     * theirs — and the password is what that someone would not have.
     */
    public function destroyOtherSessions(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($this->sessions->available()) {
            $this->sessions->destroyOthers($user, $request->session()->getId());
        }

        $this->forgetRememberedBrowsers($request, $user);

        return back()
            ->with('message', 'account.sessions.others_signed_out')
            ->with('message_type', 'success');
    }

    /** Revoke a device's token: the field app there has to sign in again. */
    public function destroyDevice(Request $request, int $device): RedirectResponse
    {
        $deleted = $request->user()->tokens()->whereKey($device)->delete();

        return back()
            ->with('message', $deleted ? 'account.devices.revoked' : 'account.devices.not_found')
            ->with('message_type', $deleted ? 'success' : 'error');
    }

    /**
     * A browser signed in with "remember me" would sign itself back in from
     * its cookie once its session is gone. Changing the remember token voids
     * every such cookie; this browser, if it was remembered, gets a new one.
     */
    private function forgetRememberedBrowsers(Request $request, User $user): void
    {
        $token = Str::random(60);

        // Not an edit of the account, so updated_at stays as it was.
        User::whereKey($user->id)->toBase()->update(['remember_token' => $token]);
        $user->setRememberToken($token);

        $guard = Auth::guard('web');

        if ($request->cookies->has($guard->getRecallerName())) {
            $guard->login($user, true);
        }
    }
}
