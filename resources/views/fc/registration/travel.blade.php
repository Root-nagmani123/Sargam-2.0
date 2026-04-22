@extends('admin.layouts.master')
@section('title', 'Travel Plan – FC Registration')

@section('setup_content')
<div class="row justify-content-center">
<div class="col-12 col-xl-10">

    @include('partials.step-indicator', ['current' => 5])

    <div class="card border-0 shadow-sm" style="border-radius:10px;">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <h5 class="fw-bold mb-0" style="color:#1a3c6e;">
                <i class="bi bi-train-front me-2"></i>Travel Plan — Joining Details
            </h5>
            @if($step1)<small class="text-muted">{{ $step1->full_name }}</small>@endif
            @if($plan?->is_submitted)
                <span class="badge bg-success ms-2">Submitted</span>
            @elseif($plan)
                <span class="badge bg-warning text-dark ms-2">Draft</span>
            @endif
        </div>

        <div class="card-body p-4">
        @if(session('success'))
            <div class="alert alert-success small py-2">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger small py-2">{{ session('error') }}</div>
        @endif

        @php
            $readOnly = $plan?->is_submitted;
            $rows = count($legRows) ? $legRows : [[]];
        @endphp

        @if($readOnly)
            <p class="text-muted small mb-3">Your travel plan is on file. To change it, contact the academy office.</p>
            <dl class="row small mb-0">
                <dt class="col-sm-3">Joining date</dt><dd class="col-sm-9">{{ $plan->joining_date?->format('d M Y') ?? '—' }}</dd>
                <dt class="col-sm-3">Journey type</dt><dd class="col-sm-9">{{ $plan->travelType?->travel_type_name ?? '—' }}</dd>
                <dt class="col-sm-3">From</dt><dd class="col-sm-9">{{ $plan->departure_city ?? '—' }}, {{ $plan->departure_state ?? '' }}</dd>
                <dt class="col-sm-3">Pickup</dt><dd class="col-sm-9">{{ $plan->needs_pickup ? 'Yes — '.($plan->pickup_from_location ?? '') : 'No' }}</dd>
            </dl>
            @if($plan->legs->isNotEmpty())
                <h6 class="mt-3 small fw-bold text-muted">Journey legs</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0" style="font-size:12px;">
                        <thead class="table-light"><tr><th>#</th><th>From</th><th>To</th><th>Mode</th><th>Date</th></tr></thead>
                        <tbody>
                        @foreach($plan->legs as $lg)
                            <tr>
                                <td>{{ $lg->leg_number ?? $lg->leg_no }}</td>
                                <td>{{ $lg->from_station }}</td>
                                <td>{{ $lg->to_station }}</td>
                                <td>{{ $lg->travelMode?->travel_mode_name ?? $lg->travel_mode ?? '—' }}</td>
                                <td>{{ $lg->travel_date?->format('d M Y') ?? '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            <div class="mt-4 d-flex gap-2">
                <a href="{{ route('fc-reg.registration.documents') }}" class="btn btn-primary"><i class="bi bi-file-earmark-arrow-up me-1"></i>Continue to Documents</a>
                <a href="{{ route('fc-reg.dashboard') }}" class="btn btn-outline-secondary">Dashboard</a>
            </div>
        @else
        <form method="POST" action="{{ route('fc-reg.registration.travel.save') }}" id="travelDraftForm">
            @csrf

            <h6 class="text-uppercase text-muted fw-semibold mb-3 border-bottom pb-2" style="font-size:.72rem;letter-spacing:1px;">
                1. Joining Details at Mussoorie
            </h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Expected Date of Arrival <span class="text-danger">*</span></label>
                    <input type="date" name="joining_date" class="form-control"
                           value="{{ old('joining_date', $plan?->joining_date?->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Expected Time</label>
                    <input type="time" name="joining_time" class="form-control"
                           value="{{ old('joining_time', $plan?->joining_time) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Journey Type <span class="text-danger">*</span></label>
                    <select name="travel_type_id" class="form-select" required>
                        <option value="">-- Select --</option>
                        @foreach($travelTypes as $tt)
                            <option value="{{ $tt->id }}" {{ (string) old('travel_type_id', $plan?->travel_type_id) === (string) $tt->id ? 'selected' : '' }}>
                                {{ $tt->travel_type_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Departing City <span class="text-danger">*</span></label>
                    <input type="text" name="departure_city" class="form-control" required
                           value="{{ old('departure_city', $plan?->departure_city) }}" placeholder="e.g. New Delhi">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Departing State <span class="text-danger">*</span></label>
                    <input type="text" name="departure_state" class="form-control" required
                           value="{{ old('departure_state', $plan?->departure_state) }}" placeholder="e.g. Delhi">
                </div>
            </div>

            <h6 class="text-uppercase text-muted fw-semibold mb-3 border-bottom pb-2" style="font-size:.72rem;letter-spacing:1px;">
                2. Pick-up from Station / Airport
            </h6>
            <div class="row g-3 mb-1">
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" name="needs_pickup" value="1" class="form-check-input" id="needsPickup"
                               {{ old('needs_pickup', $plan?->needs_pickup) ? 'checked' : '' }}
                               onchange="toggleSection('pickupSection', this.checked)">
                        <label class="form-check-label fw-semibold small" for="needsPickup">
                            I need a pick-up from station / airport / bus stand
                        </label>
                    </div>
                </div>
            </div>
            <div id="pickupSection" class="row g-3 mb-4 mt-1 ps-3"
                 style="display:{{ old('needs_pickup', $plan?->needs_pickup) ? 'flex' : 'none' }};flex-wrap:wrap;">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Pick-up Type</label>
                    <select name="pickup_type_id" class="form-select">
                        <option value="">-- Select --</option>
                        @foreach($pickupTypes as $pt)
                            <option value="{{ $pt->id }}" {{ (string) old('pickup_type_id', $plan?->pickup_type_id) === (string) $pt->id ? 'selected' : '' }}>
                                {{ $pt->type_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Pick-up From</label>
                    <input type="text" name="pickup_from_location" class="form-control"
                           value="{{ old('pickup_from_location', $plan?->pickup_from_location) }}"
                           placeholder="e.g. Dehradun Railway Station">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Pick-up Date &amp; Time</label>
                    <input type="datetime-local" name="pickup_datetime" class="form-control"
                           value="{{ old('pickup_datetime', $plan?->pickup_datetime?->format('Y-m-d\TH:i')) }}">
                </div>
            </div>

            <h6 class="text-uppercase text-muted fw-semibold mb-3 border-bottom pb-2" style="font-size:.72rem;letter-spacing:1px;">
                3. Drop to Station / Airport (departure from Mussoorie)
            </h6>
            <div class="row g-3 mb-1">
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" name="needs_drop" value="1" class="form-check-input" id="needsDrop"
                               {{ old('needs_drop', $plan?->needs_drop) ? 'checked' : '' }}
                               onchange="toggleSection('dropSection', this.checked)">
                        <label class="form-check-label fw-semibold small" for="needsDrop">
                            I need a drop to station / airport / bus stand on departure
                        </label>
                    </div>
                </div>
            </div>
            <div id="dropSection" class="row g-3 mb-4 mt-1 ps-3"
                 style="display:{{ old('needs_drop', $plan?->needs_drop) ? 'flex' : 'none' }};flex-wrap:wrap;">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Drop Type</label>
                    <select name="drop_type_id" class="form-select">
                        <option value="">-- Select --</option>
                        @foreach($pickupTypes as $pt)
                            <option value="{{ $pt->id }}" {{ (string) old('drop_type_id', $plan?->drop_type_id) === (string) $pt->id ? 'selected' : '' }}>
                                {{ $pt->type_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Drop To</label>
                    <input type="text" name="drop_to_location" class="form-control"
                           value="{{ old('drop_to_location', $plan?->drop_to_location) }}"
                           placeholder="e.g. Jolly Grant Airport">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Drop Date &amp; Time</label>
                    <input type="datetime-local" name="drop_datetime" class="form-control"
                           value="{{ old('drop_datetime', $plan?->drop_datetime?->format('Y-m-d\TH:i')) }}">
                </div>
            </div>

            <h6 class="text-uppercase text-muted fw-semibold mb-3 border-bottom pb-2" style="font-size:.72rem;letter-spacing:1px;">
                4. Journey details (leg by leg)
            </h6>

            <div id="legsContainer">
                @foreach($rows as $i => $leg)
                    @include('fc.registration.partials.travel-leg-row', ['leg' => $leg, 'i' => $i, 'travelModes' => $travelModes])
                @endforeach
            </div>

            <button type="button" class="btn btn-sm btn-outline-primary mt-2 mb-4" id="addLegBtn">
                <i class="bi bi-plus-circle me-1"></i>Add Another Journey Leg
            </button>

            <div class="mb-4">
                <label class="form-label small fw-semibold">Special Requirements / Remarks</label>
                <textarea name="special_requirements" class="form-control" rows="3"
                          placeholder="Wheelchair, medical equipment, other needs…">{{ old('special_requirements', $plan?->special_requirements) }}</textarea>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 border-top pt-3">
                <a href="{{ route('fc-reg.registration.bank') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Back to Bank
                </a>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1"></i>Save Draft
                </button>
            </div>
        </form>

        @if($plan && $plan->legs->isNotEmpty())
            <form method="POST" action="{{ route('fc-reg.registration.travel.submit') }}" class="mt-3 d-flex justify-content-end"
                  onsubmit="return confirm('Submit your travel plan? After submission you can continue to document upload.');">
                @csrf
                <button type="submit" class="btn btn-success px-4">
                    <i class="bi bi-send-check me-1"></i>Submit Travel Plan
                </button>
            </form>
        @endif
        @endif
        </div>
    </div>

</div>
</div>
@endsection

@push('scripts')
@if(empty($readOnly))
@php
    $modesForJs = $travelModes->map(function ($m) {
        return ['id' => $m->id, 'name' => $m->travel_mode_name];
    })->values();
@endphp
<script>
function toggleSection(id, show) {
    var el = document.getElementById(id);
    if (el) el.style.display = show ? 'flex' : 'none';
}
var legCount = {{ count($rows) }};
document.getElementById('addLegBtn')?.addEventListener('click', function () {
    var i = legCount++;
    var modes = @json($modesForJs);
    var opts = modes.map(function (m) {
        return '<option value="' + m.id + '">' + (m.name || '').replace(/</g, '') + '</option>';
    }).join('');
    var html = '<div class="leg-row border rounded p-3 mb-2 bg-light position-relative">' +
        '<button type="button" class="btn-close position-absolute top-0 end-0 m-2" onclick="this.closest(\'.leg-row\').remove()" title="Remove leg"></button>' +
        '<div class="row-label mb-2 small text-muted fw-semibold">Leg ' + (i + 1) + '</div>' +
        '<div class="row g-2">' +
        '<div class="col-md-2"><label class="form-label small">From City *</label>' +
        '<input type="text" name="legs[' + i + '][from_city]" class="form-control form-control-sm" required placeholder="Departure city"></div>' +
        '<div class="col-md-2"><label class="form-label small">To City *</label>' +
        '<input type="text" name="legs[' + i + '][to_city]" class="form-control form-control-sm" required placeholder="Arrival city"></div>' +
        '<div class="col-md-2"><label class="form-label small">Mode *</label>' +
        '<select name="legs[' + i + '][travel_mode_id]" class="form-select form-select-sm" required>' +
        '<option value="">--</option>' + opts + '</select></div>' +
        '<div class="col-md-2"><label class="form-label small">Travel Date</label>' +
        '<input type="date" name="legs[' + i + '][travel_date]" class="form-control form-control-sm"></div>' +
        '<div class="col-md-1"><label class="form-label small">Dep.</label>' +
        '<input type="time" name="legs[' + i + '][departure_time]" class="form-control form-control-sm"></div>' +
        '<div class="col-md-1"><label class="form-label small">Arr.</label>' +
        '<input type="time" name="legs[' + i + '][arrival_time]" class="form-control form-control-sm"></div>' +
        '<div class="col-md-2"><label class="form-label small">Train/Flight No.</label>' +
        '<input type="text" name="legs[' + i + '][train_flight_bus_no]" class="form-control form-control-sm"></div>' +
        '<div class="col-md-3"><label class="form-label small">Train/Flight Name</label>' +
        '<input type="text" name="legs[' + i + '][train_flight_name]" class="form-control form-control-sm"></div>' +
        '<div class="col-md-2"><label class="form-label small">Class</label>' +
        '<select name="legs[' + i + '][class_of_travel]" class="form-select form-select-sm">' +
        '<option value="">--</option>' +
        ['3AC','2AC','1AC','Sleeper','Economy','Business','General'].map(function (c) {
            return '<option value="' + c + '">' + c + '</option>';
        }).join('') + '</select></div>' +
        '<div class="col-md-2"><label class="form-label small">Ticket No.</label>' +
        '<input type="text" name="legs[' + i + '][ticket_no]" class="form-control form-control-sm"></div>' +
        '<div class="col-md-2"><label class="form-label small">Amount (₹)</label>' +
        '<input type="number" name="legs[' + i + '][ticket_amount]" class="form-control form-control-sm" min="0" step="0.01"></div>' +
        '</div></div>';
    document.getElementById('legsContainer').insertAdjacentHTML('beforeend', html);
});
</script>
@endif
@endpush
