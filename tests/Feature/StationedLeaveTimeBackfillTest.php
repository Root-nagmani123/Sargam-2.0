<?php

namespace Tests\Feature;

use App\Models\LeaveApplication;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Before time_from / time_to existed, the apply form posted a stationed leave's
 * times inside from_date / to_date (DATETIME since 2026_09_03). Those rows showed
 * "-" for both times after deploy, and an edit dropped them (PR #334 F-045). The
 * backfill migration copies the time parts across.
 *
 * Runs against a session-scoped TEMPORARY leave_application with DATETIME date
 * columns: it shadows the real table for this connection only, needs no DDL on
 * the real table (whose 2026_09_03 widening may not have run locally), and does
 * not commit the surrounding transaction.
 */
class StationedLeaveTimeBackfillTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const MIGRATION = '2026_10_08_000001_backfill_stationed_leave_time_from_time_to.php';

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasColumn('leave_application', 'time_from')) {
            $this->markTestSkipped('leave_application has no time_from column');
        }

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
    }

    protected function tearDown(): void
    {
        DB::statement('DROP TEMPORARY TABLE IF EXISTS leave_application');
        parent::tearDown();
    }

    private function leave(string $type, string $from, string $to, ?string $timeFrom = null): int
    {
        return (int) DB::table('leave_application')->insertGetId([
            'leave_type' => $type, 'from_date' => $from, 'to_date' => $to, 'time_from' => $timeFrom,
        ]);
    }

    private function migrate(): void
    {
        (require database_path('migrations/' . self::MIGRATION))->up();
    }

    public function test_a_pre_deploy_stationed_leave_keeps_its_times(): void
    {
        $pk = $this->leave('STATIONED_LEAVE', '2026-09-20 09:30:00', '2026-09-21 18:00:00');

        $this->migrate();

        $leave = LeaveApplication::findOrFail($pk);
        $this->assertSame('09:30 AM', $leave->time_from_display);
        $this->assertSame('06:00 PM', $leave->time_to_display);
    }

    public function test_a_row_with_no_time_and_a_pt_exemption_are_left_alone(): void
    {
        $dateOnly = $this->leave('STATIONED_LEAVE', '2026-09-20 00:00:00', '2026-09-21 00:00:00');
        $pt = $this->leave('PT_EXEMPTION', '2026-09-20 09:30:00', '2026-09-21 18:00:00');

        $this->migrate();

        $this->assertNull(DB::table('leave_application')->where('pk', $dateOnly)->value('time_from'));
        $this->assertNull(DB::table('leave_application')->where('pk', $pt)->value('time_from'));
    }

    public function test_a_time_entered_after_deploy_is_not_overwritten(): void
    {
        $pk = $this->leave('STATIONED_LEAVE', '2026-09-20 09:30:00', '2026-09-21 18:00:00', '07:15:00');

        $this->migrate();

        $this->assertSame('07:15:00', DB::table('leave_application')->where('pk', $pk)->value('time_from'));
    }
}
