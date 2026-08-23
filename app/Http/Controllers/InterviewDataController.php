<?php

namespace App\Http\Controllers;

use App\Exports\CustomExport;
use App\Exports\EthnobotanyRExport;
use App\Exports\IndicesExport;
use App\Exports\IndicesReportExport;
use App\Exports\ReferencesSheet;
use App\Models\CatalogSpecies;
use App\Models\ChartPreference;
use App\Models\InstanceAnswer;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Project;
use App\Models\User;
use App\Services\CatalogSpeciesSearch;
use App\Services\EthnobiologyIndices;
use App\Services\FieldRecordEvidence;
use App\Services\InterviewDataExport;
use App\Services\InterviewDataTable;
use App\Services\SpeciesLinkingList;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

    public function viewData(
        Project $project,
        Request $request,
        InterviewDataTable $table
    ): Response|RedirectResponse {
        $this->checkPermission($project);

        // Only forms that actually have interviews are worth viewing.
        $forms = $project->interviewForms()
            ->withCount('instances')
            ->orderBy('name')
            ->get()
            ->filter(fn ($form) => $form->instances_count > 0)
            ->values();

        if ($forms->isEmpty()) {
            return redirect()
                ->route('data.index')
                ->with('error', 'Este proyecto no tiene formularios con datos.');
        }

        $form = $forms->firstWhere('id', (int) $request->query('form')) ?? $forms->first();
        $form->load([
            'sections' => fn ($query) => $query->orderBy('order'),
            'sections.items' => fn ($query) => $query->orderBy('order'),
        ]);

        $section = $form->sections->firstWhere('id', (int) $request->query('section'))
            ?? $form->sections->first();

        $filters = [
            'form' => $form->id,
            'section' => $section?->id,
            'interviewer' => $request->integer('interviewer') ?: null,
            'from' => $request->query('from') ?: null,
            'to' => $request->query('to') ?: null,
            'tab' => in_array($request->query('tab'), ['table', 'summary'], true)
                ? $request->query('tab')
                : 'table',
        ];

        $interviewers = User::query()
            ->whereIn('id', $form->instances()->select('user_id')->distinct())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($user) => ['id' => $user->id, 'name' => $user->name])
            ->values();

        return Inertia::render('Data/View', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'forms' => $forms->map(fn ($f) => [
                'id' => $f->id,
                'name' => $f->name,
            ])->values(),
            'structure' => $section === null ? null : [
                'form_id' => $form->id,
                'sections' => $form->sections->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'repeatable' => $s->repeatable,
                ])->values(),
                'section' => [
                    'id' => $section->id,
                    'name' => $section->name,
                    'repeatable' => $section->repeatable,
                    'items' => $section->items->map(fn ($item) => [
                        'id' => $item->id,
                        'label' => $item->label,
                        'type' => $item->type,
                        'link_to_species' => $item->link_to_species,
                    ])->values(),
                ],
            ],
            'rows' => $section === null
                ? null
                : $table->rows($form, $section, $filters, (int) $request->integer('page', 1)),
            'filters' => $filters,
            'interviewers' => $interviewers,
            // Decrypting a whole section to aggregate is only worth it for the
            // Summary tab: eager when that tab is requested (so a direct link
            // renders without a round-trip), optional otherwise so the Table
            // tab never pays for it.
            'summary' => $section === null
                ? null
                : ($filters['tab'] === 'summary'
                    ? fn () => $this->withChartPreferences($table->summary($form, $section))
                    : Inertia::optional(
                        fn () => $this->withChartPreferences($table->summary($form, $section))
                    )),
            // For the native-form "export this section" download.
            'csrf_token' => csrf_token(),
        ]);
    }

    /**
     * Overlays the current user's saved chart type onto each summary field,
     * ignoring a stale choice that is no longer valid for the field's kind.
     */
    private function withChartPreferences(array $summary): array
    {
        $preferences = ChartPreference::query()
            ->where('user_id', Auth::id())
            ->whereIn('interview_item_id', collect($summary)->pluck('item_id'))
            ->pluck('chart_type', 'interview_item_id');

        return collect($summary)->map(function (array $field) use ($preferences) {
            $saved = $preferences[$field['item_id']] ?? null;

            if ($saved !== null && in_array($saved, $field['available'], true)) {
                $field['chart_type'] = $saved;
            }

            return $field;
        })->all();
    }

    public function saveChartPreference(
        Project $project,
        Request $request
    ): JsonResponse {
        $this->checkPermission($project, true);

        $validated = $request->validate([
            'interview_item_id' => 'required|exists:interview_items,id',
            'chart_type' => 'required|string',
        ]);

        $item = InterviewItem::findOrFail($validated['interview_item_id']);
        $section = InterviewSection::findOrFail($item->interview_section_id);

        if (! $project->interviewForms()->pluck('id')->contains($section->interview_form_id)) {
            $this->deny('Recurso no válido para este proyecto.', true);
        }

        $kind = InterviewDataTable::kindFor($item);

        if (! in_array($validated['chart_type'], InterviewDataTable::chartTypesFor($kind), true)) {
            return response()->json(
                ['error' => 'Tipo de gráfico no válido para este campo.'],
                422
            );
        }

        ChartPreference::updateOrCreate(
            ['user_id' => Auth::id(), 'interview_item_id' => $item->id],
            ['chart_type' => $validated['chart_type']],
        );

        return response()->json(['success' => true]);
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

    public function reports(
        Project $project,
        EthnobiologyIndices $indices,
        FieldRecordEvidence $evidence
    ): Response|RedirectResponse {
        $this->checkPermission($project);

        return Inertia::render('Data/Reports', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'indices' => $indices->compute($project),
            // Stated, not enforced — the same treatment unlinked citations get,
            // so a researcher knows the denominator of what is documented.
            'evidence' => $evidence->forProject($project)['coverage'],
        ]);
    }

    public function downloadReport(
        Project $project,
        Request $request,
        EthnobiologyIndices $indices,
        FieldRecordEvidence $evidence
    ): BinaryFileResponse {
        $this->checkPermission($project);

        $format = $request->query('format') === 'csv' ? 'csv' : 'xlsx';
        $data = $indices->compute($project);
        $byTaxon = $evidence->forProject($project)['by_taxon'];

        // Literature-standard abbreviations — language-independent. Voucher and
        // permit sit with the taxonomy rather than the indices: they say what
        // the row is evidenced by, not what was computed from it.
        $headings = ['Family', 'Genus', 'Species', 'Authority', 'Voucher No.', 'Collecting permit', 'FC', 'NU', 'RFC', 'UV', 'CI', 'RI', 'CV'];
        $rows = array_map(fn ($species) => [
            $species['species']['family'],
            $species['species']['genus'],
            $species['species']['name'],
            $species['species']['authority'],
            $byTaxon[$species['species']['id']]['vouchers'] ?? '',
            $byTaxon[$species['species']['id']]['permits'] ?? '',
            $species['fc'],
            $species['nu'],
            round($species['rfc'], 4),
            round($species['uv'], 4),
            round($species['ci'], 4),
            round($species['ri'], 4),
            round($species['cv'], 4),
        ], $data['species']);

        $indicesSheet = new IndicesExport($headings, $rows);
        $filename = $this->exportFilename($project, 'indices', $format);

        // CSV is a single flat table; xlsx also carries a References sheet.
        if ($format === 'csv') {
            return Excel::download($indicesSheet, $filename, $this->exportWriter('csv'));
        }

        return Excel::download(
            new IndicesReportExport(
                $indicesSheet,
                new ReferencesSheet($this->indexReferences())
            ),
            $filename,
        );
    }

    /**
     * The source paper for each index plus the ethnobotanyR attribution, for the
     * xlsx References sheet. Mirrors the citations shown on the report page
     * (resources/js/Pages/Data/Reports.jsx); keep the two in sync.
     *
     * @return list<list<string>>
     */
    private function indexReferences(): array
    {
        return [
            ['RFC', 'Relative frequency of citation', 'Tardío, J. & Pardo-de-Santayana, M. (2008). Cultural importance indices: a comparative analysis. Economic Botany 62(1), 24–39.'],
            ['NU', 'Number of uses', 'Prance, G. T., Balée, W., Boom, B. M. & Carneiro, R. L. (1987). Quantitative ethnobotany and the case for conservation in Amazonia. Conservation Biology 1(4), 296–310.'],
            ['UV', 'Use value', 'Phillips, O. & Gentry, A. H. (1993). The useful plants of Tambopata, Peru. Economic Botany 47(1), 15–32.'],
            ['CI', 'Cultural importance index', 'Tardío, J. & Pardo-de-Santayana, M. (2008). Cultural importance indices: a comparative analysis. Economic Botany 62(1), 24–39.'],
            ['RI', 'Relative importance', 'Tardío, J. & Pardo-de-Santayana, M. (2008). Cultural importance indices: a comparative analysis. Economic Botany 62(1), 24–39.'],
            ['CV', 'Cultural value', 'Reyes-García, V., Huanca, T., Vadez, V. & Leonard, W. (2006). Cultural, practical and economic value of wild plants: a quantitative study in the Bolivian Amazon. Economic Botany 60(1), 62–74.'],
            ['ICF', 'Informant consensus factor', 'Trotter, R. T. & Logan, M. H. (1986). Informant consensus. In: Plants in Indigenous Medicine and Diet. Redgrave.'],
            ['FL', 'Fidelity level', 'Friedman, J., Yaniv, Z., Dafni, A. & Palewitch, D. (1986). A preliminary classification of the healing potential of medicinal plants. Journal of Ethnopharmacology 16, 275–287.'],
            ['ethnobotanyR', 'Implementation (Whitney, C.)', 'https://CRAN.R-project.org/package=ethnobotanyR — implements these indices; definitions from the primary sources above.'],
        ];
    }

    public function prepareExport(Project $project, Request $request): Response|RedirectResponse
    {
        $this->checkPermission($project);

        $forms = $project->interviewForms()
            ->with([
                'sections' => fn ($query) => $query->orderBy('order'),
                'sections.items' => fn ($query) => $query->orderBy('order'),
            ])
            ->get();

        return Inertia::render('Data/Export', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            // Deep-link prefill (e.g. from the View page's "Customize export").
            'initial' => [
                'mode' => in_array($request->query('mode'), ['custom', 'ethnobotanyr'], true)
                    ? $request->query('mode')
                    : 'custom',
                'form' => $request->integer('form') ?: null,
                'section' => $request->integer('section') ?: null,
            ],
            'forms' => $forms->map(fn ($form) => [
                'id' => $form->id,
                'name' => $form->name,
                'sections' => $form->sections->map(fn ($section) => [
                    'id' => $section->id,
                    'name' => $section->name,
                    'repeatable' => $section->repeatable,
                    'items' => $section->items->map(fn ($item) => [
                        'id' => $item->id,
                        'label' => $item->label,
                        'type' => $item->type,
                        'link_to_species' => $item->link_to_species,
                    ])->values(),
                ])->values(),
            ])->values(),
            'csrf_token' => csrf_token(),
        ]);
    }

    public function exportPreview(
        Project $project,
        Request $request,
        InterviewDataExport $export
    ): JsonResponse {
        $this->checkPermission($project, true);

        $validated = $request->validate([
            'mode' => 'required|in:custom,ethnobotanyr',
            'form_id' => 'required|exists:interview_forms,id',
        ]);

        $form = $project->interviewForms->firstWhere('id', (int) $validated['form_id']);

        if (! $form) {
            $this->deny('El formulario no pertenece a este proyecto.', true);
        }

        if ($validated['mode'] === 'custom') {
            $ids = array_filter((array) json_decode($request->query('selected_fields', '[]'), true));

            if ($ids === []) {
                return response()->json([
                    'columns' => [],
                    'instance_count' => 0,
                    'record_count' => 0,
                    'rows' => [],
                ]);
            }

            $items = $this->customExportItems($form, $ids);
            $repeatable = $this->exportRepeatable($items);

            if ($repeatable === null) {
                return response()->json(['error' => 'mixed_repeatable'], 422);
            }

            return response()->json($export->customPreview($form, $items, $repeatable));
        }

        $field = $this->ethnobotanyField($form, (int) $request->query('field_id'));

        return response()->json($export->ethnobotanyPreview($field));
    }

    public function downloadExport(
        Project $project,
        Request $request,
        InterviewDataExport $export
    ): BinaryFileResponse|RedirectResponse {
        $this->checkPermission($project);

        $validated = $request->validate([
            'mode' => 'required|in:custom,ethnobotanyr',
            'form_id' => 'required|exists:interview_forms,id',
            'format' => 'nullable|in:xlsx,csv',
        ]);

        $format = $validated['format'] ?? 'xlsx';
        $form = $project->interviewForms->firstWhere('id', (int) $validated['form_id']);

        if (! $form) {
            $this->deny('El formulario no pertenece a este proyecto.', false);
        }

        if ($validated['mode'] === 'custom') {
            $request->validate(['selected_fields' => 'required|json']);

            $items = $this->customExportItems(
                $form,
                (array) json_decode($request->selected_fields, true)
            );
            $repeatable = $this->exportRepeatable($items);

            if ($repeatable === null) {
                return redirect()->back()->with(
                    'error',
                    'No puedes seleccionar campos de secciones repetibles y no repetibles al mismo tiempo.'
                );
            }

            $matrix = $export->customMatrix($form, $items, $repeatable);

            return Excel::download(
                new CustomExport($matrix['headers'], $matrix['rows']),
                $this->exportFilename($project, 'custom', $format),
                $this->exportWriter($format),
            );
        }

        $field = $this->ethnobotanyField($form, (int) $request->input('field_id'));
        $matrix = $export->ethnobotanyMatrix($field);

        return Excel::download(
            new EthnobotanyRExport($matrix['headers'], $matrix['rows']),
            $this->exportFilename($project, 'ethnobotanyr', $format),
            $this->exportWriter($format),
        );
    }

    /**
     * Selected custom-export fields, guarded to belong to the form, ordered by
     * section then field order.
     */
    private function customExportItems(InterviewForm $form, array $ids): Collection
    {
        $items = InterviewItem::whereIn('id', $ids)->with('section')->get();
        $sectionIds = $form->sections()->pluck('id');

        foreach ($items as $item) {
            if (! $sectionIds->contains($item->interview_section_id)) {
                $this->deny(
                    'Los campos seleccionados no pertenecen a este proyecto.',
                    request()->expectsJson()
                );
            }
        }

        return $items
            ->sortBy(fn ($item) => [$item->section->order ?? 0, $item->order ?? 0])
            ->values();
    }

    /** Shared repeatable flag of the selected fields, or null when mixed. */
    private function exportRepeatable(Collection $items): ?bool
    {
        $values = $items->map(fn ($item) => (bool) $item->section->repeatable)->unique();

        return $values->count() === 1 ? (bool) $values->first() : null;
    }

    private function ethnobotanyField(InterviewForm $form, int $fieldId): InterviewItem
    {
        $item = InterviewItem::with('section')->find($fieldId);

        if (! $item || $item->section->interview_form_id !== $form->id) {
            $this->deny('El campo no pertenece a este proyecto.', request()->expectsJson());
        }

        return $item;
    }

    private function exportFilename(Project $project, string $kind, string $format): string
    {
        return Str::slug($project->name)."-{$kind}-".now()->format('Y-m-d').".{$format}";
    }

    private function exportWriter(string $format): ?string
    {
        return $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : null;
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
