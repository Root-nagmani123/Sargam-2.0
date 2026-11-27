@if ($claim->status === \Modules\GuestTaClaim\Enums\ClaimStatus::Paid)
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('guest-ta-claim.claims.report', $claim) }}" target="_blank" rel="noopener"><i class="bi bi-file-earmark-text"></i> Claim report</a>
@endif
