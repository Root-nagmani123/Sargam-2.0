@php
    /** @var \App\Models\Mess\ItemSubcategory|null $itemsubcategory */
    $itemsubcategory = $itemsubcategory ?? null;
    $categories = $categories ?? collect();
    $oldCategoryId = old('category_id', $itemsubcategory ? $itemsubcategory->category_id : '');
    $oldItemName = old('item_name', $itemsubcategory ? $itemsubcategory->item_name : '');
    $oldUnitMeasurement = old('unit_measurement', $itemsubcategory ? $itemsubcategory->unit_measurement : '');
    $oldDesc = old('description', $itemsubcategory ? $itemsubcategory->description : '');
    $oldStatus = old('status', $itemsubcategory ? ($itemsubcategory->status ?? 'active') : 'active');
@endphp

<div class="row">
    <div class="col-md-12 mb-3">
        <label class="form-label">Category <span class="text-danger">*</span></label>
        <select name="category_id" class="form-select select2" required>
            <option value="">Select Category</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ (string)$oldCategoryId === (string)$cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>
            @endforeach
        </select>
        @error('category_id')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-3">
        <label class="form-label">Item Name <span class="text-danger">*</span></label>
        <input type="text" name="item_name" class="form-control" required value="{{ $oldItemName }}">
        @error('item_name')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Item Code <span class="text-danger">*</span></label>
        <input type="text" name="item_code" class="form-control bg-light" value="{{ old('item_code', $itemsubcategory ? $itemsubcategory->item_code : '') }}" readonly placeholder="Auto-generated on save">
        <div class="text-muted small">Mandatory. Auto-generated.</div>
        @error('item_code')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Unit Measurement <span class="text-danger">*</span></label>
        <input type="text" name="unit_measurement" class="form-control" value="{{ $oldUnitMeasurement }}" placeholder="e.g., kg, liter, piece" required>
        @error('unit_measurement')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select select2">
            <option value="active" {{ $oldStatus === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ $oldStatus === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <div class="text-muted small">Default is Active.</div>
        @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3">{{ $oldDesc }}</textarea>
        @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
</div>
