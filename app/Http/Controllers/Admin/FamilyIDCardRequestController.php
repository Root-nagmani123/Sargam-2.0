<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\FamilyIDCardExport;
use App\Models\EmployeeMaster;
use App\Models\SecurityFamilyIdApply;
use App\Models\SecurityParmIdApply;
use App\Support\IdCardSecurityMapper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Request Family ID Card - mapped to security_family_id_apply.
 */
class FamilyIDCardRequestController extends Controller
{
    /**
     * Group rows by (emp_id_apply, created_by, created_date) and return paginated group list.
     * Supports search and filters
     */
    private function groupedFamilyRequests(int $idStatus, int $perPage, string $pageName = 'page'): LengthAwarePaginator
    {
        $query = SecurityFamilyIdApply::query();
        $query->where('created_by', Auth::user()->user_id);
        
        if ($idStatus === 1) {
            $query->where('id_status', 1);
        } else {
            $query->whereIn('id_status', [2, 3]);
        }
        
        // Apply search filter
        $search = request()->get('search', '');
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('emp_id_apply', 'LIKE', "%{$search}%")
                  ->orWhere('family_name', 'LIKE', "%{$search}%")
                  ->orWhere('family_relation', 'LIKE', "%{$search}%");
            });
        }
        
        // Apply card type filter
        $cardType = request()->get('card_type', '');
        if (!empty($cardType)) {
            // This filter would be applied at grouping level
        }
        
        $all = $query->orderBy('created_date', 'desc')->get();
        
        $groupKey = function ($r) {
            $date = $r->created_date ? Carbon::parse($r->created_date)->format('Y-m-d H:i:s') : '';
            return $r->emp_id_apply . '|' . ($r->created_by ?? '') . '|' . $date;
        };
        
        $groups = $all->groupBy($groupKey);
        
        $groupList = $groups->map(function ($rows) {
            $first = $rows->sortBy('fml_id_apply')->first();
            $empName = '--';
            $designation = '--';
            $section = '--';
            
            if ($first->created_by) {
                // created_by stores employee pk (from user_credentials.user_id which maps to employee_master.pk)
                $emp = EmployeeMaster::with(['designation', 'department'])
                    ->where('pk', $first->created_by)
                    ->orWhere('pk_old', $first->created_by)
                    ->first();
                if ($emp) {
                    $empName = trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? ''));
                    $designation = $emp->designation->designation_name ?? '--';
                    $section = $emp->department->department_name ?? '--';
                }
            }
            if ($empName === '' || $empName === ' ') {
                $empName = $first->emp_id_apply ?? '--';
            }
            
            return (object) [
                'first_id' => $first->fml_id_apply,
                'created_at' => $first->created_date,
                'employee_id' => $first->emp_id_apply,
                'employee_name' => $empName,
                'designation' => $designation,
                'section' => $section,
                'member_count' => $rows->count(),
                'card_type' => $first->card_type ?? 'Family',
                'id_status' => (int) ($first->id_status ?? 1),
            ];
        })->values();
        
        // Apply card type filter after grouping
        $cardType = request()->get('card_type', '');
        if (!empty($cardType)) {
            $groupList = $groupList->filter(function ($group) use ($cardType) {
                return $group->card_type === $cardType;
            })->values();
        }
        
        $page = request()->get($pageName, 1);
        $slice = $groupList->forPage($page, $perPage);
        
        return new LengthAwarePaginator(
            $slice->values(),
            $groupList->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => $pageName, 'query' => request()->query()]
        );
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
        $activeRequests = $this->groupedFamilyRequests(1, $perPage, 'page');
        $activeRequests->withQueryString();
        $archivedRequests = $this->groupedFamilyRequests(0, $perPage, 'archive_page');
        $archivedRequests->withQueryString();

        return view('admin.family_idcard.index', [
            'activeRequests' => $activeRequests,
            'archivedRequests' => $archivedRequests,
        ]);
    }

    /**
     * List of family members for one request (same emp_id_apply + created_by + created_date).
     */
    public function members($id)
    {
        $row = SecurityFamilyIdApply::where('fml_id_apply', $id)->firstOrFail();
        $members = SecurityFamilyIdApply::where('emp_id_apply', $row->emp_id_apply)
            ->where('created_by', $row->created_by)
            ->where('created_date', $row->created_date)
            ->orderBy('fml_id_apply')
            ->get();
        $memberDtos = $members->map(fn ($r) => IdCardSecurityMapper::toFamilyRequestDto($r));
    //    print_r($memberDtos);
    //    die;
        return view('admin.family_idcard.members', [
            'parentId' => $row->emp_id_apply,
            'requestDate' => $row->created_date,
            'members' => $memberDtos,
        ]);
    }

    public function create()
    {
        $userDepartmentName = null;
        $approvalAuthorityEmployees = collect();
        $defaultApprovalAuthorityPk = null;
        $defaultEmployeeIdPermanent = '';
        $defaultEmployeeIdContractual = '';
        $defaultDesignation = '';
        $authUserId = Auth::user()->user_id ?? null;
        if ($authUserId) {
            $authEmp = EmployeeMaster::with(['department', 'designation'])
                ->where('pk', $authUserId)
                ->orWhere('pk_old', $authUserId)
                ->first();
                if ($authEmp) {
                    $defaultApprovalAuthorityPk = $authEmp->pk;
                    $defaultDesignation = $authEmp->designation->designation_name ?? '';
                    if ($authEmp->department_master_pk) {
                        $userDepartmentName = $authEmp->department->department_name ?? null;
                        $approvalAuthorityEmployees = EmployeeMaster::with('designation')
                            ->where('department_master_pk', $authEmp->department_master_pk)
                            ->when(Schema::hasColumn('employee_master', 'payroll'), fn ($q) => $q->where('payroll', 0))
                            ->when(Schema::hasColumn('employee_master', 'status'), fn ($q) => $q->where('status', 1))
                            ->orderBy('first_name')
                            ->orderBy('last_name')
                            ->get(['pk', 'first_name', 'last_name', 'designation_master_pk']);
                    }

                    // Permanent Employee: default to EmployeeMaster.emp_id (Employee ID)
                    $defaultEmployeeIdPermanent = $authEmp->emp_id ?? '';

                    // If an approved security_parm_id_apply exists with an ID card number, prefer that
                    $parmRow = DB::table('security_parm_id_apply')
                        ->where('employee_master_pk', $authEmp->pk)
                        ->where('id_status', SecurityParmIdApply::ID_STATUS_APPROVED)
                        ->orderBy('created_date', 'desc')
                        ->first(['id_card_no', 'emp_id_apply']);
                    if ($parmRow && !empty($parmRow->id_card_no)) {
                        $defaultEmployeeIdPermanent = $parmRow->id_card_no;
                    }
                // Contractual: approved ID from security_con_oth_id_apply (created_by = employee pk)
                $conRow = DB::table('security_con_oth_id_apply')
                    ->where('created_by', $authEmp->pk)
                    ->where('id_status', SecurityParmIdApply::ID_STATUS_APPROVED)
                    ->orderBy('created_date', 'desc')
                    ->first(['id_card_no', 'emp_id_apply']);
                if ($conRow) {
                    $defaultEmployeeIdContractual = $conRow->id_card_no ?? $conRow->emp_id_apply ?? '';
                }
            }
        }

        return view('admin.family_idcard.create', [
            'userDepartmentName' => $userDepartmentName,
            'approvalAuthorityEmployees' => $approvalAuthorityEmployees,
            'defaultApprovalAuthorityPk' => $defaultApprovalAuthorityPk,
            'defaultEmployeeIdPermanent' => $defaultEmployeeIdPermanent,
            'defaultEmployeeIdContractual' => $defaultEmployeeIdContractual,
            'defaultDesignation' => $defaultDesignation,
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'employee_id' => 'required|string|max:100',
            'designation' => 'required|string|max:255',
            'card_type' => 'required|string|max:100',
            'section' => 'required|string|max:255',
            'group_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'members' => 'required|array|min:1',
            'members.*.name' => 'required|string|max:255',
            'members.*.relation' => 'nullable|string|max:100',
            'members.*.family_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'members.*.dob' => 'nullable|date',
            'members.*.valid_from' => 'nullable|date',
            'members.*.valid_to' => 'nullable|date',
        ];

        $request->validate($rules);

        // Valid To must not be less than Valid From for each member
        $members = $request->input('members', []);
        foreach ($members as $index => $member) {
            $from = $member['valid_from'] ?? null;
            $to = $member['valid_to'] ?? null;
            if (!empty($from) && !empty($to) && $to < $from) {
                throw ValidationException::withMessages([
                    "members.{$index}.valid_to" => 'Valid To date must not be earlier than Valid From date.',
                ]);
            }

            // Enforce minimum age: family member must be 13 years or older
            $dob = $member['dob'] ?? null;
            if (!empty($dob)) {
                try {
                    $birthDate = Carbon::parse($dob)->startOfDay();
                    $thirteenYearsAgo = Carbon::now()->subYears(13)->startOfDay();
                    if ($birthDate->greaterThan($thirteenYearsAgo)) {
                        throw ValidationException::withMessages([
                            "members.{$index}.dob" => 'Family members younger than 13 years do not require a separate ID card.',
                        ]);
                    }
                } catch (\Exception $e) {
                    // If DOB parsing fails, let default date validation handle it
                }
            }
        }

        // Check for duplicate family member names
        $members = $request->input('members', []);
        $memberNames = [];
        $duplicateNames = [];
        foreach ($members as $member) {
            $name = trim($member['name'] ?? '');
            if (!empty($name)) {
                $nameKey = strtolower($name);
                if (in_array($nameKey, $memberNames)) {
                    if (!in_array($nameKey, $duplicateNames)) {
                        $duplicateNames[] = $nameKey;
                    }
                } else {
                    $memberNames[] = $nameKey;
                }
            }
        }
        if (!empty($duplicateNames)) {
            throw ValidationException::withMessages([
                'members' => 'Duplicate family member(s) found: ' . implode(', ', array_map('ucfirst', $duplicateNames)) . '. Each family member can only be added once.'
            ]);
        }

        $employeeId = $request->input('employee_id');
        $createdBy = Auth::user()->user_id;
        $employeeType = $request->input('employee_type', 'Permanent Employee');
        $approvalAuthorityPk = $employeeType === 'Contractual Employee'
            ? (int) $request->input('approval_authority')
            : null;
        $count = 0;
        $nextPk = (int) SecurityFamilyIdApply::max('pk') + 1;
        $groupPhotoPath = null;
        if ($request->hasFile('group_photo')) {
            $groupPhotoPath = $request->file('group_photo')->store('family_idcard/Group_photos', 'public');
        }
        foreach ($members as $index => $member) {
            $name = $member['name'] ?? null;
            if (empty(trim($name ?? ''))) {
                continue;
            }

            // Hard guard: prevent duplicate family member (same employee + same name + same relation) in DB
            $existingDuplicate = SecurityFamilyIdApply::where('emp_id_apply', $employeeId)
                ->whereRaw('LOWER(TRIM(family_name)) = ?', [strtolower(trim($name))])
                ->when(!empty($member['relation'] ?? null), function ($q) use ($member) {
                    $q->whereRaw('LOWER(TRIM(family_relation)) = ?', [strtolower(trim($member['relation']))]);
                })
                ->exists();

            if ($existingDuplicate) {
                throw ValidationException::withMessages([
                    "members.{$index}.name" => "Family member '{$name}' with the same relation is already added for this employee. Duplicate entries are not allowed.",
                ]);
            }

            $familyPhotoPath_individual = null;
            if ($request->hasFile('members.' . $index . '.family_photo')) {
                $familyPhotoPath_individual = $request->file('members.' . $index . '.family_photo')->store('family_idcard/Individual_photos', 'public');
            }
            $fmlIdApply = 'FMD' . str_pad((string) $nextPk, 5, '0', STR_PAD_LEFT);
            $nextPk++;

            SecurityFamilyIdApply::create([
                'fml_id_apply' => $fmlIdApply,
                'family_name' => $name,
                'family_relation' => !empty($member['relation']) ? $member['relation'] : null,
                'card_valid_from' => !empty($member['valid_from']) ? $member['valid_from'] : null,
                'card_valid_to' => !empty($member['valid_to']) ? $member['valid_to'] : null,
                'id_status' => 1,
                'created_by' => $createdBy,
                'created_date' => now()->format('Y-m-d H:i:s'),
                'id_photo_path' => $familyPhotoPath_individual,
                'family_photo' => $groupPhotoPath,
                'employee_dob' => !empty($member['dob']) ? $member['dob'] : null,
                'emp_id_apply' => $employeeId,
               
            ]);
            $count++;
        }

        $message = $count === 1
            ? 'Family ID Card request created successfully!'
            : "{$count} Family ID Card requests created successfully!";

        return redirect()->route('admin.family_idcard.index')->with('success', $message);
    }

    public function show($id)
    {
        $row = SecurityFamilyIdApply::where('fml_id_apply', $id)->firstOrFail();
        $request = IdCardSecurityMapper::toFamilyRequestDto($row);

        // Load all family members for this request (same emp_id_apply + created_by + created_date)
        $members = $this->sameGroupQuery($row)
            ->orderBy('fml_id_apply')
            ->get()
            ->map(fn ($r) => IdCardSecurityMapper::toFamilyRequestDto($r));

        return view('admin.family_idcard.show', [
            'request' => $request,
            'members' => $members,
        ]);
    }

    public function edit($id)
    {
        $row = SecurityFamilyIdApply::where('fml_id_apply', $id)->firstOrFail();
        $request = IdCardSecurityMapper::toFamilyRequestDto($row);
        // Same group = same emp_id_apply + created_by + created_date (include all members)
        $createdDate = $row->created_date instanceof \DateTimeInterface
            ? $row->created_date->format('Y-m-d H:i:s')
            : \Carbon\Carbon::parse($row->created_date)->format('Y-m-d H:i:s');
        $existingFamilyMembers = SecurityFamilyIdApply::where('emp_id_apply', $row->emp_id_apply)
            ->where('created_by', $row->created_by)
            ->whereRaw("DATE_FORMAT(created_date, '%Y-%m-%d %H:%i:%s') = ?", [$createdDate])
            ->orderBy('fml_id_apply')
            ->get()
            ->map(fn ($r) => IdCardSecurityMapper::toFamilyRequestDto($r));

        // Employee type for this family request (defaults to Permanent for older rows)
        $employeeType = $row->employee_type ?? 'Permanent Employee';

        // For contractual employees, prepare approval authority dropdown data
        $approvalAuthorityEmployees = collect();
        $currentApprovalAuthorityPk = null;

        if ($employeeType === 'Contractual Employee') {
            $authUserId = Auth::user()->user_id ?? null;
            if ($authUserId) {
                $authEmp = EmployeeMaster::with(['department', 'designation'])
                    ->where('pk', $authUserId)
                    ->orWhere('pk_old', $authUserId)
                    ->first();

                if ($authEmp && $authEmp->department_master_pk) {
                    $approvalAuthorityEmployees = EmployeeMaster::with('designation')
                        ->where('department_master_pk', $authEmp->department_master_pk)
                        ->when(Schema::hasColumn('employee_master', 'payroll'), fn ($q) => $q->where('payroll', 0))
                        ->when(Schema::hasColumn('employee_master', 'status'), fn ($q) => $q->where('status', 1))
                        ->orderBy('first_name')
                        ->orderBy('last_name')
                        ->get(['pk', 'first_name', 'last_name', 'designation_master_pk']);
                }
            }

            // Existing approval authority (if any) on the record
            if (isset($row->department_approval_emp_pk)) {
                $currentApprovalAuthorityPk = (int) $row->department_approval_emp_pk;
            }
        }

        return view('admin.family_idcard.edit', [
            'request' => $request,
            'existingFamilyMembers' => $existingFamilyMembers,
            'employeeType' => $employeeType,
            'approvalAuthorityEmployees' => $approvalAuthorityEmployees,
            'currentApprovalAuthorityPk' => $currentApprovalAuthorityPk,
        ]);
    }

    public function update(Request $request, $id)
    {
        $row = SecurityFamilyIdApply::where('fml_id_apply', $id)->firstOrFail();
        $employeeType = $row->employee_type;
        
        $validated = $request->validate([
            'employee_id' => 'required|string|max:100',
            'designation' => 'required|string|max:255',
            'card_type' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'relation' => 'nullable|string|max:100',
            'section' => 'required|string|max:255',
            'family_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'dob' => 'nullable|date',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date',
            'family_member_id' => 'nullable|string|max:100',
            'status' => 'nullable|in:Pending,Approved,Rejected,Issued',
            'remarks' => 'nullable|string',
        ]);
        
        // Add approval authority validation for contractual employees
        if ($employeeType === 'Contractual Employee') {
            $approvalValidated = $request->validate([
                'approval_authority' => 'required|integer|exists:employee_master,pk',
            ]);
            $approvalAuthorityPk = (int) $approvalValidated['approval_authority'];
        }

        $from = $validated['valid_from'] ?? null;
        $to = $validated['valid_to'] ?? null;
        if (!empty($from) && !empty($to) && $to < $from) {
            throw ValidationException::withMessages([
                'valid_to' => 'Valid To date must not be earlier than Valid From date.',
            ]);
        }

        $row->family_name = $validated['name'];
        $row->family_relation = $validated['relation'] ?? null;
        $row->emp_id_apply = $validated['employee_id'];
        $row->card_valid_from = $validated['valid_from'] ?? null;
        $row->card_valid_to = $validated['valid_to'] ?? null;
        $row->employee_dob = $validated['dob'] ?? null;
        $row->remarks = $validated['remarks'] ?? null;
        $row->id_card_no = $validated['family_member_id'] ?? null;
        if ($employeeType === 'Contractual Employee' && isset($approvalAuthorityPk)) {
            $row->department_approval_emp_pk = $approvalAuthorityPk;
        }
        if ($request->hasFile('family_photo')) {
            $row->family_photo = $request->file('family_photo')->store('family_idcard/photos', 'public');
            $row->id_photo_path = $row->family_photo;
        }
        $row->save();

        // --- Sync family members list (inline table, create-style) ---
        $members = $request->input('members', []);

        // If member rows are present, validate and sync them with security_family_id_apply
        if (is_array($members) && count($members) > 0) {
            // Basic validation: valid_to must be >= valid_from when both are present
            foreach ($members as $idx => $member) {
                $from = $member['valid_from'] ?? null;
                $to = $member['valid_to'] ?? null;
                if (!empty($from) && !empty($to) && $to < $from) {
                    throw ValidationException::withMessages([
                        "members.{$idx}.valid_to" => 'Valid To date must not be earlier than Valid From date.',
                    ]);
                }
            }

            // Existing group rows (all members for this application)
            $existingRows = $this->sameGroupQuery($row)->orderBy('fml_id_apply')->get();
            $existingById = $existingRows->keyBy('fml_id_apply');

            $seenIds = [];
            $nextPk = (int) SecurityFamilyIdApply::max('pk') + 1;

            foreach ($members as $idx => $member) {
                $name = trim($member['name'] ?? '');
                $memberId = $member['id'] ?? null;
                $relation = $member['relation'] ?? null;
                $dob = $member['dob'] ?? null;
                $validFrom = $member['valid_from'] ?? null;
                $validTo = $member['valid_to'] ?? null;
                $fileKey = "members.{$idx}.family_photo";

                // Skip completely empty rows
                if ($name === '' && empty($memberId)) {
                    continue;
                }

                // Update existing member
                if (!empty($memberId) && $existingById->has($memberId)) {
                    /** @var \App\Models\SecurityFamilyIdApply $memberRow */
                    $memberRow = $existingById->get($memberId);
                    $memberRow->family_name = $name !== '' ? $name : $memberRow->family_name;
                    $memberRow->family_relation = $relation ?: null;
                    $memberRow->employee_dob = $dob ?: null;
                    $memberRow->card_valid_from = $validFrom ?: null;
                    $memberRow->card_valid_to = $validTo ?: null;

                    if ($request->hasFile($fileKey)) {
                        $path = $request->file($fileKey)->store('family_idcard/Individual_photos', 'public');
                        $memberRow->id_photo_path = $path;
                    }
                    $memberRow->save();
                    $seenIds[] = $memberId;
                    continue;
                }

                // Create new member (name required)
                if ($name !== '') {
                    $fmlIdApply = 'FMD' . str_pad((string) $nextPk, 5, '0', STR_PAD_LEFT);
                    $nextPk++;

                    $createdDate = $row->created_date instanceof \DateTimeInterface
                        ? $row->created_date->format('Y-m-d H:i:s')
                        : Carbon::parse($row->created_date)->format('Y-m-d H:i:s');

                    $photoPath = null;
                    if ($request->hasFile($fileKey)) {
                        $photoPath = $request->file($fileKey)->store('family_idcard/Individual_photos', 'public');
                    }

                    SecurityFamilyIdApply::create([
                        'pk' => $nextPk - 1,
                        'fml_id_apply' => $fmlIdApply,
                        'family_name' => $name,
                        'family_relation' => $relation ?: null,
                        'card_valid_from' => $validFrom ?: null,
                        'card_valid_to' => $validTo ?: null,
                        'id_status' => (int) $row->id_status,
                        'created_by' => $row->created_by,
                        'created_date' => $createdDate,
                        'id_photo_path' => $photoPath,
                        'family_photo' => $row->family_photo,
                        'employee_dob' => $dob ?: null,
                        'emp_id_apply' => $row->emp_id_apply,
                        'employee_type' => $row->employee_type ?? null,
                        'department_approval_emp_pk' => $row->department_approval_emp_pk ?? null,
                        'card_type' => $row->card_type ?? 'Family',
                    ]);
                }
            }

            // Delete members that were removed in the UI (but keep at least one record)
            $submittedIds = array_filter($seenIds, fn ($v) => !empty($v));
            $idsToDelete = $existingRows->pluck('fml_id_apply')
                ->reject(function ($existingId) use ($submittedIds) {
                    return in_array($existingId, $submittedIds, true);
                })
                ->values();

            if ($idsToDelete->count() > 0 && $existingRows->count() > $idsToDelete->count()) {
                SecurityFamilyIdApply::whereIn('fml_id_apply', $idsToDelete)->delete();
            }
        }

        return redirect()->route('admin.family_idcard.show', $row->fml_id_apply)
            ->with('success', 'Family ID Card request updated successfully!');
    }

    /**
     * Ensure member row belongs to the same group as the main row (id = fml_id_apply of main).
     */
    private function sameGroupQuery(SecurityFamilyIdApply $mainRow)
    {
        $createdDate = $mainRow->created_date instanceof \DateTimeInterface
            ? $mainRow->created_date->format('Y-m-d H:i:s')
            : Carbon::parse($mainRow->created_date)->format('Y-m-d H:i:s');
        return SecurityFamilyIdApply::where('emp_id_apply', $mainRow->emp_id_apply)
            ->where('created_by', $mainRow->created_by)
            ->whereRaw("DATE_FORMAT(created_date, '%Y-%m-%d %H:%i:%s') = ?", [$createdDate]);
    }

    /**
     * Add a new family member to the same group (same emp_id_apply, created_by, created_date).
     */
    public function storeMember(Request $request, $id)
    {
        $mainRow = SecurityFamilyIdApply::where('fml_id_apply', $id)->firstOrFail();
        if ((int) $mainRow->created_by !== (int) (Auth::user()->user_id ?? Auth::id())) {
            abort(403, 'You can only add members to your own family ID card request.');
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'relation' => 'nullable|string|max:100',
            'dob' => 'nullable|date',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'family_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);
        $from = $validated['valid_from'] ?? null;
        $to = $validated['valid_to'] ?? null;
        if (!empty($from) && !empty($to) && $to < $from) {
            throw ValidationException::withMessages(['valid_to' => 'Valid To must not be earlier than Valid From.']);
        }
        $nextPk = (int) SecurityFamilyIdApply::max('pk') + 1;
        $fmlIdApply = 'FMD' . str_pad((string) $nextPk, 5, '0', STR_PAD_LEFT);
        $createdDate = $mainRow->created_date instanceof \DateTimeInterface
            ? $mainRow->created_date->format('Y-m-d H:i:s')
            : Carbon::parse($mainRow->created_date)->format('Y-m-d H:i:s');
        $photoPath = null;
        if ($request->hasFile('family_photo')) {
            $photoPath = $request->file('family_photo')->store('family_idcard/Individual_photos', 'public');
        }
        SecurityFamilyIdApply::create([
            'fml_id_apply' => $fmlIdApply,
            'family_name' => $validated['name'],
            'family_relation' => $validated['relation'] ?? null,
            'card_valid_from' => $validated['valid_from'] ?? null,
            'card_valid_to' => $validated['valid_to'] ?? null,
            'employee_dob' => $validated['dob'] ?? null,
            'id_status' => (int) $mainRow->id_status,
            'created_by' => $mainRow->created_by,
            'created_date' => $createdDate,
            'id_photo_path' => $photoPath,
            'family_photo' => $mainRow->family_photo,
            'emp_id_apply' => $mainRow->emp_id_apply,
            'employee_type' => $mainRow->employee_type ?? null,
            'department_approval_emp_pk' => $mainRow->department_approval_emp_pk ?? null,
        ]);
        return redirect()->route('admin.family_idcard.edit', $id)
            ->with('success', 'Family member added successfully.');
    }

    /**
     * Update an existing family member in the same group.
     */
    public function updateMember(Request $request, $id, $memberId)
    {
        $mainRow = SecurityFamilyIdApply::where('fml_id_apply', $id)->firstOrFail();
        if ((int) $mainRow->created_by !== (int) (Auth::user()->user_id ?? Auth::id())) {
            abort(403, 'You can only edit members of your own family ID card request.');
        }
        $memberRow = $this->sameGroupQuery($mainRow)->where('fml_id_apply', $memberId)->firstOrFail();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'relation' => 'nullable|string|max:100',
            'dob' => 'nullable|date',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'family_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);
        $from = $validated['valid_from'] ?? null;
        $to = $validated['valid_to'] ?? null;
        if (!empty($from) && !empty($to) && $to < $from) {
            throw ValidationException::withMessages(['valid_to' => 'Valid To must not be earlier than Valid From.']);
        }
        $memberRow->family_name = $validated['name'];
        $memberRow->family_relation = $validated['relation'] ?? null;
        $memberRow->employee_dob = $validated['dob'] ?? null;
        $memberRow->card_valid_from = $validated['valid_from'] ?? null;
        $memberRow->card_valid_to = $validated['valid_to'] ?? null;
        if ($request->hasFile('family_photo')) {
            $memberRow->id_photo_path = $request->file('family_photo')->store('family_idcard/Individual_photos', 'public');
            $memberRow->family_photo = $memberRow->family_photo ?? $memberRow->id_photo_path;
        }
        $memberRow->save();
        return redirect()->route('admin.family_idcard.edit', $id)
            ->with('success', 'Family member updated successfully.');
    }

    /**
     * Remove a family member from the group (must be same group, and cannot delete the main row).
     */
    public function destroyMember($id, $memberId)
    {
        $mainRow = SecurityFamilyIdApply::where('fml_id_apply', $id)->firstOrFail();
        if ((int) $mainRow->created_by !== (int) (Auth::user()->user_id ?? Auth::id())) {
            abort(403, 'You can only remove members from your own family ID card request.');
        }
        if ($memberId === $id) {
            return redirect()->route('admin.family_idcard.edit', $id)
                ->with('error', 'You cannot remove the main card. Edit the main details above instead.');
        }
        $memberRow = $this->sameGroupQuery($mainRow)->where('fml_id_apply', $memberId)->firstOrFail();
        $memberRow->delete();
        return redirect()->route('admin.family_idcard.edit', $id)
            ->with('success', 'Family member removed successfully.');
    }

    public function destroy($id)
    {
        $row = SecurityFamilyIdApply::where('fml_id_apply', $id)->firstOrFail();
        $row->delete();
        return redirect()->route('admin.family_idcard.index')
            ->with('success', 'Family ID Card request archived successfully!');
    }

    public function restore($id)
    {
        // Restore a rejected Family ID card request back to Active list
        // Only rejected requests (id_status = 3) can be restored, not approved ones
        $row = SecurityFamilyIdApply::where('fml_id_apply', $id)->first();
        if ($row) {
            // Check if the request is rejected (id_status = 3)
            if ((int) $row->id_status !== 3) {
                return redirect()
                    ->route('admin.family_idcard.index')
                    ->with('error', 'Only rejected requests can be restored.');
            }

            // Find all rows belonging to the same grouped request (same emp_id_apply + created_by + created_date)
            $query = SecurityFamilyIdApply::where('emp_id_apply', $row->emp_id_apply)
                ->where('created_by', $row->created_by);

            if ($row->created_date) {
                $created = \Carbon\Carbon::parse($row->created_date)->format('Y-m-d H:i:s');
                $query->whereRaw("DATE_FORMAT(created_date, '%Y-%m-%d %H:%i:%s') = ?", [$created]);
            }

            // Move all members in this group back to id_status = 1 (Active)
            $query->update(['id_status' => 1]);

            return redirect()
                ->route('admin.family_idcard.index')
                ->with('success', 'Family ID Card request restored to Active list successfully.');
        }

        return redirect()
            ->route('admin.family_idcard.index')
            ->with('error', 'Family ID Card request not found.');
    }

    public function forceDelete($id)
    {
        return redirect()->route('admin.family_idcard.index')
            ->with('info', 'Security table does not use soft delete.');
    }

    /**
     * Submit duplicate ID card request for a family member (modal form).
     */
    public function duplicateRequest(Request $request, $id)
    {
        $request->validate([
            'duplicate_reason' => 'required|string|in:Card Lost,Card Damage,Card Extended',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'dup_doc' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
        ]);

        $row = SecurityFamilyIdApply::where('fml_id_apply', $id)->firstOrFail();
        $row->dup_reason = $request->input('duplicate_reason');
        if ($request->input('from_date')) {
            $row->card_valid_from = $request->input('from_date');
        }
        if ($request->input('to_date')) {
            $row->card_valid_to = $request->input('to_date');
        }
        if ($request->hasFile('dup_doc')) {
            $row->dup_doc = $request->file('dup_doc')->store('family_idcard/dup_docs', 'public');
        }
        $row->save();

        $membersUrl = route('admin.family_idcard.members', $id);
        return redirect($membersUrl)->with('success', 'Duplicate ID card request submitted successfully.');
    }

    public function export(Request $request)
    {
        $tab = $request->get('tab', 'active');
        $format = $request->get('format', 'xlsx');
        $search = $request->get('search', '');
        $cardType = $request->get('card_type', '');
        
        if (!in_array($tab, ['active', 'archive', 'all'])) {
            $tab = 'active';
        }
        $filename = 'family_idcard_requests_' . $tab . '_' . now()->format('Y-m-d_His');

        $query = match ($tab) {
            'archive' => SecurityFamilyIdApply::whereIn('id_status', [2, 3]),
            'all' => SecurityFamilyIdApply::query(),
            default => SecurityFamilyIdApply::where('id_status', 1),
        };
        
        // Filter by current user
        $query->where('created_by', Auth::user()->user_id);
        
        // Apply search filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('emp_id_apply', 'LIKE', "%{$search}%")
                  ->orWhere('family_name', 'LIKE', "%{$search}%")
                  ->orWhere('family_relation', 'LIKE', "%{$search}%");
            });
        }
        
        // Apply card type filter
        if (!empty($cardType)) {
            $query->where('card_type', $cardType);
        }
        
        $query->orderBy('created_date', 'desc');
        $rows = $query->get();
        $requests = $rows->map(fn ($r) => IdCardSecurityMapper::toFamilyRequestDto($r));

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.family_idcard.export_pdf', [
                'requests' => $requests,
                'tab' => $tab,
                'export_date' => now()->format('d/m/Y H:i'),
                'filters' => compact('search', 'cardType'),
            ])
                ->setPaper('a4', 'landscape')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true);
            return $pdf->download($filename . '.pdf');
        }

        return Excel::download(
            new FamilyIDCardExport($tab, true, $search, $cardType),
            $filename . ($format === 'csv' ? '.csv' : '.xlsx'),
            $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
        );
    }
}
