<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #334 F-074: F-069 let a no-category login that is provably a trainee's own
 * (user_id = the student, the student's email, no category-S login for that student)
 * read its own notices, but every other "a trainee sees only their own" gate still
 * accepted category S alone, so that trainee was refused its own attendance page and
 * feed (403; 200 before the PR). One rule, auth_student_master_pk(), now decides
 * "this login is student X" for the feed and every trainee gate. Another trainee's
 * record, and a login that only shares the user_id, stay refused.
 */
class TraineeIdentityRuleTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /**
     * A trainee enrolled in a course, whose login is turned into the F-069 shape: no
     * category, user_id = the student, the student's email (case differs), and no
     * category-S login left for that student. Returns [login, student, course].
     */
    private function noCategoryOwnLogin(): array
    {
        $row = DB::table('user_credentials as u')
            ->join('student_master_course__map as c', 'c.student_master_pk', '=', 'u.user_id')
            ->join('course_master as cm', 'cm.pk', '=', 'c.course_master_pk')
            ->where('u.user_category', 'S')
            ->orderBy('u.pk')
            ->first(['u.pk as user_pk', 'u.user_id as student', 'c.course_master_pk as course']);
        if (! $row) {
            $this->markTestSkipped('needs a category-S login enrolled in a course');
        }

        $student = (int) $row->student;
        $email = 'f074.'.$student.'@probe.test';
        DB::table('student_master')->where('pk', $student)->update(['email' => $email]);
        DB::table('user_credentials')->where('user_category', 'S')->where('user_id', $student)
            ->where('pk', '!=', $row->user_pk)->update(['user_category' => 'E']);
        DB::table('user_credentials')->where('pk', $row->user_pk)
            ->update(['user_category' => null, 'email_id' => strtoupper($email)]);

        return [User::findOrFail($row->user_pk), $student, (int) $row->course];
    }

    private function anotherStudent(int $student): int
    {
        $pk = DB::table('student_master')->where('pk', '!=', $student)->orderBy('pk')->value('pk');
        if (! $pk) {
            $this->markTestSkipped('needs a second student');
        }

        return (int) $pk;
    }

    private function attendancePage(int $course, int $student): string
    {
        return route('attendance.OT.student_mark.student', [
            'group_pk' => 0, 'course_pk' => $course, 'timetable_pk' => 0, 'student_pk' => $student,
        ]);
    }

    private function attendanceFeed(int $course, int $student): string
    {
        // The feed answers 400 without a group; any group pk passes that check.
        return route('ot.student.attendance.data', ['group_pk' => 1, 'course_pk' => $course, 'student_pk' => $student]);
    }

    /** Every trainee-own endpoint, for the login's own student. */
    private function ownEndpoints(int $student, int $course): array
    {
        return [
            'attendance page' => $this->attendancePage($course, $student),
            'attendance feed' => $this->attendanceFeed($course, $student),
            'My Marks' => route('memo.discipline.ot_marks'),
            'student feedback' => route('feedback.get.studentFeedback'),
            'faculty feedback' => route('feedback.get.studentFacultyFeedback'),
        ];
    }

    public function test_the_trainees_own_no_category_login_opens_its_own_records(): void
    {
        [$login, $student, $course] = $this->noCategoryOwnLogin();

        $this->as($login, ['Student-OT'])->get($this->attendancePage($course, $student))->assertOk();
        $this->as($login, ['Student-OT'])->getJson($this->attendanceFeed($course, $student))->assertOk();
        $this->as($login, ['Student-OT'])->get(route('memo.discipline.ot_marks'))->assertOk();
        $this->as($login, ['Student-OT'])->get(route('feedback.get.studentFacultyFeedback'))->assertOk();
        // Not assertOk(): on the local MariaDB copy this page's own query fails and its
        // catch redirects (pre-existing, StudentFacultyFeedbackTraineeOnlyTest). The gate
        // under test decides 403 or not.
        $this->assertNotSame(403, $this->as($login, ['Student-OT'])
            ->get(route('feedback.get.studentFeedback'))->getStatusCode());
    }

    public function test_the_same_login_is_refused_another_trainees_attendance(): void
    {
        [$login, $student, $course] = $this->noCategoryOwnLogin();
        $other = $this->anotherStudent($student);

        $this->as($login, ['Student-OT'])->get($this->attendancePage($course, $other))->assertForbidden();
        $this->as($login, ['Student-OT'])->getJson($this->attendanceFeed($course, $other))->assertForbidden();
    }

    public function test_a_no_category_login_with_another_email_is_refused_every_trainee_endpoint(): void
    {
        [$login, $student, $course] = $this->noCategoryOwnLogin();
        DB::table('user_credentials')->where('pk', $login->pk)->update(['email_id' => 'someone.else@probe.test']);
        $login = $login->fresh();

        foreach ($this->ownEndpoints($student, $course) as $name => $url) {
            $this->assertSame(403, $this->as($login, ['Student-OT'])->get($url)->getStatusCode(),
                "a login that only shares the user_id is refused the {$name}");
        }
    }

    public function test_a_second_login_is_not_the_student_while_the_student_has_a_category_s_login(): void
    {
        [$login, $student, $course] = $this->noCategoryOwnLogin();
        $email = DB::table('student_master')->where('pk', $student)->value('email');
        DB::table('user_credentials')->where('pk', $login->pk)->update(['user_category' => 'S']);

        $other = DB::table('user_credentials')->where('pk', '!=', $login->pk)->orderBy('pk')->value('pk');
        DB::table('user_credentials')->where('pk', $other)
            ->update(['user_category' => null, 'user_id' => $student, 'email_id' => $email]);

        foreach ($this->ownEndpoints($student, $course) as $name => $url) {
            $this->assertSame(403, $this->as(User::findOrFail($other), ['Student-OT'])->get($url)->getStatusCode(),
                "a second login is refused the student's {$name}");
        }
    }

    public function test_the_rule_reads_identity_the_same_way_for_the_feed_and_the_gates(): void
    {
        [$login, $student] = $this->noCategoryOwnLogin();

        $this->actingAs($login);
        session(['user_roles' => ['Student-OT']]);
        $this->assertSame((string) $student, auth_student_master_pk(), 'the provably-own login is the student');

        // A staff category is never a student, whatever its user_id and email.
        DB::table('user_credentials')->where('pk', $login->pk)->update(['user_category' => 'E']);
        $this->actingAs($login->fresh());
        session(['user_roles' => ['Student-OT']]);
        $this->assertNull(auth_student_master_pk());
    }
}
