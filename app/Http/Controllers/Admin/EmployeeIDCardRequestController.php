<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\EmployeeIDCardExport;
use App\Models\DesignationMaster;
use App\Models\SecurityParmIdApply;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\SecurityParmIdApplyApproval;
use App\Models\EmployeeMaster;
use App\Support\IdCardSecurityMapper;
use App\Support\IdCardSecurityLookup;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

/**
 * ID Card List & Generate New ID Card - mapped to security_parm_id_apply.
 */
class EmployeeIDCardRequestController extends Controller
{
    public function index(Request $request)
    {
        $with = [
            'employee:pk,first_name,last_name,designation_master_pk',
            'employee.designation:pk,designation_name',
            'approvals:pk,security_parm_id_apply_pk,status,recommend_status,approval_emp_pk,created_date,approval_remarks',
            'approvals.approver:pk,first_name,last_name',
        ];
        $columns = ['pk', 'emp_id_apply', 'employee_master_pk', 'id_status', 'created_date', 'card_valid_from', 'card_valid_to', 'id_card_no', 'id_photo_path', 'joining_letter_path', 'mobile_no', 'telephone_no', 'blood_group', 'card_type', 'permanent_type', 'perm_sub_type', 'remarks', 'created_by', 'employee_dob'];
        // Load ALL records by default; tabs below will split into Active / Archive / Duplication / Extension.
        // Optional ?filter=active or ?filter=archive can still be used, but default is 'all'
        $filter = $request->get('filter', 'all');
        if (! in_array($filter, ['active', 'archive', 'all'], true)) {
            $filter = 'all';
        }

        $listStatus = $request->get('list_status', 'all');
        if (! in_array($listStatus, ['all', 'pending', 'approved', 'rejected'], true)) {
            $listStatus = 'all';
        }

        // Permanent
        $permQuery = SecurityParmIdApply::select($columns)->with($with)->orderBy('created_date', 'desc');
        if (!hasRole('Admin') && !hasRole('SuperAdmin')) {
            $currentUserId = Auth::user()->user_id ?? Auth::id();
            if ($currentUserId) {
                $permQuery->where('created_by', $currentUserId);
            }
        }
        // Non-admin users: show only their own requests (created_by = logged-in user)
        if (!hasRole('Admin') && !hasRole('SuperAdmin')) {
            $currentUserId = Auth::user()->user_id ?? Auth::id();
            if ($currentUserId) {
                $permQuery->where('created_by', $currentUserId);
            }
        }

        // Contractual
        $contCols = ['pk', 'emp_id_apply', 'employee_name', 'designation_name', 'id_status', 'depart_approval_status', 'department_approval_emp_pk', 'created_date', 'card_valid_from', 'card_valid_to', 'id_card_no', 'id_photo_path', 'mobile_no', 'telephone_no', 'blood_group', 'permanent_type', 'perm_sub_type', 'remarks', 'created_by', 'employee_dob', 'vender_name', 'father_name', 'doc_path'];
        $contQuery = DB::table('security_con_oth_id_apply')->select($contCols)->orderBy('created_date', 'desc');
        if (!hasRole('Admin') && !hasRole('SuperAdmin')) {
            $currentUserId = Auth::user()->user_id ?? Auth::id();
            if ($currentUserId) {
                $contQuery->where('created_by', $currentUserId);
            }
        }
        if (!hasRole('Admin') && !hasRole('SuperAdmin')) {
            $currentUserId = Auth::user()->user_id ?? Auth::id();
            if ($currentUserId) {
                $contQuery->where('created_by', $currentUserId);
            }
        }

        static::applyEmployeeIdcardListStatusFilters($permQuery, $contQuery, $filter, $listStatus);

        $permRows = $permQuery->get();
        $contRows = $contQuery->get();

        $permDto = $permRows->map(fn ($r) => IdCardSecurityMapper::toEmployeeRequestDto($r));
        $contDto = $contRows->map(fn ($r) => IdCardSecurityMapper::toContractualRequestDto($r));
        $merged = $permDto->concat($contDto)->sortByDesc('created_at')->values();

        // Build pending-stage tooltip text for status hover in list view.
        $approvalAuthorityIds = $merged->pluck('approval_authority')
            ->filter(fn ($v) => !empty($v))
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();
        $approvalAuthorityNames = [];
        if ($approvalAuthorityIds->isNotEmpty()) {
            $authIds = $approvalAuthorityIds->all();
            $hasPkOld = Schema::hasColumn('employee_master', 'pk_old');
            $cols = $hasPkOld ? ['pk', 'pk_old', 'first_name', 'last_name'] : ['pk', 'first_name', 'last_name'];
            $rows = DB::table('employee_master')
                ->where(function ($q) use ($authIds, $hasPkOld) {
                    $q->whereIn('pk', $authIds);
                    if ($hasPkOld) {
                        $q->orWhereIn('pk_old', $authIds);
                    }
                })
                ->get($cols);
            foreach ($rows as $row) {
                $label = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
                if ((int) ($row->pk ?? 0) > 0) {
                    $approvalAuthorityNames[(int) $row->pk] = $label;
                }
                if ($hasPkOld && isset($row->pk_old) && (int) $row->pk_old > 0) {
                    $approvalAuthorityNames[(int) $row->pk_old] = $label;
                }
            }
        }

        $merged = $merged->map(function ($row) use ($approvalAuthorityNames) {
            $status = (string) ($row->status ?? '');
            $row->pending_status_tooltip = null;
            if ($status !== 'Pending') {
                return $row;
            }

            $isContractual = (($row->employee_type ?? '') === 'Contractual Employee');
            $approvalAuthPk = !empty($row->approval_authority) ? (int) $row->approval_authority : null;
            $approvalAuthName = ($approvalAuthPk !== null && $approvalAuthPk > 0)
                ? ($approvalAuthorityNames[$approvalAuthPk] ?? null)
                : null;

            if (!empty($row->approved_by_a2)) {
                $row->pending_status_tooltip = 'Pending with Security Head';
                return $row;
            }

            // For contractual employees: check if security member has approved (status=1, recommend_status=1)
            if ($isContractual) {
                $contractualSecurityMemberApproved = DB::table('security_con_oth_id_apply_approval')
                    ->where('security_parm_id_apply_pk', $row->emp_id_apply ?? null)
                    ->where('status', 1)
                    ->where('recommend_status', 1)
                    ->exists();
                
                if ($contractualSecurityMemberApproved) {
                    $row->pending_status_tooltip = 'Pending with Security Head';
                    return $row;
                }
            }

            // For contractual employees: check if approved by section authority (depart_approval_status=2)
            // If so, it's now at Security Member level (not section level anymore)
            $isContractualApprovedBySection = $isContractual && (int) ($row->depart_approval_status ?? 0) === 2;
            
            $sectionApproved = !empty($row->approved_by_a1)
                || ((int) ($row->depart_approval_status ?? 0) === 2);

            if ($sectionApproved) {
                // If contractual and approved by section authority, show it's with Security Member now
                if ($isContractualApprovedBySection) {
                    $row->pending_status_tooltip = 'Pending with Security Member';
                } else {
                    $row->pending_status_tooltip = 'Pending with Security Section';
                    // For permanent or other cases: show the section head / approval authority
                    if ($isContractual && $approvalAuthName) {
                        $row->pending_status_tooltip .= ' (' . $approvalAuthName . ')';
                    }
                }
                return $row;
            }

            // For permanent employees: they bypass section head approval and go directly to security member
            if (!$isContractual) {
                $row->pending_status_tooltip = 'Pending with Security Member';
                return $row;
            }

            // Waiting for section-level approval first.
            $sectionEmployeeName = $approvalAuthName;
            if (!$isContractual && $sectionEmployeeName === null && !empty($row->requested_by)) {
                $sectionEmployeeName = (string) $row->requested_by;
            }
            $row->pending_status_tooltip = 'Pending with Section Employee'
                . ($sectionEmployeeName ? (' (' . $sectionEmployeeName . ')') : '');

            return $row;
        })->values();

        // Date range filter (created_date)
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        if ($dateFrom) {
            try {
                $from = \Carbon\Carbon::parse($dateFrom)->startOfDay();
                $merged = $merged->filter(fn ($r) => $r->created_at && $r->created_at->gte($from))->values();
            } catch (\Exception $e) {
            }
        }
        if ($dateTo) {
            try {
                $to = \Carbon\Carbon::parse($dateTo)->endOfDay();
                $merged = $merged->filter(fn ($r) => $r->created_at && $r->created_at->lte($to))->values();
            } catch (\Exception $e) {
            }
        }

        // Name search (case-insensitive)
        $search = trim($request->get('search', ''));
        if ($search !== '') {
            $searchLower = mb_strtolower($search);
            $merged = $merged->filter(function ($r) use ($searchLower) {
                $name = mb_strtolower($r->name ?? '');
                return str_contains($name, $searchLower);
            })->values();
        }

        $allRequests = $merged->values();
        $activeCollection = $allRequests
            ->filter(fn ($r) => ($r->status ?? '') === 'Pending')
            ->values();
        $archivedCollection = $allRequests
            ->filter(fn ($r) => in_array(($r->status ?? ''), ['Approved', 'Rejected'], true))
            ->values();
        $duplicationCollection = $allRequests
            ->filter(fn ($r) => in_array(($r->request_for ?? ''), ['Replacement', 'Duplication'], true) && ($r->status ?? '') === 'Approved')
            ->values();
        $extensionCollection = $allRequests
            ->filter(fn ($r) => ($r->request_for ?? '') === 'Extension' && ($r->status ?? '') === 'Approved')
            ->values();

        $perPage = (int) $request->get('per_page', 15);
        $perPage = $perPage >= 5 && $perPage <= 100 ? $perPage : 15;

        $activeRequests = static::paginateCollection($activeCollection, (int) $request->get('active_page', 1) ?: 1, $perPage, $request->url(), 'active_page');
        $activeRequests->withQueryString();
        // Combined list (all statuses in one list)
        $allRequestsPaged = static::paginateCollection($allRequests, (int) $request->get('page', 1) ?: 1, $perPage, $request->url(), 'page');
        $allRequestsPaged->withQueryString();
        $archivedRequests = static::paginateCollection($archivedCollection, (int) $request->get('archive_page', 1) ?: 1, $perPage, $request->url(), 'archive_page');
        $archivedRequests->withQueryString();
        $duplicationRequests = static::paginateCollection($duplicationCollection, (int) $request->get('duplication_page', 1) ?: 1, $perPage, $request->url(), 'duplication_page');
        $duplicationRequests->withQueryString();
        $extensionRequests = static::paginateCollection($extensionCollection, (int) $request->get('extension_page', 1) ?: 1, $perPage, $request->url(), 'extension_page');
        $extensionRequests->withQueryString();

        return view('admin.employee_idcard.index', [
            'allRequests' => $allRequestsPaged,
            'activeRequests' => $activeRequests,
            'archivedRequests' => $archivedRequests,
            'duplicationRequests' => $duplicationRequests,
            'extensionRequests' => $extensionRequests,
            'filter' => $filter,
            'list_status' => $listStatus,
            'dateFrom' => $dateFrom ?? '',
            'dateTo' => $dateTo ?? '',
            'search' => $search ?? '',
        ]);
    }

    /**
     * Apply id_status for employee ID card list (permanent Eloquent + contractual query builder).
     * When list_status is pending/approved/rejected it overrides filter for status; otherwise filter (active/archive/all) applies.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $permQuery
     * @param  \Illuminate\Database\Query\Builder  $contQuery
     */
    private static function applyEmployeeIdcardListStatusFilters($permQuery, $contQuery, string $filter, string $listStatus): void
    {
        if ($listStatus === 'pending') {
            $permQuery->where('id_status', SecurityParmIdApply::ID_STATUS_PENDING);
            $contQuery->where('id_status', 1);

            return;
        }
        if ($listStatus === 'approved') {
            $permQuery->where('id_status', SecurityParmIdApply::ID_STATUS_APPROVED);
            $contQuery->where('id_status', 2);

            return;
        }
        if ($listStatus === 'rejected') {
            $permQuery->where('id_status', SecurityParmIdApply::ID_STATUS_REJECTED);
            $contQuery->where('id_status', 3);

            return;
        }

        if ($filter === 'active') {
            $permQuery->where('id_status', SecurityParmIdApply::ID_STATUS_PENDING);
            $contQuery->where('id_status', 1);
        } elseif ($filter === 'archive') {
            $permQuery->whereIn('id_status', [SecurityParmIdApply::ID_STATUS_APPROVED, SecurityParmIdApply::ID_STATUS_REJECTED]);
            $contQuery->whereIn('id_status', [2, 3]);
        }
    }

    /**
     * Paginate a collection with custom page name (for tab-specific pagination).
     */
    private static function paginateCollection(\Illuminate\Support\Collection $collection, int $currentPage, int $perPage, string $path, string $pageName): LengthAwarePaginator
    {
        $total = $collection->count();
        $slice = $collection->forPage($currentPage, $perPage)->values();
        $paginator = new LengthAwarePaginator($slice, $total, $perPage, $currentPage, ['path' => $path, 'pageName' => $pageName]);
        return $paginator;
    }

    /**
     * Approved **permanent** ID card for this employee (security_parm_id_apply).
     * Used when blocking a new Permanent "Own ID Card" — contractual approvals must not block that flow.
     */
    private static function hasApprovedPermanentIdCard(int $employeePk): bool
    {
        $subjectPks = static::employeeLinkedSubjectMasterPks($employeePk);
        if ($subjectPks === []) {
            return false;
        }

        return SecurityParmIdApply::whereIn('employee_master_pk', $subjectPks)
            ->where('id_status', SecurityParmIdApply::ID_STATUS_APPROVED)
            ->exists();
    }

    /**
     * employee_master.pk values that may appear as security_parm_id_apply.employee_master_pk (pk and pk_old).
     *
     * @return list<int>
     */
    private static function employeeLinkedSubjectMasterPks(int $employeeMasterPk): array
    {
        if ($employeeMasterPk <= 0) {
            return [];
        }
        $hasPkOld = Schema::hasColumn('employee_master', 'pk_old');
        $cols = $hasPkOld ? ['pk', 'pk_old'] : ['pk'];
        $row = DB::table('employee_master')
            ->where('pk', $employeeMasterPk)
            ->when($hasPkOld, fn ($q) => $q->orWhere('pk_old', $employeeMasterPk))
            ->first($cols);
        if (! $row) {
            return [$employeeMasterPk];
        }
        $ids = [(int) ($row->pk ?? $employeeMasterPk)];
        if ($hasPkOld && isset($row->pk_old) && (int) $row->pk_old > 0 && (int) $row->pk_old !== (int) ($row->pk ?? 0)) {
            $ids[] = (int) $row->pk_old;
        }

        return array_values(array_unique(array_filter($ids, fn ($v) => (int) $v > 0)));
    }

    /**
     * Approved contractual ID card application for this employee in security_con_oth_id_apply (any approved status).
     * Matches created_by to employee pk and pk_old where applicable.
     */
    private static function hasApprovedContractualIdApplyForEmployee(int $employeePk): bool
    {
        $ids = static::employeeLinkedCreatedByIds($employeePk);
        if ($ids === []) {
            return false;
        }

        return DB::table('security_con_oth_id_apply')
            ->whereIn('created_by', $ids)
            ->where('id_status', SecurityParmIdApply::ID_STATUS_APPROVED)
            ->exists();
    }

    /**
     * Check if the given employee (pk) already has an approved ID card (Permanent or Contractual row they created).
     */
    private static function hasApprovedIdCard(int $employeePk): bool
    {
        if (static::hasApprovedPermanentIdCard($employeePk)) {
            return true;
        }

        return static::hasApprovedContractualIdApplyForEmployee($employeePk);
    }

    /**
     * Check if the given employee has an approved ID card that is still valid today.
     * Treat null card_valid_to as still valid.
     */
    private static function hasValidApprovedIdCard(int $employeePk): bool
    {
        $canonical = (int) (IdCardSecurityMapper::resolveCanonicalEmployeeMasterPk($employeePk) ?? $employeePk);
        $today = now()->toDateString();

        if (IdCardSecurityMapper::hasOpenEndedApprovedEmployeeIdCard($canonical, 'Permanent Employee')) {
            return true;
        }
        $permEnd = IdCardSecurityMapper::approvedEmployeeIdCardValidityEnd($canonical, 'Permanent Employee');
        if ($permEnd && $permEnd->format('Y-m-d') >= $today) {
            return true;
        }

        if (IdCardSecurityMapper::hasOpenEndedApprovedEmployeeIdCard($canonical, 'Contractual Employee')) {
            return true;
        }
        $contEnd = IdCardSecurityMapper::approvedEmployeeIdCardValidityEnd($canonical, 'Contractual Employee');
        if ($contEnd && $contEnd->format('Y-m-d') >= $today) {
            return true;
        }

        return false;
    }

    private static function normalizeBeneficiaryNameKey(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $name)));
    }

    private static function normalizeMobileDigits(?string $mobile): ?string
    {
        if ($mobile === null || trim((string) $mobile) === '') {
            return null;
        }
        $digits = preg_replace('/\D+/', '', (string) $mobile);

        return $digits !== '' ? $digits : null;
    }

    /**
     * Pending contractual row for the same real-world person.
     * Name matching is done in PHP with {@see normalizeBeneficiaryNameKey} so it matches DB values that only differ
     * by internal spaces/tabs (SQL LOWER(TRIM(...)) alone does not collapse "A  B" vs "A B").
     */
    private static function hasPendingContractualByBeneficiaryIdentity(string $beneficiaryName, ?string $mobileRaw, ?string $dobYmd): bool
    {
        $nameKey = static::normalizeBeneficiaryNameKey($beneficiaryName);
        if ($nameKey === '') {
            return false;
        }

        $rows = DB::table('security_con_oth_id_apply')
            ->where('id_status', SecurityParmIdApply::ID_STATUS_PENDING)
            ->get(['employee_name', 'mobile_no', 'employee_dob']);

        $mobileDigits = static::normalizeMobileDigits($mobileRaw);

        foreach ($rows as $row) {
            if (static::normalizeBeneficiaryNameKey((string) ($row->employee_name ?? '')) !== $nameKey) {
                continue;
            }

            if ($mobileDigits !== null) {
                $rowM = static::normalizeMobileDigits($row->mobile_no ?? null);
                if ($rowM === $mobileDigits || $rowM === null) {
                    return true;
                }

                continue;
            }

            if ($dobYmd) {
                $rowDob = null;
                if (! empty($row->employee_dob)) {
                    try {
                        $rowDob = \Carbon\Carbon::parse($row->employee_dob)->toDateString();
                    } catch (\Exception $e) {
                        $rowDob = null;
                    }
                }
                if ($rowDob === null || $rowDob === $dobYmd) {
                    return true;
                }

                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * All employee_master identifiers that may appear in security_con_oth_id_apply.created_by (pk and pk_old).
     *
     * @return list<int>
     */
    private static function employeeLinkedCreatedByIds(?int $employeeMasterPk): array
    {
        if ($employeeMasterPk === null || $employeeMasterPk <= 0) {
            return [];
        }
        $hasPkOld = Schema::hasColumn('employee_master', 'pk_old');
        $cols = $hasPkOld ? ['pk', 'pk_old'] : ['pk'];
        $row = DB::table('employee_master')->where('pk', $employeeMasterPk)->first($cols);
        if (! $row) {
            return [(int) $employeeMasterPk];
        }
        $ids = [(int) ($row->pk ?? $employeeMasterPk)];
        if ($hasPkOld && isset($row->pk_old) && (int) $row->pk_old > 0 && (int) $row->pk_old !== (int) ($row->pk ?? 0)) {
            $ids[] = (int) $row->pk_old;
        }

        return array_values(array_unique(array_filter($ids, fn ($v) => (int) $v > 0)));
    }

    /**
     * True if this employee already has a pending (not approved/rejected) permanent ID card application.
     */
    private static function hasPendingPermanentIdCardRequest(int $employeeMasterPk): bool
    {
        return SecurityParmIdApply::where('employee_master_pk', $employeeMasterPk)
            ->where('id_status', SecurityParmIdApply::ID_STATUS_PENDING)
            ->exists();
    }

    /**
     * Pending permanent application whose linked employee name matches (PHP-normalized), for cases where
     * employee_master_pk was not resolved on the form but the beneficiary is the same person.
     */
    private static function hasPendingPermanentMatchingBeneficiaryName(string $beneficiaryName): bool
    {
        $nameKey = static::normalizeBeneficiaryNameKey($beneficiaryName);
        if ($nameKey === '') {
            return false;
        }

        $rows = DB::table('security_parm_id_apply as spa')
            ->join('employee_master as em', 'em.pk', '=', 'spa.employee_master_pk')
            ->where('spa.id_status', SecurityParmIdApply::ID_STATUS_PENDING)
            ->select(['em.first_name', 'em.last_name'])
            ->get();

        foreach ($rows as $r) {
            $full = static::normalizeBeneficiaryNameKey(trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? '')));
            if ($full === $nameKey) {
                return true;
            }
        }

        return false;
    }

    /**
     * True if a pending contractual row exists for the same beneficiary (normalized name) and the same
     * submitter/subject id(s) including pk_old. Also includes $rawAuthUserId (session user_id) because
     * created_by is sometimes stored as login id rather than resolved employee_master.pk.
     * Does not filter on DOB — old rows may have NULL employee_dob, which previously let duplicates through.
     *
     * @param  int|string|null  $rawAuthUserId
     */
    private static function hasPendingContractualBeneficiaryRequest(int $authEmployeePk, int $subjectEmployeePk, string $beneficiaryName, $rawAuthUserId = null): bool
    {
        if ($authEmployeePk <= 0) {
            return false;
        }
        $nameKey = static::normalizeBeneficiaryNameKey($beneficiaryName);
        if ($nameKey === '') {
            return false;
        }

        $idSet = array_merge(
            static::employeeLinkedCreatedByIds($authEmployeePk),
            static::employeeLinkedCreatedByIds($subjectEmployeePk),
        );
        if ($rawAuthUserId !== null && $rawAuthUserId !== '') {
            $idSet[] = (int) $rawAuthUserId;
        }
        $idSet = array_values(array_unique(array_filter($idSet, fn ($v) => (int) $v > 0)));
        if ($idSet === []) {
            return false;
        }

        $rows = DB::table('security_con_oth_id_apply')
            ->where('id_status', SecurityParmIdApply::ID_STATUS_PENDING)
            ->whereIn('created_by', $idSet)
            ->get(['employee_name']);

        foreach ($rows as $row) {
            if (static::normalizeBeneficiaryNameKey((string) ($row->employee_name ?? '')) === $nameKey) {
                return true;
            }
        }

        return false;
    }

    /**
     * Pending new-card request for this subject: permanent row, contractual row matched by beneficiary identity
     * (name + mobile / DOB / name-only), or contractual row matched by created_by. Works when the logged-in user
     * has no employee_master row (e.g. Admin) — authEmployeePk may be null.
     *
     * @param  int|string|null  $rawAuthUserId
     */
    private static function hasAnyPendingNewEmployeeIdCardRequest(
        int $subjectEmployeePk,
        string $beneficiaryName,
        ?int $authEmployeePk,
        $rawAuthUserId = null,
        ?string $mobileRaw = null,
        ?string $dobYmd = null
    ): bool {
        if (static::normalizeBeneficiaryNameKey($beneficiaryName) === '') {
            return false;
        }

        if ($subjectEmployeePk > 0 && static::hasPendingPermanentIdCardRequest($subjectEmployeePk)) {
            return true;
        }

        if (static::hasPendingPermanentMatchingBeneficiaryName($beneficiaryName)) {
            return true;
        }

        if (static::hasPendingContractualByBeneficiaryIdentity($beneficiaryName, $mobileRaw, $dobYmd)) {
            return true;
        }

        if ($authEmployeePk !== null && (int) $authEmployeePk > 0) {
            $subj = $subjectEmployeePk > 0 ? $subjectEmployeePk : (int) $authEmployeePk;

            return static::hasPendingContractualBeneficiaryRequest((int) $authEmployeePk, $subj, $beneficiaryName, $rawAuthUserId);
        }

        return false;
    }

    public function create()
    {
        $authUserId = Auth::user()->user_id ?? Auth::id();

        $cardTypes = DB::table('sec_id_cardno_master')->orderBy('sec_card_name')->pluck('sec_card_name');

        // Contractual: Section = logged-in user's department only; Approval Authority = same department employees with Payroll=0 (permanent)
        $userDepartmentPk = null;
        $userDepartmentName = null;
        $approvalAuthorityEmployees = collect();
        $lockedEmployeeType = null;
        if ($authUserId) {
            $authEmp = EmployeeMaster::with('department')
                ->where('pk', $authUserId)
                ->orWhere('pk_old', $authUserId)
                ->first();
            if ($authEmp && Schema::hasColumn('employee_master', 'payroll')) {
                $payroll = (int) ($authEmp->payroll ?? 0);
                $lockedEmployeeType = $payroll === 0 ? 'Permanent Employee' : 'Contractual Employee';
            }
            if ($authEmp && $authEmp->department_master_pk) {
                $userDepartmentPk = $authEmp->department_master_pk;
                $userDepartmentName = $authEmp->department->department_name ?? null;
                // Same department, Payroll = 0 (permanent) for Approval Authority dropdown
                $approvalAuthorityEmployees = EmployeeMaster::with('designation')
                    ->where('department_master_pk', $userDepartmentPk)
                    ->when(Schema::hasColumn('employee_master', 'payroll'), fn ($q) => $q->where('payroll', 0))
                    ->when(Schema::hasColumn('employee_master', 'status'), fn ($q) => $q->where('status', 1))
                    ->orderBy('first_name')
                    ->orderBy('last_name')
                    ->get(['pk', 'first_name', 'last_name', 'designation_master_pk']);
            }
        }

        return view('admin.employee_idcard.create', [
            'cardTypes' => $cardTypes,
            'userDepartmentPk' => $userDepartmentPk,
            'userDepartmentName' => $userDepartmentName,
            'approvalAuthorityEmployees' => $approvalAuthorityEmployees,
            'lockedEmployeeType' => $lockedEmployeeType,
        ]);
    }

    /**
     * AJAX: Sub-types for selected card type + employee type (for dropdown).
     */
    public function subTypes(Request $request)
    {
        $cardType = $request->get('card_type');
        $employeeType = $request->get('employee_type', 'Permanent Employee');
        $code = $employeeType === 'Permanent Employee' ? 'p' : 'c';
        $masterPk = IdCardSecurityLookup::resolveCardMasterPk($cardType);
        if (!$masterPk) {
            return response()->json(['sub_types' => []]);
        }
        $rows = DB::table('sec_id_cardno_config_map')
            ->where('card_name', $code)
            ->where('sec_id_cardno_master', $masterPk)
            ->where('active_inactive', 1)
            ->whereNotNull('config_name')
            ->where('config_name', '!=', '')
            ->orderBy('config_name')
            ->get(['pk', 'config_name']);
        $subTypes = $rows->map(fn ($r) => ['value' => $r->config_name, 'text' => $r->config_name])->values();
        return response()->json(['sub_types' => $subTypes]);
    }

    /**
     * AJAX: Logged-in user's employee details for autofill on create form (Permanent + Contractual "Own ID Card").
     * Resolves employee by pk or pk_old so data is found either way.
     */
    public function me()
    {
        $userId = Auth::user()->user_id ?? Auth::id();
        if (! $userId) {
            return response()->json(['employee' => null]);
        }
        $emp = EmployeeMaster::with('designation')
            ->where(function ($q) use ($userId) {
                $q->where('pk', $userId)->orWhere('pk_old', $userId);
            })
            ->first();
        if (!$emp) {
            return response()->json(['employee' => null]);
        }
        // New ID card requests: valid-up-to is always one calendar year from today on this form
        // (do not reuse an old card's expiry — that caused empty or wrong dates for first-time applicants).
        $idCardValidUpto = \Carbon\Carbon::today()->addYear()->format('Y-m-d');
        $name = trim($emp->first_name . ' ' . ($emp->middle_name ?? '') . ' ' . ($emp->last_name ?? ''));
        $designation = $emp->designation->designation_name ?? null;
        $dob = $emp->dob ? \Carbon\Carbon::parse($emp->dob)->format('Y-m-d') : null;
        $doj = $emp->doj ? \Carbon\Carbon::parse($emp->doj)->format('Y-m-d') : null;
        $mobile = $emp->mobile ? (string) $emp->mobile : null;
        $telephone = $emp->landline_contact_no ? (string) $emp->landline_contact_no : ($emp->residence_no ?? null);
        return response()->json([
            'employee' => [
                'employee_master_pk' => (int) $emp->pk,
                'name' => $name,
                'designation' => $designation,
                'date_of_birth' => $dob,
                'father_name' => $emp->father_name,
                'academy_joining' => $doj,
                'mobile_number' => $mobile,
                'telephone_number' => $telephone,
                'id_card_valid_upto' => $idCardValidUpto,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $authUserId = Auth::user()->user_id ?? Auth::id();
        $authEmployeePk = null;
        if ($authUserId) {
            $authEmp = EmployeeMaster::where('pk', $authUserId)->orWhere('pk_old', $authUserId)->first();
            $authEmployeePk = $authEmp?->pk;
        }

        $validated = $request->validate([
            'employee_type' => 'required|in:Permanent Employee,Contractual Employee',
            'card_type' => 'required|string|max:100',
            'sub_type' => 'required|string|max:100',
            'request_for' => 'nullable|string|max:100|in:Own ID Card,Others ID Card,Family ID Card,Replacement,Duplication,Extension',
            'duplication_reason' => 'nullable|string|in:Expired Card,Lost,Damage',
            'name' => 'required|string|max:255',
            'designation' => 'required_if:employee_type,Contractual Employee|nullable|string|max:255',
            'date_of_birth' => 'required_if:employee_type,Contractual Employee|nullable|date',
            'father_name' => 'required_if:employee_type,Contractual Employee|nullable|string|max:255',
            'academy_joining' => 'required_if:employee_type,Contractual Employee|nullable|date',
            'id_card_valid_upto' => 'required_if:employee_type,Contractual Employee|nullable|date|after:today',
            'id_card_valid_from' => 'nullable|string|max:50',
            'id_card_number' => 'nullable|string|max:50',
            'mobile_number' => 'required_if:employee_type,Contractual Employee|nullable|string|max:20',
            'telephone_number' => 'nullable|string|max:20',
            'blood_group' => 'nullable|string|max:10',
            'section' => 'required_if:employee_type,Contractual Employee|nullable|string|max:255',
            'approval_authority' => 'required_if:employee_type,Contractual Employee|nullable|string|max:255',
            'vendor_organization_name' => 'required_if:employee_type,Contractual Employee|nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'photo_perm' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'photo_cont' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'joining_letter' => 'nullable|mimes:pdf,doc,docx|max:5120',
            'fir_receipt' => 'required_if:duplication_reason,Lost|nullable|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120',
            'payment_receipt' => 'nullable|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120',
            'documents' => [
                'nullable',
                'file',
                Rule::requiredIf(function () use ($request) {
                    $dup = in_array($request->input('request_for') ?? '', ['Replacement', 'Duplication', 'Extension'], true);

                    return ($request->input('employee_type') === 'Contractual Employee') && ! $dup;
                }),
                'mimes:pdf,doc,docx',
                'max:5120',
            ],
            'remarks' => 'nullable|string',
            'employee_master_pk' => 'nullable|integer|exists:employee_master,pk',
        ], [
            'fir_receipt.required_if' => 'FIR Receipt is required when the card is reported as Lost.',
            'approval_authority.required_if' => 'Approval Authority is required for Contractual Employees.',
            'designation.required_if' => 'Designation is required for Contractual Employees.',
            'date_of_birth.required_if' => 'Date of Birth is required for Contractual Employees.',
            'father_name.required_if' => 'Father Name is required for Contractual Employees.',
            'academy_joining.required_if' => 'Academy Joining is required for Contractual Employees.',
            'id_card_valid_upto.required_if' => 'ID Card Valid Upto is required for Contractual Employees.',
            'mobile_number.required_if' => 'Mobile Number is required for Contractual Employees.',
            'section.required_if' => 'Section is required for Contractual Employees.',
            'vendor_organization_name.required_if' => 'Vendor / Organization Name is required for Contractual Employees.',
            'id_card_valid_upto.after' => 'ID Card Valid Upto date must be a future date.',
            'documents.required' => 'Please upload a supporting document (PDF or DOC, max 5 MB): include appointment letter, joining letter, contract/engagement order, or similar proof as applicable.',
        ]);

        if (($validated['employee_type'] ?? '') === 'Contractual Employee') {
            $validated['request_for'] = 'Others ID Card';
        }

        $authEmpPk = Auth::user()->user_id ?? Auth::id();
        $authEmpRow = null;
        if ($authEmpPk) {
            $authEmpRow = EmployeeMaster::where('pk', $authEmpPk)->orWhere('pk_old', $authEmpPk)->first();
        }
        if ($authEmpRow && Schema::hasColumn('employee_master', 'payroll')) {
            $isContractual = ((int) ($authEmpRow->payroll ?? 0) !== 0);
            if ($isContractual && ($validated['employee_type'] ?? null) !== 'Contractual Employee') {
                throw ValidationException::withMessages([
                    'employee_type' => 'You can apply only as Contractual Employee.',
                ]);
            }
        }

        // Contractual request: Approval Authority must be a permanent employee from same section.
        if (($validated['employee_type'] ?? null) === 'Contractual Employee') {
            $approvalAuthorityPk = (int) ($validated['approval_authority'] ?? 0);
            $authDeptPk = (int) ($authEmpRow->department_master_pk ?? 0);
            $isValidApprovalAuthority = false;
            if ($approvalAuthorityPk > 0 && $authDeptPk > 0) {
                $approverQuery = EmployeeMaster::query()
                    ->where('pk', $approvalAuthorityPk)
                    ->where('department_master_pk', $authDeptPk)
                    ->when(Schema::hasColumn('employee_master', 'payroll'), fn ($q) => $q->where('payroll', 0))
                    ->when(Schema::hasColumn('employee_master', 'status'), fn ($q) => $q->where('status', 1));
                $isValidApprovalAuthority = $approverQuery->exists();
            }
            if (!$isValidApprovalAuthority) {
                throw ValidationException::withMessages([
                    'approval_authority' => 'Approval Authority must be a permanent employee from your section only.',
                ]);
            }
        }

        $employeePk = $validated['employee_master_pk'] ?? $authEmpPk;
        // Resolve to actual employee_master.pk (user_id may be pk or pk_old)
        if ($employeePk) {
            $empRow = EmployeeMaster::where('pk', $employeePk)->orWhere('pk_old', $employeePk)->first();
            if ($empRow) {
                $employeePk = $empRow->pk;
            }
        }
        if (!$employeePk && !empty($validated['name'])) {
            $emp = EmployeeMaster::where(DB::raw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))"), 'like', '%' . trim($validated['name']) . '%')->first();
            $employeePk = $emp?->pk;
        }

        // Beneficiary master pk when explicitly chosen (Others/Family). Do not use auth fallback — used for pending checks only.
        $beneficiaryMasterPkFromForm = null;
        if (! empty($validated['employee_master_pk'])) {
            $rawBenef = (int) $validated['employee_master_pk'];
            $benefRow = EmployeeMaster::where('pk', $rawBenef)->orWhere('pk_old', $rawBenef)->first();
            $beneficiaryMasterPkFromForm = $benefRow ? (int) $benefRow->pk : $rawBenef;
        }

        // Restrict NEW *own* ID card only when user already has an approved card.
        // Do NOT block when request is for "Others ID Card" or "Family ID Card" (applying for someone else).
        $requestFor = $validated['request_for'] ?? 'Own ID Card';
        if ($requestFor === '') {
            $requestFor = 'Own ID Card';
        }
        $isDupOrExt = in_array($requestFor, ['Replacement', 'Duplication', 'Extension'], true);
        $isApplyingForAnotherEmployee = in_array($requestFor, ['Others ID Card', 'Family ID Card'], true);
        // Others/Family: never treat as "self" even if employee_master_pk fell back to the logged-in user.
        $isForSelf = ! $isApplyingForAnotherEmployee
            && $employeePk
            && $authEmployeePk
            && (int) $employeePk === (int) $authEmployeePk;
        $employeeType = $validated['employee_type'];
        // Own fresh card: block when an approved card already exists (permanent vs contractual rules differ).
        $blockOwnNewCard = $isForSelf
            && $requestFor === 'Own ID Card'
            && ! $isDupOrExt;
        if ($blockOwnNewCard) {
            if ($employeeType === 'Permanent Employee') {
                if (static::hasApprovedPermanentIdCard((int) $employeePk)) {
                    throw ValidationException::withMessages([
                        'employee_type' => 'You already have an approved ID card. A new request for yourself cannot be created. For duplicate or extension, use the Duplicate ID Card or relevant option.',
                    ]);
                }
            } else {
                if (static::hasApprovedContractualIdApplyForEmployee((int) $employeePk)) {
                    throw ValidationException::withMessages([
                        'employee_type' => 'You already have an approved ID card. A new request for yourself cannot be created. For duplicate or extension, use the Duplicate ID Card or relevant option.',
                    ]);
                }
                if (static::hasApprovedPermanentIdCard((int) $employeePk)) {
                    throw ValidationException::withMessages([
                        'employee_type' => 'You already have an approved ID card. A new request for yourself cannot be created. For duplicate or extension, use the Duplicate ID Card or relevant option.',
                    ]);
                }
            }
        }

        // Only block when the contractual applicant is requesting their *own* card (not Others/Family).
        $isContractualSelfRequest = $isForSelf
            && $employeeType === 'Contractual Employee'
            && ($requestFor ?? '') === 'Own ID Card';
        if ($isContractualSelfRequest && static::hasValidApprovedIdCard((int) $employeePk)) {
            throw ValidationException::withMessages([
                'employee_type' => 'You already have a valid ID card. You can raise a new request for yourself only after expiry, or apply for another person.',
            ]);
        }

        // Block a second *new* ID card request while one is already pending (generation flow only).
        $requestForPending = ($requestFor !== '' && $requestFor !== null) ? $requestFor : 'Own ID Card';
        if (! $isDupOrExt && in_array($requestForPending, ['Own ID Card', 'Others ID Card'], true)) {
            $pendingMsg = 'An Employee ID Card request is already pending for this employee. Please wait until it is approved or rejected before submitting a new one.';
            $beneficiaryName = trim((string) ($validated['name'] ?? ''));
            // Others/Family: do not use applicant's employee_master_pk for security_parm_id_apply pending lookup
            // (that table row may exist from another creator and never appear in this user's list).
            $pendingPermanentSubjectPk = $isApplyingForAnotherEmployee
                ? (int) ($beneficiaryMasterPkFromForm ?? 0)
                : (int) ($employeePk ?: ($authEmployeePk ?? 0));
            $rawSessionUserId = Auth::user()->user_id ?? Auth::id();
            $pendingDobYmd = ! empty($validated['date_of_birth'])
                ? static::parseDateToYmd($validated['date_of_birth'])
                : null;
            $pendingMobile = $validated['mobile_number'] ?? null;
            if ($beneficiaryName !== ''
                && static::hasAnyPendingNewEmployeeIdCardRequest(
                    $pendingPermanentSubjectPk,
                    $beneficiaryName,
                    $authEmployeePk,
                    $rawSessionUserId,
                    $pendingMobile,
                    $pendingDobYmd
                )) {
                throw ValidationException::withMessages([
                    'employee_type' => $pendingMsg,
                ]);
            }
        }

        $cardNameCode = $employeeType === 'Permanent Employee' ? 'p' : 'c';
        $cardMasterPk = IdCardSecurityLookup::resolveCardMasterPk($validated['card_type']);
        if (!$cardMasterPk) {
            throw ValidationException::withMessages([
                'card_type' => 'Invalid Card Type for security mapping. Please select a valid card type.',
            ]);
        }
        $configMap = IdCardSecurityLookup::resolveConfigMapRow($cardNameCode, $cardMasterPk, $validated['sub_type']);
        if (!$configMap) {
            throw ValidationException::withMessages([
                'sub_type' => 'Invalid Sub Type for the selected Card Type (security mapping not found).',
            ]);
        }

        $photoPath = null;
        if ($employeeType === 'Permanent Employee' && $request->hasFile('photo_perm')) {
            $photoPath = $request->file('photo_perm')->store('idcard/photos', 'public');
        } elseif ($employeeType === 'Contractual Employee' && $request->hasFile('photo_cont')) {
            $photoPath = $request->file('photo_cont')->store('idcard/photos', 'public');
        } elseif ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('idcard/photos', 'public');
        }
        $joiningLetterPath = null;
        if ($request->hasFile('joining_letter')) {
            $joiningLetterPath = $request->file('joining_letter')->store('idcard/joining_letters', 'public');
        }

        $cardValidFrom = null;
        $cardValidTo = null;
        if (!empty($validated['id_card_valid_from'])) {
            $cardValidFrom = static::parseDateToYmd($validated['id_card_valid_from']);
        }
        if (!empty($validated['id_card_valid_upto'])) {
            $cardValidTo = static::parseDateToYmd($validated['id_card_valid_upto']);
        }
        // Permanent Own ID Card: default card_valid_from to today if not provided
        if ($cardValidFrom === null && $employeeType === 'Permanent Employee' && !$isDupOrExt) {
            $cardValidFrom = now()->format('Y-m-d');
        }

        $now = now()->format('Y-m-d H:i:s');

        // Build approver chain from idcard_request_approvar_master_new
        $statusFlagColumn = $employeeType === 'Permanent Employee' ? 'per_status' : 'cont_status';
        if ($isDupOrExt) {
            $statusFlagColumn = 'duplicate_status';
        }
        $approvers = DB::table('idcard_request_approvar_master_new')
            ->where('employee_master_pk', $employeePk)
            ->where('type', 'employee')
            ->where($statusFlagColumn, 1)
            ->orderBy('sequence', 'asc')
            ->pluck('employees_pk')
            ->filter()
            ->values();

        // Contractual: section column is bigint (department_master_pk). Resolve logged-in user's department.
        $userDepartmentPk = null;
        if ($employeeType === 'Contractual Employee' && $authEmpPk) {
            $authEmp = EmployeeMaster::where('pk', $authEmpPk)->orWhere('pk_old', $authEmpPk)->first(['department_master_pk']);
            $userDepartmentPk = $authEmp->department_master_pk ?? null;
        }

        DB::transaction(function () use (
            $employeeType,
            $employeePk,
            $authEmpPk,
            $validated,
            $cardNameCode,
            $cardMasterPk,
            $configMap,
            $photoPath,
            $joiningLetterPath,
            $cardValidFrom,
            $cardValidTo,
            $now,
            $approvers,
            $isDupOrExt,
            $request,
            $userDepartmentPk
        ) {
            // If user is applying for duplication/extension via this form,
            // insert into duplication tables; otherwise insert into normal apply tables.
            if ($isDupOrExt) {
                if ($employeeType === 'Permanent Employee') {
                    $nextPk = (int) DB::table('security_dup_perm_id_apply')->max('pk') + 1;
                    $applyId = 'DUP' . str_pad((string) $nextPk, 5, '0', STR_PAD_LEFT);

                    $paymentReceipt = null;
                    if ($request->hasFile('payment_receipt')) {
                        $ext = $request->file('payment_receipt')->getClientOriginalExtension();
                        $file = $applyId . '_PAY_' . time() . '.' . $ext;
                        $request->file('payment_receipt')->storeAs('idcard/dup_docs', $file, 'public');
                        $paymentReceipt = $file;
                    }

                    $firDoc = null;
                    if ($request->hasFile('fir_receipt')) {
                        $ext = $request->file('fir_receipt')->getClientOriginalExtension();
                        $file = $applyId . '_FIR_' . time() . '.' . $ext;
                        $request->file('fir_receipt')->storeAs('idcard/dup_docs', $file, 'public');
                        $firDoc = $file;
                    }

                    $serviceExt = null;
                    if ($request->hasFile('joining_letter')) {
                        $ext = $request->file('joining_letter')->getClientOriginalExtension();
                        $file = $applyId . '_EXT_' . time() . '.' . $ext;
                        $request->file('joining_letter')->storeAs('idcard/dup_docs', $file, 'public');
                        $serviceExt = $file;
                    }

                    $reason = $validated['duplication_reason'] ?? null;
                    $cardReason = match ($validated['request_for'] ?? '') {
                        'Extension' => 'Service Extended',
                        default => match ($reason) {
                            'Lost' => 'Card Lost',
                            'Damage' => 'Damage Card',
                            'Expired Card' => 'Expired Card',
                            default => ($validated['request_for'] ?? 'Duplication'),
                        },
                    };

                    $emp = EmployeeMaster::select(['pk', 'designation_master_pk'])->find($employeePk);
                    DB::table('security_dup_perm_id_apply')->insert([
                        'emp_id_apply' => $applyId,
                        'employee_master_pk' => $employeePk,
                        'designation_pk' => $emp?->designation_master_pk,
                        'card_valid_from' => $cardValidFrom,
                        'card_valid_to' => $cardValidTo,
                        'id_card_no' => $validated['id_card_number'] ?? null,
                        'id_status' => SecurityParmIdApply::ID_STATUS_PENDING,
                        'remarks' => $validated['remarks'] ?? null,
                        'created_by' => $employeePk ?? $authEmpPk,
                        'created_date' => $now,
                        'id_photo_path' => $photoPath,
                        'employee_dob' => $validated['date_of_birth'] ?? null,
                        'mobile_no' => $validated['mobile_number'] ?? null,
                        'blood_group' => $validated['blood_group'] ?? null,
                        'payment_receipt' => $paymentReceipt,
                        'fir_doc' => $firDoc,
                        'service_ext' => ($validated['request_for'] ?? '') === 'Extension' ? $serviceExt : null,
                        'parent_id' => null,
                        'card_reason' => $cardReason,
                        'name_change_doc' => null,
                    ]);

                    // Case 2 - Extension/Duplicate ID Card (Own Permanent): Only security_dup_perm_id_apply at request time.
                    // security_dup_perm_id_apply_approval rows are inserted when approvers approve.
                } else {
                    $nextPk = (int) DB::table('security_dup_other_id_apply')->max('pk') + 1;
                    $applyId = 'DUO' . str_pad((string) $nextPk, 5, '0', STR_PAD_LEFT);

                    $paymentReceipt = null;
                    if ($request->hasFile('payment_receipt')) {
                        $ext = $request->file('payment_receipt')->getClientOriginalExtension();
                        $file = $applyId . '_PAY_' . time() . '.' . $ext;
                        $request->file('payment_receipt')->storeAs('idcard/dup_docs', $file, 'public');
                        $paymentReceipt = $file;
                    }

                    $firDoc = null;
                    if ($request->hasFile('fir_receipt')) {
                        $ext = $request->file('fir_receipt')->getClientOriginalExtension();
                        $file = $applyId . '_FIR_' . time() . '.' . $ext;
                        $request->file('fir_receipt')->storeAs('idcard/dup_docs', $file, 'public');
                        $firDoc = $file;
                    }

                    $serviceExt = null;
                    if ($request->hasFile('documents')) {
                        $ext = $request->file('documents')->getClientOriginalExtension();
                        $file = $applyId . '_DOC_' . time() . '.' . $ext;
                        $request->file('documents')->storeAs('idcard/dup_docs', $file, 'public');
                        $serviceExt = $file;
                    }

                    $reason = $validated['duplication_reason'] ?? null;
                    $cardReason = match ($validated['request_for'] ?? '') {
                        'Extension' => 'Service Extended',
                        default => match ($reason) {
                            'Lost' => 'Card Lost',
                            'Damage' => 'Damage Card',
                            'Expired Card' => 'Expired Card',
                            default => ($validated['request_for'] ?? 'Duplication'),
                        },
                    };

                    DB::table('security_dup_other_id_apply')->insert([
                        'emp_id_apply' => $applyId,
                        'employee_name' => $validated['name'] ?? null,
                        'designation_name' => $validated['designation'] ?? null,
                        'card_valid_from' => $cardValidFrom,
                        'card_valid_to' => $cardValidTo,
                        'id_card_no' => $validated['id_card_number'] ?? null,
                        'id_status' => SecurityParmIdApply::ID_STATUS_PENDING,
                        'remarks' => $validated['remarks'] ?? null,
                        'created_by' => $employeePk ?? $authEmpPk,
                        'created_date' => $now,
                        'id_photo_path' => $photoPath,
                        'employee_dob' => $validated['date_of_birth'] ?? null,
                        'mobile_no' => $validated['mobile_number'] ?? null,
                        'blood_group' => $validated['blood_group'] ?? null,
                        'payment_receipt' => $paymentReceipt,
                        'fir_doc' => $firDoc,
                        'service_ext' => ($validated['request_for'] ?? '') === 'Extension' ? $serviceExt : null,
                        'card_reason' => $cardReason,
                        'vender_name' => $validated['vendor_organization_name'] ?? null,
                        'father_name' => $validated['father_name'] ?? null,
                        'card_type' => '',
                        'department_approval_emp_pk' => null,
                        'depart_approval_status' => 1,
                        'depart_approval_date' => null,
                        'section' => null,
                        'id_proof' => null,
                        'aadhar_doc' => null,
                    ]);

                    // Case 4 - Extension/Duplicate ID Card (Other/Contractual): Only security_dup_other_id_apply at request time.
                    // security_dup_other_id_apply_approval rows are inserted when approvers approve.
                }

                return;
            }

            if ($employeeType === 'Permanent Employee') {
                $nextId = (int) DB::table('security_parm_id_apply')->max('pk') + 1;
                $empIdApply = 'PID' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);

                $emp = EmployeeMaster::select(['pk', 'designation_master_pk'])->find($employeePk);
                SecurityParmIdApply::create([
                    'emp_id_apply' => $empIdApply,
                    'employee_master_pk' => $employeePk,
                    'sec_id_card_config_pk' => $configMap->config_pk,
                    'designation_pk' => $emp?->designation_master_pk,
                    'card_valid_from' => $cardValidFrom,
                    'card_valid_to' => $cardValidTo,
                    'id_card_no' => $validated['id_card_number'] ?? null,
                    'id_status' => SecurityParmIdApply::ID_STATUS_PENDING,
                    'remarks' => $validated['remarks'] ?? null,
                    'created_by' => $employeePk ?? $authEmpPk,
                    'created_date' => $now,
                    'id_photo_path' => $photoPath,
                    'joining_letter_path' => $joiningLetterPath,
                    'employee_dob' => $validated['date_of_birth'] ?? null,
                    'mobile_no' => $validated['mobile_number'] ?? null,
                    'telephone_no' => $validated['telephone_number'] ?? null,
                    'blood_group' => $validated['blood_group'] ?? null,
                    'card_type' => $cardNameCode, // p/c
                    'permanent_type' => $cardMasterPk, // sec_id_cardno_master.pk
                    'perm_sub_type' => $configMap->map_pk, // sec_id_cardno_config_map.pk
                ]);

                // Case 1 - Permanent Employee (Own ID Card): Only security_parm_id_apply at request time.
                // security_parm_id_apply_approval rows are inserted when approvers approve (EmployeeIDCardApprovalController).
            } else {
                $nextId = (int) DB::table('security_con_oth_id_apply')->max('pk') + 1;
                $empIdApply = 'COD' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);

                $docPath = null;
                if ($request->hasFile('documents')) {
                    $docPath = $request->file('documents')->store('idcard/documents', 'public');
                }

                // Insert matches security_con_oth_id_apply structure (pk auto; section = bigint department_master_pk)
                $contRow = [
                    'emp_id_apply' => $empIdApply,
                    'employee_name' => $validated['name'] ?? null,
                    'sec_id_card_config_pk' => $configMap->config_pk,
                    'designation_name' => $validated['designation'] ?? null,
                    'card_valid_from' => $cardValidFrom,
                    'card_valid_to' => $cardValidTo,
                    'id_card_no' => $validated['id_card_number'] ?? null,
                    'id_status' => SecurityParmIdApply::ID_STATUS_PENDING,
                    'remarks' => $validated['remarks'] ?? null,
                    'created_by' => $employeePk ?? $authEmpPk,
                    'created_date' => $now,
                    'id_photo_path' => $photoPath,
                    'employee_dob' => $validated['date_of_birth'] ?? null,
                    'mobile_no' => $validated['mobile_number'] ?? null,
                    'blood_group' => $validated['blood_group'] ?? null,
                    'card_type' => $cardNameCode,
                    'permanent_type' => $cardMasterPk,
                    'perm_sub_type' => $configMap->map_pk,
                    'telephone_no' => $validated['telephone_number'] ?? null,
                    'vender_name' => $validated['vendor_organization_name'] ?? null,
                    'father_name' => $validated['father_name'] ?? null,
                    'doc_path' => $docPath,
                    'department_approval_emp_pk' => !empty($validated['approval_authority']) ? (int) $validated['approval_authority'] : null,
                    'section' => $userDepartmentPk,
                    'depart_approval_status' => 1,
                ];
                if ($docPath !== null && Schema::hasColumn('security_con_oth_id_apply', 'joining_letter_path')) {
                    $contRow['joining_letter_path'] = $docPath;
                }
                DB::table('security_con_oth_id_apply')->insert($contRow);
                if ($employeePk) {
                    static::syncEmployeeDojIfEmpty((int) $employeePk, $validated['academy_joining'] ?? null);
                }

                // Case 3 - Request Employee ID Card (Other/Contractual): Only security_con_oth_id_apply at request time.
                // security_con_oth_id_apply_approval rows are inserted when approvers approve (EmployeeIDCardApprovalController).
            }
        });

        $successMsg = 'Employee ID Card request created successfully!';
        if ($joiningLetterPath) {
            $successMsg .= ' Joining document uploaded successfully.';
        }
        if ($employeeType === 'Contractual Employee' && ! $isDupOrExt && $request->hasFile('documents')) {
            $successMsg .= ' Supporting document saved to database successfully.';
        }
        return redirect()
            ->route('admin.employee_idcard.index')
            ->with('success', $successMsg);
    }

    /**
     * Resolve list id to source: permanent (emp_id_apply: int or string e.g. PID00523) or contractual (c-{pk}).
     * @return array{type: 'perm'|'cont', pk: int|string, id: string|int}
     */
    private static function resolveId($id): array
    {
        if (is_string($id) && str_starts_with($id, 'c-')) {
            $pk = (int) substr($id, 2);
            return ['type' => 'cont', 'pk' => $pk, 'id' => $id];
        }
        // emp_id_apply can be numeric or string (e.g. PID00523)
        if (is_numeric($id)) {
            return ['type' => 'perm', 'pk' => (int) $id, 'id' => (int) $id];
        }
        return ['type' => 'perm', 'pk' => $id, 'id' => $id];
    }

    public function show($id)
    {
        $res = static::resolveId($id);
        if ($res['type'] === 'cont') {
            $row = DB::table('security_con_oth_id_apply')->where('pk', $res['pk'])->first();
            if (!$row) {
                abort(404);
            }
            $request = IdCardSecurityMapper::toContractualRequestDto($row);
            return view('admin.employee_idcard.show', ['request' => $request]);
        }
        // SecurityParmIdApply primary key is emp_id_apply (string or int)
        $row = SecurityParmIdApply::with(['employee.designation', 'approvals.approver'])
            ->findOrFail($res['pk']);
        $request = IdCardSecurityMapper::toEmployeeRequestDto($row);
        return view('admin.employee_idcard.show', ['request' => $request]);
    }

    /**
     * Block edit/delete once final status is not pending, or any section/security approver has acted.
     */
    private function redirectIfIdCardRequestNotEditableByApplicant(mixed $routeId, array $res): ?\Illuminate\Http\RedirectResponse
    {
        $mayEdit = false;
        if ($res['type'] === 'cont') {
            $row = DB::table('security_con_oth_id_apply')->where('pk', $res['pk'])->first();
            if ($row) {
                $dto = IdCardSecurityMapper::toContractualRequestDto($row);
                $mayEdit = (bool) ($dto->user_may_edit_request ?? false);
            }
        } else {
            $row = SecurityParmIdApply::with(['approvals' => function ($q) {
                $q->select(['pk', 'security_parm_id_apply_pk', 'status', 'recommend_status']);
            }])->find($res['pk']);
            if ($row) {
                $dto = IdCardSecurityMapper::toEmployeeRequestDto($row);
                $mayEdit = (bool) ($dto->user_may_edit_request ?? false);
            }
        }
        if (! $mayEdit) {
            return redirect()
                ->route('admin.employee_idcard.show', $routeId)
                ->with('error', 'This request cannot be edited or deleted: an approver has already acted or the status has changed.');
        }

        return null;
    }

    public function edit($id)
    {
        $cardTypes = DB::table('sec_id_cardno_master')->orderBy('sec_card_name')->pluck('sec_card_name');
        $userDepartmentPk = null;
        $userDepartmentName = null;
        $approvalAuthorityEmployees = collect();
        $authUserId = Auth::user()->user_id ?? null;
        if ($authUserId) {
            $authEmp = EmployeeMaster::with('department')
                ->where('pk', $authUserId)
                ->orWhere('pk_old', $authUserId)
                ->first();
            if ($authEmp && $authEmp->department_master_pk) {
                $userDepartmentPk = $authEmp->department_master_pk;
                $userDepartmentName = $authEmp->department->department_name ?? null;
                $approvalAuthorityEmployees = EmployeeMaster::with('designation')
                    ->where('department_master_pk', $userDepartmentPk)
                    ->when(Schema::hasColumn('employee_master', 'payroll'), fn ($q) => $q->where('payroll', 0))
                    ->when(Schema::hasColumn('employee_master', 'status'), fn ($q) => $q->where('status', 1))
                    ->orderBy('first_name')->orderBy('last_name')
                    ->get(['pk', 'first_name', 'last_name', 'designation_master_pk']);
            }
        }
        $res = static::resolveId($id);
        if ($redirectLocked = $this->redirectIfIdCardRequestNotEditableByApplicant($id, $res)) {
            return $redirectLocked;
        }
        if ($res['type'] === 'cont') {
            $row = DB::table('security_con_oth_id_apply')->where('pk', $res['pk'])->first();
            if (!$row) {
                abort(404);
            }
            $request = IdCardSecurityMapper::toContractualRequestDto($row);
            $designations = DesignationMaster::active()->orderBy('designation_name')->pluck('designation_name', 'designation_name')->all();
            return view('admin.employee_idcard.edit', [
                'request' => $request,
                'cardTypes' => $cardTypes,
                'userDepartmentName' => $userDepartmentName,
                'approvalAuthorityEmployees' => $approvalAuthorityEmployees,
                'designations' => $designations,
            ]);
        }
        $row = SecurityParmIdApply::with([
            'employee:pk,first_name,last_name,middle_name,designation_master_pk,dob,father_name,doj,mobile,landline_contact_no',
            'employee.designation:pk,designation_name',
            'creator:pk,first_name,last_name,department_master_pk',
            'creator.department:pk,department_name',
            'approvals:pk,security_parm_id_apply_pk,status,recommend_status,approval_emp_pk,created_date,approval_remarks',
            'approvals.approver:pk,first_name,last_name'
        ])->findOrFail($res['pk']);
        $request = IdCardSecurityMapper::toEmployeeRequestDto($row);
        $designations = DesignationMaster::active()->orderBy('designation_name')->pluck('designation_name', 'designation_name')->all();
        return view('admin.employee_idcard.edit', [
            'request' => $request,
            'cardTypes' => $cardTypes,
            'userDepartmentName' => $userDepartmentName,
            'approvalAuthorityEmployees' => $approvalAuthorityEmployees,
            'designations' => $designations,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'employee_type' => 'required|in:Permanent Employee,Contractual Employee',
            'card_type' => 'nullable|string|max:100',
            'sub_type' => 'nullable|string|max:100',
            'request_for' => 'nullable|string|max:100|in:Own ID Card,Others ID Card,Family ID Card,Replacement,Duplication,Extension',
            'duplication_reason' => 'nullable|string|in:Expired Card,Lost,Damage',
            'name' => 'required|string|max:255',
            'designation' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'father_name' => 'nullable|string|max:255',
            'academy_joining' => 'nullable|date',
            'id_card_valid_upto' => 'nullable|string|max:50',
            'id_card_valid_from' => 'nullable|string|max:50',
            'id_card_valid_upto_extension' => 'nullable|string|max:50',
            'id_card_valid_from_extension' => 'nullable|string|max:50',
            'id_card_number' => 'nullable|string|max:50',
            'mobile_number' => 'nullable|string|max:20',
            'telephone_number' => 'nullable|string|max:20',
            'blood_group' => 'nullable|string|max:10',
            'section' => 'nullable|string|max:255',
            'approval_authority' => 'required_if:employee_type,Contractual Employee|nullable|string|max:255',
            'vendor_organization_name' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'joining_letter' => 'nullable|mimes:pdf,doc,docx|max:5120',
            'fir_receipt' => 'nullable|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120',
            'payment_receipt' => 'nullable|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120',
            'documents' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'status' => 'nullable|in:Pending,Approved,Rejected,Issued',
            'remarks' => 'nullable|string',
        ], [
            'approval_authority.required_if' => 'Approval Authority is required for Contractual Employees.',
        ]);

        if (($validated['employee_type'] ?? '') === 'Contractual Employee') {
            $validated['request_for'] = 'Others ID Card';
        }

        $res = static::resolveId($id);
        if ($redirectLocked = $this->redirectIfIdCardRequestNotEditableByApplicant($id, $res)) {
            return $redirectLocked;
        }
        if ($res['type'] === 'cont') {
            $row = DB::table('security_con_oth_id_apply')->where('pk', $res['pk'])->first();
            if (!$row) {
                abort(404);
            }
            $authUserId = Auth::user()->user_id ?? Auth::id();
            $userDepartmentPkForUpdate = null;
            if ($authUserId) {
                $authEmp = EmployeeMaster::where('pk', $authUserId)->orWhere('pk_old', $authUserId)->first(['department_master_pk']);
                $userDepartmentPkForUpdate = $authEmp->department_master_pk ?? null;
            }
            $employeeType = $validated['employee_type'] ?? 'Contractual Employee';
            $cardNameCode = $employeeType === 'Permanent Employee' ? 'p' : 'c';
            $cardMasterPk = $row->permanent_type;
            if (!empty($validated['card_type'])) {
                $cardMasterPk = IdCardSecurityLookup::resolveCardMasterPk($validated['card_type']) ?? $row->permanent_type;
            }
            $configMapPk = $row->perm_sub_type;
            if (!empty($validated['sub_type']) && $cardMasterPk) {
                $configMap = IdCardSecurityLookup::resolveConfigMapRow($cardNameCode, $cardMasterPk, $validated['sub_type']);
                if ($configMap) {
                    $configMapPk = $configMap->map_pk;
                }
            }
            $cardValidFrom = !empty($validated['id_card_valid_from']) ? static::parseDateToYmd($validated['id_card_valid_from']) : $row->card_valid_from;
            $cardValidTo = !empty($validated['id_card_valid_upto']) ? static::parseDateToYmd($validated['id_card_valid_upto']) : $row->card_valid_to;
            $up = [
                'employee_name' => $validated['name'] ?? $row->employee_name,
                'designation_name' => $validated['designation'] ?? $row->designation_name,
                'card_valid_from' => $cardValidFrom,
                'card_valid_to' => $cardValidTo,
                'id_card_no' => $validated['id_card_number'] ?? $row->id_card_no,
                'remarks' => $validated['remarks'] ?? $row->remarks,
                'employee_dob' => $validated['date_of_birth'] ?? $row->employee_dob,
                'mobile_no' => $validated['mobile_number'] ?? $row->mobile_no,
                'telephone_no' => $validated['telephone_number'] ?? $row->telephone_no,
                'blood_group' => $validated['blood_group'] ?? $row->blood_group,
                'permanent_type' => $cardMasterPk,
                'perm_sub_type' => $configMapPk,
                'vender_name' => $validated['vendor_organization_name'] ?? $row->vender_name,
                'father_name' => $validated['father_name'] ?? $row->father_name,
                'department_approval_emp_pk' => array_key_exists('approval_authority', $validated) ? (!empty($validated['approval_authority']) ? (int) $validated['approval_authority'] : null) : $row->department_approval_emp_pk,
                'section' => $userDepartmentPkForUpdate ?? $row->section,
            ];
            $documentsUploadedList = [];
            if ($request->hasFile('photo')) {
                $up['id_photo_path'] = $request->file('photo')->store('idcard/photos', 'public');
                $documentsUploadedList[] = 'Photo';
            }
            if ($request->hasFile('documents') && $request->file('documents')->isValid()) {
                $oldDocPath = $row->doc_path ?? null;
                $oldJoinLetterPath = Schema::hasColumn('security_con_oth_id_apply', 'joining_letter_path')
                    ? ($row->joining_letter_path ?? null)
                    : null;
                $storedDoc = $request->file('documents')->store('idcard/documents', 'public');
                $up['doc_path'] = $storedDoc;
                if (Schema::hasColumn('security_con_oth_id_apply', 'joining_letter_path')) {
                    $up['joining_letter_path'] = $storedDoc;
                }
                $documentsUploadedList[] = 'Supporting document';
                foreach (array_unique(array_filter([$oldDocPath, $oldJoinLetterPath])) as $prevPath) {
                    if ($prevPath !== '' && $prevPath !== $storedDoc && Storage::disk('public')->exists($prevPath)) {
                        Storage::disk('public')->delete($prevPath);
                    }
                }
            }
            DB::table('security_con_oth_id_apply')->where('pk', $res['pk'])->update($up);
            $freshRow = DB::table('security_con_oth_id_apply')->where('pk', $res['pk'])->first();
            if ($freshRow) {
                $benef = IdCardSecurityMapper::resolveContractualBeneficiaryEmployee($freshRow);
                if ($benef && ! empty($benef->pk)) {
                    static::syncEmployeeDojIfEmpty((int) $benef->pk, $validated['academy_joining'] ?? null);
                }
            }

            $successMsg = 'Employee ID Card request updated successfully!';
            if (!empty($documentsUploadedList)) {
                $successMsg .= ' ' . implode(' and ', $documentsUploadedList) . ' uploaded successfully.';
            }
            
            return redirect()
                ->route('admin.employee_idcard.show', $res['id'])
                ->with('success', $successMsg);
        }

        $row = SecurityParmIdApply::findOrFail($res['pk']);

        $employeeType = $validated['employee_type'] ?? 'Permanent Employee';
        $cardNameCode = $employeeType === 'Permanent Employee' ? 'p' : 'c';
        if (!empty($validated['card_type'])) {
            $cardMasterPk = IdCardSecurityLookup::resolveCardMasterPk($validated['card_type']);
            if ($cardMasterPk) {
                $row->permanent_type = $cardMasterPk;
            }
        }
        if (!empty($validated['sub_type']) && !empty($row->permanent_type)) {
            $configMap = IdCardSecurityLookup::resolveConfigMapRow($cardNameCode, $row->permanent_type, $validated['sub_type']);
            if ($configMap) {
                $row->perm_sub_type = $configMap->map_pk;
            }
        }
        $row->card_type = $cardNameCode;

        // Prefer main form fields; use extension modal fields (Duplication/Extension) when main are empty
        $cardValidFrom = !empty($validated['id_card_valid_from'])
            ? static::parseDateToYmd($validated['id_card_valid_from'])
            : (!empty($validated['id_card_valid_from_extension']) ? static::parseDateToYmd($validated['id_card_valid_from_extension']) : $row->card_valid_from);
        $cardValidTo = !empty($validated['id_card_valid_upto'])
            ? static::parseDateToYmd($validated['id_card_valid_upto'])
            : (!empty($validated['id_card_valid_upto_extension']) ? static::parseDateToYmd($validated['id_card_valid_upto_extension']) : $row->card_valid_to);
        if ($cardValidFrom instanceof \DateTimeInterface) {
            $cardValidFrom = $cardValidFrom->format('Y-m-d');
        }
        if ($cardValidTo instanceof \DateTimeInterface) {
            $cardValidTo = $cardValidTo->format('Y-m-d');
        }

        $row->card_valid_from = $cardValidFrom;
        $row->card_valid_to = $cardValidTo;
        $row->id_card_no = $validated['id_card_number'] ?? null;
        $row->remarks = $validated['remarks'] ?? null;
        $row->employee_dob = $validated['date_of_birth'] ?? null;
        $row->mobile_no = $validated['mobile_number'] ?? null;
        $row->telephone_no = $validated['telephone_number'] ?? null;
        $row->blood_group = $validated['blood_group'] ?? null;
        if (array_key_exists('designation', $validated) && !empty($validated['designation'])) {
            $designationPk = DesignationMaster::where('designation_name', $validated['designation'])->value('pk');
            if ($designationPk) {
                $row->designation_pk = $designationPk;
            }
        }
        $documentsUploadedList = [];
        if ($request->hasFile('photo')) {
            $row->id_photo_path = $request->file('photo')->store('idcard/photos', 'public');
            $documentsUploadedList[] = 'Photo';
        }
        if ($request->hasFile('joining_letter')) {
            $row->joining_letter_path = $request->file('joining_letter')->store('idcard/joining_letters', 'public');
            $documentsUploadedList[] = 'Joining Letter';
        }
        $row->save();

        // Persist name, father_name and academy_joining (doj) to linked employee_master
        $emp = EmployeeMaster::find($row->employee_master_pk);
        if ($emp) {
            $name = trim($validated['name'] ?? '');
            if ($name !== '') {
                $parts = preg_split('/\s+/', $name, 2);
                $emp->first_name = $parts[0] ?? '';
                $emp->last_name = $parts[1] ?? '';
            }
            if (array_key_exists('father_name', $validated)) {
                $emp->father_name = $validated['father_name'];
            }
            if (! empty($validated['academy_joining']) && static::employeeMasterDojIsEmpty($emp->doj)) {
                $emp->doj = \Carbon\Carbon::parse($validated['academy_joining'])->format('Y-m-d');
            }
            $emp->save();
        }

        $successMsg = 'Employee ID Card request updated successfully!';
        if (!empty($documentsUploadedList)) {
            $successMsg .= ' ' . implode(' and ', $documentsUploadedList) . ' uploaded successfully.';
        }
        
        // Use emp_id_apply (business key) for redirect so show() can resolve correctly.
        return redirect()
            ->route('admin.employee_idcard.show', $row->emp_id_apply)
            ->with('success', $successMsg);
    }

    public function amendDuplicationExtension(Request $request, $id)
    {
        $res = static::resolveId($id);
        if ($res['type'] === 'cont') {
            $message = 'Duplication/Extension is not supported for contractual ID card requests.';
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }
            return back()
                ->withErrors(['duplication_reason' => $message])
                ->withInput();
        }
        $validated = $request->validate([
            'duplication_reason' => 'nullable|string|in:Expired Card,Lost,Damage',
            'id_card_valid_from' => 'nullable|string|max:50',
            'id_card_valid_upto' => 'nullable|string|max:50',
            'id_card_number' => 'nullable|string|max:50',
            'fir_receipt' => 'nullable|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120',
            'payment_receipt' => 'nullable|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120',
            'supporting_document' => 'nullable|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120',
            'extension_reason' => 'nullable|string|max:500',
            'extension_document' => 'nullable|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120',
        ]);

        $row = SecurityParmIdApply::with('employee')->findOrFail($res['pk']);

        if (array_key_exists('id_card_valid_from', $validated) && $validated['id_card_valid_from']) {
            $row->card_valid_from = static::parseDateToYmd($validated['id_card_valid_from']);
        }
        if (array_key_exists('id_card_valid_upto', $validated) && $validated['id_card_valid_upto']) {
            $row->card_valid_to = static::parseDateToYmd($validated['id_card_valid_upto']);
        }
        if (array_key_exists('id_card_number', $validated)) {
            $row->id_card_no = $validated['id_card_number'];
        }
        if (array_key_exists('extension_reason', $validated)) {
            $row->extension_reason = $validated['extension_reason'] ?: null;
        }
        if ($request->hasFile('extension_document')) {
            $row->extension_document_path = $request->file('extension_document')->store('idcard/extension_documents', 'public');
        }
        $row->save();

        $dupInserted = false;
        $dupReason = $validated['duplication_reason'] ?? null;
        if (in_array($dupReason, ['Expired Card', 'Lost', 'Damage'], true)) {
            $dupInserted = $this->insertDuplicatePermFromAmend($request, $row, $dupReason);
        }

        $dto = IdCardSecurityMapper::toEmployeeRequestDto($row->load(['employee.designation', 'approvals.approver']));
        return response()->json([
            'success' => true,
            'message' => $dupInserted
                ? 'Duplication/Extension saved and duplicate request created in approval queue.'
                : 'Duplication/Extension details updated successfully.',
            'data' => [
                'duplication_reason' => $dto->duplication_reason ?? '',
                'id_card_valid_from' => $dto->id_card_valid_from ?? '',
                'id_card_valid_upto' => $dto->id_card_valid_upto ?? '',
                'id_card_number' => $dto->id_card_number ?? '',
                'extension_reason' => $row->extension_reason ?? '',
            ],
        ]);
    }

    /**
     * Insert a row into security_dup_perm_id_apply when user submits Amend with a duplicate reason (Expired Card / Lost / Damage).
     */
    private function insertDuplicatePermFromAmend(Request $request, SecurityParmIdApply $parentRow, string $cardReason): bool
    {
        $nextPk = (int) DB::table('security_dup_perm_id_apply')->max('pk') + 1;
        $applyId = 'DUP' . str_pad((string) $nextPk, 5, '0', STR_PAD_LEFT);
        $now = now()->format('Y-m-d H:i:s');
        $createdBy = Auth::id() ?? $parentRow->created_by;

        $cardValidFrom = null;
        $cardValidTo = null;
        if ($request->filled('id_card_valid_from')) {
            $cardValidFrom = static::parseDateToYmd($request->get('id_card_valid_from'));
        }
        if ($request->filled('id_card_valid_upto')) {
            $cardValidTo = static::parseDateToYmd($request->get('id_card_valid_upto'));
        }
        if ($cardValidFrom === null && $parentRow->card_valid_from) {
            $cardValidFrom = $parentRow->card_valid_from->format('Y-m-d');
        }
        if ($cardValidTo === null && $parentRow->card_valid_to) {
            $cardValidTo = $parentRow->card_valid_to->format('Y-m-d');
        }

        $firDoc = null;
        if ($cardReason === 'Lost' && $request->hasFile('fir_receipt')) {
            $ext = $request->file('fir_receipt')->getClientOriginalExtension();
            $firDoc = $applyId . '_FIR_' . time() . '.' . $ext;
            $request->file('fir_receipt')->storeAs('idcard/dup_docs', $firDoc, 'public');
        }

        $paymentReceipt = null;
        $serviceExt = null;
        if ($request->hasFile('payment_receipt')) {
            $ext = $request->file('payment_receipt')->getClientOriginalExtension();
            $file = $applyId . '_DOC_' . time() . '.' . $ext;
            $request->file('payment_receipt')->storeAs('idcard/dup_docs', $file, 'public');
            if ($cardReason === 'Expired Card') {
                $serviceExt = $file;
            } else {
                $paymentReceipt = $file;
            }
        }

        $nameChangeDoc = null;
        if ($request->hasFile('supporting_document')) {
            $ext = $request->file('supporting_document')->getClientOriginalExtension();
            $file = $applyId . '_SUPPORT_' . time() . '.' . $ext;
            $request->file('supporting_document')->storeAs('idcard/dup_docs', $file, 'public');
            $nameChangeDoc = $file;
        }

        $designationPk = $parentRow->employee && isset($parentRow->employee->designation_master_pk)
            ? $parentRow->employee->designation_master_pk
            : null;

        DB::table('security_dup_perm_id_apply')->insert([
            'emp_id_apply' => $applyId,
            'employee_master_pk' => $parentRow->employee_master_pk,
            'designation_pk' => $designationPk,
            'card_valid_from' => $cardValidFrom,
            'card_valid_to' => $cardValidTo,
            'id_card_no' => $request->get('id_card_number') ?: $parentRow->id_card_no,
            'id_status' => SecurityParmIdApply::ID_STATUS_PENDING,
            'remarks' => null,
            'created_by' => $createdBy,
            'created_date' => $now,
            'id_photo_path' => $parentRow->id_photo_path,
            'employee_dob' => $parentRow->employee_dob ? (\Carbon\Carbon::parse($parentRow->employee_dob)->format('Y-m-d')) : null,
            'mobile_no' => $parentRow->mobile_no,
            'blood_group' => $parentRow->blood_group,
            'payment_receipt' => $paymentReceipt,
            'fir_doc' => $firDoc,
            'service_ext' => $serviceExt,
            'parent_id' => $parentRow->emp_id_apply,
            'card_reason' => $cardReason,
            'name_change_doc' => $nameChangeDoc,
        ]);

        return true;
    }

    public function destroy($id)
    {
        $res = static::resolveId($id);
        if ($redirectLocked = $this->redirectIfIdCardRequestNotEditableByApplicant($id, $res)) {
            return $redirectLocked;
        }
        if ($res['type'] === 'cont') {
            $row = DB::table('security_con_oth_id_apply')->where('pk', $res['pk'])->first();
            if (!$row) {
                abort(404);
            }
            DB::table('security_con_oth_id_apply_approval')->where('security_parm_id_apply_pk', $row->emp_id_apply)->delete();
            DB::table('security_con_oth_id_apply')->where('pk', $res['pk'])->delete();
            return redirect()
                ->route('admin.employee_idcard.index')
                ->with('success', 'Employee ID Card request archived successfully!');
        }
        $row = SecurityParmIdApply::findOrFail($res['pk']);
        SecurityParmIdApplyApproval::where('security_parm_id_apply_pk', $row->emp_id_apply)->delete();
        $row->delete();

        return redirect()
            ->route('admin.employee_idcard.index')
            ->with('success', 'Employee ID Card request archived successfully!');
    }

    public function restore($id)
    {
        return redirect()->route('admin.employee_idcard.index')
            ->with('info', 'Security table does not use soft delete. Record remains in archive.');
    }

    public function forceDelete($id)
    {
        return redirect()->route('admin.employee_idcard.index')
            ->with('info', 'Security table does not use soft delete.');
    }

    public function export(Request $request)
    {
        $tab = $request->get('tab', 'active');
        $format = $request->get('format', 'xlsx');
        if (!in_array($tab, ['active', 'archive', 'duplication', 'extension', 'all'])) {
            $tab = 'active';
        }
        $filename = 'employee_idcard_requests_' . $tab . '_' . now()->format('Y-m-d_His');

        $requests = $this->getFilteredMergedRequests($request);

        $filteredByTab = match ($tab) {
            'archive' => $requests->filter(fn ($r) => in_array($r->status ?? '', ['Approved', 'Rejected'], true))->values(),
            'duplication' => $requests->filter(fn ($r) => in_array($r->request_for ?? '', ['Replacement', 'Duplication'], true) && ($r->status ?? '') === 'Approved')->values(),
            'extension' => $requests->filter(fn ($r) => ($r->request_for ?? '') === 'Extension' && ($r->status ?? '') === 'Approved')->values(),
            'all' => $requests,
            default => $requests->filter(fn ($r) => ($r->status ?? '') === 'Pending')->values(),
        };

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.employee_idcard.export_pdf', [
                'requests' => $filteredByTab,
                'tab' => $tab,
                'export_date' => now()->format('d/m/Y H:i'),
            ])
                ->setPaper('a4', 'landscape')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true);
            return $pdf->download($filename . '.pdf');
        }

        return Excel::download(
            new EmployeeIDCardExport($tab, true, $filteredByTab),
            $filename . ($format === 'csv' ? '.csv' : '.xlsx'),
            $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
        );
    }

    /**
     * Build merged (permanent + contractual) requests with optional date range and name search.
     */
    private function getFilteredMergedRequests(Request $request): \Illuminate\Support\Collection
    {
        $with = [
            'employee:pk,first_name,last_name,designation_master_pk',
            'employee.designation:pk,designation_name',
            'approvals:pk,security_parm_id_apply_pk,status,recommend_status,approval_emp_pk,created_date,approval_remarks',
            'approvals.approver:pk,first_name,last_name',
        ];
        $columns = ['pk', 'emp_id_apply', 'employee_master_pk', 'id_status', 'created_date', 'card_valid_from', 'card_valid_to', 'id_card_no', 'id_photo_path', 'joining_letter_path', 'mobile_no', 'telephone_no', 'blood_group', 'card_type', 'permanent_type', 'perm_sub_type', 'remarks', 'created_by', 'employee_dob'];
        $filter = $request->get('filter', 'all');
        if (!in_array($filter, ['active', 'archive', 'all'], true)) {
            $filter = 'all';
        }

        $listStatus = $request->get('list_status', 'all');
        if (! in_array($listStatus, ['all', 'pending', 'approved', 'rejected'], true)) {
            $listStatus = 'all';
        }

        $permQuery = SecurityParmIdApply::select($columns)->with($with)->orderBy('created_date', 'desc');
        $contCols = ['pk', 'emp_id_apply', 'employee_name', 'designation_name', 'id_status', 'depart_approval_status', 'department_approval_emp_pk', 'created_date', 'card_valid_from', 'card_valid_to', 'id_card_no', 'id_photo_path', 'mobile_no', 'telephone_no', 'blood_group', 'permanent_type', 'perm_sub_type', 'remarks', 'created_by', 'employee_dob', 'vender_name', 'father_name', 'doc_path'];
        $contQuery = DB::table('security_con_oth_id_apply')->select($contCols)->orderBy('created_date', 'desc');

        static::applyEmployeeIdcardListStatusFilters($permQuery, $contQuery, $filter, $listStatus);

        $permRows = $permQuery->get();
        $contRows = $contQuery->get();

        $permDto = $permRows->map(fn ($r) => IdCardSecurityMapper::toEmployeeRequestDto($r));
        $contDto = $contRows->map(fn ($r) => IdCardSecurityMapper::toContractualRequestDto($r));
        $merged = $permDto->concat($contDto)->sortByDesc('created_at')->values();

        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        if ($dateFrom) {
            try {
                $from = \Carbon\Carbon::parse($dateFrom)->startOfDay();
                $merged = $merged->filter(fn ($r) => $r->created_at && $r->created_at->gte($from))->values();
            } catch (\Exception $e) {
            }
        }
        if ($dateTo) {
            try {
                $to = \Carbon\Carbon::parse($dateTo)->endOfDay();
                $merged = $merged->filter(fn ($r) => $r->created_at && $r->created_at->lte($to))->values();
            } catch (\Exception $e) {
            }
        }

        $search = trim($request->get('search', ''));
        if ($search !== '') {
            $searchLower = mb_strtolower($search);
            $merged = $merged->filter(function ($r) use ($searchLower) {
                $name = mb_strtolower($r->name ?? '');
                return str_contains($name, $searchLower);
            })->values();
        }

        return $merged;
    }

    /**
     * True when employee_master.doj is unset or a known "empty" sentinel.
     */
    private static function employeeMasterDojIsEmpty($doj): bool
    {
        if ($doj === null) {
            return true;
        }
        $s = trim((string) $doj);
        if ($s === '') {
            return true;
        }
        if ($s === '0000-00-00' || str_starts_with($s, '0000-00-00')) {
            return true;
        }

        return false;
    }

    /**
     * Set employee_master.doj from the ID card form only when doj is currently empty.
     */
    private static function syncEmployeeDojIfEmpty(int $employeeMasterPk, ?string $academyJoiningInput): void
    {
        $ymd = static::parseDateToYmd(trim((string) ($academyJoiningInput ?? '')));
        if ($ymd === null) {
            return;
        }
        $emp = EmployeeMaster::query()->where('pk', $employeeMasterPk)->first(['pk', 'doj']);
        if (! $emp || ! static::employeeMasterDojIsEmpty($emp->doj)) {
            return;
        }
        $emp->doj = $ymd;
        $emp->save();
    }

    /**
     * Parse date string (Y-m-d from HTML date input, or d/m/Y from text) to Y-m-d.
     */
    private static function parseDateToYmd(?string $value): ?string
    {
        $value = trim($value ?? '');
        if ($value === '') {
            return null;
        }
        // HTML5 date input sends Y-m-d (e.g. 2025-12-31)
        try {
            $date = \Carbon\Carbon::createFromFormat('Y-m-d', $value);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        } catch (\Exception $e) {
        }
        // Text field placeholder DD/MM/YYYY (e.g. 31/12/2025)
        try {
            $date = \Carbon\Carbon::createFromFormat('d/m/Y', $value);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        } catch (\Exception $e) {
        }
        // Fallback: Carbon::parse (handles many formats)
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
