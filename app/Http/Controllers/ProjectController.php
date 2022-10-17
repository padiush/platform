<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Project;
use App\Models\ProjectAccess;
use App\Models\ProjectCapability;
use App\Models\User;

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

        return view('projects.access', ['project' => $project, 'users' => $users, 'capabilities' => $capabilities]);
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
}
