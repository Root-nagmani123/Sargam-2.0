@extends('admin.layouts.master')

@section('title', 'PT Exemption Count Configuration - Sargam | Lal Bahadur')

@section('setup_content')
<style>
    .pt-exemption-config .config-table thead th {
        background-color: #004a93;
        color: #fff;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.03em;
        border: none;
        vertical-align: middle;
    }
    .pt-exemption-config .config-table tbody td {
        vertical-align: middle;
    }
    .pt-exemption-config .days-input-group {
        max-width: 220px;
    }
    .pt-exemption-config .days-input-group .form-control {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    .pt-exemption-config .days-input-group .input-group-text {
        background: #f8f9fa;
        border-top-right-radius: 0.375rem;
        border-bottom-right-radius: 0.375rem;
        min-width: 52px;
        justify-content: center;
    }
    .pt-exemption-config .btn-save-config {
        background-color: #198754;
        border-color: #198754;
        color: #fff;
        min-width: 120px;
    }
    .pt-exemption-config .btn-save-config:hover {
        background-color: #157347;
        border-color: #146c43;
        color: #fff;
    }
    .pt-exemption-config .info-note {
        background-color: #e7f1ff;
        border: 1px solid #b6d4fe;
        color: #084298;
        border-radius: 0.375rem;
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }
</style>

<div class="container-fluid py-3 pt-exemption-config">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.pt-exemption-master.index') }}">Leave Management</a></li>
            <li class="breadcrumb-item active" aria-current="page">PT Exemption Settings</li>
        </ol>
    </nav>

    <x-session_message />

    @if ($errors->has('course_master_pk'))
        <div class="alert alert-danger">{{ $errors->first('course_master_pk') }}</div>
    @endif

    @if (!($isEditing ?? false) && $courses->isEmpty())
        <div class="alert alert-warning">
            All eligible courses already have a PT exemption configuration. Use Edit on the list page to update an existing record.
        </div>
    @endif

    <div class="card shadow-sm border-0 border-start border-4 border-primary rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
                <h2 class="h5 mb-0 fw-semibold text-body">PT Exemption Count Configuration</h2>
                <a href="{{ route('admin.pt-exemption-master.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="material-icons material-symbols-rounded align-middle" style="font-size:18px;">arrow_back</i>
                    Back to List
                </a>
            </div>

            <form method="POST" action="{{ route('admin.pt-exemption-master.store') }}" id="exemption-config-form">
                @csrf

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6 col-lg-4">
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
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="effective_from" class="form-label fw-semibold">Effective From <span class="text-danger">*</span></label>
                        <input type="date" id="effective_from" name="effective_from" class="form-control" required
                            value="{{ old('effective_from', $effectiveFrom ? \Carbon\Carbon::parse($effectiveFrom)->format('Y-m-d') : '') }}"
                            @if($isEditing ?? false) readonly @endif>
                    </div>
                </div>

                <h3 class="h6 fw-semibold mb-3">PT Exemption Count (Per Academic Year)</h3>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered config-table mb-0">
                        <thead>
                            <tr>
                                <th style="width: 35%;">Gender</th>
                                <th style="width: 65%;">PT Exemption Count (Days)</th>
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
                                        <span class="input-group-text">Days</span>
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
                                        <span class="input-group-text">Days</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    <div class="info-note flex-grow-1 mb-0">
                        <strong>Note:</strong> This count will be applicable to participants of the selected course.
                    </div>
                    <button type="submit" class="btn btn-save-config d-inline-flex align-items-center gap-1 px-4"
                        @if(!($isEditing ?? false) && $courses->isEmpty()) disabled @endif>
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;">save</i>
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
