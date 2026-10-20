<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\FacultyLeaveApprovalController;
use App\Models\FacultyMaster;
use App\Models\LeaveApplication;
use App\Models\User;
use App\Services\FacultyLeaveApprovalService;
use PHPUnit\Framework\TestCase;

class LeaveApprovalPdfHeaderTest extends TestCase
{
    private function leave(?int $faculty, ?int $operator, int $status = LeaveApplication::STATUS_APPROVED, string $name = 'Shared Name'): LeaveApplication
    {
        $leave = new LeaveApplication;
        $leave->forceFill([
            'approved_by_faculty_pk' => $faculty,
            'applied_by_user_pk' => $operator,
            'status' => $status,
        ]);
        $leave->setRelation('approvedByFaculty', $faculty === null ? null : new FacultyMaster(['full_name' => $name]));
        $leave->setRelation('appliedByUser', $operator === null ? null : (new User)->forceFill(['first_name' => $name]));

        return $leave;
    }

    private function header(array $rows): string
    {
        $controller = new FacultyLeaveApprovalController(new FacultyLeaveApprovalService);
        $method = new \ReflectionMethod($controller, 'buildApproverHeaderLine');
        $method->setAccessible(true);

        return $method->invoke($controller, collect($rows));
    }

    public function test_faculty_and_on_behalf_operator_are_multiple_approvers_even_with_the_same_pk_and_name(): void
    {
        $this->assertSame('Approved/Rejected By: Multiple Approvers', $this->header([
            $this->leave(7, null),
            $this->leave(null, 7),
        ]));
    }

    public function test_multiple_operators_and_faculty_are_distinguished_by_identity_not_name(): void
    {
        $this->assertSame('Approved/Rejected By: Multiple Approvers', $this->header([
            $this->leave(null, 7), $this->leave(null, 8),
        ]));
        $this->assertSame('Approved/Rejected By: Multiple Approvers', $this->header([
            $this->leave(7, null), $this->leave(8, null),
        ]));
    }

    public function test_a_single_actor_keeps_the_row_display_name_and_pending_rows_are_ignored(): void
    {
        $this->assertSame('Approved/Rejected By: Shared Name', $this->header([
            $this->leave(7, null), $this->leave(7, null, LeaveApplication::STATUS_REJECTED),
            $this->leave(null, 8, LeaveApplication::STATUS_PENDING),
        ]));
        $this->assertSame('Approved/Rejected By: Shared Name (Training Section)', $this->header([
            $this->leave(null, 7), $this->leave(null, 7),
        ]));
    }

    public function test_unknown_action_sources_are_not_attributed_to_a_known_faculty(): void
    {
        $this->assertSame('Approved/Rejected By: Multiple Approvers', $this->header([
            $this->leave(7, null), $this->leave(null, null),
        ]));
        $this->assertSame('', $this->header([$this->leave(null, null)]));
        $this->assertSame('', $this->header([$this->leave(null, null, LeaveApplication::STATUS_PENDING)]));
        $this->assertSame('', $this->header([]));
    }
}
