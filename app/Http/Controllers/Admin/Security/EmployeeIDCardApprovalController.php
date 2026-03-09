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
            ->where('id_status', 1)
            ->where('department_approval_emp_pk', $currentEmployeePk);
        if ($contA1Done->isNotEmpty()) {
            $contQuery->whereNotIn('emp_id_apply', $contA1Done);
        }
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
        $contDtos = $contRows->map(fn ($r) => IdCardSecurityMapper::toContractualRequestDto($r));

        // Contractual Duplicate ID Card requests only (not Permanent/Family) - same approving authority
        $dupContA1Done = DB::table('security_dup_other_id_apply_approval')
            ->where('status', 1)
            ->pluck('security_con_id_apply_pk');
        $dupContQuery = DB::table('security_dup_other_id_apply')
            ->where('id_status', 1)
            ->where('card_type', 'Contractual')
            ->where('department_approval_emp_pk', $currentEmployeePk);
        if ($dupContA1Done->isNotEmpty()) {
            $dupContQuery->whereNotIn('emp_id_apply', $dupContA1Done);
        }
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
        $dupContDtos = $dupContRows->map(function ($r) {
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
            $dto->status = 'Pending';
            $dto->request_type = 'duplicate';
            $dto->father_name = null;
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
        $user = Auth::user();
        $currentEmployeePk = $user->user_id ?? $user->pk ?? null;

        $hasA1 = SecurityParmIdApplyApproval::select('security_parm_id_apply_pk')
            ->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_1);
        $hasA2 = SecurityParmIdApplyApproval::select('security_parm_id_apply_pk')
            ->where('status', SecurityParmIdApplyApproval::STATUS_APPROVAL_2);
        // Permanent employees: go directly to Approval 2 (no Approval 1 required)
        // Contractual employees can also come here after completing Approval 1
        $permQuery = SecurityParmIdApply::with(['employee.designation', 'employee.department', 'creator.department', 'approvals.approver'])
            ->where('id_status', SecurityParmIdApply::ID_STATUS_PENDING)
            ->whereNotIn('emp_id_apply', $hasA2)
            ->orderBy('created_date', 'desc');

        // Permanent Duplicate: goes directly to Approval 2 (no A1 required, like permanent regular)
        $dupPermA2 = DB::table('security_dup_perm_id_apply_approval')->where('status', 2)->pluck('security_parm_id_apply_pk');
        $dupPermQuery = DB::table('security_dup_perm_id_apply as dup')
            ->leftJoin('employee_master as emp', 'dup.employee_master_pk', '=', 'emp.pk')
            ->leftJoin('designation_master as desig', 'dup.designation_pk', '=', 'desig.pk')
            ->leftJoin('department_master as dept', 'emp.department_master_pk', '=', 'dept.pk')
            ->where('dup.id_status', 1);
        if ($dupPermA2->isNotEmpty()) {
            $dupPermQuery->whereNotIn('dup.emp_id_apply', $dupPermA2);
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

        $permRows = $permQuery->get();
        $dupPermRows = $dupPermQuery->get();
        
        $permDtos = $permRows->map(fn ($r) => IdCardSecurityMapper::toEmployeeRequestDto($r));
        // For duplicate permanent records (stdClass from DB query), create DTOs directly without mapper
        $dupPermDtos = $dupPermRows->map(function ($r) {
            $fullName = trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? ''));
            if ($fullName === '') {
                $fullName = $r->employee_name ?? '--';
            }
            $dto = (object) [
                'id' => $r->emp_id_apply,
                'name' => $fullName,
                'designation' => $r->designation_name ?? null,
                'father_name' => null,
                'id_card_number' => $r->id_card_no,
                'card_type' => null,
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
            return $dto;
        });

        // Contractual Regular: has A1, no A2 - shown at Approval 2 for visibility only (no actions)
        $contHasA1 = DB::table('security_con_oth_id_apply_approval')->where('status', 1)->pluck('security_parm_id_apply_pk');
        $contHasA2 = DB::table('security_con_oth_id_apply_approval')->where('status', 2)->pluck('security_parm_id_apply_pk');
        $contQuery = DB::table('security_con_oth_id_apply')->where('id_status', 1);
        if ($contHasA1->isNotEmpty()) {
            $contQuery->whereIn('emp_id_apply', $contHasA1);
            if ($contHasA2->isNotEmpty()) {
                $contQuery->whereNotIn('emp_id_apply', $contHasA2);
            }
        } else {
            $contQuery->whereRaw('0 = 1'); // no A1 approvals yet => no rows
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

        // Contractual Duplicate: shown at Approval 2 WITH actions (Approve/Reject) at Level 2
        $dupContHasA2 = DB::table('security_dup_other_id_apply_approval')->where('status', 2)->pluck('security_con_id_apply_pk');
        $dupContQuery = DB::table('security_dup_other_id_apply')->where('id_status', 1);
        if ($dupContHasA2->isNotEmpty()) {
            $dupContQuery->whereNotIn('emp_id_apply', $dupContHasA2);
        }
        $dupContQuery->where(function ($q) use ($currentEmployeePk) {
            $q->where('department_approval_emp_pk', $currentEmployeePk);
            if (hasRole('Admin')) {
                $q->orWhereNull('department_approval_emp_pk');
            }
        });
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
        
        // Mark contractual REGULAR requests as view-only (no approve/reject actions at Approval 2)
        $contDtos = $contRows->map(function ($r) {
            $dto = IdCardSecurityMapper::toContractualRequestDto($r);
            $dto->is_view_only = true; // Contractual requests are view-only at Approval 2
            return $dto;
        });
        // For duplicate contractual records (stdClass from DB query), create DTOs directly without mapper.
        // These SHOULD be actionable at Approval II, so we do NOT mark them as view-only.
        $dupContDtos = $dupContRows->map(function ($r) {
            $dto = (object) [
                // Use "c-<applyId>" as base id so view can build c-dup- prefix
                'id' => 'c-' . $r->emp_id_apply,
                'name' => $r->employee_name ?? '--',
                'designation' => $r->designation_name ?? '--',
                'father_name' => null,
                'id_card_number' => $r->id_card_no,
                'card_type' => null,
                'date_of_birth' => $r->employee_dob,
                'blood_group' => $r->blood_group,
                'mobile_number' => $r->mobile_no,
                'telephone_number' => null,
                'id_card_valid_from' => $r->card_valid_from,
                'id_card_valid_upto' => $r->card_valid_to,
                'photo' => $r->id_photo_path,
                'created_at' => isset($r->created_date) ? \Carbon\Carbon::parse($r->created_date) : null,
                'requested_by' => null,
                'requested_section' => $r->section,
                'request_type' => 'duplicate',
            ];
            return $dto;
        });

        // At Approval II level, permanent regular and permanent duplicate (actionable), plus contractual regular and duplicate (view-only)
        $merged = $permDtos->concat($dupPermDtos)->concat($contDtos)->concat($dupContDtos)->sortByDesc(function ($d) {
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
        if (is_string($decrypted) && str_starts_with($decrypted, 'c-dup-')) {
            $applyId = substr($decrypted, 6);
            $row = DB::table('security_dup_other_id_apply')->where('emp_id_apply', $applyId)->first();
            if (!$row) {
                abort(404);
            }
            $request = (object) [
                'id' => 'c-dup-' . $row->emp_id_apply,
                'name' => $row->employee_name ?? '--',
                'designation' => $row->designation_name ?? '--',
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
            $row = DB::table('security_dup_perm_id_apply')->where('emp_id_apply', $applyId)->first();
            if (!$row) {
                abort(404);
            }
            $request = (object) [
                'id' => 'p-dup-' . $row->emp_id_apply,
                'name' => $row->employee_name ?? '--',
                'designation' => null,
                'id_card_number' => $row->id_card_no,
                'date_of_birth' => $row->employee_dob,
                'blood_group' => $row->blood_group,
                'mobile_number' => $row->mobile_no,
                'photo' => $row->id_photo_path,
                'created_at' => $row->created_date ? \Carbon\Carbon::parse($row->created_date) : null,
                'id_card_valid_from' => $row->card_valid_from,
                'id_card_valid_upto' => $row->card_valid_to,
                'request_type' => 'duplicate',
                'card_type' => 'Permanent',
                'request_for' => 'Duplication',
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
            DB::table('security_dup_other_id_apply_approval')->insert([
                'security_con_id_apply_pk' => $row->emp_id_apply,
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
                ->with('success', 'Duplicate ID Card request approved at Level 1. It will now move to Approver 2.');
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
            $hasA1 = DB::table('security_con_oth_id_apply_approval')
                ->where('security_parm_id_apply_pk', $row->emp_id_apply)
                ->where('status', 1)
                ->exists();
            if ($hasA1) {
                return redirect()->back()->with('error', 'This request has already been approved at Level 1.');
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
            // For contractual regular ID cards, Level 1 is the FINAL approval.
            // Mark the underlying request as Approved so it appears in the requestor's Archived tab
            // and is no longer considered Pending in Approval flows.
            DB::table('security_con_oth_id_apply')
                ->where('pk', $pk)
                ->update(['id_status' => 2]);
            return redirect()->route('admin.security.employee_idcard_approval.approval1')
                ->with('success', 'Contractual ID Card request approved successfully. ID card is now fully approved.');
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
            if ((int) $row->department_approval_emp_pk !== (int) $employeePk && !hasRole('Admin')) {
                return redirect()->back()->with('error', 'Only the designated section authority can approve this request.');
            }
            $hasA1 = DB::table('security_dup_other_id_apply_approval')->where('security_con_id_apply_pk', $row->emp_id_apply)->where('status', 1)->exists();
            $hasA2 = DB::table('security_dup_other_id_apply_approval')->where('security_con_id_apply_pk', $row->emp_id_apply)->where('status', 2)->exists();
            if ($hasA2) {
                return redirect()->back()->with('error', 'This request has already been approved.');
            }
            if (!$hasA1) {
                DB::table('security_dup_other_id_apply_approval')->insert([
                    'security_con_id_apply_pk' => $row->emp_id_apply,
                    'status' => 1,
                    'approval_remarks' => null,
                    'approval_emp_pk' => $employeePk,
                    'created_by' => $employeePk,
                    'created_date' => now()->format('Y-m-d H:i:s'),
                    'modified_by' => $employeePk,
                    'modified_date' => now()->format('Y-m-d H:i:s'),
                ]);
            }
            DB::table('security_dup_other_id_apply_approval')->insert([
                'security_con_id_apply_pk' => $row->emp_id_apply,
                'status' => 2,
                'approval_remarks' => null,
                'approval_emp_pk' => $employeePk,
                'created_by' => $employeePk,
                'created_date' => now()->format('Y-m-d H:i:s'),
                'modified_by' => $employeePk,
                'modified_date' => now()->format('Y-m-d H:i:s'),
            ]);
            DB::table('security_dup_other_id_apply')->where('emp_id_apply', $applyId)->update(['id_status' => 2]);
            return redirect()->route('admin.security.employee_idcard_approval.approval2')
                ->with('success', 'Duplicate ID Card request approved successfully.');
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
            DB::table('security_dup_perm_id_apply_approval')->insert([
                'security_parm_id_apply_pk' => $row->emp_id_apply,
                'status' => 2,
                'approval_remarks' => null,
                'approval_emp_pk' => $employeePk,
                'created_by' => $employeePk,
                'created_date' => now()->format('Y-m-d H:i:s'),
                'modified_by' => $employeePk,
                'modified_date' => now()->format('Y-m-d H:i:s'),
            ]);
            DB::table('security_dup_perm_id_apply')->where('emp_id_apply', $applyId)->update(['id_status' => 2]);
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
            $hasA1 = DB::table('security_con_oth_id_apply_approval')->where('security_parm_id_apply_pk', $row->emp_id_apply)->where('status', 1)->exists();
            $hasA2 = DB::table('security_con_oth_id_apply_approval')->where('security_parm_id_apply_pk', $row->emp_id_apply)->where('status', 2)->exists();
            if (!$hasA1 || $hasA2) {
                return redirect()->back()->with('error', 'This request is not pending your approval.');
            }
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

        // Auto-generate ID card number for government (Permanent) employees: DDMMYYYY + first 4 letters of name
        if (empty(trim($row->id_card_no ?? ''))) {
            $dob = $row->employee_dob ?? ($row->employee ? ($row->employee->dob ?? null) : null);
            $name = $row->employee
                ? trim(($row->employee->first_name ?? '') . ' ' . ($row->employee->last_name ?? ''))
                : '';
            $generated = IdCardSecurityMapper::generateGovernmentEmployeeIdCardNumber($dob, $name);
            if ($generated) {
                $row->id_card_no = $generated;
            }
        }

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

        // Contractual/Family Duplicate reject (Approval 1 or 2)
        if (is_string($pk) && str_starts_with($pk, 'c-dup-')) {
            $applyId = substr($pk, 6);
            $row = DB::table('security_dup_other_id_apply')->where('emp_id_apply', $applyId)->first();
            if (!$row || (int) $row->id_status !== 1) {
                return redirect()->back()->with('error', 'This request is not pending your action.');
            }
            if ((int) $row->department_approval_emp_pk !== (int) $employeePk && !hasRole('Admin')) {
                return redirect()->back()->with('error', 'Only the designated section authority can reject this request.');
            }
            $hasA1 = DB::table('security_dup_other_id_apply_approval')->where('security_con_id_apply_pk', $row->emp_id_apply)->where('status', 1)->exists();
            $hasA2 = DB::table('security_dup_other_id_apply_approval')->where('security_con_id_apply_pk', $row->emp_id_apply)->where('status', 2)->exists();
            if ($hasA2) {
                return redirect()->back()->with('error', 'This request has already been approved.');
            }
            if ($stage === 1 && $hasA1) {
                return redirect()->back()->with('error', 'This request has already been approved at Level 1.');
            }
            if ($stage === 2 && !$hasA1) {
                return redirect()->back()->with('error', 'This request must be approved at Level 1 first.');
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
            $route = $stage === 1 ? 'admin.security.employee_idcard_approval.approval1' : 'admin.security.employee_idcard_approval.approval2';
            return redirect()->route($route)->with('success', 'Request rejected.');
        }

        // Permanent Duplicate reject (Approval 2 only - permanent duplicates go directly to Approval 2)
        if (is_string($pk) && str_starts_with($pk, 'p-dup-')) {
            $applyId = substr($pk, 6);
            $row = DB::table('security_dup_perm_id_apply')->where('emp_id_apply', $applyId)->first();
            if (!$row || (int) $row->id_status !== 1) {
                return redirect()->back()->with('error', 'This request is not pending your action.');
            }
            $hasA2 = DB::table('security_dup_perm_id_apply_approval')->where('security_parm_id_apply_pk', $row->emp_id_apply)->where('status', 2)->exists();
            if ($hasA2) {
                return redirect()->back()->with('error', 'This request has already been approved.');
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
            return redirect()->route('admin.security.employee_idcard_approval.approval2')->with('success', 'Request rejected.');
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
                DB::raw("'Regular' as type"),
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
                DB::raw("'Regular' as type"),
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
                DB::raw("'Regular' as type"),
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
