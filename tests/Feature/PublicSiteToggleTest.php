<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The public pages describe one operator and make legal claims on their behalf.
 * A deployment that has not written its own must not serve ours, so the default
 * is off and the routes answer 404 until it opts in.
 */
class PublicSiteToggleTest extends TestCase
{
    use RefreshDatabase;

    private function enablePublicSite(bool $enabled): void
    {
        config(['padiush.public_site_enabled' => $enabled]);
    }

    public function test_the_marketing_pages_are_hidden_by_default(): void
    {
        $this->enablePublicSite(false);

        foreach (['/acerca', '/contacto', '/privacidad', '/terminos'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }

    public function test_the_root_sends_a_visitor_to_sign_in_rather_than_a_404(): void
    {
        $this->enablePublicSite(false);

        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_the_root_sends_a_signed_in_visitor_to_the_dashboard(): void
    {
        $this->enablePublicSite(false);

        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    public function test_the_marketing_pages_are_served_once_enabled(): void
    {
        $this->enablePublicSite(true);

        $this->get('/')->assertOk();
        $this->get('/acerca')->assertOk();
        $this->get('/contacto')->assertOk();
    }

    public function test_legal_pages_stay_hidden_until_documents_are_published(): void
    {
        $this->enablePublicSite(true);

        // Point at a directory holding no documents — the state a fork is in,
        // whether or not this working copy has its own installed.
        config(['padiush.legal_documents_path' => 'locales/legal-absent']);

        $this->get('/privacidad')->assertNotFound();
        $this->get('/terminos')->assertNotFound();
    }

    public function test_legal_pages_are_served_once_documents_exist(): void
    {
        $this->enablePublicSite(true);

        $dir = public_path('locales/legal-test-fixture');
        mkdir($dir, 0755, true);
        file_put_contents($dir.'/es.json', '{}');
        config(['padiush.legal_documents_path' => 'locales/legal-test-fixture']);

        try {
            $this->get('/privacidad')->assertOk();
            $this->get('/terminos')->assertOk();
        } finally {
            unlink($dir.'/es.json');
            rmdir($dir);
        }
    }
}
