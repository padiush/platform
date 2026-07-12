<?php

namespace App\Services;

use App\Models\InstanceAnswer;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Support\Spreadsheet;
use Illuminate\Support\Collection;

/**
 * Builds the tabular export matrices (custom field selection and the
 * EthnobotanyR species × use-category matrix), shared by the download and the
 * on-screen preview so what you see is exactly what downloads.
 *
 * Every row emits one cell per column — including an empty cell for an
 * unanswered field — so columns never shift (the old Blade emitted a <td> only
 * when an answer existed). Cells pass through Spreadsheet::safe for CSV/XLSX
 * formula-injection safety. Repeatable records are derived from the selected
 * fields' own answers, not the instance's whole answer set.
 */
class InterviewDataExport
{
    public const PREVIEW_LIMIT = 10;

    /** Sentinel-prefixed instance id, matching the historical export format. */
    private function informant(string $instanceId): string
    {
        return "PADIUSH_INST_{$instanceId}";
    }

    /**
     * @return array{headers: list<string>, rows: list<list<string>>}
     */
    public function customMatrix(InterviewForm $form, Collection $items, bool $repeatable): array
    {
        $records = collect($this->records($items, $repeatable));

        return [
            'headers' => $this->customHeaders($items),
            'rows' => $this->customRows($items, $repeatable, $records),
        ];
    }

    /**
     * @return array{columns: list<string>, instance_count: int, record_count: int, rows: list<list<string>>}
     */
    public function customPreview(
        InterviewForm $form,
        Collection $items,
        bool $repeatable,
        int $limit = self::PREVIEW_LIMIT
    ): array {
        $records = collect($this->records($items, $repeatable));

        return [
            'columns' => $this->customHeaders($items),
            'instance_count' => $records->pluck('instance_id')->unique()->count(),
            'record_count' => $records->count(),
            'rows' => $this->customRows($items, $repeatable, $records->take($limit)),
        ];
    }

    /**
     * @return list<string>
     */
    private function customHeaders(Collection $items): array
    {
        return array_merge(
            ['Entrevista'],
            $items->map(fn (InterviewItem $item) => Spreadsheet::safe($item->label))->all(),
        );
    }

    /**
     * Distinct records (instance, repeatable_index) across the selected fields'
     * answers, ordered by interview date. Reads structural columns only, so no
     * decryption happens here.
     *
     * @return list<array{instance_id: string, index: ?int}>
     */
    private function records(Collection $items, bool $repeatable): array
    {
        $keys = InstanceAnswer::query()
            ->whereIn('interview_item_id', $items->pluck('id'))
            ->get(['interview_instance_id', 'repeatable_index']);

        $orderedInstanceIds = InterviewInstance::query()
            ->whereIn('id', $keys->pluck('interview_instance_id')->unique())
            ->orderBy('created_at')
            ->pluck('id');

        $byInstance = $keys->groupBy('interview_instance_id');
        $records = [];

        foreach ($orderedInstanceIds as $instanceId) {
            if ($repeatable) {
                $indices = $byInstance[$instanceId]
                    ->pluck('repeatable_index')
                    ->map(fn ($i) => $i ?? 0)
                    ->unique()
                    ->sort();

                foreach ($indices as $index) {
                    $records[] = ['instance_id' => $instanceId, 'index' => $index];
                }
            } else {
                $records[] = ['instance_id' => $instanceId, 'index' => null];
            }
        }

        return $records;
    }

    /**
     * @return list<list<string>>
     */
    private function customRows(Collection $items, bool $repeatable, Collection $records): array
    {
        if ($records->isEmpty()) {
            return [];
        }

        $answers = InstanceAnswer::query()
            ->whereIn('interview_item_id', $items->pluck('id'))
            ->whereIn('interview_instance_id', $records->pluck('instance_id')->unique())
            ->get();

        $byKey = [];
        foreach ($answers as $answer) {
            $index = $repeatable ? ($answer->repeatable_index ?? 0) : null;
            $byKey["{$answer->interview_instance_id}|{$index}|{$answer->interview_item_id}"] = $answer;
        }

        return $records->map(function (array $record) use ($items, $byKey) {
            $row = [$this->informant($record['instance_id'])];

            foreach ($items as $item) {
                $answer = $byKey["{$record['instance_id']}|{$record['index']}|{$item->id}"] ?? null;
                $row[] = $this->cell($item, $answer);
            }

            return $row;
        })->all();
    }

    private function cell(InterviewItem $item, ?InstanceAnswer $answer): string
    {
        if ($answer === null) {
            return '';
        }

        $value = (string) ($answer->answer ?? '');

        if ($item->type === 'multi') {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? implode('; ', $decoded) : $value;
        }

        return Spreadsheet::safe($value);
    }

    /**
     * The EthnobotanyR informant × species matrix with a 0/1 column per
     * distinct use category.
     *
     * @return array{headers: list<string>, rows: list<list<string>>}
     */
    public function ethnobotanyMatrix(InterviewItem $categoryField): array
    {
        return $this->ethnobotany($categoryField, null);
    }

    /**
     * @return array{columns: list<string>, species_count: int, rows: list<list<string>>}
     */
    public function ethnobotanyPreview(InterviewItem $categoryField, int $limit = self::PREVIEW_LIMIT): array
    {
        $full = $this->ethnobotany($categoryField, $limit);

        return [
            'columns' => $full['headers'],
            'species_count' => $full['total'],
            'rows' => $full['rows'],
        ];
    }

    /**
     * @return array{headers: list<string>, rows: list<list<string>>, total: int}
     */
    private function ethnobotany(InterviewItem $categoryField, ?int $limit): array
    {
        $categories = InstanceAnswer::query()
            ->where('interview_item_id', $categoryField->id)
            ->get()
            ->pluck('answer')
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->unique()
            ->values();

        $categoryByRecord = InstanceAnswer::query()
            ->where('interview_item_id', $categoryField->id)
            ->get()
            ->mapWithKeys(fn ($a) => [
                "{$a->interview_instance_id}|{$a->repeatable_index}" => $a->answer,
            ]);

        $linked = InstanceAnswer::query()
            ->where('interview_section_id', $categoryField->interview_section_id)
            ->whereNotNull('catalog_species_id')
            ->with('species')
            ->get();

        $headers = array_merge(
            ['informant', 'sp_name'],
            $categories->map(fn ($c) => Spreadsheet::safe(str_replace(' ', '_', mb_strtolower($c))))->all(),
        );

        $selected = $limit === null ? $linked : $linked->take($limit);

        $rows = $selected->map(function ($answer) use ($categories, $categoryByRecord) {
            $category = $categoryByRecord["{$answer->interview_instance_id}|{$answer->repeatable_index}"] ?? null;

            $row = [
                $this->informant($answer->interview_instance_id),
                Spreadsheet::safe(trim(($answer->species->genus ?? '').' '.($answer->species->name ?? ''))),
            ];

            foreach ($categories as $category_) {
                $row[] = $category === $category_ ? '1' : '0';
            }

            return $row;
        })->values()->all();

        return ['headers' => $headers, 'rows' => $rows, 'total' => $linked->count()];
    }
}
