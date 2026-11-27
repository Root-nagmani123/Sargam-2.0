@php($order = $claim->status->stepIndex())
<div class="gtc-steps">
    @foreach (['Claim prepared', 'Course coordinator', 'Accounts', 'Paid'] as $i => $label)
        @php($state = $i < $order ? 'done' : ($i === $order ? ($claim->status === \Modules\GuestTaClaim\Enums\ClaimStatus::Returned ? 'ret' : 'cur') : ''))
        <div class="gtc-step {{ $state }}"><div class="dot">@if ($state === 'done')<i class="bi bi-check-lg"></i>@else{{ $i + 1 }}@endif</div>{{ $label }}</div>
    @endforeach
</div>
