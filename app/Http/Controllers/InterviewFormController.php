<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\InterviewForm;
use App\Models\ProjectAccess;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InterviewFormController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        $accesses = ProjectAccess::where('user_id', Auth::id())->get();

        $projects = collect();

        foreach ($accesses as $access) {
            $project = Project::find($access->project_id);
            $project->load('interviewForms', 'interviewForms.instances');

            if (
                !$project->finished &&
                Auth::user()->hasCapabilityOnProject($project, 'manage_forms')
            ) {
                $projects->push($project);
            }
        }

        if ($projects->count() == 0) {
            return redirect()
                ->route('projects.index')
                ->with(
                    'error',
                    'No tienes proyectos activos para diseñar entrevistas.'
                );
        }

        return Inertia::render('Designer/Index', [
            'projects' => $projects,
        ]);
    }

    public function create(Project $project): RedirectResponse|Response
    {
        $access = ProjectAccess::where('user_id', Auth::id())
            ->where('project_id', $project->id)
            ->first();

        if (
            !$access ||
            !Auth::user()->hasCapabilityOnProject($project, 'manage_forms')
        ) {
            return redirect()
                ->route('projects.index')
                ->with('message', 'designer.no_access')
                ->with('messsage_type', 'error');
        }

        return Inertia::render('Designer/Form', [
            'project' => $project,
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $access = ProjectAccess::where('user_id', Auth::id())
            ->where('project_id', $project->id)
            ->first();

        if (
            !$access ||
            !Auth::user()->hasCapabilityOnProject($project, 'manage_forms')
        ) {
            return redirect()
                ->route('projects.index')
                ->with('message', 'designer.no_access')
                ->with('messsage_type', 'error');
        }

        $form = InterviewForm::create([
            'project_id' => $request->project_id,
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

        $access = ProjectAccess::where('user_id', Auth::id())
            ->where('project_id', $project->id)
            ->first();

        if (
            !$access ||
            !Auth::user()->hasCapabilityOnProject($project, 'manage_forms')
        ) {
            return redirect()
                ->route('projects.index')
                ->with('message', 'designer.no_access')
                ->with('messsage_type', 'error');
        }

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
        $access = ProjectAccess::where('user_id', Auth::id())
            ->where('project_id', $project->id)
            ->first();

        if (
            !$access ||
            !Auth::user()->hasCapabilityOnProject($project, 'manage_forms')
        ) {
            return redirect()
                ->route('projects.index')
                ->with('message', 'designer.no_access')
                ->with('message_type', 'error');
        }

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
        $access = ProjectAccess::where('user_id', Auth::id())
            ->where('project_id', $project->id)
            ->first();

        if (
            !$access ||
            !Auth::user()->hasCapabilityOnProject($project, 'manage_forms')
        ) {
            return redirect()
                ->route('projects.index')
                ->with('message', 'designer.no_access')
                ->with('message_type', 'error');
        }

        $form->is_active = !$form->is_active;
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
        $access = ProjectAccess::where('user_id', Auth::id())
            ->where('project_id', $project->id)
            ->first();

        if (
            !$access ||
            !Auth::user()->hasCapabilityOnProject($project, 'manage_forms')
        ) {
            return redirect()
                ->route('projects.index')
                ->with('message', 'designer.no_access')
                ->with('message_type', 'error');
        }

        return Inertia::render('Designer/Form', [
            'project' => $project,
            'form' => $form,
        ]);
    }
}
