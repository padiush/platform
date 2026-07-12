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
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

class DataExportTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    private InterviewForm $form;

    private InterviewSection $section;

    private InterviewItem $field;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->form = InterviewForm::factory()->create(['project_id' => $this->project->id]);
        $this->section = InterviewSection::factory()->create([
            'interview_form_id' => $this->form->id,
            'repeatable' => false,
        ]);
        $this->field = InterviewItem::factory()->create([
            'interview_section_id' => $this->section->id,
            'label' => 'Uso medicinal',
        ]);
    }

    private function reporter()
    {
        return $this->userWithCapability($this->project, 'generate_reports');
    }

    private function filename(string $kind, string $format): string
    {
        return Str::slug($this->project->name)."-{$kind}-".now()->format('Y-m-d').".{$format}";
    }

    private function answer(string $value): InterviewInstance
    {
        $instance = InterviewInstance::factory()->create(['interview_form_id' => $this->form->id]);
        InstanceAnswer::create([
            'interview_instance_id' => $instance->id,
            'interview_section_id' => $this->section->id,
            'interview_item_id' => $this->field->id,
            'answer' => $value,
        ]);

        return $instance;
    }

    public function test_export_builder_page_renders()
    {
        $response = $this->actingAs($this->reporter())->get(
            route('data.export', $this->project)
        );

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Data/Export', false)
            ->has('forms', 1)
        );
    }

    public function test_custom_preview_returns_columns_counts_and_rows()
    {
        $this->answer('guaba');
        $this->answer('ceiba');

        $response = $this->actingAs($this->reporter())->getJson(
            route('data.export.preview', [
                'project' => $this->project,
                'mode' => 'custom',
                'form_id' => $this->form->id,
                'selected_fields' => json_encode([$this->field->id]),
            ])
        );

        $response->assertOk();
        $response->assertJsonPath('record_count', 2);
        $response->assertJsonPath('instance_count', 2);
        $response->assertJsonCount(2, 'rows');
    }

    public function test_custom_preview_rejects_a_repeatable_mix()
    {
        $repeatable = InterviewSection::factory()->create([
            'interview_form_id' => $this->form->id,
            'repeatable' => true,
        ]);
        $repeatableField = InterviewItem::factory()->create([
            'interview_section_id' => $repeatable->id,
        ]);
        $this->answer('x');

        $response = $this->actingAs($this->reporter())->getJson(
            route('data.export.preview', [
                'project' => $this->project,
                'mode' => 'custom',
                'form_id' => $this->form->id,
                'selected_fields' => json_encode([$this->field->id, $repeatableField->id]),
            ])
        );

        $response->assertStatus(422);
    }

    public function test_custom_download_neutralizes_formulas_as_xlsx()
    {
        Excel::fake();
        $instance = $this->answer('=HYPERLINK("http://evil")');

        $response = $this->actingAs($this->reporter())->post(
            route('data.export.download', $this->project),
            ['mode' => 'custom', 'form_id' => $this->form->id, 'format' => 'xlsx',
                'selected_fields' => json_encode([$this->field->id])]
        );

        $response->assertStatus(200);
        Excel::assertDownloaded(
            $this->filename('custom', 'xlsx'),
            function (CustomExport $export) use ($instance) {
                $rows = $export->array();

                return $export->headings()[1] === 'Uso medicinal'
                    && $rows[0][0] === "PADIUSH_INST_{$instance->id}"
                    && str_starts_with($rows[0][1], "'=");
            }
        );
    }

    public function test_custom_download_supports_csv()
    {
        Excel::fake();
        $this->answer('guaba');

        $this->actingAs($this->reporter())->post(
            route('data.export.download', $this->project),
            ['mode' => 'custom', 'form_id' => $this->form->id, 'format' => 'csv',
                'selected_fields' => json_encode([$this->field->id])]
        )->assertStatus(200);

        Excel::assertDownloaded($this->filename('custom', 'csv'));
    }

    public function test_ethnobotanyr_download_builds_the_matrix()
    {
        Excel::fake();

        $category = InterviewItem::factory()->create(['interview_section_id' => $this->section->id]);
        $speciesField = InterviewItem::factory()->create([
            'interview_section_id' => $this->section->id,
            'link_to_species' => true,
        ]);
        $species = CatalogSpecies::factory()->create([
            'project_id' => $this->project->id, 'genus' => 'Inga', 'name' => 'edulis',
        ]);
        $instance = InterviewInstance::factory()->create(['interview_form_id' => $this->form->id]);
        InstanceAnswer::create([
            'interview_instance_id' => $instance->id, 'interview_section_id' => $this->section->id,
            'interview_item_id' => $speciesField->id, 'catalog_species_id' => $species->id, 'answer' => 'guaba',
        ]);
        InstanceAnswer::create([
            'interview_instance_id' => $instance->id, 'interview_section_id' => $this->section->id,
            'interview_item_id' => $category->id, 'answer' => 'Alimento',
        ]);

        $this->actingAs($this->reporter())->post(
            route('data.export.download', $this->project),
            ['mode' => 'ethnobotanyr', 'form_id' => $this->form->id, 'field_id' => $category->id]
        )->assertStatus(200);

        Excel::assertDownloaded(
            $this->filename('ethnobotanyr', 'xlsx'),
            fn (EthnobotanyRExport $export) => $export->headings() === ['informant', 'sp_name', 'alimento']
                && $export->array()[0][1] === 'Inga edulis'
                && $export->array()[0][2] === '1'
        );
    }

    public function test_outsider_cannot_export()
    {
        $this->answer('guaba');

        $this->actingAs($this->outsider())->get(
            route('data.export', $this->project)
        )->assertRedirect(route('projects.index'));
    }
}
