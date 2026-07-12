<?php

namespace Tests\Feature;

use App\Models\InterviewForm;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

class DesignerAuthorizationTest extends TestCase
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
        $this->form = InterviewForm::factory()->create([
            'project_id' => $this->project->id,
        ]);
        $this->section = InterviewSection::factory()->create([
            'interview_form_id' => $this->form->id,
        ]);
        $this->item = InterviewItem::factory()->create([
            'interview_section_id' => $this->section->id,
        ]);
    }

    public function test_member_with_manage_forms_can_open_the_wizard()
    {
        $user = $this->userWithCapability($this->project, 'manage_forms');

        $response = $this->actingAs($user)->get(
            route('designer.form.wizard', [
                'project' => $this->project,
                'form' => $this->form,
            ])
        );

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Designer/Wizard')
                ->has('structure', 1)
        );
    }

    public function test_outsider_is_redirected_away_from_the_wizard_page()
    {
        $response = $this->actingAs($this->outsider())->get(
            route('designer.form.wizard', [
                'project' => $this->project,
                'form' => $this->form,
            ])
        );

        $response->assertRedirect(route('designer.index'));
        $response->assertSessionHas('message', 'designer.no_access');
        $response->assertSessionHas('message_type', 'error');
    }

    public function test_member_without_manage_forms_is_redirected_away_from_the_wizard_page()
    {
        $user = $this->userWithCapability(
            $this->project,
            'manage_forms',
            false
        );

        $response = $this->actingAs($user)->get(
            route('designer.form.wizard', [
                'project' => $this->project,
                'form' => $this->form,
            ])
        );

        $response->assertRedirect(route('designer.index'));
        $response->assertSessionHas('message', 'designer.no_access');
    }

    public function test_outsider_is_redirected_away_from_the_preview_page()
    {
        $response = $this->actingAs($this->outsider())->get(
            route('designer.form.preview', [
                'project' => $this->project,
                'form' => $this->form,
            ])
        );

        $response->assertRedirect(route('designer.index'));
        $response->assertSessionHas('message', 'designer.no_access');
    }

    public function test_form_of_another_project_cannot_be_opened_in_the_wizard()
    {
        $otherProject = Project::factory()->create();
        $user = $this->userWithCapability($otherProject, 'manage_forms');

        $response = $this->actingAs($user)->get(
            route('designer.form.wizard', [
                'project' => $otherProject,
                'form' => $this->form,
            ])
        );

        $response->assertRedirect(route('designer.index'));
        $response->assertSessionHas('message', 'designer.form_not_found');
    }

    public function test_form_of_another_project_cannot_be_saved_through_the_structure_endpoint()
    {
        $otherProject = Project::factory()->create();
        $user = $this->userWithCapability($otherProject, 'manage_forms');

        $response = $this->actingAs($user)->putJson(
            route('designer.form.structure.update', [
                'project' => $otherProject,
                'form' => $this->form,
            ]),
            ['sections' => []]
        );

        $response->assertForbidden();
        $response->assertJsonPath('message', 'designer.form_not_found');
        $this->assertDatabaseHas('interview_sections', [
            'id' => $this->section->id,
        ]);
    }
}
