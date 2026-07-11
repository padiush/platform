<?php

namespace Tests\Feature;

use App\Exports\CustomExport;
use App\Exports\EthnobotanyRExport;
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

    public function test_ethnobotanyr_export_builds_the_presence_matrix()
    {
        Excel::fake();

        $project = Project::factory()->create();
        $user = $this->userWithCapability($project, 'generate_reports');

        $form = InterviewForm::factory()->create(['project_id' => $project->id]);
        $section = InterviewSection::factory()->create([
            'interview_form_id' => $form->id,
        ]);
        $categoryField = InterviewItem::factory()->create([
            'interview_section_id' => $section->id,
        ]);
        $speciesField = InterviewItem::factory()->create([
            'interview_section_id' => $section->id,
            'link_to_species' => true,
        ]);

        $species = CatalogSpecies::factory()->create([
            'project_id' => $project->id,
            'genus' => 'Inga',
            'name' => 'edulis',
        ]);
        $instance = InterviewInstance::factory()->create([
            'interview_form_id' => $form->id,
        ]);

        InstanceAnswer::create([
            'interview_instance_id' => $instance->id,
            'interview_section_id' => $section->id,
            'interview_item_id' => $speciesField->id,
            'catalog_species_id' => $species->id,
            'answer' => 'guaba',
        ]);
        InstanceAnswer::create([
            'interview_instance_id' => $instance->id,
            'interview_section_id' => $section->id,
            'interview_item_id' => $categoryField->id,
            'answer' => 'Alimento',
        ]);

        $response = $this->actingAs($user)->post(
            route('data.ethnobotanyR', $project),
            ['form_id' => $form->id, 'field_id' => $categoryField->id]
        );

        $response->assertStatus(200);
        Excel::assertDownloaded(
            'ethnobotanyr.xlsx',
            function (EthnobotanyRExport $export) use ($instance) {
                $html = $export->view()->render();

                // Header uses the normalized category name; the row carries
                // the informant id, the species binomial, and a 1 in the
                // matching category column.
                return str_contains($html, '<td>alimento</td>')
                    && str_contains($html, "PADIUSH_INST_{$instance->id}")
                    && str_contains($html, '<td>Inga edulis</td>')
                    && preg_match('/<td>\s*1\s*<\/td>/', $html) === 1;
            }
        );
    }

    public function test_custom_export_contains_answers_and_neutralizes_formulas()
    {
        Excel::fake();

        $project = Project::factory()->create();
        $user = $this->userWithCapability($project, 'generate_reports');

        $form = InterviewForm::factory()->create(['project_id' => $project->id]);
        $section = InterviewSection::factory()->create([
            'interview_form_id' => $form->id,
            'repeatable' => false,
        ]);
        $field = InterviewItem::factory()->create([
            'interview_section_id' => $section->id,
            'label' => 'Uso medicinal',
        ]);

        $instance = InterviewInstance::factory()->create([
            'interview_form_id' => $form->id,
        ]);

        InstanceAnswer::create([
            'interview_instance_id' => $instance->id,
            'interview_section_id' => $section->id,
            'interview_item_id' => $field->id,
            'answer' => '=HYPERLINK("http://evil")',
        ]);

        $response = $this->actingAs($user)->post(
            route('data.custom', $project),
            [
                'form_id' => $form->id,
                'selected_fields' => json_encode([$field->id]),
            ]
        );

        $response->assertStatus(200);
        Excel::assertDownloaded(
            'custom.xlsx',
            function (CustomExport $export) use ($instance) {
                $html = $export->view()->render();

                // The formula must come out prefixed with a quote so
                // spreadsheet apps treat it as text.
                return str_contains($html, 'Uso medicinal')
                    && str_contains($html, "PADIUSH_INST_{$instance->id}")
                    && str_contains($html, '&#039;=HYPERLINK(')
                    && ! str_contains($html, '<td>=HYPERLINK(');
            }
        );
    }
}
