@extends('admin.layouts.master')

@section('title', 'Edit Student Medical Exemption - Sargam | Lal Bahadur')

@section('setup_content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css">
<link rel="stylesheet" href="{{ asset('css/choices-theme.css') }}?v={{ filemtime(public_path('css/choices-theme.css')) }}">
<style>
/* Sectioned form polish (shared look with the Add modal) */
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
.sme-form .select2-container .select2-selection--single {
    min-height: var(--ds-control-h);
    border-color: var(--bs-border-color);
    border-radius: var(--ds-radius-1);
    display: flex;
    align-items: center;
}
.sme-form .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: calc(var(--ds-control-h) - 2px);
}
.sme-form-footer {
    margin-top: var(--ds-space-4);
    padding-top: var(--ds-space-3);
    border-top: 1px solid var(--ds-line);
}
</style>

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

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Doctor Name <span class="text-danger">*</span></label>
                        <select name="employee_master_pk" class="form-select" readonly required>
                            @if(Auth::user())
                            <option value="{{ Auth::user()->user_id }}" selected>{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</option>
                            @endif
                        </select>
                        @error('employee_master_pk')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Course <span class="text-danger">*</span></label>
                        <select name="course_master_pk" class="form-select" required>
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
                        <label class="form-label">Student Name <span class="text-danger">*</span></label>
                        <select name="student_master_pk" class="form-select" id="studentDropdown" required>
                            <option value="">Search Student</option>
                            @foreach($students as $student)
                            <option value="{{ $student->pk }}" data-ot_code="{{ $student->generated_OT_code }}"
                                {{ $record->student_master_pk == $student->pk ? 'selected' : '' }}>
                                {{ $student->display_name }}
                            </option>
                            @endforeach
                        </select>
                        @error('student_master_pk')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">OT Code</label>
                        <input type="text" class="form-control" name="ot_code" id="otCodeField"
                               value="{{ $record->student->generated_OT_code ?? '' }}" placeholder="eg. A72" disabled>
                    </div>
                </div>

                {{-- ============ Exemption and Other Information ============ --}}
                <h6 class="sme-section-title mt-4">Exemption and Other Information</h6>

                <div class="row g-3">
                    <div class="col-md-4">
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

                    <div class="col-md-4">
                        <label class="form-label">OPD Category</label>
                        <select name="opd_category" class="form-select">
                            <option value="">Select Type</option>
                            @foreach(['OPD', 'Referred', 'IPD', 'Other'] as $type)
                            <option value="{{ $type }}" {{ $record->opd_category == $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                        @error('opd_category')<small class="text-danger">{{ $message }}</small>@enderror
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

                    <div class="col-md-6">
                        <label class="form-label">Start Date &amp; Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="from_date" class="form-control" required
                               value="{{ $record->from_date ? \Carbon\Carbon::parse($record->from_date)->format('Y-m-d\TH:i') : '' }}">
                        @error('from_date')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">End Date &amp; Time</label>
                        <input type="datetime-local" name="to_date" class="form-control"
                               value="{{ $record->to_date ? \Carbon\Carbon::parse($record->to_date)->format('Y-m-d\TH:i') : '' }}">
                        @error('to_date')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="active_inactive" class="form-select" required>
                            <option value="1" {{ $record->active_inactive == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ $record->active_inactive == 0 ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('active_inactive')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Attachment</label>
                        <input type="file" name="Doc_upload" class="form-control">
                        @if($record->Doc_upload)
                        <a href="{{ asset('storage/' . $record->Doc_upload) }}" target="_blank"
                           class="d-inline-block mt-1 small">View existing file</a>
                        @endif
                        @error('Doc_upload')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="Description" class="form-control" rows="3"
                                  placeholder="eg. Lorem ipsum dolor">{{ $record->Description }}</textarea>
                        @error('Description')<small class="text-danger">{{ $message }}</small>@enderror
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
        // ot-code lookup built from the server-rendered options (before Choices init)
        var otMap = {};
        $('#studentDropdown option').each(function() {
            if (this.value) otMap[this.value] = String($(this).data('ot_code') || '');
        });

        // Turn EVERY <select> in the form into a Choices.js dropdown
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

        var studentSelect = document.getElementById('studentDropdown');
        var studentChoices = choicesMap['studentDropdown'];

        studentSelect.addEventListener('change', function() {
            $('#otCodeField').val(otMap[studentChoices.getValue(true)] || '');
        });
    });
</script>
@endpush
