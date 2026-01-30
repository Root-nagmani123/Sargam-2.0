<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Models\VehiclePassTWApply;
use App\Models\VehiclePassTWApplyApproval;
use App\Models\SecVehicleType;
use App\Models\EmployeeMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VehiclePassController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Get employee pk from user credentials
        $employeePk = $user->user_id ?? null;

        $vehiclePasses = VehiclePassTWApply::with(['vehicleType', 'employee', 'approval'])
            ->where('veh_created_by', $employeePk)
            ->orderBy('created_date', 'desc')
            ->paginate(10);

        return view('admin.security.vehicle_pass.index', compact('vehiclePasses'));
    }

    public function create()
    {
        $vehicleTypes = SecVehicleType::active()->get();
        $employees = EmployeeMaster::with(['designation', 'department'])->where('status', 1)->get();
        
        return view('admin.security.vehicle_pass.create', compact('vehicleTypes', 'employees'));
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
            'vehicle_no' => ['required', 'string', 'max:50'],
            'doc_upload' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'veh_card_valid_from' => ['required', 'date'],
            'vech_card_valid_to' => ['required', 'date', 'after_or_equal:veh_card_valid_from'],
        ]);

        $user = Auth::user();
        $employeePk = $user->user_id ?? null;

        $applicantType = $validated['applicant_type'];
        $applicantName = $validated['applicant_name'] ?? null;
        $designation = $validated['designation'] ?? null;
        $department = $validated['department'] ?? null;
        $employeeIdCard = $validated['employee_id_card'] ?? null;
        $empMasterPk = $validated['emp_master_pk'] ?? null;

        if ($applicantType === 'employee' && $empMasterPk) {
            $emp = EmployeeMaster::with(['designation', 'department'])->find($empMasterPk);
            if ($emp) {
                $applicantName = $applicantName ?: trim($emp->first_name . ' ' . ($emp->last_name ?? ''));
                $designation = $designation ?: ($emp->designation->designation_name ?? null);
                $department = $department ?: ($emp->department->department_name ?? null);
                $employeeIdCard = $employeeIdCard ?: ($emp->emp_id ?? null);
            }
        }

        $govVeh = $applicantType === 'government_vehicle' ? 1 : 0;

        // Handle file upload
        $docPath = null;
        if ($request->hasFile('doc_upload')) {
            $docPath = $request->file('doc_upload')->store('vehicle_documents', 'public');
        }

        // Generate vehicle request ID
        $vehicleReqId = $this->generateVehicleReqId($validated['vehicle_type']);

        $vehiclePass = new VehiclePassTWApply();
        $vehiclePass->applicant_type = $applicantType;
        $vehiclePass->applicant_name = $applicantName;
        $vehiclePass->designation = $designation;
        $vehiclePass->department = $department;
        $vehiclePass->employee_id_card = $employeeIdCard;
        $vehiclePass->emp_master_pk = $empMasterPk;
        $vehiclePass->vehicle_type = $validated['vehicle_type'];
        $vehiclePass->vehicle_no = $validated['vehicle_no'];
        $vehiclePass->doc_upload = $docPath;
        $vehiclePass->veh_card_valid_from = $validated['veh_card_valid_from'];
        $vehiclePass->vech_card_valid_to = $validated['vech_card_valid_to'];
        $vehiclePass->gov_veh = $govVeh;
        $vehiclePass->vech_card_status = 1; // Pending
        $vehiclePass->veh_card_forward_status = 0; // Not forwarded
        $vehiclePass->vehicle_req_id = $vehicleReqId;
        $vehiclePass->veh_created_by = $employeePk;
        $vehiclePass->created_date = now();
        $vehiclePass->save();

        // Create initial approval record
        $approval = new VehiclePassTWApplyApproval();
        $approval->vehicle_TW_pk = $vehiclePass->vehicle_tw_pk;
        $approval->status = 0; // Pending
        $approval->created_date = now();
        $approval->save();

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
            'vehicle_no' => ['required', 'string', 'max:50'],
            'doc_upload' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'veh_card_valid_from' => ['required', 'date'],
            'vech_card_valid_to' => ['required', 'date', 'after_or_equal:veh_card_valid_from'],
        ]);

        $applicantType = $validated['applicant_type'];
        $applicantName = $validated['applicant_name'] ?? null;
        $designation = $validated['designation'] ?? null;
        $department = $validated['department'] ?? null;
        $employeeIdCard = $validated['employee_id_card'] ?? null;
        $empMasterPk = $validated['emp_master_pk'] ?? null;

        if ($applicantType === 'employee' && $empMasterPk) {
            $emp = EmployeeMaster::with(['designation', 'department'])->find($empMasterPk);
            if ($emp) {
                $applicantName = $applicantName ?: trim($emp->first_name . ' ' . ($emp->last_name ?? ''));
                $designation = $designation ?: ($emp->designation->designation_name ?? null);
                $department = $department ?: ($emp->department->department_name ?? null);
                $employeeIdCard = $employeeIdCard ?: ($emp->emp_id ?? null);
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

        $vehiclePass->applicant_type = $applicantType;
        $vehiclePass->applicant_name = $applicantName;
        $vehiclePass->designation = $designation;
        $vehiclePass->department = $department;
        $vehiclePass->employee_id_card = $employeeIdCard;
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

    private function generateVehicleReqId($vehicleTypePk)
    {
        $config = \App\Models\SecVehiclePassConfig::where('sec_vehicle_type_pk', $vehicleTypePk)->first();
        $counter = $config ? $config->start_counter : 1;

        // Get count of existing applications for this vehicle type
        $existingCount = VehiclePassTWApply::where('vehicle_type', $vehicleTypePk)->count();
        $nextNumber = $counter + $existingCount;

        return 'VP' . date('Ymd') . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
