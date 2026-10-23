<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\COE\UploadTranslationRequest;
use App\Services\COE\QuestionPaperStorage;
use App\Services\COE\RajbhashaPaperService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * COE → Question Paper Management → My Question Paper (Rajbhasha).
 *
 * The Rajbhasha translator's view: download the original, upload the Hindi
 * translation, then send it to the faculty for approval. Rows come from
 * RajbhashaPaperService::papers(). Every route is gated by
 * menu.permission:coe_my_question_paper_rajbhasha (routes/web.php).
 */
class MyQuestionPaperController extends Controller
{
    /** Badge keys the view knows how to show and act on (also its CSS classes). */
    public const STATUS_PENDING = 'pending';       // waiting on the Rajbhasha translator

    public const STATUS_TRANSLATED = 'translated'; // translation uploaded, not yet sent

    public const STATUS_SENT = 'sent';             // sent for approval; no further edits here

    public const STATUS_PUBLISHED = 'published';   // the faculty verified and published it

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pending at Rajbhasha',
        self::STATUS_TRANSLATED => 'Translated',
        self::STATUS_SENT => 'Sent for Approval',
        self::STATUS_PUBLISHED => 'Published',
    ];

    public function __construct(
        private RajbhashaPaperService $papers,
        private QuestionPaperStorage $files,
    ) {
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

    /** Download Original Paper / See Attachment (original). */
    public function original(int $paper): StreamedResponse
    {
        $qp = $this->papers->find($paper);

        return $this->files->download($qp->original_file_path, $qp->original_file_name);
    }

    /** See Attachment (translated). */
    public function translated(int $paper): StreamedResponse
    {
        $qp = $this->papers->find($paper);
        abort_unless($qp->translated_file_path, 404);

        return $this->files->download($qp->translated_file_path, $qp->translated_file_name);
    }

    /** Upload Translated Paper → Translated. */
    public function storeTranslation(UploadTranslationRequest $request, int $paper): JsonResponse
    {
        $this->papers->addTranslation($paper, $request->file('translated_paper'));

        return response()->json(['message' => 'Translated question paper uploaded.']);
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
