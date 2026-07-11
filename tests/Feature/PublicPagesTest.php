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
        $response->assertInertia(
            fn (Assert $page) => $page->component('Public/Index')->has('images')
        );
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
        $response->assertInertia(
            fn (Assert $page) => $page->component('Public/Privacy')->has('pageContent')
        );
    }

    public function test_terms_page_renders()
    {
        $response = $this->get(route('public.terms'));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page->component('Public/Terms')->has('pageContent')
        );
    }
}
