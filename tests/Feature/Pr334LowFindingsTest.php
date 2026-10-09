<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LeaveApplicationService;
use App\Support\CourseRepositorySearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Regression tests for the Low / Advisory findings of the PR #334 round-2 review:
 * F-033, F-034, F-035, F-037, F-038 and F-030. Each case says what it guards.
 *
 * Requests go through the real HTTP kernel; writes roll back.
 */
class Pr334LowFindingsTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private function assertNotServerError($response, string $what): void
    {
        $this->assertLessThan(500, $response->getStatusCode(), "{$what} must not be a server error");
    }

    /* ------------------------------------------------------------------
     | F-035 — the OT's own /leave/store accepted deactivated and other-bucket
     | natures; only `exists` was checked.
     * ----------------------------------------------------------------- */

    /** @return array{0: User, 1: int} */
    private function otWithStationedLeaveConfigured(): array
    {
        $row = DB::table('user_credentials as u')
            ->join('student_master_course__map as smcm', 'smcm.student_master_pk', '=', 'u.user_id')
            ->join('course_master as cm', 'cm.pk', '=', 'smcm.course_master_pk')
            ->where('u.user_category', 'S')
            ->where('smcm.active_inactive', 1)
            ->where('cm.active_inactive', 1)
            ->orderByDesc('smcm.pk')
            ->first(['u.pk as user_pk']);
        if (! $row) {
            $this->markTestSkipped('no officer trainee with an active course enrolment');
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

        return [User::findOrFail($row->user_pk), (int) $context['student_pk']];
    }

    private function postStationedLeave(User $ot, int $naturePk, string $date)
    {
        return $this->as($ot, ['Student-OT'])->post('/leave/store', [
            'leave_type' => 'STATIONED_LEAVE',
            'leave_nature_master_pk' => $naturePk,
            'from_date' => $date,
            'to_date' => $date,
            'time_from' => '09:00',
            'time_to' => '18:00',
            'reason' => 'F-035 probe',
            'contact_number' => '9876543210',
            'submit_action' => 'submit',
        ]);
    }

    public function test_f035_the_ots_leave_store_refuses_inactive_and_other_bucket_natures(): void
    {
        [$ot, $studentPk] = $this->otWithStationedLeaveConfigured();
        $date = now()->addDays(210)->toDateString();
        $before = DB::table('leave_application')->where('student_master_pk', $studentPk)->count();

        $otherBucket = (int) DB::table('leave_nature_master')->where('leave_type', 'PT_EXEMPTION')->value('pk');
        $inactive = (int) DB::table('leave_nature_master')->insertGetId([
            'leave_type' => 'STATIONED_LEAVE', 'nature_name' => 'F-035 inactive '.uniqid(),
            'display_order' => 999, 'active_inactive' => 2, 'created_date' => now(), 'modified_date' => now(),
        ]);

        foreach (array_filter([$otherBucket, $inactive]) as $nature) {
            $this->postStationedLeave($ot, $nature, $date)->assertSessionHasErrors('leave_nature_master_pk');
        }
        $this->assertSame($before, DB::table('leave_application')->where('student_master_pk', $studentPk)->count());

        // Control: an active nature of the right bucket is still accepted.
        $good = (int) DB::table('leave_nature_master')->where('leave_type', 'STATIONED_LEAVE')->where('active_inactive', 1)->value('pk');
        $this->postStationedLeave($ot, $good, $date)->assertSessionHasNoErrors();
        $this->assertSame($before + 1, DB::table('leave_application')->where('student_master_pk', $studentPk)->count());
    }

    /* ------------------------------------------------------------------
     | F-034 — canSeeHousePerformance() had no trainee exclusion: an OT login
     | given a staff role read every trainee's discipline deductions.
     * ----------------------------------------------------------------- */

    public function test_f034_a_trainee_holding_a_widget_role_is_still_refused_house_performance(): void
    {
        $roleId = DB::table('dashboard_cards as c')
            ->join('role_dashboard_cards as rc', 'rc.dashboard_card_id', '=', 'c.id')
            ->join('roles as r', 'r.id', '=', 'rc.role_id')
            ->where('c.key', 'widget_house_performance')
            ->where('r.name', '!=', 'Super Admin')
            ->value('r.id');
        if (! $roleId) {
            $this->markTestSkipped('the house widget is assigned to no non-Super-Admin role here');
        }

        $ot = $this->officerTrainee();
        DB::table('model_has_roles')->insertOrIgnore(['role_id' => $roleId, 'model_type' => User::class, 'model_id' => $ot->pk]);

        $url = route('admin.dashboard.house-wise-performance');
        $this->as($ot->fresh(), ['Student-OT'])->get($url)->assertForbidden();
        $this->as($ot->fresh(), ['Student-OT'])->get($url.'?format=excel')->assertForbidden();
    }

    /* ------------------------------------------------------------------
     | F-037 — array-valued inputs reached string casts and returned 500.
     * ----------------------------------------------------------------- */

    public function test_f037_array_inputs_no_longer_return_500(): void
    {
        $admin = $this->staffWithRole('Super Admin');
        $as = fn () => $this->as($admin, ['Super Admin']);

        $this->assertNotServerError($as()->get(route('admin.dashboard.feed', ['tab' => 'notices', 'notice_type' => ['x']])), 'feed ?notice_type[]=');
        $this->assertNotServerError($as()->get(route('admin.dashboard.feed', ['tab' => 'notices', 'q' => ['x'], 'notice_dept' => ['x']])), 'feed ?q[]=');
        $this->assertNotServerError($as()->get(route('admin.dashboard.ot-participants', ['counsellor_faculty' => ['1']])), 'participants ?counsellor_faculty[]=');
        $this->assertNotServerError($as()->getJson(route('admin.dashboard.ot-participants', ['counsellor_faculty' => ['1'], 'draw' => 1])), 'participants data ?counsellor_faculty[]=');
        $this->assertNotServerError($as()->get(route('admin.dashboard.ot-participants.export', ['format' => 'excel', 'counsellor_faculty' => ['1']])), 'participants export ?counsellor_faculty[]=');

        $student = (int) DB::table('student_master')->orderByDesc('pk')->value('pk');
        $this->assertNotServerError(
            $as()->getJson(route('admin.dashboard.ot-participants.comments', ['id' => $student, 'search' => ['value' => ['a']]])),
            'comments ?search[value][]='
        );

        $this->assertNotServerError(
            $this->as($this->officerTrainee(), ['Student-OT'])->get('/leave/apply?leave_type[]=x'),
            '/leave/apply?leave_type[]='
        );
    }

    /* ------------------------------------------------------------------
     | F-038 — My Counsellees counted sessions saved as MDO / Escort / Medical /
     | Other (4-7) as absent, while the student list counts them present.
     * ----------------------------------------------------------------- */

    /** A faculty login counselling one trainee in a group on a running course, built in the transaction. */
    private function counselleeFixture(): object
    {
        $course = DB::table('course_master')->where('active_inactive', 1)
            ->where('end_date', '>=', now()->toDateString())->orderBy('pk')->value('pk');
        $faculty = DB::table('faculty_master as f')
            ->join('user_credentials as u', 'u.user_id', '=', 'f.employee_master_pk')
            ->where('u.user_category', '!=', 'S')
            ->orderBy('f.pk')
            ->first(['f.pk as faculty_pk', 'u.pk as user_pk']);
        $student = DB::table('student_master')->orderByDesc('pk')->value('pk');
        $type = DB::table('course_group_type_master')->where('active_inactive', 1)->value('pk');
        if (! $course || ! $faculty || ! $student || ! $type) {
            $this->markTestSkipped('needs a running course, a faculty login, a student and a group type');
        }

        $group = DB::table('group_type_master_course_master_map')->insertGetId([
            'type_name' => $type, 'group_name' => 'F-038 probe', 'course_name' => $course,
            'facility_id' => $faculty->faculty_pk, 'active_inactive' => 1,
            'created_date' => now(), 'modified_date' => now(),
        ]);
        DB::table('student_course_group_map')->insert([
            'student_master_pk' => $student, 'group_type_master_course_master_map_pk' => $group,
            'active_inactive' => 1, 'created_date' => now(), 'modified_date' => now(),
        ]);

        return (object) ['user_pk' => $faculty->user_pk, 'student' => $student, 'course' => $course];
    }

    public function test_f038_a_duty_saved_session_counts_like_a_present_one_in_my_counsellees(): void
    {
        $mapping = DB::table('group_type_master_course_master_map as g')
            ->join('course_master as cm', 'cm.pk', '=', 'g.course_name')
            ->join('faculty_master as f', 'f.pk', '=', 'g.facility_id')
            ->join('user_credentials as u', 'u.user_id', '=', 'f.employee_master_pk')
            ->join('student_course_group_map as s', 's.group_type_master_course_master_map_pk', '=', 'g.pk')
            ->where('g.active_inactive', 1)->where('cm.active_inactive', 1)
            ->where('cm.end_date', '>=', now()->toDateString())
            ->where('s.active_inactive', 1)
            ->where('u.user_category', '!=', 'S')
            ->first(['u.pk as user_pk', 's.student_master_pk as student', 'g.course_name as course']);
        $mapping ??= $this->counselleeFixture();

        $faculty = User::findOrFail($mapping->user_pk);
        $template = DB::table('course_student_attendance')->orderByDesc('pk')->first();
        if (! $template) {
            $this->markTestSkipped('no attendance row to copy');
        }

        $pctAfterAdding = function (string $status) use ($faculty, $mapping, $template) {
            $row = (array) $template;
            unset($row['pk']);
            $pk = DB::table('course_student_attendance')->insertGetId(array_merge($row, [
                'Student_master_pk' => $mapping->student,
                'course_master_pk' => $mapping->course,
                'status' => $status,
                'timetable_pk' => null,
            ]));

            $counselees = $this->as($faculty, $faculty->roles()->pluck('name')->all())
                ->get(route('admin.dashboard.my-counselee'))
                ->assertOk()
                ->viewData('counselees');
            DB::table('course_student_attendance')->where('pk', $pk)->delete();

            $code = DB::table('student_master')->where('pk', $mapping->student)->value('generated_OT_code') ?? ('STU-'.$mapping->student);
            $mine = collect($counselees)->firstWhere('id', $code);
            $this->assertNotNull($mine, 'the counsellee must be listed, or this test proves nothing');

            return $mine['attendance'];
        };

        $this->assertSame($pctAfterAdding('1'), $pctAfterAdding('5'), 'a session saved as Escort (5) counts like a Present (1) one');
    }

    /* ------------------------------------------------------------------
     | F-033 — a session whose documents were all deleted came back in search as
     | a video-only result, and an empty video link could not clear the field.
     * ----------------------------------------------------------------- */

    private function forgetFolderCaches(): void
    {
        foreach (['folderTree', 'folderChildren', 'hiddenFolderPks'] as $property) {
            if (property_exists(CourseRepositorySearch::class, $property)) {
                $p = new \ReflectionProperty(CourseRepositorySearch::class, $property);
                $p->setAccessible(true);
                $p->setValue(null, null);
            }
        }
    }

    /** @return array{0: int, 1: string, 2: int}  detail pk, topic, document pk */
    private function sessionWithVideoAndDocument(): array
    {
        $folder = DB::table('course_repository_master')->where('del_folder_status', 1)->orderBy('pk')->value('pk');
        if (! $folder) {
            $this->markTestSkipped('no live repository folder');
        }

        $topic = 'Zqf033'.random_int(100000, 999999);
        $detail = (int) DB::table('course_repository_details')->insertGetId([
            'course_repository_master_pk' => $folder,
            'topic_pk' => $topic,
            'videolink' => 'https://example.invalid/video/'.$topic,
            'video_download_enabled' => 1,
            'status' => 1,
            'created_date' => now(),
        ]);
        $doc = (int) DB::table('course_repository_documents')->insertGetId([
            'course_repository_details_pk' => $detail,
            'course_repository_master_pk' => $folder,
            'upload_document' => 'zq.pdf',
            'file_title' => $topic,
            'del_type' => 1,
        ]);

        return [$detail, $topic, $doc];
    }

    public function test_f033_a_session_whose_documents_were_deleted_is_not_a_video_only_hit(): void
    {
        [$detail, $topic, $doc] = $this->sessionWithVideoAndDocument();
        DB::table('course_repository_documents')->where('pk', $doc)->update(['del_type' => 0]);
        $this->forgetFolderCaches();

        $hits = CourseRepositorySearch::documentQuery(CourseRepositorySearch::criteria(Request::create('/', 'GET', ['q' => $topic])))
            ->where('doc.course_repository_details_pk', $detail)
            ->count();

        $this->assertSame(0, $hits);
    }

    public function test_f033_an_empty_video_link_clears_the_stored_link(): void
    {
        [$detail, , $doc] = $this->sessionWithVideoAndDocument();

        $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])
            ->postJson(route('course-repository.document.update', $doc), ['video_link' => ''])
            ->assertOk();

        $this->assertNull(DB::table('course_repository_details')->where('pk', $detail)->value('videolink'));
    }

    public function test_f033_an_update_that_does_not_post_the_field_keeps_the_link(): void
    {
        [$detail, $topic, $doc] = $this->sessionWithVideoAndDocument();

        $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])
            ->postJson(route('course-repository.document.update', $doc), ['file_title' => $topic.' renamed'])
            ->assertOk();

        $this->assertSame('https://example.invalid/video/'.$topic, DB::table('course_repository_details')->where('pk', $detail)->value('videolink'));
    }

    /* ------------------------------------------------------------------
     | F-030 — Choices.js from an unpinned CDN URL with no SRI.
     * ----------------------------------------------------------------- */

    public function test_f030_both_notice_forms_load_a_pinned_choices_build_with_sri_and_no_html(): void
    {
        $admin = $this->staffWithRole('Super Admin');
        $notice = (int) DB::table('notices_notification')->orderByDesc('pk')->value('pk');
        if (! $notice) {
            $this->markTestSkipped('no notice to edit');
        }

        foreach ([route('admin.notice.create'), route('admin.notice.edit', Crypt::encrypt($notice))] as $url) {
            $html = $this->as($admin, ['Super Admin'])->get($url)->assertOk()->getContent();

            $this->assertStringNotContainsString('npm/choices.js/', $html, "{$url}: no unpinned URL");
            $this->assertStringContainsString('choices.js@11.2.4/public/assets/scripts/choices.min.js" integrity="sha384-0dGX4oSRqvcKtSNa5YGzTI3pkW4p3uoor1Izx0R5/7emRjiR619oCIBVnvM9k3tj" crossorigin="anonymous"', $html);
            $this->assertStringContainsString('choices.js@11.2.4/public/assets/styles/choices.min.css" integrity="sha384-uLFsUvpIOC9MdzZeDr7vuIUG6LgTaguBn469csIwzNIG9N9TFVoYgM4Ov5PG1V1l" crossorigin="anonymous"', $html);
            $this->assertStringContainsString('allowHTML: false', $html);
        }
    }
}
