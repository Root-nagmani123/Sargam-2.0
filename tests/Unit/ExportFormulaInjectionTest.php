<?php

namespace Tests\Unit;

use App\Exports\BrandedGridExport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Guards the shared grid exports against spreadsheet formula injection
 * (CWE-1236) — and against the mitigation itself corrupting the data.
 *
 * Master names and member fields are stored text with no character restriction,
 * and both spreadsheet formats hand that text to something that evaluates it:
 * PhpSpreadsheet's default binder types any leading =, +, - or @ string as a
 * FORMULA, and Excel does the same when it opens a CSV. A row named
 * `=HYPERLINK("http://evil","Dept")` would therefore execute on the workstation
 * of whichever admin opened the download.
 *
 * The two formats need DIFFERENT mitigations, which is what this file pins:
 *
 *  - CSV has no cell types, so an apostrophe prefix (sanitize_export_cell) is
 *    the only way to say "literal text".
 *  - .xlsx has cell types, so the apostrophe is both unnecessary and harmful:
 *    PhpSpreadsheet stores it as DATA and Excel shows it. Binding the value as
 *    TYPE_STRING says the same thing without altering the value, and it also
 *    stops the default binder rounding a long digit-only identifier to Excel's
 *    15 significant digits.
 *
 * Asserted against a real workbook rather than the array handed to the writer,
 * because the defect lives in the binder, which array() never sees.
 */
class ExportFormulaInjectionTest extends TestCase
{
    /** Build a one-column workbook carrying $storedValue and read it back. */
    private function cellFor(string $storedValue): Cell
    {
        $export = new BrandedGridExport(
            'Members',
            new Collection([(object) ['name' => $storedValue]]),
            ['name' => ['key' => 'name', 'heading' => 'Name', 'value' => fn ($row) => $row->name]],
            '01-01-2026 10:00 AM'
        );

        $path = tempnam(sys_get_temp_dir(), 'bge').'.xlsx';
        file_put_contents($path, Excel::raw($export, ExcelFormat::XLSX));

        try {
            $sheet = IOFactory::load($path)->getActiveSheet();

            foreach ($sheet->getRowIterator() as $row) {
                foreach ($row->getCellIterator() as $cell) {
                    if ((string) $cell->getValue() === $storedValue) {
                        return $cell;
                    }
                }
            }
        } finally {
            @unlink($path);
        }

        $this->fail('The stored value did not appear in the workbook: '.$storedValue);
    }

    /** @dataProvider dangerousPrefixes */
    public function test_a_stored_formula_is_written_as_text_not_as_a_formula(string $stored): void
    {
        $cell = $this->cellFor($stored);

        $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
        $this->assertNotSame(DataType::TYPE_FORMULA, $cell->getDataType());
        // Exactly what was stored — no apostrophe added, nothing removed.
        $this->assertSame($stored, (string) $cell->getValue());
    }

    public static function dangerousPrefixes(): array
    {
        return [
            'equals' => ['=HYPERLINK("http://evil","Dept")'],
            'plus' => ['+1+1'],
            'at' => ['@SUM(A1)'],
            'mobile' => ['+91 9876543210'],
        ];
    }

    /**
     * The half the apostrophe could never fix: precision.
     *
     * Excel holds 15 significant digits, so a 16-digit identifier bound as a
     * number comes back rounded. sanitize_export_cell() leaves digit-only
     * strings alone by design, so this was silent corruption no matter how the
     * helper was applied.
     */
    public function test_a_long_numeric_identifier_keeps_every_digit(): void
    {
        $cell = $this->cellFor('7755000100020824');

        $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
        $this->assertSame('7755000100020824', (string) $cell->getValue());
    }

    public function test_a_leading_zero_identifier_survives(): void
    {
        $cell = $this->cellFor('0012345678');

        $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
        $this->assertSame('0012345678', (string) $cell->getValue());
    }

    public function test_ordinary_values_are_untouched(): void
    {
        $this->assertSame('Finance Department', (string) $this->cellFor('Finance Department')->getValue());
    }

    /**
     * A real quantity must stay a number, or the grid stops sorting and totalling.
     */
    public function test_a_plain_number_is_still_a_number(): void
    {
        $export = new BrandedGridExport(
            'Members',
            new Collection([(object) ['n' => '42']]),
            ['n' => ['key' => 'n', 'heading' => 'N', 'value' => fn ($row) => $row->n]],
            '01-01-2026 10:00 AM'
        );

        $path = tempnam(sys_get_temp_dir(), 'bge').'.xlsx';
        file_put_contents($path, Excel::raw($export, ExcelFormat::XLSX));

        try {
            $sheet = IOFactory::load($path)->getActiveSheet();

            $found = null;
            foreach ($sheet->getRowIterator() as $row) {
                foreach ($row->getCellIterator() as $cell) {
                    if ((string) $cell->getValue() === '42') {
                        $found = $cell;
                    }
                }
            }

            $this->assertNotNull($found, '42 should appear in the workbook');
            $this->assertSame(DataType::TYPE_NUMERIC, $found->getDataType());
        } finally {
            @unlink($path);
        }
    }

    /**
     * The CSV writer is a streamed closure rather than a returnable array, so
     * the guarantee is pinned at its source: the apostrophe helper must be in
     * the cell path there, and the raw value must not be.
     */
    public function test_the_csv_writer_neutralises_cells_as_well(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Concerns/ExportsBrandedGrid.php'));

        $this->assertStringContainsString(
            "sanitize_export_cell(\$col['value'](\$row, \$index))",
            $source,
            'the CSV writer must sanitise each cell'
        );
        $this->assertStringNotContainsString(
            "fn (\$col) => \$col['value'](\$row, \$index),",
            $source,
            'no cell path may write the raw stored value'
        );
    }
}
