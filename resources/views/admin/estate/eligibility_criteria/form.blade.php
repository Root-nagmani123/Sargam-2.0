@extends('admin.layouts.master')

@section('title', ($item ? 'Edit' : 'Add') . ' Eligibility Unit Mapping - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Protocol</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.request-for-others') }}">Estate Management</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.eligibility-criteria.index') }}">Eligibility - Criteria</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $item ? 'Edit' : 'Add' }} Eligibility Unit Mapping</li>
        </ol>
    </nav>

    <x-session_message />

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h1 class="h4 fw-bold mb-1">{{ $item ? 'Edit Eligibility Unit Mapping' : 'Add Eligibility Unit Mapping' }}</h1>
            <p class="text-muted small mb-4">{{ $item ? 'Update mapping.' : 'Please Add Eligibility Unit Mapping.' }}</p>
            <hr class="my-4">

            <form action="{{ $item ? route('admin.estate.eligibility-criteria.update', $item->pk) : route('admin.estate.eligibility-criteria.store') }}" method="POST">
                @csrf
                @if($item) @method('PUT') @endif

                <div class="mb-3">
                    <label for="salary_grade_master_pk" class="form-label">Pay Scale (Salary Grade) <span class="text-danger">*</span></label>
                    <select class="form-select @error('salary_grade_master_pk') is-invalid @enderror" id="salary_grade_master_pk" name="salary_grade_master_pk" required>
                        <option value="">--select--</option>
                        @foreach($salaryGrades as $pk => $label)
                        <option value="{{ $pk }}" {{ old('salary_grade_master_pk', $item->salary_grade_master_pk ?? '') == $pk ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('salary_grade_master_pk')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="estate_unit_type_master_pk" class="form-label">Unit Type <span class="text-danger">*</span></label>
                    <select class="form-select @error('estate_unit_type_master_pk') is-invalid @enderror" id="estate_unit_type_master_pk" name="estate_unit_type_master_pk" required>
                        <option value="">--select--</option>
                        @foreach($unitTypes as $pk => $name)
                        <option value="{{ $pk }}" {{ old('estate_unit_type_master_pk', $item->estate_unit_type_master_pk ?? '') == $pk ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('estate_unit_type_master_pk')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-4">
                    <label for="estate_unit_sub_type_master_pk" class="form-label">Unit Sub type <span class="text-danger">*</span></label>
                    <select class="form-select @error('estate_unit_sub_type_master_pk') is-invalid @enderror" id="estate_unit_sub_type_master_pk" name="estate_unit_sub_type_master_pk" required>
                        <option value="">--select--</option>
                        @foreach($unitSubTypes as $pk => $name)
                        <option value="{{ $pk }}" {{ old('estate_unit_sub_type_master_pk', $item->estate_unit_sub_type_master_pk ?? '') == $pk ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('estate_unit_sub_type_master_pk')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i> Save</button>
                    <a href="{{ route('admin.estate.eligibility-criteria.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
