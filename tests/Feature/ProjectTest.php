<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectAccess;
use App\Models\ProjectCapability;
use App\Models\ProjectInvite;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Freeze time so the controller's Carbon::now()->addDays(7) and the
        // assertions' now()->addDays(7) can't straddle a second boundary and
        // differ by a second (a latent flake in the invite tests).
        $this->freezeTime();
    }

    public function test_projects_index_can_be_rendered()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('projects.index'));

        $response->assertStatus(200);
    }

    public function test_projects_index_displays_projects_with_access()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $user->id]);

        $access = ProjectAccess::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'project_capability_id' => ProjectCapability::where('manage_project', true)->first()->id,
        ]);

        $response = $this->actingAs($user)->get(route('projects.index'));

        $response->assertSee($project->name);
    }

    public function test_projects_index_does_not_display_projects_without_access()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create();

        $response = $this->actingAs($user)->get(route('projects.index'));

        $response->assertDontSee($project->name);
    }

    public function test_projects_create_redirects_to_the_list_modal()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('projects.create'));

        $response->assertRedirect(route('projects.index', ['create' => 1]));
    }

    public function test_projects_can_be_created()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('projects.create'), [
            'name' => 'Proyecto de prueba',
            'author' => 'Autor de prueba',
            'institution' => 'Institución de prueba',
            'author_email' => 'testing@avalontechsv.dev',
            'country' => 'El Salvador',
        ]);

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('message', 'project.create_success');
        $response->assertSessionHas('message_type', 'success');
        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'name' => 'Proyecto de prueba',
            'author' => 'Autor de prueba',
            'institution' => 'Institución de prueba',
            'author_email' => 'testing@avalontechsv.dev',
            'country' => 'El Salvador',
            'finished' => false,
            'published' => false,
            'shared' => false,
        ]);

        $this->assertDatabaseHas('project_accesses', [
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', true)->first()->id,
        ]);
    }

    public function test_projects_edit_redirects_to_the_list_modal_for_projects_with_manage_capability()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $user->id]);

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', true)->first()->id,
        ]);

        $response = $this->actingAs($user)->get(route('projects.edit', $project));

        $response->assertRedirect(route('projects.index', ['edit' => $project->id]));
    }

    public function test_projects_edit_cannot_be_rendered_for_projects_without_access()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create();

        $response = $this->actingAs($user)->get(route('projects.edit', $project));

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('error', 'No tienes acceso a este proyecto.');
    }

    public function test_projects_edit_cannot_be_rendered_for_projects_without_manage_capability()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create();

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
        ]);

        $response = $this->actingAs($user)->get(route('projects.edit', $project));

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('message', 'projects.no_edit_permission');
        $response->assertSessionHas('message_type', 'error');
    }

    public function test_projects_can_be_updated()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $user->id]);

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', true)->first()->id,
        ]);

        $response = $this->actingAs($user)->post(route('projects.edit', $project), [
            'name' => 'Proyecto de prueba',
            'author' => 'Autor de prueba',
            'institution' => 'Institución de prueba',
            'author_email' => 'testing@avalontechsv.dev',
            'country' => 'El Salvador',
        ]);

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('message', 'project.update_success');
        $response->assertSessionHas('message_type', 'success');
        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'name' => 'Proyecto de prueba',
            'author' => 'Autor de prueba',
            'institution' => 'Institución de prueba',
            'author_email' => 'testing@avalontechsv.dev',
            'country' => 'El Salvador',
            'finished' => false,
            'published' => false,
            'shared' => false,
        ]);
    }

    public function test_project_accesses_can_be_rendered_for_projects_with_manage_capability()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $user->id]);

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', true)->first()->id,
        ]);

        $response = $this->actingAs($user)->get(route('projects.accesses', $project));

        $response->assertStatus(200);
    }

    public function test_project_accesses_cannot_be_rendered_for_projects_without_access()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create();

        $response = $this->actingAs($user)->get(route('projects.accesses', $project));

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('error', 'No tienes acceso a este proyecto.');
    }

    public function test_project_accesses_cannot_be_rendered_for_projects_without_manage_capability()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create();

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
        ]);

        $response = $this->actingAs($user)->get(route('projects.accesses', $project));

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('message', 'projects.no_edit_permission');
        $response->assertSessionHas('message_type', 'error');
    }

    public function test_project_access_cannot_be_revoked_for_own_user()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $user->id]);

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', true)->first()->id,
        ]);

        $response = $this->actingAs($user)->delete(route('projects.accesses.revoke', ['project' => $project, 'user' => $user]));

        $response->assertRedirect(route('projects.accesses', $project));
        $response->assertSessionHas('message', 'projects.no_revoke_own_access');
        $response->assertSessionHas('message_type', 'error');
        $this->assertDatabaseHas('project_accesses', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', true)->first()->id,
        ]);
    }

    public function test_project_access_can_be_revoked_for_other_user()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $user->id]);

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', true)->first()->id,
        ]);

        $otherUser = User::factory()->create();

        $otherAccess = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $otherUser->id,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
        ]);

        $response = $this->actingAs($user)->delete(route('projects.accesses.revoke', ['project' => $project, 'user' => $otherUser]));

        $response->assertRedirect(route('projects.accesses', $project));
        $response->assertSessionHas('message', 'projects.revoke_access_success');
        $response->assertSessionHas('message_type', 'success');
        $this->assertDatabaseMissing('project_accesses', [
            'project_id' => $project->id,
            'user_id' => $otherUser->id,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
        ]);
    }

    public function test_a_user_cannot_have_duplicate_access_rows_on_a_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $capabilityId = ProjectCapability::where('manage_project', true)->first()->id;

        ProjectAccess::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'project_capability_id' => $capabilityId,
        ]);

        $this->expectException(QueryException::class);

        ProjectAccess::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'project_capability_id' => $capabilityId,
        ]);
    }

    public function test_registered_users_can_be_invited()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create(['email' => 'testing@avalontechsv.dev']);

        $project = Project::factory()->create(['user_id' => $user->id]);

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', true)->first()->id,
        ]);

        $response = $this->actingAs($user)->post(route('projects.accesses.invite', $project), [
            'name' => 'Usuario de prueba',
            'email' => 'testing@avalontechsv.dev',
            'capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
        ]);

        $response->assertRedirect(route('projects.accesses', $project));
        $response->assertSessionHas('message', 'projects.invite_sent');
        $response->assertSessionHas('message_type', 'success');
        $this->assertDatabaseHas('project_invites', [
            'project_id' => $project->id,
            'inviting_user_id' => $user->id,
            'invited_user_id' => $anotherUser->id,
            'invited_name' => null,
            'invited_email' => $anotherUser->email,
            'expires_at' => now()->addDays(7),
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
        ]);
    }

    public function test_not_registered_users_can_be_invited()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $user->id]);

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', true)->first()->id,
        ]);

        $response = $this->actingAs($user)->post(route('projects.accesses.invite', $project), [
            'name' => 'Usuario de prueba',
            'email' => 'testing@avalontechsv.dev',
            'capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
        ]);

        $response->assertRedirect(route('projects.accesses', $project));
        $response->assertSessionHas('message', 'projects.invite_sent');
        $response->assertSessionHas('message_type', 'success');
        $this->assertDatabaseHas('project_invites', [
            'project_id' => $project->id,
            'inviting_user_id' => $user->id,
            'invited_user_id' => null,
            'invited_name' => 'Usuario de prueba',
            'invited_email' => 'testing@avalontechsv.dev',
            'expires_at' => now()->addDays(7),
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
        ]);
    }

    public function test_users_with_access_cannot_be_invited_again()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $user->id]);

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', true)->first()->id,
        ]);

        $otherAccess = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $anotherUser->id,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
        ]);

        $response = $this->actingAs($user)->post(route('projects.accesses.invite', $project), [
            'name' => 'Usuario de prueba',
            'email' => $anotherUser->email,
            'capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
        ]);

        $response->assertRedirect(route('projects.accesses', $project));
        $response->assertSessionHas('message', 'projects.user_already_in_project');
        $response->assertSessionHas('message_type', 'error');
        $this->assertDatabaseMissing('project_invites', [
            'project_id' => $project->id,
            'inviting_user_id' => $user->id,
            'invited_user_id' => $anotherUser->id,
            'invited_name' => null,
            'invited_email' => $anotherUser->email,
            'expires_at' => now()->addDays(7),
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
        ]);
    }

    public function test_users_cannot_be_invited_without_access()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create();

        $response = $this->actingAs($user)->post(route('projects.accesses.invite', $project), [
            'name' => 'Usuario de prueba',
            'email' => 'testing@avalontechsv.dev',
            'capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
        ]);

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('message', 'projects.no_edit_permission');
        $response->assertSessionHas('message_type', 'error');
        $this->assertDatabaseMissing('project_invites', [
            'project_id' => $project->id,
            'inviting_user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
        ]);
    }

    public function test_users_cannot_be_invited_without_manage_users_capability()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create();

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_users', false)->first()->id,
        ]);

        $response = $this->actingAs($user)->post(route('projects.accesses.invite', $project), [
            'name' => 'Usuario de prueba',
            'email' => 'testing@avalontechsv.dev',
            'capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
        ]);

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('message', 'projects.no_edit_permission');
        $response->assertSessionHas('message_type', 'error');
        $this->assertDatabaseMissing('project_invites', [
            'project_id' => $project->id,
            'inviting_user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
        ]);
    }

    public function test_project_invitations_can_be_rendered_with_manage_users_capability()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create();

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_users', true)->first()->id,
        ]);

        $invite = ProjectInvite::factory()->create([
            'project_id' => $project->id,
            'inviting_user_id' => $user->id,
            'invited_user_id' => null,
            'invited_name' => 'Usuario de prueba',
            'invited_email' => 'testing@avalontechsv.dev',
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($user)->get(route('projects.accesses.invites', $project));

        $response->assertStatus(200);
        $response->assertInertia(
            fn (Assert $page) => $page->component('Projects/PendingInvites')
        );
    }

    public function test_project_invitations_cannot_be_rendered_without_access()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create();

        $response = $this->actingAs($user)->get(route('projects.accesses.invites', $project));

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('message', 'projects.no_edit_permission');
        $response->assertSessionHas('message_type', 'error');
    }

    public function test_project_invitations_cannot_be_rendered_without_manage_users_capability()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create();

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_users', false)->first()->id,
        ]);

        $response = $this->actingAs($user)->get(route('projects.accesses.invites', $project));

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('message', 'projects.no_edit_permission');
        $response->assertSessionHas('message_type', 'error');
    }

    public function test_invitations_appear_in_projects_index()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $project = Project::factory()->create();

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_users', true)->first()->id,
        ]);

        $invite = ProjectInvite::factory()->create([
            'project_id' => $project->id,
            'inviting_user_id' => $user->id,
            'invited_user_id' => $anotherUser->id,
            'invited_name' => null,
            'invited_email' => $anotherUser->email,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($anotherUser)->get(route('projects.index'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Projects/Index')
                ->has('invites', 1)
                ->where('invites.0.project.name', $project->name)
        );
    }

    public function test_invitations_can_be_accepted_by_invited_user()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $project = Project::factory()->create();

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_users', true)->first()->id,
        ]);

        $invite = ProjectInvite::factory()->create([
            'project_id' => $project->id,
            'inviting_user_id' => $user->id,
            'invited_user_id' => $anotherUser->id,
            'invited_name' => null,
            'invited_email' => $anotherUser->email,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($anotherUser)->get(route('projects.invites.accept', $invite));

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('message', 'projects.invite_accepted');
        $response->assertSessionHas('message_type', 'success');
        $this->assertDatabaseHas('project_accesses', [
            'project_id' => $project->id,
            'user_id' => $anotherUser->id,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
        ]);
        $this->assertDatabaseMissing('project_invites', [
            'project_id' => $project->id,
            'inviting_user_id' => $user->id,
            'invited_user_id' => $anotherUser->id,
            'invited_name' => null,
            'invited_email' => $anotherUser->email,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function test_invitations_can_be_declined_by_invited_user()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $project = Project::factory()->create();

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_users', true)->first()->id,
        ]);

        $invite = ProjectInvite::factory()->create([
            'project_id' => $project->id,
            'inviting_user_id' => $user->id,
            'invited_user_id' => $anotherUser->id,
            'invited_name' => null,
            'invited_email' => $anotherUser->email,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($anotherUser)->get(route('projects.invites.decline', $invite));

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('message', 'projects.invite_declined');
        $response->assertSessionHas('message_type', 'success');
        $this->assertDatabaseMissing('project_accesses', [
            'project_id' => $project->id,
            'user_id' => $anotherUser->id,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
        ]);
        $this->assertDatabaseMissing('project_invites', [
            'project_id' => $project->id,
            'inviting_user_id' => $user->id,
            'invited_user_id' => $anotherUser->id,
            'invited_name' => null,
            'invited_email' => $anotherUser->email,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function test_expired_invitations_cannot_be_accepted()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $project = Project::factory()->create();

        $invite = ProjectInvite::factory()->create([
            'project_id' => $project->id,
            'inviting_user_id' => $user->id,
            'invited_user_id' => $anotherUser->id,
            'invited_email' => $anotherUser->email,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($anotherUser)->get(route('projects.invites.accept', $invite));

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('message', 'projects.invite_expired');
        $response->assertSessionHas('message_type', 'error');
        // No access is granted and the stale invite is cleared.
        $this->assertDatabaseMissing('project_accesses', [
            'project_id' => $project->id,
            'user_id' => $anotherUser->id,
        ]);
        $this->assertDatabaseMissing('project_invites', [
            'id' => $invite->id,
        ]);
    }

    public function test_expired_invitations_are_cleared_on_decline()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $project = Project::factory()->create();

        $invite = ProjectInvite::factory()->create([
            'project_id' => $project->id,
            'inviting_user_id' => $user->id,
            'invited_user_id' => $anotherUser->id,
            'invited_email' => $anotherUser->email,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($anotherUser)->get(route('projects.invites.decline', $invite));

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('message', 'projects.invite_expired');
        $response->assertSessionHas('message_type', 'error');
        $this->assertDatabaseMissing('project_invites', [
            'id' => $invite->id,
        ]);
    }

    public function test_invitations_cannot_be_accepted_by_other_users()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $yetAnotherUser = User::factory()->create();

        $project = Project::factory()->create();

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_users', true)->first()->id,
        ]);

        $invite = ProjectInvite::factory()->create([
            'project_id' => $project->id,
            'inviting_user_id' => $user->id,
            'invited_user_id' => $anotherUser->id,
            'invited_name' => null,
            'invited_email' => $anotherUser->email,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($yetAnotherUser)->get(route('projects.invites.accept', $invite));

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('message', 'projects.cannot_accept_another_user_invite');
        $response->assertSessionHas('message_type', 'error');
        $this->assertDatabaseMissing('project_accesses', [
            'project_id' => $project->id,
            'user_id' => $yetAnotherUser->id,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
        ]);
        $this->assertDatabaseHas('project_invites', [
            'project_id' => $project->id,
            'inviting_user_id' => $user->id,
            'invited_user_id' => $anotherUser->id,
            'invited_name' => null,
            'invited_email' => $anotherUser->email,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function test_invitations_cannot_be_declined_by_other_users()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $yetAnotherUser = User::factory()->create();

        $project = Project::factory()->create();

        $access = ProjectAccess::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_users', true)->first()->id,
        ]);

        $invite = ProjectInvite::factory()->create([
            'project_id' => $project->id,
            'inviting_user_id' => $user->id,
            'invited_user_id' => $anotherUser->id,
            'invited_name' => null,
            'invited_email' => $anotherUser->email,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($yetAnotherUser)->get(route('projects.invites.decline', $invite));

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('message', 'projects.cannot_decline_another_user_invite');
        $response->assertSessionHas('message_type', 'error');
        $this->assertDatabaseMissing('project_accesses', [
            'project_id' => $project->id,
            'user_id' => $yetAnotherUser->id,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
        ]);
        $this->assertDatabaseHas('project_invites', [
            'project_id' => $project->id,
            'inviting_user_id' => $user->id,
            'invited_user_id' => $anotherUser->id,
            'invited_name' => null,
            'invited_email' => $anotherUser->email,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->first()->id,
            'expires_at' => now()->addDays(7),
        ]);
    }
}
