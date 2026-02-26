@extends('admin.layouts.master')
@section('title', 'Client Types Master')
@section('setup_content')
@php
    $clientTypeOptions = \App\Models\Mess\ClientType::clientTypes();
@endphp
<div class="container-fluid">
    <div class="datatables">
        <div class="card">
            <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Client Types Master</h4>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createClientTypeModal">
                    Add Client Type
                </button>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table id="clientTypesTable" class="table table-striped table-hover table-bordered align-middle mb-0 w-100">
                    <thead>
                        <tr>
                            <th style="width: 70px; background-color: #004a93; color: #fff; border-color: #004a93;">#</th>
                            <th style="width: 160px; background-color: #004a93; color: #fff; border-color: #004a93;">Client Types</th>
                            <th style="background-color: #004a93; color: #fff; border-color: #004a93;">Client Name</th>
                            <th style="width: 120px; background-color: #004a93; color: #fff; border-color: #004a93;">Status</th>
                            <th style="width: 160px; background-color: #004a93; color: #fff; border-color: #004a93;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($clientTypes as $clientType)
                            <tr>
                                <td>{{ $clientType->id }}</td>
                                <td><div class="fw-semibold">{{ $clientTypeOptions[$clientType->client_type] ?? $clientType->client_type }}</div></td>
                                <td><div class="fw-semibold">{{ $clientType->client_name }}</div></td>
                                <td>
                                    <span class="badge bg-{{ $clientType->status_badge_class }}">
                                        {{ $clientType->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="button" class="btn btn-sm btn-warning btn-edit-clienttype"
                                                data-id="{{ $clientType->id }}"
                                                data-client-type="{{ e($clientType->client_type) }}"
                                                data-client-name="{{ e($clientType->client_name) }}"
                                                data-status="{{ e($clientType->status ?? 'active') }}"
                                                title="Edit">Edit</button>
                                        <form method="POST" action="{{ route('admin.mess.client-types.destroy', $clientType->id) }}" class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete this client type?');">
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

{{-- Create Client Type Modal --}}
<div class="modal fade" id="createClientTypeModal" tabindex="-1" aria-labelledby="createClientTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.mess.client-types.store') }}">
                @csrf
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="createClientTypeModalLabel">Add Client Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Client Types <span class="text-danger">*</span></label>
                            <select name="client_type" class="form-select select2" required>
                                <option value="">Select</option>
                                @foreach($clientTypeOptions as $value => $label)
                                    <option value="{{ $value }}" {{ old('client_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('client_type')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Client Name <span class="text-danger">*</span></label>
                            <input type="text" name="client_name" class="form-control" required value="{{ old('client_name') }}">
                            @error('client_name')<div class="text-danger small">{{ $message }}</div>@enderror
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

{{-- Edit Client Type Modal --}}
<div class="modal fade" id="editClientTypeModal" tabindex="-1" aria-labelledby="editClientTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editClientTypeForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="editClientTypeModalLabel">Edit Client Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Client Types <span class="text-danger">*</span></label>
                            <select name="client_type" id="edit_client_type" class="form-select select2" required>
                                <option value="">Select</option>
                                @foreach($clientTypeOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Client Name <span class="text-danger">*</span></label>
                            <input type="text" name="client_name" id="edit_client_name" class="form-control" required>
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

@include('components.mess-master-datatables', ['tableId' => 'clientTypesTable', 'searchPlaceholder' => 'Search client types...', 'orderColumn' => 1, 'actionColumnIndex' => 4, 'infoLabel' => 'client types'])
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if($errors->isNotEmpty())
    new bootstrap.Modal(document.getElementById('createClientTypeModal')).show();
    @endif
    document.addEventListener('mousedown', function(e) {
        var btn = e.target.closest('.btn-edit-clienttype');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('editClientTypeForm').action = '{{ url("admin/mess/client-types") }}/' + btn.getAttribute('data-id');
        document.getElementById('edit_client_type').value = btn.getAttribute('data-client-type') || '';
        document.getElementById('edit_client_name').value = btn.getAttribute('data-client-name') || '';
        document.getElementById('edit_status').value = btn.getAttribute('data-status') || 'active';
        new bootstrap.Modal(document.getElementById('editClientTypeModal')).show();
    }, true);
});
</script>
@endpush

<style>
.table thead th { background-color: #004a93 !important; color: #fff !important; }
</style>
@endsection
