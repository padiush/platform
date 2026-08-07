<?php

namespace Tests\Feature;

use App\Models\CatalogSpecies;
use App\Models\InstanceAnswer;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\Project;
use App\Models\User;
use App\Services\LegacyResearchImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyResearchImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_spanish_rows_links_species_and_reconciles_fingerprints(): void
    {
        $owner = User::factory()->create([
            'name' => 'Investigadora',
            'email' => 'investigadora@example.com',
        ]);
        $dataset = $this->dataset();

        $result = app(LegacyResearchImporter::class)->import(
            $dataset,
            $owner,
            'Investigación histórica'
        );

        $this->assertSame([
            'instances' => 1,
            'records' => 2,
            'reports' => 1,
            'catalog_species' => 2,
            'linked_species_answers' => 2,
            'unlinked_species_answers' => 0,
            'analytical_combinations' => 2,
            'fingerprints_match' => true,
        ], $result['verification']);

        $project = Project::findOrFail($result['project_id']);
        $this->assertSame('Investigación histórica', $project->name);
        $this->assertSame('El Salvador', $project->country);
        $this->assertSame(1, InterviewInstance::count());

        $form = InterviewForm::findOrFail($result['form_id']);
        $this->assertSame('Entrevista etnobotánica histórica', $form->name);

        $speciesItem = InterviewItem::where('name', 'nombre_comun')->firstOrFail();
        $categoryItem = InterviewItem::where('name', 'categoria_uso')->firstOrFail();
        $partsItem = InterviewItem::where('name', 'partes_utilizadas')->firstOrFail();
        $this->assertTrue($speciesItem->link_to_species);
        $this->assertTrue($categoryItem->is_use_category);
        $this->assertSame(['Bulbo', 'Fruto'], $partsItem->options);

        $musa = CatalogSpecies::where('genus', 'Musa')->firstOrFail();
        $this->assertSame(['guineo', 'plátano'], $musa->metadata['nombres_comunes']);
        $this->assertSame('Exógena', $musa->metadata['origen']);

        $commonAnswers = InstanceAnswer::where('interview_item_id', $speciesItem->id)->get();
        $this->assertSame(['guineo', 'ajo'], $commonAnswers->pluck('answer')->all());
        $this->assertSame(2, $commonAnswers->whereNotNull('catalog_species_id')->count());

        $raw = DB::table('instance_answers')->where('id', $commonAnswers->first()->id)->value('answer');
        $this->assertNotSame('guineo', $raw);
    }

    private function dataset(): array
    {
        $base = [
            'source_sequence' => 0,
            'source_sheet' => 'Reportes Alimenticio',
            'source_row' => 2,
            'interview_original_id' => '1',
            'report_original_id' => '10',
            'categoria_uso' => 'Alimenticio',
            'estado' => 'Fresco',
            'obtencion' => 'Colecta libre',
            'epoca_del_ano' => 'Todo el año',
            'origen_del_conocimiento' => 'Mamá',
            'preparacion' => 'Cocido',
            'forma_de_servir' => 'Asado',
            'metodos_de_administracion' => null,
            'enfermedad' => null,
            'uso' => null,
        ];

        $records = [
            array_merge($base, [
                'family' => 'Musaceae',
                'genus' => 'Musa',
                'species' => 'x paradisiaca',
                'nombre_comun' => 'guineo',
                'partes_utilizadas' => ['Fruto'],
            ]),
            array_merge($base, [
                'source_sequence' => 1,
                'source_row' => 3,
                'family' => 'Amaryllidaceae',
                'genus' => 'Allium',
                'species' => 'sativum',
                'nombre_comun' => 'ajo',
                'partes_utilizadas' => ['Bulbo'],
            ]),
        ];

        return [
            'source' => ['filename' => 'data_ingest.xlsx', 'sha256' => str_repeat('a', 64)],
            'species' => [
                'musaceae|musa|x paradisiaca' => [
                    'family' => 'Musaceae',
                    'genus' => 'Musa',
                    'name' => 'x paradisiaca',
                    'authority' => 'L.',
                    'metadata' => [
                        'nombres_comunes' => ['guineo', 'plátano'],
                        'habito' => 'arbusto',
                        'origen' => 'Exógena',
                        'estado_uicn' => 'No Evaluado',
                        'estado_el_salvador' => 'No evaluado',
                        'fuente' => 'data_ingest.xlsx',
                    ],
                    'source_rows' => [2, 3],
                ],
                'amaryllidaceae|allium|sativum' => [
                    'family' => 'Amaryllidaceae',
                    'genus' => 'Allium',
                    'name' => 'sativum',
                    'authority' => 'L.',
                    'metadata' => [
                        'nombres_comunes' => ['ajo'],
                        'habito' => 'Hierba',
                        'origen' => 'Exógena',
                        'estado_uicn' => 'No Evaluado',
                        'estado_el_salvador' => 'No evaluado',
                        'fuente' => 'data_ingest.xlsx',
                    ],
                    'source_rows' => [4],
                ],
            ],
            'records' => $records,
            'summary' => [
                'records' => 2,
                'interviews' => 1,
                'reports' => 1,
                'taxa' => 2,
                'analytical_combinations' => 2,
            ],
        ];
    }
}
