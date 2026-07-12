<?php

namespace Tests\Feature;

use App\Models\ChartPreference;
use App\Models\InstanceAnswer;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

class ChartPreferenceTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    private InterviewSection $section;

    private InterviewItem $select;

    private InterviewItem $number;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $form = InterviewForm::factory()->create(['project_id' => $this->project->id]);
        $this->section = InterviewSection::factory()->create(['interview_form_id' => $form->id]);
        $this->select = InterviewItem::factory()->create([
            'interview_section_id' => $this->section->id,
            'type' => 'select',
        ]);
        $this->number = InterviewItem::factory()->create([
            'interview_section_id' => $this->section->id,
            'type' => 'number',
        ]);

        // One answer so the field shows up in the summary.
        $instance = InterviewInstance::factory()->create(['interview_form_id' => $form->id]);
        InstanceAnswer::create([
            'interview_instance_id' => $instance->id,
            'interview_section_id' => $this->section->id,
            'interview_item_id' => $this->select->id,
            'answer' => 'Alimento',
        ]);
    }

    private function manager()
    {
        return $this->userWithCapability($this->project, 'manage_data');
    }

    private function save($user, array $payload)
    {
        return $this->actingAs($user)->postJson(
            route('data.chart-preference', $this->project),
            $payload
        );
    }

    public function test_manager_saves_a_chart_preference()
    {
        $user = $this->manager();

        $response = $this->save($user, [
            'interview_item_id' => $this->select->id,
            'chart_type' => 'pie',
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('chart_preferences', [
            'user_id' => $user->id,
            'interview_item_id' => $this->select->id,
            'chart_type' => 'pie',
        ]);
    }

    public function test_saving_again_updates_the_same_row()
    {
        $user = $this->manager();

        $this->save($user, ['interview_item_id' => $this->select->id, 'chart_type' => 'pie']);
        $this->save($user, ['interview_item_id' => $this->select->id, 'chart_type' => 'table']);

        $this->assertDatabaseCount('chart_preferences', 1);
        $this->assertDatabaseHas('chart_preferences', [
            'interview_item_id' => $this->select->id,
            'chart_type' => 'table',
        ]);
    }

    public function test_rejects_a_type_invalid_for_the_field_kind()
    {
        // A number field only allows bar/table, never pie.
        $response = $this->save($this->manager(), [
            'interview_item_id' => $this->number->id,
            'chart_type' => 'pie',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('chart_preferences', 0);
    }

    public function test_rejects_an_item_from_another_project()
    {
        $foreign = InterviewItem::factory()->create();

        $response = $this->save($this->manager(), [
            'interview_item_id' => $foreign->id,
            'chart_type' => 'bar',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('chart_preferences', 0);
    }

    public function test_outsider_cannot_save()
    {
        $response = $this->save($this->outsider(), [
            'interview_item_id' => $this->select->id,
            'chart_type' => 'pie',
        ]);

        $response->assertForbidden();
    }

    public function test_saved_preference_appears_in_the_summary()
    {
        $user = $this->manager();
        ChartPreference::create([
            'user_id' => $user->id,
            'interview_item_id' => $this->select->id,
            'chart_type' => 'pie',
        ]);

        $response = $this->actingAs($user)->get(
            route('data.view', ['project' => $this->project, 'tab' => 'summary'])
        );

        $response->assertInertia(fn (Assert $page) => $page
            ->where('summary', fn ($summary) => collect($summary)
                ->firstWhere('item_id', $this->select->id)['chart_type'] === 'pie')
        );
    }
}
