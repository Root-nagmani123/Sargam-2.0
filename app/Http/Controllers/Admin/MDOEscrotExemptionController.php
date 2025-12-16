<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\{MDODutyTypeMaster, StudentMaster, CourseMaster, MDOEscotDutyMap, FacultyMaster};
use App\Http\Requests\MDOEscrotExemptionRequest;
use App\DataTables\MDOEscrotExemptionDataTable;
use App\Services\NotificationService;
use App\Services\NotificationReceiverService;

class MDOEscrotExemptionController extends Controller
{
    public function index(MDOEscrotExemptionDataTable $dataTable)
    {
        $courseMaster = CourseMaster::where('active_inactive', '1')
            ->where('end_date', '>', now())
            ->orderBy('course_name')
            ->pluck('course_name', 'pk')
            ->toArray();
        
        // Get distinct years from mdo_date
        $years = DB::table('mdo_escot_duty_map')
            ->select(DB::raw('DISTINCT YEAR(mdo_date) as year'))
            ->whereNotNull('mdo_date')
            ->orderBy('year', 'desc')
            ->pluck('year', 'year')
            ->toArray();
        
        // Get duty types for filter - show all duty types
        $dutyTypes = MDODutyTypeMaster::orderBy('mdo_duty_type_name')
            ->pluck('mdo_duty_type_name', 'pk')
            ->toArray();
        
        return $dataTable->render('admin.mdo_escrot_exemption.index', compact('courseMaster', 'years', 'dutyTypes'));
    }

    public function create()
    {
        try {
            $courseMaster = CourseMaster::where('active_inactive', '1')
                ->where('end_date', '>', now())
                ->pluck('course_name', 'pk')
                ->toArray();
            $MDODutyTypeMaster = MDODutyTypeMaster::where('active_inactive', 1)->pluck('mdo_duty_type_name', 'pk')->toArray();
            $facultyMaster = FacultyMaster::where('active_inactive', 1)
                ->orderBy('full_name')
                ->pluck('full_name', 'pk')
                ->toArray();
            $students = []; // Initialize empty array, will be populated via AJAX

            return view('admin.mdo_escrot_exemption.create', compact('MDODutyTypeMaster', 'courseMaster', 'students', 'facultyMaster'));
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error occurred while fetching MDO Duty Type Master.']);
        }
    }

    public function edit($id)
    {
        $MDODutyTypeMaster = MDODutyTypeMaster::where('active_inactive', 1)->pluck('mdo_duty_type_name', 'pk')->toArray();
        $facultyMaster = FacultyMaster::where('active_inactive', 1)
            ->orderBy('full_name')
            ->pluck('full_name', 'pk')
            ->toArray();

        $mdoDutyType = MDOEscotDutyMap::with(['studentMaster'])->findOrFail($id);

        return view('admin.mdo_escrot_exemption.edit', compact('id', 'MDODutyTypeMaster', 'mdoDutyType', 'facultyMaster'));
    }

    function store(MDOEscrotExemptionRequest $request)
    {
        try {
            $insertedRecords = [];

            if ($request->selected_student_list != null) {
                foreach ($request->selected_student_list as $student_id) {
                    $record = MDOEscotDutyMap::create([
                        'course_master_pk' => $request->course_master_pk,
                        'mdo_duty_type_master_pk' => $request->mdo_duty_type_master_pk,
                        'mdo_date' => $request->mdo_date,
                        'Time_from' => $request->Time_from,
                        'Time_to' => $request->Time_to,
                        'Remark' => $request->Remark,
                        'selected_student_list' => $student_id,
                        'faculty_master_pk' => $request->faculty_master_pk ?? null,
                    ]);
                    
                    $insertedRecords[] = [
                        'record' => $record,
                        'student_id' => $student_id
                    ];
                }
            }

            // Send notifications to students
            try {
                $notificationService = app(NotificationService::class);
                $receiverService = app(NotificationReceiverService::class);
                
                // Get course and duty type information for notification
                $course = CourseMaster::find($request->course_master_pk);
                $dutyType = MDODutyTypeMaster::find($request->mdo_duty_type_master_pk);
                
                $courseName = $course ? $course->course_name : 'Course';
                $dutyTypeName = $dutyType ? $dutyType->mdo_duty_type_name : 'Duty';
                $mdoDate = date('d M Y', strtotime($request->mdo_date));
                $timeFrom = date('h:i A', strtotime($request->Time_from));
                $timeTo = date('h:i A', strtotime($request->Time_to));
                
                foreach ($insertedRecords as $item) {
                    // Get student user_id using NotificationReceiverService
                    $receiverUserId = $receiverService->getStudentUserId((int) $item['student_id']);
                    
                    if ($receiverUserId) {
                        $title = "{$dutyTypeName} Duty Assigned";
                        $message = "You have been assigned {$dutyTypeName} duty for {$courseName} on {$mdoDate} from {$timeFrom} to {$timeTo}.";
                        
                        if (!empty($request->Remark)) {
                            $message .= " Remark: {$request->Remark}";
                        }
                        
                        $notificationService->create(
                            $receiverUserId,
                            'mdo_escort_exemption',
                            'MDO/Escort Exemption',
                            $item['record']->pk,
                            $title,
                            $message
                        );
                    }
                }
            } catch (\Exception $e) {
                // Log error but don't fail the request
                \Log::error('Failed to send MDO/Escort exemption notifications: ' . $e->getMessage());
            }

            return redirect()->route('mdo-escrot-exemption.index')->with('success', 'MDO/Escort Exemption created successfully.');
        } catch (\Exception $e) {
            dd($e->getMessage());
            return response()->json(['status' => false, 'message' => 'Error occurred while creating MDO/Escort Exemption.']);
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
            $updateData = $request->only('mdo_duty_type_master_pk', 'mdo_date', 'Time_from', 'Time_to');
            
            // If duty type is not Escort, set faculty_master_pk to null
            $escortDutyTypeId = MDOEscotDutyMap::getMdoDutyTypes()['escort'] ?? null;
            if ($request->mdo_duty_type_master_pk != $escortDutyTypeId) {
                $updateData['faculty_master_pk'] = null;
            } else {
                $updateData['faculty_master_pk'] = $request->faculty_master_pk ?? null;
            }
            
            $mdoDutyType->update($updateData);

            return redirect()->route('mdo-escrot-exemption.index')->with('success', 'MDO/Escort Exemption updated successfully.');
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error occurred while updating MDO/Escort Exemption.']);
        }
    }
}