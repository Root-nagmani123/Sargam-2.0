@extends('admin.layouts.master')
@section('title', 'Subcategory Item Master')
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
<div class="container-fluid mess-master-page">
    <x-breadcrum title="Subcategory Item Master">
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold text-nowrap shadow-sm"
            data-bs-toggle="modal" data-bs-target="#createItemSubcategoryModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Subcategory Item</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card mess-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">

            {{-- Filter bar matching reference design --}}
            <div class="isc-filter-bar">
                <div class="isc-filter-left">
                    <span class="isc-filter-label">Filters</span>
                    <form method="GET" action="{{ route('admin.mess.itemsubcategories.index') }}" class="itemsubcategories-filter-form mess-filter-bar d-inline-flex align-items-center gap-2 mb-0">
                        <select name="category_id" id="filter_category_id" class="isc-filter-select js-choices" data-placeholder="All" onchange="this.form.submit()">
                            <option value="">Category Type</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ (string) $selectedCategoryId === (string) $cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>
                            @endforeach
                        </select>
                    </form>
                    <a href="{{ route('admin.mess.itemsubcategories.index') }}" class="isc-reset-btn">Reset Filters</a>
                </div>
                <div class="isc-filter-right">
                    <div id="messColManagerMount-itemSubcategoriesTable" class="flex-shrink-0"></div>
                    <div id="iscDtSearch" class="programme-dt-search" data-dt-search-for="itemSubcategoriesTable"></div>
                </div>
            </form>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table id="itemSubcategoriesTable" class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Item Name</th>
                            <th>Item Code</th>
                            <th>Unit Measurement</th>
                            <th>Alert Qty</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
</div>

{{-- Tom Select CSS --}}
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">

{{-- Tom Select JS --}}
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

{{-- Create Item Modal --}}
<div class="modal fade" id="createItemSubcategoryModal" tabindex="-1" aria-labelledby="createItemSubcategoryModalLabel"
    aria-hidden="true" data-bs-backdrop="static">
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
                            <select name="category_id" id="create_category_id"
                                class="form-select form-select-sm js-choices" data-placeholder="Select Category"
                                required>
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->category_name }}</option>
                                @endforeach
                            </select>
                            @error('category_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Item Name <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" id="create_item_name" class="form-control" required
                                value="{{ old('item_name') }}" pattern="[a-zA-Z0-9\s\-]+" autocomplete="off">
                            <div class="text-danger small mt-1" id="create_item_name_error" role="alert">
                                @error('item_name'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Item Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control bg-light" value="" readonly
                                placeholder="Auto-generated on save">
                            <small class="text-muted">Mandatory. Auto-generated.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit Measurement <span class="text-danger">*</span></label>
                            <input type="text" name="unit_measurement" id="create_unit_measurement" class="form-control"
                                value="{{ old('unit_measurement') }}" placeholder="e.g., kg, liter, piece" required
                                pattern="[a-zA-Z0-9\s\-\/\.]+" autocomplete="off">
                            <div class="text-danger small mt-1" id="create_unit_measurement_error" role="alert">
                                @error('unit_measurement'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Alert Quantity (min. stock)</label>
                            <input type="number" name="alert_quantity" class="form-control" step="0.0001" min="0"
                                value="{{ old('alert_quantity') }}" placeholder="Optional">
                            <small class="text-muted">Low stock alert when remaining &le; this</small>
                            @error('alert_quantity')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" id="create_status" class="form-select form-select-sm js-choices">
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>
                                    Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive
                                </option>
                            </select>
                            @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control"
                                rows="3">{{ old('description') }}</textarea>
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
<div class="modal fade" id="editItemSubcategoryModal" tabindex="-1" aria-labelledby="editItemSubcategoryModalLabel"
    aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable mess-master-modal-dialog--lg">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4 mess-modal-form">
            <form id="editItemSubcategoryForm" method="POST"
                action="{{ $openEditModal && old('item_subcategory_modal_id') ? route('admin.mess.itemsubcategories.update', old('item_subcategory_modal_id')) : '' }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="item_subcategory_modal_id" id="edit_item_subcategory_modal_id"
                    value="{{ old('item_subcategory_modal_id', $editItemSubcategory?->id ?? '') }}">
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
                            <input type="text" name="unit_measurement" id="edit_unit_measurement" class="form-control"
                                required pattern="[a-zA-Z0-9\s\-\/\.]+" autocomplete="off">
                            <div class="text-danger small mt-1" id="edit_unit_measurement_error" role="alert"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Alert Quantity (min. stock)</label>
                            <input type="number" name="alert_quantity" id="edit_alert_quantity" class="form-control"
                                step="0.0001" min="0" placeholder="Optional">
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
    'searchPlaceholder' => 'Search items...',
    'orderColumn' => 1,
    'actionColumnIndex' => 6,
    'infoLabel' => 'subcategory items',
    'serverSide' => true,
    'ajaxUrlBase' => route('admin.mess.itemsubcategories.index'),
])
@push('scripts')
<script src="{{ asset('js/mess-master-list.js') }}?v={{ @filemtime(public_path('js/mess-master-list.js')) ?: time() }}">
</script>
<script>
(function() {
    function initItemSubcategoryScripts() {
        var tableSelector = '#itemSubcategoriesTable';
        var canDelete = @json($canDeleteItemSubcategory);
        var destroyBaseUrl = @json(url('admin/mess/itemsubcategories'));
        var ML = window.MessMasterList;

        if (ML) {
            ML.moveModalsToBody(['createItemSubcategoryModal', 'editItemSubcategoryModal']);
            ML.wireModalExclusivity([{
                create: 'createItemSubcategoryModal',
                edit: 'editItemSubcategoryModal'
            }]);
            ML.bindMessStatusToggle(tableSelector, {
                entityLabel: 'item',
                canDelete: canDelete,
                destroyBaseUrl: destroyBaseUrl
            });
        }

        // Validation rules (must match ItemSubcategoryController)
        var itemNameRegex = /^[a-zA-Z0-9\s\-]+$/;
        var unitMeasurementRegex = /^[a-zA-Z0-9\s\-\/\.]+$/;
        var itemNameMessage =
            'Item name may only contain letters, numbers, spaces and hyphens. Special characters are not allowed.';
        var unitMeasurementMessage =
            'Unit measurement may only contain letters, numbers, spaces, hyphens, slashes and periods. Special characters are not allowed.';

        // Initialize Tom Select on all dropdowns (filter + create + edit)
        if (window.TomSelect) {
            var dropdowns = [
                // Filter dropdown: keep value on open
                {
                    id: 'filter_category_id',
                    placeholder: 'All categories',
                    clearOnInit: false,
                    clearOnOpen: false
                },
                // Create dropdowns: blank on init + every open
                {
                    id: 'create_category_id',
                    placeholder: 'Select category',
                    clearOnInit: true,
                    clearOnOpen: true
                },
                {
                    id: 'create_status',
                    placeholder: 'Select status',
                    clearOnInit: true,
                    clearOnOpen: true
                },
                // Edit dropdowns: show saved value initially, but clear when user opens to pick new
                {
                    id: 'edit_category_id',
                    placeholder: 'Select category',
                    clearOnInit: false,
                    clearOnOpen: true
                },
                {
                    id: 'edit_status',
                    placeholder: 'Select status',
                    clearOnInit: false,
                    clearOnOpen: true
                }
            ];

            dropdowns.forEach(function(cfg) {
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
                        onInitialize: function() {
                            // Dont auto-highlight first option
                            this.activeOption = null;
                        },
                        onDropdownOpen: function(dropdown) {
                            // Search input cursor always at start
                            var input = this.control_input || dropdown.querySelector('input');
                            if (input) {
                                input.value = '';
                                setTimeout(function() {
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
                            setTimeout(function() {
                                var opts = dropdown.querySelectorAll(
                                    '.option.active, .option.selected, .option[aria-selected="true"]'
                                    );
                                opts.forEach(function(opt) {
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
            if (typeof value !== 'string') return {
                valid: true
            };
            value = value.trim();
            if (value.length === 0) return {
                valid: false,
                message: 'Item name is required.'
            };
            return itemNameRegex.test(value) ? {
                valid: true
            } : {
                valid: false,
                message: itemNameMessage
            };
        }

        function validateUnitMeasurement(value) {
            if (typeof value !== 'string') return {
                valid: true
            };
            value = value.trim();
            if (value.length === 0) return {
                valid: false,
                message: 'Unit measurement is required.'
            };
            return unitMeasurementRegex.test(value) ? {
                valid: true
            } : {
                valid: false,
                message: unitMeasurementMessage
            };
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
                showLiveError(document.getElementById('create_item_name'), document.getElementById(
                    'create_item_name_error'), r1);
                showLiveError(document.getElementById('create_unit_measurement'), document.getElementById(
                    'create_unit_measurement_error'), r2);
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
                showLiveError(document.getElementById('edit_item_name'), document.getElementById(
                    'edit_item_name_error'), r1);
                showLiveError(document.getElementById('edit_unit_measurement'), document.getElementById(
                    'edit_unit_measurement_error'), r2);
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
            document.getElementById('edit_item_code_display').value = btn.getAttribute('data-item-code') ||
                '-';
            document.getElementById('edit_unit_measurement').value = btn.getAttribute(
                'data-unit-measurement') || '';
            document.getElementById('edit_alert_quantity').value = btn.getAttribute(
                'data-alert-quantity') || '';
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
            (function() {
                var editId = document.getElementById('edit_item_subcategory_modal_id');
                if (editId && editId.value) {
                    document.getElementById('editItemSubcategoryForm').action = destroyBaseUrl + '/' + editId
                        .value;
                    @if($editItemSubcategory)
                    var ec = document.getElementById('edit_category_id');
                    if (ec) {
                        ec.value = @json((string)($editItemSubcategory->category_id ?? ''));
                        if (ec.tomselect) ec.tomselect.setValue(@json((string)($editItemSubcategory->category_id ?? '')), true);
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