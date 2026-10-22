<?php

namespace Tests\Feature\Auth;

use App\Models\Project;
use App\Models\ProjectAccess;
use App\Models\ProjectCapability;
use App\Models\ProjectInvite;
use App\Models\RegistrationInvite;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Spatie\Honeypot\Honeypot;
use Tests\TestCase;

/**
 * An address is only someone's once they have shown they receive mail there.
 * Until then an account can do nothing, and least of all take up a project
 * invitation that was sent to that address.
 */
class EmailAddressConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_registration_starts_unconfirmed_and_sends_a_confirmation_link(): void
    {
        config()->set('padiush.registration_enabled', true);
        Notification::fake();

        $this->post(route('register'), $this->registrationPayload('new@example.org'));

        $user = User::where('email', 'new@example.org')->firstOrFail();
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_an_invitation_waits_until_the_address_is_confirmed(): void
    {
        config()->set('padiush.registration_enabled', true);
        Notification::fake();
        $invite = $this->projectInviteFor('invited@example.org');

        // Whoever types an invited address first does not get the project.
        $this->post(route('register'), $this->registrationPayload('invited@example.org'));
        $user = User::where('email', 'invited@example.org')->firstOrFail();

        $this->assertDatabaseMissing('project_accesses', [
            'project_id' => $invite->project_id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('project_invites', ['id' => $invite->id]);

        // Following the link sent to the address is what proves it.
        $this->actingAs($user)->get($this->confirmationUrl($user));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertDatabaseHas('project_accesses', [
            'project_id' => $invite->project_id,
            'user_id' => $user->id,
            'project_capability_id' => $invite->project_capability_id,
        ]);
        $this->assertDatabaseMissing('project_invites', ['id' => $invite->id]);
    }

    public function test_an_account_made_from_a_project_invitation_is_confirmed_at_once(): void
    {
        Notification::fake();
        $invite = $this->projectInviteFor('invited@example.org');

        $this->post(
            $this->signed('register.project-invite.store', $invite),
            $this->registrationPayload('ignored@example.org')
        );

        $user = User::where('email', 'invited@example.org')->firstOrFail();
        $this->assertTrue($user->hasVerifiedEmail());
        Notification::assertNotSentTo($user, VerifyEmail::class);
        $this->assertDatabaseHas('project_accesses', [
            'project_id' => $invite->project_id,
            'user_id' => $user->id,
        ]);
    }

    public function test_an_account_made_from_a_platform_invitation_is_confirmed_at_once(): void
    {
        Notification::fake();
        $invite = RegistrationInvite::factory()->create(['invited_email' => 'tester@example.org']);

        $this->post(
            $this->signed('register.platform-invite.store', $invite),
            $this->registrationPayload('ignored@example.org')
        );

        $user = User::where('email', 'tester@example.org')->firstOrFail();
        $this->assertTrue($user->hasVerifiedEmail());
        Notification::assertNotSentTo($user, VerifyEmail::class);
    }

    public function test_an_unconfirmed_account_is_sent_to_confirm_its_address(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get(route('account.show'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_an_unconfirmed_account_cannot_take_up_an_invitation(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'invited@example.org']);
        $invite = $this->projectInviteFor('invited@example.org');
        $invite->update(['invited_user_id' => $user->id]);

        $this->actingAs($user)->get(route('projects.invites.accept', $invite))
            ->assertRedirect(route('verification.notice'));

        $this->assertDatabaseMissing('project_accesses', [
            'project_id' => $invite->project_id,
            'user_id' => $user->id,
        ]);
    }

    /** The confirmation page has the language menu, and the email follows the choice. */
    public function test_an_unconfirmed_account_can_still_choose_its_language(): void
    {
        $user = User::factory()->unverified()->create(['locale' => 'es']);

        $this->actingAs($user)
            ->putJson(route('account.language'), ['locale' => 'pt'])
            ->assertSuccessful();

        $this->assertSame('pt', $user->fresh()->locale);
    }

    public function test_the_app_is_not_given_a_token_for_an_unconfirmed_account(): void
    {
        User::factory()->unverified()->create(['email' => 'field@example.org']);

        $this->postJson('/api/v1/tokens', [
            'email' => 'field@example.org',
            'password' => 'password',
            'device_name' => 'Pixel 8',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'api.tokens.email_unverified');

        // With the wrong password it is the usual refusal, so nothing is
        // learned about the address.
        $this->postJson('/api/v1/tokens', [
            'email' => 'field@example.org',
            'password' => 'wrong-password',
            'device_name' => 'Pixel 8',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'api.tokens.invalid_credentials');
    }

    public function test_accounts_that_already_exist_are_counted_as_confirmed(): void
    {
        $existing = User::factory()->unverified()->create();
        $confirmed = User::factory()->create(['email_verified_at' => '2026-01-01 00:00:00']);

        $migration = require database_path('migrations/2026_10_22_000000_confirm_existing_email_addresses.php');
        $migration->up();

        $this->assertTrue($existing->fresh()->hasVerifiedEmail());
        $this->assertSame(
            '2026-01-01 00:00:00',
            (string) DB::table('users')->where('id', $confirmed->id)->value('email_verified_at')
        );
    }

    private function projectInviteFor(string $email): ProjectInvite
    {
        $manager = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $manager->id]);
        ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $manager->id,
            'project_capability_id' => ProjectCapability::where('manage_project', true)->first()->id,
        ]);

        return ProjectInvite::create([
            'project_id' => $project->id,
            'inviting_user_id' => $manager->id,
            'invited_name' => 'Invited',
            'invited_email' => $email,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
            'expires_at' => now()->addDays(7),
        ]);
    }

    private function confirmationUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );
    }

    private function signed(string $route, ProjectInvite|RegistrationInvite $invite): string
    {
        return URL::temporarySignedRoute($route, $invite->expires_at, ['invite' => $invite]);
    }

    private function registrationPayload(string $email): array
    {
        config()->set('honeypot.randomize_name_field_name', false);
        config()->set('honeypot.valid_from_timestamp', false);
        $honeypot = new Honeypot(config('honeypot'));

        return [
            'name' => 'New Researcher',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
            $honeypot->unrandomizedNameFieldName() => '',
            $honeypot->validFromFieldName() => $honeypot->encryptedValidFrom(),
        ];
    }
}
