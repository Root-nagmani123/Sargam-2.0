@extends('admin.layouts.master')
@section('title', 'Mess Vendors')
@section('setup_content')
<div class="container-fluid py-3">
    <x-breadcrum title="Vendor Master"></x-breadcrum>
    <div class="datatables">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3 mb-4">
                    <h4 class="mb-0 fw-semibold">Vendor Master</h4>
                    <button type="button" class="btn btn-primary rounded-2 px-3 d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createVendorModal">
                        <i class="material-icons material-icons-rounded" style="font-size: 1.25rem;">add</i>
                        <span>Add Vendor</span>
                    </button>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show rounded-3 d-flex align-items-center gap-2" role="alert">
                        <i class="material-icons material-icons-rounded flex-shrink-0">check_circle</i>
                        <span>{{ session('success') }}</span>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
<hr class="my-2">
                <div class="table-responsive">
                    <table id="vendorsTable" class="table align-middle w-100 mb-0">
                        <thead>
                            <tr>
                                <th class="col">S.No.</th>
                                <th class="col">Vendor Name</th>
                                <th class="col">Email</th>
                                <th class="col">Contact Person</th>
                                <th class="col">Phone</th>
                                <th class="col">Address</th>
                                <th class="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vendors as $vendor)
                                <tr>
                                    <td>{{ $vendor->id }}</td>
                                    <td>{{ $vendor->name }}</td>
                                    <td>{{ $vendor->email ?? '-' }}</td>
                                    <td>{{ $vendor->contact_person ?? '-' }}</td>
                                    <td>{{ $vendor->phone ?? '-' }}</td>
                                    <td>{{ $vendor->address ?? '-' }}</td>
                                    <td>
                                        <div class="d-flex gap-2 flex-wrap justify-content-end">
                                            <a href="javascript:void(0)" class="text-primary btn-view-vendor"
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
                                                    title="View"><i class="material-icons material-icons-rounded">visibility</i></a>
                                            <a href="javascript:void(0)" class="text-primary btn-edit-vendor"
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
                                                    title="Edit"><i class="material-icons material-icons-rounded">edit</i></a>
                                            <form method="POST" action="{{ route('admin.mess.vendors.destroy', $vendor->id) }}" class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this vendor?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-primary d-none" title="Delete"><i class="material-icons material-icons-rounded">delete</i></button>
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
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-3 border-0 shadow">
            <form method="POST" action="{{ route('admin.mess.vendors.store') }}">
                @csrf
                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-semibold" id="createVendorModalLabel">Add Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-medium small">Vendor Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm rounded-2" required value="{{ old('name') }}" placeholder="Enter vendor name">
                            @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium small">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control form-control-sm rounded-2" required value="{{ old('email') }}" placeholder="email@example.com">
                            @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Contact Person <span class="text-danger">*</span></label>
                            <input type="text" name="contact_person" class="form-control form-control-sm rounded-2" required value="{{ old('contact_person') }}" placeholder="Contact name">
                            @error('contact_person')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control form-control-sm rounded-2" required value="{{ old('phone') }}" inputmode="numeric" pattern="[0-9]*" maxlength="20" placeholder="Digits only">
                            @error('phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium small">Address <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control form-control-sm rounded-2" rows="3" required placeholder="Full address">{{ old('address') }}</textarea>
                            @error('address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 pt-2">
                            <span class="small text-muted fw-medium">Bank & tax (optional)</span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">GST Number</label>
                            <input type="text" name="gst_number" class="form-control form-control-sm rounded-2" value="{{ old('gst_number') }}" placeholder="Optional">
                            @error('gst_number')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control form-control-sm rounded-2" value="{{ old('bank_name') }}" placeholder="Optional">
                            @error('bank_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">IFSC Code</label>
                            <input type="text" name="ifsc_code" class="form-control form-control-sm rounded-2" value="{{ old('ifsc_code') }}" placeholder="Optional">
                            @error('ifsc_code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Account Number</label>
                            <input type="text" name="account_number" class="form-control form-control-sm rounded-2" value="{{ old('account_number') }}" inputmode="numeric" pattern="[0-9]*" maxlength="50" placeholder="Digits only (optional)">
                            @error('account_number')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4 gap-2">
                    <button type="button" class="btn btn-light rounded-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-2 px-4">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- View Vendor Modal --}}
<div class="modal fade" id="viewVendorModal" tabindex="-1" aria-labelledby="viewVendorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-semibold" id="viewVendorModalLabel">Vendor Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label text-muted small mb-1">Vendor Name</label>
                        <p class="mb-0 fw-semibold lh-base" id="view_vendor_name">—</p>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted small mb-1">Email</label>
                        <p class="mb-0 lh-base" id="view_vendor_email">—</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">Contact Person</label>
                        <p class="mb-0 lh-base" id="view_vendor_contact_person">—</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">Phone</label>
                        <p class="mb-0 lh-base" id="view_vendor_phone">—</p>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted small mb-1">Address</label>
                        <p class="mb-0 lh-base" id="view_vendor_address">—</p>
                    </div>
                    <div class="col-12 pt-2">
                        <span class="small text-muted fw-medium">Bank & tax</span>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">GST Number</label>
                        <p class="mb-0 lh-base" id="view_vendor_gst_number">—</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">Bank Name</label>
                        <p class="mb-0 lh-base" id="view_vendor_bank_name">—</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">IFSC Code</label>
                        <p class="mb-0 lh-base" id="view_vendor_ifsc_code">—</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">Account Number</label>
                        <p class="mb-0 lh-base" id="view_vendor_account_number">—</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 px-4">
                <button type="button" class="btn btn-primary rounded-2 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Edit Vendor Modal --}}
<div class="modal fade" id="editVendorModal" tabindex="-1" aria-labelledby="editVendorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-3 border-0 shadow">
            <form id="editVendorForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-semibold" id="editVendorModalLabel">Edit Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-medium small">Vendor Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_vendor_name" class="form-control form-control-sm rounded-2" required placeholder="Enter vendor name">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium small">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="edit_vendor_email" class="form-control form-control-sm rounded-2" required placeholder="email@example.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Contact Person <span class="text-danger">*</span></label>
                            <input type="text" name="contact_person" id="edit_vendor_contact_person" class="form-control form-control-sm rounded-2" required placeholder="Contact name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="edit_vendor_phone" class="form-control form-control-sm rounded-2" required inputmode="numeric" pattern="[0-9]*" maxlength="20" placeholder="Digits only">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium small">Address <span class="text-danger">*</span></label>
                            <textarea name="address" id="edit_vendor_address" class="form-control form-control-sm rounded-2" rows="3" required placeholder="Full address"></textarea>
                        </div>
                        <div class="col-12 pt-2">
                            <span class="small text-muted fw-medium">Bank & tax (optional)</span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">GST Number</label>
                            <input type="text" name="gst_number" id="edit_vendor_gst_number" class="form-control form-control-sm rounded-2" placeholder="Optional">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Bank Name</label>
                            <input type="text" name="bank_name" id="edit_vendor_bank_name" class="form-control form-control-sm rounded-2" placeholder="Optional">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">IFSC Code</label>
                            <input type="text" name="ifsc_code" id="edit_vendor_ifsc_code" class="form-control form-control-sm rounded-2" placeholder="Optional">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Account Number</label>
                            <input type="text" name="account_number" id="edit_vendor_account_number" class="form-control form-control-sm rounded-2" inputmode="numeric" pattern="[0-9]*" maxlength="50" placeholder="Digits only (optional)">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4 gap-2">
                    <button type="button" class="btn btn-light rounded-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-2 px-4">Update</button>
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
@endsection
