<?php

namespace Tests\Unit;

use App\Exports\BrandedGridExport;
use App\Support\Concerns\BindsExportCellsAsText;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * The .xlsx formula-injection fix is a class-header change, and nothing guarded it.
 *
 * BrandedGridExport neutralises `=`-leading text by declaring the cell TYPE
 * rather than by prefixing an apostrophe — `extends DefaultValueBinder`,
 * `implements WithCustomValueBinder`, `use BindsExportCellsAsText`. Drop any one
 * of those three lines, or let a later refactor stop extending the binder, and
 * every export still produces a perfectly good-looking workbook: only the cell
 * type changes, formulas come back to life, and no test notices.
 *
 * So these assert on the type of a cell read back out of a real saved workbook,
 * not on the string the export class happened to build.
 */
class BrandedGridExportBindingTest extends TestCase
{
    /**
     * Values that must reach the sheet as TEXT, with every character intact.
     *
     * @return array<string, array{0: string}>
     */
    public static function textValues(): array
    {
        return [
            'formula'            => ['=1+1'],
            'hyperlink formula'  => ['=HYPERLINK("http://evil.example/x","click")'],
            'DDE command'        => ['=cmd|\' /C calc\'!A0'],
            'leading plus'       => ['+91 9876543210'],
            'leading minus text' => ['-lead'],
            'leading at'         => ['@cmd'],
            'leading zero'       => ['0012345678'],
            '16-digit identifier'=> ['1234567890123456'],
            'exponent-looking'   => ['1e5'],
            'spaced digits'      => ['98765 43210'],
        ];
    }

    /**
     * Values that are genuine quantities and should stay numeric, so the fix
     * cannot be "pass everything as text" — that would be a different bug.
     *
     * @return array<string, array{0: string}>
     */
    public static function numericValues(): array
    {
        return [
            'small int'   => ['42'],
            'negative'    => ['-5'],
            'decimal'     => ['12.50'],
            '15 digits'   => ['123456789012345'],
        ];
    }

    /** @dataProvider textValues */
    public function test_identifier_shaped_values_are_written_as_text(string $value): void
    {
        $cell = $this->firstDataCell($value);

        $this->assertSame('s', $cell['type'], "[$value] must be a text cell, not a formula or a number");
        $this->assertSame($value, $cell['value'], "[$value] must survive byte for byte");
        $this->assertStringStartsNotWith("'", $cell['value'], 'the apostrophe belongs to CSV, not to .xlsx');
    }

    /** @dataProvider numericValues */
    public function test_real_numbers_stay_numeric(string $value): void
    {
        $this->assertSame('n', $this->firstDataCell($value)['type'], "[$value] is a quantity and should stay numeric");
    }

    /**
     * The guard the rest of this file rests on: with the binder removed, the
     * very first value goes back to being a live formula. Without this the
     * suite could pass because nothing was being bound at all.
     */
    public function test_the_default_binder_would_reintroduce_the_defect(): void
    {
        $this->assertTrue(
            BindsExportCellsAsText::isSpreadsheetSafeNumber('42'),
            'sanity: the helper must recognise a plain number'
        );
        $this->assertFalse(
            BindsExportCellsAsText::isSpreadsheetSafeNumber('=1+1'),
            'sanity: a formula is never a safe number'
        );

        // What PhpSpreadsheet does with the SAME value under the stock binder.
        $sheet = (new \PhpOffice\PhpSpreadsheet\Spreadsheet())->getActiveSheet();
        Cell::setValueBinder(new DefaultValueBinder());
        $sheet->setCellValue('A1', '=1+1');

        $this->assertSame(
            'f',
            $sheet->getCell('A1')->getDataType(),
            'the stock binder makes this a FORMULA — which is exactly what BrandedGridExport must prevent'
        );
    }

    /** The export class must still declare the binder contract. */
    public function test_the_export_declares_the_binder_contract(): void
    {
        $export = $this->export('x');

        $this->assertInstanceOf(\Maatwebsite\Excel\Concerns\WithCustomValueBinder::class, $export);
        $this->assertInstanceOf(\PhpOffice\PhpSpreadsheet\Cell\IValueBinder::class, $export);
    }

    private function export(string $value): BrandedGridExport
    {
        return new BrandedGridExport(
            [(object) ['v' => $value]],
            [['key' => 'v', 'heading' => 'Value', 'class' => '', 'value' => fn ($row, $i) => $row->v]],
            'Binder Probe',
            '16 Sep 2026'
        );
    }

    /**
     * Render the export for real, save it, read it back, and return the first
     * cell under the column heading.
     *
     * @return array{type: string, value: string}
     */
    private function firstDataCell(string $value): array
    {
        $binary = Excel::raw($this->export($value), ExcelFormat::XLSX);

        $path = tempnam(sys_get_temp_dir(), 'bge') . '.xlsx';
        file_put_contents($path, $binary);

        try {
            $sheet = IOFactory::load($path)->getActiveSheet();

            $headingRow = null;
            foreach ($sheet->getRowIterator() as $row) {
                if (trim((string) $sheet->getCell('A' . $row->getRowIndex())->getValue()) === 'Value') {
                    $headingRow = $row->getRowIndex();
                    break;
                }
            }

            $this->assertNotNull($headingRow, 'the export produced no column heading to anchor on');

            $cell = $sheet->getCell('A' . ($headingRow + 1));

            return ['type' => $cell->getDataType(), 'value' => (string) $cell->getValue()];
        } finally {
            @unlink($path);
        }
    }
}
