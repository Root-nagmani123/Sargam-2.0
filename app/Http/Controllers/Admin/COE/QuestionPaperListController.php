<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * COE → Question Paper Management → Question Paper (the administrator's list).
 *
 * Design-first screen, like MyQuestionPaperController: the rows are SAMPLE
 * data and no table backs this page yet. Replace sampleRows() with the real
 * query when the COE question paper module lands; the view reads only the
 * keys listed on sampleRows().
 *
 * Two scopes, each its own URL (docs/new-design-index-page.md §1 "Scope tabs"):
 *   ?scope=all        every paper (default)
 *   ?scope=translated only papers with a Rajbhasha translation
 */
class QuestionPaperListController extends Controller
{
    public const STATUS_PENDING_FACULTY = 'pending-faculty';     // faculty has not submitted the paper

    public const STATUS_PENDING_RAJBHASHA = 'pending-rajbhasha'; // waiting on the translator

    public const STATUS_PUBLISHED = 'published';                 // translated and released

    public const STATUS_LABELS = [
        self::STATUS_PENDING_FACULTY => 'Pending at Faculty',
        self::STATUS_PENDING_RAJBHASHA => 'Pending at Rajbhasha',
        self::STATUS_PUBLISHED => 'Published',
    ];

    public const SCOPES = ['all', 'translated'];

    public function index(Request $request)
    {
        $scope = in_array($request->query('scope'), self::SCOPES, true) ? $request->query('scope') : 'all';

        $papers = $this->sampleRows();
        if ($scope === 'translated') {
            $papers = $papers->filter(fn (array $p) => filled($p['translated_file']))->values();
        }

        return view('admin.coe.question_paper_list.index', [
            'scope' => $scope,
            'papers' => $papers,
            'drives' => $papers->pluck('drive')->unique()->sort()->values(),
            'batches' => $papers->pluck('batch')->unique()->sort()->values(),
            'faculties' => $papers->pluck('faculty')->unique()->sort()->values(),
            'statusLabels' => self::STATUS_LABELS,
        ]);
    }

    /**
     * @return Collection<int, array{id:int, code:string, name:string, drive:string, faculty:string, batch:string,
     *     deadline:Carbon, original_file:?string, translated_file:?string, translated_by:?string, status:string}>
     */
    private function sampleRows(): Collection
    {
        $rows = [
            ['FC101-001', 'Law- Ethics & Intergrity', 'Mid Term Drive', 'Amit Sharma', '2026', '2026-10-05', false, null, self::STATUS_PENDING_FACULTY],
            ['FC101-002', 'Leadership- Discipline', 'Mid Term Drive', 'Sumit Verma', '2026', '2026-10-05', true, 'Lova Gray', self::STATUS_PUBLISHED],
            ['FC101-003', 'FC- FC 103', 'Mid Term Drive', 'John Doe', '2026', '2026-10-05', true, null, self::STATUS_PENDING_RAJBHASHA],
            ['FC102-001', 'FC- Indian Economy', 'End Term Drive', 'Rahul Verma', '2026', '2026-11-12', true, 'Kavita Rao', self::STATUS_PUBLISHED],
            ['FC102-004', 'FC- Constitutional Law', 'End Term Drive', 'Meera Iyer', '2025', '2026-11-15', false, null, self::STATUS_PENDING_FACULTY],
            ['FC104-003', 'FC- Disaster Management', 'Supplementary Drive', 'Priya Nair', '2026', '2026-12-01', true, null, self::STATUS_PENDING_RAJBHASHA],
        ];

        return collect($rows)->values()->map(fn (array $r, int $i) => [
            'id' => $i + 1,
            'code' => $r[0],
            'name' => $r[1],
            'drive' => $r[2],
            'faculty' => $r[3],
            'batch' => $r[4],
            'deadline' => Carbon::parse($r[5]),
            'original_file' => $r[6] ? $r[0].'_original.pdf' : null,
            // Only a published paper has a translation on file.
            'translated_file' => $r[8] === self::STATUS_PUBLISHED ? $r[0].'_hindi.docx' : null,
            'translated_by' => $r[7],
            'status' => $r[8],
        ]);
    }
}
