<?php

namespace App\Http\Controllers;

use App\Exports\SpecimensExport;
use App\Models\CatalogSpecies;
use App\Models\Determination;
use App\Models\Project;
use App\Models\Specimen;
use App\Services\AccessionNumbers;
use App\Services\SpecimenPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The physical collections a project has made.
 *
 * The order here follows the field, not the database: a specimen is collected
 * and recorded first, identified later — often by someone else — and deposited
 * against a voucher later still. So a specimen is created carrying no
 * determination at all, and `determine()` and `deposit()` are separate acts
 * performed when there is something to say.
 *
 * See docs/decisions/0008-specimens-and-determinations.md.
 */
class SpecimenController extends Controller
{
    /** Lawful collections that fall outside the permit regime. */
    public const EXEMPTIONS = ['private_land', 'cultivated', 'market', 'other'];

    public function __construct(
        private readonly AccessionNumbers $accessions,
        private readonly SpecimenPresenter $presenter,
    ) {}

    /** Every collection in the project, identified or not. */
    public function index(Request $request, Project $project): Response|RedirectResponse
    {
        $user = Auth::user();

        if (! $user->can('viewCatalog', $project)) {
            return redirect()
                ->route('catalogs.index')
                ->with('message', 'catalogs.no_access')
                ->with('message_type', 'error');
        }

        $specimens = $project->specimens()
            ->with(['currentDetermination.species', 'collectingPermit'])
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Catalog/Specimens', [
            'project' => ['id' => $project->id, 'name' => $project->name],
            'specimens' => $this->presenter->collection($specimens),
            'summary' => [
                'total' => $specimens->count(),
                'vouchered' => $specimens->filter->isVouchered()->count(),
                // Everything still lacking a taxon: never examined, and
                // examined without a name reached. The table keeps those two
                // apart — they mean different things — but as a work queue
                // they are one number, and it matches the list's filter.
                'unidentified' => $specimens->filter(
                    fn (Specimen $s) => $s->currentDetermination?->species === null
                )->count(),
            ],
            // The catalog to identify against. Small enough to send whole, and
            // sending it avoids leaning on the data-linking search endpoint,
            // which is gated on a different capability.
            'catalog' => $project->catalogSpecies()
                ->orderBy('genus')
                ->orderBy('name')
                ->get(['id', 'family', 'genus', 'name', 'authority'])
                ->all(),
            'permits' => $project->collectingPermits()
                ->orderBy('authority')
                ->orderBy('reference')
                ->get()
                ->map(fn ($permit) => [
                    'id' => $permit->id,
                    'label' => $permit->label(),
                ])
                ->all(),
            'exemptions' => self::EXEMPTIONS,
            'canEdit' => (bool) $user->can('editCatalog', $project),
            'nextAccessionNumber' => $this->accessions->peek($project),
            // The species tab is a dead end without one — catalogs.show
            // redirects away from an empty catalog.
            'speciesCount' => $project->catalogSpecies()->count(),
        ]);
    }

    /**
     * The collection list as a spreadsheet.
     *
     * Gated on viewCatalog, the same capability that shows the page: a specimen
     * record holds no informant response, so exporting it is not a wider
     * disclosure than reading it on screen.
     */
    public function export(Request $request, Project $project): BinaryFileResponse|RedirectResponse
    {
        if (! Auth::user()->can('viewCatalog', $project)) {
            return redirect()
                ->route('catalogs.index')
                ->with('message', 'catalogs.no_access')
                ->with('message_type', 'error');
        }

        // Defaults to xlsx, matching the indices download — a bare link from
        // either place should behave the same way.
        $format = $request->query('format') === 'csv' ? 'csv' : 'xlsx';

        $rows = $project->specimens()
            ->with(['currentDetermination.species', 'collectingPermit'])
            ->orderBy('accession_number')
            ->orderBy('collection_number')
            ->get()
            ->map(function (Specimen $specimen) {
                $determination = $specimen->currentDetermination;
                $species = $determination?->species;
                $permit = $specimen->collectingPermit;

                return [
                    $specimen->accession_number,
                    $specimen->collection_number,
                    $specimen->collector,
                    $specimen->collected_on?->toDateString(),
                    $specimen->locality,
                    $specimen->location_lat,
                    $specimen->location_lng,
                    $specimen->repository,
                    $species?->family,
                    $species?->genus,
                    $species?->name,
                    $determination?->qualifier,
                    $determination?->determiner,
                    $determination?->determined_on?->toDateString(),
                    $permit?->authority,
                    $permit?->reference,
                    $specimen->permit_exemption,
                    $specimen->notes,
                ];
            })
            ->all();

        $filename = Str::slug($project->name).'-specimens-'.now()->format('Y-m-d').".{$format}";

        return Excel::download(
            new SpecimensExport($rows),
            $filename,
            $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : null
        );
    }

    /**
     * Record a collection, with no claim about what it is.
     *
     * No determination row is written: nobody has looked at it yet, and an
     * empty determination would assert that someone had and failed. The
     * specimen simply has no current determination until `determine()` says
     * otherwise.
     */
    public function store(Request $request, Project $project): RedirectResponse
    {
        if ($denied = $this->denyUnlessEditable($project)) {
            return $denied;
        }

        $validated = $request->validate($this->collectionRules($project));

        $specimen = new Specimen($validated);
        $specimen->project_id = $project->id;
        $specimen->save();

        return back()
            ->with('message', 'catalogs.specimens.registered')
            ->with('message_type', 'success');
    }

    /**
     * Record a collection already known to be a given taxon — the shortcut from
     * a species page, where the identification is the reason you are there.
     */
    public function storeForSpecies(
        Request $request,
        Project $project,
        CatalogSpecies $species
    ): RedirectResponse {
        if ($denied = $this->denyUnlessEditable($project)) {
            return $denied;
        }

        if ($species->project_id !== $project->id) {
            return $this->speciesNotFound();
        }

        $validated = $request->validate($this->collectionRules($project) + $this->determinationRules());

        DB::transaction(function () use ($project, $species, $validated) {
            $specimen = new Specimen($validated);
            $specimen->project_id = $project->id;
            $specimen->save();

            $this->recordDetermination($specimen, $species->id, $validated);
        });

        return back()
            ->with('message', 'catalogs.specimens.registered')
            ->with('message_type', 'success');
    }

    /** Correct the collection itself — who collected it, where, and when. */
    public function update(
        Request $request,
        Project $project,
        Specimen $specimen
    ): RedirectResponse {
        if ($denied = $this->denyUnlessEditable($project)) {
            return $denied;
        }

        if ($specimen->project_id !== $project->id) {
            return $this->specimenNotFound();
        }

        $specimen->update($request->validate($this->collectionRules($project)));

        return back()
            ->with('message', 'catalogs.specimens.updated')
            ->with('message_type', 'success');
    }

    /**
     * Identify the specimen, or revise an existing identification.
     *
     * The previous determination is superseded rather than replaced — the whole
     * reason determinations are a table is that what was thought before is part
     * of the record. A null taxon is legitimate: it says someone examined this
     * and could not name it, which is different from nobody having looked.
     */
    public function determine(
        Request $request,
        Project $project,
        Specimen $specimen
    ): RedirectResponse {
        if ($denied = $this->denyUnlessEditable($project)) {
            return $denied;
        }

        if ($specimen->project_id !== $project->id) {
            return $this->specimenNotFound();
        }

        $validated = $request->validate(
            $this->determinationRules() + [
                'catalog_species_id' => [
                    'nullable',
                    Rule::exists('catalog_species', 'id')
                        ->where('project_id', $project->id),
                ],
            ]
        );

        DB::transaction(function () use ($specimen, $validated) {
            $specimen->determinations()
                ->where('is_current', true)
                ->update(['is_current' => false]);

            $this->recordDetermination(
                $specimen,
                $validated['catalog_species_id'] ?? null,
                $validated
            );
        });

        return back()
            ->with('message', 'catalogs.specimens.determined')
            ->with('message_type', 'success');
    }

    /**
     * Record the deposit: where the specimen went and under what number.
     *
     * Separate from collection because it happens later, and often not at all.
     * The number is either issued from the project's sequence or typed in —
     * a study with its own numbering keeps it.
     */
    public function deposit(
        Request $request,
        Project $project,
        Specimen $specimen
    ): RedirectResponse {
        if ($denied = $this->denyUnlessEditable($project)) {
            return $denied;
        }

        if ($specimen->project_id !== $project->id) {
            return $this->specimenNotFound();
        }

        $validated = $request->validate([
            'accession_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('specimens', 'accession_number')
                    ->where('project_id', $project->id)
                    ->ignore($specimen->getKey()),
            ],
            'mint_accession' => 'boolean',
            'repository' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request, $project, $specimen, $validated) {
            $specimen->repository = $validated['repository'] ?? null;

            // Minting is additive. An accession number already written on a
            // label and cited elsewhere is not ours to change.
            if ($request->boolean('mint_accession') && ! $specimen->isVouchered()) {
                $specimen->accession_number = $this->accessions->mint($project);
            } elseif (! $request->boolean('mint_accession')) {
                $specimen->accession_number = $validated['accession_number'] ?? null;
            }

            $specimen->save();
        });

        return back()
            ->with('message', 'catalogs.specimens.deposited')
            ->with('message_type', 'success');
    }

    public function destroy(Project $project, Specimen $specimen): RedirectResponse
    {
        if ($denied = $this->denyUnlessEditable($project)) {
            return $denied;
        }

        if ($specimen->project_id !== $project->id) {
            return $this->specimenNotFound();
        }

        $specimen->delete();

        return back()
            ->with('message', 'catalogs.specimens.deleted')
            ->with('message_type', 'success');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function recordDetermination(
        Specimen $specimen,
        ?int $speciesId,
        array $validated
    ): void {
        $determination = new Determination([
            'catalog_species_id' => $speciesId,
            'determiner' => $validated['determiner'] ?? null,
            'determined_on' => $validated['determined_on'] ?? null,
            'qualifier' => $validated['qualifier'] ?? null,
            'is_current' => true,
        ]);
        $determination->specimen_id = $specimen->id;
        $determination->save();
    }

    /**
     * What is known at collection time, and nothing else.
     *
     * @return array<string, mixed>
     */
    private function collectionRules(Project $project): array
    {
        return [
            'collection_number' => 'nullable|string|max:255',
            'collector' => 'nullable|string|max:255',
            'collected_on' => 'nullable|date',
            'locality' => 'nullable|string|max:255',
            'location_lat' => 'nullable|numeric|between:-90,90',
            'location_lng' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string',
            // A permit is held before the fieldwork, so it is known when the
            // collection is recorded — unlike a voucher, which is not.
            'collecting_permit_id' => [
                'nullable',
                'prohibits:permit_exemption',
                Rule::exists('collecting_permits', 'id')
                    ->where('project_id', $project->getKey()),
            ],
            // Or a stated reason none was needed. Never both: the pairing has
            // no meaning.
            'permit_exemption' => [
                'nullable',
                'prohibits:collecting_permit_id',
                Rule::in(self::EXEMPTIONS),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function determinationRules(): array
    {
        return [
            'determiner' => 'nullable|string|max:255',
            'determined_on' => 'nullable|date',
            'qualifier' => [
                'nullable',
                Rule::in([
                    Determination::QUALIFIER_CF,
                    Determination::QUALIFIER_AFF,
                    Determination::QUALIFIER_SP,
                ]),
            ],
        ];
    }

    private function denyUnlessEditable(Project $project): ?RedirectResponse
    {
        if (Auth::user()->can('editCatalog', $project)) {
            return null;
        }

        return redirect()
            ->route('catalogs.index')
            ->with('message', 'catalogs.no_access')
            ->with('message_type', 'error');
    }

    private function speciesNotFound(): RedirectResponse
    {
        return redirect()
            ->route('catalogs.index')
            ->with('message', 'catalogs.species_not_found')
            ->with('message_type', 'error');
    }

    private function specimenNotFound(): RedirectResponse
    {
        return redirect()
            ->route('catalogs.index')
            ->with('message', 'catalogs.specimens.not_found')
            ->with('message_type', 'error');
    }
}
