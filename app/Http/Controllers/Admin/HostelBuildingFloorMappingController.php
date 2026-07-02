<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\DataTables\HostelBuildingFloorMappingDataTable;
use App\Models\{
    HostelBuildingFloorMapping,
    HostelBuildingMaster,
    HostelFloorMaster,
    CourseMaster
};
use App\Imports\AssignHostelToStudent;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\DataTables\OTHostelRoomDetailsDataTable;

class HostelBuildingFloorMappingController extends Controller
{
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
        $courses = CourseMaster::where('active_inactive', 1)
            ->orderBy('course_name')
            ->pluck('course_name', 'pk');
        return $dataTable->render('admin.building_floor_mapping.assign_student', compact('courses'));
    }

    public function previewAssignHostelToStudent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_master_pk' => 'required|integer|exists:course_master,pk',
            'file'             => 'required|mimes:xlsx,xls,csv|max:10248',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $import = new AssignHostelToStudent((int) $request->course_master_pk, true);
            Excel::import($import, $request->file('file'));

            if (count($import->failures) > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation errors found in Excel file.',
                    'failures' => $import->failures,
                ], 422);
            }

            return response()->json([
                'status' => 'success',
                'rows' => $import->rows,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Excel import failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function assignHostelToStudent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_master_pk' => 'required|integer|exists:course_master,pk',
            'file'             => 'required|mimes:xlsx,xls,csv|max:10248',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $import = new AssignHostelToStudent((int) $request->course_master_pk);
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

    public function import(Request $request)
    {
        $courses = CourseMaster::where('active_inactive', 1)
            ->orderBy('course_name')
            ->pluck('course_name', 'pk');

        if ($request->isMethod('post')) {
            $validator = Validator::make($request->all(), [
                'course_master_pk' => 'required|integer|exists:course_master,pk',
                'file'             => 'required|mimes:xlsx,xls,csv|max:10248',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with('courses', $courses);
            }

            try {
                $import = new AssignHostelToStudent((int) $request->course_master_pk);
                Excel::import($import, $request->file('file'));

                if (!empty($import->failures)) {
                    return redirect()->back()
                        ->with('failures', $import->failures)
                        ->with('error', 'Import failed. Please fix the errors below.')
                        ->with('selected_course', $request->course_master_pk)
                        ->withInput();
                }

                return redirect()->route('hostel.building.map.assign.student')
                    ->with('success', 'Students assigned successfully.');
            } catch (\Throwable $e) {
                return redirect()->back()
                    ->with('error', 'An unexpected error occurred during import. Please check your file.')
                    ->withInput();
            }
        }

        return view('admin.building_floor_mapping.import', compact('courses'));
    }

    function export()
    {
        $fileName = 'hostel_room_details_' . date('Y_m_d_H_i_s') . '.xlsx';
        return Excel::download(new \App\Exports\OTHostelRoomDetailsExport, $fileName);
    }
}
