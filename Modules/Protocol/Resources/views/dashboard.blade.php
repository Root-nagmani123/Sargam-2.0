@extends('admin.layouts.master')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Overview of protocol requests across Guest House, Vehicle Pass & Ticket')


@section('setup_content')

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="icon-badge" style="background:#EFEBFF;color:#5B3FD9;"><i class="bi bi-building"></i></div>
        <div class="num mt-2">{{ $counts['guesthouse'] }}</div>
        <div class="lbl">Guest House Requests</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="icon-badge" style="background:#FFF0E0;color:#E6802A;"><i class="bi bi-truck-front"></i></div>
        <div class="num mt-2">{{ $counts['vehicle'] }}</div>
        <div class="lbl">Vehicle Pass Requests</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="icon-badge" style="background:#E0F5F0;color:#0E8F73;"><i class="bi bi-ticket-perforated"></i></div>
        <div class="num mt-2">{{ $counts['ticket'] }}</div>
        <div class="lbl">Ticket Requests</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="icon-badge" style="background:#FFF4E0;color:#B8720A;"><i class="bi bi-hourglass-split"></i></div>
        <div class="num mt-2">{{ $counts['pending_mine'] }}</div>
        <div class="lbl">Pending My Action</div>
      </div>
    </div>
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

@endsection