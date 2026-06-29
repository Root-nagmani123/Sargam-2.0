<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{ClassSessionMaster, CourseGroupTimetableMapping, CourseMaster, FacultyMaster, VenueMaster, SubjectMaster, SubjectModuleMaster, CalendarEvent, Holiday, SectorMaster};
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Services\FacultyFeedbackReportService;
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

        // Training-admin roles manage events and must see courses by role mapping, not timetable.
        $isTrainingAdmin = hasRole('Training') || hasRole('Training-Induction') || hasRole('Training MCTP Admin') || hasRole('Training IST');

        // Faculty see courses from their timetable / coordinator assignments, not role mapping.
        if (is_faculty_portal_user() && !$isTrainingAdmin) {
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

        $sectors = SectorMaster::query()->active()->get(['pk', 'sector_name']);
        $facultyRoles = ['Teaching', 'Sectional', 'Administration'];

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
            'internal_faculty',
            'sectors',
            'facultyRoles'
        ));
    }

    /**
     * Dedicated full-page "Add Event" form (replaces the in-calendar modal for
     * creating events). Loads the same masters the modal used, plus sectors and
     * the static faculty-role list, and renders admin.calendar.create-event.
     */
    public function createEvent(Request $request)
    {
        $data_course_id = get_Role_by_course();

        $courseMaster = CourseMaster::where('course_master.active_inactive', 1)
            ->whereDate('end_date', '>=', today());

        $isTrainingAdmin = hasRole('Training') || hasRole('Training-Induction') || hasRole('Training MCTP Admin') || hasRole('Training IST');

        if (is_faculty_portal_user() && !$isTrainingAdmin) {
            $facultyPk = get_auth_faculty_master_pk();
            if ($facultyPk) {
                $facultyCourseIds = app(FacultyFeedbackReportService::class)->getAccessibleCourseIds($facultyPk);
                $courseMaster = $facultyCourseIds->isNotEmpty()
                    ? $courseMaster->whereIn('course_master.pk', $facultyCourseIds)
                    : $courseMaster->whereRaw('1 = 0');
            } else {
                $courseMaster = $courseMaster->whereRaw('1 = 0');
            }
        } elseif (!empty($data_course_id)) {
            $courseMaster = $courseMaster->whereIn('course_master.pk', $data_course_id);
        }

        $courseMaster = $courseMaster->select('course_master.pk', 'course_name', 'couse_short_name', 'course_year')
            ->get();

        $facultyMaster = FacultyMaster::where('active_inactive', 1)
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

        $sectors = SectorMaster::query()->active()->get(['pk', 'sector_name']);

        // No roles master exists yet — fixed list per product decision.
        $facultyRoles = ['Teaching', 'Sectional', 'Administration'];

        // Optional date prefill (e.g. arriving from a calendar day click).
        $prefillDate = $request->filled('date')
            ? Carbon::parse($request->date)->format('Y-m-d')
            : null;

        return view('admin.calendar.create-event', compact(
            'courseMaster',
            'facultyMaster',
            'subjects',
            'venueMaster',
            'classSessionMaster',
            'sectors',
            'facultyRoles',
            'prefillDate'
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

        $sectors = SectorMaster::query()->active()->get(['pk', 'sector_name']);
        $facultyRoles = ['Teaching', 'Sectional', 'Administration'];

        return view('admin.calendar.ot-index', compact(
            'courseMaster',
            'facultyMaster',
            'subjects',
            'venueMaster',
            'classSessionMaster',
            'internal_faculty',
            'sectors',
            'facultyRoles'
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
        abort_unless(hasRole('Training') || hasRole('Super Admin') || hasRole('Admin') || hasRole('Training MCTP Admin') || hasRole('Training IST') || hasRole('Training-Induction'), 403);
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
            'faculty_type' => 'nullable|integer',
            'faculty_row_type' => 'nullable|array',
            'faculty_role' => 'nullable|array',
            'faculty_feedback_remark' => 'nullable|array',
            'faculty_feedback_rating' => 'nullable|array',
            'sector' => 'nullable|integer',
            'vanue' => 'required|integer',
            'shift' => 'required_if:shift_type,1',
            'start_time' => 'required_if:shift_type,2',
            'end_time' => 'required_if:shift_type,2',
            'break_type' => 'nullable|in:tea,lunch,snacks',
            'break_start_time' => 'nullable',
            'break_end_time' => 'nullable',
        ], [
            'type_names.required' => 'The Group type names field is required.',
            'type_names.min' => 'Please select at least one Group type name.',
            'faculty.required' => 'The Faculty field is required.',
            'faculty.min' => 'Please select at least one Faculty.',
        ]);

        // Per-faculty rows from the full-page form (null for the legacy modal).
        $facultyDetails = $this->buildFacultyDetails($request);

        $event = new CalendarEvent();
        $event->course_master_pk = $request->Course_name;
        $event->subject_master_pk = $request->subject_name;
        $event->subject_module_master_pk = $request->subject_module;
        $event->subject_topic = $request->topic;
        $event->course_group_type_master = $request->group_type;
        $event->group_name = json_encode(array_values($request->type_names ?? []));
        // array_values: dynamic faculty rows submit keyed arrays (faculty[2] etc.)
        // — store a clean sequential JSON array for existing reads.
        $event->faculty_master = json_encode(array_values($request->input('faculty', [])));
        // Keep the single faculty_type column populated for existing reads:
        // explicit value (modal) or the first faculty row's type (new form).
        $event->faculty_type = $request->faculty_type
            ?? ($facultyDetails[0]['faculty_type'] ?? null);
        // Internal faculty: explicit list (modal) or rows flagged Internal (type 1).
        $internalFaculty = $request->internal_faculty
            ?? collect($facultyDetails)->where('faculty_type', 1)->pluck('faculty_pk')->values()->all();
        $event->internal_faculty = json_encode($internalFaculty ?? []);
        $event->faculty_details = $facultyDetails ? json_encode($facultyDetails) : null;
        $event->sector_pk = $request->sector ?: null;
        // Break section: a break is present when a break type is chosen.
        $event->break_type = $request->break_type ?: null;
        $event->break_start_time = $request->break_start_time ?: null;
        $event->break_end_time = $request->break_end_time ?: null;
        $event->is_break = ($request->break_type || $request->boolean('is_break')) ? 1 : 0;
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

        if ($facultyDetails) {
            // Per-faculty feedback (new form) — keep event-level flags in sync.
            $feedbacks = collect($facultyDetails)->pluck('feedback');
            $hasRemark = $feedbacks->contains('remark') || $feedbacks->contains('both');
            $hasRating = $feedbacks->contains('rating') || $feedbacks->contains('both');
            $event->feedback_checkbox = ($hasRemark || $hasRating) ? 1 : 0;
            $event->Ratting_checkbox = $hasRating ? 1 : 0;
            $event->Remark_checkbox = $hasRemark ? 1 : 0;
            $event->Faculty_feedback = json_encode($facultyDetails);
        } else {
            $event->feedback_checkbox = $request->has('feedback_checkbox') ? 1 : 0;
            $event->Ratting_checkbox = $request->has('ratingCheckbox') ? 1 : 0;
            $event->Remark_checkbox = $request->has('remarkCheckbox') ? 1 : 0;
        }
        $event->Bio_attendance = $request->boolean('bio_attendanceCheckbox') ? 1 : 0;
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

        $this->syncSupportingFacultyFeedback($event, $facultyDetails);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Event created successfully']);
        }

        return redirect()->route('calendar.index')->with('success', 'Event created successfully.');
    }

    /**
     * Assemble the per-faculty detail rows from the full-page Add Event form's
     * parallel arrays (faculty[], faculty_row_type[], faculty_role[],
     * faculty_feedback[]). Returns [] when those arrays are absent (legacy modal).
     */
    /**
     * Sync supporting_faculty_feedback rows whenever a timetable event is saved.
     *
     * Logic:
     *   - Guest (type 2) / Research (type 3) faculty  → main faculty (gets rated)
     *   - Internal (type 1) faculty                   → supporting faculty (gives rating)
     *   - For every Internal × External pair, insert a pending row (is_submitted = 0).
     *   - Already-submitted rows (is_submitted = 1) are never touched.
     *   - Pairs removed from the timetable get soft-deleted (active_inactive = 0).
     */
    private function syncSupportingFacultyFeedback(CalendarEvent $event, array $facultyDetails): void
    {
        if (empty($facultyDetails)) {
            return;
        }

        // Teaching role → main faculty (gets rated by supporting faculty)
        // Any other role (Sectional, Administration, etc.) → supporting faculty (gives rating)
        $mainFaculty       = collect($facultyDetails)
            ->where('role', 'Teaching')
            ->pluck('faculty_pk')->all();
        $supportingFaculty = collect($facultyDetails)
            ->filter(fn($d) => !empty($d['role']) && $d['role'] !== 'Teaching')
            ->pluck('faculty_pk')->all();

        $now = now();

        // Soft-delete pending rows whose faculty pairs are no longer valid.
        // Runs even when one side is empty (e.g. all roles set to none).
        $deleteQuery = DB::table('supporting_faculty_feedback')
            ->where('timetable_pk', $event->pk)
            ->where('is_submitted', 0);

        if (empty($supportingFaculty) || empty($mainFaculty)) {
            // One side is completely gone — soft-delete all pending rows for this timetable
            $deleteQuery->update(['active_inactive' => 0, 'modified_date' => $now]);
            return;
        }

        // Partial removal — only rows whose faculty is no longer in either list
        $deleteQuery->where(function ($q) use ($supportingFaculty, $mainFaculty) {
            $q->whereNotIn('supporting_faculty_master_pk', $supportingFaculty)
              ->orWhereNotIn('main_faculty_master_pk', $mainFaculty);
        })->update(['active_inactive' => 0, 'modified_date' => $now]);

        // Insert or reactivate pairs
        foreach ($supportingFaculty as $supportingPk) {
            foreach ($mainFaculty as $mainPk) {
                // Reactivate soft-deleted pending row if it exists
                $reactivated = DB::table('supporting_faculty_feedback')
                    ->where('timetable_pk', $event->pk)
                    ->where('main_faculty_master_pk', $mainPk)
                    ->where('supporting_faculty_master_pk', $supportingPk)
                    ->where('is_submitted', 0)
                    ->where('active_inactive', 0)
                    ->update(['active_inactive' => 1, 'modified_date' => $now]);

                // No existing row — insert fresh
                if (!$reactivated) {
                    DB::table('supporting_faculty_feedback')->insertOrIgnore([
                        'timetable_pk'                 => $event->pk,
                        'course_master_pk'             => $event->course_master_pk,
                        'session_type'                 => $event->session_type,
                        'main_faculty_master_pk'       => $mainPk,
                        'supporting_faculty_master_pk' => $supportingPk,
                        'is_submitted'                 => 0,
                        'active_inactive'              => 1,
                        'created_by'                   => auth()->id(),
                        'created_date'                 => $now,
                        'modified_date'                => $now,
                    ]);
                }
            }
        }
    }

    private function buildFacultyDetails(Request $request): array
    {
        $facultyIds      = $request->input('faculty', []);
        $rowTypes        = $request->input('faculty_row_type', []);
        $roles           = $request->input('faculty_role', []);
        $feedbackRemarks = $request->input('faculty_feedback_remark', []);
        $feedbackRatings = $request->input('faculty_feedback_rating', []);

        // Only treat as the new format when at least one per-row attribute is sent.
        if (empty($rowTypes) && empty($roles) && empty($feedbackRemarks) && empty($feedbackRatings)) {
            return [];
        }

        $details = [];
        foreach ($facultyIds as $i => $facultyPk) {
            if ($facultyPk === null || $facultyPk === '') {
                continue;
            }
            $role      = $roles[$i] ?? null;
            $hasRemark = ($role === 'Teaching') && !empty($feedbackRemarks[$i]);
            $hasRating = ($role === 'Teaching') && !empty($feedbackRatings[$i]);
            if ($hasRemark && $hasRating) {
                $feedback = 'both';
            } elseif ($hasRemark) {
                $feedback = 'remark';
            } elseif ($hasRating) {
                $feedback = 'rating';
            } else {
                $feedback = 'none';
            }
            $details[] = [
                'faculty_pk'   => (int) $facultyPk,
                'faculty_type' => isset($rowTypes[$i]) && $rowTypes[$i] !== '' ? (int) $rowTypes[$i] : null,
                'role'         => $role,
                'feedback'     => $feedback,
            ];
        }

        return $details;
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

        // Keep the raw rows so breaks can be emitted as their own calendar entries.
        $timetableRows = $events;

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
                $orderedIds = implode(',', array_map('intval', $facultyIds));
                $facultyNames = DB::table('faculty_master')
                    ->whereIn('pk', $facultyIds)
                    ->orderByRaw("FIELD(pk, {$orderedIds})")
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
                'edit_url'     => route('calendar.event.edit.page', encrypt($event->pk)),
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

        // Emit breaks (tea / lunch / snacks) as their own entries so they
        // appear in both the calendar and the Weekly Timetable table.
        $breaks = $this->buildBreakEvents($timetableRows);

        // Merge events, breaks and holidays
        $allEvents = $events->merge($breaks)->merge($holidays);

        return response()->json($allEvents);
    }

    /**
     * Build synthetic calendar entries for any timetable row that carries a
     * break (tea / lunch / snacks). A break is stored on the event row itself
     * (break_type / break_start_time / break_end_time) rather than as its own
     * timetable record, so we surface it here as a separate timed event.
     */
    private function buildBreakEvents($rows)
    {
        $breaks = collect();

        foreach ($rows as $row) {
            $hasBreak = !empty($row->is_break) || !empty($row->break_type);
            if (!$hasBreak || empty($row->break_start_time) || empty($row->break_end_time)) {
                continue;
            }

            try {
                $date    = Carbon::parse($row->START_DATE)->format('Y-m-d');
                $startCarbon = Carbon::parse($row->break_start_time);
                $endCarbon   = Carbon::parse($row->break_end_time);
            } catch (\Exception $e) {
                continue;
            }

            $sessionLabel = $startCarbon->format('h:i A') . ' - ' . $endCarbon->format('h:i A');
            $title        = ucfirst($row->break_type ?: 'break') . ' Break';

            $breaks->push([
                'id'                  => 'break_' . $row->pk,
                'title'               => $title,
                'start'               => $date . 'T' . $startCarbon->format('H:i:s'),
                'end'                 => $date . 'T' . $endCarbon->format('H:i:s'),
                'vanue'               => '',
                'faculty_name'        => '',
                'group_name'          => '',
                'class_session'       => $sessionLabel,
                'class_session_debug' => $sessionLabel,
                'backgroundColor'     => '#fff7ed',
                'borderColor'         => '#fed7aa',
                'textColor'           => '#9a3412',
                'allDay'              => false,
                'display'             => 'block',
                'is_break'            => true,
                'break_type'          => $row->break_type,
                'type'                => 'break',
            ]);
        }

        return $breaks;
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
                'timetable.faculty_details',
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

        // Build faculty name list with role in parentheses if available
        $facultyDetails = json_decode($event->faculty_details, true) ?? [];
        $facultyDetailMap = collect($facultyDetails)->keyBy('faculty_pk'); // pk => detail row

        $orderedFacultyIds = $facultyIds ?: [];
        $facultyOrderSql   = !empty($orderedFacultyIds)
            ? 'FIELD(pk, ' . implode(',', array_map('intval', $orderedFacultyIds)) . ')'
            : 'pk';
        $facultyRows = DB::table('faculty_master')
            ->whereIn('pk', $orderedFacultyIds)
            ->orderByRaw($facultyOrderSql)
            ->get(['pk', 'full_name']);

        $facultyNames = $facultyRows->map(function ($f) use ($facultyDetailMap) {
            $detail = $facultyDetailMap->get($f->pk);
            $role   = $detail['role'] ?? null;
            return $role ? $f->full_name . ' (' . $role . ')' : $f->full_name;
        })->implode(', ');

        return response()->json([
            'id'               => $event->pk,
            'topic'            => $event->subject_topic ?? '',
            'start'            => $event->START_DATE,
            'faculty_name'     => $facultyNames,
            'internal_faculty' => $internalFacultyNames->implode(', '),
            'venue_name'       => $event->venue_name ?? '',
            'class_session'    => $event->class_session ?? '',
            'group_name'       => $groupNames->implode(', ') ?? '',
            'edit_url'         => route('calendar.event.edit.page', encrypt($event->pk)),
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

        // Keep the raw rows so breaks can be emitted as their own calendar entries.
        $timetableRows = $events;

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
                $orderedIds = implode(',', array_map('intval', $facultyIds));
                $facultyNames = DB::table('faculty_master')
                    ->whereIn('pk', $facultyIds)
                    ->orderByRaw("FIELD(pk, {$orderedIds})")
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

        $breaks = $this->buildBreakEvents($timetableRows);

        $allEvents = $events->merge($breaks)->merge($holidays);

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
                $facultyIds = !empty($r->faculty_master) ? [$r->faculty_master] : [];
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
     * Academic time table as a print-ready A4 portrait PDF (flat session list).
     * Renders admin.calendar.pdf.ot-timetable-pdf for the calendar's visible range.
     * ?start & ?end (YYYY-MM-DD) bound the period, ?course_id filters (same as the
     * calendar filter), ?download=1 forces an attachment.
     */
    public function downloadTimetablePdf(Request $request)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(120);

        $rangeStartDate = $request->filled('start')
            ? Carbon::parse($request->start)
            : Carbon::now()->startOfMonth();
        $rangeEndDate = $request->filled('end')
            ? Carbon::parse($request->end)
            : Carbon::now()->endOfMonth();

        $courseId = $request->query('course_id') ?: null;

        $events = DB::table('timetable')
            ->leftJoin('venue_master', 'timetable.venue_id', '=', 'venue_master.venue_id');

        // Scope events by user type — mirror fullCalendarDetails().
        if (hasRole('Student-OT')) {
            $studentPk = auth()->user()->user_id;
            $events = $events
                ->join('course_group_timetable_mapping', 'course_group_timetable_mapping.timetable_pk', '=', 'timetable.pk')
                ->join('student_course_group_map', 'student_course_group_map.group_type_master_course_master_map_pk', '=', 'course_group_timetable_mapping.group_pk')
                ->where('student_course_group_map.student_master_pk', $studentPk);
        } elseif (is_faculty_portal_user()) {
            $facultyPk = get_auth_faculty_master_pk();
            if ($facultyPk) {
                $events = $this->scopeTimetableForFaculty($events, $facultyPk);
            } else {
                $events = $events->whereRaw('1 = 0');
            }
        } else {
            $dataCourseId = get_Role_by_course();
            if (!empty($dataCourseId)) {
                $events = $events->whereIn('timetable.course_master_pk', $dataCourseId);
            }
        }

        if ($courseId) {
            $events = $events->where('timetable.course_master_pk', $courseId);
        }

        $events = $events
            ->whereDate('timetable.START_DATE', '>=', $rangeStartDate->toDateString())
            ->whereDate('timetable.START_DATE', '<=', $rangeEndDate->toDateString())
            ->select(
                'timetable.subject_topic',
                'timetable.class_session',
                'timetable.START_DATE',
                'timetable.faculty_master',
                'timetable.is_break',
                'timetable.break_type',
                'timetable.break_start_time',
                'timetable.break_end_time',
                'venue_master.venue_name as venue_name'
            )
            ->orderBy('timetable.START_DATE')
            ->orderBy('timetable.class_session')
            ->get();

        $course = $courseId
            ? DB::table('course_master')->where('pk', $courseId)->first()
            : null;

        $courseStartDate = ($course && !empty($course->start_year))
            ? Carbon::parse($course->start_year)->format('j F Y') : '';
        $courseEndDate = ($course && !empty($course->end_date))
            ? Carbon::parse($course->end_date)->format('j F Y') : '';
        $courseDuration = ($courseStartDate && $courseEndDate)
            ? $courseStartDate . ' to ' . $courseEndDate : '';

        $toDataUri = function (string $path): string {
            if (!extension_loaded('gd') || !is_file($path)) return '';
            $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = in_array($ext, ['jpg', 'jpeg']) ? 'image/jpeg' : 'image/png';
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
        };

        $weeks = $this->buildWeeksGrid($events, $rangeStartDate, $rangeEndDate, $course);

        $primaryVenue = '';
        $venueCounts  = [];
        foreach ($events as $e) {
            if (!empty($e->venue_name)) {
                $venueCounts[$e->venue_name] = ($venueCounts[$e->venue_name] ?? 0) + 1;
            }
        }
        if (!empty($venueCounts)) {
            arsort($venueCounts);
            $primaryVenue = array_key_first($venueCounts);
        }

        $data = [
            'weeks'          => $weeks,
            'rangeStart'     => $rangeStartDate->format('d M Y'),
            'rangeEnd'       => $rangeEndDate->format('d M Y'),
            'course'         => $course,
            'courseStartDate'=> $courseStartDate,
            'courseEndDate'  => $courseEndDate,
            'courseDuration' => $courseDuration,
            'multiCourse'    => is_null($courseId),
            'primaryVenue'   => $primaryVenue,
            'footerNote'     => '',
            'studentName'    => hasRole('Student-OT') ? (auth()->user()->user_name ?? null) : null,
            'logoLeft'       => $toDataUri(public_path('admin_assets/images/logos/logo_new.png')),
            'logoRight'      => $toDataUri(public_path('admin_assets/images/logos/constitution-75.png'))
                ?: $toDataUri(public_path('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png')),
            'titleHindi'     => $toDataUri(public_path('admin_assets/images/logos/lbsnaa-title-hi.png')),
        ];

        $pdf = Pdf::loadView('admin.calendar.pdf.ot-timetable-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'          => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'isPhpEnabled'         => true,
                'dpi'                  => 96,
            ]);

        $fileName = 'time-table-' . $rangeStartDate->format('Y-m-d') . '.pdf';

        return $request->boolean('download')
            ? $pdf->download($fileName)
            : $pdf->stream($fileName);
    }

    /**
     * OT (Student-OT) timetable PDF for the dedicated OT calendar's Download
     * button. Always scoped to the logged-in student's groups (mirrors
     * otFullCalendarDetails) and renders the same ot-timetable-pdf template
     * via buildWeeksGrid(). Accepts ?start, ?end (YYYY-MM-DD) and ?course_id.
     */
    public function otDownloadPdf(Request $request)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(120);

        $rangeStartDate = $request->filled('start')
            ? Carbon::parse($request->start)
            : Carbon::now()->startOfMonth();
        $rangeEndDate = $request->filled('end')
            ? Carbon::parse($request->end)
            : Carbon::now()->endOfMonth();

        $courseId  = $request->query('course_id') ?: null;
        $studentPk = auth()->user()->user_id;

        $events = DB::table('timetable')
            ->leftJoin('venue_master', 'timetable.venue_id', '=', 'venue_master.venue_id')
            ->join('course_group_timetable_mapping', 'course_group_timetable_mapping.timetable_pk', '=', 'timetable.pk')
            ->join('student_course_group_map', 'student_course_group_map.group_type_master_course_master_map_pk', '=', 'course_group_timetable_mapping.group_pk')
            ->where('student_course_group_map.student_master_pk', $studentPk);

        if ($courseId) {
            $events = $events->where('timetable.course_master_pk', $courseId);
        }

        $events = $events
            ->whereDate('timetable.START_DATE', '>=', $rangeStartDate->toDateString())
            ->whereDate('timetable.START_DATE', '<=', $rangeEndDate->toDateString())
            ->select(
                'timetable.pk',
                'timetable.subject_topic',
                'timetable.class_session',
                'timetable.START_DATE',
                'timetable.faculty_master',
                'timetable.is_break',
                'timetable.break_type',
                'timetable.break_start_time',
                'timetable.break_end_time',
                'venue_master.venue_name as venue_name'
            )
            ->distinct()
            ->orderBy('timetable.START_DATE')
            ->orderBy('timetable.class_session')
            ->get();

        $course = $courseId
            ? DB::table('course_master')->where('pk', $courseId)->first()
            : null;

        $courseStartDate = ($course && !empty($course->start_year))
            ? Carbon::parse($course->start_year)->format('j F Y') : '';
        $courseEndDate = ($course && !empty($course->end_date))
            ? Carbon::parse($course->end_date)->format('j F Y') : '';
        $courseDuration = ($courseStartDate && $courseEndDate)
            ? $courseStartDate . ' to ' . $courseEndDate : '';

        $toDataUri = function (string $path): string {
            if (!extension_loaded('gd') || !is_file($path)) return '';
            $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = in_array($ext, ['jpg', 'jpeg']) ? 'image/jpeg' : 'image/png';
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
        };

        $weeks = $this->buildWeeksGrid($events, $rangeStartDate, $rangeEndDate, $course);

        $primaryVenue = '';
        $venueCounts  = [];
        foreach ($events as $e) {
            if (!empty($e->venue_name)) {
                $venueCounts[$e->venue_name] = ($venueCounts[$e->venue_name] ?? 0) + 1;
            }
        }
        if (!empty($venueCounts)) {
            arsort($venueCounts);
            $primaryVenue = array_key_first($venueCounts);
        }

        $data = [
            'weeks'          => $weeks,
            'rangeStart'     => $rangeStartDate->format('d M Y'),
            'rangeEnd'       => $rangeEndDate->format('d M Y'),
            'course'         => $course,
            'courseStartDate'=> $courseStartDate,
            'courseEndDate'  => $courseEndDate,
            'courseDuration' => $courseDuration,
            'multiCourse'    => is_null($courseId),
            'primaryVenue'   => $primaryVenue,
            'footerNote'     => '',
            'studentName'    => auth()->user()->user_name ?? null,
            'logoLeft'       => $toDataUri(public_path('admin_assets/images/logos/logo_new.png')),
            'logoRight'      => $toDataUri(public_path('admin_assets/images/logos/constitution-75.png'))
                ?: $toDataUri(public_path('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png')),
            'titleHindi'     => $toDataUri(public_path('admin_assets/images/logos/lbsnaa-title-hi.png')),
        ];

        $pdf = Pdf::loadView('admin.calendar.pdf.ot-timetable-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'          => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'isPhpEnabled'         => true,
                'dpi'                  => 96,
            ]);

        $fileName = 'time-table-' . $rangeStartDate->format('Y-m-d') . '.pdf';

        return $request->boolean('download')
            ? $pdf->download($fileName)
            : $pdf->stream($fileName);
    }

    /** Preview page: shows the timetable PDF in-browser with a Download button. */
    public function previewTimetablePdf(Request $request)
    {
        $streamUrl   = route('calendar.timetable.pdf',   $request->only(['start', 'end', 'course_id']));
        $downloadUrl = route('calendar.timetable.pdf',   array_merge($request->only(['start', 'end', 'course_id']), ['download' => 1]));
        $title = 'Time Table';
        return view('admin.calendar.pdf.preview', compact('streamUrl', 'downloadUrl', 'title'));
    }

    /** Preview page: shows the weekly timetable PDF in-browser with a Download button. */
    public function previewWeeklyTimetablePdf(Request $request)
    {
        $streamUrl   = route('calendar.weekly-timetable.pdf',   $request->only(['week_start', 'course_id']));
        $downloadUrl = route('calendar.weekly-timetable.pdf',   array_merge($request->only(['week_start', 'course_id']), ['download' => 1]));
        $title = 'Weekly Time Table';
        return view('admin.calendar.pdf.preview', compact('streamUrl', 'downloadUrl', 'title'));
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

        $weekRows = DB::table('timetable')
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
                'timetable.is_break',
                'timetable.break_type',
                'timetable.break_start_time',
                'timetable.break_end_time',
                'venue_master.venue_name as venue_name'
            )
            ->orderBy('timetable.START_DATE')
            ->get();

        // Programme details from the course.
        $course = $courseId ? DB::table('course_master')->where('pk', $courseId)->first() : null;
        $multiCourse = is_null($courseId);

        $courseStartDate = ($course && !empty($course->start_year))
            ? Carbon::parse($course->start_year)->format('j F Y') : '';
        $courseEndDate = ($course && !empty($course->end_date))
            ? Carbon::parse($course->end_date)->format('j F Y') : '';
        $courseDuration = ($courseStartDate && $courseEndDate)
            ? $courseStartDate . ' to ' . $courseEndDate : '';

        // Most common venue across the week.
        $primaryVenue = '';
        $venueCounts  = [];
        foreach ($weekRows as $r) {
            if (!empty($r->venue_name)) {
                $venueCounts[$r->venue_name] = ($venueCounts[$r->venue_name] ?? 0) + 1;
            }
        }
        if (!empty($venueCounts)) {
            arsort($venueCounts);
            $primaryVenue = array_key_first($venueCounts);
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
            'weeks'           => $this->buildWeeksGrid($weekRows, $weekStart, $weekEnd, $course),
            'rangeStart'      => $weekStart->format('d M Y'),
            'rangeEnd'        => $weekEnd->format('d M Y'),
            'course'          => $course,
            'courseStartDate' => $courseStartDate,
            'courseEndDate'   => $courseEndDate,
            'courseDuration'  => $courseDuration,
            'multiCourse'     => $multiCourse,
            'primaryVenue'    => $primaryVenue,
            'footerNote'      => trim((string) $request->input('note', '')),
            'studentName'     => auth()->user()->user_name ?? '',
            'logoLeft'        => $toDataUri(public_path('admin_assets/images/logos/logo_new.png')),
            'logoRight'       => $toDataUri(public_path('admin_assets/images/logos/constitution-75.png'))
                ?: $toDataUri(public_path('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png')),
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

        return $request->boolean('download')
            ? $pdf->download($fileName)
            : $pdf->stream($fileName);
    }

    /**
     * Build the $weeks array expected by ot-timetable-pdf.blade.php.
     * Each entry covers one Mon–Sun week within [$rangeStart, $rangeEnd], with
     * sessions/breaks laid onto shared time boundaries so longer sessions span
     * multiple rows (rowspan) and breaks shared by all days become full bands.
     */
    private function buildWeeksGrid($events, Carbon $rangeStart, Carbon $rangeEnd, $course = null): array
    {
        $weeks = [];
        $cursor = $rangeStart->copy()->startOfWeek(Carbon::MONDAY);

        while ($cursor->lte($rangeEnd)) {
            $weekEnd = $cursor->copy()->addDays(6);

            // Filter events for this week (Mon–Sun)
            $weekEvents = collect($events)->filter(function ($e) use ($cursor, $weekEnd) {
                $d = Carbon::parse($e->START_DATE);
                return $d->between($cursor, $weekEnd) && $d->dayOfWeekIso >= 1 && $d->dayOfWeekIso <= 7;
            });

            // Build days descriptors Mon–Sun
            $days = [];
            for ($i = 0; $i < 7; $i++) {
                $d = $cursor->copy()->addDays($i);
                $days[] = [
                    'key'     => $i + 1,
                    'dayName' => $d->format('l'),
                    'label'   => $d->format('d.m.Y'),
                ];
            }

            // Collect timed items (sessions + breaks) per day and the time
            // boundaries they imply. Each distinct start/end becomes a grid line;
            // an item then spans (rowspan) every row that falls inside it, giving
            // the nested look where a long session covers several time rows.
            $itemsByDay   = [];
            $untimedByDay = [];
            foreach ($days as $day) {
                $itemsByDay[$day['key']]   = [];
                $untimedByDay[$day['key']] = [];
            }
            $boundaries = [];

            foreach ($weekEvents as $r) {
                $dow = (int) Carbon::parse($r->START_DATE)->dayOfWeekIso;
                if (!isset($itemsByDay[$dow])) {
                    continue;
                }

                $faculty = $this->resolveEventFaculty($r->faculty_master);
                $slot    = trim((string) $r->class_session);
                [$sFrom, $sTo] = $this->splitSessionTime($slot);
                $sStart = $this->parseToMinutes($sFrom);
                $sEnd   = $this->parseToMinutes($sTo);

                $session = [
                    'topic'   => trim((string) $r->subject_topic) ?: 'Session',
                    'faculty' => $faculty,
                    'venue'   => trim((string) ($r->venue_name ?? '')),
                    'isBreak' => false,
                    'course'  => '',
                ];

                if ($sStart !== null && $sEnd !== null && $sEnd > $sStart) {
                    $session['start'] = $sStart;
                    $session['end']   = $sEnd;
                    $session['time']  = $this->fmtMinutes($sStart) . '-' . $this->fmtMinutes($sEnd);
                    $itemsByDay[$dow][] = $session;
                    $boundaries[$sStart] = true;
                    $boundaries[$sEnd]   = true;
                } else {
                    // Shift-based / unparsable time — render in a trailing plain row.
                    $session['time'] = $slot;
                    $untimedByDay[$dow][] = $session;
                }

                // Break (tea / lunch / snacks) stored on the event row itself.
                $bk = $this->buildBreakInterval($r);
                if ($bk) {
                    $itemsByDay[$dow][] = [
                        'topic'   => $bk['topic'],
                        'faculty' => '',
                        'venue'   => '',
                        'isBreak' => true,
                        'course'  => '',
                        'time'    => $bk['time'],
                        'start'   => $bk['start'],
                        'end'     => $bk['end'],
                    ];
                    $boundaries[$bk['start']] = true;
                    $boundaries[$bk['end']]   = true;
                }
            }

            $bs = array_keys($boundaries);
            sort($bs, SORT_NUMERIC);
            $rowCount = max(0, count($bs) - 1);

            // Lay each day's items onto the boundary rows with rowspans; rows
            // below an item's first row (still inside it) become 'skip'.
            $cellGrid = [];
            foreach ($days as $day) {
                $cellGrid[$day['key']] = [];
                for ($i = 0; $i < $rowCount; $i++) {
                    $cellGrid[$day['key']][$i] = [
                        'state'   => 'show',
                        'rowspan' => 1,
                        'isBreak' => false,
                        'events'  => [],
                    ];
                }
                foreach ($itemsByDay[$day['key']] as $it) {
                    $si = array_search($it['start'], $bs, true);
                    $ei = array_search($it['end'], $bs, true);
                    if ($si === false || $ei === false || $ei <= $si) {
                        continue;
                    }
                    $span = $ei - $si;
                    $cellGrid[$day['key']][$si]['events'][] = [
                        'topic'   => $it['topic'],
                        'faculty' => $it['faculty'],
                        'venue'   => $it['venue'],
                        'isBreak' => $it['isBreak'],
                        'course'  => $it['course'],
                        'time'    => $it['time'],
                    ];
                    if ($span > $cellGrid[$day['key']][$si]['rowspan']) {
                        $cellGrid[$day['key']][$si]['rowspan'] = $span;
                    }
                    if ($it['isBreak']) {
                        $cellGrid[$day['key']][$si]['isBreak'] = true;
                    }
                    for ($k = $si + 1; $k < $ei; $k++) {
                        $cellGrid[$day['key']][$k]['state'] = 'skip';
                    }
                }
            }

            // Build row descriptors from boundaries.
            $rows = [];
            for ($i = 0; $i < $rowCount; $i++) {
                // Full-width band when every day shows a break-only cell here.
                $isBand = true;
                foreach ($days as $day) {
                    $cell = $cellGrid[$day['key']][$i];
                    if ($cell['state'] === 'skip' || empty($cell['events'])) {
                        $isBand = false;
                        break;
                    }
                    foreach ($cell['events'] as $ev) {
                        if (empty($ev['isBreak'])) {
                            $isBand = false;
                            break 2;
                        }
                    }
                }

                $cells     = [];
                $bandTopic = null;
                foreach ($days as $day) {
                    $cells[$day['key']] = $cellGrid[$day['key']][$i];
                    if ($isBand && $bandTopic === null && !empty($cells[$day['key']]['events'])) {
                        $bandTopic = $cells[$day['key']]['events'][0]['topic'];
                    }
                }

                $rows[] = [
                    'from'      => $this->fmtMinutes($bs[$i]),
                    'to'        => $this->fmtMinutes($bs[$i + 1]),
                    'isBand'    => $isBand,
                    'bandTopic' => $bandTopic,
                    'cells'     => $cells,
                ];
            }

            // Append untimed (shift-based) sessions as a plain trailing row.
            $hasUntimed = false;
            foreach ($days as $day) {
                if (!empty($untimedByDay[$day['key']])) {
                    $hasUntimed = true;
                    break;
                }
            }
            if ($hasUntimed) {
                $cells = [];
                foreach ($days as $day) {
                    $cells[$day['key']] = [
                        'state'   => 'show',
                        'rowspan' => 1,
                        'isBreak' => false,
                        'events'  => $untimedByDay[$day['key']],
                    ];
                }
                $rows[] = [
                    'from'      => '',
                    'to'        => '',
                    'isBand'    => false,
                    'bandTopic' => null,
                    'cells'     => $cells,
                ];
            }

            // Determine programme-relative week number
            $weekNumber = $cursor->isoWeek;
            if ($course && !empty($course->start_year)) {
                $courseMonday = Carbon::parse($course->start_year)->startOfWeek(Carbon::MONDAY);
                $rel = intdiv((int) $courseMonday->diffInDays($cursor, false), 7) + 1;
                if ($rel >= 1) {
                    $weekNumber = $rel;
                }
            }

            $rangeLabel = $cursor->format('j M') . ' – ' . min($weekEnd, $rangeEnd)->format('j M Y');

            $weeks[] = [
                'weekNumber' => $weekNumber,
                'rangeLabel' => $rangeLabel,
                'days'       => $days,
                'rows'       => $rows,
            ];

            $cursor->addWeek();
        }

        return $weeks;
    }

    /** Convert a time-slot string to a sortable integer (minutes since midnight). */
    /** Parse a time part ("09:30 AM", "9:30", "01:00 PM") into minutes-of-day. */
    private function parseToMinutes(?string $time): ?int
    {
        $time = trim((string) $time);
        if ($time === '') {
            return null;
        }
        try {
            $c = Carbon::parse($time);
            return $c->hour * 60 + $c->minute;
        } catch (\Exception $e) {
            return null;
        }
    }

    /** Format minutes-of-day as "0930" (image style). */
    private function fmtMinutes(int $minutes): string
    {
        return sprintf('%02d%02d', intdiv($minutes, 60), $minutes % 60);
    }

    /** Break-band / cell label matching the printed timetable wording. */
    private function breakTopicLabel(?string $type): string
    {
        switch (strtolower((string) $type)) {
            case 'lunch':
                return 'Lunch';
            case 'snacks':
                return 'Snacks';
            case 'tea':
            default:
                return 'Tea Break';
        }
    }

    /** Resolve a timetable row's faculty_master JSON into a "Name, Name" string. */
    private function resolveEventFaculty($facultyMaster): string
    {
        $facultyIds = json_decode((string) $facultyMaster, true);
        if (!is_array($facultyIds)) {
            $facultyIds = !empty($facultyMaster) ? [$facultyMaster] : [];
        }
        if (!$facultyIds) {
            return '';
        }
        $ordered = implode(',', array_map('intval', $facultyIds));
        return DB::table('faculty_master')
            ->whereIn('pk', $facultyIds)
            ->orderByRaw("FIELD(pk, {$ordered})")
            ->pluck('full_name')
            ->implode(', ');
    }

    /**
     * Turn a timetable row's break fields into an interval (minutes) + label.
     * Returns null when the row has no usable break.
     */
    private function buildBreakInterval($row): ?array
    {
        $hasBreak = !empty($row->is_break ?? null) || !empty($row->break_type ?? null);
        if (!$hasBreak || empty($row->break_start_time) || empty($row->break_end_time)) {
            return null;
        }

        $start = $this->parseToMinutes($row->break_start_time);
        $end   = $this->parseToMinutes($row->break_end_time);
        if ($start === null || $end === null || $end <= $start) {
            return null;
        }

        return [
            'start' => $start,
            'end'   => $end,
            'topic' => $this->breakTopicLabel($row->break_type),
            'time'  => $this->fmtMinutes($start) . '-' . $this->fmtMinutes($end),
        ];
    }

    private function timetableSlotSortKey(string $slot): int
    {
        [$start] = $this->splitSessionTime($slot);
        if (!$start) {
            return PHP_INT_MAX;
        }
        // Try 12-hour format first
        if (preg_match('/(\d{1,2}):(\d{2})\s*(AM|PM)/i', $start, $m)) {
            $h = (int) $m[1];
            $min = (int) $m[2];
            if (strtoupper($m[3]) === 'PM' && $h !== 12) $h += 12;
            if (strtoupper($m[3]) === 'AM' && $h === 12) $h = 0;
            return $h * 60 + $min;
        }
        // 24-hour format
        if (preg_match('/(\d{1,2}):(\d{2})/', $start, $m)) {
            return (int) $m[1] * 60 + (int) $m[2];
        }
        return PHP_INT_MAX;
    }

    /** Split "09:00 AM - 05:00 PM" into ["09:00 AM", "05:00 PM"]. */
    /**
     * Extract the per-faculty feedback type (remark/rating/both/none) from
     * the faculty_details JSON for a specific faculty PK.
     * Falls back to 'both' for legacy events that have no faculty_details.
     */
    private function resolveFacultyFeedbackType(?string $facultyDetailsJson, int $facultyPk): string
    {
        if (empty($facultyDetailsJson)) {
            return 'both';
        }
        $details = json_decode($facultyDetailsJson, true);
        if (!is_array($details)) {
            return 'both';
        }
        foreach ($details as $d) {
            if (isset($d['faculty_pk']) && (int) $d['faculty_pk'] === $facultyPk) {
                return $d['feedback'] ?? 'both';
            }
        }
        return 'both';
    }

    private function splitSessionTime(string $slot): array
    {
        $parts = preg_split('/\s*[-–—]\s*/', trim($slot), 2);
        $from  = trim($parts[0] ?? $slot);
        $to    = trim($parts[1] ?? '');
        return [$from, $to];
    }

    /**
     * Roles allowed to edit the weekly info-sheet details (same as event authoring).
     */
    private function canEditWeeklyInfo(): bool
    {
        return hasRole('Training') || hasRole('Admin') || hasRole('Training-MCTP') || hasRole('IST') || hasRole('Training-Induction');
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

        $facultyMaster = json_decode($event->faculty_master, true);
        $event->faculty_master = is_array($facultyMaster)
            ? $facultyMaster
            : ($event->faculty_master ? [$event->faculty_master] : []);

        $event->internal_faculty = json_decode($event->internal_faculty, true) ?? [];
        $event->faculty_details  = json_decode($event->faculty_details, true)  ?? [];
        $event->Faculty_feedback = json_decode($event->Faculty_feedback, true)  ?? [];

        return response()->json($event);

        // return response()->json($event);
    }
    public function editEventPage($hash)
    {
        $id    = decrypt($hash);
        $event = CalendarEvent::findOrFail($id);
        $event->START_DATE = Carbon::parse($event->START_DATE)->format('Y-m-d');
        $event->END_DATE   = Carbon::parse($event->END_DATE)->format('Y-m-d');

        $fm = json_decode($event->faculty_master, true);
        $event->faculty_master   = is_array($fm) ? $fm : ($event->faculty_master ? [$event->faculty_master] : []);
        $event->internal_faculty = json_decode($event->internal_faculty, true) ?? [];
        $event->faculty_details  = json_decode($event->faculty_details,  true) ?? [];
        $event->Faculty_feedback = json_decode($event->Faculty_feedback,  true) ?? [];
        $event->group_name       = json_decode($event->group_name,        true) ?? [];

        $courseMaster = CourseMaster::where('course_master.active_inactive', 1)
            ->select('pk', 'course_name')
            ->get();

        $facultyMaster = FacultyMaster::where('active_inactive', 1)
            ->select('pk', 'faculty_type', 'full_name')
            ->orderBy('full_name')
            ->get();

        $subjects = SubjectModuleMaster::where('active_inactive', 1)
            ->select('pk', 'module_name')
            ->get();

        $venueMaster = VenueMaster::where('active_inactive', 1)
            ->select('venue_id', 'venue_name')
            ->orderBy('venue_name')
            ->get();

        $classSessionMaster = ClassSessionMaster::where('active_inactive', 1)
            ->select('pk', 'shift_name', 'shift_time', 'start_time', 'end_time')
            ->get();

        $sectors      = SectorMaster::query()->active()->get(['pk', 'sector_name']);
        $facultyRoles = ['Teaching', 'Sectional', 'Administration'];

        return view('admin.calendar.edit-event', compact(
            'event',
            'courseMaster',
            'facultyMaster',
            'subjects',
            'venueMaster',
            'classSessionMaster',
            'sectors',
            'facultyRoles'
        ));
    }

    public function update_event(Request $request, $hash)
    {
        abort_unless(hasRole('Training') || hasRole('Super Admin') || hasRole('Admin') || hasRole('Training MCTP Admin') || hasRole('Training IST') || hasRole('Training-Induction'), 403);
        $id = decrypt($hash);
        $validated = $request->validate([
            'Course_name'      => 'required|integer',
            'subject_name'     => 'required|integer',
            'subject_module'   => 'required|integer',
            'topic'            => 'nullable|string',
            'group_type'       => 'required|string',
            'type_names'       => 'required|array|min:1',
            'type_names.*'     => 'required|integer',
            'faculty'          => 'required|array|min:1',
            'faculty.*'              => 'required|integer',
            'faculty_type'           => 'nullable|integer',
            'faculty_row_type'       => 'nullable|array',
            'faculty_role'           => 'nullable|array',
            'faculty_feedback_remark' => 'nullable|array',
            'faculty_feedback_rating' => 'nullable|array',
            'sector'                 => 'nullable|integer',
            'vanue'            => 'required|integer',
            'shift'            => 'required_if:shift_type,1',
            'start_time'       => 'required_if:shift_type,2',
            'end_time'         => 'required_if:shift_type,2',
            'start_datetime'   => 'nullable|date',
            'end_datetime'     => 'nullable|date',
            'break_type'       => 'nullable|in:tea,lunch,snacks',
            'break_start_time' => 'nullable',
            'break_end_time'   => 'nullable',
        ], [
            'type_names.required' => 'The Group type names field is required.',
            'type_names.min'      => 'Please select at least one Group type name.',
            'faculty.required'    => 'The Faculty field is required.',
            'faculty.min'         => 'Please select at least one Faculty.',
        ]);

        $facultyDetails = $this->buildFacultyDetails($request);

        $event = CalendarEvent::findOrFail($id);
        $event->course_master_pk          = $request->Course_name;
        $event->subject_master_pk         = $request->subject_name;
        $event->subject_module_master_pk  = $request->subject_module;
        $event->subject_topic             = $request->topic;
        $event->course_group_type_master  = $request->group_type;
        $event->group_name                = json_encode(array_values($request->type_names ?? []));
        $event->faculty_master            = json_encode(array_values($request->input('faculty', [])));
        $event->faculty_type              = $request->faculty_type ?? ($facultyDetails[0]['faculty_type'] ?? null);
        $internalFaculty = $request->internal_faculty
            ?? collect($facultyDetails)->where('faculty_type', 1)->pluck('faculty_pk')->values()->all();
        $event->internal_faculty          = json_encode($internalFaculty ?? []);
        $event->faculty_details           = $facultyDetails ? json_encode($facultyDetails) : null;
        $event->sector_pk                 = $request->sector ?: null;
        $event->break_type                = $request->break_type ?: null;
        $event->break_start_time          = $request->break_start_time ?: null;
        $event->break_end_time            = $request->break_end_time ?: null;
        $event->is_break                  = ($request->break_type || $request->boolean('is_break')) ? 1 : 0;
        $event->venue_id                  = $request->vanue;
        $event->START_DATE                = Carbon::parse($request->start_datetime)->timezone('Asia/Kolkata')->format('Y-m-d');
        $event->END_DATE                  = Carbon::parse($request->start_datetime)->timezone('Asia/Kolkata')->format('Y-m-d');
        $event->session_type              = $request->shift_type;

        if ($request->shift_type == 1) {
            $event->full_day      = 0;
            $event->class_session = $request->shift;
        } else {
            $event->full_day      = $request->has('fullDayCheckbox') ? 1 : 0;
            $startTime = Carbon::parse($request->start_time)->format('h:i A');
            $endTime   = Carbon::parse($request->end_time)->format('h:i A');
            $event->class_session = $startTime . ' - ' . $endTime;
        }

        if ($facultyDetails) {
            $feedbacks = collect($facultyDetails)->pluck('feedback');
            $hasRemark = $feedbacks->contains('remark') || $feedbacks->contains('both');
            $hasRating = $feedbacks->contains('rating') || $feedbacks->contains('both');
            $event->feedback_checkbox = ($hasRemark || $hasRating) ? 1 : 0;
            $event->Ratting_checkbox  = $hasRating ? 1 : 0;
            $event->Remark_checkbox   = $hasRemark ? 1 : 0;
            $event->Faculty_feedback  = json_encode($facultyDetails);
        } else {
            $event->feedback_checkbox = $request->has('feedback_checkbox') ? 1 : 0;
            $event->Ratting_checkbox  = $request->has('ratingCheckbox') ? 1 : 0;
            $event->Remark_checkbox   = $request->has('remarkCheckbox') ? 1 : 0;
        }
        $event->Bio_attendance  = $request->boolean('bio_attendanceCheckbox') ? 1 : 0;
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

        $this->syncSupportingFacultyFeedback($event, $facultyDetails);

        return redirect()->route('calendar.index')->with('success', 'Event updated successfully.');
    }
    public function delete_event($id)
    {
        abort_unless(hasRole('Training') || hasRole('Super Admin') || hasRole('Admin') || hasRole('Training MCTP Admin') || hasRole('Training IST') || hasRole('Training-Induction'), 403);
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
                    't.faculty_details',
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
                // Only show Teaching-role faculty — strict filter.
                ->whereRaw("
                    JSON_VALID(t.faculty_details) = 1
                    AND JSON_CONTAINS(
                        t.faculty_details,
                        JSON_OBJECT('faculty_pk', f.pk, 'role', 'Teaching')
                    ) = 1
                ")
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
                    return $item->timetable_pk . '_' . $item->faculty_pk;
                })
                ->map(function ($item) {
                    $item->faculty_feedback_type = $this->resolveFacultyFeedbackType($item->faculty_details, $item->faculty_pk);
                    return $item;
                })
                ->values();

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
                    't.faculty_details',
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
                // Only show Teaching-role faculty at OT end — strict filter.
                ->whereRaw("
                    JSON_VALID(t.faculty_details) = 1
                    AND JSON_CONTAINS(
                        t.faculty_details,
                        JSON_OBJECT('faculty_pk', f.pk, 'role', 'Teaching')
                    ) = 1
                ")
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
                ->unique(fn($item) => $item->timetable_pk . '_' . $item->faculty_pk)
                ->map(function ($item) {
                    $item->faculty_feedback_type = $this->resolveFacultyFeedbackType($item->faculty_details, $item->faculty_pk);
                    return $item;
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

 /**
  * Student session-feedback listing, doubling as the SSO entry point.
  *
  * An external site (e.g. Moodle) redirects here with ?username=<user_name>.
  * On that first hop we log the user in, stash the username in the session,
  * then redirect back to this same route WITHOUT the query string so the
  * username never lingers in the address bar. The clean follow-up request is
  * authenticated (via the session) and renders the listing.
  *
  * SECURITY: the username arrives in plaintext, so anyone can impersonate any
  * user simply by editing the query string. This is intentional for now per
  * request. Before exposing this beyond a trusted/internal redirect, switch to
  * an encrypted token like studentFacultyFeedback() does.
  */
 public function studentFeedback_url(Request $request)
  {
        // SSO hop: ?username=<user_name> present → log in, stash, strip the query.
        if ($request->filled('username')) {
            $username = trim((string) $request->query('username'));

            $user = User::where('user_name', $username)->firstOrFail();
            Auth::login($user);

            // Keep the username available in the session for downstream use.
            session(['feedback_username' => $username]);

            // Redirect to the same route with no query string → clean URL.
            return redirect()->route('feedback.get.studentFeedbackUrl');
        }

        // Clean request: ensure we have an authenticated user. Fall back to the
        // username stashed on the SSO hop if the session somehow lost the login.
        if (!auth()->check() && ($stashed = session('feedback_username'))) {
            if ($user = User::where('user_name', trim($stashed))->first()) {
                Auth::login($user);
            }
        }

        if (!auth()->check()) {
            return redirect()->route('login');
        }

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

            return view('admin.feedback.student_feedback_url', compact(
                'pendingData',
                'submittedData',
                'otUrl'
            ));
        } catch (\Throwable $e) {
            logger()->error('Error in studentFeedback: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
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

    // =========================================================
    // FACULTY INTERNAL FEEDBACK
    // =========================================================

    public function facultyInternalFeedback(Request $request)
    {
        try {
            // Admin can pass ?faculty_pk=X to view any faculty's feedback
            $isAdminRole = hasRole('Super Admin') || hasRole('Admin') || hasRole('Training') || hasRole('Super-Admin');

            if ($request->filled('faculty_pk') && $isAdminRole) {
                $supporting_faculty_pk = (int) $request->faculty_pk;
            } else {
                $supporting_faculty_pk = get_auth_faculty_master_pk();
            }

            // Only true admins (with no faculty record) see all data; a faculty whose
            // pk lookup fails gets an empty result set, not everyone else's data.
            $isAdmin = $isAdminRole && !$supporting_faculty_pk;

            // ================= PENDING FEEDBACK =================
            $pendingQuery = DB::table('supporting_faculty_feedback as sff')
                ->select([
                    'sff.pk as feedback_pk',
                    'sff.timetable_pk',
                    'sff.main_faculty_master_pk',
                    'sff.supporting_faculty_master_pk',
                    'fm.full_name as main_faculty_name',
                    'sf.full_name as supporting_faculty_name',
                    'c.course_name',
                    'v.venue_name',
                    't.subject_topic',
                    't.class_session',
                    't.Ratting_checkbox',
                    't.Remark_checkbox',
                    't.faculty_details',
                    DB::raw('t.START_DATE as from_date'),
                ])
                ->join('timetable as t', 'sff.timetable_pk', '=', 't.pk')
                ->join('faculty_master as fm', 'sff.main_faculty_master_pk', '=', 'fm.pk')
                ->join('faculty_master as sf', 'sff.supporting_faculty_master_pk', '=', 'sf.pk')
                ->join('course_master as c', 'sff.course_master_pk', '=', 'c.pk')
                ->leftJoin('venue_master as v', 't.venue_id', '=', 'v.venue_id')
                ->where('sff.is_submitted', 0)
                ->where('sff.active_inactive', 1);

            if (!$isAdmin) {
                $pendingQuery->where('sff.supporting_faculty_master_pk', $supporting_faculty_pk)
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
            }

            $pendingData = $pendingQuery->orderBy('t.START_DATE', 'asc')->get()
                ->map(function ($item) {
                    $item->faculty_feedback_type = $this->resolveFacultyFeedbackType(
                        $item->faculty_details,
                        (int) $item->main_faculty_master_pk
                    );
                    return $item;
                });

            // ================= SUBMITTED FEEDBACK =================
            $submittedData = DB::table('supporting_faculty_feedback as sff')
                ->select([
                    'sff.pk as feedback_pk',
                    'sff.timetable_pk',
                    'sff.content',
                    'sff.presentation',
                    'sff.remark',
                    'sff.rating',
                    'sff.modified_date as submitted_date',
                    'sff.main_faculty_master_pk',
                    'fm.full_name as main_faculty_name',
                    'sf.full_name as supporting_faculty_name',
                    'c.course_name',
                    'v.venue_name',
                    't.subject_topic',
                    't.class_session',
                    't.Ratting_checkbox',
                    't.Remark_checkbox',
                    DB::raw('t.START_DATE as from_date'),
                ])
                ->join('timetable as t', 'sff.timetable_pk', '=', 't.pk')
                ->join('faculty_master as fm', 'sff.main_faculty_master_pk', '=', 'fm.pk')
                ->join('faculty_master as sf', 'sff.supporting_faculty_master_pk', '=', 'sf.pk')
                ->join('course_master as c', 'sff.course_master_pk', '=', 'c.pk')
                ->leftJoin('venue_master as v', 't.venue_id', '=', 'v.venue_id')
                ->where('sff.is_submitted', 1)
                ->when(!$isAdmin, fn($q) => $q->where('sff.supporting_faculty_master_pk', $supporting_faculty_pk))
                ->orderByDesc('sff.modified_date')
                ->get();

            return view(
                'admin.feedback.faculty_internal_feedback',
                compact('pendingData', 'submittedData', 'isAdmin')
            );
        } catch (\Throwable $e) {
            logger()->error('Error in facultyInternalFeedback: ' . $e->getMessage());
            return back()->with('error', 'Something went wrong');
        }
    }

    public function facultyPendingFeedbackCount()
    {
        try {
            $supporting_faculty_pk = get_auth_faculty_master_pk();
            if (!$supporting_faculty_pk) {
                return response()->json(['count' => 0, 'items' => []]);
            }

            $items = DB::table('supporting_faculty_feedback as sff')
                ->select([
                    'sff.pk as feedback_pk',
                    'fm.full_name as main_faculty_name',
                    't.subject_topic',
                    'c.course_name',
                    DB::raw('t.START_DATE as from_date'),
                    't.class_session',
                ])
                ->join('timetable as t', 'sff.timetable_pk', '=', 't.pk')
                ->join('faculty_master as fm', 'sff.main_faculty_master_pk', '=', 'fm.pk')
                ->join('course_master as c', 'sff.course_master_pk', '=', 'c.pk')
                ->where('sff.supporting_faculty_master_pk', $supporting_faculty_pk)
                ->where('sff.is_submitted', 0)
                ->where('sff.active_inactive', 1)
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
                ")
                ->orderBy('t.START_DATE', 'asc')
                ->get();

            return response()->json(['count' => $items->count(), 'items' => $items]);
        } catch (\Throwable $e) {
            return response()->json(['count' => 0, 'items' => []]);
        }
    }

    public function submitFacultyInternalFeedback(Request $request)
    {
        $request->validate([
            'feedback_pk' => 'required|array|min:1',
        ]);

        $supporting_faculty_pk = get_auth_faculty_master_pk();

        if (!$supporting_faculty_pk) {
            return back()->withErrors(['error' => 'Faculty record not found. Please log in as a faculty member.']);
        }
        $now = now();

        $indexes = $request->has('submit_index')
            ? [$request->submit_index]
            : array_keys($request->feedback_pk);

        $inserted = 0;
        $errors   = [];

        foreach ($indexes as $i) {
            $feedbackPk   = $request->feedback_pk[$i] ?? null;
            $content      = $request->content[$i] ?? null;
            $presentation = $request->presentation[$i] ?? null;
            $remarks      = $request->remarks[$i] ?? null;
            $ratingCb     = $request->Ratting_checkbox[$i] ?? 0;

            if (!$feedbackPk) {
                $errors[] = "Invalid feedback row at index $i";
                continue;
            }

            // On bulk submit skip rows the faculty left untouched
            $isBulk = !$request->has('submit_index');
            if ($isBulk && !$content && !$presentation && empty(trim((string) $remarks))) {
                continue;
            }

            if ($ratingCb == 1 && !$content && !$presentation) {
                $errors[] = 'Please provide content or presentation rating';
                continue;
            }

            // Verify the row belongs to this faculty and is still pending
            $row = DB::table('supporting_faculty_feedback')
                ->where('pk', $feedbackPk)
                ->where('supporting_faculty_master_pk', $supporting_faculty_pk)
                ->where('is_submitted', 0)
                ->where('active_inactive', 1)
                ->first();

            if (!$row) {
                $errors[] = 'Feedback already submitted or not found';
                continue;
            }

            $overallRating = null;
            if ($content && $presentation) {
                $overallRating = ($content + $presentation) / 2;
            } elseif ($content) {
                $overallRating = $content;
            } elseif ($presentation) {
                $overallRating = $presentation;
            }

            DB::table('supporting_faculty_feedback')
                ->where('pk', $feedbackPk)
                ->update([
                    'content'       => $content,
                    'presentation'  => $presentation,
                    'remark'        => $remarks,
                    'rating'        => $overallRating,
                    'is_submitted'  => 1,
                    'modified_date' => $now,
                ]);

            $inserted++;
        }

        $errorSummary = '';
        if (!empty($errors)) {
            $errorSummary = collect($errors)
                ->countBy()
                ->map(fn($count, $reason) => $count > 1 ? "$reason ($count)" : $reason)
                ->implode('; ');
        }

        if ($inserted === 0) {
            $msg = $errorSummary !== '' ? $errorSummary : 'Please submit at least one feedback.';
            return back()->withErrors(['error' => $msg]);
        }

        if (!empty($errors) && $inserted > 0) {
            return back()->with('success', "Successfully submitted $inserted feedback(s). " . count($errors) . " item(s) failed: $errorSummary");
        }

        return back()->with('success', 'Feedback submitted successfully.');
    }
}