<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Attendance\OtExemptionResolver;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * A medical exemption covers only the sessions its time window overlaps, and every
 * screen must agree on that (PR #334 F-006, F-016).
 *
 * F-006: the OT's own attendance page and feed matched an exemption on its DATE
 * only, so one that ended at 07:30 turned a 14:00 session saved Absent into
 * "Present (Medical)" — while save(), the admin grid and the defaulter list kept
 * it Absent.
 *
 * F-016: with two exemptions on one day, save() looked at the first row only. If
 * that one missed the session and the second covered it, save() stored the posted
 * Absent while the defaulter list (which checks every row) counted it covered.
 *
 * Driven through the routes the OT and the marker actually use, so a failure is a
 * wrong answer, not a missing method. Every row is written inside a transaction
 * that is rolled back.
 */
class OtMedicalExemptionWindowTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const DAY = '2031-03-11';

    private const PREVIOUS_DAY = '2031-03-10';

    private const TOPIC = 'PR334 exemption window probe';

    /** @return array{ot: User, student: int, course: int, group: int} an OT login enrolled in an active group */
    private function enrolledOt(): array
    {
        $row = DB::table('user_credentials as u')
            ->join('student_course_group_map as scgm', 'scgm.student_master_pk', '=', 'u.user_id')
            ->join('group_type_master_course_master_map as g', 'g.pk', '=', 'scgm.group_type_master_course_master_map_pk')
            ->join('course_master as cm', 'cm.pk', '=', 'g.course_name')
            ->where('u.user_category', 'S')
            ->where('scgm.active_inactive', 1)
            ->orderByDesc('scgm.pk')
            ->first(['u.pk as user_pk', 'scgm.student_master_pk as student', 'g.pk as grp', 'cm.pk as course']);

        if (! $row) {
            $this->markTestSkipped('no OT login mapped to a group on a course');
        }

        return ['ot' => User::findOrFail($row->user_pk), 'student' => (int) $row->student, 'course' => (int) $row->course, 'group' => (int) $row->grp];
    }

    /** A session on DAY for the OT's group, saved with $status; returns the timetable pk. */
    private function probeSession(array $s, string $classSession, string $status = '3'): int
    {
        $timetable = DB::table('timetable')->insertGetId([
            'course_master_pk' => $s['course'],
            'subject_master_pk' => 0,
            'subject_module_master_pk' => 0,
            'subject_topic' => self::TOPIC,
            'course_group_type_master' => 0,
            'group_name' => '[]',
            'venue_id' => 0,
            'class_session' => $classSession,
            'START_DATE' => self::DAY,
            'END_DATE' => self::DAY,
            'active_inactive' => 1,
        ]);

        DB::table('course_group_timetable_mapping')->insert([
            'group_pk' => $s['group'],
            'Programme_pk' => $s['course'],
            'timetable_pk' => $timetable,
        ]);

        if ($status !== '') {
            DB::table('course_student_attendance')->insert([
                'timetable_pk' => $timetable,
                'Student_master_pk' => $s['student'],
                'group_type_master_course_master_map_pk' => $s['group'],
                'course_master_pk' => $s['course'],
                'status' => $status,
            ]);
        }

        return (int) $timetable;
    }

    private function exemption(array $s, string $from, string $to): void
    {
        DB::table('student_medical_exemption')->insert([
            'course_master_pk' => $s['course'],
            'student_master_pk' => $s['student'],
            'employee_master_pk' => 0,
            'exemption_category_master_pk' => 0,
            'exemption_medical_speciality_pk' => 0,
            'from_date' => $from,
            'to_date' => $to,
            'Description' => self::TOPIC,
            'active_inactive' => 1,
        ]);
    }

    /** What the OT's own attendance page shows for the probe session. */
    private function pageStatus(array $s, int $timetable): string
    {
        $response = $this->as($s['ot'], ['Student-OT'])->get(route('attendance.OT.student_mark.student', [
            'group_pk' => $s['group'], 'course_pk' => $s['course'], 'timetable_pk' => $timetable,
            'student_pk' => $s['student'], 'filter_date' => self::DAY,
        ]));
        $response->assertOk();

        $record = collect($response->viewData('attendanceRecords'))->firstWhere('topic', self::TOPIC);
        $this->assertNotNull($record, 'the probe session is on the page');

        return $record['attendance_status'];
    }

    /** What the OT's attendance feed (/getstudentmarks) shows for the probe session. */
    private function feedStatus(array $s, int $timetable): string
    {
        $response = $this->as($s['ot'], ['Student-OT'])->getJson(route('ot.student.attendance.data', [
            'group_pk' => $s['group'], 'course_pk' => $s['course'], 'timetable_pk' => $timetable,
            'student_pk' => $s['student'], 'filter_date' => self::DAY,
        ]));
        $response->assertOk();

        $row = collect($response->json('data'))->firstWhere('topic', self::TOPIC);
        $this->assertNotNull($row, 'the probe session is in the feed');

        // The feed renders the status as a badge; the word is what the OT reads.
        return trim(strip_tags((string) $row['attendance_status']));
    }

    private function defaulterListCovers(array $s, int $timetable): bool
    {
        return OtExemptionResolver::coveredSessions([
            ['student' => $s['student'], 'course' => $s['course'], 'timetable' => $timetable],
        ]) !== [];
    }

    /* ---------------- F-006 ---------------- */

    /** Ran from the previous morning and ended at 07:30 on the session's day. */
    public function test_an_exemption_that_ended_before_the_session_leaves_the_saved_absent_on_the_ots_page_and_feed(): void
    {
        $s = $this->enrolledOt();
        $timetable = $this->probeSession($s, '14:00 to 15:00', '3');
        $this->exemption($s, self::PREVIOUS_DAY.' 09:00:00', self::DAY.' 07:30:00');

        $this->assertFalse($this->defaulterListCovers($s, $timetable), 'the defaulter list counts the session Absent');
        $this->assertSame('Absent', $this->pageStatus($s, $timetable), 'the OT page must show the saved Absent');
        $this->assertSame('Absent', $this->feedStatus($s, $timetable), 'the OT feed must show the saved Absent');
    }

    /** Starts later on the session's day and covers it — a date-only rule dropped it. */
    public function test_a_same_day_exemption_overlapping_the_session_shows_present_on_the_ots_page_and_feed(): void
    {
        $s = $this->enrolledOt();
        $timetable = $this->probeSession($s, '14:00 to 15:00', '3');
        $this->exemption($s, self::DAY.' 13:30:00', self::DAY.' 16:00:00');

        $this->assertTrue($this->defaulterListCovers($s, $timetable));
        $this->assertSame('Present', $this->pageStatus($s, $timetable));
        $this->assertSame('Present', $this->feedStatus($s, $timetable));
    }

    /** Control, true under any rule: from the previous day through the end of the session. */
    public function test_an_exemption_spanning_the_whole_session_shows_present_on_the_ots_page_and_feed(): void
    {
        $s = $this->enrolledOt();
        $timetable = $this->probeSession($s, '14:00 to 15:00', '3');
        $this->exemption($s, self::PREVIOUS_DAY.' 09:00:00', self::DAY.' 16:00:00');

        $this->assertTrue($this->defaulterListCovers($s, $timetable));
        $this->assertSame('Present', $this->pageStatus($s, $timetable));
        $this->assertSame('Present', $this->feedStatus($s, $timetable));
    }

    /* ---------------- F-016 ---------------- */

    /**
     * Two exemptions on one day, only the second overlapping the session: the
     * marker posts Absent, and save() must store Present like the defaulter list
     * counts it — not the posted Absent because the first row missed.
     */
    public function test_save_honours_the_second_of_two_same_day_exemptions_like_the_defaulter_list(): void
    {
        $s = $this->enrolledOt();
        $timetable = $this->probeSession($s, '14:10 to 16:20', '');
        $this->exemption($s, self::DAY.' 06:15:00', self::DAY.' 07:30:00');
        $this->exemption($s, self::DAY.' 14:03:00', self::DAY.' 16:30:00');

        $marker = $this->staffWithRole('Super Admin');
        $this->as($marker, ['Super Admin'])->post(route('attendance.save'), [
            'group_pk' => $s['group'],
            'course_pk' => $s['course'],
            'timetable_pk' => $timetable,
            'student' => [$s['student'] => '3'],
        ])->assertRedirect(route('attendance.index'));

        $stored = DB::table('course_student_attendance')
            ->where('timetable_pk', $timetable)->where('Student_master_pk', $s['student'])->value('status');

        $this->assertTrue($this->defaulterListCovers($s, $timetable), 'batch: covered');
        $this->assertSame('1', (string) $stored, 'per-session (save): stored Present, not the posted Absent');
        $this->assertSame('Present', $this->pageStatus($s, $timetable), 'the OT page agrees');
    }

    /** Control: with only the missing exemption, the posted Absent is stored. */
    public function test_save_keeps_the_posted_absent_when_no_same_day_exemption_overlaps(): void
    {
        $s = $this->enrolledOt();
        $timetable = $this->probeSession($s, '14:10 to 16:20', '');
        $this->exemption($s, self::DAY.' 06:15:00', self::DAY.' 07:30:00');

        $marker = $this->staffWithRole('Super Admin');
        $this->as($marker, ['Super Admin'])->post(route('attendance.save'), [
            'group_pk' => $s['group'],
            'course_pk' => $s['course'],
            'timetable_pk' => $timetable,
            'student' => [$s['student'] => '3'],
        ])->assertRedirect(route('attendance.index'));

        $this->assertFalse($this->defaulterListCovers($s, $timetable));
        $this->assertSame('3', (string) DB::table('course_student_attendance')
            ->where('timetable_pk', $timetable)->where('Student_master_pk', $s['student'])->value('status'));
    }
}
