<div class="gtc-travel-item border rounded-3 p-3 mb-3" data-travel-row>
    <div class="gtc-travel-item-head">
        <h4 class="h6 mb-0">Journey details</h4>
        <button type="button" class="btn btn-outline-danger btn-sm" data-gtc-remove-travel-row>
            <i class="bi bi-trash"></i> Remove journey
        </button>
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="departure-at-{{ $index }}">Departure date &amp; time</label>
            <input type="datetime-local" class="form-control" id="departure-at-{{ $index }}" name="travelling_allowances[{{ $index }}][departure_at]" value="{{ $row['departure_at'] ?? '' }}">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="departure-from-{{ $index }}">Departure from</label>
            <input class="form-control" id="departure-from-{{ $index }}" name="travelling_allowances[{{ $index }}][departure_from]" maxlength="200" value="{{ $row['departure_from'] ?? '' }}">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="arrival-at-{{ $index }}">Arrival date &amp; time</label>
            <input type="datetime-local" class="form-control" id="arrival-at-{{ $index }}" name="travelling_allowances[{{ $index }}][arrival_at]" value="{{ $row['arrival_at'] ?? '' }}">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="arrival-to-{{ $index }}">Arrival to</label>
            <input class="form-control" id="arrival-to-{{ $index }}" name="travelling_allowances[{{ $index }}][arrival_to]" maxlength="200" value="{{ $row['arrival_to'] ?? '' }}">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="travel-mode-{{ $index }}">Mode of travel &amp; class of accommodation</label>
            <select class="form-select" id="travel-mode-{{ $index }}" name="travelling_allowances[{{ $index }}][travel_mode]">
                <option value="">Select</option>
                @foreach (config('guest-ta-claim.travel_modes') as $mode)
                    <option value="{{ $mode }}" {{ ($row['travel_mode'] ?? '') === $mode ? 'selected' : '' }}>{{ $mode }}</option>
                @endforeach
                <option value="Other" {{ ($row['travel_mode'] ?? '') === 'Other' ? 'selected' : '' }}>Other</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="distance-km-{{ $index }}">Distance (km)</label>
            <input type="number" min="0" step="0.01" class="form-control" id="distance-km-{{ $index }}" name="travelling_allowances[{{ $index }}][distance_km]" value="{{ $row['distance_km'] ?? '' }}">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="fare-paid-{{ $index }}">Fare paid (₹)</label>
            <input type="number" min="0" step="0.01" class="form-control" id="fare-paid-{{ $index }}" name="travelling_allowances[{{ $index }}][fare_paid]" value="{{ $row['fare_paid'] ?? '' }}">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="fare-admissible-{{ $index }}">Fare admissible (₹)</label>
            <input type="number" min="0" step="0.01" class="form-control" id="fare-admissible-{{ $index }}" name="travelling_allowances[{{ $index }}][fare_admissible]" value="{{ $row['fare_admissible'] ?? '' }}">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="accommodation-{{ $index }}">Accommodation (₹)</label>
            <input type="number" min="0" step="0.01" class="form-control" id="accommodation-{{ $index }}" name="travelling_allowances[{{ $index }}][accommodation]" value="{{ $row['accommodation'] ?? '' }}">
        </div>
    </div>
</div>
