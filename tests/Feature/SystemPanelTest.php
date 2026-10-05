<?php

namespace Tests\Feature;

use App\Models\AdminAction;
use App\Models\FieldRecord;
use App\Models\InstanceAnswer;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\Media;
use App\Models\Project;
use App\Models\ProjectAccess;
use App\Models\ProjectCapability;
use App\Models\RegistrationInvite;
use App\Models\SystemRun;
use App\Models\User;
use App\Notifications\RegistrationInviteNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** The system administrators' panel: figures and names, never content. */
class SystemPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['name' => 'Rodrigo']);
    }

    private function media(array $owner, string $kind, int $bytes, string $status = Media::STATUS_STORED): Media
    {
        return Media::create([
            ...$owner,
            'client_id' => (string) Str::uuid(),
            'kind' => $kind,
            'storage_disk' => 's3',
            'storage_key' => 'projects/secret-key-'.Str::uuid(),
            'content_type' => $kind === Media::KIND_AUDIO ? 'audio/mp4' : 'image/jpeg',
            'byte_size' => $bytes,
            'status' => $status,
        ]);
    }

    private function interviewIn(Project $project): InterviewInstance
    {
        $form = InterviewForm::factory()->create(['project_id' => $project->id]);

        return InterviewInstance::factory()->create(['interview_form_id' => $form->id]);
    }

    private function member(Project $project, User $user): void
    {
        ProjectAccess::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', false)->value('id'),
        ]);
    }

    public function test_only_system_administrators_reach_it(): void
    {
        $someone = User::factory()->create();
        $invite = RegistrationInvite::factory()->create(['inviting_user_id' => $this->admin->id]);

        $this->actingAs($someone);
        $this->get(route('system.users'))->assertRedirect(route('dashboard'));
        $this->get(route('system.storage'))->assertRedirect(route('dashboard'));
        $this->get(route('system.users.deletion', $this->admin))->assertRedirect(route('dashboard'));
        $this->post(route('system.users.transfer', $this->admin), ['to' => $someone->id])->assertRedirect(route('dashboard'));
        $this->post(route('system.registration-invites.resend', $invite))->assertRedirect(route('dashboard'));
        $this->delete(route('system.registration-invites.destroy', $invite))->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('registration_invites', ['id' => $invite->id]);
        $this->assertSame(0, AdminAction::count());
    }

    /** Stored audio and photos, counted for the person who owns the project. */
    public function test_storage_is_counted_by_owner_and_kind(): void
    {
        $ana = User::factory()->create(['name' => 'Ana']);
        $marta = User::factory()->create(['name' => 'Marta']);
        $anas = Project::factory()->create(['user_id' => $ana->id]);
        $martas = Project::factory()->create(['user_id' => $marta->id]);

        $this->media(['interview_instance_id' => $this->interviewIn($anas)->id], Media::KIND_AUDIO, 3000);
        $this->media(['field_record_id' => FieldRecord::factory()->create(['project_id' => $anas->id])->id], Media::KIND_PHOTO, 500);
        $this->media(['interview_instance_id' => $this->interviewIn($martas)->id], Media::KIND_AUDIO, 1000);
        // Announced, not sent: not counted as stored.
        $this->media(['interview_instance_id' => $this->interviewIn($martas)->id], Media::KIND_AUDIO, 9000, Media::STATUS_PENDING);

        $this->actingAs($this->admin)->get(route('system.storage'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('System/Storage')
                ->where('storage.total', 4500)
                ->where('storage.audio', 4000)
                ->where('storage.photo', 500)
                ->where('storage.files', 3)
                ->where('owners.0.name', 'Ana')
                ->where('owners.0.total', 3500)
                ->where('owners.0.audio', 3000)
                ->where('owners.0.photo', 500)
                ->where('owners.1.name', 'Marta')
                ->where('owners.1.total', 1000)
                ->where('largest.0.id', $anas->id)
                ->where('largest.0.owner', 'Ana')
                ->where('checks.waiting.count', 1));

        $this->actingAs($this->admin)->get(route('system.users'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('users', fn ($users) => collect($users)->firstWhere('id', $ana->id)['storage'] === 3500));
    }

    /** Nothing from inside a project reaches the panel. */
    public function test_it_never_shows_what_is_inside_a_project(): void
    {
        $project = Project::factory()->create();
        $instance = $this->interviewIn($project);
        $media = $this->media(['interview_instance_id' => $instance->id], Media::KIND_AUDIO, 100);
        InstanceAnswer::factory()->create([
            'interview_instance_id' => $instance->id,
            'answer' => 'Lo que dijo la informante',
        ]);
        $this->member($project, User::factory()->create());

        foreach (['system.index', 'system.users', 'system.storage'] as $page) {
            $body = $this->actingAs($this->admin)->get(route($page))->getContent();

            $this->assertStringNotContainsString('Lo que dijo la informante', $body, $page);
            $this->assertStringNotContainsString($media->storage_key, $body, $page);
        }

        $preview = $this->actingAs($this->admin)
            ->getJson(route('system.users.deletion', $project->user))
            ->getContent();
        $this->assertStringNotContainsString('Lo que dijo la informante', $preview);
        $this->assertStringNotContainsString($media->storage_key, $preview);
    }

    public function test_the_summary_counts_people_projects_and_activity(): void
    {
        $active = User::factory()->create();
        User::factory()->create();
        Project::factory()->create(['finished' => true]);
        Project::factory()->create();
        RegistrationInvite::factory()->create(['inviting_user_id' => $this->admin->id]);
        DB::table('sessions')->insert([
            'id' => Str::random(40), 'user_id' => $active->id, 'payload' => '', 'last_activity' => now()->subDay()->getTimestamp(),
        ]);

        $this->actingAs($this->admin)->get(route('system.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('System/Index')
                // The admin, the active one, the inactive one, and the two
                // project owners the factory made.
                ->where('counts.users', 5)
                ->where('counts.active_users', 1)
                ->where('counts.invites', 1)
                ->where('counts.projects', 2)
                ->where('counts.finished_projects', 1)
                ->where('pending_migrations', 0)
                ->where('version', config('app.version')));
    }

    /** Last seen on the web or from a field device, whichever was later. */
    public function test_last_activity_is_the_latest_session_or_device(): void
    {
        $ana = User::factory()->create();
        DB::table('sessions')->insert([
            'id' => Str::random(40), 'user_id' => $ana->id, 'payload' => '', 'last_activity' => now()->subDays(5)->getTimestamp(),
        ]);
        $ana->createToken('Fold')->accessToken->forceFill(['last_used_at' => now()->subDay()])->save();

        $this->actingAs($this->admin)->get(route('system.users'))
            ->assertInertia(fn (Assert $page) => $page->where('users', function ($users) use ($ana) {
                $seen = collect($users)->firstWhere('id', $ana->id)['last_active_at'];

                return now()->subDay()->diffInMinutes($seen, true) < 2;
            }));
    }

    public function test_the_deletion_preview_says_what_goes_and_what_stays(): void
    {
        $ana = User::factory()->create();
        $study = Project::factory()->create(['user_id' => $ana->id, 'name' => 'Huertos']);
        $example = Project::factory()->create(['user_id' => $ana->id, 'name' => 'Ejemplo']);
        $example->forceFill(['is_example' => true])->save();
        $this->media(['interview_instance_id' => $this->interviewIn($study)->id], Media::KIND_AUDIO, 2048);
        $this->member($study, User::factory()->create(['name' => 'Marta']));
        $this->member($study, User::factory()->create(['name' => 'Julio']));
        $this->member(Project::factory()->create(), $ana);

        $this->actingAs($this->admin)->getJson(route('system.users.deletion', $ana))
            ->assertOk()
            ->assertJson([
                'user' => ['id' => $ana->id],
                'collaborators' => ['count' => 2, 'names' => ['Julio', 'Marta']],
                'files' => 1,
                'bytes' => 2048,
                'other_projects' => 1,
                'transferable' => 1,
            ])
            ->assertJsonPath('projects.0.name', 'Ejemplo')
            ->assertJsonPath('projects.0.is_example', true)
            ->assertJsonPath('projects.1.name', 'Huertos')
            ->assertJsonPath('projects.1.interviews', 1);
    }

    /** Someone leaving need not take the lab's study with them. */
    public function test_projects_can_be_transferred_before_an_account_is_deleted(): void
    {
        Storage::fake('s3');
        $ana = User::factory()->create(['name' => 'Ana']);
        $marta = User::factory()->create(['name' => 'Marta']);
        $study = Project::factory()->create(['user_id' => $ana->id]);
        $example = Project::factory()->create(['user_id' => $ana->id]);
        $example->forceFill(['is_example' => true])->save();
        $this->member($study, $marta);

        $this->actingAs($this->admin)
            ->post(route('system.users.transfer', $ana), ['to' => $marta->id])
            ->assertSessionHas('message', 'system.projects_transferred');

        $this->assertSame($marta->id, $study->fresh()->user_id);
        $this->assertTrue($marta->fresh()->hasCapabilityOnProject($study, 'manage_project'));
        // An example is its owner's own copy: it goes with them.
        $this->assertSame($ana->id, $example->fresh()->user_id);
        $this->assertSame(
            ['from' => 'Ana', 'to' => 'Marta', 'count' => 1],
            AdminAction::where('action', AdminAction::PROJECTS_TRANSFERRED)->first()->details
        );

        $this->actingAs($this->admin)->delete(route('system.users.delete', $ana));

        $this->assertNotNull($study->fresh());
        $this->assertNull($example->fresh());
    }

    public function test_projects_cannot_be_transferred_to_their_owner(): void
    {
        $ana = User::factory()->create();
        $study = Project::factory()->create(['user_id' => $ana->id]);

        $this->actingAs($this->admin)
            ->post(route('system.users.transfer', $ana), ['to' => $ana->id])
            ->assertSessionHasErrors('to');

        $this->assertSame($ana->id, $study->fresh()->user_id);
    }

    public function test_an_invitation_can_be_sent_again_or_withdrawn(): void
    {
        Notification::fake();
        $resent = RegistrationInvite::factory()->create([
            'inviting_user_id' => $this->admin->id,
            'invited_email' => 'carla@example.org',
            'expires_at' => now()->addDay(),
        ]);
        $withdrawn = RegistrationInvite::factory()->create([
            'inviting_user_id' => $this->admin->id,
            'invited_email' => 'luis@example.org',
        ]);

        $this->actingAs($this->admin)->post(route('system.registration-invites.resend', $resent));
        $this->actingAs($this->admin)->delete(route('system.registration-invites.destroy', $withdrawn))
            ->assertSessionHas('message', 'system.registration_invite_withdrawn');

        $this->assertTrue($resent->fresh()->expires_at->gt(now()->addDays(6)));
        Notification::assertSentOnDemand(RegistrationInviteNotification::class);
        $this->assertNull($withdrawn->fresh());
        $this->assertSame(
            [AdminAction::INVITE_RESENT, AdminAction::INVITE_WITHDRAWN],
            AdminAction::orderBy('id')->pluck('action')->all()
        );
    }

    /** Every administrator sees what the others did, newest first. */
    public function test_the_summary_shows_the_administrators_record(): void
    {
        Notification::fake();
        $this->actingAs($this->admin)->post(route('system.registration-invites.store'), [
            'name' => 'Carla', 'email' => 'carla@example.org',
        ]);
        $this->artisan('user:promote', ['email' => User::factory()->create(['name' => 'Sara'])->email]);

        $this->actingAs(User::factory()->admin()->create())->get(route('system.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('log.0.action', AdminAction::ADMIN_PROMOTED)
                ->where('log.0.actor', null)
                ->where('log.0.details.name', 'Sara')
                ->where('log.1.action', AdminAction::INVITE_SENT)
                ->where('log.1.actor', 'Rodrigo')
                ->where('log.1.details.email', 'carla@example.org'));
    }

    public function test_the_record_outlives_the_administrator_who_acted(): void
    {
        $other = User::factory()->admin()->create(['name' => 'Sara']);
        AdminAction::record($other, AdminAction::INVITE_WITHDRAWN, ['email' => 'luis@example.org']);

        $other->delete();

        $this->assertSame('Sara', AdminAction::first()->actor_name);
        $this->assertNull(AdminAction::first()->actor_id);
    }

    public function test_the_checks_say_whether_upkeep_is_running(): void
    {
        $this->actingAs($this->admin)->get(route('system.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('checks.scheduler.status', 'unknown')
                ->where('checks.orphans.status', 'warn')
                ->where('checks.uploads.status', 'ok'));

        SystemRun::mark(SystemRun::SCHEDULER);
        Storage::fake('s3');
        $this->artisan('media:prune-orphans');
        $stuck = $this->media(['interview_instance_id' => $this->interviewIn(Project::factory()->create())->id], Media::KIND_AUDIO, 10, Media::STATUS_PENDING);
        $stuck->forceFill(['upload_id' => 'abc', 'upload_part_size' => 8])->save();
        DB::table('media')->where('id', $stuck->id)->update(['updated_at' => now()->subDays(9)]);

        $this->actingAs($this->admin)->get(route('system.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('checks.scheduler.status', 'ok')
                ->where('checks.orphans.status', 'ok')
                ->where('checks.orphans.found', 0)
                ->where('checks.uploads.status', 'warn')
                ->where('checks.uploads.count', 1));
    }

    public function test_a_scheduler_that_stopped_is_flagged(): void
    {
        SystemRun::mark(SystemRun::SCHEDULER);
        SystemRun::where('key', SystemRun::SCHEDULER)->update(['ran_at' => now()->subHour()]);

        $this->actingAs($this->admin)->get(route('system.index'))
            ->assertInertia(fn (Assert $page) => $page->where('checks.scheduler.status', 'warn'));
    }

    public function test_the_scheduler_records_its_heartbeat(): void
    {
        $heartbeat = collect(app(Schedule::class)->events())
            ->first(fn ($event) => $event->description === 'system-heartbeat');

        $this->assertNotNull($heartbeat);
        $heartbeat->run($this->app);

        $this->assertNotNull(SystemRun::find(SystemRun::SCHEDULER));
    }
}
