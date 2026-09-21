<?php

namespace App\Http\Controllers\Admin\COE;

use App\DataTables\QuestionPaperDataTable;
use App\Http\Controllers\Controller;
use App\Models\QuestionPaper;
use App\Models\QuestionPaperAuditLog;
use App\Models\QuestionPaperUnfreezeRequest;
use App\Services\COE\QuestionPaperAccess;
use App\Services\COE\QuestionPaperAssignmentService;
use App\Services\COE\QuestionPaperAuditService;
use App\Services\COE\QuestionPaperNotificationService;
use Illuminate\Http\Request;

/**
 * COE Examination - the Examination Section's question paper screens.
 *
 * Assignment, deadlines and oversight of a drive's papers. The faculty upload
 * and freeze screens live in QuestionPaperFacultyController; this side never
 * uploads a paper, it only decides who writes one and by when.
 */
class QuestionPaperController extends Controller
{
    public function __construct(
        protected QuestionPaperAssignmentService $assignments,
        protected QuestionPaperNotificationService $notifications,
        protected QuestionPaperAuditService $audit
    ) {
    }

    /** Papers of one drive, chosen from a dropdown on the page. */
    public function index(QuestionPaperDataTable $dataTable, Request $request)
    {
        $this->authorizeSection();

        $drives = $this->assignments->selectableDrives();

        $selectedDriveId = (int) $request->input('examination_drive_id', 0);

        // Default to the most recent drive so the page is not empty on arrival.
        if (! $selectedDriveId && $drives->isNotEmpty()) {
            $selectedDriveId = (int) $drives->first()->id;
            $request->merge(['examination_drive_id' => $selectedDriveId]);
        }

        $progress = $selectedDriveId
            ? QuestionPaper::driveProgress($selectedDriveId)
            : ['total' => 0, 'finalized' => 0, 'pending' => 0, 'is_complete' => false];

        return $dataTable->render('admin.coe.question_paper.index', [
            'drives' => $drives,
            'selectedDriveId' => $selectedDriveId,
            'progress' => $progress,
            // Across every drive, not just the selected one: a request left
            // undecided anywhere holds up that paper.
            'pendingUnfreezeCount' => QuestionPaperUnfreezeRequest::pending()->count(),
            'driveLabels' => $drives->mapWithKeys(
                fn ($d) => [$d->id => $this->assignments->driveLabel($d)]
            ),
        ]);
    }

    /**
     * Create the missing paper rows for a drive.
     *
     * Re-runnable: papers already being written are left alone, so this can be
     * used again after the drive gains a subject.
     */
    public function sync(Request $request)
    {
        $this->authorizeSection();

        $request->validate([
            'examination_drive_id' => 'required|integer|exists:examination_drives,id',
            'deadline' => 'nullable|date',
        ]);

        $result = $this->assignments->syncForDrive(
            $request->examination_drive_id,
            $request->deadline,
            auth()->user()->user_id ?? null
        );

        // Tell each setter about the paper now waiting on them. Only the rows
        // this run created: a re-run must not re-notify papers already in hand.
        if (! empty($result['created_ids'])) {
            QuestionPaper::with('componentMap.subject', 'componentMap.component')
                ->whereIn('id', $result['created_ids'])
                ->get()
                ->each(function (QuestionPaper $paper) {
                    $this->notifications->paperAssigned($paper);
                    $this->audit->log($paper->id, QuestionPaperAuditService::ACTION_ASSIGNED, [
                        'faculty_master_pk' => $paper->faculty_master_pk,
                        'deadline' => optional($paper->deadline)->format('Y-m-d'),
                    ]);
                });
        }

        $redirect = redirect()->route('coe.question_paper.index', [
            'examination_drive_id' => $request->examination_drive_id,
        ]);

        if ($result['created'] === 0 && empty($result['skipped'])) {
            return $redirect->with('success', 'All components in this drive already have a question paper.');
        }

        $message = $result['created'] . ' question paper(s) created.';

        // A skipped component is a gap on the drive side, not here, so name the
        // subjects rather than reporting a silent partial success.
        if (! empty($result['skipped'])) {
            $subjects = collect($result['skipped'])->pluck('subject')->unique()->implode(', ');
            $message .= ' Skipped (no faculty mapped in the drive): ' . $subjects . '.';

            return $redirect->with('warning', $message);
        }

        return $redirect->with('success', $message);
    }

    /** Set a deadline across every paper still being written in a drive. */
    public function setDeadline(Request $request)
    {
        $this->authorizeSection();

        $request->validate([
            'examination_drive_id' => 'required|integer|exists:examination_drives,id',
            'deadline' => 'required|date',
        ]);

        $updated = $this->assignments->setDeadline(
            $request->examination_drive_id,
            $request->deadline
        );

        // Logged against the drive rather than a paper: one action covering many.
        $this->audit->log(null, QuestionPaperAuditService::ACTION_DEADLINE_SET, [
            'examination_drive_id' => (int) $request->examination_drive_id,
            'deadline' => $request->deadline,
            'papers_updated' => $updated,
        ]);

        return redirect()
            ->route('coe.question_paper.index', ['examination_drive_id' => $request->examination_drive_id])
            ->with('success', $updated . ' question paper(s) updated. Frozen and finalized papers were left unchanged.');
    }

    /** One paper: its files, its version history and its requests. */
    public function show($id)
    {
        $paper = QuestionPaper::with([
            'drive.course',
            'drive.term',
            'componentMap.subject',
            'componentMap.component',
            'faculty',
            'currentFiles',
            'versions',
            'unfreezeRequests',
            'translationRequests',
        ])->findOrFail(decrypt($id));

        if (! QuestionPaperAccess::canView($paper)) {
            abort(404);
        }

        // The trail is only for the people who oversee the paper; the setter
        // has their own history on their screen.
        $auditLogs = QuestionPaperAccess::isExamSection() || QuestionPaperAccess::isApprover()
            ? QuestionPaperAuditLog::forPaper($paper->id)->orderByDesc('created_date')->limit(100)->get()
            : collect();

        return view('admin.coe.question_paper.show', compact('paper', 'auditLogs'));
    }

    /**
     * The Examination Section oversees every paper in a drive, so the whole
     * screen is gated rather than each paper checked one at a time.
     */
    protected function authorizeSection(): void
    {
        if (! QuestionPaperAccess::isExamSection()) {
            abort(403, 'You are not authorised to manage question papers.');
        }
    }
}
