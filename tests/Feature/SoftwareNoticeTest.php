<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Section 13 of the AGPL entitles anyone using Padiush over a network to the
 * source of the version they are using. The offer therefore cannot depend on
 * being signed in, or on the deployment having a public site at all.
 */
class SoftwareNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_offer_of_source_survives_a_deployment_with_no_public_site(): void
    {
        config(['padiush.public_site_enabled' => false]);

        $this->get('/software')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('SoftwareNotice'));
    }

    public function test_a_guest_can_reach_it(): void
    {
        $this->get('/software')->assertOk();
    }

    public function test_a_signed_in_user_can_reach_it(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/software')
            ->assertOk();
    }

    public function test_it_offers_the_configured_source_location(): void
    {
        // An operator running modified code must be able to point this at their
        // own source; aiming it upstream would not satisfy the licence.
        config(['padiush.source_url' => 'https://example.org/our/fork']);

        $this->get('/software')->assertInertia(
            fn ($page) => $page->where('sourceUrl', 'https://example.org/our/fork')
        );
    }

    public function test_the_source_location_is_shared_with_every_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->has('sourceUrl'));
    }
}
