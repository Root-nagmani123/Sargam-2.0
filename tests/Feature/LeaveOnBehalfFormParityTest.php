<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Leave on Behalf stores the leave APPROVED, so the server must not accept what
 * the form never offers: a deactivated nature, or a course that is not running
 * (PR #334 round 5, delegated-reader candidate). All writes roll back.
 */
class LeaveOnBehalfFormParityTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private function enrolment(): object
    {
        $e = DB::table('student_master_course__map as m')
            ->join('course_master as c', 'c.pk', '=', 'm.course_master_pk')
            ->where('m.active_inactive', 1)
            ->where('c.active_inactive', 1)
            ->where(fn ($q) => $q->whereNull('c.end_date')->orWhereDate('c.end_date', '>=', now()->toDateString()))
            ->first(['m.course_master_pk', 'm.student_master_pk']);
        if (! $e) {
            $this->markTestSkipped('needs a running enrolment');
        }

        return $e;
    }

    private function submitWith(array $overrides, ?object $e = null)
    {
        $e ??= $this->enrolment();

        return $this->as($this->userWithRole('Super Admin'), ['Super Admin'])
            ->post(route('admin.leave-on-behalf.store'), array_merge([
                'course_master_pk' => $e->course_master_pk,
                'student_master_pk' => $e->student_master_pk,
                'leave_nature_master_pk' => DB::table('leave_nature_master')->where('leave_type', 'LEAVE')->where('active_inactive', 1)->value('pk'),
                'from_date' => now()->addDays(430)->toDateString(),
                'to_date' => now()->addDays(430)->toDateString(),
                'time_from' => '09:00',
                'time_to' => '18:00',
                'contact_number' => '9876543210',
                'reason' => 'parity probe',
            ], $overrides));
    }

    public function test_a_deactivated_leave_nature_is_refused(): void
    {
        $inactive = DB::table('leave_nature_master')->insertGetId([
            'leave_type' => 'LEAVE',
            'nature_name' => 'Parity probe (inactive)',
            'display_order' => 999,
            'active_inactive' => 0,
        ], 'pk');

        $before = DB::table('leave_application')->count();
        $this->submitWith(['leave_nature_master_pk' => $inactive])->assertSessionHasErrors('leave_nature_master_pk');
        $this->assertSame($before, DB::table('leave_application')->count());
    }

    public function test_a_course_that_has_ended_is_refused(): void
    {
        $e = $this->enrolment();
        DB::table('course_master')->where('pk', $e->course_master_pk)->update(['end_date' => now()->subDays(10)->toDateString()]);

        $before = DB::table('leave_application')->count();
        $this->submitWith([], $e)->assertSessionHasErrors('course_master_pk');
        $this->assertSame($before, DB::table('leave_application')->count());
    }
}
