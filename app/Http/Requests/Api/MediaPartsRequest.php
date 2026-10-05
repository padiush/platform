<?php

namespace App\Http\Requests\Api;

class MediaPartsRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'uuid'],
            'storage_key' => ['required', 'string'],
        ];
    }
}
