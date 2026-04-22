@extends('admin.layouts.master')
@section('title', 'Create New Form')

@section('setup_content')
<div class="container py-4" style="max-width:750px;">
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('fc-reg.admin.forms.index') }}" class="btn btn-sm btn-outline-secondary me-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h4 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Create New Form</h4>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:10px;">
        <div class="card-body">
            <form method="POST" action="{{ route('fc-reg.admin.forms.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">Form Name <span class="text-danger">*</span></label>
                    <input type="text" name="form_name" class="form-control @error('form_name') is-invalid @enderror"
                           value="{{ old('form_name') }}" required placeholder="e.g. Phase 2 Training Feedback">
                    @error('form_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Form Slug <span class="text-danger">*</span></label>
                    <input type="text" name="form_slug" class="form-control @error('form_slug') is-invalid @enderror"
                           value="{{ old('form_slug') }}" required placeholder="e.g. phase2-training-feedback">
                    <small class="text-muted">URL-friendly identifier. Only lowercase letters, numbers, and hyphens.</small>
                    @error('form_slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                              rows="2" placeholder="Brief description of this form">{{ old('description') }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Clone Steps From</label>
                    <select name="source_form_id" class="form-select @error('source_form_id') is-invalid @enderror">
                        <option value="">-- Start with empty form --</option>
                        @foreach($sourceForms as $sf)
                            <option value="{{ $sf->id }}" {{ (int)old('source_form_id') === $sf->id ? 'selected' : '' }}>
                                {{ $sf->form_name }} ({{ $sf->steps_count }} steps)
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">
                        Copy all steps (same tables, same structure) from an existing form. You can then customize labels and add new fields.
                    </small>
                    @error('source_form_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Icon Class</label>
                        <input type="text" name="icon" class="form-control @error('icon') is-invalid @enderror"
                               value="{{ old('icon', 'bi-file-text') }}" placeholder="bi-file-text">
                        <small class="text-muted">Bootstrap Icons class name</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">User Identifier Column</label>
                        <input type="text" name="user_identifier" class="form-control @error('user_identifier') is-invalid @enderror"
                               value="{{ old('user_identifier', 'username') }}" placeholder="username">
                        <small class="text-muted">Column name used to identify user rows in target tables</small>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Consolidation / Tracking Table</label>
                    <select name="consolidation_table" class="form-select @error('consolidation_table') is-invalid @enderror">
                        <option value="">-- None (no step tracking) --</option>
                        @foreach($tables as $table)
                            <option value="{{ $table }}" {{ old('consolidation_table') === $table ? 'selected' : '' }}>{{ $table }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">
                        Existing table to track step completion. Leave blank if not needed.
                    </small>
                    @error('consolidation_table') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('fc-reg.admin.forms.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Create Form
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Auto-generate slug from form name
document.querySelector('[name="form_name"]').addEventListener('input', function() {
    const slugInput = document.querySelector('[name="form_slug"]');
    if (!slugInput.dataset.manual) {
        slugInput.value = this.value.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
    }
});
document.querySelector('[name="form_slug"]').addEventListener('input', function() {
    this.dataset.manual = '1';
});
</script>
@endpush
@endsection
