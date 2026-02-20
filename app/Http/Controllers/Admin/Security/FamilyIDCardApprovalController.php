<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Models\SecurityFamilyIdApply;
use App\Models\SecurityFamilyIdApplyApproval;
use App\Support\IdCardSecurityMapper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Family ID Card Approval - List pending family ID card requests and approve/reject.
 * Uses security_family_id_apply (id_status: 1=Pending, 2=Approved, 3=Rejected)
 * and security_family_id_apply_approval for approval history.
 */
class FamilyIDCardApprovalController extends Controller
{
    public function index()
    {
        $pendingApplications = SecurityFamilyIdApply::with('approvals')
            ->where('id_status', 1)
            ->orderBy('created_date', 'desc')
            ->paginate(10);

        return view('admin.security.family_idcard_approval.index', compact('pendingApplications'));
    }

    public function show($id)
    {
        try {
            $fmlIdApply = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $application = SecurityFamilyIdApply::with('approvals')
            ->where('fml_id_apply', $fmlIdApply)
            ->firstOrFail();

        $request = IdCardSecurityMapper::toFamilyRequestDto($application);

        return view('admin.security.family_idcard_approval.show', [
            'application' => $application,
            'request' => $request,
        ]);
    }

    public function approve(Request $request, $id)
    {
        try {
            $fmlIdApply = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $validated = $request->validate([
            'approval_remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $application = SecurityFamilyIdApply::where('fml_id_apply', $fmlIdApply)->firstOrFail();

        if ((int) $application->id_status !== 1) {
            return redirect()->back()->with('error', 'Application already processed');
        }

        $user = Auth::user();
        $employeePk = $user->user_id ?? null;

        $application->id_status = 2;
        $application->save();

        SecurityFamilyIdApplyApproval::create([
            'security_fm_id_apply_pk' => $fmlIdApply,
            'status' => 1,
            'approval_remarks' => $validated['approval_remarks'] ?? null,
            'created_by' => $employeePk,
            'created_date' => now(),
        ]);

        return redirect()->route('admin.security.family_idcard_approval.index')
            ->with('success', 'Family ID Card approved successfully');
    }

    public function reject(Request $request, $id)
    {
        try {
            $fmlIdApply = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $validated = $request->validate([
            'approval_remarks' => ['required', 'string', 'max:500'],
        ]);

        $application = SecurityFamilyIdApply::where('fml_id_apply', $fmlIdApply)->firstOrFail();

        if ((int) $application->id_status !== 1) {
            return redirect()->back()->with('error', 'Application already processed');
        }

        $user = Auth::user();
        $employeePk = $user->user_id ?? null;

        $application->id_status = 3;
        $application->save();

        SecurityFamilyIdApplyApproval::create([
            'security_fm_id_apply_pk' => $fmlIdApply,
            'status' => 3,
            'approval_remarks' => $validated['approval_remarks'],
            'created_by' => $employeePk,
            'created_date' => now(),
        ]);

        return redirect()->route('admin.security.family_idcard_approval.index')
            ->with('success', 'Family ID Card rejected');
    }

    public function all()
    {
        $applications = SecurityFamilyIdApply::with('approvals')
            ->orderBy('created_date', 'desc')
            ->paginate(15);

        return view('admin.security.family_idcard_approval.all', compact('applications'));
    }
}
