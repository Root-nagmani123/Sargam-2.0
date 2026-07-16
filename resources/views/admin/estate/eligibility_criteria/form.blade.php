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
                    <label for="salary_grade_master_pk" class="form-label">Salary Grade / Pay Scale <span class="text-danger">*</span></label>
                    <select class="form-select @error('salary_grade_master_pk') is-invalid @enderror" id="salary_grade_master_pk" name="salary_grade_master_pk" required>
                        <option value="">--select--</option>
                        @foreach($payScales as $pk => $label)
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

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<style>
    .select2-container--open { z-index: 1060; } /* sirf khula dropdown modal ke upar; closed widget normal flow me (modal ke peeche) */
    .select2-container--default .select2-selection--single { min-height: calc(1.5em + 0.75rem + 2px); display: flex; align-items: center; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 1.5; padding-left: 0.25rem; }
</style>
@endpush

@push('scripts')
{{-- Select2 JS globally footer (admin.layouts.footer) se load hoti hai; yahan include ki zaroorat nahi. --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $.fn.select2 === 'undefined') return;
    var ids = [
        'salary_grade_master_pk',
        'estate_unit_type_master_pk',
        'estate_unit_sub_type_master_pk'
    ];
    ids.forEach(function(id) {
        var el = document.getElementById(id);
        if (!el) return;
        if ($(el).data('select2')) { try { $(el).select2('destroy'); } catch (e) {} }
        $(el).select2({ placeholder: '--select--', allowClear: false, width: '100%' });
    });
});
</script>
@endpush
