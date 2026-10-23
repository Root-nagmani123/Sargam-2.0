<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\COE\FreezeQuestionPaperRequest;
use App\Http\Requests\Admin\COE\UploadQuestionPaperRequest;
use App\Services\COE\QuestionPaperService;
use App\Services\COE\QuestionPaperStorage;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * COE → Question Paper Management → My Question Paper, as the FACULTY sees it
 * (the setter uploads the original paper, which goes straight to Rajbhasha for
 * translation - no freeze - and can replace it until Rajbhasha translates it).
 *
 * Once Rajbhasha sends the translation back, the faculty verifies it and
 * freezes it with an OTP to publish. The rows and their shape come from
 * QuestionPaperService::papersForFaculty().
 */
class FacultyQuestionPaperController extends Controller
{
    public function __construct(
        private QuestionPaperService $papers,
        private QuestionPaperStorage $files,
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
     * Add Question Paper — upload a .docx or write the paper in the editor. Also
     * "Reupload or Edit Original Paper" while it is Pending at Rajbhasha; once
     * Rajbhasha has translated it, it goes back to the list.
     */
    public function create(int $paper)
    {
        $facultyPk = $this->facultyPk();
        $row = $this->papers->findForFaculty($facultyPk, $paper);
        abort_if($row === null, 404);

        if (! $row['editable']) {
            return redirect()->route('admin.coe.faculty-question-paper.index')
                ->with('error', 'Rajbhasha has already translated this question paper, so it can no longer be changed.');
        }

        return view('admin.coe.faculty_question_paper.create', [
            'paper' => $row,
            'editorHtml' => $this->papers->editorHtmlFor($facultyPk, $paper),
        ]);
    }

    /**
     * Add Question Paper (submit): the first paper is V1 and goes to Rajbhasha;
     * a reupload / edit before Rajbhasha translates it is the next version.
     */
    public function upload(UploadQuestionPaperRequest $request, int $paper): JsonResponse
    {
        $qp = $this->papers->saveOriginalPaper(
            $this->facultyPk(),
            $paper,
            $request->isEditorPaper() ? null : $request->file('question_paper'),
            $request->isEditorPaper() ? $request->input('paper_html') : null
        );

        return response()->json(['message' => $qp->version > 1
            ? 'Question paper updated (V'.$qp->version.').'
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
        $this->papers->freeze($this->facultyPk(), $paper, $request->input('otp'));

        return response()->json(['message' => 'Question paper verified and published.']);
    }

    /** See Attachment / Download Original Paper. */
    public function original(int $paper): StreamedResponse
    {
        $qp = $this->papers->originalPaperFor($this->facultyPk(), $paper);

        return $this->files->download($qp->original_file_path, $qp->original_file_name);
    }

    /** See Attachment / Download Translated Paper, once Rajbhasha has sent it. */
    public function translated(int $paper): StreamedResponse
    {
        $qp = $this->papers->translatedPaperFor($this->facultyPk(), $paper);

        return $this->files->download($qp->translated_file_path, $qp->translated_file_name);
    }

    /** The logged-in faculty, or 404: no one else has papers here. */
    private function facultyPk(): int
    {
        $facultyPk = $this->papers->currentFacultyPk();
        abort_unless($facultyPk, 404);

        return $facultyPk;
    }
}
