@extends('admin.layouts.master')

@section('title', 'Store Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/store-master-admin.css') }}?v={{ @filemtime(public_path('css/store-master-admin.css')) ?: time() }}">
@endpush

@section('content')
@php
    $storeTypes = \App\Models\Mess\Store::storeTypes();
    $canDeleteStore = hasRole('Admin') || hasRole('Mess-Admin') || hasRole('Mess Admin') || hasRole('mess admin');
    $isStoreActive = static function ($store) {
        return ($store->status ?? 'active') === 'active';
    };
    $openCreateModal = request('open') === 'create' || ($errors->any() && old('_method') !== 'PUT');
    $openEditModal = request('open') === 'edit' || ($errors->any() && old('_method') === 'PUT');
@endphp
<div class="container-fluid str-master-page">
    <x-breadcrum title="Store Master">
        <button type="button" id="openCreateStore"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold text-nowrap shadow-sm"
            data-bs-toggle="modal" data-bs-target="#createStoreModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Store</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card str-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="programme-dt-toolbar str-dt-toolbar d-flex flex-wrap align-items-center justify-content-end gap-2 gap-md-3 mb-4">
            <div id="messColManagerMount-storesTable" class="str-dt-columns-mount flex-shrink-0"></div>    
            <div id="strDtSearch" class="programme-dt-search" data-dt-search-for="storesTable"></div>
                
            </div>

            <div class="programme-dt-panel str-dt-panel">
                <div class="table-responsive str-dt-scroll">
                    <table id="storesTable"
                        class="table table-hover align-middle mb-0 w-100 programme-dt-table border-0">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Store Name</th>
                                <th scope="col">Store Type</th>
                                <th scope="col">Location</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stores as $index => $store)
                            <tr class="{{ $loop->odd ? 'odd' : 'even' }}"
                                data-str-search="{{ strtolower($store->store_name . ' ' . ($store->store_code ?? '') . ' ' . ($store->location ?? '')) }}">
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <div class="str-store-name">{{ $store->store_name }}</div>
                                    <div class="str-store-code">Code: {{ $store->store_code }}</div>
                                </td>
                                <td class="text-capitalize">{{ $storeTypes[$store->store_type ?? 'mess'] ?? ($store->store_type ?? '-') }}</td>
                                <td>{{ $store->location ?? '-' }}</td>
                                <td class="str-status-cell">
                                    <span class="badge rounded-pill programme-status-badge str-status-badge programme-status-badge--{{ $isStoreActive($store) ? 'active' : 'inactive' }}">
                                        {{ $isStoreActive($store) ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="str-action-cell">
                                    <div class="str-store-actions d-inline-flex align-items-center gap-2 programme-action-group" role="group"
                                        aria-label="Store actions">
                                        <button type="button"
                                            class="btn-edit-store programme-action-btn"
                                            data-id="{{ $store->id }}"
                                            data-store-name="{{ e($store->store_name) }}"
                                            data-store-type="{{ e(trim((string)($store->store_type ?? '')) ?: 'mess') }}"
                                            data-location="{{ e($store->location ?? '') }}"
                                            data-status="{{ e($store->status ?? 'active') }}"
                                            aria-label="Edit store"
                                            title="Edit store">
                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                        </button>
                                        <div class="form-check form-switch str-action-switch-wrap mb-0">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="mess_stores"
                                                data-column="status"
                                                data-id="{{ $store->id }}"
                                                data-id_column="id"
                                                aria-label="Toggle store status"
                                                {{ $isStoreActive($store) ? 'checked' : '' }}>
                                        </div>
                                        @if($isStoreActive($store))
                                            <button type="button"
                                                class="str-delete-btn programme-action-btn programme-action-btn--danger"
                                                disabled
                                                aria-disabled="true"
                                                title="Cannot delete active store"
                                                aria-label="Delete store">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        @elseif($canDeleteStore)
                                            <form method="POST" action="{{ route('admin.mess.stores.destroy', $store->id) }}" class="d-inline str-delete-form m-0"
                                                onsubmit="return confirm('Are you sure you want to delete this store?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="str-delete-btn programme-action-btn programme-action-btn--danger"
                                                    aria-label="Delete store"
                                                    title="Delete store">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        @else
                                            <button type="button"
                                                class="str-delete-btn programme-action-btn programme-action-btn--danger"
                                                disabled
                                                aria-disabled="true"
                                                title="You do not have permission to delete stores"
                                                aria-label="Delete store">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr class="str-empty-row">
                                <td colspan="6" class="str-empty-state text-center">
                                    <i class="bi bi-shop display-4 text-secondary opacity-50 d-block mb-3" aria-hidden="true"></i>
                                    <h5 class="fw-semibold text-dark mb-1">No Stores Found</h5>
                                    <p class="text-secondary mb-0">Add a store to get started.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div id="strDtFooter"
                    class="programme-dt-footer str-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 pt-3"
                    data-dt-footer-for="storesTable"></div>
            </div>
        </div>
    </div>
</div>

{{-- Create Store Modal --}}
<div class="modal fade" id="createStoreModal" tabindex="-1" aria-labelledby="createStoreModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered str-store-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4 str-modal-form">
            <form method="POST" action="{{ route('admin.mess.stores.store') }}">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold mb-0" id="createStoreModalLabel">Add Store</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    @include('mess.stores._modal_fields', ['prefix' => 'create', 'storeTypes' => $storeTypes])
                </div>
                <div class="modal-footer border-0 pt-0 gap-2">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Store Modal --}}
<div class="modal fade" id="editStoreModal" tabindex="-1" aria-labelledby="editStoreModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered str-store-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4 str-modal-form">
            <form id="editStoreForm" method="POST" action="{{ $openEditModal && old('store_modal_id') ? route('admin.mess.stores.update', old('store_modal_id')) : '' }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="store_modal_id" id="edit_store_modal_id" value="{{ old('store_modal_id', $editStore?->id ?? '') }}">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold mb-0" id="editStoreModalLabel">Edit Store</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    @include('mess.stores._modal_fields', [
                        'prefix' => 'edit',
                        'storeTypes' => $storeTypes,
                        'storeName' => old('store_name', $editStore->store_name ?? ''),
                        'storeType' => old('store_type', $editStore->store_type ?? 'mess'),
                        'location' => old('location', $editStore->location ?? ''),
                        'status' => old('status', $editStore->status ?? 'active'),
                    ])
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
    'tableId' => 'storesTable',
    'searchPlaceholder' => 'Search',
    'orderColumn' => 1,
    'actionColumnIndex' => 5,
    'infoLabel' => 'stores',
    'pageLength' => 10,
])

@push('scripts')
<script>
(function () {
    var tableSelector = '#storesTable';
    var canDeleteStore = @json($canDeleteStore);
    var storesDestroyBaseUrl = @json(url('admin/mess/stores'));

    var storeNameRegex = /^[a-zA-Z0-9\s\-]+$/;
    var locationRegex = /^[a-zA-Z0-9\s\-\.\,]*$/;
    var storeNameMessage = 'Store name may only contain letters, numbers, spaces and hyphens. Special characters are not allowed.';
    var locationMessage = 'Location may only contain letters, numbers, spaces, hyphens, commas and periods. Special characters are not allowed.';

    function validateStoreName(value) {
        if (typeof value !== 'string') return { valid: true };
        value = value.trim();
        if (value.length === 0) return { valid: false, message: 'Store name is required.' };
        return storeNameRegex.test(value) ? { valid: true } : { valid: false, message: storeNameMessage };
    }

    function validateLocation(value) {
        if (typeof value !== 'string') return { valid: true };
        return locationRegex.test(value) ? { valid: true } : { valid: false, message: locationMessage };
    }

    function showLiveError(inputEl, errorEl, result) {
        if (!inputEl || !errorEl) return;
        if (result.valid) {
            inputEl.classList.remove('is-invalid');
            errorEl.textContent = '';
        } else {
            inputEl.classList.add('is-invalid');
            errorEl.textContent = result.message;
        }
    }

    function attachLiveValidation(inputId, errorId, validateFn) {
        var input = document.getElementById(inputId);
        var errorEl = document.getElementById(errorId);
        if (!input || !errorEl) return;
        function run() {
            showLiveError(input, errorEl, validateFn(input.value));
        }
        input.addEventListener('input', run);
        input.addEventListener('blur', run);
    }

    function updateStrStatusBadge($row, isActive) {
        if (typeof jQuery === 'undefined') return;
        var $badge = jQuery($row).find('.str-status-badge').first();
        if (!$badge.length) return;
        $badge
            .removeClass('programme-status-badge--active programme-status-badge--inactive')
            .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
            .text(isActive ? 'Active' : 'Inactive');
    }

    function updateStrEditButtonStatus($row, isActive) {
        if (typeof jQuery === 'undefined') return;
        var $editBtn = jQuery($row).find('.btn-edit-store').first();
        if ($editBtn.length) {
            $editBtn.attr('data-status', isActive ? 'active' : 'inactive');
        }
    }

    function buildStrDeleteControl(isActive, storeId) {
        if (typeof jQuery === 'undefined') return null;
        var $ = jQuery;
        var baseClass = 'str-delete-btn programme-action-btn programme-action-btn--danger';

        if (isActive) {
            return $('<button>', {
                type: 'button',
                class: baseClass,
                disabled: true,
                'aria-disabled': 'true',
                title: 'Cannot delete active store',
                'aria-label': 'Delete store'
            }).append('<i class="bi bi-trash" aria-hidden="true"></i>');
        }

        if (!canDeleteStore) {
            return $('<button>', {
                type: 'button',
                class: baseClass,
                disabled: true,
                'aria-disabled': 'true',
                title: 'You do not have permission to delete stores',
                'aria-label': 'Delete store'
            }).append('<i class="bi bi-trash" aria-hidden="true"></i>');
        }

        var $form = $('<form>', {
            method: 'POST',
            action: storesDestroyBaseUrl + '/' + storeId,
            class: 'd-inline str-delete-form m-0'
        });
        $form.append('<input type="hidden" name="_token" value="' + ($('meta[name="csrf-token"]').attr('content') || '') + '">');
        $form.append('<input type="hidden" name="_method" value="DELETE">');
        var $btn = $('<button>', {
            type: 'submit',
            class: baseClass,
            title: 'Delete store',
            'aria-label': 'Delete store'
        }).append('<i class="bi bi-trash" aria-hidden="true"></i>');
        $form.on('submit', function () {
            return confirm('Are you sure you want to delete this store?');
        });
        $form.append($btn);
        return $form;
    }

    function updateStrDeleteControl($row, isActive, storeId) {
        if (typeof jQuery === 'undefined') return;
        var $ = jQuery;
        var $group = $($row).find('.str-store-actions').first();
        if (!$group.length) return;

        $group.find('.str-delete-form, .str-delete-btn').remove();
        var $deleteControl = buildStrDeleteControl(isActive, storeId);
        if ($deleteControl) {
            $group.append($deleteControl);
        }
    }

    function bindStoreTableUi() {
        if (typeof jQuery === 'undefined') return;
        var $ = jQuery;

        $(document).on('change', tableSelector + ' .status-toggle', function () {
            var $toggle = $(this);
            var isActive = $toggle.is(':checked');
            var $row = $toggle.closest('tr');
            var storeId = $toggle.data('id');
            window.setTimeout(function () {
                updateStrStatusBadge($row, isActive);
                updateStrEditButtonStatus($row, isActive);
                updateStrDeleteControl($row, isActive, storeId);
            }, 0);
        });
    }

    function initStorePage() {
        attachLiveValidation('create_store_name', 'create_store_name_error', validateStoreName);
        attachLiveValidation('create_location', 'create_location_error', validateLocation);
        attachLiveValidation('edit_store_name', 'edit_store_name_error', validateStoreName);
        attachLiveValidation('edit_location', 'edit_location_error', validateLocation);

        var createForm = document.querySelector('#createStoreModal form');
        if (createForm) {
            createForm.addEventListener('submit', function (e) {
                var nameResult = validateStoreName(document.getElementById('create_store_name').value);
                var locResult = validateLocation(document.getElementById('create_location').value);
                showLiveError(document.getElementById('create_store_name'), document.getElementById('create_store_name_error'), nameResult);
                showLiveError(document.getElementById('create_location'), document.getElementById('create_location_error'), locResult);
                if (!nameResult.valid || !locResult.valid) e.preventDefault();
            });
        }

        var editForm = document.getElementById('editStoreForm');
        if (editForm) {
            editForm.addEventListener('submit', function (e) {
                var nameResult = validateStoreName(document.getElementById('edit_store_name').value);
                var locResult = validateLocation(document.getElementById('edit_location').value);
                showLiveError(document.getElementById('edit_store_name'), document.getElementById('edit_store_name_error'), nameResult);
                showLiveError(document.getElementById('edit_location'), document.getElementById('edit_location_error'), locResult);
                if (!nameResult.valid || !locResult.valid) e.preventDefault();
            });
        }

        var createStoreModal = document.getElementById('createStoreModal');
        if (createStoreModal) {
            createStoreModal.addEventListener('hidden.bs.modal', function () {
                var form = createStoreModal.querySelector('form');
                if (form) {
                    form.reset();
                    var storeTypeSelect = form.querySelector('select[name="store_type"]');
                    if (storeTypeSelect) storeTypeSelect.value = 'mess';
                    var statusSelect = form.querySelector('select[name="status"]');
                    if (statusSelect) statusSelect.value = 'active';
                }
                document.getElementById('create_store_name_error').textContent = '';
                document.getElementById('create_location_error').textContent = '';
                var createNameInput = document.getElementById('create_store_name');
                var createLocInput = document.getElementById('create_location');
                if (createNameInput) createNameInput.classList.remove('is-invalid');
                if (createLocInput) createLocInput.classList.remove('is-invalid');
            });

            createStoreModal.addEventListener('shown.bs.modal', function () {
                var nameInput = document.getElementById('create_store_name');
                var locInput = document.getElementById('create_location');
                showLiveError(nameInput, document.getElementById('create_store_name_error'), validateStoreName(nameInput.value));
                showLiveError(locInput, document.getElementById('create_location_error'), validateLocation(locInput.value));
            });
        }

        var editStoreModal = document.getElementById('editStoreModal');
        if (editStoreModal) {
            editStoreModal.addEventListener('hidden.bs.modal', function () {
                document.getElementById('edit_store_name_error').textContent = '';
                document.getElementById('edit_location_error').textContent = '';
                document.getElementById('edit_store_name').classList.remove('is-invalid');
                document.getElementById('edit_location').classList.remove('is-invalid');
            });
        }

        var storesBaseUrl = @json(url('admin/mess/stores'));

        function moveStoreModalsToBody() {
            ['createStoreModal', 'editStoreModal'].forEach(function (id) {
                var el = document.getElementById(id);
                if (el && el.parentElement !== document.body) {
                    document.body.appendChild(el);
                }
            });
        }

        function hideStoreModal(modalId) {
            var el = document.getElementById(modalId);
            if (!el || !window.bootstrap || !bootstrap.Modal) return;
            var instance = bootstrap.Modal.getInstance(el);
            if (instance) instance.hide();
        }

        function showStoreModal(modalId) {
            var el = document.getElementById(modalId);
            if (!el || !window.bootstrap || !bootstrap.Modal) return;
            bootstrap.Modal.getOrCreateInstance(el).show();
        }

        function openEditStoreModal(payload) {
            payload = payload || {};
            var id = String(payload.id || '').trim();
            if (!id) return;

            hideStoreModal('createStoreModal');

            var form = document.getElementById('editStoreForm');
            var modalIdInput = document.getElementById('edit_store_modal_id');
            form.action = storesBaseUrl + '/' + id;
            if (modalIdInput) modalIdInput.value = id;

            document.getElementById('edit_store_name').value = payload.storeName || '';
            var storeType = (payload.storeType || '').trim() || 'mess';
            var typeSelect = document.getElementById('edit_store_type');
            typeSelect.value = storeType;
            if (typeSelect.value !== storeType) typeSelect.value = 'mess';
            document.getElementById('edit_location').value = payload.location || '';
            document.getElementById('edit_status').value = payload.status || 'active';

            showStoreModal('editStoreModal');
        }

        moveStoreModalsToBody();

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-edit-store');
            if (!btn || !btn.closest('#storesTable')) return;
            e.preventDefault();
            e.stopPropagation();
            openEditStoreModal({
                id: btn.getAttribute('data-id'),
                storeName: btn.getAttribute('data-store-name') || '',
                storeType: btn.getAttribute('data-store-type') || 'mess',
                location: btn.getAttribute('data-location') || '',
                status: btn.getAttribute('data-status') || 'active'
            });
        });

        if (createStoreModal) {
            createStoreModal.addEventListener('show.bs.modal', function () {
                hideStoreModal('editStoreModal');
            });
        }

        if (editStoreModal) {
            editStoreModal.addEventListener('show.bs.modal', function () {
                hideStoreModal('createStoreModal');
            });
        }

        @if($openCreateModal)
        showStoreModal('createStoreModal');
        @endif

        @if($openEditModal)
        (function () {
            var editId = document.getElementById('edit_store_modal_id');
            if (editId && editId.value) {
                document.getElementById('editStoreForm').action = storesBaseUrl + '/' + editId.value;
            }
            showStoreModal('editStoreModal');
        })();
        @endif

        bindStoreTableUi();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initStorePage);
    } else {
        initStorePage();
    }
})();
</script>
@endpush
@endsection
