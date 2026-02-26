<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\FamilyIDCardExport;
use App\Models\EmployeeMaster;
use App\Models\SecurityFamilyIdApply;
use App\Support\IdCardSecurityMapper;
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
        $authUserId = Auth::user()->user_id ?? null;
        if ($authUserId) {
            $authEmp = EmployeeMaster::with('department')
                ->where('pk', $authUserId)
                ->orWhere('pk_old', $authUserId)
                ->first();
            if ($authEmp) {
                $defaultApprovalAuthorityPk = $authEmp->pk;
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
            }
        }

        return view('admin.family_idcard.create', [
            'userDepartmentName' => $userDepartmentName,
            'approvalAuthorityEmployees' => $approvalAuthorityEmployees,
            'defaultApprovalAuthorityPk' => $defaultApprovalAuthorityPk,
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'employee_id' => 'required|string|max:100',
            'designation' => 'required|string|max:255',
            'card_type' => 'required|string|max:100',
            'section' => 'required|string|max:255',
            'group_photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'members' => 'required|array|min:1',
            'members.*.name' => 'required|string|max:255',
            'members.*.relation' => 'nullable|string|max:100',
            'members.*.family_photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'members.*.dob' => 'nullable|date',
            'members.*.valid_from' => 'nullable|date',
            'members.*.valid_to' => 'nullable|date',
        ];
        
        $request->validate($rules);

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
        $approvalAuthorityPk = $request->input('employee_type') === 'Contractual Employee'
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
        return view('admin.family_idcard.show', ['request' => $request]);
    }

    public function edit($id)
    {
        $row = SecurityFamilyIdApply::where('fml_id_apply', $id)->firstOrFail();
        $request = IdCardSecurityMapper::toFamilyRequestDto($row);
        $existingFamilyMembers = SecurityFamilyIdApply::where('created_by', Auth::user()->user_id)
            ->where('fml_id_apply', '!=', $id)->orderBy('created_date', 'desc')->limit(50)->get()
            ->map(fn ($r) => IdCardSecurityMapper::toFamilyRequestDto($r));
        return view('admin.family_idcard.edit', [
            'request' => $request,
            'existingFamilyMembers' => $existingFamilyMembers,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string|max:100',
            'designation' => 'required|string|max:255',
            'card_type' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'relation' => 'nullable|string|max:100',
            'section' => 'required|string|max:255',
            'family_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'dob' => 'nullable|date',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date',
            'family_member_id' => 'nullable|string|max:100',
            'status' => 'nullable|in:Pending,Approved,Rejected,Issued',
            'remarks' => 'nullable|string',
        ]);

        $row = SecurityFamilyIdApply::where('fml_id_apply', $id)->firstOrFail();
        $row->family_name = $validated['name'];
        $row->family_relation = $validated['relation'] ?? null;
        $row->emp_id_apply = $validated['employee_id'];
        $row->card_valid_from = $validated['valid_from'] ?? null;
        $row->card_valid_to = $validated['valid_to'] ?? null;
        $row->employee_dob = $validated['dob'] ?? null;
        $row->remarks = $validated['remarks'] ?? null;
        $row->id_card_no = $validated['family_member_id'] ?? null;
        if ($request->hasFile('family_photo')) {
            $row->family_photo = $request->file('family_photo')->store('family_idcard/photos', 'public');
            $row->id_photo_path = $row->family_photo;
        }
        $row->save();

        return redirect()->route('admin.family_idcard.show', $row->fml_id_apply)
            ->with('success', 'Family ID Card request updated successfully!');
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
        // Restore an approved / rejected Family ID card request back to Active list
        $row = SecurityFamilyIdApply::where('fml_id_apply', $id)->first();
        if ($row) {
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
