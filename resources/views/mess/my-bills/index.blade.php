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
<div class="container-fluid process-mess-bills-employee-report mess-my-bills-page">
    <x-breadcrum title="My Mess Bills"></x-breadcrum>

    <div class="report-header text-center mb-4">
        <h4 class="fw-bold">My Mess Bills</h4>
        <p class="mb-1">Period: {{ $dateFromDisplay }} to {{ $dateToDisplay }}</p>
        <p class="text-muted mb-0 small">Generated on: {{ now()->format('d-m-Y H:i:s') }}</p>
    </div>

    <div class="no-print">
        @php $stats = $stats ?? ['total_bills' => 0, 'paid_count' => 0, 'unpaid_count' => 0, 'total_amount' => 0]; @endphp
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 mb-4">
            <div class="col">
                <div class="im-stat">
                    <div class="im-stat-icon im-stat-icon--primary">
                        <i class="material-symbols-rounded" aria-hidden="true">description</i>
                    </div>
                    <div>
                        <div class="im-stat-label">Total Bills</div>
                        <div class="im-stat-value">{{ number_format($stats['total_bills']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="im-stat">
                    <div class="im-stat-icon im-stat-icon--warning">
                        <i class="material-symbols-rounded" aria-hidden="true">schedule</i>
                    </div>
                    <div>
                        <div class="im-stat-label">Unpaid</div>
                        <div class="im-stat-value">{{ number_format($stats['unpaid_count']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="im-stat">
                    <div class="im-stat-icon im-stat-icon--success">
                        <i class="material-symbols-rounded" aria-hidden="true">check_circle</i>
                    </div>
                    <div>
                        <div class="im-stat-label">Paid</div>
                        <div class="im-stat-value">{{ number_format($stats['paid_count']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="im-stat">
                    <div class="im-stat-icon im-stat-icon--info">
                        <i class="material-symbols-rounded" aria-hidden="true">payments</i>
                    </div>
                    <div>
                        <div class="im-stat-label">Total Amount</div>
                        <div class="im-stat-value">₹ {{ number_format($stats['total_amount'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="ds-card mb-4 no-print">
        <div class="ds-card-body p-3 p-lg-4">
            <form method="GET" action="{{ route('admin.mess.my-bills.index') }}" id="myBillsFilterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label"><i class="material-symbols-rounded align-middle me-1" style="font-size: 1.1rem;">event</i>Date From <span class="text-danger">*</span></label>
                        <div class="input-group im-datepicker" id="date_from_wrap">
                            <input type="text" name="date_from" id="date_from" class="form-control"
                                   value="{{ $effectiveDateFrom ?? request('date_from', now()->startOfMonth()->format('d-m-Y')) }}"
                                   data-default-ymd="{{ $effectiveDateFromYmd ?? now()->startOfMonth()->format('Y-m-d') }}"
                                   placeholder="dd-mm-yyyy" autocomplete="off" data-input>
                            <span class="input-group-text im-datepicker-toggle" data-toggle title="Open calendar">
                                <i class="material-symbols-rounded" style="font-size: 1.1rem;">calendar_month</i>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="material-symbols-rounded align-middle me-1" style="font-size: 1.1rem;">event</i>Date To <span class="text-danger">*</span></label>
                        <div class="input-group im-datepicker" id="date_to_wrap">
                            <input type="text" name="date_to" id="date_to" class="form-control"
                                   value="{{ $effectiveDateTo ?? request('date_to', now()->endOfMonth()->format('d-m-Y')) }}"
                                   data-default-ymd="{{ $effectiveDateToYmd ?? now()->endOfMonth()->format('Y-m-d') }}"
                                   placeholder="dd-mm-yyyy" autocomplete="off" data-input>
                            <span class="input-group-text im-datepicker-toggle" data-toggle title="Open calendar">
                                <i class="material-symbols-rounded" style="font-size: 1.1rem;">calendar_month</i>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-primary flex-grow-1 d-inline-flex align-items-center justify-content-center gap-1">
                            <i class="material-symbols-rounded align-middle">filter_list</i>
                            Apply
                        </button>
                        <a href="{{ route('admin.mess.my-bills.index') }}" class="btn btn-outline-secondary" title="Clear filters">
                            <i class="material-symbols-rounded" style="font-size: 1.1rem;">filter_list_off</i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="ds-card">
        <div class="ds-card-body p-3 p-lg-4">
            <div class="table-responsive">
                <table class="table table-sm table-hover text-nowrap align-middle mb-0" id="myMessBillsTable">
                    <thead>
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
                            <tr class="{{ ($cb->status ?? 0) == 2 ? '' : 'im-row-unpaid' }}">
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $cb->combined_invoice_no ?? '—' }}</td>
                                <td>{{ $cb->invoice_date_range ?? '—' }}</td>
                                <td>{{ $cb->client_type_display ?? '—' }}</td>
                                <td class="text-end fw-semibold">₹ {{ number_format($cb->total ?? 0, 2) }}</td>
                                <td>{{ $cb->payment_type ?? '—' }}</td>
                                <td>
                                    @if(($cb->status ?? 0) == 2)
                                        <span class="im-pill im-pill--success">Paid</span>
                                    @elseif(($cb->status ?? 0) == 1)
                                        <span class="im-pill im-pill--warning">Partial</span>
                                    @else
                                        <span class="im-pill im-pill--secondary">Unpaid</span>
                                    @endif
                                </td>
                                <td class="text-center no-print">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary my-bills-details-btn"
                                            data-bill-id="{{ $cb->combined_id }}"
                                            data-date-from-ymd="{{ $effectiveDateFromYmd ?? '' }}"
                                            data-date-to-ymd="{{ $effectiveDateToYmd ?? '' }}">
                                        <i class="material-symbols-rounded" style="font-size: 1.1rem;">visibility</i>
                                        <span class="d-none d-sm-inline">Details</span>
                                    </button>
                                    <a href="{{ route('admin.mess.process-mess-bills-employee.print-receipt', ['id' => $cb->combined_id]) }}?date_from={{ urlencode($effectiveDateFromYmd ?? '') }}&date_to={{ urlencode($effectiveDateToYmd ?? '') }}"
                                       target="_blank"
                                       class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 px-2"
                                       title="Print receipt">
                                        <i class="material-symbols-rounded" style="font-size: 1.1rem;">print</i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="im-empty">
                                        <i class="material-symbols-rounded" aria-hidden="true">inbox</i>
                                        <div class="fw-semibold fs-5 mb-1 text-body-emphasis">No Generated Mess Bills</div>
                                        <div class="small text-body-secondary">Try another date range, or confirm your mess account is linked to your employee or student record.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade mess-bills-modal" id="myBillDetailsModal" tabindex="-1" aria-labelledby="myBillDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content bill-receipt-modal-content">
            <div class="modal-header align-items-start">
                <h5 class="modal-title fw-bold" id="myBillDetailsModalLabel">Bill details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bill-receipt-modal-body">
                <div id="myBillDetailsContent" class="bill-receipt-content">
                    <div class="text-center py-4 text-muted">Loading…</div>
                </div>
                <div class="bill-receipt-actions">
                    <button type="button" class="btn btn-receipt-print" id="myBillDetailsPrintBtn">
                        <i class="material-symbols-rounded align-middle" style="font-size: 1.1rem;">print</i> Print
                    </button>
                    <button type="button" class="btn btn-receipt-cancel" data-bs-dismiss="modal">Close</button>
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
    'infoLabel' => 'generated mess bills',
])
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
/* =====================================================================
   My Mess Bills — page-scoped polish.
   Tokens/components come from sargam-app.css (--ds-*, .ds-*).
   Scoped to .mess-my-bills-page / modal id so nothing leaks elsewhere.
   ===================================================================== */
@media screen { .mess-my-bills-page .report-header { display: none; } }
@media print {
    .mess-my-bills-page .no-print { display: none !important; }
}

/* Page intro */
.mess-my-bills-page .im-intro {
    display: flex;
    align-items: center;
    gap: var(--ds-space-3);
}
.mess-my-bills-page .im-intro h1 { color: var(--ds-ink); }
.mess-my-bills-page .im-intro-icon {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--ds-radius-2);
    background: rgba(var(--bs-primary-rgb, 0 74 147), 0.10);
    color: var(--bs-primary);
}
.mess-my-bills-page .im-intro-icon i { font-size: 26px; }

/* Stat cards */
.mess-my-bills-page .im-stat {
    display: flex;
    align-items: center;
    gap: var(--ds-space-3);
    height: 100%;
    padding: var(--ds-space-4);
    background: #fff;
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-2);
    box-shadow: var(--ds-shadow-sm);
}
.mess-my-bills-page .im-stat-icon {
    flex-shrink: 0;
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--ds-radius-2);
}
.mess-my-bills-page .im-stat-icon i { font-size: 28px; }
.mess-my-bills-page .im-stat-icon--primary { background: rgba(0, 74, 147, 0.10); color: #004a93; }
.mess-my-bills-page .im-stat-icon--warning { background: #fff3d6; color: #9a6a00; }
.mess-my-bills-page .im-stat-icon--success { background: #e3f5ea; color: #0f7b3e; }
.mess-my-bills-page .im-stat-icon--info    { background: #e6f0fd; color: #0d5bbd; }
.mess-my-bills-page .im-stat-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    font-weight: 600;
    color: var(--ds-ink-muted);
    margin-bottom: 0.15rem;
}
.mess-my-bills-page .im-stat-value { font-size: 1.5rem; font-weight: 700; color: var(--ds-ink); line-height: 1.1; }

/* Labels + controls */
.mess-my-bills-page .form-label { font-size: 0.8125rem; font-weight: 600; color: var(--ds-ink); margin-bottom: 0.4rem; }
.mess-my-bills-page .form-control,
.mess-my-bills-page .form-select { border-radius: var(--ds-radius-1); font-size: 0.9rem; }
.mess-my-bills-page .form-control:focus,
.mess-my-bills-page .form-select:focus { border-color: #86b7fe; box-shadow: var(--ds-focus-ring); }
.mess-my-bills-page .btn-primary { border-radius: var(--ds-radius-1); font-weight: 600; }

/* Datepicker input-group (flatpickr) */
.mess-my-bills-page .im-datepicker .form-control {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    border-right: 0;
}
.mess-my-bills-page .im-datepicker-toggle {
    background: var(--ds-surface-2);
    border: 1px solid var(--ds-border, var(--ds-line));
    border-left: 0;
    border-top-right-radius: var(--ds-radius-1);
    border-bottom-right-radius: var(--ds-radius-1);
    color: var(--ds-ink-muted);
    cursor: pointer;
}
.mess-my-bills-page .im-datepicker:focus-within .form-control,
.mess-my-bills-page .im-datepicker:focus-within .im-datepicker-toggle {
    border-color: #86b7fe;
}
.mess-my-bills-page .im-datepicker:focus-within { border-radius: var(--ds-radius-1); box-shadow: var(--ds-focus-ring); }
.mess-my-bills-page .im-datepicker-toggle:hover { color: var(--bs-primary); }

/* Table — neutral uppercase header (matches other modernized pages) */
.mess-my-bills-page #myMessBillsTable thead th {
    background: var(--ds-surface-2);
    color: var(--ds-ink-muted);
    font-size: 0.8125rem;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    font-weight: 600;
    border-bottom: 1px solid var(--ds-line);
    white-space: nowrap;
}
.mess-my-bills-page #myMessBillsTable tbody td {
    font-size: 0.9rem;
    color: var(--ds-ink);
    vertical-align: middle;
}
.mess-my-bills-page #myMessBillsTable tbody tr.im-row-unpaid { background: #fffaf0; }

/* Soft status pills */
.mess-my-bills-page .im-pill {
    display: inline-block;
    padding: 0.3rem 0.75rem;
    border-radius: 50rem;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1.2;
    white-space: nowrap;
}
.mess-my-bills-page .im-pill--success   { color: #0f7b3e; background: #e3f5ea; }
.mess-my-bills-page .im-pill--warning   { color: #9a6a00; background: #fff3d6; }
.mess-my-bills-page .im-pill--secondary { color: #475467; background: #eef1f5; }

/* Empty state */
.mess-my-bills-page .im-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 2rem 1rem;
}
.mess-my-bills-page .im-empty i { font-size: 56px; color: #98a2b3; margin-bottom: 0.75rem; }

/* --- Modal shell --- */
.mess-bills-modal .modal-content { border: 0; border-radius: var(--ds-radius-2); box-shadow: 0 10px 40px rgba(16,24,40,.18); }
.mess-bills-modal .modal-header { border-bottom: 1px solid var(--ds-line); padding: var(--ds-space-4); }
.mess-bills-modal .modal-body { padding: var(--ds-space-4); }

/* --- Receipt content (unchanged look, kept for the printable receipt) --- */
.bill-receipt-content .receipt-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem; }
.bill-receipt-content .receipt-logo { display: flex; align-items: center; gap: 0.5rem; font-weight: 700; color: #0a3d6b; }
.bill-receipt-content .receipt-center { text-align: center; margin: 0.75rem 0; }
.bill-receipt-content .receipt-title { font-weight: 700; font-size: 1rem; color: #0a3d6b; }
.bill-receipt-content .receipt-subtitle { font-size: 0.9rem; color: #333; }
.bill-receipt-content .receipt-period { font-size: 0.85rem; color: #555; margin-top: 0.25rem; }
.bill-receipt-content .receipt-client-info {
    font-size: 0.9rem;
    color: #212529;
}
.bill-receipt-content .receipt-client-info .receipt-info-line {
    word-break: break-word;
}
.bill-receipt-content .bill-table { width: 100%; border-collapse: collapse; margin-top: 0.5rem; font-size: 0.85rem; }
.bill-receipt-content .bill-table th, .bill-receipt-content .bill-table td { border: 1px solid #dee2e6; padding: 0.35rem 0.5rem; }
.bill-receipt-content .bill-table th { background: #f8f9fa; }
.bill-receipt-content .receipt-bottom { display: flex; justify-content: flex-end; margin-top: 1rem; }
.bill-receipt-content .payment-summary { text-align: right; min-width: 200px; }
.bill-receipt-content .payment-summary .summary-row { display: flex; justify-content: flex-end; gap: 0.5rem; margin-bottom: 0.2rem; }
.bill-receipt-actions { margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--ds-line); display: flex; gap: 0.75rem; flex-wrap: wrap; }
.bill-receipt-actions .btn-receipt-print { background: var(--bs-primary); color: #fff; border: none; padding: 0.5rem 1.25rem; font-weight: 600; border-radius: var(--ds-radius-1); }
.bill-receipt-actions .btn-receipt-cancel { background: #fff; color: var(--ds-ink); border: 1px solid var(--ds-line); padding: 0.5rem 1.25rem; border-radius: var(--ds-radius-1); font-weight: 600; }
.bill-receipt-actions .btn-receipt-cancel:hover { background: var(--ds-surface-2); }
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

    function escapeReceiptHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function receiptInfoCol(label, value, alignEnd) {
        var alignClass = alignEnd ? ' text-md-end' : ' text-md-start';
        return '<div class="col-12 col-md-6' + alignClass + ' receipt-info-line">' +
            '<span class="fw-bold">' + escapeReceiptHtml(label) + ':</span> ' +
            '<span>' + escapeReceiptHtml(value) + '</span></div>';
    }

    function renderPaymentDetailsContent(data) {
        var dateStr = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-');
        var timeStr = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false });
        var rows = (data.items || []).map(function (item) {
            var issue = item.issue_date || item.purchase_date || '—';
            return '<tr><td>' + escapeReceiptHtml(item.store_name || '—') + '</td><td>' + escapeReceiptHtml(item.item_name || '—') + '</td><td>' + escapeReceiptHtml(issue) + '</td><td class="text-end">' + escapeReceiptHtml(item.price || '0') + '</td><td class="text-end">' + escapeReceiptHtml(item.quantity || '0') + '</td><td class="text-end">' + escapeReceiptHtml(item.amount || '0') + '</td></tr>';
        }).join('');
        var clientNameCourse = data.client_name_course || (function () {
            if (data.course_name) {
                return (data.client_name || '—') + ' – ' + data.course_name;
            }
            return data.client_name || '—';
        })();
        var hasRefOrOrder = !!(data.reference_number || data.order_by);
        return '<div class="receipt-top">' +
            '<div class="receipt-logo"><span class="receipt-logo-text">Sargam</span></div>' +
            '<span class="receipt-date">Date ' + escapeReceiptHtml(dateStr + ' ' + timeStr) + '</span>' +
            '</div>' +
            '<div class="receipt-center">' +
            '<div class="receipt-title">OFFICER\'S MESS LBSNAA MUSSOORIE</div>' +
            '<div class="receipt-subtitle">MESS BILLS</div>' +
            '<div class="receipt-period">Client Bill From Period ' + escapeReceiptHtml(data.date_from || '') + ' To ' + escapeReceiptHtml(data.date_to || '') + '</div>' +
            '</div>' +
            '<hr class="border-secondary-subtle opacity-50 my-2" />' +
            '<div class="receipt-client-info border-top border-bottom border-secondary-subtle py-2 my-2">' +
            '<div class="row g-2 py-1 align-items-start">' +
            receiptInfoCol('Receipt No', data.receipt_no || '—', false) +
            receiptInfoCol('Invoice No', data.invoice_no || '—', true) +
            '</div>' +
            '<div class="row g-2 py-1 align-items-start">' +
            receiptInfoCol('Client Name', clientNameCourse, false) +
            receiptInfoCol('Client Type', data.client_type || '—', true) +
            '</div>' +
            '</div>' +
            (hasRefOrOrder ? ('<div class="receipt-client-info border-bottom border-secondary-subtle pb-2 mb-2">' +
                '<div class="row g-2 py-1 align-items-start">' +
                (data.reference_number ? receiptInfoCol('Reference Number', data.reference_number, false) : '') +
                (data.order_by ? receiptInfoCol('Order By', data.order_by, true) : '') +
                '</div></div>') : '') +
            (data.remarks ? ('<div class="receipt-client-info border-bottom border-secondary-subtle pb-2 mb-2">' +
                '<div class="row g-2 py-1"><div class="col-12 text-start receipt-info-line">' +
                '<span>Remarks:</span> <span>' + escapeReceiptHtml(data.remarks) + '</span></div></div></div>') : '') +
            '<hr class="border-secondary-subtle opacity-50 my-2" />' +
            '<table class="bill-table"><thead><tr><th>Store Name</th><th>Item Name</th><th>Issue Date</th><th class="text-end">Price</th><th class="text-end">Quantity</th><th class="text-end">Amount</th></tr></thead><tbody>' + rows + '</tbody></table>' +
            '<div class="receipt-bottom"><div class="payment-summary">' +
            '<div class="summary-row"><span class="summary-label">Paid Amount</span><span class="summary-value">' + escapeReceiptHtml(data.paid_amount || '0.0') + '</span></div>' +
            '<div class="summary-row"><span class="summary-label">Total Amount</span><span class="summary-value">' + escapeReceiptHtml(data.total_amount || '0.0') + '</span></div>' +
            '<div class="summary-row"><span class="summary-label">Due Amount</span><span class="summary-value">' + escapeReceiptHtml(data.due_amount || '0.0') + '</span></div>' +
            '</div></div>';
    }

    function openMyBillDetails(billId, dateFromYmd, dateToYmd) {
        myBillDetailsBillId = billId;
        myBillDetailsDateFrom = dateFromYmd || null;
        myBillDetailsDateTo = dateToYmd || null;
        var content = document.getElementById('myBillDetailsContent');
        if (content) content.innerHTML = '<div class="text-center py-4 text-muted">Loading…</div>';
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
                    if (content) content.innerHTML = '<div class="text-danger py-4 text-center">' + (data.error || 'Failed to load.') + '</div>';
                    return;
                }
                if (content) content.innerHTML = renderPaymentDetailsContent(data);
                var modalEl = document.getElementById('myBillDetailsModal');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            })
            .catch(function () {
                if (content) content.innerHTML = '<div class="text-danger py-4 text-center">Failed to load bill details.</div>';
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
            flatpickr('#date_from_wrap', { wrap: true, dateFormat: 'd-m-Y', allowInput: true, defaultDate: defaultFrom });
            flatpickr('#date_to_wrap', { wrap: true, dateFormat: 'd-m-Y', allowInput: true, defaultDate: defaultTo });
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
