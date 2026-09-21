<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Models\QuestionPaperUnfreezeRequest;
use App\Services\COE\QuestionPaperAccess;
use App\Services\COE\QuestionPaperNotificationService;
use App\Services\COE\QuestionPaperWorkflowService;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * COE Examination - the Examination Section's unfreeze inbox.
 *
 * Freezing a paper is meant to be final, so reopening one is a decision an
 * officer makes on the record rather than something the author can do alone.
 * This is where those requests are read and decided.
 */
class QuestionPaperUnfreezeController extends Controller
{
    public function __construct(
        protected QuestionPaperWorkflowService $workflow,
        protected QuestionPaperNotificationService $notifications
    ) {
    }

    /** Requests awaiting a decision, oldest first. */
    public function index(Request $request)
    {
        $this->authorizeSection();

        $query = QuestionPaperUnfreezeRequest::with([
            'questionPaper.componentMap.subject',
            'questionPaper.componentMap.component',
            'questionPaper.faculty',
            'questionPaper.drive.course',
            'questionPaper.drive.term',
        ]);

        // Decided requests stay readable, but the inbox opens on what still
        // needs acting on.
        $showDecided = $request->boolean('decided');

        if ($showDecided) {
            $query->where('status', '!=', QuestionPaperUnfreezeRequest::STATUS_PENDING)
                ->orderByDesc('approved_at');
        } else {
            $query->pending()->orderBy('requested_at');
        }

        if ($driveId = (int) $request->input('examination_drive_id')) {
            $query->forDrive($driveId);
        }

        return view('admin.coe.question_paper.unfreeze.index', [
            'requests' => $query->paginate(20)->withQueryString(),
            'showDecided' => $showDecided,
            'pendingCount' => QuestionPaperUnfreezeRequest::pending()->count(),
        ]);
    }

    /** Approve: the paper reopens for correction. */
    public function approve(Request $request, $id)
    {
        $unfreeze = $this->pendingRequest($id);

        $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        try {
            $this->workflow->approveUnfreeze(
                $unfreeze,
                auth()->user()->user_id ?? null,
                $request->remarks
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->notifications->unfreezeDecided($unfreeze->refresh());

        return back()->with('success', 'Unfreeze approved. The paper is open for correction.');
    }

    /** Reject: the paper stays frozen. */
    public function reject(Request $request, $id)
    {
        $unfreeze = $this->pendingRequest($id);

        $request->validate([
            // A refusal has to say why - the author is being told their locked
            // paper stays locked.
            'remarks' => 'required|string|min:5|max:1000',
        ]);

        try {
            $this->workflow->rejectUnfreeze(
                $unfreeze,
                auth()->user()->user_id ?? null,
                $request->remarks
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->notifications->unfreezeDecided($unfreeze->refresh());

        return back()->with('success', 'Unfreeze rejected. The paper remains frozen.');
    }

    /**
     * Resolve a request the signed-in officer may decide.
     *
     * canApproveUnfreeze also refuses the paper's own author, so someone who
     * holds both roles cannot grant their own request.
     */
    protected function pendingRequest($id): QuestionPaperUnfreezeRequest
    {
        $this->authorizeSection();

        $unfreeze = QuestionPaperUnfreezeRequest::with('questionPaper')->findOrFail(decrypt($id));

        if (! $unfreeze->questionPaper
            || ! QuestionPaperAccess::canApproveUnfreeze($unfreeze->questionPaper)) {
            abort(403, 'You are not authorised to decide this request.');
        }

        return $unfreeze;
    }

    protected function authorizeSection(): void
    {
        if (! QuestionPaperAccess::isExamSection()) {
            abort(403, 'You are not authorised to manage question papers.');
        }
    }
}
