<?php

namespace Tests\Feature;

use App\Models\InstanceAnswer;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Media;
use App\Models\Project;
use App\Models\ProjectAccess;
use App\Models\ProjectCapability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

class InterviewDataViewTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    private InterviewForm $form;

    private InterviewSection $section;

    private InterviewItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->form = InterviewForm::factory()->create(['project_id' => $this->project->id]);
        $this->section = InterviewSection::factory()->create([
            'interview_form_id' => $this->form->id,
        ]);
        $this->item = InterviewItem::factory()->create([
            'interview_section_id' => $this->section->id,
        ]);
    }

    private function manager()
    {
        return $this->userWithCapability($this->project, 'manage_data');
    }

    private function recordAnswer(string $value): InterviewInstance
    {
        $instance = InterviewInstance::factory()->create([
            'interview_form_id' => $this->form->id,
        ]);
        InstanceAnswer::create([
            'interview_instance_id' => $instance->id,
            'interview_section_id' => $this->section->id,
            'interview_item_id' => $this->item->id,
            'answer' => $value,
        ]);

        return $instance;
    }

    /** The sidebar offers the section, so it opens even before any interview. */
    public function test_a_project_without_data_opens_in_place()
    {
        $response = $this->actingAs($this->manager())->get(
            route('data.view', $this->project)
        );

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Data/View')
            ->where('forms', [])
            ->where('structure', null)
            ->has('tabs')
        );
    }

    private function attachMedia(InterviewInstance $instance, string $status = Media::STATUS_STORED): void
    {
        Media::create([
            'interview_instance_id' => $instance->id,
            'client_id' => (string) Str::uuid(),
            'kind' => Media::KIND_AUDIO,
            'storage_disk' => 's3',
            'storage_key' => 'projects/1/'.Str::uuid().'.m4a',
            'content_type' => 'audio/mp4',
            'status' => $status,
        ]);
    }

    /** Recording without filling in the form is ordinary fieldwork. */
    public function test_an_interview_with_audio_and_no_answers_can_still_be_reached()
    {
        $audioOnly = InterviewInstance::factory()->create(['interview_form_id' => $this->form->id]);
        $this->attachMedia($audioOnly);

        $response = $this->actingAs($this->manager())->get(route('data.view', $this->project));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('rows.data', 0)
            ->has('unanswered', 1)
            ->where('unanswered.0.id', $audioOnly->id)
            ->where('unanswered.0.media_count', 1)
        );
    }

    public function test_only_interviews_with_files_and_no_answers_are_listed_apart()
    {
        // Has a row of its own in the table.
        $this->attachMedia($this->recordAnswer('ceiba'));
        // Nothing to play: its one file never arrived.
        $this->attachMedia(
            InterviewInstance::factory()->create(['interview_form_id' => $this->form->id]),
            Media::STATUS_PENDING
        );
        // Started and left empty.
        InterviewInstance::factory()->create(['interview_form_id' => $this->form->id]);

        $this->actingAs($this->manager())
            ->get(route('data.view', $this->project))
            ->assertInertia(fn (Assert $page) => $page->has('unanswered', 0));
    }

    public function test_the_unanswered_list_follows_the_filters()
    {
        $audioOnly = InterviewInstance::factory()->create(['interview_form_id' => $this->form->id]);
        $this->attachMedia($audioOnly);

        $this->actingAs($this->manager())
            ->get(route('data.view', [
                'project' => $this->project,
                'from' => now()->addDay()->toDateString(),
            ]))
            ->assertInertia(fn (Assert $page) => $page->has('unanswered', 0));
    }

    public function test_a_row_counts_only_files_that_have_arrived()
    {
        $instance = $this->recordAnswer('ceiba');
        $this->attachMedia($instance);
        $this->attachMedia($instance, Media::STATUS_PENDING);

        $this->actingAs($this->manager())
            ->get(route('data.view', $this->project))
            ->assertInertia(fn (Assert $page) => $page->where('rows.data.0.media_count', 1));
    }

    public function test_manager_sees_the_data_table()
    {
        $this->recordAnswer('guarumo');
        $this->recordAnswer('ceiba');

        $response = $this->actingAs($this->manager())->get(
            route('data.view', $this->project)
        );

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            // Existence unchecked here; the page component ships in the
            // frontend batch. This keeps the backend commit self-contained.
            ->component('Data/View', false)
            ->has('rows.data', 2)
            ->has('forms', 1)
            ->where('structure.section.id', $this->section->id)
            ->where('filters.tab', 'table')
            // The heavy summary is optional and absent from the initial load.
            ->missing('summary')
        );
    }

    public function test_summary_tab_includes_the_summary()
    {
        $this->recordAnswer('guarumo');

        $response = $this->actingAs($this->manager())->get(
            route('data.view', ['project' => $this->project, 'tab' => 'summary'])
        );

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->has('summary'));
    }

    public function test_member_without_data_capability_is_denied()
    {
        $this->recordAnswer('guarumo');

        // A project member whose role grants neither manage_data nor
        // generate_reports (no seeded role qualifies, so build one).
        $user = User::factory()->create();
        $capability = ProjectCapability::factory()->create([
            'manage_data' => false,
            'generate_reports' => false,
        ]);
        ProjectAccess::factory()->create([
            'user_id' => $user->id,
            'project_id' => $this->project->id,
            'project_capability_id' => $capability->id,
        ]);

        $response = $this->actingAs($user)->get(
            route('data.view', $this->project)
        );

        // To the project's overview, which every member can open.
        $response->assertRedirect(route('projects.overview', $this->project));
        $response->assertSessionHas('message', 'data.no_access');
    }

    public function test_outsider_is_denied()
    {
        $this->recordAnswer('guarumo');

        $response = $this->actingAs($this->outsider())->get(
            route('data.view', $this->project)
        );

        $response->assertRedirect(route('dashboard'));
    }
}
