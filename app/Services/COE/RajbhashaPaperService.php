<?php

namespace App\Services\COE;

use App\Models\QuestionPaper;
use App\Support\COE\PaperHtmlSanitizer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * COE question papers from Rajbhasha's (the Hindi translator's) side: every
 * paper a faculty has added, writing its translation in the editor (and
 * editing or discarding it), and sending it back to the faculty to verify.
 *
 * Who is Rajbhasha is a permission, not a role: whichever role is given
 * PERMISSION (the menu permission of this screen; its routes check it).
 */
class RajbhashaPaperService
{
    public const PERMISSION = 'coe_my_question_paper_rajbhasha';

    /**
     * Status → the Rajbhasha view's badge key (also its CSS class). The keys are
     * the view's own: MyQuestionPaperController::STATUS_*.
     */
    private const VIEW = [
        QuestionPaper::STATUS_PENDING_RAJBHASHA => 'pending',
        QuestionPaper::STATUS_TRANSLATED => 'translated',
        QuestionPaper::STATUS_SENT_FOR_APPROVAL => 'sent',
        QuestionPaper::STATUS_PUBLISHED => 'published',
    ];

    /** Translate Paper (no translation yet) and Edit Translated Paper (not yet sent). */
    private const TRANSLATABLE = [
        QuestionPaper::STATUS_PENDING_RAJBHASHA,
        QuestionPaper::STATUS_TRANSLATED,
    ];

    private const SENT_MESSAGE = 'This translation has already been sent for approval, so it can no longer be changed.';

    public function __construct(
        private QuestionPaperStorage $files,
        private PaperHtmlSanitizer $sanitizer,
    ) {
    }

    /**
     * Every paper that has reached Rajbhasha, shaped for its view.
     *
     * @return Collection<int, array{id:int, code:string, name:string, drive:string, batch:string,
     *     deadline:Carbon, faculty:string, original_file:string, translated_file:?string, status:string}>
     */
    public function papers(): Collection
    {
        return QuestionPaper::with(['drive', 'faculty'])
            ->whereIn('status', array_keys(self::VIEW))
            ->orderBy('deadline')
            ->get()
            ->map(fn (QuestionPaper $p) => [
                'id' => $p->id,
                'code' => $p->paper_code,
                'name' => $p->paper_name,
                'drive' => $p->drive->drive_name ?? '-',
                'batch' => (string) ($p->drive->academic_session ?? '-'),
                'deadline' => $p->deadline ?? $p->drive->start_date ?? Carbon::parse($p->created_date),
                'faculty' => trim($p->faculty->full_name ?? '-'),
                'original_file' => $p->original_file_name,
                'translated_file' => $p->translated_file_path ? $p->translated_file_name : null,
                'status' => self::VIEW[$p->status],
            ])
            ->values();
    }

    /** @throws \Illuminate\Database\Eloquent\ModelNotFoundException when it has not reached Rajbhasha */
    public function find(int $paperId): QuestionPaper
    {
        return QuestionPaper::whereIn('status', array_keys(self::VIEW))->findOrFail($paperId);
    }

    /** Translate Paper / Edit Translated Paper: until it is sent for approval. */
    public function isTranslatable(QuestionPaper $paper): bool
    {
        return in_array($paper->status, self::TRANSLATABLE, true);
    }

    /**
     * The paper's facts for the editor page: the summary card, and the header
     * of a template (admin/coe/faculty_question_paper/templates/_head).
     *
     * @return array{id:int, code:string, name:string, examination:string, course:string,
     *     course_short:string, term:string, batch:string, max_marks:string, exam_date:Carbon}
     */
    public function editorRow(QuestionPaper $paper): array
    {
        $paper->loadMissing(['drive.course', 'drive.term']);
        $drive = $paper->drive;

        return [
            'id' => $paper->id,
            'code' => $paper->paper_code,
            'name' => $paper->paper_name,
            // "Mid Term Drive" is sat as the "Mid Term Examination".
            'examination' => preg_replace('/\s*Drive$/i', '', (string) ($drive->drive_name ?? '')).' Examination',
            'course' => $drive->course->course_name ?? '-',
            'course_short' => trim((string) ($drive->course->couse_short_name ?? '')),
            'term' => $drive->term->term_name ?? '-',
            'batch' => (string) ($drive->academic_session ?? '-'),
            'max_marks' => '-',
            'exam_date' => $drive->start_date ?? Carbon::parse($paper->created_date),
        ];
    }

    /**
     * What the editor starts from: the translation so far, or else the faculty's
     * paper itself (the templates are English and Hindi side by side, so it is
     * translated in place). Null when neither was written in the editor (an older
     * .docx upload) - the page then starts from a template.
     */
    public function editorHtmlFor(QuestionPaper $paper): ?string
    {
        foreach ([[$paper->translated_file_path, $paper->translated_file_name], [$paper->original_file_path, $paper->original_file_name]] as [$path, $name]) {
            if ($path && str_ends_with(strtolower((string) $name), '.html')) {
                $document = $this->files->contents($path);
                if ($document !== null) {
                    return $this->sanitizer->fromDocument($document);
                }
            }
        }

        return null;
    }

    /**
     * Upload Paper on Translate Question Paper: stores the translation written in
     * the editor as .html; the paper is Translated (still Rajbhasha's, not yet
     * sent). Editing it again replaces the file.
     *
     * @throws ValidationException when it has been sent already, or the translation is blank
     */
    public function saveTranslation(int $paperId, string $html): QuestionPaper
    {
        $paper = $this->find($paperId);
        if (! $this->isTranslatable($paper)) {
            throw ValidationException::withMessages(['paper' => self::SENT_MESSAGE]);
        }

        $clean = $this->sanitizer->clean($html);
        if ($this->sanitizer->isBlank($clean)) {
            throw ValidationException::withMessages(['paper_html' => 'Write the translated paper before uploading it.']);
        }

        $path = $this->files->store($paper->examination_drive_id, $paper->paper_code.'_hindi', 'html', null,
            $this->sanitizer->toDocument($paper->paper_name.' (Hindi)', $clean));

        try {
            [$saved, $oldPath] = DB::transaction(function () use ($paper, $path) {
                $locked = QuestionPaper::lockForUpdate()->findOrFail($paper->id);
                if (! $this->isTranslatable($locked)) {
                    throw ValidationException::withMessages(['paper' => self::SENT_MESSAGE]);
                }

                $oldPath = $locked->translated_file_path;
                $locked->update([
                    'translated_file_path' => $path,
                    'translated_file_name' => $locked->paper_code.'_hindi.html',
                    'translated_by' => auth()->id(),
                    'translated_at' => now(),
                    'status' => QuestionPaper::STATUS_TRANSLATED,
                ]);

                return [$locked, $oldPath];
            });
        } catch (\Throwable $e) {
            $this->files->delete($path);
            throw $e;
        }

        // Only once the row points at the new file.
        $this->files->delete($oldPath);

        return $saved;
    }

    /**
     * Delete Translated Paper: discards the translation before it is sent; the
     * paper is Pending at Rajbhasha again.
     *
     * @throws ValidationException when it has already been sent
     */
    public function deleteTranslation(int $paperId): void
    {
        $paper = $this->find($paperId);

        $path = DB::transaction(function () use ($paper) {
            $locked = QuestionPaper::lockForUpdate()->findOrFail($paper->id);
            $this->requireStatus($locked, QuestionPaper::STATUS_TRANSLATED, 'Only a translation not yet sent can be deleted.');

            $path = $locked->translated_file_path;
            $locked->update([
                'translated_file_path' => null,
                'translated_file_name' => null,
                'translated_by' => null,
                'translated_at' => null,
                'status' => QuestionPaper::STATUS_PENDING_RAJBHASHA,
            ]);

            return $path;
        });

        $this->files->delete($path);
    }

    /**
     * Send for Approval: the translation goes back to the faculty, who verifies
     * it and freezes it to publish. Nothing more to do here after this.
     *
     * @throws ValidationException when there is no unsent translation
     */
    public function sendForApproval(int $paperId): QuestionPaper
    {
        $paper = $this->find($paperId);

        return DB::transaction(function () use ($paper) {
            $locked = QuestionPaper::lockForUpdate()->findOrFail($paper->id);
            $this->requireStatus($locked, QuestionPaper::STATUS_TRANSLATED, 'Translate the paper before sending it for approval.');

            $locked->update([
                'status' => QuestionPaper::STATUS_SENT_FOR_APPROVAL,
                'sent_for_approval_at' => now(),
                'sent_for_approval_by' => auth()->id(),
            ]);

            return $locked;
        });
    }

    /** @throws ValidationException */
    private function requireStatus(QuestionPaper $paper, string $status, string $message): void
    {
        if ($paper->status !== $status) {
            throw ValidationException::withMessages(['paper' => $message]);
        }
    }
}
