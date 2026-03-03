<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;  
use App\Models\User;
use App\Models\JbpUser;
use Illuminate\Validation\ValidationException;
use PDF;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ApiController extends Controller
{

        public function getCategoryEmployee(Request $request)
        {
            try {
            // Validate the request to ensure a category ID is provided
            $request->validate([ 
                'category_id' => 'required|integer|exists:issue_category_master,pk',
            ]);
    
            // Retrieve the category ID from the request
            $categoryId = $request->input('category_id');
    
            // Query to fetch all employees with their names and IDs for the specified category
            $categoryEmployees = DB::table('issue_category_master as a')
                ->join('issue_category_employee_map as b', 'a.pk', '=', 'b.issue_category_master_pk')
                ->join('employee_master as d', 'b.employee_master_pk', '=', 'd.pk')
                ->where('a.pk', $categoryId)
                ->select('a.issue_category', 'b.priority','d.pk as employee_id', 'd.first_name', 'd.middle_name', 'd.last_name')
                ->orderBy('priority', 'asc')
                ->get();
    
            // Check if data is retrieved
            if ($categoryEmployees->isEmpty()) {
                // No data found
                return response()->json([
                    'status' => false,
                    'message' => 'No employees found for the specified category.',
                    'data' => [],
                ], 200); // 200 Not Found
            }
    
            // Return the data as a JSON response
            return response()->json([
                'status' => true,
                'message' => 'Employees retrieved successfully!',
                'data' => $categoryEmployees,
            ], 200); // 200 OK
        
    } catch (ValidationException $e) {
        // Handle validation exception and redirect with error message
 
            return response()->json([
                'status' => false,
                'message' => 'Invalid category ID. Please try again with a valid ID.',
                'data' => [],
            ], 200); // 200 Not Found
    }
}
public function getSubCategories(Request $request)
{
    try {
        // Validate the request to ensure a category ID is provided
        $request->validate([
            'category_id' => 'required|integer|exists:issue_category_master,pk',
        ]);

        // Retrieve the category ID from the request
        $categoryId = $request->input('category_id');

        // Query to fetch all sub-categories for the specified category
        $subCategories = DB::table('issue_sub_category_master as c')
        ->join('issue_category_master as a', 'c.issue_category_master_pk', '=', 'a.pk')
        ->where('a.pk', $categoryId)
        ->select('c.pk as sub_category_id', 'c.issue_sub_category as sub_category_name')
        ->get();

        // Check if data is retrieved
        if ($subCategories->isEmpty()) {
            // No data found
            return response()->json([
                'status' => false,
                'message' => 'No sub-categories found for the specified category.',
                'data' => [],
            ], 200); // 200 Not Found
        }

        // Return the data as a JSON response
        return response()->json([
            'status' => true,
            'message' => 'Sub-categories retrieved successfully!',
            'data' => $subCategories,
        ], 200); // 200 OK

    } catch (ValidationException $e) {
        // Handle validation exception and redirect with error message
        return response()->json([
            'status' => false,
            'message' => 'Invalid category ID. Please try again with a valid ID.',
            'data' => [],
        ], 200); // 200 Not Found
    }
}
public function getComplainantEmpList(Request $request)
{
    $employeePk = $request->query('emp_id');

    // Build the query
    $query = DB::table('employee_master as e')
        ->leftJoin('designation_master as d', 'e.designation_master_pk', '=', 'd.pk')
        ->select(
            'e.pk',
            DB::raw("TRIM(CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name)) as full_name"),
            DB::raw("COALESCE(e.mobile, '') as mobile"),  // Treat null and empty as the same
            DB::raw("COALESCE(e.mobile1, '') as mobile1"),  // Treat null and empty as the same
            'd.designation_name'
        )
        ->groupBy('e.pk', 'e.first_name', 'e.middle_name', 'e.last_name', 'e.mobile', 'e.mobile1', 'd.designation_name');

    // If 'emp_id' is provided, filter the query for that specific employee
    if ($employeePk) {
        $query->where('e.pk', $employeePk);
    }

    // Execute the query and get the result
    $employees = $query->get();

    // Check if any employees were found
    if ($employees->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'No employees found!',
            'data' => []
        ], 200);  // Return 200 if no employees are found
    }

    // Return the list of employees with a success message
    return response()->json([
        'status' => true,
        'message' => 'Complainant employee list retrieved successfully!',
        'data' => $employees
    ], 200);  // 200 OK
}


public function getLocations(Request $request)
{
    // Determine which type of location to fetch
    $type = $request->query('type');

    switch ($type) {
        case 'hostel':
            $data = $this->getHostelLocations();
            break;
        case 'residential':
            $data = $this->getResidentialLocations();
            break;
        case 'other':
            $data = $this->getOtherLocations();
            break;
        default:
            return response()->json([
                'status' => false,
                'message' => 'Invalid location type specified.',
                'data' => [],
            ], 400); // 400 Bad Request
    }

    return response()->json([
        'status' => true,
        'message' => ucfirst($type) . ' locations retrieved successfully!',
        'data' => $data,
    ], 200); // 200 OK
}

protected function getHostelLocations()
{
    return DB::table('hostel_building_master as e')
        ->select('e.*')
        ->get();
}

protected function getResidentialLocations()
{
    return DB::table('estate_block_master as h')
        
        ->select('h.block_name','h.pk')
        ->get();
}

protected function getOtherLocations()
{
    return DB::table('building_master as k')
       
        ->select('k.building_name', 'k.pk')
        ->get();
}
public function getFloors(Request $request)
    {
        try {
        // Validate the request to ensure the type and id are provided
        $request->validate([
            'type' => 'required|string|in:hostel,residential,other',
            'id' => 'required|integer',
        ]);

        $type = $request->input('type');
        $id = $request->input('id');

        switch ($type) {
            case 'hostel':
                $data = $this->getHostelFloors($id);
                break;
            case 'residential':
                $data = $this->getResidentialFloors($id);
                break;
            case 'other':
                $data = $this->getOtherFloors($id);
                break;
            default:
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid location type specified.',
                    'data' => [],
                ], 400); // 400 Bad Request
        }

        return response()->json([
            'status' => true,
            'message' => ucfirst($type) . ' floors retrieved successfully!',
            'data' => $data,
        ], 200); // 200 OK
    } catch (ValidationException $e) {
        // Handle validation exception and redirect with error message
        return response()->json([
            'status' => false,
            'message' => 'Invalid ID. Please try again with a valid ID.',
            'data' => [],
        ], 200); // 200 Not Found
    }
    }

    protected function getHostelFloors($buildingId)
    {
        // Retrieve floor names for the specified hostel building
        return DB::table('hostel_building_floor_map as f')
            ->where('f.hostel_building_master_pk', $buildingId)
            ->select('f.pk as floor_id', 'f.floor_name as floor')
            ->get();
    }
    //iske bad ka need disccusion

    protected function getResidentialFloors($blockId)
    {
        // Retrieve floor types for the specified residential block
        return  $floors = DB::table('estate_block_master as h')
        ->join('estate_house_master as j', 'h.pk', '=', 'j.estate_block_master_pk')
        ->leftJoin('estate_unit_sub_type_master as i', 'j.estate_unit_sub_type_master_pk', '=', 'i.pk')
        ->where('h.pk', $blockId)
        ->select('h.pk as block_id', 'h.block_name', 'i.unit_sub_type as floor_type','j.estate_unit_sub_type_master_pk')
        ->distinct()
        ->orderBy('h.block_name')
        ->get();
    }

    protected function getOtherFloors($buildingId)
    {
        // Retrieve floor names for the specified building
        // return DB::table('building_room_master as l')
        //     ->where('l.building_master_pk', $buildingId)
        //     ->select('l.pk as floor_id', 'l.floor_name as floor')
        //     ->distinct()
        //     ->get();
           return  $floors = DB::table('building_master as k')
           ->join('building_room_master as l', 'k.pk', '=', 'l.building_master_pk')
           ->where('k.pk', $buildingId)
           ->select(
               'k.building_name',
               'l.floor',
               DB::raw("GROUP_CONCAT(DISTINCT l.room_no ORDER BY l.room_no SEPARATOR ', ') as room_numbers")
           )
           ->groupBy('k.building_name', 'l.floor')
           ->orderBy('k.building_name')
           ->get();
    }

    public function getRoomHouseNumbers(Request $request)
    {
        try {
            // Validate the request to ensure the type, building_id, and floor_id are provided
            $validated = $request->validate([
                'type' => 'required|string|in:hostel,residential,other',
                'building_id' => 'required|integer',
                'floor_id' => 'required|integer',
            ]);
    
            // Retrieve validated parameters
            $type = $validated['type'];
            $buildingId = $validated['building_id'];
            $floorId = $validated['floor_id'];
    
            // Initialize the query result
            $result = collect();
    
            switch ($type) {
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
                    ->where('l.floor', $floorId) // Updated based on schema
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
                    ], 400); // Bad Request
            }
    
            // Check if any results were found
            if ($result->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No data found for the specified type and identifier!',
                    'data' => []
                ], 200); // Not Found
            }
    
            // Return the results with a success message
            return response()->json([
                'status' => true,
                'message' => 'Room/House numbers retrieved successfully!',
                'data' => $result
            ], 200); // OK
    
        } catch (ValidationException $e) {
            // Handle validation exception and return error message
            return response()->json([
                'status' => false,
                'message' => 'Invalid parameters. Please ensure type, building_id, and floor_id are provided correctly.',
                'data' => [],
            ], 422); // Unprocessable Entity
        }
    }
    

    public function dashboard(Request $request)
{
    // Get the user ID from the request
    $userId = $request->created_by;

    $user = JbpUser::where('user_id', $userId)
    
    ->first();
    // Fetch the total count of notifications for the user
    $notificationCount = DB::table('app_notification')
        ->where('notification_to', $userId)
        ->where('status', 2)
        ->count();

    // Prepare dashboard data
    $dashboard = [
        'name' => $user->jbp_givenname . ' ' . $user->jbp_familyname, // Assuming you'll add user's name here
        'pk' => $user->jbp_uid, // User's primary key
        'phone' => $user->mobile_no, // User's phone number
        'notification_count' => $notificationCount, // Total count of notifications for this user
    ];

    // Return response
    return response()->json([
        'status' => true,
        'message' => 'Dashboard access successfully!',
        'data' => $dashboard,
    ], 200); // 200 OK
}



    function submit_complaint(Request $request){
        try {
            // Validate input data
            $validated = $request->validate([
                'description' => 'required|string',
                'issue_logger' => 'required|integer',  // User ID who is updating the complaint
                'created_by' => 'required|integer', // The user who created the complaint
                'location' => 'required|string|in:H,R,O',  // Hostel, Residential, Other locations
                'building_master_pk' => 'required|integer',
                
                'issue_category_id' => 'required|integer', // Adding category ID
                'issue_sub_category_id' => 'required|integer', // Adding subcategory ID
                'sub_category_name' => 'required|string', // Adding subcategory name
                'complaint_img_url.*' => 'nullable', // Validate each URL
                ]);
           
        $data = array(
            'issue_category_master_pk' => $request->issue_category_id,
            'location' => $request->location,
            'description' => $request->description,
            'created_by' => $request->created_by,  // login user id
            'issue_logger' => $request->issue_logger, // if user change for or complaint another user
            'issue_status' => 0, 
            'created_date' => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
            'complaint_img' => is_array($request->complaint_img_url) 
        ? json_encode($request->complaint_img_url) 
        : $request->complaint_img_url,
        );
      
        $id = DB::table('issue_log_management')->insertGetId($data);
        $issue_log_sub_category_map = array(
            'issue_log_management_pk' => $id,
            'issue_category_master_pk' => $request->issue_category_id,
            'issue_sub_category_master_pk' => $request->issue_sub_category_id,
           'sub_category_name' => $request->sub_category_name,
        );
         DB::table('issue_log_sub_category_map')->insert($issue_log_sub_category_map);

        if ($request->location == 'H'){// condition for hostel location
         $hostel_data = array(
            'issue_log_management_pk' => $id,
            'hostel_building_master_pk' => $request->building_master_pk,//here user hostel building pk
            'floor_name' => $request->floor_id ?? '', // hostel_building_floor_map ka pk yha ayega 
            'room_name' => $request->room_name ?? '', // ye static ayega room name
        );
        DB::table('issue_log_hostel_map')->insert($hostel_data);


        }else if ($request->location == 'R'){// condition for resident location

            $other_data = array(
                'issue_log_management_pk' => $id,
                'hostel_building_master_pk' => $request->building_master_pk,//building_master table ka pk yha ayega 
                'floor_name' => $request->floor_id ?? '', // ye estate_unit_sub_type_master ka pk ayega 
                'room_name' => $request->room_name ?? '', // ye static ayega room name
            );
            DB::table('issue_log_hostel_map')->insert($other_data);


        }else if ($request->location == 'O'){// condition for other location 

            $other_data = array(
                'issue_log_management_pk' => $id,
                'building_master_pk' => $request->building_master_pk,//building_master table ka pk yha ayega 
                'floor_name' => $request->floor_id ?? '', // ye static ayega floor name
                'room_name' => $request->room_name ?? '', // ye static ayega room name
            );
            DB::table('issue_log_building_map')->insert($other_data);


            
        }

        $status_data = array(
            'issue_log_management_pk' => $id,
            'issue_status' => 0,
            'issue_date' => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
            'created_by' => $request->created_by,
        );      
        DB::table('Issue_log_status')->insert($status_data);


        return response()->json([
            'status' => true,
            'message' => 'Complaint submitted successfully!',
           
        ], 200); // 200 OK
    } catch (ValidationException $e) {
        // Handle validation exception and return error message
        return response()->json([
            'status' => false,
            'message' => 'An error occurred while submit the complaint.',
            'error' => $e->getMessage(),
        ], 500); // Internal Server Error
    }
       

    }
    function list_complaint(Request $request) {
        $created_by = $request->input('created_by'); 
       
        $result = DB::table('issue_log_management as a')
            ->join('issue_category_master as b', 'a.issue_category_master_pk', '=', 'b.pk')
            ->join('issue_log_sub_category_map as e', 'a.pk', '=', 'e.issue_log_management_pk')
            // ->join('employee_master as c', 'a.employee_master_pk', '=', 'c.pk')
            ->where('a.created_by', $created_by) // Only complaints created by this user
            ->select(
                'a.pk',
                'a.description',
                'a.location',
                'a.employee_master_pk',
                'a.created_by',
                'a.issue_status',
                'a.show_status',
                'a.created_date',
                'b.issue_category',
                'e.sub_category_name',
                DB::raw("
                    CASE
                        WHEN a.issue_status = 0 THEN 'Complaint Reported'
                        WHEN a.issue_status = 1 THEN 'Report is Open and Work in Progress'
                        WHEN a.issue_status = 2 THEN 'Complaint has been Resolved'
                        WHEN a.issue_status = 3 THEN 'Complaint is Pending'
                        WHEN a.issue_status = 6 THEN 'Complaint is Reopened and Work in Progress'
                        ELSE 'Unknown Status'
                    END as issue_status_label
                ")
            )
            ->get();
    
        return response()->json([
            'status' => true,
            'message' => 'Complaint list retrieved successfully!',
            'data' => $result
        ], 200); // 200 OK
    }
    public function view_complaint(Request $request) {
        // Validate the input to ensure complaint_id is provided and is an integer
        $validated = $request->validate([
            'complaint_id' => 'required|integer',
        ]);
    
        $complaintId = $validated['complaint_id'];
    
        // Query to fetch complaint details along with related data
        $result = DB::table('issue_log_management as a')
            ->join('issue_category_master as b', 'a.issue_category_master_pk', '=', 'b.pk')
            ->join('issue_log_sub_category_map as e', 'a.pk', '=', 'e.issue_log_management_pk')
            ->leftJoin('employee_master as em', 'em.pk', '=', 'a.issue_logger') // Get name for the logger
            ->leftJoin('employee_master as ne', 'ne.pk', '=', 'a.employee_master_pk') // Get logger name
            // Join with hostel map for hostel-specific complaints (location = H)
            ->leftJoin('issue_log_hostel_map as h', function($join) {
                $join->on('a.pk', '=', 'h.issue_log_management_pk')
                     ->where('a.location', '=', 'H');  // Hostel-specific condition
            })
            
            // Join with hostel_building_floor_map to get floor_name for hostels
            ->leftJoin('hostel_building_floor_map as hf', 'h.floor_name', '=', 'hf.pk')
    
            // Join with hostel_building_master to get building name
            ->leftJoin('hostel_building_master as hb', 'h.hostel_building_master_pk', '=', 'hb.pk')
    
            // Join for residential location (location = R)
            ->leftJoin('issue_log_hostel_map as r', function($join) {
                $join->on('a.pk', '=', 'r.issue_log_management_pk')
                     ->where('a.location', '=', 'R');  // Residential-specific condition
            })
            
            // Additional join for residential floor name
            ->leftJoin('estate_unit_sub_type_master as rf', 'r.floor_name', '=', 'rf.pk')
    
            // Join for other location (location = O)
            ->leftJoin('issue_log_building_map as o', function($join) {
                $join->on('a.pk', '=', 'o.issue_log_management_pk')
                     ->where('a.location', '=', 'O');  // Other-specific condition
            })
            ->leftJoin('building_master as bm', 'o.building_master_pk', '=', 'bm.pk') // Corrected building master join
    
            // Join with estate_house_master to get the estate_block_master_pk and then join with estate_block_master to get block_name
            // ->leftJoin('estate_house_master as eh', 'r.floor_name', '=', 'eh.pk') // Join estate_house_master on floor_name
            ->leftJoin('estate_block_master as eb', 'r.hostel_building_master_pk', '=', 'eb.pk') // Join with estate_block_master to get block_name
    
            ->where('a.pk', $complaintId)
    
            ->select(
                'a.pk',
                'a.description',
                'a.location',
                'a.employee_master_pk',
                'a.created_by',
                'a.issue_status',
                'a.show_status',
                'a.created_date',
                'a.complaint_img',
                'b.issue_category',
                'e.sub_category_name',
                
    
                DB::raw("TRIM(CONCAT(em.first_name, ' ', COALESCE(em.middle_name, ''), ' ', em.last_name)) as full_name"),
                DB::raw("TRIM(CONCAT(ne.first_name, ' ', COALESCE(ne.middle_name, ''), ' ', ne.last_name)) as nodel_full_name"),
                DB::raw("COALESCE(hf.floor_name, rf.unit_sub_type, o.floor_name) as floor_name"),
                DB::raw("COALESCE(h.room_name, r.room_name, o.room_name) as room_name"),
                DB::raw("COALESCE(hb.building_name, '') as building_name"), // Added building name from hostel_building_master
                DB::raw("COALESCE(eb.block_name, '') as block_name"), // Added block name from estate_block_master for residential complaints
                DB::raw("COALESCE(bm.building_name, '') as building_name_other"), // Added building name from building_master for other complaints
                DB::raw("
                    CASE
                        WHEN a.issue_status = 0 THEN 'Complaint Reported'
                        WHEN a.issue_status = 1 THEN 'Report is Open and Work in Progress'
                        WHEN a.issue_status = 2 THEN 'Complaint has been Resolved'
                        WHEN a.issue_status = 3 THEN 'Complaint is Pending'
                        WHEN a.issue_status = 6 THEN 'Complaint is Reopened and Work in Progress'
                        ELSE 'Unknown Status'
                    END as issue_status_label
                ")
            )
            ->first();
    
        // Return the result in JSON format
        if ($result) {
            if (!empty($result->complaint_img)) {
                $result->complaint_img = json_decode($result->complaint_img);
            }
    
            return response()->json([
                'status' => true,
                'message' => 'Complaint details retrieved successfully!',
                'data' => $result
            ], 200); // 200 OK
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Complaint not found.',
                'data' => [],
            ], 404); // 404 Not Found
        }
    }
    
    
    function view_complaint1(Request $request) {
        $validated = $request->validate([
            'complaint_id' => 'required|integer',
        ]);
    
        $complaintId = $validated['complaint_id'];
    
        $result = DB::table('issue_log_management as a')
            ->join('issue_category_master as b', 'a.issue_category_master_pk', '=', 'b.pk')
            ->join('issue_log_sub_category_map as e', 'a.pk', '=', 'e.issue_log_management_pk')
            ->leftJoin('employee_master as em', 'em.pk', '=', 'a.issue_logger')// name get krne ke liye
            // Join with hostel map for hostel-specific complaints
            ->leftJoin('issue_log_hostel_map as h', function($join) {
                $join->on('a.pk', '=', 'h.issue_log_management_pk')
                     ->where('a.location', '=', 'H');
            })
            
            // Additional join with hostel_building_floor_map to get floor_name for hostels
            ->leftJoin('hostel_building_floor_map as hf', 'h.floor_name', '=', 'hf.pk')
            ->leftJoin('hostel_building_master as hb', 'h.hostel_building_master_pk', '=', 'hb.pk')
           

            // Join for residential location
            ->leftJoin('issue_log_hostel_map as r', function($join) {
                $join->on('a.pk', '=', 'r.issue_log_management_pk')
                     ->where('a.location', '=', 'R');
            })
            // Additional join for residential floor name
            ->leftJoin('estate_house_master as eh', 'r.floor_name', '=', 'eh.pk') // Join estate_house_master on floor_name
            ->leftJoin('estate_block_master as eb', 'eh.estate_block_master_pk', '=', 'eb.pk') // Join with estate_block_master to get block_name
    
            // Join for other location
            ->leftJoin('issue_log_building_map as o', function($join) {
                $join->on('a.pk', '=', 'o.issue_log_management_pk')
                     ->where('a.location', '=', 'O');
            })
            
            ->where('a.pk', $complaintId)
            
            ->select(
                'a.pk',
                'a.description',
                'a.location',
                'a.employee_master_pk',
                'a.created_by',
                'a.issue_status',
                'a.show_status',
                'a.created_date',
                'b.issue_category',
                'e.sub_category_name',
                'eb.block_name',
                
                DB::raw("TRIM(CONCAT(em.first_name, ' ', COALESCE(em.middle_name, ''), ' ', em.last_name)) as full_name"),
                DB::raw("COALESCE(hf.floor_name, rf.unit_sub_type, o.floor_name) as floor_name"),
                DB::raw("COALESCE(h.room_name, r.room_name, o.room_name) as room_name"),
                DB::raw("
                CASE
                    WHEN a.issue_status = 0 THEN 'Complaint Reported'
                    WHEN a.issue_status = 1 THEN 'Report is Open and Work in Progress'
                    WHEN a.issue_status = 2 THEN 'Complaint has been Resolved'
                    WHEN a.issue_status = 3 THEN 'Complaint is Pending'
                    WHEN a.issue_status = 6 THEN 'Complaint is Reopened and Work in Progress'
                    ELSE 'Unknown Status'
                END as issue_status_label
            ")
            )
            ->first();
    
        if ($result) {
            return response()->json([
                'status' => true,
                'message' => 'Complaint details retrieved successfully!',
                'data' => $result
            ], 200); // 200 OK
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Complaint not found.',
                'data' => [],
            ], 404); // 404 Not Found
        }
    }
    function active_complaint(Request $request){
        $created_by = $request->input('created_by');
        $result = DB::table('issue_log_management as a')
        ->join('issue_category_master as b', 'a.issue_category_master_pk', '=', 'b.pk')
        ->join('issue_log_sub_category_map as e', 'a.pk', '=', 'e.issue_log_management_pk')
        // ->join('employee_master as c', 'a.employee_master_pk', '=', 'c.pk')
        ->where('a.created_by', $created_by) // Only complaints created by this user
        // Only complaints created by this user
        ->where('a.issue_status', '!=', 2)
        ->orderBy('a.pk', 'desc')
        ->select(
            'a.pk',
            'a.description',
            'a.location',
            'a.employee_master_pk',
            'a.created_by',
            'a.issue_status',
            'a.show_status',
            'a.created_date',
            'b.issue_category',
            'e.sub_category_name',
            DB::raw("
                CASE
                    WHEN a.issue_status = 0 THEN 'Complaint Reported'
                    WHEN a.issue_status = 1 THEN 'Report is Open and Work in Progress'
                    WHEN a.issue_status = 2 THEN 'Complaint has been Resolved'
                    WHEN a.issue_status = 3 THEN 'Complaint is Pending'
                    WHEN a.issue_status = 6 THEN 'Complaint is Reopened and Work in Progress'
                    ELSE 'Unknown Status'
                END as issue_status_label
            ")
        )
        ->get();

    return response()->json([
        'status' => true,
        'message' => 'Complaint list retrieved successfully !',
        'data' => $result
    ], 200); // 200 OK
    }

    
    function inactive_complaint(Request $request){
        $created_by = $request->input('created_by');
        $currentYear = date('Y');
        $currentMonth = date('m');
        $result = DB::table('issue_log_management as a')
        ->join('issue_category_master as b', 'a.issue_category_master_pk', '=', 'b.pk')
        ->join('issue_log_sub_category_map as e', 'a.pk', '=', 'e.issue_log_management_pk')
        // ->join('employee_master as c', 'a.employee_master_pk', '=', 'c.pk')
        ->where('a.created_by', $created_by) // Only complaints created by this user
        ->where('a.issue_status', 'LIKE', '%2%') // Only complaints created by this user
        ->whereRaw('YEAR(a.created_date) = ? AND MONTH(a.created_date) = ?', [$currentYear, $currentMonth]) // Faster filtering
        ->orderBy('a.pk', 'desc')
        ->select(
            'a.pk',
            'a.description',
            'a.location',
            'a.employee_master_pk',
            'a.created_by',
            'a.issue_status',
            'a.show_status',
            'a.created_date',
            'b.issue_category',
            'e.sub_category_name',
            DB::raw("
                CASE
                    WHEN a.issue_status = 0 THEN 'Complaint Reported'
                    WHEN a.issue_status = 1 THEN 'Report is Open and Work in Progress'
                    WHEN a.issue_status = 2 THEN 'Complaint has been Resolved'
                    WHEN a.issue_status = 3 THEN 'Complaint is Pending'
                    WHEN a.issue_status = 6 THEN 'Complaint is Reopened and Work in Progress'
                    ELSE 'Unknown Status'
                END as issue_status_label
            ")
        )
        ->get();

    return response()->json([
        'status' => true,
        'message' => 'Complaint list retrieved successfully!',
        'data' => $result
    ], 200); // 200 OK
    }

    public function filter_list_complaint(Request $request)
    {
        $issue_category = $request->input('issue_category');
        $location = $request->input('location');
        $issue_status = $request->input('issue_status');
        $filter_date = $request->input('date'); // Single date input in format YYYY-MM-DD
        $created_by = $request->input('created_by'); 
    
        // Start building the query
        $query = DB::table('issue_log_management as a')
            ->join('issue_category_master as b', 'a.issue_category_master_pk', '=', 'b.pk')
            ->join('issue_log_sub_category_map as e', 'a.pk', '=', 'e.issue_log_management_pk');
    
        if ($created_by) {
            $query->where('a.created_by', $created_by);
        }
        
        // Apply dynamic filters
        if ($issue_category) {
            $query->where('a.issue_category_master_pk', $issue_category);
        }
        if ($location) {
            $query->where('a.location', 'LIKE', '%' . $location . '%');
        }
        if ($issue_status !== null) { // Allow 0 as a valid status
            $query->where('a.issue_status', $issue_status);
        }
        if ($filter_date) {
            $query->whereDate('a.created_date', $filter_date); // Filter by a single specific date
        }
        $query->orderBy('a.pk', 'desc');
    
        // Execute query with selected fields
        $result = $query->select(
            'a.pk',
            'a.description',
            'a.location',
            'a.employee_master_pk',
            'a.created_by',
            'a.issue_status',
            'a.show_status',
            'a.created_date',
            'b.issue_category',
            'e.sub_category_name',
            DB::raw("
                CASE
                    WHEN a.issue_status = 0 THEN 'Complaint Reported'
                    WHEN a.issue_status = 1 THEN 'Report is Open and Work in Progress'
                    WHEN a.issue_status = 2 THEN 'Complaint has been Resolved'
                    WHEN a.issue_status = 3 THEN 'Complaint is Pending'
                    WHEN a.issue_status = 6 THEN 'Complaint is Reopened and Work in Progress'
                    ELSE 'Unknown Status'
                END as issue_status_label
            ")
        )->get();
    
        // Return JSON response with the results
        return response()->json([
            'status' => true,
            'message' => 'Complaint list retrieved successfully!',
            'data' => $result
        ], 200); // 200 OK
    }
    
    public function delete_complaint(Request $request)
    {
        try {
            // Get the complaint_id and created_by fields from the request
            $complaint_id = $request->input('complaint_id');
            $created_by = $request->input('created_by');
    
            // Check if the complaint exists and was created by the given user
            $complaint = DB::table('issue_log_management')
                ->where([
                    ['pk', '=', $complaint_id],
                    ['created_by', '=', $created_by]
                ])->first();
            
            if (!$complaint) {
                return response()->json([
                    'status' => false,
                    'message' => 'Complaint not found or you are not authorized to delete this complaint.',
                ], 404); // 404 Not Found
            }
    
            // Delete related records from associated tables
            DB::table('issue_log_sub_category_map')->where('issue_log_management_pk', $complaint_id)->delete();
            DB::table('issue_log_hostel_map')->where('issue_log_management_pk', $complaint_id)->delete();
            DB::table('issue_log_building_map')->where('issue_log_management_pk', $complaint_id)->delete();
    
            // Delete the complaint from issue_log_management
            DB::table('issue_log_management')->where('pk', $complaint_id)->delete();
    
            return response()->json([
                'status' => true,
                'message' => 'Complaint deleted successfully!',
            ], 200); // 200 OK
    
        } catch (Exception $e) {
            // Handle any errors and return error message
            return response()->json([
                'status' => false,
                'message' => 'Error deleting complaint. Please try again.',
                'error' => $e->getMessage(),
            ], 500); // 500 Internal Server Error
        }
    }

    public function edit_complaint(Request $request)
{
    try {
        // Get the complaint_id and created_by from the request
        $validated = $request->validate([
            'complaint_id' => 'required|integer',
            'created_by' => 'required|integer',
        ]);

        $complaintId = $validated['complaint_id'];
        $createdBy = $validated['created_by'];

        // Retrieve the complaint details with enhanced query
        $result = DB::table('issue_log_management as a')
            ->join('issue_category_master as b', 'a.issue_category_master_pk', '=', 'b.pk')
            ->join('issue_log_sub_category_map as e', 'a.pk', '=', 'e.issue_log_management_pk')
            ->leftJoin('employee_master as em', 'em.pk', '=', 'a.issue_logger') // Get logger name
            ->leftJoin('employee_master as ne', 'ne.pk', '=', 'a.employee_master_pk') // Get logger name
            
            // Join with hostel map for hostel-specific complaints (location = H)
            ->leftJoin('issue_log_hostel_map as h', function($join) {
                $join->on('a.pk', '=', 'h.issue_log_management_pk')
                     ->where('a.location', '=', 'H');  // Hostel-specific condition
            })
            
            // Join with hostel_building_floor_map to get floor_name for hostels
            ->leftJoin('hostel_building_floor_map as hf', 'h.floor_name', '=', 'hf.pk')
            
            // Join with hostel_building_master to get building name
            ->leftJoin('hostel_building_master as hb', 'h.hostel_building_master_pk', '=', 'hb.pk')
            
            // Join for residential location (location = R)
            ->leftJoin('issue_log_hostel_map as r', function($join) {
                $join->on('a.pk', '=', 'r.issue_log_management_pk')
                     ->where('a.location', '=', 'R');  // Residential-specific condition
            })
            
            // Additional join for residential floor name
            ->leftJoin('estate_unit_sub_type_master as rf', 'r.floor_name', '=', 'rf.pk')
            
            // Join for other location (location = O)
            ->leftJoin('issue_log_building_map as o', function($join) {
                $join->on('a.pk', '=', 'o.issue_log_management_pk')
                     ->where('a.location', '=', 'O');  // Other-specific condition
            })
            ->leftJoin('building_master as bm', 'o.building_master_pk', '=', 'bm.pk') // Corrected building master join
            
            // Join with estate_house_master to get the estate_block_master_pk and then join with estate_block_master to get block_name
            // ->leftJoin('estate_house_master as eh', 'r.floor_name', '=', 'eh.pk') // Join estate_house_master on floor_name
            ->leftJoin('estate_block_master as eb', 'r.hostel_building_master_pk', '=', 'eb.pk') // Join with estate_block_master to get block_name
            
            ->where('a.pk', $complaintId)
            ->where('a.created_by', $createdBy)
            ->select(
                'a.pk',
                'a.issue_category_master_pk',
                'a.description',
                'a.location',
                'a.employee_master_pk',
                'a.created_by',
                'a.issue_status',
                'a.show_status',
                'a.created_date',
                'a.complaint_img',
                'b.issue_category',
                'e.sub_category_name',
                'e.issue_sub_category_master_pk',
                'h.hostel_building_master_pk as building_name_hostel',
                'h.floor_name as floor_name_hostel',
                'r.hostel_building_master_pk as building_name_residence',
                'r.floor_name as floor_name_residence',
                
                'o.building_master_pk as building_name_o',
                'o.floor_name as floor_name_o',
               
                DB::raw("TRIM(CONCAT(em.first_name, ' ', COALESCE(em.middle_name, ''), ' ', em.last_name)) as full_name"),
                DB::raw("TRIM(CONCAT(ne.first_name, ' ', COALESCE(ne.middle_name, ''), ' ', ne.last_name)) as nodel_full_name"),
                DB::raw("COALESCE(hf.floor_name, rf.unit_sub_type, o.floor_name) as floor_name"),
                DB::raw("COALESCE(h.room_name, r.room_name, o.room_name) as room_name"),
                DB::raw("COALESCE(hb.building_name, '') as building_name"), // Added building name from hostel_building_master
                DB::raw("COALESCE(eb.block_name, '') as block_name"), // Added block name from estate_block_master for residential complaints
                DB::raw("COALESCE(bm.building_name, '') as building_name_other"), // Added building name from building_master for other complaints
                DB::raw("
                    CASE
                        WHEN a.issue_status = 0 THEN 'Complaint Reported'
                        WHEN a.issue_status = 1 THEN 'Report is Open and Work in Progress'
                        WHEN a.issue_status = 2 THEN 'Complaint has been Resolved'
                        WHEN a.issue_status = 3 THEN 'Complaint is Pending'
                        WHEN a.issue_status = 6 THEN 'Complaint is Reopened and Work in Progress'
                        ELSE 'Unknown Status'
                    END as issue_status_label
                ")
            )
            ->first();

        // Check if the result exists
        if ($result) {
            if (!empty($result->complaint_img)) {
                $result->complaint_img = json_decode($result->complaint_img);
            }
            return response()->json([
                'status' => true,
                'message' => 'Complaint details retrieved successfully!',
                'data' => $result
            ], 200); // 200 OK
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Complaint not found.',
                'data' => [],
            ], 404); // 404 Not Found
        }

    } catch (Exception $e) {
        // Handle any errors and return error message
        return response()->json([
            'status' => false,
            'message' => 'Error retrieving complaint details. Please try again.',
            'error' => $e->getMessage(),
        ], 500); // 500 Internal Server Error
    }
}




public function update_complaint(Request $request)
{
    try {
        // Validate input data
        $validated = $request->validate([
            'complaint_id' => 'required|integer',
            'description' => 'required|string',
            'issue_logger' => 'required|integer',  // User ID who is updating the complaint
            'created_by' => 'required|integer', // The user who created the complaint
            'location' => 'required|string|in:H,R,O',  // Hostel, Residential, Other locations
            'building_master_pk' => 'required|integer',
            // 'floor_id' => 'required',
            'issue_category_id' => 'required|integer', // Adding category ID
            'issue_sub_category_id' => 'required|integer', // Adding subcategory ID
            'sub_category_name' => 'required|string', // Adding subcategory name
            'complaint_img_url.*' => 'nullable', // Validate each URL
        ]);

        // Extract validated data
        $complaint_id = $request->complaint_id;
        $description = $request->description;
        $issue_logger = $request->issue_logger;
        $created_by = $request->created_by;
        $location = $request->location;
        $building_master_pk = $request->building_master_pk;
        //$floor_id = $request->floor_id;
       // $employee_master_pk = $request->employee_master_pk;
        $issue_category_id = $request->issue_category_id; // Category ID
        $issue_sub_category_id = $request->issue_sub_category_id; // Subcategory ID
        $sub_category_name = $request->sub_category_name; // Subcategory Name

        // Update complaint record in the database
        $update_data = array(
            'description' => $description,
            'issue_category_master_pk' => $issue_category_id,
            'updated_date' => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
            'issue_logger' => $issue_logger,
            'location' => $location,
            // 'employee_master_pk' => $employee_master_pk,
            'complaint_img' => is_array($request->complaint_img_url) 
            ? json_encode($request->complaint_img_url) 
            : $request->complaint_img_url,
        );

        $updated = DB::table('issue_log_management')
            ->where('pk', $complaint_id)
            ->where('created_by', $created_by) // Ensure only the creator can update the complaint
            ->update($update_data);

        if (!$updated) {
            return response()->json([
                'status' => false,
                'message' => 'Complaint not found or you are not authorized to update it.',
            ], 404); // Not Found or Unauthorized
        }

        // Update the category and subcategory mapping
        $update_category = array(
            'issue_category_master_pk' => $issue_category_id,
            'issue_sub_category_master_pk' => $issue_sub_category_id,
            'sub_category_name' => $sub_category_name,
        );

        DB::table('issue_log_sub_category_map')
            ->where('issue_log_management_pk', $complaint_id)
            ->update($update_category);

 

            $complaint_h_r = DB::table('issue_log_hostel_map')
                ->where([
                    ['issue_log_management_pk', '=', $complaint_id]
                ])->first();
                if ($complaint_h_r) {
                    DB::table('issue_log_hostel_map')->where('issue_log_management_pk', $complaint_id)->delete();
                }
                $complaint_o = DB::table('issue_log_building_map')
                ->where([
                    ['issue_log_management_pk', '=', $complaint_id]
                ])->first();
                if ($complaint_o) {
                    DB::table('issue_log_building_map')->where('issue_log_management_pk', $complaint_id)->delete();
                }
               

        // Update location-based data (Hostel, Residential, Other locations)

        if ($request->location == 'H'){// condition for hostel location
            $hostel_data = array(
               'issue_log_management_pk' => $complaint_id,
               'hostel_building_master_pk' => $request->building_master_pk,//here user hostel building pk
               'floor_name' => $request->floor_id ?? '', // hostel_building_floor_map ka pk yha ayega 
               'room_name' => $request->room_name ?? '', // ye static ayega room name
           );
           DB::table('issue_log_hostel_map')->insert($hostel_data);
   
   
           }else if ($request->location == 'R'){// condition for resident location
   
               $other_data = array(
                   'issue_log_management_pk' => $complaint_id,
                   'hostel_building_master_pk' => $request->building_master_pk,//building_master table ka pk yha ayega 
                   'floor_name' => $request->floor_id ?? '', // ye estate_unit_sub_type_master ka pk ayega 
                   'room_name' => $request->room_name ?? '', // ye static ayega room name
               );
               DB::table('issue_log_hostel_map')->insert($other_data);
   
   
           }else if ($request->location == 'O'){// condition for other location 
   
               $other_data = array(
                   'issue_log_management_pk' => $complaint_id,
                   'building_master_pk' => $request->building_master_pk,//building_master table ka pk yha ayega 
                   'floor_name' => $request->floor_id ?? '', // ye static ayega floor name
                   'room_name' => $request->room_name ?? '', // ye static ayega room name
               );
               DB::table('issue_log_building_map')->insert($other_data);
   
   
               
           }
       

        return response()->json([
            'status' => true,
            'message' => 'Complaint updated successfully!',
        ], 200); // 200 OK

    } catch (ValidationException $e) {
        // Handle validation exception
        return response()->json([
            'status' => false,
            'message' => 'Invalid parameters. Something went wrong.',
            'error' => $e->getMessage(),
        ], 422); // Unprocessable Entity
    } catch (\Exception $e) {
        // Handle general errors
        return response()->json([
            'status' => false,
            'message' => 'An error occurred while updating the complaint.',
            'error' => $e->getMessage(),
        ], 500); // Internal Server Error
    }
}

public function complaint_exportPdf(Request $request)
{
    try {
     

        $issue_category = $request->input('issue_category');
        $location = $request->input('location');
        $issue_status = $request->input('issue_status');
        $filter_date = $request->input('date'); // Single date input in format YYYY-MM-DD
        $created_by = $request->input('created_by'); 
    
        // Start building the query
        $query = DB::table('issue_log_management as a')
            ->join('issue_category_master as b', 'a.issue_category_master_pk', '=', 'b.pk')
            ->join('issue_log_sub_category_map as e', 'a.pk', '=', 'e.issue_log_management_pk')
            ->leftJoin('employee_master as em', 'em.pk', '=', 'a.issue_logger');
    
        if ($created_by) {
            $query->where('a.created_by', $created_by);
        }
        
        // Apply dynamic filters
        if ($issue_category) {
            $query->where('a.issue_category_master_pk', $issue_category);
        }
        if ($location) {
            $query->where('a.location', 'LIKE', '%' . $location . '%');
        }
        if ($issue_status !== null) { // Allow 0 as a valid status
            $query->where('a.issue_status', $issue_status);
        }
        if ($filter_date) {
            $query->whereDate('a.created_date', $filter_date); // Filter by a single specific date
        }
        $query->orderBy('a.pk', 'desc');
    
        // Execute query with selected fields
        $result = $query->select(
            'a.pk',
            'a.description',
            'a.location',
            'a.employee_master_pk',
            'a.created_by',
            'a.issue_status',
            'a.show_status',
            'a.created_date',
            'b.issue_category',
            'e.sub_category_name',
            DB::raw("TRIM(CONCAT(em.first_name, ' ', COALESCE(em.middle_name, ''), ' ', em.last_name)) as full_name"),
            DB::raw("
                CASE
                    WHEN a.location = 'H' THEN 'Hostel'
                    WHEN a.location = 'R' THEN 'Resident'
                    WHEN a.location = 'O' THEN 'Other'
                    ELSE 'Unknown'
                END as location_full_name
            "),
            DB::raw("
                CASE
                    WHEN a.issue_status = 0 THEN 'Complaint Reported'
                    WHEN a.issue_status = 1 THEN 'Report is Open and Work in Progress'
                    WHEN a.issue_status = 2 THEN 'Complaint has been Resolved'
                    WHEN a.issue_status = 3 THEN 'Complaint is Pending'
                    WHEN a.issue_status = 6 THEN 'Complaint is Reopened and Work in Progress'
                    ELSE 'Unknown Status'
                END as issue_status_label
            ")
        )->get();
        // print_r($result);die;

        // Generate PDF report
        $pdf = PDF::loadView('pdf', compact('result'));
 
        // Define file path and name
        $fileName = 'complaint_report_' . time() . '.pdf';
        $filePath = public_path('export_pdf/' . $fileName);

        // Ensure directory exists
        if (!file_exists(public_path('export_pdf'))) {
            mkdir(public_path('export_pdf'), 0777, true);
        }

        // Save PDF to the public folder
        $pdf->save($filePath);

        // Generate a public link
        $fileUrl = env('APP_URL') . asset('export_pdf/' . $fileName);

        // Return the file URL
        return response()->json([
            'status' => true,
            'message' => 'PDF generated successfully',
            'file_url' => $fileUrl,
        ], 200);

    } catch (\Exception $e) {
        // Handle general errors
        return response()->json([
            'status' => false,
            'message' => 'An error occurred while generating the PDF report.',
            'error' => $e->getMessage(),
        ], 500); // Internal Server Error
    }
}



public function deleteUser(Request $request)
{   try {
    // 1. Validate the incoming request
    $validatedData = $request->validate([
        'created_by' => 'required|integer|exists:jbp_users,jbp_uid',
    ]);

    $user_pk = $validatedData['created_by'];

 
        // 2. Check if the user exists with login_status_app as null
        $user = DB::table('jbp_users')
                    ->where('jbp_uid', $user_pk)
                    ->whereNull('login_status_app')
                    ->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found or already deleted.',
                'status' => false
            ], 404);
        }

        // 3. Update the login_status_app to 1
        $updated = DB::table('jbp_users')
                    ->where('jbp_uid', $user_pk)
                    ->update(['login_status_app' => 1]);

        if ($updated) {
          
            return response()->json([
                'message' => 'User deleted successfully.',
                'status' => true
            ], 200);
        } else {
          
            return response()->json([
                'message' => 'Failed to delete user.',
                'status' => false
            ], 500);
        }
    } catch (\Exception $e) {
         return response()->json([
            'message' => 'An error occurred while deleting the user.',
            'status' => false
        ], 500);
    }
}

function reopen_complaint(Request $request){
    try {
        // Get the complaint_id and created_by fields from the request
        $complaint_id = $request->input('complaint_id');
        $created_by = $request->input('created_by');
        $remark = $request->input('description');

        // Check if the complaint exists and was created by the given user
        $complaint = DB::table('issue_log_management')
            ->where([
                ['pk', '=', $complaint_id],
                ['created_by', '=', $created_by]
            ])->first();
        
        if (!$complaint) {
            return response()->json([
                'status' => false,
                'message' => 'Complaint not found or you are not authorized to delete this complaint.',
            ], 404); // 404 Not Found
        }
        $updated = DB::table('issue_log_management')
        ->where('pk', $complaint_id)
        ->update(['issue_status' => 6,'remark'=>$remark]);

            if ($updated) {

                $status_data = array(
                    'issue_log_management_pk' => $complaint_id,
                    'issue_status' => 6,
                    'issue_date' => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    'created_by' => $created_by,
                );      
                DB::table('Issue_log_status')->insert($status_data);

            return response()->json([
                'message' => 'complaint reopen successfully.',
                'status' => true
            ], 200);
            } else {

            return response()->json([
                'message' => 'Failed to delete user.',
                'status' => false
            ], 500);
            }
    } catch (\Exception $e) {
        return response()->json([
           'message' => 'An error occurred',
           'status' => false
       ], 500);
   }

}

public function notification_list(Request $request)
{
    // Validate that userId is provided in the request
    $request->validate([
        'created_by' => 'required|integer'
    ]);

    // Fetch the user ID from the request
    $userId = $request->created_by;

    // Fetch all notifications for the given userId
    $notifications = DB::table('app_notification')
        ->where('notification_to', $userId)
        // ->where('status', 2)
        ->orderBy('created_on', 'desc') // Optional: Order by created date
        ->get();

    // Check if any notifications were found
    if ($notifications->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'No notifications found for the user',
            'data' => []
        ], 200); // 404 Not Found
    }

    // Add status labels to each notification
    foreach ($notifications as $notification) {
        switch ($notification->complaint_status) {
            case 0:
                $notification->complaint_status_label = 'Complaint Reported';
                break;
            case 1:
                $notification->complaint_status_label = 'Report is Open and Work in Progress';
                break;
            case 2:
                $notification->complaint_status_label = 'Complaint has been Resolved';
                break;
            case 3:
                $notification->complaint_status_label = 'Complaint is Pending';
                break;
            case 6:
                $notification->complaint_status_label = 'Complaint is Reopened and Work in Progress';
                break;
            default:
                $notification->complaint_status_label = 'Unknown Status';
                break;
        }
    }

    // Return the list of notifications
    return response()->json([
        'status' => true,
        'message' => 'Notifications fetched successfully',
        'data' => $notifications
    ], 200); // 200 OK
}

public function test_cronjob()
{
    $dateThreshold = '2025-01-30';

    // Fetch all complaints created after the given date
    $complaints = DB::table('issue_log_management')
        ->select('pk', 'created_by', 'issue_status', 'created_date')
        ->where('created_date', '>', $dateThreshold)
        ->get()
        ->keyBy('pk'); // Convert to associative array for faster lookup

    // Optimize deletion query for removing old notifications
    DB::table('app_notification')
        ->whereNotIn('issue_log_management_pk', $complaints->keys()) // Faster check instead of subquery
        ->delete();

    // Fetch all existing notifications in one go
    $existingNotifications = DB::table('app_notification')
        ->whereIn('issue_log_management_pk', $complaints->keys())
        ->get()
        ->keyBy('issue_log_management_pk');

    // Notification messages mapping
    $statusMessages = [
        0 => 'Complaint Reported',
        1 => 'Report is Open and Work in Progress',
        2 => 'Complaint has been Resolved',
        3 => 'Complaint is Pending',
        6 => 'Complaint is Reopened and Work in Progress',
    ];

    $insertNotifications = [];
    $deleteNotifications = [];

    foreach ($complaints as $complaint) {
        $statusLabel = $statusMessages[$complaint->issue_status] ?? 'Unknown Status';

        // Check if a notification exists and if the status is the same
        if (isset($existingNotifications[$complaint->pk]) &&
            $existingNotifications[$complaint->pk]->complaint_status == $complaint->issue_status) {
            continue; // Skip if no change in status
        }

        // Delete old notifications for this complaint
        $deleteNotifications[] = $complaint->pk;

        // Prepare new notification data
        $insertNotifications[] = [
            'notification_title' => 'Complaint Status Updated',
            'notification_msg' => "The status of complaint ID {$complaint->pk} has changed to: {$statusLabel}",
            'notification_to' => $complaint->created_by,
            'issue_log_management_pk' => $complaint->pk,
            'status' => '2',
            'complaint_status' => $complaint->issue_status,
            'created_on' => now(),
        ];
    }

    // Bulk delete old notifications
    if (!empty($deleteNotifications)) {
        DB::table('app_notification')
            ->whereIn('issue_log_management_pk', $deleteNotifications)
            ->delete();
    }

    // Bulk insert new notifications
    if (!empty($insertNotifications)) {
        DB::table('app_notification')->insert($insertNotifications);
    }

    // Send push notifications in bulk
    foreach ($insertNotifications as $notification) {
        $this->sendPushNotification($notification['notification_to'], $notification['notification_title'], $notification['notification_msg']);
    }
}
public function test_cronjob_bkp()
{
    // Fetch all complaints
    $complaints = DB::table('issue_log_management')->select('pk', 'created_by', 'issue_status','created_date') ->where('created_date', '>', '2025-01-27')->get();
    DB::table('app_notification')
    ->whereNotExists(function ($query) {
        $query->select(DB::raw(1))
              ->from('issue_log_management')
              ->whereColumn('issue_log_management.pk', 'app_notification.issue_log_management_pk');
    })
    ->delete();

    foreach ($complaints as $complaint) {
        // Fetch the latest notification for this complaint (if any)
        $latestNotification = DB::table('app_notification')
            ->where('issue_log_management_pk', $complaint->pk)
            ->where('complaint_status', $complaint->issue_status)
            
            ->orderBy('created_on', 'desc')
            ->first();
        
        //     DB::table('app_notification')
        // ->where('issue_log_management_pk', $complaint->pk)
        // ->where('complaint_status', $complaint->issue_status)
        // ->where('notification_to', $complaint->created_by)
        // ->delete();
        // Check if there is no notification or if the status has changed
        if (!$latestNotification || $latestNotification->complaint_status != $complaint->issue_status) {
            // print_r($latestNotification);die;
                 DB::table('app_notification')
        ->where('issue_log_management_pk', $complaint->pk)
        ->where('notification_to', $complaint->created_by)
        ->delete();
            // Prepare data for inserting into notifications table
        // Determine the status label based on issue_status
     

            $statusLabel = '';

            switch ($complaint->issue_status) {
                case 0:
                    $statusLabel = 'Complaint Reported';
                    break;
                case 1:
                    $statusLabel = 'Report is Open and Work in Progress';
                    break;
                case 2:
                    $statusLabel = 'Complaint has been Resolved';
                    break;
                case 3:
                    $statusLabel = 'Complaint is Pending';
                    break;
                case 6:
                    $statusLabel = 'Complaint is Reopened and Work in Progress';
                    break;
                default:
                    $statusLabel = 'Unknown Status';
                    break;
            }

            // Create the notification data with the full status message
            $notificationData = [
                'notification_title' => 'Complaint Status Updated',
                'notification_msg' => "The status of complaint ID {$complaint->pk} has changed to: {$statusLabel}",
                'notification_to' => $complaint->created_by,
                'issue_log_management_pk' => $complaint->pk,
                'status' => '2',
                'complaint_status' => $complaint->issue_status,
                'created_on' => now(),
            ];

            // Insert the notification into the table
            DB::table('app_notification')->insert($notificationData);

            // Call the function to send a push notification
            $this->sendPushNotification($complaint->created_by, $notificationData['notification_title'], $notificationData['notification_msg']);
        }
    }
}
    

// Function to send push notification to Android and iOS
public function sendPushNotification($userId, $title, $description)
{
    // Fetch FCM tokens for Android and iOS from the user table
    $user = DB::table('jbp_users')->where('user_id', $userId)->first();

    if (!$user) {
        return;
    }

    $fcmTokens = [];
  
    if (!empty($user->fcm_token_mobile_app)) {
        $fcmTokens[] = $user->fcm_token_mobile_app;
    }

    if (empty($fcmTokens)) {
        return; // No FCM tokens found, nothing to send
    }
// print_r($fcmTokens);die;
    // Prepare the payload for FCM
    $projectId = 'centcom-2e9e4'; // Replace with your actual Project ID
    $credentialsFilePath = Storage::path('json/file.json');

    $client = new GoogleClient();
    $client->setAuthConfig($credentialsFilePath);
    $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
    $client->refreshTokenWithAssertion();
    $token = $client->getAccessToken();

    if (!isset($token['access_token'])) {
        Log::error('Unable to obtain access token for FCM.');
        return; // Unable to obtain access token, skip sending the push notification
    }

    $access_token = $token['access_token'];
    $headers = [
        "Authorization: Bearer $access_token",
        'Content-Type: application/json'
    ];

    // Prepare payload for each token
    foreach ($fcmTokens as $fcm) {
        $data = [
            "message" => [
                "token" => $fcm,
                "notification" => [
                // "data" => [
                    "title" => $title,
                    "body" => $description
                ],
                "android" => [
                    "priority" => "high"
                ],
                "apns" => [
                    "headers" => [
                        "apns-priority" => "10",
                        "apns-topic" => "com.lbsnaa.centcom",
                    ],
                    "payload" => [
                        "aps" => [
                            "alert" => [
                                "title" => $title,
                                "body" => $description
                            ],
                            "sound" => "default"
                        ]
                    ]
                ]
            ]
        ];
        $payload = json_encode($data);

        // Send the push notification via CURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        $responseData = json_decode($response, true);
        print_r($responseData);die;
        if ($err) {
            Log::error("Push notification error: " . $err);
        } else {
            Log::info("Push notification response: " . $response);
        }
    }
}
public function update_notification_status(Request $request)
{
    // Validate that 'created_by' is provided and 'notification_id' is optional
    $request->validate([
        'created_by' => 'required|integer',
        'notification_id' => 'nullable|integer'
    ]);

    // Fetch the user ID and optional notification ID from the request
    $userId = $request->created_by;
    $notificationId = $request->notification_id;

    // Update logic based on whether 'notification_id' is provided
    if ($notificationId) {
        // Update a single notification if 'notification_id' is provided
        $updated = DB::table('app_notification')
            ->where('notification_to', $userId)
            ->where('id', $notificationId)
            ->update(['status' => 1]);
    } else {
        // Update all notifications for the user if 'notification_id' is not provided
        $updated = DB::table('app_notification')
            ->where('notification_to', $userId)
            ->update(['status' => 1]);
    }

    // Check if the update was successful
    // if ($updated) {
        return response()->json([
            'message' => 'Notification status updated successfully.',
            'status' => true
        ], 200);
    // } else {
    //     return response()->json([
    //         'message' => 'Failed to update notification status.',
    //         'status' => false
    //     ], 200);
    // }
}

function delete_all_notification(Request $request){
    $request->validate([
        'created_by' => 'required|integer'
    ]);

    // Fetch the user ID from the request
    $userId = $request->created_by;

    $updated = DB::table('app_notification')
            ->where('notification_to', $userId)
           
            ->update(['is_deleted' => 1]);

    //   $updated =  DB::table('app_notification')->where('notification_to', $userId)->delete();
        if ($updated) {
          
            return response()->json([
                'message' => 'notification deleted successfully.',
                'status' => true
            ], 200);
        } else {
          
            return response()->json([
                'message' => 'Failed',
                'status' => false
            ], 200);
        }
}
public function complaint_img(Request $request)
{
    // Initialize an array to store image URLs
    

    // Check if files are uploaded
    if ($request->hasFile('complaint_img')) {
        $image = $request->file('complaint_img');
        // foreach ($request->file('complaint_img') as $image) {
            // Validate if the file is valid
            if ($image->isValid()) {
                // Generate a unique file name with a timestamp to prevent file name collisions
                $fileName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . time() . '.' . $image->getClientOriginalExtension();

                // Move the file to the public folder (directly under public/complaints_img)
                $image->move(public_path('complaints_img'), $fileName);

                // Generate the full URL to the uploaded image
                $fullUrl = env('APP_URL') . '/complaints_img/' . $fileName;

                // Add the full URL to the imageUrls array
                $imageUrls = $fullUrl;
            } else {
                // Handle invalid image upload error
                return response()->json([
                    'status' => false,
                    'message' => 'There was an error uploading the image.',
                ], 400);
            }
        // }
    } else {
        // Return an error if no file is uploaded
        return response()->json([
            'status' => false,
            'message' => 'No image file was uploaded.',
        ], 400);
    }

    // Return the list of image URLs as a JSON response
    return response()->json([
        'status' => true,
        'message' => 'Images uploaded successfully.',
        'image_urls' => $imageUrls,  // Return the image URLs for further use
    ], 200);
}
public function delete_complaint_img(Request $request)
{
    $request->validate([
        'image_url' => 'required|string', // Ensure image_url is provided
    ]);

    $imageUrl = $request->input('image_url');
    $imagePath = str_replace(env('APP_URL') . '/complaints_img/', '', $imageUrl);

    $fullFilePath = public_path('complaints_img/' . $imagePath);

    if (file_exists($fullFilePath)) {
        if (unlink($fullFilePath)) {
            return response()->json([
                'status' => true,
                'message' => 'Image deleted successfully.',
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete the image.',
            ], 500);
        }
    } else {
        return response()->json([
            'status' => false,
            'message' => 'Image not found.',
        ], 404);
    }
}



    
    
}