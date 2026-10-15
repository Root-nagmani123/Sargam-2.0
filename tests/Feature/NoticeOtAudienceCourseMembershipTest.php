<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #334 F-087: changing the course of a Personal or Group OT notice kept the old
 * trainee or group selected, and the server stored it under a course it does not
 * belong to. store() and update() now refuse a trainee or group outside the
 * selected courses; a deactivated one that still belongs keeps saving (F-044).
 */
class NoticeOtAudienceCourseMembershipTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /** [course A, a trainee of A, a group of A, course B that has neither] */
    private function twoCourses(): array
    {
        $row = DB::table('student_master_course__map as c')
            ->join('group_type_master_course_master_map as g', 'g.course_name', '=', 'c.course_master_pk')
            ->orderBy('c.course_master_pk')
            ->first(['c.course_master_pk as course', 'c.student_master_pk as student', 'g.pk as grp']);
        if (! $row) {
            $this->markTestSkipped('needs a course with a trainee and a group');
        }

        $other = DB::table('course_master')
            ->where('pk', '!=', $row->course)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('student_master_course__map as m')
                ->whereColumn('m.course_master_pk', 'course_master.pk')->where('m.student_master_pk', $row->student))
            ->orderBy('pk')
            ->value('pk');
        if (! $other) {
            $this->markTestSkipped('needs a second course the trainee is not in');
        }

        return [(int) $row->course, (int) $row->student, (int) $row->grp, (int) $other];
    }

    private function admin()
    {
        return $this->as($this->staffWithRole('Super Admin'), ['Super Admin']);
    }

    private function payload(string $title, array $audience): array
    {
        return [
            'notice_title' => $title,
            'description' => 'F-087 probe',
            'notice_type' => 'Personal',
            'display_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(10)->toDateString(),
            'target_audience' => 'Office trainee',
        ] + $audience;
    }

    private function store(array $audience)
    {
        $title = 'F-087 probe '.random_int(100000, 999999);
        $response = $this->admin()->post(route('admin.notice.store'), $this->payload($title, $audience));

        return [$response, (int) DB::table('notices_notification')->where('notice_title', $title)->value('pk')];
    }

    private function update(int $pk, string $title, array $audience)
    {
        return $this->admin()->put(route('admin.notice.update', Crypt::encrypt($pk)), $this->payload($title, $audience));
    }

    private function audience(int $pk): array
    {
        return DB::table('notice_audience_map')->where('notices_notification_pk', $pk)
            ->orderBy('audience_type')->orderBy('reference_pk')->get()
            ->map(fn ($r) => $r->audience_type.':'.$r->reference_pk)->all();
    }

    public function test_changing_the_course_of_a_personal_notice_with_the_old_trainee_is_refused(): void
    {
        [$a, $student, , $b] = $this->twoCourses();
        [, $pk] = $this->store(['course_master_pks' => [(string) $a], 'ot_scope' => 'individual', 'student_pks' => [(string) $student]]);
        $stored = $this->audience($pk);
        $this->assertSame(["C:{$a}", "S:{$student}"], $stored, 'control: stored under its own course');

        $this->update($pk, 'F-087 moved', ['course_master_pks' => [(string) $b], 'ot_scope' => 'individual', 'student_pks' => [(string) $student]])
            ->assertSessionHasErrors(['student_pks' => 'One of the selected Officer Trainees does not belong to the selected courses.']);

        $this->assertSame($stored, $this->audience($pk), 'audience unchanged');
        $this->assertNotSame('F-087 moved', DB::table('notices_notification')->where('pk', $pk)->value('notice_title'));
    }

    public function test_changing_the_course_of_a_group_notice_with_the_old_group_is_refused(): void
    {
        [$a, , $group, $b] = $this->twoCourses();
        [, $pk] = $this->store(['course_master_pks' => [(string) $a], 'ot_scope' => 'group', 'group_type_map_pks' => [(string) $group]]);
        $stored = $this->audience($pk);
        $this->assertSame(["C:{$a}", "G:{$group}"], $stored, 'control: stored under its own course');

        $this->update($pk, 'F-087 moved', ['course_master_pks' => [(string) $b], 'ot_scope' => 'group', 'group_type_map_pks' => [(string) $group]])
            ->assertSessionHasErrors(['group_type_map_pks' => 'One of the selected groups does not belong to the selected courses.']);

        $this->assertSame($stored, $this->audience($pk), 'audience unchanged');
    }

    public function test_creating_a_notice_with_a_trainee_or_group_of_another_course_stores_nothing(): void
    {
        [, $student, $group, $b] = $this->twoCourses();

        foreach ([
            'student_pks' => ['ot_scope' => 'individual', 'student_pks' => [(string) $student]],
            'group_type_map_pks' => ['ot_scope' => 'group', 'group_type_map_pks' => [(string) $group]],
        ] as $field => $audience) {
            [$response, $pk] = $this->store(['course_master_pks' => [(string) $b]] + $audience);
            $response->assertSessionHasErrors($field);
            $this->assertSame(0, $pk, "no notice stored for {$field}");
        }
    }

    public function test_adding_a_second_course_keeps_the_trainee_of_the_first(): void
    {
        [$a, $student, , $b] = $this->twoCourses();
        [, $pk] = $this->store(['course_master_pks' => [(string) $a], 'ot_scope' => 'individual', 'student_pks' => [(string) $student]]);

        $this->update($pk, 'F-087 two courses', ['course_master_pks' => [(string) $a, (string) $b], 'ot_scope' => 'individual', 'student_pks' => [(string) $student]])
            ->assertSessionHasNoErrors();

        $expected = ["C:{$a}", "C:{$b}", "S:{$student}"];
        sort($expected);
        $actual = $this->audience($pk);
        sort($actual);
        $this->assertSame($expected, $actual);
    }

    public function test_a_deactivated_trainee_still_in_the_course_survives_a_title_only_edit(): void
    {
        [$a, $student] = $this->twoCourses();
        [, $pk] = $this->store(['course_master_pks' => [(string) $a], 'ot_scope' => 'individual', 'student_pks' => [(string) $student]]);
        DB::table('student_master')->where('pk', $student)->update(['status' => 0]);

        $this->update($pk, 'F-087 title only', ['course_master_pks' => [(string) $a], 'ot_scope' => 'individual', 'student_pks' => [(string) $student]])
            ->assertSessionHasNoErrors();

        $this->assertSame(["C:{$a}", "S:{$student}"], $this->audience($pk));
    }
}
