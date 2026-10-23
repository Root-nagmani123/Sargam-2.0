<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Services\COE\AdminPaperService;
use App\Services\COE\QuestionPaperStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * COE → Question Paper Management → Question Paper (the administrator's list).
 *
 * Rows come from AdminPaperService::papers(): every subject mapped on a drive,
 * with its paper's progress and files.
 *
 * Two scopes, each its own URL (docs/new-design-index-page.md §1 "Scope tabs"):
 *   ?scope=all        every paper (default)
 *   ?scope=translated only papers with a Rajbhasha translation
 */
class QuestionPaperListController extends Controller
{
    /** Badge keys the view shows (also its CSS classes). */
    public const STATUS_PENDING_FACULTY = 'pending-faculty';     // with the faculty: to write, or to verify

    public const STATUS_PENDING_RAJBHASHA = 'pending-rajbhasha'; // waiting on the translator

    public const STATUS_PUBLISHED = 'published';                 // translated and released

    public const STATUS_LABELS = [
        self::STATUS_PENDING_FACULTY => 'Pending at Faculty',
        self::STATUS_PENDING_RAJBHASHA => 'Pending at Rajbhasha',
        self::STATUS_PUBLISHED => 'Published',
    ];

    public const SCOPES = ['all', 'translated'];

    public function __construct(
        private AdminPaperService $papers,
        private QuestionPaperStorage $files,
    ) {
    }

    public function index(Request $request)
    {
        $scope = in_array($request->query('scope'), self::SCOPES, true) ? $request->query('scope') : 'all';

        $papers = $this->papers->papers();
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

    /** See Attachment / Download Original Paper. */
    public function original(int $paper): StreamedResponse
    {
        $qp = $this->papers->originalFor($paper);

        return $this->files->download($qp->original_file_path, $qp->original_file_name);
    }

    /** See Attachment / Download Translated Paper. */
    public function translated(int $paper): StreamedResponse
    {
        $qp = $this->papers->translationFor($paper);

        return $this->files->download($qp->translated_file_path, $qp->translated_file_name);
    }

    /** Delete: the paper and its files go; the subject is Pending at Faculty again. */
    public function destroy(int $paper): JsonResponse
    {
        $this->papers->delete($paper);

        return response()->json(['message' => 'Question paper deleted.']);
    }
}
