<?php

namespace Tests\Unit\Services;

use App\Services\GbifDistribution;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GbifDistributionTest extends TestCase
{
    private function wcvp(string $code, string $name, ?string $means = null): array
    {
        return array_filter([
            'locationId' => $code,
            'locality' => $name,
            'source' => 'The World Checklist of Vascular Plants (WCVP)',
            'establishmentMeans' => $means,
        ], fn ($v) => $v !== null);
    }

    /**
     * @param  array<int, array<string, mixed>>  $distributions
     */
    private function fakeGbif(array $match, array $distributions): void
    {
        Http::fake([
            'api.gbif.org/v1/species/match*' => Http::response($match),
            'api.gbif.org/v1/species/*/distributions*' => Http::response(['results' => $distributions]),
        ]);
    }

    public function test_it_groups_wcvp_records_into_native_and_introduced()
    {
        $this->fakeGbif(
            ['usageKey' => 2984473, 'scientificName' => 'Cecropia obtusifolia Bertol.', 'status' => 'ACCEPTED', 'matchType' => 'EXACT'],
            [
                $this->wcvp('TDWG:CLM', 'Colombia'),
                $this->wcvp('TDWG:BLZ', 'Belize'),
                $this->wcvp('TDWG:HWI', 'Hawaii', 'INTRODUCED'),
                $this->wcvp('TDWG:BLZ', 'Belize'), // duplicate → deduped
                ['locality' => 'Middle America', 'source' => 'Integrated Taxonomic Information System (ITIS)'], // non-WCVP → ignored
            ],
        );

        $result = (new GbifDistribution)->forSpecies('Cecropia', 'obtusifolia', 'Bertol.');

        $this->assertSame('Cecropia obtusifolia Bertol.', $result['matched']['name']);
        $this->assertSame('EXACT', $result['matched']['match_type']);
        $this->assertSame(['Belize', 'Colombia'], array_column($result['native'], 'name'));
        $this->assertSame(['Hawaii'], array_column($result['introduced'], 'name'));
        $this->assertSame('WCVP via GBIF', $result['source']);
    }

    public function test_it_returns_empty_when_gbif_has_no_match()
    {
        $this->fakeGbif(['matchType' => 'NONE'], []);

        $result = (new GbifDistribution)->forSpecies('Zzz', 'zzz', null);

        $this->assertNull($result['matched']);
        $this->assertSame([], $result['native']);
        $this->assertSame([], $result['introduced']);
    }

    public function test_it_follows_the_accepted_taxon_for_a_synonym_match()
    {
        $this->fakeGbif(
            ['usageKey' => 111, 'acceptedUsageKey' => 999, 'scientificName' => 'Old name', 'status' => 'SYNONYM', 'matchType' => 'EXACT'],
            [$this->wcvp('TDWG:MXE', 'Mexico Central')],
        );

        (new GbifDistribution)->forSpecies('Old', 'name', null);

        // Distribution must be fetched for the accepted taxon (999), not the synonym (111).
        Http::assertSent(fn ($request) => str_contains($request->url(), '/species/999/distributions'));
    }

    public function test_it_normalizes_the_hybrid_marker_typed_as_x()
    {
        $gbif = new GbifDistribution;

        $this->assertSame('Citrus × limon', $gbif->queryName('Citrus', 'x limon'));
        $this->assertSame('Citrus × limon', $gbif->queryName('Citrus', 'X limon'));
        // A real × is left as-is.
        $this->assertSame('Citrus × limon', $gbif->queryName('Citrus', '× limon'));
        $this->assertSame('Citrus limon', $gbif->queryName('Citrus', 'limon'));
        // An x inside a name (or a genus starting with X) must never be touched.
        $this->assertSame('Ximenia americana', $gbif->queryName('Ximenia', 'americana'));
        $this->assertSame('Citrus maxima', $gbif->queryName('Citrus', 'maxima'));
    }

    public function test_it_sends_the_normalized_name_and_plant_kingdom_to_gbif()
    {
        $this->fakeGbif(['matchType' => 'NONE'], []);

        (new GbifDistribution)->forSpecies('Citrus', 'x limon', null);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/species/match')
                && $request['name'] === 'Citrus × limon'
                && $request['kingdom'] === 'Plantae';
        });
    }
}
