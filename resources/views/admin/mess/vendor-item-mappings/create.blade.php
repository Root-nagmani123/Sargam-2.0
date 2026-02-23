@extends('admin.layouts.master')

<<<<<<< HEAD
@section('title', 'Add Vendor Mapping - Sargam | Lal Bahadur')

=======
>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)
@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:tag-price-bold" class="me-2"></iconify-icon>
<<<<<<< HEAD
            Add Vendor Mapping
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.mess.vendor-item-mappings.store') }}" method="POST" id="createVendorMappingForm">
            @csrf

            <div class="mb-3">
                <label for="vendor_id" class="form-label">Vendor Name <span class="text-danger">*</span></label>
                <select class="form-select select2 @error('vendor_id') is-invalid @enderror"
                        id="vendor_id" name="vendor_id" required>
                    <option value="">Please Select Vendor Name</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                            {{ $vendor->vendor_name ?? $vendor->name ?? 'Vendor #'.$vendor->id }}
                        </option>
                    @endforeach
                </select>
                @error('vendor_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3" id="wrap_items">
                <label class="form-label">Item <span class="text-danger">*</span></label>
                <div class="border rounded p-3" style="max-height: 220px; overflow-y: auto;">
                    @foreach($itemSubcategories as $sub)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="item_subcategory_ids[]"
                                   id="item_{{ $sub->id }}" value="{{ $sub->id }}"
                                   {{ in_array($sub->id, old('item_subcategory_ids', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="item_{{ $sub->id }}">
                                {{ $sub->item_name ?? $sub->subcategory_name ?? 'Item #'.$sub->id }}
                                <span class="text-muted small">({{ $sub->item_code ?? '—' }}) — {{ $sub->unit_measurement ?? '—' }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
                @error('item_subcategory_ids')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
=======
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
>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.mess.vendor-item-mappings.index') }}" class="btn btn-secondary">
                    <iconify-icon icon="solar:arrow-left-bold" class="me-1"></iconify-icon>
                    Back
                </a>
                <button type="submit" class="btn btn-primary">
                    <iconify-icon icon="solar:diskette-bold" class="me-1"></iconify-icon>
<<<<<<< HEAD
                    Save
=======
                    Save Mapping
>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)
                </button>
            </div>
        </form>
    </div>
</div>
<<<<<<< HEAD
<<<<<<< HEAD

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var wrapCategories = document.getElementById('wrap_item_categories');
    var wrapSubcategories = document.getElementById('wrap_item_subcategories');
    var radios = document.querySelectorAll('.mapping-type-radio');

    function toggleMappingFields() {
        var typeCategory = document.getElementById('mapping_type_category').checked;
        if (typeCategory) {
            wrapCategories.classList.remove('d-none');
            wrapSubcategories.classList.add('d-none');
            wrapSubcategories.querySelectorAll('input[type="checkbox"]').forEach(function(cb) { cb.checked = false; });
        } else {
            wrapCategories.classList.add('d-none');
            wrapSubcategories.classList.remove('d-none');
            wrapCategories.querySelectorAll('input[type="checkbox"]').forEach(function(cb) { cb.checked = false; });
        }
    }

    radios.forEach(function(r) {
        r.addEventListener('change', toggleMappingFields);
    });
    toggleMappingFields();
});
</script>
@endpush
=======
>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)
=======
>>>>>>> 6ae073ee (remove category and subcategory convert item master)
@endsection
