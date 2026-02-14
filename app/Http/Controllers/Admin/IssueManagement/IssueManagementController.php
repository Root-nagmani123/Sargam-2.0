<?php

namespace App\Http\Controllers\Admin\IssueManagement;

use App\Http\Controllers\Controller;
use App\Exports\IssueManagementExport;
use App\Models\{
    IssueLogManagement,
    IssueCategoryMaster,
    IssueSubCategoryMaster,
    IssuePriorityMaster,
    IssueReproducibilityMaster,
    IssueLogSubCategoryMap, 
    IssueLogBuildingMap,
    IssueLogHostelMap, 
    IssueLogStatus,
    BuildingMaster,
    HostelBuildingMaster,
    EmployeeMaster,
    User
};
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\{DB, Auth, Storage, Schema, Log};
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class IssueManagementController extends Controller
{
    /**
     * Display a listing of all issues.
     */
    public function index(Request $request)
    {
        // echo Auth::user()->user_id; exit;
        $query = IssueLogManagement::with([
            'category',
            'priority',
            'reproducibility',
            'subCategoryMappings.subCategory',
            'buildingMapping.building',
            'hostelMapping.hostelBuilding',
            'statusHistory'
        ]);

        $applyUserScope = function ($builder) {
            if (!hasRole('Admin') && !hasRole('SuperAdmin')) {
                $builder->where(function ($q) {
                    $q->where('employee_master_pk', Auth::user()->user_id)
                        ->orWhere('issue_logger', Auth::user()->user_id)
                        ->orWhere('assigned_to', Auth::user()->user_id)
                        ->orWhere('created_by', Auth::user()->user_id);
                });
            }
        };

        // Raised by: "all" = raised by himself or other employee, "self" = raised by himself only
        $applyRaisedBy = function ($builder) use ($request) {
            if ($request->get('raised_by') === 'self') {
                $builder->where('created_by', Auth::user()->user_id);
            }
        };

        $applyFilters = function ($builder) use ($request) {
            // Search (ID, description, category name, sub-category)
            if ($request->filled('search')) {
                $term = trim($request->search);
                $builder->where(function ($q) use ($term) {
                    if (is_numeric($term)) {
                        $q->orWhere('pk', $term);
                    }
                    $q->orWhere('description', 'like', "%{$term}%")
                        ->orWhereHas('category', function ($cq) use ($term) {
                            $cq->where('issue_category', 'like', "%{$term}%");
                        })
                        ->orWhereHas('subCategoryMappings.subCategory', function ($sq) use ($term) {
                            $sq->where('issue_sub_category', 'like', "%{$term}%");
                        });
                });
            }

            // Filter by category
            if ($request->has('category') && !empty($request->category)) {
                $builder->where('issue_category_master_pk', $request->category);
            }

            // Filter by priority
            if ($request->has('priority') && !empty($request->priority)) {
                $builder->where('issue_priority_master_pk', $request->priority);
            }

            // Filter by date range (use Carbon for consistent timezone handling)
            if ($request->filled('date_from')) {
                // Use full datetime so the day's range is applied correctly
                $from = Carbon::parse($request->date_from)->startOfDay()->toDateTimeString();
                $builder->where('created_date', '>=', $from);
            }
            if ($request->filled('date_to')) {
                // Use full datetime so the "to" date includes the entire day (23:59:59)
                $to = Carbon::parse($request->date_to)->endOfDay()->toDateTimeString();
                $builder->where('created_date', '<=', $to);
            }
        };

        $applyUserScope($query);
        $applyRaisedBy($query);
        $query->orderBy('created_date', 'desc');

        // Single list: all complaints. Status filter only when user selects from dropdown.
        if ($request->filled('status') && $request->status !== '') {
            $query->where('issue_status', (int) $request->status);
        }

        $applyFilters($query);

        $issues = $query->paginate(20);

        $categories = IssueCategoryMaster::active()->get();
        $priorities = IssuePriorityMaster::active()->ordered()->get();

        return view('admin.issue_management.index', compact('issues', 'categories', 'priorities'));
    }

    /**
     * Export issues to Excel.
     */
    public function exportExcel(Request $request)
    {
        $filters = [
            'search' => $request->get('search'),
            'status' => $request->get('status'),
            'category' => $request->get('category'),
            'priority' => $request->get('priority'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'raised_by' => $request->get('raised_by'),
        ];
        $fileName = 'issues-export-' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new IssueManagementExport($filters), $fileName);
    }

    /**
     * Export issues to PDF.
     */
    public function exportPdf(Request $request)
    {
        $filters = [
            'search' => $request->get('search'),
            'status' => $request->get('status'),
            'category' => $request->get('category'),
            'priority' => $request->get('priority'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'raised_by' => $request->get('raised_by'),
        ];
        $export = new IssueManagementExport($filters);
        $issues = $export->collection();
        $filterLabels = $this->getExportFilterLabels($filters);

        $data = [
            'issues' => $issues,
            'filters' => $filterLabels,
            'export_date' => now()->format('d-M-Y H:i'),
        ];

        $pdf = Pdf::loadView('admin.issue_management.export_pdf', $data)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont' => 'Arial',
                'isHtml5ParserEnabled' => true,
                'dpi' => 96,
            ]);

        return $pdf->download('issues-export-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Build filter labels for PDF header.
     */
    protected function getExportFilterLabels(array $filters): array
    {
        $labels = [];
        if (!empty($filters['search'])) {
            $labels['search'] = $filters['search'];
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $statusLabels = [0 => 'Reported', 1 => 'In Progress', 2 => 'Completed', 3 => 'Pending', 6 => 'Reopened'];
            $labels['status'] = $statusLabels[(int) $filters['status']] ?? $filters['status'];
        }
        if (!empty($filters['category'])) {
            $cat = IssueCategoryMaster::find($filters['category']);
            $labels['category'] = $cat ? $cat->issue_category : $filters['category'];
        }
        if (!empty($filters['priority'])) {
            $pri = IssuePriorityMaster::find($filters['priority']);
            $labels['priority'] = $pri ? $pri->priority : $filters['priority'];
        }
        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $labels['date_range'] = trim(($filters['date_from'] ?? '') . ' to ' . ($filters['date_to'] ?? ''));
        }
        return $labels;
    }

    /**
     * Display issues reported on behalf (CENTCOM).
     */
    public function centcom(Request $request)
    {
        $query = IssueLogManagement::with([
                'category',
                'priority',
                'reproducibility',
                'subCategoryMappings.subCategory',
                'buildingMapping.building',
                'hostelMapping.hostelBuilding',
                'statusHistory'
            ])->orderBy('created_date', 'desc');
            $query->where(function ($q) {
                $q->where('assigned_to', Auth::user()->user_id)
                    ->orWhere('employee_master_pk', Auth::user()->user_id)
                    ->orWhere('issue_logger', Auth::user()->user_id)
                    ->orWhere('created_by', Auth::user()->user_id);
            });

        // Search (ID, description, category name, sub-category)
        if ($request->filled('search')) {
            $term = trim($request->search);
            $query->where(function ($q) use ($term) {
                if (is_numeric($term)) {
                    $q->orWhere('pk', $term);
                }
                $q->orWhere('description', 'like', "%{$term}%")
                    ->orWhereHas('category', fn ($cq) => $cq->where('issue_category', 'like', "%{$term}%"))
                    ->orWhereHas('subCategoryMappings.subCategory', fn ($sq) => $sq->where('issue_sub_category', 'like', "%{$term}%"));
            });
        }

        // Status (use has + !== '' so "0" works)
        if ($request->has('status') && $request->status !== '') {
            $query->where('issue_status', (int) $request->status);
        }

        // Category
        if ($request->has('category') && $request->category !== '') {
            $query->where('issue_category_master_pk', (int) $request->category);
        }

        // Priority
        if ($request->has('priority') && $request->priority !== '') {
            $query->where('issue_priority_master_pk', (int) $request->priority);
        }

        // Date range (Carbon for consistent timezone)
        if ($request->filled('date_from')) {
            $from = Carbon::parse($request->date_from)->startOfDay()->toDateTimeString();
            $query->where('created_date', '>=', $from);
        }
        if ($request->filled('date_to')) {
            $to = Carbon::parse($request->date_to)->endOfDay()->toDateTimeString();
            $query->where('created_date', '<=', $to);
        }

        $issues = $query->paginate(20);
        // print_r($issues); exit;

        $categories = IssueCategoryMaster::active()->get();
        $priorities = IssuePriorityMaster::active()->ordered()->get();

        return view('admin.issue_management.centcom', compact('issues', 'categories', 'priorities'));
    }

    /**
     * Show the form for creating a new issue.
     */
    public function create()
    {
        $categories = IssueCategoryMaster::active()->get();
        $priorities = IssuePriorityMaster::active()->ordered()->get();
        $reproducibilities = IssueReproducibilityMaster::active()->get();
        
        // Make building/hostel queries conditional based on table existence
        $buildings = collect([]);
        $hostels = collect([]);
        $employees = collect([]);
        
        try {
            if (Schema::hasTable('building_master')) {
                $buildings = BuildingMaster::get();
            }
        } catch (\Exception $e) {
            \Log::warning('Building master table not accessible: ' . $e->getMessage());
        }
        
        try {
            if (Schema::hasTable('hostel_building_master')) {
                $hostels = HostelBuildingMaster::get();
            }
        } catch (\Exception $e) {
            \Log::warning('Hostel building master table not accessible: ' . $e->getMessage());
        }

        try {
            // Complaint section: employees + faculty only (user_credentials.user_category != 'S'), user_id = employee_master.pk
            $employees = User::getEmployeesAndFacultyForComplaint();
        } catch (\Exception $e) {
            \Log::warning('Employees/faculty for complaint not accessible: ' . $e->getMessage());
            $employees = collect([]);
        }

        $currentUserEmployeeId = Auth::user()->user_id ?? null;

        return view('admin.issue_management.create', compact(
            'categories',
            'priorities',
            'reproducibilities',
            'buildings',
            'hostels',
            'employees',
            'currentUserEmployeeId'
        ));
    }

    /**
     * Get nodal employees for a category (Level 1 only - selectable).
     * Level 2 & 3 returned for display only.
     */
    public function getNodalEmployees($categoryId)
    {
        try {
            $all = DB::table('issue_category_employee_map as b')
                ->join('employee_master as d', 'b.employee_master_pk', '=', 'd.pk')
                ->where('b.issue_category_master_pk', $categoryId)
                ->select(
                    'b.priority',
                    'b.days_notify',
                    'd.pk as employee_pk',
                    'd.first_name',
                    'd.middle_name',
                    'd.last_name',
                    DB::raw("TRIM(CONCAT(COALESCE(d.first_name, ''), ' ', COALESCE(d.middle_name, ''), ' ', COALESCE(d.last_name, ''))) as employee_name")
                )
                ->orderBy('b.priority', 'asc')
                ->get();

            $level1 = $all->where('priority', 1)->values();
            $level2 = $all->where('priority', 2)->first();
            $level3 = $all->where('priority', 3)->first();

            if ($level1->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No Level 1 nodal employees found for this category',
                    'data' => [],
                    'level1' => [],
                    'level1_auto_select' => null,
                    'level2' => null,
                    'level3' => null,
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Nodal employees fetched successfully',
                'data' => $level1->toArray(),
                'level1' => $level1->toArray(),
                'level1_auto_select' => $level1->first()->employee_pk,
                'level2' => $level2 ? ['employee_name' => $level2->employee_name, 'days_notify' => $level2->days_notify] : null,
                'level3' => $level3 ? ['employee_name' => $level3->employee_name, 'days_notify' => $level3->days_notify] : null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching nodal employees: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Store a newly created issue in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validate attachment files first (so unsupported files always show an error)
            $allowedExtensions = ['jpg', 'jpeg', 'png'];
            $maxSizeKb = 5120; // 5MB
            if ($request->hasFile('complaint_img_url')) {
                $files = $request->file('complaint_img_url');
                if (!is_array($files)) {
                    $files = [$files];
                }
                foreach ($files as $index => $file) {
                    if (!$file) {
                        continue;
                    }
                    if (!$file->isValid()) {
                        throw ValidationException::withMessages([
                            'complaint_img_url' => ['One or more file uploads failed. Please try again or use a different file.'],
                        ]);
                    }
                    $ext = strtolower($file->getClientOriginalExtension());
                    if (!in_array($ext, $allowedExtensions)) {
                        throw ValidationException::withMessages([
                            'complaint_img_url' => ['Unsupported file type. Only JPG and PNG images are allowed. File "' . $file->getClientOriginalName() . '" is not allowed.'],
                        ]);
                    }
                    if ($file->getSize() > $maxSizeKb * 1024) {
                        throw ValidationException::withMessages([
                            'complaint_img_url' => ['Each attachment must not exceed 5MB. File "' . $file->getClientOriginalName() . '" is too large.'],
                        ]);
                    }
                }
            }

            // Validate input data - same as ApiController
            $validated = $request->validate([
                'description' => 'required|string',
                'issue_category_id' => 'required|integer',
                'issue_sub_category_id' => 'required|integer',
                'issue_priority_id' => 'required|integer|exists:issue_priority_master,pk',
                'sub_category_name' => 'required|string',
                'created_by' => 'required|integer',
                'nodal_employee_id' => 'nullable|integer',
                'mobile_number' => 'nullable|string',
                'location' => 'required|string|in:H,R,O',
                'building_master_pk' => 'required|integer',
                'floor_id' => 'nullable',
                'room_name' => 'nullable|string',
                'complaint_img_url' => 'nullable|array',
                'complaint_img_url.*' => 'nullable|file|image|mimes:jpeg,jpg,png|max:5120',
            ], [
                'complaint_img_url.*.image' => 'Each attachment must be an image. Only JPG and PNG are allowed.',
                'complaint_img_url.*.mimes' => 'Each attachment must be a JPG or PNG file. Unsupported file type uploaded.',
                'complaint_img_url.*.max' => 'Each attachment must not exceed 5MB.',
            ]);

            $data = array(
                'issue_category_master_pk' => $request->issue_category_id,
                'issue_priority_master_pk' => $request->issue_priority_id,
                'location' => $request->location,
                'description' => $request->description,
                'created_by' => $request->created_by,
                'employee_master_pk' => $request->nodal_employee_id,
                'issue_logger' => Auth::user()->user_id ?? $request->created_by,
                'issue_status' => 0,
                'created_date' => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
            );

            // Handle complaint images - store in document column (JSON array of paths)
            $files = $request->file('complaint_img_url');
            if (!empty($files)) {
                $paths = [];
                $files = is_array($files) ? $files : [$files];
                foreach ($files as $image) {
                    if ($image && $image->isValid()) {
                        $path = $image->store('complaints_img', 'public');
                        if ($path) {
                            $paths[] = $path;
                        }
                    }
                }
                if (!empty($paths)) {
                    $data['document'] = count($paths) > 1 ? json_encode($paths) : $paths[0];
                }
            }

            $id = DB::table('issue_log_management')->insertGetId($data);

            // Insert sub-category mapping
            $issue_log_sub_category_map = array(
                'issue_log_management_pk' => $id,
                'issue_category_master_pk' => $request->issue_category_id,
                'issue_sub_category_master_pk' => $request->issue_sub_category_id,
                'sub_category_name' => $request->sub_category_name,
            );
            DB::table('issue_log_sub_category_map')->insert($issue_log_sub_category_map);

            // Insert location mapping based on location type (H=Hostel, R=Residential, O=Other)
            if ($request->location == 'H') {
                // Hostel location
                $hostel_data = array(
                    'issue_log_management_pk' => $id,
                    'hostel_building_master_pk' => $request->building_master_pk,
                    'floor_name' => $request->floor_id ?? '',
                    'room_name' => $request->room_name ?? '',
                );
                DB::table('issue_log_hostel_map')->insert($hostel_data);
            } elseif ($request->location == 'R') {
                // Residential location (uses same table as hostel)
                $residential_data = array(
                    'issue_log_management_pk' => $id,
                    'hostel_building_master_pk' => $request->building_master_pk,
                    'floor_name' => $request->floor_id ?? '',
                    'room_name' => $request->room_name ?? '',
                );
                DB::table('issue_log_hostel_map')->insert($residential_data);
            } elseif ($request->location == 'O') {
                // Other location
                $other_data = array(
                    'issue_log_management_pk' => $id,
                    'building_master_pk' => $request->building_master_pk,
                    'floor_name' => $request->floor_id ?? '',
                    'room_name' => $request->room_name ?? '',
                );
                DB::table('issue_log_building_map')->insert($other_data);
            }

            // Insert status history (Note: table name is case sensitive as per ApiController)
            $status_data = array(
                'issue_log_management_pk' => $id,
                'issue_status' => 0,
                'issue_date' => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                'created_by' => $request->created_by,
            );
            DB::table('Issue_log_status')->insert($status_data);

            return redirect()->route('admin.issue-management.show', $id)
                ->with('success', 'Complaint submitted successfully!');

        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->validator);
        } catch (\Exception $e) {
            \Log::error('Store complaint error: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'Error submitting complaint: ' . $e->getMessage());
        }
    }
            
    /**
     * Display the specified issue.
     */
    public function show($id)
    {
        $issue = IssueLogManagement::with([
            'category',
            'priority',
            'reproducibility',
            'subCategoryMappings.subCategory',
            'buildingMapping.building',
            'hostelMapping.hostelBuilding',
            'statusHistory.creator',
            'nodal_officer',
            // 'escalationHistory',
            'creator',
            'logger'
        ])->findOrFail($id);

        if (!hasRole('Admin') && !hasRole('SuperAdmin')) {
            $userId = Auth::user()->user_id;
            $canView = $issue->created_by == $userId || $issue->issue_logger == $userId
                || $issue->employee_master_pk == $userId || $issue->assigned_to == $userId;
            if (!$canView) {
                return redirect()->route('admin.issue-management.index')
                    ->with('error', 'You do not have access to view this issue.');
            }
        }

        $department_id = $issue->nodal_officer?->department_master_pk ?? null;
        // Complaint section: employees + faculty only (user_credentials.user_category != 'S'), optional department filter
        $employees = User::getEmployeesAndFacultyForComplaint($department_id);

        return view('admin.issue_management.show', compact('issue', 'employees'));
    }

    /**
     * Show the form for editing the specified issue.
     */
    public function edit($id)
    {
        $issue = IssueLogManagement::with([
            'category',
            'subCategoryMappings.subCategory',
            'buildingMapping',
            'hostelMapping',
            'creator' 
        ])->findOrFail($id);
        
        // Allow complainant (created_by) OR logger (issue_logger) to edit
        if($issue->issue_logger != Auth::user()->user_id && $issue->created_by != Auth::user()->user_id){
            return redirect()->route('admin.issue-management.show', $issue->pk)
                ->with('error', 'You can only edit issues you created or logged on behalf.');
        }
        
        // print_r($issue->toArray()); exit;

        $categories = IssueCategoryMaster::active()->get();
        $priorities = IssuePriorityMaster::active()->ordered()->get();
        $reproducibilities = IssueReproducibilityMaster::active()->get();
        
        // Complaint section: employees + faculty only (user_credentials.user_category != 'S')
        $employees = User::getEmployeesAndFacultyForComplaint();
        
        // Determine current building, floor, and room
        $currentBuilding = null;
        $currentFloor = null;
        $currentRoom = null;
        
        if ($issue->location == 'H' && $issue->hostelMapping) {
            $currentBuilding = $issue->hostelMapping->hostel_building_master_pk;
            $currentFloor = $issue->hostelMapping->floor_name;
            $currentRoom = $issue->hostelMapping->room_name;
        } elseif ($issue->location == 'R' && $issue->hostelMapping) {
            $currentBuilding = $issue->hostelMapping->hostel_building_master_pk;
            $currentFloor = $issue->hostelMapping->floor_name;
            $currentRoom = $issue->hostelMapping->room_name;
        } elseif ($issue->location == 'O' && $issue->buildingMapping) {
            $currentBuilding = $issue->buildingMapping->building_master_pk;
            $currentFloor = $issue->buildingMapping->floor_name;
            $currentRoom = $issue->buildingMapping->room_name;
        }
        
        // Make building/hostel queries conditional
        $buildings = collect([]);
        $hostels = collect([]);
        
        try {
            if (Schema::hasTable('building_master')) {
                $buildings = BuildingMaster::get();
            }
        } catch (\Exception $e) {
            \Log::warning('Building master table not accessible: ' . $e->getMessage());
        }
        
        try {
            if (Schema::hasTable('hostel_building_master')) {
                $hostels = HostelBuildingMaster::get();
            }
        } catch (\Exception $e) {
            \Log::warning('Hostel building master table not accessible: ' . $e->getMessage());
        }

        return view('admin.issue_management.edit', compact(
            'issue',
            'categories',
            'priorities',
            'reproducibilities',
            'buildings',
            'hostels',
            'employees',
            'currentBuilding',
            'currentFloor',
            'currentRoom'
        ));
    }

    /**
     * Update the specified issue in storage.
     */
    public function update(Request $request, $id)
    {
        $issue = IssueLogManagement::findOrFail($id);
      
        
        // Allow complainant (created_by) OR logger (issue_logger) to update
        if($issue->issue_logger != Auth::user()->user_id && $issue->created_by != Auth::user()->user_id){
            return redirect()->route('admin.issue-management.show', $issue->pk)
                ->with('error', 'You can only edit issues you created or logged on behalf.');
        }

        $request->validate([
            'issue_category_id' => 'required|integer|exists:issue_category_master,pk',
            'issue_sub_category_id' => 'required|integer|exists:issue_sub_category_master,pk',
            'issue_priority_id' => 'required|integer|exists:issue_priority_master,pk',
            'created_by' => 'required|integer|exists:employee_master,pk',
            'mobile_number' => 'nullable|string',
            'nodal_employee_id' => 'required|integer|exists:employee_master,pk',
            'location' => 'required|in:H,R,O',
            // Building details can be empty for legacy/partial records
            'building_select' => 'nullable|integer',
            // Some legacy records store floor as a label/string; allow either
            'floor_select' => 'nullable',
            'room_select' => 'nullable|string',
            'description' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            // Update main issue record
            $issue->update([
                'issue_category_master_pk' => $request->issue_category_id,
                'issue_priority_master_pk' => $request->issue_priority_id,
                'created_by' => $request->created_by,
                'location' => $request->location,
                'description' => $request->description,
                'employee_master_pk' => $request->nodal_employee_id,
                'updated_by' => Auth::id(),
                'updated_date' => now(),
            ]);

            // Update sub-category mapping
            IssueLogSubCategoryMap::where('issue_log_management_pk', $issue->pk)->delete();
            IssueLogSubCategoryMap::create([
                'issue_log_management_pk' => $issue->pk,
                'issue_category_master_pk' => $request->issue_category_id,
                'issue_sub_category_master_pk' => $request->issue_sub_category_id,
                'sub_category_name' => $request->input('sub_category_name', ''),
            ]);

            // Update building/floor/room mapping based on location
            // If building is not provided, clear mappings and keep update successful.
            $buildingPk = $request->input('building_select');
            $floorVal = $request->input('floor_select', '');
            $roomVal = $request->input('room_select', '');

            if (empty($buildingPk)) {
                IssueLogHostelMap::where('issue_log_management_pk', $issue->pk)->delete();
                IssueLogBuildingMap::where('issue_log_management_pk', $issue->pk)->delete();
            } elseif ($request->location == 'H' || $request->location == 'R') {
                // Hostel / Residential location (same mapping table)
                IssueLogHostelMap::where('issue_log_management_pk', $issue->pk)->delete();
                IssueLogHostelMap::create([
                    'issue_log_management_pk' => $issue->pk,
                    'hostel_building_master_pk' => (int) $buildingPk,
                    'floor_name' => $floorVal ?: '',
                    'room_name' => $roomVal ?: '',
                ]);
                IssueLogBuildingMap::where('issue_log_management_pk', $issue->pk)->delete();
            } elseif ($request->location == 'O') {
                // Other (Office building) location
                IssueLogBuildingMap::where('issue_log_management_pk', $issue->pk)->delete();
                IssueLogBuildingMap::create([
                    'issue_log_management_pk' => $issue->pk,
                    'building_master_pk' => (int) $buildingPk,
                    'floor_name' => $floorVal ?: '',
                    'room_name' => $roomVal ?: '',
                ]);
                IssueLogHostelMap::where('issue_log_management_pk', $issue->pk)->delete();
            }

            DB::commit();

            $showUrl = route('admin.issue-management.show', $issue->pk);
            if ($request->filled('from_modal')) {
                session()->flash('success', 'Issue updated successfully.');
                return response()->view('admin.issue_management.close_modal_redirect', ['url' => $showUrl]);
            }
            return redirect()->to($showUrl)->with('success', 'Issue updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()
                ->with('error', 'Failed to update issue: ' . $e->getMessage());
        }
    }

    /**
     * Get sub-categories based on category (AJAX).
     */
    public function getSubCategories($categoryId)
    {
        $subCategories = IssueSubCategoryMaster::byCategory($categoryId)
            ->active()
            ->orderBy('issue_sub_category')
            ->get();

        return response()->json($subCategories);
    }

    /**
     * Get buildings based on location type (AJAX).
     */
    public function getBuildings(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|string|in:H,R,O',
            ]);

            $type = $request->type;
            $data = [];

            switch ($type) {
                case 'H':
                    $data = $this->getHostelBuildings();
                    break;
                case 'R':
                    $data = $this->getResidentialBuildings();
                    break;
                case 'O':
                    $data = $this->getOtherBuildings();
                    break;
            }

            return response()->json([
                'status' => true,
                'message' => 'Buildings retrieved successfully',
                'data' => $data
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'data' => []
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get hostel buildings.
     */
    protected function getHostelBuildings()
    {
        return DB::table('hostel_building_master')
            ->select('pk', 'building_name')
            ->get();
    }

    /**
     * Get residential blocks.
     */
    protected function getResidentialBuildings()
    {
        return DB::table('estate_block_master')
            ->select('pk', 'block_name as building_name')
            ->get();
    }

    /**
     * Get other buildings.
     */
    protected function getOtherBuildings()
    {
        return DB::table('building_master')
            ->select('pk', 'building_name')
            ->get();
    }

    /**
     * Get floors based on building and location type (AJAX).
     */
    public function getFloors(Request $request)
    {
        try {
            $request->validate([
                'building_id' => 'required|integer',
                'type' => 'required|string|in:H,R,O',
            ]);

            $buildingId = $request->building_id;
            $type = $request->type;

            // Convert H/R/O to hostel/residential/other for API call
            $typeMap = [
                'H' => 'hostel',
                'R' => 'residential',
                'O' => 'other'
            ];
            $apiType = $typeMap[$type];

            $data = [];
            switch ($apiType) {
                case 'hostel':
                    $data = $this->getHostelFloors($buildingId);
                    break;
                case 'residential':
                    $data = $this->getResidentialFloors($buildingId);
                    break;
                case 'other':
                    $data = $this->getOtherFloors($buildingId);
                    break;
            }

            return response()->json([
                'status' => true,
                'message' => 'Floors retrieved successfully',
                'data' => $data
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'data' => []
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get hostel floors (from ApiController logic).
     */
    protected function getHostelFloors($buildingId)
    {
        return DB::table('hostel_building_floor_map as f')
            ->where('f.hostel_building_master_pk', $buildingId)
            ->select('f.pk as floor_id', 'f.floor_name as floor')
            ->get();
    }

    /**
     * Get residential floors (from ApiController logic).
     */
    protected function getResidentialFloors($blockId)
    {
        return DB::table('estate_block_master as h')
            ->join('estate_house_master as j', 'h.pk', '=', 'j.estate_block_master_pk')
            ->leftJoin('estate_unit_sub_type_master as i', 'j.estate_unit_sub_type_master_pk', '=', 'i.pk')
            ->where('h.pk', $blockId)
            ->select('h.pk as block_id', 'h.block_name', 'i.unit_sub_type as floor', 'j.estate_unit_sub_type_master_pk')
            ->distinct()
            ->orderBy('h.block_name')
            ->get();
    }

    /**
     * Get other floors (from ApiController logic).
     */
    protected function getOtherFloors($buildingId)
    {
        return DB::table('building_master as k')
            ->join('building_room_master as l', 'k.pk', '=', 'l.building_master_pk')
            ->where('k.pk', $buildingId)
            ->select(
                'k.building_name as floor',
                'l.floor',
                DB::raw("GROUP_CONCAT(DISTINCT l.room_no ORDER BY l.room_no SEPARATOR ', ') as room_numbers")
            )
            ->groupBy('k.building_name', 'l.floor')
            ->orderBy('k.building_name')
            ->get();
    }

    /**
     * Get rooms based on building, floor and location type (AJAX).
     */
    public function getRooms(Request $request)
    {
        try {
            $request->validate([
                'building_id' => 'required|integer',
                'floor_id' => 'required|integer',
                'type' => 'required|string|in:H,R,O',
            ]);

            $buildingId = $request->building_id;
            $floorId = $request->floor_id;
            $type = $request->type;

            // Convert H/R/O to hostel/residential/other
            $typeMap = [
                'H' => 'hostel',
                'R' => 'residential',
                'O' => 'other'
            ];
            $apiType = $typeMap[$type];

            $result = collect();

            switch ($apiType) {
                case 'hostel':
                    $result = DB::table('hostel_building_master as e')
                        ->join('hostel_building_floor_map as f', 'e.pk', '=', 'f.hostel_building_master_pk')
                        ->join('hostel_floor_room_map as g', 'f.pk', '=', 'g.hostel_building_floor_map_pk')
                        ->where('e.pk', $buildingId)
                        ->where('f.pk', $floorId)
                        ->select(
                            'e.building_name',
                            'f.floor_name',
                            'g.room_name',
                            'g.pk',
                            'g.room_capacity',
                            'g.facilities',
                            'g.fees',
                            'g.sub_unit_type_master_pk',
                            'g.room_type'
                        )
                        ->get();
                    break;

                case 'other':
                    $result = DB::table('building_master as k')
                        ->join('building_room_master as l', 'k.pk', '=', 'l.building_master_pk')
                        ->where('k.pk', $buildingId)
                        ->where('l.floor', $floorId)
                        ->select(
                            'k.building_name',
                            'l.floor as floor_name',
                            'l.room_no as room_name',
                            'l.pk',
                            'l.room_capacity',
                            'l.facility',
                            'l.fee_per_bed'
                        )
                        ->distinct()
                        ->get();
                    break;

                case 'residential':
                    $result = DB::table('estate_house_master as j')
                        ->join('estate_block_master as h', 'j.estate_block_master_pk', '=', 'h.pk')
                        ->where('h.pk', $buildingId)
                        ->where('j.estate_unit_sub_type_master_pk', $floorId)
                        ->select(
                            'h.block_name',
                            'j.house_no',
                            'j.pk',
                            'j.licence_fee',
                            'j.water_charge',
                            'j.electric_charge'
                        )
                        ->get();
                    break;

                default:
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid type specified!',
                        'data' => []
                    ], 400);
            }

            if ($result->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No data found!',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'status' => true,
                'message' => 'Rooms retrieved successfully',
                'data' => $result
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'data' => []
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Update issue status.
     */
    public function status_update(Request $request, $id)
    {
        $issue = IssueLogManagement::findOrFail($id);
        $userId = Auth::user()->user_id ?? null;

        $isNodalOrAssigned = ($issue->employee_master_pk == $userId) || ($issue->assigned_to == $userId);
        $isComplainant = ($issue->created_by == $userId);
        $isCompleted = (int) $issue->issue_status === 2;
        $requestedStatus = (int) $request->issue_status;

        // Complainant can only reopen (status 2 -> 6); nodal/assigned can update status as before
        if ($isComplainant && !$isNodalOrAssigned) {
            if (!$isCompleted || $requestedStatus !== 6) {
                return redirect()->route('admin.issue-management.show', $issue->pk)
                    ->with('error', 'As the complainant, you can only reopen this issue when it is completed.');
            }
        } elseif (!$isNodalOrAssigned && !$isComplainant) {
            return redirect()->back()->with('error', 'You are not allowed to update this issue status.');
        }

        $isAssigned = !empty($issue->assigned_to);

        $rules = [
            'issue_status' => 'required|in:0,1,2,3,6',
            'remark' => 'nullable|string|max:500',
        ];
        if (!$isAssigned) {
            $rules['assign_to_type'] = 'required';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $assignedTo = null;
            $assignedToContact = null;

            // Handle assignment based on type (only if not already assigned)
            if (!$isAssigned) {
                if ($request->assign_to_type === 'other') {
                    // Validate other fields if "other" is selected
                    $request->validate([
                        'other_name' => 'required|string|max:255',
                        'other_phone' => 'required|string|max:10',
                    ]);
                    
                    $assignedTo = $request->other_name;
                    $assignedToContact = $request->other_phone;
                } else {
                    // Employee selected - use hidden fields
                    $assignedTo = $request->assigned_to;
                    $assignedToContact = $request->assigned_to_contact;
                }
            } else {
                // Keep existing assignment
                $assignedTo = $issue->assigned_to;
                $assignedToContact = $issue->assigned_to_contact;
            }

            // Update the main issue record
            $updateData = [
                'issue_status' => $request->issue_status,
                'updated_by' => Auth::user()->user_id,
                'updated_date' => now(),
            ];
            
            // Add remark if provided
            if ($request->remark) {
                $updateData['remark'] = $request->remark;
            }
            
            // Update assignment only if not already assigned
            if (!$isAssigned) {
                $updateData['assigned_to'] = $assignedTo;
                $updateData['assigned_to_contact'] = $assignedToContact;
            }
            
            $issue->update($updateData);

            // Create status history record
            IssueLogStatus::create([
                'issue_log_management_pk' => $issue->pk,
                'issue_date' => now(),
                'created_by' => Auth::user()->user_id,
                'issue_status' => $request->issue_status,
                'remarks' => $request->remark,
                'assign_to' => $assignedTo,
            ]);

            DB::commit();

            return redirect()->route('admin.issue-management.show', $issue->pk)
                ->with('success', 'Issue status updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to update status: ' . $e->getMessage());
        }
    }

    /**
     * Add feedback to an issue.
     */
    public function addFeedback(Request $request, $id)
    {
        $request->validate([
            'feedback' => 'required|string',
        ]);

        $issue = IssueLogManagement::findOrFail($id);

        $issue->update([
            'feedback' => $request->feedback,
            'feedback_status' => 1,
            'updated_by' => Auth::id(),
            'updated_date' => now(),
        ]);

        return back()->with('success', 'Feedback added successfully.');
    }
}
