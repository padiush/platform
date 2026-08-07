<?php

namespace Tests\Unit\Services;

use App\Services\INaturalistPhoto;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class INaturalistPhotoTest extends TestCase
{
    private function fakeTaxa(?array $taxon): void
    {
        Http::fake([
            'api.inaturalist.org/*' => Http::response([
                'results' => $taxon === null ? [] : [$taxon],
            ]),
        ]);
    }

    public function test_it_returns_the_default_photo_with_credit()
    {
        $this->fakeTaxa([
            'id' => 160255,
            'name' => 'Cecropia obtusifolia',
            'default_photo' => [
                'medium_url' => 'https://inaturalist-open-data.s3.amazonaws.com/photos/1/medium.jpg',
                'attribution' => '(c) Reinaldo Aguilar, some rights reserved (CC BY-NC-SA)',
                'license_code' => 'cc-by-nc-sa',
            ],
        ]);

        $photo = (new INaturalistPhoto)->forName('Cecropia obtusifolia');

        $this->assertSame('https://inaturalist-open-data.s3.amazonaws.com/photos/1/medium.jpg', $photo['photo_url']);
        $this->assertSame('(c) Reinaldo Aguilar, some rights reserved (CC BY-NC-SA)', $photo['attribution']);
        $this->assertSame('cc-by-nc-sa', $photo['license']);
        $this->assertSame('https://www.inaturalist.org/taxa/160255', $photo['page_url']);
    }

    public function test_it_returns_null_when_there_is_no_match_or_photo()
    {
        $this->fakeTaxa(null);
        $this->assertNull((new INaturalistPhoto)->forName('Nope nope'));

        $this->fakeTaxa(['id' => 1, 'name' => 'X', 'default_photo' => null]);
        $this->assertNull((new INaturalistPhoto)->forName('X'));
    }

    public function test_allowlist_accepts_inaturalist_hosts_and_rejects_others()
    {
        $inat = new INaturalistPhoto;

        $this->assertTrue($inat->isAllowedPhotoUrl('https://inaturalist-open-data.s3.amazonaws.com/photos/1/medium.jpg'));
        $this->assertTrue($inat->isAllowedPhotoUrl('https://static.inaturalist.org/photos/1/medium.jpg'));
        // SSRF guard: internal/other hosts are refused.
        $this->assertFalse($inat->isAllowedPhotoUrl('http://169.254.169.254/latest/meta-data/'));
        $this->assertFalse($inat->isAllowedPhotoUrl('https://evil.example.com/x.jpg'));
    }
}
