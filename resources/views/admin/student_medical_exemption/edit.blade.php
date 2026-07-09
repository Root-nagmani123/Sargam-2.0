@extends('admin.layouts.master')

@section('title', 'Edit Student Medical Exemption - Sargam | Lal Bahadur')

@section('setup_content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css">
<link rel="stylesheet" href="{{ asset('css/choices-theme.css') }}?v={{ filemtime(public_path('css/choices-theme.css')) }}">
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
}
.sme-form .form-control,
.sme-form .form-select { min-height: 44px; border-radius: var(--ds-radius-2); }
.sme-form input[readonly].sme-days { background: var(--bs-secondary-bg, #eef1f4); color: var(--ds-ink); }
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
    $arrDate = $record->from_date ? \Carbon\Carbon::parse($record->from_date)->format('Y-m-d') : '';
    $arrTime = $record->from_date ? \Carbon\Carbon::parse($record->from_date)->format('H:i') : '';
    $depDate = $record->to_date ? \Carbon\Carbon::parse($record->to_date)->format('Y-m-d') : '';
    $depTime = $record->to_date ? \Carbon\Carbon::parse($record->to_date)->format('H:i') : '';
    $opdOptions = ['IPD', 'OPD', 'After OPD', 'Referral'];
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
                            <option value="{{ $course->pk }}" {{ $record->course_master_pk == $course->pk ? 'selected' : '' }}>
                                {{ $course->course_name }}
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
                    <div class="col-md-4">
                        <label class="form-label">IPD/OPD/After OPD/Referral <span class="text-danger">*</span></label>
                        <select name="opd_category" class="form-select" required>
                            <option value="">Select Category</option>
                            @foreach($opdOptions as $opt)
                            <option value="{{ $opt }}" {{ $record->opd_category == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        @error('opd_category')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Arrival Date <span class="text-danger">*</span></label>
                        <input type="date" name="arrival_date" id="arrivalDate" class="form-control" required value="{{ $arrDate }}">
                        @error('arrival_date')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Arrival Time <span class="text-danger">*</span></label>
                        <input type="time" name="arrival_time" id="arrivalTime" class="form-control" required value="{{ $arrTime }}">
                        @error('arrival_time')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Departure Date <span class="text-danger">*</span></label>
                        <input type="date" name="departure_date" id="departureDate" class="form-control" required value="{{ $depDate }}">
                        @error('departure_date')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Departure Time <span class="text-danger">*</span></label>
                        <input type="time" name="departure_time" id="departureTime" class="form-control" required value="{{ $depTime }}">
                        @error('departure_time')<small class="text-danger">{{ $message }}</small>@enderror
                        @error('to_date')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-4">
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

                    <div class="col-md-4">
                        <label class="form-label">Days</label>
                        <input type="number" name="days" id="daysField" class="form-control sme-days" placeholder="eg. 6" readonly value="{{ $record->days }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="active_inactive" class="form-select" required>
                            <option value="1" {{ $record->active_inactive == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ $record->active_inactive == 0 ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('active_inactive')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-12">
                        <div class="row g-3 sme-remarks-row">
                            <div class="col-md-6">
                                <label class="form-label">Provisional Diagnosis/ Remarks</label>
                                <textarea name="Description" class="form-control" rows="3" placeholder="eg. Lorem ipsum dolor">{{ $record->Description }}</textarea>
                                @error('Description')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">PT/Outdoor Advise</label>
                                <textarea name="pt_outdoor_advise" class="form-control" rows="3" placeholder="eg. Yoga">{{ $record->pt_outdoor_advise }}</textarea>
                                @error('pt_outdoor_advise')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                        </div>
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
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
<script>
$(document).ready(function() {
    var choicesMap = {};
    document.querySelectorAll('.sme-form select').forEach(function(sel) {
        choicesMap[sel.id || sel.name] = new Choices(sel, {
            searchEnabled: sel.options.length > 5,
            searchPlaceholderValue: 'Search...',
            itemSelectText: '',
            shouldSort: false,
            allowHTML: false
        });
    });

    // Course -> Officer Trainee cascade (reloads the OT list when the course changes).
    var studentChoices = choicesMap['studentDropdown'];
    function setStudents(list, placeholder, loading) {
        if (!studentChoices) return;
        var choices = [{ value: '', label: placeholder || 'Select Officer Trainee', selected: true, disabled: !!loading }];
        (list || []).forEach(function(s) {
            choices.push({ value: String(s.pk), label: s.display_name + (s.generated_OT_code ? ' (' + s.generated_OT_code + ')' : '') });
        });
        studentChoices.clearStore();
        studentChoices.setChoices(choices, 'value', 'label', true);
    }
    $('#courseDropdown').on('change', function() {
        var courseId = $(this).val();
        if (!courseId) { setStudents([], 'Select Course First'); return; }
        setStudents(null, 'Loading...', true);
        $.get('{{ route("student.medical.exemption.getStudentsByCourse") }}', { course_id: courseId })
            .done(function(res) { setStudents(res.students, 'Select Officer Trainee'); });
    });

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
});
</script>
@endpush
