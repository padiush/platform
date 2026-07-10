<?php

namespace Database\Factories;

use App\Models\InterviewForm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InterviewSection>
 */
class InterviewSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'interview_form_id' => InterviewForm::factory(),
            'name' => fake()->sentence(2),
            'order' => 1,
            'repeatable' => false,
        ];
    }
}
