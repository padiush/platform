<?php

namespace Tests\Feature\Api;

use App\Jobs\TranscribeAudio;
use App\Models\InstanceMedia;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\Project;
use App\Models\User;
use App\Services\Media\UploadUrlFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    private InterviewInstance $instance;

    private User $recorder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $form = InterviewForm::factory()->create(['project_id' => $this->project->id]);

        $this->recorder = User::factory()->create();
        $this->giveAccess($this->recorder, $this->project, 'record_data');

        $this->instance = new InterviewInstance;
        $this->instance->id = (string) Str::uuid();
        $this->instance->interview_form_id = $form->id;
        $this->instance->user_id = $this->recorder->id;
        $this->instance->save();

        // A deterministic presigned-URL factory so tests never touch S3.
        $this->app->instance(UploadUrlFactory::class, new class implements UploadUrlFactory
        {
            public function create(string $disk, string $key, string $contentType, int $ttlMinutes): array
            {
                return [
                    'url' => 'https://storage.test/'.$key,
                    'headers' => ['Content-Type' => $contentType],
                    'expires_at' => now()->addMinutes($ttlMinutes)->toIso8601String(),
                ];
            }
        });
    }

    private function actingAsRecorder(): void
    {
        Sanctum::actingAs($this->recorder, ['capture']);
    }

    private function intentUrl(): string
    {
        return "/api/v1/instances/{$this->instance->id}/media/intent";
    }

    private function completeUrl(): string
    {
        return "/api/v1/instances/{$this->instance->id}/media/complete";
    }

    public function test_intent_issues_a_presigned_url_and_records_pending_media(): void
    {
        $this->actingAsRecorder();
        $clientId = (string) Str::uuid();

        $response = $this->postJson($this->intentUrl(), [
            'client_id' => $clientId,
            'kind' => 'audio',
            'content_type' => 'audio/mp4',
            'byte_size' => 2_000_000,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['upload_url', 'headers', 'storage_key', 'expires_at']);

        $this->assertDatabaseHas('instance_media', [
            'client_id' => $clientId,
            'kind' => 'audio',
            'status' => 'pending',
        ]);
    }

    public function test_intent_rejects_a_content_type_that_does_not_match_the_kind(): void
    {
        $this->actingAsRecorder();

        $this->postJson($this->intentUrl(), [
            'client_id' => (string) Str::uuid(),
            'kind' => 'audio',
            'content_type' => 'image/jpeg',
            'byte_size' => 1000,
        ])->assertStatus(422)
            ->assertJsonPath('errors.content_type.0', 'api.media.content_type_mismatch');
    }

    public function test_completing_a_photo_stores_it_without_transcription(): void
    {
        $this->actingAsRecorder();
        $clientId = (string) Str::uuid();

        $media = InstanceMedia::create([
            'interview_instance_id' => $this->instance->id,
            'client_id' => $clientId,
            'kind' => 'photo',
            'storage_disk' => 's3',
            'storage_key' => 'projects/1/photo.jpg',
            'content_type' => 'image/jpeg',
            'status' => 'pending',
        ]);

        Queue::fake();

        $this->postJson($this->completeUrl(), [
            'client_id' => $clientId,
            'storage_key' => 'projects/1/photo.jpg',
        ])->assertOk()
            ->assertJsonPath('status', 'stored')
            ->assertJsonMissingPath('transcription');

        Queue::assertNothingPushed();
        $this->assertNull($media->fresh()->transcription_status);
    }

    public function test_completing_audio_with_transcription_disabled_does_not_queue(): void
    {
        config(['services.transcription.enabled' => false]);
        $this->actingAsRecorder();
        $clientId = (string) Str::uuid();

        InstanceMedia::create([
            'interview_instance_id' => $this->instance->id,
            'client_id' => $clientId,
            'kind' => 'audio',
            'storage_disk' => 's3',
            'storage_key' => 'projects/1/audio.m4a',
            'content_type' => 'audio/mp4',
            'status' => 'pending',
        ]);

        Queue::fake();

        $this->postJson($this->completeUrl(), [
            'client_id' => $clientId,
            'storage_key' => 'projects/1/audio.m4a',
            'duration_s' => 42,
        ])->assertOk()
            ->assertJsonPath('status', 'stored')
            ->assertJsonMissingPath('transcription');

        Queue::assertNothingPushed();
    }

    public function test_completing_audio_with_transcription_enabled_queues_the_job(): void
    {
        config(['services.transcription.enabled' => true]);
        $this->actingAsRecorder();
        $clientId = (string) Str::uuid();

        $media = InstanceMedia::create([
            'interview_instance_id' => $this->instance->id,
            'client_id' => $clientId,
            'kind' => 'audio',
            'storage_disk' => 's3',
            'storage_key' => 'projects/1/audio.m4a',
            'content_type' => 'audio/mp4',
            'status' => 'pending',
        ]);

        Queue::fake();

        $this->postJson($this->completeUrl(), [
            'client_id' => $clientId,
            'storage_key' => 'projects/1/audio.m4a',
        ])->assertOk()
            ->assertJsonPath('status', 'stored')
            ->assertJsonPath('transcription', 'queued');

        Queue::assertPushed(TranscribeAudio::class);
        $this->assertSame('queued', $media->fresh()->transcription_status);
    }

    public function test_complete_rejects_a_mismatched_storage_key(): void
    {
        $this->actingAsRecorder();
        $clientId = (string) Str::uuid();

        InstanceMedia::create([
            'interview_instance_id' => $this->instance->id,
            'client_id' => $clientId,
            'kind' => 'photo',
            'storage_disk' => 's3',
            'storage_key' => 'projects/1/real.jpg',
            'content_type' => 'image/jpeg',
            'status' => 'pending',
        ]);

        $this->postJson($this->completeUrl(), [
            'client_id' => $clientId,
            'storage_key' => 'projects/1/spoofed.jpg',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'api.media.storage_key_mismatch');
    }

    public function test_complete_unknown_media_is_not_found(): void
    {
        $this->actingAsRecorder();

        $this->postJson($this->completeUrl(), [
            'client_id' => (string) Str::uuid(),
            'storage_key' => 'projects/1/whatever.jpg',
        ])->assertStatus(404);
    }

    public function test_member_without_record_data_is_forbidden(): void
    {
        $user = User::factory()->create();
        $this->giveAccess($user, $this->project, 'record_data', false);
        Sanctum::actingAs($user, ['capture']);

        $this->postJson($this->intentUrl(), [
            'client_id' => (string) Str::uuid(),
            'kind' => 'photo',
            'content_type' => 'image/jpeg',
            'byte_size' => 1000,
        ])->assertStatus(403);
    }

    public function test_requires_authentication(): void
    {
        $this->postJson($this->intentUrl(), [])->assertStatus(401);
    }
}
