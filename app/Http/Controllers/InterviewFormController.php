<?php

namespace App\Http\Controllers;

use App\Models\InterviewForm;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InterviewFormController extends Controller
{
    /**
     * Aborts with a redirect unless the form actually belongs to the route
     * project. Prevents a user authorized on their own project from editing
     * another project's forms by mixing ids in the URL.
     */
    private static function ensureFormBelongsToProject(
        Project $project,
        InterviewForm $form
    ): void {
        if ($form->project_id !== $project->id) {
            throw new HttpResponseException(
                redirect()
                    ->route('designer.index')
                    ->with('message', 'designer.form_not_found')
                    ->with('message_type', 'error')
            );
        }
    }

    public function index(): Response|RedirectResponse
    {
        $accesses = Auth::user()
            ->projectAccesses()
            ->with(['project.interviewForms.instances', 'capability'])
            ->get();

        $projects = collect();

        foreach ($accesses as $access) {
            $project = $access->project;
            if (! $project) {
                // Access row pointing at a deleted project — skip it.
                continue;
            }

            if (! $project->finished && $access->capability->manage_forms) {
                $projects->push($project);
            }
        }

        return Inertia::render('Designer/Index', [
            'projects' => $projects,
        ]);
    }

    public function create(Project $project): RedirectResponse|Response
    {
        if (! Auth::user()->can('manageForms', $project)) {
            return $this->denyNoAccess();
        }

        return Inertia::render('Designer/Form', [
            'project' => $project,
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
        ]);

        if (! Auth::user()->can('manageForms', $project)) {
            return $this->denyNoAccess();
        }

        // Bind the form to the authorized route project, never to a
        // project id supplied in the request body.
        $form = InterviewForm::create([
            'project_id' => $project->id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()
            ->route('designer.form.edit', [
                'project' => $project,
                'form' => $form,
            ])
            ->with('message', 'designer.form_create_success')
            ->with('message_type', 'success');
    }

    public function update(
        Request $request,
        Project $project,
        InterviewForm $form
    ): RedirectResponse {
        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
        ]);

        if (! Auth::user()->can('manageForms', $project)) {
            return $this->denyNoAccess();
        }

        self::ensureFormBelongsToProject($project, $form);

        $form->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()
            ->route('designer.index')
            ->with('message', 'designer.form_update_success')
            ->with('message_type', 'success');
    }

    public function destroy(
        Project $project,
        InterviewForm $form
    ): RedirectResponse {
        if (! Auth::user()->can('manageForms', $project)) {
            return $this->denyNoAccess();
        }

        self::ensureFormBelongsToProject($project, $form);

        foreach ($form->instances as $instance) {
            foreach ($instance->answers as $answer) {
                $answer->delete();
            }

            $instance->delete();
        }

        foreach ($form->sections as $section) {
            foreach ($section->items as $item) {
                $item->delete();
            }

            $section->delete();
        }

        $form->delete();

        return redirect()
            ->route('designer.index')
            ->with('message', 'designer.form_delete_success')
            ->with('message_type', 'success');
    }

    public function toggle(
        Project $project,
        InterviewForm $form
    ): RedirectResponse {
        if (! Auth::user()->can('manageForms', $project)) {
            return $this->denyNoAccess();
        }

        self::ensureFormBelongsToProject($project, $form);

        $form->is_active = ! $form->is_active;
        $form->save();

        return redirect()
            ->route('designer.index')
            ->with('message', 'designer.form_toggle_success')
            ->with('message_type', 'success');
    }

    public function edit(
        Project $project,
        InterviewForm $form
    ): RedirectResponse|Response {
        if (! Auth::user()->can('manageForms', $project)) {
            return $this->denyNoAccess();
        }

        self::ensureFormBelongsToProject($project, $form);

        return Inertia::render('Designer/Form', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    private function denyNoAccess(): RedirectResponse
    {
        return redirect()
            ->route('projects.index')
            ->with('message', 'designer.no_access')
            ->with('message_type', 'error');
    }
}
