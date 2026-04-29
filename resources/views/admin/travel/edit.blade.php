@extends('admin.layouts.master')
@section('title', 'Edit Travel Plan — '.($displayName ?? $username))

@section('setup_content')
<div class="container-fluid px-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.travel.index') }}">Travel Plans</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.travel.show', $username) }}">{{ $username }}</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="card border-0 shadow-sm" style="border-radius:10px;">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h5 class="fw-bold mb-0" style="color:#1a3c6e;">
                        <i class="bi bi-pencil-square me-2"></i>Edit Travel Plan
                    </h5>
                    <small class="text-muted">{{ $displayName ?? $username }}</small>
                </div>
                <div class="card-body p-4">
                    @if(session('error'))
                        <div class="alert alert-danger small py-2">{{ session('error') }}</div>
                    @endif

                    <form method="POST" action="{{ route('admin.travel.update', $username) }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3 mb-1">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Arrival date <span class="text-danger">*</span></label>
                                <select name="joining_date" id="joiningDateSelect" class="form-select" required>
                                    <option value="">-- Select arrival date --</option>
                                    @foreach($availableDates as $date)
                                        <option value="{{ $date }}" {{ old('joining_date', $plan->joining_date?->format('Y-m-d')) === $date ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Activity slot <span class="text-danger">*</span></label>
                                <select name="fc_travel_arrival_slot_id" id="arrivalSlotSelect" class="form-select" required>
                                    <option value="">-- Select slot --</option>
                                    @foreach($slots as $s)
                                        @php
                                            $sel = (string) old('fc_travel_arrival_slot_id', $plan->fc_travel_arrival_slot_id) === (string) $s->id;
                                            $noRoom = ! $s->hasRoomForUser($username) && ! $sel;
                                            $cap = $s->max_capacity;
                                            $other = $s->countOtherBookings($username);
                                            $left = $cap ? max(0, (int) $cap - $other) : null;
                                        @endphp
                                        @if($noRoom)
                                            @continue
                                        @endif
                                        <option value="{{ $s->id }}" data-slot-date="{{ $s->slot_date?->format('Y-m-d') }}" {{ $sel ? 'selected' : '' }}>
                                            {{ $s->slot_label }}
                                            @if($s->time_start && $s->time_end)
                                                ({{ \Illuminate\Support\Str::substr($s->time_start, 0, 5) }}–{{ \Illuminate\Support\Str::substr($s->time_end, 0, 5) }})
                                            @endif
                                            @if($left !== null) — {{ $left }} left @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-1">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Mode of journey <span class="text-danger">*</span></label>
                                <select name="mode_of_journey" class="form-select" required>
                                    <option value="">-- Select --</option>
                                    @foreach(['By Air', 'By Road', 'By Train'] as $m)
                                        <option value="{{ $m }}" {{ old('mode_of_journey', $plan->mode_of_journey) === $m ? 'selected' : '' }}>{{ $m }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Flight No / Train No / Vehicle No <span class="text-danger">*</span></label>
                                <input type="text" name="journey_vehicle_no" class="form-control" maxlength="200" required
                                       value="{{ old('journey_vehicle_no', $plan->journey_vehicle_no) }}">
                            </div>
                        </div>

                        <div class="row g-3 mb-1">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Arrival time at Dehradun Airport/Railway Station <span class="text-danger">*</span></label>
                                <input type="text" name="arrival_time_dehradun" class="form-control" maxlength="120" required
                                       value="{{ old('arrival_time_dehradun', $plan->arrival_time_dehradun) }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Whether Require Academy Vehicle From Dehradun Airport/Railway Station to Academy <span class="text-danger">*</span></label>
                                @php
                                    $requireValue = old('require_academy_vehicle');
                                    if ($requireValue === null) {
                                        $requireValue = \App\Models\FC\StudentTravelPlanMaster::interpretRequiresAcademyVehicle($plan->getRawOriginal('require_academy_vehicle')) ? '1' : '0';
                                    }
                                @endphp
                                <div class="d-flex gap-3 pt-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="require_academy_vehicle" id="reqVehYes" value="1" required
                                            {{ (string) $requireValue === '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="reqVehYes">Yes</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="require_academy_vehicle" id="reqVehNo" value="0" required
                                            {{ (string) $requireValue === '0' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="reqVehNo">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label class="form-label">Remarks (optional)</label>
                                <textarea name="special_requirements" class="form-control" rows="2" maxlength="1000">{{ old('special_requirements', $plan->special_requirements) }}</textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between flex-wrap gap-2 border-top pt-3">
                            <a href="{{ route('admin.travel.show', $username) }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                            <button type="submit" class="btn btn-success px-4">
                                <i class="bi bi-save me-1"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const dateSelect = document.getElementById('joiningDateSelect');
        const slotSelect = document.getElementById('arrivalSlotSelect');
        if (!dateSelect || !slotSelect) return;

        function filterSlotsByDate() {
            const selectedDate = dateSelect.value;
            const currentSlot = slotSelect.value;
            let hasVisible = false;

            Array.from(slotSelect.options).forEach((opt, idx) => {
                if (idx === 0) {
                    opt.hidden = false;
                    return;
                }

                const slotDate = opt.getAttribute('data-slot-date') || '';
                const isForSelectedDate = selectedDate && slotDate === selectedDate;
                opt.hidden = !isForSelectedDate;
                if (!isForSelectedDate && opt.selected) {
                    opt.selected = false;
                }
                if (isForSelectedDate) {
                    hasVisible = true;
                }
            });

            const hasCurrent = Array.from(slotSelect.options).some((opt) => !opt.hidden && opt.value === currentSlot);
            if (!hasCurrent) {
                slotSelect.value = '';
            }

            slotSelect.disabled = !selectedDate || !hasVisible;
            slotSelect.options[0].text = selectedDate && !hasVisible
                ? '-- No slot available for this date --'
                : '-- Select slot --';
        }

        dateSelect.addEventListener('change', filterSlotsByDate);
        filterSlotsByDate();
    })();
</script>
@endsection
