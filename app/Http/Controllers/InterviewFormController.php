<?php

namespace App\Http\Controllers;

use App\Models\InterviewForm;
use App\Models\Project;
use App\Models\User;
use App\Services\ActiveProject;
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
                    ->route('designer.index', ['project' => $project->id])
                    ->with('message', 'designer.form_not_found')
                    ->with('message_type', 'error')
            );
        }
    }

    /**
     * The project's forms. A finished project takes no new forms, so its
     * forms are not offered for editing either; its interviews stay readable
     * in the data views.
     */
    public function index(Project $project): Response|RedirectResponse
    {
        if (! Auth::user()->can('manageForms', $project)) {
            return $this->denyNoAccess();
        }

        if ($project->finished) {
            return $this->denyNoAccess('designer.project_finished');
        }

        return Inertia::render('Designer/Index', [
            'project' => ['id' => $project->id, 'name' => $project->name],
            'forms' => $project->interviewForms()
                ->withCount('instances')
                ->get(['id', 'project_id', 'name', 'description', 'is_active'])
                ->map(fn (InterviewForm $form) => [
                    'id' => $form->id,
                    'name' => $form->name,
                    'description' => $form->description,
                    'is_active' => (bool) $form->is_active,
                    'instances_count' => $form->instances_count,
                ])
                ->all(),
        ]);
    }

    public function create(Project $project): RedirectResponse
    {
        if (! Auth::user()->can('manageForms', $project)) {
            return $this->denyNoAccess();
        }

        // The form-details form is a modal on the project's forms; deep-link
        // opens it there.
        return redirect()->route('designer.index', ['project' => $project->id, 'create' => 1]);
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

        // The details are captured in the modal; land back on the list, where
        // the new form is ready to open in the Wizard.
        return redirect()
            ->route('designer.index', ['project' => $project->id])
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
            ->route('designer.index', ['project' => $project->id])
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
            ->route('designer.index', ['project' => $project->id])
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
            ->route('designer.index', ['project' => $project->id])
            ->with('message', 'designer.form_toggle_success')
            ->with('message_type', 'success');
    }

    public function edit(
        Project $project,
        InterviewForm $form
    ): RedirectResponse {
        if (! Auth::user()->can('manageForms', $project)) {
            return $this->denyNoAccess();
        }

        self::ensureFormBelongsToProject($project, $form);

        return redirect()->route('designer.index', ['project' => $project->id, 'edit' => $form->id]);
    }

    /**
     * Turned away: to the overview of the project the user works in, which
     * every member can open.
     */
    private function denyNoAccess(string $message = 'designer.no_access'): RedirectResponse
    {
        return redirect()
            ->to(app(ActiveProject::class)->home(request()))
            ->with('message', $message)
            ->with('message_type', 'error');
    }
}
