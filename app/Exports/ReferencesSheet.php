<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * The "References" sheet of the indices workbook — the source paper for each
 * index plus the ethnobotanyR attribution. Bibliographic entries are
 * language-independent, so they are passed in as plain rows.
 */
class ReferencesSheet implements FromArray, WithHeadings, WithTitle
{
    /**
     * @param  list<list<string>>  $rows  [index, name, source]
     */
    public function __construct(private array $rows) {}

    public function headings(): array
    {
        return ['Index', 'Name', 'Source'];
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'References';
    }
}
