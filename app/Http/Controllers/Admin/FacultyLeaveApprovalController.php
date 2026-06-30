<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use App\Models\LeaveApplication;
use App\Services\FacultyLeaveApprovalService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class FacultyLeaveApprovalController extends Controller
{
    public function __construct(protected FacultyLeaveApprovalService $approvalService)
    {
        $this->middleware(function ($request, $next) {
            if (! $this->approvalService->canUserAccessLeaveApprovals()) {
                abort(403, 'You are not authorized to access leave approvals.');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatable($request);
        }

        return view('admin.leave.faculty_approval.index', [
            'courses' => $this->getFilterCourses(),
        ]);
    }

    public function show($id)
    {
        $application = $this->findAccessibleLeave((int) $id);

        return view('admin.leave.faculty_approval.show', compact('application'));
    }

    public function approve(Request $request, $id)
    {
        $application = $this->findAccessibleLeave((int) $id);

        if (! $this->approvalService->canUserActOnLeave($application)) {
            return response()->json([
                'success' => false,
                'message' => 'This leave application cannot be approved.',
            ], 422);
        }

        $application->update([
            'status' => LeaveApplication::STATUS_APPROVED,
            'approved_by_faculty_pk' => $this->approvalService->resolveFacultyPk(),
            'approved_at' => now(),
            'rejection_remarks' => null,
            'modified_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leave application approved successfully.',
        ]);
    }

    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'rejection_remarks' => 'nullable|string|max:1000',
        ]);

        $application = $this->findAccessibleLeave((int) $id);

        if (! $this->approvalService->canUserActOnLeave($application)) {
            return response()->json([
                'success' => false,
                'message' => 'This leave application cannot be rejected.',
            ], 422);
        }

        $application->update([
            'status' => LeaveApplication::STATUS_REJECTED,
            'approved_by_faculty_pk' => $this->approvalService->resolveFacultyPk(),
            'approved_at' => now(),
            'rejection_remarks' => $validated['rejection_remarks'] ?? null,
            'modified_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leave application rejected.',
        ]);
    }

    protected function baseQuery(Request $request)
    {
        $coursePks = $this->approvalService->getAccessibleCourseIds();

        $query = LeaveApplication::with(['student', 'nature'])
            ->where('leave_type', LeaveApplication::TYPE_STATIONED_LEAVE)
            ->whereIn('status', [
                LeaveApplication::STATUS_PENDING,
                LeaveApplication::STATUS_APPROVED,
                LeaveApplication::STATUS_REJECTED,
            ])
            ->orderByDesc('pk');

        if ($coursePks !== null) {
            $query->whereIn('course_master_pk', $coursePks ?: [-1]);
        }

        if (! $request->has('status')) {
            // Default landing (no status param sent at all) shows pending only.
            $query->where('status', LeaveApplication::STATUS_PENDING);
        } elseif ($request->filled('status')) {
            // A specific status tab was chosen.
            $query->where('status', (int) $request->status);
        }

        if ($request->filled('course_filter')) {
            $query->where('course_master_pk', (int) $request->input('course_filter'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('from_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('from_date', '<=', $request->input('to_date'));
        }

        return $query;
    }

    protected function datatable(Request $request)
    {
        return DataTables::of($this->baseQuery($request))
            ->addIndexColumn()
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');
                if (! empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->whereHas('student', function ($qs) use ($search) {
                            $qs->where('generated_OT_code', 'like', "%{$search}%")
                                ->orWhere('display_name', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        })->orWhere('reason', 'like', "%{$search}%");
                    });
                }
            })
            ->addColumn('ot_code', fn ($row) => e($row->student->generated_OT_code ?? '-'))
            ->addColumn('ot_name', fn ($row) => e($this->approvalService->studentDisplayName($row->student)))
            ->addColumn('leave_type_label', fn ($row) => e($row->leave_type_label))
            ->addColumn('from_date_display', fn ($row) => $row->from_date?->format('d-m-Y') ?? '-')
            ->addColumn('to_date_display', fn ($row) => $row->to_date?->format('d-m-Y') ?? '-')
            ->addColumn('total_days_display', fn ($row) => number_format((float) $row->total_days, 0))
            ->addColumn('reason_text', fn ($row) => e(\Illuminate\Support\Str::limit($row->reason ?? '-', 80)))
            ->addColumn('status_label', function ($row) {
                $map = [
                    LeaveApplication::STATUS_PENDING => ['Pending', 'pending'],
                    LeaveApplication::STATUS_APPROVED => ['Approved', 'approved'],
                    LeaveApplication::STATUS_REJECTED => ['Rejected', 'rejected'],
                ];
                [$label, $variant] = $map[(int) $row->status] ?? ['-', 'pending'];

                return '<span class="badge rounded-pill approval-status approval-status--' . $variant . '">' . $label . '</span>';
            })
            ->addColumn('action', function ($row) {
                $viewUrl = route('faculty.leave-approval.show', $row->pk);
                $html = '<div class="d-inline-flex align-items-center gap-2 approval-action">';
                $html .= '<a href="' . $viewUrl . '" class="approval-action-btn approval-action-btn--view" title="View" aria-label="View"><i class="bi bi-eye"></i></a>';

                if ($this->approvalService->canUserActOnLeave($row)) {
                    $html .= '<button type="button" class="approval-action-btn approval-action-btn--approve faculty-leave-approve" data-id="' . $row->pk . '" title="Approve" aria-label="Approve"><i class="bi bi-hand-thumbs-up-fill"></i></button>';
                    $html .= '<button type="button" class="approval-action-btn approval-action-btn--reject faculty-leave-reject" data-id="' . $row->pk . '" title="Reject" aria-label="Reject"><i class="bi bi-hand-thumbs-down-fill"></i></button>';
                }

                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['status_label', 'action'])
            ->make(true);
    }

    public function export(Request $request)
    {
        $rows = $this->baseQuery($request)->get();

        $columns = ['S. No.', 'OT Code', 'OT Name', 'Leave Type', 'Date From', 'Date To', 'Total Days', 'Reason', 'Status'];
        $filename = 'Leave_Approval_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows, $columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);

            $serial = 1;
            foreach ($rows as $row) {
                fputcsv($out, [
                    $serial++,
                    $row->student->generated_OT_code ?? '-',
                    $this->approvalService->studentDisplayName($row->student),
                    $row->leave_type_label,
                    $row->from_date?->format('d-m-Y') ?? '-',
                    $row->to_date?->format('d-m-Y') ?? '-',
                    number_format((float) $row->total_days, 0),
                    $row->reason ?? '-',
                    $row->status_label,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Accessible courses that have at least one stationed-leave application (for the filter dropdown).
     */
    protected function getFilterCourses()
    {
        $coursePks = $this->approvalService->getAccessibleCourseIds();

        $ids = LeaveApplication::query()
            ->where('leave_type', LeaveApplication::TYPE_STATIONED_LEAVE)
            ->whereIn('status', [
                LeaveApplication::STATUS_PENDING,
                LeaveApplication::STATUS_APPROVED,
                LeaveApplication::STATUS_REJECTED,
            ])
            ->when($coursePks !== null, fn ($q) => $q->whereIn('course_master_pk', $coursePks ?: [-1]))
            ->distinct()
            ->pluck('course_master_pk')
            ->all();

        if (empty($ids)) {
            return collect();
        }

        return CourseMaster::whereIn('pk', $ids)
            ->orderBy('course_name')
            ->pluck('course_name', 'pk');
    }

    protected function findAccessibleLeave(int $id): LeaveApplication
    {
        $application = LeaveApplication::with(['student', 'nature', 'attachments', 'course'])->findOrFail($id);

        if (! $this->approvalService->canUserAccessLeave($application)) {
            abort(403, 'You are not authorized to view this leave application.');
        }

        return $application;
    }
}
