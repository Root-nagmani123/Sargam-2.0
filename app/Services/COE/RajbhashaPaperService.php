<?php

namespace App\Services\COE;

use App\Models\QuestionPaper;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * COE question papers from Rajbhasha's (the Hindi translator's) side: every
 * paper a faculty has uploaded, uploading or discarding its translation, and
 * sending it back to the faculty to verify.
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

    public function __construct(private QuestionPaperStorage $files)
    {
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

    /**
     * Upload Translated Paper: stores the Hindi .docx; the paper is Translated
     * (still Rajbhasha's, not yet sent).
     *
     * @throws ValidationException when the paper is not waiting on a translation
     */
    public function addTranslation(int $paperId, UploadedFile $file): QuestionPaper
    {
        $paper = $this->find($paperId);
        $this->requireStatus($paper, QuestionPaper::STATUS_PENDING_RAJBHASHA, 'This paper already has a translation.');

        $path = $this->files->store($paper->examination_drive_id, $paper->paper_code.'_hindi', 'docx', $file);

        try {
            return DB::transaction(function () use ($paper, $path) {
                $locked = QuestionPaper::lockForUpdate()->findOrFail($paper->id);
                $this->requireStatus($locked, QuestionPaper::STATUS_PENDING_RAJBHASHA, 'This paper already has a translation.');

                $locked->update([
                    'translated_file_path' => $path,
                    'translated_file_name' => $locked->paper_code.'_hindi.docx',
                    'translated_by' => auth()->id(),
                    'translated_at' => now(),
                    'status' => QuestionPaper::STATUS_TRANSLATED,
                ]);

                return $locked;
            });
        } catch (\Throwable $e) {
            $this->files->delete($path);
            throw $e;
        }
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
            $this->requireStatus($locked, QuestionPaper::STATUS_TRANSLATED, 'Upload the translated paper before sending it for approval.');

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
