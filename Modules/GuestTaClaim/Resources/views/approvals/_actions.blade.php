@if ($claim->status === \Modules\GuestTaClaim\Enums\ClaimStatus::PendingCc)
    <button type="button" class="btn btn-outline-danger btn-sm" data-gtc-action="gtcReturnModal" data-url="{{ route('guest-ta-claim.claims.return', $claim) }}" data-claim="{{ $claim->claim_no }}">Return</button>
    <button type="button" class="btn btn-success btn-sm" data-gtc-action="gtcApproveModal" data-url="{{ route('guest-ta-claim.claims.approve', $claim) }}" data-claim="{{ $claim->claim_no }}">Approve</button>
@endif
