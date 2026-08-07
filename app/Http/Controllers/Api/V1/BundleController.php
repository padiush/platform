<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\InterviewForm;
use App\Models\Project;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/projects/{project}/bundle?since=<iso8601> — the offline capture
 * bundle: every active form's full structure, so a device can record against it
 * with no connectivity. `?since` makes it incremental (only forms whose
 * structure changed after the cursor). The device does NOT pull the species
 * catalog — linking is a web-side task (ADR 0003); it captures the raw folk name.
 *
 * form_version_cursor is the latest structure-change time across the project's
 * active forms; the device stores it and passes it back as `since`, and carries
 * it on captured instances to anchor snapshot-at-capture form-version skew
 * (docs/contracts/sync-protocol.md).
 */
class BundleController extends ApiController
{
    public function show(Request $request, Project $project): JsonResponse
    {
        $this->requireCapability($request->user(), $project, 'record_data');

        $since = $this->parseSince($request->query('since'));

        $forms = $project->activeInterviewForms()
            ->with([
                'sections' => fn ($query) => $query->orderBy('order'),
                'sections.items' => fn ($query) => $query->orderBy('order'),
            ])
            ->orderBy('id')
            ->get();

        $cursor = null;
        $payload = [];

        foreach ($forms as $form) {
            $version = $this->structureUpdatedAt($form);

            if ($cursor === null || $version->greaterThan($cursor)) {
                $cursor = $version;
            }

            // Skip forms unchanged since the device's cursor.
            if ($since !== null && $version->lessThanOrEqualTo($since)) {
                continue;
            }

            $payload[] = $this->formPayload($form);
        }

        return response()->json([
            'form_version_cursor' => $cursor?->toIso8601String(),
            // Every form the device should still be able to record against.
            // `forms` is a delta once `since` is given, and a delta cannot
            // express a removal: a deactivated or deleted form simply stops
            // appearing, which is indistinguishable from one that has not
            // changed. Without this list a form retired on the web keeps
            // accepting interviews on every device that already cached it.
            'active_form_ids' => $forms->pluck('id')->values(),
            'forms' => $payload,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    private function parseSince(?string $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (InvalidFormatException) {
            $this->fail('api.validation_failed', 422, [
                'since' => ['api.bundle.invalid_since'],
            ]);
        }
    }

    /**
     * The most recent change to any part of a form's structure — the form row
     * itself, its sections, or its items.
     */
    private function structureUpdatedAt(InterviewForm $form): Carbon
    {
        $times = collect([$form->updated_at]);

        foreach ($form->sections as $section) {
            $times->push($section->updated_at);

            foreach ($section->items as $item) {
                $times->push($item->updated_at);
            }
        }

        return $times->filter()->max();
    }

    private function formPayload(InterviewForm $form): array
    {
        return [
            'id' => $form->id,
            'name' => $form->name,
            'description' => $form->description,
            'is_active' => (bool) $form->is_active,
            'updated_at' => $form->updated_at?->toIso8601String(),
            'sections' => $form->sections->map(fn ($section) => [
                'id' => $section->id,
                'name' => $section->name,
                'order' => $section->order,
                'repeatable' => (bool) $section->repeatable,
                'items' => $section->items->map(fn ($item) => [
                    'id' => $item->id,
                    'label' => $item->label,
                    'name' => $item->name,
                    'type' => $item->type,
                    'required' => (bool) $item->required,
                    'options' => $item->options,
                    'link_to_species' => (bool) $item->link_to_species,
                    'is_use_category' => (bool) $item->is_use_category,
                    'min' => $item->min,
                    'max' => $item->max,
                    'step' => $item->step,
                    'order' => $item->order,
                ])->values(),
            ])->values(),
        ];
    }
}
