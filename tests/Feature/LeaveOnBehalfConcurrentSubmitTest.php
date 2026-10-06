<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LeaveApplicationService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A REAL two-connection race on Leave-on-Behalf store() (PR #334 F-022): two PHP
 * processes submit the same leave; the first holds its transaction open. Exactly
 * one Approved leave may result, and the second submit must wait on the lock and
 * then be refused with the overlap error.
 *
 * The child processes COMMIT, so this test cannot run inside a rolled-back
 * transaction. It is skipped unless SARGAM_ALLOW_COMMITTED_PROBES names the
 * database, and it deletes exactly the rows it created (finally block).
 *
 *   SARGAM_ALLOW_TESTS_ON=<db> SARGAM_ALLOW_COMMITTED_PROBES=<db> \
 *     php vendor/bin/phpunit tests/Feature/LeaveOnBehalfConcurrentSubmitTest.php
 *
 * @group concurrency
 */
class LeaveOnBehalfConcurrentSubmitTest extends TestCase
{
    private const REASON = 'PR #334 F-022 concurrency probe - delete after run';

    public function test_two_concurrent_submits_store_exactly_one_approved_leave(): void
    {
        $db = DB::connection()->getDatabaseName();
        if (env('SARGAM_ALLOW_COMMITTED_PROBES') !== $db) {
            $this->markTestSkipped("commits probe rows; set SARGAM_ALLOW_COMMITTED_PROBES={$db} to run");
        }

        $day = now()->addDays(420)->toDateString();
        $svc = app(LeaveApplicationService::class);

        $enrolment = DB::table('student_master_course__map as m')
            ->join('course_master as c', 'c.pk', '=', 'm.course_master_pk')
            ->where('m.active_inactive', 1)
            ->where('c.active_inactive', 1)
            ->where(fn ($q) => $q->whereNull('c.end_date')->orWhereDate('c.end_date', '>=', now()->toDateString()))
            ->orderBy('m.pk')
            ->first(['m.course_master_pk', 'm.student_master_pk']);
        $nature = DB::table('leave_nature_master')->where('leave_type', 'LEAVE')->where('active_inactive', 1)->value('pk');
        $superAdmin = DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')
            ->where('r.name', 'Super Admin')->where('m.model_type', User::class)
            ->orderBy('m.model_id')->value('m.model_id');
        if (! $enrolment || ! $nature || ! $superAdmin) {
            $this->markTestSkipped('needs a running enrolment, an active LEAVE nature and a Super Admin');
        }

        $maxBefore = (int) DB::table('leave_application')->max('pk');
        $probeConfigPk = null;
        $payloadFile = tempnam(sys_get_temp_dir(), 'lob');

        try {
            if (! $svc->stationedLeaveConfigured((int) $enrolment->course_master_pk, $day)) {
                // Effective only from the probe day onward, so no live flow sees it.
                $probeConfigPk = DB::table('stationed_leave_master')->insertGetId([
                    'course_master_pk' => $enrolment->course_master_pk,
                    'effective_from' => $day,
                    'is_faculty_approval_required' => 0,
                    'active_inactive' => 1,
                    'created_date' => now(),
                    'modified_date' => now(),
                ]);
            }

            file_put_contents($payloadFile, json_encode([
                'course_master_pk' => $enrolment->course_master_pk,
                'student_master_pk' => $enrolment->student_master_pk,
                'leave_nature_master_pk' => $nature,
                'from_date' => $day,
                'to_date' => $day,
                'time_from' => '09:00',
                'time_to' => '18:00',
                'contact_number' => '9876543210',
                'reason' => self::REASON,
            ]));

            $worker = base_path('tests/Support/leave_on_behalf_race_worker.php');
            $start = function (string $role) use ($worker, $payloadFile, $superAdmin) {
                $cmd = [PHP_BINARY, $worker, $role, $payloadFile, (string) $superAdmin];
                $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());

                return [$proc, $pipes];
            };

            [$holder, $hp] = $start('holder');
            [$second, $sp] = $start('second');

            $out = [];
            foreach ([['holder', $holder, $hp], ['second', $second, $sp]] as [$role, $proc, $pipes]) {
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $code = proc_close($proc);
                $this->assertSame(0, $code, "{$role} worker failed: {$stderr}");
                $out[$role] = json_decode(trim($stdout), true);
                $this->assertIsArray($out[$role], "{$role} printed no result: {$stdout} {$stderr}");
            }

            $created = DB::table('leave_application')
                ->where('pk', '>', $maxBefore)
                ->where('student_master_pk', $enrolment->student_master_pk)
                ->where('reason', self::REASON)
                ->get(['pk', 'status']);

            $this->assertCount(1, $created, 'exactly one leave for two concurrent submits; got '.json_encode($out));
            $this->assertSame(2, (int) $created->first()->status, 'stored Approved');
            $this->assertSame([], $out['holder']['errors'], 'the first submit succeeds');
            $this->assertArrayHasKey('from_date', $out['second']['errors'], 'the second submit is refused with the overlap error');
            $this->assertGreaterThan(
                2.0,
                $out['second']['returned'] - $out['second']['started'],
                'the second submit waited on the lock held by the first'
            );
        } finally {
            $pks = DB::table('leave_application')
                ->where('pk', '>', $maxBefore)
                ->where('reason', self::REASON)
                ->pluck('pk')->all();
            if ($pks) {
                DB::table('leave_application_attachment')->whereIn('leave_application_pk', $pks)->delete();
                DB::table('leave_application')->whereIn('pk', $pks)->delete();
            }
            if ($probeConfigPk) {
                DB::table('stationed_leave_master')->where('pk', $probeConfigPk)->delete();
            }
            @unlink($payloadFile);

            $this->assertSame(0, DB::table('leave_application')->where('reason', self::REASON)->count(), 'probe rows cleaned up');
        }
    }
}
