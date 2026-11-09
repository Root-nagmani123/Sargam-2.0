@extends('admin.layouts.master')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Overview of protocol requests across Guest House, Vehicle Pass & Ticket')


@section('setup_content')
  @include('protocol::partials.style')
  <div class="container-fluid protocol-page">
    <x-breadcrum
        title="Dashboard"
        :items="[
            'Home',
            ['label' => 'Protocol', 'url' => route('protocol.dashboard')],
            'Dashboard',
        ]"
    />

  @php
    $cardIcons = [
      'guesthouse' => 'bi-building',
      'vehicle' => 'bi-truck-front',
      'ticket' => 'bi-ticket-perforated',
    ];
  @endphp

  <div class="row g-3 mb-4">
    @foreach ($cards as $card)
      <div class="col-6 col-md-3">
        <a href="{{ $card['url'] }}" class="d-block h-100 text-reset text-decoration-none">
          <div class="stat-card">
            <div class="icon-badge pr-tint-{{ $card['type'] }}"><i class="bi {{ $cardIcons[$card['type']] }}" aria-hidden="true"></i></div>
            <div class="num mt-3">{{ $card['count'] }}</div>
            <div class="lbl">{{ $card['label'] }}</div>
          </div>
        </a>
      </div>
    @endforeach
    @if ($pending)
    <div class="col-6 col-md-3">
      <a href="{{ $pending['url'] }}" class="d-block h-100 text-reset text-decoration-none">
        <div class="stat-card">
          <div class="icon-badge pr-tint-pending"><i class="bi bi-hourglass-split" aria-hidden="true"></i></div>
          <div class="num mt-3">{{ $pending['count'] }}</div>
          <div class="lbl">Pending My Action</div>
        </div>
      </a>
    </div>
    @endif
  </div>

  <div class="card">
    <div class="card-body">
      <div class="programme-dt-toolbar mb-4">
        <div class="section-title">Recent Requests</div>
        <div class="section-sub">Latest activity across all protocol request types</div>
      </div>
      <div class="programme-dt-panel">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 w-100 programme-dt-table">
            <thead>
              <tr>
                <th>Request ID</th>
                <th>Type</th>
                <th>Employee</th>
                <th>Raised On</th>
                <th>Current Stage</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($recent as $r)
                <tr data-href="{{ route('protocol.requests.show', $r) }}" onclick="window.location=this.dataset.href">
                  <td class="pr-col-id"><a href="{{ route('protocol.requests.show', $r) }}" class="text-reset text-decoration-none">{{ $r->request_number }}</a></td>
                  <td><x-protocol::type-pill :type="$r->request_type" /></td>
                  <td>{{ $r->employee->name ?? '—' }}</td>
                  <td>{{ $r->created_at->format('d M Y') }}</td>
                  <td class="text-muted small">{{ $r->current_stage }}</td>
                  <td><x-protocol::status-badge :status="$r->status" /></td>
                </tr>
              @empty
                <tr>
                  <td colspan="6">
                    <div class="empty-state"><i class="bi bi-inbox"></i>No requests yet.</div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  </div>

@endsection
