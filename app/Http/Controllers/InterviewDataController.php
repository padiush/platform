<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

use App\Exports\EthnobotanyRExport;
use App\Exports\CustomExport;

use App\Models\ProjectAccess;
use App\Models\Project;
use App\Models\User;
use App\Models\InstanceAnswer;
use App\Models\CatalogSpecies;
use App\Models\InterviewItem;
use App\Models\InterviewInstance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InterviewDataController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        $user = Auth::user();

        $accesses = ProjectAccess::where('user_id', $user->id)->get();

        $projects = collect();

        foreach ($accesses as $access) {
            $project = Project::find($access->project_id);

            if (!$project) {
                continue;
            }

            // Only include projects where the user has at least one relevant capability
            $canManageData = $user->hasCapabilityOnProject(
                $project,
                'manage_data'
            );
            $canGenerateReports = $user->hasCapabilityOnProject(
                $project,
                'generate_reports'
            );

            if ($canManageData || $canGenerateReports) {
                $projects->push([
                    'id' => $project->id,
                    'name' => $project->name,
                    'unlinked_count' => $project->unlinkedAnswers()->count(),
                    'linked_count' => $project->linkedAnswers()->count(),
                    'capabilities' => [
                        'manage_data' => $canManageData,
                        'generate_reports' => $canGenerateReports,
                    ],
                ]);
            }
        }

        if ($projects->isEmpty()) {
            return redirect()
                ->route('projects.index')
                ->with('message', 'data.no_projects')
                ->with('message_type', 'error');
        }

        return Inertia::render('Data/Index', [
            'projects' => $projects,
        ]);
    }

    public function linkSpecies(Project $project): Response|RedirectResponse
    {
        $this->checkPermission($project);

        $unlinked_answers = $project->unlinkedAnswers();

        if ($unlinked_answers->count() === 0) {
            return redirect()
                ->route('data.index')
                ->with(
                    'error',
                    'No hay respuestas sin vincular en este proyecto.'
                );
        }

        $answered_sections = collect();

        foreach ($unlinked_answers as $answer) {
            $this_section = new \stdClass();

            if ($answer->section->repeatable) {
                $section_answers = InstanceAnswer::where(
                    'interview_section_id',
                    $answer->section->id
                )
                    ->where('repeatable_index', $answer->repeatable_index)
                    ->get();
            } else {
                $section_answers = InstanceAnswer::where(
                    'interview_section_id',
                    $answer->section->id
                )->get();
            }

            $this_section->section = [
                'id' => $answer->section->id,
                'name' => $answer->section->name,
                'repeatable' => $answer->section->repeatable,
            ];
            $this_section->repeatable = $answer->section->repeatable;
            $this_section->interview_instance_id =
                $answer->interview_instance_id;
            $this_section->repeatable_index = $answer->section->repeatable
                ? $answer->repeatable_index
                : null;
            $this_section->items = $answer->section->items->map(function (
                $item
            ) {
                return [
                    'id' => $item->id,
                    'label' => $item->label,
                ];
            });
            $this_section->answers = $section_answers->map(function ($ans) {
                return [
                    'id' => $ans->id,
                    'interview_item_id' => $ans->interview_item_id,
                    'answer' => $ans->answer,
                ];
            });

            $answered_sections->push($this_section);
        }

        $species = CatalogSpecies::where('project_id', $project->id)
            ->orderBy('family', 'asc')
            ->orderBy('genus', 'asc')
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'family' => $s->family,
                    'genus' => $s->genus,
                    'name' => $s->name,
                    'authority' => $s->authority,
                ];
            });

        // Order by genus and then by name
        $species = $species
            ->sortBy(function ($s) {
                return [$s['genus'], $s['name']];
            })
            ->values();

        return Inertia::render('Data/LinkSpecies', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'answered_sections' => $answered_sections,
            'species' => $species,
            'csrf_token' => csrf_token(),
        ]);
    }

    public function handleLinkRequest(
        Project $project,
        Request $request
    ): JsonResponse {
        $this->checkPermission($project, true);

        $request->validate([
            'interview_instance_id' => 'required|exists:interview_instances,id',
            'catalog_species_id' => 'required|exists:catalog_species,id',
            'interview_section_id' => 'required|exists:interview_sections,id',
            'repeatable_index' => 'nullable|integer',
        ]);

        $answers = InstanceAnswer::where(
            'interview_instance_id',
            $request->interview_instance_id
        )
            ->where('interview_section_id', $request->interview_section_id)
            ->get();

        if ($request->filled('repeatable_index')) {
            $answers = $answers->where(
                'repeatable_index',
                $request->repeatable_index
            );
        }

        foreach ($answers as $answer) {
            if ($answer->item->link_to_species) {
                $answer->catalog_species_id = $request->catalog_species_id;
                $answer->save();
            }
        }

        return response()->json(['success' => true]);
    }

    public function prepareEthnobotanyR(Project $project)
    {
        $this->checkPermission($project);

        // Get all the forms on the project
        $forms = $project->interviewForms;

        foreach ($forms as $form) {
            $form->load('sections.items');
        }

        return view('data.ethnobotanyr', compact('project', 'forms'));
    }

    public function handleEthnobotanyRRequest(
        Project $project,
        Request $request
    ) {
        $this->checkPermission($project, true);

        $request->validate([
            'form_id' => 'required|exists:interview_forms,id',
            'field_id' => 'required|exists:interview_items,id',
        ]);

        $form = $project->interviewForms
            ->where('id', $request->form_id)
            ->first();

        $item = InterviewItem::find($request->field_id);
        $categories = InstanceAnswer::where(
            'interview_item_id',
            $item->id
        )->get();

        // Leave only unique answers
        $categories = $categories->unique('answer');

        // Get all the answers from $item->section where the answer's catalog_species_id is not null
        $answers = InstanceAnswer::where(
            'interview_section_id',
            $item->section->id
        )
            ->where('catalog_species_id', '!=', null)
            ->get();

        foreach ($answers as $answer) {
            $answer->load('species');
            $answer->category = InstanceAnswer::where(
                'interview_item_id',
                $request->field_id
            )
                ->where('interview_instance_id', $answer->interview_instance_id)
                ->where('repeatable_index', $answer->repeatable_index)
                ->first()->answer;
        }

        return Excel::download(
            new EthnobotanyRExport($answers, $categories),
            'ethnobotanyr.xlsx'
        );
    }

    public function prepareCustom(Project $project)
    {
        $this->checkPermission($project);

        // Get all the forms on the project
        $forms = $project->interviewForms;

        foreach ($forms as $form) {
            $form->load('sections', 'sections.items');
        }

        return view('data.custom', compact('project', 'forms'));
    }

    public function handleCustomRequest(Project $project, Request $request)
    {
        $this->checkPermission($project);

        $request->validate([
            'form_id' => 'required|exists:interview_forms,id',
            'selected_fields' => 'required|json',
        ]);

        $form = $project->interviewForms
            ->where('id', $request->form_id)
            ->first();

        $selected_fields = json_decode($request->selected_fields);

        $items = InterviewItem::whereIn('id', $selected_fields)->get();

        // Check if all items belong to a section with the same repeatable value
        $repeatable = collect();

        foreach ($items as $item) {
            $item->load('section');
            $repeatable->push($item->section->repeatable);
        }

        if ($repeatable->unique()->count() > 1) {
            return redirect()
                ->back()
                ->with(
                    'error',
                    'No puedes seleccionar campos de secciones repetibles y no repetibles al mismo tiempo.'
                );
        }

        $repeatable = $repeatable->first();

        $instances = collect();

        foreach ($items as $item) {
            $answers = InstanceAnswer::where(
                'interview_item_id',
                $item->id
            )->get();

            foreach ($answers as $answer) {
                $instances->push($answer->interview_instance_id);
            }
        }

        $instances = $instances->unique();
        $instances = InterviewInstance::whereIn('id', $instances)->get();

        if ($repeatable) {
            foreach ($instances as $instance) {
                // Get the highest repeatable index for this instance
                $max_repeatable_index = InstanceAnswer::where(
                    'interview_instance_id',
                    $instance->id
                )->max('repeatable_index');
                $instance->max_repeatable_index = $max_repeatable_index;
            }
        }

        foreach ($items as $item) {
            $answers = InstanceAnswer::where(
                'interview_item_id',
                $item->id
            )->get();
            $item->answers = $answers;
        }

        return Excel::download(
            new CustomExport($items, $instances, $repeatable),
            'custom.xlsx'
        );
    }

    private function checkPermission(Project $project, $json = false)
    {
        $access = ProjectAccess::where('user_id', Auth::id())
            ->where('project_id', $project->id)
            ->first();

        if (!$access) {
            if ($json) {
                return response()->json(
                    ['error' => 'No tienes acceso a este proyecto.'],
                    403
                );
            }

            return redirect()
                ->route('projects.index')
                ->with('error', 'No tienes acceso a este proyecto.');
        }

        if (
            !Auth::user()->hasCapabilityOnProject($project, 'manage_data') &&
            !Auth::user()->hasCapabilityOnProject($project, 'generate_reports')
        ) {
            if ($json) {
                return response()->json(
                    [
                        'error' =>
                            'No tienes permisos para acceder a los datos de este proyecto.',
                    ],
                    403
                );
            }

            return redirect()
                ->route('projects.index')
                ->with(
                    'error',
                    'No tienes permisos para acceder a los datos de este proyecto.'
                );
        }
    }
}
