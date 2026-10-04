<?php

namespace Tests\Feature\Api;

use App\Jobs\TranscribeAudio;
use App\Models\FieldRecord;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\Media;
use App\Models\Project;
use App\Models\User;
use App\Services\Media\StoredObjectInspector;
use App\Services\Media\UploadUrlFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

/**
 * A field record's photographs and audio, from the companion. The same
 * presigned handshake as an interview's media (MediaTest covers it in depth);
 * these tests pin down what differs — the owner, its access check, where the
 * bytes go, and that nothing is transcribed.
 */
class RecordMediaTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    private FieldRecord $record;

    private User $recorder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->record = FieldRecord::factory()->create([
            'project_id' => $this->project->id,
            'basis_of_record' => FieldRecord::BASIS_OBSERVATION,
        ]);

        $this->recorder = User::factory()->create();
        $this->giveAccess($this->recorder, $this->project, 'record_data');

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

    private function intent(FieldRecord $record, array $overrides = [])
    {
        return $this->postJson("/api/v1/records/{$record->id}/media/intent", array_merge([
            'client_id' => (string) Str::uuid(),
            'kind' => 'photo',
            'content_type' => 'image/jpeg',
            'byte_size' => 1_000,
        ], $overrides));
    }

    private function pendingMedia(array $overrides = []): Media
    {
        return Media::create(array_merge([
            'field_record_id' => $this->record->id,
            'client_id' => (string) Str::uuid(),
            'kind' => 'photo',
            'storage_disk' => 's3',
            'storage_key' => "projects/{$this->project->id}/field-records/{$this->record->id}/media/p.jpg",
            'content_type' => 'image/jpeg',
            'byte_size' => 1_000,
            'status' => 'pending',
        ], $overrides));
    }

    /** @param  array{byte_size:int, content_type:?string}|null  $result */
    private function expectStoredObject(string $key, ?array $result): void
    {
        $inspector = \Mockery::mock(StoredObjectInspector::class);
        $inspector->shouldReceive('inspect')->once()->with('s3', $key)->andReturn($result);

        $this->app->instance(StoredObjectInspector::class, $inspector);
    }

    public function test_intent_issues_an_upload_url_under_the_record(): void
    {
        $this->actingAsRecorder();
        $clientId = (string) Str::uuid();

        $response = $this->intent($this->record, ['client_id' => $clientId]);

        $key = "projects/{$this->project->id}/field-records/{$this->record->id}/media/{$clientId}.jpg";
        $response->assertOk()
            ->assertJsonPath('storage_key', $key)
            ->assertJsonPath('upload_url', 'https://storage.test/'.$key);

        $media = Media::where('client_id', $clientId)->sole();
        $this->assertSame($this->record->id, (int) $media->field_record_id);
        $this->assertNull($media->interview_instance_id);
        $this->assertSame('pending', $media->status);
    }

    /** A retried intent — the device never saw the answer — lands on the same key. */
    public function test_retrying_an_intent_keeps_the_storage_key(): void
    {
        $this->actingAsRecorder();
        $clientId = (string) Str::uuid();

        $first = $this->intent($this->record, ['client_id' => $clientId])->json('storage_key');
        $second = $this->intent($this->record, ['client_id' => $clientId])->json('storage_key');

        $this->assertSame($first, $second);
        $this->assertSame(1, Media::where('client_id', $clientId)->count());
    }

    public function test_a_client_id_already_used_by_another_record_is_a_conflict(): void
    {
        $this->actingAsRecorder();
        $other = FieldRecord::factory()->create(['project_id' => $this->project->id]);
        $clientId = (string) Str::uuid();
        $this->intent($other, ['client_id' => $clientId])->assertOk();

        $this->intent($this->record, ['client_id' => $clientId])
            ->assertStatus(409)
            ->assertJsonPath('message', 'api.media.client_id_conflict');
    }

    /** Either kind of owner, never both: a record cannot take over an interview's file, nor the reverse. */
    public function test_a_client_id_cannot_move_between_an_interview_and_a_record(): void
    {
        $this->actingAsRecorder();
        $form = InterviewForm::factory()->create(['project_id' => $this->project->id]);
        $instance = new InterviewInstance;
        $instance->id = (string) Str::uuid();
        $instance->interview_form_id = $form->id;
        $instance->user_id = $this->recorder->id;
        $instance->save();

        $recordMedia = $this->pendingMedia();
        $this->postJson("/api/v1/instances/{$instance->id}/media/intent", [
            'client_id' => $recordMedia->client_id,
            'kind' => 'photo',
            'content_type' => 'image/jpeg',
            'byte_size' => 1_000,
        ])->assertStatus(409);

        $interviewMedia = Media::create([
            'interview_instance_id' => $instance->id,
            'client_id' => (string) Str::uuid(),
            'kind' => 'photo',
            'storage_disk' => 's3',
            'storage_key' => 'projects/1/instances/x/media/i.jpg',
            'content_type' => 'image/jpeg',
            'byte_size' => 1_000,
            'status' => 'pending',
        ]);
        $this->intent($this->record, ['client_id' => $interviewMedia->client_id])->assertStatus(409);
    }

    public function test_completing_stores_the_upload(): void
    {
        $this->actingAsRecorder();
        $media = $this->pendingMedia();
        $this->expectStoredObject($media->storage_key, ['byte_size' => 1_000, 'content_type' => 'image/jpeg']);

        $this->postJson("/api/v1/records/{$this->record->id}/media/complete", [
            'client_id' => $media->client_id,
            'storage_key' => $media->storage_key,
        ])->assertOk()
            ->assertJson(['id' => $media->id, 'status' => 'stored'])
            ->assertJsonMissingPath('transcription');

        $this->assertSame('stored', $media->fresh()->status);
    }

    /** Transcripts reach the device through GET /instances; a record has nothing like it. */
    public function test_completing_audio_never_queues_transcription(): void
    {
        Queue::fake();
        config(['services.transcription.enabled' => true]);
        $this->actingAsRecorder();
        $media = $this->pendingMedia([
            'kind' => 'audio',
            'content_type' => 'audio/mp4',
            'storage_key' => "projects/{$this->project->id}/field-records/{$this->record->id}/media/a.m4a",
        ]);
        $this->expectStoredObject($media->storage_key, ['byte_size' => 1_000, 'content_type' => 'audio/mp4']);

        $this->postJson("/api/v1/records/{$this->record->id}/media/complete", [
            'client_id' => $media->client_id,
            'storage_key' => $media->storage_key,
            'duration_s' => 12,
        ])->assertOk()->assertJsonMissingPath('transcription');

        Queue::assertNotPushed(TranscribeAudio::class);
        $this->assertNull($media->fresh()->transcription_status);
        $this->assertSame(12, $media->fresh()->duration_s);
    }

    public function test_complete_refuses_an_upload_that_never_arrived(): void
    {
        $this->actingAsRecorder();
        $media = $this->pendingMedia();
        $this->expectStoredObject($media->storage_key, null);

        $this->postJson("/api/v1/records/{$this->record->id}/media/complete", [
            'client_id' => $media->client_id,
            'storage_key' => $media->storage_key,
        ])->assertStatus(422)->assertJsonPath('message', 'api.media.upload_missing');

        $this->assertSame('pending', $media->fresh()->status);
    }

    public function test_complete_cannot_reach_another_records_media(): void
    {
        $this->actingAsRecorder();
        $media = $this->pendingMedia();
        $other = FieldRecord::factory()->create(['project_id' => $this->project->id]);

        $this->postJson("/api/v1/records/{$other->id}/media/complete", [
            'client_id' => $media->client_id,
            'storage_key' => $media->storage_key,
        ])->assertNotFound()->assertJsonPath('message', 'api.media.not_found');
    }

    public function test_member_without_record_data_is_forbidden(): void
    {
        $viewer = User::factory()->create();
        $this->giveAccess($viewer, $this->project, 'record_data', false);
        Sanctum::actingAs($viewer, ['capture']);

        $this->intent($this->record)->assertForbidden()->assertJsonPath('message', 'api.forbidden');
    }

    public function test_a_record_in_a_project_the_device_cannot_record_on_is_forbidden(): void
    {
        $this->actingAsRecorder();
        $foreign = FieldRecord::factory()->create();

        $this->intent($foreign)->assertForbidden();
    }

    public function test_an_unknown_record_is_not_found(): void
    {
        $this->actingAsRecorder();

        $this->postJson('/api/v1/records/999999/media/intent', [
            'client_id' => (string) Str::uuid(),
            'kind' => 'photo',
            'content_type' => 'image/jpeg',
            'byte_size' => 1_000,
        ])->assertNotFound();
    }

    public function test_requires_authentication(): void
    {
        $this->intent($this->record)->assertUnauthorized();
    }
}
