<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BrowserSessions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Mi cuenta: the browsers a user is signed in on and the devices running the
 * field app, each of which they can sign out — and nobody else's.
 */
class AccountTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /** The id of the session the test requests run in. */
    private string $current;

    protected function setUp(): void
    {
        parent::setUp();

        config(['session.driver' => 'database']);

        $this->user = User::factory()->create(['password' => bcrypt('secreta-larga')]);
        $this->current = $this->storedSession($this->user, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15');
    }

    /** A stored session for a user, as a browser of theirs would have left it. */
    private function storedSession(User $user, string $agent, int $minutesAgo = 0): string
    {
        $id = Str::random(40);

        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '203.0.113.7',
            'user_agent' => $agent,
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->subMinutes($minutesAgo)->timestamp,
        ]);

        return $id;
    }

    private function key(string $id): string
    {
        return hash_hmac('sha256', $id, (string) config('app.key'));
    }

    /** Requests made from the browser whose session is $this->current. */
    private function fromThisBrowser()
    {
        return $this->actingAs($this->user)->withCookie(config('session.cookie'), $this->current);
    }

    // --------------------------------------------------------- the page ---

    public function test_a_guest_is_sent_to_sign_in()
    {
        $this->get(route('account.show'))->assertRedirect(route('login'));
    }

    public function test_the_page_lists_the_users_browsers_and_marks_this_one()
    {
        $this->storedSession($this->user, 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0 Mobile Safari/537.36', 90);
        $this->storedSession(User::factory()->create(), 'Mozilla/5.0 (Windows NT 10.0) Firefox/131.0');

        $this->fromThisBrowser()
            ->get(route('account.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Show')
                ->where('account.email', $this->user->email)
                ->has('sessions', 2)
                ->where('sessions.0.current', true)
                ->where('sessions.0.browser', 'Safari')
                ->where('sessions.0.platform', 'macOS')
                ->where('sessions.1.current', false)
                ->where('sessions.1.browser', 'Chrome')
                ->where('sessions.1.platform', 'Android')
                ->where('sessions.1.ip_address', '203.0.113.7')
            );
    }

    /** A session id signs a browser in; the page names each by a key instead. */
    public function test_the_page_never_carries_a_session_id()
    {
        $other = $this->storedSession($this->user, 'Firefox/131.0', 5);

        $response = $this->fromThisBrowser()->get(route('account.show'));

        $this->assertStringNotContainsString($other, $response->getContent());
        $this->assertStringNotContainsString($this->current, $response->getContent());
    }

    public function test_without_database_sessions_the_page_says_they_cannot_be_listed()
    {
        config(['session.driver' => 'array']);

        $this->actingAs($this->user)
            ->get(route('account.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('sessions', null));
    }

    // ------------------------------------------------- signing one out ---

    public function test_another_browser_can_be_signed_out()
    {
        $other = $this->storedSession($this->user, 'Firefox/131.0', 30);
        $remembered = $this->user->fresh()->remember_token;

        $this->fromThisBrowser()
            ->delete(route('account.sessions.destroy', $this->key($other)))
            ->assertSessionHas('message', 'account.sessions.signed_out');

        $this->assertDatabaseMissing('sessions', ['id' => $other]);
        // Its "remember me" cookie no longer signs it back in.
        $this->assertNotSame($remembered, $this->user->fresh()->remember_token);
    }

    public function test_this_browser_is_not_signed_out_from_the_list()
    {
        $this->fromThisBrowser()
            ->delete(route('account.sessions.destroy', $this->key($this->current)))
            ->assertSessionHas('message', 'account.sessions.not_found');

        $this->assertAuthenticatedAs($this->user);
    }

    public function test_someone_elses_browser_cannot_be_signed_out()
    {
        $theirs = $this->storedSession(User::factory()->create(), 'Firefox/131.0');

        $this->fromThisBrowser()
            ->delete(route('account.sessions.destroy', $this->key($theirs)))
            ->assertSessionHas('message', 'account.sessions.not_found');

        $this->assertDatabaseHas('sessions', ['id' => $theirs]);
    }

    // ---------------------------------------------- signing others out ---

    public function test_signing_out_every_other_browser_asks_for_the_password()
    {
        $other = $this->storedSession($this->user, 'Firefox/131.0', 30);

        $this->fromThisBrowser()
            ->delete(route('account.sessions.destroy-others'), ['password' => 'equivocada'])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseHas('sessions', ['id' => $other]);
    }

    public function test_every_other_browser_is_signed_out_but_this_one_and_nobody_elses()
    {
        $mine = [
            $this->storedSession($this->user, 'Firefox/131.0', 30),
            $this->storedSession($this->user, 'Chrome/129.0', 300),
        ];
        $theirs = $this->storedSession(User::factory()->create(), 'Firefox/131.0');
        $remembered = $this->user->fresh()->remember_token;

        $this->fromThisBrowser()
            ->delete(route('account.sessions.destroy-others'), ['password' => 'secreta-larga'])
            ->assertSessionHas('message', 'account.sessions.others_signed_out');

        foreach ($mine as $id) {
            $this->assertDatabaseMissing('sessions', ['id' => $id]);
        }
        $this->assertDatabaseHas('sessions', ['id' => $theirs]);
        $this->assertAuthenticatedAs($this->user);
        $this->assertNotSame($remembered, $this->user->fresh()->remember_token);
    }

    /** This browser, if it was remembered, stays remembered under the new token. */
    public function test_a_remembered_browser_keeps_being_remembered()
    {
        $this->user->forceFill(['remember_token' => 'antiguo'])->save();
        $recaller = auth()->guard('web')->getRecallerName();

        $response = $this->fromThisBrowser()
            ->withCookie($recaller, $this->user->id.'|antiguo|'.$this->user->password)
            ->delete(route('account.sessions.destroy-others'), ['password' => 'secreta-larga']);

        $token = $this->user->fresh()->remember_token;
        $this->assertNotSame('antiguo', $token);
        $this->assertStringStartsWith(
            $this->user->id.'|'.$token.'|',
            $response->getCookie($recaller)->getValue()
        );
    }

    // --------------------------------------------------------- devices ---

    public function test_the_page_lists_the_field_app_devices_by_name()
    {
        $this->user->createToken('Galaxy Z Fold 5', ['capture']);
        User::factory()->create()->createToken('Someone else’s phone', ['capture']);

        $this->actingAs($this->user)
            ->get(route('account.show'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('devices', 1)
                ->where('devices.0.name', 'Galaxy Z Fold 5')
                ->where('devices.0.last_used_at', null)
            );
    }

    public function test_a_lost_device_can_be_revoked()
    {
        $token = $this->user->createToken('iPhone', ['capture']);

        $this->actingAs($this->user)
            ->delete(route('account.devices.destroy', $token->accessToken->id))
            ->assertSessionHas('message', 'account.devices.revoked');

        $this->assertSame(0, $this->user->tokens()->count());

        // The device's next request is refused.
        $this->app['auth']->forgetGuards();
        $this->withToken($token->plainTextToken)->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_someone_elses_device_cannot_be_revoked()
    {
        $theirs = User::factory()->create()->createToken('Their phone', ['capture']);

        $this->actingAs($this->user)
            ->delete(route('account.devices.destroy', $theirs->accessToken->id))
            ->assertSessionHas('message', 'account.devices.not_found');

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $theirs->accessToken->id]);
    }

    // ------------------------------------------------------ describing ---

    public function test_a_browser_is_named_by_what_its_user_agent_says()
    {
        $sessions = app(BrowserSessions::class);

        $this->assertSame(
            ['browser' => 'Edge', 'platform' => 'Windows'],
            $sessions->describe('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0 Safari/537.36 Edg/129.0')
        );
        $this->assertSame(
            ['browser' => 'Safari', 'platform' => 'iOS'],
            $sessions->describe('Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1')
        );
        $this->assertSame(
            ['browser' => 'Firefox', 'platform' => 'Linux'],
            $sessions->describe('Mozilla/5.0 (X11; Linux x86_64; rv:131.0) Gecko/20100101 Firefox/131.0')
        );
        $this->assertSame(['browser' => null, 'platform' => null], $sessions->describe('curl/8.10'));
    }
}
