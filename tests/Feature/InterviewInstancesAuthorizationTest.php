<?php

namespace Tests\Feature;

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

class InterviewInstancesAuthorizationTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    private InterviewForm $form;

    private InterviewSection $section;

    private InterviewItem $item;

    private InterviewInstance $instance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->form = InterviewForm::factory()->create([
            'project_id' => $this->project->id,
        ]);
        $this->section = InterviewSection::factory()->create([
            'interview_form_id' => $this->form->id,
        ]);
        $this->item = InterviewItem::factory()->create([
            'interview_section_id' => $this->section->id,
        ]);
        $this->instance = InterviewInstance::factory()->create([
            'interview_form_id' => $this->form->id,
        ]);
    }

    public function test_outsider_cannot_view_an_instance()
    {
        $response = $this->actingAs($this->outsider())->get(
            route('interviews.show', ['instance' => $this->instance])
        );

        $response->assertRedirect(route('interviews.index'));
        $response->assertSessionHas('message', 'interviews.no_access');
        $response->assertSessionHas('message_type', 'error');
    }

    public function test_member_without_record_data_cannot_view_an_instance()
    {
        $user = $this->userWithCapability($this->project, 'record_data', false);

        $response = $this->actingAs($user)->get(
            route('interviews.show', ['instance' => $this->instance])
        );

        $response->assertRedirect(route('interviews.index'));
        $response->assertSessionHas('message', 'interviews.no_access');
    }

    public function test_member_with_record_data_can_view_an_instance()
    {
        $user = $this->userWithCapability($this->project, 'record_data');

        $response = $this->actingAs($user)->get(
            route('interviews.show', ['instance' => $this->instance])
        );

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page->component('Interviews/Instance')
        );
    }

    public function test_outsider_cannot_list_instances()
    {
        $response = $this->actingAs($this->outsider())->get(
            route('interviews.instances', ['form' => $this->form])
        );

        $response->assertRedirect(route('interviews.index'));
        $response->assertSessionHas('message', 'interviews.no_access');
    }

    public function test_outsider_cannot_create_an_instance()
    {
        $response = $this->actingAs($this->outsider())->get(
            route('interviews.create', ['form' => $this->form])
        );

        $response->assertRedirect(route('interviews.index'));
        $response->assertSessionHas('message', 'interviews.no_access');
        $this->assertSame(1, InterviewInstance::count());
    }

    public function test_outsider_cannot_delete_an_instance()
    {
        $response = $this->actingAs($this->outsider())->delete(
            route('interviews.destroy', ['instance' => $this->instance])
        );

        $response->assertRedirect(route('interviews.index'));
        $this->assertDatabaseHas('interview_instances', [
            'id' => $this->instance->id,
        ]);
    }

    public function test_outsider_cannot_save_an_answer()
    {
        $response = $this->actingAs($this->outsider())->postJson(
            route('interviews.save_answer', ['instance' => $this->instance]),
            ['item_id' => $this->item->id, 'value' => 'stolen']
        );

        $response->assertForbidden();
        $this->assertDatabaseMissing('instance_answers', [
            'interview_instance_id' => $this->instance->id,
            'interview_item_id' => $this->item->id,
        ]);
    }

    public function test_member_without_record_data_cannot_save_an_answer()
    {
        $user = $this->userWithCapability($this->project, 'record_data', false);

        $response = $this->actingAs($user)->postJson(
            route('interviews.save_answer', ['instance' => $this->instance]),
            ['item_id' => $this->item->id, 'value' => 'stolen']
        );

        $response->assertForbidden();
        $this->assertDatabaseMissing('instance_answers', [
            'interview_instance_id' => $this->instance->id,
            'interview_item_id' => $this->item->id,
        ]);
    }

    public function test_member_with_record_data_can_save_an_answer()
    {
        $user = $this->userWithCapability($this->project, 'record_data');

        $response = $this->actingAs($user)->postJson(
            route('interviews.save_answer', ['instance' => $this->instance]),
            ['item_id' => $this->item->id, 'value' => 'a valid answer']
        );

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('instance_answers', [
            'interview_instance_id' => $this->instance->id,
            'interview_item_id' => $this->item->id,
        ]);
    }

    public function test_answer_cannot_be_saved_for_an_item_of_another_form()
    {
        $user = $this->userWithCapability($this->project, 'record_data');

        $foreignItem = InterviewItem::factory()->create();

        $response = $this->actingAs($user)->postJson(
            route('interviews.save_answer', ['instance' => $this->instance]),
            ['item_id' => $foreignItem->id, 'value' => 'misplaced']
        );

        $response->assertUnprocessable();
        $this->assertSame(0, InstanceAnswer::count());
    }

    public function test_answer_cannot_be_saved_on_a_finished_project()
    {
        $user = $this->userWithCapability($this->project, 'record_data');

        $this->project->update(['finished' => true]);

        $response = $this->actingAs($user)->postJson(
            route('interviews.save_answer', ['instance' => $this->instance]),
            ['item_id' => $this->item->id, 'value' => 'too late']
        );

        $response->assertForbidden();
        $this->assertSame(0, InstanceAnswer::count());
    }
}
