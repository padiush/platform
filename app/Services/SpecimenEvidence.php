<?php

namespace App\Services;

use App\Models\CollectingPermit;
use App\Models\Project;
use App\Models\Specimen;
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
class SpecimenEvidence
{
    /** Several specimens can back one taxon; they read as one cell. */
    private const JOIN = '; ';

    /**
     * @return array{
     *     by_taxon: array<int, array{vouchers: string, permits: string, specimens: int}>,
     *     coverage: array<string, int>
     * }
     */
    public function forProject(Project $project): array
    {
        $specimens = Specimen::query()
            ->where('project_id', $project->getKey())
            ->with(['currentDetermination', 'collectingPermit'])
            ->get();

        return [
            'by_taxon' => $this->byTaxon($specimens),
            'coverage' => $this->coverage($project, $specimens),
        ];
    }

    /**
     * Keyed by the taxon each specimen is *currently* determined as. Specimens
     * with no determination, or an indeterminate one, belong to no taxon and so
     * appear nowhere here — they are counted in coverage instead.
     *
     * @param  Collection<int, Specimen>  $specimens
     * @return array<int, array{vouchers: string, permits: string, specimens: int}>
     */
    private function byTaxon(Collection $specimens): array
    {
        $rows = [];

        foreach ($specimens as $specimen) {
            $taxonId = $specimen->currentDetermination?->catalog_species_id;

            if ($taxonId === null) {
                continue;
            }

            $rows[$taxonId] ??= ['vouchers' => [], 'permits' => [], 'specimens' => 0];
            $rows[$taxonId]['specimens']++;

            if ($specimen->isVouchered()) {
                $rows[$taxonId]['vouchers'][] = $specimen->accession_number;
            }

            $permit = $specimen->collectingPermit;

            if ($permit instanceof CollectingPermit) {
                $rows[$taxonId]['permits'][$permit->getKey()] = $permit->label();
            }
        }

        return array_map(fn (array $row) => [
            'vouchers' => implode(self::JOIN, $row['vouchers']),
            'permits' => implode(self::JOIN, array_values($row['permits'])),
            'specimens' => $row['specimens'],
        ], $rows);
    }

    /**
     * Figures a paper can print. The three permit states are counted apart
     * because an exemption is an answer and a blank is not.
     *
     * @param  Collection<int, Specimen>  $specimens
     * @return array<string, int>
     */
    private function coverage(Project $project, Collection $specimens): array
    {
        $vouchered = $specimens->filter->isVouchered();

        $taxaWithVoucher = $vouchered
            ->map(fn (Specimen $s) => $s->currentDetermination?->catalog_species_id)
            ->filter()
            ->unique();

        return [
            'taxa_total' => $project->catalogSpecies()->count(),
            'taxa_vouchered' => $taxaWithVoucher->count(),
            'specimens_total' => $specimens->count(),
            'specimens_vouchered' => $vouchered->count(),
            'specimens_under_permit' => $specimens
                ->filter(fn (Specimen $s) => $s->collecting_permit_id !== null)
                ->count(),
            'specimens_permit_exempt' => $specimens->filter->isPermitExempt()->count(),
            'specimens_permit_unrecorded' => $specimens
                ->reject(fn (Specimen $s) => $s->permitIsAccountedFor())
                ->count(),
        ];
    }
}
