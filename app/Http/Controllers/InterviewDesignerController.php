<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\InterviewForm;
use App\Models\InterviewSection;
use App\Models\InterviewItem;
use App\Models\Project;
use App\Models\ProjectAccess;

class InterviewDesignerController extends Controller
{
    public function createSection(Request $request, InterviewForm $form){
        $project = $form->project;

        $access = ProjectAccess::where('user_id', Auth::id())->where('project_id', $project->id)->first();

        if(!$access || !Auth::user()->hasCapabilityOnProject($project, 'manage_forms')){
            return redirect()->route('projects.index')->with('error', 'No tienes permisos para editar formularios en este proyecto.');
        }

        $request->validate([
            'name' =>  'nullable|string',
            'description' =>  'nullable|string',
            'order' =>  'nullable|integer',
            'repeatable' =>  'nullable|boolean',
        ]);

        $last_section = InterviewSection::where('interview_form_id', $form->id)->orderBy('order', 'desc')->get();
        if($last_section->count() == 0){
            $order = 1;
        } else {
            $order = $last_section->first()->order + 1;
        }

        $section = new InterviewSection();
        $section->interview_form_id = $form->id;
        $section->name = $request->name;
        $section->description = $request->description;
        $section->order = $order;
        $section->repeatable = $request->repeatable;
        $section->save();

        return response()->json($section);
    }

    public function getSectionItems(Request $request, InterviewForm $form){
        $project = $form->project;

        $access = ProjectAccess::where('user_id', Auth::id())->where('project_id', $project->id)->first();

        if(!$access || !Auth::user()->hasCapabilityOnProject($project, 'manage_forms')){
            return redirect()->route('projects.index')->with('error', 'No tienes permisos para editar formularios en este proyecto.');
        }

        $request->validate([
            'section_id' =>  'required|integer|exists:interview_sections,id',
        ]);

        $section = InterviewSection::find($request->section_id);

        return response()->json($section->items);
    }

    public function createItem(Request $request, InterviewForm $form){
        $project = $form->project;

        $access = ProjectAccess::where('user_id', Auth::id())->where('project_id', $project->id)->first();

        if(!$access || !Auth::user()->hasCapabilityOnProject($project, 'manage_forms')){
            return redirect()->route('projects.index')->with('error', 'No tienes permisos para editar formularios en este proyecto.');
        }

        $request->validate([
            'label' =>  'nullable|string',
            'type' =>  'nullable|string',
            'name' =>  'nullable|string',
            'required' =>  'nullable|boolean',
            'min' =>  'nullable|numeric',
            'max' =>  'nullable|numeric',
            'step' =>  'nullable|numeric',
            'options' =>  'nullable|array',
            'link_to_species' =>  'nullable|boolean',
        ]);

        $section = InterviewSection::where('interview_form_id', $form->id)->orderBy('order', 'desc')->first();

        if($section->items){
            $lastItem = InterviewItem::where('interview_section_id', $section->id)->orderBy('order', 'desc')->first();
        } else {
            $lastItem = null;
        }

        $item = new InterviewItem();
        $item->interview_section_id = $section->id;
        $item->label = $request->label;
        $item->type = $request->type;
        $item->name = $request->name;
        $item->required = $request->has('required') ? true : false;
        $item->order = $lastItem ? $lastItem->order + 1 : 1;
        $item->min = $request->min;
        $item->max = $request->max;
        $item->step = $request->step;
        $item->options = $request->options;
        $item->link_to_species = $request->name === 'especie' ? true : false;
        $item->save();

        return response()->json($item);
    }

    public function updateItem(Request $request, InterviewForm $form){
        $project = $form->project;

        $access = ProjectAccess::where('user_id', Auth::id())->where('project_id', $project->id)->first();

        if(!$access || !Auth::user()->hasCapabilityOnProject($project, 'manage_forms')){
            return redirect()->route('projects.index')->with('error', 'No tienes permisos para editar formularios en este proyecto.');
        }

        $request->validate([
            'id' =>  'required|integer',
            'json' =>  'required|string',
        ]);

        $item = InterviewItem::find($request->id);
        $item_section = InterviewSection::find($item->interview_section_id);
        $item_form = InterviewForm::find($item_section->interview_form_id);

        if($item_form->id != $form->id){
            return redirect()->route('projects.index')->with('error', 'Se ha intentado actualizar un item que no pertenece al formulario.');
        }

        $data = json_decode($request->json);

        $item->label = $data->label != 'null' ? $data->label : $item->label;
        $item->name = $data->name != 'null' ? $data->name : $item->name;
        $item->required = $data->required;
        $item->min = $data->min;
        $item->max = $data->max;
        $item->step = $data->step;
        $item->options = $data->options;
        $item->link_to_species = $data->link_to_species;
        $item->save();

        return response()->json($item);
    }

    public function getItem(Request $request, InterviewForm $form){
        $project = $form->project;

        $access = ProjectAccess::where('user_id', Auth::id())->where('project_id', $project->id)->first();

        if(!$access || !Auth::user()->hasCapabilityOnProject($project, 'manage_forms')){
            return redirect()->route('projects.index')->with('error', 'No tienes permisos para editar formularios en este proyecto.');
        }

        $request->validate([
            'id' =>  'required|integer',
        ]);

        $item = InterviewItem::find($request->id);

        return response()->json($item);
    }

    public function updateSection(Request $request, InterviewForm $form){
        $project = $form->project;

        $access = ProjectAccess::where('user_id', Auth::id())->where('project_id', $project->id)->first();

        if(!$access || !Auth::user()->hasCapabilityOnProject($project, 'manage_forms')){
            return redirect()->route('projects.index')->with('error', 'No tienes permisos para editar formularios en este proyecto.');
        }

        $request->validate([
            'id' =>  'required|integer',
            'json' =>  'required|string',
        ]);

        $section = InterviewSection::find($request->id);
        $section_form = InterviewForm::find($section->interview_form_id);

        if($section_form->id != $form->id){
            return redirect()->route('projects.index')->with('error', 'Se ha intentado actualizar una sección que no pertenece al formulario.');
        }

        $data = json_decode($request->json);

        $section->name = $data->name != 'null' ? $data->name : $section->name;
        $section->description = $data->description != 'null' ? $data->description : $section->description;
        $section->order = $data->order;
        $section->repeatable = $data->repeatable;
        $section->save();

        return response()->json($section);
    }

    public function getSection(Request $request, InterviewForm $form){
        $project = $form->project;

        $access = ProjectAccess::where('user_id', Auth::id())->where('project_id', $project->id)->first();

        if(!$access || !Auth::user()->hasCapabilityOnProject($project, 'manage_forms')){
            return redirect()->route('projects.index')->with('error', 'No tienes permisos para editar formularios en este proyecto.');
        }

        $request->validate([
            'id' =>  'required|integer',
        ]);

        $section = InterviewSection::find($request->id);

        return response()->json($section);
    }
}