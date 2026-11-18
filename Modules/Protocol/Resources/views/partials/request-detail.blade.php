{{-- Expects: $protocolRequest with ->loadFullRequestable() and 'batch.guests' eager loaded --}}

@php
  $batch = $protocolRequest->batch;
  $d = $protocolRequest->requestable;
@endphp

<div class="pr-card mb-3">
  <div class="pr-card__body">
    <h3 class="pr-card__title">Request Summary</h3>
    <div class="pr-facts">
      <div><span class="pr-fact__label">Course Team To Notify</span><div class="pr-fact__value">{{ $batch->course_team_to_notify ?? '—' }}</div></div>
      <div><span class="pr-fact__label">Purpose</span><div class="pr-fact__value">{{ $batch->purpose ?? '—' }}</div></div>
      <div><span class="pr-fact__label">Escort Required</span><div class="pr-fact__value">{{ $batch->escort_required ? 'Yes' : 'No' }}</div></div>
      <div><span class="pr-fact__label">Faculty</span><div class="pr-fact__value">{{ $batch->is_faculty ? 'Yes' : 'No' }}</div></div>
    </div>
  </div>
</div>

<div class="pr-card mb-3">
  <div class="pr-card__body">
    <h3 class="pr-card__title">Guest Details</h3>
    <div class="programme-dt-panel">
      <div class="table-responsive">
        <table class="table align-middle mb-0 w-100 programme-dt-table">
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
              <tr><td colspan="8"><div class="empty-state"><i class="bi bi-people"></i>No guest details recorded.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="pr-card mb-3">
  <div class="pr-card__body">
    <h3 class="pr-card__title">{{ $protocolRequest->typeLabel() }} Details</h3>

    @if ($protocolRequest->request_type === 'guesthouse')
      <div class="pr-facts">
        <div><span class="pr-fact__label">Date From</span><div class="pr-fact__value">{{ $d->date_from?->format('d M Y') ?? '—' }}</div></div>
        <div><span class="pr-fact__label">Date To</span><div class="pr-fact__value">{{ $d->date_to?->format('d M Y') ?? '—' }}</div></div>
        <div><span class="pr-fact__label">No. of Guests</span><div class="pr-fact__value">{{ $d->no_of_guests ?? '—' }}</div></div>
        <div><span class="pr-fact__label">No. of Rooms</span><div class="pr-fact__value">{{ $d->no_of_rooms }}</div></div>
        <div><span class="pr-fact__label">Assigned Guest House</span><div class="pr-fact__value">{{ $d->assignedGuestHouse?->building_name ?? 'Not yet assigned' }}</div></div>
        <div><span class="pr-fact__label">Payment Done By</span><div class="pr-fact__value">{{ $d->payment_done_by }}</div></div>
        <div style="grid-column: 1 / -1;"><span class="pr-fact__label">Remarks</span><div class="pr-fact__value">{{ $d->remarks ?? '—' }}</div></div>
      </div>

    @elseif ($protocolRequest->request_type === 'vehicle')
      <div class="pr-facts mb-3">
        <div><span class="pr-fact__label">Vehicle</span><div class="pr-fact__value">{{ $d->vehicle?->vehicle_name ?? '—' }}</div></div>
        <div><span class="pr-fact__label">One Way Booking</span><div class="pr-fact__value">{{ $d->one_way_booking ? 'Yes' : 'No' }}</div></div>
        <div><span class="pr-fact__label">Payment Will Be Done By</span><div class="pr-fact__value">{{ $d->payment_will_be_done_by }}</div></div>
      </div>
      <div class="programme-dt-panel">
        <div class="table-responsive">
          <table class="table align-middle mb-0 w-100 programme-dt-table">
            <thead><tr><th>From</th><th>To</th><th>Pickup</th><th>Drop</th><th>Persons</th><th>Remarks</th></tr></thead>
            <tbody>
              @foreach ($d->legs as $leg)
                <tr>
                  <td>{{ $leg->date_time_from?->format('d M Y, h:i A') }}</td>
                  <td>{{ $leg->date_time_to?->format('d M Y, h:i A') ?? '—' }}</td>
                  <td>{{ $leg->pickupLabel() }}</td>
                  <td>{{ $leg->dropLabel() }}</td>
                  <td>{{ $leg->no_of_persons }}</td>
                  <td class="text-muted small">{{ $leg->remarks ?? '—' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

    @else
      @foreach ($d->journeys as $journey)
        <div class="pr-journey">
          <div class="pr-facts">
            <div><span class="pr-fact__label">Origin</span><div class="pr-fact__value">{{ $journey->origin }}</div></div>
            <div><span class="pr-fact__label">Destination</span><div class="pr-fact__value">{{ $journey->destination }}</div></div>
            <div><span class="pr-fact__label">Mode</span><div class="pr-fact__value">{{ $journey->ticket_type }}</div></div>
            <div><span class="pr-fact__label">Class</span><div class="pr-fact__value">{{ $journey->class ?? '—' }}</div></div>
            <div><span class="pr-fact__label">Date</span><div class="pr-fact__value">{{ $journey->date_of_journey?->format('d M Y') }}</div></div>
            <div><span class="pr-fact__label">Train/Flight/Bus</span><div class="pr-fact__value">{{ $journey->train_flight_bus_name }} ({{ $journey->train_flight_bus_no }})</div></div>
            <div><span class="pr-fact__label">Quota</span><div class="pr-fact__value">{{ $journey->quota }}</div></div>
            <div><span class="pr-fact__label">Waitlist OK</span><div class="pr-fact__value">{{ $journey->book_if_waiting ? 'Yes' : 'No' }}</div></div>
            <div><span class="pr-fact__label">Payment By</span><div class="pr-fact__value">{{ $journey->payment_will_be_done_by }}</div></div>
          </div>
          @if ($journey->remarks)
            <div class="text-muted small mt-2">Remarks: {{ $journey->remarks }}</div>
          @endif
        </div>
      @endforeach
    @endif
  </div>
</div>
