<?php

namespace App\Services;

use App\Models\Specimen;

/**
 * How a specimen reads in the interface: one row per physical collection, with
 * the current determination flattened onto it.
 *
 * Shared so a specimen looks identical on the project-wide list and under a
 * taxon. See docs/decisions/0008-specimens-and-determinations.md.
 */
class SpecimenPresenter
{
    /**
     * `species` is null for anything not yet identified — including material
     * examined without a name being reached, which the determination itself
     * distinguishes.
     *
     * @return array<string, mixed>
     */
    public function present(Specimen $specimen): array
    {
        $current = $specimen->currentDetermination;

        return [
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
            'is_determined' => $current?->catalog_species_id !== null,
            'species' => $current?->species === null ? null : [
                'id' => $current->species->id,
                'genus' => $current->species->genus,
                'name' => $current->species->name,
            ],
            'determiner' => $current?->determiner,
            'determined_on' => $current?->determined_on?->toDateString(),
            'qualifier' => $current?->qualifier,
        ];
    }

    /**
     * @param  iterable<Specimen>  $specimens
     * @return array<int, array<string, mixed>>
     */
    public function collection(iterable $specimens): array
    {
        $rows = [];

        foreach ($specimens as $specimen) {
            $rows[] = $this->present($specimen);
        }

        return $rows;
    }
}
