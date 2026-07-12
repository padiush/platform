<?php

namespace Database\Factories;

use App\Models\ChartPreference;
use App\Models\InterviewItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChartPreference>
 */
class ChartPreferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'interview_item_id' => InterviewItem::factory(),
            'chart_type' => 'bar',
        ];
    }
}
