<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\UserController;
use Illuminate\Contracts\Auth\Authenticatable;
use ReflectionClass;
use Tests\TestCase;

/**
 * Guards the shared status-toggle endpoint's allow-list.
 *
 * The endpoint used to take the table name, the column name and the value
 * straight off the request, which made it an arbitrary-write primitive for any
 * authenticated session. It now writes only pairs it recognises.
 *
 * An allow-list is only as good as its currency, so the first test here derives
 * the pairs the UI actually sends from the markup and fails if any is missing —
 * a new screen that forgets to register itself is caught in CI rather than by a
 * toggle that quietly does nothing.
 *
 * DB-free: the refusal happens before any query runs.
 */
class ToggleStatusAllowListTest extends TestCase
{
    /** @return array<string, array{id_column: string, columns: string[]}> */
    private function allowList(): array
    {
        $const = (new ReflectionClass(UserController::class))
            ->getConstant('TOGGLE_STATUS_ALLOWED');

        $this->assertIsArray($const, 'the endpoint must carry an allow-list');

        return $const;
    }

    /** @return string[] the status columns permitted for $table */
    private function columnsFor(string $table): array
    {
        return $this->allowList()[$table]['columns'] ?? [];
    }

    /**
     * Every table/column pair the shipped markup asks for must be permitted.
     *
     * Scans for the shared `.status-toggle` class and reads the data-table /
     * data-column attributes around it, which is exactly what custom.js posts.
     */
    public function test_the_allow_list_covers_every_toggle_in_the_markup(): void
    {
        $allowed = $this->allowList();
        $missing = [];

        foreach ($this->toggleMarkupPairs() as $triple => $where) {
            [$table, $column, $idColumn] = explode('|', $triple);

            if (! in_array($column, $allowed[$table]['columns'] ?? [], true)) {
                $missing[] = "{$table}.{$column} ({$where})";
                continue;
            }

            // The third parameter the UI sends. Reading only table and column is
            // how a screen keyed by something other than pk stayed green while
            // its toggle was writing WHERE pk = <that other key>.
            $expected = $allowed[$table]['id_column'];

            if ($idColumn !== $expected) {
                $missing[] = "{$table} is keyed by {$idColumn} in the markup but {$expected} in the allow-list ({$where})";
            }
        }

        $this->assertSame([], $missing, "status toggles the endpoint would refuse:\n".implode("\n", $missing));
    }

    /** @return array<string, string> "table|column|id_column" => the file it was found in */
    private function toggleMarkupPairs(): array
    {
        $roots = [app_path(), resource_path('views'), public_path('js'), public_path('admin_assets/js')];
        $pairs = [];

        foreach ($roots as $root) {
            if (! is_dir($root)) {
                continue;
            }

            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

            foreach ($files as $file) {
                if (! $file->isFile() || ! in_array($file->getExtension(), ['php', 'js'], true)) {
                    continue;
                }

                $source = file_get_contents($file->getPathname());

                if (! preg_match(self::SHARED_TOGGLE_CLASS, $source)) {
                    continue;
                }

                foreach ($this->pairsIn($source) as $pair) {
                    $pairs[$pair] = basename($file->getPathname());
                }
            }
        }

        $this->assertNotEmpty($pairs, 'the scan must find the toggles it is guarding');

        return $pairs;
    }

    /**
     * The SHARED toggle class, and only that one.
     *
     * A plain substring search for "status-toggle" also matches
     * `plain-status-toggle` and `sidebar-category-status-toggle`, which are
     * different controls with their own handlers posting to their own routes.
     * Matching those dragged three tables into the allow-list that this endpoint
     * never writes - including one, sidebar_menu_groups, that is not a table in
     * the database at all. Requiring a word boundary keeps the allow-list to
     * what custom.js actually posts here.
     */
    private const SHARED_TOGGLE_CLASS = '/(?<![-\w])status-toggle(?![-\w])/';

    /** @return string[] */
    private function pairsIn(string $source): array
    {
        $found = [];

        if (! preg_match_all(self::SHARED_TOGGLE_CLASS, $source, $m, PREG_OFFSET_CAPTURE)) {
            return $found;
        }

        foreach ($m[0] as $hit) {
            $at = $hit[1];
            // The attributes sit on the same element, so a window around the
            // class is enough and avoids pairing across unrelated elements.
            $window = substr($source, max(0, $at - 600), 1200);

            if (preg_match('/data-table=["\']([A-Za-z0-9_]+)["\']/', $window, $t)
                && preg_match('/data-column=["\']([A-Za-z0-9_]+)["\']/', $window, $c)) {
                // Absent means the handler will send the default, `pk`.
                $idColumn = preg_match('/data-id_column=["\']([A-Za-z0-9_]+)["\']/', $window, $k)
                    ? $k[1]
                    : 'pk';

                $found[] = $t[1].'|'.$c[1].'|'.$idColumn;
            }
        }

        return $found;
    }

    /** The allow-list must not have grown a table that is not a status switch. */
    public function test_the_allow_list_permits_only_status_columns(): void
    {
        // active_room is hostel_building_master's status column - an odd name for
        // one, but it is what the table has, and the alternative spelling does
        // not exist there at all.
        $permitted = ['active_inactive', 'status', 'is_active', 'visible', 'active_room'];

        foreach ($this->allowList() as $table => $rule) {
            $this->assertNotEmpty($rule['columns'], "{$table} must name at least one column");
            $this->assertNotEmpty($rule['id_column'], "{$table} must name its key column");

            foreach ($rule['columns'] as $column) {
                $this->assertContains(
                    $column,
                    $permitted,
                    "{$table}.{$column} is not a status column — this endpoint writes nothing else"
                );
            }
        }
    }

    public function test_identity_tables_are_not_toggleable(): void
    {
        $allowed = $this->allowList();

        foreach (['user_credentials', 'users', 'employee_master', 'student_master', 'roles', 'permissions'] as $table) {
            $this->assertArrayNotHasKey($table, $allowed, "{$table} must not be writable through the generic toggle");
        }
    }

    /**
     * The refusal, executed: a request naming a table off the list is rejected
     * before it reaches the database.
     *
     * @dataProvider forbiddenPayloads
     */
    public function test_the_endpoint_refuses_an_unlisted_write(array $payload, int $expected): void
    {
        $this->withoutExceptionHandling();
        $this->actingAs($this->toggleUser());

        try {
            $this->post(route('admin.toggleStatus'), $payload);
            $this->fail('the endpoint must refuse '.json_encode($payload));
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame($expected, $e->getStatusCode());
        }
    }

    public static function forbiddenPayloads(): array
    {
        return [
            'another table entirely' => [
                ['table' => 'user_credentials', 'column' => 'user_name', 'id' => 1, 'status' => 1], 403,
            ],
            'a listed table, an unlisted column' => [
                ['table' => 'department_master', 'column' => 'department_name', 'id' => 1, 'status' => 1], 403,
            ],
            'no table at all' => [
                ['column' => 'active_inactive', 'id' => 1, 'status' => 1], 403,
            ],
            'a non-numeric row id' => [
                ['table' => 'department_master', 'column' => 'active_inactive', 'id' => '1 OR 1=1', 'status' => 1], 422,
            ],
            'a status outside 0/1' => [
                ['table' => 'department_master', 'column' => 'active_inactive', 'id' => 1, 'status' => 9], 422,
            ],
        ];
    }

    /** An authenticated actor with no database behind it. */
    private function toggleUser(): Authenticatable
    {
        return new class implements Authenticatable
        {
            /** user_credentials.user_name — the auth middleware logs it when app.debug is on. */
            public $user_name = 'toggle-guard-stub';

            public function hasRole($role): bool
            {
                return false;
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
}
