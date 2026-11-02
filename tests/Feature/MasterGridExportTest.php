<?php

namespace Tests\Feature;

use App\Models\FacultyExpertiseMaster;
use App\Models\FacultyMaster;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
 * where the transaction opened in setUp() covers a write, and skips rather than
 * fails when the fixture data a case needs is not present - or when there is no
 * database at all.
 */
class MasterGridExportTest extends TestCase
{
    private bool $inTransaction = false;

    /**
     * The transaction is opened by hand rather than by DatabaseTransactions.
     *
     * That trait is booted from parent::setUp() and opens the connection there,
     * so a guard placed after that call can never run: on a host with no
     * database this file reported 23 errors while its own docblock claimed it
     * skipped. Same shape as ToggleStatusEndpointTest and StreamDeleteGuardTest.
     */
    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('the master grid export tests need the application database');
        }

        DB::beginTransaction();
        $this->inTransaction = true;
    }

    protected function tearDown(): void
    {
        if ($this->inTransaction) {
            DB::rollBack();
            $this->inTransaction = false;
        }

        parent::tearDown();
    }

    private function admin(): User
    {
        $user = User::query()->orderBy('pk')->first();

        if (! $user) {
            $this->markTestSkipped('No user rows in this database to act as.');
        }

        return $user;
    }

    /**
     * A throw-away expertise row carrying $name.
     *
     * Renaming an existing row (what this file used to do) meant the only thing
     * standing between the suite and a real row named after a formula was the
     * transaction; a connection drop mid-test left the damage behind. Creating a
     * row instead makes the rollback a tidy-up rather than a safety barrier.
     */
    private function probeExpertise(string $name): FacultyExpertiseMaster
    {
        $row = new FacultyExpertiseMaster();
        $row->expertise_name = $name;
        $row->save();

        return $row;
    }

    /** A throw-away faculty row; only the columns the workbook reads are set. */
    private function probeFaculty(array $attributes = []): FacultyMaster
    {
        $row = new FacultyMaster();
        $row->faculty_type              = 'Probe';
        $row->first_name                = 'ExportProbe';
        $row->country_master_pk         = 0;
        $row->state_master_pk           = 0;
        $row->state_district_mapping_pk = 0;
        $row->city_master_pk            = 0;

        foreach ($attributes as $column => $value) {
            $row->{$column} = $value;
        }

        $row->save();

        return $row;
    }

    /** The cell whose value is exactly $text, or null. */
    private function findCell(string $path, string $text)
    {
        $sheet = IOFactory::load($path)->getActiveSheet();

        foreach ($sheet->getRowIterator() as $sheetRow) {
            foreach ($sheetRow->getCellIterator() as $cell) {
                if ((string) $cell->getValue() === $text) {
                    return $cell;
                }
            }
        }

        return null;
    }

    /** Count live formula cells in a workbook. */
    private function formulaCellsIn(string $path): int
    {
        $sheet = IOFactory::load($path)->getActiveSheet();

        $formulas = 0;
        foreach ($sheet->getRowIterator() as $sheetRow) {
            foreach ($sheetRow->getCellIterator() as $cell) {
                if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                    $formulas++;
                }
            }
        }

        return $formulas;
    }

    /** Write a response body to a temp .xlsx and hand the path to $assert. */
    private function withWorkbook(string $body, callable $assert): void
    {
        $path = tempnam(sys_get_temp_dir(), 'mge') . '.xlsx';
        file_put_contents($path, $body);

        try {
            $assert($path);
        } finally {
            @unlink($path);
        }
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
        // The apostrophe is the correct mitigation HERE and only here: a CSV
        // has no cell types, so a leading quote is the only way to tell Excel
        // the rest of the field is literal text. The .xlsx path uses the cell
        // TYPE instead - see the workbook tests below.
        $this->probeExpertise('=HYPERLINK("http://x","c")');

        $body = $this->bodyOf(
            $this->actingAs($this->admin())->get('/master/faculty-expertise/export/csv')->assertOk()
        );

        $this->assertStringContainsString('\'=HYPERLINK', $body);
        $this->assertStringNotContainsString(',=HYPERLINK', $body);
    }

    public function test_the_workbook_stores_a_formula_like_value_as_plain_text_without_an_apostrophe(): void
    {
        $this->probeExpertise('=1+1');

        $body = $this->bodyOf(
            $this->actingAs($this->admin())->get('/master/faculty-expertise/export/excel')->assertOk()
        );

        $this->withWorkbook($body, function (string $path) {
            $this->assertSame(0, $this->formulaCellsIn($path), 'An exported workbook must never contain a live formula.');

            // The value must be the four characters someone typed - not a formula,
            // and not '=1+1 either. PhpSpreadsheet stores a leading apostrophe as
            // DATA, so the CSV mitigation applied here would reach the reader as
            // visible corruption of the cell.
            $cell = $this->findCell($path, '=1+1');

            $this->assertNotNull($cell, 'The exported value should appear verbatim in the workbook.');
            $this->assertSame(DataType::TYPE_STRING, $cell->getDataType(), 'A formula-like value must be typed as text.');
        });
    }

    /**
     * Identifiers must survive the round trip digit for digit.
     *
     * Excel holds 15 significant digits, and the default value binder types a
     * digit-only string as a NUMBER - so a 16-digit account number came back
     * rounded. That is silent corruption of exactly the field a reader would
     * copy into a payment form. faculty_master.Account_No reaches 16 digits in
     * this database, so this is a real row shape, not a hypothetical one.
     */
    public function test_a_long_numeric_identifier_keeps_every_digit_in_the_workbook(): void
    {
        $this->probeFaculty([
            'Account_No' => '7755000100020824',
            'mobile_no'  => '+91 9876543210',
        ]);

        $body = $this->bodyOf(
            $this->actingAs($this->admin())->get('/faculty/excel-export')->assertOk()
        );

        $this->withWorkbook($body, function (string $path) {
            $account = $this->findCell($path, '7755000100020824');
            $this->assertNotNull($account, 'A 16-digit account number must appear unrounded.');
            $this->assertSame(DataType::TYPE_STRING, $account->getDataType());

            $mobile = $this->findCell($path, '+91 9876543210');
            $this->assertNotNull($mobile, 'A +91 mobile must appear without a leading apostrophe.');
            $this->assertSame(DataType::TYPE_STRING, $mobile->getDataType());
        });
    }

    /**
     * The same property, on the full-detail workbook.
     *
     * FacultyExport is a separate 34-column export reached from the same
     * Download menu. It passed raw strings to PhpSpreadsheet, whose default
     * binder stores a leading "=" as a real formula cell - so the most
     * data-rich download in the module was the one export without the
     * protection its siblings had.
     */
    public function test_the_full_detail_faculty_workbook_contains_no_formula_cells(): void
    {
        $this->probeFaculty(['first_name' => '=HYPERLINK("http://x","c")']);

        $body = $this->bodyOf(
            $this->actingAs($this->admin())->get('/faculty/excel-export')->assertOk()
        );

        $this->withWorkbook($body, function (string $path) {
            $this->assertSame(0, $this->formulaCellsIn($path), 'The full-detail workbook must never contain a live formula.');

            $cell = $this->findCell($path, '=HYPERLINK("http://x","c")');
            $this->assertNotNull($cell);
            $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
        });
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

    /**
     * Section 19.1, "disallowed is denied": the export routes must refuse a
     * request that carries no session.
     *
     * The route group's `auth` middleware was verified structurally during
     * review, but nothing executed it - so a middleware regression, or a route
     * moved out of the group by a careless merge, would have shipped these
     * downloads to anonymous callers with the suite still green. Personal data
     * (names, email addresses, mobile numbers, and bank columns in the
     * full-detail workbook) makes that the expensive kind of regression.
     *
     * @dataProvider exportRoutes
     */
    public function test_an_export_route_denies_an_unauthenticated_request(string $uri): void
    {
        $response = $this->get($uri);

        $this->assertNotSame(
            200,
            $response->getStatusCode(),
            $uri . ' served an export to a caller with no session.'
        );

        // The app redirects guests to the login screen rather than 401-ing.
        $response->assertRedirect();
    }

    /** Every export route this PR added, plus the full-detail workbook. */
    public static function exportRoutes(): array
    {
        return [
            'faculty expertise (csv)'  => ['/master/faculty-expertise/export/csv'],
            'faculty expertise (xlsx)' => ['/master/faculty-expertise/export/excel'],
            'faculty type (csv)'       => ['/master/faculty-type-master/export/csv'],
            'appellation (csv)'        => ['/admin/appellation/export/csv'],
            'faculty grid (csv)'       => ['/faculty/export/csv'],
            'faculty grid (pdf)'       => ['/faculty/export/pdf'],
            'faculty full workbook'    => ['/faculty/excel-export'],
        ];
    }

    /**
     * Section 17: an export of personal data leaves an audit record.
     *
     * Asserted on the trait rather than per controller, because that is where
     * the single call site lives - all four adopters and all four formats
     * funnel through renderMasterExport().
     */
    public function test_a_grid_export_writes_an_audit_record(): void
    {
        Log::spy();

        $this->actingAs($this->admin())
            ->get('/master/faculty-expertise/export/csv')
            ->assertOk();

        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context = []) {
                return $message === 'Master grid export'
                    && ($context['slug'] ?? null) === 'FacultyExpertise'
                    && ($context['format'] ?? null) === 'csv'
                    && array_key_exists('actor', $context)
                    && array_key_exists('rows', $context);
            })
            ->once();
    }

    /**
     * The audit record must survive a hostile search term.
     *
     * This goes through the REAL Monolog stack rather than a spy, because the
     * defect lives in the formatter: LineFormatter writes one record per line
     * but keeps inline line breaks, so ?q=x%0A<a plausible record> used to end
     * the genuine line and open a forged one naming any actor and row count.
     * Counting records in an actual log file is the only assertion that can see
     * that; a spy sees one call either way.
     */
    public function test_a_search_term_with_a_line_break_cannot_forge_a_second_audit_record(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'auditprobe') . '.log';

        config()->set('logging.channels.audit_probe', [
            'driver' => 'single',
            'path'   => $path,
            'level'  => 'debug',
        ]);
        config()->set('logging.default', 'audit_probe');
        Log::forgetChannel();

        $forged = "x\n[2026-09-15 10:00:00] production.INFO: Master grid export "
            . '{"actor":42,"slug":"Faculty","format":"excel","rows":668}';

        try {
            $this->actingAs($this->admin())
                ->get('/master/faculty-expertise/export/csv?q=' . rawurlencode($forged))
                ->assertOk();

            $written = trim(file_get_contents($path));
            $lines   = preg_split('/\R/', $written) ?: [];

            // A log RECORD is a line beginning with a timestamp; an export audit
            // record is one whose MESSAGE is "Master grid export". The forged
            // payload survives as text inside the filter value - LogSafe
            // neutralises, it does not censor - but it can no longer open a
            // record of its own, which is the whole of the attack.
            $auditRecords = preg_grep('/^\[\d{4}-\d{2}-\d{2}[^\]]*\] \w+\.INFO: Master grid export /', $lines);

            $this->assertCount(
                1,
                $auditRecords,
                'One export call must leave exactly one audit record, not a second forged one.'
            );

            // The surviving record carries the search term, on that same line.
            $this->assertStringContainsString('"filter":"Search: x ', implode('', $auditRecords));
        } finally {
            Log::forgetChannel();
            @unlink($path);
        }
    }

    /**
     * The same property for the full-detail workbook - the widest personal-data
     * export in the module, and the one that carries the bank columns.
     */
    public function test_the_full_detail_workbook_writes_an_audit_record(): void
    {
        Log::spy();

        $this->actingAs($this->admin())
            ->get('/faculty/excel-export')
            ->assertOk();

        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context = []) {
                return $message === 'Faculty full-detail workbook export'
                    && array_key_exists('actor', $context);
            })
            ->once();
    }

    /**
     * created_by records who created the row, so a later edit by someone else
     * must not rewrite it.
     *
     * Before this PR the assignment was unconditional but wrote NULL, because
     * User::$primaryKey is `pk` and the code read ->id. The PR fixed the NULL
     * and left the assignment unconditional, which turned a column that was
     * always empty into one that was quietly wrong - it recorded the last
     * editor under a created_by heading. This pins the create-only behaviour.
     */
    public function test_editing_an_expertise_row_does_not_rewrite_its_created_by(): void
    {
        $users = User::query()->orderBy('pk')->limit(2)->get();

        if ($users->count() < 2) {
            $this->markTestSkipped('Need two users to tell author from editor.');
        }

        [$author, $editor] = [$users[0], $users[1]];
        $name = 'ZZ Review Probe ' . substr((string) microtime(true), -6);

        // DatabaseTransactions rolls both writes back.
        $this->actingAs($author)
            ->post('/master/faculty-expertise/store', ['expertise_name' => $name])
            ->assertRedirect();

        $row = FacultyExpertiseMaster::query()->where('expertise_name', $name)->first();
        $this->assertNotNull($row, 'The probe row was not created.');
        $this->assertSame($author->getKey(), $row->created_by, 'created_by must be the creator.');

        // The controller reads the encrypted `id` field, not `pk`. Posting the
        // wrong key silently creates a second row instead of editing this one,
        // and the assertion below would then pass without exercising an edit.
        $this->actingAs($editor)
            ->post('/master/faculty-expertise/store', [
                'id'             => encrypt($row->pk),
                'expertise_name' => $name . ' edited',
            ])
            ->assertRedirect();

        $edited = FacultyExpertiseMaster::query()->find($row->pk);

        // Proves the update path really ran, so the created_by assertion below
        // cannot pass merely because nothing happened.
        $this->assertSame($name . ' edited', $edited->expertise_name, 'The edit did not take effect.');

        $this->assertSame(
            $author->getKey(),
            (int) $edited->created_by,
            'An edit by a second user must not overwrite created_by.'
        );
    }
}
