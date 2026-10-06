<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Leave on Behalf records leave as APPROVED, so who may record it for which
 * course is the control. Super Admin is unrestricted; every other
 * Training-section role is confined to the courses its role owns
 * (course_master.user_role_master_pk). Before the fix the scope could never
 * apply (PR #334 F-006; policy decision 2026-10-06: own courses only).
 */
class LeaveOnBehalfCourseScopeTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const ROLE = 'Training-Induction';

    /** @return array{0: User, 1: int, 2: int}  operator, an owned course, a course their roles do not own */
    private function operatorAndCourses(): array
    {
        $roleId = DB::table('roles')->where('name', self::ROLE)->value('id');
        $operator = $this->userWithRole(self::ROLE);

        $theirRoleIds = DB::table('model_has_roles')->where('model_id', $operator->pk)
            ->where('model_type', User::class)->pluck('role_id');

        if ($theirRoleIds->contains(fn ($id) => in_array((int) $id, [1], true))) {
            $this->markTestSkipped('the first Training-Induction user is also Super Admin');
        }

        $owned = DB::table('course_master')->where('user_role_master_pk', $roleId)->value('pk');
        $foreign = DB::table('course_master')
            ->whereNotIn('user_role_master_pk', $theirRoleIds->all())
            ->orWhereNull('user_role_master_pk')
            ->value('pk');

        if (! $owned || ! $foreign) {
            $this->markTestSkipped('need one course the role owns and one it does not');
        }

        return [$operator, (int) $owned, (int) $foreign];
    }

    private function studentOf(int $coursePk): int
    {
        return (int) (DB::table('student_master_course__map')->where('course_master_pk', $coursePk)->value('student_master_pk') ?? 1);
    }

    public function test_an_operator_is_refused_a_course_their_role_does_not_own(): void
    {
        [$operator, , $foreign] = $this->operatorAndCourses();

        $this->as($operator, [self::ROLE])
            ->getJson('/admin/leave-on-behalf/context?' . http_build_query(['course_master_pk' => $foreign, 'student_master_pk' => $this->studentOf($foreign)]))
            ->assertForbidden();

        $before = DB::table('leave_application')->count();

        $this->as($operator, [self::ROLE])->post('/admin/leave-on-behalf/store', [
            'course_master_pk' => $foreign,
            'student_master_pk' => $this->studentOf($foreign),
            'leave_nature_master_pk' => DB::table('leave_nature_master')->where('leave_type', 'LEAVE')->value('pk'),
            'from_date' => now()->addDays(300)->toDateString(),
            'to_date' => now()->addDays(300)->toDateString(),
            'time_from' => '09:00',
            'time_to' => '18:00',
            'contact_number' => '9876543210',
            'reason' => 'scope probe',
        ])->assertForbidden();

        $this->assertSame($before, DB::table('leave_application')->count(), 'no leave may be recorded out of scope');
    }

    public function test_an_operator_may_use_a_course_their_role_owns(): void
    {
        [$operator, $owned] = $this->operatorAndCourses();

        $status = $this->as($operator, [self::ROLE])
            ->getJson('/admin/leave-on-behalf/context?' . http_build_query(['course_master_pk' => $owned, 'student_master_pk' => $this->studentOf($owned)]))
            ->getStatusCode();

        $this->assertNotSame(403, $status, 'an owned course must not be refused');
    }

    public function test_a_super_admin_is_not_restricted(): void
    {
        [, , $foreign] = $this->operatorAndCourses();

        $status = $this->as($this->userWithRole('Super Admin'), ['Super Admin'])
            ->getJson('/admin/leave-on-behalf/context?' . http_build_query(['course_master_pk' => $foreign, 'student_master_pk' => $this->studentOf($foreign)]))
            ->getStatusCode();

        $this->assertNotSame(403, $status);
    }
}
