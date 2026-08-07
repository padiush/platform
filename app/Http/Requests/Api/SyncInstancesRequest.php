<?php

namespace App\Http\Requests\Api;

class SyncInstancesRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'instances' => ['required', 'array', 'max:100'],
            'instances.*.id' => ['required', 'uuid'],
            'instances.*.interview_form_id' => ['required', 'integer'],
            'instances.*.captured_at' => ['nullable', 'date'],
            // The structure cursor the interview was captured against. Stored,
            // not enforced: answers are validated against the form's current
            // structure, and this records which one the device was holding so a
            // refusal can be explained.
            'instances.*.form_version_cursor' => ['nullable', 'date'],
            'instances.*.location' => ['nullable', 'array'],
            'instances.*.location.lat' => ['required_with:instances.*.location', 'numeric', 'between:-90,90'],
            'instances.*.location.lng' => ['required_with:instances.*.location', 'numeric', 'between:-180,180'],
            'instances.*.location.accuracy_m' => ['nullable', 'numeric', 'min:0'],
            'instances.*.location.captured_at' => ['nullable', 'date'],
            'instances.*.answers' => ['nullable', 'array'],
            'instances.*.answers.*.client_id' => ['required', 'uuid'],
            'instances.*.answers.*.interview_section_id' => ['required', 'integer'],
            'instances.*.answers.*.interview_item_id' => ['required', 'integer'],
            'instances.*.answers.*.repeatable_index' => ['nullable', 'integer'],
            'instances.*.answers.*.value' => ['nullable'],
            'instances.*.answers.*.edited_at' => ['nullable', 'date'],
        ];
    }
}
