<?php

namespace Tests\Feature;

use App\Models\FieldRecord;
use App\Models\Media;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PruneOrphanedMediaTest extends TestCase
{
    use RefreshDatabase;

    private string $named;

    private string $orphan;

    private string $recent;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');
        $disk = Storage::disk('s3');

        $record = FieldRecord::factory()->create(['project_id' => Project::factory()->create()->id]);
        $this->named = 'projects/1/field-records/1/named.jpg';
        $this->orphan = 'projects/1/field-records/2/orphan.jpg';
        $this->recent = 'projects/1/field-records/3/recent.jpg';

        foreach ([$this->named, $this->orphan, $this->recent] as $key) {
            $disk->put($key, str_repeat('x', 1024));
        }
        $old = now()->subDays(3)->getTimestamp();
        touch($disk->path($this->named), $old);
        touch($disk->path($this->orphan), $old);

        Media::create([
            'field_record_id' => $record->id,
            'client_id' => (string) Str::uuid(),
            'kind' => Media::KIND_PHOTO,
            'storage_disk' => 's3',
            'storage_key' => $this->named,
            'content_type' => 'image/jpeg',
            'byte_size' => 1024,
            'status' => Media::STATUS_STORED,
        ]);
    }

    public function test_it_only_lists_by_default(): void
    {
        $this->artisan('media:prune-orphans')
            ->expectsOutput($this->orphan)
            ->doesntExpectOutput($this->named)
            ->doesntExpectOutput($this->recent)
            ->assertSuccessful();

        Storage::disk('s3')->assertExists($this->orphan);
    }

    /** What a row names, and what may be an upload still landing, stay. */
    public function test_it_deletes_only_old_objects_no_row_names(): void
    {
        $this->artisan('media:prune-orphans', ['--delete' => true])
            ->expectsOutputToContain('Deleted 1 orphaned object(s)')
            ->assertSuccessful();

        Storage::disk('s3')->assertMissing($this->orphan);
        Storage::disk('s3')->assertExists($this->named);
        Storage::disk('s3')->assertExists($this->recent);
    }
}
