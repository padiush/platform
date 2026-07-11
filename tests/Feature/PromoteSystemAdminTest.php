<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoteSystemAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_promotes_an_existing_user()
    {
        $user = User::factory()->create(['system_admin' => false]);

        $this->artisan('user:promote', ['email' => $user->email])
            ->expectsOutputToContain('is now a system administrator')
            ->assertSuccessful();

        $this->assertTrue($user->fresh()->system_admin);
    }

    public function test_fails_for_an_unknown_email()
    {
        $this->artisan('user:promote', ['email' => 'nobody@example.com'])
            ->expectsOutputToContain('User not found')
            ->assertFailed();
    }
}
