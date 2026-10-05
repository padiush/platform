<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ActiveProject;
use App\Services\ExampleStudy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The example project: a private copy of the invented demonstration study, so
 * someone new can see every section with something in it, and change anything
 * without fear. One per user, opened on request and removed in one step.
 */
class ExampleProjectController extends Controller
{
    public function __construct(private ActiveProject $activeProject) {}

    /** Open the user's example project, making it first if there is none yet. */
    public function store(Request $request, ExampleStudy $study): RedirectResponse
    {
        $user = $request->user();

        $project = $this->ownExample($request)
            ?? $study->build($user, __('Example: useful plants of the highlands'), __('Example project'));

        $this->activeProject->remember($user, $project);

        return redirect()
            ->route('projects.overview', ['project' => $project->id])
            ->with('message', 'example.created')
            ->with('message_type', 'success');
    }

    /** Remove the user's example project, and everything in it. */
    public function destroy(Request $request): RedirectResponse
    {
        $project = $this->ownExample($request);

        if ($project) {
            // Memberships first: the constraint that cascades them was only
            // ever added for MySQL and MariaDB, not for SQLite.
            DB::transaction(function () use ($project) {
                $project->accesses()->delete();
                $project->delete();
            });
        }

        return redirect()
            ->route('projects.index')
            ->with('message', 'example.removed')
            ->with('message_type', 'success');
    }

    private function ownExample(Request $request): ?Project
    {
        return Project::where('user_id', $request->user()->id)
            ->where('is_example', true)
            ->first();
    }
}
