<?php

namespace Tests\Unit;

use App\Support\Spreadsheet;
use PHPUnit\Framework\TestCase;

class SpreadsheetTest extends TestCase
{
    public function test_it_neutralizes_formula_leading_characters()
    {
        foreach (['=', '+', '-', '@', "\t", "\r", "\n"] as $char) {
            $payload = $char.'cmd|calc';
            $this->assertSame(
                "'".$payload,
                Spreadsheet::safe($payload),
                "Failed to neutralize a value starting with '{$char}'"
            );
        }
    }

    public function test_it_leaves_ordinary_values_untouched()
    {
        foreach (['Medicinal', 'Uso 3', '123', 'a=b', ''] as $value) {
            $this->assertSame($value, Spreadsheet::safe($value));
        }
    }

    public function test_it_handles_null()
    {
        $this->assertSame('', Spreadsheet::safe(null));
    }
}
