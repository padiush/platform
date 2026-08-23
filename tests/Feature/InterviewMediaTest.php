<?php

namespace Tests\Feature;

use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\Media;
use App\Models\Project;
use App\Models\ProjectAccess;
use App\Models\ProjectCapability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

/**
 * The companion has been syncing photographs and audio since it shipped, and
 * until now nothing on the web could show them.
 */
class InterviewMediaTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    private InterviewInstance $instance;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');

        $this->project = Project::factory()->create();
        $form = InterviewForm::factory()->create(['project_id' => $this->project->id]);
        $this->instance = InterviewInstance::factory()->create([
            'interview_form_id' => $form->id,
        ]);
    }

    private function medium(array $attributes = []): Media
    {
        $key = 'projects/1/audio.m4a';
        Storage::disk('s3')->put($key, 'bytes');

        return Media::create(array_merge([
            'interview_instance_id' => $this->instance->id,
            'client_id' => (string) Str::uuid(),
            'kind' => Media::KIND_PHOTO,
            'storage_disk' => 's3',
            'storage_key' => $key,
            'content_type' => 'image/jpeg',
            'status' => Media::STATUS_STORED,
        ], $attributes));
    }

    private function url(string $name, array $extra = []): string
    {
        return route($name, array_merge([
            'project' => $this->project->id,
            'instance' => $this->instance->id,
        ], $extra));
    }

    public function test_a_data_capable_user_can_list_what_was_captured()
    {
        $this->medium();

        $this->actingAs($this->userWithCapability($this->project, 'manage_data'))
            ->getJson($this->url('data.media.index'))
            ->assertOk()
            ->assertJsonCount(1, 'media')
            ->assertJsonPath('media.0.kind', Media::KIND_PHOTO);
    }

    public function test_it_carries_a_transcription_when_one_exists()
    {
        $this->medium([
            'kind' => Media::KIND_AUDIO,
            'content_type' => 'audio/mp4',
            'transcription_status' => 'done',
            'transcription_text' => 'lo llamamos cortez blanco',
        ]);

        $this->actingAs($this->userWithCapability($this->project, 'manage_data'))
            ->getJson($this->url('data.media.index'))
            ->assertOk()
            ->assertJsonPath('media.0.transcription_text', 'lo llamamos cortez blanco');
    }

    public function test_the_bytes_stream_to_someone_allowed_to_see_them()
    {
        $medium = $this->medium();

        $this->actingAs($this->userWithCapability($this->project, 'generate_reports'))
            ->get($this->url('data.media.show', ['medium' => $medium->id]))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');
    }

    public function test_someone_without_the_data_capability_is_refused()
    {
        $medium = $this->medium();

        // Every seeded role carries generate_reports, so a role that fails the
        // gate has to be built: this tests the rule rather than the seed. A
        // photograph of an informant's plot is no less theirs than what they
        // said, so it is gated exactly as the answers are.
        $user = User::factory()->create();
        ProjectAccess::factory()->create([
            'user_id' => $user->id,
            'project_id' => $this->project->id,
            'project_capability_id' => ProjectCapability::factory()->create([
                'manage_data' => false,
                'generate_reports' => false,
            ])->id,
        ]);

        $this->actingAs($user)
            ->getJson($this->url('data.media.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get($this->url('data.media.show', ['medium' => $medium->id]))
            ->assertRedirect(route('projects.index'));
    }

    public function test_a_stranger_is_refused()
    {
        $medium = $this->medium();

        $this->actingAs($this->outsider())
            ->getJson($this->url('data.media.index'))
            ->assertForbidden();

        $this->actingAs($this->outsider())
            ->get($this->url('data.media.show', ['medium' => $medium->id]))
            ->assertRedirect(route('projects.index'));
    }

    public function test_media_of_another_project_cannot_be_reached_through_this_one()
    {
        $foreignForm = InterviewForm::factory()->create();
        $foreignInstance = InterviewInstance::factory()->create([
            'interview_form_id' => $foreignForm->id,
        ]);

        $this->actingAs($this->userWithCapability($this->project, 'manage_data'))
            ->getJson(route('data.media.index', [
                'project' => $this->project->id,
                'instance' => $foreignInstance->id,
            ]))
            ->assertNotFound();
    }

    public function test_a_field_records_media_is_not_reachable_as_an_interviews()
    {
        $medium = $this->medium(['interview_instance_id' => null]);

        $this->actingAs($this->userWithCapability($this->project, 'manage_data'))
            ->get($this->url('data.media.show', ['medium' => $medium->id]))
            ->assertNotFound();
    }

    public function test_the_data_table_says_how_many_each_interview_carries()
    {
        $this->medium();
        $this->medium();

        $this->assertSame(2, $this->instance->media()->count());
    }
}
