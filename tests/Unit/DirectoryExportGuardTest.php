<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\DirectoryController;
use App\Http\Middleware\EnsureDirectoryExportAccess;
use App\Exports\DirectoryGridExport;
use App\Support\ExportCell;
use App\Support\LogText;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Cell as SpreadsheetCell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use ReflectionClass;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Guards for the directory export layer (docs/new-design-index-page.md).
 *
 * Deliberately DB-free: every assertion drives a pure resolver through
 * reflection, the route table, or a middleware object with a stub actor, so
 * the suite's shared-database constraint (see phpunit.xml) never applies.
 * The DB-backed half of the same guarantees lives in
 * tests/Feature/DirectoryExportAccessTest.php, which skips without a
 * connection.
 */
class DirectoryExportGuardTest extends TestCase
{
    /** What fetchCappedExportRows() hands the capper: EXPORT_ROW_CAP + 1. */
    private const FETCHED_SLICE = 1501;

    /** @return mixed */
    private function invokePrivate(string $method, array $args = [])
    {
        $controller = new DirectoryController();
        $ref = (new ReflectionClass($controller))->getMethod($method);
        $ref->setAccessible(true);

        return $ref->invokeArgs($controller, $args);
    }

    private function defs(): array
    {
        return $this->invokePrivate('otExportColumnDefs');
    }

    private function request(array $query): Request
    {
        return Request::create('/directory/ot/export/csv', 'GET', $query);
    }

    /**
     * An authenticated actor with no database behind it.
     *
     * hasRole() consults the session first and only then asks the user
     * object, so a stub that answers hasRole() itself lets BOTH branches of
     * the export gate execute without a connection — which is the point: the
     * denied case is the one that must never regress unnoticed.
     */
    private function gateUser(bool $superAdmin = false): Authenticatable
    {
        return new class($superAdmin) implements Authenticatable
        {
            /** user_credentials.user_name — the auth middleware logs it when app.debug is on. */
            public $user_name = 'directory-gate-stub';

            private bool $superAdmin;

            public function __construct(bool $superAdmin)
            {
                $this->superAdmin = $superAdmin;
            }

            public function hasRole($role): bool
            {
                return $this->superAdmin && in_array($role, ['Super Admin', 'SuperAdmin'], true);
            }

            public function getAuthIdentifierName()
            {
                return 'pk';
            }

            public function getAuthIdentifier()
            {
                return 4242;
            }

            public function getAuthPassword()
            {
                return '';
            }

            public function getRememberToken()
            {
                return '';
            }

            public function setRememberToken($value)
            {
                //
            }

            public function getRememberTokenName()
            {
                return '';
            }
        };
    }

    // ── Spreadsheet formula injection ─────────────────────────────────────

    /** @dataProvider formulaPrefixes */
    public function test_export_cell_neutralises_formula_prefixes(string $raw): void
    {
        $col = ['value' => fn () => $raw];

        $this->assertSame("'" . $raw, ExportCell::text($col, null, 0));
    }

    public static function formulaPrefixes(): array
    {
        return [
            'equals' => ['=HYPERLINK("http://evil","x")'],
            'plus' => ['+1+1'],
            'at' => ['@SUM(A1)'],
            'tab' => ["\tcmd"],
        ];
    }

    public function test_export_cell_leaves_ordinary_values_alone(): void
    {
        $this->assertSame('Ravi Patel', ExportCell::text(['value' => fn () => 'Ravi Patel'], null, 0));
        $this->assertSame('1', ExportCell::text(['value' => fn ($r, $i) => $i + 1], null, 0));
    }

    public function test_export_cell_leaves_the_empty_placeholder_unquoted(): void
    {
        // "-" is a formula prefix to sanitize_export_cell() but it is this
        // module's own empty marker, so every gap would read "'-".
        $this->assertSame('-', ExportCell::text(['value' => fn () => '-'], null, 0));
    }

    // ── DomPDF must not be a PHP execution context ────────────────────────

    public function test_pdf_partial_carries_no_php_script_block(): void
    {
        $blade = file_get_contents(resource_path('views/admin/directory/partials/export_pdf.blade.php'));

        $this->assertStringNotContainsString('type="text/php"', $blade);
        $this->assertStringNotContainsString('{!!', $blade);
    }

    public function test_pdf_renderer_does_not_enable_php(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Admin/DirectoryController.php'));

        $this->assertStringContainsString("'isPhpEnabled' => false", $source);
        $this->assertStringNotContainsString("'isPhpEnabled' => true", $source);
    }

    // ── The downloads are gated, the grids are not ────────────────────────

    /** @dataProvider exportRouteNames */
    public function test_export_routes_are_gated_and_throttled(string $name): void
    {
        $middleware = app('router')->getRoutes()->getByName($name)->gatherMiddleware();

        $this->assertContains('auth', $middleware);
        $this->assertContains('directory.export', $middleware);
        $this->assertNotEmpty(preg_grep('/^throttle:/', $middleware));
    }

    public static function exportRouteNames(): array
    {
        return [
            ['admin.directory.ot.export'],
            ['admin.directory.lbsnaa.export'],
        ];
    }

    /** @dataProvider gridRouteNames */
    public function test_grid_routes_stay_open_to_any_authenticated_user(string $name): void
    {
        $middleware = app('router')->getRoutes()->getByName($name)->gatherMiddleware();

        $this->assertContains('auth', $middleware);
        $this->assertNotContains('directory.export', $middleware);
    }

    public static function gridRouteNames(): array
    {
        return [
            ['admin.directory.ot'],
            ['admin.directory.ot.data'],
            ['admin.directory.lbsnaa'],
            ['admin.directory.lbsnaa.data'],
        ];
    }

    /**
     * The denied case, end to end: the request goes through the real HTTP
     * kernel and the real middleware stack, and is refused with 403 before it
     * reaches the controller. Asserting that the route CARRIES the alias only
     * proves the wiring; this proves the wiring actually refuses somebody.
     *
     * The exception is asserted rather than the response because rendering the
     * 403 PAGE pulls in the admin layout, which reads columns off a real user
     * row — a database, which this suite does without.
     *
     * @dataProvider exportRouteNames
     */
    public function test_export_routes_refuse_a_non_privileged_user(string $name): void
    {
        $this->withoutExceptionHandling();
        $this->actingAs($this->gateUser(false));

        try {
            $this->get(route($name, ['format' => 'csv']));
            $this->fail("{$name} must refuse a non-privileged user");
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    /**
     * The allowed case, at the middleware rather than the route: past the
     * gate the controller queries the directory, and this suite is DB-free by
     * construction. The 200 through the full stack is asserted in
     * tests/Feature/DirectoryExportAccessTest.php.
     */
    public function test_export_gate_passes_a_privileged_user_through(): void
    {
        $this->actingAs($this->gateUser(true));

        $reached = false;
        $response = (new EnsureDirectoryExportAccess())->handle(
            $this->request([]),
            function () use (&$reached) {
                $reached = true;

                return response('served');
            }
        );

        $this->assertTrue($reached, 'a Super Admin must reach the export action');
        $this->assertSame('served', $response->getContent());
    }

    public function test_export_gate_aborts_403_for_a_non_privileged_user(): void
    {
        $this->actingAs($this->gateUser(false));

        $reached = false;

        try {
            (new EnsureDirectoryExportAccess())->handle($this->request([]), function () use (&$reached) {
                $reached = true;

                return response('served');
            });
            $this->fail('the gate must abort for a non-privileged user');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertFalse($reached, 'the export action must never run for a refused user');
    }

    /** @dataProvider exportRouteNames */
    public function test_format_allow_list_matches_the_controller(string $name): void
    {
        $pattern = app('router')->getRoutes()->getByName($name)->wheres['format'] ?? '';

        foreach (['csv', 'excel', 'pdf', 'print', 'full'] as $format) {
            $this->assertSame(1, preg_match('#^' . $pattern . '$#', $format), "{$format} should be routable");
        }

        $this->assertSame(0, preg_match('#^' . $pattern . '$#', 'php'));
        $this->assertSame(0, preg_match('#^' . $pattern . '$#', '../../etc/passwd'));
    }

    // ── ?cols= is whitelisted, never trusted ──────────────────────────────

    public function test_absent_cols_yields_every_column(): void
    {
        $resolved = $this->invokePrivate('resolveDirectoryExportColumns', [$this->request([]), $this->defs()]);

        $this->assertSame(array_keys($this->defs()), array_keys($resolved));
    }

    public function test_unrecognised_cols_falls_back_to_every_column(): void
    {
        $resolved = $this->invokePrivate('resolveDirectoryExportColumns', [
            $this->request(['cols' => 'password,salary']), $this->defs(),
        ]);

        $this->assertSame(array_keys($this->defs()), array_keys($resolved));
    }

    public function test_cols_cannot_reorder_the_report(): void
    {
        $resolved = $this->invokePrivate('resolveDirectoryExportColumns', [
            $this->request(['cols' => 'cadre,name,sno']), $this->defs(),
        ]);

        // Canonical def order, not the order the URL asked for.
        $this->assertSame(['sno', 'name', 'cadre'], array_keys($resolved));
    }

    public function test_cols_cannot_inject_a_column(): void
    {
        $resolved = $this->invokePrivate('resolveDirectoryExportColumns', [
            $this->request(['cols' => 'name,generated_OT_code,secret']), $this->defs(),
        ]);

        $this->assertSame(['name'], array_keys($resolved));
    }

    // ── Array parameters must not become the string "Array" ───────────────

    public function test_array_cols_parameter_does_not_break_resolution(): void
    {
        $resolved = $this->invokePrivate('resolveDirectoryExportColumns', [
            $this->request(['cols' => ['name', 'email']]), $this->defs(),
        ]);

        $this->assertSame(array_keys($this->defs()), array_keys($resolved));
    }

    // ── "No filter" must not collide with a real pk ───────────────────────

    /**
     * department_master really does hold a row at pk 0 ("NIAR"), so 0 has to
     * survive as a FILTER. Under the old 0-as-absent sentinel the WHERE clause
     * was dropped and the export band still printed the section name — every
     * employee in a file labelled as one section.
     */
    public function test_pk_zero_filters_instead_of_meaning_no_filter(): void
    {
        [$pk, $name] = $this->resolveSection('0', [0 => 'NIAR', 5 => 'Estate']);

        $this->assertSame(0, $pk, 'pk 0 must reach the query as a filter');
        $this->assertSame('NIAR', $name, 'and the header band must name it');
    }

    /** @dataProvider absentFilterInputs */
    public function test_no_filter_resolves_to_null($raw): void
    {
        [$pk, $name] = $this->resolveSection($raw, [0 => 'NIAR', 5 => 'Estate']);

        $this->assertNull($pk);
        $this->assertNull($name);
    }

    public static function absentFilterInputs(): array
    {
        return [
            'absent' => [null],
            'blank' => [''],
            'not a number' => ['dept'],
            'array' => [['0']],
        ];
    }

    // ── An unknown pk must NARROW, never widen ────────────────────────────

    /**
     * The dropdown offers only the sections that currently have an active
     * employee, so a pk can be perfectly real and still be absent from the
     * list: deactivate a one-person section and its own pk stops being offered.
     * Dropping the filter there is fail-open — it turns "the OTs of section X"
     * into a download of every employee in the institute. Executed against the
     * application database, ?section=999999 returned all 444 active employees
     * before this guard and 0 after it.
     */
    public function test_a_pk_the_list_does_not_offer_still_filters(): void
    {
        [$pk, $name] = $this->resolveSection('999', [0 => 'NIAR', 5 => 'Estate'], ['999' => 'Dormant Section']);

        $this->assertSame(999, $pk, 'an unfamiliar pk must reach the query, not vanish from it');
        $this->assertSame('Dormant Section', $name, 'named from the master row so the band matches the rows');
    }

    /** A pk no master row holds still filters — to nothing — and the band says so. */
    public function test_a_pk_no_master_row_holds_filters_to_nothing(): void
    {
        [$pk, $name] = $this->resolveSection('424242', [0 => 'NIAR']);

        $this->assertSame(424242, $pk);
        $this->assertSame('#424242', $name);
    }

    /** Negative pks narrow like any other unknown value; they never widen. */
    public function test_a_negative_pk_filters_rather_than_widening(): void
    {
        [$pk] = $this->resolveSection('-1', [0 => 'NIAR']);

        $this->assertSame(-1, $pk);
    }

    /** The option list answers first, so the common path costs no extra query. */
    public function test_an_offered_pk_costs_no_master_lookup(): void
    {
        $looked = 0;
        [$pk, $name] = $this->resolveSection('5', [5 => 'Estate'], [], $looked);

        $this->assertSame(5, $pk);
        $this->assertSame('Estate', $name);
        $this->assertSame(0, $looked, 'an offered pk must not hit the master table');
    }

    /**
     * A label that came back null must not turn a valid pk into "no filter" —
     * the reason the lookup is has(), not `?? null`.
     */
    public function test_a_null_label_still_filters(): void
    {
        [$pk, $name] = $this->resolveSection('7', [7 => null]);

        $this->assertSame(7, $pk);
        $this->assertSame('', $name);
    }

    /**
     * resolveOptionFilter() with a stubbed master lookup, so these stay DB-free.
     *
     * @param  mixed  $raw
     * @param  array<int|string, ?string>  $options
     * @param  array<int|string, ?string>  $masterRows  what the master table would answer
     * @return array{0: ?int, 1: ?string}
     */
    private function resolveSection($raw, array $options, array $masterRows = [], int &$looked = null): array
    {
        $looked = 0;

        return $this->invokePrivate('resolveOptionFilter', [
            $this->request($raw === null ? [] : ['section' => $raw]),
            'section',
            collect($options),
            function (int $pk) use ($masterRows, &$looked) {
                $looked++;

                return $masterRows[$pk] ?? null;
            },
        ]);
    }

    /** @dataProvider sortInputs */
    public function test_sort_key_is_whitelisted($sort, $dir, string $expectedKey, string $expectedDir): void
    {
        $map = ['name' => 'sm.display_name', 'email' => 'sm.email'];

        $resolved = $this->invokePrivate('resolveDirectorySort', [
            $this->request(['sort' => $sort, 'dir' => $dir]), $map,
        ]);

        $this->assertSame($expectedKey, $resolved['key']);
        $this->assertSame($expectedDir, $resolved['dir']);
    }

    public static function sortInputs(): array
    {
        return [
            'known key' => ['email', 'desc', 'email', 'desc'],
            'unknown key' => ['sm.password', 'asc', 'name', 'asc'],
            'injection attempt' => ['1) OR SLEEP(5)--', 'asc', 'name', 'asc'],
            'array key' => [['email'], 'desc', 'name', 'desc'],
            'unknown dir' => ['name', 'sideways', 'name', 'asc'],
            'array dir' => ['name', ['desc'], 'name', 'asc'],
        ];
    }

    // ── The row cap covers every format, and bounds the query ─────────────

    public function test_rows_under_the_cap_are_untouched(): void
    {
        [$rows, $note] = $this->invokePrivate('capExportRows', [Collection::times(50, fn ($n) => $n)]);

        $this->assertCount(50, $rows);
        $this->assertNull($note);
    }

    public function test_rows_over_the_cap_are_truncated_and_announced(): void
    {
        [$rows, $note] = $this->invokePrivate('capExportRows', [Collection::times(1600, fn ($n) => $n)]);

        $this->assertCount(1500, $rows);
        $this->assertStringContainsString('1,500', $note);
        $this->assertStringContainsString('1,600', $note);
    }

    /**
     * Only CAP + 1 rows are fetched now, so the note's total comes from the
     * caller's COUNT rather than from the slice in hand — otherwise every
     * truncated report would claim exactly 1,501 records.
     */
    public function test_the_cap_note_reports_the_queried_total(): void
    {
        [$rows, $note] = $this->invokePrivate('capExportRows', [
            Collection::times(self::FETCHED_SLICE, fn ($n) => $n), 20482,
        ]);

        $this->assertCount(1500, $rows);
        $this->assertStringContainsString('20,482', $note);
        $this->assertStringNotContainsString('1,501', $note);
    }

    // ── Audit line: one record per download, whatever was submitted ────────

    /**
     * Write one record through a real single-file channel and return its lines.
     *
     * Not a Log fake: the defect lives in the FORMATTER (LineFormatter is built
     * with allowInlineLineBreaks = true), so a fake that captured the context
     * array would report success on exactly the input that breaks the file.
     *
     * @return array<int, string>
     */
    private function writtenLines(string $search): array
    {
        $path = tempnam(sys_get_temp_dir(), 'pr317log');

        try {
            Log::build(['driver' => 'single', 'path' => $path, 'level' => 'debug'])
                ->info('directory.export', ['grid' => 'lbsnaa', 'user_pk' => 7, 'search' => $search]);

            return array_values(array_filter(
                explode(chr(10), (string) file_get_contents($path)),
                static fn (string $line): bool => trim($line) !== ''
            ));
        } finally {
            @unlink($path);
        }
    }

    public function test_a_line_feed_in_the_search_term_cannot_append_a_record(): void
    {
        $forgery = 'x' . chr(10) . '[2026-09-15 10:00:00] production.INFO: directory.export {"user_pk":1}';

        $lines = $this->writtenLines(LogText::inline($forgery));

        $this->assertCount(1, $lines, 'one download must leave exactly one record');
        $this->assertStringContainsString('\\n', $lines[0], 'the submitted break is still visible, escaped');
    }

    /**
     * Negative control for the test above.
     *
     * Without LogText::inline() the same value writes more than one record,
     * one of them a well-formed directory.export line that no download made.
     * Asserting the defect keeps the passing test honest: if the escaping is
     * ever removed, one of these two cases fails.
     */
    public function test_the_unescaped_value_is_what_forges_records(): void
    {
        $forgery = 'x' . chr(10) . '[2026-09-15 10:00:00] production.INFO: directory.export {"user_pk":1}';

        $lines = $this->writtenLines($forgery);

        $this->assertGreaterThan(1, count($lines), 'the premise of the fix');
    }

    /** @dataProvider controlCharacters */
    public function test_log_text_escapes_control_characters(string $raw, string $expected): void
    {
        $this->assertSame($expected, LogText::inline($raw));
    }

    public static function controlCharacters(): array
    {
        return [
            'line feed' => ['a' . chr(10) . 'b', 'a\\nb'],
            'carriage return' => ['a' . chr(13) . 'b', 'a\\rb'],
            'tab' => ['a' . chr(9) . 'b', 'a\\tb'],
            'null byte' => ['a' . chr(0) . 'b', 'a\\x00b'],
            'escape' => ['a' . chr(27) . '[31m', 'a\\x1B[31m'],
            'ordinary text' => ['Ravi Patel', 'Ravi Patel'],
            'a literal backslash-n is not a break' => ['a\\nb', 'a\\nb'],
        ];
    }

    /** Multi-byte text must survive: the escape pass is byte-wise by design. */
    public function test_log_text_leaves_utf8_intact(): void
    {
        $this->assertSame('अन्य पिछड़ा वर्ग', LogText::inline('अन्य पिछड़ा वर्ग'));
    }

    // ── .xlsx cell typing ──────────────────────────────────────────────────

    /**
     * Drive one value through the export's own binder, the way the writer does.
     *
     * @return array{0: mixed, 1: string}
     */
    private function boundCell(string $value, bool $useExportBinder): array
    {
        $previous = SpreadsheetCell::getValueBinder();

        try {
            SpreadsheetCell::setValueBinder($useExportBinder
                ? new DirectoryGridExport(new Collection(), [], '15-09-2026 10:00 AM')
                : new DefaultValueBinder());

            $sheet = (new Spreadsheet())->getActiveSheet();
            $sheet->setCellValue('A1', $value);
            $cell = $sheet->getCell('A1');

            return [$cell->getValue(), $cell->getDataType()];
        } finally {
            SpreadsheetCell::setValueBinder($previous);
        }
    }

    /** @dataProvider spreadsheetValues */
    public function test_the_xlsx_writer_types_every_string_as_text(string $raw): void
    {
        [$value, $type] = $this->boundCell($raw, true);

        $this->assertSame($raw, $value, 'the cell holds what the column resolved, byte for byte');
        $this->assertSame(DataType::TYPE_STRING, $type);
    }

    public static function spreadsheetValues(): array
    {
        return [
            'international mobile' => ['+91 9876543210'],
            'mobile, no spaces' => ['+919876543210'],
            'bare digits' => ['9000000000'],
            'extension with a leading zero' => ['0245'],
            'landline' => ['0135-2222'],
            'formula attempt' => ['=HYPERLINK("http://evil","x")'],
            'at formula' => ['@SUM(A1)'],
            'empty placeholder' => ['-'],
        ];
    }

    /**
     * A typed text cell is not a formula, so the .xlsx path needs no apostrophe.
     *
     * This is the whole argument for ExportCell::raw() on this writer: the
     * neutralisation survives, the apostrophe does not reach the reader.
     */
    public function test_a_formula_is_inert_on_the_xlsx_path_without_an_apostrophe(): void
    {
        [$value, $type] = $this->boundCell('=1+1', true);

        $this->assertSame(DataType::TYPE_STRING, $type, 'never TYPE_FORMULA');
        $this->assertSame('=1+1', $value);
        $this->assertStringStartsNotWith("'", (string) $value, 'the apostrophe belongs to CSV, not to .xlsx');
    }

    /**
     * Negative control: what the default binder does with the same values.
     *
     * Pins the two behaviours the fix exists to replace - a formula is built
     * from "=1+1", and a bare digit string becomes a NUMBER - so the test
     * above cannot quietly start passing for the wrong reason.
     */
    public function test_the_default_binder_is_what_the_fix_replaces(): void
    {
        $this->assertSame(DataType::TYPE_FORMULA, $this->boundCell('=1+1', false)[1]);
        $this->assertSame(DataType::TYPE_NUMERIC, $this->boundCell('9000000000', false)[1]);
    }

    /** raw() is text() without the CSV apostrophe, and nothing else. */
    public function test_export_cell_raw_adds_nothing(): void
    {
        $col = ['value' => fn () => '=1+1'];

        $this->assertSame('=1+1', ExportCell::raw($col, null, 0));
        $this->assertSame("'=1+1", ExportCell::text($col, null, 0));
    }

    /**
     * The CONTROLLER's audit line, not just the escaper it calls.
     *
     * The two tests above prove LogText::inline() works and that the raw value
     * forges records; neither notices if logDirectoryExport() stops calling it.
     * This drives the real method, through the real default channel, with the
     * value arriving the way a request delivers it.
     */
    public function test_the_controllers_audit_line_is_one_record_per_download(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'pr317audit');
        $forgery = 'x' . chr(10) . '[2026-09-15 10:00:00] production.INFO: directory.export {"user_pk":1}';

        try {
            config([
                'logging.default' => 'pr317probe',
                'logging.channels.pr317probe' => ['driver' => 'single', 'path' => $path, 'level' => 'debug'],
            ]);
            Log::forgetChannel();

            $this->invokePrivate('logDirectoryExport', [
                'lbsnaa', 'csv', 'Search: ' . $forgery, $forgery, 3, false,
            ]);

            $lines = array_values(array_filter(
                explode(chr(10), (string) file_get_contents($path)),
                static fn (string $line): bool => trim($line) !== ''
            ));

            $this->assertCount(1, $lines, 'a download must be one auditable record');
            $this->assertStringContainsString('directory.export', $lines[0]);

            // The forged text is still THERE - escaped, inside the record's own
            // context, where a reader can see what was submitted. What it is
            // not is a second line. That distinction is the whole fix, so
            // assert the escape rather than the absence of the payload.
            $this->assertStringContainsString('\n', $lines[0], 'the submitted break survives as an escape');
            $this->assertStringNotContainsString(chr(10), rtrim($lines[0], chr(10) . chr(13)));
        } finally {
            Log::forgetChannel();
            @unlink($path);
        }
    }

    /**
     * The WRITER feeds raw values to its typed cells.
     *
     * Guards the other half of the .xlsx fix: the binder can type every cell
     * as text and the sheet would still show an apostrophe if array() went
     * back to ExportCell::text().
     */
    public function test_the_xlsx_rows_carry_no_csv_apostrophe(): void
    {
        $columns = [
            'mobile' => ['heading' => 'Mobile', 'width' => '', 'align' => 'left', 'value' => fn () => '+91 9876543210'],
            'formula' => ['heading' => 'Note', 'width' => '', 'align' => 'left', 'value' => fn () => '=1+1'],
        ];

        $export = new DirectoryGridExport(
            new Collection([(object) ['pk' => 1]]),
            $columns,
            '15-09-2026 10:00 AM'
        );

        $this->assertSame([['+91 9876543210', '=1+1']], $export->array());
    }

    /**
     * The whole writer, end to end: build a real .xlsx and read the cells back.
     *
     * The binder tests above call bindValue() directly, so they keep passing
     * even if the class stops DECLARING WithCustomValueBinder - in which case
     * Maatwebsite never installs it and the shipped file silently reverts to
     * default typing. Only a round-trip catches that, so this case is the one
     * that actually pins the fix.
     */
    public function test_a_written_xlsx_holds_phone_numbers_as_text(): void
    {
        $samples = ['+91 9876543210', '9000000000', '0245', '=1+1'];

        $columns = [];
        foreach ($samples as $i => $value) {
            $columns['c' . $i] = [
                'heading' => 'C' . $i,
                'width' => '',
                'align' => 'left',
                'value' => fn () => $value,
            ];
        }

        $previous = SpreadsheetCell::getValueBinder();
        $path = tempnam(sys_get_temp_dir(), 'pr317xlsx');

        try {
            SpreadsheetCell::setValueBinder(new DefaultValueBinder());

            file_put_contents($path, Excel::raw(
                new DirectoryGridExport(
                    new Collection([(object) ['pk' => 1]]),
                    $columns,
                    '15-09-2026 10:00 AM'
                ),
                \Maatwebsite\Excel\Excel::XLSX
            ));

            $sheet = IOFactory::load($path)->getActiveSheet();

            // 5 branded header rows, then the column headings, then the data.
            $dataRow = 7;

            foreach ($samples as $i => $value) {
                $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($i + 1) . $dataRow);

                $this->assertSame(DataType::TYPE_STRING, $cell->getDataType(), "[$value] must be a text cell");
                $this->assertSame($value, $cell->getValue(), "[$value] must reach the sheet unchanged");
            }

            $this->assertFalse(
                SpreadsheetCell::getValueBinder() instanceof DirectoryGridExport,
                'the export must put the global value binder back, or it leaks into the next export'
            );
        } finally {
            SpreadsheetCell::setValueBinder($previous);
            @unlink($path);
        }
    }
}
