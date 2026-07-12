<?php

namespace Tests\Unit;

use App\Models\CatalogSpecies;
use App\Models\InstanceAnswer;
use App\Models\Project;
use App\Services\CatalogSpeciesSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSpeciesSearchTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    private CatalogSpeciesSearch $search;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->search = new CatalogSpeciesSearch;
    }

    private function species(array $attributes = []): CatalogSpecies
    {
        return CatalogSpecies::factory()->create(array_merge(
            ['project_id' => $this->project->id],
            $attributes,
        ));
    }

    private function paginate(array $filters)
    {
        return $this->search->paginate($this->project, $filters, 1);
    }

    /** @return array<int, string> genus values of the returned page, in order */
    private function genera($paginator): array
    {
        return array_map(fn ($sp) => $sp->genus, $paginator->items());
    }

    public function test_matches_a_scientific_field()
    {
        $this->species(['genus' => 'Cecropia', 'name' => 'obtusifolia']);
        $this->species(['genus' => 'Inga', 'name' => 'edulis']);

        $result = $this->paginate(['q' => 'cecropia']);

        $this->assertSame(['Cecropia'], $this->genera($result));
    }

    public function test_matching_is_accent_and_case_insensitive()
    {
        $this->species(['genus' => 'Cecropia', 'name' => 'obtusifolia']);

        $result = $this->paginate(['q' => 'CÉCROPÍA']);

        $this->assertSame(['Cecropia'], $this->genera($result));
    }

    public function test_matching_tolerates_a_typo()
    {
        $this->species(['genus' => 'Cecropia', 'name' => 'obtusifolia']);

        // "cecropa" is one deletion away from "cecropia".
        $result = $this->paginate(['q' => 'cecropa']);

        $this->assertSame(['Cecropia'], $this->genera($result));
    }

    public function test_matches_a_linked_interview_name()
    {
        $species = $this->species(['genus' => 'Cecropia', 'name' => 'obtusifolia']);
        InstanceAnswer::factory()->create([
            'catalog_species_id' => $species->id,
            'answer' => 'guarumo',
        ]);
        $this->species(['genus' => 'Inga', 'name' => 'edulis']);

        $result = $this->paginate(['q' => 'guarumo']);

        $this->assertSame(['Cecropia'], $this->genera($result));
    }

    public function test_unrelated_term_matches_nothing()
    {
        $this->species(['genus' => 'Cecropia', 'name' => 'obtusifolia']);

        $result = $this->paginate(['q' => 'zzzzzz']);

        $this->assertSame(0, $result->total());
    }

    public function test_scientific_match_outranks_a_linked_name_only_match()
    {
        // "cecropia" is the genus here — a direct scientific hit.
        $direct = $this->species(['genus' => 'Cecropia', 'name' => 'obtusifolia']);

        // Here "cecropia" only appears as a linked interview note.
        $linkedOnly = $this->species(['genus' => 'Ficus', 'name' => 'insipida']);
        InstanceAnswer::factory()->create([
            'catalog_species_id' => $linkedOnly->id,
            'answer' => 'parece cecropia',
        ]);

        $result = $this->paginate(['q' => 'cecropia']);

        $this->assertSame(['Cecropia', 'Ficus'], $this->genera($result));
    }

    public function test_all_query_tokens_must_match()
    {
        // Has both tokens: "cecropia" (genus) and "guarumo" (linked name).
        $both = $this->species(['genus' => 'Cecropia', 'name' => 'obtusifolia']);
        InstanceAnswer::factory()->create([
            'catalog_species_id' => $both->id,
            'answer' => 'guarumo',
        ]);

        // Has only "cecropia".
        $this->species(['genus' => 'Cecropia', 'name' => 'peltata']);

        $result = $this->paginate(['q' => 'cecropia guarumo']);

        $this->assertSame(1, $result->total());
        $this->assertSame('obtusifolia', $result->items()[0]->name);
    }

    public function test_family_filter_narrows_results()
    {
        $this->species(['family' => 'Urticaceae', 'genus' => 'Cecropia']);
        $this->species(['family' => 'Fabaceae', 'genus' => 'Inga']);

        $result = $this->paginate(['family' => 'Fabaceae']);

        $this->assertSame(['Inga'], $this->genera($result));
    }

    public function test_link_status_filter_selects_linked_and_unlinked()
    {
        $linked = $this->species(['genus' => 'Cecropia']);
        InstanceAnswer::factory()->create(['catalog_species_id' => $linked->id]);
        $this->species(['genus' => 'Inga']);

        $this->assertSame(['Cecropia'], $this->genera($this->paginate(['link' => 'linked'])));
        $this->assertSame(['Inga'], $this->genera($this->paginate(['link' => 'unlinked'])));
        $this->assertSame(2, $this->paginate(['link' => 'all'])->total());
    }

    public function test_most_linked_ordering_puts_best_documented_first()
    {
        $few = $this->species(['genus' => 'Inga', 'family' => 'Fabaceae']);
        InstanceAnswer::factory()->create(['catalog_species_id' => $few->id]);

        $many = $this->species(['genus' => 'Cecropia', 'family' => 'Urticaceae']);
        InstanceAnswer::factory()->count(3)->create(['catalog_species_id' => $many->id]);

        $result = $this->paginate(['sort' => 'linked']);

        $this->assertSame(['Cecropia', 'Inga'], $this->genera($result));
    }

    public function test_default_ordering_is_taxonomic()
    {
        $this->species(['family' => 'Urticaceae', 'genus' => 'Cecropia', 'name' => 'obtusifolia']);
        $this->species(['family' => 'Fabaceae', 'genus' => 'Inga', 'name' => 'edulis']);

        $result = $this->paginate([]);

        // Fabaceae sorts before Urticaceae by family.
        $this->assertSame(['Inga', 'Cecropia'], $this->genera($result));
    }
}
