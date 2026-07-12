<?php

namespace Tests\Feature;

use App\Models\InterviewForm;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

class DesignerFormTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    public function test_create_link_redirects_to_the_list_modal()
    {
        $project = Project::factory()->create();
        $user = $this->userWithCapability($project, 'manage_forms');

        $response = $this->actingAs($user)->get(
            route('designer.create', $project)
        );

        $response->assertRedirect(
            route('designer.index', ['create' => $project->id])
        );
    }

    public function test_edit_link_redirects_to_the_list_modal()
    {
        $project = Project::factory()->create();
        $user = $this->userWithCapability($project, 'manage_forms');
        $form = InterviewForm::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($user)->get(
            route('designer.form.edit', ['project' => $project, 'form' => $form])
        );

        $response->assertRedirect(
            route('designer.index', ['edit' => $form->id])
        );
    }

    public function test_store_lands_back_on_the_list()
    {
        $project = Project::factory()->create();
        $user = $this->userWithCapability($project, 'manage_forms');

        $response = $this->actingAs($user)->post(
            route('designer.create', $project),
            ['name' => 'Field guide', 'description' => 'Notes']
        );

        $response->assertRedirect(route('designer.index'));
        $response->assertSessionHas('message', 'designer.form_create_success');
        $this->assertDatabaseHas('interview_forms', [
            'project_id' => $project->id,
            'name' => 'Field guide',
        ]);
    }
}
