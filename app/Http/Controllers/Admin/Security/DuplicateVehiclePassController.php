<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Models\DuplicateVehiclePassRequest;
use App\Models\EmployeeMaster;
use App\Models\SecVehicleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DuplicateVehiclePassController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $createdBy = $user->user_id ?? null;

        $query = DuplicateVehiclePassRequest::with(['vehicleType', 'employee'])
            ->when($createdBy, fn ($q) => $q->where('created_by', $createdBy))
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $term = trim($request->search);
            $query->where(function ($q) use ($term) {
                $q->where('vehicle_number', 'like', "%{$term}%")
                    ->orWhere('vehicle_pass_no', 'like', "%{$term}%")
                    ->orWhere('employee_name', 'like', "%{$term}%")
                    ->orWhere('id_card_number', 'like', "%{$term}%");
            });
        }

        $perPage = (int) $request->get('per_page', 10);
        $requests = $query->paginate(min(max($perPage, 5), 100))->withQueryString();

        return view('admin.security.duplicate_vehicle_pass.index', compact('requests'));
    }

    public function create()
    {
        $vehicleTypes = SecVehicleType::active()->get();
        $employees = EmployeeMaster::with(['designation', 'department'])
            ->where('status', 1)
            ->orderBy('first_name')
            ->get()
            ->map(function ($e) {
                return (object) [
                    'pk' => $e->pk,
                    'name' => trim($e->first_name . ' ' . ($e->last_name ?? '')),
                    'designation' => $e->designation->designation_name ?? '',
                    'department' => $e->department->department_name ?? '',
                    'emp_id' => $e->emp_id ?? '',
                ];
            });

        return view('admin.security.duplicate_vehicle_pass.create', compact('vehicleTypes', 'employees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_number' => ['required', 'string', 'max:50'],
            'vehicle_pass_no' => ['required', 'string', 'max:50'],
            'id_card_number' => ['nullable', 'string', 'max:100'],
            'emp_master_pk' => ['required', 'exists:employee_master,pk'],
            'designation' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'vehicle_type' => ['required', 'exists:sec_vehicle_type,pk'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason_for_duplicate' => ['required', 'string', 'max:100'],
            'doc_upload' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);

        $user = Auth::user();
        $createdBy = $user->user_id ?? null;

        $emp = EmployeeMaster::with(['designation', 'department'])->find($validated['emp_master_pk']);
        $employeeName = $emp ? trim($emp->first_name . ' ' . ($emp->last_name ?? '')) : '';
        $designation = $validated['designation'] ?: ($emp->designation->designation_name ?? null);
        $department = $validated['department'] ?: ($emp->department->department_name ?? null);
        $idCardNumber = $validated['id_card_number'] ?: ($emp->emp_id ?? null);

        $docPath = null;
        if ($request->hasFile('doc_upload')) {
            $docPath = $request->file('doc_upload')->store('duplicate_vehicle_pass_docs', 'public');
        }

        DuplicateVehiclePassRequest::create([
            'vehicle_number' => $validated['vehicle_number'],
            'vehicle_pass_no' => $validated['vehicle_pass_no'],
            'id_card_number' => $idCardNumber,
            'emp_master_pk' => $validated['emp_master_pk'],
            'employee_name' => $employeeName,
            'designation' => $designation,
            'department' => $department,
            'vehicle_type' => $validated['vehicle_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason_for_duplicate' => $validated['reason_for_duplicate'],
            'doc_upload' => $docPath,
            'status' => 'Pending',
            'created_by' => $createdBy,
        ]);

        return redirect()->route('admin.security.duplicate_vehicle_pass.index')
            ->with('success', 'Duplicate / Extended Vehicle Pass request submitted successfully.');
    }

    public function show($id)
    {
        $req = DuplicateVehiclePassRequest::with(['vehicleType', 'employee'])->findOrFail($id);
        return view('admin.security.duplicate_vehicle_pass.show', compact('req'));
    }

    public function edit($id)
    {
        $req = DuplicateVehiclePassRequest::with(['vehicleType', 'employee'])->findOrFail($id);
        $vehicleTypes = SecVehicleType::active()->get();
        $employees = EmployeeMaster::with(['designation', 'department'])
            ->where('status', 1)
            ->orderBy('first_name')
            ->get()
            ->map(function ($e) {
                return (object) [
                    'pk' => $e->pk,
                    'name' => trim($e->first_name . ' ' . ($e->last_name ?? '')),
                    'designation' => $e->designation->designation_name ?? '',
                    'department' => $e->department->department_name ?? '',
                    'emp_id' => $e->emp_id ?? '',
                ];
            });

        return view('admin.security.duplicate_vehicle_pass.edit', compact('req', 'vehicleTypes', 'employees'));
    }

    public function update(Request $request, $id)
    {
        $req = DuplicateVehiclePassRequest::findOrFail($id);

        $validated = $request->validate([
            'vehicle_number' => ['required', 'string', 'max:50'],
            'vehicle_pass_no' => ['required', 'string', 'max:50'],
            'id_card_number' => ['nullable', 'string', 'max:100'],
            'emp_master_pk' => ['required', 'exists:employee_master,pk'],
            'designation' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'vehicle_type' => ['required', 'exists:sec_vehicle_type,pk'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason_for_duplicate' => ['required', 'string', 'max:100'],
            'doc_upload' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'status' => ['nullable', 'in:Pending,Approved,Rejected,Issued'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $emp = EmployeeMaster::with(['designation', 'department'])->find($validated['emp_master_pk']);
        $employeeName = $emp ? trim($emp->first_name . ' ' . ($emp->last_name ?? '')) : '';
        $designation = $validated['designation'] ?: ($emp->designation->designation_name ?? null);
        $department = $validated['department'] ?: ($emp->department->department_name ?? null);
        $idCardNumber = $validated['id_card_number'] ?: ($emp->emp_id ?? null);

        $docPath = $req->doc_upload;
        if ($request->hasFile('doc_upload')) {
            if ($req->doc_upload) {
                Storage::disk('public')->delete($req->doc_upload);
            }
            $docPath = $request->file('doc_upload')->store('duplicate_vehicle_pass_docs', 'public');
        }

        $user = Auth::user();
        $req->update([
            'vehicle_number' => $validated['vehicle_number'],
            'vehicle_pass_no' => $validated['vehicle_pass_no'],
            'id_card_number' => $idCardNumber,
            'emp_master_pk' => $validated['emp_master_pk'],
            'employee_name' => $employeeName,
            'designation' => $designation,
            'department' => $department,
            'vehicle_type' => $validated['vehicle_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason_for_duplicate' => $validated['reason_for_duplicate'],
            'doc_upload' => $docPath,
            'status' => $validated['status'] ?? $req->status,
            'remarks' => $validated['remarks'] ?? $req->remarks,
            'updated_by' => $user->user_id ?? null,
        ]);

        return redirect()->route('admin.security.duplicate_vehicle_pass.index')
            ->with('success', 'Request updated successfully.');
    }

    public function destroy($id)
    {
        $req = DuplicateVehiclePassRequest::findOrFail($id);
        $req->delete();
        return redirect()->route('admin.security.duplicate_vehicle_pass.index')
            ->with('success', 'Request deleted successfully.');
    }
}
