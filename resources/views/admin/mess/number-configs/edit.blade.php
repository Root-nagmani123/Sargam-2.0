@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:settings-bold" class="me-2"></iconify-icon>
            Edit Number Configuration
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.mess.number-configs.update', $config->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="config_type" class="form-label">Configuration Type</label>
                        <input type="text" class="form-control" id="config_type" 
                               value="{{ $config->config_type }}" disabled>
                        <small class="text-muted">Configuration type cannot be changed</small>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="prefix" class="form-label">Prefix <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('prefix') is-invalid @enderror" 
                               id="prefix" name="prefix" value="{{ old('prefix', $config->prefix) }}" 
                               placeholder="e.g., PO, INV, BILL" maxlength="10" required>
                        @error('prefix')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Text prefix for the number (max 10 characters)</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="current_number" class="form-label">Current Number <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('current_number') is-invalid @enderror" 
                               id="current_number" name="current_number" 
                               value="{{ old('current_number', $config->current_number) }}" 
                               min="0" required>
                        @error('current_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">The last used number in the sequence</small>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="padding" class="form-label">Number Padding <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('padding') is-invalid @enderror" 
                               id="padding" name="padding" value="{{ old('padding', $config->padding) }}" 
                               min="1" max="10" required>
                        @error('padding')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Number of digits (e.g., 4 = 0001, 0002)</small>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <div class="alert alert-info">
                    <strong>Next Number Preview:</strong> 
                    <span id="preview">
                        <span class="prefix-preview">{{ $config->prefix }}</span>-<span class="number-preview">{{ str_pad($config->current_number + 1, $config->padding, '0', STR_PAD_LEFT) }}</span>
                    </span>
                </div>
            </div>

            <div class="mb-3">
                <label for="sample_format" class="form-label">Sample Format (Optional)</label>
                <input type="text" class="form-control" id="sample_format" name="sample_format" 
                       value="{{ old('sample_format', $config->sample_format) }}" 
                       placeholder="e.g., PO-YYYY-0001">
                <small class="text-muted">Example format for reference</small>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.mess.number-configs.index') }}" class="btn btn-secondary">
                    <iconify-icon icon="solar:arrow-left-bold" class="me-1"></iconify-icon>
                    Back
                </a>
                <button type="submit" class="btn btn-primary">
                    <iconify-icon icon="solar:diskette-bold" class="me-1"></iconify-icon>
                    Update Configuration
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const prefixInput = document.getElementById('prefix');
    const paddingInput = document.getElementById('padding');
    const currentNumberInput = document.getElementById('current_number');
    const prefixPreview = document.querySelector('.prefix-preview');
    const numberPreview = document.querySelector('.number-preview');

    function updatePreview() {
        const prefix = prefixInput.value || 'PREFIX';
        const padding = parseInt(paddingInput.value) || 4;
        const currentNumber = parseInt(currentNumberInput.value) || 0;
        const nextNumber = (currentNumber + 1).toString().padStart(padding, '0');
        
        prefixPreview.textContent = prefix;
        numberPreview.textContent = nextNumber;
    }

    prefixInput.addEventListener('input', updatePreview);
    paddingInput.addEventListener('input', updatePreview);
    currentNumberInput.addEventListener('input', updatePreview);
    
    updatePreview();
});
</script>
@endsection
