<?php

namespace Database\Factories;

use App\Models\InstanceAnswer;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstanceAnswer>
 */
class InstanceAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'interview_instance_id' => InterviewInstance::factory(),
            'interview_section_id' => InterviewSection::factory(),
            'interview_item_id' => InterviewItem::factory(),
            'repeatable_index' => null,
            'answer' => fake()->words(2, true),
            'catalog_species_id' => null,
        ];
    }
}
