<?php

namespace Database\Factories;

use App\Models\InterviewForm;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InterviewForm>
 */
class InterviewFormFactory extends Factory
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
            'name' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
