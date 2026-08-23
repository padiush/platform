<?php

namespace Tests\Feature;

use App\Models\CatalogSpecies;
use App\Models\CollectingPermit;
use App\Models\Determination;
use App\Models\FieldRecord;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Excel;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

/**
 * The order here follows the field: collected and recorded first, identified
 * later, deposited later still.
 * See docs/decisions/0008-specimens-and-determinations.md.
 */
class FieldRecordTest extends TestCase
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

    private function fieldRecord(array $attributes = []): FieldRecord
    {
        return FieldRecord::factory()->create(
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
        $this->actingAs($this->editor())->post($this->url('catalogs.fieldRecords.store'), [
            'collection_number' => '042',
            'collector' => 'M. Menéndez',
            'collected_on' => '2026-03-14',
            'locality' => 'Cafetal above the school',
        ])->assertRedirect();

        $fieldRecord = FieldRecord::sole();

        $this->assertSame('042', $fieldRecord->collection_number);
        // No determination at all: nobody has looked at it yet, and an empty
        // determination would assert that someone had and failed.
        $this->assertSame(0, $fieldRecord->determinations()->count());
        $this->assertNull($fieldRecord->currentDetermination);
    }

    public function test_recording_a_collection_asks_for_nothing_about_the_taxon()
    {
        $this->actingAs($this->editor())
            ->post($this->url('catalogs.fieldRecords.store'), ['collector' => 'M. Menéndez'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, FieldRecord::count());
    }

    public function test_the_species_page_shortcut_records_the_identification_too()
    {
        $this->actingAs($this->editor())->post(
            $this->url('catalogs.fieldRecords.store-for-species', ['species' => $this->species->id]),
            ['collector' => 'M. Menéndez', 'determiner' => 'M. Menéndez']
        )->assertRedirect();

        $fieldRecord = FieldRecord::sole();

        $this->assertSame($this->species->id, $fieldRecord->currentDetermination->catalog_species_id);
        $this->assertSame('M. Menéndez', $fieldRecord->currentDetermination->determiner);
    }

    // --------------------------------------------------------- identifying ---

    public function test_a_collection_can_be_identified_later()
    {
        $fieldRecord = $this->fieldRecord();

        $this->actingAs($this->editor())->post(
            $this->url('catalogs.fieldRecords.determine', ['fieldRecord' => $fieldRecord->id]),
            [
                'catalog_species_id' => $this->species->id,
                'determiner' => 'A. Botanist',
                'determined_on' => '2026-06-01',
                'qualifier' => 'cf',
            ]
        )->assertRedirect();

        $current = $fieldRecord->fresh()->currentDetermination;

        $this->assertSame($this->species->id, $current->catalog_species_id);
        $this->assertSame('A. Botanist', $current->determiner);
        $this->assertSame('cf', $current->qualifier);
    }

    public function test_revising_an_identification_supersedes_rather_than_replaces()
    {
        $fieldRecord = $this->fieldRecord();
        $wasThought = CatalogSpecies::factory()->create(['project_id' => $this->project->id]);

        Determination::factory()->create([
            'field_record_id' => $fieldRecord->id,
            'catalog_species_id' => $wasThought->id,
            'determiner' => 'First opinion',
        ]);

        $this->actingAs($this->editor())->post(
            $this->url('catalogs.fieldRecords.determine', ['fieldRecord' => $fieldRecord->id]),
            ['catalog_species_id' => $this->species->id, 'determiner' => 'Second opinion']
        )->assertRedirect();

        // What was thought before is part of the record.
        $this->assertSame(2, $fieldRecord->determinations()->count());
        $this->assertSame('Second opinion', $fieldRecord->fresh()->currentDetermination->determiner);
        $this->assertSame(
            $wasThought->id,
            $fieldRecord->determinations()->where('is_current', false)->sole()->catalog_species_id
        );
    }

    public function test_examined_but_unnameable_is_recordable_as_a_determination()
    {
        $fieldRecord = $this->fieldRecord();

        $this->actingAs($this->editor())->post(
            $this->url('catalogs.fieldRecords.determine', ['fieldRecord' => $fieldRecord->id]),
            ['catalog_species_id' => null, 'determiner' => 'A. Botanist']
        )->assertRedirect();

        $current = $fieldRecord->fresh()->currentDetermination;

        // Different from nobody having looked: someone examined it and could
        // not name it, and said so.
        $this->assertNotNull($current);
        $this->assertNull($current->catalog_species_id);
        $this->assertSame('A. Botanist', $current->determiner);
    }

    public function test_it_refuses_a_taxon_belonging_to_another_project()
    {
        $fieldRecord = $this->fieldRecord();
        $foreign = CatalogSpecies::factory()->create();

        $this->actingAs($this->editor())->post(
            $this->url('catalogs.fieldRecords.determine', ['fieldRecord' => $fieldRecord->id]),
            ['catalog_species_id' => $foreign->id]
        )->assertSessionHasErrors('catalog_species_id');
    }

    // ---------------------------------------------------------- depositing ---

    public function test_depositing_records_the_repository_and_mints_a_number()
    {
        $fieldRecord = $this->fieldRecord();

        $this->actingAs($this->editor())->post(
            $this->url('catalogs.fieldRecords.deposit', ['fieldRecord' => $fieldRecord->id]),
            ['repository' => 'Community herbarium', 'mint_accession' => true]
        )->assertRedirect();

        $this->assertSame('MML-0001', $fieldRecord->fresh()->accession_number);
        $this->assertSame('Community herbarium', $fieldRecord->fresh()->repository);
    }

    public function test_a_number_the_researcher_types_is_kept_as_typed()
    {
        $fieldRecord = $this->fieldRecord();

        $this->actingAs($this->editor())->post(
            $this->url('catalogs.fieldRecords.deposit', ['fieldRecord' => $fieldRecord->id]),
            ['accession_number' => 'EXISTING-77']
        )->assertRedirect();

        $this->assertSame('EXISTING-77', $fieldRecord->fresh()->accession_number);
    }

    public function test_minting_never_overwrites_a_number_already_carried()
    {
        $fieldRecord = $this->fieldRecord(['accession_number' => 'ALREADY-1']);

        $this->actingAs($this->editor())->post(
            $this->url('catalogs.fieldRecords.deposit', ['fieldRecord' => $fieldRecord->id]),
            ['mint_accession' => true]
        )->assertRedirect();

        // Written on a label and cited elsewhere; not ours to change.
        $this->assertSame('ALREADY-1', $fieldRecord->fresh()->accession_number);
    }

    public function test_an_accession_number_cannot_repeat_within_a_project()
    {
        $this->fieldRecord(['accession_number' => 'MML-0001']);
        $another = $this->fieldRecord();

        $this->actingAs($this->editor())->post(
            $this->url('catalogs.fieldRecords.deposit', ['fieldRecord' => $another->id]),
            ['accession_number' => 'MML-0001']
        )->assertSessionHasErrors('accession_number');
    }

    public function test_the_same_number_may_be_used_by_a_different_project()
    {
        FieldRecord::factory()->create([
            'project_id' => Project::factory()->create()->id,
            'accession_number' => 'MML-0001',
        ]);
        $mine = $this->fieldRecord();

        $this->actingAs($this->editor())->post(
            $this->url('catalogs.fieldRecords.deposit', ['fieldRecord' => $mine->id]),
            ['accession_number' => 'MML-0001']
        )->assertSessionHasNoErrors();

        $this->assertSame('MML-0001', $mine->fresh()->accession_number);
    }

    // ------------------------------------------------------- the two lists ---

    public function test_the_project_list_counts_what_is_still_unidentified()
    {
        $identified = $this->fieldRecord();
        Determination::factory()->create([
            'field_record_id' => $identified->id,
            'catalog_species_id' => $this->species->id,
        ]);

        $examinedButUnnameable = $this->fieldRecord();
        Determination::factory()->indeterminate()->create([
            'field_record_id' => $examinedButUnnameable->id,
        ]);

        $this->fieldRecord(['accession_number' => 'MML-0003']);

        $this->actingAs($this->editor())
            ->get($this->url('catalogs.fieldRecords.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/FieldRecords')
                ->has('fieldRecords', 3)
                ->where('summary.total', 3)
                ->where('summary.vouchered', 1)
                ->where('summary.unidentified', 2)
                ->has('catalog', 1)
                ->where('nextAccessionNumber', 'MML-0001')
            );
    }

    public function test_the_species_page_shows_only_what_is_determined_as_that_taxon()
    {
        $determined = $this->fieldRecord(['accession_number' => 'MML-0009']);
        Determination::factory()->create([
            'field_record_id' => $determined->id,
            'catalog_species_id' => $this->species->id,
        ]);

        // Unidentified material belongs to the project, not to any taxon.
        $this->fieldRecord();

        $this->actingAs($this->editor())->get(route('catalogs.species.show', [
            'project' => $this->project->id,
            'species' => $this->species->id,
        ]))->assertInertia(fn (Assert $page) => $page
            ->has('fieldRecords', 1)
            ->where('fieldRecords.0.accession_number', 'MML-0009')
            ->where('fieldRecords.0.is_determined', true)
        );
    }

    // ------------------------------------------------- observed, not taken ---

    public function test_a_walk_can_be_recorded_without_collecting_anything()
    {
        $this->actingAs($this->editor())->post($this->url('catalogs.fieldRecords.store'), [
            'basis_of_record' => FieldRecord::BASIS_OBSERVATION,
            'collector' => 'M. Menéndez',
            'locality' => 'Cafetal sobre la escuela',
            'vernacular_name' => 'cortez blanco',
        ])->assertSessionHasNoErrors();

        $record = FieldRecord::sole();

        $this->assertSame(FieldRecord::BASIS_OBSERVATION, $record->basis_of_record);
        $this->assertFalse($record->wasCollected());
        $this->assertSame('cortez blanco', $record->vernacular_name);
    }

    public function test_a_record_is_a_collection_unless_it_says_otherwise()
    {
        $this->actingAs($this->editor())
            ->post($this->url('catalogs.fieldRecords.store'), ['collector' => 'M. Menéndez'])
            ->assertSessionHasNoErrors();

        // Everything recorded before this existed was collected.
        $this->assertSame(FieldRecord::BASIS_PRESERVED, FieldRecord::sole()->basis_of_record);
        $this->assertTrue(FieldRecord::sole()->wasCollected());
    }

    public function test_the_vernacular_name_is_encrypted_at_rest()
    {
        $record = $this->fieldRecord(['vernacular_name' => 'cortez blanco']);

        // What an informant said is not stored differently for having been
        // typed on this screen rather than into an interview.
        $stored = DB::table('field_records')->where('id', $record->id)->value('vernacular_name');

        $this->assertNotSame('cortez blanco', $stored);
        $this->assertStringNotContainsString('cortez', (string) $stored);
        $this->assertSame('cortez blanco', $record->fresh()->vernacular_name);
    }

    public function test_it_refuses_a_basis_outside_darwin_core()
    {
        $this->actingAs($this->editor())
            ->post($this->url('catalogs.fieldRecords.store'), ['basis_of_record' => 'hearsay'])
            ->assertSessionHasErrors('basis_of_record');
    }

    public function test_something_that_was_never_collected_cannot_be_deposited()
    {
        $observed = $this->fieldRecord([
            'basis_of_record' => FieldRecord::BASIS_OBSERVATION,
        ]);

        $this->actingAs($this->editor())->post(
            $this->url('catalogs.fieldRecords.deposit', ['fieldRecord' => $observed->id]),
            ['repository' => 'Herbario', 'mint_accession' => true]
        )->assertRedirect();

        // Minting against nothing would put a voucher on material that does
        // not exist.
        $this->assertNull($observed->fresh()->accession_number);
    }

    public function test_the_list_counts_what_was_only_observed()
    {
        $this->fieldRecord(['basis_of_record' => FieldRecord::BASIS_OBSERVATION]);
        $this->fieldRecord(['basis_of_record' => FieldRecord::BASIS_OBSERVATION]);
        $this->fieldRecord(['accession_number' => 'MML-0001']);

        $this->actingAs($this->editor())
            ->get($this->url('catalogs.fieldRecords.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.total', 3)
                ->where('summary.observed', 2)
                ->where('summary.vouchered', 1)
                ->has('bases', 4)
            );
    }

    // ---------------------------------------------------------- the permit ---

    public function test_a_collection_can_be_recorded_under_a_permit()
    {
        $permit = CollectingPermit::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $this->actingAs($this->editor())->post($this->url('catalogs.fieldRecords.store'), [
            'collector' => 'M. Menéndez',
            'collecting_permit_id' => $permit->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame($permit->id, FieldRecord::sole()->collecting_permit_id);
    }

    public function test_a_collection_can_state_that_none_was_required()
    {
        $this->actingAs($this->editor())->post($this->url('catalogs.fieldRecords.store'), [
            'collector' => 'M. Menéndez',
            'permit_exemption' => 'market',
        ])->assertSessionHasNoErrors();

        $this->assertSame('market', FieldRecord::sole()->permit_exemption);
    }

    public function test_a_permit_and_an_exemption_together_are_refused()
    {
        $permit = CollectingPermit::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $this->actingAs($this->editor())->post($this->url('catalogs.fieldRecords.store'), [
            'collecting_permit_id' => $permit->id,
            'permit_exemption' => 'market',
        ])->assertSessionHasErrors();

        // The pairing has no meaning; recording it would make coverage a lie.
        $this->assertSame(0, FieldRecord::count());
    }

    public function test_it_refuses_a_permit_belonging_to_another_project()
    {
        $foreign = CollectingPermit::factory()->create();

        $this->actingAs($this->editor())->post($this->url('catalogs.fieldRecords.store'), [
            'collecting_permit_id' => $foreign->id,
        ])->assertSessionHasErrors('collecting_permit_id');
    }

    public function test_it_refuses_an_exemption_outside_the_vocabulary()
    {
        $this->actingAs($this->editor())->post($this->url('catalogs.fieldRecords.store'), [
            'permit_exemption' => 'because-i-said-so',
        ])->assertSessionHasErrors('permit_exemption');
    }

    public function test_the_list_offers_the_project_permits_to_choose_from()
    {
        CollectingPermit::factory()->create([
            'project_id' => $this->project->id,
            'authority' => 'MARN',
            'reference' => 'RES-042-2026',
        ]);
        CollectingPermit::factory()->create(); // another project's

        $this->actingAs($this->editor())
            ->get($this->url('catalogs.fieldRecords.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('permits', 1)
                ->where('permits.0.label', 'MARN · RES-042-2026')
                ->has('exemptions', 4)
            );
    }

    // ------------------------------------------------------------- export ---

    public function test_the_export_carries_the_collection_its_name_and_its_permit()
    {
        $permit = CollectingPermit::factory()->create([
            'project_id' => $this->project->id,
            'authority' => 'MARN',
            'reference' => 'RES-042-2026',
        ]);

        $fieldRecord = $this->fieldRecord([
            'accession_number' => 'MML-0001',
            'collection_number' => '042',
            'collector' => 'M. Menéndez',
            'collecting_permit_id' => $permit->id,
        ]);
        Determination::factory()->create([
            'field_record_id' => $fieldRecord->id,
            'catalog_species_id' => $this->species->id,
            'determiner' => 'A. Botanist',
            'qualifier' => 'cf',
            'is_current' => true,
        ]);

        $csv = $this->actingAs($this->editor())
            ->get($this->url('catalogs.fieldRecords.export').'?format=csv')
            ->streamedContent();

        // Darwin Core terms, so the sheet can be read or mapped by anyone who
        // works with occurrence data.
        $this->assertStringContainsString('catalogNumber', $csv);
        $this->assertStringContainsString('identificationQualifier', $csv);

        $this->assertStringContainsString('MML-0001', $csv);
        $this->assertStringContainsString('M. Menéndez', $csv);
        $this->assertStringContainsString($this->species->genus, $csv);
        $this->assertStringContainsString('A. Botanist', $csv);
        $this->assertStringContainsString('RES-042-2026', $csv);
    }

    public function test_it_offers_both_formats_and_defaults_to_xlsx()
    {
        $this->fieldRecord(['accession_number' => 'MML-0001']);

        $default = $this->actingAs($this->editor())
            ->get($this->url('catalogs.fieldRecords.export'));
        $csv = $this->actingAs($this->editor())
            ->get($this->url('catalogs.fieldRecords.export').'?format=csv');
        $xlsx = $this->actingAs($this->editor())
            ->get($this->url('catalogs.fieldRecords.export').'?format=xlsx');

        foreach ([$default, $csv, $xlsx] as $response) {
            $response->assertOk();
            $this->assertStringContainsString(
                'attachment',
                (string) $response->headers->get('content-disposition')
            );
        }

        // A bare link behaves like the indices download: xlsx.
        $this->assertStringContainsString(
            '.xlsx',
            (string) $default->headers->get('content-disposition')
        );
        $this->assertStringContainsString(
            '.csv',
            (string) $csv->headers->get('content-disposition')
        );
    }

    public function test_the_xlsx_is_a_readable_workbook()
    {
        $fieldRecord = $this->fieldRecord(['accession_number' => 'MML-0001', 'collector' => 'M. Menéndez']);
        Determination::factory()->create([
            'field_record_id' => $fieldRecord->id,
            'catalog_species_id' => $this->species->id,
            'is_current' => true,
        ]);

        $path = tempnam(sys_get_temp_dir(), 'fieldRecords').'.xlsx';
        file_put_contents(
            $path,
            $this->actingAs($this->editor())
                ->get($this->url('catalogs.fieldRecords.export').'?format=xlsx')
                ->streamedContent()
        );

        // Round-trip it: a download that opens is the claim being made.
        $sheet = \Maatwebsite\Excel\Facades\Excel::toArray(new \stdClass, $path, null, Excel::XLSX)[0];
        unlink($path);

        $this->assertSame('catalogNumber', $sheet[0][0]);
        $this->assertSame('MML-0001', $sheet[1][0]);
        $this->assertContains('M. Menéndez', $sheet[1]);
    }

    public function test_the_export_includes_material_nobody_has_named()
    {
        $this->fieldRecord(['collection_number' => '099', 'permit_exemption' => 'market']);

        $csv = $this->actingAs($this->editor())
            ->get($this->url('catalogs.fieldRecords.export').'?format=csv')
            ->streamedContent();

        // A species table cannot show these; the collection list must.
        $this->assertStringContainsString('099', $csv);
        $this->assertStringContainsString('market', $csv);
    }

    public function test_a_viewer_may_export_but_a_stranger_may_not()
    {
        $this->fieldRecord();

        $this->actingAs($this->viewer())
            ->get($this->url('catalogs.fieldRecords.export'))
            ->assertOk();

        $this->actingAs($this->outsider())
            ->get($this->url('catalogs.fieldRecords.export'))
            ->assertRedirect(route('catalogs.index'));
    }

    public function test_the_export_covers_only_this_project()
    {
        $this->fieldRecord(['accession_number' => 'MINE-1']);
        FieldRecord::factory()->create([
            'project_id' => Project::factory()->create()->id,
            'accession_number' => 'THEIRS-1',
        ]);

        $csv = $this->actingAs($this->editor())
            ->get($this->url('catalogs.fieldRecords.export').'?format=csv')
            ->streamedContent();

        $this->assertStringContainsString('MINE-1', $csv);
        $this->assertStringNotContainsString('THEIRS-1', $csv);
    }

    // ------------------------------------------------------ authorization ---

    public function test_a_viewer_can_read_the_list_but_not_add_to_it()
    {
        $this->actingAs($this->viewer())
            ->get($this->url('catalogs.fieldRecords.index'))
            ->assertInertia(fn (Assert $page) => $page->where('canEdit', false));

        $this->actingAs($this->viewer())
            ->post($this->url('catalogs.fieldRecords.store'), ['collector' => 'Someone'])
            ->assertRedirect(route('catalogs.index'));

        $this->assertSame(0, FieldRecord::count());
    }

    public function test_the_project_list_offers_a_route_to_records_with_an_empty_catalog()
    {
        $emptyCatalog = Project::factory()->create();
        $user = $this->userWithCapability($emptyCatalog, 'view_catalog');
        FieldRecord::factory()->create(['project_id' => $emptyCatalog->id]);

        // catalogs.show redirects away when no species exist, so the catalog
        // index is the only page a brand-new project can reach — and that is
        // precisely the project whose fieldRecords come before its taxa.
        $this->actingAs($user)
            ->get(route('catalogs.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Index')
                ->where('projects.0.catalog_species_count', 0)
                ->where('projects.0.field_record_count', 1)
            );
    }

    public function test_a_stranger_cannot_see_the_list_at_all()
    {
        $this->actingAs($this->outsider())
            ->get($this->url('catalogs.fieldRecords.index'))
            ->assertRedirect(route('catalogs.index'));
    }

    public function test_a_specimen_from_another_project_cannot_be_touched()
    {
        $foreign = FieldRecord::factory()->create();

        foreach (['catalogs.fieldRecords.determine', 'catalogs.fieldRecords.deposit'] as $action) {
            $this->actingAs($this->editor())
                ->post($this->url($action, ['fieldRecord' => $foreign->id]), [])
                ->assertRedirect(route('catalogs.index'));
        }

        $this->actingAs($this->editor())
            ->delete($this->url('catalogs.fieldRecords.destroy', ['fieldRecord' => $foreign->id]))
            ->assertRedirect(route('catalogs.index'));

        $this->assertNotNull($foreign->fresh());
    }

    public function test_an_editor_can_correct_the_collection_itself()
    {
        $fieldRecord = $this->fieldRecord(['collector' => 'Typo']);

        $this->actingAs($this->editor())->patch(
            $this->url('catalogs.fieldRecords.update', ['fieldRecord' => $fieldRecord->id]),
            ['collector' => 'Corrected']
        )->assertRedirect();

        $this->assertSame('Corrected', $fieldRecord->fresh()->collector);
    }

    public function test_deleting_a_collection_takes_its_determinations_with_it()
    {
        $fieldRecord = $this->fieldRecord();
        $determination = Determination::factory()->create(['field_record_id' => $fieldRecord->id]);

        $this->actingAs($this->editor())
            ->delete($this->url('catalogs.fieldRecords.destroy', ['fieldRecord' => $fieldRecord->id]))
            ->assertRedirect();

        $this->assertNull($fieldRecord->fresh());
        $this->assertNull($determination->fresh());
    }
}
