<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use Illuminate\Http\Request;
use App\Models\{CalendarEvent, GroupTypeMasterCourseMasterMap, CourseGroupTimetableMapping, ClassSessionMaster, VenueMaster, FacultyMaster};
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


            if ($request->course_master_pk) {
                $query->where('Programme_pk', $request->course_master_pk);
            }
            if ($fromDate) {
                $query->whereDate('mannual_starttime', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('mannual_end_time', '<=', $toDate);
            }

            // $attendanceData = $query->get();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('programme_name', fn($row) => optional($row->course)->course_name ?? 'N/A')
                ->addColumn('mannual_starttime', function($row) {
                    $startTime = '';
                    if (optional($row->timetable)->mannual_starttime) {
                        $startTime = Carbon::parse(optional($row->timetable)->mannual_starttime)->format('Y-m-d');
                    }
                    return $startTime;
                })
                ->addColumn('session_time', function ($row) {

                    $startTime = '';
                    $endTime = '';
                    if (optional($row->timetable)->classSession->start_time) {
                        $startTime = Carbon::parse(optional($row->timetable)->classSession->start_time)->format('Y-m-d');
                    }
                    if(optional($row->timetable)->classSession->end_time) {
                        $endTime = Carbon::parse(optional($row->timetable)->classSession->end_time)->format('Y-m-d');
                    }

                    return $startTime . ' - ' . $endTime;
                })
                ->addColumn('venue_name', fn($row) => optional($row->timetable)->venue->venue_name ?? 'N/A')
                ->addColumn('subject_topic', fn($row) => optional($row->timetable)->subject_topic ?? 'N/A')
                ->addColumn('faculty_name', fn($row) => optional($row->timetable)->faculty->full_name ?? 'N/A')
                ->addColumn('actions', fn($row) => '<a href="javascript:void(0);">Mark Attendance</a>')
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
}
