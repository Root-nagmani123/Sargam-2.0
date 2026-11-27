<?php

namespace Modules\GuestTaClaim\Http\Controllers;

use DomainException;
use Illuminate\Http\Request;
use Modules\GuestTaClaim\Enums\ClaimStatus;
use Modules\GuestTaClaim\Http\Requests\DecisionRequest;
use Modules\GuestTaClaim\Models\Claim;
use Modules\GuestTaClaim\Services\ClaimWorkflowService;

class ApprovalController extends Controller
{
    public function __construct(protected ClaimWorkflowService $workflow) {}

    public static function query(string $tab)
    {
        $q = Claim::forCoordinator(auth()->user());
        return $tab === 'done'
            ? $q->whereIn('status', [ClaimStatus::PendingAccounts, ClaimStatus::Paid, ClaimStatus::Returned])
                ->whereDoesntHave('histories', fn ($history) => $history->where('action', 'Signed bill submitted directly to Accounts'))
                ->latest('updated_at')
            : $q->where('status', ClaimStatus::PendingCc)->oldest('forwarded_at');
    }

    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'done' ? 'done' : 'pending';
        $claims = self::query($tab)->paginate(25)->withQueryString();
        // dd($claims);
        $pendingCount = self::query('pending')->count();

        return view('guesttaclaim::approvals.index', compact('claims', 'tab', 'pendingCount'));
    }

    public function approve(DecisionRequest $request, Claim $claim)
    {
        try {
            $this->workflow->approve($claim, $request->user(), $request->remark);
        } catch (DomainException $e) {
            return back()->with('gtc_error', $e->getMessage());
        }

        return back()->with('gtc_success', "{$claim->claim_no} approved and sent to Accounts.");
    }

    public function return(DecisionRequest $request, Claim $claim)
    {
        try {
            $this->workflow->return($claim, $request->user(), $request->remark);
        } catch (DomainException $e) {
            return back()->with('gtc_error', $e->getMessage());
        }

        return back()->with('gtc_success', "{$claim->claim_no} returned to TA & Training.");
    }
}
