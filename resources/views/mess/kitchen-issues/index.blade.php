@extends('admin.layouts.master')
@section('title', 'Material Management')
@section('setup_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Material Management</h4>
        <a href="{{ route('admin.mess.material-management.create') }}" class="btn btn-primary">Create Material Management</a>
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
                <div class="row">
                    <div class="col-md-3">
                        <label>Status</label>
                        <select name="status" class="form-select">
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
            @forelse($kitchenIssues as $issue)
                <tr>
                    <td>{{ $issue->pk }}</td>
                    <td>{{ $issue->request_date ? \Carbon\Carbon::parse($issue->request_date)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $issue->issue_date ? \Carbon\Carbon::parse($issue->issue_date)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $issue->storeMaster->store_name ?? 'N/A' }}</td>
                    <td>{{ $issue->quantity }}</td>
                    <td>
                        @if($issue->status == 'pending')
                            <span class="badge bg-warning">Pending</span>
                        @elseif($issue->status == 'approved')
                            <span class="badge bg-success">Approved</span>
                        @elseif($issue->status == 'issued')
                            <span class="badge bg-primary">Issued</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($issue->status) }}</span>
                        @endif
                    </td>
                    <td>
                        @if($issue->payment_type == 1)
                            <span class="badge bg-success">Paid</span>
                        @else
                            <span class="badge bg-danger">Unpaid</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.mess.material-management.show', $issue->pk) }}" class="btn btn-sm btn-info">View</a>
                        @if($issue->status == 'pending')
                            <a href="{{ route('admin.mess.material-management.edit', $issue->pk) }}" class="btn btn-sm btn-warning">Edit</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No records found</td>
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
                                            <option value="{{ $course->pk }}">{{ e($course->course_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="modalCourseSelect" class="form-select" style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                            <option value="{{ e($course->course_name) }}">{{ e($course->course_name) }}</option>
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
                                            <option value="{{ e($course->course_name) }}">{{ e($course->course_name) }}</option>
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
                                            <option value="{{ $store->id }}" {{ old('store_id') == $store->id ? 'selected' : '' }}>{{ $store->store_name }}</option>
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
                                            <option value="{{ $course->pk }}">{{ e($course->course_name) }}</option>
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
                                            <option value="{{ e($course->course_name) }}">{{ e($course->course_name) }}</option>
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
document.addEventListener('DOMContentLoaded', function() {
    console.log('Selling Voucher script loaded');
    console.log('Bootstrap available:', typeof bootstrap !== 'undefined');
    
    // Debug: Check if buttons exist
    const viewButtons = document.querySelectorAll('.btn-view-sv');
    const editButtons = document.querySelectorAll('.btn-edit-sv');
    const returnButtons = document.querySelectorAll('.btn-return-sv');
    console.log('Found buttons:', {
        view: viewButtons.length,
        edit: editButtons.length,
        return: returnButtons.length
    });
    
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
                if (row) { calcRow(row); updateGrandTotal(); }
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
                if (otCourseSelect) { otCourseSelect.style.display = 'block'; otCourseSelect.setAttribute('required', 'required'); otCourseSelect.value = ''; }
                if (otStudentSelect) { otStudentSelect.style.display = 'block'; otStudentSelect.innerHTML = '<option value="">Select course first</option>'; otStudentSelect.setAttribute('required', 'required'); otStudentSelect.value = ''; }
                if (courseSelect) { courseSelect.style.display = 'none'; courseSelect.removeAttribute('required'); courseSelect.value = ''; }
                if (courseNameSelect) { courseNameSelect.style.display = 'none'; courseNameSelect.removeAttribute('required'); courseNameSelect.value = ''; }
                if (nameInput) { nameInput.style.display = 'none'; nameInput.value = ''; nameInput.removeAttribute('required'); }
            } else if (isCourse) {
                if (clientSelect) { clientSelect.style.display = 'none'; clientSelect.removeAttribute('required'); clientSelect.value = ''; clientSelect.removeAttribute('name'); }
                if (otCourseSelect) { otCourseSelect.style.display = 'none'; otCourseSelect.removeAttribute('required'); otCourseSelect.value = ''; }
                if (otStudentSelect) { otStudentSelect.style.display = 'none'; otStudentSelect.removeAttribute('required'); otStudentSelect.innerHTML = '<option value="">Select Student</option>'; otStudentSelect.value = ''; }
                if (courseSelect) { courseSelect.style.display = 'block'; courseSelect.setAttribute('required', 'required'); courseSelect.value = ''; }
                if (courseNameSelect) { courseNameSelect.style.display = 'block'; courseNameSelect.setAttribute('required', 'required'); courseNameSelect.value = ''; }
                if (nameInput) { nameInput.style.display = 'none'; nameInput.value = ''; nameInput.removeAttribute('required'); }
            } else {
                if (clientSelect) { clientSelect.style.display = 'block'; clientSelect.setAttribute('required', 'required'); clientSelect.setAttribute('name', 'client_type_pk'); }
                if (otCourseSelect) { otCourseSelect.style.display = 'none'; otCourseSelect.removeAttribute('required'); otCourseSelect.value = ''; }
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
            nameInput.value = '';
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
            const val = this.value || '';
            const courseNameSelect = document.getElementById('modalCourseNameSelect');
            const inp = document.getElementById('modalClientNameInput');
            if (courseNameSelect) courseNameSelect.value = val;
            if (inp) inp.value = val;
        });
    }
    
    const modalCourseNameSelect = document.getElementById('modalCourseNameSelect');
    if (modalCourseNameSelect) {
        modalCourseNameSelect.addEventListener('change', function() {
            const val = this.value || '';
            const courseSelect = document.getElementById('modalCourseSelect');
            const inp = document.getElementById('modalClientNameInput');
            if (courseSelect) courseSelect.value = val;
            if (inp) inp.value = val;
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
                if (otCourseSelect) { otCourseSelect.style.display = 'block'; otCourseSelect.setAttribute('required', 'required'); otCourseSelect.value = ''; }
                if (editCourseSelect) { editCourseSelect.style.display = 'none'; editCourseSelect.removeAttribute('required'); editCourseSelect.value = ''; }
                if (editCourseNameSelect) { editCourseNameSelect.style.display = 'none'; editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
                if (nameInput) { nameInput.style.display = 'none'; nameInput.readOnly = true; nameInput.placeholder = 'Select course above'; nameInput.value = ''; nameInput.removeAttribute('required'); }
            } else if (isCourse) {
                if (clientSelect) { clientSelect.style.display = 'none'; clientSelect.removeAttribute('required'); clientSelect.value = ''; clientSelect.removeAttribute('name'); }
                if (otCourseSelect) { otCourseSelect.style.display = 'none'; otCourseSelect.removeAttribute('required'); otCourseSelect.value = ''; }
                if (editCourseSelect) { editCourseSelect.style.display = 'block'; editCourseSelect.setAttribute('required', 'required'); editCourseSelect.value = nameInput.value || ''; }
                if (editCourseNameSelect) { editCourseNameSelect.style.display = 'block'; editCourseNameSelect.setAttribute('required', 'required'); editCourseNameSelect.value = nameInput.value || ''; }
                if (nameInput) { nameInput.style.display = 'none'; nameInput.value = ''; nameInput.removeAttribute('required'); }
            } else {
                if (clientSelect) { clientSelect.style.display = 'block'; clientSelect.setAttribute('required', 'required'); clientSelect.setAttribute('name', 'client_type_pk'); }
                if (otCourseSelect) { otCourseSelect.style.display = 'none'; otCourseSelect.removeAttribute('required'); otCourseSelect.value = ''; }
                if (editCourseSelect) { editCourseSelect.style.display = 'none'; editCourseSelect.removeAttribute('required'); editCourseSelect.value = ''; }
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
            const inp = document.getElementById('editModalClientNameInput');
            if (inp) inp.value = this.value || '';
        });
    }
    
    const editModalCourseSelect = document.getElementById('editModalCourseSelect');
    if (editModalCourseSelect) {
        editModalCourseSelect.addEventListener('change', function() {
            const val = this.value || '';
            const editCourseNameSelect = document.getElementById('editModalCourseNameSelect');
            const inp = document.getElementById('editModalClientNameInput');
            if (editCourseNameSelect) editCourseNameSelect.value = val;
            if (inp) inp.value = val;
        });
    }
    
    const editModalCourseNameSelect = document.getElementById('editModalCourseNameSelect');
    if (editModalCourseNameSelect) {
        editModalCourseNameSelect.addEventListener('change', function() {
            const val = this.value || '';
            const editCourseSelect = document.getElementById('editModalCourseSelect');
            const inp = document.getElementById('editModalClientNameInput');
            if (editCourseSelect) editCourseSelect.value = val;
            if (inp) inp.value = val;
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

    // View button handler
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('btn-view-sv')) {
            e.preventDefault();
            const voucherId = e.target.getAttribute('data-voucher-id');
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

    // Return button handler
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('btn-return-sv')) {
            e.preventDefault();
            const voucherId = e.target.getAttribute('data-voucher-id');
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
                    const modal = new bootstrap.Modal(document.getElementById('returnItemModal'));
                    modal.show();
                })
                .catch(err => { 
                    console.error('Error loading return data:', err); 
                    alert('Failed to load return data: ' + err.message); 
                });
        }
    });

    // Edit button handler
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('btn-edit-sv')) {
            e.preventDefault();
            const voucherId = e.target.getAttribute('data-voucher-id');
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
                    if (editOtCourseEl) editOtCourseEl.value = v.client_name || '';
                    const editCourseEl = document.getElementById('editModalCourseSelect');
                    if (editCourseEl) editCourseEl.value = v.client_name || '';
                    const editCourseNameEl = document.getElementById('editModalCourseNameSelect');
                    if (editCourseNameEl) editCourseNameEl.value = v.client_name || '';
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
                    const editClientSelect = document.getElementById('editClientNameSelect');
                    const editOtSelect = document.getElementById('editModalOtCourseSelect');
                    const editNameInp = document.getElementById('editModalClientNameInput');
                    if (isOt) {
                        if (editClientSelect) { editClientSelect.style.display = 'none'; editClientSelect.removeAttribute('required'); editClientSelect.removeAttribute('name'); }
                        if (editOtSelect) { editOtSelect.style.display = 'block'; editOtSelect.setAttribute('required', 'required'); editOtSelect.value = v.client_name || ''; }
                        if (editNameInp) { editNameInp.readOnly = true; editNameInp.placeholder = 'Select course above'; editNameInp.value = v.client_name || ''; editNameInp.removeAttribute('required'); }
                    } else {
                        if (editClientSelect) { editClientSelect.style.display = 'block'; editClientSelect.setAttribute('required', 'required'); editClientSelect.setAttribute('name', 'client_type_pk'); editClientSelect.querySelectorAll('option').forEach(function(opt) { if (opt.value === '') { opt.hidden = false; return; } opt.hidden = (opt.dataset.type || '') !== (v.client_type_slug || 'employee'); }); }
                        if (editOtSelect) { editOtSelect.style.display = 'none'; editOtSelect.removeAttribute('required'); editOtSelect.value = ''; }
                        if (editNameInp) { editNameInp.readOnly = false; editNameInp.placeholder = 'Client / section / role name'; editNameInp.setAttribute('required', 'required'); }
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
                if (row) { calcRow(row); updateEditGrandTotal(); }
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
