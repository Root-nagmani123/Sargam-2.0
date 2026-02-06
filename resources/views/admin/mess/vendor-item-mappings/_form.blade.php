@php($mapping = $mapping ?? null)
@php($isEdit = !empty($mapping))
@php($editCategoryIds = $isEdit && $mapping->mapping_type === \App\Models\Mess\VendorItemMapping::MAPPING_TYPE_ITEM_CATEGORY && $mapping->item_category_id ? [$mapping->item_category_id] : [])
@php($editSubcategoryIds = $isEdit && $mapping->mapping_type === \App\Models\Mess\VendorItemMapping::MAPPING_TYPE_ITEM_SUB_CATEGORY && $mapping->item_subcategory_id ? [$mapping->item_subcategory_id] : [])
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

    <div class="mb-3">
        <label class="form-label">Mapping Type <span class="text-danger">*</span></label>
        <div class="d-flex gap-4">
            <div class="form-check">
                <input class="form-check-input mapping-type-radio" type="radio" name="mapping_type"
                       id="mapping_type_category" value="item_category"
                       {{ old('mapping_type', $mapping->mapping_type ?? 'item_category') === 'item_category' ? 'checked' : '' }}>
                <label class="form-check-label" for="mapping_type_category">Item Category</label>
            </div>
            <div class="form-check">
                <input class="form-check-input mapping-type-radio" type="radio" name="mapping_type"
                       id="mapping_type_subcategory" value="item_sub_category"
                       {{ old('mapping_type', $mapping->mapping_type ?? '') === 'item_sub_category' ? 'checked' : '' }}>
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
                    <input class="form-check-input" type="checkbox" name="item_category_ids[]" id="cat_{{ $cat->id }}" value="{{ $cat->id }}"
                           {{ in_array($cat->id, old('item_category_ids', $editCategoryIds)) ? 'checked' : '' }}>
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
                    <input class="form-check-input" type="checkbox" name="item_subcategory_ids[]" id="sub_{{ $sub->id }}" value="{{ $sub->id }}"
                           {{ in_array($sub->id, old('item_subcategory_ids', $editSubcategoryIds)) ? 'checked' : '' }}>
                    <label class="form-check-label" for="sub_{{ $sub->id }}">{{ $sub->item_name ?? $sub->subcategory_name ?? 'Item #'.$sub->id }}</label>
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
