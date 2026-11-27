<button type="button" class="btn btn-success {{ $size }}" data-gtc-action="gtcPayModal"
        data-url="{{ route('guest-ta-claim.claims.pay', $claim) }}" data-claim="{{ $claim->claim_no }}" data-faculty="{{ $claim->faculty_name }}"
        data-total="{{ (float) $claim->total_amount }}" data-total-text="{{ \Modules\GuestTaClaim\Support\Format::money($claim->total_amount) }}">
    <i class="bi bi-wallet2"></i> Paid
</button>
