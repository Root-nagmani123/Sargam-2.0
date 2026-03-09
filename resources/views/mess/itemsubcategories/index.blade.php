@extends('admin.layouts.master')
@section('title', 'Subcategory Item Master')
@section('setup_content')
@php
    $selectedCategoryId = $categoryIdFilter ?? request('category_id', '');
@endphp
<div class="container-fluid">
    <x-breadcrum title="Subcategory Item Master"></x-breadcrum>
    <div class="datatables">
        <div class="card">
            <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h4 class="mb-0">Subcategory Item Master</h4>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createItemSubcategoryModal">
                    Add Subcategory Item
                </button>
            </div>

            <form method="GET" action="{{ route('admin.mess.itemsubcategories.index') }}" class="mb-3 row g-2 align-items-end">
                <div class="col-auto">
                    <label for="filter_category_id" class="form-label mb-0">Category name</label>
                    <select name="category_id" id="filter_category_id" class="form-select form-select-sm" style="min-width: 200px;">
                        <option value="">All</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ (string) $selectedCategoryId === (string) $cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
                    @if($selectedCategoryId !== '')
                        <a href="{{ route('admin.mess.itemsubcategories.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </form>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table id="itemSubcategoriesTable" class="table align-middle w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Category</th>
                            <th>Item Name</th>
                            <th>Item Code</th>
                            <th>Unit Measurement</th>
                            <th>Alert Qty</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($itemsubcategories as $itemsubcategory)
                            <tr>
                                <td>{{ $itemsubcategory->id }}</td>
                                <td>{{ $itemsubcategory->category ? $itemsubcategory->category->category_name : '-' }}</td>
                                <td><div class="fw-semibold">{{ $itemsubcategory->item_name }}</div></td>
                                <td>{{ $itemsubcategory->item_code ?? '-' }}</td>
                                <td>{{ $itemsubcategory->unit_measurement ?? '-' }}</td>
                                <td>{{ isset($itemsubcategory->alert_quantity) && $itemsubcategory->alert_quantity !== null && $itemsubcategory->alert_quantity !== '' ? number_format($itemsubcategory->alert_quantity, 2) : '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ $itemsubcategory->status_badge_class }}">
                                        {{ $itemsubcategory->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="button" class="text-primary btn-edit-itemsubcategory bg-transparent border-0"
                                                data-id="{{ $itemsubcategory->id }}"
                                                data-category-id="{{ $itemsubcategory->category_id ?? '' }}"
                                                data-item-name="{{ e($itemsubcategory->item_name) }}"
                                                data-item-code="{{ e($itemsubcategory->item_code ?? '') }}"
                                                data-unit-measurement="{{ e($itemsubcategory->unit_measurement ?? '') }}"
                                                data-alert-quantity="{{ $itemsubcategory->alert_quantity ?? '' }}"
                                                data-description="{{ e($itemsubcategory->description ?? '') }}"
                                                data-status="{{ e($itemsubcategory->status ?? 'active') }}"
                                                title="Edit"><i class="material-symbols-rounded">edit</i></button>
                                        <form method="POST" action="{{ route('admin.mess.itemsubcategories.destroy', $itemsubcategory->id) }}" class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete this item?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-primary btn-delete-itemsubcategory bg-transparent border-0" title="Delete" style="display: none;">
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

{{-- Create Item Modal --}}
<div class="modal fade" id="createItemSubcategoryModal" tabindex="-1" aria-labelledby="createItemSubcategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.mess.itemsubcategories.store') }}">
                @csrf
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="createItemSubcategoryModalLabel">Add Subcategory Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>
                                @endforeach
                            </select>
                            @error('category_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Item Name <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" id="create_item_name" class="form-control" required
                                   value="{{ old('item_name') }}" pattern="[a-zA-Z0-9\s\-]+" autocomplete="off">
                            <div class="text-danger small mt-1" id="create_item_name_error" role="alert">@error('item_name'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Item Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control bg-light" value="" readonly placeholder="Auto-generated on save">
                            <small class="text-muted">Mandatory. Auto-generated.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit Measurement <span class="text-danger">*</span></label>
                            <input type="text" name="unit_measurement" id="create_unit_measurement" class="form-control"
                                   value="{{ old('unit_measurement') }}" placeholder="e.g., kg, liter, piece" required
                                   pattern="[a-zA-Z0-9\s\-\/\.]+" autocomplete="off">
                            <div class="text-danger small mt-1" id="create_unit_measurement_error" role="alert">@error('unit_measurement'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Alert Quantity (min. stock)</label>
                            <input type="number" name="alert_quantity" class="form-control" step="0.0001" min="0" value="{{ old('alert_quantity') }}" placeholder="Optional">
                            <small class="text-muted">Low stock alert when remaining &le; this</small>
                            @error('alert_quantity')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                            @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
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

{{-- Edit Subcategory Item Modal --}}
<div class="modal fade" id="editItemSubcategoryModal" tabindex="-1" aria-labelledby="editItemSubcategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="editItemSubcategoryForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="editItemSubcategoryModalLabel">Edit Subcategory Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="edit_category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Item Name <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" id="edit_item_name" class="form-control" required
                                   pattern="[a-zA-Z0-9\s\-]+" autocomplete="off">
                            <div class="text-danger small mt-1" id="edit_item_name_error" role="alert"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Item Code <span class="text-danger">*</span></label>
                            <input type="text" id="edit_item_code_display" class="form-control bg-light" readonly>
                            <small class="text-muted">Mandatory. Auto-generated; read-only.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit Measurement <span class="text-danger">*</span></label>
                            <input type="text" name="unit_measurement" id="edit_unit_measurement" class="form-control" required
                                   pattern="[a-zA-Z0-9\s\-\/\.]+" autocomplete="off">
                            <div class="text-danger small mt-1" id="edit_unit_measurement_error" role="alert"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Alert Quantity (min. stock)</label>
                            <input type="number" name="alert_quantity" id="edit_alert_quantity" class="form-control" step="0.0001" min="0" placeholder="Optional">
                            <small class="text-muted">Low stock alert when remaining &le; this</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
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

@include('components.mess-master-datatables', ['tableId' => 'itemSubcategoriesTable', 'searchPlaceholder' => 'Search subcategory items...', 'orderColumn' => 2, 'actionColumnIndex' => 7, 'infoLabel' => 'subcategory items'])
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validation rules (must match ItemSubcategoryController)
    var itemNameRegex = /^[a-zA-Z0-9\s\-]+$/;
    var unitMeasurementRegex = /^[a-zA-Z0-9\s\-\/\.]+$/;
    var itemNameMessage = 'Item name may only contain letters, numbers, spaces and hyphens. Special characters are not allowed.';
    var unitMeasurementMessage = 'Unit measurement may only contain letters, numbers, spaces, hyphens, slashes and periods. Special characters are not allowed.';

    function validateItemName(value) {
        if (typeof value !== 'string') return { valid: true };
        value = value.trim();
        if (value.length === 0) return { valid: false, message: 'Item name is required.' };
        return itemNameRegex.test(value) ? { valid: true } : { valid: false, message: itemNameMessage };
    }

    function validateUnitMeasurement(value) {
        if (typeof value !== 'string') return { valid: true };
        value = value.trim();
        if (value.length === 0) return { valid: false, message: 'Unit measurement is required.' };
        return unitMeasurementRegex.test(value) ? { valid: true } : { valid: false, message: unitMeasurementMessage };
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
        function run() { showLiveError(input, errorEl, validateFn(input.value)); }
        input.addEventListener('input', run);
        input.addEventListener('blur', run);
    }

    // Create modal: real-time validation
    attachLiveValidation('create_item_name', 'create_item_name_error', validateItemName);
    attachLiveValidation('create_unit_measurement', 'create_unit_measurement_error', validateUnitMeasurement);

    // Edit modal: real-time validation
    attachLiveValidation('edit_item_name', 'edit_item_name_error', validateItemName);
    attachLiveValidation('edit_unit_measurement', 'edit_unit_measurement_error', validateUnitMeasurement);

    // Create form submit validation; prevent double submit
    var createForm = document.querySelector('#createItemSubcategoryModal form');
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            var r1 = validateItemName(document.getElementById('create_item_name').value);
            var r2 = validateUnitMeasurement(document.getElementById('create_unit_measurement').value);
            showLiveError(document.getElementById('create_item_name'), document.getElementById('create_item_name_error'), r1);
            showLiveError(document.getElementById('create_unit_measurement'), document.getElementById('create_unit_measurement_error'), r2);
            if (!r1.valid || !r2.valid) {
                e.preventDefault();
                return;
            }
            var btn = this.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.textContent = 'Saving...';
            }
        });
    }

    // Edit form submit validation; prevent double submit
    var editForm = document.getElementById('editItemSubcategoryForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            var r1 = validateItemName(document.getElementById('edit_item_name').value);
            var r2 = validateUnitMeasurement(document.getElementById('edit_unit_measurement').value);
            showLiveError(document.getElementById('edit_item_name'), document.getElementById('edit_item_name_error'), r1);
            showLiveError(document.getElementById('edit_unit_measurement'), document.getElementById('edit_unit_measurement_error'), r2);
            if (!r1.valid || !r2.valid) {
                e.preventDefault();
                return;
            }
            var btn = this.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.textContent = 'Updating...';
            }
        });
    }

    // Clear errors when modals are hidden
    var createModal = document.getElementById('createItemSubcategoryModal');
    if (createModal) {
        createModal.addEventListener('hidden.bs.modal', function() {
            ['create_item_name_error', 'create_unit_measurement_error'].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.textContent = '';
            });
            ['create_item_name', 'create_unit_measurement'].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.classList.remove('is-invalid');
            });
        });
    }

    var editModal = document.getElementById('editItemSubcategoryModal');
    if (editModal) {
        editModal.addEventListener('hidden.bs.modal', function() {
            ['edit_item_name_error', 'edit_unit_measurement_error'].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.textContent = '';
            });
            ['edit_item_name', 'edit_unit_measurement'].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.classList.remove('is-invalid');
            });
        });
    }

    document.addEventListener('mousedown', function(e) {
        var btn = e.target.closest('.btn-edit-itemsubcategory');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('editItemSubcategoryForm').action = '{{ url("admin/mess/itemsubcategories") }}/' + btn.getAttribute('data-id');
        document.getElementById('edit_category_id').value = btn.getAttribute('data-category-id') || '';
        document.getElementById('edit_item_name').value = btn.getAttribute('data-item-name') || '';
        document.getElementById('edit_item_code_display').value = btn.getAttribute('data-item-code') || '-';
        document.getElementById('edit_unit_measurement').value = btn.getAttribute('data-unit-measurement') || '';
        document.getElementById('edit_alert_quantity').value = btn.getAttribute('data-alert-quantity') || '';
        document.getElementById('edit_description').value = btn.getAttribute('data-description') || '';
        document.getElementById('edit_status').value = btn.getAttribute('data-status') || 'active';
        new bootstrap.Modal(document.getElementById('editItemSubcategoryModal')).show();
    }, true);
});
</script>
@endpush

<style>
.table thead th { background-color: #004a93 !important; color: #fff !important; }
</style>
@endsection
