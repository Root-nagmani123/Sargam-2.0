@extends('admin.layouts.master')

@section('title', 'Add Other Estate Request - Sargam')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-breadcrum title="Add Other Estate Request"></x-breadcrum>

    <!-- Page Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">{{ isset($record) && $record ? 'Edit' : 'Add' }} Other Estate Request</h2>
        <a href="{{ route('admin.estate.request-for-others') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to List
        </a>
    </div>

    <!-- Form Card -->
    <div class="card shadow-sm">
        <div class="card-body">
            <p class="text-muted mb-4">Please Other Estate Request</p>
            <hr class="mb-4">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.estate.add-other-estate-request.store') }}">
                @csrf
                @if(isset($record) && $record)
                    <input type="hidden" name="id" value="{{ $record->pk }}">
                @endif
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="employee_name" class="form-label">Employee Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="employee_name" name="employee_name" value="{{ old('employee_name', $prefill['employee_name'] ?? '') }}" required maxlength="500">
                        <div class="text-danger small field-error" data-field="employee_name" role="alert">@error('employee_name'){{ $message }}@enderror</div>
                    </div>
                    <div class="col-md-6">
                        <label for="father_name" class="form-label">Father Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control other-estate-no-special" id="father_name" name="father_name" value="{{ old('father_name', $prefill['father_name'] ?? '') }}" required maxlength="500" title="Only letters, numbers, spaces, hyphen, apostrophe and dot are allowed.">
                        <div class="text-danger small field-error" data-field="father_name" role="alert">@error('father_name'){{ $message }}@enderror</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="section" class="form-label">Section <span class="text-danger">*</span></label>
                        <input type="text" class="form-control other-estate-no-special" id="section" name="section" value="{{ old('section', $prefill['section'] ?? '') }}" required maxlength="500" title="Only letters, numbers, spaces, hyphen, apostrophe and dot are allowed.">
                        <div class="text-danger small field-error" data-field="section" role="alert">@error('section'){{ $message }}@enderror</div>
                    </div>
                    <div class="col-md-6">
                        <label for="designation" class="form-label">Designation</label>
                        <input type="text" class="form-control other-estate-no-special" id="designation" name="designation" value="{{ old('designation', isset($record) ? ($record->designation ?? '') : ($prefill['designation'] ?? '')) }}" maxlength="500" placeholder="e.g. Section Officer" title="Only letters, numbers, spaces, hyphen, apostrophe and dot are allowed.">
                        <div class="text-danger small field-error" data-field="designation" role="alert">@error('designation'){{ $message }}@enderror</div>
                    </div>
                </div>
                <div class="row mb-3">
                        <div class="col-md-6">
                        <label for="doj_academy" class="form-label">DOJ in Academy <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="doj_academy" name="doj_academy" value="{{ old('doj_academy', $prefill['doj_academy'] ?? '') }}" required min="1950-01-01" max="{{ date('Y-m-d') }}" title="Date must be between 01-01-1950 and today.">
                        <small class="text-muted d-block mt-1">Date must be between 01-01-1950 and today.</small>
                        <div class="text-danger small field-error" data-field="doj_academy" role="alert">@error('doj_academy'){{ $message }}@enderror</div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-2"></i>Save
                    </button>
                    <a href="{{ route('admin.estate.request-for-others') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Allow only letters (any language), numbers, space, hyphen, apostrophe, dot (no special characters)
    var stripRegex = /[^\p{L}\p{N}\s.\-' ]/gu;
    document.querySelectorAll('.other-estate-no-special').forEach(function(el) {
        el.addEventListener('input', function() {
            var val = this.value;
            var cleaned = val.replace(stripRegex, '');
            if (cleaned !== val) this.value = cleaned;
        });
    });
});
</script>
@endpush
