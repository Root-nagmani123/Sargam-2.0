<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LeaveApplicationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #334 round-4 low findings, each through the route that showed it:
 *
 *   F-050  ?filter_date[]= on the OT attendance page, feed and export -> 500
 *   F-051  the OT's own /leave/store with nature[]= -> 500
 *   F-042  Leave on Behalf: nature[]= -> 500; a time-suffixed range slipped past
 *          the overlap check
 *   F-024  Leave on Behalf: a repeat submit recorded twice; an inactive nature was accepted
 *   F-023  Leave on Behalf grid: names escaped twice
 *   F-045  a filter-only repository search listed every folder as a category
 *   F-043  My Leave Excel / PDF named no trainee
 *
 * The Leave on Behalf cases need a running course, which the development copy
 * lacks; the fixture makes one inside the rolled-back transaction.
 */
class Pr334Round4LowFindingsTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /* ---------------- F-050 ---------------- */

    public function test_an_array_filter_date_does_not_fail_the_ot_attendance_page_feed_or_export(): void
    {
        $row = DB::table('user_credentials as u')
            ->join('student_course_group_map as scgm', 'scgm.student_master_pk', '=', 'u.user_id')
            ->join('group_type_master_course_master_map as g', 'g.pk', '=', 'scgm.group_type_master_course_master_map_pk')
            ->where('u.user_category', 'S')->where('scgm.active_inactive', 1)
            ->orderByDesc('scgm.pk')
            ->first(['u.pk as user_pk', 'u.user_id as student', 'g.pk as grp', 'g.course_name as course']);
        if (! $row) {
            $this->markTestSkipped('no OT in a group');
        }

        $as = fn () => $this->as(User::findOrFail($row->user_pk), ['Student-OT']);
        $params = ['group_pk' => $row->grp, 'course_pk' => $row->course, 'timetable_pk' => 0, 'student_pk' => $row->student, 'filter_date' => ['x']];

        $statuses = [
            'page' => $as()->get(route('attendance.OT.student_mark.student', $params))->getStatusCode(),
            'feed' => $as()->getJson(route('ot.student.attendance.data', $params))->getStatusCode(),
            'export' => $as()->get(route('attendance.OT.student_mark.export', $params))->getStatusCode(),
        ];

        // Exactly 200: "below 500" also passed a 403 or a redirect that never reached the filter (F-064).
        $this->assertSame(['page' => 200, 'feed' => 200, 'export' => 200], $statuses);
    }

    /* ---------------- F-051 ---------------- */

    public function test_the_ots_own_leave_store_refuses_an_array_nature_as_a_validation_error(): void
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
        $date = now()->addDays(210)->toDateString();
        $payload = [
            'leave_type' => 'STATIONED_LEAVE',
            'from_date' => $date, 'to_date' => $date, 'time_from' => '09:00', 'time_to' => '18:00',
            'reason' => 'F-051 probe', 'contact_number' => '9876543210', 'submit_action' => 'submit',
        ];
        $as = fn () => $this->as(User::findOrFail($row->user_pk), ['Student-OT']);

        $as()->post('/leave/store', $payload + ['leave_nature_master_pk' => [$nature]])
            ->assertRedirect()->assertSessionHasErrors('leave_nature_master_pk');

        // Control: the scalar is accepted.
        $as()->post('/leave/store', $payload + ['leave_nature_master_pk' => $nature])
            ->assertRedirect()->assertSessionHasNoErrors();
    }

    /* ---------------- Leave on Behalf: F-042, F-024, F-023 ---------------- */

    /** @return array<string, mixed> a valid store payload for an OT enrolled on a course made running for this test */
    private function onBehalfPayload(): array
    {
        $enrolment = DB::table('student_master_course__map as m')
            ->join('course_master as c', 'c.pk', '=', 'm.course_master_pk')
            ->join('student_master as s', 's.pk', '=', 'm.student_master_pk')
            ->where('m.active_inactive', 1)
            ->orderByDesc('m.pk')
            ->first(['m.course_master_pk', 'm.student_master_pk']);
        $nature = DB::table('leave_nature_master')->where('leave_type', 'LEAVE')->where('active_inactive', 1)->value('pk');
        if (! $enrolment || ! $nature) {
            $this->markTestSkipped('needs an enrolment and an active LEAVE nature');
        }

        DB::table('course_master')->where('pk', $enrolment->course_master_pk)
            ->update(['active_inactive' => 1, 'end_date' => now()->addYears(3)->toDateString()]);
        DB::table('stationed_leave_master')->insert([
            'course_master_pk' => $enrolment->course_master_pk,
            'effective_from' => '2000-01-01',
            'is_faculty_approval_required' => 0,
            'active_inactive' => 1,
        ]);

        $day = now()->addDays(420)->toDateString();

        return [
            'course_master_pk' => $enrolment->course_master_pk,
            'student_master_pk' => $enrolment->student_master_pk,
            'leave_nature_master_pk' => $nature,
            'from_date' => $day,
            'to_date' => $day,
            'time_from' => '09:00',
            'time_to' => '18:00',
            'contact_number' => '9876543210',
            'reason' => 'PR334 on-behalf probe',
        ];
    }

    private function storeOnBehalf(array $payload)
    {
        return $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])->post(route('admin.leave-on-behalf.store'), $payload);
    }

    private function onBehalfRows(array $payload): int
    {
        return DB::table('leave_application')
            ->where('student_master_pk', $payload['student_master_pk'])
            ->whereDate('from_date', substr($payload['from_date'], 0, 10))
            ->count();
    }

    public function test_on_behalf_refuses_an_array_nature_as_a_validation_error(): void
    {
        $payload = $this->onBehalfPayload();

        $this->storeOnBehalf(['leave_nature_master_pk' => [$payload['leave_nature_master_pk']]] + $payload)
            ->assertRedirect()->assertSessionHasErrors('leave_nature_master_pk');
        $this->assertSame(0, $this->onBehalfRows($payload));
    }

    public function test_on_behalf_refuses_a_time_suffixed_range_over_an_existing_leave(): void
    {
        $payload = $this->onBehalfPayload();
        $this->storeOnBehalf($payload)->assertSessionHasNoErrors()->assertRedirect();

        $this->storeOnBehalf(['from_date' => $payload['from_date'].' 00:00:01', 'to_date' => $payload['to_date'].' 23:59:59'] + $payload)
            ->assertSessionHasErrors('from_date');

        $this->assertSame(1, $this->onBehalfRows($payload), 'one leave for that day');
    }

    public function test_on_behalf_records_a_repeated_submit_once(): void
    {
        $payload = $this->onBehalfPayload();

        $this->storeOnBehalf($payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->storeOnBehalf($payload)->assertSessionHasErrors('from_date');

        $this->assertSame(1, $this->onBehalfRows($payload));
    }

    public function test_on_behalf_refuses_an_inactive_nature(): void
    {
        $payload = $this->onBehalfPayload();
        DB::table('leave_nature_master')->where('pk', $payload['leave_nature_master_pk'])->update(['active_inactive' => 0]);

        $this->storeOnBehalf($payload)->assertSessionHasErrors('leave_nature_master_pk');
        $this->assertSame(0, $this->onBehalfRows($payload));
    }

    public function test_the_on_behalf_grid_escapes_a_name_once(): void
    {
        $payload = $this->onBehalfPayload();
        DB::table('student_master')->where('pk', $payload['student_master_pk'])->update(['display_name' => "D'Souza & Co"]);
        $this->storeOnBehalf($payload)->assertSessionHasNoErrors();

        $json = $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])
            ->getJson(route('admin.leave-on-behalf.index', ['draw' => 1, 'start' => 0, 'length' => 10]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data');

        $row = collect($json)->firstWhere('reason_text', 'PR334 on-behalf probe');
        $this->assertNotNull($row, 'the recorded leave is in the grid');
        // Yajra escapes once for the browser; a second e() showed "&amp;#039;" as text.
        $this->assertSame(e("D'Souza & Co"), $row['ot_name']);
    }

    /* ---------------- F-045 ---------------- */

    public function test_a_filter_only_repository_search_lists_no_categories(): void
    {
        $as = $this->as($this->staffWithRole('Super Admin'), ['Super Admin']);

        $filtersOnly = $as->get(route('admin.course-repository.user.search', ['author' => 'zzzz-no-such-author']))->assertOk();
        $this->assertSame(0, $filtersOnly->viewData('categoryTotal'));

        // Control: words still match folders.
        $folder = DB::table('course_repository_master')->where('del_folder_status', 1)->whereNull('parent_type')
            ->whereRaw("course_repository_name REGEXP '^[A-Za-z]{4,}'")->value('course_repository_name');
        if ($folder) {
            $word = strtok($folder, ' ');
            $this->assertGreaterThan(0, $as->get(route('admin.course-repository.user.search', ['q' => $word]))->viewData('categoryTotal'), "a search for '{$word}' still finds folders");
        }
    }

    /* ---------------- F-043 ---------------- */

    public function test_my_leave_exports_name_the_trainee(): void
    {
        $row = DB::table('user_credentials as u')
            ->join('student_master_course__map as smcm', 'smcm.student_master_pk', '=', 'u.user_id')
            ->join('student_master as sm', 'sm.pk', '=', 'u.user_id')
            ->where('u.user_category', 'S')->where('smcm.active_inactive', 1)
            ->where('sm.display_name', '!=', '')
            ->orderByDesc('smcm.pk')
            ->first(['u.pk as user_pk', 'sm.display_name']);
        if (! $row) {
            $this->markTestSkipped('needs an enrolled OT with a display name');
        }
        $as = fn () => $this->as(User::findOrFail($row->user_pk), ['Student-OT']);
        $name = trim($row->display_name);

        $xlsx = $as()->get(route('leave.my-leave.export'))->assertOk();
        $cells = [];
        foreach (IOFactory::load($xlsx->baseResponse->getFile()->getPathname())->getActiveSheet()->toArray() as $line) {
            $cells = array_merge($cells, array_map('strval', $line));
        }
        $this->assertStringContainsString('Officer Trainee: '.$name, implode("\n", $cells), 'the Excel names the trainee');

        $rendered = null;
        View::composer('admin.exports.table_pdf', function ($view) use (&$rendered) {
            $rendered = $view->getData();
        });
        $as()->get(route('leave.my-leave.export', ['format' => 'pdf']))->assertOk();
        $this->assertStringContainsString('Officer Trainee: '.$name, (string) ($rendered['filterLine'] ?? ''), 'the PDF names the trainee');
    }
}
