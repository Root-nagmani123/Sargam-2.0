@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:tag-price-bold" class="me-2"></iconify-icon>
            Create Vendor Item Mapping
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.mess.vendor-item-mappings.store') }}" method="POST">
            @csrf
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="vendor_id" class="form-label">Vendor <span class="text-danger">*</span></label>
                        <select class="form-select @error('vendor_id') is-invalid @enderror" 
                                id="vendor_id" name="vendor_id" required>
                            <option value="">-- Select Vendor --</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->vendor_name }} ({{ $vendor->vendor_code }})
                                </option>
                            @endforeach
                        </select>
                        @error('vendor_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="inventory_id" class="form-label">Item <span class="text-danger">*</span></label>
                        <select class="form-select @error('inventory_id') is-invalid @enderror" 
                                id="inventory_id" name="inventory_id" required>
                            <option value="">-- Select Item --</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" {{ old('inventory_id') == $item->id ? 'selected' : '' }}>
                                    {{ $item->item_name }} ({{ $item->item_code ?? 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                        @error('inventory_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="rate" class="form-label">Rate (₹) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('rate') is-invalid @enderror" 
                               id="rate" name="rate" value="{{ old('rate') }}" 
                               step="0.01" min="0" placeholder="0.00" required>
                        @error('rate')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="effective_from" class="form-label">Effective From</label>
                        <input type="date" class="form-control" id="effective_from" name="effective_from" 
                               value="{{ old('effective_from', date('Y-m-d')) }}">
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                           value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">
                        Active
                    </label>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.mess.vendor-item-mappings.index') }}" class="btn btn-secondary">
                    <iconify-icon icon="solar:arrow-left-bold" class="me-1"></iconify-icon>
                    Back
                </a>
                <button type="submit" class="btn btn-primary">
                    <iconify-icon icon="solar:diskette-bold" class="me-1"></iconify-icon>
                    Save Mapping
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
