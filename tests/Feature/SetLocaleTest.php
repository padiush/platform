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
            ->get(route('software.notice'))
            ->assertOk();

        $this->assertSame('pt', app()->getLocale());
    }

    public function test_a_region_qualified_cookie_is_reduced_to_its_language()
    {
        // i18next writes values such as "pt-BR".
        $this->withUnencryptedCookie('i18next', 'pt-BR')
            ->get(route('software.notice'))
            ->assertOk();

        $this->assertSame('pt', app()->getLocale());
    }

    public function test_an_unsupported_language_leaves_the_default_in_place()
    {
        $this->withUnencryptedCookie('i18next', 'de')
            ->get(route('software.notice'))
            ->assertOk();

        $this->assertSame(config('app.locale'), app()->getLocale());
    }

    public function test_the_choice_is_remembered_on_the_account()
    {
        $user = User::factory()->create(['locale' => null]);

        $this->actingAs($user)
            ->withUnencryptedCookie('i18next', 'en')
            ->get(route('software.notice'))
            ->assertOk();

        // Queued notifications run without a request, so the preference has to
        // outlive the cookie for their email to arrive in the right language.
        $this->assertSame('en', $user->fresh()->locale);
    }

    public function test_a_stored_preference_applies_without_a_cookie()
    {
        $user = User::factory()->create(['locale' => 'pt']);

        $this->actingAs($user)
            ->get(route('software.notice'))
            ->assertOk();

        $this->assertSame('pt', app()->getLocale());
    }

    public function test_a_stored_preference_wins_over_the_cookie()
    {
        // i18next writes the cookie from the browser's own language as
        // readily as from a choice, so it must not override the account.
        $user = User::factory()->create(['locale' => 'pt']);

        $this->actingAs($user)
            ->withUnencryptedCookie('i18next', 'en')
            ->get(route('software.notice'))
            ->assertOk();

        $this->assertSame('pt', app()->getLocale());
        $this->assertSame('pt', $user->fresh()->locale);
    }

    public function test_choosing_a_language_saves_it_on_the_account()
    {
        $user = User::factory()->create(['locale' => 'es']);

        $this->actingAs($user)
            ->putJson(route('account.language'), ['locale' => 'en'])
            ->assertNoContent();

        $this->assertSame('en', $user->fresh()->locale);
    }

    public function test_only_the_supported_languages_can_be_chosen()
    {
        $user = User::factory()->create(['locale' => 'es']);

        $this->actingAs($user)
            ->putJson(route('account.language'), ['locale' => 'de'])
            ->assertUnprocessable();

        $this->assertSame('es', $user->fresh()->locale);
    }

    public function test_guests_have_no_account_to_save_a_language_on()
    {
        $this->putJson(route('account.language'), ['locale' => 'en'])
            ->assertUnauthorized();
    }

    public function test_the_signed_in_pages_carry_the_preference_to_the_interface()
    {
        $user = User::factory()->create(['locale' => 'pt']);

        $this->actingAs($user)
            ->get(route('account.show'))
            ->assertInertia(fn ($page) => $page->where('auth.user.locale', 'pt'));
    }
}
