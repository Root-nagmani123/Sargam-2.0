<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\{MDODutyTypeMaster, StudentMaster, CourseMaster, MDOEscotDutyMap};
use App\DataTables\MDOEscrotExemptionDataTable;

class MDOEscrotExemptionController extends Controller
{
    public function index(MDOEscrotExemptionDataTable $dataTable)
    {
        return $dataTable->render('admin.mdo_escrot_exemption.index');
    }

    public function create()
    {
        try {
            $courseMaster = CourseMaster::where('active_inactive', 1)->pluck('course_name', 'pk')->toArray();
            $MDODutyTypeMaster = MDODutyTypeMaster::where('active_inactive', 1)->pluck('mdo_duty_type_name', 'pk')->toArray();

            return view('admin.mdo_escrot_exemption.create', compact('MDODutyTypeMaster', 'courseMaster'));
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error occurred while fetching MDO Duty Type Master.']);
        }
    }

    public function edit($id)
    {
        return view('admin.mdo_escrot_exemption.edit', compact('id'));
    }

    function store(Request $request)
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

            if ($course->studentMaster) {
                $students = [];
                $students = $course->studentMaster->map(function ($student) {
                    $students['pk'] = $student['pk'];
                    $students['display_name'] = $student['display_name'];
                    return $students;
                });

                return response()->json(['status' => true, 'message' => 'Student list fetched successfully.', 'students' => $students]);
            }

            return response()->json(['status' => false, 'message' => 'No students found for this course.']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error occurred while fetching student list.']);
        }
    }
}
//