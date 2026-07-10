<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

use App\Models\InterviewForm;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Project;

class DesignerAuthorizationTest extends TestCase
{
    use RefreshDatabase, InteractsWithProjects;

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

    public function test_outsider_cannot_fetch_sections()
    {
        $response = $this->actingAs($this->outsider())->getJson(
            route('designer.form.sections', [
                'project' => $this->project,
                'form' => $this->form,
            ])
        );

        $response->assertForbidden();
        $response->assertJson(['message' => 'designer.no_access']);
    }

    public function test_outsider_cannot_create_a_section()
    {
        $response = $this->actingAs($this->outsider())->postJson(
            route('designer.form.sections.create', [
                'project' => $this->project,
                'form' => $this->form,
            ]),
            ['name' => 'Intrusion']
        );

        $response->assertForbidden();
        $this->assertDatabaseMissing('interview_sections', [
            'name' => 'Intrusion',
        ]);
    }

    public function test_member_without_manage_forms_cannot_create_a_section()
    {
        $user = $this->userWithCapability(
            $this->project,
            'manage_forms',
            false
        );

        $response = $this->actingAs($user)->postJson(
            route('designer.form.sections.create', [
                'project' => $this->project,
                'form' => $this->form,
            ]),
            ['name' => 'Intrusion']
        );

        $response->assertForbidden();
        $this->assertDatabaseMissing('interview_sections', [
            'name' => 'Intrusion',
        ]);
    }

    public function test_member_with_manage_forms_can_create_a_section()
    {
        $user = $this->userWithCapability($this->project, 'manage_forms');

        $response = $this->actingAs($user)->postJson(
            route('designer.form.sections.create', [
                'project' => $this->project,
                'form' => $this->form,
            ]),
            ['name' => 'Legitimate section']
        );

        $response->assertOk();
        $this->assertDatabaseHas('interview_sections', [
            'interview_form_id' => $this->form->id,
            'name' => 'Legitimate section',
        ]);
    }

    public function test_outsider_cannot_rename_a_section()
    {
        $originalName = $this->section->name;

        $response = $this->actingAs($this->outsider())->putJson(
            route('designer.form.sections.rename', [
                'project' => $this->project,
                'form' => $this->form,
                'section' => $this->section,
            ]),
            ['name' => 'Hijacked']
        );

        $response->assertForbidden();
        $this->assertDatabaseHas('interview_sections', [
            'id' => $this->section->id,
            'name' => $originalName,
        ]);
    }

    public function test_outsider_cannot_delete_a_section()
    {
        $response = $this->actingAs($this->outsider())->deleteJson(
            route('designer.form.sections.delete', [
                'project' => $this->project,
                'form' => $this->form,
                'section' => $this->section,
            ])
        );

        $response->assertForbidden();
        $this->assertDatabaseHas('interview_sections', [
            'id' => $this->section->id,
        ]);
        $this->assertDatabaseHas('interview_items', [
            'id' => $this->item->id,
        ]);
    }

    public function test_outsider_cannot_delete_an_item()
    {
        $response = $this->actingAs($this->outsider())->deleteJson(
            route('designer.form.section.items.delete', [
                'project' => $this->project,
                'form' => $this->form,
                'section' => $this->section,
                'item' => $this->item,
            ])
        );

        $response->assertForbidden();
        $this->assertDatabaseHas('interview_items', ['id' => $this->item->id]);
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

    public function test_section_of_another_form_cannot_be_renamed_through_it()
    {
        // Regression test for the ownership check comparing the section's
        // form id against the *project* id: with sqlite's sequential ids the
        // section's form id equals the project id here, which the old
        // comparison wrongly accepted.
        $user = $this->userWithCapability($this->project, 'manage_forms');

        $otherForm = InterviewForm::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $originalName = $this->section->name;

        $response = $this->actingAs($user)->putJson(
            route('designer.form.sections.rename', [
                'project' => $this->project,
                'form' => $otherForm,
                'section' => $this->section,
            ]),
            ['name' => 'Cross-form rename']
        );

        $response->assertForbidden();
        $response->assertJson(['message' => 'designer.section_not_found']);
        $this->assertDatabaseHas('interview_sections', [
            'id' => $this->section->id,
            'name' => $originalName,
        ]);
    }

    public function test_form_of_another_project_cannot_be_accessed()
    {
        $otherProject = Project::factory()->create();
        $user = $this->userWithCapability($otherProject, 'manage_forms');

        $response = $this->actingAs($user)->getJson(
            route('designer.form.sections', [
                'project' => $otherProject,
                'form' => $this->form,
            ])
        );

        $response->assertForbidden();
        $response->assertJson(['message' => 'designer.form_not_found']);
    }
}
