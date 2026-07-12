<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * The full indices workbook (xlsx): the species-indices table plus a References
 * sheet. Used for xlsx only — CSV is a single flat table and downloads just the
 * indices sheet.
 */
class IndicesReportExport implements WithMultipleSheets
{
    public function __construct(
        private IndicesExport $indices,
        private ReferencesSheet $references
    ) {}

    public function sheets(): array
    {
        return [$this->indices, $this->references];
    }
}
