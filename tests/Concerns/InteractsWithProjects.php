<?php

namespace Tests\Concerns;

use App\Models\Project;
use App\Models\ProjectAccess;
use App\Models\ProjectCapability;
use App\Models\User;

trait InteractsWithProjects
{
    /**
     * Create a user with access to the project through the first seeded
     * capability role where the given flag matches $has.
     */
    protected function userWithCapability(
        Project $project,
        string $capability,
        bool $has = true
    ): User {
        $user = User::factory()->create();

        ProjectAccess::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'project_capability_id' => ProjectCapability::where(
                $capability,
                $has
            )->first()->id,
        ]);

        return $user;
    }

    /**
     * Grant an existing user access to a project through the first seeded
     * capability role where the given flag matches $has. Useful when one user
     * needs access to several projects with different roles.
     */
    protected function giveAccess(
        User $user,
        Project $project,
        string $capability,
        bool $has = true
    ): ProjectAccess {
        return ProjectAccess::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'project_capability_id' => ProjectCapability::where(
                $capability,
                $has
            )->first()->id,
        ]);
    }

    /**
     * Create a user with no access to any project.
     */
    protected function outsider(): User
    {
        return User::factory()->create();
    }
}
