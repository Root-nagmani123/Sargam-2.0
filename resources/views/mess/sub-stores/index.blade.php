@extends('admin.layouts.master')
@section('title', 'Sub Store Master')
@section('setup_content')
<div class="container-fluid">
    <div class="datatables">
        <div class="card">
            <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Sub Store Master</h4>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSubStoreModal">
                    Add Sub Store
                </button>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table id="subStoresTable" class="table table-bordered table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th style="width: 70px; background-color: #004a93; color: #fff; border-color: #004a93;">#</th>
                            <th style="background-color: #004a93; color: #fff; border-color: #004a93;">Sub Store Name</th>
                            <th style="width: 120px; background-color: #004a93; color: #fff; border-color: #004a93;">Status</th>
                            <th style="width: 160px; background-color: #004a93; color: #fff; border-color: #004a93;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subStores as $subStore)
                            <tr>
                                <td>{{ $subStore->id }}</td>
                                <td><div class="fw-semibold">{{ $subStore->sub_store_name }}</div></td>
                                <td>
                                    <span class="badge bg-{{ $subStore->status_badge_class }}">
                                        {{ $subStore->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="button" class="btn btn-sm btn-warning btn-edit-substore"
                                                data-id="{{ $subStore->id }}"
                                                data-sub-store-name="{{ e($subStore->sub_store_name) }}"
                                                data-status="{{ e($subStore->status ?? 'active') }}"
                                                title="Edit">Edit</button>
                                        <form method="POST" action="{{ route('admin.mess.sub-stores.destroy', $subStore->id) }}" class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete this sub store?');">
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

{{-- Create Sub Store Modal --}}
<div class="modal fade" id="createSubStoreModal" tabindex="-1" aria-labelledby="createSubStoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.mess.sub-stores.store') }}">
                @csrf
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="createSubStoreModalLabel">Add Sub Store</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Sub Store Name <span class="text-danger">*</span></label>
                            <input type="text" name="sub_store_name" class="form-control" required value="{{ old('sub_store_name') }}">
                            @error('sub_store_name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select select2">
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

{{-- Edit Sub Store Modal --}}
<div class="modal fade" id="editSubStoreModal" tabindex="-1" aria-labelledby="editSubStoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editSubStoreForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="editSubStoreModalLabel">Edit Sub Store</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Sub Store Name <span class="text-danger">*</span></label>
                            <input type="text" name="sub_store_name" id="edit_sub_store_name" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select select2">
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

@include('components.mess-master-datatables', ['tableId' => 'subStoresTable', 'searchPlaceholder' => 'Search sub stores...', 'orderColumn' => 1, 'actionColumnIndex' => 3, 'infoLabel' => 'sub stores'])
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('mousedown', function(e) {
        var btn = e.target.closest('.btn-edit-substore');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('editSubStoreForm').action = '{{ url("admin/mess/sub-stores") }}/' + btn.getAttribute('data-id');
        document.getElementById('edit_sub_store_name').value = btn.getAttribute('data-sub-store-name') || '';
        document.getElementById('edit_status').value = btn.getAttribute('data-status') || 'active';
        new bootstrap.Modal(document.getElementById('editSubStoreModal')).show();
    }, true);
});
</script>
@endpush

<style>
.table thead th { background-color: #004a93 !important; color: #fff !important; }
</style>
@endsection
