<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_renders()
    {
        $response = $this->get(route('public.index'));

        $response->assertOk();
        // The landing page draws its own visuals, so it deliberately carries no
        // image props — and therefore mints no presigned URLs on every visit.
        $response->assertInertia(
            fn (Assert $page) => $page->component('Public/Index')->missing('images')
        );
    }

    public function test_landing_page_exposes_the_registration_state_for_its_call_to_action()
    {
        config(['padiush.registration_enabled' => false]);

        $response = $this->get(route('public.index'));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page->where('registrationEnabled', false)
        );
    }

    public function test_public_pages_emit_link_preview_metadata()
    {
        $response = $this->get(route('public.index'));

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
        $response = $this->get(route('public.index'));

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
        $response = $this->get(route('public.index'));

        $response->assertOk();
        $response->assertDontSee('maximum-scale', false);
    }

    public function test_about_page_renders()
    {
        $response = $this->get(route('public.about'));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page->component('Public/About')->has('images')
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
