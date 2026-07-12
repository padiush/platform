<?php

namespace App\Http\Controllers;

use App\Models\InterviewForm;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Project;
use App\Services\FormStructureService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InterviewDesignerController extends Controller
{
    /**
     * Aborts the request (403 JSON or redirect with a flash) unless the user
     * has manage_forms on the project and the form belongs to it.
     */
    private static function verifyAccess(
        Project $project,
        InterviewForm $form
    ): void {
        if (! Auth::user()->can('manageForms', $project)) {
            self::deny('designer.no_access');
        }

        if ($form->project_id !== $project->id) {
            self::deny('designer.form_not_found');
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
                    ->route('designer.index')
                    ->with('message', $message)
                    ->with('message_type', 'error')
        );
    }

    public function designer(
        Project $project,
        InterviewForm $form
    ): Response|RedirectResponse {
        self::verifyAccess($project, $form);

        return Inertia::render('Designer/Wizard', [
            'project' => $project,
            'form' => $form,
            'structure' => self::structurePayload($form),
            'instancesCount' => $form->instances()->count(),
        ]);
    }

    /**
     * Replace the form's whole structure with the submitted draft. The heavy
     * lifting (reconciliation, answer-detach guard) lives in
     * FormStructureService; this method maps its outcome to responses.
     */
    public function updateStructure(
        Request $request,
        Project $project,
        InterviewForm $form,
        FormStructureService $structureService
    ): JsonResponse {
        self::verifyAccess($project, $form);

        $validated = $request->validate([
            'confirm_detach' => 'boolean',
            'sections' => 'present|array',
            'sections.*.id' => 'nullable|integer',
            'sections.*.name' => 'required|string|max:255',
            'sections.*.repeatable' => 'boolean',
            'sections.*.items' => 'present|array',
            'sections.*.items.*.id' => 'nullable|integer',
            'sections.*.items.*.label' => 'required|string|max:255',
            'sections.*.items.*.name' => 'required|string|max:255',
            'sections.*.items.*.type' => 'required|string|in:text,number,date,multi,select',
            'sections.*.items.*.required' => 'boolean',
            'sections.*.items.*.link_to_species' => 'boolean',
            'sections.*.items.*.min' => 'nullable|numeric',
            'sections.*.items.*.max' => 'nullable|numeric',
            'sections.*.items.*.step' => 'nullable|numeric',
            'sections.*.items.*.options' => 'nullable|array',
            'sections.*.items.*.options.*' => 'nullable|string|max:255',
        ]);

        $result = $structureService->apply(
            $form,
            $validated['sections'],
            (bool) ($validated['confirm_detach'] ?? false)
        );

        return match ($result['status']) {
            'invalid_section' => response()->json(
                ['message' => 'designer.section_not_found', 'message_type' => 'error'],
                403
            ),
            'invalid_item' => response()->json(
                ['message' => 'designer.item_not_found', 'message_type' => 'error'],
                403
            ),
            'blocked_moves' => response()->json(
                [
                    'message' => 'designer.move_has_answers',
                    'message_type' => 'error',
                    'items' => $result['items'],
                ],
                422
            ),
            'conflict' => response()->json(
                [
                    'message' => 'designer.detach_confirmation',
                    'requires_confirmation' => true,
                    'detaching' => $result['detaching'],
                    'total_answers' => $result['total_answers'],
                ],
                409
            ),
            default => response()->json([
                'message' => 'designer.structure_saved',
                'message_type' => 'success',
                'structure' => self::structurePayload($form->refresh()),
            ]),
        };
    }

    /**
     * Sections with their items in display order, plus per-item recorded
     * answer counts so the designer can warn before destructive edits.
     */
    private static function structurePayload(InterviewForm $form): array
    {
        return $form
            ->sections()
            ->with(['items' => fn ($query) => $query->orderBy('order')->withCount('answers')])
            ->orderBy('order')
            ->get()
            ->map(fn ($section) => [
                'id' => $section->id,
                'name' => $section->name,
                'repeatable' => (bool) $section->repeatable,
                'items' => $section->items->map(fn ($item) => [
                    'id' => $item->id,
                    'label' => $item->label,
                    'name' => $item->name,
                    'type' => $item->type,
                    'required' => (bool) $item->required,
                    'link_to_species' => (bool) $item->link_to_species,
                    'min' => $item->min,
                    'max' => $item->max,
                    'step' => $item->step,
                    'options' => $item->options ?? [],
                    'answers_count' => $item->answers_count,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    public function preview(
        Project $project,
        InterviewForm $form
    ): Response|RedirectResponse {
        self::verifyAccess($project, $form);

        $sections = InterviewSection::where('interview_form_id', $form->id)
            ->orderBy('order', 'asc')
            ->get();

        foreach ($sections as $section) {
            $items = InterviewItem::where('interview_section_id', $section->id)
                ->orderBy('order', 'asc')
                ->get();

            $section->items = $items;
        }
        $form->sections = $sections;

        return Inertia::render('Designer/Preview', [
            'project' => $project,
            'form' => $form,
        ]);
    }
}
