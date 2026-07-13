<?php

namespace App\Http\Requests\Api;

class StoreTokenRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            // Names the token so a user can revoke a specific lost device.
            'device_name' => ['required', 'string', 'max:255'],
        ];
    }
}
