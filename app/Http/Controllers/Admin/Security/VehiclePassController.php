<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Exports\VehiclePassExport;
use App\Support\IdCardSecurityMapper;
use App\Models\VehiclePassTWApply;
use App\Models\VehiclePassFWApply;
use App\Models\SecVehicleType;
use App\Models\EmployeeMaster;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class VehiclePassController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $user_old_pk = EmployeeMaster::where('pk', $user->user_id)->first();
       
        $employeePk = $user->user_id ?? null;
        $pk_old = $user_old_pk->pk_old ?? null;

        // IMPORTANT: Group creator conditions so status filters apply correctly.
        // Otherwise "where(veh_created_by = X) OR (veh_created_by = pk_old AND status = ...)"
        // would cause records to appear in both Pending and Archive regardless of status.
        $baseQuery = fn () => VehiclePassTWApply::with(['vehicleType', 'employee'])
            ->withExists(['approvals' => function ($q) {
                $q->where(function ($w) {
                    $w->whereIn('status', [1, 2, 3])
                        ->orWhereIn('veh_recommend_status', [1, 2, 3]);
                });
            }])
            ->where(function ($q) use ($employeePk, $pk_old) {
                $q->where('veh_created_by', $employeePk);
                if ($pk_old) {
                    $q->orWhere('veh_created_by', $pk_old);
                }
            })
            ->orderBy('created_date', 'desc');
            

        $activePasses = $baseQuery()->where('vech_card_status', 1)->paginate(10);
        $archivedPasses = $baseQuery()->whereIn('vech_card_status', [2, 3])->paginate(10, ['*'], 'archive_page');

        return view('admin.security.vehicle_pass.index', compact('activePasses', 'archivedPasses'));
    }

    /**
     * Export vehicle pass requests to Excel, CSV or PDF.
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $employeePk = $user->user_id ?? $user->pk ?? null;
        $tab = $request->get('tab', 'active');
        $format = $request->get('format', 'xlsx');

        if (! in_array($tab, ['active', 'archive', 'all'])) {
            $tab = 'active';
        }

        $filename = 'vehicle_pass_requests_' . $tab . '_' . now()->format('Y-m-d_His');

        $baseQuery = VehiclePassTWApply::with(['vehicleType', 'employee'])
            ->where('veh_created_by', $employeePk)
            ->orderBy('created_date', 'desc');

        if ($format === 'pdf') {
            $query = match ($tab) {
                'archive' => (clone $baseQuery)->whereIn('vech_card_status', [2, 3]),
                'all' => clone $baseQuery,
                default => (clone $baseQuery)->where('vech_card_status', 1),
            };
            $passes = $query->get();

            $pdf = Pdf::loadView('admin.security.vehicle_pass.export_pdf', [
                'passes' => $passes,
                'tab' => $tab,
                'export_date' => now()->format('d/m/Y H:i'),
            ])
                ->setPaper('a4', 'landscape')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true);

            return $pdf->download($filename . '.pdf');
        }

        if ($format === 'csv') {
            return Excel::download(
                new VehiclePassExport($tab, $employeePk),
                $filename . '.csv',
                \Maatwebsite\Excel\Excel::CSV
            );
        }

        return Excel::download(
            new VehiclePassExport($tab, $employeePk),
            $filename . '.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function create()
    {
        $vehicleTypes = SecVehicleType::active()->get();
        $currentUserEmployee = $this->currentUserEmployeeForVehiclePass();
        $idCardValidityCapYmd = null;
        $user = Auth::user();
        $sessionPk = $user->user_id ?? $user->pk ?? null;
        $canonical = IdCardSecurityMapper::resolveCanonicalEmployeeMasterPk($sessionPk);
        if ($canonical) {
            $cap = IdCardSecurityMapper::approvedEmployeeIdCardValidityEnd($canonical, null);
            if ($cap) {
                $idCardValidityCapYmd = $cap->format('Y-m-d');
            }
        }

        return view('admin.security.vehicle_pass.create', compact('vehicleTypes', 'currentUserEmployee', 'idCardValidityCapYmd'));
    }

    /**
     * Logged-in employee row for create/edit autofill (Employee / Government Vehicle).
     * ID card is not validated or enforced for these applicant types.
     */
    private function currentUserEmployeeForVehiclePass(): ?object
    {
        $user = Auth::user();
        $employeePk = $user->user_id ?? null;
        if (! $employeePk) {
            return null;
        }
        $emp = EmployeeMaster::with(['designation', 'department'])
            ->where('status', 1)
            ->where(function ($q) use ($employeePk) {
                $q->where('pk', $employeePk)->orWhere('pk_old', $employeePk);
            })
            ->first();
        if (! $emp) {
            return null;
        }

        $empIdDisplay = $emp->emp_id ?? '';
        $canonical = IdCardSecurityMapper::resolveCanonicalEmployeeMasterPk((int) $emp->pk);
        if ($canonical) {
            $resolvedNo = IdCardSecurityMapper::resolvedDisplayIdCardNumberForEmployee($canonical);
            if (is_string($resolvedNo) && $resolvedNo !== '') {
                $empIdDisplay = $resolvedNo;
            }
        }

        return (object) [
            'pk' => $emp->pk,
            'name' => trim($emp->first_name . ' ' . ($emp->last_name ?? '')),
            'designation' => $emp->designation->designation_name ?? '',
            'department' => $emp->department->department_name ?? '',
            'emp_id' => $empIdDisplay,
        ];
    }

    /**
     * Others applicant: resolve name / designation / department from the first matching source:
     * employee_master, security_parm_id_apply (permanent ID applications), optional security_parm_oth_id_apply,
     * security_con_oth_id_apply (contractual ID applications).
     */
    public function lookupByIdCard(Request $request)
    {
        $lookup = trim((string) $request->get('id_card_number', ''));
        if ($lookup === '') {
            return response()->json([
                'success' => false,
                'message' => 'ID Card Number is required.',
            ], 422);
        }

        $data = $this->vehiclePassLookupEmployeeMasterRow($lookup);
        if ($data) {
            return response()->json([
                'success' => true,
                'data' => $this->vehiclePassBuildLookupPayloadFromEmployeeJoinRow($data, $lookup),
            ]);
        }

        foreach (['security_parm_id_apply', 'security_parm_oth_id_apply'] as $parmTable) {
            if (! Schema::hasTable($parmTable)) {
                continue;
            }
            $fromParm = $this->vehiclePassLookupFromSecurityParmLikeTable($lookup, $parmTable);
            if ($fromParm !== null) {
                return response()->json(['success' => true, 'data' => $fromParm]);
            }
        }

        $fromCon = $this->vehiclePassLookupFromSecurityConOthIdApply($lookup);
        if ($fromCon !== null) {
            return response()->json(['success' => true, 'data' => $fromCon]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No record found for this ID in employee master or security ID card applications.',
        ], 404);
    }

    /**
     * @return object|null  pk, first_name, last_name, emp_id, designation_name, department_name
     */
    private function vehiclePassLookupEmployeeMasterRow(string $lookup): ?object
    {
        return DB::table('employee_master as em')
            ->leftJoin('designation_master as dm', 'dm.pk', '=', 'em.designation_master_pk')
            ->leftJoin('department_master as dept', 'dept.pk', '=', 'em.department_master_pk')
            ->where(function ($q) use ($lookup) {
                $q->where('em.emp_id', $lookup)
                    ->orWhereRaw('TRIM(em.emp_id) = ?', [trim($lookup)]);
                if (ctype_digit($lookup)) {
                    $q->orWhere('em.pk', $lookup);
                    if (Schema::hasColumn('employee_master', 'pk_old')) {
                        $q->orWhere('em.pk_old', $lookup);
                    }
                }
            })
            ->orderBy('em.pk')
            ->select([
                'em.pk',
                'em.first_name',
                'em.last_name',
                'em.emp_id',
                'dm.designation_name',
                'dept.department_name',
            ])
            ->first();
    }

    /**
     * @param  object  $em  row from vehiclePassLookupEmployeeMasterRow()
     */
    private function vehiclePassBuildLookupPayloadFromEmployeeJoinRow(object $em, string $lookupFallback, ?string $preferredEmployeeIdCard = null): array
    {
        $name = trim(($em->first_name ?? '') . ' ' . ($em->last_name ?? ''));
        $empCode = $em->emp_id !== null && (string) $em->emp_id !== ''
            ? (string) $em->emp_id
            : (string) $lookupFallback;
        if ($preferredEmployeeIdCard !== null && $preferredEmployeeIdCard !== '') {
            $empCode = $preferredEmployeeIdCard;
        }

        $canonicalPk = IdCardSecurityMapper::resolveCanonicalEmployeeMasterPk((int) $em->pk);
        $idCardCap = $canonicalPk ? IdCardSecurityMapper::approvedEmployeeIdCardValidityEnd($canonicalPk, null) : null;

        return [
            'employee_id_card' => $empCode,
            'applicant_name' => $name,
            'designation' => (string) ($em->designation_name ?? ''),
            'department' => (string) ($em->department_name ?? ''),
            'emp_master_pk' => (int) $em->pk,
            'emp_id' => $em->emp_id !== null && (string) $em->emp_id !== '' ? (string) $em->emp_id : null,
            'id_card_valid_to' => $idCardCap ? $idCardCap->format('Y-m-d') : null,
        ];
    }

    /**
     * Permanent / parm-type ID apply tables: match id_card_no or emp_id_apply, prefer linked employee_master.
     *
     * @return array<string, mixed>|null
     */
    private function vehiclePassLookupFromSecurityParmLikeTable(string $lookup, string $table): ?array
    {
        $parts = [];
        $bindings = [];
        if (Schema::hasColumn($table, 'id_card_no')) {
            $parts[] = '(id_card_no = ? OR TRIM(COALESCE(id_card_no, "")) = ?)';
            $bindings[] = $lookup;
            $bindings[] = trim($lookup);
        }
        if (Schema::hasColumn($table, 'emp_id_apply')) {
            $parts[] = '(emp_id_apply = ? OR TRIM(COALESCE(emp_id_apply, "")) = ?)';
            $bindings[] = $lookup;
            $bindings[] = trim($lookup);
        }
        if ($parts === []) {
            return null;
        }

        $orderCol = 'created_date';
        if (! Schema::hasColumn($table, 'created_date')) {
            $orderCol = Schema::hasColumn($table, 'pk') ? 'pk' : 'emp_id_apply';
        }

        $row = DB::table($table)
            ->whereRaw('('.implode(' OR ', $parts).')', $bindings)
            ->orderByDesc($orderCol)
            ->first();

        if (! $row) {
            return null;
        }

        $displayId = null;
        if (Schema::hasColumn($table, 'id_card_no') && ! empty($row->id_card_no)) {
            $displayId = trim((string) $row->id_card_no);
        }
        if (($displayId === null || $displayId === '') && Schema::hasColumn($table, 'emp_id_apply') && ! empty($row->emp_id_apply)) {
            $displayId = trim((string) $row->emp_id_apply);
        }

        $empPk = Schema::hasColumn($table, 'employee_master_pk') ? (int) ($row->employee_master_pk ?? 0) : 0;
        if ($empPk > 0) {
            $em = DB::table('employee_master as em')
                ->leftJoin('designation_master as dm', 'dm.pk', '=', 'em.designation_master_pk')
                ->leftJoin('department_master as dept', 'dept.pk', '=', 'em.department_master_pk')
                ->where('em.pk', $empPk)
                ->select([
                    'em.pk',
                    'em.first_name',
                    'em.last_name',
                    'em.emp_id',
                    'dm.designation_name',
                    'dept.department_name',
                ])
                ->first();
            if ($em) {
                return $this->vehiclePassBuildLookupPayloadFromEmployeeJoinRow(
                    $em,
                    $lookup,
                    ($displayId !== null && $displayId !== '') ? $displayId : null
                );
            }
        }

        if (Schema::hasColumn($table, 'emp_id_apply') && ! empty($row->emp_id_apply)) {
            $em = $this->vehiclePassLookupEmployeeMasterRow(trim((string) $row->emp_id_apply));
            if ($em) {
                return $this->vehiclePassBuildLookupPayloadFromEmployeeJoinRow(
                    $em,
                    $lookup,
                    ($displayId !== null && $displayId !== '') ? $displayId : null
                );
            }
        }

        $designation = '';
        if (Schema::hasColumn($table, 'designation_pk') && ! empty($row->designation_pk)) {
            $designation = (string) (DB::table('designation_master')->where('pk', $row->designation_pk)->value('designation_name') ?? '');
        }

        $name = Schema::hasColumn($table, 'employee_name')
            ? trim((string) ($row->employee_name ?? ''))
            : '';

        $idCardCap = null;
        if (Schema::hasColumn($table, 'card_valid_to') && ! empty($row->card_valid_to)) {
            try {
                $parsed = Carbon::parse($row->card_valid_to);
                $idCardCap = $parsed;
            } catch (\Throwable $e) {
                $idCardCap = null;
            }
        }

        if ($name === '' && $designation === '' && $idCardCap === null) {
            return null;
        }

        return [
            'employee_id_card' => ($displayId !== null && $displayId !== '') ? $displayId : $lookup,
            'applicant_name' => $name,
            'designation' => $designation,
            'department' => '',
            'emp_master_pk' => $empPk > 0 ? $empPk : null,
            'emp_id' => ($displayId !== null && $displayId !== '') ? $displayId : null,
            'id_card_valid_to' => $idCardCap ? $idCardCap->format('Y-m-d') : null,
        ];
    }

    /**
     * Contractual other ID applications (security_con_oth_id_apply).
     *
     * @return array<string, mixed>|null
     */
    private function vehiclePassLookupFromSecurityConOthIdApply(string $lookup): ?array
    {
        if (! Schema::hasTable('security_con_oth_id_apply')) {
            return null;
        }

        $conParts = [];
        $conBindings = [];
        if (Schema::hasColumn('security_con_oth_id_apply', 'id_card_no')) {
            $conParts[] = '(id_card_no = ? OR TRIM(COALESCE(id_card_no, "")) = ?)';
            $conBindings[] = $lookup;
            $conBindings[] = trim($lookup);
        }
        if (Schema::hasColumn('security_con_oth_id_apply', 'emp_id_apply')) {
            $conParts[] = '(emp_id_apply = ? OR TRIM(COALESCE(emp_id_apply, "")) = ?)';
            $conBindings[] = $lookup;
            $conBindings[] = trim($lookup);
        }
        if ($conParts === []) {
            return null;
        }

        $conOrder = Schema::hasColumn('security_con_oth_id_apply', 'created_date') ? 'created_date' : 'pk';

        $con = DB::table('security_con_oth_id_apply')
            ->whereRaw('('.implode(' OR ', $conParts).')', $conBindings)
            ->orderByDesc($conOrder)
            ->first();

        if (! $con) {
            return null;
        }

        $displayId = null;
        if (Schema::hasColumn('security_con_oth_id_apply', 'id_card_no') && ! empty($con->id_card_no)) {
            $displayId = (string) $con->id_card_no;
        }
        if ($displayId === null && Schema::hasColumn('security_con_oth_id_apply', 'emp_id_apply') && ! empty($con->emp_id_apply)) {
            $displayId = (string) $con->emp_id_apply;
        }

        $empPk = Schema::hasColumn('security_con_oth_id_apply', 'employee_master_pk') ? (int) ($con->employee_master_pk ?? 0) : 0;
        if ($empPk > 0) {
            $em = DB::table('employee_master as em')
                ->leftJoin('designation_master as dm', 'dm.pk', '=', 'em.designation_master_pk')
                ->leftJoin('department_master as dept', 'dept.pk', '=', 'em.department_master_pk')
                ->where('em.pk', $empPk)
                ->select([
                    'em.pk',
                    'em.first_name',
                    'em.last_name',
                    'em.emp_id',
                    'dm.designation_name',
                    'dept.department_name',
                ])
                ->first();
            if ($em) {
                return $this->vehiclePassBuildLookupPayloadFromEmployeeJoinRow(
                    $em,
                    $lookup,
                    ($displayId !== null && $displayId !== '') ? $displayId : null
                );
            }
        }

        if (Schema::hasColumn('security_con_oth_id_apply', 'emp_id_apply') && ! empty($con->emp_id_apply)) {
            $em = $this->vehiclePassLookupEmployeeMasterRow(trim((string) $con->emp_id_apply));
            if ($em) {
                return $this->vehiclePassBuildLookupPayloadFromEmployeeJoinRow(
                    $em,
                    $lookup,
                    ($displayId !== null && $displayId !== '') ? $displayId : null
                );
            }
        }

        $name = Schema::hasColumn('security_con_oth_id_apply', 'employee_name')
            ? trim((string) ($con->employee_name ?? ''))
            : '';
        $designation = Schema::hasColumn('security_con_oth_id_apply', 'designation_name')
            ? (string) ($con->designation_name ?? '')
            : '';
        $department = '';
        if (Schema::hasColumn('security_con_oth_id_apply', 'section') && ! empty($con->section) && Schema::hasTable('department_master')) {
            $department = (string) (DB::table('department_master')->where('pk', $con->section)->value('department_name') ?? '');
        }

        $idCardCap = null;
        if (Schema::hasColumn('security_con_oth_id_apply', 'card_valid_to') && ! empty($con->card_valid_to)) {
            try {
                $idCardCap = Carbon::parse($con->card_valid_to);
            } catch (\Throwable $e) {
                $idCardCap = null;
            }
        }

        return [
            'employee_id_card' => ($displayId !== null && $displayId !== '') ? $displayId : $lookup,
            'applicant_name' => $name,
            'designation' => $designation,
            'department' => $department,
            'emp_master_pk' => $empPk > 0 ? $empPk : null,
            'emp_id' => ($displayId !== null && $displayId !== '') ? $displayId : null,
            'id_card_valid_to' => $idCardCap ? $idCardCap->format('Y-m-d') : null,
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'applicant_type' => ['required', 'in:employee,others,government_vehicle'],
            'employee_id_card' => ['nullable', 'string', 'max:100'],
            'applicant_name' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'emp_master_pk' => ['nullable', 'exists:employee_master,pk'],
            'vehicle_type' => ['required', 'exists:sec_vehicle_type,pk'],
            'vehicle_no' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9\s\-]{3,20}$/'],
            'doc_upload' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'veh_card_valid_from' => ['required', 'date', 'after_or_equal:today'],
            'vech_card_valid_to' => ['required', 'date', 'after_or_equal:veh_card_valid_from'],
        ], [
            'vehicle_no.regex' => 'Vehicle number must be 3–20 characters (letters, numbers, spaces or hyphens only).',
            'veh_card_valid_from.after_or_equal' => 'Start date cannot be in the past.',
            'vech_card_valid_to.after_or_equal' => 'End date must be on or after start date.',
        ]);

        $user = Auth::user();
        $employeePk = $user->user_id ?? $user->pk ?? null;

        $applicantType = $validated['applicant_type'];
        $applicantName = $validated['applicant_name'] ?? null;
        $designation = $validated['designation'] ?? null;
        $department = $validated['department'] ?? null;
        $employeeIdCard = $validated['employee_id_card'] ?? null;
        $empMasterPk = $validated['emp_master_pk'] ?? null;

        // Employee / Government Vehicle: always use logged-in user's employee (no selection from request).
        // Others: optional emp_master_pk from employee lookup (stored for correct name on show / list).
        if (in_array($applicantType, ['employee', 'government_vehicle'])) {
            $empMasterPk = $employeePk;
        } elseif ($applicantType === 'others') {
            $empMasterPk = $validated['emp_master_pk'] ?? null;
        }

        if (in_array($applicantType, ['employee', 'government_vehicle']) && $empMasterPk) {
            $emp = EmployeeMaster::with(['designation', 'department'])->find($empMasterPk);
            if ($emp) {
                $applicantName = $applicantName ?: trim($emp->first_name . ' ' . ($emp->last_name ?? ''));
                $designation = $designation ?: ($emp->designation->designation_name ?? null);
                $department = $department ?: ($emp->department->department_name ?? null);
                $employeeIdCard = $employeeIdCard ?: ($emp->emp_id ?? null);
            }
        }

        $canonicalForCap = null;
        if (in_array($applicantType, ['employee', 'government_vehicle'], true)) {
            $canonicalForCap = IdCardSecurityMapper::resolveCanonicalEmployeeMasterPk($employeePk);
        } elseif ($applicantType === 'others') {
            $othersEmpPk = isset($validated['emp_master_pk']) ? (int) $validated['emp_master_pk'] : 0;
            if ($othersEmpPk > 0) {
                $canonicalForCap = IdCardSecurityMapper::resolveCanonicalEmployeeMasterPk($othersEmpPk);
            }
            if (! $canonicalForCap) {
                $idCardRaw = trim((string) ($validated['employee_id_card'] ?? ''));
                if ($idCardRaw !== '') {
                    $canonicalForCap = IdCardSecurityMapper::resolveCanonicalFromPrintedIdCardNumber($idCardRaw);
                }
            }
        }
        if ($canonicalForCap) {
            IdCardSecurityMapper::assertPassValidityWithinApprovedEmployeeIdCard(
                $canonicalForCap,
                null,
                $validated['veh_card_valid_from'],
                $validated['vech_card_valid_to'],
                'veh_card_valid_from',
                'vech_card_valid_to'
            );
        }

        if ($employeePk && $this->applicantHasBlockingDuplicateVehicleNumber((int) $employeePk, $validated['vehicle_no'])) {
            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'vehicle_no' => 'You already have a pending or approved request for this vehicle number. You can apply again only after that request is rejected.',
                ]);
        }

        $govVeh = $applicantType === 'government_vehicle' ? 1 : 0;

        // Handle file upload
        $docPath = null;
        if ($request->hasFile('doc_upload')) {
            $docPath = $request->file('doc_upload')->store('vehicle_documents', 'public');
        }

        // Generate vehicle_tw_pk per SQL: TW + zero-padded next pk (primary key in table is vehicle_tw_pk)
        $nextPk = (int) DB::table('vehicle_pass_tw_apply')->max('pk') + 1;
        $vehicleTwPk = 'TW' . str_pad($nextPk, 5, '0', STR_PAD_LEFT);

        $vehicleReqId = $this->generateVehicleReqId($validated['vehicle_type']);

        $vehiclePass = new VehiclePassTWApply();
        $vehiclePass->vehicle_tw_pk = $vehicleTwPk;
        $vehiclePass->employee_id_card = $employeeIdCard ?? '';
        $vehiclePass->emp_master_pk = $empMasterPk;
        $vehiclePass->applicant_type = VehiclePassTWApply::applicantTypeFormToInt($applicantType);

        $vehiclePass->vehicle_type = $validated['vehicle_type'];
        $vehiclePass->vehicle_no = $validated['vehicle_no'];
        $vehiclePass->vehicle_req_id = $vehicleReqId;
        $vehiclePass->doc_upload = $docPath;
        $vehiclePass->vehicle_card_reapply = 0;
        $vehiclePass->veh_card_valid_from = $validated['veh_card_valid_from'];
        $vehiclePass->vech_card_valid_to = $validated['vech_card_valid_to'];
        $vehiclePass->vech_card_status = 1; // Pending
        $vehiclePass->veh_card_forward_status = 0; // Not forwarded
        $vehiclePass->veh_created_by = $employeePk;
        $vehiclePass->gov_veh = $govVeh;
        $vehiclePass->created_date = now();
        $vehiclePass->save();

        // Case 6 - Vehicle Pass Request (Two Wheeler): Only vehicle_pass_tw_apply at request time.
        // vehicle_pass_tw_apply_approval rows are inserted when approvers approve (VehiclePassApprovalController).
        // Condition: Employee ID card must be valid (not expired), vehicle no must be valid.

        return redirect()->route('admin.security.vehicle_pass.index')->with('success', 'Vehicle Pass application submitted successfully');
    }

    public function show($id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $vehiclePass = VehiclePassTWApply::with(['vehicleType', 'employee', 'approvals.approvedBy'])
            ->findOrFail($pk);
        $canModifyApplication = $this->applicantCanModifyVehiclePass($vehiclePass);

        return view('admin.security.vehicle_pass.show', compact('vehiclePass', 'canModifyApplication'));
    }

    public function edit($id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $vehiclePass = VehiclePassTWApply::with(['employee.designation', 'employee.department'])->findOrFail($pk);
        
        // Only allow editing if status is pending and no approver has acted yet
        if ($vehiclePass->vech_card_status != 1) {
            return redirect()->route('admin.security.vehicle_pass.index')->with('error', 'Cannot edit approved/rejected application');
        }
        if (! $this->applicantCanModifyVehiclePass($vehiclePass)) {
            return redirect()->route('admin.security.vehicle_pass.show', encrypt($pk))
                ->with('error', 'This application cannot be edited because the approval process has already started.');
        }

        $vehicleTypes = SecVehicleType::active()->get();
        $editApplicantDisplay = $this->resolveVehiclePassApplicantDisplayForEdit($vehiclePass);
        $currentUserEmployee = $this->currentUserEmployeeForVehiclePass();
        $idCardValidityCapYmd = null;
        $capPk = $vehiclePass->emp_master_pk
            ? IdCardSecurityMapper::resolveCanonicalEmployeeMasterPk((int) $vehiclePass->emp_master_pk)
            : null;
        if ($capPk) {
            $cap = IdCardSecurityMapper::approvedEmployeeIdCardValidityEnd($capPk, null);
            if ($cap) {
                $idCardValidityCapYmd = $cap->format('Y-m-d');
            }
        }

        return view('admin.security.vehicle_pass.edit', compact(
            'vehiclePass',
            'vehicleTypes',
            'editApplicantDisplay',
            'currentUserEmployee',
            'idCardValidityCapYmd'
        ));
    }

    /**
     * @return array{name: string, designation: string, department: string}
     */
    private function resolveVehiclePassApplicantDisplayForEdit(VehiclePassTWApply $vehiclePass): array
    {
        $name = '';
        $designation = '';
        $department = '';

        if ($vehiclePass->employee) {
            $emp = $vehiclePass->employee;
            $name = trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? ''));
            if ($emp->relationLoaded('designation') && $emp->designation) {
                $designation = (string) ($emp->designation->designation_name ?? '');
            }
            if ($emp->relationLoaded('department') && $emp->department) {
                $department = (string) ($emp->department->department_name ?? '');
            }
        }

        if ($name === '') {
            $name = trim((string) ($vehiclePass->applicant_name ?? ''));
        }
        if ($name === '') {
            $name = (string) (VehiclePassTWApply::resolveNameByEmployeeIdCard($vehiclePass->employee_id_card) ?? '');
        }

        if ($designation === '') {
            $designation = trim((string) ($vehiclePass->designation ?? ''));
        }
        if ($department === '') {
            $department = trim((string) ($vehiclePass->department ?? ''));
        }

        if ($name === '' || $designation === '' || $department === '') {
            $row = $this->fetchEmployeeMasterDisplayRowForVehiclePass($vehiclePass);
            if ($row) {
                if ($name === '') {
                    $name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
                }
                if ($designation === '') {
                    $designation = (string) ($row->designation_name ?? '');
                }
                if ($department === '') {
                    $department = (string) ($row->department_name ?? '');
                }
            }
        }

        return [
            'name' => $name,
            'designation' => $designation,
            'department' => $department,
        ];
    }

    private function fetchEmployeeMasterDisplayRowForVehiclePass(VehiclePassTWApply $vehiclePass): ?object
    {
        $select = [
            'em.first_name',
            'em.last_name',
            'em.emp_id',
            'dm.designation_name',
            'dept.department_name',
        ];

        $baseQuery = fn () => DB::table('employee_master as em')
            ->leftJoin('designation_master as dm', 'dm.pk', '=', 'em.designation_master_pk')
            ->leftJoin('department_master as dept', 'dept.pk', '=', 'em.department_master_pk')
            ->select($select);

        if ($vehiclePass->emp_master_pk) {
            $row = $baseQuery()->where('em.pk', $vehiclePass->emp_master_pk)->first();
            if ($row) {
                return $row;
            }
        }

        $card = trim((string) ($vehiclePass->employee_id_card ?? ''));
        if ($card === '') {
            return null;
        }

        return $baseQuery()
            ->where(function ($w) use ($card) {
                $w->where('em.emp_id', $card)
                    ->orWhereRaw('TRIM(em.emp_id) = ?', [trim($card)]);
                if (ctype_digit($card)) {
                    $w->orWhere('em.pk', $card);
                    if (Schema::hasColumn('employee_master', 'pk_old')) {
                        $w->orWhere('em.pk_old', $card);
                    }
                }
            })
            ->orderBy('em.pk')
            ->first();
    }

    public function update(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $vehiclePass = VehiclePassTWApply::findOrFail($pk);

        // Only allow editing if status is pending and no approver has acted yet
        if ($vehiclePass->vech_card_status != 1) {
            return redirect()->route('admin.security.vehicle_pass.index')->with('error', 'Cannot edit approved/rejected application');
        }
        if (! $this->applicantCanModifyVehiclePass($vehiclePass)) {
            return redirect()->route('admin.security.vehicle_pass.show', encrypt($pk))
                ->with('error', 'This application cannot be updated because the approval process has already started.');
        }

        $validated = $request->validate([
            'applicant_type' => ['required', 'in:employee,others,government_vehicle'],
            'employee_id_card' => ['nullable', 'string', 'max:100'],
            'applicant_name' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'emp_master_pk' => ['nullable', 'exists:employee_master,pk'],
            'vehicle_type' => ['required', 'exists:sec_vehicle_type,pk'],
            'vehicle_no' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9\s\-]{3,20}$/'],
            'doc_upload' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'veh_card_valid_from' => ['required', 'date', 'after_or_equal:today'],
            'vech_card_valid_to' => ['required', 'date', 'after_or_equal:veh_card_valid_from'],
        ], [
            'vehicle_no.regex' => 'Vehicle number must be 3–20 characters (letters, numbers, spaces or hyphens only).',
            'veh_card_valid_from.after_or_equal' => 'Valid From date cannot be in the past.',
            'vech_card_valid_to.after_or_equal' => 'Valid To date must be on or after Valid From date.',
        ]);

        $user = Auth::user();
        $employeePk = $user->user_id ?? null;

        $applicantType = $validated['applicant_type'];
        $applicantName = $validated['applicant_name'] ?? null;
        $designation = $validated['designation'] ?? null;
        $department = $validated['department'] ?? null;
        $employeeIdCard = $validated['employee_id_card'] ?? null;
        $empMasterPk = $validated['emp_master_pk'] ?? null;

        // Employee / Government Vehicle: always use logged-in user's employee (no selection from request).
        if (in_array($applicantType, ['employee', 'government_vehicle'])) {
            $empMasterPk = $employeePk;
        } elseif ($applicantType === 'others') {
            $empMasterPk = $validated['emp_master_pk'] ?? $vehiclePass->emp_master_pk;
        }

        if (in_array($applicantType, ['employee', 'government_vehicle']) && $empMasterPk) {
            $emp = EmployeeMaster::with(['designation', 'department'])->find($empMasterPk);
            if ($emp) {
                $applicantName = $applicantName ?: trim($emp->first_name . ' ' . ($emp->last_name ?? ''));
                $designation = $designation ?: ($emp->designation->designation_name ?? null);
                $department = $department ?: ($emp->department->department_name ?? null);
                $employeeIdCard = $employeeIdCard ?: ($emp->emp_id ?? null);
            }
        }

        $canonicalForCap = null;
        if (in_array($applicantType, ['employee', 'government_vehicle'], true)) {
            $canonicalForCap = IdCardSecurityMapper::resolveCanonicalEmployeeMasterPk($employeePk);
        } elseif ($applicantType === 'others') {
            $othersEmpPk = isset($validated['emp_master_pk']) ? (int) $validated['emp_master_pk'] : 0;
            if ($othersEmpPk > 0) {
                $canonicalForCap = IdCardSecurityMapper::resolveCanonicalEmployeeMasterPk($othersEmpPk);
            }
            if (! $canonicalForCap) {
                $idCardRaw = trim((string) ($validated['employee_id_card'] ?? ''));
                if ($idCardRaw !== '') {
                    $canonicalForCap = IdCardSecurityMapper::resolveCanonicalFromPrintedIdCardNumber($idCardRaw);
                }
            }
        }
        if ($canonicalForCap) {
            IdCardSecurityMapper::assertPassValidityWithinApprovedEmployeeIdCard(
                $canonicalForCap,
                null,
                $validated['veh_card_valid_from'],
                $validated['vech_card_valid_to'],
                'veh_card_valid_from',
                'vech_card_valid_to'
            );
        }

        if ($employeePk && $this->applicantHasBlockingDuplicateVehicleNumber((int) $employeePk, $validated['vehicle_no'], $vehiclePass->vehicle_tw_pk)) {
            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'vehicle_no' => 'You already have a pending or approved request for this vehicle number. You can apply again only after that request is rejected.',
                ]);
        }

        $govVeh = $applicantType === 'government_vehicle' ? 1 : 0;

        // Handle file upload
        if ($request->hasFile('doc_upload')) {
            if ($vehiclePass->doc_upload) {
                Storage::disk('public')->delete($vehiclePass->doc_upload);
            }
            $vehiclePass->doc_upload = $request->file('doc_upload')->store('vehicle_documents', 'public');
        }

        $vehiclePass->employee_id_card = $employeeIdCard ?? $vehiclePass->employee_id_card;
        $vehiclePass->emp_master_pk = $empMasterPk;
        $vehiclePass->applicant_type = VehiclePassTWApply::applicantTypeFormToInt($applicantType);
        $vehiclePass->applicant_name = $applicantName;
        $vehiclePass->designation = $designation;
        $vehiclePass->department = $department;

        $vehiclePass->vehicle_type = $validated['vehicle_type'];
        $vehiclePass->vehicle_no = $validated['vehicle_no'];
        $vehiclePass->veh_card_valid_from = $validated['veh_card_valid_from'];
        $vehiclePass->vech_card_valid_to = $validated['vech_card_valid_to'];
        $vehiclePass->gov_veh = $govVeh;
        $vehiclePass->save();

        return redirect()->route('admin.security.vehicle_pass.index')->with('success', 'Vehicle Pass application updated successfully');
    }

    public function delete($id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $vehiclePass = VehiclePassTWApply::findOrFail($pk);

        // Only allow deleting if status is pending and no approver has acted yet
        if ($vehiclePass->vech_card_status != 1) {
            return redirect()->route('admin.security.vehicle_pass.index')->with('error', 'Cannot delete approved/rejected application');
        }
        if (! $this->applicantCanModifyVehiclePass($vehiclePass)) {
            return redirect()->route('admin.security.vehicle_pass.index')
                ->with('error', 'Cannot delete this application because the approval process has already started.');
        }

        // Delete uploaded document
        if ($vehiclePass->doc_upload) {
            Storage::disk('public')->delete($vehiclePass->doc_upload);
        }

        $vehiclePass->delete();

        return redirect()->route('admin.security.vehicle_pass.index')->with('success', 'Vehicle Pass application deleted successfully');
    }

    /**
     * True when any security approver has recorded an action (recommend / final approve / reject).
     * Pending-only rows (status 0, no recommend) do not count.
     */
    private function vehiclePassHasApproverAction(VehiclePassTWApply $vehiclePass): bool
    {
        if (! Schema::hasTable('vehicle_pass_tw_apply_approval')) {
            return false;
        }

        return DB::table('vehicle_pass_tw_apply_approval')
            ->where('vehicle_TW_pk', $vehiclePass->vehicle_tw_pk)
            ->where(function ($q) {
                $q->whereIn('status', [1, 2, 3])
                    ->orWhereIn('veh_recommend_status', [1, 2, 3]);
            })
            ->exists();
    }

    /**
     * Applicant may edit/delete only while pending and before any approval step is recorded.
     */
    private function applicantCanModifyVehiclePass(VehiclePassTWApply $vehiclePass): bool
    {
        return (int) $vehiclePass->vech_card_status === 1 && ! $this->vehiclePassHasApproverAction($vehiclePass);
    }

    /**
     * Generate next global vehicle_req_id.
     *
     * Requirement: vehicle_pass_tw_apply aur vehicle_pass_fw_apply
     * dono tables me vehicle_req_id UNIQUE hona chahiye aur
     * last row ke vehicle_req_id se +1 hokr next request banega.
     */
    /**
     * Normalize plate for duplicate detection (case, spaces, hyphens ignored).
     */
    private function normalizeVehicleNumberForDuplicateCheck(string $vehicleNo): string
    {
        return strtoupper(preg_replace('/[\s\-]+/', '', trim($vehicleNo)));
    }

    /**
     * Employee master pk / pk_old values that may appear in veh_created_by for the logged-in user.
     *
     * @return list<int>
     */
    private function vehiclePassDuplicateCheckCreatedByKeys(int $employeePk): array
    {
        $keys = [$employeePk];
        $em = EmployeeMaster::query()
            ->where('pk', $employeePk)
            ->orWhere('pk_old', $employeePk)
            ->first(['pk', 'pk_old']);
        if ($em) {
            $keys[] = (int) $em->pk;
            if (! empty($em->pk_old)) {
                $keys[] = (int) $em->pk_old;
            }
        }

        return array_values(array_unique(array_filter($keys)));
    }

    /**
     * True when this submitter already has another TW or FW pass for the same vehicle number
     * in Pending (1) or Approved (2). Rejected (3) does not block a new request.
     */
    private function applicantHasBlockingDuplicateVehicleNumber(int $employeePk, string $vehicleNo, ?string $excludeVehicleTwPk = null): bool
    {
        $createdByKeys = $this->vehiclePassDuplicateCheckCreatedByKeys($employeePk);
        $norm = $this->normalizeVehicleNumberForDuplicateCheck($vehicleNo);

        $twBlocks = VehiclePassTWApply::query()
            ->whereIn('veh_created_by', $createdByKeys)
            ->whereIn('vech_card_status', [1, 2])
            ->when($excludeVehicleTwPk, fn ($q) => $q->where('vehicle_tw_pk', '!=', $excludeVehicleTwPk))
            ->pluck('vehicle_no')
            ->contains(fn ($stored) => $this->normalizeVehicleNumberForDuplicateCheck((string) $stored) === $norm);

        if ($twBlocks) {
            return true;
        }

        if (! Schema::hasTable('vehicle_pass_fw_apply')) {
            return false;
        }

        return VehiclePassFWApply::query()
            ->whereIn('veh_created_by', $createdByKeys)
            ->whereIn('vech_card_status', [1, 2])
            ->pluck('vehicle_no')
            ->contains(fn ($stored) => $this->normalizeVehicleNumberForDuplicateCheck((string) $stored) === $norm);
    }

    private function generateVehicleReqId($vehicleTypePk)
    {
        // Get max vehicle_req_id from two-wheeler table
        $maxTw = (int) DB::table('vehicle_pass_tw_apply')->max('vehicle_req_id');

        // Get max vehicle_req_id from four-wheeler table (if exists)
        $maxFw = 0;
        if (DB::getSchemaBuilder()->hasTable('vehicle_pass_fw_apply')) {
            $maxFw = (int) DB::table('vehicle_pass_fw_apply')->max('vehicle_req_id');
        }

        $currentMax = max($maxTw, $maxFw);

        // Start from 1 if no records yet
        return $currentMax > 0 ? $currentMax + 1 : 1;
    }
}
