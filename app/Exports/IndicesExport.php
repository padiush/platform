<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * The species-indices table (FC/RFC/UV/CI) as one sheet. Headers use the
 * literature-standard abbreviations, which are language-independent. Also the
 * lone sheet of a CSV download; the sheet title is ignored there.
 */
class IndicesExport implements FromArray, WithHeadings, WithTitle
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

    public function title(): string
    {
        return 'Indices';
    }
}
