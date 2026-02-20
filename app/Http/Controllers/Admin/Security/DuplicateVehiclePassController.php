<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Models\EmployeeMaster;
use App\Models\SecVehicleType;
use App\Models\VehiclePassDuplicateApplyTwfw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DuplicateVehiclePassController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $userOldPk = EmployeeMaster::where('pk', $user->user_id)->first();
        $employeePk = $user->user_id ?? null;
        $pkOld = $userOldPk->pk_old ?? null;

        $query = VehiclePassDuplicateApplyTwfw::with(['vehicleType', 'employee'])
            ->where('veh_created_by', $employeePk)
            ->orWhere('veh_created_by', $pkOld)
            ->orderBy('created_date', 'desc');

        if ($request->filled('search')) {
            $term = trim($request->search);
            $query->where(function ($q) use ($term) {
                $q->where('vehicle_no', 'like', "%{$term}%")
                    ->orWhere('vehicle_primary_pk', 'like', "%{$term}%")
                    ->orWhere('employee_id_card', 'like', "%{$term}%");
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

    /**
     * Case 7 - Vehicle Pass Request (Duplicate): INSERT into vehicle_pass_duplicate_apply_TWFW.
     * Maps: vehicle_pass_no -> vehicle_primary_pk, reason_for_duplicate -> card_reason.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_number' => ['required', 'string', 'max:50'],
            'vehicle_pass_no' => ['required', 'string', 'max:50'],
            'id_card_number' => ['nullable', 'string', 'max:100'],
            'emp_master_pk' => ['required', 'exists:employee_master,pk'],
            'vehicle_type' => ['required', 'exists:sec_vehicle_type,pk'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason_for_duplicate' => ['required', 'string', 'max:100'],
            'doc_upload' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);

        $user = Auth::user();
        $createdBy = $user->user_id ?? null;

        $emp = EmployeeMaster::find($validated['emp_master_pk']);
        $idCardNumber = $validated['id_card_number'] ?: ($emp->emp_id ?? '');

        $docPath = null;
        if ($request->hasFile('doc_upload')) {
            $docPath = $request->file('doc_upload')->store('duplicate_vehicle_pass_docs', 'public');
        }

        // Generate vehicle_tw_pk: DUP + 3-digit padded number (per SQL structure)
        $maxNum = VehiclePassDuplicateApplyTwfw::where('vehicle_tw_pk', 'like', 'DUP%')
            ->get()
            ->map(fn ($r) => (int) preg_replace('/^DUP0*/', '', $r->vehicle_tw_pk))
            ->filter()
            ->max() ?? 0;
        $vehicleTwPk = 'DUP' . str_pad($maxNum + 1, 3, '0', STR_PAD_LEFT);

        $cardReason = VehiclePassDuplicateApplyTwfw::mapReasonToCardReason($validated['reason_for_duplicate']);

        VehiclePassDuplicateApplyTwfw::create([
            'vehicle_tw_pk' => $vehicleTwPk,
            'vehicle_no' => $validated['vehicle_number'],
            'vehicle_primary_pk' => $validated['vehicle_pass_no'],
            'employee_id_card' => $idCardNumber ?: '',
            'emp_master_pk' => $validated['emp_master_pk'],
            'vehicle_type' => $validated['vehicle_type'],
            'card_reason' => $cardReason,
            'doc_upload' => $docPath,
            'veh_card_valid_from' => $validated['start_date'],
            'vech_card_valid_to' => $validated['end_date'],
            'vech_card_status' => VehiclePassDuplicateApplyTwfw::STATUS_PENDING,
            'veh_created_by' => $createdBy,
            'created_date' => now(),
            'vehicle_card_reapply' => 0,
        ]);

        return redirect()->route('admin.security.duplicate_vehicle_pass.index')
            ->with('success', 'Duplicate / Extended Vehicle Pass request submitted successfully.');
    }

    public function show($id)
    {
        $vehicleTwPk = $this->decryptPk($id);
        $req = VehiclePassDuplicateApplyTwfw::with(['vehicleType', 'employee'])->findOrFail($vehicleTwPk);

        return view('admin.security.duplicate_vehicle_pass.show', compact('req'));
    }

    public function edit($id)
    {
        $vehicleTwPk = $this->decryptPk($id);
        $req = VehiclePassDuplicateApplyTwfw::with(['vehicleType', 'employee'])->findOrFail($vehicleTwPk);

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
        $vehicleTwPk = $this->decryptPk($id);
        $req = VehiclePassDuplicateApplyTwfw::findOrFail($vehicleTwPk);

        if ((int) $req->vech_card_status !== VehiclePassDuplicateApplyTwfw::STATUS_PENDING) {
            return redirect()->route('admin.security.duplicate_vehicle_pass.index')
                ->with('error', 'Cannot edit approved/rejected application.');
        }

        $validated = $request->validate([
            'vehicle_number' => ['required', 'string', 'max:50'],
            'vehicle_pass_no' => ['required', 'string', 'max:50'],
            'id_card_number' => ['nullable', 'string', 'max:100'],
            'emp_master_pk' => ['required', 'exists:employee_master,pk'],
            'vehicle_type' => ['required', 'exists:sec_vehicle_type,pk'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason_for_duplicate' => ['required', 'string', 'max:100'],
            'doc_upload' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);

        $emp = EmployeeMaster::find($validated['emp_master_pk']);
        $idCardNumber = $validated['id_card_number'] ?: ($emp->emp_id ?? '');

        $docPath = $req->doc_upload;
        if ($request->hasFile('doc_upload')) {
            if ($req->doc_upload) {
                Storage::disk('public')->delete($req->doc_upload);
            }
            $docPath = $request->file('doc_upload')->store('duplicate_vehicle_pass_docs', 'public');
        }

        $cardReason = VehiclePassDuplicateApplyTwfw::mapReasonToCardReason($validated['reason_for_duplicate']);

        $req->update([
            'vehicle_no' => $validated['vehicle_number'],
            'vehicle_primary_pk' => $validated['vehicle_pass_no'],
            'employee_id_card' => $idCardNumber ?: '',
            'emp_master_pk' => $validated['emp_master_pk'],
            'vehicle_type' => $validated['vehicle_type'],
            'card_reason' => $cardReason,
            'doc_upload' => $docPath,
            'veh_card_valid_from' => $validated['start_date'],
            'vech_card_valid_to' => $validated['end_date'],
        ]);

        return redirect()->route('admin.security.duplicate_vehicle_pass.show', encrypt($req->vehicle_tw_pk))
            ->with('success', 'Request updated successfully.');
    }

    public function destroy($id)
    {
        $vehicleTwPk = $this->decryptPk($id);
        $req = VehiclePassDuplicateApplyTwfw::findOrFail($vehicleTwPk);

        if ((int) $req->vech_card_status !== VehiclePassDuplicateApplyTwfw::STATUS_PENDING) {
            return redirect()->route('admin.security.duplicate_vehicle_pass.index')
                ->with('error', 'Cannot delete approved/rejected application.');
        }

        if ($req->doc_upload) {
            Storage::disk('public')->delete($req->doc_upload);
        }

        $req->delete();

        return redirect()->route('admin.security.duplicate_vehicle_pass.index')
            ->with('success', 'Request deleted successfully.');
    }

    private function decryptPk(string $id): string
    {
        try {
            return decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }
    }
}
