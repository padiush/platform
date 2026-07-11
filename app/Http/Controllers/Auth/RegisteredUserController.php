<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ProjectInvite;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Inertia\Inertia;
use Spatie\Honeypot\Honeypot;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return View
     */
    public function create(Honeypot $honeypot)
    {
        if (! config('padiush.registration_enabled')) {
            return redirect()->route('login');
        }

        $url = \Storage::disk('s3')->temporaryUrl('public/bg.jpg', now()->addMinutes(5));

        return Inertia::render('Auth/Register', [
            'bgImage' => $url,
            'honeypot' => $honeypot,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @return RedirectResponse
     *
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        if (! config('padiush.registration_enabled')) {
            return redirect()->route('login');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Check if the user was invited to a project
        $invites = ProjectInvite::where('invited_email', $user->email)->where('expires_at', '>', Carbon::now())->get();

        if ($invites) {
            foreach ($invites as $invite) {
                $project = $invite->project;

                $project->accesses()->create([
                    'user_id' => $user->id,
                    'project_capability_id' => $invite->project_capability_id,
                ]);

                $invite->delete();
            }
        }

        $expired_invites = ProjectInvite::where('invited_email', $user->email)->where('expires_at', '<', Carbon::now())->get();

        if ($expired_invites) {
            foreach ($expired_invites as $expired_invite) {
                $expired_invite->delete();
            }
        }

        return redirect(RouteServiceProvider::HOME);
    }
}
