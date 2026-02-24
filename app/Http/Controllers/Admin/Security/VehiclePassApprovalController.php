<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Models\VehiclePassTWApply;
use App\Models\VehiclePassTWApplyApproval;
use App\Models\VehiclePassDuplicateApplyTwfw;
use App\Models\VehiclePassDuplicateApplyApprovalTwfw;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VehiclePassApprovalController extends Controller
{
    /**
     * Consolidated index showing both regular and duplicate vehicle pass applications.
     * Prefix format:
     * - numeric = regular vehicle pass (VehiclePassTWApply)
     * - dup-<pk> = duplicate vehicle pass (VehiclePassDuplicateApplyTwfw)
     */
    public function index()
    {
        // Regular vehicle pass applications - pending
        $regularQuery = VehiclePassTWApply::with(['vehicleType', 'employee', 'createdBy'])
            ->where('vech_card_status', 1)
            ->orderBy('created_date', 'desc');

        // Duplicate vehicle pass applications - pending
        $duplicateQuery = VehiclePassDuplicateApplyTwfw::with(['vehicleType', 'employee', 'createdBy'])
            ->where('vech_card_status', 1)
            ->orderBy('created_date', 'desc');

        $regularRows = $regularQuery->get();
        $duplicateRows = $duplicateQuery->get();

        // Convert regular rows to DTOs
        $regularDtos = $regularRows->map(function ($r) {
            return (object) [
                'id' => $r->vehicle_tw_pk,
                'vehicle_number' => $r->vehicle_no ?? '--',
                'employee_id' => $r->employee_id_card ?? '--',
                'vehicle_type' => $r->vehicleType ? ($r->vehicleType->vehicle_type ?? '--') : '--',
                'status' => 'Pending',
                'created_date' => $r->created_date,
                'request_type' => 'regular',
            ];
        });

        // Convert duplicate rows to DTOs
        $duplicateDtos = $duplicateRows->map(function ($r) {
            return (object) [
                'id' => $r->vehicle_tw_pk,
                'vehicle_number' => $r->vehicle_no ?? '--',
                'employee_id' => $r->employee_id_card ?? '--',
                'vehicle_type' => $r->vehicleType ? ($r->vehicleType->vehicle_type ?? '--') : '--',
                'status' => 'Pending',
                'created_date' => $r->created_date,
                'request_type' => 'duplicate',
            ];
        });

        // Merge and sort by created_date
        $merged = $regularDtos->concat($duplicateDtos)->sortByDesc(function ($d) {
            return $d->created_date ? (\Carbon\Carbon::parse($d->created_date)->timestamp ?? 0) : 0;
        })->values();

        $perPage = 10;
        $page = (int) request()->get('page', 1);
        $pendingApplications = new LengthAwarePaginator(
            $merged->forPage($page, $perPage),
            $merged->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('admin.security.vehicle_pass_approval.index', compact('pendingApplications'));
    }

    public function show($id)
    {
        try {
            $decryptedId = decrypt($id);
        } catch (\Exception $e) {
            return redirect()->route('admin.security.vehicle_pass_approval.index')
                ->with('error', 'Invalid request. Please try again.');
        }

        $isDuplicate = false;
        $pk = $decryptedId;
        
        // Check if it's a duplicate (has dup- prefix after decryption)
        if (is_string($decryptedId) && strpos($decryptedId, 'dup-') === 0) {
            $isDuplicate = true;
            $pk = str_replace('dup-', '', $decryptedId);
        }

        $application = null;
        
        if ($isDuplicate) {
            $application = VehiclePassDuplicateApplyTwfw::find($pk);
            if ($application) {
                $application->request_type = 'duplicate';
                $application->encrypted_id = encrypt('dup-' . $application->vehicle_tw_pk);
            }
        } else {
            $application = VehiclePassTWApply::find($pk);
            if ($application) {
                $application->request_type = 'regular';
                $application->encrypted_id = encrypt($application->vehicle_tw_pk);
            }
        }

        if (!$application) {
            return redirect()->route('admin.security.vehicle_pass_approval.index')
                ->with('error', 'Application not found.');
        }

        // Load relationships with nested loads
        if ($isDuplicate) {
            $application->load([
                'vehicleType',
                'employee' => function($q) {
                    $q->with(['designation', 'department']);
                },
                'createdBy',
                'approvals'
            ]);
        } else {
            $application->load([
                'vehicleType',
                'employee' => function($q) {
                    $q->with(['designation', 'department']);
                },
                'createdBy',
                'approvals'
            ]);
        }

        return view('admin.security.vehicle_pass_approval.show', compact('application'));
    }

    public function approve(Request $request, $id)
    {
        try {
            $decryptedId = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $validated = $request->validate([
            'veh_approval_remarks' => ['nullable', 'string'],
            'forward_status' => ['nullable', 'in:1,2'], // 1=Forwarded, 2=Card Ready (optional for duplicate)
        ]);

        $user = Auth::user();
        $employeePk = $user->user_id ?? null;

        // Check if it's a duplicate (has dup- prefix after decryption)
        if (is_string($decryptedId) && str_starts_with($decryptedId, 'dup-')) {
            $duplicatePk = substr($decryptedId, 4); // Remove 'dup-' prefix
            $application = VehiclePassDuplicateApplyTwfw::findOrFail($duplicatePk);

            if ((int) $application->vech_card_status !== 1) {
                return redirect()->back()->with('error', 'Application already processed');
            }

            $application->vech_card_status = 2; // Approved
            $application->save();

            VehiclePassDuplicateApplyApprovalTwfw::create([
                'vehicle_TW_pk' => $duplicatePk,
                'status' => 2, // Approved
                'veh_approval_remarks' => $validated['veh_approval_remarks'] ?? null,
                'created_by' => $employeePk,
                'created_date' => now(),
            ]);

            return redirect()->route('admin.security.vehicle_pass_approval.index')
                ->with('success', 'Duplicate Vehicle Pass approved successfully');
        } else {
            // Regular vehicle pass
            $pk = (int) $decryptedId;
            $application = VehiclePassTWApply::findOrFail($pk);

            if ($application->vech_card_status != 1) {
                return redirect()->back()->with('error', 'Application already processed');
            }

            $application->vech_card_status = 2; // Approved
            $application->veh_card_forward_status = $validated['forward_status'] ?? 1;
            $application->save();

            // Create/Update approval record
            $approval = VehiclePassTWApplyApproval::where('vehicle_TW_pk', $application->vehicle_tw_pk)
                ->latest('created_date')
                ->first();

            if ($approval) {
                $approval->status = 2;
                $approval->veh_recommend_status = 2;
                $approval->veh_approval_remarks = $validated['veh_approval_remarks'];
                $approval->veh_approved_by = $employeePk;
                $approval->modified_date = now();
                $approval->save();
            } else {
                VehiclePassTWApplyApproval::create([
                    'vehicle_TW_pk' => $application->vehicle_tw_pk,
                    'status' => 2,
                    'veh_recommend_status' => 2,
                    'veh_approval_remarks' => $validated['veh_approval_remarks'],
                    'veh_approved_by' => $employeePk,
                    'created_date' => now(),
                ]);
            }

            return redirect()->route('admin.security.vehicle_pass_approval.index')
                ->with('success', 'Vehicle Pass approved successfully');
        }
    }

    public function reject(Request $request, $id)
    {
        try {
            $decryptedId = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $validated = $request->validate([
            'veh_approval_remarks' => ['required', 'string'],
        ]);

        $user = Auth::user();
        $employeePk = $user->user_id ?? null;

        // Check if it's a duplicate (has dup- prefix after decryption)
        if (is_string($decryptedId) && str_starts_with($decryptedId, 'dup-')) {
            $duplicatePk = substr($decryptedId, 4); // Remove 'dup-' prefix
            $application = VehiclePassDuplicateApplyTwfw::findOrFail($duplicatePk);

            if ((int) $application->vech_card_status !== 1) {
                return redirect()->back()->with('error', 'Application already processed');
            }

            $application->vech_card_status = 3; // Rejected
            $application->save();

            VehiclePassDuplicateApplyApprovalTwfw::create([
                'vehicle_TW_pk' => $duplicatePk,
                'status' => 3, // Rejected
                'veh_approval_remarks' => $validated['veh_approval_remarks'],
                'created_by' => $employeePk,
                'created_date' => now(),
            ]);

            return redirect()->route('admin.security.vehicle_pass_approval.index')
                ->with('success', 'Duplicate Vehicle Pass rejected');
        } else {
            // Regular vehicle pass
            $pk = (int) $decryptedId;
            $application = VehiclePassTWApply::findOrFail($pk);

            if ($application->vech_card_status != 1) {
                return redirect()->back()->with('error', 'Application already processed');
            }

            $application->vech_card_status = 3; // Rejected
            $application->save();

            // Create/Update approval record
            $approval = VehiclePassTWApplyApproval::where('vehicle_TW_pk', $application->vehicle_tw_pk)
                ->latest('created_date')
                ->first();

            if ($approval) {
                $approval->status = 3;
                $approval->veh_recommend_status = 3;
                $approval->veh_approval_remarks = $validated['veh_approval_remarks'];
                $approval->veh_approved_by = $employeePk;
                $approval->modified_date = now();
                $approval->save();
            } else {
                VehiclePassTWApplyApproval::create([
                    'vehicle_TW_pk' => $application->vehicle_tw_pk,
                    'status' => 3,
                    'veh_recommend_status' => 3,
                    'veh_approval_remarks' => $validated['veh_approval_remarks'],
                    'veh_approved_by' => $employeePk,
                    'created_date' => now(),
                ]);
            }

            return redirect()->route('admin.security.vehicle_pass_approval.index')
                ->with('success', 'Vehicle Pass rejected');
        }
    }

    public function allApplications()
    {
        // Regular vehicle pass applications - all
        $regularQuery = VehiclePassTWApply::with(['vehicleType', 'employee', 'createdBy'])
            ->orderBy('created_date', 'desc');

        // Duplicate vehicle pass applications - all
        $duplicateQuery = VehiclePassDuplicateApplyTwfw::with(['vehicleType', 'employee', 'createdBy'])
            ->orderBy('created_date', 'desc');

        $regularRows = $regularQuery->get();
        $duplicateRows = $duplicateQuery->get();

        // Convert regular rows to DTOs (view expects veh_req_id, employee, vehicleType, veh_reg_no, vech_card_status, veh_card_forward_status, vehicle_tw_pk)
        $regularDtos = $regularRows->map(function ($r) {
            $emp = $r->employee;
            return (object) [
                'id' => $r->vehicle_tw_pk,
                'vehicle_tw_pk' => $r->vehicle_tw_pk,
                'veh_req_id' => $r->vehicle_req_id ?? $r->vehicle_tw_pk,
                'veh_reg_no' => $r->vehicle_no ?? '--',
                'vehicle_number' => $r->vehicle_no ?? '--',
                'employee_id' => $r->employee_id_card ?? '--',
                'employee' => $emp ? (object) [
                    'emp_name' => trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')),
                    'emp_code' => $emp->emp_id ?? $emp->pk ?? 'N/A',
                ] : null,
                'vehicleType' => $r->vehicleType ? (object) ['vehicle_type' => $r->vehicleType->vehicle_type ?? '--'] : null,
                'vehicle_type' => $r->vehicleType ? ($r->vehicleType->vehicle_type ?? '--') : '--',
                'status' => match((int) $r->vech_card_status) {
                    1 => 'Pending',
                    2 => 'Approved',
                    3 => 'Rejected',
                    default => 'Unknown'
                },
                'vech_card_status' => (int) $r->vech_card_status,
                'veh_card_forward_status' => (int) ($r->veh_card_forward_status ?? 0),
                'created_date' => $r->created_date,
                'request_type' => 'regular',
            ];
        });

        // Convert duplicate rows to DTOs
        $duplicateDtos = $duplicateRows->map(function ($r) {
            $emp = $r->employee;
            return (object) [
                'id' => $r->vehicle_tw_pk,
                'vehicle_tw_pk' => $r->vehicle_tw_pk,
                'veh_req_id' => $r->vehicle_req_id ?? $r->vehicle_tw_pk,
                'veh_reg_no' => $r->vehicle_no ?? '--',
                'vehicle_number' => $r->vehicle_no ?? '--',
                'employee_id' => $r->employee_id_card ?? '--',
                'employee' => $emp ? (object) [
                    'emp_name' => trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')),
                    'emp_code' => $emp->emp_id ?? $emp->pk ?? 'N/A',
                ] : null,
                'vehicleType' => $r->vehicleType ? (object) ['vehicle_type' => $r->vehicleType->vehicle_type ?? '--'] : null,
                'vehicle_type' => $r->vehicleType ? ($r->vehicleType->vehicle_type ?? '--') : '--',
                'status' => match((int) $r->vech_card_status) {
                    1 => 'Pending',
                    2 => 'Approved',
                    3 => 'Rejected',
                    default => 'Unknown'
                },
                'vech_card_status' => (int) $r->vech_card_status,
                'veh_card_forward_status' => (int) ($r->veh_card_forward_status ?? 0),
                'created_date' => $r->created_date,
                'request_type' => 'duplicate',
            ];
        });

        // Merge and sort by created_date
        $merged = $regularDtos->concat($duplicateDtos)->sortByDesc(function ($d) {
            return $d->created_date ? (\Carbon\Carbon::parse($d->created_date)->timestamp ?? 0) : 0;
        })->values();

        $perPage = 15;
        $page = (int) request()->get('page', 1);
        $applications = new LengthAwarePaginator(
            $merged->forPage($page, $perPage),
            $merged->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('admin.security.vehicle_pass_approval.all', compact('applications'));
    }
}
