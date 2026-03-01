@extends('admin.layouts.master')

@section('title', 'Student Medical Exemption - Sargam | Lal Bahadur')

@section('setup_content')
<style>
/* Student Medical Exemption Create - Modern Bootstrap 5 */
.student-medical-create .form-card {
    border: none;
    border-radius: 0.75rem;
    box-shadow: 0 0.125rem 0.375rem rgba(0, 0, 0, 0.06), 0 0.5rem 1rem rgba(0, 0, 0, 0.04);
}
.student-medical-create .form-card .card-body {
    padding: 1.5rem;
}
.student-medical-create .section-title {
    font-size: 0.8125rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--bs-secondary);
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--bs-border-color-translucent);
}
.student-medical-create .form-label {
    font-weight: 500;
    color: var(--bs-body-color);
}
.student-medical-create .form-control,
.student-medical-create .form-select {
    border-radius: 0.5rem;
    min-height: 2.5rem;
}
.student-medical-create .form-control:focus,
.student-medical-create .form-select:focus {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.15);
}
.student-medical-create .file-upload-hint {
    background: var(--bs-tertiary-bg);
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: var(--bs-secondary);
}
.student-medical-create .form-actions {
    padding-top: 1rem;
    border-top: 1px solid var(--bs-border-color-translucent);
}
.student-medical-create .btn {
    border-radius: 0.5rem;
    font-weight: 500;
    min-width: 6rem;
}

/* Responsive */
@media (min-width: 576px) {
    .student-medical-create .form-card .card-body {
        padding: 1.75rem;
    }
}
@media (min-width: 768px) {
    .student-medical-create .form-card .card-body {
        padding: 2rem;
    }
    .student-medical-create .section-title {
        margin-top: 1.25rem;
    }
    .student-medical-create .section-title:first-child {
        margin-top: 0;
    }
}
@media (min-width: 992px) {
    .student-medical-create .form-card .card-body {
        padding: 2.25rem;
    }
}
@media (max-width: 767.98px) {
    .student-medical-create .form-card .card-body {
        padding: 1rem;
    }
    .student-medical-create .form-actions {
        flex-direction: column;
        gap: 0.5rem !important;
    }
    .student-medical-create .form-actions .btn {
        width: 100%;
        min-width: unset;
    }
}
@media (max-width: 575.98px) {
    .student-medical-create .form-card .card-body {
        padding: 0.75rem;
    }
    .student-medical-create .section-title {
        font-size: 0.75rem;
        margin-bottom: 0.75rem;
    }
    .student-medical-create .form-label {
        font-size: 0.9375rem;
    }
    .student-medical-create .form-control,
    .student-medical-create .form-select {
        font-size: 0.9375rem;
        min-height: 2.625rem;
    }
}
.student-medical-create input[type="datetime-local"] {
    max-width: 100%;
}
.student-medical-create .select2-container {
    width: 100% !important;
}
</style>
<div class="container-fluid student-medical-create py-2 py-md-3">
    <x-session_message />
    <x-breadcrum title="Add Student Medical Exemption" />

    <div class="card form-card mt-3">
        <div class="card-body">
            <form method="POST" action="{{ route('student.medical.exemption.store') }}" enctype="multipart/form-data">
                @csrf

                <p class="section-title mb-3">Course &amp; Student</p>
                <div class="row g-3 mb-0">
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label class="form-label">Course <span class="text-danger">*</span></label>
                        <select name="course_master_pk" class="form-select" id="courseDropdown" required>
                            <option value="">Select Course</option>
                            @foreach($courses as $course)
                            <option value="{{ $course->pk }}" {{ old('course_master_pk') == $course->pk ? 'selected' : '' }}>
                                {{ $course->course_name }}
                            </option>
                            @endforeach
                        </select>
                        @error('course_master_pk')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label class="form-label">Student Name <span class="text-danger">*</span></label>
                        <select name="student_master_pk" class="form-select select2" id="studentDropdown" required>
                            <option value="">Search Student</option>
                        </select>
                        @error('student_master_pk')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label class="form-label">OT Code</label>
                        <input type="text" class="form-control bg-light" name="ot_code" id="otCodeField" readonly placeholder="—">
                    </div>
                </div>

                <p class="section-title">Exemption Details</p>
                <div class="row g-3 mb-0">
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label class="form-label">Exemption Category <span class="text-danger">*</span></label>
                        <select name="exemption_category_master_pk" class="form-select" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->pk }}" {{ old('exemption_category_master_pk') == $cat->pk ? 'selected' : '' }}>
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
                            <option value="{{ $type }}" {{ old('opd_category') == $type ? 'selected' : '' }}>{{ $type }}</option>
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
                            <option value="{{ $spec->pk }}" {{ old('exemption_medical_speciality_pk') == $spec->pk ? 'selected' : '' }}>
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
                        <input type="datetime-local" name="from_date" class="form-control" required value="{{ old('from_date') }}">
                        @error('from_date')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label class="form-label">To Date &amp; Time</label>
                        <input type="datetime-local" name="to_date" class="form-control" value="{{ old('to_date') }}">
                        @error('to_date')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <p class="section-title">Description &amp; Document</p>
                <div class="row g-3 mb-0">
                    <div class="col-12 col-lg-6">
                        <label class="form-label">Description</label>
                        <textarea name="Description" class="form-control" rows="3" placeholder="Optional notes">{{ old('Description') }}</textarea>
                        @error('Description')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="col-12 col-lg-6">
                        <label class="form-label">Upload Document <span class="text-danger">*</span></label>
                        <div class="file-upload-hint mb-2">
                            Image or PDF only. Max size: 3 MB.
                        </div>
                        <input type="file" name="Doc_upload" class="form-control" accept="image/*,.pdf" id="Doc_upload">
                        <small id="fileInfo" class="text-muted d-block mt-1"></small>
                        <small id="fileError" class="text-danger d-block mt-1"></small>
                        @error('Doc_upload')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-end gap-2 form-actions mt-4">
                    <a href="{{ route('student.medical.exemption.index') }}" class="btn btn-outline-secondary">Back</a>
                    <button class="btn btn-success" type="submit">Submit</button>
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

    $('#courseDropdown').on('change', function() {
        var courseId = $(this).val();
        $('#studentDropdown').html('<option value="">Loading...</option>');

        if (courseId !== '') {
            $.ajax({
                url: '{{ route("student.medical.exemption.getStudentsByCourse") }}',
                type: 'GET',
                data: { course_id: courseId },
                success: function(response) {
                    var options = '<option value="">Search Student</option>';
                    $.each(response.students, function(i, student) {
                        options += '<option value="' + student.pk + '" data-ot_code="' + (student.generated_OT_code || '') + '">' + student.display_name + '</option>';
                    });
                    $('#studentDropdown').html(options);
                    $('#studentDropdown').select2('destroy').select2({
                        placeholder: 'Search Student',
                        allowClear: true,
                        width: '100%'
                    });
                }
            });
        } else {
            $('#studentDropdown').html('<option value="">Select Course First</option>');
            $('#studentDropdown').select2('destroy').select2({
                placeholder: 'Select Course First',
                allowClear: true,
                width: '100%'
            });
        }
    });

    $('#studentDropdown').on('change', function() {
        var otCode = $(this).find('option:selected').data('ot_code') || '';
        $('#otCodeField').val(otCode);
    });
});

// Restore student after validation error
let oldCourse = "{{ old('course_master_pk') }}";
let oldStudent = "{{ old('student_master_pk') }}";

if (oldCourse) {

    $('#courseDropdown').val(oldCourse);

    $.ajax({
        url: '{{ route("student.medical.exemption.getStudentsByCourse") }}',
        type: 'GET',
        data: { course_id: oldCourse },
        success: function(response) {

            var options = '<option value="">Search Student</option>';

            $.each(response.students, function(i, student) {

                let selected = oldStudent == student.pk ? 'selected' : '';

                options += '<option value="' + student.pk + '" data-ot_code="' +
                    (student.generated_OT_code || '') + '" ' + selected + '>' +
                    student.display_name + '</option>';
            });

            $('#studentDropdown').html(options).trigger('change');

            $('#studentDropdown').select2('destroy').select2({
                placeholder: 'Search Student',
                allowClear: true,
                width: '100%'
            });

            // Restore OT code
            let otCode = $('#studentDropdown option:selected').data('ot_code') || '';
            $('#otCodeField').val(otCode);
        }
    });
}

</script>
<script>
document.getElementById('Doc_upload').addEventListener('change', function() {
    var file = this.files[0];
    var fileInfo = document.getElementById('fileInfo');
    var fileError = document.getElementById('fileError');

    fileInfo.textContent = '';
    fileError.textContent = '';

    if (!file) return;

    var allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    var maxSize = 3 * 1024 * 1024;

    if (allowedTypes.indexOf(file.type) === -1) {
        fileError.textContent = 'Only image files or PDF are allowed.';
        this.value = '';
        return;
    }
    if (file.size > maxSize) {
        fileError.textContent = 'File size must not exceed 3 MB.';
        this.value = '';
        return;
    }
    var sizeMB = (file.size / (1024 * 1024)).toFixed(2);
    fileInfo.innerHTML = 'Selected: <strong>' + file.name + '</strong> &middot; ' + sizeMB + ' MB';
});
</script>
@endpush
