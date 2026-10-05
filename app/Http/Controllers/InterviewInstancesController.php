<?php

namespace App\Http\Controllers;

use App\Models\InstanceAnswer;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Project;
use App\Models\User;
use App\Services\ActiveProject;
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
            // To the overview of the project the user works in, which every
            // member can open; the interviews could turn them away again.
            self::deny('interviews.no_access', app(ActiveProject::class)->home(request()));
        }

        // The address names the project; the form and the interview must be
        // of it, or ids from another project could be mixed in.
        if ($form && $form->project_id !== $project->id) {
            self::deny('interviews.form_not_found', route('interviews.index', ['project' => $project->id]));
        }

        if ($instance && $instance->interview_form_id !== $form?->id) {
            self::deny('interviews.instance_not_found', route('interviews.index', ['project' => $project->id]));
        }
    }

    private static function deny(string $message, string $to): never
    {
        throw new HttpResponseException(
            request()->expectsJson()
                ? response()->json(
                    ['message' => $message, 'message_type' => 'error'],
                    403
                )
                : redirect()
                    ->to($to)
                    ->with('message', $message)
                    ->with('message_type', 'error')
        );
    }

    /** A finished project takes no new interviews and no new answers. */
    private static function denyIfFinished(Project $project): void
    {
        if ($project->finished) {
            self::deny('interviews.project_finished', app(ActiveProject::class)->home(request()));
        }
    }

    /** The project's active forms, each to start an interview on or list its own. */
    public function index(Project $project): Response|RedirectResponse
    {
        self::verifyAccess($project);
        self::denyIfFinished($project);

        return Inertia::render('Interviews/Index', [
            'project' => ['id' => $project->id, 'name' => $project->name],
            'forms' => $project->activeInterviewForms()
                ->withCount('instances')
                ->get()
                ->map(fn (InterviewForm $form) => [
                    'id' => $form->id,
                    'name' => $form->name,
                    'instances_count' => $form->instances_count,
                ])
                ->all(),
        ]);
    }

    public function create(Project $project, InterviewForm $form): RedirectResponse
    {
        self::verifyAccess($project, $form);
        self::denyIfFinished($project);

        $instance = InterviewInstance::create([
            'interview_form_id' => $form->id,
            'user_id' => Auth::id(),
        ]);

        return redirect()
            ->route('interviews.show', [
                'project' => $project->id,
                'instance' => $instance->id,
            ])
            ->with('message', 'interviews.instance_created')
            ->with('message_type', 'success');
    }

    public function list(Project $project, InterviewForm $form): Response|RedirectResponse
    {
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

    public function show(Project $project, InterviewInstance $instance): Response|RedirectResponse
    {
        $form = $instance->form;

        self::verifyAccess($project, $form, $instance);
        self::denyIfFinished($project);
        // Eager load sections and items
        $form->load('sections.items');
        $form->sections = $form->sections->sortBy('order')->values();

        foreach ($form->sections as $section) {
            $section->items = $section->items->sortBy('order')->values();
        }

        // Load answers for this instance, each with the field records made
        // from it (ADR 0011) — the other side of the record's link.
        $answers = $instance
            ->answers()
            ->with('fieldRecords')
            ->get()
            ->map(function ($answer) {
                return [
                    'item_id' => $answer->interview_item_id,
                    'section_id' => $answer->interview_section_id,
                    'repeatable_index' => $answer->repeatable_index,
                    'value' => $answer->answer,
                    'field_records' => $answer->fieldRecords
                        ->sortBy('created_at')
                        ->values()
                        ->map(fn ($record) => [
                            'id' => $record->id,
                            'accession_number' => $record->accession_number,
                            'collection_number' => $record->collection_number,
                            'was_collected' => $record->wasCollected(),
                        ])
                        ->all(),
                ];
            });

        // The page header shows who recorded the interview and when,
        // instead of the raw instance id.
        $instance->load('user');

        return Inertia::render('Interviews/Instance', [
            'project' => $project,
            'form' => $form,
            'instance' => $instance,
            'answers' => $answers,
            // The records are listed either way; they link to the catalog
            // only for someone who can read it.
            'canViewCatalog' => (bool) Auth::user()->can('viewCatalog', $project),
        ]);
    }

    public function saveAnswer(
        Request $request,
        Project $project,
        InterviewInstance $instance
    ): JsonResponse {
        $validated = $request->validate([
            'item_id' => 'required|integer|exists:interview_items,id',
            'repeatable_index' => 'nullable|integer',
            'value' => 'nullable',
        ]);

        $form = $instance->form;

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

        // Stamp the edit-time so web corrections take part in the companion
        // sync's last-writer-wins policy (docs/decisions/0004-offline-sync-model.md).
        $answer->edited_at = now();

        $answer->save();

        return response()->json(['success' => true]);
    }

    public function destroyRepeatableSet(
        Request $request,
        Project $project,
        InterviewInstance $instance,
        InterviewSection $section
    ): RedirectResponse {
        $indexToRemove = $request->input('repeatable_index');

        // Validate access
        $form = $instance->form;

        self::verifyAccess($project, $form, $instance);

        // The section must belong to the instance's form, otherwise this
        // could delete answers from another form's section.
        if ($section->interview_form_id !== $form->id) {
            self::deny('interviews.instance_not_found', route('interviews.index', ['project' => $project->id]));
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
            ->route('interviews.show', ['project' => $project->id, 'instance' => $instance->id])
            ->with('message', 'interviews.repeatable_set_deleted')
            ->with('message_type', 'success');
    }

    public function destroy(Project $project, InterviewInstance $instance): RedirectResponse
    {
        $form = $instance->form;

        self::verifyAccess($project, $form, $instance);

        foreach ($instance->answers as $answer) {
            $answer->delete();
        }

        $instance->delete();

        return redirect()
            ->route('interviews.instances', ['project' => $project->id, 'form' => $form->id])
            ->with('message', 'interviews.instance_deleted')
            ->with('message_type', 'success');
    }
}
