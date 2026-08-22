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
 * The order here follows the field: collected and recorded first, identified
 * later, deposited later still.
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

    private function editor()
    {
        return $this->userWithCapability($this->project, 'edit_catalog');
    }

    /**
     * The first seeded role carrying view_catalog is the project administrator,
     * which also edits — so a read-only user must be asked for by the flag that
     * has to be false, or the test quietly checks an admin.
     */
    private function viewer()
    {
        return $this->userWithCapability($this->project, 'edit_catalog', false);
    }

    private function specimen(array $attributes = []): Specimen
    {
        return Specimen::factory()->create(
            array_merge(['project_id' => $this->project->id], $attributes)
        );
    }

    private function url(string $name, array $extra = []): string
    {
        return route($name, array_merge(['project' => $this->project->id], $extra));
    }

    // ---------------------------------------------------------- collecting ---

    public function test_a_collection_can_be_recorded_before_anyone_identifies_it()
    {
        $this->actingAs($this->editor())->post($this->url('catalogs.specimens.store'), [
            'collection_number' => '042',
            'collector' => 'M. Menéndez',
            'collected_on' => '2026-03-14',
            'locality' => 'Cafetal above the school',
        ])->assertRedirect();

        $specimen = Specimen::sole();

        $this->assertSame('042', $specimen->collection_number);
        // No determination at all: nobody has looked at it yet, and an empty
        // determination would assert that someone had and failed.
        $this->assertSame(0, $specimen->determinations()->count());
        $this->assertNull($specimen->currentDetermination);
    }

    public function test_recording_a_collection_asks_for_nothing_about_the_taxon()
    {
        $this->actingAs($this->editor())
            ->post($this->url('catalogs.specimens.store'), ['collector' => 'M. Menéndez'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Specimen::count());
    }

    public function test_the_species_page_shortcut_records_the_identification_too()
    {
        $this->actingAs($this->editor())->post(
            $this->url('catalogs.specimens.store-for-species', ['species' => $this->species->id]),
            ['collector' => 'M. Menéndez', 'determiner' => 'M. Menéndez']
        )->assertRedirect();

        $specimen = Specimen::sole();

        $this->assertSame($this->species->id, $specimen->currentDetermination->catalog_species_id);
        $this->assertSame('M. Menéndez', $specimen->currentDetermination->determiner);
    }

    // --------------------------------------------------------- identifying ---

    public function test_a_collection_can_be_identified_later()
    {
        $specimen = $this->specimen();

        $this->actingAs($this->editor())->post(
            $this->url('catalogs.specimens.determine', ['specimen' => $specimen->id]),
            [
                'catalog_species_id' => $this->species->id,
                'determiner' => 'A. Botanist',
                'determined_on' => '2026-06-01',
                'qualifier' => 'cf',
            ]
        )->assertRedirect();

        $current = $specimen->fresh()->currentDetermination;

        $this->assertSame($this->species->id, $current->catalog_species_id);
        $this->assertSame('A. Botanist', $current->determiner);
        $this->assertSame('cf', $current->qualifier);
    }

    public function test_revising_an_identification_supersedes_rather_than_replaces()
    {
        $specimen = $this->specimen();
        $wasThought = CatalogSpecies::factory()->create(['project_id' => $this->project->id]);

        Determination::factory()->create([
            'specimen_id' => $specimen->id,
            'catalog_species_id' => $wasThought->id,
            'determiner' => 'First opinion',
        ]);

        $this->actingAs($this->editor())->post(
            $this->url('catalogs.specimens.determine', ['specimen' => $specimen->id]),
            ['catalog_species_id' => $this->species->id, 'determiner' => 'Second opinion']
        )->assertRedirect();

        // What was thought before is part of the record.
        $this->assertSame(2, $specimen->determinations()->count());
        $this->assertSame('Second opinion', $specimen->fresh()->currentDetermination->determiner);
        $this->assertSame(
            $wasThought->id,
            $specimen->determinations()->where('is_current', false)->sole()->catalog_species_id
        );
    }

    public function test_examined_but_unnameable_is_recordable_as_a_determination()
    {
        $specimen = $this->specimen();

        $this->actingAs($this->editor())->post(
            $this->url('catalogs.specimens.determine', ['specimen' => $specimen->id]),
            ['catalog_species_id' => null, 'determiner' => 'A. Botanist']
        )->assertRedirect();

        $current = $specimen->fresh()->currentDetermination;

        // Different from nobody having looked: someone examined it and could
        // not name it, and said so.
        $this->assertNotNull($current);
        $this->assertNull($current->catalog_species_id);
        $this->assertSame('A. Botanist', $current->determiner);
    }

    public function test_it_refuses_a_taxon_belonging_to_another_project()
    {
        $specimen = $this->specimen();
        $foreign = CatalogSpecies::factory()->create();

        $this->actingAs($this->editor())->post(
            $this->url('catalogs.specimens.determine', ['specimen' => $specimen->id]),
            ['catalog_species_id' => $foreign->id]
        )->assertSessionHasErrors('catalog_species_id');
    }

    // ---------------------------------------------------------- depositing ---

    public function test_depositing_records_the_repository_and_mints_a_number()
    {
        $specimen = $this->specimen();

        $this->actingAs($this->editor())->post(
            $this->url('catalogs.specimens.deposit', ['specimen' => $specimen->id]),
            ['repository' => 'Community herbarium', 'mint_accession' => true]
        )->assertRedirect();

        $this->assertSame('MML-0001', $specimen->fresh()->accession_number);
        $this->assertSame('Community herbarium', $specimen->fresh()->repository);
    }

    public function test_a_number_the_researcher_types_is_kept_as_typed()
    {
        $specimen = $this->specimen();

        $this->actingAs($this->editor())->post(
            $this->url('catalogs.specimens.deposit', ['specimen' => $specimen->id]),
            ['accession_number' => 'EXISTING-77']
        )->assertRedirect();

        $this->assertSame('EXISTING-77', $specimen->fresh()->accession_number);
    }

    public function test_minting_never_overwrites_a_number_already_carried()
    {
        $specimen = $this->specimen(['accession_number' => 'ALREADY-1']);

        $this->actingAs($this->editor())->post(
            $this->url('catalogs.specimens.deposit', ['specimen' => $specimen->id]),
            ['mint_accession' => true]
        )->assertRedirect();

        // Written on a label and cited elsewhere; not ours to change.
        $this->assertSame('ALREADY-1', $specimen->fresh()->accession_number);
    }

    public function test_an_accession_number_cannot_repeat_within_a_project()
    {
        $this->specimen(['accession_number' => 'MML-0001']);
        $another = $this->specimen();

        $this->actingAs($this->editor())->post(
            $this->url('catalogs.specimens.deposit', ['specimen' => $another->id]),
            ['accession_number' => 'MML-0001']
        )->assertSessionHasErrors('accession_number');
    }

    public function test_the_same_number_may_be_used_by_a_different_project()
    {
        Specimen::factory()->create([
            'project_id' => Project::factory()->create()->id,
            'accession_number' => 'MML-0001',
        ]);
        $mine = $this->specimen();

        $this->actingAs($this->editor())->post(
            $this->url('catalogs.specimens.deposit', ['specimen' => $mine->id]),
            ['accession_number' => 'MML-0001']
        )->assertSessionHasNoErrors();

        $this->assertSame('MML-0001', $mine->fresh()->accession_number);
    }

    // ------------------------------------------------------- the two lists ---

    public function test_the_project_list_counts_what_is_still_unidentified()
    {
        $identified = $this->specimen();
        Determination::factory()->create([
            'specimen_id' => $identified->id,
            'catalog_species_id' => $this->species->id,
        ]);

        $examinedButUnnameable = $this->specimen();
        Determination::factory()->indeterminate()->create([
            'specimen_id' => $examinedButUnnameable->id,
        ]);

        $this->specimen(['accession_number' => 'MML-0003']);

        $this->actingAs($this->editor())
            ->get($this->url('catalogs.specimens.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Specimens')
                ->has('specimens', 3)
                ->where('summary.total', 3)
                ->where('summary.vouchered', 1)
                ->where('summary.unidentified', 2)
                ->has('catalog', 1)
                ->where('nextAccessionNumber', 'MML-0001')
            );
    }

    public function test_the_species_page_shows_only_what_is_determined_as_that_taxon()
    {
        $determined = $this->specimen(['accession_number' => 'MML-0009']);
        Determination::factory()->create([
            'specimen_id' => $determined->id,
            'catalog_species_id' => $this->species->id,
        ]);

        // Unidentified material belongs to the project, not to any taxon.
        $this->specimen();

        $this->actingAs($this->editor())->get(route('catalogs.species.show', [
            'project' => $this->project->id,
            'species' => $this->species->id,
        ]))->assertInertia(fn (Assert $page) => $page
            ->has('specimens', 1)
            ->where('specimens.0.accession_number', 'MML-0009')
            ->where('specimens.0.is_determined', true)
        );
    }

    // ------------------------------------------------------ authorization ---

    public function test_a_viewer_can_read_the_list_but_not_add_to_it()
    {
        $this->actingAs($this->viewer())
            ->get($this->url('catalogs.specimens.index'))
            ->assertInertia(fn (Assert $page) => $page->where('canEdit', false));

        $this->actingAs($this->viewer())
            ->post($this->url('catalogs.specimens.store'), ['collector' => 'Someone'])
            ->assertRedirect(route('catalogs.index'));

        $this->assertSame(0, Specimen::count());
    }

    public function test_the_project_list_offers_a_route_to_specimens_with_an_empty_catalog()
    {
        $emptyCatalog = Project::factory()->create();
        $user = $this->userWithCapability($emptyCatalog, 'view_catalog');
        Specimen::factory()->create(['project_id' => $emptyCatalog->id]);

        // catalogs.show redirects away when no species exist, so the catalog
        // index is the only page a brand-new project can reach — and that is
        // precisely the project whose specimens come before its taxa.
        $this->actingAs($user)
            ->get(route('catalogs.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Index')
                ->where('projects.0.catalog_species_count', 0)
                ->where('projects.0.specimen_count', 1)
            );
    }

    public function test_a_stranger_cannot_see_the_list_at_all()
    {
        $this->actingAs($this->outsider())
            ->get($this->url('catalogs.specimens.index'))
            ->assertRedirect(route('catalogs.index'));
    }

    public function test_a_specimen_from_another_project_cannot_be_touched()
    {
        $foreign = Specimen::factory()->create();

        foreach (['catalogs.specimens.determine', 'catalogs.specimens.deposit'] as $action) {
            $this->actingAs($this->editor())
                ->post($this->url($action, ['specimen' => $foreign->id]), [])
                ->assertRedirect(route('catalogs.index'));
        }

        $this->actingAs($this->editor())
            ->delete($this->url('catalogs.specimens.destroy', ['specimen' => $foreign->id]))
            ->assertRedirect(route('catalogs.index'));

        $this->assertNotNull($foreign->fresh());
    }

    public function test_an_editor_can_correct_the_collection_itself()
    {
        $specimen = $this->specimen(['collector' => 'Typo']);

        $this->actingAs($this->editor())->patch(
            $this->url('catalogs.specimens.update', ['specimen' => $specimen->id]),
            ['collector' => 'Corrected']
        )->assertRedirect();

        $this->assertSame('Corrected', $specimen->fresh()->collector);
    }

    public function test_deleting_a_collection_takes_its_determinations_with_it()
    {
        $specimen = $this->specimen();
        $determination = Determination::factory()->create(['specimen_id' => $specimen->id]);

        $this->actingAs($this->editor())
            ->delete($this->url('catalogs.specimens.destroy', ['specimen' => $specimen->id]))
            ->assertRedirect();

        $this->assertNull($specimen->fresh());
        $this->assertNull($determination->fresh());
    }
}
