<?php

namespace App\Services;

use App\Models\CatalogSpecies;
use App\Models\InstanceAnswer;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Project;
use App\Models\ProjectCapability;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LegacyResearchImporter
{
    public function __construct(private LegacyResearchWorkbook $workbook) {}

    /**
     * @return array{project_id:int,form_id:int,verification:array}
     */
    public function import(array $dataset, User $owner, string $projectName): array
    {
        if (Project::where('name', $projectName)->exists()) {
            throw new RuntimeException("A project named '{$projectName}' already exists; the import was not run.");
        }

        return DB::transaction(function () use ($dataset, $owner, $projectName) {
            $capability = ProjectCapability::where('name', 'Administrador del proyecto')->first();

            if (! $capability) {
                throw new RuntimeException('The project administrator capability is missing.');
            }

            $project = $owner->projects()->create([
                'name' => $projectName,
                'author' => $owner->name,
                'author_email' => $owner->email,
                'country' => 'El Salvador',
                'finished' => false,
                'published' => false,
                'shared' => false,
            ]);

            $project->accesses()->create([
                'user_id' => $owner->id,
                'project_capability_id' => $capability->id,
            ]);

            [$form, $identityItem, $reportSection, $items] = $this->createForm($project, $dataset);
            $species = $this->createCatalog($project, $dataset['species']);
            $instances = $this->createInstances($form, $owner, $identityItem, $dataset['records']);
            $this->createReportAnswers($reportSection, $items, $species, $instances, $dataset['records']);

            $verification = $this->verify($project, $identityItem, $reportSection, $items, $dataset);

            return [
                'project_id' => $project->id,
                'form_id' => $form->id,
                'verification' => $verification,
            ];
        }, 3);
    }

    private function createForm(Project $project, array $dataset): array
    {
        $hash = $dataset['source']['sha256'] ?? 'desconocido';
        $form = InterviewForm::create([
            'project_id' => $project->id,
            'name' => 'Entrevista etnobotánica histórica',
            'description' => "Datos importados de data_ingest.xlsx. Huella SHA-256: {$hash}",
            'is_active' => true,
        ]);

        $identitySection = InterviewSection::create([
            'interview_form_id' => $form->id,
            'name' => 'Identificación de la entrevista',
            'description' => 'Identificador conservado únicamente para trazabilidad con la investigación original.',
            'order' => 1,
            'repeatable' => false,
        ]);

        $identityItem = InterviewItem::create([
            'interview_section_id' => $identitySection->id,
            'label' => 'ID de entrevista original',
            'name' => 'id_entrevista_original',
            'type' => 'text',
            'required' => true,
            'order' => 1,
            'link_to_species' => false,
            'is_use_category' => false,
        ]);

        $reportSection = InterviewSection::create([
            'interview_form_id' => $form->id,
            'name' => 'Reportes de uso',
            'description' => 'Cada registro conserva una cita de especie y uso de la investigación original.',
            'order' => 2,
            'repeatable' => true,
        ]);

        $parts = collect($dataset['records'])
            ->pluck('partes_utilizadas')
            ->flatten()
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        $states = collect($dataset['records'])
            ->pluck('estado')
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        $definitions = [
            'report_original_id' => ['ID del reporte original', 'text', true, null, false, false],
            'categoria_uso' => ['Categoría de uso', 'select', true, ['Alimenticio', 'Medicinal', 'Ornamental', 'Económico', 'Cultural'], false, true],
            'nombre_comun' => ['Nombre común', 'text', true, null, true, false],
            'partes_utilizadas' => ['Partes utilizadas', 'multi', true, $parts, false, false],
            'estado' => ['Estado', 'select', false, $states, false, false],
            'obtencion' => ['Obtención', 'text', false, null, false, false],
            'epoca_del_ano' => ['Época del año', 'text', false, null, false, false],
            'origen_del_conocimiento' => ['Origen del conocimiento', 'text', false, null, false, false],
            'preparacion' => ['Preparación', 'text', false, null, false, false],
            'forma_de_servir' => ['Forma de servir', 'text', false, null, false, false],
            'metodos_de_administracion' => ['Métodos de administración', 'text', false, null, false, false],
            'enfermedad' => ['Enfermedad o padecimiento', 'text', false, null, false, false],
            'uso' => ['Uso económico o cultural', 'text', false, null, false, false],
        ];

        $items = [];
        $order = 1;
        foreach ($definitions as $name => [$label, $type, $required, $options, $linkToSpecies, $isUseCategory]) {
            $items[$name] = InterviewItem::create([
                'interview_section_id' => $reportSection->id,
                'label' => $label,
                'name' => $name,
                'type' => $type,
                'required' => $required,
                'order' => $order++,
                'options' => $options,
                'link_to_species' => $linkToSpecies,
                'is_use_category' => $isUseCategory,
            ]);
        }

        return [$form, $identityItem, $reportSection, $items];
    }

    /** @return array<string,CatalogSpecies> */
    private function createCatalog(Project $project, array $taxa): array
    {
        $species = [];

        foreach ($taxa as $key => $taxon) {
            $metadata = $taxon['metadata'];
            $metadata['filas_fuente'] = $taxon['source_rows'];

            $species[$key] = CatalogSpecies::create([
                'project_id' => $project->id,
                'family' => $taxon['family'],
                'genus' => $taxon['genus'],
                'name' => $taxon['name'],
                'authority' => $taxon['authority'],
                'metadata' => $metadata,
            ]);
        }

        return $species;
    }

    /** @return array<string,InterviewInstance> */
    private function createInstances(InterviewForm $form, User $owner, InterviewItem $identityItem, array $records): array
    {
        $interviewIds = collect($records)
            ->pluck('interview_original_id')
            ->unique()
            ->sort(fn ($a, $b) => (int) $a <=> (int) $b)
            ->values();
        $instances = [];

        foreach ($interviewIds as $legacyId) {
            $instance = InterviewInstance::create([
                'interview_form_id' => $form->id,
                'user_id' => $owner->id,
                'captured_at' => null,
            ]);

            InstanceAnswer::create([
                'interview_instance_id' => $instance->id,
                'interview_section_id' => $identityItem->interview_section_id,
                'interview_item_id' => $identityItem->id,
                'repeatable_index' => null,
                'answer' => $legacyId,
                'edited_at' => now(),
            ]);

            $instances[$legacyId] = $instance;
        }

        return $instances;
    }

    private function createReportAnswers(
        InterviewSection $section,
        array $items,
        array $species,
        array $instances,
        array $records
    ): void {
        $recordsByInterview = collect($records)
            ->groupBy('interview_original_id');

        foreach ($recordsByInterview as $legacyInterviewId => $interviewRecords) {
            $ordered = $interviewRecords
                ->sort(fn ($a, $b) => [(int) $a['report_original_id'], $a['source_sequence']] <=> [(int) $b['report_original_id'], $b['source_sequence']])
                ->values();

            foreach ($ordered as $repeatableIndex => $record) {
                $taxonKey = $this->workbook->taxonKey($record['family'], $record['genus'], $record['species']);
                $catalogSpecies = $species[$taxonKey] ?? null;

                if (! $catalogSpecies) {
                    throw new RuntimeException("No catalog species found for {$taxonKey}.");
                }

                foreach ($items as $name => $item) {
                    $value = $record[$name] ?? null;

                    if ($name === 'partes_utilizadas') {
                        $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                    } elseif ($value === null) {
                        continue;
                    }

                    InstanceAnswer::create([
                        'interview_instance_id' => $instances[$legacyInterviewId]->id,
                        'interview_section_id' => $section->id,
                        'interview_item_id' => $item->id,
                        'repeatable_index' => $repeatableIndex,
                        'answer' => $value,
                        'catalog_species_id' => $name === 'nombre_comun' ? $catalogSpecies->id : null,
                        'edited_at' => now(),
                    ]);
                }
            }
        }
    }

    private function verify(
        Project $project,
        InterviewItem $identityItem,
        InterviewSection $reportSection,
        array $items,
        array $dataset
    ): array {
        $instances = InterviewInstance::whereIn('interview_form_id', $project->interviewForms()->select('id'))->get();
        $instanceIds = $instances->pluck('id');
        $identityByInstance = InstanceAnswer::where('interview_item_id', $identityItem->id)
            ->get()
            ->pluck('answer', 'interview_instance_id');
        $rows = [];
        $answerQuery = InstanceAnswer::whereIn('interview_instance_id', $instanceIds)
            ->where('interview_section_id', $reportSection->id);

        foreach ((clone $answerQuery)->with(['item:id,name', 'species:id,family,genus,name'])->lazyById(500) as $answer) {
            $key = $answer->interview_instance_id.'|'.$answer->repeatable_index;

            if (! isset($rows[$key])) {
                $rows[$key] = [
                    'interview_original_id' => (string) $identityByInstance[$answer->interview_instance_id],
                    'report_original_id' => null,
                    'categoria_uso' => null,
                    'family' => null,
                    'genus' => null,
                    'species' => null,
                    'nombre_comun' => null,
                    'partes_utilizadas' => [],
                    'estado' => null,
                    'obtencion' => null,
                    'epoca_del_ano' => null,
                    'origen_del_conocimiento' => null,
                    'preparacion' => null,
                    'forma_de_servir' => null,
                    'metodos_de_administracion' => null,
                    'enfermedad' => null,
                    'uso' => null,
                ];
            }

            $name = $answer->item->name;
            if ($name === 'nombre_comun') {
                $rows[$key]['family'] = $answer->species?->family;
                $rows[$key]['genus'] = $answer->species?->genus;
                $rows[$key]['species'] = $answer->species?->name;
                $rows[$key]['nombre_comun'] = $answer->answer;
            } elseif ($name === 'partes_utilizadas') {
                $rows[$key]['partes_utilizadas'] = json_decode($answer->answer ?? '[]', true, 512, JSON_THROW_ON_ERROR);
            } else {
                $rows[$key][$name] = $answer->answer;
            }
        }

        $rows = array_values($rows);

        $sourceFingerprints = $this->fingerprints($dataset['records']);
        $databaseFingerprints = $this->fingerprints($rows);

        if ($sourceFingerprints !== $databaseFingerprints) {
            throw new RuntimeException('Imported row fingerprints do not match the source workbook.');
        }

        $reportIds = collect($rows)->pluck('report_original_id')->unique();
        $analyticalCombinations = collect($rows)->map(fn ($row) => implode('|', [
            $row['interview_original_id'],
            $this->workbook->taxonKey($row['family'], $row['genus'], $row['species']),
            $row['categoria_uso'],
        ]))->unique()->count();

        $verification = [
            'instances' => $instances->count(),
            'records' => count($rows),
            'reports' => $reportIds->count(),
            'catalog_species' => $project->catalogSpecies()->count(),
            'linked_species_answers' => (clone $answerQuery)->where('interview_item_id', $items['nombre_comun']->id)->whereNotNull('catalog_species_id')->count(),
            'unlinked_species_answers' => (clone $answerQuery)->where('interview_item_id', $items['nombre_comun']->id)->whereNull('catalog_species_id')->count(),
            'analytical_combinations' => $analyticalCombinations,
            'fingerprints_match' => true,
        ];

        $expected = [
            'instances' => $dataset['summary']['interviews'],
            'records' => $dataset['summary']['records'],
            'reports' => $dataset['summary']['reports'],
            'catalog_species' => $dataset['summary']['taxa'],
            'linked_species_answers' => $dataset['summary']['records'],
            'unlinked_species_answers' => 0,
            'analytical_combinations' => $dataset['summary']['analytical_combinations'],
            'fingerprints_match' => true,
        ];

        if ($verification !== $expected) {
            throw new RuntimeException(
                'Database reconciliation failed. Expected: '.json_encode($expected).' Actual: '.json_encode($verification)
            );
        }

        return $verification;
    }

    private function fingerprints(array $rows): array
    {
        $fingerprints = array_map(function ($row) {
            $canonical = [
                'interview_original_id' => (string) $row['interview_original_id'],
                'report_original_id' => (string) $row['report_original_id'],
                'categoria_uso' => $row['categoria_uso'],
                'taxon' => $this->workbook->taxonKey($row['family'], $row['genus'], $row['species']),
                'nombre_comun' => $row['nombre_comun'],
                'partes_utilizadas' => array_values($row['partes_utilizadas'] ?? []),
                'estado' => $row['estado'] ?? null,
                'obtencion' => $row['obtencion'] ?? null,
                'epoca_del_ano' => $row['epoca_del_ano'] ?? null,
                'origen_del_conocimiento' => $row['origen_del_conocimiento'] ?? null,
                'preparacion' => $row['preparacion'] ?? null,
                'forma_de_servir' => $row['forma_de_servir'] ?? null,
                'metodos_de_administracion' => $row['metodos_de_administracion'] ?? null,
                'enfermedad' => $row['enfermedad'] ?? null,
                'uso' => $row['uso'] ?? null,
            ];

            return hash('sha256', json_encode($canonical, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        }, $rows);

        sort($fingerprints);

        return $fingerprints;
    }
}
