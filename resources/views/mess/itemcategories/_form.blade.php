@php
    /** @var \App\Models\Mess\ItemCategory|null $itemcategory */
    $itemcategory = $itemcategory ?? null;
    $types = \App\Models\Mess\ItemCategory::categoryTypes();
    $oldType = old('category_type', $itemcategory ? ($itemcategory->category_type ?? 'raw_material') : 'raw_material');
    $oldName = old('category_name', $itemcategory ? $itemcategory->category_name : '');
    $oldDesc = old('description', $itemcategory ? $itemcategory->description : '');
    $oldStatus = old('status', $itemcategory ? ($itemcategory->status ?? 'active') : 'active');
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Category Name <span class="text-danger">*</span></label>
        <input type="text" name="category_name" class="form-control" required value="{{ $oldName }}">
        @error('category_name')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Category Type <span class="text-danger">*</span></label>
        <select name="category_type" class="form-control" required>
            <option value="">Select</option>
            @foreach($types as $value => $label)
                <option value="{{ $value }}" {{ $oldType === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('category_type')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Item Category Description</label>
        <textarea name="description" class="form-control" rows="3">{{ $oldDesc }}</textarea>
        @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
            <option value="active" {{ $oldStatus === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ $oldStatus === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <div class="text-muted small">Default is Active.</div>
        @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
</div>
