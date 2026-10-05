<?php

namespace Tests\Feature;

use App\Models\CatalogSpecies;
use App\Models\Determination;
use App\Models\FieldRecord;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\Media;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

/**
 * A project at a glance: what has been gathered, what was added lately, and
 * what is waiting on someone — each part for the roles that can act on it.
 */
class ProjectOverviewTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    private InterviewInstance $interview;

    private FieldRecord $identified;

    private FieldRecord $unidentified;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create(['name' => 'Plantas útiles']);
        $form = InterviewForm::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Usos de plantas',
        ]);
        $this->interview = InterviewInstance::factory()->create(['interview_form_id' => $form->id]);

        $species = CatalogSpecies::factory()->create(['project_id' => $this->project->id]);
        $this->identified = FieldRecord::factory()->create([
            'project_id' => $this->project->id,
            'vernacular_name' => 'Guaba',
        ]);
        Determination::factory()->create([
            'field_record_id' => $this->identified->id,
            'catalog_species_id' => $species->id,
        ]);
        $this->unidentified = FieldRecord::factory()->create([
            'project_id' => $this->project->id,
            'vernacular_name' => 'Ruda',
        ]);

        // Another project's work never counts here.
        $elsewhere = Project::factory()->create();
        FieldRecord::factory()->create(['project_id' => $elsewhere->id]);
    }

    private function medium(array $owner, string $status): Media
    {
        $media = new Media;
        $media->forceFill(array_merge($owner, [
            'client_id' => (string) Str::uuid(),
            'kind' => 'photo',
            'storage_disk' => 's3',
            'storage_key' => 'k/'.Str::random(8).'.jpg',
            'content_type' => 'image/jpeg',
            'byte_size' => 3,
            'status' => $status,
        ]))->save();

        return $media;
    }

    private function overview(User $user)
    {
        return $this->actingAs($user)->get(route('projects.overview', $this->project));
    }

    public function test_a_member_sees_what_the_project_has_gathered()
    {
        $this->overview($this->userWithCapability($this->project, 'manage_project'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Projects/Overview')
                ->where('project.name', 'Plantas útiles')
                ->where('project.finished', false)
                ->where('counts.interviews', 1)
                ->where('counts.field_records', 2)
                ->where('counts.species', 1)
            );
    }

    public function test_someone_outside_the_project_is_refused()
    {
        $this->overview($this->outsider())->assertForbidden();
    }

    /** What still needs doing: unidentified records, and files never received. */
    public function test_it_says_what_is_waiting()
    {
        $this->medium(['interview_instance_id' => $this->interview->id], Media::STATUS_PENDING);
        $this->medium(['field_record_id' => $this->unidentified->id], Media::STATUS_PENDING);
        $this->medium(['field_record_id' => $this->identified->id], Media::STATUS_STORED);

        $this->overview($this->userWithCapability($this->project, 'manage_project'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('waiting.pending_media', 2)
                ->where('waiting.undetermined_records', 1)
            );
    }

    public function test_it_lists_the_latest_interviews_and_records()
    {
        $this->overview($this->userWithCapability($this->project, 'manage_project'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('recentInterviews', 1)
                ->where('recentInterviews.0.id', $this->interview->id)
                ->where('recentInterviews.0.form', 'Usos de plantas')
                ->has('recentRecords', 2)
            );
    }

    /**
     * The consultation role reads the catalog and the reports and records
     * nothing: it sees the records, and not who recorded which interview.
     */
    public function test_each_part_is_shown_to_the_roles_that_can_act_on_it()
    {
        $reader = $this->userWithCapability($this->project, 'record_data', false);

        $this->overview($reader)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('recentInterviews', null)
                ->has('recentRecords', 2)
                ->where('can.link_species', false)
                ->where('can.export', true)
                ->where('counts.interviews', 1)
            );
    }

    public function test_a_finished_project_says_so()
    {
        $this->project->update(['finished' => true]);

        $this->overview($this->userWithCapability($this->project, 'manage_project'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('project.finished', true)
                ->missing('sections.interviews')
                ->missing('sections.forms')
            );
    }

    public function test_opening_it_makes_it_the_project_worked_in()
    {
        $user = $this->userWithCapability($this->project, 'manage_project');

        $this->overview($user);

        $this->assertSame($this->project->id, $user->fresh()->last_project_id);
    }
}
