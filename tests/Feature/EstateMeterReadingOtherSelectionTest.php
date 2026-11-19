<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * F-017: Update Meter Reading (Other) with possessions from two blocks selected.
 * The form prefills campus / block / sub-type from the first possession; the list
 * must still return every selected possession.
 */
class EstateMeterReadingOtherSelectionTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    public function test_selection_across_blocks_ignores_the_prefilled_location(): void
    {
        $readings = DB::table('estate_month_reading_details_other as r')
            ->join('estate_possession_other as epo', 'epo.pk', '=', 'r.estate_possession_other_pk')
            ->join('estate_other_req as eor', 'eor.pk', '=', 'epo.estate_other_req_pk')
            ->where(fn ($q) => $q->whereNull('epo.return_home_status')->orWhere('epo.return_home_status', 0))
            ->whereNotNull('epo.estate_block_master_pk')
            ->orderByDesc('r.bill_year')->orderBy('epo.pk')
            ->get(['r.pk', 'r.bill_month', 'r.bill_year', 'epo.pk as poss', 'epo.estate_campus_master_pk as campus',
                'epo.estate_block_master_pk as block', 'epo.estate_unit_sub_type_master_pk as sub']);

        $pair = null;
        foreach ($readings->groupBy(fn ($r) => $r->bill_year.'|'.$r->bill_month) as $month) {
            $first = $month->first();
            $other = $month->first(fn ($r) => $r->block != $first->block);
            if ($other) {
                $pair = [$first, $other];
                break;
            }
        }
        if (! $pair) {
            $this->markTestSkipped('needs two Other possessions in different blocks with a reading in the same month');
        }
        [$a, $b] = $pair;

        $query = ['bill_month' => $a->bill_month, 'bill_year' => $a->bill_year, 'possession_pks' => $a->poss.','.$b->poss];
        $user = $this->staffWithRole('Estate Admin');

        $baseline = $this->possessionsIn($this->as($user, ['Estate Admin'])
            ->getJson(route('admin.estate.update-meter-reading-of-other.list', $query))->assertOk()->json('data'));
        if (count($baseline) < 2) {
            $this->markTestSkipped('the bill-period rules exclude this pair even without location filters');
        }

        $prefilled = $query + ['campus_id' => $a->campus, 'block_id' => $a->block, 'unit_sub_type_id' => $a->sub];
        $got = $this->possessionsIn($this->as($user, ['Estate Admin'])
            ->getJson(route('admin.estate.update-meter-reading-of-other.list', $prefilled))->assertOk()->json('data'));

        $this->assertEqualsCanonicalizing([(int) $a->poss, (int) $b->poss], $got, 'both selected possessions are listed');
    }

    /** @return int[] distinct possession pks behind the listed reading rows */
    private function possessionsIn(array $rows): array
    {
        $readingPks = array_map(fn ($r) => (int) $r['pk'], $rows);

        return DB::table('estate_month_reading_details_other')->whereIn('pk', $readingPks ?: [0])
            ->distinct()->pluck('estate_possession_other_pk')->map(fn ($p) => (int) $p)->values()->all();
    }
}
