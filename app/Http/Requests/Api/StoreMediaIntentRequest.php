<?php

namespace App\Http\Requests\Api;

use App\Models\Media;
use Illuminate\Validation\Rule;

class StoreMediaIntentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'uuid'],
            'kind' => ['required', Rule::in([Media::KIND_AUDIO, Media::KIND_PHOTO])],
            'content_type' => ['required', 'string', 'max:255'],
            // Guardrail on absurd sizes; ~500 MB ceiling.
            'byte_size' => ['required', 'integer', 'min:1', 'max:524288000'],
            // Opt in to a multipart upload for a file larger than one part
            // (docs/decisions/0012-resumable-media-upload.md).
            'resumable' => ['sometimes', 'boolean'],
        ];
    }
}
