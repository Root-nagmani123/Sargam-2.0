<?php
 
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{ClassSessionMaster, CourseGroupTimetableMapping,CourseMaster, FacultyMaster, VenueMaster, SubjectMaster, SubjectModuleMaster, CalendarEvent, MemoTypeMaster};
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
 
class CourseAttendanceNoticeMapController extends Controller
{
    //
    public function index()
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
    $memo_master = MemoTypeMaster::where('active_inactive', 1)->get();
}

                    
                    // print_r($memos);die;
         return view('admin.courseAttendanceNoticeMap.index', compact('memos', 'venue', 'memo_master'));
    }
public function create(Request $request)
{
    $today = Carbon::today();

    $activeCourses = CourseMaster::where('active_inactive', 1)
        ->whereDate('start_year', '<=', $today)
        ->whereDate('end_date', '>=', $today)
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
function conversation($id){

    // Validate the ID
    if (!$id || !is_numeric($id)) {
        return redirect()->back()->with('error', 'Invalid Memo/Notice ID.');
    }
    // Fetch the memo/notice details
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

        $attendance = DB::table('course_student_attendance as a')
            ->leftJoin('student_master as s', 'a.Student_master_pk', '=', 's.pk')
            ->where('a.timetable_pk', $topicId)
            ->whereIn('a.status', [2, 3])

            ->select(
                'a.pk as pk',
                's.display_name as display_name'
            )
            ->get();

        if ($attendance->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No students found for this topic.'
            ]);
        }

        // Format the attendance data
        $students = $attendance->map(function ($student) {
            return [
                'pk' => $student->pk,
                'display_name' => $student->display_name
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Student list fetched successfully.',
            'students' => $students
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Error occurred while fetching student list.',
            'error' => $e->getMessage() // optional for debugging
        ]);
    }
}
function store_memo_notice(Request $request){
  
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
        $data[] = [
            'course_master_pk' => $validated['course_master_pk'],
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
        return redirect()->back()->with('success', 'Notice msg created successfully.');

        } else {
        return redirect()->back()->with('error', 'Failed to create Memo/Notice. Please try again.');
    }
}
public function noticedeleteMessage($id)
{
       $message = DB::table('notice_message_student_decip_incharge')
        ->where('pk', $id)
        ->first();

    if ($message && !empty($message->file_name)) {
        // Delete the file from the 'public' disk
        if (Storage::disk('public')->exists($message->file_name)) {
            Storage::disk('public')->delete($message->file_name);
        }
    }


    // Now delete the DB record
    DB::table('notice_message_student_decip_incharge')
        ->where('pk', $id)
        ->delete();

    return redirect()->back()->with('success', 'Message and associated file deleted successfully.');

}
  public function user()
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
                    // print_r($memos);die;
         return view('admin.courseAttendanceNoticeMap.uers_notice_list', compact('memos'));
    }
public function conversation_student($id , Request $request){
     if (!$id || !is_numeric($id)) {
        return redirect()->back()->with('error', 'Invalid Memo/Notice ID.');
    }
 
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


   return view('admin.courseAttendanceNoticeMap.chat', compact('id', 'memoNotice'));
}
public function memo_notice_conversation_student(Request $request){
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
public function get_conversation_model($id,$type, Request $request)
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
        'memo_notice_id' => 'required|exists:student_notice_status,pk',
        
        'message' => 'required|string|max:500',
        'created_by' => 'required',
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