<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveApplication;
use App\Models\LeaveApplicationAttachment;
use App\Models\LeaveNatureMaster;
use App\Services\FacultyLeaveApprovalService;
use App\Services\LeaveApplicationService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $validated = $request->validate([
            'leave_type' => 'required|in:PT_EXEMPTION,STATIONED_LEAVE',
            'leave_nature_master_pk' => 'required|exists:leave_nature_master,pk',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
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
            'contact_number.regex' => 'Contact number must be a valid 10-digit mobile number starting with 6, 7, 8, or 9.',
            'attachments.*.file.max' => 'Each attachment must not exceed 5 MB.',
            'attachments.*.file.mimes' => 'Allowed file types: PDF, JPG, JPEG, PNG, DOC, DOCX.',
        ]);

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
                $application?->pk
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

        if ($isSubmit) {
            $status = $autoApprovePt
                ? LeaveApplication::STATUS_APPROVED
                : LeaveApplication::STATUS_PENDING;
        } else {
            $status = LeaveApplication::STATUS_DRAFT;
        }

        $now = now();
        $isNew = $application === null;

        $application = DB::transaction(function () use ($validated, $context, $application, $totalDays, $status, $now, $request, $isSubmit, $autoApprovePt) {
            $data = [
                'course_master_pk' => $context['course_pk'],
                'student_master_pk' => $context['student_pk'],
                'leave_type' => $validated['leave_type'],
                'leave_nature_master_pk' => $validated['leave_nature_master_pk'],
                'from_date' => $validated['from_date'],
                'to_date' => $validated['to_date'],
                'total_days' => $totalDays,
                'reason' => $validated['reason'],
                'contact_number' => $validated['contact_number'] ?? null,
                'status' => $status,
                'submitted_at' => $isSubmit ? $now : null,
                'approved_at' => $autoApprovePt ? $now : ($isSubmit ? null : $application?->approved_at),
                'approved_by_faculty_pk' => $autoApprovePt ? null : ($isSubmit ? null : $application?->approved_by_faculty_pk),
                'rejection_remarks' => $autoApprovePt ? null : ($isSubmit ? null : $application?->rejection_remarks),
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
        // PT exemptions auto-approve and drafts are not actionable, so neither is notified.
        if ($isSubmit && ! $autoApprovePt
            && $validated['leave_type'] === LeaveApplication::TYPE_STATIONED_LEAVE) {
            $this->notifyApproversOfNewLeaveRequest($application, $context, $totalDays);
        }

        $message = match (true) {
            ! $isSubmit => 'Leave application saved as draft.',
            $autoApprovePt => 'PT exemption application submitted and approved successfully.',
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

    protected function myLeaveDatatable(Request $request)
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

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('leave_type_label', fn ($row) => $row->leave_type_label)
            ->addColumn('from_date_display', fn ($row) => $row->from_date?->format('d-m-Y') ?? '-')
            ->addColumn('to_date_display', fn ($row) => $row->to_date?->format('d-m-Y') ?? '-')
            ->addColumn('total_days_display', fn ($row) => number_format((float) $row->total_days, 1))
            ->addColumn('status_badge', function ($row) {
                return '<span class="badge rounded-pill ' . $row->status_badge_class . '">' . e($row->status_label) . '</span>';
            })
            ->addColumn('action', function ($row) {
                $html = '<div class="d-inline-flex align-items-center gap-2">';

                if (in_array((int) $row->status, [LeaveApplication::STATUS_DRAFT, LeaveApplication::STATUS_PENDING], true)) {
                    $html .= '<a href="' . route('leave.edit', $row->pk) . '" class="text-primary" title="Edit"><i class="material-icons material-symbols-rounded" style="font-size:20px;">edit</i></a>';
                    $html .= '<a href="javascript:void(0)" class="text-danger leave-delete-btn" data-id="' . $row->pk . '" title="Delete"><i class="material-icons material-symbols-rounded" style="font-size:20px;">delete</i></a>';
                } else {
                    $html .= '<a href="' . route('leave.view', $row->pk) . '" class="text-primary" title="View"><i class="material-icons material-symbols-rounded" style="font-size:20px;">visibility</i></a>';
                }

                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
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
        ];
    }
}
