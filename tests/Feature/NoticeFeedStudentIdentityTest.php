<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #334 F-067: the notice feed read user_id as a student_master pk for any login
 * whose session held 'Student-OT', whatever its category. The Moodle-token branch of
 * Authenticate sets that role for any user it finds, and many empty-category logins
 * carry a user_id equal to some other student's pk - so such a login received that
 * student's individual and group notices. Only a category-S login's user_id is a
 * student pk now (the student-side counterpart of F-058).
 */
class NoticeFeedStudentIdentityTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /** A category-S login whose student is enrolled in a course and an active group of it. */
    private function studentFixture(): array
    {
        $row = DB::table('user_credentials as u')
            ->join('student_master_course__map as c', 'c.student_master_pk', '=', 'u.user_id')
            ->join('student_course_group_map as g', 'g.student_master_pk', '=', 'u.user_id')
            ->join('group_type_master_course_master_map as gm', function ($j) {
                $j->on('gm.pk', '=', 'g.group_type_master_course_master_map_pk')->on('gm.course_name', '=', 'c.course_master_pk');
            })
            ->where('u.user_category', 'S')
            ->where('g.active_inactive', 1)
            ->orderBy('u.pk')
            ->first(['u.pk as user_pk', 'u.user_id as student', 'c.course_master_pk as course', 'gm.pk as grp']);
        if (! $row) {
            $this->markTestSkipped('needs a category-S login enrolled in a course and an active group of it');
        }

        return [User::findOrFail($row->user_pk), (int) $row->student, (int) $row->course, (int) $row->grp];
    }

    /** A live Office-trainee notice for one course, narrowed by $scope, through the store route. */
    private function otNotice(int $course, string $scope, array $targets): int
    {
        $title = 'F-067 probe '.random_int(100000, 999999);
        $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])->post(route('admin.notice.store'), [
            'notice_title' => $title, 'description' => 'probe', 'notice_type' => 'Personal',
            'display_date' => now()->toDateString(), 'expiry_date' => now()->addDays(10)->toDateString(),
            'target_audience' => 'Office trainee', 'course_master_pks' => [(string) $course],
            'ot_scope' => $scope,
        ] + $targets)->assertSessionHasNoErrors();
        $pk = (int) DB::table('notices_notification')->where('notice_title', $title)->value('pk');
        $this->assertGreaterThan(0, $pk, 'notice was stored');
        DB::table('notices_notification')->where('pk', $pk)->update(['active_inactive' => 1]);

        return $pk;
    }

    private function feedFor(User $user, array $roles): array
    {
        $this->actingAs($user);
        session(['user_roles' => $roles]);

        return notice_feed_query_by_role('live')->pluck('notices_notification.pk')->map(fn ($pk) => (int) $pk)->all();
    }

    public function test_an_empty_category_login_is_not_read_as_the_student_its_user_id_matches(): void
    {
        [$login, $student, $course, $group] = $this->studentFixture();
        $individual = $this->otNotice($course, 'individual', ['student_pks' => [(string) $student]]);
        $grouped = $this->otNotice($course, 'group', ['group_type_map_pks' => [(string) $group]]);

        // Control: the student's own category-S login sees both.
        $own = $this->feedFor($login->fresh(), ['Student-OT']);
        $this->assertContains($individual, $own);
        $this->assertContains($grouped, $own);

        // Another login, no category, session role Student-OT (as the Moodle-token branch sets),
        // whose user_id equals that student's pk.
        $other = DB::table('user_credentials')->where('pk', '!=', $login->pk)->orderBy('pk')->value('pk');
        DB::table('user_credentials')->where('pk', $other)->update(['user_category' => null, 'user_id' => $student]);

        $feed = $this->feedFor(User::findOrFail($other), ['Student-OT']);
        $this->assertNotContains($individual, $feed, "another login does not get the student's individual notice");
        $this->assertNotContains($grouped, $feed, "another login does not get the student's group notice");
    }
}
