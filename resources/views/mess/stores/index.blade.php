@extends('admin.layouts.master')
@section('title', 'Mess Stores')
@section('setup_content')
@php
    $storeTypes = \App\Models\Mess\Store::storeTypes();
@endphp
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Store Master</h4>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createStoreModal">
                    Add Store
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
                            <th style="background-color: #af2910; color: #fff; border-color: #af2910;">Store Name</th>
                            <th style="width: 160px; background-color: #af2910; color: #fff; border-color: #af2910;">Store Type</th>
                            <th style="background-color: #af2910; color: #fff; border-color: #af2910;">Location</th>
                            <th style="width: 120px; background-color: #af2910; color: #fff; border-color: #af2910;">Status</th>
                            <th style="width: 160px; background-color: #af2910; color: #fff; border-color: #af2910;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stores as $store)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $store->store_name }}</div>
                                    <div class="text-muted small">Code: {{ $store->store_code }}</div>
                                </td>
                                <td class="text-capitalize">{{ $storeTypes[$store->store_type ?? 'mess'] ?? ($store->store_type ?? '-') }}</td>
                                <td>{{ $store->location ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ $store->status_badge_class }}">
                                        {{ $store->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="button" class="btn btn-sm btn-warning btn-edit-store"
                                                data-id="{{ $store->id }}"
                                                data-store-name="{{ e($store->store_name) }}"
                                                data-store-type="{{ e($store->store_type ?? 'mess') }}"
                                                data-location="{{ e($store->location ?? '') }}"
                                                data-status="{{ e($store->status ?? 'active') }}"
                                                title="Edit">Edit</button>
                                        <form method="POST" action="{{ route('admin.mess.stores.destroy', $store->id) }}" class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete this store?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete" style="display: none;">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No stores found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Create Store Modal --}}
<div class="modal fade" id="createStoreModal" tabindex="-1" aria-labelledby="createStoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.mess.stores.store') }}">
                @csrf
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="createStoreModalLabel">Add Store</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Store Name <span class="text-danger">*</span></label>
                            <input type="text" name="store_name" class="form-control" required value="{{ old('store_name') }}">
                            @error('store_name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Store Type <span class="text-danger">*</span></label>
                            <select name="store_type" class="form-select" required>
                                <option value="">Select</option>
                                @foreach($storeTypes as $value => $label)
                                    <option value="{{ $value }}" {{ old('store_type', 'mess') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('store_type')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" value="{{ old('location') }}">
                            @error('location')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            <div class="text-muted small">Default is Active.</div>
                            @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
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

{{-- Edit Store Modal --}}
<div class="modal fade" id="editStoreModal" tabindex="-1" aria-labelledby="editStoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editStoreForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="editStoreModalLabel">Edit Store</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Store Name <span class="text-danger">*</span></label>
                            <input type="text" name="store_name" id="edit_store_name" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Store Type <span class="text-danger">*</span></label>
                            <select name="store_type" id="edit_store_type" class="form-select" required>
                                <option value="">Select</option>
                                @foreach($storeTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" id="edit_location" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
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
    document.querySelectorAll('.btn-edit-store').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var baseUrl = '{{ url("admin/mess/stores") }}';
            document.getElementById('editStoreForm').action = baseUrl + '/' + id;
            document.getElementById('edit_store_name').value = this.getAttribute('data-store-name') || '';
            document.getElementById('edit_store_type').value = this.getAttribute('data-store-type') || 'mess';
            document.getElementById('edit_location').value = this.getAttribute('data-location') || '';
            document.getElementById('edit_status').value = this.getAttribute('data-status') || 'active';
            new bootstrap.Modal(document.getElementById('editStoreModal')).show();
        });
    });
});
</script>
@endpush

<style>
.table thead th { background-color: #af2910 !important; color: #fff !important; }
</style>
@endsection
