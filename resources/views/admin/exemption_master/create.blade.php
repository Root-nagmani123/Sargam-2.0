@extends('admin.layouts.master')

@section('title', 'Configure PT Exemption')

@section('setup_content')
<style>
    .pt-exemption-config .config-table {
        border: 1px solid #e4e7ec;
        border-radius: 8px;
        overflow: hidden;
    }
    .pt-exemption-config .config-table thead th {
        background-color: #f2f4f7;
        color: #667085;
        font-weight: 600;
        font-size: 0.8125rem;
        border-bottom: 1px solid #e4e7ec;
        border-top: 0;
        padding: 0.75rem 1.25rem;
        vertical-align: middle;
    }
    .pt-exemption-config .config-table tbody td {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #eef2f6;
        color: #344054;
        vertical-align: middle;
    }
    .pt-exemption-config .config-table tbody tr:last-child td {
        border-bottom: 0;
    }
    .pt-exemption-config .days-input-group {
        max-width: 240px;
    }
    .pt-exemption-config .days-input-group .form-control {
        border-right: 0;
    }
    .pt-exemption-config .days-input-group .form-control:focus {
        box-shadow: none;
        border-color: #004a93;
    }
    .pt-exemption-config .days-input-group:focus-within {
        border-radius: 0.375rem;
        box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.12);
    }
    .pt-exemption-config .days-input-group .input-group-text {
        background: #f2f4f7;
        color: #667085;
        border-left: 0;
        min-width: 76px;
        justify-content: center;
        font-size: 0.875rem;
    }
    .pt-exemption-config .form-label {
        color: #344054;
    }
</style>

<div class="container-fluid pt-exemption-config">
    <x-breadcrum title="Configure PT Exemption" :showBack="true" />

    <x-session_message />

    @if ($errors->has('course_master_pk'))
        <div class="alert alert-danger">{{ $errors->first('course_master_pk') }}</div>
    @endif

    @if (!($isEditing ?? false) && $courses->isEmpty())
        <div class="alert alert-warning">
            All eligible courses already have a PT exemption configuration. Use Edit on the list page to update an existing record.
        </div>
    @endif

    @php
        $cutoffValue = old(
            'apply_cutoff_time',
            $maleRecord?->apply_cutoff_time
                ? \Carbon\Carbon::parse($maleRecord->apply_cutoff_time)->format('H:i')
                : '06:00'
        );
    @endphp

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-3 p-md-4">
            <form method="POST" action="{{ route('admin.pt-exemption-master.store') }}" id="exemption-config-form">
                @csrf

                <div class="row g-4 mb-4">
                    <div class="col-12 col-md-4">
                        <label for="course_master_pk" class="form-label fw-semibold">Select Course <span class="text-danger">*</span></label>
                        <select id="course_master_pk" name="course_master_pk" class="form-select" required
                            @if(($isEditing ?? false) || $courses->isEmpty()) disabled @endif>
                            <option value="">Select Course</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->pk }}"
                                    data-start-date="{{ filled($course->start_year) ? \Carbon\Carbon::parse($course->start_year)->format('Y-m-d') : '' }}"
                                    {{ (string) old('course_master_pk', $courseMasterPk) === (string) $course->pk ? 'selected' : '' }}>
                                    {{ $course->course_name }}
                                </option>
                            @endforeach
                        </select>
                        @if($isEditing ?? false)
                            <input type="hidden" name="course_master_pk" value="{{ old('course_master_pk', $courseMasterPk) }}">
                        @endif
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="effective_from" class="form-label fw-semibold">Effective From <span class="text-danger">*</span></label>
                        <input type="date" id="effective_from" name="effective_from" class="form-control" required
                            placeholder="Select the date"
                            value="{{ old('effective_from', $effectiveFrom ? \Carbon\Carbon::parse($effectiveFrom)->format('Y-m-d') : '') }}"
                            @if($isEditing ?? false) readonly @endif>
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="apply_cutoff_time" class="form-label fw-semibold">PT Time <span class="text-danger">*</span></label>
                        <input type="time" id="apply_cutoff_time" name="apply_cutoff_time" class="form-control" required
                            placeholder="Select the time"
                            value="{{ $cutoffValue }}">
                    </div>
                </div>

                <h3 class="h6 fw-semibold mb-2">PT Exemption Count (Per Academic Year)</h3>
                <hr class="mt-0 mb-3">

                <div class="table-responsive mb-4">
                    <table class="table config-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50%;">Gender</th>
                                <th style="width: 50%;">PT Exemption</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-medium">Male</td>
                                <td>
                                    <div class="input-group days-input-group">
                                        <input type="number" step="0.1" min="0" max="999.9"
                                            id="male_exemption_days" name="male_exemption_days" class="form-control" required
                                            value="{{ old('male_exemption_days', $maleRecord ? number_format((float) $maleRecord->exemption_days, 1, '.', '') : '') }}"
                                            placeholder="0.0">
                                        <span class="input-group-text">In Days</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-medium">Female</td>
                                <td>
                                    <div class="input-group days-input-group">
                                        <input type="number" step="0.1" min="0" max="999.9"
                                            id="female_exemption_days" name="female_exemption_days" class="form-control" required
                                            value="{{ old('female_exemption_days', $femaleRecord ? number_format((float) $femaleRecord->exemption_days, 1, '.', '') : '') }}"
                                            placeholder="0.0">
                                        <span class="input-group-text">In Days</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap justify-content-end gap-2">
                    <a href="{{ route('admin.pt-exemption-master.index') }}" class="btn btn-outline-primary px-4 rounded-1 fw-semibold">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary px-4 rounded-1 fw-semibold"
                        @if(!($isEditing ?? false) && $courses->isEmpty()) disabled @endif>
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    function setEffectiveFromCourseStart() {
        const startDate = $('#course_master_pk option:selected').data('startDate');
        if (startDate) {
            $('#effective_from').val(startDate);
        }
    }

    $('#course_master_pk').on('change', setEffectiveFromCourseStart);

    if ($('#course_master_pk').val() && !$('#effective_from').val()) {
        setEffectiveFromCourseStart();
    }
});
</script>
@endpush
