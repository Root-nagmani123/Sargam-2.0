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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;




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

        $sessionMastersByShiftTime = ClassSessionMaster::query()
            ->select('pk', 'shift_time', 'start_time', 'end_time')
            ->get()
            ->keyBy(function ($row) {
                return trim((string) $row->shift_time);
            });

        // Assign random color to each event
        $events = $events->map(function ($event) use ($colors, $sessionMastersByShiftTime) {
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

            $timing = $this->resolveFullCalendarEventTiming($event, $sessionMastersByShiftTime);

            return [
                'id' => $event->pk,
                'title' => $event->subject_topic,
                'start' => $timing['start'],
                'end'   => $timing['end'],
                'vanue'   => $event->venue_name,
                'faculty_name'   => $facultyNames,
                'backgroundColor' => $colors[array_rand($colors)],
                'borderColor' => $colors[array_rand($colors)],
                'textColor' => '#111827',
                'allDay' => $timing['allDay'],
                'display' => 'block',
                'class_session_debug' => $event->class_session,
                'session_type' => $event->session_type ?? null,
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

    /* =====================================================================
     |  Event Card — printable / downloadable PDF representation
     |  (additive: does not alter the existing calendar/timetable workflow)
     * ===================================================================== */

    /**
     * Gather the full data set rendered on the Event Card (preview + PDF).
     * Mirrors SingleCalendarDetails() but adds the optional presentation
     * fields and resolves human-readable course/faculty/group names.
     */
    private function buildEventCardData($id)
    {
        $row = DB::table('timetable')
            ->leftJoin('venue_master', 'timetable.venue_id', '=', 'venue_master.venue_id')
            ->where('timetable.pk', $id)
            ->select('timetable.*', 'venue_master.venue_name as venue_name')
            ->first();

        if (!$row) {
            return null;
        }

        // (array) cast keeps optional columns null-safe even before the migration runs.
        $event = (array) $row;

        $groupIds = json_decode($event['group_name'] ?? '', true) ?: [];
        $internalFacultyIds = json_decode($event['internal_faculty'] ?? '', true) ?: [];

        // faculty_master supports both legacy integer and new JSON-array formats
        $facultyIds = json_decode($event['faculty_master'] ?? '', true);
        if (!is_array($facultyIds)) {
            $facultyIds = !empty($event['faculty_master']) ? [$event['faculty_master']] : [];
        }

        $groupNames = DB::table('group_type_master_course_master_map')
            ->whereIn('pk', $groupIds ?: [])->pluck('group_name');
        $internalFacultyNames = DB::table('faculty_master')
            ->whereIn('pk', $internalFacultyIds ?: [])->pluck('full_name');
        $facultyNames = DB::table('faculty_master')
            ->whereIn('pk', $facultyIds ?: [])->pluck('full_name');

        $courseName = null;
        if (!empty($event['course_master_pk'])) {
            $courseName = DB::table('course_master')->where('pk', $event['course_master_pk'])->value('course_name');
        }

        // custom_fields → normalize to an array of {label, value} pairs
        $customFields = [];
        $rawCustom = $event['custom_fields'] ?? null;
        if (!empty($rawCustom)) {
            $decoded = is_array($rawCustom) ? $rawCustom : json_decode($rawCustom, true);
            if (is_array($decoded)) {
                $isList = array_keys($decoded) === range(0, count($decoded) - 1);
                if ($isList) {
                    foreach ($decoded as $rowItem) {
                        if (is_array($rowItem) && (isset($rowItem['label']) || isset($rowItem['value']))) {
                            $customFields[] = [
                                'label' => (string) ($rowItem['label'] ?? ''),
                                'value' => is_array($rowItem['value'] ?? null) ? implode(', ', $rowItem['value']) : (string) ($rowItem['value'] ?? ''),
                            ];
                        }
                    }
                } else {
                    foreach ($decoded as $label => $value) {
                        $customFields[] = [
                            'label' => (string) $label,
                            'value' => is_array($value) ? implode(', ', $value) : (string) $value,
                        ];
                    }
                }
            }
        }

        return [
            'id'                => $event['pk'],
            'topic'             => $event['subject_topic'] ?: ($courseName ?: 'Event'),
            'course_name'       => $courseName,
            'start_date'        => $event['START_DATE'] ?? null,
            'end_date'          => $event['END_DATE'] ?? null,
            'class_session'     => $event['class_session'] ?? '',
            'faculty_name'      => $facultyNames->implode(', '),
            'internal_faculty'  => $internalFacultyNames->implode(', '),
            'group_name'        => $groupNames->implode(', '),
            'venue_name'        => $event['venue_name'] ?? '',
            // optional presentation fields (nullable)
            'event_banner'      => $event['event_banner'] ?? null,
            'event_category'    => $event['event_category'] ?? null,
            'organizer'         => $event['organizer'] ?? null,
            'contact_info'      => $event['contact_info'] ?? null,
            'qr_code_data'      => $event['qr_code_data'] ?? null,
            'event_description' => $event['event_description'] ?? null,
            'custom_fields'     => $customFields,
        ];
    }

    /**
     * Event Card page — preview with View / Download / Print / Share actions.
     */
    public function eventCard($id)
    {
        $event = $this->buildEventCardData($id);
        abort_if(!$event, 404, 'Event not found');

        $pdfUrl = route('calendar.event.card.pdf', ['id' => $id]);

        return view('admin.calendar.event-card-preview', compact('event', 'pdfUrl', 'id'));
    }

    /**
     * Stream / download the Event Card as a print-ready A4 PDF.
     * ?download=1 forces an attachment; otherwise the PDF is streamed inline
     * (used by the preview iframe and the Print action). ?orientation=landscape
     * switches the page orientation.
     */
    public function eventCardPdf($id, Request $request)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(120);

        $event = $this->buildEventCardData($id);
        abort_if(!$event, 404, 'Event not found');

        $orientation = $request->query('orientation') === 'landscape' ? 'landscape' : 'portrait';

        $data = [
            'event'         => $event,
            'bannerSrc'     => $this->cardBannerSrc($event['event_banner']),
            'qrSrc'         => $this->cardQrSrc($event['qr_code_data']),
            'lbsnaaLogoSrc' => $this->cardLbsnaaLogo(),
            'emblemSrc'     => $this->cardIndiaEmblem(),
            'orientation'   => $orientation,
        ];

        $pdf = Pdf::loadView('admin.calendar.pdf.event-card-pdf', $data)
            ->setPaper('a4', $orientation)
            ->setOptions([
                'defaultFont'          => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'dpi'                  => 110,
            ]);

        $slug = Str::slug($event['topic'] ?: 'event-card') ?: 'event-card';
        $fileName = 'event-card-' . $slug . '-' . $id . '.pdf';

        return $request->boolean('download')
            ? $pdf->download($fileName)
            : $pdf->stream($fileName);
    }

    /**
     * Read a local image file into a data URI for Dompdf (avoids broken remote loads).
     */
    private function cardFileToDataUri(?string $path): ?string
    {
        if (!$path || !is_file($path) || !is_readable($path)) {
            return null;
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png'  => 'image/png',
            'webp' => 'image/webp',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            default => 'image/jpeg',
        };

        return 'data:' . $mime . ';base64,' . base64_encode($raw);
    }

    /**
     * Fetch a remote image and return it as a data URI for Dompdf embedding.
     */
    private function cardHttpToDataUri(string $url, string $mime = 'image/png'): ?string
    {
        try {
            $response = Http::timeout(20)->connectTimeout(8)->get($url);
            if ($response->successful()) {
                $body = $response->body();
                if ($body !== '' && strlen($body) > 100) {
                    return 'data:' . $mime . ';base64,' . base64_encode($body);
                }
            }
        } catch (\Throwable $e) {
            // ignore — caller falls back gracefully
        }

        return null;
    }

    /**
     * Resolve the event banner (stored path or URL) to a Dompdf-safe source.
     */
    private function cardBannerSrc(?string $banner): ?string
    {
        if (!$banner) {
            return null;
        }
        if (preg_match('#^https?://#i', $banner)) {
            return $this->cardHttpToDataUri($banner, 'image/jpeg') ?? $banner;
        }
        $rel = ltrim(str_replace('\\', '/', $banner), '/');
        foreach ([
            storage_path('app/public/' . $rel),
            public_path('storage/' . $rel),
            public_path($rel),
        ] as $candidate) {
            $uri = $this->cardFileToDataUri($candidate);
            if ($uri !== null) {
                return $uri;
            }
        }

        return null;
    }

    /**
     * Build a QR code image (data URI) from arbitrary text/URL using a public
     * QR rendering service. Returns null if the value is empty or fetch fails.
     */
    private function cardQrSrc(?string $data): ?string
    {
        $data = trim((string) $data);
        if ($data === '') {
            return null;
        }
        $url = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&margin=0&data=' . urlencode($data);

        return $this->cardHttpToDataUri($url, 'image/png');
    }

    /**
     * LBSNAA header logo for the Event Card PDF (local asset first, then official site).
     */
    private function cardLbsnaaLogo(): string
    {
        foreach ([
            public_path('images/lbsnaa_logo.jpg'),
            public_path('images/lbsnaa_logo.png'),
        ] as $path) {
            $uri = $this->cardFileToDataUri($path);
            if ($uri !== null) {
                return $uri;
            }
        }

        $official = 'https://www.lbsnaa.gov.in/admin_assets/images/logo.png';

        return $this->cardHttpToDataUri($official, 'image/png') ?? $official;
    }

    /**
     * India emblem for the Event Card PDF header.
     */
    private function cardIndiaEmblem(): string
    {
        $url = 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png';

        return $this->cardHttpToDataUri($url, 'image/png') ?? $url;
    }

    /**
     * Whole-week timetable as a print-ready A4 landscape PDF (Time × Mon–Fri grid).
     * Mirrors the on-screen "Weekly Timetable" list view. ?week_start=YYYY-MM-DD
     * selects the week, ?course_id filters (same as the calendar filter),
     * ?download=1 forces an attachment.
     */
    public function weeklyTimetablePdf(Request $request)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(120);

        $weekStart = $request->filled('week_start')
            ? Carbon::parse($request->week_start)->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->addDays(6);

        $courseId = $request->query('course_id') ?: null;

        $rows = DB::table('timetable')
            ->leftJoin('venue_master', 'timetable.venue_id', '=', 'venue_master.venue_id')
            ->whereBetween('timetable.START_DATE', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->when($courseId, function ($q) use ($courseId) {
                $q->where('timetable.course_master_pk', $courseId);
            })
            ->select(
                'timetable.pk',
                'timetable.subject_topic',
                'timetable.class_session',
                'timetable.START_DATE',
                'timetable.faculty_master',
                'venue_master.venue_name as venue_name'
            )
            ->orderBy('timetable.START_DATE')
            ->get();

        // Build grid[timeSlot][isoWeekday 1..5] = list of session cells, ordered by start time.
        $grid = [];
        $slotOrder = [];
        foreach ($rows as $r) {
            $dow = (int) Carbon::parse($r->START_DATE)->dayOfWeekIso; // 1=Mon .. 7=Sun
            if ($dow < 1 || $dow > 5) {
                continue; // Mon–Fri grid (matches the on-screen weekly view)
            }

            $slot = trim((string) $r->class_session);
            if ($slot === '') {
                $slot = 'Unscheduled';
            }
            if (!isset($grid[$slot])) {
                $grid[$slot] = [];
                $slotOrder[$slot] = $this->timetableSlotSortKey($slot);
            }

            $facultyIds = json_decode($r->faculty_master, true);
            if (!is_array($facultyIds)) {
                $facultyIds = !empty($r->faculty_master) ? [$r->faculty_master] : [];
            }
            $faculty = $facultyIds
                ? DB::table('faculty_master')->whereIn('pk', $facultyIds)->pluck('full_name')->implode(', ')
                : '';

            $grid[$slot][$dow][] = [
                'topic'   => trim((string) $r->subject_topic) ?: 'Session',
                'faculty' => $faculty,
                'venue'   => trim((string) ($r->venue_name ?? '')),
            ];
        }

        uksort($grid, function ($a, $b) use ($slotOrder) {
            return ($slotOrder[$a] ?? PHP_INT_MAX) <=> ($slotOrder[$b] ?? PHP_INT_MAX);
        });

        // Build ordered row descriptors: stacked time + full-width break/lunch detection.
        $rows = [];
        $venueCounts = [];
        foreach ($grid as $slot => $byDay) {
            $allCells = [];
            foreach ($byDay as $cells) {
                foreach ($cells as $c) {
                    $allCells[] = $c;
                    if ($c['venue'] !== '') {
                        $venueCounts[$c['venue']] = ($venueCounts[$c['venue']] ?? 0) + 1;
                    }
                }
            }

            $isBreak = !empty($allCells);
            foreach ($allCells as $c) {
                if (!preg_match('/\b(tea\s*break|lunch|break|recess|hi[\s-]?tea)\b/i', $c['topic'])) {
                    $isBreak = false;
                    break;
                }
            }

            [$startLbl, $endLbl] = $this->splitSessionTime($slot);

            $rows[] = [
                'slot'       => $slot,
                'startLbl'   => $startLbl,
                'endLbl'     => $endLbl,
                'byDay'      => $byDay,
                'isBreak'    => $isBreak,
                'breakLabel' => $isBreak ? $allCells[0]['topic'] : null,
            ];
        }

        arsort($venueCounts);
        $topVenue = !empty($venueCounts) ? array_key_first($venueCounts) : null;

        // Programme details (subtitle, period, programme-relative week) from the course.
        $programmeName = null;
        $period = null;
        $programmeWeek = $weekStart->isoWeek;
        if ($courseId) {
            $course = DB::table('course_master')->where('pk', $courseId)->first();
            if ($course) {
                $programmeName = $course->course_name ?? null;
                $start = !empty($course->start_year) ? Carbon::parse($course->start_year) : null;
                $end   = !empty($course->end_date) ? Carbon::parse($course->end_date) : null;
                if ($start) {
                    $courseMonday = $start->copy()->startOfWeek(Carbon::MONDAY);
                    $relWeek = intdiv((int) $courseMonday->diffInDays($weekStart, false), 7) + 1;
                    if ($relWeek >= 1) {
                        $programmeWeek = $relWeek;
                    }
                }
                if ($start && $end) {
                    $period = $start->format('jS M Y') . ' to ' . $end->format('jS M Y');
                } elseif ($start) {
                    $period = 'Commencing ' . $start->format('jS M Y');
                }
            }
        }

        $data = [
            'rows'          => $rows,
            'weekStart'     => $weekStart,
            'weekEnd'       => $weekEnd,
            'weekNumber'    => $programmeWeek,
            'days'          => [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday'],
            'programmeName' => $programmeName,
            'period'        => $period,
            'topVenue'      => $topVenue,
            'lbsnaaLogoSrc' => $this->cardLbsnaaLogo(),
            'emblemSrc'     => $this->cardIndiaEmblem(),
        ];

        $pdf = Pdf::loadView('admin.calendar.pdf.weekly-timetable-pdf', $data)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont'          => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'dpi'                  => 110,
            ]);

        $fileName = 'weekly-timetable-' . $weekStart->format('Y-m-d') . '.pdf';

        return $request->boolean('download')
            ? $pdf->download($fileName)
            : $pdf->stream($fileName);
    }

    /**
     * Derive a chronological sort key (minutes from midnight) from a class_session
     * label such as "09:30 AM - 10:30 AM" or "0930 - 1030". Unparseable slots sort last.
     */
    private function timetableSlotSortKey(string $slot): int
    {
        if (preg_match('/(\d{1,2}):(\d{2})\s*([AaPp][Mm])?/', $slot, $m)) {
            $h = (int) $m[1];
            $min = (int) $m[2];
            $mer = strtolower($m[3] ?? '');
            if ($mer === 'pm' && $h < 12) {
                $h += 12;
            }
            if ($mer === 'am' && $h === 12) {
                $h = 0;
            }
            return $h * 60 + $min;
        }
        if (preg_match('/\b(\d{2})(\d{2})\b/', $slot, $m)) {
            return ((int) $m[1]) * 60 + (int) $m[2];
        }

        return PHP_INT_MAX;
    }

    /**
     * Split a class_session label such as "09:45 AM - 10:45 AM" or "0930 to 1030"
     * into [start, end] for the stacked Time column. Returns [label, null] if not a range.
     */
    private function splitSessionTime(string $slot): array
    {
        $parts = preg_split('/\s*(?:to|\-|–|—)\s*/i', $slot, 2);
        if (is_array($parts) && count($parts) === 2) {
            return [trim($parts[0]), trim($parts[1])];
        }

        return [trim($slot), null];
    }

    /**
     * Course Information + "Resource Persons / Faculty for the Week" sheet as a
     * print-ready A4 portrait PDF. Resource persons are derived from the week's
     * sessions. ?week_start, ?course_id, ?download=1 behave as for the timetable PDF.
     */
    public function weeklyInfoPdf(Request $request)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(120);

        $weekStart = $request->filled('week_start')
            ? Carbon::parse($request->week_start)->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->addDays(6);

        $courseId = $request->query('course_id') ?: null;

        $sessions = DB::table('timetable')
            ->leftJoin('venue_master', 'timetable.venue_id', '=', 'venue_master.venue_id')
            ->whereBetween('timetable.START_DATE', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->when($courseId, function ($q) use ($courseId) {
                $q->where('timetable.course_master_pk', $courseId);
            })
            ->select(
                'timetable.pk',
                'timetable.subject_topic',
                'timetable.START_DATE',
                'timetable.faculty_master',
                'venue_master.venue_name as venue_name'
            )
            ->orderBy('timetable.START_DATE')
            ->get();

        // Bulk-load faculty referenced this week.
        $allFacultyIds = [];
        foreach ($sessions as $s) {
            $ids = json_decode($s->faculty_master, true);
            if (!is_array($ids)) {
                $ids = !empty($s->faculty_master) ? [$s->faculty_master] : [];
            }
            foreach ($ids as $id) {
                $allFacultyIds[$id] = true;
            }
        }
        $facultyMap = DB::table('faculty_master')
            ->whereIn('pk', array_keys($allFacultyIds) ?: [0])
            ->get()
            ->keyBy('pk');

        // One resource-person row per (faculty, session).
        $facultyRows = [];
        $venueCounts = [];
        foreach ($sessions as $s) {
            if (!empty($s->venue_name)) {
                $venueCounts[$s->venue_name] = ($venueCounts[$s->venue_name] ?? 0) + 1;
            }
            $ids = json_decode($s->faculty_master, true);
            if (!is_array($ids)) {
                $ids = !empty($s->faculty_master) ? [$s->faculty_master] : [];
            }
            foreach ($ids as $id) {
                $f = $facultyMap->get($id);
                if (!$f) {
                    continue;
                }
                $desig = trim((string) ($f->current_designation ?? ''));
                $org   = trim((string) ($f->current_department ?? ''));
                $designation = trim($desig . ($desig !== '' && $org !== '' ? ', ' : '') . $org);
                $facultyRows[] = [
                    'name'        => trim((string) ($f->full_name ?? '')) ?: trim(($f->first_name ?? '') . ' ' . ($f->last_name ?? '')),
                    'designation' => $designation,
                    'topic'       => trim((string) $s->subject_topic) ?: 'Session',
                    'date'        => Carbon::parse($s->START_DATE),
                ];
            }
        }
        usort($facultyRows, function ($a, $b) {
            return [$a['date']->timestamp, $a['name']] <=> [$b['date']->timestamp, $b['name']];
        });

        arsort($venueCounts);
        $topVenue = !empty($venueCounts) ? array_key_first($venueCounts) : null;

        // Course Information.
        $programmeName = null;
        $shortName = null;
        $period = null;
        $programmeWeek = $weekStart->isoWeek;
        $participants = null;
        $coordinator = null;
        $assistantCoordinator = null;
        $director = null;
        $jointDirector = null;
        $participantsProfile = null;
        $mentionOfWeek = null;

        if ($courseId) {
            $course = DB::table('course_master')->where('pk', $courseId)->first();
            if ($course) {
                $programmeName = $course->course_name ?? null;
                $shortName = $course->couse_short_name ?? null;
                $participantsProfile = $course->participants_profile ?? null;
                $start = !empty($course->start_year) ? Carbon::parse($course->start_year) : null;
                $end   = !empty($course->end_date) ? Carbon::parse($course->end_date) : null;
                if ($start) {
                    $courseMonday = $start->copy()->startOfWeek(Carbon::MONDAY);
                    $relWeek = intdiv((int) $courseMonday->diffInDays($weekStart, false), 7) + 1;
                    if ($relWeek >= 1) {
                        $programmeWeek = $relWeek;
                    }
                }
                if ($start && $end) {
                    $period = $start->format('jS M Y') . ' to ' . $end->format('jS M Y');
                } elseif ($start) {
                    $period = 'Commencing ' . $start->format('jS M Y');
                }
            }
            $participants = DB::table('student_master_course__map')
                ->where('course_master_pk', $courseId)
                ->where('active_inactive', 1)
                ->count();
            $cc = DB::table('course_coordinator_master')->where('courses_master_pk', $courseId)->first();
            if ($cc) {
                $coordinator = $cc->Coordinator_name ?? null;
                $assistantCoordinator = $cc->Assistant_Coordinator_name ?? null;
                $director = $cc->director_name ?? null;
                $jointDirector = $cc->joint_director_name ?? null;
            }
            $mentionOfWeek = DB::table('course_week_notes')
                ->where('course_master_pk', $courseId)
                ->where('week_start', $weekStart->toDateString())
                ->value('mention_of_week');
        }

        $data = [
            'facultyRows'          => $facultyRows,
            'weekStart'            => $weekStart,
            'weekEnd'              => $weekEnd,
            'weekNumber'           => $programmeWeek,
            'programmeName'        => $programmeName,
            'shortName'            => $shortName,
            'period'               => $period,
            'topVenue'             => $topVenue,
            'participants'         => $participants,
            'coordinator'          => $coordinator,
            'assistantCoordinator' => $assistantCoordinator,
            'director'             => $director,
            'jointDirector'        => $jointDirector,
            'participantsProfile'  => $participantsProfile,
            'mentionOfWeek'        => $mentionOfWeek,
            'lbsnaaLogoSrc'        => $this->cardLbsnaaLogo(),
            'emblemSrc'            => $this->cardIndiaEmblem(),
        ];

        $pdf = Pdf::loadView('admin.calendar.pdf.weekly-info-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'          => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'dpi'                  => 110,
            ]);

        $fileName = 'faculty-for-the-week-' . $weekStart->format('Y-m-d') . '.pdf';

        return $request->boolean('download')
            ? $pdf->download($fileName)
            : $pdf->stream($fileName);
    }

    /**
     * Roles allowed to edit the weekly info-sheet details (same as event authoring).
     */
    private function canEditWeeklyInfo(): bool
    {
        return hasRole('Training') || hasRole('Admin') || hasRole('Training-MCTP') || hasRole('IST');
    }

    /**
     * Current editable values for the info sheet (course-level + the given week).
     */
    public function weeklyInfoMeta(Request $request)
    {
        $courseId = $request->query('course_id') ?: null;
        if (!$courseId) {
            return response()->json(['error' => 'Select a course first.'], 422);
        }

        $weekStart = ($request->filled('week_start')
            ? Carbon::parse($request->week_start)
            : Carbon::now())->startOfWeek(Carbon::MONDAY)->toDateString();

        $course = DB::table('course_master')->where('pk', $courseId)->first();
        $cc = DB::table('course_coordinator_master')->where('courses_master_pk', $courseId)->first();
        $mention = DB::table('course_week_notes')
            ->where('course_master_pk', $courseId)
            ->where('week_start', $weekStart)
            ->value('mention_of_week');

        return response()->json([
            'course_id'            => (int) $courseId,
            'week_start'           => $weekStart,
            'director_name'        => $cc->director_name ?? '',
            'joint_director_name'  => $cc->joint_director_name ?? '',
            'participants_profile' => $course->participants_profile ?? '',
            'mention_of_week'      => $mention ?? '',
            'can_edit'             => $this->canEditWeeklyInfo(),
        ]);
    }

    /**
     * Persist the info-sheet details: Director / Joint Director / Participants Profile
     * (course-level) and Mention of the Week (per course, per week).
     */
    public function saveWeeklyInfo(Request $request)
    {
        abort_unless($this->canEditWeeklyInfo(), 403, 'You do not have permission to edit info-sheet details.');

        $validated = $request->validate([
            'course_id'            => 'required|integer',
            'week_start'           => 'required|date',
            'director_name'        => 'nullable|string|max:255',
            'joint_director_name'  => 'nullable|string|max:255',
            'participants_profile' => 'nullable|string',
            'mention_of_week'      => 'nullable|string',
        ]);

        $courseId = (int) $validated['course_id'];
        $weekStart = Carbon::parse($validated['week_start'])->startOfWeek(Carbon::MONDAY)->toDateString();

        // Course-level personnel — update existing coordinator row or create one.
        DB::table('course_coordinator_master')->updateOrInsert(
            ['courses_master_pk' => $courseId],
            [
                'director_name'       => $validated['director_name'] ?? null,
                'joint_director_name' => $validated['joint_director_name'] ?? null,
            ]
        );

        // Participants profile lives on the course.
        DB::table('course_master')->where('pk', $courseId)->update([
            'participants_profile' => $validated['participants_profile'] ?? null,
        ]);

        // Mention of the week — per course, per week.
        $existingNote = DB::table('course_week_notes')
            ->where('course_master_pk', $courseId)
            ->where('week_start', $weekStart)
            ->first();
        if ($existingNote) {
            DB::table('course_week_notes')->where('id', $existingNote->id)->update([
                'mention_of_week' => $validated['mention_of_week'] ?? null,
                'updated_at'      => now(),
            ]);
        } else {
            DB::table('course_week_notes')->insert([
                'course_master_pk' => $courseId,
                'week_start'       => $weekStart,
                'mention_of_week'  => $validated['mention_of_week'] ?? null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        return response()->json(['status' => 'success', 'message' => 'Info-sheet details saved.']);
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

    /**
     * Build ISO start/end for FullCalendar (timed slots in week view).
     */
    private function resolveFullCalendarEventTiming($event, $sessionMastersByShiftTime): array
    {
        $dateStart = Carbon::parse($event->START_DATE)->toDateString();
        $dateEnd = Carbon::parse($event->END_DATE ?: $event->START_DATE)->toDateString();

        if (!empty($event->full_day) && (int) $event->full_day === 1) {
            return $this->allDayFullCalendarRange($dateStart, $dateEnd);
        }

        $timeRangeStr = trim((string) ($event->class_session ?? ''));

        if ((int) ($event->session_type ?? 0) === 1 && $timeRangeStr !== '') {
            $master = $sessionMastersByShiftTime->get($timeRangeStr)
                ?? $sessionMastersByShiftTime->firstWhere('pk', $timeRangeStr);
            if ($master) {
                if (!empty($master->start_time) && !empty($master->end_time)) {
                    $timeRangeStr = trim($master->start_time) . ' - ' . trim($master->end_time);
                } elseif (!empty($master->shift_time)) {
                    $timeRangeStr = trim($master->shift_time);
                }
            }
        }

        $times = $this->parseClassSessionTimeRange($timeRangeStr);
        if ($times) {
            $startDateTime = $dateStart . 'T' . $times['start'];
            $endDateTime = $dateEnd . 'T' . $times['end'];

            if ($times['end'] <= $times['start'] && $dateStart === $dateEnd) {
                $endDateTime = Carbon::parse($dateEnd)->addDay()->format('Y-m-d') . 'T' . $times['end'];
            }

            return [
                'start' => $startDateTime,
                'end' => $endDateTime,
                'allDay' => false,
            ];
        }

        return $this->allDayFullCalendarRange($dateStart, $dateEnd);
    }

    private function allDayFullCalendarRange(string $dateStart, string $dateEnd): array
    {
        $end = Carbon::parse($dateEnd)->addDay()->format('Y-m-d');

        return [
            'start' => $dateStart,
            'end' => $end,
            'allDay' => true,
        ];
    }

    /**
     * @return array{start: string, end: string}|null 24h times as H:i:s
     */
    private function parseClassSessionTimeRange(string $timeRangeStr): ?array
    {
        if ($timeRangeStr === '' || !preg_match('/[-–—]/u', $timeRangeStr)) {
            return null;
        }

        $parts = preg_split('/\s*[-–—]\s*/u', $timeRangeStr, 2);
        if (count($parts) !== 2) {
            return null;
        }

        $start = $this->parseTimeTo24Hour(trim($parts[0]));
        $end = $this->parseTimeTo24Hour(trim($parts[1]));

        if (!$start || !$end) {
            return null;
        }

        return ['start' => $start, 'end' => $end];
    }

    private function parseTimeTo24Hour(string $timeStr): ?string
    {
        try {
            return Carbon::parse($timeStr)->format('H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
}