<?php

namespace App\Services\COE;

use App\Models\ExaminationDriveFacultyMap;
use App\Models\QuestionPaper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * COE question papers from the administrator's side (Question Paper screen):
 * every paper owed on every drive, where it stands, its files, and deleting it.
 *
 * The administrator sees the original once the faculty has uploaded it, and
 * the translation only once Rajbhasha has sent it - never Rajbhasha's draft.
 */
class AdminPaperService
{
    /** Status → the admin view's badge key (also its CSS class). */
    private const VIEW = [
        QuestionPaper::STATUS_PENDING_RAJBHASHA => 'pending-rajbhasha',
        QuestionPaper::STATUS_TRANSLATED => 'pending-rajbhasha',
        QuestionPaper::STATUS_SENT_FOR_APPROVAL => 'pending-faculty',   // back with the faculty to verify
        QuestionPaper::STATUS_PUBLISHED => 'published',
    ];

    private const SHOWS_ORIGINAL = [
        QuestionPaper::STATUS_PENDING_RAJBHASHA,
        QuestionPaper::STATUS_TRANSLATED,
        QuestionPaper::STATUS_SENT_FOR_APPROVAL,
        QuestionPaper::STATUS_PUBLISHED,
    ];

    private const SHOWS_TRANSLATION = [
        QuestionPaper::STATUS_SENT_FOR_APPROVAL,
        QuestionPaper::STATUS_PUBLISHED,
    ];

    public function __construct(private QuestionPaperStorage $files)
    {
    }

    /**
     * One row per subject mapped on a drive, with its paper if added - plus any
     * paper whose subject has since been taken off the drive.
     *
     * @return Collection<int, array{id:int, paper_id:?int, code:string, name:string, drive:string, faculty:string,
     *     batch:string, deadline:Carbon, original_file:?string, translated_file:?string, translated_by:?string, status:string}>
     */
    public function papers(): Collection
    {
        $maps = ExaminationDriveFacultyMap::with(['drive', 'faculty', 'subject'])->get();
        $papers = QuestionPaper::with(['drive', 'faculty', 'translator'])->get()
            ->keyBy(fn (QuestionPaper $p) => $this->key($p->examination_drive_id, $p->subject_master_pk));

        $rows = $maps->map(function (ExaminationDriveFacultyMap $map) use ($papers) {
            $paper = $papers->pull($this->key($map->examination_drive_id, $map->subject_master_pk));

            return $this->toRow(
                $paper,
                $map->drive,
                $paper->faculty->full_name ?? $map->faculty->full_name ?? '-',
                $paper->paper_code ?? sprintf('QP-%d-%03d', $map->examination_drive_id, $map->subject_master_pk),
                trim($map->subject->subject_name ?? '-')
            );
        });

        // What is left was added for a subject no longer on its drive: still shown.
        $orphans = $papers->map(fn (QuestionPaper $p) => $this->toRow(
            $p, $p->drive, $p->faculty->full_name ?? '-', $p->paper_code, $p->paper_name
        ));

        return $rows->concat($orphans->values())
            ->sortBy(fn (array $r) => $r['deadline']->timestamp)
            ->values()
            ->map(fn (array $r, int $i) => ['id' => $i + 1] + $r);
    }

    /** @throws ModelNotFoundException when the paper has no original the admin may see */
    public function originalFor(int $paperId): QuestionPaper
    {
        return QuestionPaper::whereIn('status', self::SHOWS_ORIGINAL)->whereNotNull('original_file_path')->findOrFail($paperId);
    }

    /** @throws ModelNotFoundException when the paper has no translation the admin may see */
    public function translationFor(int $paperId): QuestionPaper
    {
        return QuestionPaper::whereIn('status', self::SHOWS_TRANSLATION)->whereNotNull('translated_file_path')->findOrFail($paperId);
    }

    /**
     * Delete: the paper and its files go; the record stays soft-deleted (who
     * owed it, its code and dates). The subject is Pending at Faculty again.
     *
     * @throws ModelNotFoundException
     */
    public function delete(int $paperId): void
    {
        $paths = DB::transaction(function () use ($paperId) {
            $paper = QuestionPaper::lockForUpdate()->findOrFail($paperId);
            $paths = [$paper->original_file_path, $paper->translated_file_path];

            $paper->update(['original_file_path' => null, 'translated_file_path' => null]);
            $paper->delete();

            return $paths;
        });

        foreach ($paths as $path) {
            $this->files->delete($path);
        }
    }

    private function toRow(?QuestionPaper $paper, $drive, string $faculty, string $code, string $name): array
    {
        $status = $paper->status ?? null;
        $showsOriginal = $paper && $paper->original_file_path && in_array($status, self::SHOWS_ORIGINAL, true);
        $showsTranslation = $paper && $paper->translated_file_path && in_array($status, self::SHOWS_TRANSLATION, true);

        return [
            'paper_id' => $paper?->id,
            'code' => $code,
            'name' => $name,
            'drive' => $drive->drive_name ?? '-',
            'faculty' => trim($faculty),
            'batch' => (string) ($drive->academic_session ?? '-'),
            'deadline' => $paper?->deadline ?? $drive?->start_date ?? Carbon::parse($paper?->created_date ?? $drive?->created_date),
            'original_file' => $showsOriginal ? $paper->original_file_name : null,
            'translated_file' => $showsTranslation ? $paper->translated_file_name : null,
            'translated_by' => $showsTranslation && $paper->translator
                ? trim($paper->translator->first_name.' '.$paper->translator->last_name)
                : null,
            'status' => $status ? self::VIEW[$status] : 'pending-faculty',
        ];
    }

    private function key(int $driveId, int $subjectPk): string
    {
        return $driveId.'-'.$subjectPk;
    }
}
