<?php

namespace Tests\Feature;

use App\Http\Controllers\Mess\ReportController;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Stock Summary and Stock Balance Till Date value stock at a running weighted average.
 *
 * Stock Summary used to compute sale value as opening + purchase - closing, with closing at the
 * average of every purchase ever made. Purchases that were already sold still moved that average,
 * so an item with no sale showed a sale value (PR #333 F-001). The rules pinned here:
 *   - no sale quantity means no sale value,
 *   - opening + purchase - sale = closing on every row,
 *   - the opening of a period equals the closing of the day before,
 *   - purchase value includes tax and equals the Stock Purchase Details total,
 *   - Stock Balance Till Date equals Stock Summary closing.
 *
 * Every row is created inside a transaction for a store and items of its own, dated 2020, and the
 * reports are filtered to that store, so real data neither affects nor is affected by the test.
 */
class MessStockValuationReportTest extends TestCase
{
    use DatabaseTransactions;

    private int $vendorId;

    private int $storeId;

    private int $subStoreId;

    protected function setUp(): void
    {
        parent::setUp();

        $now = now();
        $this->vendorId = DB::table('mess_vendors')->insertGetId(['name' => 'Test Vendor '.uniqid(), 'created_at' => $now, 'updated_at' => $now]);
        $this->storeId = DB::table('mess_stores')->insertGetId(['store_name' => 'Test Store '.uniqid(), 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        $this->subStoreId = DB::table('mess_sub_stores')->insertGetId(['sub_store_name' => 'Test Sub Store '.uniqid(), 'created_at' => $now, 'updated_at' => $now]);
    }

    private function item(string $label, ?float $standardCost = null): array
    {
        $name = "Test {$label} ".uniqid();
        $id = DB::table('mess_item_subcategories')->insertGetId([
            'name' => $name,
            'item_code' => 'T'.uniqid(),
            'status' => 'active',
            'standard_cost' => $standardCost,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$id, $name];
    }

    private function purchase(int $itemId, string $date, float $qty, float $unitPrice, float $taxPercent = 0): void
    {
        $poId = DB::table('mess_purchase_orders')->insertGetId([
            'po_number' => 'TPO'.uniqid(),
            'vendor_id' => $this->vendorId,
            'store_id' => $this->storeId,
            'po_date' => $date,
            'status' => 'approved',
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('mess_purchase_order_items')->insert([
            'purchase_order_id' => $poId,
            'item_subcategory_id' => $itemId,
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'tax_percent' => $taxPercent,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function allocate(int $itemId, string $date, float $qty, float $unitPrice): void
    {
        $allocationId = DB::table('mess_store_allocations')->insertGetId([
            'sub_store_id' => $this->subStoreId,
            'allocation_date' => $date,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('mess_store_allocation_items')->insert([
            'store_allocation_id' => $allocationId,
            'item_subcategory_id' => $itemId,
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function kitchenIssueSale(int $itemId, string $date, float $qty, string $storeType = 'store'): void
    {
        $masterPk = DB::table('kitchen_issue_master')->insertGetId([
            'store_id' => $storeType === 'store' ? $this->storeId : $this->subStoreId,
            'store_type' => $storeType,
            'kitchen_issue_type' => 1,
            'client_type_pk' => 1,
            'issue_date' => $date,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'pk');
        DB::table('kitchen_issue_items')->insert([
            'kitchen_issue_master_pk' => $masterPk,
            'item_subcategory_id' => $itemId,
            'item_name' => 'Test',
            'quantity' => $qty,
            'return_quantity' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function dateRangeSale(int $itemId, string $date, float $qty): void
    {
        $reportId = DB::table('sv_date_range_reports')->insertGetId([
            'date_from' => $date,
            'date_to' => $date,
            'store_id' => $this->storeId,
            'store_type' => 'store',
            'client_type_pk' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sv_date_range_report_items')->insert([
            'sv_date_range_report_id' => $reportId,
            'item_subcategory_id' => $itemId,
            'issue_date' => $date,
            'quantity' => $qty,
            'return_quantity' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function callReport(string $method, ...$args)
    {
        $reflection = new ReflectionMethod(ReportController::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke(app(ReportController::class), ...$args);
    }

    private function summaryRow(string $from, string $to, string $itemName, string $storeType = 'main'): array
    {
        $storeIds = [$storeType === 'main' ? $this->storeId : $this->subStoreId];
        [$rows] = $this->callReport('getStockSummaryReportData', $from, $to, $storeIds, $storeType);
        $row = collect($rows)->firstWhere('item_name', $itemName);
        $this->assertNotNull($row, "{$itemName} missing from Stock Summary {$from}..{$to}");

        return $row;
    }

    private function assertRowBalances(array $row): void
    {
        $this->assertEqualsWithDelta(
            $row['closing_amount'],
            $row['opening_amount'] + $row['purchase_amount'] - $row['sale_amount'],
            0.01,
            'opening + purchase - sale must equal closing'
        );
    }

    /**
     * 100 bought at 800, 99 sold, then 10 bought at 600 + 5% tax in February with nothing sold.
     * The old code valued closing at the all-time average (805.09) and booked -93.17 as "sale".
     */
    private function seedGhee(): string
    {
        [$itemId, $name] = $this->item('Ghee');
        $this->purchase($itemId, '2020-01-05', 100, 800);
        $this->kitchenIssueSale($itemId, '2020-01-10', 99);
        $this->purchase($itemId, '2020-02-03', 10, 600, 5);
        $this->dateRangeSale($itemId, '2020-03-05', 2);

        return $name;
    }

    public function test_no_sale_quantity_means_no_sale_value(): void
    {
        $row = $this->summaryRow('2020-02-01', '2020-02-29', $this->seedGhee());

        $this->assertEqualsWithDelta(1, $row['opening_qty'], 0.0001);
        $this->assertEqualsWithDelta(800, $row['opening_amount'], 0.01);
        $this->assertEqualsWithDelta(0, $row['sale_qty'], 0.0001);
        $this->assertEqualsWithDelta(0, $row['sale_amount'], 0.0001, 'no sale quantity, so no sale value');
        $this->assertEqualsWithDelta(11, $row['closing_qty'], 0.0001);
        $this->assertEqualsWithDelta(7100, $row['closing_amount'], 0.01, 'closing is the stock actually on hand: 800 + 6300');
        $this->assertRowBalances($row);
    }

    public function test_sale_is_valued_at_the_running_average_on_its_date(): void
    {
        $name = $this->seedGhee();

        $january = $this->summaryRow('2020-01-01', '2020-01-31', $name);
        $this->assertEqualsWithDelta(99 * 800, $january['sale_amount'], 0.01);
        $this->assertRowBalances($january);

        $march = $this->summaryRow('2020-03-01', '2020-03-31', $name);
        $this->assertEqualsWithDelta(2, $march['sale_qty'], 0.0001);
        $this->assertEqualsWithDelta(2 * 7100 / 11, $march['sale_amount'], 0.01);
        $this->assertEqualsWithDelta(9 * 7100 / 11, $march['closing_amount'], 0.01);
        $this->assertRowBalances($march);
    }

    public function test_opening_equals_the_closing_of_the_day_before(): void
    {
        $name = $this->seedGhee();

        $february = $this->summaryRow('2020-02-01', '2020-02-29', $name);
        $march = $this->summaryRow('2020-03-01', '2020-03-31', $name);

        $this->assertEqualsWithDelta($february['closing_qty'], $march['opening_qty'], 0.0001);
        $this->assertEqualsWithDelta($february['closing_amount'], $march['opening_amount'], 0.01);
    }

    public function test_purchase_value_includes_tax_and_matches_purchase_details(): void
    {
        $row = $this->summaryRow('2020-02-01', '2020-02-29', $this->seedGhee());

        $this->assertEqualsWithDelta(6300, $row['purchase_amount'], 0.01, '10 x 600 + 5% tax');
        $this->assertEqualsWithDelta(630, $row['purchase_rate'], 0.0001);

        $purchaseDetailsTotal = $this->callReport('computeStockPurchaseDetailGrandTotal', [
            'fromDate' => '2020-02-01',
            'toDate' => '2020-02-29',
            'vendorIds' => [],
            'storeIds' => [$this->storeId],
        ]);
        $this->assertEqualsWithDelta($purchaseDetailsTotal, $row['purchase_amount'], 0.01);
    }

    public function test_stock_balance_till_date_equals_stock_summary_closing(): void
    {
        $name = $this->seedGhee();

        $summary = $this->summaryRow('2020-02-01', '2020-02-29', $name);
        $balance = collect($this->callReport('buildStockBalanceTillDateData', '2020-02-29', [$this->storeId]))
            ->firstWhere('item_name', $name);

        $this->assertNotNull($balance, 'item missing from Stock Balance Till Date');
        $this->assertEqualsWithDelta($summary['closing_qty'], $balance['remaining_qty'], 0.0001);
        $this->assertEqualsWithDelta($summary['closing_amount'], $balance['amount'], 0.01);
        $this->assertEqualsWithDelta($summary['closing_rate'], $balance['rate'], 0.0001);
    }

    public function test_oversold_stock_is_recosted_at_the_next_purchase_rate(): void
    {
        [$itemId, $name] = $this->item('Oversold', 100);
        $this->kitchenIssueSale($itemId, '2020-01-10', 5);
        $this->purchase($itemId, '2020-01-20', 10, 200);

        $row = $this->summaryRow('2020-01-01', '2020-01-31', $name);

        $this->assertEqualsWithDelta(5 * 200, $row['sale_amount'], 0.01, 'the 5 issued without stock are costed at the purchase that covered them');
        $this->assertEqualsWithDelta(5, $row['closing_qty'], 0.0001);
        $this->assertEqualsWithDelta(200, $row['closing_rate'], 0.0001);
        $this->assertRowBalances($row);
    }

    public function test_sub_store_allocations_use_the_same_valuation(): void
    {
        [$itemId, $name] = $this->item('Sub Item');
        $this->allocate($itemId, '2020-01-05', 50, 100);
        $this->kitchenIssueSale($itemId, '2020-01-07', 10, 'sub_store');
        $this->allocate($itemId, '2020-02-03', 10, 160);

        $row = $this->summaryRow('2020-02-01', '2020-02-29', $name, 'sub');

        $this->assertEqualsWithDelta(4000, $row['opening_amount'], 0.01);
        $this->assertEqualsWithDelta(1600, $row['purchase_amount'], 0.01);
        $this->assertEqualsWithDelta(0, $row['sale_amount'], 0.0001);
        $this->assertEqualsWithDelta(5600, $row['closing_amount'], 0.01);
        $this->assertRowBalances($row);
    }
}
