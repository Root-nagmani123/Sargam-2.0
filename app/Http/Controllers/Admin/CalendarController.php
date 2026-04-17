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
use App\Exports\WeekTimetableWorkbookExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;




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
            ->whereDate('end_date', '>=', today());

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

        $courseMaster = $courseMaster->select(
            'course_master.pk',
            'course_name',
            'couse_short_name',
            'course_year',
            'course_master.start_year',
            'course_master.end_date'
        )->get();

        $calendarCourseMeta = $courseMaster->mapWithKeys(static function ($c) {
            return [(string) $c->pk => [
                'start_year' => $c->start_year,
                'end_date' => $c->end_date,
            ]];
        });

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
            'calendarCourseMeta',
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
        $rangeStart = $request->input('start');
        $rangeEnd = $request->input('end');
        if (!$rangeStart || !$rangeEnd) {
            $rangeStart = Carbon::now()->startOfMonth()->toDateString();
            $rangeEnd = Carbon::now()->endOfMonth()->toDateString();
        }
        $coursePk = $request->filled('course_id') ? (int) $request->course_id : null;

        return response()->json($this->collectCalendarEventsForRange($request, $rangeStart, $rangeEnd, $coursePk));
    }

    /**
     * Timetable events + holidays for a date span (same payload as full-calendar-details JSON).
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function collectCalendarEventsForRange(Request $request, string $rangeStart, string $rangeEnd, ?int $courseMasterPk): Collection
    {
        $events = DB::table('timetable')
            ->join('venue_master', 'timetable.venue_id', '=', 'venue_master.venue_id');

        if (hasRole('Student-OT')) {
            $student_pk = auth()->user()->user_id;

            $events = $events
                ->join('course_group_timetable_mapping', 'course_group_timetable_mapping.timetable_pk', '=', 'timetable.pk')
                ->join('student_course_group_map', 'student_course_group_map.group_type_master_course_master_map_pk', '=', 'course_group_timetable_mapping.group_pk')
                ->where('student_course_group_map.student_master_pk', $student_pk);
        }

        if ($courseMasterPk !== null) {
            $events = $events->where('timetable.course_master_pk', $courseMasterPk);
        }

        $events = $events
            ->whereDate('START_DATE', '>=', $rangeStart)
            ->whereDate('END_DATE', '<=', $rangeEnd)
            ->select(
                'timetable.*',
                'venue_master.venue_name as venue_name'
            )
            ->get();

        if (hasRole('Internal Faculty') || hasRole('Guest Faculty')) {
            $faculty_pk = auth()->user()->user_id;
            $faculty_master_pk = DB::table('faculty_master')
                ->where('employee_master_pk', $faculty_pk)
                ->value('pk');

            if ($faculty_master_pk) {
                $events = $events->filter(function ($event) use ($faculty_master_pk) {
                    $facultyIds = json_decode($event->faculty_master, true);
                    if (is_array($facultyIds)) {
                        return in_array($faculty_master_pk, $facultyIds);
                    }

                    return $event->faculty_master == $faculty_master_pk;
                });
            } else {
                $events = collect([]);
            }
        }

        $colors = ['#ffffff'];

        $events = $events->map(function ($event) use ($colors) {
            $startDateTime = $event->START_DATE;
            $endDateTime = $event->END_DATE;
            $allDay = false;

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

            $sessionPair = !empty($event->class_session)
                ? $this->splitClassSessionTimeRange((string) $event->class_session)
                : null;

            if ($sessionPair !== null) {
                [$startTime, $endTime] = $sessionPair;
                $startTimestamp = strtotime($startTime);
                $endTimestamp = strtotime($endTime);

                if ($startTimestamp !== false && $endTimestamp !== false) {
                    $startTime24 = date('H:i', $startTimestamp);
                    $endTime24 = date('H:i', $endTimestamp);

                    $startDateTime = $event->START_DATE . 'T' . $startTime24 . ':00';
                    $endDay = $event->END_DATE ?: $event->START_DATE;
                    $endDateTime = $endDay . 'T' . $endTime24 . ':00';
                    $allDay = false;
                } else {
                    $startTime24 = $this->parseClockChunkTo24h($startTime);
                    $endTime24 = $this->parseClockChunkTo24h($endTime);
                    if ($startTime24 !== null && $endTime24 !== null) {
                        $startDateTime = $event->START_DATE . 'T' . $startTime24 . ':00';
                        $endDay = $event->END_DATE ?: $event->START_DATE;
                        $endDateTime = $endDay . 'T' . $endTime24 . ':00';
                        $allDay = false;
                    } else {
                        $allDay = true;
                    }
                }
            } else {
                $sessNorm = $this->normalizeTimetableSessionString((string) ($event->class_session ?? ''));
                if ($sessNorm !== '') {
                    $single = $this->parseClockChunkTo24h($sessNorm);
                    if ($single !== null) {
                        $startDateTime = $event->START_DATE . 'T' . $single . ':00';
                        $endDay = $event->END_DATE ?: $event->START_DATE;
                        $endDateTime = $endDay . 'T' . $single . ':00';
                        $allDay = false;
                    } else {
                        $allDay = true;
                    }
                } else {
                    $allDay = true;
                }
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

            $decodedGroups = json_decode($event->group_name, true);
            $groupIds = [];
            if (is_array($decodedGroups)) {
                $groupIds = $decodedGroups;
            } elseif (is_numeric($decodedGroups)) {
                $groupIds = [(int) $decodedGroups];
            }
            $groupNamesList = [];
            if ($groupIds !== []) {
                $groupNamesList = DB::table('group_type_master_course_master_map')
                    ->whereIn('pk', $groupIds)
                    ->pluck('group_name')
                    ->values()
                    ->all();
            }
            $groupNameStr = $groupNamesList !== [] ? implode(', ', $groupNamesList) : '';

            return [
                'id' => $event->pk,
                'title' => $event->subject_topic,
                'start' => $startDateTime,
                'end'   => $endDateTime,
                'vanue'   => $event->venue_name,
                'faculty_name'   => $facultyNames,
                'class_session' => $event->class_session ?? '',
                'group_name' => $groupNameStr,
                'group_names' => $groupNamesList,
                'backgroundColor' => $colors[array_rand($colors)],
                'borderColor' => $colors[array_rand($colors)],
                'textColor' => '#111827',
                'allDay' => $allDay,
                'display' => 'block',
                'class_session_debug' => $event->class_session,
            ];
        });

        $holidays = Holiday::active()
            ->whereBetween('holiday_date', [$rangeStart, $rangeEnd])
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
                    'allDay' => true,
                ];
            });

        return $events->merge($holidays);
    }

    /**
     * @param  array<string, mixed>  $ev
     */
    protected function isWeekTimetableHoliday(array $ev): bool
    {
        return ($ev['type'] ?? '') === 'holiday' || str_starts_with((string) ($ev['id'] ?? ''), 'holiday_');
    }

    /**
     * Shared data for week timetable PDF / print / Excel.
     *
     * @return array{weekMonday: \Carbon\Carbon, weekNum: int, weekYear: int, courseTitle: string, headerDates: list<array{weekday: string, dmy: string}>, weekRangeLabel: string, gridRows: list<array<string, string>>, allEvents: Collection<int, array<string, mixed>>, coursePk: ?int, pdfFileBase: string, venueSummaryLine: ?string, footnotes: list<string>, pdfBodyFont: string}
     */
    protected function prepareWeekTimetableExport(Request $request): array
    {
        $weekOffset = (int) $request->input('week_offset', 0);
        $weekMonday = Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeeks($weekOffset);
        $weekSunday = $weekMonday->copy()->endOfWeek(Carbon::SUNDAY);

        $rangeStart = $weekMonday->toDateString();
        $rangeEnd = $weekSunday->toDateString();

        $coursePk = $request->filled('course_id') ? (int) $request->course_id : null;

        $allEvents = $this->collectCalendarEventsForRange($request, $rangeStart, $rangeEnd, $coursePk);

        $weekYear = (int) $weekMonday->isoWeekYear();
        $isoWeek = (int) $weekMonday->isoWeek();

        $courseRow = null;
        if ($coursePk) {
            $courseRow = CourseMaster::where('pk', $coursePk)->first([
                'pk', 'course_name', 'course_year', 'start_year', 'end_date',
            ]);
        }

        $courseProgrammeTitle = $courseRow?->course_name ?? 'Academic timetable';
        $coursePeriodParen = $this->formatCoursePeriodLine($courseRow);
        $sheetWeekNumber = $this->resolveSheetWeekNumber($weekMonday, $courseRow, $isoWeek);

        $headerDates = [];
        for ($i = 0; $i < 5; $i++) {
            $d = $weekMonday->copy()->addDays($i);
            $headerDates[] = [
                'weekday' => $d->englishDayOfWeek,
                'dmy' => $d->format('d.m.Y'),
            ];
        }

        $weekRangeLabel = $weekMonday->format('d.m.Y') . ' to ' . $weekMonday->copy()->addDays(6)->format('d.m.Y');

        $gridRows = $this->buildWeekTimetablePdfGridRows($allEvents, $weekMonday);
        $breakNotices = $this->buildWeekTimetableBreakNoticeCells($allEvents, $weekMonday);
        $venueSummaryLine = $this->buildWeekTimetableVenueSummaryLine($allEvents, $weekMonday);
        $footnotes = array_values(array_unique(array_filter(array_map('trim', config('week_timetable.footnotes', [])))));

        return [
            'weekMonday' => $weekMonday,
            'weekNum' => $isoWeek,
            'weekYear' => $weekYear,
            'sheetWeekNumber' => $sheetWeekNumber,
            'courseTitle' => $courseProgrammeTitle,
            'courseProgrammeTitle' => $courseProgrammeTitle,
            'coursePeriodParen' => $coursePeriodParen,
            'headerDates' => $headerDates,
            'weekRangeLabel' => $weekRangeLabel,
            'gridRows' => $gridRows,
            'breakNotices' => $breakNotices,
            'venueSummaryLine' => $venueSummaryLine,
            'footnotes' => $footnotes,
            'pdfBodyFont' => (string) config('week_timetable.pdf_body_font', 'DejaVu Sans, Arial, Helvetica, sans-serif'),
            'allEvents' => $allEvents,
            'coursePk' => $coursePk,
            'pdfFileBase' => 'week-timetable-' . $weekMonday->format('Y-m-d'),
        ];
    }

    /**
     * Programme dates line like official PDF: "(8 December 2025 to 17 April, 2026)".
     */
    protected function formatCoursePeriodLine(?CourseMaster $course): ?string
    {
        if (!$course || empty($course->start_year) || empty($course->end_date)) {
            return null;
        }
        try {
            $s = Carbon::parse($course->start_year);
            $e = Carbon::parse($course->end_date);

            return '(' . $s->format('j F Y') . ' to ' . $e->format('j F, Y') . ')';
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Official "Week-19" style number: weeks since programme start (Monday-aligned), else ISO week.
     */
    protected function resolveSheetWeekNumber(Carbon $weekMonday, ?CourseMaster $course, int $isoWeekFallback): int
    {
        if ($course && !empty($course->start_year)) {
            try {
                $progStart = Carbon::parse($course->start_year)->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
                $wm = $weekMonday->copy()->startOfDay();
                $days = (int) $progStart->diffInDays($wm, false);
                if ($days >= 0) {
                    return max(1, (int) floor($days / 7) + 1);
                }
            } catch (\Throwable $e) {
            }
        }

        return $isoWeekFallback;
    }

    /**
     * Per-column tea/lunch break notes (matches official sheet pattern when titles contain those words).
     *
     * @return array{Mon: string, Tue: string, Wed: string, Thu: string, Fri: string}
     */
    protected function buildWeekTimetableBreakNoticeCells(Collection $allEvents, Carbon $weekMonday): array
    {
        $byDay = ['Mon' => '', 'Tue' => '', 'Wed' => '', 'Thu' => '', 'Fri' => ''];
        $weekFriday = $weekMonday->copy()->addDays(4)->endOfDay();
        $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        foreach ($allEvents as $ev) {
            if ($this->isWeekTimetableHoliday($ev)) {
                continue;
            }
            $title = (string) ($ev['title'] ?? '');
            if ($title === '' || !preg_match('/\b(tea\s*break|lunch\s*break)\b/i', $title)) {
                continue;
            }
            try {
                $start = Carbon::parse($ev['start']);
            } catch (\Throwable $e) {
                continue;
            }
            if ($start->lt($weekMonday->copy()->startOfDay()) || $start->gt($weekFriday)) {
                continue;
            }
            $d = $dayNames[$start->dayOfWeek];
            if (!isset($byDay[$d])) {
                continue;
            }
            $sess = trim((string) ($ev['class_session'] ?? ''));
            $line = $title . ($sess !== '' ? ': ' . $sess : '');
            $byDay[$d] = $byDay[$d] === '' ? $line : ($byDay[$d] . ' ' . $line);
        }

        return $byDay;
    }

    /**
     * One-line venue summary like official sheet: "VENUES: Group A: TH, Group B: AH".
     */
    protected function buildWeekTimetableVenueSummaryLine(Collection $allEvents, Carbon $weekMonday): ?string
    {
        $weekFriday = $weekMonday->copy()->addDays(4)->endOfDay();
        $pairs = [];

        foreach ($allEvents as $ev) {
            if ($this->isWeekTimetableHoliday($ev)) {
                continue;
            }
            $title = (string) ($ev['title'] ?? '');
            if ($title !== '' && preg_match('/\b(tea\s*break|lunch\s*break)\b/i', $title)) {
                continue;
            }
            try {
                $start = Carbon::parse($ev['start']);
            } catch (\Throwable $e) {
                continue;
            }
            if ($start->lt($weekMonday->copy()->startOfDay()) || $start->gt($weekFriday)) {
                continue;
            }
            $g = trim((string) ($ev['group_name'] ?? ''));
            $v = trim((string) ($ev['vanue'] ?? $ev['venue_name'] ?? ''));
            if ($g === '' && $v === '') {
                continue;
            }
            $label = $g !== '' ? ($g . ': ' . $v) : $v;
            $pairs[strtolower($label)] = $label;
        }

        if ($pairs === []) {
            return null;
        }

        return 'VENUES: ' . implode(', ', array_values($pairs));
    }

    /**
     * Strip HTML from PDF grid cells for Excel export.
     */
    protected function weekTimetableGridCellToPlain(string $html): string
    {
        if ($html === '') {
            return '';
        }
        $s = preg_replace('#<br\s*/?>#i', "\n", $html);
        $s = strip_tags($s);
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace("/\n{3,}/u", "\n\n", $s));
    }

    /**
     * Excel rows mirroring the official revised sheet (letterhead + grid).
     *
     * @param  array<string, mixed>  $data  Output of {@see prepareWeekTimetableExport()}
     * @return list<list<string>>
     */
    protected function buildWeekTimetableExcelSheetArray(array $data): array
    {
        $lines = [];
        $blank = ['', '', '', '', '', ''];

        $lines[] = ['लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी, मसूरी', '', '', '', '', ''];
        $lines[] = ['Lal Bahadur Shastri National Academy of Administration, Mussoorie', '', '', '', '', ''];
        $lines[] = [(string) ($data['courseProgrammeTitle'] ?? $data['courseTitle'] ?? 'Academic timetable'), '', '', '', '', ''];
        $period = trim((string) ($data['coursePeriodParen'] ?? ''));
        if ($period !== '') {
            $lines[] = [$period, '', '', '', '', ''];
        }
        $wn = (int) ($data['sheetWeekNumber'] ?? $data['weekNum'] ?? 1);
        $lines[] = ['Time Table : Week-' . $wn . '     Revised', '', '', '', '', ''];
        $lines[] = $blank;

        $headerDates = $data['headerDates'] ?? [];
        $rowWeekdays = ['TIME'];
        foreach ($headerDates as $h) {
            $rowWeekdays[] = (string) ($h['weekday'] ?? '');
        }
        while (count($rowWeekdays) < 6) {
            $rowWeekdays[] = '';
        }
        $lines[] = $rowWeekdays;

        $rowDmY = [''];
        foreach ($headerDates as $h) {
            $rowDmY[] = (string) ($h['dmy'] ?? '');
        }
        while (count($rowDmY) < 6) {
            $rowDmY[] = '';
        }
        $lines[] = $rowDmY;

        $bn = $data['breakNotices'] ?? ['Mon' => '', 'Tue' => '', 'Wed' => '', 'Thu' => '', 'Fri' => ''];
        $showBreak = collect($bn)->contains(static fn ($v) => trim((string) $v) !== '');
        if ($showBreak) {
            $normBreak = static fn ($v): string => trim(str_replace(["\r\n", "\r"], "\n", (string) $v));
            $lines[] = [
                '',
                $normBreak($bn['Mon'] ?? ''),
                $normBreak($bn['Tue'] ?? ''),
                $normBreak($bn['Wed'] ?? ''),
                $normBreak($bn['Thu'] ?? ''),
                $normBreak($bn['Fri'] ?? ''),
            ];
        }

        $venue = trim((string) ($data['venueSummaryLine'] ?? ''));
        if ($venue !== '') {
            $lines[] = ['', $venue, '', '', '', ''];
        }

        $gridRows = $data['gridRows'] ?? [];
        if ($gridRows === []) {
            $lines[] = ['No sessions in this week for the selected filters.', '', '', '', '', ''];
        } else {
            foreach ($gridRows as $row) {
                $lines[] = [
                    str_replace(["\r\n", "\r"], "\n", (string) ($row['time'] ?? '')),
                    $this->weekTimetableGridCellToPlain((string) ($row['Mon'] ?? '')),
                    $this->weekTimetableGridCellToPlain((string) ($row['Tue'] ?? '')),
                    $this->weekTimetableGridCellToPlain((string) ($row['Wed'] ?? '')),
                    $this->weekTimetableGridCellToPlain((string) ($row['Thu'] ?? '')),
                    $this->weekTimetableGridCellToPlain((string) ($row['Fri'] ?? '')),
                ];
            }
        }

        foreach ($data['footnotes'] ?? [] as $fn) {
            $t = trim((string) $fn);
            if ($t === '') {
                continue;
            }
            $lines[] = ['', $t, '', '', '', ''];
        }

        return $lines;
    }

    /**
     * PDF export — weekly Mon–Fri grid. Use query ?download=1 to force download.
     */
    public function exportWeekTimetablePdf(Request $request)
    {
        $data = $this->prepareWeekTimetableExport($request);

        $viewData = Arr::only($data, [
            'weekNum',
            'weekYear',
            'sheetWeekNumber',
            'courseTitle',
            'courseProgrammeTitle',
            'coursePeriodParen',
            'headerDates',
            'weekRangeLabel',
            'gridRows',
            'breakNotices',
            'venueSummaryLine',
            'footnotes',
            'pdfBodyFont',
        ]);

        $pdf = Pdf::loadView('admin.calendar.pdf.week-timetable-pdf', $viewData)
            ->setPaper('a4', 'landscape');

        $fname = $data['pdfFileBase'] . '.pdf';

        if ($request->boolean('download')) {
            return $pdf->download($fname);
        }

        return $pdf->stream($fname);
    }

    /**
     * Printable HTML (opens in new tab; auto print dialog).
     */
    public function exportWeekTimetablePrint(Request $request)
    {
        $data = $this->prepareWeekTimetableExport($request);

        $viewData = Arr::only($data, [
            'weekNum',
            'weekYear',
            'sheetWeekNumber',
            'courseTitle',
            'courseProgrammeTitle',
            'coursePeriodParen',
            'headerDates',
            'weekRangeLabel',
            'gridRows',
            'breakNotices',
            'venueSummaryLine',
            'footnotes',
            'pdfBodyFont',
        ]);

        return response()->view('admin.calendar.print.week-timetable-print', $viewData);
    }

    /**
     * Excel export — same layout as the revised sheet (letterhead + Mon–Fri grid; holidays omitted).
     */
    public function exportWeekTimetableExcel(Request $request)
    {
        $data = $this->prepareWeekTimetableExport($request);
        $sheetRows = $this->buildWeekTimetableExcelSheetArray($data);
        $flatSessions = $this->buildWeekTimetableExcelSessionRows($data['allEvents'], $data['weekMonday']);
        $sessionsMatrix = $this->buildWeekTimetableSessionsExcelMatrix($flatSessions);

        $fileName = $data['pdfFileBase'] . '.xlsx';
        $sheetTitle = 'Week ' . ($data['sheetWeekNumber'] ?? $data['weekNum']) . ' — ' . Str::limit($data['courseTitle'], 24, '');

        return Excel::download(
            new WeekTimetableWorkbookExport($sheetRows, $sheetTitle, $sessionsMatrix),
            $fileName
        );
    }

    /**
     * Flat session rows for the "Sessions" Excel sheet (Mon–Sun window; holidays omitted).
     *
     * @param  Collection<int, array<string, mixed>>  $allEvents
     * @return list<array<string, string>>
     */
    protected function buildWeekTimetableExcelSessionRows(Collection $allEvents, Carbon $weekMonday): array
    {
        $weekEnd = $weekMonday->copy()->addDays(6)->endOfDay();
        $rows = [];

        foreach ($allEvents as $ev) {
            if ($this->isWeekTimetableHoliday($ev)) {
                continue;
            }
            try {
                $start = Carbon::parse($ev['start']);
            } catch (\Throwable $e) {
                continue;
            }

            if ($start->lt($weekMonday->copy()->startOfDay()) || $start->gt($weekEnd)) {
                continue;
            }

            $timeStr = str_replace(["\n", "\r"], [' ', ''], $this->weekTimetableTimeColumnDisplay($ev, $start));
            $letter = $this->inferPdfGroupRowLetter((string) ($ev['group_name'] ?? ''));

            $rows[] = [
                'date' => $start->format('Y-m-d'),
                'weekday' => $start->englishDayOfWeek,
                'time' => $timeStr,
                'group_row' => $letter,
                'topic' => (string) ($ev['title'] ?? ''),
                'group_names' => (string) ($ev['group_name'] ?? ''),
                'session' => (string) ($ev['class_session'] ?? ''),
                'venue' => (string) ($ev['vanue'] ?? $ev['venue_name'] ?? ''),
                'faculty' => (string) ($ev['faculty_name'] ?? ''),
                'type' => 'Session',
                '__slot' => $this->weekTimetableSlotSortKey($ev, $start),
            ];
        }

        usort($rows, static function (array $a, array $b): int {
            return [$a['date'], $a['__slot'], $a['topic']] <=> [$b['date'], $b['__slot'], $b['topic']];
        });

        foreach ($rows as &$r) {
            unset($r['__slot']);
        }
        unset($r);

        return $rows;
    }

    /**
     * @param  list<array<string, string>>  $flat
     * @return list<list<string>>
     */
    protected function buildWeekTimetableSessionsExcelMatrix(array $flat): array
    {
        $head = ['Date', 'Weekday', 'Time', 'Group row', 'Topic', 'Group names', 'Session', 'Venue', 'Faculty', 'Type'];
        $matrix = [$head];
        foreach ($flat as $r) {
            $matrix[] = [
                $r['date'] ?? '',
                $r['weekday'] ?? '',
                $r['time'] ?? '',
                $r['group_row'] ?? '',
                $r['topic'] ?? '',
                $r['group_names'] ?? '',
                $r['session'] ?? '',
                $r['venue'] ?? '',
                $r['faculty'] ?? '',
                $r['type'] ?? '',
            ];
        }

        return $matrix;
    }

    protected function normalizeTimetableSessionString(string $s): string
    {
        $s = trim($s);
        if ($s === '') {
            return '';
        }
        $s = preg_replace('/[\x{2013}\x{2014}\x{2212}]/u', '-', $s);
        $s = preg_replace('/\s*hrs\.?\s*$/i', '', $s);

        return trim($s);
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    protected function splitClassSessionTimeRange(?string $raw): ?array
    {
        $s = $this->normalizeTimetableSessionString((string) $raw);
        if ($s === '') {
            return null;
        }
        $patterns = ['/\s+to\s+/i', '/\s*-\s*/'];
        foreach ($patterns as $pat) {
            $parts = preg_split($pat, $s, 2);
            if (is_array($parts) && count($parts) === 2) {
                $a = trim($parts[0]);
                $b = trim($parts[1]);
                if ($a !== '' && $b !== '') {
                    return [$a, $b];
                }
            }
        }

        return null;
    }

    /**
     * Start-of-slot from class_session (left segment) on the event calendar day — used when allDay hides real times in start/end.
     *
     * @param  array<string, mixed>  $ev
     */
    protected function weekTimetableSessionSlotStart(array $ev, Carbon $eventDay): ?Carbon
    {
        $pair = $this->splitClassSessionTimeRange($ev['class_session'] ?? null);
        $left = null;
        if ($pair !== null) {
            $left = trim($pair[0]);
        } else {
            $sessNorm = $this->normalizeTimetableSessionString((string) ($ev['class_session'] ?? ''));
            $left = $sessNorm !== '' ? $sessNorm : null;
        }
        if ($left === null || $left === '') {
            return null;
        }

        $dateStr = $eventDay->copy()->startOfDay()->format('Y-m-d');
        try {
            return Carbon::parse($dateStr . ' ' . $left);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Start/end minutes from midnight (0–2880 if session crosses midnight) for ordering.
     *
     * @param  array<string, mixed>  $ev
     * @return array{0: int, 1: int}  Use t1 < 0 for “no clock” (sort last).
     */
    protected function weekTimetableSlotWindowMinutes(array $ev, Carbon $start): array
    {
        $pair = $this->splitClassSessionTimeRange($ev['class_session'] ?? null);
        if ($pair !== null) {
            $left = $this->parseClockChunkTo24h(trim($pair[0]));
            $right = $this->parseClockChunkTo24h(trim($pair[1]));
            if ($left !== null && $right !== null) {
                [$h1, $m1] = array_map('intval', explode(':', $left, 2));
                [$h2, $m2] = array_map('intval', explode(':', $right, 2));
                $t1 = $h1 * 60 + $m1;
                $t2 = $h2 * 60 + $m2;
                if ($t2 < $t1) {
                    $t2 += 1440;
                }

                return [$t1, $t2];
            }
        }

        if (!empty($ev['allDay'])) {
            $sessNorm = $this->normalizeTimetableSessionString((string) ($ev['class_session'] ?? ''));
            if ($sessNorm !== '') {
                $single = $this->parseClockChunkTo24h($sessNorm);
                if ($single !== null) {
                    [$h, $mi] = array_map('intval', explode(':', $single, 2));
                    $t1 = $h * 60 + $mi;

                    return [$t1, min($t1 + 50, 24 * 60)];
                }
            }
            $fromSession = $this->weekTimetableSessionSlotStart($ev, $start);
            if ($fromSession !== null) {
                $t1 = (int) $fromSession->format('H') * 60 + (int) $fromSession->format('i');

                return [$t1, min($t1 + 50, 24 * 60)];
            }

            return [-1, -1];
        }

        $t1 = (int) $start->format('H') * 60 + (int) $start->format('i');
        try {
            $end = isset($ev['end']) ? Carbon::parse($ev['end']) : $start->copy()->addHour();
        } catch (\Throwable $e) {
            $end = $start->copy()->addHour();
        }
        $t2 = (int) $end->format('H') * 60 + (int) $end->format('i');
        if ($end->toDateString() !== $start->toDateString() && $t2 < $t1) {
            $t2 += 1440;
        }

        return [$t1, $t2];
    }

    /**
     * Stable slot key: clock-only band so Mon–Fri share one row per same session time (serial day order).
     *
     * @param  array<string, mixed>  $ev
     */
    protected function weekTimetableSlotSortKey(array $ev, Carbon $start): string
    {
        [$t1, $t2] = $this->weekTimetableSlotWindowMinutes($ev, $start);
        if ($t1 < 0) {
            return '999990_999990';
        }

        return sprintf('%05d_%05d', min($t1, 99999), min($t2, 99999));
    }

    /**
     * Official-style time column: "09:45" newline "to" newline "10:35" (24h from class_session when possible).
     *
     * @param  array<string, mixed>  $ev
     */
    protected function weekTimetableTimeColumnDisplay(array $ev, Carbon $start): string
    {
        $pair = $this->splitClassSessionTimeRange($ev['class_session'] ?? null);
        if ($pair !== null) {
            $left = $this->parseClockChunkTo24h($pair[0]);
            $right = $this->parseClockChunkTo24h($pair[1]);
            if ($left !== null && $right !== null) {
                return $left . "\nto\n" . $right;
            }
        }

        $sessNorm = $this->normalizeTimetableSessionString((string) ($ev['class_session'] ?? ''));
        if (!empty($ev['allDay']) && $sessNorm !== '') {
            $single = $this->parseClockChunkTo24h($sessNorm);
            if ($single !== null) {
                return $single . "\nto\n" . $single;
            }

            return 'All Day';
        }

        if (!empty($ev['allDay'])) {
            return 'All Day';
        }

        try {
            $end = isset($ev['end']) ? Carbon::parse($ev['end']) : $start->copy()->addHour();
        } catch (\Throwable $e) {
            $end = $start->copy()->addHour();
        }

        return $start->format('H:i') . "\nto\n" . $end->format('H:i');
    }

    protected function parseClockChunkTo24h(string $chunk): ?string
    {
        $chunk = trim($chunk);
        if ($chunk === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2})(\d{2})$/', $chunk, $m)) {
            $h = (int) $m[1];
            $mi = (int) $m[2];
            if ($h <= 23 && $mi <= 59) {
                return sprintf('%02d:%02d', $h, $mi);
            }
        }

        if (preg_match('/^(\d{2})(\d{2})$/', $chunk, $m4) && strlen($chunk) === 4) {
            $h = (int) $m4[1];
            $mi = (int) $m4[2];
            if ($h <= 23 && $mi <= 59) {
                return sprintf('%02d:%02d', $h, $mi);
            }
        }

        $ts = strtotime($chunk);
        if ($ts === false) {
            return null;
        }

        return date('H:i', $ts);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $allEvents
     * @return list<array{time: string, Mon: string, Tue: string, Wed: string, Thu: string, Fri: string}>
     */
    protected function buildWeekTimetablePdfGridRows(Collection $allEvents, Carbon $weekMonday): array
    {
        $weekFriday = $weekMonday->copy()->addDays(4)->endOfDay();
        $slots = [];

        foreach ($allEvents as $ev) {
            if ($this->isWeekTimetableHoliday($ev)) {
                continue;
            }
            try {
                $start = Carbon::parse($ev['start']);
            } catch (\Throwable $e) {
                continue;
            }
            if ($start->lt($weekMonday->copy()->startOfDay()) || $start->gt($weekFriday)) {
                continue;
            }

            $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            $day = $dayNames[$start->dayOfWeek];
            if (!in_array($day, ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'], true)) {
                continue;
            }

            $slotKey = $this->weekTimetableSlotSortKey($ev, $start);

            if (!isset($slots[$slotKey])) {
                $slots[$slotKey] = ['Mon' => [], 'Tue' => [], 'Wed' => [], 'Thu' => [], 'Fri' => []];
            }
            $slots[$slotKey][$day][] = $ev;
        }

        uksort($slots, static function (string $a, string $b): int {
            return strcmp($a, $b);
        });

        $rows = [];
        foreach ($slots as $slotKey => $days) {
            $displayTime = '';
            foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri'] as $d) {
                if (($days[$d] ?? []) !== []) {
                    $first = $days[$d][0];
                    try {
                        $st = Carbon::parse($first['start']);
                    } catch (\Throwable $e) {
                        $st = Carbon::now();
                    }
                    $displayTime = $this->weekTimetableTimeColumnDisplay($first, $st);
                    break;
                }
            }
            if ($displayTime === '') {
                $displayTime = $slotKey;
            }

            $rows[] = [
                'time' => $displayTime,
                'Mon' => $this->formatPdfCellHtml($days['Mon']),
                'Tue' => $this->formatPdfCellHtml($days['Tue']),
                'Wed' => $this->formatPdfCellHtml($days['Wed']),
                'Thu' => $this->formatPdfCellHtml($days['Thu']),
                'Fri' => $this->formatPdfCellHtml($days['Fri']),
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $cellEvents
     */
    protected function formatPdfCellHtml(array $cellEvents): string
    {
        if ($cellEvents === []) {
            return '';
        }

        $buckets = $this->bucketPdfCellEvents($cellEvents);
        $blocks = [];
        foreach ($buckets as $bucket) {
            $body = '';
            foreach ($bucket['events'] as $ev) {
                $body .= $this->formatPdfMiniCard($ev, $bucket['letter'] !== '');
            }
            if ($bucket['letter'] !== '') {
                $blocks[] = '<table class="cell-stack" width="100%" cellpadding="0" cellspacing="0"><tr>'
                    . '<td class="cell-lbl">' . e($bucket['letter']) . '</td>'
                    . '<td class="cell-body">' . $body . '</td></tr></table>';
            } else {
                $blocks[] = '<table class="cell-stack" width="100%" cellpadding="0" cellspacing="0"><tr>'
                    . '<td class="cell-body cell-body-full">' . $body . '</td></tr></table>';
            }
        }

        return '<div class="tt-wrap">' . implode('', $blocks) . '</div>';
    }

    /**
     * @param  list<array<string, mixed>>  $cellEvents
     * @return list<array{letter: string, events: list<array<string, mixed>>}>
     */
    protected function bucketPdfCellEvents(array $cellEvents): array
    {
        $isHoliday = static function (array $ev): bool {
            return ($ev['type'] ?? '') === 'holiday' || str_starts_with((string) ($ev['id'] ?? ''), 'holiday_');
        };

        $onlyHolidays = true;
        foreach ($cellEvents as $ev) {
            if (!$isHoliday($ev)) {
                $onlyHolidays = false;
                break;
            }
        }
        if ($onlyHolidays) {
            return [['letter' => '', 'events' => $cellEvents]];
        }

        $letters = [];
        foreach ($cellEvents as $idx => $ev) {
            $letters[$idx] = $this->inferPdfGroupRowLetter((string) ($ev['group_name'] ?? ''));
        }

        $allBlank = !array_filter($letters, static fn ($l) => $l !== '');
        if ($allBlank && count($cellEvents) > 1) {
            $out = [];
            foreach ($cellEvents as $i => $ev) {
                $out[] = ['letter' => chr(65 + $i), 'events' => [$ev]];
            }

            return $out;
        }

        $map = [];
        foreach ($cellEvents as $idx => $ev) {
            $key = $letters[$idx] !== '' ? $letters[$idx] : '_';
            if (!isset($map[$key])) {
                $map[$key] = [];
            }
            $map[$key][] = $ev;
        }

        $sortedKeys = array_keys($map);
        usort($sortedKeys, static function ($a, $b) {
            if ($a === '_') {
                return 1;
            }
            if ($b === '_') {
                return -1;
            }

            return strcmp((string) $a, (string) $b);
        });

        $rows = [];
        foreach ($sortedKeys as $k) {
            $rows[] = [
                'letter' => $k === '_' ? '' : $k,
                'events' => $map[$k],
            ];
        }

        return $rows;
    }

    protected function inferPdfGroupRowLetter(string $groupName): string
    {
        $blob = strtolower($groupName);
        if (preg_match('/\bgroup\s*([a-z])\b/i', $groupName, $m)) {
            return strtoupper($m[1]);
        }
        if (str_contains($blob, 'group a')) {
            return 'A';
        }
        if (str_contains($blob, 'group b')) {
            return 'B';
        }
        if (str_contains($blob, 'group c')) {
            return 'C';
        }
        if (str_contains($blob, 'group d')) {
            return 'D';
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $ev
     */
    protected function formatPdfMiniCard(array $ev, bool $suppressGroupBadge): string
    {
        $title = (string) ($ev['title'] ?? '');
        $group = (string) ($ev['group_name'] ?? '');
        $sess = (string) ($ev['class_session'] ?? '');
        $venue = (string) ($ev['vanue'] ?? $ev['venue_name'] ?? '');
        $fac = (string) ($ev['faculty_name'] ?? '');

        $badge = (!$suppressGroupBadge && $group !== '')
            ? '<div class="gb">' . e(Str::limit($group, 80)) . '</div>'
            : '';

        $lines = '<div class="ttl">' . e(Str::limit($title, 220)) . '</div>';
        if ($sess !== '') {
            $lines .= '<div class="ln">' . e(Str::limit($sess, 72)) . '</div>';
        }
        if ($fac !== '') {
            $lines .= '<div class="ln">(' . e(Str::limit($fac, 90)) . ')</div>';
        }
        if ($venue !== '') {
            $lines .= '<div class="ln">' . e(Str::limit($venue, 72)) . '</div>';
        }

        return '<div class="cardx">' . $badge . $lines . '</div>';
    }
    function SingleCalendarDetails(Request $request)
    {
        $eventId = $request->id;

        $event = DB::table('timetable')
            ->join('venue_master', 'timetable.venue_id', '=', 'venue_master.venue_id')
            ->where('timetable.pk', $eventId)
            ->select(
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
                    // Add field to extract session end time for sorting
                    DB::raw("
                    STR_TO_DATE(
                        TRIM(SUBSTRING_INDEX(t.class_session, '-', -1)),
                        '%h:%i %p'
                    ) as session_end_time
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
                // Modified: Show past sessions OR today's sessions if end time has passed
                ->whereRaw("
                TIMESTAMP(
                    t.END_DATE,
                    STR_TO_DATE(
                        TRIM(SUBSTRING_INDEX(t.class_session, '-', -1)),
                        '%h:%i %p'
                    )
                ) <= CONVERT_TZ(NOW(), '+00:00', '+05:30')
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
                ) <= CONVERT_TZ(NOW(), '+00:00', '+05:30')
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

        if ($inserted === 0) {
            $errorMessage = !empty($errors) ? implode(', ', $errors) : 'Please submit at least one feedback.';
            return back()->withErrors([
                'error' => $errorMessage
            ]);
        }

        // If some succeeded but some failed
        if (!empty($errors) && $inserted > 0) {
            $successMessage = "Successfully submitted $inserted feedback(s). " .
                (!empty($errors) ? 'Some items failed: ' . implode(', ', $errors) : '');
            return back()->with('success', $successMessage);
        }

        return back()->with('success', 'Feedback submitted successfully.');
    }
}