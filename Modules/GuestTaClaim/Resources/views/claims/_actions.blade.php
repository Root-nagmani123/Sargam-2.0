@if ($claim->status === \Modules\GuestTaClaim\Enums\ClaimStatus::Paid)
    @can('gtc.claim.report')
        <a class="btn btn-success btn-sm" href="{{ route('guest-ta-claim.claims.report', $claim) }}" target="_blank" rel="noopener"><i class="bi bi-file-earmark-text"></i> Generate report</a>
    @endcan
@elseif ($claim->status->isEditable())
    @can('gtc.claim.create')
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('guest-ta-claim.claims.edit', $claim) }}"><i class="bi bi-pencil"></i> Edit</a>
    @endcan
@endif
