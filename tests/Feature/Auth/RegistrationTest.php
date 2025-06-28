<?php

namespace Tests\Feature\Auth;

use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register()
    {
        config()->set('honeypot.randomize_name_field_name', false);
        config()->set('honeypot.valid_from_timestamp', false);

        $honeypot = new \Spatie\Honeypot\Honeypot(config('honeypot'));

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            $honeypot->unrandomizedNameFieldName() => '',
            $honeypot->validFromFieldName() => $honeypot->encryptedValidFrom(),
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_registration_is_rejected_when_honeypot_filled()
    {
        config()->set('honeypot.randomize_name_field_name', false);
        config()->set('honeypot.valid_from_timestamp', false);

        $honeypot = new \Spatie\Honeypot\Honeypot(config('honeypot'));

        $response = $this->post('/register', [
            'name' => 'Spam User',
            'email' => 'spam@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            $honeypot->unrandomizedNameFieldName() => 'spam',
            $honeypot->validFromFieldName() => $honeypot->encryptedValidFrom(),
        ]);

        $this->assertGuest();
        $response->assertOk();
    }
}
