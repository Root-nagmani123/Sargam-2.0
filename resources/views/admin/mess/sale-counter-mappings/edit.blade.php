@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:layers-minimalistic-bold" class="me-2"></iconify-icon>
            Edit Sale Counter Mapping
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.mess.sale-counter-mappings.update', $mapping->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="sale_counter_id" class="form-label">Sale Counter <span class="text-danger">*</span></label>
                        <select class="form-select select2 @error('sale_counter_id') is-invalid @enderror" 
                                id="sale_counter_id" name="sale_counter_id" required>
                            <option value="">-- Select Sale Counter --</option>
                            @foreach($counters as $counter)
                                <option value="{{ $counter->id }}" 
                                    {{ (old('sale_counter_id', $mapping->sale_counter_id) == $counter->id) ? 'selected' : '' }}>
                                    {{ $counter->counter_name }} ({{ $counter->counter_code }})
                                </option>
                            @endforeach
                        </select>
                        @error('sale_counter_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="inventory_id" class="form-label">Item <span class="text-danger">*</span></label>
                        <select class="form-select select2 @error('inventory_id') is-invalid @enderror" 
                                id="inventory_id" name="inventory_id" required>
                            <option value="">-- Select Item --</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" 
                                    {{ (old('inventory_id', $mapping->inventory_id) == $item->id) ? 'selected' : '' }}>
                                    {{ $item->item_name }} @if($item->item_code)({{ $item->item_code }})@endif
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
                        <label for="available_quantity" class="form-label">Available Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('available_quantity') is-invalid @enderror" 
                               id="available_quantity" name="available_quantity" 
                               value="{{ old('available_quantity', $mapping->available_quantity) }}" 
                               min="0" placeholder="0" required>
                        @error('available_quantity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                           value="1" {{ old('is_active', $mapping->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">
                        Active
                    </label>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.mess.sale-counter-mappings.index') }}" class="btn btn-secondary">
                    <iconify-icon icon="solar:arrow-left-bold" class="me-1"></iconify-icon>
                    Back
                </a>
                <button type="submit" class="btn btn-primary">
                    <iconify-icon icon="solar:diskette-bold" class="me-1"></iconify-icon>
                    Update Mapping
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
