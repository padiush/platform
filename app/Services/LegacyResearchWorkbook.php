<?php

namespace App\Services;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class LegacyResearchWorkbook
{
    public const EXPECTED = [
        'records' => 2457,
        'interviews' => 89,
        'reports' => 2067,
        'taxa' => 190,
        'master_species_rows' => 191,
        'multi_species_reports' => 230,
        'analytical_combinations' => 2160,
        'categories' => [
            'Alimenticio' => 739,
            'Medicinal' => 1027,
            'Ornamental' => 135,
            'Económico' => 395,
            'Cultural' => 161,
        ],
        'formula_only_rows' => [
            'Alimenticio' => 21,
            'Medicinal' => 0,
            'Ornamental' => 0,
            'Económico' => 0,
            'Cultural' => 0,
        ],
    ];

    private const REPORT_SHEETS = [
        'Reportes Alimenticio' => 'Alimenticio',
        'Reportes Medicinal' => 'Medicinal',
        'Reportes Ornamental' => 'Ornamental',
        'Reportes Económico' => 'Económico',
        'Reportes Cultural' => 'Cultural',
    ];

    /**
     * @return array{source:array, species:array<string,array>, records:array<int,array>, summary:array}
     */
    public function read(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException("Workbook is not readable: {$path}");
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $workbook = $reader->load($path);

        try {
            $species = $this->readSpecies($workbook->getSheetByName('Especies'));
            $records = [];
            $formulaOnlyRows = [];
            $sequence = 0;

            foreach (self::REPORT_SHEETS as $sheetName => $category) {
                $sheet = $workbook->getSheetByName($sheetName);

                if (! $sheet) {
                    throw new RuntimeException("Missing required sheet: {$sheetName}");
                }

                [$sheetRecords, $ignored] = $this->readReports(
                    $sheet->toArray(null, false, false, false),
                    $sheetName,
                    $category,
                    $sequence
                );
                $records = array_merge($records, $sheetRecords);
                $formulaOnlyRows[$category] = $ignored;
                $sequence += count($sheetRecords);
            }

            $summary = $this->summarize($species, $records, $formulaOnlyRows);

            return [
                'source' => [
                    'filename' => basename($path),
                    'sha256' => hash_file('sha256', $path),
                ],
                'species' => $species,
                'records' => $records,
                'summary' => $summary,
            ];
        } finally {
            $workbook->disconnectWorksheets();
            unset($workbook);
        }
    }

    public function assertExpected(array $dataset): void
    {
        $actual = Arr::only($dataset['summary'] ?? [], array_keys(self::EXPECTED));

        if ($actual !== self::EXPECTED) {
            throw new RuntimeException(
                "Workbook reconciliation failed.\nExpected: ".json_encode(self::EXPECTED, JSON_UNESCAPED_UNICODE)."\nActual: ".json_encode($actual, JSON_UNESCAPED_UNICODE)
            );
        }

        if (($dataset['summary']['unmatched_taxa'] ?? []) !== []) {
            throw new RuntimeException('Workbook contains report taxa missing from the species sheet.');
        }

        if (($dataset['summary']['critical_errors'] ?? []) !== []) {
            throw new RuntimeException('Workbook contains records with missing critical fields.');
        }

        if (($dataset['summary']['report_conflicts'] ?? []) !== []) {
            throw new RuntimeException('A legacy report ID belongs to multiple interviews or categories.');
        }
    }

    private function readSpecies($sheet): array
    {
        if (! $sheet) {
            throw new RuntimeException('Missing required sheet: Especies');
        }

        $species = [];

        foreach (array_slice($sheet->toArray(null, false, false, false), 1) as $offset => $row) {
            $family = $this->text($row[0] ?? null);
            $genus = $this->text($row[1] ?? null);
            $name = $this->text($row[2] ?? null);

            if ($family === null && $genus === null && $name === null) {
                continue;
            }

            if ($family === null || $genus === null || $name === null) {
                throw new RuntimeException('Incomplete taxonomy on Especies row '.($offset + 2));
            }

            $key = $this->taxonKey($family, $genus, $name);
            $commonName = $this->commonName($row[4] ?? null);

            if (! isset($species[$key])) {
                $species[$key] = [
                    'family' => $family,
                    'genus' => $genus,
                    'name' => $name,
                    'authority' => $this->text($row[3] ?? null),
                    'metadata' => [
                        'nombres_comunes' => [],
                        'habito' => $this->text($row[5] ?? null),
                        'origen' => $this->text($row[6] ?? null),
                        'estado_uicn' => $this->text($row[7] ?? null),
                        'estado_el_salvador' => $this->text($row[8] ?? null),
                        'fuente' => 'data_ingest.xlsx',
                    ],
                    'source_rows' => [],
                ];
            }

            if ($commonName !== null && ! in_array($commonName, $species[$key]['metadata']['nombres_comunes'], true)) {
                $species[$key]['metadata']['nombres_comunes'][] = $commonName;
            }

            $species[$key]['source_rows'][] = $offset + 2;
        }

        return $species;
    }

    /**
     * @return array{0:array<int,array>,1:int}
     */
    private function readReports(array $rows, string $sheetName, string $category, int $sequenceStart): array
    {
        $records = [];
        $ignored = 0;

        foreach (array_slice($rows, 1) as $offset => $row) {
            $reportId = $this->id($row[1] ?? null);

            if ($reportId === null) {
                if (array_filter($row, fn ($value) => $this->text($value) !== null) !== []) {
                    $ignored++;
                }

                continue;
            }

            $record = [
                'source_sequence' => $sequenceStart + count($records),
                'source_sheet' => $sheetName,
                'source_row' => $offset + 2,
                'interview_original_id' => $this->id($row[0] ?? null),
                'report_original_id' => $reportId,
                'categoria_uso' => $category,
                'family' => $this->text($row[2] ?? null),
                'genus' => $this->text($row[3] ?? null),
                'species' => $this->text($row[4] ?? null),
                'nombre_comun' => $this->commonName($row[5] ?? null),
                'partes_utilizadas' => $this->listValue($row[7] ?? null),
                'estado' => $this->text($row[8] ?? null),
                'obtencion' => $this->text($row[9] ?? null),
                'epoca_del_ano' => $this->text($row[10] ?? null),
                'origen_del_conocimiento' => $this->text($row[11] ?? null),
                'preparacion' => null,
                'forma_de_servir' => null,
                'metodos_de_administracion' => null,
                'enfermedad' => null,
                'uso' => null,
            ];

            if ($category === 'Alimenticio') {
                $record['preparacion'] = $this->text($row[12] ?? null);
                $record['forma_de_servir'] = $this->text($row[13] ?? null);
            } elseif ($category === 'Medicinal') {
                $record['metodos_de_administracion'] = $this->text($row[12] ?? null);
                $record['preparacion'] = $this->text($row[13] ?? null);
                $record['enfermedad'] = $this->text($row[14] ?? null);
            } elseif ($category === 'Ornamental') {
                $record['preparacion'] = $this->text($row[12] ?? null);
            } else {
                $record['preparacion'] = $this->text($row[12] ?? null);
                $record['uso'] = $this->text($row[13] ?? null);
            }

            $records[] = $record;
        }

        return [$records, $ignored];
    }

    private function summarize(array $species, array $records, array $formulaOnlyRows): array
    {
        $interviews = [];
        $reports = [];
        $reportGroups = [];
        $reportTaxa = [];
        $criticalErrors = [];
        $categories = array_fill_keys(array_values(self::REPORT_SHEETS), 0);
        $analyticalCombinations = [];

        foreach ($records as $record) {
            $critical = [
                'interview_original_id',
                'report_original_id',
                'family',
                'genus',
                'species',
                'nombre_comun',
            ];

            foreach ($critical as $field) {
                if (($record[$field] ?? null) === null) {
                    $criticalErrors[] = [
                        'sheet' => $record['source_sheet'],
                        'row' => $record['source_row'],
                        'field' => $field,
                    ];
                }
            }

            $taxon = $this->taxonKey($record['family'] ?? '', $record['genus'] ?? '', $record['species'] ?? '');
            $interviews[$record['interview_original_id']] = true;
            $reports[$record['report_original_id']] = true;
            $reportTaxa[$taxon] = true;
            $reportGroups[$record['report_original_id']][] = $record;
            $categories[$record['categoria_uso']]++;
            $analyticalCombinations[implode('|', [
                $record['interview_original_id'],
                $taxon,
                $record['categoria_uso'],
            ])] = true;
        }

        $reportConflicts = [];
        foreach ($reportGroups as $id => $group) {
            $groupInterviews = array_unique(array_column($group, 'interview_original_id'));
            $groupCategories = array_unique(array_column($group, 'categoria_uso'));

            if (count($groupInterviews) > 1 || count($groupCategories) > 1) {
                $reportConflicts[] = [
                    'report_original_id' => $id,
                    'interviews' => $groupInterviews,
                    'categories' => $groupCategories,
                ];
            }
        }

        return [
            'records' => count($records),
            'interviews' => count($interviews),
            'reports' => count($reports),
            'taxa' => count($species),
            'master_species_rows' => array_sum(array_map(fn ($taxon) => count($taxon['source_rows']), $species)),
            'multi_species_reports' => count(array_filter($reportGroups, fn ($group) => count($group) > 1)),
            'analytical_combinations' => count($analyticalCombinations),
            'categories' => $categories,
            'formula_only_rows' => $formulaOnlyRows,
            'unmatched_taxa' => array_values(array_diff(array_keys($reportTaxa), array_keys($species))),
            'critical_errors' => $criticalErrors,
            'report_conflicts' => $reportConflicts,
        ];
    }

    public function taxonKey(string $family, string $genus, string $species): string
    {
        return implode('|', array_map(
            fn ($value) => mb_strtolower(trim($value), 'UTF-8'),
            [$family, $genus, $species]
        ));
    }

    private function text(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function id(mixed $value): ?string
    {
        $value = $this->text($value);

        if ($value === null) {
            return null;
        }

        if (is_numeric($value) && (float) $value === floor((float) $value)) {
            return (string) (int) $value;
        }

        return $value;
    }

    private function commonName(mixed $value): ?string
    {
        $value = $this->text($value);

        if ($value === null || ! str_contains($value, '"')) {
            return $value;
        }

        $parsed = array_values(array_filter(
            array_map('trim', str_getcsv($value, ',', '"', '')),
            fn ($part) => $part !== ''
        ));

        return $parsed === [] ? $value : implode(', ', $parsed);
    }

    /** @return array<int,string> */
    private function listValue(mixed $value): array
    {
        $value = $this->text($value);

        if ($value === null) {
            return [];
        }

        if (preg_match('/^\[(.*)\]$/u', $value, $matches) !== 1) {
            return [$value];
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $matches[1])),
            fn ($part) => $part !== ''
        ));
    }
}
