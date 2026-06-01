@php
    /** @var \App\Models\ClassSessionMaster|null $classSessionMaster */
    $isEdit = isset($classSessionMaster) && $classSessionMaster;
    $formatTime = function ($value) {
        if ($value === null || $value === '') {
            return '';
        }
        $value = (string) $value;
        return strlen($value) >= 5 ? substr($value, 0, 5) : $value;
    };
@endphp

<form action="{{ route('master.class.session.store') }}" method="POST" id="classSessionForm" class="csm-modal-form" novalidate>
    @csrf
    @if ($isEdit)
        <input type="hidden" name="id" value="{{ encrypt($classSessionMaster->pk) }}">
    @endif

    <div class="mb-3">
        <label for="shift_name" class="form-label cgt-field-label mb-2">
            Session Name <span class="text-danger">*</span>
        </label>
        <input type="text"
            name="shift_name"
            id="shift_name"
            class="form-control rounded-2"
            placeholder="eg. Session 01"
            value="{{ old('shift_name', $classSessionMaster->shift_name ?? '') }}"
            maxlength="255"
            autocomplete="off"
            required>
        <small class="text-danger d-none csm-field-error" data-field="shift_name"></small>
    </div>

    <div class="mb-3">
        <label for="start_time" class="form-label cgt-field-label mb-2">
            Start Time <span class="text-danger">*</span>
        </label>
        <div class="csm-time-input-wrap position-relative">
            <input type="time"
                name="start_time"
                id="start_time"
                class="form-control rounded-2 csm-time-input"
                value="{{ old('start_time', $formatTime($classSessionMaster->start_time ?? '')) }}"
                required>
            <i class="bi bi-clock csm-time-input-icon" aria-hidden="true"></i>
        </div>
        <small class="text-danger d-none csm-field-error" data-field="start_time"></small>
    </div>

    <div class="mb-4">
        <label for="end_time" class="form-label cgt-field-label mb-2">
            End Time <span class="text-danger">*</span>
        </label>
        <div class="csm-time-input-wrap position-relative">
            <input type="time"
                name="end_time"
                id="end_time"
                class="form-control rounded-2 csm-time-input"
                value="{{ old('end_time', $formatTime($classSessionMaster->end_time ?? '')) }}"
                required>
            <i class="bi bi-clock csm-time-input-icon" aria-hidden="true"></i>
        </div>
        <small class="text-danger d-none csm-field-error" data-field="end_time"></small>
    </div>

    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
        <button type="button" class="btn btn-outline-primary rounded-2 px-4" data-bs-dismiss="modal">
            Cancel
        </button>
        <button type="submit" class="btn btn-primary rounded-2 px-4" id="saveClassSessionForm">
            {{ $isEdit ? 'Update Session' : 'Add Session' }}
        </button>
    </div>
</form>
