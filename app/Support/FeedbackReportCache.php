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
 * DEPLOYMENT REQUIREMENT: this environment must be able to reach the store the chain resolves
 * to. With neither REDIS_BACKED_CACHE_STORE nor APP_REDIS_CACHE_STORE set, that name is "redis" —
 * and because "redis" is always present in config/cache.php, the cache.default fallback in
 * RedisBackedCache::repositoryForStore() is NOT reached. Two configurations satisfy this:
 *
 *   - CACHE_DRIVER=redis with a reachable Redis — the deployed production setting. The resolved
 *     store and the fallback are then the same store, so the unreachable fallback is moot.
 *   - REDIS_BACKED_CACHE_STORE set explicitly to a reachable store (e.g. `file`) — needed on any
 *     box where Redis is absent, such as a developer machine or a CI worker with no .env.
 *
 * Where neither holds, every remember()/bust()/generation() call throws inside the store, is
 * report()ed, and falls through to computing: no caching at all, and one reported exception per
 * call on hot paths including the per-keystroke typeahead. Nothing serves wrong data, but
 * nothing is cached either. The same chain backs the Estate, Mess and DataTable caches, so this
 * is not specific to these reports.
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

    public static function store(): Repository
    {
        return RedisBackedCache::repositoryForStore(
            RedisBackedCache::projectDefaultStoreName()
        );
    }

    /**
     * Remember a value under the current generation.
     *
     * Cache problems must never take a report down, so a store failure falls through to
     * computing the value directly.
     */
    public static function remember(string $key, int $ttl, Closure $callback)
    {
        try {
            return self::store()->remember(self::namespaced($key), $ttl, $callback);
        } catch (Throwable $e) {
            report($e);

            return $callback();
        }
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
