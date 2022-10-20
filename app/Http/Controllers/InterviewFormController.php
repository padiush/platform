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
    public function index()
    {
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

    public function create(Project $project)
    {
        return view('interview-forms.create', [
            'project' => $project,
        ]);
    }
}
