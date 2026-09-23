{{-- Expects: $protocolRequest with ->loadFullRequestable() and 'batch.guests' eager loaded --}}

@php
  $batch = $protocolRequest->batch;
  $d = $protocolRequest->requestable;
@endphp

<div class="card p-3 mb-3">
  <div class="row g-3">
    <div class="col-md-4">
      <div class="text-muted small">Course Team To Notify</div>
      <div class="fw-semibold">{{ $batch->course_team_to_notify ?? '—' }}</div>
    </div>
    <div class="col-md-4">
      <div class="text-muted small">Purpose</div>
      <div class="fw-semibold">{{ $batch->purpose ?? '—' }}</div>
    </div>
    <div class="col-md-2">
      <div class="text-muted small">Escort Required</div>
      <div class="fw-semibold">{{ $batch->escort_required ? 'Yes' : 'No' }}</div>
    </div>
    <div class="col-md-2">
      <div class="text-muted small">Faculty</div>
      <div class="fw-semibold">{{ $batch->is_faculty ? 'Yes' : 'No' }}</div>
    </div>
  </div>
</div>

<div class="card p-3 mb-3">
  <div class="fw-bold small text-uppercase text-muted mb-2" style="letter-spacing:.05em;">Guest Details</div>
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <thead>
        <tr><th>Guest Name</th><th>Age</th><th>Sex</th><th>Designation</th><th>Mobile No</th><th>Email ID</th><th>ID Proof</th><th>Main</th></tr>
      </thead>
      <tbody>
        @forelse ($batch->guests as $guest)
          <tr>
            <td class="fw-semibold">{{ $guest->guest_name }}</td>
            <td>{{ $guest->age ?? '—' }}</td>
            <td>{{ $guest->sex ?? '—' }}</td>
            <td>{{ $guest->designation ?? '—' }}</td>
            <td>{{ $guest->mobile_no }}</td>
            <td>{{ $guest->email_id ?? '—' }}</td>
            <td>{{ $guest->guest_id_proof ?? '—' }}</td>
            <td>@if($guest->is_main_guest)<span class="badge-status badge-approved">Main</span>@endif</td>
          </tr>
        @empty
          <tr><td colspan="8" class="text-muted text-center">No guest details recorded.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="card p-3 mb-3">
  <div class="fw-bold small text-uppercase text-muted mb-2" style="letter-spacing:.05em;">
    {{ $protocolRequest->typeLabel() }} Details
  </div>

  @if ($protocolRequest->request_type === 'guesthouse')
    <table class="table table-sm mb-0">
      <tr><td class="text-muted" style="width:200px;">Date From</td><td class="fw-semibold">{{ $d->date_from?->format('d M Y') }}</td></tr>
      <tr><td class="text-muted">Date To</td><td class="fw-semibold">{{ $d->date_to?->format('d M Y') }}</td></tr>
      <tr><td class="text-muted">No. of Guests</td><td class="fw-semibold">{{ $d->no_of_guests ?? '—' }}</td></tr>
      <tr><td class="text-muted">No. of Rooms</td><td class="fw-semibold">{{ $d->no_of_rooms }}</td></tr>
      <tr><td class="text-muted">Assigned Guest House</td><td class="fw-semibold">{{ $d->assigned_guest_house ?? 'Not yet assigned' }}</td></tr>
      <tr><td class="text-muted">Payment Done By</td><td class="fw-semibold">{{ $d->payment_done_by }}</td></tr>
      <tr><td class="text-muted">Remarks</td><td class="fw-semibold">{{ $d->remarks ?? '—' }}</td></tr>
    </table>

  @elseif ($protocolRequest->request_type === 'vehicle')
    <div class="row g-3 mb-3">
      <div class="col-md-4"><span class="text-muted small">Vehicle Type</span><div class="fw-semibold">{{ $d->vehicle_type ?? '—' }}</div></div>
      <div class="col-md-4"><span class="text-muted small">One Way Booking</span><div class="fw-semibold">{{ $d->one_way_booking ? 'Yes' : 'No' }}</div></div>
      <div class="col-md-4"><span class="text-muted small">Payment Will Be Done By</span><div class="fw-semibold">{{ $d->payment_will_be_done_by }}</div></div>
    </div>
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead><tr><th>From</th><th>To</th><th>Pickup</th><th>Drop</th><th>Persons</th><th>Remarks</th></tr></thead>
        <tbody>
          @foreach ($d->legs as $leg)
            <tr>
              <td class="text-muted small">{{ $leg->date_time_from?->format('d M Y, h:i A') }}</td>
              <td class="text-muted small">{{ $leg->date_time_to?->format('d M Y, h:i A') ?? '—' }}</td>
              <td>{{ $leg->pickupLabel() }}</td>
              <td>{{ $leg->dropLabel() }}</td>
              <td>{{ $leg->no_of_persons }}</td>
              <td class="text-muted small">{{ $leg->remarks ?? '—' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

  @else
    @foreach ($d->journeys as $journey)
      <div class="border rounded p-3 mb-2">
        <div class="row g-2">
          <div class="col-md-3"><span class="text-muted small">Origin</span><div class="fw-semibold">{{ $journey->origin }}</div></div>
          <div class="col-md-3"><span class="text-muted small">Destination</span><div class="fw-semibold">{{ $journey->destination }}</div></div>
          <div class="col-md-2"><span class="text-muted small">Mode</span><div class="fw-semibold">{{ $journey->ticket_type }}</div></div>
          <div class="col-md-2"><span class="text-muted small">Class</span><div class="fw-semibold">{{ $journey->class ?? '—' }}</div></div>
          <div class="col-md-2"><span class="text-muted small">Date</span><div class="fw-semibold">{{ $journey->date_of_journey?->format('d M Y') }}</div></div>
          <div class="col-md-3"><span class="text-muted small">Train/Flight/Bus</span><div class="fw-semibold">{{ $journey->train_flight_bus_name }} ({{ $journey->train_flight_bus_no }})</div></div>
          <div class="col-md-2"><span class="text-muted small">Quota</span><div class="fw-semibold">{{ $journey->quota }}</div></div>
          <div class="col-md-2"><span class="text-muted small">Waitlist OK</span><div class="fw-semibold">{{ $journey->book_if_waiting ? 'Yes' : 'No' }}</div></div>
          <div class="col-md-3"><span class="text-muted small">Payment By</span><div class="fw-semibold">{{ $journey->payment_will_be_done_by }}</div></div>
        </div>
        @if ($journey->remarks)
          <div class="text-muted small mt-2">Remarks: {{ $journey->remarks }}</div>
        @endif
      </div>
    @endforeach
  @endif
</div>
