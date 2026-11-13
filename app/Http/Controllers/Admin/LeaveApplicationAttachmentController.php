<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveApplicationAttachment;
use App\Services\FacultyLeaveApprovalService;
use Illuminate\Support\Facades\Storage;

class LeaveApplicationAttachmentController extends Controller
{
    public function __construct(private FacultyLeaveApprovalService $approvalService) {}

    public function show(int $applicationId, int $attachmentId)
    {
        $attachment = LeaveApplicationAttachment::with('application')
            ->where('leave_application_pk', $applicationId)
            ->findOrFail($attachmentId);
        $application = $attachment->application;
        if (! $application) {
            abort(404);
        }

        $studentPk = auth_student_master_pk();

        $isOwner = $studentPk !== null && (int) $application->student_master_pk === (int) $studentPk;
        if (! $isOwner && ! $this->approvalService->canUserAccessLeave($application)) {
            abort(404);
        }

        if (! $attachment->file_path || ! Storage::disk('private')->exists($attachment->file_path)) {
            abort(404);
        }

        return Storage::disk('private')->response(
            $attachment->file_path,
            $attachment->original_file_name
        );
    }
}
