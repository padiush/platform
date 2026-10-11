<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_emit_link_preview_metadata()
    {
        $response = $this->get(route('public.contact'));

        $response->assertOk();
        // Regression: these controller calls existed but were never rendered,
        // so every shared link previewed blank.
        $response->assertSee('<meta property="og:title"', false);
        $response->assertSee('<meta property="og:description"', false);
        $response->assertSee('<meta name="description"', false);
        $response->assertSee('rel="canonical"', false);
    }

    public function test_public_pages_carry_a_link_preview_image()
    {
        $response = $this->get(route('public.contact'));

        $response->assertOk();
        // A crawler fetches this long after reading the page and caches the
        // result, so it has to be an absolute URL to a static file.
        $response->assertSee(
            '<meta property="og:image" content="'.config('app.url').'/images/site/og-card.png">',
            false
        );
        // Without an explicit card type X renders a small square thumbnail
        // even when og:image is present.
        $response->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
        $response->assertSee('<meta name="twitter:image"', false);
    }

    public function test_the_link_preview_image_exists_and_is_a_png()
    {
        $file = public_path('images/site/og-card.png');

        $this->assertFileExists($file);

        // WhatsApp and some LinkedIn paths refuse WebP, and drop previews that
        // are too heavy — both fail silently, so assert the shape here.
        [$width, $height, $type] = getimagesize($file);

        $this->assertSame(IMAGETYPE_PNG, $type);
        $this->assertSame(1200, $width);
        $this->assertSame(630, $height);
        $this->assertLessThan(300 * 1024, filesize($file));
    }

    public function test_pinch_zoom_is_not_blocked()
    {
        $response = $this->get(route('public.contact'));

        $response->assertOk();
        $response->assertDontSee('maximum-scale', false);
    }

    public function test_the_landing_and_about_pages_are_not_part_of_the_application()
    {
        // They are a separate site (PUBLIC_SITE_URL), served in front of the
        // application; the root only sends visitors in.
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/acerca')->assertNotFound();
    }

    public function test_pages_link_back_to_the_deployments_own_site()
    {
        config(['padiush.public_site_url' => 'https://example.org']);

        $this->get(route('public.contact'))->assertInertia(
            fn (Assert $page) => $page->where('publicSiteUrl', 'https://example.org')
        );
    }

    public function test_pages_share_the_field_apps_store_listings()
    {
        config(['padiush.companion_stores' => [
            'app_store' => 'https://apps.example/1',
            'play_store' => null,
            'play_store_coming_soon' => true,
        ]]);

        $this->get(route('public.contact'))->assertInertia(
            fn (Assert $page) => $page
                ->where('companionStores.app_store', 'https://apps.example/1')
                ->where('companionStores.play_store', null)
                ->where('companionStores.play_store_coming_soon', true)
        );
    }

    public function test_pages_offer_no_way_back_to_a_site_that_does_not_exist()
    {
        config(['padiush.public_site_url' => null]);

        $this->get(route('login'))->assertInertia(
            fn (Assert $page) => $page->where('publicSiteUrl', null)
        );
    }

    public function test_contact_page_renders_with_honeypot_fields()
    {
        $response = $this->get(route('public.contact'));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Public/Contact')
                // Without these props the form can't render the fields the
                // honeypot middleware requires, and every real submission
                // gets silently swallowed as spam.
                ->has('honeypot.nameFieldName')
                ->has('honeypot.validFromFieldName')
                ->has('honeypot.encryptedValidFrom')
        );
    }

    /**
     * The legal documents are deployment configuration and the repository
     * ships none, so these pages 404 on a clean checkout. Pointing at a
     * fixture exercises the rendering either way, rather than passing only on
     * a machine that happens to have documents installed.
     */
    private function installLegalDocument(): string
    {
        $dir = public_path('locales/legal-pages-fixture');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($dir.'/es.json', '{}');

        config(['padiush.legal_documents_path' => 'locales/legal-pages-fixture']);

        return $dir;
    }

    private function removeLegalDocument(string $dir): void
    {
        @unlink($dir.'/es.json');
        @rmdir($dir);
    }

    public function test_privacy_page_renders()
    {
        $dir = $this->installLegalDocument();

        try {
            $response = $this->get(route('public.privacy'));

            $response->assertOk();
            // The document itself lives in the `legal` translation namespace so
            // it follows the language toggle; the server only picks the page.
            $response->assertInertia(
                fn (Assert $page) => $page->component('Public/Privacy')->missing('pageContent')
            );
        } finally {
            $this->removeLegalDocument($dir);
        }
    }

    public function test_terms_page_renders()
    {
        $dir = $this->installLegalDocument();

        try {
            $response = $this->get(route('public.terms'));

            $response->assertOk();
            $response->assertInertia(
                fn (Assert $page) => $page->component('Public/Terms')->missing('pageContent')
            );
        } finally {
            $this->removeLegalDocument($dir);
        }
    }
}
