<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use Illuminate\Http\Request;
use App\Models\{CalendarEvent, GroupTypeMasterCourseMasterMap, CourseGroupTimetableMapping, StudentCourseGroupMap, ClassSessionMaster, VenueMaster, FacultyMaster, CourseStudentAttendance};
use Yajra\DataTables\DataTables;
use Carbon\Carbon;

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
                    $actions = '<a href="' . route('attendance.mark', ['group_pk' => $row->group_pk, 'course_pk' => $row->Programme_pk]) . '" class="btn btn-primary btn-sm" data-id="' . $row->pk . '">Mark Attendance</a>';
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
            // Log or handle as needed
            // return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage());
        }

    }

    //    public function getAttendanceList(Request $request)
// {
//     try {
//         $perPage = 10;
//         $page = $request->get('page', 1);

    //         $fromDate = $request->from_date ? date('Y-m-d', strtotime($request->from_date)) : null;
//         $toDate = $request->to_date ? date('Y-m-d', strtotime($request->to_date)) : null;

    //         $query = Timetable::active()
//             ->with([
//                 'courseGroupTypeMaster:pk,course_name',
//                 'classSession:pk,shift_name,start_time,end_time',
//                 'venue:venue_id,venue_name',
//                 'faculty:pk,full_name'
//             ]);

    //         if ($request->programme) {
//             $query->where('course_master_pk', $request->programme);
//         }
//         if ($fromDate) {
//             $query->whereDate('mannual_starttime', '>=', $fromDate);
//         }
//         if ($toDate) {
//             $query->whereDate('mannual_end_time', '<=', $toDate);
//         }

    //         $data = $query->paginate($perPage, ['*'], 'page', $page);
//         $offset = ($data->currentPage() - 1) * $perPage;

    //         // Send original paginator + offset
//         $html = view('admin.attendance.partial.attendance', compact('data', 'offset'))->render();
//         $pagination = $data->appends($request->all())->links('pagination::bootstrap-5')->render();

    //         return response()->json([
//             'status' => 'success',
//             'html' => $html,
//             'pagination' => $pagination,
//         ]);
//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => 'error',
//             'message' => $e->getMessage(),
//         ], 500);
//     }
// }
    function markAttendanceView($group_pk, $course_pk)
    {
        if ($group_pk && $course_pk) {

            $courseGroup = CourseGroupTimetableMapping::with([
                'group',
                'course:pk,course_name',
                'timetable',
                'timetable.classSession:pk,shift_name,start_time,end_time',
                'timetable.venue:venue_id,venue_name',
                'timetable.faculty:pk,full_name',
            ])->where('group_pk', $group_pk)
                ->where('Programme_pk', $course_pk)
                ->first();


            return view('admin.attendance.mark-attendance', compact('group_pk', 'course_pk', 'courseGroup'));
        }
        return redirect()->back()->with('error', 'Invalid parameters');
    }

    function studentAttendanceList($group_pk, $course_pk)
    {
        // return view('admin.attendance.mark-attendance', []);

        if ($group_pk && $course_pk) {

            $groupTypeMaster = GroupTypeMasterCourseMasterMap::where('pk', $group_pk)
                ->where('course_name', $course_pk)
                ->first();

            if (!$groupTypeMaster) {
                return redirect()->back()->with('error', 'Group or Course not found');
            }

            $students = StudentCourseGroupMap::with(['studentsMaster:display_name,generated_OT_code,pk', 'attendance' => fn($q) => $q->where('course_master_pk', $course_pk)->where('student_course_group_map_pk', $group_pk)])
                ->where('group_type_master_course_master_map_pk', $groupTypeMaster->pk);

            return DataTables::of($students)
                ->addIndexColumn()
                ->addColumn('student_name', fn($row) => $row->studentsMaster->display_name ?? 'N/A')
                ->addColumn('student_code', fn($row) => $row->studentsMaster->generated_OT_code ?? 'N/A')
                ->addColumn(
                    'attendance_status',
                    function ($row) use ($course_pk, $group_pk) {
                        $courseStudent = CourseStudentAttendance::where('Student_master_pk', $row->studentsMaster->pk)
                            ->where('Course_master_pk', $course_pk)
                            ->where('student_course_group_map_pk', $group_pk)
                            ->first();


                        return '
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="student[' . $row->studentsMaster->pk . ']" id="present_[' . $row->studentsMaster->pk . ']" value="1" ' . ($courseStudent && $courseStudent->status == 1 ? 'checked' : '') . '>
                            <label class="form-check-label text-success" for="present_[' . $row->studentsMaster->pk . ']">Present</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="student[' . $row->studentsMaster->pk . ']" id="late_[' . $row->studentsMaster->pk . ']" value="2" ' . ($courseStudent && $courseStudent->status == 2 ? 'checked' : '') . '>
                            <label class="form-check-label text-warning" for="late_[' . $row->studentsMaster->pk . ']">Late</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="student[' . $row->studentsMaster->pk . ']" id="absent_[' . $row->studentsMaster->pk . ']" value="3" ' . ($courseStudent && $courseStudent->status == 3 ? 'checked' : '') . '>
                            <label class="form-check-label text-danger" for="absent_[' . $row->studentsMaster->pk . ']">Absent</label>
                        </div>
                        ';
                    }
                )
                ->addColumn(
                    'mdo_duty',
                    function ($row) use ($course_pk, $group_pk) {
                        $courseStudent = CourseStudentAttendance::where('Student_master_pk', $row->studentsMaster->pk)
                            ->where('Course_master_pk', $course_pk) 
                            ->where('student_course_group_map_pk', $group_pk)
                            ->first();

                        return '
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="student[' . $row->studentsMaster->pk . ']" id="mdo_[' . $row->studentsMaster->pk . ']" value="4" ' . ($courseStudent && $courseStudent->status == 4 ? 'checked' : '') . '>
                        <label class="form-check-label text-dark" for="mdo_[' . $row->studentsMaster->pk . ']">MDO</label>
                    </div>
                    ';
                    }
                )
                ->addColumn(
                    'escort_duty',
                    function ($row) use ($course_pk, $group_pk) {
                        $courseStudent = CourseStudentAttendance::where('Student_master_pk', $row->studentsMaster->pk)
                            ->where('Course_master_pk', $course_pk) 
                            ->where('student_course_group_map_pk', $group_pk)
                            ->first();
                        return
                            '
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="student[' . $row->studentsMaster->pk . ']" id="escort_[' . $row->studentsMaster->pk . ']" value="5" ' . ($courseStudent && $courseStudent->status == 5 ? 'checked' : '') . '>
                        <label class="form-check-label text-dark" for="escort_[' . $row->studentsMaster->pk . ']">Escort</label>
                    </div>
                    ';
                    }
                )
                ->addColumn(
                    'medical_exempt',
                    function ($row) use ($course_pk, $group_pk) {
                        $courseStudent = CourseStudentAttendance::where('Student_master_pk', $row->studentsMaster->pk)
                            ->where('Course_master_pk', $course_pk) 
                            ->where('student_course_group_map_pk', $group_pk)
                            ->first();

                        return '
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="student[' . $row->studentsMaster->pk . ']"
                                id="medical_exempted_[' . $row->studentsMaster->pk . ']" value="6" ' . ($courseStudent && $courseStudent->status == 6 ? 'checked' : '') . '>
                            <label class="form-check-label text-dark" for="medical_exempted_[' . $row->studentsMaster->pk . ']">Medical Exempted</label>
                        </div>
                    ';
                    }
                )
                ->addColumn(
                    'other_exempt',
                    function ($row) use ($course_pk, $group_pk) {
                        $courseStudent = CourseStudentAttendance::where('Student_master_pk', $row->studentsMaster->pk)
                            ->where('Course_master_pk', $course_pk) 
                            ->where('student_course_group_map_pk', $group_pk)
                            ->first();

                        return '
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="student[' . $row->studentsMaster->pk . ']"
                                id="other_exempted_[' . $row->studentsMaster->pk . ']" value="7" ' . ($courseStudent && $courseStudent->status == 7 ? 'checked' : '') . '>
                            <label class="form-check-label text-dark" for="other_exempted_[' . $row->studentsMaster->pk . ']">Other Exempted</label>
                        </div>
                    ';
                    }
                )
                ->filterColumn('student_name', function ($query, $keyword) {
                    $query->whereHas('studentsMaster', function ($q) use ($keyword) {
                        $q->where('display_name', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('student_code', function ($query, $keyword) {
                    $query->whereHas('studentsMaster', function ($q) use ($keyword) {
                        $q->where('generated_OT_code', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns(['attendance_status', 'mdo_duty', 'escort_duty', 'medical_exempt', 'other_exempt'])
                ->make(true);




        } else {
            return redirect()->back()->with('error', 'Invalid parameters');
        }

    }

    public function save(Request $request)
    {

        try {

            $validated = $request->validate([
                'student' => 'required|array',
                'student.*' => 'required|in:1,2,3,4,5,6,7', // values from radio buttons
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
                            'student_course_group_map_pk' => $group_pk,
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
