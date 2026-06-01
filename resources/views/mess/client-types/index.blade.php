@extends('admin.layouts.master')

@section('title', 'Client Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/mess-master-admin.css') }}?v={{ @filemtime(public_path('css/mess-master-admin.css')) ?: time() }}">
@endpush

@section('content')
@php
    $clientTypeOptions = \App\Models\Mess\ClientType::clientTypes();
    $canDeleteClientType = hasRole('Admin') || hasRole('Mess-Admin') || hasRole('Mess Admin') || hasRole('mess admin');
    $isClientTypeActive = static function ($clientType) {
        return ($clientType->status ?? 'active') === 'active';
    };
    $openCreateModal = request('open') === 'create' || ($errors->any() && old('_method') !== 'PUT');
    $openEditModal = request('open') === 'edit' || ($errors->any() && old('_method') === 'PUT');
@endphp
<div class="container-fluid mess-master-page py-4">
    <x-breadcrum title="Client Master">
        <button type="button" id="openCreateClientType"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm"
            data-bs-toggle="modal" data-bs-target="#createClientTypeModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Client</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card mess-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="programme-dt-toolbar mess-dt-toolbar d-flex flex-wrap align-items-center justify-content-end gap-2 gap-md-3 mb-4">
                <div id="ctDtSearch" class="programme-dt-search" data-dt-search-for="clientTypesTable"></div>
                <div id="messColManagerMount-clientTypesTable" class="flex-shrink-0"></div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive mess-dt-scroll">
                    <table id="clientTypesTable"
                        class="table table-hover align-middle mb-0 w-100 programme-dt-table border-0">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Client Types</th>
                                <th scope="col">Client Name</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($clientTypes as $index => $clientType)
                            <tr class="{{ $loop->odd ? 'odd' : 'even' }}">
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $clientTypeOptions[$clientType->client_type] ?? $clientType->client_type }}</td>
                                <td><span class="mess-row-title">{{ $clientType->client_name }}</span></td>
                                <td>
                                    <span class="badge rounded-pill programme-status-badge mess-status-badge programme-status-badge--{{ $isClientTypeActive($clientType) ? 'active' : 'inactive' }}">
                                        {{ $isClientTypeActive($clientType) ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    @include('components.mess-master-action-cell', [
                                        'entityLabel' => 'client',
                                        'recordId' => $clientType->id,
                                        'isActive' => $isClientTypeActive($clientType),
                                        'canDelete' => $canDeleteClientType,
                                        'destroyUrl' => route('admin.mess.client-types.destroy', $clientType->id),
                                        'toggleTable' => 'mess_client_types',
                                        'editClass' => 'btn-edit-clienttype',
                                        'editAttributes' => [
                                            'data-id' => $clientType->id,
                                            'data-client-type' => e($clientType->client_type),
                                            'data-client-name' => e($clientType->client_name),
                                            'data-status' => e($clientType->status ?? 'active'),
                                        ],
                                    ])
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="mess-empty-state text-center">
                                    <i class="bi bi-people display-4 text-secondary opacity-50 d-block mb-3" aria-hidden="true"></i>
                                    <h5 class="fw-semibold text-dark mb-1">No Clients Found</h5>
                                    <p class="text-secondary mb-0">Add a client to get started.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div id="ctDtFooter"
                    class="programme-dt-footer mess-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 pt-3"
                    data-dt-footer-for="clientTypesTable"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createClientTypeModal" tabindex="-1" aria-labelledby="createClientTypeModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered mess-master-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4 mess-modal-form">
            <form method="POST" action="{{ route('admin.mess.client-types.store') }}">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold mb-0" id="createClientTypeModalLabel">Add Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
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
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 gap-2">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editClientTypeModal" tabindex="-1" aria-labelledby="editClientTypeModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered mess-master-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4 mess-modal-form">
            <form id="editClientTypeForm" method="POST" action="{{ $openEditModal && old('client_type_modal_id') ? route('admin.mess.client-types.update', old('client_type_modal_id')) : '' }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="client_type_modal_id" id="edit_client_type_modal_id" value="{{ old('client_type_modal_id', $editClientType?->id ?? '') }}">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold mb-0" id="editClientTypeModalLabel">Edit Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
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
                            <input type="text" name="client_name" id="edit_client_name" class="form-control" required
                                value="{{ old('client_name', $editClientType->client_name ?? '') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active" {{ old('status', $editClientType->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $editClientType->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 gap-2">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('components.mess-master-datatables', [
    'tableId' => 'clientTypesTable',
    'searchPlaceholder' => 'Search',
    'orderColumn' => 1,
    'actionColumnIndex' => 4,
    'infoLabel' => 'clients',
    'pageLength' => 10,
])

@push('scripts')
<script src="{{ asset('js/mess-master-list.js') }}?v={{ @filemtime(public_path('js/mess-master-list.js')) ?: time() }}"></script>
<script>
(function () {
    var tableSelector = '#clientTypesTable';
    var canDelete = @json($canDeleteClientType);
    var destroyBaseUrl = @json(url('admin/mess/client-types'));
    var baseUrl = destroyBaseUrl;

    function initPage() {
        var ML = window.MessMasterList;
        if (!ML) return;

        ML.moveModalsToBody(['createClientTypeModal', 'editClientTypeModal']);
        ML.wireModalExclusivity([{ create: 'createClientTypeModal', edit: 'editClientTypeModal' }]);
        ML.bindMessStatusToggle(tableSelector, {
            entityLabel: 'client',
            canDelete: canDelete,
            destroyBaseUrl: destroyBaseUrl,
            confirmMessage: 'Are you sure you want to delete this client?'
        });

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-edit-clienttype');
            if (!btn || !btn.closest('#clientTypesTable')) return;
            e.preventDefault();
            e.stopPropagation();
            ML.hideMessModal('createClientTypeModal');
            var id = btn.getAttribute('data-id');
            document.getElementById('editClientTypeForm').action = baseUrl + '/' + id;
            document.getElementById('edit_client_type_modal_id').value = id;
            document.getElementById('edit_client_type').value = btn.getAttribute('data-client-type') || '';
            document.getElementById('edit_client_name').value = btn.getAttribute('data-client-name') || '';
            document.getElementById('edit_status').value = btn.getAttribute('data-status') || 'active';
            ML.showMessModal('editClientTypeModal');
        });

        @if($openCreateModal)
        ML.showMessModal('createClientTypeModal');
        @endif
        @if($openEditModal)
        (function () {
            var editId = document.getElementById('edit_client_type_modal_id');
            if (editId && editId.value) {
                document.getElementById('editClientTypeForm').action = baseUrl + '/' + editId.value;
            }
            ML.showMessModal('editClientTypeModal');
        })();
        @endif
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPage);
    } else {
        initPage();
    }
})();
</script>
@endpush
@endsection
