<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveApplication;
use App\Services\FacultyLeaveApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class FacultyLeaveApprovalController extends Controller
{
    public function __construct(protected FacultyLeaveApprovalService $approvalService)
    {
        $this->middleware(function ($request, $next) {
            if (! is_faculty_portal_user()) {
                abort(403, 'Only faculty can access leave approvals.');
            }

            if (! $this->approvalService->resolveFacultyPk()) {
                abort(403, 'Faculty profile not found for your account.');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatable($request);
        }

        return view('admin.leave.faculty_approval.index');
    }

    public function show($id)
    {
        $application = $this->findAccessibleLeave((int) $id);

        return view('admin.leave.faculty_approval.show', compact('application'));
    }

    public function approve(Request $request, $id)
    {
        $application = $this->findAccessibleLeave((int) $id);
        $facultyPk = (int) $this->approvalService->resolveFacultyPk();

        if (! $this->approvalService->canFacultyActOnLeave($facultyPk, $application)) {
            return response()->json([
                'success' => false,
                'message' => 'This leave application cannot be approved.',
            ], 422);
        }

        $application->update([
            'status' => LeaveApplication::STATUS_APPROVED,
            'approved_by_faculty_pk' => $facultyPk,
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
        $facultyPk = (int) $this->approvalService->resolveFacultyPk();

        if (! $this->approvalService->canFacultyActOnLeave($facultyPk, $application)) {
            return response()->json([
                'success' => false,
                'message' => 'This leave application cannot be rejected.',
            ], 422);
        }

        $application->update([
            'status' => LeaveApplication::STATUS_REJECTED,
            'approved_by_faculty_pk' => $facultyPk,
            'approved_at' => now(),
            'rejection_remarks' => $validated['rejection_remarks'] ?? null,
            'modified_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leave application rejected.',
        ]);
    }

    protected function datatable(Request $request)
    {
        $facultyPk = (int) $this->approvalService->resolveFacultyPk();
        $coursePks = $this->approvalService->getApproverCourseIds($facultyPk);

        $query = LeaveApplication::with(['student', 'nature'])
            ->whereIn('course_master_pk', $coursePks ?: [-1])
            ->whereIn('status', [
                LeaveApplication::STATUS_PENDING,
                LeaveApplication::STATUS_APPROVED,
                LeaveApplication::STATUS_REJECTED,
            ])
            ->orderByDesc('pk');

        if ($request->filled('leave_type')) {
            $query->where('leave_type', $request->leave_type);
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', (int) $request->status);
        } elseif (! $request->has('status')) {
            $query->where('status', LeaveApplication::STATUS_PENDING);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('participant_name', fn ($row) => e($this->approvalService->studentDisplayName($row->student)))
            ->addColumn('leave_type_label', fn ($row) => $row->leave_type_label)
            ->addColumn('from_date_display', fn ($row) => $row->from_date?->format('d/m/Y') ?? '-')
            ->addColumn('to_date_display', fn ($row) => $row->to_date?->format('d/m/Y') ?? '-')
            ->addColumn('total_days_display', fn ($row) => number_format((float) $row->total_days, 0))
            ->addColumn('reason_text', fn ($row) => e(\Illuminate\Support\Str::limit($row->reason ?? '-', 80)))
            ->addColumn('action', function ($row) use ($facultyPk) {
                $viewUrl = route('faculty.leave-approval.show', $row->pk);
                $html = '<div class="d-inline-flex align-items-center gap-2 flex-wrap">';

                if ($this->approvalService->canFacultyActOnLeave($facultyPk, $row)) {
                    $html .= '<button type="button" class="btn btn-sm btn-success faculty-leave-approve" data-id="' . $row->pk . '">Approve</button>';
                    $html .= '<button type="button" class="btn btn-sm btn-danger faculty-leave-reject" data-id="' . $row->pk . '">Reject</button>';
                } else {
                    $html .= '<a href="' . $viewUrl . '" class="btn btn-sm btn-outline-primary">View</a>';
                }

                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    protected function findAccessibleLeave(int $id): LeaveApplication
    {
        $facultyPk = (int) $this->approvalService->resolveFacultyPk();
        $application = LeaveApplication::with(['student', 'nature', 'attachments', 'course'])->findOrFail($id);

        if (! $this->approvalService->canFacultyAccessLeave($facultyPk, $application)) {
            abort(403, 'You are not authorized to view this leave application.');
        }

        return $application;
    }
}
