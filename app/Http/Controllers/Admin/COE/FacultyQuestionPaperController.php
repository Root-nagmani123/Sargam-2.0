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
     * Add Question Paper — upload a .docx or write the paper in the editor.
     * Only a paper still waiting on the faculty can be added; any other state
     * goes back to the list (a draft is revised with Re-upload there).
     */
    public function create(int $paper)
    {
        $row = $this->sampleRows()->firstWhere('id', $paper);
        abort_if($row === null, 404);

        if ($row['status'] !== self::STATUS_PENDING_FACULTY) {
            return redirect()->route('admin.coe.faculty-question-paper.index')
                ->with('error', 'This question paper has already been added.');
        }

        return view('admin.coe.faculty_question_paper.create', ['paper' => $row]);
    }

    /**
     * @return Collection<int, array{id:int, code:string, name:string, drive:string, examination:string,
     *     course:string, term:string, batch:string, max_marks:int, duration:int, exam_date:Carbon,
     *     original_file:?string, translated_file:?string, translated_by:?string, comments:?string,
     *     version:int, status:string}>
     */
    private function sampleRows(): Collection
    {
        $rows = [
            ['FC101-001', 'Law- Ethics & Intergrity', 'Mid Term Drive', 'Phase IV 2025', 'Annual Term', '2026', 100, 120, '2026-10-05', null, 1, self::STATUS_PENDING_FACULTY],
            ['FC101-004', 'Public Policy & Governance', 'Mid Term Drive', 'Phase IV 2025', 'Annual Term', '2026', 100, 120, '2026-10-09', 'Please reduce Section B to 5 questions.', 2, self::STATUS_DRAFT],
            ['FC102-002', 'FC- Indian Polity', 'End Term Drive', 'Foundation Course 2026', 'Term I', '2026', 50, 90, '2026-11-14', null, 1, self::STATUS_PENDING_RAJBHASHA],
            ['FC102-006', 'FC- Economic Survey', 'End Term Drive', 'Foundation Course 2025', 'Term II', '2025', 75, 90, '2026-11-18', 'Approved.', 3, self::STATUS_PUBLISHED],
            ['FC103-002', 'FC- Public Administration', 'End Term Drive', 'Foundation Course 2026', 'Term I', '2026', 100, 180, '2026-11-20', null, 1, self::STATUS_PENDING_FACULTY],
        ];

        return collect($rows)->values()->map(function (array $r, int $i) {
            [$code, $name, $drive, $course, $term, $batch, $maxMarks, $duration, $examDate, $comments, $version, $status] = $r;
            $hasOriginal = $status !== self::STATUS_PENDING_FACULTY;
            $isPublished = $status === self::STATUS_PUBLISHED;

            return [
                'id' => $i + 1,
                'code' => $code,
                'name' => $name,
                'drive' => $drive,
                // "Mid Term Drive" is sat as the "Mid Term Examination".
                'examination' => preg_replace('/\s*Drive$/i', '', $drive).' Examination',
                'course' => $course,
                'term' => $term,
                'batch' => $batch,
                'max_marks' => $maxMarks,
                'duration' => $duration,           // minutes
                'exam_date' => Carbon::parse($examDate),
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
