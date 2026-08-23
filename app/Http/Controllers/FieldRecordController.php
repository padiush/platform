<?php

namespace App\Http\Controllers;

use App\Exports\FieldRecordsExport;
use App\Models\CatalogSpecies;
use App\Models\Determination;
use App\Models\FieldRecord;
use App\Models\Project;
use App\Services\AccessionNumbers;
use App\Services\FieldRecordPresenter;
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
 * The order here follows the field, not the database: a fieldRecord is collected
 * and recorded first, identified later — often by someone else — and deposited
 * against a voucher later still. So a fieldRecord is created carrying no
 * determination at all, and `determine()` and `deposit()` are separate acts
 * performed when there is something to say.
 *
 * See docs/decisions/0008-specimens-and-determinations.md.
 */
class FieldRecordController extends Controller
{
    /** Lawful collections that fall outside the permit regime. */
    public const EXEMPTIONS = ['private_land', 'cultivated', 'market', 'other'];

    public function __construct(
        private readonly AccessionNumbers $accessions,
        private readonly FieldRecordPresenter $presenter,
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

        $fieldRecords = $project->fieldRecords()
            ->with(['currentDetermination.species', 'collectingPermit', 'media'])
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Catalog/FieldRecords', [
            'project' => ['id' => $project->id, 'name' => $project->name],
            'fieldRecords' => $this->presenter->collection($fieldRecords),
            'summary' => [
                'total' => $fieldRecords->count(),
                'vouchered' => $fieldRecords->filter->isVouchered()->count(),
                // Everything still lacking a taxon: never examined, and
                // examined without a name reached. The table keeps those two
                // apart — they mean different things — but as a work queue
                // they are one number, and it matches the list's filter.
                // Documented without anything being taken. Counted so an
                // inventory walk does not read as missing vouchers.
                'observed' => $fieldRecords->reject->wasCollected()->count(),
                'unidentified' => $fieldRecords->filter(
                    fn (FieldRecord $s) => $s->currentDetermination?->species === null
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
            'bases' => FieldRecord::BASES,
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
     * Gated on viewCatalog, the same capability that shows the page: a fieldRecord
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

        $rows = $project->fieldRecords()
            ->with(['currentDetermination.species', 'collectingPermit', 'media'])
            ->orderBy('accession_number')
            ->orderBy('collection_number')
            ->get()
            ->map(function (FieldRecord $fieldRecord) {
                $determination = $fieldRecord->currentDetermination;
                $species = $determination?->species;
                $permit = $fieldRecord->collectingPermit;

                return [
                    $fieldRecord->accession_number,
                    $fieldRecord->collection_number,
                    $fieldRecord->collector,
                    $fieldRecord->collected_on?->toDateString(),
                    $fieldRecord->locality,
                    $fieldRecord->location_lat,
                    $fieldRecord->location_lng,
                    $fieldRecord->repository,
                    $species?->family,
                    $species?->genus,
                    $species?->name,
                    $determination?->qualifier,
                    $determination?->determiner,
                    $determination?->determined_on?->toDateString(),
                    $permit?->authority,
                    $permit?->reference,
                    $fieldRecord->permit_exemption,
                    $fieldRecord->notes,
                ];
            })
            ->all();

        $filename = Str::slug($project->name).'-fieldRecords-'.now()->format('Y-m-d').".{$format}";

        return Excel::download(
            new FieldRecordsExport($rows),
            $filename,
            $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : null
        );
    }

    /**
     * Record a collection, with no claim about what it is.
     *
     * No determination row is written: nobody has looked at it yet, and an
     * empty determination would assert that someone had and failed. The
     * fieldRecord simply has no current determination until `determine()` says
     * otherwise.
     */
    public function store(Request $request, Project $project): RedirectResponse
    {
        if ($denied = $this->denyUnlessEditable($project)) {
            return $denied;
        }

        $validated = $request->validate($this->collectionRules($project));

        $fieldRecord = new FieldRecord($validated);
        $fieldRecord->project_id = $project->id;
        $fieldRecord->save();

        return back()
            ->with('message', 'catalogs.fieldRecords.registered')
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
            $fieldRecord = new FieldRecord($validated);
            $fieldRecord->project_id = $project->id;
            $fieldRecord->save();

            $this->recordDetermination($fieldRecord, $species->id, $validated);
        });

        return back()
            ->with('message', 'catalogs.fieldRecords.registered')
            ->with('message_type', 'success');
    }

    /** Correct the collection itself — who collected it, where, and when. */
    public function update(
        Request $request,
        Project $project,
        FieldRecord $fieldRecord
    ): RedirectResponse {
        if ($denied = $this->denyUnlessEditable($project)) {
            return $denied;
        }

        if ($fieldRecord->project_id !== $project->id) {
            return $this->fieldRecordNotFound();
        }

        $fieldRecord->update($request->validate($this->collectionRules($project)));

        return back()
            ->with('message', 'catalogs.fieldRecords.updated')
            ->with('message_type', 'success');
    }

    /**
     * Identify the fieldRecord, or revise an existing identification.
     *
     * The previous determination is superseded rather than replaced — the whole
     * reason determinations are a table is that what was thought before is part
     * of the record. A null taxon is legitimate: it says someone examined this
     * and could not name it, which is different from nobody having looked.
     */
    public function determine(
        Request $request,
        Project $project,
        FieldRecord $fieldRecord
    ): RedirectResponse {
        if ($denied = $this->denyUnlessEditable($project)) {
            return $denied;
        }

        if ($fieldRecord->project_id !== $project->id) {
            return $this->fieldRecordNotFound();
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

        DB::transaction(function () use ($fieldRecord, $validated) {
            $fieldRecord->determinations()
                ->where('is_current', true)
                ->update(['is_current' => false]);

            $this->recordDetermination(
                $fieldRecord,
                $validated['catalog_species_id'] ?? null,
                $validated
            );
        });

        return back()
            ->with('message', 'catalogs.fieldRecords.determined')
            ->with('message_type', 'success');
    }

    /**
     * Record the deposit: where the fieldRecord went and under what number.
     *
     * Separate from collection because it happens later, and often not at all.
     * The number is either issued from the project's sequence or typed in —
     * a study with its own numbering keeps it.
     */
    public function deposit(
        Request $request,
        Project $project,
        FieldRecord $fieldRecord
    ): RedirectResponse {
        if ($denied = $this->denyUnlessEditable($project)) {
            return $denied;
        }

        if ($fieldRecord->project_id !== $project->id) {
            return $this->fieldRecordNotFound();
        }

        if ($fieldRecord->basis_of_record === FieldRecord::BASIS_OBSERVATION) {
            return back()
                ->with('message', 'catalogs.fieldRecords.cannot_deposit_observation')
                ->with('message_type', 'error');
        }

        $validated = $request->validate([
            'accession_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('field_records', 'accession_number')
                    ->where('project_id', $project->id)
                    ->ignore($fieldRecord->getKey()),
            ],
            'mint_accession' => 'boolean',
            'repository' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request, $project, $fieldRecord, $validated) {
            $fieldRecord->repository = $validated['repository'] ?? null;

            // Minting is additive. An accession number already written on a
            // label and cited elsewhere is not ours to change.
            if ($request->boolean('mint_accession') && ! $fieldRecord->isVouchered()) {
                $fieldRecord->accession_number = $this->accessions->mint($project);
            } elseif (! $request->boolean('mint_accession')) {
                $fieldRecord->accession_number = $validated['accession_number'] ?? null;
            }

            $fieldRecord->save();
        });

        return back()
            ->with('message', 'catalogs.fieldRecords.deposited')
            ->with('message_type', 'success');
    }

    public function destroy(Project $project, FieldRecord $fieldRecord): RedirectResponse
    {
        if ($denied = $this->denyUnlessEditable($project)) {
            return $denied;
        }

        if ($fieldRecord->project_id !== $project->id) {
            return $this->fieldRecordNotFound();
        }

        $fieldRecord->delete();

        return back()
            ->with('message', 'catalogs.fieldRecords.deleted')
            ->with('message_type', 'success');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function recordDetermination(
        FieldRecord $fieldRecord,
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
        $determination->field_record_id = $fieldRecord->id;
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
            // What kind of encounter this was. Defaults to a collection, which
            // is what everything recorded before this existed.
            'basis_of_record' => ['nullable', Rule::in(FieldRecord::BASES)],
            // What an informant called it. Encrypted at rest by the model.
            'vernacular_name' => 'nullable|string|max:255',
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

    private function fieldRecordNotFound(): RedirectResponse
    {
        return redirect()
            ->route('catalogs.index')
            ->with('message', 'catalogs.fieldRecords.not_found')
            ->with('message_type', 'error');
    }
}
