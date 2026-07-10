<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'user_count' => User::count(),
            'project_count' => Project::count(),
        ]);
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
