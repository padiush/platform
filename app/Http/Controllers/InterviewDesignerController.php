<?php

namespace App\Http\Controllers;

use App\Models\InterviewForm;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Project;
use App\Models\ProjectAccess;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InterviewDesignerController extends Controller
{
    /**
     * Aborts the request (403 JSON or redirect with a flash) unless the user
     * has manage_forms on the project and each given resource belongs to its
     * parent.
     */
    private static function verifyAccess(
        Project $project,
        ?InterviewForm $form = null,
        ?InterviewSection $section = null,
        ?InterviewItem $item = null
    ): void {
        $access = ProjectAccess::where('user_id', Auth::id())
            ->where('project_id', $project->id)
            ->first();

        if (
            ! $access ||
            ! Auth::user()->hasCapabilityOnProject($project, 'manage_forms')
        ) {
            self::deny('designer.no_access');
        }

        // Check if the form belongs to the project
        if ($form && $form->project_id !== $project->id) {
            self::deny('designer.form_not_found');
        }

        // Check if the section belongs to the form
        if ($section && $section->interview_form_id !== $form->id) {
            self::deny('designer.section_not_found');
        }

        // Check if the item belongs to the section
        if ($item && $item->interview_section_id !== $section->id) {
            self::deny('designer.item_not_found');
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
        ]);
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

    public function fetchSections(
        Project $project,
        InterviewForm $form
    ): JsonResponse {
        self::verifyAccess($project, $form);

        $sections = InterviewSection::where('interview_form_id', $form->id)
            ->orderBy('order', 'asc')
            ->get();

        return response()->json($sections);
    }

    public function createSection(
        Request $request,
        Project $project,
        InterviewForm $form
    ): JsonResponse {
        self::verifyAccess($project, $form);

        $name = $request->name ?? 'Nueva sección';
        $lastSection = InterviewSection::where('interview_form_id', $form->id)
            ->orderBy('order', 'desc')
            ->first();
        $order = $lastSection ? $lastSection->order + 1 : 1;

        InterviewSection::create([
            'interview_form_id' => $form->id,
            'name' => $name,
            'order' => $order,
            'repeatable' => $request->has('repeatable') ? true : false,
        ]);

        return response()->json([
            'message' => 'designer.section_created',
            'message_type' => 'success',
        ]);
    }

    public function renameSection(
        Request $request,
        Project $project,
        InterviewForm $form,
        InterviewSection $section
    ): JsonResponse {
        self::verifyAccess($project, $form, $section);

        $name = $request->name ?? 'Nueva sección';

        $section->name = $name;
        $section->save();

        return response()->json([
            'message' => 'designer.section_renamed',
            'message_type' => 'success',
        ]);
    }

    public function reorderSection(
        Request $request,
        Project $project,
        InterviewForm $form,
        InterviewSection $section
    ): JsonResponse {
        self::verifyAccess($project, $form, $section);

        $direction = $request->input('direction');

        $allSections = $form->sections()->orderBy('order')->get();

        $currentIndex = $allSections->search(fn ($s) => $s->id === $section->id);

        if ($currentIndex === false) {
            return response()->json(
                [
                    'message' => 'designer.section_not_found',
                    'message_type' => 'error',
                ],
                404
            );
        }

        $swapIndex =
            $direction === 'up'
                ? $currentIndex - 1
                : ($direction === 'down'
                    ? $currentIndex + 1
                    : null);

        if (
            $swapIndex === null ||
            $swapIndex < 0 ||
            $swapIndex >= $allSections->count()
        ) {
            return response()->json(
                [
                    'message' => 'designer.invalid_move',
                    'message_type' => 'error',
                ],
                400
            );
        }

        $otherSection = $allSections[$swapIndex];

        // Swap the order values
        $tempOrder = $section->order;
        $section->order = $otherSection->order;
        $otherSection->order = $tempOrder;

        $section->save();
        $otherSection->save();

        return response()->json([
            'message' => 'designer.section_reordered',
            'message_type' => 'success',
        ]);
    }

    public function toggleRepeatable(
        Request $request,
        Project $project,
        InterviewForm $form,
        InterviewSection $section
    ): JsonResponse {
        self::verifyAccess($project, $form, $section);

        $section->repeatable = $request->boolean('repeatable');
        $section->save();

        return response()->json([
            'message' => 'designer.repeatable_updated',
            'message_type' => 'success',
        ]);
    }

    public function deleteSection(
        Project $project,
        InterviewForm $form,
        InterviewSection $section
    ): JsonResponse {
        self::verifyAccess($project, $form, $section);

        // Delete all items in the section
        InterviewItem::where('interview_section_id', $section->id)->delete();

        // Delete the section
        $section->delete();

        return response()->json([
            'message' => 'designer.section_deleted',
            'message_type' => 'success',
        ]);
    }

    public function fetchItems(
        Project $project,
        InterviewForm $form,
        InterviewSection $section
    ): JsonResponse {
        self::verifyAccess($project, $form, $section);

        $items = InterviewItem::where('interview_section_id', $section->id)
            ->orderBy('order', 'asc')
            ->get();

        return response()->json($items);
    }

    public function createItem(
        Request $request,
        Project $project,
        InterviewForm $form,
        InterviewSection $section
    ): JsonResponse {
        self::verifyAccess($project, $form, $section);

        $name = $request->name ?? 'item';
        $lastItem = InterviewItem::where('interview_section_id', $section->id)
            ->orderBy('order', 'desc')
            ->first();
        $order = $lastItem ? $lastItem->order + 1 : 1;

        InterviewItem::create([
            'interview_section_id' => $section->id,
            'name' => $name,
            'order' => $order,
            'type' => $request->type ?? 'text',
        ]);

        return response()->json([
            'message' => 'designer.item_created',
            'message_type' => 'success',
        ]);
    }

    public function updateItem(
        Request $request,
        Project $project,
        InterviewForm $form,
        InterviewSection $section,
        InterviewItem $item
    ): JsonResponse {
        self::verifyAccess($project, $form, $section, $item);

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:text,number,date,multi,select',
            'min' => 'nullable|numeric',
            'max' => 'nullable|numeric',
            'step' => 'nullable|numeric',
            'options' => 'nullable|array',
            'required' => 'boolean',
            'link_to_species' => 'boolean',
        ]);

        $validated['name'] = Str::slug($validated['name'], '_');

        $item->update($validated);

        return response()->json([
            'message' => 'designer.item_updated',
            'message_type' => 'success',
        ]);
    }

    public function deleteItem(
        Project $project,
        InterviewForm $form,
        InterviewSection $section,
        InterviewItem $item
    ): JsonResponse {
        self::verifyAccess($project, $form, $section, $item);

        $item->delete();

        return response()->json([
            'message' => 'designer.item_deleted',
            'message_type' => 'success',
        ]);
    }

    public function reorderItem(
        Request $request,
        Project $project,
        InterviewForm $form,
        InterviewSection $section,
        InterviewItem $item
    ): JsonResponse {
        self::verifyAccess($project, $form, $section, $item);

        $direction = $request->input('direction');

        $items = $section->items()->orderBy('order')->get();
        $index = $items->search(fn ($i) => $i->id === $item->id);

        if ($index === false) {
            return response()->json(
                [
                    'message' => 'designer.item_not_found',
                    'message_type' => 'error',
                ],
                404
            );
        }

        $swapWithIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if ($swapWithIndex < 0 || $swapWithIndex >= $items->count()) {
            return response()->json(
                [
                    'message' => 'designer.invalid_move',
                    'message_type' => 'error',
                ],
                400
            );
        }

        $swapItem = $items[$swapWithIndex];

        // Swap orders
        $currentOrder = $item->order;
        $item->order = $swapItem->order;
        $swapItem->order = $currentOrder;

        $item->save();
        $swapItem->save();

        return response()->json([
            'message' => 'designer.item_reordered',
            'message_type' => 'success',
        ]);
    }
}
