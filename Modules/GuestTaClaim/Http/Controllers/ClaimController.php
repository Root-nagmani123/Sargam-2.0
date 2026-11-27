<?php

namespace Modules\GuestTaClaim\Http\Controllers;

use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\GuestTaClaim\Contracts\TimetableProvider;
use Modules\GuestTaClaim\Enums\ClaimStatus;
use Modules\GuestTaClaim\Enums\Permission;
use Modules\GuestTaClaim\Http\Requests\PrepareClaimRequest;
use Modules\GuestTaClaim\Http\Requests\SearchTimetableRequest;
use Modules\GuestTaClaim\Http\Requests\SubmitClaimBillRequest;
use Modules\GuestTaClaim\Http\Requests\StoreClaimRequest;
use Modules\GuestTaClaim\Models\Claim;
use Modules\GuestTaClaim\Models\SessionConfirmation;
use Modules\GuestTaClaim\Services\ClaimWorkflowService;

class ClaimController extends Controller
{
    public function __construct(
        protected TimetableProvider $timetable,
        protected ClaimWorkflowService $workflow,
    ) {}

    public function index(Request $request)
    {
        $tab = $request->query('tab', 'all');
        // $base = Claim::visibleTo(auth()->user());
        $base = Claim::query();
   
        
        $counts = (clone $base)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $claims = (clone $base) 
            ->when($tab !== 'all' && ClaimStatus::tryFrom($tab), fn ($q) => $q->where('status', $tab))
            ->latest('id')->paginate(25)->withQueryString();

        return view('guesttaclaim::claims.index', compact('claims', 'counts', 'tab'));
    }

    /** Step 1: course + dates, faculty-wise guest sessions. */
    public function create(SearchTimetableRequest $request)
    {
        $courses = $this->timetable->courses();
        $search = null;

        if ($request->filled('course_id')) {
            $course = $this->timetable->course($request->course_id);
            abort_unless($course, 404, 'Course not found.');
            $from = Carbon::parse($request->from);
            $to = Carbon::parse($request->to);
            $sessions = $this->timetable->guestSessions($course['id'], $from, $to);
            $faculty = $this->timetable->facultyMany($sessions->pluck('faculty_id')->unique()->values()->all());

            $existing = Claim::where('course_id', (string) $course['id'])
                ->whereDate('period_from', $from)->whereDate('period_to', $to)
                ->get(['id', 'claim_no', 'faculty_id'])->keyBy('faculty_id');

            $groupedSessions = $sessions->groupBy('faculty_id');
            $fingerprints = $groupedSessions->map(fn ($rows, $fid) => $this->sessionFingerprint(
                (string) $course['id'],
                (string) $fid,
                $from,
                $to,
                $rows
            ));
            $confirmations = SessionConfirmation::where('user_id', $request->user()->getKey())
                ->whereIn('session_fingerprint', $fingerprints->values())
                ->get()
                ->keyBy('session_fingerprint');

            $groups = $groupedSessions->map(function ($rows, $fid) use ($faculty, $existing, $fingerprints, $confirmations) {
                $fingerprint = $fingerprints->get($fid);
                $confirmation = $confirmations->get($fingerprint);
                $confirmedSessions = $confirmation
                    ? collect($confirmation->sessions)->map(fn ($session) => $session + ['confirmed' => true])
                    : $rows->values()->map(fn ($session) => [
                        'source_session_id' => $session['id'],
                        'session_date' => $session['date'],
                        'start_time' => $session['start'],
                        'end_time' => $session['end'],
                        'topic' => $session['topic'],
                        'venue' => $session['venue'],
                        'status' => '',
                        'confirmed' => false,
                    ]);

                return [
                    'faculty' => $faculty[$fid] ?? ['id' => $fid, 'name' => 'Faculty '.$fid, 'designation' => '', 'organisation' => '', 'category' => '', 'city' => ''],
                    'sessions' => $confirmedSessions,
                    'sessions_confirmed' => $confirmation !== null,
                    'existing' => $existing[(string) $fid] ?? null,
                ];
            })->values();

            $search = compact('course', 'from', 'to', 'groups') + ['count' => $sessions->count()];
        }
        return view('guesttaclaim::claims.create', compact('courses', 'search'));
    }

    /** Step 2: carry the chosen faculty's (possibly edited) sessions into the claim form. */
    public function prepare(PrepareClaimRequest $request)
    {
        $from = Carbon::parse($request->from);
        $to = Carbon::parse($request->to);
        $source = $this->timetable->guestSessions($request->course_id, $from, $to)
            ->where('faculty_id', $request->faculty_id)->keyBy('id');

        $sessions = $this->can(Permission::EditSessions)
            ? $this->normaliseSessions($request->input('sessions'), $source)
            : $this->sessionsFromSource($source);

        SessionConfirmation::updateOrCreate([
            'user_id' => $request->user()->getKey(),
            'session_fingerprint' => $this->sessionFingerprint(
                $request->course_id,
                $request->faculty_id,
                $from,
                $to,
                $source
            ),
        ], [
            'course_id' => $request->course_id,
            'faculty_id' => $request->faculty_id,
            'period_from' => $from->format('Y-m-d'),
            'period_to' => $to->format('Y-m-d'),
            'sessions' => $sessions,
            'confirmed_at' => now(),
        ]);

        session()->put('gtc.prepare', [
            'course_id' => $request->course_id,
            'faculty_id' => $request->faculty_id,
            'period_from' => $from->format('Y-m-d'),
            'period_to' => $to->format('Y-m-d'),
            'sessions' => $sessions,
            'sessions_confirmed' => true,
        ]);

        return redirect()->route('guest-ta-claim.claims.form');
    }

    public function form()
    {
        $p = session('gtc.prepare');
        if (! $p) {
            return redirect()->route('guest-ta-claim.claims.create')->with('gtc_error', 'Find the timetable and choose a faculty first.');
        }

        $course = $this->timetable->course($p['course_id']);
        $faculty = $this->timetable->faculty($p['faculty_id']);
        abort_unless($course && $faculty, 404);

        $category = array_key_exists($faculty['category'] ?? '', config('guest-ta-claim.honorarium_rates'))
            ? $faculty['category'] : array_key_first(config('guest-ta-claim.honorarium_rates'));

        $values = [
            'faculty_category' => $category,
            'honorarium_rate' => config("guest-ta-claim.honorarium_rates.$category"),
            'faculty_official_address' => $faculty['official_address'] ?? collect([$faculty['organisation'] ?? null, $faculty['city'] ?? null])->filter()->join(', '),
            'basic_pay' => '',
            'travelling_allowances' => [],
            'da_journey_days' => 0,
            'da_stay_days' => 0,
            'da_rate' => '',
            'da_percentage' => '',
            'da_rate_basis' => '',
            'admissible_journey_numbers' => '',
            'higher_class_journey_numbers' => '',
            'higher_class_reimbursement_basis' => '',
            'remarks' => '',
        ];

        return view('guesttaclaim::claims.form', [
            'claim' => null, 'course' => $course, 'faculty' => $faculty, 'prepare' => $p,
            'values' => $values, 'sessions' => $p['sessions'], 'sessionsConfirmed' => $p['sessions_confirmed'] ?? false,
        ]);
    }

    public function store(StoreClaimRequest $request)
    {
        $p = session('gtc.prepare');
        abort_unless($p && $p['course_id'] === $request->course_id && $p['faculty_id'] === $request->faculty_id, 419, 'The claim form expired. Start again from New claim.');

        $forward = $request->action === 'forward' && $this->can(Permission::Forward);
        $sessions = $this->can(Permission::EditSessions) ? $this->normaliseSessions($request->input('sessions'), collect()) : $p['sessions'];

        try {
            $claim = $this->workflow->save(null, array_merge($request->validated(), [
                'period_from' => $p['period_from'], 'period_to' => $p['period_to'],
            ]), $sessions, $request->file('attachments', []), $request->user(), $forward);
        } catch (DomainException $e) {
            return back()->withInput()->with('gtc_error', $e->getMessage());
        }

        session()->forget('gtc.prepare');

        return redirect()->route('guest-ta-claim.claims.show', $claim)
            ->with('gtc_success', $forward ? "{$claim->claim_no} forwarded to the course coordinator." : "{$claim->claim_no} saved as draft.");
    }

    public function show(Claim $claim)
    {
        // $this->authorizeView($claim);
        $claim->load('sessions', 'histories', 'attachments', 'travellingAllowances');

        return view('guesttaclaim::claims.show', [
            'claim' => $claim,
            'canApprove' => $claim->status === ClaimStatus::PendingCc && $this->workflow->canApprove($claim, auth()->user()),
        ]);
    }

    public function bill(Claim $claim)
    {
        $this->authorizeView($claim);
        $claim->load('travellingAllowances');

        return view('guesttaclaim::claims.bill', compact('claim'));
    }

    public function submitBill(SubmitClaimBillRequest $request, Claim $claim)
    {
        $this->authorizeView($claim);
        abort_unless($this->can(Permission::Forward), 403, 'You do not have permission to submit claims directly to Accounts.');

        try {
            $this->workflow->submitBillToAccounts($claim, $request->file('bill_pdf'), $request->user());
        } catch (DomainException $e) {
            return back()->withInput()->with('gtc_error', $e->getMessage());
        }

        return redirect()->route('guest-ta-claim.claims.show', $claim)
            ->with('gtc_success', "{$claim->claim_no} submitted directly to Accounts with the signed bill.");
    }

    public function edit(Claim $claim)
    {
        $this->authorizeView($claim);
        abort_unless($claim->status->isEditable(), 403, 'This claim can no longer be edited.');
        $claim->load('sessions', 'attachments', 'travellingAllowances');

        $values = $claim->only([
            'faculty_category', 'honorarium_rate', 'faculty_official_address', 'basic_pay',
            'da_journey_days', 'da_stay_days', 'da_rate', 'da_percentage', 'da_rate_basis',
            'admissible_journey_numbers', 'higher_class_journey_numbers', 'higher_class_reimbursement_basis', 'remarks',
        ]);

        return view('guesttaclaim::claims.form', [
            'claim' => $claim,
            'course' => ['id' => $claim->course_id, 'name' => $claim->course_name, 'coordinator_user_id' => $claim->coordinator_user_id],
            'faculty' => [
                'id' => $claim->faculty_id, 'name' => $claim->faculty_name, 'designation' => $claim->faculty_designation,
                'organisation' => $claim->faculty_organisation, 'city' => $claim->travel_from,
                'official_address' => $claim->faculty_official_address,
            ],
            'prepare' => ['period_from' => $claim->period_from->format('Y-m-d'), 'period_to' => $claim->period_to->format('Y-m-d')],
            'values' => $values,
            'sessions' => $claim->sessions->map->toFormArray()->all(),
            'travelAllowances' => $claim->travellingAllowances->map->toFormArray()->all(),
            'sessionsConfirmed' => true,
        ]);
    }

    public function update(StoreClaimRequest $request, Claim $claim)
    {
        $this->authorizeView($claim);
        $forward = $request->action === 'forward' && $this->can(Permission::Forward);
        $sessions = $this->can(Permission::EditSessions)
            ? $this->normaliseSessions($request->input('sessions'), collect())
            : $claim->sessions->map->toFormArray()->all();

        try {
            $this->workflow->save($claim, $request->validated(), $sessions, $request->file('attachments', []), $request->user(), $forward);
        } catch (DomainException $e) {
            return back()->withInput()->with('gtc_error', $e->getMessage());
        }

        return redirect()->route('guest-ta-claim.claims.show', $claim)
            ->with('gtc_success', $forward ? "{$claim->claim_no} forwarded to the course coordinator." : "{$claim->claim_no} saved.");
    }

    public function forward(Claim $claim)
    {
        $this->authorizeView($claim);
        try {
            $this->workflow->forward($claim, auth()->user());
        } catch (DomainException $e) {
            return back()->with('gtc_error', $e->getMessage());
        }

        return back()->with('gtc_success', "{$claim->claim_no} forwarded to the course coordinator.");
    }

    public function destroy(Claim $claim)
    {
        $this->authorizeView($claim);
        try {
            $this->workflow->delete($claim, auth()->user());
        } catch (DomainException $e) {
            return back()->with('gtc_error', $e->getMessage());
        }

        return redirect()->route('guest-ta-claim.claims.index')->with('gtc_success', "Draft {$claim->claim_no} deleted.");
    }

    protected function normaliseSessions(array $rows, $source): array
    {
        return collect($rows)->map(function ($s) use ($source) {
            $orig = isset($s['source_session_id']) ? $source->get($s['source_session_id']) : null;
            $row = [
                'source_session_id' => $s['source_session_id'] ?? null,
                'session_date' => Carbon::parse($s['session_date'])->format('Y-m-d'),
                'start_time' => $s['start_time'],
                'end_time' => $s['end_time'],
                'topic' => trim($s['topic']),
                'venue' => isset($s['venue']) ? trim($s['venue']) : null,
                'status' => $s['status'],
            ];
            $row['is_edited'] = $orig ? ($orig['date'] !== $row['session_date'] || $orig['start'] !== $row['start_time']
                || $orig['end'] !== $row['end_time'] || $orig['topic'] !== $row['topic'] || $row['status'] !== 'taken') : (bool) ($s['is_edited'] ?? false);

            return $row;
        })->values()->all();
    }

    protected function sessionFingerprint(string $courseId, string $facultyId, Carbon $from, Carbon $to, $sessions): string
    {
        $schedule = collect($sessions)->map(fn ($session) => [
            'id' => (string) ($session['id'] ?? $session['source_session_id'] ?? ''),
            'date' => Carbon::parse($session['date'] ?? $session['session_date'])->format('Y-m-d'),
            'start' => substr((string) ($session['start'] ?? $session['start_time']), 0, 5),
            'end' => substr((string) ($session['end'] ?? $session['end_time']), 0, 5),
            'topic' => (string) ($session['topic'] ?? ''),
            'venue' => (string) ($session['venue'] ?? ''),
        ])->sortBy(fn ($session) => $session['date'].' '.$session['start'].' '.$session['id'])->values()->all();

        return hash('sha256', json_encode([
            'course_id' => $courseId,
            'faculty_id' => $facultyId,
            'period_from' => $from->format('Y-m-d'),
            'period_to' => $to->format('Y-m-d'),
            'sessions' => $schedule,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    protected function sessionsFromSource($source): array
    {
        return $source->map(fn ($s) => [
            'source_session_id' => $s['id'], 'session_date' => $s['date'], 'start_time' => $s['start'], 'end_time' => $s['end'],
            'topic' => $s['topic'], 'venue' => $s['venue'], 'status' => 'taken', 'is_edited' => false,
        ])->values()->all();
    }
}