<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Models\VehiclePassDuplicateApplyApprovalTwfw;
use App\Models\VehiclePassDuplicateApplyTwfw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Case 7 - Vehicle Pass Duplicate Approval.
 * At approval: INSERT into vehicle_pass_duplicate_apply_approval_TWFW.
 * FK column: vehicle_TW_pk (links to vehicle_pass_duplicate_apply_TWFW.vehicle_tw_pk)
 */
class DuplicateVehiclePassApprovalController extends Controller
{
    public function index()
    {
        $pendingApplications = VehiclePassDuplicateApplyTwfw::with(['vehicleType', 'employee', 'createdBy'])
            ->where('vech_card_status', VehiclePassDuplicateApplyTwfw::STATUS_PENDING)
            ->orderBy('created_date', 'desc')
            ->paginate(10);

        return view('admin.security.duplicate_vehicle_pass_approval.index', compact('pendingApplications'));
    }

    public function show($id)
    {
        try {
            $vehicleTwPk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $application = VehiclePassDuplicateApplyTwfw::with(['vehicleType', 'employee', 'createdBy', 'approvals.approvedBy'])
            ->findOrFail($vehicleTwPk);

        return view('admin.security.duplicate_vehicle_pass_approval.show', compact('application'));
    }

    public function approve(Request $request, $id)
    {
        try {
            $vehicleTwPk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $validated = $request->validate([
            'veh_approval_remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $application = VehiclePassDuplicateApplyTwfw::findOrFail($vehicleTwPk);

        if ((int) $application->vech_card_status !== VehiclePassDuplicateApplyTwfw::STATUS_PENDING) {
            return redirect()->back()->with('error', 'Application already processed');
        }

        $user = Auth::user();
        $employeePk = $user->user_id ?? null;

        $application->vech_card_status = VehiclePassDuplicateApplyTwfw::STATUS_APPROVED;
        $application->save();

        VehiclePassDuplicateApplyApprovalTwfw::create([
            'vehicle_TW_pk' => $vehicleTwPk,
            'status' => VehiclePassDuplicateApplyApprovalTwfw::STATUS_APPROVED,
            'veh_approval_remarks' => $validated['veh_approval_remarks'] ?? null,
            'created_by' => $employeePk,
            'created_date' => now(),
        ]);

        return redirect()->route('admin.security.duplicate_vehicle_pass_approval.index')
            ->with('success', 'Application approved successfully');
    }

    public function reject(Request $request, $id)
    {
        try {
            $vehicleTwPk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $validated = $request->validate([
            'veh_approval_remarks' => ['required', 'string', 'max:500'],
        ]);

        $application = VehiclePassDuplicateApplyTwfw::findOrFail($vehicleTwPk);

        if ((int) $application->vech_card_status !== VehiclePassDuplicateApplyTwfw::STATUS_PENDING) {
            return redirect()->back()->with('error', 'Application already processed');
        }

        $user = Auth::user();
        $employeePk = $user->user_id ?? null;

        $application->vech_card_status = VehiclePassDuplicateApplyTwfw::STATUS_REJECTED;
        $application->save();

        VehiclePassDuplicateApplyApprovalTwfw::create([
            'vehicle_TW_pk' => $vehicleTwPk,
            'status' => VehiclePassDuplicateApplyApprovalTwfw::STATUS_REJECTED,
            'veh_approval_remarks' => $validated['veh_approval_remarks'],
            'created_by' => $employeePk,
            'created_date' => now(),
        ]);

        return redirect()->route('admin.security.duplicate_vehicle_pass_approval.index')
            ->with('success', 'Application rejected');
    }

    public function all()
    {
        $applications = VehiclePassDuplicateApplyTwfw::with(['vehicleType', 'employee', 'createdBy', 'approvals'])
            ->orderBy('created_date', 'desc')
            ->paginate(15);

        return view('admin.security.duplicate_vehicle_pass_approval.all', compact('applications'));
    }
}
