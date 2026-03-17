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

        $search = trim(request()->get('search', ''));
        $dateFrom = request()->get('date_from');
        $dateTo = request()->get('date_to');

        $isLevel1 = hasRole('Security Card') && !hasRole('Admin Security');
        $isLevel2 = hasRole('Admin Security') && !hasRole('Security Card');

        // Base query: ALL family ID card requests (Pending / Approved / Rejected), with approval history
        // Requirement: Both Security Card (Level 1) and Admin Security (Level 2) should see ALL records,
        // with actionability controlled by can_approve flags only.
        $baseQuery = SecurityFamilyIdApply::with('approvals')->orderBy('created_date', 'desc');

        // Date filters on created_date
        if (!empty($dateFrom)) {
            try {
                $from = \Carbon\Carbon::parse($dateFrom)->startOfDay()->toDateTimeString();
                $baseQuery->where('created_date', '>=', $from);
            } catch (\Exception $e) {
            }
        }
        if (!empty($dateTo)) {
            try {
                $to = \Carbon\Carbon::parse($dateTo)->endOfDay()->toDateTimeString();
                $baseQuery->where('created_date', '<=', $to);
            } catch (\Exception $e) {
            }
        }

        $pendingRows = $baseQuery->get();

        $groupKey = function ($r) {
            $date = $r->created_date ? \Carbon\Carbon::parse($r->created_date)->format('Y-m-d H:i:s') : '';
            return $r->emp_id_apply . '|' . ($r->created_by ?? '') . '|' . $date;
        };
        $groups = $pendingRows->groupBy($groupKey);
        $creatorPks = $pendingRows->pluck('created_by')->filter()->unique();
        $creators = collect();
        if ($creatorPks->isNotEmpty()) {
            // created_by may store either current pk or legacy pk_old from employee_master
            $emps = DB::table('employee_master')
                ->whereIn('pk', $creatorPks)
                ->orWhereIn('pk_old', $creatorPks)
                ->get(['pk', 'pk_old', 'first_name', 'last_name']);

            foreach ($emps as $e) {
                $fullName = trim(($e->first_name ?? '') . ' ' . ($e->last_name ?? ''));
                $label = $fullName ?: ('Employee #' . ($e->pk ?? $e->pk_old));

                if (!is_null($e->pk)) {
                    $creators[(string) $e->pk] = $label;
                }
                if (!is_null($e->pk_old)) {
                    $creators[(string) $e->pk_old] = $label;
                }
            }
        }

        $groupList = $groups->map(function ($rows) use ($creators, $isLevel1, $isLevel2) {
            $first = $rows->sortBy('fml_id_apply')->first();
            $creatorName = $creators[(string) ($first->created_by ?? '')] ?? ('User #' . ($first->created_by ?? '--'));

            // Determine phase/status
            $statusInt = (int) ($first->id_status ?? 1); // 1=Pending,2=Approved,3=Rejected
            $hasLevel1 = $first->approvals && $first->approvals->where('status', 1)->isNotEmpty();
            $hasLevel2 = $first->approvals && $first->approvals->where('status', 2)->isNotEmpty();

            $phaseLabel = 'Pending (Level 1)';
            $phaseClass = 'warning';
            if ($statusInt === 2 && $hasLevel2) {
                $phaseLabel = 'Approved';
                $phaseClass = 'success';
            } elseif ($statusInt === 3) {
                $phaseLabel = 'Rejected';
                $phaseClass = 'danger';
            } elseif ($statusInt === 1 && $hasLevel1 && ! $hasLevel2) {
                $phaseLabel = 'Pending Final Approval';
                $phaseClass = 'primary';
            }

            // Can current role approve this group?
            $canApprove = false;
            if ($statusInt === 1) {
                if ($isLevel1 && ! $hasLevel1) {
                    // Level 1: Pending and no L1 approval yet
                    $canApprove = true;
                } elseif ($isLevel2 && $hasLevel1 && ! $hasLevel2) {
                    // Level 2: L1 done, final approval pending
                    $canApprove = true;
                }
            }

            return (object) [
                'first_id' => $first->fml_id_apply,
                'emp_id_apply' => $first->emp_id_apply,
                'created_by' => $first->created_by,
                'created_date' => $first->created_date,
                'submitted_by' => $creatorName,
                'member_count' => $rows->count(),
                'members' => $rows,
                'employee_type' => $first->employee_type,
                'phase_label' => $phaseLabel,
                'phase_class' => $phaseClass,
                'can_approve' => $canApprove,
                'status_int' => $statusInt,
                'has_level1' => $hasLevel1,
                'has_level2' => $hasLevel2,
            ];
        })->values();

        // Text search across Submitted By and Employee ID (case-insensitive)
        if ($search !== '') {
            $searchLower = mb_strtolower($search);
            $groupList = $groupList->filter(function ($g) use ($searchLower) {
                $submitted = mb_strtolower($g->submitted_by ?? '');
                $empId = mb_strtolower($g->emp_id_apply ?? '');
                return str_contains($submitted, $searchLower) || str_contains($empId, $searchLower);
            })->values();
        }

        // For Security Admin (Level 2), show only records where Level 1 is completed
        // i.e. recommendation given (has_level1 = true). Pending Level 1 rows are hidden.
        if ($isLevel2) {
            $groupList = $groupList->filter(function ($g) {
                return (bool) ($g->has_level1 ?? false);
            })->values();
        }

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
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
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

        $user = Auth::user();
        $employeePk = $user->user_id ?? null;

        // Determine level based on role
        $isLevel1 = hasRole('Security Card') && !hasRole('Admin Security');
        $isLevel2 = hasRole('Admin Security') || hasRole('Admin');

        if (! $isLevel1 && ! $isLevel2) {
            return redirect()->back()->with('error', 'You are not authorized to approve this request.');
        }

        // On first approval: update existing status 0 row (or create) to status=1, recommend_status=1,
        // and create a fresh status 0 row for the next level.
        if ($isLevel1) {
            if ((int) $application->id_status !== 1) {
                return redirect()->back()->with('error', 'Application already processed');
            }

            // Find latest approval row with status 0 for this application
            $baseApproval = SecurityFamilyIdApplyApproval::where('security_fm_id_apply_pk', $fmlIdApply)
                ->where('status', 0)
                ->orderByDesc('created_date')
                ->first();

            if (! $baseApproval) {
                $baseApproval = new SecurityFamilyIdApplyApproval([
                    'security_fm_id_apply_pk' => $fmlIdApply,
                    'status' => 0,
                ]);
            }

            $baseApproval->status = 1;
            $baseApproval->approval_remarks = $validated['approval_remarks'] ?? null;
            $baseApproval->recommend_status = 1;
            $baseApproval->approval_emp_pk = $employeePk;
            $baseApproval->created_by = $baseApproval->created_by ?: $employeePk;
            $baseApproval->created_date = $baseApproval->created_date ?: now();
            $baseApproval->modified_by = $employeePk;
            $baseApproval->modified_date = now();
            $baseApproval->save();

            // Prepare a fresh row with status 0 for final approval level
            SecurityFamilyIdApplyApproval::create([
                'security_fm_id_apply_pk' => $fmlIdApply,
                'status' => 0,
                'approval_remarks' => null,
                'recommend_status' => null,
                'approval_emp_pk' => null,
                'created_by' => $employeePk,
                'created_date' => now(),
                'modified_by' => $employeePk,
                'modified_date' => now(),
            ]);
        } elseif ($isLevel2) {
            // Final approval: mark latest status 0 row as status=2 and update application to Approved
            if ((int) $application->id_status !== 1) {
                return redirect()->back()->with('error', 'Application is not pending final approval.');
            }

            $pendingApproval = SecurityFamilyIdApplyApproval::where('security_fm_id_apply_pk', $fmlIdApply)
                ->where('status', 0)
                ->orderByDesc('created_date')
                ->first();

            if (! $pendingApproval) {
                return redirect()->back()->with('error', 'No pending approval step found for this application.');
            }

            $pendingApproval->status = 2;
            $pendingApproval->approval_remarks = $validated['approval_remarks'] ?? null;
            $pendingApproval->approval_emp_pk = $employeePk;
            $pendingApproval->modified_by = $employeePk;
            $pendingApproval->modified_date = now();
            $pendingApproval->save();

            $application->id_status = 2;
            $application->save();
        }

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

        $isLevel1 = hasRole('Security Card') && !hasRole('Admin Security');
        $isLevel2 = hasRole('Admin Security') || hasRole('Admin');

        if (! $isLevel1 && ! $isLevel2) {
            return redirect()->route('admin.security.family_idcard_approval.index')
                ->with('error', 'You are not authorized to approve these requests.');
        }

        foreach ($groupRows as $row) {
            if ($isLevel1) {
                if ((int) $row->id_status !== 1) {
                    continue;
                }
                $baseApproval = SecurityFamilyIdApplyApproval::where('security_fm_id_apply_pk', $row->fml_id_apply)
                    ->where('status', 0)
                    ->orderByDesc('created_date')
                    ->first();

                if (! $baseApproval) {
                    $baseApproval = new SecurityFamilyIdApplyApproval([
                        'security_fm_id_apply_pk' => $row->fml_id_apply,
                        'status' => 0,
                    ]);
                }

                $baseApproval->status = 1;
                $baseApproval->approval_remarks = $remarks;
                $baseApproval->recommend_status = 1;
                $baseApproval->approval_emp_pk = $employeePk;
                $baseApproval->created_by = $baseApproval->created_by ?: $employeePk;
                $baseApproval->created_date = $baseApproval->created_date ?: now();
                $baseApproval->modified_by = $employeePk;
                $baseApproval->modified_date = now();
                $baseApproval->save();

                SecurityFamilyIdApplyApproval::create([
                    'security_fm_id_apply_pk' => $row->fml_id_apply,
                    'status' => 0,
                    'approval_remarks' => null,
                    'recommend_status' => null,
                    'approval_emp_pk' => null,
                    'created_by' => $employeePk,
                    'created_date' => now(),
                    'modified_by' => $employeePk,
                    'modified_date' => now(),
                ]);
            } elseif ($isLevel2) {
                if ((int) $row->id_status !== 1) {
                    continue;
                }

                $pendingApproval = SecurityFamilyIdApplyApproval::where('security_fm_id_apply_pk', $row->fml_id_apply)
                    ->where('status', 0)
                    ->orderByDesc('created_date')
                    ->first();

                if (! $pendingApproval) {
                    continue;
                }

                $pendingApproval->status = 2;
                $pendingApproval->approval_remarks = $remarks;
                $pendingApproval->approval_emp_pk = $employeePk;
                $pendingApproval->modified_by = $employeePk;
                $pendingApproval->modified_date = now();
                $pendingApproval->save();

                $row->id_status = 2;
                $row->save();
            }
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
