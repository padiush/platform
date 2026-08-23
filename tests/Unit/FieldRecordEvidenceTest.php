<?php

namespace Tests\Unit;

use App\Models\CatalogSpecies;
use App\Models\CollectingPermit;
use App\Models\Determination;
use App\Models\FieldRecord;
use App\Models\Project;
use App\Services\FieldRecordEvidence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The evidence behind a species table, and the coverage figures that say how
 * much of it is on record.
 */
class FieldRecordEvidenceTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    private FieldRecordEvidence $evidence;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->evidence = new FieldRecordEvidence;
    }

    private function taxon(): CatalogSpecies
    {
        return CatalogSpecies::factory()->create(['project_id' => $this->project->id]);
    }

    private function fieldRecord(array $attributes = []): FieldRecord
    {
        return FieldRecord::factory()->create(
            array_merge(['project_id' => $this->project->id], $attributes)
        );
    }

    private function determine(FieldRecord $fieldRecord, ?CatalogSpecies $taxon): void
    {
        Determination::factory()->create([
            'field_record_id' => $fieldRecord->id,
            'catalog_species_id' => $taxon?->id,
            'is_current' => true,
        ]);
    }

    public function test_it_carries_the_voucher_for_a_taxon()
    {
        $taxon = $this->taxon();
        $this->determine($this->fieldRecord(['accession_number' => 'MML-0001']), $taxon);

        $evidence = $this->evidence->forProject($this->project);

        $this->assertSame('MML-0001', $evidence['by_taxon'][$taxon->id]['vouchers']);
    }

    public function test_several_records_backing_one_taxon_read_as_one_cell()
    {
        $taxon = $this->taxon();
        $this->determine($this->fieldRecord(['accession_number' => 'MML-0001']), $taxon);
        $this->determine($this->fieldRecord(['accession_number' => 'MML-0002']), $taxon);

        $evidence = $this->evidence->forProject($this->project);

        $this->assertSame('MML-0001; MML-0002', $evidence['by_taxon'][$taxon->id]['vouchers']);
        $this->assertSame(2, $evidence['by_taxon'][$taxon->id]['fieldRecords']);
    }

    public function test_one_permit_covering_several_records_is_not_repeated()
    {
        $taxon = $this->taxon();
        $permit = CollectingPermit::factory()->create([
            'project_id' => $this->project->id,
            'authority' => 'MARN',
            'reference' => 'RES-042-2026',
        ]);

        foreach (range(1, 3) as $ignored) {
            $this->determine($this->fieldRecord(['collecting_permit_id' => $permit->id]), $taxon);
        }

        $this->assertSame(
            'MARN · RES-042-2026',
            $this->evidence->forProject($this->project)['by_taxon'][$taxon->id]['permits']
        );
    }

    public function test_an_unidentified_specimen_belongs_to_no_taxon()
    {
        $this->determine($this->fieldRecord(['accession_number' => 'MML-0001']), null);
        $this->fieldRecord(['accession_number' => 'MML-0002']);

        $evidence = $this->evidence->forProject($this->project);

        // Neither indet. material nor unexamined material can appear in a
        // species table; they are counted in coverage instead.
        $this->assertSame([], $evidence['by_taxon']);
        $this->assertSame(2, $evidence['coverage']['records_vouchered']);
        $this->assertSame(0, $evidence['coverage']['taxa_vouchered']);
    }

    public function test_taxon_coverage_counts_taxa_not_specimens()
    {
        $backed = $this->taxon();
        $this->taxon();
        $this->taxon();

        // Two fieldRecords, one taxon: coverage is one of three, not two of three.
        $this->determine($this->fieldRecord(['accession_number' => 'A-1']), $backed);
        $this->determine($this->fieldRecord(['accession_number' => 'A-2']), $backed);

        $coverage = $this->evidence->forProject($this->project)['coverage'];

        $this->assertSame(3, $coverage['taxa_total']);
        $this->assertSame(1, $coverage['taxa_vouchered']);
    }

    public function test_the_three_permit_states_are_counted_apart()
    {
        $permit = CollectingPermit::factory()->create(['project_id' => $this->project->id]);

        $this->fieldRecord(['collecting_permit_id' => $permit->id]);
        $this->fieldRecord(['permit_exemption' => 'market']);
        $this->fieldRecord(['permit_exemption' => 'cultivated']);
        $this->fieldRecord();

        $coverage = $this->evidence->forProject($this->project)['coverage'];

        // An exemption is an answer; a blank is not.
        $this->assertSame(1, $coverage['records_under_permit']);
        $this->assertSame(2, $coverage['records_permit_exempt']);
        $this->assertSame(1, $coverage['records_permit_unrecorded']);
    }

    public function test_another_project_does_not_leak_in()
    {
        $other = Project::factory()->create();
        FieldRecord::factory()->create([
            'project_id' => $other->id,
            'accession_number' => 'OTHER-1',
        ]);

        $coverage = $this->evidence->forProject($this->project)['coverage'];

        $this->assertSame(0, $coverage['records_total']);
    }

    public function test_a_project_with_nothing_collected_reports_zeroes()
    {
        $this->taxon();

        $coverage = $this->evidence->forProject($this->project)['coverage'];

        $this->assertSame(1, $coverage['taxa_total']);
        $this->assertSame(0, $coverage['taxa_vouchered']);
        $this->assertSame(0, $coverage['records_total']);
    }
}
