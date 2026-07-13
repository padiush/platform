<?php

namespace Tests\Feature\Api;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

class MeTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    public function test_returns_only_projects_the_user_can_record_data_on(): void
    {
        $user = User::factory()->create();
        $recordable = Project::factory()->create(['name' => 'Capture study']);
        $reportsOnly = Project::factory()->create(['name' => 'Analysis only']);

        $this->giveAccess($user, $recordable, 'record_data');
        // A role with generate_reports but not record_data must be excluded.
        $this->giveAccess($user, $reportsOnly, 'record_data', false);

        Sanctum::actingAs($user, ['capture']);

        $response = $this->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonCount(1, 'projects')
            ->assertJsonPath('projects.0.id', $recordable->id)
            ->assertJsonPath('projects.0.capabilities.record_data', true);
    }

    public function test_capabilities_map_is_present(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $this->giveAccess($user, $project, 'record_data');

        Sanctum::actingAs($user, ['capture']);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'projects' => [['id', 'name', 'capabilities' => [
                    'manage_project', 'record_data', 'generate_reports', 'view_catalog',
                ], 'updated_at']],
            ]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/me')
            ->assertStatus(401)
            ->assertJsonPath('message', 'api.unauthenticated');
    }
}
