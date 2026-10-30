<?php

namespace Tests\Feature;

use App\Models\LeaveApplication;
use App\Services\LeaveApplicationService;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * leave_application.from_date / to_date are DATETIME since 2026_09_03, and stationed
 * leaves filed before time_from / time_to existed keep their times there. The overlap
 * guard compared those columns with date-only bounds, so a timed leave starting on the
 * last day of a new range was missed and Leave on Behalf stored a second Approved
 * leave (PR #334 F-065).
 *
 * Shadows the real table with a session TEMPORARY one (as StationedLeaveTimeBackfillTest
 * does), so the DATETIME schema is tested whatever the local database has migrated.
 */
class LeaveOverlapDatetimeColumnsTest extends TestCase
{
    use RollsBackAgainstAppDatabase {
        setUp as openRollbackTransaction;
        tearDown as rollBackTransaction;
    }

    private const STUDENT = 990001;

    private LeaveApplicationService $service;

    protected function setUp(): void
    {
        $this->openRollbackTransaction();

        DB::statement('CREATE TEMPORARY TABLE leave_application (
            pk BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            course_master_pk BIGINT UNSIGNED NOT NULL DEFAULT 0,
            student_master_pk BIGINT UNSIGNED NOT NULL DEFAULT 0,
            leave_type VARCHAR(30) NOT NULL,
            from_date DATETIME NOT NULL,
            to_date DATETIME NOT NULL,
            time_from TIME NULL,
            time_to TIME NULL,
            status TINYINT NOT NULL DEFAULT 0,
            active_inactive TINYINT NOT NULL DEFAULT 1
        )');

        $this->service = app(LeaveApplicationService::class);
    }

    protected function tearDown(): void
    {
        DB::statement('DROP TEMPORARY TABLE IF EXISTS leave_application');
        $this->rollBackTransaction();
    }

    private function approvedStationed(string $from, string $to): int
    {
        return (int) DB::table('leave_application')->insertGetId([
            'student_master_pk' => self::STUDENT,
            'leave_type' => LeaveApplication::TYPE_STATIONED_LEAVE,
            'from_date' => $from,
            'to_date' => $to,
            'status' => LeaveApplication::STATUS_APPROVED,
        ]);
    }

    public function test_a_timed_leave_on_the_last_day_of_the_range_blocks_a_second_leave(): void
    {
        $pk = $this->approvedStationed('2028-02-21 10:00:00', '2028-02-21 18:00:00');

        $found = $this->service->findOverlappingApplication(self::STUDENT, '2028-02-19', '2028-02-21');
        $this->assertSame($pk, $found?->pk);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->assertNoOverlap(self::STUDENT, '2028-02-19', '2028-02-21');
    }

    public function test_a_timed_leave_ending_on_the_first_day_of_the_range_blocks_it(): void
    {
        $pk = $this->approvedStationed('2028-02-17 10:00:00', '2028-02-19 09:00:00');

        $this->assertSame($pk, $this->service->findOverlappingApplication(self::STUDENT, '2028-02-19', '2028-02-21')?->pk);
    }

    public function test_a_timed_leave_inside_a_one_day_range_blocks_it(): void
    {
        $pk = $this->approvedStationed('2028-02-20 10:00:00', '2028-02-20 18:00:00');

        $this->assertSame($pk, $this->service->findOverlappingApplication(self::STUDENT, '2028-02-20', '2028-02-20')?->pk);
    }

    public function test_a_timed_leave_on_the_day_after_the_range_does_not_block_it(): void
    {
        $this->approvedStationed('2028-02-22 10:00:00', '2028-02-22 18:00:00');
        $this->approvedStationed('2028-02-17 10:00:00', '2028-02-18 18:00:00');

        $this->assertNull($this->service->findOverlappingApplication(self::STUDENT, '2028-02-19', '2028-02-21'));
        $this->service->assertNoOverlap(self::STUDENT, '2028-02-19', '2028-02-21');
    }
}
