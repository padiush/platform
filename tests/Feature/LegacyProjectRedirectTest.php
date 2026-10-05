<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

/**
 * A project's data lived under /data and its administration under
 * /projects/{project}/edit and /accesses. Both now live under the project, and
 * the old addresses send there, so a bookmark or a link shared before the move
 * still opens the same page.
 */
class LegacyProjectRedirectTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->user = $this->userWithCapability($this->project, 'manage_project');
    }

    public function test_each_old_page_moves_permanently_to_its_new_address()
    {
        $p = $this->project->id;
        $uuid = '01a1087b-aa81-7202-8556-d37303638eaf';

        $moves = [
            "/data/{$p}/view" => route('data.view', $p),
            "/data/link/{$p}" => route('data.link', $p),
            "/data/link/{$p}/species-search" => route('data.link.species-search', $p),
            "/data/{$p}/reports" => route('data.reports', $p),
            "/data/{$p}/reports/download" => route('data.reports.download', $p),
            "/data/{$p}/export" => route('data.export', $p),
            "/data/{$p}/export/preview" => route('data.export.preview', $p),
            "/data/{$p}/interviews/{$uuid}/media" => route('data.media.index', [$p, $uuid]),
            "/data/{$p}/interviews/{$uuid}/media/9" => route('data.media.show', [$p, $uuid, 9]),
            "/projects/{$p}/edit" => route('projects.edit', $p),
            "/projects/{$p}/accesses" => route('projects.accesses', $p),
            "/projects/{$p}/accesses/invites" => route('projects.accesses.invites', $p),
        ];

        foreach ($moves as $old => $new) {
            $this->actingAs($this->user)->get($old)->assertStatus(301)->assertRedirect($new);
        }
    }

    /** The data table opens filtered from a link, and the filter comes along. */
    public function test_the_query_string_comes_along()
    {
        $p = $this->project->id;

        $this->actingAs($this->user)
            ->get("/data/{$p}/view?form=3&tab=summary")
            ->assertRedirect(route('data.view', $p).'?form=3&tab=summary');
    }

    public function test_the_old_data_landing_page_opens_the_active_project()
    {
        $other = Project::factory()->create();
        $this->giveAccess($this->user, $other, 'manage_project');
        $this->user->forceFill(['last_project_id' => $this->project->id])->save();

        $this->actingAs($this->user)->get('/data')
            ->assertRedirect(route('data.view', $this->project));
        $this->actingAs($this->user)->get("/data?project={$other->id}")
            ->assertRedirect(route('data.view', $other));
        $this->actingAs($this->outsider())->get('/data')
            ->assertRedirect(route('dashboard'));
    }

    public function test_an_address_that_never_existed_is_not_found()
    {
        $p = $this->project->id;

        $this->actingAs($this->user)->get("/data/{$p}/nonsense")->assertNotFound();
        $this->actingAs($this->user)->get("/projects/{$p}/accesses/nonsense")->assertNotFound();
    }
}
