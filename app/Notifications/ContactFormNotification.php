<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ContactFormNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $name,
        protected string $emailAddress,
        protected string $textMessage
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('emails.contact.subject'))
            ->greeting(__('emails.contact.greeting'))
            ->line(__('emails.contact.introduction', [
                'name' => $this->name,
                'email' => $this->emailAddress,
            ]))
            // The message is quoted in a panel so it reads as the sender's
            // words rather than ours, and so line breaks survive.
            ->line(new HtmlString(
                '<blockquote>'.nl2br(e($this->textMessage)).'</blockquote>'
            ))
            ->action(
                __('emails.contact.action', ['name' => $this->name]),
                'mailto:'.$this->emailAddress
            )
            ->line(__('emails.contact.received_at', [
                'date' => now()->isoFormat('LLL'),
            ]))
            ->salutation(__('emails.salutation'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'name' => $this->name,
            'email' => $this->emailAddress,
        ];
    }
}
