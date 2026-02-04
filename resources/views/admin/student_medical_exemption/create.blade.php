@extends('admin.layouts.master')

@section('title', 'Student Medical Exemption - Sargam | Lal Bahadur')

@section('setup_content')
<style>
/* Responsive - Student Medical Exemption Create Form */
@media (max-width: 991.98px) {
    .student-medical-create .card-body {
        padding: 1rem !important;
    }
    .student-medical-create .card-title {
        font-size: 1.1rem;
    }
}

@media (max-width: 767.98px) {
    .student-medical-create .container-fluid {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    .student-medical-create .card-body {
        padding: 0.75rem !important;
    }
    .student-medical-create .d-sm-flex {
        flex-direction: column;
        align-items: flex-start !important;
    }
    .student-medical-create .d-sm-flex .breadcrumb {
        margin-top: 0.5rem;
        margin-left: 0 !important;
    }
    .student-medical-create .form-actions {
        flex-direction: column;
        align-items: stretch !important;
    }
    .student-medical-create .form-actions .btn {
        width: 100%;
    }
}

@media (max-width: 575.98px) {
    .student-medical-create .container-fluid {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    .student-medical-create .card-body {
        padding: 0.5rem !important;
    }
    .student-medical-create .card-title {
        font-size: 1rem;
    }
    .student-medical-create .badge.fs-2 {
        font-size: 0.875rem !important;
    }
    .student-medical-create .form-label {
        font-size: 0.9rem;
    }
    .student-medical-create .form-control,
    .student-medical-create select.form-control {
        font-size: 0.9rem;
    }
}

/* Select2 responsive - ensure full width on mobile */
@media (max-width: 575.98px) {
    .student-medical-create .select2-container {
        width: 100% !important;
    }
}

/* Prevent datetime inputs from overflowing on mobile */
.student-medical-create input[type="datetime-local"] {
    max-width: 100%;
}
</style>
<div class="container-fluid student-medical-create">
    <x-session_message />
    <div class="card card-body py-3 student-medical-create" style="border-left:4px solid #004a93;">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Add Student Medical Exemption</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="index.html">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                    Student Medical Exemption
                                </span>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3 student-medical-create" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <h4 class="card-title mb-3">Student Medical Exemption</h4>
            <hr>
            <form method="POST" action="{{ route('student.medical.exemption.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="row g-2 g-md-3">
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Doctor Name <span class="text-danger">*</span></label>
                            <select name="employee_master_pk" class="form-control col-form-label" readonly required>
                                @if(Auth::user())
                                <option value="{{ Auth::user()->user_id }}" selected>{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</option>
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Course <span class="text-danger">*</span></label>
                            <select name="course_master_pk" class="form-control col-form-label" id="courseDropdown" required>
                                <option value="">Select Course</option>
                                @foreach($courses as $course)
                                <option value="{{ $course->pk }}"
                                    {{ old('course_master_pk') == $course->pk ? 'selected' : '' }}>
                                    {{ $course->course_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @error('course_master_pk')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                  <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Student Name <span class="text-danger">*</span></label>
                        <select name="student_master_pk" class="form-control select2" id="studentDropdown" required>
                            <option value="">Search Student</option>
                            {{-- Student options will be populated by AJAX --}}
                        </select>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label class="form-label">OT Code</label>
                        <input type="text" class="form-control" name="ot_code" id="otCodeField" readonly>
                    </div>
                </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Exemption Category <span class="text-danger">*</span></label>
                            <select name="exemption_category_master_pk" class="form-control col-form-label" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->pk }}"
                                    {{ old('exemption_category_master_pk') == $cat->pk ? 'selected' : '' }}>
                                    {{ $cat->exemp_category_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @error('exemption_category_master_pk')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="form-label">OPD Category</label>
                            <select name="opd_category" class="form-control col-form-label">
                                <option value="">Select Type</option>
                                @foreach(['OPD', 'Referred', 'IPD', 'Other'] as $type)
                                <option value="{{ $type }}" {{ old('opd_category') == $type ? 'selected' : '' }}>
                                    {{ $type }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @error('opd_category')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Medical Speciality <span class="text-danger">*</span></label>
                            <select name="exemption_medical_speciality_pk" class="form-control col-form-label" required>
                                <option value="">Select Speciality</option>
                                @foreach($specialities as $spec)
                                <option value="{{ $spec->pk }}"
                                    {{ old('exemption_medical_speciality_pk') == $spec->pk ? 'selected' : '' }}>
                                    {{ $spec->speciality_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @error('exemption_medical_speciality_pk')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="form-label">From Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="from_date" class="form-control col-form-label" required
                                value="{{ old('from_date') }}">
                        </div>
                        @error('from_date')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="form-label">To Date & Time</label>
                            <input type="datetime-local" name="to_date" class="form-control col-form-label"
                                value="{{ old('to_date') }}">
                        </div>
                        @error('to_date')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="Description" class="form-control col-form-label"
                                rows="2">{{ old('Description') }}</textarea>
                        </div>
                        @error('Description')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                        <label class="form-label">Upload Document</label>
                        <input type="file" name="Doc_upload" class="form-control col-form-label"accept="image/*,.pdf" id="Doc_upload">
                        <small id="fileInfo" class="text-muted"></small>
						<small id="fileError" class="text-danger"></small>
                        </div>
                        @error('Doc_upload')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="active_inactive" class="form-control col-form-label" required>
                                <option value="1" {{ old('active_inactive', '1') == '1' ? 'selected' : '' }}>Active
                                </option>
                                <option value="0" {{ old('active_inactive') == '0' ? 'selected' : '' }}>Inactive
                                </option>
                            </select>
                        </div>
                        @error('active_inactive')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div> --}}
                </div>
                <hr>
                <div class="d-flex flex-wrap justify-content-end gap-2 gap-sm-3 form-actions">
                    <button class="btn btn-success" type="submit">Submit</button>
                    <a href="{{ route('student.medical.exemption.index') }}" class="btn btn-secondary">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2 for student dropdown with search functionality
    $('#studentDropdown').select2({
        placeholder: 'Search Student',
        allowClear: true
    });

    // Course to Student AJAX
    $('#courseDropdown').on('change', function() {
        let courseId = $(this).val();
        $('#studentDropdown').html('<option value="">Loading...</option>');

        if (courseId !== '') {
            $.ajax({
                url: '{{ route("student.medical.exemption.getStudentsByCourse") }}',
                type: 'GET',
                data: { course_id: courseId },
                success: function(response) {
                    let options = '<option value="">Search Student</option>';
                    $.each(response.students, function(index, student) {
                        options += `<option value="${student.pk}" data-ot_code="${student.generated_OT_code}">
                                        ${student.display_name}
                                    </option>`;
                    });
                    $('#studentDropdown').html(options);
                    // Re-initialize Select2 with search functionality
                    $('#studentDropdown').select2('destroy').select2({
                        placeholder: 'Search Student',
                        allowClear: true
                    });
                }
            });
        } else {
            $('#studentDropdown').html('<option value="">Select Course First</option>');
            $('#studentDropdown').select2('destroy').select2({
                placeholder: 'Select Course First',
                allowClear: true
            });
        }
    });

    // Student to OT Code Display
    $('#studentDropdown').on('change', function() {
        let selectedOption = $(this).find('option:selected');
        let otCode = selectedOption.data('ot_code') || '';
        $('#otCodeField').val(otCode);
    });
});
</script>



<script>
    // File upload validation by Dhananjay
document.getElementById('Doc_upload').addEventListener('change', function () {

    const file = this.files[0];
    const fileInfo = document.getElementById('fileInfo');
    const fileError = document.getElementById('fileError');

    fileInfo.innerHTML = '';
    fileError.innerHTML = '';

    if (!file) return;

    const allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf'
    ];

    const maxSize = 3 * 1024 * 1024; // 3MB

    //  File type
    if (!allowedTypes.includes(file.type)) {
        fileError.innerHTML = 'Only image files or PDF are allowed.';
        this.value = '';
        return;
    }

    // Size check
    if (file.size > maxSize) {
        fileError.innerHTML = 'File size must not exceed 3 MB.';
        this.value = '';
        return;
    }

    // Show file info
    const sizeMB = (file.size / (1024 * 1024)).toFixed(2);

    fileInfo.innerHTML = `
        Selected: <strong>${file.name}</strong><br>
        Type: ${file.type}<br>
        Size: ${sizeMB} MB
    `;
});
</script>


@endpush
