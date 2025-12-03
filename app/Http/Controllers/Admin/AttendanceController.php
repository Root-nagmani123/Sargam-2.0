<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use Illuminate\Http\Request;
use App\Models\{CalendarEvent, GroupTypeMasterCourseMasterMap, CourseGroupTimetableMapping, StudentCourseGroupMap, ClassSessionMaster, VenueMaster, FacultyMaster, CourseStudentAttendance, Timetable};
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use App\DataTables\StudentAttendanceListDataTable;

class AttendanceController extends Controller
{
    function index()
    {
        try {

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

        if ($currentPath === 'user_attendance') {
            // User Page
            return '<a href="' . route('attendance.student_mark', [
                'group_pk' => $row->group_pk,
                'course_pk' => $row->Programme_pk,
                'timetable_pk' => $row->timetable_pk
            ]) . '" class="btn btn-primary btn-sm 1">Show Attendance</a>';
        }else{
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
}
