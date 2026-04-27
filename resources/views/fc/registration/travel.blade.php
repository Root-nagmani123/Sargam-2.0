@extends('admin.layouts.master')
@section('title', 'Travel Plan – FC Registration')
@php
    $username = auth()->user()->username;
@endphp

@section('setup_content')
<div class="row justify-content-center">
<div class="col-12 col-xl-10">

    @include('partials.step-indicator', ['current' => 5])

    <div class="card border-0 shadow-sm" style="border-radius:10px;">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <h5 class="fw-bold mb-0" style="color:#1a3c6e;">
                <i class="bi bi-train-front me-2"></i>Travel Plan — Joining (Joining Date report)
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
        @endphp

        @if($readOnly)
            <p class="text-muted small mb-3">Your travel plan is on file. To change it, contact the academy office.</p>
            <dl class="row small mb-0">
                <dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $step1->full_name ?? '—' }}</dd>
                <dt class="col-sm-3">Code</dt><dd class="col-sm-9">{{ $displayCode ?? '—' }}</dd>
                <dt class="col-sm-3">Mobile</dt><dd class="col-sm-9">{{ $step1->mobile_no ?? '—' }}</dd>
                <dt class="col-sm-3">Arrival date</dt><dd class="col-sm-9">{{ $plan->joining_date?->format('d M Y') ?? '—' }}</dd>
                <dt class="col-sm-3">Slot &amp; time</dt><dd class="col-sm-9">
                    {{ $plan->fcArrivalSlot?->slot_label ?? '—' }}
                    @if($plan->fcArrivalSlot?->time_start && $plan->fcArrivalSlot?->time_end)
                        <span class="text-muted">(
                            {{ \Illuminate\Support\Str::substr($plan->fcArrivalSlot->time_start, 0, 5) }}–{{ \Illuminate\Support\Str::substr($plan->fcArrivalSlot->time_end, 0, 5) }}
                        )</span>
                    @endif
                </dd>
                <dt class="col-sm-3">Mode of journey</dt><dd class="col-sm-9">{{ $plan->mode_of_journey ?? '—' }}</dd>
                <dt class="col-sm-3">Flight / Train / Vehicle no.</dt><dd class="col-sm-9">{{ $plan->journey_vehicle_no ?? '—' }}</dd>
                <dt class="col-sm-3">Date of arrival at Academy</dt><dd class="col-sm-9">{{ $plan->academy_arrival_date?->format('d M Y') ?? '—' }}</dd>
                <dt class="col-sm-3">Arrival time at Dehradun (Airport)</dt><dd class="col-sm-9">{{ $plan->arrival_time_dehradun ?? '—' }}</dd>
                <dt class="col-sm-3">Require academy vehicle</dt>
                <dd class="col-sm-9">{{ $plan->requiresAcademyVehicleYes() ? 'Yes' : 'No' }}</dd>
            </dl>
            @if($plan->special_requirements)
                <p class="small mt-2"><span class="text-muted">Remarks:</span> {{ $plan->special_requirements }}</p>
            @endif
            <div class="mt-4 d-flex gap-2">
                <a href="{{ route('fc-reg.registration.documents') }}" class="btn btn-primary"><i class="bi bi-file-earmark-arrow-up me-1"></i>Continue to Documents</a>
                <a href="{{ route('fc-reg.dashboard') }}" class="btn btn-outline-secondary">Dashboard</a>
            </div>
        @else
        <p class="text-muted small">Fields follow the <strong>Joining Date</strong> report format. Choose an <strong>arrival time slot</strong> managed by the academy. Save a draft, then submit.</p>
        <form method="POST" action="{{ route('fc-reg.registration.travel.save') }}" id="travelDraftForm">
            @csrf
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Arrival date <span class="text-danger">*</span></label>
                    <input type="date" name="joining_date" class="form-control" required
                           value="{{ old('joining_date', $plan?->joining_date?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Expected time (optional)</label>
                    <input type="time" name="joining_time" class="form-control"
                           value="{{ old('joining_time', $plan?->joining_time) }}">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Arrival time slot <span class="text-danger">*</span></label>
                <select name="fc_travel_arrival_slot_id" class="form-select" required>
                    <option value="">-- Select slot --</option>
                    @forelse($slots as $s)
                        @php
                            $sel = (string) old('fc_travel_arrival_slot_id', $plan?->fc_travel_arrival_slot_id) === (string) $s->id;
                            $noRoom = ! $s->hasRoomForUser($username) && ! $sel;
                            $cap = $s->max_capacity;
                            $other = $s->countOtherBookings($username);
                            $left = $cap ? max(0, (int) $cap - $other) : null;
                        @endphp
                        <option value="{{ $s->id }}" {{ $sel ? 'selected' : '' }} @if($noRoom) disabled @endif>
                            {{ $s->slot_label }}
                            @if($s->time_start && $s->time_end)
                                ({{ \Illuminate\Support\Str::substr($s->time_start, 0, 5) }}–{{ \Illuminate\Support\Str::substr($s->time_end, 0, 5) }})
                            @endif
                            @if($left !== null) — {{ $left }} left @endif
                        </option>
                    @empty
                        <option value="" disabled>No slots available — please contact the academy</option>
                    @endforelse
                </select>
                <div class="form-text">Slots and capacity are set by the office in Admin → Travel slots.</div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Mode of journey <span class="text-danger">*</span></label>
                    <select name="mode_of_journey" class="form-select" required>
                        <option value="">-- Select --</option>
                        @foreach(['By Air', 'By Road', 'By Train'] as $m)
                            <option value="{{ $m }}" {{ old('mode_of_journey', $plan?->mode_of_journey) === $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Flight / Train / Vehicle no.</label>
                    <input type="text" name="journey_vehicle_no" class="form-control" maxlength="200"
                           value="{{ old('journey_vehicle_no', $plan?->journey_vehicle_no) }}">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Date of arrival at Academy</label>
                    <input type="date" name="academy_arrival_date" class="form-control"
                           value="{{ old('academy_arrival_date', $plan?->academy_arrival_date?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Arrival time at Dehradun (Airport)</label>
                    <input type="text" name="arrival_time_dehradun" class="form-control" maxlength="120" placeholder="e.g. 6:00 AM"
                           value="{{ old('arrival_time_dehradun', $plan?->arrival_time_dehradun) }}">
                </div>
            </div>

            <div class="form-check mb-3">
                <input type="checkbox" name="require_academy_vehicle" value="1" class="form-check-input" id="reqVeh"
                    {{ old('require_academy_vehicle', $plan?->require_academy_vehicle) ? 'checked' : '' }}>
                <label class="form-check-label small" for="reqVeh">I require a vehicle from Dehradun (Airport) / station</label>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold">Remarks (optional)</label>
                <textarea name="special_requirements" class="form-control" rows="2" maxlength="1000"
                          placeholder="Special needs…">{{ old('special_requirements', $plan?->special_requirements) }}</textarea>
            </div>

            <div class="d-flex justify-content-between flex-wrap gap-2 border-top pt-3">
                <a href="{{ route('fc-reg.registration.bank') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Back to Bank
                </a>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1"></i>Save Draft
                </button>
            </div>
        </form>

        @if($plan && $plan->fc_travel_arrival_slot_id && $plan->joining_date && $plan->mode_of_journey)
            <form method="POST" action="{{ route('fc-reg.registration.travel.submit') }}" class="mt-3 d-flex justify-content-end"
                  onsubmit="return confirm('Submit your travel plan? You can then upload documents.');">
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
{{-- Legacy (pre–Apr 2026) full travel form: see Git history for `resources/views/fc/registration/travel.blade.php` and `TravelPlanController@save` with multi-leg MCTP details. --}}
@endsection
