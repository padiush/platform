<?php

namespace Database\Factories;

use App\Models\CatalogSpecies;
use App\Models\Determination;
use App\Models\FieldRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Determination>
 */
class DeterminationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'field_record_id' => FieldRecord::factory(),
            'catalog_species_id' => CatalogSpecies::factory(),
            'determiner' => fake()->name(),
            'determined_on' => fake()->date(),
            'qualifier' => null,
            'is_current' => true,
            'notes' => null,
        ];
    }

    /** Collected, but nobody has put a name to it yet. */
    public function indeterminate()
    {
        return $this->state(fn (array $attributes) => [
            'catalog_species_id' => null,
        ]);
    }

    /** Superseded by a later determination. */
    public function superseded()
    {
        return $this->state(fn (array $attributes) => [
            'is_current' => false,
        ]);
    }
}
