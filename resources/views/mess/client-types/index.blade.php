@extends('admin.layouts.master')
@section('title', 'Client Types Master')
@section('content')
@php
    $clientTypeOptions = \App\Models\Mess\ClientType::clientTypes();
@endphp
<div class="container-fluid mess-master-page">
    <x-breadcrum title="Client Types Master">
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createClientTypeModal">
            <i class="material-symbols-rounded" aria-hidden="true">add</i>
            <span>Add Client Type</span>
        </button>
    </x-breadcrum>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <x-mess-export-bar table-id="clientTypesTable" title="Client Types Master" />

    <div class="datatables">
        <div class="ds-card mess-dt-card">
            <x-mess-master-toolbar table-id="clientTypesTable" />

            <div class="table-responsive">
                <table id="clientTypesTable" class="table  align-middle w-100">
                    <thead>
                        <tr>
                            <th>Client Types</th>
                            <th>Client Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
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
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-semibold" id="createClientTypeModalLabel">Add Client Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Client Types <span class="text-danger">*</span></label>
                            <select name="client_type" class="form-select" required>
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
                            <select name="status" class="form-select">
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            <div class="text-muted small">Default is Active.</div>
                            @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
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
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-semibold" id="editClientTypeModalLabel">Edit Client Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Client Types <span class="text-danger">*</span></label>
                            <select name="client_type" id="edit_client_type" class="form-select" required>
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
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('components.mess-master-datatables', [
    'tableId' => 'clientTypesTable',
    'searchPlaceholder' => 'Search client types...',
    'orderColumn' => 0,
    'actionColumnIndex' => 3,
    'infoLabel' => 'client types',
    'serverSide' => true,
    'ajaxUrlBase' => route('admin.mess.client-types.index'),
])
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

@endsection
