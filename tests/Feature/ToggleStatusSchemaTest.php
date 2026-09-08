<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\DB;
use ReflectionClassConstant;
use Tests\TestCase;

/**
 * The allow-list must agree with the database, not just with the markup.
 *
 * ToggleStatusAllowListTest proves the list is COMPLETE - every screen wired to
 * the generic handler is covered. It cannot prove any entry is CORRECT, because
 * it only ever compares the list against the same markup the list was built
 * from. If a screen posts a column that does not exist, both agree and both are
 * wrong.
 *
 * That is not hypothetical. Two screens shipped in exactly that state and their
 * status toggle had never worked once:
 *
 *   - stream_master posted `status`; the column is `active_inactive`
 *   - hostel_building_master posted `active_inactive`; the column is `active_room`
 *
 * Every toggle on those screens issued an UPDATE against a column that is not
 * there, got an "Unknown column" error, and surfaced as a generic failure
 * message. Nobody noticed, because nothing compared the two sources of truth.
 * This test is that comparison.
 *
 * Needs a database. Tables absent from the connection under test are reported
 * and skipped rather than failed - a developer database legitimately does not
 * carry every production table, and failing on that would train people to
 * ignore this test.
 */
class ToggleStatusSchemaTest extends TestCase
{
    /**
     * @return array<string, array{id_column: string, columns: array<int, string>}>
     */
    private function allowList(): array
    {
        return (new ReflectionClassConstant(UserController::class, 'TOGGLE_STATUS_ALLOWED'))->getValue();
    }

    /** @return array<int, string>|null column rows, or null when the table is absent */
    private function columnsOf(string $table): ?array
    {
        try {
            $rows = DB::select("SHOW COLUMNS FROM `{$table}`");
        } catch (\Throwable $e) {
            return null;
        }

        $out = [];
        foreach ($rows as $row) {
            $out[$row->Field] = strtolower($row->Type);
        }

        return $out;
    }

    public function test_every_allow_listed_status_column_exists_in_the_database(): void
    {
        $wrong = [];
        $absent = [];

        foreach ($this->allowList() as $table => $rule) {
            $columns = $this->columnsOf($table);

            if ($columns === null) {
                $absent[] = $table;
                continue;
            }

            foreach ($rule['columns'] as $column) {
                if (! array_key_exists($column, $columns)) {
                    $near = implode(', ', array_slice(preg_grep('/activ|status|visib|is_|flag/i', array_keys($columns)), 0, 4));

                    $wrong[] = sprintf(
                        '%s.%s does not exist%s',
                        $table,
                        $column,
                        $near !== '' ? ' (status-like columns present: ' . $near . ')' : ''
                    );
                }
            }
        }

        if ($absent) {
            // Reported, not failed - see the class docblock.
            fwrite(STDERR, PHP_EOL . '  [toggle-status schema] not present on this connection, unverified: '
                . implode(', ', $absent) . PHP_EOL);
        }

        $this->assertSame(
            [],
            $wrong,
            "These allow-listed status columns do not exist, so those screens' toggles "
            . "cannot work:\n  " . implode("\n  ", $wrong)
        );
    }

    public function test_every_allow_listed_key_column_exists_and_is_numeric(): void
    {
        // toggleStatus() rejects a non-numeric id, so a table keyed by anything
        // else would have its toggle refused. Every key in the list is numeric
        // today; this keeps it that way.
        $wrong = [];

        foreach ($this->allowList() as $table => $rule) {
            $columns = $this->columnsOf($table);

            if ($columns === null) {
                continue;
            }

            $key = $rule['id_column'];

            if (! array_key_exists($key, $columns)) {
                $wrong[] = sprintf('%s.%s (key column) does not exist', $table, $key);
                continue;
            }

            if (preg_match('/^(big|small|medium|tiny)?int|^decimal|^numeric/', $columns[$key]) !== 1) {
                $wrong[] = sprintf(
                    '%s.%s is %s - toggleStatus() requires a numeric id and would refuse this table',
                    $table,
                    $key,
                    $columns[$key]
                );
            }
        }

        $this->assertSame([], $wrong, "Key-column problems:\n  " . implode("\n  ", $wrong));
    }
}
