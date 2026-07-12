<?php

namespace Tests\Unit;

use App\Models\CatalogSpecies;
use App\Models\InstanceAnswer;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Project;
use App\Services\EthnobiologyIndices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Verifies the indices against the canonical worked example in
 * docs/analysis/ethnobotany-indices.md — the spec is the oracle.
 */
class EthnobiologyIndicesTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    private InterviewForm $form;

    private InterviewSection $section;

    private InterviewItem $taxonItem;

    private InterviewItem $useItem;

    private EthnobiologyIndices $indices;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->form = InterviewForm::factory()->create(['project_id' => $this->project->id]);
        $this->section = InterviewSection::factory()->create([
            'interview_form_id' => $this->form->id,
            'repeatable' => true,
        ]);
        $this->taxonItem = InterviewItem::factory()->create([
            'interview_section_id' => $this->section->id,
            'link_to_species' => true,
        ]);
        $this->useItem = InterviewItem::factory()->create([
            'interview_section_id' => $this->section->id,
            'is_use_category' => true,
        ]);
        $this->indices = new EthnobiologyIndices;
    }

    public function test_computes_all_five_indices_for_the_worked_example()
    {
        $a = $this->species('edulis');       // Inga edulis
        $b = $this->species('peltata');      // Cecropia peltata

        // Cells from the spec's worked example (N = 4 informants).
        $i1 = $this->interview();
        $this->useReport($i1, 0, $a, 'food');
        $this->useReport($i1, 1, $a, 'medicine');
        $this->useReport($i1, 2, $b, 'food');

        $i2 = $this->interview();
        $this->useReport($i2, 0, $a, 'food');
        $this->useReport($i2, 1, $b, 'medicine');

        $i3 = $this->interview();
        $this->useReport($i3, 0, $a, 'food');
        $this->useReport($i3, 1, $a, 'medicine');
        $this->useReport($i3, 2, $b, 'medicine');

        $i4 = $this->interview();
        $this->useReport($i4, 0, $b, 'food');

        $result = $this->indices->compute($this->project);

        $this->assertSame(4, $result['informants']);
        $this->assertSame(0, $result['unlinked_citations']);

        $species = collect($result['species']);
        $speciesA = $species->firstWhere('species.id', $a->id);
        $speciesB = $species->firstWhere('species.id', $b->id);

        // FC / RFC
        $this->assertSame(3, $speciesA['fc']);
        $this->assertSame(4, $speciesB['fc']);
        $this->assertEqualsWithDelta(0.75, $speciesA['rfc'], 1e-9);
        $this->assertEqualsWithDelta(1.0, $speciesB['rfc'], 1e-9);

        // UV and CI (equal under one-report-per-cell coding)
        $this->assertEqualsWithDelta(1.25, $speciesA['uv'], 1e-9);
        $this->assertEqualsWithDelta(1.0, $speciesB['uv'], 1e-9);
        $this->assertEqualsWithDelta(1.25, $speciesA['ci'], 1e-9);
        $this->assertEqualsWithDelta(1.0, $speciesB['ci'], 1e-9);

        // Fidelity Level
        $this->assertEqualsWithDelta(100.0, $this->fl($speciesA, 'food'), 1e-9);
        $this->assertEqualsWithDelta(50.0, $this->fl($speciesB, 'medicine'), 1e-9);

        // Informant Consensus Factor per use-category
        $categories = collect($result['use_categories']);
        $food = $categories->firstWhere('use_category', 'food');
        $medicine = $categories->firstWhere('use_category', 'medicine');
        $this->assertSame(5, $food['n_ur']);
        $this->assertSame(2, $food['n_taxa']);
        $this->assertEqualsWithDelta(0.75, $food['icf'], 1e-9);
        $this->assertSame(4, $medicine['n_ur']);
        $this->assertEqualsWithDelta(2 / 3, $medicine['icf'], 1e-9);
    }

    public function test_no_informants_yields_an_empty_result()
    {
        $result = $this->indices->compute($this->project);

        $this->assertSame(0, $result['informants']);
        $this->assertSame([], $result['species']);
        $this->assertSame([], $result['use_categories']);
        $this->assertSame(0, $result['unlinked_citations']);
    }

    public function test_unlinked_citations_are_counted_and_excluded()
    {
        $a = $this->species('edulis');
        $interview = $this->interview();

        // A linked, categorized use report.
        $this->useReport($interview, 0, $a, 'food');

        // A folk name recorded but never linked to a species.
        InstanceAnswer::create([
            'interview_instance_id' => $interview->id,
            'interview_section_id' => $this->section->id,
            'interview_item_id' => $this->taxonItem->id,
            'repeatable_index' => 1,
            'answer' => 'unidentified vine',
            'catalog_species_id' => null,
        ]);

        $result = $this->indices->compute($this->project);

        $this->assertSame(1, $result['unlinked_citations']);
        $this->assertCount(1, $result['species']);
        $this->assertSame($a->id, $result['species'][0]['species']['id']);
    }

    public function test_icf_is_undefined_for_a_single_use_report()
    {
        $a = $this->species('edulis');
        $interview = $this->interview();
        $this->useReport($interview, 0, $a, 'food');

        $result = $this->indices->compute($this->project);

        $food = collect($result['use_categories'])->firstWhere('use_category', 'food');
        $this->assertSame(1, $food['n_ur']);
        $this->assertNull($food['icf']);
    }

    public function test_instances_of_non_use_forms_do_not_dilute_the_denominator()
    {
        $a = $this->species('edulis');

        // The main instrument (setUp) carries a species field: two informants,
        // both citing A for food.
        $this->useReport($this->interview(), 0, $a, 'food');
        $this->useReport($this->interview(), 0, $a, 'food');

        // A second, unrelated instrument in the same project with no species/use
        // fields — e.g. a demographics survey with three respondents.
        $other = InterviewForm::factory()->create(['project_id' => $this->project->id]);
        $otherSection = InterviewSection::factory()->create([
            'interview_form_id' => $other->id,
        ]);
        $otherItem = InterviewItem::factory()->create([
            'interview_section_id' => $otherSection->id,
            'type' => 'text',
        ]);
        foreach (range(1, 3) as $ignored) {
            $instance = InterviewInstance::factory()->create([
                'interview_form_id' => $other->id,
            ]);
            InstanceAnswer::create([
                'interview_instance_id' => $instance->id,
                'interview_section_id' => $otherSection->id,
                'interview_item_id' => $otherItem->id,
                'answer' => 'a demographic value',
            ]);
        }

        $result = $this->indices->compute($this->project);

        // N counts only the two species-instrument interviews, not the three
        // demographic ones — otherwise RFC(A) would be 2/5 instead of 2/2.
        $this->assertSame(2, $result['informants']);
        $speciesA = collect($result['species'])->firstWhere('species.id', $a->id);
        $this->assertSame(2, $speciesA['fc']);
        $this->assertEqualsWithDelta(1.0, $speciesA['rfc'], 1e-9);
    }

    private function species(string $name): CatalogSpecies
    {
        return CatalogSpecies::factory()->create([
            'project_id' => $this->project->id,
            'family' => 'Fabaceae',
            'genus' => 'Inga',
            'name' => $name,
            'authority' => 'Mart.',
        ]);
    }

    private function interview(): InterviewInstance
    {
        return InterviewInstance::factory()->create([
            'interview_form_id' => $this->form->id,
        ]);
    }

    /** One repeatable set: the species linked plus its use-category. */
    private function useReport(
        InterviewInstance $interview,
        int $index,
        CatalogSpecies $species,
        string $use
    ): void {
        InstanceAnswer::create([
            'interview_instance_id' => $interview->id,
            'interview_section_id' => $this->section->id,
            'interview_item_id' => $this->taxonItem->id,
            'repeatable_index' => $index,
            'answer' => 'folk name for '.$species->name,
            'catalog_species_id' => $species->id,
        ]);

        InstanceAnswer::create([
            'interview_instance_id' => $interview->id,
            'interview_section_id' => $this->section->id,
            'interview_item_id' => $this->useItem->id,
            'repeatable_index' => $index,
            'answer' => $use,
        ]);
    }

    private function fl(array $species, string $useCategory): float
    {
        return (new Collection($species['fidelity']))
            ->firstWhere('use_category', $useCategory)['value'];
    }
}
