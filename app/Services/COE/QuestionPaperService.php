<?php

namespace App\Services\COE;

use App\Models\ExaminationDriveFacultyMap;
use App\Models\FacultyMaster;
use App\Models\QuestionPaper;
use App\Support\COE\PaperHtmlSanitizer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * COE question papers from the faculty's side: which papers they owe, writing
 * the original paper in the editor (V1, straight to Rajbhasha - no freeze),
 * editing it (V2, V3...) while it waits on Rajbhasha, and - once the translation
 * is sent back - verifying it and freezing it with an OTP to publish, or editing
 * it again, which sends it back to Rajbhasha to translate the change.
 *
 * A faculty owes one paper per subject mapped to them on an examination drive
 * (examination_drive_faculty_maps). The question_papers row only exists once
 * they add it; until then the subject is Pending at Faculty.
 */
class QuestionPaperService
{
    /**
     * Status → the faculty view's badge key (also its CSS class). The faculty
     * does not see Rajbhasha's own draft; a translation sent back to them uses
     * the blue "your move" badge.
     */
    private const FACULTY_VIEW = [
        QuestionPaper::STATUS_PENDING_RAJBHASHA => 'pending-rajbhasha',
        QuestionPaper::STATUS_TRANSLATED => 'pending-rajbhasha',
        QuestionPaper::STATUS_SENT_FOR_APPROVAL => 'translated',
        QuestionPaper::STATUS_PUBLISHED => 'published',
    ];

    /** Badge key → label, in the faculty view's order (also the Status filter). */
    public const FACULTY_STATUS_LABELS = [
        'pending-faculty' => 'Pending at Faculty',
        'pending-rajbhasha' => 'Pending at Rajbhasha',
        'translated' => 'Pending Verification',
        'published' => 'Published',
    ];

    /**
     * Freeze moves a paper on from these, with the OTP: status => status after.
     * Only a translation the faculty has verified - an upload needs no freeze.
     */
    private const FREEZE_TO = [
        QuestionPaper::STATUS_SENT_FOR_APPROVAL => QuestionPaper::STATUS_PUBLISHED,
    ];

    /**
     * "Edit Original Paper": while it waits on Rajbhasha, and while the faculty
     * verifies the translation (before the freeze) - an edit then sends it back
     * to Rajbhasha to translate again. Not while Rajbhasha is translating it,
     * nor once it is published.
     */
    private const EDITABLE = [
        QuestionPaper::STATUS_PENDING_RAJBHASHA,
        QuestionPaper::STATUS_SENT_FOR_APPROVAL,
    ];

    private const LOCKED_MESSAGE = 'Rajbhasha is translating this question paper. You can edit it again once the translation is sent to you for verification.';

    private const PUBLISHED_MESSAGE = 'This question paper is published, so it can no longer be changed.';

    public function __construct(
        private PaperHtmlSanitizer $sanitizer,
        private FreezeOtpService $otp,
        private QuestionPaperStorage $files,
    ) {
    }

    /**
     * faculty_master.pk of the logged-in user, or null when they are not faculty.
     *
     * Question papers are confidential: get_auth_faculty_master_pk() also treats a
     * bare user_id as a faculty pk, which a non-faculty account can collide with.
     */
    public function currentFacultyPk(): ?int
    {
        return is_faculty_portal_user() ? get_auth_faculty_master_pk() : null;
    }

    /**
     * The faculty's papers, shaped for the My Question Paper views.
     *
     * Max marks and duration are not stored anywhere yet, so they show "-".
     * `id` is the mapping row's id, which changes when the drive is re-saved.
     *
     * @return Collection<int, array{id:int, code:string, name:string, drive:string, examination:string,
     *     course:string, course_short:string, term:string, batch:string, max_marks:string, duration:string, exam_date:Carbon,
     *     original_file:?string, translated_file:?string, translated_by:?string, comments:?string,
     *     version:int, status:string, editable:bool}>
     */
    public function papersForFaculty(int $facultyPk): Collection
    {
        // TODO: list frozen drives only, once COE freeze is saved on the drive.
        $maps = ExaminationDriveFacultyMap::with(['drive.course', 'drive.term', 'subject'])
            ->where('faculty_master_pk', $facultyPk)
            ->get();

        $added = QuestionPaper::with('translator')
            ->whereIn('examination_drive_id', $maps->pluck('examination_drive_id')->unique())
            ->where('faculty_master_pk', $facultyPk)
            ->get()
            ->keyBy(fn (QuestionPaper $qp) => $this->key($qp->examination_drive_id, $qp->subject_master_pk));

        return $maps
            ->map(fn (ExaminationDriveFacultyMap $map) => $this->toRow(
                $map,
                $added->get($this->key($map->examination_drive_id, $map->subject_master_pk))
            ))
            ->values();
    }

    /** One of the faculty's papers by its list id, or null when it is not theirs. */
    public function findForFaculty(int $facultyPk, int $paperId): ?array
    {
        return $this->papersForFaculty($facultyPk)->firstWhere('id', $paperId);
    }

    /**
     * The faculty's own added paper, for viewing its original file.
     *
     * @throws ModelNotFoundException when the paper is not theirs or has no file yet
     */
    public function originalPaperFor(int $facultyPk, int $paperId): QuestionPaper
    {
        $paper = $this->ownPaper($facultyPk, $paperId);
        if (! $paper->original_file_path) {
            throw (new ModelNotFoundException())->setModel(QuestionPaper::class, [$paper->id]);
        }

        return $paper;
    }

    /**
     * The faculty's own paper, for viewing its translation - only once
     * Rajbhasha has sent it to them (not while it is still Rajbhasha's draft).
     *
     * @throws ModelNotFoundException when the paper is not theirs or the translation is not theirs to see yet
     */
    public function translatedPaperFor(int $facultyPk, int $paperId): QuestionPaper
    {
        $paper = $this->ownPaper($facultyPk, $paperId);
        if (! $this->showsTranslation($paper)) {
            throw (new ModelNotFoundException())->setModel(QuestionPaper::class, [$paper->id]);
        }

        return $paper;
    }

    /** "+91 ******3274" — the number the freeze OTP goes to. */
    public function otpPhoneMasked(int $facultyPk): string
    {
        return FreezeOtpService::mask(FacultyMaster::where('pk', $facultyPk)->value('mobile_no'));
    }

    /**
     * Store the faculty's original paper - written in the editor (from a template
     * or from scratch), kept as an .html file. The first one is V1 and goes
     * straight to Rajbhasha (no freeze); "Edit Original Paper" while it is still
     * Pending at Rajbhasha replaces it as the next version.
     *
     * @throws ModelNotFoundException when the paper is not this faculty's
     * @throws ValidationException when it cannot be edited now, or the paper is blank
     */
    public function saveOriginalPaper(int $facultyPk, int $paperId, string $html): QuestionPaper
    {
        $row = $this->findForFaculty($facultyPk, $paperId);
        if ($row === null) {
            throw (new ModelNotFoundException())->setModel(ExaminationDriveFacultyMap::class, [$paperId]);
        }
        if (! $row['editable']) {
            throw ValidationException::withMessages(['paper' => $this->lockedMessage($row['status'])]);
        }

        $clean = $this->sanitizer->clean($html);
        if ($this->sanitizer->isBlank($clean)) {
            throw ValidationException::withMessages(['paper_html' => 'Write the question paper before uploading it.']);
        }
        $contents = $this->sanitizer->toDocument($row['name'], $clean);

        return $row['status'] === QuestionPaper::STATUS_PENDING_FACULTY
            ? $this->addFirstVersion($paperId, $row, $contents)
            : $this->addNextVersion($facultyPk, $paperId, $contents);
    }

    /** Why a paper cannot be edited now, by its faculty view status. */
    public function lockedMessage(string $viewStatus): string
    {
        return $viewStatus === 'published' ? self::PUBLISHED_MESSAGE : self::LOCKED_MESSAGE;
    }

    /**
     * The paper written in the editor, cleaned again, to start "Edit Original
     * Paper" from - or null when it has none (not added yet, or an older .docx upload).
     */
    public function editorHtmlFor(int $facultyPk, int $paperId): ?string
    {
        $row = $this->findForFaculty($facultyPk, $paperId);
        if ($row === null || $row['status'] === QuestionPaper::STATUS_PENDING_FACULTY) {
            return null;
        }

        $paper = $this->ownPaper($facultyPk, $paperId);
        if (! str_ends_with(strtolower((string) $paper->original_file_name), '.html')) {
            return null;
        }
        $document = $this->files->contents($paper->original_file_path);

        return $document === null ? null : $this->sanitizer->fromDocument($document);
    }

    private function addFirstVersion(int $paperId, array $row, string $contents): QuestionPaper
    {
        $map = ExaminationDriveFacultyMap::findOrFail($paperId);
        $path = $this->files->store($map->examination_drive_id, $row['code'].'_v1', 'html', null, $contents);

        try {
            return DB::transaction(function () use ($map, $row, $path) {
                // A row the admin soft-deleted is reused (drive + subject is unique),
                // starting over: nothing of the deleted paper's progress carries across.
                $paper = QuestionPaper::withTrashed()->firstOrNew([
                    'examination_drive_id' => $map->examination_drive_id,
                    'subject_master_pk' => $map->subject_master_pk,
                ]);
                if ($paper->exists && ! $paper->trashed()) {
                    throw ValidationException::withMessages(['paper' => 'This question paper has already been added.']);
                }

                $paper->fill([
                    'faculty_master_pk' => $map->faculty_master_pk,
                    'paper_code' => $row['code'],
                    'paper_name' => $row['name'],
                    'deadline' => $row['exam_date'],
                    'original_file_path' => $path,
                    'original_file_name' => $row['code'].'_v1.html',
                    'version' => 1,
                    'original_uploaded_at' => now(),
                    'status' => QuestionPaper::STATUS_PENDING_RAJBHASHA,
                    'comments' => null,
                    'frozen_at' => null,
                    'frozen_by' => null,
                    'translated_file_path' => null,
                    'translated_file_name' => null,
                    'translated_by' => null,
                    'translated_at' => null,
                    'sent_for_approval_at' => null,
                    'sent_for_approval_by' => null,
                    'created_by' => auth()->id(),
                    'deleted_at' => null,
                ])->save();

                return $paper;
            });
        } catch (\Throwable $e) {
            $this->files->delete($path);
            throw $e;
        }
    }

    /**
     * Edit: the new paper becomes the next version, still Pending at Rajbhasha.
     * The previous version's file is removed once the new one is recorded.
     */
    private function addNextVersion(int $facultyPk, int $paperId, string $contents): QuestionPaper
    {
        $paper = $this->ownPaper($facultyPk, $paperId);
        $version = (int) $paper->version + 1;
        $path = $this->files->store($paper->examination_drive_id, $paper->paper_code.'_v'.$version, 'html', null, $contents);

        try {
            $oldPath = DB::transaction(function () use ($paper, $path, $version) {
                $locked = QuestionPaper::lockForUpdate()->findOrFail($paper->id);
                if (! in_array($locked->status, self::EDITABLE, true)) {
                    throw ValidationException::withMessages(['paper' => $locked->status === QuestionPaper::STATUS_PUBLISHED
                        ? self::PUBLISHED_MESSAGE
                        : self::LOCKED_MESSAGE]);
                }
                if ((int) $locked->version + 1 !== $version) {
                    throw ValidationException::withMessages(['paper' => 'The paper changed meanwhile. Refresh the page and try again.']);
                }

                $oldPath = $locked->original_file_path;
                // Back to Rajbhasha. A translation sent for approval stays on the
                // paper, so Rajbhasha corrects it rather than starting again.
                $locked->update([
                    'original_file_path' => $path,
                    'original_file_name' => $locked->paper_code.'_v'.$version.'.html',
                    'version' => $version,
                    'original_uploaded_at' => now(),
                    'status' => QuestionPaper::STATUS_PENDING_RAJBHASHA,
                    'sent_for_approval_at' => null,
                    'sent_for_approval_by' => null,
                ]);

                return $oldPath;
            });
        } catch (\Throwable $e) {
            $this->files->delete($path);
            throw $e;
        }

        $this->files->delete($oldPath);

        return $paper->refresh();
    }

    /**
     * Freeze, step 1: sends the OTP to the faculty's mobile number.
     *
     * @throws ModelNotFoundException when the paper is not this faculty's
     * @throws ValidationException when it cannot be frozen now, or the OTP cannot be sent
     */
    public function sendFreezeOtp(int $facultyPk, int $paperId): void
    {
        $paper = $this->ownPaper($facultyPk, $paperId);
        $this->requireFreezable($paper);

        $this->otp->send((int) auth()->id(), $paper->id, FacultyMaster::where('pk', $facultyPk)->value('mobile_no'));
    }

    /**
     * Freeze, step 2: with the right OTP, a translation the faculty has verified
     * is published.
     *
     * @throws ModelNotFoundException when the paper is not this faculty's
     * @throws ValidationException when it cannot be frozen now, or the OTP is wrong / expired
     */
    public function freeze(int $facultyPk, int $paperId, string $otp): QuestionPaper
    {
        $paper = $this->ownPaper($facultyPk, $paperId);
        $this->requireFreezable($paper);

        $this->otp->verify((int) auth()->id(), $paper->id, $otp);

        return DB::transaction(function () use ($paper) {
            $locked = QuestionPaper::lockForUpdate()->findOrFail($paper->id);
            $this->requireStatus($locked, $paper->status, 'The paper changed meanwhile. Refresh the page and try again.');

            $locked->update([
                'status' => self::FREEZE_TO[$locked->status],
                'frozen_at' => now(),
                'frozen_by' => auth()->id(),
            ]);

            return $locked;
        });
    }

    /**
     * The faculty's added paper behind a list id.
     *
     * @throws ModelNotFoundException when it is not theirs, or not added yet
     */
    private function ownPaper(int $facultyPk, int $paperId): QuestionPaper
    {
        if ($this->findForFaculty($facultyPk, $paperId) === null) {
            throw (new ModelNotFoundException())->setModel(ExaminationDriveFacultyMap::class, [$paperId]);
        }

        $map = ExaminationDriveFacultyMap::findOrFail($paperId);

        return QuestionPaper::where('examination_drive_id', $map->examination_drive_id)
            ->where('subject_master_pk', $map->subject_master_pk)
            ->where('faculty_master_pk', $facultyPk)
            ->firstOrFail();
    }

    /** @throws ValidationException */
    private function requireStatus(QuestionPaper $paper, string $status, string $message): void
    {
        if ($paper->status !== $status) {
            throw ValidationException::withMessages(['paper' => $message]);
        }
    }

    /** @throws ValidationException */
    private function requireFreezable(QuestionPaper $paper): void
    {
        if (! isset(self::FREEZE_TO[$paper->status])) {
            throw ValidationException::withMessages(['paper' => 'This question paper cannot be frozen now.']);
        }
    }

    /** The faculty sees the translation only once Rajbhasha has sent it to them. */
    private function showsTranslation(?QuestionPaper $paper): bool
    {
        return $paper !== null
            && $paper->translated_file_path
            && in_array($paper->status, [QuestionPaper::STATUS_SENT_FOR_APPROVAL, QuestionPaper::STATUS_PUBLISHED], true);
    }

    private function toRow(ExaminationDriveFacultyMap $map, ?QuestionPaper $paper): array
    {
        $drive = $map->drive;
        $showsTranslation = $this->showsTranslation($paper);

        return [
            'id' => $map->id,
            'code' => $paper->paper_code ?? sprintf('QP-%d-%03d', $map->examination_drive_id, $map->subject_master_pk),
            'name' => trim($map->subject->subject_name ?? '-'),
            'drive' => $drive->drive_name ?? '-',
            // "Mid Term Drive" is sat as the "Mid Term Examination".
            'examination' => preg_replace('/\s*Drive$/i', '', (string) $drive->drive_name).' Examination',
            'course' => $drive->course->course_name ?? '-',
            'course_short' => trim((string) ($drive->course->couse_short_name ?? '')),
            'term' => $drive->term->term_name ?? '-',
            'batch' => (string) $drive->academic_session,
            'max_marks' => '-',
            'duration' => '-',
            'exam_date' => $drive->start_date ?? Carbon::parse($drive->created_date),
            'original_file' => $paper?->original_file_path ? $paper->original_file_name : null,
            'translated_file' => $showsTranslation ? $paper->translated_file_name : null,
            'translated_by' => $showsTranslation && $paper->translator
                ? trim($paper->translator->first_name.' '.$paper->translator->last_name)
                : null,
            'comments' => $paper?->comments,
            'version' => $paper->version ?? 1,
            'status' => $paper ? (self::FACULTY_VIEW[$paper->status] ?? $paper->status) : QuestionPaper::STATUS_PENDING_FACULTY,
            // Add; Edit while it waits on Rajbhasha or on the faculty's verification.
            'editable' => $paper === null || in_array($paper->status, self::EDITABLE, true),
        ];
    }

    private function key(int $driveId, int $subjectPk): string
    {
        return $driveId.'-'.$subjectPk;
    }
}
