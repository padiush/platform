<?php

namespace Tests\Feature;

use App\Models\AdminAction;
use App\Models\Project;
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

        $response->assertRedirect(route('system.users'));
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

        $response->assertRedirect(route('system.users'));
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

    public function test_the_deletion_is_recorded_for_every_administrator(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create(['name' => 'Pedro Ruiz']);
        Project::factory()->create(['user_id' => $target->id]);

        $this->actingAs($admin)->delete(route('system.users.delete', $target));

        $this->assertDatabaseHas('admin_actions', [
            'actor_id' => $admin->id,
            'action' => AdminAction::USER_DELETED,
        ]);
        $this->assertSame(
            ['name' => 'Pedro Ruiz', 'email' => $target->email, 'projects' => 1, 'files' => 0],
            AdminAction::first()->details
        );
    }
}
