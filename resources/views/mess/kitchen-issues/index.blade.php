@extends('admin.layouts.master')
@section('title', 'Selling Voucher')
@section('setup_content')
<div class="container-fluid py-3">
    <x-breadcrum title="Selling Voucher" />

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mt-2" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mt-2" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card mb-3 border-0 shadow-sm rounded-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h5 class="mb-1 fw-semibold">Selling Voucher</h5>
                    <p class="text-muted mb-0 small">Quickly filter and manage selling vouchers from here.</p>
                </div>
                <button type="button"
                        class="btn btn-primary d-inline-flex align-items-center gap-2"
                        data-bs-toggle="modal"
                        data-bs-target="#addSellingVoucherModal">
                    <span class="material-symbols-rounded" style="font-size: 1.1rem;">add</span>
                    <span class="fw-semibold">Add Selling Voucher</span>
                </button>
            </div>
            <div class="border rounded-3 bg-light p-3">
                <form method="GET" action="{{ route('admin.mess.material-management.index') }}">
                    <div class="row g-3">
                        <div class="col-md-2 col-sm-6">
                            <label class="form-label small text-muted mb-1">Status</label>
                            <select name="status" id="filter_status" class="form-select form-select-sm">
                                <option value="">All</option>
                                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Pending</option>
                                <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>Approved</option>
                                <option value="4" {{ request('status') == '4' ? 'selected' : '' }}>Completed</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <label class="form-label small text-muted mb-1">Store</label>
                            <select name="store" id="filter_store" class="form-select form-select-sm">
                                <option value="">All</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store['id'] }}" {{ request('store') == $store['id'] ? 'selected' : '' }}>{{ $store['store_name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <label class="form-label small text-muted mb-1">Start Date</label>
                            <input type="date" name="start_date" id="filter_start_date" class="form-control form-control-sm" value="{{ request('start_date') ?? date('Y-m-d') }}">
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <label class="form-label small text-muted mb-1">End Date</label>
                            <input type="date" name="end_date" id="filter_end_date" class="form-control form-control-sm" value="{{ request('end_date') }}" min="{{ request('start_date') ?? date('Y-m-d') }}">
                        </div>
                        <div class="col-md-4 d-flex align-items-end justify-content-md-end gap-2">
                            <button type="submit" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                                <span class="material-symbols-rounded" style="font-size: 1rem;">filter_list</span>
                                <span>Filter</span>
                            </button>
                            <a href="{{ route('admin.mess.material-management.index') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                                <span class="material-symbols-rounded" style="font-size: 1rem;">refresh</span>
                                <span>Clear</span>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table w-100 text-nowrap" id="sellingVouchersTable">
            <thead>
            <tr>
                    <th>S. No.</th>
                    <th>Item Name</th>
                    <th>Item Quantity</th>
                    <th>Return Quantity</th>
                    <th>Transfer From Store</th>
                    <th>Client Type</th>
                    <th>Client Name</th>
                    <th>Name</th>
                    <th>Payment Type</th>
                    <th>Request Date</th>
                    <th>Status</th>
                    <th>Return Item</th>
                    <th>Action</th>
                </tr>
            </thead>
            @php($serial = 1)
            <tbody>
                @forelse($kitchenIssues as $voucher)
                    @forelse($voucher->items as $item)
                        <tr>
                            <td>{{ $serial++ }}</td>
                            <td>{{ $item->item_name ?: ($item->itemSubcategory->item_name ?? '—') }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->return_quantity ?? 0 }}</td>
                            <td>{{ $voucher->resolved_store_name }}</td>
                            <td>{{ $voucher->client_type_label ?? '—' }}</td>
                            <td>{{ $voucher->display_client_name }}</td>
                            <td>{{ $voucher->client_name ?? '—' }}</td>
                            <td>{{ $voucher->payment_type == 1 ? 'Credit' : ($voucher->payment_type == 0 ? 'Cash' : ($voucher->payment_type == 2 ? 'UPI' : '—')) }}</td>
                            <td>{{ $voucher->created_at ? $voucher->created_at->format('d/m/Y') : '—' }}</td>
                            <td>
                                @if($voucher->status == 0)<span class="badge bg-warning">Pending</span>
                                @elseif($voucher->status == 2)<span class="badge bg-success">Approved</span>
                                @elseif($voucher->status == 4)<span class="badge bg-primary">Completed</span>
                                @else<span class="badge bg-secondary">{{ $voucher->status }}</span>@endif
                            </td>
                            <td>
                                @if(($item->return_quantity ?? 0) > 0)
                                    <span class="badge bg-info">Returned</span>
                                @endif
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-1 btn-return-sv" data-voucher-id="{{ $voucher->pk }}" title="Return">Return</button>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info btn-view-sv" data-voucher-id="{{ $voucher->pk }}" title="View">View</button>
                                <button type="button" class="btn btn-sm btn-warning btn-edit-sv" data-voucher-id="{{ $voucher->pk }}" title="{{ $voucher->status == \App\Models\KitchenIssueMaster::STATUS_APPROVED ? 'Edit is disabled for approved voucher' : 'Edit' }}" @if($voucher->status == \App\Models\KitchenIssueMaster::STATUS_APPROVED) disabled @endif>Edit</button>
                                <form action="{{ route('admin.mess.material-management.destroy', $voucher->pk) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this Selling Voucher?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete" style="display: none;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td>{{ $serial++ }}</td>
                            <td>—</td>
                            <td>—</td>
                            <td>—</td>
                            <td>{{ $voucher->resolved_store_name }}</td>
                            <td>{{ $voucher->client_type_label ?? '—' }}</td>
                            <td>{{ $voucher->display_client_name }}</td>
                            <td>{{ $voucher->client_name ?? '—' }}</td>
                            <td>—</td>
                            <td>{{ $voucher->created_at ? $voucher->created_at->format('d/m/Y') : '—' }}</td>
                            <td><span class="badge bg-secondary">{{ $voucher->status }}</span></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-return-sv" data-voucher-id="{{ $voucher->pk }}" title="Return">Return</button>
                            </td>
                            <td class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-sm btn-info btn-view-sv" data-voucher-id="{{ $voucher->pk }}" title="View">View</button>
                                <button type="button" class="btn btn-sm btn-warning btn-edit-sv" data-voucher-id="{{ $voucher->pk }}" title="{{ $voucher->status == \App\Models\KitchenIssueMaster::STATUS_APPROVED ? 'Edit is disabled for approved voucher' : 'Edit' }}" @if($voucher->status == \App\Models\KitchenIssueMaster::STATUS_APPROVED) disabled @endif>Edit</button>
                                <form action="{{ route('admin.mess.material-management.destroy', $voucher->pk) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this Selling Voucher?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete" style="display: none;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforelse
                @empty
                    <tr>
                        <td class="text-center py-4" colspan="12">No kitchen issues found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </div>
   </div>

    @include('components.mess-master-datatables', [
        'tableId' => 'sellingVouchersTable',
        'searchPlaceholder' => 'Search selling vouchers...',
        'ordering' => false,
        'actionColumnIndex' => 12,
        'infoLabel' => 'selling vouchers',
        'searchDelay' => 0
    ])
</div>

{{-- Tom Select CSS --}}
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">

{{-- Tom Select JS --}}
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.TomSelect) return;
    // Filter dropdowns aur Selling Voucher JS niche main script block me initialize ho raha hai.
});
</script>

{{-- Add Selling Voucher Modal (same UI/UX as Create Purchase Order) --}}
<style>
#addSellingVoucherModal .modal-dialog { max-height: calc(100vh - 2rem); margin: 1rem auto; }
#addSellingVoucherModal .modal-content { max-height: calc(100vh - 2rem); display: flex; flex-direction: column; }
#addSellingVoucherModal .modal-body { overflow-y: auto; max-height: calc(100vh - 10rem); position: relative; }
.ts-dropdown { z-index: 2000; }

/* Visually remove default first-option highlight in Tom Select dropdowns */
.ts-dropdown .option.active,
.ts-dropdown .option.selected,
.ts-dropdown .option[aria-selected="true"] {
    background-color: transparent !important;
    color: inherit !important;
}
</style>
<div class="modal fade" id="addSellingVoucherModal" tabindex="-1" aria-labelledby="addSellingVoucherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('admin.mess.material-management.store') }}" method="POST" id="sellingVoucherModalForm" enctype="multipart/form-data">
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
                                        <option value="2" {{ old('payment_type') == '2' ? 'selected' : '' }}>UPI</option>
                                    </select>
                                    <small class="text-muted" id="modalPaymentTypeHint">Cash / UPI / Credit</small>
                                </div>
                                <div class="col-md-4" id="modalClientNameWrap">
                                    <label class="form-label">Client Name <span class="text-danger">*</span></label>
                                    <select name="client_type_pk" class="form-select" id="modalClientNameSelect">
                                        <option value="">Select Client Name</option>
                                        @foreach($clientNamesByType as $type => $list)
                                            @foreach($list as $c)
                                                <option value="{{ $c->id }}" data-type="{{ $c->client_type }}" data-client-name="{{ strtolower($c->client_name ?? '') }}">{{ $c->client_name }}</option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                    <select id="modalOtCourseSelect" class="form-select" style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                            <option value="{{ $course->pk }}" data-course-name="{{ e($course->course_name) }}">{{ e($course->course_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="modalCourseSelect" class="form-select" style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                            <option value="{{ $course->pk }}" data-course-name="{{ e($course->course_name) }}">{{ e($course->course_name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4" id="modalNameFieldWrap">
                                    <label class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" name="client_name" id="modalClientNameInput" class="form-control" value="{{ old('client_name') }}" placeholder="Client / section / role name" required>
                                    <select id="modalFacultySelect" class="form-select" style="display:none;">
                                        <option value="">Select Faculty</option>
                                        @foreach($faculties ?? [] as $f)
                                            <option value="{{ e($f->full_name) }}">{{ e($f->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="modalAcademyStaffSelect" class="form-select" style="display:none;">
                                        <option value="">Select Academy Staff</option>
                                        @foreach($employees ?? [] as $e)
                                            <option value="{{ e($e->full_name) }}">{{ e($e->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="modalMessStaffSelect" class="form-select" style="display:none;">
                                        <option value="">Select Mess Staff</option>
                                        @foreach($messStaff ?? [] as $e)
                                            <option value="{{ e($e->full_name) }}">{{ e($e->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="modalOtStudentSelect" class="form-select" style="display:none;">
                                        <option value="">Select Student</option>
                                    </select>
                                    <select id="modalCourseNameSelect" class="form-select" style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                            <option value="{{ $course->pk }}">{{ e($course->course_name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Issue Date <span class="text-danger">*</span></label>
                                    <input type="date" name="issue_date" class="form-control" value="{{ old('issue_date', date('Y-m-d')) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Transfer From Store <span class="text-danger">*</span></label>
                                    <select name="store_id" class="form-select" required>
                                        <option value="">Select Store</option>
                                        @foreach($stores as $store)
                                            <option value="{{ $store['id'] }}" {{ old('store_id') == $store['id'] ? 'selected' : '' }}>{{ $store['store_name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Remarks</label>
                                    <input type="text" name="remarks" class="form-control" value="{{ old('remarks') }}" placeholder="Remarks (optional)">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Reference Number</label>
                                    <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number') }}" placeholder="Reference number (optional)" maxlength="100">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Order By</label>
                                    <input type="text" name="order_by" class="form-control" value="{{ old('order_by') }}" placeholder="Order by (optional)" maxlength="100">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Bill upload removed as per requirement --}}

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
                                <table class="table align-middle mb-0" id="svItemsTable">
                                    <thead>
                                        <tr>
                                            <th style="min-width: 280px;">Item Name <span class="text-white">*</span></th>
                                            <th style="min-width: 80px;">Unit</th>
                                            <th style="min-width: 100px;">Available Qty</th>
                                            <th style="min-width: 100px;">Issue Qty <span class="text-white">*</span></th>
                                            <th style="min-width: 100px;">Left Qty</th>
                                            <th style="min-width: 100px;">Rate <span class="text-white">*</span></th>
                                            <th style="min-width: 100px;">Total Amount</th>
                                            <th style="min-width: 50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="modalItemsBody">
                                        <tr class="sv-item-row">
                                            <td>
                                                <select name="items[0][item_subcategory_id]" class="form-select form-select-sm sv-item-select" required>
                                                    <option value="">Select Item</option>
                                                    @foreach($itemSubcategories as $s)
                                                        <option value="{{ $s['id'] }}" data-unit="{{ e($s['unit_measurement'] ?? '') }}" data-rate="{{ e($s['standard_cost'] ?? 0) }}">{{ e($s['item_name'] ?? '—') }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="text" name="items[0][unit]" class="form-control  sv-unit" readonly></td>
                                            <td><input type="text" name="items[0][available_quantity]" class="form-control  sv-avail bg-light" readonly></td>
                                            <td>
                                                <input type="text" name="items[0][quantity]" class="form-control  sv-qty" required>
                                                <div class="invalid-feedback">Issue Qty cannot exceed Available Qty.</div>
                                            </td>
                                            <td><input type="text" class="form-control  sv-left bg-light" readonly></td>
                                            <td><input type="text" name="items[0][rate]" class="form-control  sv-rate" required></td>
                                            <td><input type="text" class="form-control  sv-total" readonly></td>
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
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <form id="editSellingVoucherForm" method="POST" action="" enctype="multipart/form-data">
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
                                        <option value="2">UPI</option>
                                    </select>
                                </div>
                                <div class="col-md-4" id="editModalClientNameWrap">
                                    <label class="form-label">Client Name <span class="text-danger">*</span></label>
                                    <select name="client_type_pk" class="form-select" id="editClientNameSelect">
                                        <option value="">Select Client Name</option>
                                        @foreach($clientNamesByType as $type => $list)
                                            @foreach($list as $c)
                                                <option value="{{ $c->id }}" data-type="{{ $c->client_type }}" data-client-name="{{ strtolower($c->client_name ?? '') }}">{{ $c->client_name }}</option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                    <select id="editModalOtCourseSelect" class="form-select" style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                            <option value="{{ $course->pk }}" data-course-name="{{ e($course->course_name) }}">{{ e($course->course_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="editModalCourseSelect" class="form-select" style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                            <option value="{{ $course->pk }}" data-course-name="{{ e($course->course_name) }}">{{ e($course->course_name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4" id="editModalNameFieldWrap">
                                    <label class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" name="client_name" class="form-control edit-client-name" id="editModalClientNameInput" placeholder="Client / section / role name" required>
                                    <select id="editModalFacultySelect" class="form-select" style="display:none;">
                                        <option value="">Select Faculty</option>
                                        @foreach($faculties ?? [] as $f)
                                            <option value="{{ e($f->full_name) }}">{{ e($f->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="editModalAcademyStaffSelect" class="form-select" style="display:none;">
                                        <option value="">Select Academy Staff</option>
                                        @foreach($employees ?? [] as $e)
                                            <option value="{{ e($e->full_name) }}">{{ e($e->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="editModalMessStaffSelect" class="form-select" style="display:none;">
                                        <option value="">Select Mess Staff</option>
                                        @foreach($messStaff ?? [] as $e)
                                            <option value="{{ e($e->full_name) }}">{{ e($e->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="editModalCourseNameSelect" class="form-select" style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                            <option value="{{ $course->pk }}">{{ e($course->course_name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Issue Date <span class="text-danger">*</span></label>
                                    <input type="date" name="issue_date" class="form-control edit-issue-date" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Transfer From Store <span class="text-danger">*</span></label>
                                    <select name="store_id" class="form-select edit-store" required>
                                        <option value="">Select Store</option>
                                        @foreach($stores as $store)
                                            <option value="{{ $store['id'] }}">{{ $store['store_name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Remarks</label>
                                    <input type="text" name="remarks" class="form-control edit-remarks" placeholder="Remarks (optional)">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Reference Number</label>
                                    <input type="text" name="reference_number" class="form-control edit-reference-number" placeholder="Reference number (optional)" maxlength="100">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Order By</label>
                                    <input type="text" name="order_by" class="form-control edit-order-by" placeholder="Order by (optional)" maxlength="100">
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Bill upload removed as per requirement --}}
                    <div class="card mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                            <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="editModalAddItemRow">+ Add Item</button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="min-width: 280px;">Item Name <span class="text-white">*</span></th>
                                            <th style="min-width: 80px;">Unit</th>
                                            <th style="min-width: 100px;">Available Qty</th>
                                            <th style="min-width: 100px;">Issue Qty <span class="text-white">*</span></th>
                                            <th style="min-width: 100px;">Left Qty</th>
                                            <th style="min-width: 100px;">Rate <span class="text-white">*</span></th>
                                            <th style="min-width: 100px;">Total Amount</th>
                                            <th style="min-width: 50px;"></th>
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
#viewSellingVoucherModal .modal-header * { color: #212529 !important; }
#viewSellingVoucherModal .modal-title { color: #212529 !important; }
#viewSellingVoucherModal .modal-body { overflow-y: auto; max-height: calc(100vh - 10rem); background: #fff; color: #212529 !important; }
#viewSellingVoucherModal .modal-body *, #viewSellingVoucherModal .modal-body p, #viewSellingVoucherModal .modal-body span { color: inherit; }
#viewSellingVoucherModal .card { background: #fff; color: #212529; }
#viewSellingVoucherModal .card-header { background: #fff !important; color: #212529 !important; border-color: #dee2e6; }
#viewSellingVoucherModal .card-header h6 { color: #0d6efd !important; }
#viewSellingVoucherModal .card-body { background: #fff !important; color: #212529 !important; }
#viewSellingVoucherModal .card-body table th { color: #495057 !important; font-weight: 600; }
#viewSellingVoucherModal .card-body table td { color: #212529 !important; }
#viewSellingVoucherModal .card-body .table-borderless th { background: transparent !important; }
#viewSellingVoucherModal .card-body .table-borderless td { background: transparent !important; }
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
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
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
                                    <tr><th style="color: #495057;">Reference Number:</th><td id="viewReferenceNumber" style="color: #212529;">—</td></tr>
                                    <tr><th style="color: #495057;">Order By:</th><td id="viewOrderBy" style="color: #212529;">—</td></tr>
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
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Unit</th>
                                        <th>Issue Qty</th>
                                        <th>Return Qty</th>
                                        <th>Rate</th>
                                        <th>Total</th>
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
                <button type="button" class="btn btn-outline-primary btn-print-view-modal" data-print-target="#viewSellingVoucherModal" title="Print">
                    <i class="ti ti-printer"></i> Print
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Return Item Modal (Transfer To) --}}
<div class="modal fade" id="returnItemModal" tabindex="-1" aria-labelledby="returnItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
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
document.addEventListener('DOMContentLoaded', function() {
    console.log('Selling Voucher script loaded');
    console.log('Bootstrap available:', typeof bootstrap !== 'undefined');

    // Cache original Client Name options so we can rebuild the select per Client Type.
    // (TomSelect doesn't reliably respect option.hidden after init.)
    var clientNameOptionsAdd = [];
    var clientNameOptionsEdit = [];
    function cacheClientNameOptions() {
        clientNameOptionsAdd = [];
        clientNameOptionsEdit = [];
        var addSel = document.getElementById('modalClientNameSelect');
        if (addSel) {
            addSel.querySelectorAll('option[value]').forEach(function(opt) {
                clientNameOptionsAdd.push({
                    value: opt.value,
                    text: (opt.textContent || '').trim(),
                    type: ((opt.dataset.type || '').toLowerCase().trim()),
                    clientName: ((opt.dataset.clientName || '').toLowerCase().trim())
                });
            });
        }
        var editSel = document.getElementById('editClientNameSelect');
        if (editSel) {
            editSel.querySelectorAll('option[value]').forEach(function(opt) {
                clientNameOptionsEdit.push({
                    value: opt.value,
                    text: (opt.textContent || '').trim(),
                    type: ((opt.dataset.type || '').toLowerCase().trim()),
                    clientName: ((opt.dataset.clientName || '').toLowerCase().trim())
                });
            });
        }
    }
    cacheClientNameOptions();

    function rebuildClientNameSelect(selectEl, optionsList, slug) {
        if (!selectEl || !Array.isArray(optionsList)) return;
        var slugLower = (slug || '').toLowerCase().trim();
        var filtered = optionsList.filter(function(o) { return (o.type || '').toLowerCase().trim() === slugLower; });

        // Preserve a valid selection if possible; otherwise clear.
        var preserved = '';
        if (selectEl.tomselect) preserved = selectEl.tomselect.getValue() || '';
        else preserved = selectEl.value || '';

        if (selectEl.tomselect) { try { selectEl.tomselect.destroy(); } catch (e) {} }
        selectEl.innerHTML = '<option value="">Select Client Name</option>';
        filtered.forEach(function(o) {
            var opt = document.createElement('option');
            opt.value = o.value;
            opt.textContent = o.text;
            opt.setAttribute('data-type', (o.type || '').toLowerCase().trim());
            opt.setAttribute('data-client-name', (o.clientName || '').toLowerCase().trim());
            selectEl.appendChild(opt);
        });

        if (typeof TomSelect !== 'undefined') {
            new TomSelect(selectEl, {
                allowEmptyOption: true,
                dropdownParent: 'body',
                placeholder: 'Select Client Name',
                searchField: ['text'],
                controlInput: '<input>',
                highlight: false,
                onInitialize: function () {
                    this.activeOption = null;
                },
                onDropdownOpen: function (dropdown) {
                    var self = this;
                    var input = this.control_input || (dropdown && dropdown.querySelector('input'));
                    function clearInputAndCursor() {
                        if (typeof self.setTextboxValue === 'function') self.setTextboxValue('');
                        if (typeof self.onSearchChange === 'function') self.onSearchChange('');
                        if (typeof self.refreshOptions === 'function') self.refreshOptions(false);
                        if (input) {
                            input.value = '';
                            input.focus();
                            try { input.setSelectionRange(0, 0); } catch (e) {}
                            input.scrollLeft = 0;
                        }
                    }
                    clearInputAndCursor();
                    setTimeout(clearInputAndCursor, 0);
                    setTimeout(clearInputAndCursor, 50);
                    setTimeout(clearInputAndCursor, 100);
                    // dropdown open होते ही selection bhi clear karni hai (blank state)
                    self.clear(true);
                    if (dropdown) {
                        setTimeout(function () {
                            var opts = dropdown.querySelectorAll('.option.active, .option.selected, .option[aria-selected="true"]');
                            opts.forEach(function (opt) {
                                opt.classList.remove('active');
                                opt.classList.remove('selected');
                                opt.setAttribute('aria-selected', 'false');
                            });
                        }, 0);
                    }
                }
            });
        }

        // Restore preserved selection if it still exists.
        if (preserved) {
            var stillExists = Array.from(selectEl.options).some(function(o) { return String(o.value) === String(preserved); });
            if (stillExists) {
                if (selectEl.tomselect) selectEl.tomselect.setValue(preserved, true);
                else selectEl.value = preserved;
            }
        }
    }

    // When user clicks any Cancel/Close button in a modal (secondary button),
    // close the modal and refresh the page to reset all filters/state (only for Add/Edit Selling Voucher modals).
    document.querySelectorAll('#addSellingVoucherModal button.btn-secondary[data-bs-dismiss="modal"], #editSellingVoucherModal button.btn-secondary[data-bs-dismiss="modal"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            window.location.reload();
        });
    });

    // Initialize Tom Select for filter dropdowns
    if (typeof TomSelect !== 'undefined') {
        var filterStatus = document.querySelector('form[method="GET"] select[name="status"]');
        var filterStore = document.querySelector('form[method="GET"] select[name="store"]');

        if (filterStatus) {
            try {
                if (filterStatus.tomselect) {
                    filterStatus.tomselect.destroy();
                }
                new TomSelect(filterStatus, {
                    allowEmptyOption: true,
                    dropdownParent: 'body',
                    placeholder: 'All Status',
                    searchField: ['text'],
                    controlInput: '<input>',
                    highlight: false,
                    onInitialize: function () {
                        this.activeOption = null;
                    },
                    onDropdownOpen: function (dropdown) {
                        var self = this;
                        function clearInputAndCursor() {
                            var input = self.control_input || (dropdown && dropdown.querySelector('input'));
                            if (typeof self.setTextboxValue === 'function') self.setTextboxValue('');
                            if (typeof self.onSearchChange === 'function') self.onSearchChange('');
                            if (typeof self.refreshOptions === 'function') self.refreshOptions(false);
                            if (input) {
                                input.value = '';
                                input.focus();
                                try { input.setSelectionRange(0, 0); } catch (e) {}
                                input.scrollLeft = 0;
                            }
                        }
                        // selection + search dono ko blank karo har open par
                        self.clear(true);
                        clearInputAndCursor();
                        setTimeout(function () {
                            self.clear(true);
                            clearInputAndCursor();
                        }, 0);
                        setTimeout(function () {
                            self.clear(true);
                            clearInputAndCursor();
                        }, 50);
                        setTimeout(function () {
                            self.clear(true);
                            clearInputAndCursor();
                        }, 100);
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
            } catch (e) {
                console.error('Tom Select initialization failed for status filter:', e);
            }
        }

        if (filterStore) {
            try {
                if (filterStore.tomselect) {
                    filterStore.tomselect.destroy();
                }
                new TomSelect(filterStore, {
                    allowEmptyOption: true,
                    dropdownParent: 'body',
                    placeholder: 'All Stores',
                    searchField: ['text'],
                    controlInput: '<input>',
                    highlight: false,
                    onInitialize: function () {
                        this.activeOption = null;
                    },
                    onDropdownOpen: function (dropdown) {
                        var self = this;
                        function clearInputAndCursor() {
                            var input = self.control_input || (dropdown && dropdown.querySelector('input'));
                            if (typeof self.setTextboxValue === 'function') self.setTextboxValue('');
                            if (typeof self.onSearchChange === 'function') self.onSearchChange('');
                            if (typeof self.refreshOptions === 'function') self.refreshOptions(false);
                            if (input) {
                                input.value = '';
                                input.focus();
                                try { input.setSelectionRange(0, 0); } catch (e) {}
                                input.scrollLeft = 0;
                            }
                        }
                        // selection + search dono ko blank karo har open par
                        self.clear(true);
                        clearInputAndCursor();
                        setTimeout(function () {
                            self.clear(true);
                            clearInputAndCursor();
                        }, 0);
                        setTimeout(function () {
                            self.clear(true);
                            clearInputAndCursor();
                        }, 50);
                        setTimeout(function () {
                            self.clear(true);
                            clearInputAndCursor();
                        }, 100);
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
            } catch (e) {
                console.error('Tom Select initialization failed for store filter:', e);
            }
        }
    } else {
        console.warn('TomSelect library not loaded on Selling Voucher page');
    }

    // Add / Edit Selling Voucher modals: Tom Select instances (payment, client, store)
    var addModalTomSelectInstances = { payment: null, client: null, store: null };
    var editModalTomSelectInstances = { payment: null, client: null, store: null };

    function destroyAddModalTomSelects() {
        // Destroy tracked instances (payment, client, store, item selects only)
        if (addModalTomSelectInstances.payment) {
            try { addModalTomSelectInstances.payment.destroy(); } catch (e) {}
            addModalTomSelectInstances.payment = null;
        }
        if (addModalTomSelectInstances.client) {
            try { addModalTomSelectInstances.client.destroy(); } catch (e) {}
            addModalTomSelectInstances.client = null;
        }
        if (addModalTomSelectInstances.store) {
            try { addModalTomSelectInstances.store.destroy(); } catch (e) {}
            addModalTomSelectInstances.store = null;
        }
        document.querySelectorAll('#addSellingVoucherModal select').forEach(function(el) {
            if (el.tomselect) {
                try { el.tomselect.destroy(); } catch (e) {}
            }
        });
    }

    function destroyEditModalTomSelects() {
        // Destroy tracked instances for Edit modal
        if (editModalTomSelectInstances.payment) {
            try { editModalTomSelectInstances.payment.destroy(); } catch (e) {}
            editModalTomSelectInstances.payment = null;
        }
        if (editModalTomSelectInstances.client) {
            try { editModalTomSelectInstances.client.destroy(); } catch (e) {}
            editModalTomSelectInstances.client = null;
        }
        if (editModalTomSelectInstances.store) {
            try { editModalTomSelectInstances.store.destroy(); } catch (e) {}
            editModalTomSelectInstances.store = null;
        }
        document.querySelectorAll('#editSellingVoucherModal select').forEach(function(el) {
            if (el.tomselect) {
                try { el.tomselect.destroy(); } catch (e) {}
            }
        });
    }

    // Show/hide select (or its Tom Select wrapper) so only one Name dropdown is visible at a time
    function setSelectVisible(select, visible) {
        if (!select) return;
        var wrapper = (select.tomselect && select.tomselect.wrapper) || (select.parentElement && select.parentElement.classList && select.parentElement.classList.contains('ts-wrapper') ? select.parentElement : null);
        if (wrapper) {
            wrapper.style.display = visible ? '' : 'none';
        } else {
            select.style.display = visible ? 'block' : 'none';
        }
    }

    function initAddModalTomSelects() {
        if (typeof TomSelect === 'undefined') return;
        var modal = document.getElementById('addSellingVoucherModal');
        if (!modal) return;

        function createBlankSearchConfig(extra) {
            return Object.assign({
                allowEmptyOption: true,
                dropdownParent: 'body',
                searchField: ['text'],
                controlInput: '<input>',
                highlight: false,
                onInitialize: function () {
                    this.activeOption = null;
                },
                onDropdownOpen: function (dropdown) {
                    var self = this;
                    var input = this.control_input || (dropdown && dropdown.querySelector('input'));
                    function clearInputAndCursor() {
                        if (typeof self.setTextboxValue === 'function') self.setTextboxValue('');
                        if (typeof self.onSearchChange === 'function') self.onSearchChange('');
                        if (typeof self.refreshOptions === 'function') self.refreshOptions(false);
                        if (input) {
                            input.value = '';
                            input.focus();
                            try { input.setSelectionRange(0, 0); } catch (e) {}
                            input.scrollLeft = 0;
                        }
                    }
                    clearInputAndCursor();
                    setTimeout(clearInputAndCursor, 0);
                    setTimeout(clearInputAndCursor, 50);
                    setTimeout(clearInputAndCursor, 100);
                    // dropdown open होते ही selection bhi clear karni hai (blank state)
                    if (self.settings && self.settings.clearOnOpen) {
                        self.clear(true);
                    }
                    if (dropdown) {
                        setTimeout(function () {
                            var opts = dropdown.querySelectorAll('.option.active, .option.selected, .option[aria-selected="true"]');
                            opts.forEach(function (opt) {
                                opt.classList.remove('active');
                                opt.classList.remove('selected');
                                opt.setAttribute('aria-selected', 'false');
                            });
                        }, 0);
                    }
                }
            }, extra || {});
        }

        var paymentSel = modal.querySelector('select[name="payment_type"]');
        if (paymentSel && !paymentSel.tomselect) {
            addModalTomSelectInstances.payment = new TomSelect(paymentSel, createBlankSearchConfig({
                placeholder: 'Payment Type',
                clearOnOpen: true
            }));
        }
        var clientSel = document.getElementById('modalClientNameSelect');
        var addRadio = document.querySelector('#addSellingVoucherModal .client-type-radio:checked');
        var addSlug = addRadio ? (addRadio.value || '').toLowerCase().trim() : 'employee';
        if (clientSel && addSlug !== 'ot' && addSlug !== 'course' && clientNameOptionsAdd.length) {
            rebuildClientNameSelect(clientSel, clientNameOptionsAdd, addSlug);
        } else if (clientSel && !clientSel.tomselect) {
            addModalTomSelectInstances.client = new TomSelect(clientSel, createBlankSearchConfig({
                placeholder: 'Select Client Name',
                clearOnOpen: true
            }));
        }
        var storeSel = modal.querySelector('select[name="store_id"]');
        if (storeSel && !storeSel.tomselect) {
            addModalTomSelectInstances.store = new TomSelect(storeSel, createBlankSearchConfig({
                placeholder: 'Select Store',
                clearOnOpen: true
            }));
        }
        // Name-related dropdowns: Tom Select with search; visibility controlled by setSelectVisible
        var nameSelectIds = ['modalFacultySelect', 'modalAcademyStaffSelect', 'modalMessStaffSelect', 'modalOtStudentSelect', 'modalOtCourseSelect', 'modalCourseSelect', 'modalCourseNameSelect'];
        nameSelectIds.forEach(function(id) {
            var sel = document.getElementById(id);
            if (sel && !sel.tomselect) {
                new TomSelect(sel, createBlankSearchConfig({
                    placeholder: sel.id.indexOf('Faculty') !== -1 ? 'Select Faculty'
                        : sel.id.indexOf('Academy') !== -1 ? 'Select Academy Staff'
                        : sel.id.indexOf('Mess') !== -1 ? 'Select Mess Staff'
                        : sel.id.indexOf('OtStudent') !== -1 ? 'Select Student'
                        : 'Select Course',
                    clearOnOpen: true
                }));
            }
        });
        modal.querySelectorAll('#modalItemsBody .sv-item-select').forEach(function(select) {
            if (select.tomselect) return;
            var hadValue = !!select.value;
            var ts = new TomSelect(select, {
                allowEmptyOption: true,
                dropdownParent: 'body',
                placeholder: 'Select Item',
                maxOptions: null,
                highlight: false,
                searchField: ['text'],
                controlInput: '<input>',
                onInitialize: function () {
                    // prevent first option from being auto-highlighted
                    this.activeOption = null;
                },
                onDropdownOpen: function (dropdown) {
                    var self = this;
                    var input = this.control_input || dropdown.querySelector('input');
                    function clearInputAndCursor() {
                        if (typeof self.setTextboxValue === 'function') self.setTextboxValue('');
                        if (typeof self.onSearchChange === 'function') self.onSearchChange('');
                        if (typeof self.refreshOptions === 'function') self.refreshOptions(false);
                        if (input) {
                            input.value = '';
                            input.focus();
                            try { input.setSelectionRange(0, 0); } catch (e) {}
                            input.scrollLeft = 0;
                        }
                    }
                    clearInputAndCursor();
                    setTimeout(clearInputAndCursor, 0);
                    setTimeout(clearInputAndCursor, 50);
                    setTimeout(clearInputAndCursor, 100);
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
            // agar pehle koi value nahi thi to ensure fresh blank state
            if (!hadValue) {
                ts.clear(true);
            }
        });
        // Client Name & Name columns: hide until a Client Type is selected
        var clientNameWrap = document.getElementById('modalClientNameWrap');
        var nameFieldWrap = document.getElementById('modalNameFieldWrap');
        var clientTypeChecked = document.querySelector('#addSellingVoucherModal .client-type-radio:checked');
        if (clientNameWrap && nameFieldWrap) {
            if (clientTypeChecked) {
                clientNameWrap.style.display = '';
                nameFieldWrap.style.display = '';
            } else {
                clientNameWrap.style.display = 'none';
                nameFieldWrap.style.display = 'none';
            }
        }
        // After all TomSelect instances (and their wrappers) are created, ensure that
        // only the correct Name dropdown(s) are visible for the currently selected
        // client type (especially after a validation error + old() values).
        if (typeof updateModalNameField === 'function') {
            updateModalNameField();
        }
    }

    function initEditModalTomSelects() {
        if (typeof TomSelect === 'undefined') return;
        var modal = document.getElementById('editSellingVoucherModal');
        if (!modal) return;

        function createBlankSearchConfig(extra) {
            return Object.assign({
                allowEmptyOption: true,
                dropdownParent: 'body',
                searchField: ['text'],
                controlInput: '<input>',
                highlight: false,
                onInitialize: function () {
                    this.activeOption = null;
                },
                onDropdownOpen: function (dropdown) {
                    var self = this;
                    var input = this.control_input || (dropdown && dropdown.querySelector('input'));
                    function clearInputAndCursor() {
                        if (typeof self.setTextboxValue === 'function') self.setTextboxValue('');
                        if (typeof self.onSearchChange === 'function') self.onSearchChange('');
                        if (typeof self.refreshOptions === 'function') self.refreshOptions(false);
                        if (input) {
                            input.value = '';
                            input.focus();
                            try { input.setSelectionRange(0, 0); } catch (e) {}
                            input.scrollLeft = 0;
                        }
                    }
                    clearInputAndCursor();
                    setTimeout(clearInputAndCursor, 0);
                    setTimeout(clearInputAndCursor, 50);
                    setTimeout(clearInputAndCursor, 100);
                    // dropdown open होते ही selection bhi clear karni hai (blank state)
                    if (self.settings && self.settings.clearOnOpen) {
                        self.clear(true);
                    }
                    if (dropdown) {
                        setTimeout(function () {
                            var opts = dropdown.querySelectorAll('.option.active, .option.selected, .option[aria-selected="true"]');
                            opts.forEach(function (opt) {
                                opt.classList.remove('active');
                                opt.classList.remove('selected');
                                opt.setAttribute('aria-selected', 'false');
                            });
                        }, 0);
                    }
                }
            }, extra || {});
        }

        // Payment Type
        var paymentSel = modal.querySelector('select.edit-payment-type');
        if (paymentSel && !paymentSel.tomselect) {
            editModalTomSelectInstances.payment = new TomSelect(paymentSel, createBlankSearchConfig({
                placeholder: 'Payment Type',
                clearOnOpen: true
            }));
        }

        // Client Name (filter by selected Client Type)
        var clientSel = document.getElementById('editClientNameSelect');
        var editRadio = document.querySelector('#editSellingVoucherModal .edit-client-type-radio:checked');
        var editSlug = editRadio ? (editRadio.value || '').toLowerCase().trim() : 'employee';
        if (clientSel && editSlug !== 'ot' && editSlug !== 'course' && clientNameOptionsEdit.length) {
            rebuildClientNameSelect(clientSel, clientNameOptionsEdit, editSlug);
        } else if (clientSel && !clientSel.tomselect) {
            editModalTomSelectInstances.client = new TomSelect(clientSel, createBlankSearchConfig({
                placeholder: 'Select Client Name',
                clearOnOpen: true
            }));
        }

        // Store
        var storeSel = modal.querySelector('select.edit-store');
        if (storeSel && !storeSel.tomselect) {
            editModalTomSelectInstances.store = new TomSelect(storeSel, createBlankSearchConfig({
                placeholder: 'Select Store',
                clearOnOpen: true
            }));
        }

        // Name-related dropdowns (Faculty, Academy Staff, Mess Staff, OT Course, Course, Course Name)
        var editNameSelectIds = ['editModalFacultySelect', 'editModalAcademyStaffSelect', 'editModalMessStaffSelect', 'editModalOtCourseSelect', 'editModalCourseSelect', 'editModalCourseNameSelect'];
        editNameSelectIds.forEach(function(id) {
            var sel = document.getElementById(id);
            if (sel && !sel.tomselect) {
                var placeholder = id.indexOf('Faculty') !== -1 ? 'Select Faculty'
                    : id.indexOf('Academy') !== -1 ? 'Select Academy Staff'
                    : id.indexOf('Mess') !== -1 ? 'Select Mess Staff'
                    : 'Select Course';
                new TomSelect(sel, createBlankSearchConfig({ placeholder: placeholder, clearOnOpen: true }));
            }
        });
    }

    // After Tom Select init in Edit modal: show only the active dropdown in Client Name column (hide OT Course / Course when Client Name is active, and vice versa)
    function applyEditModalClientNameColumnVisibility() {
        var radio = document.querySelector('#editSellingVoucherModal .edit-client-type-radio:checked');
        var clientSelect = document.getElementById('editClientNameSelect');
        var otCourseSelect = document.getElementById('editModalOtCourseSelect');
        var editCourseSelect = document.getElementById('editModalCourseSelect');
        if (!radio || !clientSelect) return;
        var isOt = (radio.value || '').toLowerCase() === 'ot';
        var isCourse = (radio.value || '').toLowerCase() === 'course';
        if (isOt) {
            setSelectVisible(clientSelect, false);
            if (otCourseSelect) setSelectVisible(otCourseSelect, true);
            if (editCourseSelect) setSelectVisible(editCourseSelect, false);
        } else if (isCourse) {
            setSelectVisible(clientSelect, false);
            if (otCourseSelect) setSelectVisible(otCourseSelect, false);
            if (editCourseSelect) setSelectVisible(editCourseSelect, true);
        } else {
            setSelectVisible(clientSelect, true);
            if (otCourseSelect) setSelectVisible(otCourseSelect, false);
            if (editCourseSelect) setSelectVisible(editCourseSelect, false);
        }
    }

    // Filter: End Date must not be before Start Date
    var filterStart = document.getElementById('filter_start_date');
    var filterEnd = document.getElementById('filter_end_date');
    if (filterStart && filterEnd) {
        filterStart.addEventListener('change', function() {
            filterEnd.min = this.value || '';
            if (filterEnd.value && this.value && filterEnd.value < this.value) {
                filterEnd.value = this.value;
            }
        });
    }

    // Prevent double submit on Add Selling Voucher form (stops double entry)
    var sellingVoucherModalForm = document.getElementById('sellingVoucherModalForm');
    if (sellingVoucherModalForm) {
        sellingVoucherModalForm.addEventListener('submit', function() {
            var btn = this.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.textContent = 'Saving...';
            }
        });
    }

    // Prevent double submit on Edit Selling Voucher form
    var editSellingVoucherForm = document.getElementById('editSellingVoucherForm');
    if (editSellingVoucherForm) {
        editSellingVoucherForm.addEventListener('submit', function() {
            var btn = this.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.textContent = 'Updating...';
            }
        });
    }
    
    // Debug: Check if buttons exist
    const viewButtons = document.querySelectorAll('.btn-view-sv');
    const editButtons = document.querySelectorAll('.btn-edit-sv');
    const returnButtons = document.querySelectorAll('.btn-return-sv');
    console.log('Found buttons:', {
        view: viewButtons.length,
        edit: editButtons.length,
        return: returnButtons.length
    });
    
    let itemSubcategories = @json($itemSubcategories);
    let filteredItems = itemSubcategories;
    const editSvBaseUrl = "{{ url('admin/mess/material-management') }}";
    const viewSvBaseUrl = "{{ url('admin/mess/material-management') }}";
    const returnSvBaseUrl = "{{ url('admin/mess/material-management') }}";
    let rowIndex = 1;
    let editRowIndex = 0;
    let currentStoreId = null;
    let editCurrentStoreId = null;

    function enforceQtyWithinAvailable(row) {
        if (!row) return;
        const availEl = row.querySelector('.sv-avail');
        const qtyEl = row.querySelector('.sv-qty');
        if (!availEl || !qtyEl) return;

        let avail = parseFloat(availEl.value) || 0;
        const qtyRaw = qtyEl.value;
        const qty = parseFloat(qtyRaw);

        // In edit modal: effective available = current stock + this row's original issue qty
        // (so saving without changes does not fail when current stock already reflects the voucher)
        const isEditRow = row.closest('#editModalItemsBody') !== null;
        const originalQty = isEditRow ? (parseFloat(row.getAttribute('data-original-qty')) || 0) : 0;
        const effectiveAvail = isEditRow ? (avail + originalQty) : avail;

        // Keep browser constraint in sync
        qtyEl.max = String(effectiveAvail);

        // If empty, don't force an error yet
        if (qtyRaw === '' || Number.isNaN(qty)) {
            qtyEl.setCustomValidity('');
            qtyEl.classList.remove('is-invalid');
            return;
        }

        if (qty > effectiveAvail) {
            qtyEl.setCustomValidity('Issue Qty cannot exceed Available Qty.');
            qtyEl.classList.add('is-invalid');
        } else {
            qtyEl.setCustomValidity('');
            qtyEl.classList.remove('is-invalid');
        }
    }

    function getBaseAvailableForItem(itemId) {
        if (!itemId) return 0;
        const item = filteredItems.find(function(i) { return String(i.id) === String(itemId); });
        return item ? (parseFloat(item.available_quantity) || 0) : 0;
    }

    function refreshAllAvailable() {
        const rows = document.querySelectorAll('#modalItemsBody .sv-item-row');
        const usedByItem = {};

        rows.forEach(function(row) {
            const select = row.querySelector('.sv-item-select');
            const itemId = select ? select.value : '';
            const availInp = row.querySelector('.sv-avail');
            const leftInp = row.querySelector('.sv-left');
            if (!itemId || !availInp) return;

            const base = getBaseAvailableForItem(itemId);
            const alreadyUsed = usedByItem[itemId] || 0;
            const availableForRow = Math.max(0, base - alreadyUsed);

            availInp.value = availableForRow.toFixed(2);

            const qty = parseFloat(row.querySelector('.sv-qty').value) || 0;
            if (leftInp) {
                leftInp.value = Math.max(0, availableForRow - qty).toFixed(2);
            }

            usedByItem[itemId] = alreadyUsed + qty;
            enforceQtyWithinAvailable(row);
        });
    }

    function fetchStoreItems(storeId, callback) {
        if (!storeId) {
            filteredItems = itemSubcategories;
            if (callback) callback();
            return;
        }
        
        fetch(editSvBaseUrl + '/store/' + storeId + '/items', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            filteredItems = data;
            if (callback) callback();
        })
        .catch(err => {
            console.error(err);
            alert('Failed to load store items.');
            filteredItems = [];
        });
    }

    function updateAddItemDropdowns() {
        const rows = document.querySelectorAll('#modalItemsBody .sv-item-row');
        console.log('Updating dropdowns, found rows:', rows.length); // Debug log
        rows.forEach(row => {
            const select = row.querySelector('.sv-item-select');
            if (!select) return;
            if (select.tomselect) {
                select.tomselect.destroy();
            }
            const currentValue = select.value;
            select.innerHTML = '<option value="">Select Item</option>';
            filteredItems.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.item_name || '—';
                option.setAttribute('data-unit', item.unit_measurement || '');
                option.setAttribute('data-rate', item.standard_cost || 0);
                option.setAttribute('data-available', item.available_quantity || 0);
                if (item.price_tiers && item.price_tiers.length > 0) {
                    option.setAttribute('data-price-tiers', JSON.stringify(item.price_tiers));
                }
                if (item.id == currentValue) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
            if (typeof TomSelect !== 'undefined') {
                new TomSelect(select, {
                    allowEmptyOption: true,
                    dropdownParent: 'body',
                    placeholder: 'Select Item',
                    maxOptions: null,
                    highlight: false,
                    controlInput: '<input>',
                    onInitialize: function () { this.activeOption = null; },
                    onDropdownOpen: function (dropdown) {
                        var self = this;
                        var input = this.control_input || (dropdown && dropdown.querySelector('input'));
                        function clearInputAndCursor() {
                            if (typeof self.setTextboxValue === 'function') self.setTextboxValue('');
                            if (typeof self.onSearchChange === 'function') self.onSearchChange('');
                            if (typeof self.refreshOptions === 'function') self.refreshOptions(false);
                            if (input) {
                                input.value = '';
                                input.focus();
                                try { input.setSelectionRange(0, 0); } catch (e) {}
                                input.scrollLeft = 0;
                            }
                        }
                        clearInputAndCursor();
                        setTimeout(clearInputAndCursor, 0);
                        setTimeout(clearInputAndCursor, 50);
                        setTimeout(clearInputAndCursor, 100);
                        if (dropdown) setTimeout(function () {
                            var opts = dropdown.querySelectorAll('.option.active, .option.selected, .option[aria-selected="true"]');
                            opts.forEach(function (opt) {
                                opt.classList.remove('active');
                                opt.classList.remove('selected');
                                opt.setAttribute('aria-selected', 'false');
                            });
                        }, 0);
                    }
                });
            }
            updateUnit(row);
        });
    }

    function getRowHtml(index) {
        const options = filteredItems.map(s => {
            let attrs = 'data-unit="' + (s.unit_measurement || '').replace(/"/g, '&quot;') + '" data-rate="' + (s.standard_cost || 0) + '" data-available="' + (s.available_quantity || 0) + '"';
            if (s.price_tiers && s.price_tiers.length > 0) {
                attrs += ' data-price-tiers="' + (JSON.stringify(s.price_tiers) || '').replace(/"/g, '&quot;') + '"';
            }
            return '<option value="' + s.id + '" ' + attrs + '>' + (s.item_name || '—').replace(/</g, '&lt;') + '</option>';
        }).join('');
        return '<tr class="sv-item-row">' +
            '<td><select name="items[' + index + '][item_subcategory_id]" class="form-select form-select-sm sv-item-select" required><option value="">Select Item</option>' + options + '</select></td>' +
            '<td><input type="text" name="items[' + index + '][unit]" class="form-control  sv-unit" readonly placeholder="—"></td>' +
            '<td><input type="number" name="items[' + index + '][available_quantity]" class="form-control  sv-avail bg-light" step="0.01" min="0" value="0" placeholder="0" readonly></td>' +
            '<td><input type="number" name="items[' + index + '][quantity]" class="form-control  sv-qty" step="0.01" min="0.01" placeholder="0" required><div class="invalid-feedback">Issue Qty cannot exceed Available Qty.</div></td>' +
            '<td><input type="text" class="form-control  sv-left bg-light" readonly placeholder="0"></td>' +
            '<td><input type="number" name="items[' + index + '][rate]" class="form-control  sv-rate" step="0.01" min="0" placeholder="0" required></td>' +
            '<td><input type="text" class="form-control  sv-total bg-light" readonly placeholder="0.00"></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger sv-remove-row" title="Remove">×</button></td>' +
            '</tr>';
    }

    function updateUnit(row) {
        const sel = row.querySelector('.sv-item-select');
        const opt = sel && sel.options[sel.selectedIndex];
        const unitInp = row.querySelector('.sv-unit');
        const rateInp = row.querySelector('.sv-rate');
        const availInp = row.querySelector('.sv-avail');
        if (unitInp) unitInp.value = opt && opt.dataset.unit ? opt.dataset.unit : '';
        if (rateInp && opt && opt.dataset.rate) rateInp.value = opt.dataset.rate;
        if (availInp && opt && opt.dataset.available) availInp.value = opt.dataset.available;
        if (availInp) availInp.readOnly = true;
        if (row.closest('#editModalItemsBody')) {
            refreshEditAllAvailable();
        } else {
            refreshAllAvailable();
        }
        enforceQtyWithinAvailable(row);
    }

    function calcFifoAmount(tiers, qty) {
        if (!tiers || tiers.length === 0 || qty <= 0) return null;
        let remaining = qty;
        let amount = 0;
        for (let i = 0; i < tiers.length && remaining > 0; i++) {
            const take = Math.min(remaining, parseFloat(tiers[i].quantity) || 0);
            amount += take * (parseFloat(tiers[i].unit_price) || 0);
            remaining -= take;
        }
        return remaining <= 0 ? amount : null;
    }

    function calcRow(row) {
        const avail = parseFloat(row.querySelector('.sv-avail').value) || 0;
        const qty = parseFloat(row.querySelector('.sv-qty').value) || 0;
        const rateInp = row.querySelector('.sv-rate');
        let rate = parseFloat(rateInp.value) || 0;
        const sel = row.querySelector('.sv-item-select');
        const opt = sel && sel.options[sel.selectedIndex];
        const tiersJson = opt && opt.getAttribute('data-price-tiers');
        const tiers = tiersJson ? (function(){ try { return JSON.parse(tiersJson); } catch(e) { return null; } })() : null;
        let total;
        if (tiers && tiers.length > 0 && qty > 0) {
            const fifoAmount = calcFifoAmount(tiers, qty);
            if (fifoAmount !== null) {
                total = fifoAmount;
                rate = qty > 0 ? total / qty : 0;
                rateInp.value = rate.toFixed(2);
            } else {
                total = qty * rate;
            }
        } else {
            total = qty * rate;
        }
        const left = Math.max(0, avail - qty);
        row.querySelector('.sv-left').value = left;
        row.querySelector('.sv-total').value = (total || 0).toFixed(2);
        enforceQtyWithinAvailable(row);
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

    // Store selection change in ADD modal
    const addModalStoreSelect = document.querySelector('#addSellingVoucherModal select[name="store_id"]');
    if (addModalStoreSelect) {
        addModalStoreSelect.addEventListener('change', function() {
            const storeId = this.value;
            currentStoreId = storeId;
            
            console.log('Store changed:', storeId); // Debug log
            
            if (!storeId) {
                filteredItems = itemSubcategories;
                updateAddItemDropdowns();
                return;
            }
            
            fetchStoreItems(storeId, function() {
                console.log('Filtered items count:', filteredItems.length); // Debug log
                updateAddItemDropdowns();
            });
        });
    }

    const modalAddItemBtn = document.getElementById('modalAddItemRow');
    if (modalAddItemBtn) {
        modalAddItemBtn.addEventListener('click', function() {
            const tbody = document.getElementById('modalItemsBody');
            if (tbody) {
                tbody.insertAdjacentHTML('beforeend', getRowHtml(rowIndex));
                rowIndex++;
                updateRemoveButtons();
                var newRow = tbody.querySelector('.sv-item-row:last-child');
                var newSelect = newRow ? newRow.querySelector('.sv-item-select') : null;
                if (newSelect && typeof TomSelect !== 'undefined') {
                    new TomSelect(newSelect, {
                        allowEmptyOption: true,
                        dropdownParent: 'body',
                        placeholder: 'Select Item',
                        maxOptions: null,
                        highlight: false,
                        controlInput: '<input>',
                        onInitialize: function () { this.activeOption = null; },
                        onDropdownOpen: function (dropdown) {
                            var self = this;
                            var input = this.control_input || (dropdown && dropdown.querySelector('input'));
                            function clearInputAndCursor() {
                                if (typeof self.setTextboxValue === 'function') self.setTextboxValue('');
                                if (typeof self.onSearchChange === 'function') self.onSearchChange('');
                                if (typeof self.refreshOptions === 'function') self.refreshOptions(false);
                                if (input) {
                                    input.value = '';
                                    input.focus();
                                    try { input.setSelectionRange(0, 0); } catch (e) {}
                                    input.scrollLeft = 0;
                                }
                            }
                            clearInputAndCursor();
                            setTimeout(clearInputAndCursor, 0);
                            setTimeout(clearInputAndCursor, 50);
                            setTimeout(clearInputAndCursor, 100);
                            if (dropdown) setTimeout(function () {
                                var opts = dropdown.querySelectorAll('.option.active, .option.selected, .option[aria-selected="true"]');
                                opts.forEach(function (opt) {
                                    opt.classList.remove('active');
                                    opt.classList.remove('selected');
                                    opt.setAttribute('aria-selected', 'false');
                                });
                            }, 0);
                        }
                    });
                }
            }
        });
    }

    const modalItemsBody = document.getElementById('modalItemsBody');
    const addSvModal = document.getElementById('addSellingVoucherModal');
    if (modalItemsBody) {
        modalItemsBody.addEventListener('change', function(e) {
            if (e.target.classList.contains('sv-item-select')) {
                const row = e.target.closest('.sv-item-row');
                if (row) { updateUnit(row); calcRow(row); updateGrandTotal(); }
            }
        });

        modalItemsBody.addEventListener('input', function(e) {
            if (e.target.classList.contains('sv-avail') || e.target.classList.contains('sv-qty') || e.target.classList.contains('sv-rate')) {
                const row = e.target.closest('.sv-item-row');
                if (row) {
                    refreshAllAvailable();
                    enforceQtyWithinAvailable(row);
                    calcRow(row);
                    updateGrandTotal();
                }
            }
        });

        modalItemsBody.addEventListener('click', function(e) {
            if (e.target.classList.contains('sv-remove-row')) {
                const row = e.target.closest('.sv-item-row');
                if (row && document.querySelectorAll('#modalItemsBody .sv-item-row').length > 1) {
                    row.remove();
                    refreshAllAvailable();
                    updateGrandTotal();
                    updateRemoveButtons();
                }
            }
        });
    }

    // Delegate input/change from modal so qty/rate updates always run (Left Qty + Total)
    if (addSvModal) {
        function onAddModalQtyOrRateInput(e) {
            if (!e.target.matches('.sv-avail, .sv-qty, .sv-rate')) return;
            const row = e.target.closest('.sv-item-row');
            if (!row) return;
            refreshAllAvailable();
    const addSvModal = document.getElementById('addSellingVoucherModal');
            calcRow(row);
            updateGrandTotal();
        }
        addSvModal.addEventListener('input', onAddModalQtyOrRateInput);
        addSvModal.addEventListener('change', onAddModalQtyOrRateInput);
    }

    // Enter key inside Item Details table triggers Add Item (and prevents form submit)
    const svItemsTable = document.getElementById('svItemsTable');
    if (addSvModal && svItemsTable) {
        addSvModal.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && svItemsTable.contains(document.activeElement)) {
                const addBtn = document.getElementById('modalAddItemRow');
                if (addBtn) {
                    e.preventDefault();
                    addBtn.click();
                }
            }
        });
    }

    function updateModalNameField() {
        const clientTypeRadio = document.querySelector('#addSellingVoucherModal .client-type-radio:checked');
        const clientNameSelect = document.getElementById('modalClientNameSelect');
        const nameInput = document.getElementById('modalClientNameInput');
        const facultySelect = document.getElementById('modalFacultySelect');
        const academyStaffSelect = document.getElementById('modalAcademyStaffSelect');
        const messStaffSelect = document.getElementById('modalMessStaffSelect');
        const otStudentSelect = document.getElementById('modalOtStudentSelect');
        const courseSelect = document.getElementById('modalCourseSelect');
        const courseNameSelect = document.getElementById('modalCourseNameSelect');
        if (!clientTypeRadio || !clientNameSelect || !nameInput) return;
        const isEmployee = (clientTypeRadio.value || '').toLowerCase() === 'employee';
        const isOt = (clientTypeRadio.value || '').toLowerCase() === 'ot';
        const isCourse = (clientTypeRadio.value || '').toLowerCase() === 'course';
        const opt = clientNameSelect.options[clientNameSelect.selectedIndex];
        const clientNameVal = (opt && opt.dataset.clientName) ? opt.dataset.clientName : '';
        const isFaculty = clientNameVal === 'faculty';
        const isAcademyStaff = clientNameVal === 'academy staff';
        const isMessStaff = clientNameVal === 'mess staff';
        const showFaculty = isEmployee && isFaculty;
        const showAcademyStaff = isEmployee && isAcademyStaff;
        const showMessStaff = isEmployee && isMessStaff;
        const showAny = showFaculty || showAcademyStaff || showMessStaff;

        // Pehle high-level Client Name / OT Course / Course select ko control karo
        if (isOt) {
            // OT: sirf OT Course + OT Student dikhna chahiye
            setSelectVisible(clientNameSelect, false);
            if (courseSelect) setSelectVisible(courseSelect, false);
            if (otStudentSelect) { setSelectVisible(otStudentSelect, true); }
            const otCourseSelect = document.getElementById('modalOtCourseSelect');
            if (otCourseSelect) setSelectVisible(otCourseSelect, true);
            nameInput.style.display = 'none';
            nameInput.removeAttribute('required');
        } else if (isCourse) {
            // Course: sirf Course select + text Name field
            setSelectVisible(clientNameSelect, false);
            const otCourseSelect = document.getElementById('modalOtCourseSelect');
            if (otCourseSelect) setSelectVisible(otCourseSelect, false);
            if (otStudentSelect) { setSelectVisible(otStudentSelect, false); }
            if (courseSelect) setSelectVisible(courseSelect, true);
            nameInput.style.display = 'block';
            nameInput.placeholder = 'Course name';
            nameInput.setAttribute('required', 'required');
        } else {
            // Employee / Section / Other: sirf Client Name + (Faculty/Staff/Mess) dropdown ya text field
            setSelectVisible(clientNameSelect, true);
            const otCourseSelect = document.getElementById('modalOtCourseSelect');
            if (otCourseSelect) setSelectVisible(otCourseSelect, false);
            if (otStudentSelect) { setSelectVisible(otStudentSelect, false); }
            if (courseSelect) setSelectVisible(courseSelect, false);
            nameInput.style.display = showAny ? 'none' : 'block';
        }

        // Ab niche ke detailed faculty/academy/mess/course-name dropdowns handle karo
        [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) {
            if (!sel) return;
            const show = sel === facultySelect ? showFaculty : (sel === academyStaffSelect ? showAcademyStaff : showMessStaff);
            setSelectVisible(sel, show);
            sel.removeAttribute('required');
            if (show) {
                sel.setAttribute('required', 'required');
                sel.value = nameInput.value || '';
                if (sel.value) nameInput.value = sel.value;
            } else {
                sel.value = '';
            }
        });
        if (otStudentSelect) { setSelectVisible(otStudentSelect, isOt); if (!isOt) { otStudentSelect.value = ''; otStudentSelect.removeAttribute('required'); } }
        if (courseNameSelect) { setSelectVisible(courseNameSelect, false); courseNameSelect.value = ''; courseNameSelect.removeAttribute('required'); }
        if (!showAny && !isOt && !isCourse) {
            nameInput.setAttribute('required', 'required');
        }
    }
    document.querySelectorAll('#addSellingVoucherModal .client-type-radio').forEach(function(radio) {
        radio.addEventListener('change', function() {
            // Show Client Name & Name columns as soon as a Client Type is selected
            var clientNameWrap = document.getElementById('modalClientNameWrap');
            var nameFieldWrap = document.getElementById('modalNameFieldWrap');
            if (clientNameWrap) clientNameWrap.style.display = '';
            if (nameFieldWrap) nameFieldWrap.style.display = '';
            const isOt = (this.value || '').toLowerCase() === 'ot';
            const isCourse = (this.value || '').toLowerCase() === 'course';
            const clientSelect = document.getElementById('modalClientNameSelect');
            const otCourseSelect = document.getElementById('modalOtCourseSelect');
            const otStudentSelect = document.getElementById('modalOtStudentSelect');
            const courseSelect = document.getElementById('modalCourseSelect');
            const courseNameSelect = document.getElementById('modalCourseNameSelect');
            const nameInput = document.getElementById('modalClientNameInput');
            if (isOt) {
                if (clientSelect) { setSelectVisible(clientSelect, false); clientSelect.removeAttribute('required'); clientSelect.value = ''; clientSelect.removeAttribute('name'); }
                if (otCourseSelect) { setSelectVisible(otCourseSelect, true); otCourseSelect.setAttribute('required', 'required'); otCourseSelect.setAttribute('name', 'client_type_pk'); otCourseSelect.value = ''; }
                if (otStudentSelect) { setSelectVisible(otStudentSelect, true); otStudentSelect.innerHTML = '<option value="">Select course first</option>'; otStudentSelect.setAttribute('required', 'required'); otStudentSelect.value = ''; }
                if (courseSelect) { setSelectVisible(courseSelect, false); courseSelect.removeAttribute('required'); courseSelect.removeAttribute('name'); courseSelect.value = ''; }
                if (courseNameSelect) { setSelectVisible(courseNameSelect, false); courseNameSelect.removeAttribute('required'); courseNameSelect.value = ''; }
                if (nameInput) { nameInput.style.display = 'none'; nameInput.value = ''; nameInput.removeAttribute('required'); }
            } else if (isCourse) {
                if (clientSelect) { setSelectVisible(clientSelect, false); clientSelect.removeAttribute('required'); clientSelect.value = ''; clientSelect.removeAttribute('name'); }
                if (otCourseSelect) { setSelectVisible(otCourseSelect, false); otCourseSelect.removeAttribute('required'); otCourseSelect.removeAttribute('name'); otCourseSelect.value = ''; }
                if (otStudentSelect) { setSelectVisible(otStudentSelect, false); otStudentSelect.removeAttribute('required'); otStudentSelect.innerHTML = '<option value="">Select Student</option>'; otStudentSelect.value = ''; }
                if (courseSelect) { setSelectVisible(courseSelect, true); courseSelect.setAttribute('required', 'required'); courseSelect.setAttribute('name', 'client_type_pk'); courseSelect.value = ''; }
                if (courseNameSelect) { setSelectVisible(courseNameSelect, false); courseNameSelect.removeAttribute('required'); courseNameSelect.value = ''; }
                if (nameInput) { nameInput.style.display = 'block'; nameInput.value = ''; nameInput.placeholder = 'Course name'; nameInput.setAttribute('required', 'required'); }
            } else {
                if (clientSelect) { setSelectVisible(clientSelect, true); clientSelect.setAttribute('required', 'required'); clientSelect.setAttribute('name', 'client_type_pk'); }
                if (otCourseSelect) { setSelectVisible(otCourseSelect, false); otCourseSelect.removeAttribute('required'); otCourseSelect.removeAttribute('name'); otCourseSelect.value = ''; }
                if (otStudentSelect) { setSelectVisible(otStudentSelect, false); otStudentSelect.removeAttribute('required'); otStudentSelect.innerHTML = '<option value="">Select Student</option>'; otStudentSelect.value = ''; }
                if (courseSelect) { setSelectVisible(courseSelect, false); courseSelect.removeAttribute('required'); courseSelect.value = ''; }
                if (courseNameSelect) { setSelectVisible(courseNameSelect, false); courseNameSelect.removeAttribute('required'); courseNameSelect.value = ''; }
                if (clientSelect && clientNameOptionsAdd.length) {
                    rebuildClientNameSelect(clientSelect, clientNameOptionsAdd, this.value);
                }
                if (nameInput) { nameInput.style.display = 'block'; nameInput.placeholder = 'Client / section / role name'; nameInput.setAttribute('required', 'required'); }
            }
            updateModalNameField();
        });
    });
    function reinitNameSelectTomSelect(select, placeholder) {
        if (!select || typeof TomSelect === 'undefined') return;
        if (select.tomselect) {
            try { select.tomselect.destroy(); } catch (e) {}
        }
        new TomSelect(select, { allowEmptyOption: true, dropdownParent: 'body', placeholder: placeholder || 'Select' });
    }
    const modalOtCourseSelect = document.getElementById('modalOtCourseSelect');
    if (modalOtCourseSelect) {
        modalOtCourseSelect.addEventListener('change', function() {
            const coursePk = this.value;
            const otStudentSelect = document.getElementById('modalOtStudentSelect');
            const nameInput = document.getElementById('modalClientNameInput');
            if (!otStudentSelect || !nameInput) return;
            if (otStudentSelect.tomselect) { try { otStudentSelect.tomselect.destroy(); } catch (e) {} }
            otStudentSelect.innerHTML = '<option value="">Loading...</option>';
            otStudentSelect.value = '';
            const selectedOpt = this.options[this.selectedIndex];
            nameInput.value = (selectedOpt && selectedOpt.dataset.courseName) ? selectedOpt.dataset.courseName : '';
            if (!coursePk) {
                otStudentSelect.innerHTML = '<option value="">Select course first</option>';
                reinitNameSelectTomSelect(otStudentSelect, 'Select Student');
                setSelectVisible(otStudentSelect, true);
                return;
            }
            fetch(editSvBaseUrl + '/students-by-course/' + coursePk, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(function(data) {
                    otStudentSelect.innerHTML = '<option value="">Select Student</option>';
                    (data.students || []).forEach(function(s) {
                        const opt = document.createElement('option');
                        opt.value = s.display_name || '';
                        opt.textContent = s.display_name || '—';
                        otStudentSelect.appendChild(opt);
                    });
                    reinitNameSelectTomSelect(otStudentSelect, 'Select Student');
                    setSelectVisible(otStudentSelect, true);
                })
                .catch(function() {
                    otStudentSelect.innerHTML = '<option value="">Error loading students</option>';
                    reinitNameSelectTomSelect(otStudentSelect, 'Select Student');
                    setSelectVisible(otStudentSelect, true);
                });
        });
    }
    
    const modalOtStudentSelect = document.getElementById('modalOtStudentSelect');
    if (modalOtStudentSelect) {
        modalOtStudentSelect.addEventListener('change', function() {
            const inp = document.getElementById('modalClientNameInput');
            if (inp) inp.value = this.value || '';
        });
    }
    
    const modalCourseSelect = document.getElementById('modalCourseSelect');
    if (modalCourseSelect) {
        modalCourseSelect.addEventListener('change', function() {
            // Do not auto-fill Name with course value
        });
    }
    
    const modalClientNameSelect = document.getElementById('modalClientNameSelect');
    if (modalClientNameSelect) {
        modalClientNameSelect.addEventListener('change', updateModalNameField);
    }
    
    const modalFacultySelect = document.getElementById('modalFacultySelect');
    if (modalFacultySelect) {
        modalFacultySelect.addEventListener('change', function() {
            const inp = document.getElementById('modalClientNameInput');
            if (inp) inp.value = this.value || '';
        });
    }
    const modalAcademyEl = document.getElementById('modalAcademyStaffSelect');
    if (modalAcademyEl) modalAcademyEl.addEventListener('change', function() {
        const inp = document.getElementById('modalClientNameInput');
        if (inp) inp.value = this.value || '';
    });
    const modalMessEl = document.getElementById('modalMessStaffSelect');
    if (modalMessEl) modalMessEl.addEventListener('change', function() {
        const inp = document.getElementById('modalClientNameInput');
        if (inp) inp.value = this.value || '';
    });
    const checked = document.querySelector('#addSellingVoucherModal .client-type-radio:checked');
    if (checked) checked.dispatchEvent(new Event('change'));

    // Edit modal: same Faculty / Academy Staff / Mess Staff dropdown logic
    function updateEditModalNameField() {
        const clientTypeRadio = document.querySelector('#editSellingVoucherModal .edit-client-type-radio:checked');
        const clientNameSelect = document.getElementById('editClientNameSelect');
        const nameInput = document.getElementById('editModalClientNameInput');
        const facultySelect = document.getElementById('editModalFacultySelect');
        const academyStaffSelect = document.getElementById('editModalAcademyStaffSelect');
        const messStaffSelect = document.getElementById('editModalMessStaffSelect');
        const editCourseSelect = document.getElementById('editModalCourseSelect');
        const editCourseNameSelect = document.getElementById('editModalCourseNameSelect');
        if (!clientTypeRadio || !clientNameSelect || !nameInput) return;
        const isEmployee = (clientTypeRadio.value || '').toLowerCase() === 'employee';
        const isOt = (clientTypeRadio.value || '').toLowerCase() === 'ot';
        const isCourse = (clientTypeRadio.value || '').toLowerCase() === 'course';
        const opt = clientNameSelect.options[clientNameSelect.selectedIndex];
        const clientNameVal = (opt && opt.dataset.clientName) ? opt.dataset.clientName : '';
        const isFaculty = clientNameVal === 'faculty';
        const isAcademyStaff = clientNameVal === 'academy staff';
        const isMessStaff = clientNameVal === 'mess staff';
        const showFaculty = isEmployee && isFaculty;
        const showAcademyStaff = isEmployee && isAcademyStaff;
        const showMessStaff = isEmployee && isMessStaff;
        const showAny = showFaculty || showAcademyStaff || showMessStaff;
        if (isOt) {
            nameInput.style.display = 'block';
            nameInput.readOnly = true;
            nameInput.removeAttribute('required');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) { if (sel) { setSelectVisible(sel, false); sel.value = ''; sel.removeAttribute('required'); } });
            if (editCourseSelect) { setSelectVisible(editCourseSelect, false); editCourseSelect.value = ''; editCourseSelect.removeAttribute('required'); }
            if (editCourseNameSelect) { setSelectVisible(editCourseNameSelect, false); editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
        } else if (isCourse) {
            nameInput.style.display = 'block';
            nameInput.placeholder = 'Course name';
            nameInput.removeAttribute('readonly');
            nameInput.readOnly = false;
            nameInput.setAttribute('required', 'required');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) { if (sel) { setSelectVisible(sel, false); sel.value = ''; sel.removeAttribute('required'); } });
            if (editCourseSelect) { setSelectVisible(editCourseSelect, true); }
            if (editCourseNameSelect) { setSelectVisible(editCourseNameSelect, false); editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
        } else {
            nameInput.style.display = showAny ? 'none' : 'block';
            nameInput.removeAttribute('required');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) {
                if (!sel) return;
                const show = sel === facultySelect ? showFaculty : (sel === academyStaffSelect ? showAcademyStaff : showMessStaff);
                setSelectVisible(sel, show);
                sel.removeAttribute('required');
                if (show) { sel.setAttribute('required', 'required'); sel.value = nameInput.value || ''; if (sel.value) nameInput.value = sel.value; } else sel.value = '';
            });
            if (editCourseSelect) { setSelectVisible(editCourseSelect, false); editCourseSelect.value = ''; editCourseSelect.removeAttribute('required'); }
            if (editCourseNameSelect) { setSelectVisible(editCourseNameSelect, false); editCourseNameSelect.value = ''; editCourseNameSelect.removeAttribute('required'); }
            if (!showAny) nameInput.setAttribute('required', 'required');
        }
    }
    document.querySelectorAll('#editSellingVoucherModal .edit-client-type-radio').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const isOt = (this.value || '').toLowerCase() === 'ot';
            const isCourse = (this.value || '').toLowerCase() === 'course';
            const clientSelect = document.getElementById('editClientNameSelect');
            const otCourseSelect = document.getElementById('editModalOtCourseSelect');
            const editCourseSelect = document.getElementById('editModalCourseSelect');
            const editCourseNameSelect = document.getElementById('editModalCourseNameSelect');
            const nameInput = document.getElementById('editModalClientNameInput');
            if (isOt) {
                if (clientSelect) { setSelectVisible(clientSelect, false); clientSelect.removeAttribute('required'); clientSelect.value = ''; clientSelect.removeAttribute('name'); }
                if (otCourseSelect) { setSelectVisible(otCourseSelect, true); otCourseSelect.setAttribute('required', 'required'); otCourseSelect.setAttribute('name', 'client_type_pk'); otCourseSelect.value = ''; }
                if (editCourseSelect) { setSelectVisible(editCourseSelect, false); editCourseSelect.removeAttribute('required'); editCourseSelect.removeAttribute('name'); editCourseSelect.value = ''; }
                if (editCourseNameSelect) { setSelectVisible(editCourseNameSelect, false); editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
                if (nameInput) { nameInput.style.display = 'block'; nameInput.readOnly = true; nameInput.placeholder = 'Select course above'; nameInput.value = nameInput.value || ''; nameInput.removeAttribute('required'); }
            } else if (isCourse) {
                if (clientSelect) { setSelectVisible(clientSelect, false); clientSelect.removeAttribute('required'); clientSelect.value = ''; clientSelect.removeAttribute('name'); }
                if (otCourseSelect) { setSelectVisible(otCourseSelect, false); otCourseSelect.removeAttribute('required'); otCourseSelect.removeAttribute('name'); otCourseSelect.value = ''; }
                if (editCourseSelect) { setSelectVisible(editCourseSelect, true); editCourseSelect.setAttribute('required', 'required'); editCourseSelect.setAttribute('name', 'client_type_pk'); editCourseSelect.value = ''; }
                if (editCourseNameSelect) { setSelectVisible(editCourseNameSelect, false); editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
                if (nameInput) { nameInput.style.display = 'block'; nameInput.readOnly = false; nameInput.placeholder = 'Course name'; nameInput.value = nameInput.value || ''; nameInput.setAttribute('required', 'required'); }
            } else {
                if (clientSelect) { setSelectVisible(clientSelect, true); clientSelect.setAttribute('required', 'required'); clientSelect.setAttribute('name', 'client_type_pk'); }
                if (otCourseSelect) { setSelectVisible(otCourseSelect, false); otCourseSelect.removeAttribute('required'); otCourseSelect.removeAttribute('name'); otCourseSelect.value = ''; }
                if (editCourseSelect) { setSelectVisible(editCourseSelect, false); editCourseSelect.removeAttribute('required'); editCourseSelect.removeAttribute('name'); editCourseSelect.value = ''; }
                if (editCourseNameSelect) { setSelectVisible(editCourseNameSelect, false); editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
                if (clientSelect && clientNameOptionsEdit.length) {
                    rebuildClientNameSelect(clientSelect, clientNameOptionsEdit, this.value);
                }
                if (nameInput) { nameInput.style.display = 'block'; nameInput.readOnly = false; nameInput.placeholder = 'Client / section / role name'; nameInput.setAttribute('required', 'required'); }
            }
            updateEditModalNameField();
        });
    });
    const editModalOtCourseSelect = document.getElementById('editModalOtCourseSelect');
    if (editModalOtCourseSelect) {
        editModalOtCourseSelect.addEventListener('change', function() {
            const selectedOpt = this.options[this.selectedIndex];
            const inp = document.getElementById('editModalClientNameInput');
            if (inp) inp.value = (selectedOpt && selectedOpt.dataset.courseName) ? selectedOpt.dataset.courseName : '';
        });
    }
    
    const editModalCourseSelect = document.getElementById('editModalCourseSelect');
    if (editModalCourseSelect) {
        editModalCourseSelect.addEventListener('change', function() {
            const inp = document.getElementById('editModalClientNameInput');
            const courseName = (this.options[this.selectedIndex] && this.options[this.selectedIndex].textContent) ? this.options[this.selectedIndex].textContent.trim() : '';
            if (inp) inp.value = courseName;
        });
    }
    
    const editClientNameSelect = document.getElementById('editClientNameSelect');
    if (editClientNameSelect) {
        editClientNameSelect.addEventListener('change', updateEditModalNameField);
    }
    
    const editModalFacultySelect = document.getElementById('editModalFacultySelect');
    if (editModalFacultySelect) {
        editModalFacultySelect.addEventListener('change', function() {
            const inp = document.getElementById('editModalClientNameInput');
            if (inp) inp.value = this.value || '';
        });
    }
    const editModalAcademyEl = document.getElementById('editModalAcademyStaffSelect');
    if (editModalAcademyEl) editModalAcademyEl.addEventListener('change', function() {
        const inp = document.getElementById('editModalClientNameInput');
        if (inp) inp.value = this.value || '';
    });
    const editModalMessEl = document.getElementById('editModalMessStaffSelect');
    if (editModalMessEl) editModalMessEl.addEventListener('change', function() {
        const inp = document.getElementById('editModalClientNameInput');
        if (inp) inp.value = this.value || '';
    });

    function getEditRowHtml(index, item) {
        const sourceItems = Array.isArray(filteredItems) && filteredItems.length > 0 ? filteredItems : itemSubcategories;
        const options = sourceItems.map(s => {
            let attrs = 'data-unit="' + (s.unit_measurement || '').replace(/"/g, '&quot;') + '" data-rate="' + (s.standard_cost || 0) + '" data-available="' + (s.available_quantity || 0) + '"';
            if (s.price_tiers && s.price_tiers.length > 0) {
                attrs += ' data-price-tiers="' + (JSON.stringify(s.price_tiers) || '').replace(/"/g, '&quot;') + '"';
            }
            return '<option value="' + s.id + '" ' + attrs + (item && item.item_subcategory_id == s.id ? ' selected' : '') + '>' + (s.item_name || '—').replace(/</g, '&lt;') + '</option>';
        }).join('');
        const qty = item ? item.quantity : '';
        const avail = item ? item.available_quantity : 0;
        const rate = item ? item.rate : '';
        const total = item ? item.amount : '';
        const unit = item ? (item.unit || '') : '';
        const left = item && (avail - qty) >= 0 ? (avail - qty) : 0;
        const originalQtyAttr = item ? (' data-original-qty="' + (parseFloat(item.quantity) || 0) + '"') : '';
        return '<tr class="sv-item-row edit-sv-item-row"' + originalQtyAttr + '>' +
            '<td><select name="items[' + index + '][item_subcategory_id]" class="form-select form-select-sm sv-item-select" required><option value="">Select Item</option>' + options + '</select></td>' +
            '<td><input type="text" name="items[' + index + '][unit]" class="form-control  sv-unit" readonly placeholder="—" value="' + (unit || '') + '"></td>' +
            '<td><input type="number" name="items[' + index + '][available_quantity]" class="form-control  sv-avail bg-light" step="0.01" min="0" value="' + avail + '" placeholder="0" readonly></td>' +
            '<td><input type="number" name="items[' + index + '][quantity]" class="form-control  sv-qty" step="0.01" min="0.01" placeholder="0" value="' + qty + '" required><div class="invalid-feedback">Issue Qty cannot exceed Available Qty.</div></td>' +
            '<td><input type="text" class="form-control  sv-left bg-light" readonly placeholder="0" value="' + left + '"></td>' +
            '<td><input type="number" name="items[' + index + '][rate]" class="form-control  sv-rate" step="0.01" min="0" placeholder="0" value="' + rate + '" required></td>' +
            '<td><input type="text" class="form-control  sv-total bg-light" readonly placeholder="0.00" value="' + (total ? total.toFixed(2) : '') + '"></td>' +
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

    /**
     * Recalculate Available Qty and Left Qty for all rows in the Edit modal.
     * Effective base per item = current stock + sum of original qtys (from this voucher) for that item.
     * Then each row gets available = base - already used in previous rows (same logic as Add mode).
     */
    function refreshEditAllAvailable() {
        const rows = document.querySelectorAll('#editModalItemsBody .sv-item-row');
        if (!rows.length) return;

        const effectiveBaseByItem = {};
        rows.forEach(function(row) {
            const select = row.querySelector('.sv-item-select');
            const itemId = select ? select.value : '';
            if (!itemId) return;
            const originalQty = parseFloat(row.getAttribute('data-original-qty')) || 0;
            if (!effectiveBaseByItem.hasOwnProperty(itemId)) {
                effectiveBaseByItem[itemId] = getBaseAvailableForItem(itemId);
            }
            effectiveBaseByItem[itemId] += originalQty;
        });

        const usedByItem = {};
        rows.forEach(function(row) {
            const select = row.querySelector('.sv-item-select');
            const itemId = select ? select.value : '';
            const availInp = row.querySelector('.sv-avail');
            const leftInp = row.querySelector('.sv-left');
            if (!itemId || !availInp) return;

            const effectiveBase = effectiveBaseByItem[itemId] != null ? effectiveBaseByItem[itemId] : getBaseAvailableForItem(itemId);
            const alreadyUsed = usedByItem[itemId] || 0;
            const availableForRow = Math.max(0, effectiveBase - alreadyUsed);

            availInp.value = availableForRow.toFixed(2);

            const qty = parseFloat(row.querySelector('.sv-qty').value) || 0;
            if (leftInp) {
                leftInp.value = Math.max(0, availableForRow - qty).toFixed(2);
            }

            usedByItem[itemId] = alreadyUsed + qty;
            enforceQtyWithinAvailable(row);
        });
    }

    function updateEditItemDropdowns() {
        const rows = document.querySelectorAll('#editModalItemsBody .sv-item-row');
        rows.forEach(row => {
            const select = row.querySelector('.sv-item-select');
            if (!select) return;

            const currentValue = select.value;
            select.innerHTML = '<option value="">Select Item</option>';

            const sourceItems = Array.isArray(filteredItems) && filteredItems.length > 0 ? filteredItems : itemSubcategories;
            sourceItems.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.item_name || '—';
                option.setAttribute('data-unit', item.unit_measurement || '');
                option.setAttribute('data-rate', item.standard_cost || 0);
                option.setAttribute('data-available', item.available_quantity || 0);
                if (item.price_tiers && item.price_tiers.length > 0) {
                    option.setAttribute('data-price-tiers', JSON.stringify(item.price_tiers));
                }
                if (item.id == currentValue) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
            if (typeof TomSelect !== 'undefined') {
                if (select.tomselect) {
                    try { select.tomselect.destroy(); } catch (e) {}
                }
                new TomSelect(select, {
                    allowEmptyOption: true,
                    dropdownParent: '#editSellingVoucherModal .modal-body',
                    placeholder: 'Select Item',
                    maxOptions: null,
                    highlight: false,
                    controlInput: '<input>',
                    onInitialize: function () { this.activeOption = null; },
                    onDropdownOpen: function (dropdown) {
                        var self = this;
                        var input = this.control_input || (dropdown && dropdown.querySelector('input'));
                        function clearInputAndCursor() {
                            if (typeof self.setTextboxValue === 'function') self.setTextboxValue('');
                            if (typeof self.onSearchChange === 'function') self.onSearchChange('');
                            if (typeof self.refreshOptions === 'function') self.refreshOptions(false);
                            if (input) {
                                input.value = '';
                                input.focus();
                                try { input.setSelectionRange(0, 0); } catch (e) {}
                                input.scrollLeft = 0;
                            }
                        }
                        clearInputAndCursor();
                        setTimeout(clearInputAndCursor, 0);
                        setTimeout(clearInputAndCursor, 50);
                        setTimeout(clearInputAndCursor, 100);
                        if (dropdown) setTimeout(function () {
                            var opts = dropdown.querySelectorAll('.option.active, .option.selected, .option[aria-selected="true"]');
                            opts.forEach(function (opt) {
                                opt.classList.remove('active');
                                opt.classList.remove('selected');
                                opt.setAttribute('aria-selected', 'false');
                            });
                        }, 0);
                    }
                });
            }
            updateUnit(row);
        });
        updateEditGrandTotal();
    }

    function buildEditItemsTable(items) {
        const tbody = document.getElementById('editModalItemsBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        if (!Array.isArray(items) || items.length === 0) {
            tbody.insertAdjacentHTML('beforeend', getEditRowHtml(0, null));
            editRowIndex = 1;
        } else {
            items.forEach((item, i) => {
                tbody.insertAdjacentHTML('beforeend', getEditRowHtml(i, item));
            });
            editRowIndex = items.length;
        }
        // Initialize Tom Select on Edit modal item selects
        if (typeof TomSelect !== 'undefined') {
            tbody.querySelectorAll('.sv-item-select').forEach(function(select) {
                if (select.tomselect) {
                    try { select.tomselect.destroy(); } catch (e) {}
                }
                new TomSelect(select, {
                    allowEmptyOption: true,
                    dropdownParent: '#editSellingVoucherModal .modal-body',
                    placeholder: 'Select Item',
                    maxOptions: null,
                    highlight: false,
                    controlInput: '<input>',
                    onInitialize: function () { this.activeOption = null; },
                    onDropdownOpen: function (dropdown) {
                        var self = this;
                        function clearInputAndCursor() {
                            var input = self.control_input || (dropdown && dropdown.querySelector('input'));
                            if (typeof self.setTextboxValue === 'function') self.setTextboxValue('');
                            if (typeof self.onSearchChange === 'function') self.onSearchChange('');
                            if (typeof self.refreshOptions === 'function') self.refreshOptions(false);
                            if (input) {
                                input.value = '';
                                input.focus();
                                try { input.setSelectionRange(0, 0); } catch (e) {}
                                input.scrollLeft = 0;
                            }
                        }
                        // Edit modal: click karte hi blank state, isliye selection bhi clear
                        self.clear(true);
                        clearInputAndCursor();
                        setTimeout(function () {
                            self.clear(true);
                            clearInputAndCursor();
                        }, 0);
                        setTimeout(function () {
                            self.clear(true);
                            clearInputAndCursor();
                        }, 50);
                        setTimeout(function () {
                            self.clear(true);
                            clearInputAndCursor();
                        }, 100);
                        if (dropdown) setTimeout(function () {
                            var opts = dropdown.querySelectorAll('.option.active, .option.selected, .option[aria-selected="true"]');
                            opts.forEach(function (opt) {
                                opt.classList.remove('active');
                                opt.classList.remove('selected');
                                opt.setAttribute('aria-selected', 'false');
                            });
                        }, 0);
                    }
                });
            });
        }
        updateEditRemoveButtons();
        refreshEditAllAvailable();
        updateEditGrandTotal();
    }

    // View button handler (mousedown ensures single-tap works with DataTables)
    document.addEventListener('mousedown', function(e) {
        const btn = e.target.closest('.btn-view-sv');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const voucherId = btn.getAttribute('data-voucher-id');
            if (!voucherId) {
                console.error('No voucher ID found');
                return;
            }
            console.log('Fetching voucher:', voucherId);
            fetch(viewSvBaseUrl + '/' + voucherId, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => {
                    if (!r.ok) throw new Error('HTTP error ' + r.status);
                    return r.json();
                })
                .then(data => {
                    console.log('Voucher data:', data);
                    const v = data.voucher;
                    const items = data.items || [];
                    document.getElementById('viewSellingVoucherModalLabel').textContent = 'View Selling Voucher #' + (v.pk || voucherId);
                    document.getElementById('viewRequestDate').textContent = v.request_date || '—';
                    document.getElementById('viewIssueDate').textContent = v.issue_date || '—';
                    document.getElementById('viewStoreName').textContent = v.store_name || '—';
                    document.getElementById('viewReferenceNumber').textContent = v.reference_number || '—';
                    document.getElementById('viewOrderBy').textContent = v.order_by || '—';
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
                    // Bill display removed; keep view logic resilient if elements are absent
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
                    const modal = new bootstrap.Modal(document.getElementById('viewSellingVoucherModal'));
                    modal.show();
                })
                .catch(err => { 
                    console.error('Error loading voucher:', err); 
                    alert('Failed to load selling voucher: ' + err.message); 
                });
        }
    }, true);

    // Return button handler (mousedown ensures single-tap works with DataTables)
    document.addEventListener('mousedown', function(e) {
        const btn = e.target.closest('.btn-return-sv');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const voucherId = btn.getAttribute('data-voucher-id');
            if (!voucherId) {
                console.error('No voucher ID found for return');
                return;
            }
            console.log('Loading return data for voucher:', voucherId);
            fetch(returnSvBaseUrl + '/' + voucherId + '/return', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => {
                    if (!r.ok) throw new Error('HTTP error ' + r.status);
                    return r.json();
                })
                .then(data => {
                    console.log('Return data:', data);
                    document.getElementById('returnTransferFromStore').textContent = data.store_name || '—';
                    const issueDate = data.issue_date || '';
                    const tbody = document.getElementById('returnItemModalBody');
                    tbody.innerHTML = '';
                    (data.items || []).forEach(function(item, i) {
                        const id = (item.id != null) ? item.id : '';
                        const name = (item.item_name || '—').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                        const qty = item.quantity != null ? item.quantity : '';
                        const unit = (item.unit || '—').replace(/</g, '&lt;');
                        const retQty = item.return_quantity != null ? item.return_quantity : 0;
                        const retDate = item.return_date || '';
                        const issuedQty = parseFloat(qty) || 0;
                        tbody.insertAdjacentHTML('beforeend',
                            '<tr><td>' + name + '<input type="hidden" name="items[' + i + '][id]" value="' + id + '"></td><td>' + qty + '</td><td>' + unit + '</td>' +
                            '<td><input type="number" name="items[' + i + '][return_quantity]" class="form-control  sv-return-qty" step="0.01" min="0" max="' + issuedQty + '" data-issued="' + issuedQty + '" value="' + retQty + '"><div class="invalid-feedback">Return Qty cannot exceed Issued Qty.</div></td>' +
                            '<td><input type="date" name="items[' + i + '][return_date]" class="form-control  sv-return-date" ' + (issueDate ? ('min="' + issueDate + '" data-issue-date="' + issueDate + '"') : '') + ' value="' + retDate + '"><div class="invalid-feedback">Return date cannot be earlier than issue date.</div></td></tr>');
                    });
                    document.getElementById('returnItemForm').action = returnSvBaseUrl + '/' + voucherId + '/return';
                    const modal = new bootstrap.Modal(document.getElementById('returnItemModal'));
                    modal.show();
                })
                .catch(err => { 
                    console.error('Error loading return data:', err); 
                    alert('Failed to load return data: ' + err.message); 
                });
        }
    }, true);

    function enforceReturnQtyWithinIssued(inputEl) {
        if (!inputEl) return;
        const issued = parseFloat(inputEl.dataset.issued) || 0;
        const raw = inputEl.value;
        const val = parseFloat(raw);
        inputEl.max = String(issued);
        if (raw === '' || Number.isNaN(val)) {
            inputEl.setCustomValidity('');
            inputEl.classList.remove('is-invalid');
            return;
        }
        if (val > issued) {
            inputEl.setCustomValidity('Return Qty cannot exceed Issued Qty.');
            inputEl.classList.add('is-invalid');
        } else {
            inputEl.setCustomValidity('');
            inputEl.classList.remove('is-invalid');
        }
    }

    function enforceReturnDateNotBeforeIssue(inputEl) {
        if (!inputEl) return;
        const issue = inputEl.dataset.issueDate || '';
        const raw = inputEl.value;
        if (!issue || !raw) {
            inputEl.setCustomValidity('');
            inputEl.classList.remove('is-invalid');
            return;
        }
        // yyyy-mm-dd safe lexical compare
        if (raw < issue) {
            inputEl.setCustomValidity('Return date cannot be earlier than issue date.');
            inputEl.classList.add('is-invalid');
        } else {
            inputEl.setCustomValidity('');
            inputEl.classList.remove('is-invalid');
        }
    }

    const returnItemModalBody = document.getElementById('returnItemModalBody');
    if (returnItemModalBody) {
        returnItemModalBody.addEventListener('input', function(e) {
            if (e.target && e.target.classList.contains('sv-return-qty')) {
                enforceReturnQtyWithinIssued(e.target);
            }
            if (e.target && e.target.classList.contains('sv-return-date')) {
                enforceReturnDateNotBeforeIssue(e.target);
            }
        });
    }

    const returnItemForm = document.getElementById('returnItemForm');
    if (returnItemForm) {
        returnItemForm.addEventListener('submit', function(e) {
            this.querySelectorAll('.sv-return-qty').forEach(enforceReturnQtyWithinIssued);
            this.querySelectorAll('.sv-return-date').forEach(enforceReturnDateNotBeforeIssue);
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
            }
        }, true);
    }

    // Edit button handler (mousedown ensures single-tap works with DataTables)
    document.addEventListener('mousedown', function(e) {
        const btn = e.target.closest('.btn-edit-sv');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const voucherId = btn.getAttribute('data-voucher-id');
            if (!voucherId) {
                console.error('No voucher ID found for edit');
                return;
            }
            console.log('Loading edit data for voucher:', voucherId);
            fetch(editSvBaseUrl + '/' + voucherId + '/edit', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json().then(data => ({ ok: r.ok, status: r.status, data })))
                .then(({ ok, status, data }) => {
                    if (!ok) {
                        alert(data && data.error ? data.error : 'Failed to load voucher (HTTP ' + status + ').');
                        return;
                    }
                    console.log('Edit data:', data);
                    if (data.error) { alert(data.error); return; }
                    const v = data.voucher;
                    const items = data.items || [];
                    document.getElementById('editSellingVoucherForm').action = editSvBaseUrl + '/' + voucherId;
                    
                    // Set client type radio
                    const clientTypeRadio = document.querySelector('#editSellingVoucherModal input[name="client_type_slug"][value="' + (v.client_type_slug || 'employee') + '"]');
                    if (clientTypeRadio) {
                        clientTypeRadio.checked = true;
                        clientTypeRadio.dispatchEvent(new Event('change'));
                    }
                    
                    document.querySelector('#editSellingVoucherModal select.edit-payment-type').value = String(v.payment_type ?? 1);
                    
                    const clientTypePkSelect = document.querySelector('#editSellingVoucherModal select[name="client_type_pk"]');
                    if (clientTypePkSelect) clientTypePkSelect.value = v.client_type_pk || '';
                    
                    document.getElementById('editModalClientNameInput').value = v.client_name || '';
                    document.getElementById('editModalFacultySelect').value = v.client_name || '';
                    const editAcademyEl = document.getElementById('editModalAcademyStaffSelect');
                    if (editAcademyEl) editAcademyEl.value = v.client_name || '';
                    const editMessEl = document.getElementById('editModalMessStaffSelect');
                    if (editMessEl) editMessEl.value = v.client_name || '';
                    const editOtCourseEl = document.getElementById('editModalOtCourseSelect');
                    if (editOtCourseEl) editOtCourseEl.value = v.client_type_pk || '';
                    const editCourseEl = document.getElementById('editModalCourseSelect');
                    if (editCourseEl) editCourseEl.value = v.client_type_pk || '';
                    const editCourseNameEl = document.getElementById('editModalCourseNameSelect');
                    if (editCourseNameEl) editCourseNameEl.value = v.client_type_pk || '';
                    document.querySelector('#editSellingVoucherModal input.edit-issue-date').value = v.issue_date || '';
                    
                    const storeSelect = document.querySelector('#editSellingVoucherModal select.edit-store');
                    if (storeSelect) storeSelect.value = v.inve_store_master_pk || v.store_id || '';
                    
                    document.querySelector('#editSellingVoucherModal input.edit-remarks').value = v.remarks || '';
                    const editRefNum = document.querySelector('#editSellingVoucherModal input.edit-reference-number');
                    if (editRefNum) editRefNum.value = v.reference_number || '';
                    const editOrderBy = document.querySelector('#editSellingVoucherModal input.edit-order-by');
                    if (editOrderBy) editOrderBy.value = v.order_by || '';
                    var editBillFileNameEl = document.getElementById('editBillCurrentFileName');
                    if (editBillFileNameEl) {
                        if (v.bill_path) {
                            var billFileName = v.bill_path.split('/').pop() || v.bill_path;
                            editBillFileNameEl.textContent = billFileName;
                            editBillFileNameEl.setAttribute('title', billFileName);
                        } else {
                            editBillFileNameEl.textContent = 'No file chosen';
                            editBillFileNameEl.removeAttribute('title');
                        }
                    }
                    var editSvBillFileInputEl = document.getElementById('editSvBillFileInput');
                    if (editSvBillFileInputEl) editSvBillFileInputEl.value = '';
                    var editRemoveBillFlagEl = document.getElementById('editRemoveBillFlag');
                    if (editRemoveBillFlagEl) editRemoveBillFlagEl.value = '0';
                    var editBillLinkEl = document.getElementById('editCurrentBillLink');
                    if (editBillLinkEl) {
                        if (v.bill_url) {
                            editBillLinkEl.innerHTML = 'Current bill: <a href="' + (v.bill_url || '').replace(/"/g, '&quot;') + '" target="_blank" rel="noopener" class="text-primary">View Bill</a>';
                        } else {
                            editBillLinkEl.innerHTML = '';
                        }
                    }
                    editCurrentStoreId = storeSelect ? storeSelect.value || '' : null;
                    const openEditModalWithItems = function() {
                        buildEditItemsTable(items);
                        // Initialize Tom Select in Edit modal (payment, client, store, name dropdowns, item selects)
                        if (typeof initEditModalTomSelects === 'function') {
                            initEditModalTomSelects();
                        }
                        // After Tom Select init, show only the active dropdowns in Client Name column and Name column
                        if (typeof applyEditModalClientNameColumnVisibility === 'function') {
                            applyEditModalClientNameColumnVisibility();
                        }
                        if (typeof updateEditModalNameField === 'function') {
                            updateEditModalNameField();
                        }
                        const modal = new bootstrap.Modal(document.getElementById('editSellingVoucherModal'));
                        modal.show();
                    };
                    if (editCurrentStoreId) {
                        fetchStoreItems(editCurrentStoreId, function() {
                            updateEditItemDropdowns();
                            openEditModalWithItems();
                        });
                    } else {
                        filteredItems = itemSubcategories;
                        openEditModalWithItems();
                    }
                    const isOt = (v.client_type_slug || '') === 'ot';
                    const isCourse = (v.client_type_slug || '') === 'course';
                    const editClientSelect = document.getElementById('editClientNameSelect');
                    const editOtSelect = document.getElementById('editModalOtCourseSelect');
                    const editCourseSelect = document.getElementById('editModalCourseSelect');
                    const editCourseNameSelect = document.getElementById('editModalCourseNameSelect');
                    const editNameInp = document.getElementById('editModalClientNameInput');
                    if (isOt) {
                        if (editClientSelect) { editClientSelect.style.display = 'none'; editClientSelect.removeAttribute('required'); editClientSelect.removeAttribute('name'); }
                        if (editOtSelect) { editOtSelect.style.display = 'block'; editOtSelect.setAttribute('required', 'required'); editOtSelect.setAttribute('name', 'client_type_pk'); editOtSelect.value = v.client_type_pk || ''; }
                        if (editCourseSelect) { editCourseSelect.style.display = 'none'; editCourseSelect.removeAttribute('required'); editCourseSelect.removeAttribute('name'); editCourseSelect.value = ''; }
                        if (editCourseNameSelect) { editCourseNameSelect.style.display = 'none'; editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
                        if (editNameInp) { editNameInp.style.display = 'block'; editNameInp.readOnly = true; editNameInp.placeholder = 'Name (from course/student)'; editNameInp.value = v.client_name || ''; editNameInp.removeAttribute('required'); }
                    } else if (isCourse) {
                        if (editClientSelect) { editClientSelect.style.display = 'none'; editClientSelect.removeAttribute('required'); editClientSelect.removeAttribute('name'); }
                        if (editOtSelect) { editOtSelect.style.display = 'none'; editOtSelect.removeAttribute('required'); editOtSelect.removeAttribute('name'); editOtSelect.value = ''; }
                        if (editCourseSelect) { editCourseSelect.style.display = 'block'; editCourseSelect.setAttribute('required', 'required'); editCourseSelect.setAttribute('name', 'client_type_pk'); editCourseSelect.value = v.client_type_pk || ''; }
                        if (editCourseNameSelect) { editCourseNameSelect.style.display = 'none'; editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
                        if (editNameInp) { editNameInp.style.display = 'block'; editNameInp.readOnly = false; editNameInp.placeholder = 'Course name'; editNameInp.value = v.client_name || ''; editNameInp.setAttribute('required', 'required'); }
                    } else {
                        if (editClientSelect) { editClientSelect.style.display = 'block'; editClientSelect.setAttribute('required', 'required'); editClientSelect.setAttribute('name', 'client_type_pk'); editClientSelect.querySelectorAll('option').forEach(function(opt) { if (opt.value === '') { opt.hidden = false; return; } opt.hidden = (opt.dataset.type || '') !== (v.client_type_slug || 'employee'); }); }
                        if (editOtSelect) { editOtSelect.style.display = 'none'; editOtSelect.removeAttribute('required'); editOtSelect.removeAttribute('name'); editOtSelect.value = ''; }
                        if (editCourseSelect) { editCourseSelect.style.display = 'none'; editCourseSelect.removeAttribute('required'); editCourseSelect.removeAttribute('name'); editCourseSelect.value = ''; }
                        if (editCourseNameSelect) { editCourseNameSelect.style.display = 'none'; editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
                        if (editNameInp) { editNameInp.style.display = 'block'; editNameInp.readOnly = false; editNameInp.placeholder = 'Client / section / role name'; editNameInp.setAttribute('required', 'required'); }
                    }
                    updateEditModalNameField();
                })
                .catch(err => { 
                    console.error('Error loading voucher for edit:', err); 
                    alert('Failed to load selling voucher: ' + err.message); 
                });
        }
    }, true);

    const editModalAddItemRow = document.getElementById('editModalAddItemRow');
    if (editModalAddItemRow) {
        editModalAddItemRow.addEventListener('click', function() {
            const tbody = document.getElementById('editModalItemsBody');
            if (tbody) {
                tbody.insertAdjacentHTML('beforeend', getEditRowHtml(editRowIndex, null));
                editRowIndex++;
                const newRow = tbody.querySelector('.sv-item-row:last-child');
                const newSelect = newRow ? newRow.querySelector('.sv-item-select') : null;
                if (newSelect && typeof TomSelect !== 'undefined') {
                    if (newSelect.tomselect) {
                        try { newSelect.tomselect.destroy(); } catch (e) {}
                    }
                    new TomSelect(newSelect, {
                        allowEmptyOption: true,
                        dropdownParent: '#editSellingVoucherModal .modal-body',
                        placeholder: 'Select Item',
                        maxOptions: null,
                        highlight: false,
                        controlInput: '<input>',
                        onInitialize: function () { this.activeOption = null; },
                        onDropdownOpen: function (dropdown) {
                            var self = this;
                            function clearInputAndCursor() {
                                var input = self.control_input || (dropdown && dropdown.querySelector('input'));
                                if (typeof self.setTextboxValue === 'function') self.setTextboxValue('');
                                if (typeof self.onSearchChange === 'function') self.onSearchChange('');
                                if (typeof self.refreshOptions === 'function') self.refreshOptions(false);
                                if (input) {
                                    input.value = '';
                                    input.focus();
                                    try { input.setSelectionRange(0, 0); } catch (e) {}
                                    input.scrollLeft = 0;
                                }
                            }
                            // Edit modal new row: open par bhi blank state
                            self.clear(true);
                            clearInputAndCursor();
                            setTimeout(function () {
                                self.clear(true);
                                clearInputAndCursor();
                            }, 0);
                            setTimeout(function () {
                                self.clear(true);
                                clearInputAndCursor();
                            }, 50);
                            setTimeout(function () {
                                self.clear(true);
                                clearInputAndCursor();
                            }, 100);
                            if (dropdown) setTimeout(function () {
                                var opts = dropdown.querySelectorAll('.option.active, .option.selected, .option[aria-selected="true"]');
                                opts.forEach(function (opt) {
                                    opt.classList.remove('active');
                                    opt.classList.remove('selected');
                                    opt.setAttribute('aria-selected', 'false');
                                });
                            }, 0);
                        }
                    });
                }
                updateEditRemoveButtons();
                refreshEditAllAvailable();
                updateEditGrandTotal();
            }
        });
    }

    // Add modal: show selected bill file name and Remove button
    var addSvBillFileInputEl = document.getElementById('addSvBillFileInput');
    if (addSvBillFileInputEl) {
        addSvBillFileInputEl.addEventListener('change', function() {
            var wrap = document.getElementById('addSvBillFileChosenWrap');
            var nameEl = document.getElementById('addSvBillFileChosenName');
            if (wrap && nameEl) {
                if (this.files && this.files[0]) {
                    nameEl.textContent = this.files[0].name;
                    wrap.classList.remove('d-none');
                } else {
                    nameEl.textContent = '';
                    wrap.classList.add('d-none');
                }
            }
        });
    }
    var addSvBillFileRemoveEl = document.getElementById('addSvBillFileRemove');
    if (addSvBillFileRemoveEl) {
        addSvBillFileRemoveEl.addEventListener('click', function() {
            var input = document.getElementById('addSvBillFileInput');
            var wrap = document.getElementById('addSvBillFileChosenWrap');
            var nameEl = document.getElementById('addSvBillFileChosenName');
            if (input) input.value = '';
            if (nameEl) nameEl.textContent = '';
            if (wrap) wrap.classList.add('d-none');
        });
    }

    // Edit modal: show selected file name when user picks a new bill (same as Selling Voucher with Date Range)
    var editSvBillFileInputEl = document.getElementById('editSvBillFileInput');
    if (editSvBillFileInputEl) {
        editSvBillFileInputEl.addEventListener('change', function() {
            var pathEl = document.getElementById('editBillCurrentFileName');
            var removeFlag = document.getElementById('editRemoveBillFlag');
            if (pathEl) pathEl.textContent = this.files && this.files[0] ? this.files[0].name : 'No file chosen';
            if (removeFlag) removeFlag.value = '0';
        });
    }
    var editSvBillFileRemoveEl = document.getElementById('editSvBillFileRemove');
    if (editSvBillFileRemoveEl) {
        editSvBillFileRemoveEl.addEventListener('click', function() {
            var input = document.getElementById('editSvBillFileInput');
            var pathEl = document.getElementById('editBillCurrentFileName');
            var removeFlag = document.getElementById('editRemoveBillFlag');
            if (input) input.value = '';
            if (pathEl) pathEl.textContent = 'No file chosen';
            if (removeFlag) removeFlag.value = '1';
        });
    }

    const editModalItemsBody = document.getElementById('editModalItemsBody');
    if (editModalItemsBody) {
        editModalItemsBody.addEventListener('change', function(e) {
            if (e.target.classList.contains('sv-item-select')) {
                const row = e.target.closest('.sv-item-row');
                if (row) { updateUnit(row); calcRow(row); updateEditGrandTotal(); }
            }
        });
        
        editModalItemsBody.addEventListener('input', function(e) {
            if (e.target.classList.contains('sv-avail') || e.target.classList.contains('sv-qty') || e.target.classList.contains('sv-rate')) {
                const row = e.target.closest('.sv-item-row');
                if (row) {
                    refreshEditAllAvailable();
                    calcRow(row);
                    updateEditGrandTotal();
                }
            }
        });
        
        editModalItemsBody.addEventListener('click', function(e) {
            if (e.target.classList.contains('sv-remove-row')) {
                const row = e.target.closest('.sv-item-row');
                if (row && document.querySelectorAll('#editModalItemsBody .sv-item-row').length > 1) {
                    row.remove();
                    refreshEditAllAvailable();
                    updateEditGrandTotal();
                    updateEditRemoveButtons();
                }
            }
        });
    }

    // Store selection change in EDIT modal
    const editStoreSelect = document.querySelector('#editSellingVoucherModal select.edit-store');
    if (editStoreSelect) {
        editStoreSelect.addEventListener('change', function() {
            const storeId = this.value;
            editCurrentStoreId = storeId;
            if (!storeId) {
                filteredItems = itemSubcategories;
                updateEditItemDropdowns();
                return;
            }
            fetchStoreItems(storeId, function() {
                updateEditItemDropdowns();
            });
        });
    }

    // Reset add selling voucher modal when closed (so next open starts fresh)
    const addSellingVoucherModal = document.getElementById('addSellingVoucherModal');
    if (addSellingVoucherModal) {
        addSellingVoucherModal.addEventListener('hidden.bs.modal', function() {
            destroyAddModalTomSelects();
            const form = document.getElementById('sellingVoucherModalForm');
            if (form) {
                form.reset();
                form.classList.remove('was-validated');
                form.querySelectorAll('.is-invalid').forEach(function(el) { el.classList.remove('is-invalid'); });
            }
            const storeSel = addSellingVoucherModal.querySelector('select[name="store_id"]');
            if (storeSel) storeSel.value = '';
            const issueDateInp = addSellingVoucherModal.querySelector('input[name="issue_date"]');
            if (issueDateInp) issueDateInp.value = new Date().toISOString().slice(0, 10);
            const paymentSel = addSellingVoucherModal.querySelector('select[name="payment_type"]');
            if (paymentSel) paymentSel.value = '1';
            const empRadio = addSellingVoucherModal.querySelector('.client-type-radio[value="employee"]');
            if (empRadio) { empRadio.checked = true; empRadio.dispatchEvent(new Event('change')); }
            const clientPkSel = addSellingVoucherModal.querySelector('#modalClientNameSelect');
            if (clientPkSel) clientPkSel.value = '';
            const clientNameInp = document.getElementById('modalClientNameInput');
            if (clientNameInp) clientNameInp.value = '';
            addSellingVoucherModal.querySelectorAll('#modalClientNameWrap select, #modalNameFieldWrap select').forEach(function(s) { if (s.value !== undefined) s.value = ''; });
            const billInput = document.getElementById('addSvBillFileInput');
            if (billInput) billInput.value = '';
            const billWrap = document.getElementById('addSvBillFileChosenWrap');
            const billName = document.getElementById('addSvBillFileChosenName');
            if (billWrap) billWrap.classList.add('d-none');
            if (billName) billName.textContent = '';
            const tbody = document.getElementById('modalItemsBody');
            if (tbody) {
                tbody.innerHTML = getRowHtml(0);
                rowIndex = 1;
                updateRemoveButtons();
            }
            const grandTotalEl = document.getElementById('modalGrandTotal');
            if (grandTotalEl) grandTotalEl.textContent = '₹0.00';
        });

        addSellingVoucherModal.addEventListener('show.bs.modal', function() {
            const storeSelect = addSellingVoucherModal.querySelector('select[name="store_id"]');
            const preSelectedStore = storeSelect ? storeSelect.value : null;
            
            console.log('Modal opening, pre-selected store:', preSelectedStore); // Debug log
            
            // If there's a pre-selected store, fetch its items
            if (preSelectedStore) {
                currentStoreId = preSelectedStore;
                fetchStoreItems(preSelectedStore, function() {
                    console.log('Pre-fetched items for store:', preSelectedStore, 'Count:', filteredItems.length);
                    updateAddItemDropdowns();
                    refreshAllAvailable();
                    document.querySelectorAll('#modalItemsBody .sv-item-row').forEach(function(row) { calcRow(row); });
                    updateGrandTotal();
                });
            } else {
                currentStoreId = null;
                filteredItems = itemSubcategories;
                if (storeSelect) storeSelect.value = '';
            }
        });
        addSellingVoucherModal.addEventListener('shown.bs.modal', function() {
            initAddModalTomSelects();
            refreshAllAvailable();
            document.querySelectorAll('#modalItemsBody .sv-item-row').forEach(function(row) { calcRow(row); });
            updateGrandTotal();
        });
    }

    // Before disabling submit buttons, ensure form is valid (includes qty <= available)
    if (sellingVoucherModalForm) {
        sellingVoucherModalForm.addEventListener('submit', function(e) {
            // sync validity for all rows
            document.querySelectorAll('#modalItemsBody .sv-item-row').forEach(enforceQtyWithinAvailable);
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
                return;
            }
        }, true);
    }

    if (editSellingVoucherForm) {
        editSellingVoucherForm.addEventListener('submit', function(e) {
            document.querySelectorAll('#editModalItemsBody .sv-item-row').forEach(enforceQtyWithinAvailable);
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
                return;
            }
        }, true);
    }

    @if($errors->any() || session('open_selling_voucher_modal'))
    var modal = document.getElementById('addSellingVoucherModal');
    if (modal && typeof bootstrap !== 'undefined') {
        (new bootstrap.Modal(modal)).show();
    }
    @endif

    // Print View modal content (Selling Voucher) – correct design with standard header
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-print-view-modal');
        if (!btn) return;
        var sel = btn.getAttribute('data-print-target');
        if (!sel) return;
        var modalEl = document.querySelector(sel);
        if (!modalEl) return;
        var content = modalEl.querySelector('.modal-content');
        if (!content) return;
        var win = window.open('', '_blank', 'width=900,height=700');
        if (!win) { alert('Please allow popups to print.'); return; }
        var title = (modalEl.querySelector('.modal-title') || {}).textContent || 'Selling Voucher';
        var printedOn = new Date();
        var dateStr = printedOn.getDate().toString().padStart(2,'0') + '/' + (printedOn.getMonth()+1).toString().padStart(2,'0') + '/' + printedOn.getFullYear() + ', ' + printedOn.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true });
        var bodyContent = content.innerHTML.replace(/<button[^>]*btn-close[^>]*>[\s\S]*?<\/button>/gi, '');
        var printHeader = '<div class="print-doc-header" style="text-align:center;margin-bottom:16px;padding-bottom:12px;border-bottom:2px solid #2c3e50;">' +
            '<div style="font-size:16px;font-weight:700;color:#1a1a1a;margin-bottom:4px;">OFFICER\'S MESS LBSNAA MUSSOORIE</div>' +
            '<div style="background:#495057;color:#fff;padding:6px 12px;font-size:13px;display:inline-block;margin:4px 0;">Selling Voucher</div>' +
            '<div style="font-size:11px;color:#6c757d;margin-top:6px;">Printed on ' + dateStr + '</div></div>';
        var printCss = '<style>@page{size:A4;margin:14mm;}body{font-family:Arial,sans-serif;font-size:12px;color:#212529;padding:0 12px;margin:0;background:#fff;}.print-doc-header{-webkit-print-color-adjust:exact;print-color-adjust:exact;}.modal-header{border-bottom:1px solid #dee2e6;padding-bottom:8px;margin-bottom:12px;}.modal-body{color:#212529;}.card{margin-bottom:14px;page-break-inside:avoid;}.card-header{font-weight:600;font-size:12px;margin-bottom:8px;}.card-body table th,.card-body table td{border:1px solid #adb5bd;padding:6px 8px;}table{width:100%;border-collapse:collapse;font-size:11px;}thead th{background:#af2910!important;color:#fff!important;border-color:#8b2009;font-weight:600;-webkit-print-color-adjust:exact;print-color-adjust:exact;}.card-footer{font-weight:600;padding-top:8px;}.btn-close,.modal-footer{display:none!important;}@media print{body{padding:0;}}</style>';
        win.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>' + title.replace(/</g, '&lt;') + '</title>' + printCss + '</head><body>' + printHeader + '<div class="modal-content-wrap">' + bodyContent + '</div></body></html>');
        win.document.close();
        win.focus();
        setTimeout(function() { win.print(); win.close(); }, 350);
    });

    console.log('✅ All event listeners attached successfully');
    console.log('Script initialization complete');
});
</script>
@endsection