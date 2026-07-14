<?php

namespace Tests\Feature;

use App\Models\RegistrationInvite;
use App\Models\User;
use App\Notifications\RegistrationInviteNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SystemRegistrationInviteTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_admin_can_send_a_platform_registration_invite(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(
            route('system.registration-invites.store'),
            ['name' => 'Beta Tester', 'email' => 'Tester@Example.com']
        );

        $response->assertRedirect(route('system.index'));
        $response->assertSessionHas('message', 'system.registration_invite_sent');
        $this->assertDatabaseHas('registration_invites', [
            'inviting_user_id' => $admin->id,
            'invited_name' => 'Beta Tester',
            'invited_email' => 'tester@example.com',
        ]);
        Notification::assertSentOnDemand(
            RegistrationInviteNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'tester@example.com'
        );
    }

    public function test_non_admin_cannot_send_a_platform_registration_invite(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(
            route('system.registration-invites.store'),
            ['name' => 'Beta Tester', 'email' => 'tester@example.com']
        );

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseCount('registration_invites', 0);
        Notification::assertNothingSent();
    }

    public function test_registered_email_cannot_receive_a_platform_invite(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $existing = User::factory()->create();

        $response = $this->actingAs($admin)->post(
            route('system.registration-invites.store'),
            ['name' => $existing->name, 'email' => $existing->email]
        );

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('registration_invites', 0);
        Notification::assertNothingSent();
    }

    public function test_resending_a_platform_invite_renews_the_existing_record(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $invite = RegistrationInvite::factory()->create([
            'inviting_user_id' => $admin->id,
            'invited_email' => 'tester@example.com',
            'expires_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)->post(
            route('system.registration-invites.store'),
            ['name' => 'Updated Name', 'email' => 'tester@example.com']
        );

        $this->assertDatabaseCount('registration_invites', 1);
        $this->assertDatabaseHas('registration_invites', [
            'id' => $invite->id,
            'invited_name' => 'Updated Name',
            'inviting_user_id' => $admin->id,
        ]);
        $this->assertTrue($invite->fresh()->expires_at->isFuture());
    }

    public function test_system_dashboard_lists_only_active_registration_invites(): void
    {
        $admin = User::factory()->admin()->create();
        $active = RegistrationInvite::factory()->create([
            'inviting_user_id' => $admin->id,
        ]);
        RegistrationInvite::factory()->create([
            'inviting_user_id' => $admin->id,
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($admin)
            ->get(route('system.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('System/Index')
                ->has('registration_invites', 1)
                ->where('registration_invites.0.id', $active->id));
    }
}
