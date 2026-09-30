<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * COE → Question Paper Management → My Question Paper, as the FACULTY sees it
 * (the setter uploads the original paper, revises it, then freezes it with an
 * OTP so it can go to Rajbhasha for translation).
 *
 * Design-first screen, like MyQuestionPaperController: the rows are SAMPLE
 * data and no table backs this page yet. Replace sampleRows() with the real
 * query when the COE question paper module lands; the view reads only the
 * keys listed on sampleRows().
 */
class FacultyQuestionPaperController extends Controller
{
    public const STATUS_PENDING_FACULTY = 'pending-faculty';     // nothing uploaded yet

    public const STATUS_DRAFT = 'draft';                         // uploaded, still editable

    public const STATUS_PENDING_RAJBHASHA = 'pending-rajbhasha'; // frozen, with the translator

    public const STATUS_PUBLISHED = 'published';                 // translated and released

    public const STATUS_LABELS = [
        self::STATUS_PENDING_FACULTY => 'Pending at Faculty',
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PENDING_RAJBHASHA => 'Pending at Rajbhasha',
        self::STATUS_PUBLISHED => 'Published',
    ];

    public function index()
    {
        $papers = $this->sampleRows();

        return view('admin.coe.faculty_question_paper.index', [
            'papers' => $papers,
            'drives' => $papers->pluck('drive')->unique()->sort()->values(),
            'batches' => $papers->pluck('batch')->unique()->sort()->values(),
            'statusLabels' => self::STATUS_LABELS,
            'otpPhoneMasked' => '+91 ******3274',
        ]);
    }

    /**
     * @return Collection<int, array{id:int, code:string, name:string, drive:string, batch:string, deadline:Carbon,
     *     original_file:?string, translated_file:?string, translated_by:?string, comments:?string,
     *     version:int, status:string}>
     */
    private function sampleRows(): Collection
    {
        $rows = [
            ['FC101-001', 'Law- Ethics & Intergrity', 'Mid Term Drive', '2026', '2026-10-05', null, 1, self::STATUS_PENDING_FACULTY],
            ['FC101-004', 'Public Policy & Governance', 'Mid Term Drive', '2026', '2026-10-09', 'Please reduce Section B to 5 questions.', 2, self::STATUS_DRAFT],
            ['FC102-002', 'FC- Indian Polity', 'End Term Drive', '2026', '2026-11-14', null, 1, self::STATUS_PENDING_RAJBHASHA],
            ['FC102-006', 'FC- Economic Survey', 'End Term Drive', '2025', '2026-11-18', 'Approved.', 3, self::STATUS_PUBLISHED],
        ];

        return collect($rows)->values()->map(function (array $r, int $i) {
            [$code, $name, $drive, $batch, $deadline, $comments, $version, $status] = $r;
            $hasOriginal = $status !== self::STATUS_PENDING_FACULTY;
            $isPublished = $status === self::STATUS_PUBLISHED;

            return [
                'id' => $i + 1,
                'code' => $code,
                'name' => $name,
                'drive' => $drive,
                'batch' => $batch,
                'deadline' => Carbon::parse($deadline),
                'original_file' => $hasOriginal ? "{$code}_v{$version}.docx" : null,
                'translated_file' => $isPublished ? "{$code}_hindi.docx" : null,
                'translated_by' => $isPublished ? 'Lova Gray' : null,
                'comments' => $comments,
                'version' => $version,
                'status' => $status,
            ];
        });
    }
}
