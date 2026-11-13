<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\EstateController;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #349 F-048: the Update Meter Reading saves checked every row, then wrote row by row. When a second
 * operator saved one of the houses for the same month in between, the store refused that house after the
 * houses before it were already written: a failure response, a bill left behind, and no cache bump.
 *
 * The second operator is a second database connection that commits its row from a DB::listen hook on the
 * first house's write-loop fetch (after validation, before any write). Its row is really committed, so run
 * this against a scratch copy of the database, not a shared one; the hook's rows are deleted afterwards.
 * It is skipped unless SARGAM_ALLOW_COMMITTED_PROBES names the database (PR #349 F-055):
 *
 *   SARGAM_ALLOW_COMMITTED_PROBES=<db> \
 *     php vendor/bin/phpunit tests/Feature/EstateMeterReadingAtomicSaveTest.php
 */
class EstateMeterReadingAtomicSaveTest extends TestCase
{
    use RollsBackAgainstAppDatabase {
        setUp as setUpRollback;
    }

    private const OTHER_OPERATOR = 'f048_other_operator';

    /** @var array<int, array{0: string, 1: int}> rows the other operator committed: [table, pk] */
    private array $committedByOtherOperator = [];

    protected function setUp(): void
    {
        $this->setUpRollback();
        $db = DB::connection()->getDatabaseName();
        if (env('SARGAM_ALLOW_COMMITTED_PROBES') !== $db) {
            $this->markTestSkipped("commits probe rows; set SARGAM_ALLOW_COMMITTED_PROBES={$db} to run");
        }
        // Production runs these saves at READ COMMITTED (runAllotmentTransaction); the test transaction must
        // too, or its snapshot hides the other operator's commit. The level can only change between transactions.
        DB::rollBack();
        DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
        DB::beginTransaction();
        $manager = $this->app->make('db.transactions');
        $manager->callbacksShouldIgnore($manager->getTransactions()->first());

        config(['cache.redis_backed_unified_store' => 'array']);
        config(['database.connections.'.self::OTHER_OPERATOR => config('database.connections.'.config('database.default'))]);
        $this->beforeApplicationDestroyed(function () {
            foreach ($this->committedByOtherOperator as [$table, $pk]) {
                DB::connection(self::OTHER_OPERATOR)->table($table)->where('pk', $pk)->delete();
            }
            DB::purge(self::OTHER_OPERATOR);
        });
    }

    public function test_a_stale_refusal_leaves_none_of_the_save_written(): void
    {
        [$a, $b, $target] = $this->regularPair();
        $this->whenFirstQueryMatches('audit_estate_house_pk', fn () => $this->otherOperatorSaves(
            'estate_month_reading_details', 'estate_possession_details_pk', $b, $target
        ));
        $versionBefore = $this->cacheVersion();

        $this->actor()->postJson(route('admin.estate.update-meter-reading.store'), $this->regularPayload([$a, $b], $target))
            ->assertStatus(409)->assertJson(['success' => false]);

        $this->assertCount(1, $this->committedByOtherOperator, 'the hook fired: the other operator saved house B');
        $this->assertCount(0, $this->targetRows('estate_month_reading_details', 'estate_possession_details_pk', $a->poss, $target), 'house A: no new month row, no bill');
        $this->assertEquals([$this->otherOperatorRow('estate_month_reading_details')], $this->targetRows('estate_month_reading_details', 'estate_possession_details_pk', $b->poss, $target)->all(), 'house B: only the other operator\'s row, unchanged');
        $this->assertSame($versionBefore, $this->cacheVersion(), 'nothing committed, so no cache bump');
    }

    /** Control: the same save without the second operator commits both houses and bumps the cache. */
    public function test_the_same_save_without_a_second_operator_writes_both_houses(): void
    {
        [$a, $b, $target] = $this->regularPair();
        $versionBefore = $this->cacheVersion();

        $this->actor()->postJson(route('admin.estate.update-meter-reading.store'), $this->regularPayload([$a, $b], $target))
            ->assertOk()->assertJson(['success' => true]);

        foreach ([$a, $b] as $house) {
            $rows = $this->targetRows('estate_month_reading_details', 'estate_possession_details_pk', $house->poss, $target);
            $this->assertCount(1, $rows);
            $this->assertSame((int) $house->curr_month_elec_red, (int) $rows[0]->last_month_elec_red);
            $this->assertSame((int) $house->curr_month_elec_red + 100, (int) $rows[0]->curr_month_elec_red);
            $this->assertSame(100, (int) $rows[0]->meter_one_consume_unit);
        }
        $this->assertSame($versionBefore + 1, $this->cacheVersion());
    }

    public function test_other_flow_a_stale_refusal_leaves_none_of_the_save_written(): void
    {
        [$a, $b, $target] = $this->otherPair();
        $this->whenFirstQueryMatches('electric_unit_type_pk_resolved', fn () => $this->otherOperatorSaves(
            'estate_month_reading_details_other', 'estate_possession_other_pk', $b, $target
        ));
        $versionBefore = $this->cacheVersion();

        $this->actor()->postJson(route('admin.estate.update-meter-reading-of-other.store'), $this->otherPayload([$a, $b], $target))
            ->assertStatus(409)->assertJson(['success' => false]);

        $this->assertCount(1, $this->committedByOtherOperator, 'the hook fired: the other operator saved house B');
        $this->assertCount(0, $this->targetRows('estate_month_reading_details_other', 'estate_possession_other_pk', $a->poss, $target), 'house A: no new month row, no bill');
        $this->assertEquals([$this->otherOperatorRow('estate_month_reading_details_other')], $this->targetRows('estate_month_reading_details_other', 'estate_possession_other_pk', $b->poss, $target)->all(), 'house B: only the other operator\'s row, unchanged');
        $this->assertSame($versionBefore, $this->cacheVersion(), 'nothing committed, so no cache bump');
    }

    public function test_other_flow_the_same_save_without_a_second_operator_writes_both_houses(): void
    {
        [$a, $b, $target] = $this->otherPair();
        $versionBefore = $this->cacheVersion();

        // This store answers success with a redirect, AJAX or not.
        $this->actor()->postJson(route('admin.estate.update-meter-reading-of-other.store'), $this->otherPayload([$a, $b], $target))
            ->assertRedirect()->assertSessionHas('success', 'Meter readings updated successfully.');

        foreach ([$a, $b] as $house) {
            $rows = $this->targetRows('estate_month_reading_details_other', 'estate_possession_other_pk', $house->poss, $target);
            $this->assertCount(1, $rows);
            $this->assertSame((int) $house->curr_month_elec_red + 100, (int) $rows[0]->curr_month_elec_red);
        }
        $this->assertSame($versionBefore + 1, $this->cacheVersion());
    }

    private function actor()
    {
        return $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin'])->withHeaders(['X-Requested-With' => 'XMLHttpRequest']);
    }

    /** Run $then once, on the first main-connection query whose SQL contains $marker. */
    private function whenFirstQueryMatches(string $marker, \Closure $then): void
    {
        $fired = false;
        DB::listen(function ($query) use (&$fired, $marker, $then) {
            if (! $fired && $query->connectionName === config('database.default') && str_contains($query->sql, $marker)) {
                $fired = true;
                $then();
            }
        });
    }

    private function otherOperatorSaves(string $table, string $possessionCol, object $house, Carbon $target): void
    {
        $pk = (int) DB::connection(self::OTHER_OPERATOR)->table($table)->insertGetId([
            $possessionCol => $house->poss,
            'bill_month' => $target->format('F'),
            'bill_year' => $target->format('Y'),
            'from_date' => $target->toDateString(),
            'to_date' => $target->copy()->endOfMonth()->toDateString(),
            'last_month_elec_red' => (int) $house->curr_month_elec_red,
            'curr_month_elec_red' => (int) $house->curr_month_elec_red + 7,
        ]);
        $this->committedByOtherOperator[] = [$table, $pk];
    }

    private function otherOperatorRow(string $table): object
    {
        return DB::table($table)->where('pk', $this->committedByOtherOperator[0][1])->first();
    }

    /** @return array{0: object, 1: object, 2: Carbon} two houses whose latest reading is the same month, next month unsaved */
    private function regularPair(): array
    {
        $rows = DB::table('estate_month_reading_details as emrd')
            ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
            ->where(fn ($q) => $q->whereNull('epd.return_home_status')->orWhere('epd.return_home_status', 0))
            ->whereNotNull('emrd.curr_month_elec_red')->whereNotNull('emrd.bill_month')->whereNotNull('emrd.bill_year')
            ->orderByDesc('emrd.pk')->limit(300)
            ->get(['emrd.pk', 'emrd.bill_month', 'emrd.bill_year', 'emrd.curr_month_elec_red', 'epd.pk as poss']);

        return $this->pairFrom($rows, 'estate_month_reading_details', 'estate_possession_details_pk');
    }

    /** @return array{0: object, 1: object, 2: Carbon} */
    private function otherPair(): array
    {
        $rows = DB::table('estate_month_reading_details_other as r')
            ->join('estate_possession_other as epo', 'epo.pk', '=', 'r.estate_possession_other_pk')
            ->where(fn ($q) => $q->whereNull('epo.return_home_status')->orWhere('epo.return_home_status', 0))
            ->whereNotNull('r.curr_month_elec_red')->whereNotNull('r.bill_month')->whereNotNull('r.bill_year')
            ->orderByDesc('r.pk')->limit(300)
            ->get(['r.pk', 'r.bill_month', 'r.bill_year', 'r.curr_month_elec_red', 'r.estate_possession_other_pk as poss']);

        return $this->pairFrom($rows, 'estate_month_reading_details_other', 'estate_possession_other_pk');
    }

    private function pairFrom($rows, string $table, string $possessionCol): array
    {
        $byTarget = [];
        $seenPoss = [];
        foreach ($rows as $row) {
            if (isset($seenPoss[$row->poss])) {
                continue;
            }
            $seenPoss[$row->poss] = true;
            try {
                $target = Carbon::parse('1 '.$row->bill_month.' '.$row->bill_year)->startOfMonth()->addMonthNoOverflow();
            } catch (\Throwable $e) {
                continue;
            }
            if ($this->targetRows($table, $possessionCol, $row->poss, $target)->isNotEmpty()) {
                continue;
            }
            $key = $target->format('Y-m');
            $byTarget[$key][] = $row;
            if (count($byTarget[$key]) === 2) {
                return [$byTarget[$key][0], $byTarget[$key][1], $target];
            }
        }
        $this->markTestSkipped("no two {$table} houses whose latest reading is the same month with the next month unsaved");
    }

    private function regularPayload(array $houses, Carbon $target): array
    {
        return [
            'readings' => array_map(fn ($h) => ['pk' => $h->pk, 'meter_slot' => 1, 'selected' => 1, 'curr_month_elec_red' => (string) ((int) $h->curr_month_elec_red + 100)], $houses),
            'reading_bill_month' => $target->format('Y-m'),
            'reading_current_date' => $target->copy()->addDays(14)->toDateString(),
        ];
    }

    private function otherPayload(array $houses, Carbon $target): array
    {
        return [
            'readings' => array_map(fn ($h) => ['pk' => $h->pk, 'meter_slot' => 1, 'selected' => 1, 'curr_month_elec_red' => (string) ((int) $h->curr_month_elec_red + 100)], $houses),
            'reading_bill_month' => $target->format('Y-m'),
            'reading_meter_reading_date' => $target->copy()->addDays(14)->toDateString(),
        ];
    }

    private function targetRows(string $table, string $possessionCol, $poss, Carbon $target)
    {
        return DB::table($table)->where($possessionCol, $poss)
            ->where('bill_month', $target->format('F'))->where('bill_year', $target->format('Y'))
            ->orderBy('pk')->get();
    }

    private function cacheVersion(): int
    {
        $method = new \ReflectionMethod(EstateController::class, 'estateCacheVersion');
        $method->setAccessible(true);

        return $method->invoke($this->app->make(EstateController::class));
    }
}
