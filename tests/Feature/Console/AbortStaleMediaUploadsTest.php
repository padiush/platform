<?php

namespace Tests\Feature\Console;

use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\Media;
use App\Models\Project;
use App\Models\User;
use App\Services\Media\MultipartUploads;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Fakes\FakeMultipartUploads;
use Tests\TestCase;

/**
 * The scheduled abort of multipart uploads nobody is coming back for
 * (docs/decisions/0012-resumable-media-upload.md).
 */
class AbortStaleMediaUploadsTest extends TestCase
{
    use RefreshDatabase;

    private FakeMultipartUploads $storage;

    private InterviewInstance $instance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = new FakeMultipartUploads;
        $this->app->instance(MultipartUploads::class, $this->storage);

        $project = Project::factory()->create();
        $form = InterviewForm::factory()->create(['project_id' => $project->id]);

        $this->instance = new InterviewInstance;
        $this->instance->id = (string) Str::uuid();
        $this->instance->interview_form_id = $form->id;
        $this->instance->user_id = User::factory()->create()->id;
        $this->instance->save();
    }

    /** A pending row whose upload storage started the given number of days ago. */
    private function uploading(string $uploadId, float $daysAgo): Media
    {
        $key = "projects/1/instances/{$this->instance->id}/media/{$uploadId}.m4a";
        $this->storage->seed($uploadId, $key, now()->subHours((int) ($daysAgo * 24))->toImmutable());

        return Media::create([
            'interview_instance_id' => $this->instance->id,
            'client_id' => (string) Str::uuid(),
            'kind' => 'audio',
            'storage_disk' => 's3',
            'storage_key' => $key,
            'content_type' => 'audio/mp4',
            'byte_size' => 20_000_000,
            'upload_id' => $uploadId,
            'upload_part_size' => 8 * 1024 * 1024,
            'status' => Media::STATUS_PENDING,
        ]);
    }

    public function test_an_upload_older_than_a_week_is_aborted_and_forgotten(): void
    {
        $stale = $this->uploading('stale', 8);
        $recent = $this->uploading('recent', 6);

        $this->artisan('media:abort-stale-uploads')->assertSuccessful();

        $this->assertSame(['stale'], $this->storage->aborted);
        $this->assertNull($stale->fresh()->upload_id);
        $this->assertNull($stale->fresh()->upload_part_size);
        $this->assertSame('recent', $recent->fresh()->upload_id);
    }

    public function test_the_window_can_be_shortened(): void
    {
        $this->uploading('two-days', 2);

        $this->artisan('media:abort-stale-uploads', ['--days' => 1])->assertSuccessful();

        $this->assertSame(['two-days'], $this->storage->aborted);
    }

    /** A recording deleted mid-upload takes its row with it, not its parts. */
    public function test_an_upload_no_row_names_is_aborted(): void
    {
        $this->storage->seed('orphan', 'projects/1/instances/x/media/a.m4a', now()->subHours(2)->toImmutable());

        $this->artisan('media:abort-stale-uploads')->assertSuccessful();

        $this->assertSame(['orphan'], $this->storage->aborted);
    }

    /** Intent starts the upload a moment before it saves the row. */
    public function test_a_brand_new_upload_without_a_row_is_left_alone(): void
    {
        $this->storage->seed('starting', 'projects/1/instances/x/media/a.m4a', now()->subMinutes(5)->toImmutable());

        $this->artisan('media:abort-stale-uploads')->assertSuccessful();

        $this->assertSame([], $this->storage->aborted);
    }

    public function test_uploads_outside_device_media_are_not_touched(): void
    {
        $this->storage->seed('other', 'site/backup.tar', now()->subDays(30)->toImmutable());

        $this->artisan('media:abort-stale-uploads')->assertSuccessful();

        $this->assertSame([], $this->storage->aborted);
    }

    /** Aborted by something else, such as a bucket lifecycle rule. */
    public function test_a_row_whose_upload_storage_no_longer_has_is_forgotten(): void
    {
        $media = $this->uploading('vanished', 1);
        unset($this->storage->uploads['vanished']);
        Media::whereKey($media->id)->toBase()->update(['updated_at' => now()->subDay()]);

        $this->artisan('media:abort-stale-uploads')->assertSuccessful();

        $this->assertNull($media->fresh()->upload_id);
        $this->assertSame([], $this->storage->aborted);
    }
}
