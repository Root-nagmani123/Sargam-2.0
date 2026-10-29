<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\COE\FreezeQuestionPaperRequest;
use App\Http\Requests\Admin\COE\UploadQuestionPaperRequest;
use App\Services\COE\QuestionPaperLogger;
use App\Services\COE\QuestionPaperService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * COE → Question Paper Management → My Question Paper, as the FACULTY sees it
 * (the setter writes the original paper in the editor - from a template or from
 * scratch - which goes straight to Rajbhasha for translation - no freeze - and
 * can edit it while it waits there).
 *
 * Once Rajbhasha sends the translation back, the faculty verifies it and
 * freezes it with an OTP to publish - or edits the original again, which sends
 * it back to Rajbhasha. The rows and their shape come from
 * QuestionPaperService::papersForFaculty().
 */
class FacultyQuestionPaperController extends Controller
{
    use ShowsPaperInViewer;

    /**
     * Template cards on the paper editor (Add Question Paper, and Rajbhasha's
     * Translate Question Paper), in order. Each key has a starting document in
     * admin/coe/faculty_question_paper/templates/{key}.blade.php.
     */
    public const TEMPLATES = [
        'standard' => [
            'title' => 'Standard Question Paper',
            'description' => 'A clean, single-column layout for most exams.',
            'meta' => '1 column • 100 marks',
        ],
        'multi_section' => [
            'title' => 'Multi-Section Paper',
            'description' => 'Best for exams with multiple sections and time allocations.',
            'meta' => '3 sections • 150 marks',
        ],
        'case_study' => [
            'title' => 'Case Study Format',
            'description' => 'Ideal for long-form case studies and scenario-based questions.',
            'meta' => '2 pages • 200 marks',
        ],
    ];

    public function __construct(
        private QuestionPaperService $papers,
        private QuestionPaperLogger $log,
    ) {
    }

    public function index()
    {
        $facultyPk = $this->papers->currentFacultyPk();
        $papers = $facultyPk ? $this->papers->papersForFaculty($facultyPk) : collect();

        return view('admin.coe.faculty_question_paper.index', [
            'papers' => $papers,
            'drives' => $papers->pluck('drive')->unique()->sort()->values(),
            'batches' => $papers->pluck('batch')->unique()->sort()->values(),
            'statusLabels' => QuestionPaperService::FACULTY_STATUS_LABELS,
            'otpPhoneMasked' => $facultyPk ? $this->papers->otpPhoneMasked($facultyPk) : '',
        ]);
    }

    /**
     * Add Question Paper — pick a template or write the paper from scratch in the
     * editor. Also "Edit Original Paper" while it is Pending at Rajbhasha; once
     * Rajbhasha has translated it, it goes back to the list.
     */
    public function create(Request $request, int $paper)
    {
        $facultyPk = $this->facultyPk();
        $row = $this->papers->findForFaculty($facultyPk, $paper);
        abort_if($row === null, 404);

        if (! $row['editable']) {
            return redirect()->route('admin.coe.faculty-question-paper.index')
                ->with('error', $this->papers->lockedMessage($row));
        }

        return view('admin.coe.question_paper_editor', [
            'pageTitle' => 'Add Question Paper',
            'paper' => $row,
            'editorHtml' => $this->papers->editorHtmlFor($facultyPk, $paper),
            'templates' => self::TEMPLATES,
            'listUrl' => route('admin.coe.faculty-question-paper.index'),
            'saveUrl' => route('admin.coe.faculty-question-paper.upload', $paper),
            'watermark' => $this->paperStamp($request),
        ]);
    }

    /**
     * Add Question Paper (Upload Paper): the first paper is V1 and goes to
     * Rajbhasha; an edit before Rajbhasha translates it is the next version.
     */
    public function upload(UploadQuestionPaperRequest $request, int $paper): JsonResponse
    {
        $qp = $this->papers->saveOriginalPaper($this->facultyPk(), $paper, $request->input('paper_html'));
        $this->log->record(QuestionPaperLogger::ROLE_FACULTY, QuestionPaperLogger::MODULE_PAPER,
            $qp->version > 1 ? QuestionPaperLogger::UPDATED : QuestionPaperLogger::CREATED, $qp, 'V'.$qp->version);

        return response()->json(['message' => $qp->version > 1
            ? 'Question paper updated (V'.$qp->version.') and sent to Rajbhasha for translation.'
            : 'Question paper uploaded and sent for translation.']);
    }

    /** Freeze, step 1 (modal opened / Resend OTP): sends the OTP. */
    public function freezeOtp(int $paper): JsonResponse
    {
        $this->papers->sendFreezeOtp($this->facultyPk(), $paper);

        return response()->json(['message' => 'OTP sent.']);
    }

    /** Freeze, step 2: the right OTP publishes a translation the faculty has verified. */
    public function freeze(FreezeQuestionPaperRequest $request, int $paper): JsonResponse
    {
        $qp = $this->papers->freeze($this->facultyPk(), $paper, $request->input('otp'));
        $this->log->record(QuestionPaperLogger::ROLE_FACULTY, QuestionPaperLogger::MODULE_PAPER, QuestionPaperLogger::FROZEN, $qp, 'V'.$qp->version);

        return response()->json(['message' => 'Question paper verified and published.']);
    }

    /** See Attachment / View Original Paper — read-only viewer, never a download. */
    public function original(Request $request, int $paper): JsonResponse
    {
        $qp = $this->papers->originalPaperFor($this->facultyPk(), $paper);
        $response = $this->paperViewer($request, $qp->original_file_path, $qp->original_file_name);
        $this->log->record(QuestionPaperLogger::ROLE_FACULTY, QuestionPaperLogger::MODULE_PAPER, QuestionPaperLogger::VIEWED, $qp, 'V'.$qp->version);

        return $response;
    }

    /** See Attachment / View Translated Paper, once Rajbhasha has sent it. */
    public function translated(Request $request, int $paper): JsonResponse
    {
        $qp = $this->papers->translatedPaperFor($this->facultyPk(), $paper);
        $response = $this->paperViewer($request, $qp->translated_file_path, $qp->translated_file_name);
        $this->log->record(QuestionPaperLogger::ROLE_FACULTY, QuestionPaperLogger::MODULE_TRANSLATION, QuestionPaperLogger::VIEWED, $qp);

        return $response;
    }

    /** The logged-in faculty, or 404: no one else has papers here. */
    private function facultyPk(): int
    {
        $facultyPk = $this->papers->currentFacultyPk();
        abort_unless($facultyPk, 404);

        return $facultyPk;
    }
}
