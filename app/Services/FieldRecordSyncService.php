<?php

namespace App\Services;

use App\Models\CollectingPermit;
use App\Models\FieldRecord;
use App\Models\InstanceAnswer;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Applies one pushed field record from the companion sync endpoint. An
 * idempotent upsert keyed on the device-minted `client_id`: re-sending a batch
 * is safe and already-applied records come back "unchanged".
 * See docs/contracts/sync-protocol.md.
 *
 * Only the recorded stage is written here
 * (docs/decisions/0011-companion-field-records.md). The determination and the
 * deposit are web-side, so this service never touches `accession_number`,
 * `repository` or any determination row — which is why a device push cannot
 * conflict with web work on the same record, and why last-writer-wins has less
 * to settle here than it does for an interview answer.
 */
class FieldRecordSyncService
{
    /**
     * The recorded-stage fields, applied only when the payload names them so a
     * device can send a partial update. The permit pair is deliberately absent:
     * it is applied as a unit, below.
     */
    private const DEVICE_FIELDS = [
        'basis_of_record',
        'vernacular_name',
        'collection_number',
        'collector',
        'collected_on',
        'locality',
        'notes',
    ];

    /**
     * @return array{client_id:string,status:string,id?:int,errors?:array}
     */
    public function syncRecord(User $user, Project $project, array $payload): array
    {
        $clientId = $payload['client_id'];

        $existing = FieldRecord::where('client_id', $clientId)->first();

        // A key that already names a record in another study is a hard
        // conflict, not something to silently re-home.
        if ($existing && $existing->project_id !== $project->id) {
            return $this->rejected($clientId, ['client_id' => ['api.sync.client_id_conflict']]);
        }

        $permitId = $payload['collecting_permit_id'] ?? null;

        if ($permitId !== null && ! $this->permitBelongsToProject($permitId, $project)) {
            return $this->rejected($clientId, [
                'collecting_permit_id' => ['api.sync.permit_not_in_project'],
            ]);
        }

        $answerId = null;

        if (filled($payload['answer_client_id'] ?? null)) {
            $answerId = $this->resolveAnswerId($payload['answer_client_id'], $project);

            // The interview it came out of has not landed yet. Refusing keeps
            // the link, which accepting the record without it would lose for
            // good; the device pushes interviews first and retries this.
            if ($answerId === null) {
                return $this->rejected($clientId, [
                    'answer_client_id' => ['api.sync.answer_not_found'],
                ]);
            }
        }

        $editedAt = $this->clampEditedAt($payload['edited_at'] ?? null);

        return DB::transaction(function () use ($project, $existing, $payload, $clientId, $permitId, $answerId, $editedAt) {
            if (! $existing) {
                $record = new FieldRecord;
                $record->project_id = $project->id;
                $record->client_id = $clientId;

                $this->applyDeviceFields($record, $payload, $permitId, $answerId);
                $record->edited_at = $editedAt;
                $record->save();

                return ['client_id' => $clientId, 'id' => $record->id, 'status' => 'created'];
            }

            // Last-writer-wins on the device edit time, as answers resolve
            // (docs/decisions/0004-offline-sync-model.md). A stored time at or
            // after the incoming one wins, which is also how an idempotent
            // re-send resolves to "unchanged".
            if ($existing->edited_at !== null && $editedAt->lessThanOrEqualTo($existing->edited_at)) {
                return ['client_id' => $clientId, 'id' => $existing->id, 'status' => 'unchanged'];
            }

            $this->applyDeviceFields($existing, $payload, $permitId, $answerId);

            // A push that changes nothing is still a no-op, even when the
            // device stamped it later.
            if (! $existing->isDirty()) {
                return ['client_id' => $clientId, 'id' => $existing->id, 'status' => 'unchanged'];
            }

            $existing->edited_at = $editedAt;
            $existing->save();

            return ['client_id' => $clientId, 'id' => $existing->id, 'status' => 'updated'];
        });
    }

    /**
     * Copy the recorded-stage fields the payload names.
     *
     * The permit and the exemption are applied together whenever either appears:
     * they are mutually exclusive (0009), so setting one without clearing the
     * other could leave a record carrying both — a state the web refuses and
     * coverage cannot read.
     */
    private function applyDeviceFields(
        FieldRecord $record,
        array $payload,
        ?int $permitId,
        ?int $answerId
    ): void {
        foreach (self::DEVICE_FIELDS as $field) {
            if (array_key_exists($field, $payload)) {
                $record->{$field} = $payload[$field];
            }
        }

        if (array_key_exists('location', $payload) && is_array($payload['location'])) {
            $record->location_lat = $payload['location']['lat'] ?? null;
            $record->location_lng = $payload['location']['lng'] ?? null;
        }

        if (array_key_exists('collecting_permit_id', $payload) || array_key_exists('permit_exemption', $payload)) {
            $record->collecting_permit_id = $permitId;
            $record->permit_exemption = $permitId === null
                ? ($payload['permit_exemption'] ?? null)
                : null;
        }

        // Only ever set, never cleared: a record that already names the answer
        // it came out of should not lose it because a later push omitted the
        // key it could not have known.
        if ($answerId !== null) {
            $record->instance_answer_id = $answerId;
        }
    }

    private function permitBelongsToProject(int $permitId, Project $project): bool
    {
        return CollectingPermit::whereKey($permitId)
            ->where('project_id', $project->id)
            ->exists();
    }

    /**
     * The answer a record came out of, named by the client_id the device minted
     * for it. Null when it is unknown here or belongs to another study — the
     * caller turns that into a refusal rather than a silently dropped link.
     */
    private function resolveAnswerId(string $answerClientId, Project $project): ?int
    {
        $answer = InstanceAnswer::with('instance.form')
            ->where('client_id', $answerClientId)
            ->first();

        if (! $answer || $answer->instance?->form?->project_id !== $project->id) {
            return null;
        }

        return $answer->id;
    }

    /**
     * The device edit-time is the LWW key, but device clocks can be wrong:
     * clamp anything in the future back to now so a fast clock can't win
     * forever. Same rule the interview sync applies.
     */
    private function clampEditedAt(?string $value): Carbon
    {
        $now = Carbon::now();

        if ($value === null || $value === '') {
            return $now;
        }

        $edited = Carbon::parse($value)->setTimezone(config('app.timezone'));

        return $edited->greaterThan($now) ? $now : $edited;
    }

    /**
     * @return array{client_id:string,status:string,errors:array}
     */
    private function rejected(string $clientId, array $errors): array
    {
        return ['client_id' => $clientId, 'status' => 'rejected', 'errors' => $errors];
    }
}
