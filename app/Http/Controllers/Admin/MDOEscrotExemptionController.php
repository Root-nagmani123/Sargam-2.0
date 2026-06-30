<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\{MDODutyTypeMaster, StudentMaster, CourseMaster, MDOEscotDutyMap, FacultyMaster};
use App\Http\Requests\MDOEscrotExemptionRequest;
use App\DataTables\MDOEscrotExemptionDataTable;
use App\Imports\MDOEscrotExemptionImport;
use App\Exports\MDOEscrotExemptionTemplateExport;
use App\Services\NotificationService;
use App\Services\NotificationReceiverService;
use Maatwebsite\Excel\Facades\Excel;

class MDOEscrotExemptionController extends Controller
{
    public function index(MDOEscrotExemptionDataTable $dataTable, Request $request)
    {
        // Filter by course status (Active/Archive)
        $filter = $request->get('filter', 'active'); // Default to 'active'
        $currentDate = now()->format('Y-m-d');
        
        // Get courses based on filter
        $courseMasterQuery = CourseMaster::where('active_inactive', '1');
        
        $data_course_id =  get_Role_by_course();
         if(!empty($data_course_id))
        {
            $courseMasterQuery->whereIn('pk',$data_course_id);
        }
        if ($filter === 'active') {
            // Active Courses: end_date > current date
            $courseMasterQuery->where('end_date', '>', $currentDate);
        } elseif ($filter === 'archive') {
            // Archive Courses: end_date < current date
            $courseMasterQuery->where('end_date', '<', $currentDate);
        }
        
        $courseMaster = $courseMasterQuery->orderBy('course_name')
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
        $dutyTypes = MDODutyTypeMaster::where('active_inactive', 1)
                ->orderBy('mdo_duty_type_name')
                ->pluck('mdo_duty_type_name', 'pk')
                ->toArray();

        $formCourseQuery = CourseMaster::where('active_inactive', '1');
        if (!empty($data_course_id)) {
            $formCourseQuery->whereIn('pk', $data_course_id);
        }
        $formCourses = $formCourseQuery
            ->where('end_date', '>', $currentDate)
            ->orderBy('course_name')
            ->pluck('course_name', 'pk')
            ->toArray();

        $MDODutyTypeMaster = MDODutyTypeMaster::where('active_inactive', 1)
            ->orderBy('mdo_duty_type_name')
            ->pluck('mdo_duty_type_name', 'pk')
            ->toArray();

        $facultyMaster = FacultyMaster::where('active_inactive', 1)
            ->orderBy('full_name')
            ->pluck('full_name', 'pk')
            ->toArray();

        return $dataTable->render(
            'admin.mdo_escrot_exemption.index',
            compact('courseMaster', 'years', 'dutyTypes', 'filter', 'formCourses', 'MDODutyTypeMaster', 'facultyMaster')
        );
    }

    public function create()
    {
        try {
            $data_course_id =  get_Role_by_course();
            $courseMaster = CourseMaster::where('active_inactive', '1');
            if(!empty($data_course_id))
            {
                $courseMaster->whereIn('pk',$data_course_id);
            }
            $courseMaster = $courseMaster->where('end_date', '>', now())
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

    public function editData($id)
    {
        $mdoDutyType = MDOEscotDutyMap::with(['studentMaster', 'courseMaster'])->findOrFail($id);

        $mdoDate = $mdoDutyType->mdo_date;
        if ($mdoDate) {
            $mdoDate = format_date($mdoDate, 'Y-m-d') ?: (is_string($mdoDate) ? substr($mdoDate, 0, 10) : $mdoDate);
        }

        return response()->json([
            'record' => [
                'pk' => encrypt($mdoDutyType->pk),
                'mdo_duty_type_master_pk' => $mdoDutyType->mdo_duty_type_master_pk,
                'mdo_date' => $mdoDate,
                'Time_from' => $mdoDutyType->Time_from ? substr($mdoDutyType->Time_from, 0, 5) : '',
                'Time_to' => $mdoDutyType->Time_to ? substr($mdoDutyType->Time_to, 0, 5) : '',
                'faculty_master_pk' => $mdoDutyType->faculty_master_pk,
                'faculty_master_pks' => $mdoDutyType->faculty_master_pks
                    ? array_values(array_filter(explode(',', $mdoDutyType->faculty_master_pks)))
                    : array_values(array_filter([$mdoDutyType->faculty_master_pk])),
                'student_name' => optional($mdoDutyType->studentMaster)->display_name ?? '—',
                'course_name' => optional($mdoDutyType->courseMaster)->course_name ?? '—',
            ],
        ]);
    }

    function store(MDOEscrotExemptionRequest $request)
    {
        try {
            $insertedRecords = [];

            // Faculty may be one or many. Store the full list as a comma-separated
            // string in faculty_master_pks and keep faculty_master_pk = first faculty
            // (for the existing belongsTo relation / list filters).
            $facultyPks = array_values(array_filter((array) $request->faculty_master_pk));
            $primaryFaculty = $facultyPks[0] ?? null;
            $facultyPksCsv = !empty($facultyPks) ? implode(',', $facultyPks) : null;

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
                        'faculty_master_pk' => $primaryFaculty,
                        'faculty_master_pks' => $facultyPksCsv,
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

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => true,
                    'message' => 'MDO/Escort Exemption created successfully.',
                ]);
            }

            return redirect()->route('mdo-escrot-exemption.index')->with('success', 'MDO/Escort Exemption created successfully.');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Error occurred while creating MDO/Escort Exemption.',
                ], 500);
            }

            return redirect()->route('mdo-escrot-exemption.index')->with('error', 'Error occurred while creating MDO/Escort Exemption.');
        }
    }

    /**
     * Download the bulk-upload sample template. When a course is selected, the
     * enrolled OTs are pre-filled so the user only fills Date and Session.
     */
    public function bulkTemplate(Request $request)
    {
        $coursePk = $request->course_master_pk ? (int) $request->course_master_pk : null;

        return Excel::download(
            new MDOEscrotExemptionTemplateExport($coursePk),
            'MDO_Escort_Exemption_Bulk_Template.xlsx'
        );
    }

    /**
     * Bulk create MDO/Escort Exemption duties from an uploaded Excel/CSV file.
     * Course, duty type, faculty and remark are shared; each row provides
     * Name, OT Code, Date and Session.
     */
    public function bulkStore(Request $request)
    {
        $escortDutyTypeId = MDOEscotDutyMap::getMdoDutyTypes()['escort'] ?? null;

        $request->validate([
            'course_master_pk'        => 'required|exists:course_master,pk',
            'mdo_duty_type_master_pk' => 'required|exists:mdo_duty_type_master,pk',
            'faculty_master_pk'       => 'nullable|exists:faculty_master,pk',
            'bulk_file'               => 'required|file|mimes:xlsx,xls,csv,txt|max:5120',
        ], [
            'bulk_file.required' => 'Please select a file to upload.',
            'bulk_file.mimes'    => 'The file must be an Excel (.xlsx, .xls) or CSV file.',
            'bulk_file.max'      => 'The file may not be larger than 5 MB.',
        ]);

        // Faculty is mandatory only for Escort duty (mirrors the single-add form).
        if ($escortDutyTypeId && (int) $request->mdo_duty_type_master_pk === (int) $escortDutyTypeId && empty($request->faculty_master_pk)) {
            return response()->json([
                'status'  => false,
                'message' => 'Faculty is required for Escort duty.',
                'errors'  => ['faculty_master_pk' => ['Faculty is required for Escort duty.']],
            ], 422);
        }

        try {
            $import = new MDOEscrotExemptionImport(
                (int) $request->course_master_pk,
                (int) $request->mdo_duty_type_master_pk,
                $request->faculty_master_pk ? (int) $request->faculty_master_pk : null,
                $request->Remark
            );

            Excel::import($import, $request->file('bulk_file'));

            $imported = $import->getImportedCount();
            $skipped  = $import->getSkippedCount();
            $errors   = $import->getErrors();

            // Notify the students whose duties were created (best-effort).
            $this->sendDutyNotifications(
                $import->getInsertedRecords(),
                (int) $request->course_master_pk,
                (int) $request->mdo_duty_type_master_pk
            );

            if ($imported === 0) {
                return response()->json([
                    'status'   => false,
                    'message'  => 'No records were imported. Please review the errors and try again.',
                    'imported' => 0,
                    'skipped'  => $skipped,
                    'errors'   => $errors,
                ], 422);
            }

            $message = "{$imported} record(s) imported successfully.";
            if ($skipped > 0) {
                $message .= " {$skipped} row(s) were skipped.";
            }

            return response()->json([
                'status'   => true,
                'message'  => $message,
                'imported' => $imported,
                'skipped'  => $skipped,
                'errors'   => $errors,
            ]);
        } catch (\Exception $e) {
            \Log::error('MDO/Escort bulk upload failed: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'Error occurred while processing the file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send "duty assigned" notifications for a set of newly created duty records.
     * Reads date/time from each record so it works for both single and bulk creates.
     *
     * @param array<int, array{record: \App\Models\MDOEscotDutyMap, student_id: int|string}> $insertedRecords
     */
    private function sendDutyNotifications(array $insertedRecords, int $coursePk, int $dutyTypePk): void
    {
        if (empty($insertedRecords)) {
            return;
        }

        try {
            $notificationService = app(NotificationService::class);
            $receiverService = app(NotificationReceiverService::class);

            $course = CourseMaster::find($coursePk);
            $dutyType = MDODutyTypeMaster::find($dutyTypePk);

            $courseName = $course ? $course->course_name : 'Course';
            $dutyTypeName = $dutyType ? $dutyType->mdo_duty_type_name : 'Duty';

            foreach ($insertedRecords as $item) {
                $record = $item['record'];
                $receiverUserId = $receiverService->getStudentUserId((int) $item['student_id']);

                if (!$receiverUserId) {
                    continue;
                }

                $mdoDate  = date('d M Y', strtotime($record->mdo_date));
                $timeFrom = date('h:i A', strtotime($record->Time_from));
                $timeTo   = date('h:i A', strtotime($record->Time_to));

                $title = "{$dutyTypeName} Duty Assigned";
                $message = "You have been assigned {$dutyTypeName} duty for {$courseName} on {$mdoDate} from {$timeFrom} to {$timeTo}.";
                if (!empty($record->Remark)) {
                    $message .= " Remark: {$record->Remark}";
                }

                $notificationService->create(
                    $receiverUserId,
                    'mdo_escort_exemption',
                    'MDO/Escort Exemption',
                    $record->pk,
                    $title,
                    $message
                );
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send MDO/Escort exemption notifications: ' . $e->getMessage());
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

                // Exclude students whose existing duty on this date OVERLAPS the requested
                // time window. A student may hold multiple duties on one day as long as the
                // times don't clash, so we treat any overlap (not just an exact slot match)
                // as a conflict: existing.from < new.to AND existing.to > new.from.
                $timeFrom = $request->selectedTimeFrom ? date('H:i:s', strtotime($request->selectedTimeFrom)) : null;
                $timeTo   = $request->selectedTimeTo ? date('H:i:s', strtotime($request->selectedTimeTo)) : null;

                $assignedQuery = MDOEscotDutyMap::where('course_master_pk', $course->pk)
                    ->whereDate('mdo_date', $request->selectedDate);

                if ($timeFrom && $timeTo) {
                    $assignedQuery->where('Time_from', '<', $timeTo)
                        ->where('Time_to', '>', $timeFrom);
                }

                $alreadyAssignedStudents = $assignedQuery->pluck('selected_student_list')->toArray();
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
            
            // If duty type is not Escort, clear faculty. Otherwise store the (possibly
            // multiple) selected faculty: full list in faculty_master_pks, first in
            // faculty_master_pk for the existing relation / list filters.
            $escortDutyTypeId = MDOEscotDutyMap::getMdoDutyTypes()['escort'] ?? null;
            if ($request->mdo_duty_type_master_pk != $escortDutyTypeId) {
                $updateData['faculty_master_pk'] = null;
                $updateData['faculty_master_pks'] = null;
            } else {
                $facultyPks = array_values(array_filter((array) $request->faculty_master_pk));
                $updateData['faculty_master_pk'] = $facultyPks[0] ?? null;
                $updateData['faculty_master_pks'] = !empty($facultyPks) ? implode(',', $facultyPks) : null;
            }
            
            $mdoDutyType->update($updateData);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => true,
                    'message' => 'MDO/Escort Exemption updated successfully.',
                ]);
            }

            return redirect()->route('mdo-escrot-exemption.index')->with('success', 'MDO/Escort Exemption updated successfully.');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Error occurred while updating MDO/Escort Exemption.',
                ], 500);
            }

            return redirect()->route('mdo-escrot-exemption.index')->with('error', 'Error occurred while updating MDO/Escort Exemption.');
        }
    }

    public function destroy($id)
    {
        try {
            $mdoDutyType = MDOEscotDutyMap::findOrFail($id);
            $mdoDutyType->delete();

            return redirect()->route('mdo-escrot-exemption.index')->with('success', 'MDO/Escort Exemption deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('mdo-escrot-exemption.index')->with('error', 'Error occurred while deleting MDO/Escort Exemption.');
        }
    }
}