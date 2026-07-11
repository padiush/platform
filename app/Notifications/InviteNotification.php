<?php

namespace App\Notifications;

use App\Models\ProjectInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InviteNotification extends Notification
{
    use Queueable;

    protected ProjectInvite $invite;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(ProjectInvite $invite)
    {
        $this->invite = $invite;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        $project_invite = $this->invite;

        if ($project_invite->invitedUser) {
            return (new MailMessage)->subject('Has recibido una invitación a un proyecto en Padiush')->view('email.invite', [
                'project' => $project_invite->project,
                'inviting_user' => $project_invite->invitingUser,
                'invited_user' => $project_invite->invitedUser,
            ]);
        }

        return (new MailMessage)->subject('Has recibido una invitación a un proyecto en Padiush')->view('email.invite', [
            'project' => $project_invite->project,
            'inviting_user' => $project_invite->invitingUser,
            'invited_name' => $project_invite->invited_name,
            'invited_email' => $project_invite->invited_email,
        ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $project_invite = $this->invite;
        if ($project_invite->invitedUser) {
            return [
                'project' => $project_invite->project,
                'inviting_user' => $project_invite->invitingUser,
                'invited_user' => $project_invite->invitedUser,
            ];
        }

        return [
            'project' => $project_invite->project,
            'inviting_user' => $project_invite->invitingUser,
            'invited_name' => $project_invite->invited_name,
            'invited_email' => $project_invite->invited_email,
        ];
    }
}
