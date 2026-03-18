@extends('admin.layouts.master')
@section('title', 'Process Mess Bills')
@section('setup_content')
<div class="container-fluid py-3 py-md-4 process-mess-bills-employee-report">
    <x-breadcrum title="Process Mess Bills"></x-breadcrum>
    {{-- Report Header (Print Only) --}}
    @php
        $dateFromDisplay = $effectiveDateFrom ?? now()->startOfMonth()->format('d-m-Y');
        $dateToDisplay = $effectiveDateTo ?? now()->endOfMonth()->format('d-m-Y');
        try {
            $dateFromDisplay = \Carbon\Carbon::parse($dateFromDisplay)->format('d-F-Y');
            $dateToDisplay = \Carbon\Carbon::parse($dateToDisplay)->format('d-F-Y');
        } catch (\Exception $e) {
            $dateFromDisplay = $effectiveDateFrom ?? '';
            $dateToDisplay = $effectiveDateTo ?? '';
        }
    @endphp
    <div class="report-header text-center mb-4">
        <h4 class="fw-bold">Process Mess Bills</h4>
        <p class="mb-1">Period: {{ $dateFromDisplay }} to {{ $dateToDisplay }}</p>
        <p class="text-muted mb-0 small">Generated on: {{ now()->format('d-m-Y H:i:s') }}</p>
    </div>

    {{-- Page header --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4 no-print p-4 rounded-3 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div>
            <h4 class="mb-2 fw-bold d-flex align-items-center gap-2">
                <i class="material-symbols-rounded" style="font-size: 2rem;">receipt_long</i>
                Process Mess Bills
            </h4>
            <p class="mb-0 small opacity-90">View mess bills for Employee, OT, Course & Other, generate invoices, and mark payments. Filter by date to see bills from Selling Voucher and Date Range reports.</p>
        </div>
        <button type="button" class="btn btn-light shadow d-inline-flex align-items-center gap-2 px-4" data-bs-toggle="modal" data-bs-target="#addProcessMessBillsModal" style="font-weight: 600;">
            <i class="material-symbols-rounded" style="font-size: 1.3rem;">add_circle</i>
            Generate Invoice
        </button>
    </div>

    {{-- Summary cards --}}
    <div class="no-print">
    @php $stats = $stats ?? ['total_bills' => 0, 'paid_count' => 0, 'unpaid_count' => 0, 'total_amount' => 0]; @endphp
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 mb-4 animate__animated animate__fadeIn">
        <div class="col">
            <div class="card border-0 shadow h-100 hover-lift transition-all">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                        <i class="material-symbols-rounded text-primary" style="font-size: 2rem;">description</i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold mb-1">Total Bills</div>
                        <div class="fs-3 fw-bold text-dark">{{ number_format($stats['total_bills']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 shadow h-100 hover-lift transition-all">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-warning bg-opacity-10 p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                        <i class="material-symbols-rounded text-warning" style="font-size: 2rem;">schedule</i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold mb-1">Unpaid</div>
                        <div class="fs-3 fw-bold text-dark">{{ number_format($stats['unpaid_count']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 shadow h-100 hover-lift transition-all">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success bg-opacity-10 p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                        <i class="material-symbols-rounded text-success" style="font-size: 2rem;">check_circle</i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold mb-1">Paid</div>
                        <div class="fs-3 fw-bold text-dark">{{ number_format($stats['paid_count']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 shadow h-100 hover-lift transition-all">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-info bg-opacity-10 p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                        <i class="material-symbols-rounded text-info" style="font-size: 2rem;">payments</i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold mb-1">Total Amount</div>
                        <div class="fs-3 fw-bold text-dark">₹ {{ number_format($stats['total_amount'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    {{-- Filters card --}}
    <div class="card border-0 shadow mb-4 no-print" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
        <div class="card-body p-3 p-lg-4">
            <form method="GET" action="{{ route('admin.mess.process-mess-bills-employee.index') }}" id="mainFilterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-dark mb-2"><i class="material-symbols-rounded align-middle me-1" style="font-size: 1.1rem;">event</i>Date From <span class="text-danger">*</span></label>
                        <input type="text" name="date_from" id="date_from" class="form-select"
                               value="{{ $effectiveDateFrom ?? request('date_from', now()->startOfMonth()->format('d-m-Y')) }}"
                               data-default-ymd="{{ $effectiveDateFromYmd ?? now()->startOfMonth()->format('Y-m-d') }}"
                               placeholder="dd-mm-yyyy" autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-dark mb-2"><i class="material-symbols-rounded align-middle me-1" style="font-size: 1.1rem;">event</i>Date To <span class="text-danger">*</span></label>
                        <input type="text" name="date_to" id="date_to" class="form-select"
                               value="{{ $effectiveDateTo ?? request('date_to', now()->endOfMonth()->format('d-m-Y')) }}"
                               data-default-ymd="{{ $effectiveDateToYmd ?? now()->endOfMonth()->format('Y-m-d') }}"
                               placeholder="dd-mm-yyyy" autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-dark mb-2"><i class="material-symbols-rounded align-middle me-1" style="font-size: 1.1rem;">person</i>Employee / OT / Course</label>
                        <select name="client_type" id="filterClientTypeSlug" class="form-select choices-select" data-placeholder="All client types">
                            <option value="">All Client Types</option>
                            @foreach($clientTypes ?? [] as $key => $label)
                                <option value="{{ $key }}" {{ ($clientType ?? request('client_type')) === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-dark mb-2"><i class="material-symbols-rounded align-middle me-1" style="font-size: 1.1rem;">category</i>Client Type</label>
                        <select name="client_type_pk" id="filterClientTypePk" class="form-select choices-select" data-placeholder="All">
                            <option value="">All</option>
                        </select>
                    </div>
                        <div class="col-md-3">
                        <label class="form-label small fw-semibold text-dark mb-2"><i class="material-symbols-rounded align-middle me-1" style="font-size: 1.1rem;">badge</i>Buyer Name</label>
                        <select name="buyer_name" id="filterBuyerName" class="form-select shadow-sm border-0 choices-select">
                            <option value="">All Buyers</option>
                            @if(($clientType ?? request('client_type')) === 'ot' && isset($otBuyerNames) && $otBuyerNames->isNotEmpty())
                                @foreach($otBuyerNames as $buyer)
                                    <option value="{{ $buyer }}" {{ ($buyerName ?? request('buyer_name')) === $buyer ? 'selected' : '' }}>{{ $buyer }}</option>
                                @endforeach
                            @elseif(($clientType ?? request('client_type')) === 'course' && isset($courseBuyerNames) && $courseBuyerNames->isNotEmpty())
                                @foreach($courseBuyerNames as $buyer)
                                    <option value="{{ $buyer }}" {{ ($buyerName ?? request('buyer_name')) === $buyer ? 'selected' : '' }}>{{ $buyer }}</option>
                                @endforeach
                            @elseif(($clientType ?? request('client_type')) === 'other' && isset($otherBuyerNames) && $otherBuyerNames->isNotEmpty())
                                @foreach($otherBuyerNames as $buyer)
                                    <option value="{{ $buyer }}" {{ ($buyerName ?? request('buyer_name')) === $buyer ? 'selected' : '' }}>{{ $buyer }}</option>
                                @endforeach
                            @elseif(($clientType ?? request('client_type')) === 'section' && isset($sectionBuyerNames) && $sectionBuyerNames->isNotEmpty())
                                @foreach($sectionBuyerNames as $buyer)
                                    <option value="{{ $buyer }}" {{ ($buyerName ?? request('buyer_name')) === $buyer ? 'selected' : '' }}>{{ $buyer }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Status</label>
                        @php $currentStatus = $statusFilter ?? request('status', ''); @endphp
                        <select name="status" id="filterStatus" class="form-select">
                            <option value="">All Status</option>
                            <option value="unpaid" {{ $currentStatus === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                            <option value="partial" {{ $currentStatus === 'partial' ? 'selected' : '' }}>Partial</option>
                            <option value="paid" {{ $currentStatus === 'paid' ? 'selected' : '' }}>Paid</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-primary  flex-grow-1">
                            <i class="material-symbols-rounded align-middle">filter_list</i>
                            Apply
                        </button>
                        @php
                            $clearFilterParams = [];
                        @endphp
                        <a href="{{ route('admin.mess.process-mess-bills-employee.index', $clearFilterParams) }}" class="btn btn-outline-secondary shadow-sm" title="Clear all filters">
                            <i class="material-symbols-rounded" style="font-size: 1.1rem;">filter_list_off</i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Table card – DataTables client-side search/sort like mess master --}}
    <div class="card border-0 shadow">
        <div class="card-body p-3 p-lg-4">
            <form method="GET" action="{{ route('admin.mess.process-mess-bills-employee.index') }}" id="filterForm" class="no-print">
                <input type="hidden" name="date_from" value="{{ $effectiveDateFrom ?? request('date_from') }}">
                <input type="hidden" name="date_to" value="{{ $effectiveDateTo ?? request('date_to') }}">
                <input type="hidden" name="client_type" value="{{ $clientType ?? request('client_type') }}">
                <input type="hidden" name="client_type_pk" value="{{ $clientTypePk ?? request('client_type_pk') }}">
                <input type="hidden" name="buyer_name" value="{{ $buyerName ?? request('buyer_name') }}">
                <input type="hidden" name="status" value="{{ $statusFilter ?? request('status') }}">
                <div class="d-flex flex-wrap justify-content-end align-items-right mb-3 gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('admin.mess.process-mess-bills-employee.export') }}?{{ http_build_query(request()->only(['date_from', 'date_to', 'client_type', 'client_type_pk', 'buyer_name', 'status', 'search'])) }}" class="btn btn-outline-success shadow-sm d-inline-flex align-items-center gap-2 px-3" title="Export to Excel">
                            <i class="material-symbols-rounded" style="font-size: 1.1rem;">file_download</i>
                            <span>Export</span>
                        </a>
                        <button type="button" class="btn btn-outline-primary shadow-sm d-inline-flex align-items-center gap-2 px-3" title="Print" onclick="printProcessMessBillsMainTable()">
                            <i class="material-symbols-rounded" style="font-size: 1.1rem;">print</i>
                            <span>Print</span>
                        </button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover text-nowrap align-middle mb-0" id="processMessBillsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-nowrap py-2">S.No.</th>
                            <th class="text-nowrap py-2">Buyer Name</th>
                            <th class="text-nowrap py-2">Slip No.</th>
                            <th class="text-nowrap py-2">Invoice Date</th>
                            <th class="text-nowrap py-2">Client Type</th>
                            <th class="text-nowrap py-2 text-end">Total</th>
                            <th class="text-nowrap py-2">Payment Type</th>
                            <th class="text-nowrap py-2">Status</th>
                            <th class="text-nowrap py-2 text-center no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($combinedBills ?? [] as $index => $cb)
                            <tr class="{{ ($cb->status ?? 0) == 2 ? '' : 'table-warning table-warning-subtle' }}">
                                <td>
                                    {{ (method_exists($combinedBills, 'firstItem') && !is_null($combinedBills->firstItem()))
                                        ? $combinedBills->firstItem() + $index
                                        : $index + 1 }}
                                </td>
                                <td>{{ $cb->buyer_name ?? '—' }}</td>
                                <td>{{ $cb->combined_invoice_no ?? '—' }}</td>
                                <td>{{ $cb->invoice_date_range ?? '—' }}</td>
                                <td>{{ $cb->client_type_display ?? '—' }}</td>
                                <td class="text-end fw-semibold">₹ {{ number_format($cb->total ?? 0, 2) }}</td>
                                <td>{{ $cb->payment_type ?? '—' }}</td>
                                <td>
                                    @if(($cb->status ?? 0) == 2)
                                        <span class="badge rounded-pill text-bg-success shadow-sm px-3 py-2">✓ Paid</span>
                                    @elseif(($cb->status ?? 0) == 1)
                                        <span class="badge rounded-pill text-bg-warning text-dark shadow-sm px-3 py-2">⏱ Partial</span>
                                    @else
                                        <span class="badge rounded-pill text-bg-secondary shadow-sm px-3 py-2">○ Unpaid</span>
                                    @endif
                                </td>
                                <td class="text-center no-print">
                                    <a href="{{ route('admin.mess.process-mess-bills-employee.print-receipt', ['id' => $cb->combined_id]) }}?date_from={{ urlencode($effectiveDateFromYmd ?? '') }}&date_to={{ urlencode($effectiveDateToYmd ?? '') }}" target="_blank"
                                       class="btn btn-sm btn-outline-primary shadow-sm d-inline-flex align-items-center justify-content-center gap-1 px-3" title="Print receipt ({{ $cb->combined_invoice_no ?? 'Invoice' }})">
                                        <i class="material-symbols-rounded" style="font-size: 1.1rem;">receipt</i>
                                        <span class="d-none d-sm-inline">Receipt</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="material-symbols-rounded d-block mb-3 text-primary" style="font-size: 4rem;">inbox</i>
                                    <div class="fw-semibold fs-5 mb-1">No bills found</div>
                                    <div class="small">Try adjusting your filters or date range</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('components.mess-master-datatables', [
    'tableId' => 'processMessBillsTable',
    'searchPlaceholder' => 'Search name or invoice no.',
    'orderColumn' => [[0, 'asc']],
    'actionColumnIndex' => 8,
    'infoLabel' => 'bills',
])

{{-- Toast container for feedback --}}
<div class="toast-container position-fixed bottom-0 end-0 p-3 no-print" id="processBillsToastContainer"></div>

{{-- Payment Details (Bill Receipt) Modal - shows when user clicks "Payment" --}}
<div class="modal fade" id="paymentDetailsModal" tabindex="-1" aria-labelledby="paymentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content bill-receipt-modal-content">
            <div class="modal-header border-0 py-2 align-items-start">
                <h5 class="modal-title fw-bold" id="paymentDetailsModalLabel">Bill Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bill-receipt-modal-body">
                <div id="paymentDetailsContent" class="bill-receipt-content">
                    <div class="text-center py-4 text-muted">Loading...</div>
                </div>
                <div class="bill-receipt-actions">
                    <button type="button" class="btn btn-receipt-pay" id="paymentDetailsPayNowBtn">
                        <i class="material-symbols-rounded align-middle" style="font-size: 1.1rem;">payments</i> Pay Now
                    </button>
                    <button type="button" class="btn btn-receipt-print" id="paymentDetailsPrintBtn">
                        <i class="material-symbols-rounded align-middle" style="font-size: 1.1rem;">print</i> Print
                    </button>
                    <button type="button" class="btn btn-receipt-cancel" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Pay Now (Payment Detail form) Modal - opens when user clicks "Pay Now" in Bill Receipt --}}
<div class="modal fade payment-detail-modal" id="payNowModal" tabindex="-1" aria-labelledby="payNowModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content payment-detail-modal-content">
            <div class="modal-header payment-detail-modal-header">
                <h5 class="modal-title payment-detail-modal-title" id="payNowModalLabel">Payment Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body payment-detail-modal-body">
                <form id="payNowForm">
                    @csrf
                    <div class="payment-detail-grid">
                        <div class="payment-detail-row">
                            <label class="payment-detail-label">Payment Mode</label>
                            <select name="payment_mode" id="payNowPaymentMode" class="payment-detail-input form-select  choices-select" data-placeholder="Select mode">
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                                <option value="deduct_from_salary">Deduct From Salary</option>
                                <option value="online">Online</option>
                            </select>
                            <div class="payment-detail-bank-wrap">
                                <label class="payment-detail-label">Bank Name</label>
                                <input type="text" name="bank_name" id="payNowBankName" class="payment-detail-input form-control " placeholder="Bank Name" autocomplete="off">
                            </div>
                        </div>
                        <div class="payment-detail-row payment-detail-cheque-row" id="payNowChequeRow">
                            <label class="payment-detail-label">Cheque Number</label>
                            <input type="text" name="cheque_number" id="payNowChequeNumber" class="payment-detail-input form-control " placeholder="Cheque Number" autocomplete="off">
                            <label class="payment-detail-label">Cheque Date</label>
                            <input type="text" name="cheque_date" id="payNowChequeDate" class="payment-detail-input form-control " value="{{ now()->format('d-m-Y') }}" placeholder="dd-mm-yyyy" autocomplete="off">
                        </div>
                        <div class="payment-detail-row">
                            <label class="payment-detail-label">Amount</label>
                            <input type="number" name="amount" id="payNowAmount" class="payment-detail-input form-control " step="0.01" min="0" required placeholder="0.00">
                            <label class="payment-detail-label">Payment Date</label>
                            <input type="text" name="payment_date" id="payNowPaymentDate" class="payment-detail-input form-control " value="{{ now()->format('d-m-Y') }}" placeholder="dd-mm-yyyy" autocomplete="off">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer payment-detail-modal-footer">
                <button type="button" class="btn payment-detail-save-btn" id="payNowSaveBtn">
                    <i class="material-symbols-rounded align-middle" style="font-size: 1.1rem;">save</i> Save
                </button>
                <button type="button" class="btn payment-detail-cancel-btn" data-bs-dismiss="modal">
                    <i class="material-symbols-rounded align-middle" style="font-size: 1.1rem;">close</i> Cancel
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Generate Invoice & Payment Modal --}}
<style>
/* Bill Receipt (Payment Details) modal – match reference design */
#paymentDetailsModal .modal-dialog { max-width: 720px; }
.bill-receipt-modal-content { border-radius: 8px; margin: 1rem auto; }
.bill-receipt-modal-body { padding: 1rem 1.5rem 1.5rem; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
.bill-receipt-content { color: #333; }
.bill-receipt-content .receipt-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; }
.bill-receipt-content .receipt-logo { display: inline-flex; align-items: center; gap: 0.35rem; }
.bill-receipt-content .receipt-logo-icon { width: 20px; height: 20px; background: linear-gradient(135deg, #c00 0%, #a00 50%, #800 100%); clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%); }
.bill-receipt-content .receipt-logo-text { font-size: 1.1rem; font-weight: 700; color: #0a3d6b; letter-spacing: 0.02em; }
.bill-receipt-content .receipt-date { font-size: 0.9rem; color: #555; }
.bill-receipt-content .receipt-center { text-align: center; margin: 1rem 0; padding: 0 0.5rem; }
.bill-receipt-content .receipt-title { font-size: 1.35rem; font-weight: 700; color: #0a3d6b; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 0.25rem; }
.bill-receipt-content .receipt-subtitle { font-size: 1rem; font-weight: 700; color: #c00; text-transform: uppercase; letter-spacing: 0.02em; margin-bottom: 0.5rem; }
.bill-receipt-content .receipt-period { font-size: 0.95rem; font-weight: 600; color: #0a3d6b; }
.bill-receipt-content hr { border: 0; border-top: 1px solid #333; margin: 0.75rem 0; opacity: 0.25; }
.bill-receipt-content .client-row { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; padding: 0.25rem 0; }
.bill-receipt-content .client-row .client-label { font-weight: 700; color: #333; }
.bill-receipt-content .client-row .client-value { font-weight: 500; }
.bill-receipt-content .bill-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; margin: 0.75rem 0; }
.bill-receipt-content .bill-table th, .bill-receipt-content .bill-table td { padding: 0.5rem 0.75rem; text-align: left; border-bottom: 1px solid #e0e0e0; }
.bill-receipt-content .bill-table th { font-weight: 700; color: #333; background: transparent; border-bottom: 1px solid #333; }
.bill-receipt-content .bill-table td { border-bottom: 1px solid #e8e8e8; }
.bill-receipt-content .bill-table .text-end { text-align: right; }
.bill-receipt-content .bill-table th.text-end { text-align: right; }
.bill-receipt-content .receipt-bottom { display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem; margin-top: 1rem; }
.bill-receipt-content .payment-summary { text-align: right; min-width: 200px; }
.bill-receipt-content .payment-summary .summary-row { display: flex; justify-content: flex-end; align-items: baseline; gap: 0.5rem; margin-bottom: 0.2rem; }
.bill-receipt-content .payment-summary .summary-label { font-weight: 600; color: #333; }
.bill-receipt-content .payment-summary .summary-value { font-weight: 500; min-width: 3rem; text-align: right; }
.bill-receipt-actions { margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #dee2e6; display: flex; gap: 0.75rem; flex-wrap: wrap; }
.bill-receipt-actions .btn { padding: 0.5rem 1.25rem; font-weight: 600; border-radius: 6px; border: none; font-size: 0.95rem; }
.bill-receipt-actions .btn-receipt-pay, .bill-receipt-actions .btn-receipt-print { background: linear-gradient(180deg, #0a6bb5 0%, #0a3d6b 100%); color: #fff; }
.bill-receipt-actions .btn-receipt-pay:hover, .bill-receipt-actions .btn-receipt-print:hover { background: linear-gradient(180deg, #0a5a9a 0%, #082d52 100%); color: #fff; }
.bill-receipt-actions .btn-receipt-cancel { background: #c00; color: #fff; }
.bill-receipt-actions .btn-receipt-cancel:hover { background: #a00; color: #fff; }

/* Payment Detail modal – match reference (two-column grid, Cheque fields, Save/Cancel style) */
.payment-detail-modal-content { border: 1px solid #333; border-radius: 8px; }
.payment-detail-modal-header { border-bottom: 1px solid #dee2e6; padding: 0.75rem 1rem; }
.payment-detail-modal-title { font-weight: 700; color: #0a3d6b; font-size: 1.1rem; text-transform: capitalize; }
.payment-detail-modal-body { padding: 1rem 1.25rem; }
.payment-detail-grid { display: flex; flex-direction: column; gap: 0.75rem; }
.payment-detail-row { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 0.5rem 0.75rem; align-items: center; }
.payment-detail-row .payment-detail-label { font-weight: 600; color: #333; font-size: 0.9rem; }
.payment-detail-row .payment-detail-input { background: #faf9f6; border: 1px solid #ccc; border-radius: 4px; padding: 0.4rem 0.5rem; font-size: 0.9rem; }
.payment-detail-row .payment-detail-input:focus { background: #fff; border-color: #0a3d6b; outline: none; }
.payment-detail-cheque-row { display: none; }
.payment-detail-modal.payment-mode-cheque .payment-detail-cheque-row { display: grid; }
.payment-detail-bank-wrap { display: none; grid-column: span 2; grid-template-columns: 1fr 1fr; gap: 0.5rem 0.75rem; align-items: center; }
.payment-detail-modal.payment-mode-cheque .payment-detail-bank-wrap { display: grid; }
.payment-detail-modal-footer { border-top: 1px solid #dee2e6; padding: 0.75rem 1rem; gap: 0.5rem; }
.payment-detail-save-btn { background: #198754; color: #fff; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 0.35rem; }
.payment-detail-save-btn:hover { background: #157347; color: #fff; }
.payment-detail-cancel-btn { background: #e9ecef; color: #495057; border: 1px solid #dee2e6; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 0.35rem; }
.payment-detail-cancel-btn:hover { background: #dee2e6; color: #212529; }
.payment-detail-cancel-btn i { color: #c00; }

#addProcessMessBillsModal .modal-dialog { max-height: calc(100vh - 2rem); margin: 1rem auto; }
#addProcessMessBillsModal .modal-content { max-height: calc(100vh - 2rem); display: flex; flex-direction: column; border-radius: 0.5rem; }
#addProcessMessBillsModal .modal-header { border-radius: 0.5rem 0.5rem 0 0; }
#addProcessMessBillsModal .modal-body { overflow-y: auto; max-height: calc(100vh - 10rem); }
#addProcessMessBillsModal .modal-footer { border-top: 1px solid var(--bs-border-color); }

/* Print styles */
@media screen {
    .report-header { display: none; }
}
@media print {
    .no-print { display: none !important; }
    .topbar,
    .side-mini-panel,
    aside.side-mini-panel,
    #mainSidebar,
    .sidebar-google-hamburger,
    #mainNavbar,
    header.topbar {
        display: none !important;
    }
    .page-wrapper { margin-left: 0 !important; padding-left: 0 !important; }
    .body-wrapper { margin-left: 0 !important; }
    .report-header {
        display: block !important;
        margin-top: 0;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #333;
    }
    .report-header h4 { margin-bottom: 8px; color: #000; font-weight: bold; }
    .report-header p { color: #333; font-size: 14px; margin: 4px 0; }
    html, body {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
        font-size: 11px;
    }
    .page-wrapper,
    .body-wrapper,
    .container-fluid.process-mess-bills-employee-report {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .process-mess-bills-employee-report .card,
    .process-mess-bills-employee-report .card-body {
        border: 0 !important;
        box-shadow: none !important;
    }
    .process-mess-bills-employee-report .table-responsive {
        overflow: visible !important;
    }
    .process-mess-bills-employee-report .table {
        width: 100% !important;
        table-layout: fixed;
        font-size: 10px;
    }
    .process-mess-bills-employee-report .table th,
    .process-mess-bills-employee-report .table td {
        padding: 4px !important;
        white-space: normal !important;
        word-break: break-word !important;
        overflow-wrap: anywhere !important;
        vertical-align: top !important;
    }
    /*
     * Let the browser choose orientation / scaling for printing instead of
     * forcing A4 landscape, which could crop the right or bottom content
     * on some printers. Slightly larger margins also help avoid clipping.
     */
    @page {
        margin: 12mm;
        size: auto;
    }
}

/* Modern UI/UX Enhancements */
.hover-lift {
    transition: all 0.3s ease;
}
.hover-lift:hover {
    transform: translateY(-4px);
    box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.15) !important;
}
.transition-all {
    transition: all 0.3s ease-in-out;
}
.animate__animated {
    animation-duration: 0.6s;
}
.animate__fadeIn {
    animation-name: fadeIn;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
}
.btn {
    transition: all 0.2s ease-in-out;
}
.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.15);
}
.btn:active {
    transform: translateY(0);
}
.badge {
    padding: 0.35em 0.65em;
    font-weight: 600;
}
.table tbody tr {
    transition: all 0.2s ease;
}
.table tbody tr:hover {
    background-color: rgba(13, 110, 253, 0.05);
}
.card {
    transition: all 0.3s ease;
}
</style>
<div class="modal fade" id="addProcessMessBillsModal" tabindex="-1" aria-labelledby="addProcessMessBillsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered modal-fullscreen-md-down">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-0 py-3">
                <h5 class="modal-title fw-semibold d-flex align-items-center gap-2" id="addProcessMessBillsModalLabel">
                    <span class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="material-symbols-rounded" style="font-size: 1.3rem;">receipt_long</i>
                    </span>
                    <span>Generate Invoice &amp; Process Payment</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-body-tertiary p-3 p-lg-4">
                <form id="addModalFilterForm" class="mb-3">
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-dark mb-2\"><i class=\"material-symbols-rounded align-middle me-1\" style=\"font-size: 1rem;\">event</i>Date From <span class="text-danger">*</span></label>
                            <input type="text" name="modal_date_from" id="modal_date_from" class="form-control "
                                   value="{{ now()->startOfMonth()->format('d-m-Y') }}" placeholder="dd-mm-yyyy" autocomplete="off" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-dark mb-2"><i class="material-symbols-rounded align-middle me-1" style="font-size: 1rem;">event</i>Date To <span class="text-danger">*</span></label>
                            <input type="text" name="modal_date_to" id="modal_date_to" class="form-control "
                                   value="{{ now()->endOfMonth()->format('d-m-Y') }}" placeholder="dd-mm-yyyy" autocomplete="off" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-dark mb-2"><i class="material-symbols-rounded align-middle me-1" style="font-size: 1rem;">person</i>Employee / OT / Course</label>
                            <select name="modal_client_type" id="modal_client_type" class="form-select shadow-sm border-0 choices-select" data-placeholder="All Client Types">
                                <option value="">All Client Types</option>
                                @foreach($clientTypes ?? [] as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-dark mb-2"><i class="material-symbols-rounded align-middle me-1" style="font-size: 1rem;">category</i>Client Type</label>
                            <select name="modal_client_type_pk" id="modal_client_type_pk" class="form-select choices-select" data-placeholder="All">
                                <option value="">All</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                        <label class="form-label small fw-semibold text-dark mb-2"><i class="material-symbols-rounded align-middle me-1" style="font-size: 1.1rem;">badge</i>Buyer Name</label>
                        <select name="modal_buyer_name" id="modal_buyer_name" class="form-select choices-select">
                            <option value="">All Buyers</option>
                            @if(($clientType ?? request('client_type')) === 'course' && isset($courseBuyerNames) && $courseBuyerNames->isNotEmpty())
                                @foreach($courseBuyerNames as $buyer)
                                    <option value="{{ $buyer }}" {{ ($buyerName ?? request('buyer_name')) === $buyer ? 'selected' : '' }}>{{ $buyer }}</option>
                                @endforeach
                            @elseif(($clientType ?? request('client_type')) === 'other' && isset($otherBuyerNames) && $otherBuyerNames->isNotEmpty())
                                @foreach($otherBuyerNames as $buyer)
                                    <option value="{{ $buyer }}" {{ ($buyerName ?? request('buyer_name')) === $buyer ? 'selected' : '' }}>{{ $buyer }}</option>
                                @endforeach
                            @elseif(($clientType ?? request('client_type')) === 'section' && isset($sectionBuyerNames) && $sectionBuyerNames->isNotEmpty())
                                @foreach($sectionBuyerNames as $buyer)
                                    <option value="{{ $buyer }}" {{ ($buyerName ?? request('buyer_name')) === $buyer ? 'selected' : '' }}>{{ $buyer }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-dark mb-2"><i class="material-symbols-rounded align-middle me-1" style="font-size: 1rem;">receipt</i>Invoice Date</label>
                            <input type="text" name="modal_invoice_date" id="modal_invoice_date" class="form-control"
                                   value="{{ now()->format('d-m-Y') }}" placeholder="dd-mm-yyyy" autocomplete="off">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-dark mb-2"><i class="material-symbols-rounded align-middle me-1" style="font-size: 1rem;">payments</i>Mode of Payment</label>
                            <select name="mode_of_payment" id="modal_mode_of_payment" class="form-select choices-select" data-placeholder="Select mode">
                                <option value="deduct_from_salary" selected>Deduct From Salary</option>
                                <option value="cash">Cash</option>
                                <option value="online">Online</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end justify-content-md-end">
                            <div class="d-flex flex-wrap gap-2 w-100 justify-content-start justify-content-md-end">
                            <button type="button" class="btn btn-primary shadow btn-sm d-inline-flex align-items-center gap-2 px-3" id="modalLoadBillsBtn">
                                <i class="material-symbols-rounded" style="font-size: 1rem;">search</i>
                                <span>Load Bills</span>
                            </button>
                            <button type="button" class="btn btn-outline-secondary shadow-sm btn-sm d-inline-flex align-items-center gap-2 px-3" id="modalClearFiltersBtn">
                                <i class="material-symbols-rounded" style="font-size: 1rem;">filter_list_off</i>
                                <span>Clear Filters</span>
                            </button>
                            </div>
                        </div>
                    </div>
                </form>

                {{-- Bulk actions (shown when rows selected) --}}
                <div class="d-none align-items-center gap-2 mb-3 p-3 rounded-3 bg-light border border-primary border-opacity-25" id="modalBulkActionsBar">
                    <span class="small fw-bold text-primary" id="modalSelectedCount">0 selected</span>
                    <button type="button" class="btn btn-sm btn-outline-primary shadow-sm" id="modalBulkInvoiceBtn">Generate Invoice (selected)</button>
                    <button type="button" class="btn btn-sm btn-outline-success shadow-sm" id="modalBulkPaymentBtn">Mark as Paid (selected)</button>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-muted fw-semibold">Show</span>
                        <select id="modalPerPage" class="form-select form-select-sm" style="width: auto;">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span class="small text-muted fw-semibold">entries</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group input-group-sm shadow-sm" style="width: 240px; max-width: 100%;">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="material-symbols-rounded text-muted" style="font-size: 1.1rem;">search</i>
                            </span>
                            <input type="text" id="modalSearch" class="form-control border-start-0" placeholder="Search bills...">
                        </div>
                        <button type="button" class="btn btn-outline-primary shadow-sm btn-sm d-inline-flex align-items-center gap-2 px-3" onclick="printProcessMessBillsTable()" title="Print bills list">
                            <i class="material-symbols-rounded align-middle" style="font-size: 1rem;">print</i>
                            <span>Print</span>
                        </button>
                    </div>
                </div>

                <div class="table-responsive rounded-3 border shadow-sm bg-white">
                <table id="modalBillsTable" class="table table-sm table-hover align-middle mb-0">
                        <thead style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                            <tr>
                                <th class="text-nowrap py-3 fw-semibold" style="width: 40px;"><input type="checkbox" id="modalSelectAll" class="form-check-input" title="Select all"></th>
                                <th class="text-nowrap py-3 fw-semibold">S.No.</th>
                                <th class="text-nowrap py-3 fw-semibold">Buyer Name</th>
                                <th class="text-nowrap py-3 fw-semibold">Invoice No.</th>
                                <th class="text-nowrap py-3 fw-semibold">Payment Type</th>
                                <th class="text-nowrap py-3 fw-semibold text-end">Total</th>
                                <th class="text-nowrap py-3 fw-semibold text-center">Actions</th>
                                <th class="text-nowrap py-3 fw-semibold text-center">Receipt</th>
                            </tr>
                        </thead>
                        <tbody id="modalBillsTableBody">
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="material-symbols-rounded d-block mb-2 text-primary" style="font-size: 3rem;">description</i>
                                    <div class="fw-semibold">Select date range and click <strong class="text-primary">Load Bills</strong> to load unpaid bills.</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-3 border-top">
                    <div class="small text-muted fw-semibold" id="modalPaginationInfo">Showing 0 to 0 of 0 entries</div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 rounded-bottom-3 py-3">
                <button type="button" class="btn btn-outline-secondary shadow-sm px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<style>
    .choices__list--dropdown {
        z-index: 2000;
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof flatpickr !== 'undefined') {
        var dateFromInput = document.getElementById('date_from');
        var dateToInput = document.getElementById('date_to');
        // Prefer data-default-ymd (Y-m-d from server) so nothing can overwrite before we read it.
        function ymdToDate(ymd) {
            if (!ymd || !/^\d{4}-\d{2}-\d{2}$/.test(String(ymd))) return null;
            var p = String(ymd).split('-');
            return new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
        }
        function dmyToDate(dmy) {
            var m = (dmy || '').match(/^(\d{1,2})-(\d{1,2})-(\d{4})$/);
            if (!m) return null;
            return new Date(parseInt(m[3], 10), parseInt(m[2], 10) - 1, parseInt(m[1], 10));
        }
        var defaultFrom = (dateFromInput && dateFromInput.getAttribute('data-default-ymd')) ? ymdToDate(dateFromInput.getAttribute('data-default-ymd')) : (dateFromInput && dateFromInput.value ? dmyToDate(dateFromInput.value) : null);
        var defaultTo = (dateToInput && dateToInput.getAttribute('data-default-ymd')) ? ymdToDate(dateToInput.getAttribute('data-default-ymd')) : (dateToInput && dateToInput.value ? dmyToDate(dateToInput.value) : null);
        var fpFrom = flatpickr('#date_from', {
            dateFormat: 'd-m-Y',
            allowInput: true,
            defaultDate: defaultFrom
        });
        var fpTo = flatpickr('#date_to', {
            dateFormat: 'd-m-Y',
            allowInput: true,
            defaultDate: defaultTo
        });
        if (defaultFrom && fpFrom) fpFrom.setDate(defaultFrom, false);
        if (defaultTo && fpTo) fpTo.setDate(defaultTo, false);
        flatpickr('#modal_date_from', { dateFormat: 'd-m-Y', allowInput: true });
        flatpickr('#modal_date_to', { dateFormat: 'd-m-Y', allowInput: true });
        flatpickr('#modal_invoice_date', { dateFormat: 'd-m-Y', allowInput: true });
        flatpickr('#payNowPaymentDate', { dateFormat: 'd-m-Y', allowInput: true });
        flatpickr('#payNowChequeDate', { dateFormat: 'd-m-Y', allowInput: true });
    }

    // Initialize Choices.js on all dropdowns within this report
    function initChoicesElement(el) {
        if (!el || typeof window.Choices === 'undefined') return;
        if (el.dataset.choicesInitialized === 'true') return;

        var placeholder = el.getAttribute('data-placeholder') || 'Select';
        var shouldSearch = (el.options && el.options.length > 10);

        var instance = new Choices(el, {
            searchEnabled: shouldSearch,
            removeItemButton: false,
            itemSelectText: '',
            shouldSort: false,
            placeholderValue: placeholder,
            allowHTML: false
        });

        el.dataset.choicesInitialized = 'true';
        el.choicesInstance = instance;
    }

    function refreshChoicesFromSelect(el, selectedValue) {
        console.log('refreshChoicesFromSelect called - el:', el, 'selectedValue:', selectedValue);
        console.log('choicesInstance exists?', !!el.choicesInstance);
        if (!el || !el.choicesInstance) {
            console.warn('No choicesInstance found for element:', el);
            return;
        }
        var instance = el.choicesInstance;
        var values = Array.from(el.options).map(function (o) {
            return { value: o.value, label: o.text, selected: selectedValue != null ? String(o.value) === String(selectedValue) : o.selected };
        });
        console.log('Refreshing choices with', values.length, 'options');
        instance.clearStore();
        instance.setChoices(values, 'value', 'label', true);
        try {
            instance.setChoiceByValue(selectedValue != null ? String(selectedValue) : (el.value || ''));
        } catch (e) {
            console.error('Error setting choice value:', e);
        }
    }

    if (typeof window.Choices !== 'undefined') {
        document
            .querySelectorAll('.process-mess-bills-employee-report select.choices-select')
            .forEach(function (el) {
                initChoicesElement(el);
            });
    }

    // Ensure modal dropdowns are (re)initialized with Choices when the modal opens
    var addProcessMessBillsModalEl = document.getElementById('addProcessMessBillsModal');
    if (addProcessMessBillsModalEl && typeof bootstrap !== 'undefined') {
        addProcessMessBillsModalEl.addEventListener('shown.bs.modal', function () {
            ['modal_client_type', 'modal_client_type_pk', 'modal_buyer_name', 'modal_mode_of_payment'].forEach(function (id) {
                var el = document.getElementById(id);
                initChoicesElement(el);
            });
        });
    }

    var modalBillsData = [];
    var paymentDetailsBillId = null;
    var paymentDetailsDateFrom = null;
    var paymentDetailsDateTo = null;
    var paymentDetailsUrl = '{{ route("admin.mess.process-mess-bills-employee.payment-details", ["id" => "__ID__"]) }}';
    var printReceiptBaseUrl = '{{ route("admin.mess.process-mess-bills-employee.print-receipt", ["id" => "__ID__"]) }}';
    var generateInvoiceBaseUrl = '{{ url("admin/mess/process-mess-bills-employee") }}';
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';

    function toYmd(val) {
        if (!val || !String(val).match(/^\d{1,2}-\d{1,2}-\d{4}$/)) return val;
        var p = String(val).split('-');
        return p[2] + '-' + p[1] + '-' + p[0];
    }

    function showToast(message, type) {
        type = type || 'success';
        var container = document.getElementById('processBillsToastContainer');
        var toastEl = document.createElement('div');
        toastEl.className = 'toast align-items-center text-bg-' + (type === 'error' ? 'danger' : 'success') + ' border-0 show';
        toastEl.setAttribute('role', 'alert');
        toastEl.innerHTML = '<div class="d-flex"><div class="toast-body">' + (message || 'Done') + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
        container.appendChild(toastEl);
        var t = new bootstrap.Toast(toastEl, { delay: 4000 });
        t.show();
        toastEl.addEventListener('hidden.bs.toast', function() { toastEl.remove(); });
    }

    function loadModalBills() {
        var df = document.getElementById('modal_date_from');
        var dt = document.getElementById('modal_date_to');
        var ct = document.getElementById('modal_client_type');
        var ctp = document.getElementById('modal_client_type_pk');
        var bn = document.getElementById('modal_buyer_name');
        var dateFrom = (df && df.value) ? toYmd(df.value) : '';
        var dateTo = (dt && dt.value) ? toYmd(dt.value) : '';
        var clientType = (ct && ct.value) ? ct.value : '';
        var clientTypePk = (ctp && ctp.value) ? ctp.value : '';
        var buyerName = (bn && bn.value) ? bn.value.trim() : '';
        var url = '{{ route("admin.mess.process-mess-bills-employee.modal-data") }}?date_from=' + encodeURIComponent(dateFrom) + '&date_to=' + encodeURIComponent(dateTo);
        if (clientType) url += '&client_type=' + encodeURIComponent(clientType);
        if (clientTypePk) url += '&client_type_pk=' + encodeURIComponent(clientTypePk);
        if (buyerName) url += '&buyer_name=' + encodeURIComponent(buyerName);
        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                modalBillsData = data.bills || [];
                renderModalTable();

                // Also refresh Buyer Name dropdown in modal based on loaded bills.
                // IMPORTANT: Only do this when no client type is selected, otherwise it
                // overrides the dependent "Client Type -> Buyer Name" behavior.
                if (clientType) {
                    return;
                }
                try {
                    var buyerSelect = document.getElementById('modal_buyer_name');
                    if (buyerSelect) {
                        var buyers = Array.from(new Set(
                            (modalBillsData || [])
                                .map(function (b) { return b.buyer_name || b.client_name || ''; })
                                .filter(function (name) { return !!name; })
                        ));

                        buyerSelect.innerHTML = '<option value=\"\">All Buyers</option>';

                        buyers.forEach(function (name) {
                            var opt = document.createElement('option');
                            opt.value = name;
                            opt.textContent = name;
                            buyerSelect.appendChild(opt);
                        });

                        if (buyerSelect.choicesInstance) {
                            var values = Array.from(buyerSelect.options).map(function (o) {
                                return { value: o.value, label: o.text, selected: o.selected };
                            });
                            buyerSelect.choicesInstance.clearStore();
                            buyerSelect.choicesInstance.setChoices(values, 'value', 'label', true);
                        }
                    }
                } catch (e) {
                    console.error('Failed to refresh modal_buyer_name options:', e);
                }
            })
            .catch(function() {
                modalBillsData = [];
                renderModalTable();
                showToast('Failed to load bills.', 'error');
            });
    }

    function getFilteredModalBills() {
        var search = (document.getElementById('modalSearch') || {}).value || '';
        search = String(search).toLowerCase().trim();
        return modalBillsData.filter(function(b) {
            if (!search) return true;
            return (b.buyer_name || '').toLowerCase().indexOf(search) >= 0 ||
                   String(b.invoice_no || '').indexOf(search) >= 0 ||
                   (b.payment_type || '').toLowerCase().indexOf(search) >= 0 ||
                   String(b.total || '').indexOf(search) >= 0;
        });
    }

    function renderModalTable() {
        var tbody = document.getElementById('modalBillsTableBody');
        var modalSelectAllEl = document.getElementById('modalSelectAll');
        if (modalSelectAllEl) modalSelectAllEl.checked = false;
        var filtered = getFilteredModalBills();
        var perPage = parseInt((document.getElementById('modalPerPage') || {}).value || 10, 10);
        var page = 1;
        var start = (page - 1) * perPage;
        var pageData = filtered.slice(start, start + perPage);

        if (pageData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No unpaid bills found. Adjust date range and click Load Bills.</td></tr>';
        } else {
            tbody.innerHTML = pageData.map(function(b, i) {
                var sn = start + i + 1;
                var printUrl = printReceiptBaseUrl.replace('__ID__', b.id);
                return '<tr class="' + (i % 2 === 0 ? 'table-light' : '') + '">' +
                    '<td><input type="checkbox" class="form-check-input modal-bill-check" data-id="' + b.id + '" data-name="' + (b.buyer_name || '').replace(/"/g, '&quot;') + '"></td>' +
                    '<td>' + sn + '</td>' +
                    '<td>' + (b.buyer_name || '—') + '</td>' +
                    '<td>' + (b.invoice_no || '—') + '</td>' +
                    '<td>' + (b.payment_type || '—') + '</td>' +
                    '<td class="text-end">' + (b.total || '0') + '</td>' +
                    '<td class="text-center"><div class="btn-group btn-group-sm">' +
                    '<button type="button" class="btn btn-outline-primary generate-invoice-btn" data-bill-id="' + b.id + '" data-buyer-name="' + (b.buyer_name || '').replace(/"/g, '&quot;') + '" title="Generate Invoice">Invoice</button>' +
                    '<button type="button" class="btn btn-outline-success generate-payment-btn" data-bill-id="' + b.id + '" data-buyer-name="' + (b.buyer_name || '').replace(/"/g, '&quot;') + '" title="Mark as Paid">Payment</button>' +
                    '</div></td>' +
                    '<td class="text-center"><a href="' + printUrl + '" target="_blank" class="btn  btn-outline-secondary" title="Print receipt"><i class="material-symbols-rounded" style="font-size:1.1rem;">receipt</i></a></td>' +
                    '</tr>';
            }).join('');
        }

        document.getElementById('modalPaginationInfo').textContent = 'Showing ' + (filtered.length ? start + 1 : 0) + ' to ' + Math.min(start + perPage, filtered.length) + ' of ' + filtered.length + ' entries';
        updateBulkActionsBar();
    }

    function updateBulkActionsBar() {
        var checked = document.querySelectorAll('#addProcessMessBillsModal .modal-bill-check:checked');
        var bar = document.getElementById('modalBulkActionsBar');
        var countEl = document.getElementById('modalSelectedCount');
        if (!bar || !countEl) return;
        if (checked.length === 0) {
            bar.classList.add('d-none');
        } else {
            bar.classList.remove('d-none');
            countEl.textContent = checked.length + ' selected';
        }
    }

    function clearModalFilters() {
        // Reset all filter inputs to defaults, then reload bills so table shows unfiltered (default) data
        var defaultDateFrom = '{{ now()->startOfMonth()->format("d-m-Y") }}';
        var defaultDateTo = '{{ now()->endOfMonth()->format("d-m-Y") }}';
        var defaultInvoiceDate = '{{ now()->format("d-m-Y") }}';

        function setDateInput(id, value) {
            var el = document.getElementById(id);
            if (!el) return;
            el.value = value;
            if (el._flatpickr) el._flatpickr.setDate(value, false);
        }
        setDateInput('modal_date_from', defaultDateFrom);
        setDateInput('modal_date_to', defaultDateTo);
        setDateInput('modal_invoice_date', defaultInvoiceDate);

        var ct = document.getElementById('modal_client_type');
        if (ct) {
            ct.value = '';
            if (ct.choicesInstance) {
                ct.choicesInstance.setChoiceByValue('');
            }
        }
        var ctp = document.getElementById('modal_client_type_pk');
        if (ctp) {
            ctp.innerHTML = '<option value=\"\">All</option>';
            if (ctp.choicesInstance) {
                ctp.choicesInstance.clearStore();
                ctp.choicesInstance.setChoices([{ value: '', label: 'All', selected: true }], 'value', 'label', true);
            }
        }
        var bn = document.getElementById('modal_buyer_name');
        if (bn) {
            bn.innerHTML = '<option value=\"\">All Buyers</option>';
            if (bn.choicesInstance) {
                bn.choicesInstance.clearStore();
                bn.choicesInstance.setChoices([{ value: '', label: 'All Buyers', selected: true }], 'value', 'label', true);
            }
        }
        var mp = document.getElementById('modal_mode_of_payment');
        if (mp) {
            mp.value = 'deduct_from_salary';
            if (mp.choicesInstance) {
                mp.choicesInstance.setChoiceByValue('deduct_from_salary');
            }
        }
        var ms = document.getElementById('modalSearch');
        if (ms) ms.value = '';

        loadModalBills();
    }

    document.getElementById('addProcessMessBillsModal').addEventListener('show.bs.modal', function() { loadModalBills(); });
    document.getElementById('modalLoadBillsBtn').addEventListener('click', loadModalBills);
    document.getElementById('modalClearFiltersBtn').addEventListener('click', clearModalFilters);
    document.getElementById('modalSearch').addEventListener('input', renderModalTable);
    document.getElementById('modalPerPage').addEventListener('change', renderModalTable);

    // --- Client Type / Buyer dependent dropdowns in modal (similar to Sale Voucher Report) ---
    (function initModalClientTypeFilters() {
        var modalClientType = document.getElementById('modal_client_type');
        var modalClientTypePk = document.getElementById('modal_client_type_pk');
        var modalBuyerName = document.getElementById('modal_buyer_name');
        var studentsByCourseUrl = "{{ url('/admin/mess/selling-voucher-date-range/students-by-course') }}";
        var buyersForReportUrl = "{{ route('admin.mess.reports.category-wise-print-slip.buyers') }}";

        if (!modalClientType || !modalClientTypePk || !modalBuyerName) {
            return;
        }

        var clientTypeOptions = {
@foreach($clientTypes ?? [] as $key => $label)
    '{{ $key }}': [
        @if(isset($clientTypeCategories[$key]))
            @foreach($clientTypeCategories[$key] as $category)
                { value: '{{ $category->id }}', text: '{{ addslashes($category->client_name) }}', dataClientName: '{{ strtolower($category->client_name ?? '') }}' },
            @endforeach
        @endif
    ],
@endforeach
        };

        var otCourseOptions = [
@if(isset($otCourses))
    @foreach($otCourses as $course)
            { value: '{{ $course->pk }}', text: '{{ addslashes($course->course_name) }}' },
    @endforeach
@endif
        ];

        var employeeNames = {
            'academy staff': [
@foreach($employees ?? [] as $e)
                { value: '{{ addslashes($e->full_name) }}', text: '{{ addslashes($e->full_name) }}' },
@endforeach
            ],
            'faculty': [
@foreach($faculties ?? [] as $f)
                { value: '{{ addslashes($f->full_name) }}', text: '{{ addslashes($f->full_name) }}' },
@endforeach
            ],
            'mess staff': [
@foreach($messStaff ?? [] as $m)
                { value: '{{ addslashes($m->full_name) }}', text: '{{ addslashes($m->full_name) }}' },
@endforeach
            ]
        };

        var otBuyerNames = {!! json_encode(($otBuyerNames ?? collect())->values()->all(), JSON_UNESCAPED_UNICODE) !!};
        var courseBuyerNames = {!! json_encode(($courseBuyerNames ?? collect())->values()->all(), JSON_UNESCAPED_UNICODE) !!};
        var otherBuyerNames = {!! json_encode(($otherBuyerNames ?? collect())->values()->all(), JSON_UNESCAPED_UNICODE) !!};
        var sectionBuyerNames = {!! json_encode(($sectionBuyerNames ?? collect())->values()->all(), JSON_UNESCAPED_UNICODE) !!};
        var allBuyerNames = {!! json_encode(($allBuyerNames ?? collect())->values()->all(), JSON_UNESCAPED_UNICODE) !!};

        // NOTE: Choices.js may recreate <option> nodes and drop custom dataset attributes.
        // Keep an explicit mapping from client_type_pk -> client group key (academy staff/faculty/mess staff)
        // so Buyer Name filtering stays correct inside the modal.
        var modalPkToClientGroupKey = {};

        function fillModalClientTypePk() {
            var slug = modalClientType.value;
            modalClientTypePk.innerHTML = '<option value=\"\">All</option>';

            var choicesPk = modalClientTypePk.choicesInstance || null;
            if (choicesPk) {
                choicesPk.clearStore();
                choicesPk.setChoices([{ value: '', label: 'All', selected: true }], 'value', 'label', true);
            }

            modalPkToClientGroupKey = {};

            if ((slug === 'ot' || slug === 'course') && otCourseOptions.length) {
                otCourseOptions.forEach(function (o) {
                    var opt = document.createElement('option');
                    opt.value = o.value;
                    opt.textContent = o.text;
                    modalClientTypePk.appendChild(opt);
                });
            } else if (slug && clientTypeOptions[slug]) {
                clientTypeOptions[slug].forEach(function (o) {
                    var opt = document.createElement('option');
                    opt.value = o.value;
                    opt.textContent = o.text;
                    if (o.dataClientName) {
                        opt.dataset.clientName = o.dataClientName;
                        modalPkToClientGroupKey[String(o.value)] = String(o.dataClientName);
                    }
                    modalClientTypePk.appendChild(opt);
                });
            }

            if (choicesPk) {
                var newChoices = Array.from(modalClientTypePk.options).map(function (o) {
                    return { value: o.value, label: o.text, selected: o.selected };
                });
                choicesPk.clearStore();
                choicesPk.setChoices(newChoices, 'value', 'label', true);
            }
            fillModalBuyerNames();
        }

        function fillModalBuyerNames() {
            var slug = modalClientType.value;
            var selectedPk = modalClientTypePk.value;
            modalBuyerName.innerHTML = '<option value=\"\">All Buyers</option>';

            var choicesBuyer = modalBuyerName.choicesInstance || null;
            if (choicesBuyer) {
                choicesBuyer.clearStore();
                choicesBuyer.setChoices([{ value: '', label: 'All Buyers', selected: true }], 'value', 'label', true);
            }

            function addBuyerOptions(list) {
                (list || []).forEach(function (o) {
                    var opt = document.createElement('option');
                    opt.value = o.value;
                    opt.textContent = o.text;
                    modalBuyerName.appendChild(opt);
                });
            }

            if (slug === 'employee') {
                var selectedOpt = modalClientTypePk.options[modalClientTypePk.selectedIndex];
                var dataClientName = '';
                if (selectedPk && modalPkToClientGroupKey[String(selectedPk)]) {
                    dataClientName = modalPkToClientGroupKey[String(selectedPk)];
                } else if (selectedOpt && selectedOpt.dataset && selectedOpt.dataset.clientName) {
                    dataClientName = selectedOpt.dataset.clientName || '';
                }

                if (dataClientName && employeeNames[dataClientName] && employeeNames[dataClientName].length) {
                    addBuyerOptions(employeeNames[dataClientName]);
                } else if (!selectedPk) {
                    // No subgroup selected: show all employee groups
                    Object.keys(employeeNames || {}).forEach(function (key) {
                        addBuyerOptions(employeeNames[key] || []);
                    });
                }
            } else if (slug === 'ot' && selectedPk) {
                // OT + specific course: students by course
                fetch(studentsByCourseUrl + '/' + selectedPk, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        var students = (data.students || []).map(function (s) {
                            return { value: s.display_name || '', text: s.display_name || '—' };
                        });
                        addBuyerOptions(students);
                    })
                    .catch(function () {
                        // ignore error; All Buyers hi rahe
                    });
            } else if (slug === 'ot' && !selectedPk) {
                // OT + All: use distinct OT buyer names if available
                var listOt = (otBuyerNames || []).map(function (name) {
                    return { value: name, text: name };
                });
                addBuyerOptions(listOt);
            } else if (slug === 'course') {
                // Requirement (modal):
                // - Course selected + Client Type = All => Buyer Name = ALL course names
                // - Course selected + specific Client Type PK => Buyer Name = that course name only
                var courses = (otCourseOptions || []);
                if (!selectedPk) {
                    var listCourseAll = courses.map(function (o) {
                        return { value: o.text, text: o.text };
                    });
                    addBuyerOptions(listCourseAll);
                } else {
                    var matchCourse = courses.find(function (o) {
                        return String(o.value) === String(selectedPk);
                    });
                    if (matchCourse) {
                        addBuyerOptions([{ value: matchCourse.text, text: matchCourse.text }]);
                    }
                }
            } else if (slug === 'other') {
                // Other: use distinct buyer names list
                var listOther = (otherBuyerNames || []).map(function (name) {
                    return { value: name, text: name };
                });
                addBuyerOptions(listOther);
            } else if (slug === 'section') {
                // Requirement (modal):
                // - Section selected + Client Type = All => Buyer Name = ALL section names
                // - Section selected + specific Client Type PK => Buyer Name = that section only
                var sectionOptions = clientTypeOptions['section'] || [];
                if (!selectedPk) {
                    var listSectionAll = sectionOptions.map(function (o) {
                        return { value: o.text, text: o.text };
                    });
                    addBuyerOptions(listSectionAll);
                } else {
                    var matchSection = sectionOptions.find(function (o) {
                        return String(o.value) === String(selectedPk);
                    });
                    if (matchSection) {
                        addBuyerOptions([{ value: matchSection.text, text: matchSection.text }]);
                    }
                }
            } else if (!slug && (allBuyerNames || []).length) {
                // koi client type select nahi – saare distinct buyer names (course/other/section etc.) dikhado
                var listAll = (allBuyerNames || []).map(function (name) {
                    return { value: name, text: name };
                });
                addBuyerOptions(listAll);
            }

            if (choicesBuyer) {
                var newChoices = Array.from(modalBuyerName.options).map(function (o) {
                    return { value: o.value, label: o.text, selected: o.selected };
                });
                choicesBuyer.clearStore();
                choicesBuyer.setChoices(newChoices, 'value', 'label', true);
            }
        }

        modalClientType.addEventListener('change', fillModalClientTypePk);
        modalClientTypePk.addEventListener('change', fillModalBuyerNames);

        // Initial fill
        fillModalClientTypePk();
    })();

    // --- Main "Process Mess Bills" filters – Employee / OT / Course + Client Type + Buyer Name ---
    (function initMainClientTypeFilters() {
        var clientTypeSlug = document.getElementById('filterClientTypeSlug');
        var clientTypePk = document.getElementById('filterClientTypePk');
        var buyerSelect = document.getElementById('filterBuyerName');
        var studentsByCourseUrl = "{{ url('/admin/mess/selling-voucher-date-range/students-by-course') }}";
        var buyersForReportUrl = "{{ route('admin.mess.reports.category-wise-print-slip.buyers') }}";
        var preservedClientTypePk = {!! json_encode($clientTypePk ?? request('client_type_pk', '')) !!};
        var preservedBuyerName = {!! json_encode($buyerName ?? request('buyer_name', '')) !!};

        if (!clientTypeSlug || !clientTypePk || !buyerSelect) {
            return;
        }

        var clientTypeOptions = {
@foreach($clientTypes ?? [] as $key => $label)
    '{{ $key }}': [
        @if(isset($clientTypeCategories[$key]))
            @foreach($clientTypeCategories[$key] as $category)
                { value: '{{ $category->id }}', text: '{{ addslashes($category->client_name) }}', dataClientName: '{{ strtolower($category->client_name ?? '') }}' },
            @endforeach
        @endif
    ],
@endforeach
        };

        var otCourseOptions = [
@if(isset($otCourses))
    @foreach($otCourses as $course)
            { value: '{{ $course->pk }}', text: '{{ addslashes($course->course_name) }}' },
    @endforeach
@endif
        ];

        var employeeNames = {
            'academy staff': [
@foreach($employees ?? [] as $e)
                { value: '{{ addslashes($e->full_name) }}', text: '{{ addslashes($e->full_name) }}' },
@endforeach
            ],
            'faculty': [
@foreach($faculties ?? [] as $f)
                { value: '{{ addslashes($f->full_name) }}', text: '{{ addslashes($f->full_name) }}' },
@endforeach
            ],
            'mess staff': [
@foreach($messStaff ?? [] as $m)
                { value: '{{ addslashes($m->full_name) }}', text: '{{ addslashes($m->full_name) }}' },
@endforeach
            ]
        };
        var courseBuyerNames = {!! json_encode(($courseBuyerNames ?? collect())->values()->all(), JSON_UNESCAPED_UNICODE) !!};
        var otherBuyerNames = {!! json_encode(($otherBuyerNames ?? collect())->values()->all(), JSON_UNESCAPED_UNICODE) !!};
        var sectionBuyerNames = {!! json_encode(($sectionBuyerNames ?? collect())->values()->all(), JSON_UNESCAPED_UNICODE) !!};

        // Debug: Log initial data
        console.log('=== Main Filter Initialization ===');
        console.log('clientTypeOptions:', clientTypeOptions);
        console.log('employeeNames:', employeeNames);
        console.log('Academy Staff count:', employeeNames['academy staff'] ? employeeNames['academy staff'].length : 0);
        console.log('Faculty count:', employeeNames['faculty'] ? employeeNames['faculty'].length : 0);
        console.log('Mess Staff count:', employeeNames['mess staff'] ? employeeNames['mess staff'].length : 0);
        console.log('otCourses count:', otCourseOptions.length);

        function fillClientTypePk(preserve) {
            var slug = clientTypeSlug.value;
            var currentClientPk = preserve ? preservedClientTypePk : '';
            console.log('=== fillClientTypePk START ===');
            console.log('slug:', slug, 'preserve:', preserve, 'currentClientPk:', currentClientPk);
            
            // If Choices.js exists, destroy it first to rebuild clean
            if (clientTypePk.choicesInstance) {
                console.log('Destroying existing Choices.js instance for clientTypePk...');
                try {
                    clientTypePk.choicesInstance.destroy();
                    clientTypePk.choicesInstance = null;
                    clientTypePk.dataset.choicesInitialized = 'false';
                } catch (e) {
                    console.error('Error destroying Choices instance:', e);
                }
            }
            
            clientTypePk.innerHTML = '<option value=\"\">All</option>';

            if ((slug === 'ot' || slug === 'course') && otCourseOptions.length) {
                otCourseOptions.forEach(function (o) {
                    var opt = document.createElement('option');
                    opt.value = o.value;
                    opt.textContent = o.text;
                    clientTypePk.appendChild(opt);
                });
            } else if (slug && clientTypeOptions[slug]) {
                clientTypeOptions[slug].forEach(function (o) {
                    var opt = document.createElement('option');
                    opt.value = o.value;
                    opt.textContent = o.text;
                    if (o.dataClientName) {
                        opt.dataset.clientName = o.dataClientName;
                    }
                    clientTypePk.appendChild(opt);
                });
            }
            
            // Restore selected value if preserving
            if (currentClientPk) {
                clientTypePk.value = currentClientPk;
            }

            console.log('fillClientTypePk - Re-initializing Choices.js for clientTypePk...');
            console.log('fillClientTypePk - Total options:', clientTypePk.options.length);
            
            // Re-initialize Choices.js after options are added
            if (typeof window.Choices !== 'undefined') {
                initChoicesElement(clientTypePk);
                if (currentClientPk && clientTypePk.choicesInstance) {
                    console.log('fillClientTypePk - Setting choice to:', currentClientPk);
                    try {
                        clientTypePk.choicesInstance.setChoiceByValue(currentClientPk);
                    } catch (e) {
                        console.error('Error setting choice value:', e);
                    }
                }
            }
            
            console.log('fillClientTypePk - Calling fillBuyerSelect(true)...');
            fillBuyerSelect(true);
        }

        function fillBuyerSelect(preserve) {
            var slug = clientTypeSlug.value;
            var selectedPk = clientTypePk.value;
            var currentBuyer = preserve ? preservedBuyerName : '';
            console.log('=== fillBuyerSelect START ===');
            console.log('slug:', slug, 'selectedPk:', selectedPk, 'preserve:', preserve);
            console.log('buyerSelect.choicesInstance exists?', !!buyerSelect.choicesInstance);
            
            // If Choices.js exists, destroy it first to rebuild clean
            if (buyerSelect.choicesInstance) {
                console.log('Destroying existing Choices.js instance...');
                try {
                    buyerSelect.choicesInstance.destroy();
                    buyerSelect.choicesInstance = null;
                    buyerSelect.dataset.choicesInitialized = 'false';
                } catch (e) {
                    console.error('Error destroying Choices instance:', e);
                }
            }
            
            // Clear existing options
            buyerSelect.innerHTML = '<option value="">All Buyers</option>';

            function addOptions(list) {
                console.log('addOptions called with', list ? list.length : 0, 'items');
                (list || []).forEach(function (o) {
                    var opt = document.createElement('option');
                    opt.value = o.value;
                    opt.textContent = o.text;
                    buyerSelect.appendChild(opt);
                    console.log('Added option:', o.text);
                });
                if (currentBuyer) {
                    buyerSelect.value = currentBuyer;
                    console.log('Set current buyer to:', currentBuyer);
                }
            }

            function loadBuyersFromReportEndpoint(slugToLoad) {
                // Uses existing report endpoint to return distinct buyers (optionally date-filtered)
                var df = document.getElementById('date_from');
                var dt = document.getElementById('date_to');
                var dateFromYmd = (df && df.value) ? toYmd(df.value) : '';
                var dateToYmd = (dt && dt.value) ? toYmd(dt.value) : '';

                function fallbackFromServerLists() {
                    if (slugToLoad === 'course') {
                        var listCourse = (courseBuyerNames || []).map(function (name) { return { value: name, text: name }; });
                        addOptions(listCourse);
                        return;
                    }
                    if (slugToLoad === 'other') {
                        var listOther = (otherBuyerNames || []).map(function (name) { return { value: name, text: name }; });
                        addOptions(listOther);
                        return;
                    }
                    if (slugToLoad === 'section') {
                        var listSection = (sectionBuyerNames || []).map(function (name) { return { value: name, text: name }; });
                        addOptions(listSection);
                        return;
                    }
                }

                var qs = new URLSearchParams();
                qs.set('client_type_slug', slugToLoad);
                if (dateFromYmd) qs.set('from_date', dateFromYmd);
                if (dateToYmd) qs.set('to_date', dateToYmd);
                // Add PK if selected (course/ot => course_master_pk, others => client_type_pk)
                if (selectedPk) {
                    if (slugToLoad === 'course' || slugToLoad === 'ot') {
                        qs.set('course_master_pk', selectedPk);
                    } else {
                        qs.set('client_type_pk', selectedPk);
                    }
                }

                fetch(buyersForReportUrl + '?' + qs.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        var buyers = (data.buyers || []).map(function (name) {
                            return { value: name || '', text: name || '—' };
                        }).filter(function (o) { return !!o.value; });
                        if (!buyers.length) {
                            fallbackFromServerLists();
                        } else {
                        addOptions(buyers);
                        }
                        if (typeof window.Choices !== 'undefined') {
                            initChoicesElement(buyerSelect);
                            if (currentBuyer && buyerSelect.choicesInstance) {
                                buyerSelect.choicesInstance.setChoiceByValue(currentBuyer);
                            }
                        }
                    })
                    .catch(function () {
                        fallbackFromServerLists();
                        // still init Choices
                        if (typeof window.Choices !== 'undefined') {
                            initChoicesElement(buyerSelect);
                        }
                    });
            }

            if (slug === 'employee' && selectedPk) {
                var selectedOpt = clientTypePk.options[clientTypePk.selectedIndex];
                var dataClientName = selectedOpt && selectedOpt.dataset ? (selectedOpt.dataset.clientName || '') : '';
                console.log('Employee Debug - selectedPk:', selectedPk);
                console.log('Employee Debug - selectedOpt:', selectedOpt);
                console.log('Employee Debug - dataClientName:', dataClientName);
                console.log('Employee Debug - employeeNames keys:', Object.keys(employeeNames));
                console.log('Employee Debug - employeeNames[dataClientName]:', employeeNames[dataClientName]);
                
                if (dataClientName && employeeNames[dataClientName] && employeeNames[dataClientName].length > 0) {
                    console.log('Employee Debug - Adding', employeeNames[dataClientName].length, 'employees');
                    addOptions(employeeNames[dataClientName]);
                } else {
                    console.warn('Employee: No employees found for dataClientName:', dataClientName);
                    console.warn('Available keys:', Object.keys(employeeNames));
                }
            } else if (slug === 'employee' && !selectedPk) {
                // Employee selected but Client Type = All => show all employee groups
                var all = []
                    .concat(employeeNames['academy staff'] || [])
                    .concat(employeeNames['faculty'] || [])
                    .concat(employeeNames['mess staff'] || []);

                // De-duplicate + sort (by name)
                var map = new Map();
                all.forEach(function (o) {
                    var key = String(o.value || '').trim().toLowerCase();
                    if (!key) return;
                    if (!map.has(key)) map.set(key, { value: o.value, text: o.text });
                });
                var unique = Array.from(map.values()).sort(function (a, b) {
                    return String(a.text || '').localeCompare(String(b.text || ''), undefined, { sensitivity: 'base' });
                });
                addOptions(unique);
            } else if (slug === 'ot' && selectedPk) {
                fetch(studentsByCourseUrl + '/' + selectedPk, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        var students = (data.students || []).map(function (s) {
                            return { value: s.display_name || '', text: s.display_name || '—' };
                        });
                        addOptions(students);
                        // Re-initialize Choices.js after async data load
                        if (typeof window.Choices !== 'undefined') {
                            initChoicesElement(buyerSelect);
                            if (currentBuyer) {
                                buyerSelect.choicesInstance.setChoiceByValue(currentBuyer);
                            }
                        }
                    })
                    .catch(function () {
                        // ignore; leave All Buyers only - still need to init Choices
                        if (typeof window.Choices !== 'undefined') {
                            initChoicesElement(buyerSelect);
                        }
                    });
                return; // Exit early for async case
            } else if (slug === 'ot' && !selectedPk) {
                // OT selected but Course = All => show all OT buyers from precomputed list
                var listOt = (otBuyerNames || []).map(function (name) {
                    return { value: name, text: name };
                });
                addOptions(listOt);
            } else if (slug === 'course') {
                // Requirement:
                // - If Course selected AND Client Type = All => Buyer Name should list ALL course names
                // - If specific course selected => Buyer Name should be that course name
                if (!selectedPk) {
                    var listCourseAll = (otCourseOptions || []).map(function (o) {
                        return { value: o.text, text: o.text };
                    });
                    addOptions(listCourseAll);
                } else {
                    var matchCourse = (otCourseOptions || []).find(function (o) {
                        return String(o.value) === String(selectedPk);
                    });
                    if (matchCourse) {
                        addOptions([{ value: matchCourse.text, text: matchCourse.text }]);
                    }
                }
            } else if (slug === 'section') {
                // Requirement:
                // - If Section selected AND Client Type = All => Buyer Name should list ALL section names
                // - If specific section selected => Buyer Name should be that section's name
                var sectionOptions = clientTypeOptions['section'] || [];
                if (!selectedPk) {
                    var listSectionAll = sectionOptions.map(function (o) {
                        return { value: o.text, text: o.text };
                    });
                    addOptions(listSectionAll);
                } else {
                    var matchSection = sectionOptions.find(function (o) {
                        return String(o.value) === String(selectedPk);
                    });
                    if (matchSection) {
                        addOptions([{ value: matchSection.text, text: matchSection.text }]);
                    }
                }
            } else if (slug === 'other') {
                // For "other" we can still rely on precomputed distinct buyer names
                var listOther = (otherBuyerNames || []).map(function (name) {
                    return { value: name, text: name };
                });
                addOptions(listOther);
            } else if (slug && clientTypeOptions[slug]) {
                var list3 = clientTypeOptions[slug].map(function (o) {
                    return { value: o.text, text: o.text };
                });
                addOptions(list3);
            }
            
            console.log('fillBuyerSelect - Total options in buyerSelect:', buyerSelect.options.length);
            console.log('fillBuyerSelect - Re-initializing Choices.js...');
            
            // Re-initialize Choices.js after options are added
            if (typeof window.Choices !== 'undefined') {
                initChoicesElement(buyerSelect);
                if (currentBuyer && buyerSelect.choicesInstance) {
                    console.log('fillBuyerSelect - Setting choice to:', currentBuyer);
                    try {
                        buyerSelect.choicesInstance.setChoiceByValue(currentBuyer);
                    } catch (e) {
                        console.error('Error setting choice value:', e);
                    }
                }
            }
        }

        clientTypeSlug.addEventListener('change', function () {
            preservedClientTypePk = ''; // reset when main type changes
            preservedBuyerName = ''; // reset when main type changes
            fillClientTypePk(false);
        });
        clientTypePk.addEventListener('change', function () {
            preservedBuyerName = '';
            fillBuyerSelect(false);
        });

        // Initial populate on page load
        fillClientTypePk(true);
    })();

    document.getElementById('modalSelectAll').addEventListener('change', function() {
        document.querySelectorAll('#addProcessMessBillsModal .modal-bill-check').forEach(function(cb) {
            cb.checked = this.checked;
        }.bind(this));
        updateBulkActionsBar();
    });

    document.getElementById('addProcessMessBillsModal').addEventListener('click', function(e) {
        var target = e.target.closest('.modal-bill-check');
        if (target && target.classList.contains('modal-bill-check')) {
            updateBulkActionsBar();
        }
    });

    function doGenerateInvoice(billId, buyerName, btnEl) {
        if (!billId) { showToast('Bill ID not found.', 'error'); return; }
        if (btnEl) { btnEl.disabled = true; btnEl.textContent = '…'; }
        var body = {};
        if (String(billId).indexOf('combined-') === 0) {
            var mFrom = document.getElementById('modal_date_from');
            var mTo = document.getElementById('modal_date_to');
            if (mFrom && mFrom.value) body.date_from = toYmd(mFrom.value);
            if (mTo && mTo.value) body.date_to = toYmd(mTo.value);
        }
        fetch(generateInvoiceBaseUrl + '/' + encodeURIComponent(billId) + '/generate-invoice', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(body)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                showToast(data.message || 'Invoice generated.');
                loadModalBills();
            } else {
                showToast(data.message || 'Failed to generate invoice.', 'error');
            }
        })
        .catch(function() {
            showToast('Request failed. Try again.', 'error');
        })
        .finally(function() {
            if (btnEl) { btnEl.disabled = false; btnEl.textContent = 'Invoice'; }
        });
    }

    function doGeneratePayment(billId, buyerName, btnEl, paymentPayload) {
        if (!billId) { showToast('Bill ID not found.', 'error'); return; }
        if (btnEl) { btnEl.disabled = true; btnEl.textContent = '…'; }
        var body = paymentPayload && (paymentPayload.amount || paymentPayload.payment_mode || paymentPayload.payment_date)
            ? JSON.stringify(paymentPayload)
            : JSON.stringify({});
        fetch(generateInvoiceBaseUrl + '/' + encodeURIComponent(billId) + '/generate-payment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: body
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                showToast('Payment completed for ' + (buyerName || data.client_name) + '.');
                var payNowModalEl = document.getElementById('payNowModal');
                if (payNowModalEl && bootstrap.Modal.getInstance(payNowModalEl)) bootstrap.Modal.getInstance(payNowModalEl).hide();
                var addModalEl = document.getElementById('addProcessMessBillsModal');
                if (addModalEl && bootstrap.Modal.getInstance(addModalEl)) bootstrap.Modal.getInstance(addModalEl).hide();
                window.location.reload();
            } else {
                showToast(data.message || 'Failed to process payment.', 'error');
                if (btnEl) { btnEl.disabled = false; btnEl.textContent = 'Payment'; }
            }
        })
        .catch(function() {
            showToast('Request failed. Try again.', 'error');
            if (btnEl) { btnEl.disabled = false; btnEl.textContent = 'Payment'; }
        });
    }

    function renderPaymentDetailsContent(data) {
        var dateStr = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-');
        var timeStr = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false });
        var rows = (data.items || []).map(function(item) {
            return '<tr><td>' + (item.store_name || '—') + '</td><td>' + (item.item_name || '—') + '</td><td>' + (item.issue_date || '—') + '</td><td class="text-end">' + (item.price || '0') + '</td><td class="text-end">' + (item.quantity || '0') + '</td><td class="text-end">' + (item.amount || '0') + '</td></tr>';
        }).join('');
        var clientNameCourse = data.client_name_course || (function () {
            if (data.course_name) {
                return (data.client_name || '—') + ' – ' + data.course_name;
            }
            return data.client_name || '—';
        })();
        var hasRefOrOrder = !!(data.reference_number || data.order_by);
        var html = '<div class="receipt-top">' +
            '<div class="receipt-logo"><span class="receipt-logo-icon"></span><span class="receipt-logo-text">Sargam</span></div>' +
            '<span class="receipt-date">Date ' + dateStr + ' ' + timeStr + '</span>' +
            '</div>' +
            '<div class="receipt-center">' +
            '<div class="receipt-title">OFFICER\'S MESS LBSNAA MUSSOORIE</div>' +
            '<div class="receipt-subtitle">MESS BILLS</div>' +
            '<div class="receipt-period">Client Bill From Period ' + (data.date_from || '') + ' To ' + (data.date_to || '') + '</div>' +
            '</div>' +
            '<hr/>' +
            '<div class="client-row">' +
            '<span><span class="client-label">Receipt No</span>: <span class="client-value">' + (data.receipt_no || '—') + '</span></span>' +
            '<span><span class="client-label">Invoice No</span>: <span class="client-value">' + (data.invoice_no || '—') + '</span></span>' +
            '</div>' +
            '<div class="client-row">' +
            '<span><span class="client-label">Client Name</span>: <span class="client-value">' + clientNameCourse + '</span></span>' +
            '<span><span class="client-label">Client Type</span>: <span class="client-value">' + (data.client_type || '—') + '</span></span>' +
            '</div>' +
            (hasRefOrOrder
                ? ('<div class="client-row">' +
                   (data.reference_number ? '<span><span class="client-label">Reference Number</span>: <span class="client-value">' + data.reference_number + '</span></span>' : '') +
                   (data.order_by ? '<span><span class="client-label">Order By</span>: <span class="client-value">' + data.order_by + '</span></span>' : '') +
                   '</div>')
                : '') +
            (data.remarks
                ? ('<div class="client-row"><span><span class="client-label">Remarks</span>: <span class="client-value">' + data.remarks + '</span></span></div>')
                : '') +
            '<hr/>' +
            '<table class="bill-table"><thead><tr><th>Store Name</th><th>Item Name</th><th>Issue Date</th><th class="text-end">Price</th><th class="text-end">Quantity</th><th class="text-end">Amount</th></tr></thead><tbody>' + rows + '</tbody></table>' +
            '<div class="receipt-bottom">' +
            '<div></div>' +
            '<div class="payment-summary">' +
            '<div class="summary-row"><span class="summary-label">Paid Amount</span><span class="summary-value">' + (data.paid_amount || '0.0') + '</span></div>' +
            '<div class="summary-row"><span class="summary-label">Total Amount</span><span class="summary-value">' + (data.total_amount || '0.0') + '</span></div>' +
            '<div class="summary-row"><span class="summary-label">Due Amount</span><span class="summary-value">' + (data.due_amount || '0.0') + '</span></div>' +
            '</div>' +
            '</div>';
        return html;
    }

    function openPaymentDetailsModal(billId, dateFromYmd, dateToYmd) {
        paymentDetailsBillId = billId;
        paymentDetailsDateFrom = dateFromYmd || null;
        paymentDetailsDateTo = dateToYmd || null;
        var content = document.getElementById('paymentDetailsContent');
        if (content) content.innerHTML = '<div class="text-center py-4 text-muted">Loading...</div>';
        var url = paymentDetailsUrl.replace('__ID__', encodeURIComponent(billId));
        if (String(billId).indexOf('combined-') === 0 && (paymentDetailsDateFrom || paymentDetailsDateTo)) {
            var params = [];
            if (paymentDetailsDateFrom) params.push('date_from=' + encodeURIComponent(paymentDetailsDateFrom));
            if (paymentDetailsDateTo) params.push('date_to=' + encodeURIComponent(paymentDetailsDateTo));
            if (params.length) url += (url.indexOf('?') >= 0 ? '&' : '?') + params.join('&');
        }
        fetch(url).then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.error) {
                    content.innerHTML = '<div class="text-danger py-4 text-center">' + (data.error || 'Failed to load.') + '</div>';
                    showToast(data.error || 'Failed to load payment details.', 'error');
                    return;
                }
                content.innerHTML = renderPaymentDetailsContent(data);
                content.setAttribute('data-due-amount-raw', data.due_amount_raw != null ? data.due_amount_raw : data.due_amount || 0);
                if (data.first_receipt_id) content.setAttribute('data-first-receipt-id', data.first_receipt_id);
                else content.removeAttribute('data-first-receipt-id');
                var pdModal = document.getElementById('paymentDetailsModal');
                var addModalEl = document.getElementById('addProcessMessBillsModal');
                var addModalInstance = addModalEl && typeof bootstrap !== 'undefined' ? bootstrap.Modal.getInstance(addModalEl) : null;
                function showPaymentDetailsModal() {
                    if (pdModal && typeof bootstrap !== 'undefined') {
                        var m = bootstrap.Modal.getOrCreateInstance(pdModal);
                        m.show();
                    }
                }
                if (addModalInstance) {
                    addModalEl.addEventListener('hidden.bs.modal', function once() {
                        addModalEl.removeEventListener('hidden.bs.modal', once);
                        showPaymentDetailsModal();
                    });
                    addModalInstance.hide();
                } else {
                    showPaymentDetailsModal();
                }
            })
            .catch(function() {
                if (content) content.innerHTML = '<div class="text-danger py-4 text-center">Failed to load payment details.</div>';
                showToast('Failed to load payment details.', 'error');
            });
    }

    document.getElementById('paymentDetailsPayNowBtn').addEventListener('click', function() {
        var content = document.getElementById('paymentDetailsContent');
        var dueRaw = content && content.getAttribute('data-due-amount-raw');
        var due = dueRaw !== null && dueRaw !== '' ? parseFloat(dueRaw) : 0;
        var amountInput = document.getElementById('payNowAmount');
        amountInput.value = isNaN(due) ? '' : due;
        amountInput.setAttribute('max', (isNaN(due) || due < 0) ? '' : due);
        var pdModal = document.getElementById('paymentDetailsModal');
        if (pdModal && bootstrap.Modal.getInstance(pdModal)) bootstrap.Modal.getInstance(pdModal).hide();
        var payNowModal = document.getElementById('payNowModal');
        if (payNowModal && typeof bootstrap !== 'undefined') {
            payNowModal.classList.toggle('payment-mode-cheque', document.getElementById('payNowPaymentMode').value === 'cheque');
            var m = bootstrap.Modal.getOrCreateInstance(payNowModal);
            m.show();
        }
    });

    document.getElementById('paymentDetailsPrintBtn').addEventListener('click', function() {
        var content = document.getElementById('paymentDetailsContent');
        var receiptId = paymentDetailsBillId;
        if (String(receiptId || '').indexOf('combined-') === 0) {
            receiptId = receiptId;
        } else {
            receiptId = (content && content.getAttribute('data-first-receipt-id')) || receiptId;
        }
        if (receiptId) {
            var printUrl = printReceiptBaseUrl.replace('__ID__', encodeURIComponent(receiptId));
            if (String(receiptId).indexOf('combined-') === 0 && (paymentDetailsDateFrom || paymentDetailsDateTo)) {
                printUrl += (printUrl.indexOf('?') >= 0 ? '&' : '?') + 'date_from=' + encodeURIComponent(paymentDetailsDateFrom || '') + '&date_to=' + encodeURIComponent(paymentDetailsDateTo || '');
            }
            window.open(printUrl, '_blank');
        }
    });

    document.getElementById('payNowPaymentMode').addEventListener('change', function() {
        var modal = document.getElementById('payNowModal');
        if (modal) modal.classList.toggle('payment-mode-cheque', this.value === 'cheque');
    });

    document.getElementById('payNowSaveBtn').addEventListener('click', function() {
        var billId = paymentDetailsBillId;
        if (!billId) { showToast('No bill selected.', 'error'); return; }
        var content = document.getElementById('paymentDetailsContent');
        var dueRaw = content && content.getAttribute('data-due-amount-raw');
        var due = dueRaw !== null && dueRaw !== '' ? parseFloat(dueRaw) : 0;
        var amountEl = document.getElementById('payNowAmount');
        var modeEl = document.getElementById('payNowPaymentMode');
        var dateEl = document.getElementById('payNowPaymentDate');
        var amount = amountEl && amountEl.value ? amountEl.value : '';
        var paymentMode = modeEl && modeEl.value ? modeEl.value : 'cash';
        var paymentDate = dateEl && dateEl.value ? dateEl.value : '';
        if (!amount) { showToast('Please enter amount.', 'error'); return; }
        var amountNum = parseFloat(amount);
        if (isNaN(amountNum) || amountNum <= 0) { showToast('Please enter a valid amount.', 'error'); return; }
        if (amountNum > due) { showToast('Payment amount cannot exceed the balance due (₹ ' + (due.toFixed(2)) + ').', 'error'); return; }
        var payload = { amount: amount, payment_mode: paymentMode, payment_date: paymentDate };
        if (paymentMode === 'cheque') {
            payload.bank_name = (document.getElementById('payNowBankName') || {}).value || '';
            payload.cheque_number = (document.getElementById('payNowChequeNumber') || {}).value || '';
            payload.cheque_date = (document.getElementById('payNowChequeDate') || {}).value || '';
        }
        if (String(billId).indexOf('combined-') === 0 && paymentDetailsDateFrom) payload.date_from = paymentDetailsDateFrom;
        if (String(billId).indexOf('combined-') === 0 && paymentDetailsDateTo) payload.date_to = paymentDetailsDateTo;
        var btn = this;
        btn.disabled = true;
        doGeneratePayment(billId, '', btn, payload);
        btn.disabled = false;
    });

    document.addEventListener('mousedown', function(e) {
        var invoiceBtn = e.target.closest('.generate-invoice-btn');
        if (invoiceBtn) {
            e.preventDefault();
            e.stopPropagation();
            var billId = invoiceBtn.getAttribute('data-bill-id');
            var buyerName = invoiceBtn.getAttribute('data-buyer-name') || '';
            if (confirm('Generate invoice and send notification to ' + (buyerName || 'this employee') + '?')) {
                doGenerateInvoice(billId, buyerName, invoiceBtn);
            }
            return;
        }
        var paymentBtn = e.target.closest('.generate-payment-btn');
        if (paymentBtn) {
            e.preventDefault();
            e.stopPropagation();
            var billId = paymentBtn.getAttribute('data-bill-id');
            var dateFromYmd = null;
            var dateToYmd = null;
            if (String(billId).indexOf('combined-') === 0) {
                var mFrom = document.getElementById('modal_date_from');
                var mTo = document.getElementById('modal_date_to');
                if (mFrom && mFrom.value) dateFromYmd = toYmd(mFrom.value);
                if (mTo && mTo.value) dateToYmd = toYmd(mTo.value);
            }
            openPaymentDetailsModal(billId, dateFromYmd, dateToYmd);
            return;
        }
    }, true);

    // Bulk actions
    document.getElementById('modalBulkInvoiceBtn').addEventListener('click', function() {
        var ids = Array.from(document.querySelectorAll('#addProcessMessBillsModal .modal-bill-check:checked')).map(function(c) { return c.getAttribute('data-id'); });
        if (ids.length === 0) { showToast('Select at least one bill.', 'error'); return; }
        if (!confirm('Generate invoice for ' + ids.length + ' selected bill(s)?')) return;
        var done = 0;
        ids.forEach(function(id) {
            doGenerateInvoice(id, '', null);
            done++;
        });
        showToast('Processing ' + ids.length + ' invoice(s)...');
    });

    document.getElementById('modalBulkPaymentBtn').addEventListener('click', function() {
        var checked = document.querySelectorAll('#addProcessMessBillsModal .modal-bill-check:checked');
        if (checked.length === 0) { showToast('Select at least one bill.', 'error'); return; }
        if (!confirm('Mark ' + checked.length + ' selected bill(s) as paid?')) return;
        checked.forEach(function(cb) {
            doGeneratePayment(cb.getAttribute('data-id'), cb.getAttribute('data-name'), null);
        });
    });
});
</script>
<script>
function printProcessMessBillsMainTable() {
    var table = document.getElementById('processMessBillsTable');
    if (!table) { window.print(); return; }

    // Use DataTables API so print includes ALL filtered rows, not just current page
    var dt = null;
    try {
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable && window.jQuery.fn.DataTable.isDataTable('#processMessBillsTable')) {
            dt = window.jQuery('#processMessBillsTable').DataTable();
        }
    } catch (e) {}

    var rowsData = [];
    if (dt) {
        rowsData = dt.rows({ search: 'applied' }).data().toArray();
    } else {
        rowsData = Array.from(table.querySelectorAll('tbody tr')).map(function (tr) {
            return Array.from(tr.children).map(function (td) { return td.innerHTML; });
        });
    }

    // Remove action column (last column) for print
    var actionColIdx = 8;

    var originalThead = table.querySelector('thead');
    var headerCells = originalThead ? Array.from(originalThead.querySelectorAll('tr th')) : [];
    var printHeaderCells = headerCells.filter(function (_, idx) { return idx !== actionColIdx; });
    var headerHtml = '<tr>' + printHeaderCells.map(function (th) { return '<th>' + th.innerHTML + '</th>'; }).join('') + '</tr>';

    var bodyRowsHtml = rowsData.map(function (row) {
        var cells = Array.isArray(row) ? row : (row && row.length != null ? Array.from(row) : []);
        var filteredCells = cells.filter(function (_, idx) { return idx !== actionColIdx; });
        return '<tr>' + filteredCells.map(function (c) { return '<td>' + c + '</td>'; }).join('') + '</tr>';
    }).join('');

    var columnsCount = printHeaderCells.length || 8;
    var title = 'Process Mess Bills - Employee';
    var periodText = 'Period: {{ $dateFromDisplay }} to {{ $dateToDisplay }}';

    var printableTable = `
      <table class="table table-sm table-bordered align-middle mb-0">
        <thead>
          <tr>
            <th colspan="${columnsCount}">
              <div class="d-flex justify-content-between align-items-center mb-2 lbsnaa-header">
                <div class="d-flex align-items-center gap-2">
                  <img src="https://upload.wikimedia.org/wikipedia/commons/5/55/Emblem_of_India.svg" alt="India Emblem" height="40">
                  <div>
                    <div class="brand-line-1">Government of India</div>
                    <div class="brand-line-2">OFFICER'S MESS LBSNAA MUSSOORIE</div>
                    <div class="brand-line-3">Lal Bahadur Shastri National Academy of Administration</div>
                  </div>
                </div>
                <div class="d-none d-print-block">
                  <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="LBSNAA Logo" height="40">
                </div>
              </div>
              <div class="d-flex flex-wrap justify-content-between align-items-center report-meta">
                <span><strong>${title}</strong></span>
                <span>${periodText}</span>
                <span><strong>Printed on:</strong> ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</span>
              </div>
            </th>
          </tr>
          ${headerHtml}
        </thead>
        <tbody>
          ${bodyRowsHtml}
        </tbody>
      </table>`;

    var printWindow = window.open('', '_blank');
    if (!printWindow) { window.print(); return; }

    printWindow.document.open();
    printWindow.document.write(`<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>${title} - OFFICER'S MESS LBSNAA MUSSOORIE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      font-size: 10px;
      margin: 0;
      padding: 0;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .lbsnaa-header {
      border-bottom: 2px solid #004a93;
      padding-bottom: .75rem;
      margin-bottom: 1rem;
    }
    .brand-line-1 { font-size: .85rem; text-transform: uppercase; letter-spacing: .06em; color: #004a93; }
    .brand-line-2 { font-size: 1.1rem; font-weight: 700; text-transform: uppercase; color: #222; }
    .brand-line-3 { font-size: .8rem; color: #555; }
    .report-meta { font-size: .8rem; margin-bottom: .75rem; }
    .report-meta span { display: inline-block; margin-right: 1.5rem; }
    .container-fluid { padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 9px; }
    th, td {
      padding: 4px 6px;
      border: 1px solid #dee2e6;
      white-space: normal !important;
      word-break: break-word;
      overflow-wrap: anywhere;
      vertical-align: top;
    }
    thead th { background: #f8f9fa; font-weight: 600; }
    .table, .table * { white-space: normal !important; }
    .table-responsive { overflow: visible !important; }
    thead { display: table-header-group; }
    @page { size: A4 landscape; margin: 8mm; }
    @media print { body { margin: 0; } }
  </style>
</head>
<body>
  <div class="container-fluid">
    <div class="table-responsive">
      ${printableTable}
    </div>
  </div>

  <script>
    window.addEventListener('load', function() { window.print(); });
  <\/script>
</body>
</html>`);
    printWindow.document.close();
}

function printProcessMessBillsTable() {
    var table = document.getElementById('modalBillsTable');
    if (!table) {
        window.print();
        return;
    }

    var dateFrom = document.getElementById('modal_date_from')?.value || '';
    var dateTo   = document.getElementById('modal_date_to')?.value || '';

    var printWindow = window.open('', '_blank');
    if (!printWindow) {
        window.print();
        return;
    }

    var title = 'Process Mess Bills - Invoice & Payment';
    var periodText = dateFrom || dateTo
        ? ('From ' + (dateFrom || 'Start') + ' To ' + (dateTo || 'End'))
        : 'All Dates';

    // Build printable table with LBSNAA header inside thead so it repeats on every page
    // Print ALL rows from modal dataset (not only current "per page" view)
    var originalThead = table.querySelector('thead');
    var headerRow = originalThead ? originalThead.querySelector('tr') : null;
    var headerCells = headerRow ? Array.from(headerRow.children) : [];
    // Remove Checkbox (0), Actions (6) and Receipt (7) columns from print
    var removeIdx = { 0: true, 6: true, 7: true };
    var printHeaderCells = headerCells.filter(function (_, idx) { return !removeIdx[idx]; });
    var columnsCount = printHeaderCells.length || 6;
    var columnHeadHtml = '<tr>' + printHeaderCells.map(function (th) { return '<th>' + th.innerHTML + '</th>'; }).join('') + '</tr>';

    var filtered = (typeof getFilteredModalBills === 'function') ? getFilteredModalBills() : [];
    var bodyHtml = filtered.map(function (b, i) {
        var sn = i + 1;
        return '<tr>' +
            '<td>' + sn + '</td>' +
            '<td>' + (b.buyer_name || '—') + '</td>' +
            '<td>' + (b.invoice_no || '—') + '</td>' +
            '<td>' + (b.payment_type || '—') + '</td>' +
            '<td class="text-end">' + (b.total || '0') + '</td>' +
            '</tr>';
    }).join('');

    var printableTable = `
      <table class="table table-sm table-bordered align-middle mb-0">
        <thead>
          <tr>
            <th colspan="${columnsCount}">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2">
                  <img src="https://upload.wikimedia.org/wikipedia/commons/5/55/Emblem_of_India.svg" alt="India Emblem" height="40">
                  <div>
                    <div class="brand-line-1">Government of India</div>
                    <div class="brand-line-2">OFFICER'S MESS LBSNAA MUSSOORIE</div>
                    <div class="brand-line-3">Lal Bahadur Shastri National Academy of Administration</div>
                  </div>
                </div>
                <div class="d-none d-print-block">
                  <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="LBSNAA Logo" height="40">
                </div>
              </div>
              <div class="d-flex flex-wrap justify-content-between align-items-center report-meta">
                <span><strong>${title}</strong></span>
                <span>${periodText}</span>
                <span><strong>Printed on:</strong> ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</span>
              </div>
            </th>
          </tr>
          ${columnHeadHtml}
        </thead>
        <tbody>
          ${bodyHtml}
        </tbody>
      </table>`;

    printWindow.document.open();
    printWindow.document.write(`<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>${title} - OFFICER'S MESS LBSNAA MUSSOORIE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      font-size: 10px;
      margin: 0;
      padding: 0;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .lbsnaa-header {
      border-bottom: 2px solid #004a93;
      padding-bottom: .75rem;
      margin-bottom: 1rem;
    }
    .brand-line-1 { font-size: .85rem; text-transform: uppercase; letter-spacing: .06em; color: #004a93; }
    .brand-line-2 { font-size: 1.1rem; font-weight: 700; text-transform: uppercase; color: #222; }
    .brand-line-3 { font-size: .8rem; color: #555; }
    .report-meta { font-size: .8rem; margin-bottom: .75rem; }
    .report-meta span { display: inline-block; margin-right: 1.5rem; }
    .container-fluid { padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 9px; }
    th, td {
      padding: 4px 6px;
      border: 1px solid #dee2e6;
      white-space: normal !important;
      word-break: break-word;
      overflow-wrap: anywhere;
      vertical-align: top;
    }
    thead th { background: #f8f9fa; font-weight: 600; }
    .table, .table * { white-space: normal !important; }
    .table-responsive { overflow: visible !important; }
    thead { display: table-header-group; }
    @page { size: A4 landscape; margin: 8mm; }
    @media print { body { margin: 0; } }
  </style>
</head>
<body>
  <div class="container-fluid">
    <div class="table-responsive">
      ${printableTable}
    </div>
  </div>

  <script>
    window.addEventListener('load', function() { window.print(); });
  <\/script>
</body>
</html>`);
    printWindow.document.close();
}
</script>
@endpush

<script>
document.addEventListener('DOMContentLoaded', function() {
    var df = document.getElementById('date_from');
    var dt = document.getElementById('date_to');
    function toYmd(val) {
        if (!val || !String(val).match(/^\d{1,2}-\d{1,2}-\d{4}$/)) return val;
        var p = String(val).split('-');
        return p[2] + '-' + p[1] + '-' + p[0];
    }
    document.querySelectorAll('form[action="{{ route('admin.mess.process-mess-bills-employee.index') }}"]').forEach(function(form) {
        form.addEventListener('submit', function() {
            var hFrom = form.querySelector('input[name="date_from"]');
            var hTo = form.querySelector('input[name="date_to"]');
            var valFrom = (df && df.value) ? (toYmd(df.value) || df.value) : (hFrom ? hFrom.value : '');
            var valTo = (dt && dt.value) ? (toYmd(dt.value) || dt.value) : (hTo ? hTo.value : '');
            if (hFrom && valFrom) hFrom.value = valFrom;
            if (hTo && valTo) hTo.value = valTo;
        });
    });
});
</script>
@endsection

@push('styles')
    {{-- Choices.js via CDN --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
@endpush

@push('scripts')
    {{-- Choices.js via CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
@endpush