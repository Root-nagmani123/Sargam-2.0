<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\DataTableSearchHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Mess grid Download/Print fetch every filtered row (PR #347 F-002 / F-008).
 *
 * The grids cap a normal page at 100 rows (10 when the request asks for more). The
 * Download/Print fetch sends `for_print=1`, which must lift that cap — otherwise the
 * CSV or printout holds 10 rows while looking complete. Covers both length patterns in
 * use: skip/take (Item Subcategories) and offset/limit (Store Allocations).
 *
 * Rows are seeded inside a transaction under a unique token and every request searches
 * for that token, so real data neither affects nor is affected by the test.
 */
class MessDataTableExportLengthTest extends TestCase
{
    use DatabaseTransactions;

    private const ROWS = 12;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->token = 'ZXEXP'.strtoupper(substr(md5(uniqid('', true)), 0, 10));
        $now = now();

        $subStoreId = DB::table('mess_sub_stores')->insertGetId(['sub_store_name' => $this->token.' Store', 'created_at' => $now, 'updated_at' => $now]);
        $allocationId = DB::table('mess_store_allocations')->insertGetId(['sub_store_id' => $subStoreId, 'allocation_date' => $now->toDateString(), 'created_at' => $now, 'updated_at' => $now]);
        for ($i = 1; $i <= self::ROWS; $i++) {
            $itemId = DB::table('mess_item_subcategories')->insertGetId([
                'name' => $this->token.' Item '.$i,
                'item_code' => $this->token.$i,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('mess_store_allocation_items')->insert([
                'store_allocation_id' => $allocationId,
                'item_subcategory_id' => $itemId,
                'quantity' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->actingAs(User::query()->firstOrFail());
        $this->withSession(['user_roles' => ['Mess Admin']]);
    }

    /** @return array<string, array{string}> */
    public static function grids(): array
    {
        return [
            'item subcategories (skip/take)' => ['/admin/mess/itemsubcategories'],
            'store allocations (offset/limit)' => ['/admin/mess/storeallocations'],
        ];
    }

    private function fetch(string $url, array $params): array
    {
        $query = http_build_query(array_merge(['draw' => 1, 'start' => 0, 'search' => ['value' => $this->token]], $params));

        return $this->get($url.'?'.$query, ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->assertOk()
            ->json();
    }

    /** @dataProvider grids */
    public function test_normal_request_stays_paginated(string $url): void
    {
        $json = $this->fetch($url, ['length' => 500]);

        $this->assertSame(self::ROWS, $json['recordsFiltered']);
        $this->assertCount(10, $json['data'], 'an over-long normal request still falls back to 10 rows');

        $this->assertCount(5, $this->fetch($url, ['length' => 5])['data']);
    }

    /** @dataProvider grids */
    public function test_print_request_returns_every_filtered_row(string $url): void
    {
        // length 10 on purpose: for_print must not depend on the client asking for enough rows.
        $json = $this->fetch($url, ['length' => 10, 'for_print' => 1]);

        $this->assertSame(self::ROWS, $json['recordsFiltered'], 'search is still applied');
        $this->assertCount($json['recordsFiltered'], $json['data']);
        $this->assertLessThan($json['recordsTotal'] + 1, count($json['data']));
        foreach ($json['data'] as $row) {
            $this->assertStringContainsString($this->token, implode(' ', array_map('strval', $row)), 'only rows matching the search');
        }
    }

    /** @dataProvider grids */
    public function test_print_request_still_requires_login(string $url): void
    {
        auth()->logout();
        $this->flushSession();

        $this->get($url.'?draw=1&for_print=1', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertRedirect();
        $this->assertGuest();
    }

    public function test_page_length_rule(): void
    {
        $length = fn (array $q) => DataTableSearchHelper::pageLength(Request::create('/', 'GET', $q));

        $this->assertSame(25, $length(['length' => 25]));
        $this->assertSame(100, $length(['length' => 100]));
        $this->assertSame(10, $length(['length' => 101]));
        $this->assertSame(10, $length(['length' => 0]));
        $this->assertSame(10, $length(['length' => -1]), 'a normal request cannot ask for everything');
        $this->assertSame(10, $length([]));
        $this->assertSame(DataTableSearchHelper::EXPORT_MAX_ROWS, $length(['length' => 10, 'for_print' => 1]));
        $this->assertSame(DataTableSearchHelper::EXPORT_MAX_ROWS, $length(['length' => -1, 'for_print' => 1]), 'for_print is capped, never unbounded');
        $this->assertSame(10, $length(['length' => 10, 'for_print' => 0]));
        // The cap must stay above the largest grid on record (Selling Voucher, 14,815 rows) or Download refuses it.
        $this->assertGreaterThan(14815, DataTableSearchHelper::EXPORT_MAX_ROWS);
    }
}
