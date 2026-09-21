<?php

namespace App\Services\COE;

use App\Models\QuestionPaper;
use App\Models\QuestionPaperAuditLog;
use App\Models\QuestionPaperFile;
use App\Support\COE\QuestionPaperStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Throwable;

/**
 * COE Examination - writing the question paper audit trail.
 *
 * Logging never fails the action it is recording: a paper must still freeze if
 * the log write throws. The failure is sent to the application log instead, so
 * a broken audit trail is noticed rather than silently accepted.
 */
class QuestionPaperAuditService
{
    public const ACTION_ASSIGNED = 'ASSIGNED';
    public const ACTION_DEADLINE_SET = 'DEADLINE_SET';
    public const ACTION_FILE_UPLOADED = 'FILE_UPLOADED';
    public const ACTION_FILE_REMOVED = 'FILE_REMOVED';
    public const ACTION_FILE_DOWNLOADED = 'FILE_DOWNLOADED';
    public const ACTION_FILE_PREVIEWED = 'FILE_PREVIEWED';
    public const ACTION_FROZEN = 'FROZEN';
    public const ACTION_UNFREEZE_REQUESTED = 'UNFREEZE_REQUESTED';
    public const ACTION_UNFREEZE_APPROVED = 'UNFREEZE_APPROVED';
    public const ACTION_UNFREEZE_REJECTED = 'UNFREEZE_REJECTED';
    public const ACTION_MARKED_FOR_TRANSLATION = 'MARKED_FOR_TRANSLATION';
    public const ACTION_TRANSLATION_UPLOADED = 'TRANSLATION_UPLOADED';
    public const ACTION_TRANSLATION_FROZEN = 'TRANSLATION_FROZEN';
    public const ACTION_FINALIZED = 'FINALIZED';

    public const ACTION_LABELS = [
        self::ACTION_ASSIGNED => 'Assigned',
        self::ACTION_DEADLINE_SET => 'Deadline Set',
        self::ACTION_FILE_UPLOADED => 'File Uploaded',
        self::ACTION_FILE_REMOVED => 'File Removed',
        self::ACTION_FILE_DOWNLOADED => 'File Downloaded',
        self::ACTION_FILE_PREVIEWED => 'File Previewed',
        self::ACTION_FROZEN => 'Question Paper Frozen',
        self::ACTION_UNFREEZE_REQUESTED => 'Unfreeze Requested',
        self::ACTION_UNFREEZE_APPROVED => 'Unfreeze Approved',
        self::ACTION_UNFREEZE_REJECTED => 'Unfreeze Rejected',
        self::ACTION_MARKED_FOR_TRANSLATION => 'Marked for Translation',
        self::ACTION_TRANSLATION_UPLOADED => 'Translation Uploaded',
        self::ACTION_TRANSLATION_FROZEN => 'Translation Frozen',
        self::ACTION_FINALIZED => 'Finalized',
    ];

    /** Record one action. */
    public function log(?int $paperId, string $action, array $details = []): void
    {
        try {
            $user = Auth::user();

            QuestionPaperAuditLog::create([
                'question_paper_id' => $paperId,
                'action' => $action,
                'user_id' => $user->user_id ?? null,
                'user_name' => $this->userName($user),
                'user_role' => $this->userRole(),
                'details' => $details ?: null,
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 255),
                'created_date' => now(),
            ]);
        } catch (Throwable $e) {
            // Never break the action being audited; make the gap visible.
            Log::error('COE question paper audit log failed', [
                'question_paper_id' => $paperId,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** A status move, recorded with both ends so the trail reads on its own. */
    public function logStatusChange(QuestionPaper $paper, int $from, int $to, array $extra = []): void
    {
        $this->log($paper->id, $this->actionForStatus($to), array_merge([
            'from' => QuestionPaperStatus::label($from),
            'to' => QuestionPaperStatus::label($to),
        ], $extra));
    }

    /**
     * A file read. Logged for every download and preview because a question
     * paper is confidential - who opened it and when is the one thing that
     * leaves no other trace.
     */
    public function logFileAccess(QuestionPaperFile $file, string $action): void
    {
        $this->log($file->question_paper_id, $action, [
            'file_id' => $file->id,
            'file_type' => $file->file_type,
            'language' => $file->language,
            'file_name' => $file->original_name,
        ]);
    }

    protected function actionForStatus(int $status): string
    {
        return match ($status) {
            QuestionPaperStatus::FROZEN => self::ACTION_FROZEN,
            QuestionPaperStatus::UNFROZEN => self::ACTION_UNFREEZE_APPROVED,
            QuestionPaperStatus::TRANSLATION_PENDING => self::ACTION_MARKED_FOR_TRANSLATION,
            QuestionPaperStatus::TRANSLATED => self::ACTION_TRANSLATION_FROZEN,
            QuestionPaperStatus::FINALIZED => self::ACTION_FINALIZED,
            default => 'STATUS_CHANGED',
        };
    }

    protected function userName($user): ?string
    {
        if (! $user) {
            return null;
        }

        return $user->full_name
            ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))
            ?: ($user->name ?? null);
    }

    /** The role in play, for the trail. Session roles are what hasRole() reads. */
    protected function userRole(): ?string
    {
        $roles = Session::get('user_roles', []);

        if (is_array($roles) && $roles) {
            return substr(implode(', ', $roles), 0, 100);
        }

        return null;
    }
}
