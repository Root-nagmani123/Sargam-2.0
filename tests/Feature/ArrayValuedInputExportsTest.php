<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR-added lines cast or concatenated query/form values with no scalar check, so
 * an array-valued parameter (?format[]=pdf) raised "Array to string conversion"
 * and the page returned 500 (PR #334 F-041, the F-025 family). Each entry point
 * must answer normally instead.
 */
class ArrayValuedInputExportsTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private function asSuperAdmin()
    {
        return $this->as($this->userWithRole('Super Admin'), ['Super Admin']);
    }

    private function assertNotServerError($response): void
    {
        $this->assertLessThan(500, $response->getStatusCode(), 'an array-valued parameter must not cause a 500');
        // And the request must have reached the export, not been turned away first.
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_my_leave_export_with_array_format_type_and_period(): void
    {
        $response = $this->as($this->officerTrainee(), ['Student-OT'])
            ->get(route('leave.my-leave.export', [
                'format' => ['pdf'],
                'leave_type' => ['X'],
                'from_date' => ['2026-01-01'],
                'to_date' => ['2026-12-31'],
            ]));

        $this->assertNotServerError($response);
    }

    public function test_leave_on_behalf_export_with_array_format_and_period(): void
    {
        $response = $this->asSuperAdmin()
            ->get(route('admin.leave-on-behalf.export', [
                'format' => ['pdf'],
                'from_date' => ['2026-01-01'],
            ]));

        $this->assertNotServerError($response);
    }

    public function test_leave_approval_export_with_array_period(): void
    {
        $response = $this->asSuperAdmin()
            ->get(route('faculty.leave-approval.export', [
                'format' => 'pdf',
                'status' => ['2'],
                'course_filter' => ['1'],
                'from_date' => ['2026-01-01'],
                'to_date' => ['2026-12-31'],
            ]));

        $this->assertNotServerError($response);
    }

    public function test_timetable_report_with_array_course_mode(): void
    {
        $response = $this->asSuperAdmin()
            ->getJson(route('timetable-report.data', ['course_mode' => ['x'], 'draw' => 1, 'start' => 0, 'length' => 10]));

        $this->assertNotServerError($response);
    }

    public function test_notice_store_rejects_an_array_target_audience_with_a_validation_error(): void
    {
        $this->asSuperAdmin()
            ->post(route('admin.notice.store'), ['target_audience' => ['All']])
            ->assertSessionHasErrors('target_audience');
    }

    public function test_notice_store_rejects_an_array_ot_scope_with_a_validation_error(): void
    {
        $course = DB::table('course_master')->value('pk');
        if (! $course) {
            $this->markTestSkipped('no course');
        }

        $this->asSuperAdmin()
            ->post(route('admin.notice.store'), [
                'target_audience' => 'Office trainee',
                'course_master_pks' => [(int) $course],
                'ot_scope' => ['group'],
            ])
            ->assertSessionHasErrors('ot_scope');
    }
}
