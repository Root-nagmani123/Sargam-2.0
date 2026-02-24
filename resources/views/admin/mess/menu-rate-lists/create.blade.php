@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:clipboard-list-bold" class="me-2"></iconify-icon>
            Create Menu Rate
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.mess.menu-rate-lists.store') }}" method="POST">
            @csrf
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="menu_item_name" class="form-label">Menu Item Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('menu_item_name') is-invalid @enderror" 
                               id="menu_item_name" name="menu_item_name" value="{{ old('menu_item_name') }}" 
                               placeholder="e.g., Breakfast - Poha" required>
                        @error('menu_item_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="inventory_id" class="form-label">Inventory Item (Optional)</label>
                        <select class="form-select select2 @error('inventory_id') is-invalid @enderror" 
                                id="inventory_id" name="inventory_id">
                            <option value="">-- Select Inventory Item --</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" {{ old('inventory_id') == $item->id ? 'selected' : '' }}>
                                    {{ $item->item_name }} ({{ $item->item_code ?? 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                        @error('inventory_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Link to inventory item if applicable</small>
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

                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="effective_from" class="form-label">Effective From <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('effective_from') is-invalid @enderror" 
                               id="effective_from" name="effective_from" 
                               value="{{ old('effective_from', date('Y-m-d')) }}" required>
                        @error('effective_from')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="effective_to" class="form-label">Effective To</label>
                        <input type="date" class="form-control @error('effective_to') is-invalid @enderror" 
                               id="effective_to" name="effective_to" value="{{ old('effective_to') }}">
                        @error('effective_to')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Leave blank for no end date</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" 
                                   name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                        <small class="text-muted">Only active menu rates will be available for selection</small>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.mess.menu-rate-lists.index') }}" class="btn btn-secondary">
                    <iconify-icon icon="solar:arrow-left-bold" class="me-1"></iconify-icon>
                    Back
                </a>
                <button type="submit" class="btn btn-primary">
                    <iconify-icon icon="solar:diskette-bold" class="me-1"></iconify-icon>
                    Save Menu Rate
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
