<?php

namespace Database\Factories;

use App\Models\ProjectCapability;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectCapability>
 */
class ProjectCapabilityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->word,
            'manage_project' => false,
            'manage_users' => false,
            'manage_forms' => false,
            'record_data' => false,
            'manage_data' => false,
            'generate_reports' => false,
        ];
    }
}
