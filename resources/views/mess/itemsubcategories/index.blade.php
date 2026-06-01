@extends('admin.layouts.master')
@section('title', 'Subcategory Item Master')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/mess-master-admin.css') }}?v={{ @filemtime(public_path('css/mess-master-admin.css')) ?: time() }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<style>
    /* Choices.js + Bootstrap for filter category select */
    .itemsubcategories-filter-form .choices__inner {
        min-height: 31px;
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        border-radius: var(--bs-border-radius, 0.375rem);
        border: 1px solid var(--bs-border-color);
        background-color: var(--bs-body-bg);
    }
    .itemsubcategories-filter-form .choices__list--single .choices__item {
        padding: 2px 0;
    }
    .itemsubcategories-filter-form .choices.is-focused .choices__inner,
    .itemsubcategories-filter-form .choices.is-open .choices__inner {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    .itemsubcategories-filter-form .choices__list--dropdown .choices__item--selectable.is-highlighted,
    .itemsubcategories-filter-form .choices__list[aria-expanded] .choices__item--selectable.is-highlighted {
        background-color: var(--bs-primary);
        color: #fff;
    }

    /* Choices.js + Bootstrap for create/edit modal selects */
    #createItemSubcategoryModal .choices__inner,
    #editItemSubcategoryModal .choices__inner {
        min-height: 31px;
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        border-radius: var(--bs-border-radius, 0.375rem);
        border: 1px solid var(--bs-border-color);
        background-color: var(--bs-body-bg);
    }
    #createItemSubcategoryModal .choices__list--single .choices__item,
    #editItemSubcategoryModal .choices__list--single .choices__item {
        padding: 2px 0;
    }
    #createItemSubcategoryModal .choices.is-focused .choices__inner,
    #createItemSubcategoryModal .choices.is-open .choices__inner,
    #editItemSubcategoryModal .choices.is-focused .choices__inner,
    #editItemSubcategoryModal .choices.is-open .choices__inner {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    #createItemSubcategoryModal .choices__list--dropdown .choices__item--selectable.is-highlighted,
    #createItemSubcategoryModal .choices__list[aria-expanded] .choices__item--selectable.is-highlighted,
    #editItemSubcategoryModal .choices__list--dropdown .choices__item--selectable.is-highlighted,
    #editItemSubcategoryModal .choices__list[aria-expanded] .choices__item--selectable.is-highlighted {
        background-color: var(--bs-primary);
        color: #fff;
    }
</style>
@endpush
@section('content')
@php
    $selectedCategoryId = $categoryIdFilter ?? request('category_id', '');
    $canDeleteItemSubcategory = hasRole('Admin') || hasRole('Mess-Admin') || hasRole('Mess Admin') || hasRole('mess admin');
    $isItemSubcategoryActive = static function ($item) {
        return ($item->status ?? 'active') === 'active';
    };
    $openCreateModal = request('open') === 'create' || ($errors->any() && old('_method') !== 'PUT');
    $openEditModal = request('open') === 'edit' || ($errors->any() && old('_method') === 'PUT');
@endphp
<div class="container-fluid mess-master-page py-4">
    <x-breadcrum title="Subcategory Item Master">
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm"
            data-bs-toggle="modal" data-bs-target="#createItemSubcategoryModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Subcategory Item</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card mess-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="{{ route('admin.mess.itemsubcategories.index') }}" class="itemsubcategories-filter-form mess-filter-bar row g-3 align-items-end">
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <label for="filter_category_id" class="form-label mb-1 small fw-semibold">Category name</label>
                    <select name="category_id" id="filter_category_id" class="form-select js-choices" data-placeholder="All">
                        <option value="">All</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ (string) $selectedCategoryId === (string) $cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-auto d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    @if($selectedCategoryId !== '')
                        <a href="{{ route('admin.mess.itemsubcategories.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </form>

            <div class="programme-dt-toolbar mess-dt-toolbar d-flex flex-wrap align-items-center justify-content-end gap-2 gap-md-3 mb-4">
                <div id="iscDtSearch" class="programme-dt-search" data-dt-search-for="itemSubcategoriesTable"></div>
                <div id="messColManagerMount-itemSubcategoriesTable" class="flex-shrink-0"></div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive mess-dt-scroll">
                    <table id="itemSubcategoriesTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table border-0">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Category</th>
                                <th scope="col">Item Name</th>
                                <th scope="col">Item Code</th>
                                <th scope="col">Unit Measurement</th>
                                <th scope="col">Alert Qty</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($itemsubcategories as $index => $itemsubcategory)
                            <tr class="{{ $loop->odd ? 'odd' : 'even' }}">
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $itemsubcategory->category ? $itemsubcategory->category->category_name : '-' }}</td>
                                <td><span class="mess-row-title">{{ $itemsubcategory->item_name }}</span></td>
                                <td>{{ $itemsubcategory->item_code ?? '-' }}</td>
                                <td>{{ $itemsubcategory->unit_measurement ?? '-' }}</td>
                                <td>{{ isset($itemsubcategory->alert_quantity) && $itemsubcategory->alert_quantity !== null && $itemsubcategory->alert_quantity !== '' ? number_format($itemsubcategory->alert_quantity, 2) : '-' }}</td>
                                <td>
                                    <span class="badge rounded-pill programme-status-badge mess-status-badge programme-status-badge--{{ $isItemSubcategoryActive($itemsubcategory) ? 'active' : 'inactive' }}">
                                        {{ $isItemSubcategoryActive($itemsubcategory) ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    @include('components.mess-master-action-cell', [
                                        'entityLabel' => 'item',
                                        'recordId' => $itemsubcategory->id,
                                        'isActive' => $isItemSubcategoryActive($itemsubcategory),
                                        'canDelete' => $canDeleteItemSubcategory,
                                        'destroyUrl' => route('admin.mess.itemsubcategories.destroy', $itemsubcategory->id),
                                        'toggleTable' => 'mess_item_subcategories',
                                        'editClass' => 'btn-edit-itemsubcategory',
                                        'editAttributes' => [
                                            'data-id' => $itemsubcategory->id,
                                            'data-category-id' => $itemsubcategory->category_id ?? '',
                                            'data-item-name' => e($itemsubcategory->item_name),
                                            'data-item-code' => e($itemsubcategory->item_code ?? ''),
                                            'data-unit-measurement' => e($itemsubcategory->unit_measurement ?? ''),
                                            'data-alert-quantity' => $itemsubcategory->alert_quantity ?? '',
                                            'data-description' => e($itemsubcategory->description ?? ''),
                                            'data-status' => e($itemsubcategory->status ?? 'active'),
                                        ],
                                    ])
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="mess-empty-state text-center">
                                    <i class="bi bi-box display-4 text-secondary opacity-50 d-block mb-3" aria-hidden="true"></i>
                                    <h5 class="fw-semibold text-dark mb-1">No Items Found</h5>
                                    <p class="text-secondary mb-0">Add a subcategory item to get started.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div id="iscDtFooter"
                    class="programme-dt-footer mess-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 pt-3"
                    data-dt-footer-for="itemSubcategoriesTable"></div>
            </div>
        </div>
    </div>
</div>

{{-- Tom Select CSS --}}
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">

{{-- Tom Select JS --}}
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

{{-- Create Item Modal --}}
<div class="modal fade" id="createItemSubcategoryModal" tabindex="-1" aria-labelledby="createItemSubcategoryModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable mess-master-modal-dialog--lg">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4 mess-modal-form">
            <form method="POST" action="{{ route('admin.mess.itemsubcategories.store') }}">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold mb-0" id="createItemSubcategoryModalLabel">Add Subcategory Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="create_category_id" class="form-select form-select-sm js-choices" data-placeholder="Select Category" required>
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
                            <select name="status" id="create_status" class="form-select form-select-sm js-choices">
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
                <div class="modal-footer border-0 pt-0 gap-2">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Subcategory Item Modal --}}
<div class="modal fade" id="editItemSubcategoryModal" tabindex="-1" aria-labelledby="editItemSubcategoryModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable mess-master-modal-dialog--lg">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4 mess-modal-form">
            <form id="editItemSubcategoryForm" method="POST" action="{{ $openEditModal && old('item_subcategory_modal_id') ? route('admin.mess.itemsubcategories.update', old('item_subcategory_modal_id')) : '' }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="item_subcategory_modal_id" id="edit_item_subcategory_modal_id" value="{{ old('item_subcategory_modal_id', $editItemSubcategory?->id ?? '') }}">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold mb-0" id="editItemSubcategoryModalLabel">Edit Subcategory Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-6">
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
                <div class="modal-footer border-0 pt-0 gap-2">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('components.mess-master-datatables', [
    'tableId' => 'itemSubcategoriesTable',
    'searchPlaceholder' => 'Search',
    'orderColumn' => 2,
    'actionColumnIndex' => 7,
    'infoLabel' => 'items',
    'pageLength' => 10,
])
@push('scripts')
<script src="{{ asset('js/mess-master-list.js') }}?v={{ @filemtime(public_path('js/mess-master-list.js')) ?: time() }}"></script>
<script>
(function () {
function initItemSubcategoryScripts() {
    var tableSelector = '#itemSubcategoriesTable';
    var canDelete = @json($canDeleteItemSubcategory);
    var destroyBaseUrl = @json(url('admin/mess/itemsubcategories'));
    var ML = window.MessMasterList;

    if (ML) {
        ML.moveModalsToBody(['createItemSubcategoryModal', 'editItemSubcategoryModal']);
        ML.wireModalExclusivity([{ create: 'createItemSubcategoryModal', edit: 'editItemSubcategoryModal' }]);
        ML.bindMessStatusToggle(tableSelector, {
            entityLabel: 'item',
            canDelete: canDelete,
            destroyBaseUrl: destroyBaseUrl
        });
    }

    // Validation rules (must match ItemSubcategoryController)
    var itemNameRegex = /^[a-zA-Z0-9\s\-]+$/;
    var unitMeasurementRegex = /^[a-zA-Z0-9\s\-\/\.]+$/;
    var itemNameMessage = 'Item name may only contain letters, numbers, spaces and hyphens. Special characters are not allowed.';
    var unitMeasurementMessage = 'Unit measurement may only contain letters, numbers, spaces, hyphens, slashes and periods. Special characters are not allowed.';

    // Initialize Tom Select on all dropdowns (filter + create + edit)
    if (window.TomSelect) {
        var dropdowns = [
            // Filter dropdown: keep value on open
            { id: 'filter_category_id', placeholder: 'All categories', clearOnInit: false, clearOnOpen: false },
            // Create dropdowns: blank on init + every open
            { id: 'create_category_id', placeholder: 'Select category', clearOnInit: true, clearOnOpen: true },
            { id: 'create_status', placeholder: 'Select status', clearOnInit: true, clearOnOpen: true },
            // Edit dropdowns: show saved value initially, but clear when user opens to pick new
            { id: 'edit_category_id', placeholder: 'Select category', clearOnInit: false, clearOnOpen: true },
            { id: 'edit_status', placeholder: 'Select status', clearOnInit: false, clearOnOpen: true }
        ];

        dropdowns.forEach(function (cfg) {
            var el = document.getElementById(cfg.id);
            if (!el) return;
            if (el.tomselect) {
                el.tomselect.destroy();
            }
            try {
                var hadValue = !!el.value;
                var clearOnOpen = !!cfg.clearOnOpen;
                var ts = new TomSelect(el, {
                    allowEmptyOption: true,
                    create: false,
                    dropdownParent: 'body',
                    placeholder: cfg.placeholder,
                    maxOptions: null,
                    hideSelected: false,
                    highlight: false,
                    onInitialize: function () {
                        // Dont auto-highlight first option
                        this.activeOption = null;
                    },
                    onDropdownOpen: function (dropdown) {
                        // Search input cursor always at start
                        var input = this.control_input || dropdown.querySelector('input');
                        if (input) {
                            input.value = '';
                            setTimeout(function () {
                                input.focus();
                                try {
                                    input.setSelectionRange(0, 0);
                                } catch (e) {}
                                input.scrollLeft = 0;
                            }, 0);
                        }

                        // User ne dropdown open kiya hai: agar is config me clearOnOpen true hai
                        // to pehle se selected value hata do taaki fresh selection mile
                        if (clearOnOpen) {
                            this.clear(true);
                        }

                        // Remove visual active/selected option in dropdown list
                        setTimeout(function () {
                            var opts = dropdown.querySelectorAll('.option.active, .option.selected, .option[aria-selected="true"]');
                            opts.forEach(function (opt) {
                                opt.classList.remove('active');
                                opt.classList.remove('selected');
                                opt.setAttribute('aria-selected', 'false');
                            });
                        }, 0);
                    }
                });

                // Sirf jahan clearOnInit true ho aur koi value na ho, wahan hi init par clear karein
                if (cfg.clearOnInit && !hadValue) {
                    ts.clear(true);
                }
            } catch (e) {
                console.error('Tom Select init failed for', cfg.id, e);
            }
        });
    }

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

    // Initialize Choices.js on create & edit modal selects
    var choicesInstances = {};
    function createChoicesInstance(selectEl, key) {
        if (!window.Choices || !selectEl) return null;
        if (choicesInstances[key]) {
            choicesInstances[key].destroy();
        }
        choicesInstances[key] = new Choices(selectEl, {
            searchEnabled: true,
            itemSelectText: '',
            shouldSort: false
        });
        return choicesInstances[key];
    }

    // Initial setup for filter + create modal selects
    createChoicesInstance(document.getElementById('filter_category_id'), 'filterCategory');
    createChoicesInstance(document.getElementById('create_category_id'), 'createCategory');
    createChoicesInstance(document.getElementById('create_status'), 'createStatus');

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-edit-itemsubcategory');
        if (!btn || !btn.closest('#itemSubcategoriesTable')) return;
        e.preventDefault();
        e.stopPropagation();
        if (ML) ML.hideMessModal('createItemSubcategoryModal');
        var id = btn.getAttribute('data-id');
        document.getElementById('editItemSubcategoryForm').action = destroyBaseUrl + '/' + id;
        var modalIdInput = document.getElementById('edit_item_subcategory_modal_id');
        if (modalIdInput) modalIdInput.value = id;
        var categoryId = (btn.getAttribute('data-category-id') || '').toString().trim();
        var statusVal = (btn.getAttribute('data-status') || 'active').toString().trim();

        var editCategorySelect = document.getElementById('edit_category_id');
        var editStatusSelect = document.getElementById('edit_status');

        if (editCategorySelect) {
            editCategorySelect.value = categoryId;
            if (editCategorySelect.tomselect) {
                editCategorySelect.tomselect.setValue(categoryId || '', true);
            }
        }
        document.getElementById('edit_item_name').value = btn.getAttribute('data-item-name') || '';
        document.getElementById('edit_item_code_display').value = btn.getAttribute('data-item-code') || '-';
        document.getElementById('edit_unit_measurement').value = btn.getAttribute('data-unit-measurement') || '';
        document.getElementById('edit_alert_quantity').value = btn.getAttribute('data-alert-quantity') || '';
        document.getElementById('edit_description').value = btn.getAttribute('data-description') || '';

        if (editStatusSelect) {
            editStatusSelect.value = statusVal;
            if (editStatusSelect.tomselect) {
                editStatusSelect.tomselect.setValue(statusVal || '', true);
            }
        }
        if (ML) {
            ML.showMessModal('editItemSubcategoryModal');
        } else {
            new bootstrap.Modal(document.getElementById('editItemSubcategoryModal')).show();
        }
    });

    @if($openCreateModal)
    if (ML) ML.showMessModal('createItemSubcategoryModal');
    @endif
    @if($openEditModal)
    (function () {
        var editId = document.getElementById('edit_item_subcategory_modal_id');
        if (editId && editId.value) {
            document.getElementById('editItemSubcategoryForm').action = destroyBaseUrl + '/' + editId.value;
            @if($editItemSubcategory)
            var ec = document.getElementById('edit_category_id');
            if (ec) {
                ec.value = @json((string) ($editItemSubcategory->category_id ?? ''));
                if (ec.tomselect) ec.tomselect.setValue(@json((string) ($editItemSubcategory->category_id ?? '')), true);
            }
            document.getElementById('edit_item_name').value = @json($editItemSubcategory->item_name ?? '');
            document.getElementById('edit_item_code_display').value = @json($editItemSubcategory->item_code ?? '-');
            document.getElementById('edit_unit_measurement').value = @json($editItemSubcategory->unit_measurement ?? '');
            document.getElementById('edit_alert_quantity').value = @json($editItemSubcategory->alert_quantity ?? '');
            document.getElementById('edit_description').value = @json($editItemSubcategory->description ?? '');
            var es = document.getElementById('edit_status');
            if (es) {
                es.value = @json($editItemSubcategory->status ?? 'active');
                if (es.tomselect) es.tomselect.setValue(@json($editItemSubcategory->status ?? 'active'), true);
            }
            @endif
        }
        if (ML) ML.showMessModal('editItemSubcategoryModal');
    })();
    @endif
}
// Run immediately if DOM is already ready, otherwise wait for DOMContentLoaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initItemSubcategoryScripts);
} else {
    initItemSubcategoryScripts();
}
})(); 
</script>
@endpush

<style>
/* Ensure Tom Select dropdowns appear above modals/backdrop */
.ts-dropdown {
    z-index: 10000 !important;
}
.ts-control {
    z-index: 1;
}
</style>
@endsection
