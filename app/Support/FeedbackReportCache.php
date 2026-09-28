<?php

namespace App\Support;

use Closure;
use Illuminate\Contracts\Cache\Repository;
use Throwable;

/**
 * Cache for the read-mostly lookups behind the session-feedback reports.
 *
 * Backed by whichever store {@see RedisBackedCache} resolves.
 *
 * Store selection: store() probes the stores in candidateStoreNames() order (the chain's
 * preferred store, then `file`, then cache.default) with a throwaway write and keeps the first
 * that accepts it, once per PHP process. With neither REDIS_BACKED_CACHE_STORE nor
 * APP_REDIS_CACHE_STORE set, the preferred name is "redis"; when no Redis client is loaded,
 * `file` is tried first. So a box without Redis caches on the file store rather than failing.
 *
 * Costs of that choice: a loaded but unreachable Redis costs one failed connect per process
 * before falling back; and if the store resolves differently across processes (Redis
 * flapping), a bust goes to one store while reads come from the other. Only if EVERY candidate
 * rejects the probe does a call throw, get report()ed, and fall through to computing:
 * no caching, never wrong data. On the file store, entries retired by a bust are never read
 * again, so nothing deletes them — schedule `cache:prune`-style cleanup there, or run on Redis.
 *
 * Invalidation is by generation counter rather than by deleting keys. Entries are namespaced
 * with the current generation; submitting feedback bumps it, so every existing entry becomes
 * unreachable at once and expires on its own TTL. That matters because the cached lookups are
 * keyed by arbitrary filter combinations, which cannot be enumerated to delete, and because
 * cache tags are unavailable on the file store this project falls back to.
 *
 * ONLY cache values that do not vary per user. Several feedback queries are scoped by the
 * viewer's role (see ScopesSessionFeedbackReports); those must either stay uncached or carry
 * the viewer in the key, otherwise one user's rows leak into another's report.
 */
final class FeedbackReportCache
{
    /** Dropdown/reference lookups: cheap to rebuild, safe to serve slightly stale. */
    public const TTL_LOOKUP = 900;      // 15 minutes

    /** Typeahead suggestions: hit repeatedly while the user types. */
    public const TTL_SUGGESTIONS = 600; // 10 minutes

    /** Dashboard counters: the pre-existing TTL for these, preserved. */
    public const TTL_STATS = 300;       // 5 minutes

    private const GENERATION_KEY = 'feedback_reports:generation';

    /** Store name proven writable this request; skips re-probing on every call. */
    private static ?string $resolvedStore = null;

    /**
     * The cache store these reports use, proven usable before it is returned.
     *
     * RedisBackedCache::projectDefaultStoreName() returns 'redis' when neither
     * REDIS_BACKED_CACHE_STORE nor APP_REDIS_CACHE_STORE is set, and repositoryForStore()
     * hands that back unconditionally because 'redis' is always present in config/cache.php.
     * On a host without the phpredis/predis client the first get() then throws, so the
     * "falls back to cache.default" promise never actually fired: every call threw, was
     * reported, and recomputed. getPendingStats() in particular lost the working file-store
     * cache it had before these reports moved onto this class.
     *
     * So probe instead of assume — write a throwaway key and keep the first store that
     * accepts it. Mirrors ProcessMessBillsEmployeeController::processMessBillsCacheRepository().
     */
    public static function store(): Repository
    {
        if (self::$resolvedStore !== null) {
            return RedisBackedCache::repositoryForStore(self::$resolvedStore);
        }

        foreach (self::candidateStoreNames() as $name) {
            try {
                $repository = RedisBackedCache::repositoryForStore($name);
                $repository->put('feedback_reports:probe', 1, 10);
                self::$resolvedStore = $name;

                return $repository;
            } catch (Throwable $e) {
                continue;
            }
        }

        self::$resolvedStore = (string) config('cache.default', 'file');

        return RedisBackedCache::repositoryForStore(self::$resolvedStore);
    }

    /**
     * Preferred store first, then the fallbacks worth trying.
     *
     * When the chain resolves to 'redis' but no client extension is loaded, try 'file' first
     * rather than paying a connection failure on every call.
     *
     * @return array<int, string>
     */
    private static function candidateStoreNames(): array
    {
        $preferred = RedisBackedCache::projectDefaultStoreName();
        $configured = config('cache.stores', []);
        $names = [];

        if ($preferred === 'redis' && ! extension_loaded('redis') && ! class_exists(\Predis\Client::class)) {
            if (array_key_exists('file', $configured)) {
                $names[] = 'file';
            }
            $names[] = $preferred;
        } else {
            $names[] = $preferred;
            if ($preferred !== 'file' && array_key_exists('file', $configured)) {
                $names[] = 'file';
            }
        }

        $default = (string) config('cache.default', 'file');
        if ($default !== '' && ! in_array($default, $names, true)) {
            $names[] = $default;
        }

        return array_values(array_unique($names));
    }

    /**
     * Remember a value under the current generation.
     *
     * Cache problems must never take a report down, so a store failure falls through to
     * computing the value directly.
     *
     * The callback is invoked OUTSIDE every try block, and so runs exactly once. Wrapping
     * Repository::remember() instead would put the callback inside the catch's reach: a callback
     * that throws — a QueryException from the report query, say — would be report()ed as though
     * the cache had failed and then executed a second time, doubling the work at the moment the
     * database is already in trouble. The get/put split below is what Repository::remember() does
     * internally (miss on null, put, return), so the caching semantics are unchanged.
     */
    public static function remember(string $key, int $ttl, Closure $callback)
    {
        $store = null;
        $namespaced = null;

        try {
            $store = self::store();
            $namespaced = self::namespaced($key);

            $cached = $store->get($namespaced);
            if ($cached !== null) {
                return $cached;
            }
        } catch (Throwable $e) {
            report($e);
            $store = null;
        }

        $value = $callback();

        if ($store !== null) {
            try {
                $store->put($namespaced, $value, $ttl);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $value;
    }

    /**
     * Invalidate every cached feedback lookup by moving to a new generation.
     * Call after any write to topic_feedback.
     */
    public static function bust(): void
    {
        try {
            $store = self::store();
            if ($store->get(self::GENERATION_KEY) === null) {
                $store->forever(self::GENERATION_KEY, 1);
            }
            $store->increment(self::GENERATION_KEY);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public static function generation(): int
    {
        try {
            $store = self::store();
            $current = $store->get(self::GENERATION_KEY);
            if ($current === null) {
                $store->forever(self::GENERATION_KEY, 1);

                return 1;
            }

            return (int) $current;
        } catch (Throwable $e) {
            report($e);

            return 1;
        }
    }

    private static function namespaced(string $key): string
    {
        return 'feedback_reports:v' . self::generation() . ':' . $key;
    }
}
