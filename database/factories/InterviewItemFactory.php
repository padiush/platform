<?php

namespace Database\Factories;

use App\Models\InterviewItem;
use App\Models\InterviewSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InterviewItem>
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
            'is_use_category' => false,
        ];
    }
}
