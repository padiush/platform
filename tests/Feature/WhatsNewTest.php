<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * "What's new": which release each user last saw, and the notes of the
 * releases since (docs/releasing.md).
 */
class WhatsNewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.version' => '1.1.0']);
    }

    public function test_the_running_version_comes_from_package_json(): void
    {
        $this->refreshApplication();

        $package = json_decode(file_get_contents(base_path('package.json')), true);

        $this->assertSame($package['version'], config('app.version'));
    }

    public function test_a_user_who_has_not_seen_this_release_is_told_since_when(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['last_seen_version' => '1.0.0'])->save();

        $this->actingAs($user)->get(route('account.show'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('release.version', '1.1.0')
                ->where('release.unseenSince', '1.0.0'));
    }

    public function test_a_user_who_has_seen_it_is_not(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['last_seen_version' => '1.1.0'])->save();

        $this->actingAs($user)->get(route('account.show'))
            ->assertInertia(fn (Assert $page) => $page->where('release.unseenSince', null));
    }

    /** A new account has nothing to catch up on; the tour is for them instead. */
    public function test_a_new_account_starts_on_the_running_release(): void
    {
        $user = User::factory()->create();

        $this->assertSame('1.1.0', $user->fresh()->last_seen_version);
        $this->assertFalse($user->hasUnseenRelease());
    }

    public function test_versions_compare_as_numbers(): void
    {
        config(['app.version' => '1.10.0']);
        $user = User::factory()->create();
        $user->forceFill(['last_seen_version' => '1.9.0'])->save();

        $this->assertTrue($user->hasUnseenRelease());
    }

    public function test_dismissing_the_notes_marks_the_release_seen(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['last_seen_version' => '1.0.0'])->save();

        $this->actingAs($user)
            ->from(route('account.show'))
            ->post(route('whats-new.seen'))
            ->assertRedirect(route('account.show'));

        $this->assertSame('1.1.0', $user->fresh()->last_seen_version);
    }

    public function test_the_notes_have_a_page_of_their_own(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('whats-new'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('WhatsNew/Index'));
    }

    /** The page lists the same notes, so the dialog over it would only repeat them. */
    public function test_reading_the_page_counts_as_seeing_the_notes(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['last_seen_version' => '1.0.0'])->save();

        $this->actingAs($user)->get(route('whats-new'))
            ->assertInertia(fn (Assert $page) => $page->where('release.unseenSince', null));

        $this->assertSame('1.1.0', $user->fresh()->last_seen_version);
    }

    public function test_guests_are_sent_to_sign_in(): void
    {
        $this->get(route('whats-new'))->assertRedirect(route('login'));
        $this->post(route('whats-new.seen'))->assertRedirect(route('login'));
    }
}
