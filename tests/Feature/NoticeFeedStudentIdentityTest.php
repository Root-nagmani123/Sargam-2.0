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

    /* ------------------------------------------------------------------
     | PR #334 F-069: some trainees' only login has no category. It carries their
     | own student pk and email, and arrives with the session role Student-OT
     | through the Moodle token. The F-067 fix stopped such a login receiving its
     | own course, group and individual notices.
     * ----------------------------------------------------------------- */

    /**
     * The fixture student's login turned into the shape found on the dev databases:
     * no category, user_id = the student, the student's email, and no category-S
     * login left for that student. Returns [login, student, course, group, email].
     */
    private function noCategoryOwnLogin(): array
    {
        [$login, $student, $course, $group] = $this->studentFixture();
        $email = 'f069.'.$student.'@probe.test';
        DB::table('student_master')->where('pk', $student)->update(['email' => $email]);
        DB::table('user_credentials')->where('user_category', 'S')->where('user_id', $student)
            ->where('pk', '!=', $login->pk)->update(['user_category' => 'E']);
        DB::table('user_credentials')->where('pk', $login->pk)
            ->update(['user_category' => null, 'email_id' => strtoupper($email)]);

        return [User::findOrFail($login->pk), $student, $course, $group, $email];
    }

    /** Individual, group and course-wide notices for the fixture student. */
    private function noticesFor(int $student, int $course, int $group): array
    {
        return [
            'individual' => $this->otNotice($course, 'individual', ['student_pks' => [(string) $student]]),
            'group' => $this->otNotice($course, 'group', ['group_type_map_pks' => [(string) $group]]),
            'course' => $this->otNotice($course, 'all', []),
        ];
    }

    public function test_a_no_category_login_that_is_provably_the_students_own_gets_its_notices(): void
    {
        [$login, $student, $course, $group] = $this->noCategoryOwnLogin();
        $notices = $this->noticesFor($student, $course, $group);

        $feed = $this->feedFor($login, ['Student-OT']);

        foreach ($notices as $kind => $pk) {
            $this->assertContains($pk, $feed, "the trainee's own no-category login gets their {$kind} notice");
        }
    }

    public function test_a_no_category_login_with_another_email_still_gets_none_of_them(): void
    {
        [$login, $student, $course, $group] = $this->noCategoryOwnLogin();
        $notices = $this->noticesFor($student, $course, $group);
        DB::table('user_credentials')->where('pk', $login->pk)->update(['email_id' => 'someone.else@probe.test']);

        $feed = $this->feedFor($login->fresh(), ['Student-OT']);

        foreach ($notices as $kind => $pk) {
            $this->assertNotContains($pk, $feed, "a login that only shares the user_id does not get the {$kind} notice");
        }
    }

    public function test_a_no_category_login_is_not_the_student_while_the_student_has_a_category_s_login(): void
    {
        // The F-067 shape with the email copied too: the category-S login is the
        // student's, so a second, uncategorised login is not read as them.
        [$login, $student, $course, $group, $email] = $this->noCategoryOwnLogin();
        $notices = $this->noticesFor($student, $course, $group);
        DB::table('user_credentials')->where('pk', $login->pk)->update(['user_category' => 'S']);

        $other = DB::table('user_credentials')->where('pk', '!=', $login->pk)->orderBy('pk')->value('pk');
        DB::table('user_credentials')->where('pk', $other)
            ->update(['user_category' => null, 'user_id' => $student, 'email_id' => $email]);

        $feed = $this->feedFor(User::findOrFail($other), ['Student-OT']);

        foreach ($notices as $kind => $pk) {
            $this->assertNotContains($pk, $feed, "a second login does not get the student's {$kind} notice");
        }
    }
}
