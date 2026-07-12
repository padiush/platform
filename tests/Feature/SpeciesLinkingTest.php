<?php

namespace Tests\Feature;

use App\Models\CatalogSpecies;
use App\Models\InstanceAnswer;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

class SpeciesLinkingTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    private InterviewForm $form;

    private InterviewSection $section;

    private InterviewItem $item;

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
    }

    private function manager()
    {
        return $this->userWithCapability($this->project, 'manage_data');
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

    public function test_empty_project_redirects_to_the_data_index()
    {
        $response = $this->actingAs($this->manager())->get(
            route('data.link', $this->project)
        );

        $response->assertRedirect(route('data.index'));
    }

    public function test_page_renders_grouped_rows_and_totals()
    {
        $species = $this->species();
        $this->reported('guarumo', $species->id);
        $this->reported('guarumo');
        $this->reported('yagrumo');

        $response = $this->actingAs($this->manager())->get(
            route('data.link', $this->project)
        );

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Data/LinkSpecies')
            ->has('rows.data', 2)
            ->where('totals.total', 3)
            ->where('totals.linked', 1)
            ->where('totals.unlinked', 2)
            ->where('filters.group', true)
        );
    }

    public function test_manager_can_link_a_species_to_a_row()
    {
        $species = $this->species();
        $answer = $this->reported('guarumo');

        $response = $this->actingAs($this->manager())->postJson(
            route('data.link.handle', $this->project),
            [
                'interview_instance_id' => $answer->interview_instance_id,
                'interview_section_id' => $answer->interview_section_id,
                'catalog_species_id' => $species->id,
            ]
        );

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('instance_answers', [
            'id' => $answer->id,
            'catalog_species_id' => $species->id,
        ]);
    }

    public function test_manager_can_unlink_a_row()
    {
        $species = $this->species();
        $answer = $this->reported('guarumo', $species->id);

        $response = $this->actingAs($this->manager())->postJson(
            route('data.link.handle', $this->project),
            [
                'interview_instance_id' => $answer->interview_instance_id,
                'interview_section_id' => $answer->interview_section_id,
                'catalog_species_id' => null,
            ]
        );

        $response->assertOk();
        $this->assertDatabaseHas('instance_answers', [
            'id' => $answer->id,
            'catalog_species_id' => null,
        ]);
    }

    public function test_manager_can_bulk_link_a_group()
    {
        $species = $this->species();
        $first = $this->reported('guarumo');
        $second = $this->reported('guarumo');

        $targets = collect([$first, $second])->map(fn ($a) => [
            'interview_instance_id' => $a->interview_instance_id,
            'interview_section_id' => $a->interview_section_id,
            'repeatable_index' => null,
        ])->all();

        $response = $this->actingAs($this->manager())->postJson(
            route('data.link.bulk', $this->project),
            ['catalog_species_id' => $species->id, 'targets' => $targets]
        );

        $response->assertOk()->assertJson(['success' => true, 'count' => 2]);
        $this->assertDatabaseHas('instance_answers', [
            'id' => $first->id, 'catalog_species_id' => $species->id,
        ]);
        $this->assertDatabaseHas('instance_answers', [
            'id' => $second->id, 'catalog_species_id' => $species->id,
        ]);
    }

    public function test_bulk_link_rejects_species_from_another_project()
    {
        $foreign = CatalogSpecies::factory()->create();
        $answer = $this->reported('guarumo');

        $response = $this->actingAs($this->manager())->postJson(
            route('data.link.bulk', $this->project),
            [
                'catalog_species_id' => $foreign->id,
                'targets' => [[
                    'interview_instance_id' => $answer->interview_instance_id,
                    'interview_section_id' => $answer->interview_section_id,
                    'repeatable_index' => null,
                ]],
            ]
        );

        $response->assertForbidden();
        $this->assertDatabaseHas('instance_answers', [
            'id' => $answer->id, 'catalog_species_id' => null,
        ]);
    }

    public function test_species_search_returns_matches_as_json()
    {
        $this->species(['genus' => 'Cecropia', 'name' => 'obtusifolia']);
        $this->species(['genus' => 'Inga', 'name' => 'edulis']);

        $response = $this->actingAs($this->manager())->getJson(
            route('data.link.species-search', ['project' => $this->project, 'q' => 'cecropia'])
        );

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.genus', 'Cecropia');
    }

    public function test_outsider_cannot_search_species()
    {
        $response = $this->actingAs($this->outsider())->getJson(
            route('data.link.species-search', ['project' => $this->project, 'q' => 'x'])
        );

        $response->assertForbidden();
    }
}
