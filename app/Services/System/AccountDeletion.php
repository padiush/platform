<?php

namespace App\Services\System;

use App\Models\Project;
use App\Models\ProjectAccess;
use App\Models\ProjectCapability;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * What deleting an account takes with it, said before it happens, and the one
 * way to keep a person's projects when they leave: hand them to someone else.
 *
 * Deleting an account deletes the projects it owns, with everything in them
 * (User::booted). Its work in other people's projects stays, without them.
 */
class AccountDeletion
{
    public function __construct(private StorageUsage $usage) {}

    public function preview(User $user): array
    {
        $owned = Project::where('user_id', $user->id)->orderBy('name')->get(['id', 'name', 'is_example']);
        $usage = $this->usage->byProject();

        $collaborators = User::whereIn('id', ProjectAccess::whereIn('project_id', $owned->pluck('id'))->select('user_id'))
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->pluck('name');

        $interviews = DB::table('interview_instances')
            ->join('interview_forms', 'interview_forms.id', '=', 'interview_instances.interview_form_id')
            ->whereIn('interview_forms.project_id', $owned->pluck('id'))
            ->select('interview_forms.project_id', DB::raw('COUNT(*) as count'))
            ->groupBy('interview_forms.project_id')
            ->pluck('count', 'interview_forms.project_id');

        $projects = $owned->map(function (Project $project) use ($usage, $interviews) {
            $stored = $usage->get($project->id, ['audio' => 0, 'photo' => 0, 'files' => 0]);

            return [
                'id' => $project->id,
                'name' => $project->name,
                'is_example' => (bool) $project->is_example,
                'interviews' => (int) ($interviews[$project->id] ?? 0),
                'bytes' => $stored['audio'] + $stored['photo'],
                'files' => $stored['files'],
            ];
        });

        return [
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            'projects' => $projects->values(),
            'collaborators' => [
                'count' => $collaborators->count(),
                'names' => $collaborators->take(3)->values(),
            ],
            'files' => $projects->sum('files'),
            'bytes' => $projects->sum('bytes'),
            'other_projects' => ProjectAccess::where('user_id', $user->id)
                ->whereNotIn('project_id', $owned->pluck('id'))
                ->count(),
            // An example is the owner's own copy of invented data: it goes
            // with them, never to someone else.
            'transferable' => $projects->where('is_example', false)->count(),
        ];
    }

    /**
     * Give everything $from owns, except an example, to $to, who is made an
     * administrator of each project so they can run it.
     *
     * @return int how many projects changed hands
     */
    public function transfer(User $from, User $to): int
    {
        return DB::transaction(function () use ($from, $to) {
            $manage = ProjectCapability::where('manage_project', true)->value('id');
            $projects = Project::where('user_id', $from->id)->where('is_example', false)->get();

            foreach ($projects as $project) {
                $project->user_id = $to->id;
                $project->save();

                ProjectAccess::updateOrCreate(
                    ['project_id' => $project->id, 'user_id' => $to->id],
                    ['project_capability_id' => $manage],
                );
            }

            return $projects->count();
        });
    }
}
