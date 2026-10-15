<?php

namespace Tests\Feature;

use App\Exports\AttendanceDataExport;
use App\Http\Controllers\Admin\AttendanceController;
use App\Models\CalendarEvent;
use App\Models\CourseStudentAttendance;
use App\Models\StudentMaster;
use App\Services\Attendance\OtExemptionResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Every screen must reach the same attendance verdict for one session
 * (PR #334 F-004).
 *
 * Scenario from the review: a medical exemption running day 1 09:00 → day 2
 * 10:00, the OT marked Absent for the day-2 14:00–15:00 session. save() and the
 * admin grid (OtExemptionResolver::isExempt) say the session is NOT covered, so
 * the saved Absent stands; the defaulter list (coveredSessions) agrees. Before
 * the fix the OT's own view and export matched the exemption by date only and
 * showed Present, and the attendance Excel used clock time only.
 *
 * Skips without a database; all rows are written inside a transaction that is
 * always rolled back.
 */
class OtAttendanceExemptionConsistencyTest extends TestCase
{
    private const DAY1 = '2031-03-10';
    private const DAY2 = '2031-03-11';

    private bool $inTransaction = false;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('attendance consistency tests need the application database');
        }

        DB::beginTransaction();
        $this->inTransaction = true;
    }

    protected function tearDown(): void
    {
        if ($this->inTransaction) {
            DB::rollBack();
            $this->inTransaction = false;
        }

        parent::tearDown();
    }

    /**
     * One OT in one group, a 14:00–15:00 session on DAY2 saved Absent, and a
     * medical exemption with the given window.
     *
     * @return array{student: int, course: int, group: int, timetable: int}
     */
    private function scenario(string $exemptionFrom, string $exemptionTo): array
    {
        $member = DB::table('student_course_group_map as scgm')
            ->join('group_type_master_course_master_map as g', 'g.pk', '=', 'scgm.group_type_master_course_master_map_pk')
            ->join('student_master as sm', 'sm.pk', '=', 'scgm.student_master_pk')
            ->join('course_master as cm', 'cm.pk', '=', 'g.course_name')
            ->where('scgm.active_inactive', 1)
            ->orderByDesc('scgm.pk')
            ->first(['scgm.student_master_pk as student', 'g.pk as grp', 'cm.pk as course']);

        if (! $member) {
            $this->markTestSkipped('no officer trainee mapped to a group on a course');
        }

        $timetable = DB::table('timetable')->insertGetId([
            'course_master_pk' => $member->course,
            'subject_master_pk' => 0,
            'subject_module_master_pk' => 0,
            'subject_topic' => 'F-004 probe',
            'course_group_type_master' => 0,
            'group_name' => '[]',
            'venue_id' => 0,
            'class_session' => '14:00 to 15:00',
            'START_DATE' => self::DAY2,
            'END_DATE' => self::DAY2,
            'active_inactive' => 1,
        ]);

        DB::table('course_group_timetable_mapping')->insert([
            'group_pk' => $member->grp,
            'Programme_pk' => $member->course,
            'timetable_pk' => $timetable,
        ]);

        DB::table('course_student_attendance')->insert([
            'timetable_pk' => $timetable,
            'Student_master_pk' => $member->student,
            'group_type_master_course_master_map_pk' => $member->grp,
            'course_master_pk' => $member->course,
            'status' => '3',
        ]);

        DB::table('student_medical_exemption')->insert([
            'course_master_pk' => $member->course,
            'student_master_pk' => $member->student,
            'employee_master_pk' => 0,
            'exemption_category_master_pk' => 0,
            'exemption_medical_speciality_pk' => 0,
            'from_date' => $exemptionFrom,
            'to_date' => $exemptionTo,
            'Description' => 'F-004 probe',
            'active_inactive' => 1,
        ]);

        return ['student' => (int) $member->student, 'course' => (int) $member->course, 'group' => (int) $member->grp, 'timetable' => (int) $timetable];
    }

    /**
     * The verdict of every consumer for the scenario's session.
     *
     * @return array<string, string>  consumer => 'Present' | 'Absent' | 'covered' | 'not covered'
     */
    private function verdicts(array $s): array
    {
        $resolver = new OtExemptionResolver($s['course'], $s['timetable']);
        $covered = OtExemptionResolver::coveredSessions([
            ['student' => $s['student'], 'course' => $s['course'], 'timetable' => $s['timetable']],
        ]);

        $controller = app(AttendanceController::class);

        // OT mark-attendance view and its twin getOTAttendanceData() decide through this helper.
        $helper = new \ReflectionMethod($controller, 'coveringMedicalExemption');
        $helper->setAccessible(true);
        $viewCovered = $helper->invoke($controller, $s['course'], $s['student'], CalendarEvent::find($s['timetable'])) !== null;

        // The OT's own Excel export builds its rows here.
        $build = new \ReflectionMethod($controller, 'buildOtStudentAttendanceData');
        $build->setAccessible(true);
        $built = $build->invoke($controller, Request::create('/', 'GET', ['filter_date' => self::DAY2]), $s['group'], $s['course'], $s['timetable'], $s['student']);
        $otExportStatus = collect($built['attendanceRecords'])->firstWhere('topic', 'F-004 probe')['attendance_status'] ?? 'missing';

        // The admin attendance sheet (.xlsx and PDF share array()).
        $record = (object) [
            'studentsMaster' => StudentMaster::find($s['student']),
            'attendance' => CourseStudentAttendance::where('timetable_pk', $s['timetable'])->get(),
        ];
        $row = (new AttendanceDataExport(collect([$record]), '', '', '', '', '', $s['course'], $s['group'], $s['timetable'], self::DAY2, '14:00 to 15:00'))->array()[0] ?? [];
        $sheetStatus = in_array('Present', $row, true) ? 'Present' : (in_array('Absent', $row, true) ? 'Absent' : 'missing');

        return [
            'save() / admin grid (isExempt)' => $resolver->isExempt($s['student']) ? 'covered' : 'not covered',
            'defaulter list (coveredSessions)' => $covered === [] ? 'not covered' : 'covered',
            'OT view (medical helper)' => $viewCovered ? 'covered' : 'not covered',
            'OT export rows' => $otExportStatus,
            'attendance sheet' => $sheetStatus,
        ];
    }

    public function test_a_timed_exemption_that_misses_the_session_leaves_the_saved_absent_everywhere(): void
    {
        $s = $this->scenario(self::DAY1 . ' 09:00:00', self::DAY2 . ' 10:00:00');

        $this->assertSame([
            'save() / admin grid (isExempt)' => 'not covered',
            'defaulter list (coveredSessions)' => 'not covered',
            'OT view (medical helper)' => 'not covered',
            'OT export rows' => 'Absent',
            'attendance sheet' => 'Absent',
        ], $this->verdicts($s));
    }

    public function test_a_date_only_exemption_covers_the_session_everywhere(): void
    {
        // Dates entered without times are stored at midnight and cover whole days.
        $s = $this->scenario(self::DAY1 . ' 00:00:00', self::DAY2 . ' 00:00:00');

        $this->assertSame([
            'save() / admin grid (isExempt)' => 'covered',
            'defaulter list (coveredSessions)' => 'covered',
            'OT view (medical helper)' => 'covered',
            'OT export rows' => 'Present',
            'attendance sheet' => 'Present',
        ], $this->verdicts($s));
    }

    public function test_a_timed_exemption_that_overlaps_the_session_covers_it_everywhere(): void
    {
        $s = $this->scenario(self::DAY2 . ' 13:30:00', self::DAY2 . ' 16:00:00');

        $this->assertSame([
            'save() / admin grid (isExempt)' => 'covered',
            'defaulter list (coveredSessions)' => 'covered',
            'OT view (medical helper)' => 'covered',
            'OT export rows' => 'Present',
            'attendance sheet' => 'Present',
        ], $this->verdicts($s));
    }
}
