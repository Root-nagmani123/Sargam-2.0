<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #349 F-033: the Bill Month select lists the newest 24 calendar periods, newest first,
 * although bill_month is stored as a month name ('February'), which SQL cannot order.
 */
class EstateBillMonthOptionsTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    public function test_generate_estate_bill_lists_the_newest_24_periods(): void
    {
        $this->seedPeriods('estate_month_reading_details', 'estate_possession_details_pk', 'estate_possession_details');
        $this->assertNewest24('admin.estate.generate-estate-bill');
    }

    public function test_bill_for_other_lists_the_newest_24_periods(): void
    {
        $this->seedPeriods('estate_month_reading_details_other', 'estate_possession_other_pk', 'estate_possession_other');
        $this->assertNewest24('admin.estate.generate-estate-bill-for-other');
    }

    private function assertNewest24(string $route): void
    {
        $options = $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin'])
            ->get(route($route))->assertOk()->viewData('billMonthOptions');

        $expected = [];
        for ($i = 0; $i < 24; $i++) {
            $d = (new \DateTimeImmutable('2092-06-01'))->modify("-{$i} months");
            $expected[$d->format('Y-m')] = $d->format('F Y');
        }
        $this->assertSame($expected, $options, 'Jun 2092 down to Jul 2090; the 24-period cut lands mid-year');
    }

    /** 30 periods, Jan 2090 to Jun 2092, stored as month names, inserted in shuffled order. */
    private function seedPeriods(string $table, string $fkColumn, string $parent): void
    {
        $parentPk = DB::table($parent)->min('pk');
        if (! $parentPk) {
            $this->markTestSkipped("needs a row in {$parent}");
        }
        $rows = [];
        foreach ([2090, 2091, 2092] as $year) {
            for ($m = 1; $m <= ($year === 2092 ? 6 : 12); $m++) {
                $rows[] = [
                    $fkColumn => $parentPk, 'from_date' => "{$year}-01-01", 'to_date' => "{$year}-01-31",
                    'last_month_elec_red' => 0, 'curr_month_elec_red' => 0,
                    'bill_year' => (string) $year, 'bill_month' => date('F', mktime(0, 0, 0, $m, 1, $year)),
                ];
            }
        }
        shuffle($rows);
        DB::table($table)->insert($rows);
    }
}
