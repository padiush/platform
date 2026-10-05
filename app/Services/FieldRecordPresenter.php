<?php

namespace App\Services;

use App\Models\FieldRecord;
use App\Models\Media;

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
            // Streamed through an authorized route rather than a public or
            // signed URL, so losing access to the project loses access to the
            // photographs at the same moment.
            //
            // Stored files only. A device registers an upload before sending
            // the bytes, and one interrupted in between leaves a pending row
            // with nothing behind it — shown, it would be a broken tile.
            'media' => $fieldRecord->media
                ->where('status', Media::STATUS_STORED)
                ->values()
                ->map(fn ($medium) => [
                    'id' => $medium->id,
                    'kind' => $medium->kind,
                    'content_type' => $medium->content_type,
                    'url' => route('catalogs.fieldRecords.media.show', [
                        'project' => $fieldRecord->project_id,
                        'fieldRecord' => $fieldRecord->id,
                        'medium' => $medium->id,
                    ]),
                ])->all(),
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
            'interview' => $this->interview($fieldRecord),
        ];
    }

    /**
     * The interview answer the record came out of, if it came out of one:
     * which interview, and the question that was being answered.
     *
     * The answer itself is left out. It is an informant's response, which the
     * catalog's readers are not otherwise shown, and what it named is already
     * on the record as its local name. Whether the interview can be opened is
     * the page's to say, from the reader's own access.
     *
     * Expects `answer.item` loaded; null when the answer is gone, which
     * releases the record rather than deleting it.
     *
     * @return array{instance_id: string, question: string|null}|null
     */
    private function interview(FieldRecord $fieldRecord): ?array
    {
        $answer = $fieldRecord->answer;

        if ($answer === null) {
            return null;
        }

        return [
            'instance_id' => $answer->interview_instance_id,
            'question' => $answer->item?->label,
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
