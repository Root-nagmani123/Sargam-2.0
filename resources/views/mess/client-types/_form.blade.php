@php
    /** @var \App\Models\Mess\ClientType|null $clientType */
    $clientType = $clientType ?? null;
    $types = \App\Models\Mess\ClientType::clientTypes();
    $oldType = old('client_type', $clientType ? $clientType->client_type : '');
    $oldName = old('client_name', $clientType ? $clientType->client_name : '');
    $oldStatus = old('status', $clientType ? ($clientType->status ?? 'active') : 'active');
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Client Types <span class="text-danger">*</span></label>
        <select name="client_type" class="form-control" required>
            <option value="">Select</option>
            @foreach($types as $value => $label)
                <option value="{{ $value }}" {{ $oldType === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('client_type')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Client Name <span class="text-danger">*</span></label>
        <input type="text" name="client_name" class="form-control" required value="{{ $oldName }}">
        @error('client_name')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
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
