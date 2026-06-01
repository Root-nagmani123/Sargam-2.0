@php
    $prefix = $prefix ?? 'create';
    $storeTypes = $storeTypes ?? \App\Models\Mess\Store::storeTypes();
    $storeName = $storeName ?? old('store_name', '');
    $storeType = $storeType ?? old('store_type', 'mess');
    $location = $location ?? old('location', '');
    $status = $status ?? old('status', 'active');
@endphp
<div class="row g-3">
    <div class="col-12">
        <label class="form-label fw-semibold" for="{{ $prefix }}_store_name">Store Name <span class="text-danger">*</span></label>
        <input type="text" name="store_name" id="{{ $prefix }}_store_name" class="form-control rounded-3" required
               value="{{ $storeName }}"
               pattern="[a-zA-Z0-9\s\-]+"
               autocomplete="off">
        <div class="text-danger small mt-1 str-field-error" id="{{ $prefix }}_store_name_error" role="alert">@error('store_name'){{ $message }}@enderror</div>
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold" for="{{ $prefix }}_store_type">Store Type <span class="text-danger">*</span></label>
        <select name="store_type" id="{{ $prefix }}_store_type" class="form-select rounded-3" required>
            <option value="">Select</option>
            @foreach($storeTypes as $value => $label)
                <option value="{{ $value }}" {{ (string) $storeType === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('store_type')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold" for="{{ $prefix }}_location">Location</label>
        <input type="text" name="location" id="{{ $prefix }}_location" class="form-control rounded-3"
               value="{{ $location }}"
               pattern="[a-zA-Z0-9\s\-\.\,]*"
               autocomplete="off">
        <div class="text-danger small mt-1 str-field-error" id="{{ $prefix }}_location_error" role="alert">@error('location'){{ $message }}@enderror</div>
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold" for="{{ $prefix }}_status">Status</label>
        <select name="status" id="{{ $prefix }}_status" class="form-select rounded-3">
            <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        @if($prefix === 'create')
            <div class="form-text">Default is Active.</div>
        @endif
        @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
</div>
