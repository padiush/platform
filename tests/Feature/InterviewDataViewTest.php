<?php

namespace Tests\Feature;

use App\Models\InstanceAnswer;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Project;
use App\Models\ProjectAccess;
use App\Models\ProjectCapability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_project_without_data_redirects()
    {
        $response = $this->actingAs($this->manager())->get(
            route('data.view', $this->project)
        );

        $response->assertRedirect(route('data.index'));
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

        $response->assertRedirect(route('projects.index'));
    }

    public function test_outsider_is_denied()
    {
        $this->recordAnswer('guarumo');

        $response = $this->actingAs($this->outsider())->get(
            route('data.view', $this->project)
        );

        $response->assertRedirect(route('projects.index'));
    }
}
