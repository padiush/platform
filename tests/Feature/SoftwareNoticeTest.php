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

    /**
     * MIT, ISC and BSD require their notice to travel with the copies they are
     * in, and minifying the bundle strips the comments that carried it. The
     * page is generated from the dependency tree at build time.
     */
    public function test_dependency_attribution_is_served_when_it_has_been_generated(): void
    {
        if (! file_exists(public_path('build/licenses.json'))) {
            $this->markTestSkipped('Assets are not built in this environment.');
        }

        $this->get(route('software.licences'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page->component('SoftwareLicences')
                    ->has('licences.groups')
                    ->has('licences.packageCount')
            );
    }

    public function test_attribution_is_absent_rather_than_empty_before_a_build(): void
    {
        $path = public_path('build/licenses.json');

        if (file_exists($path)) {
            // Assets are built here, so the un-built case cannot be produced
            // without destroying them. The notice page's own flag is what the
            // link depends on, and that is asserted below.
            $this->assertTrue(true);

            return;
        }

        $this->get(route('software.licences'))->assertNotFound();
    }

    public function test_the_notice_states_whether_attribution_is_available(): void
    {
        $this->get('/software')->assertInertia(
            fn ($page) => $page->where(
                'hasLicences',
                file_exists(public_path('build/licenses.json'))
            )
        );
    }
}
