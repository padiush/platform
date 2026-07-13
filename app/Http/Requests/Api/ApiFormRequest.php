<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base request for the companion API: validation failures render the same JSON
 * envelope the rest of the API uses ({ message, message_type, errors }) with a
 * 422, instead of Laravel's default shape. Per-endpoint authorization is done
 * in the controllers against ProjectPolicy, so authorize() is open here.
 */
abstract class ApiFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json([
            'message' => 'api.validation_failed',
            'message_type' => 'error',
            'errors' => $validator->errors()->toArray(),
        ], 422));
    }
}
