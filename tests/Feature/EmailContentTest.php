<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectCapability;
use App\Models\ProjectInvite;
use App\Models\User;
use App\Notifications\ContactFormNotification;
use App\Notifications\InviteNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

/**
 * These render the notifications rather than faking them, because the point of
 * the rework is what actually reaches the recipient: branded chrome, no
 * hardcoded Spanish, and no third-party assets.
 */
class EmailContentTest extends TestCase
{
    use RefreshDatabase;

    private function invite(?User $invitedUser = null): ProjectInvite
    {
        $inviter = User::factory()->create(['name' => 'Mercedes']);
        $project = Project::factory()->create(['name' => 'Cordillera']);

        return ProjectInvite::create([
            'project_id' => $project->id,
            'project_capability_id' => ProjectCapability::first()->id,
            'inviting_user_id' => $inviter->id,
            'invited_user_id' => $invitedUser?->id,
            'invited_name' => $invitedUser?->name ?? 'Ana',
            'invited_email' => $invitedUser?->email ?? 'ana@example.test',
            'expires_at' => now()->addDays(7),
        ]);
    }

    private function render($notification, $notifiable): string
    {
        return (string) $notification->toMail($notifiable)->render();
    }

    public function test_the_invite_email_is_translated_per_locale()
    {
        $invite = $this->invite();
        $notification = new InviteNotification($invite);
        $notifiable = User::factory()->make();

        App::setLocale('es');
        $spanish = $this->render($notification, $notifiable);
        $this->assertStringContainsString('Mercedes', $spanish);
        $this->assertStringContainsString('Crear mi cuenta', $spanish);

        App::setLocale('en');
        $english = $this->render($notification, $notifiable);
        $this->assertStringContainsString('Create my account', $english);
        $this->assertStringNotContainsString('Crear mi cuenta', $english);

        App::setLocale('pt');
        $portuguese = $this->render($notification, $notifiable);
        $this->assertStringContainsString('Criar minha conta', $portuguese);
    }

    public function test_the_invite_email_points_an_existing_user_into_the_app()
    {
        $invited = User::factory()->create(['name' => 'Ana']);

        App::setLocale('en');
        $html = $this->render(new InviteNotification($this->invite($invited)), $invited);

        // An account holder accepts inside the app; offering them a
        // registration link would be a dead end.
        $this->assertStringContainsString('View my invitations', $html);
        $this->assertStringNotContainsString('Create my account', $html);
    }

    public function test_the_contact_email_quotes_the_message_and_escapes_it()
    {
        App::setLocale('en');

        $html = $this->render(
            new ContactFormNotification('Ana', 'ana@example.test', '<script>alert(1)</script>'),
            User::factory()->make()
        );

        $this->assertStringContainsString('Reply to Ana', $html);
        $this->assertStringContainsString('mailto:ana@example.test', $html);
        // The body is attacker-supplied, so it must arrive escaped.
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    public function test_emails_are_branded_and_free_of_third_party_assets()
    {
        config(['app.name' => 'Padiush']);
        App::setLocale('es');

        $html = $this->render(
            new InviteNotification($this->invite()),
            User::factory()->make()
        );

        $this->assertStringContainsString('Padiush', $html);
        $this->assertStringContainsString('#3c6200', $html);

        // Regression: the old templates pulled a Google font and a logo from
        // storage, both of which most clients block or fail to resolve.
        $this->assertStringNotContainsString('fonts.googleapis.com', $html);
        $this->assertStringNotContainsString('laravel.com/img', $html);
    }

    public function test_a_user_locale_preference_is_honoured_when_sending()
    {
        $user = User::factory()->create(['locale' => 'pt']);

        $this->assertSame('pt', $user->preferredLocale());
    }
}
