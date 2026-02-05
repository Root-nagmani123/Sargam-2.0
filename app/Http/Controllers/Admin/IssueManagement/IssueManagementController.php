<?php

namespace App\Http\Controllers\Admin\IssueManagement;

use App\Http\Controllers\Controller;
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
    EmployeeMaster
};
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\{DB, Auth, Storage, Schema, Log};
use Carbon\Carbon;

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
       
         if(hasRole('Admin')) {  }else{
$query->where('employee_master_pk', Auth::user()->user_id);
 $query->orWhere('issue_logger', Auth::user()->user_id); 
 $query->orWhere('assigned_to', Auth::user()->user_id); 
        }
        $query->orderBy('created_date', 'desc');

        // Active vs Archive tab: Active = non-completed (0,1,3,6), Archive = completed (2)
        $tab = $request->get('tab', 'active');
        if ($tab === 'archive') {
            $query->where('issue_status', 2); // Completed only
        } else {
            $query->whereIn('issue_status', [0, 1, 3, 6]); // Reported, In Progress, Pending, Reopened
        }

        // Filter by status (further refines within the tab)
        if ($request->has('status') && $request->status !== '') {
            $query->where('issue_status', $request->status);
        }

        // Filter by category
        if ($request->has('category') && !empty($request->category)) {
            $query->where('issue_category_master_pk', $request->category);
        }

        // Filter by priority
        if ($request->has('priority') && !empty($request->priority)) {
            $query->where('issue_priority_master_pk', $request->priority);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_date', '<=', $request->date_to);
        }

        $issues = $query->paginate(20);
        // print_r($issues); exit;

        $categories = IssueCategoryMaster::active()->get();
        $priorities = IssuePriorityMaster::active()->ordered()->get();

        $baseQuery = IssueLogManagement::query();
        $activeCount = (clone $baseQuery)->whereIn('issue_status', [0, 1, 3, 6]);
        if(! hasRole('Admin') && ! hasRole('SuperAdmin')) {

            $activeCount->where('employee_master_pk', Auth::user()->user_id); 
        }
            $activeCount->orWhere('issue_logger', Auth::user()->user_id); 
            $activeCount->orWhere('assigned_to', Auth::user()->user_id); 

        $activeCount = $activeCount->count();
        $archiveCount = (clone $baseQuery)->where('issue_status', 2);
        if(! hasRole('Admin') && ! hasRole('SuperAdmin')) {
        
            $archiveCount->where('employee_master_pk', Auth::user()->user_id); 
        }
            $archiveCount->orWhere('issue_logger', Auth::user()->user_id); 
            $archiveCount->orWhere('assigned_to', Auth::user()->user_id);
        $archiveCount = $archiveCount->count();

        return view('admin.issue_management.index', compact('issues', 'categories', 'priorities', 'tab', 'activeCount', 'archiveCount'));
    }

    /**
     * Display issues reported on behalf (CENTCOM).
     */
    public function centcom(Request $request)
    {
        $query = IssueLogManagement::reportedOnBehalf()
            ->with([
                'category',
                'priority',
                'reproducibility',
                'subCategoryMappings.subCategory',
                'buildingMapping.building',
                'hostelMapping.hostelBuilding',
                'statusHistory'
            ])->orderBy('created_date', 'desc');

        // Apply filters similar to index
        if ($request->has('status') && $request->status !== '') {
            $query->where('issue_status', $request->status);
        }

        if ($request->has('category') && $request->category !== '') {
            $query->where('issue_category_master_pk', $request->category);
        }

        $issues = $query->paginate(20);

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
          
               $query = DB::table('employee_master as e')
        ->leftJoin('designation_master as d', 'e.designation_master_pk', '=', 'd.pk')
        ->select(
            'e.pk as employee_pk',
            DB::raw("TRIM(CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name)) as employee_name"),
            DB::raw("COALESCE(e.mobile, '') as mobile"),  // Treat null and empty as the same
            // Treat null and empty as the same
            'd.designation_name'
        )
        ->orderBy('first_name')
        ->groupBy('e.pk', 'e.first_name', 'e.middle_name', 'e.last_name', 'e.mobile', 'd.designation_name');

    // If 'emp_id' is provided, filter the query for that specific employee
    // if ($employeePk) {
    //     $query->where('e.pk', $employeePk);
    // }

    // Execute the query and get the result
    $employees = $query->get();
            
        } catch (\Exception $e) {
            \Log::warning('Employee master table not accessible: ' . $e->getMessage());
        }

        return view('admin.issue_management.create', compact(
            'categories',
            'priorities',
            'reproducibilities',
            'buildings',
            'hostels',
            'employees'
        ));
    }

    /**
     * Get nodal employees for a category
     */
    public function getNodalEmployees($categoryId)
    {
        try {
            $employees = DB::table('issue_category_master as a')
                ->join('issue_category_employee_map as b', 'a.pk', '=', 'b.issue_category_master_pk')
                ->join('employee_master as d', 'b.employee_master_pk', '=', 'd.pk_old')
                ->where('a.pk', $categoryId)
                ->select(
                    'a.issue_category',
                    'b.priority',
                    'd.pk as employee_pk',
                    'd.first_name',
                    'd.middle_name',
                    'd.last_name',
                    DB::raw("TRIM(CONCAT(d.first_name, ' ', COALESCE(d.middle_name, ''), ' ', d.last_name)) as employee_name")
                )
                ->orderBy('priority', 'asc')
                ->get();

            if ($employees->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No nodal employees found for this category',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Nodal employees fetched successfully',
                'data' => $employees
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
            // Validate input data - same as ApiController
            $validated = $request->validate([
                'description' => 'required|string',
                'issue_category_id' => 'required|integer',
                'issue_sub_category_id' => 'required|integer',
                'sub_category_name' => 'required|string',
                'created_by' => 'required|integer',
                'nodal_employee_id' => 'nullable|integer',
                'mobile_number' => 'nullable|string',
                'location' => 'required|string|in:H,R,O',
                'building_master_pk' => 'required|integer',
                'floor_id' => 'nullable',
                'room_name' => 'nullable|string',
                'complaint_img_url.*' => 'nullable',
            ]);

            $data = array(
                'issue_category_master_pk' => $request->issue_category_id,
                'location' => $request->location,
                'description' => $request->description,
                'created_by' => $request->created_by,
                'employee_master_pk' => $request->nodal_employee_id,
                'issue_logger' => Auth::user()->user_id ?? $request->created_by,
                'issue_status' => 0,
                'created_date' => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
            );

            // Handle complaint images
            if ($request->hasFile('complaint_img_url')) {
                $images = [];
                foreach ($request->file('complaint_img_url') as $image) {
                    $path = $image->store('complaints_img', 'public');
                    $images[] = asset('storage/' . $path);
                }
                $data['complaint_img'] = json_encode($images);
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
            // 'escalationHistory',
            'creator',
            'logger'
        ])->findOrFail($id);

        // Load all employees for assignment dropdown in modal
        $query = DB::table('employee_master as e')
            ->select(
                'e.pk as employee_pk',
                'e.first_name as first_name',
                'e.last_name as last_name',
                // DB::raw("CONCAT(e.first_name, ' ', e.last_name) as employee_name"),
            DB::raw("TRIM(CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name)) as employee_name"),

                'e.mobile'
            )
            // ->where('e.first_name', '!=', null)
            ->orderBy('first_name');
        
        $employees = $query->get();
        // print_r($employees); exit;

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
        
        // Check if logged-in user is the creator of this issue
        if ($issue->created_by != Auth::user()->user_id) {
            return redirect()->route('admin.issue-management.show', $issue->pk)
                ->with('error', 'You can only edit issues you created.');
        }
        
        // print_r($issue->toArray()); exit;

        $categories = IssueCategoryMaster::active()->get();
        $priorities = IssuePriorityMaster::active()->ordered()->get();
        $reproducibilities = IssueReproducibilityMaster::active()->get();
        
        // Load all employees for complainant dropdown
        $query = DB::table('employee_master as e')
            ->select(
                'e.pk as employee_pk',
                DB::raw("CONCAT(e.first_name, ' ', e.last_name) as employee_name"),
                'e.mobile'
            )
            ->orderBy('employee_name');
        
        $employees = $query->get();
        
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
        
        // Check if logged-in user is the creator of this issue
        if ($issue->created_by != Auth::id()) {
            return redirect()->route('admin.issue-management.show', $issue->pk)
                ->with('error', 'You can only edit issues you created.');
        }

        $request->validate([
            'issue_category_id' => 'required|integer|exists:issue_category_master,pk',
            'issue_sub_category_id' => 'required|integer|exists:issue_sub_category_master,pk',
            'created_by' => 'required|integer|exists:employee_master,pk',
            'mobile_number' => 'nullable|string',
            'nodal_employee_id' => 'required|integer|exists:employee_master,pk',
            'location' => 'required|in:H,R,O',
            'building_select' => 'required|integer',
            'floor_select' => 'required|integer',
            'description' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            // Update main issue record
            $issue->update([
                'issue_category_master_pk' => $request->issue_category_id,
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
                'issue_sub_category_master_pk' => $request->issue_sub_category_id,
                'sub_category_name' => $request->input('sub_category_name', ''),
            ]);

            // Update building/floor/room mapping based on location
            if ($request->location == 'H') {
                // Hostel location
                IssueLogHostelMap::where('issue_log_management_pk', $issue->pk)->delete();
                IssueLogHostelMap::create([
                    'issue_log_management_pk' => $issue->pk,
                    'hostel_building_master_pk' => $request->building_select,
                    'floor_name' => $request->floor_select,
                    'room_name' => $request->input('room_select', ''),
                ]);
                IssueLogBuildingMap::where('issue_log_management_pk', $issue->pk)->delete();
            } elseif ($request->location == 'R') {
                // Residential location
                IssueLogHostelMap::where('issue_log_management_pk', $issue->pk)->delete();
                IssueLogHostelMap::create([
                    'issue_log_management_pk' => $issue->pk,
                    'hostel_building_master_pk' => $request->building_select,
                    'floor_name' => $request->floor_select,
                    'room_name' => $request->input('room_select', ''),
                ]);
                IssueLogBuildingMap::where('issue_log_management_pk', $issue->pk)->delete();
            } elseif ($request->location == 'O') {
                // Other (Office building) location
                IssueLogBuildingMap::where('issue_log_management_pk', $issue->pk)->delete();
                IssueLogBuildingMap::create([
                    'issue_log_management_pk' => $issue->pk,
                    'building_master_pk' => $request->building_select,
                    'floor_name' => $request->floor_select,
                    'room_name' => $request->input('room_select', ''),
                ]);
                IssueLogHostelMap::where('issue_log_management_pk', $issue->pk)->delete();
            }

            DB::commit();

            return redirect()->route('admin.issue-management.show', $issue->pk)
                ->with('success', 'Issue updated successfully.');
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
        
        // Check if issue is already assigned
        $isAssigned = !empty($issue->assigned_to);

        // Validate request - assign_to_type only required if not already assigned
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
