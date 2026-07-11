<?php

namespace Tests\Feature;

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
}
