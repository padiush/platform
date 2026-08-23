<?php

namespace Tests\Unit;

use App\Models\CollectingPermit;
use App\Models\FieldRecord;
use App\Models\Project;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * One permit covers many collections, and an absent permit means two different
 * things. See docs/decisions/0009-collecting-permits.md.
 */
class CollectingPermitTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
    }

    /**
     * Frozen time must be released even when a test fails part-way. Resetting
     * at the end of the test body means a failure leaks 2026-06-01 into every
     * later test in the process, and the resulting failures point nowhere near
     * the cause.
     */
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function permit(array $attributes = []): CollectingPermit
    {
        return CollectingPermit::factory()->create(
            array_merge(['project_id' => $this->project->id], $attributes)
        );
    }

    private function fieldRecord(array $attributes = []): FieldRecord
    {
        return FieldRecord::factory()->create(
            array_merge(['project_id' => $this->project->id], $attributes)
        );
    }

    public function test_one_permit_covers_many_collections()
    {
        $permit = $this->permit();

        foreach (range(1, 3) as $ignored) {
            $this->fieldRecord(['collecting_permit_id' => $permit->id]);
        }

        // The question a herbarium or an authority actually asks.
        $this->assertCount(3, $permit->fieldRecords);
    }

    public function test_deleting_a_permit_leaves_the_collections_standing()
    {
        $permit = $this->permit();
        $fieldRecord = $this->fieldRecord(['collecting_permit_id' => $permit->id]);

        $permit->delete();

        // The physical record outlives the paperwork, as it outlives a taxon.
        $this->assertNotNull($fieldRecord->fresh());
        $this->assertNull($fieldRecord->fresh()->collecting_permit_id);
    }

    public function test_a_reference_cannot_repeat_within_a_project()
    {
        $this->permit(['reference' => 'RES-042-2026']);

        $this->expectException(QueryException::class);

        $this->permit(['reference' => 'RES-042-2026']);
    }

    public function test_two_projects_may_record_the_same_reference()
    {
        $this->permit(['reference' => 'RES-042-2026']);

        CollectingPermit::factory()->create([
            'project_id' => Project::factory()->create()->id,
            'reference' => 'RES-042-2026',
        ]);

        $this->assertSame(2, CollectingPermit::where('reference', 'RES-042-2026')->count());
    }

    public function test_a_permit_and_an_exemption_are_both_complete_answers()
    {
        $underPermit = $this->fieldRecord([
            'collecting_permit_id' => $this->permit()->id,
        ]);
        $exempt = $this->fieldRecord(['permit_exemption' => 'market']);
        $unrecorded = $this->fieldRecord();

        $this->assertTrue($underPermit->permitIsAccountedFor());
        $this->assertTrue($exempt->permitIsAccountedFor());
        // Only this one is a gap; the other two are answered, differently.
        $this->assertFalse($unrecorded->permitIsAccountedFor());
    }

    public function test_exemption_is_distinguishable_from_being_under_a_permit()
    {
        $this->assertTrue($this->fieldRecord(['permit_exemption' => 'cultivated'])->isPermitExempt());
        $this->assertFalse($this->fieldRecord()->isPermitExempt());
    }

    public function test_expiry_reads_the_recorded_date_and_nothing_more()
    {
        Carbon::setTestNow('2026-06-01');

        $this->assertFalse($this->permit()->hasExpired());

        $lapsed = CollectingPermit::factory()->expired()->create([
            'project_id' => $this->project->id,
        ]);
        $this->assertTrue($lapsed->hasExpired());

        // No expiry recorded is not the same as still valid, so it is neither
        // true nor false.
        $this->assertNull($this->permit(['expires_on' => null])->hasExpired());
    }

    public function test_it_reads_as_authority_and_reference()
    {
        $permit = $this->permit(['authority' => 'MARN', 'reference' => 'RES-042-2026']);

        $this->assertSame('MARN · RES-042-2026', $permit->label());
    }

    public function test_the_project_is_not_mass_assignable()
    {
        $permit = new CollectingPermit(['project_id' => 999, 'authority' => 'MARN']);

        $this->assertNull($permit->project_id);
        $this->assertSame('MARN', $permit->authority);
    }
}
