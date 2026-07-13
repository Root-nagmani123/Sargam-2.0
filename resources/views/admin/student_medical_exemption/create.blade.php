@extends('admin.layouts.master')

@section('title', 'Student Medical Exemption')

@section('setup_content')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}?v={{ filemtime(public_path('css/select2-theme.css')) }}">
<style>
/* =====================================================================
   Add Student Medical Exemption — page-scoped polish.
   Tokens/components come from sargam-app.css (--ds-*, .ds-*).
   ===================================================================== */
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
}
.sme-form .form-control,
.sme-form .form-select {
    min-height: 44px;
    border-radius: var(--ds-radius-2);
}
.sme-form input[readonly].sme-days {
    background: var(--bs-secondary-bg, #eef1f4);
    color: var(--ds-ink);
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
</style>

@php
    $doctorName = Auth::user() ? trim((Auth::user()->first_name ?? '') . ' ' . (Auth::user()->last_name ?? '')) : '';
    // Driven by the Medical Case Master (falls back to the legacy list if none active).
    $opdOptions = (isset($opdOptions) && count($opdOptions)) ? $opdOptions : ['IPD', 'OPD', 'After OPD', 'Referral'];
@endphp

<div class="container-fluid">
    <x-breadcrum title="Add Student Medical Exemption" />
    <x-session_message />

    <div class="ds-card">
        <div class="ds-card-body">
            <form method="POST" action="{{ route('student.medical.exemption.store') }}"
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
                            <option value="{{ $course->pk }}" {{ old('course_master_pk') == $course->pk ? 'selected' : '' }}>
                                {{ $course->couse_short_name ?: $course->course_name }}
                            </option>
                            @endforeach
                        </select>
                        @error('course_master_pk')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Name of Officer Trainee <span class="text-danger">*</span></label>
                        <select name="student_master_pk" id="studentDropdown" class="form-select" required>
                            <option value="">Select Course First</option>
                        </select>
                        @error('student_master_pk')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Treating Doctor Name <span class="text-danger">*</span></label>
                        <select name="employee_master_pk" class="form-select" required>
                            @if(Auth::user())
                            <option value="{{ Auth::user()->user_id }}" selected>{{ $doctorName !== '' ? $doctorName : 'Current Doctor' }}</option>
                            @endif
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Exemption Category <span class="text-danger">*</span></label>
                        <select name="exemption_category_master_pk" class="form-select" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->pk }}" {{ old('exemption_category_master_pk') == $cat->pk ? 'selected' : '' }}>
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
                    <div class="col-md-4">
                        <label class="form-label">IPD/OPD/After OPD/Referral <span class="text-danger">*</span></label>
                        <select name="opd_category" class="form-select" required>
                            <option value="">Select Category</option>
                            @foreach($opdOptions as $opt)
                            <option value="{{ $opt }}" {{ old('opd_category') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        @error('opd_category')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="arrival_date" id="arrivalDate" class="form-control sme-arr" required value="{{ old('arrival_date') }}">
                        @error('arrival_date')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Start Time <span class="text-danger">*</span></label>
                        <input type="time" name="arrival_time" id="arrivalTime" class="form-control" required value="{{ old('arrival_time') }}">
                        @error('arrival_time')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="departure_date" id="departureDate" class="form-control sme-dep" required value="{{ old('departure_date') }}">
                        @error('departure_date')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">End Time <span class="text-danger">*</span></label>
                        <input type="time" name="departure_time" id="departureTime" class="form-control" required value="{{ old('departure_time') }}">
                        @error('departure_time')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Medical Speciality <span class="text-danger">*</span></label>
                        <select name="exemption_medical_speciality_pk" class="form-select" required>
                            <option value="">Select Speciality</option>
                            @foreach($specialities as $spec)
                            <option value="{{ $spec->pk }}" {{ old('exemption_medical_speciality_pk') == $spec->pk ? 'selected' : '' }}>
                                {{ $spec->speciality_name }}
                            </option>
                            @endforeach
                        </select>
                        @error('exemption_medical_speciality_pk')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Days</label>
                        <input type="number" name="days" id="daysField" class="form-control sme-days" placeholder="eg. 6" readonly value="{{ old('days') }}">
                    </div>

                    <div class="col-12">
                        <div class="row g-3 sme-remarks-row">
                            <div class="col-md-6">
                                <label class="form-label">Diagnosis / Remarks</label>
                                <textarea name="Description" class="form-control" rows="3" placeholder="eg. Enter remarks...">{{ old('Description') }}</textarea>
                                @error('Description')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">PT/Outdoor Advise</label>
                                <textarea name="pt_outdoor_advise" class="form-control" rows="3" placeholder="eg. Yoga">{{ old('pt_outdoor_advise') }}</textarea>
                                @error('pt_outdoor_advise')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Attachment</label>
                        <input type="file" name="Doc_upload" id="Doc_upload" class="form-control"
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <div class="form-text text-muted mt-1">
                            Allowed types: PDF, JPG, JPEG, PNG, DOC, DOCX &nbsp;|&nbsp; Max size: <strong>5 MB</strong>
                        </div>
                        <div id="fileError" class="text-danger small mt-1" style="display:none;"></div>
                        @error('Doc_upload')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                </div>

                {{-- ============ Footer ============ --}}
                <div class="sme-form-footer d-flex flex-wrap justify-content-end gap-2">
                    <a href="{{ route('student.medical.exemption.index') }}"
                       class="btn btn-outline-secondary px-4">Cancel</a>
                    <button class="btn btn-primary px-4" type="submit">Add Student Medical Exemption</button>
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
    // Standalone page init (the modal path has its own initialiser in the index view).
    // Turn every select into a Select2 dropdown styled (via CSS) like .form-select.
    $('.sme-form select').each(function(){
        $(this).select2({ width: '100%', allowClear: false });
    });

    // Course -> Officer Trainee cascade.
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

    // Days = inclusive span between arrival and departure dates.
    function recalcDays() {
        var a = document.getElementById('arrivalDate');
        var d = document.getElementById('departureDate');
        var out = document.getElementById('daysField');
        if (!a || !d || !out) return;
        if (a.value && d.value) {
            var da = new Date(a.value), dd = new Date(d.value);
            var diff = Math.floor((dd - da) / 86400000);
            out.value = (diff >= 0) ? (diff + 1) : '';
        } else {
            out.value = '';
        }
    }
    ['arrivalDate', 'departureDate'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('change', recalcDays);
    });
    recalcDays();

    // Attachment validation
    var ALLOWED_EXT  = ['pdf','jpg','jpeg','png','doc','docx'];
    var MAX_SIZE_MB  = 5;
    document.getElementById('Doc_upload').addEventListener('change', function() {
        var errEl = document.getElementById('fileError');
        errEl.style.display = 'none';
        errEl.textContent = '';
        if (!this.files.length) return;
        var file = this.files[0];
        var ext  = file.name.split('.').pop().toLowerCase();
        if (!ALLOWED_EXT.includes(ext)) {
            errEl.textContent = 'Invalid file type. Allowed: PDF, JPG, JPEG, PNG, DOC, DOCX.';
            errEl.style.display = 'block';
            this.value = '';
            return;
        }
        if (file.size > MAX_SIZE_MB * 1024 * 1024) {
            errEl.textContent = 'File size exceeds ' + MAX_SIZE_MB + ' MB limit.';
            errEl.style.display = 'block';
            this.value = '';
        }
    });

    document.querySelector('.sme-form').addEventListener('submit', function(e) {
        var errEl = document.getElementById('fileError');
        if (errEl.style.display !== 'none' && errEl.textContent) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
