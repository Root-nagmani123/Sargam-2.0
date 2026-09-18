<?php

namespace App\Http\Controllers\Admin;

use App\Exports\LbsnaaTableExport;
use App\Http\Controllers\Controller;
use App\Models\LeaveApplication;
use App\Models\LeaveApplicationAttachment;
use App\Models\LeaveNatureMaster;
use App\Services\FacultyLeaveApprovalService;
use App\Services\LeaveApplicationService;
use App\Services\NotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class LeaveApplicationController extends Controller
{
    public function __construct(protected LeaveApplicationService $leaveService)
    {
        $this->middleware(function ($request, $next) {
            if (! isOfficerTraineeUser()) {
                abort(403, 'Only officer trainees can access leave applications.');
            }

            return $next($request);
        });
    }

    public function apply(Request $request)
    {
        $context = $this->leaveService->resolveStudentContext((int) Auth::user()->pk);
        $leaveType = $request->query('leave_type', LeaveApplication::TYPE_PT_EXEMPTION);
        $natures = $this->getNatures($leaveType);
        $ptBalance = $this->leaveService->getPtBalance(
            $context['student_pk'],
            $context['course_pk'],
            $context['student']->gender ?? null
        );

        return view('admin.leave.apply', array_merge($context, $this->leaveFormViewData($context, $leaveType, $natures, $ptBalance, null, false)));
    }

    public function store(Request $request)
    {
        return $this->saveApplication($request);
    }

    public function myLeave(Request $request)
    {
        if ($request->ajax()) {
            return $this->myLeaveDatatable($request);
        }

        return view('admin.leave.my_leave');
    }

    public function balance()
    {
        $context = $this->leaveService->resolveStudentContext((int) Auth::user()->pk);
        $ptBalance = $this->leaveService->getPtBalance(
            $context['student_pk'],
            $context['course_pk'],
            $context['student']->gender ?? null
        );

        return view('admin.leave.balance', array_merge($context, compact('ptBalance')));
    }

    public function edit($id)
    {
        $context = $this->leaveService->resolveStudentContext((int) Auth::user()->pk);
        $application = $this->findOwnedApplication($context['student_pk'], $id);

        if (! in_array((int) $application->status, [LeaveApplication::STATUS_DRAFT, LeaveApplication::STATUS_PENDING], true)) {
            return redirect()->route('leave.view', $id)->with('error', 'Only draft or pending applications can be edited.');
        }

        $leaveType = $application->leave_type;
        $natures = $this->getNatures($leaveType);
        $ptBalance = $this->leaveService->getPtBalance(
            $context['student_pk'],
            $context['course_pk'],
            $context['student']->gender ?? null
        );

        return view('admin.leave.apply', array_merge($context, $this->leaveFormViewData($context, $leaveType, $natures, $ptBalance, $application->load('attachments'), false)));
    }

    public function update(Request $request, $id)
    {
        $context = $this->leaveService->resolveStudentContext((int) Auth::user()->pk);
        $application = $this->findOwnedApplication($context['student_pk'], $id);

        if (! in_array((int) $application->status, [LeaveApplication::STATUS_DRAFT, LeaveApplication::STATUS_PENDING], true)) {
            abort(403, 'This application cannot be edited.');
        }

        return $this->saveApplication($request, $application);
    }

    public function view($id)
    {
        $context = $this->leaveService->resolveStudentContext((int) Auth::user()->pk);
        $application = $this->findOwnedApplication($context['student_pk'], $id)
            ->load(['attachments', 'approvedByFaculty']);
        $leaveType = $application->leave_type;
        $natures = $this->getNatures($leaveType);
        $ptBalance = $this->leaveService->getPtBalance(
            $context['student_pk'],
            $context['course_pk'],
            $context['student']->gender ?? null
        );

        return view('admin.leave.apply', array_merge($context, $this->leaveFormViewData($context, $leaveType, $natures, $ptBalance, $application, true)));
    }

    public function destroy($id)
    {
        $context = $this->leaveService->resolveStudentContext((int) Auth::user()->pk);
        $application = $this->findOwnedApplication($context['student_pk'], $id);

        if (! in_array((int) $application->status, [LeaveApplication::STATUS_DRAFT, LeaveApplication::STATUS_PENDING], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft or pending applications can be deleted.',
            ], 422);
        }

        DB::transaction(function () use ($application) {
            LeaveApplicationAttachment::where('leave_application_pk', $application->pk)->delete();
            $application->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Leave application deleted successfully.',
        ]);
    }

    protected function saveApplication(Request $request, ?LeaveApplication $application = null)
    {
        $context = $this->leaveService->resolveStudentContext((int) Auth::user()->pk);
        $isStationed = $request->input('leave_type') === LeaveApplication::TYPE_STATIONED_LEAVE;

        $validated = $request->validate([
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
            'submit_action' => 'required|in:draft,submit',
            'attachments' => 'nullable|array',
            'attachments.*.title' => 'nullable|string|max:200',
            'attachments.*.file' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx',
            'existing_attachments' => 'nullable|array',
            'existing_attachments.*' => 'integer',
        ], [
            'to_date.after_or_equal' => 'End date cannot be before the start date. Please update the end date.',
            'time_from.required' => 'Please enter the time you leave the station.',
            'time_to.required' => 'Please enter the time you report back.',
            'time_from.regex' => 'Enter a valid time from.',
            'time_to.regex' => 'Enter a valid time to.',
            'contact_number.regex' => 'Contact number must be a valid 10-digit mobile number starting with 6, 7, 8, or 9.',
            'attachments.*.file.max' => 'Each attachment must not exceed 5 MB.',
            'attachments.*.file.mimes' => 'Allowed file types: PDF, JPG, JPEG, PNG, DOC, DOCX.',
        ]);

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

        if ($validated['leave_type'] === LeaveApplication::TYPE_STATIONED_LEAVE
            && ! $this->leaveService->stationedLeaveConfigured($context['course_pk'], $validated['from_date'])) {
            $courseName = $context['course']->course_name ?? 'your course';
            $upcoming = $this->leaveService->getUpcomingStationedLeaveConfig(
                $context['course_pk'],
                $validated['from_date']
            );
            $message = $upcoming
                ? 'Stationed leave for ' . $courseName . ' is available from '
                    . $upcoming->effective_from->format('d-m-Y') . ' onwards. Please choose a start date on or after that date.'
                : 'Stationed leave is not configured for your course (' . $courseName . ').';

            return back()->withInput()->withErrors([
                'from_date' => $message,
            ]);
        }

        if ($validated['leave_type'] === LeaveApplication::TYPE_PT_EXEMPTION
            && ! $this->leaveService->ptExemptionConfigured(
                $context['course_pk'],
                $context['student']->gender ?? null,
                $validated['from_date']
            )) {
            $courseName = $context['course']->course_name ?? 'your course';
            $upcoming = $this->leaveService->getUpcomingPtExemptionConfig(
                $context['course_pk'],
                $context['student']->gender ?? null,
                $validated['from_date']
            );
            $message = $upcoming
                ? 'PT exemption for ' . $courseName . ' is available from '
                    . $upcoming->effective_from->format('d-m-Y') . ' onwards. Please choose a start date on or after that date.'
                : 'PT exemption is not configured for your course (' . $courseName . ').';

            return back()->withInput()->withErrors([
                'from_date' => $message,
            ]);
        }

        if ($validated['leave_type'] === LeaveApplication::TYPE_PT_EXEMPTION) {
            $ptConfig = $this->leaveService->getActivePtExemptionConfig(
                $context['course_pk'],
                $context['student']->gender ?? null,
                $validated['from_date']
            );

            if ($ptConfig && ! $this->leaveService->isLeaveStartDateAllowedForApply(
                $ptConfig->apply_cutoff_time,
                $validated['from_date']
            )) {
                return back()->withInput()->withErrors([
                    'from_date' => $this->leaveService->applyCutoffErrorMessage(
                        'PT exemption',
                        $ptConfig->apply_cutoff_time
                    ),
                ]);
            }
        }

        if ($validated['leave_type'] === LeaveApplication::TYPE_STATIONED_LEAVE) {
            $stationedConfig = $this->leaveService->getActiveStationedLeaveConfig(
                $context['course_pk'],
                $validated['from_date']
            );

            if ($stationedConfig && ! $this->leaveService->isLeaveStartDateAllowedForApply(
                $stationedConfig->apply_cutoff_time,
                $validated['from_date']
            )) {
                return back()->withInput()->withErrors([
                    'from_date' => $this->leaveService->applyCutoffErrorMessage(
                        'stationed leave',
                        $stationedConfig->apply_cutoff_time
                    ),
                ]);
            }
        }

        $totalDays = $this->leaveService->calculateTotalDays($validated['from_date'], $validated['to_date']);

        try {
            $this->leaveService->assertNoOverlap(
                $context['student_pk'],
                $validated['from_date'],
                $validated['to_date'],
                $application?->pk,
                $validated['leave_type']
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['from_date' => $e->getMessage()]);
        }

        if ($validated['leave_type'] === LeaveApplication::TYPE_PT_EXEMPTION && $validated['submit_action'] === 'submit') {
            $balance = $this->leaveService->getPtBalance(
                $context['student_pk'],
                $context['course_pk'],
                $context['student']->gender ?? null
            );

            if ($totalDays > $balance['remaining']) {
                return back()->withInput()->withErrors([
                    'to_date' => 'Requested days exceed your remaining PT balance (' . number_format($balance['remaining'], 1) . ' days).',
                ]);
            }
        }

        $isSubmit = $validated['submit_action'] === 'submit';
        $autoApprovePt = $isSubmit && $validated['leave_type'] === LeaveApplication::TYPE_PT_EXEMPTION;
        $autoApproveStationed = $isSubmit
            && $validated['leave_type'] === LeaveApplication::TYPE_STATIONED_LEAVE
            && ! $this->leaveService->stationedLeaveRequiresFacultyApproval(
                $context['course_pk'],
                $validated['from_date']
            );
        $autoApprove = $autoApprovePt || $autoApproveStationed;

        if ($isSubmit) {
            $status = $autoApprove
                ? LeaveApplication::STATUS_APPROVED
                : LeaveApplication::STATUS_PENDING;
        } else {
            $status = LeaveApplication::STATUS_DRAFT;
        }

        $now = now();
        $isNew = $application === null;

        $application = DB::transaction(function () use ($validated, $context, $application, $totalDays, $status, $now, $request, $isSubmit, $autoApprove, $isStationed) {
            $data = [
                'course_master_pk' => $context['course_pk'],
                'student_master_pk' => $context['student_pk'],
                'leave_type' => $validated['leave_type'],
                'leave_nature_master_pk' => $validated['leave_nature_master_pk'],
                'from_date' => $validated['from_date'],
                'to_date' => $validated['to_date'],
                // Cleared on PT exemption so switching an application's type never
                // leaves a stale departure/return time behind.
                'time_from' => $isStationed ? ($validated['time_from'] ?? null) : null,
                'time_to' => $isStationed ? ($validated['time_to'] ?? null) : null,
                'total_days' => $totalDays,
                'reason' => $validated['reason'],
                'contact_number' => $validated['contact_number'] ?? null,
                'status' => $status,
                'submitted_at' => $isSubmit ? $now : null,
                'approved_at' => $autoApprove ? $now : ($isSubmit ? null : $application?->approved_at),
                'approved_by_faculty_pk' => $autoApprove ? null : ($isSubmit ? null : $application?->approved_by_faculty_pk),
                'rejection_remarks' => $autoApprove ? null : ($isSubmit ? null : $application?->rejection_remarks),
                'modified_date' => $now,
            ];

            if ($application) {
                $application->update($data);
            } else {
                $application = LeaveApplication::create(array_merge($data, [
                    'active_inactive' => 1,
                    'created_date' => $now,
                ]));
            }

            $keepIds = collect($request->input('existing_attachments', []))->map(fn ($id) => (int) $id)->filter()->all();
            LeaveApplicationAttachment::where('leave_application_pk', $application->pk)
                ->when(! empty($keepIds), fn ($q) => $q->whereNotIn('pk', $keepIds))
                ->when(empty($keepIds), fn ($q) => $q)
                ->delete();

            foreach ($request->input('attachments', []) as $index => $attachmentRow) {
                $file = $request->file("attachments.$index.file");
                if (! $file) {
                    continue;
                }

                $path = $file->store('uploads/leave-applications', 'public');

                LeaveApplicationAttachment::create([
                    'leave_application_pk' => $application->pk,
                    'attachment_title' => $attachmentRow['title'] ?? 'Attachment',
                    'file_path' => $path,
                    'original_file_name' => $file->getClientOriginalName(),
                    'created_date' => $now,
                ]);
            }

            return $application;
        });

        // Notify stationed-leave approvers when a request is submitted for their review.
        // PT exemptions and auto-approved stationed leave are not notified.
        if ($isSubmit && ! $autoApprove
            && $validated['leave_type'] === LeaveApplication::TYPE_STATIONED_LEAVE) {
            $this->notifyApproversOfNewLeaveRequest($application, $context, $totalDays);
        }

        $message = match (true) {
            ! $isSubmit => 'Leave application saved as draft.',
            $autoApprovePt => 'PT exemption application submitted and approved successfully.',
            $autoApproveStationed => 'Stationed leave application submitted and approved successfully.',
            default => 'Leave application submitted successfully. Awaiting faculty approval.',
        };

        return redirect()->route('leave.my-leave')->with('success', $message);
    }

    /**
     * Notify the faculty assigned as stationed-leave approvers for the student's
     * course that a new leave request is awaiting their review.
     */
    protected function notifyApproversOfNewLeaveRequest(LeaveApplication $application, array $context, float $totalDays): void
    {
        try {
            $approverUserIds = app(FacultyLeaveApprovalService::class)
                ->getApproverUserIdsForCourse((int) $context['course_pk']);

            if ($approverUserIds === []) {
                return;
            }

            $studentName = app(FacultyLeaveApprovalService::class)
                ->studentDisplayName($context['student'] ?? null);
            $courseName = $context['course']->course_name ?? 'their course';
            $fromDate = optional($application->from_date)->format('d-m-Y') ?? $application->from_date;
            $toDate = optional($application->to_date)->format('d-m-Y') ?? $application->to_date;
            $days = number_format($totalDays, 0);

            $title = 'New Leave Request';
            $message = "{$studentName} has submitted a Stationed Leave request for {$courseName} "
                . "from {$fromDate} to {$toDate} ({$days} day" . ($days === '1' ? '' : 's') . "). "
                . 'Awaiting your approval.';

            app(NotificationService::class)->createMultiple(
                $approverUserIds,
                'leave',
                'StationedLeave',
                (int) $application->pk,
                $title,
                $message
            );
        } catch (\Throwable $e) {
            // Notification failure must never block leave submission.
            Log::error('Failed to notify approvers of new leave request', [
                'leave_pk' => $application->pk ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function baseMyLeaveQuery(Request $request)
    {
        $context = $this->leaveService->resolveStudentContext((int) Auth::user()->pk);

        $query = LeaveApplication::with('nature')
            ->where('student_master_pk', $context['student_pk'])
            ->orderByDesc('pk');

        if ($request->filled('leave_type')) {
            $query->where('leave_type', $request->leave_type);
        }

        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', (int) $request->status);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('from_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('from_date', '<=', $request->input('to_date'));
        }

        return $query;
    }

    protected function myLeaveDatatable(Request $request)
    {
        return DataTables::of($this->baseMyLeaveQuery($request))
            ->addIndexColumn()
            ->addColumn('leave_type_label', fn ($row) => $row->leave_type_label)
            ->addColumn('from_date_display', fn ($row) => $row->from_date?->format('d-m-Y') ?? '-')
            ->addColumn('to_date_display', fn ($row) => $row->to_date?->format('d-m-Y') ?? '-')
            ->addColumn('time_from_display', fn ($row) => e($row->time_from_display))
            ->addColumn('time_to_display', fn ($row) => e($row->time_to_display))
            ->addColumn('total_days_display', fn ($row) => number_format((float) $row->total_days, 1))
            ->addColumn('status_badge', function ($row) {
                $map = [
                    LeaveApplication::STATUS_DRAFT => ['Draft', 'draft'],
                    LeaveApplication::STATUS_PENDING => ['Pending', 'pending'],
                    LeaveApplication::STATUS_APPROVED => ['Approved', 'approved'],
                    LeaveApplication::STATUS_REJECTED => ['Rejected', 'rejected'],
                ];
                [$label, $variant] = $map[(int) $row->status] ?? [$row->status_label, 'draft'];

                return '<span class="badge rounded-1 leave-status leave-status--' . $variant . '">' . e($label) . '</span>';
            })
            ->addColumn('action', function ($row) {
                $html = '<div class="d-inline-flex align-items-center justify-content-center gap-2 programme-action-group">';

                if (in_array((int) $row->status, [LeaveApplication::STATUS_DRAFT, LeaveApplication::STATUS_PENDING], true)) {
                    $html .= '<a href="' . route('leave.edit', $row->pk) . '" class="programme-action-btn" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>';
                    $html .= '<a href="javascript:void(0)" class="programme-action-btn programme-action-btn--danger leave-delete-btn" data-id="' . $row->pk . '" title="Delete" aria-label="Delete"><i class="bi bi-trash3"></i></a>';
                } else {
                    $html .= '<a href="' . route('leave.view', $row->pk) . '" class="programme-action-btn" title="View" aria-label="View"><i class="bi bi-eye"></i></a>';
                }

                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
    }

    /**
     * Excel (.xlsx) or PDF of the officer trainee's own leave, honouring the same
     * filters. Both formats render the identical heading/row arrays, so the two
     * downloads can never disagree about what the list contained.
     */
    public function myLeaveExport(Request $request)
    {
        $rows = $this->baseMyLeaveQuery($request)->with('course')->get();

        $headings = ['S. No.', 'Course Name', 'Leave Type', 'Nature', 'From Date', 'To Date', 'Time From', 'Time To', 'Total Days', 'Status'];

        $serial = 1;
        $data = $rows->map(fn ($row) => [
            $serial++,
            $row->course->course_name ?? '-',
            $row->leave_type_label,
            $row->nature->nature_name ?? '-',
            $row->from_date?->format('d-m-Y') ?? '-',
            $row->to_date?->format('d-m-Y') ?? '-',
            $row->time_from_display,
            $row->time_to_display,
            number_format((float) $row->total_days, 1),
            $row->status_label,
        ])->values();

        $baseName = 'My_Leave_Applications_' . now()->format('Ymd_His');
        // Serial, dates, times, day count and status centred; course, type,
        // nature stay left-aligned.
        $centreColumns = [0, 4, 5, 6, 7, 8, 9];
        $filterLine = $this->myLeaveFilterLine($request);

        if (strtolower((string) $request->get('format')) === 'pdf') {
            @ini_set('memory_limit', '256M');
            @set_time_limit(120);

            $pdf = Pdf::loadView('admin.exports.table_pdf', [
                'headings' => $headings,
                'rows' => $data,
                'reportTitle' => 'My Leave Applications',
                'filterLine' => $filterLine,
                'centreColumns' => $centreColumns,
            ])->setPaper('a4', 'landscape');

            return $pdf->download($baseName . '.pdf');
        }

        return Excel::download(
            new LbsnaaTableExport($data, $headings, 'My Leave Applications', $filterLine, $centreColumns, 'My Leave'),
            $baseName . '.xlsx'
        );
    }

    /**
     * Human-readable summary of the filters in force, printed on the PDF so a
     * shared copy says what it is a report of.
     */
    protected function myLeaveFilterLine(Request $request): string
    {
        $parts = [];

        if ($request->filled('leave_type')) {
            $parts[] = 'Leave Type: ' . match ($request->input('leave_type')) {
                LeaveApplication::TYPE_PT_EXEMPTION => 'PT Exemption',
                LeaveApplication::TYPE_STATIONED_LEAVE => 'Stationed Leave',
                default => $request->input('leave_type'),
            };
        }

        if ($request->filled('status') && $request->input('status') !== '') {
            $labels = [
                LeaveApplication::STATUS_DRAFT => 'Draft',
                LeaveApplication::STATUS_PENDING => 'Pending',
                LeaveApplication::STATUS_APPROVED => 'Approved',
                LeaveApplication::STATUS_REJECTED => 'Rejected',
            ];
            $parts[] = 'Status: ' . ($labels[(int) $request->input('status')] ?? 'All');
        }

        if ($request->filled('from_date') || $request->filled('to_date')) {
            $parts[] = 'Period: ' . ($request->input('from_date') ?: '…') . ' to ' . ($request->input('to_date') ?: '…');
        }

        return implode(' | ', $parts);
    }

    protected function findOwnedApplication(int $studentPk, $id): LeaveApplication
    {
        return LeaveApplication::where('student_master_pk', $studentPk)->findOrFail($id);
    }

    protected function getNatures(string $leaveType)
    {
        return LeaveNatureMaster::query()
            ->where('leave_type', $leaveType)
            ->where('active_inactive', 1)
            ->orderBy('display_order')
            ->get();
    }

    protected function leaveFormViewData(
        array $context,
        string $leaveType,
        $natures,
        array $ptBalance,
        ?LeaveApplication $application,
        bool $readOnly
    ): array {
        $gender = $context['student']->gender ?? null;
        $activePt = $this->leaveService->getActivePtExemptionConfig($context['course_pk'], $gender);
        $upcomingPt = $this->leaveService->getUpcomingPtExemptionConfig($context['course_pk'], $gender);
        $activeStationed = $this->leaveService->getActiveStationedLeaveConfig($context['course_pk']);
        $upcomingStationed = $this->leaveService->getUpcomingStationedLeaveConfig($context['course_pk']);

        $ptConfigMinDate = $activePt?->effective_from?->format('Y-m-d')
            ?? $upcomingPt?->effective_from?->format('Y-m-d');
        $stationedConfigMinDate = $activeStationed?->effective_from?->format('Y-m-d')
            ?? $upcomingStationed?->effective_from?->format('Y-m-d');

        return [
            'leaveType' => $leaveType,
            'natures' => $natures,
            'ptBalance' => $ptBalance,
            'application' => $application,
            'readOnly' => $readOnly,
            'stationedLeaveConfigured' => $this->leaveService->stationedLeaveConfigured($context['course_pk']),
            'upcomingStationedLeave' => $upcomingStationed,
            'activeStationedLeave' => $activeStationed,
            'ptExemptionConfigured' => $this->leaveService->ptExemptionConfigured($context['course_pk'], $gender),
            'upcomingPtExemption' => $upcomingPt,
            'activePtExemption' => $activePt,
            'ptEarliestFromDate' => $this->leaveService->resolveEarliestFromDate(
                $ptConfigMinDate,
                $activePt?->apply_cutoff_time
            ),
            'stationedEarliestFromDate' => $this->leaveService->resolveEarliestFromDate(
                $stationedConfigMinDate,
                $activeStationed?->apply_cutoff_time
            ),
            'ptCutoffTimeDisplay' => $this->leaveService->formatCutoffTimeDisplay($activePt?->apply_cutoff_time),
            'stationedCutoffTimeDisplay' => $this->leaveService->formatCutoffTimeDisplay($activeStationed?->apply_cutoff_time),
            'ptCutoffPassedToday' => $activePt
                && $activePt->apply_cutoff_time
                && ! $this->leaveService->isLeaveStartDateAllowedForApply(
                    $activePt->apply_cutoff_time,
                    now()->toDateString()
                ),
            'stationedCutoffPassedToday' => $activeStationed
                && $activeStationed->apply_cutoff_time
                && ! $this->leaveService->isLeaveStartDateAllowedForApply(
                    $activeStationed->apply_cutoff_time,
                    now()->toDateString()
                ),
            'stationedLeaveRequiresFacultyApproval' => $this->leaveService->stationedLeaveRequiresFacultyApproval($context['course_pk']),
        ];
    }
}
