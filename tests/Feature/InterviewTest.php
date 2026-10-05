<?php

namespace Tests\Feature;

use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

/**
 * The Formularios and Entrevistas sections, each in one project: the project's
 * forms to design, and its active forms to interview with.
 */
class InterviewTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
    }

    private function form(array $attributes = []): InterviewForm
    {
        return InterviewForm::factory()->create(
            ['project_id' => $this->project->id] + $attributes
        );
    }

    // ------------------------------------------------------------ forms ---

    public function test_the_forms_page_lists_the_projects_forms_with_their_interviews()
    {
        $user = $this->userWithCapability($this->project, 'manage_forms');
        $form = $this->form(['name' => 'Usos de plantas', 'is_active' => false]);
        InterviewInstance::factory()->count(2)->create(['interview_form_id' => $form->id]);
        InterviewForm::factory()->create(); // another project's

        $this->actingAs($user)
            ->get(route('designer.index', $this->project))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Designer/Index')
                ->where('project.id', $this->project->id)
                ->has('forms', 1)
                ->where('forms.0.name', 'Usos de plantas')
                ->where('forms.0.is_active', false)
                ->where('forms.0.instances_count', 2)
            );
    }

    public function test_the_forms_page_turns_away_a_role_that_does_not_design_forms()
    {
        $user = $this->userWithCapability($this->project, 'manage_forms', false);

        $this->actingAs($user)
            ->get(route('designer.index', $this->project))
            ->assertRedirect(route('projects.overview', $this->project))
            ->assertSessionHas('message', 'designer.no_access');
    }

    public function test_a_finished_project_takes_no_new_forms()
    {
        $this->project->update(['finished' => true]);
        $user = $this->userWithCapability($this->project, 'manage_forms');

        $this->actingAs($user)
            ->get(route('designer.index', $this->project))
            ->assertRedirect(route('projects.overview', $this->project))
            ->assertSessionHas('message', 'designer.project_finished');
    }

    public function test_the_forms_page_turns_a_stranger_away_to_the_welcome()
    {
        $this->actingAs($this->outsider())
            ->get(route('designer.index', $this->project))
            ->assertRedirect(route('dashboard'));
    }

    // ------------------------------------------------------- interviews ---

    public function test_the_interviews_page_offers_only_the_active_forms()
    {
        $user = $this->userWithCapability($this->project, 'record_data');
        $active = $this->form(['name' => 'Activo', 'is_active' => true]);
        $this->form(['name' => 'Apagado', 'is_active' => false]);
        InterviewInstance::factory()->create(['interview_form_id' => $active->id]);

        $this->actingAs($user)
            ->get(route('interviews.index', $this->project))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Interviews/Index')
                ->has('forms', 1)
                ->where('forms.0.name', 'Activo')
                ->where('forms.0.instances_count', 1)
            );
    }

    public function test_the_interviews_page_turns_away_a_role_that_does_not_record()
    {
        $user = $this->userWithCapability($this->project, 'record_data', false);

        $this->actingAs($user)
            ->get(route('interviews.index', $this->project))
            ->assertRedirect(route('projects.overview', $this->project))
            ->assertSessionHas('message', 'interviews.no_access');
    }

    public function test_a_finished_project_takes_no_new_interviews()
    {
        $this->project->update(['finished' => true]);
        $user = $this->userWithCapability($this->project, 'record_data');
        $form = $this->form(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('interviews.index', $this->project))
            ->assertRedirect(route('projects.overview', $this->project))
            ->assertSessionHas('message', 'interviews.project_finished');

        $this->actingAs($user)
            ->get(route('interviews.create', ['project' => $this->project, 'form' => $form]))
            ->assertRedirect(route('projects.overview', $this->project));

        $this->assertSame(0, InterviewInstance::count());
    }

    public function test_starting_an_interview_opens_it_under_its_project()
    {
        $user = $this->userWithCapability($this->project, 'record_data');
        $form = $this->form(['is_active' => true]);

        $response = $this->actingAs($user)
            ->get(route('interviews.create', ['project' => $this->project, 'form' => $form]));

        $instance = InterviewInstance::sole();
        $response->assertRedirect(route('interviews.show', ['project' => $this->project, 'instance' => $instance]));
        $this->assertSame("/projects/{$this->project->id}/interviews/{$instance->id}", parse_url($response->headers->get('Location'), PHP_URL_PATH));
    }
}
