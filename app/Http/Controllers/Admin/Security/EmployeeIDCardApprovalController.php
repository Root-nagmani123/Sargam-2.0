<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Models\SecurityParmIdApply;
use App\Models\SecurityParmIdApplyApproval;
use App\Models\SecurityDupPermIdApply;
use App\Models\SecurityDupPermIdApplyApproval;
use App\Models\SecurityDupOtherIdApply;
use App\Models\SecurityDupOtherIdApplyApproval;
use App\Support\IdCardSecurityMapper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Approval I: Contractual ID Card requests + Contractual Duplicate ID Card requests
 * Only shows requests where current user is the department_approval_emp_pk (Approval Authority)
 * Approval II / All Requests: Permanent ID Card + Permanent Duplicate ID Card + Contractual (post Approval I) as per flow.
 */
class EmployeeIDCardApprovalController extends Controller
{
    /**
     * Approval I: Only contractual employee requests where current user is the Approval Authority.
     * Includes:
     *  - security_con_oth_id_apply (Contractual regular ID Card requests)
     *  - security_dup_other_id_apply where card_type = 'Contractual' (Contractual Duplicate ID Card requests only)
     * Does NOT include: Permanent duplicate, Family duplicate (those go to Approval 2 only).
     * Filtered by department_approval_emp_pk = current user's employee pk
     */
    public function approval1(Request $request)
    {
        $user = Auth::user();
        $currentEmployeePk = $user->user_id ?? $user->pk ?? null;

        // Contractual regular ID Card requests - Approval 1
        $contA1Done = DB::table('security_con_oth_id_apply_approval')
            ->where('status', 1)
            ->pluck('security_parm_id_apply_pk');
        $contQuery = DB::table('security_con_oth_id_apply')
            // Show history too: Pending/Approved/Rejected
            ->whereIn('id_status', [1, 2, 3])
            // Approval-I scope: requests assigned to current authority
            ->where('department_approval_emp_pk', $currentEmployeePk);
        // (No whereNotIn here: we want already-approved rows to still appear as view-only.)
        $contQuery->orderByDesc('created_date');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $searchLike = '%' . $search . '%';
            $contQuery->where(function ($q) use ($searchLike) {
                $q->where('employee_name', 'like', $searchLike)
                    ->orWhere('id_card_no', 'like', $searchLike);
            });
        }

        // Date filters (by created_date)
        if ($request->filled('date_from')) {
            $from = \Carbon\Carbon::parse($request->date_from)->startOfDay()->toDateTimeString();
            $contQuery->where('created_date', '>=', $from);
        }
        if ($request->filled('date_to')) {
            $to = \Carbon\Carbon::parse($request->date_to)->endOfDay()->toDateTimeString();
            $contQuery->where('created_date', '<=', $to);
        }
        if ($request->filled('card_type')) {
            $contQuery->where('permanent_type', $request->card_type);
        }

        $contRows = $contQuery->get();
        $contA1DoneArr = $contA1Done->toArray();
        $contDtos = $contRows->map(function ($r) use ($contA1DoneArr) {
            $dto = IdCardSecurityMapper::toContractualRequestDto($r);

            // Normalize status fields so the shared approval table can hide action buttons
            // once a request is Approved / Rejected.
            $dto->id_status = (int) ($dto->id_status ?? $r->id_status ?? 0);
            $dto->status = match ((int) ($dto->id_status ?? 0)) {
                1 => 'Pending',
                2 => 'Approved',
                3 => 'Rejected',
                default => 'Unknown',
            };

            // Section head (Approval I) for contractual regular: approve1() sets
            // depart_approval_status = 2 and inserts security row with approval status 0 (not 1).
            // So we must key "A1 done" off the main table, not approval.status = 1.
            $sectionHeadDone = (int) ($r->depart_approval_status ?? 0) === 2;
            $legacyA1InApprovalTable = in_array($r->emp_id_apply ?? null, $contA1DoneArr, false);

            if ((int) ($dto->id_status ?? 0) === 1 && ($sectionHeadDone || $legacyA1InApprovalTable)) {
                $dto->is_view_only = true;
            }

            return $dto;
        });

        // Contractual Duplicate ID Card requests only (not Permanent/Family) - same approving authority
        $dupContA1Done = DB::table('security_dup_other_id_apply_approval')
            ->where('status', 1)
            ->pluck('security_con_id_apply_pk');
        $dupContQuery = DB::table('security_dup_other_id_apply')
            // Show history too: Pending/Approved/Rejected
            ->whereIn('id_status', [1, 2, 3])
            ->where('card_type', 'Contractual')
            // Approval-I scope: requests assigned to current authority
            ->where('department_approval_emp_pk', $currentEmployeePk);
            
        // (No whereNotIn here: we want already-approved rows to still appear as view-only.)
        $dupContQuery->orderByDesc('created_date');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $searchLike = '%' . $search . '%';
            $dupContQuery->where(function ($q) use ($searchLike) {
                $q->where('employee_name', 'like', $searchLike)
                    ->orWhere('id_card_no', 'like', $searchLike);
            });
        }
        if ($request->filled('date_from')) {
            $from = \Carbon\Carbon::parse($request->date_from)->startOfDay()->toDateTimeString();
            $dupContQuery->where('created_date', '>=', $from);
        }
        if ($request->filled('date_to')) {
            $to = \Carbon\Carbon::parse($request->date_to)->endOfDay()->toDateTimeString();
            $dupContQuery->where('created_date', '<=', $to);
        }

        $dupContRows = $dupContQuery->get();
        $deptMap = DB::table('department_master')->pluck('department_name', 'pk')->toArray();
        $dupContA1DoneArr = $dupContA1Done->toArray();
        $dupContDtos = $dupContRows->map(function ($r) use ($deptMap, $dupContA1DoneArr) {
            $requestedSection = null;
            if (!empty($r->section) && isset($deptMap[$r->section])) {
                $requestedSection = $deptMap[$r->section];
            }
            $dto = new \stdClass();
            $dto->id = 'c-' . $r->emp_id_apply;
            $dto->pk = 0;
            $dto->emp_id_apply = $r->emp_id_apply ?? '';
            $dto->name = $r->employee_name ?? '--';
            $dto->designation = $r->designation_name ?? '--';
            $dto->photo = $r->id_photo_path ?? null;
            $dto->joining_letter = null;
            $dto->created_at = isset($r->created_date) ? \Carbon\Carbon::parse($r->created_date) : null;
            $dto->card_type = $r->card_type ?? 'Contractual';
            $dto->request_for = 'Duplication';
            $dto->duplication_reason = $r->card_reason ?? null;
            $dto->id_card_valid_upto = isset($r->card_valid_to) ? \Carbon\Carbon::parse($r->card_valid_to)->format('d/m/Y') : null;
            $dto->id_card_valid_from = isset($r->card_valid_from) ? \Carbon\Carbon::parse($r->card_valid_from)->format('d/m/Y') : null;
            $dto->id_card_number = $r->id_card_no ?? null;
            $dto->date_of_birth = $r->employee_dob ?? null;
            $dto->mobile_number = $r->mobile_no ?? null;
            $dto->telephone_number = null;
            $dto->blood_group = $r->blood_group ?? null;
            $dto->remarks = $r->remarks ?? null;
            $dto->created_by = $r->created_by ?? null;
            $dto->id_status = (int) ($r->id_status ?? 0);
            $dto->status = match ((int) ($r->id_status ?? 0)) {
                1 => 'Pending',
                2 => 'Approved',
                3 => 'Rejected',
                default => 'Unknown',
            };
            $dto->request_type = 'duplicate';
            $dto->father_name = null;
            $dto->requested_section = $requestedSection;
            if ((int) ($r->id_status ?? 0) === 1 && in_array(($r->emp_id_apply ?? ''), $dupContA1DoneArr, true)) {
                $dto->is_view_only = true;
            }
            return $dto;
        });

        $merged = $contDtos->concat($dupContDtos)->sortByDesc('created_at')->values();

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

        return view('admin.security.employee_idcard_approval.approval1', compact('requests', 'cardTypes'));
    }

    public function approval2(Request $request)
    {
        // Default filter: show records from 01-03-2026 onward unless user selects another date.
        if (!$request->filled('date_from')) {
            $request->merge(['date_from' => '2026-03-01']);
        }

        $user = Auth::user();
        $currentEmployeePk = $user->user_id ?? $user->pk ?? null;

        $hasA1 = SecurityParmIdApplyApproval::select('security_parm_id_apply_pk')
            ->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_1);
        $hasA2 = SecurityParmIdApplyApproval::select('security_parm_id_apply_pk')
            ->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_2);
        // Permanent employees: go directly to Approval 2 (no Approval 1 required)
        // Contractual employees can also come here after completing Approval 1
        // Show ALL permanent requests (Pending / Approved / Rejected) with clear status.
        $permQuery = SecurityParmIdApply::with(['employee.designation', 'employee.department', 'creator.department', 'approvals.approver'])
            ->orderBy('created_date', 'desc');
        if (Schema::hasColumn('security_parm_id_apply', 'id_card_generate_date')) {
            $permQuery->whereNull('id_card_generate_date');
        }

        // Permanent Duplicate: show both pending and already finally approved records at Approval II.
        // Non-pending rows will be view-only in the table.
        $dupPermQuery = DB::table('security_dup_perm_id_apply as dup')
            ->leftJoin('employee_master as emp', 'dup.employee_master_pk', '=', 'emp.pk')
            ->leftJoin('designation_master as desig', function ($j) {
                $j->on('dup.designation_pk', '=', 'desig.pk')
                    ->orOn('emp.designation_master_pk', '=', 'desig.pk');
            })
            ->leftJoin('department_master as dept', 'emp.department_master_pk', '=', 'dept.pk');
        if (Schema::hasColumn('security_dup_perm_id_apply', 'id_card_generate_date')) {
            $dupPermQuery->whereNull('dup.id_card_generate_date');
        }
        $dupPermQuery->orderByDesc('dup.created_date')
            ->select([
                'dup.*',
                'emp.first_name',
                'emp.last_name',
                'desig.designation_name',
                'dept.department_name',
            ]);
           
          
               

        if ($request->filled('search')) {
            $search = trim($request->search);
            $searchLike = '%' . $search . '%';
            // Match full name (first_name + ' ' + last_name) so "RAJ SHOD" finds "Raj Shod"
            $permQuery->where(function ($q) use ($search, $searchLike) {
                $q->whereHas('employee', function ($eq) use ($searchLike) {
                    $eq->whereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", [$searchLike]);
                })
                    ->orWhere('id_card_no', 'like', $searchLike);
            });
            // security_dup_perm_id_apply: match full name from joined emp (employee_master)
            $dupPermQuery->where(function ($q) use ($searchLike) {
                $q->where('dup.id_card_no', 'like', $searchLike)
                    ->orWhereRaw("CONCAT(COALESCE(emp.first_name, ''), ' ', COALESCE(emp.last_name, '')) LIKE ?", [$searchLike]);
            });
        }

        // Date filters (by created_date)
        if ($request->filled('date_from')) {
            $from = \Carbon\Carbon::parse($request->date_from)->startOfDay()->toDateTimeString();
            $permQuery->where('created_date', '>=', $from);
            $dupPermQuery->where('dup.created_date', '>=', $from);
        }
        if ($request->filled('date_to')) {
            $to = \Carbon\Carbon::parse($request->date_to)->endOfDay()->toDateTimeString();
            $permQuery->where('created_date', '<=', $to);
            $dupPermQuery->where('dup.created_date', '<=', $to);
        }
        if ($request->filled('card_type')) {
            $permQuery->where('permanent_type', $request->card_type);
        }

        // Pagination parameters
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
        $newPage = max(1, (int) $request->get('new_page', 1));
        $forApprovalPage = max(1, (int) $request->get('for_page', 1));
        $archivePage = max(1, (int) $request->get('archive_page', 1));

        // For tab-wise split + independent pagination we need complete filtered sets.
        $permRows = $permQuery->get();
        $dupPermRows = $dupPermQuery->get();
        $dupPermCardLabels = $this->mapDupPermIdCardTypeLabels($dupPermRows);

        // PERF: Avoid N+1 queries for permanent duplicate approvals by preloading flags in bulk.
        $dupPermIds = $dupPermRows->pluck('emp_id_apply')->filter()->unique()->values();
        $dupPermRecommendedMap = [];
        $dupPermFinalMap = [];
        if ($dupPermIds->isNotEmpty()) {
            $dupPermApprovalRows = DB::table('security_dup_perm_id_apply_approval')
                ->select(['security_parm_id_apply_pk', 'status', 'recommend_status'])
                ->whereIn('security_parm_id_apply_pk', $dupPermIds->all())
                ->whereIn('status', [1, 2])
                ->get();

            foreach ($dupPermApprovalRows as $a) {
                $k = (string) ($a->security_parm_id_apply_pk ?? '');
                if ($k === '') {
                    continue;
                }
                if ((int) $a->status === 2) {
                    $dupPermFinalMap[$k] = true;
                }
                if ((int) $a->status === 1 && (int) ($a->recommend_status ?? 0) === 1) {
                    $dupPermRecommendedMap[$k] = true;
                }
            }
        }


        // Map Permanent rows to DTOs and mark those which already have Approval II as view-only (pending final approval).
        $permDtos = $permRows->map(function ($r) {
            $dto = IdCardSecurityMapper::toEmployeeRequestDto($r);
            $hasA2 = $r->approvals
                ? $r->approvals->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_2)->isNotEmpty()
                : false;
            if ($hasA2) {
                $dto->is_view_only = true;
                $dto->final_status_hint = 'Pending for final approval';
            }
            return $dto;
        });
        // For duplicate permanent records (stdClass from DB query), create DTOs directly without mapper
        // and mark those which already have recommendation (recommend_status = 1) as view-only (pending final approval).
        $dupPermDtos = $dupPermRows->map(function ($r) use ($dupPermRecommendedMap, $dupPermFinalMap, $dupPermCardLabels) {
            $fullName = trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? ''));
            if ($fullName === '') {
                $fullName = $r->employee_name ?? '--';
            }
            $applyKey = (string) ($r->emp_id_apply ?? '');
            // Map base fields
            $dto = (object) [
                'id' => $r->emp_id_apply,
                'employee_type' => 'Permanent Employee',
                'name' => $fullName,
                'designation' => $r->designation_name ?? null,
                'father_name' => null,
                'id_card_number' => $r->id_card_no,
                'card_type' => trim((string) ($dupPermCardLabels[$applyKey] ?? '--')) ?: '--',
                'date_of_birth' => $r->employee_dob,
                'blood_group' => $r->blood_group,
                'mobile_number' => $r->mobile_no,
                'telephone_number' => null,
                'id_card_valid_from' => $r->card_valid_from,
                'id_card_valid_upto' => $r->card_valid_to,
                'photo' => $r->id_photo_path,
                'created_at' => isset($r->created_date) ? \Carbon\Carbon::parse($r->created_date) : null,
                'requested_by' => $fullName,
                'requested_section' => $r->department_name ?? null,
                'request_type' => 'duplicate',
            ];

            // Base status from main duplicate table
            $idStatus = (int) ($r->id_status ?? 1);
            $dto->status = match ($idStatus) {
                1 => 'Pending',
                2 => 'Approved',
                3 => 'Rejected',
                default => 'Unknown',
            };

            // Apply preloaded recommendation / final approval flags
            $applyKey = (string) ($r->emp_id_apply ?? '');
            if ($applyKey !== '' && isset($dupPermFinalMap[$applyKey])) {
                // Fully approved – show as Approved, view-only
                $dto->is_view_only = true;
                $dto->final_status_hint = 'Approved at final level';
                $dto->status = 'Approved';
            } elseif ($applyKey !== '' && isset($dupPermRecommendedMap[$applyKey])) {
                // Recommended by Security Card, pending final approval by Admin Security
                $dto->is_view_only = true;
                $dto->final_status_hint = 'Pending for final approval';
                $dto->status = 'Pending';
            }

            return $dto;
        });

        // Contractual Regular: shown at Approval 2 for Security Card (Level 2)
        // Criteria: contractual requests with overall status pending and department approval done.
        $contQuery = DB::table('security_con_oth_id_apply')
            ->where('depart_approval_status', 2);
        if (Schema::hasColumn('security_con_oth_id_apply', 'id_card_generate_date')) {
            $contQuery->whereNull('id_card_generate_date');
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $contQuery->where(function ($q) use ($search) {
                $q->where('employee_name', 'like', '%' . $search . '%')
                    ->orWhere('id_card_no', 'like', '%' . $search . '%');
            });
        }
        if ($request->filled('date_from')) {
            $from = \Carbon\Carbon::parse($request->date_from)->startOfDay()->toDateTimeString();
            $contQuery->where('created_date', '>=', $from);
        }
        if ($request->filled('date_to')) {
            $to = \Carbon\Carbon::parse($request->date_to)->endOfDay()->toDateTimeString();
            $contQuery->where('created_date', '<=', $to);
        }
        $contQuery->orderByDesc('created_date');

        // For contractual regular, detect which requests already have Level 2 (A2) approval
        // so that they can be shown as "Pending from final approval" and made view-only.
        $contHasA2 = DB::table('security_con_oth_id_apply_approval')
            ->where('status', 1)
            ->where('recommend_status', 1)
            ->pluck('security_parm_id_apply_pk')
            ->toArray();

        // Contractual Duplicate: shown at Approval 2.
        // Recommended rows should remain visible here as view-only (pending final approval),
        // and actionable rows remain available for approve/reject.
        $dupContFinalDoneIds = DB::table('security_dup_other_id_apply_approval')
            ->whereIn('status', [2, 3])
            ->pluck('security_con_id_apply_pk')
            ->toArray();
        $dupContRecommended = DB::table('security_dup_other_id_apply_approval')
            ->where('status', 1)
            ->where('recommend_status', 1)
            ->pluck('security_con_id_apply_pk')
            ->toArray();
        // If a request is finally approved/rejected, it should not be treated as "pending final approval"
        if (!empty($dupContFinalDoneIds) && !empty($dupContRecommended)) {
            $dupContRecommended = array_values(array_diff($dupContRecommended, $dupContFinalDoneIds));
        }
        // Include pending + finally approved/rejected so Approval II can show history as view-only.
        $dupContQuery = DB::table('security_dup_other_id_apply')
            ->whereIn('id_status', [1, 2, 3])
            ->where('depart_approval_status', 2);
        if (Schema::hasColumn('security_dup_other_id_apply', 'id_card_generate_date')) {
            $dupContQuery->whereNull('id_card_generate_date');
        }
        // Approval II list is for Security level; do not filter by department_approval_emp_pk (Section Head).
        if ($request->filled('search')) {
            $search = $request->search;
            $dupContQuery->where(function ($q) use ($search) {
                $q->where('employee_name', 'like', '%' . $search . '%')
                    ->orWhere('id_card_no', 'like', '%' . $search . '%');
            });
        }
        if ($request->filled('date_from')) {
            $from = \Carbon\Carbon::parse($request->date_from)->startOfDay()->toDateTimeString();
            $dupContQuery->where('created_date', '>=', $from);
        }
        if ($request->filled('date_to')) {
            $to = \Carbon\Carbon::parse($request->date_to)->endOfDay()->toDateTimeString();
            $dupContQuery->where('created_date', '<=', $to);
        }
        $dupContQuery->orderByDesc('created_date');

        $contRows = $contQuery->get();
        $dupContRows = $dupContQuery->get();
        $dupContCardLabels = $this->mapDupOtherIdCardTypeLabels($dupContRows);
        $deptMap = DB::table('department_master')->pluck('department_name', 'pk')->toArray();
        
        $contDtos = $contRows->map(function ($r) use ($contHasA2) {
            $dto = IdCardSecurityMapper::toContractualRequestDto($r);

            // If this contractual request already has Level 2 (A2) approval,
            // then at Approval 2 screen it should be view-only and marked
            // as pending from final approval.
            if (in_array($r->emp_id_apply, $contHasA2, true)) {
                $dto->is_view_only = true;
                $dto->final_status_hint = 'Pending from final approval';
            }

            return $dto;
        });
        // For duplicate contractual records (stdClass from DB query), create DTOs directly without mapper.
        // Mark recommended rows as view-only so no actions appear.
        $dupContDtos = $dupContRows->map(function ($r) use ($deptMap, $dupContRecommended, $dupContCardLabels) {
            $requestedSection = null;
            if (!empty($r->section) && isset($deptMap[$r->section])) {
                $requestedSection = $deptMap[$r->section];
            }
            $idStatus = (int) ($r->id_status ?? 1);
            $status = match ($idStatus) {
                1 => 'Pending',
                2 => 'Approved',
                3 => 'Rejected',
                default => 'Unknown',
            };
            $applyKey = (string) ($r->emp_id_apply ?? '');
            $dto = (object) [
                // Use "c-<applyId>" as base id so view can build c-dup- prefix
                'id' => 'c-' . $r->emp_id_apply,
                'employee_type' => 'Contractual Employee',
                'name' => $r->employee_name ?? '--',
                'designation' => $r->designation_name ?? '--',
                'father_name' => null,
                'id_card_number' => $r->id_card_no,
                'card_type' => $dupContCardLabels[$applyKey] ?? '--',
                'date_of_birth' => $r->employee_dob,
                'blood_group' => $r->blood_group,
                'mobile_number' => $r->mobile_no,
                'telephone_number' => null,
                'id_card_valid_from' => $r->card_valid_from,
                'id_card_valid_upto' => $r->card_valid_to,
                'photo' => $r->id_photo_path,
                'created_at' => isset($r->created_date) ? \Carbon\Carbon::parse($r->created_date) : null,
                'requested_by' => null,
                'requested_section' => $requestedSection,
                'request_type' => 'duplicate',
                'status' => $status,
            ];
            if ($idStatus === 1 && in_array($r->emp_id_apply, $dupContRecommended, true)) {
                $dto->is_view_only = true;
                $dto->final_status_hint = 'Pending from final approval';
                // keep status as Pending but view-only
                $dto->status = 'Pending';
            }
            return $dto;
        });

        // At Approval II level, permanent regular and permanent duplicate (actionable), plus contractual regular and duplicate (view-only)
        $merged = $permDtos->concat($dupPermDtos)->concat($contDtos)->concat($dupContDtos)->sortByDesc(function ($d) {
            return $d->created_at ? (\Carbon\Carbon::parse($d->created_at)->timestamp ?? 0) : 0;
        })->values();

        // New Request: actionable pending rows (not view-only)
        $newRows = $merged->filter(function ($r) {
            return (string) ($r->status ?? 'Pending') === 'Pending'
                && !((bool) ($r->is_view_only ?? false));
        })->values();

        // For Approval: view-only rows and non-pending rows except rejected.
        // Generated rows are already excluded by query-level whereNull(id_card_generate_date).
        $forApprovalRows = $merged->filter(function ($r) {
            $status = (string) ($r->status ?? 'Pending');
            if ($status === 'Rejected') {
                return false;
            }
            return ((bool) ($r->is_view_only ?? false))
                || $status !== 'Pending';
        })->values();

        $newRequests = new LengthAwarePaginator(
            $newRows->forPage($newPage, $perPage)->values(),
            $newRows->count(),
            $perPage,
            $newPage,
            ['path' => $request->url(), 'pageName' => 'new_page', 'query' => $request->query()]
        );
        $forApprovalRequests = new LengthAwarePaginator(
            $forApprovalRows->forPage($forApprovalPage, $perPage)->values(),
            $forApprovalRows->count(),
            $perPage,
            $forApprovalPage,
            ['path' => $request->url(), 'pageName' => 'for_page', 'query' => $request->query()]
        );

        // ── Archive Tab ────────────────────────────────────────────────────────
        // Includes: rejected records (id_status=3) + moved-to-archive records (id_card_generate_date IS NOT NULL)

        // Archive – Permanent Regular
        $archivePermQuery = SecurityParmIdApply::with(['employee.designation', 'employee.department', 'creator.department', 'approvals.approver'])
            ->orderBy('created_date', 'desc')
            ->where(function ($q) {
                $q->where('id_status', 3);
                if (Schema::hasColumn('security_parm_id_apply', 'id_card_generate_date')) {
                    $q->orWhereNotNull('id_card_generate_date');
                }
            });
        if ($request->filled('search')) {
            $searchLike = '%' . trim($request->search) . '%';
            $archivePermQuery->where(function ($q) use ($searchLike) {
                $q->whereHas('employee', fn ($eq) => $eq->whereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", [$searchLike]))
                    ->orWhere('id_card_no', 'like', $searchLike);
            });
        }
        if ($request->filled('date_from')) {
            $archivePermQuery->where('created_date', '>=', \Carbon\Carbon::parse($request->date_from)->startOfDay()->toDateTimeString());
        }
        if ($request->filled('date_to')) {
            $archivePermQuery->where('created_date', '<=', \Carbon\Carbon::parse($request->date_to)->endOfDay()->toDateTimeString());
        }
        $archivePermDtos = $archivePermQuery->get()->map(fn ($r) => IdCardSecurityMapper::toEmployeeRequestDto($r));

        // Archive – Permanent Duplicate
        $archiveDupPermQuery = DB::table('security_dup_perm_id_apply as dup')
            ->leftJoin('employee_master as emp', 'dup.employee_master_pk', '=', 'emp.pk')
            ->leftJoin('designation_master as desig', function ($j) {
                $j->on('dup.designation_pk', '=', 'desig.pk')->orOn('emp.designation_master_pk', '=', 'desig.pk');
            })
            ->leftJoin('department_master as dept', 'emp.department_master_pk', '=', 'dept.pk')
            ->where(function ($q) {
                $q->where('dup.id_status', 3);
                if (Schema::hasColumn('security_dup_perm_id_apply', 'id_card_generate_date')) {
                    $q->orWhereNotNull('dup.id_card_generate_date');
                }
            })
            ->orderByDesc('dup.created_date')
            ->select(['dup.*', 'emp.first_name', 'emp.last_name', 'desig.designation_name', 'dept.department_name']);
        if ($request->filled('search')) {
            $searchLike = '%' . trim($request->search) . '%';
            $archiveDupPermQuery->where(function ($q) use ($searchLike) {
                $q->where('dup.id_card_no', 'like', $searchLike)
                    ->orWhereRaw("CONCAT(COALESCE(emp.first_name, ''), ' ', COALESCE(emp.last_name, '')) LIKE ?", [$searchLike]);
            });
        }
        if ($request->filled('date_from')) {
            $archiveDupPermQuery->where('dup.created_date', '>=', \Carbon\Carbon::parse($request->date_from)->startOfDay()->toDateTimeString());
        }
        if ($request->filled('date_to')) {
            $archiveDupPermQuery->where('dup.created_date', '<=', \Carbon\Carbon::parse($request->date_to)->endOfDay()->toDateTimeString());
        }
        $archiveDupPermRows = $archiveDupPermQuery->get();
        $archiveDupPermCardLabels = $this->mapDupPermIdCardTypeLabels($archiveDupPermRows);
        $archiveDupPermDtos = $archiveDupPermRows->map(function ($r) use ($archiveDupPermCardLabels) {
            $fullName = trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? '')) ?: ($r->employee_name ?? '--');
            $applyKey = (string) ($r->emp_id_apply ?? '');
            $idStatus = (int) ($r->id_status ?? 1);
            return (object) [
                'id'                => $r->emp_id_apply,
                'employee_type'     => 'Permanent Employee',
                'name'              => $fullName,
                'designation'       => $r->designation_name ?? null,
                'father_name'       => null,
                'id_card_number'    => $r->id_card_no,
                'card_type'         => trim((string) ($archiveDupPermCardLabels[$applyKey] ?? '--')) ?: '--',
                'date_of_birth'     => $r->employee_dob,
                'blood_group'       => $r->blood_group,
                'mobile_number'     => $r->mobile_no,
                'telephone_number'  => null,
                'id_card_valid_from' => $r->card_valid_from,
                'id_card_valid_upto' => $r->card_valid_to,
                'photo'             => $r->id_photo_path,
                'created_at'        => isset($r->created_date) ? \Carbon\Carbon::parse($r->created_date) : null,
                'requested_by'      => $fullName,
                'requested_section' => $r->department_name ?? null,
                'request_type'      => 'duplicate',
                'status'            => match ($idStatus) { 1 => 'Pending', 2 => 'Approved', 3 => 'Rejected', default => 'Unknown' },
            ];
        });

        // Archive – Contractual Regular (only those that reached Approval 2)
        $archiveContQuery = DB::table('security_con_oth_id_apply')
            ->where('depart_approval_status', 2)
            ->where(function ($q) {
                $q->where('id_status', 3);
                if (Schema::hasColumn('security_con_oth_id_apply', 'id_card_generate_date')) {
                    $q->orWhereNotNull('id_card_generate_date');
                }
            })
            ->orderByDesc('created_date');
        if ($request->filled('search')) {
            $search = $request->search;
            $archiveContQuery->where(function ($q) use ($search) {
                $q->where('employee_name', 'like', '%' . $search . '%')->orWhere('id_card_no', 'like', '%' . $search . '%');
            });
        }
        if ($request->filled('date_from')) {
            $archiveContQuery->where('created_date', '>=', \Carbon\Carbon::parse($request->date_from)->startOfDay()->toDateTimeString());
        }
        if ($request->filled('date_to')) {
            $archiveContQuery->where('created_date', '<=', \Carbon\Carbon::parse($request->date_to)->endOfDay()->toDateTimeString());
        }
        $archiveContDtos = $archiveContQuery->get()->map(fn ($r) => IdCardSecurityMapper::toContractualRequestDto($r));

        // Archive – Contractual Duplicate (only those that reached Approval 2)
        $archiveDupContQuery = DB::table('security_dup_other_id_apply')
            ->where('depart_approval_status', 2)
            ->where(function ($q) {
                $q->where('id_status', 3);
                if (Schema::hasColumn('security_dup_other_id_apply', 'id_card_generate_date')) {
                    $q->orWhereNotNull('id_card_generate_date');
                }
            })
            ->orderByDesc('created_date');
        if ($request->filled('search')) {
            $search = $request->search;
            $archiveDupContQuery->where(function ($q) use ($search) {
                $q->where('employee_name', 'like', '%' . $search . '%')->orWhere('id_card_no', 'like', '%' . $search . '%');
            });
        }
        if ($request->filled('date_from')) {
            $archiveDupContQuery->where('created_date', '>=', \Carbon\Carbon::parse($request->date_from)->startOfDay()->toDateTimeString());
        }
        if ($request->filled('date_to')) {
            $archiveDupContQuery->where('created_date', '<=', \Carbon\Carbon::parse($request->date_to)->endOfDay()->toDateTimeString());
        }
        $archiveDupContRows = $archiveDupContQuery->get();
        $archiveDupContCardLabels = $this->mapDupOtherIdCardTypeLabels($archiveDupContRows);
        $archiveDupContDtos = $archiveDupContRows->map(function ($r) use ($deptMap, $archiveDupContCardLabels) {
            $requestedSection = !empty($r->section) ? ($deptMap[$r->section] ?? null) : null;
            $idStatus = (int) ($r->id_status ?? 1);
            return (object) [
                'id'                => 'c-' . $r->emp_id_apply,
                'employee_type'     => 'Contractual Employee',
                'name'              => $r->employee_name ?? '--',
                'designation'       => $r->designation_name ?? '--',
                'father_name'       => null,
                'id_card_number'    => $r->id_card_no,
                'card_type'         => $archiveDupContCardLabels[(string) ($r->emp_id_apply ?? '')] ?? '--',
                'date_of_birth'     => $r->employee_dob,
                'blood_group'       => $r->blood_group,
                'mobile_number'     => $r->mobile_no,
                'telephone_number'  => null,
                'id_card_valid_from' => $r->card_valid_from,
                'id_card_valid_upto' => $r->card_valid_to,
                'photo'             => $r->id_photo_path,
                'created_at'        => isset($r->created_date) ? \Carbon\Carbon::parse($r->created_date) : null,
                'requested_by'      => null,
                'requested_section' => $requestedSection,
                'request_type'      => 'duplicate',
                'status'            => match ($idStatus) { 1 => 'Pending', 2 => 'Approved', 3 => 'Rejected', default => 'Unknown' },
            ];
        });

        $archiveMerged = $archivePermDtos->concat($archiveDupPermDtos)->concat($archiveContDtos)->concat($archiveDupContDtos)
            ->sortByDesc(fn ($d) => $d->created_at ? (\Carbon\Carbon::parse($d->created_at)->timestamp ?? 0) : 0)
            ->values();

        $archiveRequests = new LengthAwarePaginator(
            $archiveMerged->forPage($archivePage, $perPage)->values(),
            $archiveMerged->count(),
            $perPage,
            $archivePage,
            ['path' => $request->url(), 'pageName' => 'archive_page', 'query' => $request->query()]
        );
        // ──────────────────────────────────────────────────────────────────────

        $activeTab = $request->get('tab', 'new');
        if (!in_array($activeTab, ['new', 'for_approval', 'archive'], true)) {
            $activeTab = 'new';
        }

        $cardTypes = DB::table('sec_id_cardno_master')->orderBy('sec_card_name')->pluck('sec_card_name', 'pk')->toArray();

        return view('admin.security.employee_idcard_approval.approval2', compact(
            'newRequests',
            'forApprovalRequests',
            'archiveRequests',
            'activeTab',
            'cardTypes'
        ));
    }

    /**
     * Mark a final-approved request as generated/archived from Approval II list.
     * Sets id_card_generate_date = now() on the underlying table row.
     */
    public function markGenerated(Request $request, $id)
    {
        try {
            $decoded = decrypt($id);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Invalid request id.');
        }

        $now = now()->format('Y-m-d H:i:s');

        if (is_string($decoded) && str_starts_with($decoded, 'c-dup-')) {
            $applyId = substr($decoded, 6);
            DB::table('security_dup_other_id_apply')
                ->where('emp_id_apply', $applyId)
                ->update(['id_card_generate_date' => $now]);
            return redirect()->back()->with('success', 'Record moved to archive successfully.');
        }

        if (is_string($decoded) && str_starts_with($decoded, 'p-dup-')) {
            $applyId = substr($decoded, 6);
            DB::table('security_dup_perm_id_apply')
                ->where('emp_id_apply', $applyId)
                ->update(['id_card_generate_date' => $now]);
            return redirect()->back()->with('success', 'Record moved to archive successfully.');
        }

        if (is_string($decoded) && str_starts_with($decoded, 'c-')) {
            $pk = (int) substr($decoded, 2);
            DB::table('security_con_oth_id_apply')
                ->where('pk', $pk)
                ->update(['id_card_generate_date' => $now]);
            return redirect()->back()->with('success', 'Record moved to archive successfully.');
        }

        // Permanent regular (emp_id_apply)
        DB::table('security_parm_id_apply')
            ->where('emp_id_apply', $decoded)
            ->update(['id_card_generate_date' => $now]);

        return redirect()->back()->with('success', 'Record moved to archive successfully.');
    }

    /**
     * Approval III: Final approval + history view.
     * Shows Pending/Approved/Rejected records that have reached Level 3 flow.
     */
    public function approval3(Request $request)
    {
        $search = trim($request->get('search', ''));
        $searchLike = $search !== '' ? '%' . $search . '%' : null;
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $dateFromDt = empty($dateFrom) ? null : \Carbon\Carbon::parse($dateFrom)->startOfDay()->toDateTimeString();
        $dateToDt = empty($dateTo) ? null : \Carbon\Carbon::parse($dateTo)->endOfDay()->toDateTimeString();
        
        // Status filter: pending=1, approved=2, rejected=3
        $statusFilter = trim($request->get('status', ''));
        $statusIds = [1, 2, 3]; // default: all statuses
        if ($statusFilter === 'pending') {
            $statusIds = [1];
        } elseif ($statusFilter === 'approved') {
            $statusIds = [2];
        } elseif ($statusFilter === 'rejected') {
            $statusIds = [3];
        }

        $hasA2 = SecurityParmIdApplyApproval::select('security_parm_id_apply_pk')
            ->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_2);

        $permQuery = SecurityParmIdApply::with(['employee.designation', 'employee.department', 'creator.department', 'approvals.approver'])
            ->whereIn('id_status', $statusIds)
            ->whereIn('emp_id_apply', $hasA2)
            ->orderBy('created_date', 'desc');

        if ($searchLike) {
            $permQuery->where(function ($q) use ($searchLike) {
                $q->whereHas('employee', function ($eq) use ($searchLike) {
                    $eq->whereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", [$searchLike]);
                })->orWhere('id_card_no', 'like', $searchLike);
            });
        }
        if ($dateFromDt) {
            $permQuery->where('created_date', '>=', $dateFromDt);
        }
        if ($dateToDt) {
            $permQuery->where('created_date', '<=', $dateToDt);
        }

        $permRows = $permQuery->get();
        $permDtos = $permRows->map(fn ($r) => IdCardSecurityMapper::toEmployeeRequestDto($r));

        // Permanent Duplicate ready for final approval:
        // Approval II sets recommend_status=1 (status=1) and leaves main id_status=1 until final approval.
        $dupPermRecommended = DB::table('security_dup_perm_id_apply_approval')
            ->where('status', 1)
            ->where('recommend_status', 1)
            ->pluck('security_parm_id_apply_pk');
        $dupPermQuery = DB::table('security_dup_perm_id_apply as dup')
            ->leftJoin('employee_master as emp', 'dup.employee_master_pk', '=', 'emp.pk')
            ->leftJoin('designation_master as desig', function ($j) {
                $j->on('dup.designation_pk', '=', 'desig.pk')
                    ->orOn('emp.designation_master_pk', '=', 'desig.pk');
            })
            ->leftJoin('department_master as dept', 'emp.department_master_pk', '=', 'dept.pk')
            ->whereIn('dup.id_status', $statusIds);

        if ($dupPermRecommended->isNotEmpty()) {
            $dupPermQuery->whereIn('dup.emp_id_apply', $dupPermRecommended);
        } else {
            $dupPermQuery->whereRaw('0 = 1');
        }

        if ($searchLike) {
            $dupPermQuery->where(function ($q) use ($searchLike) {
                $q->where('dup.id_card_no', 'like', $searchLike)
                    ->orWhereRaw("CONCAT(COALESCE(emp.first_name, ''), ' ', COALESCE(emp.last_name, '')) LIKE ?", [$searchLike]);
            });
        }
        if ($dateFromDt) {
            $dupPermQuery->where('dup.created_date', '>=', $dateFromDt);
        }
        if ($dateToDt) {
            $dupPermQuery->where('dup.created_date', '<=', $dateToDt);
        }

        $dupPermRows = $dupPermQuery->orderByDesc('dup.created_date')->select([
            'dup.*',
            'emp.first_name',
            'emp.last_name',
            'desig.designation_name',
            'dept.department_name',
        ])->get();
        $dupPermCardLabels = $this->mapDupPermIdCardTypeLabels($dupPermRows);

        $dupPermDtos = $dupPermRows->map(function ($r) use ($dupPermCardLabels) {
            $fullName = trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? ''));
            if ($fullName === '') {
                $fullName = $r->employee_name ?? '--';
            }
            $status = match ((int) ($r->id_status ?? 0)) {
                1 => 'Pending',
                2 => 'Approved',
                3 => 'Rejected',
                default => 'Unknown',
            };
            $applyKey = (string) ($r->emp_id_apply ?? '');
            return (object) [
                // keep base id as emp_id_apply; _approval_table will add p-dup- prefix for duplicates
                'id' => $r->emp_id_apply,
                'employee_type' => 'Permanent Employee',
                'name' => $fullName,
                'designation' => $r->designation_name ?? null,
                'father_name' => null,
                'id_card_number' => $r->id_card_no,
                'card_type' => trim((string) ($dupPermCardLabels[$applyKey] ?? '--')) ?: '--',
                'date_of_birth' => $r->employee_dob,
                'blood_group' => $r->blood_group,
                'mobile_number' => $r->mobile_no,
                'telephone_number' => null,
                'id_card_valid_from' => $r->card_valid_from,
                'id_card_valid_upto' => $r->card_valid_to,
                'photo' => $r->id_photo_path,
                'created_at' => isset($r->created_date) ? \Carbon\Carbon::parse($r->created_date) : null,
                'requested_by' => $fullName,
                'requested_section' => $r->department_name ?? null,
                'request_type' => 'duplicate',
                'status' => $status,
            ];
        });

        // Contractual Regular ready for final approval (has A2, still id_status pending)
        $contHasA2 = DB::table('security_con_oth_id_apply_approval')
            ->where('status', 1)
            ->where('recommend_status', 1)
            ->pluck('security_parm_id_apply_pk');

        $contQuery = DB::table('security_con_oth_id_apply')
            ->whereIn('id_status', $statusIds);
        if ($contHasA2->isNotEmpty()) {
            $contQuery->whereIn('emp_id_apply', $contHasA2);
        } else {
            $contQuery->whereRaw('0 = 1');
        }

        if ($searchLike) {
            $contQuery->where(function ($q) use ($searchLike) {
                $q->where('employee_name', 'like', $searchLike)
                    ->orWhere('id_card_no', 'like', $searchLike);
            });
        }
        if ($dateFromDt) {
            $contQuery->where('created_date', '>=', $dateFromDt);
        }
        if ($dateToDt) {
            $contQuery->where('created_date', '<=', $dateToDt);
        }
        $contQuery->orderByDesc('created_date');
        $contRows = $contQuery->get();
        $contDtos = $contRows->map(fn ($r) => IdCardSecurityMapper::toContractualRequestDto($r));

        // Contractual Duplicate ready for final approval (recommended at Approval II, still id_status pending)
        $dupContRecommended = DB::table('security_dup_other_id_apply_approval')
            ->where('status', 1)
            ->where('recommend_status', 1)
            ->pluck('security_con_id_apply_pk');
        $dupContQuery = DB::table('security_dup_other_id_apply')
            ->whereIn('id_status', $statusIds)
            ->where('depart_approval_status', 2);
        if ($dupContRecommended->isNotEmpty()) {
            $dupContQuery->whereIn('emp_id_apply', $dupContRecommended);
        } else {
            $dupContQuery->whereRaw('0 = 1');
        }
        if ($searchLike) {
            $dupContQuery->where(function ($q) use ($searchLike) {
                $q->where('employee_name', 'like', $searchLike)
                    ->orWhere('id_card_no', 'like', $searchLike);
            });
        }
        if ($dateFromDt) {
            $dupContQuery->where('created_date', '>=', $dateFromDt);
        }
        if ($dateToDt) {
            $dupContQuery->where('created_date', '<=', $dateToDt);
        }
        $dupContRows = $dupContQuery->orderByDesc('created_date')->get();
        $dupContCardLabels = $this->mapDupOtherIdCardTypeLabels($dupContRows);
        $deptMap = DB::table('department_master')->pluck('department_name', 'pk')->toArray();
        $dupContDtos = $dupContRows->map(function ($r) use ($deptMap, $dupContCardLabels) {
            $requestedSection = null;
            if (!empty($r->section) && isset($deptMap[$r->section])) {
                $requestedSection = $deptMap[$r->section];
            }
            $status = match ((int) ($r->id_status ?? 0)) {
                1 => 'Pending',
                2 => 'Approved',
                3 => 'Rejected',
                default => 'Unknown',
            };
            $applyKey = (string) ($r->emp_id_apply ?? '');
            return (object) [
                // base id is "c-<applyId>" so _approval_table can build "c-dup-<applyId>"
                'id' => 'c-' . $r->emp_id_apply,
                'employee_type' => 'Contractual Employee',
                'name' => $r->employee_name ?? '--',
                'designation' => $r->designation_name ?? '--',
                'father_name' => $r->father_name ?? null,
                'id_card_number' => $r->id_card_no,
                'card_type' => $dupContCardLabels[$applyKey] ?? '--',
                'date_of_birth' => $r->employee_dob,
                'blood_group' => $r->blood_group,
                'mobile_number' => $r->mobile_no,
                'telephone_number' => null,
                'id_card_valid_from' => $r->card_valid_from,
                'id_card_valid_upto' => $r->card_valid_to,
                'photo' => $r->id_photo_path,
                'created_at' => isset($r->created_date) ? \Carbon\Carbon::parse($r->created_date) : null,
                'requested_by' => null,
                'requested_section' => $requestedSection,
                'request_type' => 'duplicate',
                'status' => $status,
            ];
        });

        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
        $page = (int) $request->get('page', 1);

        $merged = $permDtos->concat($contDtos)->sortByDesc(function ($dto) {
            return $dto->created_at ? $dto->created_at->timestamp : 0;
        })->values();

        // Include permanent duplicate pending final approval as well
        $merged = $merged->concat($dupPermDtos)->sortByDesc(function ($dto) {
            return $dto->created_at ? $dto->created_at->timestamp : 0;
        })->values();

        // Include contractual duplicate pending final approval as well
        $merged = $merged->concat($dupContDtos)->sortByDesc(function ($dto) {
            return $dto->created_at ? $dto->created_at->timestamp : 0;
        })->values();

        $requests = new LengthAwarePaginator(
            $merged->forPage($page, $perPage),
            $merged->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.security.employee_idcard_approval.approval3', compact('requests', 'statusFilter'));
    }

    public function show(Request $httpRequest, $id)
    {
        try {
            $decrypted = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $stage = (int) $httpRequest->get('stage', 1);
        if (!in_array($stage, [1, 2, 3], true)) {
            $stage = 1;
        }
        $canApprove = true;

        if (is_string($decrypted) && str_starts_with($decrypted, 'c-dup-')) {
            $applyId = substr($decrypted, 6);
            $row = DB::table('security_dup_other_id_apply')->where('emp_id_apply', $applyId)->first();
            if (!$row) {
                abort(404);
            }

            $hasA1 = DB::table('security_dup_other_id_apply_approval')
                ->where('security_con_id_apply_pk', $row->emp_id_apply)
                ->where('status', 1)
                ->exists();
            $hasRecommended = DB::table('security_dup_other_id_apply_approval')
                ->where('security_con_id_apply_pk', $row->emp_id_apply)
                ->where('status', 1)
                ->where('recommend_status', 1)
                ->exists();
            $hasA2 = DB::table('security_dup_other_id_apply_approval')
                ->where('security_con_id_apply_pk', $row->emp_id_apply)
                ->where('status', 2)
                ->exists();
            $hasRej = DB::table('security_dup_other_id_apply_approval')
                ->where('security_con_id_apply_pk', $row->emp_id_apply)
                ->where('status', 3)
                ->exists();

            // Stage 1 allowed only if not yet A1/recommended/final/rejected.
            // Stage 2 allowed only if A1 done and not yet recommended/final/rejected.
            // Stage 3 allowed only if recommended and not yet final/rejected.
            if ($stage === 1) {
                $canApprove = !$hasA1 && !$hasRecommended && !$hasA2 && !$hasRej && (int) $row->id_status === 1;
            } elseif ($stage === 2) {
                $canApprove = $hasA1 && !$hasRecommended && !$hasA2 && !$hasRej && (int) $row->id_status === 1;
            } elseif ($stage === 3) {
                $canApprove = $hasRecommended && !$hasA2 && !$hasRej && (int) $row->id_status === 1;
            } else {
                $canApprove = false;
            }

            $request = (object) [
                'id' => 'c-dup-' . $row->emp_id_apply,
                'name' => $row->employee_name ?? '--',
                'designation' => $row->designation_name ?? '--',
                'father_name' => null,
                'id_card_number' => $row->id_card_no,
                'date_of_birth' => $row->employee_dob,
                'blood_group' => $row->blood_group,
                'mobile_number' => $row->mobile_no,
                'photo' => $row->id_photo_path,
                'created_at' => $row->created_date ? \Carbon\Carbon::parse($row->created_date) : null,
                'id_card_valid_from' => $row->card_valid_from,
                'id_card_valid_upto' => $row->card_valid_to,
                'request_type' => 'duplicate',
                'card_type' => $row->card_type ?? 'Contractual',
                'request_for' => 'Duplication',
                'requested_by' => $row->employee_name ?? '--',
                'requested_section' => null,
                'card_reason' => $row->card_reason ?? null,
                // Reason/document-specific fields for display
                'fir_receipt' => $row->fir_doc ?? null,
                'payment_receipt' => $row->payment_receipt ?? null,
                'service_ext' => $row->service_ext ?? null,
                'id_proof_doc' => $row->aadhar_doc ?? null,
                'other_documents' => $row->doc_path ?? null,
                'status' => (int) $row->id_status === 1 ? 'Pending' : ((int) $row->id_status === 2 ? 'Approved' : 'Rejected'),
                'approver1' => null,
                'approver2' => null,
                'approved_by_a1' => null,
                'approved_by_a2' => null,
                'approved_by_a1_at' => null,
                'approved_by_a2_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
                'rejectedByUser' => null,
            ];
        } elseif (is_string($decrypted) && str_starts_with($decrypted, 'p-dup-')) {
            $applyId = substr($decrypted, 6);
            // Join employee_master / designation / department so that detail view
            // shows latest master data (name, father, designation, section, etc.).
            $row = DB::table('security_dup_perm_id_apply as dup')
                ->leftJoin('employee_master as emp', function ($j) {
                    $j->on('dup.employee_master_pk', '=', 'emp.pk')
                        ->orOn('dup.employee_master_pk', '=', 'emp.pk_old');
                })
                ->leftJoin('designation_master as desig', 'dup.designation_pk', '=', 'desig.pk')
                ->leftJoin('department_master as dept', 'emp.department_master_pk', '=', 'dept.pk')
                ->where('dup.emp_id_apply', $applyId)
                ->select([
                    'dup.*',
                    'emp.first_name as emp_first_name',
                    'emp.last_name as emp_last_name',
                    'emp.father_name as emp_father_name',
                    'emp.dob as emp_dob',
                    'emp.doj as emp_doj',
                    'emp.mobile as emp_mobile',
                    'emp.landline_contact_no as emp_landline',
                    'desig.designation_name',
                    'dept.department_name',
                ])
                ->first();
            if (!$row) {
                abort(404);
            }

            $hasRecommended = DB::table('security_dup_perm_id_apply_approval')
                ->where('security_parm_id_apply_pk', $row->emp_id_apply)
                ->where('status', 1)
                ->where('recommend_status', 1)
                ->exists();
            $hasFinal = DB::table('security_dup_perm_id_apply_approval')
                ->where('security_parm_id_apply_pk', $row->emp_id_apply)
                ->where('status', 2)
                ->exists();
            $hasRej = DB::table('security_dup_perm_id_apply_approval')
                ->where('security_parm_id_apply_pk', $row->emp_id_apply)
                ->where('status', 3)
                ->exists();

            if ($stage === 2) {
                // Stage 2 (Security Card) should not allow approve again once recommended
                $canApprove = !$hasRecommended && !$hasFinal && !$hasRej && (int) $row->id_status === 1;
            } elseif ($stage === 3) {
                // Stage 3 (Admin Security) allowed only if recommended and not final/rejected
                $canApprove = $hasRecommended && !$hasFinal && !$hasRej && (int) $row->id_status === 1;
            } else {
                $canApprove = false;
            }

            $fullName = trim(($row->emp_first_name ?? '') . ' ' . ($row->emp_last_name ?? ''));
            if ($fullName === '') {
                $fullName = $row->employee_name ?? '--';
            }
            $fatherName = $row->emp_father_name ?? null;

            $mobile = null;
            if (!empty($row->emp_mobile)) {
                $mobile = (string) $row->emp_mobile;
            } elseif (!empty($row->mobile_no)) {
                $mobile = $row->mobile_no;
            }
            $telephone = !empty($row->emp_landline) ? (string) $row->emp_landline : null;

            $dob = $row->employee_dob ?? $row->emp_dob ?? null;
            $academyJoiningYmd = null;
            if (! empty($row->emp_doj)) {
                try {
                    $academyJoiningYmd = \Carbon\Carbon::parse($row->emp_doj)->format('Y-m-d');
                } catch (\Exception $e) {
                    $academyJoiningYmd = null;
                }
            }

            $request = (object) [
                'id' => 'p-dup-' . $row->emp_id_apply,
                'name' => $fullName,
                'designation' => $row->designation_name ?? null,
                'father_name' => $fatherName,
                'id_card_number' => $row->id_card_no,
                'date_of_birth' => $dob,
                'academy_joining' => $academyJoiningYmd,
                'blood_group' => $row->blood_group,
                'mobile_number' => $mobile,
                'telephone_number' => $telephone,
                'photo' => $row->id_photo_path,
                'created_at' => $row->created_date ? \Carbon\Carbon::parse($row->created_date) : null,
                'id_card_valid_from' => $row->card_valid_from,
                'id_card_valid_upto' => $row->card_valid_to,
                'request_type' => 'duplicate',
                'card_type' => 'Permanent',
                'request_for' => 'Duplication',
                'requested_by' => $fullName,
                'requested_section' => $row->department_name ?? null,
                'card_reason' => $row->card_reason ?? null,
                // Reason/document-specific fields for display
                'fir_receipt' => $row->fir_doc ?? null,
                'payment_receipt' => $row->payment_receipt ?? null,
                'service_ext' => $row->service_ext ?? null,
                'id_proof_doc' => null,
                'other_documents' => null,
                'status' => (int) $row->id_status === 1 ? 'Pending' : ((int) $row->id_status === 2 ? 'Approved' : 'Rejected'),
                'approver1' => null,
                'approver2' => null,
                'approved_by_a1' => null,
                'approved_by_a2' => null,
                'approved_by_a1_at' => null,
                'approved_by_a2_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
                'rejectedByUser' => null,
            ];
        } elseif (is_string($decrypted) && str_starts_with($decrypted, 'c-')) {
            $pk = (int) substr($decrypted, 2);
            $row = DB::table('security_con_oth_id_apply')->where('pk', $pk)->first();
            if (!$row) {
                abort(404);
            }
            $request = IdCardSecurityMapper::toContractualRequestDto($row);

            // Contractual regular requests: compute actionable state per stage.
            $approvals = DB::table('security_con_oth_id_apply_approval')
                ->where('security_parm_id_apply_pk', $row->emp_id_apply)
                ->get();
            $hasA1 = $approvals->where('status', 1)->isNotEmpty();
            $hasRecommended = $approvals->where('status', 1)->where('recommend_status', 1)->isNotEmpty();
            $hasFinal = $approvals->where('status', 2)->isNotEmpty();
            $hasRej = $approvals->where('status', 3)->isNotEmpty();
            $hasPending = $approvals->where('status', 0)->isNotEmpty();
            $sectionHeadDone = (int) ($row->depart_approval_status ?? 0) === 2;

            if ($stage === 1) {
                $canApprove = ! $sectionHeadDone && ! $hasA1 && ! $hasRecommended && ! $hasFinal && ! $hasRej && (int) $row->id_status === 1;
            } elseif ($stage === 2) {
                $canApprove = $hasPending && !$hasRecommended && !$hasFinal && !$hasRej && (int) $row->id_status === 1;
            } elseif ($stage === 3) {
                $canApprove = $hasPending && $hasRecommended && !$hasFinal && !$hasRej && (int) $row->id_status === 1;
            } else {
                $canApprove = false;
            }
        } else {
            $row = SecurityParmIdApply::with(['employee.designation', 'employee.department', 'creator.department', 'approvals.approver'])
                ->findOrFail($decrypted);
            $request = IdCardSecurityMapper::toEmployeeRequestDto($row);

            // Permanent regular requests:
            // - At stage 2 (Approval II), action is allowed only if not yet approved at level-2 and not rejected.
            // - At stage 3 (Approval III), action is allowed only if already approved at level-2 and not rejected.
            // Note: main id_status stays Pending until final approval at stage 3.
            $approvals = SecurityParmIdApplyApproval::where('security_parm_id_apply_pk', $row->emp_id_apply)->get();
            $hasA1 = $approvals->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_1)->isNotEmpty();
            $hasA2 = $approvals->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_2)->isNotEmpty();
            $hasRej = $approvals->where('status', SecurityParmIdApplyApproval::STATUS_REJECTED)->isNotEmpty();
            if ($stage === 1) {
                $canApprove = !$hasA1 && !$hasA2 && !$hasRej && (int) $row->id_status === (int) SecurityParmIdApply::ID_STATUS_PENDING;
            } elseif ($stage === 2) {
                $canApprove = !$hasA2 && !$hasRej && (int) $row->id_status === (int) SecurityParmIdApply::ID_STATUS_PENDING;
            } elseif ($stage === 3) {
                $canApprove = $hasA2 && !$hasRej && (int) $row->id_status === (int) SecurityParmIdApply::ID_STATUS_PENDING;
            } else {
                $canApprove = false;
            }
        }
        return view('admin.security.employee_idcard_approval.show', compact('request', 'canApprove', 'stage'));
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

        $validated = $request->validate([
            'approval_remarks' => ['nullable', 'string', 'max:1000'],
        ]);
        $remarks = $validated['approval_remarks'] ?? null;

        // Contractual Duplicate ID Card request (c-dup- prefix) - Approval 1 only for contractual
        if (is_string($decrypted) && str_starts_with($decrypted, 'c-dup-')) {
            $applyId = substr($decrypted, 6);
            $row = DB::table('security_dup_other_id_apply')->where('emp_id_apply', $applyId)->first();
            if (!$row || (int) $row->id_status !== 1) {
                return redirect()->back()->with('error', 'This request is not pending your approval.');
            }
            if ((int) $row->department_approval_emp_pk !== (int) $employeePk) {
                return redirect()->back()->with('error', 'Only the designated Approval Authority can approve this request.');
            }
            $hasA1 = DB::table('security_dup_other_id_apply_approval')
                ->where('security_con_id_apply_pk', $row->emp_id_apply)
                ->where('status', 1)
                ->exists();
            if ($hasA1) {
                return redirect()->back()->with('error', 'This request has already been approved at Level 1.');
            }

            // Section Head (Level 1) approval for Contractual Duplicate:
            //  - Mark department approval as completed on main table
            //  - Insert Level 1 row in approval table (status = 1)
            DB::table('security_dup_other_id_apply')
                ->where('emp_id_apply', $applyId)
                ->update([
                    'depart_approval_status' => 2,
                    'depart_approval_date' => now()->format('Y-m-d H:i:s'),
                ]);

            DB::table('security_dup_other_id_apply_approval')->insert([
                'security_con_id_apply_pk' => $row->emp_id_apply,
                'status' => 1,
                'approval_remarks' => $remarks,
                'approval_emp_pk' => $employeePk,
                'created_by' => $employeePk,
                'created_date' => now()->format('Y-m-d H:i:s'),
                'modified_by' => $employeePk,
                'modified_date' => now()->format('Y-m-d H:i:s'),
            ]);

            return redirect()->route('admin.security.employee_idcard_approval.approval1')
                ->with('success', 'Contractual duplicate ID Card request approved at Level 1 and forwarded to Security for further approval.');
         }

        // Contractual Regular ID Card request (c- prefix, not c-dup-)
        if (is_string($decrypted) && str_starts_with($decrypted, 'c-')) {
            $pk = (int) substr($decrypted, 2);
            $row = DB::table('security_con_oth_id_apply')->where('pk', $pk)->first();
            if (!$row || (int) $row->id_status !== 1) {
                return redirect()->back()->with('error', 'This request is not pending your approval.');
            }
            if ((int) $row->department_approval_emp_pk !== (int) $employeePk) {
                return redirect()->back()->with('error', 'Only the designated Approval Authority can approve this request.');
            }
            // Section head ke level par approval table me koi change nahi hona chahiye,
            // sirf departmental status/remarks/date update honge.
            // Level-1 (section head) complete is stored on the main row; approval table gets status 0 for Security.
            $sectionHeadDone = (int) ($row->depart_approval_status ?? 0) === 2;
            $legacyA1 = DB::table('security_con_oth_id_apply_approval')
                ->where('security_parm_id_apply_pk', $row->emp_id_apply)
                ->where('status', 1)
                ->exists();
            if ($sectionHeadDone || $legacyA1) {
                return redirect()->back()->with('error', 'This request has already been approved at Level 1.');
            }
            // Mark department approval as completed (Section Head)
            DB::table('security_con_oth_id_apply')
                ->where('pk', $pk)
                ->update([
                    'depart_approval_status' => 2,
                    'depart_approval_date' => now()->format('Y-m-d H:i:s'),
                    'depart_approval_remarks' => $remarks,
                ]);
                DB::table('security_con_oth_id_apply_approval')->insert([
                    'security_parm_id_apply_pk' => $row->emp_id_apply,
                    'status' => 0,
                    'approval_remarks' => '',
                    
                    'created_by' => $employeePk ?? $authEmpPk,
                    'created_date' => now()->format('Y-m-d H:i:s'),
                    'modified_by' => $employeePk ?? $authEmpPk,
                    'modified_date' => now()->format('Y-m-d H:i:s'),
                ]);
               

            return redirect()->route('admin.security.employee_idcard_approval.approval1')
                ->with('success', 'Contractual ID Card request approved at Level 1 and forwarded to Security for further approval.');
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
        $user = Auth::user();
        $employeePk = $user->user_id ?? $user->pk ?? null;

        // Contractual/Family Duplicate ID Card request (c-dup- prefix) - Approval 2 only; section authority may have no A1 (single-step) or has A1 (insert A2)
        if (is_string($pk) && str_starts_with($pk, 'c-dup-')) {
            $applyId = substr($pk, 6);
            $row = DB::table('security_dup_other_id_apply')->where('emp_id_apply', $applyId)->first();
            if (!$row || (int) $row->id_status !== 1) {
                return redirect()->back()->with('error', 'This request is not pending your approval.');
            }
            // Approval II for contractual duplicates is handled at Security level (no department_approval_emp_pk restriction here).
            $hasA1 = DB::table('security_dup_other_id_apply_approval')->where('security_con_id_apply_pk', $row->emp_id_apply)->where('status', 1)->exists();
           
            $hasA2 = DB::table('security_dup_other_id_apply_approval')->where('security_con_id_apply_pk', $row->emp_id_apply)->where('status', 2)->exists();
            if ($hasA2) {
                return redirect()->back()->with('error', 'This request has already been approved.');
            }
            if (!$hasA1) {
                return redirect()->back()->with('error', 'This request must be approved at Level 1 first.');
            }

            // Level 2 = Recommendation; keep main id_status pending and forward to Approval III.
            $now = now()->format('Y-m-d H:i:s');

            // Mark latest Level-1 row as recommended
            DB::table('security_dup_other_id_apply_approval')
                ->where('security_con_id_apply_pk', $applyId)
                ->where('status', 1)
                ->orderByDesc('pk')
                ->limit(1)
                ->update([
                    'recommend_status' => 1,
                    'modified_by' => $employeePk,
                    'modified_date' => $now,
                ]);

            // Create a pending row for final approver (status = 0)
            DB::table('security_dup_other_id_apply_approval')->insert([
                'security_con_id_apply_pk' => $applyId,
                'status' => 0,
                'approval_remarks' => null,
                'recommend_status' => null,
                'approval_emp_pk' => null,
                'created_by' => $employeePk,
                'created_date' => $now,
                'modified_by' => null,
                'modified_date' => null,
            ]);

            // Optional forwarding fields (if columns exist in DB schema)
            try {
                DB::table('security_dup_other_id_apply')
                    ->where('emp_id_apply', $applyId)
                    ->update([
                        'id_card_forward' => $employeePk,
                        'id_card_forward_status' => 2,
                    ]);
            } catch (\Throwable $e) {
                // ignore if columns don't exist
            }

            return redirect()->route('admin.security.employee_idcard_approval.approval2')
                ->with('success', 'Duplicate ID Card request recommended at Level 2. It will now move to Approval III.');
        }
        // Permanent Duplicate ID Card request (p-dup- prefix) - goes directly to Approval 2 (no A1 check needed)
        // Identifier after "p-dup-" is emp_id_apply (e.g. DUP00001), not numeric pk
        elseif (is_string($pk) && str_starts_with($pk, 'p-dup-')) {
            $applyId = substr($pk, 6);
            $row = DB::table('security_dup_perm_id_apply')->where('emp_id_apply', $applyId)->first();
            if (!$row || (int) $row->id_status !== 1) {
                return redirect()->back()->with('error', 'This request is not pending your approval.');
            }
            $hasA2 = DB::table('security_dup_perm_id_apply_approval')->where('security_parm_id_apply_pk', $row->emp_id_apply)->where('status', 2)->exists();
            if ($hasA2) {
                return redirect()->back()->with('error', 'This request has already been approved.');
            }
            // Permanent duplicate goes directly to Approval 2 (status=2), no A1 prerequisite needed
          
            DB::table('security_dup_perm_id_apply_approval')->where('security_parm_id_apply_pk', $row->emp_id_apply)->where('status', 0)->update([
                'status' => 1,
                'recommend_status' => 1,
                'approval_emp_pk' => $employeePk,
                'modified_by' => $employeePk ?? $authEmpPk,
                'modified_date' => now()->format('Y-m-d H:i:s'),
            ]);
              DB::table('security_dup_perm_id_apply_approval')->insert([
                'security_parm_id_apply_pk' => $row->emp_id_apply,
                'status' => 0,
                'approval_remarks' => null,
                'recommend_status' => null,
                'approval_emp_pk' => $employeePk,
                'created_by' => $employeePk ?? $authEmpPk,
                'created_date' => now()->format('Y-m-d H:i:s'),
                'modified_by' => $employeePk ?? $authEmpPk,
                'modified_date' => now()->format('Y-m-d H:i:s'),
            ]);


            return redirect()->route('admin.security.employee_idcard_approval.approval2')
                ->with('success', 'Duplicate ID Card request approved successfully.');
        }
        // Contractual Regular ID Card request (c- prefix)
        elseif (is_string($pk) && str_starts_with($pk, 'c-')) {
            $contPk = (int) substr($pk, 2);
            $row = DB::table('security_con_oth_id_apply')->where('pk', $contPk)->first();
            if (!$row || (int) $row->id_status !== 1) {
                return redirect()->back()->with('error', 'This request is not pending your approval.');
            }
            $hasA1 = DB::table('security_con_oth_id_apply_approval')->where('security_parm_id_apply_pk', $row->emp_id_apply)->where('status', 0)->exists();
            $hasA2 = DB::table('security_con_oth_id_apply_approval')->where('security_parm_id_apply_pk', $row->emp_id_apply)->where('status', 0)->exists();
            if (!$hasA1) {
                return redirect()->back()->with('error', 'This request is not pending your approval.');
            }

            DB::beginTransaction();
            try {
                // ID card number is generated at final approval (Level 3), not here.

                // 1) Update existing pending row (status=0) -> status=1, recommend_status=1
                DB::table('security_con_oth_id_apply_approval')
                    ->where('security_parm_id_apply_pk', $row->emp_id_apply)
                    ->where('status', 0)
                    ->orderByDesc('pk')
                    ->limit(1)
                    ->update([
                        'status' => 1,
                        'recommend_status' => 1,
                        'modified_by' => $employeePk,
                        'modified_date' => now()->format('Y-m-d H:i:s'),
                    ]);

                // 2) Create new pending row for final approver (status=0, rest mostly null)
                DB::table('security_con_oth_id_apply_approval')->insert([
                    'security_parm_id_apply_pk' => $row->emp_id_apply,
                    'status' => 0,
                    'approval_remarks' => null,
                    'recommend_status' => null,
                    'approval_emp_pk' => null,
                    'created_by' => $employeePk,
                    'created_date' => now()->format('Y-m-d H:i:s'),
                    'modified_by' => null,
                    'modified_date' => null,
                ]);

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Unable to process Level 2 approval. Please try again.');
            }

            // Do not mark id_status approved here; final approval at Level 3 (ID number generated there).
            return redirect()->route('admin.security.employee_idcard_approval.approval2')
                ->with('success', 'Contractual ID Card request approved at Level 2 and forwarded for final approval.');
        }
        // Permanent Regular ID Card request (numeric, no prefix)
        $row = SecurityParmIdApply::with('employee')->findOrFail($pk);
        if ($row->id_status != SecurityParmIdApply::ID_STATUS_PENDING) {
            return redirect()->back()->with('error', 'This request is not pending your approval.');
        }
        $hasA2 = SecurityParmIdApplyApproval::where('security_parm_id_apply_pk', $row->emp_id_apply)->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_2)->exists();
        if ($hasA2) {
            return redirect()->back()->with('error', 'This request is not pending your approval.');
        }
        // Permanent employees don't need Approval 1 - they go directly to Approval 2
        // No prerequisite check needed for permanent employees
        // ID card number is generated at final approval (Level 3), not at Level 2.

        SecurityParmIdApplyApproval::create([
            'security_parm_id_apply_pk' => $row->emp_id_apply,
            'status' => SecurityParmIdApplyApproval::STATUS_APPROVAL_2,
            'approval_emp_pk' => $employeePk,
            'created_by' => $employeePk,
            'created_date' => now()->format('Y-m-d H:i:s'),
            'modified_by' => $employeePk,
            'modified_date' => now()->format('Y-m-d H:i:s'),
        ]);

        // Level 2 = Recommendation for Permanent employees.
        // Create/ensure recommendation row in idcard_request_approvar_master_new with sequence = 0.
        try {
            $existing = DB::table('idcard_request_approvar_master_new')
                ->where('employee_master_pk', $row->employee_master_pk)
                ->where('employees_pk', $employeePk)
                ->where('type', 'employee')
                ->where('sequence', 0)
                ->where('per_status', 1)
                ->first();

            if (!$existing) {
                $nextPk = (int) DB::table('idcard_request_approvar_master_new')->max('pk') + 1;
                DB::table('idcard_request_approvar_master_new')->insert([
                    'pk' => $nextPk,
                    'employee_master_pk' => $row->employee_master_pk,
                    'student_master_pk' => null,
                    'employees_pk' => $employeePk,
                    'type' => 'employee',
                    'sequence' => 0,
                    'is_forwarded' => 1,
                    'cont_status' => 0,
                    'per_status' => 1,
                    'family_status' => 0,
                    'traning_status' => 0,
                    'duplicate_status' => 0,
                    'vec_status' => 0,
                ]);
            }
        } catch (\Throwable $e) {
            // Fail silently for mapping table; core approval should still work.
        }

        // Keep id_status as Pending; final Approval III will mark as Approved.
        $row->save();

        return redirect()->route('admin.security.employee_idcard_approval.approval2')
            ->with('success', 'Request recommended successfully at Level 2. It will now move to Approval III.');
    }

    public function reject1(Request $request, $id)
    {
        return $this->reject($request, $id, 1);
    }

    public function reject2(Request $request, $id)
    {
        return $this->reject($request, $id, 2);
    }

    public function reject3(Request $request, $id)
    {
        return $this->reject($request, $id, 3);
    }

    /**
     * Final approval (Level 3) for Permanent Employee ID Cards.
     * Marks SecurityParmIdApply as Approved and records final approver in idcard_request_approvar_master_new.
     */
    public function approve3(Request $request, $id)
    {
        try {
            $empIdApply = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $user = Auth::user();
        $employeePk = $user->user_id ?? $user->pk ?? null;

        // Contractual Duplicate final approval (c-dup- prefix)
        if (is_string($empIdApply) && str_starts_with($empIdApply, 'c-dup-')) {
            $applyId = substr($empIdApply, 6);
            $row = DB::table('security_dup_other_id_apply')->where('emp_id_apply', $applyId)->first();
            if (!$row || (int) $row->id_status !== 1) {
                return redirect()->back()->with('error', 'This request is not pending your approval.');
            }

            $hasRecommended = DB::table('security_dup_other_id_apply_approval')
                ->where('security_con_id_apply_pk', $applyId)
                ->where('status', 1)
                ->where('recommend_status', 1)
                ->exists();
            if (!$hasRecommended) {
                return redirect()->back()->with('error', 'This request must be recommended at Level 2 first.');
            }

            $hasFinal = DB::table('security_dup_other_id_apply_approval')
                ->where('security_con_id_apply_pk', $applyId)
                ->where('status', 2)
                ->exists();
            if ($hasFinal) {
                return redirect()->back()->with('error', 'This request has already been finally approved.');
            }

            // Update latest pending row (status=0) to final approved (status=2)
            DB::table('security_dup_other_id_apply_approval')
                ->where('security_con_id_apply_pk', $applyId)
                ->where('status', 0)
                ->orderByDesc('pk')
                ->limit(1)
                ->update([
                    'status' => 2,
                    'approval_emp_pk' => $employeePk,
                    'modified_by' => $employeePk,
                    'modified_date' => now()->format('Y-m-d H:i:s'),
                ]);

            DB::table('security_dup_other_id_apply')->where('emp_id_apply', $applyId)->update([
                'id_status' => 2,
            ]);

            return redirect()->route('admin.security.employee_idcard_approval.approval3')
                ->with('success', 'Duplicate ID Card request approved successfully at final level.');
        }

        // Permanent Duplicate final approval (p-dup- prefix)
        if (is_string($empIdApply) && str_starts_with($empIdApply, 'p-dup-')) {
            $applyId = substr($empIdApply, 6);
            $row = DB::table('security_dup_perm_id_apply')->where('emp_id_apply', $applyId)->first();
            if (!$row || (int) $row->id_status !== 1) {
                return redirect()->back()->with('error', 'This request is not pending your approval.');
            }

            $hasRecommended = DB::table('security_dup_perm_id_apply_approval')
                ->where('security_parm_id_apply_pk', $applyId)
                ->where('status', 1)
                ->where('recommend_status', 1)
                ->exists();
            if (!$hasRecommended) {
                return redirect()->back()->with('error', 'This request must be recommended at Level 2 first.');
            }

            $hasFinal = DB::table('security_dup_perm_id_apply_approval')
                ->where('security_parm_id_apply_pk', $applyId)
                ->where('status', 2)
                ->exists();
            if ($hasFinal) {
                return redirect()->back()->with('error', 'This request has already been finally approved.');
            }

            // Update latest pending row (status=0) to final approved (status=2)
            DB::table('security_dup_perm_id_apply_approval')
                ->where('security_parm_id_apply_pk', $applyId)
                ->where('status', 0)
                ->orderByDesc('pk')
                ->limit(1)
                ->update([
                    'status' => 2,
                    'approval_emp_pk' => $employeePk,
                    'modified_by' => $employeePk,
                    'modified_date' => now()->format('Y-m-d H:i:s'),
                ]);

            DB::table('security_dup_perm_id_apply')->where('emp_id_apply', $applyId)->update([
                'id_status' => 2,
            ]);

            return redirect()->route('admin.security.employee_idcard_approval.approval3')
                ->with('success', 'Duplicate ID Card request approved successfully at final level.');
        }

        // Contractual Regular final approval (c- prefix)
        if (is_string($empIdApply) && str_starts_with($empIdApply, 'c-')) {
            $contPk = (int) substr($empIdApply, 2);
            $row = DB::table('security_con_oth_id_apply')->where('pk', $contPk)->first();
            if (! $row || (int) $row->id_status !== 1) {
                return redirect()->back()->with('error', 'This request is not pending your approval.');
            }
            // Must be recommended at Level 2 first (status=1 with recommend_status=1),
            // and must not already be finally approved/rejected.
            $hasRecommended = DB::table('security_con_oth_id_apply_approval')
                ->where('security_parm_id_apply_pk', $row->emp_id_apply)
                ->where('status', 1)
                ->where('recommend_status', 1)
                ->exists();
            if (! $hasRecommended) {
                return redirect()->back()->with('error', 'This request must be approved at Level 2 first.');
            }
            $hasFinal = DB::table('security_con_oth_id_apply_approval')
                ->where('security_parm_id_apply_pk', $row->emp_id_apply)
                ->where('status', 2)
                ->exists();
            if ($hasFinal) {
                return redirect()->back()->with('error', 'This request has already been finally approved.');
            }
            $hasRejected = DB::table('security_con_oth_id_apply_approval')
                ->where('security_parm_id_apply_pk', $row->emp_id_apply)
                ->where('status', 3)
                ->exists();
            if ($hasRejected) {
                return redirect()->back()->with('error', 'This request has already been rejected.');
            }

            DB::beginTransaction();
            try {
                // Generate id_card_no at final approval (Level 3): prefix + next full_sec_no from sec_id_cardno_config
                if (empty(trim($row->id_card_no ?? ''))) {
                    $configPk = $row->sec_id_card_config_pk ?? null;
                    if (! $configPk) {
                        DB::rollBack();
                        return redirect()->back()->with('error', 'ID card configuration not found for this contractual employee.');
                    }
                    $config = DB::table('sec_id_cardno_config')
                        ->where('pk', $configPk)
                        ->lockForUpdate()
                        ->first();
                    if (! $config || empty($config->prefix) || empty($config->full_sec_no)) {
                        DB::rollBack();
                        return redirect()->back()->with('error', 'Invalid ID card configuration for this contractual employee.');
                    }
                    $prefix = $config->prefix;
                    $currentFull = $config->full_sec_no;
                    if (str_starts_with($currentFull, $prefix)) {
                        $numericPart = substr($currentFull, strlen($prefix));
                    } else {
                        $numericPart = $currentFull;
                    }
                    $numericLen = strlen($numericPart);
                    $nextNumber = (int) $numericPart + 1;
                    $nextPadded = str_pad((string) $nextNumber, $numericLen, '0', STR_PAD_LEFT);
                    $newFull = $prefix . $nextPadded;
                    DB::table('security_con_oth_id_apply')
                        ->where('pk', $contPk)
                        ->update(['id_card_no' => $newFull]);
                    DB::table('sec_id_cardno_config')
                        ->where('pk', $configPk)
                        ->update(['full_sec_no' => $newFull]);
                }

                DB::table('security_con_oth_id_apply_approval')
                    ->where('security_parm_id_apply_pk', $row->emp_id_apply)
                    ->where('status', 0)
                    ->orderByDesc('pk')
                    ->limit(1)
                    ->update([
                        'status' => 2,
                        'recommend_status' => 0,
                        'modified_by' => $employeePk,
                        'modified_date' => now()->format('Y-m-d H:i:s'),
                    ]);
                DB::table('security_con_oth_id_apply')->where('pk', $contPk)->update(['id_status' => 2]);
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Unable to generate ID card number or complete final approval. Please try again.');
            }

            // Final approval row (status 3) and mark main row approved
            // DB::table('security_con_oth_id_apply_approval')->insert([
            //     'security_parm_id_apply_pk' => $row->emp_id_apply,
            //     'status' => 3,
            //     'approval_remarks' => null,
            //     'recommend_status' => null,
            //     'approval_emp_pk' => $employeePk,
            //     'created_by' => $employeePk,
            //     'created_date' => now()->format('Y-m-d H:i:s'),
            //     'modified_by' => $employeePk,
            //     'modified_date' => now()->format('Y-m-d H:i:s'),
            // ]);
            // DB::table('security_con_oth_id_apply')->where('pk', $contPk)->update(['id_status' => 2]);

            // return redirect()->route('admin.security.employee_idcard_approval.approval3')
            //     ->with('success', 'Contractual ID Card request approved at Level 3. ID card is now fully approved.');

            return redirect()->route('admin.security.employee_idcard_approval.approval3')
                ->with('success', 'Contractual ID Card request approved successfully at final level.');
        }

        // Permanent regular ID Card request (emp_id_apply string, primary key on security_parm_id_apply)
        $row = SecurityParmIdApply::with('employee')->findOrFail($empIdApply);
        if ($row->id_status != SecurityParmIdApply::ID_STATUS_PENDING) {
            return redirect()->back()->with('error', 'This request is not pending your approval.');
        }

        // Must already have Approval II
        $hasA2 = SecurityParmIdApplyApproval::where('security_parm_id_apply_pk', $row->emp_id_apply)
            ->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_2)
            ->exists();
        if (! $hasA2) {
            return redirect()->back()->with('error', 'This request must be approved at Level 2 first.');
        }

        // Auto-generate ID card number at final approval (Level 3): DDMMYYYY + first 4 letters of name
        if (empty(trim($row->id_card_no ?? ''))) {
            $row->loadMissing('employee');
            $dob = $row->employee_dob ?? ($row->employee ? ($row->employee->dob ?? null) : null);
            $name = $row->employee
                ? trim(($row->employee->first_name ?? '') . ' ' . ($row->employee->last_name ?? ''))
                : '';
            $generated = IdCardSecurityMapper::generateGovernmentEmployeeIdCardNumber($dob, $name);
            if ($generated) {
                $row->id_card_no = $generated;
            }
        }

        // Insert final approver row into idcard_request_approvar_master_new with sequence = 1
        try {
            $nextPk = (int) DB::table('idcard_request_approvar_master_new')->max('pk') + 1;
            DB::table('idcard_request_approvar_master_new')->insert([
                'pk' => $nextPk,
                'employee_master_pk' => $row->employee_master_pk,
                'student_master_pk' => null,
                'employees_pk' => $employeePk,
                'type' => 'employee',
                'sequence' => 1,
                'is_forwarded' => 1,
                'cont_status' => 0,
                'per_status' => 1,
                'family_status' => 0,
                'traning_status' => 0,
                'duplicate_status' => 0,
                'vec_status' => 0,
            ]);
        } catch (\Throwable $e) {
            // Do not block core approval if logging fails
        }

        // Final approval: mark ID card as Approved
        $row->id_status = SecurityParmIdApply::ID_STATUS_APPROVED;
        $row->save();

        return redirect()->route('admin.security.employee_idcard_approval.approval3')
            ->with('success', 'Request approved successfully at Level 3. ID card is now fully approved.');
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

        // Contractual/Family Duplicate reject (Approval 1 or 2)
        if (is_string($pk) && str_starts_with($pk, 'c-dup-')) {
            $applyId = substr($pk, 6);
            $row = DB::table('security_dup_other_id_apply')->where('emp_id_apply', $applyId)->first();
            if (!$row || (int) $row->id_status !== 1) {
                return redirect()->back()->with('error', 'This request is not pending your action.');
            }
            // Approval I reject is restricted to section head; Approval II reject is for Security level.
            if ($stage === 1 && (int) $row->department_approval_emp_pk !== (int) $employeePk) {
                return redirect()->back()->with('error', 'Only the designated Approval Authority can reject this request.');
            }
            $hasA1 = DB::table('security_dup_other_id_apply_approval')->where('security_con_id_apply_pk', $row->emp_id_apply)->where('status', 1)->exists();
            $hasRecommended = DB::table('security_dup_other_id_apply_approval')
                ->where('security_con_id_apply_pk', $row->emp_id_apply)
                ->where('status', 1)
                ->where('recommend_status', 1)
                ->exists();
            $hasA2 = DB::table('security_dup_other_id_apply_approval')->where('security_con_id_apply_pk', $row->emp_id_apply)->where('status', 2)->exists();
            $hasRej = DB::table('security_dup_other_id_apply_approval')->where('security_con_id_apply_pk', $row->emp_id_apply)->where('status', 3)->exists();
            if ($hasA2) {
                return redirect()->back()->with('error', 'This request has already been approved.');
            }
            if ($stage === 1 && $hasA1) {
                return redirect()->back()->with('error', 'This request has already been approved at Level 1.');
            }
            if ($stage === 2 && (!$hasA1 || $hasRecommended || $hasRej)) {
                return redirect()->back()->with('error', 'This request must be approved at Level 1 first.');
            }
            if ($stage === 3 && (!$hasRecommended || $hasRej)) {
                return redirect()->back()->with('error', 'This request is not pending your action.');
            }
            DB::table('security_dup_other_id_apply_approval')->insert([
                'security_con_id_apply_pk' => $row->emp_id_apply,
                'status' => 3,
                'approval_remarks' => $validated['rejection_reason'],
                'approval_emp_pk' => $employeePk,
                'created_by' => $employeePk,
                'created_date' => now()->format('Y-m-d H:i:s'),
                'modified_by' => $employeePk,
                'modified_date' => now()->format('Y-m-d H:i:s'),
            ]);
            DB::table('security_dup_other_id_apply')->where('emp_id_apply', $applyId)->update(['id_status' => 3]);
            $route = $stage === 1
                ? 'admin.security.employee_idcard_approval.approval1'
                : ($stage === 2 ? 'admin.security.employee_idcard_approval.approval2' : 'admin.security.employee_idcard_approval.approval3');
            return redirect()->route($route)->with('success', 'Request rejected.');
        }

        // Permanent Duplicate reject (Approval 2 only - permanent duplicates go directly to Approval 2)
        if (is_string($pk) && str_starts_with($pk, 'p-dup-')) {
            $applyId = substr($pk, 6);
            $row = DB::table('security_dup_perm_id_apply')->where('emp_id_apply', $applyId)->first();
            if (!$row || (int) $row->id_status !== 1) {
                return redirect()->back()->with('error', 'This request is not pending your action.');
            }
            $hasRecommended = DB::table('security_dup_perm_id_apply_approval')
                ->where('security_parm_id_apply_pk', $row->emp_id_apply)
                ->where('status', 1)
                ->where('recommend_status', 1)
                ->exists();
            $hasFinal = DB::table('security_dup_perm_id_apply_approval')->where('security_parm_id_apply_pk', $row->emp_id_apply)->where('status', 2)->exists();
            $hasRej = DB::table('security_dup_perm_id_apply_approval')->where('security_parm_id_apply_pk', $row->emp_id_apply)->where('status', 3)->exists();
            if ($hasFinal) {
                return redirect()->back()->with('error', 'This request has already been approved.');
            }
            if ($hasRej) {
                return redirect()->back()->with('error', 'This request is not pending your action.');
            }
            if ($stage === 2 && $hasRecommended) {
                return redirect()->back()->with('error', 'This request is not pending your action.');
            }
            if ($stage === 3 && !$hasRecommended) {
                return redirect()->back()->with('error', 'This request is not pending your action.');
            }
            DB::table('security_dup_perm_id_apply_approval')->insert([
                'security_parm_id_apply_pk' => $row->emp_id_apply,
                'status' => 3,
                'approval_remarks' => $validated['rejection_reason'],
                'approval_emp_pk' => $employeePk,
                'created_by' => $employeePk,
                'created_date' => now()->format('Y-m-d H:i:s'),
                'modified_by' => $employeePk,
                'modified_date' => now()->format('Y-m-d H:i:s'),
            ]);
            DB::table('security_dup_perm_id_apply')->where('emp_id_apply', $applyId)->update(['id_status' => 3]);
            $route = $stage === 2
                ? 'admin.security.employee_idcard_approval.approval2'
                : 'admin.security.employee_idcard_approval.approval3';
            return redirect()->route($route)->with('success', 'Request rejected.');
        }

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
            $hasFinal = $approvals->where('status', 2)->isNotEmpty();
            $hasRej = $approvals->where('status', 3)->isNotEmpty();
            $hasRecommended = $approvals->where('status', 1)->where('recommend_status', 1)->isNotEmpty();
            // For contractual regular flow:
            // - Stage 1 creates a pending row (status=0) for stage 2.
            // - Stage 2 updates that row to recommended (status=1, recommend_status=1) and creates a new pending row (status=0) for final.
            // So the correct "pending your action" check is presence of a status=0 row.
            $hasPending = $approvals->where('status', 0)->isNotEmpty();
            $sectionHeadDone = (int) ($row->depart_approval_status ?? 0) === 2;
            if ($stage === 1 && ($sectionHeadDone || $hasA1 || $hasRej)) {
                return redirect()->back()->with('error', 'This request is not pending your action.');
            }
            if ($stage === 2 && (!$hasPending || $hasRecommended || $hasFinal || $hasRej)) {
                return redirect()->back()->with('error', 'This request is not pending your action.');
            }
            if ($stage === 3 && (!$hasPending || !$hasRecommended || $hasFinal || $hasRej)) {
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
            $route = $stage === 1
                ? 'admin.security.employee_idcard_approval.approval1'
                : ($stage === 2 ? 'admin.security.employee_idcard_approval.approval2' : 'admin.security.employee_idcard_approval.approval3');
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
        // Permanent employees can come directly to Approval II (no Approval I prerequisite),
        // so at stage 2 we only block if it was already approved/rejected.
        if ($stage === 2 && ($hasA2 || $hasRej)) {
            return redirect()->back()->with('error', 'This request is not pending your action.');
        }
        // At final stage (Approval III), allow rejection only if already recommended at Level 2 and not rejected.
        if ($stage === 3 && (!$hasA2 || $hasRej)) {
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

        $route = $stage === 1
            ? 'admin.security.employee_idcard_approval.approval1'
            : ($stage === 2 ? 'admin.security.employee_idcard_approval.approval2' : 'admin.security.employee_idcard_approval.approval3');
        return redirect()->route($route)->with('success', 'Request rejected.');
    }

    public function all(Request $request)
    {
        $search = trim($request->get('search', ''));
        $statusFilter = $request->get('status', '');

        // 1) Permanent requests (SecurityParmIdApply)
        $permQuery = SecurityParmIdApply::with(['employee.designation', 'approvals.approver'])
            ->orderBy('created_date', 'desc');

        if ($search !== '') {
            $permQuery->whereHas('employee', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            })->orWhere('id_card_no', 'like', "%{$search}%");
        }

        if ($statusFilter !== '') {
            if ($statusFilter === 'Pending_A1') {
                $subA1 = SecurityParmIdApplyApproval::select('security_parm_id_apply_pk')
                    ->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_1);
                $permQuery->where('id_status', SecurityParmIdApply::ID_STATUS_PENDING)
                    ->whereNotIn('emp_id_apply', $subA1);
            } elseif ($statusFilter === 'Pending_A2') {
                $hasA1 = SecurityParmIdApplyApproval::select('security_parm_id_apply_pk')
                    ->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_1);
                $hasA2 = SecurityParmIdApplyApproval::select('security_parm_id_apply_pk')
                    ->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_2);
                $permQuery->where('id_status', SecurityParmIdApply::ID_STATUS_PENDING)
                    ->whereIn('emp_id_apply', $hasA1)
                    ->whereNotIn('emp_id_apply', $hasA2);
            } else {
                $permQuery->where('id_status', match ($statusFilter) {
                    'Approved' => SecurityParmIdApply::ID_STATUS_APPROVED,
                    'Rejected' => SecurityParmIdApply::ID_STATUS_REJECTED,
                    default => $statusFilter,
                });
            }
        }

        $permRows = $permQuery->get();
        $permDtos = $permRows->map(fn ($r) => IdCardSecurityMapper::toEmployeeRequestDto($r));

        // 2) Contractual Regular requests (security_con_oth_id_apply)
        $contQuery = DB::table('security_con_oth_id_apply')->orderByDesc('created_date');

        if ($search !== '') {
            $contQuery->where(function ($q) use ($search) {
                $q->where('employee_name', 'like', "%{$search}%")
                    ->orWhere('id_card_no', 'like', "%{$search}%");
            });
        }

        if ($statusFilter !== '') {
            // For contractual, support simple status filters only (Pending/Approved/Rejected)
            $statusMap = [
                'Pending' => 1,
                'Approved' => 2,
                'Rejected' => 3,
            ];
            if (isset($statusMap[$statusFilter])) {
                $contQuery->where('id_status', $statusMap[$statusFilter]);
            }
        }

        $contRows = $contQuery->get();
        $contDtos = $contRows->map(fn ($r) => IdCardSecurityMapper::toContractualRequestDto($r));

        // 3) Merge Permanent + Contractual and paginate together
        $merged = $permDtos->concat($contDtos)->sortByDesc(function ($d) {
            return $d->created_at ? (\Carbon\Carbon::parse($d->created_at)->timestamp ?? 0) : 0;
        })->values();

        $perPage = 10;
        $page = (int) $request->get('page', 1);

        $requests = new \Illuminate\Pagination\LengthAwarePaginator(
            $merged->forPage($page, $perPage),
            $merged->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.security.employee_idcard_approval.all', compact('requests'));
    }

    public function export(Request $request)
    {
        $stage = $request->get('stage', '1');
        $format = $request->get('format', 'xlsx');
        $search = $request->get('search', '');
        $cardType = $request->get('card_type', '');
        $perPage = (int) $request->get('per_page', 100);
        $dateFrom = $request->get('date_from', '');
        $dateTo = $request->get('date_to', '');

        // Fetch data based on stage
        if ($stage === '1') {
            return $this->exportApproval1($search, $cardType, $dateFrom, $dateTo, $format, $perPage);
        } else {
            return $this->exportApproval2($search, $cardType, $dateFrom, $dateTo, $format, $perPage);
        }
    }

    private function exportApproval1($search, $cardType, $dateFrom, $dateTo, $format, $perPage)
    {
        $user = Auth::user();
        $currentEmployeePk = $user->user_id ?? $user->pk ?? null;

        // Build query for Contractual requests (same base as approval1() list)
        $contQuery = DB::table('security_con_oth_id_apply')
            ->where('id_status', 1)
            ->where('department_approval_emp_pk', $currentEmployeePk)
            ->select(
                'emp_id_apply as id',
                'employee_name as name',
                'designation_name as designation',
                'id_card_no',
                'employee_dob',
                'blood_group',
                'mobile_no',
                'created_date',
                DB::raw("'Fresh' as type"),
                'permanent_type as card_type'
            );

        if (!empty($search)) {
            $contQuery->where(function ($q) use ($search) {
                $q->where('employee_name', 'like', "%{$search}%")
                  ->orWhere('id_card_no', 'like', "%{$search}%");
            });
        }
        if (!empty($cardType)) {
            $contQuery->where('permanent_type', $cardType);
        }

        // Date filters (created_date)
        if (!empty($dateFrom)) {
            $from = \Carbon\Carbon::parse($dateFrom)->startOfDay()->toDateTimeString();
            $contQuery->where('created_date', '>=', $from);
        }
        if (!empty($dateTo)) {
            $to = \Carbon\Carbon::parse($dateTo)->endOfDay()->toDateTimeString();
            $contQuery->where('created_date', '<=', $to);
        }

        $contData = $contQuery->get()->toArray();

        // Contractual Duplicate ID Card requests only (same as approval1 list - not permanent/family)
        $dupContA1Done = DB::table('security_dup_other_id_apply_approval')
            ->where('status', 1)
            ->pluck('security_con_id_apply_pk');
        $dupContQuery = DB::table('security_dup_other_id_apply')
            ->where('id_status', 1)
            ->where('card_type', 'Contractual')
            ->where('department_approval_emp_pk', $currentEmployeePk)
            ->whereNotIn('emp_id_apply', $dupContA1Done)
            ->select(
                'emp_id_apply as id',
                'employee_name as name',
                'designation_name as designation',
                'id_card_no',
                'employee_dob',
                'blood_group',
                'mobile_no',
                'created_date',
                DB::raw("'Duplicate' as type"),
                'card_type'
            );

        if (!empty($search)) {
            $dupContQuery->where(function ($q) use ($search) {
                $q->where('employee_name', 'like', '%' . $search . '%')
                  ->orWhere('id_card_no', 'like', '%' . $search . '%');
            });
        }
        if (!empty($dateFrom)) {
            $from = \Carbon\Carbon::parse($dateFrom)->startOfDay()->toDateTimeString();
            $dupContQuery->where('created_date', '>=', $from);
        }
        if (!empty($dateTo)) {
            $to = \Carbon\Carbon::parse($dateTo)->endOfDay()->toDateTimeString();
            $dupContQuery->where('created_date', '<=', $to);
        }

        $dupContData = $dupContQuery->get()->toArray();

        $data = array_merge($contData, $dupContData);

        return $this->outputExport($data, 'Approval_I_' . now()->format('Y-m-d'), $format);
    }

    private function exportApproval2($search, $cardType, $dateFrom, $dateTo, $format, $perPage)
    {
        $searchLike = empty($search) ? null : '%' . trim($search) . '%';
        $dateFromDt = empty($dateFrom) ? null : \Carbon\Carbon::parse($dateFrom)->startOfDay()->toDateTimeString();
        $dateToDt = empty($dateTo) ? null : \Carbon\Carbon::parse($dateTo)->endOfDay()->toDateTimeString();

        // 1) Permanent Regular: match employee by pk OR pk_old so name/designation resolve
        $permRegQuery = DB::table('security_parm_id_apply as spa')
            ->leftJoin('employee_master as emp', function ($j) {
                $j->on('spa.employee_master_pk', '=', 'emp.pk')
                  ->orOn('spa.employee_master_pk', '=', 'emp.pk_old');
            })
            ->leftJoin('designation_master as desig', 'emp.designation_master_pk', '=', 'desig.pk')
            ->where('spa.id_status', 1)
            ->select(
                'spa.emp_id_apply as id',
                DB::raw("TRIM(CONCAT(COALESCE(MAX(emp.first_name), ''), ' ', COALESCE(MAX(emp.last_name), ''))) as name"),
                DB::raw("MAX(desig.designation_name) as designation"),
                'spa.id_card_no',
                'spa.employee_dob',
                'spa.blood_group',
                'spa.mobile_no',
                'spa.created_date',
                DB::raw("'Fresh' as type"),
                DB::raw("'Permanent' as card_type")
            )
            ->groupBy('spa.pk', 'spa.emp_id_apply', 'spa.id_card_no', 'spa.employee_dob', 'spa.blood_group', 'spa.mobile_no', 'spa.created_date');

        if ($searchLike) {
            $permRegQuery->where(function ($q) use ($searchLike) {
                $q->where('emp.first_name', 'like', $searchLike)
                  ->orWhere('emp.last_name', 'like', $searchLike)
                  ->orWhere('spa.id_card_no', 'like', $searchLike);
            });
        }
        if (!empty($cardType)) {
            $permRegQuery->where('spa.permanent_type', $cardType);
        }
        if ($dateFromDt) {
            $permRegQuery->where('spa.created_date', '>=', $dateFromDt);
        }
        if ($dateToDt) {
            $permRegQuery->where('spa.created_date', '<=', $dateToDt);
        }
        $permRegData = $permRegQuery->orderByDesc('spa.created_date')->get();

        // 2) Permanent Duplicate
        $dupPermA2 = DB::table('security_dup_perm_id_apply_approval')->where('status', 2)->pluck('security_parm_id_apply_pk');
        $dupPermQuery = DB::table('security_dup_perm_id_apply as dup')
            ->leftJoin('employee_master as emp', function ($j) {
                $j->on('dup.employee_master_pk', '=', 'emp.pk')
                  ->orOn('dup.employee_master_pk', '=', 'emp.pk_old');
            })
            ->leftJoin('designation_master as desig', 'dup.designation_pk', '=', 'desig.pk')
            ->where('dup.id_status', 1);
        if ($dupPermA2->isNotEmpty()) {
            $dupPermQuery->whereNotIn('dup.emp_id_apply', $dupPermA2);
        }
        $dupPermQuery->select(
            'dup.emp_id_apply as id',
            DB::raw("TRIM(CONCAT(COALESCE(emp.first_name, ''), ' ', COALESCE(emp.last_name, ''))) as name"),
            'desig.designation_name as designation',
            'dup.id_card_no',
            'dup.employee_dob',
            'dup.blood_group',
            'dup.mobile_no',
            'dup.created_date',
            DB::raw("'Duplicate' as type"),
            DB::raw("'Permanent' as card_type")
        );
        if ($searchLike) {
            $dupPermQuery->where(function ($q) use ($searchLike) {
                $q->where('dup.id_card_no', 'like', $searchLike)
                  ->orWhereRaw("CONCAT(COALESCE(emp.first_name, ''), ' ', COALESCE(emp.last_name, '')) LIKE ?", [$searchLike]);
            });
        }
        if ($dateFromDt) {
            $dupPermQuery->where('dup.created_date', '>=', $dateFromDt);
        }
        if ($dateToDt) {
            $dupPermQuery->where('dup.created_date', '<=', $dateToDt);
        }
        $dupPermData = $dupPermQuery->orderByDesc('dup.created_date')->get();

        // 3) Contractual Regular (has A1, no A2)
        $contHasA1 = DB::table('security_con_oth_id_apply_approval')->where('status', 1)->pluck('security_parm_id_apply_pk');
        $contHasA2 = DB::table('security_con_oth_id_apply_approval')->where('status', 2)->pluck('security_parm_id_apply_pk');
        $contQuery = DB::table('security_con_oth_id_apply')
            ->where('id_status', 1)
            ->select(
                'emp_id_apply as id',
                'employee_name as name',
                'designation_name as designation',
                'id_card_no',
                'employee_dob',
                'blood_group',
                'mobile_no',
                'created_date',
                DB::raw("'Fresh' as type"),
                DB::raw("'Contractual' as card_type")
            );
        if ($contHasA1->isNotEmpty()) {
            $contQuery->whereIn('emp_id_apply', $contHasA1);
            if ($contHasA2->isNotEmpty()) {
                $contQuery->whereNotIn('emp_id_apply', $contHasA2);
            }
        } else {
            $contQuery->whereRaw('0 = 1');
        }
        if ($searchLike) {
            $contQuery->where(function ($q) use ($searchLike) {
                $q->where('employee_name', 'like', $searchLike)->orWhere('id_card_no', 'like', $searchLike);
            });
        }
        if ($dateFromDt) {
            $contQuery->where('created_date', '>=', $dateFromDt);
        }
        if ($dateToDt) {
            $contQuery->where('created_date', '<=', $dateToDt);
        }
        $contData = $contQuery->orderByDesc('created_date')->get();

        // 4) Contractual Duplicate
        $dupContA2 = DB::table('security_dup_other_id_apply_approval')->where('status', 2)->pluck('security_con_id_apply_pk');
        $dupContQuery = DB::table('security_dup_other_id_apply')
            ->where('id_status', 1)
            ->select(
                'emp_id_apply as id',
                'employee_name as name',
                'designation_name as designation',
                'id_card_no',
                'employee_dob',
                'blood_group',
                'mobile_no',
                'created_date',
                DB::raw("'Duplicate' as type"),
                DB::raw("'Contractual' as card_type")
            );
        if ($dupContA2->isNotEmpty()) {
            $dupContQuery->whereNotIn('emp_id_apply', $dupContA2);
        }
        if ($searchLike) {
            $dupContQuery->where(function ($q) use ($searchLike) {
                $q->where('employee_name', 'like', $searchLike)->orWhere('id_card_no', 'like', $searchLike);
            });
        }
        if ($dateFromDt) {
            $dupContQuery->where('created_date', '>=', $dateFromDt);
        }
        if ($dateToDt) {
            $dupContQuery->where('created_date', '<=', $dateToDt);
        }
        $dupContData = $dupContQuery->orderByDesc('created_date')->get();

        $data = $permRegData->concat($dupPermData)->concat($contData)->concat($dupContData)
            ->sortByDesc(function ($r) {
                return $r->created_date ? \Carbon\Carbon::parse($r->created_date)->timestamp : 0;
            })
            ->values()
            ->all();

        return $this->outputExport($data, 'Approval_II_' . now()->format('Y-m-d'), $format);
    }

    /**
     * Resolve ID Type label (sec_id_cardno_master.sec_card_name) for permanent duplicate rows
     * shown on Approval II / III lists.
     *
     * Important:
     * For permanent duplicates, `security_dup_perm_id_apply` rows may NOT store `permanent_type` / `parent_id`.
     * In that case, we must resolve ID Type from the *first time apply* record in `security_parm_id_apply`
     * using (employee_master_pk + id_card_no).
     *
     * @param  \Illuminate\Support\Collection|array<int, object>  $dupPermRows
     * @return array<string, string>  keyed by emp_id_apply
     */
    private function mapDupPermIdCardTypeLabels($dupPermRows): array
    {
        if ($dupPermRows->isEmpty()) {
            return [];
        }

        // 1) Optional fallback (older data): use dup.parent_id -> security_parm_id_apply.permanent_type
        $parentIds = $dupPermRows->pluck('parent_id')->filter()->unique()->values();
        $permTypeByParent = [];
        if ($parentIds->isNotEmpty()) {
            $permTypeByParent = DB::table('security_parm_id_apply')
                ->whereIn('emp_id_apply', $parentIds->all())
                ->pluck('permanent_type', 'emp_id_apply')
                ->all();
        }

        // 2) Primary resolution: use *first apply* record from security_parm_id_apply
        // using (employee_master_pk + id_card_no).
        $empPks = $dupPermRows->pluck('employee_master_pk')->filter()->unique()->values();
        $cardNos = $dupPermRows->pluck('id_card_no')
            ->filter()
            ->map(fn ($n) => trim((string) $n))
            ->unique()
            ->values();

        $permTypeByEmpCard = [];
        if ($empPks->isNotEmpty() && $cardNos->isNotEmpty()) {
            $candidates = DB::table('security_parm_id_apply')
                ->whereIn('employee_master_pk', $empPks->all())
                ->whereIn('id_card_no', $cardNos->all())
                ->select(['employee_master_pk', 'id_card_no', 'permanent_type', 'created_date'])
                ->orderBy('created_date', 'asc')
                ->get();

            foreach ($candidates as $c) {
                $empPk = (int) ($c->employee_master_pk ?? 0);
                $cardNo = trim((string) ($c->id_card_no ?? ''));
                $key = $empPk . '|' . $cardNo;

                if ($empPk <= 0 || $cardNo === '') {
                    continue;
                }
                if (! isset($permTypeByEmpCard[$key]) && !empty($c->permanent_type)) {
                    $permTypeByEmpCard[$key] = (int) $c->permanent_type;
                }
            }
        }

        // Collect all possible master pks so we can map to sec_card_name.
        $masterPks = [];
        foreach ($dupPermRows as $r) {
            $applyKey = (string) ($r->emp_id_apply ?? '');
            if ($applyKey === '') {
                continue;
            }

            $pk = $r->permanent_type ?? null;
            if (empty($pk) && ! empty($r->parent_id)) {
                $pk = $permTypeByParent[$r->parent_id] ?? null;
            }
            if ((empty($pk) || $pk === null) && !empty($r->employee_master_pk) && !empty($r->id_card_no)) {
                $key = (int) $r->employee_master_pk . '|' . trim((string) $r->id_card_no);
                $pk = $permTypeByEmpCard[$key] ?? null;
            }
            if ($pk !== null && $pk !== '' && (int) $pk > 0) {
                $masterPks[] = (int) $pk;
            }
        }
        $masterPks = array_values(array_unique(array_filter($masterPks)));

        $namesByPk = $masterPks !== []
            ? DB::table('sec_id_cardno_master')
                ->whereIn('pk', $masterPks)
                ->pluck('sec_card_name', 'pk')
                ->all()
            : [];

        // Final labels: keyed by dup.emp_id_apply
        $labels = [];
        foreach ($dupPermRows as $r) {
            $applyKey = (string) ($r->emp_id_apply ?? '');

            $pk = $r->permanent_type ?? null;
            if (empty($pk) && ! empty($r->parent_id)) {
                $pk = $permTypeByParent[$r->parent_id] ?? null;
            }
            if ((empty($pk) || $pk === null) && !empty($r->employee_master_pk) && !empty($r->id_card_no)) {
                $key = (int) $r->employee_master_pk . '|' . trim((string) $r->id_card_no);
                $pk = $permTypeByEmpCard[$key] ?? null;
            }

            $label = null;
            if ($pk !== null && $pk !== '' && (int) $pk > 0 && isset($namesByPk[(int) $pk])) {
                $label = is_string($namesByPk[(int) $pk]) ? trim($namesByPk[(int) $pk]) : $namesByPk[(int) $pk];
            }

            // Last fallback to whatever string is stored.
            if (($label === null || $label === '') && ! empty($r->card_type) && ! in_array((string) $r->card_type, ['Permanent', 'Contractual', 'Family'], true)) {
                $label = is_string($r->card_type) ? trim($r->card_type) : (string) $r->card_type;
            }

            $labels[$applyKey] = ($label === null || $label === '') ? '--' : $label;
        }

        return $labels;
    }

    /**
     * Resolve ID Type (card master name) for contractual duplicate rows on Approval II/III lists.
     * Note: security_dup_other_id_apply.id_proof is document type (Aadhar/PAN…), not sec_id_cardno_master.pk.
     * Prefer row.permanent_type when present; else resolve via latest matching security_con_oth_id_apply by id_card_no.
     *
     * @param  \Illuminate\Support\Collection|array<int, object>  $dupContRows
     * @return array<string, string>  keyed by emp_id_apply
     */
    private function mapDupOtherIdCardTypeLabels($dupContRows): array
    {
        if ($dupContRows->isEmpty()) {
            return [];
        }

        $cardNos = $dupContRows->pluck('id_card_no')
            ->filter()
            ->map(fn ($n) => trim((string) $n))
            ->unique()
            ->filter()
            ->values();

        $permTypeByCardNo = [];
        if ($cardNos->isNotEmpty()) {
            $candidates = DB::table('security_con_oth_id_apply')
                ->whereIn('id_card_no', $cardNos->all())
                ->orderByRaw('CASE WHEN id_status = 2 THEN 0 ELSE 1 END')
                ->orderByDesc('created_date')
                ->get(['id_card_no', 'permanent_type']);

            foreach ($candidates as $c) {
                $cn = trim((string) ($c->id_card_no ?? ''));
                if ($cn === '' || isset($permTypeByCardNo[$cn])) {
                    continue;
                }
                if (! empty($c->permanent_type)) {
                    $permTypeByCardNo[$cn] = (int) $c->permanent_type;
                }
            }
        }

        $masterPkList = collect(array_values($permTypeByCardNo));
        foreach ($dupContRows as $r) {
            if (! empty($r->permanent_type)) {
                $masterPkList->push((int) $r->permanent_type);
            }
        }
        $masterPks = $masterPkList->filter(fn ($v) => (int) $v > 0)->unique()->values()->all();

        $namesByPk = $masterPks !== []
            ? DB::table('sec_id_cardno_master')->whereIn('pk', $masterPks)->pluck('sec_card_name', 'pk')->all()
            : [];

        $labels = [];
        foreach ($dupContRows as $r) {
            $applyKey = (string) ($r->emp_id_apply ?? '');
            $label = null;
            $typePk = ! empty($r->permanent_type) ? (int) $r->permanent_type : null;
            if (! $typePk) {
                $cn = trim((string) ($r->id_card_no ?? ''));
                $typePk = ($cn !== '' && isset($permTypeByCardNo[$cn])) ? $permTypeByCardNo[$cn] : null;
            }
            if ($typePk && isset($namesByPk[$typePk])) {
                $label = $namesByPk[$typePk];
            }
            if ($label === null && ! empty($r->card_type) && ! in_array((string) $r->card_type, ['Permanent', 'Contractual', 'Family'], true)) {
                $label = (string) $r->card_type;
            }
            $labels[$applyKey] = $label ?: '--';
        }

        return $labels;
    }

    private function outputExport($data, $filename, $format)
    {
        if ($format === 'pdf') {
            return $this->exportPdf($data, $filename);
        } else {
            return $this->exportExcel($data, $filename);
        }
    }

    private function exportExcel($data, $filename)
    {
        $headers = ['ID', 'Employee Name', 'Designation', 'ID Card No', 'DOB', 'Blood Group', 'Mobile', 'Request Date', 'Type', 'Card Type'];
        
        $csvData = implode(',', $headers) . "\n";
        foreach ($data as $row) {
            $csvData .= implode(',', [
                $row->id ?? '',
                '"' . ($row->name ?? '') . '"',
                $row->designation ?? '',
                $row->id_card_no ?? '',
                $row->employee_dob ?? '',
                $row->blood_group ?? '',
                $row->mobile_no ?? '',
                $row->created_date ?? '',
                $row->type ?? '',
                $row->card_type ?? '',
            ]) . "\n";
        }

        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}.csv\"");
    }

    private function exportPdf($data, $filename)
    {
        $html = '<table border="1" cellpadding="5" style="width:100%;">';
        $html .= '<tr><th>ID</th><th>Employee Name</th><th>Designation</th><th>ID Card No</th><th>DOB</th><th>Blood Group</th><th>Mobile</th><th>Request Date</th><th>Type</th><th>Card Type</th></tr>';
        
        foreach ($data as $row) {
            $html .= '<tr>';
            $html .= '<td>' . ($row->id ?? '') . '</td>';
            $html .= '<td>' . ($row->name ?? '') . '</td>';
            $html .= '<td>' . ($row->designation ?? '') . '</td>';
            $html .= '<td>' . ($row->id_card_no ?? '') . '</td>';
            $html .= '<td>' . ($row->employee_dob ?? '') . '</td>';
            $html .= '<td>' . ($row->blood_group ?? '') . '</td>';
            $html .= '<td>' . ($row->mobile_no ?? '') . '</td>';
            $html .= '<td>' . ($row->created_date ?? '') . '</td>';
            $html .= '<td>' . ($row->type ?? '') . '</td>';
            $html .= '<td>' . ($row->card_type ?? '') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape');

        return $pdf->download($filename . '.pdf');
    }
}
