<?php

namespace App\Services;

use App\Models\CollectingPermit;
use App\Models\FieldRecord;
use App\Models\Project;
use Illuminate\Support\Collection;

/**
 * The physical evidence behind a project's taxa: which voucher backs each one,
 * under what authority it was collected, and how much of that is on record.
 *
 * Reporting conventions in quantitative ethnobotany identify the voucher behind
 * each taxon, so a species table that cannot carry one is incomplete. Coverage
 * is stated rather than enforced, following the pattern the indices
 * specification already sets for unlinked answers — a researcher needs to know
 * the denominator.
 *
 * See docs/decisions/0008-specimens-and-determinations.md and
 * docs/decisions/0009-collecting-permits.md.
 */
class FieldRecordEvidence
{
    /** Several fieldRecords can back one taxon; they read as one cell. */
    private const JOIN = '; ';

    /**
     * @return array{
     *     by_taxon: array<int, array{vouchers: string, permits: string, fieldRecords: int}>,
     *     coverage: array<string, int>
     * }
     */
    public function forProject(Project $project): array
    {
        $fieldRecords = FieldRecord::query()
            ->where('project_id', $project->getKey())
            ->with(['currentDetermination', 'collectingPermit'])
            ->get();

        return [
            'by_taxon' => $this->byTaxon($fieldRecords),
            'coverage' => $this->coverage($project, $fieldRecords),
        ];
    }

    /**
     * Keyed by the taxon each fieldRecord is *currently* determined as. FieldRecords
     * with no determination, or an indeterminate one, belong to no taxon and so
     * appear nowhere here — they are counted in coverage instead.
     *
     * @param  Collection<int, FieldRecord>  $fieldRecords
     * @return array<int, array{vouchers: string, permits: string, fieldRecords: int}>
     */
    private function byTaxon(Collection $fieldRecords): array
    {
        $rows = [];

        foreach ($fieldRecords as $fieldRecord) {
            $taxonId = $fieldRecord->currentDetermination?->catalog_species_id;

            if ($taxonId === null) {
                continue;
            }

            $rows[$taxonId] ??= ['vouchers' => [], 'permits' => [], 'fieldRecords' => 0];
            $rows[$taxonId]['fieldRecords']++;

            if ($fieldRecord->isVouchered()) {
                $rows[$taxonId]['vouchers'][] = $fieldRecord->accession_number;
            }

            $permit = $fieldRecord->collectingPermit;

            if ($permit instanceof CollectingPermit) {
                $rows[$taxonId]['permits'][$permit->getKey()] = $permit->label();
            }
        }

        return array_map(fn (array $row) => [
            'vouchers' => implode(self::JOIN, $row['vouchers']),
            'permits' => implode(self::JOIN, array_values($row['permits'])),
            'fieldRecords' => $row['fieldRecords'],
        ], $rows);
    }

    /**
     * Figures a paper can print. The three permit states are counted apart
     * because an exemption is an answer and a blank is not.
     *
     * @param  Collection<int, FieldRecord>  $fieldRecords
     * @return array<string, int>
     */
    private function coverage(Project $project, Collection $fieldRecords): array
    {
        $vouchered = $fieldRecords->filter->isVouchered();

        $taxaWithVoucher = $vouchered
            ->map(fn (FieldRecord $s) => $s->currentDetermination?->catalog_species_id)
            ->filter()
            ->unique();

        // A voucher figure over records that could never carry one is not a
        // data-quality signal, so observations are counted beside it rather
        // than inside it.
        $collected = $fieldRecords->filter->wasCollected();

        return [
            'taxa_total' => $project->catalogSpecies()->count(),
            'taxa_vouchered' => $taxaWithVoucher->count(),
            'records_total' => $fieldRecords->count(),
            'records_collected' => $collected->count(),
            'records_observed' => $fieldRecords->count() - $collected->count(),
            'records_vouchered' => $vouchered->count(),
            'records_under_permit' => $fieldRecords
                ->filter(fn (FieldRecord $record) => $record->collecting_permit_id !== null)
                ->count(),
            'records_permit_exempt' => $fieldRecords->filter->isPermitExempt()->count(),
            // Only what was collected can want a permit; nothing was taken
            // from an observation.
            'records_permit_unrecorded' => $collected
                ->reject(fn (FieldRecord $record) => $record->permitIsAccountedFor())
                ->count(),
        ];
    }
}
