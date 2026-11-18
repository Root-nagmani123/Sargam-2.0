<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\EstateController;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #349 round 14 (every save rolled back):
 *
 *  - F-057: a save could create a month out of order. Update Meter Reading of Other (opened from List Meter Reading
 *    → Edit) saved the latest row under any earlier unsaved month, and either store created a later month from a
 *    row that was not the house's latest, re-billing the months in between. Both are refused inside the store's
 *    transaction now, whatever the screen posts.
 *  - F-052 with several later months saved, and F-058: the refusal names no remedy the application lacks.
 *  - F-059: List Meter Reading's Bill Month select keeps the newest 24 periods of both reading tables, by Y-m.
 *
 * Each chain starts from a single-meter house's latest saved month M0 and saves M1, M2 … from the month before,
 * each +100 over it, the way the dialog does.
 */
class EstateMeterReadingMonthOrderTest extends TestCase
{
    use RollsBackAgainstAppDatabase {
        setUp as setUpRollback;
    }

    private const REMEDY = 'Please contact the Estate module administrator for correction.';

    protected function setUp(): void
    {
        $this->setUpRollback();
        $manager = $this->app->make('db.transactions');
        $manager->callbacksShouldIgnore($manager->getTransactions()->first());
        config(['cache.redis_backed_unified_store' => 'array']);
    }

    /** F-057 (P2), regular: M1's row posted under M3 while M2 is saved is refused; nothing is written. */
    public function test_regular_a_new_month_from_an_older_row_is_refused_and_writes_nothing(): void
    {
        $c = $this->regularChain(2);
        $m3 = $c['months'][2]['target']->copy()->addMonthNoOverflow();
        $before = $this->snapshot('estate_month_reading_details', 'estate_possession_details_pk', $c['poss']);

        $this->regular($c['months'][1]['pk'], $c['opening'] + 300, $m3, $c['meter'])
            ->assertStatus(409)->assertJson(['success' => false, 'message' => $this->outOfOrder($m3, $c['months'][2]['target'])]);
        // The same crafted POST without the screen's AJAX headers.
        $this->actor(false)->post(route('admin.estate.update-meter-reading.store'), $this->regularPayload($c['months'][1]['pk'], $c['opening'] + 300, $m3, $c['meter']))
            ->assertRedirect()->assertSessionHas('error', $this->outOfOrder($m3, $c['months'][2]['target']));

        $this->assertSame($before, $this->snapshot('estate_month_reading_details', 'estate_possession_details_pk', $c['poss']), 'no row created, none changed');
        $this->assertSame($c['opening'] + 200, $this->regularPossessionReading($c['poss']), 'the possession keeps M2\'s reading');
    }

    /** F-057, regular: the latest row posted under a month earlier than the history is refused (Edit or not). */
    public function test_regular_a_month_earlier_than_the_saved_history_is_refused(): void
    {
        $c = $this->regularChain(1);
        $earlier = $this->monthBeforeHistory('estate_month_reading_details', 'estate_possession_details_pk', $c['poss']);
        $before = $this->snapshot('estate_month_reading_details', 'estate_possession_details_pk', $c['poss']);

        $this->regular($c['months'][1]['pk'], $c['opening'] + 150, $earlier, $c['meter'])
            ->assertStatus(409)->assertJson(['success' => false, 'message' => $this->outOfOrder($earlier, $c['months'][1]['target'])]);
        // Through the Edit link the F-053 validator refuses it first.
        $this->regular($c['months'][1]['pk'], $c['opening'] + 150, $earlier, $c['meter'], true)
            ->assertStatus(422)->assertJsonValidationErrors(['readings.0.pk']);

        $this->assertSame($before, $this->snapshot('estate_month_reading_details', 'estate_possession_details_pk', $c['poss']));
    }

    /** F-057 (P1), Other: the latest row posted under an earlier unsaved month is refused; no row, no bill. */
    public function test_other_the_latest_row_posted_under_an_earlier_month_is_refused(): void
    {
        $c = $this->otherChain(1);
        $earlier = $this->monthBeforeHistory('estate_month_reading_details_other', 'estate_possession_other_pk', $c['poss']);
        $latest = $c['months'][1];
        $before = $this->snapshot('estate_month_reading_details_other', 'estate_possession_other_pk', $c['poss']);

        $this->other($latest['pk'], $c['opening'] + 150, $earlier, $c['meter'])
            ->assertStatus(409)->assertJson(['success' => false, 'message' => $this->outOfOrder($earlier, $latest['target'])]);
        $this->actor(false)->post(route('admin.estate.update-meter-reading-of-other.store'), $this->otherPayload($latest['pk'], $c['opening'] + 150, $earlier, $c['meter']))
            ->assertRedirect()->assertSessionHas('error', $this->outOfOrder($earlier, $latest['target']));

        $this->assertNull($this->monthPk('estate_month_reading_details_other', 'estate_possession_other_pk', $c['poss'], $earlier), 'no row for the earlier month');
        $this->assertSame($before, $this->snapshot('estate_month_reading_details_other', 'estate_possession_other_pk', $c['poss']), 'the latest month unchanged');
        $this->assertSame($c['opening'] + 100, $this->otherPossessionReading($c['poss']));
    }

    /** F-057, Other: M1's row posted under M3 while M2 is saved is refused. */
    public function test_other_a_new_month_from_an_older_row_is_refused(): void
    {
        $c = $this->otherChain(2);
        $m3 = $c['months'][2]['target']->copy()->addMonthNoOverflow();
        $before = $this->snapshot('estate_month_reading_details_other', 'estate_possession_other_pk', $c['poss']);

        $this->other($c['months'][1]['pk'], $c['opening'] + 300, $m3, $c['meter'])
            ->assertStatus(409)->assertJson(['success' => false, 'message' => $this->outOfOrder($m3, $c['months'][2]['target'])]);

        $this->assertSame($before, $this->snapshot('estate_month_reading_details_other', 'estate_possession_other_pk', $c['poss']));
        $this->assertSame($c['opening'] + 200, $this->otherPossessionReading($c['poss']));
    }

    /** F-047 protection intact: an older row posted under a month that is already saved keeps the stale-list refusal. */
    public function test_saving_into_a_month_that_already_exists_keeps_the_stale_refusal(): void
    {
        $c = $this->regularChain(2);
        $m2 = $c['months'][2]['target'];
        $stale = "A reading for {$m2->format('F')} {$m2->format('Y')} is already saved for this house, so this list is out of date.";
        $response = $this->regular($c['months'][1]['pk'], $c['opening'] + 250, $m2, $c['meter'])->assertStatus(422)
            ->assertJsonValidationErrors(['readings.0.pk']);
        $this->assertStringStartsWith($stale, (string) $response->json('message'));
        $this->assertSame($c['opening'] + 200, (int) $this->row('estate_month_reading_details', $c['months'][2]['pk'])->curr_month_elec_red);

        $o = $this->otherChain(2);
        $o2 = $o['months'][2]['target'];
        $this->other($o['months'][1]['pk'], $o['opening'] + 250, $o2, $o['meter'])->assertStatus(422)->assertJsonValidationErrors(['readings.0.pk']);
        $this->assertSame($o['opening'] + 200, (int) $this->row('estate_month_reading_details_other', $o['months'][2]['pk'])->curr_month_elec_red);
    }

    /** Control (case E): the next month from the latest row is saved, opening at its reading and billing the rise. */
    public function test_the_next_month_from_the_latest_row_is_saved(): void
    {
        $c = $this->regularChain(2);
        $m2 = $this->row('estate_month_reading_details', $c['months'][2]['pk']);
        $this->assertSame($c['opening'] + 100, (int) $m2->last_month_elec_red, 'M2 opens at M1\'s reading');
        $this->assertSame(100, (int) $m2->meter_one_consume_unit);
        $this->assertEqualsWithDelta($this->tariff($c['poss'], 100), (float) $m2->electricty_charges, 0.001);
        $this->assertSame($c['opening'] + 200, $this->regularPossessionReading($c['poss']));

        $o = $this->otherChain(2);
        $o2 = $this->row('estate_month_reading_details_other', $o['months'][2]['pk']);
        $this->assertSame($o['opening'] + 100, (int) $o2->last_month_elec_red);
        $this->assertSame(100, (int) $o2->per_unit);
        $this->assertSame($o['opening'] + 200, $this->otherPossessionReading($o['poss']));
    }

    /**
     * F-052 with two later months saved, the later bill issued, and F-058: correcting M1 is refused through Edit and
     * the dialog, with a text that names no in-app remedy; M1, M2, M3 and the possession's reading stay as they were.
     */
    public function test_correcting_a_month_with_several_later_months_saved_is_refused(): void
    {
        $c = $this->regularChain(3);
        DB::table('estate_month_reading_details')->where('pk', $c['months'][3]['pk'])->update(['notify_employee_status' => 1]);
        $before = $this->snapshot('estate_month_reading_details', 'estate_possession_details_pk', $c['poss']);
        $message = 'A reading for '.$c['months'][2]['target']->format('F Y').' is already saved for this house, so an earlier month cannot be corrected. '.self::REMEDY;

        foreach ([true, false] as $edit) {
            $this->regular($c['months'][1]['pk'], $c['opening'] + 150, $c['months'][1]['target'], $c['meter'], $edit)
                ->assertStatus(422)->assertJsonPath('errors', ['readings.0.pk' => [$message]]);
        }

        $this->assertSame($before, $this->snapshot('estate_month_reading_details', 'estate_possession_details_pk', $c['poss']));
        $this->assertSame($c['opening'] + 300, $this->regularPossessionReading($c['poss']));
        $this->assertStringNotContainsStringIgnoringCase('remove', $message);
    }

    /** F-052 / F-058 in Update Meter Reading of Other. */
    public function test_other_correcting_a_month_with_several_later_months_saved_is_refused(): void
    {
        $c = $this->otherChain(3);
        $before = $this->snapshot('estate_month_reading_details_other', 'estate_possession_other_pk', $c['poss']);
        $message = 'A reading for '.$c['months'][2]['target']->format('F Y').' is already saved for this house, so an earlier month cannot be corrected. '.self::REMEDY;

        $this->other($c['months'][1]['pk'], $c['opening'] + 150, $c['months'][1]['target'], $c['meter'])
            ->assertStatus(422)->assertJsonPath('errors', ['readings.0.pk' => [$message]]);

        $this->assertSame($before, $this->snapshot('estate_month_reading_details_other', 'estate_possession_other_pk', $c['poss']));
        $this->assertSame($c['opening'] + 300, $this->otherPossessionReading($c['poss']));
    }

    /** F-057: the Other Edit list names the row's own month, which the screen's Edit guard compares with. */
    public function test_the_other_edit_list_names_the_rows_own_month(): void
    {
        $c = $this->otherChain(1);
        $earlier = $this->monthBeforeHistory('estate_month_reading_details_other', 'estate_possession_other_pk', $c['poss']);

        $data = $this->actor()->getJson(route('admin.estate.update-meter-reading-of-other.list', [
            'bill_month' => $earlier->month, 'bill_year' => $earlier->year,
            'possession_pks' => (string) $c['poss'], 'reading_pk' => $c['months'][1]['pk'],
        ]))->assertOk()->json('data');

        $this->assertCount(1, $data, 'the Edit link lists its row under any month');
        $this->assertSame($c['months'][1]['target']->format('Y-m'), $data[0]['bill_ym'] ?? null);
    }

    /** F-059: more than 24 periods over several years, one only in the Other table: the newest 24, newest first. */
    public function test_list_meter_reading_offers_the_newest_24_periods_of_both_reading_tables(): void
    {
        $regularPoss = (int) DB::table('estate_possession_details')->value('pk');
        $otherPoss = (int) DB::table('estate_possession_other')->value('pk');
        $start = Carbon::create(2091, 1, 1);
        for ($i = 0; $i < 26; $i++) {                                    // January 2091 … February 2093
            $this->insertPeriod('estate_month_reading_details', 'estate_possession_details_pk', $regularPoss, $start->copy()->addMonths($i));
        }
        $otherOnly = $start->copy()->addMonths(26);                       // March 2093, Other table only
        $this->insertPeriod('estate_month_reading_details_other', 'estate_possession_other_pk', $otherPoss, $otherOnly);

        $options = $this->actor(false)->get(route('admin.estate.list-meter-reading'))->assertOk()->viewData('billMonthOptions');

        $expected = [];
        for ($i = 26; $i >= 3; $i--) {                                    // March 2093 down to April 2091
            $m = $start->copy()->addMonths($i);
            $expected[$m->format('Y-m')] = $m->format('F Y');
        }
        $this->assertSame($expected, $options);
        $this->assertSame('2093-03', array_key_first($options), 'the Other-only period is offered, first');
        $this->assertArrayNotHasKey('2091-03', $options, 'cut after the 24th period');
    }

    // ---- fixtures -------------------------------------------------------------------------------------------

    /**
     * A single-meter house whose latest saved month is M0, then $n new months saved from the month before.
     *
     * @return array{poss: int, meter: string, opening: int, months: array<int, array{pk: int, target: Carbon}>}
     */
    private function regularChain(int $n): array
    {
        $candidates = DB::table('estate_month_reading_details as emrd')
            ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
            ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
            ->where(fn ($q) => $q->whereNull('epd.return_home_status')->orWhere('epd.return_home_status', 0))
            ->whereNotNull('emrd.curr_month_elec_red')->whereNotNull('emrd.bill_month')->whereNotNull('emrd.bill_year')
            ->where('emrd.meter_one', '>', 0)
            ->whereRaw('COALESCE(NULLIF(emrd.meter_two, 0), NULLIF(ehm.meter_two, 0)) IS NULL')
            ->orderByDesc('emrd.pk')->limit(80)
            ->get(['emrd.pk', 'emrd.bill_month', 'emrd.bill_year', 'emrd.curr_month_elec_red', 'emrd.meter_one', 'epd.pk as poss']);

        return $this->chain($candidates, 'estate_month_reading_details', 'estate_possession_details_pk', $n,
            fn ($pk, $reading, $target, $meter) => $this->regular($pk, $reading, $target, $meter)->assertOk()->assertJson(['success' => true]));
    }

    /** @return array{poss: int, meter: string, opening: int, months: array<int, array{pk: int, target: Carbon}>} */
    private function otherChain(int $n): array
    {
        $candidates = DB::table('estate_month_reading_details_other as r')
            ->join('estate_possession_other as epo', 'epo.pk', '=', 'r.estate_possession_other_pk')
            ->leftJoin('estate_house_master as ehm', 'epo.estate_house_master_pk', '=', 'ehm.pk')
            ->where(fn ($q) => $q->whereNull('epo.return_home_status')->orWhere('epo.return_home_status', 0))
            ->whereNotNull('r.curr_month_elec_red')->whereNotNull('r.bill_month')->whereNotNull('r.bill_year')
            ->where('r.meter_one', '>', 0)
            ->whereRaw('COALESCE(NULLIF(r.meter_two, 0), NULLIF(ehm.meter_two, 0)) IS NULL')
            ->orderByDesc('r.pk')->limit(80)
            ->get(['r.pk', 'r.bill_month', 'r.bill_year', 'r.curr_month_elec_red', 'r.meter_one', 'r.estate_possession_other_pk as poss']);

        return $this->chain($candidates, 'estate_month_reading_details_other', 'estate_possession_other_pk', $n,
            fn ($pk, $reading, $target, $meter) => $this->other($pk, $reading, $target, $meter)->assertRedirect()->assertSessionHas('success'));
    }

    private function chain($candidates, string $table, string $possCol, int $n, \Closure $save): array
    {
        foreach ($candidates as $c) {
            $ym = $this->ym($c->bill_month, $c->bill_year);
            $history = array_filter(array_map(fn ($h) => $this->ym($h->bill_month, $h->bill_year),
                DB::table($table)->where($possCol, $c->poss)->get(['bill_month', 'bill_year'])->all()));
            if ($ym === null || max($history) !== $ym) {
                continue;                                                  // not the house's latest month
            }
            $opening = (int) $c->curr_month_elec_red;
            $meter = (string) $c->meter_one;
            $months = [0 => ['pk' => (int) $c->pk, 'target' => Carbon::createFromFormat('Y-m-d', $ym.'-01')->startOfDay()]];
            for ($i = 1; $i <= $n; $i++) {
                $target = $months[$i - 1]['target']->copy()->addMonthNoOverflow();
                $save($months[$i - 1]['pk'], $opening + 100 * $i, $target, $meter);
                $pk = $this->monthPk($table, $possCol, $c->poss, $target);
                $this->assertNotNull($pk, "precondition: month {$i} saved");
                $this->assertSame($opening + 100 * ($i - 1), (int) $this->row($table, $pk)->last_month_elec_red, "precondition: month {$i} opens at the month before");
                $months[$i] = ['pk' => $pk, 'target' => $target];
            }

            return ['poss' => (int) $c->poss, 'meter' => $meter, 'opening' => $opening, 'months' => $months];
        }
        $this->markTestSkipped("no single-meter house in {$table} with a parseable latest month");
    }

    private function ym($month, $year): ?string
    {
        try {
            return Carbon::parse('1 '.$month.' '.$year)->format('Y-m');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** The month before the house's earliest saved month: unsaved, and earlier than all of its history. */
    private function monthBeforeHistory(string $table, string $possCol, int $poss): Carbon
    {
        $yms = array_filter(array_map(fn ($h) => $this->ym($h->bill_month, $h->bill_year),
            DB::table($table)->where($possCol, $poss)->get(['bill_month', 'bill_year'])->all()));

        return Carbon::createFromFormat('Y-m-d', min($yms).'-01')->startOfDay()->subMonthNoOverflow();
    }

    /** Every reading row of the possession, as stored. */
    private function snapshot(string $table, string $possCol, int $poss): array
    {
        return DB::table($table)->where($possCol, $poss)->orderBy('pk')->get()->map(fn ($r) => (array) $r)->all();
    }

    private function insertPeriod(string $table, string $possCol, int $poss, Carbon $month): void
    {
        DB::table($table)->insert([
            $possCol => $poss, 'bill_month' => $month->format('F'), 'bill_year' => $month->format('Y'),
            'from_date' => $month->toDateString(), 'to_date' => $month->copy()->endOfMonth()->toDateString(),
            'last_month_elec_red' => 0, 'curr_month_elec_red' => 0,
        ]);
    }

    private function outOfOrder(Carbon $target, Carbon $latest): string
    {
        return "{$target->format('F Y')} cannot be saved from this reading: the latest month saved for this house is {$latest->format('F Y')}, and a new month can only follow it. Reload the list and choose a month after {$latest->format('F Y')}.";
    }

    private function regular(int $pk, int $reading, Carbon $month, string $meterNo, bool $edit = false)
    {
        return $this->actor()->postJson(route('admin.estate.update-meter-reading.store'), $this->regularPayload($pk, $reading, $month, $meterNo, $edit));
    }

    private function regularPayload(int $pk, int $reading, Carbon $month, string $meterNo, bool $edit = false): array
    {
        $payload = [
            'readings' => [['pk' => $pk, 'meter_slot' => 1, 'selected' => 1, 'curr_month_elec_red' => (string) $reading, 'new_meter_no' => $meterNo]],
            'reading_bill_month' => $month->format('Y-m'),
            'reading_current_date' => $month->copy()->addDays(14)->toDateString(),
        ];
        if ($edit) {
            $payload['edit_reading_pk'] = $pk;
        }

        return $payload;
    }

    private function other(int $pk, int $reading, Carbon $month, string $meterNo)
    {
        return $this->actor()->postJson(route('admin.estate.update-meter-reading-of-other.store'), $this->otherPayload($pk, $reading, $month, $meterNo));
    }

    private function otherPayload(int $pk, int $reading, Carbon $month, string $meterNo): array
    {
        return [
            'readings' => [['pk' => $pk, 'meter_slot' => 1, 'selected' => 1, 'curr_month_elec_red' => (string) $reading, 'new_meter_no' => $meterNo]],
            'reading_bill_month' => $month->format('Y-m'),
            'reading_meter_reading_date' => $month->copy()->addDays(14)->toDateString(),
        ];
    }

    private function monthPk(string $table, string $possCol, int $poss, Carbon $month): ?int
    {
        $pk = DB::table($table)->where($possCol, $poss)
            ->where('bill_month', $month->format('F'))->where('bill_year', $month->format('Y'))->value('pk');

        return $pk !== null ? (int) $pk : null;
    }

    private function row(string $table, int $pk): object
    {
        return DB::table($table)->where('pk', $pk)->first();
    }

    private function regularPossessionReading(int $poss): int
    {
        return (int) DB::table('estate_possession_details')->where('pk', $poss)->value('electric_meter_reading');
    }

    private function otherPossessionReading(int $poss): int
    {
        return (int) DB::table('estate_possession_other')->where('pk', $poss)->value('meter_reading_oth');
    }

    private function actor(bool $ajax = true)
    {
        // withHeaders() persists on the test case, so a plain form POST must drop the AJAX header first.
        $actor = $this->flushHeaders()->as($this->staffWithRole('Estate Admin'), ['Estate Admin']);

        return $ajax ? $actor->withHeaders(['X-Requested-With' => 'XMLHttpRequest']) : $actor;
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
