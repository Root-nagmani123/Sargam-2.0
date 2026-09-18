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
        $pairs = [];

        foreach ($this->sourcesCarryingTheSharedClass() as $relativePath => $source) {
            foreach ($this->pairsIn($source) as $pair) {
                $pairs[$pair] = basename($relativePath);
            }
        }

        $this->assertNotEmpty($pairs, 'the scan must find the toggles it is guarding');

        return $pairs;
    }

    /**
     * Every scanned file that mentions the shared class, keyed by its path
     * relative to the repository root.
     *
     * @return array<string, string> relative path => file contents
     */
    private function sourcesCarryingTheSharedClass(): array
    {
        $roots = [app_path(), resource_path('views'), public_path('js'), public_path('admin_assets/js')];
        $sources = [];

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

                $sources[$this->relativePath($file->getPathname())] = $source;
            }
        }

        return $sources;
    }

    /** Repository-relative, forward slashes, so the expectations below are readable and stable. */
    private function relativePath(string $absolute): string
    {
        $normalise = fn (string $path) => str_replace(chr(92), '/', $path);

        return str_replace($normalise(base_path()).'/', '', $normalise($absolute));
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

            if (preg_match('/data-table=\\\\?["\']([A-Za-z0-9_]+)\\\\?["\']/', $window, $t)
                && preg_match('/data-column=\\\\?["\']([A-Za-z0-9_]+)\\\\?["\']/', $window, $c)) {
                // Absent means the handler will send the default, `pk`.
                $idColumn = preg_match('/data-id_column=\\\\?["\']([A-Za-z0-9_]+)\\\\?["\']/', $window, $k)
                    ? $k[1]
                    : 'pk';

                $found[] = $t[1].'|'.$c[1].'|'.$idColumn;
            }
        }

        return $found;
    }

    /**
     * Controls that carry the shared class but deliberately register no
     * (table, column) pair with this endpoint.
     *
     * Each one is named with its reason. A blanket "skip what you cannot read"
     * is what the check below exists to remove; an exception that nobody wrote
     * down is the same hole with a nicer name.
     *
     * @var array<string, string> repository-relative path => why it is exempt
     */
    private const NOT_REGISTERED_BY_DESIGN = [
        'resources/views/admin/security/vehicle_pass_config/index.blade.php' =>
            'carries data-url and its own in-page handler, and posts to '
            .'admin.security.vehicle_pass_config.toggle.status, not to this endpoint',
        'resources/views/admin/security/vehicle_type/index.blade.php' =>
            'carries data-url and its own in-page handler, and posts to '
            .'admin.security.vehicle_type.toggle.status, not to this endpoint',
    ];

    /**
     * Nothing carrying the shared class may be invisible to the pair scan.
     *
     * The guard above is worth exactly one claim - "the allow-list cannot
     * silently fall behind the UI" - and that claim only holds for toggles
     * whose data-table and data-column are literal strings. A screen written as
     * data-table="{{ $table }}", or with the attributes further from the class
     * than the scan's window, or assembled in JS, used to be SKIPPED: the test
     * stayed green, the allow-list never gained the row, and the switch became a
     * dead button answering "That table cannot be toggled from here."
     *
     * So an element the scan cannot read is reported here instead of ignored.
     * The only way past it is to name the control and say why.
     */
    public function test_no_shared_toggle_element_is_skipped_by_the_scan(): void
    {
        $unreadable = [];
        $elements = 0;

        foreach ($this->sourcesCarryingTheSharedClass() as $relativePath => $source) {
            foreach ($this->sharedToggleTags($source) as $tag) {
                $elements++;

                if ($this->registersALiteralPair($tag)) {
                    continue;
                }

                if (array_key_exists($relativePath, self::NOT_REGISTERED_BY_DESIGN)) {
                    continue;
                }

                $unreadable[] = $relativePath.'  ->  '.$this->summarise($tag);
            }
        }

        // If this ever reaches zero the scan has stopped finding elements at all,
        // which would make an empty $unreadable meaningless.
        $this->assertGreaterThan(30, $elements, 'the element scan must find the toggles it is guarding');

        $this->assertSame(
            [],
            $unreadable,
            "shared-class toggles the allow-list scan cannot read - register them, or add them to "
            ."NOT_REGISTERED_BY_DESIGN with the reason:
".implode("
", $unreadable)
        );
    }

    /** Every named exception must still exist, or the list is quietly excusing nothing. */
    public function test_each_named_exception_still_carries_the_shared_class(): void
    {
        $scanned = $this->sourcesCarryingTheSharedClass();

        foreach (self::NOT_REGISTERED_BY_DESIGN as $path => $reason) {
            $this->assertArrayHasKey(
                $path,
                $scanned,
                "{$path} is excused from the toggle scan but no longer carries the shared class - drop the exception"
            );
            $this->assertNotEmpty($reason, "{$path} must say why it is exempt");
        }
    }

    /**
     * Does this element name its table and column as literal strings?
     *
     * The optional backslash is not decoration: five of these screens insert
     * new rows from a JS template literal, where the same markup is written
     * class=\"...status-toggle\". Those toggles are as real as the server-rendered
     * ones and register the same pair; reading only unescaped quotes reported
     * all five as unreadable when they are merely quoted differently.
     */
    private function registersALiteralPair(string $tag): bool
    {
        return (bool) preg_match('/data-table=\\\\?["\']([A-Za-z0-9_]+)\\\\?["\']/', $tag)
            && (bool) preg_match('/data-column=\\\\?["\']([A-Za-z0-9_]+)\\\\?["\']/', $tag);
    }

    /**
     * The check above is only worth what its parser is worth, so the parser is
     * exercised directly - once on each shape it must accept, and once on the
     * shape it must reject.
     *
     * @dataProvider elementShapes
     */
    public function test_the_scan_can_tell_a_literal_pair_from_one_it_cannot_read(string $markup, bool $readable, string $why): void
    {
        $tags = $this->sharedToggleTags($markup);

        $this->assertCount(1, $tags, "the tag scan must find exactly one element: {$why}");
        $this->assertSame($readable, $this->registersALiteralPair($tags[0]), $why);
    }

    /** @return array<string, array{0: string, 1: bool, 2: string}> */
    public static function elementShapes(): array
    {
        return [
            'a plain server-rendered toggle' => [
                '<input class="form-check-input status-toggle" type="checkbox" '
                .'data-table="department_master" data-column="active_inactive" data-id="7">',
                true,
                'the ordinary shape must still be read',
            ],
            'a row inserted from a JS template literal' => [
                '<input class=\\"form-check-input status-toggle\\" type=\\"checkbox\\" '
                .'data-table=\\"department_master\\" data-column=\\"active_inactive\\" data-id=\\"7\\">',
                true,
                'escaped quotes are a quoting difference, not an unreadable toggle',
            ],
            'an attribute value containing a Blade arrow' => [
                '<input class="form-check-input status-toggle" type="checkbox" '
                .'data-table="venue_master" data-column="active_inactive" '
                .'data-id_column="{{ $venue->venue_id }}" data-id="{{ $venue->venue_id }}">',
                true,
                'a > inside an attribute value must not truncate the tag and lose its attributes',
            ],
            'a table name supplied by a Blade expression' => [
                '<input class="form-check-input status-toggle" type="checkbox" '
                .'data-table="{{ $table }}" data-column="active_inactive" data-id="7">',
                false,
                'THIS is the case that used to be skipped silently - it must now be reported',
            ],
        ];
    }

    /**
     * The HTML tags in $source that carry the shared class.
     *
     * Quote-aware on purpose. A naive /<input[^>]*>/ stops at the first `>`, and
     * in this codebase that `>` is often INSIDE an attribute value - Blade
     * expressions such as data-id_column="{{ $venue->venue_id }}" contain one.
     * A tag truncated there loses exactly the attributes being looked for, and
     * the element is then reported as unreadable when it is merely mis-parsed.
     *
     * Matching tags rather than raw occurrences is also what keeps handler code
     * out of the result: $('.status-toggle') in a script is a reference to these
     * controls, not one of them.
     *
     * @return string[]
     */
    private function sharedToggleTags(string $source): array
    {
        $pattern = "/<[a-zA-Z][^>\"']*(?:(?:\"[^\"]*\"|'[^']*')[^>\"']*)*>/s";

        if (! preg_match_all($pattern, $source, $matches)) {
            return [];
        }

        return array_values(array_filter(
            $matches[0],
            fn (string $tag) => (bool) preg_match(self::SHARED_TOGGLE_CLASS, $tag)
        ));
    }

    /** A tag short enough to read in a failure message. */
    private function summarise(string $tag): string
    {
        $flat = trim(preg_replace('/\s+/', ' ', $tag));

        return strlen($flat) > 160 ? substr($flat, 0, 157).'...' : $flat;
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
