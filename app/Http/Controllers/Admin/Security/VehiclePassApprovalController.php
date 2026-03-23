<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Models\EmployeeMaster;
use App\Models\VehiclePassTWApply;
use App\Models\VehiclePassTWApplyApproval;
use App\Models\VehiclePassFWApply;
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
        $user = Auth::user();
        $isLevel1 = hasRole('Security Card') && !hasRole('Admin Security');
        $isLevel2 = hasRole('Admin Security') && !hasRole('Security Card');

        $search = trim(request()->get('search', ''));
        $dateFrom = request()->get('date_from');
        $dateTo = request()->get('date_to');
        $wheeler = request()->get('wheeler', 'tw'); // tw, fw, all

        // Two Wheeler applications (regular only) - no heavy relations here
        $twQuery = VehiclePassTWApply::with(['vehicleType'])
            ->orderBy('created_date', 'desc');

        // Apply filters to TW
        if ($search !== '') {
            $twQuery->where(function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where('employee_id_card', 'like', $like)
                    ->orWhere('vehicle_no', 'like', $like)
                    ->orWhere('applicant_name', 'like', $like);
            });
        }
        if (!empty($dateFrom)) {
            try {
                $from = \Carbon\Carbon::parse($dateFrom)->startOfDay()->toDateTimeString();
                $twQuery->where('created_date', '>=', $from);
            } catch (\Exception $e) {
            }
        }
        if (!empty($dateTo)) {
            try {
                $to = \Carbon\Carbon::parse($dateTo)->endOfDay()->toDateTimeString();
                $twQuery->where('created_date', '<=', $to);
            } catch (\Exception $e) {
            }
        }

        // Four Wheeler applications (regular only)
        $fwQuery = VehiclePassFWApply::with(['vehicleType'])
            ->orderBy('created_date', 'desc');

        // Apply filters to FW
        if ($search !== '') {
            $fwQuery->where(function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where('employee_id_card', 'like', $like)
                    ->orWhere('vehicle_no', 'like', $like)
                    ->orWhere('applicant_name', 'like', $like);
            });
        }
        if (!empty($dateFrom)) {
            try {
                $from = \Carbon\Carbon::parse($dateFrom)->startOfDay()->toDateTimeString();
                $fwQuery->where('created_date', '>=', $from);
            } catch (\Exception $e) {
            }
        }
        if (!empty($dateTo)) {
            try {
                $to = \Carbon\Carbon::parse($dateTo)->endOfDay()->toDateTimeString();
                $fwQuery->where('created_date', '<=', $to);
            } catch (\Exception $e) {
            }
        }

        // Load rows based on wheeler filter (with DB-level pagination when not mixing)
        $twRows = collect();
        $fwRows = collect();

        $perPage = 10;
        $page = (int) request()->get('page', 1);

        if ($wheeler === 'tw') {
            $total = (clone $twQuery)->count();
            $twRows = $twQuery->forPage($page, $perPage)->get();
        } elseif ($wheeler === 'fw') {
            $total = (clone $fwQuery)->count();
            $fwRows = $fwQuery->forPage($page, $perPage)->get();
        } else { // all
            $twRows = $twQuery->get();
            $fwRows = $fwQuery->get();
            $total = $twRows->count() + $fwRows->count();
        }

        // Compute approval stats only for rows on this page (or filtered set)
        $vehicleKeys = $twRows->pluck('vehicle_tw_pk')
            ->merge($fwRows->pluck('vehicle_fw_pk'))
            ->filter()
            ->unique()
            ->values();

        $approvalStats = collect();
        if ($vehicleKeys->isNotEmpty()) {
            $approvalStats = VehiclePassTWApplyApproval::select(
                    'vehicle_TW_pk',
                    DB::raw('MAX(CASE WHEN status = 1 OR veh_recommend_status = 1 THEN 1 ELSE 0 END) as has_level1'),
                    DB::raw('MAX(CASE WHEN status = 2 THEN 1 ELSE 0 END) as has_level2')
                )
                ->whereIn('vehicle_TW_pk', $vehicleKeys)
                ->groupBy('vehicle_TW_pk')
                ->get()
                ->keyBy('vehicle_TW_pk');
        }

        $mapFn = function ($r, string $kind) use ($isLevel1, $isLevel2, $approvalStats) {
            $statusInt = (int) ($r->vech_card_status ?? 1); // 1=Pending,2=Approved,3=Rejected
            $vehicleKey = $kind === 'tw' ? $r->vehicle_tw_pk : $r->vehicle_fw_pk;
            $stat = $approvalStats->get($vehicleKey);
            $hasLevel1 = $stat ? (bool) ($stat->has_level1 ?? false) : false;
            $hasLevel2 = $stat ? (bool) ($stat->has_level2 ?? false) : false;

            $phaseLabel = 'Pending (Level 1)';
            $phaseClass = 'warning';
            if ($statusInt === 2 && $hasLevel2) {
                $phaseLabel = 'Approved';
                $phaseClass = 'success';
            } elseif ($statusInt === 3) {
                $phaseLabel = 'Rejected';
                $phaseClass = 'danger';
            } elseif ($statusInt === 1 && $hasLevel1 && ! $hasLevel2) {
                $phaseLabel = 'Pending Final Approval';
                $phaseClass = 'primary';
            }

            $canApprove = false;
            if ($statusInt === 1) {
                if ($isLevel1 && ! $hasLevel1) {
                    $canApprove = true;
                } elseif ($isLevel2 && $hasLevel1 && ! $hasLevel2) {
                    $canApprove = true;
                }
            }

            $employeeName = method_exists($r, 'getDisplayNameAttribute')
                ? ($r->display_name ?? $r->employee_id_card ?? '--')
                : ($r->employee_id_card ?? '--');

            // Vehicle type label should clearly indicate wheeler type based on source table,
            // not rely on legacy sec_vehicle_type description.
            $vehicleTypeLabel = $kind === 'tw' ? 'Two Wheeler' : 'Four Wheeler';

            return (object) [
                'id' => $kind . '-' . ($kind === 'tw' ? $r->vehicle_tw_pk : $r->vehicle_fw_pk),
                'vehicle_number' => $r->vehicle_no ?? '--',
                'employee_id' => $r->employee_id_card ?? '--',
                'employee_name' => $employeeName,
                'vehicle_type' => $vehicleTypeLabel,
                'status' => $phaseLabel,
                'status_class' => $phaseClass,
                'created_date' => $r->created_date,
                'request_type' => 'fresh',
                'vehicle_pass_no' => $r->vehicle_req_id ?? '--',
                'can_approve' => $canApprove,
                'status_int' => $statusInt,
                'has_level1' => $hasLevel1,
                'has_level2' => $hasLevel2,
            ];
        };

        $twDtos = $twRows->map(fn ($r) => $mapFn($r, 'tw'));
        $fwDtos = $fwRows->map(fn ($r) => $mapFn($r, 'fw'));

        $merged = $twDtos->concat($fwDtos)->sortByDesc(function ($d) {
            return $d->created_date ? (\Carbon\Carbon::parse($d->created_date)->timestamp ?? 0) : 0;
        })->values();

        // For Level 2 (Admin Security), show only records where Level 1 is completed
        if ($isLevel2) {
            $merged = $merged->filter(function ($d) {
                return (bool) ($d->has_level1 ?? false);
            })->values();
        }

        // When wheeler is tw or fw we already applied DB-level pagination,
        // so $merged already contains only current page items.
        // For "all" we paginate in memory.
        if ($wheeler === 'all') {
            $items = $merged->forPage($page, $perPage)->values();
            $totalItems = $merged->count();
        } else {
            $items = $merged->values();
            $totalItems = $total ?? $merged->count();
        }

        $pendingApplications = new LengthAwarePaginator(
            $items,
            $totalItems,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('admin.security.vehicle_pass_approval.index', [
            'pendingApplications' => $pendingApplications,
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'wheeler' => $wheeler,
        ]);
    }

    public function show($id)
    {
        $id = urldecode($id);
        try {
            $decryptedId = decrypt($id);
        } catch (\Exception $e) {
            return redirect()->route('admin.security.vehicle_pass_approval.index')
                ->with('error', 'Invalid request. Please try again.');
        }

        $kind = 'tw';
        $pk = $decryptedId;

        if (is_string($decryptedId) && str_starts_with($decryptedId, 'tw-')) {
            $kind = 'tw';
            $pk = substr($decryptedId, 3);
        } elseif (is_string($decryptedId) && str_starts_with($decryptedId, 'fw-')) {
            $kind = 'fw';
            $pk = substr($decryptedId, 3);
        }

        $user = Auth::user();
        $isLevel1 = hasRole('Security Card') && !hasRole('Admin Security');
        $isLevel2 = hasRole('Admin Security') || hasRole('Admin');

        if ($kind === 'fw') {
            $application = VehiclePassFWApply::with([
                'vehicleType',
                'employee' => function($q) {
                    $q->with(['designation', 'department']);
                },
                'createdBy',
                'approvals'
            ])->find($pk);

            if (! $application) {
                return redirect()->route('admin.security.vehicle_pass_approval.index')
                    ->with('error', 'Application not found.');
            }

            $application->request_type = 'regular';
            $application->encrypted_id = encrypt('fw-' . $application->vehicle_fw_pk);
            // Fallback: try to resolve employee by employee_id_card -> employee_master.emp_id
            if (! $application->employee && $application->employee_id_card) {
                $emp = EmployeeMaster::where('emp_id', $application->employee_id_card)
                    ->with(['designation', 'department'])
                    ->first();
                if ($emp) {
                    $application->setRelation('employee', $emp);
                }
            }
        } else {
            $application = VehiclePassTWApply::with([
                'vehicleType',
                'employee' => function($q) {
                    $q->with(['designation', 'department']);
                },
                'createdBy',
                'approvals'
            ])->find($pk);

            if (! $application) {
                return redirect()->route('admin.security.vehicle_pass_approval.index')
                    ->with('error', 'Application not found.');
            }

            // Fallback: try to resolve employee by employee_id_card -> employee_master.emp_id
            if (! $application->employee && $application->employee_id_card) {
                $emp = EmployeeMaster::where('emp_id', $application->employee_id_card)
                    ->with(['designation', 'department'])
                    ->first();
                if ($emp) {
                    $application->setRelation('employee', $emp);
                }
            }

            $application->request_type = 'regular';
            $application->encrypted_id = encrypt('tw-' . $application->vehicle_tw_pk);
        }

        // Determine phase for show page (same rules as index)
        $statusInt = (int) ($application->vech_card_status ?? 1);
        $vehicleKey = $kind === 'fw' ? $application->vehicle_fw_pk : $application->vehicle_tw_pk;
        $stats = VehiclePassTWApplyApproval::select(
                'vehicle_TW_pk',
                DB::raw('MAX(CASE WHEN status = 1 OR veh_recommend_status = 1 THEN 1 ELSE 0 END) as has_level1'),
                DB::raw('MAX(CASE WHEN status = 2 THEN 1 ELSE 0 END) as has_level2')
            )
            ->where('vehicle_TW_pk', $vehicleKey)
            ->groupBy('vehicle_TW_pk')
            ->first();
        $hasLevel1 = $stats ? (bool) ($stats->has_level1 ?? false) : false;
        $hasLevel2 = $stats ? (bool) ($stats->has_level2 ?? false) : false;

        $canApprove = false;
        if ($statusInt === 1) {
            if ($isLevel1 && ! $hasLevel1) {
                $canApprove = true;
            } elseif ($isLevel2 && $hasLevel1 && ! $hasLevel2) {
                $canApprove = true;
            }
        }

        return view('admin.security.vehicle_pass_approval.show', [
            'application' => $application,
            'canApprove' => $canApprove,
        ]);
    }

    public function approve(Request $request, $id)
    {
        $id = urldecode($id);
        try {
            $decryptedId = decrypt($id);
        } catch (\Exception $e) {
            return redirect()->route('admin.security.vehicle_pass_approval.index')
                ->with('error', 'Invalid or expired link. Please try again from the list.');
        }

        $validated = $request->validate([
            'veh_approval_remarks' => ['nullable', 'string'],
            'forward_status' => ['nullable', 'in:1,2'], // 1=Forwarded, 2=Card Ready
        ]);

        $user = Auth::user();
        $employeePk = $user->user_id ?? null;

        $isLevel1 = hasRole('Security Card') && !hasRole('Admin Security');
        $isLevel2 = hasRole('Admin Security') || hasRole('Admin');

        if (! $isLevel1 && ! $isLevel2) {
            return redirect()->back()->with('error', 'You are not authorized to approve this request.');
        }

        $kind = 'tw';
        $pk = $decryptedId;
        if (is_string($decryptedId) && str_starts_with($decryptedId, 'tw-')) {
            $kind = 'tw';
            $pk = substr($decryptedId, 3);
        } elseif (is_string($decryptedId) && str_starts_with($decryptedId, 'fw-')) {
            $kind = 'fw';
            $pk = substr($decryptedId, 3);
        }

        $application = $kind === 'fw'
            ? VehiclePassFWApply::findOrFail($pk)
            : VehiclePassTWApply::findOrFail($pk);

        if ((int) $application->vech_card_status !== 1) {
            return redirect()->back()->with('error', 'Application already processed');
        }

        $vehicleKey = $kind === 'fw' ? $application->vehicle_fw_pk : $application->vehicle_tw_pk;

        if ($isLevel1) {
            // Level 1: recommend and create next pending step
            $baseApproval = VehiclePassTWApplyApproval::where('vehicle_TW_pk', $vehicleKey)
                ->where('status', 0)
                ->orderByDesc('created_date')
                ->first();

            if (! $baseApproval) {
                $baseApproval = new VehiclePassTWApplyApproval([
                    'vehicle_TW_pk' => $vehicleKey,
                    'status' => 0,
                ]);
            }

            $baseApproval->status = 1;
            $baseApproval->veh_recommend_status = 1;
            $baseApproval->veh_approval_remarks = $validated['veh_approval_remarks'] ?? null;
            $baseApproval->veh_emp_approval_pk = $employeePk;
            $baseApproval->created_by = $baseApproval->created_by ?: $employeePk;
            $baseApproval->created_date = $baseApproval->created_date ?: now();
            $baseApproval->modified_by = $employeePk;
            $baseApproval->modified_date = now();
            $baseApproval->save();

            VehiclePassTWApplyApproval::create([
                'vehicle_TW_pk' => $vehicleKey,
                'status' => 0,
                'veh_recommend_status' => null,
                'veh_approval_remarks' => null,
                'veh_emp_approval_pk' => null,
                'created_by' => $employeePk,
                'created_date' => now(),
                'modified_by' => $employeePk,
                'modified_date' => now(),
            ]);
        } elseif ($isLevel2) {
            // Level 2: final approval
            $pendingApproval = VehiclePassTWApplyApproval::where('vehicle_TW_pk', $vehicleKey)
                ->where('status', 0)
                ->orderByDesc('created_date')
                ->first();

            if (! $pendingApproval) {
                return redirect()->back()->with('error', 'No pending approval step found for this application.');
            }

            $pendingApproval->status = 2;
            $pendingApproval->veh_recommend_status = 2;
            $pendingApproval->veh_approval_remarks = $validated['veh_approval_remarks'] ?? null;
            $pendingApproval->veh_emp_approval_pk = $employeePk;
            $pendingApproval->modified_by = $employeePk;
            $pendingApproval->modified_date = now();
            $pendingApproval->save();

            $application->vech_card_status = 2;
            $application->veh_card_forward_status = $validated['forward_status'] ?? 1;
            $application->save();
        }

        return redirect()->route('admin.security.vehicle_pass_approval.index')
            ->with('success', 'Vehicle Pass approved successfully');
    }

    public function reject(Request $request, $id)
    {
        $id = urldecode($id);
        try {
            $decryptedId = decrypt($id);
        } catch (\Exception $e) {
            return redirect()->route('admin.security.vehicle_pass_approval.index')
                ->with('error', 'Invalid or expired link. Please try again from the list.');
        }

        $validated = $request->validate([
            'veh_approval_remarks' => ['required', 'string'],
        ]);

        $user = Auth::user();
        $employeePk = $user->user_id ?? null;

        $kind = 'tw';
        $pk = $decryptedId;
        if (is_string($decryptedId) && str_starts_with($decryptedId, 'tw-')) {
            $kind = 'tw';
            $pk = substr($decryptedId, 3);
        } elseif (is_string($decryptedId) && str_starts_with($decryptedId, 'fw-')) {
            $kind = 'fw';
            $pk = substr($decryptedId, 3);
        }

        $application = $kind === 'fw'
            ? VehiclePassFWApply::findOrFail($pk)
            : VehiclePassTWApply::findOrFail($pk);

        if ((int) $application->vech_card_status !== 1) {
            return redirect()->back()->with('error', 'Application already processed');
        }

        $vehicleKey = $kind === 'fw' ? $application->vehicle_fw_pk : $application->vehicle_tw_pk;

        $application->vech_card_status = 3; // Rejected
        $application->save();

        VehiclePassTWApplyApproval::create([
            'vehicle_TW_pk' => $vehicleKey,
            'status' => 3,
            'veh_recommend_status' => 3,
            'veh_approval_remarks' => $validated['veh_approval_remarks'],
            'veh_emp_approval_pk' => $employeePk,
            'created_by' => $employeePk,
            'created_date' => now(),
        ]);

        return redirect()->route('admin.security.vehicle_pass_approval.index')
            ->with('success', 'Vehicle Pass rejected');
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
