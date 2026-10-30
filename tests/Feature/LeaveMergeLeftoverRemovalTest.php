<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\FacultyLeaveApprovalController;
use App\Http\Controllers\Admin\LeaveApplicationController;
use Illuminate\Http\Request;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Merge leftovers removed (PR #334 F-016): unreachable old export code after the
 * return in the two PDF filter-line builders, and an 831-line Nature Leave Master
 * view nothing rendered. These pin that what IS used still works.
 */
class LeaveMergeLeftoverRemovalTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private function filterLine(string $class, string $method, array $query): string
    {
        $m = new \ReflectionMethod($class, $method);
        $m->setAccessible(true);

        return $m->invoke(app($class), Request::create('/', 'GET', $query));
    }

    public function test_the_approval_pdf_filter_line_is_unchanged(): void
    {
        $this->assertSame(
            'Status: Approved | Period: 2026-01-01 to 2026-01-31',
            $this->filterLine(FacultyLeaveApprovalController::class, 'exportFilterLine', ['status' => '2', 'from_date' => '2026-01-01', 'to_date' => '2026-01-31'])
        );
    }

    public function test_the_my_leave_pdf_filter_line_is_unchanged(): void
    {
        $this->assertSame(
            'Period: 2026-01-01 to …',
            $this->filterLine(LeaveApplicationController::class, 'myLeaveFilterLine', ['from_date' => '2026-01-01'])
        );
    }

    public function test_the_unused_view_is_gone_and_the_live_one_renders(): void
    {
        $this->assertFalse(view()->exists('admin.master.leave_nature_master.index'));

        $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])
            ->get(route('master.leave-nature.index'))
            ->assertOk();
    }
}
