<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_umami_tracker_renders_when_configured()
    {
        config([
            'padiush.analytics.umami_src' => 'https://analytics.example.test/script.js',
            'padiush.analytics.umami_website_id' => 'abc-123',
        ]);

        $response = $this->get(route('public.contact'));

        $response->assertOk();
        $response->assertSee('https://analytics.example.test/script.js', false);
        $response->assertSee('data-website-id="abc-123"', false);
        // Umami respects the browser's Do Not Track signal only when told to.
        $response->assertSee('data-do-not-track="true"', false);
    }

    public function test_umami_tracker_is_absent_without_a_website_id()
    {
        config([
            'padiush.analytics.umami_src' => 'https://analytics.example.test/script.js',
            'padiush.analytics.umami_website_id' => null,
        ]);

        $response = $this->get(route('public.contact'));

        $response->assertOk();
        $response->assertDontSee('analytics.example.test', false);
    }

    public function test_umami_tracker_is_absent_without_a_script_source()
    {
        config([
            'padiush.analytics.umami_src' => null,
            'padiush.analytics.umami_website_id' => 'abc-123',
        ]);

        $response = $this->get(route('public.contact'));

        $response->assertOk();
        $response->assertDontSee('data-website-id', false);
    }

    public function test_no_tracking_is_rendered_by_default()
    {
        $response = $this->get(route('public.contact'));

        $response->assertOk();
        $response->assertDontSee('data-website-id', false);
    }
}
