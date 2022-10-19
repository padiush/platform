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

class ProjectController extends Controller
{
    public function index(){
        $accesses = ProjectAccess::where('user_id', Auth::id())->get();

        $projects = collect();

        foreach($accesses as $access){
            $projects->push(Project::find($access->project_id));
        }

        return view('projects.index', ['projects' => $projects]);
    }

    public function create(){
        return view('projects.form');
    }

    public function store(Request $request){
        $request->validate([
            'name' => 'required|string|max:255',
            'author' => 'nullable|string|max:255',
            'institution' => 'nullable|string|max:255',
            'author_email' => 'nullable|email|max:255',
            'country' => 'nullable|string|max:255',
        ]);

        $project = Auth::user()->projects()->create([
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
            'project_capability_id' => ProjectCapability::where('manage_project', true)->first()->id,
        ]);

        return redirect()->route('projects.index')->with('success', 'Se ha creado el proyecto exitosamente.');
    }

    public function edit(Project $project){
        $access = Auth::user()->hasAccessToProject($project);

        if(!$access){
            return redirect()->route('projects.index')->with('error', 'No tienes acceso a este proyecto.');
        }

        if(!$access->capability->manage_project){
            return redirect()->route('projects.index')->with('error', 'No cuentas con los permisos necesarios para editar este proyecto.');
        }

        return view('projects.form', ['project' => $project]);
    }

    public function update(Request $request, Project $project){
        $access = Auth::user()->hasAccessToProject($project);

        if(!$access){
            return redirect()->route('projects.index')->with('error', 'No tienes acceso a este proyecto.');
        }

        if(!$access->capability->manage_project){
            return redirect()->route('projects.index')->with('error', 'No cuentas con los permisos necesarios para editar este proyecto.');
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

        return redirect()->route('projects.index')->with('success', 'Se ha actualizado el proyecto exitosamente.');
    }

    public function manageAccess(Project $project){
        $access = Auth::user()->hasAccessToProject($project);
        $capabilities = ProjectCapability::all();

        if(!$access){
            return redirect()->route('projects.index')->with('error', 'No tienes acceso a este proyecto.');
        }

        if(!$access->capability->manage_users){
            return redirect()->route('projects.index')->with('error', 'No cuentas con los permisos necesarios para editar este proyecto.');
        }

        $users = $project->users();
        $invites = $project->invites;

        return view('projects.access', ['project' => $project, 'users' => $users, 'capabilities' => $capabilities, 'invites' => $invites]);
    }

    public function revokeAccess(Project $project, User $user)
    {
        $access = Auth::user()->hasAccessToProject($project);

        if(!$access){
            return redirect()->route('projects.index')->with('error', 'No tienes acceso a este proyecto.');
        }

        if(!$access->capability->manage_users){
            return redirect()->route('projects.index')->with('error', 'No cuentas con los permisos necesarios para editar este proyecto.');
        }

        if($user->id == Auth::user()->id){
            return redirect()->route('projects.accesses', ['project' => $project])->with('error', 'No puedes revocar tu propio acceso a este proyecto.');
        }

        $project->accesses()->where('user_id', $user->id)->delete();

        return redirect()->route('projects.accesses', ['project' => $project])->with('success', 'Se ha revocado el acceso al proyecto exitosamente.');
    }

    public function inviteUser(Request $request, Project $project){
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'capability_id' => 'required|numeric|exists:project_capabilities,id'
        ]);

        $access = Auth::user()->hasAccessToProject($project);

        if(!$access){
            return redirect()->route('projects.index')->with('error', 'No tienes acceso a este proyecto.');
        }

        if(!$access->capability->manage_users){
            return redirect()->route('projects.index')->with('error', 'No cuentas con los permisos necesarios para editar este proyecto.');
        }

        $existingInvite = ProjectInvite::where('invited_email', $request->email)->where('project_id', $project->id)->where('expires_at', '>', Carbon::now())->first();

        if($existingInvite){
            return redirect()->route('projects.accesses', ['project' => $project])->with('error', 'Ya existe una invitación pendiente para este usuario.');
        }

        $invitingUser = Auth::user();
        $invitedUser = User::where('email', $request->email)->first();
        $capability = ProjectCapability::find($request->capability_id);
        $expiringDate = Carbon::now()->addDays(7);

        if(!$invitedUser){
            $invite = ProjectInvite::create([
                'project_id' => $project->id,
                'inviting_user_id' => $invitingUser->id,
                'invited_name' => $request->name,
                'invited_email' => $request->email,
                'project_capability_id' => $capability->id,
                'expires_at' => $expiringDate,
            ]);

            Notification::route('mail', $request->email)->notify(new InviteNotification($invite));

            return redirect()->route('projects.accesses', ['project' => $project])->with('success', 'Se ha enviado la invitación al proyecto exitosamente.');
        }

        $invite = ProjectInvite::create([
            'project_id' => $project->id,
            'inviting_user_id' => $invitingUser->id,
            'invited_user_id' => $invitedUser->id,
            'invited_email' => $request->email,
            'project_capability_id' => $capability->id,
            'expires_at' => $expiringDate,
        ]);

        Notification::route('mail', $request->email)->notify(new InviteNotification($invite));

        return redirect()->route('projects.accesses', ['project' => $project])->with('success', 'Se ha enviado la invitación al proyecto exitosamente.');
    }

    public function projectInvites(Project $project){
        $access = Auth::user()->hasAccessToProject($project);

        if(!$access){
            return redirect()->route('projects.index')->with('error', 'No tienes acceso a este proyecto.');
        }

        if(!$access->capability->manage_users){
            return redirect()->route('projects.index')->with('error', 'No cuentas con los permisos necesarios para editar este proyecto.');
        }

        $invites = $project->invites;

        if($invites->count() == 0){
            return redirect()->route('projects.accesses', ['project' => $project])->with('error', 'No hay invitaciones pendientes para este proyecto.');
        }

        return view('projects.project-invites', ['project' => $project, 'invites' => $invites]);
    }

    public function revokeInvite(Project $project, ProjectInvite $invite){
        $access = Auth::user()->hasAccessToProject($project);

        if(!$access){
            return redirect()->route('projects.index')->with('error', 'No tienes acceso a este proyecto.');
        }

        if(!$access->capability->manage_users){
            return redirect()->route('projects.index')->with('error', 'No cuentas con los permisos necesarios para editar este proyecto.');
        }

        $invite->delete();

        return redirect()->route('projects.accesses.invites', ['project' => $project])->with('success', 'Se ha revocado la invitación al proyecto exitosamente.');
    }
}