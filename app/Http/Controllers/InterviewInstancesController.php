<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\ProjectAccess;
use App\Models\Project;
use App\Models\User;
use App\Models\InstanceAnswer;
use App\Models\InterviewSection;
use App\Models\InterviewItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InterviewInstancesController extends Controller
{
    private static function verifyAccess(
        Project $project,
        InterviewForm $form = null,
        InterviewInstance $instance = null,
        string $permission = 'record_data'
    ): RedirectResponse|bool {
        $access = ProjectAccess::where('user_id', Auth::id())
            ->where('project_id', $project->id)
            ->first();

        if (
            !$access ||
            !Auth::user()->hasCapabilityOnProject($project, $permission)
        ) {
            return redirect()
                ->route('interviews.index')
                ->with('message', 'interviews.no_access')
                ->with('messsage_type', 'error');
        }

        // Check if the form belongs to the project
        if ($form && $form->project_id !== $project->id) {
            return redirect()
                ->route('interviews.index')
                ->with('message', 'interviews.form_not_found')
                ->with('messsage_type', 'error');
        }

        // Check if the instance belongs to the form
        if ($instance && $instance->interview_form_id !== $form->id) {
            return redirect()
                ->route('interviews.index')
                ->with('message', 'interviews.instance_not_found')
                ->with('messsage_type', 'error');
        }

        return true;
    }

    public function index(): Response|RedirectResponse
    {
        $accesses = ProjectAccess::where('user_id', Auth::id())->get();

        $projects = collect();

        foreach ($accesses as $access) {
            $project = Project::find($access->project_id);

            if (
                !$project->finished &&
                Auth::user()->hasCapabilityOnProject($project, 'record_data')
            ) {
                $project->load(
                    'activeInterviewForms',
                    'activeInterviewForms.instances'
                );
                $projects->push($project);
            }
        }

        if ($projects->count() == 0) {
            return redirect()
                ->route('projects.index')
                ->with('message', 'interviews.no_projects_available')
                ->with('messsage_type', 'error');
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
            ->with('messsage_type', 'success');
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
                fn($instance) => [
                    'id' => $instance->id,
                    'created_at' => $instance->created_at->format('Y-m-d H:i'),
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

        if (!self::verifyAccess($project, $form, $instance)) {
            return redirect()
                ->route('projects.index')
                ->with(
                    'error',
                    'No tienes permisos para tomar entrevistas en este proyecto.'
                );
        }

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

        $item = InterviewItem::findOrFail($validated['item_id']);
        $repeatableIndex = $validated['repeatable_index'] ?? null;

        // Find or create the answer
        $answer = InstanceAnswer::firstOrNew([
            'interview_instance_id' => $instance->id,
            'interview_item_id' => $item->id,
            'repeatable_index' => $repeatableIndex,
            'interview_section_id' => $item->interview_section_id ?? null,
            'catalog_species_id' => $item->link_to_species ?? null,
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
}
