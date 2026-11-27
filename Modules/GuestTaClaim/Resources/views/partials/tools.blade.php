@can('gtc.export')
    <button type="button" class="btn btn-outline-secondary btn-sm" data-gtc-print><i class="bi bi-printer"></i> Print</button>
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('guest-ta-claim.export', ['type' => $type] + ($query ?? [])) }}"><i class="bi bi-download"></i> Export CSV</a>
@endcan
