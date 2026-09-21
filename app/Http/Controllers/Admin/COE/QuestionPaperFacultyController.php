<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Models\QuestionPaper;
use App\Models\QuestionPaperFile;
use App\Services\COE\QuestionPaperAccess;
use App\Services\COE\QuestionPaperAuditService;
use App\Services\COE\QuestionPaperFileService;
use App\Services\COE\QuestionPaperNotificationService;
use App\Services\COE\QuestionPaperWorkflowService;
use App\Support\COE\QuestionPaperStatus;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * COE Examination - the paper setter's own screens.
 *
 * A faculty member sees only the papers assigned to them, and only until they
 * freeze one. Everything here is scoped by QuestionPaperAccess rather than by
 * a route prefix: holding an examination role does not let someone upload into
 * another person's paper.
 */
class QuestionPaperFacultyController extends Controller
{
    public function __construct(
        protected QuestionPaperFileService $files,
        protected QuestionPaperWorkflowService $workflow,
        protected QuestionPaperNotificationService $notifications,
        protected QuestionPaperAuditService $audit
    ) {
    }

    /** "My Assigned Question Papers". */
    public function index()
    {
        $facultyPk = QuestionPaperAccess::currentFacultyPk();

        if (! $facultyPk) {
            abort(403, 'This screen is for faculty members.');
        }

        $papers = QuestionPaper::with([
            'drive.course',
            'drive.term',
            'componentMap.subject',
            'componentMap.component',
            'currentFiles',
            'pendingUnfreezeRequest',
        ])
            ->forFaculty($facultyPk)
            // Papers still to be written come first; finalized ones sink.
            ->orderByRaw('CASE WHEN status = ? THEN 1 ELSE 0 END', [QuestionPaperStatus::FINALIZED])
            ->orderBy('deadline')
            ->orderBy('id')
            ->get();

        return view('admin.coe.question_paper.faculty.index', compact('papers'));
    }

    /** One paper: upload, preview, freeze. */
    public function show($id)
    {
        $paper = $this->facultyPaper($id);

        $paper->load(['drive.course', 'drive.term', 'componentMap.subject', 'componentMap.component',
            'currentFiles', 'versions', 'unfreezeRequests']);

        return view('admin.coe.question_paper.faculty.show', [
            'paper' => $paper,
            'fileTypes' => QuestionPaperFile::TYPE_LABELS,
        ]);
    }

    /** Upload or replace one file. */
    public function upload(Request $request, $id)
    {
        $paper = $this->facultyPaper($id);

        if (! QuestionPaperAccess::canUpload($paper)) {
            return back()->with('error', 'This paper is locked and cannot be changed.');
        }

        $request->validate([
            'file_type' => 'required|string|in:' . implode(',', array_keys(QuestionPaperFile::TYPE_LABELS)),
            'file' => 'required|file',
        ]);

        try {
            // The service does the real checking - extension, detected MIME and
            // size - because those rules guard storage, not just this form.
            $stored = $this->files->store(
                $paper,
                $request->file('file'),
                $request->file_type,
                config('coe.question_paper.original_language', 'EN'),
                auth()->user()->user_id ?? null
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->logFileAccess($stored, QuestionPaperAuditService::ACTION_FILE_UPLOADED);

        // A first upload moves the paper out of Draft; an unfrozen paper stays
        // unfrozen until it is frozen again.
        if ($paper->status === QuestionPaperStatus::DRAFT) {
            $paper->update([
                'status' => QuestionPaperStatus::UPLOADED,
                'modified_date' => now(),
            ]);
        }

        return back()->with('success', 'File uploaded.');
    }

    /** Remove an optional attachment before freezing. */
    public function removeFile($id, $fileId)
    {
        $paper = $this->facultyPaper($id);

        if (! QuestionPaperAccess::canUpload($paper)) {
            return back()->with('error', 'This paper is locked and cannot be changed.');
        }

        $file = QuestionPaperFile::where('question_paper_id', $paper->id)
            ->where('id', $fileId)
            ->current()
            ->firstOrFail();

        // The question paper itself is replaced, never removed: a paper with no
        // paper on it is not a state worth allowing.
        if (in_array($file->file_type, QuestionPaperFile::REQUIRED_TYPES, true)) {
            return back()->with('error', 'The question paper cannot be removed. Upload a new file to replace it.');
        }

        $this->files->supersede($file);

        $this->audit->logFileAccess($file, QuestionPaperAuditService::ACTION_FILE_REMOVED);

        return back()->with('success', 'File removed.');
    }

    /** Lock the paper. */
    public function freeze($id)
    {
        $paper = $this->facultyPaper($id);

        if (! QuestionPaperAccess::canFreeze($paper)) {
            return back()->with('error', 'This paper cannot be frozen right now.');
        }

        try {
            $this->workflow->freeze($paper, auth()->user()->user_id ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Question paper frozen. It can no longer be edited.');
    }

    /** Ask the Examination Section to reopen a frozen paper. */
    public function requestUnfreeze(Request $request, $id)
    {
        $paper = $this->facultyPaper($id);

        if (! QuestionPaperAccess::canRequestUnfreeze($paper)) {
            return back()->with('error', 'An unfreeze request cannot be raised for this paper right now.');
        }

        $request->validate([
            // The reason is the record of why a locked paper was reopened, so a
            // one-word entry is not enough.
            'reason' => 'required|string|min:10|max:1000',
        ]);

        try {
            $unfreeze = $this->workflow->requestUnfreeze(
                $paper,
                $request->reason,
                auth()->user()->user_id ?? null
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->notifications->unfreezeRequested(
            $unfreeze,
            QuestionPaperAccess::examSectionUserIds()
        );

        return back()->with('success', 'Unfreeze request submitted. The Examination Section has been notified.');
    }

    /**
     * Resolve a paper the signed-in faculty member owns.
     *
     * 404 rather than 403 for someone else's paper: a 403 would confirm which
     * subjects have papers, which is itself worth withholding before an exam.
     */
    protected function facultyPaper($id): QuestionPaper
    {
        $paper = QuestionPaper::find(decrypt($id));

        if (! $paper || ! QuestionPaperAccess::isAssignedFaculty($paper)) {
            abort(404);
        }

        return $paper;
    }
}
