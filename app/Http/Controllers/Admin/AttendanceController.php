<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use Illuminate\Http\Request;
use App\Models\{CalendarEvent, GroupTypeMasterCourseMasterMap, CourseGroupTimetableMapping, StudentCourseGroupMap, ClassSessionMaster, VenueMaster, FacultyMaster, CourseStudentAttendance};
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use App\DataTables\StudentAttendanceListDataTable;

class AttendanceController extends Controller
{
    function index()
    {
        try {

            $courseMasterPK = CalendarEvent::active()->select('course_master_pk')->groupBy('course_master_pk')->get()->toArray();
            $courseMasters = CourseMaster::whereIn('pk', $courseMasterPK)->select('course_name', 'pk')->get()->toArray();

            return view('admin.attendance.index', compact('courseMasters'));
        } catch (\Exception $e) {

            // Handle the exception
            return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage());
        }

    }

    function getAttendanceList(Request $request)
    {
        try {

            $fromDate = $request->from_date ? date('Y-m-d', strtotime($request->from_date)) : null;
            $toDate = $request->to_date ? date('Y-m-d', strtotime($request->to_date)) : null;
            // $viewType = $request->view_type ?? null;

            $query = CourseGroupTimetableMapping::with([
                'group',
                'course:pk,course_name',
                'timetable',
                'timetable.classSession:pk,shift_name,start_time,end_time',
                'timetable.venue:venue_id,venue_name',
                'timetable.faculty:pk,full_name',
            ]);

            if ($fromDate || $toDate) {
                $query->whereHas('timetable', function ($q) use ($fromDate, $toDate) {
                    if ($fromDate) {
                        $q->whereDate('mannual_starttime', '>=', $fromDate);
                    }
                    if ($toDate) {
                        $q->whereDate('mannual_end_time', '<=', $toDate);
                    }
                });
            }


            if ($request->course_master_pk) {
                $query->where('Programme_pk', $request->course_master_pk);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('programme_name', fn($row) => optional($row->course)->course_name ?? 'N/A')
                ->addColumn('mannual_starttime', function ($row) {
                    $startTime = '';
                    if (optional($row->timetable)->mannual_starttime) {
                        $startTime = Carbon::parse(optional($row->timetable)->mannual_starttime)->format('Y-m-d');
                    }
                    return $startTime;
                })
                ->addColumn('session_time', function ($row) {
                    $classSession = optional(optional($row->timetable)->classSession);

                    $startTime = $classSession->start_time
                        ? Carbon::parse($classSession->start_time)->format('H:i')
                        : 'N/A';

                    $endTime = $classSession->end_time
                        ? Carbon::parse($classSession->end_time)->format('H:i')
                        : 'N/A';

                    return $startTime . ' - ' . $endTime;
                })

                ->addColumn('venue_name', fn($row) => optional($row->timetable)->venue->venue_name ?? 'N/A')
                ->addColumn('group_name', function ($row) {
                    return $row->group->group_name ?? 'NO GROUP';
                })

                ->addColumn('subject_topic', fn($row) => optional($row->timetable)->subject_topic ?? 'N/A')
                ->addColumn('faculty_name', fn($row) => optional($row->timetable)->faculty->full_name ?? 'N/A')
                ->addColumn('actions', function ($row) {
                    $actions = '<a href="' . route('attendance.mark', ['group_pk' => $row->group_pk, 'course_pk' => $row->Programme_pk, 'timetable_pk' => $row->timetable_pk]) . '" class="btn btn-primary btn-sm" data-id="' . $row->pk . '">Mark Attendance</a>';
                    return $actions;
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
                    if (!in_array($attendanceStatus, [1, 2, 3, 4, 5, 6, 7])) {
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
