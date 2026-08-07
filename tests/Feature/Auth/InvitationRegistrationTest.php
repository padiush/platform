<?php

namespace Tests\Feature\Auth;

use App\Models\Project;
use App\Models\ProjectAccess;
use App\Models\ProjectCapability;
use App\Models\ProjectInvite;
use App\Models\RegistrationInvite;
use App\Models\User;
use App\Notifications\InviteNotification;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Honeypot\Honeypot;
use Tests\TestCase;

class InvitationRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_invitee_can_register_when_public_registration_is_disabled(): void
    {
        config()->set('padiush.registration_enabled', false);
        $invite = RegistrationInvite::factory()->create([
            'invited_name' => 'Beta Tester',
            'invited_email' => 'tester@example.com',
        ]);

        $this->get($this->platformUrl($invite, 'register.platform-invite'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Register')
                ->where('invitation.name', 'Beta Tester')
                ->where('invitation.email', 'tester@example.com')
                ->where('registrationUrl', fn ($url) => str_contains(
                    $url,
                    "/register/platform-invite/{$invite->id}"
                )));

        $response = $this->post(
            $this->platformUrl($invite, 'register.platform-invite.store'),
            $this->registrationPayload('attacker@example.com')
        );

        $response->assertRedirect(RouteServiceProvider::HOME);
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'tester@example.com',
            'system_admin' => false,
        ]);
        $this->assertDatabaseMissing('users', ['email' => 'attacker@example.com']);
        $this->assertDatabaseMissing('registration_invites', ['id' => $invite->id]);
    }

    public function test_unsigned_platform_registration_link_is_rejected(): void
    {
        $invite = RegistrationInvite::factory()->create();

        $this->get(route('register.platform-invite', $invite))->assertForbidden();
    }

    public function test_project_invitee_can_register_and_receives_project_access(): void
    {
        config()->set('padiush.registration_enabled', false);
        $manager = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $manager->id]);
        $managerCapability = ProjectCapability::where('manage_project', true)->first();
        $memberCapability = ProjectCapability::where('manage_project', false)->first();
        ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $manager->id,
            'project_capability_id' => $managerCapability->id,
        ]);
        $invite = ProjectInvite::create([
            'project_id' => $project->id,
            'inviting_user_id' => $manager->id,
            'invited_name' => 'Project Tester',
            'invited_email' => 'project@example.com',
            'project_capability_id' => $memberCapability->id,
            'expires_at' => now()->addDays(7),
        ]);

        // Sent to an address with no account, so the notifiable is anonymous;
        // the signed link now rides on the mail's action rather than view data.
        $mail = (new InviteNotification($invite))->toMail(new AnonymousNotifiable);

        $this->get($mail->actionUrl)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Register')
                ->where('invitation.email', 'project@example.com'));

        $response = $this->post(
            $this->projectUrl($invite, 'register.project-invite.store'),
            $this->registrationPayload('changed@example.com')
        );

        $user = User::where('email', 'project@example.com')->firstOrFail();
        $response->assertRedirect(RouteServiceProvider::HOME);
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('project_accesses', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => $memberCapability->id,
        ]);
        $this->assertDatabaseMissing('project_invites', ['id' => $invite->id]);
        $this->assertDatabaseMissing('users', ['email' => 'changed@example.com']);
    }

    public function test_expired_project_invitation_cannot_register(): void
    {
        $invite = ProjectInvite::factory()->create([
            'invited_user_id' => null,
            'invited_name' => 'Expired User',
            'invited_email' => 'expired@example.com',
            'expires_at' => now()->subMinute(),
        ]);

        $this->get($this->projectUrl($invite, 'register.project-invite'))
            ->assertForbidden();
    }

    private function registrationPayload(string $email): array
    {
        config()->set('honeypot.randomize_name_field_name', false);
        config()->set('honeypot.valid_from_timestamp', false);
        $honeypot = new Honeypot(config('honeypot'));

        return [
            'name' => 'Invited User',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
            $honeypot->unrandomizedNameFieldName() => '',
            $honeypot->validFromFieldName() => $honeypot->encryptedValidFrom(),
        ];
    }

    private function platformUrl(RegistrationInvite $invite, string $route): string
    {
        return URL::temporarySignedRoute(
            $route,
            $invite->expires_at,
            ['invite' => $invite]
        );
    }

    private function projectUrl(ProjectInvite $invite, string $route): string
    {
        return URL::temporarySignedRoute(
            $route,
            $invite->expires_at,
            ['invite' => $invite]
        );
    }
}
