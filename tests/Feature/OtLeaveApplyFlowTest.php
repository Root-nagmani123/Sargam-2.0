<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The Officer Trainee's own Apply Leave flow, end to end through the router.
 *
 * The merge of main into new_hotfix_mayank (7082e5204) dropped the hidden
 * from_date / to_date inputs while the controller kept requiring both, so every
 * OT submission failed validation (PR #334 review F-001). These cases pin the
 * rendered form AND a real POST, so the form and the validator cannot drift
 * apart again without a red test.
 *
 * Skips when the application database is unreachable; every write happens in a
 * transaction that is always rolled back (suite convention, see
 * DirectoryExportAccessTest).
 */
class OtLeaveApplyFlowTest extends TestCase
{
    private bool $inTransaction = false;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('leave apply tests need the application database');
        }

        DB::beginTransaction();
        $this->inTransaction = true;
    }

    protected function tearDown(): void
    {
        if ($this->inTransaction) {
            DB::rollBack();
            $this->inTransaction = false;
        }

        parent::tearDown();
    }

    /**
     * An OT login with an active course enrolment, and a stationed-leave
     * configuration for that course that needs no faculty approval and has no
     * cut-off (created inside the rolled-back transaction).
     *
     * @return array{0: User, 1: int, 2: int}  user, student pk, course pk
     */
    private function otWithStationedLeaveConfigured(): array
    {
        $row = DB::table('user_credentials as u')
            ->join('student_master_course__map as smcm', 'smcm.student_master_pk', '=', 'u.user_id')
            ->join('course_master as cm', 'cm.pk', '=', 'smcm.course_master_pk')
            ->where('u.user_category', 'S')
            ->where('smcm.active_inactive', 1)
            ->where('cm.active_inactive', 1)
            ->orderByDesc('smcm.pk')
            ->first(['u.pk as user_pk', 'u.user_id as student_pk']);

        if (! $row) {
            $this->markTestSkipped('no officer trainee with an active course enrolment');
        }

        // The course the controller will resolve for this student.
        $context = app(\App\Services\LeaveApplicationService::class)->resolveStudentContext((int) $row->user_pk);

        DB::table('stationed_leave_master')->insert([
            'course_master_pk' => $context['course_pk'],
            'effective_from' => now()->subDay()->toDateString(),
            'apply_cutoff_time' => null,
            'is_faculty_approval_required' => 0,
            'active_inactive' => 1,
            'created_date' => now(),
        ]);

        return [User::findOrFail($row->user_pk), (int) $context['student_pk'], (int) $context['course_pk']];
    }

    private function asOt(User $user)
    {
        return $this->actingAs($user)->withSession(['user_roles' => ['Student-OT']]);
    }

    public function test_the_apply_form_posts_from_date_and_to_date(): void
    {
        [$user] = $this->otWithStationedLeaveConfigured();

        foreach (['PT_EXEMPTION', 'STATIONED_LEAVE'] as $type) {
            $html = $this->asOt($user)->get('/leave/apply?leave_type=' . $type)
                ->assertOk()
                ->getContent();

            $this->assertMatchesRegularExpression('/<input[^>]*name="from_date"/', $html, "$type form must post from_date");
            $this->assertMatchesRegularExpression('/<input[^>]*name="to_date"/', $html, "$type form must post to_date");

            // Stationed leave also requires the departure and return times (PR #334 F-064).
            if ($type === 'STATIONED_LEAVE') {
                $this->assertMatchesRegularExpression('/<input[^>]*name="time_from"/', $html, 'stationed leave must post time_from');
                $this->assertMatchesRegularExpression('/<input[^>]*name="time_to"/', $html, 'stationed leave must post time_to');
            }
        }
    }

    public function test_an_officer_trainee_can_submit_stationed_leave(): void
    {
        [$user, $studentPk] = $this->otWithStationedLeaveConfigured();

        // Far enough ahead to clear any existing application of this student.
        $date = now()->addDays(200)->toDateString();
        $natureId = DB::table('leave_nature_master')
            ->where('leave_type', 'STATIONED_LEAVE')->where('active_inactive', 1)->value('pk');

        $response = $this->asOt($user)->post('/leave/store', [
            'leave_type' => 'STATIONED_LEAVE',
            'leave_nature_master_pk' => $natureId,
            'from_date' => $date,
            'to_date' => $date,
            'time_from' => '09:00',
            'time_to' => '18:00',
            'reason' => 'Family function',
            'contact_number' => '9876543210',
            'submit_action' => 'submit',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertTrue(
            DB::table('leave_application')
                ->where('student_master_pk', $studentPk)
                ->whereDate('from_date', $date)
                ->where('leave_type', 'STATIONED_LEAVE')
                ->exists(),
            'the submitted leave must be stored'
        );
    }

    public function test_a_post_without_dates_is_still_rejected(): void
    {
        [$user] = $this->otWithStationedLeaveConfigured();

        $this->asOt($user)->post('/leave/store', [
            'leave_type' => 'STATIONED_LEAVE',
            'leave_nature_master_pk' => 5,
            'time_from' => '09:00',
            'time_to' => '18:00',
            'reason' => 'x',
            'contact_number' => '9876543210',
            'submit_action' => 'submit',
        ])->assertSessionHasErrors(['from_date', 'to_date']);
    }
}
