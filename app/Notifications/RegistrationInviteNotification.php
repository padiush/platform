<?php

namespace App\Notifications;

use App\Models\RegistrationInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class RegistrationInviteNotification extends Notification
{
    use Queueable;

    public function __construct(private RegistrationInvite $invite) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $registrationUrl = URL::temporarySignedRoute(
            'register.platform-invite',
            $this->invite->expires_at,
            ['invite' => $this->invite]
        );

        return (new MailMessage)
            ->subject(__('registration.platform.subject'))
            ->greeting(__('registration.platform.greeting', [
                'name' => $this->invite->invited_name,
            ]))
            ->line(__('registration.platform.introduction', [
                'inviter' => $this->invite->invitingUser->name,
            ]))
            ->line(__('registration.platform.explanation'))
            ->action(__('registration.platform.action'), $registrationUrl)
            ->line(__('registration.platform.expiration', [
                'days' => RegistrationInvite::EXPIRATION_DAYS,
            ]));
    }

    public function toArray($notifiable): array
    {
        return [
            'invited_email' => $this->invite->invited_email,
            'inviting_user_id' => $this->invite->inviting_user_id,
        ];
    }
}
