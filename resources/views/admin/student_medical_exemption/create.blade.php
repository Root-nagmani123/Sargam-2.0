@extends('admin.layouts.master')

@section('title', 'Student Medical Exemption - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <x-session_message />
    <div class="card card-body py-3" style="border-left:4px solid #004a93;">
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

    <div class="card mt-3" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <h4 class="card-title mb-3">Student Medical Exemption</h4>
            <hr>
            <form method="POST" action="{{ route('student.medical.exemption.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Doctor Name <span class="text-danger">*</span></label>
                            <select name="employee_master_pk" class="form-control col-form-label" readonly required>
                                <option value="1" selected>XYZ</option>
                            </select>
                        </div>

                        @error('employee_master_pk')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Course <span class="text-danger">*</span></label>
                            <select name="course_master_pk" class="form-control col-form-label" required>
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

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Student Name <span class="text-danger">*</span></label>
                            <select name="student_master_pk" class="form-control select2" id="studentDropdown" required>
                                <option value="">Search Student</option>
                                @foreach($students as $student)
                                <option value="{{ $student->pk }}" data-ot_code="{{ $student->generated_OT_code }}"
                                    {{ old('student_master_pk') == $student->pk ? 'selected' : '' }}>
                                    {{ $student->display_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @error('student_master_pk')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">OT Code</label>
                            <input type="text" class="form-control" name="ot_code" id="otCodeField" readonly>
                        </div>
                    </div>

                    <div class="col-md-6">
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

                    <div class="col-md-6">
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

                    <div class="col-md-6">
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

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">From Date <span class="text-danger">*</span></label>
                            <input type="date" name="from_date" class="form-control col-form-label" required
                                value="{{ old('from_date') }}">
                        </div>
                        @error('from_date')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" name="to_date" class="form-control col-form-label"
                                value="{{ old('to_date') }}">
                        </div>
                        @error('to_date')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="Description" class="form-control col-form-label"
                                rows="2">{{ old('Description') }}</textarea>
                        </div>
                        @error('Description')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Upload Document</label>
                            <input type="file" name="Doc_upload" class="form-control col-form-label">
                        </div>
                        @error('Doc_upload')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6">
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
                    </div>
                </div>
                <hr>
                <div class="text-end gap-3">
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
    $('#studentDropdown').on('change', function() {
        var otCode = $(this).find(':selected').data('ot_code') || '';
        $('#otCodeField').val(otCode);
    });
});
</script>
@endpush