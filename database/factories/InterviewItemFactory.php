<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\InterviewSection;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InterviewItem>
 */
class InterviewItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'interview_section_id' => InterviewSection::factory(),
            'label' => fake()->words(2, true),
            'name' => fake()->slug(2),
            'type' => 'text',
            'required' => false,
            'order' => 1,
            'link_to_species' => false,
        ];
    }
}
