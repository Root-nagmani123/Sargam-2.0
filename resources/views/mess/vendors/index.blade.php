@extends('admin.layouts.master')
@section('title', 'Mess Vendors')
@section('setup_content')
<div class="container-fluid">
    <div class="datatables">
        <div class="card">
            <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Vendor Master</h4>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createVendorModal">
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
                <table id="vendorsTable" class="table table-bordered table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th style="width: 70px; background-color: #004a93; color: #fff; border-color: #004a93;">#</th>
                            <th style="background-color: #004a93; color: #fff; border-color: #004a93;">Vendor Name</th>
                            <th style="background-color: #004a93; color: #fff; border-color: #004a93;">Email</th>
                            <th style="background-color: #004a93; color: #fff; border-color: #004a93;">Contact Person</th>
                            <th style="background-color: #004a93; color: #fff; border-color: #004a93;">Phone</th>
                            <th style="background-color: #004a93; color: #fff; border-color: #004a93;">Address</th>
                            <th style="width: 160px; background-color: #004a93; color: #fff; border-color: #004a93;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vendors as $vendor)
                            <tr>
                                <td>{{ $vendor->id }}</td>
                                <td><div class="fw-semibold">{{ $vendor->name }}</div></td>
                                <td>{{ $vendor->email ?? '-' }}</td>
                                <td>{{ $vendor->contact_person ?? '-' }}</td>
                                <td>{{ $vendor->phone ?? '-' }}</td>
                                <td>{{ $vendor->address ?? '-' }}</td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="button" class="btn btn-sm btn-info btn-view-vendor text-white"
                                                data-id="{{ $vendor->id }}"
                                                data-name="{{ e($vendor->name) }}"
                                                data-email="{{ e($vendor->email ?? '') }}"
                                                data-contact-person="{{ e($vendor->contact_person ?? '') }}"
                                                data-phone="{{ e($vendor->phone ?? '') }}"
                                                data-address="{{ e($vendor->address ?? '') }}"
                                                data-gst-number="{{ e($vendor->gst_number ?? '') }}"
                                                data-bank-name="{{ e($vendor->bank_name ?? '') }}"
                                                data-ifsc-code="{{ e($vendor->ifsc_code ?? '') }}"
                                                data-account-number="{{ e($vendor->account_number ?? '') }}"
                                                title="View">View</button>
                                        <button type="button" class="btn btn-sm btn-warning btn-edit-vendor"
                                                data-id="{{ $vendor->id }}"
                                                data-name="{{ e($vendor->name) }}"
                                                data-email="{{ e($vendor->email ?? '') }}"
                                                data-contact-person="{{ e($vendor->contact_person ?? '') }}"
                                                data-phone="{{ e($vendor->phone ?? '') }}"
                                                data-address="{{ e($vendor->address ?? '') }}"
                                                data-gst-number="{{ e($vendor->gst_number ?? '') }}"
                                                data-bank-name="{{ e($vendor->bank_name ?? '') }}"
                                                data-ifsc-code="{{ e($vendor->ifsc_code ?? '') }}"
                                                data-account-number="{{ e($vendor->account_number ?? '') }}"
                                                title="Edit">Edit</button>
                                        <form method="POST" action="{{ route('admin.mess.vendors.destroy', $vendor->id) }}" class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete this vendor?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete" style="display: none;">Delete</button>
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
<div class="modal fade" id="createVendorModal" tabindex="-1" aria-labelledby="createVendorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.mess.vendors.store') }}">
                @csrf
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="createVendorModalLabel">Add Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Vendor Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
                            @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required value="{{ old('email') }}">
                            @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Person <span class="text-danger">*</span></label>
                            <input type="text" name="contact_person" class="form-control" required value="{{ old('contact_person') }}">
                            @error('contact_person')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" required value="{{ old('phone') }}" inputmode="numeric" pattern="[0-9]*" maxlength="20" placeholder="Digits only">
                            @error('phone')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control" rows="3" required>{{ old('address') }}</textarea>
                            @error('address')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GST Number</label>
                            <input type="text" name="gst_number" class="form-control" value="{{ old('gst_number') }}" placeholder="Optional">
                            @error('gst_number')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name') }}" placeholder="Optional">
                            @error('bank_name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">IFSC Code</label>
                            <input type="text" name="ifsc_code" class="form-control" value="{{ old('ifsc_code') }}" placeholder="Optional">
                            @error('ifsc_code')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Account Number</label>
                            <input type="text" name="account_number" class="form-control" value="{{ old('account_number') }}" inputmode="numeric" pattern="[0-9]*" maxlength="50" placeholder="Digits only (optional)">
                            @error('account_number')<div class="text-danger small">{{ $message }}</div>@enderror
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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-semibold" id="viewVendorModalLabel">Vendor Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label text-muted small mb-0">Vendor Name</label>
                        <p class="mb-0 fw-semibold" id="view_vendor_name">—</p>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted small mb-0">Email</label>
                        <p class="mb-0" id="view_vendor_email">—</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-0">Contact Person</label>
                        <p class="mb-0" id="view_vendor_contact_person">—</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-0">Phone</label>
                        <p class="mb-0" id="view_vendor_phone">—</p>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted small mb-0">Address</label>
                        <p class="mb-0" id="view_vendor_address">—</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-0">GST Number</label>
                        <p class="mb-0" id="view_vendor_gst_number">—</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-0">Bank Name</label>
                        <p class="mb-0" id="view_vendor_bank_name">—</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-0">IFSC Code</label>
                        <p class="mb-0" id="view_vendor_ifsc_code">—</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-0">Account Number</label>
                        <p class="mb-0" id="view_vendor_account_number">—</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Edit Vendor Modal --}}
<div class="modal fade" id="editVendorModal" tabindex="-1" aria-labelledby="editVendorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editVendorForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="editVendorModalLabel">Edit Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Vendor Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_vendor_name" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="edit_vendor_email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Person <span class="text-danger">*</span></label>
                            <input type="text" name="contact_person" id="edit_vendor_contact_person" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="edit_vendor_phone" class="form-control" required inputmode="numeric" pattern="[0-9]*" maxlength="20" placeholder="Digits only">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea name="address" id="edit_vendor_address" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GST Number</label>
                            <input type="text" name="gst_number" id="edit_vendor_gst_number" class="form-control" placeholder="Optional">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bank Name</label>
                            <input type="text" name="bank_name" id="edit_vendor_bank_name" class="form-control" placeholder="Optional">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">IFSC Code</label>
                            <input type="text" name="ifsc_code" id="edit_vendor_ifsc_code" class="form-control" placeholder="Optional">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Account Number</label>
                            <input type="text" name="account_number" id="edit_vendor_account_number" class="form-control" inputmode="numeric" pattern="[0-9]*" maxlength="50" placeholder="Digits only (optional)">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('components.mess-master-datatables', ['tableId' => 'vendorsTable', 'searchPlaceholder' => 'Search vendors...', 'orderColumn' => 1, 'actionColumnIndex' => 6, 'infoLabel' => 'vendors'])
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('mousedown', function(e) {
        var viewBtn = e.target.closest('.btn-view-vendor');
        if (viewBtn) {
            e.preventDefault();
            e.stopPropagation();
            var set = function(id, val) { var el = document.getElementById(id); if (el) el.textContent = val || '—'; };
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
        document.getElementById('editVendorForm').action = '{{ url("admin/mess/vendors") }}/' + btn.getAttribute('data-id');
        document.getElementById('edit_vendor_name').value = btn.getAttribute('data-name') || '';
        document.getElementById('edit_vendor_email').value = btn.getAttribute('data-email') || '';
        document.getElementById('edit_vendor_contact_person').value = btn.getAttribute('data-contact-person') || '';
        document.getElementById('edit_vendor_phone').value = btn.getAttribute('data-phone') || '';
        document.getElementById('edit_vendor_address').value = btn.getAttribute('data-address') || '';
        document.getElementById('edit_vendor_gst_number').value = btn.getAttribute('data-gst-number') || '';
        document.getElementById('edit_vendor_bank_name').value = btn.getAttribute('data-bank-name') || '';
        document.getElementById('edit_vendor_ifsc_code').value = btn.getAttribute('data-ifsc-code') || '';
        document.getElementById('edit_vendor_account_number').value = btn.getAttribute('data-account-number') || '';
        new bootstrap.Modal(document.getElementById('editVendorModal')).show();
    }, true);
});
</script>
@endpush

<style>
.table thead th { background-color: #004a93 !important; color: #fff !important; }
</style>
@endsection
