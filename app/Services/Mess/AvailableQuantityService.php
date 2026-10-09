<?php

namespace App\Services\Mess;

use App\Models\KitchenIssueMaster;
use App\Support\RedisBackedCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Shared logic for available quantity calculation across Selling Voucher and Selling Voucher with Date Range.
 * Available = Purchased/Allocated - Issued (from BOTH modules) + Returned.
 *
 * Results are cached briefly per store (Redis-backed) to cut repeated aggregation load.
 * Call {@see self::bumpCacheEpoch()} after issue/return/stock mutations; pass $fresh=true on write validation.
 */
class AvailableQuantityService
{
    private const EPOCH_KEY = 'mess_available_qty_epoch';

    private const CACHE_KEY_PREFIX = 'mess_available_qty:';

    /** Fallback Redis TTL (seconds) when MESS_AVAILABLE_QTY_CACHE_SECONDS is unset. Write-time validation always passes $fresh=true and bypasses this cache, so this only bounds display staleness, not overselling risk. */
    private const DEFAULT_CACHE_TTL = 300;

    /**
     * Get available quantities by item_subcategory_id for a store/sub-store.
     * Subtracts issued quantities from BOTH Selling Voucher (kitchen_issue) and Selling Voucher with Date Range.
     *
     * @return array<int, float> [item_subcategory_id => available_quantity]
     */
    public static function availableQuantitiesForStore(string $storeType, int $storeId, bool $fresh = false): array
    {
        if ($fresh) {
            // $fresh is the write-validation read. Inside a transaction, lock the store first so two
            // stock-changing requests for the same store cannot both pass the check on the same stock.
            if (DB::transactionLevel() > 0) {
                self::lockStoreForStockChange($storeType, $storeId);
            }
            $map = self::computeAvailableQuantitiesForStore($storeType, $storeId);
            self::putCache($storeType, $storeId, $map);

            return $map;
        }

        $ttl = self::cacheTtlSeconds();
        if ($ttl <= 0) {
            return self::computeAvailableQuantitiesForStore($storeType, $storeId);
        }

        try {
            $repo = RedisBackedCache::repositoryForStore(RedisBackedCache::projectDefaultStoreName());
            $key = self::cacheKey($storeType, $storeId);

            /** @var array<int, float> $map */
            $map = $repo->remember($key, $ttl, function () use ($storeType, $storeId) {
                return self::computeAvailableQuantitiesForStore($storeType, $storeId);
            });

            return is_array($map) ? $map : self::computeAvailableQuantitiesForStore($storeType, $storeId);
        } catch (\Throwable $e) {
            Log::warning('AvailableQuantityService: cache read failed; computing fresh.', [
                'message' => $e->getMessage(),
                'store_type' => $storeType,
                'store_id' => $storeId,
            ]);

            return self::computeAvailableQuantitiesForStore($storeType, $storeId);
        }
    }

    /**
     * Row-lock the store (until the surrounding transaction ends). A second request for the same
     * store waits here, then reads stock that includes the first request's change.
     */
    public static function lockStoreForStockChange(string $storeType, int $storeId): void
    {
        if ($storeId <= 0) {
            return;
        }
        DB::table($storeType === 'sub_store' ? 'mess_sub_stores' : 'mess_stores')
            ->where('id', $storeId)
            ->lockForUpdate()
            ->value('id');
    }

    /**
     * Invalidate all cached available-quantity maps (issue / return / stock mutations).
     */
    public static function bumpCacheEpoch(): void
    {
        try {
            $repo = RedisBackedCache::repositoryForStore(RedisBackedCache::projectDefaultStoreName());
            $repo->increment(self::EPOCH_KEY);
        } catch (\Throwable $e) {
            Log::warning('AvailableQuantityService: failed to bump cache epoch.', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Lowering or cancelling a return adds that quantity back to the sale, so it has to be in
     * stock now: the returned goods may already have been sold to another buyer.
     *
     * @param  array<int, float>  $available  from availableQuantitiesForStore(), read before the returns change
     * @param  array<int, array{qty: float, name: string}>  $reductions  item_subcategory_id => net quantity the returns go down by
     * @return array<int, string>  one message per item that is short of stock
     */
    public static function returnReductionShortfalls(array $available, array $reductions): array
    {
        return array_values(array_map(
            fn (array $s) => "{$s['name']}: return cannot be reduced by {$s['qty']}, only {$s['in_stock']} is in stock (the returned quantity has already been issued).",
            self::stockShortfalls($available, $reductions)
        ));
    }

    /**
     * Items that do not have enough stock for the quantity about to be taken out of the store.
     *
     * @param  array<int, float>  $available  from availableQuantitiesForStore(), read before the change
     * @param  array<int, array{qty: float, name: string}>  $reductions  item_subcategory_id => net quantity taken out (zero or negative: none)
     * @return array<int, array{name: string, qty: float, in_stock: float}>
     */
    public static function stockShortfalls(array $available, array $reductions): array
    {
        $shortfalls = [];
        foreach ($reductions as $itemId => $reduction) {
            $qty = round((float) $reduction['qty'], 4);
            if ($qty <= 0) {
                continue;
            }
            $inStock = round((float) ($available[$itemId] ?? 0), 4);
            if ($qty > $inStock) {
                $shortfalls[$itemId] = ['name' => $reduction['name'], 'qty' => $qty, 'in_stock' => $inStock];
            }
        }

        return $shortfalls;
    }

    /**
     * @return array<int, float>
     */
    private static function computeAvailableQuantitiesForStore(string $storeType, int $storeId): array
    {
        // Step 1: Get purchased/allocated quantities
        if ($storeType === 'sub_store') {
            $rows = DB::table('mess_store_allocation_items as sai')
                ->join('mess_store_allocations as sa', 'sai.store_allocation_id', '=', 'sa.id')
                ->where('sa.sub_store_id', $storeId)
                ->select('sai.item_subcategory_id', DB::raw('SUM(sai.quantity) as total_quantity'))
                ->groupBy('sai.item_subcategory_id')
                ->get();
        } else {
            $rows = DB::table('mess_purchase_order_items as poi')
                ->join('mess_purchase_orders as po', 'poi.purchase_order_id', '=', 'po.id')
                ->where('po.store_id', $storeId)
                ->where('po.status', 'approved')
                ->select('poi.item_subcategory_id', DB::raw('SUM(poi.quantity) as total_quantity'))
                ->groupBy('poi.item_subcategory_id')
                ->get();
        }

        $map = [];
        foreach ($rows as $r) {
            $id = (int) ($r->item_subcategory_id ?? 0);
            if ($id > 0) {
                $map[$id] = (float) ($r->total_quantity ?? 0);
            }
        }

        // Step 2: Subtract issued from Selling Voucher (kitchen_issue, kitchen_issue_type = Selling Voucher only)
        $kitchenIssued = DB::table('kitchen_issue_items as kii')
            ->join('kitchen_issue_master as kim', 'kii.kitchen_issue_master_pk', '=', 'kim.pk')
            ->where('kim.store_id', $storeId)
            ->where('kim.store_type', $storeType)
            ->where('kim.kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
            ->select('kii.item_subcategory_id', DB::raw('SUM(kii.quantity - COALESCE(kii.return_quantity, 0)) as issued_quantity'))
            ->groupBy('kii.item_subcategory_id')
            ->get();

        foreach ($kitchenIssued as $r) {
            $id = (int) ($r->item_subcategory_id ?? 0);
            $issued = (float) ($r->issued_quantity ?? 0);
            if ($id > 0) {
                $map[$id] = max(0, ($map[$id] ?? 0) - $issued);
            }
        }

        // Step 3: Subtract issued from Selling Voucher with Date Range
        $svDateRangeIssued = DB::table('sv_date_range_report_items as svi')
            ->join('sv_date_range_reports as svr', 'svi.sv_date_range_report_id', '=', 'svr.id')
            ->where('svr.store_id', $storeId)
            ->where('svr.store_type', $storeType)
            ->select('svi.item_subcategory_id', DB::raw('SUM(svi.quantity - COALESCE(svi.return_quantity, 0)) as issued_quantity'))
            ->groupBy('svi.item_subcategory_id')
            ->get();

        foreach ($svDateRangeIssued as $r) {
            $id = (int) ($r->item_subcategory_id ?? 0);
            $issued = (float) ($r->issued_quantity ?? 0);
            if ($id > 0) {
                $map[$id] = max(0, ($map[$id] ?? 0) - $issued);
            }
        }

        return $map;
    }

    /**
     * @param  array<int, float>  $map
     */
    private static function putCache(string $storeType, int $storeId, array $map): void
    {
        $ttl = self::cacheTtlSeconds();
        if ($ttl <= 0) {
            return;
        }

        try {
            $repo = RedisBackedCache::repositoryForStore(RedisBackedCache::projectDefaultStoreName());
            $repo->put(self::cacheKey($storeType, $storeId), $map, $ttl);
        } catch (\Throwable $e) {
            Log::warning('AvailableQuantityService: cache write failed.', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private static function cacheKey(string $storeType, int $storeId): string
    {
        return self::CACHE_KEY_PREFIX . self::readEpoch() . ':' . $storeType . ':' . $storeId;
    }

    private static function readEpoch(): int
    {
        try {
            $repo = RedisBackedCache::repositoryForStore(RedisBackedCache::projectDefaultStoreName());

            return (int) $repo->get(self::EPOCH_KEY, 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private static function cacheTtlSeconds(): int
    {
        return max(0, (int) env('MESS_AVAILABLE_QTY_CACHE_SECONDS', self::DEFAULT_CACHE_TTL));
    }
}
