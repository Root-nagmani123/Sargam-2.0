<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Models\VehiclePassTWApply;
use App\Models\VehiclePassTWApplyApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VehiclePassApprovalController extends Controller
{
    public function index()
    {
        $pendingApplications = VehiclePassTWApply::with(['vehicleType', 'employee', 'createdBy', 'approval'])
            ->where('vech_card_status', 1) // Pending status
            ->orderBy('created_date', 'desc')
            ->paginate(10);

        return view('admin.security.vehicle_pass_approval.index', compact('pendingApplications'));
    }

    public function show($id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $application = VehiclePassTWApply::with(['vehicleType', 'employee', 'createdBy', 'approvals.approvedBy'])
            ->findOrFail($pk);

        return view('admin.security.vehicle_pass_approval.show', compact('application'));
    }

    public function approve(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $validated = $request->validate([
            'veh_approval_remarks' => ['nullable', 'string'],
            'forward_status' => ['required', 'in:1,2'], // 1=Forwarded, 2=Card Ready
        ]);

        $application = VehiclePassTWApply::findOrFail($pk);

        // Only allow approval if status is pending
        if ($application->vech_card_status != 1) {
            return redirect()->back()->with('error', 'Application already processed');
        }

        $user = Auth::user();
        $employeePk = $user->user_id ?? null;

        // Update application status
        $application->vech_card_status = 2; // Approved
        $application->veh_card_forward_status = $validated['forward_status'];
        $application->save();

        // Create/Update approval record
        $approval = VehiclePassTWApplyApproval::where('vehicle_TW_pk', $application->vehicle_tw_pk)
            ->latest('created_date')
            ->first();

        if ($approval) {
            $approval->status = 2; // Approved
            $approval->veh_recommend_status = 2; // Approved
            $approval->veh_approval_remarks = $validated['veh_approval_remarks'];
            $approval->veh_approved_by = $employeePk;
            $approval->modified_date = now();
            $approval->save();
        } else {
            $approval = new VehiclePassTWApplyApproval();
            $approval->vehicle_TW_pk = $application->vehicle_tw_pk;
            $approval->status = 2;
            $approval->veh_recommend_status = 2;
            $approval->veh_approval_remarks = $validated['veh_approval_remarks'];
            $approval->veh_approved_by = $employeePk;
            $approval->created_date = now();
            $approval->save();
        }

        return redirect()->route('admin.security.vehicle_pass_approval.index')->with('success', 'Application approved successfully');
    }

    public function reject(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $validated = $request->validate([
            'veh_approval_remarks' => ['required', 'string'],
        ]);

        $application = VehiclePassTWApply::findOrFail($pk);

        // Only allow rejection if status is pending
        if ($application->vech_card_status != 1) {
            return redirect()->back()->with('error', 'Application already processed');
        }

        $user = Auth::user();
        $employeePk = $user->user_id ?? null;

        // Update application status
        $application->vech_card_status = 3; // Rejected
        $application->save();

        // Create/Update approval record
        $approval = VehiclePassTWApplyApproval::where('vehicle_TW_pk', $application->vehicle_tw_pk)
            ->latest('created_date')
            ->first();

        if ($approval) {
            $approval->status = 3; // Rejected
            $approval->veh_recommend_status = 3; // Rejected
            $approval->veh_approval_remarks = $validated['veh_approval_remarks'];
            $approval->veh_approved_by = $employeePk;
            $approval->modified_date = now();
            $approval->save();
        } else {
            $approval = new VehiclePassTWApplyApproval();
            $approval->vehicle_TW_pk = $application->vehicle_tw_pk;
            $approval->status = 3;
            $approval->veh_recommend_status = 3;
            $approval->veh_approval_remarks = $validated['veh_approval_remarks'];
            $approval->veh_approved_by = $employeePk;
            $approval->created_date = now();
            $approval->save();
        }

        return redirect()->route('admin.security.vehicle_pass_approval.index')->with('success', 'Application rejected');
    }

    public function allApplications()
    {
        $applications = VehiclePassTWApply::with(['vehicleType', 'employee', 'createdBy', 'approval'])
            ->orderBy('created_date', 'desc')
            ->paginate(15);

        return view('admin.security.vehicle_pass_approval.all', compact('applications'));
    }
}
