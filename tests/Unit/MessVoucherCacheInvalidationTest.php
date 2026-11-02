<?php

namespace Tests\Unit;

use App\Http\Controllers\Mess\KitchenIssueController;
use App\Http\Controllers\Mess\ProcessMessBillsEmployeeController;
use App\Http\Controllers\Mess\SellingVoucherDateRangeController;
use Illuminate\Contracts\Cache\Repository;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Covers the Process Mess Bills cache invalidation a voucher write performs.
 *
 * Kitchen Issue and Selling Voucher (Date Range) writes all end in their controller's
 * bump...ListingCacheEpoch(). Before this was wired up, those writes left the grouped
 * bills and modal summaries cached until the TTL, so a new or edited voucher stayed
 * off Process Mess Bills for up to half an hour.
 *
 * Database-free, like MessPaymentCacheInvalidationTest: only the cache store is touched.
 *
 * Run with:  php vendor/bin/phpunit --filter=MessVoucherCacheInvalidation
 */
class MessVoucherCacheInvalidationTest extends TestCase
{
    private const VERSION_KEY = 'process_mess_bills_combined_cache_version';

    private Repository $store;

    protected function setUp(): void
    {
        parent::setUp();

        $controller = new ProcessMessBillsEmployeeController();
        $method = new ReflectionMethod($controller, 'processMessBillsCacheRepository');
        $method->setAccessible(true);
        $this->store = $method->invoke($controller);
    }

    private function version(): int
    {
        return (int) $this->store->get(self::VERSION_KEY, 0);
    }

    public function test_a_kitchen_issue_write_moves_the_combined_bills_version(): void
    {
        $this->store->put(self::VERSION_KEY, 5, 3600);

        KitchenIssueController::bumpSellingVoucherListingCacheEpoch();

        $this->assertGreaterThan(5, $this->version(), 'A selling voucher write must invalidate the Process Mess Bills cache.');
    }

    public function test_a_date_range_voucher_write_moves_the_combined_bills_version(): void
    {
        $this->store->put(self::VERSION_KEY, 9, 3600);

        SellingVoucherDateRangeController::bumpSellingVoucherDateRangeListingCacheEpoch();

        $this->assertGreaterThan(9, $this->version(), 'A date-range voucher write must invalidate the Process Mess Bills cache.');
    }

    public function test_the_first_write_on_a_cold_store_still_moves_the_version(): void
    {
        $this->store->forget(self::VERSION_KEY);

        ProcessMessBillsEmployeeController::invalidateCombinedBillsCache();

        $this->assertGreaterThan(1, $this->version(), 'A missing version key must be created above the default of 1.');
    }
}
