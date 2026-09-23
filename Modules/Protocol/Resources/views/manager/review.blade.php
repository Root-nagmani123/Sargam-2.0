@extends('protocol::layouts.app')

@section('title', 'Final Decision — ' . $protocolRequest->request_number)
@section('page_title', 'Final Decision — ' . $protocolRequest->request_number)
@section('page_subtitle', 'Recommended by ' . ($protocolRequest->protocolStaff->name ?? '—'))

@section('content')

  <div class="card-clean p-4">

    <div class="d-flex justify-content-between align-items-start mb-3">
      <div>
        <div class="text-muted small">Raised by</div>
        <div class="fw-bold">{{ $protocolRequest->employee->name ?? '—' }}</div>
        <div class="text-muted small">{{ $protocolRequest->employee_department }} &middot; {{ $protocolRequest->created_at->format('d M Y') }}</div>
      </div>
      <x-protocol::status-badge :status="$protocolRequest->status" />
    </div>

    <div class="card-clean p-3 mb-3">
      <table class="table table-sm mb-0">
        @php $d = $protocolRequest->requestable; @endphp

        @if ($protocolRequest->request_type === 'guesthouse')
          <tr><td class="text-muted" style="width:180px;">Guest</td><td class="fw-semibold">{{ $d->guest_name }}</td></tr>
          <tr><td class="text-muted">Guest House</td><td class="fw-semibold">{{ $d->guest_house_name }}</td></tr>
          <tr><td class="text-muted">Check-in</td><td class="fw-semibold">{{ $d->check_in_date->format('d M Y') }}</td></tr>
          <tr><td class="text-muted">Check-out</td><td class="fw-semibold">{{ $d->check_out_date->format('d M Y') }}</td></tr>
          <tr><td class="text-muted">No. of Guests</td><td class="fw-semibold">{{ $d->no_of_guests }}</td></tr>
        @elseif ($protocolRequest->request_type === 'vehicle')
          <tr><td class="text-muted" style="width:180px;">From</td><td class="fw-semibold">{{ $d->from_location }}</td></tr>
          <tr><td class="text-muted">To</td><td class="fw-semibold">{{ $d->to_location }}</td></tr>
          <tr><td class="text-muted">Date of Travel</td><td class="fw-semibold">{{ $d->date_of_travel->format('d M Y') }}</td></tr>
          <tr><td class="text-muted">Vehicle</td><td class="fw-semibold">{{ $d->preferred_vehicle ?? '—' }}</td></tr>
          <tr><td class="text-muted">Passengers</td><td class="fw-semibold">{{ $d->no_of_passengers }}</td></tr>
        @else
          <tr><td class="text-muted" style="width:180px;">Mode</td><td class="fw-semibold">{{ $d->mode }}</td></tr>
          <tr><td class="text-muted">From</td><td class="fw-semibold">{{ $d->from_place }}</td></tr>
          <tr><td class="text-muted">To</td><td class="fw-semibold">{{ $d->to_place }}</td></tr>
          <tr><td class="text-muted">Journey Date</td><td class="fw-semibold">{{ $d->journey_date->format('d M Y') }}</td></tr>
          <tr><td class="text-muted">Class</td><td class="fw-semibold">{{ $d->travel_class ?? '—' }}</td></tr>
        @endif

        <tr><td class="text-muted">Purpose</td><td class="fw-semibold">{{ $protocolRequest->purpose }}</td></tr>
      </table>
    </div>

    <div class="fw-bold small text-uppercase text-muted mb-2" style="letter-spacing:.05em;">Approval Log</div>
    <x-protocol::timeline :logs="$protocolRequest->logs" />
    <hr class="my-4">

    <form method="POST" action="{{ route('protocol.manager.decide', $protocolRequest) }}" id="managerDecisionForm">
      @csrf
      <input type="hidden" name="decision" id="managerDecisionField" value="approve">

      <div class="mb-3">
        <label class="form-label">Remarks</label>
        <textarea name="remarks" class="form-control" rows="2" placeholder="Optional note for the record"></textarea>
      </div>

      <div class="d-flex gap-2 justify-content-end">
        <a href="{{ route('protocol.manager.queue') }}" class="btn btn-light">Cancel</a>
        <button type="submit" class="btn btn-outline-danger" onclick="document.getElementById('managerDecisionField').value='reject'">
          <i class="bi bi-x-lg me-1"></i> Reject
        </button>
        <button type="submit" class="btn btn-green" onclick="document.getElementById('managerDecisionField').value='approve'">
          <i class="bi bi-check-lg me-1"></i> Approve
        </button>
      </div>
    </form>

  </div>

@endsection
