<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

use App\Exports\EthnobotanyRExport;

use App\Models\ProjectAccess;
use App\Models\Project;
use App\Models\User;
use App\Models\InstanceAnswer;
use App\Models\CatalogSpecies;
use App\Models\InterviewItem;

class InterviewDataController extends Controller
{
    public function index(){
        $accesses = ProjectAccess::where('user_id', Auth::id())->get();

        $projects = collect();

        foreach($accesses as $access){
            $project = Project::find($access->project_id);

            if(Auth::user()->hasCapabilityOnProject($project, 'manage_data') || Auth::user()->hasCapabilityOnProject($project, 'generate_reports')){
                $projects->push($project);
            }
        }

        if($projects->count() == 0){
            return redirect()->route('projects.index')->with('error', 'No has participado en ningún proyecto.');
        }

        return view('data.index', compact('projects'));
    }

    public function linkSpecies(Project $project){
        $this->checkPermission($project);

        $unlinked_answers = $project->unlinkedAnswers();

        if($unlinked_answers->count() == 0){
            return redirect()->route('data.index')->with('error', 'No hay respuestas sin vincular en este proyecto.');
        }

        $answered_sections = collect();

        foreach($unlinked_answers as $answer){
            $this_section = new \stdClass();

            if($answer->section->repeatable){
                $section_answers = InstanceAnswer::where('interview_section_id', $answer->section->id)->where('repeatable_index', $answer->repeatable_index)->get();
            } else {
                $section_answers = InstanceAnswer::where('interview_section_id', $answer->section->id)->get();
            }

            $this_section->section = $answer->section;
            $this_section->repeatable = $answer->section->repeatable;
            $this_section->interview_instance_id = $answer->interview_instance_id;
            $this_section->repeatable_index = $answer->section->repeatable ? $answer->repeatable_index : null;
            $this_section->items = $answer->section->items;
            $this_section->answers = $section_answers;

            $answered_sections->push($this_section);
        }

        $species = CatalogSpecies::where('project_id', $project->id)->orderBy('family', 'asc')->orderBy('genus', 'asc')->orderBy('name', 'asc')->get();


        return view('data.link', compact('project', 'answered_sections', 'species'));
    }

    public function handleLinkRequest(Project $project, Request $request){
        $this->checkPermission($project, true);

        $request->validate([
            'interview_instance_id' => 'required|exists:interview_instances,id',
            'catalog_species_id' => 'required|exists:catalog_species,id',
            'interview_section_id' => 'required|exists:interview_sections,id',
            'repeatable_index' => 'nullable|integer',
        ]);

        $answers = InstanceAnswer::where('interview_instance_id', $request->interview_instance_id)->where('interview_section_id', $request->interview_section_id)->get();

        if($request->repeatable_index){
            $answers = $answers->where('repeatable_index', $request->repeatable_index);
        }

        foreach($answers as $answer){
            if($answer->item->link_to_species){
                $answer->catalog_species_id = $request->catalog_species_id;
                $answer->save();
            }
        }

        $view = view('data.linked')->render();

        return response()->json(['success' => true, 'html' => $view]);
    }

    public function prepareEthnobotanyR(Project $project){
        $this->checkPermission($project);

        // Get all the forms on the project
        $forms = $project->interviewForms;

        foreach($forms as $form){
            $form->load('sections.items');
        }

        return view('data.ethnobotanyr', compact('project', 'forms'));
    }

    public function handleEthnobotanyRRequest(Project $project, Request $request){
        $this->checkPermission($project, true);

        $request->validate([
            'form_id' => 'required|exists:interview_forms,id',
            'field_id' => 'required|exists:interview_items,id',
        ]);

        $form = $project->interviewForms->where('id', $request->form_id)->first();

        $item = InterviewItem::find($request->field_id);
        $categories = InstanceAnswer::where('interview_item_id', $item->id)->get();

        // Leave only unique answers
        $categories = $categories->unique('answer');

        // Get all the answers from $item->section where the answer's catalog_species_id is not null
        $answers = InstanceAnswer::where('interview_section_id', $item->section->id)->where('catalog_species_id', '!=', null)->get();
        
        foreach($answers as $answer){
            $answer->load('species');
            $answer->category = InstanceAnswer::where('interview_item_id', $request->field_id)->where('interview_instance_id', $answer->interview_instance_id)->where('repeatable_index', $answer->repeatable_index)->first()->answer;
        }

        return Excel::download(new EthnobotanyRExport($answers, $categories), 'ethnobotanyr.xlsx');
    }


    private function checkPermission(Project $project, $json = false){
        $access = ProjectAccess::where('user_id', Auth::id())->where('project_id', $project->id)->first();

        if(!$access){
            if($json){
                return response()->json(['error' => 'No tienes acceso a este proyecto.'], 403);
            }

            return redirect()->route('projects.index')->with('error', 'No tienes acceso a este proyecto.');
        }

        if(!Auth::user()->hasCapabilityOnProject($project, 'manage_data') && !Auth::user()->hasCapabilityOnProject($project, 'generate_reports')){
            if($json){
                return response()->json(['error' => 'No tienes permisos para acceder a los datos de este proyecto.'], 403);
            }

            return redirect()->route('projects.index')->with('error', 'No tienes permisos para acceder a los datos de este proyecto.');
        }
    }
}
