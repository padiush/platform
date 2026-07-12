<?php

namespace Tests\Feature;

use App\Models\InstanceAnswer;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

class DesignerStructureTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    private InterviewForm $form;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->form = InterviewForm::factory()->create([
            'project_id' => $this->project->id,
        ]);
    }

    private function structureUrl(?InterviewForm $form = null): string
    {
        return route('designer.form.structure.update', [
            'project' => $this->project,
            'form' => $form ?? $this->form,
        ]);
    }

    private function itemPayload(array $overrides = []): array
    {
        return array_merge([
            'label' => 'A field',
            'name' => 'a_field',
            'type' => 'text',
            'required' => false,
            'link_to_species' => false,
            'options' => [],
        ], $overrides);
    }

    public function test_outsider_cannot_save_structure()
    {
        $response = $this->actingAs($this->outsider())->putJson(
            $this->structureUrl(),
            ['sections' => []]
        );

        $response->assertForbidden();
        $response->assertJsonPath('message', 'designer.no_access');
    }

    public function test_member_without_manage_forms_cannot_save_structure()
    {
        $user = $this->userWithCapability($this->project, 'manage_forms', false);

        $response = $this->actingAs($user)->putJson(
            $this->structureUrl(),
            ['sections' => []]
        );

        $response->assertForbidden();
    }

    public function test_structure_is_created_from_a_draft_without_ids()
    {
        $user = $this->userWithCapability($this->project, 'manage_forms');

        $response = $this->actingAs($user)->putJson($this->structureUrl(), [
            'sections' => [
                [
                    'name' => 'Informant',
                    'repeatable' => false,
                    'items' => [
                        $this->itemPayload(['label' => 'Name', 'name' => 'informant_name']),
                        $this->itemPayload([
                            'label' => 'Community',
                            'name' => 'Community Name', // slugged server-side
                        ]),
                    ],
                ],
                [
                    'name' => 'Plant use',
                    'repeatable' => true,
                    'items' => [
                        $this->itemPayload([
                            'label' => 'Use category',
                            'name' => 'use_category',
                            'type' => 'select',
                            'options' => ['Medicinal', ' Food ', ''],
                        ]),
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'designer.structure_saved');
        $response->assertJsonPath('structure.0.name', 'Informant');
        $response->assertJsonPath('structure.0.items.1.name', 'community_name');
        $response->assertJsonPath('structure.1.repeatable', true);
        // Blank options are dropped and values trimmed.
        $response->assertJsonPath('structure.1.items.0.options', ['Medicinal', 'Food']);

        $this->assertDatabaseHas('interview_sections', [
            'interview_form_id' => $this->form->id,
            'name' => 'Plant use',
            'order' => 2,
        ]);
    }

    public function test_structure_updates_reorders_and_deletes()
    {
        $user = $this->userWithCapability($this->project, 'manage_forms');
        $sectionA = InterviewSection::factory()->create([
            'interview_form_id' => $this->form->id,
            'name' => 'A',
            'order' => 1,
        ]);
        $sectionB = InterviewSection::factory()->create([
            'interview_form_id' => $this->form->id,
            'name' => 'B',
            'order' => 2,
        ]);
        $keptItem = InterviewItem::factory()->create([
            'interview_section_id' => $sectionA->id,
            'label' => 'Keep me',
            'order' => 1,
        ]);
        $droppedItem = InterviewItem::factory()->create([
            'interview_section_id' => $sectionA->id,
            'label' => 'Drop me',
            'order' => 2,
        ]);

        // Reverse section order, rename, delete one item, move the kept item along.
        $response = $this->actingAs($user)->putJson($this->structureUrl(), [
            'sections' => [
                [
                    'id' => $sectionB->id,
                    'name' => 'B renamed',
                    'repeatable' => false,
                    'items' => [],
                ],
                [
                    'id' => $sectionA->id,
                    'name' => 'A',
                    'repeatable' => false,
                    'items' => [
                        $this->itemPayload([
                            'id' => $keptItem->id,
                            'label' => 'Keep me renamed',
                            'name' => 'keep_me',
                        ]),
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('interview_sections', [
            'id' => $sectionB->id,
            'name' => 'B renamed',
            'order' => 1,
        ]);
        $this->assertDatabaseHas('interview_sections', ['id' => $sectionA->id, 'order' => 2]);
        $this->assertDatabaseHas('interview_items', [
            'id' => $keptItem->id,
            'label' => 'Keep me renamed',
        ]);
        $this->assertDatabaseMissing('interview_items', ['id' => $droppedItem->id]);
    }

    public function test_dropped_sections_are_deleted_with_their_items()
    {
        $user = $this->userWithCapability($this->project, 'manage_forms');
        $section = InterviewSection::factory()->create([
            'interview_form_id' => $this->form->id,
        ]);
        $item = InterviewItem::factory()->create([
            'interview_section_id' => $section->id,
        ]);

        $response = $this->actingAs($user)->putJson($this->structureUrl(), [
            'sections' => [],
        ]);

        $response->assertOk();
        $this->assertDatabaseMissing('interview_sections', ['id' => $section->id]);
        $this->assertDatabaseMissing('interview_items', ['id' => $item->id]);
    }

    public function test_items_can_move_between_sections_when_they_have_no_answers()
    {
        $user = $this->userWithCapability($this->project, 'manage_forms');
        $sectionA = InterviewSection::factory()->create([
            'interview_form_id' => $this->form->id,
            'order' => 1,
        ]);
        $sectionB = InterviewSection::factory()->create([
            'interview_form_id' => $this->form->id,
            'order' => 2,
        ]);
        $item = InterviewItem::factory()->create([
            'interview_section_id' => $sectionA->id,
        ]);

        $response = $this->actingAs($user)->putJson($this->structureUrl(), [
            'sections' => [
                ['id' => $sectionA->id, 'name' => 'A', 'repeatable' => false, 'items' => []],
                [
                    'id' => $sectionB->id,
                    'name' => 'B',
                    'repeatable' => false,
                    'items' => [
                        $this->itemPayload(['id' => $item->id]),
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('interview_items', [
            'id' => $item->id,
            'interview_section_id' => $sectionB->id,
        ]);
    }

    public function test_moving_an_item_with_answers_between_sections_is_refused()
    {
        $user = $this->userWithCapability($this->project, 'manage_forms');
        [$sectionA, $sectionB, $item] = $this->sectionsWithAnsweredItem();

        $response = $this->actingAs($user)->putJson($this->structureUrl(), [
            'sections' => [
                ['id' => $sectionA->id, 'name' => 'A', 'repeatable' => false, 'items' => []],
                [
                    'id' => $sectionB->id,
                    'name' => 'B',
                    'repeatable' => false,
                    'items' => [$this->itemPayload(['id' => $item->id])],
                ],
            ],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('message', 'designer.move_has_answers');
        $response->assertJsonPath('items.0.id', $item->id);
        $this->assertDatabaseHas('interview_items', [
            'id' => $item->id,
            'interview_section_id' => $sectionA->id,
        ]);
    }

    public function test_deleting_an_item_with_answers_requires_confirmation()
    {
        $user = $this->userWithCapability($this->project, 'manage_forms');
        [$sectionA, , $item] = $this->sectionsWithAnsweredItem();

        $payload = [
            'sections' => [
                ['id' => $sectionA->id, 'name' => 'A', 'repeatable' => false, 'items' => []],
            ],
        ];

        $response = $this->actingAs($user)->putJson($this->structureUrl(), $payload);

        $response->assertStatus(409);
        $response->assertJsonPath('requires_confirmation', true);
        $response->assertJsonPath('detaching.0.id', $item->id);
        $response->assertJsonPath('total_answers', 1);
        $this->assertDatabaseHas('interview_items', ['id' => $item->id]);

        // Confirmed: the item and its answers are removed together.
        $confirmed = $this->actingAs($user)->putJson(
            $this->structureUrl(),
            $payload + ['confirm_detach' => true]
        );

        $confirmed->assertOk();
        $this->assertDatabaseMissing('interview_items', ['id' => $item->id]);
        $this->assertDatabaseCount('instance_answers', 0);
    }

    public function test_section_ids_from_another_form_are_rejected()
    {
        $user = $this->userWithCapability($this->project, 'manage_forms');
        $otherForm = InterviewForm::factory()->create([
            'project_id' => $this->project->id,
        ]);
        $foreignSection = InterviewSection::factory()->create([
            'interview_form_id' => $otherForm->id,
        ]);

        $response = $this->actingAs($user)->putJson($this->structureUrl(), [
            'sections' => [
                [
                    'id' => $foreignSection->id,
                    'name' => 'Hijack',
                    'repeatable' => false,
                    'items' => [],
                ],
            ],
        ]);

        $response->assertForbidden();
        $response->assertJsonPath('message', 'designer.section_not_found');
        $this->assertDatabaseHas('interview_sections', [
            'id' => $foreignSection->id,
            'interview_form_id' => $otherForm->id,
        ]);
    }

    public function test_item_ids_from_another_form_are_rejected()
    {
        $user = $this->userWithCapability($this->project, 'manage_forms');
        $section = InterviewSection::factory()->create([
            'interview_form_id' => $this->form->id,
        ]);
        $otherForm = InterviewForm::factory()->create([
            'project_id' => $this->project->id,
        ]);
        $foreignSection = InterviewSection::factory()->create([
            'interview_form_id' => $otherForm->id,
        ]);
        $foreignItem = InterviewItem::factory()->create([
            'interview_section_id' => $foreignSection->id,
        ]);

        $response = $this->actingAs($user)->putJson($this->structureUrl(), [
            'sections' => [
                [
                    'id' => $section->id,
                    'name' => 'Mine',
                    'repeatable' => false,
                    'items' => [$this->itemPayload(['id' => $foreignItem->id])],
                ],
            ],
        ]);

        $response->assertForbidden();
        $response->assertJsonPath('message', 'designer.item_not_found');
        $this->assertDatabaseHas('interview_items', [
            'id' => $foreignItem->id,
            'interview_section_id' => $foreignSection->id,
        ]);
    }

    public function test_payload_shape_is_validated()
    {
        $user = $this->userWithCapability($this->project, 'manage_forms');

        $response = $this->actingAs($user)->putJson($this->structureUrl(), [
            'sections' => [
                [
                    'name' => '',
                    'items' => [
                        $this->itemPayload(['label' => '', 'type' => 'bogus']),
                    ],
                ],
            ],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([
            'sections.0.name',
            'sections.0.items.0.label',
            'sections.0.items.0.type',
        ]);
    }

    public function test_number_settings_are_kept_and_non_number_settings_cleared()
    {
        $user = $this->userWithCapability($this->project, 'manage_forms');

        $response = $this->actingAs($user)->putJson($this->structureUrl(), [
            'sections' => [
                [
                    'name' => 'Measures',
                    'repeatable' => false,
                    'items' => [
                        $this->itemPayload([
                            'label' => 'Height',
                            'name' => 'height',
                            'type' => 'number',
                            'min' => 0,
                            'max' => 100,
                            'step' => 0.5,
                        ]),
                        $this->itemPayload([
                            'label' => 'Notes',
                            'name' => 'notes',
                            'type' => 'text',
                            'min' => 3, // must be discarded for non-number types
                        ]),
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('structure.0.items.0.min', 0);
        $response->assertJsonPath('structure.0.items.1.min', null);
    }

    public function test_wizard_page_provides_structure_and_instance_count()
    {
        $user = $this->userWithCapability($this->project, 'manage_forms');
        $section = InterviewSection::factory()->create([
            'interview_form_id' => $this->form->id,
        ]);
        $item = InterviewItem::factory()->create([
            'interview_section_id' => $section->id,
        ]);
        $instance = InterviewInstance::factory()->create([
            'interview_form_id' => $this->form->id,
        ]);
        InstanceAnswer::create([
            'interview_instance_id' => $instance->id,
            'interview_section_id' => $section->id,
            'interview_item_id' => $item->id,
            'answer' => 'hello',
        ]);

        $response = $this->actingAs($user)->get(
            route('designer.form.wizard', [
                'project' => $this->project,
                'form' => $this->form,
            ])
        );

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Designer/Wizard')
                ->where('instancesCount', 1)
                ->where('structure.0.items.0.answers_count', 1)
        );
    }

    /** A form with two sections where section A's item has one recorded answer. */
    private function sectionsWithAnsweredItem(): array
    {
        $sectionA = InterviewSection::factory()->create([
            'interview_form_id' => $this->form->id,
            'name' => 'A',
            'order' => 1,
        ]);
        $sectionB = InterviewSection::factory()->create([
            'interview_form_id' => $this->form->id,
            'name' => 'B',
            'order' => 2,
        ]);
        $item = InterviewItem::factory()->create([
            'interview_section_id' => $sectionA->id,
        ]);
        $instance = InterviewInstance::factory()->create([
            'interview_form_id' => $this->form->id,
        ]);
        InstanceAnswer::create([
            'interview_instance_id' => $instance->id,
            'interview_section_id' => $sectionA->id,
            'interview_item_id' => $item->id,
            'answer' => 'recorded',
        ]);

        return [$sectionA, $sectionB, $item];
    }
}
