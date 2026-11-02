<?php

namespace Tests\Unit;

use App\Http\Controllers\Mess\ProcessMessBillsEmployeeController;
use App\Support\MessBuyerClientFilter;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Pins the indexed buyer matcher used by payment allocation to the per-bill rule it replaced:
 * the slug must match; with a client_id a bill matches on that id or any name variant; without
 * one, on the exact trimmed name. A drift here allocates a payment to the wrong buyer's bills.
 *
 * Database-free: the name-variant and client-id memos are primed so no lookup reaches MySQL.
 *
 * Run with:  php vendor/bin/phpunit --filter=MessBuyerBillMatching
 */
class MessBuyerBillMatchingTest extends TestCase
{
    protected function tearDown(): void
    {
        MessBuyerClientFilter::flushResolvedClientIdCache();
        parent::tearDown();
    }

    private function prime(string $property, array $entries): void
    {
        $prop = new ReflectionProperty(MessBuyerClientFilter::class, $property);
        $prop->setAccessible(true);
        $prop->setValue(null, $entries);
    }

    /**
     * @return list<int>
     */
    private function match(array $bills, array $entry): array
    {
        $controller = new ProcessMessBillsEmployeeController;
        $index = new ReflectionMethod($controller, 'indexBillsForBuyerMatching');
        $index->setAccessible(true);
        $match = new ReflectionMethod($controller, 'billsMatchingBuyerEntry');
        $match->setAccessible(true);

        $collection = collect($bills);

        return $match->invoke($controller, $collection, $index->invoke($controller, $collection), $entry)
            ->pluck('ref')->all();
    }

    public function test_matches_the_per_bill_rule(): void
    {
        $bill = fn (int $ref, string $slug, int $clientId, string $name) => (object) [
            'ref' => $ref, 'client_type_slug' => $slug, 'client_id' => $clientId, 'client_name' => $name,
        ];
        $bills = [
            $bill(0, 'employee', 5, 'Asha Rao'),
            $bill(1, 'employee', 0, 'Asha Rao (EMP)'),  // no id, name variant
            $bill(2, 'employee', 9, 'Asha Rao'),        // other id, name variant: still matches
            $bill(3, 'ot', 5, 'Asha Rao'),              // same id, other slug
            $bill(4, 'employee', 0, ' Ravi '),          // trimmed before comparing
            $bill(5, 'employee', 0, 'Ravi K'),
            $bill(6, 'employee', 5, 'Someone Else'),    // same id, unrelated name
        ];

        $this->prime('nameVariantsCache', ['Asha Rao|5|0' => ['Asha Rao', 'Asha Rao (EMP)']]);
        $this->prime('resolvedClientIdCache', [
            'Asha Rao (EMP)|employee' => null,
            'Ravi|employee' => null,
            'Ravi K|employee' => null,
        ]);

        $this->assertSame([0, 1, 2, 6], $this->match($bills, ['name' => 'Asha Rao', 'slug' => 'employee', 'client_id' => 5]));
        $this->assertSame([3], $this->match($bills, ['name' => 'Asha Rao', 'slug' => 'ot', 'client_id' => 5]));
        $this->assertSame([4], $this->match($bills, ['name' => 'Ravi', 'slug' => 'employee', 'client_id' => 0]));
        $this->assertSame([], $this->match($bills, ['name' => 'Nobody', 'slug' => 'employee', 'client_id' => 0]));
    }
}
