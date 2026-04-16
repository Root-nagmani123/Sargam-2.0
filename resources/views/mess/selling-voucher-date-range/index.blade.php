@extends('admin.layouts.master')
@section('title', 'Selling Voucher with Date Range')
@section('setup_content')
@php
$canDeleteSellingVoucherDateRange = hasRole('Admin') || hasRole('Mess-Admin');
$selectedStatuses = collect((array) request()->input('status', []))
->filter(fn ($value) => $value !== null && $value !== '')
->map(fn ($value) => (string) $value)
->values()
->all();
$selectedStores = collect((array) request()->input('store', []))
->filter(fn ($value) => $value !== null && $value !== '')
->map(fn ($value) => (string) $value)
->values()
->all();
@endphp
<div class="container-fluid py-2 py-lg-3">
    <x-breadcrum title="Selling Voucher with Date Range"></x-breadcrum>


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

    <div class="card mb-4 border-0 shadow-sm rounded-4 selling-voucher-filter">
        <div class="card-body p-3 p-lg-4">
            <div class="d-flex justify-content-between align-items-start align-items-md-center mb-4 flex-wrap gap-3">
                <div>
                    <h4 class="mb-1 fw-semibold">Selling Voucher with Date Range</h4>
                    <p class="mb-0 small">Review and manage selling vouchers across a selected date range.</p>
                </div>
                <button type="button" class="btn btn-primary shadow-sm d-inline-flex align-items-center gap-2 px-3"
                    data-bs-toggle="modal" data-bs-target="#addReportModal">
                    <i class="material-symbols-rounded" style="font-size: 1.1rem;">add</i>
                    <span>Add Voucher</span>
                </button>
            </div>
            <hr class="my-4">
            <form method="GET" action="{{ route('admin.mess.selling-voucher-date-range.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                        <label class="form-label small fw-semibold text-uppercase mb-1">Status</label>
                        <select name="status[]" class="form-select voucher-filter-multiselect" multiple
                            data-placeholder="All Statuses">
                            <option value="" disabled>All</option>
                            <option value="0" {{ in_array('0', $selectedStatuses, true) ? 'selected' : '' }}>Pending
                            </option>
                            <option value="1" {{ in_array('1', $selectedStatuses, true) ? 'selected' : '' }}>Final
                            </option>
                            <option value="2" {{ in_array('2', $selectedStatuses, true) ? 'selected' : '' }}>Approved
                            </option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                        <label class="form-label small fw-semibold text-uppercase mb-1">Store</label>
                        <select name="store[]" class="form-select voucher-filter-multiselect" multiple
                            data-placeholder="All Stores">
                            <option value="" disabled>All</option>
                            @foreach($stores as $store)
                            <option value="{{ $store['id'] }}"
                                {{ in_array((string) $store['id'], $selectedStores, true) ? 'selected' : '' }}>
                                {{ $store['store_name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                        <label class="form-label small fw-semibold text-uppercase mb-1">Start Date</label>
                        <input type="date" name="start_date" id="filter_start_date" class="form-control "
                            value="{{ request('start_date') ?? date('Y-m-d') }}">
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                        <label class="form-label small fw-semibold text-uppercase mb-1">End Date</label>
                        <input type="date" name="end_date" id="filter_end_date" class="form-control "
                            value="{{ request('end_date') }}" min="{{ request('start_date') ?? date('Y-m-d') }}">
                    </div>
                    <div class="col-12 col-lg-8 col-xl-4 d-flex align-items-end gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary  d-inline-flex align-items-center gap-1 px-3">
                            <i class="material-symbols-rounded" style="font-size: 1rem;">filter_list</i>
                            <span>Filter</span>
                        </button>
                        <a href="{{ route('admin.mess.selling-voucher-date-range.index') }}"
                            class="btn btn-outline-secondary  d-inline-flex align-items-center gap-1 px-3">
                            <i class="material-symbols-rounded" style="font-size: 1rem;">refresh</i>
                            <span>Clear</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 selling-voucher-card">
        <div class="card-body">
            <div class="d-flex justify-content-end mb-2">
                <div class="input-group input-group-sm selling-voucher-search-wrapper" style="max-width: 260px;">
                    <span class="input-group-text">
                        <i class="material-symbols-rounded" style="font-size: 1rem;">search</i>
                    </span>
                    <input type="text" id="sellingVoucherCustomSearch" class="form-control"
                        placeholder="Search selling vouchers...">
                </div>
            </div>
            <div class="table-responsive selling-voucher-table-wrap">
                <table class="table table-sm align-middle mb-0 w-100" id="sellingVoucherDateRangeTable">
                    <thead class="position-sticky top-0" style="z-index: 1;">
                        <tr>
                            <th scope="col" class="ps-3 text-center">#</th>
                            <th scope="col">Item Name</th>
                            <th scope="col" class="text-end">Qty</th>
                            <th scope="col" class="text-end">Return Qty</th>
                            <th scope="col">Store</th>
                            <th scope="col">Client Type</th>
                            <th scope="col">Client Name</th>
                            <th scope="col">Name</th>
                            <th scope="col">Payment</th>
                            <th scope="col">Request Date</th>
                            <th scope="col" class="text-center">Status</th>
                            <th scope="col">Return</th>
                            <th scope="col" class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0 small">
                        @php $serial = 1; @endphp
                        @forelse($reports as $report)
                        @forelse($report->items as $item)
                        <tr>
                            <td class="ps-3 text-center text-body-secondary">{{ $serial++ }}</td>
                            <td class="cell-item-name fw-semibold text-wrap text-break">
                                {{ $item->item_name ?: ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? '—') }}
                            </td>
                            <td class="text-end font-monospace">{{ $item->quantity }}</td>
                            <td class="text-end font-monospace">{{ $item->return_quantity ?? 0 }}</td>
                            <td class="text-wrap text-break" title="{{ $report->resolved_store_name }}">
                                {{ $report->resolved_store_name }}</td>
                            <td>{{ $report->clientTypeCategory ? ucfirst($report->clientTypeCategory->client_type ?? '') : ($report->client_type_slug ? ucfirst($report->client_type_slug) : '—') }}
                            </td>
                            <td class="text-wrap text-break">{{ $report->display_client_name }}</td>
                            <td class="text-wrap text-break">{{ $report->client_name ?? '—' }}</td>
                            <td>
                                <span class="badge text-bg-light border border-light-subtle fw-semibold">
                                    {{ $report->payment_type == 1 ? 'Credit' : ($report->payment_type == 0 ? 'Cash' : ($report->payment_type == 2 ? 'UPI' : '—')) }}
                                </span>
                            </td>
                            <td class="text-body-secondary">
                                {{ $report->date_from ? $report->date_from->format('d/m/Y') : '—' }}</td>
                            <td class="text-center">
                                @if($report->status == 0)<span class="badge rounded-1 text-bg-warning">Pending</span>
                                @elseif($report->status == 2)<span
                                    class="badge rounded-1 text-bg-success">Approved</span>
                                @elseif($report->status == 4)<span
                                    class="badge rounded-1 text-bg-primary">Completed</span>
                                @else<span class="badge rounded-1 text-bg-secondary">Final</span>@endif
                            </td>
                            <td>
                                <div class="d-flex flex-wrap align-items-center gap-1">
                                    @if(($item->return_quantity ?? 0) > 0)
                                    <span class="badge rounded-1 text-bg-info">Returned</span>
                                    @endif
                                    @if($loop->first)
                                    <button type="button"
                                        class="btn btn-sm btn-outline-secondary btn-return-report d-inline-flex align-items-center gap-1 rounded-2 px-2"
                                        data-report-id="{{ $report->id }}" title="Return">
                                        <i class="material-symbols-rounded"
                                            style="font-size: 1rem;">assignment_return</i>
                                        <span>Return</span>
                                    </button>
                                    @endif
                                </div>
                            </td>
                            <td class="text-end pe-3">
                                @if($loop->first)
                                <div class="d-inline-flex flex-wrap align-items-center justify-content-end gap-1">
                                    <button type="button"
                                        class="btn btn-sm btn-outline-primary btn-view-report voucher-icon-btn rounded-2"
                                        data-report-id="{{ $report->id }}" title="View">
                                        <i class="material-symbols-rounded">visibility</i>
                                    </button>
                                    <button type="button"
                                        class="btn btn-sm btn-outline-warning btn-edit-report voucher-icon-btn rounded-2"
                                        data-report-id="{{ $report->id }}"
                                        title="{{ $report->status == \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_APPROVED ? 'Edit is disabled for approved voucher' : 'Edit' }}"
                                        @if($report->status ==
                                        \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_APPROVED) disabled
                                        @endif>
                                        <i class="material-symbols-rounded">edit</i>
                                    </button>
                                    @if($canDeleteSellingVoucherDateRange)
                                    <form
                                        action="{{ route('admin.mess.selling-voucher-date-range.destroy', $report->id) }}"
                                        method="POST" class="d-inline"
                                        onsubmit="return confirm('Are you sure you want to delete this report?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="btn btn-sm btn-outline-danger voucher-icon-btn rounded-2"
                                            title="Delete"><i class="material-symbols-rounded">delete</i></button>
                                    </form>
                                    @endif
                                </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td class="ps-3 text-center text-body-secondary">{{ $serial++ }}</td>
                            <td class="cell-item-name text-body-secondary text-wrap text-break">—</td>
                            <td class="text-end font-monospace text-body-secondary">—</td>
                            <td class="text-end font-monospace text-body-secondary">—</td>
                            <td class="text-wrap text-break" title="{{ $report->resolved_store_name }}">
                                {{ $report->resolved_store_name }}</td>
                            <td>{{ $report->clientTypeCategory ? ucfirst($report->clientTypeCategory->client_type ?? '') : ($report->client_type_slug ? ucfirst($report->client_type_slug) : '—') }}
                            </td>
                            <td class="text-wrap text-break">{{ $report->display_client_name }}</td>
                            <td class="text-wrap text-break">{{ $report->client_name ?? '—' }}</td>
                            <td>
                                <span class="badge text-bg-light border border-light-subtle fw-semibold">
                                    {{ $report->payment_type == 1 ? 'Credit' : ($report->payment_type == 0 ? 'Cash' : ($report->payment_type == 2 ? 'UPI' : '—')) }}
                                </span>
                            </td>
                            <td class="text-body-secondary">
                                {{ $report->date_from ? $report->date_from->format('d/m/Y') : '—' }}</td>
                            <td class="text-center">
                                @if($report->status == 0)<span class="badge rounded-1 text-bg-warning">Pending</span>
                                @elseif($report->status == 2)<span
                                    class="badge rounded-1 text-bg-success">Approved</span>
                                @else<span class="badge rounded-1 text-bg-secondary">Final</span>@endif
                            </td>
                            <td>
                                <button type="button"
                                    class="btn btn-sm btn-outline-secondary btn-return-report d-inline-flex align-items-center gap-1 rounded-2 px-2"
                                    data-report-id="{{ $report->id }}" title="Return">
                                    <i class="material-symbols-rounded" style="font-size: 1rem;">assignment_return</i>
                                    <span>Return</span>
                                </button>
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-inline-flex flex-wrap align-items-center justify-content-end gap-1">
                                    <button type="button"
                                        class="btn btn-sm btn-outline-primary btn-view-report voucher-icon-btn rounded-3"
                                        data-report-id="{{ $report->id }}" title="View"><i
                                            class="material-symbols-rounded">visibility</i></button>
                                    <button type="button"
                                        class="btn btn-sm btn-outline-warning btn-edit-report voucher-icon-btn rounded-3"
                                        data-report-id="{{ $report->id }}"
                                        title="{{ $report->status == \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_APPROVED ? 'Edit is disabled for approved voucher' : 'Edit' }}"
                                        @if($report->status ==
                                        \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_APPROVED) disabled
                                        @endif><i class="material-symbols-rounded">edit</i></button>
                                    @if($canDeleteSellingVoucherDateRange)
                                    <form
                                        action="{{ route('admin.mess.selling-voucher-date-range.destroy', $report->id) }}"
                                        method="POST" class="d-inline"
                                        onsubmit="return confirm('Are you sure you want to delete this report?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="btn btn-sm btn-outline-danger voucher-icon-btn rounded-3"
                                            title="Delete"><i class="material-symbols-rounded">delete</i></button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforelse
                        @empty
                        <tr>
                            <td class="text-center py-5 text-body-secondary border-0" colspan="13">
                                <div class="d-flex flex-column align-items-center gap-2 py-3">
                                    <span
                                        class="d-inline-flex align-items-center justify-content-center rounded-circle bg-body-tertiary text-secondary p-3">
                                        <i class="material-symbols-rounded" style="font-size: 2rem;">inbox</i>
                                    </span>
                                    <span class="fs-6 fw-medium text-body">No reports found</span>
                                    <span class="small text-body-secondary">Try adjusting filters or add a new
                                        voucher.</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('components.mess-master-datatables', [
    'tableId' => 'sellingVoucherDateRangeTable',
    'searchPlaceholder' => 'Search selling vouchers...',
    'ordering' => false,
    'actionColumnIndex' => 12,
    'infoLabel' => 'selling vouchers',
    'searchDelay' => 0,
    'scrollX' => true
    ])
    @include('mess.partials.modal-dropdown-stability')

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable) return;
        var $ = window.jQuery;
        var $table = $('#sellingVoucherDateRangeTable');
        if (!$table.length) return;

        function bindSellingVoucherSearch(dtApi) {
            var $input = $('#sellingVoucherCustomSearch');
            if (!$input.length) return;
            $input.on('keyup change', function() {
                var val = this.value;
                dtApi.search(val).draw();
            });
        }

        if ($.fn.DataTable.isDataTable($table)) {
            bindSellingVoucherSearch($table.DataTable());
        } else {
            $table.on('init.dt', function(e, settings) {
                var api = new $.fn.dataTable.Api(settings);
                bindSellingVoucherSearch(api);
            });
        }
    });
    </script>
    @endpush
</div>

{{-- Choices.js CSS --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
{{-- Choices.js JS --}}
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<style>
.ts-dropdown {
    z-index: 2000;
}

.selling-voucher-filter {
    position: relative;
    overflow: visible;
}

.selling-voucher-filter.dropdown-open {
    z-index: 1065;
}

.selling-voucher-filter .card-body,
.selling-voucher-filter .row,
.selling-voucher-filter .col-12,
.selling-voucher-filter .ts-wrapper {
    overflow: visible;
}

.ts-wrapper.choices {
    margin-bottom: 0;
}

.ts-wrapper.choices .choices__inner {
    min-height: calc(1.5em + 0.75rem + 2px);
    padding: 0.375rem 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    background-color: #fff;
}

.ts-wrapper.choices.is-open .choices__inner,
.ts-wrapper.choices.is-focused .choices__inner {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.ts-wrapper.choices .choices__list--single {
    padding: 0;
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
    margin-bottom: 0 !important;
}

.ts-wrapper.choices .choices__list--dropdown .choices__input--cloned {
    display: block !important;
    position: relative !important;
    opacity: 1 !important;
    flex-shrink: 0;
    min-height: 34px;
    width: 100% !important;
}

.selling-voucher-filter .ts-dropdown,
.selling-voucher-filter .ts-wrapper.choices .choices__list--dropdown,
.selling-voucher-filter .choices__list--dropdown.is-active {
    z-index: 1066 !important;
}

.selling-voucher-filter .ts-wrapper.choices[data-type*="select-multiple"] .choices__inner {
    min-height: calc(1.5em + 0.75rem + 2px);
    padding: 0.25rem 0.5rem;
}

.selling-voucher-filter .choices__list--multiple .choices__item {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%) !important;
    border: none !important;
    border-radius: 0.375rem !important;
    color: #fff !important;
    font-size: 0.8rem !important;
    margin-bottom: 0;
}

.voucher-icon-btn {
    width: 2rem;
    height: 2rem;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
}

.voucher-section-card {
    border: 1px solid var(--bs-border-color-translucent);
    border-radius: 0.75rem;
}

.voucher-section-card .card-header {
    background: var(--bs-tertiary-bg);
    border-bottom: 1px solid var(--bs-border-color-translucent);
}
</style>

{{-- Add Report Modal --}}
<style>
/* Hide number input up/down arrows for qty/rate fields in add/edit modals */
#addReportModal input.dr-qty,
#addReportModal input.dr-rate,
#editSellingVoucherModal input.edit-dr-qty,
#editSellingVoucherModal input.edit-dr-rate {
    -moz-appearance: textfield;
}

#addReportModal input.dr-qty::-webkit-outer-spin-button,
#addReportModal input.dr-qty::-webkit-inner-spin-button,
#addReportModal input.dr-rate::-webkit-outer-spin-button,
#addReportModal input.dr-rate::-webkit-inner-spin-button,
#editSellingVoucherModal input.edit-dr-qty::-webkit-outer-spin-button,
#editSellingVoucherModal input.edit-dr-qty::-webkit-inner-spin-button,
#editSellingVoucherModal input.edit-dr-rate::-webkit-outer-spin-button,
#editSellingVoucherModal input.edit-dr-rate::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.voucher-table thead th {
    font-size: .76rem;
    font-weight: 700;
    letter-spacing: .02em;
    text-transform: uppercase;
    color: #495057;
}

.voucher-table tbody td {
    white-space: nowrap;
}

.voucher-table tbody td.cell-item-name {
    white-space: normal;
    max-width: 18rem;
    vertical-align: middle;
}

.voucher-icon-btn {
    width: 2rem;
    height: 2rem;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.voucher-label {
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .02em;
    color: #6c757d;
    margin-bottom: .35rem;
}

.voucher-section-card {
    border: 0;
    box-shadow: var(--bs-box-shadow-sm);
}

.voucher-section-card .card-header {
    background: var(--bs-tertiary-bg);
    border-bottom: 1px solid var(--bs-border-color-translucent);
}

/* Responsive modal sizing across viewport widths */
#addReportModal .modal-dialog,
#viewReportModal .modal-dialog,
#editReportModal .modal-dialog,
#returnItemModal .modal-dialog {
    width: calc(100vw - 1rem);
    max-width: min(var(--bs-modal-width), calc(100vw - 1rem));
}

@media (min-width: 576px) {

    #addReportModal .modal-dialog,
    #viewReportModal .modal-dialog,
    #editReportModal .modal-dialog,
    #returnItemModal .modal-dialog {
        width: calc(100vw - 2rem);
        max-width: min(var(--bs-modal-width), calc(100vw - 2rem));
    }
}

#addReportModal .modal-dialog {
    max-height: calc(100dvh - 2rem);
    margin: 1rem auto;
}

#addReportModal .modal-content,
#viewReportModal .modal-content,
#editReportModal .modal-content,
#returnItemModal .modal-content {
    max-height: calc(100dvh - 2rem);
    display: flex;
    flex-direction: column;
    border: 0;
    box-shadow: var(--bs-box-shadow-lg);
}

#addReportModal .modal-header,
#viewReportModal .modal-header,
#editReportModal .modal-header,
#returnItemModal .modal-header {
    position: sticky;
    top: 0;
    z-index: 2;
    background: var(--bs-tertiary-bg) !important;
}

#addReportModal .modal-footer,
#viewReportModal .modal-footer,
#editReportModal .modal-footer,
#returnItemModal .modal-footer {
    position: sticky;
    bottom: 0;
    z-index: 2;
    background: #fff;
}

/* Item Name (Choices): footer/stacking + overflow — same pattern as kitchen selling voucher */
#addReportModal .modal-body,
#editReportModal .modal-body {
    position: relative;
    z-index: 2;
}

#addReportModal .modal-footer,
#editReportModal .modal-footer {
    z-index: 1;
}

/* Keep modal scroll stable; don't toggle overflow on dropdown open */
#addReportModal .modal-body,
#editReportModal .modal-body {
    /* Important: don't let the modal-body become wider than the viewport.
           The items table handles its own horizontal scroll. */
    overflow-x: hidden;
}

#addReportModal .dr-item-details-table-wrap,
#editReportModal .dr-item-details-table-wrap {
    /* Show all item columns in one view */
    overflow-x: auto;
    overflow-y: hidden;
    width: 100%;
}

#addReportModal .dr-item-details-table-wrap .table,
#editReportModal .dr-item-details-table-wrap .table {
    min-width: 0;
    width: 100%;
    table-layout: fixed;
    margin-bottom: 0;
}

#addReportItemsTable th,
#addReportItemsTable td,
#editReportItemsTable th,
#editReportItemsTable td {
    white-space: normal;
    padding: .35rem .4rem;
    word-break: break-word;
}

/* Column widths tuned so all td are visible in one go */
#addReportItemsTable th:nth-child(1),
#editReportItemsTable th:nth-child(1) {
    width: 28%;
}

#addReportItemsTable th:nth-child(2),
#editReportItemsTable th:nth-child(2) {
    width: 8%;
}

#addReportItemsTable th:nth-child(3),
#editReportItemsTable th:nth-child(3) {
    width: 11%;
}

#addReportItemsTable th:nth-child(4),
#editReportItemsTable th:nth-child(4) {
    width: 11%;
}

#addReportItemsTable th:nth-child(5),
#editReportItemsTable th:nth-child(5) {
    width: 10%;
}

#addReportItemsTable th:nth-child(6),
#editReportItemsTable th:nth-child(6) {
    width: 14%;
}

#addReportItemsTable th:nth-child(7),
#editReportItemsTable th:nth-child(7) {
    width: 9%;
}

#addReportItemsTable th:nth-child(8),
#editReportItemsTable th:nth-child(8) {
    width: 11%;
}

#addReportItemsTable th:nth-child(9),
#editReportItemsTable th:nth-child(9) {
    width: 5%;
}

@media (max-width: 991.98px) {

    #addReportModal .dr-item-details-table-wrap,
    #editReportModal .dr-item-details-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    #addReportModal .dr-item-details-table-wrap .table,
    #editReportModal .dr-item-details-table-wrap .table {
        min-width: 980px;
        table-layout: auto;
    }

    #addReportItemsTable th,
    #addReportItemsTable td,
    #editReportItemsTable th,
    #editReportItemsTable td {
        white-space: nowrap;
    }
}

/* Item dropdowns use fixed-position panel; no need to relax container overflow */
#addReportModal .card:has(#addModalItemsBody) .card-body,
#editReportModal .card:has(#editModalItemsBody) .card-body {
    position: relative;
    z-index: 2;
}

#addReportModal .card:has(#addModalItemsBody) .card-footer,
#editReportModal .card:has(#editModalItemsBody) .card-footer {
    position: relative;
    z-index: 1;
}

#addReportModal .choices,
#editReportModal .choices {
    --choices-z-index: 6100;
}

#addModalItemsBody tr:has(.choices.is-open),
#editModalItemsBody tr:has(.choices.is-open) {
    position: relative;
    z-index: 50;
}

#addReportModal .ts-dropdown,
#addReportModal .ts-wrapper.choices .choices__list--dropdown,
#addReportModal .choices__list--dropdown.is-active,
#editReportModal .ts-dropdown,
#editReportModal .ts-wrapper.choices .choices__list--dropdown,
#editReportModal .choices__list--dropdown.is-active {
    z-index: 6100 !important;
}

/* Limit dropdown panel size for large lists (e.g. Academy Staff) */
#addReportModal .ts-wrapper.choices .choices__list--dropdown,
#addReportModal .choices__list--dropdown.is-active,
#editReportModal .ts-wrapper.choices .choices__list--dropdown,
#editReportModal .choices__list--dropdown.is-active {
    width: 100% !important;
    min-width: 100% !important;
    max-height: min(320px, 45vh) !important;
    overflow: hidden !important;
}

#addReportModal .ts-wrapper.choices .choices__list--dropdown .choices__list,
#addReportModal .choices__list--dropdown.is-active .choices__list,
#editReportModal .ts-wrapper.choices .choices__list--dropdown .choices__list,
#editReportModal .choices__list--dropdown.is-active .choices__list {
    max-height: min(280px, 40vh) !important;
    overflow-y: auto !important;
    -webkit-overflow-scrolling: touch;
}

/* Ensure the Choices container itself fills the column */
#addReportModal .ts-wrapper.choices,
#editReportModal .ts-wrapper.choices {
    width: 100% !important;
}

/* Keep dropdown width aligned to its input (avoid full-modal width) */
#addReportModal .ts-wrapper.choices[data-type*="select-one"] .choices__list--dropdown,
#editReportModal .ts-wrapper.choices[data-type*="select-one"] .choices__list--dropdown {
    width: 100% !important;
    min-width: 100% !important;
    max-width: 100% !important;
    left: 0 !important;
    right: auto !important;
    box-sizing: border-box;
}

#addModalItemsBody .choices__list--dropdown.dr-item-choices-dropdown-fixed,
#editModalItemsBody .choices__list--dropdown.dr-item-choices-dropdown-fixed {
    box-sizing: border-box;
}

/* (Reserved) */
#addModalItemsBody .choices__list--dropdown.dr-item-choices-dropdown-fixed .choices__list,
#editModalItemsBody .choices__list--dropdown.dr-item-choices-dropdown-fixed .choices__list {
    max-height: min(280px, 42vh) !important;
}

#addReportModal .modal-body,
#viewReportModal .modal-body,
#editReportModal .modal-body,
#returnItemModal .modal-body {
    overflow-y: auto;
    max-height: calc(100dvh - 10rem);
}

#addReportModal .card,
#viewReportModal .card,
#editReportModal .card,
#returnItemModal .card {
    border: 0;
    box-shadow: var(--bs-box-shadow-sm);
}

.selling-voucher-card .selling-voucher-table-wrap {
    width: 100%;
    max-width: 100%;
    overflow: visible;
}

#sellingVoucherDateRangeTable {
    min-width: 1200px;
}

.selling-voucher-card #sellingVoucherDateRangeTable_wrapper .dataTables_scrollBody {
    overflow-x: auto !important;
}

/* Table: show full cell values (no forced single-line truncation) */
#sellingVoucherDateRangeTable th,
#sellingVoucherDateRangeTable td {
    white-space: normal !important;
    vertical-align: middle;
}

/* Keep DataTable search box pinned and not floating while scrolling */
.selling-voucher-card .dataTables_wrapper {
    position: relative;
}

.selling-voucher-card .dataTables_wrapper .dataTables_filter {
    display: none;
    /* hide default DataTables search for this table */
}

.selling-voucher-search-wrapper {
    max-width: 260px;
}

/* Keep DataTables controls in normal flow (no floating) */
.selling-voucher-card .dataTables_wrapper .dataTables_length,
.selling-voucher-card .dataTables_wrapper .dataTables_info,
.selling-voucher-card .dataTables_wrapper .dataTables_paginate {
    position: static;
    background-color: transparent;
}
</style>
<div class="modal fade" id="addReportModal" tabindex="-1" aria-labelledby="addReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-lg-down modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('admin.mess.selling-voucher-date-range.store') }}" method="POST" id="addReportForm"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-semibold" id="addReportModalLabel">ADD Selling Voucher with Date Range
                    </h5>
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
                        <button type="button" class="btn-close " data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    {{-- Voucher Details (exactly same as Add Selling Voucher) --}}
                    <div class="card mb-4 voucher-section-card shadow-sm">
                        <div class="card-header py-3">
                            <h6 class="mb-0 fw-semibold text-primary">Voucher Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label voucher-label">Client Type <span
                                            class="text-danger">*</span></label>
                                    <div class="d-flex flex-wrap gap-3 pt-1">
                                        @foreach($clientTypes as $slug => $label)
                                        <div class="form-check">
                                            <input class="form-check-input dr-client-type-radio" type="radio"
                                                name="client_type_slug" id="dr_ct_{{ $slug }}" value="{{ $slug }}"
                                                {{ old('client_type_slug') === $slug ? 'checked' : '' }} required>
                                            <label class="form-check-label" for="dr_ct_{{ $slug }}">{{ $label }}</label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label voucher-label">Payment Type <span
                                            class="text-danger">*</span></label>
                                    <select name="payment_type" class="form-select " required>
                                        <option value="1" {{ old('payment_type', '1') == '1' ? 'selected' : '' }}>Credit
                                        </option>
                                        <option value="0" {{ old('payment_type') == '0' ? 'selected' : '' }}>Cash
                                        </option>
                                        <option value="2" {{ old('payment_type') == '2' ? 'selected' : '' }}>UPI
                                        </option>
                                    </select>
                                    <small class="form-text text-muted" id="drPaymentTypeHint">Cash / UPI /
                                        Credit</small>
                                </div>
                                <div class="col-md-4" id="drClientNameWrap" style="display:none;">
                                    <label class="form-label voucher-label">Client Name <span
                                            class="text-danger">*</span></label>
                                    <select name="client_type_pk" class="form-select " id="drClientNameSelect">
                                        <option value="">Select Client Name</option>
                                        @foreach($clientNamesByType as $type => $list)
                                        @foreach($list as $c)
                                        <option value="{{ $c->id }}" data-type="{{ $c->client_type }}"
                                            data-client-name="{{ strtolower($c->client_name ?? '') }}">
                                            {{ $c->client_name }}</option>
                                        @endforeach
                                        @endforeach
                                    </select>
                                    <select id="drOtCourseSelect" class="form-select " style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                        <option value="{{ $course->pk }}"
                                            data-course-name="{{ e($course->course_name) }}">
                                            {{ e($course->course_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="drCourseSelect" class="form-select " style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                        <option value="{{ $course->pk }}"
                                            data-course-name="{{ e($course->course_name) }}">
                                            {{ e($course->course_name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4" id="drNameFieldWrap" style="display:none;">
                                    <label class="form-label voucher-label">Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="client_name" id="drClientNameInput" class="form-control "
                                        value="{{ old('client_name') }}" placeholder="Client / section / role name"
                                        required>
                                    <datalist id="drCourseBuyerNames"></datalist>
                                    <datalist id="drGenericBuyerNames"></datalist>
                                    <select id="drFacultySelect" class="form-select " style="display:none;">
                                        <option value="">Select Faculty</option>
                                        @foreach($faculties ?? [] as $f)
                                        <option value="{{ e($f->full_name) }}">
                                            {{ e($f->full_name_with_code ?? $f->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="drAcademyStaffSelect" class="form-select " style="display:none;">
                                        <option value="">Select Academy Staff</option>
                                        @foreach($employees ?? [] as $e)
                                        <option value="{{ e($e->full_name_with_department ?? $e->full_name) }}">
                                            {{ e($e->full_name_with_department ?? $e->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="drMessStaffSelect" class="form-select " style="display:none;">
                                        <option value="">Select Mess Staff</option>
                                        @foreach($messStaff ?? [] as $e)
                                        <option value="{{ e($e->full_name_with_department ?? $e->full_name) }}">
                                            {{ e($e->full_name_with_department ?? $e->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="drOtStudentSelect" class="form-select " style="display:none;">
                                        <option value="">Select Student</option>
                                    </select>
                                    <select id="drCourseNameSelect" class="form-select " style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                        <option value="{{ $course->pk }}">{{ e($course->course_name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label voucher-label">Transfer From Store <span
                                            class="text-danger">*</span></label>
                                    <select name="inve_store_master_pk" class="form-select " required>
                                        <option value="">Select Store</option>
                                        @foreach($stores as $store)
                                        <option value="{{ $store['id'] }}"
                                            {{ old('inve_store_master_pk') == $store['id'] ? 'selected' : '' }}>
                                            {{ $store['store_name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label voucher-label">Remarks / Reference Number / Order
                                        By</label>
                                    <input type="text" name="remarks" class="form-control " value="{{ old('remarks') }}"
                                        placeholder="Remarks / Reference Number / Order By (optional)">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Bill upload removed as per requirement --}}

                    {{-- Item Details (exactly same as Add Selling Voucher) --}}
                    <div class="card mb-4 voucher-section-card">
                        <div class="card-header d-flex justify-content-between align-items-center py-3">
                            <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                            <button type="button"
                                class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
                                id="addModalAddItemRow">
                                <i class="material-symbols-rounded" style="font-size: 1rem;">add</i>
                                <span>Add Item</span>
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="dr-item-details-table-wrap">
                                <table class="table table-bordered table-sm table-hover align-middle mb-0"
                                    id="addReportItemsTable">
                                    <thead class="voucher-brand-head">
                                        <tr>
                                            <th>Item Name <span class="text-white">*</span></th>
                                            <th>Unit</th>
                                            <th>Available Qty</th>
                                            <th>Issue Qty <span class="text-white">*</span></th>
                                            <th>Left Qty</th>
                                            <th>Issue Date</th>
                                            <th>Rate <span class="text-white">*</span></th>
                                            <th>Total Amount</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="addModalItemsBody">
                                        <tr class="dr-item-row">
                                            <td>
                                                <select name="items[0][item_subcategory_id]"
                                                    class="form-select  dr-item-select" required>
                                                    <option value="">Select Item</option>
                                                    @foreach($itemSubcategories as $s)
                                                    <option value="{{ $s['id'] }}"
                                                        data-unit="{{ e($s['unit_measurement'] ?? '') }}"
                                                        data-rate="{{ e($s['standard_cost'] ?? 0) }}">
                                                        {{ e($s['item_name'] ?? '—') }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="text" name="items[0][unit]" class="form-control  dr-unit"
                                                    readonly placeholder="—"></td>
                                            <td><input type="text" name="items[0][available_quantity]"
                                                    class="form-control  dr-avail bg-light" readonly></td>
                                            <td>
                                                <input type="text" name="items[0][quantity]"
                                                    class="form-control  dr-qty" required>
                                                <div class="invalid-feedback">Issue Qty cannot exceed Available Qty.
                                                </div>
                                            </td>
                                            <td><input type="text" class="form-control  dr-left bg-light" readonly></td>
                                            <td><input type="date" name="items[0][issue_date]"
                                                    class="form-control  dr-issue-date" value="{{ date('Y-m-d') }}">
                                            </td>
                                            <td><input type="number" name="items[0][rate]" class="form-control  dr-rate"
                                                    step="0.01" min="0" required></td>
                                            <td><input type="text" class="form-control  dr-total bg-light" readonly>
                                            </td>
                                            <td><button type="button"
                                                    class="btn btn-sm btn-outline-danger dr-remove-row voucher-icon-btn"
                                                    disabled title="Remove">×</button></td>
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
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                        <i class="material-symbols-rounded" style="font-size: 1rem;">save</i>
                        <span>Save Selling Voucher</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- View Selling Voucher with Date Range Modal (same columns as Selling Voucher view modal + Issue Date) --}}
<style>
#viewReportModal .modal-dialog {
    max-height: calc(100dvh - 2rem);
    margin: 1rem auto;
}

#viewReportModal .modal-content {
    max-height: calc(100dvh - 2rem);
    display: flex;
    flex-direction: column;
    background: #fff;
    color: #212529;
}

#viewReportModal .modal-header {
    background: #f8f9fa !important;
    color: #212529 !important;
}

#viewReportModal .modal-header * {
    color: #212529 !important;
}

#viewReportModal .modal-title {
    color: #212529 !important;
}

#viewReportModal .modal-body {
    overflow-y: auto;
    max-height: calc(100dvh - 10rem);
    background: #fff;
    color: #212529 !important;
}

#viewReportModal .modal-body *,
#viewReportModal .modal-body p,
#viewReportModal .modal-body span {
    color: inherit;
}

#viewReportModal .card {
    background: #fff;
    color: #212529;
}

#viewReportModal .card-header {
    background: #fff !important;
    color: #212529 !important;
    border-color: #dee2e6;
}

#viewReportModal .card-header h6 {
    color: #0d6efd !important;
}

#viewReportModal .card-body {
    background: #fff !important;
    color: #212529 !important;
}

#viewReportModal .card-body table th {
    color: #495057 !important;
    font-weight: 600;
}

#viewReportModal .card-body table td {
    color: #212529 !important;
}

#viewReportModal .card-body .table-borderless th {
    background: transparent !important;
}

#viewReportModal .card-body .table-borderless td {
    background: transparent !important;
}

#viewReportModal #viewReportItemsCard .table thead th {
    color: #fff !important;
    background: #af2910 !important;
    border-color: #af2910;
}

#viewReportModal #viewReportItemsCard .table tbody td {
    color: #212529 !important;
    background: #fff !important;
}

#viewReportModal #viewReportGrandTotal {
    color: #212529 !important;
}

#viewReportModal .text-muted {
    color: #495057 !important;
}

#viewReportModal .card-footer {
    background: #f8f9fa !important;
    color: #212529 !important;
}

#viewReportModal .card-footer strong {
    color: #212529 !important;
}

#viewReportModal .badge {
    font-weight: 600;
}

#viewReportModal .modal-footer {
    background: #fff;
    border-color: #dee2e6;
}
</style>
<div class="modal fade" id="viewReportModal" tabindex="-1" aria-labelledby="viewReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-lg-down modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold" id="viewReportModalLabel">View Selling Voucher with Date Range</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Voucher Details (exactly same as Selling Voucher view modal) --}}
                <div class="card mb-4 voucher-section-card">
                    <div class="card-header py-3">
                        <h6 class="mb-0 fw-semibold text-primary">Voucher Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <table class="table table-borderless table-sm mb-0">
                                    <tr>
                                        <th width="40%" class="text-secondary fw-semibold">Request Date:</th>
                                        <td id="viewRequestDate">—</td>
                                    </tr>
                                    <tr>
                                        <th class="text-secondary fw-semibold">Transfer From Store:</th>
                                        <td id="viewStoreName">—</td>
                                    </tr>
                                    <tr>
                                        <th class="text-secondary fw-semibold">Reference Number:</th>
                                        <td id="viewReferenceNumber">—</td>
                                    </tr>
                                    <tr>
                                        <th class="text-secondary fw-semibold">Order By:</th>
                                        <td id="viewOrderBy">—</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-4">
                                <table class="table table-borderless table-sm mb-0">
                                    <tr>
                                        <th width="40%" class="text-secondary fw-semibold">Client Type:</th>
                                        <td id="viewClientType">—</td>
                                    </tr>
                                    <tr>
                                        <th class="text-secondary fw-semibold">Client Name:</th>
                                        <td id="viewClientName">—</td>
                                    </tr>
                                    <tr>
                                        <th class="text-secondary fw-semibold">Payment Type:</th>
                                        <td id="viewPaymentType">—</td>
                                    </tr>
                                    <tr>
                                        <th class="text-secondary fw-semibold">Status:</th>
                                        <td id="viewStatus">—</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <p class="mb-0 mt-3" id="viewRemarksWrap" style="display:none;"><strong>Remarks:</strong> <span
                                id="viewRemarks"></span></p>
                    </div>
                </div>
                {{-- Item Details (same as Selling Voucher view modal + one extra column Issue Date) --}}
                <div class="card mb-4 voucher-section-card" id="viewReportItemsCard">
                    <div class="card-header py-3">
                        <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="voucher-brand-head">
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Unit</th>
                                        <th>Issue Qty</th>
                                        <th>Return Qty</th>
                                        <th>Rate</th>
                                        <th>Total</th>
                                        <th>Issue Date</th>
                                    </tr>
                                </thead>
                                <tbody id="viewReportItemsBody"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-light d-flex justify-content-end">
                        <strong>Grand Total: ₹<span id="viewReportGrandTotal">0.00</span></strong>
                    </div>
                </div>
                <div class="small text-secondary">
                    Created: <span id="viewCreatedAt" class="text-body">—</span>
                    <span class="ms-3" id="viewUpdatedAtWrap" style="display:none;">Last Updated: <span
                            id="viewUpdatedAt" class="text-body"></span></span>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button"
                    class="btn btn-outline-primary btn-print-view-modal d-inline-flex align-items-center gap-1"
                    data-print-target="#viewReportModal" title="Print">
                    <i class="ti ti-printer"></i> Print
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Return Item Modal (Transfer To) --}}
<div class="modal fade" id="returnItemModal" tabindex="-1" aria-labelledby="returnItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered modal-fullscreen-lg-down">
        <div class="modal-content">
            <form id="returnItemForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-semibold" id="returnItemModalLabel">Transfer To</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label voucher-label">Transfer From Store</label>
                        <p class="mb-0 form-control-plaintext" id="returnTransferFromStore">—</p>
                    </div>
                    <div class="card voucher-section-card">
                        <div class="card-header py-3">
                            <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle mb-0">
                                    <thead class="voucher-brand-head">
                                        <tr>
                                            <th>Item Name</th>
                                            <th>Issued Quantity</th>
                                            <th>Item Unit</th>
                                            <th>Item Issue Date</th>
                                            <th>Return Quantity</th>
                                            <th>Return Date</th>
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
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                        <i class="material-symbols-rounded" style="font-size: 1rem;">sync</i>
                        <span>Update</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Report Modal --}}
<style>
#editReportModal .modal-dialog {
    max-height: calc(100dvh - 2rem);
    margin: 1rem auto;
}

#editReportModal .modal-content {
    max-height: calc(100dvh - 2rem);
    display: flex;
    flex-direction: column;
}

#editReportModal .modal-body {
    overflow-y: auto;
    max-height: calc(100dvh - 10rem);
}
</style>
<div class="modal fade" id="editReportModal" tabindex="-1" aria-labelledby="editReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-lg-down modal-dialog-centered">
        <div class="modal-content">
            <form id="editReportForm" method="POST" action="" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-semibold" id="editReportModalLabel">Edit Selling Voucher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Voucher Details (exactly same as Edit Selling Voucher) --}}
                    <div class="card mb-4 voucher-section-card shadow-sm">
                        <div class="card-header py-3">
                            <h6 class="mb-0 fw-semibold text-primary">Voucher Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label voucher-label">Client Type <span
                                            class="text-danger">*</span></label>
                                    <div class="d-flex flex-wrap gap-3 pt-1">
                                        @foreach($clientTypes as $slug => $label)
                                        <div class="form-check">
                                            <input class="form-check-input edit-dr-client-type-radio" type="radio"
                                                name="client_type_slug" id="edit_dr_ct_{{ $slug }}" value="{{ $slug }}"
                                                required>
                                            <label class="form-check-label"
                                                for="edit_dr_ct_{{ $slug }}">{{ $label }}</label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label voucher-label">Payment Type <span
                                            class="text-danger">*</span></label>
                                    <select name="payment_type" class="form-select  edit-payment-type" required>
                                        <option value="1">Credit</option>
                                        <option value="0">Cash</option>
                                        <option value="2">UPI</option>
                                    </select>
                                </div>
                                <div class="col-md-4" id="editDrClientNameWrap" style="display:none;">
                                    <label class="form-label voucher-label">Client Name <span
                                            class="text-danger">*</span></label>
                                    <select name="client_type_pk" class="form-select  edit-client-type-pk"
                                        id="editDrClientNameSelect">
                                        <option value="">Select Client Name</option>
                                        @foreach($clientNamesByType as $type => $list)
                                        @foreach($list as $c)
                                        <option value="{{ $c->id }}" data-type="{{ $c->client_type }}"
                                            data-client-name="{{ strtolower($c->client_name ?? '') }}">
                                            {{ $c->client_name }}</option>
                                        @endforeach
                                        @endforeach
                                    </select>
                                    <select id="editDrOtCourseSelect" class="form-select " style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                        <option value="{{ $course->pk }}"
                                            data-course-name="{{ e($course->course_name) }}">
                                            {{ e($course->course_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="editDrCourseSelect" class="form-select " style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                        <option value="{{ $course->pk }}"
                                            data-course-name="{{ e($course->course_name) }}">
                                            {{ e($course->course_name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4" id="editDrNameFieldWrap" style="display:none;">
                                    <label class="form-label voucher-label">Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="client_name" class="form-control  edit-client-name"
                                        id="editDrClientNameInput" placeholder="Client / section / role name" required>
                                    <datalist id="editDrCourseBuyerNames"></datalist>
                                    <datalist id="editDrGenericBuyerNames"></datalist>
                                    <select id="editDrFacultySelect" class="form-select " style="display:none;">
                                        <option value="">Select Faculty</option>
                                        @foreach($faculties ?? [] as $f)
                                        <option value="{{ e($f->full_name) }}">
                                            {{ e($f->full_name_with_code ?? $f->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="editDrAcademyStaffSelect" class="form-select " style="display:none;">
                                        <option value="">Select Academy Staff</option>
                                        @foreach($employees ?? [] as $e)
                                        <option value="{{ e($e->full_name_with_department ?? $e->full_name) }}">
                                            {{ e($e->full_name_with_department ?? $e->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="editDrMessStaffSelect" class="form-select " style="display:none;">
                                        <option value="">Select Mess Staff</option>
                                        @foreach($messStaff ?? [] as $e)
                                        <option value="{{ e($e->full_name_with_department ?? $e->full_name) }}">
                                            {{ e($e->full_name_with_department ?? $e->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="editDrOtStudentSelect" class="form-select " style="display:none;">
                                        <option value="">Select Student</option>
                                    </select>
                                    <select id="editDrCourseNameSelect" class="form-select " style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                        <option value="{{ $course->pk }}">{{ e($course->course_name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label voucher-label">Transfer From Store <span
                                            class="text-danger">*</span></label>
                                    <select name="inve_store_master_pk" class="form-select  edit-store-id" required>
                                        <option value="">Select Store</option>
                                        @foreach($stores as $store)
                                        <option value="{{ $store['id'] }}">{{ $store['store_name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label voucher-label">Remarks / Reference Number / Order
                                        By</label>
                                    <input type="text" name="remarks" class="form-control  edit-remarks"
                                        placeholder="Remarks / Reference Number / Order By (optional)">
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Bill upload removed as per requirement --}}
                    <div class="card mb-4 voucher-section-card">
                        <div class="card-header d-flex justify-content-between align-items-center py-3">
                            <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                            <button type="button"
                                class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
                                id="editModalAddItemRow">
                                <i class="material-symbols-rounded" style="font-size: 1rem;">add</i>
                                <span>Add Item</span>
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="dr-item-details-table-wrap">
                                <table class="table table-bordered table-sm table-hover align-middle mb-0"
                                    id="editReportItemsTable">
                                    <thead class="voucher-brand-head">
                                        <tr>
                                            <th>Item Name <span class="text-white">*</span></th>
                                            <th>Unit</th>
                                            <th>Available Qty</th>
                                            <th>Issue Qty <span class="text-white">*</span></th>
                                            <th>Left Qty</th>
                                            <th>Issue Date</th>
                                            <th>Rate <span class="text-white">*</span></th>
                                            <th>Total Amount</th>
                                            <th></th>
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
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                        <i class="material-symbols-rounded" style="font-size: 1rem;">save</i>
                        <span>Update Selling Voucher</span>
                    </button>
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
    let addRowIndex = 1;
    let editRowIndex = 0;
    let currentStoreId = null;
    let editCurrentStoreId = null;

    function safeFocus(el) {
        if (!el || typeof el.focus !== 'function') return;
        try {
            el.focus({
                preventScroll: true
            });
        } catch (e) {
            try {
                el.focus();
            } catch (e2) {}
        }
    }

    // Prevent "jump to top" while clicking/focusing inside scrollable modals.
    // This guards against scroll resets caused by focus management, overflow toggles, and dropdown portals.
    function installModalScrollGuard(modalId) {
        var modal = document.getElementById(modalId);
        if (!modal) return;

        var last = {
            winTop: 0,
            bodyTop: 0,
            has: false
        };

        function capture() {
            var body = modal.querySelector('.modal-body');
            last.winTop = (typeof window !== 'undefined') ? (window.scrollY || window.pageYOffset || 0) : 0;
            last.bodyTop = body ? body.scrollTop : 0;
            last.has = true;
        }

        function restoreSoon() {
            if (!last.has) return;
            var body = modal.querySelector('.modal-body');

            function restoreOnce() {
                try {
                    window.scrollTo(0, last.winTop);
                } catch (e) {}
                if (body) body.scrollTop = last.bodyTop;
            }
            requestAnimationFrame(restoreOnce);
            setTimeout(restoreOnce, 0);
            setTimeout(restoreOnce, 50);
            setTimeout(restoreOnce, 150);
        }

        modal.addEventListener('pointerdown', function() {
            capture();
            // Some scroll resets happen *after* the click (focus trap / dropdown open / overflow changes).
            restoreSoon();
        }, true);
        modal.addEventListener('focusin', function() {
            capture();
            restoreSoon();
        }, true);
    }

    // We intentionally do NOT toggle modal overflow classes on dropdown open.
    // That pattern was causing the modal-body to jump to the top.

    /**
     * Item rows: Choices list is position:absolute inside nested overflow/table contexts.
     * Pin the panel to the viewport so it is not clipped by modal/table/card.
     */
    function bindDrItemChoicesFixedDropdown(selectEl, choices, api) {
        var modalBody = null;
        var placeScheduled = false;

        function getDropdownEl() {
            return choices.dropdown && choices.dropdown.element;
        }

        function getModalRect() {
            var modal = selectEl.closest ? selectEl.closest('.modal') : null;
            var dialog = modal ? modal.querySelector('.modal-dialog') : null;
            return dialog ? dialog.getBoundingClientRect() : null;
        }

        function place() {
            var dd = getDropdownEl();
            var wrap = api.wrapper;
            if (!dd || !wrap || !wrap.classList.contains('is-open')) return;
            var inner = wrap.querySelector('.choices__inner');
            if (!inner) return;
            var r = inner.getBoundingClientRect();
            var selectRect = selectEl.getBoundingClientRect ? selectEl.getBoundingClientRect() : null;
            var mr = getModalRect();
            var flipped = wrap.classList.contains('is-flipped');
            var margin = 8;
            var spaceBelow = window.innerHeight - r.bottom - margin * 2;
            var spaceAbove = r.top - margin * 2;
            // Use the actual select width (stable) instead of wrapper/table influenced width.
            var baseWidth = (selectRect && selectRect.width) ? selectRect.width : r.width;
            var width = Math.max(180, baseWidth);
            if (mr) {
                width = Math.min(width, Math.max(180, mr.width - margin * 2));
            }
            var leftMin = mr ? (mr.left + margin) : margin;
            var leftMax = mr ? (mr.right - width - margin) : (window.innerWidth - width - margin);
            var leftBase = (selectRect && typeof selectRect.left === 'number') ? selectRect.left : r.left;
            var left = Math.max(leftMin, Math.min(leftBase, leftMax));
            dd.classList.add('dr-item-choices-dropdown-fixed');
            dd.style.setProperty('position', 'fixed', 'important');
            dd.style.setProperty('left', left + 'px', 'important');
            dd.style.setProperty('width', width + 'px', 'important');
            dd.style.setProperty('min-width', width + 'px', 'important');
            dd.style.setProperty('max-width', width + 'px', 'important');
            dd.style.setProperty('max-height', Math.max(120, flipped ? spaceAbove : spaceBelow) + 'px',
                'important');
            dd.style.setProperty('z-index', '200000', 'important');
            if (flipped) {
                dd.style.setProperty('top', 'auto', 'important');
                dd.style.setProperty('bottom', (window.innerHeight - r.top + 2) + 'px', 'important');
            } else {
                dd.style.setProperty('top', (r.bottom + 2) + 'px', 'important');
                dd.style.setProperty('bottom', 'auto', 'important');
            }
        }

        function onScrollOrResize() {
            if (placeScheduled) return;
            placeScheduled = true;
            requestAnimationFrame(function() {
                placeScheduled = false;
                place();
            });
        }

        function onShow() {
            modalBody = selectEl.closest('.modal-body');
            requestAnimationFrame(function() {
                place();
                requestAnimationFrame(place);
            });
            setTimeout(place, 0);
            setTimeout(place, 80);
            window.addEventListener('resize', onScrollOrResize, {
                passive: true
            });
            document.addEventListener('scroll', onScrollOrResize, true);
            if (modalBody) modalBody.addEventListener('scroll', onScrollOrResize, {
                passive: true
            });
        }

        function onHide() {
            var dd = getDropdownEl();
            if (dd) {
                dd.classList.remove('dr-item-choices-dropdown-fixed');
                ['position', 'left', 'top', 'right', 'bottom', 'width', 'min-width', 'max-width', 'max-height',
                    'z-index'
                ].forEach(function(p) {
                    dd.style.removeProperty(p);
                });
            }
            window.removeEventListener('resize', onScrollOrResize);
            document.removeEventListener('scroll', onScrollOrResize, true);
            if (modalBody) modalBody.removeEventListener('scroll', onScrollOrResize);
            modalBody = null;
        }
        selectEl.addEventListener('showDropdown', onShow);
        selectEl.addEventListener('hideDropdown', onHide);
    }

    // Note: we only pin ITEM selects dropdowns to viewport (see bindDrItemChoicesFixedDropdown).
    // For normal selects (store/payment/name), we keep default positioning so width stays aligned.

    document.addEventListener('DOMContentLoaded', function() {
        installModalScrollGuard('addReportModal');
        installModalScrollGuard('editReportModal');
    });

    // Native Choices.js instance helper (keeps legacy alias for existing logic).
    function normalizeChoicesSearchText(text) {
        return String(text || '').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function applyExactChoicesSearchFilter(api, dropdownEl, rawQuery) {
        if (!dropdownEl) return;
        var query = normalizeChoicesSearchText(rawQuery);
        var choiceItems = dropdownEl.querySelectorAll('.choices__item--choice');
        if (!choiceItems || !choiceItems.length) return;
        choiceItems.forEach(function(item) {
            if (item.classList.contains('choices__placeholder')) return;
            var label = normalizeChoicesSearchText(item.textContent || '');
            var show = !query || label === query;
            item.style.display = show ? '' : 'none';
        });
    }

    function createChoicesInstance(selectEl, settings) {
        if (!selectEl || typeof window.Choices === 'undefined') return null;
        if (selectEl.choicesInstance) return selectEl.choicesInstance;
        settings = settings || {};
        var isMulti = !!selectEl.multiple;

        var choiceConfig = {
            allowHTML: false,
            itemSelectText: '',
            shouldSort: false,
            searchEnabled: settings.searchEnabled !== false,
            searchChoices: settings.searchChoices !== false,
            searchFloor: typeof settings.searchFloor === 'number' ? settings.searchFloor : 0,
            searchResultLimit: typeof settings.maxOptions === 'number' ? settings.maxOptions : -1,
            placeholder: true,
            placeholderValue: settings.placeholder || (selectEl.getAttribute('data-placeholder') || selectEl
                .getAttribute('placeholder') || ''),
            searchPlaceholderValue: '',
            removeItemButton: isMulti,
            closeDropdownOnSelect: typeof settings.closeDropdownOnSelect === 'boolean' ? settings
                .closeDropdownOnSelect : !isMulti
        };

        var choices = new window.Choices(selectEl, choiceConfig);
        var api = {
            _choices: choices,
            selectEl: selectEl,
            settings: settings,
            activeOption: null,
            items: [],
            wrapper: choices.containerOuter ? choices.containerOuter.element : null,
            control_input: null,
            getValue: function() {
                if (!this.selectEl) return isMulti ? [] : '';
                if (isMulti) {
                    try {
                        var values = this._choices.getValue(true);
                        if (Array.isArray(values)) return values.map(String).filter(Boolean);
                        return values ? [String(values)] : [];
                    } catch (e) {
                        return Array.from(this.selectEl.selectedOptions || []).map(function(option) {
                            return option.value;
                        }).filter(Boolean);
                    }
                }
                return this.selectEl.value || '';
            },
            setValue: function(v) {
                this._choices.removeActiveItems();

                if (isMulti) {
                    var values = Array.isArray(v) ? v : (v !== '' && v !== null && typeof v !==
                        'undefined' ? [v] : []);
                    values.forEach(function(value) {
                        if (value === '' || value === null || typeof value === 'undefined') return;
                        try {
                            this._choices.setChoiceByValue(String(value));
                        } catch (e) {}
                    }, this);
                } else {
                    var value = (v === null || typeof v === 'undefined') ? '' : String(v);
                    if (value !== '') this._choices.setChoiceByValue(value);
                }

                this.syncItems();
            },
            clear: function() {
                this._choices.removeActiveItems();
                this.syncItems();
            },
            addOption: function(opt) {
                if (!opt) return;
                var val = (opt.value === null || typeof opt.value === 'undefined') ? '' : String(opt.value);
                this._choices.setChoices([{
                    value: val,
                    label: opt.text || val,
                    selected: false,
                    disabled: false
                }], 'value', 'label', false);
            },
            destroy: function() {
                if (this._choices) this._choices.destroy();
                if (this.selectEl) {
                    this.selectEl.choicesInstance = null;
                    this.selectEl.tomselect = null;
                }
            },
            setTextboxValue: function(v) {
                if (this.control_input) this.control_input.value = v || '';
            },
            onSearchChange: function() {},
            refreshOptions: function() {},
            syncItems: function() {
                var v = this.getValue();
                if (isMulti) {
                    this.items = Array.isArray(v) ? v.map(String) : [];
                } else {
                    this.items = (v === '' || v === null || typeof v === 'undefined') ? [] : [String(v)];
                }
            }
        };
        api.control_input = api.wrapper ? api.wrapper.querySelector('input.choices__input--cloned') : null;
        if (api.wrapper && api.wrapper.classList) api.wrapper.classList.add('ts-wrapper');
        if (choices.dropdown && choices.dropdown.element && choices.dropdown.element.classList) {
            choices.dropdown.element.classList.add('ts-dropdown');
        }
        api.syncItems();

        selectEl.addEventListener('change', function() {
            api.syncItems();
        });
        selectEl.addEventListener('showDropdown', function() {
            if (api.control_input) {
                applyExactChoicesSearchFilter(api, choices.dropdown ? choices.dropdown.element : null, api
                    .control_input.value || '');
            }
            if (typeof settings.onDropdownOpen === 'function') {
                settings.onDropdownOpen.call(api, choices.dropdown ? choices.dropdown.element : null);
            }
        });
        selectEl.addEventListener('hideDropdown', function() {
            if (typeof settings.onDropdownClose === 'function') {
                settings.onDropdownClose.call(api, choices.dropdown ? choices.dropdown.element : null);
            }
        });
        if (typeof settings.onInitialize === 'function') settings.onInitialize.call(api);

        if (selectEl.classList.contains('dr-item-select') || selectEl.classList.contains('edit-dr-item-select')) {
            bindDrItemChoicesFixedDropdown(selectEl, choices, api);
        }

        selectEl.choicesInstance = api;
        selectEl.tomselect = api; // legacy alias until full cleanup
        return api;
    }

    var clientNameOptionsAdd = [];
    var clientNameOptionsEdit = [];
    document.addEventListener('DOMContentLoaded', function() {
        var addSel = document.getElementById('drClientNameSelect');
        if (addSel) {
            addSel.querySelectorAll('option[value]').forEach(function(opt) {
                clientNameOptionsAdd.push({
                    value: opt.value,
                    text: (opt.textContent || '').trim(),
                    type: ((opt.dataset.type || '').toLowerCase().trim()),
                    clientName: ((opt.dataset.clientName || '').toLowerCase().trim())
                });
            });
        }
        var editSel = document.getElementById('editDrClientNameSelect');
        if (editSel) {
            editSel.querySelectorAll('option[value]').forEach(function(opt) {
                clientNameOptionsEdit.push({
                    value: opt.value,
                    text: (opt.textContent || '').trim(),
                    type: ((opt.dataset.type || '').toLowerCase().trim()),
                    clientName: ((opt.dataset.clientName || '').toLowerCase().trim())
                });
            });
        }
    });

    function rebuildClientNameSelect(selectEl, optionsList, slug) {
        if (!selectEl || !Array.isArray(optionsList)) return;
        var slugLower = (slug || '').toLowerCase().trim();
        var filtered = optionsList.filter(function(o) {
            return (o.type || '').toLowerCase().trim() === slugLower;
        });
        if (selectEl.tomselect) {
            try {
                selectEl.tomselect.destroy();
            } catch (e) {}
        }
        if (selectEl.id === 'drClientNameSelect') addModalTomSelectInstances.client = null;
        selectEl.innerHTML = '<option value="">Select Client Name</option>';
        filtered.forEach(function(o) {
            var opt = document.createElement('option');
            opt.value = o.value;
            opt.textContent = o.text;
            opt.setAttribute('data-type', ((o.type || '').toLowerCase().trim()));
            opt.setAttribute('data-client-name', ((o.clientName || '').toLowerCase().trim()));
            selectEl.appendChild(opt);
        });
        if (typeof Choices !== 'undefined') {
            var inst = createChoicesInstance(selectEl, createBlankSearchConfig({
                placeholder: 'Select Client Name',
                clearOnOpen: true
            }));
            if (selectEl.id === 'drClientNameSelect') addModalTomSelectInstances.client = inst;
        }
    }

    function rebuildEditClientNameSelect(slug) {
        var editSel = document.getElementById('editDrClientNameSelect');
        if (!editSel || !clientNameOptionsEdit.length) return;
        var slugLower = (slug || '').toLowerCase().trim();
        var filtered = clientNameOptionsEdit.filter(function(o) {
            return (o.type || '').toLowerCase().trim() === slugLower;
        });
        if (editSel.tomselect) {
            try {
                editSel.tomselect.destroy();
            } catch (e) {}
            editModalTomSelectInstances.client = null;
        }
        editSel.innerHTML = '<option value="">Select Client Name</option>';
        filtered.forEach(function(o) {
            var opt = document.createElement('option');
            opt.value = o.value;
            opt.textContent = o.text;
            opt.setAttribute('data-type', ((o.type || '').toLowerCase().trim()));
            opt.setAttribute('data-client-name', ((o.clientName || '').toLowerCase().trim()));
            editSel.appendChild(opt);
        });
        if (typeof Choices !== 'undefined') {
            editModalTomSelectInstances.client = createChoicesInstance(editSel, createBlankSearchConfig({
                placeholder: 'Select Client Name',
                clearOnOpen: true
            }));
        }
    }

    function getSelectValue(select) {
        if (!select) return '';
        return select.tomselect ? select.tomselect.getValue() : select.value;
    }

    function setSelectValue(select, value) {
        if (!select) return;
        var v = (value === null || value === undefined) ? '' : String(value);
        if (select.tomselect) select.tomselect.setValue(v);
        else select.value = v;
    }

    /** After Choices.js init on Edit Date-Range modal, apply API values to instances (store, payment, client, course, staff name). */
    function syncEditDrChoicesFromVoucher(v, slug) {
        slug = String(slug || 'employee').toLowerCase();
        var paySel = document.querySelector('#editReportModal select.edit-payment-type');
        if (paySel && paySel.tomselect) {
            try { paySel.tomselect.setValue(String(v.payment_type ?? 1)); } catch (e) {}
        }
        var storeSel = document.querySelector('#editReportModal select.edit-store-id');
        var sid = v.store_id || v.inve_store_master_pk || '';
        if (storeSel && storeSel.tomselect && sid !== '') {
            try { storeSel.tomselect.setValue(String(sid)); } catch (e) {}
        }
        var ecs = document.getElementById('editDrClientNameSelect');
        if (ecs && ecs.tomselect && slug !== 'ot' && slug !== 'course' && v.client_type_pk != null && String(v.client_type_pk) !== '') {
            try { ecs.tomselect.setValue(String(v.client_type_pk)); } catch (e) {}
        }
        var eot = document.getElementById('editDrOtCourseSelect');
        if (eot && eot.tomselect && slug === 'ot' && v.client_type_pk != null && String(v.client_type_pk) !== '') {
            try { eot.tomselect.setValue(String(v.client_type_pk)); } catch (e) {}
        }
        var edc = document.getElementById('editDrCourseSelect');
        if (edc && edc.tomselect && slug === 'course' && v.client_type_pk != null && String(v.client_type_pk) !== '') {
            try { edc.tomselect.setValue(String(v.client_type_pk)); } catch (e) {}
        }
        var cn = String(v.client_name || '').trim();
        if (cn) {
            ['editDrFacultySelect', 'editDrAcademyStaffSelect', 'editDrMessStaffSelect'].forEach(function(id) {
                var el = document.getElementById(id);
                if (!el || !el.tomselect) return;
                try { el.tomselect.setValue(cn); } catch (e) {}
            });
        }
    }

    function getSelectSelectedOption(select) {
        if (!select) return null;
        const val = getSelectValue(select);
        for (let i = 0; i < select.options.length; i++) {
            if (select.options[i].value == val) return select.options[i];
        }
        return null;
    }

    function setSelectVisible(select, visible) {
        if (!select) return;
        var wrapper = null;
        if (select.tomselect && select.tomselect.wrapper) wrapper = select.tomselect.wrapper;
        if (!wrapper && select.parentElement) {
            var p = select.parentElement;
            if (p.classList && p.classList.contains('ts-wrapper')) wrapper = p;
            else if (p.parentElement && p.parentElement.classList && p.parentElement.classList.contains(
                    'ts-wrapper')) wrapper = p.parentElement;
        }
        if (wrapper) wrapper.style.display = visible ? '' : 'none';
        else select.style.display = visible ? 'block' : 'none';
    }

    var addModalTomSelectInstances = {
        payment: null,
        client: null,
        store: null
    };
    var editModalTomSelectInstances = {
        payment: null,
        client: null,
        store: null
    };

    function destroyAddModalTomSelects() {
        if (addModalTomSelectInstances.payment) {
            try {
                addModalTomSelectInstances.payment.destroy();
            } catch (e) {}
            addModalTomSelectInstances.payment = null;
        }
        if (addModalTomSelectInstances.client) {
            try {
                addModalTomSelectInstances.client.destroy();
            } catch (e) {}
            addModalTomSelectInstances.client = null;
        }
        if (addModalTomSelectInstances.store) {
            try {
                addModalTomSelectInstances.store.destroy();
            } catch (e) {}
            addModalTomSelectInstances.store = null;
        }
        document.querySelectorAll('#addReportModal select').forEach(function(el) {
            if (el.tomselect) {
                try {
                    el.tomselect.destroy();
                } catch (e) {}
            }
        });
    }

    function destroyEditModalTomSelects() {
        if (editModalTomSelectInstances.payment) {
            try {
                editModalTomSelectInstances.payment.destroy();
            } catch (e) {}
            editModalTomSelectInstances.payment = null;
        }
        if (editModalTomSelectInstances.client) {
            try {
                editModalTomSelectInstances.client.destroy();
            } catch (e) {}
            editModalTomSelectInstances.client = null;
        }
        if (editModalTomSelectInstances.store) {
            try {
                editModalTomSelectInstances.store.destroy();
            } catch (e) {}
            editModalTomSelectInstances.store = null;
        }
        document.querySelectorAll('#editReportModal select').forEach(function(el) {
            if (el.tomselect) {
                try {
                    el.tomselect.destroy();
                } catch (e) {}
            }
        });
    }

    function createBlankSearchConfig(extra) {
        return Object.assign({
            allowEmptyOption: true,
            dropdownParent: 'body',
            searchField: ['text'],
            controlInput: '<input>',
            highlight: false,
            onInitialize: function() {
                this.activeOption = null;
            },
            onDropdownOpen: function(dropdown) {
                var self = this;
                var modalEl = self.input && self.input.closest ? self.input.closest('.modal') : null;
                var modalBody = modalEl ? modalEl.querySelector('.modal-body') : null;
                var helper = window.MessModalDropdownStability;
                self._modalDropdownState = helper && modalEl ? helper.onOpen(modalEl) : null;
                if (!self._modalDropdownState && modalBody) self._modalDropdownState = {
                    scrollTop: modalBody.scrollTop
                };

                function clearInputAndCursor() {
                    var prevWinTop = (typeof window !== 'undefined') ? (window.scrollY || window
                        .pageYOffset || 0) : 0;
                    // Choices dropdown me visible cloned input ko priority do.
                    var input = (dropdown && dropdown.querySelector('input.choices__input--cloned')) ||
                        (dropdown && dropdown.querySelector('input')) ||
                        self.control_input;
                    if (typeof self.setTextboxValue === 'function') self.setTextboxValue('');
                    if (typeof self.onSearchChange === 'function') self.onSearchChange('');
                    if (typeof self.refreshOptions === 'function') self.refreshOptions(false);
                    if (input) {
                        // Ensure search field is visible in all modal dropdown contexts (including Edit modal).
                        input.style.display = 'block';
                        input.style.visibility = 'visible';
                        input.style.opacity = '1';
                        input.value = '';
                        safeFocus(input);
                        try {
                            input.setSelectionRange(0, 0);
                        } catch (e) {}
                        input.scrollLeft = 0;
                    }
                    // Some browsers still scroll on focus; restore window position.
                    if (typeof window !== 'undefined') {
                        requestAnimationFrame(function() {
                            try {
                                window.scrollTo(0, prevWinTop);
                            } catch (e) {}
                        });
                        setTimeout(function() {
                            try {
                                window.scrollTo(0, prevWinTop);
                            } catch (e) {}
                        }, 0);
                    }
                    if (helper && modalEl) {
                        helper.keepScroll(modalEl, self._modalDropdownState);
                    } else if (modalBody && self._modalDropdownState && typeof self._modalDropdownState
                        .scrollTop === 'number') {
                        modalBody.scrollTop = self._modalDropdownState.scrollTop;
                    }
                }
                // Agar clearOnOpen true hai to har open par selection bhi hatao
                if (self.settings && self.settings.clearOnOpen) {
                    self.clear(true);
                }
                clearInputAndCursor();
                setTimeout(clearInputAndCursor, 0);
                setTimeout(clearInputAndCursor, 50);
                setTimeout(clearInputAndCursor, 100);
                if (dropdown) {
                    setTimeout(function() {
                        var opts = dropdown.querySelectorAll(
                            '.option.active, .option.selected, .option[aria-selected="true"]'
                        );
                        opts.forEach(function(opt) {
                            opt.classList.remove('active');
                            opt.classList.remove('selected');
                            opt.setAttribute('aria-selected', 'false');
                        });
                    }, 0);
                }
            },
            onDropdownClose: function() {
                var self = this;
                var modalEl = self.input && self.input.closest ? self.input.closest('.modal') : null;
                var modalBody = modalEl ? modalEl.querySelector('.modal-body') : null;
                var helper = window.MessModalDropdownStability;
                if (helper && modalEl) {
                    helper.onClose(modalEl, self._modalDropdownState);
                } else if (modalBody && self._modalDropdownState && typeof self._modalDropdownState
                    .scrollTop === 'number') {
                    modalBody.scrollTop = self._modalDropdownState.scrollTop;
                }
                self._modalDropdownState = null;
            }
        }, extra || {});
    }

    function createItemSelectConfig() {
        return createBlankSearchConfig({
            placeholder: 'Select Item',
            maxOptions: null,
            clearOnOpen: false,
            searchEnabled: true,
            searchChoices: true,
            searchFloor: 0
        });
    }

    function ensureChoicesInitializedForItemSelect(selectEl) {
        if (!selectEl || typeof Choices === 'undefined') return null;
        if (selectEl.tomselect) return selectEl.tomselect;
        return createChoicesInstance(selectEl, createItemSelectConfig());
    }

    function initAddModalTomSelects() {
        if (typeof Choices === 'undefined') return;
        var paymentSel = document.querySelector('#addReportModal select[name="payment_type"]');
        if (paymentSel && !paymentSel.tomselect) {
            addModalTomSelectInstances.payment = createChoicesInstance(paymentSel, createBlankSearchConfig({
                placeholder: 'Payment Type',
                clearOnOpen: true
            }));
        }
        var clientSel = document.getElementById('drClientNameSelect');
        var clientTypeRadio = document.querySelector('#addReportModal .dr-client-type-radio:checked');
        var slug = clientTypeRadio ? (clientTypeRadio.value || '').toLowerCase() : 'employee';
        if (clientSel && slug !== 'ot' && slug !== 'course' && clientNameOptionsAdd.length) {
            rebuildClientNameSelect(clientSel, clientNameOptionsAdd, slug);
        } else if (clientSel && !clientSel.tomselect) {
            addModalTomSelectInstances.client = createChoicesInstance(clientSel, createBlankSearchConfig({
                placeholder: 'Select Client Name',
                clearOnOpen: true
            }));
        }
        var storeSel = document.querySelector('#addReportModal select[name="inve_store_master_pk"]');
        if (storeSel && !storeSel.tomselect) {
            addModalTomSelectInstances.store = createChoicesInstance(storeSel, createBlankSearchConfig({
                placeholder: 'Select Store',
                clearOnOpen: true
            }));
        }
        var nameSelectIds = ['drOtCourseSelect', 'drCourseSelect', 'drFacultySelect', 'drAcademyStaffSelect',
            'drMessStaffSelect', 'drOtStudentSelect', 'drCourseNameSelect'
        ];
        nameSelectIds.forEach(function(id) {
            var sel = document.getElementById(id);
            if (!sel || sel.tomselect) return;
            var ph = id.indexOf('Faculty') !== -1 ? 'Select Faculty' : id.indexOf('Academy') !== -1 ?
                'Select Academy Staff' : id.indexOf('Mess') !== -1 ? 'Select Mess Staff' : id.indexOf(
                    'OtStudent') !== -1 ? 'Select Student' : 'Select Course';
            createChoicesInstance(sel, createBlankSearchConfig({
                placeholder: ph,
                clearOnOpen: true
            }));
        });
        var otCourseSel = document.getElementById('drOtCourseSelect');
        var drCourseSel = document.getElementById('drCourseSelect');
        setSelectVisible(otCourseSel, slug === 'ot');
        setSelectVisible(drCourseSel, slug === 'course');
        if (clientSel) setSelectVisible(clientSel, slug !== 'ot' && slug !== 'course');
        document.querySelectorAll('#addModalItemsBody .dr-item-select').forEach(function(select) {
            if (select.tomselect) return;
            createChoicesInstance(select, createItemSelectConfig());
        });
        if (typeof updateDrNameField === 'function') updateDrNameField();
        var addChecked = document.querySelector('#addReportModal .dr-client-type-radio:checked');
        if (addChecked) {
            var w1 = document.getElementById('drClientNameWrap');
            var w2 = document.getElementById('drNameFieldWrap');
            if (w1) w1.style.display = '';
            if (w2) w2.style.display = '';
        }
    }

    // Defensive init: if an item select is clicked before Choices is attached,
    // initialize immediately and open searchable dropdown.
    document.addEventListener('mousedown', function(e) {
        var selectEl = e.target && e.target.closest ? e.target.closest(
            '#addModalItemsBody .dr-item-select, #editModalItemsBody .edit-dr-item-select') : null;
        if (!selectEl || selectEl.tomselect || typeof Choices === 'undefined') return;

        // Keep modal scroll position stable while we initialize and open the dropdown.
        var modalBody = selectEl.closest ? selectEl.closest('.modal') : null;
        modalBody = modalBody ? modalBody.querySelector('.modal-body') : null;
        var prevTop = modalBody ? modalBody.scrollTop : 0;
        var prevWinTop = (typeof window !== 'undefined') ? (window.scrollY || window.pageYOffset || 0) : 0;

        e.preventDefault();
        var inst = ensureChoicesInitializedForItemSelect(selectEl);
        setTimeout(function() {
            if (inst && inst._choices && typeof inst._choices.showDropdown === 'function') {
                inst._choices.showDropdown();
            }
            var wrapper = inst && inst.wrapper ? inst.wrapper : null;
            var input = wrapper ? wrapper.querySelector(
                '.choices__list--dropdown .choices__input--cloned') : null;
            if (input) {
                input.style.display = 'block';
                safeFocus(input);
            }

            if (modalBody) {
                requestAnimationFrame(function() {
                    modalBody.scrollTop = prevTop;
                });
                setTimeout(function() {
                    modalBody.scrollTop = prevTop;
                }, 0);
            }
            requestAnimationFrame(function() {
                try {
                    window.scrollTo(0, prevWinTop);
                } catch (e) {}
            });
            setTimeout(function() {
                try {
                    window.scrollTo(0, prevWinTop);
                } catch (e) {}
            }, 0);
        }, 0);
    }, true);

    function initEditModalTomSelects() {
        if (typeof Choices === 'undefined') return;
        var paymentSel = document.querySelector('#editReportModal select.edit-payment-type');
        if (paymentSel && !paymentSel.tomselect) {
            editModalTomSelectInstances.payment = createChoicesInstance(paymentSel, createBlankSearchConfig({
                placeholder: 'Payment Type',
                clearOnOpen: true
            }));
        }
        var clientSel = document.getElementById('editDrClientNameSelect');
        var editRadio = document.querySelector('#editReportModal .edit-dr-client-type-radio:checked');
        var editSlug = editRadio ? (editRadio.value || '').toLowerCase() : 'employee';
        if (clientSel && editSlug !== 'ot' && editSlug !== 'course' && clientNameOptionsEdit.length) {
            var preservedPk = getSelectValue(clientSel) || '';
            rebuildEditClientNameSelect(editSlug);
            clientSel = document.getElementById('editDrClientNameSelect');
            if (clientSel && preservedPk) {
                if (clientSel.tomselect) clientSel.tomselect.setValue(preservedPk);
                else clientSel.value = preservedPk;
            }
        } else if (clientSel && !clientSel.tomselect) {
            editModalTomSelectInstances.client = createChoicesInstance(clientSel, createBlankSearchConfig({
                placeholder: 'Select Client Name',
                clearOnOpen: true
            }));
        }
        var storeSel = document.querySelector('#editReportModal select.edit-store-id');
        if (storeSel && !storeSel.tomselect) {
            editModalTomSelectInstances.store = createChoicesInstance(storeSel, createBlankSearchConfig({
                placeholder: 'Select Store',
                clearOnOpen: true
            }));
        }
        var editNameInpForInit = document.getElementById('editDrClientNameInput');
        var nameValForInit = (editNameInpForInit && editNameInpForInit.value) ? String(editNameInpForInit.value)
            .trim() : '';
        if (nameValForInit) {
            var fn = document.getElementById('editDrFacultySelect');
            var an = document.getElementById('editDrAcademyStaffSelect');
            var mn = document.getElementById('editDrMessStaffSelect');
            if (fn) fn.value = nameValForInit;
            if (an) an.value = nameValForInit;
            if (mn) mn.value = nameValForInit;
        }
        var editNameIds = ['editDrOtCourseSelect', 'editDrCourseSelect', 'editDrFacultySelect',
            'editDrAcademyStaffSelect', 'editDrMessStaffSelect', 'editDrOtStudentSelect',
            'editDrCourseNameSelect'
        ];
        editNameIds.forEach(function(id) {
            var sel = document.getElementById(id);
            if (!sel || sel.tomselect) return;
            var ph = id.indexOf('Faculty') !== -1 ? 'Select Faculty' : id.indexOf('Academy') !== -1 ?
                'Select Academy Staff' : id.indexOf('Mess') !== -1 ? 'Select Mess Staff' : 'Select Course';
            createChoicesInstance(sel, createBlankSearchConfig({
                placeholder: ph,
                clearOnOpen: true
            }));
        });
        document.querySelectorAll('#editModalItemsBody .edit-dr-item-select').forEach(function(select) {
            if (select.tomselect) return;
            createChoicesInstance(select, createItemSelectConfig());
        });
        if (typeof updateEditDrNameField === 'function') updateEditDrNameField();
        var editChecked = document.querySelector('#editReportModal .edit-dr-client-type-radio:checked');
        if (editChecked) {
            var ew1 = document.getElementById('editDrClientNameWrap');
            var ew2 = document.getElementById('editDrNameFieldWrap');
            if (ew1) ew1.style.display = '';
            if (ew2) ew2.style.display = '';
            var es = (editChecked.value || '').toLowerCase();
            var ec = document.getElementById('editDrClientNameSelect');
            var eo = document.getElementById('editDrOtCourseSelect');
            var ed = document.getElementById('editDrCourseSelect');
            if (es === 'ot') {
                setSelectVisible(ec, false);
                setSelectVisible(eo, true);
                setSelectVisible(ed, false);
            } else if (es === 'course') {
                setSelectVisible(ec, false);
                setSelectVisible(eo, false);
                setSelectVisible(ed, true);
            } else {
                setSelectVisible(ec, true);
                setSelectVisible(eo, false);
                setSelectVisible(ed, false);
            }
        }
        if (typeof updateEditDrNameField === 'function') updateEditDrNameField();
        var editNameInp = document.getElementById('editDrClientNameInput');
        var savedName = (editNameInp && editNameInp.value) ? String(editNameInp.value).trim() : '';

        function syncEditNameValue() {
            var val = (document.getElementById('editDrClientNameInput') || {}).value;
            if (val !== undefined && val !== null) val = String(val).trim();
            if (!val) return;
            [document.getElementById('editDrFacultySelect'), document.getElementById('editDrAcademyStaffSelect'),
                document.getElementById('editDrMessStaffSelect')
            ].forEach(function(sel) {
                if (!sel) return;
                var wrapper = (sel.tomselect && sel.tomselect.wrapper) ? sel.tomselect.wrapper : (sel
                    .parentElement && sel.parentElement.classList && sel.parentElement.classList
                    .contains('ts-wrapper') ? sel.parentElement : null);
                if (wrapper && wrapper.style.display !== 'none') {
                    if (sel.tomselect) {
                        sel.tomselect.setValue(val);
                        if (!sel.tomselect.items || sel.tomselect.items.length === 0) {
                            sel.tomselect.addOption({
                                value: val,
                                text: val
                            });
                            sel.tomselect.setValue(val);
                        }
                    } else {
                        sel.value = val;
                    }
                }
            });
        }
        syncEditNameValue();
        setTimeout(syncEditNameValue, 0);
        setTimeout(syncEditNameValue, 80);
        setTimeout(syncEditNameValue, 200);
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Choices === 'undefined') return;
        var filterStatus = document.querySelector('form[method="GET"] select[name="status[]"]');
        var filterStore = document.querySelector('form[method="GET"] select[name="store[]"]');

        function setFilterDropdownState(instance, isOpen) {
            if (!instance || !instance.selectEl || !instance.selectEl.closest) return;
            var filterCard = instance.selectEl.closest('.selling-voucher-filter');
            if (!filterCard) return;
            filterCard.classList.toggle('dropdown-open', !!isOpen);
        }

        function createFilterChoicesConfig(placeholder) {
            return {
                placeholder: placeholder,
                closeDropdownOnSelect: false,
                onInitialize: function() {
                    this.activeOption = null;
                },
                onDropdownOpen: function(dropdown) {
                    var self = this;
                    setFilterDropdownState(self, true);

                    function clearInputAndCursor() {
                        var input = self.control_input || (dropdown && dropdown.querySelector('input'));
                        if (typeof self.setTextboxValue === 'function') self.setTextboxValue('');
                        if (typeof self.onSearchChange === 'function') self.onSearchChange('');
                        if (typeof self.refreshOptions === 'function') self.refreshOptions(false);
                        if (input) {
                            input.value = '';
                            input.focus();
                            try {
                                input.setSelectionRange(0, 0);
                            } catch (e) {}
                            input.scrollLeft = 0;
                        }
                    }

                    clearInputAndCursor();
                    setTimeout(clearInputAndCursor, 0);

                    if (dropdown) {
                        setTimeout(function() {
                            var opts = dropdown.querySelectorAll('.option.active');
                            opts.forEach(function(opt) {
                                opt.classList.remove('active');
                            });
                        }, 0);
                    }
                },
                onDropdownClose: function() {
                    setFilterDropdownState(this, false);
                }
            };
        }

        if (filterStatus) {
            if (filterStatus.tomselect) filterStatus.tomselect.destroy();
            createChoicesInstance(filterStatus, createFilterChoicesConfig('All Statuses'));
        }

        if (filterStore) {
            if (filterStore.tomselect) filterStore.tomselect.destroy();
            createChoicesInstance(filterStore, createFilterChoicesConfig('All Stores'));
        }
    });

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

    function getBaseAvailableForItem(itemId) {
        if (!itemId) return 0;
        const item = filteredItems.find(function(i) {
            return String(i.id) === String(itemId);
        });
        return item ? (parseFloat(item.available_quantity) || 0) : 0;
    }

    function refreshAllAvailable() {
        const rows = document.querySelectorAll('#addModalItemsBody .dr-item-row');
        const usedByItem = {};

        rows.forEach(function(row) {
            const select = row.querySelector('.dr-item-select');
            const itemId = select ? getSelectValue(select) : '';
            const availInp = row.querySelector('.dr-avail');
            const leftInp = row.querySelector('.dr-left');
            if (!itemId || !availInp) return;

            const base = getBaseAvailableForItem(itemId);
            const alreadyUsed = usedByItem[itemId] || 0;
            const availableForRow = Math.max(0, base - alreadyUsed);

            availInp.value = availableForRow.toFixed(2);

            const qty = parseFloat(row.querySelector('.dr-qty').value) || 0;
            if (leftInp) {
                leftInp.value = Math.max(0, availableForRow - qty).toFixed(2);
            }

            usedByItem[itemId] = alreadyUsed + qty;
            enforceQtyWithinAvailable(row, '.dr-avail', '.dr-qty');
        });
    }

    function fetchStoreItems(storeId, callback) {
        if (!storeId) {
            filteredItems = itemSubcategories;
            if (callback) callback();
            return;
        }
        // Reuse the same store-items endpoint as Selling Voucher (material-management)
        const url = "{{ url('admin/mess/material-management') }}" + '/store/' + encodeURIComponent(storeId) +
            '/items';
        fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(r) {
                if (!r.ok) {
                    return r.text().then(function(t) {
                        var msg = (r.status === 500 && t) ? t : ('Server returned ' + r.status);
                        throw new Error(msg);
                    });
                }
                return r.json();
            })
            .then(function(data) {
                if (Array.isArray(data)) {
                    filteredItems = data;
                } else if (data && data.error) {
                    throw new Error(data.error);
                } else {
                    filteredItems = [];
                }
                if (callback) callback();
            })
            .catch(function(err) {
                console.error('Store items fetch failed:', err);
                filteredItems = itemSubcategories || [];
                if (callback) callback();
                alert(
                    'Could not load store-specific items. Showing all items; available quantity may not reflect this store.'
                    );
            });
    }

    function updateAddItemDropdowns() {
        const rows = document.querySelectorAll('#addModalItemsBody .dr-item-row');
        rows.forEach(row => {
            const select = row.querySelector('.dr-item-select');
            if (!select) return;

            const currentValue = getSelectValue(select);
            if (select.tomselect) {
                try {
                    select.tomselect.destroy();
                } catch (e) {}
            }
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

            if (typeof Choices !== 'undefined') createChoicesInstance(select, createItemSelectConfig());
            updateAddRowUnit(row);
        });
    }

    function updateEditItemDropdowns() {
        const rows = document.querySelectorAll('#editModalItemsBody .edit-dr-item-row');
        rows.forEach(row => {
            const select = row.querySelector('.edit-dr-item-select');
            if (!select) return;

            const currentValue = getSelectValue(select);
            if (select.tomselect) {
                try {
                    select.tomselect.destroy();
                } catch (e) {}
            }
            select.innerHTML = '<option value="">Select Item</option>';

            const sourceItems = Array.isArray(filteredItems) && filteredItems.length > 0 ? filteredItems :
                itemSubcategories;
            sourceItems.forEach(item => {
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

            if (typeof Choices !== 'undefined') createChoicesInstance(select, createItemSelectConfig());
            const o = getSelectSelectedOption(select);
            const unitInp = row.querySelector('.edit-dr-unit');
            const rateInp = row.querySelector('.edit-dr-rate');
            const availInp = row.querySelector('.edit-dr-avail');
            if (unitInp) unitInp.value = (o && o.dataset.unit) ? o.dataset.unit : '—';
            if (rateInp && o && o.dataset.rate) rateInp.value = o.dataset.rate;
            if (availInp && o && o.dataset.available) availInp.value = o.dataset.available;
            updateEditRowLeft(row);
            updateEditRowTotal(row);
        });
        refreshEditAllAvailable();
        updateEditGrandTotal();
    }

    function getAddRowHtml(index) {
        const options = filteredItems.map(s => {
            let attrs = 'data-unit="' + (s.unit_measurement || '').replace(/"/g, '&quot;') +
                '" data-rate="' + (s.standard_cost || 0) + '" data-available="' + (s.available_quantity ||
                    0) + '"';
            if (s.price_tiers && s.price_tiers.length > 0) {
                attrs += ' data-price-tiers="' + (JSON.stringify(s.price_tiers) || '').replace(/"/g,
                    '&quot;') + '"';
            }
            return '<option value="' + s.id + '" ' + attrs + '>' + (s.item_name || '—').replace(/</g,
                '&lt;') + '</option>';
        }).join('');
        return '<tr class="dr-item-row">' +
            '<td><select name="items[' + index +
            '][item_subcategory_id]" class="form-select  dr-item-select" required><option value="">Select Item</option>' +
            options + '</select></td>' +
            '<td><input type="text" name="items[' + index +
            '][unit]" class="form-control  dr-unit" readonly placeholder="—"></td>' +
            '<td><input type="number" name="items[' + index +
            '][available_quantity]" class="form-control  dr-avail bg-light" readonly></td>' +
            '<td><input type="number" name="items[' + index +
            '][quantity]" class="form-control  dr-qty" step="0.01" required><div class="invalid-feedback">Issue Qty cannot exceed Available Qty.</div></td>' +
            '<td><input type="text" class="form-control  dr-left bg-light" readonly placeholder="0"></td>' +
            '<td><input type="date" name="items[' + index +
            '][issue_date]" class="form-control  dr-issue-date" value="' + new Date().toISOString().slice(0, 10) +
            '"></td>' +
            '<td><input type="number" name="items[' + index +
            '][rate]" class="form-control  dr-rate" step="0.01" min="0" required></td>' +
            '<td><input type="text" class="form-control  dr-total bg-light" readonly></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger dr-remove-row voucher-icon-btn" title="Remove">×</button></td>' +
            '</tr>';
    }

    function updateAddRowUnit(row) {
        const sel = row.querySelector('.dr-item-select');
        const opt = getSelectSelectedOption(sel);
        const unitInp = row.querySelector('.dr-unit');
        const rateInp = row.querySelector('.dr-rate');
        const availInp = row.querySelector('.dr-avail');
        if (unitInp) unitInp.value = (opt && opt.dataset.unit) ? opt.dataset.unit : '—';
        // Only auto-set rate if user has not manually overridden it
        if (rateInp && rateInp.dataset.manualRate !== '1' && opt && opt.dataset.rate) {
            rateInp.value = opt.dataset.rate;
        }
        if (availInp && opt && opt.dataset.available) availInp.value = opt.dataset.available;
        if (availInp) availInp.readOnly = true;
        refreshAllAvailable();
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
        const isManualRate = rateInp && rateInp.dataset.manualRate === '1';
        const totalInp = row.querySelector('.dr-total');
        const sel = row.querySelector('.dr-item-select');
        const opt = getSelectSelectedOption(sel);
        const tiersJson = opt && opt.getAttribute('data-price-tiers');
        const tiers = tiersJson ? (function() {
            try {
                return JSON.parse(tiersJson);
            } catch (e) {
                return null;
            }
        })() : null;
        let total;
        if (!isManualRate && tiers && tiers.length > 0 && qty > 0) {
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
            const storeId = getSelectValue(this);
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
        var newItemSelect = newTr.querySelector('.dr-item-select');
        if (newItemSelect && typeof Choices !== 'undefined') {
            createChoicesInstance(newItemSelect, createItemSelectConfig());
        }
        updateAddRowUnit(newTr);
        newTr.querySelector('.dr-avail').addEventListener('input', function() {
            updateAddRowLeft(newTr);
        });
        newTr.querySelector('.dr-qty').addEventListener('input', function() {
            refreshAllAvailable();
            updateAddRowTotal(newTr);
            updateAddGrandTotal();
        });
        newTr.querySelector('.dr-rate').addEventListener('input', function() {
            // Mark that the user has manually set the rate so it is not auto-overwritten
            this.dataset.manualRate = '1';
            updateAddRowTotal(newTr);
            updateAddGrandTotal();
        });
        newTr.querySelector('.dr-item-select').addEventListener('change', function() {
            // On item change, allow auto-rate again until user edits manually
            const rateInp = newTr.querySelector('.dr-rate');
            if (rateInp) rateInp.dataset.manualRate = '';
            updateAddRowUnit(newTr);
        });
        newTr.querySelector('.dr-remove-row').addEventListener('click', function() {
            newTr.remove();
            refreshAllAvailable();
            updateAddGrandTotal();
            const rows = tbody.querySelectorAll('.dr-item-row');
            if (rows.length === 1) rows[0].querySelector('.dr-remove-row').disabled = true;
        });
        tbody.querySelectorAll('.dr-remove-row').forEach(function(btn) {
            btn.disabled = tbody.querySelectorAll('.dr-item-row').length <= 1;
        });
    });

    document.querySelectorAll('#addModalItemsBody .dr-item-row').forEach(function(row) {
        row.querySelector('.dr-item-select').addEventListener('change', function() {
            const rateInp = row.querySelector('.dr-rate');
            if (rateInp) rateInp.dataset.manualRate = '';
            updateAddRowUnit(row);
        });
        row.querySelector('.dr-avail').addEventListener('input', function() {
            updateAddRowLeft(row);
        });
        row.querySelector('.dr-qty').addEventListener('input', function() {
            refreshAllAvailable();
            updateAddRowTotal(row);
            updateAddGrandTotal();
        });
        row.querySelector('.dr-rate').addEventListener('input', function() {
            const rateInp = row.querySelector('.dr-rate');
            if (rateInp) rateInp.dataset.manualRate = '1';
            updateAddRowTotal(row);
            updateAddGrandTotal();
        });
    });

    document.getElementById('addModalItemsBody').addEventListener('click', function(e) {
        if (e.target.classList.contains('dr-remove-row')) {
            const row = e.target.closest('tr');
            if (row && document.getElementById('addModalItemsBody').querySelectorAll('.dr-item-row')
                .length > 1) {
                row.remove();
                refreshAllAvailable();
                updateAddGrandTotal();
            }
        }
    });

    // Delegate input/change on items tbody so Available Qty updates in real time when qty/rate change in any row
    const addModalItemsBodyEl = document.getElementById('addModalItemsBody');
    if (addModalItemsBodyEl) {
        addModalItemsBodyEl.addEventListener('input', function(e) {
            if (e.target.classList.contains('dr-qty') || e.target.classList.contains('dr-rate')) {
                const row = e.target.closest('.dr-item-row');
                if (row) {
                    refreshAllAvailable();
                    updateAddRowTotal(row);
                    updateAddGrandTotal();
                }
            }
        });
        addModalItemsBodyEl.addEventListener('change', function(e) {
            if (e.target.classList.contains('dr-qty') || e.target.classList.contains('dr-rate')) {
                const row = e.target.closest('.dr-item-row');
                if (row) {
                    refreshAllAvailable();
                    updateAddRowTotal(row);
                    updateAddGrandTotal();
                }
            }
        });
    }

    // Delegate input/change from add modal so Left Qty + Total update when qty/rate change
    const addReportModalEl = document.getElementById('addReportModal');
    if (addReportModalEl) {
        function onAddModalQtyOrRateInput(e) {
            if (!e.target.matches('.dr-avail, .dr-qty, .dr-rate')) return;
            const row = e.target.closest('.dr-item-row');
            if (!row) return;
            refreshAllAvailable();
            updateAddRowTotal(row);
            updateAddGrandTotal();
        }
        addReportModalEl.addEventListener('input', onAddModalQtyOrRateInput);
        addReportModalEl.addEventListener('change', onAddModalQtyOrRateInput);
    }

    // Enter key inside Item Details table triggers Add Item (and prevents form submit)
    // Dropdown (Choices.js) me Enter/Tab apna normal behaviour rakhega
    const addReportItemsTable = document.getElementById('addReportItemsTable');
    if (addReportModalEl && addReportItemsTable) {
        addReportModalEl.addEventListener('keydown', function(e) {
            if (e.key !== 'Enter') return;
            const activeEl = document.activeElement;
            if (!activeEl || !addReportItemsTable.contains(activeEl)) return;

            // Agar Choices.js dropdown open hai (input inside .choices.is-open), toh Enter ko normal rehne do
            var choicesWrap = activeEl.closest('.choices.is-open, .ts-wrapper.is-open');
            if (choicesWrap) return; // dropdown ka apna behaviour chalega

            e.preventDefault();

            // Kisi bhi input field se Enter press => new row append karo
            const addBtn = document.getElementById('addModalAddItemRow');
            if (addBtn) {
                addBtn.click();
                // Naye row ki pehli editable field pe focus
                setTimeout(function() {
                    const tbody = document.getElementById('addModalItemsBody');
                    const lastRow = tbody ? tbody.querySelector('.dr-item-row:last-child') : null;
                    if (lastRow) {
                        const firstSelect = lastRow.querySelector('.dr-item-select');
                        if (firstSelect && firstSelect.tomselect && firstSelect.tomselect.wrapper) {
                            firstSelect.tomselect.wrapper.querySelector('.choices__inner')?.click();
                        } else if (firstSelect) {
                            firstSelect.focus();
                        }
                    }
                }, 100);
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
        [facultySelect, academyStaffSelect, messStaffSelect, otStudentSelect, drCourseNameSelect].forEach(function(
            s) {
            if (s) setSelectVisible(s, false);
        });
        const isEmployee = (clientTypeRadio.value || '').toLowerCase() === 'employee';
        const isOt = (clientTypeRadio.value || '').toLowerCase() === 'ot';
        const isCourse = (clientTypeRadio.value || '').toLowerCase() === 'course';
        const opt = getSelectSelectedOption(clientNameSelect);
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
            nameInput.removeAttribute('list');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) {
                if (sel) {
                    setSelectVisible(sel, false);
                    if (sel.tomselect) sel.tomselect.clear();
                    else sel.value = '';
                    sel.removeAttribute('required');
                }
            });
            if (otStudentSelect) setSelectVisible(otStudentSelect, true);
            if (drCourseSelect) {
                setSelectVisible(drCourseSelect, false);
                if (drCourseSelect.tomselect) drCourseSelect.tomselect.clear();
                else drCourseSelect.value = '';
                drCourseSelect.removeAttribute('required');
            }
            if (drCourseNameSelect) {
                setSelectVisible(drCourseNameSelect, false);
                if (drCourseNameSelect.tomselect) drCourseNameSelect.tomselect.clear();
                else drCourseNameSelect.value = '';
                drCourseNameSelect.removeAttribute('required');
            }
        } else if (isCourse) {
            nameInput.style.display = 'block';
            nameInput.placeholder = 'Name';
            nameInput.setAttribute('required', 'required');
            nameInput.setAttribute('list', 'drCourseBuyerNames');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) {
                if (sel) {
                    setSelectVisible(sel, false);
                    if (sel.tomselect) sel.tomselect.clear();
                    else sel.value = '';
                    sel.removeAttribute('required');
                }
            });
            if (otStudentSelect) {
                setSelectVisible(otStudentSelect, false);
                if (otStudentSelect.tomselect) otStudentSelect.tomselect.clear();
                else otStudentSelect.value = '';
                otStudentSelect.removeAttribute('required');
            }
            if (drCourseSelect) setSelectVisible(drCourseSelect, true);
            if (drCourseNameSelect) {
                setSelectVisible(drCourseNameSelect, false);
                if (drCourseNameSelect.tomselect) drCourseNameSelect.tomselect.clear();
                else drCourseNameSelect.value = '';
                drCourseNameSelect.removeAttribute('required');
            }
        } else {
            nameInput.style.display = showAny ? 'none' : 'block';
            nameInput.removeAttribute('required');
            nameInput.setAttribute('list', 'drGenericBuyerNames');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) {
                if (!sel) return;
                const show = sel === facultySelect ? showFaculty : (sel === academyStaffSelect ?
                    showAcademyStaff : showMessStaff);
                setSelectVisible(sel, show);
                sel.removeAttribute('required');
                if (show) {
                    sel.setAttribute('required', 'required');
                    var nameVal = (nameInput.value || '').trim();
                    if (sel.tomselect) sel.tomselect.setValue(nameVal);
                    else sel.value = nameVal;
                    if (getSelectValue(sel)) nameInput.value = getSelectValue(sel);
                    if (nameVal && sel.tomselect) setTimeout(function() {
                        sel.tomselect.setValue(nameVal);
                    }, 0);
                } else {
                    if (sel.tomselect) sel.tomselect.clear();
                    else sel.value = '';
                }
            });
            if (otStudentSelect) {
                setSelectVisible(otStudentSelect, false);
                if (otStudentSelect.tomselect) otStudentSelect.tomselect.clear();
                else otStudentSelect.value = '';
                otStudentSelect.removeAttribute('required');
            }
            if (drCourseSelect) {
                setSelectVisible(drCourseSelect, false);
                if (drCourseSelect.tomselect) drCourseSelect.tomselect.clear();
                else drCourseSelect.value = '';
                drCourseSelect.removeAttribute('required');
            }
            if (drCourseNameSelect) {
                setSelectVisible(drCourseNameSelect, false);
                if (drCourseNameSelect.tomselect) drCourseNameSelect.tomselect.clear();
                else drCourseNameSelect.value = '';
                drCourseNameSelect.removeAttribute('required');
            }
            if (!showAny) nameInput.setAttribute('required', 'required');
        }
    }
    document.querySelectorAll('#addReportModal .dr-client-type-radio').forEach(function(radio) {
        radio.addEventListener('change', function() {
            var clientNameWrap = document.getElementById('drClientNameWrap');
            var nameFieldWrap = document.getElementById('drNameFieldWrap');
            if (clientNameWrap) clientNameWrap.style.display = '';
            if (nameFieldWrap) nameFieldWrap.style.display = '';

            const isOt = (this.value || '').toLowerCase() === 'ot';
            const isCourse = (this.value || '').toLowerCase() === 'course';
            const clientSelect = document.getElementById('drClientNameSelect');
            const otCourseSelect = document.getElementById('drOtCourseSelect');
            const otStudentSelect = document.getElementById('drOtStudentSelect');
            const drCourseSelect = document.getElementById('drCourseSelect');
            const drCourseNameSelect = document.getElementById('drCourseNameSelect');
            const nameInput = document.getElementById('drClientNameInput');
            if (isOt) {
                if (clientSelect) {
                    setSelectVisible(clientSelect, false);
                    clientSelect.removeAttribute('required');
                    if (clientSelect.tomselect) clientSelect.tomselect.clear();
                    else clientSelect.value = '';
                    clientSelect.removeAttribute('name');
                }
                if (otCourseSelect) {
                    setSelectVisible(otCourseSelect, true);
                    otCourseSelect.setAttribute('required', 'required');
                    otCourseSelect.setAttribute('name', 'client_type_pk');
                    if (otCourseSelect.tomselect) otCourseSelect.tomselect.clear();
                    else otCourseSelect.value = '';
                }
                if (otStudentSelect) {
                    setSelectVisible(otStudentSelect, true);
                    if (otStudentSelect.tomselect) {
                        try {
                            otStudentSelect.tomselect.destroy();
                        } catch (e) {}
                    }
                    otStudentSelect.innerHTML = '<option value="">Select course first</option>';
                    otStudentSelect.setAttribute('required', 'required');
                    if (typeof Choices !== 'undefined') createChoicesInstance(otStudentSelect, {
                        allowEmptyOption: true,
                        dropdownParent: 'body',
                        placeholder: 'Select Student'
                    });
                }
                if (drCourseSelect) {
                    setSelectVisible(drCourseSelect, false);
                    drCourseSelect.removeAttribute('required');
                    drCourseSelect.removeAttribute('name');
                    if (drCourseSelect.tomselect) drCourseSelect.tomselect.clear();
                    else drCourseSelect.value = '';
                }
                if (drCourseNameSelect) {
                    setSelectVisible(drCourseNameSelect, false);
                    drCourseNameSelect.removeAttribute('required');
                    if (drCourseNameSelect.tomselect) drCourseNameSelect.tomselect.clear();
                    else drCourseNameSelect.value = '';
                }
                if (nameInput) {
                    nameInput.style.display = 'none';
                    nameInput.value = '';
                    nameInput.removeAttribute('required');
                }
            } else if (isCourse) {
                if (clientSelect) {
                    setSelectVisible(clientSelect, false);
                    clientSelect.removeAttribute('required');
                    if (clientSelect.tomselect) clientSelect.tomselect.clear();
                    else clientSelect.value = '';
                    clientSelect.removeAttribute('name');
                }
                if (otCourseSelect) {
                    setSelectVisible(otCourseSelect, false);
                    otCourseSelect.removeAttribute('required');
                    otCourseSelect.removeAttribute('name');
                    if (otCourseSelect.tomselect) otCourseSelect.tomselect.clear();
                    else otCourseSelect.value = '';
                }
                if (otStudentSelect) {
                    setSelectVisible(otStudentSelect, false);
                    otStudentSelect.removeAttribute('required');
                    if (otStudentSelect.tomselect) {
                        try {
                            otStudentSelect.tomselect.destroy();
                        } catch (e) {}
                    }
                    otStudentSelect.innerHTML = '<option value="">Select Student</option>';
                    if (typeof Choices !== 'undefined') createChoicesInstance(otStudentSelect, {
                        allowEmptyOption: true,
                        dropdownParent: 'body',
                        placeholder: 'Select Student'
                    });
                }
                if (drCourseSelect) {
                    setSelectVisible(drCourseSelect, true);
                    drCourseSelect.setAttribute('required', 'required');
                    drCourseSelect.setAttribute('name', 'client_type_pk');
                    if (drCourseSelect.tomselect) drCourseSelect.tomselect.clear();
                    else drCourseSelect.value = '';
                }
                if (drCourseNameSelect) {
                    setSelectVisible(drCourseNameSelect, false);
                    drCourseNameSelect.removeAttribute('required');
                    if (drCourseNameSelect.tomselect) drCourseNameSelect.tomselect.clear();
                    else drCourseNameSelect.value = '';
                }
                if (nameInput) {
                    nameInput.style.display = 'block';
                    nameInput.value = '';
                    nameInput.placeholder = 'Course name';
                    nameInput.setAttribute('required', 'required');
                }
            } else {
                if (clientSelect) {
                    setSelectVisible(clientSelect, true);
                    clientSelect.setAttribute('required', 'required');
                    clientSelect.setAttribute('name', 'client_type_pk');
                    rebuildClientNameSelect(clientSelect, clientNameOptionsAdd, this.value);
                }
                if (otCourseSelect) {
                    setSelectVisible(otCourseSelect, false);
                    otCourseSelect.removeAttribute('required');
                    otCourseSelect.removeAttribute('name');
                    if (otCourseSelect.tomselect) otCourseSelect.tomselect.clear();
                    else otCourseSelect.value = '';
                }
                if (otStudentSelect) {
                    setSelectVisible(otStudentSelect, false);
                    otStudentSelect.removeAttribute('required');
                    if (otStudentSelect.tomselect) {
                        try {
                            otStudentSelect.tomselect.destroy();
                        } catch (e) {}
                    }
                    otStudentSelect.innerHTML = '<option value="">Select Student</option>';
                    if (typeof Choices !== 'undefined') createChoicesInstance(otStudentSelect, {
                        allowEmptyOption: true,
                        dropdownParent: 'body',
                        placeholder: 'Select Student'
                    });
                }
                if (drCourseSelect) {
                    setSelectVisible(drCourseSelect, false);
                    drCourseSelect.removeAttribute('required');
                    if (drCourseSelect.tomselect) drCourseSelect.tomselect.clear();
                    else drCourseSelect.value = '';
                }
                if (drCourseNameSelect) {
                    setSelectVisible(drCourseNameSelect, false);
                    drCourseNameSelect.removeAttribute('required');
                    if (drCourseNameSelect.tomselect) drCourseNameSelect.tomselect.clear();
                    else drCourseNameSelect.value = '';
                }
                if (nameInput) {
                    nameInput.style.display = 'block';
                    nameInput.placeholder = 'Client / section / role name';
                    nameInput.setAttribute('required', 'required');
                }
            }
            updateDrNameField();
        });
    });

    function loadAddDrBuyerNames() {
        const clientTypeRadio = document.querySelector('#addReportModal .dr-client-type-radio:checked');
        const clientNameSelect = document.getElementById('drClientNameSelect');
        const drCourseSelect = document.getElementById('drCourseSelect');
        const nameInput = document.getElementById('drClientNameInput');
        const courseDl = document.getElementById('drCourseBuyerNames');
        const genericDl = document.getElementById('drGenericBuyerNames');
        if (!clientTypeRadio || !nameInput || !courseDl || !genericDl) return;

        const slug = (clientTypeRadio.value || '').toLowerCase();
        let pk = '';
        let targetDl = null;

        if (slug === 'course') {
            pk = drCourseSelect ? getSelectValue(drCourseSelect) : '';
            targetDl = courseDl;
            nameInput.setAttribute('list', 'drCourseBuyerNames');
            genericDl.innerHTML = '';
        } else if (slug === 'section' || slug === 'other') {
            pk = clientNameSelect ? getSelectValue(clientNameSelect) : '';
            targetDl = genericDl;
            nameInput.setAttribute('list', 'drGenericBuyerNames');
            courseDl.innerHTML = '';
        } else {
            nameInput.removeAttribute('list');
            courseDl.innerHTML = '';
            genericDl.innerHTML = '';
            return;
        }

        targetDl.innerHTML = '';
        if (!pk) return;

        fetch(baseUrl + '/buyer-names?client_type_slug=' + encodeURIComponent(slug) + '&client_type_pk=' +
                encodeURIComponent(pk), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
            .then(r => r.json())
            .then(function(data) {
                targetDl.innerHTML = '';
                (data.buyers || []).forEach(function(b) {
                    const opt = document.createElement('option');
                    opt.value = b;
                    targetDl.appendChild(opt);
                });
            })
            .catch(function() {
                targetDl.innerHTML = '';
            });
    }
    document.getElementById('drOtCourseSelect').addEventListener('change', function() {
        const coursePk = getSelectValue(this);
        const otStudentSelect = document.getElementById('drOtStudentSelect');
        const nameInput = document.getElementById('drClientNameInput');
        if (!otStudentSelect || !nameInput) return;
        if (otStudentSelect.tomselect) {
            try {
                otStudentSelect.tomselect.destroy();
            } catch (e) {}
        }
        otStudentSelect.innerHTML = '<option value="">Loading...</option>';
        const selectedOpt = getSelectSelectedOption(this);
        nameInput.value = (selectedOpt && selectedOpt.dataset.courseName) ? selectedOpt.dataset.courseName :
            '';
        if (!coursePk) {
            otStudentSelect.innerHTML = '<option value="">Select course first</option>';
            if (typeof Choices !== 'undefined') createChoicesInstance(otStudentSelect, {
                allowEmptyOption: true,
                dropdownParent: 'body',
                placeholder: 'Select Student'
            });
            return;
        }
        fetch(baseUrl + '/students-by-course/' + coursePk, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(function(data) {
                otStudentSelect.innerHTML = '<option value="">Select Student</option>';
                (data.students || []).forEach(function(s) {
                    const opt = document.createElement('option');
                    opt.value = s.display_name || '';
                    opt.textContent = s.display_name || '—';
                    otStudentSelect.appendChild(opt);
                });
                if (typeof Choices !== 'undefined') createChoicesInstance(otStudentSelect, {
                    allowEmptyOption: true,
                    dropdownParent: 'body',
                    placeholder: 'Select Student'
                });
            })
            .catch(function() {
                otStudentSelect.innerHTML = '<option value="">Error loading students</option>';
                if (typeof Choices !== 'undefined') createChoicesInstance(otStudentSelect, {
                    allowEmptyOption: true,
                    dropdownParent: 'body',
                    placeholder: 'Select Student'
                });
            });
    });
    document.getElementById('drOtStudentSelect').addEventListener('change', function() {
        const inp = document.getElementById('drClientNameInput');
        if (inp) inp.value = getSelectValue(this) || '';
    });
    document.getElementById('drCourseSelect').addEventListener('change', function() {
        loadAddDrBuyerNames();
    });
    document.getElementById('drClientNameSelect').addEventListener('change', function() {
        updateDrNameField();
        loadAddDrBuyerNames();
    });
    document.getElementById('drFacultySelect').addEventListener('change', function() {
        const inp = document.getElementById('drClientNameInput');
        if (inp) inp.value = getSelectValue(this) || '';
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
    loadAddDrBuyerNames();

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
        [facultySelect, academyStaffSelect, messStaffSelect, document.getElementById('editDrOtStudentSelect'),
            editDrCourseNameSelect
        ].forEach(function(s) {
            if (s) setSelectVisible(s, false);
        });
        const isEmployee = (clientTypeRadio.value || '').toLowerCase() === 'employee';
        const isOt = (clientTypeRadio.value || '').toLowerCase() === 'ot';
        const isCourse = (clientTypeRadio.value || '').toLowerCase() === 'course';
        const opt = getSelectSelectedOption(clientNameSelect);
        const clientNameVal = (opt && opt.dataset.clientName) ? opt.dataset.clientName : '';
        const isFaculty = clientNameVal === 'faculty';
        const isAcademyStaff = clientNameVal === 'academy staff';
        const isMessStaff = clientNameVal === 'mess staff';
        const showFaculty = isEmployee && isFaculty;
        const showAcademyStaff = isEmployee && isAcademyStaff;
        const showMessStaff = isEmployee && isMessStaff;
        const showAny = showFaculty || showAcademyStaff || showMessStaff;
        if (isOt) {
            nameInput.style.display = 'block';
            nameInput.readOnly = true;
            nameInput.placeholder = 'Buyer name (OT)';
            nameInput.removeAttribute('required');
            nameInput.removeAttribute('list');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) {
                if (sel) {
                    setSelectVisible(sel, false);
                    if (sel.tomselect) sel.tomselect.clear();
                    else sel.value = '';
                    sel.removeAttribute('required');
                }
            });
            var editOtStu2 = document.getElementById('editDrOtStudentSelect');
            if (editOtStu2) {
                setSelectVisible(editOtStu2, false);
                editOtStu2.removeAttribute('required');
            }
            if (editDrCourseSelect) {
                setSelectVisible(editDrCourseSelect, false);
                if (editDrCourseSelect.tomselect) editDrCourseSelect.tomselect.clear();
                else editDrCourseSelect.value = '';
                editDrCourseSelect.removeAttribute('required');
            }
            if (editDrCourseNameSelect) {
                setSelectVisible(editDrCourseNameSelect, false);
                if (editDrCourseNameSelect.tomselect) editDrCourseNameSelect.tomselect.clear();
                else editDrCourseNameSelect.value = '';
                editDrCourseNameSelect.removeAttribute('required');
            }
        } else if (isCourse) {
            nameInput.style.display = 'block';
            nameInput.placeholder = 'Name';
            nameInput.setAttribute('required', 'required');
            nameInput.setAttribute('list', 'editDrCourseBuyerNames');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) {
                if (sel) {
                    setSelectVisible(sel, false);
                    if (sel.tomselect) sel.tomselect.clear();
                    else sel.value = '';
                    sel.removeAttribute('required');
                }
            });
            if (editDrCourseSelect) setSelectVisible(editDrCourseSelect, true);
            if (editDrCourseNameSelect) {
                setSelectVisible(editDrCourseNameSelect, false);
                if (editDrCourseNameSelect.tomselect) editDrCourseNameSelect.tomselect.clear();
                else editDrCourseNameSelect.value = '';
                editDrCourseNameSelect.removeAttribute('required');
            }
        } else {
            nameInput.style.display = showAny ? 'none' : 'block';
            nameInput.removeAttribute('required');
            nameInput.setAttribute('list', 'editDrGenericBuyerNames');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) {
                if (!sel) return;
                const show = sel === facultySelect ? showFaculty : (sel === academyStaffSelect ?
                    showAcademyStaff : showMessStaff);
                setSelectVisible(sel, show);
                sel.removeAttribute('required');
                if (show) {
                    sel.setAttribute('required', 'required');
                    var nameVal = (nameInput.value || '').trim();
                    if (sel.tomselect) sel.tomselect.setValue(nameVal);
                    else sel.value = nameVal;
                    if (getSelectValue(sel)) nameInput.value = getSelectValue(sel);
                    if (nameVal && sel.tomselect) setTimeout(function() {
                        sel.tomselect.setValue(nameVal);
                    }, 0);
                } else {
                    if (sel.tomselect) sel.tomselect.clear();
                    else sel.value = '';
                }
            });
            if (editDrCourseSelect) {
                setSelectVisible(editDrCourseSelect, false);
                if (editDrCourseSelect.tomselect) editDrCourseSelect.tomselect.clear();
                else editDrCourseSelect.value = '';
                editDrCourseSelect.removeAttribute('required');
            }
            if (editDrCourseNameSelect) {
                setSelectVisible(editDrCourseNameSelect, false);
                if (editDrCourseNameSelect.tomselect) editDrCourseNameSelect.tomselect.clear();
                else editDrCourseNameSelect.value = '';
                editDrCourseNameSelect.removeAttribute('required');
            }
            if (!showAny) nameInput.setAttribute('required', 'required');
        }
    }

    function loadEditDrBuyerNames() {
        const clientTypeRadio = document.querySelector('#editReportModal .edit-dr-client-type-radio:checked');
        const clientNameSelect = document.getElementById('editDrClientNameSelect');
        const drCourseSelect = document.getElementById('editDrCourseSelect');
        const nameInput = document.getElementById('editDrClientNameInput');
        const courseDl = document.getElementById('editDrCourseBuyerNames');
        const genericDl = document.getElementById('editDrGenericBuyerNames');
        if (!clientTypeRadio || !nameInput || !courseDl || !genericDl) return;

        const slug = (clientTypeRadio.value || '').toLowerCase();
        let pk = '';
        let targetDl = null;

        if (slug === 'course') {
            pk = drCourseSelect ? getSelectValue(drCourseSelect) : '';
            targetDl = courseDl;
            nameInput.setAttribute('list', 'editDrCourseBuyerNames');
            genericDl.innerHTML = '';
        } else if (slug === 'section' || slug === 'other') {
            pk = clientNameSelect ? getSelectValue(clientNameSelect) : '';
            targetDl = genericDl;
            nameInput.setAttribute('list', 'editDrGenericBuyerNames');
            courseDl.innerHTML = '';
        } else {
            nameInput.removeAttribute('list');
            courseDl.innerHTML = '';
            genericDl.innerHTML = '';
            return;
        }

        targetDl.innerHTML = '';
        if (!pk) return;

        fetch(baseUrl + '/buyer-names?client_type_slug=' + encodeURIComponent(slug) + '&client_type_pk=' +
                encodeURIComponent(pk), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
            .then(r => r.json())
            .then(function(data) {
                targetDl.innerHTML = '';
                (data.buyers || []).forEach(function(b) {
                    const opt = document.createElement('option');
                    opt.value = b;
                    targetDl.appendChild(opt);
                });
            })
            .catch(function() {
                targetDl.innerHTML = '';
            });
    }
    document.querySelectorAll('#editReportModal .edit-dr-client-type-radio').forEach(function(radio) {
        radio.addEventListener('change', function() {
            var editClientNameWrap = document.getElementById('editDrClientNameWrap');
            var editNameFieldWrap = document.getElementById('editDrNameFieldWrap');
            if (editClientNameWrap) editClientNameWrap.style.display = '';
            if (editNameFieldWrap) editNameFieldWrap.style.display = '';

            const isOt = (this.value || '').toLowerCase() === 'ot';
            const isCourse = (this.value || '').toLowerCase() === 'course';
            const clientSelect = document.getElementById('editDrClientNameSelect');
            const otCourseSelect = document.getElementById('editDrOtCourseSelect');
            const otStudentSelect = document.getElementById('editDrOtStudentSelect');
            const editDrCourseSelect = document.getElementById('editDrCourseSelect');
            const editDrCourseNameSelect = document.getElementById('editDrCourseNameSelect');
            const nameInput = document.getElementById('editDrClientNameInput');
            if (isOt) {
                if (clientSelect) {
                    setSelectVisible(clientSelect, false);
                    clientSelect.removeAttribute('required');
                    if (clientSelect.tomselect) clientSelect.tomselect.clear();
                    else clientSelect.value = '';
                    clientSelect.removeAttribute('name');
                }
                if (otCourseSelect) {
                    setSelectVisible(otCourseSelect, true);
                    otCourseSelect.setAttribute('required', 'required');
                    otCourseSelect.setAttribute('name', 'client_type_pk');
                    if (otCourseSelect.tomselect) otCourseSelect.tomselect.clear();
                    else otCourseSelect.value = '';
                }
                if (otStudentSelect) {
                    setSelectVisible(otStudentSelect, true);
                    if (otStudentSelect.tomselect) {
                        try {
                            otStudentSelect.tomselect.destroy();
                        } catch (e) {}
                    }
                    otStudentSelect.innerHTML = '<option value="">Select course first</option>';
                    otStudentSelect.setAttribute('required', 'required');
                    if (typeof Choices !== 'undefined') createChoicesInstance(otStudentSelect, {
                        allowEmptyOption: true,
                        dropdownParent: 'body',
                        placeholder: 'Select Student'
                    });
                }
                if (editDrCourseSelect) {
                    setSelectVisible(editDrCourseSelect, false);
                    editDrCourseSelect.removeAttribute('required');
                    editDrCourseSelect.removeAttribute('name');
                    if (editDrCourseSelect.tomselect) editDrCourseSelect.tomselect.clear();
                    else editDrCourseSelect.value = '';
                }
                if (editDrCourseNameSelect) {
                    setSelectVisible(editDrCourseNameSelect, false);
                    editDrCourseNameSelect.removeAttribute('required');
                    if (editDrCourseNameSelect.tomselect) editDrCourseNameSelect.tomselect.clear();
                    else editDrCourseNameSelect.value = '';
                }
                if (nameInput) {
                    nameInput.style.display = 'none';
                    nameInput.value = '';
                    nameInput.removeAttribute('required');
                }
            } else if (isCourse) {
                if (clientSelect) {
                    setSelectVisible(clientSelect, false);
                    clientSelect.removeAttribute('required');
                    if (clientSelect.tomselect) clientSelect.tomselect.clear();
                    else clientSelect.value = '';
                    clientSelect.removeAttribute('name');
                }
                if (otCourseSelect) {
                    setSelectVisible(otCourseSelect, false);
                    otCourseSelect.removeAttribute('required');
                    otCourseSelect.removeAttribute('name');
                    if (otCourseSelect.tomselect) otCourseSelect.tomselect.clear();
                    else otCourseSelect.value = '';
                }
                if (otStudentSelect) {
                    setSelectVisible(otStudentSelect, false);
                    otStudentSelect.removeAttribute('required');
                    if (otStudentSelect.tomselect) {
                        try {
                            otStudentSelect.tomselect.destroy();
                        } catch (e) {}
                    }
                    otStudentSelect.innerHTML = '<option value="">Select Student</option>';
                    if (typeof Choices !== 'undefined') createChoicesInstance(otStudentSelect, {
                        allowEmptyOption: true,
                        dropdownParent: 'body',
                        placeholder: 'Select Student'
                    });
                }
                if (editDrCourseSelect) {
                    setSelectVisible(editDrCourseSelect, true);
                    editDrCourseSelect.setAttribute('required', 'required');
                    editDrCourseSelect.setAttribute('name', 'client_type_pk');
                    if (editDrCourseSelect.tomselect) editDrCourseSelect.tomselect.clear();
                    else editDrCourseSelect.value = '';
                }
                if (editDrCourseNameSelect) {
                    setSelectVisible(editDrCourseNameSelect, false);
                    editDrCourseNameSelect.removeAttribute('required');
                    if (editDrCourseNameSelect.tomselect) editDrCourseNameSelect.tomselect.clear();
                    else editDrCourseNameSelect.value = '';
                }
                if (nameInput) {
                    nameInput.style.display = 'block';
                    nameInput.value = '';
                    nameInput.placeholder = 'Course name';
                    nameInput.setAttribute('required', 'required');
                }
            } else {
                if (clientSelect) {
                    setSelectVisible(clientSelect, true);
                    clientSelect.setAttribute('required', 'required');
                    clientSelect.setAttribute('name', 'client_type_pk');
                    rebuildEditClientNameSelect(this.value);
                }
                if (otCourseSelect) {
                    setSelectVisible(otCourseSelect, false);
                    otCourseSelect.removeAttribute('required');
                    otCourseSelect.removeAttribute('name');
                    if (otCourseSelect.tomselect) otCourseSelect.tomselect.clear();
                    else otCourseSelect.value = '';
                }
                if (otStudentSelect) {
                    setSelectVisible(otStudentSelect, false);
                    otStudentSelect.removeAttribute('required');
                    if (otStudentSelect.tomselect) {
                        try {
                            otStudentSelect.tomselect.destroy();
                        } catch (e) {}
                    }
                    otStudentSelect.innerHTML = '<option value="">Select Student</option>';
                    if (typeof Choices !== 'undefined') createChoicesInstance(otStudentSelect, {
                        allowEmptyOption: true,
                        dropdownParent: 'body',
                        placeholder: 'Select Student'
                    });
                }
                if (editDrCourseSelect) {
                    setSelectVisible(editDrCourseSelect, false);
                    editDrCourseSelect.removeAttribute('required');
                    editDrCourseSelect.removeAttribute('name');
                    if (editDrCourseSelect.tomselect) editDrCourseSelect.tomselect.clear();
                    else editDrCourseSelect.value = '';
                }
                if (editDrCourseNameSelect) {
                    setSelectVisible(editDrCourseNameSelect, false);
                    editDrCourseNameSelect.removeAttribute('required');
                    if (editDrCourseNameSelect.tomselect) editDrCourseNameSelect.tomselect.clear();
                    else editDrCourseNameSelect.value = '';
                }
                if (nameInput) {
                    nameInput.style.display = 'block';
                    nameInput.placeholder = 'Client / section / role name';
                    nameInput.setAttribute('required', 'required');
                }
            }
            updateEditDrNameField();
            loadEditDrBuyerNames();
        });
    });
    document.getElementById('editDrOtCourseSelect').addEventListener('change', function() {
        const coursePk = getSelectValue(this);
        const otStudentSelect = document.getElementById('editDrOtStudentSelect');
        const nameInput = document.getElementById('editDrClientNameInput');
        if (!otStudentSelect || !nameInput) return;
        if (otStudentSelect.tomselect) {
            try {
                otStudentSelect.tomselect.destroy();
            } catch (e) {}
        }
        otStudentSelect.innerHTML = '<option value="">Loading...</option>';
        const selectedOpt = getSelectSelectedOption(this);
        nameInput.value = (selectedOpt && selectedOpt.dataset.courseName) ? selectedOpt.dataset.courseName :
            '';
        if (!coursePk) {
            otStudentSelect.innerHTML = '<option value="">Select course first</option>';
            if (typeof Choices !== 'undefined') createChoicesInstance(otStudentSelect, {
                allowEmptyOption: true,
                dropdownParent: 'body',
                placeholder: 'Select Student'
            });
            return;
        }
        fetch(baseUrl + '/students-by-course/' + coursePk, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(function(data) {
                otStudentSelect.innerHTML = '<option value="">Select Student</option>';
                (data.students || []).forEach(function(s) {
                    const opt = document.createElement('option');
                    opt.value = s.display_name || '';
                    opt.textContent = s.display_name || '—';
                    otStudentSelect.appendChild(opt);
                });
                if (typeof Choices !== 'undefined') createChoicesInstance(otStudentSelect, {
                    allowEmptyOption: true,
                    dropdownParent: 'body',
                    placeholder: 'Select Student'
                });
            })
            .catch(function() {
                otStudentSelect.innerHTML = '<option value="">Error loading students</option>';
                if (typeof Choices !== 'undefined') createChoicesInstance(otStudentSelect, {
                    allowEmptyOption: true,
                    dropdownParent: 'body',
                    placeholder: 'Select Student'
                });
            });
    });
    document.getElementById('editDrOtStudentSelect').addEventListener('change', function() {
        const inp = document.getElementById('editDrClientNameInput');
        if (inp) inp.value = getSelectValue(this) || '';
    });
    document.getElementById('editDrCourseSelect').addEventListener('change', function() {
        loadEditDrBuyerNames();
    });
    document.getElementById('editDrClientNameSelect').addEventListener('change', function() {
        updateEditDrNameField();
        loadEditDrBuyerNames();
    });
    document.getElementById('editDrFacultySelect').addEventListener('change', function() {
        const inp = document.getElementById('editDrClientNameInput');
        if (inp) inp.value = getSelectValue(this) || '';
    });
    const editDrAcademyStaffEl = document.getElementById('editDrAcademyStaffSelect');
    if (editDrAcademyStaffEl) editDrAcademyStaffEl.addEventListener('change', function() {
        const inp = document.getElementById('editDrClientNameInput');
        if (inp) inp.value = getSelectValue(this) || '';
    });
    const editDrMessStaffEl = document.getElementById('editDrMessStaffSelect');
    if (editDrMessStaffEl) editDrMessStaffEl.addEventListener('change', function() {
        const inp = document.getElementById('editDrClientNameInput');
        if (inp) inp.value = getSelectValue(this) || '';
    });

    // Edit modal row helpers
    function getEditRowHtml(index, item) {
        item = item || {};
        const sourceItems = Array.isArray(filteredItems) && filteredItems.length > 0 ? filteredItems :
            itemSubcategories;
        const options = sourceItems.map(s => {
            let attrs = 'data-unit="' + (s.unit_measurement || '').replace(/"/g, '&quot;') +
                '" data-rate="' + (s.standard_cost || 0) + '" data-available="' + (s.available_quantity ||
                    0) + '"';
            if (s.price_tiers && s.price_tiers.length > 0) {
                attrs += ' data-price-tiers="' + (JSON.stringify(s.price_tiers) || '').replace(/"/g,
                    '&quot;') + '"';
            }
            return '<option value="' + s.id + '" ' + attrs + (item.item_subcategory_id == s.id ?
                ' selected' : '') + '>' + (s.item_name || '—').replace(/</g, '&lt;') + '</option>';
        }).join('');
        const avail = item.available_quantity != null ? item.available_quantity : '';
        const qty = item.quantity != null ? item.quantity : '';
        const rate = item.rate != null ? item.rate : '';
        const issueDate = item.issue_date || '';
        const total = (qty && rate) ? (parseFloat(qty) * parseFloat(rate)).toFixed(2) : '';
        const left = (avail !== '' && qty !== '') ? Math.max(0, parseFloat(avail) - parseFloat(qty)).toFixed(2) :
            '';
        const originalQtyAttr = (item.quantity != null && item.quantity !== '') ? (' data-original-qty="' + (
            parseFloat(item.quantity) || 0) + '"') : '';
        return '<tr class="edit-dr-item-row"' + originalQtyAttr + '>' +
            '<td><select name="items[' + index +
            '][item_subcategory_id]" class="form-select  edit-dr-item-select" required><option value="">Select Item</option>' +
            options + '</select></td>' +
            '<td><input type="text" name="items[' + index +
            '][unit]" class="form-control  edit-dr-unit" readonly placeholder="—" value="' + (item.unit || '')
            .replace(/"/g, '&quot;') + '"></td>' +
            '<td><input type="text" name="items[' + index +
            '][available_quantity]" class="form-control  edit-dr-avail bg-light" value="' + avail +
            '" readonly></td>' +
            '<td><input type="text" name="items[' + index +
            '][quantity]" class="form-control  edit-dr-qty" required value="' + qty +
            '"><div class="invalid-feedback">Issue Qty cannot exceed Available Qty.</div></td>' +
            '<td><input type="text" class="form-control  edit-dr-left bg-light" readonly value="' + left +
            '"></td>' +
            '<td><input type="date" name="items[' + index +
            '][issue_date]" class="form-control  edit-dr-issue-date" value="' + issueDate + '"></td>' +
            '<td><input type="text" name="items[' + index +
            '][rate]" class="form-control  edit-dr-rate" required value="' + rate + '"></td>' +
            '<td><input type="text" class="form-control  edit-dr-total bg-light" readonly value="' + total +
            '"></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger edit-dr-remove-row voucher-icon-btn" title="Remove">×</button></td>' +
            '</tr>';
    }

    function updateEditRowLeft(row) {
        const avail = parseFloat(row.querySelector('.edit-dr-avail').value) || 0;
        const qty = parseFloat(row.querySelector('.edit-dr-qty').value) || 0;
        const leftInp = row.querySelector('.edit-dr-left');
        if (leftInp) leftInp.value = Math.max(0, avail - qty).toFixed(2);
    }

    /**
     * Recalculate Available Qty and Left Qty for all rows in the Edit modal (Selling Voucher with Date Range).
     * Effective base per item = current stock + sum of original qtys (from this voucher) for that item.
     * Then each row gets available = base - already used in previous rows (same logic as Add mode).
     */
    function refreshEditAllAvailable() {
        const rows = document.querySelectorAll('#editModalItemsBody .edit-dr-item-row');
        if (!rows.length) return;

        const effectiveBaseByItem = {};
        rows.forEach(function(row) {
            const select = row.querySelector('.edit-dr-item-select');
            const itemId = select ? getSelectValue(select) : '';
            if (!itemId) return;
            const originalQty = parseFloat(row.getAttribute('data-original-qty')) || 0;
            if (!effectiveBaseByItem.hasOwnProperty(itemId)) {
                effectiveBaseByItem[itemId] = getBaseAvailableForItem(itemId);
            }
            effectiveBaseByItem[itemId] += originalQty;
        });

        const usedByItem = {};
        rows.forEach(function(row) {
            const select = row.querySelector('.edit-dr-item-select');
            const itemId = select ? getSelectValue(select) : '';
            const availInp = row.querySelector('.edit-dr-avail');
            const leftInp = row.querySelector('.edit-dr-left');
            if (!itemId || !availInp) return;

            const effectiveBase = effectiveBaseByItem[itemId] != null ? effectiveBaseByItem[itemId] :
                getBaseAvailableForItem(itemId);
            const alreadyUsed = usedByItem[itemId] || 0;
            const availableForRow = Math.max(0, effectiveBase - alreadyUsed);

            availInp.value = availableForRow.toFixed(2);

            const qty = parseFloat(row.querySelector('.edit-dr-qty').value) || 0;
            if (leftInp) {
                leftInp.value = Math.max(0, availableForRow - qty).toFixed(2);
            }

            usedByItem[itemId] = alreadyUsed + qty;
            enforceQtyWithinAvailable(row, '.edit-dr-avail', '.edit-dr-qty');
        });
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
        if (sel && typeof Choices !== 'undefined') createChoicesInstance(sel, createItemSelectConfig());
        const opt = getSelectSelectedOption(sel);
        newTr.querySelector('.edit-dr-unit').value = (opt && opt.dataset.unit) ? opt.dataset.unit : '—';
        const initAvailInp = newTr.querySelector('.edit-dr-avail');
        if (initAvailInp && opt && opt.dataset.available) {
            initAvailInp.value = opt.dataset.available;
        }
        refreshEditAllAvailable();
        newTr.querySelector('.edit-dr-avail').addEventListener('input', function() {
            updateEditRowLeft(newTr);
        });
        newTr.querySelector('.edit-dr-qty').addEventListener('input', function() {
            refreshEditAllAvailable();
            updateEditRowTotal(newTr);
            updateEditGrandTotal();
        });
        newTr.querySelector('.edit-dr-rate').addEventListener('input', function() {
            updateEditRowTotal(newTr);
            updateEditGrandTotal();
        });
        newTr.querySelector('.edit-dr-item-select').addEventListener('change', function() {
            const o = getSelectSelectedOption(this);
            newTr.querySelector('.edit-dr-unit').value = (o && o.dataset.unit) ? o.dataset.unit :
                '—';
            const rateInp = newTr.querySelector('.edit-dr-rate');
            if (rateInp && o && o.dataset.rate) rateInp.value = o.dataset.rate;
            const availInp = newTr.querySelector('.edit-dr-avail');
            if (availInp && o && o.dataset.available) {
                availInp.value = o.dataset.available;
            }
            refreshEditAllAvailable();
            updateEditRowTotal(newTr);
            updateEditGrandTotal();
        });
        newTr.querySelector('.edit-dr-remove-row').addEventListener('click', function() {
            newTr.remove();
            refreshEditAllAvailable();
            updateEditGrandTotal();
        });
    });

    document.getElementById('editModalItemsBody').addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-dr-remove-row')) {
            const row = e.target.closest('tr');
            if (row) {
                row.remove();
                refreshEditAllAvailable();
                updateEditGrandTotal();
            }
        }
    });

    // Edit modal keyboard flow:
    // - Dropdowns: Enter behaves like Tab (next focus)
    // - Last row Rate: Enter appends a new row
    // - Other fields: block Enter to avoid accidental submit
    const editReportFormKeydownEl = document.getElementById('editReportForm');
    const editModalItemsBodyEl = document.getElementById('editModalItemsBody');
    if (editReportFormKeydownEl && editModalItemsBodyEl) {
        function getNextEditFocusable(currentEl) {
            const modalEl = document.getElementById('editReportModal');
            if (!modalEl) return null;
            const focusable = Array.from(modalEl.querySelectorAll('input, select, textarea, button, [tabindex]'))
                .filter(function(el) {
                    if (el.disabled) return false;
                    if (el.getAttribute('tabindex') === '-1') return false;
                    if (el.type === 'hidden') return false;
                    if (el.offsetParent === null) return false;
                    return true;
                });
            const currentIndex = focusable.indexOf(currentEl);
            return currentIndex >= 0 && currentIndex < focusable.length - 1 ? focusable[currentIndex + 1] : null;
        }

        function focusNextFromDropdown(activeEl) {
            const wrapper = activeEl && activeEl.closest ? activeEl.closest('.ts-wrapper') : null;
            if (wrapper) {
                const nextEl = getNextEditFocusable(wrapper);
                if (nextEl && typeof nextEl.focus === 'function') nextEl.focus();
                return true;
            }
            if (activeEl && activeEl.matches && activeEl.matches('select')) {
                const nextEl = getNextEditFocusable(activeEl);
                if (nextEl && typeof nextEl.focus === 'function') nextEl.focus();
                return true;
            }
            return false;
        }

        editReportFormKeydownEl.addEventListener('keydown', function(e) {
            if (e.key !== 'Enter') return;
            const activeEl = document.activeElement;
            if (!activeEl || !editReportFormKeydownEl.contains(activeEl)) return;

            // Agar Choices.js dropdown open hai, toh Enter ko normal rehne do
            var choicesWrap = activeEl.closest('.choices.is-open, .ts-wrapper.is-open');
            if (choicesWrap) return;

            // Item table ke andar kisi bhi field se Enter => new row append
            const row = activeEl.closest('.edit-dr-item-row');
            if (row) {
                e.preventDefault();
                const addBtn = document.getElementById('editModalAddItemRow');
                if (addBtn) {
                    addBtn.click();
                    setTimeout(function() {
                        const lastRow = editModalItemsBodyEl.querySelector('.edit-dr-item-row:last-child');
                        if (lastRow) {
                            const firstSelect = lastRow.querySelector('.edit-dr-item-select');
                            if (firstSelect && firstSelect.tomselect && firstSelect.tomselect.wrapper) {
                                var inner = firstSelect.tomselect.wrapper.querySelector('.choices__inner');
                                if (inner) inner.click();
                            } else if (firstSelect) {
                                firstSelect.focus();
                            }
                        }
                    }, 100);
                }
                return;
            }

            if (focusNextFromDropdown(activeEl)) {
                e.preventDefault();
                return;
            }

            e.preventDefault();
        }, true);
    }

    // View report (mousedown ensures single-tap works with DataTables)
    document.addEventListener('mousedown', function(e) {
        const btn = e.target.closest('.btn-view-report');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        const reportId = btn.getAttribute('data-report-id');
        fetch(baseUrl + '/' + reportId, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(function(data) {
                const v = data.voucher;
                document.getElementById('viewReportModalLabel').textContent =
                    'View Selling Voucher with Date Range #' + (v.id || reportId);
                document.getElementById('viewRequestDate').textContent = v.request_date || '—';
                document.getElementById('viewStoreName').textContent = v.store_name || '—';
                document.getElementById('viewReferenceNumber').textContent = v.reference_number || '—';
                document.getElementById('viewOrderBy').textContent = v.order_by || '—';
                document.getElementById('viewClientType').textContent = v.client_type || '—';
                document.getElementById('viewClientName').textContent = (v.client_name_text || v
                    .client_name || '—');
                document.getElementById('viewPaymentType').textContent = v.payment_type || '—';
                const statusEl = document.getElementById('viewStatus');
                statusEl.innerHTML = v.status === 0 ?
                    '<span class="badge rounded-1 text-bg-warning">Pending</span>' : (v.status === 2 ?
                        '<span class="badge rounded-1 text-bg-success">Approved</span>' : (v.status ===
                            4 ? '<span class="badge rounded-1 text-bg-primary">Completed</span>' :
                            '<span class="badge rounded-1 text-bg-secondary">' + (v.status_label || v
                                .status) + '</span>'));
                if (v.remarks) {
                    document.getElementById('viewRemarksWrap').style.display = 'block';
                    document.getElementById('viewRemarks').textContent = v.remarks;
                } else {
                    document.getElementById('viewRemarksWrap').style.display = 'none';
                }
                // Bill display removed; keep view logic resilient if elements are absent
                const tbody = document.getElementById('viewReportItemsBody');
                tbody.innerHTML = '';
                if (data.has_items && data.items && data.items.length > 0) {
                    data.items.forEach(function(item) {
                        tbody.insertAdjacentHTML('beforeend', '<tr><td>' + (item.item_name ||
                                '—') + '</td><td>' + (item.unit || '—') + '</td><td>' + item
                            .quantity + '</td><td>' + (item.return_quantity || 0) +
                            '</td><td>₹' + item.rate + '</td><td>₹' + item.amount +
                            '</td><td>' + (item.issue_date || '—') + '</td></tr>');
                    });
                    document.getElementById('viewReportGrandTotal').textContent = data.grand_total ||
                        '0.00';
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
            .catch(err => {
                console.error(err);
                alert('Failed to load report.');
            });
    }, true);

    // Return item modal (mousedown ensures single-tap works with DataTables)
    document.addEventListener('mousedown', function(e) {
        const btn = e.target.closest('.btn-return-report');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        const reportId = btn.getAttribute('data-report-id');
        fetch(baseUrl + '/' + reportId + '/return', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(function(data) {
                document.getElementById('returnTransferFromStore').textContent = data.store_name || '—';
                const issueDate = data.issue_date || '';
                const todayYmd = new Date().toISOString().slice(0, 10);
                const tbody = document.getElementById('returnItemModalBody');
                tbody.innerHTML = '';
                function ymdToDmY(ymd) {
                    if (!ymd) return '—';
                    var p = String(ymd).split('-');
                    if (p.length !== 3) return ymd;
                    return p[2] + '/' + p[1] + '/' + p[0];
                }
                (data.items || []).forEach(function(item, i) {
                    const id = (item.id != null) ? item.id : '';
                    const name = (item.item_name || '—').replace(/</g, '&lt;').replace(/"/g,
                        '&quot;');
                    const qty = item.quantity != null ? item.quantity : '';
                    const unit = (item.unit || '—').replace(/</g, '&lt;');
                    const retQty = item.return_quantity != null ? item.return_quantity : 0;
                    const retDate = item.return_date || '';
                    const issuedQty = parseFloat(qty) || 0;
                    const rowIssueYmd = (item.issue_date || issueDate || '').trim();
                    const issueDisp = ymdToDmY(rowIssueYmd);
                    tbody.insertAdjacentHTML('beforeend',
                        '<tr><td>' + name + '<input type="hidden" name="items[' + i +
                        '][id]" value="' + id + '"></td><td>' + qty + '</td><td>' + unit +
                        '</td><td class="text-nowrap">' + issueDisp + '</td>' +
                        '<td><input type="number" name="items[' + i +
                        '][return_quantity]" class="form-control  dr-return-qty" step="0.01" min="0" max="' +
                        issuedQty + '" data-issued="' + issuedQty + '" value="' + retQty +
                        '"><div class="invalid-feedback">Return Qty cannot exceed Issued Qty.</div></td>' +
                        '<td><input type="date" name="items[' + i +
                        '][return_date]" class="form-control  dr-return-date" max="' +
                        todayYmd + '" ' + (rowIssueYmd ? ('min="' + rowIssueYmd +
                            '" data-issue-date="' + rowIssueYmd + '"') : '') + ' value="' +
                        retDate +
                        '"><div class="invalid-feedback">Return date must be between issue date and today.</div></td></tr>'
                    );
                });
                document.getElementById('returnItemForm').action = baseUrl + '/' + reportId + '/return';
                new bootstrap.Modal(document.getElementById('returnItemModal')).show();
            })
            .catch(err => {
                console.error(err);
                alert('Failed to load return data.');
            });
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
        const today = new Date().toISOString().slice(0, 10);
        inputEl.max = today;
        if (!raw) {
            inputEl.setCustomValidity('');
            inputEl.classList.remove('is-invalid');
            return;
        }
        if (raw > today) {
            inputEl.setCustomValidity('Return date cannot be in the future.');
            inputEl.classList.add('is-invalid');
            return;
        }
        if (issue && raw < issue) {
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

    function buildEditItemsTable(items) {
        const tbody = document.getElementById('editModalItemsBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        editRowIndex = 0;
        (items || []).forEach(function(item) {
            tbody.insertAdjacentHTML('beforeend', getEditRowHtml(editRowIndex, item));
            editRowIndex++;
        });
        if (tbody.querySelectorAll('.edit-dr-item-row').length === 0) {
            tbody.insertAdjacentHTML('beforeend', getEditRowHtml(editRowIndex, {}));
            editRowIndex++;
        }
        tbody.querySelectorAll('.edit-dr-item-row').forEach(function(row) {
            row.querySelector('.edit-dr-avail').addEventListener('input', function() {
                updateEditRowLeft(row);
            });
            row.querySelector('.edit-dr-qty').addEventListener('input', function() {
                refreshEditAllAvailable();
                updateEditRowTotal(row);
                updateEditGrandTotal();
            });
            row.querySelector('.edit-dr-rate').addEventListener('input', function() {
                updateEditRowTotal(row);
                updateEditGrandTotal();
            });
            row.querySelector('.edit-dr-item-select').addEventListener('change', function() {
                const o = getSelectSelectedOption(this);
                row.querySelector('.edit-dr-unit').value = (o && o.dataset.unit) ? o.dataset.unit :
                    '—';
                const rateInp = row.querySelector('.edit-dr-rate');
                if (rateInp && o && o.dataset.rate) rateInp.value = o.dataset.rate;
                const availInp = row.querySelector('.edit-dr-avail');
                if (availInp && o && o.dataset.available) availInp.value = o.dataset.available;
                refreshEditAllAvailable();
                updateEditRowTotal(row);
                updateEditGrandTotal();
            });
            row.querySelector('.edit-dr-remove-row').addEventListener('click', function() {
                row.remove();
                refreshEditAllAvailable();
                updateEditGrandTotal();
            });
        });
        refreshEditAllAvailable();
        updateEditGrandTotal();
    }

    // Edit report (mousedown ensures single-tap works with DataTables)
    document.addEventListener('mousedown', function(e) {
        const btn = e.target.closest('.btn-edit-report');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        const reportId = btn.getAttribute('data-report-id');
        document.getElementById('editReportForm').action = baseUrl + '/' + reportId;
        fetch(baseUrl + '/' + reportId + '/edit', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json().then(data => ({
                ok: r.ok,
                data
            })))
            .then(function({
                ok,
                data
            }) {
                if (!ok) {
                    alert(data && data.error ? data.error : 'Failed to load report for edit.');
                    return;
                }
                destroyEditModalTomSelects();
                const v = data.voucher;
                document.getElementById('editReportModalLabel').textContent = 'Edit Selling Voucher #' +
                    (v.id || reportId);
                document.querySelector('.edit-store-id').value = v.store_id || v.inve_store_master_pk || '';
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
                var editDrRemoveBillFlagEl = document.getElementById('editDrRemoveBillFlag');
                if (editDrRemoveBillFlagEl) editDrRemoveBillFlagEl.value = '0';
                var editBillLinkEl = document.getElementById('editCurrentBillLink');
                if (editBillLinkEl) {
                    if (v.bill_url) {
                        editBillLinkEl.innerHTML = 'Current bill: <a href="' + v.bill_url +
                            '" target="_blank" rel="noopener" class="text-primary">View Bill</a>';
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
                const slug = v.client_type_slug || 'employee';
                document.querySelectorAll('.edit-dr-client-type-radio').forEach(function(radio) {
                    radio.checked = (radio.value === slug);
                });
                var editWrap1 = document.getElementById('editDrClientNameWrap');
                var editWrap2 = document.getElementById('editDrNameFieldWrap');
                if (editWrap1) editWrap1.style.display = '';
                if (editWrap2) editWrap2.style.display = '';
                const isOt = slug === 'ot';
                const isCourse = slug === 'course';
                let editClientSelect = document.getElementById('editDrClientNameSelect');
                const editOtSelect = document.getElementById('editDrOtCourseSelect');
                const editCourseSelect = document.getElementById('editDrCourseSelect');
                const editCourseNameSelect = document.getElementById('editDrCourseNameSelect');
                const editNameInp = document.getElementById('editDrClientNameInput');
                if (isOt) {
                    if (editClientSelect) {
                        editClientSelect.style.display = 'none';
                        editClientSelect.removeAttribute('required');
                        editClientSelect.removeAttribute('name');
                    }
                    if (editOtSelect) {
                        editOtSelect.style.display = 'block';
                        editOtSelect.setAttribute('required', 'required');
                        editOtSelect.setAttribute('name', 'client_type_pk');
                        editOtSelect.value = v.client_type_pk || '';
                    }
                    if (editCourseSelect) {
                        editCourseSelect.style.display = 'none';
                        editCourseSelect.removeAttribute('required');
                        editCourseSelect.removeAttribute('name');
                        editCourseSelect.value = '';
                    }
                    if (editCourseNameSelect) {
                        editCourseNameSelect.style.display = 'none';
                        editCourseNameSelect.removeAttribute('required');
                        editCourseNameSelect.value = '';
                    }
                    if (editNameInp) {
                        editNameInp.style.display = 'block';
                        editNameInp.readOnly = true;
                        editNameInp.placeholder = 'Buyer name (OT)';
                        editNameInp.value = v.client_name || '';
                        editNameInp.removeAttribute('required');
                    }
                    var editOtStu = document.getElementById('editDrOtStudentSelect');
                    if (editOtStu) {
                        setSelectVisible(editOtStu, false);
                        editOtStu.removeAttribute('required');
                    }
                } else if (isCourse) {
                    if (editClientSelect) {
                        editClientSelect.style.display = 'none';
                        editClientSelect.removeAttribute('required');
                        editClientSelect.removeAttribute('name');
                    }
                    if (editOtSelect) {
                        editOtSelect.style.display = 'none';
                        editOtSelect.removeAttribute('required');
                        editOtSelect.removeAttribute('name');
                        editOtSelect.value = '';
                    }
                    if (editCourseSelect) {
                        editCourseSelect.style.display = 'block';
                        editCourseSelect.setAttribute('required', 'required');
                        editCourseSelect.setAttribute('name', 'client_type_pk');
                        editCourseSelect.value = v.client_type_pk || '';
                    }
                    if (editCourseNameSelect) {
                        editCourseNameSelect.style.display = 'none';
                        editCourseNameSelect.removeAttribute('required');
                        editCourseNameSelect.value = '';
                    }
                    if (editNameInp) {
                        editNameInp.style.display = 'block';
                        editNameInp.value = v.client_name || '';
                        editNameInp.placeholder = 'Course name';
                        editNameInp.setAttribute('required', 'required');
                    }
                } else {
                    if (editClientSelect) {
                        editClientSelect.style.display = 'block';
                        editClientSelect.setAttribute('required', 'required');
                        editClientSelect.setAttribute('name', 'client_type_pk');
                        if (clientNameOptionsEdit && clientNameOptionsEdit.length) {
                            rebuildEditClientNameSelect(slug);
                        }
                        editClientSelect = document.getElementById('editDrClientNameSelect');
                        setSelectValue(editClientSelect, v.client_type_pk || '');
                    }
                    if (editOtSelect) {
                        editOtSelect.style.display = 'none';
                        editOtSelect.removeAttribute('required');
                        editOtSelect.removeAttribute('name');
                        editOtSelect.value = '';
                    }
                    if (editCourseSelect) {
                        editCourseSelect.style.display = 'none';
                        editCourseSelect.removeAttribute('required');
                        editCourseSelect.removeAttribute('name');
                        editCourseSelect.value = '';
                    }
                    if (editCourseNameSelect) {
                        editCourseNameSelect.style.display = 'none';
                        editCourseNameSelect.removeAttribute('required');
                        editCourseNameSelect.value = '';
                    }
                    if (editNameInp) {
                        editNameInp.style.display = 'block';
                        editNameInp.readOnly = false;
                        editNameInp.placeholder = 'Client / section / role name';
                        editNameInp.setAttribute('required', 'required');
                    }
                }
                updateEditDrNameField();
                // Ensure TomSelect instances exist for the final state (and preserve selected values)
                initEditModalTomSelects();
                syncEditDrChoicesFromVoucher(v, slug);
                editCurrentStoreId = v.store_id || v.inve_store_master_pk || '';
                const items = data.items || [];
                const openEditModalWithItems = function() {
                    buildEditItemsTable(items);
                    new bootstrap.Modal(document.getElementById('editReportModal')).show();
                };
                if (editCurrentStoreId) {
                    fetchStoreItems(editCurrentStoreId, function() {
                        updateEditItemDropdowns();
                        openEditModalWithItems();
                    });
                } else {
                    filteredItems = itemSubcategories;
                    openEditModalWithItems();
                }
            })
            .catch(err => {
                console.error(err);
                alert('Failed to load report for edit.');
            });
    }, true);

    // Store selection change in EDIT modal
    const editStoreSelect = document.querySelector('#editReportModal select[name="inve_store_master_pk"]');
    if (editStoreSelect) {
        editStoreSelect.addEventListener('change', function() {
            const storeId = getSelectValue(this);
            editCurrentStoreId = storeId;
            if (!storeId) {
                filteredItems = itemSubcategories;
                updateEditItemDropdowns();
                return;
            }
            fetchStoreItems(storeId, function() {
                updateEditItemDropdowns();
            });
        });
    }

    const editReportModal = document.getElementById('editReportModal');
    if (editReportModal) {
        editReportModal.addEventListener('shown.bs.modal', function() {
            initEditModalTomSelects();
        });
        editReportModal.addEventListener('hidden.bs.modal', function() {
            destroyEditModalTomSelects();
        });
    }

    // Helper: reset Add Selling Voucher (Date Range) form to default state (without closing modal)
    function resetAddReportForm() {
        var addReportModal = document.getElementById('addReportModal');
        if (!addReportModal) return;

        destroyAddModalTomSelects();

        var form = document.getElementById('addReportForm');
        if (form) {
            form.reset();
            form.classList.remove('was-validated');
            form.querySelectorAll('.is-invalid').forEach(function(el) {
                el.classList.remove('is-invalid');
            });
        }
        var storeSel = addReportModal.querySelector('select[name="inve_store_master_pk"]');
        if (storeSel) storeSel.value = '';
        var issueDateInp = addReportModal.querySelector('input[name="issue_date"]');
        if (issueDateInp) issueDateInp.value = new Date().toISOString().slice(0, 10);
        var paymentSel = addReportModal.querySelector('select[name="payment_type"]');
        if (paymentSel) paymentSel.value = '1';
        var empRadio = addReportModal.querySelector('.dr-client-type-radio[value="employee"]');
        if (empRadio) {
            empRadio.checked = true;
            empRadio.dispatchEvent(new Event('change'));
        }
        var clientPkSel = addReportModal.querySelector('#drClientNameSelect');
        if (clientPkSel) clientPkSel.value = '';
        var clientNameInp = document.getElementById('drClientNameInput');
        if (clientNameInp) clientNameInp.value = '';
        addReportModal.querySelectorAll('#drClientNameWrap select, #drNameFieldWrap select').forEach(function(s) {
            if (s && typeof s.value !== 'undefined') s.value = '';
        });
        var billInput = document.getElementById('addDrBillFileInput');
        if (billInput) billInput.value = '';
        var billWrap = document.getElementById('addDrBillFileChosenWrap');
        var billName = document.getElementById('addDrBillFileChosenName');
        if (billWrap) billWrap.classList.add('d-none');
        if (billName) billName.textContent = '';
        var tbody = document.getElementById('addModalItemsBody');
        if (tbody) {
            tbody.innerHTML = getAddRowHtml(0);
            addRowIndex = 1;
            tbody.querySelectorAll('.dr-remove-row').forEach(function(btn) {
                btn.disabled = (tbody.querySelectorAll('.dr-item-row').length <= 1);
            });
            var firstRow = tbody.querySelector('.dr-item-row');
            if (firstRow) {
                firstRow.querySelector('.dr-item-select').addEventListener('change', function() {
                    var rateInp = firstRow.querySelector('.dr-rate');
                    if (rateInp) rateInp.dataset.manualRate = '';
                    updateAddRowUnit(firstRow);
                });
                firstRow.querySelector('.dr-qty').addEventListener('input', function() {
                    refreshAllAvailable();
                    updateAddRowTotal(firstRow);
                    updateAddGrandTotal();
                });
                firstRow.querySelector('.dr-rate').addEventListener('input', function() {
                    // Must match initial row wiring: otherwise FIFO tier logic overwrites rate every keystroke.
                    this.dataset.manualRate = '1';
                    updateAddRowTotal(firstRow);
                    updateAddGrandTotal();
                });
            }
        }
        var grandTotalEl = document.getElementById('addModalGrandTotal');
        if (grandTotalEl) grandTotalEl.textContent = '₹0.00';
    }

    // Reset add modal when closed (so next open starts fresh)
    const addReportModal = document.getElementById('addReportModal');
    if (addReportModal) {
        // Reset ASAP when user closes via X/Cancel/backdrop.
        // (hidden.bs.modal is sometimes late; do both for a reliable "refreshed" feel.)
        addReportModal.addEventListener('hide.bs.modal', function() {
            resetAddReportForm();
            var body = addReportModal.querySelector('.modal-body');
            if (body) body.scrollTop = 0;
        });
        addReportModal.addEventListener('hidden.bs.modal', function() {
            resetAddReportForm();
            var body = addReportModal.querySelector('.modal-body');
            if (body) body.scrollTop = 0;
        });

        addReportModal.addEventListener('show.bs.modal', function() {
            const storeSelect = addReportModal.querySelector('select[name="inve_store_master_pk"]');
            const preSelectedStore = storeSelect ? getSelectValue(storeSelect) : null;

            console.log('Modal opening, pre-selected store:', preSelectedStore); // Debug log

            // If there's a pre-selected store, fetch its items
            if (preSelectedStore) {
                currentStoreId = preSelectedStore;
                fetchStoreItems(preSelectedStore, function() {
                    console.log('Pre-fetched items for store:', preSelectedStore, 'Count:',
                        filteredItems.length);
                    updateAddItemDropdowns();
                    refreshAllAvailable();
                    document.querySelectorAll('#addModalItemsBody .dr-item-row').forEach(function(
                        row) {
                        updateAddRowTotal(row);
                    });
                    updateAddGrandTotal();
                });
            } else {
                currentStoreId = null;
                filteredItems = itemSubcategories;
                if (storeSelect) storeSelect.value = '';
            }
        });
        addReportModal.addEventListener('shown.bs.modal', function() {
            initAddModalTomSelects();
            var addRadio = document.querySelector('#addReportModal .dr-client-type-radio:checked');
            if (addRadio) {
                setTimeout(function() {
                    addRadio.dispatchEvent(new Event('change'));
                }, 0);
            }
            refreshAllAvailable();
            document.querySelectorAll('#addModalItemsBody .dr-item-row').forEach(function(row) {
                updateAddRowTotal(row);
            });
            updateAddGrandTotal();
        });
    }

    // After AJAX save (add/edit), refresh the listing DataTable so new rows show immediately.
    // This fetches the current page HTML and swaps DataTable rows (preserves search/paging).
    var isRefreshingSellingVoucherDateRangeTable = false;

    function refreshSellingVoucherDateRangeTable() {
        if (isRefreshingSellingVoucherDateRangeTable) return;
        if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable) return;

        var $ = window.jQuery;
        var $table = $('#sellingVoucherDateRangeTable');
        if (!$table.length || !$.fn.DataTable.isDataTable($table)) return;

        var dt = $table.DataTable();
        var expectedCols = $table.find('thead tr:first th').length;
        var url = window.location.pathname + window.location.search;

        isRefreshingSellingVoucherDateRangeTable = true;

        fetch(url, {
                headers: {
                    'Accept': 'text/html'
                }
            })
            .then(function(r) {
                return r.text();
            })
            .then(function(html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var newTbody = doc.querySelector('#sellingVoucherDateRangeTable tbody');
                if (!newTbody) return;

                var newRowData = [];
                newTbody.querySelectorAll('tr').forEach(function(tr) {
                    var cells = Array.from(tr.querySelectorAll('td,th'));
                    if (expectedCols && cells.length !== expectedCols)
                        return; // skip colspan/empty rows
                    newRowData.push(cells.map(function(td) {
                        return td.innerHTML;
                    }));
                });

                dt.clear();
                if (newRowData.length) dt.rows.add(newRowData);
                dt.draw(false);
            })
            .catch(function(err) {
                console.error('Failed to refresh selling voucher date-range table', err);
            })
            .finally(function() {
                isRefreshingSellingVoucherDateRangeTable = false;
            });
    }

    // Prevent double submit on Add form (stops double entry on Save Selling Voucher) + AJAX submit
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

        addReportFormEl.addEventListener('submit', function(e) {
            // If the form is invalid, the capture listener above will already have prevented default.
            if (!this.checkValidity()) {
                return;
            }

            e.preventDefault();

            var form = this;
            var btn = form.querySelector('button[type="submit"]');
            if (btn && btn.disabled) {
                return;
            }
            if (btn) {
                if (!btn.dataset.originalText) {
                    btn.dataset.originalText = btn.textContent || '';
                }
                btn.disabled = true;
                btn.textContent = 'Saving...';
            }

            var action = form.getAttribute('action') || window.location.href;
            var method = (form.getAttribute('method') || 'POST').toUpperCase();
            var formData = new FormData(form);
            var csrf = form.querySelector('input[name="_token"]');

            fetch(action, {
                    method: method,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf ? csrf.value : '',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(function(response) {
                    return response.json().then(function(payload) {
                        return {
                            ok: response.ok,
                            status: response.status,
                            payload: payload
                        };
                    }).catch(function() {
                        return {
                            ok: response.ok,
                            status: response.status,
                            payload: null
                        };
                    });
                })
                .then(function(res) {
                    var data = res.payload;
                    if (res.ok && data && data.success) {
                        var modalRoot = document.getElementById('addReportModal');
                        var storeSelect = modalRoot ? modalRoot.querySelector(
                            'select[name="inve_store_master_pk"]') : null;
                        var savedStoreId = getSelectValue(storeSelect);

                        resetAddReportForm();

                        function afterAddModalInventoryRefresh() {
                            updateAddItemDropdowns();
                            initAddModalTomSelects();
                            refreshAllAvailable();
                            document.querySelectorAll('#addModalItemsBody .dr-item-row').forEach(
                                function(row) {
                                    updateAddRowTotal(row);
                                });
                            updateAddGrandTotal();
                            var body = modalRoot && modalRoot.querySelector('.modal-body');
                            if (body) body.scrollTop = 0;
                        }

                        if (savedStoreId) {
                            if (storeSelect) {
                                storeSelect.value = String(savedStoreId);
                            }
                            currentStoreId = String(savedStoreId);
                            fetchStoreItems(String(savedStoreId), function() {
                                afterAddModalInventoryRefresh();
                            });
                        } else {
                            currentStoreId = null;
                            filteredItems = itemSubcategories;
                            afterAddModalInventoryRefresh();
                        }

                        refreshSellingVoucherDateRangeTable();

                        if (window.toastr && data.message) {
                            toastr.success(data.message);
                        } else if (data.message) {
                            alert(data.message);
                        }
                    } else {
                        var msg = (data && data.message) ? data.message :
                            'Failed to save voucher. Please try again.';
                        if (res.status === 422 && data && data.errors) {
                            try {
                                var firstKey = Object.keys(data.errors)[0];
                                if (firstKey && data.errors[firstKey] && data.errors[firstKey][0]) {
                                    msg = data.errors[firstKey][0];
                                }
                            } catch (e) {}
                        }
                        alert(msg);
                    }
                })
                .catch(function() {
                    alert('Failed to save voucher. Please try again.');
                })
                .finally(function() {
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = btn.dataset.originalText || 'Save Selling Voucher';
                    }
                });
        });
    }

    // Add modal: show selected bill file name and Remove button
    var addDrBillFileInputEl = document.getElementById('addDrBillFileInput');
    if (addDrBillFileInputEl) {
        addDrBillFileInputEl.addEventListener('change', function() {
            var wrap = document.getElementById('addDrBillFileChosenWrap');
            var nameEl = document.getElementById('addDrBillFileChosenName');
            if (wrap && nameEl) {
                if (this.files && this.files[0]) {
                    nameEl.textContent = this.files[0].name;
                    wrap.classList.remove('d-none');
                } else {
                    nameEl.textContent = '';
                    wrap.classList.add('d-none');
                }
            }
        });
    }
    var addDrBillFileRemoveEl = document.getElementById('addDrBillFileRemove');
    if (addDrBillFileRemoveEl) {
        addDrBillFileRemoveEl.addEventListener('click', function() {
            var input = document.getElementById('addDrBillFileInput');
            var wrap = document.getElementById('addDrBillFileChosenWrap');
            var nameEl = document.getElementById('addDrBillFileChosenName');
            if (input) input.value = '';
            if (nameEl) nameEl.textContent = '';
            if (wrap) wrap.classList.add('d-none');
        });
    }

    // Edit modal: show selected file name in same field when user picks a new bill
    var editSvBillFileInputEl = document.getElementById('editSvBillFileInput');
    if (editSvBillFileInputEl) {
        editSvBillFileInputEl.addEventListener('change', function() {
            var pathEl = document.getElementById('editSvCurrentBillPath');
            var removeFlag = document.getElementById('editDrRemoveBillFlag');
            if (pathEl) pathEl.textContent = this.files && this.files[0] ? this.files[0].name :
                'No file chosen';
            if (removeFlag) removeFlag.value = '0';
        });
    }
    var editDrBillFileRemoveEl = document.getElementById('editDrBillFileRemove');
    if (editDrBillFileRemoveEl) {
        editDrBillFileRemoveEl.addEventListener('click', function() {
            var input = document.getElementById('editSvBillFileInput');
            var pathEl = document.getElementById('editSvCurrentBillPath');
            var removeFlag = document.getElementById('editDrRemoveBillFlag');
            if (input) input.value = '';
            if (pathEl) pathEl.textContent = 'No file chosen';
            if (removeFlag) removeFlag.value = '1';
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

    // Filter: End Date must not be before Start Date
    document.addEventListener('DOMContentLoaded', function() {
        var filterStart = document.getElementById('filter_start_date');
        var filterEnd = document.getElementById('filter_end_date');
        if (filterStart && filterEnd) {
            filterStart.addEventListener('change', function() {
                filterEnd.min = this.value || '';
                if (filterEnd.value && this.value && filterEnd.value < this.value) {
                    filterEnd.value = this.value;
                }
            });
        }
    });

    // Print View modal content (Selling Voucher Date Range) – correct design with standard header
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-print-view-modal');
        if (!btn) return;
        var sel = btn.getAttribute('data-print-target');
        if (!sel) return;
        var modalEl = document.querySelector(sel);
        if (!modalEl) return;
        var content = modalEl.querySelector('.modal-content');
        if (!content) return;
        var win = window.open('', '_blank', 'width=900,height=700');
        if (!win) {
            alert('Please allow popups to print.');
            return;
        }
        var title = (modalEl.querySelector('.modal-title') || {}).textContent ||
            'Selling Voucher (Date Range)';
        var printedOn = new Date();
        var dateStr = printedOn.getDate().toString().padStart(2, '0') + '/' + (printedOn.getMonth() + 1)
            .toString().padStart(2, '0') + '/' + printedOn.getFullYear() + ', ' + printedOn
            .toLocaleTimeString('en-IN', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
        var bodyContent = content.innerHTML.replace(/<button[^>]*btn-close[^>]*>[\s\S]*?<\/button>/gi, '');
        var printHeader =
            '<div class="print-doc-header" style="text-align:center;margin-bottom:16px;padding-bottom:12px;border-bottom:2px solid #2c3e50;">' +
            '<div style="font-size:16px;font-weight:700;color:#1a1a1a;margin-bottom:4px;">OFFICER\'S MESS LBSNAA MUSSOORIE</div>' +
            '<div style="background:#495057;color:#fff;padding:6px 12px;font-size:13px;display:inline-block;margin:4px 0;">Selling Voucher (Date Range)</div>' +
            '<div style="font-size:11px;color:#6c757d;margin-top:6px;">Printed on ' + dateStr +
            '</div></div>';
        var printCss =
            '<style>@page{size:A4;margin:14mm;}body{font-family:Arial,sans-serif;font-size:12px;color:#212529;padding:0 12px;margin:0;background:#fff;}.print-doc-header{-webkit-print-color-adjust:exact;print-color-adjust:exact;}.modal-header{border-bottom:1px solid #dee2e6;padding-bottom:8px;margin-bottom:12px;}.modal-body{color:#212529;}.card{margin-bottom:14px;page-break-inside:avoid;}.card-header{font-weight:600;font-size:12px;margin-bottom:8px;}.card-body table th,.card-body table td{border:1px solid #adb5bd;padding:6px 8px;}table{width:100%;border-collapse:collapse;font-size:11px;}thead th{background:#af2910!important;color:#fff!important;border-color:#8b2009;font-weight:600;-webkit-print-color-adjust:exact;print-color-adjust:exact;}.card-footer{font-weight:600;padding-top:8px;}.btn-close,.modal-footer{display:none!important;}@media print{body{padding:0;}}</style>';
        win.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>' + title.replace(/</g,
                '&lt;') + '</title>' + printCss + '</head><body>' + printHeader +
            '<div class="modal-content-wrap">' + bodyContent + '</div></body></html>');
        win.document.close();
        win.focus();
        setTimeout(function() {
            win.print();
            win.close();
        }, 350);
    });
})();
</script>
@endsection