<?php

namespace App\Services;

use App\Models\CatalogSpecies;
use App\Models\InstanceAnswer;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Assembles the on-platform data view for one section of a form: a paginated
 * table of answers and per-field summary distributions for charts.
 *
 * A section is the unit because repeatable and non-repeatable sections have
 * incompatible row grains (one row per repeatable record vs. one per
 * interview). Answers live in the encrypted InstanceAnswer.answer column, so
 * values can't be filtered/aggregated in SQL — but the table paginates by
 * interview/record (structural columns, in SQL) and only decrypts the rows on
 * the current page. Summaries decrypt a section's answers in PHP and are only
 * computed when the Summary tab asks for them.
 */
class InterviewDataTable
{
    public const PER_PAGE = 20;

    /** Categories shown per categorical chart (by descending count). */
    private const TOP_N = 12;

    /** Histogram buckets for numeric fields. */
    private const BINS = 8;

    /**
     * @param  array{interviewer?: ?int, from?: ?string, to?: ?string}  $filters
     */
    public function rows(
        InterviewForm $form,
        InterviewSection $section,
        array $filters,
        int $page
    ): LengthAwarePaginator {
        $items = $section->items()->orderBy('order')->get();

        // Instances of the form, filtered on structural columns (SQL only).
        $instances = InterviewInstance::query()
            ->where('interview_form_id', $form->id)
            ->with('user')
            ->when(
                filled($filters['interviewer'] ?? null),
                fn ($q) => $q->where('user_id', $filters['interviewer'])
            )
            ->when(
                filled($filters['from'] ?? null),
                fn ($q) => $q->whereDate('created_at', '>=', $filters['from'])
            )
            ->when(
                filled($filters['to'] ?? null),
                fn ($q) => $q->whereDate('created_at', '<=', $filters['to'])
            )
            ->orderByDesc('created_at')
            ->get();

        $records = $this->buildRecords($section, $instances);

        $page = max(1, $page);
        $pageRecords = $records->forPage($page, self::PER_PAGE)->values();

        return new Paginator(
            $this->buildRows($section, $items, $pageRecords),
            $records->count(),
            self::PER_PAGE,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );
    }

    /**
     * One record per (instance, repeatable_index) that has answers in this
     * section — derived from structural columns, so no decryption yet.
     */
    private function buildRecords(InterviewSection $section, Collection $instances): Collection
    {
        $keys = InstanceAnswer::query()
            ->where('interview_section_id', $section->id)
            ->whereIn('interview_instance_id', $instances->pluck('id'))
            ->select('interview_instance_id', 'repeatable_index')
            ->distinct()
            ->get()
            ->groupBy('interview_instance_id');

        $records = collect();

        foreach ($instances as $instance) {
            $rows = $keys[$instance->id] ?? collect();

            if ($rows->isEmpty()) {
                continue;
            }

            if ($section->repeatable) {
                $rows->pluck('repeatable_index')
                    ->map(fn ($i) => $i ?? 0)
                    ->unique()
                    ->sort()
                    ->each(fn ($index) => $records->push([
                        'instance' => $instance,
                        'index' => $index,
                    ]));
            } else {
                $records->push(['instance' => $instance, 'index' => null]);
            }
        }

        return $records;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildRows(
        InterviewSection $section,
        Collection $items,
        Collection $records
    ): array {
        if ($records->isEmpty()) {
            return [];
        }

        $answers = InstanceAnswer::query()
            ->where('interview_section_id', $section->id)
            ->whereIn('interview_instance_id', $records->pluck('instance.id')->unique())
            ->with('species')
            ->get();

        $byKey = [];
        foreach ($answers as $answer) {
            $index = $section->repeatable ? ($answer->repeatable_index ?? 0) : null;
            $byKey["{$answer->interview_instance_id}|{$index}|{$answer->interview_item_id}"] = $answer;
        }

        return $records->map(function (array $record) use ($items, $byKey) {
            $instance = $record['instance'];
            $index = $record['index'];

            $cells = [];
            foreach ($items as $item) {
                $answer = $byKey["{$instance->id}|{$index}|{$item->id}"] ?? null;
                $cells[$item->id] = $this->cellValue($item, $answer);
            }

            return [
                'key' => "{$instance->id}-".($index ?? ''),
                'instance_id' => $instance->id,
                'record_index' => $index,
                'interview' => [
                    'recorded_at' => $instance->created_at?->toIso8601String(),
                    'recorder' => $instance->user?->name,
                ],
                'cells' => $cells,
            ];
        })->all();
    }

    private function cellValue(InterviewItem $item, ?InstanceAnswer $answer): ?array
    {
        if ($answer === null) {
            return null;
        }

        if ($item->link_to_species && $answer->species) {
            $species = $answer->species;

            return [
                'kind' => 'species',
                'value' => trim("{$species->genus} {$species->name}"),
            ];
        }

        $raw = $answer->answer;

        if ($raw === null || $raw === '') {
            return null;
        }

        if ($item->type === 'multi') {
            $decoded = json_decode($raw, true);
            $values = is_array($decoded) ? $decoded : [];

            return $values === [] ? null : ['kind' => 'multi', 'values' => $values];
        }

        return ['kind' => 'text', 'value' => (string) $raw];
    }

    /**
     * Per-field distributions for the Summary tab.
     *
     * @return array<int, array<string, mixed>>
     */
    public function summary(InterviewForm $form, InterviewSection $section): array
    {
        return $section->items()
            ->orderBy('order')
            ->get()
            ->map(fn (InterviewItem $item) => $this->fieldSummary($item))
            ->all();
    }

    private function fieldSummary(InterviewItem $item): array
    {
        $base = ['item_id' => $item->id, 'label' => $item->label];

        if ($item->link_to_species) {
            return $base + ['kind' => 'species', 'data' => $this->speciesSummary($item)];
        }

        $values = InstanceAnswer::query()
            ->where('interview_item_id', $item->id)
            ->get()
            ->pluck('answer')
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->values();

        return match ($item->type) {
            'number' => $base + ['kind' => 'number'] + $this->numericSummary($values),
            'date' => $base + ['kind' => 'date', 'data' => $this->dateSummary($values)],
            'multi' => $base + ['kind' => 'categorical', 'data' => $this->multiSummary($values)],
            default => $base + ['kind' => 'categorical', 'data' => $this->categoricalSummary($values)],
        };
    }

    /**
     * catalog_species_id isn't encrypted, so citations group in SQL.
     */
    private function speciesSummary(InterviewItem $item): array
    {
        $counts = InstanceAnswer::query()
            ->where('interview_item_id', $item->id)
            ->whereNotNull('catalog_species_id')
            ->selectRaw('catalog_species_id, COUNT(*) as total')
            ->groupBy('catalog_species_id')
            ->pluck('total', 'catalog_species_id');

        $names = CatalogSpecies::whereIn('id', $counts->keys())
            ->get()
            ->mapWithKeys(fn ($s) => [$s->id => trim("{$s->genus} {$s->name}")]);

        return $counts
            ->map(fn ($total, $id) => ['label' => $names[$id] ?? '—', 'count' => $total])
            ->sortByDesc('count')
            ->take(self::TOP_N)
            ->values()
            ->all();
    }

    private function categoricalSummary(Collection $values): array
    {
        return $this->topN($values->countBy(fn ($v) => (string) $v));
    }

    private function multiSummary(Collection $values): array
    {
        $counts = collect();

        foreach ($values as $raw) {
            $decoded = json_decode($raw, true);

            if (is_array($decoded)) {
                foreach ($decoded as $value) {
                    $counts[(string) $value] = ($counts[(string) $value] ?? 0) + 1;
                }
            }
        }

        return $this->topN($counts);
    }

    private function topN(Collection $counts): array
    {
        return $counts
            ->sortDesc()
            ->take(self::TOP_N)
            ->map(fn ($count, $label) => ['label' => (string) $label, 'count' => $count])
            ->values()
            ->all();
    }

    private function numericSummary(Collection $values): array
    {
        $numbers = $values
            ->map(fn ($v) => is_numeric($v) ? (float) $v : null)
            ->filter(fn ($v) => $v !== null)
            ->sort()
            ->values();

        if ($numbers->isEmpty()) {
            return ['data' => [], 'stats' => null];
        }

        $count = $numbers->count();
        $min = $numbers->first();
        $max = $numbers->last();
        $median = $count % 2
            ? $numbers[intdiv($count, 2)]
            : ($numbers[$count / 2 - 1] + $numbers[$count / 2]) / 2;

        return [
            'data' => $this->histogram($numbers, $min, $max),
            'stats' => [
                'count' => $count,
                'min' => $min,
                'max' => $max,
                'mean' => round($numbers->avg(), 2),
                'median' => round($median, 2),
            ],
        ];
    }

    private function histogram(Collection $numbers, float $min, float $max): array
    {
        if ($min === $max) {
            return [['label' => (string) $min, 'count' => $numbers->count()]];
        }

        $width = ($max - $min) / self::BINS;
        $buckets = array_fill(0, self::BINS, 0);

        foreach ($numbers as $value) {
            $bucket = (int) min(self::BINS - 1, floor(($value - $min) / $width));
            $buckets[$bucket]++;
        }

        $data = [];
        for ($i = 0; $i < self::BINS; $i++) {
            $lo = round($min + $i * $width, 1);
            $hi = round($min + ($i + 1) * $width, 1);
            $data[] = ['label' => "{$lo}–{$hi}", 'count' => $buckets[$i]];
        }

        return $data;
    }

    private function dateSummary(Collection $values): array
    {
        $counts = collect();

        foreach ($values as $raw) {
            try {
                $month = Carbon::parse($raw)->format('Y-m');
            } catch (\Throwable) {
                continue;
            }

            $counts[$month] = ($counts[$month] ?? 0) + 1;
        }

        return $counts
            ->sortKeys()
            ->map(fn ($count, $month) => ['label' => $month, 'count' => $count])
            ->values()
            ->all();
    }
}
