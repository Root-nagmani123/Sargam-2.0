<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\LeaveOnBehalfController;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #334 F-085: Leave on Behalf accepted any end date. A mistyped year (2036) stored
 * thousands of approved leave days, and a span past 9999 days overflowed
 * leave_application.total_days decimal(5,1) with a 500. store() now refuses an end
 * date after the course end date and a leave longer than MAX_LEAVE_DAYS, before
 * anything is written.
 */
class LeaveOnBehalfDateLimitTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /** A valid payload for an enrolled OT on a running, configured course whose end date is $endDate. */
    private function payload(?string $endDate): array
    {
        $enrolment = DB::table('student_master_course__map')->where('active_inactive', 1)
            ->orderBy('pk')->first(['course_master_pk', 'student_master_pk']);
        $nature = DB::table('leave_nature_master')->where('leave_type', 'LEAVE')->where('active_inactive', 1)->value('pk');
        if (! $enrolment || ! $nature) {
            $this->markTestSkipped('need an active enrolment and a LEAVE nature');
        }

        // Rolled back with the test.
        DB::table('course_master')->where('pk', $enrolment->course_master_pk)
            ->update(['active_inactive' => 1, 'end_date' => $endDate]);
        DB::table('stationed_leave_master')->insert([
            'course_master_pk' => $enrolment->course_master_pk,
            'effective_from' => '2000-01-01',
            'is_faculty_approval_required' => 0,
            'active_inactive' => 1,
        ]);

        $from = now()->addDays(5)->toDateString();

        return [
            'course_master_pk' => $enrolment->course_master_pk,
            'student_master_pk' => $enrolment->student_master_pk,
            'leave_nature_master_pk' => $nature,
            'from_date' => $from,
            'to_date' => $from,
            'time_from' => '09:00',
            'time_to' => '18:00',
            'contact_number' => '9876543210',
            'reason' => 'F-085 probe',
        ];
    }

    private function submit(array $payload)
    {
        return $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])
            ->post('/admin/leave-on-behalf/store', $payload);
    }

    private function stored(array $payload): int
    {
        return DB::table('leave_application')
            ->where('student_master_pk', $payload['student_master_pk'])
            ->whereDate('from_date', $payload['from_date'])
            ->count();
    }

    public function test_an_end_date_after_the_course_end_is_refused_and_nothing_is_stored(): void
    {
        $courseEnd = now()->addDays(30);
        $payload = $this->payload($courseEnd->toDateString());

        foreach ([$courseEnd->copy()->addDay()->toDateString(), '2036-'.$courseEnd->format('m-d')] as $to) {
            $this->submit(['to_date' => $to] + $payload)
                ->assertStatus(302)
                ->assertSessionHasErrors(['to_date' => 'End date cannot be after the course end date ('.$courseEnd->format('d-m-Y').').']);
            $this->assertSame(0, $this->stored($payload), "nothing stored for {$to}");
        }

        // Control: ending on the course end date is accepted.
        $this->submit(['to_date' => $courseEnd->toDateString()] + $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(1, $this->stored($payload));
    }

    public function test_a_leave_longer_than_the_maximum_is_refused_on_a_course_with_no_end_date(): void
    {
        $payload = $this->payload(null);
        $max = LeaveOnBehalfController::MAX_LEAVE_DAYS;
        $lastDay = fn (int $days) => now()->addDays(5 + $days - 1)->toDateString();

        // One day over the cap, and far enough to overflow total_days decimal(5,1).
        foreach ([$lastDay($max + 1), '9999-12-31'] as $to) {
            $this->submit(['to_date' => $to] + $payload)
                ->assertStatus(302)
                ->assertSessionHasErrors(['to_date' => "A single leave cannot be longer than {$max} days."]);
            $this->assertSame(0, $this->stored($payload), "nothing stored for {$to}");
        }

        // Control: exactly the cap is accepted.
        $this->submit(['to_date' => $lastDay($max)] + $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame((float) $max, (float) DB::table('leave_application')
            ->where('student_master_pk', $payload['student_master_pk'])
            ->whereDate('from_date', $payload['from_date'])->value('total_days'));
    }
}
