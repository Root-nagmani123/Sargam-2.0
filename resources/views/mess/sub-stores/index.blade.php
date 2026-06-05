@extends('admin.layouts.master')

@section('title', 'Sub Store Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/mess-master-admin.css') }}?v={{ @filemtime(public_path('css/mess-master-admin.css')) ?: time() }}">
@endpush

@section('content')
@php
    $canDeleteSubStore = hasRole('Admin') || hasRole('Mess-Admin') || hasRole('Mess Admin') || hasRole('mess admin');
    $isSubStoreActive = static function ($subStore) {
        return ($subStore->status ?? 'active') === 'active';
    };
    $openCreateModal = request('open') === 'create' || ($errors->any() && old('_method') !== 'PUT');
    $openEditModal = request('open') === 'edit' || ($errors->any() && old('_method') === 'PUT');
@endphp
<div class="container-fluid mess-master-page py-4">
    <x-breadcrum title="Sub Store Master">
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold text-nowrap shadow-sm"
            data-bs-toggle="modal" data-bs-target="#createSubStoreModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Sub Store</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card mess-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="programme-dt-toolbar mess-dt-toolbar d-flex flex-wrap align-items-center justify-content-end gap-2 gap-md-3 mb-4">
                <div id="messColManagerMount-subStoresTable" class="flex-shrink-0"></div>
                <div id="ssDtSearch" class="programme-dt-search" data-dt-search-for="subStoresTable"></div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive mess-dt-scroll">
                    <table id="subStoresTable"
                        class="table table-hover align-middle mb-0 w-100 programme-dt-table border-0">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Sub Store Name</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($subStores as $index => $subStore)
                            <tr class="{{ $loop->odd ? 'odd' : 'even' }}">
                                <td>{{ $index + 1 }}</td>
                                <td><span class="mess-row-title">{{ $subStore->sub_store_name }}</span></td>
                                <td>
                                    <span class="badge rounded-pill programme-status-badge mess-status-badge programme-status-badge--{{ $isSubStoreActive($subStore) ? 'active' : 'inactive' }}">
                                        {{ $isSubStoreActive($subStore) ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    @include('components.mess-master-action-cell', [
                                        'entityLabel' => 'sub store',
                                        'recordId' => $subStore->id,
                                        'isActive' => $isSubStoreActive($subStore),
                                        'canDelete' => $canDeleteSubStore,
                                        'destroyUrl' => route('admin.mess.sub-stores.destroy', $subStore->id),
                                        'toggleTable' => 'mess_sub_stores',
                                        'editClass' => 'btn-edit-substore',
                                        'editAttributes' => [
                                            'data-id' => $subStore->id,
                                            'data-sub-store-name' => e($subStore->sub_store_name),
                                            'data-status' => e($subStore->status ?? 'active'),
                                        ],
                                    ])
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="mess-empty-state text-center">
                                    <i class="bi bi-box-seam display-4 text-secondary opacity-50 d-block mb-3" aria-hidden="true"></i>
                                    <h5 class="fw-semibold text-dark mb-1">No Sub Stores Found</h5>
                                    <p class="text-secondary mb-0">Add a sub store to get started.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div id="ssDtFooter"
                    class="programme-dt-footer mess-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 pt-3"
                    data-dt-footer-for="subStoresTable"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createSubStoreModal" tabindex="-1" aria-labelledby="createSubStoreModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered mess-master-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4 mess-modal-form">
            <form method="POST" action="{{ route('admin.mess.sub-stores.store') }}">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold mb-0" id="createSubStoreModalLabel">Add Sub Store</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
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

<div class="modal fade" id="editSubStoreModal" tabindex="-1" aria-labelledby="editSubStoreModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered mess-master-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4 mess-modal-form">
            <form id="editSubStoreForm" method="POST" action="{{ $openEditModal && old('sub_store_modal_id') ? route('admin.mess.sub-stores.update', old('sub_store_modal_id')) : '' }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="sub_store_modal_id" id="edit_sub_store_modal_id" value="{{ old('sub_store_modal_id', $editSubStore?->id ?? '') }}">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold mb-0" id="editSubStoreModalLabel">Edit Sub Store</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Sub Store Name <span class="text-danger">*</span></label>
                            <input type="text" name="sub_store_name" id="edit_sub_store_name" class="form-control" required
                                value="{{ old('sub_store_name', $editSubStore->sub_store_name ?? '') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active" {{ old('status', $editSubStore->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $editSubStore->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
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
    'tableId' => 'subStoresTable',
    'searchPlaceholder' => 'Search',
    'orderColumn' => 1,
    'actionColumnIndex' => 3,
    'infoLabel' => 'sub stores',
    'pageLength' => 10,
])

@push('scripts')
<script src="{{ asset('js/mess-master-list.js') }}?v={{ @filemtime(public_path('js/mess-master-list.js')) ?: time() }}"></script>
<script>
(function () {
    var tableSelector = '#subStoresTable';
    var canDelete = @json($canDeleteSubStore);
    var destroyBaseUrl = @json(url('admin/mess/sub-stores'));

    function initPage() {
        var ML = window.MessMasterList;
        if (!ML) return;

        ML.moveModalsToBody(['createSubStoreModal', 'editSubStoreModal']);
        ML.wireModalExclusivity([{ create: 'createSubStoreModal', edit: 'editSubStoreModal' }]);
        ML.bindMessStatusToggle(tableSelector, {
            entityLabel: 'sub store',
            canDelete: canDelete,
            destroyBaseUrl: destroyBaseUrl
        });

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-edit-substore');
            if (!btn || !btn.closest('#subStoresTable')) return;
            e.preventDefault();
            e.stopPropagation();
            ML.hideMessModal('createSubStoreModal');
            var id = btn.getAttribute('data-id');
            document.getElementById('editSubStoreForm').action = destroyBaseUrl + '/' + id;
            document.getElementById('edit_sub_store_modal_id').value = id;
            document.getElementById('edit_sub_store_name').value = btn.getAttribute('data-sub-store-name') || '';
            document.getElementById('edit_status').value = btn.getAttribute('data-status') || 'active';
            ML.showMessModal('editSubStoreModal');
        });

        @if($openCreateModal)
        ML.showMessModal('createSubStoreModal');
        @endif
        @if($openEditModal)
        (function () {
            var editId = document.getElementById('edit_sub_store_modal_id');
            if (editId && editId.value) {
                document.getElementById('editSubStoreForm').action = destroyBaseUrl + '/' + editId.value;
            }
            ML.showMessModal('editSubStoreModal');
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
