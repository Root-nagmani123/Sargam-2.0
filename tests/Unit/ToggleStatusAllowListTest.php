<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\UserController;
use PHPUnit\Framework\TestCase;
use ReflectionClassConstant;

/**
 * The allow-list that closes the generic toggle-status write primitive.
 *
 * `UserController::toggleStatus()` takes the table, column and key column from
 * the request body. Before the allow-list, any authenticated session could
 * write any column of any table (accepted-risk record SAST-2026-08-21-01).
 *
 * Two properties matter, and they pull against each other:
 *
 *   1. the list must be *narrow* — only status columns, never a credential or
 *      an identity column;
 *   2. the list must be *complete* — every screen that posts to the endpoint
 *      today must still work, or the fix breaks 43 admin listings.
 *
 * Property 2 is the one a human cannot hold in their head, so it is asserted
 * against the markup itself rather than against a copy of the list: the test
 * scans the views and the DataTable classes for switches wired to the global
 * `.status-toggle` handler and fails if any of them is not allow-listed. Add a
 * status switch to a new screen and this test tells you to add its row.
 *
 * No database and no application boot — this runs anywhere.
 */
class ToggleStatusAllowListTest extends TestCase
{
    /** Directories that can contain a status switch. */
    private const SCAN_ROOTS = ['resources/views', 'app', 'public'];

    /**
     * @return array<string, array{id_column: string, columns: array<int, string>}>
     */
    private function allowList(): array
    {
        return (new ReflectionClassConstant(UserController::class, 'TOGGLE_STATUS_ALLOWED'))->getValue();
    }

    private function repoRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    public function test_the_allow_list_is_populated_and_well_shaped(): void
    {
        $allowList = $this->allowList();

        $this->assertNotEmpty($allowList, 'An empty allow-list would refuse every status toggle in the product.');

        foreach ($allowList as $table => $rule) {
            $this->assertIsString($table);
            $this->assertMatchesRegularExpression(
                '/^[a-z0-9_]+$/',
                $table,
                sprintf('%s is not a plain table name.', var_export($table, true))
            );

            $this->assertArrayHasKey('id_column', $rule, $table . ' has no id_column.');
            $this->assertArrayHasKey('columns', $rule, $table . ' has no columns.');
            $this->assertNotEmpty($rule['columns'], $table . ' allows no column, so its switch cannot work.');

            foreach ($rule['columns'] as $column) {
                $this->assertMatchesRegularExpression(
                    '/^[a-z0-9_]+$/i',
                    $column,
                    sprintf('%s.%s is not a plain column name.', $table, $column)
                );
            }
        }
    }

    /**
     * The narrowness half. A status endpoint has no business writing anything
     * but a status column, so the allowed column names are themselves fenced —
     * this is what stops the list quietly regrowing into the original defect.
     */
    public function test_the_allow_list_permits_only_status_columns(): void
    {
        // active_room is the odd one out: hostel_building_master stores its
        // status in a varchar(1) under that name. It earns its place because
        // it IS that table's status column, not because the list is elastic.
        $permitted = ['active_inactive', 'status', 'is_active', 'visible', 'active_room'];

        foreach ($this->allowList() as $table => $rule) {
            foreach ($rule['columns'] as $column) {
                $this->assertContains(
                    $column,
                    $permitted,
                    sprintf(
                        '%s.%s is not a status column. The toggle endpoint must never be able to write it; '
                        . 'give that screen its own route instead of widening this list.',
                        $table,
                        $column
                    )
                );
            }
        }
    }

    /**
     * The completeness half: every switch wired to the global handler is listed.
     *
     * Only elements that actually carry `data-table` are considered — a JS
     * selector mentioning the class, or a screen that posts to its own
     * `data-url` route, never reaches this endpoint.
     */
    public function test_every_screen_wired_to_the_generic_handler_is_allow_listed(): void
    {
        $allowList = $this->allowList();
        $missing = [];

        foreach ($this->scanForGenericSwitches() as $switch) {
            [$table, $column, $idColumn, $file] = $switch;

            $rule = $allowList[$table] ?? null;

            if ($rule === null) {
                $missing[] = sprintf('%s (%s.%s) — table not allow-listed', $file, $table, $column);
                continue;
            }

            if (! in_array($column, $rule['columns'], true)) {
                $missing[] = sprintf('%s — %s.%s is not among the allowed columns', $file, $table, $column);
            }

            if ($idColumn !== $rule['id_column']) {
                $missing[] = sprintf(
                    '%s — %s uses key column "%s" but the list says "%s"',
                    $file,
                    $table,
                    $idColumn,
                    $rule['id_column']
                );
            }
        }

        $this->assertSame(
            [],
            $missing,
            "These status switches post to /admin/toggle-status but the allow-list refuses them, "
            . "so their screens are broken:\n  " . implode("\n  ", $missing)
        );
    }

    /**
     * Every status switch bound to the global `.status-toggle` handler that
     * supplies a literal table and column.
     *
     * @return array<int, array{0: string, 1: string, 2: string, 3: string}>
     */
    private function scanForGenericSwitches(): array
    {
        $found = [];

        foreach (self::SCAN_ROOTS as $root) {
            $base = $this->repoRoot() . DIRECTORY_SEPARATOR . $root;

            if (! is_dir($base)) {
                continue;
            }

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($files as $file) {
                if (! in_array($file->getExtension(), ['php', 'js'], true)) {
                    continue;
                }

                $source = @file_get_contents($file->getPathname());

                if ($source === false || strpos($source, 'status-toggle') === false) {
                    continue;
                }

                // Several screens build the switch inside a JS template string,
                // where the attribute quotes arrive escaped. Unescape first so a
                // single attribute pattern covers both spellings.
                $source = str_replace(['\\"', "\\'"], ['"', "'"], $source);

                $relative = str_replace($this->repoRoot() . DIRECTORY_SEPARATOR, '', $file->getPathname());

                foreach ($this->switchesIn($source, $relative) as $switch) {
                    $found[] = $switch;
                }
            }
        }

        return $found;
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: string, 3: string}>
     */
    private function switchesIn(string $source, string $relative): array
    {
        $out = [];

        // `status-toggle` as a whole class token: not `member-status-toggle`,
        // not `status-toggle-data`, both of which are separate handlers with
        // their own endpoints.
        if (! preg_match_all('/(?<![-\w])status-toggle(?![-\w])/', $source, $hits, PREG_OFFSET_CAPTURE)) {
            return $out;
        }

        foreach ($hits[0] as $hit) {
            $offset = $hit[1];
            $start = max(0, $offset - 2000);
            $window = substr($source, $start, 4000);

            if (! preg_match('/data-table\s*=\s*["\']([^"\']+)["\']/', $window, $t)) {
                continue;
            }

            if (! preg_match('/data-column\s*=\s*["\']([^"\']+)["\']/', $window, $c)) {
                continue;
            }

            $table = $t[1];
            $column = $c[1];

            // Blade or JS interpolation — the value is not knowable statically.
            if (strpbrk($table . $column, '{$') !== false) {
                continue;
            }

            $idColumn = preg_match('/data-id_column\s*=\s*["\']([^"\']+)["\']/', $window, $i)
                ? $i[1]
                : 'pk';

            $out[] = [$table, $column, $idColumn, $relative];
        }

        return $out;
    }
}
