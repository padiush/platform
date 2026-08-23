<?php

namespace Tests\Unit;

use App\Models\CatalogSpecies;
use App\Models\InstanceAnswer;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Media;
use App\Models\Project;
use App\Models\User;
use App\Services\InterviewDataTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InterviewDataTableTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    private InterviewForm $form;

    private InterviewDataTable $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->form = InterviewForm::factory()->create(['project_id' => $this->project->id]);
        $this->table = new InterviewDataTable;
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

    private function interview(?User $user = null): InterviewInstance
    {
        return InterviewInstance::factory()->create([
            'interview_form_id' => $this->form->id,
            'user_id' => ($user ?? User::factory()->create())->id,
        ]);
    }

    private function answer(
        InterviewInstance $instance,
        InterviewSection $section,
        InterviewItem $item,
        ?string $value,
        ?int $index = null,
        ?int $speciesId = null
    ): InstanceAnswer {
        return InstanceAnswer::create([
            'interview_instance_id' => $instance->id,
            'interview_section_id' => $section->id,
            'interview_item_id' => $item->id,
            'repeatable_index' => $index,
            'answer' => $value,
            'catalog_species_id' => $speciesId,
        ]);
    }

    private function rows(InterviewSection $section, array $filters = [])
    {
        return $this->table->rows($this->form, $section, $filters, 1);
    }

    public function test_non_repeatable_section_has_one_row_per_interview()
    {
        $section = $this->section();
        $item = $this->item($section);

        $a = $this->interview();
        $this->answer($a, $section, $item, 'guarumo');
        $b = $this->interview();
        $this->answer($b, $section, $item, 'ceiba');

        $result = $this->rows($section);

        $this->assertSame(2, $result->total());
        $values = collect($result->items())->map(fn ($r) => $r['cells'][$item->id]['value']);
        $this->assertEqualsCanonicalizing(['guarumo', 'ceiba'], $values->all());
    }

    public function test_repeatable_section_has_one_row_per_record()
    {
        $section = $this->section(true);
        $item = $this->item($section);

        $instance = $this->interview();
        $this->answer($instance, $section, $item, 'first', 0);
        $this->answer($instance, $section, $item, 'second', 1);

        $result = $this->rows($section);

        $this->assertSame(2, $result->total());
        $this->assertEqualsCanonicalizing(
            [0, 1],
            collect($result->items())->pluck('record_index')->all(),
        );
    }

    public function test_multi_cell_is_parsed_to_an_array()
    {
        $section = $this->section();
        $item = $this->item($section, 'multi');

        $instance = $this->interview();
        $this->answer($instance, $section, $item, json_encode(['Alimento', 'Medicina']));

        $cell = $this->rows($section)->items()[0]['cells'][$item->id];

        $this->assertSame('multi', $cell['kind']);
        $this->assertSame(['Alimento', 'Medicina'], $cell['values']);
    }

    public function test_unanswered_cell_is_null()
    {
        $section = $this->section();
        $answered = $this->item($section);
        $blank = $this->item($section);

        $instance = $this->interview();
        $this->answer($instance, $section, $answered, 'value');

        $cells = $this->rows($section)->items()[0]['cells'];

        $this->assertNotNull($cells[$answered->id]);
        $this->assertNull($cells[$blank->id]);
    }

    public function test_species_cell_preserves_the_recorded_name()
    {
        $section = $this->section();
        $item = $this->item($section, 'text', ['link_to_species' => true]);
        $species = CatalogSpecies::factory()->create([
            'project_id' => $this->project->id,
            'genus' => 'Cecropia',
            'name' => 'obtusifolia',
        ]);

        $instance = $this->interview();
        $this->answer($instance, $section, $item, 'guarumo', null, $species->id);

        $cell = $this->rows($section)->items()[0]['cells'][$item->id];

        $this->assertSame('species', $cell['kind']);
        $this->assertSame('guarumo', $cell['value']);
    }

    public function test_interviewer_filter_narrows_rows()
    {
        $section = $this->section();
        $item = $this->item($section);

        $alice = User::factory()->create();
        $this->answer($this->interview($alice), $section, $item, 'a');
        $this->answer($this->interview(), $section, $item, 'b');

        $result = $this->rows($section, ['interviewer' => $alice->id]);

        $this->assertSame(1, $result->total());
        $this->assertSame('a', $result->items()[0]['cells'][$item->id]['value']);
    }

    public function test_summary_counts_categorical_values()
    {
        $section = $this->section();
        $item = $this->item($section, 'select');

        foreach (['Alimento', 'Alimento', 'Medicina'] as $value) {
            $this->answer($this->interview(), $section, $item, $value);
        }

        $summary = collect($this->table->summary($this->form, $section))
            ->firstWhere('item_id', $item->id);

        $this->assertSame('categorical', $summary['kind']);
        $this->assertSame(
            ['Alimento' => 2, 'Medicina' => 1],
            collect($summary['data'])->pluck('count', 'label')->all(),
        );
    }

    public function test_summary_explodes_multi_values()
    {
        $section = $this->section();
        $item = $this->item($section, 'multi');

        $this->answer($this->interview(), $section, $item, json_encode(['Hoja', 'Corteza']));
        $this->answer($this->interview(), $section, $item, json_encode(['Hoja']));

        $summary = collect($this->table->summary($this->form, $section))
            ->firstWhere('item_id', $item->id);

        $this->assertSame(
            ['Hoja' => 2, 'Corteza' => 1],
            collect($summary['data'])->pluck('count', 'label')->all(),
        );
    }

    public function test_summary_computes_numeric_stats()
    {
        $section = $this->section();
        $item = $this->item($section, 'number');

        foreach ([2, 4, 6] as $value) {
            $this->answer($this->interview(), $section, $item, (string) $value);
        }

        $summary = collect($this->table->summary($this->form, $section))
            ->firstWhere('item_id', $item->id);

        $this->assertSame('number', $summary['kind']);
        $this->assertSame(3, $summary['stats']['count']);
        $this->assertEquals(2, $summary['stats']['min']);
        $this->assertEquals(6, $summary['stats']['max']);
        $this->assertEquals(4, $summary['stats']['mean']);
        $this->assertEquals(4, $summary['stats']['median']);
    }

    public function test_summary_exposes_available_types_and_default_per_kind()
    {
        $section = $this->section();
        $select = $this->item($section, 'select');
        $number = $this->item($section, 'number');
        $date = $this->item($section, 'date');
        $this->answer($this->interview(), $section, $select, 'A');
        $this->answer($this->interview(), $section, $number, '5');
        $this->answer($this->interview(), $section, $date, '2026-01-01');

        $byId = collect($this->table->summary($this->form, $section))->keyBy('item_id');

        $this->assertSame(['bar', 'pie', 'table'], $byId[$select->id]['available']);
        $this->assertSame(['bar', 'table'], $byId[$number->id]['available']);
        $this->assertSame(['bar', 'line', 'table'], $byId[$date->id]['available']);
        $this->assertSame('bar', $byId[$select->id]['chart_type']);
    }

    public function test_summary_reports_truncation_totals()
    {
        $section = $this->section();
        $item = $this->item($section, 'select');
        foreach (['A', 'A', 'B'] as $value) {
            $this->answer($this->interview(), $section, $item, $value);
        }

        $summary = collect($this->table->summary($this->form, $section))
            ->firstWhere('item_id', $item->id);

        $this->assertSame(2, $summary['total_distinct']);
        $this->assertSame(3, $summary['total_count']);
    }

    public function test_date_summary_buckets_by_month_or_year_by_span()
    {
        $section = $this->section();

        $monthly = $this->item($section, 'date');
        $this->answer($this->interview(), $section, $monthly, '2026-01-15');
        $this->answer($this->interview(), $section, $monthly, '2026-02-20');

        $yearly = $this->item($section, 'date');
        $this->answer($this->interview(), $section, $yearly, '2020-01-01');
        $this->answer($this->interview(), $section, $yearly, '2026-01-01');

        $byId = collect($this->table->summary($this->form, $section))->keyBy('item_id');

        $this->assertSame('month', $byId[$monthly->id]['bucket']);
        $this->assertSame('year', $byId[$yearly->id]['bucket']);
    }

    public function test_kind_for_maps_item_types()
    {
        $section = $this->section();

        $this->assertSame('number', InterviewDataTable::kindFor($this->item($section, 'number')));
        $this->assertSame('date', InterviewDataTable::kindFor($this->item($section, 'date')));
        $this->assertSame('categorical', InterviewDataTable::kindFor($this->item($section, 'multi')));
        $this->assertSame('species', InterviewDataTable::kindFor(
            $this->item($section, 'text', ['link_to_species' => true])
        ));
    }

    public function test_summary_counts_recorded_names_for_species_linked_fields()
    {
        $section = $this->section();
        $item = $this->item($section, 'text', ['link_to_species' => true]);
        $species = CatalogSpecies::factory()->create([
            'project_id' => $this->project->id,
            'genus' => 'Inga',
            'name' => 'edulis',
        ]);

        $this->answer($this->interview(), $section, $item, 'guaba', null, $species->id);
        $this->answer($this->interview(), $section, $item, 'guama', null, $species->id);
        $this->answer($this->interview(), $section, $item, 'guaba');

        $summary = collect($this->table->summary($this->form, $section))
            ->firstWhere('item_id', $item->id);

        $this->assertSame('species', $summary['kind']);
        $this->assertSame(
            ['guaba' => 2, 'guama' => 1],
            collect($summary['data'])->pluck('count', 'label')->all(),
        );
    }

    public function test_a_row_says_how_much_media_its_interview_carries()
    {
        $section = $this->section();
        $item = $this->item($section);
        $instance = $this->interview();
        $this->answer($instance, $section, $item, 'algo');

        Media::create([
            'interview_instance_id' => $instance->id,
            'client_id' => (string) Str::uuid(),
            'kind' => Media::KIND_PHOTO,
            'storage_disk' => 's3',
            'storage_key' => 'k.jpg',
            'content_type' => 'image/jpeg',
            'status' => Media::STATUS_STORED,
        ]);

        $rows = $this->table->rows($this->form, $section, [], 1)->items();

        // Read off a grouped count rather than a query per row. The closure
        // has to capture that map — `?? 0` would otherwise hide its absence,
        // which is exactly how this shipped as zero once.
        $this->assertSame(1, $rows[0]['media_count']);
    }
}
