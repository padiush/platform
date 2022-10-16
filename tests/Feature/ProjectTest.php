<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

use Tests\TestCase;

use App\Models\Project;
use App\Models\User;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_index_can_be_rendered()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('projects.index'));

        $response->assertStatus(200);
    }

    public function test_projects_index_displays_own_projects()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('projects.index'));

        $response->assertSee($project->name);
    }

    public function test_projects_index_does_not_display_other_projects()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $project = Project::factory()->create([
            'user_id' => $anotherUser->id,
        ]);

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
    }

    public function test_projects_edit_can_be_rendered()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('projects.edit', $project));

        $response->assertStatus(200);
        $response->assertSee($project->name);
    }

    public function test_projects_edit_cannot_be_rendered_for_others_projects()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $project = Project::factory()->create([
            'user_id' => $anotherUser->id,
        ]);

        $response = $this->actingAs($user)->get(route('projects.edit', $project));

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('error', 'No tienes permiso para editar este proyecto.');
    }

    public function test_projects_can_be_updated(){
        $user = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $user->id]);

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
}
