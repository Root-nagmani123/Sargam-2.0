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
use App\Services\FacultyFeedbackReportService;




class CalendarController extends Controller
{
    /**
     * Limit timetable rows to sessions assigned to the given faculty.
     */
    private function scopeTimetableForFaculty($query, int $facultyPk)
    {
        return $query->where(function ($q) use ($facultyPk) {
            $q->where('timetable.faculty_master', $facultyPk)
                ->orWhereRaw('JSON_CONTAINS(COALESCE(NULLIF(timetable.faculty_master, ""), "[]"), ?)', ['"'.$facultyPk.'"'])
                ->orWhereRaw('FIND_IN_SET(?, timetable.faculty_master)', [$facultyPk])
                ->orWhereRaw('JSON_CONTAINS(COALESCE(NULLIF(timetable.internal_faculty, ""), "[]"), ?)', ['"'.$facultyPk.'"'])
                ->orWhereRaw('FIND_IN_SET(?, timetable.internal_faculty)', [$facultyPk]);
        });
    }

    public function index(Request $request)
    {
        // OT (Officer Trainee) users get their own dedicated calendar page.
        if (hasRole('Student-OT')) {
            return redirect()->route('calendar.ot.index');
        }

        \Log::info('CalendarController index: User authenticated via middleware', [
            'user' => auth()->user()->user_name,
            'user_id' => auth()->id(),
            'session_id' => session()->getId()
        ]);

        $data_course_id = get_Role_by_course();

        $courseMaster = CourseMaster::where('course_master.active_inactive', 1)
            ->whereDate('end_date', '>=', today());

        // Faculty see courses from their timetable / coordinator assignments, not role mapping.
        if (is_faculty_portal_user()) {
            $facultyPk = get_auth_faculty_master_pk();
            if ($facultyPk) {
                $facultyCourseIds = app(FacultyFeedbackReportService::class)->getAccessibleCourseIds($facultyPk);
                $courseMaster = $facultyCourseIds->isNotEmpty()
                    ? $courseMaster->whereIn('course_master.pk', $facultyCourseIds)
                    : $courseMaster->whereRaw('1 = 0');
            } else {
                $courseMaster = $courseMaster->whereRaw('1 = 0');
            }
        } elseif (!hasRole('Student-OT') && !empty($data_course_id)) {
            // Students are scoped by enrolment (the join below), not by role. Skipping the
            // role-course filter for them avoids get_Role_by_course()'s [-1] (students have
            // no Spatie role), which would otherwise wipe out their course list.
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

    /**
     * Dedicated OT (Officer Trainee / Student-OT) calendar page.
     * Mirrors index() but is always scoped to the logged-in student and
     * renders its own blade with its own (OT) data endpoints.
     */
    public function otIndex(Request $request)
    {
        \Log::info('CalendarController otIndex: OT calendar accessed', [
            'user' => auth()->user()->user_name,
            'user_id' => auth()->id(),
            'session_id' => session()->getId()
        ]);

        // The OT page is always for a Student-OT, who has no Spatie role — so
        // get_Role_by_course() would return [-1] and wipe out the list. Scope by
        // enrolment (the join below) only, never by the role-course filter.
        //
        // No end_date restriction: a student must still see their enrolled
        // course timetable after the course has ended (to review past sessions,
        // give feedback, etc.). The enrolment mapping is the only scope.
        $courseMaster = CourseMaster::where('course_master.active_inactive', 1);

        // OT page is always scoped to the student's active course mappings.
        $courseMaster = $courseMaster->leftJoin(
            'student_master_course__map',
            'student_master_course__map.course_master_pk',
            '=',
            'course_master.pk'
        )
            ->where('student_master_course__map.student_master_pk', auth()->user()->user_id)
            ->where('student_master_course__map.active_inactive', 1);

        $courseMaster = $courseMaster->select('course_master.pk', 'course_name', 'couse_short_name', 'course_year')
            ->get();

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

        return view('admin.calendar.ot-index', compact(
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

        // We'll consider monday-sunday display (7 days) but fetch full week for safety
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
            ->leftJoin('venue_master', 'timetable.venue_id', '=', 'venue_master.venue_id');

        $data_course_id = get_Role_by_course();
        if (is_faculty_portal_user()) {
            $facultyPk = get_auth_faculty_master_pk();
            if ($facultyPk) {
                $events = $this->scopeTimetableForFaculty($events, $facultyPk);
            } else {
                $events = $events->whereRaw('1 = 0');
            }
        } elseif (!hasRole('Student-OT') && !empty($data_course_id)) {
            $events = $events->whereIn('timetable.course_master_pk', $data_course_id);
        }

        $events = $events->whereBetween('timetable.START_DATE', [$weekStart->toDateString(), $weekEnd->toDateString()])
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
            'faculty' => 'required|array|min:1',
            'faculty.*' => 'required|integer',
            'faculty_type' => 'required|integer',
            'vanue' => 'required|integer',
            'shift' => 'required_if:shift_type,1',
            'start_time' => 'required_if:shift_type,2',
            'end_time' => 'required_if:shift_type,2',
        ], [
            'type_names.required' => 'The Group type names field is required.',
            'type_names.min' => 'Please select at least one Group type name.',
            'faculty.required' => 'The Faculty field is required.',
            'faculty.min' => 'Please select at least one Faculty.',
        ]);

        $event = new CalendarEvent();
        $event->course_master_pk = $request->Course_name;
        $event->subject_master_pk = $request->subject_name;
        $event->subject_module_master_pk = $request->subject_module;
        $event->subject_topic = $request->topic;
        $event->course_group_type_master = $request->group_type;
        $event->group_name = json_encode($request->type_names ?? []);
        $event->faculty_master = json_encode($request->faculty ?? []);
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
            ->join('venue_master', 'timetable.venue_id', '=', 'venue_master.venue_id');

        // Student-OT Role
        if (hasRole('Student-OT')) {

            $student_pk = auth()->user()->user_id;

            $events = $events
                ->join('course_group_timetable_mapping', 'course_group_timetable_mapping.timetable_pk', '=', 'timetable.pk')
                ->join('student_course_group_map', 'student_course_group_map.group_type_master_course_master_map_pk', '=', 'course_group_timetable_mapping.group_pk')
                ->where('student_course_group_map.student_master_pk', $student_pk);
        }

        // Scope events by user type (faculty assignments vs training-admin course alignment).
        if (is_faculty_portal_user()) {
            $facultyPk = get_auth_faculty_master_pk();
            if ($facultyPk) {
                $events = $this->scopeTimetableForFaculty($events, $facultyPk);
            } else {
                $events = $events->whereRaw('1 = 0');
            }
        } else {
            $data_course_id = get_Role_by_course();
            if (!hasRole('Student-OT') && !empty($data_course_id)) {
                $events = $events->whereIn('timetable.course_master_pk', $data_course_id);
            }
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
                'venue_master.venue_name as venue_name'
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

            // Get faculty names from JSON (handle both old integer and new JSON array format)
            $facultyIds = json_decode($event->faculty_master, true);
            $facultyNames = '';
            if (is_array($facultyIds) && !empty($facultyIds)) {
                $facultyNames = DB::table('faculty_master')
                    ->whereIn('pk', $facultyIds)
                    ->pluck('full_name')
                    ->implode(', ');
            } elseif (!is_array($facultyIds) && !empty($event->faculty_master)) {
                // Old format: integer value
                $facultyNames = DB::table('faculty_master')
                    ->where('pk', $event->faculty_master)
                    ->value('full_name') ?? '';
            }

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

            // For all-day events, FullCalendar expects an exclusive end date.
            // If start and end are the same, advance end by +1 day so it renders.
            if ($allDay) {
                try {
                    $startDateTime = Carbon::parse($event->START_DATE)->format('Y-m-d');
                    $endDateTime = Carbon::parse($event->END_DATE ?: $event->START_DATE)
                        ->addDay()
                        ->format('Y-m-d');
                } catch (\Exception $e) {
                    // Fallback: ensure at least a one-day span
                    $startDateTime = $event->START_DATE;
                    $endDateTime = Carbon::parse($event->START_DATE)->addDay()->format('Y-m-d');
                }
            }

            return [
                'id' => $event->pk,
                'title' => $event->subject_topic,
                'start' => $startDateTime,
                'end'   => $endDateTime,
                'vanue'   => $event->venue_name,
                'faculty_name'   => $facultyNames,
                'backgroundColor' => $colors[array_rand($colors)],  // background color for event
                'borderColor' => $colors[array_rand($colors)],  // border color for event
                // Use dark text for better contrast with light backgrounds
                'textColor' => '#111827',
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
                    // End must be exclusive for all-day events (+1 day)
                    'end' => $holiday->holiday_date->copy()->addDay()->format('Y-m-d'),
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

        $eventQuery = DB::table('timetable')
            ->join('venue_master', 'timetable.venue_id', '=', 'venue_master.venue_id')
            ->where('timetable.pk', $eventId);

        if (is_faculty_portal_user()) {
            $facultyPk = get_auth_faculty_master_pk();
            if ($facultyPk) {
                $eventQuery = $this->scopeTimetableForFaculty($eventQuery, $facultyPk);
            } else {
                $eventQuery->whereRaw('1 = 0');
            }
        } else {
            $data_course_id = get_Role_by_course();
            if (!hasRole('Student-OT') && !empty($data_course_id)) {
                $eventQuery->whereIn('timetable.course_master_pk', $data_course_id);
            }
        }

        $event = $eventQuery->select(
                'timetable.pk',
                'timetable.class_session',
                'timetable.subject_topic',
                'timetable.START_DATE',
                'timetable.END_DATE',
                'timetable.faculty_master',
                'venue_master.venue_name as venue_name',
                'timetable.group_name',
                'timetable.internal_faculty'
            )
            ->first();

        if (!$event) {
            return response()->json(['error' => 'Event not found'], 404);
        }

        $groupIds = json_decode($event->group_name, true) ?? [];
        $internalFacultyIds = json_decode($event->internal_faculty, true) ?? [];

        // Handle both old integer format and new JSON array format
        $facultyIds = json_decode($event->faculty_master, true);
        if (!is_array($facultyIds)) {
            // Old format: integer value, convert to array
            $facultyIds = $event->faculty_master ? [$event->faculty_master] : [];
        }

        $groupNames = DB::table('group_type_master_course_master_map')
            ->whereIn('pk', $groupIds ?: [])
            ->pluck('group_name');

        $internalFacultyNames = DB::table('faculty_master')
            ->whereIn('pk', $internalFacultyIds ?: [])
            ->pluck('full_name');

        $facultyNames = DB::table('faculty_master')
            ->whereIn('pk', $facultyIds ?: [])
            ->pluck('full_name');

        return response()->json([
            'id' => $event->pk,
            'topic' => $event->subject_topic ?? '', // if topic exists
            'start' => $event->START_DATE,
            // 'end' => $event->END_DATE,
            'faculty_name' => $facultyNames->implode(', '),
            'internal_faculty' => $internalFacultyNames->implode(', '),
            'venue_name' => $event->venue_name ?? '',
            'class_session' => $event->class_session ?? '',
            'group_name' => $groupNames->implode(', ') ?? '',
        ]);
    }
    /**
     * OT calendar events feed. Always scoped to the logged-in student's groups.
     * Independent endpoint for the dedicated OT calendar page.
     */
    public function otFullCalendarDetails(Request $request)
    {
        $student_pk = auth()->user()->user_id;

        $events = DB::table('timetable')
            ->join('venue_master', 'timetable.venue_id', '=', 'venue_master.venue_id')
            ->join('course_group_timetable_mapping', 'course_group_timetable_mapping.timetable_pk', '=', 'timetable.pk')
            ->join('student_course_group_map', 'student_course_group_map.group_type_master_course_master_map_pk', '=', 'course_group_timetable_mapping.group_pk')
            ->where('student_course_group_map.student_master_pk', $student_pk);

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
                'venue_master.venue_name as venue_name'
            )
            ->get();

        // Array of some sample colors
        $colors = ['#ffffff'];

        // Assign color to each event
        $events = $events->map(function ($event) use ($colors) {
            $startDateTime = $event->START_DATE;
            $endDateTime = $event->END_DATE;
            $allDay = false;

            // Get faculty names from JSON (handle both old integer and new JSON array format)
            $facultyIds = json_decode($event->faculty_master, true);
            $facultyNames = '';
            if (is_array($facultyIds) && !empty($facultyIds)) {
                $facultyNames = DB::table('faculty_master')
                    ->whereIn('pk', $facultyIds)
                    ->pluck('full_name')
                    ->implode(', ');
            } elseif (!is_array($facultyIds) && !empty($event->faculty_master)) {
                $facultyNames = DB::table('faculty_master')
                    ->where('pk', $event->faculty_master)
                    ->value('full_name') ?? '';
            }

            // Check if class_session exists and contains a time range with dash
            if (!empty($event->class_session) && strpos($event->class_session, '-') !== false) {
                $timeRange = trim($event->class_session);
                $parts = explode('-', $timeRange);

                if (count($parts) === 2) {
                    $startTime = trim($parts[0]);
                    $endTime = trim($parts[1]);

                    $startTimestamp = strtotime($startTime);
                    $endTimestamp = strtotime($endTime);

                    if ($startTimestamp !== false && $endTimestamp !== false) {
                        $startTime24 = date('H:i', $startTimestamp);
                        $endTime24 = date('H:i', $endTimestamp);

                        $startDateTime = $event->START_DATE . 'T' . $startTime24 . ':00';
                        $endDateTime = $event->END_DATE . 'T' . $endTime24 . ':00';
                        $allDay = false;
                    } else {
                        $allDay = true;
                    }
                } else {
                    $allDay = true;
                }
            } else {
                $allDay = true;
            }

            if ($allDay) {
                try {
                    $startDateTime = Carbon::parse($event->START_DATE)->format('Y-m-d');
                    $endDateTime = Carbon::parse($event->END_DATE ?: $event->START_DATE)
                        ->addDay()
                        ->format('Y-m-d');
                } catch (\Exception $e) {
                    $startDateTime = $event->START_DATE;
                    $endDateTime = Carbon::parse($event->START_DATE)->addDay()->format('Y-m-d');
                }
            }

            return [
                'id' => $event->pk,
                'title' => $event->subject_topic,
                'start' => $startDateTime,
                'end'   => $endDateTime,
                'vanue'   => $event->venue_name,
                'faculty_name'   => $facultyNames,
                'backgroundColor' => $colors[array_rand($colors)],
                'borderColor' => $colors[array_rand($colors)],
                'textColor' => '#111827',
                'allDay' => $allDay,
                'display' => 'block',
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
                        $backgroundColor = '#dc3545';
                        break;
                    case 'restricted':
                        $backgroundColor = '#ffc107';
                        $textColor = '#000';
                        break;
                    case 'optional':
                        $backgroundColor = '#17a2b8';
                        break;
                }

                return [
                    'id' => 'holiday_' . $holiday->id,
                    'title' => $holiday->holiday_name . ' (' . ucfirst($holiday->holiday_type) . ')',
                    'start' => $holiday->holiday_date->format('Y-m-d'),
                    'end' => $holiday->holiday_date->copy()->addDay()->format('Y-m-d'),
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

        $allEvents = $events->merge($holidays);

        return response()->json($allEvents);
    }

    /**
     * OT single event details. Separate endpoint for the OT calendar page;
     * shares the read-only detail logic with SingleCalendarDetails().
     */
    public function otSingleCalendarDetails(Request $request)
    {
        $eventId = $request->id;

        $event = DB::table('timetable')
            ->join('venue_master', 'timetable.venue_id', '=', 'venue_master.venue_id')
            ->leftJoin('subject_master', 'timetable.subject_master_pk', '=', 'subject_master.pk')
            ->leftJoin('subject_module_master', 'timetable.subject_module_master_pk', '=', 'subject_module_master.pk')
            ->where('timetable.pk', $eventId)
            ->select(
                'timetable.pk',
                'timetable.class_session',
                'timetable.subject_topic',
                'timetable.START_DATE',
                'timetable.END_DATE',
                'timetable.faculty_master',
                'timetable.group_name',
                'timetable.internal_faculty',
                'venue_master.venue_name as venue_name',
                'subject_master.subject_name as subject_name',
                'subject_module_master.module_name as module_name'
            )
            ->first();

        if (!$event) {
            return response()->json(['error' => 'Event not found'], 404);
        }

        $groupIds = json_decode($event->group_name, true) ?? [];
        $internalFacultyIds = json_decode($event->internal_faculty, true) ?? [];

        $facultyIds = json_decode($event->faculty_master, true);
        if (!is_array($facultyIds)) {
            $facultyIds = $event->faculty_master ? [$event->faculty_master] : [];
        }

        $groupNames = DB::table('group_type_master_course_master_map')
            ->whereIn('pk', $groupIds ?: [])
            ->pluck('group_name');

        $internalFacultyNames = DB::table('faculty_master')
            ->whereIn('pk', $internalFacultyIds ?: [])
            ->pluck('full_name');

        $facultyNames = DB::table('faculty_master')
            ->whereIn('pk', $facultyIds ?: [])
            ->pluck('full_name');

        return response()->json([
            'id' => $event->pk,
            // Card header = subject/module name; falls back to topic when missing.
            'title' => $event->subject_name ?: ($event->module_name ?: $event->subject_topic),
            'subject_name' => $event->subject_name ?? '',
            'module_name' => $event->module_name ?? '',
            'topic' => $event->subject_topic ?? '',
            'start' => $event->START_DATE,
            'faculty_name' => $facultyNames->implode(', '),
            'internal_faculty' => $internalFacultyNames->implode(', '),
            'venue_name' => $event->venue_name ?? '',
            'class_session' => $event->class_session ?? '',
            'group_name' => $groupNames->implode(', ') ?? '',
        ]);
    }

    /**
     * Download the OT timetable as a PDF for the currently viewed range.
     * Always scoped to the logged-in student's groups.
     */
    public function otDownloadPdf(Request $request)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(120);

        $student_pk = auth()->user()->user_id;

        // Resolve the course up-front so the timetable window can default to the
        // course's own start/end dates instead of a hard-coded month.
        $course = null;
        if ($request->filled('course_id')) {
            $course = CourseMaster::where('pk', $request->course_id)
                ->select('course_name', 'couse_short_name', 'course_year', 'start_year', 'end_date')
                ->first();
        }

        // Date range: an explicit request range wins; otherwise align to the
        // course's start/end dates, falling back to the current month.
        $start = $request->start
            ?: (($course && !empty($course->start_year))
                ? Carbon::parse($course->start_year)->toDateString()
                : Carbon::now()->startOfMonth()->toDateString());
        $end = $request->end
            ?: (($course && !empty($course->end_date))
                ? Carbon::parse($course->end_date)->toDateString()
                : Carbon::now()->endOfMonth()->toDateString());

        $events = DB::table('timetable')
            ->join('venue_master', 'timetable.venue_id', '=', 'venue_master.venue_id')
            ->join('course_group_timetable_mapping', 'course_group_timetable_mapping.timetable_pk', '=', 'timetable.pk')
            ->join('student_course_group_map', 'student_course_group_map.group_type_master_course_master_map_pk', '=', 'course_group_timetable_mapping.group_pk')
            ->where('student_course_group_map.student_master_pk', $student_pk)
            ->whereDate('timetable.START_DATE', '>=', $start)
            ->whereDate('timetable.END_DATE', '<=', $end);

        if ($request->filled('course_id')) {
            $events = $events->where('timetable.course_master_pk', $request->course_id);
        }

        $events = $events
            ->select('timetable.*', 'venue_master.venue_name as venue_name')
            ->orderBy('timetable.START_DATE')
            ->orderBy('timetable.class_session')
            ->get();

        // Resolve the course name for every course referenced by the events, so
        // each card can show its own course even when the range spans several.
        $courseNameMap = CourseMaster::whereIn('pk', $events->pluck('course_master_pk')->filter()->unique()->values())
            ->pluck('course_name', 'pk');
        $multiCourse = $courseNameMap->count() > 1;

        // Parse a session label ("0930 - 1030", "09:00 AM - 12:00 PM") into
        // start/end minutes-from-midnight. Returns null when no time is present.
        $parseTimes = function ($label) {
            if (!preg_match_all('/(\d{1,2})[:.]?(\d{2})\s*(am|pm)?/i', (string) $label, $mm, PREG_SET_ORDER)) {
                return null;
            }
            $toMin = function ($m) {
                $h   = (int) $m[1];
                $min = (int) $m[2];
                $ap  = isset($m[3]) ? strtolower($m[3]) : '';
                if ($ap === 'pm' && $h < 12)  { $h += 12; }
                if ($ap === 'am' && $h === 12) { $h = 0; }
                return ($h * 60) + $min;
            };
            return [
                'start' => $toMin($mm[0]),
                'end'   => isset($mm[1]) ? $toMin($mm[1]) : null,
            ];
        };

        // Tea Break / Lunch rows get merged into a full-width band.
        $isBreakTopic = function ($topic) {
            return (bool) preg_match('/\b(tea\s*break|lunch|break|recess)\b/i', (string) $topic);
        };

        // Minutes-from-midnight -> 4-digit "0930".
        $fmt4 = function ($min) {
            if ($min === null) { return ''; }
            return sprintf('%02d%02d', intdiv($min, 60) % 24, $min % 60);
        };

        // Resolve every event to start/end minutes plus display fields, per day.
        $eventsByDay = [];   // [Y-m-d] => [ ['start','end','topic',...], ... ]
        $venueCounts = [];

        foreach ($events as $event) {
            $dateKey = Carbon::parse($event->START_DATE)->format('Y-m-d');

            $facultyIds = json_decode($event->faculty_master, true);
            if (!is_array($facultyIds)) {
                $facultyIds = $event->faculty_master ? [$event->faculty_master] : [];
            }
            $facultyNames = !empty($facultyIds)
                ? DB::table('faculty_master')->whereIn('pk', $facultyIds)->pluck('full_name')->implode(', ')
                : '';

            $rawLabel = trim((string) $event->class_session);
            $t = $parseTimes($rawLabel);
            if ($t === null) {
                if (strlen((string) $event->START_DATE) > 10) {
                    $s = Carbon::parse($event->START_DATE);
                    $e = $event->END_DATE ? Carbon::parse($event->END_DATE) : null;
                    $t = ['start' => ($s->hour * 60) + $s->minute, 'end' => $e ? (($e->hour * 60) + $e->minute) : null];
                } else {
                    $t = ['start' => null, 'end' => null];
                }
            }
            // Without a resolvable start time the event can't be placed on the grid.
            if (($t['start'] ?? null) === null) {
                continue;
            }
            $st = $t['start'];
            $en = ($t['end'] !== null && $t['end'] > $st) ? $t['end'] : $st + 60;

            $topic     = $event->subject_topic ?: 'Session';
            $timeLabel = ($rawLabel !== '' && preg_match('/\d/', $rawLabel))
                ? $rawLabel
                : ($fmt4($st) . ' - ' . $fmt4($en));

            $eventsByDay[$dateKey][] = [
                'start'   => $st,
                'end'     => $en,
                'topic'   => $topic,
                'faculty' => $facultyNames,
                'venue'   => $event->venue_name,
                'course'  => $courseNameMap[$event->course_master_pk] ?? '',
                'time'    => $timeLabel,
                'isBreak' => $isBreakTopic($topic),
            ];

            if (!empty($event->venue_name)) {
                $venueCounts[$event->venue_name] = ($venueCounts[$event->venue_name] ?? 0) + 1;
            }
        }

        foreach ($eventsByDay as $k => $evs) {
            usort($evs, fn ($a, $b) => $a['start'] <=> $b['start']);
            $eventsByDay[$k] = $evs;
        }

        $courseStartWeek = ($course && !empty($course->start_year))
            ? Carbon::parse($course->start_year)->startOfWeek(Carbon::MONDAY)
            : null;

        // Group the range into Mon–Sun weeks; one page per week that has events.
        $weeks   = [];
        $cursor  = Carbon::parse($start)->startOfWeek(Carbon::MONDAY);
        $lastDay = Carbon::parse($end)->endOfWeek(Carbon::SUNDAY);

        while ($cursor <= $lastDay) {
            $weekDays   = [];
            $hasEvents  = false;
            $boundaries = [];   // distinct start/end minutes across the week

            for ($i = 0; $i < 7; $i++) {
                $d   = $cursor->copy()->addDays($i);
                $key = $d->format('Y-m-d');
                $evs = $eventsByDay[$key] ?? [];
                // Show Sat/Sun only when those days have sessions; Mon–Fri always.
                if ($d->isWeekend() && empty($evs)) {
                    continue;
                }
                if (!empty($evs)) {
                    $hasEvents = true;
                    foreach ($evs as $e) {
                        $boundaries[$e['start']] = true;
                        $boundaries[$e['end']]   = true;
                    }
                }
                $weekDays[] = [
                    'key'     => $key,
                    'dayName' => $d->format('l'),
                    'label'   => $d->format('d.m.Y'),
                    'events'  => $evs,
                ];
            }

            if (!$hasEvents) { $cursor->addWeek(); continue; }

            // Canonical rows = intervals that begin at an actual session start.
            // (Intervals that exist only as a gap inside a long session are dropped,
            // so we never create unnecessary empty rows.)
            $bs = array_keys($boundaries);
            sort($bs);
            $rowItv = [];
            for ($c = 0; $c < count($bs) - 1; $c++) {
                $rs = $bs[$c];
                $re = $bs[$c + 1];
                $startsHere = false;
                foreach ($weekDays as $wd) {
                    foreach ($wd['events'] as $e) {
                        if ($e['start'] === $rs) { $startsHere = true; break 2; }
                    }
                }
                if ($startsHere) {
                    $rowItv[] = ['start' => $rs, 'end' => $re];
                }
            }
            $rowCount = count($rowItv);
            if ($rowCount === 0) { $cursor->addWeek(); continue; }

            // Per-day cells with vertical merge (rowspan) over the rows a session covers.
            $dayCells = [];   // [dayKey][rowIndex] = cell
            foreach ($weekDays as $wd) {
                $evs      = $wd['events'];
                $occupied = array_fill(0, $rowCount, false);
                $cells    = [];
                for ($r = 0; $r < $rowCount; $r++) {
                    if ($occupied[$r]) {
                        $cells[$r] = ['state' => 'skip'];
                        continue;
                    }
                    $rStart   = $rowItv[$r]['start'];
                    $rEnd     = $rowItv[$r]['end'];
                    $starting = array_values(array_filter($evs, fn ($e) => $e['start'] === $rStart && $e['end'] >= $rEnd));

                    if (!empty($starting)) {
                        $maxEnd = max(array_map(fn ($e) => $e['end'], $starting));
                        $span   = 0;
                        for ($k = $r; $k < $rowCount; $k++) {
                            if ($rowItv[$k]['start'] >= $rStart && $rowItv[$k]['end'] <= $maxEnd) {
                                $span++;
                                $occupied[$k] = true;
                            } else {
                                break;
                            }
                        }
                        if ($span < 1) { $span = 1; }
                        $allBreak = true;
                        foreach ($starting as $e) { if (!$e['isBreak']) { $allBreak = false; break; } }
                        $cells[$r] = ['state' => 'render', 'rowspan' => $span, 'events' => $starting, 'isBreak' => $allBreak];
                    } else {
                        $cells[$r] = ['state' => 'render', 'rowspan' => 1, 'events' => [], 'isBreak' => false];
                    }
                }
                $dayCells[$wd['key']] = $cells;
            }

            // Assemble rows; a row becomes a full-width break band when every day's
            // cell there is a single-row break or empty (no session crosses it).
            $rows = [];
            for ($r = 0; $r < $rowCount; $r++) {
                $isBand = true; $hasBreak = false; $bandTopic = '';
                foreach ($weekDays as $wd) {
                    $c = $dayCells[$wd['key']][$r];
                    if ($c['state'] === 'skip') { $isBand = false; break; }
                    if (empty($c['events'])) { continue; }
                    if (!empty($c['isBreak']) && $c['rowspan'] === 1) {
                        $hasBreak = true;
                        if ($bandTopic === '') { $bandTopic = $c['events'][0]['topic']; }
                    } else {
                        $isBand = false; break;
                    }
                }
                if (!$hasBreak) { $isBand = false; }

                $cellsForRow = [];
                foreach ($weekDays as $wd) {
                    $cellsForRow[$wd['key']] = $dayCells[$wd['key']][$r];
                }

                $rows[] = [
                    'from'      => $fmt4($rowItv[$r]['start']),
                    'to'        => $fmt4($rowItv[$r]['end']),
                    'isBand'    => $isBand,
                    'bandTopic' => $bandTopic,
                    'cells'     => $cellsForRow,
                ];
            }

            $weekNumber = $courseStartWeek
                ? $courseStartWeek->diffInWeeks($cursor) + 1
                : (int) $cursor->copy()->addDays(3)->format('W');

            $firstDay = Carbon::parse($weekDays[0]['key']);
            $lastDayD = Carbon::parse($weekDays[count($weekDays) - 1]['key']);

            $dayHeaders = array_map(
                fn ($wd) => ['key' => $wd['key'], 'dayName' => $wd['dayName'], 'label' => $wd['label']],
                $weekDays
            );

            $weeks[] = [
                'weekNumber' => $weekNumber,
                'days'       => $dayHeaders,
                'rows'       => $rows,
                'rangeLabel' => $firstDay->format('d M Y') . ' - ' . $lastDayD->format('d M Y'),
            ];

            $cursor->addWeek();
        }

        $primaryVenue = '';
        if (!empty($venueCounts)) {
            arsort($venueCounts);
            $primaryVenue = array_key_first($venueCounts);
        }

        // Course duration line, e.g. "8 December 2025 to 17 April 2026".
        $courseStartDate = ($course && !empty($course->start_year))
            ? Carbon::parse($course->start_year)->format('j F Y') : '';
        $courseEndDate = ($course && !empty($course->end_date))
            ? Carbon::parse($course->end_date)->format('j F Y') : '';

        $courseDuration = '';
        if ($courseStartDate && $courseEndDate) {
            $courseDuration = $courseStartDate . ' to ' . $courseEndDate;
        }

        // Embed logos as base64 data URIs — DomPDF can't reliably resolve
        // Windows filesystem paths used in <img src>.
        $toDataUri = function (string $path): string {
            if (!is_file($path)) {
                return '';
            }
            $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = in_array($ext, ['jpg', 'jpeg']) ? 'image/jpeg' : 'image/png';
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
        };

        $data = [
            'weeks'           => $weeks,
            'rangeStart'      => Carbon::parse($start)->format('d M Y'),
            'rangeEnd'        => Carbon::parse($end)->format('d M Y'),
            'course'          => $course,
            'courseStartDate' => $courseStartDate,
            'courseEndDate'   => $courseEndDate,
            'courseDuration'  => $courseDuration,
            'multiCourse'     => $multiCourse,
            'primaryVenue'    => $primaryVenue,
            // Optional footer note / announcement (no hardcoding — driven by request).
            'footerNote'      => trim((string) $request->input('note', '')),
            'studentName'     => auth()->user()->user_name ?? '',
            'logoLeft'        => $toDataUri(public_path('admin_assets/images/logos/logo_new.png')),
            'logoRight'       => $toDataUri(public_path('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png')),
            // Pre-shaped Devanagari academy name (DomPDF can't shape Indic text).
            'titleHindi'      => $toDataUri(public_path('admin_assets/images/logos/lbsnaa-title-hi.png')),
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.calendar.pdf.ot-timetable-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'          => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                // Enables the <script type="text/php"> page-number block in the view.
                'isPhpEnabled'         => true,
                'dpi'                  => 96,
            ]);

        $fileName = 'time-table-' . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($fileName);
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

        // Decode faculty_master JSON to array for edit form (handle both old integer and new JSON array format)
        $facultyMaster = json_decode($event->faculty_master, true);
        if (!is_array($facultyMaster)) {
            // Old format: integer value, convert to array
            $event->faculty_master = $event->faculty_master ? [$event->faculty_master] : [];
        } else {
            $event->faculty_master = $facultyMaster;
        }

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
            'faculty' => 'required|array|min:1',
            'faculty.*' => 'required|integer',
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
            'faculty.required' => 'The Faculty field is required.',
            'faculty.min' => 'Please select at least one Faculty.',
        ]);

        $event = CalendarEvent::findOrFail($id);
        $event->course_master_pk = $request->Course_name;
        $event->subject_master_pk = $request->subject_name;
        $event->subject_module_master_pk = $request->subject_module;
        $event->subject_topic = $request->topic;
        $event->course_group_type_master = $request->group_type;
        $event->group_name = json_encode($request->type_names ?? []);
        $event->faculty_master = json_encode($request->faculty ?? []);
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
                    't.faculty_master as faculty_json', // Keep original JSON
                    'f.pk as faculty_pk', // Get individual faculty PK
                    'f.full_name as faculty_name',
                    'c.course_name',
                    'v.venue_name',
                    DB::raw('t.START_DATE as from_date'),
                    't.class_session',
                    // Add field to extract session end time for sorting (handles both "HH:MM AM - HH:MM PM" and "HH:MM to HH:MM")
                    DB::raw("
                    CASE
                        WHEN t.class_session LIKE '% - %' THEN
                            STR_TO_DATE(TRIM(SUBSTRING_INDEX(t.class_session, ' - ', -1)), '%h:%i %p')
                        WHEN t.class_session LIKE '% to %' THEN
                            STR_TO_DATE(TRIM(SUBSTRING_INDEX(t.class_session, ' to ', -1)), '%H:%i')
                        ELSE NULL
                    END as session_end_time
                ")
                ])
                ->leftJoin('faculty_master as f', function ($join) {
                    $join->whereRaw("
                    (
                        JSON_VALID(t.faculty_master)
                        AND JSON_CONTAINS(
                            t.faculty_master,
                            JSON_QUOTE(CAST(f.pk AS CHAR))
                        )
                    )
                    OR
                    (
                        NOT JSON_VALID(t.faculty_master)
                        AND CAST(t.faculty_master AS CHAR) = CAST(f.pk AS CHAR)
                    )
                ");
                })
                ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')
                ->join('venue_master as v', 't.venue_id', '=', 'v.venue_id')
                ->join('student_master_course__map as smcm', function ($join) use ($student_pk) {
                    $join->on('smcm.course_master_pk', '=', 't.course_master_pk')
                        ->where('smcm.student_master_pk', '=', $student_pk)
                        ->where('smcm.active_inactive', '=', 1);
                })
                ->where('t.feedback_checkbox', 1)
                ->join('course_student_attendance as csa', function ($join) use ($student_pk) {
                    $join->on('csa.timetable_pk', '=', 't.pk')
                        ->where('csa.Student_master_pk', '=', $student_pk)
                        ->where('csa.status', '1');
                })
                ->whereNotExists(function ($sub) use ($student_pk) {
                    $sub->select(DB::raw(1))
                        ->from('topic_feedback as tf')
                        ->whereColumn('tf.timetable_pk', 't.pk')
                        ->where('tf.student_master_pk', $student_pk)
                        ->where('tf.faculty_pk', DB::raw('f.pk')) // Check for this specific faculty
                        ->where('tf.is_submitted', 1);
                })
                // Show only sessions whose end time has passed (handles both "HH:MM AM - HH:MM PM" and "HH:MM to HH:MM")
                ->whereRaw("
                TIMESTAMP(
                    t.END_DATE,
                    CASE
                        WHEN t.class_session LIKE '% - %' THEN
                            STR_TO_DATE(TRIM(SUBSTRING_INDEX(t.class_session, ' - ', -1)), '%h:%i %p')
                        WHEN t.class_session LIKE '% to %' THEN
                            STR_TO_DATE(TRIM(SUBSTRING_INDEX(t.class_session, ' to ', -1)), '%H:%i')
                        ELSE NULL
                    END
                ) <= NOW()
            ");

            if (hasRole('Student-OT')) {
                $pendingQuery
                    ->join('course_group_timetable_mapping as cgtm', 'cgtm.timetable_pk', '=', 't.pk')
                    ->join('student_course_group_map as scgm', 'scgm.group_type_master_course_master_map_pk', '=', 'cgtm.group_pk')
                    ->where('scgm.student_master_pk', $student_pk);
            }

            $pendingData = $pendingQuery
                ->orderBy('t.START_DATE', 'asc')
                ->orderBy('session_end_time', 'asc')
                ->get()
                ->unique(function ($item) {
                    // Ensure each faculty-timetable combination is unique
                    return $item->timetable_pk . '_' . $item->faculty_pk;
                })
                ->values(); // Reset array keys

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
                    'tf.faculty_pk',
                    't.subject_topic',
                    // Get faculty name from faculty_master using tf.faculty_pk
                    'fm.full_name as faculty_name',
                    'c.course_name',
                    'v.venue_name',
                    DB::raw('t.START_DATE as from_date'),
                    't.class_session',
                    't.Ratting_checkbox',
                    't.Remark_checkbox',
                ])
                ->join('timetable as t', 'tf.timetable_pk', '=', 't.pk')
                ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')
                ->join('faculty_master as fm', 'tf.faculty_pk', '=', 'fm.pk') // JOIN ON tf.faculty_pk
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
            if (!$request->has('token')) {
                abort(403, 'Missing token');
            }

            // ================= TOKEN AUTH =================
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

            $user = User::where('user_name', trim($username))->firstOrFail();
            Auth::login($user);

            $student_pk = auth()->user()->user_id;

            // ================= PENDING FEEDBACK =================
            $pendingQuery = DB::table('timetable as t')
                ->select([
                    't.pk as timetable_pk',
                    't.subject_topic',
                    't.Ratting_checkbox',
                    't.feedback_checkbox',
                    't.Remark_checkbox',
                    't.faculty_master as faculty_json', // Keep original JSON
                    'f.pk as faculty_pk', // Get individual faculty PK
                    'f.full_name as faculty_name',
                    'c.course_name',
                    'v.venue_name',
                    DB::raw('t.START_DATE as from_date'),
                    't.class_session',
                    DB::raw("
                    STR_TO_DATE(
                        TRIM(SUBSTRING_INDEX(t.class_session, '-', -1)),
                        '%h:%i %p'
                    ) as session_end_time
                "),
                ])
                // ===== FACULTY (JSON + SINGLE VALUE SUPPORT) =====
                ->leftJoin('faculty_master as f', function ($join) {
                    $join->whereRaw("
                    (
                        JSON_VALID(t.faculty_master)
                        AND JSON_CONTAINS(
                            t.faculty_master,
                            JSON_QUOTE(CAST(f.pk AS CHAR))
                        )
                    )
                    OR
                    (
                        NOT JSON_VALID(t.faculty_master)
                        AND CAST(t.faculty_master AS CHAR) = CAST(f.pk AS CHAR)
                    )
                ");
                })
                ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')
                ->leftJoin('venue_master as v', 't.venue_id', '=', 'v.venue_id')
                ->join('student_master_course__map as smcm', function ($join) use ($student_pk) {
                    $join->on('smcm.course_master_pk', '=', 't.course_master_pk')
                        ->where('smcm.student_master_pk', $student_pk)
                        ->where('smcm.active_inactive', 1);
                })
                ->join('course_student_attendance as csa', function ($join) use ($student_pk) {
                    $join->on('csa.timetable_pk', '=', 't.pk')
                        ->where('csa.Student_master_pk', $student_pk)
                        ->where('csa.status', '1');
                })
                ->where('t.feedback_checkbox', 1)
                ->where('t.active_inactive', 1)
                ->whereNotExists(function ($sub) use ($student_pk) {
                    $sub->select(DB::raw(1))
                        ->from('topic_feedback as tf')
                        ->whereColumn('tf.timetable_pk', 't.pk')
                        ->where('tf.student_master_pk', $student_pk)
                        ->where('tf.faculty_pk', DB::raw('f.pk')) // Check for this specific faculty
                        ->where('tf.is_submitted', 1);
                })
                ->whereRaw("
                TIMESTAMP(
                    t.END_DATE,
                    STR_TO_DATE(
                        TRIM(SUBSTRING_INDEX(t.class_session, '-', -1)),
                        '%h:%i %p'
                    )
                ) <= NOW()
            ");

            // ===== OT GROUP FILTER =====
            if (hasRole('Student-OT')) {
                $pendingQuery
                    ->join('course_group_timetable_mapping as cgtm', 'cgtm.timetable_pk', '=', 't.pk')
                    ->join('student_course_group_map as scgm', 'scgm.group_type_master_course_master_map_pk', '=', 'cgtm.group_pk')
                    ->where('scgm.student_master_pk', $student_pk);
            }

            $pendingData = $pendingQuery
                ->orderBy('t.START_DATE', 'asc')
                ->orderBy('session_end_time', 'asc')
                ->get()
                ->unique(function ($item) {
                    // Ensure each faculty-timetable combination is unique
                    return $item->timetable_pk . '_' . $item->faculty_pk;
                })
                ->values();

            // ================= SUBMITTED FEEDBACK =================
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
                    'tf.faculty_pk',
                    't.subject_topic',
                    // Get faculty name from faculty_master using tf.faculty_pk
                    'fm.full_name as faculty_name',
                    'c.course_name',
                    'v.venue_name',
                    DB::raw('t.START_DATE as from_date'),
                    't.class_session',
                    't.Ratting_checkbox',
                    't.Remark_checkbox',
                ])
                ->join('timetable as t', 'tf.timetable_pk', '=', 't.pk')
                ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')
                ->join('faculty_master as fm', 'tf.faculty_pk', '=', 'fm.pk') // JOIN ON tf.faculty_pk
                ->leftJoin('venue_master as v', 't.venue_id', '=', 'v.venue_id')
                ->where('tf.student_master_pk', $student_pk)
                ->where('tf.is_submitted', 1)
                ->orderByDesc('tf.created_date')
                ->get();

            return view(
                'admin.feedback.student_feedback',
                compact('pendingData', 'submittedData')
            );
        } catch (\Throwable $e) {
            logger()->error('Error in studentFacultyFeedback: ' . $e->getMessage());
            return back()->with('error', 'Something went wrong');
        }
    }






    // public function submitFeedback(Request $request)
    // {
    //     $request->validate([
    //         'timetable_pk' => 'required|array|min:1',
    //     ]);

    //     $studentId = auth()->user()->user_id;
    //     $now = now();

    //     // 👇 If individual button clicked
    //     if ($request->has('submit_index')) {
    //         $indexes = [$request->submit_index];
    //     }
    //     // 👇 Bulk submit
    //     else {
    //         $indexes = array_keys($request->timetable_pk);
    //     }

    //     $inserted = 0;

    //     foreach ($indexes as $i) {

    //         $content      = $request->content[$i] ?? null;
    //         $presentation = $request->presentation[$i] ?? null;
    //         $remarks      = $request->remarks[$i] ?? null;

    //         // ⛔ Skip empty rows
    //         if (empty($content) && empty($presentation) && empty($remarks)) {
    //             continue;
    //         }

    //         // ✅ Validate only filled fields
    //         $rules = [];
    //         if ($content) {
    //             $rules["content.$i"] = 'in:1,2,3,4,5';
    //         }
    //         if ($presentation) {
    //             $rules["presentation.$i"] = 'in:1,2,3,4,5';
    //         }
    //         if ($remarks) {
    //             $rules["remarks.$i"] = 'string|max:255';
    //         }

    //         if ($rules) {
    //             $request->validate($rules);
    //         }

    //         DB::table('topic_feedback')->insert([
    //             'timetable_pk'      => $request->timetable_pk[$i],
    //             'student_master_pk' => $studentId,
    //             'topic_name'        => $request->topic_name[$i] ?? '',
    //             'faculty_pk'        => $request->faculty_pk[$i] ?? null,
    //             'content'           => $content,
    //             'presentation'      => $presentation,
    //             'remark'            => $remarks,
    //             'created_date'      => $now,
    //             'modified_date'     => $now,
    //         ]);

    //         $inserted++;
    //     }

    //     if ($inserted === 0) {
    //         return back()->withErrors(['error' => 'Please fill at least one feedback before submitting.']);
    //     }

    //     return back()->with('success', 'Feedback submitted successfully!');
    // }

    public function submitFeedback(Request $request)
    {

        // dd($request->all());
        $request->validate([
            'timetable_pk' => 'required|array|min:1',
        ]);

        $studentId = auth()->user()->user_id;
        $now = now();

        $indexes = $request->has('submit_index')
            ? [$request->submit_index]
            : array_keys($request->timetable_pk);

        $inserted = 0;
        $errors = [];

        foreach ($indexes as $i) {
            // Get the combined timetable_pk_faculty_pk value
            $combinedPk = $request->timetable_pk[$i] ?? null;

            if (!$combinedPk || strpos($combinedPk, '_') === false) {
                $errors[] = "Invalid feedback data at index $i";
                continue;
            }

            // Split the combined PK
            list($timetablePk, $facultyPk) = explode('_', $combinedPk, 2);

            if (!$timetablePk || !$facultyPk) {
                $errors[] = "Missing timetable or faculty information at index $i";
                continue;
            }

            // Check if feedback already exists for this specific combination
            $existingFeedback = DB::table('topic_feedback')
                ->where('timetable_pk', $timetablePk)
                ->where('student_master_pk', $studentId)
                ->where('faculty_pk', $facultyPk)
                ->where('is_submitted', 1)
                ->first();

            if ($existingFeedback) {
                $errors[] = "Feedback already submitted for this faculty";
                continue;
            }

            // Get rating values
            $content = $request->content[$i] ?? null;
            $presentation = $request->presentation[$i] ?? null;
            $remarks = $request->remarks[$i] ?? null;
            $ratingCheckbox = $request->Ratting_checkbox[$i] ?? 0;
            $remarkCheckbox = $request->Remark_checkbox[$i] ?? 0;

            // On a bulk submit, rows the student left untouched are silently
            // skipped — they are not errors, just sessions with no feedback yet.
            $isBulk = !$request->has('submit_index');
            if ($isBulk && !$content && !$presentation && empty(trim((string) $remarks))) {
                continue;
            }

            // Validate that ratings are provided if rating checkbox is enabled
            if ($ratingCheckbox == 1) {
                if (!$content && !$presentation) {
                    $errors[] = "Please provide content or presentation rating";
                    continue;
                }
            }

            // Validate that remarks are provided if remark checkbox is enabled
            // if ($remarkCheckbox == 1 && empty($remarks)) {
            //     $errors[] = "Please provide remarks";
            //     continue;
            // }

            // Calculate overall rating
            $overallRating = null;
            if ($content && $presentation) {
                $overallRating = ($content + $presentation) / 2;
            } elseif ($content) {
                $overallRating = $content;
            } elseif ($presentation) {
                $overallRating = $presentation;
            }

            // Insert the feedback
            DB::table('topic_feedback')->insert([
                'timetable_pk' => $timetablePk,
                'student_master_pk' => $studentId,
                'topic_name' => $request->topic_name[$i] ?? '',
                'faculty_pk' => $facultyPk,
                'content' => $content,
                'presentation' => $presentation,
                'remark' => $remarks,
                'rating' => $overallRating,
                'is_submitted' => 1,
                'created_date' => $now,
                'modified_date' => $now,
            ]);

            $inserted++;
        }

        // Summarise errors: show each distinct reason once with a count, so the
        // flash message stays short instead of repeating the same line per row.
        $errorSummary = '';
        if (!empty($errors)) {
            $errorSummary = collect($errors)
                ->countBy()
                ->map(fn($count, $reason) => $count > 1 ? "$reason ($count)" : $reason)
                ->implode('; ');
        }

        if ($inserted === 0) {
            $errorMessage = $errorSummary !== '' ? $errorSummary : 'Please submit at least one feedback.';
            return back()->withErrors([
                'error' => $errorMessage
            ]);
        }

        // If some succeeded but some failed
        if (!empty($errors) && $inserted > 0) {
            $failedCount = count($errors);
            $successMessage = "Successfully submitted $inserted feedback(s). " .
                "$failedCount item(s) failed: $errorSummary";
            return back()->with('success', $successMessage);
        }

        return back()->with('success', 'Feedback submitted successfully.');
    }
}