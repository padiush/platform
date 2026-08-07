<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\RegistrationInvite;
use App\Models\User;
use App\Notifications\RegistrationInviteNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SystemController extends Controller
{
    public function index(): Response
    {
        $users = User::with(['projectAccesses.project', 'projectAccesses.capability'])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'accesses' => $user->projectAccesses->map(function ($access) {
                        return [
                            'project' => [
                                'id' => $access->project->id,
                                'name' => $access->project->name,
                            ],
                            'capability' => $access->capability->name,
                        ];
                    }),
                ];
            });

        return Inertia::render('System/Index', [
            'users' => $users,
            'registration_invites' => RegistrationInvite::query()
                ->where('expires_at', '>', now())
                ->orderBy('expires_at')
                ->get(['id', 'invited_name', 'invited_email', 'expires_at']),
            'user_count' => User::count(),
            'project_count' => Project::count(),
        ]);
    }

    public function inviteRegistration(Request $request)
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
        ]);

        $invite = RegistrationInvite::updateOrCreate(
            ['invited_email' => $validated['email']],
            [
                'inviting_user_id' => Auth::id(),
                'invited_name' => $validated['name'],
                'expires_at' => now()->addDays(RegistrationInvite::EXPIRATION_DAYS),
            ]
        );

        Notification::route('mail', $invite->invited_email)->notify(
            new RegistrationInviteNotification($invite)
        );

        return redirect()
            ->route('system.index')
            ->with('message', 'system.registration_invite_sent')
            ->with('message_type', 'success');
    }

    public function destroyUser(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()
                ->route('system.index')
                ->with('message', 'system.cannot_delete_self')
                ->with('message_type', 'error');
        }

        if ($user->system_admin && $this->wouldRemoveLastAdmin([$user->id])) {
            return redirect()
                ->route('system.index')
                ->with('message', 'system.cannot_delete_last_admin')
                ->with('message_type', 'error');
        }

        $user->delete();

        return redirect()
            ->route('system.index')
            ->with('message', 'system.user_deleted')
            ->with('message_type', 'success');
    }

    public function destroyUsers(Request $request)
    {
        // Never delete yourself, whatever the selection.
        $ids = collect($request->input('ids', []))
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === Auth::id())
            ->values();

        if ($ids->isEmpty()) {
            return redirect()
                ->route('system.index')
                ->with('message', 'system.users_deleted')
                ->with('message_type', 'success');
        }

        if ($this->wouldRemoveLastAdmin($ids->all())) {
            return redirect()
                ->route('system.index')
                ->with('message', 'system.cannot_delete_last_admin')
                ->with('message_type', 'error');
        }

        User::whereIn('id', $ids)->delete();

        return redirect()
            ->route('system.index')
            ->with('message', 'system.users_deleted')
            ->with('message_type', 'success');
    }

    /**
     * Whether deleting the given user ids would leave no system admins.
     */
    private function wouldRemoveLastAdmin(array $ids): bool
    {
        $totalAdmins = User::where('system_admin', true)->count();
        $adminsBeingDeleted = User::whereIn('id', $ids)
            ->where('system_admin', true)
            ->count();

        return $totalAdmins - $adminsBeingDeleted < 1;
    }
}
