@extends('admin.layouts.master')

@section('title', 'Edit Vendor Mapping - Sargam | Lal Bahadur')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:tag-price-bold" class="me-2"></iconify-icon>
            Edit Vendor Mapping
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.mess.vendor-item-mappings.update', $mapping->id) }}" method="POST" id="editVendorMappingForm">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="vendor_id" class="form-label">Vendor Name <span class="text-danger">*</span></label>
                <select class="form-select @error('vendor_id') is-invalid @enderror"
                        id="vendor_id" name="vendor_id" required>
                    <option value="">Please Select Vendor Name</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}"
                                {{ (old('vendor_id', $mapping->vendor_id) == $vendor->id ? 'selected' : '' }}>
                            {{ $vendor->vendor_name ?? $vendor->name ?? 'Vendor #'.$vendor->id }}
                        </option>
                    @endforeach
                </select>
                @error('vendor_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            @php($editItemIds = $mapping->mapping_type === \App\Models\Mess\VendorItemMapping::MAPPING_TYPE_ITEM_SUB_CATEGORY && $mapping->item_subcategory_id ? [$mapping->item_subcategory_id] : [])
            <div class="mb-3" id="wrap_items">
                <label class="form-label">Item <span class="text-danger">*</span></label>
                <div class="border rounded p-3" style="max-height: 220px; overflow-y: auto;">
                    @foreach($itemSubcategories as $sub)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="item_subcategory_ids[]"
                                   id="item_{{ $sub->id }}" value="{{ $sub->id }}"
                                   {{ in_array($sub->id, old('item_subcategory_ids', $editItemIds)) ? 'checked' : '' }}>
                            <label class="form-check-label" for="item_{{ $sub->id }}">{{ $sub->item_name ?? $sub->subcategory_name ?? 'Item #'.$sub->id }}</label>
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
                    Update
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
