@extends('admin.layouts.master')

@section('title', 'Student Medical Exemption - Sargam | Lal Bahadur')

@section('css')
    @parent
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <style>
        .student-medical-create .choices__inner {
            min-height: calc(2.25rem + 2px);
            border-radius: 0.375rem;
            border: 1px solid #ced4da;
            padding: 0.375rem 0.75rem;
            background-color: #fff;
        }

        .student-medical-create .choices__list--single .choices__item {
            padding: 0;
            margin: 0;
        }

        .student-medical-create .choices__list--dropdown {
            border-radius: 0.375rem;
            border-color: #ced4da;
        }

        .student-medical-create .choices.is-focused .choices__inner,
        .student-medical-create .choices.is-open .choices__inner {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>
@endsection

@section('setup_content')
<div class="container-fluid student-medical-create py-2 py-md-3">
    <x-session_message />
    <x-breadcrum title="Add Student Medical Exemption" />

    <div class="card form-card mt-3 shadow-sm border-0 rounded-3">
        <div class="card-header border-0 bg-primary py-3">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                <div>
                    <h5 class="mb-1 fw-semibold text-white">Student Medical Exemption</h5>
                    <p class="mb-0 text-white small">Capture exemption details, timing and supporting documents in one place.</p>
                </div>
            </div>
        </div>
        <div class="card-body pt-3 pt-md-4">
            <form method="POST" action="{{ route('student.medical.exemption.store') }}" enctype="multipart/form-data">
                @csrf

                <p class="section-title text-uppercase small fw-semibold text-muted mb-3">Course &amp; Student</p>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3 mb-0">
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

                <hr class="my-4">
                <p class="section-title text-uppercase small fw-semibold text-muted mb-3">Exemption Details</p>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3 mb-0">
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

                <hr class="my-4">
                <p class="section-title text-uppercase small fw-semibold text-muted mb-3">Date &amp; Time</p>
                <div class="row row-cols-1 row-cols-sm-2 g-3 mb-0">
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

                <hr class="my-4">
                <p class="section-title text-uppercase small fw-semibold text-muted mb-3">Description &amp; Document</p>
                <div class="row row-cols-1 row-cols-lg-2 g-3 mb-0">
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
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
let studentChoicesInstance = null;

function initStudentChoices() {
    if (typeof Choices === 'undefined') return;
    const el = document.getElementById('studentDropdown');
    if (!el) return;

    if (studentChoicesInstance) {
        studentChoicesInstance.destroy();
    }

    studentChoicesInstance = new Choices(el, {
        allowHTML: false,
        searchPlaceholderValue: 'Search Student',
        removeItemButton: false,
        shouldSort: false,
        placeholder: true,
        placeholderValue: 'Search Student',
    });
}

$(document).ready(function() {
    // Initialize Choices on all selects in this form
    if (typeof Choices !== 'undefined') {
        document.querySelectorAll('.student-medical-create select').forEach(function (el) {
            if (el.id === 'studentDropdown') {
                // handled separately
                return;
            }
            if (el.dataset.choicesInitialized === 'true') return;

            new Choices(el, {
                allowHTML: false,
                searchPlaceholderValue: 'Search...',
                removeItemButton: !!el.multiple,
                shouldSort: false,
                placeholder: true,
                placeholderValue: el.getAttribute('placeholder') || el.options[0]?.text || 'Select an option',
            });

            el.dataset.choicesInitialized = 'true';
        });
    }

    // Initialize student dropdown with Choices
    initStudentChoices();

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
                    initStudentChoices();
                }
            });
        } else {
            $('#studentDropdown').html('<option value="">Select Course First</option>');
            initStudentChoices();
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

            initStudentChoices();

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
        fileError.textContent = 'Only image files (jpg, jpeg, png, webp) or PDF are allowed.';
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
