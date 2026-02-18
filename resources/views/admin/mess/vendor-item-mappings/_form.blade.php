@php($mapping = $mapping ?? null)
@php($isEdit = !empty($mapping))
@php($editItemIds = $isEdit && $mapping->mapping_type === \App\Models\Mess\VendorItemMapping::MAPPING_TYPE_ITEM_SUB_CATEGORY && $mapping->item_subcategory_id ? [$mapping->item_subcategory_id] : [])
<form method="POST" action="{{ $isEdit ? route('admin.mess.vendor-item-mappings.update', $mapping->id) : route('admin.mess.vendor-item-mappings.store') }}" id="vendorMappingForm">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="mb-3">
        <label for="vendor_id" class="form-label">Vendor Name <span class="text-danger">*</span></label>
        <select class="form-select @error('vendor_id') is-invalid @enderror" id="vendor_id" name="vendor_id" required>
            <option value="">Please Select Vendor Name</option>
            @foreach($vendors as $vendor)
                <option value="{{ $vendor->id }}" {{ old('vendor_id', $mapping->vendor_id ?? null) == $vendor->id ? 'selected' : '' }}>
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
                    <input class="form-check-input" type="checkbox" name="item_subcategory_ids[]" id="item_{{ $sub->id }}" value="{{ $sub->id }}"
                           {{ in_array($sub->id, old('item_subcategory_ids', $editItemIds)) ? 'checked' : '' }}>
                    <label class="form-check-label" for="item_{{ $sub->id }}">{{ $sub->item_name ?? $sub->subcategory_name ?? 'Item #'.$sub->id }}</label>
                </div>
            @endforeach
        </div>
        @error('item_subcategory_ids')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update' : 'Save' }}</button>
    </div>
</form>
