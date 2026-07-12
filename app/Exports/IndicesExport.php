<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * A quantitative-indices table (species with FC/RFC/UV/CI) as xlsx/csv. Headers
 * use the literature-standard abbreviations, which are language-independent.
 */
class IndicesExport implements FromArray, WithHeadings
{
    /**
     * @param  list<string>  $headings
     * @param  list<list<string|int|float>>  $rows
     */
    public function __construct(
        private array $headings,
        private array $rows
    ) {}

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return $this->rows;
    }
}
