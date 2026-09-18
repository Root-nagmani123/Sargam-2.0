<?php

namespace App\Http\Controllers\Admin;

use App\Exports\LbsnaaTableExport;
use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use App\Models\LeaveApplication;
use App\Models\LeaveApplicationAttachment;
use App\Models\LeaveNatureMaster;
use App\Models\StudentMaster;
use App\Services\LeaveApplicationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

/**
 * Training Section — apply leave on behalf of an officer trainee.
 *
 * Same form as the officer trainee's own Apply Leave page, with the course and the
 * officer trainee chosen up front instead of derived from the logged-in account.
 *
 * One leave type only, shown simply as "Leave" with no type picker. It is stored
 * as STATIONED_LEAVE so these applications sit alongside the officer trainee's own
 * everywhere else; what is separate is the Nature list, which comes from the LEAVE
 * bucket of the Nature Leave Master.
 *
 * Two deliberate differences from the officer-trainee flow, both because this page
 * records a leave the Course Coordinator has *already* approved offline:
 *   - the application is stored as Approved, so it never re-enters the faculty queue;
 *   - the same-day apply cutoff (PT timing) is not enforced and dates may be backdated
 *     to the start of the course's configured leave window, since the operator is
 *     regularising leave after the fact rather than requesting it.
 * Every other rule — configuration must exist, no overlapping leave — is applied
 * exactly as on the officer-trainee page.
 */
class LeaveOnBehalfController extends Controller
{
    public function __construct(protected LeaveApplicationService $leaveService)
    {
        $this->middleware(function ($request, $next) {
            // Checked here, not left to sidebar visibility: a route with no visible
            // menu entry is still reachable by any authenticated user in this app.
            if (! isTrainingSectionUser()) {
                abort(403, 'Only the training section can apply leave on behalf of officer trainees.');
            }

            return $next($request);
        });
    }

    /**
     * The register: every leave this page has recorded, newest first.
     *
     * Scoped to applied_by_user_pk — leave an officer trainee applied for
     * themselves belongs on their own pages, not in the Training Section's
     * record of what it entered.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->listDatatable($request);
        }

        return view('admin.leave.on_behalf.index', [
            'courses' => $this->filterCourses(),
        ]);
    }

    public function create()
    {
        return view('admin.leave.on_behalf.apply', [
            'courses' => $this->getCourses(),
            'natures' => $this->leaveNatures(),
        ]);
    }

    protected function baseListQuery(Request $request)
    {
        $courseIds = $this->getAllowedCourseIds();

        return LeaveApplication::query()
            ->with(['student', 'course', 'nature', 'appliedByUser'])
            ->whereNotNull('applied_by_user_pk')
            ->when($courseIds !== null, fn ($q) => $q->whereIn('course_master_pk', $courseIds ?: [-1]))
            ->when($request->filled('course_filter'), fn ($q) => $q->where('course_master_pk', (int) $request->input('course_filter')))
            ->when($request->filled('from_date'), fn ($q) => $q->whereDate('from_date', '>=', $request->input('from_date')))
            ->when($request->filled('to_date'), fn ($q) => $q->whereDate('from_date', '<=', $request->input('to_date')))
            ->orderByDesc('pk');
    }

    protected function listDatatable(Request $request)
    {
        return DataTables::of($this->baseListQuery($request))
            ->addIndexColumn()
            // Universal search: OT code / name, course, nature and reason — every
            // text column the grid actually shows.
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');
                if (empty($search)) {
                    return;
                }

                $query->where(function ($q) use ($search) {
                    $q->whereHas('student', function ($qs) use ($search) {
                        $qs->where('generated_OT_code', 'like', "%{$search}%")
                            ->orWhere('display_name', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                        ->orWhereHas('course', fn ($qc) => $qc->where('course_name', 'like', "%{$search}%"))
                        ->orWhereHas('nature', fn ($qn) => $qn->where('nature_name', 'like', "%{$search}%"))
                        ->orWhere('reason', 'like', "%{$search}%");
                });
            })
            ->addColumn('course_name', fn ($row) => e($row->course->course_name ?? '-'))
            ->addColumn('ot_code', fn ($row) => e($row->student->generated_OT_code ?: '-'))
            ->addColumn('ot_name', fn ($row) => e($this->studentName($row->student)))
            ->addColumn('nature_name', fn ($row) => e($row->nature->nature_name ?? '-'))
            ->addColumn('from_date_display', fn ($row) => $row->from_date?->format('d-m-Y') ?? '-')
            ->addColumn('to_date_display', fn ($row) => $row->to_date?->format('d-m-Y') ?? '-')
            ->addColumn('time_from_display', fn ($row) => e($row->time_from_display))
            ->addColumn('time_to_display', fn ($row) => e($row->time_to_display))
            ->addColumn('total_days_display', fn ($row) => number_format((float) $row->total_days, 0))
            ->addColumn('reason_text', fn ($row) => e(\Illuminate\Support\Str::limit($row->reason ?? '-', 80)))
            ->addColumn('recorded_by', fn ($row) => e($this->recordedByName($row)))
            ->addColumn('status_badge', fn ($row) => '<span class="badge rounded-1 leave-status leave-status--approved">'
                . e($row->status_label) . '</span>')
            ->rawColumns(['status_badge'])
            ->make(true);
    }

    /** Who entered the record — the operator's name, not the faculty approver. */
    protected function recordedByName(LeaveApplication $row): string
    {
        $actor = $row->appliedByUser;

        if (! $actor) {
            return 'Training Section';
        }

        return trim(implode(' ', array_filter([
            $actor->first_name ?? '',
            $actor->last_name ?? '',
        ]))) ?: ($actor->user_name ?? 'Training Section');
    }

    /** Courses that actually have a record here — the filter dropdown's options. */
    protected function filterCourses()
    {
        $courseIds = $this->getAllowedCourseIds();

        $ids = LeaveApplication::query()
            ->whereNotNull('applied_by_user_pk')
            ->when($courseIds !== null, fn ($q) => $q->whereIn('course_master_pk', $courseIds ?: [-1]))
            ->distinct()
            ->pluck('course_master_pk')
            ->all();

        if ($ids === []) {
            return collect();
        }

        return CourseMaster::whereIn('pk', $ids)->orderBy('course_name')->pluck('course_name', 'pk');
    }

    /**
     * Excel (.xlsx) or PDF of the register, honouring the same filters. Both
     * formats render the identical heading/row arrays, so the two downloads can
     * never disagree about what the list contained.
     */
    public function export(Request $request)
    {
        $rows = $this->baseListQuery($request)->get();

        $headings = ['S. No.', 'Course Name', 'OT Code', 'OT Name', 'Nature of Leave',
            'Date From', 'Date To', 'Time From', 'Time To', 'Total Days', 'Reason', 'Recorded By', 'Status'];

        $serial = 1;
        $data = $rows->map(fn ($row) => [
            $serial++,
            $row->course->course_name ?? '-',
            $row->student->generated_OT_code ?: '-',
            $this->studentName($row->student),
            $row->nature->nature_name ?? '-',
            $row->from_date?->format('d-m-Y') ?? '-',
            $row->to_date?->format('d-m-Y') ?? '-',
            $row->time_from_display,
            $row->time_to_display,
            number_format((float) $row->total_days, 0),
            $row->reason ?? '-',
            $this->recordedByName($row),
            $row->status_label,
        ])->values();

        $baseName = 'Leave_On_Behalf_' . now()->format('Ymd_His');
        // Serial, OT code, dates, times, day count and status centred; names,
        // course, nature, reason and recorder stay left-aligned.
        $centreColumns = [0, 2, 5, 6, 7, 8, 9, 12];
        $filterLine = $this->exportFilterLine($request);

        if (strtolower((string) $request->get('format')) === 'pdf') {
            @ini_set('memory_limit', '256M');
            @set_time_limit(120);

            return Pdf::loadView('admin.exports.table_pdf', [
                'headings' => $headings,
                'rows' => $data,
                'reportTitle' => 'Leave Applied on Behalf of OT',
                'filterLine' => $filterLine,
                'centreColumns' => $centreColumns,
            ])->setPaper('a4', 'landscape')->download($baseName . '.pdf');
        }

        return Excel::download(
            new LbsnaaTableExport($data, $headings, 'Leave Applied on Behalf of OT', $filterLine, $centreColumns, 'Leave On Behalf'),
            $baseName . '.xlsx'
        );
    }

    /** Filters in force, printed on the PDF so a shared copy says what it is. */
    protected function exportFilterLine(Request $request): string
    {
        $parts = [];

        if ($request->filled('course_filter')) {
            $courseName = CourseMaster::where('pk', (int) $request->input('course_filter'))->value('course_name');
            if ($courseName) {
                $parts[] = 'Course: ' . $courseName;
            }
        }

        if ($request->filled('from_date') || $request->filled('to_date')) {
            $parts[] = 'Period: ' . ($request->input('from_date') ?: '…') . ' to ' . ($request->input('to_date') ?: '…');
        }

        return implode(' | ', $parts);
    }

    /**
     * The Nature of Leave options: everything filed under "Leave" in the Nature
     * Leave Master. This page offers one leave type only, so there is one bucket
     * and no type switch.
     */
    protected function leaveNatures()
    {
        return LeaveNatureMaster::ofType(LeaveNatureMaster::TYPE_LEAVE)
            ->get(['pk', 'nature_name']);
    }

    /**
     * Officer trainees enrolled on a course — the searchable dropdown's options.
     * Uses the same enrollment map the leave module resolves a student's course from,
     * so anyone listed here can genuinely hold leave against this course.
     */
    public function students(Request $request)
    {
        $coursePk = (int) $request->query('course_master_pk');

        if (! $coursePk) {
            return response()->json(['students' => []]);
        }

        $this->assertCourseAllowed($coursePk);

        $students = DB::table('student_master_course__map as smcm')
            ->join('student_master as sm', 'sm.pk', '=', 'smcm.student_master_pk')
            ->where('smcm.course_master_pk', $coursePk)
            ->where('smcm.active_inactive', 1)
            ->where('sm.status', 1)
            ->select('sm.pk', 'sm.display_name', 'sm.first_name', 'sm.last_name', 'sm.generated_OT_code')
            ->distinct()
            ->orderBy('sm.display_name')
            ->get()
            ->map(fn ($row) => [
                'pk' => (int) $row->pk,
                'name' => $this->studentName($row),
                'ot_code' => $row->generated_OT_code ?: '',
            ])
            ->values();

        return response()->json(['students' => $students]);
    }

    /**
     * Per-student context for the chosen course: whether leave is configured and
     * the earliest date that configuration covers, so the form can set its date
     * pickers and warn without a reload.
     */
    public function context(Request $request)
    {
        $coursePk = (int) $request->query('course_master_pk');
        $studentPk = (int) $request->query('student_master_pk');

        if (! $coursePk || ! $studentPk) {
            return response()->json(['message' => 'Select a course and an officer trainee.'], 422);
        }

        $this->assertCourseAllowed($coursePk);

        $student = StudentMaster::find($studentPk);
        if (! $student || ! $this->studentBelongsToCourse($studentPk, $coursePk)) {
            return response()->json(['message' => 'This officer trainee is not enrolled on the selected course.'], 422);
        }

        $minDate = $this->leaveService->earliestStationedLeaveDate($coursePk);

        return response()->json([
            'student' => [
                'name' => $this->studentName($student),
                'ot_code' => $student->generated_OT_code ?: '',
            ],
            'leave' => [
                'configured' => $minDate !== null,
                'min_date' => $minDate,
                'message' => $minDate === null
                    ? 'Leave is not configured for this course. Configure it under Stationed Leave Master first.'
                    : null,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_master_pk' => 'required|exists:course_master,pk',
            'student_master_pk' => 'required|exists:student_master,pk',
            'leave_nature_master_pk' => 'required|exists:leave_nature_master,pk',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            // When the trainee leaves the station and when they report back.
            'time_from' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/'],
            'time_to' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/'],
            'reason' => 'required|string|max:2000',
            'contact_number' => ['required', 'string', 'regex:/^[6-9][0-9]{9}$/'],
            'attachments' => 'nullable|array',
            'attachments.*.title' => 'nullable|string|max:200',
            'attachments.*.file' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx',
        ], [
            'course_master_pk.required' => 'Please select a course.',
            'student_master_pk.required' => 'Please select an officer trainee.',
            'to_date.after_or_equal' => 'End date cannot be before the start date. Please update the end date.',
            'time_from.required' => 'Please enter the time the officer trainee leaves the station.',
            'time_to.required' => 'Please enter the time the officer trainee reports back.',
            'time_from.regex' => 'Enter a valid time from.',
            'time_to.regex' => 'Enter a valid time to.',
            'contact_number.regex' => 'Contact number must be a valid 10-digit mobile number starting with 6, 7, 8, or 9.',
            'attachments.*.file.max' => 'Each attachment must not exceed 5 MB.',
            'attachments.*.file.mimes' => 'Allowed file types: PDF, JPG, JPEG, PNG, DOC, DOCX.',
        ]);

        $coursePk = (int) $validated['course_master_pk'];
        $studentPk = (int) $validated['student_master_pk'];

        // One leave type on this page, shown simply as "Leave". It is stored as
        // STATIONED_LEAVE so these applications sit alongside the officer
        // trainee's own in My Leave and the Leave Approval history; only the
        // Nature list is separate (the LEAVE bucket of the Nature Leave Master).
        $leaveType = LeaveApplication::TYPE_STATIONED_LEAVE;

        $this->assertCourseAllowed($coursePk);

        if (! $this->studentBelongsToCourse($studentPk, $coursePk)) {
            return back()->withInput()->withErrors([
                'student_master_pk' => 'This officer trainee is not enrolled on the selected course.',
            ]);
        }

        $nature = LeaveNatureMaster::find($validated['leave_nature_master_pk']);
        if (! $nature || $nature->leave_type !== LeaveNatureMaster::TYPE_LEAVE) {
            return back()->withInput()->withErrors([
                'leave_nature_master_pk' => 'Select a nature from the Leave list. '
                    . 'Natures are maintained under Nature Leave Master.',
            ]);
        }

        // Only meaningful on a single-day leave — across days the return time is
        // naturally earlier in the day than the departure time.
        if ($validated['from_date'] === $validated['to_date']
            && $validated['time_to'] <= $validated['time_from']) {
            return back()->withInput()->withErrors([
                'time_to' => 'On a single-day leave, time to must be later than time from.',
            ]);
        }

        $student = StudentMaster::find($studentPk);

        // Configuration must already cover the leave start date — the same rule the
        // officer-trainee page applies, checked against from_date rather than today.
        if (! $this->leaveService->stationedLeaveConfigured($coursePk, $validated['from_date'])) {
            return back()->withInput()->withErrors([
                'from_date' => 'Leave is not configured for this course on the selected start date. '
                    . 'Configure it under Stationed Leave Master first.',
            ]);
        }

        $totalDays = $this->leaveService->calculateTotalDays($validated['from_date'], $validated['to_date']);

        try {
            $this->leaveService->assertNoOverlap(
                $studentPk,
                $validated['from_date'],
                $validated['to_date'],
                null,
                $leaveType
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['from_date' => $e->getMessage()]);
        }

        $now = now();

        $application = DB::transaction(function () use ($request, $validated, $coursePk, $studentPk, $leaveType, $totalDays, $now) {
            $application = LeaveApplication::create([
                'course_master_pk' => $coursePk,
                'student_master_pk' => $studentPk,
                'leave_type' => $leaveType,
                'leave_nature_master_pk' => $validated['leave_nature_master_pk'],
                'from_date' => $validated['from_date'],
                'to_date' => $validated['to_date'],
                'time_from' => $validated['time_from'],
                'time_to' => $validated['time_to'],
                'total_days' => $totalDays,
                'reason' => $validated['reason'],
                'contact_number' => $validated['contact_number'],
                // Approved on entry: the Course Coordinator has already approved this
                // leave, so it must not queue for faculty approval a second time.
                // approved_by_faculty_pk stays null — the actor is an employee, not
                // faculty; applied_by_user_pk carries who recorded it.
                'status' => LeaveApplication::STATUS_APPROVED,
                'submitted_at' => $now,
                'approved_by_faculty_pk' => null,
                'approved_at' => $now,
                'rejection_remarks' => null,
                'applied_by_user_pk' => (int) Auth::user()->pk,
                'active_inactive' => 1,
                'created_date' => $now,
                'modified_date' => $now,
            ]);

            foreach (array_keys($request->input('attachments', [])) as $index) {
                $file = $request->file("attachments.$index.file");
                if (! $file) {
                    continue;
                }

                LeaveApplicationAttachment::create([
                    'leave_application_pk' => $application->pk,
                    'attachment_title' => $request->input("attachments.$index.title") ?: 'Attachment',
                    'file_path' => $file->store('uploads/leave-applications', 'public'),
                    'original_file_name' => $file->getClientOriginalName(),
                    'created_date' => $now,
                ]);
            }

            return $application;
        });

        $label = $application->leave_type_label;
        $name = $this->studentName($student);

        return redirect()->route('admin.leave-on-behalf.index')->with(
            'success',
            $label . ' recorded and approved for ' . $name . ' — '
                . $application->from_date->format('d-m-Y') . ' to ' . $application->to_date->format('d-m-Y')
                . ' (' . number_format($totalDays, 0) . ' day' . ($totalDays == 1 ? '' : 's') . ').'
        );
    }


    protected function studentName($student): string
    {
        $display = trim((string) ($student->display_name ?? ''));
        if ($display !== '') {
            return $display;
        }

        return trim(implode(' ', array_filter([
            $student->first_name ?? '',
            $student->last_name ?? '',
        ]))) ?: 'Officer Trainee';
    }

    protected function studentBelongsToCourse(int $studentPk, int $coursePk): bool
    {
        return DB::table('student_master_course__map')
            ->where('student_master_pk', $studentPk)
            ->where('course_master_pk', $coursePk)
            ->where('active_inactive', 1)
            ->exists();
    }

    /**
     * Running courses, scoped the same way as Stationed Leave Master so an operator
     * only sees the courses they administer.
     */
    protected function getCourses()
    {
        $courseIds = $this->getAllowedCourseIds();

        $query = CourseMaster::query()
            ->where('active_inactive', 1)
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', now()->toDateString());
            })
            ->orderBy('course_name');

        if ($courseIds !== null) {
            $query->whereIn('pk', $courseIds);
        }

        return $query->get(['pk', 'course_name', 'couse_short_name']);
    }

    protected function getAllowedCourseIds(): ?array
    {
        if (isTrainingOrEstateAuthority()) {
            return null;
        }

        $courseIds = get_Role_by_course();

        if (empty($courseIds) || $courseIds === [-1]) {
            return [-1];
        }

        return $courseIds;
    }

    protected function assertCourseAllowed(int $coursePk): void
    {
        $courseIds = $this->getAllowedCourseIds();

        if ($courseIds !== null && ! in_array($coursePk, array_map('intval', $courseIds), true)) {
            abort(403, 'You are not authorized for this course.');
        }
    }
}
