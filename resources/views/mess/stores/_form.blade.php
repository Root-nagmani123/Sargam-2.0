@php
    /** @var \App\Models\Mess\Store|null $store */
    $store    = $store ?? null;
    $types    = \App\Models\Mess\Store::storeTypes();
    $oldType  = old('store_type', $store->store_type ?? '');
    $oldLoc   = old('location', $store->location ?? '');
    $oldName  = old('store_name', $store->store_name ?? '');
    $oldStatus = old('status', $store->status ?? 'active');
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Store Name <span class="text-danger">*</span></label>
        <input type="text" name="store_name" class="form-control" required value="{{ $oldName }}">
        @error('store_name')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Store Type <span class="text-danger">*</span></label>
        <select name="store_type" class="form-control" required>
            <option value="">Select</option>
            @foreach($types as $value => $label)
                <option value="{{ $value }}" {{ $oldType === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('store_type')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Location</label>
        <input type="text" name="location" class="form-control" value="{{ $oldLoc }}">
        @error('location')<div class="text-danger small">{{ $message }}</div>@enderror
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

