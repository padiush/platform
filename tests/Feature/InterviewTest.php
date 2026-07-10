<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

use App\Models\User;
use App\Models\Project;
use App\Models\ProjectAccess;
use App\Models\ProjectCapability;

class InterviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_interview_designer_renders()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $user->id]);

        $access = ProjectAccess::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'project_capability_id' => ProjectCapability::where('manage_forms', true)->first()->id,
        ]);

        $response = $this->actingAs($user)->get(route('designer.index'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Designer/Index')
                ->has('projects', 1)
                ->where('projects.0.name', $project->name)
        );
    }

    public function test_interview_designer_does_not_show_finished_projects(){
        $user = User::factory()->create();

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'finished' => true,
        ]);

        $anotherProject = Project::factory()->create(['user_id' => $user->id]);

        $access = ProjectAccess::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'project_capability_id' => ProjectCapability::where('manage_forms', true)->first()->id,
        ]);

        $anotherAccess = ProjectAccess::factory()->create([
            'user_id' => $user->id,
            'project_id' => $anotherProject->id,
            'project_capability_id' => ProjectCapability::where('manage_forms', true)->first()->id,
        ]);

        $response = $this->actingAs($user)->get(route('designer.index'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Designer/Index')
                ->has('projects', 1)
                ->where('projects.0.name', $anotherProject->name)
        );
    }

    public function test_interview_designer_does_not_show_projects_without_manage_forms_capability(){
        $user = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $user->id]);
        $anotherProject = Project::factory()->create(['user_id' => $user->id]);

        $access = ProjectAccess::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'project_capability_id' => ProjectCapability::where('manage_forms', false)->first()->id,
        ]);

        $anotherAccess = ProjectAccess::factory()->create([
            'user_id' => $user->id,
            'project_id' => $anotherProject->id,
            'project_capability_id' => ProjectCapability::where('manage_forms', true)->first()->id,
        ]);

        $response = $this->actingAs($user)->get(route('designer.index'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Designer/Index')
                ->has('projects', 1)
                ->where('projects.0.name', $anotherProject->name)
        );
    }

    public function test_interview_designer_does_not_render_if_all_projects_are_finished()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'finished' => true,
        ]);

        $access = ProjectAccess::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'project_capability_id' => ProjectCapability::where('manage_forms', true)->first()->id,
        ]);

        $response = $this->actingAs($user)->get(route('designer.index'));

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('error', 'No tienes proyectos activos para diseñar entrevistas.');
    }
}
