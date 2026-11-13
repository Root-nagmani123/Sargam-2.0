<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\EstateController;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #349 round 13 follow-ups to F-049 Rule A (every save rolled back):
 *
 *  - F-051: once a month records a meter replacement, every later save of that month bills the new meter's
 *    reading (the new meter starts at 0). The month used to be measured from the old meter's opening reading
 *    on the next save, so it billed 0 units.
 *  - F-053: List Meter Reading → Edit corrects the edited row's own month only. Posted under another, unsaved
 *    month it used to create that month below its opening reading at 0 units, reported as success.
 *  - F-054: for a month's own row the screen's minimum is the store's minimum (the saved reading); the
 *    opening reading is offered separately, for the Unit preview only.
 *  - F-052, decision 6845841302 (Option A): a month cannot be corrected while a later month is saved. It used to
 *    be corrected alone, leaving the later month's opening stale and moving the possession's reading back.
 */
class EstateMeterReadingFollowUpSaveTest extends TestCase
{
    use RollsBackAgainstAppDatabase {
        setUp as setUpRollback;
    }

    /** A meter number no fixture row carries. */
    private const NEW_METER = '987654321';

    protected function setUp(): void
    {
        $this->setUpRollback();
        $manager = $this->app->make('db.transactions');
        $manager->callbacksShouldIgnore($manager->getTransactions()->first());
        config(['cache.redis_backed_unified_store' => 'array']);
    }

    /** F-051, the review's closure case: Edit records a new meter at 30 (30 units); a dialog re-save at 40 bills 40, not 0. */
    public function test_after_a_meter_replacement_a_dialog_resave_bills_the_new_meters_reading(): void
    {
        $m = $this->replacedMonth(30);

        $this->regular($m['pk'], 40, $m['target'], self::NEW_METER, false)->assertOk()->assertJson(['success' => true]);

        $row = $this->regularRow($m['pk']);
        $this->assertSame(40, (int) $row->curr_month_elec_red);
        $this->assertSame(40, (int) $row->meter_one_consume_unit, 'the new meter started at 0');
        $this->assertEqualsWithDelta($this->tariff($m['poss'], 40), (float) $row->electricty_charges, 0.001);
        $this->assertSame(self::NEW_METER, (string) $row->meter_one);
    }

    /** F-051: an Edit correction of the replaced month keeps billing from the new meter's start. */
    public function test_after_a_meter_replacement_an_edit_correction_keeps_billing_the_new_meter(): void
    {
        $m = $this->replacedMonth(30);

        $this->regular($m['pk'], 35, $m['target'], self::NEW_METER, true)->assertOk()->assertJson(['success' => true]);

        $row = $this->regularRow($m['pk']);
        $this->assertSame(35, (int) $row->meter_one_consume_unit);
        $this->assertEqualsWithDelta($this->tariff($m['poss'], 35), (float) $row->electricty_charges, 0.001);
    }

    /** F-051 guard: undoing a mistaken replacement (Edit back to the month's own meter) bills from the opening again. */
    public function test_an_edit_back_to_the_months_original_meter_bills_from_the_opening_reading(): void
    {
        $m = $this->replacedMonth(30);

        $this->regular($m['pk'], $m['opening'] + 60, $m['target'], $m['meter'], true)->assertOk()->assertJson(['success' => true]);

        $row = $this->regularRow($m['pk']);
        $this->assertSame($m['meter'], (string) $row->meter_one);
        $this->assertSame(60, (int) $row->meter_one_consume_unit, 'no replacement in this month any more');
    }

    /** F-051: the dialog's row for the replaced month carries the number the month opened with, so the preview bills the reading. */
    public function test_the_dialog_offers_the_months_opening_meter_number_after_a_replacement(): void
    {
        $m = $this->replacedMonth(30);

        $own = collect($this->actor()->getJson(route('admin.estate.update-meter-reading.list', [
            'bill_month' => $m['target']->format('F'), 'bill_year' => $m['target']->year, 'possession_pk' => $m['poss'],
        ]))->assertOk()->json('data'))->firstWhere('pk', $m['pk']);

        $this->assertNotNull($own, 'the month\'s own row is listed');
        $slot = $own['m1'] ?? $own;
        $this->assertSame(self::NEW_METER, (string) $slot['new_meter_no']);
        $this->assertSame($m['meter'], (string) ($slot['opening_meter_no'] ?? ''), 'the meter the month opened with');
    }

    /** F-051 in Update Meter Reading of Other: a re-save after the replacement bills the new meter's reading, not 0. */
    public function test_other_after_a_meter_replacement_a_resave_bills_the_new_meters_reading(): void
    {
        $m = $this->savedOtherMonth();

        $this->other($m['pk'], 30, $m['target'], self::NEW_METER)->assertRedirect()->assertSessionHas('success');
        $row = $this->otherRow($m['pk']);
        $this->assertSame(self::NEW_METER, (string) $row->meter_one, 'precondition: the replacement is recorded');
        $this->assertSame(30, (int) $row->per_unit, 'precondition: the replacing save bills the new meter\'s reading');

        $this->other($m['pk'], 40, $m['target'], self::NEW_METER)->assertRedirect()->assertSessionHas('success');

        $row = $this->otherRow($m['pk']);
        $this->assertSame(40, (int) $row->curr_month_elec_red);
        $this->assertSame(40, (int) $row->per_unit, 'the new meter started at 0');
        $this->assertEqualsWithDelta($this->otherTariff($m['poss'], 40), (float) $row->meter_one_elec_charge, 0.001);
    }

    /** F-053, the review's closure case: Edit a saved month, change the month to an unsaved later one, enter a lower reading. */
    public function test_an_edit_posted_under_another_unsaved_month_is_refused_and_creates_no_row(): void
    {
        $m = $this->savedRegularMonth();
        $next = $m['target']->copy()->addMonthNoOverflow();
        if ($this->regularMonthPk($m['poss'], $next) !== null) {
            $this->markTestSkipped('the month after the saved one already exists');
        }

        $this->regular($m['pk'], $m['opening'] + 60, $next, $m['meter'], true)
            ->assertStatus(422)->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['readings.0.pk']);

        $this->assertNull($this->regularMonthPk($m['poss'], $next), 'no row for the later month');
        $row = $this->regularRow($m['pk']);
        $this->assertSame($m['opening'] + 100, (int) $row->curr_month_elec_red, 'the edited month is untouched');
    }

    /** F-053: the Edit list reloaded under another month neither offers the opening reading nor hides the row's own month. */
    public function test_the_edit_list_under_another_month_offers_the_saved_reading_and_names_the_rows_month(): void
    {
        $m = $this->savedRegularMonth();
        $next = $m['target']->copy()->addMonthNoOverflow();

        $data = $this->actor()->getJson(route('admin.estate.update-meter-reading.list', [
            'bill_month' => $next->format('F'), 'bill_year' => $next->year,
            'possession_pk' => $m['poss'], 'reading_pk' => $m['pk'],
        ]))->assertOk()->json('data');

        $this->assertCount(1, $data);
        $slot = $data[0]['m1'] ?? $data[0];
        $this->assertSame($m['opening'] + 100, (int) ($slot['opening_reading'] ?? -1), 'not the edited month\'s opening');
        $this->assertSame($m['target']->format('Y-m'), $data[0]['bill_ym'] ?? null);
    }

    /** F-054: the dialog's own-month minimum is the server's — one below it is refused, the minimum itself is saved. */
    public function test_own_month_client_minimum_equals_the_server_minimum(): void
    {
        $m = $this->savedRegularMonth();
        $saved = $m['opening'] + 100;

        $own = collect($this->actor()->getJson(route('admin.estate.update-meter-reading.list', [
            'bill_month' => $m['target']->format('F'), 'bill_year' => $m['target']->year, 'possession_pk' => $m['poss'],
        ]))->assertOk()->json('data'))->firstWhere('pk', $m['pk']);
        $slot = $own['m1'] ?? $own;
        $this->assertSame($saved, (int) $slot['baseline_min_reading'], 'client minimum = the saved reading');
        $this->assertSame($m['opening'], (int) $slot['opening_reading'], 'the preview still measures from the opening');

        $this->regular($m['pk'], $saved - 1, $m['target'], $m['meter'], false)->assertStatus(422);
        $this->regular($m['pk'], $saved, $m['target'], $m['meter'], false)->assertOk()->assertJson(['success' => true]);
    }

    /** F-054 on Update Meter Reading of Other: the reading the screen checks against is the server's minimum. */
    public function test_other_own_month_client_minimum_equals_the_server_minimum(): void
    {
        $m = $this->savedOtherMonth();
        $saved = $m['opening'] + 100;

        $own = collect($this->actor()->getJson(route('admin.estate.update-meter-reading-of-other.list', [
            'bill_month' => $m['target']->month, 'bill_year' => $m['target']->year, 'possession_pks' => (string) $m['poss'],
        ]))->assertOk()->json('data'))->firstWhere('pk', $m['pk']);
        $this->assertSame($saved, (int) $own['last_month_reading'], 'the screen\'s minimum (data-last-reading)');
        $this->assertSame($m['opening'], $own['opening_reading'], 'the preview still measures from the opening');

        $this->other($m['pk'], $saved - 1, $m['target'], $m['meter'])->assertStatus(422);
        $this->other($m['pk'], $saved, $m['target'], $m['meter'])->assertRedirect()->assertSessionHas('success');
    }

    /**
     * F-052, decision 6845841302 (Option A): once December is saved, November cannot be corrected — not through Edit,
     * not through the dialog, and not once December's bill is issued. December and the possession's reading stay as they were.
     */
    public function test_correcting_a_month_after_the_next_month_is_saved_is_refused(): void
    {
        $m = $this->savedRegularMonth();
        $dec = $this->savedRegularNextMonth($m);

        $this->regular($m['pk'], $m['opening'] + 60, $m['target'], $m['meter'], true)
            ->assertStatus(422)->assertJsonValidationErrors(['readings.0.pk']);
        $this->regular($m['pk'], $m['opening'] + 150, $m['target'], $m['meter'], false)
            ->assertStatus(422)->assertJsonValidationErrors(['readings.0.pk']);
        DB::table('estate_month_reading_details')->where('pk', $dec)->update(['notify_employee_status' => 1]);
        $this->regular($m['pk'], $m['opening'] + 60, $m['target'], $m['meter'], true)
            ->assertStatus(422)->assertJsonValidationErrors(['readings.0.pk']);

        $nov = $this->regularRow($m['pk']);
        $this->assertSame($m['opening'] + 100, (int) $nov->curr_month_elec_red, 'November is not corrected');
        $this->assertSame(100, (int) $nov->meter_one_consume_unit);
        $decRow = $this->regularRow($dec);
        $this->assertSame($m['opening'] + 100, (int) $decRow->last_month_elec_red, 'December still opens at November\'s saved reading');
        $this->assertSame(100, (int) $decRow->meter_one_consume_unit);
        $this->assertSame($m['opening'] + 200, (int) DB::table('estate_possession_details')->where('pk', $m['poss'])->value('electric_meter_reading'),
            'the possession keeps December\'s reading');
    }

    /** F-052 in Update Meter Reading of Other: the same refusal; December and the possession's reading unchanged. */
    public function test_other_correcting_a_month_after_the_next_month_is_saved_is_refused(): void
    {
        $m = $this->savedOtherMonth();
        $next = $m['target']->copy()->addMonthNoOverflow();
        if ($this->otherMonthPk($m['poss'], $next) !== null) {
            $this->markTestSkipped('the month after the saved one already exists');
        }
        $this->other($m['pk'], $m['opening'] + 200, $next, $m['meter'])->assertRedirect()->assertSessionHas('success');
        $dec = $this->otherMonthPk($m['poss'], $next);
        $this->assertNotNull($dec, 'precondition: December is saved');

        $this->other($m['pk'], $m['opening'] + 150, $m['target'], $m['meter'])
            ->assertStatus(422)->assertJsonValidationErrors(['readings.0.pk']);

        $this->assertSame($m['opening'] + 100, (int) $this->otherRow($m['pk'])->curr_month_elec_red, 'November is not corrected');
        $decRow = $this->otherRow($dec);
        $this->assertSame($m['opening'] + 100, (int) $decRow->last_month_elec_red, 'December still opens at November\'s saved reading');
        $this->assertSame(100, (int) $decRow->per_unit);
        $this->assertSame($m['opening'] + 200, (int) DB::table('estate_possession_other')->where('pk', $m['poss'])->value('meter_reading_oth'),
            'the possession keeps December\'s reading');
    }

    /** F-052: both lists tell the screen which later month blocks a save of the month's own row. */
    public function test_the_lists_name_the_later_month_that_blocks_a_correction(): void
    {
        $m = $this->savedRegularMonth();
        $this->savedRegularNextMonth($m);
        $data = $this->actor()->getJson(route('admin.estate.update-meter-reading.list', [
            'bill_month' => $m['target']->format('F'), 'bill_year' => $m['target']->year,
            'possession_pk' => $m['poss'], 'reading_pk' => $m['pk'],
        ]))->assertOk()->json('data');
        $this->assertSame($m['target']->copy()->addMonthNoOverflow()->format('Y-m'), $data[0]['later_month_ym'] ?? null);

        $o = $this->savedOtherMonth();
        $next = $o['target']->copy()->addMonthNoOverflow();
        $this->other($o['pk'], $o['opening'] + 200, $next, $o['meter'])->assertRedirect()->assertSessionHas('success');
        $own = collect($this->actor()->getJson(route('admin.estate.update-meter-reading-of-other.list', [
            'bill_month' => $o['target']->month, 'bill_year' => $o['target']->year, 'possession_pks' => (string) $o['poss'],
        ]))->assertOk()->json('data'))->firstWhere('pk', $o['pk']);
        $this->assertNotNull($own, 'the month\'s own row is listed');
        $this->assertSame($next->format('Y-m'), $own['later_month_ym'] ?? null);
    }

    /** Saves the month after $m from $m's row (+100 over it), as the dialog does; returns the new row's pk. */
    private function savedRegularNextMonth(array $m): int
    {
        $next = $m['target']->copy()->addMonthNoOverflow();
        if ($this->regularMonthPk($m['poss'], $next) !== null) {
            $this->markTestSkipped('the month after the saved one already exists');
        }
        $this->regular($m['pk'], $m['opening'] + 200, $next, $m['meter'], false)->assertOk()->assertJson(['success' => true]);
        $pk = $this->regularMonthPk($m['poss'], $next);
        $this->assertNotNull($pk, 'precondition: the next month is saved');
        $this->assertSame(100, (int) $this->regularRow($pk)->meter_one_consume_unit, 'precondition: the next month bills 100 units');

        return $pk;
    }

    /**
     * November saved +100 over October, then List Meter Reading → Edit records NEW_METER at $reading.
     *
     * @return array{pk: int, poss: int, opening: int, target: Carbon, meter: string}
     */
    private function replacedMonth(int $reading): array
    {
        $m = $this->savedRegularMonth();
        $this->regular($m['pk'], $reading, $m['target'], self::NEW_METER, true)->assertOk()->assertJson(['success' => true]);
        $row = $this->regularRow($m['pk']);
        $this->assertSame(self::NEW_METER, (string) $row->meter_one, 'precondition: the replacement is recorded');
        $this->assertSame($reading, (int) $row->meter_one_consume_unit, 'precondition: the replacing save bills the new meter\'s reading');

        return $m;
    }

    /**
     * Saves the next month +100 over a single-meter house's latest reading, posting its meter number as the dialog does.
     *
     * @return array{pk: int, poss: int, opening: int, target: Carbon, meter: string}
     */
    private function savedRegularMonth(): array
    {
        $candidates = DB::table('estate_month_reading_details as emrd')
            ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
            ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
            ->where(fn ($q) => $q->whereNull('epd.return_home_status')->orWhere('epd.return_home_status', 0))
            ->whereNotNull('emrd.curr_month_elec_red')->whereNotNull('emrd.bill_month')->whereNotNull('emrd.bill_year')
            ->where('emrd.meter_one', '>', 0)->where('emrd.meter_one', '<>', (int) self::NEW_METER)
            ->whereRaw('COALESCE(NULLIF(emrd.meter_two, 0), NULLIF(ehm.meter_two, 0)) IS NULL')
            ->orderByDesc('emrd.pk')->limit(50)
            ->get(['emrd.pk', 'emrd.bill_month', 'emrd.bill_year', 'emrd.curr_month_elec_red', 'emrd.meter_one', 'epd.pk as poss']);
        foreach ($candidates as $c) {
            $target = Carbon::parse('1 '.$c->bill_month.' '.$c->bill_year)->startOfMonth()->addMonthNoOverflow();
            if ($this->regularMonthPk($c->poss, $target) !== null) {
                continue;
            }
            $opening = (int) $c->curr_month_elec_red;
            $meter = (string) $c->meter_one;
            $this->regular($c->pk, $opening + 100, $target, $meter, false)->assertOk();
            $pk = $this->regularMonthPk($c->poss, $target);
            $this->assertNotNull($pk, 'precondition: the new month row exists');
            $row = $this->regularRow($pk);
            $this->assertSame($opening, (int) $row->last_month_elec_red, 'precondition: the new month opens at the previous reading');
            $this->assertSame(100, (int) $row->meter_one_consume_unit, 'precondition: first save bills the rise');

            return ['pk' => $pk, 'poss' => (int) $c->poss, 'opening' => $opening, 'target' => $target, 'meter' => $meter];
        }
        $this->markTestSkipped('no single-meter reading row whose next month is still unsaved');
    }

    /**
     * Saves the next month +100 for a single-meter Other house.
     *
     * @return array{pk: int, poss: int, opening: int, target: Carbon, meter: string}
     */
    private function savedOtherMonth(): array
    {
        $candidates = DB::table('estate_month_reading_details_other as r')
            ->join('estate_possession_other as epo', 'epo.pk', '=', 'r.estate_possession_other_pk')
            ->leftJoin('estate_house_master as ehm', 'epo.estate_house_master_pk', '=', 'ehm.pk')
            ->where(fn ($q) => $q->whereNull('epo.return_home_status')->orWhere('epo.return_home_status', 0))
            ->whereNotNull('r.curr_month_elec_red')->whereNotNull('r.bill_month')->whereNotNull('r.bill_year')
            ->where('r.meter_one', '>', 0)->where('r.meter_one', '<>', (int) self::NEW_METER)
            ->whereRaw('COALESCE(NULLIF(r.meter_two, 0), NULLIF(ehm.meter_two, 0)) IS NULL')
            ->orderByDesc('r.pk')->limit(50)
            ->get(['r.pk', 'r.bill_month', 'r.bill_year', 'r.curr_month_elec_red', 'r.meter_one', 'r.estate_possession_other_pk as poss']);
        foreach ($candidates as $c) {
            try {
                $target = Carbon::parse('1 '.$c->bill_month.' '.$c->bill_year)->startOfMonth()->addMonthNoOverflow();
            } catch (\Throwable $e) {
                continue;
            }
            if ($this->otherMonthPk($c->poss, $target) !== null) {
                continue;
            }
            $opening = (int) $c->curr_month_elec_red;
            $meter = (string) $c->meter_one;
            $this->other($c->pk, $opening + 100, $target, $meter)->assertRedirect()->assertSessionHas('success');
            $pk = $this->otherMonthPk($c->poss, $target);
            $this->assertNotNull($pk, 'precondition: the new month row exists');
            $this->assertSame($opening, (int) $this->otherRow($pk)->last_month_elec_red, 'precondition: the new month opens at the previous reading');

            return ['pk' => $pk, 'poss' => (int) $c->poss, 'opening' => $opening, 'target' => $target, 'meter' => $meter];
        }
        $this->markTestSkipped('no single-meter Other reading whose next month is still unsaved');
    }

    private function regular(int $pk, int $reading, Carbon $month, string $meterNo, bool $edit)
    {
        $payload = [
            'readings' => [['pk' => $pk, 'meter_slot' => 1, 'selected' => 1, 'curr_month_elec_red' => (string) $reading, 'new_meter_no' => $meterNo]],
            'reading_bill_month' => $month->format('Y-m'),
            'reading_current_date' => $month->copy()->addDays(14)->toDateString(),
        ];
        if ($edit) {
            $payload['edit_reading_pk'] = $pk;
        }

        return $this->actor()->postJson(route('admin.estate.update-meter-reading.store'), $payload);
    }

    private function other(int $pk, int $reading, Carbon $month, string $meterNo)
    {
        return $this->actor()->postJson(route('admin.estate.update-meter-reading-of-other.store'), [
            'readings' => [['pk' => $pk, 'meter_slot' => 1, 'selected' => 1, 'curr_month_elec_red' => (string) $reading, 'new_meter_no' => $meterNo]],
            'reading_bill_month' => $month->format('Y-m'),
            'reading_meter_reading_date' => $month->copy()->addDays(14)->toDateString(),
        ]);
    }

    private function regularMonthPk($poss, Carbon $month): ?int
    {
        $pk = DB::table('estate_month_reading_details')->where('estate_possession_details_pk', $poss)
            ->where('bill_month', $month->format('F'))->where('bill_year', $month->format('Y'))->value('pk');

        return $pk !== null ? (int) $pk : null;
    }

    private function otherMonthPk($poss, Carbon $month): ?int
    {
        $pk = DB::table('estate_month_reading_details_other')->where('estate_possession_other_pk', $poss)
            ->where('bill_month', $month->format('F'))->where('bill_year', $month->format('Y'))->value('pk');

        return $pk !== null ? (int) $pk : null;
    }

    private function regularRow(int $pk): object
    {
        return DB::table('estate_month_reading_details')->where('pk', $pk)->first();
    }

    private function otherRow(int $pk): object
    {
        return DB::table('estate_month_reading_details_other')->where('pk', $pk)->first();
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

        return $this->charge($unitTypePk !== null ? (int) $unitTypePk : null, $units);
    }

    private function otherTariff(int $possessionPk, int $units): float
    {
        $houseType = Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk') ? 'eust.estate_unit_type_master_pk' : 'ehm.estate_unit_master_pk';
        $unitTypePk = DB::table('estate_possession_other as epo')
            ->leftJoin('estate_house_master as ehm', 'epo.estate_house_master_pk', '=', 'ehm.pk')
            ->leftJoin('estate_unit_sub_type_master as eust', 'ehm.estate_unit_sub_type_master_pk', '=', 'eust.pk')
            ->where('epo.pk', $possessionPk)
            ->value(DB::raw("COALESCE(epo.estate_unit_type_master_pk, $houseType)"));

        return $this->charge($unitTypePk !== null && (int) $unitTypePk > 0 ? (int) $unitTypePk : null, $units);
    }

    private function charge(?int $unitTypePk, int $units): float
    {
        $method = new \ReflectionMethod(EstateController::class, 'calculateElectricChargeForUnits');
        $method->setAccessible(true);

        return (float) $method->invoke($this->app->make(EstateController::class), $unitTypePk, $units);
    }
}
