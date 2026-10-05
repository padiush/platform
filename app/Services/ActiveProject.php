<?php

namespace App\Services;

use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\Project;
use App\Models\ProjectAccess;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * The project the sidebar is open on, and the sections it offers there.
 *
 * Almost every page belongs to one project, so the navigation works in one at
 * a time instead of asking for the project again in every section. The page's
 * address is the authority: a page that names a project, or a form, interview
 * or record of one, is in that project. A page that names none — the
 * dashboard, a section's landing page — is in the project the user last
 * worked in, which is kept on their account.
 */
class ActiveProject
{
    /**
     * The sidebar's sections, in order. Each one is offered in a project only
     * when the user's role there opens it; a finished project takes no new
     * forms and no new interviews, so those two are not offered in one.
     */
    public const SECTIONS = ['overview', 'forms', 'interviews', 'records', 'catalog', 'data'];

    /** @var array<int, Collection<int, ProjectAccess>> */
    private array $accesses = [];

    /**
     * The user's accesses with their projects and roles, by project id.
     * Accesses whose project is gone are left out.
     *
     * @return Collection<int, ProjectAccess>
     */
    public function accesses(User $user): Collection
    {
        return $this->accesses[$user->id] ??= $user->projectAccesses()
            ->with(['project', 'capability'])
            ->get()
            ->filter(fn (ProjectAccess $access) => $access->project !== null)
            ->keyBy('project_id');
    }

    /** The project the current page names, if it names one the user can open. */
    public function fromRoute(Request $request): ?Project
    {
        $route = $request->route();

        if (! $route || ! $request->user()) {
            return null;
        }

        $project = match (true) {
            $route->parameter('project') instanceof Project => $route->parameter('project'),
            $route->parameter('form') instanceof InterviewForm => $route->parameter('form')->project,
            $route->parameter('instance') instanceof InterviewInstance => $route->parameter('instance')->form?->project,
            // A section's landing page narrowed to one project.
            ctype_digit((string) $request->query('project')) => Project::find((int) $request->query('project')),
            default => null,
        };

        if ($project === null || ! $this->accesses($request->user())->has($project->id)) {
            return null;
        }

        return $project;
    }

    /**
     * The project the sidebar is open on: the page's, else the one the user
     * last worked in, else the first they can open — an unfinished one before
     * a finished one. Null only for someone with no project at all.
     */
    public function resolve(Request $request): ?Project
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        $accesses = $this->accesses($user);

        $fromRoute = $this->fromRoute($request);
        if ($fromRoute) {
            return $fromRoute;
        }

        if ($user->last_project_id && $accesses->has($user->last_project_id)) {
            return $accesses->get($user->last_project_id)->project;
        }

        return $accesses
            ->map(fn (ProjectAccess $access) => $access->project)
            ->sortBy(fn (Project $project) => [$project->finished ? 1 : 0, $project->name])
            ->first();
    }

    /**
     * Where to send someone a page turned away: the overview of the project
     * they are working in, or the welcome when they have none. Never the page
     * of a section, which could turn them away again.
     */
    public function home(Request $request): string
    {
        $project = $this->resolve($request);

        return $project
            ? route('projects.overview', ['project' => $project->id])
            : route('dashboard');
    }

    /** Remember the project the user is working in, on their account. */
    public function remember(User $user, Project $project): void
    {
        if ($user->last_project_id === $project->id) {
            return;
        }

        // Not an edit of the account, so updated_at stays as it was.
        User::whereKey($user->id)->toBase()->update(['last_project_id' => $project->id]);
        $user->setAttribute('last_project_id', $project->id)->syncOriginalAttribute('last_project_id');
    }

    /**
     * Where each section opens in a project, for the sections the user's role
     * there offers.
     *
     * @return array<string, string>
     */
    public function sections(User $user, Project $project): array
    {
        $access = $this->accesses($user)->get($project->id);

        if (! $access) {
            return [];
        }

        $can = $access->capability;
        $open = ! $project->finished;
        $id = $project->id;

        $sections = [
            'overview' => route('projects.overview', ['project' => $id]),
            'forms' => $open && $can->manage_forms
                ? route('designer.index', ['project' => $id]) : null,
            'interviews' => $open && $can->record_data
                ? route('interviews.index', ['project' => $id]) : null,
            'records' => $can->view_catalog
                ? route('catalogs.fieldRecords.index', ['project' => $id]) : null,
            'catalog' => $can->view_catalog
                ? route('catalogs.show', ['project' => $id]) : null,
            'data' => $can->manage_data || $can->generate_reports
                ? route('data.index', ['project' => $id]) : null,
        ];

        return array_filter($sections);
    }

    /**
     * What the sidebar needs: every project the user can open, the active
     * one, and the sections it offers there.
     *
     * @return array{active: array{id:int, name:string, finished:bool}|null, projects: array<int, array{id:int, name:string, finished:bool}>, sections: array<string, string>}
     */
    public function navigation(Request $request): array
    {
        $user = $request->user();
        $active = $this->resolve($request);

        $summary = fn (Project $project) => [
            'id' => $project->id,
            'name' => $project->name,
            'finished' => (bool) $project->finished,
        ];

        return [
            'active' => $active ? $summary($active) : null,
            'projects' => $this->accesses($user)
                ->map(fn (ProjectAccess $access) => $summary($access->project))
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all(),
            'sections' => $active ? $this->sections($user, $active) : [],
        ];
    }
}
