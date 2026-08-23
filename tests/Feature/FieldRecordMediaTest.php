<?php

namespace Tests\Feature;

use App\Models\FieldRecord;
use App\Models\Media;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

/**
 * For a record of something never collected, the photograph is the record.
 * See docs/decisions/0010-field-records-and-basis.md.
 */
class FieldRecordMediaTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    private FieldRecord $record;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        $this->project = Project::factory()->create();
        $this->record = FieldRecord::factory()->create([
            'project_id' => $this->project->id,
            'basis_of_record' => FieldRecord::BASIS_OBSERVATION,
        ]);
    }

    private function editor()
    {
        return $this->userWithCapability($this->project, 'edit_catalog');
    }

    /** The first role carrying view_catalog is the admin, which also edits. */
    private function viewer()
    {
        return $this->userWithCapability($this->project, 'edit_catalog', false);
    }

    private function url(string $name, array $extra = []): string
    {
        return route($name, array_merge([
            'project' => $this->project->id,
            'fieldRecord' => $this->record->id,
        ], $extra));
    }

    private function attach(?UploadedFile $file = null)
    {
        return $this->actingAs($this->editor())->post(
            $this->url('catalogs.fieldRecords.media.store'),
            ['file' => $file ?? UploadedFile::fake()->image('planta.jpg')]
        );
    }

    public function test_a_photograph_can_be_attached_to_a_record()
    {
        $this->attach()->assertRedirect();

        $media = Media::sole();

        $this->assertSame($this->record->id, $media->field_record_id);
        $this->assertNull($media->interview_instance_id);
        $this->assertSame(Media::KIND_PHOTO, $media->kind);
        // Posted, not handshaked: the bytes are already here, so nothing is
        // left stranded in pending.
        $this->assertSame(Media::STATUS_STORED, $media->status);
        Storage::disk('local')->assertExists($media->storage_key);
    }

    public function test_audio_is_recognised_as_audio()
    {
        $this->attach(UploadedFile::fake()->create('nota.mp3', 64, 'audio/mpeg'))
            ->assertRedirect();

        $this->assertSame(Media::KIND_AUDIO, Media::sole()->kind);
    }

    public function test_it_refuses_something_that_is_neither()
    {
        $this->attach(UploadedFile::fake()->create('notas.pdf', 8, 'application/pdf'))
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Media::count());
    }

    public function test_the_bytes_are_not_world_readable()
    {
        $this->attach()->assertRedirect();

        // A photograph taken on someone's land is not public because it
        // happens to be a file.
        $this->assertSame(
            'private',
            Storage::disk('local')->getVisibility(Media::sole()->storage_key)
        );
    }

    public function test_a_viewer_can_see_it_and_a_stranger_cannot()
    {
        $this->attach()->assertRedirect();
        $media = Media::sole();

        $this->actingAs($this->viewer())
            ->get($this->url('catalogs.fieldRecords.media.show', ['medium' => $media->id]))
            ->assertOk()
            ->assertHeader('content-type', $media->content_type);

        $this->actingAs($this->outsider())
            ->get($this->url('catalogs.fieldRecords.media.show', ['medium' => $media->id]))
            ->assertRedirect(route('catalogs.index'));
    }

    public function test_a_viewer_cannot_attach_or_remove()
    {
        $this->attach()->assertRedirect();
        $media = Media::sole();

        $this->actingAs($this->viewer())->post(
            $this->url('catalogs.fieldRecords.media.store'),
            ['file' => UploadedFile::fake()->image('otra.jpg')]
        )->assertRedirect(route('catalogs.index'));

        $this->actingAs($this->viewer())
            ->delete($this->url('catalogs.fieldRecords.media.destroy', ['medium' => $media->id]))
            ->assertRedirect(route('catalogs.index'));

        $this->assertSame(1, Media::count());
    }

    public function test_removing_it_takes_the_bytes_too()
    {
        $this->attach()->assertRedirect();
        $media = Media::sole();
        $key = $media->storage_key;

        $this->actingAs($this->editor())
            ->delete($this->url('catalogs.fieldRecords.media.destroy', ['medium' => $media->id]))
            ->assertRedirect();

        // Orphaned objects in a bucket are how it becomes a liability.
        $this->assertNull($media->fresh());
        Storage::disk('local')->assertMissing($key);
    }

    public function test_deleting_the_record_takes_its_media_with_it()
    {
        $this->attach()->assertRedirect();
        $media = Media::sole();

        $this->record->delete();

        $this->assertNull($media->fresh());
    }

    public function test_media_of_another_project_cannot_be_reached()
    {
        $this->attach()->assertRedirect();
        $media = Media::sole();

        $foreignProject = Project::factory()->create();
        $foreignRecord = FieldRecord::factory()->create(['project_id' => $foreignProject->id]);

        $this->actingAs($this->editor())->get(route('catalogs.fieldRecords.media.show', [
            'project' => $this->project->id,
            'fieldRecord' => $foreignRecord->id,
            'medium' => $media->id,
        ]))->assertRedirect(route('catalogs.index'));
    }
}
