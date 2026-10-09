<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Leave on Behalf stores the leave as APPROVED, so two overlapping records for one
 * OT have no approver to catch them. The overlap check used to run before, and
 * outside, the insert's transaction: a double click or two tabs could both pass it
 * (PR #334 F-022). It now runs inside the transaction, after the OT's
 * student_master row is locked, so concurrent records for one OT serialise.
 *
 * A single PHPUnit connection cannot race two requests, so the second test pins
 * the ordering that makes the race impossible rather than the race itself.
 */
class LeaveOnBehalfDoubleSubmitTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /** @return array<string, mixed> a valid store payload for an enrolled OT on a configured course */
    private function payload(): array
    {
        // A running course: store() accepts only what the form offers.
        $enrolment = DB::table('student_master_course__map as m')
            ->join('course_master as c', 'c.pk', '=', 'm.course_master_pk')
            ->where('m.active_inactive', 1)
            ->where('c.active_inactive', 1)
            ->where(fn ($q) => $q->whereNull('c.end_date')->orWhereDate('c.end_date', '>=', now()->toDateString()))
            ->orderBy('m.pk')
            ->first(['m.course_master_pk', 'm.student_master_pk']);
        if (! $enrolment) {
            // No running course on the copy (F-024 closure gap): make one running
            // inside the rolled-back transaction, as Pr334Round4LowFindingsTest does.
            $enrolment = DB::table('student_master_course__map as m')
                ->join('student_master as s', 's.pk', '=', 'm.student_master_pk')
                ->where('m.active_inactive', 1)
                ->orderBy('m.pk')
                ->first(['m.course_master_pk', 'm.student_master_pk']);
            if ($enrolment) {
                DB::table('course_master')->where('pk', $enrolment->course_master_pk)
                    ->update(['active_inactive' => 1, 'end_date' => now()->addYears(3)->toDateString()]);
            }
        }
        $nature = DB::table('leave_nature_master')->where('leave_type', 'LEAVE')->where('active_inactive', 1)->value('pk');

        if (! $enrolment || ! $nature) {
            $this->markTestSkipped('need an active enrolment and a LEAVE nature');
        }

        // Rolled back with the test: the course must have stationed leave configured.
        DB::table('stationed_leave_master')->insert([
            'course_master_pk' => $enrolment->course_master_pk,
            'effective_from' => '2000-01-01',
            'is_faculty_approval_required' => 0,
            'active_inactive' => 1,
        ]);

        $day = now()->addDays(400)->toDateString();

        return [
            'course_master_pk' => $enrolment->course_master_pk,
            'student_master_pk' => $enrolment->student_master_pk,
            'leave_nature_master_pk' => $nature,
            'from_date' => $day,
            'to_date' => $day,
            'time_from' => '09:00',
            'time_to' => '18:00',
            'contact_number' => '9876543210',
            'reason' => 'double submit probe',
        ];
    }

    private function submit(array $payload)
    {
        return $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])
            ->post('/admin/leave-on-behalf/store', $payload);
    }

    public function test_a_repeated_submit_records_one_approved_leave(): void
    {
        $payload = $this->payload();

        $this->submit($payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->submit($payload)->assertSessionHasErrors('from_date');

        $this->assertSame(1, DB::table('leave_application')
            ->where('student_master_pk', $payload['student_master_pk'])
            ->whereDate('from_date', $payload['from_date'])
            ->count());
    }

    public function test_the_overlap_check_runs_inside_the_transaction_after_the_student_lock(): void
    {
        $payload = $this->payload();
        $baseline = DB::transactionLevel();

        $queries = [];
        DB::listen(function ($q) use (&$queries) {
            $queries[] = ['sql' => strtolower($q->sql), 'level' => DB::transactionLevel()];
        });

        $this->submit($payload)->assertSessionHasNoErrors()->assertRedirect();

        $lock = null;
        $overlap = null;
        foreach ($queries as $i => $q) {
            if ($lock === null && str_contains($q['sql'], 'from `student_master`') && str_contains($q['sql'], 'for update')) {
                $lock = $i;
            }
            if ($overlap === null && str_contains($q['sql'], 'from `leave_application`') && str_contains($q['sql'], '`status` in')) {
                $overlap = $i;
            }
        }

        $this->assertNotNull($lock, 'the OT\'s student_master row must be locked FOR UPDATE');
        $this->assertNotNull($overlap, 'the overlap check must run');
        $this->assertGreaterThan($baseline, $queries[$lock]['level'], 'the lock must be taken inside the store transaction');
        $this->assertGreaterThan($lock, $overlap, 'the overlap check must run after the lock');
        $this->assertGreaterThan($baseline, $queries[$overlap]['level'], 'the overlap check must run inside the store transaction');
    }
}
