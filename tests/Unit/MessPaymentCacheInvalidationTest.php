<?php

namespace Tests\Unit;

use App\Http\Controllers\Mess\ProcessMessBillsEmployeeController;
use Illuminate\Contracts\Cache\Repository;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Covers the cache invalidation a mess payment performs.
 *
 * Database-free on purpose: the invalidation itself touches only the cache store, and
 * the application's mess tables are legacy with no factories, so a seeded test could
 * not be made to run here. What matters for correctness is verifiable without one —
 * that a payment makes the previously cached summaries unreachable even when the
 * best-effort key index has lost the key.
 *
 * The regression these guard against: forgetting only the indexed keys leaves any entry
 * whose key never reached the index serving pre-payment paid/due totals until the TTL
 * (half an hour), which on the money path is a wrong figure rather than a cold cache.
 *
 * Run with:  php vendor/bin/phpunit --filter=MessPaymentCacheInvalidation
 */
class MessPaymentCacheInvalidationTest extends TestCase
{
    private const VERSION_KEY = 'process_mess_bills_combined_cache_version';

    private const INDEX_KEY = 'process_mess_bills_bill_summary_keys';

    private ProcessMessBillsEmployeeController $controller;

    private Repository $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new ProcessMessBillsEmployeeController();
        $this->store = $this->resolveControllerStore();
        $this->store->forget(self::VERSION_KEY);
        $this->store->forget(self::INDEX_KEY);
    }

    /**
     * The controller picks its store through RedisBackedCache rather than cache.default,
     * so read it back from the controller instead of assuming one here.
     */
    private function resolveControllerStore(): Repository
    {
        $method = new ReflectionMethod($this->controller, 'processMessBillsCacheRepository');
        $method->setAccessible(true);

        return $method->invoke($this->controller);
    }

    private function forgetSummaries(): void
    {
        $method = new ReflectionMethod($this->controller, 'forgetProcessMessBillsSummaryCaches');
        $method->setAccessible(true);
        $method->invoke($this->controller);
    }

    private function version(): int
    {
        return (int) $this->store->get(self::VERSION_KEY, 0);
    }

    /**
     * The case the key index cannot cover: an entry was cached but its key never made it
     * into the index (a swallowed index write, or a lock timeout losing the read-modify-write).
     * Forgetting by name cannot reach it, so the version must move instead.
     */
    public function test_a_payment_invalidates_an_entry_missing_from_the_key_index(): void
    {
        $store = $this->store;
        $store->put(self::VERSION_KEY, 7, 3600);
        $store->put(self::INDEX_KEY, [], 3600);

        // Cached under version 7, but absent from the index.
        $orphan = 'process_mess_bills_modal_summary_v7_buyer42';
        $store->put($orphan, ['due' => 5000.0], 3600);

        $this->forgetSummaries();

        $this->assertGreaterThan(
            7,
            $this->version(),
            'A payment must move the combined cache version so an entry the index never recorded cannot be read back.'
        );
    }

    /**
     * Indexed keys are still forgotten by name — the cheap path stays in place.
     */
    public function test_a_payment_forgets_the_indexed_entries_and_clears_the_index(): void
    {
        $store = $this->store;
        $store->put(self::VERSION_KEY, 3, 3600);

        $indexed = 'process_mess_bills_modal_summary_v3_buyer7';
        $store->put($indexed, ['due' => 1200.0], 3600);
        $store->put(self::INDEX_KEY, [$indexed], 3600);

        $this->forgetSummaries();

        $this->assertNull($store->get($indexed), 'An indexed summary entry must be forgotten by name.');
        $this->assertNull($store->get(self::INDEX_KEY), 'The key index must be cleared alongside its entries.');
    }

    /**
     * An empty index is the ordinary case on a cold process, not an error: the bump still runs.
     */
    public function test_the_version_moves_even_when_the_index_is_absent(): void
    {
        $this->store->put(self::VERSION_KEY, 11, 3600);

        $this->forgetSummaries();

        $this->assertGreaterThan(11, $this->version(), 'A missing index must not skip the version bump.');
    }
}
