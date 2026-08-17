@extends('admin.layouts.master')

@section('title', ($isEditing ?? false) ? 'Edit PT Exemption' : 'Configure PT Exemption')

@push('styles')
{{-- Shared with the PT Exemption listing: same module (docs/new-design-index-page.md §7). --}}
<link rel="stylesheet"
    href="{{ asset('css/pt-exemption-admin.css') }}?v={{ @filemtime(public_path('css/pt-exemption-admin.css')) }}">
@endpush

@section('setup_content')

<div class="container-fluid pt-exemption-config">
    <x-breadcrum :title="($isEditing ?? false) ? 'Edit PT Exemption' : 'Configure PT Exemption'" :showBack="true" />

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
        $selectedCourse = $courses->firstWhere('pk', (int) old('course_master_pk', $courseMasterPk));

        $cutoffValue = old(
            'apply_cutoff_time',
            filled($selectedCourse?->pt_start_time)
                ? \Carbon\Carbon::parse($selectedCourse->pt_start_time)->format('H:i')
                : ($maleRecord?->apply_cutoff_time
                    ? \Carbon\Carbon::parse($maleRecord->apply_cutoff_time)->format('H:i')
                    : '')
        );

        $ptEndTimeValue = filled($selectedCourse?->pt_end_time)
            ? \Carbon\Carbon::parse($selectedCourse->pt_end_time)->format('H:i')
            : '';
    @endphp

    <form method="POST" action="{{ route('admin.pt-exemption-master.store') }}" id="exemption-config-form">
        @csrf

        {{-- Course & schedule (§3d) --}}
        <div class="pem-card">
            <div class="pem-section">
                <h2 class="pem-section-title">Course &amp; Schedule</h2>
            </div>

            <div class="row g-4">
                    <div class="col-12 col-md-3">
                        <label for="course_master_pk" class="pem-field-label">Select Course <span class="pem-req">*</span></label>
                        <select id="course_master_pk" name="course_master_pk" class="pem-control" required
                            @if(($isEditing ?? false) || $courses->isEmpty()) disabled @endif>
                            <option value="">Select Course</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->pk }}"
                                    data-start-date="{{ filled($course->start_year) ? \Carbon\Carbon::parse($course->start_year)->format('Y-m-d') : '' }}"
                                    data-pt-start-time="{{ filled($course->pt_start_time) ? \Carbon\Carbon::parse($course->pt_start_time)->format('H:i') : '' }}"
                                    data-pt-end-time="{{ filled($course->pt_end_time) ? \Carbon\Carbon::parse($course->pt_end_time)->format('H:i') : '' }}"
                                    {{ (string) old('course_master_pk', $courseMasterPk) === (string) $course->pk ? 'selected' : '' }}>
                                    {{ $course->course_name }}
                                </option>
                            @endforeach
                        </select>
                        @if($isEditing ?? false)
                            <input type="hidden" name="course_master_pk" value="{{ old('course_master_pk', $courseMasterPk) }}">
                        @endif
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="effective_from" class="pem-field-label">Effective From <span class="pem-req">*</span></label>
                        <input type="date" id="effective_from" name="effective_from" class="pem-control" required
                            placeholder="Select the date"
                            value="{{ old('effective_from', $effectiveFrom ? \Carbon\Carbon::parse($effectiveFrom)->format('Y-m-d') : '') }}"
                            @if($isEditing ?? false) readonly @endif>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="apply_cutoff_time" class="pem-field-label">PT Start Time <span class="pem-req">*</span></label>
                        <input type="time" id="apply_cutoff_time" name="apply_cutoff_time" class="pem-control" required
                            placeholder="Select the time"
                            value="{{ $cutoffValue }}"
                            readonly>
                        <small class="text-danger d-none" id="ptStartTimeMissingMsg">
                            PT start time is not set for this course. Please add the PT time in Course Master first.
                        </small>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="pt_end_time_display" class="pem-field-label">PT End Time</label>
                        <input type="time" id="pt_end_time_display" class="pem-control"
                            value="{{ $ptEndTimeValue }}"
                            readonly>
                    </div>
                </div>
            </div>

        <div class="pem-card">
            <div class="pem-section">
                <h2 class="pem-section-title">PT Exemption Count (Per Academic Year)</h2>
            </div>

                <div class="table-responsive">
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

        </div>

        {{-- Footer: an equal, flush-right pair, same treatment as the modals (§3d). --}}
        <div class="pem-form-footer">
            <a href="{{ route('admin.pt-exemption-master.index') }}" class="btn pem-btn-cancel">Cancel</a>
            <button type="submit" class="btn pem-btn-submit"
                @if(!($isEditing ?? false) && $courses->isEmpty()) disabled @endif>
                {{ ($isEditing ?? false) ? 'Update PT Exemption' : 'Save PT Exemption' }}
            </button>
        </div>
    </form>
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

    function setPtTimesFromCourse() {
        const selected = $('#course_master_pk option:selected');
        const ptStartTime = selected.data('ptStartTime') || '';
        $('#apply_cutoff_time').val(ptStartTime);
        $('#pt_end_time_display').val(selected.data('ptEndTime') || '');
        togglePtStartTimeMissing(selected.val() && !ptStartTime);
    }

    function togglePtStartTimeMissing(isMissing) {
        $('#ptStartTimeMissingMsg').toggleClass('d-none', !isMissing);
        $('button[type="submit"]').prop('disabled', !!isMissing);
    }

    $('#course_master_pk').on('change', function () {
        setEffectiveFromCourseStart();
        setPtTimesFromCourse();
    });

    if ($('#course_master_pk').val() && !$('#effective_from').val()) {
        setEffectiveFromCourseStart();
    }

    if ($('#course_master_pk').val()) {
        togglePtStartTimeMissing(!$('#apply_cutoff_time').val());
    }
});
</script>
@endpush
