<?php

namespace App\Http\Requests\Api;

class CompleteMediaRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'uuid'],
            'storage_key' => ['required', 'string'],
            'duration_s' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
