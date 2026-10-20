<?php

namespace Tests\Feature;

use App\Models\LeaveApplication;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * The Leave Approval Excel export lost the "Approved/Rejected By" column when the
 * exports moved to LbsnaaTableExport; the PDF kept it (PR #334 F-042).
 */
class LeaveApprovalExcelApproverColumnTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    public function test_the_approved_tab_excel_names_who_actioned_each_leave(): void
    {
        // A synthetic approved leave inside the test transaction (rolled back).
        $map = DB::table('student_master_course__map as m')
            ->join('student_master as s', 's.pk', '=', 'm.student_master_pk')
            ->whereNotNull('s.generated_OT_code')
            ->select('m.course_master_pk', 'm.student_master_pk')
            ->first();
        $facultyPk = DB::table('faculty_master')->whereNotNull('full_name')->where('full_name', '!=', '')->value('pk');
        if (! $map || ! $facultyPk) {
            $this->markTestSkipped('needs an enrolled OT and a named faculty');
        }
        $pk = DB::table('leave_application')->insertGetId([
            'course_master_pk' => $map->course_master_pk,
            'student_master_pk' => $map->student_master_pk,
            'leave_type' => LeaveApplication::TYPE_STATIONED_LEAVE,
            'from_date' => '2026-01-05',
            'to_date' => '2026-01-05',
            'total_days' => 1,
            'reason' => 'F-042 probe',
            'status' => LeaveApplication::STATUS_APPROVED,
            'approved_by_faculty_pk' => $facultyPk,
            'approved_at' => now(),
        ], 'pk');
        $leave = LeaveApplication::with(['approvedByFaculty', 'appliedByUser', 'student'])->findOrFail($pk);
        $this->assertNotSame('-', $leave->action_by_faculty_name);

        $response = $this->as($this->userWithRole('Super Admin'), ['Super Admin'])
            ->get(route('faculty.leave-approval.export', [
                'format' => 'excel',
                'status' => LeaveApplication::STATUS_APPROVED,
            ]));
        $response->assertOk();

        $sheet = IOFactory::load($response->baseResponse->getFile()->getPathname())->getActiveSheet();
        $rows = $sheet->toArray(null, false, false, false);

        $headerIndex = null;
        foreach ($rows as $i => $row) {
            if (in_array('OT Code', $row, true)) {
                $headerIndex = $i;
                break;
            }
        }
        $this->assertNotNull($headerIndex, 'the table heading row is present');
        $header = array_values(array_filter($rows[$headerIndex], fn ($v) => $v !== null && $v !== ''));
        $this->assertSame('Approved/Rejected By', end($header), 'the last column is Approved/Rejected By');

        $col = array_search('Approved/Rejected By', $rows[$headerIndex], true);
        $otCol = array_search('OT Code', $rows[$headerIndex], true);
        $found = false;
        foreach (array_slice($rows, $headerIndex + 1) as $row) {
            if (($row[$otCol] ?? null) === ($leave->student->generated_OT_code ?? '-')
                && ($row[$col] ?? null) === $leave->action_by_faculty_name) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'the approved leave\'s row carries the same approver the PDF prints');
    }
}
