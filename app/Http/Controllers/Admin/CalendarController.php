<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{ClassSessionMaster, CourseGroupTimetableMapping, CourseMaster, FacultyMaster, VenueMaster, SubjectMaster, SubjectModuleMaster, CalendarEvent, Holiday};
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Session;




class CalendarController extends Controller
{
    public function index(Request $request)
    {
        \Log::info('CalendarController index: User authenticated via middleware', [
            'user' => auth()->user()->user_name,
            'user_id' => auth()->id(),
            'session_id' => session()->getId()
        ]);

        $data_course_id = get_Role_by_course();

        $courseMaster = CourseMaster::where('course_master.active_inactive', 1)
            ->where('end_date', '>', now());

        if (!empty($data_course_id)) {
            $courseMaster = $courseMaster->whereIn('course_master.pk', $data_course_id);
        }

        if (hasRole('Student-OT')) {
            $courseMaster = $courseMaster->leftJoin(
                'student_master_course__map',
                'student_master_course__map.course_master_pk',
                '=',
                'course_master.pk'
            )
                ->where('student_master_course__map.student_master_pk', auth()->user()->user_id);
        }

        $courseMaster = $courseMaster->select('course_master.pk', 'course_name', 'couse_short_name', 'course_year')
            ->get();
            // print_r($courseMaster);die;

        $facultyMaster = FacultyMaster::where('active_inactive', 1)
            ->select('pk', 'faculty_type', 'full_name')
            ->orderby('full_name', 'ASC')
            ->get();

        $internal_faculty = FacultyMaster::where('active_inactive', 1)
            ->where('faculty_type', 1)
            ->select('pk', 'faculty_type', 'full_name')
            ->orderby('full_name', 'ASC')
            ->get();

        $subjects = SubjectModuleMaster::where('active_inactive', 1)
            ->select('pk', 'module_name')
            ->get();

        $venueMaster = VenueMaster::where('active_inactive', 1)
            ->select('venue_id', 'venue_name')
            ->orderby('venue_name', 'ASC')
            ->get();

        $classSessionMaster = ClassSessionMaster::where('active_inactive', 1)
            ->select('pk', 'shift_name', 'shift_time', 'start_time', 'end_time')
            ->get();

        \Log::info('Calendar data loaded successfully', [
            'courses_count' => $courseMaster->count(),
            'user' => auth()->user()->user_name
        ]);

        return view('admin.calendar.index', compact(
            'courseMaster',
            'facultyMaster',
            'subjects',
            'venueMaster',
            'classSessionMaster',
            'internal_faculty'
        ));
    }
    public function weeklyTimetable(Request $request)
    {
        // Determine weekStart (Monday) from request or default to current week
        $weekStart = $request->week_start
            ? Carbon::parse($request->week_start)->startOfWeek()
            : Carbon::now()->startOfWeek();

        // We'll consider monday-friday display (5 days) but fetch full week for safety
        $weekEnd = $weekStart->copy()->endOfWeek();

        // Build time slots (example: 09:00 - 18:00 hourly). Adjust as needed.
        $timeSlots = [];
        $startTime = Carbon::createFromTime(0, 0);
        $endTime = Carbon::createFromTime(23, 0);

        while ($startTime <= $endTime) {
            $timeSlots[] = $startTime->format('H:i'); // 24-hour format
            $startTime->addHour();
        }

        // Fetch events from timetable table for the week
        $events = DB::table('timetable')
            ->leftJoin('faculty_master', 'timetable.faculty_master', '=', 'faculty_master.pk')
            ->leftJoin('venue_master', 'timetable.venue_id', '=', 'venue_master.venue_id')
            ->whereBetween('timetable.START_DATE', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->select(
                'timetable.pk',
                'timetable.subject_topic',
                'timetable.class_session',
                'timetable.START_DATE',
                'timetable.END_DATE',
                'faculty_master.full_name as faculty_name',
                'venue_master.venue_name as venue_name'
            )
            ->get();

        // Normalize events to array (JSON friendly) and ensure START_DATE is Y-m-d
        $events = $events->map(function ($e) {
            return [
                'pk' => $e->pk,
                'subject_topic' => $e->subject_topic,
                'class_session' => $e->class_session,
                'START_DATE' => Carbon::parse($e->START_DATE)->toDateString(),
                'END_DATE' => $e->END_DATE ? Carbon::parse($e->END_DATE)->toDateString() : null,
                'faculty_name' => $e->faculty_name,
                'venue_name' => $e->venue_name,
            ];
        });

        return response()->json([
            'weekStart' => $weekStart->toDateString(), // yyyy-mm-dd
            'weekEnd' => $weekEnd->toDateString(),
            'timeSlots' => $timeSlots,
            'events' => $events->values(), // collection -> array
        ]);
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
            'type_names' => 'required|array|min:1',
            'type_names.*' => 'required|integer',
            'faculty' => 'required|integer',
            'faculty_type' => 'required|integer',
            'vanue' => 'required|integer',
            'shift' => 'required_if:shift_type,1',
            'start_time' => 'required_if:shift_type,2',
            'end_time' => 'required_if:shift_type,2',
        ], [
            'type_names.required' => 'The Group type names field is required.',
            'type_names.min' => 'Please select at least one Group type name.',
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
        $event->internal_faculty = json_encode($request->internal_faculty ?? []);
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
            if ($request->has('fullDayCheckbox') && $request->fullDayCheckbox == 1) {
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
            ->leftJoin('faculty_master', 'timetable.faculty_master', '=', 'faculty_master.pk');           // single join

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
        $cuurent_month_start_date = Carbon::now()->startOfMonth()->toDateString();
        $cuurent_month_end_date = Carbon::now()->endOfMonth()->toDateString();
        if (($request->start) && ($request->end)) {
        } else {
            $request->start = $cuurent_month_start_date;
            $request->end = $cuurent_month_end_date;
        }


        // Filter by course if provided
        if ($request->has('course_id') && $request->course_id) {
            $events = $events->where('timetable.course_master_pk', $request->course_id);
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
        $colors = ['#ffffff'];

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
                'timetable.class_session',
                'timetable.subject_topic',
                'timetable.START_DATE',
                'timetable.END_DATE',
                'faculty_master.full_name as faculty_name',
                'venue_master.venue_name as venue_name',
                'timetable.group_name'
            )
            ->first();
        $groupIds = json_decode($event->group_name, true);
        $groupNames = DB::table('group_type_master_course_master_map')
            ->whereIn('pk', $groupIds)
            ->pluck('group_name');

        if ($event) {
            return response()->json([
                'id' => $event->pk,
                'topic' => $event->subject_topic ?? '', // if topic exists
                'start' => $event->START_DATE,
                // 'end' => $event->END_DATE,
                'faculty_name' => $event->faculty_name ?? '',
                'venue_name' => $event->venue_name ?? '',
                'class_session' => $event->class_session ?? '',
                'group_name' => $groupNames ?? '',
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
    function event_edit($id)
    {
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
            'type_names' => 'required|array|min:1',
            'type_names.*' => 'required|integer',
            'faculty' => 'required|integer',
            'faculty_type' => 'required|integer',
            'vanue' => 'required|integer',
            'shift' => 'required_if:shift_type,1',
            'start_time' => 'required_if:shift_type,2',
            'end_time' => 'required_if:shift_type,2',
            'start_datetime' => 'nullable|date',
            'end_datetime' => 'nullable|date',
        ], [
            'type_names.required' => 'The Group type names field is required.',
            'type_names.min' => 'Please select at least one Group type name.',
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
        $data_course_id =  get_Role_by_course();
        $query = DB::table('timetable as t')
            ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')
            ->join('faculty_master as f', 't.faculty_master', '=', 'f.pk')
            ->join('subject_master as s', 't.subject_master_pk', '=', 's.pk')
            ->leftJoin('topic_feedback as tf', 'tf.timetable_pk', '=', 't.pk');
        $query->whereExists(function ($q) {
            $q->select(DB::raw(1))
                ->from('topic_feedback as tf')
                ->whereColumn('tf.timetable_pk', 't.pk');
        });
        if ($data_course_id != '') {
            $query = $query->whereIn('t.course_master_pk', $data_course_id);
        }
        $query->select([
            't.pk as event_id',
            'c.course_name',
            'f.full_name as faculty_name',
            's.subject_name',
            't.subject_topic',
            DB::raw('ROUND(AVG(tf.rating), 1) as average_rating'),
        ])
            ->groupBy('t.pk', 'c.course_name', 'f.full_name', 's.subject_name', 't.subject_topic');

        // ✅ Faculty ko sirf apna data dikhana
        if (hasRole('Internal Faculty') || hasRole('Guest Faculty')) {
            $query->where('t.faculty_master', $facultyPk);
        }

        // ✅ Admin ko sabka dikhe
        $events = $query->orderByDesc('t.pk')->paginate(10);
        $activeEvents = $events; // For blade compatibility
        $archivedEvents = null; // Provided to avoid undefined variable in blade

        // Filters: courses, faculties, subjects (for dropdowns)
        $courses = CourseMaster::where('active_inactive', 1);
        if (!empty($data_course_id)) {
            $courses->whereIn('pk', $data_course_id);
        }
        $courses = $courses->select(['pk as id', 'course_name as name'])
            ->orderBy('course_name')
            ->get();

        $faculties = FacultyMaster::where('active_inactive', 1)
            ->select(['pk as id', 'full_name as name'])
            ->orderBy('full_name')
            ->get();

        $subjects = SubjectMaster::where('active_inactive', 1)
            ->select(['pk as id', 'subject_name as name'])
            ->orderBy('subject_name')
            ->get();

        return view('admin.feedback.index', compact('activeEvents', 'archivedEvents', 'events', 'courses', 'faculties', 'subjects'));
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
            $pendingQuery = DB::table('timetable as t')
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

                ->join('student_master_course__map as smcm', function ($join) use ($student_pk) {
                    $join->on('smcm.course_master_pk', '=', 't.course_master_pk')
                        ->where('smcm.student_master_pk', '=', $student_pk)
                        ->where('smcm.active_inactive', '=', 1);
                })

                ->where('t.feedback_checkbox', 1)

                ->whereNotExists(function ($sub) use ($student_pk) {
                    $sub->select(DB::raw(1))
                        ->from('topic_feedback as tf')
                        ->whereColumn('tf.timetable_pk', 't.pk')
                        ->where('tf.student_master_pk', $student_pk)
                        ->where('tf.is_submitted', 1);
                });

            if (hasRole('Student-OT')) {
                $pendingQuery
                    ->join('course_group_timetable_mapping as cgtm', 'cgtm.timetable_pk', '=', 't.pk')
                    ->join('student_course_group_map as scgm', 'scgm.group_type_master_course_master_map_pk', '=', 'cgtm.group_pk')
                    ->where('scgm.student_master_pk', $student_pk);
            }

            $pendingData = $pendingQuery
                ->orderByDesc('t.START_DATE')
                ->get();

            $submittedData = DB::table('topic_feedback as tf')
                ->select([
                    'tf.pk as feedback_pk',
                    'tf.timetable_pk',
                    'tf.topic_name',
                    'tf.presentation',
                    'tf.content',
                    'tf.remark',
                    'tf.rating',
                    'tf.is_submitted',
                    'tf.created_date',
                    't.subject_topic',
                    'f.full_name as faculty_name',
                    'c.course_name',
                    'v.venue_name',
                    DB::raw('t.START_DATE as from_date'),
                    't.class_session',
                    't.Ratting_checkbox',
                    't.Remark_checkbox',
                ])
                ->join('timetable as t', 'tf.timetable_pk', '=', 't.pk')
                ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')

                ->join('student_master_course__map as smcm', function ($join) use ($student_pk) {
                    $join->on('smcm.course_master_pk', '=', 't.course_master_pk')
                        ->where('smcm.student_master_pk', '=', $student_pk)
                        ->where('smcm.active_inactive', '=', 1);
                })

                ->leftJoin('faculty_master as f', 't.faculty_master', '=', 'f.pk')
                ->leftJoin('venue_master as v', 't.venue_id', '=', 'v.venue_id')
                ->where('tf.student_master_pk', $student_pk)
                ->where('tf.is_submitted', 1)
                ->orderByDesc('tf.created_date')
                ->get();

            $payload = [
                'username' => auth()->user()->user_name,
            ];

            $encrypted = Crypt::encryptString(json_encode($payload));

            $otUrl = route('feedback.get.studentFacultyFeedback', ['data' => $encrypted]);

            return view('admin.feedback.student_feedback', compact(
                'pendingData',
                'submittedData',
                'otUrl'
            ));
        } catch (\Throwable $e) {
            logger()->error('Error in studentFeedback: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }


    public function studentFacultyFeedback(Request $request)
    {
        try {

            $otUrl = '';

            if (!$request->has('token')) {
                abort(403, 'Missing token');
            }

            $key = config('services.moodle.key');
            $iv  = config('services.moodle.iv');

            $username = openssl_decrypt(
                base64_decode($request->token),
                'AES-128-CBC',
                $key,
                0,
                $iv
            );

            if (!$username) {
                abort(403, 'Invalid token');
            }

            $user = User::where('user_name', $username)->first();

            if (!$user) {
                abort(403, 'User not found');
            }

            Auth::login($user);
            $student_pk = auth()->user()->user_id;


            $pendingQuery = DB::table('timetable as t')
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

                ->join('student_master_course__map as smcm', function ($join) use ($student_pk) {
                    $join->on('smcm.course_master_pk', '=', 't.course_master_pk')
                        ->where('smcm.student_master_pk', '=', $student_pk)
                        ->where('smcm.active_inactive', '=', 1);
                })

                ->where('t.feedback_checkbox', 1)

                ->whereNotExists(function ($sub) use ($student_pk) {
                    $sub->select(DB::raw(1))
                        ->from('topic_feedback as tf')
                        ->whereColumn('tf.timetable_pk', 't.pk')
                        ->where('tf.student_master_pk', $student_pk)
                        ->where('tf.is_submitted', 1);
                });

            if (hasRole('Student-OT')) {
                $pendingQuery
                    ->join('course_group_timetable_mapping as cgtm', 'cgtm.timetable_pk', '=', 't.pk')
                    ->join('student_course_group_map as scgm', 'scgm.group_type_master_course_master_map_pk', '=', 'cgtm.group_pk')
                    ->where('scgm.student_master_pk', $student_pk);
            }

            $pendingData = $pendingQuery
                ->orderByDesc('t.START_DATE')
                ->get();

            $submittedData = DB::table('topic_feedback as tf')
                ->select([
                    'tf.pk as feedback_pk',
                    'tf.timetable_pk',
                    'tf.topic_name',
                    'tf.presentation',
                    'tf.content',
                    'tf.remark',
                    'tf.rating',
                    'tf.is_submitted',
                    'tf.created_date',
                    't.subject_topic',
                    'f.full_name as faculty_name',
                    'c.course_name',
                    'v.venue_name',
                    DB::raw('t.START_DATE as from_date'),
                    't.class_session',
                    't.Ratting_checkbox',
                    't.Remark_checkbox',
                ])
                ->join('timetable as t', 'tf.timetable_pk', '=', 't.pk')
                ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')

                ->join('student_master_course__map as smcm', function ($join) use ($student_pk) {
                    $join->on('smcm.course_master_pk', '=', 't.course_master_pk')
                        ->where('smcm.student_master_pk', '=', $student_pk)
                        ->where('smcm.active_inactive', '=', 1);
                })

                ->leftJoin('faculty_master as f', 't.faculty_master', '=', 'f.pk')
                ->leftJoin('venue_master as v', 't.venue_id', '=', 'v.venue_id')
                ->where('tf.student_master_pk', $student_pk)
                ->where('tf.is_submitted', 1)
                ->orderByDesc('tf.created_date')
                ->get();

            return view(
                'admin.feedback.student_feedback',
                compact('pendingData', 'submittedData', 'otUrl')
            );
        } catch (\Throwable $e) {
            logger()->error('Error in studentFacultyFeedback: ' . $e->getMessage());
            return back()->with('error', 'Something went wrong');
        }
    }



    public function submitFeedback(Request $request)
    {
        $request->validate([
            'timetable_pk' => 'required|array|min:1',
        ]);

        $studentId = auth()->user()->user_id;
        $now = now();

        // 👇 If individual button clicked
        if ($request->has('submit_index')) {
            $indexes = [$request->submit_index];
        }
        // 👇 Bulk submit
        else {
            $indexes = array_keys($request->timetable_pk);
        }

        $inserted = 0;

        foreach ($indexes as $i) {

            $content      = $request->content[$i] ?? null;
            $presentation = $request->presentation[$i] ?? null;
            $remarks      = $request->remarks[$i] ?? null;

            // ⛔ Skip empty rows
            if (empty($content) && empty($presentation) && empty($remarks)) {
                continue;
            }

            // ✅ Validate only filled fields
            $rules = [];
            if ($content) {
                $rules["content.$i"] = 'in:1,2,3,4,5';
            }
            if ($presentation) {
                $rules["presentation.$i"] = 'in:1,2,3,4,5';
            }
            if ($remarks) {
                $rules["remarks.$i"] = 'string|max:255';
            }

            if ($rules) {
                $request->validate($rules);
            }

            DB::table('topic_feedback')->insert([
                'timetable_pk'      => $request->timetable_pk[$i],
                'student_master_pk' => $studentId,
                'topic_name'        => $request->topic_name[$i] ?? '',
                'faculty_pk'        => $request->faculty_pk[$i] ?? null,
                'content'           => $content,
                'presentation'      => $presentation,
                'remark'            => $remarks,
                'created_date'      => $now,
                'modified_date'     => $now,
            ]);

            $inserted++;
        }

        if ($inserted === 0) {
            return back()->withErrors(['error' => 'Please fill at least one feedback before submitting.']);
        }

        return back()->with('success', 'Feedback submitted successfully!');
    }
}
