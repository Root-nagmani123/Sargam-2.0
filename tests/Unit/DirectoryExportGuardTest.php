<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\DirectoryController;
use App\Http\Middleware\EnsureDirectoryExportAccess;
use App\Support\ExportCell;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        [$pk, $name] = $this->invokePrivate('resolveOptionFilter', [
            $this->request(['section' => '0']), 'section', collect([0 => 'NIAR', 5 => 'Estate']),
        ]);

        $this->assertSame(0, $pk, 'pk 0 must reach the query as a filter');
        $this->assertSame('NIAR', $name, 'and the header band must name it');
    }

    /** @dataProvider absentFilterInputs */
    public function test_no_filter_resolves_to_null($raw): void
    {
        [$pk, $name] = $this->invokePrivate('resolveOptionFilter', [
            $this->request($raw === null ? [] : ['section' => $raw]), 'section', collect([0 => 'NIAR', 5 => 'Estate']),
        ]);

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
            'pk no option offers' => ['999'],
        ];
    }

    /**
     * A label that came back null must not turn a valid pk into "no filter" —
     * the reason the lookup is has(), not `?? null`.
     */
    public function test_a_null_label_still_filters(): void
    {
        [$pk, $name] = $this->invokePrivate('resolveOptionFilter', [
            $this->request(['section' => '7']), 'section', collect([7 => null]),
        ]);

        $this->assertSame(7, $pk);
        $this->assertSame('', $name);
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
}
