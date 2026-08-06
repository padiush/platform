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

    public function test_privacy_page_renders()
    {
        $response = $this->get(route('public.privacy'));

        $response->assertOk();
        // The document itself lives in the `legal` translation namespace so it
        // follows the language toggle; the server only picks the page.
        $response->assertInertia(
            fn (Assert $page) => $page->component('Public/Privacy')->missing('pageContent')
        );
    }

    public function test_terms_page_renders()
    {
        $response = $this->get(route('public.terms'));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page->component('Public/Terms')->missing('pageContent')
        );
    }
}
