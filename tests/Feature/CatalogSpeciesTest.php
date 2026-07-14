<?php

namespace Tests\Feature;

use App\Models\CatalogSpecies;
use App\Models\InstanceAnswer;
use App\Models\Project;
use App\Models\ProjectAccess;
use App\Models\ProjectCapability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_editor_register_link_redirects_to_the_hub_modal()
    {
        $user = $this->userWithCapability($this->project, 'edit_catalog');

        $response = $this->actingAs($user)->get(
            route('catalogs.species.register', $this->project)
        );

        $response->assertRedirect(
            route('catalogs.index', ['create' => $this->project->id])
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

    /**
     * A catalog-only role: can view the catalog, but has none of the
     * data-viewing capabilities. Every seeded role that can view the catalog
     * also has data access, so this bespoke role is built explicitly.
     */
    private function catalogOnlyViewer(): User
    {
        $capability = ProjectCapability::create([
            'name' => 'Catalog only',
            'manage_project' => false,
            'manage_users' => false,
            'manage_forms' => false,
            'record_data' => false,
            'manage_data' => false,
            'generate_reports' => false,
            'view_catalog' => true,
            'edit_catalog' => false,
        ]);

        $user = User::factory()->create();
        ProjectAccess::factory()->create([
            'user_id' => $user->id,
            'project_id' => $this->project->id,
            'project_capability_id' => $capability->id,
        ]);

        return $user;
    }

    public function test_species_page_shows_the_linked_count_but_gates_the_records_from_a_catalog_only_viewer()
    {
        $species = $this->species(['genus' => 'Cecropia', 'name' => 'obtusifolia']);
        InstanceAnswer::factory()->create([
            'catalog_species_id' => $species->id,
            'answer' => 'guarumo',
        ]);

        $response = $this->actingAs($this->catalogOnlyViewer())->get(
            route('catalogs.species.show', [
                'project' => $this->project,
                'species' => $species,
            ])
        );

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Catalog/SpeciesShow')
            ->where('canViewData', false)
            ->where('linkedCount', 1)
            ->where('linkedRecords', null)
        );
    }

    public function test_data_capable_viewer_sees_the_linked_records()
    {
        $species = $this->species(['genus' => 'Cecropia', 'name' => 'obtusifolia']);
        InstanceAnswer::factory()->create([
            'catalog_species_id' => $species->id,
            'answer' => 'guarumo',
        ]);

        // The seeded catalog-viewing roles all carry generate_reports.
        $user = $this->userWithCapability($this->project, 'view_catalog');

        $response = $this->actingAs($user)->get(
            route('catalogs.species.show', [
                'project' => $this->project,
                'species' => $species,
            ])
        );

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Catalog/SpeciesShow')
            ->where('canViewData', true)
            ->where('linkedCount', 1)
            ->has('linkedRecords.data', 1)
            ->where('linkedRecords.data.0.recorded_name', 'guarumo')
        );
    }

    public function test_linked_record_name_falls_back_to_the_binomial_when_no_free_text()
    {
        $species = $this->species(['genus' => 'Cecropia', 'name' => 'obtusifolia']);
        InstanceAnswer::factory()->create([
            'catalog_species_id' => $species->id,
            'answer' => '',
        ]);

        $user = $this->userWithCapability($this->project, 'view_catalog');

        $response = $this->actingAs($user)->get(
            route('catalogs.species.show', [
                'project' => $this->project,
                'species' => $species,
            ])
        );

        $response->assertInertia(fn (Assert $page) => $page
            ->where('linkedRecords.data.0.recorded_name', 'Cecropia obtusifolia')
        );
    }

    /**
     * Fakes WFO's taxonNameById for one name, with its accepted-usage node and a
     * classification path carrying the family.
     */
    private function fakeWfoName(
        string $id,
        string $noAuthors,
        string $genus,
        string $author,
        array $accepted,
        string $family
    ): void {
        Http::fake([
            'list.worldfloraonline.org/*' => Http::response([
                'data' => [
                    'taxonNameById' => [
                        'id' => $id,
                        'fullNameStringPlain' => trim("{$noAuthors} {$author}"),
                        'fullNameStringHtml' => "<i>{$noAuthors}</i> {$author}",
                        'fullNameStringNoAuthorsPlain' => $noAuthors,
                        'genusString' => $genus,
                        'authorsString' => $author,
                        'currentPreferredUsage' => [
                            'hasName' => $accepted,
                            'path' => [
                                ['hasName' => ['nameString' => $genus, 'rank' => 'genus']],
                                ['hasName' => ['nameString' => $family, 'rank' => 'family']],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);
    }

    private function acceptedNode(string $id, string $noAuthors, string $genus, string $author): array
    {
        return [
            'id' => $id,
            'fullNameStringPlain' => trim("{$noAuthors} {$author}"),
            'fullNameStringHtml' => "<i>{$noAuthors}</i> {$author}",
            'fullNameStringNoAuthorsPlain' => $noAuthors,
            'genusString' => $genus,
            'authorsString' => $author,
        ];
    }

    public function test_editor_can_accept_a_wfo_name_and_update_the_species()
    {
        $species = $this->species([
            'family' => 'Acanthaceae',
            'genus' => 'Justicia',
            'name' => 'carthagenensis', // recorded misspelling
            'authority' => 'Jacq.',
        ]);
        $this->fakeWfoName(
            'wfo-0000354479', 'Justicia carthaginensis', 'Justicia', 'Jacq.',
            $this->acceptedNode('wfo-0000354479', 'Justicia carthaginensis', 'Justicia', 'Jacq.'),
            'Acanthaceae'
        );

        $user = $this->userWithCapability($this->project, 'edit_catalog');

        $response = $this->actingAs($user)->patch(
            route('catalogs.species.update', ['project' => $this->project, 'species' => $species]),
            ['wfo_id' => 'wfo-0000354479']
        );

        $response->assertRedirect(route('catalogs.species.show', [
            'project' => $this->project->id,
            'species' => $species->id,
        ]));
        $response->assertSessionHas('message', 'catalogs.accept.updated');

        $species->refresh();
        $this->assertSame('carthaginensis', $species->name); // corrected spelling
        $this->assertSame('Acanthaceae', $species->family);
        $this->assertSame('wfo-0000354479', $species->metadata['wfo']['id']);
    }

    public function test_accepting_a_synonym_with_use_accepted_adopts_the_accepted_name()
    {
        $species = $this->species([
            'family' => 'Acanthaceae',
            'genus' => 'Justicia',
            'name' => 'carthagenensis',
            'authority' => 'Willd. ex Nees',
        ]);
        $this->fakeWfoName(
            'wfo-0000354748', 'Justicia carthagenensis', 'Justicia', 'Willd. ex Nees',
            $this->acceptedNode('wfo-0000402095', 'Ruellia blechum', 'Ruellia', 'L.'),
            'Acanthaceae'
        );

        $user = $this->userWithCapability($this->project, 'edit_catalog');

        $this->actingAs($user)->patch(
            route('catalogs.species.update', ['project' => $this->project, 'species' => $species]),
            ['wfo_id' => 'wfo-0000354748', 'use_accepted' => true]
        );

        $species->refresh();
        $this->assertSame('Ruellia', $species->genus);
        $this->assertSame('blechum', $species->name);
        $this->assertSame('L.', $species->authority);
    }

    public function test_viewer_without_edit_capability_cannot_accept_a_name()
    {
        Http::fake();
        $species = $this->species(['genus' => 'Justicia', 'name' => 'carthagenensis']);
        // "Usuario de consulta": view_catalog but not edit_catalog.
        $user = $this->userWithCapability($this->project, 'edit_catalog', false);

        $response = $this->actingAs($user)->patch(
            route('catalogs.species.update', ['project' => $this->project, 'species' => $species]),
            ['wfo_id' => 'wfo-0000354479']
        );

        $response->assertRedirect(route('catalogs.index'));
        $this->assertSame('carthagenensis', $species->refresh()->name);
        Http::assertNothingSent();
    }

    public function test_fetch_distribution_resolves_caches_and_returns_the_range()
    {
        $species = $this->species(['genus' => 'Cecropia', 'name' => 'obtusifolia']);
        Http::fake([
            'api.gbif.org/v1/species/match*' => Http::response([
                'usageKey' => 2984473, 'scientificName' => 'Cecropia obtusifolia Bertol.',
                'status' => 'ACCEPTED', 'matchType' => 'EXACT',
            ]),
            'api.gbif.org/v1/species/*/distributions*' => Http::response(['results' => [
                ['locationId' => 'TDWG:BLZ', 'locality' => 'Belize', 'source' => 'The World Checklist of Vascular Plants (WCVP)'],
                ['locationId' => 'TDWG:HWI', 'locality' => 'Hawaii', 'source' => 'The World Checklist of Vascular Plants (WCVP)', 'establishmentMeans' => 'INTRODUCED'],
            ]]),
        ]);

        $user = $this->userWithCapability($this->project, 'view_catalog');

        $response = $this->actingAs($user)->postJson(
            route('catalogs.species.distribution', ['project' => $this->project, 'species' => $species])
        );

        $response->assertOk();
        $response->assertJsonPath('native.0.name', 'Belize');
        $response->assertJsonPath('introduced.0.name', 'Hawaii');

        // Cached into the entry's metadata for subsequent page loads.
        $species->refresh();
        $this->assertSame('Belize', $species->metadata['distribution']['native'][0]['name']);
        $this->assertArrayHasKey('fetched_at', $species->metadata['distribution']);
    }

    public function test_outsider_cannot_fetch_distribution()
    {
        Http::fake();
        $species = $this->species(['genus' => 'Cecropia', 'name' => 'obtusifolia']);

        $response = $this->actingAs($this->outsider())->postJson(
            route('catalogs.species.distribution', ['project' => $this->project, 'species' => $species])
        );

        $response->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_fetch_distribution_returns_502_when_gbif_is_unreachable()
    {
        $species = $this->species(['genus' => 'Cecropia', 'name' => 'obtusifolia']);
        Http::fake(['api.gbif.org/*' => Http::response('', 500)]);

        $user = $this->userWithCapability($this->project, 'view_catalog');

        $response = $this->actingAs($user)->postJson(
            route('catalogs.species.distribution', ['project' => $this->project, 'species' => $species])
        );

        $response->assertStatus(502);
        $response->assertJsonPath('error', 'gbif_unreachable');
    }

    public function test_preview_returns_the_current_and_proposed_taxonomy()
    {
        $species = $this->species([
            'family' => 'Acanthaceae',
            'genus' => 'Justicia',
            'name' => 'carthagenensis',
            'authority' => 'Jacq.',
        ]);
        $this->fakeWfoName(
            'wfo-0000354479', 'Justicia carthaginensis', 'Justicia', 'Jacq.',
            $this->acceptedNode('wfo-0000354479', 'Justicia carthaginensis', 'Justicia', 'Jacq.'),
            'Acanthaceae'
        );

        $user = $this->userWithCapability($this->project, 'edit_catalog');

        $response = $this->actingAs($user)->postJson(
            route('catalogs.species.wfo-preview', ['project' => $this->project, 'species' => $species]),
            ['wfo_id' => 'wfo-0000354479']
        );

        $response->assertOk();
        $response->assertJsonPath('current.name', 'carthagenensis');
        $response->assertJsonPath('proposed.name', 'carthaginensis');
        $response->assertJsonPath('proposed.family', 'Acanthaceae');
    }
}
