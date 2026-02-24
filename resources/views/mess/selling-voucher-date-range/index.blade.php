@extends('admin.layouts.master')
@section('title', 'Selling Voucher with Date Range')
@section('setup_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Selling Voucher with Date Range</h4>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReportModal">ADD</button>
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
            <form method="GET" action="{{ route('admin.mess.selling-voucher-date-range.index') }}">
                <div class="row g-2">
                    <div class="col-md-2">
                        <label class="form-label small">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Draft</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Final</option>
                            <option value="2" {{ request('status') === '2' ? 'selected' : '' }}>Approved</option>
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
                    <div class="col-md-2 d-flex align-items-end gap-1">
                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                        <a href="{{ route('admin.mess.selling-voucher-date-range.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="sellingVoucherDateRangeTable">
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
                    <th style="color: #fff;">Issue Date</th>
                    <th style="color: #fff;">Status</th>
                    <th style="color: #fff;">Return Item</th>
                    <th style="color: #fff; min-width: 120px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @php $serial = 1; @endphp
                @forelse($reports as $report)
                    @forelse($report->items as $item)
                        <tr>
                            <td>{{ $serial++ }}</td>
                            <td>{{ $item->item_name ?: ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? '—') }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->return_quantity ?? 0 }}</td>
                            <td>{{ $report->resolved_store_name }}</td>
                            <td>{{ $report->clientTypeCategory ? ucfirst($report->clientTypeCategory->client_type ?? '') : ($report->client_type_slug ? ucfirst($report->client_type_slug) : '—') }}</td>
                            <td>{{ $report->display_client_name }}</td>
                            <td>{{ $report->client_name ?? '—' }}</td>
                            <td>{{ $report->payment_type == 1 ? 'Credit' : ($report->payment_type == 0 ? 'Cash' : ($report->payment_type == 2 ? 'Online' : '—')) }}</td>
                            <td>{{ $report->date_from ? $report->date_from->format('d/m/Y') : '—' }}</td>
                            <td>{{ $report->issue_date ? $report->issue_date->format('d/m/Y') : '—' }}</td>
                            <td>
                                @if($report->status == 0)<span class="badge bg-warning">Draft</span>
                                @elseif($report->status == 2)<span class="badge bg-success">Approved</span>
                                @elseif($report->status == 4)<span class="badge bg-primary">Completed</span>
                                @else<span class="badge bg-success">Final</span>@endif
                            </td>
                            <td>
                                @if(($item->return_quantity ?? 0) > 0)
                                    <span class="badge bg-info">Returned</span>
                                @endif
                                @if($loop->first)
                                    <button type="button" class="btn btn-sm btn-outline-secondary ms-1 btn-return-report" data-report-id="{{ $report->id }}" title="Return">Return</button>
                                @endif
                            </td>
                            <td>
                                @if($loop->first)
                                    <button type="button" class="btn btn-sm btn-info btn-view-report" data-report-id="{{ $report->id }}" title="View">View</button>
                                    <button type="button" class="btn btn-sm btn-warning btn-edit-report" data-report-id="{{ $report->id }}" title="Edit">Edit</button>
                                    <form action="{{ route('admin.mess.selling-voucher-date-range.destroy', $report->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this report?');" style="display: none;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete" style="display: none;">Delete</button>
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
                            <td>{{ $report->resolved_store_name }}</td>
                            <td>{{ $report->clientTypeCategory ? ucfirst($report->clientTypeCategory->client_type ?? '') : ($report->client_type_slug ? ucfirst($report->client_type_slug) : '—') }}</td>
                            <td>{{ $report->display_client_name }}</td>
                            <td>{{ $report->client_name ?? '—' }}</td>
                            <td>{{ $report->payment_type == 1 ? 'Credit' : ($report->payment_type == 0 ? 'Cash' : ($report->payment_type == 2 ? 'Online' : '—')) }}</td>
                            <td>{{ $report->date_from ? $report->date_from->format('d/m/Y') : '—' }}</td>
                            <td>{{ $report->issue_date ? $report->issue_date->format('d/m/Y') : '—' }}</td>
                            <td>
                                @if($report->status == 0)<span class="badge bg-warning">Draft</span>
                                @elseif($report->status == 2)<span class="badge bg-success">Approved</span>
                                @else<span class="badge bg-success">Final</span>@endif
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-return-report" data-report-id="{{ $report->id }}" title="Return">Return</button>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info btn-view-report" data-report-id="{{ $report->id }}" title="View">View</button>
                                <button type="button" class="btn btn-sm btn-warning btn-edit-report" data-report-id="{{ $report->id }}" title="Edit">Edit</button>
                                <form action="{{ route('admin.mess.selling-voucher-date-range.destroy', $report->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this report?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforelse
                @empty
                    <tr>
                        <td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                        <td class="text-center py-4">No reports found.</td>
                        <td></td><td></td><td></td><td></td><td></td><td></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('components.mess-master-datatables', [
        'tableId' => 'sellingVoucherDateRangeTable',
        'searchPlaceholder' => 'Search selling vouchers...',
        'ordering' => false,
        'actionColumnIndex' => 13,
        'infoLabel' => 'selling vouchers',
        'searchDelay' => 0
    ])
</div>

{{-- Add Report Modal --}}
<style>
#addReportModal .modal-dialog { max-height: calc(100vh - 2rem); margin: 1rem auto; }
#addReportModal .modal-content { max-height: calc(100vh - 2rem); display: flex; flex-direction: column; }
#addReportModal .modal-body { overflow-y: auto; max-height: calc(100vh - 10rem); }
</style>
<div class="modal fade" id="addReportModal" tabindex="-1" aria-labelledby="addReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form action="{{ route('admin.mess.selling-voucher-date-range.store') }}" method="POST" id="addReportForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="addReportModalLabel">ADD Selling Voucher with Date Range</h5>
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

                    {{-- Voucher Details (exactly same as Add Selling Voucher) --}}
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
                                                <input class="form-check-input dr-client-type-radio" type="radio" name="client_type_slug" id="dr_ct_{{ $slug }}" value="{{ $slug }}" {{ old('client_type_slug') === $slug ? 'checked' : '' }} required>
                                                <label class="form-check-label" for="dr_ct_{{ $slug }}">{{ $label }}</label>
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
                                    <small class="text-muted" id="drPaymentTypeHint">Employee / OT / Course: Credit only</small>
                                </div>
                                <div class="col-md-4" id="drClientNameWrap">
                                    <label class="form-label">Client Name <span class="text-danger">*</span></label>
                                    <select name="client_type_pk" class="form-select" id="drClientNameSelect">
                                        <option value="">Select Client Name</option>
                                        @foreach($clientNamesByType as $type => $list)
                                            @foreach($list as $c)
                                                <option value="{{ $c->id }}" data-type="{{ $c->client_type }}" data-client-name="{{ strtolower($c->client_name ?? '') }}">{{ $c->client_name }}</option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                    <select id="drOtCourseSelect" class="form-select" style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                            <option value="{{ $course->pk }}" data-course-name="{{ e($course->course_name) }}">{{ e($course->course_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="drCourseSelect" class="form-select" style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                            <option value="{{ $course->pk }}" data-course-name="{{ e($course->course_name) }}">{{ e($course->course_name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4" id="drNameFieldWrap">
                                    <label class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" name="client_name" id="drClientNameInput" class="form-control" value="{{ old('client_name') }}" placeholder="Client / section / role name" required>
                                    <select id="drFacultySelect" class="form-select" style="display:none;">
                                        <option value="">Select Faculty</option>
                                        @foreach($faculties ?? [] as $f)
                                            <option value="{{ e($f->full_name) }}">{{ e($f->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="drAcademyStaffSelect" class="form-select" style="display:none;">
                                        <option value="">Select Academy Staff</option>
                                        @foreach($employees ?? [] as $e)
                                            <option value="{{ e($e->full_name) }}">{{ e($e->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="drMessStaffSelect" class="form-select" style="display:none;">
                                        <option value="">Select Mess Staff</option>
                                        @foreach($messStaff ?? [] as $e)
                                            <option value="{{ e($e->full_name) }}">{{ e($e->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="drOtStudentSelect" class="form-select" style="display:none;">
                                        <option value="">Select Student</option>
                                    </select>
                                    <select id="drCourseNameSelect" class="form-select" style="display:none;">
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
                                    <select name="inve_store_master_pk" class="form-select" required>
                                        <option value="">Select Store</option>
                                        @foreach($stores as $store)
                                            <option value="{{ $store['id'] }}" {{ old('inve_store_master_pk') == $store['id'] ? 'selected' : '' }}>{{ $store['store_name'] }}</option>
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

                    {{-- Bill / Attachment (Upload) --}}
                    <div class="card mb-4 border-primary">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0 fw-semibold text-primary">Upload Bill (PDF / Image)</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <label class="form-label">Bill / Attachment <small class="text-muted">(Optional)</small></label>
                                    <input type="file" name="bill_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                                    <small class="text-muted d-block mt-1">PDF, JPG, JPEG, PNG or WEBP. Max 5 MB.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Item Details (exactly same as Add Selling Voucher) --}}
                    <div class="card mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                            <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addModalAddItemRow">+ Add Item</button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0" id="addReportItemsTable">
                                    <thead style="background-color: #af2910;">
                                        <tr>
                                            <th style="min-width: 180px; color: #fff; border-color: #af2910;">Item Name <span class="text-white">*</span></th>
                                            <th style="min-width: 80px; color: #fff; border-color: #af2910;">Unit</th>
                                            <th style="min-width: 100px; color: #fff; border-color: #af2910;">Available Qty</th>
                                            <th style="min-width: 90px; color: #fff; border-color: #af2910;">Issue Qty <span class="text-white">*</span></th>
                                            <th style="min-width: 90px; color: #fff; border-color: #af2910;">Left Qty</th>
                                            <th style="min-width: 120px; color: #fff; border-color: #af2910;">Issue Date</th>
                                            <th style="min-width: 100px; color: #fff; border-color: #af2910;">Rate <span class="text-white">*</span></th>
                                            <th style="min-width: 110px; color: #fff; border-color: #af2910;">Total Amount</th>
                                            <th style="width: 50px; color: #fff; border-color: #af2910;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="addModalItemsBody">
                                        <tr class="dr-item-row">
                                            <td>
                                                <select name="items[0][item_subcategory_id]" class="form-select form-select-sm dr-item-select" required>
                                                    <option value="">Select Item</option>
                                                    @foreach($itemSubcategories as $s)
                                                        <option value="{{ $s['id'] }}" data-unit="{{ e($s['unit_measurement'] ?? '') }}" data-rate="{{ e($s['standard_cost'] ?? 0) }}">{{ e($s['item_name'] ?? '—') }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="text" name="items[0][unit]" class="form-control form-control-sm dr-unit" readonly placeholder="—"></td>
                                            <td><input type="number" name="items[0][available_quantity]" class="form-control form-control-sm dr-avail bg-light" step="0.01" min="0" value="0" placeholder="0" readonly></td>
                                            <td>
                                                <input type="number" name="items[0][quantity]" class="form-control form-control-sm dr-qty" step="0.01" min="0.01" placeholder="0" required>
                                                <div class="invalid-feedback">Issue Qty cannot exceed Available Qty.</div>
                                            </td>
                                            <td><input type="text" class="form-control form-control-sm dr-left bg-light" readonly placeholder="0"></td>
                                            <td><input type="date" name="items[0][issue_date]" class="form-control form-control-sm dr-issue-date" value="{{ date('Y-m-d') }}"></td>
                                            <td><input type="number" name="items[0][rate]" class="form-control form-control-sm dr-rate" step="0.01" min="0" placeholder="0" required></td>
                                            <td><input type="text" class="form-control form-control-sm dr-total bg-light" readonly placeholder="0.00"></td>
                                            <td><button type="button" class="btn btn-sm btn-outline-danger dr-remove-row" disabled title="Remove">×</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-light d-flex justify-content-end align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-semibold">Grand Total:</span>
                                <span class="fs-5 text-primary fw-bold" id="addModalGrandTotal">₹0.00</span>
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

{{-- View Selling Voucher with Date Range Modal (same columns as Selling Voucher view modal + Issue Date) --}}
<style>
#viewReportModal .modal-dialog { max-height: calc(100vh - 2rem); margin: 1rem auto; }
#viewReportModal .modal-content { max-height: calc(100vh - 2rem); display: flex; flex-direction: column; background: #fff; color: #212529; }
#viewReportModal .modal-header { background: #f8f9fa !important; color: #212529 !important; }
#viewReportModal .modal-header * { color: #212529 !important; }
#viewReportModal .modal-title { color: #212529 !important; }
#viewReportModal .modal-body { overflow-y: auto; max-height: calc(100vh - 10rem); background: #fff; color: #212529 !important; }
#viewReportModal .modal-body *, #viewReportModal .modal-body p, #viewReportModal .modal-body span { color: inherit; }
#viewReportModal .card { background: #fff; color: #212529; }
#viewReportModal .card-header { background: #fff !important; color: #212529 !important; border-color: #dee2e6; }
#viewReportModal .card-header h6 { color: #0d6efd !important; }
#viewReportModal .card-body { background: #fff !important; color: #212529 !important; }
#viewReportModal .card-body table th { color: #495057 !important; font-weight: 600; }
#viewReportModal .card-body table td { color: #212529 !important; }
#viewReportModal .card-body .table-borderless th { background: transparent !important; }
#viewReportModal .card-body .table-borderless td { background: transparent !important; }
#viewReportModal #viewReportItemsCard .table thead th { color: #fff !important; background: #af2910 !important; border-color: #af2910; }
#viewReportModal #viewReportItemsCard .table tbody td { color: #212529 !important; background: #fff !important; }
#viewReportModal #viewReportGrandTotal { color: #212529 !important; }
#viewReportModal .text-muted { color: #495057 !important; }
#viewReportModal .card-footer { background: #f8f9fa !important; color: #212529 !important; }
#viewReportModal .card-footer strong { color: #212529 !important; }
#viewReportModal .badge { color: #212529 !important; }
#viewReportModal .modal-footer { background: #fff; border-color: #dee2e6; }
</style>
<div class="modal fade" id="viewReportModal" tabindex="-1" aria-labelledby="viewReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-semibold" id="viewReportModalLabel" style="color: #212529;">View Selling Voucher with Date Range</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Voucher Details (exactly same as Selling Voucher view modal) --}}
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
                        <p class="mb-0 mt-2" style="color: #212529;"><strong>Bill:</strong> <span id="viewBillWrap"><a href="#" id="viewBillLink" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary ms-1" style="display: none;">View / Download Bill</a><span id="viewBillNone" class="text-muted">No bill uploaded</span></span></p>
                    </div>
                </div>
                {{-- Item Details (same as Selling Voucher view modal + one extra column Issue Date) --}}
                <div class="card mb-4" id="viewReportItemsCard">
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
                                        <th style="color: #fff !important; border-color: #af2910;">Issue Date</th>
                                    </tr>
                                </thead>
                                <tbody id="viewReportItemsBody"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-light d-flex justify-content-end" style="color: #212529;">
                        <strong>Grand Total: ₹<span id="viewReportGrandTotal">0.00</span></strong>
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

{{-- Edit Report Modal --}}
<style>
#editReportModal .modal-dialog { max-height: calc(100vh - 2rem); margin: 1rem auto; }
#editReportModal .modal-content { max-height: calc(100vh - 2rem); display: flex; flex-direction: column; }
#editReportModal .modal-body { overflow-y: auto; max-height: calc(100vh - 10rem); }
</style>
<div class="modal fade" id="editReportModal" tabindex="-1" aria-labelledby="editReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form id="editReportForm" method="POST" action="" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="editReportModalLabel">Edit Selling Voucher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Voucher Details (exactly same as Edit Selling Voucher) --}}
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
                                                <input class="form-check-input edit-dr-client-type-radio" type="radio" name="client_type_slug" id="edit_dr_ct_{{ $slug }}" value="{{ $slug }}" required>
                                                <label class="form-check-label" for="edit_dr_ct_{{ $slug }}">{{ $label }}</label>
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
                                <div class="col-md-4" id="editDrClientNameWrap">
                                    <label class="form-label">Client Name <span class="text-danger">*</span></label>
                                    <select name="client_type_pk" class="form-select edit-client-type-pk" id="editDrClientNameSelect">
                                        <option value="">Select Client Name</option>
                                        @foreach($clientNamesByType as $type => $list)
                                            @foreach($list as $c)
                                                <option value="{{ $c->id }}" data-type="{{ $c->client_type }}" data-client-name="{{ strtolower($c->client_name ?? '') }}">{{ $c->client_name }}</option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                    <select id="editDrOtCourseSelect" class="form-select" style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                            <option value="{{ $course->pk }}" data-course-name="{{ e($course->course_name) }}">{{ e($course->course_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="editDrCourseSelect" class="form-select" style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                            <option value="{{ $course->pk }}" data-course-name="{{ e($course->course_name) }}">{{ e($course->course_name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4" id="editDrNameFieldWrap">
                                    <label class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" name="client_name" class="form-control edit-client-name" id="editDrClientNameInput" placeholder="Client / section / role name" required>
                                    <select id="editDrFacultySelect" class="form-select" style="display:none;">
                                        <option value="">Select Faculty</option>
                                        @foreach($faculties ?? [] as $f)
                                            <option value="{{ e($f->full_name) }}">{{ e($f->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="editDrAcademyStaffSelect" class="form-select" style="display:none;">
                                        <option value="">Select Academy Staff</option>
                                        @foreach($employees ?? [] as $e)
                                            <option value="{{ e($e->full_name) }}">{{ e($e->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="editDrMessStaffSelect" class="form-select" style="display:none;">
                                        <option value="">Select Mess Staff</option>
                                        @foreach($messStaff ?? [] as $e)
                                            <option value="{{ e($e->full_name) }}">{{ e($e->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="editDrOtStudentSelect" class="form-select" style="display:none;">
                                        <option value="">Select Student</option>
                                    </select>
                                    <select id="editDrCourseNameSelect" class="form-select" style="display:none;">
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
                                    <select name="inve_store_master_pk" class="form-select edit-store-id" required>
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
                    {{-- Bill / Attachment (Upload) --}}
                    <div class="card mb-4 border-primary">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0 fw-semibold text-primary">Upload Bill (PDF / Image)</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <label class="form-label">Bill / Attachment <small class="text-muted">(Optional – leave empty to keep existing)</small></label>
                                    <div class="d-flex align-items-center border rounded px-2 py-1 bg-white" style="min-height: 38px;">
                                        <span id="editSvCurrentBillPath" class="flex-grow-1 text-muted small text-break me-2" style="min-width: 0;">No file chosen</span>
                                        <label class="mb-0 btn btn-sm btn-outline-secondary py-1 px-2" style="cursor: pointer;">
                                            Choose file
                                            <input type="file" name="bill_file" class="d-none" accept=".pdf,.jpg,.jpeg,.png,.webp" id="editSvBillFileInput">
                                        </label>
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

<script>
(function() {
    let itemSubcategories = @json($itemSubcategories);
    let filteredItems = itemSubcategories;
    const baseUrl = "{{ url('admin/mess/selling-voucher-date-range') }}";
    // Match Selling Voucher behavior: only "Other" can choose Cash/Online (and Credit if enabled),
    // Employee/OT/Course should be Credit-only in the UI.
    const creditOnly = ['employee', 'ot', 'course'];
    let addRowIndex = 1;
    let editRowIndex = 0;
    let currentStoreId = null;
    let editCurrentStoreId = null;

    function enforceQtyWithinAvailable(row, availSelector, qtySelector) {
        if (!row) return;
        const availEl = row.querySelector(availSelector);
        const qtyEl = row.querySelector(qtySelector);
        if (!availEl || !qtyEl) return;

        const avail = parseFloat(availEl.value) || 0;
        const qtyRaw = qtyEl.value;
        const qty = parseFloat(qtyRaw);

        qtyEl.max = String(avail);

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
        
        fetch(baseUrl + '/store/' + storeId + '/items', {
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
        const rows = document.querySelectorAll('#addModalItemsBody .dr-item-row');
        rows.forEach(row => {
            const select = row.querySelector('.dr-item-select');
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
            
            updateAddRowUnit(row);
        });
    }

    function getAddRowHtml(index) {
        const options = filteredItems.map(s => {
            let attrs = 'data-unit="' + (s.unit_measurement || '').replace(/"/g, '&quot;') + '" data-rate="' + (s.standard_cost || 0) + '" data-available="' + (s.available_quantity || 0) + '"';
            if (s.price_tiers && s.price_tiers.length > 0) {
                attrs += ' data-price-tiers="' + (JSON.stringify(s.price_tiers) || '').replace(/"/g, '&quot;') + '"';
            }
            return '<option value="' + s.id + '" ' + attrs + '>' + (s.item_name || '—').replace(/</g, '&lt;') + '</option>';
        }).join('');
        return '<tr class="dr-item-row">' +
            '<td><select name="items[' + index + '][item_subcategory_id]" class="form-select form-select-sm dr-item-select" required><option value="">Select Item</option>' + options + '</select></td>' +
            '<td><input type="text" name="items[' + index + '][unit]" class="form-control form-control-sm dr-unit" readonly placeholder="—"></td>' +
            '<td><input type="number" name="items[' + index + '][available_quantity]" class="form-control form-control-sm dr-avail bg-light" step="0.01" min="0" value="0" placeholder="0" readonly></td>' +
            '<td><input type="number" name="items[' + index + '][quantity]" class="form-control form-control-sm dr-qty" step="0.01" min="0.01" placeholder="0" required><div class="invalid-feedback">Issue Qty cannot exceed Available Qty.</div></td>' +
            '<td><input type="text" class="form-control form-control-sm dr-left bg-light" readonly placeholder="0"></td>' +
            '<td><input type="date" name="items[' + index + '][issue_date]" class="form-control form-control-sm dr-issue-date" value="' + new Date().toISOString().slice(0, 10) + '"></td>' +
            '<td><input type="number" name="items[' + index + '][rate]" class="form-control form-control-sm dr-rate" step="0.01" min="0" placeholder="0" required></td>' +
            '<td><input type="text" class="form-control form-control-sm dr-total bg-light" readonly placeholder="0.00"></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger dr-remove-row" title="Remove">×</button></td>' +
            '</tr>';
    }

    function updateAddRowUnit(row) {
        const sel = row.querySelector('.dr-item-select');
        const opt = sel && sel.options[sel.selectedIndex];
        const unitInp = row.querySelector('.dr-unit');
        const rateInp = row.querySelector('.dr-rate');
        const availInp = row.querySelector('.dr-avail');
        if (unitInp) unitInp.value = (opt && opt.dataset.unit) ? opt.dataset.unit : '—';
        if (rateInp && opt && opt.dataset.rate) rateInp.value = opt.dataset.rate;
        if (availInp && opt && opt.dataset.available) availInp.value = opt.dataset.available;
        if (availInp) availInp.readOnly = true;
        enforceQtyWithinAvailable(row, '.dr-avail', '.dr-qty');
    }

    function updateAddRowLeft(row) {
        const avail = parseFloat(row.querySelector('.dr-avail').value) || 0;
        const qty = parseFloat(row.querySelector('.dr-qty').value) || 0;
        const leftInp = row.querySelector('.dr-left');
        if (leftInp) leftInp.value = Math.max(0, avail - qty).toFixed(2);
    }

    function calcDrFifoAmount(tiers, qty) {
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

    function updateAddRowTotal(row) {
        const qty = parseFloat(row.querySelector('.dr-qty').value) || 0;
        const rateInp = row.querySelector('.dr-rate');
        let rate = parseFloat(rateInp.value) || 0;
        const totalInp = row.querySelector('.dr-total');
        const sel = row.querySelector('.dr-item-select');
        const opt = sel && sel.options[sel.selectedIndex];
        const tiersJson = opt && opt.getAttribute('data-price-tiers');
        const tiers = tiersJson ? (function(){ try { return JSON.parse(tiersJson); } catch(e) { return null; } })() : null;
        let total;
        if (tiers && tiers.length > 0 && qty > 0) {
            const fifoAmount = calcDrFifoAmount(tiers, qty);
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
        if (totalInp) totalInp.value = (total || 0).toFixed(2);
        updateAddRowLeft(row);
        enforceQtyWithinAvailable(row, '.dr-avail', '.dr-qty');
    }

    function updateAddGrandTotal() {
        let sum = 0;
        document.querySelectorAll('#addModalItemsBody .dr-item-row').forEach(function(row) {
            const totalInp = row.querySelector('.dr-total');
            if (totalInp && totalInp.value) sum += parseFloat(totalInp.value);
        });
        document.getElementById('addModalGrandTotal').textContent = '₹' + sum.toFixed(2);
    }

    // Store selection change in ADD modal
    const addStoreSelect = document.querySelector('#addReportModal select[name="inve_store_master_pk"]');
    if (addStoreSelect) {
        addStoreSelect.addEventListener('change', function() {
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

    document.getElementById('addModalAddItemRow').addEventListener('click', function() {
        const tbody = document.getElementById('addModalItemsBody');
        const div = document.createElement('div');
        div.innerHTML = '<table><tbody>' + getAddRowHtml(addRowIndex) + '</tbody></table>';
        const newTr = div.querySelector('tr');
        tbody.appendChild(newTr);
        addRowIndex++;
        updateAddRowUnit(newTr);
        newTr.querySelector('.dr-avail').addEventListener('input', function() { updateAddRowLeft(newTr); });
        newTr.querySelector('.dr-qty').addEventListener('input', function() { updateAddRowTotal(newTr); updateAddGrandTotal(); });
        newTr.querySelector('.dr-rate').addEventListener('input', function() { updateAddRowTotal(newTr); updateAddGrandTotal(); });
        newTr.querySelector('.dr-item-select').addEventListener('change', function() { updateAddRowUnit(newTr); });
        newTr.querySelector('.dr-remove-row').addEventListener('click', function() {
            newTr.remove();
            updateAddGrandTotal();
            const rows = tbody.querySelectorAll('.dr-item-row');
            if (rows.length === 1) rows[0].querySelector('.dr-remove-row').disabled = true;
        });
        tbody.querySelectorAll('.dr-remove-row').forEach(function(btn) { btn.disabled = tbody.querySelectorAll('.dr-item-row').length <= 1; });
    });

    document.querySelectorAll('#addModalItemsBody .dr-item-row').forEach(function(row) {
        row.querySelector('.dr-item-select').addEventListener('change', function() { updateAddRowUnit(row); });
        row.querySelector('.dr-avail').addEventListener('input', function() { updateAddRowLeft(row); });
        row.querySelector('.dr-qty').addEventListener('input', function() { enforceQtyWithinAvailable(row, '.dr-avail', '.dr-qty'); updateAddRowTotal(row); updateAddGrandTotal(); });
        row.querySelector('.dr-rate').addEventListener('input', function() { updateAddRowTotal(row); updateAddGrandTotal(); });
    });

    document.getElementById('addModalItemsBody').addEventListener('click', function(e) {
        if (e.target.classList.contains('dr-remove-row')) {
            const row = e.target.closest('tr');
            if (row && document.getElementById('addModalItemsBody').querySelectorAll('.dr-item-row').length > 1) {
                row.remove();
                updateAddGrandTotal();
            }
        }
    });

    // Enter key inside Item Details table triggers Add Item (and prevents form submit)
    const addReportModalEl = document.getElementById('addReportModal');
    const addReportItemsTable = document.getElementById('addReportItemsTable');
    if (addReportModalEl && addReportItemsTable) {
        addReportModalEl.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && addReportItemsTable.contains(document.activeElement)) {
                const addBtn = document.getElementById('addModalAddItemRow');
                if (addBtn) {
                    e.preventDefault();
                    addBtn.click();
                }
            }
        });
    }

    // Add modal: Client Type + Client Name -> Name field (Faculty / Academy Staff / Mess Staff dropdown when Employee)
    function updateDrNameField() {
        const clientTypeRadio = document.querySelector('#addReportModal .dr-client-type-radio:checked');
        const clientNameSelect = document.getElementById('drClientNameSelect');
        const nameInput = document.getElementById('drClientNameInput');
        const facultySelect = document.getElementById('drFacultySelect');
        const academyStaffSelect = document.getElementById('drAcademyStaffSelect');
        const messStaffSelect = document.getElementById('drMessStaffSelect');
        const otStudentSelect = document.getElementById('drOtStudentSelect');
        const drCourseSelect = document.getElementById('drCourseSelect');
        const drCourseNameSelect = document.getElementById('drCourseNameSelect');
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
            if (drCourseSelect) { drCourseSelect.style.display = 'none'; drCourseSelect.value = ''; drCourseSelect.removeAttribute('required'); }
            if (drCourseNameSelect) { drCourseNameSelect.style.display = 'none'; drCourseNameSelect.value = ''; drCourseNameSelect.removeAttribute('required'); }
        } else if (isCourse) {
            nameInput.style.display = 'none';
            nameInput.removeAttribute('required');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) { if (sel) { sel.style.display = 'none'; sel.value = ''; sel.removeAttribute('required'); } });
            if (otStudentSelect) { otStudentSelect.style.display = 'none'; otStudentSelect.value = ''; otStudentSelect.removeAttribute('required'); }
            if (drCourseSelect) { drCourseSelect.style.display = 'block'; }
            if (drCourseNameSelect) { drCourseNameSelect.style.display = 'block'; }
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
            if (drCourseSelect) { drCourseSelect.style.display = 'none'; drCourseSelect.value = ''; drCourseSelect.removeAttribute('required'); }
            if (drCourseNameSelect) { drCourseNameSelect.style.display = 'none'; drCourseNameSelect.value = ''; drCourseNameSelect.removeAttribute('required'); }
            if (!showAny) nameInput.setAttribute('required', 'required');
        }
    }
    document.querySelectorAll('#addReportModal .dr-client-type-radio').forEach(function(radio) {
        radio.addEventListener('change', function() {
            // Payment Type: enforce Credit-only for Employee/OT/Course; allow selection for Other
            const paymentSelect = document.querySelector('#addReportModal select[name="payment_type"]');
            const hint = document.getElementById('drPaymentTypeHint');
            if (paymentSelect) {
                if (creditOnly.indexOf((this.value || '').toLowerCase()) !== -1) {
                    paymentSelect.value = '1';
                    paymentSelect.querySelectorAll('option').forEach(function(opt) {
                        opt.disabled = (opt.value !== '' && opt.value !== '1');
                    });
                    if (hint) hint.textContent = 'Credit only for this client type';
                } else {
                    paymentSelect.querySelectorAll('option').forEach(function(opt) { opt.disabled = false; });
                    if (hint) hint.textContent = 'Cash / Online / Credit';
                }
            }

            const isOt = (this.value || '').toLowerCase() === 'ot';
            const isCourse = (this.value || '').toLowerCase() === 'course';
            const clientSelect = document.getElementById('drClientNameSelect');
            const otCourseSelect = document.getElementById('drOtCourseSelect');
            const otStudentSelect = document.getElementById('drOtStudentSelect');
            const drCourseSelect = document.getElementById('drCourseSelect');
            const drCourseNameSelect = document.getElementById('drCourseNameSelect');
            const nameInput = document.getElementById('drClientNameInput');
            if (isOt) {
                if (clientSelect) { clientSelect.style.display = 'none'; clientSelect.removeAttribute('required'); clientSelect.value = ''; clientSelect.removeAttribute('name'); }
                if (otCourseSelect) { otCourseSelect.style.display = 'block'; otCourseSelect.setAttribute('required', 'required'); otCourseSelect.setAttribute('name', 'client_type_pk'); otCourseSelect.value = ''; }
                if (otStudentSelect) { otStudentSelect.style.display = 'block'; otStudentSelect.innerHTML = '<option value="">Select course first</option>'; otStudentSelect.setAttribute('required', 'required'); otStudentSelect.value = ''; }
                if (drCourseSelect) { drCourseSelect.style.display = 'none'; drCourseSelect.removeAttribute('required'); drCourseSelect.removeAttribute('name'); drCourseSelect.value = ''; }
                if (drCourseNameSelect) { drCourseNameSelect.style.display = 'none'; drCourseNameSelect.removeAttribute('required'); drCourseNameSelect.value = ''; }
                if (nameInput) { nameInput.style.display = 'none'; nameInput.value = ''; nameInput.removeAttribute('required'); }
            } else if (isCourse) {
                if (clientSelect) { clientSelect.style.display = 'none'; clientSelect.removeAttribute('required'); clientSelect.value = ''; clientSelect.removeAttribute('name'); }
                if (otCourseSelect) { otCourseSelect.style.display = 'none'; otCourseSelect.removeAttribute('required'); otCourseSelect.removeAttribute('name'); otCourseSelect.value = ''; }
                if (otStudentSelect) { otStudentSelect.style.display = 'none'; otStudentSelect.removeAttribute('required'); otStudentSelect.innerHTML = '<option value="">Select Student</option>'; otStudentSelect.value = ''; }
                if (drCourseSelect) { drCourseSelect.style.display = 'block'; drCourseSelect.setAttribute('required', 'required'); drCourseSelect.setAttribute('name', 'client_type_pk'); drCourseSelect.value = ''; }
                if (drCourseNameSelect) { drCourseNameSelect.style.display = 'block'; drCourseNameSelect.setAttribute('required', 'required'); drCourseNameSelect.value = ''; }
                if (nameInput) { nameInput.style.display = 'none'; nameInput.value = ''; nameInput.removeAttribute('required'); }
            } else {
                if (clientSelect) { clientSelect.style.display = 'block'; clientSelect.setAttribute('required', 'required'); clientSelect.setAttribute('name', 'client_type_pk'); }
                if (otCourseSelect) { otCourseSelect.style.display = 'none'; otCourseSelect.removeAttribute('required'); otCourseSelect.removeAttribute('name'); otCourseSelect.value = ''; }
                if (otStudentSelect) { otStudentSelect.style.display = 'none'; otStudentSelect.removeAttribute('required'); otStudentSelect.innerHTML = '<option value="">Select Student</option>'; otStudentSelect.value = ''; }
                if (drCourseSelect) { drCourseSelect.style.display = 'none'; drCourseSelect.removeAttribute('required'); drCourseSelect.value = ''; }
                if (drCourseNameSelect) { drCourseNameSelect.style.display = 'none'; drCourseNameSelect.removeAttribute('required'); drCourseNameSelect.value = ''; }
                if (clientSelect) {
                    clientSelect.querySelectorAll('option').forEach(function(opt) {
                        if (opt.value === '') { opt.hidden = false; return; }
                        opt.hidden = (opt.dataset.type || '') !== (this.value || '');
                    }.bind(this));
                }
                if (nameInput) { nameInput.style.display = 'block'; nameInput.placeholder = 'Client / section / role name'; nameInput.setAttribute('required', 'required'); }
            }
            updateDrNameField();
        });
    });
    document.getElementById('drOtCourseSelect').addEventListener('change', function() {
        const coursePk = this.value;
        const otStudentSelect = document.getElementById('drOtStudentSelect');
        const nameInput = document.getElementById('drClientNameInput');
        if (!otStudentSelect || !nameInput) return;
        otStudentSelect.innerHTML = '<option value="">Loading...</option>';
        otStudentSelect.value = '';
        const selectedOpt = this.options[this.selectedIndex];
        nameInput.value = (selectedOpt && selectedOpt.dataset.courseName) ? selectedOpt.dataset.courseName : '';
        if (!coursePk) {
            otStudentSelect.innerHTML = '<option value="">Select course first</option>';
            return;
        }
        fetch(baseUrl + '/students-by-course/' + coursePk, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
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
    document.getElementById('drOtStudentSelect').addEventListener('change', function() {
        const inp = document.getElementById('drClientNameInput');
        if (inp) inp.value = this.value || '';
    });
    document.getElementById('drCourseSelect').addEventListener('change', function() {
        const pk = this.value || '';
        const drCourseNameSelect = document.getElementById('drCourseNameSelect');
        const inp = document.getElementById('drClientNameInput');
        const courseName = (this.options[this.selectedIndex] && this.options[this.selectedIndex].textContent) ? this.options[this.selectedIndex].textContent.trim() : '';
        if (drCourseNameSelect) drCourseNameSelect.value = pk;
        if (inp) inp.value = courseName;
    });
    document.getElementById('drCourseNameSelect').addEventListener('change', function() {
        const pk = this.value || '';
        const drCourseSelect = document.getElementById('drCourseSelect');
        const inp = document.getElementById('drClientNameInput');
        const courseName = (this.options[this.selectedIndex] && this.options[this.selectedIndex].textContent) ? this.options[this.selectedIndex].textContent.trim() : '';
        if (drCourseSelect) drCourseSelect.value = pk;
        if (inp) inp.value = courseName;
    });
    document.getElementById('drClientNameSelect').addEventListener('change', updateDrNameField);
    document.getElementById('drFacultySelect').addEventListener('change', function() {
        const inp = document.getElementById('drClientNameInput');
        if (inp) inp.value = this.value || '';
    });
    const drAcademyStaffEl = document.getElementById('drAcademyStaffSelect');
    if (drAcademyStaffEl) drAcademyStaffEl.addEventListener('change', function() {
        const inp = document.getElementById('drClientNameInput');
        if (inp) inp.value = this.value || '';
    });
    const drMessStaffEl = document.getElementById('drMessStaffSelect');
    if (drMessStaffEl) drMessStaffEl.addEventListener('change', function() {
        const inp = document.getElementById('drClientNameInput');
        if (inp) inp.value = this.value || '';
    });
    const addChecked = document.querySelector('#addReportModal .dr-client-type-radio:checked');
    if (addChecked) addChecked.dispatchEvent(new Event('change'));

    // Edit modal: same Faculty / Academy Staff / Mess Staff dropdown logic
    function updateEditDrNameField() {
        const clientTypeRadio = document.querySelector('#editReportModal .edit-dr-client-type-radio:checked');
        const clientNameSelect = document.getElementById('editDrClientNameSelect');
        const nameInput = document.getElementById('editDrClientNameInput');
        const facultySelect = document.getElementById('editDrFacultySelect');
        const academyStaffSelect = document.getElementById('editDrAcademyStaffSelect');
        const messStaffSelect = document.getElementById('editDrMessStaffSelect');
        const editDrCourseSelect = document.getElementById('editDrCourseSelect');
        const editDrCourseNameSelect = document.getElementById('editDrCourseNameSelect');
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
            if (isCourse && editDrCourseSelect) { editDrCourseSelect.style.display = 'block'; }
            if (isCourse && editDrCourseNameSelect) { editDrCourseNameSelect.style.display = 'block'; }
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
            if (editDrCourseSelect) { editDrCourseSelect.style.display = 'none'; editDrCourseSelect.value = ''; editDrCourseSelect.removeAttribute('required'); }
            if (editDrCourseNameSelect) { editDrCourseNameSelect.style.display = 'none'; editDrCourseNameSelect.value = ''; editDrCourseNameSelect.removeAttribute('required'); }
            if (!showAny) nameInput.setAttribute('required', 'required');
        }
    }
    document.querySelectorAll('#editReportModal .edit-dr-client-type-radio').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const isOt = (this.value || '').toLowerCase() === 'ot';
            const isCourse = (this.value || '').toLowerCase() === 'course';
            const clientSelect = document.getElementById('editDrClientNameSelect');
            const otCourseSelect = document.getElementById('editDrOtCourseSelect');
            const otStudentSelect = document.getElementById('editDrOtStudentSelect');
            const editDrCourseSelect = document.getElementById('editDrCourseSelect');
            const editDrCourseNameSelect = document.getElementById('editDrCourseNameSelect');
            const nameInput = document.getElementById('editDrClientNameInput');
            if (isOt) {
                if (clientSelect) { clientSelect.style.display = 'none'; clientSelect.removeAttribute('required'); clientSelect.value = ''; clientSelect.removeAttribute('name'); }
                if (otCourseSelect) { otCourseSelect.style.display = 'block'; otCourseSelect.setAttribute('required', 'required'); otCourseSelect.setAttribute('name', 'client_type_pk'); otCourseSelect.value = ''; }
                if (otStudentSelect) { otStudentSelect.style.display = 'block'; otStudentSelect.innerHTML = '<option value="">Select course first</option>'; otStudentSelect.setAttribute('required', 'required'); otStudentSelect.value = ''; }
                if (editDrCourseSelect) { editDrCourseSelect.style.display = 'none'; editDrCourseSelect.removeAttribute('required'); editDrCourseSelect.removeAttribute('name'); editDrCourseSelect.value = ''; }
                if (editDrCourseNameSelect) { editDrCourseNameSelect.style.display = 'none'; editDrCourseNameSelect.removeAttribute('required'); editDrCourseNameSelect.value = ''; }
                if (nameInput) { nameInput.style.display = 'none'; nameInput.value = ''; nameInput.removeAttribute('required'); }
            } else if (isCourse) {
                if (clientSelect) { clientSelect.style.display = 'none'; clientSelect.removeAttribute('required'); clientSelect.value = ''; clientSelect.removeAttribute('name'); }
                if (otCourseSelect) { otCourseSelect.style.display = 'none'; otCourseSelect.removeAttribute('required'); otCourseSelect.removeAttribute('name'); otCourseSelect.value = ''; }
                if (otStudentSelect) { otStudentSelect.style.display = 'none'; otStudentSelect.removeAttribute('required'); otStudentSelect.innerHTML = '<option value="">Select Student</option>'; otStudentSelect.value = ''; }
                if (editDrCourseSelect) { editDrCourseSelect.style.display = 'block'; editDrCourseSelect.setAttribute('required', 'required'); editDrCourseSelect.setAttribute('name', 'client_type_pk'); editDrCourseSelect.value = ''; }
                if (editDrCourseNameSelect) { editDrCourseNameSelect.style.display = 'block'; editDrCourseNameSelect.setAttribute('required', 'required'); editDrCourseNameSelect.value = ''; }
                if (nameInput) { nameInput.style.display = 'none'; nameInput.value = ''; nameInput.removeAttribute('required'); }
            } else {
                if (clientSelect) { clientSelect.style.display = 'block'; clientSelect.setAttribute('required', 'required'); clientSelect.setAttribute('name', 'client_type_pk'); }
                if (otCourseSelect) { otCourseSelect.style.display = 'none'; otCourseSelect.removeAttribute('required'); otCourseSelect.removeAttribute('name'); otCourseSelect.value = ''; }
                if (otStudentSelect) { otStudentSelect.style.display = 'none'; otStudentSelect.removeAttribute('required'); otStudentSelect.innerHTML = '<option value="">Select Student</option>'; otStudentSelect.value = ''; }
                if (editDrCourseSelect) { editDrCourseSelect.style.display = 'none'; editDrCourseSelect.removeAttribute('required'); editDrCourseSelect.removeAttribute('name'); editDrCourseSelect.value = ''; }
                if (editDrCourseNameSelect) { editDrCourseNameSelect.style.display = 'none'; editDrCourseNameSelect.removeAttribute('required'); editDrCourseNameSelect.value = ''; }
                if (clientSelect) {
                    clientSelect.querySelectorAll('option').forEach(function(opt) {
                        if (opt.value === '') { opt.hidden = false; return; }
                        opt.hidden = (opt.dataset.type || '') !== (this.value || '');
                    }.bind(this));
                }
                if (nameInput) { nameInput.style.display = 'block'; nameInput.placeholder = 'Client / section / role name'; nameInput.setAttribute('required', 'required'); }
            }
            updateEditDrNameField();
        });
    });
    document.getElementById('editDrOtCourseSelect').addEventListener('change', function() {
        const coursePk = this.value;
        const otStudentSelect = document.getElementById('editDrOtStudentSelect');
        const nameInput = document.getElementById('editDrClientNameInput');
        if (!otStudentSelect || !nameInput) return;
        otStudentSelect.innerHTML = '<option value="">Loading...</option>';
        otStudentSelect.value = '';
        const selectedOpt = this.options[this.selectedIndex];
        nameInput.value = (selectedOpt && selectedOpt.dataset.courseName) ? selectedOpt.dataset.courseName : '';
        if (!coursePk) {
            otStudentSelect.innerHTML = '<option value="">Select course first</option>';
            return;
        }
        fetch(baseUrl + '/students-by-course/' + coursePk, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
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
    document.getElementById('editDrOtStudentSelect').addEventListener('change', function() {
        const inp = document.getElementById('editDrClientNameInput');
        if (inp) inp.value = this.value || '';
    });
    document.getElementById('editDrCourseSelect').addEventListener('change', function() {
        const pk = this.value || '';
        const editDrCourseNameSelect = document.getElementById('editDrCourseNameSelect');
        const inp = document.getElementById('editDrClientNameInput');
        const courseName = (this.options[this.selectedIndex] && this.options[this.selectedIndex].textContent) ? this.options[this.selectedIndex].textContent.trim() : '';
        if (editDrCourseNameSelect) editDrCourseNameSelect.value = pk;
        if (inp) inp.value = courseName;
    });
    document.getElementById('editDrCourseNameSelect').addEventListener('change', function() {
        const pk = this.value || '';
        const editDrCourseSelect = document.getElementById('editDrCourseSelect');
        const inp = document.getElementById('editDrClientNameInput');
        const courseName = (this.options[this.selectedIndex] && this.options[this.selectedIndex].textContent) ? this.options[this.selectedIndex].textContent.trim() : '';
        if (editDrCourseSelect) editDrCourseSelect.value = pk;
        if (inp) inp.value = courseName;
    });
    document.getElementById('editDrClientNameSelect').addEventListener('change', updateEditDrNameField);
    document.getElementById('editDrFacultySelect').addEventListener('change', function() {
        const inp = document.getElementById('editDrClientNameInput');
        if (inp) inp.value = this.value || '';
    });
    const editDrAcademyStaffEl = document.getElementById('editDrAcademyStaffSelect');
    if (editDrAcademyStaffEl) editDrAcademyStaffEl.addEventListener('change', function() {
        const inp = document.getElementById('editDrClientNameInput');
        if (inp) inp.value = this.value || '';
    });
    const editDrMessStaffEl = document.getElementById('editDrMessStaffSelect');
    if (editDrMessStaffEl) editDrMessStaffEl.addEventListener('change', function() {
        const inp = document.getElementById('editDrClientNameInput');
        if (inp) inp.value = this.value || '';
    });

    // Edit modal row helpers
    function getEditRowHtml(index, item) {
        item = item || {};
        const options = itemSubcategories.map(s =>
            '<option value="' + s.id + '" data-unit="' + (s.unit_measurement || '').replace(/"/g, '&quot;') + '" data-rate="' + (s.standard_cost || 0) + '"' + (item.item_subcategory_id == s.id ? ' selected' : '') + '>' + (s.item_name || '—').replace(/</g, '&lt;') + '</option>'
        ).join('');
        const avail = item.available_quantity != null ? item.available_quantity : '';
        const qty = item.quantity != null ? item.quantity : '';
        const rate = item.rate != null ? item.rate : '';
        const total = (qty && rate) ? (parseFloat(qty) * parseFloat(rate)).toFixed(2) : '';
        const left = (avail !== '' && qty !== '') ? Math.max(0, parseFloat(avail) - parseFloat(qty)).toFixed(2) : '';
        return '<tr class="edit-dr-item-row">' +
            '<td><select name="items[' + index + '][item_subcategory_id]" class="form-select form-select-sm edit-dr-item-select" required><option value="">Select Item</option>' + options + '</select></td>' +
            '<td><input type="text" name="items[' + index + '][unit]" class="form-control form-control-sm edit-dr-unit" readonly placeholder="—" value="' + (item.unit || '').replace(/"/g, '&quot;') + '"></td>' +
            '<td><input type="number" name="items[' + index + '][available_quantity]" class="form-control form-control-sm edit-dr-avail bg-light" step="0.01" min="0" value="' + avail + '" readonly></td>' +
            '<td><input type="number" name="items[' + index + '][quantity]" class="form-control form-control-sm edit-dr-qty" step="0.01" min="0.01" required value="' + qty + '"><div class="invalid-feedback">Issue Qty cannot exceed Available Qty.</div></td>' +
            '<td><input type="text" class="form-control form-control-sm edit-dr-left bg-light" readonly value="' + left + '"></td>' +
            '<td><input type="number" name="items[' + index + '][rate]" class="form-control form-control-sm edit-dr-rate" step="0.01" min="0" required value="' + rate + '"></td>' +
            '<td><input type="text" class="form-control form-control-sm edit-dr-total bg-light" readonly value="' + total + '"></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger edit-dr-remove-row" title="Remove">×</button></td>' +
            '</tr>';
    }

    function updateEditRowLeft(row) {
        const avail = parseFloat(row.querySelector('.edit-dr-avail').value) || 0;
        const qty = parseFloat(row.querySelector('.edit-dr-qty').value) || 0;
        const leftInp = row.querySelector('.edit-dr-left');
        if (leftInp) leftInp.value = Math.max(0, avail - qty).toFixed(2);
    }

    function updateEditRowTotal(row) {
        const qty = parseFloat(row.querySelector('.edit-dr-qty').value) || 0;
        const rate = parseFloat(row.querySelector('.edit-dr-rate').value) || 0;
        const totalInp = row.querySelector('.edit-dr-total');
        if (totalInp) totalInp.value = (qty * rate).toFixed(2);
        updateEditRowLeft(row);
        enforceQtyWithinAvailable(row, '.edit-dr-avail', '.edit-dr-qty');
    }

    function updateEditGrandTotal() {
        let sum = 0;
        document.querySelectorAll('#editModalItemsBody .edit-dr-item-row').forEach(function(row) {
            const totalInp = row.querySelector('.edit-dr-total');
            if (totalInp && totalInp.value) sum += parseFloat(totalInp.value);
        });
        document.getElementById('editModalGrandTotal').textContent = '₹' + sum.toFixed(2);
    }

    document.getElementById('editModalAddItemRow').addEventListener('click', function() {
        const tbody = document.getElementById('editModalItemsBody');
        const trContent = getEditRowHtml(editRowIndex, {});
        const div = document.createElement('div');
        div.innerHTML = '<table><tbody>' + trContent + '</tbody></table>';
        const newTr = div.querySelector('tr');
        tbody.appendChild(newTr);
        editRowIndex++;
        const sel = newTr.querySelector('.edit-dr-item-select');
        const opt = sel && sel.options[sel.selectedIndex];
        newTr.querySelector('.edit-dr-unit').value = (opt && opt.dataset.unit) ? opt.dataset.unit : '—';
        newTr.querySelector('.edit-dr-avail').addEventListener('input', function() { updateEditRowLeft(newTr); });
        newTr.querySelector('.edit-dr-qty').addEventListener('input', function() { enforceQtyWithinAvailable(newTr, '.edit-dr-avail', '.edit-dr-qty'); updateEditRowTotal(newTr); updateEditGrandTotal(); });
        newTr.querySelector('.edit-dr-rate').addEventListener('input', function() { updateEditRowTotal(newTr); updateEditGrandTotal(); });
        newTr.querySelector('.edit-dr-item-select').addEventListener('change', function() {
            const o = this.options[this.selectedIndex];
            newTr.querySelector('.edit-dr-unit').value = (o && o.dataset.unit) ? o.dataset.unit : '—';
        });
        newTr.querySelector('.edit-dr-remove-row').addEventListener('click', function() {
            newTr.remove();
            updateEditGrandTotal();
        });
    });

    document.getElementById('editModalItemsBody').addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-dr-remove-row')) {
            const row = e.target.closest('tr');
            if (row) {
                row.remove();
                updateEditGrandTotal();
            }
        }
    });

    // View report (mousedown ensures single-tap works with DataTables)
    document.addEventListener('mousedown', function(e) {
        const btn = e.target.closest('.btn-view-report');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        const reportId = btn.getAttribute('data-report-id');
            fetch(baseUrl + '/' + reportId, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(function(data) {
                    const v = data.voucher;
                    document.getElementById('viewReportModalLabel').textContent = 'View Selling Voucher with Date Range #' + (v.id || reportId);
                    document.getElementById('viewRequestDate').textContent = v.request_date || '—';
                    document.getElementById('viewIssueDate').textContent = v.issue_date || '—';
                    document.getElementById('viewStoreName').textContent = v.store_name || '—';
                    document.getElementById('viewReferenceNumber').textContent = v.reference_number || '—';
                    document.getElementById('viewOrderBy').textContent = v.order_by || '—';
                    document.getElementById('viewClientType').textContent = v.client_type || '—';
                    document.getElementById('viewClientName').textContent = (v.client_name_text || v.client_name || '—');
                    document.getElementById('viewPaymentType').textContent = v.payment_type || '—';
                    const statusEl = document.getElementById('viewStatus');
                    statusEl.innerHTML = v.status === 0 ? '<span class="badge bg-warning">Pending</span>' : (v.status === 2 ? '<span class="badge bg-success">Approved</span>' : (v.status === 4 ? '<span class="badge bg-primary">Completed</span>' : '<span class="badge bg-secondary">' + (v.status_label || v.status) + '</span>'));
                    if (v.remarks) {
                        document.getElementById('viewRemarksWrap').style.display = 'block';
                        document.getElementById('viewRemarks').textContent = v.remarks;
                    } else {
                        document.getElementById('viewRemarksWrap').style.display = 'none';
                    }
                    var viewBillLink = document.getElementById('viewBillLink');
                    var viewBillNone = document.getElementById('viewBillNone');
                    if (v.bill_url) {
                        viewBillLink.href = v.bill_url;
                        viewBillLink.style.display = '';
                        if (viewBillNone) viewBillNone.style.display = 'none';
                    } else {
                        viewBillLink.href = '#';
                        viewBillLink.style.display = 'none';
                        if (viewBillNone) viewBillNone.style.display = '';
                    }
                    const tbody = document.getElementById('viewReportItemsBody');
                    tbody.innerHTML = '';
                    if (data.has_items && data.items && data.items.length > 0) {
                        data.items.forEach(function(item) {
                            tbody.insertAdjacentHTML('beforeend', '<tr><td>' + (item.item_name || '—') + '</td><td>' + (item.unit || '—') + '</td><td>' + item.quantity + '</td><td>' + (item.return_quantity || 0) + '</td><td>₹' + item.rate + '</td><td>₹' + item.amount + '</td><td>' + (item.issue_date || '—') + '</td></tr>');
                        });
                        document.getElementById('viewReportGrandTotal').textContent = data.grand_total || '0.00';
                        document.getElementById('viewReportItemsCard').style.display = 'block';
                    } else {
                        document.getElementById('viewReportItemsCard').style.display = 'none';
                    }
                    document.getElementById('viewCreatedAt').textContent = v.created_at || '—';
                    if (v.updated_at) {
                        document.getElementById('viewUpdatedAtWrap').style.display = 'inline';
                        document.getElementById('viewUpdatedAt').textContent = v.updated_at;
                    } else {
                        document.getElementById('viewUpdatedAtWrap').style.display = 'none';
                    }
                    new bootstrap.Modal(document.getElementById('viewReportModal')).show();
                })
                .catch(err => { console.error(err); alert('Failed to load report.'); });
    }, true);

    // Return item modal (mousedown ensures single-tap works with DataTables)
    document.addEventListener('mousedown', function(e) {
        const btn = e.target.closest('.btn-return-report');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        const reportId = btn.getAttribute('data-report-id');
            fetch(baseUrl + '/' + reportId + '/return', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(function(data) {
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
                            '<td><input type="number" name="items[' + i + '][return_quantity]" class="form-control form-control-sm dr-return-qty" step="0.01" min="0" max="' + issuedQty + '" data-issued="' + issuedQty + '" value="' + retQty + '"><div class="invalid-feedback">Return Qty cannot exceed Issued Qty.</div></td>' +
                            '<td><input type="date" name="items[' + i + '][return_date]" class="form-control form-control-sm dr-return-date" ' + (issueDate ? ('min="' + issueDate + '" data-issue-date="' + issueDate + '"') : '') + ' value="' + retDate + '"><div class="invalid-feedback">Return date cannot be earlier than issue date.</div></td></tr>');
                    });
                    document.getElementById('returnItemForm').action = baseUrl + '/' + reportId + '/return';
                    new bootstrap.Modal(document.getElementById('returnItemModal')).show();
                })
                .catch(err => { console.error(err); alert('Failed to load return data.'); });
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
            if (e.target && e.target.classList.contains('dr-return-qty')) {
                enforceReturnQtyWithinIssued(e.target);
            }
            if (e.target && e.target.classList.contains('dr-return-date')) {
                enforceReturnDateNotBeforeIssue(e.target);
            }
        });
    }

    const returnItemForm = document.getElementById('returnItemForm');
    if (returnItemForm) {
        returnItemForm.addEventListener('submit', function(e) {
            this.querySelectorAll('.dr-return-qty').forEach(enforceReturnQtyWithinIssued);
            this.querySelectorAll('.dr-return-date').forEach(enforceReturnDateNotBeforeIssue);
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
            }
        }, true);
    }

    // Edit report (mousedown ensures single-tap works with DataTables)
    document.addEventListener('mousedown', function(e) {
        const btn = e.target.closest('.btn-edit-report');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        const reportId = btn.getAttribute('data-report-id');
            document.getElementById('editReportForm').action = baseUrl + '/' + reportId;
            fetch(baseUrl + '/' + reportId + '/edit', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(function(data) {
                    const v = data.voucher;
                    document.getElementById('editReportModalLabel').textContent = 'Edit Selling Voucher #' + (v.id || reportId);
                    document.querySelector('.edit-store-id').value = v.store_id || '';
                    document.querySelector('.edit-remarks').value = v.remarks || '';
                    const editRefNumEl = document.querySelector('.edit-reference-number');
                    if (editRefNumEl) editRefNumEl.value = v.reference_number || '';
                    const editOrderByEl = document.querySelector('.edit-order-by');
                    if (editOrderByEl) editOrderByEl.value = v.order_by || '';
                    var editSvBillPathEl = document.getElementById('editSvCurrentBillPath');
                    if (editSvBillPathEl) {
                        if (v.bill_path) {
                            var billFileName = v.bill_path.split('/').pop() || v.bill_path;
                            editSvBillPathEl.textContent = billFileName;
                            editSvBillPathEl.setAttribute('title', billFileName);
                        } else {
                            editSvBillPathEl.textContent = 'No file chosen';
                            editSvBillPathEl.removeAttribute('title');
                        }
                    }
                    var editSvBillFileInputEl = document.getElementById('editSvBillFileInput');
                    if (editSvBillFileInputEl) editSvBillFileInputEl.value = '';
                    var editBillLinkEl = document.getElementById('editCurrentBillLink');
                    if (editBillLinkEl) {
                        if (v.bill_url) {
                            editBillLinkEl.innerHTML = 'Current bill: <a href="' + v.bill_url + '" target="_blank" rel="noopener" class="text-primary">View Bill</a>';
                        } else {
                            editBillLinkEl.innerHTML = '';
                        }
                    }
                    document.getElementById('editDrClientNameInput').value = v.client_name || '';
                    document.getElementById('editDrFacultySelect').value = v.client_name || '';
                    const editAcademyEl = document.getElementById('editDrAcademyStaffSelect');
                    if (editAcademyEl) editAcademyEl.value = v.client_name || '';
                    const editMessEl = document.getElementById('editDrMessStaffSelect');
                    if (editMessEl) editMessEl.value = v.client_name || '';
                    const editOtCourseEl = document.getElementById('editDrOtCourseSelect');
                    if (editOtCourseEl) editOtCourseEl.value = v.client_type_pk || '';
                    const editDrCourseEl = document.getElementById('editDrCourseSelect');
                    if (editDrCourseEl) editDrCourseEl.value = v.client_type_pk || '';
                    const editDrCourseNameEl = document.getElementById('editDrCourseNameSelect');
                    if (editDrCourseNameEl) editDrCourseNameEl.value = v.client_type_pk || '';
                    document.querySelector('.edit-payment-type').value = String(v.payment_type ?? 1);
                    document.querySelector('.edit-issue-date').value = v.issue_date || '';
                    document.querySelector('.edit-client-type-pk').value = v.client_type_pk || '';
                    const slug = v.client_type_slug || 'employee';
                    document.querySelectorAll('.edit-dr-client-type-radio').forEach(function(radio) {
                        radio.checked = (radio.value === slug);
                    });
                    const isOt = slug === 'ot';
                    const isCourse = slug === 'course';
                    const editClientSelect = document.getElementById('editDrClientNameSelect');
                    const editOtSelect = document.getElementById('editDrOtCourseSelect');
                    const editCourseSelect = document.getElementById('editDrCourseSelect');
                    const editCourseNameSelect = document.getElementById('editDrCourseNameSelect');
                    const editNameInp = document.getElementById('editDrClientNameInput');
                    if (isOt) {
                        if (editClientSelect) { editClientSelect.style.display = 'none'; editClientSelect.removeAttribute('required'); editClientSelect.removeAttribute('name'); }
                        if (editOtSelect) { editOtSelect.style.display = 'block'; editOtSelect.setAttribute('required', 'required'); editOtSelect.setAttribute('name', 'client_type_pk'); editOtSelect.value = v.client_type_pk || ''; }
                        if (editCourseSelect) { editCourseSelect.style.display = 'none'; editCourseSelect.removeAttribute('required'); editCourseSelect.removeAttribute('name'); editCourseSelect.value = ''; }
                        if (editCourseNameSelect) { editCourseNameSelect.style.display = 'none'; editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
                        if (editNameInp) { editNameInp.style.display = 'none'; editNameInp.value = v.client_name || ''; editNameInp.removeAttribute('required'); }
                    } else if (isCourse) {
                        if (editClientSelect) { editClientSelect.style.display = 'none'; editClientSelect.removeAttribute('required'); editClientSelect.removeAttribute('name'); }
                        if (editOtSelect) { editOtSelect.style.display = 'none'; editOtSelect.removeAttribute('required'); editOtSelect.removeAttribute('name'); editOtSelect.value = ''; }
                        if (editCourseSelect) { editCourseSelect.style.display = 'block'; editCourseSelect.setAttribute('required', 'required'); editCourseSelect.setAttribute('name', 'client_type_pk'); editCourseSelect.value = v.client_type_pk || ''; }
                        if (editCourseNameSelect) { editCourseNameSelect.style.display = 'block'; editCourseNameSelect.setAttribute('required', 'required'); editCourseNameSelect.value = v.client_type_pk || ''; }
                        if (editNameInp) { editNameInp.style.display = 'none'; editNameInp.value = v.client_name || ''; editNameInp.removeAttribute('required'); }
                    } else {
                        if (editClientSelect) { editClientSelect.style.display = 'block'; editClientSelect.setAttribute('required', 'required'); editClientSelect.setAttribute('name', 'client_type_pk'); editClientSelect.querySelectorAll('option').forEach(function(opt) { if (opt.value === '') { opt.hidden = false; return; } opt.hidden = (opt.dataset.type || '') !== slug; }); }
                        if (editOtSelect) { editOtSelect.style.display = 'none'; editOtSelect.removeAttribute('required'); editOtSelect.removeAttribute('name'); editOtSelect.value = ''; }
                        if (editCourseSelect) { editCourseSelect.style.display = 'none'; editCourseSelect.removeAttribute('required'); editCourseSelect.removeAttribute('name'); editCourseSelect.value = ''; }
                        if (editCourseNameSelect) { editCourseNameSelect.style.display = 'none'; editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
                        if (editNameInp) { editNameInp.style.display = 'block'; editNameInp.readOnly = false; editNameInp.placeholder = 'Client / section / role name'; editNameInp.setAttribute('required', 'required'); }
                    }
                    updateEditDrNameField();
                    const tbody = document.getElementById('editModalItemsBody');
                    tbody.innerHTML = '';
                    editRowIndex = 0;
                    (data.items || []).forEach(function(item) {
                        tbody.insertAdjacentHTML('beforeend', getEditRowHtml(editRowIndex, item));
                        editRowIndex++;
                    });
                    if (tbody.querySelectorAll('.edit-dr-item-row').length === 0) {
                        tbody.insertAdjacentHTML('beforeend', getEditRowHtml(editRowIndex, {}));
                        editRowIndex++;
                    }
                    tbody.querySelectorAll('.edit-dr-item-row').forEach(function(row) {
                        row.querySelector('.edit-dr-avail').addEventListener('input', function() { updateEditRowLeft(row); });
                        row.querySelector('.edit-dr-qty').addEventListener('input', function() { updateEditRowTotal(row); updateEditGrandTotal(); });
                        row.querySelector('.edit-dr-rate').addEventListener('input', function() { updateEditRowTotal(row); updateEditGrandTotal(); });
                        row.querySelector('.edit-dr-item-select').addEventListener('change', function() {
                            const o = this.options[this.selectedIndex];
                            row.querySelector('.edit-dr-unit').value = (o && o.dataset.unit) ? o.dataset.unit : '—';
                        });
                        row.querySelector('.edit-dr-remove-row').addEventListener('click', function() {
                            row.remove();
                            updateEditGrandTotal();
                        });
                    });
                    updateEditGrandTotal();
                    new bootstrap.Modal(document.getElementById('editReportModal')).show();
                })
                .catch(err => { console.error(err); alert('Failed to load report for edit.'); });
    }, true);

    // Reset add modal when opened
    const addReportModal = document.getElementById('addReportModal');
    if (addReportModal) {
        addReportModal.addEventListener('show.bs.modal', function() {
            const storeSelect = addReportModal.querySelector('select[name="inve_store_master_pk"]');
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

    // Prevent double submit on Add form (stops double entry on Save Selling Voucher)
    var addReportFormEl = document.getElementById('addReportForm');
    if (addReportFormEl) {
        addReportFormEl.addEventListener('submit', function(e) {
            document.querySelectorAll('#addModalItemsBody .dr-item-row').forEach(function(row) {
                enforceQtyWithinAvailable(row, '.dr-avail', '.dr-qty');
            });
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
                return;
            }
        }, true);
        addReportFormEl.addEventListener('submit', function() {
            var btn = this.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.textContent = 'Saving...';
            }
        });
    }

    // Edit modal: show selected file name in same field when user picks a new bill
    var editSvBillFileInputEl = document.getElementById('editSvBillFileInput');
    if (editSvBillFileInputEl) {
        editSvBillFileInputEl.addEventListener('change', function() {
            var pathEl = document.getElementById('editSvCurrentBillPath');
            if (pathEl) pathEl.textContent = this.files && this.files[0] ? this.files[0].name : 'No file chosen';
        });
    }

    // Prevent double submit on Edit form
    var editReportFormEl = document.getElementById('editReportForm');
    if (editReportFormEl) {
        editReportFormEl.addEventListener('submit', function(e) {
            document.querySelectorAll('#editModalItemsBody .edit-dr-item-row').forEach(function(row) {
                enforceQtyWithinAvailable(row, '.edit-dr-avail', '.edit-dr-qty');
            });
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
                return;
            }
        }, true);
        editReportFormEl.addEventListener('submit', function() {
            var btn = this.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.textContent = 'Updating...';
            }
        });
    }

    // Open add modal on validation error
    @if(session('open_add_modal'))
    document.addEventListener('DOMContentLoaded', function() {
        var modal = new bootstrap.Modal(document.getElementById('addReportModal'));
        modal.show();
    });
    @endif
})();
</script>
@endsection
