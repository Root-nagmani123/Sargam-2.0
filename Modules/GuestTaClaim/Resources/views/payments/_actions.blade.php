<a class="btn btn-outline-secondary btn-sm" href="{{ route('guest-ta-claim.claims.show', $claim) }}">Request details</a>
@if ($claim->status === \Modules\GuestTaClaim\Enums\ClaimStatus::PendingAccounts)
    @can('gtc.acc.pay')
        @include('guesttaclaim::payments._pay-button', ['claim' => $claim, 'size' => 'btn-sm'])
    @endcan
@endif
