<?php

namespace Tests\Feature;

use App\Models\CatalogSpecies;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

class DataAuthorizationTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    private InterviewForm $form;

    private InterviewSection $section;

    private InterviewItem $item;

    private InterviewInstance $instance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->form = InterviewForm::factory()->create([
            'project_id' => $this->project->id,
        ]);
        $this->section = InterviewSection::factory()->create([
            'interview_form_id' => $this->form->id,
        ]);
        $this->item = InterviewItem::factory()->create([
            'interview_section_id' => $this->section->id,
            'link_to_species' => true,
        ]);
        $this->instance = InterviewInstance::factory()->create([
            'interview_form_id' => $this->form->id,
        ]);
    }

    public function test_outsider_cannot_open_the_species_linking_page()
    {
        $response = $this->actingAs($this->outsider())->get(
            route('data.link', ['project' => $this->project])
        );

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas(
            'error',
            'No tienes acceso a este proyecto.'
        );
    }

    public function test_outsider_cannot_link_species()
    {
        $species = CatalogSpecies::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->outsider())->postJson(
            route('data.link.handle', ['project' => $this->project]),
            [
                'interview_instance_id' => $this->instance->id,
                'catalog_species_id' => $species->id,
                'interview_section_id' => $this->section->id,
            ]
        );

        $response->assertForbidden();
    }

    public function test_outsider_cannot_open_the_custom_export_page()
    {
        $response = $this->actingAs($this->outsider())->get(
            route('data.custom', ['project' => $this->project])
        );

        $response->assertRedirect(route('projects.index'));
    }

    public function test_outsider_cannot_run_the_custom_export()
    {
        $response = $this->actingAs($this->outsider())->post(
            route('data.custom', ['project' => $this->project]),
            [
                'form_id' => $this->form->id,
                'selected_fields' => json_encode([$this->item->id]),
            ]
        );

        $response->assertRedirect(route('projects.index'));
    }

    public function test_outsider_cannot_run_the_ethnobotanyr_export()
    {
        $response = $this->actingAs($this->outsider())->post(
            route('data.ethnobotanyR', ['project' => $this->project]),
            [
                'form_id' => $this->form->id,
                'field_id' => $this->item->id,
            ]
        );

        $response->assertForbidden();
    }

    public function test_member_can_open_the_custom_export_page()
    {
        $user = $this->userWithCapability($this->project, 'generate_reports');

        $response = $this->actingAs($user)->get(
            route('data.custom', ['project' => $this->project])
        );

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page->component('Data/Custom')
        );
    }
}
