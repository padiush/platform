<?php

namespace Database\Factories;

use App\Models\CollectingPermit;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CollectingPermit>
 */
class CollectingPermitFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'project_id' => Project::factory(),
            'authority' => fake()->randomElement(['MARN', 'CONAP', 'SERFOR', 'SISBIO']),
            'reference' => fake()->unique()->bothify('RES-###-20##'),
            'issued_on' => '2026-01-15',
            'expires_on' => '2027-01-14',
            'notes' => null,
        ];
    }

    /** Its expiry date has passed — a fact on the permit, not a verdict. */
    public function expired()
    {
        return $this->state(fn (array $attributes) => [
            'issued_on' => '2024-01-15',
            'expires_on' => '2025-01-14',
        ]);
    }
}
