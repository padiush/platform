<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectCapability;
use App\Models\ProjectInvite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectInvite>
 */
class ProjectInviteFactory extends Factory
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
            'inviting_user_id' => User::factory(),
            'invited_user_id' => User::factory(),
            'invited_email' => fake()->unique()->safeEmail(),
            'project_capability_id' => ProjectCapability::factory(),
            'expires_at' => now()->addDays(7),
        ];
    }
}
