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
         return view('admin.courseAttendanceNoticeMap.index');
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

    // First get the basic timetable details
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

    // If session_type == 1, join class_session_master and get start_time + end_time
    $timetable = $query->first();

    // if ($timetable->session_type == '1') {
    //     $session = DB::table('class_session_master')
    //         ->where('pk', $timetable->class_session)
    //         ->first();

    //     if ($session) {
    //         // Format time to AM/PM
    //         $startTime = date("h:i A", strtotime($session->start_time));
    //         $endTime = date("h:i A", strtotime($session->end_time));
    //         $timetable->shift_name = $startTime . ' - ' . $endTime;
    //     } else {
    //         $timetable->shift_name = null;
    //     }
    // } else {
    //     // If session_type != 1, use the string from class_session
    //     $timetable->shift_name = $timetable->class_session;
    // }

    return response()->json($timetable);
}


} 
