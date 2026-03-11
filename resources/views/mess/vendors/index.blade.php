@extends('admin.layouts.master')
@section('title', 'Mess Vendors')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Mess Stores"></x-breadcrum>
    <div class="datatables">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">Vendor Master</h4>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#createVendorModal">
                        Add Vendor
                    </button>
                </div>

                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <div class="table-responsive">
                    <table id="vendorsTable" class="table align-middle w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Vendor Name</th>
                                <th>Email</th>
                                <th>Contact Person</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vendors as $vendor)
                            <tr>
                                <td>{{ $vendor->id }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $vendor->name }}</div>
                                </td>
                                <td>{{ $vendor->email ?? '-' }}</td>
                                <td>{{ $vendor->contact_person ?? '-' }}</td>
                                <td>{{ $vendor->phone ?? '-' }}</td>
                                <td>{{ $vendor->address ?? '-' }}</td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="button"
                                            class="text-primary btn-view-vendor bg-transparent border-0"
                                            data-id="{{ $vendor->id }}" data-name="{{ e($vendor->name) }}"
                                            data-email="{{ e($vendor->email ?? '') }}"
                                            data-contact-person="{{ e($vendor->contact_person ?? '') }}"
                                            data-phone="{{ e($vendor->phone ?? '') }}"
                                            data-address="{{ e($vendor->address ?? '') }}"
                                            data-gst-number="{{ e($vendor->gst_number ?? '') }}"
                                            data-bank-name="{{ e($vendor->bank_name ?? '') }}"
                                            data-ifsc-code="{{ e($vendor->ifsc_code ?? '') }}"
                                            data-account-number="{{ e($vendor->account_number ?? '') }}" title="View"><i
                                                class="material-icons material-symbol-rounded">visibility</i></button>
                                        <button type="button"
                                            class="text-primary btn-edit-vendor bg-transparent border-0"
                                            data-id="{{ $vendor->id }}" data-name="{{ e($vendor->name) }}"
                                            data-email="{{ e($vendor->email ?? '') }}"
                                            data-contact-person="{{ e($vendor->contact_person ?? '') }}"
                                            data-phone="{{ e($vendor->phone ?? '') }}"
                                            data-address="{{ e($vendor->address ?? '') }}"
                                            data-gst-number="{{ e($vendor->gst_number ?? '') }}"
                                            data-bank-name="{{ e($vendor->bank_name ?? '') }}"
                                            data-ifsc-code="{{ e($vendor->ifsc_code ?? '') }}"
                                            data-account-number="{{ e($vendor->account_number ?? '') }}" title="Edit"><i
                                                class="material-icons material-symbol-rounded">edit</i></button>
                                        <form method="POST"
                                            action="{{ route('admin.mess.vendors.destroy', $vendor->id) }}"
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this vendor?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-primary bg-transparent border-0 p-0" title="Delete">
                                                <i class="material-icons material-symbol-rounded">delete</i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Create Vendor Modal --}}
<div class="modal fade" id="createVendorModal" tabindex="-1" aria-labelledby="createVendorModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.mess.vendors.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="createVendorModalLabel">Add Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Vendor Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="create_vendor_name" class="form-control" required
                                value="{{ old('name') }}" pattern="[a-zA-Z0-9\s\-]+" maxlength="255" autocomplete="off">
                            <div class="text-danger small mt-1" id="create_vendor_name_error" role="alert">
                                @error('name'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="create_email" class="form-control"
                                value="{{ old('email') }}" maxlength="255" placeholder="Optional">
                            @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Person <span class="text-danger">*</span></label>
                            <input type="text" name="contact_person" id="create_contact_person" class="form-control"
                                required value="{{ old('contact_person') }}" pattern="[a-zA-Z0-9\s\-]+" maxlength="255"
                                autocomplete="off">
                            <div class="text-danger small mt-1" id="create_contact_person_error" role="alert">
                                @error('contact_person'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="create_phone" class="form-control" required
                                value="{{ old('phone') }}" inputmode="numeric" pattern="[0-9]{10}" maxlength="10"
                                placeholder="10 digit mobile number">
                            <div class="text-danger small mt-1" id="create_phone_error" role="alert">
                                @error('phone'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea name="address" id="create_address" class="form-control" rows="3" required
                                maxlength="2000" autocomplete="off"
                                placeholder="Up to 2000 characters">{{ old('address') }}</textarea>
                            <div class="text-danger small mt-1" id="create_address_error" role="alert">
                                @error('address'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GST Number</label>
                            <input type="text" name="gst_number" id="create_gst_number" class="form-control"
                                value="{{ old('gst_number') }}" maxlength="15" pattern="[A-Za-z0-9]+"
                                placeholder="Letters & numbers, max 15">
                            <div class="text-danger small mt-1" id="create_gst_number_error" role="alert">
                                @error('gst_number'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bank Name</label>
                            <input type="text" name="bank_name" id="create_bank_name" class="form-control"
                                value="{{ old('bank_name') }}" maxlength="255" pattern="[a-zA-Z0-9\s\-]+"
                                placeholder="No special characters, max 255">
                            <div class="text-danger small mt-1" id="create_bank_name_error" role="alert">
                                @error('bank_name'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">IFSC Code</label>
                            <input type="text" name="ifsc_code" id="create_ifsc_code" class="form-control"
                                value="{{ old('ifsc_code') }}" maxlength="11" pattern="[A-Za-z0-9]+"
                                placeholder="Letters & numbers, max 11">
                            <div class="text-danger small mt-1" id="create_ifsc_code_error" role="alert">
                                @error('ifsc_code'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Account Number</label>
                            <input type="text" name="account_number" id="create_account_number" class="form-control"
                                value="{{ old('account_number') }}" inputmode="numeric" pattern="[0-9]*" maxlength="18"
                                placeholder="Digits only, max 18">
                            <div class="text-danger small mt-1" id="create_account_number_error" role="alert">
                                @error('account_number'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Upload Licence</label>
                            <input type="file" name="licence_document" class="form-control">
                            @error('licence_document')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- View Vendor Modal --}}
<div class="modal fade" id="viewVendorModal" tabindex="-1" aria-labelledby="viewVendorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-body-tertiary border-0 py-3 px-4">
                <h5 class="modal-title fw-semibold" id="viewVendorModalLabel">Vendor Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-lg-4 bg-body-tertiary">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-body p-3 p-lg-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="border rounded-3 p-3 bg-light-subtle">
                                    <label class="form-label text-body-secondary small mb-1">Vendor Name</label>
                                    <p class="mb-0 fw-semibold text-body" id="view_vendor_name">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border rounded-3 p-3 bg-light-subtle">
                                    <label class="form-label text-body-secondary small mb-1">Email</label>
                                    <p class="mb-0 text-body" id="view_vendor_email">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="border rounded-3 p-3 bg-light-subtle h-100">
                                    <label class="form-label text-body-secondary small mb-1">Contact Person</label>
                                    <p class="mb-0 text-body" id="view_vendor_contact_person">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="border rounded-3 p-3 bg-light-subtle h-100">
                                    <label class="form-label text-body-secondary small mb-1">Phone</label>
                                    <p class="mb-0 text-body" id="view_vendor_phone">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border rounded-3 p-3 bg-light-subtle">
                                    <label class="form-label text-body-secondary small mb-1">Address</label>
                                    <p class="mb-0 text-body" id="view_vendor_address">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="border rounded-3 p-3 bg-light-subtle h-100">
                                    <label class="form-label text-body-secondary small mb-1">GST Number</label>
                                    <p class="mb-0 text-body" id="view_vendor_gst_number">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="border rounded-3 p-3 bg-light-subtle h-100">
                                    <label class="form-label text-body-secondary small mb-1">Bank Name</label>
                                    <p class="mb-0 text-body" id="view_vendor_bank_name">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="border rounded-3 p-3 bg-light-subtle h-100">
                                    <label class="form-label text-body-secondary small mb-1">IFSC Code</label>
                                    <p class="mb-0 text-body" id="view_vendor_ifsc_code">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="border rounded-3 p-3 bg-light-subtle h-100">
                                    <label class="form-label text-body-secondary small mb-1">Account Number</label>
                                    <p class="mb-0 text-body" id="view_vendor_account_number">&mdash;</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-0 pt-2 px-4 pb-4">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Edit Vendor Modal --}}
<div class="modal fade" id="editVendorModal" tabindex="-1" aria-labelledby="editVendorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <form id="editVendorForm" method="POST" action="" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header bg-body-tertiary border-0 py-3 px-4">
                    <h5 class="modal-title fw-semibold" id="editVendorModalLabel">Edit Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3 p-lg-4 bg-body-tertiary">
                    <div class="card border-0 shadow-sm overflow-hidden">
                        <div class="card-body p-3 p-lg-4">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-medium">Vendor Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="name" id="edit_vendor_name" class="form-control" required
                                        pattern="[a-zA-Z0-9\s\-]+" maxlength="255" autocomplete="off">
                                    <div class="text-danger small mt-1" id="edit_vendor_name_error" role="alert"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">Email</label>
                                    <input type="email" name="email" id="edit_vendor_email" class="form-control"
                                        maxlength="255" placeholder="Optional">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-medium">Contact Person <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="contact_person" id="edit_vendor_contact_person"
                                        class="form-control" required pattern="[a-zA-Z0-9\s\-]+" maxlength="255"
                                        autocomplete="off">
                                    <div class="text-danger small mt-1" id="edit_vendor_contact_person_error"
                                        role="alert"></div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-medium">Phone <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" id="edit_vendor_phone" class="form-control" required
                                        inputmode="numeric" pattern="[0-9]{10}" maxlength="10"
                                        placeholder="10 digit mobile number">
                                    <div class="text-danger small mt-1" id="edit_phone_error" role="alert"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">Address <span
                                            class="text-danger">*</span></label>
                                    <textarea name="address" id="edit_vendor_address" class="form-control" rows="3"
                                        required maxlength="2000" autocomplete="off"
                                        placeholder="Up to 2000 characters"></textarea>
                                    <div class="text-danger small mt-1" id="edit_vendor_address_error" role="alert">
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-medium">GST Number</label>
                                    <input type="text" name="gst_number" id="edit_vendor_gst_number"
                                        class="form-control" maxlength="15" pattern="[A-Za-z0-9]+"
                                        placeholder="Letters & numbers, max 15">
                                    <div class="text-danger small mt-1" id="edit_gst_number_error" role="alert"></div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-medium">Bank Name</label>
                                    <input type="text" name="bank_name" id="edit_vendor_bank_name" class="form-control"
                                        maxlength="255" pattern="[a-zA-Z0-9\s\-]+"
                                        placeholder="No special characters, max 255">
                                    <div class="text-danger small mt-1" id="edit_bank_name_error" role="alert"></div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-medium">IFSC Code</label>
                                    <input type="text" name="ifsc_code" id="edit_vendor_ifsc_code"
                                        class="form-control text-uppercase" maxlength="11" pattern="[A-Za-z0-9]+"
                                        placeholder="Letters & numbers, max 11">
                                    <div class="text-danger small mt-1" id="edit_ifsc_code_error" role="alert"></div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-medium">Account Number</label>
                                    <input type="text" name="account_number" id="edit_vendor_account_number"
                                        class="form-control" inputmode="numeric" pattern="[0-9]*" maxlength="18"
                                        placeholder="Digits only, max 18">
                                    <div class="text-danger small mt-1" id="edit_account_number_error" role="alert">
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-medium">Upload Licence</label>
                                    <input type="file" name="licence_document" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-0 pt-2 px-4 pb-4">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@include('components.mess-master-datatables', ['tableId' => 'vendorsTable', 'searchPlaceholder' => 'Search vendors...',
'orderColumn' => 1, 'actionColumnIndex' => 6, 'infoLabel' => 'vendors'])
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validation rules (must match VendorController)
    var nameRegex = /^[a-zA-Z0-9\s\-]+$/;
    var addressRegex = /^[a-zA-Z0-9\s\-\.\,\r\n]+$/;
    var gstRegex = /^[A-Za-z0-9]*$/;
    var bankNameRegex = /^[a-zA-Z0-9\s\-]*$/;
    var ifscRegex = /^[A-Za-z0-9]*$/;
    var accountNumberRegex = /^[0-9]*$/;
    var nameMessage =
        'Vendor name may only contain letters, numbers, spaces and hyphens. Special characters are not allowed.';
    var contactPersonMessage =
        'Contact person may only contain letters, numbers, spaces and hyphens. Special characters are not allowed.';
    var addressMessage =
        'Address may only contain letters, numbers, spaces, hyphens, commas, periods and new lines. Special characters are not allowed.';
    var gstMessage = 'GST number may only contain letters and numbers. Max 15 characters.';
    var bankNameMessage =
    'Bank name may only contain letters, numbers, spaces and hyphens. Max 255 characters.';
    var ifscMessage = 'IFSC code may only contain letters and numbers. Max 11 characters.';
    var accountNumberMessage = 'Account number must contain only digits. Max 18 digits.';

    function validateName(value, required) {
        if (typeof value !== 'string') return {
            valid: true
        };
        value = value.trim();
        if (required && value.length === 0) return {
            valid: false,
            message: 'This field is required.'
        };
        if (value.length === 0) return {
            valid: true
        };
        return nameRegex.test(value) ? {
            valid: true
        } : {
            valid: false,
            message: nameMessage
        };
    }

    function validateContactPerson(value) {
        if (typeof value !== 'string') return {
            valid: true
        };
        value = value.trim();
        if (value.length === 0) return {
            valid: false,
            message: 'Contact person is required.'
        };
        return nameRegex.test(value) ? {
            valid: true
        } : {
            valid: false,
            message: contactPersonMessage
        };
    }

    function validateAddress(value) {
        if (typeof value !== 'string') return {
            valid: true
        };
        value = value.trim();
        if (value.length === 0) return {
            valid: false,
            message: 'Address is required.'
        };
        if (value.length > 2000) return {
            valid: false,
            message: 'Address cannot exceed 2000 characters.'
        };
        return addressRegex.test(value) ? {
            valid: true
        } : {
            valid: false,
            message: addressMessage
        };
    }

    function validateGst(value) {
        if (typeof value !== 'string') return {
            valid: true
        };
        value = value.trim();
        if (value.length === 0) return {
            valid: true
        };
        if (value.length > 15) return {
            valid: false,
            message: 'GST number cannot exceed 15 characters.'
        };
        return gstRegex.test(value) ? {
            valid: true
        } : {
            valid: false,
            message: gstMessage
        };
    }

    function validateBankName(value) {
        if (typeof value !== 'string') return {
            valid: true
        };
        value = value.trim();
        if (value.length === 0) return {
            valid: true
        };
        if (value.length > 255) return {
            valid: false,
            message: 'Bank name cannot exceed 255 characters.'
        };
        return bankNameRegex.test(value) ? {
            valid: true
        } : {
            valid: false,
            message: bankNameMessage
        };
    }

    function validateIfsc(value) {
        if (typeof value !== 'string') return {
            valid: true
        };
        value = value.trim();
        if (value.length === 0) return {
            valid: true
        };
        if (value.length > 11) return {
            valid: false,
            message: 'IFSC code cannot exceed 11 characters.'
        };
        return ifscRegex.test(value) ? {
            valid: true
        } : {
            valid: false,
            message: ifscMessage
        };
    }

    function validateAccountNumber(value) {
        if (typeof value !== 'string') return {
            valid: true
        };
        value = value.trim();
        if (value.length === 0) return {
            valid: true
        };
        if (value.length > 18) return {
            valid: false,
            message: 'Account number cannot exceed 18 digits.'
        };
        return accountNumberRegex.test(value) ? {
            valid: true
        } : {
            valid: false,
            message: accountNumberMessage
        };
    }

    function showLiveError(inputEl, errorEl, result) {
        if (!inputEl || !errorEl) return;
        if (result.valid) {
            inputEl.classList.remove('is-invalid');
            errorEl.textContent = '';
        } else {
            inputEl.classList.add('is-invalid');
            errorEl.textContent = result.message;
        }
    }

    function attachLiveValidation(inputId, errorId, validateFn) {
        var input = document.getElementById(inputId);
        var errorEl = document.getElementById(errorId);
        if (!input || !errorEl) return;

        function run() {
            showLiveError(input, errorEl, validateFn(input.value));
        }
        input.addEventListener('input', run);
        input.addEventListener('blur', run);
    }

    function normalizeAndValidatePhone(value, required) {
        if (typeof value !== 'string') return {
            valid: !required
        };
        var digits = value.replace(/\D/g, '').slice(0, 10);
        return (function() {
            if (required && digits.length === 0) {
                return {
                    valid: false,
                    message: 'Phone number is required.'
                };
            }
            if (digits.length !== 10) {
                return { valid: false, message: 'Phone number must be exactly 10 digits and start with 6, 7, 8, or 9.' };
            }
            if (!/^[6-9][0-9]{9}$/.test(digits)) {
                return { valid: false, message: 'Phone number must be exactly 10 digits and start with 6, 7, 8, or 9.' };
            }
            return {
                valid: true
            };
        })();
    }

    function attachPhoneField(inputId, errorId, required) {
        var input = document.getElementById(inputId);
        var errorEl = document.getElementById(errorId);
        if (!input || !errorEl) return;

        function run() {
            var raw = input.value || '';
            var cleaned = raw.replace(/\D/g, '').slice(0, 10);
            if (cleaned !== raw) {
                input.value = cleaned;
            }
            var result = normalizeAndValidatePhone(cleaned, required);
            showLiveError(input, errorEl, result);
            return result;
        }

        input.addEventListener('input', run);
        input.addEventListener('blur', run);

        return run;
    }

    // Create modal: real-time validation
    attachLiveValidation('create_vendor_name', 'create_vendor_name_error', function(v) {
        return validateName(v, true);
    });
    attachLiveValidation('create_contact_person', 'create_contact_person_error', validateContactPerson);
    attachLiveValidation('create_address', 'create_address_error', validateAddress);
    var createPhoneValidator = attachPhoneField('create_phone', 'create_phone_error', true);

    // Edit modal: real-time validation
    attachLiveValidation('edit_vendor_name', 'edit_vendor_name_error', function(v) {
        return validateName(v, true);
    });
    attachLiveValidation('edit_vendor_contact_person', 'edit_vendor_contact_person_error',
        validateContactPerson);
    attachLiveValidation('edit_vendor_address', 'edit_vendor_address_error', validateAddress);
    var editPhoneValidator = attachPhoneField('edit_vendor_phone', 'edit_phone_error', true);
    attachLiveValidation('edit_vendor_gst_number', 'edit_gst_number_error', validateGst);
    attachLiveValidation('edit_vendor_bank_name', 'edit_bank_name_error', validateBankName);
    attachLiveValidation('edit_vendor_ifsc_code', 'edit_ifsc_code_error', validateIfsc);
    attachLiveValidation('edit_vendor_account_number', 'edit_account_number_error', validateAccountNumber);

    // Create modal: optional fields live validation
    attachLiveValidation('create_gst_number', 'create_gst_number_error', validateGst);
    attachLiveValidation('create_bank_name', 'create_bank_name_error', validateBankName);
    attachLiveValidation('create_ifsc_code', 'create_ifsc_code_error', validateIfsc);
    attachLiveValidation('create_account_number', 'create_account_number_error', validateAccountNumber);

    function runOptionalValidators(prefix) {
        var inputGst, inputBank, inputIfsc, inputAcc, errGst, errBank, errIfsc, errAcc;
        if (prefix === 'create') {
            inputGst = document.getElementById('create_gst_number');
            inputBank = document.getElementById('create_bank_name');
            inputIfsc = document.getElementById('create_ifsc_code');
            inputAcc = document.getElementById('create_account_number');
            errGst = document.getElementById('create_gst_number_error');
            errBank = document.getElementById('create_bank_name_error');
            errIfsc = document.getElementById('create_ifsc_code_error');
            errAcc = document.getElementById('create_account_number_error');
        } else {
            inputGst = document.getElementById('edit_vendor_gst_number');
            inputBank = document.getElementById('edit_vendor_bank_name');
            inputIfsc = document.getElementById('edit_vendor_ifsc_code');
            inputAcc = document.getElementById('edit_vendor_account_number');
            errGst = document.getElementById('edit_gst_number_error');
            errBank = document.getElementById('edit_bank_name_error');
            errIfsc = document.getElementById('edit_ifsc_code_error');
            errAcc = document.getElementById('edit_account_number_error');
        }
        var rGst = validateGst((inputGst && inputGst.value) || '');
        var rBank = validateBankName((inputBank && inputBank.value) || '');
        var rIfsc = validateIfsc((inputIfsc && inputIfsc.value) || '');
        var rAcc = validateAccountNumber((inputAcc && inputAcc.value) || '');
        if (inputGst && errGst) showLiveError(inputGst, errGst, rGst);
        if (inputBank && errBank) showLiveError(inputBank, errBank, rBank);
        if (inputIfsc && errIfsc) showLiveError(inputIfsc, errIfsc, rIfsc);
        if (inputAcc && errAcc) showLiveError(inputAcc, errAcc, rAcc);
        return rGst.valid && rBank.valid && rIfsc.valid && rAcc.valid;
    }

    // Create form: prevent submit if invalid; prevent double submit
    var createForm = document.querySelector('#createVendorModal form');
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            var r1 = validateName(document.getElementById('create_vendor_name').value, true);
            var r2 = validateContactPerson(document.getElementById('create_contact_person').value);
            var r3 = validateAddress(document.getElementById('create_address').value);
            var r4 = createPhoneValidator ? createPhoneValidator() : {
                valid: true
            };
            var rOpt = runOptionalValidators('create');
            showLiveError(document.getElementById('create_vendor_name'), document.getElementById('create_vendor_name_error'), r1);
            showLiveError(document.getElementById('create_contact_person'), document.getElementById('create_contact_person_error'), r2);
            showLiveError(document.getElementById('create_address'), document.getElementById('create_address_error'), r3);
            if (!r1.valid || !r2.valid || !r3.valid || !r4.valid || !rOpt) {
                e.preventDefault();
                return;
            }
            var btn = this.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.textContent = 'Saving...';
            }
        });
    }

    // Edit form: prevent submit if invalid; prevent double submit
    var editForm = document.getElementById('editVendorForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            var r1 = validateName(document.getElementById('edit_vendor_name').value, true);
            var r2 = validateContactPerson(document.getElementById('edit_vendor_contact_person').value);
            var r3 = validateAddress(document.getElementById('edit_vendor_address').value);
            var r4 = editPhoneValidator ? editPhoneValidator() : {
                valid: true
            };
            var rOpt = runOptionalValidators('edit');
            showLiveError(document.getElementById('edit_vendor_name'), document.getElementById('edit_vendor_name_error'), r1);
            showLiveError(document.getElementById('edit_vendor_contact_person'), document.getElementById('edit_vendor_contact_person_error'), r2);
            showLiveError(document.getElementById('edit_vendor_address'), document.getElementById('edit_vendor_address_error'), r3);
            if (!r1.valid || !r2.valid || !r3.valid || !r4.valid || !rOpt) {
                e.preventDefault();
                return;
            }
            var btn = this.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.textContent = 'Updating...';
            }
        });
    }

    var createVendorModal = document.getElementById('createVendorModal');
    if (createVendorModal) {
        createVendorModal.addEventListener('hidden.bs.modal', function() {
            var form = createVendorModal.querySelector('form');
            if (form) form.reset();
            ['create_vendor_name_error', 'create_contact_person_error', 'create_address_error',
                'create_phone_error', 'create_gst_number_error', 'create_bank_name_error',
                'create_ifsc_code_error', 'create_account_number_error'
            ].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.textContent = '';
            });
            ['create_vendor_name', 'create_contact_person', 'create_address', 'create_phone',
                'create_gst_number', 'create_bank_name', 'create_ifsc_code', 'create_account_number'
            ].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.classList.remove('is-invalid');
            });
        });
        createVendorModal.addEventListener('shown.bs.modal', function() {
            showLiveError(document.getElementById('create_vendor_name'), document.getElementById(
                'create_vendor_name_error'), validateName(document.getElementById(
                'create_vendor_name').value, true));
            showLiveError(document.getElementById('create_contact_person'), document.getElementById(
                'create_contact_person_error'), validateContactPerson(document.getElementById(
                'create_contact_person').value));
            showLiveError(document.getElementById('create_address'), document.getElementById(
                'create_address_error'), validateAddress(document.getElementById(
                'create_address').value));
            if (createPhoneValidator) {
                createPhoneValidator();
            }
        });
    }

    var editVendorModal = document.getElementById('editVendorModal');
    if (editVendorModal) {
        editVendorModal.addEventListener('hidden.bs.modal', function() {
            ['edit_vendor_name_error', 'edit_vendor_contact_person_error', 'edit_vendor_address_error',
                'edit_phone_error', 'edit_gst_number_error', 'edit_bank_name_error',
                'edit_ifsc_code_error', 'edit_account_number_error'
            ].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.textContent = '';
            });
            ['edit_vendor_name', 'edit_vendor_contact_person', 'edit_vendor_address',
                'edit_vendor_phone', 'edit_vendor_gst_number', 'edit_vendor_bank_name',
                'edit_vendor_ifsc_code', 'edit_vendor_account_number'
            ].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.classList.remove('is-invalid');
            });
        });
    }

    document.addEventListener('mousedown', function(e) {
        var viewBtn = e.target.closest('.btn-view-vendor');
        if (viewBtn) {
            e.preventDefault();
            e.stopPropagation();
            var set = function(id, val) {
                var el = document.getElementById(id);
                if (el) el.textContent = val || '—';
            };
            set('view_vendor_name', viewBtn.getAttribute('data-name'));
            set('view_vendor_email', viewBtn.getAttribute('data-email'));
            set('view_vendor_contact_person', viewBtn.getAttribute('data-contact-person'));
            set('view_vendor_phone', viewBtn.getAttribute('data-phone'));
            set('view_vendor_address', viewBtn.getAttribute('data-address'));
            set('view_vendor_gst_number', viewBtn.getAttribute('data-gst-number'));
            set('view_vendor_bank_name', viewBtn.getAttribute('data-bank-name'));
            set('view_vendor_ifsc_code', viewBtn.getAttribute('data-ifsc-code'));
            set('view_vendor_account_number', viewBtn.getAttribute('data-account-number'));
            new bootstrap.Modal(document.getElementById('viewVendorModal')).show();
            return;
        }
        var btn = e.target.closest('.btn-edit-vendor');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('editVendorForm').action = '{{ url("admin/mess/vendors") }}/' + btn
            .getAttribute('data-id');
        document.getElementById('edit_vendor_name').value = btn.getAttribute('data-name') || '';
        document.getElementById('edit_vendor_email').value = btn.getAttribute('data-email') || '';
        document.getElementById('edit_vendor_contact_person').value = btn.getAttribute(
            'data-contact-person') || '';
        document.getElementById('edit_vendor_phone').value = btn.getAttribute('data-phone') || '';
        document.getElementById('edit_vendor_address').value = btn.getAttribute('data-address') || '';
        document.getElementById('edit_vendor_gst_number').value = btn.getAttribute('data-gst-number') ||
            '';
        document.getElementById('edit_vendor_bank_name').value = btn.getAttribute('data-bank-name') ||
            '';
        document.getElementById('edit_vendor_ifsc_code').value = btn.getAttribute('data-ifsc-code') ||
            '';
        document.getElementById('edit_vendor_account_number').value = btn.getAttribute(
            'data-account-number') || '';
        new bootstrap.Modal(document.getElementById('editVendorModal')).show();
    }, true);
});
</script>
@endpush
@endsection