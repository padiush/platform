<?php

namespace App\Http\Controllers;

use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Services\ActiveProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sends an address from before forms and interviews moved under their
 * project to where each lives now, so bookmarks and links shared before the
 * move keep working.
 *
 * The old interview addresses named a form or an interview but not its
 * project, so finding the new one means looking the project up. That is only
 * done for someone who can open the project, so an old address does not
 * reveal which project an id belongs to.
 */
class LegacyInterviewRedirectController extends Controller
{
    public function __construct(private readonly ActiveProject $active) {}

    /**
     * The forms used to open on every project's; this opens the project the
     * user works in, or the one an old ?project= or ?create= named.
     */
    public function forms(Request $request): RedirectResponse
    {
        $create = $request->query('create');
        $project = $this->project($request, $request->query('project') ?? $create);

        if (! $project) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('designer.index', array_filter([
            'project' => $project,
            'create' => $create !== null ? 1 : null,
            'edit' => $request->query('edit'),
        ]));
    }

    /** /designer/{project}/… named its project, so the move is by address. */
    public function form(Request $request, int $project, string $path): RedirectResponse
    {
        $path = trim($path, '/');

        $target = match (true) {
            $path === 'create' => 'create',
            (bool) preg_match('#^form/(\d+)$#', $path, $m) => "{$m[1]}/edit",
            (bool) preg_match('#^form/(\d+)/wizard$#', $path, $m) => "{$m[1]}/design",
            (bool) preg_match('#^form/(\d+)/preview$#', $path, $m) => "{$m[1]}/preview",
            default => abort(404),
        };

        return $this->moved($request, "/projects/{$project}/forms/{$target}");
    }

    public function interviews(Request $request): RedirectResponse
    {
        $project = $this->project($request, $request->query('project'));

        return $project
            ? redirect()->route('interviews.index', ['project' => $project])
            : redirect()->route('dashboard');
    }

    /** A form's interviews, or a new interview on it. */
    public function formInterviews(Request $request, int $form, string $page): RedirectResponse
    {
        $project = $this->reachable($request, InterviewForm::find($form)?->project_id);

        return $this->moved($request, $page === 'create'
            ? "/projects/{$project}/interviews/forms/{$form}/new"
            : "/projects/{$project}/interviews/forms/{$form}");
    }

    public function interview(Request $request, string $instance): RedirectResponse
    {
        $project = $this->reachable(
            $request,
            InterviewInstance::with('form')->find($instance)?->form?->project_id
        );

        return $this->moved($request, "/projects/{$project}/interviews/{$instance}");
    }

    /** The project an old hub was narrowed to, else the one the user works in. */
    private function project(Request $request, mixed $asked): ?int
    {
        if (ctype_digit((string) $asked) && $this->active->accesses($request->user())->has((int) $asked)) {
            return (int) $asked;
        }

        return $this->active->resolve($request)?->id;
    }

    /** The project id, if the user can open it; otherwise not found. */
    private function reachable(Request $request, ?int $project): int
    {
        abort_unless($project && $this->active->accesses($request->user())->has($project), 404);

        return $project;
    }

    private function moved(Request $request, string $target): RedirectResponse
    {
        $query = $request->getQueryString();

        return redirect()->to($target.($query ? "?{$query}" : ''), 301);
    }
}
