<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\V1\MediaController;
use App\Models\FieldRecord;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\Media;
use App\Models\Project;
use App\Models\User;
use App\Services\Media\MultipartUploads;
use App\Services\Media\StoredObjectInspector;
use App\Services\Media\UploadUrlFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithProjects;
use Tests\Fakes\FakeMultipartUploads;
use Tests\TestCase;

/**
 * A large file sent as a multipart upload that resumes from the parts storage
 * already holds (docs/decisions/0012-resumable-media-upload.md). MediaTest
 * covers the single-PUT handshake these steps extend.
 */
class ResumableMediaTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    /** Two full 8 MiB parts and a 4 MiB last one. */
    private const FILE_BYTES = 20 * 1024 * 1024;

    private const LAST_PART_BYTES = 4 * 1024 * 1024;

    private Project $project;

    private InterviewInstance $instance;

    private User $recorder;

    private FakeMultipartUploads $storage;

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

        $this->storage = new FakeMultipartUploads;
        $this->app->instance(MultipartUploads::class, $this->storage);

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

        Sanctum::actingAs($this->recorder, ['capture']);
    }

    private function url(string $step): string
    {
        return "/api/v1/instances/{$this->instance->id}/media/{$step}";
    }

    /** An override of null leaves that field out of the request. */
    private function intent(string $clientId, array $overrides = [])
    {
        return $this->postJson($this->url('intent'), array_filter(array_merge([
            'client_id' => $clientId,
            'kind' => 'audio',
            'content_type' => 'audio/mp4',
            'byte_size' => self::FILE_BYTES,
            'resumable' => true,
        ], $overrides), fn ($value) => $value !== null));
    }

    /** Start a multipart upload through intent, and return its media row. */
    private function startedUpload(): Media
    {
        $clientId = (string) Str::uuid();
        $this->intent($clientId)->assertOk();

        return Media::where('client_id', $clientId)->firstOrFail();
    }

    private function step(string $step, Media $media)
    {
        return $this->postJson($this->url($step), [
            'client_id' => $media->client_id,
            'storage_key' => $media->storage_key,
        ]);
    }

    private function sendAllParts(Media $media): void
    {
        $this->storage->receivePart($media->upload_id, 1, MediaController::PART_BYTES);
        $this->storage->receivePart($media->upload_id, 2, MediaController::PART_BYTES);
        $this->storage->receivePart($media->upload_id, 3, self::LAST_PART_BYTES);
    }

    /** @param  array{byte_size:int, content_type:?string}|null  $result */
    private function expectStoredObject(string $key, ?array $result): void
    {
        $inspector = \Mockery::mock(StoredObjectInspector::class);
        $inspector->shouldReceive('inspect')->once()->with('s3', $key)->andReturn($result);

        $this->app->instance(StoredObjectInspector::class, $inspector);
    }

    // --- intent ---------------------------------------------------------------

    public function test_a_large_file_starts_a_multipart_upload(): void
    {
        $clientId = (string) Str::uuid();

        $response = $this->intent($clientId)
            ->assertOk()
            ->assertJsonPath('upload.mode', 'multipart')
            ->assertJsonPath('upload.part_size', MediaController::PART_BYTES)
            ->assertJsonPath('upload.part_count', 3)
            ->assertJsonMissingPath('upload_url');

        $media = Media::where('client_id', $clientId)->firstOrFail();
        $this->assertSame($media->storage_key, $response->json('storage_key'));
        $this->assertSame(MediaController::PART_BYTES, $media->upload_part_size);
        $this->assertArrayHasKey($media->upload_id, $this->storage->uploads);
        $this->assertSame('audio/mp4', $this->storage->uploads[$media->upload_id]['content_type']);
    }

    public function test_a_file_of_one_part_keeps_the_single_put(): void
    {
        $clientId = (string) Str::uuid();

        $this->intent($clientId, ['byte_size' => MediaController::PART_BYTES])
            ->assertOk()
            ->assertJsonPath('upload.mode', 'single')
            ->assertJsonStructure(['upload_url', 'headers', 'storage_key', 'expires_at']);

        $this->assertNull(Media::where('client_id', $clientId)->value('upload_id'));
        $this->assertSame([], $this->storage->uploads);
    }

    /** An older client sees exactly the response it was written against. */
    public function test_a_client_that_does_not_ask_keeps_the_single_put(): void
    {
        $clientId = (string) Str::uuid();

        $this->intent($clientId, ['resumable' => null])
            ->assertOk()
            ->assertJsonStructure(['upload_url', 'headers', 'storage_key', 'expires_at'])
            ->assertJsonMissingPath('upload');

        $this->assertSame([], $this->storage->uploads);
    }

    public function test_retrying_the_intent_resumes_the_same_upload(): void
    {
        $media = $this->startedUpload();
        $this->storage->receivePart($media->upload_id, 1, MediaController::PART_BYTES);

        $this->intent($media->client_id)->assertOk()->assertJsonPath('upload.part_count', 3);

        $this->assertSame($media->upload_id, $media->fresh()->upload_id);
        $this->assertCount(1, $this->storage->uploads);
        $this->assertSame([], $this->storage->aborted);
    }

    public function test_an_intent_for_a_different_size_starts_again(): void
    {
        $media = $this->startedUpload();

        $this->intent($media->client_id, ['byte_size' => self::FILE_BYTES + 1])
            ->assertOk()
            ->assertJsonPath('upload.mode', 'multipart');

        $this->assertSame([$media->upload_id], $this->storage->aborted);
        $this->assertNotSame($media->upload_id, $media->fresh()->upload_id);
    }

    public function test_falling_back_to_a_single_put_discards_the_open_upload(): void
    {
        $media = $this->startedUpload();

        $this->intent($media->client_id, ['resumable' => false])
            ->assertOk()
            ->assertJsonStructure(['upload_url']);

        $this->assertSame([$media->upload_id], $this->storage->aborted);
        $this->assertNull($media->fresh()->upload_id);
    }

    public function test_resumable_must_be_a_boolean(): void
    {
        $this->intent((string) Str::uuid(), ['resumable' => 'sometimes'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('resumable');
    }

    // --- parts ----------------------------------------------------------------

    public function test_parts_signs_only_what_storage_does_not_hold(): void
    {
        $media = $this->startedUpload();
        $this->storage->receivePart($media->upload_id, 1, MediaController::PART_BYTES);
        // Cut short by a dropped connection: present, but the wrong size.
        $this->storage->receivePart($media->upload_id, 2, 1_000);

        $response = $this->step('parts', $media)->assertOk();

        $this->assertSame([2, 3], array_column($response->json('parts'), 'number'));
        $this->assertStringContainsString('partNumber=3', $response->json('parts.1.url'));
        $this->assertNotNull($response->json('expires_at'));
    }

    public function test_parts_is_empty_once_every_part_is_there(): void
    {
        $media = $this->startedUpload();
        $this->sendAllParts($media);

        $this->step('parts', $media)->assertOk()->assertJsonPath('parts', []);
    }

    public function test_an_upload_storage_no_longer_has_has_expired(): void
    {
        $media = $this->startedUpload();
        unset($this->storage->uploads[$media->upload_id]);

        $this->step('parts', $media)
            ->assertStatus(410)
            ->assertJsonPath('message', 'api.media.upload_expired');

        $this->assertNull($media->fresh()->upload_id);

        // The device's answer is a new intent, which starts a new upload.
        $this->intent($media->client_id)->assertOk()->assertJsonPath('upload.mode', 'multipart');
        $this->assertNotNull($media->fresh()->upload_id);
    }

    public function test_parts_for_a_file_with_no_open_upload_has_expired(): void
    {
        $media = $this->startedUpload();
        $media->forceFill(['upload_id' => null, 'upload_part_size' => null])->save();

        $this->step('parts', $media)->assertStatus(410)->assertJsonPath('message', 'api.media.upload_expired');
    }

    public function test_parts_for_a_stored_file_has_nothing_to_send(): void
    {
        $media = $this->startedUpload();
        $media->forceFill(['upload_id' => null, 'upload_part_size' => null, 'status' => Media::STATUS_STORED])->save();

        $this->step('parts', $media)->assertOk()->assertJsonPath('parts', []);
    }

    public function test_parts_refuses_a_storage_key_it_did_not_issue(): void
    {
        $media = $this->startedUpload();

        $this->postJson($this->url('parts'), ['client_id' => $media->client_id, 'storage_key' => 'elsewhere.m4a'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'api.media.storage_key_mismatch');
    }

    public function test_parts_for_unknown_media_is_not_found(): void
    {
        $this->postJson($this->url('parts'), ['client_id' => (string) Str::uuid(), 'storage_key' => 'k.m4a'])
            ->assertStatus(404)
            ->assertJsonPath('message', 'api.media.not_found');
    }

    public function test_parts_needs_record_data(): void
    {
        $media = $this->startedUpload();

        $viewer = User::factory()->create();
        $this->giveAccess($viewer, $this->project, 'record_data', false);
        Sanctum::actingAs($viewer, ['capture']);

        $this->step('parts', $media)->assertForbidden();
    }

    // --- complete -------------------------------------------------------------

    public function test_complete_assembles_the_parts_then_checks_the_object(): void
    {
        $media = $this->startedUpload();
        $uploadId = $media->upload_id;
        $this->sendAllParts($media);
        $this->expectStoredObject($media->storage_key, ['byte_size' => self::FILE_BYTES, 'content_type' => 'audio/mp4']);

        $this->step('complete', $media)->assertOk()->assertJsonPath('status', 'stored');

        $this->assertSame(
            [1 => '"etag-1"', 2 => '"etag-2"', 3 => '"etag-3"'],
            $this->storage->completed[$uploadId]
        );
        $media->refresh();
        $this->assertSame(Media::STATUS_STORED, $media->status);
        $this->assertNull($media->upload_id);
        $this->assertNull($media->upload_part_size);
    }

    public function test_complete_refuses_a_missing_part(): void
    {
        $media = $this->startedUpload();
        $this->storage->receivePart($media->upload_id, 1, MediaController::PART_BYTES);
        $this->storage->receivePart($media->upload_id, 3, self::LAST_PART_BYTES);

        $this->step('complete', $media)
            ->assertStatus(422)
            ->assertJsonPath('message', 'api.media.upload_incomplete');

        $this->assertSame([], $this->storage->completed);
        $this->assertSame(Media::STATUS_PENDING, $media->fresh()->status);
        $this->assertNotNull($media->fresh()->upload_id);
    }

    public function test_complete_refuses_a_part_of_the_wrong_size(): void
    {
        $media = $this->startedUpload();
        $this->sendAllParts($media);
        $this->storage->receivePart($media->upload_id, 2, 1_000);

        $this->step('complete', $media)->assertStatus(422)->assertJsonPath('message', 'api.media.upload_incomplete');
    }

    /** An earlier complete assembled it, and its answer never reached the device. */
    public function test_complete_accepts_an_object_already_assembled(): void
    {
        $media = $this->startedUpload();
        unset($this->storage->uploads[$media->upload_id]);
        $this->expectStoredObject($media->storage_key, ['byte_size' => self::FILE_BYTES, 'content_type' => 'audio/mp4']);

        $this->step('complete', $media)->assertOk()->assertJsonPath('status', 'stored');

        $this->assertNull($media->fresh()->upload_id);
    }

    public function test_complete_with_neither_the_upload_nor_the_object_has_expired(): void
    {
        $media = $this->startedUpload();
        unset($this->storage->uploads[$media->upload_id]);
        $this->expectStoredObject($media->storage_key, null);

        $this->step('complete', $media)->assertStatus(410)->assertJsonPath('message', 'api.media.upload_expired');

        $this->assertNull($media->fresh()->upload_id);
        $this->assertSame(Media::STATUS_PENDING, $media->fresh()->status);
    }

    // --- a field record's media -----------------------------------------------

    public function test_a_record_resumes_its_media_the_same_way(): void
    {
        $record = FieldRecord::factory()->create([
            'project_id' => $this->project->id,
            'basis_of_record' => FieldRecord::BASIS_OBSERVATION,
        ]);
        $clientId = (string) Str::uuid();

        $this->postJson("/api/v1/records/{$record->id}/media/intent", [
            'client_id' => $clientId,
            'kind' => 'audio',
            'content_type' => 'audio/mp4',
            'byte_size' => self::FILE_BYTES,
            'resumable' => true,
        ])->assertOk()->assertJsonPath('upload.mode', 'multipart');

        $media = Media::where('client_id', $clientId)->firstOrFail();
        $this->assertSame($record->id, $media->field_record_id);

        $this->postJson("/api/v1/records/{$record->id}/media/parts", [
            'client_id' => $clientId,
            'storage_key' => $media->storage_key,
        ])->assertOk()->assertJsonCount(3, 'parts');

        // Another record cannot reach it.
        $other = FieldRecord::factory()->create(['project_id' => $this->project->id]);
        $this->postJson("/api/v1/records/{$other->id}/media/parts", [
            'client_id' => $clientId,
            'storage_key' => $media->storage_key,
        ])->assertNotFound();
    }
}
