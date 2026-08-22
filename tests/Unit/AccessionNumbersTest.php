<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\Specimen;
use App\Services\AccessionNumbers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A project issues its own accession numbers, because a community herbarium has
 * no curator to issue them. See docs/decisions/0008-specimens-and-determinations.md.
 */
class AccessionNumbersTest extends TestCase
{
    use RefreshDatabase;

    private AccessionNumbers $accessions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accessions = new AccessionNumbers;
    }

    private function project(array $attributes = []): Project
    {
        return Project::factory()->create($attributes);
    }

    public function test_it_starts_at_one_and_pads_to_four_digits()
    {
        $project = $this->project(['accession_prefix' => 'MML']);

        $this->assertSame('MML-0001', $this->accessions->mint($project));
    }

    public function test_it_omits_the_separator_when_no_prefix_is_set()
    {
        $project = $this->project(['accession_prefix' => null]);

        $this->assertSame('0001', $this->accessions->mint($project));
    }

    public function test_a_blank_prefix_is_treated_as_no_prefix()
    {
        $project = $this->project(['accession_prefix' => '   ']);

        $this->assertSame('0001', $this->accessions->mint($project));
    }

    public function test_the_sequence_advances()
    {
        $project = $this->project(['accession_prefix' => 'MML']);

        $minted = [
            $this->accessions->mint($project),
            $this->accessions->mint($project),
            $this->accessions->mint($project),
        ];

        $this->assertSame(['MML-0001', 'MML-0002', 'MML-0003'], $minted);
    }

    public function test_each_project_keeps_its_own_sequence()
    {
        $one = $this->project(['accession_prefix' => 'AAA']);
        $two = $this->project(['accession_prefix' => 'BBB']);

        $this->accessions->mint($one);
        $this->accessions->mint($one);

        $this->assertSame('BBB-0001', $this->accessions->mint($two));
        $this->assertSame('AAA-0003', $this->accessions->mint($one));
    }

    public function test_it_steps_over_a_number_already_entered_by_hand()
    {
        $project = $this->project(['accession_prefix' => 'MML']);

        // A study that already numbers its own specimens enters them directly;
        // the sequence must not then hand out a duplicate.
        Specimen::factory()->create([
            'project_id' => $project->id,
            'accession_number' => 'MML-0001',
        ]);

        $this->assertSame('MML-0002', $this->accessions->mint($project));
    }

    public function test_the_counter_survives_a_reload()
    {
        $project = $this->project(['accession_prefix' => 'MML']);

        $this->accessions->mint($project);

        $this->assertSame(2, $project->fresh()->next_accession_number);
    }

    public function test_peek_does_not_consume_a_number()
    {
        $project = $this->project(['accession_prefix' => 'MML']);

        $this->assertSame('MML-0001', $this->accessions->peek($project));
        $this->assertSame('MML-0001', $this->accessions->peek($project));
        $this->assertSame('MML-0001', $this->accessions->mint($project));
    }

    public function test_the_sequence_counter_is_not_mass_assignable()
    {
        $project = new Project([
            'name' => 'A project',
            'next_accession_number' => 500,
        ]);

        // Left at the default rather than the value the caller tried to set:
        // only AccessionNumbers may advance the sequence.
        $this->assertSame(1, $project->next_accession_number);
    }
}
