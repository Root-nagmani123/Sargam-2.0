<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use Illuminate\Http\Request;
use App\Models\{CalendarEvent, GroupTypeMasterCourseMasterMap, CourseGroupTimetableMapping, StudentCourseGroupMap, ClassSessionMaster, VenueMaster, FacultyMaster, CourseStudentAttendance, Timetable, StudentMaster, MDOEscotDutyMap, StudentMedicalExemption, StudentMasterCourseMap};
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use App\DataTables\StudentAttendanceListDataTable;

class AttendanceController extends Controller
{
    function index()
    {
        try {
            // If Student-OT user accessing via user_attendance route, redirect to their attendance view
            if (hasRole('Student-OT') && request()->routeIs('attendance.user_attendance.index')) {
                $studentPk = auth()->user()->user_id;
                
                // Get the first course group mapping for this student
                $studentGroupMap = StudentCourseGroupMap::with('groupTypeMasterCourseMasterMap')
                    ->where('student_master_pk', $studentPk)
                    ->first();
                
                if ($studentGroupMap && $studentGroupMap->groupTypeMasterCourseMasterMap) {
                    $groupPk = $studentGroupMap->groupTypeMasterCourseMasterMap->pk;
                    $coursePk = $studentGroupMap->groupTypeMasterCourseMasterMap->course_name; // This is course_pk
                    
                    // Get the first timetable for this course and group (or use 0 as default)
                    $courseGroupTimetable = CourseGroupTimetableMapping::where('group_pk', $groupPk)
                        ->where('Programme_pk', $coursePk)
                        ->orderBy('timetable_pk', 'desc')
                        ->first();
                    
                    $timetablePk = $courseGroupTimetable ? $courseGroupTimetable->timetable_pk : 0;
                    
                    return redirect()->route('attendance.OT.student_mark.student', [
                        'group_pk' => $groupPk,
                        'course_pk' => $coursePk,
                        'timetable_pk' => $timetablePk,
                        'student_pk' => $studentPk
                    ]);
                }
                
                // If no course/group found, show error message
                return redirect()->back()->with('error', 'No attendance records found. Please contact administrator.');
            }

            $sessions = ClassSessionMaster::get();
            $maunalSessions = Timetable::select('class_session')
                ->where('class_session', 'REGEXP', '[0-9]{2}:[0-9]{2} [AP]M - [0-9]{2}:[0-9]{2} [AP]M')
                ->groupBy('class_session')
                ->select('class_session')
                ->get();


            $courseMasterPK = CalendarEvent::active()->select('course_master_pk')->groupBy('course_master_pk')->get()->toArray();
         $courseMasters = CourseMaster::whereIn('course_master.pk', $courseMasterPK)
                        ->select('course_master.course_name', 'course_master.pk');

                    if (hasRole('Student-OT')) {

                        $courseMasters = $courseMasters->join(
                            'student_master_course__map',
                            'student_master_course__map.course_master_pk',
                            '=',
                            'course_master.pk'
                        )
                        ->where('student_master_course__map.student_master_pk', auth()->user()->user_id);
                    }
                    $courseMasters->where('course_master.active_inactive', 1);


                    $courseMasters = $courseMasters->get()->toArray();


            return view('admin.attendance.index', compact('courseMasters', 'sessions', 'maunalSessions'));
        } catch (\Exception $e) {
            dd($e->getMessage());
            // Handle the exception
            return redirect()->route('attendance.index')->with('error', 'An error occurred: ' . $e->getMessage());
        }

    }

    function getAttendanceList(Request $request)
    {
        $backUrl = url()->previous();        // Full previous URL
$segments = explode('/', trim($backUrl, '/')); // Split by '/'
 $currentPath = end($segments);   
        
        try {
            $fromDate = $request->from_date ? date('Y-m-d', strtotime($request->from_date)) : null;
            $toDate = $request->to_date ? date('Y-m-d', strtotime($request->to_date)) : null;

            $query = CourseGroupTimetableMapping::with([
                'group',
                'course:pk,course_name',
                'timetable',
                'timetable.classSession:pk,shift_name,start_time,end_time',
                'timetable.venue:venue_id,venue_name',
                'timetable.faculty:pk,full_name',
            ]);

            $query->whereHas('timetable', function ($q) use ($fromDate, $toDate, $request) {

                if ($fromDate) {
                    $q->whereDate('START_DATE', '>=', $fromDate);
                }
                if ($toDate) {
                    $q->whereDate('END_DATE', '<=', $toDate);
                }

                if ($request->attendance_type === 'manual') {
                    $q->where('session_type', 2)
                        ->where('class_session', $request->session_value);
                } elseif ($request->attendance_type === 'normal') {
                    $q->where('session_type', 1)
                        ->where('class_session', $request->session_value);
                } elseif ($request->attendance_type === 'full_day') {
                    $q->where('full_day', 1);
                }
            });

            if (!empty($request->programme)) {
                $query->where('Programme_pk', $request->programme);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('programme_name', fn($row) => optional($row->course)->course_name ?? 'N/A')
                ->addColumn('mannual_starttime', function ($row) {
                    $startDate = optional($row->timetable)->START_DATE;
                    $endDate   = optional($row->timetable)->END_DATE;

                    $startTime = $startDate ? format_date($startDate, 'd/m/Y') : '';
                    $endTime   = $endDate   ? format_date($endDate, 'd/m/Y') : '';

                    return $startTime && $endTime
                        ? $startTime . ' to ' . $endTime
                        : $startTime . $endTime;

                })
                ->addColumn('session_time', content: function ($row) {
                    $classSession = optional($row->timetable)->class_session ?? '';
                    return $classSession;
                })

                ->addColumn('venue_name', fn($row) => optional($row->timetable)->venue->venue_name ?? 'N/A')
                ->addColumn('group_name', function ($row) {
                    return $row->group->group_name ?? 'NO GROUP';
                })

                ->addColumn('subject_topic', fn($row) => optional($row->timetable)->subject_topic ?? 'N/A')
                ->addColumn('faculty_name', fn($row) => optional($row->timetable)->faculty->full_name ?? 'N/A')
                ->addColumn('actions', function ($row) use ($currentPath) {

        // if ($currentPath === 'user_attendance') {
             if (hasRole('Student-OT')) {
            // Student-OT User Page - Show only their own attendance
            $studentPk = auth()->user()->user_id;
            return '<a href="' . route('attendance.OT.student_mark.student', [
                'group_pk' => $row->group_pk,
                'course_pk' => $row->Programme_pk,
                'timetable_pk' => $row->timetable_pk,
                'student_pk' => $studentPk
            ]) . '" class="btn btn-primary btn-sm 1">Show My Attendance</a>';
        } elseif (hasRole('Guest Faculty') || hasRole('Internal Faculty')) {
            // Faculty User Page
            return '<a href="' . route('attendance.student_mark', [
                'group_pk' => $row->group_pk,
                'course_pk' => $row->Programme_pk,
                'timetable_pk' => $row->timetable_pk
            ]) . '" class="btn btn-primary btn-sm 1">Show Attendance</a>';
        }else if($currentPath === 'send_notice'){
            return '<a href="' . route('attendance.send_notice', [
            'group_pk' => $row->group_pk,
            'course_pk' => $row->Programme_pk,
            'timetable_pk' => $row->timetable_pk
        ]) . '" class="btn btn-primary btn-sm">Send Notice</a>';
        }else if(hasRole('Training') || hasRole('Staff') || hasRole('Admin')){
             return '<a href="' . route('attendance.mark', [
            'group_pk' => $row->group_pk,
            'course_pk' => $row->Programme_pk,
            'timetable_pk' => $row->timetable_pk
        ]) . '" class="btn btn-primary btn-sm">Mark Attendance</a>';
        }
        else{
            return '<a href="' . route('attendance.mark', [
            'group_pk' => $row->group_pk,
            'course_pk' => $row->Programme_pk,
            'timetable_pk' => $row->timetable_pk
        ]) . '" class="btn btn-primary btn-sm">Mark Attendance</a>';
        }

        // Admin Page
       
    })

    ->rawColumns(['actions'])
    ->make(true);

            // return view('admin.attendance.partial.attendance', compact('attendanceData'));

        } catch (\Exception $e) {
            \Log::error('Error fetching attendance data: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);

        }
    }

    function markAttendanceView($group_pk, $course_pk, $timetable_pk)
    {
        try {

$backUrl = url()->current();                 // Full previous URL
$segments = explode('/', trim(parse_url($backUrl, PHP_URL_PATH), '/')); // URL ko path me convert karke split
$currentPath = $segments[1] ?? null;
// print_r($currentPath);die;
            $courseGroup = CourseGroupTimetableMapping::with([
                'course:pk,course_name',
                'timetable',
                'timetable.faculty:pk,full_name',
                'timetable.classSession:pk,start_time,end_time'
            ])
                ->where('group_pk', $group_pk)
                ->where('Programme_pk', $course_pk)
                ->where('timetable_pk', $timetable_pk)
                ->first();

            $dataTable = new StudentAttendanceListDataTable($group_pk, $course_pk, $timetable_pk);
            return $dataTable->render('admin.attendance.mark-attendance', [
                'group_pk' => $group_pk,
                'course_pk' => $course_pk,
                'courseGroup' => $courseGroup,
                'currentPath' => $currentPath,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching attendance data: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while fetching attendance data: ' . $e->getMessage());
        }
    }

    public function save(Request $request)
    {

        try {
            $validated = $request->validate([
                'student' => 'required|array',
                'student.*' => 'required|in:0,1,2,3,4,5,6,7', // values from radio buttons
            ]);

            $group_pk = $request->group_pk; // if you have session reference
            $course_pk = $request->course_pk;

            if ($request->student) {
                foreach ($request->student as $studentPk => $attendanceStatus) {
                    // Validate the attendance status
                    if (!in_array($attendanceStatus, [0, 1, 2, 3, 4, 5, 6, 7])) {
                        return redirect()->back()->with('error', 'Invalid attendance status for student ID: ' . $studentPk);
                    }

                    // Create or update the attendance record
                    CourseStudentAttendance::updateOrCreate(
                        [
                            'Student_master_pk' => $studentPk,
                            'course_master_pk' => $course_pk,
                            'group_type_master_course_master_map_pk' => $group_pk,
                            'timetable_pk' => $request->timetable_pk,
                        ],
                        [
                            'status' => $attendanceStatus,
                        ]
                    );

                }
            }
            return redirect()->back()->with('success', 'Attendance saved successfully.');
        } catch (\Exception $exception) {
            \Log::error('Error saving attendance: ' . $exception->getMessage());
            return redirect()->back()->with('error', 'An error occurred while saving attendance: ' . $exception->getMessage());
        }
    }

    public function OTmarkAttendanceView(Request $request, $group_pk, $course_pk, $timetable_pk, $student_pk){
        try {
        // Verify user has Student-OT role
            if (!hasRole('Student-OT')) {
                return redirect()->back()->with('error', 'Access denied. Student-OT role required.');
            }

            // Get student information
            $student = StudentMaster::where('pk', $student_pk)->firstOrFail();

            // Get course information
            $course = CourseMaster::where('pk', $course_pk)->firstOrFail();

            // Get filter parameters
            $filterDate = $request->input('filter_date') ? date('Y-m-d', strtotime($request->input('filter_date'))) : null;
            $filterSessionTime = $request->input('filter_session_time');
            $filterCourse = $request->input('filter_course');
            $archiveMode = $request->input('archive_mode', 'active'); // Default to 'active'

            // Get sessions for filter dropdown
            $sessions = ClassSessionMaster::get();
            $maunalSessions = Timetable::select('class_session')
                ->where('class_session', 'REGEXP', '[0-9]{2}:[0-9]{2} [AP]M - [0-9]{2}:[0-9]{2} [AP]M')
                ->groupBy('class_session')
                ->select('class_session')
                ->get();

            // Get archived courses for the student (only when in archive mode)
            $archivedCourses = [];
            if ($archiveMode === 'archive') {
                $archivedCourses = CourseMaster::join('student_master_course__map', 'student_master_course__map.course_master_pk', '=', 'course_master.pk')
                    ->where('student_master_course__map.student_master_pk', $student_pk)
                    ->whereNotNull('course_master.end_date')
                    ->whereDate('course_master.end_date', '<', Carbon::today())
                    ->select('course_master.pk', 'course_master.course_name', 'course_master.end_date')
                    ->orderBy('course_master.end_date', 'desc')
                    ->get();
            }

            // Query all course group timetable mappings for this student's course and group
            $query = CourseGroupTimetableMapping::with([
                'course:pk,course_name,end_date',
                'group:pk,group_name',
                'timetable',
                'timetable.classSession:pk,shift_name,start_time,end_time',
                'timetable.venue:venue_id,venue_name',
                'timetable.faculty:pk,full_name',
            ]);

            // Apply course filter if provided (only in archive mode)
            if ($archiveMode === 'archive' && $filterCourse) {
                $query->where('Programme_pk', $filterCourse);
                // Also need to update group_pk based on the selected course
                // Get the group for this student and the selected course
                $studentGroupMap = StudentCourseGroupMap::with('groupTypeMasterCourseMasterMap')
                    ->where('student_master_pk', $student_pk)
                    ->whereHas('groupTypeMasterCourseMasterMap', function($q) use ($filterCourse) {
                        $q->where('course_name', $filterCourse);
                    })
                    ->first();
                
                if ($studentGroupMap && $studentGroupMap->groupTypeMasterCourseMasterMap) {
                    $query->where('group_pk', $studentGroupMap->groupTypeMasterCourseMasterMap->pk);
                }
            } else {
                // Default behavior: use the original course and group
                $query->where('group_pk', $group_pk)
                      ->where('Programme_pk', $course_pk);
            }

            // Apply archive/active filter for timetable records
            $query->whereHas('timetable', function ($q) use ($archiveMode) {
                if ($archiveMode === 'archive') {
                    // Archive mode: show inactive attendance records
                    $q->where('active_inactive', 0);
                } else {
                    // Active mode: show active attendance records
                    $q->where('active_inactive', 1);
                }
            });

            // Apply date filter
            if ($filterDate) {
                $query->whereHas('timetable', function ($q) use ($filterDate) {
                    $q->whereDate('START_DATE', '=', $filterDate);
                });
            }

            // Apply session time filter
            if ($filterSessionTime) {
                $query->whereHas('timetable', function ($q) use ($filterSessionTime) {
                    // Check if filterSessionTime is a class_session_master_pk (numeric) or a manual session string
                    if (is_numeric($filterSessionTime)) {
                        $q->where('class_session', $filterSessionTime);
                    } else {
                        $q->where('class_session', $filterSessionTime);
                    }
                });
            }

            $courseGroups = $query->orderBy('timetable_pk', 'desc')->get();

            // Build attendance records array
            $attendanceRecords = [];
            $mdoDutyTypes = MDOEscotDutyMap::getMdoDutyTypes();

            foreach ($courseGroups as $courseGroup) {
                $timetableDate = optional($courseGroup->timetable)->START_DATE;
                $timetablePk = $courseGroup->timetable_pk;

                // Use the course and group from the filtered courseGroup (or fallback to original)
                $currentCoursePk = $courseGroup->Programme_pk ?? $course_pk;
                $currentGroupPk = $courseGroup->group_pk ?? $group_pk;

                // Get attendance record
                $attendance = CourseStudentAttendance::where([
                    ['Student_master_pk', '=', $student_pk],
                    ['course_master_pk', '=', $currentCoursePk],
                    ['group_type_master_course_master_map_pk', '=', $currentGroupPk],
                    ['timetable_pk', '=', $timetablePk]
                ])->first();

                // Initialize record data
                $record = [
                    'date' => $timetableDate ? format_date($timetableDate, 'd/m/Y') : 'N/A',
                    'session_time' => optional($courseGroup->timetable)->class_session ?? 'N/A',
                    'venue' => optional($courseGroup->timetable)->venue->venue_name ?? 'N/A',
                    'group' => optional($courseGroup->group)->group_name ?? 'N/A',
                    'topic' => optional($courseGroup->timetable)->subject_topic ?? 'N/A',
                    'faculty' => optional($courseGroup->timetable)->faculty->full_name ?? 'N/A',
                    'attendance_status' => 'Not Marked',
                    'duty_type' => null,
                    'exemption_type' => null,
                    'exemption_document' => null,
                    'exemption_comment' => null,
                ];

                // Format session time if classSession exists
                if ($courseGroup->timetable && $courseGroup->timetable->classSession) {
                    $record['session_time'] = optional($courseGroup->timetable->classSession)->start_time . ' - ' . optional($courseGroup->timetable->classSession)->end_time;
                }

                // Determine attendance status
                if ($attendance) {
                    $status = $attendance->status;
                    switch ($status) {
                        case 1:
                            $record['attendance_status'] = 'Present';
                            break;
                        case 2:
                            $record['attendance_status'] = 'Late';
                            break;
                        case 3:
                            $record['attendance_status'] = 'Absent';
                            break;
                        case 4:
                            $record['attendance_status'] = 'Present';
                            $record['duty_type'] = 'MDO';
                            break;
                        case 5:
                            $record['attendance_status'] = 'Present';
                            $record['duty_type'] = 'Escort';
                            break;
                        case 6:
                            $record['attendance_status'] = 'Present';
                            $record['exemption_type'] = 'Medical';
                            // Get medical exemption details
                            if ($timetableDate) {
                                $medicalExemption = StudentMedicalExemption::where([
                                    ['course_master_pk', '=', $currentCoursePk],
                                    ['student_master_pk', '=', $student_pk],
                                    ['active_inactive', '=', 1]
                                ])
                                ->where(function($query) use ($timetableDate) {
                                    $query->where('from_date', '<=', $timetableDate)
                                          ->where(function($q) use ($timetableDate) {
                                              $q->whereNull('to_date')
                                                ->orWhere('to_date', '>=', $timetableDate);
                                          });
                                })->first();

                                if ($medicalExemption) {
                                    $record['exemption_document'] = $medicalExemption->Doc_upload;
                                    $record['exemption_comment'] = $medicalExemption->Description;
                                }
                            }
                            break;
                        case 7:
                            $record['attendance_status'] = 'Present';
                            $record['exemption_type'] = 'Other';
                            // Get other exemption details
                            if ($timetableDate) {
                                $otherDutyType = $mdoDutyTypes['other'] ?? null;
                                if ($otherDutyType) {
                                    $otherExemption = MDOEscotDutyMap::where([
                                        ['course_master_pk', '=', $currentCoursePk],
                                        ['mdo_duty_type_master_pk', '=', $otherDutyType],
                                        ['selected_student_list', '=', $student_pk]
                                    ])->whereDate('mdo_date', '=', $timetableDate)->first();

                                    if ($otherExemption) {
                                        $record['exemption_comment'] = $otherExemption->Remark ?? null;
                                    }
                                }
                            }
                            break;
                    }
                } else {
                    // Check if student has exemptions even if attendance not marked
                    if ($timetableDate) {
                        // Check medical exemption
                        $medicalExemption = StudentMedicalExemption::where([
                            ['course_master_pk', '=', $currentCoursePk],
                            ['student_master_pk', '=', $student_pk],
                            ['active_inactive', '=', 1]
                        ])
                        ->where(function($query) use ($timetableDate) {
                            $query->where('from_date', '<=', $timetableDate)
                                  ->where(function($q) use ($timetableDate) {
                                      $q->whereNull('to_date')
                                        ->orWhere('to_date', '>=', $timetableDate);
                                  });
                        })->first();

                        if ($medicalExemption) {
                            $record['attendance_status'] = 'Present';
                            $record['exemption_type'] = 'Medical';
                            $record['exemption_document'] = $medicalExemption->Doc_upload;
                            $record['exemption_comment'] = $medicalExemption->Description;
                        } else {
                            // Check MDO/Escort/Other duties
                            // Check MDO
                            if (!empty($mdoDutyTypes['mdo'])) {
                                $mdoDuty = MDOEscotDutyMap::where([
                                    ['course_master_pk', '=', $currentCoursePk],
                                    ['mdo_duty_type_master_pk', '=', $mdoDutyTypes['mdo']],
                                    ['selected_student_list', '=', $student_pk]
                                ])->whereDate('mdo_date', '=', $timetableDate)->first();

                                if ($mdoDuty) {
                                    $record['attendance_status'] = 'Present';
                                    $record['duty_type'] = 'MDO';
                                }
                            }

                            // Check Escort
                            if (!$record['duty_type'] && !empty($mdoDutyTypes['escort'])) {
                                $escortDuty = MDOEscotDutyMap::where([
                                    ['course_master_pk', '=', $currentCoursePk],
                                    ['mdo_duty_type_master_pk', '=', $mdoDutyTypes['escort']],
                                    ['selected_student_list', '=', $student_pk]
                                ])->whereDate('mdo_date', '=', $timetableDate)->first();

                                if ($escortDuty) {
                                    $record['attendance_status'] = 'Present';
                                    $record['duty_type'] = 'Escort';
                                }
                            }

                            // Check Other
                            if (!$record['duty_type'] && !empty($mdoDutyTypes['other'])) {
                                $otherDuty = MDOEscotDutyMap::where([
                                    ['course_master_pk', '=', $currentCoursePk],
                                    ['mdo_duty_type_master_pk', '=', $mdoDutyTypes['other']],
                                    ['selected_student_list', '=', $student_pk]
                                ])->whereDate('mdo_date', '=', $timetableDate)->first();

                                if ($otherDuty) {
                                    $record['attendance_status'] = 'Present';
                                    $record['exemption_type'] = 'Other';
                                    $record['exemption_comment'] = $otherDuty->Remark ?? null;
                                }
                            }
                        }
                    }
                }

                $attendanceRecords[] = $record;
            }

            return view('admin.attendance.ot-student-view', compact(
                'course',
                'student',
                'attendanceRecords',
                'sessions',
                'maunalSessions',
                'filterDate',
                'filterSessionTime',
                'filterCourse',
                'archivedCourses',
                'archiveMode',
                'group_pk',
                'course_pk',
                'timetable_pk',
                'student_pk'
            ));

        } catch (\Exception $e) {
            \Log::error('Error fetching OT student attendance: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while fetching attendance data: ' . $e->getMessage());
        }
    }
}
