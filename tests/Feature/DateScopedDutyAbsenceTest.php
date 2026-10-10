<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use App\Models\MDOEscotDutyMap;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Which absences the date-scoped Student List and its export treat as covered
 * by a duty or exemption.
 *
 * collapseDateScopedAttendance() built $isAbsentRow — absent AND not covered by a
 * duty or exemption — and then bucketed on the raw status instead (PR #334 F-033).
 * The cover itself was any duty or medical exemption that day, in any course and
 * at any time, while AttendanceController::save requires the same course and an
 * overlap with the session (F-062).
 *
 * Fixtures are copies of existing timetable / duty / exemption rows moved to a
 * date nothing else uses, inside the rolled-back transaction.
 */
class DateScopedDutyAbsenceTest extends TestCase
{
    // Aliased: this class needs its own setUp(), which would otherwise replace
    // the trait's and so never open the rolled-back transaction.
    use RollsBackAgainstAppDatabase {
        setUp as openRollbackTransaction;
    }

    private const DAY = '2031-04-15';

    private int $student;
    private int $course;
    private int $otherCourse;

    protected function setUp(): void
    {
        $this->openRollbackTransaction();

        $escortPk = MDOEscotDutyMap::getMdoDutyTypes()['escort'] ?? null;
        $template = DB::table('mdo_escot_duty_map')->orderByDesc('pk')->first();
        if (! $escortPk || ! $template || ! DB::table('timetable')->exists()) {
            $this->markTestSkipped('needs an Escort duty type, a duty row and a timetable row');
        }

        $this->student = (int) $template->selected_student_list;
        $this->course = (int) $template->course_master_pk;
        $this->otherCourse = $this->course + 1;

        // Escort duty 12:15–13:15 on the day, in $this->course.
        $duty = (array) $template;
        unset($duty['pk']);
        DB::table('mdo_escot_duty_map')->insert(array_merge($duty, [
            'selected_student_list' => $this->student,
            'course_master_pk' => $this->course,
            'mdo_duty_type_master_pk' => $escortPk,
            'mdo_date' => self::DAY.' 00:00:00',
            'Time_from' => '12:15',
            'Time_to' => '13:15',
        ]));
    }

    private function timetable(string $classSession): int
    {
        $row = (array) DB::table('timetable')->orderByDesc('pk')->first();
        unset($row['pk']);
        $row['START_DATE'] = self::DAY;
        if (array_key_exists('END_DATE', $row)) {
            $row['END_DATE'] = self::DAY;
        }
        $row['class_session'] = $classSession;

        return (int) DB::table('timetable')->insertGetId($row);
    }

    private function sessionRow(int $timetable, int $course, int $status = 3): object
    {
        return (object) [
            'student_master_pk' => $this->student,
            'session_date' => self::DAY,
            'session_time' => null,
            'session_topic' => null,
            'session_timetable_pk' => $timetable,
            'session_course_pk' => $course,
            'attendance_status' => $status,
            'attendance_present' => $status !== 3,
            'other_exemption_comments' => null,
            'has_session_in_range' => true,
            'course' => null,
            'studentMaster' => (object) ['display_name' => 'T', 'generated_OT_code' => 'T1', 'user_id' => 't', 'cadre' => null],
        ];
    }

    private function invokePrivate(string $method, ...$args)
    {
        $m = new \ReflectionMethod(UserController::class, $method);
        $m->setAccessible(true);

        return $m->invoke(app(UserController::class), ...$args);
    }

    /** @return array{0: int, 1: int} [present, absent] */
    private function buckets(object $row): array
    {
        [$present, $absent] = $this->invokePrivate('collapseDateScopedAttendance', collect([$row]));

        return [$present->count(), $absent->count()];
    }

    public function test_an_absence_the_duty_overlaps_lands_in_present(): void
    {
        $row = $this->sessionRow($this->timetable('12:00 PM - 01:00 PM'), $this->course);

        $this->assertSame([1, 0], $this->buckets($row));
    }

    public function test_an_absence_at_a_time_the_duty_misses_stays_absent(): void
    {
        $row = $this->sessionRow($this->timetable('03:30 PM - 04:30 PM'), $this->course);

        $this->assertSame([0, 1], $this->buckets($row));
    }

    public function test_a_duty_on_another_course_does_not_cover_the_absence(): void
    {
        $row = $this->sessionRow($this->timetable('12:00 PM - 01:00 PM'), $this->otherCourse);

        $this->assertSame([0, 1], $this->buckets($row));
    }

    public function test_a_whole_day_medical_exemption_covers_the_absence_in_its_course_only(): void
    {
        $template = DB::table('student_medical_exemption')->orderByDesc('pk')->first();
        if (! $template) {
            $this->markTestSkipped('no medical exemption row to copy');
        }
        $exemption = (array) $template;
        unset($exemption['pk']);
        DB::table('student_medical_exemption')->insert(array_merge($exemption, [
            'student_master_pk' => $this->student,
            'course_master_pk' => $this->course,
            'from_date' => self::DAY.' 00:00:00',
            'to_date' => '2031-04-16 00:00:00',
            'active_inactive' => 1,
        ]));
        $timetable = $this->timetable('03:30 PM - 04:30 PM');

        $this->assertSame([1, 0], $this->buckets($this->sessionRow($timetable, $this->course)));
        $this->assertSame([0, 1], $this->buckets($this->sessionRow($timetable, $this->otherCourse)));
    }

    public function test_a_row_without_a_session_is_never_covered(): void
    {
        // A leave-based absentee: no timetable, so nothing to overlap.
        $row = $this->sessionRow(0, $this->course);
        $row->session_timetable_pk = null;

        $this->assertSame([false], $this->invokePrivate('dashboardSessionCoverage', collect([$row])));
    }

    public function test_the_export_keeps_the_absence_but_still_shows_the_duty_that_day(): void
    {
        $missed = $this->sessionRow($this->timetable('03:30 PM - 04:30 PM'), $this->course);
        $overlapped = $this->sessionRow($this->timetable('12:00 PM - 01:00 PM'), $this->course);

        $rows = $this->invokePrivate('dashboardStudentListExportData', collect([$missed, $overlapped]))['rows'];

        // Columns: … 10 Attendance Status, 11 MDO, 12 Escort.
        $this->assertSame('Absent', $rows[0][10]);
        $this->assertSame('Escort', $rows[0][12], 'the Escort column still reports the duty that day');
        $this->assertSame('Present', $rows[1][10]);
    }
}
