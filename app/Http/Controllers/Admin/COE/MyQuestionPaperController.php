<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\COE\UploadTranslationRequest;
use App\Services\COE\RajbhashaPaperService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * COE → Question Paper Management → My Question Paper (Rajbhasha).
 *
 * The Rajbhasha translator's view: view the original, write the Hindi
 * translation in the editor (Translate Paper / Edit Translated Paper), then send
 * it to the faculty for approval. Rows come from RajbhashaPaperService::papers().
 * Every route is gated by menu.permission:coe_my_question_paper_rajbhasha
 * (routes/web.php).
 */
class MyQuestionPaperController extends Controller
{
    use ShowsPaperInViewer;

    /** Badge keys the view knows how to show and act on (also its CSS classes). */
    public const STATUS_PENDING = 'pending';       // waiting on the Rajbhasha translator

    public const STATUS_TRANSLATED = 'translated'; // translation written, not yet sent

    public const STATUS_SENT = 'sent';             // sent for approval; no further edits here

    public const STATUS_PUBLISHED = 'published';   // the faculty verified and published it

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pending at Rajbhasha',
        self::STATUS_TRANSLATED => 'Translated',
        self::STATUS_SENT => 'Sent for Approval',
        self::STATUS_PUBLISHED => 'Published',
    ];

    public function __construct(private RajbhashaPaperService $papers)
    {
    }

    public function index()
    {
        $papers = $this->papers->papers();

        return view('admin.coe.my_question_paper.index', [
            'papers' => $papers,
            'drives' => $papers->pluck('drive')->unique()->sort()->values(),
            'batches' => $papers->pluck('batch')->unique()->sort()->values(),
            'statusLabels' => self::STATUS_LABELS,
        ]);
    }

    /** View Original Paper / See Attachment (original) — read-only viewer. */
    public function original(Request $request, int $paper): JsonResponse
    {
        $qp = $this->papers->find($paper);

        return $this->paperViewer($request, $qp->original_file_path, $qp->original_file_name);
    }

    /** View Translated Paper / See Attachment (translated) — read-only viewer. */
    public function translated(Request $request, int $paper): JsonResponse
    {
        $qp = $this->papers->find($paper);
        abort_unless($qp->translated_file_path, 404);

        return $this->paperViewer($request, $qp->translated_file_path, $qp->translated_file_name);
    }

    /**
     * Translate Question Paper — Translate Paper, or Edit Translated Paper until
     * it is sent. The same editor and templates as the faculty's Add Question Paper.
     */
    public function translate(Request $request, int $paper)
    {
        $qp = $this->papers->find($paper);

        if (! $this->papers->isTranslatable($qp)) {
            return redirect()->route('admin.coe.my-question-paper.index')
                ->with('error', 'This translation has already been sent for approval, so it can no longer be changed.');
        }

        return view('admin.coe.question_paper_editor', [
            'pageTitle' => 'Translate Question Paper',
            'paper' => $this->papers->editorRow($qp),
            'editorHtml' => $this->papers->editorHtmlFor($qp),
            // A first translation starts from the faculty's own paper: it must be changed before upload.
            'startsFromCopy' => $qp->translated_file_path === null,
            'templates' => FacultyQuestionPaperController::TEMPLATES,
            'listUrl' => route('admin.coe.my-question-paper.index'),
            'saveUrl' => route('admin.coe.my-question-paper.translation.store', $paper),
            'watermark' => $this->paperStamp($request),
        ]);
    }

    /** Upload Paper on Translate Question Paper → Translated (not yet sent). */
    public function storeTranslation(UploadTranslationRequest $request, int $paper): JsonResponse
    {
        $wasTranslated = $this->papers->find($paper)->translated_file_path !== null;
        $this->papers->saveTranslation($paper, $request->input('paper_html'));

        return response()->json(['message' => $wasTranslated
            ? 'Translated question paper updated.'
            : 'Translated question paper uploaded.']);
    }

    /** Delete Translated Paper → Pending at Rajbhasha again. */
    public function destroyTranslation(int $paper): JsonResponse
    {
        $this->papers->deleteTranslation($paper);

        return response()->json(['message' => 'Translated question paper deleted.']);
    }

    /** Send for Approval → back to the faculty to verify. */
    public function sendForApproval(int $paper): JsonResponse
    {
        $this->papers->sendForApproval($paper);

        return response()->json(['message' => 'Translated question paper sent for approval.']);
    }
}
