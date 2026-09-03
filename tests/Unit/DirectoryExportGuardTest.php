<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\DirectoryController;
use App\Support\ExportCell;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use ReflectionClass;
use Tests\TestCase;

/**
 * Guards for the directory export layer (PR #317).
 *
 * Deliberately DB-free: every assertion drives a pure resolver through
 * reflection, or the route table, so the suite's shared-database constraint
 * (see phpunit.xml) never applies.
 */
class DirectoryExportGuardTest extends TestCase
{
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

    // ── F-001: formula injection ──────────────────────────────────────────

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

    // ── F-002: dompdf must not be a PHP execution context ─────────────────

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

    // ── F-003: the downloads are gated, the grids are not ─────────────────

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

    // ── F-006: array parameters must not become the string "Array" ────────

    public function test_array_cols_parameter_does_not_break_resolution(): void
    {
        $resolved = $this->invokePrivate('resolveDirectoryExportColumns', [
            $this->request(['cols' => ['name', 'email']]), $this->defs(),
        ]);

        $this->assertSame(array_keys($this->defs()), array_keys($resolved));
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

    // ── F-007: the row cap covers every format ────────────────────────────

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
}
