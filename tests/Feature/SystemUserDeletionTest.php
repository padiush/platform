<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemUserDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_reach_user_deletion()
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        $response = $this->actingAs($user)->delete(
            route('system.users.delete', $target)
        );

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_admin_cannot_delete_their_own_account()
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->delete(
            route('system.users.delete', $admin)
        );

        $response->assertRedirect(route('system.index'));
        $response->assertSessionHas('message', 'system.cannot_delete_self');
        $response->assertSessionHas('message_type', 'error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_delete_a_regular_user()
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $response = $this->actingAs($admin)->delete(
            route('system.users.delete', $target)
        );

        $response->assertRedirect(route('system.index'));
        $response->assertSessionHas('message', 'system.user_deleted');
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_admin_can_delete_another_admin_when_others_remain()
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->delete(
            route('system.users.delete', $otherAdmin)
        );

        $response->assertSessionHas('message', 'system.user_deleted');
        $this->assertDatabaseMissing('users', ['id' => $otherAdmin->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_bulk_delete_never_removes_the_acting_admin()
    {
        $admin = User::factory()->admin()->create();
        $a = User::factory()->create();
        $b = User::factory()->create();

        $response = $this->actingAs($admin)->delete(
            route('system.users.bulk-delete'),
            ['ids' => [$a->id, $b->id, $admin->id]]
        );

        $response->assertRedirect(route('system.index'));
        // Selected non-self users are gone; the acting admin survives.
        $this->assertDatabaseMissing('users', ['id' => $a->id]);
        $this->assertDatabaseMissing('users', ['id' => $b->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
