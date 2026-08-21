<?php

namespace Tests\Feature;

use App\Models\FacultyExpertiseMaster;
use App\Models\FacultyMaster;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * The Master-module four-format export layer (ExportsMasterGrid + MasterGridExport).
 *
 * The property this layer exists to guarantee is that the CSV, the .xlsx, the PDF,
 * the printout and the screen never disagree — so these tests assert on the
 * relationship between the grid's rows and the export's rows, not on cosmetics.
 *
 * Runs against the database .env points at (see phpunit.xml): read-only except
 * where DatabaseTransactions covers a write, and skips rather than fails when
 * the fixture data a case needs is not present.
 */
class MasterGridExportTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $user = User::query()->orderBy('pk')->first();

        if (! $user) {
            $this->markTestSkipped('No user rows in this database to act as.');
        }

        return $user;
    }

    /** Drain a streamed/attachment response into a string. */
    private function bodyOf($response): string
    {
        $base = $response->baseResponse;

        if ($base instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            // sendContent() writes straight to the output buffer and may leave the
            // nesting level where it found it or not, depending on the callback.
            // Record the level and unwind back to it, or PHPUnit reports the test
            // as risky for not closing its own buffers.
            $level = ob_get_level();
            ob_start();
            $base->sendContent();

            $out = '';
            while (ob_get_level() > $level) {
                $out = ob_get_clean() . $out;
            }

            return $out;
        }

        if ($base instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
            return (string) file_get_contents($base->getFile()->getPathname());
        }

        return (string) $response->getContent();
    }

    /** @return array<int, array<int, string>> parsed CSV rows */
    private function csvRows(string $body): array
    {
        // Strip the UTF-8 BOM the export writes for Excel.
        $body = preg_replace('/^\xEF\xBB\xBF/', '', $body);

        $rows = [];
        $fh = fopen('php://temp', 'r+');
        fwrite($fh, $body);
        rewind($fh);
        while (($r = fgetcsv($fh)) !== false) {
            $rows[] = $r;
        }
        fclose($fh);

        return $rows;
    }

    /**
     * Everything before the column headings is the branded band; the headings row
     * is the first one carrying more than a single cell.
     *
     * @return array{0: array<int,string>, 1: array<int, array<int,string>>}
     */
    private function splitCsv(string $body): array
    {
        $rows = $this->csvRows($body);

        foreach ($rows as $i => $row) {
            if (count($row) > 1) {
                return [$row, array_slice($rows, $i + 1)];
            }
        }

        $this->fail('No heading row found in the exported CSV.');
    }

    public function test_every_format_is_served_and_an_unknown_format_is_rejected(): void
    {
        $user = $this->admin();

        foreach (['csv', 'excel', 'pdf', 'print'] as $format) {
            $this->refreshApplication();
            $this->actingAs($user)
                ->get('/master/faculty-expertise/export/' . $format)
                ->assertOk();
        }

        $this->refreshApplication();
        $this->actingAs($user)
            ->get('/master/faculty-expertise/export/zip')
            ->assertNotFound();
    }

    public function test_the_format_allow_list_is_not_case_or_path_sensitive(): void
    {
        $user = $this->admin();

        // Upper case is folded, so a link that shouts still works...
        $this->actingAs($user)->get('/master/faculty-expertise/export/CSV')->assertOk();

        // ...but anything outside the list is refused rather than defaulted.
        $this->refreshApplication();
        $this->actingAs($user)->get('/master/faculty-expertise/export/html')->assertNotFound();
    }

    public function test_cols_is_intersected_against_the_canonical_list_and_cannot_reorder_the_report(): void
    {
        $user = $this->admin();

        // Ask for the columns out of order, and for one that does not exist.
        $response = $this->actingAs($user)
            ->get('/master/faculty-expertise/export/csv?cols=status,nonexistent,expertise')
            ->assertOk();

        [$headings] = $this->splitCsv($this->bodyOf($response));

        // Canonical order is sno, expertise, status — the request cannot invert it,
        // and the unknown key contributes no column.
        $this->assertSame(['Faculty Expertise', 'Status'], $headings);
    }

    public function test_an_entirely_unknown_cols_list_falls_back_to_every_column(): void
    {
        $user = $this->admin();

        $all = $this->bodyOf(
            $this->actingAs($user)->get('/master/faculty-expertise/export/csv')->assertOk()
        );

        $this->refreshApplication();
        $bogus = $this->bodyOf(
            $this->actingAs($this->admin())->get('/master/faculty-expertise/export/csv?cols=nope')->assertOk()
        );

        [$allHeadings] = $this->splitCsv($all);
        [$bogusHeadings] = $this->splitCsv($bogus);

        $this->assertSame($allHeadings, $bogusHeadings);
        $this->assertSame(['S. No.', 'Faculty Expertise', 'Status'], $allHeadings);
    }

    public function test_the_export_row_count_matches_the_table_and_the_header_band_states_it(): void
    {
        $user = $this->admin();
        $expected = FacultyExpertiseMaster::query()->count();

        $body = $this->bodyOf(
            $this->actingAs($user)->get('/master/faculty-expertise/export/csv')->assertOk()
        );

        [, $dataRows] = $this->splitCsv($body);

        $this->assertCount($expected, $dataRows, 'Export must carry every row the grid would show.');
        $this->assertStringContainsString('Total Records: ' . number_format($expected), $body);
    }

    public function test_a_search_term_narrows_the_export_and_is_named_in_the_header(): void
    {
        $needle = FacultyExpertiseMaster::query()->value('expertise_name');

        if (! $needle) {
            $this->markTestSkipped('No faculty expertise rows to search for.');
        }

        $expected = FacultyExpertiseMaster::query()
            ->where('expertise_name', 'like', '%' . $needle . '%')
            ->count();

        $body = $this->bodyOf(
            $this->actingAs($this->admin())
                ->get('/master/faculty-expertise/export/csv?q=' . urlencode($needle))
                ->assertOk()
        );

        [, $dataRows] = $this->splitCsv($body);

        $this->assertCount($expected, $dataRows);
        // The filter line must state what was applied, or the report lies about its scope.
        $this->assertStringContainsString('Search: ' . $needle, $body);
    }

    /**
     * The grid's search also matches the rendered Active/Inactive label, so the
     * export has to match those labels too — otherwise searching "inactive"
     * shows rows on screen and exports nothing.
     */
    public function test_the_export_search_matches_the_rendered_status_label_like_the_grid_does(): void
    {
        $activeCount = FacultyExpertiseMaster::query()->where('active_inactive', 1)->count();

        if ($activeCount === 0) {
            $this->markTestSkipped('No active faculty expertise rows.');
        }

        $body = $this->bodyOf(
            $this->actingAs($this->admin())
                ->get('/master/faculty-expertise/export/csv?q=Active')
                ->assertOk()
        );

        [, $dataRows] = $this->splitCsv($body);

        // "Active" is a substring of "Inactive", exactly as in the browser, so
        // every row matches — the point is that the status label is searched at all.
        $this->assertCount(FacultyExpertiseMaster::query()->count(), $dataRows);
    }

    public function test_the_csv_neutralises_a_value_that_a_spreadsheet_would_run_as_a_formula(): void
    {
        $row = FacultyExpertiseMaster::query()->first();

        if (! $row) {
            $this->markTestSkipped('No faculty expertise rows to rename.');
        }

        // DatabaseTransactions rolls this back.
        $row->expertise_name = '=HYPERLINK("http://x","c")';
        $row->save();

        $body = $this->bodyOf(
            $this->actingAs($this->admin())->get('/master/faculty-expertise/export/csv')->assertOk()
        );

        $this->assertStringContainsString('\'=HYPERLINK', $body);
        $this->assertStringNotContainsString(',=HYPERLINK', $body);
    }

    public function test_the_workbook_contains_no_formula_cells(): void
    {
        $row = FacultyExpertiseMaster::query()->first();

        if (! $row) {
            $this->markTestSkipped('No faculty expertise rows to rename.');
        }

        $row->expertise_name = '=1+1';
        $row->save();

        $body = $this->bodyOf(
            $this->actingAs($this->admin())->get('/master/faculty-expertise/export/excel')->assertOk()
        );

        $path = tempnam(sys_get_temp_dir(), 'mge') . '.xlsx';
        file_put_contents($path, $body);

        try {
            $sheet = IOFactory::load($path)->getActiveSheet();

            $formulas = 0;
            foreach ($sheet->getRowIterator() as $sheetRow) {
                foreach ($sheetRow->getCellIterator() as $cell) {
                    if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                        $formulas++;
                    }
                }
            }

            $this->assertSame(0, $formulas, 'An exported workbook must never contain a live formula.');
        } finally {
            @unlink($path);
        }
    }

    /**
     * Regression for the step wizard: the submit control must be reachable from
     * every step. It used to ship with `d-none` and was revealed only on the last
     * step, so a record that could not clear an earlier step's `required` fields
     * had no reachable way to save at all.
     */
    public function test_the_faculty_edit_form_exposes_a_submit_control_that_is_not_hidden(): void
    {
        $faculty = FacultyMaster::query()->orderBy('pk')->first();

        if (! $faculty) {
            $this->markTestSkipped('No faculty rows to edit.');
        }

        // Rendering any page in this application leaves the output-buffer level one
        // higher than it found it (reproduced on /faculty and /master/* too, so it
        // is not this route). Unwind it, or PHPUnit marks the test risky for
        // something the framework did.
        $obLevel = ob_get_level();

        $html = $this->actingAs($this->admin())
            ->get('/faculty/edit/' . encrypt($faculty->getKey()))
            ->assertOk()
            ->getContent();

        while (ob_get_level() > $obLevel) {
            ob_end_clean();
        }

        $this->assertMatchesRegularExpression(
            '/<button[^>]*data-mst-final/',
            $html,
            'The edit form must render a [data-mst-final] submit control.'
        );

        preg_match('/<button([^>]*)data-mst-final/', $html, $m);
        $this->assertNotEmpty($m, 'Could not locate the submit control markup.');
        $this->assertStringNotContainsString(
            'd-none',
            $m[1],
            'The submit control must not ship hidden — it is the only way to save the record.'
        );
    }
}
