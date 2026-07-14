<?php

namespace App\Http\Controllers;

use App\Models\CatalogSpecies;
use App\Models\InstanceAnswer;
use App\Models\Project;
use App\Services\CatalogSpeciesSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProjectCatalogController extends Controller
{
    /** Linked interview records shown per page on the species page. */
    private const LINKED_PER_PAGE = 15;

    public function index(): Response|RedirectResponse
    {
        $accesses = Auth::user()
            ->projectAccesses()
            ->with([
                'capability',
                'project' => fn ($query) => $query->withCount('catalogSpecies'),
            ])
            ->get();

        $projects = collect();

        foreach ($accesses as $access) {
            $project = $access->project;

            if (! $project) {
                continue;
            }

            if ($access->capability->view_catalog) {
                $projects->push([
                    'id' => $project->id,
                    'name' => $project->name,
                    'catalog_species_count' => $project->catalog_species_count,
                    'linked_species_count' => $project
                        ->linkedSpecies()
                        ->count(),
                    'linked_families_count' => $project
                        ->linkedFamilies()
                        ->count(),
                    'can_edit_catalog' => (bool) $access->capability->edit_catalog,
                    'can_view_catalog' => true, // already verified
                ]);
            }
        }

        return Inertia::render('Catalog/Index', [
            'projects' => $projects,
        ]);
    }

    public function registerSpecies(Project $project): RedirectResponse
    {
        if (! Auth::user()->can('editCatalog', $project)) {
            return redirect()
                ->route('catalogs.index')
                ->with('message', 'catalogs.no_access')
                ->with('message_type', 'error');
        }

        // The register form is a modal on the catalog hub; deep-link opens it
        // there, carrying which project it belongs to.
        return redirect()->route('catalogs.index', ['create' => $project->id]);
    }

    public function storeSpecies(
        Request $request,
        Project $project
    ): RedirectResponse {
        $request->validate([
            'family' => 'nullable|string',
            'genus' => 'required|string',
            'name' => 'required|string',
            'authority' => 'nullable|string',
        ]);

        if (! Auth::user()->can('editCatalog', $project)) {
            return redirect()
                ->route('catalogs.index')
                ->with('message', 'catalogs.no_access')
                ->with('message_type', 'error');
        }

        CatalogSpecies::create([
            'project_id' => $project->id,
            'family' => $request->family,
            'genus' => $request->genus,
            'name' => $request->name,
            'authority' => $request->authority,
        ]);

        return redirect()
            ->route('catalogs.index')
            ->with('message', 'catalogs.species_registered')
            ->with('message_type', 'success');
    }

    public function show(
        Request $request,
        Project $project,
        CatalogSpeciesSearch $search
    ): Response|RedirectResponse {
        if (! Auth::user()->can('viewCatalog', $project)) {
            return redirect()
                ->route('catalogs.index')
                ->with('error', 'No tienes permisos para ver este catálogo.');
        }

        // Only bounce when the catalog is genuinely empty. An empty search or
        // filter result stays on the page and shows an in-place empty state.
        $catalogIsEmpty = ! CatalogSpecies::where('project_id', $project->id)->exists();

        if ($catalogIsEmpty) {
            return redirect()
                ->route('catalogs.index')
                ->with('error', 'Este catálogo no tiene especies registradas.');
        }

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'family' => (string) $request->query('family', ''),
            'genus' => (string) $request->query('genus', ''),
            'link' => in_array($request->query('link'), CatalogSpeciesSearch::LINK_STATUSES, true)
                ? $request->query('link')
                : 'all',
            'sort' => in_array($request->query('sort'), CatalogSpeciesSearch::SORTS, true)
                ? $request->query('sort')
                : 'family',
        ];

        $species = $search
            ->paginate($project, $filters, (int) $request->integer('page', 1))
            ->through(fn ($sp) => [
                'id' => $sp->id,
                'family' => $sp->family,
                'genus' => $sp->genus,
                'name' => $sp->name,
                'authority' => $sp->authority,
                'answers' => [
                    'length' => $sp->answers_count,
                ],
            ]);

        return Inertia::render('Catalog/SpeciesIndex', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'species' => $species,
            'filters' => $filters,
            // Dropdown data doesn't change with the filters, so skip it on the
            // partial reloads that search/filter changes trigger.
            'families' => fn () => $this->projectFamilies($project),
            'genera' => fn () => $this->projectGenera($project),
        ]);
    }

    /**
     * Distinct, sorted family names for the project's family filter dropdown.
     */
    private function projectFamilies(Project $project): array
    {
        return CatalogSpecies::where('project_id', $project->id)
            ->whereNotNull('family')
            ->where('family', '!=', '')
            ->distinct()
            ->orderBy('family')
            ->pluck('family')
            ->values()
            ->all();
    }

    /**
     * Distinct {family, genus} pairs so the genus dropdown can depend on the
     * selected family on the client.
     *
     * @return array<int, array{family: ?string, genus: string}>
     */
    private function projectGenera(Project $project): array
    {
        return CatalogSpecies::where('project_id', $project->id)
            ->whereNotNull('genus')
            ->where('genus', '!=', '')
            ->select('family', 'genus')
            ->distinct()
            ->orderBy('genus')
            ->get()
            ->map(fn ($row) => [
                'family' => $row->family,
                'genus' => $row->genus,
            ])
            ->all();
    }

    public function showSpecies(
        Request $request,
        Project $project,
        CatalogSpecies $species
    ): Response|RedirectResponse {
        $user = Auth::user();

        if (! $user->can('viewCatalog', $project)) {
            return redirect()
                ->route('catalogs.index')
                ->with('error', 'No tienes permisos para ver este catálogo.');
        }

        if ($species->project_id !== $project->id) {
            return redirect()
                ->route('catalogs.index')
                ->with('message', 'catalogs.species_not_found')
                ->with('message_type', 'error');
        }

        // Linked answers are interview data: everyone with view_catalog sees the
        // count, but the per-interview breakdown is gated behind the same
        // capability that guards the data views.
        $canViewData = $user->can('manageData', $project)
            || $user->can('generateReports', $project);

        return Inertia::render('Catalog/SpeciesShow', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'species' => [
                'id' => $species->id,
                'family' => $species->family,
                'genus' => $species->genus,
                'name' => $species->name,
                'authority' => $species->authority,
            ],
            'linkedCount' => $species->answers()->count(),
            'canViewData' => $canViewData,
            'linkedRecords' => $canViewData
                ? $this->linkedRecords($species, (int) $request->integer('page', 1))
                : null,
        ]);
    }

    /**
     * The interview records whose species-linked answers point at this species:
     * the recorded name, when and by whom it was recorded, and where it lives, so
     * the entry can be opened in the data view. Most recent first.
     */
    private function linkedRecords(CatalogSpecies $species, int $page): LengthAwarePaginator
    {
        return InstanceAnswer::query()
            ->where('instance_answers.catalog_species_id', $species->id)
            ->join(
                'interview_instances',
                'interview_instances.id',
                '=',
                'instance_answers.interview_instance_id'
            )
            ->with([
                'instance.user:id,name',
                'instance.form:id,name',
                'section:id,name',
            ])
            ->orderByDesc('interview_instances.created_at')
            ->select('instance_answers.*')
            ->paginate(self::LINKED_PER_PAGE, ['*'], 'page', max(1, $page))
            ->through(fn (InstanceAnswer $answer) => [
                'id' => $answer->id,
                'recorded_name' => $this->recordedName($answer, $species),
                'recorded_at' => $answer->instance?->created_at?->toIso8601String(),
                'recorder' => $answer->instance?->user?->name,
                'form' => [
                    'id' => $answer->instance?->interview_form_id,
                    'name' => $answer->instance?->form?->name,
                ],
                'section' => [
                    'id' => $answer->interview_section_id,
                    'name' => $answer->section?->name,
                ],
            ]);
    }

    /**
     * The name as recorded in the interview (the encrypted answer text), falling
     * back to the species binomial when the linked answer carried no free text.
     */
    private function recordedName(InstanceAnswer $answer, CatalogSpecies $species): string
    {
        $recorded = trim((string) $answer->answer);

        return $recorded !== ''
            ? $recorded
            : trim("{$species->genus} {$species->name}");
    }

    public function destroySpecies(
        Project $project,
        CatalogSpecies $species
    ): RedirectResponse {
        if (! Auth::user()->can('editCatalog', $project)) {
            return redirect()
                ->route('catalogs.index')
                ->with('message', 'catalogs.no_access')
                ->with('message_type', 'error');
        }

        if ($species->project_id !== $project->id) {
            return redirect()
                ->route('catalogs.index')
                ->with('message', 'catalogs.species_not_found')
                ->with('message_type', 'error');
        }

        foreach ($species->photos as $photo) {
            $photo->delete();
        }

        foreach ($species->answers as $answer) {
            $answer->catalog_species_id = null;
            $answer->save();
        }

        $species->delete();

        return redirect()
            ->route('catalogs.show', ['project' => $project->id])
            ->with('message', 'catalogs.species_deleted')
            ->with('message_type', 'success');
    }
}
