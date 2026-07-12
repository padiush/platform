<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectAccess;
use App\Models\ProjectCapability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    public function test_hub_pages_render_in_place_when_the_user_lacks_the_capability()
    {
        $project = Project::factory()->create();
        // A role with every flag off (factory defaults): the member can open
        // each hub and gets its empty state instead of a redirect bounce.
        $capability = ProjectCapability::factory()->create(['name' => 'None']);
        $user = User::factory()->create();
        ProjectAccess::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'project_capability_id' => $capability->id,
        ]);

        $hubs = [
            ['designer.index', 'Designer/Index'],
            ['interviews.index', 'Interviews/Index'],
            ['catalogs.index', 'Catalog/Index'],
            ['data.index', 'Data/Index'],
        ];

        foreach ($hubs as [$routeName, $component]) {
            $response = $this->actingAs($user)->get(route($routeName));

            $response->assertOk();
            $response->assertInertia(
                fn (Assert $page) => $page->component($component)
            );
        }
    }

    public function test_shared_capability_flags_reflect_the_users_accesses()
    {
        $project = Project::factory()->create();
        // The seeded role where manage_forms is false is the data-analyst
        // style role; whatever its other flags are, manage_forms must come
        // through as false and the flags must exist for every section.
        $user = $this->userWithCapability($project, 'manage_forms', false);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->where('auth.capabilities.manage_forms', false)
                ->has('auth.capabilities.record_data')
                ->has('auth.capabilities.data')
                ->has('auth.capabilities.view_catalog')
                ->where('auth.projects', 1)
        );
    }

    public function test_shared_capability_flags_are_true_with_a_full_access_role()
    {
        $project = Project::factory()->create();
        $user = $this->userWithCapability($project, 'manage_project');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->where('auth.capabilities.manage_forms', true)
                ->where('auth.capabilities.record_data', true)
                ->where('auth.capabilities.data', true)
                ->where('auth.capabilities.view_catalog', true)
        );
    }

    public function test_users_without_accesses_get_zero_capability_flags()
    {
        $response = $this->actingAs($this->outsider())->get(route('dashboard'));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->where('auth.projects', 0)
                ->where('auth.capabilities.manage_forms', false)
                ->where('auth.capabilities.view_catalog', false)
        );
    }
}
