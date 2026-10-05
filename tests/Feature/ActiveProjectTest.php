<?php

namespace Tests\Feature;

use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

/**
 * The sidebar works in one project at a time: the one the page belongs to,
 * else the one the user last worked in. Each section opens there, and only
 * the sections the user's role in that project opens are offered.
 */
class ActiveProjectTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $herbs;

    private Project $trees;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->herbs = Project::factory()->create(['name' => 'Hierbas']);
        $this->trees = Project::factory()->create(['name' => 'Árboles']);

        // The project administrator in both: every section is open.
        $this->user = User::factory()->create();
        $this->giveAccess($this->user, $this->herbs, 'manage_project');
        $this->giveAccess($this->user, $this->trees, 'manage_project');
    }

    private function nav(Assert $page): array
    {
        return $page->toArray()['props']['projectNav'];
    }

    // ----------------------------------------------------- remembering ---

    public function test_working_in_a_project_remembers_it_on_the_account()
    {
        $this->actingAs($this->user)
            ->get(route('catalogs.fieldRecords.index', $this->trees))
            ->assertOk();

        $this->assertSame($this->trees->id, $this->user->fresh()->last_project_id);
    }

    /** Remembering where someone works is not an edit of their account. */
    public function test_remembering_does_not_touch_the_account_timestamp()
    {
        $this->user->forceFill(['updated_at' => now()->subWeek()])->save();
        $before = $this->user->fresh()->updated_at;

        $this->actingAs($this->user)->get(route('catalogs.fieldRecords.index', $this->trees));

        $this->assertEquals($before, $this->user->fresh()->updated_at);
    }

    /** An interview names no project in its address, but belongs to one. */
    public function test_an_interview_is_in_its_forms_project()
    {
        $form = InterviewForm::factory()->create(['project_id' => $this->trees->id]);
        $instance = InterviewInstance::factory()->create(['interview_form_id' => $form->id]);

        $this->actingAs($this->user)
            ->get(route('interviews.show', $instance))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $this->assertSame(
                $this->trees->id,
                $this->nav($page)['active']['id']
            ));

        $this->assertSame($this->trees->id, $this->user->fresh()->last_project_id);
    }

    public function test_a_page_without_a_project_opens_on_the_last_one()
    {
        $this->user->forceFill(['last_project_id' => $this->trees->id])->save();

        $this->actingAs($this->user)
            ->get(route('projects.index'))
            ->assertInertia(fn (Assert $page) => $this->assertSame(
                $this->trees->id,
                $this->nav($page)['active']['id']
            ));
    }

    /** Signing in lands on the overview of the project last worked in. */
    public function test_the_dashboard_opens_the_last_projects_overview()
    {
        $this->user->forceFill(['last_project_id' => $this->trees->id])->save();

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertRedirect(route('projects.overview', $this->trees));
    }

    /** Never worked anywhere yet: an unfinished project before a finished one. */
    public function test_with_nothing_remembered_it_opens_on_an_unfinished_project()
    {
        $this->trees->update(['finished' => true]);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertRedirect(route('projects.overview', $this->herbs));
    }

    /** A project the user cannot open is never the one the sidebar is on. */
    public function test_a_project_out_of_reach_is_not_remembered()
    {
        $elsewhere = Project::factory()->create();
        $this->user->forceFill(['last_project_id' => $this->herbs->id])->save();

        $this->actingAs($this->user)->get(route('catalogs.fieldRecords.index', $elsewhere));

        $this->assertSame($this->herbs->id, $this->user->fresh()->last_project_id);
    }

    /** With nothing to open, signing in lands on the welcome. */
    public function test_a_user_with_no_project_has_none_active()
    {
        $this->actingAs($this->outsider())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('projectNav.active', null)
                ->where('projectNav.projects', [])
                ->where('projectNav.sections', [])
            );
    }

    // ------------------------------------------------------ the sidebar ---

    public function test_the_sidebar_lists_every_project_and_says_which_are_finished()
    {
        $this->trees->update(['finished' => true]);

        $this->actingAs($this->user)
            ->get(route('projects.index'))
            ->assertInertia(fn (Assert $page) => $page->where('projectNav.projects', [
                ['id' => $this->herbs->id, 'name' => 'Hierbas', 'finished' => false],
                ['id' => $this->trees->id, 'name' => 'Árboles', 'finished' => true],
            ]));
    }

    public function test_every_section_opens_in_the_active_project()
    {
        $id = $this->trees->id;

        $this->actingAs($this->user)
            ->get(route('catalogs.fieldRecords.index', $this->trees))
            ->assertInertia(fn (Assert $page) => $this->assertSame([
                'overview' => route('projects.overview', ['project' => $id]),
                'forms' => route('designer.index', ['project' => $id]),
                'interviews' => route('interviews.index', ['project' => $id]),
                'records' => route('catalogs.fieldRecords.index', ['project' => $id]),
                'catalog' => route('catalogs.index', ['project' => $id]),
                'data' => route('data.index', ['project' => $id]),
            ], $this->nav($page)['sections']));
    }

    /**
     * The consultation role reads the catalog and the reports, and records
     * nothing: no forms, no interviews.
     */
    public function test_only_the_sections_the_role_opens_are_offered()
    {
        $reader = $this->userWithCapability($this->trees, 'record_data', false);

        $this->actingAs($reader)
            ->get(route('catalogs.fieldRecords.index', $this->trees))
            ->assertInertia(fn (Assert $page) => $this->assertSame(
                ['overview', 'records', 'catalog', 'data'],
                array_keys($this->nav($page)['sections'])
            ));
    }

    /** A finished project takes no new forms and no new interviews. */
    public function test_a_finished_project_offers_no_forms_or_interviews()
    {
        $this->trees->update(['finished' => true]);

        $this->actingAs($this->user)
            ->get(route('catalogs.fieldRecords.index', $this->trees))
            ->assertInertia(fn (Assert $page) => $this->assertSame(
                ['overview', 'records', 'catalog', 'data'],
                array_keys($this->nav($page)['sections'])
            ));
    }

    // ---------------------------------------------------- switching ---

    public function test_switching_keeps_the_section()
    {
        $this->actingAs($this->user)
            ->post(route('projects.activate', $this->trees), ['section' => 'records'])
            ->assertRedirect(route('catalogs.fieldRecords.index', $this->trees));

        $this->assertSame($this->trees->id, $this->user->fresh()->last_project_id);
    }

    public function test_switching_to_a_project_without_that_section_opens_its_overview()
    {
        $this->trees->update(['finished' => true]);

        $this->actingAs($this->user)
            ->post(route('projects.activate', $this->trees), ['section' => 'interviews'])
            ->assertRedirect(route('projects.overview', $this->trees));
    }

    public function test_switching_to_a_project_out_of_reach_is_refused()
    {
        $elsewhere = Project::factory()->create();

        $this->actingAs($this->user)
            ->post(route('projects.activate', $elsewhere), ['section' => 'records'])
            ->assertForbidden();

        $this->assertNull($this->user->fresh()->last_project_id);
    }

    // ------------------------------------------------- landing pages ---

    /** Each section's landing page shows only the project the sidebar is on. */
    public function test_a_landing_page_narrowed_to_one_project_shows_only_it()
    {
        foreach (['designer.index', 'interviews.index', 'catalogs.index', 'data.index'] as $name) {
            $this->actingAs($this->user)
                ->get(route($name, ['project' => $this->trees->id]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $this->assertSame(
                    [$this->trees->id],
                    collect($page->toArray()['props']['projects'])->pluck('id')->all(),
                    $name
                ));
        }

        $this->assertSame($this->trees->id, $this->user->fresh()->last_project_id);
    }

    public function test_a_landing_page_without_a_project_still_shows_them_all()
    {
        $this->actingAs($this->user)
            ->get(route('catalogs.index'))
            ->assertInertia(fn (Assert $page) => $this->assertCount(
                2,
                $page->toArray()['props']['projects']
            ));
    }
}
