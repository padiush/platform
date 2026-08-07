<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;

/**
 * Base for the companion capture API (docs/contracts/companion-api.md).
 *
 * Authorization reuses the web's ProjectPolicy capability checks — there is no
 * new authz surface. Errors use the same JSON envelope as the web's flash
 * convention: { message, message_type: "error", errors? }. The message is an
 * i18n key the mobile app localizes.
 */
abstract class ApiController extends Controller
{
    /**
     * Abort with the standard error envelope.
     */
    protected function fail(string $message, int $status, array $errors = []): never
    {
        $payload = ['message' => $message, 'message_type' => 'error'];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        throw new HttpResponseException(response()->json($payload, $status));
    }

    /**
     * Deny (403) unless the user holds the given capability on the project.
     * Accepts the snake_case capability name used across the app.
     */
    protected function requireCapability(User $user, Project $project, string $capability): void
    {
        if (! $user->can(Str::camel($capability), $project)) {
            $this->fail('api.forbidden', 403);
        }
    }

    /**
     * Ensure a form belongs to the project (404 otherwise), so IDs from one
     * project can't reach another's data.
     */
    protected function requireFormInProject(InterviewForm $form, Project $project): void
    {
        if ($form->project_id !== $project->id) {
            $this->fail('api.not_found', 404);
        }
    }

    /**
     * Resolve the project an instance belongs to, or 404 if it is orphaned.
     */
    protected function projectForInstance(InterviewInstance $instance): Project
    {
        $project = $instance->form?->project;

        if (! $project) {
            $this->fail('api.not_found', 404);
        }

        return $project;
    }
}
