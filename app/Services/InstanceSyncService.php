<?php

namespace App\Services;

use App\Models\InstanceAnswer;
use App\Models\InstanceAnswerRevision;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Applies one pushed interview from the companion sync endpoint. The push is an
 * idempotent upsert keyed on client-generated UUIDs (instance id, answer
 * client_id): re-sending a batch is safe, already-applied records come back
 * "unchanged". See docs/contracts/sync-protocol.md.
 *
 * Post-sync conflicts (the same answer edited in two places) resolve by
 * last-writer-wins on the device edit-time, per answer row; the overwritten
 * value is snapshotted to instance_answer_revisions first, so nothing is
 * silently lost (docs/decisions/0004-offline-sync-model.md).
 */
class InstanceSyncService
{
    /**
     * @return array{id:string,status:string,errors?:array}
     */
    public function syncInstance(User $user, Project $project, array $payload): array
    {
        $form = InterviewForm::find($payload['interview_form_id']);

        if (! $form || $form->project_id !== $project->id) {
            return $this->rejected($payload['id'], [
                'interview_form_id' => ['api.sync.form_not_in_project'],
            ]);
        }

        $existing = InterviewInstance::find($payload['id']);

        // The device owns its captures: only the recorder may update an instance.
        if ($existing && $existing->user_id !== $user->id) {
            return $this->rejected($payload['id'], ['id' => ['api.sync.not_owner']]);
        }

        // An instance can't be moved to another form.
        if ($existing && $existing->interview_form_id !== $form->id) {
            return $this->rejected($payload['id'], [
                'interview_form_id' => ['api.sync.form_mismatch'],
            ]);
        }

        return DB::transaction(function () use ($user, $form, $existing, $payload) {
            $created = $existing === null;

            if ($created) {
                $instance = new InterviewInstance;
                $instance->id = $payload['id'];
                $instance->interview_form_id = $form->id;
                $instance->user_id = $user->id;
            } else {
                $instance = $existing;
            }

            $this->applyInstanceFields($instance, $payload);
            $instanceChanged = $instance->isDirty();
            $instance->save();

            [$answersChanged, $answerErrors] = $this->syncAnswers(
                $instance,
                $form,
                $payload['answers'] ?? []
            );

            $status = match (true) {
                $created => 'created',
                $instanceChanged || $answersChanged => 'updated',
                default => 'unchanged',
            };

            $result = ['id' => $instance->id, 'status' => $status];

            if ($answerErrors !== []) {
                $result['errors'] = ['answers' => $answerErrors];
            }

            return $result;
        });
    }

    private function applyInstanceFields(InterviewInstance $instance, array $payload): void
    {
        if (array_key_exists('captured_at', $payload)) {
            $instance->captured_at = $payload['captured_at']
                ? Carbon::parse($payload['captured_at'])
                : null;
        }

        if (array_key_exists('location', $payload) && is_array($payload['location'])) {
            $location = $payload['location'];
            $instance->location_lat = $location['lat'] ?? null;
            $instance->location_lng = $location['lng'] ?? null;
            $instance->location_accuracy_m = $location['accuracy_m'] ?? null;
            $instance->location_captured_at = isset($location['captured_at'])
                ? Carbon::parse($location['captured_at'])
                : null;
        }
    }

    /**
     * @return array{0:bool,1:array} [anyChanged, perAnswerErrors]
     */
    private function syncAnswers(InterviewInstance $instance, InterviewForm $form, array $answers): array
    {
        $changed = false;
        $errors = [];

        foreach ($answers as $payload) {
            $result = $this->syncAnswer($instance, $form, $payload);

            if ($result['status'] === 'rejected') {
                $errors[] = [
                    'client_id' => $payload['client_id'] ?? null,
                    'error' => $result['error'],
                ];
            } elseif ($result['status'] !== 'unchanged') {
                $changed = true;
            }
        }

        return [$changed, $errors];
    }

    /**
     * @return array{status:string,error?:string}
     */
    private function syncAnswer(InterviewInstance $instance, InterviewForm $form, array $payload): array
    {
        $item = InterviewItem::find($payload['interview_item_id']);

        // Snapshot-at-capture: an answer whose item no longer exists (or was
        // never in this form) is rejected with a clear reason, not dropped.
        if (! $item || $item->section?->interview_form_id !== $form->id) {
            return ['status' => 'rejected', 'error' => 'api.sync.item_not_in_form'];
        }

        // Answers pin their section; a mismatch would corrupt repeatable grouping.
        if ((int) $item->interview_section_id !== (int) $payload['interview_section_id']) {
            return ['status' => 'rejected', 'error' => 'api.sync.section_mismatch'];
        }

        $editedAt = $this->clampEditedAt($payload['edited_at'] ?? null);
        $repeatableIndex = $payload['repeatable_index'] ?? null;
        $value = $this->encodeValue($item, $payload['value'] ?? null);

        $existing = InstanceAnswer::where('client_id', $payload['client_id'])->first();

        if (! $existing) {
            // Adopt a web-created row for the same slot (it has no client_id),
            // so a web answer and a later device sync don't duplicate.
            $existing = InstanceAnswer::where('interview_instance_id', $instance->id)
                ->where('interview_item_id', $item->id)
                ->where('repeatable_index', $repeatableIndex)
                ->whereNull('client_id')
                ->first();
        }

        if (! $existing) {
            InstanceAnswer::create([
                'client_id' => $payload['client_id'],
                'interview_instance_id' => $instance->id,
                'interview_section_id' => $item->interview_section_id,
                'interview_item_id' => $item->id,
                'repeatable_index' => $repeatableIndex,
                'answer' => $value,
                'edited_at' => $editedAt,
            ]);

            return ['status' => 'created'];
        }

        // A client_id that resolves to a different instance is a hard conflict.
        if ($existing->interview_instance_id !== $instance->id) {
            return ['status' => 'rejected', 'error' => 'api.sync.client_id_conflict'];
        }

        // Last-writer-wins: a stored edit-time that is >= the incoming one wins
        // (this is also how idempotent re-sends resolve to "unchanged"). A null
        // stored edit-time (a web row never stamped) counts as older.
        if ($existing->edited_at !== null && $editedAt->lessThanOrEqualTo($existing->edited_at)) {
            return ['status' => 'unchanged'];
        }

        // Overwrite: snapshot the prior value to the audit trail first.
        InstanceAnswerRevision::create([
            'instance_answer_id' => $existing->id,
            'answer' => $existing->answer,
            'catalog_species_id' => $existing->catalog_species_id,
            'edited_at' => $existing->edited_at,
            'source' => 'api',
        ]);

        $existing->client_id ??= $payload['client_id'];
        $existing->answer = $value;
        $existing->edited_at = $editedAt;
        // catalog_species_id, section, item and index are preserved — linking is
        // a web-side task and must survive a device re-sync.
        $existing->save();

        return ['status' => 'updated'];
    }

    /**
     * Match the web's answer encoding so linking, export and the indices read
     * one format: multi-selects are JSON, everything else is a plain string.
     */
    private function encodeValue(InterviewItem $item, mixed $value): ?string
    {
        if ($item->type === 'multi') {
            return json_encode($value ?? []);
        }

        return $value === null ? null : (string) $value;
    }

    /**
     * The device edit-time is the LWW key, but device clocks can be wrong:
     * clamp anything in the future back to now so a fast clock can't win forever.
     */
    private function clampEditedAt(?string $value): Carbon
    {
        $now = Carbon::now();

        if ($value === null) {
            return $now;
        }

        $edited = Carbon::parse($value);

        return $edited->greaterThan($now) ? $now : $edited;
    }

    private function rejected(string $id, array $errors): array
    {
        return ['id' => $id, 'status' => 'rejected', 'errors' => $errors];
    }
}
