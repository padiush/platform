<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

use Tests\TestCase;

use App\Models\Project;
use App\Models\User;
use App\Models\ProjectAccess;
use App\Models\ProjectCapability;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_index_can_be_rendered()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('projects.index'));

        $response->assertStatus(200);
    }

    public function test_projects_index_displays_projects_with_access()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $user->id]);

        $access = ProjectAccess::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'project_capability_id' => ProjectCapability::where('manage_project', true)->first()->id,
        ]);

        $response = $this->actingAs($user)->get(route('projects.index'));

        $response->assertSee($project->name);
    }

    public function test_projects_index_does_not_display_projects_without_access()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create();

        $response = $this->actingAs($user)->get(route('projects.index'));

        $response->assertDontSee($project->name);
    }

    public function test_projects_create_can_be_rendered()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('projects.create'));

        $response->assertStatus(200);
    }

    public function test_projects_can_be_created(){
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('projects.create'), [
            'name' => 'Proyecto de prueba',
            'author' => 'Autor de prueba',
            'institution' => 'Institución de prueba',
            'author_email' => 'testing@avalontechsv.dev',
            'country' => 'El Salvador',
        ]);

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('success', 'Se ha creado el proyecto exitosamente.');
        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'name' => 'Proyecto de prueba',
            'author' => 'Autor de prueba',
            'institution' => 'Institución de prueba',
            'author_email' => 'testing@avalontechsv.dev',
            'country' => 'El Salvador',
            'finished' => false,
            'published' => false,
            'shared' => false,
        ]);

        $this->assertDatabaseHas('project_accesses', [
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', true)->first()->id,
        ]);
    }

    public function test_projects_edit_can_be_rendered_for_projects_with_manage_capability()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $user->id]);

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', true)->first()->id,
        ]);

        $response = $this->actingAs($user)->get(route('projects.edit', $project));

        $response->assertStatus(200);
        $response->assertSee($project->name);
    }

    public function test_projects_edit_cannot_be_rendered_for_projects_without_access()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create();

        $response = $this->actingAs($user)->get(route('projects.edit', $project));

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('error', 'No tienes acceso a este proyecto.');
    }

    public function test_projects_edit_cannot_be_rendered_for_projects_without_manage_capability()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create();

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
        ]);

        $response = $this->actingAs($user)->get(route('projects.edit', $project));

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('error', 'No cuentas con los permisos necesarios para editar este proyecto.');
    }

    public function test_projects_can_be_updated(){
        $user = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $user->id]);

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', true)->first()->id,
        ]);

        $response = $this->actingAs($user)->post(route('projects.edit', $project), [
            'name' => 'Proyecto de prueba',
            'author' => 'Autor de prueba',
            'institution' => 'Institución de prueba',
            'author_email' => 'testing@avalontechsv.dev',
            'country' => 'El Salvador',
        ]);

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('success', 'Se ha actualizado el proyecto exitosamente.');
        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'name' => 'Proyecto de prueba',
            'author' => 'Autor de prueba',
            'institution' => 'Institución de prueba',
            'author_email' => 'testing@avalontechsv.dev',
            'country' => 'El Salvador',
            'finished' => false,
            'published' => false,
            'shared' => false,
        ]);
    }

    public function test_project_accesses_can_be_rendered_for_projects_with_manage_capability()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $user->id]);

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', true)->first()->id,
        ]);

        $response = $this->actingAs($user)->get(route('projects.accesses', $project));

        $response->assertStatus(200);
    }

    public function test_project_accesses_cannot_be_rendered_for_projects_without_access()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create();

        $response = $this->actingAs($user)->get(route('projects.accesses', $project));

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('error', 'No tienes acceso a este proyecto.');
    }

    public function test_project_accesses_cannot_be_rendered_for_projects_without_manage_capability()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create();

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
        ]);

        $response = $this->actingAs($user)->get(route('projects.accesses', $project));

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('error', 'No cuentas con los permisos necesarios para editar este proyecto.');
    }

    public function test_project_access_cannot_be_revoked_for_own_user()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $user->id]);

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', true)->first()->id,
        ]);

        $response = $this->actingAs($user)->delete(route('projects.accesses.revoke', ['project' => $project, 'user' => $user]));

        $response->assertRedirect(route('projects.accesses', $project));
        $response->assertSessionHas('error', 'No puedes revocar tu propio acceso a este proyecto.');
        $this->assertDatabaseHas('project_accesses', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', true)->first()->id,
        ]);
    }

    public function test_project_access_can_be_revoked_for_other_user()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $user->id]);

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', true)->first()->id,
        ]);

        $otherUser = User::factory()->create();

        $otherAccess = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $otherUser->id,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
        ]);

        $response = $this->actingAs($user)->delete(route('projects.accesses.revoke', ['project' => $project, 'user' => $otherUser]));

        $response->assertRedirect(route('projects.accesses', $project));
        $response->assertSessionHas('success', 'Se ha revocado el acceso al proyecto exitosamente.');
        $this->assertDatabaseMissing('project_accesses', [
            'project_id' => $project->id,
            'user_id' => $otherUser->id,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
        ]);
    }
}
