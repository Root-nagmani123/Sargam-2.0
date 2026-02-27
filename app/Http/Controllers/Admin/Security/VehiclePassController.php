<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Exports\VehiclePassExport;
use App\Models\VehiclePassTWApply;
use App\Models\SecVehicleType;
use App\Models\EmployeeMaster;
use App\Models\SecurityParmIdApply;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class VehiclePassController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $user_old_pk = EmployeeMaster::where('pk', $user->user_id)->first();
       
        $employeePk = $user->user_id ?? null;
        $pk_old = $user_old_pk->pk_old ?? null;

        $baseQuery = fn () => VehiclePassTWApply::with(['vehicleType', 'employee', 'approval'])
            ->where('veh_created_by', $employeePk)
            ->orWhere('veh_created_by', $pk_old)
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
        $employees = EmployeeMaster::with(['designation', 'department'])->where('status', 1)->get();

        $currentUserEmployee = null;
        $user = Auth::user();
        $employeePk = $user->user_id ?? null;
        if ($employeePk) {
            $emp = $employees->firstWhere('pk', $employeePk);
            if ($emp) {
                $currentUserEmployee = (object) [
                    'pk' => $emp->pk,
                    'name' => trim($emp->first_name . ' ' . ($emp->last_name ?? '')),
                    'designation' => $emp->designation->designation_name ?? '',
                    'department' => $emp->department->department_name ?? '',
                    'emp_id' => $emp->emp_id ?? '',
                ];
            }
        }

        return view('admin.security.vehicle_pass.create', compact('vehicleTypes', 'employees', 'currentUserEmployee'));
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
            'veh_card_valid_from' => ['required', 'date'],
            'vech_card_valid_to' => ['required', 'date', 'after_or_equal:veh_card_valid_from'],
        ], [
            'vehicle_no.regex' => 'Vehicle number must be 3–20 characters (letters, numbers, spaces or hyphens only).',
        ]);

        $user = Auth::user();
        $employeePk = $user->user_id ?? $user->pk ?? null;

        $applicantType = $validated['applicant_type'];
        $applicantName = $validated['applicant_name'] ?? null;
        $designation = $validated['designation'] ?? null;
        $department = $validated['department'] ?? null;
        $employeeIdCard = $validated['employee_id_card'] ?? null;
        $empMasterPk = $validated['emp_master_pk'] ?? null;

        // Employee / Government Vehicle: always use logged-in user's employee (no selection from request). Others: no employee.
        if (in_array($applicantType, ['employee', 'government_vehicle'])) {
            $empMasterPk = $employeePk;
        } elseif ($applicantType === 'others') {
            $empMasterPk = null;
        }

        if (in_array($applicantType, ['employee', 'government_vehicle']) && $empMasterPk) {
            $emp = EmployeeMaster::with(['designation', 'department'])->find($empMasterPk);
            if ($emp) {
                $applicantName = $applicantName ?: trim($emp->first_name . ' ' . ($emp->last_name ?? ''));
                $designation = $designation ?: ($emp->designation->designation_name ?? null);
                $department = $department ?: ($emp->department->department_name ?? null);
                $employeeIdCard = $employeeIdCard ?: ($emp->emp_id ?? null);
            }

            // Employee ID card must be valid (approved, not expired) for employee/government_vehicle applicants
            $validIdCard = DB::table('security_parm_id_apply')
                ->where('employee_master_pk', $empMasterPk)
                ->where('id_status', SecurityParmIdApply::ID_STATUS_APPROVED)
                ->where(function ($q) {
                    $q->whereNull('card_valid_to')->orWhere('card_valid_to', '>=', now()->format('Y-m-d'));
                })
                ->exists();

            if (!$validIdCard) {
                throw ValidationException::withMessages([
                    'applicant_type' => ['A valid (approved and not expired) Employee ID Card is required to apply for Vehicle Pass.'],
                ]);
            }
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

        return view('admin.security.vehicle_pass.show', compact('vehiclePass'));
    }

    public function edit($id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $vehiclePass = VehiclePassTWApply::with(['employee.designation', 'employee.department'])->findOrFail($pk);
        
        // Only allow editing if status is pending
        if ($vehiclePass->vech_card_status != 1) {
            return redirect()->route('admin.security.vehicle_pass.index')->with('error', 'Cannot edit approved/rejected application');
        }

        $vehicleTypes = SecVehicleType::active()->get();

        return view('admin.security.vehicle_pass.edit', compact('vehiclePass', 'vehicleTypes'));
    }

    public function update(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $vehiclePass = VehiclePassTWApply::findOrFail($pk);

        // Only allow editing if status is pending
        if ($vehiclePass->vech_card_status != 1) {
            return redirect()->route('admin.security.vehicle_pass.index')->with('error', 'Cannot edit approved/rejected application');
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
            'veh_card_valid_from' => ['required', 'date'],
            'vech_card_valid_to' => ['required', 'date', 'after_or_equal:veh_card_valid_from'],
        ], [
            'vehicle_no.regex' => 'Vehicle number must be 3–20 characters (letters, numbers, spaces or hyphens only).',
        ]);

        $user = Auth::user();
        $employeePk = $user->user_id ?? null;

        $applicantType = $validated['applicant_type'];
        $applicantName = $validated['applicant_name'] ?? null;
        $designation = $validated['designation'] ?? null;
        $department = $validated['department'] ?? null;
        $employeeIdCard = $validated['employee_id_card'] ?? null;
        $empMasterPk = $validated['emp_master_pk'] ?? null;

        // Employee / Government Vehicle: always use logged-in user's employee (no selection from request). Others: no employee.
        if (in_array($applicantType, ['employee', 'government_vehicle'])) {
            $empMasterPk = $employeePk;
        } elseif ($applicantType === 'others') {
            $empMasterPk = null;
        }

        if (in_array($applicantType, ['employee', 'government_vehicle']) && $empMasterPk) {
            $emp = EmployeeMaster::with(['designation', 'department'])->find($empMasterPk);
            if ($emp) {
                $applicantName = $applicantName ?: trim($emp->first_name . ' ' . ($emp->last_name ?? ''));
                $designation = $designation ?: ($emp->designation->designation_name ?? null);
                $department = $department ?: ($emp->department->department_name ?? null);
                $employeeIdCard = $employeeIdCard ?: ($emp->emp_id ?? null);
            }

            // Employee ID card must be valid (approved, not expired) for employee/government_vehicle applicants
            $validIdCard = DB::table('security_parm_id_apply')
                ->where('employee_master_pk', $empMasterPk)
                ->where('id_status', SecurityParmIdApply::ID_STATUS_APPROVED)
                ->where(function ($q) {
                    $q->whereNull('card_valid_to')->orWhere('card_valid_to', '>=', now()->format('Y-m-d'));
                })
                ->exists();

            if (!$validIdCard) {
                throw ValidationException::withMessages([
                    'applicant_type' => ['A valid (approved and not expired) Employee ID Card is required to apply for Vehicle Pass.'],
                ]);
            }
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

        // Only allow deleting if status is pending
        if ($vehiclePass->vech_card_status != 1) {
            return redirect()->route('admin.security.vehicle_pass.index')->with('error', 'Cannot delete approved/rejected application');
        }

        // Delete uploaded document
        if ($vehiclePass->doc_upload) {
            Storage::disk('public')->delete($vehiclePass->doc_upload);
        }

        $vehiclePass->delete();

        return redirect()->route('admin.security.vehicle_pass.index')->with('success', 'Vehicle Pass application deleted successfully');
    }

    /** Returns numeric vehicle_req_id (int) per SQL column vehicle_req_id int(5). */
    private function generateVehicleReqId($vehicleTypePk)
    {
        $config = \App\Models\SecVehiclePassConfig::where('sec_vehicle_type_pk', $vehicleTypePk)->first();
        $counter = $config ? (int) $config->start_counter : 1;
        $existingCount = VehiclePassTWApply::where('vehicle_type', $vehicleTypePk)->count();
        return $counter + $existingCount;
    }
}
