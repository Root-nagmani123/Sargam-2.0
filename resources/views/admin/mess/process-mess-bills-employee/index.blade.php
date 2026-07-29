@extends('admin.layouts.master')
@section('title', 'Billing and Finance')
@section('content')
<div class="container-fluid py-3 py-md-4 process-mess-bills-employee-report pmbe-page">
    <x-breadcrum title="Billing and Finance" :showBack="false">
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addProcessMessBillsModal">
            <i class="material-symbols-rounded" style="font-size: 1.1rem;">add</i>
            <span>Generate Invoice</span>
        </button>
    </x-breadcrum>
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
        <h4 class="fw-bold">Billing and Finance</h4>
        <p class="mb-1">Period: {{ $dateFromDisplay }} to {{ $dateToDisplay }}</p>
        <p class="text-muted mb-0 small">Generated on: {{ now()->format('d-m-Y H:i:s') }}</p>
    </div>

    {{-- Summary cards --}}
    <section class="no-print pmbe-stat-grid mb-4" aria-label="Bill statistics">
        @php
            $stats = $stats ?? ['total_bills' => 0, 'paid_count' => 0, 'unpaid_count' => 0, 'total_amount' => 0, 'total_due_amount' => 0];
        @endphp
        <div class="pmbe-stat-card">
            <div class="pmbe-stat-label">Total Bills</div>
            <div class="pmbe-stat-value" id="process-mess-stats-total-bills">{{ number_format($stats['total_bills']) }}</div>
        </div>
        <div class="pmbe-stat-card">
            <div class="pmbe-stat-label">Unpaid</div>
            <div class="pmbe-stat-value" id="process-mess-stats-unpaid">{{ number_format($stats['unpaid_count']) }}</div>
        </div>
        <div class="pmbe-stat-card">
            <div class="pmbe-stat-label">Paid</div>
            <div class="pmbe-stat-value" id="process-mess-stats-paid">{{ number_format($stats['paid_count']) }}</div>
        </div>
        <div class="pmbe-stat-card">
            <div class="pmbe-stat-label">Total Amount</div>
            <div class="pmbe-stat-value" id="process-mess-stats-total-amount">&#8377; {{ number_format($stats['total_amount'], 2) }}</div>
        </div>
        <div class="pmbe-stat-card">
            <div class="pmbe-stat-label">Total Amount Due</div>
            <div class="pmbe-stat-value" id="process-mess-stats-total-due-amount">&#8377; {{ number_format($stats['total_due_amount'] ?? 0, 2) }}</div>
        </div>
    </section>


    {{-- Download / Print bar --}}
    @php
        $exportQuery = request()->only(['date_from', 'date_to', 'client_type', 'client_type_pk', 'buyer_name', 'status', 'search']);
        $exportQuery['invoice_sent'] = request()->has('invoice_sent') ? request('invoice_sent') : ($invoiceSentFilter ?? 'sent');
        $selectedClientTypes = request('client_type', []);
        if (!is_array($selectedClientTypes)) { $selectedClientTypes = $selectedClientTypes !== null ? [$selectedClientTypes] : []; }
        $selectedBuyerNames = (array) ($buyerName ?? request('buyer_name', []));
        $currentStatus = $statusFilter ?? request('status', '');
        $currentInvoiceSent = $invoiceSentFilter ?? (request()->has('invoice_sent') ? request('invoice_sent') : 'sent');
    @endphp
    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        <a href="{{ route('admin.mess.process-mess-bills-employee.export') }}?{{ http_build_query($exportQuery) }}"
           class="btn pmbe-export-btn border-0 text-primary" title="Download (Excel)" data-mess-excel-export="processMessBillsTable">
            <i class="material-symbols-rounded">download</i><span>Download</span>
        </a>
        <button type="button" class="btn pmbe-export-btn border-0 text-primary" title="Print" onclick="printProcessMessBillsMainTable()">
            <i class="material-symbols-rounded">print</i><span>Print</span>
        </button>
    </div>

    {{-- Table card --}}
    <div class="card border-0 shadow">
        <div class="card-body p-3 p-lg-4">
            {{-- Filter toolbar (auto-apply; filters wrap on narrow screens) --}}
            <div class="d-flex align-items-end gap-2 mb-3 pmbe-toolbar no-print">
                <form method="GET" action="{{ route('admin.mess.process-mess-bills-employee.index') }}" id="mainFilterForm" class="d-flex align-items-end gap-2 pmbe-filter-form">
                    <input type="hidden" name="refresh" value="1">
                    <span class="programme-dt-filters-label flex-shrink-0 align-self-center">Filter</span>
                    <div id="pmbeFilterItems" class="d-flex align-items-end gap-2 pmbe-filter-items">
                        <div class="pmbe-filter-item" data-filter="date">
                            <input type="text" id="pmbe_date_range" class="form-control pmbe-filter-range" placeholder="Select date range" autocomplete="off" readonly>
                            {{-- Range picker fills these; names preserved for the backend + existing JS. --}}
                            <input type="hidden" name="date_from" id="date_from" value="{{ $effectiveDateFrom ?? request('date_from', now()->startOfMonth()->format('d-m-Y')) }}" data-default-ymd="{{ $effectiveDateFromYmd ?? now()->startOfMonth()->format('Y-m-d') }}">
                            <input type="hidden" name="date_to" id="date_to" value="{{ $effectiveDateTo ?? request('date_to', now()->endOfMonth()->format('d-m-Y')) }}" data-default-ymd="{{ $effectiveDateToYmd ?? now()->endOfMonth()->format('Y-m-d') }}">
                        </div>
                        <div class="pmbe-filter-item" data-filter="status">
                            <select name="status" id="filterStatus" class="form-select pmbe-auto-filter">
                                <option value="">All Status</option>
                                <option value="unpaid" {{ $currentStatus === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                <option value="partial" {{ $currentStatus === 'partial' ? 'selected' : '' }}>Partial</option>
                                <option value="paid" {{ $currentStatus === 'paid' ? 'selected' : '' }}>Paid</option>
                            </select>
                        </div>
                        <div class="pmbe-filter-item" data-filter="buyer">
                            <select name="buyer_name[]" id="filterBuyerName" class="form-select choices-select pmbe-auto-filter" multiple data-placeholder="All Buyers">
                                @if(($clientType ?? request('client_type')) === 'ot' && isset($otBuyerNames) && $otBuyerNames->isNotEmpty())
                                    @foreach($otBuyerNames as $buyer)<option value="{{ $buyer }}" {{ in_array($buyer, $selectedBuyerNames, true) ? 'selected' : '' }}>{{ $buyer }}</option>@endforeach
                                @elseif(($clientType ?? request('client_type')) === 'course' && isset($courseBuyerNames) && $courseBuyerNames->isNotEmpty())
                                    @foreach($courseBuyerNames as $buyer)<option value="{{ $buyer }}" {{ in_array($buyer, $selectedBuyerNames, true) ? 'selected' : '' }}>{{ $buyer }}</option>@endforeach
                                @elseif(($clientType ?? request('client_type')) === 'other' && isset($otherBuyerNames) && $otherBuyerNames->isNotEmpty())
                                    @foreach($otherBuyerNames as $buyer)<option value="{{ $buyer }}" {{ in_array($buyer, $selectedBuyerNames, true) ? 'selected' : '' }}>{{ $buyer }}</option>@endforeach
                                @elseif(($clientType ?? request('client_type')) === 'section' && isset($sectionBuyerNames) && $sectionBuyerNames->isNotEmpty())
                                    @foreach($sectionBuyerNames as $buyer)<option value="{{ $buyer }}" {{ in_array($buyer, $selectedBuyerNames, true) ? 'selected' : '' }}>{{ $buyer }}</option>@endforeach
                                @endif
                            </select>
                        </div>
                        <div class="pmbe-filter-item" data-filter="invoice">
                            <select name="invoice_sent" id="filterInvoiceSent" class="form-select pmbe-auto-filter">
                                <option value="">All</option>
                                <option value="sent" {{ ($currentInvoiceSent ?? '') === 'sent' ? 'selected' : '' }}>Invoice Sent</option>
                            </select>
                        </div>
                        <div class="pmbe-filter-item" data-filter="client_type">
                            <select name="client_type[]" id="filterClientTypeSlug" class="form-select choices-select pmbe-auto-filter" multiple data-placeholder="All client types" data-clears="filterClientTypePk,filterBuyerName">
                                @foreach($clientTypes ?? [] as $key => $label)
                                    <option value="{{ $key }}" {{ in_array($key, $selectedClientTypes) ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pmbe-filter-item" data-filter="client_type_pk">
                            <select name="client_type_pk[]" id="filterClientTypePk" class="form-select choices-select pmbe-auto-filter" multiple data-placeholder="All" data-clears="filterBuyerName"></select>
                        </div>
                    </div>

                    {{-- Overflow: filters that don't fit collapse into this "+N Filter" popover --}}
                    <div class="dropdown flex-shrink-0 align-self-end d-none" id="pmbeMoreFilterWrap">
                        <a href="javascript:void(0)" class="pmbe-more-filters dropdown-toggle" id="pmbeMoreFilterToggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">+ Filter</a>
                        <div class="dropdown-menu pmbe-more-menu shadow border rounded-1">
                            <div class="pmbe-more-header mb-2 fw-semibold text-muted small">Filters</div>
                            <div id="pmbeMoreFilterItems"></div>
                        </div>
                    </div>

                    <a href="{{ route('admin.mess.process-mess-bills-employee.index') }}" class="programme-dt-btn-reset flex-shrink-0 align-self-center text-decoration-none" title="Remove all filters">Remove Filter</a>
                </form>
                <div class="d-flex align-items-center gap-2 ms-auto flex-shrink-0 align-self-center">
                    <button type="button" class="btn programme-dt-btn-columns" id="pmbeColumnsBtn" data-bs-toggle="modal" data-bs-target="#pmbeColumnVisibilityModal" title="Show / hide columns">
                        <i class="material-symbols-rounded">view_column</i><span>Columns</span>
                    </button>
                    <div class="programme-dt-search" data-dt-search-for="processMessBillsTable"></div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table programme-dt-table text-nowrap align-middle mb-0" id="processMessBillsTable" data-mess-datatable-server-side="1">
                    <thead class="table-light">
                        <tr>
                            <th class="text-nowrap py-2 mess-sort-th mess-report-sort-th" data-mess-col-original="S.No."><span class="d-inline-flex align-items-center gap-1"><span>S.No.</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                            <th class="text-nowrap py-2 mess-sort-th mess-report-sort-th mess-th-sorted" data-mess-col-original="Buyer Name"><span class="d-inline-flex align-items-center gap-1"><span>Buyer Name</span><span class="mess-report-sort-icon material-symbols-rounded" aria-hidden="true">arrow_upward</span></span></th>
                            <th class="text-nowrap py-2 mess-sort-th mess-report-sort-th" data-mess-col-original="Slip No."><span class="d-inline-flex align-items-center gap-1"><span>Slip No.</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                            <th class="text-nowrap py-2 mess-sort-th mess-report-sort-th" data-mess-col-original="Invoice Date"><span class="d-inline-flex align-items-center gap-1"><span>Invoice Date</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                            <th class="text-nowrap py-2 mess-sort-th mess-report-sort-th" data-mess-col-original="Client Type"><span class="d-inline-flex align-items-center gap-1"><span>Client Type</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                            <th class="text-nowrap py-2 text-end mess-sort-th mess-report-sort-th" data-mess-col-original="Total"><span class="d-inline-flex align-items-center gap-1"><span>Total</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                            <th class="text-nowrap py-2 text-end mess-sort-th mess-report-sort-th" data-mess-col-original="Total Due Amount"><span class="d-inline-flex align-items-center gap-1"><span>Total Due Amount</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                            <th class="text-nowrap py-2 mess-sort-th mess-report-sort-th" data-mess-col-original="Payment Type"><span class="d-inline-flex align-items-center gap-1"><span>Payment Type</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                            <th class="text-nowrap py-2 mess-sort-th mess-report-sort-th" data-mess-col-original="Status"><span class="d-inline-flex align-items-center gap-1"><span>Status</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
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
                                <td class="text-end fw-semibold">₹ {{ number_format($cb->total_due_amount ?? 0, 2) }}</td>
                                <td>{{ $cb->payment_type ?? '—' }}</td>
                                <td>
                                    @if(($cb->status ?? 0) == 2)
                                        <span class="pmbe-badge pmbe-badge--paid">Paid</span>
                                    @elseif(($cb->status ?? 0) == 1)
                                        <span class="pmbe-badge pmbe-badge--partial">Partial</span>
                                    @else
                                        <span class="pmbe-badge pmbe-badge--unpaid">Unpaid</span>
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
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="material-symbols-rounded d-block mb-3 text-primary" style="font-size: 4rem;">inbox</i>
                                    <div class="fw-semibold fs-5 mb-1">No bills found</div>
                                    <div class="small">Try adjusting your filters or date range</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3" data-dt-footer-for="processMessBillsTable"></div>
        </div>
    </div>

    {{-- Column Visibility Modal --}}
    <div class="modal fade" id="pmbeColumnVisibilityModal" tabindex="-1" aria-labelledby="pmbeColumnVisibilityLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="pmbeColumnVisibilityLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0"><hr class="mt-0"><div class="row g-3" id="pmbeColumnToggleGrid"></div></div>
                <div class="modal-footer border-0"><button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button></div>
            </div>
        </div>
    </div>
</div>


@include('components.mess-master-datatables', [
    'tableId' => 'processMessBillsTable',
    'searchPlaceholder' => 'Search name or invoice no.',
    'orderColumn' => [[1, 'asc']],
    'actionColumnIndex' => 9,
    'infoLabel' => 'bills',
    'searchDelay' => 500,
    'serverSide' => true,
    'ajaxUrlBase' => route('admin.mess.process-mess-bills-employee.index'),
    'ajaxJsonCallback' => 'applyProcessMessBillStats',
    'dom' => '<"dt-top"f>rt<"dt-foot"lip>',
    'columnManager' => true,
    'colReorder' => false,
    'columnManagerLocked' => [0],
    'columnManagerTitle' => 'Process Mess Bills columns',
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
                        <div class="payment-detail-row payment-detail-total-due-row">
                            <span class="payment-detail-label">Total Due Amount</span>
                            <span id="payNowTotalDueAmount" class="payment-detail-total-due-value">—</span>
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
@include('admin.mess.reports.partials.report-styles')
<div class="modal fade" id="addProcessMessBillsModal" tabindex="-1" aria-labelledby="addProcessMessBillsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-md-down modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-1">
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
                            <label class="form-label small fw-semibold text-dark mb-2"><i class="material-symbols-rounded align-middle me-1" style="font-size: 1rem;">event</i>Date From <span class="text-danger">*</span></label>
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
                            <select name="modal_client_type[]" id="modal_client_type" class="form-select shadow-sm border-0 choices-select" multiple data-placeholder="Select Client Types">
                                @foreach($clientTypes ?? [] as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-dark mb-2"><i class="material-symbols-rounded align-middle me-1" style="font-size: 1rem;">category</i>Client Type</label>
                            <select name="modal_client_type_pk[]" id="modal_client_type_pk" class="form-select choices-select" multiple data-placeholder="Select Client Types">
                            </select>
                        </div>
                        @php
                            $selectedModalBuyerNames = (array) ($buyerName ?? request('buyer_name', []));
                        @endphp
                        <div class="col-md-3">
                        <label class="form-label small fw-semibold text-dark mb-2"><i class="material-symbols-rounded align-middle me-1" style="font-size: 1.1rem;">badge</i>Buyer Name</label>
                        <select name="modal_buyer_name[]" id="modal_buyer_name" class="form-select choices-select" multiple data-placeholder="Select Buyers">
                            @if(($clientType ?? request('client_type')) === 'course' && isset($courseBuyerNames) && $courseBuyerNames->isNotEmpty())
                                @foreach($courseBuyerNames as $buyer)
                                    <option value="{{ $buyer }}" {{ in_array($buyer, $selectedModalBuyerNames, true) ? 'selected' : '' }}>{{ $buyer }}</option>
                                @endforeach
                            @elseif(($clientType ?? request('client_type')) === 'other' && isset($otherBuyerNames) && $otherBuyerNames->isNotEmpty())
                                @foreach($otherBuyerNames as $buyer)
                                    <option value="{{ $buyer }}" {{ in_array($buyer, $selectedModalBuyerNames, true) ? 'selected' : '' }}>{{ $buyer }}</option>
                                @endforeach
                            @elseif(($clientType ?? request('client_type')) === 'section' && isset($sectionBuyerNames) && $sectionBuyerNames->isNotEmpty())
                                @foreach($sectionBuyerNames as $buyer)
                                    <option value="{{ $buyer }}" {{ in_array($buyer, $selectedModalBuyerNames, true) ? 'selected' : '' }}>{{ $buyer }}</option>
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
                <div class="d-none align-items-center gap-2 mb-3 p-3 rounded-1 bg-light border border-primary border-opacity-25" id="modalBulkActionsBar">
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
                        <span id="messColManagerMount-modalBillsTable" class="d-inline-block"></span>
                        <button type="button" class="btn btn-outline-primary shadow-sm btn-sm d-inline-flex align-items-center gap-2 px-3" onclick="printProcessMessBillsTable()" title="Print bills list">
                            <i class="material-symbols-rounded align-middle" style="font-size: 1rem;">print</i>
                            <span>Print</span>
                        </button>
                    </div>
                </div>

                <div id="modalBillsTableHost" class="table-responsive">
                <table id="modalBillsTable"
                       class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap py-3 fw-semibold" style="width: 40px;" data-mess-col-original="Select"><input type="checkbox" id="modalSelectAll" class="form-check-input" title="Select all"></th>
                                <th class="text-nowrap py-3 fw-semibold mess-sort-th mess-report-sort-th" data-sort="sno" data-mess-col-original="S.No."><span class="d-inline-flex align-items-center gap-1"><span>S.No.</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                                <th class="text-nowrap py-3 fw-semibold mess-sort-th mess-report-sort-th mess-th-sorted" data-sort="buyer_name" data-mess-col-original="Buyer Name"><span class="d-inline-flex align-items-center gap-1"><span>Buyer Name</span><span class="mess-report-sort-icon material-symbols-rounded" aria-hidden="true">arrow_upward</span></span></th>
                                <th class="text-nowrap py-3 fw-semibold mess-sort-th mess-report-sort-th" data-sort="invoice_no" data-mess-col-original="Invoice No."><span class="d-inline-flex align-items-center gap-1"><span>Invoice No.</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                                <th class="text-nowrap py-3 fw-semibold mess-sort-th mess-report-sort-th" data-sort="payment_type" data-mess-col-original="Payment Type"><span class="d-inline-flex align-items-center gap-1"><span>Payment Type</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                                <th class="text-nowrap py-3 fw-semibold text-end mess-sort-th mess-report-sort-th" data-sort="total" data-mess-col-original="Total"><span class="d-inline-flex align-items-center gap-1"><span>Total</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                                <th class="text-nowrap py-3 fw-semibold text-end mess-sort-th mess-report-sort-th" data-sort="total_due_amount" data-mess-col-original="Total Due Amount"><span class="d-inline-flex align-items-center gap-1"><span>Total Due Amount</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                                <th class="text-nowrap py-3 fw-semibold text-center mess-sort-th mess-report-sort-th" data-sort="status" data-mess-col-original="Status"><span class="d-inline-flex align-items-center gap-1"><span>Status</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                                <th class="text-nowrap py-3 fw-semibold text-center" data-mess-col-original="Actions">Actions</th>
                                <th class="text-nowrap py-3 fw-semibold text-center" data-mess-col-original="Receipt">Receipt</th>
                            </tr>
                        </thead>
                        <tbody id="modalBillsTableBody">
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="material-symbols-rounded d-block mb-2 text-primary" style="font-size: 3rem;">description</i>
                                    <div class="fw-semibold">Select date range and click <strong class="text-primary">Load Bills</strong> to load unpaid bills.</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3 pt-3 border-top">
                    <div class="small text-muted fw-semibold" id="modalPaginationInfo">Showing 0 to 0 of 0 entries</div>
                    <nav id="modalPaginationNav" class="d-none" aria-label="Bills list pages">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item" id="modalPaginationPrevLi"><button type="button" class="page-link py-1 px-2" id="modalPaginationPrev">Previous</button></li>
                            <li class="page-item disabled" id="modalPaginationPageLi"><span class="page-link py-1 px-2" id="modalPaginationPageLabel">Page 1 of 1</span></li>
                            <li class="page-item" id="modalPaginationNextLi"><button type="button" class="page-link py-1 px-2" id="modalPaginationNext">Next</button></li>
                        </ul>
                    </nav>
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

{{-- Page JS lives in public/js/process-mess-bills-employee.js; PHP-derived values injected via window.PMBE_CFG.
     (The print functions below stay inline: they embed literal </script> markup that relies on inline-script parsing.) --}}
@php
    $__pmbeClientTypeOptions = [];
    foreach (($clientTypes ?? []) as $__pmbeK => $__pmbeLabel) {
        $__pmbeClientTypeOptions[$__pmbeK] = [];
        if (isset($clientTypeCategories[$__pmbeK])) {
            foreach ($clientTypeCategories[$__pmbeK] as $__pmbeCat) {
                $__pmbeClientTypeOptions[$__pmbeK][] = [
                    'value' => (string) $__pmbeCat->id,
                    'text' => (string) $__pmbeCat->client_name,
                    'dataClientName' => strtolower((string) ($__pmbeCat->client_name ?? '')),
                ];
            }
        }
    }
    $__pmbeOtCourseOptions = [];
    if (isset($otCourses)) {
        foreach ($otCourses as $__pmbeCourse) {
            $__pmbeOtCourseOptions[] = ['value' => (string) $__pmbeCourse->pk, 'text' => (string) $__pmbeCourse->course_name];
        }
    }
@endphp
<script>
    window.PMBE_CFG = {
        paymentDetailsUrlTemplate: @json(route('admin.mess.process-mess-bills-employee.payment-details', ['id' => '__ID__'])),
        printReceiptUrlTemplate: @json(route('admin.mess.process-mess-bills-employee.print-receipt', ['id' => '__ID__'])),
        generateInvoiceBaseUrl: @json(url('admin/mess/process-mess-bills-employee')),
        modalDataUrl: @json(route('admin.mess.process-mess-bills-employee.modal-data')),
        studentsByCourseUrl: @json(url('/admin/mess/selling-voucher-date-range/students-by-course')),
        buyersForReportUrl: @json(route('admin.mess.reports.category-wise-print-slip.buyers')),
        courseBuyersByCourseUrl: @json(url('/admin/mess/reports/category-wise-print-slip/course-buyers')),
        indexFormAction: @json(route('admin.mess.process-mess-bills-employee.index')),
        defaultDateFrom: @json(now()->startOfMonth()->format('d-m-Y')),
        defaultDateTo: @json(now()->endOfMonth()->format('d-m-Y')),
        defaultInvoiceDate: @json(now()->format('d-m-Y')),
        clientTypeOptions: @json($__pmbeClientTypeOptions, JSON_UNESCAPED_UNICODE),
        otCourseOptions: @json($__pmbeOtCourseOptions, JSON_UNESCAPED_UNICODE),
        employeeNamesByStaffType: {
            'academy staff': @json($filterEmployeeBuyerOptions ?? [], JSON_UNESCAPED_UNICODE),
            'faculty': @json($filterFacultyBuyerOptions ?? [], JSON_UNESCAPED_UNICODE),
            'mess staff': @json($filterMessStaffBuyerOptions ?? [], JSON_UNESCAPED_UNICODE)
        },
        allBuyerNames: @json(($allBuyerNames ?? collect())->values()->all(), JSON_UNESCAPED_UNICODE),
        otBuyerNames: @json(($otBuyerNames ?? collect())->values()->all(), JSON_UNESCAPED_UNICODE),
        courseBuyerNames: @json(($courseBuyerNames ?? collect())->values()->all(), JSON_UNESCAPED_UNICODE),
        otherBuyerNames: @json(($otherBuyerNames ?? collect())->values()->all(), JSON_UNESCAPED_UNICODE),
        sectionBuyerNames: @json(($sectionBuyerNames ?? collect())->values()->all(), JSON_UNESCAPED_UNICODE),
        preservedClientTypePk: @json((array) ($clientTypePks ?? request('client_type_pk', []))),
        preservedBuyerName: @json((array) ($buyerNames ?? request('buyer_name', []))),
        periodText: @json('Period: ' . ($dateFromDisplay ?? '') . ' to ' . ($dateToDisplay ?? ''))
    };
</script>
<script src="{{ asset('js/process-mess-bills-employee.js') }}?v={{ @filemtime(public_path('js/process-mess-bills-employee.js')) }}"></script>

<script>
function printProcessMessBillsMainTable() {
    if (window.MessColumnManager && typeof window.MessColumnManager.printDataTable === 'function') {
        window.MessColumnManager.printDataTable('processMessBillsTable', {
            template: 'lbsnaa',
            title: 'Process Mess Bills - Employee',
            periodText: 'Period: {{ $dateFromDisplay }} to {{ $dateToDisplay }}'
        });
        return;
    }
    if (window.alert) {
        window.alert('Print is not available. Please refresh the page and try again.');

    // Remove action column (last column) for print
    var actionColIdx = 9;

    var originalThead = table.querySelector('thead');
    var headerCells = originalThead ? Array.from(originalThead.querySelectorAll('tr th')) : [];
    var printHeaderCells = headerCells.filter(function (_, idx) { return idx !== actionColIdx; });
    var headerHtml = '<tr>' + printHeaderCells.map(function (th) { return '<th>' + th.innerHTML + '</th>'; }).join('') + '</tr>';

    var bodyRowsHtml = rowsData.map(function (row) {
        var cells = Array.isArray(row) ? row : (row && row.length != null ? Array.from(row) : []);
        var filteredCells = cells.filter(function (_, idx) { return idx !== actionColIdx; });
        return '<tr>' + filteredCells.map(function (c) { return '<td>' + c + '</td>'; }).join('') + '</tr>';
    }).join('');

    var columnsCount = printHeaderCells.length || 9;
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
}

function printProcessMessBillsTable() {
    var table = document.getElementById('modalBillsTable');
    if (!table) {
        window.print();
        return;
    }

    function openModalPrintWithBills(bills) {
        var dateFrom = document.getElementById('modal_date_from')?.value || '';
        var dateTo   = document.getElementById('modal_date_to')?.value || '';

        var title = 'Process Mess Bills - Invoice & Payment';
        var periodText = dateFrom || dateTo
            ? ('From ' + (dateFrom || 'Start') + ' To ' + (dateTo || 'End'))
            : 'All Dates';

        var originalThead = table.querySelector('thead');
        var headerRow = originalThead ? originalThead.querySelector('tr') : null;
        var headerCells = headerRow ? Array.from(headerRow.children) : [];

    // Build printable table with LBSNAA header inside thead so it repeats on every page
    // Print ALL rows from modal dataset (not only current "per page" view)
    var originalThead = table.querySelector('thead');
    var headerRow = originalThead ? originalThead.querySelector('tr') : null;
    var headerCells = headerRow ? Array.from(headerRow.children) : [];
    // Remove Checkbox (0), Actions (8) and Receipt (9) columns from print
    var removeIdx = { 0: true, 8: true, 9: true };
    var printHeaderCells = headerCells.filter(function (_, idx) { return !removeIdx[idx]; });
    var columnsCount = printHeaderCells.length || 7;
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
            '<td class="text-end">' + (b.total_due_amount || '0.00') + '</td>' +
            '<td>' + (b && b.invoice_notification_sent ? ('Invoice Sent · ' + (b.invoice_notification_read ? 'Read' : 'Unread')) : '—') + '</td>' +
            '</tr>';
    }).join('');

        function modalBillPrintCell(b, idx, sn) {
            switch (idx) {
                case 1: return String(sn);
                case 2: return b.buyer_name || '—';
                case 3: return b.invoice_no || '—';
                case 4: return b.payment_type || '—';
                case 5: return b.total || '0';
                case 6:
                    return (typeof window.formatInvoiceNotificationStatusText === 'function')
                        ? window.formatInvoiceNotificationStatusText(b)
                        : '—';
                default: return '';
            }
        }

        var filtered = bills || [];
        var bodyHtml = filtered.map(function (b, i) {
            var sn = b.sno || (i + 1);
            return '<tr>' + printColIndexes.map(function (idx) {
                var cls = idx === 5 ? ' class="text-end"' : (idx === 6 ? ' class="text-center"' : '');
                return '<td' + cls + '>' + modalBillPrintCell(b, idx, sn) + '</td>';
            }).join('') + '</tr>';
        }).join('');

        if (!bodyHtml) {
            if (window.alert) {
                window.alert('No bills to print. Load bills first or adjust your filters.');
            }
            return;
        }

        var printWindow = window.open('', '_blank');
        if (!printWindow) {
            window.print();
            return;
        }

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
    ${(window.MessColumnManager && window.MessColumnManager.MESS_PRINT_SUPPRESS_ICON_CSS) || ''}
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

    if (typeof window.buildModalBillsDataUrl === 'function') {
        fetch(window.buildModalBillsDataUrl({ forPrint: true }))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                openModalPrintWithBills(data.bills || []);
            })
            .catch(function () {
                var fallback = (typeof getFilteredModalBills === 'function') ? getFilteredModalBills() : [];
                openModalPrintWithBills(fallback);
            });
        return;
    }

    openModalPrintWithBills((typeof getFilteredModalBills === 'function') ? getFilteredModalBills() : []);
}
</script>
@endpush

@endsection

@push('styles')
    {{-- Choices.js via CDN --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <link rel="stylesheet" href="{{ asset('css/process-mess-bills-employee.css') }}?v={{ @filemtime(public_path('css/process-mess-bills-employee.css')) }}">
@endpush

@push('scripts')
    {{-- Choices.js via CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
@endpush
