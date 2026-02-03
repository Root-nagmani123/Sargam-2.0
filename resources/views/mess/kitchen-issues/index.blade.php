@extends('admin.layouts.master')
@section('title', 'Selling Voucher')
@section('setup_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Selling Voucher</h4>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSellingVoucherModal">ADD Selling Voucher</button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.mess.material-management.index') }}">
                <div class="row g-2">
                    <div class="col-md-2">
                        <label class="form-label small">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Pending</option>
                            <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>Approved</option>
                            <option value="4" {{ request('status') == '4' ? 'selected' : '' }}>Completed</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Store</label>
                        <select name="store" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ request('store') == $store->id ? 'selected' : '' }}>{{ $store->store_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Start Date</label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">End Date</label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead style="background-color: #af2910;">
                <tr>
                    <th style="color: #fff; width: 50px;">Serial No.</th>
                    <th style="color: #fff;">Item Name</th>
                    <th style="color: #fff;">Item Quantity</th>
                    <th style="color: #fff;">Return Quantity</th>
                    <th style="color: #fff;">Transfer From Store</th>
                    <th style="color: #fff;">Client Type</th>
                    <th style="color: #fff;">Client Name</th>
                    <th style="color: #fff;">Name</th>
                    <th style="color: #fff;">Payment Type</th>
                    <th style="color: #fff;">Request Date</th>
                    <th style="color: #fff;">Status</th>
                    <th style="color: #fff;">Return Item</th>
                    <th style="color: #fff; min-width: 120px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @php $serial = $kitchenIssues->firstItem() ?: 0; @endphp
                @forelse($kitchenIssues as $voucher)
                    @forelse($voucher->items as $item)
                        <tr>
                            <td>{{ $serial++ }}</td>
                            <td>{{ $item->item_name ?: ($item->itemSubcategory->item_name ?? '—') }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->return_quantity ?? 0 }}</td>
                            <td>{{ $voucher->storeMaster->store_name ?? '—' }}</td>
                            <td>{{ $voucher->clientTypeCategory ? ucfirst($voucher->clientTypeCategory->client_type ?? '') : '—' }}</td>
                            <td>{{ $voucher->clientTypeCategory ? ($voucher->clientTypeCategory->client_name ?? '—') : '—' }}</td>
                            <td>{{ $voucher->client_name ?? '—' }}</td>
                            <td>{{ $voucher->payment_type == 1 ? 'Credit' : ($voucher->payment_type == 0 ? 'Cash' : ($voucher->payment_type == 2 ? 'Online' : '—')) }}</td>
                            <td>{{ $voucher->request_date ? \Carbon\Carbon::parse($voucher->request_date)->format('d/m/Y') : '—' }}</td>
                            <td>
                                @if($voucher->status == 0)<span class="badge bg-warning">Pending</span>
                                @elseif($voucher->status == 2)<span class="badge bg-success">Approved</span>
                                @elseif($voucher->status == 4)<span class="badge bg-primary">Completed</span>
                                @else<span class="badge bg-secondary">{{ $voucher->status }}</span>@endif
                            </td>
                            <td>
                                @if(($item->return_quantity ?? 0) > 0)
                                    <span class="badge bg-info">Returned</span>
                                @else
                                    —
                                @endif
                                @if($loop->first)
                                    <button type="button" class="btn btn-sm btn-outline-secondary ms-1 btn-return-sv" data-voucher-id="{{ $voucher->pk }}" title="Return">Return</button>
                                @endif
                            </td>
                            <td>
                                @if($loop->first)
                                    <button type="button" class="btn btn-sm btn-info btn-view-sv" data-voucher-id="{{ $voucher->pk }}" title="View">View</button>
                                    @if($voucher->approve_status != 1)
                                        <button type="button" class="btn btn-sm btn-warning btn-edit-sv" data-voucher-id="{{ $voucher->pk }}" title="Edit">Edit</button>
                                    @endif
                                    <form action="{{ route('admin.mess.material-management.destroy', $voucher->pk) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this Selling Voucher?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td>{{ $serial++ }}</td>
                            <td>—</td>
                            <td>—</td>
                            <td>—</td>
                            <td>{{ $voucher->storeMaster->store_name ?? '—' }}</td>
                            <td>{{ $voucher->clientTypeCategory ? ucfirst($voucher->clientTypeCategory->client_type ?? '') : '—' }}</td>
                            <td>{{ $voucher->clientTypeCategory ? ($voucher->clientTypeCategory->client_name ?? '—') : '—' }}</td>
                            <td>{{ $voucher->client_name ?? '—' }}</td>
                            <td>—</td>
                            <td>{{ $voucher->request_date ? \Carbon\Carbon::parse($voucher->request_date)->format('d/m/Y') : '—' }}</td>
                            <td><span class="badge bg-secondary">{{ $voucher->status }}</span></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-return-sv" data-voucher-id="{{ $voucher->pk }}" title="Return">Return</button>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info btn-view-sv" data-voucher-id="{{ $voucher->pk }}" title="View">View</button>
                                @if($voucher->approve_status != 1)
                                    <button type="button" class="btn btn-sm btn-warning btn-edit-sv" data-voucher-id="{{ $voucher->pk }}" title="Edit">Edit</button>
                                @endif
                                <form action="{{ route('admin.mess.material-management.destroy', $voucher->pk) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this Selling Voucher?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforelse
                @empty
                    <tr>
                        <td colspan="13" class="text-center py-4">No selling vouchers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-center mt-3">
        {{ $kitchenIssues->links() }}
    </div>
</div>

{{-- Add Selling Voucher Modal (same UI/UX as Create Purchase Order) --}}
<style>
#addSellingVoucherModal .modal-dialog { max-height: calc(100vh - 2rem); margin: 1rem auto; }
#addSellingVoucherModal .modal-content { max-height: calc(100vh - 2rem); display: flex; flex-direction: column; }
#addSellingVoucherModal .modal-body { overflow-y: auto; max-height: calc(100vh - 10rem); }
</style>
<div class="modal fade" id="addSellingVoucherModal" tabindex="-1" aria-labelledby="addSellingVoucherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form action="{{ route('admin.mess.material-management.store') }}" method="POST" id="sellingVoucherModalForm">
                @csrf
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="addSellingVoucherModalLabel">Add Selling Voucher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                            <ul class="mb-0 small">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    {{-- Voucher Details (same pattern as Order Details) --}}
                    <div class="card mb-4">
                        <div class="card-header bg-white py-2">
                            <h6 class="mb-0 fw-semibold text-primary">Voucher Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Client Type <span class="text-danger">*</span></label>
                                    <div class="d-flex flex-wrap gap-3 pt-1">
                                        @foreach($clientTypes as $slug => $label)
                                            <div class="form-check">
                                                <input class="form-check-input client-type-radio" type="radio" name="client_type_slug" id="modal_ct_{{ $slug }}" value="{{ $slug }}" {{ old('client_type_slug') === $slug ? 'checked' : '' }} required>
                                                <label class="form-check-label" for="modal_ct_{{ $slug }}">{{ $label }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Payment Type <span class="text-danger">*</span></label>
                                    <select name="payment_type" class="form-select" required>
                                        <option value="1" {{ old('payment_type', '1') == '1' ? 'selected' : '' }}>Credit</option>
                                        <option value="0" {{ old('payment_type') == '0' ? 'selected' : '' }}>Cash</option>
                                        <option value="2" {{ old('payment_type') == '2' ? 'selected' : '' }}>Online</option>
                                    </select>
                                    <small class="text-muted" id="modalPaymentTypeHint">Employee / OT / Course: Credit only</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Client Name <span class="text-danger">*</span></label>
                                    <select name="client_type_pk" class="form-select" id="modalClientNameSelect">
                                        <option value="">Select Client Name</option>
                                        @foreach($clientNamesByType as $type => $list)
                                            @foreach($list as $c)
                                                <option value="{{ $c->id }}" data-type="{{ $c->client_type }}">{{ $c->client_name }}</option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" name="client_name" class="form-control" value="{{ old('client_name') }}" placeholder="Client / section / role name" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Issue Date <span class="text-danger">*</span></label>
                                    <input type="date" name="issue_date" class="form-control" value="{{ old('issue_date', date('Y-m-d')) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Transfer From Store <span class="text-danger">*</span></label>
                                    <select name="inve_store_master_pk" class="form-select" required>
                                        <option value="">Select Store</option>
                                        @foreach($stores as $store)
                                            <option value="{{ $store->id }}" {{ old('inve_store_master_pk') == $store->id ? 'selected' : '' }}>{{ $store->store_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Remarks</label>
                                    <input type="text" name="remarks" class="form-control" value="{{ old('remarks') }}" placeholder="Remarks (optional)">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Item Details (same pattern as Purchase Order Item Details) --}}
                    <div class="card mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                            <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="modalAddItemRow">
                                + Add Item
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0" id="svItemsTable">
                                    <thead style="background-color: #af2910;">
                                        <tr>
                                            <th style="min-width: 180px; color: #fff; border-color: #af2910;">Item Name <span class="text-white">*</span></th>
                                            <th style="min-width: 80px; color: #fff; border-color: #af2910;">Unit</th>
                                            <th style="min-width: 100px; color: #fff; border-color: #af2910;">Available Qty</th>
                                            <th style="min-width: 90px; color: #fff; border-color: #af2910;">Issue Qty <span class="text-white">*</span></th>
                                            <th style="min-width: 90px; color: #fff; border-color: #af2910;">Left Qty</th>
                                            <th style="min-width: 100px; color: #fff; border-color: #af2910;">Rate <span class="text-white">*</span></th>
                                            <th style="min-width: 110px; color: #fff; border-color: #af2910;">Total Amount</th>
                                            <th style="width: 50px; color: #fff; border-color: #af2910;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="modalItemsBody">
                                        <tr class="sv-item-row">
                                            <td>
                                                <select name="items[0][item_subcategory_id]" class="form-select form-select-sm sv-item-select" required>
                                                    <option value="">Select Item</option>
                                                    @foreach($itemSubcategories as $s)
                                                        <option value="{{ $s['id'] }}" data-unit="{{ e($s['unit_measurement'] ?? '') }}">{{ e($s['item_name'] ?? '—') }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="text" name="items[0][unit]" class="form-control form-control-sm sv-unit" readonly placeholder="—"></td>
                                            <td><input type="number" name="items[0][available_quantity]" class="form-control form-control-sm sv-avail" step="0.01" min="0" value="0" placeholder="0"></td>
                                            <td><input type="number" name="items[0][quantity]" class="form-control form-control-sm sv-qty" step="0.01" min="0.01" placeholder="0" required></td>
                                            <td><input type="text" class="form-control form-control-sm sv-left bg-light" readonly placeholder="0"></td>
                                            <td><input type="number" name="items[0][rate]" class="form-control form-control-sm sv-rate" step="0.01" min="0" placeholder="0" required></td>
                                            <td><input type="text" class="form-control form-control-sm sv-total bg-light" readonly placeholder="0.00"></td>
                                            <td><button type="button" class="btn btn-sm btn-outline-danger sv-remove-row" disabled title="Remove">×</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-light d-flex justify-content-end align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-semibold">Grand Total:</span>
                                <span class="fs-5 text-primary fw-bold" id="modalGrandTotal">₹0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Selling Voucher</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Selling Voucher Modal --}}
<style>
#editSellingVoucherModal .modal-dialog { max-height: calc(100vh - 2rem); margin: 1rem auto; }
#editSellingVoucherModal .modal-content { max-height: calc(100vh - 2rem); display: flex; flex-direction: column; }
#editSellingVoucherModal .modal-body { overflow-y: auto; max-height: calc(100vh - 10rem); }
</style>
<div class="modal fade" id="editSellingVoucherModal" tabindex="-1" aria-labelledby="editSellingVoucherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form id="editSellingVoucherForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="editSellingVoucherModalLabel">Edit Selling Voucher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card mb-4">
                        <div class="card-header bg-white py-2">
                            <h6 class="mb-0 fw-semibold text-primary">Voucher Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Client Type <span class="text-danger">*</span></label>
                                    <div class="d-flex flex-wrap gap-3 pt-1">
                                        @foreach($clientTypes as $slug => $label)
                                            <div class="form-check">
                                                <input class="form-check-input edit-client-type-radio" type="radio" name="client_type_slug" id="edit_ct_{{ $slug }}" value="{{ $slug }}" required>
                                                <label class="form-check-label" for="edit_ct_{{ $slug }}">{{ $label }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Payment Type <span class="text-danger">*</span></label>
                                    <select name="payment_type" class="form-select edit-payment-type" required>
                                        <option value="1">Credit</option>
                                        <option value="0">Cash</option>
                                        <option value="2">Online</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Client Name <span class="text-danger">*</span></label>
                                    <select name="client_type_pk" class="form-select" id="editClientNameSelect">
                                        <option value="">Select Client Name</option>
                                        @foreach($clientNamesByType as $type => $list)
                                            @foreach($list as $c)
                                                <option value="{{ $c->id }}" data-type="{{ $c->client_type }}">{{ $c->client_name }}</option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" name="client_name" class="form-control edit-client-name" placeholder="Client / section / role name" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Issue Date <span class="text-danger">*</span></label>
                                    <input type="date" name="issue_date" class="form-control edit-issue-date" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Transfer From Store <span class="text-danger">*</span></label>
                                    <select name="inve_store_master_pk" class="form-select edit-store" required>
                                        <option value="">Select Store</option>
                                        @foreach($stores as $store)
                                            <option value="{{ $store->id }}">{{ $store->store_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Remarks</label>
                                    <input type="text" name="remarks" class="form-control edit-remarks" placeholder="Remarks (optional)">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                            <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="editModalAddItemRow">+ Add Item</button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead style="background-color: #af2910;">
                                        <tr>
                                            <th style="min-width: 180px; color: #fff; border-color: #af2910;">Item Name <span class="text-white">*</span></th>
                                            <th style="min-width: 80px; color: #fff; border-color: #af2910;">Unit</th>
                                            <th style="min-width: 100px; color: #fff; border-color: #af2910;">Available Qty</th>
                                            <th style="min-width: 90px; color: #fff; border-color: #af2910;">Issue Qty <span class="text-white">*</span></th>
                                            <th style="min-width: 90px; color: #fff; border-color: #af2910;">Left Qty</th>
                                            <th style="min-width: 100px; color: #fff; border-color: #af2910;">Rate <span class="text-white">*</span></th>
                                            <th style="min-width: 110px; color: #fff; border-color: #af2910;">Total Amount</th>
                                            <th style="width: 50px; color: #fff; border-color: #af2910;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="editModalItemsBody"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-light d-flex justify-content-end align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-semibold">Grand Total:</span>
                                <span class="fs-5 text-primary fw-bold" id="editModalGrandTotal">₹0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Selling Voucher</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- View Selling Voucher Modal - ensure all text is visible (high contrast) --}}
<style>
#viewSellingVoucherModal .modal-dialog { max-height: calc(100vh - 2rem); margin: 1rem auto; }
#viewSellingVoucherModal .modal-content { max-height: calc(100vh - 2rem); display: flex; flex-direction: column; background: #fff; color: #212529; }
#viewSellingVoucherModal .modal-header { background: #f8f9fa !important; color: #212529 !important; }
#viewSellingVoucherModal .modal-title { color: #212529 !important; }
#viewSellingVoucherModal .modal-body { overflow-y: auto; max-height: calc(100vh - 10rem); background: #fff; color: #212529 !important; }
#viewSellingVoucherModal .modal-body *, #viewSellingVoucherModal .modal-body p, #viewSellingVoucherModal .modal-body span { color: inherit; }
#viewSellingVoucherModal .card { background: #fff; color: #212529; }
#viewSellingVoucherModal .card-header { background: #fff !important; color: #212529 !important; border-color: #dee2e6; }
#viewSellingVoucherModal .card-header h6 { color: #0d6efd !important; }
#viewSellingVoucherModal .card-body { background: #fff !important; color: #212529 !important; }
#viewSellingVoucherModal .card-body table th { color: #495057 !important; font-weight: 600; }
#viewSellingVoucherModal .card-body table td { color: #212529 !important; }
#viewSellingVoucherModal #viewItemsCard .table thead th { color: #fff !important; background: #af2910 !important; border-color: #af2910; }
#viewSellingVoucherModal #viewItemsCard .table tbody td { color: #212529 !important; background: #fff !important; }
#viewSellingVoucherModal #viewModalGrandTotal { color: #212529 !important; }
#viewSellingVoucherModal .text-muted { color: #495057 !important; }
#viewSellingVoucherModal .card-footer { background: #f8f9fa !important; color: #212529 !important; }
#viewSellingVoucherModal .card-footer strong { color: #212529 !important; }
#viewSellingVoucherModal .badge { color: #212529 !important; }
#viewSellingVoucherModal .modal-footer { background: #fff; border-color: #dee2e6; }
</style>
<div class="modal fade" id="viewSellingVoucherModal" tabindex="-1" aria-labelledby="viewSellingVoucherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-semibold" id="viewSellingVoucherModalLabel" style="color: #212529;">View Selling Voucher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="card mb-4">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0 fw-semibold text-primary">Voucher Details</h6>
                    </div>
                    <div class="card-body" style="color: #212529;">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless table-sm">
                                    <tr><th width="40%" style="color: #495057;">Request Date:</th><td id="viewRequestDate" style="color: #212529;">—</td></tr>
                                    <tr><th style="color: #495057;">Issue Date:</th><td id="viewIssueDate" style="color: #212529;">—</td></tr>
                                    <tr><th style="color: #495057;">Transfer From Store:</th><td id="viewStoreName" style="color: #212529;">—</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless table-sm">
                                    <tr><th width="40%" style="color: #495057;">Client Type:</th><td id="viewClientType" style="color: #212529;">—</td></tr>
                                    <tr><th style="color: #495057;">Client Name:</th><td id="viewClientName" style="color: #212529;">—</td></tr>
                                    <tr><th style="color: #495057;">Payment Type:</th><td id="viewPaymentType" style="color: #212529;">—</td></tr>
                                    <tr><th style="color: #495057;">Status:</th><td id="viewStatus" style="color: #212529;">—</td></tr>
                                </table>
                            </div>
                        </div>
                        <p class="mb-0 mt-2" id="viewRemarksWrap" style="display:none; color: #212529;"><strong>Remarks:</strong> <span id="viewRemarks"></span></p>
                    </div>
                </div>
                <div class="card mb-4" id="viewItemsCard">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead style="background-color: #af2910;">
                                    <tr>
                                        <th style="color: #fff !important; border-color: #af2910;">Item Name</th>
                                        <th style="color: #fff !important; border-color: #af2910;">Unit</th>
                                        <th style="color: #fff !important; border-color: #af2910;">Issue Qty</th>
                                        <th style="color: #fff !important; border-color: #af2910;">Return Qty</th>
                                        <th style="color: #fff !important; border-color: #af2910;">Rate</th>
                                        <th style="color: #fff !important; border-color: #af2910;">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="viewModalItemsBody"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-light d-flex justify-content-end" style="color: #212529;">
                        <strong>Grand Total: ₹<span id="viewModalGrandTotal">0.00</span></strong>
                    </div>
                </div>
                <div class="small" style="color: #495057;">
                    Created: <span id="viewCreatedAt" style="color: #212529;">—</span>
                    <span class="ms-3" id="viewUpdatedAtWrap" style="display:none;">Last Updated: <span id="viewUpdatedAt" style="color: #212529;"></span></span>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Return Item Modal (Transfer To) --}}
<div class="modal fade" id="returnItemModal" tabindex="-1" aria-labelledby="returnItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="returnItemForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="returnItemModalLabel">Transfer To</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Transfer From Store</label>
                        <p class="mb-0 form-control-plaintext" id="returnTransferFromStore">—</p>
                    </div>
                    <div class="card">
                        <div class="card-header bg-white py-2">
                            <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead style="background-color: #af2910;">
                                        <tr>
                                            <th style="color: #fff;">Item Name</th>
                                            <th style="color: #fff;">Issued Quantity</th>
                                            <th style="color: #fff;">Item Unit</th>
                                            <th style="color: #fff;">Return Quantity</th>
                                            <th style="color: #fff;">Return Date</th>
                                        </tr>
                                    </thead>
                                    <tbody id="returnItemModalBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    const itemSubcategories = @json($itemSubcategories);
    const editSvBaseUrl = "{{ url('admin/mess/material-management') }}";
    const viewSvBaseUrl = "{{ url('admin/mess/material-management') }}";
    const returnSvBaseUrl = "{{ url('admin/mess/material-management') }}";
    let rowIndex = 1;
    let editRowIndex = 0;

    function getRowHtml(index) {
        const options = itemSubcategories.map(s =>
            '<option value="' + s.id + '" data-unit="' + (s.unit_measurement || '').replace(/"/g, '&quot;') + '">' + (s.item_name || '—').replace(/</g, '&lt;') + '</option>'
        ).join('');
        return '<tr class="sv-item-row">' +
            '<td><select name="items[' + index + '][item_subcategory_id]" class="form-select form-select-sm sv-item-select" required><option value="">Select Item</option>' + options + '</select></td>' +
            '<td><input type="text" name="items[' + index + '][unit]" class="form-control form-control-sm sv-unit" readonly placeholder="—"></td>' +
            '<td><input type="number" name="items[' + index + '][available_quantity]" class="form-control form-control-sm sv-avail" step="0.01" min="0" value="0" placeholder="0"></td>' +
            '<td><input type="number" name="items[' + index + '][quantity]" class="form-control form-control-sm sv-qty" step="0.01" min="0.01" placeholder="0" required></td>' +
            '<td><input type="text" class="form-control form-control-sm sv-left bg-light" readonly placeholder="0"></td>' +
            '<td><input type="number" name="items[' + index + '][rate]" class="form-control form-control-sm sv-rate" step="0.01" min="0" placeholder="0" required></td>' +
            '<td><input type="text" class="form-control form-control-sm sv-total bg-light" readonly placeholder="0.00"></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger sv-remove-row" title="Remove">×</button></td>' +
            '</tr>';
    }

    function updateUnit(row) {
        const sel = row.querySelector('.sv-item-select');
        const opt = sel && sel.options[sel.selectedIndex];
        const unitInp = row.querySelector('.sv-unit');
        if (unitInp) unitInp.value = opt && opt.dataset.unit ? opt.dataset.unit : '';
    }

    function calcRow(row) {
        const avail = parseFloat(row.querySelector('.sv-avail').value) || 0;
        const qty = parseFloat(row.querySelector('.sv-qty').value) || 0;
        const rate = parseFloat(row.querySelector('.sv-rate').value) || 0;
        const left = Math.max(0, avail - qty);
        const total = qty * rate;
        row.querySelector('.sv-left').value = left;
        row.querySelector('.sv-total').value = total.toFixed(2);
    }

    function updateGrandTotal() {
        let sum = 0;
        document.querySelectorAll('#modalItemsBody .sv-item-row').forEach(row => {
            const t = row.querySelector('.sv-total');
            if (t && t.value) sum += parseFloat(t.value) || 0;
        });
        const el = document.getElementById('modalGrandTotal');
        if (el) el.textContent = '₹' + sum.toFixed(2);
    }

    function updateRemoveButtons() {
        const rows = document.querySelectorAll('#modalItemsBody .sv-item-row');
        rows.forEach(row => {
            const btn = row.querySelector('.sv-remove-row');
            if (btn) btn.disabled = rows.length <= 1;
        });
    }

    document.getElementById('modalAddItemRow').addEventListener('click', function() {
        const tbody = document.getElementById('modalItemsBody');
        tbody.insertAdjacentHTML('beforeend', getRowHtml(rowIndex));
        rowIndex++;
        updateRemoveButtons();
    });

    document.getElementById('modalItemsBody').addEventListener('change', function(e) {
        if (e.target.classList.contains('sv-item-select')) {
            const row = e.target.closest('.sv-item-row');
            if (row) { updateUnit(row); calcRow(row); updateGrandTotal(); }
        }
    });

    document.getElementById('modalItemsBody').addEventListener('input', function(e) {
        if (e.target.classList.contains('sv-avail') || e.target.classList.contains('sv-qty') || e.target.classList.contains('sv-rate')) {
            const row = e.target.closest('.sv-item-row');
            if (row) { calcRow(row); updateGrandTotal(); }
        }
    });

    document.getElementById('modalItemsBody').addEventListener('click', function(e) {
        if (e.target.classList.contains('sv-remove-row')) {
            const row = e.target.closest('.sv-item-row');
            if (row && document.querySelectorAll('#modalItemsBody .sv-item-row').length > 1) {
                row.remove();
                updateGrandTotal();
                updateRemoveButtons();
            }
        }
    });

    const creditOnly = ['employee', 'ot', 'course', 'section'];
    document.querySelectorAll('#addSellingVoucherModal .client-type-radio').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const paymentSelect = document.querySelector('#addSellingVoucherModal select[name="payment_type"]');
            const hint = document.getElementById('modalPaymentTypeHint');
            if (creditOnly.indexOf(this.value) !== -1) {
                if (paymentSelect) paymentSelect.value = '1';
                paymentSelect && paymentSelect.querySelectorAll('option').forEach(function(opt) {
                    opt.disabled = (opt.value !== '' && opt.value !== '1');
                });
                if (hint) hint.textContent = 'Credit only for this client type';
            } else {
                paymentSelect && paymentSelect.querySelectorAll('option').forEach(function(opt) { opt.disabled = false; });
                if (hint) hint.textContent = 'Cash / Online / Credit';
            }
            const clientSelect = document.getElementById('modalClientNameSelect');
            if (clientSelect) {
                clientSelect.querySelectorAll('option').forEach(function(opt) {
                    if (opt.value === '') { opt.hidden = false; return; }
                    opt.hidden = opt.dataset.type !== this.value;
                }.bind(this));
            }
        });
    });
    const checked = document.querySelector('#addSellingVoucherModal .client-type-radio:checked');
    if (checked) checked.dispatchEvent(new Event('change'));

    function getEditRowHtml(index, item) {
        const options = itemSubcategories.map(s =>
            '<option value="' + s.id + '" data-unit="' + (s.unit_measurement || '').replace(/"/g, '&quot;') + '"' + (item && item.item_subcategory_id == s.id ? ' selected' : '') + '>' + (s.item_name || '—').replace(/</g, '&lt;') + '</option>'
        ).join('');
        const qty = item ? item.quantity : '';
        const avail = item ? item.available_quantity : 0;
        const rate = item ? item.rate : '';
        const total = item ? item.amount : '';
        const unit = item ? (item.unit || '') : '';
        const left = item && (avail - qty) >= 0 ? (avail - qty) : 0;
        return '<tr class="sv-item-row edit-sv-item-row">' +
            '<td><select name="items[' + index + '][item_subcategory_id]" class="form-select form-select-sm sv-item-select" required><option value="">Select Item</option>' + options + '</select></td>' +
            '<td><input type="text" name="items[' + index + '][unit]" class="form-control form-control-sm sv-unit" readonly placeholder="—" value="' + (unit || '') + '"></td>' +
            '<td><input type="number" name="items[' + index + '][available_quantity]" class="form-control form-control-sm sv-avail" step="0.01" min="0" value="' + avail + '" placeholder="0"></td>' +
            '<td><input type="number" name="items[' + index + '][quantity]" class="form-control form-control-sm sv-qty" step="0.01" min="0.01" placeholder="0" value="' + qty + '" required></td>' +
            '<td><input type="text" class="form-control form-control-sm sv-left bg-light" readonly placeholder="0" value="' + left + '"></td>' +
            '<td><input type="number" name="items[' + index + '][rate]" class="form-control form-control-sm sv-rate" step="0.01" min="0" placeholder="0" value="' + rate + '" required></td>' +
            '<td><input type="text" class="form-control form-control-sm sv-total bg-light" readonly placeholder="0.00" value="' + (total ? total.toFixed(2) : '') + '"></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger sv-remove-row edit-sv-remove-row" title="Remove">×</button></td>' +
            '</tr>';
    }

    function updateEditGrandTotal() {
        let sum = 0;
        document.querySelectorAll('#editModalItemsBody .sv-item-row').forEach(row => {
            const t = row.querySelector('.sv-total');
            if (t && t.value) sum += parseFloat(t.value) || 0;
        });
        const el = document.getElementById('editModalGrandTotal');
        if (el) el.textContent = '₹' + sum.toFixed(2);
    }

    function updateEditRemoveButtons() {
        const rows = document.querySelectorAll('#editModalItemsBody .sv-item-row');
        rows.forEach(row => {
            const btn = row.querySelector('.sv-remove-row');
            if (btn) btn.disabled = rows.length <= 1;
        });
    }

    document.querySelectorAll('.btn-view-sv').forEach(btn => {
        btn.addEventListener('click', function() {
            const voucherId = this.getAttribute('data-voucher-id');
            fetch(viewSvBaseUrl + '/' + voucherId, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(data => {
                    const v = data.voucher;
                    const items = data.items || [];
                    document.getElementById('viewSellingVoucherModalLabel').textContent = 'View Selling Voucher #' + (v.pk || voucherId);
                    document.getElementById('viewRequestDate').textContent = v.request_date || '—';
                    document.getElementById('viewIssueDate').textContent = v.issue_date || '—';
                    document.getElementById('viewStoreName').textContent = v.store_name || '—';
                    document.getElementById('viewClientType').textContent = v.client_type || '—';
                    document.getElementById('viewClientName').textContent = v.client_name || '—';
                    document.getElementById('viewPaymentType').textContent = v.payment_type || '—';
                    const statusEl = document.getElementById('viewStatus');
                    statusEl.innerHTML = v.status === 0 ? '<span class="badge bg-warning">Pending</span>' : (v.status === 2 ? '<span class="badge bg-success">Approved</span>' : (v.status === 4 ? '<span class="badge bg-primary">Completed</span>' : '<span class="badge bg-secondary">' + (v.status_label || v.status) + '</span>'));
                    if (v.remarks) {
                        document.getElementById('viewRemarksWrap').style.display = 'block';
                        document.getElementById('viewRemarks').textContent = v.remarks;
                    } else {
                        document.getElementById('viewRemarksWrap').style.display = 'none';
                    }
                    const tbody = document.getElementById('viewModalItemsBody');
                    tbody.innerHTML = '';
                    if (data.has_items && items.length > 0) {
                        document.getElementById('viewItemsCard').style.display = 'block';
                        items.forEach(function(item) {
                            tbody.insertAdjacentHTML('beforeend', '<tr><td>' + (item.item_name || '—') + '</td><td>' + (item.unit || '—') + '</td><td>' + item.quantity + '</td><td>' + (item.return_quantity || 0) + '</td><td>₹' + item.rate + '</td><td>₹' + item.amount + '</td></tr>');
                        });
                        document.getElementById('viewModalGrandTotal').textContent = data.grand_total || '0.00';
                    } else {
                        document.getElementById('viewItemsCard').style.display = 'none';
                    }
                    document.getElementById('viewCreatedAt').textContent = v.created_at || '—';
                    if (v.updated_at) {
                        document.getElementById('viewUpdatedAtWrap').style.display = 'inline';
                        document.getElementById('viewUpdatedAt').textContent = v.updated_at;
                    } else {
                        document.getElementById('viewUpdatedAtWrap').style.display = 'none';
                    }
                    new bootstrap.Modal(document.getElementById('viewSellingVoucherModal')).show();
                })
                .catch(err => { console.error(err); alert('Failed to load selling voucher.'); });
        });
    });

    document.querySelectorAll('.btn-return-sv').forEach(btn => {
        btn.addEventListener('click', function() {
            const voucherId = this.getAttribute('data-voucher-id');
            fetch(returnSvBaseUrl + '/' + voucherId + '/return', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(data => {
                    document.getElementById('returnTransferFromStore').textContent = data.store_name || '—';
                    const tbody = document.getElementById('returnItemModalBody');
                    tbody.innerHTML = '';
                    (data.items || []).forEach(function(item, i) {
                        const id = (item.id != null) ? item.id : '';
                        const name = (item.item_name || '—').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                        const qty = item.quantity != null ? item.quantity : '';
                        const unit = (item.unit || '—').replace(/</g, '&lt;');
                        const retQty = item.return_quantity != null ? item.return_quantity : 0;
                        const retDate = item.return_date || '';
                        tbody.insertAdjacentHTML('beforeend',
                            '<tr><td>' + name + '<input type="hidden" name="items[' + i + '][id]" value="' + id + '"></td><td>' + qty + '</td><td>' + unit + '</td>' +
                            '<td><input type="number" name="items[' + i + '][return_quantity]" class="form-control form-control-sm" step="0.01" min="0" value="' + retQty + '"></td>' +
                            '<td><input type="date" name="items[' + i + '][return_date]" class="form-control form-control-sm" value="' + retDate + '"></td></tr>');
                    });
                    document.getElementById('returnItemForm').action = returnSvBaseUrl + '/' + voucherId + '/return';
                    new bootstrap.Modal(document.getElementById('returnItemModal')).show();
                })
                .catch(err => { console.error(err); alert('Failed to load return data.'); });
        });
    });

    document.querySelectorAll('.btn-edit-sv').forEach(btn => {
        btn.addEventListener('click', function() {
            const voucherId = this.getAttribute('data-voucher-id');
            fetch(editSvBaseUrl + '/' + voucherId + '/edit', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(data => {
                    if (data.error) { alert(data.error); return; }
                    const v = data.voucher;
                    const items = data.items || [];
                    document.getElementById('editSellingVoucherForm').action = editSvBaseUrl + '/' + voucherId;
                    document.querySelector('#editSellingVoucherModal input[name="client_type_slug"][value="' + (v.client_type_slug || 'employee') + '"]').checked = true;
                    document.querySelector('#editSellingVoucherModal select.edit-payment-type').value = String(v.payment_type ?? 1);
                    document.querySelector('#editSellingVoucherModal select[name="client_type_pk"]').value = v.client_type_pk || '';
                    document.querySelector('#editSellingVoucherModal input.edit-client-name').value = v.client_name || '';
                    document.querySelector('#editSellingVoucherModal input.edit-issue-date').value = v.issue_date || '';
                    document.querySelector('#editSellingVoucherModal select.edit-store').value = v.inve_store_master_pk || '';
                    document.querySelector('#editSellingVoucherModal input.edit-remarks').value = v.remarks || '';
                    const tbody = document.getElementById('editModalItemsBody');
                    tbody.innerHTML = '';
                    if (items.length === 0) {
                        tbody.insertAdjacentHTML('beforeend', getEditRowHtml(0, null));
                        editRowIndex = 1;
                    } else {
                        items.forEach((item, i) => {
                            tbody.insertAdjacentHTML('beforeend', getEditRowHtml(i, item));
                        });
                        editRowIndex = items.length;
                    }
                    updateEditGrandTotal();
                    updateEditRemoveButtons();
                    new bootstrap.Modal(document.getElementById('editSellingVoucherModal')).show();
                })
                .catch(err => { console.error(err); alert('Failed to load selling voucher.'); });
        });
    });

    document.getElementById('editModalAddItemRow').addEventListener('click', function() {
        const tbody = document.getElementById('editModalItemsBody');
        tbody.insertAdjacentHTML('beforeend', getEditRowHtml(editRowIndex, null));
        editRowIndex++;
        updateEditRemoveButtons();
    });

    document.getElementById('editModalItemsBody').addEventListener('change', function(e) {
        if (e.target.classList.contains('sv-item-select')) {
            const row = e.target.closest('.sv-item-row');
            if (row) { updateUnit(row); calcRow(row); updateEditGrandTotal(); }
        }
    });
    document.getElementById('editModalItemsBody').addEventListener('input', function(e) {
        if (e.target.classList.contains('sv-avail') || e.target.classList.contains('sv-qty') || e.target.classList.contains('sv-rate')) {
            const row = e.target.closest('.sv-item-row');
            if (row) { calcRow(row); updateEditGrandTotal(); }
        }
    });
    document.getElementById('editModalItemsBody').addEventListener('click', function(e) {
        if (e.target.classList.contains('sv-remove-row')) {
            const row = e.target.closest('.sv-item-row');
            if (row && document.querySelectorAll('#editModalItemsBody .sv-item-row').length > 1) {
                row.remove();
                updateEditGrandTotal();
                updateEditRemoveButtons();
            }
        }
    });

    @if($errors->any() || session('open_selling_voucher_modal'))
    document.addEventListener('DOMContentLoaded', function() {
        var modal = document.getElementById('addSellingVoucherModal');
        if (modal && typeof bootstrap !== 'undefined') {
            (new bootstrap.Modal(modal)).show();
        }
    });
    @endif
})();
</script>
@endsection
