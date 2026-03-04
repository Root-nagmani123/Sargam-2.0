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
        <input type="text" name="name" class="form-control" required value="{{ $oldName }}"
               pattern="[a-zA-Z0-9\s\-]+" maxlength="255"
               title="Only letters, numbers, spaces and hyphens allowed. No special characters.">
        <div class="text-muted small">Letters, numbers, spaces and hyphens only.</div>
        @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ $oldEmail }}" maxlength="255" placeholder="Optional">
        @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Contact Person <span class="text-danger">*</span></label>
        <input type="text" name="contact_person" class="form-control" required value="{{ $oldContactPerson }}"
               pattern="[a-zA-Z0-9\s\-]+" maxlength="255"
               title="Only letters, numbers, spaces and hyphens allowed. No special characters.">
        <div class="text-muted small">Letters, numbers, spaces and hyphens only.</div>
        @error('contact_person')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Phone <span class="text-danger">*</span></label>
        <input
            type="text"
            name="phone"
            id="vendor_phone"
            class="form-control"
            required
            value="{{ $oldPhone }}"
            inputmode="numeric"
            pattern="[0-9]{10}"
            maxlength="10"
            placeholder="10 digit mobile number">
        <div class="text-danger small mt-1" id="vendor_phone_error">@error('phone'){{ $message }}@enderror</div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-3">
        <label class="form-label">Address <span class="text-danger">*</span></label>
        <textarea name="address" class="form-control" rows="3" required maxlength="2000" placeholder="Up to 2000 characters">{{ $oldAddress }}</textarea>
        <div class="text-muted small">Letters, numbers, spaces, hyphens, commas and periods only. No special characters. Maximum 2000 characters.</div>
        @error('address')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var phoneInput = document.getElementById('vendor_phone');
    var phoneError = document.getElementById('vendor_phone_error');
    if (!phoneInput || !phoneError) return;

    function normalizeAndValidatePhone() {
        var raw = phoneInput.value || '';
        var digitsOnly = raw.replace(/\D/g, '').slice(0, 10);
        if (digitsOnly !== raw) {
            phoneInput.value = digitsOnly;
        }

        if (digitsOnly.length === 0) {
            phoneError.textContent = 'Phone number is required.';
            phoneInput.classList.add('is-invalid');
            return false;
        }

        if (digitsOnly.length !== 10) {
            phoneError.textContent = 'Phone number must be exactly 10 digits.';
            phoneInput.classList.add('is-invalid');
            return false;
        }

        phoneError.textContent = '';
        phoneInput.classList.remove('is-invalid');
        return true;
    }

    phoneInput.addEventListener('input', normalizeAndValidatePhone);
    phoneInput.addEventListener('blur', normalizeAndValidatePhone);

    var form = phoneInput.closest('form');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (!normalizeAndValidatePhone()) {
                e.preventDefault();
            }
        });
    }
});
</script>
@endpush

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">GST Number</label>
        <input type="text" name="gst_number" class="form-control" value="{{ $oldGstNumber }}" maxlength="15"
               pattern="[A-Za-z0-9]+" title="Letters and numbers only. Max 15 characters." placeholder="Optional">
        <div class="text-muted small">Letters and numbers only. Max 15 characters.</div>
        @error('gst_number')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Bank Name</label>
        <input type="text" name="bank_name" class="form-control" value="{{ $oldBankName }}" maxlength="255"
               pattern="[a-zA-Z0-9\s\-]+" title="Letters, numbers, spaces and hyphens only." placeholder="Optional">
        <div class="text-muted small">Letters, numbers, spaces and hyphens only. Max 255 characters.</div>
        @error('bank_name')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">IFSC Code</label>
        <input type="text" name="ifsc_code" class="form-control" value="{{ $oldIfscCode }}" maxlength="11"
               pattern="[A-Za-z0-9]+" title="Letters and numbers only. Max 11 characters." placeholder="Optional">
        <div class="text-muted small">Letters and numbers only. Max 11 characters.</div>
        @error('ifsc_code')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Account Number</label>
        <input type="text" name="account_number" class="form-control" value="{{ $oldAccountNumber }}" maxlength="18"
               inputmode="numeric" pattern="[0-9]*" title="Digits only. Max 18 digits." placeholder="Optional">
        <div class="text-muted small">Digits only. Max 18 digits.</div>
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
