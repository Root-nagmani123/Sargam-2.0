@extends('admin.layouts.master')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Overview of protocol requests across Guest House, Vehicle Pass & Ticket')


@section('setup_content')
  @include('protocol::partials.style')
  <div class="container-fluid profile-page">
    <x-breadcrum
        title="Dashboard"
        :items="[
            'Home',
            ['label' => 'Protocol', 'url' => route('protocol.dashboard')],
            'Dashboard',
        ]"
    />

  @php
    $cardStyles = [
      'guesthouse' => ['icon' => 'bi-building', 'style' => 'background:#EFEBFF;color:#5B3FD9;'],
      'vehicle' => ['icon' => 'bi-truck-front', 'style' => 'background:#FFF0E0;color:#E6802A;'],
      'ticket' => ['icon' => 'bi-ticket-perforated', 'style' => 'background:#E0F5F0;color:#0E8F73;'],
    ];
  @endphp

  <div class="row g-3 mb-4">
    @foreach ($cards as $card)
      <div class="col-6 col-md-3">
        <a href="{{ $card['url'] }}" class="d-block h-100 text-reset text-decoration-none">
          <div class="stat-card">
            <div class="icon-badge" style="{{ $cardStyles[$card['type']]['style'] }}"><i class="bi {{ $cardStyles[$card['type']]['icon'] }}"></i></div>
            <div class="num mt-2">{{ $card['count'] }}</div>
            <div class="lbl">{{ $card['label'] }}</div>
          </div>
        </a>
      </div>
    @endforeach
    @if ($pending)
    <div class="col-6 col-md-3">
      <a href="{{ $pending['url'] }}" class="d-block h-100 text-reset text-decoration-none">
        <div class="stat-card">
          <div class="icon-badge" style="background:#FFF4E0;color:#B8720A;"><i class="bi bi-hourglass-split"></i></div>
          <div class="num mt-2">{{ $pending['count'] }}</div>
          <div class="lbl">Pending My Action</div>
        </div>
      </a>
    </div>
    @endif
  </div>

  <div class="card-clean p-3">
    <div class="section-title">Recent Requests</div>
    <div class="section-sub mb-2">Latest activity across all protocol request types</div>
    <div class="table-responsive">
      <table class="table mb-0">
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
            <tr onclick="window.location='{{ route('protocol.requests.show', $r) }}'" style="cursor:pointer;">
              <td class="fw-semibold">{{ $r->request_number }}</td>
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

@endsection