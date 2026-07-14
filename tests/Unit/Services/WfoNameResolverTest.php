<?php

namespace Tests\Unit\Services;

use App\Services\WfoNameResolver;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WfoNameResolverTest extends TestCase
{
    /**
     * Builds a WFO GraphQL name node. When $preferred is null the name is its
     * own accepted usage (an accepted name); otherwise it's a synonym of it.
     *
     * @return array<string, mixed>
     */
    private function node(string $id, string $plain, ?array $preferred = null): array
    {
        $self = [
            'id' => $id,
            'stableUri' => "https://list.worldfloraonline.org/{$id}",
            'fullNameStringPlain' => $plain,
            'fullNameStringHtml' => "<i>{$plain}</i>",
        ];

        return $self + [
            'currentPreferredUsage' => [
                'hasName' => $preferred ?? $self,
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $suggestions
     */
    private function fakeWfo(?array $match, array $suggestions): void
    {
        Http::fake([
            'list.worldfloraonline.org/*' => Http::response([
                'data' => [
                    'taxonNameMatch' => ['match' => $match, 'error' => false],
                    'taxonNameSuggestion' => $suggestions,
                ],
            ]),
        ]);
    }

    /** The five real WFO names behind the "Justicia carthagenensis" case. */
    private function justiciaSuggestions(): array
    {
        $ruellia = ['id' => 'wfo-0000402095', 'stableUri' => '', 'fullNameStringPlain' => 'Ruellia blechum L.', 'fullNameStringHtml' => '<i>Ruellia blechum</i> L.'];
        $sphaero = ['id' => 'wfo-0000354024', 'stableUri' => '', 'fullNameStringPlain' => 'Justicia sphaerosperma Vahl', 'fullNameStringHtml' => '<i>Justicia sphaerosperma</i> Vahl'];
        $mirabil = ['id' => 'wfo-0000354450', 'stableUri' => '', 'fullNameStringPlain' => 'Justicia mirabiloides Lam.', 'fullNameStringHtml' => '<i>Justicia mirabiloides</i> Lam.'];

        return [
            $this->node('wfo-0000354748', 'Justicia carthagenensis Willd. ex Nees', $ruellia),
            $this->node('wfo-1200029692', 'Justicia carthagenensis Benth. ex Nees', $sphaero),
            $this->node('wfo-0000354479', 'Justicia carthaginensis Jacq.'), // accepted
            $this->node('wfo-1200029658', 'Justicia carthaginensis Vahl', $mirabil),
            $this->node('wfo-1200029659', 'Justicia carthaginensis Nees & Mart.', $ruellia),
        ];
    }

    public function test_it_surfaces_an_accepted_spelling_variant_as_the_top_candidate()
    {
        // No exact authored match, mirroring WFO for the misspelled epithet.
        $this->fakeWfo(null, $this->justiciaSuggestions());

        $result = (new WfoNameResolver)->resolve('Justicia', 'carthagenensis', 'Jacq.');

        $this->assertSame('Justicia carthagenensis Jacq.', $result['recorded']);
        $this->assertNull($result['match']);

        $top = $result['candidates'][0];
        $this->assertSame('Justicia carthaginensis Jacq.', $top['full_name_plain']);
        $this->assertTrue($top['is_accepted']);
        $this->assertTrue($top['is_spelling_variant']);
        $this->assertNull($top['accepted_name']);
    }

    public function test_same_spelling_homonyms_are_not_flagged_as_spelling_variants()
    {
        $this->fakeWfo(null, $this->justiciaSuggestions());

        $result = (new WfoNameResolver)->resolve('Justicia', 'carthagenensis', 'Jacq.');

        $homonym = collect($result['candidates'])
            ->firstWhere('full_name_plain', 'Justicia carthagenensis Willd. ex Nees');

        $this->assertNotNull($homonym);
        // Same epithet, different author — a homonym, not a spelling variant.
        $this->assertFalse($homonym['is_spelling_variant']);
        // And it is a synonym of a different accepted taxon.
        $this->assertFalse($homonym['is_accepted']);
        $this->assertSame('Ruellia blechum L.', $homonym['accepted_name']['full_name_plain']);
    }

    public function test_an_exact_authored_match_is_returned_and_not_duplicated_in_candidates()
    {
        $accepted = $this->node('wfo-0000354479', 'Justicia carthaginensis Jacq.');
        $this->fakeWfo($accepted, $this->justiciaSuggestions());

        $result = (new WfoNameResolver)->resolve('Justicia', 'carthaginensis', 'Jacq.');

        $this->assertNotNull($result['match']);
        $this->assertSame('wfo-0000354479', $result['match']['wfo_id']);
        $this->assertTrue($result['match']['is_accepted']);

        // The exact match must not reappear among the candidate list.
        $ids = collect($result['candidates'])->pluck('wfo_id');
        $this->assertFalse($ids->contains('wfo-0000354479'));
    }

    public function test_a_synonym_match_reports_its_accepted_name()
    {
        $ruellia = ['id' => 'wfo-0000402095', 'stableUri' => '', 'fullNameStringPlain' => 'Ruellia blechum L.', 'fullNameStringHtml' => '<i>Ruellia blechum</i> L.'];
        $synonym = $this->node('wfo-0000354748', 'Justicia carthagenensis Willd. ex Nees', $ruellia);
        $this->fakeWfo($synonym, []);

        $result = (new WfoNameResolver)->resolve('Justicia', 'carthagenensis', 'Willd. ex Nees');

        $this->assertFalse($result['match']['is_accepted']);
        $this->assertSame('Ruellia blechum L.', $result['match']['accepted_name']['full_name_plain']);
    }

    public function test_it_queries_wfo_with_a_widened_epithet_prefix()
    {
        $this->fakeWfo(null, []);

        (new WfoNameResolver)->resolve('Justicia', 'carthagenensis', 'Jacq.');

        Http::assertSent(function ($request) {
            $vars = $request['variables'];

            // The exact authored name drives the authorship-aware match; the
            // suggestion is widened to a genus + short epithet prefix so
            // orthographic variants share the query.
            return $vars['match'] === 'Justicia carthagenensis Jacq.'
                && $vars['suggest'] === 'Justicia carth';
        });
    }

    public function test_it_drops_unrelated_names_below_the_similarity_floor()
    {
        // A prefix search can drag in unrelated names; ranking must drop the ones
        // too far from the recorded name to be plausible.
        $far = $this->node('wfo-9999', 'Aphelandra scabra Nees');
        $this->fakeWfo(null, [
            $this->node('wfo-0000354479', 'Justicia carthaginensis Jacq.'),
            $far,
        ]);

        $result = (new WfoNameResolver)->resolve('Justicia', 'carthagenensis', 'Jacq.');

        $ids = collect($result['candidates'])->pluck('wfo_id');
        $this->assertTrue($ids->contains('wfo-0000354479'));
        $this->assertFalse($ids->contains('wfo-9999'));
    }
}
