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
                                <option value="{{ $store['id'] }}" {{ request('store') == $store['id'] ? 'selected' : '' }}>{{ $store['store_name'] }}</option>
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
        <table class="table table-bordered table-hover align-middle" id="sellingVouchersTable">
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
                @php $serial = 1; @endphp
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
                            <td>{{ $voucher->payment_type == 1 ? 'Credit' : ($voucher->payment_type == 0 ? 'Cash' : ($voucher->payment_type == 2 ? 'Online' : '—')) }}</td>
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
                                <button type="button" class="btn btn-sm btn-warning btn-edit-sv" data-voucher-id="{{ $voucher->pk }}" title="Edit">Edit</button>
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
                            <td>
                                <button type="button" class="btn btn-sm btn-info btn-view-sv" data-voucher-id="{{ $voucher->pk }}" title="View">View</button>
                                <button type="button" class="btn btn-sm btn-warning btn-edit-sv" data-voucher-id="{{ $voucher->pk }}" title="Edit">Edit</button>
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
                        <td></td><td></td><td></td><td></td><td></td><td></td>
                        <td class="text-center py-4">No selling vouchers found.</td>
                        <td></td><td></td><td></td><td></td><td></td><td></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
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
                                                        <option value="{{ $s['id'] }}" data-unit="{{ e($s['unit_measurement'] ?? '') }}" data-rate="{{ e($s['standard_cost'] ?? 0) }}">{{ e($s['item_name'] ?? '—') }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="text" name="items[0][unit]" class="form-control form-control-sm sv-unit" readonly placeholder="—"></td>
                                            <td><input type="number" name="items[0][available_quantity]" class="form-control form-control-sm sv-avail bg-light" step="0.01" min="0" value="0" placeholder="0" readonly></td>
                                            <td>
                                                <input type="number" name="items[0][quantity]" class="form-control form-control-sm sv-qty" step="0.01" min="0.01" placeholder="0" required>
                                                <div class="invalid-feedback">Issue Qty cannot exceed Available Qty.</div>
                                            </td>
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
document.addEventListener('DOMContentLoaded', function() {
    console.log('Selling Voucher script loaded');
    console.log('Bootstrap available:', typeof bootstrap !== 'undefined');

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

        const avail = parseFloat(availEl.value) || 0;
        const qtyRaw = qtyEl.value;
        const qty = parseFloat(qtyRaw);

        // Keep browser constraint in sync
        qtyEl.max = String(avail);

        // If empty, don't force an error yet
        if (qtyRaw === '' || Number.isNaN(qty)) {
            qtyEl.setCustomValidity('');
            qtyEl.classList.remove('is-invalid');
            return;
        }

        if (qty > avail) {
            qtyEl.setCustomValidity('Issue Qty cannot exceed Available Qty.');
            qtyEl.classList.add('is-invalid');
        } else {
            qtyEl.setCustomValidity('');
            qtyEl.classList.remove('is-invalid');
        }
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
            '<td><input type="text" name="items[' + index + '][unit]" class="form-control form-control-sm sv-unit" readonly placeholder="—"></td>' +
            '<td><input type="number" name="items[' + index + '][available_quantity]" class="form-control form-control-sm sv-avail bg-light" step="0.01" min="0" value="0" placeholder="0" readonly></td>' +
            '<td><input type="number" name="items[' + index + '][quantity]" class="form-control form-control-sm sv-qty" step="0.01" min="0.01" placeholder="0" required><div class="invalid-feedback">Issue Qty cannot exceed Available Qty.</div></td>' +
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
        const rateInp = row.querySelector('.sv-rate');
        const availInp = row.querySelector('.sv-avail');
        if (unitInp) unitInp.value = opt && opt.dataset.unit ? opt.dataset.unit : '';
        if (rateInp && opt && opt.dataset.rate) rateInp.value = opt.dataset.rate;
        if (availInp && opt && opt.dataset.available) availInp.value = opt.dataset.available;
        if (availInp) availInp.readOnly = true;
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
            }
        });
    }

    const modalItemsBody = document.getElementById('modalItemsBody');
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
                if (row) { enforceQtyWithinAvailable(row); calcRow(row); updateGrandTotal(); }
            }
        });

        modalItemsBody.addEventListener('click', function(e) {
            if (e.target.classList.contains('sv-remove-row')) {
                const row = e.target.closest('.sv-item-row');
                if (row && document.querySelectorAll('#modalItemsBody .sv-item-row').length > 1) {
                    row.remove();
                    updateGrandTotal();
                    updateRemoveButtons();
                }
            }
        });
    }

    const creditOnly = ['employee', 'ot', 'course'];
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
        if (isOt) {
            nameInput.style.display = 'none';
            nameInput.removeAttribute('required');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) { if (sel) { sel.style.display = 'none'; sel.value = ''; sel.removeAttribute('required'); } });
            if (otStudentSelect) { otStudentSelect.style.display = 'block'; }
            if (courseSelect) { courseSelect.style.display = 'none'; courseSelect.value = ''; courseSelect.removeAttribute('required'); }
            if (courseNameSelect) { courseNameSelect.style.display = 'none'; courseNameSelect.value = ''; courseNameSelect.removeAttribute('required'); }
        } else if (isCourse) {
            nameInput.style.display = 'none';
            nameInput.removeAttribute('required');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) { if (sel) { sel.style.display = 'none'; sel.value = ''; sel.removeAttribute('required'); } });
            if (otStudentSelect) { otStudentSelect.style.display = 'none'; otStudentSelect.value = ''; otStudentSelect.removeAttribute('required'); }
            if (courseSelect) { courseSelect.style.display = 'block'; }
            if (courseNameSelect) { courseNameSelect.style.display = 'block'; }
        } else {
            nameInput.style.display = showAny ? 'none' : 'block';
            nameInput.removeAttribute('required');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) {
                if (!sel) return;
                const show = sel === facultySelect ? showFaculty : (sel === academyStaffSelect ? showAcademyStaff : showMessStaff);
                sel.style.display = show ? 'block' : 'none';
                sel.removeAttribute('required');
                if (show) { sel.setAttribute('required', 'required'); sel.value = nameInput.value || ''; if (sel.value) nameInput.value = sel.value; } else sel.value = '';
            });
            if (otStudentSelect) { otStudentSelect.style.display = 'none'; otStudentSelect.value = ''; otStudentSelect.removeAttribute('required'); }
            if (courseSelect) { courseSelect.style.display = 'none'; courseSelect.value = ''; courseSelect.removeAttribute('required'); }
            if (courseNameSelect) { courseNameSelect.style.display = 'none'; courseNameSelect.value = ''; courseNameSelect.removeAttribute('required'); }
            if (!showAny) nameInput.setAttribute('required', 'required');
        }
    }
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
            const isOt = (this.value || '').toLowerCase() === 'ot';
            const isCourse = (this.value || '').toLowerCase() === 'course';
            const clientSelect = document.getElementById('modalClientNameSelect');
            const otCourseSelect = document.getElementById('modalOtCourseSelect');
            const otStudentSelect = document.getElementById('modalOtStudentSelect');
            const courseSelect = document.getElementById('modalCourseSelect');
            const courseNameSelect = document.getElementById('modalCourseNameSelect');
            const nameInput = document.getElementById('modalClientNameInput');
            if (isOt) {
                if (clientSelect) { clientSelect.style.display = 'none'; clientSelect.removeAttribute('required'); clientSelect.value = ''; clientSelect.removeAttribute('name'); }
                if (otCourseSelect) { otCourseSelect.style.display = 'block'; otCourseSelect.setAttribute('required', 'required'); otCourseSelect.setAttribute('name', 'client_type_pk'); otCourseSelect.value = ''; }
                if (otStudentSelect) { otStudentSelect.style.display = 'block'; otStudentSelect.innerHTML = '<option value="">Select course first</option>'; otStudentSelect.setAttribute('required', 'required'); otStudentSelect.value = ''; }
                if (courseSelect) { courseSelect.style.display = 'none'; courseSelect.removeAttribute('required'); courseSelect.removeAttribute('name'); courseSelect.value = ''; }
                if (courseNameSelect) { courseNameSelect.style.display = 'none'; courseNameSelect.removeAttribute('required'); courseNameSelect.value = ''; }
                if (nameInput) { nameInput.style.display = 'none'; nameInput.value = ''; nameInput.removeAttribute('required'); }
            } else if (isCourse) {
                if (clientSelect) { clientSelect.style.display = 'none'; clientSelect.removeAttribute('required'); clientSelect.value = ''; clientSelect.removeAttribute('name'); }
                if (otCourseSelect) { otCourseSelect.style.display = 'none'; otCourseSelect.removeAttribute('required'); otCourseSelect.removeAttribute('name'); otCourseSelect.value = ''; }
                if (otStudentSelect) { otStudentSelect.style.display = 'none'; otStudentSelect.removeAttribute('required'); otStudentSelect.innerHTML = '<option value="">Select Student</option>'; otStudentSelect.value = ''; }
                if (courseSelect) { courseSelect.style.display = 'block'; courseSelect.setAttribute('required', 'required'); courseSelect.setAttribute('name', 'client_type_pk'); courseSelect.value = ''; }
                if (courseNameSelect) { courseNameSelect.style.display = 'block'; courseNameSelect.setAttribute('required', 'required'); courseNameSelect.value = ''; }
                if (nameInput) { nameInput.style.display = 'none'; nameInput.value = ''; nameInput.removeAttribute('required'); }
            } else {
                if (clientSelect) { clientSelect.style.display = 'block'; clientSelect.setAttribute('required', 'required'); clientSelect.setAttribute('name', 'client_type_pk'); }
                if (otCourseSelect) { otCourseSelect.style.display = 'none'; otCourseSelect.removeAttribute('required'); otCourseSelect.removeAttribute('name'); otCourseSelect.value = ''; }
                if (otStudentSelect) { otStudentSelect.style.display = 'none'; otStudentSelect.removeAttribute('required'); otStudentSelect.innerHTML = '<option value="">Select Student</option>'; otStudentSelect.value = ''; }
                if (courseSelect) { courseSelect.style.display = 'none'; courseSelect.removeAttribute('required'); courseSelect.value = ''; }
                if (courseNameSelect) { courseNameSelect.style.display = 'none'; courseNameSelect.removeAttribute('required'); courseNameSelect.value = ''; }
                if (clientSelect) {
                    clientSelect.querySelectorAll('option').forEach(function(opt) {
                        if (opt.value === '') { opt.hidden = false; return; }
                        opt.hidden = opt.dataset.type !== this.value;
                    }.bind(this));
                }
                if (nameInput) { nameInput.style.display = 'block'; nameInput.placeholder = 'Client / section / role name'; nameInput.setAttribute('required', 'required'); }
            }
            updateModalNameField();
        });
    });
    const modalOtCourseSelect = document.getElementById('modalOtCourseSelect');
    if (modalOtCourseSelect) {
        modalOtCourseSelect.addEventListener('change', function() {
            const coursePk = this.value;
            const otStudentSelect = document.getElementById('modalOtStudentSelect');
            const nameInput = document.getElementById('modalClientNameInput');
            if (!otStudentSelect || !nameInput) return;
            otStudentSelect.innerHTML = '<option value="">Loading...</option>';
            otStudentSelect.value = '';
            const selectedOpt = this.options[this.selectedIndex];
            nameInput.value = (selectedOpt && selectedOpt.dataset.courseName) ? selectedOpt.dataset.courseName : '';
            if (!coursePk) {
                otStudentSelect.innerHTML = '<option value="">Select course first</option>';
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
                })
                .catch(function() { otStudentSelect.innerHTML = '<option value="">Error loading students</option>'; });
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
            const pk = this.value || '';
            const courseNameSelect = document.getElementById('modalCourseNameSelect');
            const inp = document.getElementById('modalClientNameInput');
            const courseName = (this.options[this.selectedIndex] && this.options[this.selectedIndex].textContent) ? this.options[this.selectedIndex].textContent.trim() : '';
            if (courseNameSelect) courseNameSelect.value = pk;
            if (inp) inp.value = courseName;
        });
    }
    
    const modalCourseNameSelect = document.getElementById('modalCourseNameSelect');
    if (modalCourseNameSelect) {
        modalCourseNameSelect.addEventListener('change', function() {
            const pk = this.value || '';
            const courseSelect = document.getElementById('modalCourseSelect');
            const inp = document.getElementById('modalClientNameInput');
            const courseName = (this.options[this.selectedIndex] && this.options[this.selectedIndex].textContent) ? this.options[this.selectedIndex].textContent.trim() : '';
            if (courseSelect) courseSelect.value = pk;
            if (inp) inp.value = courseName;
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
        if (isOt || isCourse) {
            nameInput.style.display = 'none';
            nameInput.removeAttribute('required');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) { if (sel) { sel.style.display = 'none'; sel.value = ''; sel.removeAttribute('required'); } });
            if (isCourse && editCourseSelect) { editCourseSelect.style.display = 'block'; }
            if (isCourse && editCourseNameSelect) { editCourseNameSelect.style.display = 'block'; }
        } else {
            nameInput.style.display = showAny ? 'none' : 'block';
            nameInput.removeAttribute('required');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) {
                if (!sel) return;
                const show = sel === facultySelect ? showFaculty : (sel === academyStaffSelect ? showAcademyStaff : showMessStaff);
                sel.style.display = show ? 'block' : 'none';
                sel.removeAttribute('required');
                if (show) { sel.setAttribute('required', 'required'); sel.value = nameInput.value || ''; if (sel.value) nameInput.value = sel.value; } else sel.value = '';
            });
            if (editCourseSelect) { editCourseSelect.style.display = 'none'; editCourseSelect.value = ''; editCourseSelect.removeAttribute('required'); }
            if (editCourseNameSelect) { editCourseNameSelect.style.display = 'none'; editCourseNameSelect.value = ''; editCourseNameSelect.removeAttribute('required'); }
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
                if (clientSelect) { clientSelect.style.display = 'none'; clientSelect.removeAttribute('required'); clientSelect.value = ''; clientSelect.removeAttribute('name'); }
                if (otCourseSelect) { otCourseSelect.style.display = 'block'; otCourseSelect.setAttribute('required', 'required'); otCourseSelect.setAttribute('name', 'client_type_pk'); otCourseSelect.value = ''; }
                if (editCourseSelect) { editCourseSelect.style.display = 'none'; editCourseSelect.removeAttribute('required'); editCourseSelect.removeAttribute('name'); editCourseSelect.value = ''; }
                if (editCourseNameSelect) { editCourseNameSelect.style.display = 'none'; editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
                if (nameInput) { nameInput.style.display = 'none'; nameInput.readOnly = true; nameInput.placeholder = 'Select course above'; nameInput.value = ''; nameInput.removeAttribute('required'); }
            } else if (isCourse) {
                if (clientSelect) { clientSelect.style.display = 'none'; clientSelect.removeAttribute('required'); clientSelect.value = ''; clientSelect.removeAttribute('name'); }
                if (otCourseSelect) { otCourseSelect.style.display = 'none'; otCourseSelect.removeAttribute('required'); otCourseSelect.removeAttribute('name'); otCourseSelect.value = ''; }
                if (editCourseSelect) { editCourseSelect.style.display = 'block'; editCourseSelect.setAttribute('required', 'required'); editCourseSelect.setAttribute('name', 'client_type_pk'); editCourseSelect.value = ''; }
                if (editCourseNameSelect) { editCourseNameSelect.style.display = 'block'; editCourseNameSelect.setAttribute('required', 'required'); editCourseNameSelect.value = ''; }
                if (nameInput) { nameInput.style.display = 'none'; nameInput.value = ''; nameInput.removeAttribute('required'); }
            } else {
                if (clientSelect) { clientSelect.style.display = 'block'; clientSelect.setAttribute('required', 'required'); clientSelect.setAttribute('name', 'client_type_pk'); }
                if (otCourseSelect) { otCourseSelect.style.display = 'none'; otCourseSelect.removeAttribute('required'); otCourseSelect.removeAttribute('name'); otCourseSelect.value = ''; }
                if (editCourseSelect) { editCourseSelect.style.display = 'none'; editCourseSelect.removeAttribute('required'); editCourseSelect.removeAttribute('name'); editCourseSelect.value = ''; }
                if (editCourseNameSelect) { editCourseNameSelect.style.display = 'none'; editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
                if (clientSelect) {
                    clientSelect.querySelectorAll('option').forEach(function(opt) {
                        if (opt.value === '') { opt.hidden = false; return; }
                        opt.hidden = (opt.dataset.type || '') !== (this.value || '');
                    }.bind(this));
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
            const pk = this.value || '';
            const editCourseNameSelect = document.getElementById('editModalCourseNameSelect');
            const inp = document.getElementById('editModalClientNameInput');
            const courseName = (this.options[this.selectedIndex] && this.options[this.selectedIndex].textContent) ? this.options[this.selectedIndex].textContent.trim() : '';
            if (editCourseNameSelect) editCourseNameSelect.value = pk;
            if (inp) inp.value = courseName;
        });
    }
    
    const editModalCourseNameSelect = document.getElementById('editModalCourseNameSelect');
    if (editModalCourseNameSelect) {
        editModalCourseNameSelect.addEventListener('change', function() {
            const pk = this.value || '';
            const editCourseSelect = document.getElementById('editModalCourseSelect');
            const inp = document.getElementById('editModalClientNameInput');
            const courseName = (this.options[this.selectedIndex] && this.options[this.selectedIndex].textContent) ? this.options[this.selectedIndex].textContent.trim() : '';
            if (editCourseSelect) editCourseSelect.value = pk;
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
        const options = itemSubcategories.map(s =>
            '<option value="' + s.id + '" data-unit="' + (s.unit_measurement || '').replace(/"/g, '&quot;') + '" data-rate="' + (s.standard_cost || 0) + '"' + (item && item.item_subcategory_id == s.id ? ' selected' : '') + '>' + (s.item_name || '—').replace(/</g, '&lt;') + '</option>'
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
            '<td><input type="number" name="items[' + index + '][available_quantity]" class="form-control form-control-sm sv-avail bg-light" step="0.01" min="0" value="' + avail + '" placeholder="0" readonly></td>' +
            '<td><input type="number" name="items[' + index + '][quantity]" class="form-control form-control-sm sv-qty" step="0.01" min="0.01" placeholder="0" value="' + qty + '" required><div class="invalid-feedback">Issue Qty cannot exceed Available Qty.</div></td>' +
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

    // View button handler (use closest() for reliable single-click on DataTables rows)
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-view-sv');
        if (btn) {
            e.preventDefault();
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
                    const modal = new bootstrap.Modal(document.getElementById('viewSellingVoucherModal'));
                    modal.show();
                })
                .catch(err => { 
                    console.error('Error loading voucher:', err); 
                    alert('Failed to load selling voucher: ' + err.message); 
                });
        }
    });

    // Return button handler (use closest() for reliable single-click on DataTables rows)
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-return-sv');
        if (btn) {
            e.preventDefault();
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
                            '<td><input type="number" name="items[' + i + '][return_quantity]" class="form-control form-control-sm sv-return-qty" step="0.01" min="0" max="' + issuedQty + '" data-issued="' + issuedQty + '" value="' + retQty + '"><div class="invalid-feedback">Return Qty cannot exceed Issued Qty.</div></td>' +
                            '<td><input type="date" name="items[' + i + '][return_date]" class="form-control form-control-sm sv-return-date" ' + (issueDate ? ('min="' + issueDate + '" data-issue-date="' + issueDate + '"') : '') + ' value="' + retDate + '"><div class="invalid-feedback">Return date cannot be earlier than issue date.</div></td></tr>');
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
    });

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

    // Edit button handler (use closest() for reliable single-click on DataTables rows)
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-edit-sv');
        if (btn) {
            e.preventDefault();
            const voucherId = btn.getAttribute('data-voucher-id');
            if (!voucherId) {
                console.error('No voucher ID found for edit');
                return;
            }
            console.log('Loading edit data for voucher:', voucherId);
            fetch(editSvBaseUrl + '/' + voucherId + '/edit', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => {
                    if (!r.ok) throw new Error('HTTP error ' + r.status);
                    return r.json();
                })
                .then(data => {
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
                        if (editNameInp) { editNameInp.readOnly = true; editNameInp.placeholder = 'Select course above'; editNameInp.value = v.client_name || ''; editNameInp.removeAttribute('required'); }
                    } else if (isCourse) {
                        if (editClientSelect) { editClientSelect.style.display = 'none'; editClientSelect.removeAttribute('required'); editClientSelect.removeAttribute('name'); }
                        if (editOtSelect) { editOtSelect.style.display = 'none'; editOtSelect.removeAttribute('required'); editOtSelect.removeAttribute('name'); editOtSelect.value = ''; }
                        if (editCourseSelect) { editCourseSelect.style.display = 'block'; editCourseSelect.setAttribute('required', 'required'); editCourseSelect.setAttribute('name', 'client_type_pk'); editCourseSelect.value = v.client_type_pk || ''; }
                        if (editCourseNameSelect) { editCourseNameSelect.style.display = 'block'; editCourseNameSelect.setAttribute('required', 'required'); editCourseNameSelect.value = v.client_type_pk || ''; }
                        if (editNameInp) { editNameInp.style.display = 'none'; editNameInp.value = v.client_name || ''; editNameInp.removeAttribute('required'); }
                    } else {
                        if (editClientSelect) { editClientSelect.style.display = 'block'; editClientSelect.setAttribute('required', 'required'); editClientSelect.setAttribute('name', 'client_type_pk'); editClientSelect.querySelectorAll('option').forEach(function(opt) { if (opt.value === '') { opt.hidden = false; return; } opt.hidden = (opt.dataset.type || '') !== (v.client_type_slug || 'employee'); }); }
                        if (editOtSelect) { editOtSelect.style.display = 'none'; editOtSelect.removeAttribute('required'); editOtSelect.removeAttribute('name'); editOtSelect.value = ''; }
                        if (editCourseSelect) { editCourseSelect.style.display = 'none'; editCourseSelect.removeAttribute('required'); editCourseSelect.removeAttribute('name'); editCourseSelect.value = ''; }
                        if (editCourseNameSelect) { editCourseNameSelect.style.display = 'none'; editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
                        if (editNameInp) { editNameInp.style.display = 'block'; editNameInp.readOnly = false; editNameInp.placeholder = 'Client / section / role name'; editNameInp.setAttribute('required', 'required'); }
                    }
                    updateEditModalNameField();
                    updateEditGrandTotal();
                    updateEditRemoveButtons();
                    const modal = new bootstrap.Modal(document.getElementById('editSellingVoucherModal'));
                    modal.show();
                })
                .catch(err => { 
                    console.error('Error loading voucher for edit:', err); 
                    alert('Failed to load selling voucher: ' + err.message); 
                });
        }
    });

    const editModalAddItemRow = document.getElementById('editModalAddItemRow');
    if (editModalAddItemRow) {
        editModalAddItemRow.addEventListener('click', function() {
            const tbody = document.getElementById('editModalItemsBody');
            if (tbody) {
                tbody.insertAdjacentHTML('beforeend', getEditRowHtml(editRowIndex, null));
                editRowIndex++;
                updateEditRemoveButtons();
            }
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
                if (row) { enforceQtyWithinAvailable(row); calcRow(row); updateEditGrandTotal(); }
            }
        });
        
        editModalItemsBody.addEventListener('click', function(e) {
            if (e.target.classList.contains('sv-remove-row')) {
                const row = e.target.closest('.sv-item-row');
                if (row && document.querySelectorAll('#editModalItemsBody .sv-item-row').length > 1) {
                    row.remove();
                    updateEditGrandTotal();
                    updateEditRemoveButtons();
                }
            }
        });
    }

    // Reset add selling voucher modal when opened
    const addSellingVoucherModal = document.getElementById('addSellingVoucherModal');
    if (addSellingVoucherModal) {
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
                });
            } else {
                currentStoreId = null;
                filteredItems = itemSubcategories;
                if (storeSelect) storeSelect.value = '';
            }
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
    
    console.log('✅ All event listeners attached successfully');
    console.log('Script initialization complete');
});
</script>
@endsection
