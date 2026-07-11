<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectAccess;
use App\Models\ProjectCapability;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectAccess>
 */
class ProjectAccessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'project_capability_id' => ProjectCapability::factory(),
        ];
    }
}
