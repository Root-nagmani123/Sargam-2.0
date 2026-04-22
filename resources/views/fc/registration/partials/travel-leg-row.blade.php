@php
    $classes = ['3AC','2AC','1AC','Sleeper','Economy','Business','General'];
    $clsVal = $leg['class_of_travel'] ?? '';
@endphp
<div class="leg-row border rounded p-3 mb-2 bg-light position-relative">
    @if($i > 0)
        <button type="button" class="btn-close position-absolute top-0 end-0 m-2"
                onclick="this.closest('.leg-row').remove()" title="Remove leg"></button>
    @endif
    <div class="row-label mb-2 small text-muted fw-semibold">Leg {{ $i + 1 }}</div>
    <div class="row g-2">
        <div class="col-md-2">
            <label class="form-label small">From City <span class="text-danger">*</span></label>
            <input type="text" name="legs[{{ $i }}][from_city]" class="form-control form-control-sm"
                   value="{{ $leg['from_city'] ?? '' }}" placeholder="Departure city" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small">To City <span class="text-danger">*</span></label>
            <input type="text" name="legs[{{ $i }}][to_city]" class="form-control form-control-sm"
                   value="{{ $leg['to_city'] ?? '' }}" placeholder="Arrival city" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Mode <span class="text-danger">*</span></label>
            <select name="legs[{{ $i }}][travel_mode_id]" class="form-select form-select-sm" required>
                <option value="">-- Select --</option>
                @foreach($travelModes as $m)
                    <option value="{{ $m->id }}"
                        {{ (string) ($leg['travel_mode_id'] ?? '') === (string) $m->id ? 'selected' : '' }}>
                        {{ $m->travel_mode_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Travel Date</label>
            <input type="date" name="legs[{{ $i }}][travel_date]" class="form-control form-control-sm"
                   value="{{ isset($leg['travel_date']) && $leg['travel_date'] ? (is_string($leg['travel_date']) ? $leg['travel_date'] : $leg['travel_date']->format('Y-m-d')) : '' }}">
        </div>
        <div class="col-md-1">
            <label class="form-label small">Dep.</label>
            <input type="time" name="legs[{{ $i }}][departure_time]" class="form-control form-control-sm"
                   value="{{ $leg['departure_time'] ?? '' }}">
        </div>
        <div class="col-md-1">
            <label class="form-label small">Arr.</label>
            <input type="time" name="legs[{{ $i }}][arrival_time]" class="form-control form-control-sm"
                   value="{{ $leg['arrival_time'] ?? '' }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Train/Flight No.</label>
            <input type="text" name="legs[{{ $i }}][train_flight_bus_no]" class="form-control form-control-sm"
                   value="{{ $leg['train_flight_bus_no'] ?? '' }}" placeholder="e.g. 12055">
        </div>
        <div class="col-md-3">
            <label class="form-label small">Train/Flight Name</label>
            <input type="text" name="legs[{{ $i }}][train_flight_name]" class="form-control form-control-sm"
                   value="{{ $leg['train_flight_name'] ?? '' }}" placeholder="e.g. Shatabdi Express">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Class</label>
            <select name="legs[{{ $i }}][class_of_travel]" class="form-select form-select-sm">
                <option value="">--</option>
                @foreach($classes as $cls)
                    <option value="{{ $cls }}" {{ $clsVal === $cls ? 'selected' : '' }}>{{ $cls }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Ticket No.</label>
            <input type="text" name="legs[{{ $i }}][ticket_no]" class="form-control form-control-sm"
                   value="{{ $leg['ticket_no'] ?? '' }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Amount (₹)</label>
            <input type="number" name="legs[{{ $i }}][ticket_amount]" class="form-control form-control-sm" min="0" step="0.01"
                   value="{{ $leg['ticket_amount'] ?? '' }}">
        </div>
    </div>
</div>
