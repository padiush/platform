<?php

namespace Tests\Feature;

use App\Models\FieldRecord;
use App\Models\InstanceAnswer;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\Project;
use App\Models\User;
use App\Services\ExampleStudy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** The example project: a private copy of the invented demo study. */
class ExampleProjectTest extends TestCase
{
    use RefreshDatabase;

    private function example(User $user): ?Project
    {
        return Project::where('user_id', $user->id)->where('is_example', true)->first();
    }

    public function test_a_user_can_open_an_example_project_they_administer(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('projects.example.store'));

        $project = $this->example($user);
        $this->assertNotNull($project);
        $response->assertRedirect(route('projects.overview', ['project' => $project->id]));
        $this->assertTrue($user->fresh()->hasCapabilityOnProject($project, 'manage_project'));
        $this->assertSame($project->id, $user->fresh()->last_project_id);
    }

    /** Everything a section shows, so no page of the tour is empty. */
    public function test_the_example_holds_a_whole_study(): void
    {
        $user = User::factory()->create();

        $project = app(ExampleStudy::class)->build($user, 'Ejemplo', 'Proyecto de ejemplo');

        $this->assertSame(1, $project->interviewForms()->count());
        $this->assertSame(24, InterviewInstance::whereIn('interview_form_id', $project->interviewForms()->pluck('id'))->count());
        $this->assertSame(14, $project->catalogSpecies()->count());
        $this->assertSame(7, FieldRecord::where('project_id', $project->id)->count());
        $this->assertSame(1, $project->collectingPermits()->count());
        $this->assertSame($user->name, $project->author);
    }

    /** The same seed every time: every copy shows the same figures. */
    public function test_every_copy_is_the_same_study(): void
    {
        $answers = function (Project $project) {
            return InstanceAnswer::whereIn(
                'interview_instance_id',
                InterviewInstance::whereIn('interview_form_id', $project->interviewForms()->pluck('id'))->pluck('id')
            )->count();
        };

        $first = app(ExampleStudy::class)->build(User::factory()->create(), 'A', 'x');
        $second = app(ExampleStudy::class)->build(User::factory()->create(), 'B', 'x');

        $this->assertSame($answers($first), $answers($second));
        $this->assertGreaterThan(24, $answers($first));
    }

    public function test_asking_again_opens_the_same_example(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('projects.example.store'));
        $this->actingAs($user)->post(route('projects.example.store'));

        $this->assertSame(1, Project::where('user_id', $user->id)->where('is_example', true)->count());
    }

    public function test_each_user_gets_their_own(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $this->actingAs($alice)->post(route('projects.example.store'));
        $this->actingAs($bob)->post(route('projects.example.store'));

        $this->assertNotEquals($this->example($alice)->id, $this->example($bob)->id);
        $this->assertFalse($bob->fresh()->hasAccessToProject($this->example($alice)));
    }

    public function test_removing_it_takes_only_the_example(): void
    {
        $user = User::factory()->create();
        $real = Project::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user)->post(route('projects.example.store'));
        $exampleId = $this->example($user)->id;

        $this->actingAs($user)
            ->delete(route('projects.example.destroy'))
            ->assertRedirect(route('projects.index'));

        $this->assertNull($this->example($user));
        $this->assertNotNull($real->fresh());
        // Nothing of it is left behind, the catalog included.
        $this->assertDatabaseMissing('catalog_species', ['project_id' => $exampleId]);
        $this->assertDatabaseMissing('field_records', ['project_id' => $exampleId]);
        $this->assertDatabaseMissing('interview_forms', ['project_id' => $exampleId]);
        $this->assertSame(0, InterviewItem::count());
    }

    /** Copies of invented data are not work. */
    public function test_the_system_count_leaves_examples_out(): void
    {
        $admin = User::factory()->create(['system_admin' => true]);
        Project::factory()->create();
        $this->actingAs(User::factory()->create())->post(route('projects.example.store'));

        $this->actingAs($admin)->get(route('system.index'))
            ->assertInertia(fn (Assert $page) => $page->where('project_count', 1));
    }

    public function test_the_sidebar_marks_an_example(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('projects.example.store'));

        $this->actingAs($user)->get(route('projects.overview', $this->example($user)->id))
            ->assertInertia(fn (Assert $page) => $page
                ->where('projectNav.active.is_example', true)
                ->where('projectNav.projects.0.is_example', true));
    }

    public function test_guests_cannot_make_one(): void
    {
        $this->post(route('projects.example.store'))->assertRedirect(route('login'));
        $this->delete(route('projects.example.destroy'))->assertRedirect(route('login'));
    }
}
