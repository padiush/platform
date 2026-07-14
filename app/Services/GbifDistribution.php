<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Resolves a catalog species to its geographic range, sourced from Kew's World
 * Checklist of Vascular Plants (WCVP) via GBIF's official API.
 *
 * WCVP is the distribution dataset behind Plants of the World Online, but POWO's
 * own API sits behind a bot challenge and isn't usable server-side. GBIF indexes
 * the same WCVP dataset and exposes it through a documented, open API, so we get
 * the identical native/introduced-by-botanical-country data through a reliable
 * channel — and GBIF's name matcher normalizes the hybrid "×" marker (typed as
 * x/X or the real ×) for us.
 *
 * The range is slow-changing reference data, so callers cache the result rather
 * than hitting GBIF on every page view.
 */
class GbifDistribution
{
    private const MATCH_ENDPOINT = 'https://api.gbif.org/v1/species/match';

    private const DISTRIBUTIONS_ENDPOINT = 'https://api.gbif.org/v1/species/%d/distributions';

    private const TIMEOUT = 15;

    /** Enough to cover every botanical country a species could be recorded in. */
    private const DISTRIBUTION_LIMIT = 500;

    /** Only WCVP records are the curated native/introduced range we want. */
    private const WCVP_SOURCE = 'World Checklist of Vascular Plants';

    /** Shown to users as the provenance of the range. */
    public const SOURCE_LABEL = 'WCVP via GBIF';

    /**
     * GBIF establishmentMeans values that mean "not part of the native range".
     * WCVP leaves native records unflagged, so anything else is treated native.
     */
    private const INTRODUCED_MEANS = ['introduced', 'naturalised', 'naturalized', 'invasive', 'managed'];

    /**
     * @return array{
     *     matched: ?array{key: int, name: ?string, status: ?string, match_type: ?string},
     *     native: list<array{code: ?string, name: string}>,
     *     introduced: list<array{code: ?string, name: string}>,
     *     source: string
     * }
     */
    public function forSpecies(string $genus, string $name, ?string $authority): array
    {
        $empty = ['matched' => null, 'native' => [], 'introduced' => [], 'source' => self::SOURCE_LABEL];

        $match = $this->match($genus, $name);

        if ($match === null) {
            return $empty;
        }

        // WCVP attaches distribution to the accepted taxon, so a synonym match
        // must follow acceptedUsageKey to find its range.
        $distributionKey = (int) ($match['acceptedUsageKey'] ?? $match['usageKey']);
        $grouped = $this->group($this->distributions($distributionKey));

        return [
            'matched' => [
                'key' => (int) $match['usageKey'],
                'name' => $match['scientificName'] ?? null,
                'status' => $match['status'] ?? null,
                'match_type' => $match['matchType'] ?? null,
            ],
            'native' => $grouped['native'],
            'introduced' => $grouped['introduced'],
            'source' => self::SOURCE_LABEL,
        ];
    }

    /**
     * The scientific name sent to GBIF. GBIF tolerates the hybrid marker in any
     * form, but we canonicalise a lone x/X to × first so the query is stable
     * regardless of how the epithet was typed.
     */
    public function queryName(string $genus, string $name): string
    {
        return $this->normalizeHybridMarker(trim("{$genus} {$name}"));
    }

    /**
     * A standalone "x"/"X" token acting as the nothospecies marker becomes ×.
     * Only a lone token (bounded by start/space and a following space) is
     * touched — never an x inside a name like "Ximenia" or "maxima", and an
     * already-correct × or a glued marker is left alone.
     */
    public function normalizeHybridMarker(string $value): string
    {
        return preg_replace('/(^|\s)[xX](?=\s)/u', '$1×', $value) ?? $value;
    }

    /**
     * @return array<string, mixed>|null The GBIF match, or null when unmatched.
     */
    private function match(string $genus, string $name): ?array
    {
        $response = Http::timeout(self::TIMEOUT)
            ->acceptJson()
            ->get(self::MATCH_ENDPOINT, [
                'name' => $this->queryName($genus, $name),
                'kingdom' => 'Plantae',
                'strict' => 'false',
            ]);

        if ($response->failed()) {
            throw new RuntimeException('GBIF match failed with status '.$response->status());
        }

        $data = $response->json();

        if (($data['matchType'] ?? 'NONE') === 'NONE' || ($data['usageKey'] ?? null) === null) {
            return null;
        }

        return $data;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function distributions(int $usageKey): array
    {
        $response = Http::timeout(self::TIMEOUT)
            ->acceptJson()
            ->get(sprintf(self::DISTRIBUTIONS_ENDPOINT, $usageKey), [
                'limit' => self::DISTRIBUTION_LIMIT,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('GBIF distributions failed with status '.$response->status());
        }

        return $response->json('results') ?? [];
    }

    /**
     * Keeps only WCVP records, splits them into native vs introduced, dedupes by
     * region, and sorts each list by region name.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array{native: list<array{code: ?string, name: string}>, introduced: list<array{code: ?string, name: string}>}
     */
    private function group(array $records): array
    {
        $native = [];
        $introduced = [];
        $seen = [];

        foreach ($records as $record) {
            if (! str_contains((string) ($record['source'] ?? ''), self::WCVP_SOURCE)) {
                continue;
            }

            $code = $record['locationId'] ?? null;
            $regionName = $record['locality'] ?? $record['country'] ?? $record['area'] ?? $code;

            if (! is_string($regionName) || trim($regionName) === '') {
                continue;
            }

            $means = Str::lower((string) ($record['establishmentMeans'] ?? ''));
            $bucket = in_array($means, self::INTRODUCED_MEANS, true) ? 'introduced' : 'native';

            $dedupeKey = $bucket.'|'.($code ?? $regionName);
            if (isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;

            ${$bucket}[] = ['code' => $code, 'name' => $regionName];
        }

        return [
            'native' => $this->sortByName($native),
            'introduced' => $this->sortByName($introduced),
        ];
    }

    /**
     * @param  list<array{code: ?string, name: string}>  $regions
     * @return list<array{code: ?string, name: string}>
     */
    private function sortByName(array $regions): array
    {
        return (new Collection($regions))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}
