<?php

namespace Tests\Feature;

use App\Models\CollectingPermit;
use App\Models\FieldRecord;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

/**
 * Managing the authorisations a project collects under.
 * See docs/decisions/0009-collecting-permits.md.
 */
class CollectingPermitCrudTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
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
        return route($name, array_merge(['project' => $this->project->id], $extra));
    }

    private function permit(array $attributes = []): CollectingPermit
    {
        return CollectingPermit::factory()->create(
            array_merge(['project_id' => $this->project->id], $attributes)
        );
    }

    public function test_an_editor_can_record_a_permit()
    {
        $this->actingAs($this->editor())->post($this->url('catalogs.permits.store'), [
            'authority' => 'MARN',
            'reference' => 'RES-042-2026',
            'issued_on' => '2026-01-15',
            'expires_on' => '2027-01-14',
            'notes' => 'Material botánico con fines de investigación.',
        ])->assertRedirect();

        $permit = CollectingPermit::sole();

        $this->assertSame($this->project->id, $permit->project_id);
        $this->assertSame('MARN', $permit->authority);
        $this->assertSame('RES-042-2026', $permit->reference);
    }

    public function test_an_authority_and_a_reference_are_required()
    {
        $this->actingAs($this->editor())
            ->post($this->url('catalogs.permits.store'), [])
            ->assertSessionHasErrors(['authority', 'reference']);

        $this->assertSame(0, CollectingPermit::count());
    }

    public function test_a_reference_cannot_repeat_within_a_project()
    {
        $this->permit(['reference' => 'RES-042-2026']);

        $this->actingAs($this->editor())->post($this->url('catalogs.permits.store'), [
            'authority' => 'MARN',
            'reference' => 'RES-042-2026',
        ])->assertSessionHasErrors('reference');
    }

    public function test_expiry_cannot_precede_issue()
    {
        $this->actingAs($this->editor())->post($this->url('catalogs.permits.store'), [
            'authority' => 'MARN',
            'reference' => 'RES-1',
            'issued_on' => '2026-06-01',
            'expires_on' => '2026-01-01',
        ])->assertSessionHasErrors('expires_on');
    }

    public function test_deleting_a_permit_keeps_the_collections_taken_under_it()
    {
        $permit = $this->permit();
        $fieldRecord = FieldRecord::factory()->create([
            'project_id' => $this->project->id,
            'collecting_permit_id' => $permit->id,
        ]);

        $this->actingAs($this->editor())
            ->delete($this->url('catalogs.permits.destroy', ['permit' => $permit->id]))
            ->assertRedirect();

        // The physical record outlives the paperwork.
        $this->assertNotNull($fieldRecord->fresh());
        $this->assertNull($fieldRecord->fresh()->collecting_permit_id);
    }

    public function test_the_page_reports_how_many_collections_a_permit_covers()
    {
        $permit = $this->permit(['expires_on' => '2020-01-01']);
        FieldRecord::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'collecting_permit_id' => $permit->id,
        ]);

        $this->actingAs($this->editor())
            ->get($this->url('catalogs.permits.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Permits')
                ->has('permits', 1)
                ->where('permits.0.field_records_count', 2)
                // Read from the date on the permit, not a ruling about it.
                ->where('permits.0.has_expired', true)
            );
    }

    public function test_a_viewer_may_read_the_list_but_not_change_it()
    {
        $this->permit();

        $this->actingAs($this->viewer())
            ->get($this->url('catalogs.permits.index'))
            ->assertInertia(fn (Assert $page) => $page->where('canEdit', false));

        $this->actingAs($this->viewer())
            ->post($this->url('catalogs.permits.store'), [
                'authority' => 'MARN',
                'reference' => 'NOPE-1',
            ])
            ->assertRedirect(route('catalogs.index'));

        $this->assertSame(1, CollectingPermit::count());
    }

    public function test_a_stranger_cannot_see_the_list()
    {
        $this->actingAs($this->outsider())
            ->get($this->url('catalogs.permits.index'))
            ->assertRedirect(route('catalogs.index'));
    }

    public function test_a_permit_from_another_project_cannot_be_touched()
    {
        $foreign = CollectingPermit::factory()->create();

        $this->actingAs($this->editor())
            ->delete($this->url('catalogs.permits.destroy', ['permit' => $foreign->id]))
            ->assertRedirect(route('catalogs.index'));

        $this->assertNotNull($foreign->fresh());
    }
}
