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

    /**
     * Cited classes that live in a package, not in app/, so the Class::method()
     * check skips them rather than guess a namespace and fail a correct citation.
     */
    private const FRAMEWORK_CLASSES = ['Str', 'DB', 'Log', 'Cache', 'Auth', 'Permission', 'Role', 'Excel'];

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

    /** Write a one-row workbook and hand back its sheet. */
    private function writtenSheet(?string $note, int $columnCount = 1)
    {
        // The Columns modal lets a user export any non-empty subset, and the
        // note is merged across whatever that subset is - so the width it has
        // to live in is a variable, and the tests below need to vary it.
        $headings = ['Name', 'Designation', 'Department', 'Mobile', 'Email',
            'Residence', 'Office', 'Address', 'Course'];

        $columns = [];

        foreach (array_slice($headings, 0, max(1, $columnCount)) as $i => $heading) {
            $columns['col' . $i] = [
                'heading' => $heading,
                'width' => '',
                'align' => 'left',
                'value' => fn () => 'A Person',
            ];
        }

        $previous = SpreadsheetCell::getValueBinder();
        $path = tempnam(sys_get_temp_dir(), 'pr317note');

        try {
            SpreadsheetCell::setValueBinder(new DefaultValueBinder());

            file_put_contents($path, Excel::raw(
                new DirectoryGridExport(
                    new Collection([(object) ['pk' => 1]]),
                    $columns,
                    '15-09-2026 10:00 AM',
                    '',
                    'LBSNAA Directory',
                    $note
                ),
                \Maatwebsite\Excel\Excel::XLSX
            ));

            return IOFactory::load($path)->getActiveSheet();
        } finally {
            SpreadsheetCell::setValueBinder($previous);
            @unlink($path);
        }
    }

    /**
     * A truncated .xlsx must say that it is truncated.
     *
     * This was the one format of the four that did not. The CSV band, the PDF
     * and the print sheet all received the row-cap note; the .xlsx class had no
     * note parameter at all, so "Full Details (Excel)" - the menu item whose
     * stated purpose is the everything dump - arrived with 1,500 rows under a
     * header reading "Total Records: 1,500" and nothing to say the rest had been
     * dropped. An incomplete personal-data extract presented as complete is
     * worse than one that fails, because it gets reconciled against.
     */
    public function test_a_capped_xlsx_states_the_truncation_on_the_sheet(): void
    {
        $note = 'Showing the first 1,500 of 12,345 records — narrow the filters for the rest.';

        $sheet = $this->writtenSheet($note);

        $this->assertSame($note, $sheet->getCell('A5')->getValue(),
            'the row-cap note must reach the workbook');
    }

    /** And an uncapped one carries no note row, so the band means something. */
    public function test_an_uncapped_xlsx_carries_no_note(): void
    {
        $sheet = $this->writtenSheet(null);

        $this->assertSame('', (string) $sheet->getCell('A5')->getValue(),
            'an uncapped export must not claim to be truncated');
    }

    /**
     * The gate's ACCESS DECISION block must not state the outcome as an
     * absolute that handle() contradicts.
     *
     * The block opened with "only a Super Admin may download" while handle()
     * admits `isSidebarPrivilegedUser() || $this->holdsExportPermission()`, and
     * the same docblock described the permission path forty lines further down.
     * Someone auditing who may extract this PII reads the headed block, not the
     * whole file.
     */
    public function test_the_access_decision_block_names_the_permission_path(): void
    {
        $source = file_get_contents(app_path('Http/Middleware/EnsureDirectoryExportAccess.php'));

        $start = strpos($source, 'ACCESS DECISION');
        $this->assertNotFalse($start, 'the ACCESS DECISION block should exist');

        // To the end of the "After:" paragraph - the part an auditor reads.
        $block = substr($source, $start, 1200);

        $this->assertStringNotContainsString(
            'only a Super Admin may download',
            $block,
            'the ACCESS DECISION block states an outcome that handle() does not enforce'
        );
        $this->assertStringContainsString(
            \App\Http\Middleware\EnsureDirectoryExportAccess::EXPORT_PERMISSION,
            $block,
            'the block must name the grantable permission it shares the decision with'
        );
    }

    /**
     * F-007: the block must not present an empty permissions table as a control.
     *
     * It used to end "nothing holds that permission today, so in practice this
     * reads Super Admin only until somebody grants it" - a statement about data,
     * written where a boundary is read. Granting is not an administrative act
     * here: POST roles/permissions/{id} carries auth alone and creates whatever
     * name it is posted, so the account this gate refuses can grant itself the
     * permission this gate honours. An auditor reads this block; it has to say
     * so until that endpoint is fixed.
     */
    public function test_the_access_decision_block_names_the_ungated_grant_endpoint(): void
    {
        $source = file_get_contents(app_path('Http/Middleware/EnsureDirectoryExportAccess.php'));

        $start = strpos($source, 'ACCESS DECISION');
        $this->assertNotFalse($start, 'the ACCESS DECISION block should exist');

        $block = substr($source, $start, 2600);

        $this->assertStringContainsString(
            'roles/permissions/{id}',
            $block,
            'the block presents the permission as a control without saying who may grant it'
        );
        $this->assertStringContainsString(
            'NOT A BOUNDARY',
            $block,
            'the permission branch must be described as a convenience while the grant endpoint is ungated'
        );
    }

    /**
     * F-008: "reversible without a deploy" needs a procedure somebody can follow.
     *
     * The roles screen offers only the names menus rows carry, and the
     * permissions CRUD route is commented out, so no screen exists whose purpose
     * is granting this. The remedy is real but it is a database action, and a
     * release manager reading "grant the permission" during an incident has to
     * find the how somewhere.
     *
     * This docblock used to add "a menu's permission_name is Str::slug($name,
     * '_'), which cannot contain a dot - so `directory.export` can never appear
     * on it". That is true of MenuService::store() and false of
     * MenuService::update(); see PR #317 F-011 and the test below that pins the
     * difference to source.
     */
    public function test_the_grant_procedure_is_written_down(): void
    {
        $sources = [
            'the middleware' => file_get_contents(app_path('Http/Middleware/EnsureDirectoryExportAccess.php')),
            'the deploy notes' => file_get_contents(base_path('docs/deploy-notes-directory-redesign.md')),
        ];

        foreach ($sources as $label => $source) {
            $this->assertStringContainsString(
                'role_has_permissions',
                $source,
                "{$label} promises the grant is reversible without a deploy but does not say how it is performed"
            );
            $this->assertStringContainsString(
                'permission:cache-reset',
                $source,
                "{$label} documents a hand-written permission row without the cache reset that makes it visible"
            );
        }
    }

    /**
     * F-010: a migration named in these documents must exist in this tree.
     *
     * Three rounds running, the docblock written to close one finding introduced
     * the next, and both times the defect was the same shape: a confident claim
     * about code somewhere else. Here it was "ship a guarded migration, as
     * 2026_09_16_090000_add_member_pii_read_permission does for the member
     * module" - a migration that lives only on an unmerged branch, so the only
     * worked example of the recommended pattern could not be opened from this
     * tree at all.
     *
     * Reading more carefully is not the fix for that; resolving the name is. A
     * migration filename is mechanically recognisable (four date-ish segments
     * then a snake_case tail), so any that these two documents name is required
     * to resolve against database/migrations. Prose that only DESCRIBES a
     * pattern names no file and is unaffected.
     *
     * SCOPE, stated exactly, because an earlier version of this docblock claimed
     * the wider job of catching "a confident claim about code somewhere else"
     * and does not do it (PR #317 F-012). This test guarantees ONE thing: a
     * migration FILENAME named in these two documents resolves under
     * database/migrations. It is blind to every claim that names no migration.
     * Measured against the defect it was written for: at eaa87d1c5 the
     * middleware's citation returned one hit and the deploy note's equally false
     * "as the member module now does" returned none, so this check would have
     * caught half of F-010 and none of F-011. The two tests below cover the
     * other resolvable forms - Class::method() citations, and the specific
     * behavioural claim these documents rest on.
     */
    public function test_every_migration_named_in_the_export_docs_exists(): void
    {
        $sources = [
            'the middleware' => file_get_contents(app_path('Http/Middleware/EnsureDirectoryExportAccess.php')),
            'the deploy notes' => file_get_contents(base_path('docs/deploy-notes-directory-redesign.md')),
        ];

        $pattern = '/\b\d{4}_\d{2}_\d{2}_\d{6}_[a-z0-9_]+/i';

        // Prove the detector fires before trusting it to find nothing. Without
        // this, a regex that silently stopped matching would leave the loop
        // below checking zero strings and passing for the wrong reason - which
        // is the "green proves self-consistency, not correctness" trap.
        $this->assertSame(
            1,
            preg_match($pattern, 'as 2026_09_16_090000_add_member_pii_read_permission does for'),
            'the migration-name detector no longer matches a known migration filename'
        );

        foreach ($sources as $label => $source) {
            preg_match_all($pattern, $source, $hits);

            foreach (array_unique($hits[0]) as $name) {
                $this->assertFileExists(
                    database_path('migrations/' . $name . '.php'),
                    "{$label} names the migration {$name} as though it were in this tree, but no such file "
                    . 'exists under database/migrations. Either ship it, or describe the pattern without '
                    . 'naming a file (PR #317 F-010).'
                );
            }
        }
    }

    /**
     * F-012: a Class::method() the export docs cite must resolve to real code.

     * The migration check above recognises one syntactic form. This is the next
     * one these two documents actually use: they explain themselves by pointing
     * at application methods - MenuService::store(), MenuService::update() - and
     * a citation of a method that has been renamed or never existed reads
     * exactly like one that has not.
     *
     * Only classes under app/ are resolved. A cited framework class is skipped
     * by name through FRAMEWORK_CLASSES, because resolving those means guessing
     * a namespace, and a wrong guess would fail the build over a correct
     * citation. Adding to that list is a deliberate act, which is the point.
     */
    public function test_every_class_method_named_in_the_export_docs_resolves(): void
    {
        $sources = [
            'the middleware' => file_get_contents(app_path('Http/Middleware/EnsureDirectoryExportAccess.php')),
            'the deploy notes' => file_get_contents(base_path('docs/deploy-notes-directory-redesign.md')),
        ];

        $pattern = '/\b([A-Z][A-Za-z0-9_]+)::([a-z][A-Za-z0-9_]*)\(\)/';

        // Prove the detector fires before trusting it to find nothing.
        $this->assertSame(
            1,
            preg_match($pattern, 'It is NOT true of MenuService::update(), which'),
            'the Class::method() detector no longer matches a known citation'
        );

        $classFiles = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path()));
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $classFiles[$file->getBasename('.php')] = $file->getPathname();
            }
        }

        foreach ($sources as $label => $source) {
            preg_match_all($pattern, $source, $hits, PREG_SET_ORDER);

            foreach ($hits as $hit) {
                [$citation, $class, $method] = $hit;

                if (in_array($class, self::FRAMEWORK_CLASSES, true)) {
                    continue;
                }

                $this->assertArrayHasKey(
                    $class,
                    $classFiles,
                    "{$label} cites {$citation}, but no {$class}.php exists under app/. Either fix the "
                    . 'citation, or add the class to FRAMEWORK_CLASSES if it belongs to a package (PR #317 F-012).'
                );

                $this->assertMatchesRegularExpression(
                    '/function\s+' . preg_quote($method, '/') . '\s*\(/',
                    file_get_contents($classFiles[$class]),
                    "{$label} cites {$citation}, but {$class} has no {$method}() method (PR #317 F-012)."
                );
            }
        }
    }

    /**
     * F-011: the grant runbook rests on one behavioural claim. Pin it to source.

     * Both documents tell an operator that granting `directory.export` is a DBA
     * database action and say why the screen route is the worse of the two. That
     * advice is only honest while MenuService keeps behaving the way they
     * describe: store() overwrites the posted permission_name with the slug, and
     * update() does not - it writes the posted value through verbatim, which is
     * how a dotted name reaches a menus row and then the roles screen at all.
     *
     * An earlier version of the same paragraph said the dotted name "can never
     * appear", which was store()'s behaviour mistaken for the module's. Three
     * reviews read the sentence and agreed with it, so the guard here is not a
     * closer reading - it is the assertion.
     *
     * WHEN L-9 IS FIXED, THIS TEST GOES RED, AND THAT IS ITS OTHER JOB. Making
     * update() assign the slug is the right change; it just has to arrive
     * together with a correction to both documents, because at that moment the
     * "second route" paragraph they carry stops being true.
     */
    public function test_the_documents_describe_menuservice_as_it_actually_behaves(): void
    {
        $service = file_get_contents(app_path('Services/SidebarMenu/MenuService.php'));

        $body = function (string $signature) use ($service): string {
            $start = strpos($service, $signature);
            $this->assertNotFalse($start, "MenuService no longer declares {$signature}");
            $rest = substr($service, $start + strlen($signature));
            $end = strpos($rest, 'public function ');

            return $end === false ? $rest : substr($rest, 0, $end);
        };

        $this->assertStringContainsString(
            "\$data['permission_name'] = \$permission",
            $body('public function store(array $data)'),
            'MenuService::store() no longer forces the slug, so the documents describing it are now wrong'
        );

        $this->assertStringNotContainsString(
            "\$data['permission_name'] =",
            $body('public function update($id, array $data)'),
            'MenuService::update() now assigns permission_name - L-9 is fixed. Correct the "second route" '
            . 'paragraph in EnsureDirectoryExportAccess and in docs/deploy-notes-directory-redesign.md '
            . 'section 0.1 in the same change, then delete this assertion (PR #317 F-011).'
        );

        foreach ([
            'the middleware' => file_get_contents(app_path('Http/Middleware/EnsureDirectoryExportAccess.php')),
            'the deploy notes' => file_get_contents(base_path('docs/deploy-notes-directory-redesign.md')),
        ] as $label => $source) {
            // The impossibility may still be QUOTED - explaining what an earlier
            // version got wrong is how these documents carry their own history.
            // It may not be asserted. Every occurrence must sit within reach of
            // a marker saying it is a former claim.
            foreach (['can never appear', 'never appears'] as $phrase) {
                $offset = 0;

                while (($at = strpos($source, $phrase, $offset)) !== false) {
                    $preceding = substr($source, max(0, $at - 400), min(400, $at));

                    $this->assertMatchesRegularExpression(
                        '/earlier version|used to|wrongly said/i',
                        $preceding,
                        "{$label} states \"{$phrase}\" as fact. MenuService::update() does not honour it, so "
                        . 'it may only appear marked as a claim an earlier version got wrong (PR #317 F-011).'
                    );

                    $offset = $at + strlen($phrase);
                }
            }
            $this->assertStringContainsString(
                'MenuService::update()',
                $source,
                "{$label} recommends the SQL grant without naming what makes the screen route possible"
            );
        }
    }

    /**
     * The note must wrap rather than clip.
     *
     * A5 is merged across the exported columns, and a merged cell cannot
     * overflow into its neighbours because they are inside the merge. At the
     * default column set the width carries the sentence, but a narrowed Columns
     * selection (2-4 columns) gives 36-73 character-widths against a
     * ~76-character note - so without wrapping the truncation warning is itself
     * truncated, which is the finding this note exists to answer, one layer down.
     */
    public function test_the_row_cap_note_wraps_instead_of_clipping(): void
    {
        $sheet = $this->writtenSheet('Showing the first 1,500 of 12,345 records — narrow the filters for the rest.');

        $this->assertTrue(
            $sheet->getStyle('A5')->getAlignment()->getWrapText(),
            'the merged note cell must wrap, or it is cut off at the merge width'
        );

        // NOT -1. "Size to content" is a request to the reader, and Excel does
        // not honour it for a merged cell: it keeps the default height and shows
        // the first line, which is the same sentence lost to a different edge.
        $height = (float) $sheet->getRowDimension(5)->getRowHeight();

        $this->assertGreaterThan(
            0.0,
            $height,
            'row 5 must carry an EXPLICIT height: Excel does not auto-fit a merged cell'
        );
    }

    /**
     * And the height must follow the note, not a constant.
     *
     * Two columns is the case that fails silently: the merged width falls to
     * around 23 character-widths against a ~76-character sentence, so the note
     * needs three or four lines where the full column set needs one.
     */
    public function test_a_narrowed_column_selection_gets_a_taller_note_row(): void
    {
        $note = 'Showing the first 1,500 of 12,345 records - narrow the filters for the rest.';

        $wide = $this->writtenSheet($note, 9);
        $narrow = $this->writtenSheet($note, 2);

        $this->assertGreaterThan(
            (float) $wide->getRowDimension(5)->getRowHeight(),
            (float) $narrow->getRowDimension(5)->getRowHeight(),
            'a selection too narrow to carry the note on one line must get a taller row, not a clipped one'
        );

        // And tall enough for every line, measured at the width the WRITTEN
        // file carries rather than recomputed from the production constants.
        foreach (['wide' => [$wide, 9], 'narrow' => [$narrow, 2]] as $label => [$sheet, $count]) {
            $width = 0.0;

            for ($i = 1; $i <= $count; $i++) {
                $column = $sheet->getColumnDimension(
                    \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i)
                )->getWidth();
                $width += $column > 0 ? $column : 10.0;
            }

            $lines = (int) ceil(mb_strlen($note) / max(8.0, $width - 2));

            $this->assertGreaterThanOrEqual(
                $lines * 13.0,
                (float) $sheet->getRowDimension(5)->getRowHeight(),
                "the {$label} sheet's note row is shorter than the text it has to show"
            );
        }
    }

    /** The note row must not shift the table: the headings stay on row 6. */
    public function test_the_note_does_not_move_the_data_table(): void
    {
        foreach ([null, 'Showing the first 1,500 of 12,345 records.'] as $note) {
            $sheet = $this->writtenSheet($note);

            $this->assertSame('Name', $sheet->getCell('A6')->getValue(),
                'the column headings must stay on row 6 whether or not a note is present');
            $this->assertSame('A Person', $sheet->getCell('A7')->getValue());
        }
    }

    /**
     * Every format branch must be handed the note.
     *
     * The defect was not that the .xlsx rendered the note badly - it was that
     * the value was destructured from the capping helper and then forwarded to
     * three of the four branches. Read mechanically from the method's source,
     * because "does this branch receive $note" is a property of the dispatch and
     * not of any one renderer.
     */
    public function test_every_format_branch_receives_the_row_cap_note(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Admin/DirectoryController.php'));

        $start = strpos($source, 'private function renderDirectoryExport(');
        $this->assertNotFalse($start, 'renderDirectoryExport should exist');

        // To the end of the method: it is the last thing in the CSV branch.
        $body = substr($source, $start, 3000);

        foreach ([
            'print' => "compact('columns', 'rows', 'title', 'filterLine', 'exportDate', 'note')",
            'xlsx' => '$filterLine, $title, $note)',
            'csv' => '$rows->count(), $note)',
        ] as $format => $needle) {
            $this->assertStringContainsString($needle, $body,
                "the {$format} branch must be handed the row-cap note - three of four was the defect");
        }

        // print and pdf share the compact() shape, so it must appear twice.
        $this->assertSame(
            2,
            substr_count($body, "compact('columns', 'rows', 'title', 'filterLine', 'exportDate', 'note')"),
            'both the print view and the PDF view must receive the note'
        );
    }
}
