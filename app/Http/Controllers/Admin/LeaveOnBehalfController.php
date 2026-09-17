<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use App\Models\LeaveApplication;
use App\Models\LeaveApplicationAttachment;
use App\Models\LeaveNatureMaster;
use App\Models\StudentMaster;
use App\Services\LeaveApplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Training Section — apply leave on behalf of an officer trainee.
 *
 * Same form as the officer trainee's own Apply Leave page, with the course and the
 * officer trainee chosen up front instead of derived from the logged-in account.
 *
 * Two deliberate differences from the officer-trainee flow, both because this page
 * records a leave the Course Coordinator has *already* approved offline:
 *   - the application is stored as Approved, so it never re-enters the faculty queue;
 *   - the same-day apply cutoff (PT timing) is not enforced and dates may be backdated
 *     to the start of the course's configured leave window, since the operator is
 *     regularising leave after the fact rather than requesting it.
 * Every other rule — configuration must exist, no overlapping leave, PT balance must
 * cover the request — is applied exactly as on the officer-trainee page.
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

    public function create()
    {
        return view('admin.leave.on_behalf.apply', [
            'courses' => $this->getCourses(),
            'natures' => $this->getNaturesByType(),
            'leaveType' => old('leave_type', LeaveApplication::TYPE_STATIONED_LEAVE),
        ]);
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
     * Per-student leave context for the chosen course: whether each leave type is
     * configured, the earliest date that configuration covers, and the PT balance.
     * The form uses it to set the date pickers and show the balance without a reload.
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

        $gender = $student->gender ?? null;
        $ptMinDate = $this->leaveService->earliestPtExemptionDate($coursePk, $gender);
        $stationedMinDate = $this->leaveService->earliestStationedLeaveDate($coursePk);

        return response()->json([
            'student' => [
                'name' => $this->studentName($student),
                'ot_code' => $student->generated_OT_code ?: '',
                'gender' => $gender,
            ],
            'pt_exemption' => [
                'configured' => $ptMinDate !== null,
                'min_date' => $ptMinDate,
                'message' => $this->ptUnavailableMessage($gender, $ptMinDate),
                'balance' => $this->leaveService->getPtBalance($studentPk, $coursePk, $gender),
            ],
            'stationed_leave' => [
                'configured' => $stationedMinDate !== null,
                'min_date' => $stationedMinDate,
                'message' => $stationedMinDate === null
                    ? 'Stationed leave is not configured for this course. Configure it under Stationed Leave Master first.'
                    : null,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $isStationed = $request->input('leave_type') === LeaveApplication::TYPE_STATIONED_LEAVE;

        $validated = $request->validate([
            'course_master_pk' => 'required|exists:course_master,pk',
            'student_master_pk' => 'required|exists:student_master,pk',
            'leave_type' => 'required|in:PT_EXEMPTION,STATIONED_LEAVE',
            'leave_nature_master_pk' => 'required|exists:leave_nature_master,pk',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            // Stationed leave records when the trainee leaves the station and
            // reports back; PT exemption runs for whole PT sessions and has none.
            'time_from' => [$isStationed ? 'required' : 'nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/'],
            'time_to' => [$isStationed ? 'required' : 'nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/'],
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
        $leaveType = $validated['leave_type'];

        $this->assertCourseAllowed($coursePk);

        if (! $this->studentBelongsToCourse($studentPk, $coursePk)) {
            return back()->withInput()->withErrors([
                'student_master_pk' => 'This officer trainee is not enrolled on the selected course.',
            ]);
        }

        $nature = LeaveNatureMaster::find($validated['leave_nature_master_pk']);
        if (! $nature || $nature->leave_type !== $leaveType) {
            return back()->withInput()->withErrors([
                'leave_nature_master_pk' => 'The selected nature does not belong to the chosen leave type.',
            ]);
        }

        // Only meaningful on a single-day leave — across days the return time is
        // naturally earlier in the day than the departure time.
        if ($isStationed
            && $validated['from_date'] === $validated['to_date']
            && ! empty($validated['time_from']) && ! empty($validated['time_to'])
            && $validated['time_to'] <= $validated['time_from']) {
            return back()->withInput()->withErrors([
                'time_to' => 'On a single-day leave, time to must be later than time from.',
            ]);
        }

        $student = StudentMaster::find($studentPk);
        $gender = $student->gender ?? null;

        // Configuration must already cover the leave start date — the same rule the
        // officer-trainee page applies, checked against from_date rather than today.
        if ($leaveType === LeaveApplication::TYPE_STATIONED_LEAVE
            && ! $this->leaveService->stationedLeaveConfigured($coursePk, $validated['from_date'])) {
            return back()->withInput()->withErrors([
                'from_date' => 'Stationed leave is not configured for this course on the selected start date. '
                    . 'Configure it under Stationed Leave Master first.',
            ]);
        }

        if ($leaveType === LeaveApplication::TYPE_PT_EXEMPTION
            && ! $this->leaveService->ptExemptionConfigured($coursePk, $gender, $validated['from_date'])) {
            return back()->withInput()->withErrors([
                'from_date' => $this->ptGenderProblem($gender)
                    ?? 'PT exemption is not configured for this course on the selected start date. '
                        . 'Check the effective-from date under PT Exemption Master.',
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

        if ($leaveType === LeaveApplication::TYPE_PT_EXEMPTION) {
            $balance = $this->leaveService->getPtBalance($studentPk, $coursePk, $gender);

            if ($totalDays > $balance['remaining']) {
                return back()->withInput()->withErrors([
                    'to_date' => 'Requested days exceed this officer trainee\'s remaining PT balance ('
                        . number_format($balance['remaining'], 1) . ' days).',
                ]);
            }
        }

        $now = now();

        $application = DB::transaction(function () use ($request, $validated, $coursePk, $studentPk, $leaveType, $totalDays, $now, $isStationed) {
            $application = LeaveApplication::create([
                'course_master_pk' => $coursePk,
                'student_master_pk' => $studentPk,
                'leave_type' => $leaveType,
                'leave_nature_master_pk' => $validated['leave_nature_master_pk'],
                'from_date' => $validated['from_date'],
                'to_date' => $validated['to_date'],
                'time_from' => $isStationed ? ($validated['time_from'] ?? null) : null,
                'time_to' => $isStationed ? ($validated['time_to'] ?? null) : null,
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

        return redirect()->route('admin.leave-on-behalf.create')->with(
            'success',
            $label . ' recorded and approved for ' . $name . ' — '
                . $application->from_date->format('d-m-Y') . ' to ' . $application->to_date->format('d-m-Y')
                . ' (' . number_format($totalDays, 0) . ' day' . ($totalDays == 1 ? '' : 's') . ').'
        );
    }

    /**
     * Active leave natures keyed by leave type, so the form can swap the Nature
     * options client-side when the leave type changes (no page reload needed here,
     * unlike the officer-trainee page where the type switch re-renders the page).
     */
    protected function getNaturesByType(): array
    {
        return LeaveNatureMaster::query()
            ->where('active_inactive', 1)
            ->orderBy('display_order')
            ->get()
            ->groupBy('leave_type')
            ->map(fn ($group) => $group->map(fn ($nature) => [
                'pk' => (int) $nature->pk,
                'name' => $nature->nature_name,
            ])->values()->all())
            ->all();
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
     * PT exemption is allocated per gender, so a student with no usable gender can
     * never match a configuration. That is a different problem from "not configured"
     * and needs a different fix, so it gets its own message.
     */
    protected function ptGenderProblem(?string $gender): ?string
    {
        $normalized = strtolower(trim((string) $gender));

        if (in_array($normalized, ['male', 'm', 'female', 'f'], true)) {
            return null;
        }

        return 'PT exemption is allocated by gender, and this officer trainee has no gender recorded. '
            . 'Update the student record first.';
    }

    /**
     * Why PT exemption is unavailable for this student, or null when it is available.
     */
    protected function ptUnavailableMessage(?string $gender, ?string $minDate): ?string
    {
        $genderProblem = $this->ptGenderProblem($gender);

        if ($genderProblem !== null) {
            return $genderProblem;
        }

        if ($minDate === null) {
            return 'PT exemption is not configured for this course and gender. '
                . 'Configure it under PT Exemption Master first.';
        }

        return null;
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
