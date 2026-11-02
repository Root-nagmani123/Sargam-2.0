<?php

namespace Tests\Feature;

use App\Http\Controllers\Mess\ProcessMessBillsEmployeeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use ReflectionProperty;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Generate Invoice modal: a buyer's period paid / due must equal the statement's
 * (computeCombinedBillFinancials, FIFO over all of the buyer's lines up to date_to).
 *
 * The modal gets there with a pooled shortcut (withBuyerPooledPeriodPaid) rather than
 * the per-line FIFO loop, so the two only agree while both read the same bills. These
 * tests fail if either side later changes on its own.
 *
 * Rows are dated in 2031 so no real bill falls in the period, every test is rolled back,
 * and the controller cache is pointed at the in-memory array store so nothing is left in
 * the application cache.
 *
 * Run with:  php vendor/bin/phpunit --filter=MessGenerateInvoiceModalPaid
 */
class MessGenerateInvoiceModalPaidTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const SLUG = 'other';

    private const KITCHEN_CLIENT_TYPE_OTHER = 4;

    private const CLIENT_TYPE_PK = 6;

    private const FROM = '2031-09-01';

    private const TO = '2031-09-30';

    /**
     * The reported defect: one voucher with Aug and Sep items, part paid. FIFO settles
     * August first, so September still owes 800 and the buyer must be in the modal.
     */
    public function test_part_paid_multi_month_voucher_shows_its_period_due(): void
    {
        $buyer = $this->buyerName('A');
        $this->svVoucher($buyer, 1200, [['2031-08-15', 1000], ['2031-09-10', 1000]]);

        $row = $this->modalRow($buyer);

        $this->assertNotNull($row, 'buyer with a September due is missing from the modal');
        $this->assertSame('200.00', $row['paid_amount']);
        $this->assertSame('800.00', $row['due_amount']);
        $this->assertStatementMatches($buyer, $row, 1000.0, 200.0, 800.0);
    }

    public function test_fully_paid_buyer_is_not_in_the_modal(): void
    {
        $buyer = $this->buyerName('B');
        $this->svVoucher($buyer, 2000, [['2031-08-15', 1000], ['2031-09-10', 1000]]);

        $this->assertNull($this->modalRow($buyer), 'fully paid buyer listed in the modal');

        $statement = $this->statement($buyer);
        $this->assertEqualsWithDelta(0.0, $statement['due'], 0.001);
    }

    /**
     * An older unpaid kitchen issue absorbs the payment first, so the September voucher
     * keeps only what is left (800 - 500 = 300 paid, 700 due).
     */
    public function test_older_unpaid_kitchen_issue_absorbs_the_payment_first(): void
    {
        $buyer = $this->buyerName('C');
        $this->kitchenIssue($buyer, '2031-07-20', 500);
        $this->svVoucher($buyer, 800, [['2031-09-10', 1000]]);

        $row = $this->modalRow($buyer);

        $this->assertNotNull($row, 'buyer with a September due is missing from the modal');
        $this->assertSame('300.00', $row['paid_amount']);
        $this->assertSame('700.00', $row['due_amount']);
        $this->assertStatementMatches($buyer, $row, 1000.0, 300.0, 700.0);
    }

    private function buyerName(string $tag): string
    {
        return 'Modal Paid Test ' . $tag . ' ' . uniqid();
    }

    /**
     * @param  list<array{0: string, 1: float}>  $lines  [issue_date, amount] — qty 1 at rate = amount
     */
    private function svVoucher(string $buyer, float $paid, array $lines): void
    {
        $dates = array_column($lines, 0);
        $total = array_sum(array_column($lines, 1));

        $id = DB::table('sv_date_range_reports')->insertGetId([
            'date_from' => min($dates),
            'date_to' => max($dates),
            'store_id' => 1,
            'status' => 1,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'client_type_slug' => self::SLUG,
            'client_type_pk' => self::CLIENT_TYPE_PK,
            'client_name' => $buyer,
            'payment_type' => 1,
            'issue_date' => min($dates),
        ]);

        foreach ($lines as [$date, $amount]) {
            DB::table('sv_date_range_report_items')->insert([
                'sv_date_range_report_id' => $id,
                'item_name' => 'Test item',
                'quantity' => 1,
                'rate' => $amount,
                'amount' => $amount,
                'issue_date' => $date,
            ]);
        }
    }

    /** Unpaid kitchen issue (no payment details); item issue_date left NULL as the app does. */
    private function kitchenIssue(string $buyer, string $date, float $amount): void
    {
        $pk = DB::table('kitchen_issue_master')->insertGetId([
            'store_id' => 1,
            'kitchen_issue_type' => 1,
            'issue_date' => $date,
            'client_type' => self::KITCHEN_CLIENT_TYPE_OTHER,
            'client_type_pk' => self::CLIENT_TYPE_PK,
            'payment_type' => 1,
            'client_name' => $buyer,
            'status' => 0,
        ]);

        DB::table('kitchen_issue_items')->insert([
            'kitchen_issue_master_pk' => $pk,
            'item_name' => 'Test item',
            'quantity' => 1,
            'rate' => $amount,
            'amount' => $amount,
        ]);
    }

    private function controller(): ProcessMessBillsEmployeeController
    {
        $controller = new ProcessMessBillsEmployeeController();

        $store = new ReflectionProperty($controller, 'processMessBillsResolvedCacheStore');
        $store->setAccessible(true);
        $store->setValue($controller, 'array');

        return $controller;
    }

    /** The buyer's row from the Generate Invoice modal endpoint, or null when not listed. */
    private function modalRow(string $buyer): ?array
    {
        $response = $this->controller()->modalData(Request::create('/', 'GET', [
            'date_from' => self::FROM,
            'date_to' => self::TO,
            'per_page' => 100,
            'skip_lifetime_due' => 1,
        ]));

        foreach ($response->getData(true)['bills'] ?? [] as $row) {
            if (trim((string) $row['buyer_name']) === $buyer) {
                return $row;
            }
        }

        return null;
    }

    /** @return array{total: float, paid: float, due: float} */
    private function statement(string $buyer): array
    {
        $method = new ReflectionMethod(ProcessMessBillsEmployeeController::class, 'computeCombinedBillFinancials');
        $method->setAccessible(true);

        return $method->invoke($this->controller(), $buyer, self::SLUG, self::FROM, self::TO);
    }

    private function assertStatementMatches(string $buyer, array $row, float $total, float $paid, float $due): void
    {
        $statement = $this->statement($buyer);

        $this->assertEqualsWithDelta($total, $statement['total'], 0.001, 'statement total');
        $this->assertEqualsWithDelta($paid, $statement['paid'], 0.001, 'statement paid');
        $this->assertEqualsWithDelta($due, $statement['due'], 0.001, 'statement due');
        $this->assertSame(number_format($statement['paid'], 2), $row['paid_amount'], 'modal paid differs from statement');
        $this->assertSame(number_format($statement['due'], 2), $row['due_amount'], 'modal due differs from statement');
    }
}
