<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectCapability;
use App\Notifications\InviteNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

class ProjectInviteNotificationTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    public function test_inviting_a_user_sends_the_invite_notification()
    {
        Notification::fake();

        $project = Project::factory()->create();
        $user = $this->userWithCapability($project, 'manage_users');

        $response = $this->actingAs($user)->post(
            route('projects.accesses.invite', $project),
            [
                'name' => 'New Colleague',
                'email' => 'colleague@example.com',
                'capability_id' => ProjectCapability::first()->id,
            ]
        );

        $response->assertRedirect(route('projects.accesses', $project));
        $response->assertSessionHas('message', 'projects.invite_sent');

        Notification::assertSentOnDemand(
            InviteNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'colleague@example.com'
        );
    }
}
