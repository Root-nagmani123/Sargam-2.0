<?php

namespace Tests\E2E;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Server half of tests/e2e/notice-ot-course-readd.spec.js (PR #334 F-083). Outside the
 * PHPUnit suites; the spec runs it with E2E_OUT set. Every write is rolled back.
 *
 * E2E_PHASE=render  store a Personal OT notice for one trainee and write its
 *                   controller-rendered edit page and the real lookup responses.
 * E2E_PHASE=replay  store the same notice, PUT the body the browser posted
 *                   (posted.json), and write the stored audience and whether a
 *                   classmate of the trainee then sees it.
 * E2E_VIEW_OVERLAY  a directory searched before resources/views (the red run renders
 *                   another commit's partial from there).
 */
class NoticeOtEditPageFixture extends TestCase
{
    use RollsBackAgainstAppDatabase;

    public function test_fixture(): void
    {
        $out = getenv('E2E_OUT');
        if (! $out) {
            $this->markTestSkipped('run by tests/e2e/notice-ot-course-readd.spec.js');
        }
        if ($overlay = getenv('E2E_VIEW_OVERLAY')) {
            View::getFinder()->prependLocation($overlay);
        }

        [$course, $student, $classmate] = $this->twoTraineesOfOneCourse();
        $admin = $this->as($this->staffWithRole('Super Admin'), ['Super Admin']);
        $base = [
            'notice_title' => 'F-083 e2e', 'description' => 'F-083 e2e', 'notice_type' => 'Personal',
            'display_date' => now()->toDateString(), 'expiry_date' => now()->addDays(10)->toDateString(),
            'target_audience' => 'Office trainee',
        ];
        $base['notice_title'] .= ' '.random_int(100000, 999999);
        $admin->post(route('admin.notice.store'), $base + [
            'course_master_pks' => [(string) $course], 'ot_scope' => 'individual', 'student_pks' => [(string) $student],
        ])->assertSessionHasNoErrors();
        $pk = (int) DB::table('notices_notification')->where('notice_title', $base['notice_title'])->value('pk');
        DB::table('notices_notification')->where('pk', $pk)->update(['active_inactive' => 1]);

        if (getenv('E2E_PHASE') === 'render') {
            $editUrl = route('admin.notice.edit', Crypt::encrypt($pk));
            file_put_contents("{$out}/edit.html", $admin->get($editUrl)->assertOk()->getContent());
            $lookup = fn (string $name, array $query) => json_decode($admin->getJson(route($name, $query))->assertOk()->getContent(), true);
            file_put_contents("{$out}/fixture.json", json_encode([
                'course' => (string) $course,
                'student' => (string) $student,
                'editUrl' => $editUrl,
                'paths' => [
                    'courses' => parse_url(route('admin.notice.getCourses'), PHP_URL_PATH),
                    'groupTypes' => parse_url(route('admin.notice.getGroupTypes'), PHP_URL_PATH),
                    'students' => parse_url(route('admin.notice.getStudents'), PHP_URL_PATH),
                ],
                'courses' => $lookup('admin.notice.getCourses', ['include' => [$course]]),
                'groupTypes' => $lookup('admin.notice.getGroupTypes', ['course_master_pks' => [$course]]),
                'students' => $lookup('admin.notice.getStudents', ['course_master_pks' => [$course]]),
            ]));

            return;
        }

        $posted = json_decode(file_get_contents("{$out}/posted.json"), true);
        $response = $admin->put(route('admin.notice.update', Crypt::encrypt($pk)), $base + $posted);

        $this->actingAs($classmate);
        session(['user_roles' => ['Student-OT']]);
        $seen = notice_feed_query_by_role('live')->pluck('notices_notification.pk')->map(fn ($x) => (int) $x)->contains($pk);

        file_put_contents("{$out}/replay.json", json_encode([
            'status' => $response->getStatusCode(),
            'errors' => session('errors') ? session('errors')->keys() : [],
            'audience_mode' => DB::table('notices_notification')->where('pk', $pk)->value('audience_mode'),
            'rows' => DB::table('notice_audience_map')->where('notices_notification_pk', $pk)
                ->orderBy('audience_type')->orderBy('reference_pk')->get()
                ->map(fn ($r) => $r->audience_type.':'.$r->reference_pk)->all(),
            'classmate_sees_it' => $seen,
        ]));
    }

    /** Same fixture as NoticeOtIndividualWideningTest: [course, student, a classmate's category-S login]. */
    private function twoTraineesOfOneCourse(): array
    {
        $enrolled = DB::table('student_master_course__map as c')
            ->join('user_credentials as u', function ($j) {
                $j->on('u.user_id', '=', 'c.student_master_pk')->where('u.user_category', 'S');
            })
            ->orderByRaw('c.course_master_pk = 1996272429 desc, c.student_master_pk = 42305 desc')
            ->orderBy('c.course_master_pk')
            ->limit(2000)
            ->get(['c.course_master_pk as course', 'c.student_master_pk as student', 'u.pk as user_pk']);

        foreach ($enrolled->groupBy('course') as $course => $rows) {
            $first = $rows->first();
            $other = $rows->first(fn ($r) => (int) $r->student !== (int) $first->student);
            if ($other) {
                return [(int) $course, (int) $first->student, User::findOrFail($other->user_pk)];
            }
        }

        $this->fail('needs two category-S trainees enrolled in one course');
    }
}
