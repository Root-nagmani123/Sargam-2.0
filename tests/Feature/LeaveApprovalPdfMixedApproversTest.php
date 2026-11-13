<?php

namespace Tests\Feature;

use App\Models\LeaveApplication;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

class LeaveApprovalPdfMixedApproversTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    public function test_pdf_header_does_not_attribute_an_on_behalf_leave_to_the_faculty(): void
    {
        $map = DB::table('student_master_course__map')->first();
        $faculty = DB::table('faculty_master')->whereNotNull('full_name')->where('full_name', '!=', '')->first();
        if (! $map || ! $faculty) {
            $this->markTestSkipped('needs an enrolled trainee and named faculty');
        }
        $operator = $this->staffWithRole('Super Admin');
        $date = '2097-10-13';
        $common = [
            'course_master_pk' => $map->course_master_pk,
            'student_master_pk' => $map->student_master_pk,
            'leave_type' => LeaveApplication::TYPE_STATIONED_LEAVE,
            'from_date' => $date,
            'to_date' => $date,
            'total_days' => 1,
            'status' => LeaveApplication::STATUS_APPROVED,
            'approved_at' => now(),
        ];
        $facultyPk = DB::table('leave_application')->insertGetId($common + [
            'approved_by_faculty_pk' => $faculty->pk,
            'reason' => 'F-089 faculty',
        ], 'pk');
        $onBehalfPk = DB::table('leave_application')->insertGetId($common + [
            'applied_by_user_pk' => $operator->pk,
            'reason' => 'F-089 on behalf',
        ], 'pk');

        $pdf = app('dompdf.wrapper');
        Pdf::shouldReceive('loadView')->once()->andReturnUsing(function ($view, $data) use ($pdf, $facultyPk, $onBehalfPk, $faculty) {
            $this->assertSame('admin.leave.faculty_approval.export_pdf', $view);
            $this->assertSame('Approved/Rejected By: Multiple Approvers', $data['approverLine']);
            $rows = $data['rows']->keyBy('pk');
            $this->assertTrue($rows->has($facultyPk));
            $this->assertTrue($rows->has($onBehalfPk));
            $this->assertSame($faculty->full_name, $rows[$facultyPk]->action_by_faculty_name);
            $this->assertStringContainsString('(Training Section)', $rows[$onBehalfPk]->action_by_faculty_name);
            $html = view($view, $data)->render();
            $this->assertStringContainsString('<div class="approver-line">Approved/Rejected By: Multiple Approvers</div>', $html);
            $this->assertStringNotContainsString('<div class="approver-line">Approved/Rejected By: '.$faculty->full_name.'</div>', $html);

            return $pdf->loadView($view, $data);
        });

        $response = $this->as($operator, ['Super Admin'])->get(route('faculty.leave-approval.export', [
            'format' => 'pdf',
            'status' => LeaveApplication::STATUS_APPROVED,
            'course_filter' => $map->course_master_pk,
            'from_date' => $date,
            'to_date' => $date,
        ]));
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }
}
