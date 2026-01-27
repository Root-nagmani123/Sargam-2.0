<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\KitchenIssueMaster;
use App\Models\KitchenIssueApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class KitchenIssueApprovalController extends Controller
{
    /**
     * Display pending approvals
     */
    public function index(Request $request)
    {
        $query = KitchenIssueMaster::with(['storeMaster', 'itemMaster', 'employee', 'student', 'approvals'])
                    ->where('send_for_approval', 1)
                    ->where('approve_status', KitchenIssueMaster::APPROVE_PENDING);

        if ($request->filled('store_id')) {
            $query->where('inve_store_master_pk', $request->store_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('request_date', [$request->start_date, $request->end_date]);
        }

        $pendingApprovals = $query->orderBy('request_date', 'desc')->paginate(20);

        return view('admin.mess.kitchen_issues.approvals.index', compact('pendingApprovals'));
    }

    /**
     * Show approval form
     */
    public function show($id)
    {
        $kitchenIssue = KitchenIssueMaster::with([
            'storeMaster',
            'itemMaster',
            'items',
            'employee',
            'student',
            'approvals.approver'
        ])->findOrFail($id);

        if ($kitchenIssue->approve_status != KitchenIssueMaster::APPROVE_PENDING) {
            return redirect()->route('admin.mess.kitchen-issue-approvals.index')
                           ->with('error', 'This kitchen issue has already been processed');
        }

        return view('admin.mess.kitchen_issues.approvals.show', compact('kitchenIssue'));
    }

    /**
     * Approve kitchen issue
     */
    public function approve(Request $request, $id)
    {
        $validated = $request->validate([
            'remarks' => 'nullable|string|max:500',
        ]);

        $kitchenIssue = KitchenIssueMaster::findOrFail($id);

        if ($kitchenIssue->approve_status != KitchenIssueMaster::APPROVE_PENDING) {
            return back()->with('error', 'This kitchen issue has already been processed');
        }

        try {
            DB::beginTransaction();

            // Update kitchen issue status
            $kitchenIssue->update([
                'approve_status' => KitchenIssueMaster::APPROVE_APPROVED,
                'status' => KitchenIssueMaster::STATUS_APPROVED,
                'notify_status' => 1,
                'modified_by' => Auth::id(),
            ]);

            // Create approval record
            KitchenIssueApproval::create([
                'kitchen_issue_master_pk' => $kitchenIssue->pk,
                'approver_id' => Auth::id(),
                'approval_level' => 1,
                'status' => KitchenIssueApproval::STATUS_APPROVED,
                'remarks' => $request->remarks,
                'approved_date' => now(),
            ]);

            DB::commit();

            return redirect()->route('admin.mess.kitchen-issue-approvals.index')
                           ->with('success', 'Kitchen Issue approved successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to approve Kitchen Issue: ' . $e->getMessage());
        }
    }

    /**
     * Reject kitchen issue
     */
    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'remarks' => 'required|string|max:500',
        ]);

        $kitchenIssue = KitchenIssueMaster::findOrFail($id);

        if ($kitchenIssue->approve_status != KitchenIssueMaster::APPROVE_PENDING) {
            return back()->with('error', 'This kitchen issue has already been processed');
        }

        try {
            DB::beginTransaction();

            // Update kitchen issue status
            $kitchenIssue->update([
                'approve_status' => KitchenIssueMaster::APPROVE_REJECTED,
                'status' => KitchenIssueMaster::STATUS_REJECTED,
                'notify_status' => 1,
                'modified_by' => Auth::id(),
            ]);

            // Create approval record
            KitchenIssueApproval::create([
                'kitchen_issue_master_pk' => $kitchenIssue->pk,
                'approver_id' => Auth::id(),
                'approval_level' => 1,
                'status' => KitchenIssueApproval::STATUS_REJECTED,
                'remarks' => $request->remarks,
                'approved_date' => now(),
            ]);

            DB::commit();

            return redirect()->route('admin.mess.kitchen-issue-approvals.index')
                           ->with('success', 'Kitchen Issue rejected successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to reject Kitchen Issue: ' . $e->getMessage());
        }
    }
}
