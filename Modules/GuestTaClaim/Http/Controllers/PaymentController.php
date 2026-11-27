<?php

namespace Modules\GuestTaClaim\Http\Controllers;

use DomainException;
use Illuminate\Http\Request;
use Modules\GuestTaClaim\Enums\ClaimStatus;
use Modules\GuestTaClaim\Http\Requests\PayRequest;
use Modules\GuestTaClaim\Models\Claim;
use Modules\GuestTaClaim\Services\ClaimWorkflowService;

class PaymentController extends Controller
{
    public function __construct(protected ClaimWorkflowService $workflow) {}

    public static function query(string $tab)
    {
        return $tab === 'paid'
            ? Claim::where('status', ClaimStatus::Paid)->latest('paid_at')
            : Claim::where('status', ClaimStatus::PendingAccounts)->orderByRaw('COALESCE(approved_at, forwarded_at) asc');
    }

    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'paid' ? 'paid' : 'pending';
        $claims = self::query($tab)->paginate(25)->withQueryString();
        $counts = [
            'pending' => self::query('pending')->count(),
            'paid' => self::query('paid')->count(),
        ];

        return view('guesttaclaim::payments.index', compact('claims', 'tab', 'counts'));
    }

    public function pay(PayRequest $request, Claim $claim)
    {
        try {
            $this->workflow->markPaid($claim, $request->user(),
                $request->filled('amount') ? (float) $request->amount : null, $request->remark);
        } catch (DomainException $e) {
            return back()->with('gtc_error', $e->getMessage());
        }

        return back()->with('gtc_success', "{$claim->claim_no} marked as paid.");
    }
}
