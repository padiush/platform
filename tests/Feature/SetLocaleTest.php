<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_interface_language_cookie_sets_the_request_locale()
    {
        $this->withUnencryptedCookie('i18next', 'pt')
            ->get(route('public.index'))
            ->assertOk();

        $this->assertSame('pt', app()->getLocale());
    }

    public function test_a_region_qualified_cookie_is_reduced_to_its_language()
    {
        // i18next writes values such as "pt-BR".
        $this->withUnencryptedCookie('i18next', 'pt-BR')
            ->get(route('public.index'))
            ->assertOk();

        $this->assertSame('pt', app()->getLocale());
    }

    public function test_an_unsupported_language_leaves_the_default_in_place()
    {
        $this->withUnencryptedCookie('i18next', 'de')
            ->get(route('public.index'))
            ->assertOk();

        $this->assertSame(config('app.locale'), app()->getLocale());
    }

    public function test_the_choice_is_remembered_on_the_account()
    {
        $user = User::factory()->create(['locale' => null]);

        $this->actingAs($user)
            ->withUnencryptedCookie('i18next', 'en')
            ->get(route('public.index'))
            ->assertOk();

        // Queued notifications run without a request, so the preference has to
        // outlive the cookie for their email to arrive in the right language.
        $this->assertSame('en', $user->fresh()->locale);
    }

    public function test_a_stored_preference_applies_without_a_cookie()
    {
        $user = User::factory()->create(['locale' => 'pt']);

        $this->actingAs($user)
            ->get(route('public.index'))
            ->assertOk();

        $this->assertSame('pt', app()->getLocale());
    }
}
