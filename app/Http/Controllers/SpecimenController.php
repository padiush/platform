<?php

namespace App\Http\Controllers;

use App\Models\CatalogSpecies;
use App\Models\Determination;
use App\Models\Project;
use App\Models\Specimen;
use App\Services\AccessionNumbers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Specimens recorded against a taxon in the project catalog.
 *
 * A specimen is the physical collection; the taxon is what it was identified
 * as. Creating one here also records the determination that says so, because a
 * specimen registered on a species page has, by that act, been determined as
 * that species. See docs/decisions/0008-specimens-and-determinations.md.
 */
class SpecimenController extends Controller
{
    public function __construct(private readonly AccessionNumbers $accessions) {}

    /**
     * Register a collection against a taxon.
     *
     * The accession number is either minted from the project's sequence or
     * typed in — never both. A study that already numbers its own specimens
     * enters those; one that does not lets the project issue them.
     */
    public function store(
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

        $validated = $request->validate($this->rules($project));

        DB::transaction(function () use ($request, $project, $species, $validated) {
            $specimen = new Specimen($this->specimenAttributes($validated));
            $specimen->project_id = $project->id;

            if ($request->boolean('mint_accession')) {
                $specimen->accession_number = $this->accessions->mint($project);
            }

            $specimen->save();

            // The determination this registration asserts. Current by
            // definition — it is the only one there is.
            $determination = new Determination([
                'catalog_species_id' => $species->id,
                'determiner' => $validated['determiner'] ?? null,
                'determined_on' => $validated['determined_on'] ?? null,
                'qualifier' => $validated['qualifier'] ?? null,
                'is_current' => true,
            ]);
            $determination->specimen_id = $specimen->id;
            $determination->save();
        });

        return back()
            ->with('message', 'catalogs.specimens.registered')
            ->with('message_type', 'success');
    }

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

        $validated = $request->validate($this->rules($project, $specimen));

        DB::transaction(function () use ($request, $project, $specimen, $validated) {
            $specimen->fill($this->specimenAttributes($validated));

            // Minting is additive: it never overwrites a number the specimen
            // already carries, because an accession number that has been
            // written on a label and cited elsewhere is not ours to change.
            if ($request->boolean('mint_accession') && ! $specimen->isVouchered()) {
                $specimen->accession_number = $this->accessions->mint($project);
            }

            $specimen->save();

            // The determiner and the date belong to the determination, not the
            // collection — correcting them here amends the current opinion
            // rather than recording a new one.
            $current = $specimen->currentDetermination;

            if ($current !== null) {
                $current->update([
                    'determiner' => $validated['determiner'] ?? null,
                    'determined_on' => $validated['determined_on'] ?? null,
                    'qualifier' => $validated['qualifier'] ?? null,
                ]);
            }
        });

        return back()
            ->with('message', 'catalogs.specimens.updated')
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

        // Determinations go with it (cascade); the interview answer it came
        // from does not, since deleting a specimen is not a statement about
        // what an informant said.
        $specimen->delete();

        return back()
            ->with('message', 'catalogs.specimens.deleted')
            ->with('message_type', 'success');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(Project $project, ?Specimen $specimen = null): array
    {
        return [
            'accession_number' => [
                'nullable',
                'string',
                'max:255',
                // Unique per project, not globally — two studies on one
                // instance issue their own series.
                Rule::unique('specimens', 'accession_number')
                    ->where('project_id', $project->id)
                    ->ignore($specimen?->getKey()),
            ],
            'mint_accession' => 'boolean',
            'collection_number' => 'nullable|string|max:255',
            'collector' => 'nullable|string|max:255',
            'collected_on' => 'nullable|date',
            'locality' => 'nullable|string|max:255',
            'location_lat' => 'nullable|numeric|between:-90,90',
            'location_lng' => 'nullable|numeric|between:-180,180',
            'repository' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
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

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function specimenAttributes(array $validated): array
    {
        return [
            'accession_number' => $validated['accession_number'] ?? null,
            'collection_number' => $validated['collection_number'] ?? null,
            'collector' => $validated['collector'] ?? null,
            'collected_on' => $validated['collected_on'] ?? null,
            'locality' => $validated['locality'] ?? null,
            'location_lat' => $validated['location_lat'] ?? null,
            'location_lng' => $validated['location_lng'] ?? null,
            'repository' => $validated['repository'] ?? null,
            'notes' => $validated['notes'] ?? null,
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
