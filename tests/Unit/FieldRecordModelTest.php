<?php

namespace Tests\Unit;

use App\Models\CatalogSpecies;
use App\Models\Determination;
use App\Models\FieldRecord;
use App\Models\InstanceAnswer;
use App\Models\Project;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A fieldRecord is not a taxon, and its identification is a history rather than a
 * fact. See docs/decisions/0008-specimens-and-determinations.md.
 */
class FieldRecordModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_taxon_has_many_specimens()
    {
        $project = Project::factory()->create();
        $species = CatalogSpecies::factory()->create(['project_id' => $project->id]);

        foreach (range(1, 3) as $ignored) {
            Determination::factory()->create([
                'field_record_id' => FieldRecord::factory()->create(['project_id' => $project->id])->id,
                'catalog_species_id' => $species->id,
            ]);
        }

        $this->assertCount(3, $species->fieldRecords);
    }

    public function test_a_specimen_keeps_superseded_determinations()
    {
        $fieldRecord = FieldRecord::factory()->create();
        $was = CatalogSpecies::factory()->create();
        $now = CatalogSpecies::factory()->create();

        Determination::factory()->superseded()->create([
            'field_record_id' => $fieldRecord->id,
            'catalog_species_id' => $was->id,
            'determiner' => 'First opinion',
        ]);
        Determination::factory()->create([
            'field_record_id' => $fieldRecord->id,
            'catalog_species_id' => $now->id,
            'determiner' => 'Second opinion',
        ]);

        $this->assertCount(2, $fieldRecord->determinations);
        $this->assertSame($now->id, $fieldRecord->currentDetermination->catalog_species_id);
        $this->assertSame('Second opinion', $fieldRecord->currentDetermination->determiner);
    }

    public function test_a_determination_may_name_no_taxon_at_all()
    {
        $fieldRecord = FieldRecord::factory()->create();

        $indet = Determination::factory()->indeterminate()->create([
            'field_record_id' => $fieldRecord->id,
            'determiner' => 'Nobody yet',
        ]);

        $this->assertTrue($indet->isIndeterminate());
        $this->assertNull($indet->species);
        // The useful part of "not identified" survives: who looked, and when.
        $this->assertSame('Nobody yet', $fieldRecord->currentDetermination->determiner);
    }

    public function test_deleting_a_taxon_leaves_the_specimen_and_its_history()
    {
        $species = CatalogSpecies::factory()->create();
        $fieldRecord = FieldRecord::factory()->create();
        $determination = Determination::factory()->create([
            'field_record_id' => $fieldRecord->id,
            'catalog_species_id' => $species->id,
        ]);

        $species->delete();

        $this->assertNotNull($fieldRecord->fresh());
        $this->assertNotNull($determination->fresh());
        $this->assertNull($determination->fresh()->catalog_species_id);
    }

    public function test_deleting_the_answer_it_came_from_leaves_the_specimen()
    {
        $answer = InstanceAnswer::factory()->create();
        $fieldRecord = FieldRecord::factory()->create(['instance_answer_id' => $answer->id]);

        $answer->delete();

        $this->assertNotNull($fieldRecord->fresh());
        $this->assertNull($fieldRecord->fresh()->instance_answer_id);
    }

    public function test_deleting_a_specimen_takes_its_determinations_with_it()
    {
        $fieldRecord = FieldRecord::factory()->create();
        $determination = Determination::factory()->create(['field_record_id' => $fieldRecord->id]);

        $fieldRecord->delete();

        $this->assertNull($determination->fresh());
    }

    public function test_a_specimen_is_unvouchered_until_it_has_an_accession_number()
    {
        $this->assertFalse(FieldRecord::factory()->create()->isVouchered());
        $this->assertTrue(FieldRecord::factory()->vouchered('MML-0007')->create()->isVouchered());
    }

    public function test_an_accession_number_is_unique_within_a_project()
    {
        $project = Project::factory()->create();

        FieldRecord::factory()->create([
            'project_id' => $project->id,
            'accession_number' => 'MML-0001',
        ]);

        $this->expectException(QueryException::class);

        FieldRecord::factory()->create([
            'project_id' => $project->id,
            'accession_number' => 'MML-0001',
        ]);
    }

    public function test_two_projects_may_use_the_same_accession_number()
    {
        foreach (range(1, 2) as $ignored) {
            FieldRecord::factory()->create([
                'project_id' => Project::factory()->create()->id,
                'accession_number' => 'MML-0001',
            ]);
        }

        $this->assertSame(2, FieldRecord::where('accession_number', 'MML-0001')->count());
    }

    public function test_many_specimens_may_be_unvouchered_in_one_project()
    {
        $project = Project::factory()->create();

        // The unique index must not collapse nulls — being unvouchered is the
        // ordinary case, not a conflict.
        FieldRecord::factory()->count(3)->create(['project_id' => $project->id]);

        $this->assertSame(3, $project->fieldRecords()->count());
    }

    public function test_the_project_is_not_mass_assignable()
    {
        $fieldRecord = new FieldRecord(['project_id' => 999, 'collector' => 'Someone']);

        $this->assertNull($fieldRecord->project_id);
        $this->assertSame('Someone', $fieldRecord->collector);
    }
}
