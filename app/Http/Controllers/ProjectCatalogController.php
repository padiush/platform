<?php

namespace App\Http\Controllers;

use App\Models\CatalogSpecies;
use App\Models\InstanceAnswer;
use App\Models\Project;
use App\Models\Specimen;
use App\Services\AccessionNumbers;
use App\Services\CatalogSpeciesSearch;
use App\Services\GbifDistribution;
use App\Services\INaturalistPhoto;
use App\Services\WfoNameResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Throwable;

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
            // Provenance when the entry was prefilled from a WFO name.
            'wfo_id' => 'nullable|string|max:255',
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
            'metadata' => $request->filled('wfo_id')
                ? ['wfo' => ['id' => $request->wfo_id, 'based_at' => now()->toIso8601String()]]
                : null,
        ]);

        return redirect()
            ->route('catalogs.index')
            ->with('message', 'catalogs.species_registered')
            ->with('message_type', 'success');
    }

    /**
     * Free-text WFO name search used to prefill the registration form from a
     * recognised source instead of hand-typing (and mistyping) the taxonomy.
     */
    public function searchWfoNames(
        Request $request,
        Project $project,
        WfoNameResolver $resolver
    ): JsonResponse {
        if (! Auth::user()->can('editCatalog', $project)) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        try {
            $results = $resolver->search($validated['q']);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['error' => 'wfo_unreachable'], 502);
        }

        return response()->json(['results' => $results]);
    }

    /**
     * Resolves a chosen WFO name to the catalog fields the registration form
     * prefills (family, genus, epithet, authority).
     */
    public function resolveWfoName(
        Request $request,
        Project $project,
        WfoNameResolver $resolver
    ): JsonResponse {
        if (! Auth::user()->can('editCatalog', $project)) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $validated = $request->validate([
            'wfo_id' => ['required', 'string', 'max:255'],
        ]);

        try {
            $name = $resolver->fetchName($validated['wfo_id']);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['error' => 'wfo_unreachable'], 502);
        }

        if ($name === null) {
            return response()->json(['error' => 'name_not_found'], 404);
        }

        return response()->json([
            'wfo_id' => $name['wfo_id'],
            'name_plain' => $name['full_name_plain'],
        ] + $name['apply']);
    }

    /**
     * Attribution for a species' iNaturalist reference photo, shown as a
     * visual-confirmation aid during registration. The photo itself is served by
     * the proxy below; this returns only the credit (photographer + platform).
     */
    public function inaturalistInfo(
        Request $request,
        Project $project,
        INaturalistPhoto $inaturalist
    ): JsonResponse {
        if (! Auth::user()->can('viewCatalog', $project)) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        try {
            $photo = $inaturalist->forName($validated['name']);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['error' => 'inaturalist_unreachable'], 502);
        }

        if ($photo === null) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'attribution' => $photo['attribution'],
            'license' => $photo['license'],
            'source' => INaturalistPhoto::SOURCE_LABEL,
            'page_url' => $photo['page_url'],
        ]);
    }

    /**
     * Streams a species' iNaturalist photo through our origin so the browser
     * never contacts a third party directly (no IP leak) — and we never store
     * it. Only known iNaturalist photo hosts are fetched (SSRF guard).
     */
    public function inaturalistPhoto(
        Request $request,
        Project $project,
        INaturalistPhoto $inaturalist
    ): HttpResponse {
        abort_unless(Auth::user()->can('viewCatalog', $project), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        // Resolve the photo URL and fetch the bytes in a try (network can fail);
        // keep the abort()s outside it so they aren't caught and masked as 502.
        try {
            $photo = $inaturalist->forName($validated['name']);
            $image = ($photo !== null && $inaturalist->isAllowedPhotoUrl($photo['photo_url']))
                ? Http::timeout(12)->get($photo['photo_url'])
                : null;
        } catch (Throwable $e) {
            report($e);
            abort(502);
        }

        abort_if($photo === null || $image === null || $image->failed(), 404);

        return response(
            $image->body(),
            200,
            [
                'Content-Type' => $image->header('Content-Type') ?: 'image/jpeg',
                // Browser-side cache only; the origin never persists the bytes.
                'Cache-Control' => 'private, max-age=86400',
            ]
        );
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
            'canEdit' => (bool) $user->can('editCatalog', $project),
            // Cached range only (no external call on page load); the page fetches
            // it on demand when absent.
            'distribution' => $species->metadata['distribution'] ?? null,
            'specimens' => $this->specimensFor($species),
            // What the project would issue next, so the form can show it before
            // the researcher commits to it. Peeking does not consume a number.
            'nextAccessionNumber' => app(AccessionNumbers::class)->peek($project),
        ]);
    }

    /**
     * The collections currently determined as this taxon, newest first.
     *
     * The determiner and qualifier live on the determination rather than the
     * specimen, so they are flattened here — the page shows one row per
     * physical collection, not per opinion about it.
     *
     * @return array<int, array<string, mixed>>
     */
    private function specimensFor(CatalogSpecies $species): array
    {
        return $species->specimens()
            ->with('currentDetermination')
            ->orderByDesc('specimens.created_at')
            ->get()
            ->map(fn (Specimen $specimen) => [
                'id' => $specimen->id,
                'accession_number' => $specimen->accession_number,
                'collection_number' => $specimen->collection_number,
                'collector' => $specimen->collector,
                'collected_on' => $specimen->collected_on?->toDateString(),
                'locality' => $specimen->locality,
                'location_lat' => $specimen->location_lat,
                'location_lng' => $specimen->location_lng,
                'repository' => $specimen->repository,
                'notes' => $specimen->notes,
                'is_vouchered' => $specimen->isVouchered(),
                'determiner' => $specimen->currentDetermination?->determiner,
                'determined_on' => $specimen->currentDetermination?->determined_on?->toDateString(),
                'qualifier' => $specimen->currentDetermination?->qualifier,
            ])
            ->all();
    }

    /**
     * Fetches the species' geographic range from WCVP (via GBIF) and caches it in
     * the entry's metadata, so subsequent page views read it without a round trip.
     */
    public function fetchDistribution(
        Project $project,
        CatalogSpecies $species,
        GbifDistribution $gbif
    ): JsonResponse {
        if (! Auth::user()->can('viewCatalog', $project)) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        if ($species->project_id !== $project->id) {
            return response()->json(['error' => 'not_found'], 404);
        }

        try {
            $distribution = $gbif->forSpecies(
                $species->genus,
                $species->name,
                $species->authority,
            ) + ['fetched_at' => now()->toIso8601String()];
        } catch (Throwable $e) {
            report($e);

            return response()->json(['error' => 'gbif_unreachable'], 502);
        }

        $metadata = $species->metadata ?? [];
        $metadata['distribution'] = $distribution;
        $species->update(['metadata' => $metadata]);

        return response()->json($distribution);
    }

    /**
     * Previews the catalog fields a WFO name would apply, without saving, so the
     * species page can show a before/after before the user commits.
     */
    public function previewWfoName(
        Request $request,
        Project $project,
        CatalogSpecies $species,
        WfoNameResolver $resolver
    ): JsonResponse {
        if (! Auth::user()->can('editCatalog', $project)) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        if ($species->project_id !== $project->id) {
            return response()->json(['error' => 'not_found'], 404);
        }

        $validated = $request->validate([
            'wfo_id' => ['required', 'string', 'max:255'],
            'use_accepted' => ['sometimes', 'boolean'],
        ]);

        try {
            $proposed = $this->proposedTaxonomy(
                $resolver,
                $validated['wfo_id'],
                $request->boolean('use_accepted')
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json(['error' => 'wfo_unreachable'], 502);
        }

        if ($proposed === null) {
            return response()->json(['error' => 'name_not_found'], 404);
        }

        return response()->json([
            'current' => [
                'family' => $species->family,
                'genus' => $species->genus,
                'name' => $species->name,
                'authority' => $species->authority,
            ],
            'proposed' => $proposed,
        ]);
    }

    /**
     * Adopts a WFO name: re-resolves it (never trusting client-sent taxonomy)
     * and overwrites the entry's family, genus, epithet and authority, recording
     * the WFO provenance in metadata.
     */
    public function updateSpecies(
        Request $request,
        Project $project,
        CatalogSpecies $species,
        WfoNameResolver $resolver
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

        $validated = $request->validate([
            'wfo_id' => ['required', 'string', 'max:255'],
            'use_accepted' => ['sometimes', 'boolean'],
        ]);

        try {
            $proposed = $this->proposedTaxonomy(
                $resolver,
                $validated['wfo_id'],
                $request->boolean('use_accepted')
            );
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('catalogs.species.show', [
                    'project' => $project->id,
                    'species' => $species->id,
                ])
                ->with('message', 'catalogs.accept.wfo_error')
                ->with('message_type', 'error');
        }

        if ($proposed === null) {
            return redirect()
                ->route('catalogs.species.show', [
                    'project' => $project->id,
                    'species' => $species->id,
                ])
                ->with('message', 'catalogs.accept.name_not_found')
                ->with('message_type', 'error');
        }

        $metadata = $species->metadata ?? [];
        $metadata['wfo'] = [
            'id' => $proposed['wfo_id'],
            'name' => $proposed['applied_name'],
            'accepted_at' => now()->toIso8601String(),
        ];

        $species->update([
            'family' => $proposed['family'],
            'genus' => $proposed['genus'],
            'name' => $proposed['name'],
            'authority' => $proposed['authority'],
            'metadata' => $metadata,
        ]);

        return redirect()
            ->route('catalogs.species.show', [
                'project' => $project->id,
                'species' => $species->id,
            ])
            ->with('message', 'catalogs.accept.updated')
            ->with('message_type', 'success');
    }

    /**
     * Resolves a WFO name id to the catalog fields to apply. When $useAccepted
     * and the name is a synonym, adopts its accepted name instead.
     *
     * @return array{wfo_id: string, applied_name: string, family: ?string, genus: string, name: string, authority: ?string}|null
     */
    private function proposedTaxonomy(
        WfoNameResolver $resolver,
        string $wfoId,
        bool $useAccepted
    ): ?array {
        $name = $resolver->fetchName($wfoId);

        if ($name === null) {
            return null;
        }

        $source = ($useAccepted && $name['accepted'] !== null)
            ? $name['accepted']
            : $name;

        return [
            'wfo_id' => $source['wfo_id'] ?? $wfoId,
            'applied_name' => $source['full_name_plain'],
            ...$source['apply'],
        ];
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
