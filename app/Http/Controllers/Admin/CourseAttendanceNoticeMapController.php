<?php
 
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{ClassSessionMaster, CourseGroupTimetableMapping,CourseMaster, FacultyMaster, VenueMaster, SubjectMaster, SubjectModuleMaster, CalendarEvent};
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
 
class CourseAttendanceNoticeMapController extends Controller
{
    //
    public function index()
    {
       $memos =  DB::table('course_attendance_notice')
                    ->join('course_student_attendance as csa', 'course_attendance_notice.course_student_attendance_pk', '=', 'csa.pk')
                    ->join('student_master as sm', 'csa.Student_master_pk', '=', 'sm.pk')  
                    ->join('timetable as t', 'course_attendance_notice.subject_topic', '=', 't.pk')          
       ->select(
            'course_attendance_notice.pk as memo_notice_id',
            'course_attendance_notice.course_master_pk', 'course_attendance_notice.date_',
            'course_attendance_notice.subject_master_pk','course_attendance_notice.subject_topic',
            'course_attendance_notice.venue_id', 'course_attendance_notice.class_session_master_pk','course_attendance_notice.faculty_master_pk','course_attendance_notice.message','course_attendance_notice.notice_memo',
            'course_attendance_notice.status',
            'sm.display_name as student_name','t.subject_topic as topic_name',)
                    ->get();
                    // print_r($memos);die;
         return view('admin.courseAttendanceNoticeMap.index', compact('memos'));
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
function conversation(){
     return view('admin.courseAttendanceNoticeMap.conversation');
}
<<<<<<< HEAD
<<<<<<< HEAD
<<<<<<< HEAD
=======
>>>>>>> fe496a2 (memo index page work)
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

    $courseAttendanceNotice = DB::table('course_attendance_notice')->insert($data);

    if ($courseAttendanceNotice) {
        return redirect()->route('memo.notice.management.index')->with('success', 'Memo/Notice created successfully.');
    } else {
        return redirect()->back()->with('error', 'Failed to create Memo/Notice. Please try again.');
    }
}
<<<<<<< HEAD
=======
>>>>>>> ae713cf (some chnages in memo)
=======
>>>>>>> fe496a2 (memo index page work)
=======
>>>>>>> ae713cf (some chnages in memo)


} 
