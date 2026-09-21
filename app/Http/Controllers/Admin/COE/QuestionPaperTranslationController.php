<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Models\QuestionPaper;
use App\Models\QuestionPaperFile;
use App\Models\QuestionPaperTranslationRequest;
use App\Services\COE\QuestionPaperAccess;
use App\Services\COE\QuestionPaperAuditService;
use App\Services\COE\QuestionPaperFileService;
use App\Services\COE\QuestionPaperNotificationService;
use App\Services\COE\QuestionPaperWorkflowService;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * COE Examination - the Translation Section's screens.
 *
 * A translated paper is stored as its own file under the target language, so
 * the original the faculty froze is never touched. The section can read the
 * questions but not the answer key - see QuestionPaperAccess.
 */
class QuestionPaperTranslationController extends Controller
{
    public function __construct(
        protected QuestionPaperFileService $files,
        protected QuestionPaperWorkflowService $workflow,
        protected QuestionPaperNotificationService $notifications,
        protected QuestionPaperAuditService $audit
    ) {
    }

    /** Send a frozen paper for translation. Examination Section action. */
    public function markForTranslation(Request $request, $id)
    {
        $paper = QuestionPaper::findOrFail(decrypt($id));

        if (! QuestionPaperAccess::canMarkForTranslation($paper)) {
            return back()->with('error', 'This paper cannot be sent for translation right now.');
        }

        $request->validate([
            'target_language' => 'required|string|in:' . implode(',', array_keys(config('coe.question_paper.languages', []))),
        ]);

        try {
            $translation = $this->workflow->markForTranslation(
                $paper,
                $request->target_language,
                auth()->user()->user_id ?? null
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->notifications->translationAssigned(
            $translation,
            QuestionPaperAccess::translationSectionUserIds()
        );

        return back()->with('success', 'Sent for translation. The Raj Bhasha section has been notified.');
    }

    /** The Translation Section's work list. */
    public function index(Request $request)
    {
        $this->authorizeTranslation();

        $query = QuestionPaperTranslationRequest::with([
            'questionPaper.componentMap.subject',
            'questionPaper.componentMap.component',
            'questionPaper.drive.course',
            'questionPaper.drive.term',
        ]);

        // Completed work stays readable, but the list opens on what is still
        // outstanding.
        $showCompleted = $request->boolean('completed');

        if ($showCompleted) {
            $query->whereIn('status', [
                QuestionPaperTranslationRequest::STATUS_FROZEN,
                QuestionPaperTranslationRequest::STATUS_APPROVED,
            ])->orderByDesc('frozen_at');
        } else {
            $query->open()->orderBy('requested_at');
        }

        return view('admin.coe.question_paper.translation.index', [
            'requests' => $query->paginate(20)->withQueryString(),
            'showCompleted' => $showCompleted,
            'openCount' => QuestionPaperTranslationRequest::open()->count(),
        ]);
    }

    /** One translation: the original to work from, and the upload form. */
    public function show($id)
    {
        $this->authorizeTranslation();

        $translation = QuestionPaperTranslationRequest::with([
            'questionPaper.componentMap.subject',
            'questionPaper.componentMap.component',
            'questionPaper.drive.course',
            'questionPaper.drive.term',
            'questionPaper.currentFiles',
        ])->findOrFail(decrypt($id));

        return view('admin.coe.question_paper.translation.show', [
            'translation' => $translation,
            'paper' => $translation->questionPaper,
            'fileTypes' => QuestionPaperFile::TYPE_LABELS,
        ]);
    }

    /** Upload a translated file. */
    public function upload(Request $request, $id)
    {
        $translation = $this->editableTranslation($id);
        $paper = $translation->questionPaper;

        if (! QuestionPaperAccess::canUploadTranslation($paper)) {
            return back()->with('error', 'This paper is not open for translation.');
        }

        $request->validate([
            'file_type' => 'required|string|in:' . implode(',', array_keys(QuestionPaperFile::TYPE_LABELS)),
            'file' => 'required|file',
        ]);

        // The answer key is not translated, and the section is not given it to
        // read either - accepting one here would put it in their hands.
        if ($request->file_type === QuestionPaperFile::TYPE_ANSWER_KEY) {
            return back()->with('error', 'The answer key is not translated.');
        }

        try {
            $stored = $this->files->store(
                $paper,
                $request->file('file'),
                $request->file_type,
                $translation->target_language,
                auth()->user()->user_id ?? null
            );

            $this->workflow->markTranslationUploaded($translation);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->logFileAccess($stored, QuestionPaperAuditService::ACTION_TRANSLATION_UPLOADED);

        return back()->with('success', 'Translated file uploaded.');
    }

    /** Lock the translation and send the paper for final approval. */
    public function freeze($id)
    {
        $translation = $this->editableTranslation($id);

        try {
            $paper = $this->workflow->freezeTranslation($translation, auth()->user()->user_id ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->notifications->approvalRequired($paper, QuestionPaperAccess::approverUserIds());

        return back()->with('success', 'Translation frozen and sent for final approval.');
    }

    protected function editableTranslation($id): QuestionPaperTranslationRequest
    {
        $this->authorizeTranslation();

        $translation = QuestionPaperTranslationRequest::with('questionPaper')->findOrFail(decrypt($id));

        if (! $translation->questionPaper) {
            abort(404);
        }

        if (! $translation->isEditable()) {
            abort(403, 'This translation is already frozen.');
        }

        return $translation;
    }

    protected function authorizeTranslation(): void
    {
        if (! QuestionPaperAccess::isTranslationSection()) {
            abort(403, 'You are not authorised to work on translations.');
        }
    }
}
