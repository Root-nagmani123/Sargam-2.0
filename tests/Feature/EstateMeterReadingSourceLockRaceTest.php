<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #349 F-056: the new-month save read its source row (month N) with a plain SELECT and took the possession lock
 * only before inserting N+1. A correction of N that committed in between was missed: N+1 opened at N's
 * pre-correction reading, and the corrected units were billed in both months.
 *
 * Operator A is a second database connection that corrects N the way every save must: possession lock first
 * (1 s lock wait), then the write, then commit. It runs from a DB::listen hook on the save's source-row read,
 * which is the interleaving the review executed. Its correction is really committed, so run this against a
 * scratch copy; the corrected rows are restored afterwards. Skipped unless SARGAM_ALLOW_COMMITTED_PROBES names
 * the database:
 *
 *   SARGAM_ALLOW_COMMITTED_PROBES=<db> php vendor/bin/phpunit tests/Feature/EstateMeterReadingSourceLockRaceTest.php
 *
 * Invariant: N+1 opens at N's reading as finally stored, or the correction is refused.
 */
class EstateMeterReadingSourceLockRaceTest extends TestCase
{
    use RollsBackAgainstAppDatabase {
        setUp as setUpRollback;
    }

    private const OPERATOR_A = 'f056_operator_a';

    private const CORRECTION = 40;

    /** @var array<int, array{0: string, 1: int, 2: int}> rows operator A committed: [table, pk, original reading] */
    private array $committedByA = [];

    /** Set by the hook: 'committed' (A got the lock) or 'blocked' (lock wait timeout: the save held it). */
    private ?string $operatorA = null;

    protected function setUp(): void
    {
        $this->setUpRollback();
        $db = DB::connection()->getDatabaseName();
        if (env('SARGAM_ALLOW_COMMITTED_PROBES') !== $db) {
            $this->markTestSkipped("commits probe rows; set SARGAM_ALLOW_COMMITTED_PROBES={$db} to run");
        }
        // Production runs the saves at READ COMMITTED (runAllotmentTransaction); so must the test transaction.
        DB::rollBack();
        DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
        DB::beginTransaction();
        $manager = $this->app->make('db.transactions');
        $manager->callbacksShouldIgnore($manager->getTransactions()->first());

        config(['cache.redis_backed_unified_store' => 'array']);
        config(['database.connections.'.self::OPERATOR_A => config('database.connections.'.config('database.default'))]);
        $this->beforeApplicationDestroyed(function () {
            foreach ($this->committedByA as [$table, $pk, $reading]) {
                DB::connection(self::OPERATOR_A)->table($table)->where('pk', $pk)->update(['curr_month_elec_red' => $reading]);
            }
            DB::purge(self::OPERATOR_A);
        });
    }

    /** The review's order: A corrects N after the new-month save has read N's row. */
    public function test_regular_a_correction_after_the_source_read_cannot_leave_the_next_month_stale(): void
    {
        $n = $this->latestMonth('estate_month_reading_details', 'estate_possession_details_pk');
        $this->afterFirstQuery('`audit_estate_house_pk`', fn () => $this->operatorACorrects('estate_month_reading_details', 'estate_possession_details', $n));

        $this->regular($n->pk, $n->reading + 100, $n->next)->assertOk()->assertJson(['success' => true]);

        $this->assertNotNull($this->operatorA, 'the hook fired');
        $this->assertNextMonthOpensAtStoredReading('estate_month_reading_details', 'estate_possession_details_pk', $n);
        $this->assertSame('blocked', $this->operatorA, 'the save held the possession lock when it read its source row');
        // A's correction, retried once the save is in: refused (F-052), N unchanged.
        $this->regular($n->pk, $n->reading + self::CORRECTION, $n->month)->assertStatus(422)->assertJsonValidationErrors(['readings.0.pk']);
        $this->assertSame($n->reading, (int) DB::table('estate_month_reading_details')->where('pk', $n->pk)->value('curr_month_elec_red'));
    }

    /** Control, the other order: A's correction commits before the save reads N; N+1 opens at the corrected reading. */
    public function test_regular_a_correction_committed_before_the_save_is_carried_into_the_next_month(): void
    {
        $n = $this->latestMonth('estate_month_reading_details', 'estate_possession_details_pk');
        $this->afterFirstQuery('`bill_year`, `estate_possession_details_pk` from `estate_month_reading_details` where `pk` in', fn () => $this->operatorACorrects('estate_month_reading_details', 'estate_possession_details', $n));

        $this->regular($n->pk, $n->reading + 100, $n->next)->assertOk()->assertJson(['success' => true]);

        $this->assertSame('committed', $this->operatorA);
        $next = $this->assertNextMonthOpensAtStoredReading('estate_month_reading_details', 'estate_possession_details_pk', $n);
        $this->assertSame($n->reading + self::CORRECTION, (int) $next->last_month_elec_red);
        $this->assertSame(100 - self::CORRECTION, (int) $next->meter_one_consume_unit, 'the corrected units are billed once');
    }

    public function test_other_a_correction_after_the_source_read_cannot_leave_the_next_month_stale(): void
    {
        $n = $this->latestMonth('estate_month_reading_details_other', 'estate_possession_other_pk');
        $this->afterFirstQuery('select * from `estate_month_reading_details_other` where `pk` = ?', fn () => $this->operatorACorrects('estate_month_reading_details_other', 'estate_possession_other', $n));

        $this->other($n->pk, $n->reading + 100, $n->next)->assertRedirect()->assertSessionHas('success');

        $this->assertNotNull($this->operatorA, 'the hook fired');
        $this->assertNextMonthOpensAtStoredReading('estate_month_reading_details_other', 'estate_possession_other_pk', $n);
        $this->assertSame('blocked', $this->operatorA, 'the save held the possession lock when it read its source row');
        $this->other($n->pk, $n->reading + self::CORRECTION, $n->month)->assertStatus(422)->assertJsonValidationErrors(['readings.0.pk']);
    }

    public function test_other_a_correction_committed_before_the_save_is_carried_into_the_next_month(): void
    {
        $n = $this->latestMonth('estate_month_reading_details_other', 'estate_possession_other_pk');
        $this->afterFirstQuery('`bill_year`, `estate_possession_other_pk` from `estate_month_reading_details_other` where `pk` in', fn () => $this->operatorACorrects('estate_month_reading_details_other', 'estate_possession_other', $n));

        $this->other($n->pk, $n->reading + 100, $n->next)->assertRedirect()->assertSessionHas('success');

        $this->assertSame('committed', $this->operatorA);
        $next = $this->assertNextMonthOpensAtStoredReading('estate_month_reading_details_other', 'estate_possession_other_pk', $n);
        $this->assertSame(100 - self::CORRECTION, (int) $next->per_unit, 'the corrected units are billed once');
    }

    private function assertNextMonthOpensAtStoredReading(string $table, string $possCol, object $n): object
    {
        $next = DB::table($table)->where($possCol, $n->poss)
            ->where('bill_month', $n->next->format('F'))->where('bill_year', $n->next->format('Y'))->first();
        $this->assertNotNull($next, 'N+1 is saved');
        $stored = (int) DB::table($table)->where('pk', $n->pk)->value('curr_month_elec_red');
        $this->assertSame($stored, (int) $next->last_month_elec_red, 'N+1 opens at N\'s reading as stored, not at a reading a correction replaced');

        return $next;
    }

    /** Operator A: possession lock (1 s wait), correct N by CORRECTION, commit. */
    private function operatorACorrects(string $table, string $possessionTable, object $n): void
    {
        $a = DB::connection(self::OPERATOR_A);
        $a->statement('SET SESSION innodb_lock_wait_timeout = 1');
        $a->beginTransaction();
        try {
            $a->table($possessionTable)->where('pk', $n->poss)->lockForUpdate()->value('pk');
        } catch (QueryException $e) {
            $a->rollBack();
            $this->assertSame(1205, (int) ($e->errorInfo[1] ?? 0), 'only a lock wait timeout is expected');
            $this->operatorA = 'blocked';

            return;
        }
        $a->table($table)->where('pk', $n->pk)->update(['curr_month_elec_red' => $n->reading + self::CORRECTION]);
        $a->commit();
        $this->committedByA[] = [$table, $n->pk, $n->reading];
        $this->operatorA = 'committed';
    }

    /** Run $then once, after the first main-connection query whose SQL contains $marker. */
    private function afterFirstQuery(string $marker, \Closure $then): void
    {
        $fired = false;
        DB::listen(function ($query) use (&$fired, $marker, $then) {
            if (! $fired && $query->connectionName === config('database.default') && str_contains($query->sql, $marker)) {
                $fired = true;
                $then();
            }
        });
    }

    /** A committed single-meter reading that is its house's latest month, with the next month unsaved. */
    private function latestMonth(string $table, string $possCol): object
    {
        $possessionTable = $possCol === 'estate_possession_details_pk' ? 'estate_possession_details' : 'estate_possession_other';
        $candidates = DB::table($table.' as r')
            ->join($possessionTable.' as p', 'p.pk', '=', 'r.'.$possCol)
            ->leftJoin('estate_house_master as ehm', 'p.estate_house_master_pk', '=', 'ehm.pk')
            ->where(fn ($q) => $q->whereNull('p.return_home_status')->orWhere('p.return_home_status', 0))
            ->whereNotNull('r.curr_month_elec_red')->whereNotNull('r.bill_month')->whereNotNull('r.bill_year')
            ->where('r.meter_one', '>', 0)
            ->whereRaw('COALESCE(NULLIF(r.meter_two, 0), NULLIF(ehm.meter_two, 0)) IS NULL')
            ->orderByDesc('r.pk')->limit(80)
            ->get(['r.pk', 'r.bill_month', 'r.bill_year', 'r.curr_month_elec_red', 'r.meter_one', 'r.'.$possCol.' as poss']);
        if ($possCol === 'estate_possession_details_pk') {
            // The regular store's row read joins the request; skip possessions without one.
            $candidates = $candidates->filter(fn ($c) => DB::table('estate_possession_details as p')
                ->join('estate_home_request_details as h', 'p.estate_home_request_details', '=', 'h.pk')->where('p.pk', $c->poss)->exists());
        }
        foreach ($candidates as $c) {
            $ym = $this->ym($c->bill_month, $c->bill_year);
            $history = array_filter(array_map(fn ($h) => $this->ym($h->bill_month, $h->bill_year),
                DB::table($table)->where($possCol, $c->poss)->get(['bill_month', 'bill_year'])->all()));
            if ($ym === null || max($history) !== $ym) {
                continue;
            }
            $month = Carbon::createFromFormat('Y-m-d', $ym.'-01')->startOfDay();

            return (object) [
                'pk' => (int) $c->pk, 'poss' => (int) $c->poss, 'reading' => (int) $c->curr_month_elec_red,
                'meter' => (string) $c->meter_one, 'month' => $month, 'next' => $month->copy()->addMonthNoOverflow(),
            ];
        }
        $this->markTestSkipped("no single-meter latest month in {$table}");
    }

    private function ym($month, $year): ?string
    {
        try {
            return Carbon::parse('1 '.$month.' '.$year)->format('Y-m');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function regular(int $pk, int $reading, Carbon $month)
    {
        return $this->actor()->postJson(route('admin.estate.update-meter-reading.store'), [
            'readings' => [['pk' => $pk, 'meter_slot' => 1, 'selected' => 1, 'curr_month_elec_red' => (string) $reading]],
            'reading_bill_month' => $month->format('Y-m'),
            'reading_current_date' => $month->copy()->addDays(14)->toDateString(),
        ]);
    }

    private function other(int $pk, int $reading, Carbon $month)
    {
        return $this->actor()->postJson(route('admin.estate.update-meter-reading-of-other.store'), [
            'readings' => [['pk' => $pk, 'meter_slot' => 1, 'selected' => 1, 'curr_month_elec_red' => (string) $reading]],
            'reading_bill_month' => $month->format('Y-m'),
            'reading_meter_reading_date' => $month->copy()->addDays(14)->toDateString(),
        ]);
    }

    private function actor()
    {
        return $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin'])->withHeaders(['X-Requested-With' => 'XMLHttpRequest']);
    }
}
