<?php
 
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{ClassSessionMaster, CourseGroupTimetableMapping,CourseMaster, FacultyMaster, VenueMaster, SubjectMaster, SubjectModuleMaster, CalendarEvent, Holiday};
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CalendarController extends Controller
{
    public function index()
    {
        //print_r(Auth::user()->roles()->pluck('user_role_name')->toArray());die;
        //Array ( [0] => Training )

        // print_r(auth()->user());die;
       
        
        $courseMaster = CourseMaster::where('active_inactive', '1')
            ->where('end_date', '>', now())
            ->select('pk', 'course_name')
            ->get();
    
        $facultyMaster = FacultyMaster::where('active_inactive', 1)
            ->select('pk', 'faculty_type', 'full_name')
            ->get();
    
        $subjects = SubjectModuleMaster::where('active_inactive', 1)
            ->select('pk', 'module_name')
            ->get();
            // print_r($subjects);die;
    
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
                            //   ->where('subject_module_master_pk', $dataId)
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
// $event->START_DATE = $request->start_datetime;
$event->START_DATE = Carbon::parse($request->start_datetime)
                    ->timezone('Asia/Kolkata')
                    ->format('Y-m-d');
// $event->END_DATE = $request->start_datetime;
$event->END_DATE = Carbon::parse($request->start_datetime)
                    ->timezone('Asia/Kolkata')
                    ->format('Y-m-d');
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
    // print_r(Auth::user());die;
  
    $event = new CalendarEvent();

$events = DB::table('timetable')
    ->join('venue_master', 'timetable.venue_id', '=', 'venue_master.venue_id')
    ->leftJoin('faculty_master', 'timetable.faculty_master', '=', 'faculty_master.pk');  // single join

// Student-OT Role
if (hasRole('Student-OT')) {

    $student_pk = auth()->user()->user_id;

    $events = $events
        ->join('course_group_timetable_mapping', 'course_group_timetable_mapping.timetable_pk', '=', 'timetable.pk')
        ->join('student_course_group_map', 'student_course_group_map.group_type_master_course_master_map_pk', '=', 'course_group_timetable_mapping.group_pk')
        ->where('student_course_group_map.student_master_pk', $student_pk);
}

// Internal / Guest Faculty
if (hasRole('Internal Faculty') || hasRole('Guest Faculty')) {

    $faculty_pk = auth()->user()->user_id;

    // ❗⚠ Only WHERE — NO NEW JOIN
    $events = $events->where('faculty_master.employee_master_pk', $faculty_pk);
}

$events = $events
    ->whereDate('START_DATE', '>=', $request->start)
    ->whereDate('END_DATE', '<=', $request->end)
    ->select(
        'timetable.*',
        'venue_master.venue_name as venue_name',
        'faculty_master.full_name as faculty_name'
    )
    ->get();




    // Array of some sample colors
    $colors = ['#9edcf5ff'];

    // Assign random color to each event
    $events = $events->map(function ($event) use ($colors) {
        // Parse start and end times from class_session if it contains time range
        $startDateTime = $event->START_DATE;
        $endDateTime = $event->END_DATE;
        $allDay = false;
        
        // Check if class_session exists and contains a time range with dash
        if (!empty($event->class_session) && strpos($event->class_session, '-') !== false) {
            $timeRange = trim($event->class_session);
            $parts = explode('-', $timeRange);
            
            if (count($parts) === 2) {
                $startTime = trim($parts[0]);
                $endTime = trim($parts[1]);
                
                // Try to parse times
                $startTimestamp = strtotime($startTime);
                $endTimestamp = strtotime($endTime);
                
                if ($startTimestamp !== false && $endTimestamp !== false) {
                    $startTime24 = date('H:i', $startTimestamp);
                    $endTime24 = date('H:i', $endTimestamp);
                    
                    // Append time to date (ISO 8601 format)
                    $startDateTime = $event->START_DATE . 'T' . $startTime24 . ':00';
                    $endDateTime = $event->END_DATE . 'T' . $endTime24 . ':00';
                    $allDay = false;
                } else {
                    // If time parsing failed, treat as all-day
                    $allDay = true;
                }
            } else {
                $allDay = true;
            }
        } else {
            // No time information, treat as all-day
            $allDay = true;
        }
        
        return [
            'id' => $event->pk,
            'title' => $event->subject_topic,
            'start' => $startDateTime,
            'end'   => $endDateTime,
            'vanue'   => $event->venue_name,
            'faculty_name'   => $event->faculty_name,
            'backgroundColor' => $colors[array_rand($colors)],  // background color for event
            'borderColor' => $colors[array_rand($colors)],  // border color for event
            'textColor' => '#fff',  // Text color for event (White text on colored background)
            'allDay' => $allDay,
            'display' => 'block',
            // Debug info - class_session value stored in database
            'class_session_debug' => $event->class_session,
        ];
    });

    // Fetch holidays
    $holidays = Holiday::active()
        ->whereBetween('holiday_date', [$request->start, $request->end])
        ->get()
        ->map(function ($holiday) {
            $backgroundColor = '';
            $textColor = '#fff';
            
            switch ($holiday->holiday_type) {
                case 'gazetted':
                    $backgroundColor = '#dc3545'; // Red for Gazetted
                    break;
                case 'restricted':
                    $backgroundColor = '#ffc107'; // Yellow/Amber for Restricted
                    $textColor = '#000';
                    break;
                case 'optional':
                    $backgroundColor = '#17a2b8'; // Info color for Optional
                    break;
            }
            
            return [
                'id' => 'holiday_' . $holiday->id,
                'title' => $holiday->holiday_name . ' (' . ucfirst($holiday->holiday_type) . ')',
                'start' => $holiday->holiday_date->format('Y-m-d'),
                'end' => $holiday->holiday_date->format('Y-m-d'),
                'backgroundColor' => $backgroundColor,
                'borderColor' => $backgroundColor,
                'textColor' => $textColor,
                'display' => 'block',
                'type' => 'holiday',
                'holiday_type' => $holiday->holiday_type,
                'description' => $holiday->description,
                'allDay' => true
            ];
        });

    // Merge events and holidays
    $allEvents = $events->merge($holidays);

    return response()->json($allEvents);
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
     $event->START_DATE = \Carbon\Carbon::parse($event->START_DATE)->format('Y-m-d');
$event->END_DATE   = \Carbon\Carbon::parse($event->END_DATE)->format('Y-m-d');

return response()->json($event);

    // return response()->json($event);
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

public function feedbackList()
{
    $user = auth()->user();
    $facultyPk = $user->user_id; // same as faculty_master.pk (agar alag ho to bataana)
// echo $facultyPk; die;
    $query = DB::table('timetable as t')
        ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')
        ->join('faculty_master as f', 't.faculty_master', '=', 'f.pk')
        ->join('subject_master as s', 't.subject_master_pk', '=', 's.pk')
        ->whereExists(function ($q) {
            $q->select(DB::raw(1))
              ->from('topic_feedback as tf')
              ->whereColumn('tf.timetable_pk', 't.pk');
        })
        ->select([
            't.pk as event_id',
            'c.course_name',
            'f.full_name as faculty_name',
            's.subject_name',
            't.subject_topic',
        ]);

    // ✅ Faculty ko sirf apna data dikhana
    if (hasRole('Internal Faculty') || hasRole('Guest Faculty')) {
        $query->where('t.faculty_master', $facultyPk);
    }

    // ✅ Admin ko sabka dikhe
    $events = $query->orderByDesc('t.pk')->paginate(10);

    return view('admin.feedback.index', compact('events'));
}
public function getEventFeedback($id)
{
    $user = auth()->user();
    $facultyPk = $user->user_id;

    $query = DB::table('topic_feedback as tf')
        ->join('timetable as t', 'tf.timetable_pk', '=', 't.pk')
        ->select([
            'tf.rating',
            'tf.remark',
            'tf.presentation',
            'tf.content'
        ])
        ->where('tf.timetable_pk', $id);

    // Faculty ko sirf apna timetable ka feedback dikhana
    if (hasRole('Faculty')) {
        $query->where('t.faculty_master', $facultyPk);
    }

    return response()->json($query->get());
}

public function allFeedback()
{
    $user = auth()->user();
    $facultyPk = $user->user_id;

    $query = DB::table('topic_feedback as tf')
        ->join('timetable as t', 'tf.timetable_pk', '=', 't.pk')
        ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')
        ->join('faculty_master as f', 't.faculty_master', '=', 'f.pk')
        ->select([
            't.pk as event_id',
            'c.course_name',
            'f.full_name as faculty_name',
            't.subject_topic',
            'tf.rating',
            'tf.remark',
            'tf.presentation',
            'tf.content',
            'tf.created_date'
        ]);

    // Faculty ko sirf apna feedback dikhana
    if (hasRole('Internal Faculty') || hasRole('Guest Faculty')) {
        $query->where('t.faculty_master', $facultyPk);
    }

    // Admin ko sabka dikhe
    return response()->json($query->orderByDesc('tf.created_date')->get());
}



public function studentFeedback()
{
    try {
        $student_pk = auth()->user()->user_id;

        $query = DB::table('timetable as t')
            ->select([
                't.pk as timetable_pk',
                't.subject_topic',
                't.Ratting_checkbox',
                't.feedback_checkbox',
                't.Remark_checkbox',
                't.faculty_master as faculty_pk',
                'f.full_name as faculty_name',
                'c.course_name',
                'v.venue_name',
                DB::raw('t.START_DATE as from_date'),
                't.class_session',
            ])
            ->join('faculty_master as f', 't.faculty_master', '=', 'f.pk')
            ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')
            ->join('venue_master as v', 't.venue_id', '=', 'v.venue_id')
            ->where('t.feedback_checkbox', 1)

            // ✅ Hide already given feedback rows
            ->whereNotExists(function ($sub) use ($student_pk) {
                $sub->select(DB::raw(1))
                    ->from('topic_feedback as tf')
                    ->whereColumn('tf.timetable_pk', 't.pk')
                    ->where('tf.student_master_pk', $student_pk);
            });

        // ✅ Student group mapping filter
        if (hasRole('Student-OT')) {
            $query->join('course_group_timetable_mapping as cgtm', 'cgtm.timetable_pk', '=', 't.pk')
                  ->join('student_course_group_map as scgm', 'scgm.group_type_master_course_master_map_pk', '=', 'cgtm.group_pk')
                  ->where('scgm.student_master_pk', $student_pk);
        }

        $data = $query->orderByDesc('t.START_DATE')->get();

        return view('admin.feedback.student_feedback', compact('data'));

    } catch (\Throwable $e) {
        return back()->with('error', $e->getMessage());
    }
}



public function submitFeedback(Request $request)
{
    $rules = [
        'timetable_pk' => 'required|array|min:1',
    ];

    foreach ($request->timetable_pk as $i => $ttPk) {
        $rules["presentation.$i"] = 'required|in:1,2,3,4,5';
        $rules["content.$i"] = 'required|in:1,2,3,4,5';
        $rules["rating.$i"] = 'nullable|in:1,2,3,4,5';
        $rules["remarks.$i"] = 'nullable|string|max:255';
    }

    $request->validate($rules);

    $studentId  = auth()->user()->user_id;
    $now = now();

    $insertData = [];

    foreach ($request->timetable_pk as $i => $ttPk) {
        $insertData[] = [
            'timetable_pk'       => $ttPk,
            'student_master_pk'  => $studentId,
            'topic_name'         => $request->topic_name[$i] ?? '',
            'faculty_pk'         => $request->faculty_pk[$i] ?? null,
            'presentation'       => $request->presentation[$i] ?? null,
            'content'            => $request->content[$i] ?? null,
            'remark'             => $request->remarks[$i] ?? null,
            'rating'             => $request->rating[$i] ?? null,
            'created_date'       => $now,
            'modified_date'      => $now,
        ];
    }
    // print_r($insertData);die;

    // ✅ Bulk insert = much faster
    DB::table('topic_feedback')->insert($insertData);

    return back()->with('success', 'Feedback submitted successfully!');
}




}
