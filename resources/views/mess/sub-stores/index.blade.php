@extends('admin.layouts.master')
@section('title', 'Sub Store Master')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Sub Store Master"></x-breadcrum>
    <div class="datatables">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header border-0 bg-body-tertiary d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="mb-0 fw-semibold">Sub Store Master</h4>
                    <p class="mb-0 text-muted small">Manage all mess sub stores in one place.</p>
                </div>
                <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createSubStoreModal">
                    <i class="material-symbols-rounded" style="font-size: 1.1rem;">add</i>
                    <span>Add Sub Store</span>
                </button>
            </div>

            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table id="subStoresTable" class="table text-nowrap align-middle mb-0 w-100">
                        <thead>
                            <tr>
                                <th style="width: 70px;" class="text-center">#</th>
                                <th>Sub Store Name</th>
                                <th style="width: 140px;">Status</th>
                                <th style="width: 120px;" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($subStores as $subStore)
                                <tr>
                                    <td class="text-center">{{ $subStore->id }}</td>
                                    <td><div class="fw-semibold text-truncate">{{ $subStore->sub_store_name }}</div></td>
                                    <td>
                                        <span class="badge bg-{{ $subStore->status_badge_class }} px-3 py-2">
                                            {{ $subStore->status_label }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1 flex-wrap justify-content-center">
                                            <button
                                                type="button"
                                                class=" text-primary d-inline-flex align-items-center justify-content-center bg-transparent border-0 btn-edit-substore"
                                                data-id="{{ $subStore->id }}"
                                                data-sub-store-name="{{ e($subStore->sub_store_name) }}"
                                                data-status="{{ e($subStore->status ?? 'active') }}"
                                                title="Edit"
                                            >
                                                <i class="material-symbols-rounded">edit</i>
                                            </button>
                                            <form
                                                method="POST"
                                                action="{{ route('admin.mess.sub-stores.destroy', $subStore->id) }}"
                                                class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete this sub store?');"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-primary d-inline-flex align-items-center justify-content-center bg-transparent border-0" title="Delete" style="display: none;">
                                                    <i class="material-symbols-rounded">delete</i>
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

{{-- Create Sub Store Modal --}}
<div class="modal fade" id="createSubStoreModal" tabindex="-1" aria-labelledby="createSubStoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <form method="POST" action="{{ route('admin.mess.sub-stores.store') }}">
                @csrf
                <div class="modal-header border-0 bg-body-tertiary">
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
                            <select name="status" class="form-select">
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            <div class="text-muted small mt-1">Default is Active.</div>
                            @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-body-tertiary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Sub Store Modal --}}
<div class="modal fade" id="editSubStoreModal" tabindex="-1" aria-labelledby="editSubStoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <form id="editSubStoreForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header border-0 bg-body-tertiary">
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
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-body-tertiary">
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
.table thead th {
    background-color: var(--bs-primary) !important;
    color: #fff !important;
}
</style>
@endsection
