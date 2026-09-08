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
    /** @return array<string, string[]> */
    private function allowList(): array
    {
        $const = (new ReflectionClass(UserController::class))
            ->getConstant('TOGGLEABLE_STATUS_COLUMNS');

        $this->assertIsArray($const, 'the endpoint must carry an allow-list');

        return $const;
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

        foreach ($this->toggleMarkupPairs() as $pair => $where) {
            [$table, $column] = explode('|', $pair);

            if (! in_array($column, $allowed[$table] ?? [], true)) {
                $missing[] = "{$table}.{$column} ({$where})";
            }
        }

        $this->assertSame([], $missing, "status toggles the endpoint would refuse:\n".implode("\n", $missing));
    }

    /** @return array<string, string> "table|column" => the file it was found in */
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

                if (! str_contains($source, 'status-toggle')) {
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

    /** @return string[] */
    private function pairsIn(string $source): array
    {
        $found = [];
        $offset = 0;

        while (($at = strpos($source, 'status-toggle', $offset)) !== false) {
            $offset = $at + 1;
            // The attributes sit on the same element, so a window around the
            // class is enough and avoids pairing across unrelated elements.
            $window = substr($source, max(0, $at - 600), 1200);

            if (preg_match('/data-table=["\']([A-Za-z0-9_]+)["\']/', $window, $t)
                && preg_match('/data-column=["\']([A-Za-z0-9_]+)["\']/', $window, $c)) {
                $found[] = $t[1].'|'.$c[1];
            }
        }

        return $found;
    }

    /** The allow-list must not have grown a table that is not a status switch. */
    public function test_the_allow_list_permits_only_status_columns(): void
    {
        $permitted = ['active_inactive', 'status', 'is_active', 'visible'];

        foreach ($this->allowList() as $table => $columns) {
            $this->assertNotEmpty($columns, "{$table} must name at least one column");

            foreach ($columns as $column) {
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
