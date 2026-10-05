<?php

namespace App\Services\System;

use App\Models\Media;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * How much stored media takes, by project and by the person who owns the
 * project: the sizes the admin panel shows and, from 1.2.0, what a storage
 * limit is measured against. Only stored files count; what a device has
 * announced but not sent yet is reported apart.
 *
 * Nothing here reads a file, a name inside a project or an answer: only the
 * size, kind and owner of each media row.
 */
class StorageUsage
{
    /**
     * Stored bytes and files per project, by kind.
     *
     * @return Collection<int, array{audio: int, photo: int, files: int}>
     */
    public function byProject(?\DateTimeInterface $since = null): Collection
    {
        $interviews = DB::table('media')
            ->join('interview_instances', 'interview_instances.id', '=', 'media.interview_instance_id')
            ->join('interview_forms', 'interview_forms.id', '=', 'interview_instances.interview_form_id')
            ->select('interview_forms.project_id as project_id', 'media.kind', DB::raw('SUM(media.byte_size) as bytes'), DB::raw('COUNT(*) as files'))
            ->where('media.status', Media::STATUS_STORED)
            ->when($since, fn ($query) => $query->where('media.created_at', '>=', $since))
            ->groupBy('interview_forms.project_id', 'media.kind');

        $records = DB::table('media')
            ->join('field_records', 'field_records.id', '=', 'media.field_record_id')
            ->select('field_records.project_id as project_id', 'media.kind', DB::raw('SUM(media.byte_size) as bytes'), DB::raw('COUNT(*) as files'))
            ->where('media.status', Media::STATUS_STORED)
            ->when($since, fn ($query) => $query->where('media.created_at', '>=', $since))
            ->groupBy('field_records.project_id', 'media.kind');

        $usage = collect();

        foreach ($interviews->unionAll($records)->get() as $row) {
            $project = $usage->get($row->project_id, ['audio' => 0, 'photo' => 0, 'files' => 0]);
            $kind = $row->kind === Media::KIND_AUDIO ? 'audio' : 'photo';
            $project[$kind] += (int) $row->bytes;
            $project['files'] += (int) $row->files;
            $usage->put($row->project_id, $project);
        }

        return $usage;
    }

    /**
     * Per person: the projects they own and what is stored in them.
     *
     * @return Collection<int, array{projects: int, audio: int, photo: int, files: int, total: int}>
     */
    public function byOwner(?Collection $byProject = null): Collection
    {
        $byProject ??= $this->byProject();
        $owners = collect();

        foreach (Project::get(['id', 'user_id']) as $project) {
            $owner = $owners->get($project->user_id, ['projects' => 0, 'audio' => 0, 'photo' => 0, 'files' => 0, 'total' => 0]);
            $usage = $byProject->get($project->id, ['audio' => 0, 'photo' => 0, 'files' => 0]);

            $owner['projects']++;
            $owner['audio'] += $usage['audio'];
            $owner['photo'] += $usage['photo'];
            $owner['files'] += $usage['files'];
            $owner['total'] += $usage['audio'] + $usage['photo'];

            $owners->put($project->user_id, $owner);
        }

        return $owners;
    }

    /** @return array{audio: int, photo: int, files: int, total: int} */
    public function totals(Collection $byProject): array
    {
        $audio = (int) $byProject->sum('audio');
        $photo = (int) $byProject->sum('photo');

        return [
            'audio' => $audio,
            'photo' => $photo,
            'files' => (int) $byProject->sum('files'),
            'total' => $audio + $photo,
        ];
    }

    /** What is announced but not stored, and multipart uploads still open. */
    public function inFlight(): array
    {
        $open = Media::whereNotNull('upload_id');

        return [
            'pending' => Media::where('status', Media::STATUS_PENDING)->count(),
            'open_uploads' => (clone $open)->count(),
            'oldest_open_upload' => (clone $open)->min('updated_at'),
        ];
    }
}
