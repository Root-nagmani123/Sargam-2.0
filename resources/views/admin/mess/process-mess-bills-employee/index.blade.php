@extends('admin.layouts.master')
@section('title', 'Billing and Finance')
@section('content')
<div class="container-fluid py-3 py-md-4 process-mess-bills-employee-report pmbe-page">
    <x-breadcrum title="Billing and Finance" :showBack="false">
        <a href="{{ route('admin.mess.process-mess-bills-employee.generate-invoice-page') }}"
           class="btn btn-primary d-inline-flex align-items-center gap-2">
            <i class="material-symbols-rounded" style="font-size: 1.1rem;">add</i>
            <span>Generate Invoice</span>
        </a>
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
           class="btn pmbe-export-btn text-primary" title="Download (Excel)" data-mess-excel-export="processMessBillsTable">
            <i class="material-symbols-rounded">download</i><span>Download</span>
        </a>
        <button type="button" class="btn pmbe-export-btn text-primary" title="Print" onclick="printProcessMessBillsMainTable()">
            <i class="material-symbols-rounded">print</i><span>Print</span>
        </button>
    </div>

    {{-- Table card --}}
    <div class="card border-0 shadow">
        <div class="card-body p-3 p-lg-4">
            {{-- Filter toolbar (auto-apply; filters wrap on narrow screens) --}}
            <div class="d-flex align-items-center gap-2 mb-3 pmbe-toolbar no-print">
                <form method="GET" action="{{ route('admin.mess.process-mess-bills-employee.index') }}" id="mainFilterForm" class="d-flex align-items-center gap-2 pmbe-filter-form">
                    <input type="hidden" name="refresh" value="1">
                    <span class="programme-dt-filters-label flex-shrink-0 align-self-center">Filter</span>
                    <div id="pmbeFilterItems" class="d-flex align-items-center gap-2 pmbe-filter-items">
                        <div class="pmbe-filter-item" data-filter="status">
                            <select name="status" id="filterStatus" class="form-select pmbe-filter-select pmbe-auto-filter" data-placeholder="Status">
                                <option value="">Status</option>
                                <option value="unpaid" {{ $currentStatus === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                <option value="partial" {{ $currentStatus === 'partial' ? 'selected' : '' }}>Partial</option>
                                <option value="paid" {{ $currentStatus === 'paid' ? 'selected' : '' }}>Paid</option>
                            </select>
                        </div>
                        <div class="pmbe-filter-item" data-filter="client_type">
                            <select name="client_type" id="filterClientTypeSlug" class="form-select pmbe-filter-select pmbe-auto-filter" data-placeholder="Client Type" data-clears="filterClientTypePk,filterBuyerName">
                                <option value="">Client Type</option>
                                @foreach($clientTypes ?? [] as $key => $label)
                                    <option value="{{ $key }}" {{ in_array($key, $selectedClientTypes) ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pmbe-filter-item" data-filter="client_type_pk">
                            <select name="client_type_pk" id="filterClientTypePk" class="form-select pmbe-filter-select pmbe-auto-filter" data-placeholder="Client Category" data-clears="filterBuyerName">
                                <option value="">Client Category</option>
                            </select>
                        </div>
                        <div class="pmbe-filter-item" data-filter="buyer">
                            <select name="buyer_name" id="filterBuyerName" class="form-select pmbe-filter-select pmbe-auto-filter" data-placeholder="Buyer">
                                <option value="">Buyer</option>
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
                        <div class="pmbe-filter-item" data-filter="date">
                            <input type="text" id="pmbe_date_range" class="form-control pmbe-filter-range" placeholder="Select date range" autocomplete="off" readonly>
                            {{-- Range picker fills these; names preserved for the backend + existing JS. --}}
                            <input type="hidden" name="date_from" id="date_from" value="{{ $effectiveDateFrom ?? request('date_from', now()->startOfMonth()->format('d-m-Y')) }}" data-default-ymd="{{ $effectiveDateFromYmd ?? now()->startOfMonth()->format('Y-m-d') }}">
                            <input type="hidden" name="date_to" id="date_to" value="{{ $effectiveDateTo ?? request('date_to', now()->endOfMonth()->format('d-m-Y')) }}" data-default-ymd="{{ $effectiveDateToYmd ?? now()->endOfMonth()->format('Y-m-d') }}">
                        </div>
                        <div class="pmbe-filter-item" data-filter="invoice">
                            {{-- Blank here means "all invoices", not "not sent" — it must not
                                 read the same as the option below it. --}}
                            <select name="invoice_sent" id="filterInvoiceSent" class="form-select pmbe-filter-select pmbe-auto-filter" data-placeholder="All Invoices">
                                <option value="">All Invoices</option>
                                <option value="sent" {{ ($currentInvoiceSent ?? '') === 'sent' ? 'selected' : '' }}>Invoice Sent</option>
                            </select>
                        </div>
                    </div>

                    {{-- Overflow: filters that don't fit collapse into this "+N Filter" popover --}}
                    <div class="dropdown flex-shrink-0 align-self-center d-none" id="pmbeMoreFilterWrap">
                        <a href="javascript:void(0)" class="pmbe-more-filters dropdown-toggle border-0 bg-transparent" id="pmbeMoreFilterToggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">+ Filter</a>
                        <div class="dropdown-menu pmbe-more-menu shadow border rounded-1">
                            <div class="pmbe-more-header mb-2 d-flex align-items-center justify-content-between">
                                <span class="fw-semibold text-muted small">Filters</span>
                                <button type="button" class="btn-close btn-close-sm" aria-label="Close filters"
                                    data-pmbe-close-more></button>
                            </div>
                            <div id="pmbeMoreFilterItems"></div>
                        </div>
                    </div>

                    <a href="{{ route('admin.mess.process-mess-bills-employee.index') }}" class="programme-dt-btn-reset flex-shrink-0 align-self-center text-decoration-none d-inline-flex align-items-center justify-content-center" title="Remove all filters">Remove Filter</a>
                </form>
                <div class="d-flex align-items-center gap-2 ms-auto flex-shrink-0 align-self-center">
                    <button type="button" class="btn programme-dt-btn-columns" id="pmbeColumnsBtn" data-bs-toggle="modal" data-bs-target="#pmbeColumnVisibilityModal" title="Show / hide columns">
                        <i class="material-symbols-rounded">view_column</i><span>Columns</span>
                    </button>
                    {{-- The design collapses search to an icon; the global enhancer still
                         injects the real DataTables filter into the slot below. --}}
                    <div class="pmbe-search-wrap d-flex align-items-center" id="pmbeSearchWrap">
                        <button type="button" class="pmbe-search-toggle" id="pmbeSearchToggle"
                            aria-expanded="false" aria-label="Search" title="Search">
                            <i class="material-symbols-rounded">search</i>
                        </button>
                        <div class="programme-dt-search" data-dt-search-for="processMessBillsTable"></div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table programme-dt-table text-nowrap align-middle mb-0" id="processMessBillsTable" data-mess-datatable-server-side="1">
                    <thead class="table-light">
                        <tr>
                            <th class="text-nowrap py-2 mess-sort-th mess-report-sort-th" data-mess-col-original="S.No."><span class="d-inline-flex align-items-center gap-1"><span>S.No.</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                            <th class="text-nowrap py-2 mess-sort-th mess-report-sort-th mess-th-sorted" data-mess-col-original="Buyer Name"><span class="d-inline-flex align-items-center gap-1"><span>Buyer Name</span><span class="mess-report-sort-icon material-symbols-rounded" aria-hidden="true">arrow_upward</span></span></th>
                            <th class="text-nowrap py-2 mess-sort-th mess-report-sort-th" data-mess-col-original="Slip Number"><span class="d-inline-flex align-items-center gap-1"><span>Slip Number</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                            <th class="text-nowrap py-2 mess-sort-th mess-report-sort-th" data-mess-col-original="Date"><span class="d-inline-flex align-items-center gap-1"><span>Date</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                            <th class="text-nowrap py-2 mess-sort-th mess-report-sort-th" data-mess-col-original="Client Type"><span class="d-inline-flex align-items-center gap-1"><span>Client Type</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                            <th class="text-nowrap py-2 text-end mess-sort-th mess-report-sort-th" data-mess-col-original="Total"><span class="d-inline-flex align-items-center gap-1"><span>Total</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                            <th class="text-nowrap py-2 text-end mess-sort-th mess-report-sort-th" data-mess-col-original="Total Due Amount"><span class="d-inline-flex align-items-center gap-1"><span>Total Due Amount</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                            <th class="text-nowrap py-2 mess-sort-th mess-report-sort-th" data-mess-col-original="Payment Type"><span class="d-inline-flex align-items-center gap-1"><span>Payment Type</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                            <th class="text-nowrap py-2 mess-sort-th mess-report-sort-th" data-mess-col-original="Status"><span class="d-inline-flex align-items-center gap-1"><span>Status</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                            <th class="text-nowrap py-2 text-center no-print">Action</th>
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
                                       class="pmbe-act pmbe-act--receipt" title="Print receipt ({{ $cb->combined_invoice_no ?? 'Invoice' }})">
                                        <span class="pmbe-act__icon"><i class="material-symbols-rounded">receipt_long</i></span>
                                        <span class="pmbe-act__label">Receipt</span>
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
@include('admin.mess.process-mess-bills-employee.partials.payment-modals')

{{-- Generate Invoice & Payment Modal --}}
@include('admin.mess.reports.partials.report-styles')
{{-- pmbe-page so the toolbar/pill styles apply here as well as on the page --}}

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

{{-- Page JS lives in public/js/process-mess-bills-employee.js; PHP-derived values injected via window.PMBE_CFG.
     (The print functions below stay inline: they embed literal </script> markup that relies on inline-script parsing.) --}}
@include('admin.mess.process-mess-bills-employee.partials.config-script')
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
    }
}
</script>
@endpush

@endsection

@push('styles')
{{-- Select2 powers the filter pills; its JS is global (admin footer), the CSS is per page.
     Base stylesheet only — css/select2-theme.css is scoped to the Student Medical
     Exemption pages by design, and the pill skin below is self-contained. --}}
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}" />
    {{-- Choices.js via CDN --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <link rel="stylesheet" href="{{ asset('css/process-mess-bills-employee.css') }}?v={{ @filemtime(public_path('css/process-mess-bills-employee.css')) }}">
@endpush

@push('scripts')
    {{-- Choices.js via CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
@endpush
