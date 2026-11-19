<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\EstateController;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #349 F-049, Rule A on Update Meter Reading of Other (decision 6814733034): saving a month's own reading
 * again keeps the month's opening reading and bills from it. It used to replace the opening with the saved
 * reading and bill only the rise, dropping units already billed.
 */
class EstateMeterReadingOtherResaveTest extends TestCase
{
    use RollsBackAgainstAppDatabase {
        setUp as setUpRollback;
    }

    protected function setUp(): void
    {
        $this->setUpRollback();
        $manager = $this->app->make('db.transactions');
        $manager->callbacksShouldIgnore($manager->getTransactions()->first());
        config(['cache.redis_backed_unified_store' => 'array']);
    }

    public function test_an_upward_resave_keeps_the_opening_reading_and_bills_from_it(): void
    {
        [$saved, $opening, $target] = $this->savedNewMonth(false);

        $this->actor()->postJson(route('admin.estate.update-meter-reading-of-other.store'), $this->payload([[$saved->pk, 1, $opening + 160]], $target))
            ->assertRedirect()->assertSessionHas('success'); // this store answers success with a redirect

        $row = DB::table('estate_month_reading_details_other')->where('pk', $saved->pk)->first();
        $this->assertSame($opening, (int) $row->last_month_elec_red, 'opening reading is still the previous month\'s');
        $this->assertSame($opening + 160, (int) $row->curr_month_elec_red);
        $this->assertSame(160, (int) $row->per_unit, 'the 100 units already billed are not dropped');
        $this->assertEqualsWithDelta($this->tariff((int) $saved->poss, 160), (float) $row->meter_one_elec_charge, 0.001);
    }

    /** The dialog listing a month's own row offers its opening reading for the Unit preview. */
    public function test_the_dialog_offers_the_opening_reading_for_a_months_own_row(): void
    {
        [$saved, $opening, $target] = $this->savedNewMonth(false);

        $data = collect($this->actor()->getJson(route('admin.estate.update-meter-reading-of-other.list', [
            'bill_month' => $target->month, 'bill_year' => $target->year, 'possession_pks' => (string) $saved->poss,
        ]))->assertOk()->json('data'));

        $own = $data->firstWhere('pk', $saved->pk);
        $this->assertNotNull($own, 'the month\'s own row is listed');
        $this->assertSame($opening, $own['opening_reading'] ?? null);
        $this->assertSame($opening + 100, (int) $own['last_month_reading'], 'the saved reading is still shown');
    }

    /**
     * Guard: a dual-meter house starting a new month posts its second meter onto the row the first line just
     * inserted. That row is not the month's "own" row — its second meter must still open at last month's reading.
     */
    public function test_a_dual_meter_new_month_opens_both_meters_at_last_months_readings(): void
    {
        [$saved, $opening, $target, $opening2] = $this->savedNewMonth(true);

        $this->assertSame($opening, (int) $saved->last_month_elec_red);
        $this->assertSame($opening2, (int) $saved->last_month_elec_red2, 'meter 2 opens at last month\'s meter 2 reading');
        $this->assertSame($opening2 + 50, (int) $saved->curr_month_elec_red2);
        $this->assertEqualsWithDelta($this->tariff((int) $saved->poss, 50), (float) $saved->meter_two_elec_charge, 0.001, 'meter 2 bills 50 units');
    }

    /**
     * Saves the next month for an Other house: meter 1 at +100 (and meter 2 at +50 when $dual).
     *
     * @return array{0: object, 1: int, 2: Carbon, 3: int|null} the new row, meter 1 opening, month, meter 2 opening
     */
    private function savedNewMonth(bool $dual): array
    {
        $meterTwo = 'COALESCE(NULLIF(r.meter_two, 0), NULLIF(ehm.meter_two, 0))';
        $candidates = DB::table('estate_month_reading_details_other as r')
            ->join('estate_possession_other as epo', 'epo.pk', '=', 'r.estate_possession_other_pk')
            ->leftJoin('estate_house_master as ehm', 'epo.estate_house_master_pk', '=', 'ehm.pk')
            ->where(fn ($q) => $q->whereNull('epo.return_home_status')->orWhere('epo.return_home_status', 0))
            ->whereNotNull('r.curr_month_elec_red')->whereNotNull('r.bill_month')->whereNotNull('r.bill_year')
            ->when($dual, fn ($q) => $q->whereRaw("$meterTwo IS NOT NULL")->whereNotNull('r.curr_month_elec_red2'), fn ($q) => $q->whereRaw("$meterTwo IS NULL"))
            ->orderByDesc('r.pk')->limit(50)
            ->get(['r.pk', 'r.bill_month', 'r.bill_year', 'r.curr_month_elec_red', 'r.curr_month_elec_red2', 'r.estate_possession_other_pk as poss']);
        foreach ($candidates as $c) {
            try {
                $target = Carbon::parse('1 '.$c->bill_month.' '.$c->bill_year)->startOfMonth()->addMonthNoOverflow();
            } catch (\Throwable $e) {
                continue;
            }
            if ($this->monthRow($c->poss, $target) !== null) {
                continue;
            }
            $opening = (int) $c->curr_month_elec_red;
            $opening2 = $dual ? (int) $c->curr_month_elec_red2 : null;
            $lines = [[$c->pk, 1, $opening + 100]];
            if ($dual) {
                $lines[] = [$c->pk, 2, $opening2 + 50];
            }
            $this->actor()->postJson(route('admin.estate.update-meter-reading-of-other.store'), $this->payload($lines, $target))
                ->assertRedirect()->assertSessionHas('success');
            $saved = $this->monthRow($c->poss, $target);
            $this->assertNotNull($saved, 'precondition: the new month row exists');
            $this->assertSame($opening, (int) $saved->last_month_elec_red, 'precondition: the new month opens at the previous reading');
            // per_unit holds the last line's units, so only the meter 1 charge is comparable on a dual save.
            $this->assertEqualsWithDelta($this->tariff((int) $c->poss, 100), (float) $saved->meter_one_elec_charge, 0.001, 'precondition: first save bills the rise');

            return [$saved, $opening, $target, $opening2];
        }
        $this->markTestSkipped('no '.($dual ? 'dual' : 'single').'-meter Other reading whose next month is still unsaved');
    }

    private function monthRow($poss, Carbon $target): ?object
    {
        return DB::table('estate_month_reading_details_other')->where('estate_possession_other_pk', $poss)
            ->where('bill_month', $target->format('F'))->where('bill_year', $target->format('Y'))
            ->first(['pk', 'estate_possession_other_pk as poss', 'last_month_elec_red', 'curr_month_elec_red', 'last_month_elec_red2', 'curr_month_elec_red2', 'per_unit', 'meter_one_elec_charge', 'meter_two_elec_charge']);
    }

    /** @param  array<int, array{0: int, 1: int, 2: int}>  $lines  [pk, meter slot, reading] */
    private function payload(array $lines, Carbon $target): array
    {
        return [
            'readings' => array_map(fn ($l) => ['pk' => $l[0], 'meter_slot' => $l[1], 'selected' => 1, 'curr_month_elec_red' => (string) $l[2]], $lines),
            'reading_bill_month' => $target->format('Y-m'),
            'reading_meter_reading_date' => $target->copy()->addDays(14)->toDateString(),
        ];
    }

    private function actor()
    {
        return $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin'])->withHeaders(['X-Requested-With' => 'XMLHttpRequest']);
    }

    /** The store's own tariff for this Other possession (EstateController::calculateElectricChargeForUnits). */
    private function tariff(int $possessionPk, int $units): float
    {
        $houseType = Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk') ? 'eust.estate_unit_type_master_pk' : 'ehm.estate_unit_master_pk';
        $unitTypePk = DB::table('estate_possession_other as epo')
            ->leftJoin('estate_house_master as ehm', 'epo.estate_house_master_pk', '=', 'ehm.pk')
            ->leftJoin('estate_unit_sub_type_master as eust', 'ehm.estate_unit_sub_type_master_pk', '=', 'eust.pk')
            ->where('epo.pk', $possessionPk)
            ->value(DB::raw("COALESCE(epo.estate_unit_type_master_pk, $houseType)"));
        $method = new \ReflectionMethod(EstateController::class, 'calculateElectricChargeForUnits');
        $method->setAccessible(true);

        return (float) $method->invoke($this->app->make(EstateController::class), $unitTypePk !== null && (int) $unitTypePk > 0 ? (int) $unitTypePk : null, $units);
    }
}
