<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\InterviewForm;
use App\Models\ProjectAccess;
use App\Models\Project;
use App\Models\User;

class InterviewFormController extends Controller
{
    public function index(){
        $accesses = ProjectAccess::where('user_id', Auth::id())->get();

        $projects = collect();

        foreach($accesses as $access){
            $project = Project::find($access->project_id);

            if(!$project->finished && Auth::user()->hasCapabilityOnProject($project, 'manage_forms')){
                $projects->push($project);
            }
        }

        if($projects->count() == 0){
            return redirect()->route('projects.index')->with('error', 'No tienes proyectos activos para diseñar entrevistas.');
        }

        return view('interview-forms.index', compact('projects'));
    }

    public function create(Project $project){
         $access = ProjectAccess::where('user_id', Auth::id())->where('project_id', $project->id)->first();

        if(!$access || !Auth::user()->hasCapabilityOnProject($project, 'manage_forms')){
            return redirect()->route('projects.index')->with('error', 'No tienes permisos para crear entrevistas en este proyecto.');
        }

        return view('interview-forms.create', [
            'project' => $project,
        ]);
    }

    public function store(Request $request, Project $project){
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $access = ProjectAccess::where('user_id', Auth::id())->where('project_id', $project->id)->first();

        if(!$access || !Auth::user()->hasCapabilityOnProject($project, 'manage_forms')){
            return redirect()->route('projects.index')->with('error', 'No tienes permisos para crear entrevistas en este proyecto.');
        }

        $form = InterviewForm::create([
            'project_id' => $request->project_id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('designer.form.edit', ['project' => $project, 'form' => $form])->with('success', 'Formulario de entrevista creado exitosamente.');
    }

    public function destroy(Project $project, InterviewForm $form){
        $access = ProjectAccess::where('user_id', Auth::id())->where('project_id', $project->id)->first();

        if(!$access || !Auth::user()->hasCapabilityOnProject($project, 'manage_forms')){
            return redirect()->route('projects.index')->with('error', 'No tienes permisos para eliminar formularios en este proyecto.');
        }

        $form->delete();

        return redirect()->route('designer.index')->with('success', 'Formulario eliminado exitosamente.');
    }

    public function toggle(Project $project, InterviewForm $form){
        $access = ProjectAccess::where('user_id', Auth::id())->where('project_id', $project->id)->first();

        if(!$access || !Auth::user()->hasCapabilityOnProject($project, 'manage_forms')){
            return redirect()->route('projects.index')->with('error', 'No tienes permisos para editar formularios en este proyecto.');
        }

        $form->is_active = !$form->is_active;
        $form->save();

        return redirect()->route('designer.index')->with('success', 'Formulario actualizado exitosamente.');
    }

    public function edit(Project $project, InterviewForm $form){
        $access = ProjectAccess::where('user_id', Auth::id())->where('project_id', $project->id)->first();

        if(!$access || !Auth::user()->hasCapabilityOnProject($project, 'manage_forms')){
            return redirect()->route('projects.index')->with('error', 'No tienes permisos para editar formularios en este proyecto.');
        }

        return view('interview-forms.edit', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    public function preview(Project $project, InterviewForm $form){
        $access = ProjectAccess::where('user_id', Auth::id())->where('project_id', $project->id)->first();

        if(!$access || !Auth::user()->hasCapabilityOnProject($project, 'manage_forms')){
            return redirect()->route('projects.index')->with('error', 'No tienes permisos para editar formularios en este proyecto.');
        }

        return view('interview-forms.preview', [
            'project' => $project,
            'form' => $form,
        ]);
    }
}
