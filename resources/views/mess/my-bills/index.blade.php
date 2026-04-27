@extends('admin.layouts.master')
@section('title', 'My Mess Bills')
@section('setup_content')
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

<div class="modal fade" id="myBillDetailsModal" tabindex="-1" aria-labelledby="myBillDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content bill-receipt-modal-content">
            <div class="modal-header border-0 py-2 align-items-start">
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
.bill-receipt-content .receipt-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem; }
.bill-receipt-content .receipt-logo { display: flex; align-items: center; gap: 0.5rem; font-weight: 700; color: #0a3d6b; }
.bill-receipt-content .receipt-center { text-align: center; margin: 0.75rem 0; }
.bill-receipt-content .receipt-title { font-weight: 700; font-size: 1rem; color: #0a3d6b; }
.bill-receipt-content .receipt-subtitle { font-size: 0.9rem; color: #333; }
.bill-receipt-content .receipt-period { font-size: 0.85rem; color: #555; margin-top: 0.25rem; }
.bill-receipt-content .client-row { display: flex; flex-wrap: wrap; gap: 0.75rem 1.25rem; margin: 0.35rem 0; font-size: 0.9rem; }
.bill-receipt-content .client-label { font-weight: 600; color: #333; }
.bill-receipt-content .bill-table { width: 100%; border-collapse: collapse; margin-top: 0.5rem; font-size: 0.85rem; }
.bill-receipt-content .bill-table th, .bill-receipt-content .bill-table td { border: 1px solid #dee2e6; padding: 0.35rem 0.5rem; }
.bill-receipt-content .bill-table th { background: #f8f9fa; }
.bill-receipt-content .receipt-bottom { display: flex; justify-content: flex-end; margin-top: 1rem; }
.bill-receipt-content .payment-summary { text-align: right; min-width: 200px; }
.bill-receipt-content .payment-summary .summary-row { display: flex; justify-content: flex-end; gap: 0.5rem; margin-bottom: 0.2rem; }
.bill-receipt-actions { margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #dee2e6; display: flex; gap: 0.75rem; flex-wrap: wrap; }
.bill-receipt-actions .btn-receipt-print { background: linear-gradient(180deg, #0a6bb5 0%, #0a3d6b 100%); color: #fff; border: none; padding: 0.5rem 1.25rem; font-weight: 600; border-radius: 6px; }
.bill-receipt-actions .btn-receipt-cancel { background: #c00; color: #fff; border: none; padding: 0.5rem 1.25rem; border-radius: 6px; font-weight: 600; }
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

    function renderPaymentDetailsContent(data) {
        var dateStr = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-');
        var timeStr = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false });
        var rows = (data.items || []).map(function (item) {
            var issue = item.issue_date || item.purchase_date || '—';
            return '<tr><td>' + (item.store_name || '—') + '</td><td>' + (item.item_name || '—') + '</td><td>' + issue + '</td><td class="text-end">' + (item.price || '0') + '</td><td class="text-end">' + (item.quantity || '0') + '</td><td class="text-end">' + (item.amount || '0') + '</td></tr>';
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
            '<span class="receipt-date">Date ' + dateStr + ' ' + timeStr + '</span></div>' +
            '<div class="receipt-center">' +
            '<div class="receipt-title">OFFICER\'S MESS LBSNAA MUSSOORIE</div>' +
            '<div class="receipt-subtitle">MESS BILLS</div>' +
            '<div class="receipt-period">Client Bill From Period ' + (data.date_from || '') + ' To ' + (data.date_to || '') + '</div></div><hr/>' +
            '<div class="client-row">' +
            '<span><span class="client-label">Receipt No</span>: <span class="client-value">' + (data.receipt_no || '—') + '</span></span>' +
            '<span><span class="client-label">Invoice No</span>: <span class="client-value">' + (data.invoice_no || '—') + '</span></span></div>' +
            '<div class="client-row">' +
            '<span><span class="client-label">Client Name</span>: <span class="client-value">' + clientNameCourse + '</span></span>' +
            '<span><span class="client-label">Client Type</span>: <span class="client-value">' + (data.client_type || '—') + '</span></span></div>' +
            (hasRefOrOrder ? ('<div class="client-row">' +
                (data.reference_number ? '<span><span class="client-label">Reference Number</span>: <span class="client-value">' + data.reference_number + '</span></span>' : '') +
                (data.order_by ? '<span><span class="client-label">Order By</span>: <span class="client-value">' + data.order_by + '</span></span>' : '') +
                '</div>') : '') +
            (data.remarks ? ('<div class="client-row"><span><span class="client-label">Remarks</span>: <span class="client-value">' + data.remarks + '</span></span></div>') : '') +
            '<hr/><table class="bill-table"><thead><tr><th>Store Name</th><th>Item Name</th><th>Issue Date</th><th class="text-end">Price</th><th class="text-end">Quantity</th><th class="text-end">Amount</th></tr></thead><tbody>' + rows + '</tbody></table>' +
            '<div class="receipt-bottom"><div class="payment-summary">' +
            '<div class="summary-row"><span class="summary-label">Paid Amount</span><span class="summary-value">' + (data.paid_amount || '0.0') + '</span></div>' +
            '<div class="summary-row"><span class="summary-label">Total Amount</span><span class="summary-value">' + (data.total_amount || '0.0') + '</span></div>' +
            '<div class="summary-row"><span class="summary-label">Due Amount</span><span class="summary-value">' + (data.due_amount || '0.0') + '</span></div>' +
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
