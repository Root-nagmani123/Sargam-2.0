@extends('admin.layouts.master')
@section('title', 'Mess Vendors')
@section('setup_content')
<div class="container-fluid">
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
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 70px; background-color: #af2910; color: #fff; border-color: #af2910;">#</th>
                            <th style="background-color: #af2910; color: #fff; border-color: #af2910;">Vendor Name</th>
                            <th style="background-color: #af2910; color: #fff; border-color: #af2910;">Email</th>
                            <th style="background-color: #af2910; color: #fff; border-color: #af2910;">Contact Person</th>
                            <th style="background-color: #af2910; color: #fff; border-color: #af2910;">Phone</th>
                            <th style="background-color: #af2910; color: #fff; border-color: #af2910;">Address</th>
                            <th style="width: 160px; background-color: #af2910; color: #fff; border-color: #af2910;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vendors as $vendor)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><div class="fw-semibold">{{ $vendor->name }}</div></td>
                                <td>{{ $vendor->email ?? '-' }}</td>
                                <td>{{ $vendor->contact_person ?? '-' }}</td>
                                <td>{{ $vendor->phone ?? '-' }}</td>
                                <td>{{ $vendor->address ?? '-' }}</td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="button" class="btn btn-sm btn-warning btn-edit-vendor"
                                                data-id="{{ $vendor->id }}"
                                                data-name="{{ e($vendor->name) }}"
                                                data-email="{{ e($vendor->email ?? '') }}"
                                                data-contact-person="{{ e($vendor->contact_person ?? '') }}"
                                                data-phone="{{ e($vendor->phone ?? '') }}"
                                                data-address="{{ e($vendor->address ?? '') }}"
                                                title="Edit">Edit</button>
                                        <form method="POST" action="{{ route('admin.mess.vendors.destroy', $vendor->id) }}" class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete this vendor?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No vendors found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                            @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person') }}">
                            @error('contact_person')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                            @error('phone')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="3">{{ old('address') }}</textarea>
                            @error('address')<div class="text-danger small">{{ $message }}</div>@enderror
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
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="edit_vendor_email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" id="edit_vendor_contact_person" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="edit_vendor_phone" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" id="edit_vendor_address" class="form-control" rows="3"></textarea>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-edit-vendor').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var baseUrl = '{{ url("admin/mess/vendors") }}';
            document.getElementById('editVendorForm').action = baseUrl + '/' + id;
            document.getElementById('edit_vendor_name').value = this.getAttribute('data-name') || '';
            document.getElementById('edit_vendor_email').value = this.getAttribute('data-email') || '';
            document.getElementById('edit_vendor_contact_person').value = this.getAttribute('data-contact-person') || '';
            document.getElementById('edit_vendor_phone').value = this.getAttribute('data-phone') || '';
            document.getElementById('edit_vendor_address').value = this.getAttribute('data-address') || '';
            new bootstrap.Modal(document.getElementById('editVendorModal')).show();
        });
    });
});
</script>
@endpush

<style>
.table thead th { background-color: #af2910 !important; color: #fff !important; }
</style>
@endsection
