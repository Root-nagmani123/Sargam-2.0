<?php
 
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{ClassSessionMaster, CourseMaster, FacultyMaster, VenueMaster, SubjectMaster, SubjectModuleMaster, CalendarEvent};
use Illuminate\Support\Facades\Crypt;

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
        $validated = $request->validate([
        'Course_name' => 'required|integer',
        'subject_name' => 'required|integer',
        'subject_module' => 'required|integer',
        'topic' => 'nullable|string',
        'event_level' => 'required|string',
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
$event->course_group_type_master = $request->event_level;
$event->group_name = $request->group_name;
$event->faculty_master = $request->faculty;
$event->faculty_type = $request->faculty_type;
$event->venue_id = $request->vanue;
$event->class_session_master_pk = $request->shift;
$event->fullday = $request->has('fullDayCheckbox') ? 1 : 0;
$event->group_name = $request->has('group_name') ? 1 : 0;
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
    
    

public function calendarDetails(Request $request)
{
     $events = DB::table('timetable')
        ->whereDate('mannual_starttime', '>=', $request->start)
        ->whereDate('mannual_end_time', '<=', $request->end)
        ->get();

    // Array of some sample colors
    $colors = ['#FF5733', '#33B5FF', '#28B463', '#AF7AC5', '#F39C12', '#E74C3C'];

    // Assign random color to each event
    $events = $events->map(function ($event) use ($colors) {
        return [
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
}

