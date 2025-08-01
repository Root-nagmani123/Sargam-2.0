<?php
 
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{ClassSessionMaster, CourseGroupTimetableMapping,CourseMaster, FacultyMaster, VenueMaster, SubjectMaster, SubjectModuleMaster, CalendarEvent};
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
    
        $subjects = SubjectModuleMaster::where('active_inactive', 1)
            ->select('pk', 'module_name')
            ->get();
    
        $venueMaster = VenueMaster::where('active_inactive', 1)
            ->select('venue_id', 'venue_name')
            ->get();
    
        $classSessionMaster = ClassSessionMaster::where('active_inactive', 1)
            ->select('pk', 'shift_name','shift_time', 'start_time', 'end_time')
            ->get();
            // print_r($classSessionMaster);die;
    
        return view('admin.calendar.index', compact(
            'courseMaster',
            'facultyMaster',
            'subjects',
            'venueMaster',
            'classSessionMaster'
        )); 
    }
    public function getSubjectName(Request $request)
{
    $dataId = $request->input('data_id');

    // Change the field name accordingly (assuming subject_module_master.pk = $dataId)
   $modules = SubjectMaster::where('active_inactive', 1)
                              ->where('subject_module_master_pk', $dataId)
                              ->get();


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
        'shift' => 'required_if:shift_type,1',
        'start_time' => 'required_if:shift_type,2',
        'end_time' => 'required_if:shift_type,2',
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
$event->START_DATE = $request->start_datetime;
$event->END_DATE = $request->start_datetime;
$event->session_type = $request->shift_type;
if ($request->shift_type == 1) {
             $event->class_session = $request->shift;
    } else {
        if($request->has('fullDayCheckbox') && $request->fullDayCheckbox == 1) {
        $event->full_day = $request->fullDayCheckbox; 
        }
        $startTime = Carbon::parse($request->start_time)->format('h:i A');
        $endTime = Carbon::parse($request->end_time)->format('h:i A');
        $event->class_session = $startTime . ' - ' . $endTime;
    }
$event->feedback_checkbox = $request->has('feedback_checkbox') ? 1 : 0;
$event->Ratting_checkbox = $request->has('ratingCheckbox') ? 1 : 0;
$event->Remark_checkbox = $request->has('remarkCheckbox') ? 1 : 0;
$event->Bio_attendance = $request->has('bio_attendanceCheckbox') ? 1 : 0;
$event->active_inactive = $request->active_inactive ?? 1;

$event->save();

$group_pks = $request->type_names ?? [];

foreach ($group_pks as $group_pk) {
    CourseGroupTimetableMapping::create([
        'group_pk' => $group_pk,
        'course_group_type_master' => $request->group_type,
        'Programme_pk' => $request->Course_name,
        'timetable_pk' => $event->pk,
    ]);
}

    return response()->json(['status' => 'success', 'message' => 'Event created successfully']);
    }
    
    

public function fullCalendarDetails(Request $request)
{
    $event = new CalendarEvent();
    
     $events = DB::table('timetable')
      ->join('venue_master', 'timetable.venue_id', '=', 'venue_master.venue_id')
        ->whereDate('START_DATE', '>=', $request->start)
        ->whereDate('END_DATE', '<=', $request->end)
         ->select(
            'timetable.*',
            'venue_master.venue_name as venue_name'
        )
        ->get();

    // Array of some sample colors
    $colors = ['#9edcf5ff'];

    // Assign random color to each event
    $events = $events->map(function ($event) use ($colors) {
        return [
            'id' => $event->pk,
            'title' => $event->subject_topic,
            'start' => $event->START_DATE,
            'end'   => $event->END_DATE,
            'vanue'   => $event->venue_name,
            'backgroundColor' => $colors[array_rand($colors)],  // background color for event
            'borderColor' => $colors[array_rand($colors)],  // border color for event
            'textColor' => '#fff',  // Text color for event (White text on colored background)
       'display' => 'block',
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
            'timetable.START_DATE',
            'timetable.END_DATE',
            'faculty_master.full_name as faculty_name',
            'venue_master.venue_name as venue_name'
        )
        ->first();

    if ($event) {
         return response()->json([
        'id' => $event->pk,
        'topic' => $event->subject_topic ?? '', // if topic exists
        'start' => $event->START_DATE,
        // 'end' => $event->END_DATE,
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
       'shift' => 'required_if:shift_type,1',
        'start_time' => 'required_if:shift_type,2',
        'end_time' => 'required_if:shift_type,2',
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
$event->START_DATE = $request->start_datetime;
$event->END_DATE = $request->start_datetime;
$event->session_type = $request->shift_type;

if ($request->shift_type == 1) {
    $event->full_day =  0; 
             $event->class_session = $request->shift;
    } else {
        $event->full_day = $request->fullDayCheckbox; 
        $startTime = Carbon::parse($request->start_time)->format('h:i A');
        $endTime = Carbon::parse($request->end_time)->format('h:i A');
        $event->class_session = $startTime . ' - ' . $endTime;
    }
$event->feedback_checkbox = $request->has('feedback_checkbox') ? 1 : 0;
$event->Ratting_checkbox = $request->has('ratingCheckbox') ? 1 : 0;
$event->Remark_checkbox = $request->has('remarkCheckbox') ? 1 : 0;
$event->Bio_attendance = $request->has('bio_attendanceCheckbox') ? 1 : 0;
$event->active_inactive = $request->active_inactive ?? 1;

    $event->save();
CourseGroupTimetableMapping::where('timetable_pk', $event->pk)->delete();

// ✅ Insert new mappings
$group_pks = $request->type_names ?? [];

foreach ($group_pks as $group_pk) {
    CourseGroupTimetableMapping::create([
        'group_pk' => $group_pk,
        'course_group_type_master' => $request->group_type,
        'Programme_pk' => $request->Course_name,
        'timetable_pk' => $event->pk,
    ]);
}

    return response()->json(['status' => 'success', 'message' => 'Event updated successfully']);
}
public function delete_event($id)
{
    $event = CalendarEvent::findOrFail($id);
    $event->delete();
    return response()->json(['status' => 'success']);
}

function feedbackList(){
$events = DB::table('timetable')
    ->join('course_master', 'timetable.course_master_pk', '=', 'course_master.pk')
    ->join('faculty_master', 'timetable.faculty_master', '=', 'faculty_master.pk')
    ->join('subject_master', 'timetable.subject_master_pk', '=', 'subject_master.pk')
    ->join('topic_feedback', 'topic_feedback.timetable_pk', '=', 'timetable.pk') // Only include those with feedback
    ->select(
        'timetable.pk as event_id',
        'course_master.course_name',
        'faculty_master.full_name as faculty_name',
        'subject_master.subject_name',
        'timetable.subject_topic'
    )
     ->orderBy('timetable.pk', 'desc')
    ->distinct() // prevent duplicates if multiple feedbacks
    ->get();


     return view('admin.feedback.index', compact('events'));
}
public function getEventFeedback($id)
{
    $feedbacks = DB::table('topic_feedback')
        ->where('timetable_pk', $id)
        ->select('rating', 'remark','presentation','content')
        ->get();

    return response()->json($feedbacks);
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
        ->where('timetable.feedback_checkbox', 1)
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
    // foreach ($request->timetable_pk as $i => $ttPk) {
        
    //     if (!empty($request->Ratting_checkbox[$i]) && $request->Ratting_checkbox[$i] == 1) {
    //         $rules["rating.$i"] = 'required|in:1,2,3,4,5';
    //           $rules["presentation.$i"] = 'required|in:1,2,3,4,5';
    //             $rules["content.$i"] = 'required|in:1,2,3,4,5';
    //     } else {
    //         $rules["rating.$i"] = 'nullable|in:1,2,3,4,5';
    //          $rules["presentation.$i"] = 'nullable|in:1,2,3,4,5';
    //             $rules["content.$i"] = 'nullable|in:1,2,3,4,5';
    //     }
  
    //     // Remarks required only if Remark_checkbox is 1
    //     if (!empty($request->Remark_checkbox[$i]) && $request->Remark_checkbox[$i] == 1) {
    //         $rules["remarks.$i"] = 'required|string|max:255';
    //     } else {
    //         $rules["remarks.$i"] = 'nullable|string|max:255';
    //     }
    // }

    // $validated = $request->validate($rules);

    $rules = [
        'timetable_pk' => 'required|array',
        'faculty_pk' => 'required|array',
        'topic_name' => 'required|array',
    ];

    foreach ($request->timetable_pk as $i => $ttPk) {
        $rules["presentation.$i"] = 'required|in:1,2,3,4,5';
        $rules["content.$i"] = 'required|in:1,2,3,4,5';

        if (!empty($request->Ratting_checkbox[$i]) && $request->Ratting_checkbox[$i] == 1) {
            $rules["rating.$i"] = 'required|in:1,2,3,4,5';
        } else {
            $rules["rating.$i"] = 'nullable|in:1,2,3,4,5';
        }

        if (!empty($request->Remark_checkbox[$i]) && $request->Remark_checkbox[$i] == 1) {
            $rules["remarks.$i"] = 'required|string|max:255';
        } else {
            $rules["remarks.$i"] = 'nullable|string|max:255';
        }
    }

    // 👇👇👇 User friendly attribute names 👇👇👇
    $attributes = [];
    foreach ($request->timetable_pk as $i => $ttPk) {
        $topic = $request->topic_name[$i] ?? 'this topic';
        $attributes["rating.$i"] = "rating ";
        $attributes["presentation.$i"] = "presentation ";
        $attributes["content.$i"] = "content ";
        $attributes["remarks.$i"] = "remarks ";
    }

    $validated = $request->validate($rules, [], $attributes);


//    print_r($request->all());die;
    // Save to DB
    $studentId = Auth::id();
    $now = now();

    foreach ($request->timetable_pk as $i => $ttPk) {
        DB::table('topic_feedback')->insert([
            'timetable_pk' => $ttPk,
            'student_master_pk' => $studentId,
            'topic_name' => $request->topic_name[$i] ?? '',
            'faculty_pk' => $request->faculty_pk[$i],
            'presentation' => $request->presentation[$i] ?? null,
            'content' => $request->content[$i] ?? null,
            'remark' => $request->remarks[$i] ?? null,
            'rating' => $request->rating[$i] ?? null,
            'created_date' => $now,
            'modified_date' => $now,
        ]);
    }

    return redirect()->back()->with('success', 'Feedback submitted successfully!');
}




}
