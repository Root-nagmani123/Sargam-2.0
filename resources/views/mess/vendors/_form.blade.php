@php
    /** @var \App\Models\Mess\Vendor|null $vendor */
    $vendor = $vendor ?? null;
    $oldName = old('name', $vendor->name ?? '');
    $oldEmail = old('email', $vendor->email ?? '');
    $oldContactPerson = old('contact_person', $vendor->contact_person ?? '');
    $oldPhone = old('phone', $vendor->phone ?? '');
    $oldAddress = old('address', $vendor->address ?? '');
    $oldGstNumber = old('gst_number', $vendor->gst_number ?? '');
    $oldBankName = old('bank_name', $vendor->bank_name ?? '');
    $oldIfscCode = old('ifsc_code', $vendor->ifsc_code ?? '');
    $oldAccountNumber = old('account_number', $vendor->account_number ?? '');
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Vendor Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" required value="{{ $oldName }}">
        @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ $oldEmail }}" placeholder="Optional">
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

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">GST Number</label>
        <input type="text" name="gst_number" class="form-control" value="{{ $oldGstNumber }}" placeholder="Optional">
        @error('gst_number')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Bank Name</label>
        <input type="text" name="bank_name" class="form-control" value="{{ $oldBankName }}" placeholder="Optional">
        @error('bank_name')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">IFSC Code</label>
        <input type="text" name="ifsc_code" class="form-control" value="{{ $oldIfscCode }}" placeholder="Optional">
        @error('ifsc_code')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Account Number</label>
        <input type="text" name="account_number" class="form-control" value="{{ $oldAccountNumber }}" placeholder="Optional">
        @error('account_number')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Upload Licence</label>
        <input type="file" name="licence_document" class="form-control">
        @error('licence_document')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
</div>
