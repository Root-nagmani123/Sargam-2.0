@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-4">Edit Medical Exemption</h4>

    <form method="POST" action="{{ route('student.medical.exemption.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="id" value="{{ encrypt($record->pk) }}">

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label text-danger">Doctor Name *</label>
                <select name="employee_master_pk" class="form-select" required>
                    <option value="">Select Doctor</option>
                    @foreach($doctors as $doc)
                        <option value="{{ $doc->pk }}" {{ $record->employee_master_pk == $doc->pk ? 'selected' : '' }}>{{ $doc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label text-danger">Course *</label>
                <select name="course_master_pk" class="form-select" required>
                    <option value="">Select Course</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->pk }}" {{ $record->course_master_pk == $course->pk ? 'selected' : '' }}>{{ $course->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label text-danger">Student Name *</label>
                <select name="student_master_pk" class="form-select select2" required>
                    <option value="">Search Student</option>
                    @foreach($students as $student)
                        <option value="{{ $student->pk }}" {{ $record->student_master_pk == $student->pk ? 'selected' : '' }}>
                            {{ $student->full_name }} ({{ $student->reg_no }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label text-danger">Exemption Category *</label>
                <select name="exemption_category_master_pk" class="form-select" required>
                    <option value="">Select Category</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->pk }}" {{ $record->exemption_category_master_pk == $cat->pk ? 'selected' : '' }}>{{ $cat->exemp_category_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">OPD Category</label>
                <select name="opd_category" class="form-select">
                    <option value="">Select Type</option>
                    @foreach(['OPD', 'Referred', 'IPD', 'Other'] as $type)
                        <option value="{{ $type }}" {{ $record->opd_category == $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label text-danger">Medical Speciality *</label>
                <select name="exemption_medical_speciality_pk" class="form-select" required>
                    <option value="">Select Speciality</option>
                    @foreach($specialities as $spec)
                        <option value="{{ $spec->pk }}" {{ $record->exemption_medical_speciality_pk == $spec->pk ? 'selected' : '' }}>{{ $spec->speciality_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label text-danger">From Date *</label>
                <input type="date" name="from_date" class="form-control" value="{{ $record->from_date }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">To Date</label>
                <input type="date" name="to_date" class="form-control" value="{{ $record->to_date }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Description</label>
                <textarea name="Description" class="form-control">{{ $record->Description }}</textarea>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Upload Document</label>
                <input type="file" name="Doc_upload" class="form-control">
                @if(!empty($record->Doc_upload))
                    <a href="{{ asset('storage/'.$record->Doc_upload) }}" target="_blank" class="d-block mt-1">View uploaded</a>
                @endif
            </div>
            <div class="col-md-6">
                <label class="form-label text-danger">Status *</label>
                <select name="active_inactive" class="form-select" required>
                    <option value="1" {{ $record->active_inactive == 1 ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ $record->active_inactive == 0 ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>

        <button class="btn btn-success" type="submit">Update</button>
        <a href="{{ route('student.medical.exemption.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>
@endsection
