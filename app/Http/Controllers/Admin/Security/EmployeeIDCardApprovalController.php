<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Models\SecurityParmIdApply;
use App\Models\SecurityParmIdApplyApproval;
use App\Support\IdCardSecurityMapper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Approval I, Approval II, All ID Card Requests - mapped to security_parm_id_apply + security_parm_id_apply_approval.
 */
class EmployeeIDCardApprovalController extends Controller
{
    public function approval1(Request $request)
    {
        $subApproval1 = SecurityParmIdApplyApproval::select('security_parm_id_apply_pk')
            ->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_1);
        $query = SecurityParmIdApply::with(['employee.designation', 'approvals.approver'])
            ->where('id_status', SecurityParmIdApply::ID_STATUS_PENDING)
            ->whereNotIn('emp_id_apply', $subApproval1)
            ->orderBy('created_date', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }
        if ($request->filled('card_type')) {
            $query->where('card_type', $request->card_type);
        }

        $paginator = $query->paginate(10)->withQueryString();
        $paginator->getCollection()->transform(fn ($r) => IdCardSecurityMapper::toEmployeeRequestDto($r));
        $requests = $paginator;

        return view('admin.security.employee_idcard_approval.approval1', compact('requests'));
    }

    public function approval2(Request $request)
    {
        $hasA1 = SecurityParmIdApplyApproval::select('security_parm_id_apply_pk')
            ->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_1);
        $hasA2 = SecurityParmIdApplyApproval::select('security_parm_id_apply_pk')
            ->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_2);
        $query = SecurityParmIdApply::with(['employee.designation', 'approvals.approver'])
            ->where('id_status', SecurityParmIdApply::ID_STATUS_PENDING)
            ->whereIn('emp_id_apply', $hasA1)
            ->whereNotIn('emp_id_apply', $hasA2)
            ->orderBy('pk', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }
        if ($request->filled('card_type')) {
            $query->where('card_type', $request->card_type);
        }

        $paginator = $query->paginate(10)->withQueryString();
        $paginator->getCollection()->transform(fn ($r) => IdCardSecurityMapper::toEmployeeRequestDto($r));
        $requests = $paginator;

        return view('admin.security.employee_idcard_approval.approval2', compact('requests'));
    }

    public function show($id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }
        $row = SecurityParmIdApply::with(['employee.designation', 'approvals.approver'])
            ->where('pk', $pk)->firstOrFail();
        $request = IdCardSecurityMapper::toEmployeeRequestDto($row);
        return view('admin.security.employee_idcard_approval.show', compact('request'));
    }

    public function approve1(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }
        $row = SecurityParmIdApply::where('pk', $pk)->firstOrFail();
        if ($row->id_status != SecurityParmIdApply::ID_STATUS_PENDING) {
            return redirect()->back()->with('error', 'This request is not pending your approval.');
        }
        $alreadyA1 = SecurityParmIdApplyApproval::where('security_parm_id_apply_pk', $row->emp_id_apply)->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_1)->exists();
        if ($alreadyA1) {
            return redirect()->back()->with('error', 'This request is not pending your approval.');
        }
        $user = Auth::user();
        $employeePk = $user->user_id ?? $user->pk ?? null;

        SecurityParmIdApplyApproval::create([
            'security_parm_id_apply_pk' => $row->emp_id_apply,
            'status' => SecurityParmIdApplyApproval::STATUS_APPROVAL_1,
            'approval_emp_pk' => $employeePk,
            'created_by' => $employeePk,
            'created_date' => now()->format('Y-m-d H:i:s'),
            'modified_by' => $employeePk,
            'modified_date' => now()->format('Y-m-d H:i:s'),
        ]);

        return redirect()->route('admin.security.employee_idcard_approval.approval1')
            ->with('success', 'Request approved successfully. It will now move to Approver 2.');
    }

    public function approve2(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }
        $row = SecurityParmIdApply::where('pk', $pk)->firstOrFail();
        if ($row->id_status != SecurityParmIdApply::ID_STATUS_PENDING) {
            return redirect()->back()->with('error', 'This request is not pending your approval.');
        }
        $hasA1 = SecurityParmIdApplyApproval::where('security_parm_id_apply_pk', $row->emp_id_apply)->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_1)->exists();
        $hasA2 = SecurityParmIdApplyApproval::where('security_parm_id_apply_pk', $row->emp_id_apply)->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_2)->exists();
        if (!$hasA1 || $hasA2) {
            return redirect()->back()->with('error', 'This request is not pending your approval.');
        }
        $user = Auth::user();
        $employeePk = $user->user_id ?? $user->pk ?? null;

        SecurityParmIdApplyApproval::create([
            'security_parm_id_apply_pk' => $row->emp_id_apply,
            'status' => SecurityParmIdApplyApproval::STATUS_APPROVAL_2,
            'approval_emp_pk' => $employeePk,
            'created_by' => $employeePk,
            'created_date' => now()->format('Y-m-d H:i:s'),
            'modified_by' => $employeePk,
            'modified_date' => now()->format('Y-m-d H:i:s'),
        ]);
        $row->id_status = SecurityParmIdApply::ID_STATUS_APPROVED;
        $row->save();

        return redirect()->route('admin.security.employee_idcard_approval.approval2')
            ->with('success', 'Request approved successfully. ID card is now fully approved.');
    }

    public function reject1(Request $request, $id)
    {
        return $this->reject($request, $id, 1);
    }

    public function reject2(Request $request, $id)
    {
        return $this->reject($request, $id, 2);
    }

    protected function reject(Request $request, $id, int $stage)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);
        $row = SecurityParmIdApply::where('pk', $pk)->firstOrFail();
        if ($row->id_status != SecurityParmIdApply::ID_STATUS_PENDING) {
            return redirect()->back()->with('error', 'This request is not pending your action.');
        }
        $approvals = SecurityParmIdApplyApproval::where('security_parm_id_apply_pk', $row->emp_id_apply)->get();
        $hasA1 = $approvals->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_1)->isNotEmpty();
        $hasA2 = $approvals->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_2)->isNotEmpty();
        $hasRej = $approvals->where('status', SecurityParmIdApplyApproval::STATUS_REJECTED)->isNotEmpty();
        if ($stage === 1 && ($hasA1 || $hasRej)) {
            return redirect()->back()->with('error', 'This request is not pending your action.');
        }
        if ($stage === 2 && (!$hasA1 || $hasA2 || $hasRej)) {
            return redirect()->back()->with('error', 'This request is not pending your action.');
        }
        $user = Auth::user();
        $employeePk = $user->user_id ?? $user->pk ?? null;

        SecurityParmIdApplyApproval::create([
            'security_parm_id_apply_pk' => $row->emp_id_apply,
            'status' => SecurityParmIdApplyApproval::STATUS_REJECTED,
            'approval_remarks' => $validated['rejection_reason'],
            'approval_emp_pk' => $employeePk,
            'created_by' => $employeePk,
            'created_date' => now()->format('Y-m-d H:i:s'),
            'modified_by' => $employeePk,
            'modified_date' => now()->format('Y-m-d H:i:s'),
        ]);
        $row->id_status = SecurityParmIdApply::ID_STATUS_REJECTED;
        $row->save();

        $route = $stage === 1 ? 'admin.security.employee_idcard_approval.approval1' : 'admin.security.employee_idcard_approval.approval2';
        return redirect()->route($route)->with('success', 'Request rejected.');
    }

    public function all(Request $request)
    {
        $query = SecurityParmIdApply::with(['employee.designation', 'approvals.approver'])
            ->orderBy('created_date', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }
        if ($request->filled('status')) {
            if ($request->status === 'Pending_A1') {
                $subA1 = SecurityParmIdApplyApproval::select('security_parm_id_apply_pk')->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_1);
                $query->where('id_status', SecurityParmIdApply::ID_STATUS_PENDING)->whereNotIn('emp_id_apply', $subA1);
            } elseif ($request->status === 'Pending_A2') {
                $hasA1 = SecurityParmIdApplyApproval::select('security_parm_id_apply_pk')->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_1);
                $hasA2 = SecurityParmIdApplyApproval::select('security_parm_id_apply_pk')->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_2);
                $query->where('id_status', SecurityParmIdApply::ID_STATUS_PENDING)->whereIn('emp_id_apply', $hasA1)->whereNotIn('emp_id_apply', $hasA2);
            } else {
                $query->where('id_status', match ($request->status) {
                    'Approved' => SecurityParmIdApply::ID_STATUS_APPROVED,
                    'Rejected' => SecurityParmIdApply::ID_STATUS_REJECTED,
                    default => $request->status,
                });
            }
        }

        $paginator = $query->paginate(15)->withQueryString();
        $paginator->getCollection()->transform(fn ($r) => IdCardSecurityMapper::toEmployeeRequestDto($r));
        $requests = $paginator;

        return view('admin.security.employee_idcard_approval.all', compact('requests'));
    }
}
