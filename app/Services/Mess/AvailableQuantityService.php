<?php

namespace App\Services\Mess;

use App\Models\KitchenIssueMaster;
use App\Models\Mess\ItemSubcategory;
use App\Support\RedisBackedCache;
use Illuminate\Support\Collection;
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

    /**
     * Get available quantities by item_subcategory_id for a store/sub-store.
     * Subtracts issued quantities from BOTH Selling Voucher (kitchen_issue) and Selling Voucher with Date Range.
     *
     * @return array<int, float> [item_subcategory_id => available_quantity]
     */
    public static function availableQuantitiesForStore(string $storeType, int $storeId, bool $fresh = false): array
    {
        if ($fresh) {
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
     * FIFO price tiers + available quantity for every item purchased/allocated into a store/sub-store.
     * Moved here verbatim from KitchenIssueController::getStoreItems() and
     * SellingVoucherDateRangeController::getStoreItemsData(), which were byte-identical.
     *
     * @return Collection<int, array{id:int,item_name:string,unit_measurement:string,standard_cost:float,available_quantity:float,price_tiers:array}>
     */
    public static function fifoPriceTiersForStore(string $storeType, int $storeId): Collection
    {
        $items = collect();

        if ($storeType === 'sub_store') {
            // FIFO: get allocation items ordered by date (oldest first) for price tiers
            $fifoRows = DB::table('mess_store_allocation_items as sai')
                ->join('mess_store_allocations as sa', 'sai.store_allocation_id', '=', 'sa.id')
                ->where('sa.sub_store_id', $storeId)
                ->orderByRaw('COALESCE(sa.allocation_date, sa.created_at) ASC')
                ->orderBy('sa.id')
                ->orderBy('sai.id')
                ->select('sai.item_subcategory_id', 'sai.quantity', 'sai.unit_price')
                ->get();

            $tiersByItem = [];
            foreach ($fifoRows as $r) {
                $id = (int) ($r->item_subcategory_id ?? 0);
                if ($id <= 0) continue;
                if (!isset($tiersByItem[$id])) $tiersByItem[$id] = [];
                $tiersByItem[$id][] = ['quantity' => (float) $r->quantity, 'unit_price' => (float) $r->unit_price];
            }

            $allocatedItems = DB::table('mess_store_allocation_items as sai')
                ->join('mess_store_allocations as sa', 'sai.store_allocation_id', '=', 'sa.id')
                ->where('sa.sub_store_id', $storeId)
                ->select(
                    'sai.item_subcategory_id',
                    DB::raw('SUM(sai.quantity) as total_quantity'),
                    DB::raw('SUM(sai.quantity * sai.unit_price) / NULLIF(SUM(sai.quantity), 0) as avg_unit_price')
                )
                ->groupBy('sai.item_subcategory_id')
                ->get()
                ->keyBy('item_subcategory_id');

            $availableMap = self::availableQuantitiesForStore($storeType, $storeId);

            if ($allocatedItems->isNotEmpty()) {
                $itemIds = $allocatedItems->keys();
                $items = ItemSubcategory::whereIn('id', $itemIds)
                    ->active()
                    ->get()
                    ->map(function ($s) use ($allocatedItems, $availableMap, $tiersByItem) {
                        $allocated = $allocatedItems->get($s->id);
                        $storeRate = $allocated && isset($allocated->avg_unit_price) ? (float) $allocated->avg_unit_price : null;
                        $rawTiers = $tiersByItem[$s->id] ?? [];
                        $available = (float) ($availableMap[$s->id] ?? 0);
                        $totalAllocated = array_sum(array_column($rawTiers, 'quantity'));
                        $issued = max(0, $totalAllocated - $available);
                        $adjustedTiers = [];
                        $remainingIssued = $issued;
                        foreach ($rawTiers as $t) {
                            $qty = (float) ($t['quantity'] ?? 0);
                            $take = min($remainingIssued, $qty);
                            $remaining = $qty - $take;
                            $remainingIssued -= $take;
                            if ($remaining > 0) {
                                $adjustedTiers[] = ['quantity' => $remaining, 'unit_price' => (float) ($t['unit_price'] ?? 0)];
                            }
                        }
                        $tiers = $adjustedTiers;
                        $firstPrice = !empty($tiers) ? $tiers[0]['unit_price'] : null;
                        return [
                            'id' => $s->id,
                            'item_name' => $s->item_name ?? $s->name ?? '—',
                            'unit_measurement' => $s->unit_measurement ?? '—',
                            'standard_cost' => $firstPrice ?? ($storeRate !== null ? $storeRate : ($s->standard_cost ?? 0)),
                            'available_quantity' => $available,
                            'price_tiers' => $tiers,
                        ];
                    });
            }
        } else {
            // Main store: FIFO from purchase orders (oldest first by po_date = purchase date)
            // IMPORTANT: Use unit price INCLUDING tax so that selling vouchers
            // reflect the tax-applied purchase cost in their Rate / Total.
            $fifoRows = DB::table('mess_purchase_order_items as poi')
                ->join('mess_purchase_orders as po', 'poi.purchase_order_id', '=', 'po.id')
                ->where('po.store_id', $storeId)
                ->where('po.status', 'approved')
                ->whereNotNull('poi.item_subcategory_id')
                ->where('poi.item_subcategory_id', '>', 0)
                ->orderBy('po.po_date', 'asc')
                ->orderBy('po.id')
                ->orderBy('poi.id')
                ->select(
                    'poi.item_subcategory_id',
                    'poi.quantity',
                    'poi.unit_price',
                    'poi.tax_percent'
                )
                ->get();

            $tiersByItem = [];
            foreach ($fifoRows as $r) {
                $id = (int) ($r->item_subcategory_id ?? 0);
                if ($id <= 0) {
                    continue;
                }
                if (!isset($tiersByItem[$id])) {
                    $tiersByItem[$id] = [];
                }
                $unitPrice = (float) $r->unit_price;
                $taxPercent = isset($r->tax_percent) ? (float) $r->tax_percent : 0.0;
                $effectiveUnitPrice = $unitPrice * (1 + $taxPercent / 100);
                $tiersByItem[$id][] = [
                    'quantity' => (float) $r->quantity,
                    'unit_price' => $effectiveUnitPrice,
                ];
            }

            $purchasedItems = DB::table('mess_purchase_order_items as poi')
                ->join('mess_purchase_orders as po', 'poi.purchase_order_id', '=', 'po.id')
                ->where('po.store_id', $storeId)
                ->where('po.status', 'approved')
                ->select(
                    'poi.item_subcategory_id',
                    DB::raw('SUM(poi.quantity) as total_quantity'),
                    // Average unit price INCLUDING tax, matching FIFO tiers above
                    DB::raw('SUM(poi.quantity * poi.unit_price * (1 + COALESCE(poi.tax_percent, 0) / 100)) / NULLIF(SUM(poi.quantity), 0) as avg_unit_price')
                )
                ->groupBy('poi.item_subcategory_id')
                ->get()
                ->keyBy('item_subcategory_id');

            $availableMap = self::availableQuantitiesForStore($storeType, $storeId);

            if ($purchasedItems->isNotEmpty()) {
                $itemIds = $purchasedItems->keys();
                $items = ItemSubcategory::whereIn('id', $itemIds)
                    ->active()
                    ->get()
                    ->map(function ($s) use ($purchasedItems, $availableMap, $tiersByItem) {
                        $purchased = $purchasedItems->get($s->id);
                        $storeRate = $purchased && isset($purchased->avg_unit_price) ? (float) $purchased->avg_unit_price : null;
                        $rawTiers = $tiersByItem[$s->id] ?? [];
                        $available = (float) ($availableMap[$s->id] ?? 0);
                        // Adjust tiers: subtract already-sold qty (FIFO) to get remaining per tier
                        $totalPurchased = array_sum(array_column($rawTiers, 'quantity'));
                        $issued = max(0, $totalPurchased - $available);
                        $adjustedTiers = [];
                        $remainingIssued = $issued;
                        foreach ($rawTiers as $t) {
                            $qty = (float) ($t['quantity'] ?? 0);
                            $take = min($remainingIssued, $qty);
                            $remaining = $qty - $take;
                            $remainingIssued -= $take;
                            if ($remaining > 0) {
                                $adjustedTiers[] = ['quantity' => $remaining, 'unit_price' => (float) ($t['unit_price'] ?? 0)];
                            }
                        }
                        $tiers = $adjustedTiers;
                        $firstPrice = !empty($tiers) ? $tiers[0]['unit_price'] : null;
                        return [
                            'id' => $s->id,
                            'item_name' => $s->item_name ?? $s->name ?? '—',
                            'unit_measurement' => $s->unit_measurement ?? '—',
                            'standard_cost' => $firstPrice ?? ($storeRate !== null ? $storeRate : ($s->standard_cost ?? 0)),
                            'available_quantity' => $available,
                            'price_tiers' => $tiers,
                        ];
                    });
            }
        }

        return $items->values();
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
        // Resolve matching master PKs first (uses idx_kim_store_type_issue_pk), then aggregate kitchen_issue_items
        // by those PKs (uses idx_kii_master_subcategory). Avoids grouping across the join, which forced MySQL
        // into a temp table + filesort on kitchen_issue_items.item_subcategory_id in the original single-query form.
        $kimPks = DB::table('kitchen_issue_master')
            ->where('store_id', $storeId)
            ->where('store_type', $storeType)
            ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
            ->pluck('pk');

        $kitchenIssued = $kimPks->isEmpty()
            ? collect()
            : DB::table('kitchen_issue_items')
                ->whereIn('kitchen_issue_master_pk', $kimPks)
                ->select('item_subcategory_id', DB::raw('SUM(quantity - COALESCE(return_quantity, 0)) as issued_quantity'))
                ->groupBy('item_subcategory_id')
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
        return max(0, (int) env('MESS_AVAILABLE_QTY_CACHE_SECONDS', 90));
    }
}
