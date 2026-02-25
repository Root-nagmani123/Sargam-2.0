{{-- Partial: Change Request Details form (used in full page and in modal) --}}
@php
    $inModal = $inModal ?? false;
    $formId = $inModal ? 'formChangeRequestDetailsModal' : 'formChangeRequestDetails';
@endphp
<form method="POST" action="{{ $formAction ?? '#' }}" id="{{ $formId }}" class="needs-validation" novalidate>
    @csrf

    {{-- Personal and Employment Details (read-only) --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label fw-semibold">Request ID <span class="text-danger">*</span></label>
            <input type="text" class="form-control bg-warning bg-opacity-10 border-secondary border-opacity-25" value="{{ optional($detail)->request_id ?? 'Chg-Req-'.rand(1000, 9999) }}" readonly>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label fw-semibold">Request Date <span class="text-danger">*</span></label>
            <input type="text" class="form-control bg-warning bg-opacity-10 border-secondary border-opacity-25" value="{{ optional($detail)->request_date ?? date('d-m-Y') }}" readonly>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control bg-warning bg-opacity-10 border-secondary border-opacity-25" value="{{ optional($detail)->name ?? '' }}" readonly>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label fw-semibold">Emp. ID <span class="text-danger">*</span></label>
            <input type="text" class="form-control bg-warning bg-opacity-10 border-secondary border-opacity-25" value="{{ optional($detail)->emp_id ?? '' }}" readonly>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label fw-semibold">Designation <span class="text-danger">*</span></label>
            <input type="text" class="form-control bg-warning bg-opacity-10 border-secondary border-opacity-25" value="{{ optional($detail)->designation ?? '' }}" readonly>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label fw-semibold">Current Pay scale <span class="text-danger">*</span></label>
            <input type="text" class="form-control bg-warning bg-opacity-10 border-secondary border-opacity-25" value="{{ optional($detail)->pay_scale ?? '' }}" readonly>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label fw-semibold">Date of Joining in Current Pay Scale <span class="text-danger">*</span></label>
            <input type="text" class="form-control bg-warning bg-opacity-10 border-secondary border-opacity-25" value="{{ optional($detail)->doj_pay_scale ?? '' }}" readonly>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label fw-semibold">Date of joining in Academy <span class="text-danger">*</span></label>
            <input type="text" class="form-control bg-warning bg-opacity-10 border-secondary border-opacity-25" value="{{ optional($detail)->doj_academy ?? '' }}" readonly>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label fw-semibold">Date of joining in Service <span class="text-danger">*</span></label>
            <input type="text" class="form-control bg-warning bg-opacity-10 border-secondary border-opacity-25" value="{{ optional($detail)->doj_service ?? '' }}" readonly>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label fw-semibold">Current Allotment <span class="text-danger">*</span></label>
            <input type="text" class="form-control bg-warning bg-opacity-10 border-secondary border-opacity-25" value="{{ optional($detail)->current_allotment ?? '' }}" readonly>
        </div>
    </div>

    {{-- Change Unit Sub Type for House Details --}}
    <div class="mb-4">
        <h2 class="h6 fw-bold text-body mb-1">Change Unit Sub Type for House Details <span class="text-danger">*</span></h2>
        <p class="text-body-secondary small mb-3">Change Unit Sub Type for House Details</p>
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-primary">
                    <tr>
                        <th class="text-white fw-semibold text-uppercase small">Estate Name</th>
                        <th class="text-white fw-semibold text-uppercase small">Unit Type</th>
                        <th class="text-white fw-semibold text-uppercase small">Building Name</th>
                        <th class="text-white fw-semibold text-uppercase small">Unit Sub Type</th>
                        <th class="text-white fw-semibold text-uppercase small">House No.s</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select name="estate_name" class="form-select form-select-sm" required>
                                <option value="">— Select —</option>
                                <option value="indira" selected>Indira Bhavan Campus</option>
                            </select>
                        </td>
                        <td>
                            <select name="unit_type" class="form-select form-select-sm" required>
                                <option value="">— Select —</option>
                                <option value="residential" selected>Residential</option>
                            </select>
                        </td>
                        <td>
                            <select name="building_name" class="form-select form-select-sm" required>
                                <option value="">— Select —</option>
                                <option value="kalpatru" selected>Kalpatru Avas</option>
                            </select>
                        </td>
                        <td>
                            <select name="unit_sub_type" class="form-select form-select-sm" required>
                                <option value="">— Select —</option>
                                <option value="type-ii" selected>Type - II</option>
                            </select>
                        </td>
                        <td>
                            <select name="house_no" class="form-select form-select-sm" required>
                                <option value="">— Select —</option>
                                <option value="kt-09" selected>KT-09</option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Remarks --}}
    <div class="mb-4">
        <label for="remarks_{{ $formId }}" class="form-label fw-semibold">Remarks</label>
        <textarea class="form-control" id="remarks_{{ $formId }}" name="remarks" rows="4" placeholder="Enter remarks..."></textarea>
    </div>

    {{-- Action buttons --}}
    <div class="d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-success px-4">
            <i class="bi bi-save me-2"></i>Save
        </button>
        @if($inModal)
            <button type="button" class="btn btn-danger px-4 btn-close-change-modal" data-bs-dismiss="modal" aria-label="Close">
                <i class="bi bi-x-lg me-2"></i>Cancel
            </button>
        @else
            <a href="{{ url()->previous() }}" class="btn btn-danger px-4">
                <i class="bi bi-x-lg me-2"></i>Cancel
            </a>
        @endif
    </div>
</form>
