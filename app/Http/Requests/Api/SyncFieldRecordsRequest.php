<?php

namespace App\Http\Requests\Api;

use App\Models\FieldRecord;
use Illuminate\Validation\Rule;

/**
 * What a device may say about a field record.
 *
 * Only the recorded stage is device-authored
 * (docs/decisions/0011-companion-field-records.md). The identification and
 * deposit fields are **prohibited** rather than quietly dropped: a device that
 * believes it deposited something would otherwise show a voucher number that
 * exists nowhere, and silence would make that a support question instead of a
 * 422 the client author sees on the first run.
 *
 * `collecting_permit_id` is scoped to the project in the controller, not here —
 * the project comes from the route and a FormRequest that reached for it would
 * duplicate the capability check that guards it.
 */
class SyncFieldRecordsRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'records' => ['required', 'array', 'max:100'],

            // The device's idempotency key. A record created offline has no
            // server id, and a re-send after a lost acknowledgement must land
            // on the same row rather than a second one.
            'records.*.client_id' => ['required', 'uuid'],

            'records.*.basis_of_record' => ['nullable', Rule::in(FieldRecord::BASES)],
            'records.*.vernacular_name' => ['nullable', 'string', 'max:255'],
            'records.*.collection_number' => ['nullable', 'string', 'max:255'],
            'records.*.collector' => ['nullable', 'string', 'max:255'],
            'records.*.collected_on' => ['nullable', 'date'],
            'records.*.locality' => ['nullable', 'string', 'max:255'],
            'records.*.notes' => ['nullable', 'string'],

            // Shaped like the instance payload's `location`, so a device builds
            // both the same way.
            'records.*.location' => ['nullable', 'array'],
            'records.*.location.lat' => ['required_with:records.*.location', 'numeric', 'between:-90,90'],
            'records.*.location.lng' => ['required_with:records.*.location', 'numeric', 'between:-180,180'],

            // A permit is held before the fieldwork, so the device already has
            // it from the bundle. Never both — the pairing has no meaning
            // (docs/decisions/0009-collecting-permits.md), and the rule is
            // enforced here so a form filled in the field is refused on the
            // device rather than on return.
            'records.*.collecting_permit_id' => [
                'nullable',
                'integer',
                'prohibits:records.*.permit_exemption',
            ],
            'records.*.permit_exemption' => [
                'nullable',
                'prohibits:records.*.collecting_permit_id',
                Rule::in(FieldRecord::EXEMPTIONS),
            ],

            // The answer this record came out of, named by the client_id the
            // device minted for it — offline, that is the only id it knows.
            'records.*.answer_client_id' => ['nullable', 'uuid'],

            // The device's own edit time, which last-writer-wins compares.
            'records.*.edited_at' => ['nullable', 'date'],

            // Web-only stages. See the class docblock.
            'records.*.accession_number' => ['prohibited'],
            'records.*.repository' => ['prohibited'],
            'records.*.determination' => ['prohibited'],
        ];
    }

    /**
     * The prohibited rules would otherwise report "field is prohibited", which
     * says nothing about why. These are i18n keys the device localizes, as the
     * sync result errors are.
     */
    public function messages(): array
    {
        return [
            'records.*.accession_number.prohibited' => 'api.records.deposit_is_web_side',
            'records.*.repository.prohibited' => 'api.records.deposit_is_web_side',
            'records.*.determination.prohibited' => 'api.records.identification_is_web_side',
        ];
    }
}
