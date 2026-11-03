<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A REAL two-process race on HAC approval of one change request (PR #349 F-004). The
 * approval read change_ap_dis_status without a lock, so two approvals could both pass
 * the "already decided" check and both allot a house. The first approval holds its
 * transaction open for 3 s; the second starts 1 s later with a different house. Exactly
 * one may succeed, the other must wait and be refused, and the possession must end on
 * the winner's house with the loser's house untouched.
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

        $cr = DB::table('estate_change_home_req_details as c')
            ->join('estate_home_request_details as h', 'h.pk', '=', 'c.estate_home_req_details_pk')
            ->where('c.change_ap_dis_status', 0)->where('c.estate_change_hac_status', 1)
            ->whereNotNull('h.employee_pk')
            ->orderBy('c.pk')
            ->first(['c.pk', 'c.estate_home_req_details_pk as home_req']);
        $occupied = DB::table('estate_possession_details')->where('return_home_status', 0)->whereNotNull('estate_house_master_pk')->pluck('estate_house_master_pk')
            ->merge(DB::table('estate_possession_other')->where('return_home_status', 0)->whereNotNull('estate_house_master_pk')->pluck('estate_house_master_pk'))
            ->all();
        $houses = DB::table('estate_house_master')->where('vacant_renovation_status', 1)->whereNotIn('pk', $occupied)
            ->orderBy('pk')->limit(2)->pluck('pk')->map(fn ($pk) => (int) $pk)->all();
        $admin = DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')
            ->where('r.name', 'Estate Admin')->where('m.model_type', User::class)->orderBy('m.model_id')->value('m.model_id');
        if (! $cr || count($houses) < 2 || ! $admin) {
            $this->markTestSkipped('needs a pending change request at HAC, two vacant houses and an Estate Admin');
        }

        $possessions = DB::table('estate_possession_details')->where('estate_home_request_details', $cr->home_req)->get();
        $this->fixture = [
            'cr' => (int) $cr->pk,
            'home_req' => (int) $cr->home_req,
            'houseA' => $houses[0],
            'houseB' => $houses[1],
            'admin' => (int) $admin,
            'max_possession' => (int) DB::table('estate_possession_details')->max('pk'),
        ];

        // Every row an approval can write: the request pair, the possession(s) and every house involved.
        $this->snap('estate_change_home_req_details', [$cr->pk]);
        $this->snap('estate_home_request_details', [$cr->home_req]);
        $this->snap('estate_possession_details', $possessions->pluck('pk')->all());
        $this->snap('estate_house_master', array_merge($houses, $possessions->pluck('estate_house_master_pk')->filter()->all()));
    }

    protected function tearDown(): void
    {
        if ($this->fixture) {
            DB::table('estate_possession_details')
                ->where('estate_home_request_details', $this->fixture['home_req'])
                ->where('pk', '>', $this->fixture['max_possession'])
                ->delete();
            foreach ($this->snapshot as $table => $rows) {
                foreach ($rows as $pk => $row) {
                    // Every column, including ON UPDATE timestamps, so the restore leaves no trace.
                    DB::table($table)->where('pk', $pk)->update($row);
                }
            }
            $drift = [];
            foreach ($this->snapshot as $table => $rows) {
                foreach ($rows as $pk => $row) {
                    if ((array) DB::table($table)->where('pk', $pk)->first() != $row) {
                        $drift[] = "$table#$pk";
                    }
                }
            }
        }

        parent::tearDown();

        if (isset($drift)) {
            $this->assertSame([], $drift, 'every probed row restored');
        }
    }

    public function test_two_concurrent_approvals_allot_exactly_one_house(): void
    {
        [$holder, $second] = $this->race();

        $this->assertSame(200, $holder['status'], 'the first approval succeeds: '.json_encode($holder));
        $this->assertTrue($holder['body']['success']);
        $this->assertSame(409, $second['status'], 'the concurrent approval is refused: '.json_encode($second));
        $this->assertStringContainsString('already been approved', $second['body']['message']);
        $this->assertGreaterThan(1.5, $second['returned'] - $second['started'], 'the second approval waited on the row lock');

        $possessions = DB::table('estate_possession_details')->where('estate_home_request_details', $this->fixture['home_req'])->get();
        $this->assertCount(max(1, count($this->snapshot['estate_possession_details'] ?? [])), $possessions, 'exactly one allotment for the request');
        $this->assertSame([$this->fixture['houseA']], $possessions->pluck('estate_house_master_pk')->map(fn ($h) => (int) $h)->unique()->values()->all());
        $this->assertSame(1, (int) DB::table('estate_change_home_req_details')->where('pk', $this->fixture['cr'])->value('change_ap_dis_status'));
        $this->assertSame(
            $this->snapshot['estate_house_master'][$this->fixture['houseB']]['used_home_status'],
            DB::table('estate_house_master')->where('pk', $this->fixture['houseB'])->value('used_home_status'),
            'the refused approval left its house as it was'
        );
    }

    /** @return array{0: array, 1: array} holder output, second output */
    private function race(): array
    {
        $worker = base_path('tests/Support/estate_change_request_approve_race_worker.php');
        $start = function (string $role, int $house) use ($worker) {
            $args = [PHP_BINARY, $worker, $role, (string) $this->fixture['cr'], (string) $house, (string) $this->fixture['admin']];
            $proc = proc_open($args, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());

            return [$proc, $pipes];
        };

        $procs = ['holder' => $start('holder', $this->fixture['houseA']), 'second' => $start('second', $this->fixture['houseB'])];
        $out = [];
        foreach ($procs as $role => [$proc, $pipes]) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $this->assertSame(0, proc_close($proc), "{$role} worker failed: {$stderr}");
            $out[$role] = json_decode(trim($stdout), true);
            $this->assertIsArray($out[$role], "{$role} printed no result: {$stdout} {$stderr}");
        }

        return [$out['holder'], $out['second']];
    }

    /** @param  list<int|string>  $pks */
    private function snap(string $table, array $pks): void
    {
        foreach (DB::table($table)->whereIn('pk', array_unique($pks))->get() as $row) {
            $this->snapshot[$table][$row->pk] = (array) $row;
        }
    }
}
