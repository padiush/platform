<?php

namespace App\Services;

use App\Models\CatalogSpecies;
use App\Models\InstanceAnswer;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Builds the paginated list for the species-linking table: one row per
 * species-linkable answer, optionally grouped by the reported (vernacular)
 * name so recurring names can be linked in one action.
 *
 * The reported name lives in the encrypted InstanceAnswer.answer column, so it
 * cannot be grouped, searched, or DISTINCT-ed in SQL — those steps run in PHP
 * after Eloquent decrypts each row. Status (linked/unlinked) is the plain
 * catalog_species_id, so it filters in SQL. Everything loads from one
 * eager-loaded query and paginates in memory, mirroring CatalogSpeciesSearch.
 */
class SpeciesLinkingList
{
    public const PER_PAGE = 20;

    /** Whitelisted status filters. */
    public const STATUSES = ['all', 'linked', 'unlinked'];

    /**
     * @param  array{q?: ?string, status?: ?string, group?: ?bool}  $filters
     */
    public function paginate(Project $project, array $filters, int $page): LengthAwarePaginator
    {
        $status = in_array($filters['status'] ?? null, self::STATUSES, true)
            ? $filters['status']
            : 'all';

        $query = $project->speciesAnswers()
            ->with(['species', 'instance.user', 'section', 'item']);

        if ($status === 'linked') {
            $query->whereNotNull('catalog_species_id');
        } elseif ($status === 'unlinked') {
            $query->whereNull('catalog_species_id');
        }

        $rows = $query->get()->map(fn (InstanceAnswer $answer) => $this->buildRow($answer));

        $term = trim((string) ($filters['q'] ?? ''));

        if ($term !== '') {
            $needle = $this->normalize($term);
            $rows = $rows->filter(
                fn (array $row) => str_contains($this->normalize($row['reported_name']), $needle)
            );
        }

        $group = ($filters['group'] ?? true) !== false;

        $items = $group
            ? $this->groupByName($rows)
            : $this->sortRows($rows);

        return $this->paginateCollection($items->values(), $page);
    }

    private function buildRow(InstanceAnswer $answer): array
    {
        return [
            'key' => sprintf(
                '%s-%s-%s-%s',
                $answer->interview_instance_id,
                $answer->interview_section_id,
                $answer->repeatable_index ?? '',
                $answer->interview_item_id,
            ),
            'instance_id' => $answer->interview_instance_id,
            'section_id' => $answer->interview_section_id,
            'repeatable_index' => $answer->repeatable_index,
            'item_id' => $answer->interview_item_id,
            'reported_name' => (string) $answer->answer,
            'section_name' => $answer->section?->name,
            'interview' => [
                'recorded_at' => $answer->instance?->created_at?->toIso8601String(),
                'recorder' => $answer->instance?->user?->name,
            ],
            'species' => $this->presentSpecies($answer->species),
            'linked' => $answer->catalog_species_id !== null,
        ];
    }

    private function presentSpecies(?CatalogSpecies $species): ?array
    {
        if ($species === null) {
            return null;
        }

        return [
            'id' => $species->id,
            'family' => $species->family,
            'genus' => $species->genus,
            'name' => $species->name,
            'authority' => $species->authority,
        ];
    }

    /**
     * Collapse rows that share a reported name (accent/case-insensitive) into
     * groups. Incomplete groups (some members still unlinked) sort first so the
     * remaining work surfaces at the top.
     */
    private function groupByName(Collection $rows): Collection
    {
        return $rows
            ->groupBy(fn (array $row) => $this->normalize($row['reported_name']))
            ->map(function (Collection $members, string $key) {
                $species = $members->pluck('species')->filter()->values();
                $distinctIds = $species->pluck('id')->unique();
                $linkedCount = $members->where('linked', true)->count();

                return [
                    'key' => $key,
                    'name' => $members->first()['reported_name'],
                    'total' => $members->count(),
                    'linked_count' => $linkedCount,
                    'mixed' => $distinctIds->count() > 1,
                    'species' => $distinctIds->count() === 1 ? $species->first() : null,
                    'members' => $members->values()->all(),
                ];
            })
            ->sortBy(fn (array $group) => [
                $group['linked_count'] >= $group['total'] ? 1 : 0,
                $this->normalize($group['name']),
            ])
            ->values();
    }

    private function sortRows(Collection $rows): Collection
    {
        return $rows
            ->sortBy(fn (array $row) => [
                $row['linked'] ? 1 : 0,
                $this->normalize($row['reported_name']),
                $row['interview']['recorded_at'] ?? '',
            ])
            ->values();
    }

    private function normalize(string $value): string
    {
        return Str::lower(Str::ascii($value));
    }

    private function paginateCollection(Collection $items, int $page): LengthAwarePaginator
    {
        $page = max(1, $page);

        return new Paginator(
            $items->forPage($page, self::PER_PAGE)->values(),
            $items->count(),
            self::PER_PAGE,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );
    }
}
