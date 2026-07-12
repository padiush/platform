<?php

namespace Tests\Feature;

use App\Models\CatalogSpecies;
use App\Models\InstanceAnswer;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

class CatalogSpeciesTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
    }

    public function test_editor_can_open_the_register_page()
    {
        $user = $this->userWithCapability($this->project, 'edit_catalog');

        $response = $this->actingAs($user)->get(
            route('catalogs.species.register', $this->project)
        );

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page->component('Catalog/Form')
        );
    }

    public function test_editor_can_register_a_species()
    {
        $user = $this->userWithCapability($this->project, 'edit_catalog');

        $response = $this->actingAs($user)->post(
            route('catalogs.species.register', $this->project),
            [
                'family' => 'Fabaceae',
                'genus' => 'Inga',
                'name' => 'edulis',
                'authority' => 'Mart.',
            ]
        );

        $response->assertRedirect(route('catalogs.index'));
        $response->assertSessionHas('message', 'catalogs.species_registered');
        $this->assertDatabaseHas('catalog_species', [
            'project_id' => $this->project->id,
            'genus' => 'Inga',
            'name' => 'edulis',
        ]);
    }

    public function test_genus_and_name_are_required()
    {
        $user = $this->userWithCapability($this->project, 'edit_catalog');

        $response = $this->actingAs($user)->post(
            route('catalogs.species.register', $this->project),
            ['family' => 'Fabaceae']
        );

        $response->assertSessionHasErrors(['genus', 'name']);
        $this->assertDatabaseCount('catalog_species', 0);
    }

    public function test_member_without_edit_capability_cannot_register_a_species()
    {
        $user = $this->userWithCapability($this->project, 'edit_catalog', false);

        $response = $this->actingAs($user)->post(
            route('catalogs.species.register', $this->project),
            ['genus' => 'Inga', 'name' => 'edulis']
        );

        $response->assertRedirect(route('catalogs.index'));
        $response->assertSessionHas('message', 'catalogs.no_access');
        $this->assertDatabaseCount('catalog_species', 0);
    }

    public function test_outsider_cannot_register_a_species()
    {
        $response = $this->actingAs($this->outsider())->post(
            route('catalogs.species.register', $this->project),
            ['genus' => 'Inga', 'name' => 'edulis']
        );

        $response->assertRedirect(route('catalogs.index'));
        $this->assertDatabaseCount('catalog_species', 0);
    }

    private function species(array $attributes = []): CatalogSpecies
    {
        return CatalogSpecies::factory()->create(array_merge(
            ['project_id' => $this->project->id],
            $attributes,
        ));
    }

    public function test_empty_catalog_redirects_to_the_index()
    {
        $user = $this->userWithCapability($this->project, 'view_catalog');

        $response = $this->actingAs($user)->get(
            route('catalogs.show', $this->project)
        );

        $response->assertRedirect(route('catalogs.index'));
    }

    public function test_viewer_sees_all_species_in_taxonomic_order()
    {
        $user = $this->userWithCapability($this->project, 'view_catalog');
        $this->species(['family' => 'Urticaceae', 'genus' => 'Cecropia', 'name' => 'obtusifolia']);
        $this->species(['family' => 'Fabaceae', 'genus' => 'Inga', 'name' => 'edulis']);

        $response = $this->actingAs($user)->get(
            route('catalogs.show', $this->project)
        );

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Catalog/SpeciesIndex')
            ->has('species.data', 2)
            ->where('species.data.0.genus', 'Inga')
            ->where('species.data.1.genus', 'Cecropia')
            ->where('filters.sort', 'family')
            ->has('families', 2)
            ->has('genera', 2)
        );
    }

    public function test_search_matches_a_scientific_name()
    {
        $user = $this->userWithCapability($this->project, 'view_catalog');
        $this->species(['genus' => 'Cecropia', 'name' => 'obtusifolia']);
        $this->species(['genus' => 'Inga', 'name' => 'edulis']);

        $response = $this->actingAs($user)->get(
            route('catalogs.show', ['project' => $this->project, 'q' => 'cecropia'])
        );

        $response->assertInertia(fn (Assert $page) => $page
            ->has('species.data', 1)
            ->where('species.data.0.genus', 'Cecropia')
            ->where('filters.q', 'cecropia')
        );
    }

    public function test_search_matches_a_linked_interview_name()
    {
        $user = $this->userWithCapability($this->project, 'view_catalog');
        $species = $this->species(['genus' => 'Cecropia', 'name' => 'obtusifolia']);
        InstanceAnswer::factory()->create([
            'catalog_species_id' => $species->id,
            'answer' => 'guarumo',
        ]);
        $this->species(['genus' => 'Inga', 'name' => 'edulis']);

        $response = $this->actingAs($user)->get(
            route('catalogs.show', ['project' => $this->project, 'q' => 'guarumo'])
        );

        $response->assertInertia(fn (Assert $page) => $page
            ->has('species.data', 1)
            ->where('species.data.0.genus', 'Cecropia')
        );
    }

    public function test_family_filter_narrows_the_list()
    {
        $user = $this->userWithCapability($this->project, 'view_catalog');
        $this->species(['family' => 'Urticaceae', 'genus' => 'Cecropia']);
        $this->species(['family' => 'Fabaceae', 'genus' => 'Inga']);

        $response = $this->actingAs($user)->get(
            route('catalogs.show', ['project' => $this->project, 'family' => 'Fabaceae'])
        );

        $response->assertInertia(fn (Assert $page) => $page
            ->has('species.data', 1)
            ->where('species.data.0.genus', 'Inga')
            ->where('filters.family', 'Fabaceae')
        );
    }

    public function test_link_status_filter_selects_unlinked_species()
    {
        $user = $this->userWithCapability($this->project, 'view_catalog');
        $linked = $this->species(['genus' => 'Cecropia']);
        InstanceAnswer::factory()->create(['catalog_species_id' => $linked->id]);
        $this->species(['genus' => 'Inga']);

        $response = $this->actingAs($user)->get(
            route('catalogs.show', ['project' => $this->project, 'link' => 'unlinked'])
        );

        $response->assertInertia(fn (Assert $page) => $page
            ->has('species.data', 1)
            ->where('species.data.0.genus', 'Inga')
        );
    }

    public function test_empty_search_result_stays_on_the_page()
    {
        $user = $this->userWithCapability($this->project, 'view_catalog');
        $this->species(['genus' => 'Cecropia', 'name' => 'obtusifolia']);

        $response = $this->actingAs($user)->get(
            route('catalogs.show', ['project' => $this->project, 'q' => 'zzzzzz'])
        );

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Catalog/SpeciesIndex')
            ->has('species.data', 0)
        );
    }

    public function test_outsider_cannot_view_the_catalog()
    {
        $this->species(['genus' => 'Cecropia']);

        $response = $this->actingAs($this->outsider())->get(
            route('catalogs.show', $this->project)
        );

        $response->assertRedirect(route('catalogs.index'));
    }
}
