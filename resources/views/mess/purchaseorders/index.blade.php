@extends('admin.layouts.master')
@section('title', 'Purchase Orders')
@section('setup_content')
<div class="container-fluid py-3 py-md-4">
    <x-breadcrum title="Purchase Orders"></x-breadcrum>
    <div class="datatables">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white border-0 pb-0">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 no-print">
                    <div>
                        <h4 class="mb-1 fw-semibold">Purchase Orders</h4>
                        <p class="mb-0 text-muted small">View, filter and manage mess purchase orders.</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm px-3 d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#createPurchaseOrderModal">
                        <i class="material-icons material-symbol-rounded" style="font-size: 1.1rem;">add</i>
                        <span class="d-none d-sm-inline">Create Purchase Order</span>
                        <span class="d-inline d-sm-none">New</span>
                    </button>
                </div>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- Filters --}}
                <form method="GET" action="{{ route('admin.mess.purchaseorders.index') }}" class="mb-4 p-3 p-md-4 border rounded-3 bg-body-tertiary no-print">
                    <div class="row g-3 g-md-4 align-items-end mb-3">
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="form-label small mb-1">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ $filterDateFrom }}">
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="form-label small mb-1">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ $filterDateTo }}">
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="form-label small mb-1">Vendor</label>
                            <select name="vendor_id" class="form-select form-select-sm js-filter-select">
                                <option value="">All Vendors</option>
                                @foreach($vendors as $v)
                                    <option value="{{ $v->id }}" {{ (string)$filterVendorId === (string)$v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="form-label small mb-1">Store</label>
                            <select name="store_id" class="form-select form-select-sm js-filter-select">
                                <option value="">All Stores</option>
                                @foreach($stores as $s)
                                    <option value="{{ $s->id }}" {{ (string)$filterStoreId === (string)$s->id ? 'selected' : '' }}>{{ $s->store_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2 mt-3">
                            <button type="submit" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                                <i class="material-symbols-rounded" style="font-size: 1rem;">filter_list</i>
                                <span>Apply</span>
                            </button>
                            <a href="{{ route('admin.mess.purchaseorders.index') }}" class="btn btn-outline-secondary btn-sm">
                                Clear
                            </a>
                            <button type="button" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1" onclick="window.print()" title="Print list or Save as PDF">
                                <i class="material-icons material-symbol-rounded" style="font-size: 1rem;">print</i>
                                <span>Print</span>
                            </button>
                        </div>
                    </div>
                </form>

                {{-- Print header: standard format (shown only when printing) --}}
                <div class="print-only report-header text-center mb-3" style="display: none;">
                    <div class="logo-container mb-2" style="display: flex; justify-content: center; align-items: center; gap: 15px;">
                        <img src="{{ asset('images/lbsnaa_logo.jpg') }}" alt="LBSNAA Logo" style="height: 60px; width: auto;">
                    </div>
                    <h3 class="report-mess-title mb-1">OFFICER'S MESS LBSNAA MUSSOORIE</h3>
                    <div class="report-title-bar">Purchase Orders</div>
                    <div class="report-print-date small text-muted mt-1">Printed on {{ now()->format('d-m-Y, h:i A') }}</div>
                </div>

                <div class="table-responsive">
                    <table id="purchaseOrdersTable" class="table table-hover align-middle mb-0 w-100">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Order Number</th>
                                <th>Vendor Name</th>
                                <th>Store Name</th>
                                <th>Status</th>
                                <th>Action</th>
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
                                    <span class="badge rounded-pill bg-{{ $po->status == 'approved' ? 'success' : ($po->status == 'rejected' ? 'danger' : ($po->status == 'completed' ? 'primary' : 'warning')) }}">
                                        {{ ucfirst($po->status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-inline-flex align-items-center gap-2">
                                    <button type="button" class="btn  btn-outline-primary btn-view-po bg-transparent border-0 p-0" data-po-id="{{ $po->id }}" title="View">
                                        <i class="material-icons material-symbol-rounded">visibility</i>
                                    </button>
                                    <button type="button" class="btn  btn-outline-primary btn-edit-po bg-transparent border-0 p-0" data-po-id="{{ $po->id }}" title="Edit">
                                        <i class="material-icons material-symbol-rounded">edit</i>
                                    </button>
                                    <form action="{{ route('admin.mess.purchaseorders.destroy', $po->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this purchase order?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn  btn-outline-danger d-none" title="Delete">
                                            <i class="material-icons material-symbol-rounded">delete</i>
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

<style>
    /* Print header – standard level (matches category-wise-print-slip) */
    .report-mess-title {
        color: #1a1a1a;
        font-size: 1.25rem;
        font-weight: bold;
    }
    .report-title-bar {
        background-color: #004a93;
        color: #fff;
        padding: 8px 16px;
        font-size: 0.95rem;
        border-radius: 4px;
        display: inline-block;
    }
    .report-print-date { color: #6c757d; }

    @media print {
        .no-print { display: none !important; }
        .print-only { display: block !important; }
        .dataTables_length, .dataTables_filter, .dataTables_info, .dataTables_paginate { display: none !important; }
        /* Hide layout header and sidebar so only report content prints */
        .topbar, header.topbar, .left-sidebar, .side-mini-panel, #sidebarTabContent, .navbar { display: none !important; }
        body { margin: 0; padding: 15px; }
        body * { visibility: visible; }
        .report-header { margin-top: 0; border-bottom: 2px solid #004a93; padding-bottom: 12px; margin-bottom: 20px; }
        .logo-container { margin-bottom: 12px; }
        .logo-container img { height: 60px !important; width: auto !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .report-mess-title { font-size: 18px; font-weight: 700; color: #1a1a1a; margin-bottom: 8px; }
        .report-title-bar { font-size: 14px; -webkit-print-color-adjust: exact; print-color-adjust: exact; display: inline-block; background-color: #004a93 !important; }
        .report-print-date { font-size: 11px; color: #6c757d; margin-top: 8px; }
        
        /* Table styling for print */
        .table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 12px;
            page-break-inside: auto;
        }
        .table thead th {
            background-color: #004a93 !important;
            color: #fff !important;
            font-weight: 600;
            padding: 10px 8px;
            border: 1px solid #003d7a;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .table tbody td {
            padding: 8px;
            border: 1px solid #dee2e6;
            color: #212529;
        }
        .table tbody tr:nth-child(even) {
            background-color: #f8f9fa !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        /* Badge colors in print */
        .badge {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            padding: 4px 10px;
            font-size: 11px;
            border-radius: 4px;
        }
        .bg-success { background-color: #28a745 !important; color: #fff !important; }
        .bg-danger { background-color: #dc3545 !important; color: #fff !important; }
        .bg-warning { background-color: #ffc107 !important; color: #212529 !important; }
        .bg-primary { background-color: #004a93 !important; color: #fff !important; }
        
        /* Hide unnecessary elements */
        .card { box-shadow: none; border: none; }
        .datatables { margin: 0; }
        
        /* Page breaks */
        @page { 
            size: A4; 
            margin: 15mm; 
        }
    }
</style>

@include('components.mess-master-datatables', [
    'tableId' => 'purchaseOrdersTable',
    'searchPlaceholder' => 'Search purchase orders...',
    'orderColumn' => 1,
    'actionColumnIndex' => 5,
    'infoLabel' => 'purchase orders'
])

{{-- Tom Select CSS --}}
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">

{{-- Tom Select JS --}}
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

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

/* Tom Select Dropdown Fix - Ensure dropdowns appear above everything */
.ts-dropdown {
    z-index: 10000 !important;
}

.ts-control {
    z-index: 1;
}

/* Performance optimizations for Tom Select */
.ts-dropdown .option {
    will-change: auto;
}

.ts-dropdown-content {
    contain: layout style paint;
}

/* Fix for table responsive container clipping dropdowns */
.table-responsive {
    overflow: visible !important;
}

/* Ensure modal allows dropdowns to overflow */
.modal-body .table-responsive {
    overflow-x: auto;
    overflow-y: visible !important;
}

/* Fix table body overflow in modals */
.modal .card-body {
    overflow: visible !important;
}

/* Specific fix for item details table */
#poItemsTable,
#poItemsTable .table-responsive {
    overflow: visible !important;
}

/* ========================================
   Choices.js-like Styling for Tom Select
   ======================================== */

/* Control (Input Container) - Choices.js style */
.ts-wrapper .ts-control {
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 4px 8px;
    min-height: 38px;
    box-shadow: none;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.ts-wrapper.single .ts-control {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 16px 12px;
    padding-right: 2.25rem;
}

/* Focus state - Choices.js style */
.ts-wrapper.focus .ts-control {
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Dropdown container - Choices.js style */
.ts-dropdown {
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    background-color: #fff;
    margin-top: 4px;
}

/* Search input inside dropdown - Choices.js style */
.ts-dropdown .ts-dropdown-content {
    padding: 0;
}

.ts-control > input {
    color: #333;
    font-size: 14px;
    padding: 4px 0;
}

/* Dropdown input (search field) - Choices.js style */
.ts-dropdown-content input {
    border: 1px solid #ddd !important;
    border-radius: 4px;
    padding: 8px 12px !important;
    margin: 8px 8px 4px 8px;
    width: calc(100% - 16px) !important;
    font-size: 14px;
    background-color: #f9f9f9;
    box-sizing: border-box;
}

.ts-dropdown-content input:focus {
    outline: none;
    border-color: #80bdff !important;
    background-color: #fff;
}

/* Options list - Choices.js style */
.ts-dropdown .option {
    padding: 10px 12px;
    font-size: 14px;
    color: #333;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.15s ease;
    background-color: transparent;
}

.ts-dropdown .option:last-child {
    border-bottom: none;
}

/* Option hover state - Choices.js style */
.ts-dropdown .option:hover {
    background-color: #f5f5f5;
    color: #333;
}

/* Prevent default active state highlighting */
.ts-dropdown .option.active {
    background-color: transparent;
    color: #333;
}

/* Only show active state on hover */
.ts-dropdown .option.active:hover {
    background-color: #f5f5f5;
    color: #333;
}

/* Selected option highlight - Choices.js style */
.ts-dropdown .option.selected {
    background-color: #e9ecef;
    color: #333;
}

/* Aria-selected ko bhi visually normal rakho (auto selected highlight hide) */
.ts-dropdown .option[aria-selected="true"] {
    background-color: transparent;
    color: #333;
}

/* No results message - Choices.js style */
.ts-dropdown .no-results {
    padding: 12px;
    color: #999;
    font-size: 14px;
    text-align: center;
    background-color: #f9f9f9;
}

/* Selected item (for single select) - Choices.js style */
.ts-wrapper.single .ts-control .item {
    color: #333;
    padding: 2px 0;
    font-size: 14px;
}

/* Placeholder styling - Choices.js style */
.ts-wrapper .ts-control input::placeholder {
    color: #999;
    opacity: 1;
}

/* Disabled state - Choices.js style */
.ts-wrapper.disabled .ts-control {
    background-color: #f3f3f3;
    border-color: #ddd;
    cursor: not-allowed;
    opacity: 0.6;
}

/* Scrollbar styling for dropdown */
.ts-dropdown .ts-dropdown-content {
    max-height: 300px;
    overflow-y: auto;
}

.ts-dropdown .ts-dropdown-content::-webkit-scrollbar {
    width: 8px;
}

.ts-dropdown .ts-dropdown-content::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.ts-dropdown .ts-dropdown-content::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.ts-dropdown .ts-dropdown-content::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Remove default arrow for cleaner look */
.ts-wrapper.single.input-active .ts-control {
    background-image: none;
}

/* Optgroup styling - Choices.js style */
.ts-dropdown .optgroup {
    padding: 8px 12px;
    font-weight: 600;
    font-size: 13px;
    color: #666;
    background-color: #f9f9f9;
    border-bottom: 1px solid #e0e0e0;
}

.ts-dropdown .optgroup-header {
    padding: 8px 12px;
    font-weight: 600;
    font-size: 13px;
    color: #666;
    background-color: #f9f9f9;
}
</style>
<div class="modal fade" id="createPurchaseOrderModal" tabindex="-1" aria-labelledby="createPurchaseOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.mess.purchaseorders.store') }}" id="createPOForm" enctype="multipart/form-data">
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
                                    <label class="form-label">Bill No./Invoice No</label>
                                    <input type="text" name="bill_no" class="form-control" maxlength="100" placeholder="Bill number (optional)">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Bill Date</label>
                                    <input type="date" name="bill_date" class="form-control" max="{{ date('Y-m-d') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Challan No./Reference</label>
                                    <input type="text" name="challan_no" class="form-control" maxlength="100" placeholder="Challan number (optional)">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Challan Date</label>
                                    <input type="date" name="challan_date" class="form-control" max="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Bill / Attachment (Upload) --}}
                    <div class="card mb-4 border-primary">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0 fw-semibold text-primary">Upload Bill (PDF / Image)</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <label class="form-label">Bill / Attachment <small class="text-muted">(Optional)</small></label>
                                    <div class="input-group">
                                        <input type="file" name="bill_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" id="createBillFileInput">
                                        <button type="button" class="btn btn-outline-secondary" id="createBillClearBtn">Remove</button>
                                    </div>
                                    <small class="text-muted d-block mt-1">PDF, JPG, JPEG, PNG or WEBP. Max 5 MB.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Item Details --}}
                    <div class="card mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                            <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                            <button type="button" class="btn  btn-outline-primary" id="addPoItemRow">
                                + Add Item
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table text-nowrap mb-0" id="poItemsTable">
                                    <thead>
                                        <tr>
                                            <th style="width: 180px;">Item Name <span class="text-white">*</span></th>
                                            <th style="width:150px;">Unit</th>
                                            <th style="width:auto;">Item Code</th>
                                            <th style="width:auto;">Quantity <span class="text-white">*</span></th>
                                            <th style="width:auto;">Unit Price <span class="text-white">*</span></th>
                                            <th style="width:auto;">Tax (%)</th>
                                            <th style="width:auto;">Total Amount</th>
                                            <th style="width:auto;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="poItemsBody">
                                        <tr class="po-item-row">
                                            <td>
                                                <select name="items[0][item_subcategory_id]" class="form-select  po-item-select" required>
                                                    <option value="">Select Item</option>
                                                    @foreach($itemSubcategories as $sub)
                                                        <option value="{{ $sub['id'] }}" data-unit="{{ e($sub['unit_measurement']) }}" data-code="{{ e($sub['item_code']) }}">{{ $sub['item_name'] }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="text" name="items[0][unit]" class="form-control  po-unit" readonly placeholder="—"></td>
                                            <td><input type="text" name="items[0][item_code_display]" class="form-control  po-item-code" readonly placeholder="—"></td>
                                            <td><input type="text" name="items[0][quantity]" class="form-control  po-qty" required></td>
                                            <td><input type="text" name="items[0][unit_price]" class="form-control  po-unit-price" required></td>
                                            <td><input type="text" name="items[0][tax_percent]" class="form-control  po-tax"></td>
                                            <td><input type="text" name="items[0][total_display]" class="form-control  po-line-total bg-light" readonly></td>
                                            <td><button type="button" class="btn  btn-outline-danger po-remove-row" disabled title="Remove">×</button></td>
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
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="editPOForm" action="" enctype="multipart/form-data">
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
                                    <label class="form-label">Bill No./Invoice No</label>
                                    <input type="text" name="bill_no" id="editBillNo" class="form-control" maxlength="100" placeholder="Bill number (optional)">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Bill Date</label>
                                    <input type="date" name="bill_date" id="editBillDate" class="form-control" max="{{ date('Y-m-d') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Challan No./Reference</label>
                                    <input type="text" name="challan_no" id="editChallanNo" class="form-control" maxlength="100" placeholder="Challan number (optional)">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Challan Date</label>
                                    <input type="date" name="challan_date" id="editChallanDate" class="form-control" max="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Bill / Attachment (Upload) --}}
                    <div class="card mb-4 border-primary">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0 fw-semibold text-primary">Upload Bill (PDF / Image)</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <label class="form-label">Bill / Attachment <small class="text-muted">(Optional – leave empty to keep existing)</small></label>
                                    <div class="d-flex align-items-center border rounded px-2 py-1 bg-white gap-2" style="min-height: 38px;">
                                        <span id="editCurrentBillPath" class="flex-grow-1 text-muted small text-truncate me-2" style="min-width: 0;">No file chosen</span>
                                        <label class="mb-0 btn  btn-outline-secondary py-1 px-2" style="cursor: pointer;">
                                            Choose file
                                            <input type="file" name="bill_file" class="d-none" accept=".pdf,.jpg,.jpeg,.png,.webp" id="editBillFileInput">
                                        </label>
                                        <button type="button" class="btn  btn-outline-secondary py-1 px-2" id="editBillClearBtn">
                                            Remove
                                        </button>
                                    </div>
                                    <small class="text-muted d-block mt-1">PDF, JPG, JPEG, PNG or WEBP. Max 5 MB.</small>
                                    <p class="mb-0 mt-2 small" id="editCurrentBillLink"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                            <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                            <button type="button" class="btn  btn-outline-primary" id="addEditPoItemRow">+ Add Item</button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table text-nowrap mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width:180px;">Item Name <span class="text-white">*</span></th>
                                            <th style="width:auto;">Unit</th>
                                            <th style="width:auto;">Item Code</th>
                                            <th style="width:120px;">Quantity <span class="text-white">*</span></th>
                                            <th style="width:120px;">Unit Price <span class="text-white">*</span></th>
                                            <th style="width:120px;">Tax (%)</th>
                                            <th style="width:120px;">Total Amount</th>
                                            <th style="width:auto;"></th>
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
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-lg-down modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-body-tertiary border-0 py-3 px-4">
                <h5 class="modal-title fw-semibold" id="viewPurchaseOrderModalLabel">Purchase Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-lg-4 bg-body-tertiary">
                <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                    <div class="card-header bg-white border-0 py-3 px-4">
                        <h6 class="mb-0 fw-semibold text-primary">Order Details</h6>
                    </div>
                    <div class="card-body p-3 p-lg-4">
                        <div class="row g-3">
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                                    <label class="form-label text-body-secondary small mb-1">Order Number</label>
                                    <p class="mb-0 fw-medium text-body" id="viewPoNumber">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                                    <label class="form-label text-body-secondary small mb-1">Order Date</label>
                                    <p class="mb-0 fw-medium text-body" id="viewPoDate">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                                    <label class="form-label text-body-secondary small mb-1">Store Name</label>
                                    <p class="mb-0 fw-medium text-body" id="viewStoreName">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                                    <label class="form-label text-body-secondary small mb-1">Vendor Name</label>
                                    <p class="mb-0 fw-medium text-body" id="viewVendorName">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                                    <label class="form-label text-body-secondary small mb-1">Payment Mode</label>
                                    <p class="mb-0 fw-medium text-body" id="viewPaymentCode">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                                    <label class="form-label text-body-secondary small mb-1">Bill No./Invoice No</label>
                                    <p class="mb-0 fw-medium text-body" id="viewBillNo">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                                    <label class="form-label text-body-secondary small mb-1">Bill Date</label>
                                    <p class="mb-0 fw-medium text-body" id="viewBillDate">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                                    <label class="form-label text-body-secondary small mb-1">Challan No./Reference</label>
                                    <p class="mb-0 fw-medium text-body" id="viewChallanNo">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                                    <label class="form-label text-body-secondary small mb-1">Challan Date</label>
                                    <p class="mb-0 fw-medium text-body" id="viewChallanDate">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                                    <label class="form-label text-body-secondary small mb-1">Status</label>
                                    <p class="mb-0"><span class="badge" id="viewStatus">&mdash;</span></p>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border rounded-3 p-3 bg-light-subtle">
                                    <label class="form-label text-body-secondary small mb-1">Bill</label>
                                    <p class="mb-0" id="viewBillWrap">
                                        <a href="#" id="viewBillLink" target="_blank" rel="noopener" class="btn  btn-outline-primary" style="display: none;">View / Download Bill</a>
                                        <span id="viewBillNone" class="text-muted">No bill uploaded</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-0 overflow-hidden">
                    <div class="card-header bg-white border-0 py-3 px-4">
                        <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-nowrap">Item Name</th>
                                        <th class="text-nowrap">Unit</th>
                                        <th class="text-nowrap">Item Code</th>
                                        <th class="text-nowrap">Quantity</th>
                                        <th class="text-nowrap">Unit Price</th>
                                        <th class="text-nowrap">Tax (%)</th>
                                        <th class="text-nowrap">Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="viewPoItemsBody"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-body-tertiary d-flex justify-content-end align-items-center py-3 px-4">
                        <span class="fw-semibold">Grand Total:</span>
                        <span class="fs-5 text-primary fw-bold ms-2" id="viewPoGrandTotal">&#8377;0.00</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-primary btn-print-view-modal" data-print-target="#viewPurchaseOrderModal" title="Print">
                    <i class="material-icons material-symbol-rounded">print</i> Print
                </button>
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
    let hasInitialCreateErrors = {{ $errors->any() ? 'true' : 'false' }};

    // Tom Select instances storage
    let tomSelectInstances = {
        filter: {},
        create: {},
        edit: {},
        items: []
    };

    // Initialize Tom Select for a single element
    function initTomSelect(element, options = {}) {
        if (!element) return null;
        if (element.tomselect) {
            element.tomselect.destroy();
        }
        try {
            const defaultOptions = {
                allowEmptyOption: true,
                create: false,
                dropdownParent: 'body',
                maxOptions: null,
                closeAfterSelect: true,
                hideSelected: false,
                highlight: false, // Disable auto-highlighting first item
                // Searchable dropdown input (is par hi typing se filter hota hai)
                controlInput: '<input>',
                searchField: ['text'],
                openOnFocus: true,
                selectOnTab: false, // Changed to false to prevent accidental selection
                render: {
                    no_results: function(data, escape) {
                        return '<div class="no-results">No results found for "' + escape(data.input) + '"</div>';
                    }
                },
                onInitialize: function() {
                    // Remove any active option on initialize
                    this.activeOption = null;
                },
                onDropdownOpen: function(dropdown) {
                    // Dropdown ke andar jo actual search input hai usko pakdo
                    const searchInput = dropdown.querySelector('.ts-dropdown-content input') || this.control_input;

                    // Kis original <select> par ye dropdown laga hai
                    const originalSelect = this.input || this.original_input || this.select;
                    // Filters (Vendor/Store) + Item Name dropdowns par selection clear rakhni hai
                    const shouldClearOnOpen =
                        originalSelect &&
                        originalSelect.classList &&
                        (originalSelect.classList.contains('js-filter-select') ||
                         originalSelect.classList.contains('js-item-select'));
                    if (shouldClearOnOpen) {
                        this.clear(true);
                    }

                    // Clear any previous search text so box is blank
                    if (searchInput) {
                        searchInput.value = '';
                    }
                    if (typeof this.setTextboxValue === 'function') {
                        this.setTextboxValue('');
                    }
                    if (typeof this.onSearchChange === 'function') {
                        this.onSearchChange('');
                    }
                    if (typeof this.refreshOptions === 'function') {
                        this.refreshOptions(false);
                    }

                    // TomSelect apni taraf se selected/active class dobara lagata hai,
                    // isliye thoda delay dekar unhe hata rahe hain
                    setTimeout(() => {
                        this.activeOption = null;
                        if (typeof this.setActiveOption === 'function') {
                            this.setActiveOption(null);
                        }
                        if (typeof this.clearActiveItems === 'function') {
                            this.clearActiveItems();
                        }

                        const options = dropdown.querySelectorAll('.option.active, .option.selected, .option[aria-selected="true"]');
                        options.forEach(opt => {
                            opt.classList.remove('active');
                            opt.classList.remove('selected');
                            opt.setAttribute('aria-selected', 'false');
                        });
                    }, 0);

                    // Cursor ko hamesha input ke starting me le jao
                    if (searchInput) {
                        setTimeout(() => {
                            searchInput.focus();
                            try {
                                searchInput.setSelectionRange(0, 0);
                            } catch (e) {}
                            searchInput.scrollLeft = 0;
                        }, 0);
                    }
                },
                onFocus: function() {
                    // Position cursor at start when field is focused
                    const input = this.control_input;
                    if (input) {
                        setTimeout(() => {
                            input.setSelectionRange(0, 0);
                            input.scrollLeft = 0;
                        }, 0);
                    }
                },
                onType: function(str) {
                    // Clear active option when user starts typing
                    if (str.length === 0) {
                        this.setActiveOption(null);
                    }
                },
                ...options
            };
            return new TomSelect(element, defaultOptions);
        } catch (error) {
            console.error('Tom Select initialization failed:', error);
            return null;
        }
    }

    // Destroy Tom Select instance
    function destroyTomSelect(element) {
        if (element && element.tomselect) {
            element.tomselect.destroy();
        }
    }

    // Initialize filter dropdowns
    function initFilterDropdowns() {
        const filterVendor = document.querySelector('form[method="GET"] select[name="vendor_id"]');
        const filterStore = document.querySelector('form[method="GET"] select[name="store_id"]');
        
        if (filterVendor) {
            tomSelectInstances.filter.vendor = initTomSelect(filterVendor, {
                placeholder: 'All Vendors'
            });
        }
        if (filterStore) {
            tomSelectInstances.filter.store = initTomSelect(filterStore, {
                placeholder: 'All Stores'
            });
        }
    }

    // Initialize create modal dropdowns
    function initCreateModalDropdowns() {
        const createStore = document.querySelector('#createPurchaseOrderModal select[name="store_id"]');
        const createVendor = document.querySelector('#createPurchaseOrderModal select[name="vendor_id"]');
        const createPayment = document.querySelector('#createPurchaseOrderModal select[name="payment_code"]');
        
        if (createStore) {
            tomSelectInstances.create.store = initTomSelect(createStore, {
                placeholder: 'Select Store'
            });
        }
        if (createVendor) {
            tomSelectInstances.create.vendor = initTomSelect(createVendor, {
                placeholder: 'Select Vendor'
            });
        }
        if (createPayment) {
            tomSelectInstances.create.payment = initTomSelect(createPayment, {
                placeholder: 'Select Payment Mode'
            });
        }
    }

    // Initialize edit modal dropdowns
    function initEditModalDropdowns() {
        const editStore = document.getElementById('editStoreId');
        const editVendor = document.getElementById('editVendorId');
        const editPayment = document.getElementById('editPaymentCode');
        
        if (editStore) {
            tomSelectInstances.edit.store = initTomSelect(editStore, {
                placeholder: 'Select Store'
            });
        }
        if (editVendor) {
            tomSelectInstances.edit.vendor = initTomSelect(editVendor, {
                placeholder: 'Select Vendor'
            });
        }
        if (editPayment) {
            tomSelectInstances.edit.payment = initTomSelect(editPayment, {
                placeholder: 'Select Payment Mode'
            });
        }
    }

    // Initialize item dropdown in a row
    function initItemDropdownInRow(row) {
        const select = row.querySelector('.po-item-select');
        if (select && !select.tomselect) {
            // Pehle se koi value set hai ya nahi (edit mode me hogi)
            const hadValueBefore = !!select.value;
            const instance = initTomSelect(select, {
                placeholder: 'Select Item',
                maxOptions: 200
            });
            if (instance) {
                tomSelectInstances.items.push(instance);
                // Agar pehle koi value nahi thi (naya row / blank item),
                // to TomSelect ke first-item auto select ko turant clear kar do
                if (!hadValueBefore) {
                    instance.clear(true);
                }
            }
        }
    }

    // Initialize all item dropdowns in a tbody
    function initAllItemDropdowns(tbody) {
        const rows = tbody.querySelectorAll('.po-item-row');
        rows.forEach(row => initItemDropdownInRow(row));
    }

    // Destroy all item dropdowns
    function destroyAllItemDropdowns() {
        tomSelectInstances.items.forEach(instance => {
            if (instance) instance.destroy();
        });
        tomSelectInstances.items = [];
    }

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
                <select name="items[${index}][item_subcategory_id]" class="form-select  po-item-select" required>
                    <option value="">Select Item</option>
                    ${options}
                </select>
            </td>
            <td><input type="text" name="items[${index}][unit]" class="form-control  po-unit" readonly placeholder="—" value="${unit}"></td>
            <td><input type="text" class="form-control  po-item-code" readonly placeholder="—" value="${code}"></td>
            <td><input type="text" name="items[${index}][quantity]" class="form-control  po-qty" value="${qty}" required></td>
            <td><input type="text" name="items[${index}][unit_price]" class="form-control  po-unit-price" value="${price}" required></td>
            <td><input type="text" name="items[${index}][tax_percent]" class="form-control  po-tax" max="100" value="${tax}"></td>
            <td><input type="text" class="form-control  po-line-total bg-light" readonly placeholder="0.00" value="${lineTotal}"></td>
            <td><button type="button" class="btn  btn-outline-danger po-remove-row" title="Remove">×</button></td>
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
            filteredItems = itemSubcategories || [];
            if (callback) callback();
        });
    }

    function updateItemDropdowns(tbody, isEditModal) {
        const rows = tbody.querySelectorAll('.po-item-row');
        const itemsToUse = isEditModal ? (editModalItems && editModalItems.length ? editModalItems : itemSubcategories) : filteredItems;
        
        rows.forEach(row => {
            const select = row.querySelector('.po-item-select');
            if (!select) return;
            
            const currentValue = select.tomselect ? select.tomselect.getValue() : select.value;
            
            // If Tom Select exists, use its API to update options (faster)
            if (select.tomselect) {
                select.tomselect.clearOptions();
                select.tomselect.addOption({ value: '', text: 'Select Item' });
                
                itemsToUse.forEach(item => {
                    select.tomselect.addOption({
                        value: item.id,
                        text: item.item_name || '—'
                    });
                    
                    // Update the underlying option element's data attributes
                    const opt = select.querySelector(`option[value="${item.id}"]`);
                    if (opt) {
                        opt.setAttribute('data-unit', item.unit_measurement || '');
                        opt.setAttribute('data-code', item.item_code || '');
                    }
                });
                
                select.tomselect.setValue(currentValue, true);
            } else {
                // Fallback: rebuild select options manually
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
                
                // Initialize Tom Select
                const instance = initTomSelect(select, {
                    placeholder: 'Select Item',
                    maxOptions: 200
                });
                if (instance) {
                    tomSelectInstances.items.push(instance);
                }
            }
            
            // Update unit and code after dropdown refresh
            updateUnitAndCode(row);
        });
    }

    function updateUnitAndCode(row) {
        const select = row.querySelector('.po-item-select');
        if (!select) return;
        
        // Get the selected option - works with both Tom Select and regular select
        let selectedOption;
        if (select.tomselect) {
            const selectedValue = select.tomselect.getValue();
            selectedOption = select.querySelector(`option[value="${selectedValue}"]`);
        } else {
            selectedOption = select.options[select.selectedIndex];
        }
        
        const unitInput = row.querySelector('.po-unit');
        const codeInput = row.querySelector('.po-item-code');
        if (unitInput) unitInput.value = selectedOption && selectedOption.dataset.unit ? selectedOption.dataset.unit : '';
        if (codeInput) codeInput.value = selectedOption && selectedOption.dataset.code ? selectedOption.dataset.code : '';
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
    document.addEventListener('DOMContentLoaded', function() {
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
    });

    document.getElementById('addPoItemRow').addEventListener('click', function() {
        const tbody = document.getElementById('poItemsBody');
        tbody.insertAdjacentHTML('beforeend', getItemRowHtml(itemRowIndex, null, false));
        const newRow = tbody.lastElementChild;
        initItemDropdownInRow(newRow);
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

    // View button: fetch PO and open view modal (mousedown ensures single-tap works with DataTables)
    document.addEventListener('mousedown', function(e) {
        const btn = e.target.closest('.btn-view-po');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        const poId = btn.getAttribute('data-po-id');
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
                    document.getElementById('viewBillNo').textContent = po.bill_no || '—';
                    document.getElementById('viewBillDate').textContent = po.bill_date ? new Date(po.bill_date).toLocaleDateString('en-IN') : '—';
                    document.getElementById('viewChallanNo').textContent = po.challan_no || '—';
                    document.getElementById('viewChallanDate').textContent = po.challan_date ? new Date(po.challan_date).toLocaleDateString('en-IN') : '—';
                    const billLink = document.getElementById('viewBillLink');
                    const billNone = document.getElementById('viewBillNone');
                    if (po.bill_url) {
                        billLink.href = po.bill_url;
                        billLink.style.display = '';
                        if (billNone) billNone.style.display = 'none';
                    } else {
                        billLink.href = '#';
                        billLink.style.display = 'none';
                        if (billNone) billNone.style.display = '';
                    }
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
    }, true);

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Vendor selection change in EDIT modal: load vendor-mapped items and refresh dropdowns
    document.addEventListener('DOMContentLoaded', function() {
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
                        const v = sel.tomselect ? sel.tomselect.getValue() : sel.value;
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
    });

    // Edit button: fetch PO and open modal (mousedown ensures single-tap works with DataTables)
    document.addEventListener('mousedown', function(e) {
        const btn = e.target.closest('.btn-edit-po');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        const poId = btn.getAttribute('data-po-id');
            fetch(editPoBaseUrl + '/' + poId + '/edit', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(data => {
                    const po = data.po;
                    const items = data.items || [];
                    document.getElementById('editPOForm').action = editPoBaseUrl + '/' + poId;
                    document.getElementById('editPoNumber').value = po.po_number || '';
                    document.getElementById('editPoDate').value = po.po_date || '';
                    var storeVal = (po.store_id != null && po.store_id !== '') ? String(po.store_id) : '';
                    var vendorVal = (po.vendor_id != null && po.vendor_id !== '') ? String(po.vendor_id) : '';
                    var paymentVal = (po.payment_code != null && po.payment_code !== '') ? String(po.payment_code) : '';
                    if (tomSelectInstances.edit.store) {
                        tomSelectInstances.edit.store.setValue(storeVal, true);
                    } else {
                        document.getElementById('editStoreId').value = storeVal;
                    }
                    if (tomSelectInstances.edit.vendor) {
                        tomSelectInstances.edit.vendor.setValue(vendorVal, true);
                    } else {
                        document.getElementById('editVendorId').value = vendorVal;
                    }
                    if (tomSelectInstances.edit.payment) {
                        tomSelectInstances.edit.payment.setValue(paymentVal, true);
                    } else {
                        document.getElementById('editPaymentCode').value = paymentVal;
                    }
                    document.getElementById('editBillNo').value = po.bill_no || '';
                    const editBillDateEl = document.getElementById('editBillDate');
                    if (editBillDateEl) editBillDateEl.value = po.bill_date || '';
                    document.getElementById('editChallanNo').value = po.challan_no || '';
                    const editChallanDateEl = document.getElementById('editChallanDate');
                    if (editChallanDateEl) editChallanDateEl.value = po.challan_date || '';
                    var editBillPathEl = document.getElementById('editCurrentBillPath');
                    if (editBillPathEl) {
                        editBillPathEl.textContent = po.bill_path ? (po.bill_path.split('/').pop() || po.bill_path) : 'No file chosen';
                    }
                    var editBillFileInput = document.getElementById('editBillFileInput');
                    if (editBillFileInput) {
                        editBillFileInput.value = '';
                    }
                    var editBillLinkEl = document.getElementById('editCurrentBillLink');
                    if (editBillLinkEl) {
                        if (po.bill_url) {
                            editBillLinkEl.innerHTML = 'Current bill: <a href="' + escapeHtml(po.bill_url) + '" target="_blank" rel="noopener" class="text-primary">View Bill</a>';
                        } else {
                            editBillLinkEl.innerHTML = '';
                        }
                    }
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

                        // Destroy existing item dropdowns
                        destroyAllItemDropdowns();

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
                        
                        // Initialize Tom Select for all item dropdowns
                        initAllItemDropdowns(tbody);
                        
                        updateEditGrandTotal();
                        updateEditRemoveButtons();
                        new bootstrap.Modal(document.getElementById('editPurchaseOrderModal')).show();
                    }

                    // Show modal immediately with all items; vendor-specific list loads in background
                    buildEditRows(itemSubcategories);
                    if (po.vendor_id) {
                        fetchVendorItems(po.vendor_id, function() {
                            const tbody = document.getElementById('editPoItemsBody');
                            if (tbody && document.getElementById('editPurchaseOrderModal').classList.contains('show')) {
                                editModalItems = (filteredItems && filteredItems.length) ? filteredItems : itemSubcategories;
                                updateItemDropdowns(tbody, true);
                            }
                        });
                    }
                })
                .catch(err => { console.error(err); alert('Failed to load purchase order.'); });
    }, true);

    document.getElementById('addEditPoItemRow').addEventListener('click', function() {
        const tbody = document.getElementById('editPoItemsBody');
        tbody.insertAdjacentHTML('beforeend', getItemRowHtml(editItemRowIndex, null, true));
        const newRow = tbody.lastElementChild;
        initItemDropdownInRow(newRow);
        editItemRowIndex++;
        updateEditRemoveButtons();
    });

    var createBillFileInputEl = document.getElementById('createBillFileInput');
    var createBillClearBtnEl = document.getElementById('createBillClearBtn');
    if (createBillClearBtnEl && createBillFileInputEl) {
        createBillClearBtnEl.addEventListener('click', function () {
            createBillFileInputEl.value = '';
        });
    }

    // Bill file client-side validation (extension & size)
    function validateBillFileInput(fileInput, pathLabelEl) {
        if (!fileInput || !fileInput.files || !fileInput.files[0]) {
            if (pathLabelEl) pathLabelEl.textContent = 'No file chosen';
            return;
        }
        var file = fileInput.files[0];
        var allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        var nameParts = file.name.split('.');
        var ext = nameParts.length > 1 ? nameParts.pop().toLowerCase() : '';
        var maxBytes = 5 * 1024 * 1024; // 5 MB

        if (!allowedExt.includes(ext)) {
            alert('Only PDF, JPG, JPEG, PNG or WEBP files are allowed for Bill.');
            fileInput.value = '';
            if (pathLabelEl) pathLabelEl.textContent = 'No file chosen';
            return;
        }
        if (file.size > maxBytes) {
            alert('Bill file size must not exceed 5 MB.');
            fileInput.value = '';
            if (pathLabelEl) pathLabelEl.textContent = 'No file chosen';
            return;
        }

        if (pathLabelEl) {
            pathLabelEl.textContent = file.name;
        }
    }

    if (createBillFileInputEl) {
        createBillFileInputEl.addEventListener('change', function () {
            // For create modal we don't show a file-name label; just validate
            validateBillFileInput(createBillFileInputEl, null);
        });
    }

    var editBillFileInputEl = document.getElementById('editBillFileInput');
    if (editBillFileInputEl) {
        editBillFileInputEl.addEventListener('change', function() {
            var pathEl = document.getElementById('editCurrentBillPath');
            validateBillFileInput(editBillFileInputEl, pathEl);
        });
    }

    var editBillClearBtnEl = document.getElementById('editBillClearBtn');
    if (editBillClearBtnEl && editBillFileInputEl) {
        editBillClearBtnEl.addEventListener('click', function () {
            editBillFileInputEl.value = '';
            var pathEl = document.getElementById('editCurrentBillPath');
            if (pathEl) pathEl.textContent = 'No file chosen';
        });
    }

    var editBillClearBtnEl = document.getElementById('editBillClearBtn');
    if (editBillClearBtnEl && editBillFileInputEl) {
        editBillClearBtnEl.addEventListener('click', function () {
            editBillFileInputEl.value = '';
            var pathEl = document.getElementById('editCurrentBillPath');
            if (pathEl) pathEl.textContent = 'No file chosen';
        });
    }

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

    // Contact number: restrict to digits only, max 10
    function initContactNumberValidation(inputEl) {
        if (!inputEl) return;
        inputEl.addEventListener('keydown', function(e) {
            const key = e.key;
            if (key === 'Backspace' || key === 'Tab' || key === 'ArrowLeft' || key === 'ArrowRight' || key === 'Delete') return;
            if (key.length === 1 && !/^[0-9]$/.test(key)) {
                e.preventDefault();
            }
        });
        inputEl.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
            if (validateContactNumber(this.value)) {
                this.classList.remove('is-invalid');
                const fb = this.parentNode.querySelector('.invalid-feedback.d-block');
                if (fb) fb.textContent = '';
            }
        });
        inputEl.addEventListener('paste', function(e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            const digits = text.replace(/[^0-9]/g, '').slice(0, 10);
            const start = this.selectionStart, end = this.selectionEnd;
            this.value = this.value.slice(0, start) + digits + this.value.slice(end);
            this.setSelectionRange(start + digits.length, start + digits.length);
        });
    }
    initContactNumberValidation(document.getElementById('createContactNumber'));
    document.getElementById('editPurchaseOrderModal').addEventListener('shown.bs.modal', function() {
        initContactNumberValidation(document.getElementById('editContactNumber'));
    }, { once: false });

    // Validate contact number before form submit (optional field: if provided, must be exactly 10 digits)
    function validateContactNumber(val) {
        if (!val || val.trim() === '') return true;
        return /^[0-9]{10}$/.test(val.replace(/\s/g, ''));
    }
    document.getElementById('createPOForm').addEventListener('submit', function(e) {
        const input = document.getElementById('createContactNumber');
        if (input && !validateContactNumber(input.value)) {
            e.preventDefault();
            input.classList.add('is-invalid');
            const msg = input.parentNode.querySelector('.invalid-feedback.d-block') || document.createElement('div');
            if (!msg.classList || !msg.classList.contains('invalid-feedback')) {
                const m = document.createElement('div');
                m.className = 'invalid-feedback d-block';
                m.textContent = 'Contact number must be exactly 10 digits (numbers only).';
                input.parentNode.appendChild(m);
            } else {
                msg.textContent = 'Contact number must be exactly 10 digits (numbers only).';
            }
            input.focus();
            return false;
        }
    });

    // AJAX submit: Create Purchase Order (keep modal open until user closes)
    (function() {
        var form = document.getElementById('createPOForm');
        if (!form) return;

        function resetCreatePurchaseOrderForm() {
            // Reuse existing reset logic by triggering modal show handler logic:
            // - clears TomSelect selections
            // - resets items table to one row
            // - clears bill file input
            var createModal = document.getElementById('createPurchaseOrderModal');
            if (!createModal) return;

            // Reset vendor selection + filtered items
            currentVendorId = null;
            filteredItems = itemSubcategories;

            // Reset native form fields
            form.reset();

            // Reset Tom Select dropdowns
            if (tomSelectInstances && tomSelectInstances.create) {
                if (tomSelectInstances.create.vendor) tomSelectInstances.create.vendor.clear();
                if (tomSelectInstances.create.store) tomSelectInstances.create.store.clear();
                if (tomSelectInstances.create.payment) tomSelectInstances.create.payment.clear();
            }

            // Clear selected bill file (if any)
            if (createBillFileInputEl) createBillFileInputEl.value = '';

            // Reset items table to a single fresh row
            destroyAllItemDropdowns();
            var tbody = document.getElementById('poItemsBody');
            if (tbody) {
                tbody.innerHTML = '';
                tbody.insertAdjacentHTML('beforeend', getItemRowHtml(0, null, false));
                itemRowIndex = 1;
                initAllItemDropdowns(tbody);
                updateGrandTotal();
                updateRemoveButtons();
            }
        }

        form.addEventListener('submit', function(e) {
            // If any earlier listener prevented default, do nothing.
            if (e.defaultPrevented) return;

            if (!form.checkValidity()) {
                // Let browser/Bootstrap validations do their job
                return;
            }

            e.preventDefault();

            var btn = form.querySelector('button[type="submit"]');
            if (btn && btn.disabled) return;
            if (btn) {
                if (!btn.dataset.originalText) btn.dataset.originalText = btn.textContent || '';
                btn.disabled = true;
                btn.textContent = 'Creating...';
            }

            var action = form.getAttribute('action') || window.location.href;
            var method = (form.getAttribute('method') || 'POST').toUpperCase();
            var formData = new FormData(form);
            var csrf = form.querySelector('input[name="_token"]');

            fetch(action, {
                method: method,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf ? csrf.value : '',
                    'Accept': 'application/json'
                },
                body: formData
            })
                .then(function(response) {
                    return response.json().then(function(payload) {
                        return { ok: response.ok, status: response.status, payload: payload };
                    }).catch(function() {
                        return { ok: response.ok, status: response.status, payload: null };
                    });
                })
                .then(function(res) {
                    var data = res.payload;
                    if (res.ok && data && data.success) {
                        resetCreatePurchaseOrderForm();
                        if (window.toastr && data.message) {
                            toastr.success(data.message);
                        } else if (data.message) {
                            alert(data.message);
                        }
                    } else {
                        var msg = (data && data.message) ? data.message : 'Failed to create purchase order. Please try again.';
                        if (res.status === 422 && data && data.errors) {
                            try {
                                var firstKey = Object.keys(data.errors)[0];
                                if (firstKey && data.errors[firstKey] && data.errors[firstKey][0]) {
                                    msg = data.errors[firstKey][0];
                                }
                            } catch (e) {}
                        }
                        alert(msg);
                    }
                })
                .catch(function() {
                    alert('Failed to create purchase order. Please try again.');
                })
                .finally(function() {
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = btn.dataset.originalText || 'Create Purchase Order';
                    }
                });
        });
    })();
    document.getElementById('editPOForm').addEventListener('submit', function(e) {
        const input = document.getElementById('editContactNumber');
        if (input && !validateContactNumber(input.value)) {
            e.preventDefault();
            input.classList.add('is-invalid');
            let msg = input.parentNode.querySelector('.invalid-feedback.d-block');
            if (!msg) {
                msg = document.createElement('div');
                msg.className = 'invalid-feedback d-block';
                input.parentNode.appendChild(msg);
            }
            msg.textContent = 'Contact number must be exactly 10 digits (numbers only).';
            input.focus();
            return false;
        }
    });

    // Auto-open create modal when validation errors exist (e.g. after failed submit)
    @if($errors->any() || session('open_create_po_modal'))
    document.addEventListener('DOMContentLoaded', function() {
        const createModal = document.getElementById('createPurchaseOrderModal');
        if (createModal && (document.getElementById('createPOForm') || document.querySelector('[name="po_number"]'))) {
            new bootstrap.Modal(createModal).show();
        }
    });
    @endif

    // Reset create modal when opened (except first open after validation errors)
    if (createPOModal) {
        createPOModal.addEventListener('show.bs.modal', function() {
            if (hasInitialCreateErrors) {
                // Preserve previously entered values on first open after validation error
                hasInitialCreateErrors = false;
                return;
            }

            // Reset vendor selection
            currentVendorId = null;
            filteredItems = itemSubcategories;

            // Reset form fields and Tom Select instances
            const form = document.getElementById('createPOForm');
            if (form) {
                form.reset();
                
                // Reset Tom Select dropdowns
                if (tomSelectInstances.create.vendor) {
                    tomSelectInstances.create.vendor.clear();
                }
                if (tomSelectInstances.create.store) {
                    tomSelectInstances.create.store.clear();
                }
                if (tomSelectInstances.create.payment) {
                    tomSelectInstances.create.payment.clear();
                }
            }

            // Clear selected bill file (if any)
            if (createBillFileInputEl) {
                createBillFileInputEl.value = '';
            }

            // Reset items table to a single fresh row
            destroyAllItemDropdowns();
            const tbody = document.getElementById('poItemsBody');
            if (tbody) {
                tbody.innerHTML = '';
                tbody.insertAdjacentHTML('beforeend', getItemRowHtml(0, null, false));
                itemRowIndex = 1;
                
                // Initialize Tom Select for the first row
                initAllItemDropdowns(tbody);
                
                updateGrandTotal();
                updateRemoveButtons();
            }
        });
    }

    // Print View modal content – correct design with standard header
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-print-view-modal');
        if (!btn) return;
        var sel = btn.getAttribute('data-print-target');
        if (!sel) return;
        var modal = document.querySelector(sel);
        if (!modal) return;
        var content = modal.querySelector('.modal-content');
        if (!content) return;
        var win = window.open('', '_blank', 'width=900,height=700');
        if (!win) { alert('Please allow popups to print.'); return; }
        var title = (modal.querySelector('.modal-title') || {}).textContent || 'Purchase Order Details';
        var printedOn = new Date();
        var dateStr = printedOn.getDate().toString().padStart(2,'0') + '/' + (printedOn.getMonth()+1).toString().padStart(2,'0') + '/' + printedOn.getFullYear() + ', ' + printedOn.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true });
        var bodyContent = content.innerHTML;
        bodyContent = bodyContent.replace(/<button[^>]*btn-close[^>]*>[\s\S]*?<\/button>/gi, '');
        bodyContent = bodyContent.replace(/<div class="modal-footer[^"]*"[^>]*>[\s\S]*?<\/div>\s*$/i, '');
        var printHeader = '<div class="print-doc-header" style="text-align:center;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid #2c3e50;">' +
            '<div style="margin-bottom:10px;"><img src="{{ asset('images/lbsnaa_logo.jpg') }}" alt="LBSNAA Logo" style="height:60px;width:auto;"></div>' +
            '<div style="font-size:18px;font-weight:700;color:#1a1a1a;margin-bottom:6px;">OFFICER\'S MESS LBSNAA MUSSOORIE</div>' +
            '<div style="background:#004a93;color:#fff;padding:8px 16px;font-size:14px;display:inline-block;margin:4px 0;border-radius:4px;-webkit-print-color-adjust:exact;print-color-adjust:exact;">Purchase Order Details</div>' +
            '<div style="font-size:11px;color:#6c757d;margin-top:8px;">Printed on ' + dateStr + '</div></div>';
        var printCss = '<style>' +
            '@page { size: A4; margin: 14mm; }' +
            'body { font-family: Arial, sans-serif; font-size: 12px; color: #212529; padding: 0 12px; margin: 0; background: #fff; }' +
            '.print-doc-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }' +
            '.print-doc-header img { -webkit-print-color-adjust: exact; print-color-adjust: exact; }' +
            '.modal-header { border-bottom: 1px solid #dee2e6; padding-bottom: 8px; margin-bottom: 12px; display: none; }' +
            '.modal-header .modal-title { font-size: 14px; font-weight: 600; }' +
            '.modal-body { color: #212529; }' +
            '.card { margin-bottom: 14px; page-break-inside: avoid; border: 1px solid #dee2e6; border-radius: 4px; }' +
            '.card-header { font-weight: 600; font-size: 13px; margin-bottom: 10px; padding: 8px 12px; background: #f8f9fa; border-bottom: 2px solid #004a93; color: #004a93; }' +
            '.card-body .row { display: flex; flex-wrap: wrap; margin: 0 -6px; }' +
            '.card-body .col-md-4, .card-body .col-xl-4, .card-body .col-12 { width: 33.33%; box-sizing: border-box; padding: 0 6px 10px; }' +
            '.card-body .col-md-12, .card-body .col-12 { width: 100%; }' +
            '.card-body .form-label, .card-body label { font-size: 10px; color: #6c757d; display: block; margin-bottom: 2px; font-weight: 600; }' +
            '.card-body p, .card-body .fw-medium { margin: 0; font-size: 12px; color: #212529; }' +
            '.border { border: 1px solid #dee2e6 !important; }' +
            '.rounded-3 { border-radius: 4px !important; }' +
            '.bg-light-subtle { background-color: #f8f9fa !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }' +
            'table { width: 100%; border-collapse: collapse; font-size: 11px; page-break-inside: auto; }' +
            'th, td { border: 1px solid #adb5bd; padding: 6px 8px; text-align: left; }' +
            'thead th { background: #004a93 !important; color: #fff !important; border-color: #003d7a; font-weight: 600; -webkit-print-color-adjust: exact; print-color-adjust: exact; }' +
            'tbody tr:nth-child(even) { background-color: #f8f9fa !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }' +
            '.card-footer { font-weight: 600; padding: 10px 12px; border-top: 2px solid #004a93; margin-top: 4px; font-size: 13px; background: #f8f9fa; -webkit-print-color-adjust: exact; print-color-adjust: exact; }' +
            '.badge { display: inline-block; padding: 3px 8px; font-size: 10px; border-radius: 4px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }' +
            '.bg-success { background-color: #28a745 !important; color: #fff !important; }' +
            '.bg-danger { background-color: #dc3545 !important; color: #fff !important; }' +
            '.bg-warning { background-color: #ffc107 !important; color: #212529 !important; }' +
            '.bg-primary { background-color: #004a93 !important; color: #fff !important; }' +
            '.btn-close, .modal-footer { display: none !important; }' +
            '.text-primary { color: #004a93 !important; }' +
            '.fs-5 { font-size: 16px !important; }' +
            '@media print { body { padding: 0; } .print-doc-header { margin-bottom: 16px; } }' +
            '</style>';
        win.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>' + title.replace(/</g, '&lt;') + '</title>' + printCss + '</head><body>' + printHeader + '<div class="modal-content-wrap">' + bodyContent + '</div></body></html>');
        win.document.close();
        win.focus();
        setTimeout(function() { win.print(); win.close(); }, 350);
    });

    // Initialize Tom Select on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize filter dropdowns only (always visible)
        initFilterDropdowns();
        
        // Initialize create modal dropdowns immediately
        initCreateModalDropdowns();
        
        // Initialize edit modal dropdowns immediately  
        initEditModalDropdowns();
        
        // Initialize item dropdowns in create modal
        const createTbody = document.getElementById('poItemsBody');
        if (createTbody) {
            initAllItemDropdowns(createTbody);
        }
        
        // Setup modal event listeners
        const createPOModal = document.getElementById('createPurchaseOrderModal');
        if (createPOModal) {
            createPOModal.addEventListener('show.bs.modal', function() {
                // Ensure dropdowns are initialized when modal opens
                if (!tomSelectInstances.create.vendor || !tomSelectInstances.create.vendor.input) {
                    initCreateModalDropdowns();
                }
            });
        }

        // Setup edit modal event listeners
        const editPOModal = document.getElementById('editPurchaseOrderModal');
        if (editPOModal) {
            editPOModal.addEventListener('shown.bs.modal', function() {
                // Reinitialize edit modal dropdowns to ensure they work properly
                if (!tomSelectInstances.edit.store || !tomSelectInstances.edit.store.input) {
                    initEditModalDropdowns();
                }
            });
        }
    });

})();
</script>
@endsection
