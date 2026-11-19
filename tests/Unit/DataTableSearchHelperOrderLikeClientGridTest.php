<?php

namespace Tests\Unit;

use App\Support\DataTableSearchHelper;
use PHPUnit\Framework\TestCase;

/** PR #349 F-030: a client grid's export reorders its own rows by the keys the page sends. */
class DataTableSearchHelperOrderLikeClientGridTest extends TestCase
{
    private function keys($rowOrder): array
    {
        $rows = collect([['pk' => 1], ['pk' => 2], ['pk' => 3], ['pk' => 4]]);

        return DataTableSearchHelper::orderLikeClientGrid($rows, $rowOrder, fn ($r) => $r['pk'])->pluck('pk')->all();
    }

    public function test_listed_rows_follow_the_list_and_the_rest_keep_their_order_after(): void
    {
        $this->assertSame([3, 1, 2, 4], $this->keys('3,1'));
        $this->assertSame([4, 3, 2, 1], $this->keys('4,3,2,1'));
    }

    public function test_junk_duplicates_and_unknown_keys_are_ignored(): void
    {
        // 'x', '-1', '1.5' are not keys; the second 3 is a duplicate; 99 names no row.
        $this->assertSame([2, 3, 1, 4], $this->keys('x,2,-1,3,1.5,3,99,1'));
    }

    public function test_no_or_non_string_order_keeps_the_rows_as_they_are(): void
    {
        $this->assertSame([1, 2, 3, 4], $this->keys(null));
        $this->assertSame([1, 2, 3, 4], $this->keys(''));
        $this->assertSame([1, 2, 3, 4], $this->keys(['3', '1']));
    }
}
