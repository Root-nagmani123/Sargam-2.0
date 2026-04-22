@extends('admin.layouts.master')
@section('title', 'Travel Plan — '.($step1->full_name ?? $username))

@section('setup_content')
<div class="container-fluid px-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.travel.index') }}">Travel Plans</a></li>
            <li class="breadcrumb-item active">{{ $username }}</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm mb-3" style="border-radius:10px;">
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="fw-bold mb-0" style="color:#1a3c6e;">{{ $step1->full_name ?? $username }}</h5>
                <small class="text-muted">{{ $step1?->service?->service_code ?? '' }} · {{ $step1?->mobile_no ?? '' }}</small>
            </div>
            @if($plan->is_submitted)
                <span class="badge bg-success">Submitted</span>
            @else
                <span class="badge bg-warning text-dark">Draft</span>
            @endif
        </div>
        <div class="card-body px-4 py-3">
            <div class="row small">
                <div class="col-md-6 mb-3">
                    <h6 class="text-muted text-uppercase" style="font-size:.7rem;letter-spacing:1px;">Joining</h6>
                    <p class="mb-1"><strong>Date:</strong> {{ $plan->joining_date?->format('d M Y') ?? '—' }}</p>
                    <p class="mb-1"><strong>Time:</strong> {{ $plan->joining_time ?? '—' }}</p>
                    <p class="mb-1"><strong>Type:</strong> {{ $plan->travelType?->travel_type_name ?? '—' }}</p>
                    <p class="mb-0"><strong>Departing from:</strong> {{ $plan->departure_city ?? '—' }}, {{ $plan->departure_state ?? '' }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <h6 class="text-muted text-uppercase" style="font-size:.7rem;letter-spacing:1px;">Pickup / Drop</h6>
                    <p class="mb-1"><strong>Pickup:</strong> {{ $plan->needs_pickup ? 'Yes — '.($plan->pickupType?->type_name ?? '').' @ '.($plan->pickup_from_location ?? '') : 'No' }}</p>
                    @if($plan->needs_pickup && $plan->pickup_datetime)
                        <p class="mb-1 text-muted">{{ optional($plan->pickup_datetime)->format('d M Y, H:i') }}</p>
                    @endif
                    <p class="mb-0"><strong>Drop:</strong> {{ $plan->needs_drop ? 'Yes — '.($plan->dropType?->type_name ?? '').' → '.($plan->drop_to_location ?? '') : 'No' }}</p>
                    @if($plan->needs_drop && $plan->drop_datetime)
                        <p class="mb-0 text-muted">{{ optional($plan->drop_datetime)->format('d M Y, H:i') }}</p>
                    @endif
                </div>
            </div>
            @if($plan->special_requirements)
                <div class="mb-3">
                    <h6 class="text-muted text-uppercase" style="font-size:.7rem;letter-spacing:1px;">Special requirements</h6>
                    <p class="small mb-0">{{ $plan->special_requirements }}</p>
                </div>
            @endif

            <h6 class="text-muted text-uppercase mb-2" style="font-size:.7rem;letter-spacing:1px;">Journey legs</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0" style="font-size:12px;">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Mode</th>
                            <th>Date</th>
                            <th>Dep</th>
                            <th>Arr</th>
                            <th>No./Name</th>
                            <th>Class</th>
                            <th>Ticket</th>
                            <th>₹</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($plan->legs as $lg)
                        <tr>
                            <td>{{ $lg->leg_number ?? $lg->leg_no }}</td>
                            <td>{{ $lg->from_station }}</td>
                            <td>{{ $lg->to_station }}</td>
                            <td>{{ $lg->travelMode?->travel_mode_name ?? $lg->travel_mode ?? '—' }}</td>
                            <td>{{ $lg->travel_date?->format('d M Y') ?? '—' }}</td>
                            <td>{{ $lg->departure_time ?? '—' }}</td>
                            <td>{{ $lg->arrival_time ?? '—' }}</td>
                            <td>{{ trim(($lg->train_flight_no ?? '').' '.($lg->train_flight_name ?? '')) ?: '—' }}</td>
                            <td>{{ $lg->class_of_travel ?? '—' }}</td>
                            <td>{{ $lg->pnr_ticket_no ?? '—' }}</td>
                            <td>{{ $lg->ticket_amount ?? '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <a href="{{ route('admin.travel.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
            </div>
        </div>
    </div>
</div>
@endsection
