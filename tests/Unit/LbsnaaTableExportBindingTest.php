<?php

namespace Tests\Unit;

use App\Exports\LbsnaaTableExport;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * LbsnaaTableExport must type cells, not merely format them (PR #334 F-003).
 *
 * The leave, leave-approval, leave-on-behalf, medical-exemption, My Groups and
 * faculty-list exports all build through this class. Before it declared a value
 * binder, an OT's leave reason "=HYPERLINK(…)" reached the approving faculty's
 * workbook as a live formula, and "+91…" mobiles lost their plus sign. These
 * cases read the type back out of a real saved workbook, as
 * BrandedGridExportBindingTest does, so dropping the class-header binder turns
 * them red.
 */
class LbsnaaTableExportBindingTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public static function textValues(): array
    {
        return [
            'hyperlink formula' => ['=HYPERLINK("http://evil.example/x","Medical certificate")'],
            'plain formula' => ['=1+1'],
            'mobile with country code' => ['+919876543210'],
            '19-digit identifier' => ['1234567890123456789'],
            'leading zero' => ['0245'],
            'leading at' => ['@cmd'],
        ];
    }

    /** @dataProvider textValues */
    public function test_formula_and_identifier_shaped_values_are_text(string $value): void
    {
        $cell = $this->firstDataCell($value);

        $this->assertSame('s', $cell['type'], "[$value] must be a text cell, not a formula or a number");
        $this->assertSame($value, $cell['value'], "[$value] must survive byte for byte");
    }

    public function test_real_quantities_stay_numeric(): void
    {
        $this->assertSame('n', $this->firstDataCell('42')['type'], 'a plain count stays numeric');
        $this->assertSame('n', $this->firstDataCell(7)['type'], 'an int serial number stays numeric');
    }

    public function test_the_export_declares_the_binder_contract(): void
    {
        $export = new LbsnaaTableExport(collect([[1]]), ['A'], 'T');

        $this->assertInstanceOf(\Maatwebsite\Excel\Concerns\WithCustomValueBinder::class, $export);
        $this->assertInstanceOf(\PhpOffice\PhpSpreadsheet\Cell\IValueBinder::class, $export);
    }

    /**
     * Render, save, reload, and return the cell under the heading row.
     *
     * @param  string|int  $value
     * @return array{type: string, value: mixed}
     */
    private function firstDataCell($value): array
    {
        $binary = Excel::raw(
            new LbsnaaTableExport(collect([[$value]]), ['Value'], 'Binder Probe'),
            ExcelFormat::XLSX
        );

        $path = tempnam(sys_get_temp_dir(), 'lte') . '.xlsx';
        file_put_contents($path, $binary);

        try {
            $sheet = IOFactory::load($path)->getActiveSheet();
            // Five branded header rows, the heading on row 6, the first row on 7.
            $cell = $sheet->getCell('A7');

            return ['type' => $cell->getDataType(), 'value' => $cell->getValue()];
        } finally {
            @unlink($path);
        }
    }
}
