<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => 1,
            'name' => fake()->sentence,
            'author' => fake()->name,
            'institution' => fake()->company,
            'author_email' => fake()->email,
            'country' => fake()->country,
            'finished' => false,
            'published' => false,
            'shared' => false,
        ];
    }

    /**
     * Indicate that the project is finished.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
     */
    public function finished()
    {
        return $this->state(function (array $attributes) {
            return [
                'finished' => true,
            ];
        });
    }

    /**
     * Indicate that the project is published.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
     */
    public function published()
    {
        return $this->state(function (array $attributes) {
            return [
                'published' => true,
            ];
        });
    }

    /**
     * Indicate that the project is shared.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
     */
    public function shared()
    {
        return $this->state(function (array $attributes) {
            return [
                'shared' => true,
            ];
        });
    }
}
