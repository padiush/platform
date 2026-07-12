<?php

namespace App\Services;

use App\Models\CatalogSpecies;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Builds the paginated species list for a catalog with search, filters and
 * ordering.
 *
 * Structural filters (family, genus, link status) and ordering are portable
 * SQL that runs on both MariaDB (dev) and sqlite (tests). Search is the only
 * in-memory step: linked interview names live in the encrypted
 * InstanceAnswer.answer column (unsearchable in SQL) and matching is fuzzy and
 * accent-insensitive, so a search term forces candidates to be scored in PHP.
 * That cost is only paid when a term is present; the common no-search case
 * paginates in SQL.
 */
class CatalogSpeciesSearch
{
    public const PER_PAGE = 20;

    /** Whitelisted ordering keys. */
    public const SORTS = ['family', 'linked'];

    /** Whitelisted link-status filters. */
    public const LINK_STATUSES = ['all', 'linked', 'unlinked'];

    /**
     * A token must reach this similarity to a haystack word to count as a
     * fuzzy (typo) match, so unrelated words don't leak into results.
     */
    private const FUZZY_FLOOR = 0.72;

    /**
     * @param  array{q?: ?string, family?: ?string, genus?: ?string, link?: ?string, sort?: ?string}  $filters
     */
    public function paginate(Project $project, array $filters, int $page): LengthAwarePaginator
    {
        $query = CatalogSpecies::query()
            ->withCount('answers')
            ->where('project_id', $project->id);

        $this->applyFamily($query, $filters['family'] ?? null);
        $this->applyGenus($query, $filters['genus'] ?? null);
        $this->applyLinkStatus($query, $filters['link'] ?? 'all');

        $sort = in_array($filters['sort'] ?? null, self::SORTS, true)
            ? $filters['sort']
            : 'family';

        $term = trim((string) ($filters['q'] ?? ''));

        if ($term === '') {
            $this->applySort($query, $sort);

            return $query->paginate(self::PER_PAGE)->withQueryString();
        }

        return $this->searchInMemory($query, $term, $sort, $page);
    }

    private function applyFamily(Builder $query, ?string $family): void
    {
        if (filled($family)) {
            $query->where('family', $family);
        }
    }

    private function applyGenus(Builder $query, ?string $genus): void
    {
        if (filled($genus)) {
            $query->where('genus', $genus);
        }
    }

    private function applyLinkStatus(Builder $query, ?string $status): void
    {
        match ($status) {
            'linked' => $query->has('answers'),
            'unlinked' => $query->doesntHave('answers'),
            default => null,
        };
    }

    private function applySort(Builder $query, string $sort): void
    {
        if ($sort === 'linked') {
            $query->orderByDesc('answers_count');
        }

        $query->orderBy('family')->orderBy('genus')->orderBy('name');
    }

    private function searchInMemory(
        Builder $query,
        string $term,
        string $sort,
        int $page
    ): LengthAwarePaginator {
        $needle = $this->tokens($this->normalize($term));

        $candidates = $query
            ->with(['answers' => fn ($q) => $q->select('id', 'catalog_species_id', 'answer')])
            ->get();

        $scored = $candidates
            ->map(fn (CatalogSpecies $sp) => [
                'species' => $sp,
                'score' => $this->score($sp, $needle),
            ])
            ->filter(fn (array $row) => $row['score'] > 0);

        $ordered = $this->sortScored($scored, $sort)
            ->map(fn (array $row) => $row['species'])
            ->values();

        return $this->paginateCollection($ordered, $page);
    }

    /**
     * Order by relevance first, then break ties with the chosen ordering so
     * equally-relevant hits still respect the user's sort selection.
     */
    private function sortScored(Collection $scored, string $sort): Collection
    {
        return $scored
            ->sort(function (array $a, array $b) use ($sort) {
                if ($a['score'] !== $b['score']) {
                    return $b['score'] <=> $a['score'];
                }

                $sa = $a['species'];
                $sb = $b['species'];

                if ($sort === 'linked' && $sa->answers_count !== $sb->answers_count) {
                    return $sb->answers_count <=> $sa->answers_count;
                }

                return [$sa->family, $sa->genus, $sa->name]
                    <=> [$sb->family, $sb->genus, $sb->name];
            })
            ->values();
    }

    /**
     * Score a species against the query tokens. Every token must match
     * something (AND semantics) or the species scores 0. Scientific fields
     * outweigh linked interview names on ties.
     *
     * @param  list<string>  $needle
     */
    private function score(CatalogSpecies $sp, array $needle): float
    {
        if ($needle === []) {
            return 0.0;
        }

        $haystacks = [[
            $this->normalize(trim("{$sp->genus} {$sp->name} {$sp->family} {$sp->authority}")),
            1.0,
        ]];

        foreach ($sp->answers as $answer) {
            $text = $this->normalize((string) $answer->answer);

            if ($text !== '') {
                $haystacks[] = [$text, 0.9];
            }
        }

        $total = 0.0;

        foreach ($needle as $token) {
            $best = 0.0;

            foreach ($haystacks as [$hay, $weight]) {
                $best = max($best, $this->tokenScore($hay, $token) * $weight);
            }

            if ($best <= 0.0) {
                return 0.0;
            }

            $total += $best;
        }

        return $total / count($needle);
    }

    private function tokenScore(string $hay, string $token): float
    {
        if ($hay === '' || $token === '') {
            return 0.0;
        }

        if (str_contains($hay, $token)) {
            foreach ($this->tokens($hay) as $word) {
                if ($word === $token) {
                    return 1.0;
                }

                if (str_starts_with($word, $token)) {
                    return 0.95;
                }
            }

            return 0.85;
        }

        $best = 0.0;

        foreach ($this->tokens($hay) as $word) {
            $best = max($best, $this->ratio($token, $word));
        }

        return $best >= self::FUZZY_FLOOR ? $best * 0.8 : 0.0;
    }

    private function ratio(string $a, string $b): float
    {
        $max = max(strlen($a), strlen($b));

        // levenshtein() only accepts strings up to 255 bytes; tokens are short
        // but guard so a pathological answer word can't raise a warning.
        if ($max === 0 || strlen($a) > 255 || strlen($b) > 255) {
            return 0.0;
        }

        return 1 - (levenshtein($a, $b) / $max);
    }

    private function normalize(string $value): string
    {
        return Str::lower(Str::ascii($value));
    }

    /**
     * @return list<string>
     */
    private function tokens(string $value): array
    {
        return array_values(array_filter(preg_split('/\s+/', trim($value))));
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
