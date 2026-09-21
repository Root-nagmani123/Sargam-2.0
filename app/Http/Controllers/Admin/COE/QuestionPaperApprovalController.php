<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Models\QuestionPaper;
use App\Services\COE\QuestionPaperAccess;
use App\Services\COE\QuestionPaperNotificationService;
use App\Services\COE\QuestionPaperWorkflowService;
use App\Support\COE\QuestionPaperStatus;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * COE Examination - final approval of a translated question paper.
 *
 * The last gate before a paper is locked for the examination. The approver
 * reads the original and the translation side by side on the paper's detail
 * screen; this handles the queue and the decision itself.
 */
class QuestionPaperApprovalController extends Controller
{
    public function __construct(
        protected QuestionPaperWorkflowService $workflow,
        protected QuestionPaperNotificationService $notifications
    ) {
    }

    /** Papers whose translation is frozen and awaiting approval. */
    public function index(Request $request)
    {
        $this->authorizeApprover();

        $query = QuestionPaper::with([
            'componentMap.subject',
            'componentMap.component',
            'faculty',
            'drive.course',
            'drive.term',
            'currentFiles',
        ]);

        // Finalized papers stay readable, but the queue opens on what is still
        // waiting on this person.
        $showFinalized = $request->boolean('finalized');

        if ($showFinalized) {
            $query->where('status', QuestionPaperStatus::FINALIZED)
                ->orderByDesc('finalized_at');
        } else {
            $query->where('status', QuestionPaperStatus::TRANSLATED)
                ->orderBy('id');
        }

        if ($driveId = (int) $request->input('examination_drive_id')) {
            $query->forDrive($driveId);
        }

        return view('admin.coe.question_paper.approval.index', [
            'papers' => $query->paginate(20)->withQueryString(),
            'showFinalized' => $showFinalized,
            'pendingCount' => QuestionPaper::where('status', QuestionPaperStatus::TRANSLATED)->count(),
        ]);
    }

    /** Approve and lock the paper. */
    public function finalize($id)
    {
        $this->authorizeApprover();

        $paper = QuestionPaper::findOrFail(decrypt($id));

        if (! QuestionPaperAccess::canFinalize($paper)) {
            return back()->with('error', 'This paper cannot be finalized right now.');
        }

        try {
            // Re-hashes every stored file first: a finalized paper is the one
            // that goes to the examination hall.
            $this->workflow->finalize($paper, auth()->user()->user_id ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->notifications->finalized($paper->refresh());

        return back()->with('success', 'Question paper finalized. It is now locked.');
    }

    protected function authorizeApprover(): void
    {
        if (! QuestionPaperAccess::isApprover()) {
            abort(403, 'You are not authorised to approve question papers.');
        }
    }
}
