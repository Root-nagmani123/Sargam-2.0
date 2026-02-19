@extends('admin.layouts.master')

@section('title', 'Edit Student Medical Exemption - Sargam | Lal Bahadur')

@section('setup_content')
<style>
/* Student Medical Exemption Edit - Modern Bootstrap 5 */
.student-medical-edit .form-card {
    border: none;
    border-left: 4px solid #004a93;
    border-radius: 0.75rem;
    box-shadow: 0 0.125rem 0.375rem rgba(0, 0, 0, 0.06), 0 0.5rem 1rem rgba(0, 0, 0, 0.04);
}
.student-medical-edit .form-card .card-body {
    padding: 1.5rem;
}
.student-medical-edit .section-title {
    font-size: 0.8125rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--bs-secondary);
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--bs-border-color-translucent);
}
.student-medical-edit .form-label {
    font-weight: 500;
    color: var(--bs-body-color);
}
.student-medical-edit .form-control,
.student-medical-edit .form-select {
    border-radius: 0.5rem;
    min-height: 2.5rem;
}
.student-medical-edit .form-control:focus,
.student-medical-edit .form-select:focus {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.15);
}
.student-medical-edit .file-upload-hint {
    background: var(--bs-tertiary-bg);
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: var(--bs-secondary);
}
.student-medical-edit .form-actions {
    padding-top: 1rem;
    border-top: 1px solid var(--bs-border-color-translucent);
}
.student-medical-edit .btn {
    border-radius: 0.5rem;
    font-weight: 500;
    min-width: 6rem;
}

@media (min-width: 576px) {
    .student-medical-edit .form-card .card-body {
        padding: 1.75rem;
    }
}
@media (min-width: 768px) {
    .student-medical-edit .form-card .card-body {
        padding: 2rem;
    }
    .student-medical-edit .section-title {
        margin-top: 1.25rem;
    }
    .student-medical-edit .section-title:first-child {
        margin-top: 0;
    }
}
@media (min-width: 992px) {
    .student-medical-edit .form-card .card-body {
        padding: 2.25rem;
    }
}
@media (max-width: 767.98px) {
    .student-medical-edit .form-card .card-body {
        padding: 1rem;
    }
    .student-medical-edit .form-actions {
        flex-direction: column;
        gap: 0.5rem !important;
    }
    .student-medical-edit .form-actions .btn {
        width: 100%;
        min-width: unset;
    }
}
@media (max-width: 575.98px) {
    .student-medical-edit .form-card .card-body {
        padding: 0.75rem;
    }
    .student-medical-edit .section-title {
        font-size: 0.75rem;
        margin-bottom: 0.75rem;
    }
    .student-medical-edit .form-label {
        font-size: 0.9375rem;
    }
    .student-medical-edit .form-control,
    .student-medical-edit .form-select {
        font-size: 0.9375rem;
        min-height: 2.625rem;
    }
}
.student-medical-edit input[type="datetime-local"] {
    max-width: 100%;
}
.student-medical-edit .select2-container {
    width: 100% !important;
}
</style>
<div class="container-fluid student-medical-edit py-2 py-md-3">
    <x-session_message />
    <x-breadcrum title="Edit Student Medical Exemption" />

    <div class="card form-card mt-3">
        <div class="card-body">
            <form method="POST" action="{{ route('student.medical.exemption.update', encrypt($record->pk)) }}" enctype="multipart/form-data">
                @csrf

                <p class="section-title mb-3">Doctor &amp; Course</p>
                <div class="row g-3 mb-0">
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label class="form-label">Doctor Name <span class="text-danger">*</span></label>
                        <select name="employee_master_pk" class="form-select" required>
                            @if(Auth::user())
                            <option value="{{ Auth::user()->user_id }}" selected>{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</option>
                            @endif
                        </select>
                        @error('employee_master_pk')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label class="form-label">Course <span class="text-danger">*</span></label>
                        <select name="course_master_pk" class="form-select" required>
                            <option value="">Select Course</option>
                            @foreach($courses as $course)
                            <option value="{{ $course->pk }}" {{ $record->course_master_pk == $course->pk ? 'selected' : '' }}>
                                {{ $course->course_name }}
                            </option>
                            @endforeach
                        </select>
                        @error('course_master_pk')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <p class="section-title">Student</p>
                <div class="row g-3 mb-0">
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label class="form-label">Student Name <span class="text-danger">*</span></label>
                        <select name="student_master_pk" class="form-select select2" id="studentDropdown" required>
                            <option value="">Search Student</option>
                            @foreach($students as $student)
                            <option value="{{ $student->pk }}" data-ot_code="{{ $student->generated_OT_code }}" {{ $record->student_master_pk == $student->pk ? 'selected' : '' }}>
                                {{ $student->display_name }}
                            </option>
                            @endforeach
                        </select>
                        @error('student_master_pk')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label class="form-label">OT Code</label>
                        <input type="text" class="form-control bg-light" name="ot_code" id="otCodeField" value="{{ $record->student->generated_OT_code ?? '' }}" readonly placeholder="—">
                    </div>
                </div>

                <p class="section-title">Exemption Details</p>
                <div class="row g-3 mb-0">
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label class="form-label">Exemption Category <span class="text-danger">*</span></label>
                        <select name="exemption_category_master_pk" class="form-select" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->pk }}" {{ $record->exemption_category_master_pk == $cat->pk ? 'selected' : '' }}>
                                {{ $cat->exemp_category_name }}
                            </option>
                            @endforeach
                        </select>
                        @error('exemption_category_master_pk')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label class="form-label">OPD Category</label>
                        <select name="opd_category" class="form-select">
                            <option value="">Select Type</option>
                            @foreach(['OPD', 'Referred', 'IPD', 'Other'] as $type)
                            <option value="{{ $type }}" {{ $record->opd_category == $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                        @error('opd_category')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label class="form-label">Medical Speciality <span class="text-danger">*</span></label>
                        <select name="exemption_medical_speciality_pk" class="form-select" required>
                            <option value="">Select Speciality</option>
                            @foreach($specialities as $spec)
                            <option value="{{ $spec->pk }}" {{ $record->exemption_medical_speciality_pk == $spec->pk ? 'selected' : '' }}>
                                {{ $spec->speciality_name }}
                            </option>
                            @endforeach
                        </select>
                        @error('exemption_medical_speciality_pk')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <p class="section-title">Date &amp; Time</p>
                <div class="row g-3 mb-0">
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label class="form-label">From Date &amp; Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="from_date" class="form-control" value="{{ $record->from_date ? \Carbon\Carbon::parse($record->from_date)->format('Y-m-d\TH:i') : '' }}" required>
                        @error('from_date')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label class="form-label">To Date &amp; Time</label>
                        <input type="datetime-local" name="to_date" class="form-control" value="{{ $record->to_date ? \Carbon\Carbon::parse($record->to_date)->format('Y-m-d\TH:i') : '' }}">
                        @error('to_date')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <p class="section-title">Description &amp; Document</p>
                <div class="row g-3 mb-0">
                    <div class="col-12 col-lg-6">
                        <label class="form-label">Description</label>
                        <textarea name="Description" class="form-control" rows="3" placeholder="Optional notes">{{ $record->Description }}</textarea>
                        @error('Description')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="col-12 col-lg-6">
                        <label class="form-label">Upload Document</label>
                        <input type="file" name="Doc_upload" class="form-control" accept="image/*,.pdf">
                        @if($record->Doc_upload)
                        <a href="{{ asset('storage/' . $record->Doc_upload) }}" target="_blank" class="btn btn-link btn-sm text-decoration-none mt-2 px-0">View existing file</a>
                        @endif
                        @error('Doc_upload')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <p class="section-title">Status</p>
                <div class="row g-3 mb-0">
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="active_inactive" class="form-select" required>
                            <option value="1" {{ $record->active_inactive == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ $record->active_inactive == 0 ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('active_inactive')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-end gap-2 form-actions mt-4">
                    <a href="{{ route('student.medical.exemption.index') }}" class="btn btn-outline-secondary">Back</a>
                    <button class="btn btn-primary" type="submit">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#studentDropdown').select2({
        placeholder: 'Search Student',
        allowClear: true,
        width: '100%'
    });
    $('#studentDropdown').on('change', function() {
        var otCode = $(this).find(':selected').data('ot_code') || '';
        $('#otCodeField').val(otCode);
    });
});
</script>
@endpush
