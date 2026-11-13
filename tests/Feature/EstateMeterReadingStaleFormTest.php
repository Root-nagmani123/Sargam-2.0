<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #349 F-047: a second save from an Update Meter Reading form row that a first save has already
 * superseded was checked against the old month but written onto the new month's row (reading goes
 * backwards, opening reading overwritten, 0 units). The list cache key also ignored the estate data
 * version, so a reopened dialog kept offering the superseded row for the TTL.
 */
class EstateMeterReadingStaleFormTest extends TestCase
{
    use RollsBackAgainstAppDatabase {
        setUp as setUpRollback;
    }

    protected function setUp(): void
    {
        // The trait opens the rollback transaction; afterCommit (the cache version bump) must still run
        // inside it, as Laravel's own DatabaseTransactions trait arranges.
        $this->setUpRollback();
        $manager = $this->app->make('db.transactions');
        $manager->callbacksShouldIgnore($manager->getTransactions()->first());
    }

    public function test_a_second_save_from_a_superseded_row_is_refused(): void
    {
        [$row, $target] = $this->regularCandidate();
        $actor = $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin'])->withHeaders(['X-Requested-With' => 'XMLHttpRequest']);

        $actor->postJson(route('admin.estate.update-meter-reading.store'), $this->regularPayload($row, $target, 100))
            ->assertOk()->assertJson(['success' => true]);
        $saved = $this->targetRows('estate_month_reading_details', 'estate_possession_details_pk', $row->poss, $target);
        $this->assertCount(1, $saved, 'the first save created the new month row');

        $actor->postJson(route('admin.estate.update-meter-reading.store'), $this->regularPayload($row, $target, 50))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['readings.0.pk']);

        $this->assertEquals($saved, $this->targetRows('estate_month_reading_details', 'estate_possession_details_pk', $row->poss, $target), 'the new month row is unchanged');
    }

    public function test_the_list_stops_offering_a_saved_row_with_a_working_cache(): void
    {
        config(['cache.redis_backed_unified_store' => 'array']);
        [$row, $target, $listQuery] = $this->regularCandidate();
        $actor = $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin'])->withHeaders(['X-Requested-With' => 'XMLHttpRequest']);

        $this->assertContains((int) $row->pk, $this->listedPks($actor, $listQuery), 'precondition: the list offers the row');

        $actor->postJson(route('admin.estate.update-meter-reading.store'), $this->regularPayload($row, $target, 100))->assertOk();

        $this->assertNotContains((int) $row->pk, $this->listedPks($actor, $listQuery), 'a saved house is not offered again from cache');
    }

    public function test_other_flow_refuses_a_second_save_from_a_superseded_row(): void
    {
        $row = null;
        $candidates = DB::table('estate_month_reading_details_other as r')
            ->join('estate_possession_other as epo', 'epo.pk', '=', 'r.estate_possession_other_pk')
            ->where(fn ($q) => $q->whereNull('epo.return_home_status')->orWhere('epo.return_home_status', 0))
            ->whereNotNull('r.curr_month_elec_red')->whereNotNull('r.bill_month')->whereNotNull('r.bill_year')
            ->orderByDesc('r.pk')->limit(50)
            ->get(['r.pk', 'r.bill_month', 'r.bill_year', 'r.curr_month_elec_red', 'r.estate_possession_other_pk as poss']);
        foreach ($candidates as $c) {
            $target = $this->nextMonth($c);
            if ($target && $this->targetRows('estate_month_reading_details_other', 'estate_possession_other_pk', $c->poss, $target)->isEmpty()) {
                $row = $c;
                break;
            }
        }
        if (! $row) {
            $this->markTestSkipped('no Other reading row whose next month is still unsaved');
        }
        $payload = fn (int $plus) => [
            'readings' => [['pk' => $row->pk, 'meter_slot' => 1, 'selected' => 1, 'curr_month_elec_red' => (string) ((int) $row->curr_month_elec_red + $plus)]],
            'reading_bill_month' => $target->format('Y-m'),
            'reading_meter_reading_date' => $target->copy()->addDays(14)->toDateString(),
        ];
        $actor = $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin'])->withHeaders(['X-Requested-With' => 'XMLHttpRequest']);

        // This store answers success with a redirect, AJAX or not.
        $actor->postJson(route('admin.estate.update-meter-reading-of-other.store'), $payload(100))
            ->assertRedirect()->assertSessionHas('success', 'Meter readings updated successfully.');
        $saved = $this->targetRows('estate_month_reading_details_other', 'estate_possession_other_pk', $row->poss, $target);
        $this->assertCount(1, $saved, 'the first save created the new month row');

        $actor->postJson(route('admin.estate.update-meter-reading-of-other.store'), $payload(50))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['readings.0.pk']);

        $this->assertEquals($saved, $this->targetRows('estate_month_reading_details_other', 'estate_possession_other_pk', $row->poss, $target), 'the new month row is unchanged');
    }

    /**
     * A saved regular reading whose possession has nothing yet for the next month, and which the
     * next month's list (filtered to its house's location) offers.
     *
     * @return array{0: object, 1: Carbon, 2: array<string, mixed>}
     */
    private function regularCandidate(): array
    {
        $candidates = DB::table('estate_month_reading_details as emrd')
            ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
            ->join('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
            ->where(fn ($q) => $q->whereNull('epd.return_home_status')->orWhere('epd.return_home_status', 0))
            ->whereNotNull('emrd.curr_month_elec_red')->whereNotNull('emrd.bill_month')->whereNotNull('emrd.bill_year')
            ->orderByDesc('emrd.pk')->limit(50)
            ->get(['emrd.pk', 'emrd.bill_month', 'emrd.bill_year', 'emrd.curr_month_elec_red', 'epd.pk as poss',
                'ehm.estate_campus_master_pk as campus', 'ehm.estate_block_master_pk as block', 'ehm.estate_unit_sub_type_master_pk as sub']);
        $actor = $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin']);
        foreach ($candidates as $c) {
            $target = $this->nextMonth($c);
            if (! $target || $this->targetRows('estate_month_reading_details', 'estate_possession_details_pk', $c->poss, $target)->isNotEmpty()) {
                continue;
            }
            $query = ['bill_month' => $target->month, 'bill_year' => $target->year, 'campus_id' => $c->campus, 'block_id' => $c->block, 'unit_sub_type_id' => $c->sub];
            if (in_array((int) $c->pk, $this->listedPks($actor, $query), true)) {
                return [$c, $target, $query];
            }
        }
        $this->markTestSkipped('no regular reading row offered by the next month\'s list');
    }

    private function regularPayload(object $row, Carbon $target, int $plus): array
    {
        return [
            'readings' => [['pk' => $row->pk, 'meter_slot' => 1, 'selected' => 1, 'curr_month_elec_red' => (string) ((int) $row->curr_month_elec_red + $plus)]],
            'reading_bill_month' => $target->format('Y-m'),
            'reading_current_date' => $target->copy()->addDays(14)->toDateString(),
        ];
    }

    private function nextMonth(object $row): ?Carbon
    {
        try {
            return Carbon::parse('1 '.$row->bill_month.' '.$row->bill_year)->startOfMonth()->addMonthNoOverflow();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function targetRows(string $table, string $possessionCol, $poss, Carbon $target)
    {
        return DB::table($table)->where($possessionCol, $poss)
            ->where('bill_month', $target->format('F'))->where('bill_year', $target->format('Y'))
            ->orderBy('pk')->get();
    }

    /** @return int[] */
    private function listedPks($actor, array $query): array
    {
        return array_map(fn ($r) => (int) $r['pk'], (array) $actor->getJson(route('admin.estate.update-meter-reading.list', $query))->assertOk()->json('data'));
    }
}
