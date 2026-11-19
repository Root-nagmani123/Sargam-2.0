<?php

namespace Tests\Unit;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * PR #349 F-003: every Estate .xlsx export must write user text as a TEXT cell, so
 * "=1+1" or "=HYPERLINK(...)" typed into a request is never a live formula, while
 * money columns stay numeric.
 *
 * Builds each export for real, saves it, reads it back and checks the stored cell
 * type. The text columns are discovered per class: a column qualifies when its
 * value closure echoes the row's text unchanged, so a renamed column cannot quietly
 * drop out of coverage — a class with none fails.
 */
class EstateExportCellTypingTest extends TestCase
{
    private const PROBES = ['=1+1', '=HYPERLINK("http://evil.example/x","click")', '+91 98765 43210', '@x'];

    private const AMOUNT = '1234.50';

    /** @return array<string, array{0: class-string}> */
    public static function exports(): array
    {
        $out = [];
        foreach (glob(dirname(__DIR__, 2).'/app/Exports/Estate*Export.php') as $file) {
            $class = 'App\\Exports\\'.basename($file, '.php');
            $out[basename($file, '.php')] = [$class];
        }

        return $out;
    }

    public function test_all_twelve_estate_exports_are_covered(): void
    {
        $this->assertCount(12, self::exports());
    }

    /** @dataProvider exports */
    public function test_user_text_is_stored_as_text_and_amounts_stay_numeric(string $class): void
    {
        $defs = $class::columnDefs();
        $textCols = array_keys(array_filter($defs, fn ($def) => $this->echoes($def, '=1+1')));
        $moneyCols = array_keys(array_filter($defs, fn ($def) => ! empty($def['money'])));
        $this->assertNotEmpty($textCols, "$class has no column that carries row text through unchanged");

        $rows = collect(self::PROBES)->map(fn ($v) => new EstateExportProbeRow($v))
            ->push(new EstateExportProbeRow(self::AMOUNT));
        $cells = $this->readBack($class, $rows, array_merge($textCols, $moneyCols));

        foreach (self::PROBES as $i => $probe) {
            foreach ($textCols as $col) {
                $this->assertSame('s', $cells[$i][$col]['type'], "$class [$col] stored [$probe] as type {$cells[$i][$col]['type']}");
                $this->assertSame($probe, $cells[$i][$col]['value'], "$class [$col] changed [$probe]");
            }
        }
        foreach ($moneyCols as $col) {
            $this->assertSame('n', $cells[count(self::PROBES)][$col]['type'], "$class [$col] amount must stay numeric");
        }
        $this->assertInstanceOf(WithCustomValueBinder::class, new $class(collect(), 'f', 'g'), 'the export must declare the binder');
    }

    /** Whether a column's value closure returns the row's text unchanged. */
    private function echoes(array $def, string $probe): bool
    {
        try {
            return ($def['value'])(new EstateExportProbeRow($probe), 1) === $probe;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  list<string>  $cols
     * @return list<array<string, array{type: string, value: string}>>
     */
    private function readBack(string $class, Collection $rows, array $cols): array
    {
        $path = tempnam(sys_get_temp_dir(), 'eect').'.xlsx';
        file_put_contents($path, Excel::raw(new $class($rows, 'Filter', 'now', $cols), ExcelFormat::XLSX));

        try {
            $sheet = IOFactory::load($path)->getActiveSheet();
            $headings = array_map(fn ($k) => $class::columnDefs()[$k]['heading'], $cols);
            $headingRow = null;
            foreach ($sheet->getRowIterator() as $row) {
                if ((string) $sheet->getCell('A'.$row->getRowIndex())->getValue() === $headings[0]) {
                    $headingRow = $row->getRowIndex();
                    break;
                }
            }
            $this->assertNotNull($headingRow, "$class wrote no heading row");

            $out = [];
            foreach ($rows->keys() as $r) {
                foreach ($cols as $c => $key) {
                    $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($c + 1).($headingRow + 1 + $r));
                    $out[$r][$key] = ['type' => $cell->getDataType(), 'value' => (string) $cell->getValue()];
                }
            }

            return $out;
        } finally {
            @unlink($path);
        }
    }
}

/** A row whose every field holds the same value, so any text column can be probed. */
class EstateExportProbeRow
{
    public function __construct(private string $value) {}

    public function __get(string $name): string
    {
        return $this->value;
    }

    public function __isset(string $name): bool
    {
        return true;
    }
}
