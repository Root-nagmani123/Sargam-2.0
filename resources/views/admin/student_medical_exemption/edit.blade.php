@extends('admin.layouts.master')

@section('title', 'Edit Student Medical Exemption - Sargam | Lal Bahadur')

@section('setup_content')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}?v={{ filemtime(public_path('css/select2-theme.css')) }}">
<style>
.sme-section-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--ds-ink);
    margin: 0 0 var(--ds-space-3);
    padding-bottom: var(--ds-space-2);
    border-bottom: 1px solid var(--ds-line);
}
.sme-form .form-label {
    font-weight: 500;
    font-size: 0.875rem;
    color: var(--ds-ink);
    margin-bottom: var(--ds-space-1);
    line-height: 1.35;
    display: block;
}
.sme-form .form-control,
.sme-form .form-select {
    min-height: 44px;
    border-radius: var(--ds-radius-2);
    width: 100%;
}
.sme-form .select2-container {
    width: 100% !important;
    height: 44px !important;
}
.sme-form select.select2-hidden-accessible {
    min-height: 0 !important;
    height: 1px !important;
}
.sme-form .sme-field {
    display: flex;
    flex-direction: column;
    position: relative;
}
.sme-form .row > [class*="col-"] {
    position: relative;
}
.sme-form input[readonly].sme-days { background: var(--bs-secondary-bg, #eef1f4); color: var(--ds-ink); }
.sme-form .select2-container--default.sme-frozen-select2 .select2-selection--single {
    background: var(--bs-secondary-bg, #eef1f4);
    cursor: not-allowed;
}
.sme-form textarea.form-control {
    min-height: 88px;
    resize: vertical;
    line-height: 1.5;
}
.sme-remarks-row {
    margin-top: var(--ds-space-1);
    padding-top: var(--ds-space-3);
    border-top: 1px dashed var(--ds-line);
}
.sme-form-footer {
    margin-top: var(--ds-space-4);
    padding-top: var(--ds-space-3);
    border-top: 1px solid var(--ds-line);
}
/* Advisory time-conflict banner — a brief highlight pulse so it isn't missed. */
.sme-conflict-pulse {
    animation: smeConflictPulse 1s ease-in-out 2;
}
@keyframes smeConflictPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.6); }
    50% { box-shadow: 0 0 0 6px rgba(255, 193, 7, 0); }
}
</style>

@php
    $arrDate = $record->from_date ? \Carbon\Carbon::parse($record->from_date)->format('Y-m-d') : '';
    $arrTime = $record->from_date ? \Carbon\Carbon::parse($record->from_date)->format('H:i') : '';
    $depDate = $record->to_date ? \Carbon\Carbon::parse($record->to_date)->format('Y-m-d') : '';
    $depTime = $record->to_date ? \Carbon\Carbon::parse($record->to_date)->format('H:i') : '';
    // Driven by the Medical Case Master (falls back to the legacy list if none active).
    $opdOptions = (isset($opdOptions) && count($opdOptions)) ? $opdOptions : ['IPD', 'OPD', 'After OPD', 'Referral'];
    if ($record->opd_category && !in_array($record->opd_category, $opdOptions, true)) {
        array_unshift($opdOptions, $record->opd_category);
    }
@endphp

<div class="container-fluid">
    <x-breadcrum title="Edit Student Medical Exemption" />
    <x-session_message />

    <div class="ds-card">
        <div class="ds-card-body">
            <form method="POST" action="{{ route('student.medical.exemption.update', encrypt($record->pk)) }}"
                  enctype="multipart/form-data" class="sme-form">
                @csrf

                {{-- ============ Basic Information ============ --}}
                <h6 class="sme-section-title">Basic Information</h6>
                <hr class="my-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Course Name <span class="text-danger">*</span></label>
                        <select name="course_master_pk" id="courseDropdown" class="form-select" required>
                            <option value="">Select Course Name</option>
                            @foreach($courses as $course)
                            <option value="{{ $course->pk }}"
                                data-pt-start-time="{{ filled($course->pt_start_time) ? \Carbon\Carbon::parse($course->pt_start_time)->format('H:i') : '' }}"
                                data-pt-end-time="{{ filled($course->pt_end_time) ? \Carbon\Carbon::parse($course->pt_end_time)->format('H:i') : '' }}"
                                {{ $record->course_master_pk == $course->pk ? 'selected' : '' }}>
                                {{ $course->couse_short_name ?: $course->course_name }}
                            </option>
                            @endforeach
                        </select>
                        @error('course_master_pk')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Name of Officer Trainee <span class="text-danger">*</span></label>
                        <select name="student_master_pk" id="studentDropdown" class="form-select" required>
                            <option value="">Select Officer Trainee</option>
                            @foreach($students as $s)
                            <option value="{{ $s->pk }}" data-ot_code="{{ $s->generated_OT_code }}"
                                {{ $record->student_master_pk == $s->pk ? 'selected' : '' }}>
                                {{ $s->display_name }}{{ $s->generated_OT_code ? ' ('.$s->generated_OT_code.')' : '' }}
                            </option>
                            @endforeach
                        </select>
                        @error('student_master_pk')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Treating Doctor Name <span class="text-danger">*</span></label>
                        <select name="employee_master_pk" class="form-select" required>
                            <option value="">Select Doctor</option>
                            @foreach($doctors as $doctor)
                            <option value="{{ $doctor->pk }}" {{ $record->employee_master_pk == $doctor->pk ? 'selected' : '' }}>
                                {{ trim($doctor->first_name . ' ' . $doctor->last_name) }}
                            </option>
                            @endforeach
                        </select>
                        @error('employee_master_pk')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Exemption Category <span class="text-danger">*</span></label>
                        <select name="exemption_category_master_pk" id="smeExemptionCategory" class="form-select" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->pk }}" {{ $record->exemption_category_master_pk == $cat->pk ? 'selected' : '' }}>
                                {{ $cat->exemp_category_name }}
                            </option>
                            @endforeach
                        </select>
                        @error('exemption_category_master_pk')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                </div>

                {{-- ============ Exemption and Other Information ============ --}}
                <h6 class="sme-section-title mt-4">Exemption and Other Information</h6>
                <hr class="my-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="sme-field">
                            <label class="form-label">Medical Case <span class="text-danger">*</span></label>
                            <select name="opd_category" id="smeMedicalCase" class="form-select" required>
                                <option value="">Select Category</option>
                                @foreach($opdOptions as $opt)
                                <option value="{{ $opt }}" {{ $record->opd_category == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                            @error('opd_category')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="sme-field">
                            <label class="form-label">Medical Speciality <span class="text-danger">*</span></label>
                            <select name="exemption_medical_speciality_pk" class="form-select" required>
                                <option value="">Select Speciality</option>
                                @foreach($specialities as $spec)
                                <option value="{{ $spec->pk }}" {{ $record->exemption_medical_speciality_pk == $spec->pk ? 'selected' : '' }}>
                                    {{ $spec->speciality_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('exemption_medical_speciality_pk')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="sme-field">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="arrival_date" id="arrivalDate" class="form-control" required value="{{ $arrDate }}">
                            @error('arrival_date')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="sme-field">
                            <label class="form-label">Start Time</label>
                            <input type="time" name="arrival_time" id="arrivalTime" class="form-control" value="{{ $arrTime }}">
                            @error('arrival_time')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="sme-field">
                            <label class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="departure_date" id="departureDate" class="form-control" required value="{{ $depDate }}">
                            @error('departure_date')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="sme-field">
                            <label class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="time" name="departure_time" id="departureTime" class="form-control" required value="{{ $depTime }}">
                            @error('departure_time')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="sme-field">
                            <label class="form-label">Days</label>
                            <input type="number" name="days" id="daysField" class="form-control sme-days" placeholder="eg. 6" readonly value="{{ $record->days }}">
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="sme-field">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="active_inactive" class="form-select" required>
                                <option value="1" {{ $record->active_inactive == 1 ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ $record->active_inactive == 0 ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('active_inactive')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="row g-3 sme-remarks-row">
                            <div class="col-md-6">
                                <label class="form-label">Diagnosis / Remarks</label>
                                <textarea name="Description" class="form-control" rows="3" placeholder="eg. Enter remarks...">{{ $record->Description }}</textarea>
                                @error('Description')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">PT/Outdoor Advise</label>
                                <textarea name="pt_outdoor_advise" class="form-control" rows="3" placeholder="eg. Yoga">{{ $record->pt_outdoor_advise }}</textarea>
                                @error('pt_outdoor_advise')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="col-12 sme-remarks-row" id="smePtCommentSection" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">Doctor's Comments</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="smeAddCommentBtn">+ Add Comment</button>
                        </div>
                        <div id="smePtCommentsList" data-existing="{{ $record->comments->map(fn($c) => ['pk' => $c->pk, 'comment' => $c->comment, 'comment_date' => \Carbon\Carbon::parse($c->comment_date)->format('Y-m-d')])->toJson() }}"></div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Attachment</label>
                        <input type="file" name="Doc_upload" id="Doc_upload" class="form-control"
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        @if($record->Doc_upload)
                        <a href="{{ asset('storage/' . $record->Doc_upload) }}" target="_blank" class="d-inline-block mt-1 small">View existing file</a>
                        @endif
                        <div id="fileError" class="text-danger small mt-1" style="display:none;"></div>
                        @error('Doc_upload')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                </div>

                <div class="sme-form-footer d-flex flex-wrap justify-content-end gap-2">
                    <a href="{{ route('student.medical.exemption.index') }}"
                       class="btn btn-outline-secondary px-4">Cancel</a>
                    <button class="btn btn-primary px-4" type="submit">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Select2 (select2.full.min.js) is already loaded globally in the admin footer. --}}
<script>
$(document).ready(function() {
    // Turn every select into a Select2 dropdown styled (via CSS) like .form-select.
    $('.sme-form select').each(function(){
        var $sel = $(this);
        var $parent = $sel.closest('.sme-field');
        if (!$parent.length) { $parent = $sel.parent(); }
        $sel.select2({ width: '100%', dropdownParent: $parent, allowClear: false });
    });

    // Course -> Officer Trainee cascade (reloads the OT list when the course changes).
    function setStudents(list, placeholder, loading) {
        var $sel = $('#studentDropdown');
        if (!$sel.length) return;
        var $opts = $('<div>').append($('<option>').val('').text(placeholder || 'Select Officer Trainee'));
        (list || []).forEach(function(s) {
            var label = s.display_name + (s.generated_OT_code ? ' (' + s.generated_OT_code + ')' : '');
            $opts.append($('<option>').val(String(s.pk)).text(label));
        });
        $sel.html($opts.html()).val('');
        if ($sel.hasClass('select2-hidden-accessible')) { $sel.trigger('change.select2'); }
    }
    $('#courseDropdown').on('change', function() {
        var courseId = $(this).val();
        if (!courseId) { setStudents([], 'Select Course First'); return; }
        setStudents(null, 'Loading...', true);
        $.get('{{ route("student.medical.exemption.getStudentsByCourse") }}', { course_id: courseId })
            .done(function(res) { setStudents(res.students, 'Select Officer Trainee'); });
    });

    // Medical Case = PT Exemption -> fill Start/End Time from the selected course's PT window.
    function applyPtTimesIfExempted() {
        if ($('#smeMedicalCase').val() !== 'PT Exemption') return;
        var selected = $('#courseDropdown option:selected');
        var ptStart = selected.data('ptStartTime') || '';
        var ptEnd = selected.data('ptEndTime') || '';
        if (ptStart) { $('#arrivalTime').val(ptStart); }
        if (ptEnd) { $('#departureTime').val(ptEnd); }
    }
    $('#smeMedicalCase').on('change', applyPtTimesIfExempted);
    $('#courseDropdown').on('change', applyPtTimesIfExempted);

    // Medical Case = PT Exemption -> show the Doctor's Comments section; keep at
    // least one (dated today, editable) row present whenever it's visible.
    var smeCommentRowSeq = 0;
    function todayYmd() {
        var d = new Date();
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
    function addCommentRow(comment, date, pk) {
        var idx = smeCommentRowSeq++;
        var pkField = pk ? '<input type="hidden" name="pt_comments[' + idx + '][pk]" value="' + pk + '">' : '';
        var $row = $(
            '<div class="row g-3 mb-2 sme-comment-row" data-row="' + idx + '">' +
                pkField +
                '<div class="col-md-8">' +
                    '<textarea name="pt_comments[' + idx + '][comment]" class="form-control" rows="2" placeholder="eg. You can go for normal exercise">' + $('<div>').text(comment || '').html() + '</textarea>' +
                '</div>' +
                '<div class="col-md-3">' +
                    '<input type="date" name="pt_comments[' + idx + '][comment_date]" class="form-control" value="' + (date || todayYmd()) + '">' +
                '</div>' +
                '<div class="col-md-1 d-flex align-items-start">' +
                    '<button type="button" class="btn btn-sm btn-outline-danger sme-remove-comment-btn" title="Remove">&times;</button>' +
                '</div>' +
            '</div>'
        );
        $('#smePtCommentsList').append($row);
    }
    $('#smeAddCommentBtn').on('click', function() { addCommentRow('', todayYmd()); });
    $(document).on('click', '.sme-remove-comment-btn', function() {
        $(this).closest('.sme-comment-row').remove();
    });
    function togglePtCommentRow() {
        var isPtExemption = $('#smeMedicalCase').val() === 'PT Exemption';
        $('#smePtCommentSection').toggle(isPtExemption);
        if (isPtExemption && $('#smePtCommentsList .sme-comment-row').length === 0) {
            addCommentRow('', todayYmd());
        }
        if (!isPtExemption) {
            $('#smePtCommentsList').empty();
        }
    }
    // Prefill existing comments (edit mode) before wiring the toggle so they
    // aren't wiped out if the record's medical case is already PT Exemption.
    try {
        var existingComments = JSON.parse($('#smePtCommentsList').attr('data-existing') || '[]');
        existingComments.forEach(function(c) { addCommentRow(c.comment, c.comment_date, c.pk); });
    } catch (e) { /* no-op */ }
    $('#smeMedicalCase').on('change', togglePtCommentRow);
    togglePtCommentRow();

    // Exemption Category = Cat-A (From PT) -> force Medical Case to "PT Exemption" and freeze it.
    // Kept enabled (not `disabled`) so the value still posts to the server; the dropdown
    // is just blocked from opening while frozen.
    function applyMedicalCaseLockForCategory() {
        var $category = $('#smeExemptionCategory');
        var $medicalCase = $('#smeMedicalCase');
        if (!$category.length || !$medicalCase.length) return;
        var isCatAFromPt = $category.find('option:selected').text().trim() === 'Cat-A (From PT)';
        $medicalCase.data('smeFrozen', isCatAFromPt);
        $medicalCase.next('.select2-container').toggleClass('sme-frozen-select2', isCatAFromPt);
        if (isCatAFromPt) {
            $medicalCase.val('PT Exemption');
            if ($medicalCase.hasClass('select2-hidden-accessible')) { $medicalCase.trigger('change.select2'); }
            applyPtTimesIfExempted();
            togglePtCommentRow();
        }
    }
    $('#smeExemptionCategory').on('change', applyMedicalCaseLockForCategory);
    $(document).on('select2:opening', '#smeMedicalCase', function(e) {
        if ($(this).data('smeFrozen')) { e.preventDefault(); }
    });
    applyMedicalCaseLockForCategory();

    function recalcDays() {
        var a = document.getElementById('arrivalDate');
        var d = document.getElementById('departureDate');
        var out = document.getElementById('daysField');
        if (!a || !d || !out) return;
        if (a.value && d.value) {
            var diff = Math.floor((new Date(d.value) - new Date(a.value)) / 86400000);
            out.value = (diff >= 0) ? (diff + 1) : '';
        }
    }
    ['arrivalDate', 'departureDate'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('change', recalcDays);
    });

    // Advisory warning: does this student already have ANY OTHER exemption
    // overlapping this date-time range? Purely informational — never blocks submit.
    var smeConflictTimer = null;
    function checkTimeConflict() {
        var studentId = $('#studentDropdown').val();
        var arrivalDate = $('#arrivalDate').val();
        if (!studentId || !arrivalDate) { $('#smeConflictWarning').remove(); return; }

        $.get('{{ route("student.medical.exemption.checkTimeConflict") }}', {
            student_master_pk: studentId,
            arrival_date: arrivalDate,
            arrival_time: $('#arrivalTime').val(),
            departure_date: $('#departureDate').val(),
            departure_time: $('#departureTime').val(),
            exclude_id: '{{ encrypt($record->pk) }}'
        }).done(function(res) {
            $('#smeConflictWarning').remove();
            if (res && res.conflict) {
                $('.sme-form').prepend('<div id="smeConflictWarning" class="alert alert-warning py-2 px-3 mb-3 sme-conflict-pulse fw-bold">'
                    + '<i class="bi bi-exclamation-triangle-fill me-1"></i> ' + res.message + '</div>');
                document.getElementById('smeConflictWarning').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }
    $(document).on('change', '#studentDropdown, #arrivalDate, #arrivalTime, #departureDate, #departureTime', function() {
        clearTimeout(smeConflictTimer);
        smeConflictTimer = setTimeout(checkTimeConflict, 300);
    });
});
</script>
@endpush
