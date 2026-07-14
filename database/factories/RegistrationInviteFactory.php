<?php

namespace Database\Factories;

use App\Models\RegistrationInvite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistrationInvite>
 */
class RegistrationInviteFactory extends Factory
{
    protected $model = RegistrationInvite::class;

    public function definition(): array
    {
        return [
            'inviting_user_id' => User::factory()->admin(),
            'invited_name' => fake()->name(),
            'invited_email' => fake()->unique()->safeEmail(),
            'expires_at' => now()->addDays(RegistrationInvite::EXPIRATION_DAYS),
        ];
    }
}
