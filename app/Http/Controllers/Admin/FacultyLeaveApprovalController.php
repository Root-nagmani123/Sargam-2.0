<?php

namespace App\Http\Controllers\Admin;

use App\Exports\LbsnaaTableExport;
use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use App\Models\LeaveApplication;
use App\Services\FacultyLeaveApprovalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Traits\StampsPdfPageNumbers;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class FacultyLeaveApprovalController extends Controller
{
    use StampsPdfPageNumbers;

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

        $query = LeaveApplication::with(['student', 'nature', 'course'])
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
                        })->orWhereHas('course', function ($qc) use ($search) {
                            $qc->where('course_name', 'like', "%{$search}%");
                        })->orWhere('reason', 'like', "%{$search}%");
                    });
                }
            })
            ->addColumn('ot_code', fn ($row) => (string) ($row->student->generated_OT_code ?? '-'))
            ->addColumn('ot_name', fn ($row) => (string) ($this->approvalService->studentDisplayName($row->student)))
            ->addColumn('course_name', fn ($row) => (string) ($row->course->course_name ?? '-'))
            ->addColumn('leave_type_label', fn ($row) => (string) ($row->leave_type_label))
            ->addColumn('from_date_display', fn ($row) => $row->from_date?->format('d-m-Y') ?? '-')
            ->addColumn('to_date_display', fn ($row) => $row->to_date?->format('d-m-Y') ?? '-')
            ->addColumn('time_from_display', fn ($row) => (string) ($row->time_from_display))
            ->addColumn('time_to_display', fn ($row) => (string) ($row->time_to_display))
            ->addColumn('total_days_display', fn ($row) => number_format((float) $row->total_days, 0))
            ->addColumn('reason_text', fn ($row) => (string) (\Illuminate\Support\Str::limit($row->reason ?? '-', 80)))
            ->addColumn('status_label', function ($row) {
                $map = [
                    LeaveApplication::STATUS_PENDING => ['Pending', 'pending'],
                    LeaveApplication::STATUS_APPROVED => ['Approved', 'approved'],
                    LeaveApplication::STATUS_REJECTED => ['Rejected', 'rejected'],
                ];
                [$label, $variant] = $map[(int) $row->status] ?? ['-', 'pending'];

                return '<span class="badge rounded-1 approval-status approval-status--' . $variant . '">' . $label . '</span>';
            })
            ->addColumn('approver_name', fn ($row) => (string) ($row->action_by_faculty_name))
            ->addColumn('action', function ($row) {
                $viewUrl = route('faculty.leave-approval.show', $row->pk);
                $html = '<div class="d-inline-flex align-items-center gap-2 approval-action">';
                $html .= '<a href="' . $viewUrl . '" class="approval-action-btn approval-action-btn--view" title="View" aria-label="View"><i class="bi bi-eye"></i></a>';

                if ($this->approvalService->canUserActOnLeave($row)) {
                    $html .= '<button type="button" class="approval-action-btn approval-action-btn--approve faculty-leave-approve" data-id="' . $row->pk . '" title="Approve" aria-label="Approve"><i class="bi bi-check-lg"></i></button>';
                    $html .= '<button type="button" class="approval-action-btn approval-action-btn--reject faculty-leave-reject" data-id="' . $row->pk . '" title="Reject" aria-label="Reject"><i class="bi bi-x-lg"></i></button>';
                }

                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['status_label', 'action'])
            ->make(true);
    }

    /**
     * Excel (.xlsx) or PDF of the current listing, honouring the same filters
     * (baseQuery). The PDF renders its own view (export_pdf); the Excel sheet
     * is built from the heading/row arrays below.
     */
    public function export(Request $request)
    {
        $format = strtolower((string) $request->get('format', 'excel'));
        $filename = 'Leave_Approval_' . now()->format('Ymd_His');

        if ($format === 'pdf') {
            @ini_set('memory_limit', '256M');
            @set_time_limit(120);

            $rows = $this->baseQuery($request)->get();

            $logoPath = public_path('images/lbsnaa_logo.jpg');
            $logo = (is_file($logoPath) && is_readable($logoPath))
                ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath))
                : null;

            $pdf = Pdf::loadView('admin.leave.faculty_approval.export_pdf', [
                'rows' => $rows,
                'approvalService' => $this->approvalService,
                'filterLine' => $this->buildExportFilterLine($request),
                'approverLine' => $this->buildApproverHeaderLine($rows),
                'printedOn' => now()->format('d-m-Y H:i'),
                'reportTitle' => 'Leave Approval Report',
                'logo' => $logo,
            ])
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    // Both off deliberately. A report has no reason to execute PHP, and
                    // isPhpEnabled turns any raw block that later appears in the view into
                    // server-side code execution on stored data. The only image is $logo,
                    // a base64 data URI or null, so nothing needs fetching over the network.
                    'isRemoteEnabled' => false,
                    'isPhpEnabled' => false,
                    'dpi' => 96,
                ]);

            $this->stampPageNumbers($pdf);

            return $pdf->download($filename . '.pdf');
        }

        $rows = $this->baseQuery($request)->get();

        $headings = ['S. No.', 'OT Code', 'OT Name', 'Course Name', 'Leave Type', 'Date From', 'Date To', 'Time From', 'Time To', 'Total Days', 'Reason', 'Status'];

        $data = $rows->values()->map(fn ($row, $index) => [
            $index + 1,
            $row->student->generated_OT_code ?? '-',
            $this->approvalService->studentDisplayName($row->student),
            $row->course->course_name ?? '-',
            $row->leave_type_label,
            $row->from_date?->format('d-m-Y') ?? '-',
            $row->to_date?->format('d-m-Y') ?? '-',
            $row->time_from_display,
            $row->time_to_display,
            number_format((float) $row->total_days, 0),
            $row->reason ?? '-',
            $row->status_label,
        ])->values();

        $baseName = 'Leave_Approval_' . now()->format('Ymd_His');
        // Serial, dates, times, day count and status read better centred; the
        // names, course and reason stay left-aligned.
        $centreColumns = [0, 5, 6, 7, 8, 9, 11];
        $filterLine = $this->exportFilterLine($request);

        return Excel::download(
            new LbsnaaTableExport($data, $headings, 'Leave Approval', $filterLine, $centreColumns),
            $baseName . '.xlsx'
        );
    }

    /**
     * Human-readable summary of the filters in force, printed on the PDF so a
     * shared copy says what it is a report of.
     */
    protected function exportFilterLine(Request $request): string
    {
        $parts = [];

        $statusLabels = [
            LeaveApplication::STATUS_PENDING => 'Pending',
            LeaveApplication::STATUS_APPROVED => 'Approved',
            LeaveApplication::STATUS_REJECTED => 'Rejected',
        ];
        $status = $request->filled('status') ? (int) $request->input('status') : LeaveApplication::STATUS_PENDING;
        $parts[] = 'Status: ' . ($statusLabels[$status] ?? 'All');

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

    private function buildExportFilterLine(Request $request): string
    {
        $parts = [];

        $statusMap = [
            (string) LeaveApplication::STATUS_PENDING => 'Pending',
            (string) LeaveApplication::STATUS_APPROVED => 'Approved',
            (string) LeaveApplication::STATUS_REJECTED => 'Rejected',
        ];
        if ($request->filled('status') && isset($statusMap[(string) $request->status])) {
            $parts[] = 'Status: ' . $statusMap[(string) $request->status];
        }

        if ($request->filled('course_filter')) {
            $course = CourseMaster::find($request->course_filter);
            $parts[] = 'Course: ' . ($course->course_name ?? $request->course_filter);
        }

        if ($request->filled('from_date') || $request->filled('to_date')) {
            $parts[] = 'Period: ' . ($request->from_date ?: '…') . ' to ' . ($request->to_date ?: '…');
        }

        return implode('  |  ', $parts);
    }

    /**
     * "Approved By: <Name>" summary line for the report header — shown only when
     * every actioned row (approved/rejected) in the export shares the same faculty
     * approver, so it never misattributes a mixed-approver export to one person.
     * Pending-only exports (no approver yet) show nothing.
     */
    private function buildApproverHeaderLine($rows): string
    {
        $actionedRows = $rows->filter(fn ($row) => (int) $row->status !== LeaveApplication::STATUS_PENDING);

        if ($actionedRows->isEmpty()) {
            return '';
        }

        $approverPks = $actionedRows->pluck('approved_by_faculty_pk')->unique()->filter();

        if ($approverPks->count() !== 1) {
            return $actionedRows->pluck('approved_by_faculty_pk')->unique()->count() > 1
                ? 'Approved/Rejected By: Multiple Approvers'
                : '';
        }

        // Auto-approved rows have no approver, so take the name from a row the approver
        // actually actioned rather than whichever row happens to sort first.
        $name = $actionedRows->firstWhere('approved_by_faculty_pk', $approverPks->first())
            ?->action_by_faculty_name;

        return $name && $name !== '-' ? 'Approved/Rejected By: ' . $name : '';
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
