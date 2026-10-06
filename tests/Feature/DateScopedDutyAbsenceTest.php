<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * collapseDateScopedAttendance() built $isAbsentRow — absent AND not covered by a
 * duty or exemption — and then bucketed on the raw status instead, so an absence
 * on a covered day stayed in the Absent tab while the row badge read Present
 * (PR #334 F-033).
 *
 * Uses an existing active medical exemption as the cover; the session row itself
 * is synthetic.
 */
class DateScopedDutyAbsenceTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private function collapse($rows): array
    {
        $m = new \ReflectionMethod(UserController::class, 'collapseDateScopedAttendance');
        $m->setAccessible(true);

        return $m->invoke(app(UserController::class), $rows);
    }

    private function sessionRow(int $studentPk, string $date, int $status): object
    {
        return (object) [
            'student_master_pk' => $studentPk,
            'session_date' => $date,
            'attendance_status' => $status,
            'has_session_in_range' => true,
        ];
    }

    public function test_a_covered_absence_lands_in_present(): void
    {
        $exemption = DB::table('student_medical_exemption')
            ->where('active_inactive', 1)
            ->whereNotNull('from_date')
            ->orderByDesc('pk')
            ->first(['student_master_pk', 'from_date']);
        if (! $exemption) {
            $this->markTestSkipped('no active medical exemption');
        }

        $covered = $this->sessionRow((int) $exemption->student_master_pk, substr((string) $exemption->from_date, 0, 10), 3);

        [$present, $absent] = $this->collapse(collect([$covered]));

        $this->assertCount(1, $present);
        $this->assertCount(0, $absent);
    }

    public function test_an_uncovered_absence_stays_absent(): void
    {
        // A student pk no exemption or duty can reference.
        $row = $this->sessionRow(990000001, '2026-10-01', 3);

        [$present, $absent] = $this->collapse(collect([$row]));

        $this->assertCount(0, $present);
        $this->assertCount(1, $absent);
    }
}
