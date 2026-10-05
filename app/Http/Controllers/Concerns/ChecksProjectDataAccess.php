<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Project;
use App\Services\ActiveProject;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

/**
 * The gate on a project's interview data.
 *
 * Extracted so more than one controller can apply exactly the same rule rather
 * than a similar one: what an informant said, and the photographs and audio
 * captured alongside it, are the same data and must be reached through the same
 * check.
 */
trait ChecksProjectDataAccess
{
    /**
     * Aborts the request (403 JSON or redirect with a flash) unless the user
     * has manage_data or generate_reports on the project.
     */
    protected function checkPermission(Project $project, $json = false): void
    {
        $user = Auth::user();

        if (
            ! $user->can('view', $project) || (
                ! $user->can('manageData', $project) &&
                ! $user->can('generateReports', $project)
            )
        ) {
            $this->deny('data.no_access', $json);
        }
    }

    /**
     * JSON for a request that asked for it, a flashed redirect otherwise —
     * thrown rather than returned so a caller cannot forget to stop. The
     * redirect goes to the overview of the project the user works in, which
     * every member can open; the data could turn them away again.
     */
    protected function deny(string $message, bool $json): never
    {
        throw new HttpResponseException(
            $json || request()->expectsJson()
                ? response()->json(['error' => $message], 403)
                : redirect()
                    ->to(app(ActiveProject::class)->home(request()))
                    ->with('message', $message)
                    ->with('message_type', 'error')
        );
    }
}
