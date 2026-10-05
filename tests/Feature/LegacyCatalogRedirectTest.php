<?php

namespace Tests\Feature;

use App\Models\CatalogSpecies;
use App\Models\FieldRecord;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

/**
 * Records, permits and species used to live under /catalogs/{project}. They
 * now live under the project, and the old addresses send there for good, so
 * a bookmark or a link shared before the move still opens the same page.
 */
class LegacyCatalogRedirectTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
    }

    private function visit(string $path)
    {
        $user = $this->userWithCapability($this->project, 'view_catalog');

        return $this->actingAs($user)->get($path);
    }

    public function test_each_old_page_moves_permanently_to_its_new_address()
    {
        $id = $this->project->id;
        $species = CatalogSpecies::factory()->create(['project_id' => $id]);
        $record = FieldRecord::factory()->create(['project_id' => $id]);

        $moves = [
            "/catalogs/{$id}" => route('catalogs.show', $id),
            "/catalogs/{$id}/species/{$species->id}" => route('catalogs.species.show', [$id, $species->id]),
            "/catalogs/{$id}/species/register" => route('catalogs.species.register', $id),
            "/catalogs/{$id}/records" => route('catalogs.fieldRecords.index', $id),
            "/catalogs/{$id}/records/export" => route('catalogs.fieldRecords.export', $id),
            "/catalogs/{$id}/permits" => route('catalogs.permits.index', $id),
            "/catalogs/{$id}/records/{$record->id}/media/9" => route('catalogs.fieldRecords.media.show', [$id, $record->id, 9]),
        ];

        foreach ($moves as $old => $new) {
            $this->visit($old)->assertStatus(301)->assertRedirect($new);
        }
    }

    /** The overview and an interview open the records list already filtered. */
    public function test_the_query_string_comes_along()
    {
        $id = $this->project->id;

        $this->visit("/catalogs/{$id}/records?filter=undetermined")
            ->assertRedirect(route('catalogs.fieldRecords.index', $id).'?filter=undetermined');

        $this->visit("/catalogs/{$id}/records/export?format=csv")
            ->assertRedirect(route('catalogs.fieldRecords.export', $id).'?format=csv');
    }

    public function test_an_address_that_never_existed_is_not_found()
    {
        $this->visit("/catalogs/{$this->project->id}/nonsense")->assertNotFound();
    }

    /** The new addresses are where the pages render. */
    public function test_the_new_addresses_serve_the_pages()
    {
        $id = $this->project->id;

        $this->assertSame("/projects/{$id}/catalog", route('catalogs.show', $id, false));
        $this->assertSame("/projects/{$id}/records", route('catalogs.fieldRecords.index', $id, false));
        $this->assertSame("/projects/{$id}/permits", route('catalogs.permits.index', $id, false));

        $this->visit("/projects/{$id}/records")->assertOk();
        $this->visit("/projects/{$id}/permits")->assertOk();
        $this->visit("/projects/{$id}/catalog")->assertOk();
    }
}
