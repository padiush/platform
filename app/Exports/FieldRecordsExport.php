<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * The collections a project has made, one row each.
 *
 * Headers use the Darwin Core terms the fields already correspond to —
 * `catalogNumber`, `recordNumber`, `recordedBy`, `eventDate`, `identifiedBy`,
 * `dateIdentified`, `identificationQualifier` — so the sheet can be read, or
 * mapped, by anyone who works with occurrence data. That is a naming choice
 * only: this is not a GBIF submission, and publishing occurrences is deferred
 * to its own decision.
 *
 * See docs/decisions/0008-specimens-and-determinations.md.
 */
class FieldRecordsExport implements FromArray, WithHeadings, WithTitle
{
    /**
     * @param  list<list<string|int|float|null>>  $rows
     */
    public function __construct(private array $rows) {}

    public function headings(): array
    {
        return [
            'catalogNumber',
            'recordNumber',
            'recordedBy',
            'eventDate',
            'locality',
            'decimalLatitude',
            'decimalLongitude',
            'institutionCode',
            'family',
            'genus',
            'specificEpithet',
            'identificationQualifier',
            'identifiedBy',
            'dateIdentified',
            'permitAuthority',
            'permitReference',
            'permitExemption',
            'notes',
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'FieldRecords';
    }
}
