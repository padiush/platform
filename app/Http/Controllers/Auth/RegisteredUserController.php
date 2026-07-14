<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ProjectInvite;
use App\Models\RegistrationInvite;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Honeypot\Honeypot;

class RegisteredUserController extends Controller
{
    public function create(Honeypot $honeypot): Response|RedirectResponse
    {
        if (! config('padiush.registration_enabled')) {
            return redirect()->route('login');
        }

        return $this->renderRegistration($honeypot, route('register'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (! config('padiush.registration_enabled')) {
            return redirect()->route('login');
        }

        return $this->register($request);
    }

    public function createFromProjectInvite(
        ProjectInvite $invite,
        Honeypot $honeypot
    ): Response {
        $this->ensureProjectInviteCanRegister($invite);

        return $this->renderRegistration(
            $honeypot,
            URL::temporarySignedRoute(
                'register.project-invite.store',
                $invite->expires_at,
                ['invite' => $invite]
            ),
            $invite->invited_name,
            $invite->invited_email
        );
    }

    public function storeFromProjectInvite(
        Request $request,
        ProjectInvite $invite
    ): RedirectResponse {
        $this->ensureProjectInviteCanRegister($invite);

        return $this->register($request, $invite->invited_email);
    }

    public function createFromPlatformInvite(
        RegistrationInvite $invite,
        Honeypot $honeypot
    ): Response {
        $this->ensurePlatformInviteCanRegister($invite);

        return $this->renderRegistration(
            $honeypot,
            URL::temporarySignedRoute(
                'register.platform-invite.store',
                $invite->expires_at,
                ['invite' => $invite]
            ),
            $invite->invited_name,
            $invite->invited_email
        );
    }

    public function storeFromPlatformInvite(
        Request $request,
        RegistrationInvite $invite
    ): RedirectResponse {
        $this->ensurePlatformInviteCanRegister($invite);

        return $this->register($request, $invite->invited_email, $invite);
    }

    private function renderRegistration(
        Honeypot $honeypot,
        string $registrationUrl,
        ?string $name = null,
        ?string $email = null
    ): Response {
        return Inertia::render('Auth/Register', [
            'bgImage' => Storage::disk('s3')->temporaryUrl(
                'public/bg.jpg',
                now()->addMinutes(5)
            ),
            'honeypot' => $honeypot,
            'invitation' => $email ? [
                'name' => $name,
                'email' => $email,
            ] : null,
            'registrationUrl' => $registrationUrl,
        ]);
    }

    private function register(
        Request $request,
        ?string $invitedEmail = null,
        ?RegistrationInvite $platformInvite = null
    ): RedirectResponse {
        $email = Str::lower(trim($invitedEmail ?? (string) $request->input('email')));
        $request->merge(['email' => $email]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($validated, $platformInvite) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $this->acceptProjectInvites($user);
            $platformInvite?->delete();

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }

    private function acceptProjectInvites(User $user): void
    {
        $invites = ProjectInvite::query()
            ->where('invited_email', $user->email)
            ->where('expires_at', '>', now())
            ->get();

        foreach ($invites as $invite) {
            $invite->project->accesses()->firstOrCreate(
                ['user_id' => $user->id],
                ['project_capability_id' => $invite->project_capability_id]
            );

            $invite->delete();
        }

        ProjectInvite::query()
            ->where('invited_email', $user->email)
            ->where('expires_at', '<=', now())
            ->delete();
    }

    private function ensureProjectInviteCanRegister(ProjectInvite $invite): void
    {
        abort_if(
            $invite->invited_user_id !== null ||
                ! $invite->expires_at ||
                $invite->expires_at->isPast(),
            410,
            __('registration.invalid_invitation')
        );

        $this->ensureEmailIsAvailable($invite->invited_email);
    }

    private function ensurePlatformInviteCanRegister(RegistrationInvite $invite): void
    {
        abort_if(
            $invite->expires_at->isPast(),
            410,
            __('registration.invalid_invitation')
        );

        $this->ensureEmailIsAvailable($invite->invited_email);
    }

    private function ensureEmailIsAvailable(string $email): void
    {
        abort_if(
            User::where('email', $email)->exists(),
            410,
            __('registration.already_registered')
        );
    }
}
