<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoursesMaster;
use App\Models\CourseTeamMaster;
use Illuminate\Http\Request;
use App\Http\Requests\ProgrammeRequest;
use App\Models\{EmployeeMaster, CourseMaster, FacultyMaster};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use App\DataTables\CourseMasterDataTable;


class CourseController extends Controller
{
    public function index(CourseMasterDataTable $dataTable)
    {
        return $dataTable->render('admin.programme.index');
    }

    public function create()
    {
        // $deputationEmployeeList = EmployeeMaster::getDeputationEmployeeListNameAndPK();
        $facultyList = FacultyMaster::pluck('full_name', 'pk')->toArray();

        return view('admin.programme.create', compact('facultyList'));
    }

    public function edit(string $id)
    {
        try {
            $courseMasterObj = CourseMaster::findOrFail(decrypt($id));
            // $deputationEmployeeList = EmployeeMaster::getDeputationEmployeeListNameAndPK();
            $facultyList = FacultyMaster::pluck('full_name', 'pk')->toArray();

            $courseCordinatorMaterData = $courseMasterObj->courseCordinatorMater->map(function($item){
                $item['Coordinator_name'] = $item['Coordinator_name'];
                $item['Assistant_Coordinator_name'] = $item['Assistant_Coordinator_name'];
                return $item;
            })->toArray();
            
            $coordinator_name = array_column($courseCordinatorMaterData, 'Coordinator_name');
            $assistant_coordinator_name = array_column($courseCordinatorMaterData, 'Assistant_Coordinator_name');
            $coordinator_name = $coordinator_name[0] ?? '';
            
            return view('admin.programme.create', compact('courseMasterObj', 'facultyList', 'coordinator_name', 'assistant_coordinator_name'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Invalid course ID');
        }

    }

    public function store(ProgrammeRequest $request)
    {
        DB::beginTransaction();
        try {

            $validated = $request->validated();

            $validated['courseyear'] = date('Y', strtotime($validated['courseyear']));
            $validated['startdate'] = date('Y-m-d', strtotime($validated['startdate']));
            $validated['enddate'] = date('Y-m-d', strtotime($validated['enddate']));

            $courseMasterObj = null;

            if( $request->course_id ) {

                // Update existing course

                $courseMasterObj = CourseMaster::findOrFail(decrypt($request->course_id));
                $courseMasterObj->update([
                    'course_name' => $validated['coursename'],
                    'couse_short_name' => $validated['courseshortname'],
                    'course_year' => $validated['courseyear'],
                    'start_year' => $validated['startdate'],
                    'end_date' => $validated['enddate'],
                    'Modified_date' => now(),
                ]);
            } else {

                // Create new course

                $courseMasterObj = new CourseMaster();
                $courseMasterObj->fill([
                    'course_name' => $validated['coursename'],
                    'couse_short_name' => $validated['courseshortname'],
                    'course_year' => $validated['courseyear'],
                    'start_year' => $validated['startdate'],
                    'end_date' => $validated['enddate'],
                    'created_date' => now(),
                    'Modified_date' => now(),
                ]);
                $courseMasterObj->save();
            }

            if (!$courseMasterObj) {
                return redirect()->back()->with('error', 'Course creation failed');
            }

            // Delete existing course coordinators
            $courseMasterObj->courseCordinatorMater()->delete();


            foreach ($validated['assistantcoursecoordinator'] as $key => $value) {
                $courseMasterObj->courseCordinatorMater()->create([
                    'courses_master_pk' => $courseMasterObj->pk,
                    'Coordinator_name' => $validated['coursecoordinator'],
                    'Assistant_Coordinator_name' => $value,
                    'created_date' => now(),
                    'Modified_date' => now(),
                ]);
            }

            DB::commit();
            return redirect()->route('programme.index')->with('success', 'Course created successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Course creation error: ' . $e->getMessage());
            // return redirect()->back()->with('error', $e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong. Please try again.');
        }
    }
}
