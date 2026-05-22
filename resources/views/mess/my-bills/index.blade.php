@extends('admin.layouts.master')
@section('title', 'My Mess Bills')
@section('content')
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
<div class="container-fluid py-3 py-md-4 process-mess-bills-employee-report mess-my-bills-page">
    <x-breadcrum title="My Mess Bills"></x-breadcrum>

    <div class="report-header text-center mb-4">
        <h4 class="fw-bold">My Mess Bills</h4>
        <p class="mb-1">Period: {{ $dateFromDisplay }} to {{ $dateToDisplay }}</p>
        <p class="text-muted mb-0 small">Generated on: {{ now()->format('d-m-Y H:i:s') }}</p>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4 no-print p-4 rounded-3 shadow-sm" style="background: #004a93; color: white;">
        <div>
            <h4 class="mb-2 fw-bold d-flex align-items-center gap-2">
                <i class="material-symbols-rounded" style="font-size: 2rem;">receipt_long</i>
                My Mess Bills
            </h4>
            <p class="mb-0 small opacity-90 text-white">View your mess bill totals and line items (with dates) for the selected period. Use the same date range as Process Mess Bills.</p>
        </div>
    </div>

    <div class="no-print">
        @php $stats = $stats ?? ['total_bills' => 0, 'paid_count' => 0, 'unpaid_count' => 0, 'total_amount' => 0]; @endphp
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 mb-4">
            <div class="col">
                <div class="card border-0 shadow h-100">
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
                <div class="card border-0 shadow h-100">
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
                <div class="card border-0 shadow h-100">
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
                <div class="card border-0 shadow h-100">
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

    <div class="card border-0 shadow mb-4 no-print" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
        <div class="card-body p-3 p-lg-4">
            <form method="GET" action="{{ route('admin.mess.my-bills.index') }}" id="myBillsFilterForm">
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
                    <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="material-symbols-rounded align-middle">filter_list</i>
                            Apply
                        </button>
                        <a href="{{ route('admin.mess.my-bills.index') }}" class="btn btn-outline-secondary shadow-sm" title="Clear filters">
                            <i class="material-symbols-rounded" style="font-size: 1.1rem;">filter_list_off</i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow">
        <div class="card-body p-3 p-lg-4">
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover text-nowrap align-middle mb-0" id="myMessBillsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="py-2">S.No.</th>
                            <th class="py-2">Slip No.</th>
                            <th class="py-2">Invoice Date</th>
                            <th class="py-2">Client Type</th>
                            <th class="py-2 text-end">Total</th>
                            <th class="py-2">Payment Type</th>
                            <th class="py-2">Status</th>
                            <th class="py-2 text-center no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($combinedBills ?? [] as $index => $cb)
                            <tr class="{{ ($cb->status ?? 0) == 2 ? '' : 'table-warning table-warning-subtle' }}">
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $cb->combined_invoice_no ?? '—' }}</td>
                                <td>{{ $cb->invoice_date_range ?? '—' }}</td>
                                <td>{{ $cb->client_type_display ?? '—' }}</td>
                                <td class="text-end fw-semibold">₹ {{ number_format($cb->total ?? 0, 2) }}</td>
                                <td>{{ $cb->payment_type ?? '—' }}</td>
                                <td>
                                    @if(($cb->status ?? 0) == 2)
                                        <span class="badge rounded-pill text-bg-success shadow-sm px-3 py-2">Paid</span>
                                    @elseif(($cb->status ?? 0) == 1)
                                        <span class="badge rounded-pill text-bg-warning text-dark shadow-sm px-3 py-2">Partial</span>
                                    @else
                                        <span class="badge rounded-pill text-bg-secondary shadow-sm px-3 py-2">Unpaid</span>
                                    @endif
                                </td>
                                <td class="text-center no-print">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary shadow-sm my-bills-details-btn"
                                            data-bill-id="{{ $cb->combined_id }}"
                                            data-date-from-ymd="{{ $effectiveDateFromYmd ?? '' }}"
                                            data-date-to-ymd="{{ $effectiveDateToYmd ?? '' }}">
                                        <i class="material-symbols-rounded" style="font-size: 1.1rem;">visibility</i>
                                        <span class="d-none d-sm-inline">Details</span>
                                    </button>
                                    <a href="{{ route('admin.mess.process-mess-bills-employee.print-receipt', ['id' => $cb->combined_id]) }}?date_from={{ urlencode($effectiveDateFromYmd ?? '') }}&date_to={{ urlencode($effectiveDateToYmd ?? '') }}"
                                       target="_blank"
                                       class="btn btn-sm btn-outline-secondary shadow-sm d-inline-flex align-items-center gap-1 px-2"
                                       title="Print receipt">
                                        <i class="material-symbols-rounded" style="font-size: 1.1rem;">print</i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="material-symbols-rounded d-block mb-3 text-primary" style="font-size: 4rem;">inbox</i>
                                    <div class="fw-semibold fs-5 mb-1">No bills found</div>
                                    <div class="small">Try another date range, or confirm your mess account is linked to your employee or student record.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade my-bill-details-modal" id="myBillDetailsModal" tabindex="-1" aria-labelledby="myBillDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content bill-receipt-modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 my-bill-modal-header text-white py-3 px-3 px-md-4 align-items-center">
                <div class="d-flex align-items-center gap-3 min-w-0 me-2">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-white bg-opacity-25 flex-shrink-0 my-bill-modal-header-icon" aria-hidden="true">
                        <span class="material-symbols-rounded text-white">receipt_long</span>
                    </span>
                    <div class="min-w-0">
                        <h5 class="modal-title fw-semibold mb-0 lh-sm" id="myBillDetailsModalLabel">Bill details</h5>
                        <p class="small mb-0 opacity-75 text-truncate" id="myBillDetailsModalSubtitle">Line items and payment summary</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white flex-shrink-0" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bill-receipt-modal-body p-0">
                <div class="my-bill-modal-body-inner p-3 p-md-4">
                    <div id="myBillDetailsContent" class="bill-receipt-content bg-body rounded-4 border border-light-subtle shadow-sm p-3 p-md-4 my-bill-receipt-panel" aria-live="polite">
                        <div class="d-flex flex-column align-items-center justify-content-center py-5 text-body-secondary my-bill-receipt-loading" role="status">
                            <div class="spinner-border text-primary mb-3" style="width: 2.5rem; height: 2.5rem;" aria-hidden="true"></div>
                            <span class="fw-medium">Loading bill details…</span>
                            <span class="small text-body-secondary mt-1">Fetching line items and totals</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bill-receipt-actions border-top border-light-subtle bg-body-tertiary flex-wrap justify-content-between align-items-center gap-3 py-3 px-3 px-md-4">
                <div class="d-flex align-items-start gap-2 small text-body-secondary mb-0 flex-grow-1 min-w-0">
                    <span class="material-symbols-rounded flex-shrink-0 text-primary" style="font-size: 1.15rem;" aria-hidden="true">info</span>
                    <span class="d-none d-md-inline">Print opens the official mess receipt in a new tab for your records.</span>
                    <span class="d-inline d-md-none">Tap Print for the official receipt.</span>
                </div>
                <div class="btn-toolbar gap-2 flex-shrink-0" role="toolbar" aria-label="Bill actions">
                    <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 btn-receipt-print" id="myBillDetailsPrintBtn">
                        <span class="material-symbols-rounded" style="font-size: 1.15rem;" aria-hidden="true">print</span>
                        <span>Print receipt</span>
                    </button>
                    <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 px-4 btn-receipt-cancel" data-bs-dismiss="modal">
                        <span class="material-symbols-rounded" style="font-size: 1.15rem;" aria-hidden="true">close</span>
                        <span>Close</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@include('components.mess-master-datatables', [
    'tableId' => 'myMessBillsTable',
    'searchPlaceholder' => 'Search invoice no. or type',
    'orderColumn' => [[0, 'asc']],
    'actionColumnIndex' => 7,
    'infoLabel' => 'bills',
])
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
@media screen { .mess-my-bills-page .report-header { display: none; } }
@media print {
    .mess-my-bills-page .no-print { display: none !important; }
}
/* My Bills – bill details modal */
.my-bill-details-modal .modal-dialog { max-width: 760px; }
.my-bill-details-modal .my-bill-modal-header {
    background: linear-gradient(135deg, #004a93 0%, #0a3d6b 100%);
}
.my-bill-details-modal .my-bill-modal-header-icon { width: 2.75rem; height: 2.75rem; }
.my-bill-details-modal .my-bill-modal-header-icon .material-symbols-rounded { font-size: 1.5rem; }
.my-bill-details-modal .bill-receipt-modal-body {
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    background: linear-gradient(180deg, var(--bs-tertiary-bg) 0%, var(--bs-body-bg) 100%);
}
.my-bill-details-modal .my-bill-receipt-panel { min-height: 12rem; }
.my-bill-details-modal .bill-receipt-actions { margin-top: 0; }
#myBillDetailsModal .bill-receipt-content { color: var(--bs-body-color); }
#myBillDetailsModal .bill-receipt-content .receipt-top {
    display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 0.75rem;
}
#myBillDetailsModal .bill-receipt-content .receipt-logo { display: inline-flex; align-items: center; gap: 0.4rem; }
#myBillDetailsModal .bill-receipt-content .receipt-logo-icon {
    width: 1.25rem; height: 1.25rem; flex-shrink: 0;
    background: linear-gradient(135deg, #c00 0%, #a00 50%, #800 100%);
    clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%);
}
#myBillDetailsModal .bill-receipt-content .receipt-logo-text {
    font-size: 1.1rem; font-weight: 700; color: #0a3d6b; letter-spacing: 0.02em;
}
#myBillDetailsModal .bill-receipt-content .receipt-date { font-size: 0.875rem; color: var(--bs-secondary-color); white-space: nowrap; }
#myBillDetailsModal .bill-receipt-content .receipt-center { text-align: center; margin: 1rem 0; padding: 0 0.5rem; }
#myBillDetailsModal .bill-receipt-content .receipt-title {
    font-size: 1.2rem; font-weight: 700; color: #0a3d6b; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 0.25rem;
}
#myBillDetailsModal .bill-receipt-content .receipt-subtitle {
    font-size: 0.95rem; font-weight: 700; color: #c00; text-transform: uppercase; letter-spacing: 0.02em; margin-bottom: 0.35rem;
}
#myBillDetailsModal .bill-receipt-content .receipt-period { font-size: 0.9rem; font-weight: 600; color: #004a93; }
#myBillDetailsModal .bill-receipt-content .receipt-divider {
    border: 0; border-top: 1px solid var(--bs-border-color); margin: 0.75rem 0; opacity: 0.65;
}
#myBillDetailsModal .bill-receipt-content .receipt-meta-grid { --bs-gutter-y: 0.5rem; }
#myBillDetailsModal .bill-receipt-content .receipt-meta-item {
    font-size: 0.875rem; padding: 0.35rem 0;
}
#myBillDetailsModal .bill-receipt-content .client-label { font-weight: 600; color: var(--bs-secondary-color); }
#myBillDetailsModal .bill-receipt-content .client-value { font-weight: 500; color: var(--bs-body-color); }
#myBillDetailsModal .bill-receipt-content .bill-table-wrap { margin: 0.75rem 0; }
#myBillDetailsModal .bill-receipt-content .bill-table { font-size: 0.875rem; margin-bottom: 0; }
#myBillDetailsModal .bill-receipt-content .bill-table thead th {
    font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.02em;
    background: var(--bs-tertiary-bg); color: var(--bs-emphasis-color);
}
#myBillDetailsModal .bill-receipt-content .receipt-bottom { margin-top: 1rem; }
#myBillDetailsModal .bill-receipt-content .payment-summary-card { max-width: 280px; margin-left: auto; }
#myBillDetailsModal .bill-receipt-content .payment-summary-card .list-group-item {
    display: flex; justify-content: space-between; align-items: baseline; gap: 0.75rem; padding: 0.5rem 0.85rem;
}
#myBillDetailsModal .bill-receipt-content .payment-summary-card .summary-label { font-weight: 600; color: var(--bs-secondary-color); }
#myBillDetailsModal .bill-receipt-content .payment-summary-card .summary-value { font-weight: 600; font-variant-numeric: tabular-nums; text-align: right; }
#myBillDetailsModal .bill-receipt-content .payment-summary-card .summary-row-due .summary-value { color: var(--bs-danger); font-weight: 700; }
@media (max-width: 575.98px) {
    .my-bill-details-modal .modal-footer .btn-toolbar { width: 100%; }
    .my-bill-details-modal .modal-footer .btn-toolbar .btn { flex: 1 1 auto; justify-content: center; }
    #myBillDetailsModal .bill-receipt-content .payment-summary-card { max-width: 100%; margin-left: 0; }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
(function () {
    var paymentDetailsUrl = @json(route('admin.mess.process-mess-bills-employee.payment-details', ['id' => '__ID__']));
    var printReceiptBaseUrl = @json(route('admin.mess.process-mess-bills-employee.print-receipt', ['id' => '__ID__']));
    var myBillDetailsBillId = null;
    var myBillDetailsDateFrom = null;
    var myBillDetailsDateTo = null;

    function setMyBillModalSubtitle(data) {
        var sub = document.getElementById('myBillDetailsModalSubtitle');
        if (!sub) return;
        if (!data || data.error) {
            sub.textContent = 'Line items and payment summary';
            return;
        }
        var parts = [];
        if (data.invoice_no) parts.push('Invoice ' + data.invoice_no);
        if (data.date_from && data.date_to) parts.push(data.date_from + ' – ' + data.date_to);
        sub.textContent = parts.length ? parts.join(' · ') : 'Line items and payment summary';
    }

    function myBillLoadingHtml() {
        return '<div class="d-flex flex-column align-items-center justify-content-center py-5 text-body-secondary my-bill-receipt-loading" role="status">' +
            '<div class="spinner-border text-primary mb-3" style="width: 2.5rem; height: 2.5rem;" aria-hidden="true"></div>' +
            '<span class="fw-medium">Loading bill details…</span>' +
            '<span class="small text-body-secondary mt-1">Fetching line items and totals</span></div>';
    }

    function renderPaymentDetailsContent(data) {
        var dateStr = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-');
        var timeStr = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false });
        var items = data.items || [];
        var rows = items.map(function (item) {
            var issue = item.issue_date || item.purchase_date || '—';
            return '<tr><td>' + (item.store_name || '—') + '</td><td>' + (item.item_name || '—') + '</td><td class="text-nowrap">' + issue + '</td>' +
                '<td class="text-end tabular-nums">' + (item.price || '0') + '</td><td class="text-end tabular-nums">' + (item.quantity || '0') + '</td>' +
                '<td class="text-end tabular-nums fw-medium">' + (item.amount || '0') + '</td></tr>';
        }).join('');
        if (!rows) {
            rows = '<tr><td colspan="6" class="text-center text-body-secondary py-4">No line items for this bill.</td></tr>';
        }
        var clientNameCourse = data.client_name_course || (function () {
            if (data.course_name) {
                return (data.client_name || '—') + ' – ' + data.course_name;
            }
            return data.client_name || '—';
        })();
        var hasRefOrOrder = !!(data.reference_number || data.order_by);
        function metaCol(label, value) {
            return '<div class="col-sm-6 receipt-meta-item"><span class="client-label d-block small text-uppercase">' + label + '</span>' +
                '<span class="client-value">' + (value || '—') + '</span></div>';
        }
        return '<div class="receipt-top">' +
            '<div class="receipt-logo"><span class="receipt-logo-icon" aria-hidden="true"></span><span class="receipt-logo-text">Sargam</span></div>' +
            '<span class="receipt-date badge text-bg-light border border-light-subtle fw-normal">Date ' + dateStr + ' ' + timeStr + '</span></div>' +
            '<div class="receipt-center">' +
            '<div class="receipt-title">OFFICER\'S MESS LBSNAA MUSSOORIE</div>' +
            '<div class="receipt-subtitle">MESS BILLS</div>' +
            '<div class="receipt-period">Client Bill From Period ' + (data.date_from || '') + ' To ' + (data.date_to || '') + '</div></div>' +
            '<hr class="receipt-divider"/>' +
            '<div class="row receipt-meta-grid g-2">' +
            metaCol('Receipt No', data.receipt_no) + metaCol('Invoice No', data.invoice_no) +
            metaCol('Client Name', clientNameCourse) + metaCol('Client Type', data.client_type) +
            (hasRefOrOrder ? (
                (data.reference_number ? metaCol('Reference Number', data.reference_number) : '') +
                (data.order_by ? metaCol('Order By', data.order_by) : '')
            ) : '') +
            (data.remarks ? metaCol('Remarks', data.remarks) : '') +
            '</div>' +
            '<hr class="receipt-divider"/>' +
            '<div class="table-responsive bill-table-wrap rounded-3 border border-light-subtle">' +
            '<table class="table table-sm table-hover table-bordered align-middle bill-table mb-0">' +
            '<thead class="table-light"><tr><th scope="col">Store</th><th scope="col">Item</th><th scope="col">Issue Date</th>' +
            '<th scope="col" class="text-end">Price</th><th scope="col" class="text-end">Qty</th><th scope="col" class="text-end">Amount</th></tr></thead><tbody>' + rows + '</tbody></table></div>' +
            '<div class="receipt-bottom d-flex justify-content-end">' +
            '<div class="card payment-summary-card border shadow-sm">' +
            '<div class="card-header py-2 px-3 bg-primary-subtle border-0"><span class="small fw-semibold text-primary-emphasis text-uppercase">Payment summary</span></div>' +
            '<ul class="list-group list-group-flush">' +
            '<li class="list-group-item"><span class="summary-label">Paid Amount</span><span class="summary-value">' + (data.paid_amount || '0.0') + '</span></li>' +
            '<li class="list-group-item"><span class="summary-label">Total Amount</span><span class="summary-value">' + (data.total_amount || '0.0') + '</span></li>' +
            '<li class="list-group-item summary-row-due"><span class="summary-label">Due Amount</span><span class="summary-value">' + (data.due_amount || '0.0') + '</span></li>' +
            '</ul></div></div>';
    }

    function openMyBillDetails(billId, dateFromYmd, dateToYmd) {
        myBillDetailsBillId = billId;
        myBillDetailsDateFrom = dateFromYmd || null;
        myBillDetailsDateTo = dateToYmd || null;
        var content = document.getElementById('myBillDetailsContent');
        var modalEl = document.getElementById('myBillDetailsModal');
        setMyBillModalSubtitle(null);
        if (content) content.innerHTML = myBillLoadingHtml();
        if (modalEl && typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
        var url = paymentDetailsUrl.replace('__ID__', encodeURIComponent(billId));
        if (String(billId).indexOf('combined-') === 0 && (myBillDetailsDateFrom || myBillDetailsDateTo)) {
            var params = [];
            if (myBillDetailsDateFrom) params.push('date_from=' + encodeURIComponent(myBillDetailsDateFrom));
            if (myBillDetailsDateTo) params.push('date_to=' + encodeURIComponent(myBillDetailsDateTo));
            if (params.length) url += (url.indexOf('?') >= 0 ? '&' : '?') + params.join('&');
        }
        fetch(url, { headers: { 'Accept': 'application/json' } }).then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error) {
                    setMyBillModalSubtitle(null);
                    if (content) content.innerHTML = '<div class="alert alert-danger d-flex align-items-center gap-2 mb-0 shadow-sm" role="alert">' +
                        '<span class="material-symbols-rounded flex-shrink-0" aria-hidden="true">error</span>' +
                        '<span>' + (data.error || 'Failed to load.') + '</span></div>';
                    return;
                }
                setMyBillModalSubtitle(data);
                if (content) content.innerHTML = renderPaymentDetailsContent(data);
            })
            .catch(function () {
                setMyBillModalSubtitle(null);
                if (content) content.innerHTML = '<div class="alert alert-danger d-flex align-items-center gap-2 mb-0 shadow-sm" role="alert">' +
                    '<span class="material-symbols-rounded flex-shrink-0" aria-hidden="true">error</span>' +
                    '<span>Failed to load bill details.</span></div>';
            });
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.my-bills-details-btn');
        if (!btn) return;
        e.preventDefault();
        openMyBillDetails(
            btn.getAttribute('data-bill-id'),
            btn.getAttribute('data-date-from-ymd'),
            btn.getAttribute('data-date-to-ymd')
        );
    });

    var printBtn = document.getElementById('myBillDetailsPrintBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            var receiptId = myBillDetailsBillId;
            if (!receiptId) return;
            var printUrl = printReceiptBaseUrl.replace('__ID__', encodeURIComponent(receiptId));
            if (String(receiptId).indexOf('combined-') === 0 && (myBillDetailsDateFrom || myBillDetailsDateTo)) {
                printUrl += (printUrl.indexOf('?') >= 0 ? '&' : '?') + 'date_from=' + encodeURIComponent(myBillDetailsDateFrom || '') + '&date_to=' + encodeURIComponent(myBillDetailsDateTo || '');
            }
            window.open(printUrl, '_blank');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var df = document.getElementById('date_from');
        var dt = document.getElementById('date_to');
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
        if (typeof flatpickr !== 'undefined') {
            var defaultFrom = (df && df.getAttribute('data-default-ymd')) ? ymdToDate(df.getAttribute('data-default-ymd')) : (df && df.value ? dmyToDate(df.value) : null);
            var defaultTo = (dt && dt.getAttribute('data-default-ymd')) ? ymdToDate(dt.getAttribute('data-default-ymd')) : (dt && dt.value ? dmyToDate(dt.value) : null);
            flatpickr('#date_from', { dateFormat: 'd-m-Y', allowInput: true, defaultDate: defaultFrom });
            flatpickr('#date_to', { dateFormat: 'd-m-Y', allowInput: true, defaultDate: defaultTo });
        }
        function toYmd(val) {
            if (!val || !String(val).match(/^\d{1,2}-\d{1,2}-\d{4}$/)) return val;
            var p = String(val).split('-');
            return p[2] + '-' + p[1] + '-' + p[0];
        }
        var form = document.getElementById('myBillsFilterForm');
        if (form) {
            form.addEventListener('submit', function () {
                if (df && df.value) df.value = toYmd(df.value) || df.value;
                if (dt && dt.value) dt.value = toYmd(dt.value) || dt.value;
            });
        }
    });
})();
</script>
@endpush
