<?php

namespace Tests\Unit;

use App\Support\ExportCellValue;
use PHPUnit\Framework\TestCase;

/**
 * Spreadsheet formula neutralisation for exported cell values.
 *
 * Pure function, no application boot and no database.
 */
class ExportCellValueTest extends TestCase
{
    /**
     * @dataProvider riskyValues
     */
    public function test_it_neutralises_values_a_spreadsheet_would_treat_as_a_formula(string $input): void
    {
        $this->assertSame(
            "'" . $input,
            ExportCellValue::safe($input),
            sprintf('%s must be prefixed so Excel reads it as text', var_export($input, true))
        );
    }

    public static function riskyValues(): array
    {
        return [
            'equals'            => ['=1+1'],
            'hyperlink'         => ['=HYPERLINK("http://evil","Click")'],
            'dde'               => ['=cmd|\'/c calc\'!A1'],
            'plus with text'    => ['+41 555 CALL'],
            'minus with text'   => ['-lookup'],
            'at sign'           => ['@cmd'],
            'truncated email'   => ['@nic.in'],
            'leading space'     => [' =2+2'],
            'leading tab'       => ["\t=2+2"],
        ];
    }

    /**
     * @dataProvider harmlessValues
     */
    public function test_it_leaves_values_that_cannot_be_a_formula_untouched($input): void
    {
        $this->assertSame($input, ExportCellValue::safe($input));
    }

    public static function harmlessValues(): array
    {
        return [
            'ordinary text'      => ['Dr ESWARA RAO'],
            'email'              => ['eshupasala@gmail.com'],
            'status'             => ['Active'],
            'not applicable'     => ['N/A'],
            // The column definitions emit "-" for a missing value. Quoting it
            // would put a stray apostrophe through the whole report.
            'dash placeholder'   => ['-'],
            'double dash'        => ['--'],
            // A quoted negative would become left-aligned text and stop sorting.
            'negative integer'   => ['-5'],
            'negative decimal'   => ['-5.25'],
            'positive signed'    => ['+7'],
            'plain number'       => ['1'],
            'zero'               => ['0'],
            'empty string'       => [''],
            'integer type'       => [42],
            'null'               => [null],
        ];
    }
}
