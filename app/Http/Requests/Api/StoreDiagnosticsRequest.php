<?php

namespace App\Http\Requests\Api;

use App\Models\DeviceDiagnostic;
use Illuminate\Validation\Rule;

/**
 * A batch of integrity events. The shape is closed on purpose: the only string
 * a device may choose is `code`, and that has to be one of a fixed list. There
 * is nowhere to put a message, a file path or an answer, so this channel cannot
 * carry informant data even if a future caller tries.
 */
class StoreDiagnosticsRequest extends ApiFormRequest
{
    /** A device that has been offline for weeks still should not flood a batch. */
    private const MAX_EVENTS = 100;

    public function rules(): array
    {
        return [
            'events' => ['required', 'array', 'min:1', 'max:'.self::MAX_EVENTS],
            'events.*.client_id' => ['required', 'uuid', 'distinct'],
            'events.*.code' => ['required', Rule::in(DeviceDiagnostic::CODES)],
            'events.*.occurred_at' => ['required', 'date'],
            'events.*.app_version' => ['nullable', 'string', 'max:32'],
            'events.*.platform' => ['nullable', Rule::in(['android', 'ios'])],
            'events.*.os_version' => ['nullable', 'string', 'max:32'],
        ];
    }
}
