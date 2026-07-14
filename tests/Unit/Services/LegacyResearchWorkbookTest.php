<?php

namespace Tests\Unit\Services;

use App\Services\LegacyResearchWorkbook;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

class LegacyResearchWorkbookTest extends TestCase
{
    private string $path;

    protected function tearDown(): void
    {
        if (isset($this->path) && is_file($this->path)) {
            unlink($this->path);
        }

        parent::tearDown();
    }

    public function test_reads_spanish_values_and_keeps_multi_species_rows(): void
    {
        $spreadsheet = new Spreadsheet;
        $species = $spreadsheet->getActiveSheet();
        $species->setTitle('Especies');
        $species->fromArray([
            ['Familia', 'Género', 'Especie', 'Autoridad', 'Nombre común', 'hábito', 'origen', 'UICN', 'El Salvador'],
            ['Musaceae', 'Musa', 'x paradisiaca', 'L.', '"guineo"', 'arbusto', 'Exógena', 'No Evaluado', 'No evaluado'],
            ['Musaceae', 'Musa', 'x paradisiaca', 'L.', '"plátano"', 'arbusto', 'Exógena', 'No Evaluado', 'No evaluado'],
            ['Amaryllidaceae', 'Allium', 'sativum', 'L.', '"ajo"', 'Hierba', 'Exógena', 'No Evaluado', 'No evaluado'],
        ]);

        $sheets = [
            'Reportes Alimenticio' => [1, 10, 'Musaceae', 'Musa', 'x paradisiaca', '"guineo"', null, '[Fruto, Semillas]', 'Fresco', 'Colecta libre', 'Todo el año', 'Mamá', 'Cocido', 'Asado'],
            'Reportes Medicinal' => [1, 20, 'Amaryllidaceae', 'Allium', 'sativum', '"ajo"', null, '[Bulbo]', 'Fresco', 'Mercado', 'Todo el año', 'Abuela', 'Té', '[Decocción]', 'Tos'],
            'Reportes Ornamental' => [1, 30, 'Musaceae', 'Musa', 'x paradisiaca', '"plátano"', null, '[Planta entera]', 'Fresco', 'Silvestre', 'Todo el año', 'Observación propia', null],
            'Reportes Económico' => [1, 40, 'Musaceae', 'Musa', 'x paradisiaca', '"guineo"', null, '[Fruto]', 'Fresco', 'Colecta libre', null, 'Observación propia', null, '[Venta de frutos]'],
            'Reportes Cultural' => [1, 50, 'Amaryllidaceae', 'Allium', 'sativum', '"ajo"', null, '[Bulbo]', 'Fresco', 'Mercado', null, 'Abuela', 'Maceración', 'Conocimiento ancestral'],
        ];

        foreach ($sheets as $name => $row) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($name);
            $sheet->fromArray([array_fill(0, count($row), 'Encabezado'), $row]);

            if ($name === 'Reportes Alimenticio') {
                $sheet->fromArray([[null, null, null, null, null, null, '#NAME?']], null, 'A3');
            }
        }

        $temporary = tempnam(sys_get_temp_dir(), 'legacy-research-');
        unlink($temporary);
        $this->path = $temporary.'.xlsx';
        (new Xlsx($spreadsheet))->save($this->path);
        $spreadsheet->disconnectWorksheets();

        $dataset = (new LegacyResearchWorkbook)->read($this->path);

        $this->assertCount(2, $dataset['species']);
        $this->assertCount(5, $dataset['records']);
        $this->assertSame(['guineo', 'plátano'], $dataset['species']['musaceae|musa|x paradisiaca']['metadata']['nombres_comunes']);
        $this->assertSame('guineo', $dataset['records'][0]['nombre_comun']);
        $this->assertSame(['Fruto', 'Semillas'], $dataset['records'][0]['partes_utilizadas']);
        $this->assertSame('[Decocción]', $dataset['records'][1]['preparacion']);
        $this->assertSame('Conocimiento ancestral', $dataset['records'][4]['uso']);
        $this->assertSame(1, $dataset['summary']['formula_only_rows']['Alimenticio']);
    }
}
