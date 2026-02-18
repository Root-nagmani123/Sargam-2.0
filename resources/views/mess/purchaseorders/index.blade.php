@extends('admin.layouts.master')
@section('title', 'Purchase Orders')
@section('setup_content')
<div class="container-fluid">
    <div class="datatables">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">Purchase Orders</h4>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPurchaseOrderModal">
                        Create Purchase Order
                    </button>
                </div>
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                <div class="table-responsive">
                    <table id="purchaseOrdersTable" class="table table-bordered table-hover align-middle w-100">
                        <thead>
                            <tr>
                                <th style="background-color: #004a93; color: #fff; border-color: #004a93; width: 60px;">S.No</th>
                                <th style="background-color: #004a93; color: #fff; border-color: #004a93;">Order Number</th>
                                <th style="background-color: #004a93; color: #fff; border-color: #004a93;">Vendor Name</th>
                                <th style="background-color: #004a93; color: #fff; border-color: #004a93;">Store Name</th>
                                <th style="background-color: #004a93; color: #fff; border-color: #004a93;">Status</th>
                                <th style="background-color: #004a93; color: #fff; border-color: #004a93; min-width: 180px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($purchaseOrders as $po)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $po->po_number }}</td>
                                <td>{{ $po->vendor->name ?? 'N/A' }}</td>
                                <td>{{ $po->store->store_name ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-{{ $po->status == 'approved' ? 'success' : ($po->status == 'rejected' ? 'danger' : ($po->status == 'completed' ? 'primary' : 'warning')) }}">
                                        {{ ucfirst($po->status) }}
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info btn-view-po" data-po-id="{{ $po->id }}" title="View">View</button>
                                    <button type="button" class="btn btn-sm btn-warning btn-edit-po" data-po-id="{{ $po->id }}" title="Edit">Edit</button>
                                    <form action="{{ route('admin.mess.purchaseorders.destroy', $po->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this purchase order?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete" style="display: none;">Delete</button>
                                    </form>
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

@include('components.mess-master-datatables', [
    'tableId' => 'purchaseOrdersTable',
    'searchPlaceholder' => 'Search purchase orders...',
    'orderColumn' => 1,
    'actionColumnIndex' => 5,
    'infoLabel' => 'purchase orders'
])

{{-- Create Purchase Order Modal --}}
<style>
#createPurchaseOrderModal .modal-dialog {
    max-height: calc(100vh - 2rem);
    margin: 1rem auto;
}
#createPurchaseOrderModal .modal-content {
    max-height: calc(100vh - 2rem);
    display: flex;
    flex-direction: column;
}
#createPurchaseOrderModal .modal-body {
    overflow-y: auto;
    max-height: calc(100vh - 10rem);
}
#editPurchaseOrderModal .modal-dialog { max-height: calc(100vh - 2rem); margin: 1rem auto; }
#editPurchaseOrderModal .modal-content { max-height: calc(100vh - 2rem); display: flex; flex-direction: column; }
#editPurchaseOrderModal .modal-body { overflow-y: auto; max-height: calc(100vh - 10rem); }
#viewPurchaseOrderModal .modal-dialog { max-height: calc(100vh - 2rem); margin: 1rem auto; }
#viewPurchaseOrderModal .modal-content { max-height: calc(100vh - 2rem); display: flex; flex-direction: column; }
#viewPurchaseOrderModal .modal-body { overflow-y: auto; max-height: calc(100vh - 10rem); }
</style>
<div class="modal fade" id="createPurchaseOrderModal" tabindex="-1" aria-labelledby="createPurchaseOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.mess.purchaseorders.store') }}" id="createPOForm">
                @csrf
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="createPurchaseOrderModalLabel">Create Purchase Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="po_number" value="{{ $po_number }}">

                    {{-- Order Details --}}
                    <div class="card mb-4">
                        <div class="card-header bg-white py-2">
                            <h6 class="mb-0 fw-semibold text-primary">Order Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Order Number</label>
                                    <input type="text" class="form-control bg-light" value="{{ $po_number }}" readonly placeholder="Auto-generated">
                                    <small class="text-muted">Auto-generated</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Order Date <span class="text-danger">*</span></label>
                                    <input type="date" name="po_date" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Store Name</label>
                                    <select name="store_id" class="form-select">
                                        <option value="">Select Store</option>
                                        @foreach($stores as $store)
                                            <option value="{{ $store->id }}">{{ $store->store_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Vendor Name <span class="text-danger">*</span></label>
                                    <select name="vendor_id" class="form-select" required>
                                        <option value="">Select Vendor</option>
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Payment Mode</label>
                                    <select name="payment_code" class="form-select">
                                        <option value="">Select Payment Mode</option>
                                        @foreach($paymentModes as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Contact Number</label>
                                    <input type="text" name="contact_number" class="form-control" placeholder="Contact number">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Delivery Address <small class="text-muted">(Optional)</small></label>
                                    <textarea name="delivery_address" class="form-control" rows="2" placeholder="Delivery address"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Item Details --}}
                    <div class="card mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                            <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addPoItemRow">
                                + Add Item
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0" id="poItemsTable">
                                    <thead style="background-color: #af2910;">
                                        <tr>
                                            <th style="min-width: 180px; color: #fff; border-color: #af2910;">Item Name <span class="text-white">*</span></th>
                                            <th style="min-width: 80px; color: #fff; border-color: #af2910;">Unit</th>
                                            <th style="min-width: 100px; color: #fff; border-color: #af2910;">Item Code</th>
                                            <th style="min-width: 90px; color: #fff; border-color: #af2910;">Quantity <span class="text-white">*</span></th>
                                            <th style="min-width: 100px; color: #fff; border-color: #af2910;">Unit Price <span class="text-white">*</span></th>
                                            <th style="min-width: 80px; color: #fff; border-color: #af2910;">Tax (%)</th>
                                            <th style="min-width: 110px; color: #fff; border-color: #af2910;">Total Amount</th>
                                            <th style="width: 50px; color: #fff; border-color: #af2910;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="poItemsBody">
                                        <tr class="po-item-row">
                                            <td>
                                                <select name="items[0][item_subcategory_id]" class="form-select form-select-sm po-item-select" required>
                                                    <option value="">Select Item</option>
                                                    @foreach($itemSubcategories as $sub)
                                                        <option value="{{ $sub['id'] }}" data-unit="{{ e($sub['unit_measurement']) }}" data-code="{{ e($sub['item_code']) }}">{{ $sub['item_name'] }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="text" name="items[0][unit]" class="form-control form-control-sm po-unit" readonly placeholder="—"></td>
                                            <td><input type="text" name="items[0][item_code_display]" class="form-control form-control-sm po-item-code" readonly placeholder="—"></td>
                                            <td><input type="number" name="items[0][quantity]" class="form-control form-control-sm po-qty" step="0.01" min="0.01" placeholder="0" required></td>
                                            <td><input type="number" name="items[0][unit_price]" class="form-control form-control-sm po-unit-price" step="0.01" min="0" placeholder="0" required></td>
                                            <td><input type="number" name="items[0][tax_percent]" class="form-control form-control-sm po-tax" step="0.01" min="0" max="100" value="0" placeholder="0"></td>
                                            <td><input type="text" name="items[0][total_display]" class="form-control form-control-sm po-line-total bg-light" readonly placeholder="0.00"></td>
                                            <td><button type="button" class="btn btn-sm btn-outline-danger po-remove-row" disabled title="Remove">×</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-light d-flex justify-content-end align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-semibold">Grand Total:</span>
                                <span class="fs-5 text-primary fw-bold" id="poGrandTotal">₹0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Purchase Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Purchase Order Modal --}}
<div class="modal fade" id="editPurchaseOrderModal" tabindex="-1" aria-labelledby="editPurchaseOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" id="editPOForm" action="">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="editPurchaseOrderModalLabel">Edit Purchase Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card mb-4">
                        <div class="card-header bg-white py-2">
                            <h6 class="mb-0 fw-semibold text-primary">Order Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Order Number</label>
                                    <input type="text" id="editPoNumber" class="form-control bg-light" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Order Date <span class="text-danger">*</span></label>
                                    <input type="date" name="po_date" id="editPoDate" class="form-control" max="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Store Name</label>
                                    <select name="store_id" id="editStoreId" class="form-select">
                                        <option value="">Select Store</option>
                                        @foreach($stores as $store)
                                            <option value="{{ $store->id }}">{{ $store->store_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Vendor Name <span class="text-danger">*</span></label>
                                    <select name="vendor_id" id="editVendorId" class="form-select" required>
                                        <option value="">Select Vendor</option>
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Payment Mode</label>
                                    <select name="payment_code" id="editPaymentCode" class="form-select">
                                        <option value="">Select Payment Mode</option>
                                        @foreach($paymentModes as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Contact Number</label>
                                    <input type="text" name="contact_number" id="editContactNumber" class="form-control">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Delivery Address <small class="text-muted">(Optional)</small></label>
                                    <textarea name="delivery_address" id="editDeliveryAddress" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                            <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addEditPoItemRow">+ Add Item</button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead style="background-color: #af2910;">
                                        <tr>
                                            <th style="min-width: 180px; color: #fff; border-color: #af2910;">Item Name <span class="text-white">*</span></th>
                                            <th style="min-width: 80px; color: #fff; border-color: #af2910;">Unit</th>
                                            <th style="min-width: 100px; color: #fff; border-color: #af2910;">Item Code</th>
                                            <th style="min-width: 90px; color: #fff; border-color: #af2910;">Quantity <span class="text-white">*</span></th>
                                            <th style="min-width: 100px; color: #fff; border-color: #af2910;">Unit Price <span class="text-white">*</span></th>
                                            <th style="min-width: 80px; color: #fff; border-color: #af2910;">Tax (%)</th>
                                            <th style="min-width: 110px; color: #fff; border-color: #af2910;">Total Amount</th>
                                            <th style="width: 50px; color: #fff; border-color: #af2910;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="editPoItemsBody"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-light d-flex justify-content-end align-items-center">
                            <span class="fw-semibold">Grand Total:</span>
                            <span class="fs-5 text-primary fw-bold ms-2" id="editPoGrandTotal">₹0.00</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Purchase Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- View Purchase Order Modal (read-only) --}}
<div class="modal fade" id="viewPurchaseOrderModal" tabindex="-1" aria-labelledby="viewPurchaseOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-semibold" id="viewPurchaseOrderModalLabel">Purchase Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="card mb-4">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0 fw-semibold text-primary">Order Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label text-muted small">Order Number</label>
                                <p class="mb-0 fw-medium" id="viewPoNumber">—</p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small">Order Date</label>
                                <p class="mb-0 fw-medium" id="viewPoDate">—</p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small">Store Name</label>
                                <p class="mb-0 fw-medium" id="viewStoreName">—</p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small">Vendor Name</label>
                                <p class="mb-0 fw-medium" id="viewVendorName">—</p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small">Payment Mode</label>
                                <p class="mb-0 fw-medium" id="viewPaymentCode">—</p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small">Contact Number</label>
                                <p class="mb-0 fw-medium" id="viewContactNumber">—</p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small">Status</label>
                                <p class="mb-0"><span class="badge" id="viewStatus">—</span></p>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label text-muted small">Delivery Address</label>
                                <p class="mb-0 fw-medium" id="viewDeliveryAddress">—</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead style="background-color: #af2910;">
                                    <tr>
                                        <th style="color: #fff; border-color: #af2910;">Item Name</th>
                                        <th style="color: #fff; border-color: #af2910;">Unit</th>
                                        <th style="color: #fff; border-color: #af2910;">Item Code</th>
                                        <th style="color: #fff; border-color: #af2910;">Quantity</th>
                                        <th style="color: #fff; border-color: #af2910;">Unit Price</th>
                                        <th style="color: #fff; border-color: #af2910;">Tax (%)</th>
                                        <th style="color: #fff; border-color: #af2910;">Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="viewPoItemsBody"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-light d-flex justify-content-end align-items-center">
                        <span class="fw-semibold">Grand Total:</span>
                        <span class="fs-5 text-primary fw-bold ms-2" id="viewPoGrandTotal">₹0.00</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    let itemSubcategories = @json($itemSubcategories);
    let filteredItems = itemSubcategories;
    let editModalItems = null;
    const editPoBaseUrl = "{{ url('admin/mess/purchaseorders') }}";
    let itemRowIndex = 1;
    let editItemRowIndex = 0;
    let currentVendorId = null;
    let editCurrentVendorId = null;

    function getItemRowHtml(index, editItem, isEditModal) {
        const selected = editItem && editItem.item_subcategory_id ? editItem.item_subcategory_id : '';
        const itemsToUse = isEditModal ? (editModalItems && editModalItems.length ? editModalItems : itemSubcategories) : filteredItems;
        const options = itemsToUse.map(s =>
            `<option value="${s.id}" data-unit="${(s.unit_measurement || '').replace(/"/g, '&quot;')}" data-code="${(s.item_code || '').replace(/"/g, '&quot;')}" ${s.id == selected ? 'selected' : ''}>${(s.item_name || '—').replace(/</g, '&lt;')}</option>`
        ).join('');
        const qty = editItem ? editItem.quantity : '';
        const price = editItem ? editItem.unit_price : '';
        const tax = editItem ? editItem.tax_percent : '0';
        const unit = editItem && editItem.unit ? editItem.unit.replace(/"/g, '&quot;') : '';
        const code = editItem && editItem.item_code ? editItem.item_code.replace(/"/g, '&quot;') : '';
        const lineTotal = editItem ? editItem.total_price : '';
        return `
        <tr class="po-item-row ${isEditModal ? 'edit-po-item-row' : ''}">
            <td>
                <select name="items[${index}][item_subcategory_id]" class="form-select form-select-sm po-item-select" required>
                    <option value="">Select Item</option>
                    ${options}
                </select>
            </td>
            <td><input type="text" name="items[${index}][unit]" class="form-control form-control-sm po-unit" readonly placeholder="—" value="${unit}"></td>
            <td><input type="text" class="form-control form-control-sm po-item-code" readonly placeholder="—" value="${code}"></td>
            <td><input type="number" name="items[${index}][quantity]" class="form-control form-control-sm po-qty" step="0.01" min="0.01" placeholder="0" value="${qty}" required></td>
            <td><input type="number" name="items[${index}][unit_price]" class="form-control form-control-sm po-unit-price" step="0.01" min="0" placeholder="0" value="${price}" required></td>
            <td><input type="number" name="items[${index}][tax_percent]" class="form-control form-control-sm po-tax" step="0.01" min="0" max="100" value="${tax}" placeholder="0"></td>
            <td><input type="text" class="form-control form-control-sm po-line-total bg-light" readonly placeholder="0.00" value="${lineTotal}"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger po-remove-row" title="Remove">×</button></td>
        </tr>`;
    }

    function fetchVendorItems(vendorId, callback) {
        if (!vendorId) {
            filteredItems = itemSubcategories;
            if (callback) callback();
            return;
        }
        
        fetch(`{{ url('admin/mess/purchaseorders/vendor') }}/${vendorId}/items`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            filteredItems = data;
            if (callback) callback();
        })
        .catch(err => {
            console.error(err);
            alert('Failed to load vendor items.');
            filteredItems = [];
        });
    }

    function updateItemDropdowns(tbody, isEditModal) {
        const rows = tbody.querySelectorAll('.po-item-row');
        const itemsToUse = isEditModal ? (editModalItems && editModalItems.length ? editModalItems : itemSubcategories) : filteredItems;
        
        rows.forEach(row => {
            const select = row.querySelector('.po-item-select');
            if (!select) return;
            
            const currentValue = select.value;
            select.innerHTML = '<option value="">Select Item</option>';
            
            itemsToUse.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.item_name || '—';
                option.setAttribute('data-unit', item.unit_measurement || '');
                option.setAttribute('data-code', item.item_code || '');
                if (item.id == currentValue) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
            
            // Update unit and code after dropdown refresh
            updateUnitAndCode(row);
        });
    }

    function updateUnitAndCode(row) {
        const select = row.querySelector('.po-item-select');
        const opt = select && select.options[select.selectedIndex];
        const unitInput = row.querySelector('.po-unit');
        const codeInput = row.querySelector('.po-item-code');
        if (unitInput) unitInput.value = opt && opt.dataset.unit ? opt.dataset.unit : '';
        if (codeInput) codeInput.value = opt && opt.dataset.code ? opt.dataset.code : '';
    }

    function calcLineTotal(row) {
        const qty = parseFloat(row.querySelector('.po-qty').value) || 0;
        const price = parseFloat(row.querySelector('.po-unit-price').value) || 0;
        const tax = parseFloat(row.querySelector('.po-tax').value) || 0;
        const total = qty * price * (1 + tax / 100);
        const totalInput = row.querySelector('.po-line-total');
        if (totalInput) totalInput.value = total.toFixed(2);
    }

    function updateGrandTotal() {
        let sum = 0;
        document.querySelectorAll('#poItemsBody .po-item-row').forEach(row => {
            const totalInput = row.querySelector('.po-line-total');
            if (totalInput && totalInput.value) sum += parseFloat(totalInput.value) || 0;
        });
        const el = document.getElementById('poGrandTotal');
        if (el) el.textContent = '₹' + sum.toFixed(2);
    }

    function updateRemoveButtons() {
        const rows = document.querySelectorAll('#poItemsBody .po-item-row');
        rows.forEach((row, i) => {
            const btn = row.querySelector('.po-remove-row');
            if (btn) btn.disabled = rows.length <= 1;
        });
    }

    // Vendor selection change in CREATE modal
    const createVendorSelect = document.querySelector('#createPurchaseOrderModal select[name="vendor_id"]');
    if (createVendorSelect) {
        createVendorSelect.addEventListener('change', function() {
            const vendorId = this.value;
            currentVendorId = vendorId;
            
            if (!vendorId) {
                filteredItems = itemSubcategories;
                const tbody = document.getElementById('poItemsBody');
                updateItemDropdowns(tbody, false);
                return;
            }
            
            fetchVendorItems(vendorId, function() {
                const tbody = document.getElementById('poItemsBody');
                updateItemDropdowns(tbody, false);
            });
        });
    }

    document.getElementById('addPoItemRow').addEventListener('click', function() {
        const tbody = document.getElementById('poItemsBody');
        tbody.insertAdjacentHTML('beforeend', getItemRowHtml(itemRowIndex, null, false));
        itemRowIndex++;
        updateRemoveButtons();
    });

    document.getElementById('poItemsBody').addEventListener('change', function(e) {
        // Some browsers/users (spinner, blur) trigger change more reliably than input
        if (
            e.target.classList.contains('po-item-select') ||
            e.target.classList.contains('po-qty') ||
            e.target.classList.contains('po-unit-price') ||
            e.target.classList.contains('po-tax')
        ) {
            const row = e.target.closest('.po-item-row');
            if (!row) return;
            if (e.target.classList.contains('po-item-select')) updateUnitAndCode(row);
            calcLineTotal(row);
            updateGrandTotal();
        }
    });

    document.getElementById('poItemsBody').addEventListener('input', function(e) {
        if (e.target.classList.contains('po-qty') || e.target.classList.contains('po-unit-price') || e.target.classList.contains('po-tax')) {
            const row = e.target.closest('.po-item-row');
            if (row) { calcLineTotal(row); updateGrandTotal(); }
        }
    });

    document.getElementById('poItemsBody').addEventListener('click', function(e) {
        if (e.target.classList.contains('po-remove-row')) {
            const row = e.target.closest('.po-item-row');
            if (row && document.querySelectorAll('#poItemsBody .po-item-row').length > 1) {
                row.remove();
                updateGrandTotal();
                updateRemoveButtons();
            }
        }
    });

    // Edit modal: grand total and remove buttons
    function updateEditGrandTotal() {
        let sum = 0;
        document.querySelectorAll('#editPoItemsBody .po-item-row').forEach(row => {
            const totalInput = row.querySelector('.po-line-total');
            if (totalInput && totalInput.value) sum += parseFloat(totalInput.value) || 0;
        });
        const el = document.getElementById('editPoGrandTotal');
        if (el) el.textContent = '₹' + sum.toFixed(2);
    }
    function updateEditRemoveButtons() {
        const rows = document.querySelectorAll('#editPoItemsBody .po-item-row');
        rows.forEach(row => {
            const btn = row.querySelector('.po-remove-row');
            if (btn) btn.disabled = rows.length <= 1;
        });
    }

    // View button: fetch PO and open view modal (read-only)
    document.querySelectorAll('.btn-view-po').forEach(btn => {
        btn.addEventListener('click', function() {
            const poId = this.getAttribute('data-po-id');
            fetch(editPoBaseUrl + '/' + poId + '/edit', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(data => {
                    const po = data.po;
                    const items = data.items || [];
                    document.getElementById('viewPoNumber').textContent = po.po_number || '—';
                    document.getElementById('viewPoDate').textContent = po.po_date ? new Date(po.po_date).toLocaleDateString('en-IN') : '—';
                    document.getElementById('viewStoreName').textContent = po.store_name || '—';
                    document.getElementById('viewVendorName').textContent = po.vendor_name || '—';
                    document.getElementById('viewPaymentCode').textContent = po.payment_code || '—';
                    document.getElementById('viewContactNumber').textContent = po.contact_number || '—';
                    document.getElementById('viewDeliveryAddress').textContent = po.delivery_address || '—';
                    const statusEl = document.getElementById('viewStatus');
                    statusEl.textContent = (po.status || '—').charAt(0).toUpperCase() + (po.status || '').slice(1);
                    statusEl.className = 'badge bg-' + (po.status === 'approved' ? 'success' : po.status === 'rejected' ? 'danger' : po.status === 'completed' ? 'primary' : 'warning');
                    const tbody = document.getElementById('viewPoItemsBody');
                    tbody.innerHTML = '';
                    let grandTotal = 0;
                    items.forEach(item => {
                        grandTotal += parseFloat(item.total_price) || 0;
                        tbody.insertAdjacentHTML('beforeend', `
                            <tr>
                                <td>${escapeHtml(item.item_name || '—')}</td>
                                <td>${escapeHtml(item.unit || '—')}</td>
                                <td>${escapeHtml(item.item_code || '—')}</td>
                                <td>${item.quantity}</td>
                                <td>₹${(parseFloat(item.unit_price) || 0).toFixed(2)}</td>
                                <td>${(parseFloat(item.tax_percent) || 0).toFixed(2)}%</td>
                                <td>₹${(parseFloat(item.total_price) || 0).toFixed(2)}</td>
                            </tr>`);
                    });
                    document.getElementById('viewPoGrandTotal').textContent = '₹' + grandTotal.toFixed(2);
                    new bootstrap.Modal(document.getElementById('viewPurchaseOrderModal')).show();
                })
                .catch(err => { console.error(err); alert('Failed to load purchase order.'); });
        });
    });

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Vendor selection change in EDIT modal: load vendor-mapped items and refresh dropdowns
    const editVendorSelect = document.querySelector('#editPurchaseOrderModal select[name="vendor_id"]');
    if (editVendorSelect) {
        editVendorSelect.addEventListener('change', function() {
            const vendorId = this.value;
            editCurrentVendorId = vendorId;
            const tbody = document.getElementById('editPoItemsBody');

            if (!vendorId) {
                editModalItems = itemSubcategories;
                updateItemDropdowns(tbody, true);
                return;
            }

            fetchVendorItems(vendorId, function() {
                const currentIds = [];
                tbody.querySelectorAll('.po-item-select').forEach(sel => {
                    const v = sel.value;
                    if (v) currentIds.push(v);
                });
                const merged = (filteredItems || []).slice();
                currentIds.forEach(id => {
                    if (id && !merged.some(m => m.id == id)) {
                        const fromAll = itemSubcategories.find(s => s.id == id);
                        if (fromAll) merged.push(fromAll);
                    }
                });
                editModalItems = merged.length ? merged : itemSubcategories;
                updateItemDropdowns(tbody, true);
            });
        });
    }

    // Edit button: fetch PO and open modal; use vendor-mapped items for dropdowns
    document.querySelectorAll('.btn-edit-po').forEach(btn => {
        btn.addEventListener('click', function() {
            const poId = this.getAttribute('data-po-id');
            fetch(editPoBaseUrl + '/' + poId + '/edit', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(data => {
                    const po = data.po;
                    const items = data.items || [];
                    document.getElementById('editPOForm').action = editPoBaseUrl + '/' + poId;
                    document.getElementById('editPoNumber').value = po.po_number || '';
                    document.getElementById('editPoDate').value = po.po_date || '';
                    document.getElementById('editStoreId').value = po.store_id || '';
                    document.getElementById('editVendorId').value = po.vendor_id || '';
                    document.getElementById('editPaymentCode').value = po.payment_code || '';
                    document.getElementById('editContactNumber').value = po.contact_number || '';
                    document.getElementById('editDeliveryAddress').value = po.delivery_address || '';
                    editCurrentVendorId = po.vendor_id;

                    function buildEditRows(vendorItemList) {
                        const merged = (vendorItemList || []).slice();
                        items.forEach(poItem => {
                            const id = poItem.item_subcategory_id;
                            if (id && !merged.some(m => m.id == id)) {
                                const fromAll = itemSubcategories.find(s => s.id == id);
                                if (fromAll) merged.push(fromAll);
                            }
                        });
                        editModalItems = merged.length ? merged : itemSubcategories;

                        const tbody = document.getElementById('editPoItemsBody');
                        tbody.innerHTML = '';
                        if (items.length === 0) {
                            tbody.insertAdjacentHTML('beforeend', getItemRowHtml(0, null, true));
                            editItemRowIndex = 1;
                        } else {
                            items.forEach((item, i) => {
                                tbody.insertAdjacentHTML('beforeend', getItemRowHtml(i, item, true));
                            });
                            editItemRowIndex = items.length;
                        }
                        updateEditGrandTotal();
                        updateEditRemoveButtons();
                        new bootstrap.Modal(document.getElementById('editPurchaseOrderModal')).show();
                    }

                    if (po.vendor_id) {
                        fetchVendorItems(po.vendor_id, function() {
                            buildEditRows(filteredItems);
                        });
                    } else {
                        editModalItems = itemSubcategories;
                        buildEditRows(itemSubcategories);
                    }
                })
                .catch(err => { console.error(err); alert('Failed to load purchase order.'); });
        });
    });

    document.getElementById('addEditPoItemRow').addEventListener('click', function() {
        const tbody = document.getElementById('editPoItemsBody');
        tbody.insertAdjacentHTML('beforeend', getItemRowHtml(editItemRowIndex, null, true));
        editItemRowIndex++;
        updateEditRemoveButtons();
    });

    document.getElementById('editPoItemsBody').addEventListener('change', function(e) {
        if (
            e.target.classList.contains('po-item-select') ||
            e.target.classList.contains('po-qty') ||
            e.target.classList.contains('po-unit-price') ||
            e.target.classList.contains('po-tax')
        ) {
            const row = e.target.closest('.po-item-row');
            if (!row) return;
            if (e.target.classList.contains('po-item-select')) updateUnitAndCode(row);
            calcLineTotal(row);
            updateEditGrandTotal();
        }
    });
    document.getElementById('editPoItemsBody').addEventListener('input', function(e) {
        if (e.target.classList.contains('po-qty') || e.target.classList.contains('po-unit-price') || e.target.classList.contains('po-tax')) {
            const row = e.target.closest('.po-item-row');
            if (row) { calcLineTotal(row); updateEditGrandTotal(); }
        }
    });
    document.getElementById('editPoItemsBody').addEventListener('click', function(e) {
        if (e.target.classList.contains('po-remove-row')) {
            const row = e.target.closest('.po-item-row');
            if (row && document.querySelectorAll('#editPoItemsBody .po-item-row').length > 1) {
                row.remove();
                updateEditGrandTotal();
                updateEditRemoveButtons();
            }
        }
    });

    // Enter key inside Item Details table triggers Add Item (and prevents form submit)
    const createPOModal = document.getElementById('createPurchaseOrderModal');
    const poItemsTable = document.getElementById('poItemsTable');
    if (createPOModal && poItemsTable) {
        createPOModal.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && poItemsTable.contains(document.activeElement)) {
                const addBtn = document.getElementById('addPoItemRow');
                if (addBtn) {
                    e.preventDefault();
                    addBtn.click();
                }
            }
        });
    }

    // Reset create modal when opened
    if (createPOModal) {
        createPOModal.addEventListener('show.bs.modal', function() {
            // Reset vendor selection
            currentVendorId = null;
            filteredItems = itemSubcategories;
            
            // Reset form
            const form = document.getElementById('createPOForm');
            if (form) {
                const vendorSelect = form.querySelector('select[name="vendor_id"]');
                if (vendorSelect) vendorSelect.value = '';
            }
        });
    }

})();
</script>
@endsection
