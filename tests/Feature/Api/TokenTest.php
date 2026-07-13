<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_credentials_issue_a_capture_token(): void
    {
        $user = User::factory()->create(['email' => 'field@example.org']);

        $response = $this->postJson('/api/v1/tokens', [
            'email' => 'field@example.org',
            'password' => 'password',
            'device_name' => 'Pixel 8',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']])
            ->assertJsonPath('user.email', 'field@example.org');

        $token = $user->tokens()->first();
        $this->assertNotNull($token);
        $this->assertSame('Pixel 8', $token->name);
        $this->assertSame(['capture'], $token->abilities);
    }

    public function test_wrong_password_is_rejected_without_enumeration(): void
    {
        User::factory()->create(['email' => 'field@example.org']);

        $this->postJson('/api/v1/tokens', [
            'email' => 'field@example.org',
            'password' => 'wrong-password',
            'device_name' => 'Pixel 8',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'api.tokens.invalid_credentials')
            ->assertJsonPath('message_type', 'error');
    }

    public function test_unknown_email_gives_the_same_error(): void
    {
        $this->postJson('/api/v1/tokens', [
            'email' => 'ghost@example.org',
            'password' => 'password',
            'device_name' => 'Pixel 8',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'api.tokens.invalid_credentials');
    }

    public function test_device_name_is_required(): void
    {
        $this->postJson('/api/v1/tokens', [
            'email' => 'x@example.org',
            'password' => 'password',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'api.validation_failed')
            ->assertJsonStructure(['errors' => ['device_name']]);
    }

    public function test_current_token_can_be_revoked(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('Pixel 8', ['capture'])->plainTextToken;

        $this->withToken($token)
            ->deleteJson('/api/v1/tokens/current')
            ->assertOk();

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_protected_route_requires_authentication(): void
    {
        $this->deleteJson('/api/v1/tokens/current')
            ->assertStatus(401)
            ->assertJsonPath('message', 'api.unauthenticated');
    }

    public function test_token_without_capture_ability_is_forbidden(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('Pixel 8', [])->plainTextToken;

        $this->withToken($token)
            ->deleteJson('/api/v1/tokens/current')
            ->assertStatus(403)
            ->assertJsonPath('message', 'api.forbidden');
    }
}
