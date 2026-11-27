<?php

namespace Modules\GuestTaClaim\Http\Controllers;

use Illuminate\Http\Request;
use Modules\GuestTaClaim\Contracts\TimetableProvider;
use Modules\GuestTaClaim\Enums\ClaimStatus;
use Modules\GuestTaClaim\Models\Claim;
use Modules\GuestTaClaim\Services\ClaimWorkflowService;

class ReportController extends Controller
{
    public function __construct(protected ClaimWorkflowService $workflow) {}

    public static function query(Request $request)
    {
        return Claim::visibleTo(auth()->user())
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', $request->course_id))
            ->when(ClaimStatus::tryFrom((string) $request->status), fn ($q, $s) => $q->where('status', $s))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('period_from', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('period_to', '<=', $request->to))
            ->latest('id');
    }

    public function index(Request $request, TimetableProvider $timetable)
    {
        $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date']);
        $q = self::query($request);
        $summary = [
            'count' => (clone $q)->count(),
            'claimed' => (float) (clone $q)->sum('total_amount'),
            'paid' => (float) (clone $q)->where('status', ClaimStatus::Paid)->sum('paid_amount'),
        ];
        $claims = $q->paginate(25)->withQueryString();
        $courses = $timetable->courses();

        return view('guesttaclaim::reports.index', compact('claims', 'summary', 'courses'));
    }

    /** Printable claim report; ?download=html|pdf to save a copy. */
    public function show(Request $request, Claim $claim)
    {
        $this->authorizeView($claim);
        abort_unless($claim->status === ClaimStatus::Paid, 403, 'The report is available once the claim is paid.');

        $this->workflow->markReportGenerated($claim, $request->user());
        $claim->load('sessions', 'histories', 'travellingAllowances');
        $file = str_replace('/', '-', $claim->claim_no).'-report';

        if ($request->query('download') === 'pdf' && class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('guesttaclaim::reports.claim', ['claim' => $claim, 'forPdf' => true])
                ->setPaper('a4')->download($file.'.pdf');
        }

        $html = view('guesttaclaim::reports.claim', ['claim' => $claim, 'forPdf' => $request->query('download') === 'html'])->render();

        return $request->query('download') === 'html'
            ? response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="'.$file.'.html"'])
            : response($html);
    }
}
