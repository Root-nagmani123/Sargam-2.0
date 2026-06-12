@extends('admin.layouts.master')
@section('title', 'Process Mess Bills')
@section('content')
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
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4 no-print p-4 rounded-3 shadow-sm" style="background: #004a93; color: white;">
        <div>
            <h4 class="mb-2 fw-bold d-flex align-items-center gap-2">
                <i class="material-symbols-rounded" style="font-size: 2rem;">receipt_long</i>
                Process Mess Bills
            </h4>
            <p class="mb-0 small opacity-90 text-white">View mess bills for Employee, OT, Course & Other, generate invoices, and mark payments. Filter by date to see bills from Selling Voucher and Date Range reports.</p>
        </div>
        <button type="button" class="btn btn-light shadow d-inline-flex align-items-center gap-2 px-4" data-bs-toggle="modal" data-bs-target="#addProcessMessBillsModal" style="font-weight: 600;">
            <i class="material-symbols-rounded" style="font-size: 1.3rem;">add_circle</i>
            Generate Invoice
        </button>
    </div>

    {{-- Summary cards --}}
    <section class="no-print process-mess-stats-section mb-4" aria-labelledby="process-mess-stats-heading">
        @php
            $stats = $stats ?? ['total_bills' => 0, 'paid_count' => 0, 'unpaid_count' => 0, 'total_amount' => 0, 'total_due_amount' => 0];
            $statsPaidPct = ($stats['total_bills'] ?? 0) > 0
                ? (int) round((($stats['paid_count'] ?? 0) / $stats['total_bills']) * 100)
                : 0;
        @endphp
        <div class="card border border-light-subtle shadow-sm rounded-4 overflow-hidden bg-body-tertiary bg-opacity-50">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 mb-3 border-bottom border-light-subtle">
                    <div class="d-flex align-items-center gap-3 min-w-0">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary process-mess-stat-icon flex-shrink-0" aria-hidden="true">
                            <span class="material-symbols-rounded">insights</span>
                        </span>
                        <div class="min-w-0">
                            <h2 id="process-mess-stats-heading" class="h6 mb-0 fw-semibold text-body-emphasis">Bill overview</h2>
                            <p class="small text-body-secondary mb-0 text-truncate">Key metrics for the selected period</p>
                        </div>
                    </div>
                    <span class="badge text-bg-light border border-light-subtle text-body-secondary fw-normal px-3 py-2 rounded-pill d-inline-flex align-items-center gap-1 flex-shrink-0">
                        <span class="material-symbols-rounded" style="font-size: 1rem;" aria-hidden="true">date_range</span>
                        <span class="text-truncate">{{ $dateFromDisplay }} – {{ $dateToDisplay }}</span>
                    </span>
                </div>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-5 g-3 g-lg-4 animate__animated animate__fadeIn" role="list" aria-label="Bill statistics">
                    <div class="col" role="listitem">
                        <article class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden hover-lift transition-all border-start border-4 border-primary bg-primary-subtle bg-opacity-50">
                            <div class="card-body p-3 p-md-4 d-flex align-items-start gap-3">
                                <span class="flex-shrink-0 rounded-3 bg-primary text-white d-inline-flex align-items-center justify-content-center process-mess-stat-icon shadow-sm" aria-hidden="true">
                                    <span class="material-symbols-rounded">description</span>
                                </span>
                                <div class="flex-grow-1 min-w-0">
                                    <p class="text-body-secondary small text-uppercase fw-semibold mb-1 lh-sm">Total bills</p>
                                    <p class="fs-3 fw-bold text-primary mb-1 lh-1 tabular-nums" id="process-mess-stats-total-bills">{{ number_format($stats['total_bills']) }}</p>
                                    <p class="small text-body-secondary mb-0 opacity-75">Combined invoices in range</p>
                                </div>
                            </div>
                        </article>
                    </div>
                    <div class="col" role="listitem">
                        <article class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden hover-lift transition-all border-start border-4 border-warning bg-warning-subtle bg-opacity-50">
                            <div class="card-body p-3 p-md-4 d-flex align-items-start gap-3">
                                <span class="flex-shrink-0 rounded-3 bg-warning text-dark d-inline-flex align-items-center justify-content-center process-mess-stat-icon shadow-sm" aria-hidden="true">
                                    <span class="material-symbols-rounded">pending_actions</span>
                                </span>
                                <div class="flex-grow-1 min-w-0">
                                    <p class="text-body-secondary small text-uppercase fw-semibold mb-1 lh-sm">Unpaid</p>
                                    <p class="fs-3 fw-bold text-warning-emphasis mb-1 lh-1 tabular-nums" id="process-mess-stats-unpaid">{{ number_format($stats['unpaid_count']) }}</p>
                                    <p class="small text-body-secondary mb-0 opacity-75">Awaiting payment</p>
                                </div>
                            </div>
                        </article>
                    </div>
                    <div class="col" role="listitem">
                        <article class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden hover-lift transition-all border-start border-4 border-success bg-success-subtle bg-opacity-50">
                            <div class="card-body p-3 p-md-4 d-flex flex-column gap-2">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="flex-shrink-0 rounded-3 bg-success text-white d-inline-flex align-items-center justify-content-center process-mess-stat-icon shadow-sm" aria-hidden="true">
                                        <span class="material-symbols-rounded">check_circle</span>
                                    </span>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                            <p class="text-body-secondary small text-uppercase fw-semibold mb-0 lh-sm">Paid</p>
                                            <span class="badge rounded-pill bg-success-subtle text-success-emphasis border border-success-subtle" id="process-mess-stats-paid-pct">{{ $statsPaidPct }}% cleared</span>
                                        </div>
                                        <p class="fs-3 fw-bold text-success mb-0 lh-1 tabular-nums" id="process-mess-stats-paid">{{ number_format($stats['paid_count']) }}</p>
                                    </div>
                                </div>
                                <div class="progress rounded-pill process-mess-stats-progress" id="process-mess-stats-paid-progress" role="progressbar" aria-label="Paid bills percentage" aria-valuenow="{{ $statsPaidPct }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar bg-success" id="process-mess-stats-paid-progress-bar" style="width: {{ $statsPaidPct }}%"></div>
                                </div>
                                <p class="small text-body-secondary mb-0 opacity-75">Fully settled bills</p>
                            </div>
                        </article>
                    </div>
                    <div class="col" role="listitem">
                        <article class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden hover-lift transition-all border-start border-4 border-info bg-info-subtle bg-opacity-50">
                            <div class="card-body p-3 p-md-4 d-flex align-items-start gap-3">
                                <span class="flex-shrink-0 rounded-3 bg-info text-white d-inline-flex align-items-center justify-content-center process-mess-stat-icon shadow-sm" aria-hidden="true">
                                    <span class="material-symbols-rounded">payments</span>
                                </span>
                                <div class="flex-grow-1 min-w-0">
                                    <p class="text-body-secondary small text-uppercase fw-semibold mb-1 lh-sm">Total amount</p>
                                    <p class="fs-3 fw-bold text-info-emphasis mb-1 lh-1 tabular-nums text-truncate" id="process-mess-stats-total-amount">₹ {{ number_format($stats['total_amount'], 2) }}</p>
                                    <p class="small text-body-secondary mb-0 opacity-75">Bill value for period</p>
                                </div>
                            </div>
                        </article>
                    </div>
                    <div class="col" role="listitem">
                        <article class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden hover-lift transition-all border-start border-4 border-danger bg-danger-subtle bg-opacity-50">
                            <div class="card-body p-3 p-md-4 d-flex align-items-start gap-3">
                                <span class="flex-shrink-0 rounded-3 bg-danger text-white d-inline-flex align-items-center justify-content-center process-mess-stat-icon shadow-sm" aria-hidden="true">
                                    <span class="material-symbols-rounded">account_balance_wallet</span>
                                </span>
                                <div class="flex-grow-1 min-w-0">
                                    <p class="text-body-secondary small text-uppercase fw-semibold mb-1 lh-sm">Total due amount</p>
                                    <p class="fs-3 fw-bold text-danger-emphasis mb-1 lh-1 tabular-nums text-truncate" id="process-mess-stats-total-due-amount">₹ {{ number_format($stats['total_due_amount'] ?? 0, 2) }}</p>
                                    <p class="small text-body-secondary mb-0 opacity-75">Outstanding balance for period</p>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </div>
    </section>


    {{-- Filters card --}}
    <div class="card border-0 shadow mb-4 no-print" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
        <div class="card-body p-3 p-lg-4">
            <form method="GET" action="{{ route('admin.mess.process-mess-bills-employee.index') }}" id="mainFilterForm">
                <input type="hidden" name="refresh" value="1">
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
                        @php
                            $selectedClientTypes = request('client_type', []);
                            if (!is_array($selectedClientTypes)) {
                                $selectedClientTypes = $selectedClientTypes !== null ? [$selectedClientTypes] : [];
                            }
                        @endphp
                        <select name="client_type[]" id="filterClientTypeSlug" class="form-select choices-select" multiple data-placeholder="All client types">
                            @foreach($clientTypes ?? [] as $key => $label)
                                <option value="{{ $key }}" {{ in_array($key, $selectedClientTypes) ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-dark mb-2"><i class="material-symbols-rounded align-middle me-1" style="font-size: 1.1rem;">category</i>Client Type</label>
                        @php
                            $selectedClientTypePks = request('client_type_pk', []);
                            if (!is_array($selectedClientTypePks)) {
                                $selectedClientTypePks = $selectedClientTypePks !== null ? [$selectedClientTypePks] : [];
                            }
                        @endphp
                        <select name="client_type_pk[]" id="filterClientTypePk" class="form-select choices-select" multiple data-placeholder="All">
                        </select>
                    </div>
                    @php
                        $selectedBuyerNames = (array) ($buyerName ?? request('buyer_name', []));
                    @endphp
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-dark mb-2"><i class="material-symbols-rounded align-middle me-1" style="font-size: 1.1rem;">badge</i>Buyer Name</label>
                        <select name="buyer_name[]" id="filterBuyerName" class="form-select shadow-sm border-0 choices-select" multiple data-placeholder="All Buyers">
                            @if(($clientType ?? request('client_type')) === 'ot' && isset($otBuyerNames) && $otBuyerNames->isNotEmpty())
                                @foreach($otBuyerNames as $buyer)
                                    <option value="{{ $buyer }}" {{ in_array($buyer, $selectedBuyerNames, true) ? 'selected' : '' }}>{{ $buyer }}</option>
                                @endforeach
                            @elseif(($clientType ?? request('client_type')) === 'course' && isset($courseBuyerNames) && $courseBuyerNames->isNotEmpty())
                                @foreach($courseBuyerNames as $buyer)
                                    <option value="{{ $buyer }}" {{ in_array($buyer, $selectedBuyerNames, true) ? 'selected' : '' }}>{{ $buyer }}</option>
                                @endforeach
                            @elseif(($clientType ?? request('client_type')) === 'other' && isset($otherBuyerNames) && $otherBuyerNames->isNotEmpty())
                                @foreach($otherBuyerNames as $buyer)
                                    <option value="{{ $buyer }}" {{ in_array($buyer, $selectedBuyerNames, true) ? 'selected' : '' }}>{{ $buyer }}</option>
                                @endforeach
                            @elseif(($clientType ?? request('client_type')) === 'section' && isset($sectionBuyerNames) && $sectionBuyerNames->isNotEmpty())
                                @foreach($sectionBuyerNames as $buyer)
                                    <option value="{{ $buyer }}" {{ in_array($buyer, $selectedBuyerNames, true) ? 'selected' : '' }}>{{ $buyer }}</option>
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
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-dark mb-2"><i class="material-symbols-rounded align-middle me-1" style="font-size: 1.1rem;">mail</i>Invoice Sent</label>
                        @php $currentInvoiceSent = $invoiceSentFilter ?? (request()->has('invoice_sent') ? request('invoice_sent') : 'sent'); @endphp
                        <select name="invoice_sent" id="filterInvoiceSent" class="form-select">
                            <option value="">All</option>
                            <option value="sent" {{ ($currentInvoiceSent ?? '') === 'sent' ? 'selected' : '' }}>Invoice Sent</option>
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
                @foreach((array) request('client_type', []) as $selectedClientType)
                    <input type="hidden" name="client_type[]" value="{{ $selectedClientType }}">
                @endforeach
                @foreach((array) request('client_type_pk', []) as $selectedClientTypePk)
                    <input type="hidden" name="client_type_pk[]" value="{{ $selectedClientTypePk }}">
                @endforeach
                @foreach((array) ($buyerName ?? request('buyer_name', [])) as $selectedBuyerName)
                    <input type="hidden" name="buyer_name[]" value="{{ $selectedBuyerName }}">
                @endforeach
                <input type="hidden" name="status" value="{{ $statusFilter ?? request('status') }}">
                <input type="hidden" name="invoice_sent" value="{{ $invoiceSentFilter ?? (request()->has('invoice_sent') ? request('invoice_sent') : 'sent') }}">
                <div class="d-flex flex-wrap justify-content-end align-items-right mb-3 gap-2">
                    <div class="d-flex align-items-center gap-2">
                        @php
                            $exportQuery = request()->only(['date_from', 'date_to', 'client_type', 'client_type_pk', 'buyer_name', 'status', 'search']);
                            $exportQuery['invoice_sent'] = request()->has('invoice_sent')
                                ? request('invoice_sent')
                                : ($invoiceSentFilter ?? 'sent');
                        @endphp
                        <a href="{{ route('admin.mess.process-mess-bills-employee.export') }}?{{ http_build_query($exportQuery) }}"
                           class="btn btn-outline-success shadow-sm d-inline-flex align-items-center gap-2 px-3"
                           title="Export to Excel"
                           data-mess-excel-export="processMessBillsTable">
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
                <table class="table text-nowrap align-middle mb-0" id="processMessBillsTable" data-mess-datatable-server-side="1">
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
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    function applyProcessMessBillStats(json) {
        if (!json || !json.stats) return;
        var s = json.stats;
        var fmtInt = function (n) { return String(Math.round(Number(n) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ','); };
        var fmtAmt = function (n) {
            var x = Number(n) || 0;
            var parts = x.toFixed(2).split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            return parts.join('.');
        };
        var elTotal = document.getElementById('process-mess-stats-total-bills');
        var elUnpaid = document.getElementById('process-mess-stats-unpaid');
        var elPaid = document.getElementById('process-mess-stats-paid');
        var elAmt = document.getElementById('process-mess-stats-total-amount');
        var elPaidPct = document.getElementById('process-mess-stats-paid-pct');
        var elDueAmt = document.getElementById('process-mess-stats-total-due-amount');
        if (elTotal) elTotal.textContent = fmtInt(s.total_bills);
        if (elUnpaid) elUnpaid.textContent = fmtInt(s.unpaid_count);
        if (elPaid) elPaid.textContent = fmtInt(s.paid_count);
        if (elAmt) elAmt.textContent = '₹ ' + fmtAmt(s.total_amount);
        if (elDueAmt) elDueAmt.textContent = '₹ ' + fmtAmt(s.total_due_amount);
        if (elPaidPct) {
            var total = Number(s.total_bills) || 0;
            var paid = Number(s.paid_count) || 0;
            var pct = total > 0 ? Math.round((paid / total) * 100) : 0;
            elPaidPct.textContent = pct + '% cleared';
            var elProgress = document.getElementById('process-mess-stats-paid-progress');
            var elProgressBar = document.getElementById('process-mess-stats-paid-progress-bar');
            if (elProgress) elProgress.setAttribute('aria-valuenow', String(pct));
            if (elProgressBar) elProgressBar.style.width = pct + '%';
        }
        if (elDueAmt) elDueAmt.textContent = '₹ ' + fmtAmt(s.total_due_amount);
    }

    window.applyProcessMessBillStats = applyProcessMessBillStats;

    function bindProcessMessBillStatsListener() {
        if (typeof window.jQuery === 'undefined') return;
        var $ = window.jQuery;
        var $table = $('#processMessBillsTable');
        if (!$table.length) return;

        $table.off('xhr.dt.processMessStats').on('xhr.dt.processMessStats', function (e, settings, json) {
            applyProcessMessBillStats(json);
        });

        if ($.fn.DataTable && $.fn.DataTable.isDataTable($table)) {
            applyProcessMessBillStats($table.DataTable().settings()[0].json);
        }
    }

    document.addEventListener('DOMContentLoaded', bindProcessMessBillStatsListener);
})();
</script>
@endpush

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
    'columnManager' => true,
    'colReorder' => false,
    'columnManagerLocked' => [0],
    'columnManagerTitle' => 'Process Mess Bills columns',
])

@push('scripts')
<script>
function applyMessSortHeaderIcon(th, isActive, sortDir) {
    if (!th) return;
    var icon = th.querySelector('.mess-report-sort-icon');
    if (!icon) return;
    th.classList.toggle('mess-th-sorted', !!isActive);
    th.classList.toggle('is-sorted', !!isActive);
    icon.classList.add('material-symbols-rounded');
    if (isActive) {
        icon.textContent = sortDir === 'desc' ? 'arrow_downward' : 'arrow_upward';
        icon.classList.remove('mess-report-sort-icon--muted');
        icon.setAttribute('aria-label', sortDir === 'desc' ? 'Sorted descending' : 'Sorted ascending');
    } else {
        icon.textContent = 'unfold_more';
        icon.classList.add('mess-report-sort-icon--muted');
        icon.setAttribute('aria-label', 'Sortable');
    }
}
window.applyMessSortHeaderIcon = applyMessSortHeaderIcon;

/** Column title for print/export — never include Material icon ligature text. */
function messPrintThLabel(th) {
    if (!th) {
        return '';
    }
    var label = (th.getAttribute('data-mess-col-original') || '').trim();
    if (label) {
        return label;
    }
    var clone = th.cloneNode(true);
    clone.querySelectorAll(
        '.mess-report-sort-icon, .material-symbols-rounded, .material-icons, i[class*="material"]'
    ).forEach(function (el) {
        el.remove();
    });
    label = (clone.textContent || '').replace(/\s+/g, ' ').trim();
    return label.replace(/\s+(unfold_more|arrow_upward|arrow_downward)$/i, '');
}
window.messPrintThLabel = messPrintThLabel;

function syncProcessMessBillsTableSortIcons() {
    if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable) {
        return;
    }
    var $ = window.jQuery;
    var $table = $('#processMessBillsTable');
    if (!$table.length || !$.fn.DataTable.isDataTable($table)) {
        return;
    }
    var dt = $table.DataTable();
    var order = dt.order();
    var sortCol = order.length ? order[0][0] : -1;
    var sortDir = order.length ? order[0][1] : 'asc';

    $table.find('thead tr').first().children('th.mess-sort-th').each(function () {
        var colIdx = dt.column(this).index();
        if (colIdx == null || colIdx < 0) {
            return;
        }
        applyMessSortHeaderIcon(this, colIdx === sortCol, sortDir);
    });
}
window.syncProcessMessBillsTableSortIcons = syncProcessMessBillsTableSortIcons;

function bindProcessMessBillsTableSortIcons() {
    if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable) {
        return false;
    }
    var $table = window.jQuery('#processMessBillsTable');
    if (!$table.length || !window.jQuery.fn.DataTable.isDataTable($table)) {
        return false;
    }
    var dt = $table.DataTable();
    dt.off('order.dt.messSort draw.dt.messSort column-reorder.dt.messSort');
    dt.on('order.dt.messSort draw.dt.messSort column-reorder.dt.messSort', syncProcessMessBillsTableSortIcons);
    syncProcessMessBillsTableSortIcons();
    return true;
}
window.bindProcessMessBillsTableSortIcons = bindProcessMessBillsTableSortIcons;

document.addEventListener('DOMContentLoaded', function () {
    var attempts = 0;
    var timer = setInterval(function () {
        if (bindProcessMessBillsTableSortIcons()) {
            clearInterval(timer);
            return;
        }
        if (++attempts > 60) {
            clearInterval(timer);
        }
    }, 150);
    if (typeof window.jQuery !== 'undefined') {
        window.jQuery(document).on('mess:columns:saved', function (e, tableId) {
            if (tableId === 'processMessBillsTable') {
                syncProcessMessBillsTableSortIcons();
            }
        });
    }
});
</script>
@endpush

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
.payment-detail-total-due-row .payment-detail-total-due-value { grid-column: span 3; font-weight: 700; font-size: 1rem; color: #0a3d6b; }
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

/* Sort headers — same pattern as mess reports (report-sort-th) */
.mess-sort-th {
    cursor: pointer;
    user-select: none;
    white-space: nowrap;
}
.mess-sort-th:hover {
    background: rgba(var(--bs-primary-rgb), 0.08) !important;
}
#processMessBillsTable thead th.mess-sort-th:hover {
    background: rgba(255, 255, 255, 0.12) !important;
}
#processMessBillsTable thead th .mess-report-sort-icon--muted {
    color: rgba(255, 255, 255, 0.55) !important;
    opacity: 1 !important;
}
#processMessBillsTable thead th.mess-th-sorted .mess-report-sort-icon {
    color: #fff !important;
    opacity: 1 !important;
}
#modalBillsTable thead {
    background: #004a93;
    color: #fff;
}
#modalBillsTable thead th {
    color: #fff;
    border-color: rgba(255, 255, 255, 0.15);
}
#modalBillsTable thead th.mess-sort-th:hover {
    background: rgba(255, 255, 255, 0.12) !important;
}
#modalBillsTable thead th.mess-sort-th .mess-report-sort-icon {
    font-size: 1rem !important;
    line-height: 1 !important;
    flex-shrink: 0;
    display: inline-block !important;
    font-family: 'Material Symbols Rounded', sans-serif;
}
#modalBillsTable thead th.mess-sort-th .mess-report-sort-icon--muted {
    color: rgba(255, 255, 255, 0.55) !important;
    opacity: 1 !important;
}
#modalBillsTable thead th.mess-sort-th.mess-th-sorted .mess-report-sort-icon,
#modalBillsTable thead th.mess-sort-th.is-sorted .mess-report-sort-icon {
    color: #fff !important;
    opacity: 1 !important;
}
#modalBillsTable thead th.mess-sort-th > .d-inline-flex {
    align-items: center;
    gap: 0.25rem;
}
#addProcessMessBillsModal #modalBillsTableBody .modal-bills-skeleton-row {
    pointer-events: none;
}
#addProcessMessBillsModal #modalBillsTableBody .modal-bills-skeleton-row td {
    height: 3.25rem;
    vertical-align: middle;
}
#addProcessMessBillsModal .modal-bills-skeleton {
    display: block;
    width: 100%;
    height: 0.875rem;
    border-radius: 4px;
    background: linear-gradient(90deg, #e2e8f0 25%, #f8fafc 45%, #e2e8f0 65%);
    background-size: 220% 100%;
    animation: modal-bills-skeleton-shimmer 1.25s ease-in-out infinite;
}
#addProcessMessBillsModal .modal-bills-skeleton--check {
    width: 1rem;
    height: 1rem;
    margin: 0 auto;
}
#addProcessMessBillsModal .modal-bills-skeleton--sn {
    width: 2rem;
}
#addProcessMessBillsModal .modal-bills-skeleton--buyer {
    width: min(11rem, 100%);
}
#addProcessMessBillsModal .modal-bills-skeleton--invoice {
    width: min(7rem, 100%);
}
#addProcessMessBillsModal .modal-bills-skeleton--payment {
    width: min(8rem, 100%);
}
#addProcessMessBillsModal .modal-bills-skeleton--total {
    width: min(5rem, 100%);
    margin-left: auto;
}
#addProcessMessBillsModal .modal-bills-skeleton--status {
    width: min(5.5rem, 100%);
    margin: 0 auto;
}
#addProcessMessBillsModal .modal-bills-skeleton--action {
    width: min(7.5rem, 100%);
    height: 1.5rem;
    margin: 0 auto;
}
#addProcessMessBillsModal .modal-bills-skeleton--receipt {
    width: 2rem;
    height: 1.5rem;
    margin: 0 auto;
}
@keyframes modal-bills-skeleton-shimmer {
    0% { background-position: 100% 0; }
    100% { background-position: -120% 0; }
}
@media (prefers-reduced-motion: reduce) {
    #addProcessMessBillsModal .modal-bills-skeleton {
        animation: none;
    }
}
/* Use Material icons instead of DataTables unicode arrows (often invisible on Windows) */
#processMessBillsTable.dataTable thead > tr > th.sorting:before,
#processMessBillsTable.dataTable thead > tr > th.sorting:after,
#processMessBillsTable.dataTable thead > tr > th.sorting_asc:before,
#processMessBillsTable.dataTable thead > tr > th.sorting_asc:after,
#processMessBillsTable.dataTable thead > tr > th.sorting_desc:before,
#processMessBillsTable.dataTable thead > tr > th.sorting_desc:after {
    display: none !important;
    content: '' !important;
}

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
    .mess-report-sort-icon,
    .process-mess-bills-employee-report .material-symbols-rounded,
    .process-mess-bills-employee-report i[class*="material"] {
        display: none !important;
        font-size: 0 !important;
        visibility: hidden !important;
    }
}

/* Summary stat cards */
.process-mess-stats-section .process-mess-stat-icon {
    width: 3rem;
    height: 3rem;
}
.process-mess-stats-section .process-mess-stat-icon .material-symbols-rounded {
    font-size: 1.5rem;
}
.process-mess-stats-section .process-mess-stats-progress {
    height: 0.35rem;
}
.process-mess-stats-section > .card > .card-body > .row .card .card-body {
    --bs-card-spacer-y: 0;
}
@media (prefers-reduced-motion: reduce) {
    .process-mess-stats-section .hover-lift:hover {
        transform: none;
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
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-md-down modal-dialog-centered">
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
<style>
    .choices__list--dropdown {
        z-index: 2000;
    }
    .ts-wrapper.choices {
        position: relative;
    }
    .ts-wrapper.choices .choices__list--dropdown {
        position: absolute !important;
        top: 100%;
        left: 0;
        right: 0;
    }
    .ts-wrapper.choices.is-flipped .choices__list--dropdown {
        top: auto;
        bottom: 100%;
    }
    .ts-wrapper.choices[data-type*="select-one"] .choices__input {
        display: block !important;
        width: 100% !important;
        min-width: 100% !important;
    }
    /* Niche open: search upar | Uper (flipped) open: search niche */
    .ts-wrapper.choices .choices__list--dropdown.is-active {
        display: flex;
        flex-direction: column;
    }
    .ts-wrapper.choices.is-flipped .choices__list--dropdown.is-active {
        flex-direction: column-reverse;
    }
    .ts-wrapper.choices .choices__list--dropdown.is-active .choices__list {
        flex: 1 1 auto;
        min-height: 0;
    }
    .ts-wrapper.choices[data-type*="select-one"] .choices__list--dropdown .choices__input--cloned,
    .ts-wrapper.choices[data-type*="select-one"] .choices__list--dropdown .choices__input {
        border-top: none !important;
        border-bottom: 1px solid #ced4da !important;
        margin-bottom: 0 !important;
    }
    .ts-wrapper.choices.is-flipped[data-type*="select-one"] .choices__list--dropdown .choices__input--cloned,
    .ts-wrapper.choices.is-flipped[data-type*="select-one"] .choices__list--dropdown .choices__input {
        border-bottom: none !important;
        border-top: 1px solid #ced4da !important;
    }
    .ts-wrapper.choices .choices__list--dropdown .choices__input--cloned {
        display: block !important;
        position: relative !important;
        opacity: 1 !important;
        flex-shrink: 0;
        min-height: 34px;
        width: 100% !important;
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function normalizeChoicesSearchText(text) {
        return String(text || '').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function applyPartialMatchChoicesSearchFilter(instance, rawQuery) {
        if (!instance || !instance.dropdown || !instance.dropdown.element) return;
        var dropdownEl = instance.dropdown.element;
        var query = normalizeChoicesSearchText(rawQuery);
        var choiceItems = dropdownEl.querySelectorAll('.choices__item--choice');
        if (!choiceItems || !choiceItems.length) return;

        choiceItems.forEach(function(item) {
            if (item.classList.contains('choices__placeholder')) return;
            var label = normalizeChoicesSearchText(item.textContent || '');
            var value = normalizeChoicesSearchText(item.getAttribute('data-value') || '');
            var show = !query || label.indexOf(query) !== -1 || value.indexOf(query) !== -1;
            item.style.display = show ? '' : 'none';
        });
    }

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
        // Keep search enabled for all Choices dropdowns.
        var shouldSearch = el.getAttribute('data-search') !== 'false';
        var isMultiple = !!el.multiple;

        var instance = new Choices(el, {
            searchEnabled: shouldSearch,
            // Disable built-in filter; we apply substring match below (typing shows partial matches).
            searchChoices: false,
            removeItemButton: isMultiple,
            itemSelectText: '',
            shouldSort: false,
            position: 'bottom',
            placeholderValue: placeholder,
            allowHTML: false,
            closeDropdownOnSelect: !isMultiple
        });
        if (instance.containerOuter && instance.containerOuter.element && instance.containerOuter.element.classList) {
            instance.containerOuter.element.classList.add('ts-wrapper');
        }
        if (instance.dropdown && instance.dropdown.element && instance.dropdown.element.classList) {
            instance.dropdown.element.classList.add('ts-dropdown');
        }
        function applySearchFilterAfterRender() {
            var typed = (instance.input && instance.input.element) ? (instance.input.element.value || '') : '';
            requestAnimationFrame(function () {
                applyPartialMatchChoicesSearchFilter(instance, typed);
            });
        }
        el.addEventListener('showDropdown', applySearchFilterAfterRender);
        if (instance.input && instance.input.element) {
            instance.input.element.addEventListener('input', function() {
                applySearchFilterAfterRender();
            });
            instance.input.element.addEventListener('keyup', applySearchFilterAfterRender);
        }

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

            initModalBillsColumnManager();
            updateModalBillsSortHeaderIcons();

            // After Choices.js initialization, populate the modal dropdowns
            setTimeout(function() {
                if (typeof fillModalClientTypePk === 'function') {
                    fillModalClientTypePk();
                }
            }, 50);
        });
    }

    var modalBillsData = [];
    var modalBillsCurrentPage = 1;
    var modalBillsTotal = 0;
    var modalBillsFrom = 0;
    var modalBillsTo = 0;
    var modalBillsSortCol = 'buyer_name';
    var modalBillsSortDir = 'asc';
    var modalAllBuyerNames = {!! json_encode(($allBuyerNames ?? collect())->values()->all(), JSON_UNESCAPED_UNICODE) !!};
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

    /** Prefer Flatpickr's selected date so the modal request matches the picker (avoids stale input vs calendar). */
    function getModalDateYmd(inputId) {
        var el = document.getElementById(inputId);
        if (!el) return '';
        var fp = el._flatpickr;
        if (fp && fp.selectedDates && fp.selectedDates.length > 0 && typeof fp.formatDate === 'function') {
            return fp.formatDate(fp.selectedDates[0], 'Y-m-d');
        }
        return el.value ? toYmd(el.value) : '';
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

    /** All selected values from a native multi-select or Choices.js instance. */
    function getChoicesMultiValues(el) {
        if (!el) return [];
        if (el.choicesInstance && typeof el.choicesInstance.getValue === 'function') {
            var raw = el.choicesInstance.getValue(true);
            if (Array.isArray(raw)) {
                return raw.map(function (v) { return String(v || '').trim(); }).filter(Boolean);
            }
            if (raw != null && raw !== '') {
                return [String(raw).trim()];
            }
            return [];
        }
        return Array.from(el.selectedOptions || []).map(function (opt) {
            return String(opt.value || '').trim();
        }).filter(Boolean);
    }

    function buildModalBillsDataUrl(options) {
        options = options || {};
        var ct = document.getElementById('modal_client_type');
        var ctp = document.getElementById('modal_client_type_pk');
        var bn = document.getElementById('modal_buyer_name');
        var dateFrom = getModalDateYmd('modal_date_from');
        var dateTo = getModalDateYmd('modal_date_to');
        var clientTypes = getChoicesMultiValues(ct);
        var clientTypePks = getChoicesMultiValues(ctp);
        var perPage = parseInt((document.getElementById('modalPerPage') || {}).value || 10, 10);
        var modalSearch = (document.getElementById('modalSearch') || {}).value || '';
        var buyerNames = getChoicesMultiValues(bn);
        var page = options.forPrint ? 1 : (options.page != null ? options.page : modalBillsCurrentPage);
        if (options.forPrint) {
            perPage = 10000;
        }
        var url = '{{ route("admin.mess.process-mess-bills-employee.modal-data") }}?date_from=' + encodeURIComponent(dateFrom) + '&date_to=' + encodeURIComponent(dateTo);
        url += '&page=' + encodeURIComponent(page) + '&per_page=' + encodeURIComponent(perPage);
        if (options.forPrint) {
            url += '&for_print=1';
        }
        if (modalSearch) {
            url += '&search=' + encodeURIComponent(modalSearch);
        }
        clientTypes.forEach(function (type) {
            url += '&client_type[]=' + encodeURIComponent(type);
        });
        clientTypePks.forEach(function (pk) {
            url += '&client_type_pk[]=' + encodeURIComponent(pk);
        });
        if (buyerNames.length) {
            buyerNames.forEach(function (name) {
                url += '&buyer_name[]=' + encodeURIComponent(name);
            });
        }
        url += '&sort_column=' + encodeURIComponent(modalBillsSortCol || 'buyer_name');
        url += '&sort_dir=' + encodeURIComponent(modalBillsSortDir || 'asc');
        return url;
    }
    window.buildModalBillsDataUrl = buildModalBillsDataUrl;

    function updateModalBillsSortHeaderIcons() {
        document.querySelectorAll('#modalBillsTable .mess-sort-th[data-sort]').forEach(function (th) {
            var col = th.getAttribute('data-sort') || '';
            applyMessSortHeaderIcon(th, col === modalBillsSortCol, modalBillsSortDir);
        });
    }

    function setModalBillsLoading(isLoading) {
        var table = document.getElementById('modalBillsTable');
        var host = document.getElementById('modalBillsTableHost');
        if (table) {
            table.setAttribute('aria-busy', isLoading ? 'true' : 'false');
        }
        if (host) {
            host.classList.toggle('is-loading', !!isLoading);
        }
    }

    function renderModalBillsSkeleton() {
        var tbody = document.getElementById('modalBillsTableBody');
        var modalSelectAllEl = document.getElementById('modalSelectAll');
        var bulkActionsBar = document.getElementById('modalBulkActionsBar');
        var paginationInfo = document.getElementById('modalPaginationInfo');
        var paginationNav = document.getElementById('modalPaginationNav');
        if (!tbody) return;

        var skeletonRow = function (rowIndex) {
            var srText = rowIndex === 0
                ? '<span class="visually-hidden" role="status">Loading bills</span>'
                : '';
            return '<tr class="modal-bills-skeleton-row" aria-hidden="' + (rowIndex === 0 ? 'false' : 'true') + '">' +
                '<td>' + srText + '<span class="modal-bills-skeleton modal-bills-skeleton--check"></span></td>' +
                '<td><span class="modal-bills-skeleton modal-bills-skeleton--sn"></span></td>' +
                '<td><span class="modal-bills-skeleton modal-bills-skeleton--buyer"></span></td>' +
                '<td><span class="modal-bills-skeleton modal-bills-skeleton--invoice"></span></td>' +
                '<td><span class="modal-bills-skeleton modal-bills-skeleton--payment"></span></td>' +
                '<td><span class="modal-bills-skeleton modal-bills-skeleton--total"></span></td>' +
                '<td><span class="modal-bills-skeleton modal-bills-skeleton--status"></span></td>' +
                '<td><span class="modal-bills-skeleton modal-bills-skeleton--action"></span></td>' +
                '<td><span class="modal-bills-skeleton modal-bills-skeleton--receipt"></span></td>' +
                '</tr>';
        };

        tbody.innerHTML = [0, 1, 2, 3, 4].map(skeletonRow).join('');
        if (modalSelectAllEl) modalSelectAllEl.checked = false;
        if (bulkActionsBar) bulkActionsBar.classList.add('d-none');
        if (paginationInfo) paginationInfo.textContent = 'Loading bills...';
        if (paginationNav) paginationNav.classList.add('d-none');
        setModalBillsLoading(true);
        applyModalBillsColumnVisibility();
    }

    function loadModalBills(page) {
        var requestedPage = parseInt(page, 10);
        modalBillsCurrentPage = isNaN(requestedPage) ? 1 : Math.max(1, requestedPage);
        var ct = document.getElementById('modal_client_type');
        var clientTypes = getChoicesMultiValues(ct);
        var modalSearch = (document.getElementById('modalSearch') || {}).value || '';
        var url = buildModalBillsDataUrl({ page: modalBillsCurrentPage });
        renderModalBillsSkeleton();
        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                modalBillsData = data.bills || [];
                var pagination = data.pagination || {};
                modalBillsTotal = parseInt(pagination.total || modalBillsData.length || 0, 10);
                modalBillsFrom = parseInt(pagination.from || (modalBillsTotal ? 1 : 0), 10);
                modalBillsTo = parseInt(pagination.to || modalBillsData.length || 0, 10);
                modalBillsCurrentPage = parseInt(pagination.page || modalBillsCurrentPage || 1, 10);
                updateModalBillsSortHeaderIcons();
                renderModalTable();

                // Also refresh Buyer Name dropdown in modal based on loaded bills.
                // IMPORTANT: Only do this when no client type is selected, otherwise it
                // overrides the dependent "Client Type -> Buyer Name" behavior.
                if (clientTypes.length > 0 || modalBillsCurrentPage > 1 || modalSearch) {
                    return;
                }
                try {
                    var buyerSelect = document.getElementById('modal_buyer_name');
                    if (buyerSelect) {
                        var buyers = Array.from(new Set(
                            ((modalAllBuyerNames || []).length ? modalAllBuyerNames : (modalBillsData || [])
                                .map(function (b) { return b.buyer_name || b.client_name || ''; }))
                                .filter(function (name) { return !!name; })
                        ));

                        buyerSelect.innerHTML = '';

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
                modalBillsTotal = 0;
                modalBillsFrom = 0;
                modalBillsTo = 0;
                renderModalTable();
                showToast('Failed to load bills.', 'error');
            });
    }

    function focusAddProcessMessBillsModal() {
        var addModalEl = document.getElementById('addProcessMessBillsModal');
        if (!addModalEl || typeof bootstrap === 'undefined') return;
        var wasVisible = addModalEl.classList.contains('show');
        var addInst = bootstrap.Modal.getOrCreateInstance(addModalEl);
        addInst.show();
        if (wasVisible) loadModalBills();
    }

    function getFilteredModalBills() {
        return modalBillsData || [];
    }

    function updateModalPaginationNav(totalPages, filteredLength) {
        var nav = document.getElementById('modalPaginationNav');
        var prevBtn = document.getElementById('modalPaginationPrev');
        var nextBtn = document.getElementById('modalPaginationNext');
        var prevLi = document.getElementById('modalPaginationPrevLi');
        var nextLi = document.getElementById('modalPaginationNextLi');
        var pageLi = document.getElementById('modalPaginationPageLi');
        var label = document.getElementById('modalPaginationPageLabel');
        if (!nav || !prevBtn || !nextBtn || !label) return;
        if (totalPages <= 1 || !filteredLength) {
            nav.classList.add('d-none');
            return;
        }
        nav.classList.remove('d-none');
        label.textContent = 'Page ' + modalBillsCurrentPage + ' of ' + totalPages;
        var onFirst = modalBillsCurrentPage <= 1;
        var onLast = modalBillsCurrentPage >= totalPages;
        prevBtn.disabled = onFirst;
        nextBtn.disabled = onLast;
        if (prevLi) prevLi.classList.toggle('disabled', onFirst);
        if (nextLi) nextLi.classList.toggle('disabled', onLast);
        if (pageLi) pageLi.classList.add('disabled');
    }

    function formatInvoiceNotificationStatusCell(b) {
        if (!b || !b.invoice_notification_sent) {
            return '<span class="text-muted small">—</span>';
        }
        var readBadge = b.invoice_notification_read
            ? '<span class="badge rounded-pill bg-info-subtle text-info border border-info-subtle fw-semibold">Read</span>'
            : '<span class="badge rounded-pill bg-warning-subtle text-warning border border-warning-subtle fw-semibold">Unread</span>';
        var partialBadge = b.invoice_notification_partial
            ? '<span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle fw-semibold">New items (' + (b.invoice_notification_pending_count || 0) + ')</span>'
            : '';
        var sentLabel = b.invoice_notification_fully_sent
            ? 'Invoice Sent'
            : 'Invoice Sent (partial)';
        return '<div class="d-flex flex-column align-items-center gap-1">' +
            '<span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle fw-semibold">' + sentLabel + '</span>' +
            partialBadge +
            readBadge +
            '</div>';
    }

    function formatInvoiceNotificationStatusText(b) {
        if (!b || !b.invoice_notification_sent) {
            return '—';
        }
        var partial = b.invoice_notification_partial ? ' · New items pending' : '';
        return 'Invoice Sent · ' + (b.invoice_notification_read ? 'Read' : 'Unread') + partial;
    }

    function canSendInvoiceNotification(b) {
        if (!b) return true;
        return !b.invoice_notification_fully_sent;
    }
    window.formatInvoiceNotificationStatusText = formatInvoiceNotificationStatusText;

    function destroyModalBillsDataTableIfAny() {
        if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable) {
            return;
        }
        var $ = window.jQuery;
        var $table = $('#modalBillsTable');
        if (!$table.length) {
            return;
        }
        if ($.fn.DataTable.isDataTable($table)) {
            try {
                $table.DataTable().destroy();
            } catch (e) {}
        }
        var $wrapper = $table.closest('.dataTables_wrapper');
        if ($wrapper.length) {
            $table.detach();
            var $host = $('#modalBillsTableHost');
            if ($host.length) {
                $host.empty().append($table);
            } else {
                $wrapper.replaceWith($table);
            }
        }
    }

    function initModalBillsColumnManager() {
        if (typeof window.MessColumnManager === 'undefined' || typeof window.jQuery === 'undefined') {
            return;
        }
        destroyModalBillsDataTableIfAny();

        var $table = window.jQuery('#modalBillsTable');
        if (!$table.length) return;

        if (!window.MessColumnManager.get('modalBillsTable')) {
            window.MessColumnManager.init({
                tableId: 'modalBillsTable',
                mode: 'dom',
                $table: $table,
                colReorder: false,
                lockedColumns: [0],
                skipColumns: [7, 8]
            });
        } else {
            window.MessColumnManager.get('modalBillsTable').apply();
        }
    }

    function applyModalBillsColumnVisibility() {
        var mgr = window.MessColumnManager && window.MessColumnManager.get('modalBillsTable');
        if (mgr) {
            mgr.apply();
        }
    }

    function renderModalTable() {
        var tbody = document.getElementById('modalBillsTableBody');
        var modalSelectAllEl = document.getElementById('modalSelectAll');
        setModalBillsLoading(false);
        if (modalSelectAllEl) modalSelectAllEl.checked = false;
        var filtered = getFilteredModalBills();
        var perPage = parseInt((document.getElementById('modalPerPage') || {}).value || 10, 10);
        var totalPages = modalBillsTotal ? Math.ceil(modalBillsTotal / perPage) : 0;
        modalBillsCurrentPage = Math.max(1, Math.min(modalBillsCurrentPage, totalPages || 1));
        var start = modalBillsFrom ? modalBillsFrom - 1 : 0;
        var pageData = filtered;

        if (pageData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4 text-muted">No unpaid bills found. Adjust date range and click Load Bills.</td></tr>';
        } else {
            tbody.innerHTML = pageData.map(function(b, i) {
                var sn = b.sno || (start + i + 1);
                var printUrl = printReceiptBaseUrl.replace('__ID__', encodeURIComponent(b.id));
                if (String(b.id || '').indexOf('combined-') === 0) {
                    var receiptDf = b.date_from || getModalDateYmd('modal_date_from') || '';
                    var receiptDt = b.date_to || getModalDateYmd('modal_date_to') || '';
                    printUrl += (printUrl.indexOf('?') >= 0 ? '&' : '?') + 'date_from=' + encodeURIComponent(receiptDf) + '&date_to=' + encodeURIComponent(receiptDt);
                }
                var statusCell = formatInvoiceNotificationStatusCell(b);
                var invoiceFullySent = !!b.invoice_notification_fully_sent;
                var invoiceBtnClass = invoiceFullySent ? 'btn btn-outline-secondary generate-invoice-btn' : 'btn btn-outline-primary generate-invoice-btn';
                var invoiceBtnTitle = invoiceFullySent
                    ? 'Invoice already sent for all items in this range'
                    : (b.invoice_notification_partial ? 'Send invoice for new item(s)' : 'Generate Invoice');
                var invoiceBtnAttrs = 'data-bill-id="' + b.id + '" data-buyer-name="' + (b.buyer_name || '').replace(/"/g, '&quot;') + '" title="' + invoiceBtnTitle + '"' + (invoiceFullySent ? ' disabled data-invoice-sent="1"' : '');
                return '<tr class="' + (i % 2 === 0 ? 'table-light' : '') + '">' +
                    '<td><input type="checkbox" class="form-check-input modal-bill-check" data-id="' + b.id + '" data-name="' + (b.buyer_name || '').replace(/"/g, '&quot;') + '"></td>' +
                    '<td>' + sn + '</td>' +
                    '<td>' + (b.buyer_name || '—') + '</td>' +
                    '<td>' + (b.invoice_no || '—') + '</td>' +
                    '<td>' + (b.payment_type || '—') + '</td>' +
                    '<td class="text-end">' + (b.total || '0') + '</td>' +
                    '<td class="text-end fw-semibold">' + (b.total_due_amount || '0.00') + '</td>' +
                    '<td class="text-center">' + statusCell + '</td>' +
                    '<td class="text-center"><div class="btn-group btn-group-sm">' +
                    '<button type="button" class="' + invoiceBtnClass + '" ' + invoiceBtnAttrs + '>Invoice</button>' +
                    '<button type="button" class="btn btn-outline-success generate-payment-btn" data-bill-id="' + b.id + '" data-buyer-name="' + (b.buyer_name || '').replace(/"/g, '&quot;') + '" title="Mark as Paid">Payment</button>' +
                    '</div></td>' +
                    '<td class="text-center"><a href="' + printUrl + '" target="_blank" class="btn  btn-outline-secondary" title="Print receipt"><i class="material-symbols-rounded" style="font-size:1.1rem;">receipt</i></a></td>' +
                    '</tr>';
            }).join('');
        }

        document.getElementById('modalPaginationInfo').textContent = 'Showing ' + modalBillsFrom + ' to ' + modalBillsTo + ' of ' + modalBillsTotal + ' entries';
        updateModalPaginationNav(totalPages, modalBillsTotal);
        updateBulkActionsBar();
        applyModalBillsColumnVisibility();
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

    function clearChoicesSelection(el) {
        if (!el) return;
        if (el.choicesInstance) {
            var inst = el.choicesInstance;
            if (el.multiple && typeof inst.removeActiveItems === 'function') {
                inst.removeActiveItems();
                return;
            }
            if (typeof inst.setChoiceByValue === 'function') {
                try {
                    inst.setChoiceByValue(el.multiple ? [] : '');
                } catch (e) {
                    if (typeof inst.removeActiveItems === 'function') {
                        inst.removeActiveItems();
                    }
                }
            }
            return;
        }
        if (el.multiple) {
            Array.from(el.options || []).forEach(function (opt) {
                opt.selected = false;
            });
        } else {
            el.value = '';
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
        clearChoicesSelection(ct);
        if (ct) {
            ct.dispatchEvent(new Event('change', { bubbles: true }));
        }
        var bn = document.getElementById('modal_buyer_name');
        clearChoicesSelection(bn);
        if (bn && !bn.choicesInstance) {
            bn.innerHTML = '';
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

        modalBillsSortCol = 'buyer_name';
        modalBillsSortDir = 'asc';
        updateModalBillsSortHeaderIcons();

        loadModalBills();
    }

    document.getElementById('addProcessMessBillsModal').addEventListener('show.bs.modal', function() {
        updateModalBillsSortHeaderIcons();
        loadModalBills();
    });
    updateModalBillsSortHeaderIcons();
    var payNowModalForAddRedirect = document.getElementById('payNowModal');
    if (payNowModalForAddRedirect) {
        payNowModalForAddRedirect.addEventListener('hidden.bs.modal', function () {
            focusAddProcessMessBillsModal();
        });
    }
    document.getElementById('modalLoadBillsBtn').addEventListener('click', function() { loadModalBills(1); });
    document.getElementById('modalClearFiltersBtn').addEventListener('click', clearModalFilters);
    document.getElementById('modalSearch').addEventListener('input', function() {
        loadModalBills(1);
    });
    document.getElementById('modalPerPage').addEventListener('change', function() {
        loadModalBills(1);
    });
    document.querySelectorAll('#modalBillsTable .mess-sort-th[data-sort]').forEach(function (th) {
        th.addEventListener('click', function () {
            var col = th.getAttribute('data-sort');
            if (!col) return;
            if (modalBillsSortCol === col) {
                modalBillsSortDir = modalBillsSortDir === 'asc' ? 'desc' : 'asc';
            } else {
                modalBillsSortCol = col;
                modalBillsSortDir = (col === 'total' || col === 'sno') ? 'desc' : 'asc';
            }
            updateModalBillsSortHeaderIcons();
            loadModalBills(1);
        });
    });
    document.getElementById('modalPaginationPrev').addEventListener('click', function() {
        if (modalBillsCurrentPage > 1) {
            loadModalBills(modalBillsCurrentPage - 1);
        }
    });
    document.getElementById('modalPaginationNext').addEventListener('click', function() {
        var perPage = parseInt((document.getElementById('modalPerPage') || {}).value || 10, 10);
        var totalPages = modalBillsTotal ? Math.ceil(modalBillsTotal / perPage) : 0;
        if (modalBillsCurrentPage < totalPages) {
            loadModalBills(modalBillsCurrentPage + 1);
        }
    });

    // --- Client Type / Buyer dependent dropdowns in modal (similar to Sale Voucher Report) ---
    (function initModalClientTypeFilters() {
        var modalClientType = document.getElementById('modal_client_type');
        var modalClientTypePk = document.getElementById('modal_client_type_pk');
        var modalBuyerName = document.getElementById('modal_buyer_name');
        var studentsByCourseUrl = "{{ url('/admin/mess/selling-voucher-date-range/students-by-course') }}";
        var buyersForReportUrl = "{{ route('admin.mess.reports.category-wise-print-slip.buyers') }}";
        var courseBuyersByCourseUrl = "{{ url('/admin/mess/reports/category-wise-print-slip/course-buyers') }}";

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
            'academy staff': @json($filterEmployeeBuyerOptions ?? [], JSON_UNESCAPED_UNICODE),
            'faculty': @json($filterFacultyBuyerOptions ?? [], JSON_UNESCAPED_UNICODE),
            'mess staff': @json($filterMessStaffBuyerOptions ?? [], JSON_UNESCAPED_UNICODE)
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
            var selectedSlugs = getChoicesMultiValues(modalClientType);
            
            modalClientTypePk.innerHTML = '';

            var choicesPk = modalClientTypePk.choicesInstance || null;
            if (choicesPk) {
                choicesPk.clearStore();
                choicesPk.setChoices([], 'value', 'label', true);
            }

            modalPkToClientGroupKey = {};

            // Collect options from all selected slugs
            var allOptions = [];
            selectedSlugs.forEach(function(slug) {
                if ((slug === 'ot' || slug === 'course') && otCourseOptions.length) {
                    allOptions = allOptions.concat(otCourseOptions);
                } else if (slug && clientTypeOptions[slug]) {
                    clientTypeOptions[slug].forEach(function (o) {
                        allOptions.push(o);
                        if (o.dataClientName) {
                            modalPkToClientGroupKey[String(o.value)] = String(o.dataClientName);
                        }
                    });
                }
            });
            
            // Remove duplicates based on value
            var uniqueOptions = [];
            var seenValues = {};
            allOptions.forEach(function(o) {
                if (!seenValues[o.value]) {
                    seenValues[o.value] = true;
                    uniqueOptions.push(o);
                }
            });
            
            uniqueOptions.forEach(function (o) {
                var opt = document.createElement('option');
                opt.value = o.value;
                opt.textContent = o.text;
                if (o.dataClientName) {
                    opt.dataset.clientName = o.dataClientName;
                }
                modalClientTypePk.appendChild(opt);
            });

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
            var selectedSlugs = getChoicesMultiValues(modalClientType);
            var selectedPks = getChoicesMultiValues(modalClientTypePk);
            
            modalBuyerName.innerHTML = '';

            var choicesBuyer = modalBuyerName.choicesInstance || null;
            if (choicesBuyer) {
                choicesBuyer.clearStore();
                choicesBuyer.setChoices([], 'value', 'label', true);
            }

            function syncChoicesBuyer() {
                if (!choicesBuyer) return;
                var newChoices = Array.from(modalBuyerName.options).map(function (o) {
                    return { value: o.value, label: o.text, selected: o.selected };
                });
                choicesBuyer.clearStore();
                choicesBuyer.setChoices(newChoices, 'value', 'label', true);
            }

            function addBuyerOptions(list) {
                (list || []).forEach(function (o) {
                    var opt = document.createElement('option');
                    opt.value = o.value;
                    opt.textContent = o.text;
                    modalBuyerName.appendChild(opt);
                });
            }

            function getModalDateRangeYmd() {
                return {
                    from: getModalDateYmd('modal_date_from'),
                    to: getModalDateYmd('modal_date_to')
                };
            }

            function loadBuyersFromReportEndpoint(slugToLoad) {
                var range = getModalDateRangeYmd();
                var qs = new URLSearchParams();
                qs.set('client_type_slug', slugToLoad);
                if (range.from) qs.set('from_date', range.from);
                if (range.to) qs.set('to_date', range.to);

                // Add PK if selected (course/ot => course_master_pk, others => client_type_pk)
                var selectedPk = selectedPks[0] || '';
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
                            var v = String(name || '').trim();
                            return v ? { value: v, text: v } : null;
                        }).filter(Boolean);
                        addBuyerOptions(buyers);
                        syncChoicesBuyer();
                    })
                    .catch(function () {
                        syncChoicesBuyer();
                    });
            }

            // No client type: show full buyer name list (same as legacy single-select "All" path)
            if (selectedSlugs.length === 0) {
                if ((allBuyerNames || []).length) {
                    var listAllEmpty = (allBuyerNames || []).map(function (name) {
                        return { value: name, text: name };
                    });
                    addBuyerOptions(listAllEmpty);
                }
                syncChoicesBuyer();
                return;
            }

            // Multiple client types selected: merge buyer lists from each slug
            if (selectedSlugs.length > 1) {
                var allBuyers = [];

                selectedSlugs.forEach(function (slug) {
                    if (slug === 'employee') {
                        allBuyers = allBuyers.concat(employeeNames['academy staff'] || [])
                            .concat(employeeNames['faculty'] || [])
                            .concat(employeeNames['mess staff'] || []);
                    } else if (slug === 'ot') {
                        allBuyers = allBuyers.concat((otBuyerNames || []).map(function (name) { return { value: name, text: name }; }));
                    } else if (slug === 'course') {
                        allBuyers = allBuyers.concat((courseBuyerNames || []).map(function (name) { return { value: name, text: name }; }));
                    } else if (slug === 'other') {
                        allBuyers = allBuyers.concat((otherBuyerNames || []).map(function (name) { return { value: name, text: name }; }));
                    } else if (slug === 'section') {
                        allBuyers = allBuyers.concat((sectionBuyerNames || []).map(function (name) { return { value: name, text: name }; }));
                    }
                });

                var mapMulti = new Map();
                allBuyers.forEach(function (o) {
                    var key = String(o.value || '').trim().toLowerCase();
                    if (!key) return;
                    if (!mapMulti.has(key)) mapMulti.set(key, { value: o.value, text: o.text });
                });
                var uniqueMulti = Array.from(mapMulti.values()).sort(function (a, b) {
                    return String(a.text || '').localeCompare(String(b.text || ''), undefined, { sensitivity: 'base' });
                });

                addBuyerOptions(uniqueMulti);
                syncChoicesBuyer();
                return;
            }
            
            // Single slug selected: use existing logic
            var slug = selectedSlugs[0];
            var selectedPk = selectedPks[0] || '';

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
                syncChoicesBuyer();
            } else if (slug === 'ot' && selectedPk) {
                // OT + specific course: students by course
                fetch(studentsByCourseUrl + '/' + selectedPk, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        var students = (data.students || []).map(function (s) {
                            return { value: s.display_name || '', text: s.display_name || '—' };
                        });
                        addBuyerOptions(students);
                        syncChoicesBuyer();
                    })
                    .catch(function () {
                        // ignore error; no buyer options
                        syncChoicesBuyer();
                    });
            } else if (slug === 'ot' && !selectedPk) {
                // OT + All:
                // 1) Prefer voucher-based buyer list (respects modal date range)
                // 2) If empty, fallback to students from ALL OT courses
                var range2 = getModalDateRangeYmd();
                var qsOt = new URLSearchParams();
                qsOt.set('client_type_slug', 'ot');
                if (range2.from) qsOt.set('from_date', range2.from);
                if (range2.to) qsOt.set('to_date', range2.to);

                function loadStudentsAllOtCourses() {
                    var coursePks = (otCourseOptions || []).map(function (o) { return o.value; }).filter(Boolean);
                    if (!coursePks.length) {
                        syncChoicesBuyer();
                        return;
                    }

                    Promise.all(coursePks.map(function (coursePk) {
                        return fetch(studentsByCourseUrl + '/' + coursePk, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                            .then(function (r) { return r.json(); })
                            .then(function (data) {
                                return (data.students || []).map(function (s) {
                                    return String(s.display_name || '').trim();
                                }).filter(function (n) { return !!n; });
                            })
                            .catch(function () { return []; });
                    }))
                        .then(function (results) {
                            var seen = new Set();
                            var all = [];
                            (results || []).forEach(function (names) {
                                (names || []).forEach(function (n) {
                                    var key = String(n || '').trim();
                                    if (!key || seen.has(key)) return;
                                    seen.add(key);
                                    all.push({ value: key, text: key });
                                });
                            });
                            all.sort(function (a, b) {
                                return String(a.text || '').localeCompare(String(b.text || ''), undefined, { sensitivity: 'base' });
                            });
                            addBuyerOptions(all);
                            syncChoicesBuyer();
                        })
                        .catch(function () {
                            syncChoicesBuyer();
                        });
                }

                fetch(buyersForReportUrl + '?' + qsOt.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        var buyers = (data.buyers || []).map(function (name) { return String(name || '').trim(); })
                            .filter(function (v) { return !!v; })
                            .map(function (v) { return { value: v, text: v }; });
                        if (buyers.length) {
                            addBuyerOptions(buyers);
                            syncChoicesBuyer();
                            return;
                        }
                        loadStudentsAllOtCourses();
                    })
                    .catch(function () {
                        loadStudentsAllOtCourses();
                    });
                return; // async
            } else if (slug === 'course') {
                // Course: same as Sale Voucher logic
                // - Specific course => buyer names for that course (date filtered)
                // - All => buyer names across course vouchers (date filtered)
                if (selectedPk) {
                    var range3 = getModalDateRangeYmd();
                    var qsC = new URLSearchParams();
                    if (range3.from) qsC.set('from_date', range3.from);
                    if (range3.to) qsC.set('to_date', range3.to);
                    var url = courseBuyersByCourseUrl + '/' + selectedPk + (qsC.toString() ? ('?' + qsC.toString()) : '');
                    fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            var buyers = (data.buyers || []).map(function (name) {
                                var v = String(name || '').trim();
                                return v ? { value: v, text: v } : null;
                            }).filter(Boolean);
                            addBuyerOptions(buyers);
                            syncChoicesBuyer();
                        })
                        .catch(function () {
                            // fallback: buyers endpoint still respects course_master_pk
                            loadBuyersFromReportEndpoint('course');
                        });
                    return; // async
                }

                loadBuyersFromReportEndpoint('course');
                return; // async
            } else if (slug === 'other') {
                loadBuyersFromReportEndpoint('other');
                return; // async
            } else if (slug === 'section') {
                loadBuyersFromReportEndpoint('section');
                return; // async
            } else if (!slug && (allBuyerNames || []).length) {
                // koi client type select nahi – saare distinct buyer names (course/other/section etc.) dikhado
                var listAll = (allBuyerNames || []).map(function (name) {
                    return { value: name, text: name };
                });
                addBuyerOptions(listAll);
                syncChoicesBuyer();
            } else {
                syncChoicesBuyer();
            }
        }

        function scheduleFillModalClientTypePk() {
            setTimeout(fillModalClientTypePk, 0);
        }

        modalClientType.addEventListener('change', scheduleFillModalClientTypePk);
        modalClientTypePk.addEventListener('change', fillModalBuyerNames);
        ['addItem', 'removeItem'].forEach(function (eventName) {
            modalClientType.addEventListener(eventName, scheduleFillModalClientTypePk);
        });

        window.fillModalClientTypePk = fillModalClientTypePk;

        // Note: Initial fill is now called when modal is shown (after Choices.js init)
    })();

    // --- Main "Process Mess Bills" filters – Employee / OT / Course + Client Type + Buyer Name ---
    (function initMainClientTypeFilters() {
        var clientTypeSlug = document.getElementById('filterClientTypeSlug');
        var clientTypePk = document.getElementById('filterClientTypePk');
        var buyerSelect = document.getElementById('filterBuyerName');
        var studentsByCourseUrl = "{{ url('/admin/mess/selling-voucher-date-range/students-by-course') }}";
        var buyersForReportUrl = "{{ route('admin.mess.reports.category-wise-print-slip.buyers') }}";
        var courseBuyersByCourseUrl = "{{ url('/admin/mess/reports/category-wise-print-slip/course-buyers') }}";
        var preservedClientTypePk = {!! json_encode((array) ($clientTypePks ?? request('client_type_pk', []))) !!};
        var preservedBuyerName = {!! json_encode((array) ($buyerNames ?? request('buyer_name', []))) !!};

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
            'academy staff': @json($filterEmployeeBuyerOptions ?? [], JSON_UNESCAPED_UNICODE),
            'faculty': @json($filterFacultyBuyerOptions ?? [], JSON_UNESCAPED_UNICODE),
            'mess staff': @json($filterMessStaffBuyerOptions ?? [], JSON_UNESCAPED_UNICODE)
        };
        var otBuyerNames = {!! json_encode(($otBuyerNames ?? collect())->values()->all(), JSON_UNESCAPED_UNICODE) !!};
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
            // Get selected slugs (now multiselect)
            var selectedSlugs = [];
            if (clientTypeSlug.choicesInstance) {
                selectedSlugs = clientTypeSlug.choicesInstance.getValue(true);
            } else {
                selectedSlugs = Array.from(clientTypeSlug.selectedOptions).map(function(opt) { return opt.value; });
            }
            
            // Get selected pks (now multiselect)
            var selectedPks = [];
            if (clientTypePk.choicesInstance) {
                selectedPks = clientTypePk.choicesInstance.getValue(true);
            } else {
                selectedPks = Array.from(clientTypePk.selectedOptions).map(function(opt) { return opt.value; });
            }
            
            var currentBuyer = preserve ? preservedBuyerName : [];
            console.log('=== fillBuyerSelect START ===');
            console.log('selectedSlugs:', selectedSlugs, 'selectedPks:', selectedPks, 'preserve:', preserve);
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
            buyerSelect.innerHTML = '';

            function addOptions(list) {
                console.log('addOptions called with', list ? list.length : 0, 'items');
                (list || []).forEach(function (o) {
                    var opt = document.createElement('option');
                    opt.value = o.value;
                    opt.textContent = o.text;
                    buyerSelect.appendChild(opt);
                    console.log('Added option:', o.text);
                });
                if (Array.isArray(currentBuyer) && currentBuyer.length) {
                    Array.from(buyerSelect.options).forEach(function (option) {
                        option.selected = currentBuyer.indexOf(option.value) !== -1;
                    });
                    console.log('Set current buyers to:', currentBuyer);
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
                            if (Array.isArray(currentBuyer) && currentBuyer.length && buyerSelect.choicesInstance) {
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

            // When multiple slugs are selected, load buyers from all of them
            if (selectedSlugs.length > 1 || selectedSlugs.length === 0) {
                // Multiple client types or none selected: load buyers from report endpoint for all selected types
                var allBuyers = [];
                var promises = [];
                
                selectedSlugs.forEach(function(slug) {
                    if (slug === 'employee') {
                        // Add all employee buyers
                        allBuyers = allBuyers.concat(employeeNames['academy staff'] || [])
                            .concat(employeeNames['faculty'] || [])
                            .concat(employeeNames['mess staff'] || []);
                    } else if (slug === 'ot') {
                        allBuyers = allBuyers.concat((otBuyerNames || []).map(function (name) { return { value: name, text: name }; }));
                    } else if (slug === 'course') {
                        allBuyers = allBuyers.concat((courseBuyerNames || []).map(function (name) { return { value: name, text: name }; }));
                    } else if (slug === 'other') {
                        allBuyers = allBuyers.concat((otherBuyerNames || []).map(function (name) { return { value: name, text: name }; }));
                    } else if (slug === 'section') {
                        allBuyers = allBuyers.concat((sectionBuyerNames || []).map(function (name) { return { value: name, text: name }; }));
                    }
                });
                
                // De-duplicate
                var map = new Map();
                allBuyers.forEach(function (o) {
                    var key = String(o.value || '').trim().toLowerCase();
                    if (!key) return;
                    if (!map.has(key)) map.set(key, { value: o.value, text: o.text });
                });
                var unique = Array.from(map.values()).sort(function (a, b) {
                    return String(a.text || '').localeCompare(String(b.text || ''), undefined, { sensitivity: 'base' });
                });
                
                addOptions(unique);
                
                if (typeof window.Choices !== 'undefined') {
                    initChoicesElement(buyerSelect);
                    if (Array.isArray(currentBuyer) && currentBuyer.length && buyerSelect.choicesInstance) {
                        try {
                            buyerSelect.choicesInstance.setChoiceByValue(currentBuyer);
                        } catch (e) {
                            console.error('Error setting buyer values:', e);
                        }
                    }
                }
                return;
            }
            
            // Single slug selected: use existing logic
            var slug = selectedSlugs[0];
            var selectedPk = selectedPks[0] || '';

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
                            if (Array.isArray(currentBuyer) && currentBuyer.length) {
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
                // OT selected but Course = All
                // Same behavior as Sale Voucher Report:
                // 1) Prefer buyer list from report endpoint (respects date filters)
                // 2) If empty, fallback to loading students from ALL OT courses

                var df2 = document.getElementById('date_from');
                var dt2 = document.getElementById('date_to');
                var dateFromYmd2 = (df2 && df2.value) ? toYmd(df2.value) : '';
                var dateToYmd2 = (dt2 && dt2.value) ? toYmd(dt2.value) : '';

                function initBuyerChoicesAfterAsync() {
                    if (typeof window.Choices !== 'undefined') {
                        initChoicesElement(buyerSelect);
                        if (Array.isArray(currentBuyer) && currentBuyer.length && buyerSelect.choicesInstance) {
                            try { buyerSelect.choicesInstance.setChoiceByValue(currentBuyer); } catch (e) {}
                        }
                    }
                }

                function loadStudentsAllOtCourses() {
                    var coursePks = (otCourseOptions || []).map(function (o) { return o.value; }).filter(Boolean);
                    if (!coursePks.length) {
                        initBuyerChoicesAfterAsync();
                        return;
                    }

                    Promise.all(coursePks.map(function (coursePk) {
                        return fetch(studentsByCourseUrl + '/' + coursePk, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                            .then(function (r) { return r.json(); })
                            .then(function (data) {
                                return (data.students || []).map(function (s) {
                                    return String(s.display_name || '').trim();
                                }).filter(function (n) { return !!n; });
                            })
                            .catch(function () { return []; });
                    }))
                        .then(function (results) {
                            var seen = new Set();
                            var all = [];
                            (results || []).forEach(function (names) {
                                (names || []).forEach(function (n) {
                                    var key = String(n || '').trim();
                                    if (!key || seen.has(key)) return;
                                    seen.add(key);
                                    all.push({ value: key, text: key });
                                });
                            });
                            all.sort(function (a, b) {
                                return String(a.text || '').localeCompare(String(b.text || ''), undefined, { sensitivity: 'base' });
                            });
                            addOptions(all);
                            initBuyerChoicesAfterAsync();
                        })
                        .catch(function () {
                            initBuyerChoicesAfterAsync();
                        });
                }

                // Try report endpoint first
                var qsOt = new URLSearchParams();
                qsOt.set('client_type_slug', 'ot');
                if (dateFromYmd2) qsOt.set('from_date', dateFromYmd2);
                if (dateToYmd2) qsOt.set('to_date', dateToYmd2);

                fetch(buyersForReportUrl + '?' + qsOt.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        var buyers = (data.buyers || []).map(function (name) { return String(name || '').trim(); })
                            .filter(function (v) { return !!v; })
                            .map(function (v) { return { value: v, text: v }; });

                        if (buyers.length) {
                            addOptions(buyers);
                            initBuyerChoicesAfterAsync();
                            return;
                        }

                        loadStudentsAllOtCourses();
                    })
                    .catch(function () {
                        loadStudentsAllOtCourses();
                    });

                return; // async branch
            } else if (slug === 'course') {
                // Same behavior as Sale Voucher filter:
                // - If a specific course is selected => show buyer names for that course
                // - If course = All => show buyer names across all course vouchers (respecting date filters)
                if (selectedPk) {
                    var df = document.getElementById('date_from');
                    var dt = document.getElementById('date_to');
                    var dateFromYmd = (df && df.value) ? toYmd(df.value) : '';
                    var dateToYmd = (dt && dt.value) ? toYmd(dt.value) : '';

                    var qs = new URLSearchParams();
                    if (dateFromYmd) qs.set('from_date', dateFromYmd);
                    if (dateToYmd) qs.set('to_date', dateToYmd);

                    var url = courseBuyersByCourseUrl + '/' + selectedPk + (qs.toString() ? ('?' + qs.toString()) : '');
                    fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            var buyers = (data.buyers || []).map(function (name) {
                                return { value: name || '', text: name || '—' };
                            }).filter(function (o) { return !!o.value; });

                            addOptions(buyers);

                            if (typeof window.Choices !== 'undefined') {
                                initChoicesElement(buyerSelect);
                                if (currentBuyer && buyerSelect.choicesInstance) {
                                    buyerSelect.choicesInstance.setChoiceByValue(currentBuyer);
                                }
                            }
                        })
                        .catch(function () {
                            // Fallback to the report-based endpoint (still respects selected course + dates)
                            loadBuyersFromReportEndpoint('course');
                        });

                    return; // async branch
                }

                // Course + "All"
                loadBuyersFromReportEndpoint('course');
                return; // async branch
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
            preservedClientTypePk = []; // reset when main type changes
            preservedBuyerName = []; // reset when main type changes
            fillClientTypePk(false);
        });
        clientTypePk.addEventListener('change', function () {
            preservedBuyerName = [];
            fillBuyerSelect(false);
        });

        // Initial populate on page load - delay to ensure Choices.js is initialized
        setTimeout(function() {
            fillClientTypePk(true);
        }, 100);
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
            var fromYmd = getModalDateYmd('modal_date_from');
            var toYmdVal = getModalDateYmd('modal_date_to');
            if (fromYmd) body.date_from = fromYmd;
            if (toYmdVal) body.date_to = toYmdVal;
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
                loadModalBills(modalBillsCurrentPage);
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
                var payNowWasOpen = payNowModalEl && payNowModalEl.classList.contains('show');
                if (payNowModalEl && typeof bootstrap !== 'undefined') {
                    var payInst = bootstrap.Modal.getInstance(payNowModalEl);
                    if (payInst) payInst.hide();
                }
                if (!payNowWasOpen) {
                    focusAddProcessMessBillsModal();
                }
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

    // function formatAmountTwoDecimals(value) {
    //     var num = parseFloat(value);
    //     return isNaN(num) ? '0.00' : num.toFixed(2);
    // }

    function formatPayDetailAmount(value) {
        var num = parseFloat(value);
        if (isNaN(num)) return '0.00';
        var parts = num.toFixed(2).split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.join('.');
    }

    function getPayNowTotalDueCap(content) {
        if (!content) return 0;
        var totalDueRaw = content.getAttribute('data-total-due-amount-raw');
        if (totalDueRaw === null || totalDueRaw === '') return 0;
        var totalDue = parseFloat(totalDueRaw);
        return isNaN(totalDue) || totalDue < 0 ? 0 : totalDue;
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
            '<div class="summary-row"><span class="summary-label">Total Due Amount</span><span class="summary-value">' + (data.total_due_amount || data.due_amount || '0.0') + '</span></div>' +
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
                var totalDueRaw = data.total_due_amount_raw != null
                    ? data.total_due_amount_raw
                    : (parseFloat(String(data.total_due_amount || '0').replace(/,/g, '')) || 0);
                content.setAttribute('data-total-due-amount-raw', totalDueRaw);
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
        var totalDueRaw = content && content.getAttribute('data-total-due-amount-raw');
        var totalDue = totalDueRaw !== null && totalDueRaw !== '' ? parseFloat(totalDueRaw) : NaN;
        var totalDueEl = document.getElementById('payNowTotalDueAmount');
        if (totalDueEl) {
            totalDueEl.textContent = isNaN(totalDue) ? '—' : formatPayDetailAmount(totalDue);
        }
        var totalDueCap = getPayNowTotalDueCap(content);
        var amountInput = document.getElementById('payNowAmount');
        amountInput.value = isNaN(due) ? '' : due;
        amountInput.setAttribute('max', totalDueCap > 0 ? totalDueCap : ((isNaN(due) || due < 0) ? '' : due));
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
        var totalDueCap = getPayNowTotalDueCap(content);
        var amountEl = document.getElementById('payNowAmount');
        var modeEl = document.getElementById('payNowPaymentMode');
        var dateEl = document.getElementById('payNowPaymentDate');
        var amount = amountEl && amountEl.value ? amountEl.value : '';
        var paymentMode = modeEl && modeEl.value ? modeEl.value : 'cash';
        var paymentDate = dateEl && dateEl.value ? dateEl.value : '';
        if (!amount) { showToast('Please enter amount.', 'error'); return; }
        var amountNum = parseFloat(amount);
        if (isNaN(amountNum) || amountNum <= 0) { showToast('Please enter a valid amount.', 'error'); return; }
        if (totalDueCap <= 0) {
            showToast('This bill has no outstanding due amount.', 'error');
            return;
        }
        if (amountNum > totalDueCap) {
            showToast('Amount cannot exceed total due amount.', 'error');
            return;
        }
        // var payload = { amount: amountNum.toFixed(2), payment_mode: paymentMode, payment_date: paymentDate };
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
            if (invoiceBtn.disabled || invoiceBtn.getAttribute('data-invoice-sent') === '1') {
                showToast('Already sent invoice for all items in this date range.', 'error');
                return;
            }
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
                dateFromYmd = getModalDateYmd('modal_date_from') || null;
                dateToYmd = getModalDateYmd('modal_date_to') || null;
            }
            openPaymentDetailsModal(billId, dateFromYmd, dateToYmd);
            return;
        }
    }, true);

    // Bulk actions
    document.getElementById('modalBulkInvoiceBtn').addEventListener('click', function() {
        var ids = Array.from(document.querySelectorAll('#addProcessMessBillsModal .modal-bill-check:checked')).map(function(c) { return c.getAttribute('data-id'); });
        if (ids.length === 0) { showToast('Select at least one bill.', 'error'); return; }
        var toSend = ids.filter(function(id) {
            var b = (modalBillsData || []).find(function(x) { return String(x.id) === String(id); });
            return canSendInvoiceNotification(b);
        });
        var skipped = ids.length - toSend.length;
        if (toSend.length === 0) {
            showToast('Already sent invoice for all items in the selected date range.', 'error');
            return;
        }
        if (!confirm('Generate invoice for ' + toSend.length + ' selected bill(s)?')) return;
        if (skipped > 0) {
            showToast('Skipping ' + skipped + ' bill(s): all items already notified.', 'error');
        }
        toSend.forEach(function(id) {
            doGenerateInvoice(id, '', null);
        });
        showToast('Processing ' + toSend.length + ' invoice(s)...');
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
