<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\EstateController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * REAL two-process races on Estate allotment decisions (PR #349 F-004 and its follow-ups).
 * Each read its "may I?" state without a lock, so two overlapping requests could both
 * pass the check and both write. In every race the first action holds its transaction
 * open for 3 s and the second starts 1 s later:
 *
 *  - two approvals of one change request          → one succeeds, the other is refused
 *  - two change requests approved onto one house  → one succeeds, the other sees it occupied
 *  - two allotments of one new request            → one pending possession, not two
 *  - an approval racing a rejection               → the rejection is refused, no remark
 *
 * And writers of DIFFERENT houses, both paused right after their occupancy check so each
 * holds what that check locked while the other writes (F-007: share-mode scans deadlocked
 * here). Each runs 10 times; every run must end with both writes made and no deadlock:
 *
 *  - approve × approve, allot × allot, approve × allot, Possession for Others × itself
 *
 * The child processes COMMIT, so this cannot run inside a rolled-back transaction.
 * It is skipped unless SARGAM_ALLOW_COMMITTED_PROBES names the database, and it puts
 * back every row it can touch (tearDown).
 *
 *   SARGAM_ALLOW_COMMITTED_PROBES=<db> \
 *     php vendor/bin/phpunit tests/Feature/EstateChangeRequestConcurrentApprovalTest.php
 *
 * @group concurrency
 */
class EstateChangeRequestConcurrentApprovalTest extends TestCase
{
    private const RUNS = 10;

    /** @var array<string, mixed> */
    private array $fixture = [];

    /** @var array<string, array<int, array<string, mixed>>> table => pk => full row */
    private array $snapshot = [];

    protected function setUp(): void
    {
        parent::setUp();

        $db = DB::connection()->getDatabaseName();
        if (env('SARGAM_ALLOW_COMMITTED_PROBES') !== $db) {
            $this->markTestSkipped("commits probe rows; set SARGAM_ALLOW_COMMITTED_PROBES={$db} to run");
        }

        $crs = DB::table('estate_change_home_req_details as c')
            ->join('estate_home_request_details as h', 'h.pk', '=', 'c.estate_home_req_details_pk')
            ->where('c.change_ap_dis_status', 0)->where('c.estate_change_hac_status', 1)
            ->whereNotNull('h.employee_pk')
            ->orderBy('c.pk')->limit(2)
            ->get(['c.pk', 'c.estate_home_req_details_pk as home_req']);
        $vacant = $this->vacantHouses();
        $admin = DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')
            ->where('r.name', 'Estate Admin')->where('m.model_type', User::class)->orderBy('m.model_id')->value('m.model_id');
        $newReqs = $this->eligibleNewRequests($vacant);
        [$newReq, $allotHouses] = collect($newReqs)->first(fn ($c) => count($c[1]) >= 2) ?? [null, []];
        if ($crs->count() < 2 || count($vacant) < 2 || ! $admin || ! $newReq) {
            $this->markTestSkipped('needs two pending change requests at HAC, a new request at HAC with no possession, vacant houses and an Estate Admin');
        }
        // A second new request, on a house other than the first one's, for allot × allot.
        [$newReq2, $allot2] = [null, null];
        foreach ($newReqs as [$pk, $houses]) {
            $house = collect($houses)->first(fn ($h) => $h !== $allotHouses[0]);
            if ($pk !== $newReq && $house) {
                [$newReq2, $allot2] = [$pk, $house];
                break;
            }
        }

        // For the other allotment screens: requesters from outside with no possession yet, and an
        // LBSNAA allotment still waiting for its possession details (what Add Possession completes).
        $otherReqs = DB::table('estate_other_req as r')
            ->whereNotExists(fn ($q) => $q->from('estate_possession_other as p')->whereColumn('p.estate_other_req_pk', 'r.pk'))
            ->orderBy('r.pk')->limit(2)->pluck('r.pk')->map(fn ($pk) => (int) $pk)->all();
        $otherReq = $otherReqs[0] ?? null;
        // Possession for Others also refuses a house still flagged used, so it needs flag-free ones.
        $flagFree = DB::table('estate_house_master')->whereIn('pk', $vacant)->whereRaw('COALESCE(used_home_status, 0) = 0')
            ->orderBy('pk')->limit(2)->pluck('pk')->map(fn ($pk) => (int) $pk)->all();
        $pending = DB::table('estate_possession_details as p')
            ->join('estate_home_request_details as h', 'h.pk', '=', 'p.estate_home_request_details')
            ->where('h.hac_status', 1)->where('p.possession_date', '<=', '1900-01-01')->where('p.return_home_status', 0)
            ->orderBy('p.pk')->first(['p.pk', 'p.estate_home_request_details as home_req', 'h.employee_pk']);
        $pendingHouse = $pending ? $this->houseEligibleFor((int) $pending->employee_pk, $vacant) : null;

        $homeReqs = array_merge($crs->pluck('home_req')->all(), [$newReq], $newReq2 ? [$newReq2] : [], $pending ? [(int) $pending->home_req] : []);
        $possessions = DB::table('estate_possession_details')->whereIn('estate_home_request_details', $homeReqs)->get();
        $this->fixture = [
            'cr1' => (int) $crs[0]->pk,
            'cr2' => (int) $crs[1]->pk,
            'home_req1' => (int) $crs[0]->home_req,
            'home_req2' => (int) $crs[1]->home_req,
            'new_req' => $newReq,
            'houseA' => $vacant[0],
            'houseB' => $vacant[1],
            'allotA' => $allotHouses[0],
            'allotB' => $allotHouses[1],
            'new_req2' => $newReq2,
            'allot2' => $allot2,
            'home_reqs' => $homeReqs,
            'admin' => (int) $admin,
            'other_req' => $otherReq,
            'other_reqs' => $otherReqs,
            'flag_free' => $flagFree,
            'pending' => $pending,
            'pending_house' => $pendingHouse,
            'max_possession' => (int) DB::table('estate_possession_details')->max('pk'),
            'max_possession_other' => (int) DB::table('estate_possession_other')->max('pk'),
            'max_reading' => (int) DB::table('estate_month_reading_details')->max('pk'),
        ];

        // Every row an allotment decision can write: the requests, the possessions, their readings
        // and every house involved.
        $this->snap('estate_change_home_req_details', $crs->pluck('pk')->all());
        $this->snap('estate_home_request_details', $homeReqs);
        $this->snap('estate_possession_details', $possessions->pluck('pk')->all());
        $this->snap('estate_house_master', array_merge($vacant, $allotHouses, $possessions->pluck('estate_house_master_pk')->filter()->all()));
        $this->snap('estate_other_req', $otherReqs);
        $this->snap('estate_month_reading_details', DB::table('estate_month_reading_details')
            ->whereIn('estate_possession_details_pk', $possessions->pluck('pk')->all())->pluck('pk')->all());
    }

    /** First vacant house this employee may be allotted, or null. */
    private function houseEligibleFor(int $employeePk, array $vacant): ?int
    {
        $controller = app(EstateController::class);
        $employee = (new \ReflectionMethod($controller, 'resolveEmployeeMasterCanonicalPk'))->getClosure($controller)($employeePk);
        if (! $employee) {
            return null;
        }
        $allowed = (new \ReflectionMethod($controller, 'getEligibleHousePksByEmployeePk'))->getClosure($controller)($employee);
        $houses = $allowed->isEmpty() ? $vacant : array_values(array_intersect($vacant, $allowed->map(fn ($h) => (int) $h)->all()));

        return $houses[0] ?? null;
    }

    protected function tearDown(): void
    {
        $left = $this->fixture ? $this->restore() : null;

        parent::tearDown();

        if ($left !== null) {
            $this->assertSame([], $left, 'every probed row restored and every probe possession removed');
        }
    }

    /**
     * Puts back every row a race can write and removes the possessions it added.
     *
     * @return list<string> what is still not as it was (empty when restored)
     */
    private function restore(): array
    {
        $possessionPks = DB::table('estate_possession_details')->whereIn('estate_home_request_details', $this->fixture['home_reqs'])->pluck('pk');
        DB::table('estate_month_reading_details')->whereIn('estate_possession_details_pk', $possessionPks)
            ->where('pk', '>', $this->fixture['max_reading'])->delete();
        DB::table('estate_possession_details')
            ->whereIn('estate_home_request_details', $this->fixture['home_reqs'])
            ->where('pk', '>', $this->fixture['max_possession'])
            ->delete();
        $otherPks = DB::table('estate_possession_other')->whereIn('estate_other_req_pk', $this->fixture['other_reqs'])
            ->where('pk', '>', $this->fixture['max_possession_other'])->pluck('pk');
        DB::table('estate_month_reading_details_other')->whereIn('estate_possession_other_pk', $otherPks)->delete();
        DB::table('estate_possession_other')->whereIn('pk', $otherPks)->delete();
        foreach ($this->snapshot as $table => $rows) {
            foreach ($rows as $pk => $row) {
                // Every column, including ON UPDATE timestamps, so the restore leaves no trace.
                // A row the race deleted (a house) is put back whole.
                DB::table($table)->where('pk', $pk)->exists()
                    ? DB::table($table)->where('pk', $pk)->update($row)
                    : DB::table($table)->insert($row);
            }
        }
        $left = [];
        foreach ($this->snapshot as $table => $rows) {
            foreach ($rows as $pk => $row) {
                if ((array) DB::table($table)->where('pk', $pk)->first() != $row) {
                    $left[] = "$table#$pk";
                }
            }
        }
        if (DB::table('estate_possession_details')->whereIn('estate_home_request_details', $this->fixture['home_reqs'])
            ->where('pk', '>', $this->fixture['max_possession'])->exists()) {
            $left[] = 'probe possession';
        }

        return $left;
    }

    public function test_two_concurrent_approvals_allot_exactly_one_house(): void
    {
        [$holder, $second] = $this->race(['approve', $this->fixture['cr1'], $this->fixture['houseA']], ['approve', $this->fixture['cr1'], $this->fixture['houseB']]);

        $this->assertSucceeded($holder);
        $this->assertSame(409, $second['status'], 'the concurrent approval is refused: '.json_encode($second));
        $this->assertStringContainsString('already been approved', $second['body']['message']);
        $this->assertWaited($second);

        $possessions = $this->possessionsOf($this->fixture['home_req1']);
        $this->assertCount(max(1, count($this->snapshotPossessionsOf($this->fixture['home_req1']))), $possessions, 'exactly one allotment for the request');
        $this->assertSame([$this->fixture['houseA']], array_values(array_unique($possessions)));
        $this->assertSame(1, $this->decision($this->fixture['cr1']));
        $this->assertHouseUntouched($this->fixture['houseB']);
    }

    public function test_two_change_requests_cannot_be_approved_onto_one_house_at_once(): void
    {
        [$holder, $second] = $this->race(['approve', $this->fixture['cr1'], $this->fixture['houseA']], ['approve', $this->fixture['cr2'], $this->fixture['houseA']]);

        $this->assertSucceeded($holder);
        $this->assertSame(422, $second['status'], 'the second request is refused the taken house: '.json_encode($second));
        $this->assertStringContainsString('already occupied', $second['body']['message']);
        $this->assertWaited($second);

        $this->assertSame(1, DB::table('estate_possession_details')->where('estate_house_master_pk', $this->fixture['houseA'])->where('return_home_status', 0)->count(), 'one occupant for the house');
        $this->assertSame(0, $this->decision($this->fixture['cr2']), 'the refused request is still pending');
        $this->assertSame($this->snapshotPossessionsOf($this->fixture['home_req2']), $this->possessionsOf($this->fixture['home_req2']), 'the refused request kept its possession');
    }

    public function test_two_concurrent_allotments_of_one_request_leave_one_possession(): void
    {
        [$holder, $second] = $this->race(['allot', $this->fixture['new_req'], $this->fixture['allotA']], ['allot', $this->fixture['new_req'], $this->fixture['allotB']]);

        // Re-allotting a pending request is allowed: the later allotment moves the one possession.
        $this->assertSucceeded($holder);
        $this->assertSucceeded($second);
        $this->assertWaited($second);

        $this->assertSame([$this->fixture['allotB']], $this->possessionsOf($this->fixture['new_req']), 'one pending possession, on the later house');
        $this->assertSame(1, (int) DB::table('estate_house_master')->where('pk', $this->fixture['allotB'])->value('used_home_status'));
        $this->assertHouseUntouched($this->fixture['allotA'], 'the house given up is free again');
    }

    public function test_a_rejection_racing_an_approval_is_refused(): void
    {
        [$holder, $second] = $this->race(['approve', $this->fixture['cr1'], $this->fixture['houseA']], ['disapprove', $this->fixture['cr1'], 'probe rejection']);

        $this->assertSucceeded($holder);
        $this->assertSame(409, $second['status'], 'the rejection is refused: '.json_encode($second));
        $this->assertStringContainsString('already been approved', $second['body']['message']);
        $this->assertWaited($second);

        $this->assertSame(1, $this->decision($this->fixture['cr1']), 'the approval stands');
        $this->assertNull(DB::table('estate_change_home_req_details')->where('pk', $this->fixture['cr1'])->value('remarks'), 'no rejection remark');
    }

    public function test_possession_for_others_cannot_take_a_house_being_approved(): void
    {
        $house = $this->fixture['houseA'];
        if (! $this->fixture['other_req']) {
            $this->markTestSkipped('needs a requester for others with no possession');
        }

        [$holder, $second] = $this->race(['approve', $this->fixture['cr1'], $house], ['store-other', 0, json_encode($this->housePayload($house) + [
            'estate_other_req_pk' => $this->fixture['other_req'],
            'allotment_date' => now()->toDateString(),
            'possession_date_oth' => now()->toDateString(),
            'meter_reading_oth' => '100',
        ])]);

        $this->assertSucceeded($holder);
        $this->assertRefusedAsOccupied($second);
        $this->assertSame(0, DB::table('estate_possession_other')->where('estate_other_req_pk', $this->fixture['other_req'])->count(), 'no possession for the second requester');
        $this->assertOneOccupant($house);
    }

    public function test_add_possession_cannot_take_a_house_being_approved(): void
    {
        $house = $this->fixture['pending_house'];
        if (! $this->fixture['pending'] || ! $house) {
            $this->markTestSkipped('needs a pending LBSNAA allotment and a vacant house its employee may take');
        }
        $pending = $this->fixture['pending'];

        [$holder, $second] = $this->race(['approve', $this->fixture['cr1'], $house], ['store-details', 0, json_encode($this->housePayload($house) + [
            'estate_home_request_details_pk' => (int) $pending->home_req,
            'allotment_date' => now()->toDateString(),
            'possession_date' => now()->toDateString(),
            'electric_meter_reading_primary' => '100',
        ])]);

        $this->assertSucceeded($holder);
        $this->assertRefusedAsOccupied($second);
        $this->assertSame(
            (int) $this->snapshot['estate_possession_details'][$pending->pk]['estate_house_master_pk'],
            (int) DB::table('estate_possession_details')->where('pk', $pending->pk)->value('estate_house_master_pk'),
            'the pending allotment kept its house'
        );
        $this->assertOneOccupant($house);
    }

    public function test_a_house_being_approved_cannot_be_deleted(): void
    {
        $house = $this->fixture['houseA'];

        [$holder, $second] = $this->race(['approve', $this->fixture['cr1'], $house], ['delete-house', $house, '']);

        $this->assertSucceeded($holder);
        $this->assertSame(422, $second['status'], 'the delete is refused: '.json_encode($second));
        $this->assertStringContainsString('occupied', $second['body']['message']);
        $this->assertWaited($second);
        $this->assertTrue(DB::table('estate_house_master')->where('pk', $house)->exists(), 'the allotted house still exists');
    }

    public function test_approvals_onto_different_houses_at_once_both_succeed(): void
    {
        $f = $this->fixture;
        $this->runTogether(['approve', $f['cr1'], $f['houseA']], ['approve', $f['cr2'], $f['houseB']], function () use ($f) {
            $this->assertContains($f['houseA'], $this->possessionsOf($f['home_req1']), 'first approval wrote its possession');
            $this->assertContains($f['houseB'], $this->possessionsOf($f['home_req2']), 'second approval wrote its possession');
            $this->assertSame([1, 1], [$this->decision($f['cr1']), $this->decision($f['cr2'])], 'both decisions recorded');
        });
    }

    public function test_allotments_of_different_requests_at_once_both_succeed(): void
    {
        $f = $this->fixture;
        if (! $f['new_req2']) {
            $this->markTestSkipped('needs a second new request at HAC, with no possession, eligible for another vacant house');
        }
        $this->runTogether(['allot', $f['new_req'], $f['allotA']], ['allot', $f['new_req2'], $f['allot2']], function () use ($f) {
            $this->assertSame([$f['allotA']], $this->possessionsOf($f['new_req']));
            $this->assertSame([$f['allot2']], $this->possessionsOf($f['new_req2']));
        });
    }

    public function test_an_approval_and_an_allotment_of_different_houses_at_once_both_succeed(): void
    {
        $f = $this->fixture;
        $approveHouse = $f['houseA'] !== $f['allotA'] ? $f['houseA'] : $f['houseB'];
        $this->runTogether(['approve', $f['cr1'], $approveHouse], ['allot', $f['new_req'], $f['allotA']], function () use ($f, $approveHouse) {
            $this->assertContains($approveHouse, $this->possessionsOf($f['home_req1']));
            $this->assertSame(1, $this->decision($f['cr1']));
            $this->assertSame([$f['allotA']], $this->possessionsOf($f['new_req']));
            $this->assertOneOccupant($approveHouse);
            $this->assertOneOccupant($f['allotA']);
        });
    }

    public function test_two_possessions_for_others_at_once_both_succeed(): void
    {
        $f = $this->fixture;
        if (count($f['other_reqs']) < 2 || count($f['flag_free']) < 2) {
            $this->markTestSkipped('needs two requesters for others with no possession and two vacant houses not flagged used');
        }
        $save = fn (int $otherReq, int $house) => ['store-other', 0, json_encode($this->housePayload($house) + [
            'estate_other_req_pk' => $otherReq,
            'allotment_date' => now()->toDateString(),
            'possession_date_oth' => now()->toDateString(),
            'meter_reading_oth' => '100',
        ])];
        [$req1, $req2] = $f['other_reqs'];
        [$house1, $house2] = $f['flag_free'];
        $this->runTogether($save($req1, $house1), $save($req2, $house2), function () use ($req1, $req2, $house1, $house2) {
            foreach ([$req1 => $house1, $req2 => $house2] as $req => $house) {
                $this->assertSame([$house], DB::table('estate_possession_other')->where('estate_other_req_pk', $req)
                    ->pluck('estate_house_master_pk')->map(fn ($h) => (int) $h)->all(), "requester {$req} has its one possession");
                $this->assertOneOccupant($house);
            }
        });
    }

    /**
     * Runs both sides paused together self::RUNS times, restoring in between. Every run: both
     * 200, the pauses overlapped (both checks done before either wrote), $check holds, and the
     * server's deadlock counter did not move (a retried deadlock would still count).
     */
    private function runTogether(array $a, array $b, \Closure $check): void
    {
        for ($run = 1; $run <= self::RUNS; $run++) {
            $deadlocks = $this->deadlockCount();
            [$outA, $outB] = $this->race($a, $b, ['together', 'together']);

            $this->assertSucceeded($outA);
            $this->assertSucceeded($outB);
            $this->assertNotNull($outA['hook'], "run {$run}: side A reached its check: ".json_encode($outA));
            $this->assertNotNull($outB['hook'], "run {$run}: side B reached its check: ".json_encode($outB));
            $this->assertLessThan(min($outA['hook'][1], $outB['hook'][1]), max($outA['hook'][0], $outB['hook'][0]), "run {$run}: the two pauses overlapped");
            $check();
            if ($deadlocks !== null) {
                $this->assertSame($deadlocks, $this->deadlockCount(), "run {$run}: no deadlock on the server");
            }

            $this->assertSame([], $this->restore(), "run {$run}: restored");
        }
    }

    /** InnoDB's server-wide deadlock counter, or null where the metric is not readable. */
    private function deadlockCount(): ?int
    {
        try {
            $count = DB::table('information_schema.INNODB_METRICS')->where('NAME', 'lock_deadlocks')->value('COUNT');
        } catch (\Throwable $e) {
            return null;
        }

        return $count === null ? null : (int) $count;
    }

    /** The house's own Estate / Building / Unit Sub Type, as the possession forms post them. */
    private function housePayload(int $house): array
    {
        $row = DB::table('estate_house_master')->where('pk', $house)->first();

        return [
            'estate_campus_master_pk' => (int) $row->estate_campus_master_pk,
            'estate_block_master_pk' => (int) $row->estate_block_master_pk,
            'estate_unit_sub_type_master_pk' => (int) $row->estate_unit_sub_type_master_pk,
            'estate_house_master_pk' => $house,
        ];
    }

    private function assertRefusedAsOccupied(array $second): void
    {
        $message = (string) ($second['body']['message'] ?? $second['flash']['error'] ?? '');
        $this->assertStringContainsString('already occupied', $message, 'refused as occupied: '.json_encode($second));
        $this->assertNull($second['flash']['success'], 'no success message');
        $this->assertWaited($second);
    }

    private function assertOneOccupant(int $house): void
    {
        $details = DB::table('estate_possession_details')->where('estate_house_master_pk', $house)->where('return_home_status', 0)->count();
        $other = DB::table('estate_possession_other')->where('estate_house_master_pk', $house)->where('return_home_status', 0)->count();
        $this->assertSame(1, $details + $other, 'one occupant for the house');
    }

    private function assertSucceeded(array $out): void
    {
        $this->assertSame(200, $out['status'], 'succeeds: '.json_encode($out));
        $this->assertTrue($out['body']['success']);
    }

    private function assertWaited(array $second): void
    {
        $this->assertGreaterThan(1.5, $second['returned'] - $second['started'], 'the second action waited on the row lock');
    }

    private function assertHouseUntouched(int $house, string $message = 'the house is as it was'): void
    {
        $this->assertSame(
            $this->snapshot['estate_house_master'][$house]['used_home_status'],
            DB::table('estate_house_master')->where('pk', $house)->value('used_home_status'),
            $message
        );
    }

    private function decision(int $cr): int
    {
        return (int) DB::table('estate_change_home_req_details')->where('pk', $cr)->value('change_ap_dis_status');
    }

    /** @return list<int> house of each possession row of the request, in pk order */
    private function possessionsOf(int $homeReq): array
    {
        return DB::table('estate_possession_details')->where('estate_home_request_details', $homeReq)->orderBy('pk')
            ->pluck('estate_house_master_pk')->map(fn ($h) => (int) $h)->all();
    }

    /** @return list<int> */
    private function snapshotPossessionsOf(int $homeReq): array
    {
        return collect($this->snapshot['estate_possession_details'] ?? [])
            ->where('estate_home_request_details', $homeReq)->sortKeys()
            ->pluck('estate_house_master_pk')->map(fn ($h) => (int) $h)->values()->all();
    }

    /** @return list<int> vacant houses with no current occupant */
    private function vacantHouses(): array
    {
        $occupied = DB::table('estate_possession_details')->where('return_home_status', 0)->whereNotNull('estate_house_master_pk')->pluck('estate_house_master_pk')
            ->merge(DB::table('estate_possession_other')->where('return_home_status', 0)->whereNotNull('estate_house_master_pk')->pluck('estate_house_master_pk'))
            ->all();

        return DB::table('estate_house_master')->where('vacant_renovation_status', 1)->whereNotIn('pk', $occupied)
            ->orderBy('pk')->pluck('pk')->map(fn ($pk) => (int) $pk)->all();
    }

    /**
     * New requests at HAC with no possession yet, each with the vacant houses its employee is
     * eligible for (the allotment refuses any other).
     *
     * @param  list<int>  $vacant
     * @return list<array{0: int, 1: list<int>}>
     */
    private function eligibleNewRequests(array $vacant): array
    {
        $controller = app(EstateController::class);
        $canonical = (new \ReflectionMethod($controller, 'resolveEmployeeMasterCanonicalPk'))->getClosure($controller);
        $eligible = (new \ReflectionMethod($controller, 'getEligibleHousePksByEmployeePk'))->getClosure($controller);

        $requests = DB::table('estate_home_request_details as h')
            ->where('h.hac_status', 1)->where('h.change_status', 0)->whereNotNull('h.employee_pk')
            ->whereNotExists(fn ($q) => $q->from('estate_possession_details as p')->whereColumn('p.estate_home_request_details', 'h.pk'))
            ->orderBy('h.pk')->get(['h.pk', 'h.employee_pk']);
        $found = [];
        foreach ($requests as $request) {
            $employee = $canonical((int) $request->employee_pk);
            if (! $employee) {
                continue;
            }
            $allowed = $eligible($employee);
            $houses = $allowed->isEmpty() ? $vacant : array_values(array_intersect($vacant, $allowed->map(fn ($h) => (int) $h)->all()));
            if ($houses) {
                $found[] = [(int) $request->pk, array_slice($houses, 0, 2)];
            }
        }

        return $found;
    }

    /**
     * Runs $first as the holder and $second 1 s later (or, with ['together', 'together'], both at
     * once); each is [action, id, value].
     *
     * @return array{0: array, 1: array} first output, second output
     */
    private function race(array $first, array $second, array $roles = ['holder', 'second']): array
    {
        $worker = base_path('tests/Support/estate_change_request_approve_race_worker.php');
        $start = function (string $role, array $side) use ($worker) {
            [$action, $id, $value] = $side;
            $args = [PHP_BINARY, $worker, $role, $action, (string) $id, (string) $value, (string) $this->fixture['admin']];
            $proc = proc_open($args, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());

            return [$proc, $pipes];
        };

        $procs = [$start($roles[0], $first), $start($roles[1], $second)];
        $out = [];
        foreach ($procs as $i => [$proc, $pipes]) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $this->assertSame(0, proc_close($proc), "{$roles[$i]} worker failed: {$stderr}");
            $out[$i] = json_decode(trim($stdout), true);
            $this->assertIsArray($out[$i], "{$roles[$i]} printed no result: {$stdout} {$stderr}");
        }

        return [$out[0], $out[1]];
    }

    /** @param  list<int|string>  $pks */
    private function snap(string $table, array $pks): void
    {
        foreach (DB::table($table)->whereIn('pk', array_unique($pks))->get() as $row) {
            $this->snapshot[$table][$row->pk] = (array) $row;
        }
    }
}
