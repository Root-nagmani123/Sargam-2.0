{{-- params: c (array or Claim with honorarium_rate, travel_fare, local_conveyance, da_days, da_rate, da_percentage, sessions_taken, honorarium_amount, da_amount, total_amount) --}}
@php($fmt = \Modules\GuestTaClaim\Support\Format::class)
<div class="gtc-calc" data-legacy-fare="{{ $c['travel_fare'] }}" data-legacy-accommodation="{{ $c['local_conveyance'] }}">
    <div><span data-calc="hon-label">Honorarium ({{ $c['sessions_taken'] }} × {{ $fmt::money($c['honorarium_rate']) }})</span><span data-calc="hon">{{ $fmt::money($c['honorarium_amount']) }}</span></div>
    <div><span>Fare admissible</span><span data-calc="fare">{{ $fmt::money($c['travel_fare']) }}</span></div>
    <div><span>Accommodation</span><span data-calc="accommodation">{{ $fmt::money($c['local_conveyance']) }}</span></div>
    <div><span data-calc="da-label">DA ({{ (int) $c['da_days'] }} × {{ $fmt::money($c['da_rate']) }} × {{ (int) ($c['da_percentage'] ?? 100) }}%)</span><span data-calc="da">{{ $fmt::money($c['da_amount']) }}</span></div>
    <div class="tot"><span>Total claimed</span><span data-calc="total">{{ $fmt::money($c['total_amount']) }}</span></div>
    @if (! empty($c['paid_amount']))
        <div class="text-success fw-bold"><span>Paid</span><span>{{ $fmt::money($c['paid_amount']) }}</span></div>
    @endif
</div>
