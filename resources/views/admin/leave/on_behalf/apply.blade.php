@extends('admin.layouts.master')

@section('title', 'Apply Leave on Behalf of OT')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
@endpush

@section('setup_content')

@include('admin.leave.partials.styles')

<style>
    /* Select2 renders its own control next to a hidden <select>; these keep the
       three searchable dropdowns the same height as the plain .form-control
       fields around them. select2-theme.css supplies the rest of the styling. */
    .leave-on-behalf .lob-field {
        position: relative; /* anchors the Select2 dropdown panel to the field */
    }
    .leave-on-behalf .lob-field .select2-container {
        width: 100% !important;
        height: 44px !important;
    }
    .leave-on-behalf .lob-field select.select2-hidden-accessible {
        min-height: 0 !important;
        height: 1px !important;
    }
    .leave-on-behalf .form-control,
    .leave-on-behalf .form-select {
        border-color: #d0d5dd;
        border-radius: 8px;
        min-height: 44px;
    }
    .leave-on-behalf textarea.form-control {
        min-height: 88px;
    }
    .leave-on-behalf .form-control[readonly] {
        background: #f2f4f7;
        color: #667085;
    }
    .leave-on-behalf .btn-apply:disabled {
        opacity: .55;
        cursor: not-allowed;
    }
</style>

@php
    $selectedCourse = old('course_master_pk', '');
    $selectedStudent = old('student_master_pk', '');
    $fromDateValue = old('from_date', '');
    $toDateValue = old('to_date', '');
@endphp

<div class="container-fluid leave-module leave-on-behalf">
    <x-breadcrum title="Apply Leave on Behalf of OT" />
    <x-session_message />

    <div class="alert alert-info py-2 small mb-3">
        Use this page only after the <strong>Course Coordinator has approved</strong> the leave.
        Leave recorded here is saved as <strong>Approved</strong> and does not go to the faculty
        approval queue again. It appears immediately in the officer trainee's My Leave.
    </div>

    <div class="row g-4 leave-apply-layout">
        <div class="col-12" id="lob-form-col">
            <div class="card leave-apply-card border-0 shadow-sm rounded-3">
                <div class="card-body p-3 p-md-4">
                    <form method="POST" action="{{ route('admin.leave-on-behalf.store') }}"
                        enctype="multipart/form-data" id="leave-on-behalf-form">
                        @csrf

                        <div class="row g-4">

                            {{-- Course --}}
                            <div class="col-12 col-md-6 lob-field">
                                <label for="lob_course" class="leave-grid-label d-block">Course Name <span class="text-danger">*</span></label>
                                <select name="course_master_pk" id="lob_course"
                                    class="form-select @error('course_master_pk') is-invalid @enderror" required>
                                    <option value="">Select Course</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->pk }}"
                                            {{ (string) $selectedCourse === (string) $course->pk ? 'selected' : '' }}>
                                            {{ $course->course_name }}{{ $course->couse_short_name ? ' (' . $course->couse_short_name . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('course_master_pk')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Officer Trainee --}}
                            <div class="col-12 col-md-6 lob-field">
                                <label for="lob_student" class="leave-grid-label d-block">Officer Trainee <span class="text-danger">*</span></label>
                                <select name="student_master_pk" id="lob_student"
                                    class="form-select @error('student_master_pk') is-invalid @enderror" required
                                    data-preselect="{{ $selectedStudent }}">
                                    <option value="">Select Course First</option>
                                </select>
                                @error('student_master_pk')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Search by officer trainee name or OT code.</div>
                            </div>

                            {{-- Leave Type — this page records one type only, so it
                                 is shown as a fixed value rather than a picker. --}}
                            <div class="col-12 col-md-6">
                                <label for="lob_leave_type_display" class="leave-grid-label d-block">Leave Type</label>
                                <input type="text" id="lob_leave_type_display" class="form-control" readonly value="Leave">
                            </div>

                            {{-- Nature — the LEAVE bucket of the Nature Leave Master --}}
                            <div class="col-12 col-md-6 lob-field">
                                <label for="lob_nature" class="leave-grid-label d-block">Nature of Leave <span class="text-danger">*</span></label>
                                <select name="leave_nature_master_pk" id="lob_nature"
                                    class="form-select @error('leave_nature_master_pk') is-invalid @enderror" required>
                                    <option value="">Select Nature</option>
                                    @foreach($natures as $nature)
                                        <option value="{{ $nature->pk }}"
                                            {{ (string) old('leave_nature_master_pk') === (string) $nature->pk ? 'selected' : '' }}>
                                            {{ $nature->nature_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('leave_nature_master_pk')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                @if($natures->isEmpty())
                                    <div class="text-danger small mt-1">
                                        No natures are configured under <strong>Leave</strong> yet.
                                        Add them in Nature Leave Master first.
                                    </div>
                                @endif
                            </div>

                            {{-- Availability notice for the selected course / leave type --}}
                            <div class="col-12 d-none" id="lob-availability">
                                <div class="alert alert-warning py-2 small mb-0" id="lob-availability-text"></div>
                            </div>

                            {{-- Date From --}}
                            <div class="col-12 col-md-6">
                                <label for="from_date" class="leave-grid-label d-block">Date From <span class="text-danger">*</span></label>
                                <div class="leave-date-wrap">
                                    <input type="date" name="from_date" id="from_date"
                                        class="form-control @error('from_date') is-invalid @enderror" required
                                        value="{{ $fromDateValue }}">
                                    <i class="material-icons material-symbols-rounded leave-date-icon">calendar_month</i>
                                </div>
                                @error('from_date')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Date To --}}
                            <div class="col-12 col-md-6">
                                <label for="to_date" class="leave-grid-label d-block">Date To <span class="text-danger">*</span></label>
                                <div class="leave-date-wrap">
                                    <input type="date" name="to_date" id="to_date"
                                        class="form-control @error('to_date') is-invalid @enderror" required
                                        value="{{ $toDateValue }}">
                                    <i class="material-icons material-symbols-rounded leave-date-icon">calendar_month</i>
                                </div>
                                @error('to_date')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Set a later end date to apply for more than one day.</div>
                            </div>

                            {{-- Time From / Time To — when the officer trainee leaves the
                                 station and when they report back. --}}
                            <div class="col-12 col-md-6">
                                <label for="time_from" class="leave-grid-label d-block">Time From <span class="text-danger">*</span></label>
                                <input type="time" name="time_from" id="time_from"
                                    class="form-control @error('time_from') is-invalid @enderror" required
                                    value="{{ old('time_from', '') }}">
                                @error('time_from')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Time the officer trainee leaves the station.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="time_to" class="leave-grid-label d-block">Time To <span class="text-danger">*</span></label>
                                <input type="time" name="time_to" id="time_to"
                                    class="form-control @error('time_to') is-invalid @enderror" required
                                    value="{{ old('time_to', '') }}">
                                @error('time_to')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Time the officer trainee reports back.</div>
                            </div>

                            {{-- Total Days --}}
                            <div class="col-12 col-md-6">
                                <label for="total_days_display" class="leave-grid-label d-block">Total Days <span class="text-danger">*</span></label>
                                <input type="text" id="total_days_display" class="form-control" readonly value="0">
                            </div>

                            {{-- Reason --}}
                            <div class="col-12 col-md-6">
                                <label for="reason" class="leave-grid-label d-block">Reason <span class="text-danger">*</span></label>
                                <textarea name="reason" id="reason" rows="3"
                                    class="form-control @error('reason') is-invalid @enderror" required
                                    placeholder="eg. Enter reason for leave here...">{{ old('reason', '') }}</textarea>
                                @error('reason')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Contact Number --}}
                            <div class="col-12 col-md-6">
                                <label for="contact_number" class="leave-grid-label d-block">Contact Number During Exemption <span class="text-danger">*</span></label>
                                <input type="text" name="contact_number" id="contact_number"
                                    class="form-control @error('contact_number') is-invalid @enderror"
                                    maxlength="10" inputmode="numeric" pattern="[6-9][0-9]{9}" required
                                    placeholder="eg. 9876543210"
                                    value="{{ old('contact_number', '') }}">
                                @error('contact_number')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Attachments --}}
                            <div class="col-12">
                                <label class="leave-grid-label d-block">Attachments</label>

                                <div id="leave-attachments-wrap">
                                    <div class="leave-attachment-row row g-2 align-items-center mb-2" data-index="0">
                                        <div class="col-12 col-md-5">
                                            <input type="text" name="attachments[0][title]"
                                                class="form-control leave-attachment-title"
                                                placeholder="Attachment name (eg. Coordinator approval)">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <input type="file" name="attachments[0][file]"
                                                class="form-control leave-attachment-file"
                                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                        </div>
                                        <div class="col-12 col-md-1 d-flex">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger leave-attachment-remove d-none"
                                                title="Remove attachment">
                                                <i class="material-icons material-symbols-rounded" style="font-size:18px;">close</i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-1" id="leave-attachment-add">
                                    <i class="material-icons material-symbols-rounded" style="font-size:16px;vertical-align:middle;">add</i>
                                    Add attachment
                                </button>
                                <div class="form-text">
                                    PDF, JPG, JPEG, PNG, DOC, DOCX &middot; max 5 MB each.
                                </div>

                                @php
                                    $attachmentErrors = collect($errors->messages())->filter(
                                        fn ($_, $key) => str_starts_with($key, 'attachments.')
                                    )->flatten();
                                @endphp
                                @if($attachmentErrors->isNotEmpty())
                                    <div class="alert alert-danger py-2 small mt-2 mb-0">
                                        @foreach($attachmentErrors as $error)
                                            <div>{{ $error }}</div>
                                        @endforeach
                                    </div>
                                @endif
                                <div id="attachment-validation-error" class="alert alert-danger py-2 small d-none mt-2 mb-0"></div>
                            </div>
                        </div>

                        <div class="leave-actions-end">
                            <a href="{{ route('admin.leave-on-behalf.index') }}" class="btn btn-cancel-outline">Cancel</a>
                            <button type="submit" class="btn btn-apply" id="lob-submit">Apply Leave</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- select2.full.min.js is already loaded globally in the admin footer. --}}
<script>
$(function () {
    const studentsUrl = '{{ route('admin.leave-on-behalf.students') }}';
    const contextUrl = '{{ route('admin.leave-on-behalf.context') }}';
    const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    const maxSizeBytes = 5 * 1024 * 1024;

    // Latest /context payload for the selected course + officer trainee. Null until
    // both are chosen; the date limits and the availability notice read from it.
    let leaveContext = null;

    const $course = $('#lob_course');
    const $student = $('#lob_student');
    const $nature = $('#lob_nature');

    /* ── Select2: course and officer trainee both get the search box ── */
    $course.select2({ width: '100%', dropdownParent: $course.closest('.lob-field'), placeholder: 'Select Course' });
    $student.select2({ width: '100%', dropdownParent: $student.closest('.lob-field'), placeholder: 'Select Course First' });
    $nature.select2({ width: '100%', dropdownParent: $nature.closest('.lob-field'), placeholder: 'Select Nature' });

    /* ── Officer trainee options follow the course ── */
    function setStudents(list, placeholder, preselect) {
        const $opts = $('<div>').append($('<option>').val('').text(placeholder));
        (list || []).forEach(function (s) {
            const label = s.ot_code ? (s.name + ' (' + s.ot_code + ')') : s.name;
            $opts.append($('<option>').val(String(s.pk)).text(label));
        });
        $student.html($opts.html());
        const match = preselect && (list || []).some(s => String(s.pk) === String(preselect));
        $student.val(match ? String(preselect) : '').trigger('change.select2');
        if (match) {
            loadContext();
        }
    }

    function loadStudents(preselect) {
        const coursePk = $course.val();
        leaveContext = null;
        applyContext();

        if (!coursePk) {
            setStudents([], 'Select Course First', null);
            return;
        }

        setStudents([], 'Loading…', null);
        $.get(studentsUrl, { course_master_pk: coursePk })
            .done(function (res) { setStudents(res.students, 'Select Officer Trainee', preselect); })
            .fail(function () { setStudents([], 'Could not load officer trainees', null); });
    }

    function loadContext() {
        const coursePk = $course.val();
        const studentPk = $student.val();

        if (!coursePk || !studentPk) {
            leaveContext = null;
            applyContext();
            return;
        }

        $.get(contextUrl, { course_master_pk: coursePk, student_master_pk: studentPk })
            .done(function (res) { leaveContext = res; applyContext(); })
            .fail(function () { leaveContext = null; applyContext(); });
    }

    /* ── Push the loaded context into the date limits and the notice ── */
    function applyContext() {
        const block = leaveContext ? leaveContext.leave : null;

        // Availability notice + submit gating.
        const blocked = !!(block && !block.configured);
        $('#lob-availability').toggleClass('d-none', !blocked);
        if (blocked) {
            $('#lob-availability-text').text(block.message || 'Leave is not configured for the selected course.');
        }
        $('#lob-submit').prop('disabled', blocked);

        // Earliest date the configuration covers. Back-entry is allowed down to that
        // date, so no "today" floor here — unlike the officer trainee's own page.
        const minDate = block && block.configured ? block.min_date : null;
        if (minDate) {
            $('#from_date').attr('min', minDate);
        } else {
            $('#from_date').removeAttr('min');
        }
        syncEndDateMin();
        updateTotalDays();
    }

    $course.on('change', function () { loadStudents(null); });
    $student.on('change', loadContext);

    /* ── Dates ── */
    function syncEndDateMin() {
        const from = $('#from_date').val();
        const $to = $('#to_date');
        if (!from) {
            $to.removeAttr('min');
            return;
        }
        $to.attr('min', from);
        const to = $to.val();
        if (!to || to < from) {
            $to.val(from);
        }
    }

    function updateTotalDays() {
        const from = $('#from_date').val();
        const to = $('#to_date').val();
        if (!from || !to) {
            $('#total_days_display').val('0');
            return;
        }
        const start = new Date(from + 'T00:00:00');
        const end = new Date(to + 'T00:00:00');
        if (end < start) {
            $('#total_days_display').val('0');
            return;
        }
        $('#total_days_display').val(Math.floor((end - start) / 86400000) + 1);
    }

    $('#from_date').on('change', function () { syncEndDateMin(); updateTotalDays(); });
    $('#to_date').on('change', updateTotalDays);

    /* ── Attachments ── */
    function validateAttachmentFile(file) {
        if (!file) {
            return null;
        }
        const ext = (file.name.split('.').pop() || '').toLowerCase();
        if (!allowedExtensions.includes(ext)) {
            return 'File "' + file.name + '" is not allowed. Use PDF, JPG, JPEG, PNG, DOC, or DOCX.';
        }
        if (file.size > maxSizeBytes) {
            return 'File "' + file.name + '" exceeds the 5 MB size limit.';
        }
        return null;
    }

    function showAttachmentError(message) {
        $('#attachment-validation-error').text(message).removeClass('d-none');
    }

    function clearAttachmentError() {
        $('#attachment-validation-error').addClass('d-none').text('');
        $('.leave-attachment-file').removeClass('is-invalid');
    }

    $(document).on('change', '.leave-attachment-file', function () {
        clearAttachmentError();
        const error = validateAttachmentFile(this.files && this.files[0]);
        if (error) {
            $(this).addClass('is-invalid').val('');
            showAttachmentError(error);
        }
    });

    let attachmentIndex = $('#leave-attachments-wrap .leave-attachment-row').length;

    function refreshRemoveButtons() {
        const $rows = $('#leave-attachments-wrap .leave-attachment-row');
        $rows.find('.leave-attachment-remove').toggleClass('d-none', $rows.length <= 1);
    }

    $('#leave-attachment-add').on('click', function () {
        const index = attachmentIndex++;
        $('#leave-attachments-wrap').append(
            '<div class="leave-attachment-row row g-2 align-items-center mb-2" data-index="' + index + '">' +
                '<div class="col-12 col-md-5">' +
                    '<input type="text" name="attachments[' + index + '][title]" ' +
                        'class="form-control leave-attachment-title" placeholder="Attachment name">' +
                '</div>' +
                '<div class="col-12 col-md-6">' +
                    '<input type="file" name="attachments[' + index + '][file]" ' +
                        'class="form-control leave-attachment-file" ' +
                        'accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">' +
                '</div>' +
                '<div class="col-12 col-md-1 d-flex">' +
                    '<button type="button" class="btn btn-sm btn-outline-danger leave-attachment-remove" title="Remove attachment">' +
                        '<i class="material-icons material-symbols-rounded" style="font-size:18px;">close</i>' +
                    '</button>' +
                '</div>' +
            '</div>'
        );
        refreshRemoveButtons();
    });

    $(document).on('click', '.leave-attachment-remove', function () {
        $(this).closest('.leave-attachment-row').remove();
        refreshRemoveButtons();
    });

    $('#leave-on-behalf-form').on('submit', function (e) {
        clearAttachmentError();
        let firstError = null;
        $('.leave-attachment-file').each(function () {
            const error = validateAttachmentFile(this.files && this.files[0]);
            if (error) {
                $(this).addClass('is-invalid');
                firstError = firstError || error;
            }
        });
        if (firstError) {
            showAttachmentError(firstError);
            e.preventDefault();
        }
    });

    /* ── Initial state (also restores selections after a validation error) ── */
    if ($course.val()) {
        loadStudents($student.data('preselect'));
    }
    syncEndDateMin();
    updateTotalDays();
});
</script>
@endpush
