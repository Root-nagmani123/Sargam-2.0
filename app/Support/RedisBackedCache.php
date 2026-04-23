<?php

namespace App\Support;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

/**
 * Central place to resolve which Laravel cache store backs Redis-style heavy caches.
 * Estate module and other features should use this instead of duplicating env() chains.
 *
 * Yajra DataTables JSON caching: use {@see \App\Support\DataTableRedisCache} (which calls this
 * for store name + remember); keep per-screen env keys in each DataTable class.
 */
final class RedisBackedCache
{
    /**
     * Default store name for new project code (not tied to Estate env names).
     * Uses REDIS_BACKED_CACHE_STORE when set, else APP_REDIS_CACHE_STORE, else "redis".
     */
    public static function projectDefaultStoreName(): string
    {
        $unified = self::unifiedStoreNameOrNull();
        if ($unified !== null) {
            return $unified;
        }

        return (string) env('APP_REDIS_CACHE_STORE', 'redis');
    }

    /**
     * Same resolution chain as the former inline env() for Update Meter Reading caches.
     */
    public static function estateUpdateMeterReadingStoreName(): string
    {
        $unified = self::unifiedStoreNameOrNull();
        if ($unified !== null) {
            return $unified;
        }

        return (string) env('ESTATE_UPDATE_METER_READING_CACHE_STORE', env('ESTATE_BILL_REPORT_GRID_CACHE_STORE', 'redis'));
    }

    /**
     * Same resolution chain as the former inline env() for List Meter Reading.
     */
    public static function estateListMeterReadingStoreName(): string
    {
        $unified = self::unifiedStoreNameOrNull();
        if ($unified !== null) {
            return $unified;
        }

        return (string) env('ESTATE_LIST_METER_READING_CACHE_STORE', env('ESTATE_BILL_REPORT_GRID_CACHE_STORE', 'redis'));
    }

    /**
     * Same resolution chain as the former inline env() for Bill Report Grid.
     */
    public static function estateBillReportGridStoreName(): string
    {
        $unified = self::unifiedStoreNameOrNull();
        if ($unified !== null) {
            return $unified;
        }

        return (string) env('ESTATE_BILL_REPORT_GRID_CACHE_STORE', 'redis');
    }

    /**
     * Resolve a configured store name to a cache repository, falling back to cache.default.
     */
    public static function repositoryForStore(string $storeName): Repository
    {
        $name = trim((string) $storeName);
        if ($name !== '' && array_key_exists($name, config('cache.stores', []))) {
            return Cache::store($name);
        }

        return Cache::store(config('cache.default'));
    }

    private static function unifiedStoreNameOrNull(): ?string
    {
        $v = config('cache.redis_backed_unified_store');
        if (! is_string($v)) {
            return null;
        }
        $v = trim($v);

        return $v === '' ? null : $v;
    }
}
