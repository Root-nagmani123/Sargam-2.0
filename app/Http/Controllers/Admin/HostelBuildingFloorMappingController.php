<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\DataTables\HostelBuildingFloorMappingDataTable;
use App\Models\{
    HostelBuildingFloorMapping,
    HostelBuildingMaster,
    HostelFloorMaster
};
use App\Imports\AssignHostelToStudent;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\DataTables\OTHostelRoomDetailsDataTable;

class HostelBuildingFloorMappingController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:hostel.building.map.index', ['only' => ['index']]);
        $this->middleware('permission:hostel.building.map.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:hostel.building.map.edit', ['only' => ['edit', 'store']]);
    }
    public function index(HostelBuildingFloorMappingDataTable $dataTable)
    {
        return $dataTable->render('admin.building_floor_mapping.index');
    }

    public function create()
    {
        $hostelBuilding = HostelBuildingMaster::active()->get()->pluck('hostel_building_name', 'pk')->toArray();
        $hostelFloor = HostelFloorMaster::active()->get()->pluck('hostel_floor_name', 'pk')->toArray();
        return view('admin.building_floor_mapping.create', compact('hostelBuilding', 'hostelFloor'));
    }

    public function store(Request $request)
    {

        $request->validate([
            'hostelbuilding' => 'required',
            'hostel_floor_name' => 'required'
        ]);

        if ($request->pk) {
            $message = 'Hostel Building Floor mapping updated successfully.';
            $hostelBuildingFloor = HostelBuildingFloorMapping::findOrFail(decrypt($request->pk));
        } else {
            $message = 'Hostel Building Floor mapping created successfully.';
            $hostelBuildingFloor = new HostelBuildingFloorMapping();
        }
        $hostelBuildingFloor->hostel_building_master_pk = $request->hostelbuilding;
        $hostelBuildingFloor->hostel_floor_master_pk = $request->hostel_floor_name;
        $hostelBuildingFloor->active_inactive = 1;

        $hostelBuildingFloor->save();

        return redirect()->route('hostel.building.map.index')->with('success', $message);
    }

    public function edit($id)
    {
        $id = decrypt($id);
        $hostelFloorMapping = HostelBuildingFloorMapping::findOrFail($id);
        $hostelBuilding = HostelBuildingMaster::active()->get()->pluck('hostel_building_name', 'pk')->toArray();
        $hostelFloor = HostelFloorMaster::active()->get()->pluck('hostel_floor_name', 'pk')->toArray();
        // dd($hostelBuilding);
        return view('admin.building_floor_mapping.create', compact('hostelFloorMapping', 'hostelBuilding', 'hostelFloor'));
    }

    public function assignStudent(OTHostelRoomDetailsDataTable $dataTable)
    {
        return $dataTable->render('admin.building_floor_mapping.assign_student');
    }

    public function assignHostelToStudent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls,csv|max:10248',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $import = new AssignHostelToStudent();
            Excel::import($import, $request->file('file'));

            $failures = $import->failures;

            if (count($failures) > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation errors found in Excel file.',
                    'failures' => $failures,
                ], 422);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Students assigned successfully.',
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Excel import failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
