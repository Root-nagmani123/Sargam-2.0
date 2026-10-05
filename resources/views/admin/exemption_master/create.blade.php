@extends('admin.layouts.master')

@section('title', 'Configure PT Exemption')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
<style>
    /* Values the form derives from Course Master read as read-only, and the
       "In Days" unit sits flush against its number. Same rules on the PT
       Exemption and Stationed Leave forms (.lm-page). */
    .mst-page.lm-page .mst-control[readonly] {
        background: var(--ds-surface-2);
    }

    .mst-page.lm-page .lm-unit {
        background: var(--ds-surface-2);
        color: var(--ds-ink-muted);
        border-color: var(--ds-line);
        font-size: 0.875rem;
    }

    .mst-page.lm-page .lm-hint {
        display: block;
        margin-top: var(--ds-space-1);
        color: var(--ds-ink-muted);
        font-size: 0.8125rem;
    }
</style>
@endpush

@section('setup_content')
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

    $noCourses = !($isEditing ?? false) && $courses->isEmpty();
@endphp
<div class="container-fluid mst-page lm-page ptx-form-page">
    <x-breadcrum title="Configure PT Exemption" :showBack="true" />

    <x-session_message />

    @if ($noCourses)
        <div class="alert alert-warning rounded-1" role="alert">
            All eligible courses already have a PT exemption configuration. Use Edit on the list page to update an existing record.
        </div>
    @endif

    <form method="POST" action="{{ route('admin.pt-exemption-master.store') }}" id="exemption-config-form">
        @csrf

        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">Course &amp; PT Timing</h2>

                <div class="row g-3">
                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="course_master_pk" class="mst-form-label d-block">
                            Select Course <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select id="course_master_pk" name="course_master_pk"
                                class="form-select mst-control mst-searchable @error('course_master_pk') is-invalid @enderror"
                                data-placeholder="Select Course" required aria-required="true"
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
                        @error('course_master_pk')
                            <span class="mst-field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="effective_from" class="mst-form-label d-block">
                            Effective From <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="date" id="effective_from" name="effective_from"
                               class="form-control mst-control @error('effective_from') is-invalid @enderror"
                               required aria-required="true" placeholder="Select the date"
                               value="{{ old('effective_from', $effectiveFrom ? \Carbon\Carbon::parse($effectiveFrom)->format('Y-m-d') : '') }}"
                               @if($isEditing ?? false) readonly @endif>
                        @error('effective_from')
                            <span class="mst-field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="apply_cutoff_time" class="mst-form-label d-block">
                            PT Start Time <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="time" id="apply_cutoff_time" name="apply_cutoff_time"
                               class="form-control mst-control @error('apply_cutoff_time') is-invalid @enderror"
                               required aria-required="true" placeholder="Select the time"
                               aria-describedby="ptStartTimeHint"
                               value="{{ $cutoffValue }}" readonly>
                        <span class="lm-hint" id="ptStartTimeHint">Taken from the course's PT time in Course Master.</span>
                        <span class="mst-field-error d-none" id="ptStartTimeMissingMsg" role="alert">
                            PT start time is not set for this course. Please add the PT time in Course Master first.
                        </span>
                        @error('apply_cutoff_time')
                            <span class="mst-field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="pt_end_time_display" class="mst-form-label d-block">PT End Time</label>
                        <input type="time" id="pt_end_time_display" class="form-control mst-control"
                               value="{{ $ptEndTimeValue }}" readonly>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">Exemption Rules</h2>

                <div class="row g-3">
                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="freeze_before_minutes" class="mst-form-label d-block">Freeze Before (Minutes)</label>
                        <input type="number" step="1" min="0" max="1440"
                               id="freeze_before_minutes" name="freeze_before_minutes"
                               class="form-control mst-control @error('freeze_before_minutes') is-invalid @enderror"
                               aria-describedby="freezeBeforeHint"
                               value="{{ old('freeze_before_minutes', $maleRecord ? (int) $maleRecord->freeze_before_minutes : 0) }}"
                               placeholder="0">
                        <span class="lm-hint" id="freezeBeforeHint">Apply closes this many minutes before PT Start Time.</span>
                        @error('freeze_before_minutes')
                            <span class="mst-field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="max_exemption_per_month" class="mst-form-label d-block">
                            Max Exemption Per Month <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <div class="input-group">
                            <input type="number" step="0.1" min="0.1" max="999.9"
                                   id="max_exemption_per_month" name="max_exemption_per_month"
                                   class="form-control mst-control @error('max_exemption_per_month') is-invalid @enderror"
                                   required aria-required="true" aria-describedby="maxPerMonthUnit maxPerMonthHint"
                                   value="{{ old('max_exemption_per_month', $maleRecord ? number_format((float) $maleRecord->max_exemption_per_month, 1, '.', '') : '') }}"
                                   placeholder="0.0">
                            <span class="input-group-text lm-unit" id="maxPerMonthUnit">In Days</span>
                        </div>
                        <span class="lm-hint" id="maxPerMonthHint">Applies equally to Male and Female officer trainees.</span>
                        @error('max_exemption_per_month')
                            <span class="mst-field-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">PT Exemption Count (Per Academic Year)</h2>

                <div class="row g-3">
                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="male_exemption_days" class="mst-form-label d-block">
                            Male <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <div class="input-group">
                            <input type="number" step="0.1" min="0" max="999.9"
                                   id="male_exemption_days" name="male_exemption_days"
                                   class="form-control mst-control @error('male_exemption_days') is-invalid @enderror"
                                   required aria-required="true" aria-describedby="maleDaysUnit"
                                   value="{{ old('male_exemption_days', $maleRecord ? number_format((float) $maleRecord->exemption_days, 1, '.', '') : '') }}"
                                   placeholder="0.0">
                            <span class="input-group-text lm-unit" id="maleDaysUnit">In Days</span>
                        </div>
                        @error('male_exemption_days')
                            <span class="mst-field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="female_exemption_days" class="mst-form-label d-block">
                            Female <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <div class="input-group">
                            <input type="number" step="0.1" min="0" max="999.9"
                                   id="female_exemption_days" name="female_exemption_days"
                                   class="form-control mst-control @error('female_exemption_days') is-invalid @enderror"
                                   required aria-required="true" aria-describedby="femaleDaysUnit"
                                   value="{{ old('female_exemption_days', $femaleRecord ? number_format((float) $femaleRecord->exemption_days, 1, '.', '') : '') }}"
                                   placeholder="0.0">
                            <span class="input-group-text lm-unit" id="femaleDaysUnit">In Days</span>
                        </div>
                        @error('female_exemption_days')
                            <span class="mst-field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="description" class="mst-form-label d-block">Description</label>
                        <textarea id="description" name="description" rows="3" maxlength="1000"
                                  class="form-control mst-control @error('description') is-invalid @enderror"
                                  placeholder="Add any remarks or notes for this PT exemption configuration (optional)">{{ old('description', $maleRecord->description ?? '') }}</textarea>
                        @error('description')
                            <span class="mst-field-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mst-form-footer">
                    <a href="{{ route('admin.pt-exemption-master.index') }}" class="btn mst-btn-cancel px-4">Cancel</a>
                    <button type="submit" class="btn mst-btn-submit px-4" @if($noCourses) disabled @endif>
                        Save
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
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

    // jQuery binding: the course select is searchable (Select2 fires a jQuery change).
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
