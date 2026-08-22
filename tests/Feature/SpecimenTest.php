<?php

namespace Tests\Feature;

use App\Models\CatalogSpecies;
use App\Models\Determination;
use App\Models\Project;
use App\Models\Specimen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

/**
 * Recording the physical collection behind a taxon.
 * See docs/decisions/0008-specimens-and-determinations.md.
 */
class SpecimenTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    private CatalogSpecies $species;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create(['accession_prefix' => 'MML']);
        $this->species = CatalogSpecies::factory()->create([
            'project_id' => $this->project->id,
        ]);
    }

    private function storeRoute(): string
    {
        return route('catalogs.specimens.store', [
            'project' => $this->project->id,
            'species' => $this->species->id,
        ]);
    }

    public function test_an_editor_can_register_a_collection_against_a_taxon()
    {
        $user = $this->userWithCapability($this->project, 'edit_catalog');

        $this->actingAs($user)->post($this->storeRoute(), [
            'collection_number' => '042',
            'collector' => 'M. Menéndez',
            'collected_on' => '2026-03-14',
            'locality' => 'Cafetal above the school',
            'repository' => 'Community herbarium',
            'determiner' => 'M. Menéndez',
        ])->assertRedirect();

        $specimen = Specimen::sole();

        $this->assertSame($this->project->id, $specimen->project_id);
        $this->assertSame('042', $specimen->collection_number);
        $this->assertSame('Community herbarium', $specimen->repository);

        // Registering against a taxon asserts a determination, current by
        // definition — it is the only one there is.
        $this->assertSame($this->species->id, $specimen->currentDetermination->catalog_species_id);
        $this->assertSame('M. Menéndez', $specimen->currentDetermination->determiner);
    }

    public function test_it_can_mint_an_accession_number_from_the_project_sequence()
    {
        $user = $this->userWithCapability($this->project, 'edit_catalog');

        $this->actingAs($user)->post($this->storeRoute(), [
            'collector' => 'M. Menéndez',
            'mint_accession' => true,
        ])->assertRedirect();

        $this->assertSame('MML-0001', Specimen::sole()->accession_number);
    }

    public function test_a_number_the_researcher_types_is_kept_as_typed()
    {
        $user = $this->userWithCapability($this->project, 'edit_catalog');

        $this->actingAs($user)->post($this->storeRoute(), [
            'accession_number' => 'EXISTING-77',
        ])->assertRedirect();

        $this->assertSame('EXISTING-77', Specimen::sole()->accession_number);
    }

    public function test_a_collection_may_be_registered_without_a_voucher()
    {
        $user = $this->userWithCapability($this->project, 'edit_catalog');

        $this->actingAs($user)->post($this->storeRoute(), [
            'collector' => 'M. Menéndez',
        ])->assertRedirect();

        // Capture never blocks: market surveys and observation are legitimately
        // unvouchered, and the absence is reported rather than refused.
        $this->assertFalse(Specimen::sole()->isVouchered());
    }

    public function test_an_accession_number_cannot_repeat_within_a_project()
    {
        $user = $this->userWithCapability($this->project, 'edit_catalog');

        Specimen::factory()->create([
            'project_id' => $this->project->id,
            'accession_number' => 'MML-0001',
        ]);

        $this->actingAs($user)
            ->post($this->storeRoute(), ['accession_number' => 'MML-0001'])
            ->assertSessionHasErrors('accession_number');

        $this->assertSame(1, Specimen::count());
    }

    public function test_the_same_number_may_be_used_by_a_different_project()
    {
        $other = Project::factory()->create();
        Specimen::factory()->create([
            'project_id' => $other->id,
            'accession_number' => 'MML-0001',
        ]);

        $user = $this->userWithCapability($this->project, 'edit_catalog');

        $this->actingAs($user)
            ->post($this->storeRoute(), ['accession_number' => 'MML-0001'])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Specimen::count());
    }

    public function test_a_viewer_without_edit_catalog_cannot_register_one()
    {
        // Asked for by the flag that must be FALSE: the first role carrying
        // view_catalog is the project administrator, which also edits, so
        // selecting on view_catalog would quietly test an admin.
        $user = $this->userWithCapability($this->project, 'edit_catalog', false);

        $this->actingAs($user)
            ->post($this->storeRoute(), ['collector' => 'Someone'])
            ->assertRedirect(route('catalogs.index'));

        $this->assertSame(0, Specimen::count());
    }

    public function test_a_stranger_to_the_project_cannot_register_one()
    {
        $this->actingAs($this->outsider())
            ->post($this->storeRoute(), ['collector' => 'Someone'])
            ->assertRedirect(route('catalogs.index'));

        $this->assertSame(0, Specimen::count());
    }

    public function test_it_refuses_a_taxon_from_another_project()
    {
        $user = $this->userWithCapability($this->project, 'edit_catalog');
        $foreign = CatalogSpecies::factory()->create();

        $this->actingAs($user)->post(route('catalogs.specimens.store', [
            'project' => $this->project->id,
            'species' => $foreign->id,
        ]), ['collector' => 'Someone'])->assertRedirect(route('catalogs.index'));

        $this->assertSame(0, Specimen::count());
    }

    public function test_an_editor_can_correct_a_collection()
    {
        $user = $this->userWithCapability($this->project, 'edit_catalog');
        $specimen = Specimen::factory()->create(['project_id' => $this->project->id]);
        Determination::factory()->create([
            'specimen_id' => $specimen->id,
            'catalog_species_id' => $this->species->id,
            'determiner' => 'Typo',
        ]);

        $this->actingAs($user)->patch(
            route('catalogs.specimens.update', [
                'project' => $this->project->id,
                'specimen' => $specimen->id,
            ]),
            ['collector' => 'Corrected', 'determiner' => 'Also corrected']
        )->assertRedirect();

        $this->assertSame('Corrected', $specimen->fresh()->collector);
        $this->assertSame('Also corrected', $specimen->fresh()->currentDetermination->determiner);
    }

    public function test_minting_never_overwrites_a_number_the_specimen_already_has()
    {
        $user = $this->userWithCapability($this->project, 'edit_catalog');
        $specimen = Specimen::factory()->create([
            'project_id' => $this->project->id,
            'accession_number' => 'ALREADY-1',
        ]);

        $this->actingAs($user)->patch(
            route('catalogs.specimens.update', [
                'project' => $this->project->id,
                'specimen' => $specimen->id,
            ]),
            ['accession_number' => 'ALREADY-1', 'mint_accession' => true]
        )->assertRedirect();

        // A number already written on a label and cited elsewhere is not ours
        // to change.
        $this->assertSame('ALREADY-1', $specimen->fresh()->accession_number);
    }

    public function test_an_editor_can_delete_a_collection_and_its_determinations()
    {
        $user = $this->userWithCapability($this->project, 'edit_catalog');
        $specimen = Specimen::factory()->create(['project_id' => $this->project->id]);
        $determination = Determination::factory()->create(['specimen_id' => $specimen->id]);

        $this->actingAs($user)->delete(route('catalogs.specimens.destroy', [
            'project' => $this->project->id,
            'specimen' => $specimen->id,
        ]))->assertRedirect();

        $this->assertNull($specimen->fresh());
        $this->assertNull($determination->fresh());
    }

    public function test_a_specimen_from_another_project_cannot_be_touched()
    {
        $user = $this->userWithCapability($this->project, 'edit_catalog');
        $foreign = Specimen::factory()->create();

        $this->actingAs($user)->delete(route('catalogs.specimens.destroy', [
            'project' => $this->project->id,
            'specimen' => $foreign->id,
        ]))->assertRedirect(route('catalogs.index'));

        $this->assertNotNull($foreign->fresh());
    }

    public function test_the_species_page_lists_the_collections_and_the_next_number()
    {
        $user = $this->userWithCapability($this->project, 'edit_catalog');
        $specimen = Specimen::factory()->create([
            'project_id' => $this->project->id,
            'accession_number' => 'MML-0009',
            'collector' => 'M. Menéndez',
        ]);
        Determination::factory()->create([
            'specimen_id' => $specimen->id,
            'catalog_species_id' => $this->species->id,
            'determiner' => 'A determiner',
        ]);

        $this->actingAs($user)->get(route('catalogs.species.show', [
            'project' => $this->project->id,
            'species' => $this->species->id,
        ]))->assertInertia(fn (Assert $page) => $page
            ->component('Catalog/SpeciesShow')
            ->has('specimens', 1)
            ->where('specimens.0.accession_number', 'MML-0009')
            ->where('specimens.0.collector', 'M. Menéndez')
            ->where('specimens.0.determiner', 'A determiner')
            ->where('specimens.0.is_vouchered', true)
            ->where('nextAccessionNumber', 'MML-0001')
        );
    }
}
