<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LeaveApplicationService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A REAL two-process race between an officer trainee's own leave submit and an
 * operator's Leave on Behalf entry for the same trainee and day (PR #334 F-070). The
 * OT path checked overlap outside its transaction and took no lock, so the two could
 * interleave and both commit. Whichever submit starts first holds its transaction
 * open for 4 s; the other starts 1 s later. Exactly one leave may result, and the
 * later submit must wait and then be refused with the overlap error. Control: the
 * same two submits on different days both commit.
 *
 * The child processes COMMIT, so this cannot run inside a rolled-back transaction.
 * It is skipped unless SARGAM_ALLOW_COMMITTED_PROBES names the database, and it
 * deletes exactly the rows it created (finally block).
 *
 *   SARGAM_ALLOW_COMMITTED_PROBES=<db> \
 *     php vendor/bin/phpunit tests/Feature/LeaveOtAndOnBehalfConcurrentSubmitTest.php
 *
 * @group concurrency
 */
class LeaveOtAndOnBehalfConcurrentSubmitTest extends TestCase
{
    private const REASON = 'PR #334 F-070 concurrency probe - delete after run';

    /** @var array<string, mixed> */
    private array $fixture = [];

    private ?object $restoreCourse = null;

    private array $probeConfigPks = [];

    protected function setUp(): void
    {
        parent::setUp();

        $db = DB::connection()->getDatabaseName();
        if (env('SARGAM_ALLOW_COMMITTED_PROBES') !== $db) {
            $this->markTestSkipped("commits probe rows; set SARGAM_ALLOW_COMMITTED_PROBES={$db} to run");
        }

        $running = fn ($q) => $q->whereNull('c.end_date')->orWhereDate('c.end_date', '>=', now()->toDateString());
        $base = DB::table('user_credentials as u')
            ->join('student_master_course__map as m', 'm.student_master_pk', '=', 'u.user_id')
            ->join('course_master as c', 'c.pk', '=', 'm.course_master_pk')
            ->where('u.user_category', 'S')
            ->where('m.active_inactive', 1)
            ->orderByDesc('m.pk');
        $row = (clone $base)->where('c.active_inactive', 1)->where($running)->first(['u.pk as user_pk', 'm.course_master_pk']);
        if (! $row) {
            // No running course on the copy: one is made running and restored in tearDown().
            $row = $base->first(['u.pk as user_pk', 'm.course_master_pk']);
            if ($row) {
                $this->restoreCourse = DB::table('course_master')->where('pk', $row->course_master_pk)->first(['pk', 'active_inactive', 'end_date', 'modified_date']);
                DB::table('course_master')->where('pk', $row->course_master_pk)
                    ->update(['active_inactive' => 1, 'end_date' => now()->addYears(3)->toDateString()]);
            }
        }

        $stationedNature = DB::table('leave_nature_master')->where('leave_type', 'STATIONED_LEAVE')->where('active_inactive', 1)->value('pk');
        $leaveNature = DB::table('leave_nature_master')->where('leave_type', 'LEAVE')->where('active_inactive', 1)->value('pk');
        $superAdmin = DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')
            ->where('r.name', 'Super Admin')->where('m.model_type', User::class)
            ->orderBy('m.model_id')->value('m.model_id');
        if (! $row || ! $stationedNature || ! $leaveNature || ! $superAdmin) {
            $this->markTestSkipped('needs an OT login with an active enrolment, active STATIONED_LEAVE and LEAVE natures and a Super Admin');
        }

        // The course and student the OT's own path resolves for this login.
        $context = app(LeaveApplicationService::class)->resolveStudentContext((int) $row->user_pk);

        $this->fixture = [
            'ot_user' => (int) $row->user_pk,
            'operator' => (int) $superAdmin,
            'student' => (int) $context['student_pk'],
            'course' => (int) $context['course_pk'],
            'stationed_nature' => (int) $stationedNature,
            'leave_nature' => (int) $leaveNature,
            'max_before' => (int) DB::table('leave_application')->max('pk'),
        ];
    }

    protected function tearDown(): void
    {
        if ($this->fixture) {
            $pks = DB::table('leave_application')
                ->where('pk', '>', $this->fixture['max_before'])
                ->where('reason', self::REASON)
                ->pluck('pk')->all();
            if ($pks) {
                DB::table('leave_application_attachment')->whereIn('leave_application_pk', $pks)->delete();
                DB::table('leave_application')->whereIn('pk', $pks)->delete();
            }
            if ($this->probeConfigPks) {
                DB::table('stationed_leave_master')->whereIn('pk', $this->probeConfigPks)->delete();
            }
            if ($this->restoreCourse) {
                DB::table('course_master')->where('pk', $this->restoreCourse->pk)->update([
                    'active_inactive' => $this->restoreCourse->active_inactive,
                    'end_date' => $this->restoreCourse->end_date,
                    // ON UPDATE CURRENT_TIMESTAMP: without it the restore itself changes the row.
                    'modified_date' => $this->restoreCourse->modified_date,
                ]);
            }
            $left = DB::table('leave_application')->where('reason', self::REASON)->count();
        }

        parent::tearDown();

        if (isset($left)) {
            $this->assertSame(0, $left, 'probe rows cleaned up');
        }
    }

    /** A probe day far ahead, with a stationed-leave configuration that needs no approval and has no cut-off. */
    private function probeDay(int $offset): string
    {
        $day = now()->addDays(440 + $offset)->toDateString();
        $this->probeConfigPks[] = DB::table('stationed_leave_master')->insertGetId([
            'course_master_pk' => $this->fixture['course'],
            'effective_from' => $day,
            'apply_cutoff_time' => null,
            'is_faculty_approval_required' => 0,
            'active_inactive' => 1,
            'created_date' => now(),
            'modified_date' => now(),
        ]);

        return $day;
    }

    /** [path, user pk, payload] for one side of the race. */
    private function submit(string $path, string $day): array
    {
        $common = [
            'from_date' => $day,
            'to_date' => $day,
            'time_from' => '09:00',
            'time_to' => '18:00',
            'contact_number' => '9876543210',
            'reason' => self::REASON,
        ];

        return $path === 'ot'
            ? ['ot', $this->fixture['ot_user'], $common + [
                'leave_type' => 'STATIONED_LEAVE',
                'leave_nature_master_pk' => $this->fixture['stationed_nature'],
                'submit_action' => 'submit',
            ]]
            : ['behalf', $this->fixture['operator'], $common + [
                'course_master_pk' => $this->fixture['course'],
                'student_master_pk' => $this->fixture['student'],
                'leave_nature_master_pk' => $this->fixture['leave_nature'],
            ]];
    }

    /** Runs $first as the holder and $second 1 s later; returns [holder output, second output, created rows]. */
    private function race(array $first, array $second): array
    {
        $worker = base_path('tests/Support/leave_on_behalf_race_worker.php');
        $files = [];
        $start = function (string $role, array $side) use ($worker, &$files) {
            [$path, $userPk, $payload] = $side;
            $files[] = $file = tempnam(sys_get_temp_dir(), 'lob');
            file_put_contents($file, json_encode($payload));
            $proc = proc_open([PHP_BINARY, $worker, $role, $file, (string) $userPk, $path], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());

            return [$proc, $pipes];
        };

        try {
            $procs = ['holder' => $start('holder', $first), 'second' => $start('second', $second)];
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
        } finally {
            array_map('unlink', $files);
        }

        $created = DB::table('leave_application')
            ->where('pk', '>', $this->fixture['max_before'])
            ->where('student_master_pk', $this->fixture['student'])
            ->where('reason', self::REASON)
            ->get(['pk', 'status', 'applied_by_user_pk', 'from_date']);

        return [$out['holder'], $out['second'], $created];
    }

    private function assertOneLeaveAndTheLaterSubmitRefused(array $holder, array $second, $created): void
    {
        $this->assertCount(1, $created, 'exactly one leave for two concurrent submits of the same day; got '.json_encode([$holder, $second]));
        $this->assertSame([], $holder['errors'], 'the first submit succeeds');
        $this->assertArrayHasKey('from_date', $second['errors'], 'the later submit is refused with the overlap error');
        $this->assertStringContainsString('overlap', implode(' ', $second['errors']['from_date']));
        $this->assertGreaterThan(2.0, $second['returned'] - $second['started'], 'the later submit waited on the student-row lock');
    }

    public function test_operator_first_then_the_ot_submits_the_same_day(): void
    {
        $day = $this->probeDay(0);

        [$holder, $second, $created] = $this->race($this->submit('behalf', $day), $this->submit('ot', $day));

        $this->assertOneLeaveAndTheLaterSubmitRefused($holder, $second, $created);
        $this->assertNotNull($created->first()->applied_by_user_pk, "the operator's leave is the one stored");
    }

    public function test_ot_first_then_the_operator_submits_the_same_day(): void
    {
        $day = $this->probeDay(0);

        [$holder, $second, $created] = $this->race($this->submit('ot', $day), $this->submit('behalf', $day));

        $this->assertOneLeaveAndTheLaterSubmitRefused($holder, $second, $created);
        $this->assertNull($created->first()->applied_by_user_pk, "the OT's own leave is the one stored");
    }

    public function test_control_different_days_both_commit(): void
    {
        $operatorDay = $this->probeDay(0);
        $otDay = $this->probeDay(1);

        [$holder, $second, $created] = $this->race($this->submit('behalf', $operatorDay), $this->submit('ot', $otDay));

        $this->assertSame([], $holder['errors'], 'operator submit succeeds');
        $this->assertSame([], $second['errors'], 'OT submit on another day succeeds');
        $this->assertCount(2, $created, 'both leaves stored');
    }
}
