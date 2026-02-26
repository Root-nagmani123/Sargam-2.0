<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\EmployeeIDCardExport;
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
            'approvals:pk,security_parm_id_apply_pk,status,approval_emp_pk,created_date,approval_remarks',
            'approvals.approver:pk,first_name,last_name',
        ];
        $columns = ['pk', 'emp_id_apply', 'employee_master_pk', 'id_status', 'created_date', 'card_valid_from', 'card_valid_to', 'id_card_no', 'id_photo_path', 'joining_letter_path', 'mobile_no', 'telephone_no', 'blood_group', 'card_type', 'permanent_type', 'perm_sub_type', 'remarks', 'created_by', 'employee_dob'];
        $filter = $request->get('filter', 'active');
        if (! in_array($filter, ['active', 'archive', 'all'], true)) {
            $filter = 'active';
        }

        // Permanent
        $permQuery = SecurityParmIdApply::select($columns)->with($with)->orderBy('created_date', 'desc');
        if ($filter === 'active') {
            $permQuery->where('id_status', SecurityParmIdApply::ID_STATUS_PENDING);
        } elseif ($filter === 'archive') {
            $permQuery->whereIn('id_status', [SecurityParmIdApply::ID_STATUS_APPROVED, SecurityParmIdApply::ID_STATUS_REJECTED]);
        }
        $permRows = $permQuery->get();

        // Contractual
        $contCols = ['pk', 'emp_id_apply', 'employee_name', 'designation_name', 'id_status', 'created_date', 'card_valid_from', 'card_valid_to', 'id_card_no', 'id_photo_path', 'mobile_no', 'telephone_no', 'blood_group', 'permanent_type', 'perm_sub_type', 'remarks', 'created_by', 'employee_dob', 'vender_name', 'father_name', 'doc_path'];
        $contQuery = DB::table('security_con_oth_id_apply')->select($contCols)->orderBy('created_date', 'desc');
        if ($filter === 'active') {
            $contQuery->where('id_status', 1);
        } elseif ($filter === 'archive') {
            $contQuery->whereIn('id_status', [2, 3]);
        }
        $contRows = $contQuery->get();

        $permDto = $permRows->map(fn ($r) => IdCardSecurityMapper::toEmployeeRequestDto($r));
        $contDto = $contRows->map(fn ($r) => IdCardSecurityMapper::toContractualRequestDto($r));
        $merged = $permDto->concat($contDto)->sortByDesc('created_at')->values();

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
            ->filter(fn ($r) => in_array(($r->request_for ?? ''), ['Replacement', 'Duplication'], true))
            ->values();
        $extensionCollection = $allRequests
            ->filter(fn ($r) => ($r->request_for ?? '') === 'Extension')
            ->values();

        $perPage = (int) $request->get('per_page', 15);
        $perPage = $perPage >= 5 && $perPage <= 100 ? $perPage : 15;

        $activeRequests = static::paginateCollection($activeCollection, (int) $request->get('active_page', 1) ?: 1, $perPage, $request->url(), 'active_page');
        $activeRequests->withQueryString();
        $archivedRequests = static::paginateCollection($archivedCollection, (int) $request->get('archive_page', 1) ?: 1, $perPage, $request->url(), 'archive_page');
        $archivedRequests->withQueryString();
        $duplicationRequests = static::paginateCollection($duplicationCollection, (int) $request->get('duplication_page', 1) ?: 1, $perPage, $request->url(), 'duplication_page');
        $duplicationRequests->withQueryString();
        $extensionRequests = static::paginateCollection($extensionCollection, (int) $request->get('extension_page', 1) ?: 1, $perPage, $request->url(), 'extension_page');
        $extensionRequests->withQueryString();

        return view('admin.employee_idcard.index', [
            'activeRequests' => $activeRequests,
            'archivedRequests' => $archivedRequests,
            'duplicationRequests' => $duplicationRequests,
            'extensionRequests' => $extensionRequests,
            'filter' => $filter,
            'dateFrom' => $dateFrom ?? '',
            'dateTo' => $dateTo ?? '',
            'search' => $search ?? '',
        ]);
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

    public function create()
    {
        $cardTypes = DB::table('sec_id_cardno_master')->orderBy('sec_card_name')->pluck('sec_card_name');

        // Contractual: Section = logged-in user's department only; Approval Authority = same department employees with Payroll=0 (permanent)
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
            ->whereNotNull('config_name')
            ->where('config_name', '!=', '')
            ->orderBy('config_name')
            ->get(['pk', 'config_name']);
        $subTypes = $rows->map(fn ($r) => ['value' => $r->config_name, 'text' => $r->config_name])->values();
        return response()->json(['sub_types' => $subTypes]);
    }

    /**
     * AJAX: Logged-in user's employee details for "Own ID Card" autofill.
     * Resolves employee by pk or pk_old so data is found either way.
     */
    public function me()
    {
        $userId = Auth::user()->user_id ?? null;
        if (!$userId) {
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
        $dupPermIdApply = DB::table('security_dup_perm_id_apply')->where('employee_master_pk', $emp->pk)->orWhere('employee_master_pk', $emp->pk_old)->orderBy('pk', 'desc')->first();
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
                'id_card_valid_upto' => $dupPermIdApply->card_valid_to ?? null,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_type' => 'required|in:Permanent Employee,Contractual Employee',
            'card_type' => 'required|string|max:100',
            'sub_type' => 'required|string|max:100',
            'request_for' => 'nullable|string|max:100|in:Own ID Card,Others ID Card,Family ID Card,Replacement,Duplication,Extension',
            'duplication_reason' => 'nullable|string|in:Expired Card,Lost,Damage',
            'name' => 'required|string|max:255',
            'designation' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'father_name' => 'nullable|string|max:255',
            'academy_joining' => 'nullable|date',
            'id_card_valid_upto' => 'nullable|string|max:50',
            'id_card_valid_from' => 'nullable|string|max:50',
            'id_card_number' => 'nullable|string|max:50',
            'mobile_number' => 'nullable|string|max:20',
            'telephone_number' => 'nullable|string|max:20',
            'blood_group' => 'nullable|string|max:10',
            'section' => 'nullable|string|max:255',
            'approval_authority' => 'nullable|string|max:255',
            'vendor_organization_name' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'joining_letter' => 'nullable|mimes:pdf,doc,docx|max:5120',
            'fir_receipt' => 'required_if:duplication_reason,Lost|nullable|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120',
            'payment_receipt' => 'nullable|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120',
            'documents' => 'nullable|mimes:pdf,doc,docx|max:5120',
            'remarks' => 'nullable|string',
            'employee_master_pk' => 'nullable|integer|exists:employee_master,pk',
        ], [
            'fir_receipt.required_if' => 'FIR Receipt is required when the card is reported as Lost.',
        ]);

        $authEmpPk = Auth::user()->user_id ?? Auth::id();
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

        $employeeType = $validated['employee_type'];
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

        $isDupOrExt = in_array(($validated['request_for'] ?? 'Own ID Card'), ['Replacement', 'Duplication', 'Extension'], true);

        $photoPath = null;
        if ($request->hasFile('photo')) {
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
                        'depart_approval_status' => null,
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
                DB::table('security_con_oth_id_apply')->insert([
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
                ]);

                // Case 3 - Request Employee ID Card (Other/Contractual): Only security_con_oth_id_apply at request time.
                // security_con_oth_id_apply_approval rows are inserted when approvers approve (EmployeeIDCardApprovalController).
            }
        });

        return redirect()
            ->route('admin.employee_idcard.index')
            ->with('success', 'Employee ID Card request created successfully!');
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
        if ($res['type'] === 'cont') {
            $row = DB::table('security_con_oth_id_apply')->where('pk', $res['pk'])->first();
            if (!$row) {
                abort(404);
            }
            $request = IdCardSecurityMapper::toContractualRequestDto($row);
            return view('admin.employee_idcard.edit', [
                'request' => $request,
                'cardTypes' => $cardTypes,
                'userDepartmentName' => $userDepartmentName,
                'approvalAuthorityEmployees' => $approvalAuthorityEmployees,
            ]);
        }
        $row = SecurityParmIdApply::with(['employee.designation', 'approvals.approver'])
            ->findOrFail($res['pk']);
        $request = IdCardSecurityMapper::toEmployeeRequestDto($row);
        return view('admin.employee_idcard.edit', [
            'request' => $request,
            'cardTypes' => $cardTypes,
            'userDepartmentName' => $userDepartmentName,
            'approvalAuthorityEmployees' => $approvalAuthorityEmployees,
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
            'id_card_number' => 'nullable|string|max:50',
            'mobile_number' => 'nullable|string|max:20',
            'telephone_number' => 'nullable|string|max:20',
            'blood_group' => 'nullable|string|max:10',
            'section' => 'nullable|string|max:255',
            'approval_authority' => 'nullable|string|max:255',
            'vendor_organization_name' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'joining_letter' => 'nullable|mimes:pdf,doc,docx|max:5120',
            'fir_receipt' => 'nullable|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120',
            'payment_receipt' => 'nullable|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120',
            'documents' => 'nullable|mimes:pdf,doc,docx|max:5120',
            'status' => 'nullable|in:Pending,Approved,Rejected,Issued',
            'remarks' => 'nullable|string',
        ]);

        $res = static::resolveId($id);
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
            if ($request->hasFile('photo')) {
                $up['id_photo_path'] = $request->file('photo')->store('idcard/photos', 'public');
            }
            if ($request->hasFile('documents')) {
                $up['doc_path'] = $request->file('documents')->store('idcard/documents', 'public');
            }
            DB::table('security_con_oth_id_apply')->where('pk', $res['pk'])->update($up);
            return redirect()
                ->route('admin.employee_idcard.show', $res['id'])
                ->with('success', 'Employee ID Card request updated successfully!');
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

        $cardValidFrom = null;
        $cardValidTo = null;
        if (!empty($validated['id_card_valid_from'])) {
            $cardValidFrom = static::parseDateToYmd($validated['id_card_valid_from']);
        }
        if (!empty($validated['id_card_valid_upto'])) {
            $cardValidTo = static::parseDateToYmd($validated['id_card_valid_upto']);
        }

        $row->card_valid_from = $cardValidFrom;
        $row->card_valid_to = $cardValidTo;
        $row->id_card_no = $validated['id_card_number'] ?? null;
        $row->remarks = $validated['remarks'] ?? null;
        $row->employee_dob = $validated['date_of_birth'] ?? null;
        $row->mobile_no = $validated['mobile_number'] ?? null;
        $row->telephone_no = $validated['telephone_number'] ?? null;
        $row->blood_group = $validated['blood_group'] ?? null;
        if ($request->hasFile('photo')) {
            $row->id_photo_path = $request->file('photo')->store('idcard/photos', 'public');
        }
        if ($request->hasFile('joining_letter')) {
            $row->joining_letter_path = $request->file('joining_letter')->store('idcard/joining_letters', 'public');
        }
        $row->save();

        return redirect()
            ->route('admin.employee_idcard.show', $row->pk)
            ->with('success', 'Employee ID Card request updated successfully!');
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
            'duplication' => $requests->filter(fn ($r) => in_array($r->request_for ?? '', ['Replacement', 'Duplication'], true))->values(),
            'extension' => $requests->filter(fn ($r) => ($r->request_for ?? '') === 'Extension')->values(),
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
            'approvals:pk,security_parm_id_apply_pk,status,approval_emp_pk,created_date,approval_remarks',
            'approvals.approver:pk,first_name,last_name',
        ];
        $columns = ['pk', 'emp_id_apply', 'employee_master_pk', 'id_status', 'created_date', 'card_valid_from', 'card_valid_to', 'id_card_no', 'id_photo_path', 'joining_letter_path', 'mobile_no', 'telephone_no', 'blood_group', 'card_type', 'permanent_type', 'perm_sub_type', 'remarks', 'created_by', 'employee_dob'];
        $filter = $request->get('filter', 'all');
        if (!in_array($filter, ['active', 'archive', 'all'], true)) {
            $filter = 'all';
        }

        $permQuery = SecurityParmIdApply::select($columns)->with($with)->orderBy('created_date', 'desc');
        if ($filter === 'active') {
            $permQuery->where('id_status', SecurityParmIdApply::ID_STATUS_PENDING);
        } elseif ($filter === 'archive') {
            $permQuery->whereIn('id_status', [SecurityParmIdApply::ID_STATUS_APPROVED, SecurityParmIdApply::ID_STATUS_REJECTED]);
        }
        $permRows = $permQuery->get();

        $contCols = ['pk', 'emp_id_apply', 'employee_name', 'designation_name', 'id_status', 'created_date', 'card_valid_from', 'card_valid_to', 'id_card_no', 'id_photo_path', 'mobile_no', 'telephone_no', 'blood_group', 'permanent_type', 'perm_sub_type', 'remarks', 'created_by', 'employee_dob', 'vender_name', 'father_name', 'doc_path'];
        $contQuery = DB::table('security_con_oth_id_apply')->select($contCols)->orderBy('created_date', 'desc');
        if ($filter === 'active') {
            $contQuery->where('id_status', 1);
        } elseif ($filter === 'archive') {
            $contQuery->whereIn('id_status', [2, 3]);
        }
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
