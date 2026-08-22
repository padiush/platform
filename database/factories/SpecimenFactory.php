<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Specimen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Specimen>
 */
class SpecimenFactory extends Factory
{
    /**
     * Unvouchered by default, because that is the ordinary case in the field:
     * a specimen is collected long before anything accessions it.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'project_id' => Project::factory(),
            'accession_number' => null,
            'collection_number' => fake()->numerify('##'),
            'collector' => fake()->name(),
            'collected_on' => fake()->date(),
            'locality' => fake()->words(3, true),
            'repository' => null,
            'notes' => null,
        ];
    }

    /** Carries an accession number, and so counts toward voucher coverage. */
    public function vouchered(?string $accessionNumber = null)
    {
        return $this->state(fn (array $attributes) => [
            'accession_number' => $accessionNumber ?? fake()->unique()->numerify('HERB-####'),
        ]);
    }
}
