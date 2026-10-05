<?php

namespace Tests\Feature;

use App\Models\FieldRecord;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\Media;
use App\Models\Project;
use App\Models\ProjectAccess;
use App\Models\ProjectCapability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Deleting anything that holds recordings or photographs deletes the stored
 * files too, not only their rows.
 */
class StoredMediaDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');
    }

    /** A stored file, and the row that names it. */
    private function stored(array $owner): Media
    {
        $key = 'projects/'.Str::uuid().'.m4a';
        Storage::disk('s3')->put($key, 'audio');

        return Media::create([
            ...$owner,
            'client_id' => (string) Str::uuid(),
            'kind' => Media::KIND_AUDIO,
            'storage_disk' => 's3',
            'storage_key' => $key,
            'content_type' => 'audio/mp4',
            'byte_size' => 5,
            'status' => Media::STATUS_STORED,
        ]);
    }

    private function interviewIn(Project $project): InterviewInstance
    {
        $form = InterviewForm::factory()->create(['project_id' => $project->id]);

        return InterviewInstance::factory()->create(['interview_form_id' => $form->id]);
    }

    private function recordIn(Project $project): FieldRecord
    {
        return FieldRecord::factory()->create(['project_id' => $project->id]);
    }

    private function assertGone(Media $media): void
    {
        Storage::disk('s3')->assertMissing($media->storage_key);
        $this->assertNull(Media::find($media->id));
    }

    private function assertKept(Media $media): void
    {
        Storage::disk('s3')->assertExists($media->storage_key);
        $this->assertNotNull(Media::find($media->id));
    }

    public function test_deleting_a_field_record_deletes_its_files(): void
    {
        $project = Project::factory()->create();
        $record = $this->recordIn($project);
        $photo = $this->stored(['field_record_id' => $record->id]);
        $other = $this->stored(['field_record_id' => $this->recordIn($project)->id]);

        $record->delete();

        $this->assertGone($photo);
        $this->assertKept($other);
    }

    public function test_deleting_an_interview_deletes_its_recordings(): void
    {
        $instance = $this->interviewIn(Project::factory()->create());
        $audio = $this->stored(['interview_instance_id' => $instance->id]);

        $instance->delete();

        $this->assertGone($audio);
    }

    public function test_deleting_a_form_deletes_its_interviews_recordings(): void
    {
        $instance = $this->interviewIn(Project::factory()->create());
        $audio = $this->stored(['interview_instance_id' => $instance->id]);

        $instance->form->delete();

        $this->assertGone($audio);
    }

    public function test_deleting_a_project_deletes_every_file_in_it(): void
    {
        $project = Project::factory()->create();
        $audio = $this->stored(['interview_instance_id' => $this->interviewIn($project)->id]);
        $photo = $this->stored(['field_record_id' => $this->recordIn($project)->id]);
        $elsewhere = $this->stored(['field_record_id' => $this->recordIn(Project::factory()->create())->id]);
        ProjectAccess::create([
            'project_id' => $project->id,
            'user_id' => $project->user_id,
            'project_capability_id' => ProjectCapability::where('manage_project', true)->value('id'),
        ]);

        $project->delete();

        $this->assertGone($audio);
        $this->assertGone($photo);
        $this->assertKept($elsewhere);
    }

    /** An account takes the projects it owns with it, files and all. */
    public function test_deleting_accounts_deletes_the_files_of_the_projects_they_own(): void
    {
        $admin = User::factory()->create(['system_admin' => true]);
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $kept = User::factory()->create();
        $alicePhoto = $this->stored(['field_record_id' => $this->recordIn(Project::factory()->create(['user_id' => $alice->id]))->id]);
        $bobAudio = $this->stored(['interview_instance_id' => $this->interviewIn(Project::factory()->create(['user_id' => $bob->id]))->id]);
        $keptPhoto = $this->stored(['field_record_id' => $this->recordIn(Project::factory()->create(['user_id' => $kept->id]))->id]);

        $this->actingAs($admin)->delete(route('system.users.delete', $alice));
        $this->actingAs($admin)->delete(route('system.users.bulk-delete'), ['ids' => [$bob->id]]);

        $this->assertGone($alicePhoto);
        $this->assertGone($bobAudio);
        $this->assertKept($keptPhoto);
    }

    /** A deletion that is rolled back keeps its rows, and so keeps their bytes. */
    public function test_a_rolled_back_deletion_keeps_the_files(): void
    {
        $record = $this->recordIn(Project::factory()->create());
        $photo = $this->stored(['field_record_id' => $record->id]);

        try {
            DB::transaction(function () use ($record) {
                $record->delete();
                throw new \RuntimeException('changed my mind');
            });
        } catch (\RuntimeException) {
            // Expected.
        }

        $this->assertKept($photo);
    }

    /** Storage being down does not stop someone deleting their work. */
    public function test_a_storage_failure_does_not_undo_the_deletion(): void
    {
        $record = $this->recordIn(Project::factory()->create());
        $photo = $this->stored(['field_record_id' => $record->id]);

        Storage::shouldReceive('disk')->with('s3')->andThrow(new \RuntimeException('storage is down'));

        $record->delete();

        $this->assertNull(Media::find($photo->id));
        $this->assertNull(FieldRecord::find($record->id));
    }
}
