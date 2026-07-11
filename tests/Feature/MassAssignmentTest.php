<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_admin_is_not_mass_assignable()
    {
        $user = new User([
            'name' => 'Someone',
            'email' => 'someone@example.com',
            'password' => 'irrelevant',
            'system_admin' => true,
        ]);

        $this->assertNull($user->getAttributes()['system_admin'] ?? null);
    }

    public function test_project_owner_is_not_mass_assignable()
    {
        $project = new Project([
            'name' => 'A project',
            'user_id' => 999,
        ]);

        $this->assertNull($project->user_id);
    }
}
