<?php

namespace Tests\Unit;

use App\Exports\BrandedGridExport;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Guards the shared grid exports against spreadsheet formula injection
 * (CWE-1236).
 *
 * Master names and member fields are stored text with no character restriction,
 * and both spreadsheet formats hand that text to something that evaluates it:
 * PhpSpreadsheet's default binder types any leading =, +, - or @ string as a
 * FORMULA, and Excel does the same when it opens a CSV. A row named
 * `=HYPERLINK("http://evil","Dept")` would therefore execute on the workstation
 * of whichever admin opened the download.
 *
 * Both writers route cell values through sanitize_export_cell(), which prefixes
 * an apostrophe — also PhpSpreadsheet's own marker for "this is literal text".
 *
 * DB-free.
 */
class ExportFormulaInjectionTest extends TestCase
{
    private function exportOf(string $storedValue): array
    {
        $export = new BrandedGridExport(
            'Members',
            new Collection([(object) ['name' => $storedValue]]),
            [['key' => 'name', 'heading' => 'Name', 'value' => fn ($row) => $row->name]],
            '01-01-2026 10:00 AM'
        );

        return $export->array();
    }

    /** @dataProvider dangerousPrefixes */
    public function test_a_stored_formula_is_not_written_as_one(string $stored): void
    {
        $this->assertSame([[ "'".$stored ]], $this->exportOf($stored));
    }

    public static function dangerousPrefixes(): array
    {
        return [
            'equals' => ['=HYPERLINK("http://evil","Dept")'],
            'plus' => ['+1+1'],
            'minus' => ['-2+3'],
            'at' => ['@SUM(A1)'],
            'tab' => ["\tcmd"],
        ];
    }

    public function test_ordinary_values_are_untouched(): void
    {
        $this->assertSame([['Finance Department']], $this->exportOf('Finance Department'));
        $this->assertSame([['9876543210']], $this->exportOf('9876543210'));
    }

    /**
     * The CSV writer is a streamed closure rather than a returnable array, so
     * the guarantee is pinned at its source: the same helper must be in the
     * cell path there too, and the raw value must not be.
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
