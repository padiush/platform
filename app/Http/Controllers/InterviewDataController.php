<?php

namespace App\Http\Controllers;

use App\Exports\CustomExport;
use App\Exports\EthnobotanyRExport;
use App\Models\CatalogSpecies;
use App\Models\InstanceAnswer;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Project;
use App\Models\User;
use App\Services\CatalogSpeciesSearch;
use App\Services\SpeciesLinkingList;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class InterviewDataController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        $accesses = Auth::user()
            ->projectAccesses()
            ->with(['project', 'capability'])
            ->get();

        $projects = collect();

        foreach ($accesses as $access) {
            $project = $access->project;

            if (! $project) {
                continue;
            }

            // Only include projects where the user has at least one relevant capability
            $canManageData = (bool) $access->capability->manage_data;
            $canGenerateReports = (bool) $access->capability->generate_reports;

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

        return Inertia::render('Data/Index', [
            'projects' => $projects,
        ]);
    }

    public function linkSpecies(
        Project $project,
        Request $request,
        SpeciesLinkingList $linkingList
    ): Response|RedirectResponse {
        $this->checkPermission($project);

        // Only bounce when the project has no linkable answers at all. An empty
        // search/filter result stays on the page and shows an empty state.
        if ($project->speciesAnswers()->count() === 0) {
            return redirect()
                ->route('data.index')
                ->with(
                    'error',
                    'No hay respuestas en este proyecto que puedan vincularse a especies.'
                );
        }

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => in_array($request->query('status'), SpeciesLinkingList::STATUSES, true)
                ? $request->query('status')
                : 'all',
            'group' => $request->boolean('group', true),
        ];

        $rows = $linkingList->paginate(
            $project,
            $filters,
            (int) $request->integer('page', 1)
        );

        $linked = $project->linkedAnswers()->count();
        $unlinked = $project->unlinkedAnswers()->count();

        return Inertia::render('Data/LinkSpecies', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'rows' => $rows,
            'filters' => $filters,
            'totals' => [
                'linked' => $linked,
                'unlinked' => $unlinked,
                'total' => $linked + $unlinked,
            ],
            'csrf_token' => csrf_token(),
        ]);
    }

    public function searchSpecies(
        Project $project,
        Request $request,
        CatalogSpeciesSearch $search
    ): JsonResponse {
        $this->checkPermission($project, true);

        $paginator = $search->paginate(
            $project,
            ['q' => trim((string) $request->query('q', ''))],
            (int) $request->integer('page', 1)
        );

        return response()->json([
            'data' => collect($paginator->items())->map(fn ($sp) => [
                'id' => $sp->id,
                'family' => $sp->family,
                'genus' => $sp->genus,
                'name' => $sp->name,
                'authority' => $sp->authority,
            ])->all(),
            'current_page' => $paginator->currentPage(),
            'has_more' => $paginator->hasMorePages(),
        ]);
    }

    public function handleLinkRequest(
        Project $project,
        Request $request
    ): JsonResponse {
        $this->checkPermission($project, true);

        $validated = $request->validate([
            'interview_instance_id' => 'required|exists:interview_instances,id',
            'catalog_species_id' => 'nullable|exists:catalog_species,id',
            'interview_section_id' => 'required|exists:interview_sections,id',
            'repeatable_index' => 'nullable|integer',
        ]);

        $speciesId = $validated['catalog_species_id'] ?? null;

        $this->assertTargetBelongsToProject(
            $project,
            $validated['interview_instance_id'],
            $validated['interview_section_id'],
            $speciesId
        );

        $this->linkGroup(
            $validated['interview_instance_id'],
            $validated['interview_section_id'],
            $request->filled('repeatable_index')
                ? (int) $validated['repeatable_index']
                : null,
            $speciesId
        );

        return response()->json(['success' => true]);
    }

    public function handleBulkLinkRequest(
        Project $project,
        Request $request
    ): JsonResponse {
        $this->checkPermission($project, true);

        $validated = $request->validate([
            'catalog_species_id' => 'nullable|exists:catalog_species,id',
            'targets' => 'required|array|min:1',
            'targets.*.interview_instance_id' => 'required|exists:interview_instances,id',
            'targets.*.interview_section_id' => 'required|exists:interview_sections,id',
            'targets.*.repeatable_index' => 'nullable|integer',
        ]);

        $speciesId = $validated['catalog_species_id'] ?? null;

        DB::transaction(function () use ($project, $validated, $speciesId) {
            foreach ($validated['targets'] as $target) {
                $repeatableIndex = ($target['repeatable_index'] ?? null) !== null
                    ? (int) $target['repeatable_index']
                    : null;

                $this->assertTargetBelongsToProject(
                    $project,
                    $target['interview_instance_id'],
                    $target['interview_section_id'],
                    $speciesId
                );

                $this->linkGroup(
                    $target['interview_instance_id'],
                    $target['interview_section_id'],
                    $repeatableIndex,
                    $speciesId
                );
            }
        });

        return response()->json([
            'success' => true,
            'count' => count($validated['targets']),
        ]);
    }

    /**
     * Links (or, when $speciesId is null, unlinks) every species-linkable
     * answer in one (instance, section, repeatable_index) group.
     */
    private function linkGroup(
        string $instanceId,
        int $sectionId,
        ?int $repeatableIndex,
        ?int $speciesId
    ): void {
        $answers = InstanceAnswer::where('interview_instance_id', $instanceId)
            ->where('interview_section_id', $sectionId)
            ->when(
                $repeatableIndex !== null,
                fn ($query) => $query->where('repeatable_index', $repeatableIndex)
            )
            ->get();

        foreach ($answers as $answer) {
            if ($answer->item->link_to_species) {
                $answer->catalog_species_id = $speciesId;
                $answer->save();
            }
        }
    }

    /**
     * Guards against cross-project linking: every referenced resource must
     * belong to this project, otherwise an authorized user could link across
     * projects by mixing ids.
     */
    private function assertTargetBelongsToProject(
        Project $project,
        string $instanceId,
        int $sectionId,
        ?int $speciesId
    ): void {
        $projectFormIds = $project->interviewForms()->pluck('id');
        $instance = InterviewInstance::findOrFail($instanceId);
        $section = InterviewSection::findOrFail($sectionId);

        $valid = $projectFormIds->contains($instance->interview_form_id) &&
            $projectFormIds->contains($section->interview_form_id);

        if ($speciesId !== null) {
            $species = CatalogSpecies::findOrFail($speciesId);
            $valid = $valid && $species->project_id === $project->id;
        }

        if (! $valid) {
            $this->deny('Recurso no válido para este proyecto.', true);
        }
    }

    public function prepareEthnobotanyR(Project $project)
    {
        $this->checkPermission($project);

        // Get all the forms on the project
        $forms = $project->interviewForms;

        foreach ($forms as $form) {
            $form->load('sections.items');
        }

        return Inertia::render('Data/EthnobotanyR', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'forms' => $forms->map(function ($form) {
                return [
                    'id' => $form->id,
                    'name' => $form->name,
                    'sections' => $form->sections->map(function ($section) {
                        return [
                            'id' => $section->id,
                            'name' => $section->name,
                            'items' => $section->items->map(function ($item) {
                                return [
                                    'id' => $item->id,
                                    'label' => $item->label,
                                ];
                            }),
                        ];
                    }),
                ];
            }),
            'csrf_token' => csrf_token(),
        ]);
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

        // The selected form and field must belong to this project.
        if (
            ! $form ||
            ! $item ||
            $item->section->interview_form_id !== $form->id
        ) {
            $this->deny('El campo no pertenece a este proyecto.', false);
        }

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
            // May be null when this instance has no category answer for the
            // selected field; the export treats a null category as no match.
            $answer->category = InstanceAnswer::where(
                'interview_item_id',
                $request->field_id
            )
                ->where('interview_instance_id', $answer->interview_instance_id)
                ->where('repeatable_index', $answer->repeatable_index)
                ->first()?->answer;
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

        return Inertia::render('Data/Custom', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'forms' => $forms->map(function ($form) {
                return [
                    'id' => $form->id,
                    'name' => $form->name,
                    'sections' => $form->sections->map(function ($section) {
                        return [
                            'id' => $section->id,
                            'name' => $section->name,
                            'repeatable' => $section->repeatable,
                            'items' => $section->items->map(function ($item) {
                                return [
                                    'id' => $item->id,
                                    'label' => $item->label,
                                ];
                            }),
                        ];
                    }),
                ];
            }),
            'csrf_token' => csrf_token(),
        ]);
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

        if (! $form) {
            $this->deny('El formulario no pertenece a este proyecto.', false);
        }

        $selected_fields = json_decode($request->selected_fields);

        $items = InterviewItem::whereIn('id', $selected_fields)->get();

        // Every selected field must belong to a section of the selected form.
        $formSectionIds = $form->sections()->pluck('id');

        foreach ($items as $item) {
            if (! $formSectionIds->contains($item->interview_section_id)) {
                $this->deny(
                    'Los campos seleccionados no pertenecen a este proyecto.',
                    false
                );
            }
        }

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

    /**
     * Aborts the request (403 JSON or redirect with a flash) unless the user
     * has manage_data or generate_reports on the project.
     */
    private function checkPermission(Project $project, $json = false): void
    {
        $user = Auth::user();

        if (! $user->can('view', $project)) {
            $this->deny('No tienes acceso a este proyecto.', $json);
        }

        if (
            ! $user->can('manageData', $project) &&
            ! $user->can('generateReports', $project)
        ) {
            $this->deny(
                'No tienes permisos para acceder a los datos de este proyecto.',
                $json
            );
        }
    }

    private function deny(string $message, bool $json): never
    {
        throw new HttpResponseException(
            $json || request()->expectsJson()
                ? response()->json(['error' => $message], 403)
                : redirect()
                    ->route('projects.index')
                    ->with('error', $message)
        );
    }
}
