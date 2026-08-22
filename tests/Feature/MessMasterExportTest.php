<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/**
 * The Mess master grids and the files they export.
 *
 * Two properties are worth locking down here, because both failed silently —
 * nothing threw, the download simply held the wrong thing:
 *
 *  1. `?format=csv` returns a CSV. The format was whitelisted from the start but
 *     fell through to the styled-workbook branch, so every "CSV" download was an
 *     .xlsx wearing the wrong extension.
 *
 *  2. The export's columns line up with the grid's columns. The Column-Visibility
 *     control sends the grid's own column indexes, so a grid whose first data
 *     column is not the one the export calls column 0 shifts the whole mapping.
 *     Sub Store, Category Item and Client Master each lacked the S. No. column
 *     the export prints first, which pushed every index down by one and dropped
 *     the last column — Status vanished from the file while sitting in plain
 *     sight on screen.
 *
 * Runs against the database .env points at (see phpunit.xml): read-only, and
 * skips rather than fails when a grid has no rows to export.
 */
class MessMasterExportTest extends TestCase
{
    /**
     * Grid page URI, its export URI, and the headings the export must print when
     * every column is visible — in the grid's own left-to-right order.
     *
     * @return array<string,array{0:string,1:string,2:string[]}>
     */
    public static function grids(): array
    {
        return [
            'store master' => ['/admin/mess/stores', '/admin/mess/stores/export',
                ['S.No.', 'Store Name', 'Store Type', 'Location', 'Status']],
            'vendor master' => ['/admin/mess/vendors', '/admin/mess/vendors/export',
                ['S.No.', 'Vendor Name', 'Email', 'Contact Person', 'Phone', 'Address']],
            'category item master' => ['/admin/mess/itemcategories', '/admin/mess/itemcategories/export',
                ['S.No.', 'Category Name', 'Category Type', 'Item Category Description', 'Status']],
            'sub-category item master' => ['/admin/mess/itemsubcategories', '/admin/mess/itemsubcategories/export',
                ['S.No.', 'Category Name', 'Item Name', 'Item Code', 'Unit Measurement', 'Alert Qty', 'Status']],
            'client master' => ['/admin/mess/client-types', '/admin/mess/client-types/export',
                ['S.No.', 'Client Type', 'Client Name', 'Status']],
            'sub store master' => ['/admin/mess/sub-stores', '/admin/mess/sub-stores/export',
                ['S.No.', 'Sub Store Name', 'Status']],
        ];
    }

    private function admin(): User
    {
        $user = User::query()->orderBy('pk')->first();

        if (! $user) {
            $this->markTestSkipped('No user rows in this database to act as.');
        }

        return $user;
    }

    /** Drain a streamed response into a string. */
    private function bodyOf($response): string
    {
        $base = $response->baseResponse;

        if ($base instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            // Record the buffer level and unwind back to it, or PHPUnit reports
            // the test as risky for not closing its own buffers.
            $level = ob_get_level();
            ob_start();
            $base->sendContent();

            $out = '';
            while (ob_get_level() > $level) {
                $out = ob_get_clean() . $out;
            }

            return $out;
        }

        return (string) $response->getContent();
    }

    /** @return array<int,array<int,string>> */
    private function parseCsv(string $body): array
    {
        $body = preg_replace('/^\xEF\xBB\xBF/', '', $body);

        $handle = fopen('php://memory', 'r+b');
        fwrite($handle, $body);
        rewind($handle);

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    /**
     * @dataProvider grids
     */
    public function test_csv_export_is_actually_a_csv(string $gridUri, string $exportUri, array $headings): void
    {
        $response = $this->actingAs($this->admin())->get($exportUri . '?format=csv');
        $response->assertOk();

        $this->assertStringContainsString(
            'text/csv',
            (string) $response->headers->get('Content-Type'),
            "$exportUri did not return a CSV content type — the csv format is falling through to the workbook branch."
        );
        $this->assertStringContainsString(
            '.csv',
            (string) $response->headers->get('Content-Disposition'),
            "$exportUri did not offer a .csv filename."
        );

        $rows = $this->parseCsv($this->bodyOf($response));

        $this->assertNotEmpty($rows, "$exportUri produced an empty CSV.");
        $this->assertSame($headings, $rows[0], "$exportUri header row does not match the grid.");
    }

    /**
     * With every column visible, the export must print every column — the case
     * the index shift used to break.
     *
     * @dataProvider grids
     */
    public function test_export_columns_line_up_with_the_grid(string $gridUri, string $exportUri, array $headings): void
    {
        $user = $this->admin();

        // What MessColumnManager.resolveExportIndexes sends when nothing is
        // hidden: every data column, Action excluded.
        $visible = implode(',', range(0, count($headings) - 1));

        $rows = $this->parseCsv($this->bodyOf(
            $this->actingAs($user)->get($exportUri . '?format=csv&columns=' . $visible)
        ));

        $this->assertSame(
            $headings,
            $rows[0] ?? [],
            "$exportUri dropped or shifted a column that is visible on the grid."
        );
    }

    /**
     * The grid's first cell is a running serial that follows the page, so it
     * reads 1..n whichever column the user sorted on.
     *
     * @dataProvider grids
     */
    public function test_grid_serial_column_reads_one_to_n(string $gridUri, string $exportUri, array $headings): void
    {
        if ($headings[0] !== 'S.No.') {
            $this->markTestSkipped('Grid has no serial column.');
        }

        $payload = $this->actingAs($this->admin())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get($gridUri . '?draw=1&start=0&length=5')
            ->json();

        $data = $payload['data'] ?? [];

        if ($data === []) {
            $this->markTestSkipped("$gridUri has no rows to check.");
        }

        foreach (array_values($data) as $i => $row) {
            $this->assertSame(
                (string) ($i + 1),
                (string) $row[0],
                "$gridUri serial column does not follow the page."
            );
        }

        // The row must carry one cell per heading plus the Action column.
        $this->assertCount(
            count($headings) + 1,
            $data[0],
            "$gridUri row width does not match its headings + Action."
        );
    }

    /**
     * PDF is offered as a download, not only as the inline Print preview.
     */
    public function test_pdf_is_downloadable(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/mess/sub-stores/export?format=pdf');
        $response->assertOk();

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $this->bodyOf($response));
    }
    /**
     * A PDF too big for DomPDF must explain itself, not fatal.
     *
     * The Selling Voucher grid used to exhaust three gigabytes inside DomPDF's
     * Cellmap and return a white screen. The guard is exercised directly here so
     * the case does not depend on how much data this database happens to hold.
     */
    public function test_oversized_pdf_is_refused_with_an_explanation(): void
    {
        $controller = new class {
            use \App\Http\Controllers\Mess\Concerns\RaisesExportLimits;

            public function guard(\Illuminate\Support\Collection $rows, string $title)
            {
                return $this->guardPdfRowCount($rows, $title);
            }

            public function max(): int
            {
                return $this->pdfMaxRows();
            }
        };

        $max = $controller->max();
        $this->assertGreaterThan(0, $max, 'The row guard is disabled in this environment.');

        $withinLimit = collect(array_fill(0, $max, ['a']));
        $this->assertNull(
            $controller->guard($withinLimit, 'Store Master'),
            'A report at exactly the limit must still render.'
        );

        $overLimit = collect(array_fill(0, $max + 1, ['a']));
        $response = $controller->guard($overLimit, 'Selling Voucher');

        $this->assertNotNull($response, 'A report past the limit must be refused.');
        $this->assertSame(422, $response->getStatusCode());

        $body = $response->getContent();
        $this->assertStringContainsString('Selling Voucher', $body);
        $this->assertStringContainsString(number_format($max + 1), $body, 'The message must state the real row count.');
        $this->assertStringContainsString('CSV', $body, 'The message must point at the formats that have no limit.');
    }

    /**
     * The memory ceiling is a floor, never a cap: a host granting more keeps it.
     */
    public function test_memory_limit_is_only_ever_raised(): void
    {
        $controller = new class {
            use \App\Http\Controllers\Mess\Concerns\RaisesExportLimits;

            public function raise(string $target): void
            {
                $this->raiseMemoryLimit($target);
            }
        };

        $original = ini_get('memory_limit');

        try {
            ini_set('memory_limit', '1024M');
            $controller->raise('256M');
            $this->assertSame('1024M', ini_get('memory_limit'), 'A smaller target must not lower the ceiling.');

            $controller->raise('2048M');
            $this->assertSame('2048M', ini_get('memory_limit'), 'A larger target must raise the ceiling.');
        } finally {
            ini_set('memory_limit', $original);
        }
    }
}