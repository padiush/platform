<?php

namespace App\Notifications;

use App\Models\ProjectInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class InviteNotification extends Notification
{
    use Queueable;

    public function __construct(protected ProjectInvite $invite) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $invite = $this->invite;
        $invitedUser = $invite->invitedUser;

        $message = (new MailMessage)
            ->subject(__('emails.invite.subject'))
            ->greeting(__('emails.invite.greeting', [
                'name' => $invitedUser->name ?? $invite->invited_name,
            ]))
            ->line(__('emails.invite.introduction', [
                'inviter' => $invite->invitingUser->name,
                'project' => $invite->project->name,
            ]));

        // Someone who already has an account accepts from inside the app; a new
        // recipient needs the signed registration link, and some idea of what
        // they are being invited into.
        if ($invitedUser) {
            $message
                ->line(__('emails.invite.existing_explanation'))
                ->action(__('emails.invite.existing_action'), route('projects.index'));
        } else {
            $message
                ->line(__('emails.invite.what_is_padiush'))
                ->line(__('emails.invite.new_explanation'))
                ->action(__('emails.invite.new_action'), URL::temporarySignedRoute(
                    'register.project-invite',
                    $invite->expires_at,
                    ['invite' => $invite]
                ));
        }

        return $message
            ->line(__('emails.invite.expiration', [
                'date' => $invite->expires_at->isoFormat('LL'),
            ]))
            ->line(__('emails.invite.ignore'))
            ->salutation(__('emails.salutation'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $invite = $this->invite;

        return [
            'project' => $invite->project,
            'inviting_user' => $invite->invitingUser,
            ...$invite->invitedUser
                ? ['invited_user' => $invite->invitedUser]
                : [
                    'invited_name' => $invite->invited_name,
                    'invited_email' => $invite->invited_email,
                ],
        ];
    }
}
