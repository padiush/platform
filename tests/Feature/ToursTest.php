<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** Which guided tours each user has been through. */
class ToursTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_user_has_been_through_no_tour(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('account.show'))
            ->assertInertia(fn (Assert $page) => $page->where('tours', []));
    }

    /** Skipped or finished, a tour does not start again — in any browser. */
    public function test_a_tour_once_done_is_remembered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('account.show'))
            ->post(route('tours.done', 'welcome'))
            ->assertRedirect(route('account.show'));
        $this->actingAs($user)->post(route('tours.done', 'welcome'));
        $this->actingAs($user)->post(route('tours.done', 'overview'));

        $this->assertSame(['welcome', 'overview'], $user->fresh()->completed_tours);

        $this->actingAs($user)
            ->get(route('account.show'))
            ->assertInertia(fn (Assert $page) => $page->where('tours', ['welcome', 'overview']));
    }

    public function test_only_tours_the_app_offers_can_be_marked(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tours.done', 'nonsense'))->assertNotFound();

        $this->assertNull($user->fresh()->completed_tours);
    }

    public function test_every_tour_can_be_offered_again(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['completed_tours' => ['welcome', 'forms']])->save();

        $this->actingAs($user)
            ->from(route('account.show'))
            ->delete(route('tours.reset'))
            ->assertRedirect(route('account.show'))
            ->assertSessionHas('message', 'account.tours.reset_done');

        $this->assertSame([], $user->fresh()->completed_tours);
    }

    public function test_guests_cannot_touch_tours(): void
    {
        $this->post(route('tours.done', 'welcome'))->assertRedirect(route('login'));
        $this->delete(route('tours.reset'))->assertRedirect(route('login'));
    }

    /** The two lists must agree, or a tour would be refused or never offered. */
    public function test_the_server_knows_every_tour_the_frontend_defines(): void
    {
        $source = file_get_contents(resource_path('js/tours/definitions.js'));
        preg_match_all('/^\\s{4}([a-z_]+): \\{/m', $source, $matches);

        $this->assertEqualsCanonicalizing(User::TOURS, $matches[1]);
    }
}
