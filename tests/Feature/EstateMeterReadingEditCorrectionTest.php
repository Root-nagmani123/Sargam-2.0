<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\EstateController;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #349 F-049, Rule A: correcting a saved month's reading through List Meter Reading → Edit keeps the
 * month's opening reading (the previous month's reading) and measures units from it. It used to replace
 * the opening reading with the mistaken saved one, so a downward correction billed 0 units.
 */
class EstateMeterReadingEditCorrectionTest extends TestCase
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

    public function test_a_downward_edit_correction_keeps_the_opening_reading_and_bills_from_it(): void
    {
        [$saved, $opening, $target] = $this->savedNewMonth(100);

        $this->actor()->postJson(route('admin.estate.update-meter-reading.store'), $this->payload($saved->pk, $opening + 60, $target, true))
            ->assertOk()->assertJson(['success' => true]);

        $row = DB::table('estate_month_reading_details')->where('pk', $saved->pk)->first();
        $this->assertSame($opening, (int) $row->last_month_elec_red, 'opening reading is still the previous month\'s');
        $this->assertSame($opening + 60, (int) $row->curr_month_elec_red);
        $this->assertSame(60, (int) $row->meter_one_consume_unit);
        $this->assertGreaterThan(0, (float) $row->electricty_charges);
        $this->assertEqualsWithDelta($this->tariff($saved->poss, 60), (float) $row->electricty_charges, 0.001, 'amount = the tariff for 60 units');
    }

    /** The Edit screen's Unit preview measures from the same opening reading. */
    public function test_the_edit_list_offers_the_opening_reading_as_the_unit_baseline(): void
    {
        [$saved, $opening, $target] = $this->savedNewMonth(100);

        $data = $this->actor()->getJson(route('admin.estate.update-meter-reading.list', [
            'bill_month' => $target->month, 'bill_year' => $target->year,
            'possession_pk' => $saved->poss, 'reading_pk' => $saved->pk,
        ]))->assertOk()->json('data');

        $this->assertCount(1, $data);
        $slot = $data[0]['m1'] ?? $data[0];
        $this->assertSame($opening, (int) $slot['baseline_min_reading']);
        $this->assertSame((string) ($opening + 100), (string) $slot['electric_meter_reading'], 'the reading being corrected is still shown');
    }

    /**
     * Saves a new month +$plus over a house's latest reading.
     *
     * @return array{0: object, 1: int, 2: Carbon} the new row (pk, poss), its opening reading, its month
     */
    private function savedNewMonth(int $plus): array
    {
        $candidates = DB::table('estate_month_reading_details as emrd')
            ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
            ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
            ->where(fn ($q) => $q->whereNull('epd.return_home_status')->orWhere('epd.return_home_status', 0))
            ->whereNotNull('emrd.curr_month_elec_red')->whereNotNull('emrd.bill_month')->whereNotNull('emrd.bill_year')
            // Single-meter house, so the Edit list answers one row with meter 1 only.
            ->whereRaw('COALESCE(NULLIF(emrd.meter_two, 0), NULLIF(ehm.meter_two, 0)) IS NULL')
            ->orderByDesc('emrd.pk')->limit(50)
            ->get(['emrd.pk', 'emrd.bill_month', 'emrd.bill_year', 'emrd.curr_month_elec_red', 'epd.pk as poss']);
        foreach ($candidates as $c) {
            $target = Carbon::parse('1 '.$c->bill_month.' '.$c->bill_year)->startOfMonth()->addMonthNoOverflow();
            $existing = DB::table('estate_month_reading_details')->where('estate_possession_details_pk', $c->poss)
                ->where('bill_month', $target->format('F'))->where('bill_year', $target->format('Y'))->exists();
            if ($existing) {
                continue;
            }
            $opening = (int) $c->curr_month_elec_red;
            $this->actor()->postJson(route('admin.estate.update-meter-reading.store'), $this->payload($c->pk, $opening + $plus, $target, false))
                ->assertOk();
            $saved = DB::table('estate_month_reading_details')->where('estate_possession_details_pk', $c->poss)
                ->where('bill_month', $target->format('F'))->where('bill_year', $target->format('Y'))
                ->first(['pk', 'estate_possession_details_pk as poss', 'last_month_elec_red', 'meter_one_consume_unit']);
            $this->assertSame($opening, (int) $saved->last_month_elec_red, 'precondition: the new month opens at the previous reading');
            $this->assertSame($plus, (int) $saved->meter_one_consume_unit, 'precondition: first save bills the rise');

            return [$saved, $opening, $target];
        }
        $this->markTestSkipped('no single-meter reading row whose next month is still unsaved');
    }

    private function payload(int $pk, int $reading, Carbon $target, bool $edit): array
    {
        $payload = [
            'readings' => [['pk' => $pk, 'meter_slot' => 1, 'selected' => 1, 'curr_month_elec_red' => (string) $reading]],
            'reading_bill_month' => $target->format('Y-m'),
            'reading_current_date' => $target->copy()->addDays(14)->toDateString(),
        ];
        if ($edit) {
            $payload['edit_reading_pk'] = $pk;
        }

        return $payload;
    }

    private function actor()
    {
        return $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin'])->withHeaders(['X-Requested-With' => 'XMLHttpRequest']);
    }

    /** The store's own tariff for this house's unit type (EstateController::calculateElectricChargeForUnits). */
    private function tariff(int $possessionPk, int $units): float
    {
        $unitTypeCol = Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk') ? 'eust.estate_unit_type_master_pk' : 'ehm.estate_unit_master_pk';
        $unitTypePk = DB::table('estate_possession_details as epd')
            ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
            ->leftJoin('estate_unit_sub_type_master as eust', 'ehm.estate_unit_sub_type_master_pk', '=', 'eust.pk')
            ->where('epd.pk', $possessionPk)->value($unitTypeCol);
        $method = new \ReflectionMethod(EstateController::class, 'calculateElectricChargeForUnits');
        $method->setAccessible(true);

        return (float) $method->invoke($this->app->make(EstateController::class), $unitTypePk !== null ? (int) $unitTypePk : null, $units);
    }
}
