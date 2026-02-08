@extends('admin.layouts.master')

@section('title', 'Add Vendor Mapping - Sargam | Lal Bahadur')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:tag-price-bold" class="me-2"></iconify-icon>
            Add Vendor Mapping
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.mess.vendor-item-mappings.store') }}" method="POST" id="createVendorMappingForm">
            @csrf

            <div class="mb-3">
                <label for="vendor_id" class="form-label">Vendor Name <span class="text-danger">*</span></label>
                <select class="form-select @error('vendor_id') is-invalid @enderror"
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

            <div class="mb-3">
                <label class="form-label">Mapping Type <span class="text-danger">*</span></label>
                <div class="d-flex gap-4">
                    <div class="form-check">
                        <input class="form-check-input mapping-type-radio" type="radio" name="mapping_type"
                               id="mapping_type_category" value="item_category"
                               {{ old('mapping_type', 'item_category') === 'item_category' ? 'checked' : '' }}>
                        <label class="form-check-label" for="mapping_type_category">Item Category</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input mapping-type-radio" type="radio" name="mapping_type"
                               id="mapping_type_subcategory" value="item_sub_category"
                               {{ old('mapping_type') === 'item_sub_category' ? 'checked' : '' }}>
                        <label class="form-check-label" for="mapping_type_subcategory">Item Sub Category</label>
                    </div>
                </div>
                @error('mapping_type')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3" id="wrap_item_categories">
                <label class="form-label">Item Category <span class="text-danger">*</span></label>
                <div class="border rounded p-3" style="max-height: 220px; overflow-y: auto;">
                    @foreach($itemCategories as $cat)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="item_category_ids[]"
                                   id="cat_{{ $cat->id }}" value="{{ $cat->id }}"
                                   {{ in_array($cat->id, old('item_category_ids', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="cat_{{ $cat->id }}">{{ $cat->category_name }}</label>
                        </div>
                    @endforeach
                </div>
                @error('item_category_ids')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3 d-none" id="wrap_item_subcategories">
                <label class="form-label">Item Sub Category <span class="text-danger">*</span></label>
                <div class="border rounded p-3" style="max-height: 220px; overflow-y: auto;">
                    @foreach($itemSubcategories as $sub)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="item_subcategory_ids[]"
                                   id="sub_{{ $sub->id }}" value="{{ $sub->id }}"
                                   {{ in_array($sub->id, old('item_subcategory_ids', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="sub_{{ $sub->id }}">{{ $sub->item_name ?? $sub->subcategory_name ?? 'Item #'.$sub->id }}</label>
                        </div>
                    @endforeach
                </div>
                @error('item_subcategory_ids')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.mess.vendor-item-mappings.index') }}" class="btn btn-secondary">
                    <iconify-icon icon="solar:arrow-left-bold" class="me-1"></iconify-icon>
                    Back
                </a>
                <button type="submit" class="btn btn-primary">
                    <iconify-icon icon="solar:diskette-bold" class="me-1"></iconify-icon>
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

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
@endsection
