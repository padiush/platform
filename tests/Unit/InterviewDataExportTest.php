<?php

namespace Tests\Unit;

use App\Models\CatalogSpecies;
use App\Models\InstanceAnswer;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Project;
use App\Services\InterviewDataExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterviewDataExportTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    private InterviewForm $form;

    private InterviewDataExport $export;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->form = InterviewForm::factory()->create(['project_id' => $this->project->id]);
        $this->export = new InterviewDataExport;
    }

    private function section(bool $repeatable = false): InterviewSection
    {
        return InterviewSection::factory()->create([
            'interview_form_id' => $this->form->id,
            'repeatable' => $repeatable,
        ]);
    }

    private function item(InterviewSection $section, string $type = 'text', array $extra = []): InterviewItem
    {
        return InterviewItem::factory()->create(array_merge([
            'interview_section_id' => $section->id,
            'type' => $type,
        ], $extra));
    }

    private function interview(): InterviewInstance
    {
        return InterviewInstance::factory()->create(['interview_form_id' => $this->form->id]);
    }

    private function answer($instance, $section, $item, ?string $value, ?int $index = null, ?int $speciesId = null): void
    {
        InstanceAnswer::create([
            'interview_instance_id' => $instance->id,
            'interview_section_id' => $section->id,
            'interview_item_id' => $item->id,
            'repeatable_index' => $index,
            'answer' => $value,
            'catalog_species_id' => $speciesId,
        ]);
    }

    public function test_missing_answer_yields_an_empty_cell_keeping_columns_aligned()
    {
        $section = $this->section();
        $a = $this->item($section);
        $b = $this->item($section);

        $instance = $this->interview();
        $this->answer($instance, $section, $a, 'value-a');
        // $b is unanswered for this instance.

        $matrix = $this->export->customMatrix($this->form, collect([$a, $b]), false);

        $this->assertCount(3, $matrix['headers']); // Entrevista + 2 fields
        $this->assertCount(3, $matrix['rows'][0]); // never shifts
        $this->assertSame('value-a', $matrix['rows'][0][1]);
        $this->assertSame('', $matrix['rows'][0][2]);
    }

    public function test_repeatable_records_come_from_the_selected_fields_only()
    {
        $selected = $this->section(true);
        $field = $this->item($selected);

        $other = $this->section(true);
        $otherField = $this->item($other);

        $instance = $this->interview();
        // Selected field answered at indices 0 and 1.
        $this->answer($instance, $selected, $field, 'r0', 0);
        $this->answer($instance, $selected, $field, 'r1', 1);
        // A different section has more indices — must NOT inflate the row count.
        $this->answer($instance, $other, $otherField, 'x2', 2);
        $this->answer($instance, $other, $otherField, 'x3', 3);

        $matrix = $this->export->customMatrix($this->form, collect([$field]), true);

        $this->assertCount(2, $matrix['rows']);
    }

    public function test_multi_cell_is_joined_and_missing_is_empty()
    {
        $section = $this->section();
        $item = $this->item($section, 'multi');

        $instance = $this->interview();
        $this->answer($instance, $section, $item, json_encode(['Hoja', 'Corteza']));

        $matrix = $this->export->customMatrix($this->form, collect([$item]), false);

        $this->assertSame('Hoja; Corteza', $matrix['rows'][0][1]);
    }

    public function test_formula_injection_is_neutralized()
    {
        $section = $this->section();
        $item = $this->item($section);

        $instance = $this->interview();
        $this->answer($instance, $section, $item, '=HYPERLINK("http://evil")');

        $matrix = $this->export->customMatrix($this->form, collect([$item]), false);

        $this->assertStringStartsWith("'=", $matrix['rows'][0][1]);
    }

    public function test_preview_reports_counts_and_respects_the_limit()
    {
        $section = $this->section();
        $item = $this->item($section);

        foreach (range(1, 15) as $n) {
            $this->answer($this->interview(), $section, $item, "v{$n}");
        }

        $preview = $this->export->customPreview($this->form, collect([$item]), false, 10);

        $this->assertSame(15, $preview['record_count']);
        $this->assertSame(15, $preview['instance_count']);
        $this->assertCount(10, $preview['rows']);
    }

    public function test_ethnobotany_matrix_builds_presence_columns()
    {
        $section = $this->section();
        $category = $this->item($section, 'select');
        $speciesField = $this->item($section, 'text', ['link_to_species' => true]);

        $species = CatalogSpecies::factory()->create([
            'project_id' => $this->project->id,
            'genus' => 'Inga',
            'name' => 'edulis',
        ]);
        $instance = $this->interview();
        $this->answer($instance, $section, $speciesField, 'guaba', null, $species->id);
        $this->answer($instance, $section, $category, 'Alimento');

        $matrix = $this->export->ethnobotanyMatrix($category);

        $this->assertSame(['informant', 'sp_name', 'alimento'], $matrix['headers']);
        $this->assertSame('Inga edulis', $matrix['rows'][0][1]);
        $this->assertSame('1', $matrix['rows'][0][2]);
    }
}
