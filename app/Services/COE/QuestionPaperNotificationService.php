<?php

namespace App\Services\COE;

use App\Models\FacultyMaster;
use App\Models\QuestionPaper;
use App\Models\QuestionPaperTranslationRequest;
use App\Models\QuestionPaperUnfreezeRequest;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * COE Examination - question paper notifications.
 *
 * Wraps Sargam's NotificationService with the messages this module sends. Like
 * the audit trail, a failure here never breaks the action that triggered it: a
 * paper must still freeze if the notification cannot be written.
 */
class QuestionPaperNotificationService
{
    public const TYPE = 'question_paper';
    public const MODULE = 'Question Paper';

    public function __construct(protected NotificationService $notifications)
    {
    }

    /** A paper has been assigned to its setter. */
    public function paperAssigned(QuestionPaper $paper): void
    {
        $this->toFaculty(
            $paper,
            'Question Paper Assigned',
            'You have been assigned to prepare the question paper for ' . $this->subjectLabel($paper)
                . ($paper->deadline ? '. Deadline: ' . $paper->deadline->format('d-m-Y') : '.')
        );
    }

    /** A deadline reminder for a paper not yet frozen. */
    public function deadlineReminder(QuestionPaper $paper): void
    {
        $this->toFaculty(
            $paper,
            'Question Paper Freeze Pending',
            'The question paper for ' . $this->subjectLabel($paper) . ' is still open'
                . ($paper->deadline ? ' and is due on ' . $paper->deadline->format('d-m-Y') : '') . '.'
        );
    }

    /** An unfreeze request has been raised - the Examination Section decides. */
    public function unfreezeRequested(QuestionPaperUnfreezeRequest $request, array $officerUserIds): void
    {
        $paper = $request->questionPaper;

        if (! $paper || empty($officerUserIds)) {
            return;
        }

        $this->send(
            $officerUserIds,
            $paper->id,
            'Unfreeze Request',
            'An unfreeze request has been raised for ' . $this->subjectLabel($paper)
                . '. Reason: ' . $request->reason
        );
    }

    /** The officer has decided. */
    public function unfreezeDecided(QuestionPaperUnfreezeRequest $request): void
    {
        $paper = $request->questionPaper;

        if (! $paper) {
            return;
        }

        $approved = $request->status === QuestionPaperUnfreezeRequest::STATUS_APPROVED;

        $this->toFaculty(
            $paper,
            $approved ? 'Unfreeze Request Approved' : 'Unfreeze Request Rejected',
            $approved
                ? 'The question paper for ' . $this->subjectLabel($paper)
                    . ' has been reopened. Please make the correction and freeze it again.'
                : 'Your unfreeze request for ' . $this->subjectLabel($paper) . ' was not approved.'
                    . ($request->approver_remarks ? ' Remarks: ' . $request->approver_remarks : '')
        );
    }

    /** A paper has gone to the Translation Section. */
    public function translationAssigned(QuestionPaperTranslationRequest $request, array $translatorUserIds): void
    {
        $paper = $request->questionPaper;

        if (! $paper || empty($translatorUserIds)) {
            return;
        }

        $this->send(
            $translatorUserIds,
            $paper->id,
            'Translation Assigned',
            'The question paper for ' . $this->subjectLabel($paper)
                . ' is ready for translation into ' . $request->targetLanguageLabel() . '.'
        );

        // The setter's part is done; say so rather than leaving them waiting.
        $this->toFaculty(
            $paper,
            'Question Paper Sent for Translation',
            'The question paper for ' . $this->subjectLabel($paper)
                . ' has been sent for translation. No further action is needed from you.'
        );
    }

    /** A translation has been frozen and needs final approval. */
    public function approvalRequired(QuestionPaper $paper, array $approverUserIds): void
    {
        if (empty($approverUserIds)) {
            return;
        }

        $this->send(
            $approverUserIds,
            $paper->id,
            'Question Paper Approval Required',
            'The question paper for ' . $this->subjectLabel($paper)
                . ' has been translated and is awaiting final approval.'
        );
    }

    /** The paper is done. */
    public function finalized(QuestionPaper $paper): void
    {
        $this->toFaculty(
            $paper,
            'Question Paper Finalized',
            'The question paper for ' . $this->subjectLabel($paper)
                . ' has been approved and is ready for the examination.'
        );
    }

    /* -------------------------------------------------- internals */

    /**
     * Notifications address a login, while a paper names a faculty_master row,
     * so the setter is resolved through employee_master_pk.
     */
    protected function toFaculty(QuestionPaper $paper, string $title, string $message): void
    {
        $userId = FacultyMaster::where('pk', $paper->faculty_master_pk)->value('employee_master_pk');

        if (! $userId) {
            return;
        }

        $this->send([(int) $userId], $paper->id, $title, $message);
    }

    protected function send(array $userIds, int $paperId, string $title, string $message): void
    {
        $userIds = array_values(array_unique(array_filter($userIds)));

        if (! $userIds) {
            return;
        }

        try {
            $this->notifications->createMultiple(
                $userIds,
                self::TYPE,
                self::MODULE,
                $paperId,
                $title,
                $message
            );
        } catch (Throwable $e) {
            // A missed notification must not roll back a freeze or an approval.
            Log::error('COE question paper notification failed', [
                'question_paper_id' => $paperId,
                'title' => $title,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function subjectLabel(QuestionPaper $paper): string
    {
        $subject = optional(optional($paper->componentMap)->subject)->subject_name ?? 'the subject';
        $component = optional(optional($paper->componentMap)->component)->component_name;

        return $component ? $subject . ' (' . $component . ')' : $subject;
    }
}
