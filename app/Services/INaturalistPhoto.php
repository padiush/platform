<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Looks up a single reference photo for a species from iNaturalist, used to let
 * a researcher visually confirm an identification while registering it.
 *
 * The photo is never stored: the browser loads it through a same-origin proxy
 * (so the researcher's IP isn't exposed to a third party), and the proxy only
 * streams it. Both the photographer (from iNaturalist's attribution string) and
 * the platform are credited wherever it's shown.
 */
class INaturalistPhoto
{
    private const TAXA_ENDPOINT = 'https://api.inaturalist.org/v1/taxa';

    private const TIMEOUT = 12;

    public const SOURCE_LABEL = 'iNaturalist';

    /**
     * Hosts iNaturalist serves photos from. The proxy fetches ONLY these, so a
     * name lookup can never be turned into a request to an arbitrary URL.
     */
    public const PHOTO_HOSTS = [
        'static.inaturalist.org',
        'inaturalist-open-data.s3.amazonaws.com',
    ];

    /**
     * The species' default iNaturalist photo, or null when there's no match or
     * no photo.
     *
     * @return array{photo_url: string, attribution: ?string, license: ?string, page_url: ?string, taxon_name: string}|null
     */
    public function forName(string $scientificName): ?array
    {
        $name = trim($scientificName);

        if ($name === '') {
            return null;
        }

        $response = Http::timeout(self::TIMEOUT)
            ->acceptJson()
            ->get(self::TAXA_ENDPOINT, [
                'q' => $name,
                'rank' => 'species',
                'per_page' => 1,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('iNaturalist request failed with status '.$response->status());
        }

        $taxon = $response->json('results.0');
        $photo = $taxon['default_photo'] ?? null;

        if ($taxon === null || $photo === null || empty($photo['medium_url'])) {
            return null;
        }

        return [
            'photo_url' => $photo['medium_url'],
            'attribution' => $photo['attribution'] ?? null,
            'license' => $photo['license_code'] ?? null,
            'page_url' => isset($taxon['id'])
                ? 'https://www.inaturalist.org/taxa/'.$taxon['id']
                : null,
            'taxon_name' => $taxon['name'] ?? $name,
        ];
    }

    /**
     * Whether a photo URL is on a known iNaturalist host — the SSRF guard the
     * proxy checks before fetching.
     */
    public function isAllowedPhotoUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host !== null && in_array($host, self::PHOTO_HOSTS, true);
    }
}
