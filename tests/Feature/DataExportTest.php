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
use Maatwebsite\Excel\Facades\Excel;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

class DataExportTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    public function test_ethnobotanyr_export_handles_a_species_answer_with_no_category()
    {
        Excel::fake();

        $project = Project::factory()->create();
        $user = $this->userWithCapability($project, 'generate_reports');

        $form = InterviewForm::factory()->create(['project_id' => $project->id]);
        $section = InterviewSection::factory()->create([
            'interview_form_id' => $form->id,
        ]);
        // The category field the report groups by.
        $categoryField = InterviewItem::factory()->create([
            'interview_section_id' => $section->id,
        ]);
        // The species field.
        $speciesField = InterviewItem::factory()->create([
            'interview_section_id' => $section->id,
            'link_to_species' => true,
        ]);

        $species = CatalogSpecies::factory()->create([
            'project_id' => $project->id,
        ]);
        $instance = InterviewInstance::factory()->create([
            'interview_form_id' => $form->id,
        ]);

        // A species-linked answer, but NO answer for the category field on this
        // instance — the case that used to fatally dereference ->first()->answer.
        InstanceAnswer::create([
            'interview_instance_id' => $instance->id,
            'interview_section_id' => $section->id,
            'interview_item_id' => $speciesField->id,
            'catalog_species_id' => $species->id,
            'answer' => 'some plant',
        ]);

        $response = $this->actingAs($user)->post(
            route('data.ethnobotanyR', $project),
            ['form_id' => $form->id, 'field_id' => $categoryField->id]
        );

        $response->assertStatus(200);
        Excel::assertDownloaded('ethnobotanyr.xlsx');
    }
}
