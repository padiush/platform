<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectAccess;
use App\Models\ProjectCapability;
use App\Models\ProjectInvite;
use App\Models\User;
use App\Notifications\InviteNotification;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(): Response
    {
        $accesses = Auth::user()
            ->projectAccesses()
            ->with(['project.user', 'capability'])
            ->get();

        $projects = collect();

        foreach ($accesses as $access) {
            $project = $access->project;
            if (! $project) {
                continue;
            }

            $project->can_manage = (bool) $access->capability->manage_project;
            $projects->push($project);
        }

        $invites = ProjectInvite::where('invited_user_id', Auth::id())
            ->where('expires_at', '>', Carbon::now())
            ->with(['invitingUser', 'capability', 'project'])
            ->get();

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'invites' => $invites,
        ]);
    }

    public function create(): RedirectResponse
    {
        // The form is a modal on the project list; deep-link opens it there.
        return redirect()->route('projects.index', ['create' => 1]);
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

    public function edit(Project $project): RedirectResponse
    {
        if (! Auth::user()->can('view', $project)) {
            return $this->denyNoAccess();
        }

        if (! Auth::user()->can('manageProject', $project)) {
            return $this->denyNoPermission();
        }

        return redirect()->route('projects.index', ['edit' => $project->id]);
    }

    public function update(Request $request, Project $project)
    {
        if (! Auth::user()->can('view', $project)) {
            return $this->denyNoAccess();
        }

        if (! Auth::user()->can('manageProject', $project)) {
            return $this->denyNoPermission();
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
        if (! Auth::user()->can('manageProject', $project)) {
            return $this->denyNoPermission();
        }

        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('message', 'project.delete_success')
            ->with('message_type', 'success');
    }

    public function manageAccess(Project $project): RedirectResponse|Response
    {
        if (! Auth::user()->can('view', $project)) {
            return $this->denyNoAccess();
        }

        if (! Auth::user()->can('manageUsers', $project)) {
            return $this->denyNoPermission();
        }

        $capabilities = ProjectCapability::all();

        // The Access page reads each access's nested capability and user, so
        // load them here — otherwise project.accesses[i].capability is
        // undefined on the client and the page crashes on render.
        $project->load('accesses.capability', 'accesses.user');

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
        if (! Auth::user()->can('view', $project)) {
            return $this->denyNoAccess();
        }

        if (! Auth::user()->can('manageUsers', $project)) {
            return $this->denyNoPermission();
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
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'capability_id' => 'required|numeric|exists:project_capabilities,id',
        ]);

        if (! Auth::user()->can('manageUsers', $project)) {
            return $this->denyNoPermission();
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

        if (! $invitedUser) {
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
        if (! Auth::user()->can('manageUsers', $project)) {
            return $this->denyNoPermission();
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
        if (! Auth::user()->can('manageUsers', $project)) {
            return $this->denyNoPermission();
        }

        // The invite is route-bound independently of the project, so a manager
        // of one project could otherwise revoke another project's invite by
        // mixing ids in the URL. Keep it scoped to the authorized project.
        if ($invite->project_id !== $project->id) {
            return redirect()
                ->route('projects.accesses', ['project' => $project])
                ->with('message', 'projects.invite_not_found')
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

        // An expired invite is already void; deleting it is the same outcome as
        // a decline, but tell the user why it wasn't a live invitation.
        if ($invite->expires_at->isPast()) {
            $invite->delete();

            return redirect()
                ->route('projects.index')
                ->with('message', 'projects.invite_expired')
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

        // The 7-day window is only meaningful if it's enforced at the grant
        // point too — the accept link lives in an email and would otherwise
        // work forever. Drop the stale invite instead of granting access.
        if ($invite->expires_at->isPast()) {
            $invite->delete();

            return redirect()
                ->route('projects.index')
                ->with('message', 'projects.invite_expired')
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

    private function denyNoAccess(): RedirectResponse
    {
        return redirect()
            ->route('projects.index')
            ->with('error', 'No tienes acceso a este proyecto.');
    }

    private function denyNoPermission(): RedirectResponse
    {
        return redirect()
            ->route('projects.index')
            ->with('message', 'projects.no_edit_permission')
            ->with('message_type', 'error');
    }
}
