<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
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
        $user->delete();

        return redirect()
            ->route('system.index')
            ->with('message', 'system.user_deleted')
            ->with('message_type', 'success');
    }

    public function destroyUsers(Request $request)
    {
        $ids = $request->input('ids', []);

        if (!empty($ids)) {
            User::whereIn('id', $ids)->delete();
        }

        return redirect()
            ->route('system.index')
            ->with('message', 'system.users_deleted')
            ->with('message_type', 'success');
    }
}
