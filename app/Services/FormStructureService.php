<?php

namespace App\Services;

use App\Models\InstanceAnswer;
use App\Models\InterviewForm;
use App\Models\InterviewItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Reconciles a designer draft (sections with nested items, in display order)
 * against the stored structure in one transaction: rows with ids are updated,
 * rows without ids are created, rows missing from the draft are deleted, and
 * order is derived from array position.
 *
 * Deleting items that already have recorded answers requires explicit
 * confirmation (the answers are deleted with them — never orphaned). Moving
 * an item with answers to another section is refused outright: answers store
 * their section and repeatable index, so a move would corrupt the grouping.
 *
 * Returns one of:
 *  ['status' => 'invalid_section'|'invalid_item']
 *  ['status' => 'blocked_moves', 'items' => [{id, label, answers_count}]]
 *  ['status' => 'conflict', 'detaching' => [{id, label, answers_count}], 'total_answers' => int]
 *  ['status' => 'saved']
 */
class FormStructureService
{
    public function apply(InterviewForm $form, array $sectionsPayload, bool $confirmDetach): array
    {
        $existingSections = $form->sections()->with('items')->get();
        $existingSectionIds = $existingSections->pluck('id');
        $existingItems = $existingSections->flatMap->items;
        $existingItemIds = $existingItems->pluck('id');

        $payload = collect($sectionsPayload);
        $payloadSectionIds = $payload->pluck('id')->filter();
        $payloadItems = $payload->flatMap(fn ($section) => $section['items'] ?? []);
        $payloadItemIds = $payloadItems->pluck('id')->filter();

        if (
            $payloadSectionIds->diff($existingSectionIds)->isNotEmpty() ||
            $payloadSectionIds->duplicates()->isNotEmpty()
        ) {
            return ['status' => 'invalid_section'];
        }

        if (
            $payloadItemIds->diff($existingItemIds)->isNotEmpty() ||
            $payloadItemIds->duplicates()->isNotEmpty()
        ) {
            return ['status' => 'invalid_item'];
        }

        $answerCounts = InstanceAnswer::whereIn('interview_item_id', $existingItemIds)
            ->selectRaw('interview_item_id, count(*) as answers_count')
            ->groupBy('interview_item_id')
            ->pluck('answers_count', 'interview_item_id');

        $blockedMoves = $this->movedItemsWithAnswers($payload, $existingItems, $answerCounts);

        if ($blockedMoves->isNotEmpty()) {
            return ['status' => 'blocked_moves', 'items' => $blockedMoves->values()->all()];
        }

        $deletedItemIds = $existingItemIds->diff($payloadItemIds);
        $detaching = $existingItems
            ->whereIn('id', $deletedItemIds)
            ->filter(fn ($item) => ($answerCounts[$item->id] ?? 0) > 0)
            ->map(fn ($item) => [
                'id' => $item->id,
                'label' => $item->label,
                'answers_count' => (int) $answerCounts[$item->id],
            ]);

        if ($detaching->isNotEmpty() && ! $confirmDetach) {
            return [
                'status' => 'conflict',
                'detaching' => $detaching->values()->all(),
                'total_answers' => $detaching->sum('answers_count'),
            ];
        }

        DB::transaction(function () use ($form, $payload, $deletedItemIds) {
            InstanceAnswer::whereIn('interview_item_id', $deletedItemIds)->delete();
            InterviewItem::whereIn('id', $deletedItemIds)->delete();

            $keptSectionIds = [];

            foreach ($payload as $sectionIndex => $sectionPayload) {
                $attributes = [
                    'name' => $sectionPayload['name'],
                    'repeatable' => (bool) ($sectionPayload['repeatable'] ?? false),
                    'order' => $sectionIndex + 1,
                ];

                if (! empty($sectionPayload['id'])) {
                    $section = $form->sections()->whereKey($sectionPayload['id'])->first();
                    $section->update($attributes);
                } else {
                    $section = $form->sections()->create($attributes);
                }

                $keptSectionIds[] = $section->id;

                foreach ($sectionPayload['items'] ?? [] as $itemIndex => $itemPayload) {
                    $itemAttributes = $this->itemAttributes($itemPayload, $section->id, $itemIndex + 1);

                    if (! empty($itemPayload['id'])) {
                        InterviewItem::whereKey($itemPayload['id'])->first()->update($itemAttributes);
                    } else {
                        InterviewItem::create($itemAttributes);
                    }
                }
            }

            // Remaining items of dropped sections were deleted or moved above.
            $form->sections()->whereNotIn('id', $keptSectionIds)->delete();
        });

        return ['status' => 'saved'];
    }

    /**
     * Items whose target section differs from their stored one and that
     * already have recorded answers.
     */
    private function movedItemsWithAnswers($payload, $existingItems, $answerCounts)
    {
        return $payload
            ->flatMap(function ($sectionPayload) use ($existingItems) {
                $targetSectionId = $sectionPayload['id'] ?? null;

                return collect($sectionPayload['items'] ?? [])
                    ->filter(fn ($item) => ! empty($item['id']))
                    ->map(fn ($item) => [
                        'item' => $existingItems->firstWhere('id', $item['id']),
                        'target_section_id' => $targetSectionId,
                    ]);
            })
            ->filter(fn ($row) => $row['item'] !== null
                && $row['item']->interview_section_id !== $row['target_section_id'])
            ->filter(fn ($row) => ($answerCounts[$row['item']->id] ?? 0) > 0)
            ->map(fn ($row) => [
                'id' => $row['item']->id,
                'label' => $row['item']->label,
                'answers_count' => (int) $answerCounts[$row['item']->id],
            ]);
    }

    private function itemAttributes(array $itemPayload, int $sectionId, int $order): array
    {
        $type = $itemPayload['type'];
        $isNumber = $type === 'number';
        $hasOptions = in_array($type, ['multi', 'select'], true);
        $options = $hasOptions
            ? array_values(array_filter(
                array_map(fn ($option) => trim((string) $option), $itemPayload['options'] ?? []),
                fn ($option) => $option !== ''
            ))
            : null;

        return [
            'interview_section_id' => $sectionId,
            'order' => $order,
            'label' => $itemPayload['label'],
            'name' => Str::slug($itemPayload['name'], '_'),
            'type' => $type,
            'required' => (bool) ($itemPayload['required'] ?? false),
            'link_to_species' => (bool) ($itemPayload['link_to_species'] ?? false),
            'is_use_category' => (bool) ($itemPayload['is_use_category'] ?? false),
            'min' => $isNumber ? ($itemPayload['min'] ?? null) : null,
            'max' => $isNumber ? ($itemPayload['max'] ?? null) : null,
            'step' => $isNumber ? ($itemPayload['step'] ?? null) : null,
            'options' => $options,
        ];
    }
}
