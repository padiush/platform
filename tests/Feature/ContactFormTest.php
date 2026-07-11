<?php

namespace Tests\Feature;

use App\Notifications\ContactFormNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Honeypot\Honeypot;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    private function honeypotFields(): array
    {
        config()->set('honeypot.randomize_name_field_name', false);
        config()->set('honeypot.valid_from_timestamp', false);

        $honeypot = new Honeypot(config('honeypot'));

        return [
            $honeypot->unrandomizedNameFieldName() => '',
            $honeypot->validFromFieldName() => $honeypot->encryptedValidFrom(),
        ];
    }

    public function test_contact_form_notifies_the_configured_address()
    {
        Notification::fake();
        config()->set('padiush.contact_email', 'contact@example.com');

        $response = $this->post(route('public.contact.handle'), [
            'name' => 'Visitor',
            'email' => 'visitor@example.com',
            'message' => 'Hola, tengo una pregunta.',
        ] + $this->honeypotFields());

        $response->assertRedirect(route('public.contact'));
        $response->assertSessionHas('message', 'public.contact_success');
        $response->assertSessionHas('message_type', 'success');

        Notification::assertSentOnDemand(
            ContactFormNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'contact@example.com'
        );
    }

    public function test_contact_form_is_rejected_when_honeypot_filled()
    {
        Notification::fake();
        config()->set('padiush.contact_email', 'contact@example.com');

        $fields = $this->honeypotFields();
        $honeypot = new Honeypot(config('honeypot'));
        $fields[$honeypot->unrandomizedNameFieldName()] = 'spam';

        $response = $this->post(route('public.contact.handle'), [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'message' => 'spam',
        ] + $fields);

        $response->assertOk();
        Notification::assertNothingSent();
    }

    public function test_contact_form_requires_all_fields()
    {
        Notification::fake();
        config()->set('padiush.contact_email', 'contact@example.com');

        $response = $this->post(route('public.contact.handle'), $this->honeypotFields());

        $response->assertSessionHasErrors(['name', 'email', 'message']);
        Notification::assertNothingSent();
    }
}
