<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use Illuminate\Http\Request;
use App\Models\{Timetable, GroupTypeMasterCourseMasterMap, ClassSessionMaster, VenueMaster, FacultyMaster};
use Yajra\DataTables\DataTables;

class AttendanceController extends Controller
{
    function index()
    {
        try {

            $courseMasterPK = Timetable::active()->select('course_master_pk')->groupBy('course_master_pk')->get()->toArray();
            $courseMasters = CourseMaster::whereIn('pk', $courseMasterPK)->select('course_name', 'pk')->get()->toArray();

            return view('admin.attendance.index', compact('courseMasters'));
        } catch (\Exception $e) {
            dd($e->getMessage());
            // Handle the exception
            // return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage());
        }

    }

    function getAttendanceList(Request $request)
    {
        try {

            $fromDate = $request->from_date ? date('Y-m-d', strtotime($request->from_date)) : null;
            $toDate = $request->to_date ? date('Y-m-d', strtotime($request->to_date)) : null;
            // $viewType = $request->view_type ?? null;

            $query = Timetable::active()
                ->with([
                'courseGroupTypeMaster:pk,course_name',
                'classSession:pk,shift_name,start_time,end_time',
                'venue:venue_id,venue_name',
                'faculty:pk,full_name'
            ]);

            if ($request->programme) {
                $query->where('course_master_pk', $request->programme);
            }
            if ($fromDate) {
                $query->whereDate('mannual_starttime', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('mannual_end_time', '<=', $toDate);
            }

            $attendanceData = $query->paginate(5);
            // dd($attendanceData);
            // dd($attendanceData);
            // dd($attendanceData);
            // if ($attendanceData->isEmpty()) {
            //     return response()->json(['status' => 'error', 'message' => 'No data found']);
            // }

            // $attendanceGroup = [];

            foreach ($attendanceData as $item) {
                if (!empty($item->group_name)) {
                    $groupList = json_decode($item->group_name);

                    $groups = GroupTypeMasterCourseMasterMap::whereIn('pk', $groupList)->get();

                    foreach ($groups as $group) {
                        $attendanceGroup[] = [
                            'programme_name' => optional($item->courseGroupTypeMaster)->course_name ?? 'N/A',
                            'group_name' => $group->group_name,
                            'course_master_pk' => $item->course_master_pk,
                            'mannual_starttime' => $item->mannual_starttime,
                            'mannual_end_time' => $item->mannual_end_time,
                            'session_time' => optional($item->classSession)->start_time . ' - ' . optional($item->classSession)->end_time,
                            'session_name' => optional($item->classSession)->shift_name ?? 'N/A',
                            'venue_name' => optional($item->venue)->venue_name ?? 'N/A',
                            'subject_topic' => $item->subject_topic,
                            'faculty_name' => optional($item->faculty)->full_name ?? 'N/A',
                        ];
                    }
                }
            }
            return view('admin.attendance.partial.attendance', compact('attendanceData'));

            // if (empty($attendanceList)) {
            //     return response()->json(['status' => 'error', 'message' => 'No data found']);
            // }

            // return response()->json(['status' => 'success', 'html' => $attendanceList]);

            // return DataTables::of($query)
            // ->addIndexColumn()
            // ->editColumn('programme_name', fn($row) => optional($row->courseGroupTypeMaster)->course_name ?? 'N/A')
            // ->editColumn('mannual_starttime', fn($row) => $row->mannual_starttime ?? 'N/A')
            // ->addColumn('session_time', fn($row) => optional($row->classSession)->start_time . ' - ' . optional($row->classSession)->end_time)
            // ->editColumn('venue_name', fn($row) => optional($row->venue)->venue_name ?? 'N/A')
            // ->editColumn('subject_topic', fn($row) => $row->subject_topic ?? 'N/A')
            // ->editColumn('faculty_name', fn($row) => optional($row->faculty)->full_name ?? 'N/A')
            // ->addColumn('actions', fn($row) => '<a href="#">Mark Attendance</a>')
            // ->rawColumns(['actions']) // allow HTML
            // ->make(true);
        } catch (\Exception $e) {
            dd($e->getMessage());
            // Log or handle as needed
            return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage());
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





    function create()
    {
        return view('admin.attendance.create');
    }
    function edit()
    {
        return view('admin.attendance.edit');
    }
    function show()
    {
        return view('admin.attendance.show');
    }
    function store(Request $request)
    {
        // Handle the request to store attendance data
        // Validate and save the data
        return redirect()->route('admin.attendance.index')->with('success', 'Attendance recorded successfully.');
    }

}
