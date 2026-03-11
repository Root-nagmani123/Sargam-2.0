@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:users-group-rounded-bold" class="me-2"></iconify-icon>
            Edit Client Type
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.mess.client-types.update', $clientType->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="type_name" class="form-label">Type Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('type_name') is-invalid @enderror" 
                               id="type_name" name="type_name" 
                               value="{{ old('type_name', $clientType->type_name) }}" 
                               placeholder="e.g., Officer, Student, Guest" required>
                        @error('type_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="type_code" class="form-label">Type Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('type_code') is-invalid @enderror" 
                               id="type_code" name="type_code" 
                               value="{{ old('type_code', $clientType->type_code) }}" 
                               placeholder="e.g., OFC, STD, GST" required>
                        @error('type_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Unique code for this client type</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="default_credit_limit" class="form-label">Default Credit Limit (₹) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('default_credit_limit') is-invalid @enderror" 
                               id="default_credit_limit" name="default_credit_limit" 
                               value="{{ old('default_credit_limit', $clientType->default_credit_limit) }}" 
                               step="0.01" min="0" required>
                        @error('default_credit_limit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Default credit limit assigned to this client type</small>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="priority_level" class="form-label">Priority Level</label>
                        <input type="number" class="form-control" id="priority_level" name="priority_level" 
                               value="{{ old('priority_level', $clientType->priority_level) }}" min="0">
                        <small class="text-muted">Higher number = higher priority (optional)</small>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3" 
                          placeholder="Enter description for this client type">{{ old('description', $clientType->description) }}</textarea>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                           value="1" {{ old('is_active', $clientType->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">
                        Active
                    </label>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.mess.client-types.index') }}" class="btn btn-secondary">
                    <iconify-icon icon="solar:arrow-left-bold" class="me-1"></iconify-icon>
                    Back
                </a>
                <button type="submit" class="btn btn-primary">
                    <iconify-icon icon="solar:diskette-bold" class="me-1"></iconify-icon>
                    Update Client Type
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
