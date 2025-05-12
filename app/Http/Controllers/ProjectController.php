<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;
use App\Models\Project;
use App\Models\ProjectAccess;
use App\Models\ProjectCapability;
use App\Models\ProjectInvite;
use App\Models\User;
use App\Notifications\InviteNotification;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index(): Response
    {
        $accesses = ProjectAccess::where('user_id', Auth::id())->get();

        $projects = collect();

        foreach ($accesses as $access) {
            $project = Project::find($access->project_id);
            if (!$project) {
                continue;
            }

            $project->load('user');
            $projects->push($project);

            $project->can_manage = Auth::user()->hasCapabilityOnProject(
                $project,
                'manage_project'
            );
        }

        $invites = ProjectInvite::where('invited_user_id', Auth::id())
            ->where('expires_at', '>', Carbon::now())
            ->get();

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'invites' => $invites,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Projects/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'author' => 'nullable|string|max:255',
            'institution' => 'nullable|string|max:255',
            'author_email' => 'nullable|email|max:255',
            'country' => 'nullable|string|max:255',
        ]);

        $project = Auth::user()
            ->projects()
            ->create([
                'name' => $request->name,
                'author' => $request->author,
                'institution' => $request->institution,
                'author_email' => $request->author_email,
                'country' => $request->country,
                'finished' => false,
                'published' => false,
                'shared' => false,
            ]);

        $project->accesses()->create([
            'user_id' => Auth::user()->id,
            'project_capability_id' => ProjectCapability::where(
                'manage_project',
                true
            )->first()->id,
        ]);

        return redirect()
            ->route('projects.index')
            ->with('message', 'project.create_success')
            ->with('message_type', 'success');
    }

    public function edit(Project $project): Response|RedirectResponse
    {
        $access = Auth::user()->hasAccessToProject($project);

        if (!$access) {
            return redirect()
                ->route('projects.index')
                ->with('error', 'No tienes acceso a este proyecto.');
        }

        if (!$access->capability->manage_project) {
            return redirect()
                ->route('projects.index')
                ->with('message', 'projects.no_edit_permission')
                ->with('message_type', 'error');
        }

        return Inertia::render('Projects/Form', [
            'project' => $project,
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $access = Auth::user()->hasAccessToProject($project);

        if (!$access) {
            return redirect()
                ->route('projects.index')
                ->with('error', 'No tienes acceso a este proyecto.');
        }

        if (!$access->capability->manage_project) {
            return redirect()
                ->route('projects.index')
                ->with('message', 'projects.no_edit_permission')
                ->with('message_type', 'error');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'author' => 'nullable|string|max:255',
            'institution' => 'nullable|string|max:255',
            'author_email' => 'nullable|email|max:255',
            'country' => 'nullable|string|max:255',
        ]);

        $project->update([
            'name' => $request->name,
            'author' => $request->author,
            'institution' => $request->institution,
            'author_email' => $request->author_email,
            'country' => $request->country,
        ]);

        return redirect()
            ->route('projects.index')
            ->with('message', 'project.update_success')
            ->with('message_type', 'success');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $access = Auth::user()->hasAccessToProject($project);

        if (!$access) {
            return redirect()
                ->route('projects.index')
                ->with('message', 'projects.no_edit_permission')
                ->with('message_type', 'error');
        }

        if (!$access->capability->manage_project) {
            return redirect()
                ->route('projects.index')
                ->with('message', 'projects.no_edit_permission')
                ->with('message_type', 'error');
        }

        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('message', 'project.delete_success')
            ->with('message_type', 'success');
    }

    public function manageAccess(Project $project): RedirectResponse|Response
    {
        $access = Auth::user()->hasAccessToProject($project);
        $capabilities = ProjectCapability::all();

        if (!$access) {
            return redirect()
                ->route('projects.index')
                ->with('error', 'No tienes acceso a este proyecto.');
        }

        if (!$access->capability->manage_users) {
            return redirect()
                ->route('projects.index')
                ->with('message', 'projects.no_edit_permission')
                ->with('message_type', 'error');
        }

        $users = $project->users();
        $invites = $project->invites;

        return Inertia::render('Projects/Access', [
            'project' => $project,
            'users' => $users,
            'capabilities' => $capabilities,
            'invites' => $invites,
        ]);
    }

    public function revokeAccess(Project $project, User $user)
    {
        $access = Auth::user()->hasAccessToProject($project);

        if (!$access) {
            return redirect()
                ->route('projects.index')
                ->with('error', 'No tienes acceso a este proyecto.');
        }

        if (!$access->capability->manage_users) {
            return redirect()
                ->route('projects.index')
                ->with('message', 'projects.no_edit_permission')
                ->with('message_type', 'error');
        }

        if ($user->id == Auth::user()->id) {
            return redirect()
                ->route('projects.accesses', ['project' => $project])
                ->with('message', 'projects.no_revoke_own_access')
                ->with('message_type', 'error');
        }

        $project->accesses()->where('user_id', $user->id)->delete();

        return redirect()
            ->route('projects.accesses', ['project' => $project])
            ->with('message', 'projects.revoke_access_success')
            ->with('message_type', 'success');
    }

    public function inviteUser(Request $request, Project $project)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'capability_id' =>
                'required|numeric|exists:project_capabilities,id',
        ]);

        $access = Auth::user()->hasAccessToProject($project);

        if (!$access) {
            return redirect()
                ->route('projects.index')
                ->with('message', 'projects.no_edit_permission')
                ->with('message_type', 'error');
        }

        if (!$access->capability->manage_users) {
            return redirect()
                ->route('projects.index')
                ->with('message', 'projects.no_edit_permission')
                ->with('message_type', 'error');
        }

        $existingInvite = ProjectInvite::where('invited_email', $request->email)
            ->where('project_id', $project->id)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($existingInvite) {
            return redirect()
                ->route('projects.accesses', ['project' => $project])
                ->with('message', 'projects.invite_already_sent')
                ->with('message_type', 'error');
        }

        $invitingUser = Auth::user();
        $invitedUser = User::where('email', $request->email)->first();

        foreach ($project->accesses as $access) {
            if ($access->user->email == $request->email) {
                return redirect()
                    ->route('projects.accesses', ['project' => $project])
                    ->with('message', 'projects.user_already_in_project')
                    ->with('message_type', 'error');
            }
        }

        $capability = ProjectCapability::find($request->capability_id);
        $expiringDate = Carbon::now()->addDays(7);

        if (!$invitedUser) {
            $invite = ProjectInvite::create([
                'project_id' => $project->id,
                'inviting_user_id' => $invitingUser->id,
                'invited_name' => $request->name,
                'invited_email' => $request->email,
                'project_capability_id' => $capability->id,
                'expires_at' => $expiringDate,
            ]);

            Notification::route('mail', $request->email)->notify(
                new InviteNotification($invite)
            );

            return redirect()
                ->route('projects.accesses', ['project' => $project])
                ->with('message', 'projects.invite_sent')
                ->with('message_type', 'success');
        }

        $invite = ProjectInvite::create([
            'project_id' => $project->id,
            'inviting_user_id' => $invitingUser->id,
            'invited_user_id' => $invitedUser->id,
            'invited_email' => $request->email,
            'project_capability_id' => $capability->id,
            'expires_at' => $expiringDate,
        ]);

        Notification::route('mail', $request->email)->notify(
            new InviteNotification($invite)
        );

        return redirect()
            ->route('projects.accesses', ['project' => $project])
            ->with('message', 'projects.invite_sent')
            ->with('message_type', 'success');
    }

    public function projectInvites(Project $project, Request $request)
    {
        $access = Auth::user()->hasAccessToProject($project);

        if (!$access || !$access->capability->manage_users) {
            return redirect()
                ->route('projects.index')
                ->with('message', 'projects.no_edit_permission')
                ->with('message_type', 'error');
        }

        $query = $project->invites()->with(['capability', 'invitedUser']);

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invited_name', 'like', "%{$search}%")->orWhere(
                    'invited_email',
                    'like',
                    "%{$search}%"
                );
            });
        }

        $invites = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Projects/PendingInvites', [
            'project' => $project,
            'invites' => $invites,
            'filters' => $request->only('search'),
        ]);
    }

    public function revokeInvite(Project $project, ProjectInvite $invite)
    {
        $access = Auth::user()->hasAccessToProject($project);

        if (!$access) {
            return redirect()
                ->route('projects.index')
                ->with('message', 'projects.no_edit_permission')
                ->with('message_type', 'error');
        }

        if (!$access->capability->manage_users) {
            return redirect()
                ->route('projects.index')
                ->with('message', 'projects.no_edit_permission')
                ->with('message_type', 'error');
        }

        $invite->delete();

        $remaining_invites = $project->invites->count();

        if ($remaining_invites == 0) {
            return redirect()
                ->route('projects.accesses', ['project' => $project])
                ->with('message', 'projects.no_invites')
                ->with('message_type', 'error');
        }

        return redirect()
            ->route('projects.accesses.invites', ['project' => $project])
            ->with('message', 'projects.invite_revoked')
            ->with('message_type', 'success');
    }

    public function declineInvite(ProjectInvite $invite)
    {
        $user = Auth::user();

        if ($user->email != $invite->invited_email) {
            return redirect()
                ->route('projects.index')
                ->with('message', 'projects.cannot_decline_another_user_invite')
                ->with('message_type', 'error');
        }

        $invite->delete();

        return redirect()
            ->route('projects.index')
            ->with('message', 'projects.invite_declined')
            ->with('message_type', 'success');
    }

    public function acceptInvite(ProjectInvite $invite)
    {
        $user = Auth::user();

        if ($user->email != $invite->invited_email) {
            return redirect()
                ->route('projects.index')
                ->with('message', 'projects.cannot_accept_another_user_invite')
                ->with('message_type', 'error');
        }

        $project = $invite->project;

        $existingAccess = ProjectAccess::where('user_id', $user->id)
            ->where('project_id', $project->id)
            ->first();

        if ($existingAccess) {
            $invite->delete();
            return redirect()
                ->route('projects.index')
                ->with('message', 'projects.already_in_project')
                ->with('message_type', 'error');
        }

        $project->accesses()->create([
            'user_id' => $user->id,
            'project_capability_id' => $invite->project_capability_id,
        ]);

        $invite->delete();

        return redirect()
            ->route('projects.index')
            ->with('message', 'projects.invite_accepted')
            ->with('message_type', 'success');
    }
}
