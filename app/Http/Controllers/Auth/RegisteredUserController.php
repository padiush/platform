<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

use App\Models\ProjectInvite;
use App\Models\ProjectAccess;

use Carbon\Carbon;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
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
        $invites = ProjectInvite::where('email', $user->email)->where('expires_at', '>', Carbon::now())->get();

        if($invites){
            foreach($invites as $invite){
                $project = $invite->project;

                $project->accesses()->create([
                    'user_id' => $user->id,
                    'project_capability_id' => $invite->project_capability_id,
                ]);

                $invite->delete();
            }
        }

        $expired_invites = ProjectInvite::where('email', $user->email)->where('expires_at', '<', Carbon::now())->get();

        if($expired_invites){
            foreach($expired_invites as $expired_invite){
                $expired_invite->delete();
            }
        }

        return redirect(RouteServiceProvider::HOME);
    }
}
