<?php
 
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{ClassSessionMaster, CourseGroupTimetableMapping,CourseMaster, FacultyMaster, VenueMaster, SubjectMaster, SubjectModuleMaster, CalendarEvent, MemoTypeMaster,Timetable, CourseAttendanceNoticeMap};

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Models\MemoNoticeTemplate;
 
class CourseAttendanceNoticeMapController extends Controller
{
    //
   public function index(Request $request)
{
    // Get filter parameters
    $programNameFilter = $request->get('program_name', '');
    $typeFilter = $request->get('type', '');
    $statusFilter = $request->get('status', '');
    $searchFilter = $request->get('search', '');

    // Get initial notice records with course name
    $noticesQuery = DB::table('course_student_attendance as csa')
        ->join('student_notice_status as sns', 'sns.course_student_attendance_pk', '=', 'csa.pk')
        ->leftJoin('student_master as sm', 'csa.Student_master_pk', '=', 'sm.pk')
        ->leftJoin('timetable as t', 'sns.subject_topic', '=', 't.pk')
        ->leftJoin('course_master as cm', 'sns.course_master_pk', '=', 'cm.pk')
        ->select(
            'sns.pk as notice_id',
            'sns.pk as memo_notice_id',
            'sns.student_pk',
            'sns.course_master_pk',
            'sns.date_',
            'sns.subject_master_pk',
            'sns.subject_topic',
            'sns.venue_id',
            'sns.class_session_master_pk',
            'sns.faculty_master_pk',
            'sns.message',
            'sns.notice_memo',
            'sns.status',
            'sm.display_name as student_name',
            'sm.pk as student_id',
            't.subject_topic as topic_name',
            'cm.course_name',
            DB::raw('"Notice" as type_notice_memo')
        );

    // Apply filters on notices query
    if ($programNameFilter) {
        $noticesQuery->where('sns.course_master_pk', $programNameFilter);
    }

    if ($typeFilter !== null && $typeFilter !== '') {
        if ($typeFilter == '1') {
            // Notice: get notices that haven't been converted to memos
            $noticesQuery->where('sns.notice_memo', 1)->where('sns.status', '!=', 2);
        }
        // if $typeFilter == '0' (memo), we'll fetch memos separately later
    }

    if ($statusFilter !== null && $statusFilter !== '') {
        if ($statusFilter == '1') {
            $noticesQuery->where('sns.status', 1);
        } elseif ($statusFilter == '0') {
            $noticesQuery->where('sns.status', 2);
        }
    }

    $notices = $noticesQuery->get();

    $memos = collect(); // final result collection

    // If filtering for Memo type, query student_memo_status directly
    if ($typeFilter == '0') {
        $memoQuery = DB::table('student_memo_status')
            ->leftJoin('student_master as sm', 'student_memo_status.student_pk', '=', 'sm.pk')
            ->leftJoin('student_notice_status as sns', 'student_memo_status.student_notice_status_pk', '=', 'sns.pk')
            ->leftJoin('timetable as t', 'sns.subject_topic', '=', 't.pk')
            ->leftJoin('memo_conclusion_master as mcm', 'student_memo_status.memo_conclusion_master_pk', '=', 'mcm.pk')
            ->leftJoin('course_master as cm', 'student_memo_status.course_master_pk', '=', 'cm.pk')
            ->select(
                'student_memo_status.pk as memo_id',
                'student_memo_status.pk as memo_notice_id',
                'student_memo_status.student_notice_status_pk as notice_id',
                'student_memo_status.student_pk',
                'student_memo_status.communication_status',
                'student_memo_status.course_master_pk',
                'student_memo_status.date as date_',
                'student_memo_status.conclusion_remark',
                DB::raw('NULL as subject_master_pk'),
                DB::raw('NULL as subject_topic'),
                DB::raw('NULL as venue_id'),
                DB::raw('NULL as class_session_master_pk'),
                DB::raw('NULL as faculty_master_pk'),
                DB::raw('"Memo" as type_notice_memo'),
                'student_memo_status.message',
                DB::raw('2 as notice_memo'),
                'student_memo_status.status',
                'sm.display_name as student_name',
                'sm.pk as student_id',
                't.subject_topic as topic_name',
                'mcm.discussion_name',
                'cm.course_name'
            );

        if ($programNameFilter) {
            $memoQuery->where('student_memo_status.course_master_pk', $programNameFilter);
        }
        if ($statusFilter !== null && $statusFilter !== '') {
            if ($statusFilter == '1') {
                $memoQuery->where('student_memo_status.status', 1);
            } elseif ($statusFilter == '0') {
                $memoQuery->where('student_memo_status.status', 2);
            }
        }

        $memos = $memoQuery->get();
    } else {
        // For Notice or no type filter, process notices normally
        foreach ($notices as $notice) {
            if ($notice->status == 2) {
                // memo exists, try to pull memo data
                $memoData = DB::table('student_memo_status')
                    ->leftJoin('student_master as sm', 'student_memo_status.student_pk', '=', 'sm.pk')
                    ->leftJoin('student_notice_status as sns', 'student_memo_status.student_notice_status_pk', '=', 'sns.pk')
                    ->leftJoin('timetable as t', 'sns.subject_topic', '=', 't.pk')
                    ->leftJoin('memo_conclusion_master as mcm', 'student_memo_status.memo_conclusion_master_pk', '=', 'mcm.pk')
                    ->leftJoin('course_master as cm', 'student_memo_status.course_master_pk', '=', 'cm.pk')
                    ->where('student_memo_status.student_notice_status_pk', $notice->notice_id)
                    ->select(
                        'student_memo_status.pk as memo_id',
                        'student_memo_status.pk as memo_notice_id',
                        'student_memo_status.student_notice_status_pk as notice_id',
                        'student_memo_status.student_pk',
                        'student_memo_status.communication_status',
                        'student_memo_status.course_master_pk',
                        'student_memo_status.date as date_',
                        'student_memo_status.conclusion_remark',
                        DB::raw('NULL as subject_master_pk'),
                        DB::raw('NULL as subject_topic'),
                        DB::raw('NULL as venue_id'),
                        DB::raw('NULL as class_session_master_pk'),
                        DB::raw('NULL as faculty_master_pk'),
                        DB::raw('"Memo" as type_notice_memo'),
                        'student_memo_status.message',
                        DB::raw('2 as notice_memo'),
                        'student_memo_status.status',
                        'sm.display_name as student_name',
                        'sm.pk as student_id',
                        't.subject_topic as topic_name',
                        'mcm.discussion_name',
                        'cm.course_name'
                    )
                    ->first();

                if ($memoData) {
                    $memos->push($memoData);
                } else {
                    $memos->push($notice);
                }
            } else {
                $memos->push($notice);
            }
        }
    }

    // Apply additional filters to final collection (only if not fetching pure memo type)
    if ($typeFilter != '0') {

        if ($programNameFilter) {
            $memos = $memos->filter(function($item) use ($programNameFilter) {
                return isset($item->course_master_pk) && $item->course_master_pk == $programNameFilter;
            });
        }

        if ($typeFilter !== null && $typeFilter !== '') {
            if ($typeFilter == '1') {
                $memos = $memos->filter(function($item) {
                    return isset($item->notice_memo) && $item->notice_memo == 1;
                });
            }
        }

        if ($statusFilter !== null && $statusFilter !== '') {
            if ($statusFilter == '1') {
                $memos = $memos->filter(function($item) {
                    return isset($item->status) && $item->status == 1;
                });
            } elseif ($statusFilter == '0') {
                $memos = $memos->filter(function($item) {
                    return isset($item->status) && $item->status == 2;
                });
            }
        }

        if ($searchFilter !== null && $searchFilter !== '') {
            $memos = $memos->filter(function($item) use ($searchFilter) {
                return (isset($item->student_name) && stripos($item->student_name, $searchFilter) !== false)
                    || (isset($item->course_name) && stripos($item->course_name, $searchFilter) !== false)
                    || (isset($item->topic_name) && stripos($item->topic_name, $searchFilter) !== false);
            });
        }
    }

   
   

    // Get memo type and venues if needed
    $venue = VenueMaster::where('active_inactive', 1)->get();
    $memo_master = MemoTypeMaster::where('active_inactive', 1)->get();
    
    // Get courses for Program Name filter - only active courses (active_inactive = 1 and end_date > now)
    $courses = CourseMaster::where('active_inactive', 1)
        ->where('end_date', '>', now())
        ->orderBy('course_name', 'asc')
        ->get();

    // Paginate the collection
    $perPage = 10;
    $currentPage = request()->get('page', 1);
    $pagedData = $memos->slice(($currentPage - 1) * $perPage, $perPage)->values();
    $memos = new \Illuminate\Pagination\LengthAwarePaginator(
        $pagedData,
        $memos->count(),
        $perPage,
        $currentPage,
        ['path' => request()->url(), 'query' => request()->query()]
    );

    return view('admin.courseAttendanceNoticeMap.index', compact('memos', 'venue', 'memo_master', 'courses', 'programNameFilter', 'typeFilter', 'statusFilter', 'searchFilter'));
}

    public function index_bkp()
    {
     $memos = DB::table('student_notice_status')
    ->join('course_student_attendance as csa', 'student_notice_status.course_student_attendance_pk', '=', 'csa.pk')
    ->join('student_master as sm', 'csa.Student_master_pk', '=', 'sm.pk')  
    ->join('timetable as t', 'student_notice_status.subject_topic', '=', 't.pk')          
    ->select(
        'student_notice_status.pk as memo_notice_id',
        'student_notice_status.course_master_pk', 'student_notice_status.date_',
        'student_notice_status.subject_master_pk','student_notice_status.subject_topic',
        'student_notice_status.venue_id', 'student_notice_status.class_session_master_pk',
        'student_notice_status.faculty_master_pk','student_notice_status.message','student_notice_status.notice_memo',
        'student_notice_status.status',
        'sm.display_name as student_name','sm.pk as student_id','t.subject_topic as topic_name'
    )
    ->get();
    $venue = [];
    $memo_master = [];
if($memos[0]->status == 2){
    $venue = VenueMaster::where('active_inactive', 1)->get();
    // print_r($venue);die;
    $memo_master = MemoTypeMaster::where('active_inactive', 1)->get();
}

                    
                    // print_r($memos);die;
         return view('admin.courseAttendanceNoticeMap.index', compact('memos', 'venue', 'memo_master'));
    }
public function create(Request $request)
{
    $activeCourses = CourseMaster::where('active_inactive', '1')
        ->where('end_date', '>', now())
        ->get();
// print_r($activeCourses);die;
    return view('admin.courseAttendanceNoticeMap.create', compact('activeCourses'));
}
public function getSubjectByCourse(Request $request)
{
    $courseId = $request->course_id;
    $subjects = DB::table('timetable as t')
        ->join('subject_master as s', 't.subject_master_pk', '=', 's.pk')
        ->select('s.pk as subject_id', 's.subject_name')
        ->where('t.course_master_pk', $courseId)
        ->where('s.active_inactive', 1)
        ->groupBy('s.pk', 's.subject_name',)
        ->get();
   if ($subjects->isEmpty()) {
    return '<option value="">No subjects found</option>';
}

$html = '<option value="">Select Subject</option>';
foreach ($subjects as $subject) {
    $html .= '<option value="' . $subject->subject_id . '">' . e($subject->subject_name) . '</option>';
}

return $html;
}
function getTopicBysubject(Request $request)
{
    $courseId = $request->course_id;
$subjectId = $request->subject_master_id;

$topics = DB::table('timetable as t')
  ->where('t.course_master_pk', $courseId)
    ->where('t.subject_master_pk', $subjectId)
    ->select(
        't.pk','t.subject_topic'
    )
    ->get();
    if ($topics->isEmpty()) {
        return '<option value="">No topics found</option>';
    }
    $html = '<option value="">Select Topic</option>';
    foreach ($topics as $topic) {
        $html .= '<option value="' . $topic->pk . '">' . e($topic->subject_topic) . '</option>';
    }
    return $html;
}
public function gettimetableDetailsBytopic(Request $request)
{
    $topicId = $request->topic_id;
     $query = DB::table('timetable as t')
        ->leftJoin('faculty_master as f', 't.faculty_master', '=', 'f.pk')
        ->leftJoin('venue_master as v', 't.venue_id', '=', 'v.venue_id')
        ->where('t.pk', $topicId)
        ->select(
            't.*',
            'f.full_name as faculty_name',
            'v.venue_name',
            't.class_session as shift_name'
        );
    $timetable = $query->first();
    return response()->json($timetable);
}
public function conversation($id, $type)
{
    if (!$id || !is_numeric($id)) {
        return redirect()->back()->with('error', 'Invalid Memo/Notice ID.');
    }

    $memoNotice = collect(); // default empty collection
$memo_conclusion_master = collect(); // default empty collection
    if ($type == 'notice') {
        // $memoNotice = DB::table('notice_message_student_decip_incharge as nmsdi')
        //     ->leftjoin('student_notice_status as sns', 'nmsdi.student_notice_status_pk', '=', 'sns.pk')
        //     ->leftjoin('course_student_attendance as csa', 'sns.course_student_attendance_pk', '=', 'csa.pk')
        //     ->leftjoin('student_master as sm', 'csa.Student_master_pk', '=', 'sm.pk')
        //     ->where('nmsdi.student_notice_status_pk', $id)
        //     ->orderBy('nmsdi.created_date', 'asc')
        //     ->select(
        //         'nmsdi.*',
        //         'sns.pk as notice_id',
        //         'sns.status as notice_status',
        //         'sm.pk as student_id',
        //         'sm.display_name as student_name'
        //     )
        //     ->get();
        $memoNotice = DB::table('notice_message_student_decip_incharge as n')
    ->leftJoin('student_notice_status as sns', 'n.student_notice_status_pk', '=', 'sns.pk')
    ->leftJoin('course_student_attendance as csa', 'sns.course_student_attendance_pk', '=', 'csa.pk')
    ->leftJoin('student_master as sm', 'csa.Student_master_pk', '=', 'sm.pk')
    ->where('n.student_notice_status_pk', $id)
    ->orderBy('n.created_date', 'asc')
    ->select(
        'n.*',
        'sns.pk as notice_id',
        'sns.status as notice_status',
        'sm.pk as student_id',
        'sm.display_name as student_name'
    )
    ->get();

         $template_details = DB::table('student_notice_status as sns')
    ->leftJoin('timetable as t', 'sns.subject_topic', '=', 't.pk')
    ->leftJoin('student_master as sm', 'sns.student_pk', '=', 'sm.pk')
    ->leftJoin('course_master as cm', 't.course_master_pk', '=', 'cm.pk')
    ->leftJoin('memo_notice_templates as mnt', 'sns.course_master_pk', '=', 'mnt.course_master_pk')
    ->where('sns.pk', $id)
    ->where('mnt.memo_notice_type', 'Notice')
    ->select(
        't.course_master_pk',
        't.subject_topic',
        't.venue_id',
        't.class_session',
        'sm.display_name',
        'sm.generated_OT_code'
        ,'cm.course_name',
        'mnt.content',
        'mnt.director_name',
        'mnt.director_designation'
    )
    ->first();
    $memo_conclusion_master = DB::table('memo_conclusion_master')->where('active_inactive', 1)->get();

    } elseif ($type == 'memo') {
        $memoNotice = DB::table('memo_message_student_decip_incharge as mmsdi')
            ->leftjoin('student_memo_status as sms', 'mmsdi.student_memo_status_pk', '=', 'sms.pk')
            ->leftjoin('student_master as sm', 'sms.student_pk', '=', 'sm.pk')
            ->where('mmsdi.student_memo_status_pk', $id)
            ->orderBy('mmsdi.created_date', 'asc')
            ->select(
                'mmsdi.*',
                'sms.pk as notice_id',
                'sms.communication_status as notice_status',
                
                'sm.pk as student_id',
                'sm.display_name as student_name'
            )
            ->get();
            $memo_conclusion_master = DB::table('memo_conclusion_master')->where('active_inactive', 1)->get();

             $template_details = DB::table('student_notice_status as sns')
    ->leftJoin('timetable as t', 'sns.subject_topic', '=', 't.pk')
    ->leftJoin('student_master as sm', 'sns.student_pk', '=', 'sm.pk')
    ->leftJoin('course_master as cm', 't.course_master_pk', '=', 'cm.pk')
    ->leftJoin('memo_notice_templates as mnt', 'sns.course_master_pk', '=', 'mnt.course_master_pk')
    ->where('sns.pk', $id)
    ->where('mnt.memo_notice_type', 'Notice')
    ->select(
        't.course_master_pk',
        't.subject_topic',
        't.venue_id',
        't.class_session',
        'sm.display_name',
        'sm.generated_OT_code'
        ,'cm.course_name',
        'mnt.content',
        'mnt.director_name',
        'mnt.director_designation'
    )
    ->first();
          
            
    }

    // Common: map display_name based on role
    $memoNotice->transform(function ($item) {
        if ($item->role_type == 'f') {
            $creator = DB::table('users')->where('id', $item->created_by)->first();
            $item->display_name = $creator ? $creator->name : 'Admin';
        } elseif ($item->role_type == 's') {
            $student = DB::table('student_master')->where('pk', $item->created_by)->first();
            $item->display_name = $student ? $student->display_name : 'Student';
        } else {
            $item->display_name = 'Unknown';
        }
        return $item;
    });

// print_r($memoNotice);die;
    return view('admin.courseAttendanceNoticeMap.conversation', compact('id', 'memoNotice', 'type', 'memo_conclusion_master','template_details'));
}

function conversation_bkp($id,$type){

    // Validate the ID
    if (!$id || !is_numeric($id)) {
        return redirect()->back()->with('error', 'Invalid Memo/Notice ID.');
    }
    // Fetch the memo/notice details
    if($type == 'notice'){
 $memoNotice = DB::table('notice_message_student_decip_incharge as nmsdi')
    ->join('student_notice_status as sns', 'nmsdi.student_notice_status_pk', '=', 'sns.pk')
    ->join('course_student_attendance as csa', 'sns.course_student_attendance_pk', '=', 'csa.pk')
    ->join('student_master as sm', 'csa.Student_master_pk', '=', 'sm.pk')
    ->where('nmsdi.student_notice_status_pk', $id)
    ->orderBy('nmsdi.created_date', 'asc')
    ->select(
        'nmsdi.*',
        'sns.pk as notice_id',
        'sns.status as notice_status',
        'sm.pk as student_id',
        'sm.display_name as student_name'
    )
    ->get();

$memoNotice->transform(function ($item) {
    if ($item->role_type == 'f') {
        // Admin (From users table)
        $creator = DB::table('users')
            ->where('id', $item->created_by)
            ->first();
        $item->display_name = $creator ? $creator->name : 'Admin';
    } elseif ($item->role_type == 's') {
        // Student (From student_master table)
        $student = DB::table('student_master')
            ->where('pk', $item->created_by)
            ->first();
        $item->display_name = $student ? $student->display_name : 'Student';
    } else {
        $item->display_name = 'Unknown';
    }

    return $item;
});
    }else{

    }
// print_r($memoNotice);die;
     return view('admin.courseAttendanceNoticeMap.conversation', compact('id','memoNotice'));
}

//memo conversation


public function getStudentAttendanceBytopic(Request $request)
{
    try {
        $topicId = $request->topic_id;

        if (!$topicId) {
            return response()->json([
                'status' => false,
                'message' => 'Topic ID is required.'
            ]);
        }

        // Cast topicId to integer to ensure proper comparison
        $topicId = (int) $topicId;

        // Query to get students with Late (2) or Absent (3) status
        // Handle both integer and string status values
        $attendance = DB::table('course_student_attendance as a')
                ->join('student_master as s', 'a.Student_master_pk', '=', 's.pk')
                ->where('a.timetable_pk', $topicId)
                ->whereRaw("TRIM(a.status) REGEXP '^(2|3)$'")
                ->whereNotNull('s.pk')
                ->whereNotNull('s.display_name')
                ->where('s.display_name', '!=', '')
                ->select(
                    'a.pk as studnet_pk',
                    's.pk as pk',
                    's.display_name as display_name',
                    'a.status as attendance_status'
                )
                ->distinct()
                ->get();


        // If no students found, return empty array instead of error
        // This allows the UI to handle empty state gracefully
        if ($attendance->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'No defaulter students found for this topic.',
                'students' => []
            ]);
        }

        // Format the attendance data
        $students = $attendance->map(function ($student) {
            return [
                'pk' => (int) $student->pk,
                'display_name' => $student->display_name
            ];
        })->values(); // Reset array keys

        return response()->json([
            'status' => true,
            'message' => 'Student list fetched successfully.',
            'students' => $students
        ]);

    } catch (\Exception $e) {
        \Log::error('Error in getStudentAttendanceBytopic: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        
        return response()->json([
            'status' => false,
            'message' => 'Error occurred while fetching student list.',
            'error' => $e->getMessage() // optional for debugging
        ]);
    }
}
public function store_memo_notice(Request $request)
{
   
    $validated = $request->validate([
        'course_master_pk' => 'required|exists:course_master,pk',
        'date_memo_notice' => 'required|date',
        'subject_master_id' => 'required|exists:subject_master,pk',
        'topic_id' => 'required|exists:timetable,pk',
        'venue_id' => 'required',
        'class_session_master_pk' => 'required',
        'faculty_master_pk' => 'required',
        'selected_student_list' => 'required|array|min:1',
        'Remark' => 'nullable|string|max:500',
        'submission_type' => 'required|in:1,2',
    ]);

    // ✅ Fetch all required student info in one query
    $students = DB::table('course_student_attendance as a')
        ->join('student_master as s', 'a.Student_master_pk', '=', 's.pk')
        ->whereIn('a.Student_master_pk', $validated['selected_student_list'])
        ->select('a.pk as course_attendance_pk', 's.pk as student_pk')
        ->get()
        ->keyBy('course_attendance_pk'); // So we can access by course attendance PK

    $data = [];
    // print_r($students);
    // print_r($validated['selected_student_list']);die;

    foreach ($students as $studentId) {
    // $studentId is actually the course_student_attendance.pk
    // if (isset($students[$studentId])) {
        // $student = $students[$studentId]; 
    // print_r($studentId);die;


            $data[] = [
                'course_master_pk'           => $validated['course_master_pk'],
                'student_pk'                 => $studentId->student_pk,
                'date_'                      => $validated['date_memo_notice'],
                'subject_master_pk'          => $validated['subject_master_id'],
                'subject_topic'              => $validated['topic_id'],
                'venue_id'                   => $validated['venue_id'],
                'class_session_master_pk'    => $validated['class_session_master_pk'],
                'faculty_master_pk'          => $validated['faculty_master_pk'],
                'course_student_attendance_pk' => $studentId->course_attendance_pk,
                'message'                    => $validated['Remark'],
                'notice_memo'                => $validated['submission_type'],
            ];
          
        }
    // }
    // print_r($data);die;

    // ✅ Bulk insert
    $inserted = DB::table('student_notice_status')->insert($data);

    if ($inserted) {
        return redirect()->route('memo.notice.management.index')->with('success', 'Memo/Notice created successfully.');
    } else {
        return redirect()->back()->with('error', 'Failed to create Memo/Notice. Please try again.');
    }
}

function store_memo_notice_bkp(Request $request){
  
     $validated = $request->validate([
        'course_master_pk' => 'required|exists:course_master,pk',
        'date_memo_notice' => 'required|date',
        'subject_master_id' => 'required|exists:subject_master,pk',
        'topic_id' => 'required|exists:timetable,pk',
        'venue_id' => 'required',
        'class_session_master_pk' => 'required',
        'faculty_master_pk' => 'required',
        'selected_student_list' => 'required|array|min:1',
        'Remark' => 'nullable|string|max:500',
        'submission_type' => 'required|in:1,2', // Assuming 1 for Memo and 2 for Notice
    ]);
   

    $data = [];
    foreach ($validated['selected_student_list'] as $studentId) {
         $student_id = DB::table('course_student_attendance as a')
            ->Join('student_master as s', 'a.Student_master_pk', '=', 's.pk')
            ->where('a.pk', $studentId)
            ->select(
                'a.pk as studnet_pk',
            )
            ->get();
        $data[] = [
            'course_master_pk' => $validated['course_master_pk'],
            'student_pk' => $student_id->studnet_pk,
            'date_' => $validated['date_memo_notice'],
            'subject_master_pk' => $validated['subject_master_id'],
            'subject_topic' => $validated['topic_id'],
            'venue_id' => $validated['venue_id'],
            'class_session_master_pk' => $validated['class_session_master_pk'],
            'faculty_master_pk' => $validated['faculty_master_pk'],
            'course_student_attendance_pk' => $studentId,
            'message' => $validated['Remark'],
            'notice_memo' => $validated['submission_type'],
        ];

         

        // Insert into student_notice_status table

    }

    $courseAttendanceNotice = DB::table('student_notice_status')->insert($data);

    if ($courseAttendanceNotice) {
        return redirect()->route('memo.notice.management.index')->with('success', 'Memo/Notice created successfully.');
    } else {
        return redirect()->back()->with('error', 'Failed to create Memo/Notice. Please try again.');
    }
}
public function deleteMemoNotice($id)
{
    try {
        $memoNotice = DB::table('student_notice_status')->where('pk', $id)->first();

        if (!$memoNotice) {
            return redirect()->back()->with('error', 'Memo/Notice not found.');
        }

        DB::table('student_notice_status')->where('pk', $id)->delete();

        return redirect()->route('memo.notice.management.index')->with('success', 'Memo/Notice deleted successfully.');
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Failed to delete Memo/Notice. Please try again.');
    }
}
public function memo_notice_conversation(Request $request)
{
    $type = $request->input('type'); // 'memo' or 'notice'
// print_r($request->all());die;
    $validator = Validator::make($request->all(), [
        'memo_notice_id' => [
            'required',
            function ($attribute, $value, $fail) use ($type) {
                $table = $type === 'memo' ? 'student_memo_status' : 'student_notice_status';
                if (!DB::table($table)->where('pk', $value)->exists()) {
                    $fail("The selected ID does not exist in $table.");
                }
            }
        ],
        'date' => 'required|date',
        'time' => 'required',
        'message' => 'required|string|max:500',
        'document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:1048',
        'status' => 'required|in:1,2',
        'mark_of_deduction' => 'nullable|string|max:100',
        'conclusion_type' => 'nullable|exists:memo_conclusion_master,pk',
        'conclusion_remark' => 'nullable|string|max:500',
    ]);
    if($type === 'memo') {
    $validator->sometimes('conclusion_type', 'required_if:status,2', function ($input) {
        return $input->type === 'memo';
    });

    $validator->sometimes('conclusion_remark', 'required_if:status,2|max:500', function ($input) {
        return $input->type === 'memo';
    });
}

    if ($validator->fails()) {

    //    print_r($validator->errors());die;

        return redirect()->back()->withErrors($validator)->withInput();
    }

    // ✅ Fixed: Get validated data
    $validated = $validator->validated();

    // File upload
    // print_r($validated);die;
    $filePath = null;
    if ($request->hasFile('document')) {
        $file = $request->file('document');
        $filePath = $type == 'memo'
            ? $file->store('memo_conversation_documents', 'public')
            : $file->store('notice_documents', 'public');
    }

    // Define insert table and foreign key field
    $table = $type === 'memo' ? 'memo_message_student_decip_incharge' : 'notice_message_student_decip_incharge';
    $statusTable = $type === 'memo' ? 'student_memo_status' : 'student_notice_status';
    $foreignKey = $type === 'memo' ? 'student_memo_status_pk' : 'student_notice_status_pk';

    // Insert message
    $inserted = DB::table($table)->insert([
        $foreignKey => $validated['memo_notice_id'],
        'student_decip_incharge_msg' => $validated['message'],
        'doc_upload' => $filePath,
        'role_type' => 'f',
        'created_by' => auth()->user()->id ?? 1,
        'created_date' => now(),
    ]);

   if ($inserted) {
    // Update status if needed
    if ($validated['status'] == 2) {
        $query = DB::table($statusTable)
            ->where('pk', $validated['memo_notice_id']);

        if ($type === 'memo') {
            $query->update([
                'communication_status' => 2,
                'status' => 2
            ]);
        } else {
            $query->update([
                'status' => 2
            ]);
        }
    }


        // Optional: Memo conclusion update (if applicable)
        if ($type === 'memo' && isset($validated['conclusion_type'])) {
            DB::table('student_memo_status')
                ->where('pk', $validated['memo_notice_id'])
                ->update([
                    'memo_conclusion_master_pk' => $validated['conclusion_type'],
                    'conclusion_remark' => $validated['conclusion_remark'] ?? null,
                    'mark_of_deduction' => $validated['mark_of_deduction'] ?? null,
                    'decicion_taken_by' => auth()->user()->id ?? 1,
                    'decision_date' => now(),
                    'modified_date' => now(),
                ]);
        }else if ($type === 'notice' && isset($validated['conclusion_type'])) {
            // If status is not 2, still update modified_date
            DB::table('student_notice_status')
                ->where('pk', $validated['memo_notice_id'])
                ->update([
                    'conclusion_type_pk' => $validated['conclusion_type'],
                    'conclusion_remark' => $validated['conclusion_remark'] ?? null,
                    'mark_of_deduction' => $validated['mark_of_deduction'] ?? null,
                ]);
        }

        return redirect()->back()->with('success', ucfirst($type) . ' message created successfully.');
    }

    return redirect()->back()->with('error', 'Failed to create ' . ucfirst($type) . '. Please try again.');
}


public function memo_notice_conversation_bkp(Request $request)
{
    // print_r($request->all());die;
    $validated = $request->validate([
        'memo_notice_id' => 'required|exists:student_notice_status,pk',
        'date' => 'required|date',
        'time' => 'required',
        'message' => 'required|string|max:500',
        'document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        'status' => 'required|in:1,2',
    ]);

    // Handle file upload
     $filePath = null;
   
    if ($request->hasFile('document')) {
        $file = $request->file('document');
        $filePath = $file->store('notice_documents', 'public'); // stored in /storage/app/public/notice_documents
    }

    // Insert into your table
   $data = DB::table('notice_message_student_decip_incharge')->insert([
        'student_notice_status_pk' => $validated['memo_notice_id'],
        'student_decip_incharge_msg' => $validated['message'],
        'doc_upload' => $filePath,
        'role_type' => 'f',
        'created_by' => auth()->user()->id ?? 1, // Replace with correct user ID
         ]);

   if ($data) {
             if (isset($validated['status']) && $validated['status'] == 2) {
                DB::table('student_notice_status')
                ->where('pk', $validated['memo_notice_id'])
                ->update(['status' => $validated['status']]);
        }
        return redirect()->back()->with('success', 'Memo msg created successfully.');
   


    if ($request->hasFile('document')) {
        $file = $request->file('document');
        $filePath = $file->store('notice_documents', 'public'); // stored in /storage/app/public/notice_documents
    }

    // Insert into your table
   $data = DB::table('memo_message_student_decip_incharge')->insert([
        'student_memo_status_pk' => $validated['memo_notice_id'],
        'student_decip_incharge_msg' => $validated['message'],
        'doc_upload' => $filePath,
        'role_type' => 'f',
        'created_by' => auth()->user()->id ?? 1, // Replace with correct user ID
         ]);

   if ($data) {
             if (isset($validated['status']) && $validated['status'] == 2) {
                DB::table('student_notice_status')
                ->where('pk', $validated['memo_notice_id'])
                ->update(['status' => $validated['status']]);
        }
        return redirect()->back()->with('success', 'Notice msg created successfully.');
    }
        } else {
        return redirect()->back()->with('error', 'Failed to create Memo/Notice. Please try again.');
    }
}
public function noticedeleteMessage($id,$type)
{
    if($type == 'memo'){
        $table = 'memo_message_student_decip_incharge';
    }else{
        $table = 'notice_message_student_decip_incharge';
    }

       $message = DB::table($table)
        ->where('pk', $id)
        ->first();

    if ($message && !empty($message->file_name)) {
        // Delete the file from the 'public' disk
        if (Storage::disk('public')->exists($message->file_name)) {
            Storage::disk('public')->delete($message->file_name);
        }
    }


    // Now delete the DB record
    DB::table($table)
        ->where('pk', $id)
        ->delete();

    return redirect()->back()->with('success', 'Message and associated file deleted successfully.');

}
  public function user_bkp()
    {
       $memos =  DB::table('student_notice_status')
                    ->join('course_student_attendance as csa', 'student_notice_status.course_student_attendance_pk', '=', 'csa.pk')
                    ->join('student_master as sm', 'csa.Student_master_pk', '=', 'sm.pk')  
                    ->join('timetable as t', 'student_notice_status.subject_topic', '=', 't.pk')          
       ->select(
            'student_notice_status.pk as memo_notice_id',
            'student_notice_status.course_master_pk', 'student_notice_status.date_',
            'student_notice_status.subject_master_pk','student_notice_status.subject_topic',
            'student_notice_status.venue_id', 'student_notice_status.class_session_master_pk','student_notice_status.faculty_master_pk','student_notice_status.message','student_notice_status.notice_memo',
            'student_notice_status.status',
            'sm.display_name as student_name','sm.pk as student_id','t.subject_topic as topic_name',)
                    ->get();
         return view('admin.courseAttendanceNoticeMap.uers_notice_list', compact('memos'));
    }
    public function user()
{
     
    $notices = DB::table('student_notice_status');
      if(hasRole('Student-OT')){
        $notices->where('student_notice_status.student_pk', auth()->user()->user_id);
    }
    $notices->leftJoin('course_student_attendance as csa', 'student_notice_status.course_student_attendance_pk', 'csa.pk');
    $notices->leftJoin('student_master as sm', 'csa.Student_master_pk', 'sm.pk');
    $notices->leftJoin('timetable as t', 'student_notice_status.subject_topic', 't.pk');
    $notices->select(
        'student_notice_status.pk as notice_id',
        'student_notice_status.student_pk',
        'student_notice_status.course_master_pk',
        'student_notice_status.date_',
        'student_notice_status.subject_master_pk',
        'student_notice_status.subject_topic',
        'student_notice_status.venue_id',
        'student_notice_status.class_session_master_pk',
        'student_notice_status.faculty_master_pk',
        'student_notice_status.message',
        'student_notice_status.notice_memo',
        'student_notice_status.status',
        'sm.display_name as student_name',
        'sm.pk as student_id',
        't.subject_topic as topic_name'
    );
    $notices = $notices->get();

    $memos = collect();

    foreach ($notices as $notice) {
        if ($notice->status == 2) {
            $memoData = DB::table('student_memo_status')
                ->leftJoin('student_master as sm', 'student_memo_status.student_pk', '=', 'sm.pk')
                ->leftJoin('student_notice_status as sns', 'student_memo_status.student_notice_status_pk', '=', 'sns.pk')
                ->leftJoin('timetable as t', 'sns.subject_topic', '=', 't.pk')
                ->where('student_memo_status.student_notice_status_pk', $notice->notice_id)
                ->select(
                    'student_memo_status.pk as memo_id',
                     'student_memo_status.student_notice_status_pk as notice_id',
                    'student_memo_status.student_pk',
                    'student_memo_status.course_master_pk',
                    'student_memo_status.date as date_',
                    DB::raw('NULL as subject_master_pk'),
                    DB::raw('NULL as subject_topic'),
                    DB::raw('NULL as venue_id'),
                    DB::raw('NULL as class_session_master_pk'),
                    DB::raw('NULL as faculty_master_pk'),
                    'student_memo_status.message',
                    DB::raw('2 as notice_memo'),
                    'student_memo_status.status',
                    'sm.display_name as student_name',
                    'sm.pk as student_id',
                    't.subject_topic as topic_name',
                    DB::raw('"Memo" as type_notice_memo')
                )
                ->first();

            if ($memoData) {
                $memos->push($memoData);
            }else{
                $notice->type_notice_memo = 'Notice'; // Tag as Notice
                $memos->push($notice);
            }
        } else {
            $notice->type_notice_memo = 'Notice'; // Tag as Notice
            $memos->push($notice);
        }
    }
    // print_r($memos);die;

    // Paginate the collection
    $perPage = 10;
    $currentPage = request()->get('page', 1);
    $pagedData = $memos->slice(($currentPage - 1) * $perPage, $perPage)->values();
    $memos = new \Illuminate\Pagination\LengthAwarePaginator(
        $pagedData,
        $memos->count(),
        $perPage,
        $currentPage,
        ['path' => request()->url(), 'query' => request()->query()]
    );

    return view('admin.courseAttendanceNoticeMap.uers_notice_list', compact('memos'));
}

public function conversation_student($id ,$type, Request $request){

if (!$id || !is_numeric($id)) {
        return redirect()->back()->with('error', 'Invalid Memo/Notice ID.');
    }

    $memoNotice = collect(); // default empty collection

    if ($type == 'notice') {
        $memoNotice = DB::table('notice_message_student_decip_incharge as nmsdi')
            ->leftjoin('student_notice_status as sns', 'nmsdi.student_notice_status_pk', '=', 'sns.pk')
            ->leftjoin('course_student_attendance as csa', 'sns.course_student_attendance_pk', '=', 'csa.pk')
            ->leftjoin('student_master as sm', 'csa.Student_master_pk', '=', 'sm.pk')
            ->where('nmsdi.student_notice_status_pk', $id)
            ->orderBy('nmsdi.created_date', 'asc')
            ->select(
                'nmsdi.*',
                'sns.pk as notice_id',
                'sns.status as notice_status',
                'sm.pk as student_id',
                'sm.display_name as student_name'
            )
            ->get();

    } elseif ($type == 'memo') {
        $memoNotice = DB::table('memo_message_student_decip_incharge as mmsdi')
            ->leftjoin('student_memo_status as sms', 'mmsdi.student_memo_status_pk', '=', 'sms.pk')
            ->leftjoin('student_master as sm', 'sms.student_pk', '=', 'sm.pk')
            ->where('mmsdi.student_memo_status_pk', $id)
            ->orderBy('mmsdi.created_date', 'asc')
            ->select(
                'mmsdi.*',
                'sms.pk as notice_id',
                'sms.communication_status as notice_status',
                
                'sm.pk as student_id',
                'sm.display_name as student_name'
            )
            ->get();
          
            
    }
// print_r($memoNotice);die;
    // Common: map display_name based on role
    $memoNotice->transform(function ($item) {
        if ($item->role_type == 'f') {
            $creator = DB::table('users')->where('id', $item->created_by)->first();
            $item->display_name = $creator ? $creator->name : 'Admin';
        } elseif ($item->role_type == 's') {
            $student = DB::table('student_master')->where('pk', $item->created_by)->first();
            $item->display_name = $student ? $student->display_name : 'Student';
        } else {
            $item->display_name = 'Unknown';
        }
        return $item;
    });

   return view('admin.courseAttendanceNoticeMap.chat', compact('id', 'memoNotice', 'type'));
}
public function memo_notice_conversation_student(Request $request)
{
    $type = $request->input('type'); // 'memo' or 'notice'
    
    // Determine the correct status table based on the type
    $statusTable = $type === 'memo' ? 'student_memo_status' : 'student_notice_status';

    // Basic validation
    $validated = $request->validate([
        'memo_notice_id' => [
            'required',
            Rule::exists($statusTable, 'pk'), // Dynamically check existence in the correct table
        ],
        'message' => 'required|string|max:500',
        'student_id' => 'required|exists:student_master,pk',
        'document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        'type' => 'required|in:memo,notice',
    ]);

    // Handle file upload if present
    $filePath = null;
    if ($request->hasFile('document')) {
        $file = $request->file('document');
        $folder = $type === 'memo' ? 'memo_conversation_documents' : 'notice_documents';
        $filePath = $file->store($folder, 'public');
    }

    // Determine table to insert message
    $insertTable = $type === 'memo' ? 'memo_message_student_decip_incharge' : 'notice_message_student_decip_incharge';
    $foreignKeyField = $type === 'memo' ? 'student_memo_status_pk' : 'student_notice_status_pk';

    // Insert message
    $inserted = DB::table($insertTable)->insert([
        $foreignKeyField => $validated['memo_notice_id'],
        'student_decip_incharge_msg' => $validated['message'],
        'doc_upload' => $filePath,
        'role_type' => 's',
        'created_by' => $validated['student_id'],
        'created_date' => now(),
    ]);

    // Redirect back with appropriate message
    if ($inserted) {
        return redirect()->back()->with('success', ucfirst($type) . ' message created successfully.');
    }

    return redirect()->back()->with('error', 'Failed to create ' . ucfirst($type) . ' message. Please try again.');
}

public function memo_notice_conversation_student_bkp(Request $request){
      $validated = $request->validate([
        'memo_notice_id' => 'required|exists:student_notice_status,pk',
        
        'message' => 'required|string|max:500',
        'student_id' => 'required|exists:student_master,pk',
        'document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
         ]);

    // Handle file upload
    $filePath = null;
    if ($request->hasFile('document')) {
        $file = $request->file('document');
        $filePath = $file->store('notice_documents', 'public'); // stored in /storage/app/public/notice_documents
    }

    // Insert into your table
   $data = DB::table('notice_message_student_decip_incharge')->insert([
        'student_notice_status_pk' => $validated['memo_notice_id'],
        'student_decip_incharge_msg' => $validated['message'],
        'doc_upload' => $filePath,
        'role_type' => 's',
        'created_by' => $validated['student_id'], // Replace with correct user ID
         ]);

   if ($data) {
        return redirect()->back()->with('success', 'Notice msg created successfully.');

        } else {
        return redirect()->back()->with('error', 'Failed to create Memo/Notice. Please try again.');
    }
}
public function get_conversation_model($id, $type, $user_type, Request $request)
{
    // If type invalid → default notice
    if (!in_array($type, ['memo', 'notice'])) {
        $type = 'notice';
    }

    if ($type == 'memo') {
        $conversations = DB::table('memo_message_student_decip_incharge as mmsdi')
            ->join('student_memo_status as sms', 'mmsdi.student_memo_status_pk', '=', 'sms.pk')
            ->join('student_master as sm', 'sms.student_pk', '=', 'sm.pk')
            ->where('mmsdi.student_memo_status_pk', $id)
            ->orderBy('mmsdi.created_date', 'asc')
            ->select(
                'mmsdi.*',
                'sms.pk as notice_id',
                'sms.communication_status as notice_status',
                'sm.pk as student_id',
                'sm.display_name as student_name'
            )
            ->get();
            // print_r($conversations);die;

    } else {
        $conversations = DB::table('notice_message_student_decip_incharge as nmsdi')
            ->join('student_notice_status as sns', 'nmsdi.student_notice_status_pk', '=', 'sns.pk')
            ->join('course_student_attendance as csa', 'sns.course_student_attendance_pk', '=', 'csa.pk')
            ->join('student_master as sm', 'csa.Student_master_pk', '=', 'sm.pk')
            ->where('nmsdi.student_notice_status_pk', $id)
            ->orderBy('nmsdi.created_date', 'asc')
            ->select(
                'nmsdi.*',
                'sns.pk as notice_id',
                'sns.status as notice_status',
                'sm.pk as student_id',
                'sm.display_name as student_name'
            )
            ->get();
    }

    // Common mapper
    $conversations = $conversations->map(function ($item) {
        if ($item->role_type == 'f') {
            $user = DB::table('users')->find($item->created_by);
            $item->display_name = $user->name ?? 'Admin';
            $item->user_type = 'admin';

        } elseif ($item->role_type == 's') {
            $student = DB::table('student_master')->where('pk', $item->created_by)->first();
            $item->display_name = $student->display_name ?? 'Student';
            $item->user_type = 'student';

        } else {
            $item->display_name = 'Unknown';
            $item->user_type = 'unknown';
        }
        return $item;
    });

    // print_r($conversations);die;
    return view('admin.courseAttendanceNoticeMap.conversation_model', compact('conversations','type','id','user_type'));
}

public function get_conversation_model_bkp($id,$type, Request $request)
{
    // $conversations = DB::table('notice_message_student_decip_incharge')
    //     ->where('student_notice_status_pk', $id)
    //     ->orderBy('created_date', 'asc')
    //     ->get()
        $conversations = DB::table('notice_message_student_decip_incharge as nmsdi')
    ->join('student_notice_status as sns', 'nmsdi.student_notice_status_pk', '=', 'sns.pk')
    ->join('course_student_attendance as csa', 'sns.course_student_attendance_pk', '=', 'csa.pk')
    ->join('student_master as sm', 'csa.Student_master_pk', '=', 'sm.pk')
    ->where('nmsdi.student_notice_status_pk', $id)
    ->orderBy('nmsdi.created_date', 'asc')
    ->select(
        'nmsdi.*',
        'sns.pk as notice_id',
        'sns.status as notice_status',
        'sm.pk as student_id',
        'sm.display_name as student_name'
    )
    ->get()
        ->map(function ($item) {
            if ($item->role_type == 'f') {
                $user = DB::table('users')->find($item->created_by);
                $item->display_name = $user->name ?? 'Admin';
                $item->user_type = 'admin';
            } elseif ($item->role_type == 's') {
                $student = DB::table('student_master')->where('pk', $item->created_by)->first();

                $item->display_name = $student->display_name ?? 'Student';
                $item->user_type = 'student';
            } else {
                $item->display_name = 'Unknown';
                $item->user_type = 'unknown';
            }
            return $item;
        });

    return view('admin.courseAttendanceNoticeMap.conversation_model', compact('conversations','type','id'));
}
public function memo_notice_conversation_model(Request $request){
   
   
     $validated = $request->validate([
        'memo_notice_id' => 'required',
        
        'student_decip_incharge_msg' => 'required|string|max:500',
        'created_by' => 'required',
        'document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
         ]);

    // Handle file upload
    $filePath = null;
    if ($request->hasFile('document')) {
        $file = $request->file('document');
        $filePath = $file->store('notice_documents', 'public'); // stored in /storage/app/public/notice_documents
    }

    if($request->type == 'memo'){
$data = DB::table('memo_message_student_decip_incharge')->insert([
        'student_memo_status_pk' => $validated['memo_notice_id'],
        'student_decip_incharge_msg' => $validated['student_decip_incharge_msg'],
         'doc_upload' => $filePath,
        'role_type' => $request->role_type, // 'f' for admin, 's' for student
        'created_by' => $validated['created_by'], // Replace with correct user ID
         ]);
          if ($data) {
        return redirect()->back()->with('success', 'Memo msg created successfully.');

        } else {
        return redirect()->back()->with('error', 'Failed to create Memo/Notice. Please try again.');
    }
    }else if($request->type == 'notice'){
    // Insert into your table
   $data = DB::table('notice_message_student_decip_incharge')->insert([
        'student_notice_status_pk' => $validated['memo_notice_id'],
        'student_decip_incharge_msg' => $validated['student_decip_incharge_msg'],
        'doc_upload' => $filePath,
        'role_type' => $request->role_type, // 'f' for admin, 's' for student
        'created_by' => $validated['created_by'], // Replace with correct user ID
         ]);

   if ($data) {
        return redirect()->back()->with('success', 'Notice msg created successfully.');

        } else {
        return redirect()->back()->with('error', 'Failed to create Memo/Notice. Please try again.');
    }
}

}
   public function getMemoData_bkp(Request $request)
{
    $memoId = $request->memo_notice_id;

    $memo = DB::table('student_notice_status')
    ->join('faculty_master as fm', 'student_notice_status.faculty_master_pk', '=', 'fm.pk')
    ->join('course_master as cm', 'student_notice_status.course_master_pk', '=', 'cm.pk')
    ->join('subject_master as subm', 'student_notice_status.subject_master_pk', '=', 'subm.pk')
        ->join('course_student_attendance as csa', 'student_notice_status.course_student_attendance_pk', '=', 'csa.pk')
        ->join('student_master as sm', 'csa.Student_master_pk', '=', 'sm.pk')
        ->join('timetable as t', 'student_notice_status.subject_topic', '=', 't.pk')
        ->select(
            'student_notice_status.*',
            'subm.subject_name',
            'sm.display_name as student_name',
            't.subject_topic as topic_name',
            'fm.full_name as faculty_name',
            'cm.course_name as course_name',

        )
        ->where('student_notice_status.pk', $memoId)
        ->first();

    if (!$memo) {
        return response()->json(['error' => 'Data not found!'], 404);
    }
   // hmko niklna hoga same student ne same course ke liye kitne memo le rkkhi h
    $memoCount = DB::table('student_notice_status')
        ->where('course_student_attendance_pk', $memo->course_student_attendance_pk)
        ->where('subject_master_pk', $memo->subject_master_pk)
        ->count();

    return response()->json([
        'course_master_name' => $memo->course_name,
        'course_master_pk' => $memo->course_master_pk,
        'student_notice_status_pk' => $memo->pk,
       
        'date_' => $memo->date_,
        'subject_master_name' => $memo->subject_name,
        'subject_master_pk' => $memo->subject_master_pk,
        'student_name' => $memo->student_name,
        'subject_topic' => $memo->topic_name,
        'class_session_master_pk' => $memo->class_session_master_pk,
        'faculty_name' => $memo->faculty_name,
        'memo_date' => $memo->date_, // or any other
    ]);
}
public function getMemoData(Request $request)
{
    $memoId = $request->memo_notice_id;

    $memo = DB::table('student_notice_status')
        ->join('faculty_master as fm', 'student_notice_status.faculty_master_pk', '=', 'fm.pk')
        ->join('course_master as cm', 'student_notice_status.course_master_pk', '=', 'cm.pk')
        ->join('subject_master as subm', 'student_notice_status.subject_master_pk', '=', 'subm.pk')
        ->join('student_master as sm', 'student_notice_status.student_pk', '=', 'sm.pk')
        ->join('timetable as t', 'student_notice_status.subject_topic', '=', 't.pk')
        ->select(
            'student_notice_status.*',
            'subm.subject_name',
            'sm.display_name as student_name',
            'sm.generated_OT_code as generated_OT_code',
            't.subject_topic as topic_name',
            'fm.full_name as faculty_name',
            'cm.course_name as course_name'
        )
        ->where('student_notice_status.pk', $memoId)
        ->first();

    if (!$memo) {
        return response()->json(['error' => 'Data not found!'], 404);
    }

    // ✅ Memo Count: same student, same subject, same course
    $memoCount = DB::table('student_memo_status')
        ->where('student_pk', $memo->student_pk)
        ->where('course_master_pk', $memo->course_master_pk)
        ->count();
        $memo_number = $memo->course_name . ' / ' . ($memoCount + 1) . ' / ' . $memo->generated_OT_code;


    return response()->json([
        'course_master_name' => $memo->course_name,
        'course_master_pk' => $memo->course_master_pk,
        'student_pk' => $memo->student_pk,
        'student_notice_status_pk' => $memo->pk,
        'date_' => $memo->date_,
        'subject_master_name' => $memo->subject_name,
        'subject_master_pk' => $memo->subject_master_pk,
        'student_name' => $memo->student_name,
        'subject_topic' => $memo->topic_name,
        'class_session_master_pk' => $memo->class_session_master_pk,
        'faculty_name' => $memo->faculty_name,
        'memo_date' => $memo->date_,
        'memo_count' => $memoCount,
        'memo_number' => $memo_number
    ]);
}

public function store_memo_status(Request $request)
{
    // print_r($request->all());die;
    $validated = $request->validate([
        'student_notice_status_pk' => 'required|integer',
        'memo_type_master_pk'             => 'required|integer',
        'student_pk'                      => 'required|integer',
        'course_master_pk'                => 'required|integer',
        'course_master_name'             => 'required|string',
        'memo_count'                      => 'required|integer',
        'date_memo_notice'               => 'required|date',
        'venue'                          => 'required|integer',
        'meeting_time'                   => 'required|date_format:H:i',
        'Remark'                         => 'nullable|string',
    ]);

    DB::table('student_memo_status')->insert([
        'student_notice_status_pk' => $validated['student_notice_status_pk'],
        'memo_type_master_pk'             => $validated['memo_type_master_pk'],
        'student_pk'                      => $validated['student_pk'],
        'course_master_pk'                => $validated['course_master_pk'],
        'memo_no'                         => $request->memo_number,
        'memo_count'                      => $validated['memo_count'],
        'venue_master_pk'                 => $validated['venue'],
        'date'                            => $validated['date_memo_notice'],
        'start_time'                      => $validated['meeting_time'],
        'message'                         => $validated['Remark'] ?? null,
        'created_date'                      => now(),
        'modified_date'                      => now(),
        'status'                      => 1,
        'communication_status' => 1, // Assuming 1 means 'active'
    ]);

    return redirect()->back()->with('success', 'Memo saved successfully.');
  
}
function send_only_notice(Request $request){
    $courseMasterPK = CalendarEvent::active()->select('course_master_pk')->groupBy('course_master_pk')->get()->toArray();
     $courseMasters = CourseMaster::whereIn('course_master.pk', $courseMasterPK)
                        ->select('course_master.course_name', 'course_master.pk');
                    $courseMasters->where('course_master.active_inactive', 1);
                    $courseMasters = $courseMasters->get()->toArray();
            $sessions = ClassSessionMaster::get();
             $maunalSessions = Timetable::select('class_session')
                ->where('class_session', 'REGEXP', '[0-9]{2}:[0-9]{2} [AP]M - [0-9]{2}:[0-9]{2} [AP]M')
                ->groupBy('class_session')
                ->select('class_session')
                ->get();

$courseMasters_data = [];
    return view('admin.courseAttendanceNoticeMap.send_only_notice', compact('courseMasters', 'sessions', 'maunalSessions','courseMasters_data'));

    
}
function view_all_notice_list($group_pk, $course_pk, $timetable_pk)
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
                
            



          $students = DB::table('course_student_attendance as csa')
    ->leftJoin('student_master as sm', 'sm.pk', '=', 'csa.Student_master_pk')
    ->where('csa.course_master_pk', $course_pk)
    ->where('csa.timetable_pk', $timetable_pk)
    ->whereRaw("TRIM(csa.status) REGEXP '^(2|3)$'")
    ->select(
        'csa.*',
        'sm.display_name',
        'sm.pk as student_id',
        'sm.generated_OT_code as generated_OT_code'
    )
    ->paginate(30);

            return view('admin.courseAttendanceNoticeMap.view_all_notice_list', compact('students','courseGroup', 'group_pk', 'course_pk', 'timetable_pk'));
} catch (\Exception $e) {
            \Log::error('Error fetching attendance data: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while fetching attendance data: ' . $e->getMessage());
        }
    }
    function notice_direct_save(Request $request){
       
        try{
    $validated = $request->validate([
            'course_master_pk' => 'required|exists:course_master,pk',
            'subject_master_id' => 'required|exists:subject_master,pk',
            'topic_id' => 'required|exists:timetable,pk',
            'venue_id' => 'required',
            'class_session_master_pk' => 'required',
            'faculty_master_pk' => 'required',
            'selected_student_list' => 'required|array',
            
            
        ]);
        

        
        foreach ($validated['selected_student_list'] as $studentId) {
            $data[] = [
                'course_master_pk'           => $validated['course_master_pk'],
                'student_pk'                 => $studentId,
                'date_'                      => now()->toDateString(),
                'subject_master_pk'          => $validated['subject_master_id'],
                'subject_topic'              => $validated['topic_id'],
                'venue_id'                   => $validated['venue_id'],
                'class_session_master_pk'    => $validated['class_session_master_pk'],
                'faculty_master_pk'          => $validated['faculty_master_pk'],
                'course_student_attendance_pk' =>$request->input('attendance_pk_'.$studentId),
                'notice_memo'                => 1,
            ];

    }
    // print_r($data);die;
   $insertdata =  DB::table('student_notice_status')->insert($data);
   if($insertdata){
    return redirect('admin/memo-notice-management')->with('success', 'Notice sent successfully.');
   }
}catch (\Exception $e) {
            \Log::error('Error saving notice data: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while saving notice data: ' . $e->getMessage());
        }
       

}



}