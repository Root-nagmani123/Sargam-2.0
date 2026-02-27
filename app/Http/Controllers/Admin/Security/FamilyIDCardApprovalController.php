<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Models\SecurityFamilyIdApply;
use App\Models\SecurityFamilyIdApplyApproval;
use App\Support\IdCardSecurityMapper;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Family ID Card Approval - List pending family ID card requests and approve/reject.
 * Groups by (emp_id_apply, created_by, created_date) - one row per application with member count.
 */
class FamilyIDCardApprovalController extends Controller
{
    public function index()
    {
        
        $user = Auth::user();
        $currentEmployeePk = $user->user_id ?? $user->pk ?? null;

        // Base query for pending requests
        $baseQuery = SecurityFamilyIdApply::where('id_status', 1);

        // Filter based on employee type and authority
        $baseQuery->where(function ($q) use ($currentEmployeePk) {
            // Permanent employees: Only show their own requests (emp_id_apply = current user)
            $q->where(function ($subQ) use ($currentEmployeePk) {
                 if (hasRole('Admin')) {
                    // Admin can see all pending requests
                    $subQ->whereNotNull('emp_id_apply'); // No filter for admin
                } 
            });
            // Contractual employees: Show requests where department_approval_emp_pk = current user
          
        });

        $pendingRows = $baseQuery->orderBy('created_date', 'desc')->get();
        // print_r($pendingRows->toArray()); // Debug: Check the retrieved rows
        // die;

        $groupKey = function ($r) {
            $date = $r->created_date ? \Carbon\Carbon::parse($r->created_date)->format('Y-m-d H:i:s') : '';
            return $r->emp_id_apply . '|' . ($r->created_by ?? '') . '|' . $date;
        };
        $groups = $pendingRows->groupBy($groupKey);
        $creatorUserIds = $pendingRows->pluck('created_by')->filter()->unique();
        $creators = collect();
        if ($creatorUserIds->isNotEmpty()) {
            // created_by in SecurityFamilyIdApply stores user_id (like 'ITS005'), not pk
            $ucs = DB::table('user_credentials')->whereIn('user_id', $creatorUserIds)->get(['user_id', 'pk']);
            $empPks = $ucs->pluck('pk')->filter()->unique();
            $empNames = collect();
            if ($empPks->isNotEmpty()) {
                $emps = DB::table('employee_master')->whereIn('pk', $empPks)->get(['pk', 'first_name', 'last_name']);
                foreach ($emps as $e) {
                    $empNames[(string) $e->pk] = trim(($e->first_name ?? '') . ' ' . ($e->last_name ?? ''));
                }
            }
            foreach ($ucs as $uc) {
                $name = $empNames[(string) ($uc->pk ?? '')] ?? null;
                $creators[(string) $uc->user_id] = $name ?: ('User #' . $uc->user_id);
            }
        }

        $groupList = $groups->map(function ($rows) use ($creators) {
            $first = $rows->sortBy('fml_id_apply')->first();
            $creatorName = $creators[(string) ($first->created_by ?? '')] ?? ('User #' . ($first->created_by ?? '--'));
            return (object) [
                'first_id' => $first->fml_id_apply,
                'emp_id_apply' => $first->emp_id_apply,
                'created_by' => $first->created_by,
                'created_date' => $first->created_date,
                'submitted_by' => $creatorName,
                'member_count' => $rows->count(),
                'members' => $rows,
                'employee_type' => $first->employee_type,
            ];
        })->values();

        $perPage = 10;
        $page = (int) request()->get('page', 1);
        $paginator = new LengthAwarePaginator(
            $groupList->forPage($page, $perPage)->values(),
            $groupList->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page']
        );
        $paginator->withQueryString();

        return view('admin.security.family_idcard_approval.index', [
            'groups' => $paginator,
        ]);
    }

    public function show($id)
    {
        try {
            $fmlIdApply = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $application = SecurityFamilyIdApply::with('approvals')
            ->where('fml_id_apply', $fmlIdApply)
            ->firstOrFail();

        $request = IdCardSecurityMapper::toFamilyRequestDto($application);

        return view('admin.security.family_idcard_approval.show', [
            'application' => $application,
            'request' => $request,
        ]);
    }

    public function approve(Request $request, $id)
    {
        try {
            $fmlIdApply = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $validated = $request->validate([
            'approval_remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $application = SecurityFamilyIdApply::where('fml_id_apply', $fmlIdApply)->firstOrFail();

        if ((int) $application->id_status !== 1) {
            return redirect()->back()->with('error', 'Application already processed');
        }

        $user = Auth::user();
        $employeePk = $user->user_id ?? null;

        $application->id_status = 2;
        $application->save();

        SecurityFamilyIdApplyApproval::create([
            'security_fm_id_apply_pk' => $fmlIdApply,
            'status' => 1,
            'approval_remarks' => $validated['approval_remarks'] ?? null,
            'created_by' => $employeePk,
            'created_date' => now(),
        ]);

        return redirect()->route('admin.security.family_idcard_approval.index')
            ->with('success', 'Family ID Card approved successfully');
    }

    public function reject(Request $request, $id)
    {
        try {
            $fmlIdApply = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $validated = $request->validate([
            'approval_remarks' => ['required', 'string', 'max:500'],
        ]);

        $application = SecurityFamilyIdApply::where('fml_id_apply', $fmlIdApply)->firstOrFail();

        if ((int) $application->id_status !== 1) {
            return redirect()->back()->with('error', 'Application already processed');
        }

        $user = Auth::user();
        $employeePk = $user->user_id ?? null;

        $application->id_status = 3;
        $application->save();

        SecurityFamilyIdApplyApproval::create([
            'security_fm_id_apply_pk' => $fmlIdApply,
            'status' => 3,
            'approval_remarks' => $validated['approval_remarks'],
            'created_by' => $employeePk,
            'created_date' => now(),
        ]);

        return redirect()->route('admin.security.family_idcard_approval.index')
            ->with('success', 'Family ID Card rejected');
    }

    /**
     * Approve entire group (all pending members with same emp_id_apply, created_by, created_date).
     * @param string $id Encrypted first_id (fml_id_apply of first member in group)
     */
    public function approveGroup(Request $request, $id)
    {
        try {
            $firstId = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $application = SecurityFamilyIdApply::where('fml_id_apply', $firstId)->firstOrFail();
        $groupRows = SecurityFamilyIdApply::where('emp_id_apply', $application->emp_id_apply)
            ->where('created_by', $application->created_by)
            ->where('created_date', $application->created_date)
            ->where('id_status', 1)
            ->get();

        if ($groupRows->isEmpty()) {
            return redirect()->route('admin.security.family_idcard_approval.index')
                ->with('error', 'No pending members to approve');
        }

        $user = Auth::user();
        $employeePk = $user->user_id ?? null;
        $remarks = $request->input('approval_remarks', null);

        foreach ($groupRows as $row) {
            $row->id_status = 2;
            $row->save();
            SecurityFamilyIdApplyApproval::create([
                'security_fm_id_apply_pk' => $row->fml_id_apply,
                'status' => 1,
                'approval_remarks' => $remarks,
                'created_by' => $employeePk,
                'created_date' => now(),
            ]);
        }

        return redirect()->route('admin.security.family_idcard_approval.index')
            ->with('success', 'Family ID Card group approved (' . $groupRows->count() . ' members)');
    }

    /**
     * Reject entire group (all pending members with same emp_id_apply, created_by, created_date).
     * @param string $id Encrypted first_id (fml_id_apply of first member in group)
     */
    public function rejectGroup(Request $request, $id)
    {
        try {
            $firstId = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $validated = $request->validate([
            'approval_remarks' => ['required', 'string', 'max:500'],
        ]);

        $application = SecurityFamilyIdApply::where('fml_id_apply', $firstId)->firstOrFail();
        $groupRows = SecurityFamilyIdApply::where('emp_id_apply', $application->emp_id_apply)
            ->where('created_by', $application->created_by)
            ->where('created_date', $application->created_date)
            ->where('id_status', 1)
            ->get();

        if ($groupRows->isEmpty()) {
            return redirect()->route('admin.security.family_idcard_approval.index')
                ->with('error', 'No pending members to reject');
        }

        $user = Auth::user();
        $employeePk = $user->user_id ?? null;

        foreach ($groupRows as $row) {
            $row->id_status = 3;
            $row->save();
            SecurityFamilyIdApplyApproval::create([
                'security_fm_id_apply_pk' => $row->fml_id_apply,
                'status' => 3,
                'approval_remarks' => $validated['approval_remarks'],
                'created_by' => $employeePk,
                'created_date' => now(),
            ]);
        }

        return redirect()->route('admin.security.family_idcard_approval.index')
            ->with('success', 'Family ID Card group rejected (' . $groupRows->count() . ' members)');
    }

    public function all()
    {
        $applications = SecurityFamilyIdApply::with('approvals')
            ->orderBy('created_date', 'desc')
            ->paginate(15);

        return view('admin.security.family_idcard_approval.all', compact('applications'));
    }
}
