<?php

namespace Tests\Feature;

use App\Exports\AttendanceDataExport;
use App\Http\Controllers\Admin\AttendanceController;
use App\Models\CourseStudentAttendance;
use App\Models\LeaveApplication;
use App\Models\MDOEscotDutyMap;
use App\Models\StudentMaster;
use App\Models\User;
use App\Services\Attendance\OtExemptionResolver;
use App\Services\LeaveApplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #334 round-5 findings, each through the route that showed it:
 *
 *   F-054  the OT's own /leave/store: a time-suffixed date slipped past the
 *          single-day time check and the overlap check
 */
class Pr334Round5FindingsTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /* ---------------- F-054 ---------------- */

    /** @return array{0:\Closure, 1:array<string,mixed>, 2:int} [acting OT, valid stationed-leave payload, student pk] */
    private function otLeaveFixture(): array
    {
        $row = DB::table('user_credentials as u')
            ->join('student_master_course__map as smcm', 'smcm.student_master_pk', '=', 'u.user_id')
            ->join('course_master as cm', 'cm.pk', '=', 'smcm.course_master_pk')
            ->where('u.user_category', 'S')->where('smcm.active_inactive', 1)->where('cm.active_inactive', 1)
            ->orderByDesc('smcm.pk')
            ->first(['u.pk as user_pk']);
        $nature = DB::table('leave_nature_master')->where('leave_type', 'STATIONED_LEAVE')->where('active_inactive', 1)->value('pk');
        if (! $row || ! $nature) {
            $this->markTestSkipped('needs an enrolled OT and a stationed-leave nature');
        }

        $context = app(LeaveApplicationService::class)->resolveStudentContext((int) $row->user_pk);
        DB::table('stationed_leave_master')->insert([
            'course_master_pk' => $context['course_pk'],
            'effective_from' => now()->subDay()->toDateString(),
            'apply_cutoff_time' => null,
            'is_faculty_approval_required' => 0,
            'active_inactive' => 1,
            'created_date' => now(),
        ]);

        $payload = [
            'leave_type' => 'STATIONED_LEAVE', 'leave_nature_master_pk' => $nature,
            'time_from' => '09:00', 'time_to' => '18:00',
            'reason' => 'F-054 probe', 'contact_number' => '9876543210', 'submit_action' => 'submit',
        ];

        return [fn () => $this->as(User::findOrFail($row->user_pk), ['Student-OT']), $payload, (int) $context['student_pk']];
    }

    private function leavesOn(int $studentPk, string $date): int
    {
        return LeaveApplication::where('student_master_pk', $studentPk)->whereDate('from_date', '<=', $date)->whereDate('to_date', '>=', $date)->count();
    }

    public function test_a_time_suffixed_to_date_cannot_record_a_return_before_departure(): void
    {
        [$as, $payload, $student] = $this->otLeaveFixture();
        $date = now()->addDays(230)->toDateString();
        $backwards = ['time_from' => '18:00', 'time_to' => '09:00'];

        // Control: the plain single day is refused on time_to.
        $as()->post('/leave/store', ['from_date' => $date, 'to_date' => $date] + $backwards + $payload)
            ->assertSessionHasErrors('time_to');

        $as()->post('/leave/store', ['from_date' => $date, 'to_date' => $date.' 00:00:01'] + $backwards + $payload)
            ->assertSessionHasErrors('time_to');
        $this->assertSame(0, $this->leavesOn($student, $date), 'nothing was stored');

        // Positive control: the same day with a sane time range is accepted, stored as a plain date.
        $as()->post('/leave/store', ['from_date' => $date, 'to_date' => $date.' 00:00:01'] + $payload)
            ->assertSessionHasNoErrors();
        $stored = LeaveApplication::where('student_master_pk', $student)->whereDate('from_date', $date)->firstOrFail();
        $this->assertSame([$date, $date], [substr((string) $stored->getRawOriginal('from_date'), 0, 10), substr((string) $stored->getRawOriginal('to_date'), 0, 10)]);
    }

    public function test_a_time_suffixed_date_cannot_add_a_second_leave_on_a_covered_day(): void
    {
        [$as, $payload, $student] = $this->otLeaveFixture();
        $date = now()->addDays(240)->toDateString();

        $as()->post('/leave/store', ['from_date' => $date, 'to_date' => $date] + $payload)->assertSessionHasNoErrors();
        $this->assertSame(1, $this->leavesOn($student, $date), 'fixture: the first leave is stored');

        // Control: a plain second leave that day is refused.
        $as()->post('/leave/store', ['from_date' => $date, 'to_date' => $date] + $payload)->assertSessionHasErrors('from_date');

        $as()->post('/leave/store', ['from_date' => $date.' 12:00', 'to_date' => $date.' 12:00'] + $payload)
            ->assertSessionHasErrors('from_date');
        $this->assertSame(1, $this->leavesOn($student, $date), 'still one leave on the day');
    }

    /* ---------------- F-063 ---------------- */

    public function test_a_cancelled_medical_exemption_is_absent_from_the_ots_page_and_downloads(): void
    {
        $row = DB::table('user_credentials as u')
            ->join('student_master_course__map as smcm', 'smcm.student_master_pk', '=', 'u.user_id')
            ->where('u.user_category', 'S')->where('smcm.active_inactive', 1)
            ->orderByDesc('smcm.pk')
            ->first(['u.pk as user_pk', 'u.user_id as student', 'smcm.course_master_pk as course']);
        $refs = [
            'employee_master_pk' => DB::table('employee_master')->value('pk'),
            'exemption_category_master_pk' => DB::table('exemption_category_master')->value('pk'),
            'exemption_medical_speciality_pk' => DB::table('exemption_medical_speciality_master')->value('pk'),
        ];
        if (! $row || in_array(null, $refs, true)) {
            $this->markTestSkipped('needs an enrolled OT and the exemption masters');
        }
        DB::table('course_master')->where('pk', $row->course)->update(['active_inactive' => 1, 'end_date' => now()->addYear()->toDateString()]);

        $tag = random_int(100000, 999999);
        foreach (['Zqactive'.$tag => 1, 'Zqcancelled'.$tag => 0] as $description => $active) {
            DB::table('student_medical_exemption')->insert($refs + [
                'course_master_pk' => $row->course, 'student_master_pk' => $row->student,
                'from_date' => now()->addDays(300), 'to_date' => now()->addDays(301),
                'Description' => $description, 'active_inactive' => $active,
            ]);
        }
        $as = fn () => $this->as(User::findOrFail($row->user_pk), ['Student-OT']);

        $page = implode("\n", array_column($as()->get(route('medical.exception.ot.view'))->assertOk()->viewData('studentData')['exemptions'], 'description'));

        $xlsx = $as()->get(route('medical.exception.ot.view.export'))->assertOk();
        $excel = '';
        foreach (IOFactory::load($xlsx->baseResponse->getFile()->getPathname())->getActiveSheet()->toArray() as $line) {
            $excel .= implode("\n", array_map('strval', $line))."\n";
        }

        $rendered = null;
        View::composer('admin.exports.table_pdf', function ($view) use (&$rendered) {
            $rendered = $view->getData();
        });
        $as()->get(route('medical.exception.ot.view.export', ['format' => 'pdf']))->assertOk();
        $pdf = json_encode($rendered['rows'] ?? null);

        foreach (['page' => $page, 'excel' => $excel, 'pdf' => $pdf] as $where => $text) {
            $this->assertStringContainsString('Zqactive'.$tag, $text, "{$where} lists the active exemption");
            $this->assertStringNotContainsString('Zqcancelled'.$tag, $text, "{$where} omits the cancelled exemption");
        }
    }

    /* ---------------- F-061 ---------------- */

    /** A 14:00-15:00 session for an OT, with MDO duties at the given [from, to] times that day. */
    private function sessionWithMdoDuties(array $duties): array
    {
        $member = DB::table('student_course_group_map as scgm')
            ->join('group_type_master_course_master_map as g', 'g.pk', '=', 'scgm.group_type_master_course_master_map_pk')
            ->join('course_master as cm', 'cm.pk', '=', 'g.course_name')
            ->where('scgm.active_inactive', 1)
            ->orderByDesc('scgm.pk')
            ->first(['scgm.student_master_pk as student', 'cm.pk as course', 'g.pk as grp']);
        $mdo = MDOEscotDutyMap::getMdoDutyTypes()['mdo'] ?? null;
        if (! $member || ! $mdo) {
            $this->markTestSkipped('needs an OT in a group and the MDO duty type');
        }

        $day = '2031-04-15';
        $timetable = DB::table('timetable')->insertGetId([
            'course_master_pk' => $member->course, 'subject_master_pk' => 0, 'subject_module_master_pk' => 0,
            'subject_topic' => 'F-061 probe', 'course_group_type_master' => 0, 'group_name' => '[]', 'venue_id' => 0,
            'class_session' => '14:00 to 15:00', 'START_DATE' => $day, 'END_DATE' => $day, 'active_inactive' => 1,
        ]);
        DB::table('course_group_timetable_mapping')->insert(['group_pk' => $member->grp, 'Programme_pk' => $member->course, 'timetable_pk' => $timetable]);
        DB::table('course_student_attendance')->insert([
            'timetable_pk' => $timetable, 'Student_master_pk' => $member->student,
            'group_type_master_course_master_map_pk' => $member->grp, 'course_master_pk' => $member->course, 'status' => '3',
        ]);
        foreach ($duties as [$from, $to]) {
            DB::table('mdo_escot_duty_map')->insert([
                'course_master_pk' => $member->course, 'mdo_duty_type_master_pk' => $mdo,
                'mdo_date' => $day.' 00:00:00', 'Time_from' => $from, 'Time_to' => $to,
                'selected_student_list' => $member->student,
            ]);
        }

        return ['student' => (int) $member->student, 'course' => (int) $member->course, 'timetable' => (int) $timetable, 'group' => (int) $member->grp, 'day' => $day];
    }

    /** @return array<string,bool> consumer => the session counts as MDO duty */
    private function dutyCovers(array $s): array
    {
        $controller = app(AttendanceController::class);
        $build = new \ReflectionMethod($controller, 'buildOtStudentAttendanceData');
        $build->setAccessible(true);
        $built = $build->invoke($controller, Request::create('/', 'GET', ['filter_date' => $s['day']]), $s['group'], $s['course'], $s['timetable'], $s['student']);

        $record = (object) [
            'studentsMaster' => StudentMaster::find($s['student']),
            'attendance' => CourseStudentAttendance::where('timetable_pk', $s['timetable'])->get(),
        ];
        $sheet = (new AttendanceDataExport(collect([$record]), '', '', '', '', '', $s['course'], $s['group'], $s['timetable'], $s['day'], '14:00 to 15:00'))->array()[0] ?? [];

        return [
            'save() / admin grid' => (new OtExemptionResolver($s['course'], $s['timetable']))->hasMdo($s['student']),
            'defaulter list' => OtExemptionResolver::coveredSessions([$s]) !== [],
            'OT view / export rows' => (collect($built['attendanceRecords'])->firstWhere('topic', 'F-061 probe')['duty_type'] ?? null) === 'MDO',
            'attendance sheet' => in_array('MDO Duty', $sheet, true),
        ];
    }

    public function test_a_later_same_day_duty_that_overlaps_the_session_covers_it_in_both_paths(): void
    {
        // Control: a morning duty alone does not cover the afternoon session.
        $none = ['save() / admin grid' => false, 'defaulter list' => false, 'OT view / export rows' => false, 'attendance sheet' => false];
        $this->assertSame($none, $this->dutyCovers($this->sessionWithMdoDuties([['09:00:00', '10:00:00']])));

        $this->assertSame(array_map(fn () => true, $none), $this->dutyCovers($this->sessionWithMdoDuties([['09:00:00', '10:00:00'], ['14:00:00', '15:00:00']])));
    }

    /* ---------------- F-062 ---------------- */

    /** The attendance sheet names why an exempt OT is Present (operator decision, 2026-10-09). */
    public function test_the_attendance_sheet_names_the_exemption_and_keeps_the_duty_columns_factual(): void
    {
        $s = $this->sessionWithMdoDuties([['14:00:00', '15:00:00']]);
        $row = function () use ($s) {
            $record = (object) [
                'studentsMaster' => StudentMaster::find($s['student']),
                'attendance' => CourseStudentAttendance::where('timetable_pk', $s['timetable'])->get(),
            ];
            $export = new AttendanceDataExport(collect([$record]), '', '', '', '', '', $s['course'], $s['group'], $s['timetable'], $s['day'], '14:00 to 15:00');

            return array_combine($export->headings(), $export->array()[0]);
        };

        // MDO duty alone: no exemption.
        $this->assertSame(['Present', 'MDO Duty', 'No'], [$row()['Attendance Status'], $row()['MDO Duty'], $row()['Exemption'] ?? null]);

        // Add a medical exemption over the session: Present, the sheet says Medical, and the MDO duty is still shown.
        DB::table('student_medical_exemption')->insert([
            'course_master_pk' => $s['course'], 'student_master_pk' => $s['student'],
            'employee_master_pk' => 0, 'exemption_category_master_pk' => 0, 'exemption_medical_speciality_pk' => 0,
            'from_date' => $s['day'].' 00:00:00', 'to_date' => $s['day'].' 23:59:00', 'Description' => 'F-062 probe', 'active_inactive' => 1,
        ]);
        $this->assertSame(['Present', 'MDO Duty', 'Medical'], [$row()['Attendance Status'], $row()['MDO Duty'], $row()['Exemption'] ?? null]);
    }

    /* ---------------- F-057 ---------------- */

    /** Bulk harvesting is throttled; trainees are refused (operator decision, 2026-10-09). */
    public function test_the_faculty_contact_exports_are_throttled_per_login(): void
    {
        $staff = $this->staffWithRole('Super Admin');

        $get = fn (string $route) => $this->as($staff, ['Super Admin'])->get(route($route));

        // One budget of 10 a minute across both lists.
        for ($i = 1; $i <= 5; $i++) {
            $get('admin.dashboard.guest_faculty.export')->assertOk();
            $get('admin.dashboard.inhouse_faculty.export')->assertOk();
        }
        $get('admin.dashboard.guest_faculty.export')->assertStatus(429);
        $get('admin.dashboard.inhouse_faculty.export')->assertStatus(429);

        // Its own bucket: other throttled routes are not spent by it.
        $this->assertNotSame(429, $this->as($staff, ['Super Admin'])->post(route('admin.dashboard.report-issue'))->getStatusCode());
    }

    public function test_a_trainee_cannot_download_the_faculty_contact_exports(): void
    {
        $ot = $this->officerTrainee();

        $this->as($ot, ['Student-OT'])->get(route('admin.dashboard.guest_faculty.export'))->assertForbidden();
        $this->as($ot, ['Student-OT'])->get(route('admin.dashboard.inhouse_faculty.export'))->assertForbidden();

        // Control: a staff login still downloads.
        $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])->get(route('admin.dashboard.guest_faculty.export'))->assertOk();
    }

    /* ---------------- F-055 ---------------- */

    /**
     * The rollback runbook (reviews/pr-334-release-runbook.md, step R-2) deactivates
     * every live notice whose audience main's feed cannot read. Same WHERE clause.
     */
    public const F055_SELECTION = "SELECT n.pk FROM notices_notification n
         WHERE n.active_inactive = 1
           AND n.expiry_date >= CURDATE()
           AND (n.notice_type = 'Personal'
                OR n.audience_mode IN ('group', 'individual')
                OR n.department_master_pk IS NOT NULL
                OR n.group_type_map_pk IS NOT NULL
                OR EXISTS (SELECT 1 FROM notice_audience_map m
                            WHERE m.notices_notification_pk = n.pk
                              AND m.active_inactive = 1
                              AND m.audience_type IN ('D', 'E', 'S', 'G')))";

    public function test_the_rollback_selection_catches_every_notice_main_would_widen(): void
    {
        $employee = DB::table('employee_master')->where('department_master_pk', '>', 0)->orderBy('pk')->first(['pk', 'department_master_pk']);
        $otherDept = DB::table('department_master')->where('pk', '!=', $employee->department_master_pk ?? -1)->where('pk', '>', 0)->value('pk');
        if (! $employee || ! $otherDept) {
            $this->markTestSkipped('needs an employee with a department and a second department');
        }

        $as = fn () => $this->as($this->staffWithRole('Super Admin'), ['Super Admin']);
        $post = function (string $title, string $type, string $audience, array $extra) use ($as): int {
            $as()->post(route('admin.notice.store'), [
                'notice_title' => $title, 'description' => 'F-055 probe', 'notice_type' => $type,
                'display_date' => now()->toDateString(), 'expiry_date' => now()->addDays(10)->toDateString(),
                'target_audience' => $audience,
            ] + $extra)->assertSessionHasNoErrors();
            $pk = (int) DB::table('notices_notification')->where('notice_title', $title)->value('pk');
            DB::table('notices_notification')->where('pk', $pk)->update(['active_inactive' => 1]);

            return $pk;
        };

        $tag = random_int(100000, 999999);
        $personal = $post("F055 personal {$tag}", 'Personal', 'Staff/Faculty', [
            'department_master_pks' => [(string) $employee->department_master_pk], 'staff_scope' => 'individual', 'employee_pks' => [(string) $employee->pk],
        ]);
        $twoDepartments = $post("F055 departments {$tag}", 'Office notice', 'Staff/Faculty', [
            'department_master_pks' => [(string) $employee->department_master_pk, (string) $otherDept], 'staff_scope' => 'all',
        ]);
        $everyone = $post("F055 all {$tag}", 'Office notice', 'All', []);

        $selected = collect(DB::select(self::F055_SELECTION))->pluck('pk')->map(fn ($pk) => (int) $pk)->all();

        $this->assertContains($personal, $selected, 'a Personal notice to one employee');
        $this->assertContains($twoDepartments, $selected, 'a notice pinned to two departments (no scalar column set)');
        $this->assertNotContains($everyone, $selected, 'an "All" notice reads the same on main');
    }

    /* ---------------- F-056 ---------------- */

    public function test_array_filters_on_the_notice_list_do_not_fail_the_page(): void
    {
        $as = fn () => $this->as($this->staffWithRole('Super Admin'), ['Super Admin']);

        $as()->get(route('admin.notice.index', ['search' => 'x']))->assertOk();
        // menu / category are read by the shared sidebar and breadcrumb on every page.
        foreach (['search', 'notice_type', 'status', 'year', 'year_field', 'course_id', 'department_id', 'menu', 'category'] as $param) {
            $status = $as()->get(route('admin.notice.index', [$param => ['x']]))->getStatusCode();
            $this->assertSame(200, $status, "{$param}[]=x");
        }
    }
}
