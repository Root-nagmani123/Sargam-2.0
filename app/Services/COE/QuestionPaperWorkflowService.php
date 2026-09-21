<?php

namespace App\Services\COE;

use App\Models\QuestionPaper;
use App\Models\QuestionPaperFile;
use App\Models\QuestionPaperTranslationRequest;
use App\Models\QuestionPaperUnfreezeRequest;
use App\Models\QuestionPaperVersion;
use App\Support\COE\QuestionPaperStatus;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * COE Examination - the status transitions of a question paper.
 *
 * Freezing, unfreezing and finalizing all do the same three things: check the
 * move is legal, cut a version snapshot, then change the status. Keeping them
 * together means the snapshot can never be forgotten on one path - without it
 * a paper that was corrected would lose the record of what was frozen first.
 */
class QuestionPaperWorkflowService
{
    public function __construct(
        protected QuestionPaperFileService $files,
        protected QuestionPaperAuditService $audit
    ) {
    }

    /**
     * Lock a paper against further edits by its author.
     *
     * The snapshot is taken before the status changes so it records the files
     * as they stood at the moment of freezing.
     */
    public function freeze(QuestionPaper $paper, ?int $userId = null): QuestionPaper
    {
        if (! $paper->canFreeze()) {
            throw new RuntimeException('This question paper cannot be frozen in its current state.');
        }

        $this->assertRequiredFilesPresent($paper);

        return DB::transaction(function () use ($paper, $userId) {
            $this->snapshot($paper, QuestionPaperStatus::FROZEN, $userId);

            $previous = (int) $paper->status;

            $paper->update([
                'status' => QuestionPaperStatus::FROZEN,
                'frozen_by' => $userId,
                'frozen_at' => now(),
                'modified_date' => now(),
            ]);

            $this->audit->logStatusChange($paper, $previous, QuestionPaperStatus::FROZEN, [
                'files' => $paper->files()->current()->count(),
            ]);

            return $paper->refresh();
        });
    }

    /**
     * Record a faculty request to reopen a frozen paper.
     *
     * The paper itself does not move: it stays frozen until an officer decides,
     * so a pending request cannot be used to edit in the meantime.
     */
    public function requestUnfreeze(QuestionPaper $paper, string $reason, int $userId): QuestionPaperUnfreezeRequest
    {
        if (! $paper->canRequestUnfreeze()) {
            throw new RuntimeException('An unfreeze request cannot be raised for this paper right now.');
        }

        $request = QuestionPaperUnfreezeRequest::create([
            'question_paper_id' => $paper->id,
            'requested_by' => $userId,
            'reason' => $reason,
            'requested_at' => now(),
            'status' => QuestionPaperUnfreezeRequest::STATUS_PENDING,
        ]);

        $this->audit->log($paper->id, QuestionPaperAuditService::ACTION_UNFREEZE_REQUESTED, [
            'reason' => $reason,
        ]);

        return $request;
    }

    /** Approve a pending request and reopen the paper for correction. */
    public function approveUnfreeze(
        QuestionPaperUnfreezeRequest $request,
        ?int $userId = null,
        ?string $remarks = null
    ): QuestionPaper {
        $paper = $request->questionPaper;

        if (! $request->isPending() || ! $paper) {
            throw new RuntimeException('This unfreeze request has already been decided.');
        }

        if ($paper->status !== QuestionPaperStatus::FROZEN) {
            throw new RuntimeException('Only a frozen paper can be unfrozen.');
        }

        return DB::transaction(function () use ($request, $paper, $userId, $remarks) {
            $request->update([
                'status' => QuestionPaperUnfreezeRequest::STATUS_APPROVED,
                'approved_by' => $userId,
                'approved_at' => now(),
                'approver_remarks' => $remarks,
            ]);

            // The reason travels onto the version record: the history should
            // say why a locked paper was reopened, not just that it was.
            $this->snapshot($paper, QuestionPaperStatus::UNFROZEN, $userId, $request->reason);

            $paper->update([
                'status' => QuestionPaperStatus::UNFROZEN,
                'modified_date' => now(),
            ]);

            $this->audit->logStatusChange($paper, QuestionPaperStatus::FROZEN, QuestionPaperStatus::UNFROZEN, [
                'reason' => $request->reason,
                'remarks' => $remarks,
            ]);

            return $paper->refresh();
        });
    }

    /** Refuse a request. The paper stays frozen. */
    public function rejectUnfreeze(
        QuestionPaperUnfreezeRequest $request,
        ?int $userId = null,
        ?string $remarks = null
    ): QuestionPaperUnfreezeRequest {
        if (! $request->isPending()) {
            throw new RuntimeException('This unfreeze request has already been decided.');
        }

        $request->update([
            'status' => QuestionPaperUnfreezeRequest::STATUS_REJECTED,
            'approved_by' => $userId,
            'approved_at' => now(),
            'approver_remarks' => $remarks,
        ]);

        $this->audit->log(
            $request->question_paper_id,
            QuestionPaperAuditService::ACTION_UNFREEZE_REJECTED,
            ['reason' => $request->reason, 'remarks' => $remarks]
        );

        return $request->refresh();
    }

    /* -------------------------------------------------- translation */

    /**
     * Send a frozen paper to the Translation Section.
     *
     * Re-runnable for a paper sent back for re-translation: the existing
     * request for that language is reopened rather than a second one stacked
     * beside it.
     */
    public function markForTranslation(
        QuestionPaper $paper,
        string $targetLanguage,
        ?int $userId = null,
        ?int $assignedTo = null
    ): QuestionPaperTranslationRequest {
        if (! $paper->canMarkForTranslation()) {
            throw new RuntimeException('Only a frozen question paper can be sent for translation.');
        }

        if ($targetLanguage === config('coe.question_paper.original_language', 'EN')) {
            throw new RuntimeException('A paper cannot be translated into the language it was written in.');
        }

        if (! array_key_exists($targetLanguage, config('coe.question_paper.languages', []))) {
            throw new RuntimeException('Unknown translation language.');
        }

        return DB::transaction(function () use ($paper, $targetLanguage, $userId, $assignedTo) {
            $request = QuestionPaperTranslationRequest::updateOrCreate(
                [
                    'question_paper_id' => $paper->id,
                    'target_language' => $targetLanguage,
                ],
                [
                    'status' => QuestionPaperTranslationRequest::STATUS_PENDING,
                    'requested_by' => $userId,
                    'requested_at' => now(),
                    'assigned_to' => $assignedTo,
                    'translated_at' => null,
                    'frozen_at' => null,
                    'approved_by' => null,
                    'approved_at' => null,
                    'modified_date' => now(),
                ]
            );

            $paper->update([
                'status' => QuestionPaperStatus::TRANSLATION_PENDING,
                'modified_date' => now(),
            ]);

            $this->audit->logStatusChange(
                $paper,
                QuestionPaperStatus::FROZEN,
                QuestionPaperStatus::TRANSLATION_PENDING,
                ['target_language' => $targetLanguage]
            );

            return $request;
        });
    }

    /**
     * Note that a translated file has arrived.
     *
     * The file itself is stored by QuestionPaperFileService under the target
     * language, so the original is untouched; this only moves the request on.
     */
    public function markTranslationUploaded(QuestionPaperTranslationRequest $request): QuestionPaperTranslationRequest
    {
        if (! $request->isEditable()) {
            throw new RuntimeException('This translation is already frozen.');
        }

        $request->update([
            'status' => QuestionPaperTranslationRequest::STATUS_UPLOADED,
            'translated_at' => now(),
            'modified_date' => now(),
        ]);

        return $request->refresh();
    }

    /** Lock the translation and move the paper on for approval. */
    public function freezeTranslation(
        QuestionPaperTranslationRequest $request,
        ?int $userId = null
    ): QuestionPaper {
        $paper = $request->questionPaper;

        if (! $paper) {
            throw new RuntimeException('This translation request has no paper.');
        }

        if (! $request->canFreeze()) {
            throw new RuntimeException('Upload the translated question paper before freezing.');
        }

        $this->assertTranslationFilesPresent($paper, $request->target_language);

        return DB::transaction(function () use ($request, $paper, $userId) {
            $request->update([
                'status' => QuestionPaperTranslationRequest::STATUS_FROZEN,
                'frozen_at' => now(),
                'modified_date' => now(),
            ]);

            $this->snapshot($paper, QuestionPaperStatus::TRANSLATED, $userId);

            $paper->update([
                'status' => QuestionPaperStatus::TRANSLATED,
                'modified_date' => now(),
            ]);

            $this->audit->logStatusChange(
                $paper,
                QuestionPaperStatus::TRANSLATION_PENDING,
                QuestionPaperStatus::TRANSLATED,
                ['target_language' => $request->target_language]
            );

            return $paper->refresh();
        });
    }

    /**
     * Final approval. The paper is done.
     *
     * The stored bytes are re-hashed first: a finalized paper is the one that
     * goes to the exam hall, so this is the last chance to catch a file that
     * has changed on disk since it was frozen.
     */
    public function finalize(QuestionPaper $paper, ?int $userId = null): QuestionPaper
    {
        if (! $paper->canFinalize()) {
            throw new RuntimeException('Only a translated question paper can be finalized.');
        }

        foreach ($paper->files()->current()->get() as $file) {
            if (! $this->files->verifyIntegrity($file)) {
                throw new RuntimeException(
                    'File "' . $file->original_name . '" no longer matches what was frozen. '
                    . 'It cannot be finalized until this is resolved.'
                );
            }
        }

        return DB::transaction(function () use ($paper, $userId) {
            QuestionPaperTranslationRequest::where('question_paper_id', $paper->id)
                ->where('status', QuestionPaperTranslationRequest::STATUS_FROZEN)
                ->update([
                    'status' => QuestionPaperTranslationRequest::STATUS_APPROVED,
                    'approved_by' => $userId,
                    'approved_at' => now(),
                    'modified_date' => now(),
                ]);

            $this->snapshot($paper, QuestionPaperStatus::FINALIZED, $userId);

            $paper->update([
                'status' => QuestionPaperStatus::FINALIZED,
                'finalized_by' => $userId,
                'finalized_at' => now(),
                'modified_date' => now(),
            ]);

            // The integrity check above passed, so record that the finalized
            // files are the ones that were frozen.
            $this->audit->logStatusChange(
                $paper,
                QuestionPaperStatus::TRANSLATED,
                QuestionPaperStatus::FINALIZED,
                ['integrity_verified' => true]
            );

            return $paper->refresh();
        });
    }

    /* -------------------------------------------------- internals */

    /**
     * A paper needs at least the paper itself before it can be locked.
     * Freezing an empty record would lock in nothing.
     */
    protected function assertRequiredFilesPresent(QuestionPaper $paper): void
    {
        $original = config('coe.question_paper.original_language', 'EN');

        foreach (QuestionPaperFile::REQUIRED_TYPES as $type) {
            $present = $paper->files()
                ->current()
                ->ofLanguage($original)
                ->ofType($type)
                ->exists();

            if (! $present) {
                throw new RuntimeException(
                    'Upload the ' . (QuestionPaperFile::TYPE_LABELS[$type] ?? $type) . ' before freezing.'
                );
            }
        }
    }

    /**
     * The translated paper itself must be there before the translation locks.
     * Only the question paper is required: an answer key is not translated, and
     * instructions may not need to be.
     */
    protected function assertTranslationFilesPresent(QuestionPaper $paper, string $language): void
    {
        $present = $paper->files()
            ->current()
            ->ofLanguage($language)
            ->ofType(QuestionPaperFile::TYPE_QUESTION_PAPER)
            ->exists();

        if (! $present) {
            throw new RuntimeException('Upload the translated question paper before freezing.');
        }
    }

    /** Record what the paper held at this point in its life. */
    protected function snapshot(
        QuestionPaper $paper,
        int $status,
        ?int $userId,
        ?string $reason = null
    ): QuestionPaperVersion {
        $files = $paper->files()->current()->get()
            ->map(fn (QuestionPaperFile $file) => $file->toSnapshot())
            ->values()
            ->all();

        return QuestionPaperVersion::create([
            'question_paper_id' => $paper->id,
            'version_no' => QuestionPaperVersion::nextVersionNo($paper->id),
            'status_at_version' => $status,
            'file_snapshot' => $files,
            'reason' => $reason,
            'changed_by' => $userId,
            'created_date' => now(),
        ]);
    }
}
