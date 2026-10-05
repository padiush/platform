<?php

namespace App\Http\Controllers;

use App\Models\FieldRecord;
use App\Models\InterviewInstance;
use App\Models\Media;
use App\Models\Project;
use App\Services\ActiveProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A project at a glance: how much has been gathered, what was added lately,
 * and what is still waiting on someone. It is where the sidebar's project
 * opens, so it answers "where were we?" before anything is clicked.
 *
 * Each part is shown to the roles that can act on it. Counts are figures
 * rather than records, so every member sees those; who recorded which
 * interview, or what a record is called, follows the same capability as the
 * page that holds it.
 */
class ProjectOverviewController extends Controller
{
    private const RECENT = 5;

    /** Where a signed-in user lands: their project's overview, if they have one. */
    public function landing(Request $request, ActiveProject $active): Response|RedirectResponse
    {
        $project = $active->resolve($request);

        if ($project) {
            return redirect()->route('projects.overview', $project);
        }

        return Inertia::render('Dashboard');
    }

    public function show(Request $request, Project $project, ActiveProject $active): Response
    {
        $access = $active->accesses($request->user())->get($project->id);

        if (! $access) {
            abort(403);
        }

        $can = $access->capability;
        $records = $can->view_catalog;
        $interviews = $can->record_data || $can->manage_data;
        $data = $can->manage_data || $can->generate_reports;

        $undetermined = $records
            ? $project->fieldRecords()
                ->whereDoesntHave('currentDetermination', fn ($query) => $query->whereNotNull('catalog_species_id'))
                ->count()
            : null;

        return Inertia::render('Projects/Overview', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'finished' => (bool) $project->finished,
            ],
            'counts' => [
                'interviews' => $project->interviewInstances()->count(),
                'field_records' => $project->fieldRecords()->count(),
                'species' => $project->catalogSpecies()->count(),
                'unlinked_answers' => $project->unlinkedAnswers()->count(),
            ],
            'waiting' => [
                // Registered from a device and never completed: the phone
                // still holds the file, or lost the connection mid-send.
                'pending_media' => $interviews || $records
                    ? $project->media()->where('status', Media::STATUS_PENDING)->count()
                    : null,
                'undetermined_records' => $undetermined,
                'unlinked_answers' => $data ? $project->unlinkedAnswers()->count() : null,
            ],
            'recentInterviews' => $can->record_data
                ? $project->interviewInstances()
                    ->with(['form:id,name', 'user:id,name'])
                    ->latest('interview_instances.created_at')
                    ->limit(self::RECENT)
                    ->get()
                    ->map(fn (InterviewInstance $instance) => [
                        'id' => $instance->id,
                        'form' => $instance->form?->name,
                        'recorded_by' => $instance->user?->name,
                        'created_at' => $instance->created_at?->toIso8601String(),
                    ])
                    ->all()
                : null,
            'recentRecords' => $records
                ? $project->fieldRecords()
                    ->with('currentDetermination.species')
                    ->latest()
                    ->limit(self::RECENT)
                    ->get()
                    ->map(fn (FieldRecord $record) => [
                        'id' => $record->id,
                        'vernacular_name' => $record->vernacular_name,
                        'collection_number' => $record->collection_number,
                        'accession_number' => $record->accession_number,
                        'was_collected' => $record->wasCollected(),
                        'species' => $record->currentDetermination?->species === null ? null : [
                            'genus' => $record->currentDetermination->species->genus,
                            'name' => $record->currentDetermination->species->name,
                        ],
                        'collected_on' => $record->collected_on?->toDateString(),
                    ])
                    ->all()
                : null,
            // The same sections the sidebar offers, for the shortcuts.
            'sections' => $active->sections($request->user(), $project),
            'can' => [
                'link_species' => (bool) $can->manage_data,
                'export' => (bool) ($can->manage_data || $can->generate_reports),
            ],
        ]);
    }
}
