@extends('admin.layouts.master')
@section('title', 'Mess Stores')
@section('setup_content')
@php
    $storeTypes = \App\Models\Mess\Store::storeTypes();
    $canDeleteStore = hasRole('Admin') || hasRole('Mess-Admin');
@endphp
<div class="container-fluid">
    <x-breadcrum title="Store Master"></x-breadcrum>
    <div class="datatables">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header border-0 bg-body-tertiary d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="mb-0 fw-semibold">Store Master</h4>
                    <p class="mb-0 text-muted small">Manage all mess stores in one place.</p>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createStoreModal">
                    <i class="material-symbols-rounded" style="font-size: 1.1rem;">add</i>
                    <span>Add Store</span>
                </button>
            </div>
            <div class="card-body">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table id="storesTable" class="table text-nowrap align-middle w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Store Name</th>
                            <th>Store Type</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stores as $store)
                            <tr>
                                <td>{{ $store->id }}</td>
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
                                        <button type="button" class="btn btn-sm btn-warning btn-edit-store bg-transparent border-0 p-0 text-primary"
                                                data-id="{{ $store->id }}"
                                                data-store-name="{{ e($store->store_name) }}"
                                                data-store-type="{{ e(trim((string)($store->store_type ?? '')) ?: 'mess') }}"
                                                data-location="{{ e($store->location ?? '') }}"
                                                data-status="{{ e($store->status ?? 'active') }}"
                                                title="Edit"><i class="material-symbols-rounded">edit</i></button>
                                        @if($canDeleteStore)
                                            <form method="POST" action="{{ route('admin.mess.stores.destroy', $store->id) }}" class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this store?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger bg-transparent border-0 p-0 text-primary" title="Delete"><i class="material-symbols-rounded">delete</i></button>
                                            </form>
                                        @endif
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
                            <input type="text" name="store_name" id="create_store_name" class="form-control" required
                                   value="{{ old('store_name') }}"
                                   pattern="[a-zA-Z0-9\s\-]+"
                                   autocomplete="off">
                            <div class="text-danger small mt-1" id="create_store_name_error" role="alert">@error('store_name'){{ $message }}@enderror</div>
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
                            <input type="text" name="location" id="create_location" class="form-control"
                                   value="{{ old('location') }}"
                                   pattern="[a-zA-Z0-9\s\-\.\,]*"
                                   autocomplete="off">
                            <div class="text-danger small mt-1" id="create_location_error" role="alert">@error('location'){{ $message }}@enderror</div>
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
                            <input type="text" name="store_name" id="edit_store_name" class="form-control" required
                                   pattern="[a-zA-Z0-9\s\-]+"
                                   autocomplete="off">
                            <div class="text-danger small mt-1" id="edit_store_name_error" role="alert"></div>
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
                            <input type="text" name="location" id="edit_location" class="form-control"
                                   pattern="[a-zA-Z0-9\s\-\.\,]*"
                                   autocomplete="off">
                            <div class="text-danger small mt-1" id="edit_location_error" role="alert"></div>
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

@include('components.mess-master-datatables', ['tableId' => 'storesTable', 'searchPlaceholder' => 'Search stores...', 'orderColumn' => 1, 'actionColumnIndex' => 5, 'infoLabel' => 'stores'])
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide success alerts after 5 seconds
    setTimeout(function() {
        document.querySelectorAll('.alert.alert-success.alert-dismissible').forEach(function(alertEl) {
            try {
                if (window.bootstrap && bootstrap.Alert) {
                    bootstrap.Alert.getOrCreateInstance(alertEl).close();
                } else {
                    alertEl.classList.remove('show');
                    alertEl.style.display = 'none';
                }
            } catch (e) {
                alertEl.classList.remove('show');
                alertEl.style.display = 'none';
            }
        });
    }, 5000);

    // Validation rules (must match server: StoreController)
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

    // Create modal: real-time validation
    attachLiveValidation('create_store_name', 'create_store_name_error', validateStoreName);
    attachLiveValidation('create_location', 'create_location_error', validateLocation);

    // Edit modal: real-time validation
    attachLiveValidation('edit_store_name', 'edit_store_name_error', validateStoreName);
    attachLiveValidation('edit_location', 'edit_location_error', validateLocation);

    // Create form: prevent submit if store name or location invalid
    var createForm = document.querySelector('#createStoreModal form');
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            var nameResult = validateStoreName(document.getElementById('create_store_name').value);
            var locResult = validateLocation(document.getElementById('create_location').value);
            showLiveError(document.getElementById('create_store_name'), document.getElementById('create_store_name_error'), nameResult);
            showLiveError(document.getElementById('create_location'), document.getElementById('create_location_error'), locResult);
            if (!nameResult.valid || !locResult.valid) {
                e.preventDefault();
            }
        });
    }

    // Edit form: prevent submit if store name or location invalid
    var editForm = document.getElementById('editStoreForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            var nameResult = validateStoreName(document.getElementById('edit_store_name').value);
            var locResult = validateLocation(document.getElementById('edit_location').value);
            showLiveError(document.getElementById('edit_store_name'), document.getElementById('edit_store_name_error'), nameResult);
            showLiveError(document.getElementById('edit_location'), document.getElementById('edit_location_error'), locResult);
            if (!nameResult.valid || !locResult.valid) {
                e.preventDefault();
            }
        });
    }

    // Reset Add Store form when modal is closed (Cancel or backdrop) so next open shows a clean form
    var createStoreModal = document.getElementById('createStoreModal');
    if (createStoreModal) {
        createStoreModal.addEventListener('hidden.bs.modal', function() {
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
    }

    // When Add Store modal is shown, run validation once so server-rendered errors get is-invalid styling
    if (createStoreModal) {
        createStoreModal.addEventListener('shown.bs.modal', function() {
            var nameInput = document.getElementById('create_store_name');
            var locInput = document.getElementById('create_location');
            showLiveError(nameInput, document.getElementById('create_store_name_error'), validateStoreName(nameInput.value));
            showLiveError(locInput, document.getElementById('create_location_error'), validateLocation(locInput.value));
        });
    }

    // Clear edit modal errors when it is hidden
    var editStoreModal = document.getElementById('editStoreModal');
    if (editStoreModal) {
        editStoreModal.addEventListener('hidden.bs.modal', function() {
            document.getElementById('edit_store_name_error').textContent = '';
            document.getElementById('edit_location_error').textContent = '';
            document.getElementById('edit_store_name').classList.remove('is-invalid');
            document.getElementById('edit_location').classList.remove('is-invalid');
        });
    }

    document.addEventListener('mousedown', function(e) {
        var btn = e.target.closest('.btn-edit-store');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('editStoreForm').action = '{{ url("admin/mess/stores") }}/' + btn.getAttribute('data-id');
        document.getElementById('edit_store_name').value = btn.getAttribute('data-store-name') || '';
        var storeType = (btn.getAttribute('data-store-type') || '').trim() || 'mess';
        var typeSelect = document.getElementById('edit_store_type');
        typeSelect.value = storeType;
        if (typeSelect.value !== storeType) typeSelect.value = 'mess';
        document.getElementById('edit_location').value = btn.getAttribute('data-location') || '';
        document.getElementById('edit_status').value = btn.getAttribute('data-status') || 'active';
        new bootstrap.Modal(document.getElementById('editStoreModal')).show();
    }, true);
});
</script>
@endpush
@endsection
