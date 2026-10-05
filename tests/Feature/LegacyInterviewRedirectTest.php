<?php

namespace Tests\Feature;

use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

/**
 * Forms and interviews used to live under /designer and /interviews. They now
 * live under their project, and the old addresses send there, so a bookmark
 * or a link shared before the move still opens the same page.
 */
class LegacyInterviewRedirectTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    private InterviewForm $form;

    private InterviewInstance $instance;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->form = InterviewForm::factory()->create(['project_id' => $this->project->id]);
        $this->instance = InterviewInstance::factory()->create(['interview_form_id' => $this->form->id]);
        $this->user = $this->userWithCapability($this->project, 'manage_project');
    }

    public function test_each_old_page_moves_permanently_to_its_new_address()
    {
        $p = $this->project->id;
        $f = $this->form->id;
        $i = $this->instance->id;

        $moves = [
            "/designer/{$p}/create" => route('designer.create', $p),
            "/designer/{$p}/form/{$f}" => route('designer.form.edit', [$p, $f]),
            "/designer/{$p}/form/{$f}/wizard" => route('designer.form.wizard', [$p, $f]),
            "/designer/{$p}/form/{$f}/preview" => route('designer.form.preview', [$p, $f]),
            "/interviews/{$f}/instances" => route('interviews.instances', [$p, $f]),
            "/interviews/{$f}/create" => route('interviews.create', [$p, $f]),
            "/interviews/instance/{$i}" => route('interviews.show', [$p, $i]),
        ];

        foreach ($moves as $old => $new) {
            $this->actingAs($this->user)->get($old)->assertStatus(301)->assertRedirect($new);
        }

        // Following the old "new interview" address is not what creates one.
        $this->assertSame(1, InterviewInstance::count());
    }

    public function test_the_query_string_comes_along()
    {
        $this->actingAs($this->user)
            ->get("/interviews/{$this->form->id}/instances?page=2")
            ->assertRedirect(route('interviews.instances', [$this->project, $this->form]).'?page=2');
    }

    /** The old landing pages open the project the user works in, or the one they named. */
    public function test_the_old_landing_pages_open_the_active_project()
    {
        $other = Project::factory()->create();
        $this->giveAccess($this->user, $other, 'manage_project');
        $this->user->forceFill(['last_project_id' => $this->project->id])->save();

        $this->actingAs($this->user)->get('/designer')
            ->assertRedirect(route('designer.index', $this->project));
        $this->actingAs($this->user)->get("/designer?create={$other->id}")
            ->assertRedirect(route('designer.index', ['project' => $other, 'create' => 1]));
        $this->actingAs($this->user)->get("/interviews?project={$other->id}")
            ->assertRedirect(route('interviews.index', $other));
    }

    public function test_the_old_landing_pages_welcome_someone_with_no_project()
    {
        $this->actingAs($this->outsider())->get('/designer')->assertRedirect(route('dashboard'));
        $this->actingAs($this->outsider())->get('/interviews')->assertRedirect(route('dashboard'));
    }

    /** An old address does not reveal which project a form or interview is in. */
    public function test_someone_outside_the_project_is_not_told_where_it_moved()
    {
        $this->actingAs($this->outsider())
            ->get("/interviews/instance/{$this->instance->id}")
            ->assertNotFound();

        $this->actingAs($this->outsider())
            ->get("/interviews/{$this->form->id}/instances")
            ->assertNotFound();
    }

    public function test_an_address_that_never_existed_is_not_found()
    {
        $this->actingAs($this->user)->get("/designer/{$this->project->id}/nonsense")->assertNotFound();
        $this->actingAs($this->user)->get('/interviews/instance/01a1087b-aa81-7202-8556-000000000000')->assertNotFound();
    }
}
