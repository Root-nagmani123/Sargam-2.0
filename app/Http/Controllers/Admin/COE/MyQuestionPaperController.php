<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * COE → Question Paper Management → My Question Paper.
 *
 * The Rajbhasha translator's view: download the original, upload the Hindi
 * translation, then send it for approval.
 *
 * Design-first screen: the grid, filters, upload modal and Send for Approval
 * confirm are built against the row shape below. The rows are SAMPLE data — no table backs
 * this page yet. Replace sampleRows() with the real query when the COE question
 * paper module lands; the view only reads the keys listed there.
 */
class MyQuestionPaperController extends Controller
{
    /** Status keys the view knows how to badge and act on. */
    public const STATUS_PENDING = 'pending';       // waiting on the Rajbhasha translator

    public const STATUS_TRANSLATED = 'translated'; // translation uploaded, not yet sent

    public const STATUS_SENT = 'sent';             // sent for approval; no further edits here

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pending at Rajbhasha',
        self::STATUS_TRANSLATED => 'Translated',
        self::STATUS_SENT => 'Sent for Approval',
    ];

    public function index()
    {
        $papers = $this->sampleRows();

        return view('admin.coe.my_question_paper.index', [
            'papers' => $papers,
            'drives' => $papers->pluck('drive')->unique()->sort()->values(),
            'batches' => $papers->pluck('batch')->unique()->sort()->values(),
            'statusLabels' => self::STATUS_LABELS,
        ]);
    }

    /**
     * @return Collection<int, array{id:int, code:string, name:string, drive:string, batch:string,
     *     deadline:Carbon, faculty:string, original_file:string, translated_file:?string, status:string}>
     */
    private function sampleRows(): Collection
    {
        $rows = [
            ['FC101-001', 'FC- FC 103', 'Mid Term Drive', '2026', '2026-10-05', 'John Doe', null, self::STATUS_PENDING],
            ['FC101-002', 'FC- Public Administration', 'Mid Term Drive', '2026', '2026-10-07', 'Anita Sharma', null, self::STATUS_PENDING],
            ['FC102-001', 'FC- Indian Economy', 'End Term Drive', '2026', '2026-11-12', 'Rahul Verma', 'FC102-001_hindi.docx', self::STATUS_TRANSLATED],
            ['FC102-004', 'FC- Constitutional Law', 'End Term Drive', '2025', '2026-11-15', 'Meera Iyer', 'FC102-004_hindi.docx', self::STATUS_SENT],
            ['FC103-002', 'FC- Ethics & Integrity', 'Mid Term Drive', '2025', '2026-10-20', 'Sanjay Gupta', null, self::STATUS_PENDING],
            ['FC104-003', 'FC- Disaster Management', 'Supplementary Drive', '2026', '2026-12-01', 'Priya Nair', 'FC104-003_hindi.docx', self::STATUS_TRANSLATED],
        ];

        return collect($rows)->values()->map(fn (array $r, int $i) => [
            'id' => $i + 1,
            'code' => $r[0],
            'name' => $r[1],
            'drive' => $r[2],
            'batch' => $r[3],
            'deadline' => Carbon::parse($r[4]),
            'faculty' => $r[5],
            'original_file' => $r[0].'_original.pdf',
            'translated_file' => $r[6],
            'status' => $r[7],
        ]);
    }
}
