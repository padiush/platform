<?php

namespace Database\Factories;

use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InterviewInstance>
 */
class InterviewInstanceFactory extends Factory
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
            'user_id' => User::factory(),
        ];
    }
}
