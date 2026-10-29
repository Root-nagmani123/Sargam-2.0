<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Services\COE\AdminPaperService;
use App\Services\COE\QuestionPaperLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * COE → Question Paper Management → Question Paper (the administrator's list).
 *
 * Rows come from AdminPaperService::papers(): every subject mapped on a drive,
 * with its paper's progress and files. Papers are viewed (See Attachment) and
 * printed (Print … Paper) through the read-only viewer, never downloaded; a
 * published paper can be unfrozen.
 *
 * Two scopes, each its own URL (docs/new-design-index-page.md §1 "Scope tabs"):
 *   ?scope=all        every paper (default)
 *   ?scope=translated only papers with a Rajbhasha translation
 */
class QuestionPaperListController extends Controller
{
    use ShowsPaperInViewer;

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
        private QuestionPaperLogger $log,
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

    /** See Attachment / Print Original Paper (?print=1) — never a download. */
    public function original(Request $request, int $paper): JsonResponse
    {
        $qp = $this->papers->originalFor($paper);
        $response = $this->paperViewer($request, $qp->original_file_path, $qp->original_file_name);
        $this->log->record(QuestionPaperLogger::ROLE_COE, QuestionPaperLogger::MODULE_PAPER, $this->viewAction($request), $qp, 'V'.$qp->version);

        return $response;
    }

    /** See Attachment / Print Translated Paper (?print=1) — never a download. */
    public function translated(Request $request, int $paper): JsonResponse
    {
        $qp = $this->papers->translationFor($paper);
        $response = $this->paperViewer($request, $qp->translated_file_path, $qp->translated_file_name);
        $this->log->record(QuestionPaperLogger::ROLE_COE, QuestionPaperLogger::MODULE_TRANSLATION, $this->viewAction($request), $qp);

        return $response;
    }

    /** Unfreeze (Published only) → back to the faculty's Pending Verification. */
    public function unfreeze(int $paper): JsonResponse
    {
        $qp = $this->papers->unfreeze($paper);
        $this->log->record(QuestionPaperLogger::ROLE_COE, QuestionPaperLogger::MODULE_PAPER, QuestionPaperLogger::UNFROZEN, $qp, 'V'.$qp->version);

        return response()->json(['message' => 'Question paper unfrozen. It is back with the faculty for verification.']);
    }

    /** Delete: the paper and its files go; the subject is Pending at Faculty again. */
    public function destroy(int $paper): JsonResponse
    {
        $this->papers->delete($paper);
        $this->log->record(QuestionPaperLogger::ROLE_COE, QuestionPaperLogger::MODULE_PAPER, QuestionPaperLogger::DELETED, $paper);

        return response()->json(['message' => 'Question paper deleted.']);
    }

    /** The Print buttons ask with ?print=1; See Attachment does not. */
    private function viewAction(Request $request): string
    {
        return $request->boolean('print') ? QuestionPaperLogger::PRINTED : QuestionPaperLogger::VIEWED;
    }
}
