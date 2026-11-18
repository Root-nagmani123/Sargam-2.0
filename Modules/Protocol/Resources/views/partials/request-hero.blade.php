{{-- Expects: $protocolRequest, $raisedBy, $department --}}
<div class="pr-hero">
  <div>
    <h2 class="pr-hero__id">{{ $protocolRequest->request_number }}</h2>
    <div class="pr-facts--hero">
      <div><span class="pr-fact__label">Raised by</span><div class="pr-fact__value">{{ $raisedBy ?: '—' }}</div></div>
      <div><span class="pr-fact__label">Department</span><div class="pr-fact__value">{{ $department ?: '—' }}</div></div>
      <div><span class="pr-fact__label">Raised on</span><div class="pr-fact__value">{{ $protocolRequest->created_at->format('d M Y') }}</div></div>
      <div><span class="pr-fact__label">Type</span><div class="pr-fact__value">{{ $protocolRequest->typeLabel() }}</div></div>
    </div>
  </div>
  <x-protocol::status-badge :status="$protocolRequest->status" />
</div>
