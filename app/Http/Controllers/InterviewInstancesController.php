<?php

namespace App\Http\Controllers;

use App\Models\InstanceAnswer;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InterviewInstancesController extends Controller
{
    /**
     * Aborts the request (403 JSON or redirect with a flash) unless the user
     * has the given capability on the project and each given resource belongs
     * to its parent.
     */
    private static function verifyAccess(
        Project $project,
        ?InterviewForm $form = null,
        ?InterviewInstance $instance = null,
        string $permission = 'record_data'
    ): void {
        if (! Auth::user()->can(Str::camel($permission), $project)) {
            self::deny('interviews.no_access');
        }

        // Check if the form belongs to the project
        if ($form && $form->project_id !== $project->id) {
            self::deny('interviews.form_not_found');
        }

        // Check if the instance belongs to the form
        if ($instance && $instance->interview_form_id !== $form->id) {
            self::deny('interviews.instance_not_found');
        }
    }

    private static function deny(string $message): never
    {
        throw new HttpResponseException(
            request()->expectsJson()
                ? response()->json(
                    ['message' => $message, 'message_type' => 'error'],
                    403
                )
                : redirect()
                    ->route('interviews.index')
                    ->with('message', $message)
                    ->with('message_type', 'error')
        );
    }

    public function index(): Response|RedirectResponse
    {
        $accesses = Auth::user()
            ->projectAccesses()
            ->with(['project.activeInterviewForms.instances', 'capability'])
            ->get();

        $projects = collect();

        foreach ($accesses as $access) {
            $project = $access->project;

            if (! $project) {
                continue;
            }

            if (! $project->finished && $access->capability->record_data) {
                $projects->push($project);
            }
        }

        if ($projects->count() == 0) {
            return redirect()
                ->route('projects.index')
                ->with('message', 'interviews.no_projects_available')
                ->with('message_type', 'error');
        }

        return Inertia::render('Interviews/Index', [
            'projects' => $projects,
            'user' => Auth::user(),
        ]);
    }

    public function create(InterviewForm $form): RedirectResponse
    {
        $project = Project::find($form->project_id);

        self::verifyAccess($project, $form);

        if ($project->finished) {
            return redirect()
                ->route('projects.index')
                ->with('message', 'interviews.project_finished');
        }

        $instance = InterviewInstance::create([
            'interview_form_id' => $form->id,
            'user_id' => Auth::id(),
        ]);

        return redirect()
            ->route('interviews.show', [
                'instance' => $instance->id,
            ])
            ->with('message', 'interviews.instance_created')
            ->with('message_type', 'success');
    }

    public function list(InterviewForm $form): Response|RedirectResponse
    {
        $project = Project::findOrFail($form->project_id);

        self::verifyAccess($project, $form);

        $instances = InterviewInstance::with('user')
            ->where('interview_form_id', $form->id)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(
                fn ($instance) => [
                    'id' => $instance->id,
                    // Sent as ISO; the page formats it in the user's language.
                    'created_at' => $instance->created_at->toIso8601String(),
                    'user' => [
                        'id' => $instance->user->id,
                        'name' => $instance->user->name,
                    ],
                ]
            );

        return Inertia::render('Interviews/Instances', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'form' => [
                'id' => $form->id,
                'name' => $form->name,
            ],
            'instances' => $instances,
        ]);
    }

    public function show(InterviewInstance $instance): Response|RedirectResponse
    {
        $form = $instance->form;
        $project = $form->project;

        self::verifyAccess($project, $form, $instance);

        if ($project->finished) {
            return redirect()
                ->route('projects.index')
                ->with('message', 'interviews.project_finished');
        }

        // Eager load sections and items
        $form->load('sections.items');
        $form->sections = $form->sections->sortBy('order')->values();

        foreach ($form->sections as $section) {
            $section->items = $section->items->sortBy('order')->values();
        }

        // Load answers for this instance
        $answers = $instance
            ->answers()
            ->get()
            ->map(function ($answer) {
                return [
                    'item_id' => $answer->interview_item_id,
                    'section_id' => $answer->interview_section_id,
                    'repeatable_index' => $answer->repeatable_index,
                    'value' => $answer->answer,
                ];
            });

        return Inertia::render('Interviews/Instance', [
            'project' => $project,
            'form' => $form,
            'instance' => $instance,
            'answers' => $answers,
        ]);
    }

    public function saveAnswer(
        Request $request,
        InterviewInstance $instance
    ): JsonResponse {
        $validated = $request->validate([
            'item_id' => 'required|integer|exists:interview_items,id',
            'repeatable_index' => 'nullable|integer',
            'value' => 'nullable',
        ]);

        $form = $instance->form;
        $project = $form->project;

        self::verifyAccess($project, $form, $instance);

        if ($project->finished) {
            return response()->json(
                ['success' => false, 'message' => 'interviews.project_finished'],
                403
            );
        }

        $item = InterviewItem::findOrFail($validated['item_id']);

        // The item must belong to the same form as the instance
        if ($item->section->interview_form_id !== $form->id) {
            return response()->json(
                ['success' => false, 'message' => 'interviews.item_not_found'],
                422
            );
        }

        $repeatableIndex = $validated['repeatable_index'] ?? null;

        // Find or create the answer
        $answer = InstanceAnswer::firstOrNew([
            'interview_instance_id' => $instance->id,
            'interview_item_id' => $item->id,
            'repeatable_index' => $repeatableIndex,
            'interview_section_id' => $item->interview_section_id ?? null,
        ]);

        if (in_array($item->type, ['multi'])) {
            $answer->answer = json_encode($validated['value'] ?? []);
        } else {
            $answer->answer = $validated['value'];
        }

        $answer->save();

        return response()->json(['success' => true]);
    }

    public function destroyRepeatableSet(
        Request $request,
        InterviewInstance $instance,
        InterviewSection $section
    ): RedirectResponse {
        $indexToRemove = $request->input('repeatable_index');

        // Validate access
        $form = $instance->form;
        $project = $form->project;

        self::verifyAccess($project, $form, $instance);

        // The section must belong to the instance's form, otherwise this
        // could delete answers from another form's section.
        if ($section->interview_form_id !== $form->id) {
            self::deny('interviews.instance_not_found');
        }

        // Delete all answers for the section at that index
        InstanceAnswer::where('interview_instance_id', $instance->id)
            ->where('interview_section_id', $section->id)
            ->where('repeatable_index', $indexToRemove)
            ->delete();

        // Reindex answers: decrement indexes above the removed one
        InstanceAnswer::where('interview_instance_id', $instance->id)
            ->where('interview_section_id', $section->id)
            ->where('repeatable_index', '>', $indexToRemove)
            ->orderBy('repeatable_index')
            ->get()
            ->each(function ($answer) {
                $answer->repeatable_index -= 1;
                $answer->save();
            });

        // Redirect back to the instance view
        return redirect()
            ->route('interviews.show', ['instance' => $instance->id])
            ->with('message', 'interviews.repeatable_set_deleted')
            ->with('message_type', 'success');
    }

    public function destroy(InterviewInstance $instance): RedirectResponse
    {
        $form = $instance->form;
        $project = $form->project;

        self::verifyAccess($project, $form, $instance);

        foreach ($instance->answers as $answer) {
            $answer->delete();
        }

        $instance->delete();

        return redirect()
            ->route('interviews.instances', ['form' => $form->id])
            ->with('message', 'interviews.instance_deleted')
            ->with('message_type', 'success');
    }
}
