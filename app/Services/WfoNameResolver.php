<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Resolves a catalog species (genus, specific epithet, authority) against the
 * World Flora Online taxonomic backbone.
 *
 * Two things make a naive "search the binomial" lookup misleading:
 *
 *  - Homonyms. One binomial can name several unrelated plants, told apart only
 *    by their authorship ("Justicia carthagenensis Willd. ex Nees" vs
 *    "... Benth. ex Nees"), and each may be a synonym of a *different* accepted
 *    taxon. The binomial alone can't say which one a record means.
 *  - Orthographic variants. A recorded epithet may differ from WFO's spelling by
 *    a letter ("carthagenensis" vs the accepted "carthaginensis"), so an exact
 *    lookup misses the accepted name entirely.
 *
 * So this resolver (a) asks WFO's authorship-aware matcher for an exact
 * determination from the full authored name, and (b) pulls a wider set of
 * candidate names sharing the genus and an epithet prefix, ranked by similarity
 * to the full recorded name — so a spelling variant surfaces as the closest
 * match instead of silently disappearing among unrelated homonyms.
 */
class WfoNameResolver
{
    private const ENDPOINT = 'https://list.worldfloraonline.org/gql.php';

    private const TIMEOUT = 15;

    /** How many candidate names to surface after ranking. */
    private const MAX_CANDIDATES = 6;

    /** Below this similarity to the recorded name, a candidate isn't worth showing. */
    private const CANDIDATE_FLOOR = 0.55;

    /**
     * A candidate whose epithet differs from the recorded one but stays at/above
     * this epithet similarity is flagged as a probable spelling variant (same
     * name, different spelling) rather than a different species.
     */
    private const SPELLING_FLOOR = 0.7;

    /** Characters of the epithet used to widen the candidate net across variants. */
    private const PREFIX_LEN = 5;

    private const QUERY = <<<'GRAPHQL'
    query Resolve($match: String!, $suggest: String!) {
        taxonNameMatch(inputString: $match) {
            match {
                id
                stableUri
                fullNameStringPlain
                fullNameStringHtml
                currentPreferredUsage {
                    hasName { id stableUri fullNameStringPlain fullNameStringHtml }
                }
            }
            error
        }
        taxonNameSuggestion(termsString: $suggest, limit: 60) {
            id
            stableUri
            fullNameStringPlain
            fullNameStringHtml
            currentPreferredUsage {
                hasName { id stableUri fullNameStringPlain fullNameStringHtml }
            }
        }
    }
    GRAPHQL;

    private const FETCH_QUERY = <<<'GRAPHQL'
    query Fetch($id: String!) {
        taxonNameById(nameId: $id) {
            id
            fullNameStringPlain
            fullNameStringHtml
            fullNameStringNoAuthorsPlain
            genusString
            authorsString
            currentPreferredUsage {
                hasName {
                    id
                    fullNameStringPlain
                    fullNameStringHtml
                    fullNameStringNoAuthorsPlain
                    genusString
                    authorsString
                }
                path { hasName { nameString rank } }
            }
        }
    }
    GRAPHQL;

    /**
     * @return array{
     *     recorded: string,
     *     binomial: string,
     *     match: ?array<string, mixed>,
     *     candidates: list<array<string, mixed>>
     * }
     */
    public function resolve(string $genus, string $name, ?string $authority): array
    {
        $binomial = trim("{$genus} {$name}");
        $recorded = trim($binomial.' '.trim((string) $authority));

        $data = $this->query(
            ['match' => $recorded, 'suggest' => $this->suggestTerms($genus, $name)],
            self::QUERY,
        );

        $match = $data['taxonNameMatch']['match'] ?? null;
        $match = $match ? $this->name($match) : null;

        return [
            'recorded' => $recorded,
            'binomial' => $binomial,
            'match' => $match,
            'candidates' => $this->rankCandidates(
                $data['taxonNameSuggestion'] ?? [],
                $recorded,
                $name,
                $match['wfo_id'] ?? null,
            ),
        ];
    }

    /**
     * Fetches one WFO name by id with the taxonomy an "accept" action applies:
     * family (from the classification path), genus, specific epithet and author —
     * both for the name itself and, when it is a synonym, for its accepted name.
     *
     * @return array{
     *     wfo_id: string,
     *     full_name_plain: string,
     *     full_name_html: string,
     *     is_accepted: bool,
     *     apply: array{family: ?string, genus: string, name: string, authority: ?string},
     *     accepted: ?array{wfo_id: ?string, full_name_plain: string, full_name_html: string, apply: array{family: ?string, genus: string, name: string, authority: ?string}}
     * }|null  Null when WFO has no name with that id.
     */
    public function fetchName(string $wfoId): ?array
    {
        $node = $this->query(['id' => $wfoId], self::FETCH_QUERY)['taxonNameById'] ?? null;

        if ($node === null) {
            return null;
        }

        $preferred = $node['currentPreferredUsage'] ?? [];
        $acceptedNode = $preferred['hasName'] ?? null;

        // A synonym's classification path is that of its accepted taxon, so it is
        // the best-available family for both the name and its accepted name.
        $family = $this->familyFromPath($preferred['path'] ?? []);

        $isAccepted = $acceptedNode !== null
            && ($acceptedNode['id'] ?? null) === ($node['id'] ?? null);

        return [
            'wfo_id' => $node['id'] ?? $wfoId,
            'full_name_plain' => $node['fullNameStringPlain'] ?? '',
            'full_name_html' => $node['fullNameStringHtml'] ?? '',
            'is_accepted' => $isAccepted,
            'apply' => $this->applicableParts($node, $family),
            'accepted' => (! $isAccepted && $acceptedNode !== null) ? [
                'wfo_id' => $acceptedNode['id'] ?? null,
                'full_name_plain' => $acceptedNode['fullNameStringPlain'] ?? '',
                'full_name_html' => $acceptedNode['fullNameStringHtml'] ?? '',
                'apply' => $this->applicableParts($acceptedNode, $family),
            ] : null,
        ];
    }

    /**
     * The catalog fields (family, genus, epithet, author) a WFO name node maps
     * to. Genus comes from the dedicated field; the epithet is the remainder of
     * the author-less name after the genus.
     *
     * @param  array<string, mixed>  $node
     * @return array{family: ?string, genus: string, name: string, authority: ?string}
     */
    private function applicableParts(array $node, ?string $family): array
    {
        $noAuthors = trim((string) ($node['fullNameStringNoAuthorsPlain'] ?? ''));
        $genus = trim((string) ($node['genusString'] ?? '')) ?: $this->tokenAt($noAuthors, 0);

        $epithet = trim(Str::of($noAuthors)->after($genus)->toString());
        if ($epithet === '') {
            $epithet = $this->tokenAt($noAuthors, 1);
        }

        $authority = trim((string) ($node['authorsString'] ?? ''));

        return [
            'family' => $family,
            'genus' => $genus,
            'name' => $epithet,
            'authority' => $authority !== '' ? $authority : null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $path
     */
    private function familyFromPath(array $path): ?string
    {
        foreach ($path as $node) {
            if (($node['hasName']['rank'] ?? null) === 'family') {
                return $node['hasName']['nameString'] ?? null;
            }
        }

        return null;
    }

    private function tokenAt(string $value, int $index): string
    {
        $parts = preg_split('/\s+/', trim($value)) ?: [];

        return $parts[$index] ?? '';
    }

    /**
     * Genus plus a short epithet prefix. A prefix (not the full epithet) is what
     * lets orthographic variants — which diverge in the epithet's tail — share
     * one suggestion query with the exactly-spelled homonyms.
     */
    private function suggestTerms(string $genus, string $name): string
    {
        $epithet = trim($name);
        $prefix = Str::substr($epithet, 0, self::PREFIX_LEN);

        return trim($genus.' '.$prefix);
    }

    /**
     * @param  array<int, array<string, mixed>>  $suggestions
     * @return list<array<string, mixed>>
     */
    private function rankCandidates(
        array $suggestions,
        string $recorded,
        string $recordedEpithet,
        ?string $matchId
    ): array {
        return (new Collection($suggestions))
            ->map(fn (array $n) => $this->name($n) + [
                'similarity' => $this->similarity($recorded, $n['fullNameStringPlain'] ?? ''),
            ])
            // The exact match is surfaced on its own; don't repeat it here.
            ->reject(fn (array $c) => $matchId !== null && $c['wfo_id'] === $matchId)
            ->filter(fn (array $c) => $c['similarity'] >= self::CANDIDATE_FLOOR)
            ->sortByDesc('similarity')
            ->take(self::MAX_CANDIDATES)
            ->map(fn (array $c) => $c + [
                'is_spelling_variant' => $this->isSpellingVariant($recordedEpithet, $c['full_name_plain']),
            ])
            ->values()
            ->all();
    }

    /**
     * Normalizes one WFO name node into the shape the species page renders,
     * resolving its accepted-name status from its current preferred usage.
     *
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private function name(array $node): array
    {
        $preferred = $node['currentPreferredUsage']['hasName'] ?? null;
        $id = $node['id'] ?? null;

        // A name whose preferred usage is itself is the accepted name; one whose
        // preferred usage is a different name is a synonym of it.
        $isAccepted = $preferred !== null && ($preferred['id'] ?? null) === $id;

        return [
            'wfo_id' => $id,
            'stable_uri' => $node['stableUri'] ?? null,
            'full_name_plain' => $node['fullNameStringPlain'] ?? '',
            'full_name_html' => $node['fullNameStringHtml'] ?? '',
            'is_accepted' => $isAccepted,
            'accepted_name' => (! $isAccepted && $preferred !== null) ? [
                'full_name_plain' => $preferred['fullNameStringPlain'] ?? '',
                'full_name_html' => $preferred['fullNameStringHtml'] ?? '',
                'stable_uri' => $preferred['stableUri'] ?? null,
            ] : null,
        ];
    }

    /**
     * A candidate is a probable spelling variant when its epithet differs from
     * the recorded epithet yet stays highly similar to it — the same name under
     * a different spelling, as opposed to a same-spelling homonym.
     */
    private function isSpellingVariant(string $recordedEpithet, string $candidatePlain): bool
    {
        $candidateEpithet = $this->epithetOf($candidatePlain);

        if ($candidateEpithet === '') {
            return false;
        }

        if ($this->normalize($candidateEpithet) === $this->normalize($recordedEpithet)) {
            return false;
        }

        return $this->similarity($recordedEpithet, $candidateEpithet) >= self::SPELLING_FLOOR;
    }

    /** The specific epithet — the second word of a "Genus epithet Author" string. */
    private function epithetOf(string $fullNamePlain): string
    {
        $parts = preg_split('/\s+/', trim($fullNamePlain)) ?: [];

        return $parts[1] ?? '';
    }

    private function similarity(string $a, string $b): float
    {
        $a = $this->normalize($a);
        $b = $this->normalize($b);
        $max = max(strlen($a), strlen($b));

        // levenshtein() rejects strings over 255 bytes; names are short, but
        // guard so a malformed value can't raise a warning.
        if ($max === 0 || strlen($a) > 255 || strlen($b) > 255) {
            return 0.0;
        }

        return 1 - levenshtein($a, $b) / $max;
    }

    private function normalize(string $value): string
    {
        return Str::lower(Str::ascii(trim(preg_replace('/\s+/', ' ', $value) ?? '')));
    }

    /**
     * @param  array<string, string>  $variables
     * @return array<string, mixed>
     */
    private function query(array $variables, string $query): array
    {
        $response = Http::timeout(self::TIMEOUT)
            // WFO's server omits its intermediate certificate; see the bundle's header.
            ->withOptions(['verify' => base_path('resources/certs/wfo-ca-chain.pem')])
            ->acceptJson()
            ->post(self::ENDPOINT, [
                'query' => $query,
                'variables' => $variables,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('WFO request failed with status '.$response->status());
        }

        return $response->json('data') ?? [];
    }
}
