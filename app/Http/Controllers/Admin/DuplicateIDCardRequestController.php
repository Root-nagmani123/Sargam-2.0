<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DepartmentMaster;
use App\Models\EmployeeMaster;
use App\Models\SecurityDupOtherIdApply;
use App\Models\SecurityDupOtherIdApplyApproval;
use App\Models\SecurityDupPermIdApply;
use App\Models\SecurityDupPermIdApplyApproval;
use App\Models\SecurityParmIdApply;
use App\Models\SecurityFamilyIdApply;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DuplicateIDCardRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $employeePk = $user->user_id;
        if (!$employeePk) {
            abort(403);
        }

        $search = trim((string) $request->get('search', ''));
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        $page = (int) $request->get('page', 1);

        // Permanent duplicate requests for this user
        $permQuery = SecurityDupPermIdApply::with(['employee.designation', 'employee.department'])
            ->where('created_by', $employeePk);
        if ($search !== '') {
            $permQuery->where(function ($q) use ($search) {
                $q->where('emp_id_apply', 'like', "%{$search}%")
                    ->orWhere('id_card_no', 'like', "%{$search}%");
            });
        }
        $permRows = $permQuery->orderByDesc('created_date')->get();

        // Other duplicate requests for this user (contractual/family/etc.)
        $otherQuery = SecurityDupOtherIdApply::query()->where('created_by', $employeePk);
        if ($search !== '') {
            $otherQuery->where(function ($q) use ($search) {
                $q->where('emp_id_apply', 'like', "%{$search}%")
                    ->orWhere('id_card_no', 'like', "%{$search}%")
                    ->orWhere('employee_name', 'like', "%{$search}%");
            });
        }
        $otherRows = $otherQuery->orderByDesc('created_date')->get();

        $deptMap = DepartmentMaster::query()->pluck('department_name', 'pk')->toArray();

        $items = collect();

        foreach ($permRows as $row) {
            $emp = $row->employee;
            $statusLabel = $this->statusLabelForDup('perm', $row->emp_id_apply);

            $items->push((object) [
                'id' => $row->emp_id_apply,
                'source' => 'perm',
                'employee_name' => $emp?->name ?? '--',
                'designation' => $emp?->designation?->designation_name ?? '--',
                'department' => $emp?->department?->department_name ?? '--',
                'id_card_no' => $row->id_card_no ?? '--',
                'employee_dob' => $row->employee_dob,
                'blood_group' => $row->blood_group ?? '--',
                'mobile_no' => $row->mobile_no ?? '--',
                'card_reason' => $row->card_reason ?? '--',
                'employee_type' => 'Permanent',
                'photo_path' => $row->id_photo_path,
                'doc_path' => $row->service_ext ?? $row->payment_receipt ?? $row->fir_doc,
                'valid_from' => $row->card_valid_from,
                'valid_to' => $row->card_valid_to,
                'status_label' => $statusLabel,
                'request_date' => $row->created_date,
            ]);
        }

        foreach ($otherRows as $row) {
            $deptName = '--';
            if (!empty($row->section) && isset($deptMap[$row->section])) {
                $deptName = $deptMap[$row->section];
            }
            $statusLabel = $this->statusLabelForDup('other', $row->emp_id_apply);

            $items->push((object) [
                'id' => $row->emp_id_apply,
                'source' => 'other',
                'employee_name' => $row->employee_name ?? '--',
                'designation' => $row->designation_name ?? '--',
                'department' => $deptName,
                'id_card_no' => $row->id_card_no ?? '--',
                'employee_dob' => $row->employee_dob,
                'blood_group' => $row->blood_group ?? '--',
                'mobile_no' => $row->mobile_no ?? '--',
                'card_reason' => $row->card_reason ?? '--',
                'employee_type' => ($row->card_type ?: 'Contractual'),
                'photo_path' => $row->id_photo_path,
                'doc_path' => $row->service_ext ?? $row->payment_receipt ?? $row->fir_doc ?? $row->aadhar_doc,
                'valid_from' => $row->card_valid_from,
                'valid_to' => $row->card_valid_to,
                'status_label' => $statusLabel,
                'request_date' => $row->created_date,
            ]);
        }

        $items = $items->sortByDesc(fn ($i) => $i->request_date ? strtotime((string) $i->request_date) : 0)->values();

        $requests = new LengthAwarePaginator(
            $items->forPage($page, $perPage),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.duplicate_idcard.index', compact('requests'));
    }

    public function create()
    {
        $user = Auth::user();
        $employeePk = $user->user_id ?? $user->pk ?? null;
        $me = null;
        if ($employeePk) {
            $me = EmployeeMaster::with(['designation', 'department'])->where('pk', $employeePk)->orWhere('pk_old', $employeePk)->first();
        }

        $idProofOptions = [
            1 => 'Aadhar Card',
            2 => 'PAN Card',
            3 => 'Driving Licence',
            4 => 'Voter ID',
        ];

        return view('admin.duplicate_idcard.create', compact('me', 'idProofOptions'));
    }

    /**
     * Prefetch existing ID card details (permanent / contractual / family) by card number for Duplicate ID Card form.
     */
    public function lookupByCardNumber(Request $request)
    {
        $cardNo = trim((string) $request->get('id_card_number', ''));
        $type = $request->get('id_card_type', 'Permanent');

        if ($cardNo === '') {
            return response()->json([
                'success' => false,
                'message' => 'ID Card Number is required.',
            ], 422);
        }

        // Permanent ID Cards
        if ($type === 'Permanent') {
            $row = SecurityParmIdApply::with(['employee.designation'])
                ->where('id_card_no', $cardNo)
                ->first();
            if (!$row) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active permanent ID card found with this number.',
                ], 404);
            }

            $emp = $row->employee;
            return response()->json([
                'success' => true,
                'data' => [
                    'employee_name' => $emp ? trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')) : '',
                    'designation' => $emp?->designation?->designation_name ?? '',
                    'date_of_birth' => $row->employee_dob ? $row->employee_dob->format('Y-m-d') : null,
                    'blood_group' => $row->blood_group ?? '',
                    'mobile_number' => $row->mobile_no ?? '',
                    'father_name' => $emp->father_name ?? null,
                    'card_valid_from' => $row->card_valid_from ? $row->card_valid_from->format('Y-m-d') : null,
                    'card_valid_to' => $row->card_valid_to ? $row->card_valid_to->format('Y-m-d') : null,
                ],
            ]);
        }

        // Family ID Cards
        if ($type === 'Family') {
            $row = SecurityFamilyIdApply::where('id_card_no', $cardNo)
                ->orderByDesc('created_date')
                ->first();
            if (!$row) {
                return response()->json([
                    'success' => false,
                    'message' => 'No family ID card found with this number.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'employee_name' => $row->family_name ?? '',
                    'designation' => '',
                    'date_of_birth' => $row->employee_dob ? $row->employee_dob->format('Y-m-d') : null,
                    'blood_group' => $row->blood_group ?? '',
                    'mobile_number' => $row->mobile_no ?? '',
                    'father_name' => null,
                    'card_valid_from' => $row->card_valid_from ? $row->card_valid_from->format('Y-m-d') : null,
                    'card_valid_to' => $row->card_valid_to ? $row->card_valid_to->format('Y-m-d') : null,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Lookup is currently supported only for Permanent and Family ID cards.',
        ], 422);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $employeePk = $user->user_id ?? $user->pk ?? null;
        if (!$employeePk) {
            abort(403);
        }

        // Base validation
        $baseRules = [
            'id_card_type' => 'required|in:Permanent,Contractual,Family',
            'id_card_number' => 'required|string|max:20',
            'photo' => 'required|file|image|max:2048',
            'id_proof' => 'required|integer',
            'aadhar_doc' => 'required|file|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120',
            'employee_name' => 'required|string|max:100',
            'designation' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date',
            'blood_group' => 'nullable|string|max:5',
            'mobile_number' => 'nullable|string|max:20',
            'father_name' => 'nullable|string|max:255',
            'card_reason' => 'required|string|max:255',
            'card_valid_from' => 'nullable|date',
            'card_valid_to' => 'nullable|date|after_or_equal:card_valid_from',
        ];

        // Conditional validation based on card_reason
        $cardReason = $request->get('card_reason');
        if ($cardReason === 'Damage Card') {
            $baseRules['damage_doc'] = 'required|file|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120';
        } elseif ($cardReason === 'Card Lost') {
            $baseRules['fir_doc'] = 'required|file|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120';
        } elseif ($cardReason === 'Service Extended') {
            $baseRules['service_ext'] = 'required|file|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120';
        } elseif ($cardReason === 'Change in Name') {
            $baseRules['new_employee_name'] = 'required|string|max:100';
            $baseRules['name_proof'] = 'required|file|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120';
        } elseif ($cardReason === 'Designation Change') {
            $baseRules['designation_order'] = 'required|file|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120';
        }

        $validated = $request->validate($baseRules);

        // Build approver chain from idcard_request_approvar_master_new
        // Prefer duplicate_status=1; fallback to per_status=1 or cont_status=1 if not configured
        $approvers = DB::table('idcard_request_approvar_master_new')
            ->where('employee_master_pk', $employeePk)
            ->where('type', 'employee')
            ->where('duplicate_status', 1)
            ->orderBy('sequence', 'asc')
            ->pluck('employees_pk')
            ->filter()
            ->values();

        if ($approvers->isEmpty()) {
            $approvers = DB::table('idcard_request_approvar_master_new')
                ->where('employee_master_pk', $employeePk)
                ->where('type', 'employee')
                ->where(function ($q) {
                    $q->where('per_status', 1)->orWhere('cont_status', 1);
                })
                ->orderBy('sequence', 'asc')
                ->pluck('employees_pk')
                ->filter()
                ->values();
        }

        // If no approvers found, allow submission anyway - request will stay Pending until approvers are configured
        $now = now()->format('Y-m-d H:i:s');

        $photoPath = $request->file('photo')->store('idcard/photos', 'public');

        DB::transaction(function () use ($validated, $employeePk, $approvers, $now, $photoPath, $request, $cardReason) {
            if ($validated['id_card_type'] === 'Permanent') {
                $nextPk = (int) DB::table('security_dup_perm_id_apply')->max('pk') + 1;
                $applyId = 'DUP' . str_pad((string) $nextPk, 5, '0', STR_PAD_LEFT);

                $aadharFile = null;
                if ($request->hasFile('aadhar_doc')) {
                    $ext = $request->file('aadhar_doc')->getClientOriginalExtension();
                    $file = $applyId . '_IDPROOF_' . time() . '.' . $ext;
                    $request->file('aadhar_doc')->storeAs('idcard/dup_docs', $file, 'public');
                    $aadharFile = $file;
                }

                // Handle reason-specific documents for Permanent
                $reasonDocData = $this->handleReasonDocuments($request, $applyId, $cardReason);

                // Permanent duplicate table doesn't have id_proof/aadhar_doc columns; store in service_ext as fallback doc.
                SecurityDupPermIdApply::create([
                    'emp_id_apply' => $applyId,
                    'employee_master_pk' => $employeePk,
                    'designation_pk' => null,
                    'card_valid_from' => $validated['card_valid_from'] ?? null,
                    'card_valid_to' => $validated['card_valid_to'] ?? null,
                    'id_card_no' => $validated['id_card_number'],
                    'id_status' => SecurityParmIdApply::ID_STATUS_PENDING,
                    'remarks' => null,
                    'created_by' => $employeePk,
                    'created_date' => $now,
                    'id_photo_path' => $photoPath,
                    'employee_dob' => $validated['date_of_birth'] ?? null,
                    'mobile_no' => $validated['mobile_number'] ?? null,
                    'blood_group' => $validated['blood_group'] ?? null,
                    'service_ext' => $reasonDocData['service_ext'] ?? $aadharFile,
                    'card_reason' => $validated['card_reason'],
                    'payment_receipt' => $reasonDocData['payment_receipt'] ?? null,
                    'fir_doc' => $reasonDocData['fir_doc'] ?? null,
                ]);

                // Additional fields for Change in Name
                if ($cardReason === 'Change in Name' && isset($reasonDocData['new_name'])) {
                    DB::table('security_dup_perm_id_apply')
                        ->where('emp_id_apply', $applyId)
                        ->update(['employee_name' => $reasonDocData['new_name']]);
                }

                // Case 2 - Extension/Duplicate ID Card (Own Permanent): Only security_dup_perm_id_apply at request time.
                // security_dup_perm_id_apply_approval rows are inserted when approvers approve.
            } else {
                $nextPk = (int) DB::table('security_dup_other_id_apply')->max('pk') + 1;
                $applyId = 'DUO' . str_pad((string) $nextPk, 5, '0', STR_PAD_LEFT);

                $aadharFile = null;
                if ($request->hasFile('aadhar_doc')) {
                    $ext = $request->file('aadhar_doc')->getClientOriginalExtension();
                    $file = $applyId . '_IDPROOF_' . time() . '.' . $ext;
                    $request->file('aadhar_doc')->storeAs('idcard/dup_docs', $file, 'public');
                    $aadharFile = $file;
                }

                // Handle reason-specific documents for Other
                $reasonDocData = $this->handleReasonDocuments($request, $applyId, $cardReason);

                $authEmp = EmployeeMaster::where('pk', $employeePk)->orWhere('pk_old', $employeePk)->first(['department_master_pk']);
                $sectionPk = $authEmp->department_master_pk ?? null;
                // Approval I shows requests where department_approval_emp_pk = current user; set to first approver so request appears in approval list
                $firstApproverPk = $approvers->isNotEmpty() ? (int) $approvers->first() : null;

                SecurityDupOtherIdApply::create([
                    'emp_id_apply' => $applyId,
                    'employee_name' => $validated['employee_name'],
                    'designation_name' => $validated['designation'] ?? null,
                    'card_valid_from' => $validated['card_valid_from'] ?? null,
                    'card_valid_to' => $validated['card_valid_to'] ?? null,
                    'id_card_no' => $validated['id_card_number'],
                    'id_status' => SecurityParmIdApply::ID_STATUS_PENDING,
                    'remarks' => null,
                    'created_by' => $employeePk,
                    'created_date' => $now,
                    'id_photo_path' => $photoPath,
                    'employee_dob' => $validated['date_of_birth'] ?? null,
                    'mobile_no' => $validated['mobile_number'] ?? null,
                    'blood_group' => $validated['blood_group'] ?? null,
                    'card_reason' => $validated['card_reason'],
                    'card_type' => $validated['id_card_type'],
                    'section' => $sectionPk,
                    'department_approval_emp_pk' => $firstApproverPk,
                    'id_proof' => (int) $validated['id_proof'],
                    'aadhar_doc' => $aadharFile,
                    'service_ext' => $reasonDocData['service_ext'] ?? null,
                    'payment_receipt' => $reasonDocData['payment_receipt'] ?? null,
                    'fir_doc' => $reasonDocData['fir_doc'] ?? null,
                ]);

                // Additional fields for Change in Name
                if ($cardReason === 'Change in Name' && isset($reasonDocData['new_name'])) {
                    DB::table('security_dup_other_id_apply')
                        ->where('emp_id_apply', $applyId)
                        ->update(['employee_name' => $reasonDocData['new_name']]);
                }

                // Case 4 - Extension/Duplicate ID Card (Other/Contractual): Only security_dup_other_id_apply at request time.
                // security_dup_other_id_apply_approval rows are inserted when approvers approve.
            }
        });

        return redirect()->route('admin.duplicate_idcard.index')->with('success', 'Duplicate ID Card request submitted successfully.');
    }

    /**
     * Handle reason-specific document uploads and processing
     */
    private function handleReasonDocuments(Request $request, string $applyId, string $cardReason): array
    {
        $data = [];

        if ($cardReason === 'Damage Card' && $request->hasFile('damage_doc')) {
            $ext = $request->file('damage_doc')->getClientOriginalExtension();
            $file = $applyId . '_DAMAGE_PROOF_' . time() . '.' . $ext;
            $request->file('damage_doc')->storeAs('idcard/dup_docs', $file, 'public');
            $data['fir_doc'] = $file; // Reusing fir_doc column for damage proof
        } elseif ($cardReason === 'Card Lost' && $request->hasFile('fir_doc')) {
            $ext = $request->file('fir_doc')->getClientOriginalExtension();
            $file = $applyId . '_FIR_' . time() . '.' . $ext;
            $request->file('fir_doc')->storeAs('idcard/dup_docs', $file, 'public');
            $data['fir_doc'] = $file;
        } elseif ($cardReason === 'Service Extended' && $request->hasFile('service_ext')) {
            $ext = $request->file('service_ext')->getClientOriginalExtension();
            $file = $applyId . '_SERVICE_EXT_' . time() . '.' . $ext;
            $request->file('service_ext')->storeAs('idcard/dup_docs', $file, 'public');
            $data['service_ext'] = $file;
        } elseif ($cardReason === 'Change in Name') {
            if ($request->has('new_employee_name')) {
                $data['new_name'] = $request->get('new_employee_name');
            }
            if ($request->hasFile('name_proof')) {
                $ext = $request->file('name_proof')->getClientOriginalExtension();
                $file = $applyId . '_NAME_PROOF_' . time() . '.' . $ext;
                $request->file('name_proof')->storeAs('idcard/dup_docs', $file, 'public');
                $data['payment_receipt'] = $file; // Reusing payment_receipt column for name proof
            }
        } elseif ($cardReason === 'Designation Change' && $request->hasFile('designation_order')) {
            $ext = $request->file('designation_order')->getClientOriginalExtension();
            $file = $applyId . '_DESIG_ORDER_' . time() . '.' . $ext;
            $request->file('designation_order')->storeAs('idcard/dup_docs', $file, 'public');
            $data['payment_receipt'] = $file; // Reusing payment_receipt column for designation order
        }

        return $data;
    }

    private function statusLabelForDup(string $source, string $applyId): string
    {
        if ($source === 'perm') {
            $rej = SecurityDupPermIdApplyApproval::where('security_parm_id_apply_pk', $applyId)->where('status', 3)->latest('pk')->first();
            if ($rej) {
                return 'Rejected';
            }
            $a2 = SecurityDupPermIdApplyApproval::where('security_parm_id_apply_pk', $applyId)->where('status', 2)->latest('pk')->first();
            if ($a2) {
                $name = EmployeeMaster::where('pk', $a2->approval_emp_pk)->value(DB::raw("TRIM(CONCAT(IFNULL(first_name,''),' ',IFNULL(last_name,'')))"));
                return $name ? ('Approved By ' . $name) : 'Approved';
            }
            return 'Pending';
        }

        $rej = SecurityDupOtherIdApplyApproval::where('security_con_id_apply_pk', $applyId)->where('status', 3)->latest('pk')->first();
        if ($rej) {
            return 'Rejected';
        }
        $a2 = SecurityDupOtherIdApplyApproval::where('security_con_id_apply_pk', $applyId)->where('status', 2)->latest('pk')->first();
        if ($a2) {
            $name = EmployeeMaster::where('pk', $a2->approval_emp_pk)->value(DB::raw("TRIM(CONCAT(IFNULL(first_name,''),' ',IFNULL(last_name,'')))"));
            return $name ? ('Approved By ' . $name) : 'Approved';
        }
        return 'Pending';
    }
}

