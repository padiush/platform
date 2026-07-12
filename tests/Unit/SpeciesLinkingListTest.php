<?php

namespace Tests\Unit;

use App\Models\CatalogSpecies;
use App\Models\InstanceAnswer;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Project;
use App\Services\SpeciesLinkingList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpeciesLinkingListTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    private InterviewForm $form;

    private InterviewSection $section;

    private InterviewItem $item;

    private SpeciesLinkingList $list;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->form = InterviewForm::factory()->create(['project_id' => $this->project->id]);
        $this->section = InterviewSection::factory()->create(['interview_form_id' => $this->form->id]);
        $this->item = InterviewItem::factory()->create([
            'interview_section_id' => $this->section->id,
            'link_to_species' => true,
        ]);
        $this->list = new SpeciesLinkingList;
    }

    private function reported(string $name, ?int $speciesId = null): InstanceAnswer
    {
        $instance = InterviewInstance::factory()->create(['interview_form_id' => $this->form->id]);

        return InstanceAnswer::create([
            'interview_instance_id' => $instance->id,
            'interview_section_id' => $this->section->id,
            'interview_item_id' => $this->item->id,
            'answer' => $name,
            'catalog_species_id' => $speciesId,
        ]);
    }

    private function species(array $attributes = []): CatalogSpecies
    {
        return CatalogSpecies::factory()->create(array_merge(
            ['project_id' => $this->project->id],
            $attributes,
        ));
    }

    private function paginate(array $filters)
    {
        return $this->list->paginate($this->project, $filters, 1);
    }

    private function group($paginator, string $name): ?array
    {
        return collect($paginator->items())->firstWhere('name', $name);
    }

    public function test_groups_recurring_reported_names()
    {
        $this->reported('guarumo');
        $this->reported('guarumo');
        $this->reported('yagrumo');

        $result = $this->paginate(['group' => true]);

        $this->assertSame(2, $result->total());
        $this->assertSame(2, $this->group($result, 'guarumo')['total']);
    }

    public function test_grouping_is_accent_and_case_insensitive()
    {
        $this->reported('árbol');
        $this->reported('Arbol');

        $result = $this->paginate(['group' => true]);

        $this->assertSame(1, $result->total());
        $this->assertSame(2, $result->items()[0]['total']);
    }

    public function test_flat_mode_lists_each_answer_individually()
    {
        $this->reported('guarumo');
        $this->reported('guarumo');
        $this->reported('yagrumo');

        $result = $this->paginate(['group' => false]);

        $this->assertSame(3, $result->total());
    }

    public function test_status_filter_selects_linked_and_unlinked()
    {
        $species = $this->species(['genus' => 'Cecropia', 'name' => 'obtusifolia']);
        $this->reported('guarumo', $species->id);
        $this->reported('yagrumo');

        $this->assertSame('guarumo', $this->paginate(['status' => 'linked'])->items()[0]['name']);
        $this->assertSame('yagrumo', $this->paginate(['status' => 'unlinked'])->items()[0]['name']);
        $this->assertSame(2, $this->paginate(['status' => 'all'])->total());
    }

    public function test_search_filters_by_reported_name()
    {
        $this->reported('guarumo');
        $this->reported('ceiba');

        $result = $this->paginate(['q' => 'guar']);

        $this->assertSame(1, $result->total());
        $this->assertSame('guarumo', $result->items()[0]['name']);
    }

    public function test_group_reports_a_shared_species()
    {
        $species = $this->species(['genus' => 'Cecropia', 'name' => 'obtusifolia']);
        $this->reported('guarumo', $species->id);
        $this->reported('guarumo', $species->id);

        $group = $this->group($this->paginate(['group' => true]), 'guarumo');

        $this->assertFalse($group['mixed']);
        $this->assertSame($species->id, $group['species']['id']);
        $this->assertSame(2, $group['linked_count']);
    }

    public function test_group_flags_mixed_species()
    {
        $a = $this->species(['genus' => 'Cecropia', 'name' => 'obtusifolia']);
        $b = $this->species(['genus' => 'Cecropia', 'name' => 'peltata']);
        $this->reported('guarumo', $a->id);
        $this->reported('guarumo', $b->id);

        $group = $this->group($this->paginate(['group' => true]), 'guarumo');

        $this->assertTrue($group['mixed']);
        $this->assertNull($group['species']);
    }

    public function test_incomplete_groups_sort_before_fully_linked_ones()
    {
        $species = $this->species();
        // "aaa" is fully linked; "zzz" still has an unlinked member.
        $this->reported('aaa', $species->id);
        $this->reported('zzz');

        $result = $this->paginate(['group' => true]);

        // Despite alphabetical order, the group with remaining work comes first.
        $this->assertSame('zzz', $result->items()[0]['name']);
    }
}
