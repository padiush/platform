<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectCapability>
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
