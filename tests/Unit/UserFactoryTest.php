<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

/**
 * users.email is unique, so a repeated address makes any test that creates two
 * users fail at random.
 */
class UserFactoryTest extends TestCase
{
    public function test_every_user_it_makes_has_an_email_of_their_own(): void
    {
        $emails = User::factory()->count(5000)->make()->pluck('email');

        $this->assertCount(5000, $emails->unique());
    }
}
