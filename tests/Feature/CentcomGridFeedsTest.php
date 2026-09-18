<?php

namespace Tests\Feature;

use App\Models\IssueCategoryMaster;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Cover for the CENTCOM grids after the server-side DataTables conversion.
 *
 * These are deliberately READ-ONLY and deliberately NOT RefreshDatabase:
 * phpunit.xml leaves DB_CONNECTION commented out, so the suite runs against the
 * real configured database. A RefreshDatabase test here would drop the
 * development schema. DatabaseTransactions is belt-and-braces only — nothing
 * below writes.
 *
 * They assert the properties the conversion is supposed to guarantee, so a
 * regression shows up as a failing test rather than as a slow page:
 *   - the feed answers the DataTables contract (draw / recordsTotal /
 *     recordsFiltered / data)
 *   - paging is genuinely server-side: a draw returns at most `length` rows
 *     however large the table is
 *   - recordsTotal is the count BEFORE the search term (regressed once already)
 *   - ordering is resolved on the server
 *   - values are escaped
 *   - the endpoints are behind auth
 */
class CentcomGridFeedsTest extends TestCase
{
    use DatabaseTransactions;

    /** Every converted grid: [route name, DataTables column names]. */
    private const GRIDS = [
        'admin.issue-categories.data' => ['', 'category', 'description', 'sub_categories', 'status', ''],
        'admin.issue-sub-categories.data' => ['', 'category', 'sub_category', 'status', ''],
        'admin.issue-priorities.data' => ['', 'priority', 'description', 'status', ''],
        'admin.issue-escalation-matrix.data' => ['', 'category', 'level1', 'level2', 'level3', ''],
        'admin.issue-management.data' => ['id', 'date', 'category', 'description', 'complainant', 'nodal', 'priority', 'status', 'action'],
    ];

    private function actor(): User
    {
        $user = User::query()->first();

        if (! $user) {
            $this->markTestSkipped('No user_credentials row to authenticate as.');
        }

        return $user;
    }

    /**
     * DataTables sends nested arrays (columns[0][name], search[value]).
     * route() cannot express those, so build the query string explicitly —
     * passing the array to route() silently drops it and the endpoint then sees
     * no search, no order and no paging at all.
     *
     * @param  array<string, mixed>  $query
     */
    private function url(string $routeName, array $query): string
    {
        return route($routeName) . '?' . http_build_query($query);
    }

    /**
     * @param  array<int, string>  $columns
     * @return array<string, mixed>
     */
    private function query(array $columns, string $search = '', int $start = 0, int $length = 10, int $orderColumn = 1, string $dir = 'asc'): array
    {
        $cols = [];
        foreach ($columns as $i => $name) {
            $cols[$i] = ['data' => $name, 'name' => $name, 'searchable' => 'true', 'orderable' => 'true'];
        }

        return [
            'draw' => 1,
            'start' => $start,
            'length' => $length,
            'search' => ['value' => $search, 'regex' => 'false'],
            'columns' => $cols,
            'order' => [['column' => $orderColumn, 'dir' => $dir]],
        ];
    }

    public function test_every_grid_feed_answers_the_datatables_contract(): void
    {
        foreach (self::GRIDS as $routeName => $columns) {
            $response = $this->actingAs($this->actor())
                ->getJson($this->url($routeName, $this->query($columns)));

            $response->assertOk();
            $payload = $response->json();

            $this->assertIsArray($payload, "{$routeName} did not return a JSON object");
            foreach (['draw', 'recordsTotal', 'recordsFiltered', 'data'] as $key) {
                $this->assertArrayHasKey($key, $payload, "{$routeName} is missing '{$key}'");
            }
            $this->assertIsArray($payload['data'], "{$routeName}: 'data' must be a list");
            $this->assertIsInt($payload['recordsTotal'], "{$routeName}: recordsTotal must be an int");
            $this->assertIsInt($payload['recordsFiltered'], "{$routeName}: recordsFiltered must be an int");
        }
    }

    public function test_paging_is_server_side_so_a_draw_never_returns_the_whole_table(): void
    {
        foreach (self::GRIDS as $routeName => $columns) {
            $response = $this->actingAs($this->actor())
                ->getJson($this->url($routeName, $this->query($columns, '', 0, 10)));

            $response->assertOk();
            $payload = $response->json();

            $this->assertLessThanOrEqual(
                10,
                count($payload['data']),
                "{$routeName} returned more rows than the requested page length — paging is not server-side"
            );

            // And when the table is bigger than a page, the response must say so
            // rather than quietly shipping everything.
            if ($payload['recordsFiltered'] > 10) {
                $this->assertCount(10, $payload['data'], "{$routeName} short-changed a full page");
            }
        }
    }

    public function test_records_total_is_the_count_before_the_search_term(): void
    {
        // The categories grid is small and stable enough to assert against, and
        // it is where the contract regressed: recordsTotal used to be a copy of
        // recordsFiltered, so DataTables could never say "filtered from N".
        $columns = self::GRIDS['admin.issue-categories.data'];

        $unfiltered = $this->actingAs($this->actor())
            ->getJson($this->url('admin.issue-categories.data', $this->query($columns)))
            ->assertOk()
            ->json();

        if ($unfiltered['recordsTotal'] < 2) {
            $this->markTestSkipped('Needs at least two categories to prove filtering narrows the set.');
        }

        $name = IssueCategoryMaster::query()->value('issue_category');
        if (! $name) {
            $this->markTestSkipped('No category name to search for.');
        }

        $filtered = $this->actingAs($this->actor())
            ->getJson($this->url('admin.issue-categories.data', $this->query($columns, (string) $name)))
            ->assertOk()
            ->json();

        $this->assertSame(
            $unfiltered['recordsTotal'],
            $filtered['recordsTotal'],
            'recordsTotal must not change when a search term is applied'
        );
        $this->assertLessThan(
            $filtered['recordsTotal'],
            $filtered['recordsFiltered'],
            'searching for one category name should narrow recordsFiltered below the total'
        );
    }

    public function test_ordering_is_resolved_on_the_server(): void
    {
        $columns = self::GRIDS['admin.issue-categories.data'];

        $asc = $this->actingAs($this->actor())
            ->getJson($this->url('admin.issue-categories.data', $this->query($columns, '', 0, 10, 1, 'asc')))
            ->assertOk()->json('data');

        $desc = $this->actingAs($this->actor())
            ->getJson($this->url('admin.issue-categories.data', $this->query($columns, '', 0, 10, 1, 'desc')))
            ->assertOk()->json('data');

        if (count($asc) < 2) {
            $this->markTestSkipped('Needs at least two categories to observe an ordering flip.');
        }

        $this->assertNotSame(
            $asc[0]['category'],
            $desc[0]['category'],
            'asc and desc returned the same first row — the sort is not reaching the query'
        );
    }

    public function test_feed_values_are_escaped(): void
    {
        // Find real data that would betray a missing e(): a category whose name
        // contains a character HTML must encode.
        $risky = IssueCategoryMaster::query()
            ->where(function ($q) {
                $q->where('issue_category', 'like', '%&%')
                    ->orWhere('issue_category', 'like', '%<%');
            })
            ->value('issue_category');

        if (! $risky) {
            $this->markTestSkipped('No category name containing & or < to test escaping against.');
        }

        $raw = $this->actingAs($this->actor())
            ->getJson($this->url('admin.issue-categories.data', $this->query(self::GRIDS['admin.issue-categories.data'], (string) $risky)))
            ->assertOk()
            ->getContent();

        $decoded = json_decode($raw, true);
        $names = array_column($decoded['data'], 'category');

        $this->assertNotEmpty($names, 'the search returned no rows, so escaping was not exercised');
        foreach ($names as $name) {
            $this->assertStringNotContainsString('<', $name, 'a raw "<" reached the grid payload unescaped');
            if (str_contains($risky, '&')) {
                $this->assertStringNotContainsString(
                    ' & ',
                    $name,
                    'an unencoded "&" reached the grid payload — e() is not being applied'
                );
            }
        }
    }

    public function test_grid_feeds_require_authentication(): void
    {
        foreach (array_keys(self::GRIDS) as $routeName) {
            $this->get(route($routeName))
                ->assertRedirect(route('login'));
        }
    }
}
