<?php

namespace App\Support;

class Spreadsheet
{
    /**
     * Neutralize spreadsheet formula injection (CSV/XLSX). A cell whose text
     * begins with one of these characters is interpreted as a formula by
     * Excel / Google Sheets / LibreOffice; prefixing it with a single quote
     * forces the app to treat the whole cell as literal text.
     *
     * Applies to any value written into an export cell that originates from
     * user input (interview answers, field labels, catalog names).
     */
    public static function safe(?string $value): string
    {
        $value = (string) $value;

        $dangerous = ['=', '+', '-', '@', "\t", "\r", "\n"];

        if ($value !== '' && in_array($value[0], $dangerous, true)) {
            return "'".$value;
        }

        return $value;
    }
}
