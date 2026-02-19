<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Models\SecurityParmIdApply;
use App\Models\SecurityParmIdApplyApproval;
use App\Support\IdCardSecurityMapper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Approval I: Only contractual (security_con_oth_id_apply) where department_approval_emp_pk = current user.
 * Approval II / All Requests: security_parm_id_apply + security_con_oth_id_apply as per flow.
 */
class EmployeeIDCardApprovalController extends Controller
{
    /**
     * Approval I: Only contractual requests where current user is the Approval Authority.
     * Matches: SELECT * FROM security_con_oth_id_apply WHERE department_approval_emp_pk = {current user's employee pk}.
     * Permanent requests are not shown here (they use Approval II / All Requests flow).
     */
    public function approval1(Request $request)
    {
        $user = Auth::user();
        $currentEmployeePk = $user->user_id ?? $user->pk ?? null;

        $contA1Done = DB::table('security_con_oth_id_apply_approval')
            ->where('status', 1)
            ->pluck('security_parm_id_apply_pk');
        $contQuery = DB::table('security_con_oth_id_apply')
            ->where('id_status', 1)
            ->whereNotIn('emp_id_apply', $contA1Done)
            ->where('department_approval_emp_pk', $currentEmployeePk)
            ->orderByDesc('created_date');

        if ($request->filled('search')) {
            $search = $request->search;
            $contQuery->where(function ($q) use ($search) {
                $q->where('employee_name', 'like', '%' . $search . '%')
                    ->orWhere('id_card_no', 'like', '%' . $search . '%');
            });
        }
        if ($request->filled('card_type')) {
            $contQuery->where('permanent_type', $request->card_type);
        }

        $contRows = $contQuery->get();
        $contDtos = $contRows->map(fn ($r) => IdCardSecurityMapper::toContractualRequestDto($r));

        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        $page = (int) $request->get('page', 1);
        $requests = new LengthAwarePaginator(
            $contDtos->forPage($page, $perPage),
            $contDtos->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $cardTypes = DB::table('sec_id_cardno_master')->orderBy('sec_card_name')->pluck('sec_card_name', 'pk')->toArray();

        return view('admin.security.employee_idcard_approval.approval1', compact('requests', 'cardTypes'));
    }

    public function approval2(Request $request)
    {
        $hasA1 = SecurityParmIdApplyApproval::select('security_parm_id_apply_pk')
            ->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_1);
        $hasA2 = SecurityParmIdApplyApproval::select('security_parm_id_apply_pk')
            ->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_2);
        $permQuery = SecurityParmIdApply::with(['employee.designation', 'employee.department', 'creator.department', 'approvals.approver'])
            ->where('id_status', SecurityParmIdApply::ID_STATUS_PENDING)
            ->whereIn('emp_id_apply', $hasA1)
            ->whereNotIn('emp_id_apply', $hasA2)
            ->orderBy('created_date', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $permQuery->where(function ($q) use ($search) {
                $q->whereHas('employee', function ($eq) use ($search) {
                    $eq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                    ->orWhere('id_card_no', 'like', "%{$search}%");
            });
        }
        if ($request->filled('card_type')) {
            $permQuery->where('permanent_type', $request->card_type);
        }

        $permRows = $permQuery->get();
        $permDtos = $permRows->map(fn ($r) => IdCardSecurityMapper::toEmployeeRequestDto($r));

        // Contractual: pending, has A1, no A2 (show to all for Approval 2)
        $contHasA1 = DB::table('security_con_oth_id_apply_approval')->where('status', 1)->pluck('security_parm_id_apply_pk');
        $contHasA2 = DB::table('security_con_oth_id_apply_approval')->where('status', 2)->pluck('security_parm_id_apply_pk');
        $contQuery = DB::table('security_con_oth_id_apply')
            ->where('id_status', 1)
            ->whereIn('emp_id_apply', $contHasA1)
            ->whereNotIn('emp_id_apply', $contHasA2)
            ->orderByDesc('created_date');

        if ($request->filled('search')) {
            $search = $request->search;
            $contQuery->where(function ($q) use ($search) {
                $q->where('employee_name', 'like', '%' . $search . '%')
                    ->orWhere('id_card_no', 'like', '%' . $search . '%');
            });
        }
        if ($request->filled('card_type')) {
            $contQuery->where('permanent_type', $request->card_type);
        }

        $contRows = $contQuery->get();
        $contDtos = $contRows->map(fn ($r) => IdCardSecurityMapper::toContractualRequestDto($r));

        $merged = $permDtos->concat($contDtos)->sortByDesc(function ($d) {
            return $d->created_at ? (\Carbon\Carbon::parse($d->created_at)->timestamp ?? 0) : 0;
        })->values();

        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        $page = (int) $request->get('page', 1);
        $requests = new LengthAwarePaginator(
            $merged->forPage($page, $perPage),
            $merged->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $cardTypes = DB::table('sec_id_cardno_master')->orderBy('sec_card_name')->pluck('sec_card_name', 'pk')->toArray();

        return view('admin.security.employee_idcard_approval.approval2', compact('requests', 'cardTypes'));
    }

    public function show($id)
    {
        try {
            $decrypted = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }
        if (is_string($decrypted) && str_starts_with($decrypted, 'c-')) {
            $pk = (int) substr($decrypted, 2);
            $row = DB::table('security_con_oth_id_apply')->where('pk', $pk)->first();
            if (!$row) {
                abort(404);
            }
            $request = IdCardSecurityMapper::toContractualRequestDto($row);
        } else {
            $row = SecurityParmIdApply::with(['employee.designation', 'employee.department', 'creator.department', 'approvals.approver'])
                ->findOrFail($decrypted);
            $request = IdCardSecurityMapper::toEmployeeRequestDto($row);
        }
        return view('admin.security.employee_idcard_approval.show', compact('request'));
    }

    public function approve1(Request $request, $id)
    {
        try {
            $decrypted = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $user = Auth::user();
        $employeePk = $user->user_id ?? $user->pk ?? null;

        if (is_string($decrypted) && str_starts_with($decrypted, 'c-')) {
            $pk = (int) substr($decrypted, 2);
            $row = DB::table('security_con_oth_id_apply')->where('pk', $pk)->first();
            if (!$row || (int) $row->id_status !== 1) {
                return redirect()->back()->with('error', 'This request is not pending your approval.');
            }
            if ((int) $row->department_approval_emp_pk !== (int) $employeePk) {
                return redirect()->back()->with('error', 'Only the designated Approval Authority can approve this request.');
            }
            $hasA1 = DB::table('security_con_oth_id_apply_approval')
                ->where('security_parm_id_apply_pk', $row->emp_id_apply)
                ->where('status', 1)
                ->exists();
            if ($hasA1) {
                return redirect()->back()->with('error', 'This request is not pending your approval.');
            }
            DB::table('security_con_oth_id_apply_approval')->insert([
                'security_parm_id_apply_pk' => $row->emp_id_apply,
                'status' => 1,
                'approval_remarks' => null,
                'recommend_status' => 1,
                'approval_emp_pk' => $employeePk,
                'created_by' => $employeePk,
                'created_date' => now()->format('Y-m-d H:i:s'),
                'modified_by' => $employeePk,
                'modified_date' => now()->format('Y-m-d H:i:s'),
            ]);
            return redirect()->route('admin.security.employee_idcard_approval.approval1')
                ->with('success', 'Request approved successfully. It will now move to Approver 2.');
        }

        $row = SecurityParmIdApply::findOrFail($decrypted);
        if ($row->id_status != SecurityParmIdApply::ID_STATUS_PENDING) {
            return redirect()->back()->with('error', 'This request is not pending your approval.');
        }
        $alreadyA1 = SecurityParmIdApplyApproval::where('security_parm_id_apply_pk', $row->emp_id_apply)->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_1)->exists();
        if ($alreadyA1) {
            return redirect()->back()->with('error', 'This request is not pending your approval.');
        }

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
        if (is_string($pk) && str_starts_with($pk, 'c-')) {
            $contPk = (int) substr($pk, 2);
            $row = DB::table('security_con_oth_id_apply')->where('pk', $contPk)->first();
            if (!$row || (int) $row->id_status !== 1) {
                return redirect()->back()->with('error', 'This request is not pending your approval.');
            }
            $hasA1 = DB::table('security_con_oth_id_apply_approval')->where('security_parm_id_apply_pk', $row->emp_id_apply)->where('status', 1)->exists();
            $hasA2 = DB::table('security_con_oth_id_apply_approval')->where('security_parm_id_apply_pk', $row->emp_id_apply)->where('status', 2)->exists();
            if (!$hasA1 || $hasA2) {
                return redirect()->back()->with('error', 'This request is not pending your approval.');
            }
            $employeePk = Auth::user()->user_id ?? Auth::user()->pk ?? null;
            DB::table('security_con_oth_id_apply_approval')->insert([
                'security_parm_id_apply_pk' => $row->emp_id_apply,
                'status' => 2,
                'approval_remarks' => null,
                'recommend_status' => null,
                'approval_emp_pk' => $employeePk,
                'created_by' => $employeePk,
                'created_date' => now()->format('Y-m-d H:i:s'),
                'modified_by' => $employeePk,
                'modified_date' => now()->format('Y-m-d H:i:s'),
            ]);
            DB::table('security_con_oth_id_apply')->where('pk', $contPk)->update(['id_status' => 2]);
            return redirect()->route('admin.security.employee_idcard_approval.approval2')
                ->with('success', 'Request approved successfully. ID card is now fully approved.');
        }
        $row = SecurityParmIdApply::findOrFail($pk);
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
        $user = Auth::user();
        $employeePk = $user->user_id ?? $user->pk ?? null;

        if (is_string($pk) && str_starts_with($pk, 'c-')) {
            $contPk = (int) substr($pk, 2);
            $row = DB::table('security_con_oth_id_apply')->where('pk', $contPk)->first();
            if (!$row || (int) $row->id_status !== 1) {
                return redirect()->back()->with('error', 'This request is not pending your action.');
            }
            if ($stage === 1 && (int) $row->department_approval_emp_pk !== (int) $employeePk) {
                return redirect()->back()->with('error', 'Only the designated Approval Authority can reject this request.');
            }
            $approvals = DB::table('security_con_oth_id_apply_approval')->where('security_parm_id_apply_pk', $row->emp_id_apply)->get();
            $hasA1 = $approvals->where('status', 1)->isNotEmpty();
            $hasA2 = $approvals->where('status', 2)->isNotEmpty();
            $hasRej = $approvals->where('status', 3)->isNotEmpty();
            if ($stage === 1 && ($hasA1 || $hasRej)) {
                return redirect()->back()->with('error', 'This request is not pending your action.');
            }
            if ($stage === 2 && (!$hasA1 || $hasA2 || $hasRej)) {
                return redirect()->back()->with('error', 'This request is not pending your action.');
            }
            DB::table('security_con_oth_id_apply_approval')->insert([
                'security_parm_id_apply_pk' => $row->emp_id_apply,
                'status' => 3,
                'approval_remarks' => $validated['rejection_reason'],
                'recommend_status' => null,
                'approval_emp_pk' => $employeePk,
                'created_by' => $employeePk,
                'created_date' => now()->format('Y-m-d H:i:s'),
                'modified_by' => $employeePk,
                'modified_date' => now()->format('Y-m-d H:i:s'),
            ]);
            DB::table('security_con_oth_id_apply')->where('pk', $contPk)->update(['id_status' => 3]);
            $route = $stage === 1 ? 'admin.security.employee_idcard_approval.approval1' : 'admin.security.employee_idcard_approval.approval2';
            return redirect()->route($route)->with('success', 'Request rejected.');
        }

        $row = SecurityParmIdApply::findOrFail($pk);
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
