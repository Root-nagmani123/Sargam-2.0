@extends('fc.layouts.master')
@section('title', 'Travel Plan – FC Registration')
@php
    $userId = auth()->id();
@endphp

@section('content')
@include('fc.registration.partials.fc-form-theme')
<style>
    .travel-form-enhanced {
        font-size: 1.02rem;
    }
    .travel-form-enhanced .form-label {
        font-weight: 700;
        color: #163b6d;
        margin-bottom: .4rem;
        font-size: .98rem;
    }
    .travel-form-enhanced .form-text {
        font-size: .86rem;
    }
    .travel-form-enhanced .form-control,
    .travel-form-enhanced .form-select,
    .travel-form-enhanced .form-check-label {
        font-size: .96rem;
    }
    .travel-form-enhanced .form-control,
    .travel-form-enhanced .form-select {
        min-height: 42px;
    }
    .travel-form-enhanced .field-block {
        margin-bottom: 1rem;
    }
    .travel-form-enhanced .vehicle-choice-group {
        display: flex;
        gap: 1.25rem;
        align-items: center;
        min-height: 42px;
        padding: .35rem .2rem;
    }
    .travel-form-enhanced .vehicle-choice-group .form-check {
        margin-bottom: 0;
        min-width: 72px;
    }
    .travel-form-enhanced .text-danger {
        font-weight: 700;
    }
    .travel-form-enhanced .preview-list dt {
        color: #163b6d;
        font-weight: 700;
        margin-bottom: .2rem;
    }
    .travel-form-enhanced .preview-list dd {
        margin-bottom: .9rem;
    }
    .travel-form-enhanced .preview-list dd.preview-highlight {
        background: #f8fafc;
        border: 1px solid #e7eef9;
        border-radius: .5rem;
        padding: .45rem .6rem;
        color: #1f2d3d;
        line-height: 1.35;
        min-height: 36px;
    }
    .travel-form-enhanced .preview-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, .52);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        padding: 1rem;
    }
    .travel-form-enhanced .preview-modal-overlay.is-open {
        display: flex;
    }
    .travel-form-enhanced .preview-modal-panel {
        width: 100%;
        max-width: 820px;
        max-height: 90vh;
        overflow-y: auto;
        background: #fff;
        border-radius: .8rem;
        box-shadow: 0 20px 50px rgba(0, 0, 0, .22);
    }
    .travel-form-enhanced .preview-modal-header,
    .travel-form-enhanced .preview-modal-footer {
        padding: .9rem 1rem;
        border-bottom: 1px solid #e9ecef;
    }
    .travel-form-enhanced .preview-modal-footer {
        border-bottom: 0;
        border-top: 1px solid #e9ecef;
        display: flex;
        justify-content: flex-end;
        gap: .6rem;
    }
    .travel-form-enhanced .preview-modal-body {
        padding: 1rem;
    }
</style>
<div class="fc-form-page">
<div class="fc-shell">
    @php
        $navForm = $formStepNav['form'] ?? null;
        $navItems = $formStepNav['items'] ?? [];
        $travelIdx = collect($navItems)->search(fn ($it) => ! empty($it['current']));
        $travelStepNo = $travelIdx !== false ? $travelIdx + 1 : 5;
        $travelStepTotal = count($navItems);
    @endphp
    <div class="fc-band">
        <div class="fc-band__row">
            <div class="fc-band__ico"><i class="bi bi-train-front"></i></div>
            <div>
                <h4>{{ $navForm->form_name ?? 'Foundation Course' }}</h4>
                <p>@if($travelStepTotal > 0)Step {{ $travelStepNo }} of {{ $travelStepTotal }} — @endif Travel Plan</p>
            </div>
            @if($navForm)
                <a href="{{ route('fc-reg.forms.dashboard', $navForm) }}" class="btn btn-light btn-sm ms-auto rounded-pill px-3">
                    <i class="bi bi-grid me-1"></i>All Steps
                </a>
            @endif
        </div>
    </div>

    @if(!empty($formStepNav))
        @include('fc.registration.partials.form-step-nav', ['formStepNav' => $formStepNav])
    @else
        @include('partials.step-indicator', ['current' => 5])
    @endif

    <div class="card fc-card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <h5 class="fw-bold mb-0" style="color:#1a3c6e;">
                <i class="bi bi-train-front me-2"></i>Travel Plan — Joining (Joining Date report)
            </h5>
            @if($step1)<small class="text-muted">{{ $step1->full_name }}</small>@endif
            @if($plan?->is_submitted)
                <span class="badge bg-success ms-2">Submitted</span>
            @endif
        </div>

        <div class="card-body p-4 travel-form-enhanced">
        @if(session('success'))
            <div class="alert alert-success small py-2">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger small py-2">{{ session('error') }}</div>
        @endif

        @php
            $readOnly = $plan?->is_submitted;
            $requireAcademyVehicleValue = old('require_academy_vehicle');
            if ($requireAcademyVehicleValue === null && $plan) {
                $rawRequireAcademyVehicle = $plan->getRawOriginal('require_academy_vehicle');
                if ($rawRequireAcademyVehicle !== null && $rawRequireAcademyVehicle !== '') {
                    $requireAcademyVehicleValue = \App\Models\FC\StudentTravelPlanMaster::interpretRequiresAcademyVehicle($rawRequireAcademyVehicle) ? '1' : '0';
                }
            }
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
                <dt class="col-sm-3">Arrival time at Dehradun</dt><dd class="col-sm-9">{{ $plan->arrival_time_dehradun ?? '—' }}</dd>
                <dt class="col-sm-3">Require academy vehicle</dt>
                <dd class="col-sm-9">{{ $plan->requiresAcademyVehicleYes() ? 'Yes' : 'No' }}</dd>
            </dl>
            @if($plan->special_requirements)
                <p class="small mt-2"><span class="text-muted">Remarks:</span> {{ $plan->special_requirements }}</p>
            @endif
            <div class="mt-4 d-flex gap-2 flex-wrap">
                @if(!empty($travelNav['continueUrl']))
                    <a href="{{ $travelNav['continueUrl'] }}" class="btn btn-primary">
                        <i class="bi bi-file-earmark-arrow-up me-1"></i>{{ $travelNav['continueLabel'] ?? 'Continue' }}
                    </a>
                @endif
                <a href="{{ $travelNav['backUrl'] ?? route('fc-reg.registration.bank') }}" class="btn btn-outline-secondary">
                    {{ $travelNav['backLabel'] ?? 'Back' }}
                </a>
            </div>
        @else
        <p class="text-muted small">Fields follow the <strong>Joining Date</strong> report format. Arrival date and slot are managed by the academy. Submit to confirm your travel plan.</p>
        <form method="POST" action="{{ route('fc-reg.registration.travel.submit') }}" id="travelSubmitForm">
            @csrf
            <div class="row g-3 mb-1">
                <div class="col-12 col-md-6 field-block">
                    <label class="form-label">Arrival date <span class="text-danger">*</span></label>
                    <select name="joining_date" id="joiningDateSelect" class="form-select" required>
                        <option value="">-- Select arrival date --</option>
                        @foreach($availableDates as $date)
                            <option value="{{ $date }}" {{ old('joining_date', $plan?->joining_date?->format('Y-m-d')) === $date ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text text-muted">Only dates configured by admin are available.</div>
                </div>
                <div class="col-12 col-md-6 field-block">
                    <label class="form-label">Activity slot <span class="text-danger">*</span></label>
                    <select name="fc_travel_arrival_slot_id" id="arrivalSlotSelect" class="form-select" required>
                        <option value="">-- Select slot --</option>
                        @php $visibleSlotCount = 0; @endphp
                        @foreach($slots as $s)
                            @php
                                $sel = (string) old('fc_travel_arrival_slot_id', $plan?->fc_travel_arrival_slot_id) === (string) $s->id;
                                $noRoom = ! $s->hasRoomForUser($userId) && ! $sel;
                                $cap = $s->max_capacity;
                                $other = $s->countOtherBookings($userId);
                                $left = $cap ? max(0, (int) $cap - $other) : null;
                            @endphp
                            @if($noRoom)
                                @continue
                            @endif
                            @php $visibleSlotCount++; @endphp
                            <option value="{{ $s->id }}" data-slot-date="{{ $s->slot_date?->format('Y-m-d') }}" {{ $sel ? 'selected' : '' }}>
                                {{ $s->slot_label }}
                                @if($s->time_start && $s->time_end)
                                    ({{ \Illuminate\Support\Str::substr($s->time_start, 0, 5) }}–{{ \Illuminate\Support\Str::substr($s->time_end, 0, 5) }})
                                @endif
                                @if($left !== null) — {{ $left }} left @endif
                            </option>
                        @endforeach
                        @if($visibleSlotCount === 0)
                            <option value="" disabled>No slots available — please contact the academy</option>
                        @endif
                    </select>
                    <div class="form-text text-muted">Slots are shown only for the selected arrival date.</div>
                </div>
                {{-- Keep for future use (currently hidden):
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Expected time (optional)</label>
                    <input type="time" name="joining_time" class="form-control"
                           value="{{ old('joining_time', $plan?->joining_time) }}">
                </div>
                --}}
            </div>

            <div class="row g-3 mb-1">
                <div class="col-12 col-md-6 field-block">
                    <label class="form-label">Mode of journey <span class="text-danger">*</span></label>
                    <select name="mode_of_journey" class="form-select" required>
                        <option value="">-- Select --</option>
                        @foreach(['By Air', 'By Road', 'By Train'] as $m)
                            <option value="{{ $m }}" {{ old('mode_of_journey', $plan?->mode_of_journey) === $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 field-block">
                    <label class="form-label">Flight No / Train No/ Vehicle No <span class="text-danger">*</span></label>
                    <input type="text" name="journey_vehicle_no" class="form-control" maxlength="200" required
                           value="{{ old('journey_vehicle_no', $plan?->journey_vehicle_no) }}">
                </div>
            </div>

            <div class="row g-3 mb-1">
                {{-- Keep for future use (currently hidden):
                <div class="col-12 col-md-6 field-block">
                    <label class="form-label small fw-semibold">Date of arrival at Academy</label>
                    <input type="date" name="academy_arrival_date" class="form-control"
                           value="{{ old('academy_arrival_date', $plan?->academy_arrival_date?->format('Y-m-d')) }}">
                </div>
                --}}
                <div class="col-12 col-md-6 field-block">
                    <label class="form-label">Arrival time at Dehradun Airport/Railway Station<span class="text-danger">*</span></label>
                    <input type="text" name="arrival_time_dehradun" class="form-control" maxlength="120" placeholder="e.g. 6:00 AM" required
                           value="{{ old('arrival_time_dehradun', $plan?->arrival_time_dehradun) }}">
                </div>
                <div class="col-12 col-md-6 field-block">
                    <label class="form-label">Whether Require Academy Vehicle From Dehradun Airport/Railway Station to Academy <span class="text-danger">*</span></label>
                    <div class="vehicle-choice-group">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="require_academy_vehicle" id="reqVehYes" value="1" required
                                {{ (string) $requireAcademyVehicleValue === '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="reqVehYes">Yes</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="require_academy_vehicle" id="reqVehNo" value="0" required
                                {{ (string) $requireAcademyVehicleValue === '0' ? 'checked' : '' }}>
                            <label class="form-check-label" for="reqVehNo">No</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-1">
                <div class="col-12 field-block mb-4">
                    <label class="form-label">Remarks (optional)</label>
                    <textarea name="special_requirements" class="form-control" rows="2" maxlength="1000"
                              placeholder="Special needs…">{{ old('special_requirements', $plan?->special_requirements) }}</textarea>
                </div>
            </div>

            <div class="d-flex justify-content-between flex-wrap gap-2 border-top pt-3">
                <a href="{{ $travelNav['backUrl'] ?? route('fc-reg.registration.bank') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>{{ $travelNav['backLabel'] ?? 'Back to Bank' }}
                </a>
                <button type="button" class="btn btn-success px-4" id="previewTravelPlanBtn">
                    <i class="bi bi-send-check me-1"></i>Submit Travel Plan
                </button>
            </div>
        </form>
        <div class="preview-modal-overlay" id="travelPlanPreviewModal" aria-hidden="true">
            <div class="preview-modal-panel" role="dialog" aria-modal="true" aria-labelledby="travelPlanPreviewModalLabel">
                <div class="preview-modal-header d-flex justify-content-between align-items-center">
                    <h5 class="modal-title mb-0" id="travelPlanPreviewModalLabel">Confirm Travel Plan Details</h5>
                    <button type="button" class="btn-close" id="closeTravelPlanPreviewBtn" aria-label="Close"></button>
                </div>
                <div class="preview-modal-body">
                        <p class="small text-muted mb-3">Please review your details before final submission.</p>
                        <dl class="row small mb-0 preview-list">
                            <dt class="col-sm-4">Arrival date</dt><dd class="col-sm-8 preview-highlight" data-preview="joining_date">—</dd>
                            <dt class="col-sm-4">Slot name</dt><dd class="col-sm-8 preview-highlight" data-preview="slot_name">—</dd>
                            <dt class="col-sm-4">Slot time</dt><dd class="col-sm-8 preview-highlight" data-preview="slot_time">—</dd>
                            <dt class="col-sm-4">Mode of journey</dt><dd class="col-sm-8 preview-highlight" data-preview="mode_of_journey">—</dd>
                            <dt class="col-sm-4">Flight/Train/Vehicle no.</dt><dd class="col-sm-8 preview-highlight" data-preview="journey_vehicle_no">—</dd>
                            <dt class="col-sm-4">Arrival time at Dehradun</dt><dd class="col-sm-8 preview-highlight" data-preview="arrival_time_dehradun">—</dd>
                            <dt class="col-sm-4">Require academy vehicle</dt><dd class="col-sm-8 preview-highlight" data-preview="require_academy_vehicle">—</dd>
                            <dt class="col-sm-4">Remarks</dt><dd class="col-sm-8 preview-highlight" data-preview="special_requirements">—</dd>
                        </dl>
                </div>
                    <div class="preview-modal-footer">
                        <button type="button" class="btn btn-outline-secondary" id="editTravelPlanBtn">Edit Details</button>
                        <button type="button" class="btn btn-success" id="confirmTravelPlanSubmitBtn">
                            <i class="bi bi-check2-circle me-1"></i>Confirm & Submit
                        </button>
                    </div>
                </div>
        </div>
        <script>
            (function () {
                const dateSelect = document.getElementById('joiningDateSelect');
                const slotSelect = document.getElementById('arrivalSlotSelect');
                const form = document.getElementById('travelSubmitForm');
                const previewBtn = document.getElementById('previewTravelPlanBtn');
                const previewModalEl = document.getElementById('travelPlanPreviewModal');
                const confirmSubmitBtn = document.getElementById('confirmTravelPlanSubmitBtn');
                const closePreviewBtn = document.getElementById('closeTravelPlanPreviewBtn');
                const editTravelPlanBtn = document.getElementById('editTravelPlanBtn');
                if (!dateSelect || !slotSelect || !form || !previewBtn || !previewModalEl || !confirmSubmitBtn || !closePreviewBtn || !editTravelPlanBtn) return;

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
                    if (selectedDate && !hasVisible) {
                        slotSelect.options[0].text = '-- No slot available for this date --';
                    } else {
                        slotSelect.options[0].text = '-- Select slot --';
                    }
                }

                dateSelect.addEventListener('change', filterSlotsByDate);
                filterSlotsByDate();

                function previewValue(field, value) {
                    const target = form.querySelector(`[data-preview="${field}"]`) || previewModalEl.querySelector(`[data-preview="${field}"]`);
                    if (target) {
                        target.textContent = value && String(value).trim() ? value : '—';
                    }
                }

                function selectedText(selector, options = {}) {
                    const element = form.querySelector(selector);
                    if (!element) return '';
                    if (element.tagName === 'SELECT') {
                        const option = element.options[element.selectedIndex];
                        if (!option) return '';
                        let text = option.text.replace(/\s+/g, ' ').trim();
                        if (options.stripRemainingCount) {
                            text = text.replace(/\s+—\s+\d+\s+left$/i, '').trim();
                        }
                        return text;
                    }
                    return element.value || '';
                }

                function fillPreview() {
                    const requireVehicle = form.querySelector('input[name="require_academy_vehicle"]:checked');
                    const slotText = selectedText('select[name="fc_travel_arrival_slot_id"]', { stripRemainingCount: true });
                    const slotTimeMatch = slotText.match(/\(([^)]+)\)/);
                    const slotTime = slotTimeMatch ? slotTimeMatch[1].trim() : '';
                    const slotName = slotText.replace(/\s*\([^)]+\)\s*$/, '').trim();

                    previewValue('joining_date', selectedText('select[name="joining_date"]'));
                    previewValue('slot_name', slotName);
                    previewValue('slot_time', slotTime);
                    previewValue('mode_of_journey', selectedText('select[name="mode_of_journey"]'));
                    previewValue('journey_vehicle_no', selectedText('input[name="journey_vehicle_no"]'));
                    previewValue('arrival_time_dehradun', selectedText('input[name="arrival_time_dehradun"]'));
                    previewValue('require_academy_vehicle', requireVehicle ? (requireVehicle.value === '1' ? 'Yes' : 'No') : '');
                    previewValue('special_requirements', selectedText('textarea[name="special_requirements"]'));
                }

                function openPreviewModal() {
                    previewModalEl.classList.add('is-open');
                    previewModalEl.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                }

                function closePreviewModal() {
                    previewModalEl.classList.remove('is-open');
                    previewModalEl.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                }

                previewBtn.addEventListener('click', function () {
                    if (!form.checkValidity()) {
                        form.reportValidity();
                        return;
                    }
                    fillPreview();
                    openPreviewModal();
                });

                confirmSubmitBtn.addEventListener('click', function () {
                    form.submit();
                });

                closePreviewBtn.addEventListener('click', closePreviewModal);
                editTravelPlanBtn.addEventListener('click', closePreviewModal);
                previewModalEl.addEventListener('click', function (event) {
                    if (event.target === previewModalEl) {
                        closePreviewModal();
                    }
                });
                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && previewModalEl.classList.contains('is-open')) {
                        closePreviewModal();
                    }
                });
            })();
        </script>
        @endif
        </div>
    </div>

</div>
</div>
{{-- Legacy (pre–Apr 2026) full travel form: see Git history for `resources/views/fc/registration/travel.blade.php` and `TravelPlanController@save` with multi-leg MCTP details. --}}
@endsection
