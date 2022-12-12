<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\ProjectAccess;
use App\Models\Project;
use App\Models\User;
use App\Models\InstanceAnswer;
use App\Models\InterviewSection;
use App\Models\InterviewItem;

class InterviewInstancesController extends Controller
{
    public function index(){
        $accesses = ProjectAccess::where('user_id', Auth::id())->get();

        $projects = collect();

        foreach($accesses as $access){
            $project = Project::find($access->project_id);

            if(!$project->finished && Auth::user()->hasCapabilityOnProject($project, 'record_data')){
                $projects->push($project);
            }
        }

        if($projects->count() == 0){
            return redirect()->route('projects.index')->with('error', 'No tienes proyectos activos para tomar entrevistas.');
        }

        return view('interviews.index', compact('projects'));
    }

    public function create(InterviewForm $form){
        $project = Project::find($form->project_id);

        if(!$project->finished && Auth::user()->hasCapabilityOnProject($project, 'record_data')){
            $instance = InterviewInstance::create([
                'interview_form_id' => $form->id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('interviews.show', $instance);
        }

        return redirect()->route('projects.index')->with('error', 'No tienes permisos para tomar entrevistas en este proyecto.');
    }

    public function list(InterviewForm $form){
        $project = Project::find($form->project_id);

        if($project->finished || !Auth::user()->hasCapabilityOnProject($project, 'record_data')){
            return redirect()->route('projects.index')->with('error', 'No tienes permisos para ver entrevistas en este proyecto.');
        }

        $instances = InterviewInstance::where('interview_form_id', $form->id)->paginate(20);

        return view('interviews.list', compact('instances', 'form', 'project'));
    }

    public function show(InterviewInstance $instance){
        $form = $instance->form;
        $project = $form->project;

        if(!$project->finished && Auth::user()->hasCapabilityOnProject($project, 'record_data')){
            return view('interviews.show', compact('instance', 'project', 'form'));
        }

        $repeating_sections = InterviewSection::where('interview_form_id', $form->id)->where('repeating', true)->get();

        return redirect()->route('projects.index')->with('error', 'No tienes permisos para tomar entrevistas en este proyecto.');
    }

    public function storeAnswer(Request $request, InterviewInstance $instance){
        $form = $instance->form;
        $project = $form->project;

        if($project->finished || !Auth::user()->hasCapabilityOnProject($project, 'record_data')){
            return redirect()->route('projects.index')->with('error', 'No tienes permisos para tomar entrevistas en este proyecto.');
        }

        $request->validate([
            'item_id' => 'required|integer',
            'repeatable_index' => 'nullable|integer',
            'answer' => 'required',
        ]);

        $section = InterviewItem::find($request->item_id)->section;

        // Check for previous answer
        $answer = InstanceAnswer::where('interview_instance_id', $instance->id)
            ->where('interview_item_id', $request->item_id)
            ->where('repeatable_index', $request->repeatable_index)
            ->first();

        if($answer){
            $answer->answer = $request->answer;
            $answer->save();
        } else {
            $answer = InstanceAnswer::create([
                'interview_instance_id' => $instance->id,
                'interview_section_id' => $section->id,
                'interview_item_id' => $request->item_id,
                'repeatable_index' => $request->repeatable_index,
                'answer' => $request->answer,
            ]);
        }

        return response()->json([
            'success' => true,
            'answer' => $answer,
        ]);
    }

    public function getAnswer(Request $request, InterviewInstance $instance){
        $form = $instance->form;
        $project = $form->project;

        if($project->finished || !Auth::user()->hasCapabilityOnProject($project, 'record_data')){
            return redirect()->route('projects.index')->with('error', 'No tienes permisos para tomar entrevistas en este proyecto.');
        }

        $request->validate([
            'item_id' => 'required|integer',
            'repeatable_index' => 'nullable|integer',
        ]);

        $answer = InstanceAnswer::where('interview_instance_id', $instance->id)
            ->where('interview_item_id', $request->item_id)
            ->where('repeatable_index', $request->repeatable_index)
            ->first();

        if($answer){
            return response()->json([
                'success' => true,
                'answer' => $answer,
            ]);
        }

        return response()->json([
            'success' => false,
        ]);
    }

    public function populateRepeatableSection(Request $request, InterviewInstance $instance){
        $form = $instance->form;
        $project = $form->project;

        if($project->finished || !Auth::user()->hasCapabilityOnProject($project, 'record_data')){
            return response()->json([
                'success' => false,
            ]);
        }

        $request->validate([
            'section_id' => 'required|integer',
        ]);

        $section = InterviewSection::find($request->section_id);

        if(!$section || $section->interview_form_id != $form->id){
            return response()->json([
                'success' => false,
            ]);
        }

        $highest_index = InstanceAnswer::where('interview_instance_id', $instance->id)
            ->where('repeatable_index', '!=', null)
            ->whereHas('item', function($query) use ($request){
                $query->where('interview_section_id', $request->section_id);
            })
            ->max('repeatable_index');

        $highest_index = $highest_index ? $highest_index : 0;

        if(!$highest_index){
            return response()->json([
                'success' => false,
            ]);
        }

        // Render the section $highest_index times and return it
        $html = '';
        for($i = 1; $i <= $highest_index; $i++){
            $answers = InstanceAnswer::where('interview_instance_id', $instance->id)
                ->where('repeatable_index', $i)
                ->whereHas('item', function($query) use ($request){
                    $query->where('interview_section_id', $request->section_id);
                })
                ->get();

            $html .= view('interviews.repeatable-section', compact('section', 'instance', 'i', 'answers'))->render();
        }

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }
}
