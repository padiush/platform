<?php

namespace Tests\Feature;

use App\Models\CatalogSpecies;
use App\Models\InstanceAnswer;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

/**
 * Cross-project IDOR guards: a user legitimately authorized on their own
 * project must not be able to reach another project's resources by mixing
 * ids in the route or request body.
 */
class ResourceScopingTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    public function test_form_of_another_project_cannot_be_edited()
    {
        $mine = Project::factory()->create();
        $user = $this->userWithCapability($mine, 'manage_forms');

        $other = Project::factory()->create();
        $foreignForm = InterviewForm::factory()->create([
            'project_id' => $other->id,
            'name' => 'Original name',
        ]);

        $response = $this->actingAs($user)->put(
            route('designer.form.update', [
                'project' => $mine,
                'form' => $foreignForm,
            ]),
            ['name' => 'Hijacked name']
        );

        $response->assertRedirect(route('designer.index'));
        $response->assertSessionHas('message', 'designer.form_not_found');
        $this->assertDatabaseHas('interview_forms', [
            'id' => $foreignForm->id,
            'name' => 'Original name',
        ]);
    }

    public function test_form_of_another_project_cannot_be_deleted()
    {
        $mine = Project::factory()->create();
        $user = $this->userWithCapability($mine, 'manage_forms');

        $other = Project::factory()->create();
        $foreignForm = InterviewForm::factory()->create([
            'project_id' => $other->id,
        ]);

        $response = $this->actingAs($user)->delete(
            route('designer.form.delete', [
                'project' => $mine,
                'form' => $foreignForm,
            ])
        );

        $response->assertRedirect(route('designer.index'));
        $response->assertSessionHas('message', 'designer.form_not_found');
        $this->assertDatabaseHas('interview_forms', [
            'id' => $foreignForm->id,
        ]);
    }

    public function test_form_is_created_on_the_route_project_not_the_body_project()
    {
        $mine = Project::factory()->create();
        $user = $this->userWithCapability($mine, 'manage_forms');

        $other = Project::factory()->create();

        $response = $this->actingAs($user)->post(
            route('designer.create', ['project' => $mine]),
            [
                // Attempt to smuggle another project id through the body.
                'project_id' => $other->id,
                'name' => 'My form',
                'description' => 'desc',
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('interview_forms', [
            'name' => 'My form',
            'project_id' => $mine->id,
        ]);
        $this->assertDatabaseMissing('interview_forms', [
            'name' => 'My form',
            'project_id' => $other->id,
        ]);
    }

    public function test_species_of_another_project_cannot_be_viewed()
    {
        $mine = Project::factory()->create();
        $user = $this->userWithCapability($mine, 'view_catalog');

        $other = Project::factory()->create();
        $foreignSpecies = CatalogSpecies::factory()->create([
            'project_id' => $other->id,
        ]);

        $response = $this->actingAs($user)->get(
            route('catalogs.species.show', [
                'project' => $mine,
                'species' => $foreignSpecies,
            ])
        );

        $response->assertRedirect(route('catalogs.index'));
        $response->assertSessionHas('message', 'catalogs.species_not_found');
    }

    public function test_species_of_another_project_cannot_be_deleted()
    {
        $mine = Project::factory()->create();
        $user = $this->userWithCapability($mine, 'edit_catalog');

        $other = Project::factory()->create();
        $foreignSpecies = CatalogSpecies::factory()->create([
            'project_id' => $other->id,
        ]);

        $response = $this->actingAs($user)->delete(
            route('catalogs.species.delete', [
                'project' => $mine,
                'species' => $foreignSpecies,
            ])
        );

        $response->assertRedirect(route('catalogs.index'));
        $response->assertSessionHas('message', 'catalogs.species_not_found');
        $this->assertDatabaseHas('catalog_species', [
            'id' => $foreignSpecies->id,
        ]);
    }

    public function test_link_request_rejects_species_from_another_project()
    {
        $mine = Project::factory()->create();
        $user = $this->userWithCapability($mine, 'manage_data');

        $form = InterviewForm::factory()->create(['project_id' => $mine->id]);
        $section = InterviewSection::factory()->create([
            'interview_form_id' => $form->id,
        ]);
        $instance = InterviewInstance::factory()->create([
            'interview_form_id' => $form->id,
        ]);

        // Species belongs to a different project.
        $other = Project::factory()->create();
        $foreignSpecies = CatalogSpecies::factory()->create([
            'project_id' => $other->id,
        ]);

        $response = $this->actingAs($user)->postJson(
            route('data.link.handle', ['project' => $mine]),
            [
                'interview_instance_id' => $instance->id,
                'catalog_species_id' => $foreignSpecies->id,
                'interview_section_id' => $section->id,
            ]
        );

        $response->assertForbidden();
    }

    public function test_custom_export_rejects_fields_from_another_project()
    {
        $mine = Project::factory()->create();
        $user = $this->userWithCapability($mine, 'generate_reports');

        $myForm = InterviewForm::factory()->create(['project_id' => $mine->id]);

        // A field that belongs to another project entirely.
        $foreignItem = InterviewItem::factory()->create();

        $response = $this->actingAs($user)->post(
            route('data.export.download', ['project' => $mine]),
            [
                'mode' => 'custom',
                'form_id' => $myForm->id,
                'selected_fields' => json_encode([$foreignItem->id]),
            ]
        );

        $response->assertRedirect(route('projects.index'));
    }

    public function test_repeatable_set_of_another_forms_section_cannot_be_deleted()
    {
        $mine = Project::factory()->create();
        $user = $this->userWithCapability($mine, 'record_data');

        $form = InterviewForm::factory()->create(['project_id' => $mine->id]);
        $instance = InterviewInstance::factory()->create([
            'interview_form_id' => $form->id,
        ]);

        // A section that belongs to a different form.
        $otherForm = InterviewForm::factory()->create([
            'project_id' => $mine->id,
        ]);
        $foreignSection = InterviewSection::factory()->create([
            'interview_form_id' => $otherForm->id,
        ]);
        $foreignItem = InterviewItem::factory()->create([
            'interview_section_id' => $foreignSection->id,
        ]);

        $answer = InstanceAnswer::create([
            'interview_instance_id' => $instance->id,
            'interview_section_id' => $foreignSection->id,
            'interview_item_id' => $foreignItem->id,
            'repeatable_index' => 0,
            'answer' => 'keep me',
        ]);

        $response = $this->actingAs($user)->delete(
            route('interviews.section.remove', [
                'instance' => $instance,
                'section' => $foreignSection,
            ]),
            ['repeatable_index' => 0]
        );

        $response->assertRedirect(route('interviews.index'));
        $this->assertDatabaseHas('instance_answers', ['id' => $answer->id]);
    }
}
