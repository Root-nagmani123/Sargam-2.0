<?php
 
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{ClassSessionMaster, CourseMaster, FacultyMaster, VenueMaster, SubjectMaster, SubjectModuleMaster, CalendarEvent};
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CalendarController extends Controller
{
    public function index()
    {
        $courseMaster = CourseMaster::where('active_inactive', 1)
            ->select('pk', 'course_name')
            ->get();
    
        $facultyMaster = FacultyMaster::where('active_inactive', 1)
            ->select('pk', 'faculty_type', 'full_name')
            ->get();
    
        $subjects = SubjectMaster::where('active_inactive', 1)
            ->select('pk', 'subject_name', 'subject_module_master_pk')
            ->get();
    
        $venueMaster = VenueMaster::where('active_inactive', 1)
            ->select('venue_id', 'venue_name')
            ->get();
    
        $classSessionMaster = ClassSessionMaster::where('active_inactive', 1)
            ->select('pk', 'shift_name', 'start_time', 'end_time')
            ->get();
    
        return view('admin.calendar.index', compact(
            'courseMaster',
            'facultyMaster',
            'subjects',
            'venueMaster',
            'classSessionMaster'
        ));
    }
    public function getSubjectModules(Request $request)
{
    $dataId = $request->input('data_id');

    // Change the field name accordingly (assuming subject_module_master.pk = $dataId)
    $modules = SubjectModuleMaster::where('pk', $dataId)->get();

    return response()->json($modules);
}
public function store(Request $request)
    {
        // print_r($request->all());die;
        $validated = $request->validate([
        'Course_name' => 'required|integer',
        'subject_name' => 'required|integer',
        'subject_module' => 'required|integer',
        'topic' => 'nullable|string',
        'group_type' => 'required|string',
        'faculty' => 'required|integer',
        'faculty_type' => 'required|integer',
        'vanue' => 'required|integer',
        'shift' => 'required|integer',
        'start_datetime' => 'nullable|date',
        'end_datetime' => 'nullable|date',
    ]);

    $event = new CalendarEvent();
 $event->course_master_pk = $request->Course_name;
$event->subject_master_pk = $request->subject_name;
$event->subject_module_master_pk = $request->subject_module;
$event->subject_topic = $request->topic;
$event->course_group_type_master = $request->group_type;
$event->group_name = json_encode($request->type_names ?? []);
$event->faculty_master = $request->faculty;
$event->faculty_type = $request->faculty_type;
$event->venue_id = $request->vanue;
$event->class_session_master_pk = $request->shift;
$event->fullday = $request->has('fullDayCheckbox') ? 1 : 0;
$event->mannual_starttime = $request->start_datetime;
$event->mannual_end_time = $request->end_datetime;
$event->feedback_checkbox = $request->has('feedback_checkbox') ? 1 : 0;
$event->Ratting_checkbox = $request->has('ratingCheckbox') ? 1 : 0;
$event->Remark_checkbox = $request->has('remarkCheckbox') ? 1 : 0;
$event->Bio_attendance = $request->has('bio_attendanceCheckbox') ? 1 : 0;
$event->active_inactive = $request->active_inactive ?? 1;

$event->save();

    return response()->json(['status' => 'success', 'message' => 'Event created successfully']);
    }
    
    

public function fullCalendarDetails(Request $request)
{
    $event = new CalendarEvent();
    
     $events = DB::table('timetable')
        ->whereDate('mannual_starttime', '>=', $request->start)
        ->whereDate('mannual_end_time', '<=', $request->end)
        ->get();

    // Array of some sample colors
    $colors = ['#FF5733', '#33B5FF', '#28B463', '#AF7AC5', '#F39C12', '#E74C3C'];

    // Assign random color to each event
    $events = $events->map(function ($event) use ($colors) {
        return [
            'id' => $event->pk,
            'title' => $event->subject_topic,
            'start' => $event->mannual_starttime,
            'end'   => $event->mannual_end_time,
            'backgroundColor' => $colors[array_rand($colors)],  // background color for event
            'borderColor' => $colors[array_rand($colors)],  // border color for event
            'textColor' => '#fff',  // Text color for event (White text on colored background)
        ];
    });

    return response()->json($events);
}
function SingleCalendarDetails(Request $request)
{
    $eventId = $request->id;

   $event = DB::table('timetable')
        ->join('faculty_master', 'timetable.faculty_master', '=', 'faculty_master.pk')
        ->join('venue_master', 'timetable.venue_id', '=', 'venue_master.venue_id')
        ->where('timetable.pk', $eventId)
        ->select(
            'timetable.pk',
            'timetable.subject_topic',
            'timetable.mannual_starttime',
            'timetable.mannual_end_time',
            'faculty_master.full_name as faculty_name',
            'venue_master.venue_name as venue_name'
        )
        ->first();

    if ($event) {
         return response()->json([
        'id' => $event->pk,
        'topic' => $event->subject_topic ?? '', // if topic exists
        'start' => $event->mannual_starttime,
        'end' => $event->mannual_end_time,
        'faculty_name' => $event->faculty_name ?? '',
            'venue_name' => $event->venue_name ?? '',
    ]);
    } else {
        return response()->json(['error' => 'Event not found'], 404);
    }
    
}
public function getGroupTypes(Request $request)
{
    $courseName = $request->course_id; // Yahan course_id me course_name aa raha hai

    $groupTypes = DB::table('group_type_master_course_master_map as gmap')
        ->join('course_group_type_master as cgroup', 'gmap.type_name', '=', 'cgroup.pk')
        ->where('gmap.course_name', $courseName)
        ->where('cgroup.active_inactive', 1)
        ->where('gmap.active_inactive', 1)
        ->select(
            'gmap.pk',
            'gmap.group_name',
            'gmap.type_name as group_type_name',
            'cgroup.type_name',
        )
        ->get();
// print_r($groupTypes);die;
    return response()->json($groupTypes);
}
function event_edit($id){
     $event = CalendarEvent::findOrFail($id);
    return response()->json($event);
}
    public function update_event(Request $request, $id)
{
    $validated = $request->validate([
        'Course_name' => 'required|integer',
        'subject_name' => 'required|integer',
        'subject_module' => 'required|integer',
        'topic' => 'nullable|string',
        'group_type' => 'required|string',
        'faculty' => 'required|integer',
        'faculty_type' => 'required|integer',
        'vanue' => 'required|integer',
        'shift' => 'required|integer',
        'start_datetime' => 'nullable|date',
        'end_datetime' => 'nullable|date',
    ]);

    $event = CalendarEvent::findOrFail($id);
    $event->course_master_pk = $request->Course_name;
    $event->subject_master_pk = $request->subject_name;
    $event->subject_module_master_pk = $request->subject_module;
    $event->subject_topic = $request->topic;
    $event->course_group_type_master = $request->group_type;
    $event->group_name = json_encode($request->type_names ?? []);
    $event->faculty_master = $request->faculty;
    $event->faculty_type = $request->faculty_type;
    $event->venue_id = $request->vanue;
    $event->class_session_master_pk = $request->shift;
    $event->fullday = $request->has('fullDayCheckbox') ? 1 : 0;
    $event->mannual_starttime = $request->start_datetime;
    $event->mannual_end_time = $request->end_datetime;
    $event->feedback_checkbox = $request->has('feedback_checkbox') ? 1 : 0;
    $event->Ratting_checkbox = $request->has('ratingCheckbox') ? 1 : 0;
    $event->Remark_checkbox = $request->has('remarkCheckbox') ? 1 : 0;
    $event->Bio_attendance = $request->has('bio_attendanceCheckbox') ? 1 : 0;
    $event->active_inactive = $request->active_inactive ?? 1;

    $event->save();

    return response()->json(['status' => 'success', 'message' => 'Event updated successfully']);
}
public function delete_event($id)
{
    $event = CalendarEvent::findOrFail($id);
    $event->delete();
    return response()->json(['status' => 'success']);
}

function feedbackList(){

     return view('admin.feedback.index');
}
function studentFeedback() {
    $student_pk = auth()->user()->id;

    // Get all timetable PKs already submitted by this student
    $submittedTimetablePks = DB::table('topic_feedback')
        ->where('student_master_pk', $student_pk)
        ->pluck('timetable_pk')
        ->toArray();

    $data = DB::table('timetable')
        ->join('faculty_master', 'timetable.faculty_master', '=', 'faculty_master.pk')
        ->join('course_master', 'timetable.course_master_pk', '=', 'course_master.pk')
        ->join('venue_master', 'timetable.venue_id', '=', 'venue_master.venue_id')
        ->whereNotIn('timetable.pk', $submittedTimetablePks)
        ->select(
            'timetable.*',
            'faculty_master.full_name as faculty_name',
            'course_master.course_name as course_name',
            'venue_master.venue_name as venue_name'
        )
        ->get();

    return view('admin.feedback.student_feedback', compact('data'));
}

public function submitFeedback(Request $request)
{
     $rules = [
        'timetable_pk' => 'required|array',
        'faculty_pk' => 'required|array',
        'topic_name' => 'required|array',
        'rating' => 'required|array',
        'presentation' => 'required|array',
        'content' => 'required|array',
        'remarks' => 'required|array',
    ];

    // Validate all items for each index (nested validation)
    foreach ($request->timetable_pk as $index => $value) {
        $rules["rating.$index"] = 'required|integer|min:1|max:5';
        $rules["presentation.$index"] = 'required|integer|min:1|max:5';
        $rules["content.$index"] = 'required|integer|min:1|max:5';
        $rules["remarks.$index"] = 'required|string|max:255';
    }

    $validated = $request->validate($rules);

     $studentId = Auth::user()->id; // Or however you fetch student ID
    $now = Carbon::now();

    $timetablePks = $request->input('timetable_pk');
    $facultyPks = $request->input('faculty_pk');
    $topicNames = $request->input('topic_name');
    $ratings = $request->input('rating');
    $presentations = $request->input('presentation');
    $contents = $request->input('content');
    $remarks = $request->input('remarks');

    for ($i = 0; $i < count($timetablePks); $i++) {
        DB::table('topic_feedback')->insert([
            'timetable_pk'        => $timetablePks[$i],
            'student_master_pk'   => $studentId,
            'topic_name'          => $topicNames[$i],
            'faculty_pk'          => $facultyPks[$i],
            'presentation'        => $presentations[$i] ?? null,
            'content'             => $contents[$i] ?? null,
            'remark'              => $remarks[$i] ?? null,
            'created_date'        => $now,
            'modified_date'       => $now,
        ]);
    }

    return redirect()->back()->with('success', 'Feedback submitted successfully!');
}



}