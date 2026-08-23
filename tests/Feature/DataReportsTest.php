<?php

namespace Tests\Feature;

use App\Exports\IndicesReportExport;
use App\Exports\ReferencesSheet;
use App\Models\CatalogSpecies;
use App\Models\CollectingPermit;
use App\Models\Determination;
use App\Models\FieldRecord;
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

class DataReportsTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    private InterviewForm $form;

    private InterviewSection $section;

    private InterviewItem $taxon;

    private InterviewItem $use;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->form = InterviewForm::factory()->create([
            'project_id' => $this->project->id,
        ]);
        $this->section = InterviewSection::factory()->create([
            'interview_form_id' => $this->form->id,
            'repeatable' => true,
        ]);
        $this->taxon = InterviewItem::factory()->create([
            'interview_section_id' => $this->section->id,
            'link_to_species' => true,
        ]);
        $this->use = InterviewItem::factory()->create([
            'interview_section_id' => $this->section->id,
            'is_use_category' => true,
        ]);
    }

    public function test_member_with_generate_reports_sees_the_indices()
    {
        $user = $this->userWithCapability($this->project, 'generate_reports');
        $species = CatalogSpecies::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'edulis',
        ]);
        $this->cite($this->interview(), 0, $species, 'food');

        $response = $this->actingAs($user)->get(
            route('data.reports', ['project' => $this->project])
        );

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                // Page built in the frontend batch; assert the props here.
                ->component('Data/Reports', false)
                ->where('indices.informants', 1)
                ->has('indices.species', 1)
                ->where('indices.species.0.species.name', 'edulis')
                ->has('indices.use_categories', 1)
        );
    }

    /** Back a taxon with a collection, so the species table has evidence. */
    private function voucher(
        CatalogSpecies $species,
        string $accession,
        ?CollectingPermit $permit = null
    ): FieldRecord {
        $fieldRecord = FieldRecord::factory()->create([
            'project_id' => $this->project->id,
            'accession_number' => $accession,
            'collecting_permit_id' => $permit?->id,
        ]);

        Determination::factory()->create([
            'field_record_id' => $fieldRecord->id,
            'catalog_species_id' => $species->id,
            'is_current' => true,
        ]);

        return $fieldRecord;
    }

    public function test_the_species_table_carries_the_voucher_and_the_permit()
    {
        $user = $this->userWithCapability($this->project, 'generate_reports');
        $species = CatalogSpecies::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'edulis',
        ]);
        $this->cite($this->interview(), 0, $species, 'food');

        $permit = CollectingPermit::factory()->create([
            'project_id' => $this->project->id,
            'authority' => 'MARN',
            'reference' => 'RES-042-2026',
        ]);
        $this->voucher($species, 'MML-0001', $permit);

        $csv = $this->actingAs($user)->get(route('data.reports.download', [
            'project' => $this->project,
            'format' => 'csv',
        ]))->streamedContent();

        // The column a species table is expected to carry, and the authority
        // the material was taken under.
        $this->assertStringContainsString('Voucher No.', $csv);
        $this->assertStringContainsString('MML-0001', $csv);
        $this->assertStringContainsString('MARN · RES-042-2026', $csv);
    }

    public function test_a_taxon_with_no_collection_exports_an_empty_voucher_cell()
    {
        $user = $this->userWithCapability($this->project, 'generate_reports');
        $species = CatalogSpecies::factory()->create([
            'project_id' => $this->project->id,
            'genus' => 'Inga',
            'name' => 'edulis',
        ]);
        $this->cite($this->interview(), 0, $species, 'food');

        $csv = $this->actingAs($user)->get(route('data.reports.download', [
            'project' => $this->project,
            'format' => 'csv',
        ]))->streamedContent();

        // Missing evidence must not drop the row or shift the columns.
        $this->assertStringContainsString('Voucher No.', $csv);
        $this->assertStringContainsString('Inga', $csv);
    }

    public function test_the_report_page_states_coverage()
    {
        $user = $this->userWithCapability($this->project, 'generate_reports');
        $backed = CatalogSpecies::factory()->create(['project_id' => $this->project->id]);
        CatalogSpecies::factory()->create(['project_id' => $this->project->id]);
        $this->cite($this->interview(), 0, $backed, 'food');

        $this->voucher($backed, 'MML-0001');
        FieldRecord::factory()->create([
            'project_id' => $this->project->id,
            'permit_exemption' => 'market',
        ]);

        $this->actingAs($user)
            ->get(route('data.reports', ['project' => $this->project]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('evidence.taxa_total', 2)
                ->where('evidence.taxa_vouchered', 1)
                ->where('evidence.records_total', 2)
                ->where('evidence.records_permit_exempt', 1)
                ->where('evidence.records_permit_unrecorded', 1)
            );
    }

    public function test_outsider_cannot_open_reports()
    {
        $response = $this->actingAs($this->outsider())->get(
            route('data.reports', ['project' => $this->project])
        );

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('error', 'No tienes acceso a este proyecto.');
    }

    public function test_report_download_returns_a_file()
    {
        $user = $this->userWithCapability($this->project, 'generate_reports');
        $species = CatalogSpecies::factory()->create([
            'project_id' => $this->project->id,
        ]);
        $this->cite($this->interview(), 0, $species, 'food');

        $response = $this->actingAs($user)->get(
            route('data.reports.download', [
                'project' => $this->project,
                'format' => 'csv',
            ])
        );

        $response->assertOk();
        $this->assertStringContainsString(
            'attachment',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_xlsx_report_includes_a_references_sheet()
    {
        Excel::fake();
        $this->freezeTime();

        $user = $this->userWithCapability($this->project, 'generate_reports');
        $species = CatalogSpecies::factory()->create([
            'project_id' => $this->project->id,
        ]);
        $this->cite($this->interview(), 0, $species, 'food');

        $this->actingAs($user)->get(
            route('data.reports.download', [
                'project' => $this->project,
                'format' => 'xlsx',
            ])
        )->assertOk();

        $filename = Str::slug($this->project->name)
            .'-indices-'.now()->format('Y-m-d').'.xlsx';

        Excel::assertDownloaded(
            $filename,
            fn (IndicesReportExport $export) => count($export->sheets()) === 2
                && $export->sheets()[1] instanceof ReferencesSheet
        );
    }

    private function interview(): InterviewInstance
    {
        return InterviewInstance::factory()->create([
            'interview_form_id' => $this->form->id,
        ]);
    }

    private function cite(
        InterviewInstance $interview,
        int $index,
        CatalogSpecies $species,
        string $use
    ): void {
        InstanceAnswer::create([
            'interview_instance_id' => $interview->id,
            'interview_section_id' => $this->section->id,
            'interview_item_id' => $this->taxon->id,
            'repeatable_index' => $index,
            'answer' => 'folk name',
            'catalog_species_id' => $species->id,
        ]);

        InstanceAnswer::create([
            'interview_instance_id' => $interview->id,
            'interview_section_id' => $this->section->id,
            'interview_item_id' => $this->use->id,
            'repeatable_index' => $index,
            'answer' => $use,
        ]);
    }
}
