@php
    /** @var \App\Models\Mess\SubStore|null $subStore */
    $subStore = $subStore ?? null;
    $oldSubStoreName = old('sub_store_name', $subStore ? $subStore->sub_store_name : '');
    $oldStatus = old('status', $subStore ? ($subStore->status ?? 'active') : 'active');
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Sub Store Name <span class="text-danger">*</span></label>
        <input type="text" name="sub_store_name" class="form-control" required value="{{ $oldSubStoreName }}">
        @error('sub_store_name')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

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
