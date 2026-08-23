<?php

namespace App\Services;

use App\Models\FieldRecord;

/**
 * How a fieldRecord reads in the interface: one row per physical collection, with
 * the current determination flattened onto it.
 *
 * Shared so a fieldRecord looks identical on the project-wide list and under a
 * taxon. See docs/decisions/0008-specimens-and-determinations.md.
 */
class FieldRecordPresenter
{
    /**
     * `species` is null for anything not yet identified — including material
     * examined without a name being reached, which the determination itself
     * distinguishes.
     *
     * @return array<string, mixed>
     */
    public function present(FieldRecord $fieldRecord): array
    {
        $current = $fieldRecord->currentDetermination;

        return [
            'id' => $fieldRecord->id,
            'basis_of_record' => $fieldRecord->basis_of_record,
            'was_collected' => $fieldRecord->wasCollected(),
            'vernacular_name' => $fieldRecord->vernacular_name,
            'accession_number' => $fieldRecord->accession_number,
            'collection_number' => $fieldRecord->collection_number,
            'collector' => $fieldRecord->collector,
            'collected_on' => $fieldRecord->collected_on?->toDateString(),
            'locality' => $fieldRecord->locality,
            'location_lat' => $fieldRecord->location_lat,
            'location_lng' => $fieldRecord->location_lng,
            'repository' => $fieldRecord->repository,
            'collecting_permit_id' => $fieldRecord->collecting_permit_id,
            'permit' => $fieldRecord->collectingPermit?->label(),
            'permit_exemption' => $fieldRecord->permit_exemption,
            'notes' => $fieldRecord->notes,
            'is_vouchered' => $fieldRecord->isVouchered(),
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
     * @param  iterable<FieldRecord>  $fieldRecords
     * @return array<int, array<string, mixed>>
     */
    public function collection(iterable $fieldRecords): array
    {
        $rows = [];

        foreach ($fieldRecords as $fieldRecord) {
            $rows[] = $this->present($fieldRecord);
        }

        return $rows;
    }
}
