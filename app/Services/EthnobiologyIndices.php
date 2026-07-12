<?php

namespace App\Services;

use App\Models\CatalogSpecies;
use App\Models\InstanceAnswer;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\Project;

/**
 * Quantitative-ethnobiology indices for a project, computed per the spec in
 * docs/analysis/ethnobotany-indices.md. The unit is the *use report* — one
 * informant citing one species for one use-category:
 *
 *   informant i = an InterviewInstance
 *   species s   = InstanceAnswer.catalog_species_id (a link_to_species answer)
 *   use u       = the answer to an is_use_category item, in the same repeatable
 *                 set (section + repeatable_index) as the linked species
 *   N           = number of instances in scope
 *
 * Answers are encrypted, so aggregation happens in PHP (as in InterviewDataTable
 * / InterviewDataExport), not in SQL. Values are returned at full precision;
 * rounding is the presentation layer's job.
 *
 * Species-level *citation* counts (FC → RFC, and I_u for Fidelity Level) come
 * from species links directly, independent of use-categories — a species linked
 * without a recorded use still counts as cited. Use reports additionally require
 * a use-category and drive UV, CI, ICF, and FL's numerator.
 */
class EthnobiologyIndices
{
    public function compute(Project $project): array
    {
        // Forms carrying a species field define the survey universe. A project
        // can also hold unrelated instruments — an interview may be recorded
        // with no species/use linking (e.g. a demographics form) — and those
        // interviews must not dilute the denominator N.
        $formIds = InterviewForm::where('project_id', $project->id)
            ->whereHas(
                'sections.items',
                fn ($query) => $query->where('link_to_species', true)
            )
            ->pluck('id');

        $n = InterviewInstance::whereIn('interview_form_id', $formIds)->count();

        $empty = [
            'informants' => $n,
            'species' => [],
            'use_categories' => [],
            'unlinked_citations' => 0,
        ];

        if ($n === 0) {
            return $empty;
        }

        $items = InterviewItem::whereHas(
            'section',
            fn ($query) => $query->whereIn('interview_form_id', $formIds)
        )
            ->where(fn ($query) => $query
                ->where('link_to_species', true)
                ->orWhere('is_use_category', true))
            ->get(['id', 'link_to_species', 'is_use_category']);

        $taxonItems = array_flip(
            $items->where('link_to_species', true)->pluck('id')->all()
        );
        $useItems = array_flip(
            $items->where('is_use_category', true)->pluck('id')->all()
        );

        $answers = InstanceAnswer::whereIn(
            'interview_item_id',
            array_keys($taxonItems + $useItems)
        )->get();

        [$speciesByInstance, $unlinked] = $this->speciesLinks($answers, $taxonItems);
        $useReports = $this->useReports($answers, $taxonItems, $useItems);

        return [
            'informants' => $n,
            'species' => $this->speciesIndices($speciesByInstance, $useReports, $n),
            'use_categories' => $this->useCategoryIndices($useReports),
            'unlinked_citations' => $unlinked,
        ];
    }

    /**
     * Distinct species cited per instance (for FC / RFC / I_u), and the count of
     * taxon answers with a folk name that was never linked to a species.
     *
     * @return array{0: array<string, array<int, true>>, 1: int}
     */
    private function speciesLinks($answers, array $taxonItems): array
    {
        $speciesByInstance = [];
        $unlinked = 0;

        foreach ($answers as $answer) {
            if (! isset($taxonItems[$answer->interview_item_id])) {
                continue;
            }

            if ($answer->catalog_species_id !== null) {
                $speciesByInstance[$answer->interview_instance_id][$answer->catalog_species_id] = true;
            } elseif (trim((string) $answer->answer) !== '') {
                $unlinked++;
            }
        }

        return [$speciesByInstance, $unlinked];
    }

    /**
     * The distinct (instance, species, use-category) use reports. Answers are
     * grouped by repeatable set (instance + section + repeatable_index); a set
     * with both a linked species and a non-empty use-category yields one report.
     *
     * @return array<string, array{instance: string, species: int, use: string}>
     */
    private function useReports($answers, array $taxonItems, array $useItems): array
    {
        $groups = [];
        foreach ($answers as $answer) {
            $key = $answer->interview_instance_id
                .'|'.$answer->interview_section_id
                .'|'.($answer->repeatable_index ?? 'null');
            $groups[$key][] = $answer;
        }

        $useReports = [];
        foreach ($groups as $group) {
            $speciesId = null;
            $useValue = null;

            foreach ($group as $answer) {
                if (
                    isset($taxonItems[$answer->interview_item_id]) &&
                    $answer->catalog_species_id !== null
                ) {
                    $speciesId = $answer->catalog_species_id;
                }

                if (isset($useItems[$answer->interview_item_id])) {
                    $value = trim((string) $answer->answer);
                    if ($value !== '') {
                        $useValue = $value;
                    }
                }
            }

            if ($speciesId !== null && $useValue !== null) {
                $instanceId = $group[0]->interview_instance_id;
                // Keyed so the same triple within one instance counts once.
                $useReports[$instanceId.'|'.$speciesId.'|'.$useValue] = [
                    'instance' => $instanceId,
                    'species' => (int) $speciesId,
                    'use' => $useValue,
                ];
            }
        }

        return $useReports;
    }

    /**
     * RFC, UV, CI, and per-use Fidelity Level for every cited species.
     */
    private function speciesIndices(array $speciesByInstance, array $useReports, int $n): array
    {
        // FC(s): distinct informants who cited (linked) species s.
        $fc = [];
        foreach ($speciesByInstance as $species) {
            foreach (array_keys($species) as $speciesId) {
                $fc[$speciesId] = ($fc[$speciesId] ?? 0) + 1;
            }
        }

        // Use-report tallies per species and per (species, use-category).
        $urBySpecies = [];
        $urBySpeciesUse = [];
        $informantsBySpeciesUse = [];
        foreach ($useReports as $report) {
            $s = $report['species'];
            $u = $report['use'];
            $urBySpecies[$s] = ($urBySpecies[$s] ?? 0) + 1;
            $urBySpeciesUse[$s][$u] = ($urBySpeciesUse[$s][$u] ?? 0) + 1;
            $informantsBySpeciesUse[$s][$u][$report['instance']] = true;
        }

        $catalog = CatalogSpecies::whereIn('id', array_keys($fc))
            ->get()
            ->keyBy('id');

        $species = [];
        foreach ($fc as $speciesId => $citations) {
            $fidelity = [];
            foreach ($urBySpeciesUse[$speciesId] ?? [] as $use => $count) {
                $ip = count($informantsBySpeciesUse[$speciesId][$use]);
                $fidelity[] = [
                    'use_category' => $use,
                    'value' => ($ip / $citations) * 100,
                ];
            }

            $model = $catalog->get($speciesId);

            $species[] = [
                'species' => [
                    'id' => $speciesId,
                    'family' => $model?->family,
                    'genus' => $model?->genus,
                    'name' => $model?->name,
                    'authority' => $model?->authority,
                ],
                'fc' => $citations,
                'rfc' => $citations / $n,
                'uv' => ($urBySpecies[$speciesId] ?? 0) / $n,
                'ci' => array_sum($urBySpeciesUse[$speciesId] ?? []) / $n,
                'fidelity' => $fidelity,
            ];
        }

        // Most-cited first; stable tiebreak on scientific name.
        usort($species, fn ($a, $b) => [$b['rfc'], $a['species']['name']]
            <=> [$a['rfc'], $b['species']['name']]);

        return $species;
    }

    /**
     * Informant Consensus Factor per use-category.
     */
    private function useCategoryIndices(array $useReports): array
    {
        $urByUse = [];
        $taxaByUse = [];
        foreach ($useReports as $report) {
            $u = $report['use'];
            $urByUse[$u] = ($urByUse[$u] ?? 0) + 1;
            $taxaByUse[$u][$report['species']] = true;
        }

        $categories = [];
        foreach ($urByUse as $use => $nUr) {
            $nTaxa = count($taxaByUse[$use]);
            $categories[] = [
                'use_category' => $use,
                'n_ur' => $nUr,
                'n_taxa' => $nTaxa,
                // Undefined for a single use report (division by zero).
                'icf' => $nUr > 1 ? ($nUr - $nTaxa) / ($nUr - 1) : null,
            ];
        }

        usort($categories, fn ($a, $b) => [$b['n_ur'], $a['use_category']]
            <=> [$a['n_ur'], $b['use_category']]);

        return $categories;
    }
}
