<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\{MDODutyTypeMaster, StudentMaster, CourseMaster, MDOEscotDutyMap};
use App\Http\Requests\MDOEscrotExemptionRequest;

class MDOEscrotExemptionController extends Controller
{
    public function index()
    {
        $exemptions = MDOEscotDutyMap::with([
            'courseMaster' => fn($q) => $q->select('pk', 'course_name'),
            'mdoDutyTypeMaster' => fn($q) => $q->select('pk', 'mdo_duty_type_name'),
            'studentMaster' => fn($q) => $q->select('pk', 'display_name', 'generated_OT_code')
        ])->orderBy('pk', 'desc')->paginate(10);

        return view('admin.mdo_escrot_exemption.index', compact('exemptions'));
    }

    public function create()
    {
        try {
            $courseMaster = CourseMaster::where('active_inactive', 1)->pluck('course_name', 'pk')->toArray();
            $MDODutyTypeMaster = MDODutyTypeMaster::where('active_inactive', 1)->pluck('mdo_duty_type_name', 'pk')->toArray();
            $students = []; // Initialize empty array, will be populated via AJAX

            return view('admin.mdo_escrot_exemption.create', compact('MDODutyTypeMaster', 'courseMaster', 'students'));
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error occurred while fetching MDO Duty Type Master.']);
        }
    }

    public function edit($id)
    {
        $MDODutyTypeMaster = MDODutyTypeMaster::where('active_inactive', 1)->pluck('mdo_duty_type_name', 'pk')->toArray();

        $mdoDutyType = MDOEscotDutyMap::with(['studentMaster'])->findOrFail($id);

        return view('admin.mdo_escrot_exemption.edit', compact('id', 'MDODutyTypeMaster', 'mdoDutyType'));
    }

    function store(MDOEscrotExemptionRequest $request)
    {
        try {
            
            $data = [];

            if ($request->selected_student_list != null) {
                foreach ($request->selected_student_list as $student_id) {
                    $data[] = [
                        'course_master_pk' => $request->course_master_pk,
                        'mdo_duty_type_master_pk' => $request->mdo_duty_type_master_pk,
                        'mdo_date' => $request->mdo_date,
                        'Time_from' => $request->Time_from,
                        'Time_to' => $request->Time_to,
                        'Remark' => $request->Remark,
                        'selected_student_list' => $student_id,
                    ];
                }
            }

            MDOEscotDutyMap::insert($data);

            return redirect()->route('mdo-escrot-exemption.index')->with('success', 'MDO Escrot Exemption created successfully.');
        } catch (\Exception $e) {
            dd($e->getMessage());
            return response()->json(['status' => false, 'message' => 'Error occurred while creating MDO Escrot Exemption.']);
        }
    }

    function getStudentListAccordingToCourse(Request $request)
    {
        try {

            $course = CourseMaster::findOrFail($request->selectedCourses);

            if (!$course) {
                return response()->json(['status' => false, 'message' => 'Course not found.']);
            }

            if (!empty($course->studentMasterCourseMap)) {

                $alreadyAssignedStudents = MDOEscotDutyMap::
                where('course_master_pk', $course->pk)
                ->whereDate('mdo_date', $request->selectedDate)
                ->pluck('selected_student_list')->toArray();
                

                // dd($alreadyAssignedStudents);

                
                $students = [];
                $students = $course->studentMasterCourseMap->map(function ($student) {
                    $studentMaster = StudentMaster::where('pk', $student['student_master_pk'])->first();
                    // print_r($studentMaster); exit;
                    if( !$studentMaster ) {
                        return null; 
                    }
                    $students['pk'] = $student['student_master_pk'];
                    $students['display_name'] = $studentMaster ? $studentMaster->display_name : null;
                    $students['ot_code'] = $studentMaster ? $studentMaster->generated_OT_code : null;
                    return $students;
                });
                // dd($students->toArray());
                $filteredStudents = $students->reject(function ($student) use ($alreadyAssignedStudents) {
                    return in_array($student['pk'], $alreadyAssignedStudents);
                })->values();
                
                return response()->json(['status' => true, 'message' => 'Student list fetched successfully.', 'students' => $filteredStudents]);
            }

            return response()->json(['status' => false, 'message' => 'No students found for this course.']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error occurred while fetching student list.']);
        }
    }

    function update(Request $request) {
        try{
            
            $mdoDutyType = MDOEscotDutyMap::findOrFail(decrypt($request->pk));
            $mdoDutyType->update($request->only('mdo_duty_type_master_pk', 'mdo_date', 'Time_from', 'Time_to'));

            return redirect()->route('mdo-escrot-exemption.index')->with('success', 'MDO Escrot Exemption updated successfully.');
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error occurred while updating MDO Escrot Exemption.']);
        }
    }
}