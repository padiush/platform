<?php

namespace App\Http\Controllers;

use App\Models\CollectingPermit;
use App\Models\Project;
use App\Services\ActiveProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The authorisations a project collects under.
 *
 * A reference record, and nothing more: nothing here checks that a permit is
 * genuine, current, or covers what was collected. An expiry date is shown
 * because it is written on the permit, not as a verdict.
 * See docs/decisions/0009-collecting-permits.md.
 */
class CollectingPermitController extends Controller
{
    public function index(Project $project): Response|RedirectResponse
    {
        $user = Auth::user();

        if (! $user->can('viewCatalog', $project)) {
            return $this->noAccess('catalogs.no_view_access');
        }

        $permits = $project->collectingPermits()
            ->withCount('fieldRecords')
            ->orderByDesc('issued_on')
            ->orderBy('reference')
            ->get();

        return Inertia::render('Records/Permits', [
            'project' => ['id' => $project->id, 'name' => $project->name],
            'permits' => $permits->map(fn (CollectingPermit $permit) => [
                'id' => $permit->id,
                'authority' => $permit->authority,
                'reference' => $permit->reference,
                'issued_on' => $permit->issued_on?->toDateString(),
                'expires_on' => $permit->expires_on?->toDateString(),
                'notes' => $permit->notes,
                'has_expired' => $permit->hasExpired(),
                // Deleting one leaves its collections standing, so the count
                // is what a confirmation needs to say out loud.
                'field_records_count' => $permit->field_records_count,
            ])->all(),
            'canEdit' => (bool) $user->can('editCatalog', $project),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        if ($denied = $this->denyUnlessEditable($project)) {
            return $denied;
        }

        $permit = new CollectingPermit($request->validate($this->rules($project)));
        $permit->project_id = $project->id;
        $permit->save();

        return back()
            ->with('message', 'catalogs.permits.saved')
            ->with('message_type', 'success');
    }

    public function update(
        Request $request,
        Project $project,
        CollectingPermit $permit
    ): RedirectResponse {
        if ($denied = $this->denyUnlessEditable($project)) {
            return $denied;
        }

        if ($permit->project_id !== $project->id) {
            return $this->notFound($project);
        }

        $permit->update($request->validate($this->rules($project, $permit)));

        return back()
            ->with('message', 'catalogs.permits.saved')
            ->with('message_type', 'success');
    }

    public function destroy(Project $project, CollectingPermit $permit): RedirectResponse
    {
        if ($denied = $this->denyUnlessEditable($project)) {
            return $denied;
        }

        if ($permit->project_id !== $project->id) {
            return $this->notFound($project);
        }

        // The collections taken under it survive with a null reference: a
        // fieldRecord outlives the paperwork, as it outlives a taxon.
        $permit->delete();

        return back()
            ->with('message', 'catalogs.permits.deleted')
            ->with('message_type', 'success');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(Project $project, ?CollectingPermit $permit = null): array
    {
        return [
            'authority' => 'required|string|max:255',
            'reference' => [
                'required',
                'string',
                'max:255',
                // Per project: the same reference may legitimately appear in
                // two studies run by the same researcher.
                Rule::unique('collecting_permits', 'reference')
                    ->where('project_id', $project->id)
                    ->ignore($permit?->getKey()),
            ],
            'issued_on' => 'nullable|date',
            'expires_on' => 'nullable|date|after_or_equal:issued_on',
            'notes' => 'nullable|string',
        ];
    }

    private function denyUnlessEditable(Project $project): ?RedirectResponse
    {
        return Auth::user()->can('editCatalog', $project) ? null : $this->noAccess();
    }

    /**
     * Turned away for want of the role: to the overview of the project the
     * user works in, which every member can open.
     */
    private function noAccess(string $message = 'catalogs.no_access'): RedirectResponse
    {
        return redirect()
            ->to(app(ActiveProject::class)->home(request()))
            ->with('message', $message)
            ->with('message_type', 'error');
    }

    private function notFound(Project $project): RedirectResponse
    {
        return redirect()
            ->route('catalogs.permits.index', ['project' => $project->id])
            ->with('message', 'catalogs.permits.not_found')
            ->with('message_type', 'error');
    }
}
