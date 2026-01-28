@php
    /** @var \App\Models\Mess\Vendor|null $vendor */
    $vendor = $vendor ?? null;
    $oldName = old('name', $vendor->name ?? '');
    $oldEmail = old('email', $vendor->email ?? '');
    $oldContactPerson = old('contact_person', $vendor->contact_person ?? '');
    $oldPhone = old('phone', $vendor->phone ?? '');
    $oldAddress = old('address', $vendor->address ?? '');
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Vendor Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" required value="{{ $oldName }}">
        @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ $oldEmail }}">
        @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Contact Person</label>
        <input type="text" name="contact_person" class="form-control" value="{{ $oldContactPerson }}">
        @error('contact_person')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="{{ $oldPhone }}">
        @error('phone')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-3">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="3">{{ $oldAddress }}</textarea>
        @error('address')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
</div>
