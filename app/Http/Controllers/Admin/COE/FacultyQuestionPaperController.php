<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\COE\FreezeQuestionPaperRequest;
use App\Http\Requests\Admin\COE\UploadQuestionPaperRequest;
use App\Http\Requests\Admin\COE\UploadTranslationRequest;
use App\Services\COE\QuestionPaperLogger;
use App\Services\COE\QuestionPaperNotifier;
use App\Services\COE\QuestionPaperService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * COE → Question Paper Management → My Question Paper, as the FACULTY sees it
 * (the setter writes the original paper in the editor - from a template or from
 * scratch - which goes straight to Rajbhasha for translation - no freeze - and
 * can edit it while it waits there).
 *
 * Once Rajbhasha sends the translation back, the faculty verifies it and
 * freezes it with an OTP to publish - or edits the original again, which sends
 * it back to Rajbhasha. After COE unfreezes a published paper, the faculty may
 * also edit the translation itself. The rows and their shape come from
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
        // ready: complete as filled from the paper, so it may be uploaded unedited.
        'omr' => [
            'title' => 'OMR Answer Sheet',
            'description' => 'A bubble answer sheet for objective exams, filled from this paper.',
            'meta' => '100 questions • 4 options',
            'ready' => true,
        ],
    ];

    public function __construct(
        private QuestionPaperService $papers,
        private QuestionPaperLogger $log,
        private QuestionPaperNotifier $notify,
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
            'hiddenUrl' => route('admin.coe.faculty-question-paper.hidden', $paper),
            'setsUrl' => route('admin.coe.faculty-question-paper.sets', $paper),
            'watermark' => $this->paperStamp($request),
        ]);
    }

    /**
     * Create multiple paper sets: one master paper shuffled per set, or
     * different questions in each set. ?mode=independent opens the second.
     * Same access as Add Question Paper.
     *
     * shortcut: design-first - the paper's facts are real but the questions are
     * SAMPLE data and nothing is saved; replace sampleSets() with stored
     * sections/questions (and guard the page like the editor) when sets are wired.
     */
    public function sets(Request $request, int $paper)
    {
        $row = $this->papers->findForFaculty($this->facultyPk(), $paper);
        abort_if($row === null, 404);

        if (! $row['editable']) {
            return redirect()->route('admin.coe.faculty-question-paper.index')
                ->with('error', $this->papers->lockedMessage($row));
        }

        return view('admin.coe.faculty_question_paper.sets', [
            'paper' => $row,
            'mode' => $request->query('mode') === 'independent' ? 'independent' : 'shuffled',
            'editorUrl' => route('admin.coe.faculty-question-paper.create', $paper),
            'sample' => $this->sampleSets(),
        ]);
    }

    /**
     * SAMPLE blueprint and questions for the sets page (see sets()).
     *
     * @return array{sections: list<array{title:string, kind:string, count:int, marks:int}>, master: list<array{s:int, text:string}>, independent: list<list<array{s:int, text:string}>>}
     */
    private function sampleSets(): array
    {
        $master = [
            ['s' => 0, 'text' => 'Explain the relationship between law and ethics.'],
            ['s' => 0, 'text' => 'What is meant by integrity in public service?'],
            ['s' => 0, 'text' => 'Define conflict of interest with one example.'],
            ['s' => 0, 'text' => 'Distinguish between accountability and responsibility.'],
            ['s' => 0, 'text' => 'What is the role of a code of conduct?'],
            ['s' => 1, 'text' => 'Discuss ethical decision-making in public administration.'],
            ['s' => 1, 'text' => 'Examine how transparency strengthens institutional accountability.'],
            ['s' => 1, 'text' => 'Analyse a conflict between legal duty and moral judgement.'],
            ['s' => 1, 'text' => 'Evaluate the role of integrity in preventing corruption.'],
            ['s' => 1, 'text' => 'Explain the principles of natural justice with suitable examples.'],
        ];

        return [
            'sections' => [
                ['title' => 'Section A · Short answers', 'kind' => 'short-answer', 'count' => 5, 'marks' => 4],
                ['title' => 'Section B · Long answers', 'kind' => 'long-answer', 'count' => 5, 'marks' => 16],
            ],
            'master' => $master,
            // Independent mode: Set A complete, Set B part-built, the rest empty.
            'independent' => [
                $master,
                [
                    ['s' => 0, 'text' => 'Define the concept of administrative discretion.'],
                    ['s' => 0, 'text' => 'Explain the importance of impartiality in public service.'],
                    ['s' => 0, 'text' => 'What is meant by the rule of law?'],
                    ['s' => 0, 'text' => 'Describe one safeguard against misuse of public office.'],
                    ['s' => 0, 'text' => 'How does a citizen charter promote accountability?'],
                    ['s' => 1, 'text' => 'Discuss the ethical challenges of using discretion in governance.'],
                    ['s' => 1, 'text' => 'Evaluate the relationship between civil service neutrality and democracy.'],
                    ['s' => 1, 'text' => 'Analyse the role of ethical leadership in organisational culture.'],
                ],
            ],
        ];
    }

    /**
     * The editor covered the paper (window left / PrintScreen) - logged, since a
     * capture tool outside the browser takes its shot just before. A new paper
     * has no row yet, so its code goes in the detail.
     */
    public function hidden(Request $request, int $paper): Response
    {
        $reason = $request->validate(['reason' => 'required|in:'.implode(',', array_keys(QuestionPaperLogger::HIDDEN_REASONS))])['reason'];
        $facultyPk = $this->facultyPk();
        $row = $this->papers->findForFaculty($facultyPk, $paper);
        abort_if($row === null, 404);

        $qp = $this->papers->paperFor($facultyPk, $paper);
        $detail = QuestionPaperLogger::HIDDEN_REASONS[$reason];
        $this->log->record(QuestionPaperLogger::ROLE_FACULTY, QuestionPaperLogger::MODULE_PAPER, QuestionPaperLogger::HIDDEN,
            $qp, $qp ? $detail : $row['code'].' · '.$detail);

        return response()->noContent();
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
        $this->notify->sentForTranslation($qp);

        return response()->json(['message' => $qp->version > 1
            ? 'Question paper updated (V'.$qp->version.') and sent to Rajbhasha for translation.'
            : 'Question paper uploaded and sent for translation.']);
    }

    /**
     * Edit Translated Paper — once COE has unfrozen the published paper, the
     * faculty corrects the translation in the editor themselves.
     */
    public function editTranslation(Request $request, int $paper)
    {
        $facultyPk = $this->facultyPk();
        $row = $this->papers->findForFaculty($facultyPk, $paper);
        abort_if($row === null, 404);

        try {
            $html = $this->papers->translationHtmlFor($facultyPk, $paper);
        } catch (ValidationException $e) {
            return redirect()->route('admin.coe.faculty-question-paper.index')
                ->with('error', collect($e->errors())->flatten()->first());
        }

        // The page holds the translation: logged like a view.
        $qp = $this->papers->paperFor($facultyPk, $paper);
        $this->log->record(QuestionPaperLogger::ROLE_FACULTY, QuestionPaperLogger::MODULE_TRANSLATION, QuestionPaperLogger::OPENED, $qp, 'of V'.$qp->version);

        return view('admin.coe.question_paper_editor', [
            'pageTitle' => 'Edit Translated Paper',
            'paper' => $row,
            'editorHtml' => $html,
            'templates' => self::TEMPLATES,
            'listUrl' => route('admin.coe.faculty-question-paper.index'),
            'saveUrl' => route('admin.coe.faculty-question-paper.translation.store', $paper),
            'hiddenUrl' => route('admin.coe.faculty-question-paper.hidden', $paper),
            'watermark' => $this->paperStamp($request),
        ]);
    }

    /** Upload Paper on Edit Translated Paper: still Pending Verification, to freeze. */
    public function storeTranslation(UploadTranslationRequest $request, int $paper): JsonResponse
    {
        $qp = $this->papers->saveTranslatedPaper($this->facultyPk(), $paper, $request->input('paper_html'));
        $this->log->record(QuestionPaperLogger::ROLE_FACULTY, QuestionPaperLogger::MODULE_TRANSLATION, QuestionPaperLogger::UPDATED, $qp, 'of V'.$qp->version);

        return response()->json(['message' => 'Translated question paper updated. Freeze it to publish.']);
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
        $this->notify->published($qp);

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
