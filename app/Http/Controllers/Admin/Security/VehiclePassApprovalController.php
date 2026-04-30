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
use Illuminate\Support\Facades\Schema;

class VehiclePassApprovalController extends Controller
{
    /**
     * Consolidated index showing both regular and duplicate vehicle pass applications.
     * Prefix format:
     * - numeric = regular vehicle pass (VehiclePassTWApply)
     * - dup-<pk> = duplicate vehicle pass (VehiclePassDuplicateApplyTwfw)
     */
    public function index(Request $request)
    {
        $isLevel1 = hasRole('Security Card') && !hasRole('Admin Security');
        $isLevel2 = hasRole('Admin Security') && !hasRole('Security Card');

        $search = trim($request->get('search', ''));
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $wheeler = $request->get('wheeler', 'tw');

        $twHasApplicantName = Schema::hasColumn('vehicle_pass_tw_apply', 'applicant_name');
        $fwHasApplicantName = Schema::hasColumn('vehicle_pass_fw_apply', 'applicant_name');

        // Two Wheeler applications (regular only)
        $twQuery = VehiclePassTWApply::with([
            'vehicleType',
            'employee' => function ($q) {
                $q->select(['pk', 'emp_id', 'first_name', 'last_name']);
            },
        ])
            ->orderBy('created_date', 'desc');

        if ($search !== '') {
            $twQuery->where(function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where('employee_id_card', 'like', $like)
                    ->orWhere('vehicle_no', 'like', $like);
                if ($twHasApplicantName) {
                    $q->orWhere('applicant_name', 'like', $like);
                }
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
        $fwQuery = VehiclePassFWApply::with([
            'vehicleType',
            'employee' => function ($q) {
                $q->select(['pk', 'emp_id', 'first_name', 'last_name']);
            },
        ])
            ->orderBy('created_date', 'desc');

        if ($search !== '') {
            $fwQuery->where(function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where('employee_id_card', 'like', $like)
                    ->orWhere('vehicle_no', 'like', $like);
                if ($fwHasApplicantName) {
                    $q->orWhere('applicant_name', 'like', $like);
                }
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

        $twRows = collect();
        $fwRows = collect();

        if ($wheeler === 'tw') {
            $twRows = $twQuery->get();
        } elseif ($wheeler === 'fw') {
            $fwRows = $fwQuery->get();
        } else {
            $twRows = $twQuery->get();
            $fwRows = $fwQuery->get();
        }

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

        $mapFn = function ($r, string $kind) use ($isLevel1, $isLevel2, $approvalStats, $twHasApplicantName, $fwHasApplicantName) {
            $statusInt = (int) ($r->vech_card_status ?? 1);
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
            } elseif ($statusInt === 1 && $hasLevel1 && !$hasLevel2) {
                $phaseLabel = 'Pending Final Approval';
                $phaseClass = 'primary';
            }

            $canApprove = false;
            if ($statusInt === 1) {
                if ($isLevel1 && !$hasLevel1) {
                    $canApprove = true;
                } elseif ($isLevel2 && $hasLevel1 && !$hasLevel2) {
                    $canApprove = true;
                }
            }

            $employeeName = $r->employee_id_card ?? '--';
            if (isset($r->employee) && $r->employee) {
                $resolved = trim((string) (($r->employee->first_name ?? '') . ' ' . ($r->employee->last_name ?? '')));
                if ($resolved !== '') {
                    $employeeName = $resolved . ($r->employee_id_card ? ' (' . $r->employee_id_card . ')' : '');
                }
            } elseif (($kind === 'tw' && $twHasApplicantName) || ($kind === 'fw' && $fwHasApplicantName)) {
                $fallbackName = trim((string) ($r->applicant_name ?? ''));
                if ($fallbackName !== '') {
                    $employeeName = $fallbackName . ($r->employee_id_card ? ' (' . $r->employee_id_card . ')' : '');
                }
            }

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

        if ($isLevel2) {
            $merged = $merged->filter(function ($d) {
                return (bool) ($d->has_level1 ?? false);
            })->values();
        }

        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $newPage = max(1, (int) $request->get('new_page', 1));
        $forPage = max(1, (int) $request->get('for_page', 1));
        $issuedPage = max(1, (int) $request->get('issued_page', 1));
        $rejectPage = max(1, (int) $request->get('reject_page', 1));

        $newList = $merged->filter(function ($d) {
            $isApproved = (($d->status_int ?? 1) === 2) && (($d->has_level2 ?? false) === true);
            $isRejected = (($d->status_int ?? 1) === 3);
            return !$isApproved && !$isRejected && (($d->status_int ?? 1) === 1) && (($d->can_approve ?? false) === true);
        })->values();

        $processedList = $merged->filter(function ($d) {
            $isApproved = (($d->status_int ?? 1) === 2) && (($d->has_level2 ?? false) === true);
            $isRejected = (($d->status_int ?? 1) === 3);
            return !$isApproved && !$isRejected && ((($d->status_int ?? 1) !== 1) || (($d->can_approve ?? false) !== true));
        })->values();

        // Issued tab should show only finally approved records.
        $issuedList = $merged->filter(fn ($d) => (($d->status_int ?? 1) === 2) && (($d->has_level2 ?? false) === true))->values();
        $rejectedList = $merged->filter(fn ($d) => ($d->status_int ?? 1) === 3)->values();

        $newApplications = new LengthAwarePaginator(
            $newList->forPage($newPage, $perPage)->values(),
            $newList->count(),
            $perPage,
            $newPage,
            ['path' => $request->url(), 'pageName' => 'new_page', 'query' => $request->query()]
        );
        $processedApplications = new LengthAwarePaginator(
            $processedList->forPage($forPage, $perPage)->values(),
            $processedList->count(),
            $perPage,
            $forPage,
            ['path' => $request->url(), 'pageName' => 'for_page', 'query' => $request->query()]
        );
        $issuedApplications = new LengthAwarePaginator(
            $issuedList->forPage($issuedPage, $perPage)->values(),
            $issuedList->count(),
            $perPage,
            $issuedPage,
            ['path' => $request->url(), 'pageName' => 'issued_page', 'query' => $request->query()]
        );
        $rejectedApplications = new LengthAwarePaginator(
            $rejectedList->forPage($rejectPage, $perPage)->values(),
            $rejectedList->count(),
            $perPage,
            $rejectPage,
            ['path' => $request->url(), 'pageName' => 'reject_page', 'query' => $request->query()]
        );

        $activeTab = $request->get('tab', 'new');
        if ($activeTab === 'archive') {
            $activeTab = 'issued';
        }
        if (!in_array($activeTab, ['new', 'for_approval', 'issued', 'rejected'], true)) {
            $activeTab = 'new';
        }

        return view('admin.security.vehicle_pass_approval.index', [
            'newApplications' => $newApplications,
            'processedApplications' => $processedApplications,
            'issuedApplications' => $issuedApplications,
            'rejectedApplications' => $rejectedApplications,
            'activeTab' => $activeTab,
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
