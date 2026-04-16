@extends('admin.layouts.master')
@section('title', 'Selling Voucher')
@section('setup_content')
@php
    $canDeleteSellingVoucher = hasRole('Admin') || hasRole('Mess-Admin');
@endphp
<div class="container-fluid py-3">
    <x-breadcrum title="Selling Voucher" />

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mt-2" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mt-2" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card mb-3 border-0 shadow-sm rounded-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h5 class="mb-1 fw-semibold">Selling Voucher</h5>
                    <p class="text-muted mb-0 small">Quickly filter and manage selling vouchers from here.</p>
                </div>
                <button type="button"
                        class="btn btn-primary d-inline-flex align-items-center gap-2"
                        data-bs-toggle="modal"
                        data-bs-target="#addSellingVoucherModal">
                    <span class="material-symbols-rounded" style="font-size: 1.1rem;">add</span>
                    <span class="fw-semibold">Add Selling Voucher</span>
                </button>
            </div>
            <div class="border rounded-3 bg-light p-3">
                <form method="GET" action="{{ route('admin.mess.material-management.index') }}">
                    <div class="row g-3">
                        <div class="col-md-2 col-sm-6">
                            <label class="form-label small text-muted mb-1">Status</label>
                            <select name="status[]" id="filter_status" class="form-select form-select-sm" multiple>
                                @php
                                    $selectedStatuses = request('status', []);
                                    if (!is_array($selectedStatuses)) {
                                        $selectedStatuses = $selectedStatuses !== null ? [$selectedStatuses] : [];
                                    }
                                @endphp
                                <option value="0" {{ in_array('0', $selectedStatuses) || in_array(0, $selectedStatuses) ? 'selected' : '' }}>Pending</option>
                                <option value="2" {{ in_array('2', $selectedStatuses) || in_array(2, $selectedStatuses) ? 'selected' : '' }}>Approved</option>
                                <option value="4" {{ in_array('4', $selectedStatuses) || in_array(4, $selectedStatuses) ? 'selected' : '' }}>Completed</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <label class="form-label small text-muted mb-1">Store</label>
                            <select name="store[]" id="filter_store" class="form-select form-select-sm" multiple>
                                @php
                                    $selectedStores = request('store', []);
                                    if (!is_array($selectedStores)) {
                                        $selectedStores = $selectedStores !== null ? [$selectedStores] : [];
                                    }
                                @endphp
                                @foreach($stores as $store)
                                    <option value="{{ $store['id'] }}" {{ in_array($store['id'], $selectedStores) ? 'selected' : '' }}>{{ $store['store_name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <label class="form-label small text-muted mb-1">Start Date</label>
                            <input type="date" name="start_date" id="filter_start_date" class="form-control" value="{{ request('start_date') ?? date('Y-m-d') }}">
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <label class="form-label small text-muted mb-1">End Date</label>
                            <input type="date" name="end_date" id="filter_end_date" class="form-control" value="{{ request('end_date') }}" min="{{ request('start_date') ?? date('Y-m-d') }}">
                        </div>
                        <div class="col-md-4 d-flex align-items-end justify-content-md-end gap-2">
                            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                                <span class="material-symbols-rounded" style="font-size: 1rem;">filter_list</span>
                                <span>Filter</span>
                            </button>
                            <a href="{{ route('admin.mess.material-management.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                                <span class="material-symbols-rounded" style="font-size: 1rem;">refresh</span>
                                <span>Clear</span>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0 w-100" id="sellingVouchersTable">
                    <thead class="table-light">
                         <tr class="small">
                            <th scope="col" class="text-center text-secondary fw-semibold text-uppercase" style="width: 3.5rem;">S. No.</th>
                            <th scope="col" class="text-secondary fw-semibold text-uppercase">Item Name</th>
                            <th scope="col" class="text-end text-secondary fw-semibold text-uppercase">Item Qty</th>
                            <th scope="col" class="text-end text-secondary fw-semibold text-uppercase">Return Qty</th>
                            <th scope="col" class="text-secondary fw-semibold text-uppercase">Transfer From Store</th>
                            <th scope="col" class="text-secondary fw-semibold text-uppercase">Client Type</th>
                            <th scope="col" class="text-secondary fw-semibold text-uppercase">Client Name</th>
                            <th scope="col" class="text-secondary fw-semibold text-uppercase">Name</th>
                            <th scope="col" class="text-secondary fw-semibold text-uppercase">Payment</th>
                            <th scope="col" class="text-nowrap text-secondary fw-semibold text-uppercase">Request Date</th>
                            <th scope="col" class="text-center text-secondary fw-semibold text-uppercase">Status</th>
                            <th scope="col" class="text-center text-secondary fw-semibold text-uppercase">Return Item</th>
                            <th scope="col" class="text-center text-secondary fw-semibold text-uppercase" style="width: 1%;">Action</th>
                        </tr>
                    </thead>
                    @php($serial = 1)
                    <tbody class="small">
                        @forelse($kitchenIssues as $voucher)
                            @forelse($voucher->items as $item)
                                <tr>
                                    <td class="text-center text-muted font-monospace">{{ $serial++ }}</td>
                                    <td class="fw-medium">{{ $item->item_name ?: ($item->itemSubcategory->item_name ?? '—') }}</td>
                                    <td class="text-end font-monospace">{{ $item->quantity }}</td>
                                    <td class="text-end font-monospace">{{ $item->return_quantity ?? 0 }}</td>
                                    <td>{{ $voucher->resolved_store_name }}</td>
                                    <td>{{ $voucher->client_type_label ?? '—' }}</td>
                                    <td>{{ $voucher->display_client_name }}</td>
                                    <td>{{ $voucher->client_name ?? '—' }}</td>
                                    <td>
                                        @if($voucher->payment_type == 1)<span class="badge rounded-pill text-bg-warning">Credit</span>
                                        @elseif($voucher->payment_type == 0)<span class="badge rounded-pill text-bg-secondary">Cash</span>
                                        @elseif($voucher->payment_type == 2)<span class="badge rounded-pill text-bg-info">UPI</span>
                                        @else<span class="text-muted">—</span>@endif
                                    </td>
                                    <td class="text-nowrap">{{ $voucher->created_at ? $voucher->created_at->format('d/m/Y') : '—' }}</td>
                                    <td class="text-center">
                                        @if($voucher->status == 0)<span class="badge rounded-pill text-bg-warning">Pending</span>
                                        @elseif($voucher->status == 2)<span class="badge rounded-pill text-bg-success">Approved</span>
                                        @elseif($voucher->status == 4)<span class="badge rounded-pill text-bg-primary">Completed</span>
                                        @else<span class="badge rounded-pill text-bg-secondary">{{ $voucher->status }}</span>@endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-center">
                                            @if(($item->return_quantity ?? 0) > 0)
                                                <span class="badge rounded-pill text-bg-info">Returned</span>
                                            @endif
                                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 btn-return-sv" data-voucher-id="{{ $voucher->pk }}" title="Return">Return</button>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center justify-content-center gap-1">
                                            <button type="button" class="btn btn-sm btn-light border btn-view-sv rounded-circle p-0 d-inline-flex align-items-center justify-content-center" style="width: 2.25rem; height: 2.25rem;" data-voucher-id="{{ $voucher->pk }}" title="View" aria-label="View voucher"><i class="material-symbols-rounded text-primary" style="font-size: 1.125rem;">visibility</i></button>
                                            <button type="button" class="btn btn-sm btn-light border btn-edit-sv rounded-circle p-0 d-inline-flex align-items-center justify-content-center" style="width: 2.25rem; height: 2.25rem;" data-voucher-id="{{ $voucher->pk }}" title="{{ $voucher->status == \App\Models\KitchenIssueMaster::STATUS_APPROVED ? 'Edit is disabled for approved voucher' : 'Edit' }}" aria-label="Edit voucher" @if($voucher->status == \App\Models\KitchenIssueMaster::STATUS_APPROVED) disabled @endif><i class="material-symbols-rounded text-warning" style="font-size: 1.125rem;">edit</i></button>
                                            @if($canDeleteSellingVoucher)
                                                <form action="{{ route('admin.mess.material-management.destroy', $voucher->pk) }}" method="POST" class="d-inline m-0" onsubmit="return confirm('Are you sure you want to delete this Selling Voucher?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-light border rounded-circle p-0 d-inline-flex align-items-center justify-content-center" style="width: 2.25rem; height: 2.25rem;" title="Delete" aria-label="Delete voucher"><i class="material-symbols-rounded text-danger" style="font-size: 1.125rem;">delete</i></button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center text-muted font-monospace">{{ $serial++ }}</td>
                                    <td class="text-muted">—</td>
                                    <td class="text-end text-muted">—</td>
                                    <td class="text-end text-muted">—</td>
                                    <td>{{ $voucher->resolved_store_name }}</td>
                                    <td>{{ $voucher->client_type_label ?? '—' }}</td>
                                    <td>{{ $voucher->display_client_name }}</td>
                                    <td>{{ $voucher->client_name ?? '—' }}</td>
                                    <td><span class="text-muted">—</span></td>
                                    <td class="text-nowrap">{{ $voucher->created_at ? $voucher->created_at->format('d/m/Y') : '—' }}</td>
                                    <td class="text-center">
                                        @if($voucher->status == 0)<span class="badge rounded-pill text-bg-warning">Pending</span>
                                        @elseif($voucher->status == 2)<span class="badge rounded-pill text-bg-success">Approved</span>
                                        @elseif($voucher->status == 4)<span class="badge rounded-pill text-bg-primary">Completed</span>
                                        @else<span class="badge rounded-pill text-bg-secondary">{{ $voucher->status }}</span>@endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-center">
                                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 btn-return-sv" data-voucher-id="{{ $voucher->pk }}" title="Return">Return</button>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center justify-content-center gap-1">
                                            <button type="button" class="btn btn-sm btn-light border btn-view-sv rounded-circle p-0 d-inline-flex align-items-center justify-content-center" style="width: 2.25rem; height: 2.25rem;" data-voucher-id="{{ $voucher->pk }}" title="View" aria-label="View voucher"><i class="material-symbols-rounded text-primary" style="font-size: 1.125rem;">visibility</i></button>
                                            <button type="button" class="btn btn-sm btn-light border btn-edit-sv rounded-circle p-0 d-inline-flex align-items-center justify-content-center" style="width: 2.25rem; height: 2.25rem;" data-voucher-id="{{ $voucher->pk }}" title="{{ $voucher->status == \App\Models\KitchenIssueMaster::STATUS_APPROVED ? 'Edit is disabled for approved voucher' : 'Edit' }}" aria-label="Edit voucher" @if($voucher->status == \App\Models\KitchenIssueMaster::STATUS_APPROVED) disabled @endif><i class="material-symbols-rounded text-warning" style="font-size: 1.125rem;">edit</i></button>
                                            @if($canDeleteSellingVoucher)
                                                <form action="{{ route('admin.mess.material-management.destroy', $voucher->pk) }}" method="POST" class="d-inline m-0" onsubmit="return confirm('Are you sure you want to delete this Selling Voucher?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-light border rounded-circle p-0 d-inline-flex align-items-center justify-content-center" style="width: 2.25rem; height: 2.25rem;" title="Delete" aria-label="Delete voucher"><i class="material-symbols-rounded text-danger" style="font-size: 1.125rem;">delete</i></button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        @empty
                            <tr>
                                <td class="text-center text-body-secondary py-5" colspan="13">
                                    <span class="d-inline-flex align-items-center gap-2">
                                        <span class="material-symbols-rounded text-secondary" style="font-size: 1.5rem;" aria-hidden="true">inbox</span>
                                        <span>No kitchen issues found.</span>
                                    </span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
    </div>
   </div>

    @include('components.mess-master-datatables', [
        'tableId' => 'sellingVouchersTable',
        'searchPlaceholder' => 'Search selling vouchers...',
        'ordering' => false,
        'actionColumnIndex' => 12,
        'infoLabel' => 'selling vouchers',
        'searchDelay' => 0,
        'searchSmart' => false,
    ])
    @include('mess.partials.modal-dropdown-stability')
</div>

{{-- Choices.js (Bootstrap-aligned styling below) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

{{-- Add Selling Voucher Modal (same UI/UX as Create Purchase Order) --}}
<style>
/* Filter dropdowns: Choices.js styling */
#filter_status + .choices,
#filter_store + .choices {
    margin-bottom: 0;
}
#filter_status + .choices .choices__inner,
#filter_store + .choices .choices__inner {
    min-height: calc(1.5em + 0.5rem + 2px);
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border: 1px solid var(--bs-border-color, #ced4da);
    border-radius: var(--bs-border-radius-sm, 0.25rem);
    background-color: var(--bs-body-bg, #fff);
}
#filter_status + .choices.is-open .choices__inner,
#filter_status + .choices.is-focused .choices__inner,
#filter_store + .choices.is-open .choices__inner,
#filter_store + .choices.is-focused .choices__inner {
    border-color: var(--bs-primary, #86b7fe);
    box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb, 13, 110, 253), 0.25);
}
#filter_status + .choices .choices__list--dropdown,
#filter_store + .choices .choices__list--dropdown {
    z-index: 1050;
    border: 1px solid var(--bs-border-color, #ced4da);
    border-radius: var(--bs-border-radius-sm, 0.25rem);
    font-size: 0.875rem;
}
#filter_status + .choices .choices__item,
#filter_store + .choices .choices__item {
    font-size: 0.875rem;
}

#addSellingVoucherModal .modal-dialog,
#editSellingVoucherModal .modal-dialog,
#viewSellingVoucherModal .modal-dialog,
#returnItemModal .modal-dialog {
    width: calc(100vw - 1rem);
    max-width: min(var(--bs-modal-width), calc(100vw - 1rem));
}
@media (min-width: 576px) {
    #addSellingVoucherModal .modal-dialog,
    #editSellingVoucherModal .modal-dialog,
    #viewSellingVoucherModal .modal-dialog,
    #returnItemModal .modal-dialog {
        width: calc(100vw - 2rem);
        max-width: min(var(--bs-modal-width), calc(100vw - 2rem));
    }
}
#addSellingVoucherModal .modal-dialog { max-height: calc(100dvh - 2rem); margin: 1rem auto; }
#addSellingVoucherModal .modal-content { max-height: calc(100dvh - 2rem); display: flex; flex-direction: column; }
#addSellingVoucherModal .modal-body { overflow-y: auto; max-height: calc(100dvh - 10rem); position: relative; z-index: 2; }
#editSellingVoucherModal .modal-body { overflow-y: auto; max-height: calc(100dvh - 10rem); position: relative; z-index: 2; }
#addSellingVoucherModal:not(.sv-choices-dropdown-open) .modal-body,
#editSellingVoucherModal:not(.sv-choices-dropdown-open) .modal-body {
    overflow-x: auto;
}
/* Body subtree must stack above modal-footer or the footer paints over overflowing dropdowns */
#addSellingVoucherModal .modal-footer,
#editSellingVoucherModal .modal-footer {
    position: relative;
    z-index: 1;
}
/* While dropdown is open keep modal width/scroll stable on small screens */
#addSellingVoucherModal.sv-choices-dropdown-open .modal-dialog,
#editSellingVoucherModal.sv-choices-dropdown-open .modal-dialog {
    overflow-x: hidden !important;
}
#addSellingVoucherModal.sv-choices-dropdown-open .modal-content,
#addSellingVoucherModal.sv-choices-dropdown-open .modal-body,
#editSellingVoucherModal.sv-choices-dropdown-open .modal-content,
#editSellingVoucherModal.sv-choices-dropdown-open .modal-body {
    overflow-x: hidden !important;
}
/* Item Details: do not use .table-responsive here — overflow-x:auto makes overflow-y compute to auto and clips Choices */
#addSellingVoucherModal .sv-item-details-table-wrap,
#editSellingVoucherModal .sv-item-details-table-wrap {
    overflow-x: auto;
    overflow-y: hidden;
    width: 100%;
}
#addSellingVoucherModal .sv-item-details-table-wrap .table,
#editSellingVoucherModal .sv-item-details-table-wrap .table {
    min-width: 920px;
    margin-bottom: 0;
}
@media (max-width: 991.98px) {
    #addSellingVoucherModal .sv-item-details-table-wrap,
    #editSellingVoucherModal .sv-item-details-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    #addSellingVoucherModal .sv-item-details-table-wrap .table,
    #editSellingVoucherModal .sv-item-details-table-wrap .table {
        min-width: 980px;
    }
}
#addSellingVoucherModal.sv-choices-dropdown-open .card:has(#modalItemsBody) .card-body,
#editSellingVoucherModal.sv-choices-dropdown-open .card:has(#editModalItemsBody) .card-body {
    overflow-x: hidden !important;
}
#addSellingVoucherModal.sv-choices-dropdown-open #modalItemsBody .choices,
#editSellingVoucherModal.sv-choices-dropdown-open #editModalItemsBody .choices {
    overflow: visible !important;
}
/* Item card: table sits in .card-body; .card-footer (grand total) was painting over the list */
#addSellingVoucherModal .card:has(#modalItemsBody) .card-body,
#editSellingVoucherModal .card:has(#editModalItemsBody) .card-body {
    position: relative;
    z-index: 2;
}
#addSellingVoucherModal .card:has(#modalItemsBody) .card-footer,
#editSellingVoucherModal .card:has(#editModalItemsBody) .card-footer {
    position: relative;
    z-index: 1;
}
#addSellingVoucherModal.sv-choices-dropdown-open .card:has(#modalItemsBody),
#editSellingVoucherModal.sv-choices-dropdown-open .card:has(#editModalItemsBody) {
    overflow-x: hidden !important;
}
/* Choices default --choices-z-index is 1; raise for modals + item table row stacking */
#addSellingVoucherModal .choices,
#editSellingVoucherModal .choices {
    --choices-z-index: 6100;
}
#modalItemsBody tr:has(.choices.is-open),
#editModalItemsBody tr:has(.choices.is-open) {
    position: relative;
    z-index: 50;
}
.ts-dropdown,
.ts-wrapper.choices .choices__list--dropdown,
.choices__list--dropdown.is-active {
    z-index: 6100 !important;
}
.ts-wrapper.choices { margin-bottom: 0; }
.ts-wrapper.choices .choices__inner {
    min-height: calc(1.5em + 0.75rem + 2px);
    padding: 0.375rem 0.75rem;
    border: 1px solid var(--bs-border-color, #ced4da);
    border-radius: var(--bs-border-radius, 0.375rem);
    background-color: var(--bs-body-bg, #fff);
    font-size: 1rem;
}
#modalItemsBody .ts-wrapper.choices .choices__inner,
#editModalItemsBody .ts-wrapper.choices .choices__inner {
    min-height: calc(1.5em + 0.5rem + 2px);
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: var(--bs-border-radius-sm, 0.25rem);
}
.ts-wrapper.choices.is-open .choices__inner,
.ts-wrapper.choices.is-focused .choices__inner {
    border-color: var(--bs-primary, #86b7fe);
    box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb, 13, 110, 253), 0.25);
}
.ts-wrapper.choices .choices__list--single { padding: 0; }
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
.ts-wrapper.choices.is-flipped .choices__list--dropdown.is-active { flex-direction: column-reverse; }
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
.ts-dropdown .choices__item--selectable.is-highlighted {
    background-color: rgba(var(--bs-primary-rgb, 13, 110, 253), 0.12);
}
/* Item Name: dropdown positioned with JS (position:fixed) — class for inner scroll cap */
#modalItemsBody .choices__list--dropdown.sv-item-choices-dropdown-fixed,
#editModalItemsBody .choices__list--dropdown.sv-item-choices-dropdown-fixed {
    box-sizing: border-box;
}
#modalItemsBody .choices__list--dropdown.sv-item-choices-dropdown-fixed .choices__list,
#editModalItemsBody .choices__list--dropdown.sv-item-choices-dropdown-fixed .choices__list {
    max-height: min(280px, 42vh) !important;
}
</style>
<div class="modal fade" id="addSellingVoucherModal" tabindex="-1" aria-labelledby="addSellingVoucherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-lg-down modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('admin.mess.material-management.store') }}" method="POST" id="sellingVoucherModalForm" enctype="multipart/form-data">
                @csrf
                {{-- Forces JSON response from store() so the modal can reset without a full page redirect --}}
                <input type="hidden" name="respond_json" value="1">
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="addSellingVoucherModalLabel">Add Selling Voucher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" >
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
                        <div class="card-header bg-white p-1">
                            <h6 class="mb-0 fw-semibold text-primary">Voucher Details</h6>
                        </div>
                        <div class="card-body p-1">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Client Type <span class="text-danger">*</span></label>
                                    <div class="d-flex flex-wrap gap-3 pt-1">
                                        @foreach($clientTypes as $slug => $label)
                                            <div class="form-check">
                                                <input class="form-check-input client-type-radio" type="radio" name="client_type_slug" id="modal_ct_{{ $slug }}" value="{{ $slug }}" {{ old('client_type_slug', 'employee') === $slug ? 'checked' : '' }} required>
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
                                        <option value="2" {{ old('payment_type') == '2' ? 'selected' : '' }}>UPI</option>
                                    </select>
                                    <small class="text-muted" id="modalPaymentTypeHint">Cash / UPI / Credit</small>
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
                                            <option value="{{ $course->pk }}" data-course-name="{{ e($course->course_name) }}">{{ e($course->course_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="modalCourseSelect" class="form-select" style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                            <option value="{{ $course->pk }}" data-course-name="{{ e($course->course_name) }}">{{ e($course->course_name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4" id="modalNameFieldWrap">
                                    <label class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" name="client_name" id="modalClientNameInput" class="form-control" value="{{ old('client_name') }}" placeholder="Client / section / role name" required>
                                    <datalist id="modalCourseBuyerNames"></datalist>
                                    <datalist id="modalGenericBuyerNames"></datalist>
                                    <select id="modalFacultySelect" class="form-select" style="display:none;">
                                        <option value="">Select Faculty</option>
                                        @foreach($faculties ?? [] as $f)
                                            <option value="{{ e($f->full_name) }}">{{ e($f->full_name_with_code ?? $f->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="modalAcademyStaffSelect" class="form-select" style="display:none;">
                                        <option value="">Select Academy Staff</option>
                                        @foreach($employees ?? [] as $e)
                                            <option value="{{ e($e->full_name_with_department ?? $e->full_name) }}">{{ e($e->full_name_with_department ?? $e->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="modalMessStaffSelect" class="form-select" style="display:none;">
                                        <option value="">Select Mess Staff</option>
                                        @foreach($messStaff ?? [] as $e)
                                            <option value="{{ e($e->full_name_with_department ?? $e->full_name) }}">{{ e($e->full_name_with_department ?? $e->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="modalOtStudentSelect" class="form-select" style="display:none;">
                                        <option value="">Select Student</option>
                                    </select>
                                    <select id="modalCourseNameSelect" class="form-select" style="display:none;">
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
                                    <select name="store_id" class="form-select" required>
                                        <option value="">Select Store</option>
                                        @foreach($stores as $store)
                                            <option value="{{ $store['id'] }}" {{ old('store_id') == $store['id'] ? 'selected' : '' }}>{{ $store['store_name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Remarks / Reference Number / Order By</label>
                                    <input type="text" name="remarks" class="form-control" value="{{ old('remarks') }}" placeholder="Remarks / Reference Number / Order By (optional)">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Bill upload removed as per requirement --}}

                    {{-- Item Details (same pattern as Purchase Order Item Details) --}}
                    <div class="card mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                            <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="modalAddItemRow">
                                + Add Item
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="sv-item-details-table-wrap">
                                <table class="table align-middle mb-0" id="svItemsTable">
                                    <thead>
                                        <tr>
                                            <th style="min-width: 280px;">Item Name <span class="text-white">*</span></th>
                                            <th style="min-width: 80px;">Unit</th>
                                            <th style="min-width: 100px;">Available Qty</th>
                                            <th style="min-width: 100px;">Issue Qty <span class="text-white">*</span></th>
                                            <th style="min-width: 100px;">Left Qty</th>
                                            <th style="min-width: 100px;">Rate <span class="text-white">*</span></th>
                                            <th style="min-width: 100px;">Total Amount</th>
                                            <th style="min-width: 50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="modalItemsBody">
                                        <tr class="sv-item-row">
                                            <td>
                                                <select name="items[0][item_subcategory_id]" class="form-select form-select-sm sv-item-select" required>
                                                    <option value="">Select Item</option>
                                                    @foreach($itemSubcategories as $s)
                                                        <option value="{{ $s['id'] }}" data-unit="{{ e($s['unit_measurement'] ?? '') }}" data-rate="{{ e($s['standard_cost'] ?? 0) }}">{{ e($s['item_name'] ?? '—') }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="text" name="items[0][unit]" class="form-control  sv-unit" readonly></td>
                                            <td><input type="text" name="items[0][available_quantity]" class="form-control  sv-avail bg-light" readonly></td>
                                            <td>
                                                <input type="text" name="items[0][quantity]" class="form-control  sv-qty" required>
                                                <div class="invalid-feedback">Issue Qty cannot exceed Available Qty.</div>
                                            </td>
                                            <td><input type="text" class="form-control  sv-left bg-light" readonly></td>
                                            <td><input type="text" name="items[0][rate]" class="form-control  sv-rate" required></td>
                                            <td><input type="text" class="form-control  sv-total" readonly></td>
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

{{-- Edit Selling Voucher Modal (body z-index / overflow: shared rules with Add modal above) --}}
<style>
#editSellingVoucherModal .modal-dialog { max-height: calc(100dvh - 2rem); margin: 1rem auto; }
#editSellingVoucherModal .modal-content { max-height: calc(100dvh - 2rem); display: flex; flex-direction: column; }
</style>
<div class="modal fade" id="editSellingVoucherModal" tabindex="-1" aria-labelledby="editSellingVoucherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-lg-down modal-dialog-centered">
        <div class="modal-content">
            <form id="editSellingVoucherForm" method="POST" action="" enctype="multipart/form-data">
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
                                        <option value="2">UPI</option>
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
                                            <option value="{{ $course->pk }}" data-course-name="{{ e($course->course_name) }}">{{ e($course->course_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="editModalCourseSelect" class="form-select" style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                            <option value="{{ $course->pk }}" data-course-name="{{ e($course->course_name) }}">{{ e($course->course_name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4" id="editModalNameFieldWrap">
                                    <label class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" name="client_name" class="form-control edit-client-name" id="editModalClientNameInput" placeholder="Client / section / role name" required>
                                    <datalist id="editCourseBuyerNames"></datalist>
                                    <datalist id="editGenericBuyerNames"></datalist>
                                    <select id="editModalFacultySelect" class="form-select" style="display:none;">
                                        <option value="">Select Faculty</option>
                                        @foreach($faculties ?? [] as $f)
                                            <option value="{{ e($f->full_name) }}">{{ e($f->full_name_with_code ?? $f->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="editModalAcademyStaffSelect" class="form-select" style="display:none;">
                                        <option value="">Select Academy Staff</option>
                                        @foreach($employees ?? [] as $e)
                                            <option value="{{ e($e->full_name_with_department ?? $e->full_name) }}">{{ e($e->full_name_with_department ?? $e->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="editModalMessStaffSelect" class="form-select" style="display:none;">
                                        <option value="">Select Mess Staff</option>
                                        @foreach($messStaff ?? [] as $e)
                                            <option value="{{ e($e->full_name_with_department ?? $e->full_name) }}">{{ e($e->full_name_with_department ?? $e->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="editModalCourseNameSelect" class="form-select" style="display:none;">
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
                                    <select name="store_id" class="form-select edit-store" required>
                                        <option value="">Select Store</option>
                                        @foreach($stores as $store)
                                            <option value="{{ $store['id'] }}">{{ $store['store_name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Remarks / Reference Number / Order By</label>
                                    <input type="text" name="remarks" class="form-control edit-remarks" placeholder="Remarks / Reference Number / Order By (optional)">
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Bill upload removed as per requirement --}}
                    <div class="card mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                            <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="editModalAddItemRow">+ Add Item</button>
                        </div>
                        <div class="card-body p-0">
                            <div class="sv-item-details-table-wrap">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="min-width: 280px;">Item Name <span class="text-white">*</span></th>
                                            <th style="min-width: 80px;">Unit</th>
                                            <th style="min-width: 100px;">Available Qty</th>
                                            <th style="min-width: 100px;">Issue Qty <span class="text-white">*</span></th>
                                            <th style="min-width: 100px;">Left Qty</th>
                                            <th style="min-width: 100px;">Rate <span class="text-white">*</span></th>
                                            <th style="min-width: 100px;">Total Amount</th>
                                            <th style="min-width: 50px;"></th>
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
#viewSellingVoucherModal .modal-dialog { max-height: calc(100dvh - 2rem); margin: 1rem auto; }
#viewSellingVoucherModal .modal-content { max-height: calc(100dvh - 2rem); display: flex; flex-direction: column; background: #fff; color: #212529; }
#viewSellingVoucherModal .modal-header { background: #f8f9fa !important; color: #212529 !important; }
#viewSellingVoucherModal .modal-header * { color: #212529 !important; }
#viewSellingVoucherModal .modal-title { color: #212529 !important; }
#viewSellingVoucherModal .modal-body { overflow-y: auto; max-height: calc(100dvh - 10rem); background: #fff; color: #212529 !important; }
#viewSellingVoucherModal .modal-body *, #viewSellingVoucherModal .modal-body p, #viewSellingVoucherModal .modal-body span { color: inherit; }
#viewSellingVoucherModal .card { background: #fff; color: #212529; }
#viewSellingVoucherModal .card-header { background: #fff !important; color: #212529 !important; border-color: #dee2e6; }
#viewSellingVoucherModal .card-header h6 { color: #0d6efd !important; }
#viewSellingVoucherModal .card-body { background: #fff !important; color: #212529 !important; }
#viewSellingVoucherModal .card-body table th { color: #495057 !important; font-weight: 600; }
#viewSellingVoucherModal .card-body table td { color: #212529 !important; }
#viewSellingVoucherModal .card-body .table-borderless th { background: transparent !important; }
#viewSellingVoucherModal .card-body .table-borderless td { background: transparent !important; }
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
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-lg-down modal-dialog-centered">
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
                    </div>
                </div>
                <div class="card mb-4" id="viewItemsCard">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Unit</th>
                                        <th>Issue Qty</th>
                                        <th>Return Qty</th>
                                        <th>Rate</th>
                                        <th>Total</th>
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
                <button type="button" class="btn btn-outline-primary btn-print-view-modal" data-print-target="#viewSellingVoucherModal" title="Print">
                    <i class="ti ti-printer"></i> Print
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Return Item Modal (Transfer To) --}}
<div class="modal fade" id="returnItemModal" tabindex="-1" aria-labelledby="returnItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-lg-down modal-dialog-centered">
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
                                            <th style="color: #fff;">Item Issue Date</th>
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

    function safeFocus(el) {
        if (!el || typeof el.focus !== 'function') return;
        try {
            el.focus({ preventScroll: true });
        } catch (e) {
            try { el.focus(); } catch (e2) {}
        }
    }

    // Keep modal scroll stable; don't toggle overflow classes on dropdown open/close.
    function installModalScrollGuard(modalId) {
        var modal = document.getElementById(modalId);
        if (!modal) return;

        var last = { winTop: 0, bodyTop: 0, has: false };
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
                try { window.scrollTo(0, last.winTop); } catch (e) {}
                if (body) body.scrollTop = last.bodyTop;
            }
            requestAnimationFrame(restoreOnce);
            setTimeout(restoreOnce, 0);
            setTimeout(restoreOnce, 50);
            setTimeout(restoreOnce, 150);
        }

        modal.addEventListener('pointerdown', function() {
            capture();
            restoreSoon();
        }, true);
        modal.addEventListener('focusin', function() {
            capture();
            restoreSoon();
        }, true);
    }

    installModalScrollGuard('addSellingVoucherModal');
    installModalScrollGuard('editSellingVoucherModal');

    /** Sync modal class when a Choices root (.choices) opens/closes only — not on every list item highlight (avoids huge MutationObserver churn). */
    function initSellingVoucherModalChoicesOpenSync() {
        ['addSellingVoucherModal', 'editSellingVoucherModal'].forEach(function(modalId) {
            var modal = document.getElementById(modalId);
            if (!modal) return;
            var flag = 'sv-choices-dropdown-open';
            function sync() {
                if (modal.querySelector('.choices.is-open')) modal.classList.add(flag);
                else modal.classList.remove(flag);
            }
            var mo = new MutationObserver(function(mutations) {
                for (var i = 0; i < mutations.length; i++) {
                    var m = mutations[i];
                    if (m.type !== 'attributes' || m.attributeName !== 'class') continue;
                    var t = m.target;
                    if (t && t.classList && t.classList.contains('choices')) {
                        sync();
                        return;
                    }
                }
            });
            mo.observe(modal, { subtree: true, attributes: true, attributeFilter: ['class'] });
            sync();
        });
    }
    // Disabled to prevent modal jump on dropdown open/close caused by overflow toggles.
    // initSellingVoucherModalChoicesOpenSync();

    /**
     * Item rows: Choices list is position:absolute inside nested overflow/table contexts.
     * Pin the panel to viewport with fixed + getBoundingClientRect so it is never clipped.
     */
    function bindSvItemChoicesFixedDropdown(selectEl, choices, api) {
        var modalBody = null;
        var placeScheduled = false;
        function getDropdownEl() {
            return choices.dropdown && choices.dropdown.element;
        }
        function place() {
            var dd = getDropdownEl();
            var wrap = api.wrapper;
            if (!dd || !wrap || !wrap.classList.contains('is-open')) return;
            var inner = wrap.querySelector('.choices__inner');
            if (!inner) return;
            var r = inner.getBoundingClientRect();
            var flipped = wrap.classList.contains('is-flipped');
            var margin = 8;
            var spaceBelow = window.innerHeight - r.bottom - margin * 2;
            var spaceAbove = r.top - margin * 2;
            dd.classList.add('sv-item-choices-dropdown-fixed');
            dd.style.setProperty('position', 'fixed', 'important');
            dd.style.setProperty('left', Math.max(margin, Math.min(r.left, window.innerWidth - Math.max(r.width, 200) - margin)) + 'px', 'important');
            dd.style.setProperty('width', Math.max(r.width, 220) + 'px', 'important');
            dd.style.setProperty('max-height', Math.max(120, flipped ? spaceAbove : spaceBelow) + 'px', 'important');
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
            window.addEventListener('resize', onScrollOrResize, { passive: true });
            document.addEventListener('scroll', onScrollOrResize, true);
            if (modalBody) modalBody.addEventListener('scroll', onScrollOrResize, { passive: true });
        }
        function onHide() {
            var dd = getDropdownEl();
            if (dd) {
                dd.classList.remove('sv-item-choices-dropdown-fixed');
                ['position', 'left', 'top', 'right', 'bottom', 'width', 'max-height', 'z-index'].forEach(function(p) {
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

    /**
     * Selling voucher dropdowns: type-to-search using whole words only.
     * Each space-separated token must match a label word from the start (prefix while typing),
     * so e.g. "rice" matches "Basmati Rice" but not "price".
     */
    function svNormalizeSearchQuery(q) {
        return String(q || '').trim().replace(/\s{2,}/g, ' ');
    }

    function svLabelMatchesExactWordTokens(label, query) {
        var q = svNormalizeSearchQuery(query).toLowerCase();
        if (!q) return true;
        var labelStr = String(label || '');
        var words = labelStr.toLowerCase().match(/[\u0900-\u0FFF\w]+/g);
        if (!words || !words.length) {
            return labelStr.toLowerCase().indexOf(q) >= 0;
        }
        var tokens = q.split(/\s+/).filter(Boolean);
        var allMatch = tokens.every(function(tok) {
            return words.some(function(w) {
                return w === tok || w.indexOf(tok) === 0;
            });
        });
        if (allMatch) return true;
        // Fallback: substring match so short queries and labels without word boundaries still filter
        return labelStr.toLowerCase().indexOf(q) >= 0;
    }

    function patchChoicesSearcherExactWordTokens(choicesInstance) {
        try {
            var searcher = choicesInstance._searcher;
            var store = choicesInstance._store;
            if (!searcher || !store || searcher._svExactWordPatched) return;
            searcher._svExactWordPatched = true;
            var origSearch = searcher.search.bind(searcher);
            searcher.search = function(needle) {
                var nv = svNormalizeSearchQuery(needle);
                if (!nv.length) return origSearch(needle);
                var list = store.searchableChoices;
                if (!list || !list.length) return origSearch(needle);
                var out = [];
                for (var i = 0; i < list.length; i++) {
                    var item = list[i];
                    if (item.placeholder) continue;
                    var lab = item.label != null ? String(item.label) : '';
                    if (svLabelMatchesExactWordTokens(lab, nv)) {
                        out.push({ item: item, score: 0, rank: out.length + 1 });
                    }
                }
                return out;
            };
        } catch (e) {
            console.warn('patchChoicesSearcherExactWordTokens', e);
        }
    }

    function createChoicesInstance(selectEl, settings) {
        if (!selectEl || typeof window.Choices === 'undefined') return null;
        if (selectEl.choicesInstance && selectEl.tomselect && selectEl.tomselect._choices) {
            return selectEl.choicesInstance;
        }
        selectEl.choicesInstance = null;
        selectEl.tomselect = null;
        settings = settings || {};

        var choiceConfig = {
            allowHTML: false,
            itemSelectText: '',
            shouldSort: false,
            searchEnabled: settings.searchEnabled !== false,
            searchChoices: settings.searchChoices !== false,
            searchFloor: typeof settings.searchFloor === 'number' ? settings.searchFloor : 0,
            searchResultLimit: typeof settings.maxOptions === 'number' ? settings.maxOptions : -1,
            placeholder: true,
            placeholderValue: settings.placeholder || (selectEl.getAttribute('placeholder') || ''),
            searchPlaceholderValue: typeof settings.searchPlaceholderValue === 'string' ? settings.searchPlaceholderValue : ''
        };

        if (settings.removeItemButton === true) {
            choiceConfig.removeItemButton = true;
        }

        if (Array.isArray(settings.searchFields)) {
            choiceConfig.searchFields = settings.searchFields;
        }

        var choices = new window.Choices(selectEl, choiceConfig);
        if (settings.exactWordTokenSearch === true) {
            patchChoicesSearcherExactWordTokens(choices);
        }
        var api = {
            _choices: choices,
            selectEl: selectEl,
            settings: settings,
            activeOption: null,
            items: [],
            wrapper: choices.containerOuter ? choices.containerOuter.element : null,
            control_input: null,
            getValue: function() { return this.selectEl ? (this.selectEl.value || '') : ''; },
            setValue: function(v) {
                var value = (v === null || typeof v === 'undefined') ? '' : String(v);
                this._choices.removeActiveItems();
                if (value !== '') this._choices.setChoiceByValue(value);
                this.syncItems();
            },
            clear: function() {
                this._choices.removeActiveItems();
                this.syncItems();
            },
            addOption: function(opt) {
                if (!opt) return;
                var val = (opt.value === null || typeof opt.value === 'undefined') ? '' : String(opt.value);
                this._choices.setChoices([{ value: val, label: opt.text || val, selected: false, disabled: false }], 'value', 'label', false);
            },
            destroy: function() {
                try {
                    if (this._choices) this._choices.destroy();
                } catch (e) {
                    console.warn('Choices destroy failed', e);
                } finally {
                    if (this.selectEl) {
                        this.selectEl.choicesInstance = null;
                        this.selectEl.tomselect = null;
                    }
                    this._choices = null;
                }
            },
            setTextboxValue: function(v) {
                if (this.control_input) this.control_input.value = v || '';
            },
            onSearchChange: function() {},
            refreshOptions: function() {},
            syncItems: function() {
                var v = this.getValue();
                this.items = (v === '' || v === null || typeof v === 'undefined') ? [] : [String(v)];
            }
        };
        api.control_input = api.wrapper ? api.wrapper.querySelector('input.choices__input--cloned') : null;
        if (api.wrapper && api.wrapper.classList) api.wrapper.classList.add('ts-wrapper');
        if (choices.dropdown && choices.dropdown.element && choices.dropdown.element.classList) {
            choices.dropdown.element.classList.add('ts-dropdown');
        }
        api.syncItems();

        selectEl.addEventListener('change', function() { api.syncItems(); });
        selectEl.addEventListener('showDropdown', function() {
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

        if (selectEl.classList.contains('sv-item-select')) {
            bindSvItemChoicesFixedDropdown(selectEl, choices, api);
        }

        selectEl.choicesInstance = api;
        selectEl.tomselect = api;
        return api;
    }

    function createBlankSearchConfig(extra) {
        return Object.assign({
            allowEmptyOption: true,
            dropdownParent: 'body',
            searchField: ['text'],
            controlInput: '<input>',
            highlight: false,
            exactWordTokenSearch: true,
            searchFields: ['label'],
            searchPlaceholderValue: 'Type to search...',
            onInitialize: function () {
                this.activeOption = null;
            },
            onDropdownOpen: function (dropdown) {
                var self = this;
                var modalEl = self.input && self.input.closest ? self.input.closest('.modal') : null;
                var modalBody = modalEl ? modalEl.querySelector('.modal-body') : null;
                var helper = window.MessModalDropdownStability;
                self._modalDropdownState = helper && modalEl ? helper.onOpen(modalEl) : null;
                if (!self._modalDropdownState && modalBody) self._modalDropdownState = { scrollTop: modalBody.scrollTop };
                function clearInputAndCursor() {
                    var input = (dropdown && dropdown.querySelector('input.choices__input--cloned')) ||
                        (dropdown && dropdown.querySelector('input')) ||
                        self.control_input;
                    if (typeof self.setTextboxValue === 'function') self.setTextboxValue('');
                    if (typeof self.onSearchChange === 'function') self.onSearchChange('');
                    if (typeof self.refreshOptions === 'function') self.refreshOptions(false);
                    if (input) {
                        input.style.display = 'block';
                        input.style.visibility = 'visible';
                        input.style.opacity = '1';
                        input.value = '';
                        safeFocus(input);
                        try { input.setSelectionRange(0, 0); } catch (e) {}
                        input.scrollLeft = 0;
                    }
                    if (helper && modalEl) {
                        helper.keepScroll(modalEl, self._modalDropdownState);
                    } else if (modalBody && self._modalDropdownState && typeof self._modalDropdownState.scrollTop === 'number') {
                        modalBody.scrollTop = self._modalDropdownState.scrollTop;
                    }
                }
                if (self.settings && self.settings.clearOnOpen) {
                    self.clear(true);
                }
                clearInputAndCursor();
                setTimeout(clearInputAndCursor, 0);
                setTimeout(clearInputAndCursor, 50);
                setTimeout(clearInputAndCursor, 100);
                if (dropdown) {
                    setTimeout(function () {
                        var opts = dropdown.querySelectorAll('.option.active, .option.selected, .option[aria-selected="true"], .choices__item--selectable[aria-selected="true"]');
                        opts.forEach(function (opt) {
                            opt.classList.remove('active');
                            opt.classList.remove('selected');
                            opt.setAttribute('aria-selected', 'false');
                        });
                    }, 0);
                }
            },
            onDropdownClose: function () {
                var self = this;
                var modalEl = self.input && self.input.closest ? self.input.closest('.modal') : null;
                var modalBody = modalEl ? modalEl.querySelector('.modal-body') : null;
                var helper = window.MessModalDropdownStability;
                if (helper && modalEl) {
                    helper.onClose(modalEl, self._modalDropdownState);
                } else if (modalBody && self._modalDropdownState && typeof self._modalDropdownState.scrollTop === 'number') {
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
            clearOnOpen: false
        });
    }

    function createEditModalItemSelectConfig() {
        return Object.assign(createItemSelectConfig(), {
            onDropdownOpen: function (dropdown) {
                var self = this;
                var modalEl = self.input && self.input.closest ? self.input.closest('.modal') : null;
                var modalBody = modalEl ? modalEl.querySelector('.modal-body') : null;
                var helper = window.MessModalDropdownStability;
                self._modalDropdownState = helper && modalEl ? helper.onOpen(modalEl) : null;
                if (!self._modalDropdownState && modalBody) self._modalDropdownState = { scrollTop: modalBody.scrollTop };
                function clearInputAndCursor() {
                    var input = (dropdown && dropdown.querySelector('input.choices__input--cloned')) ||
                        (dropdown && dropdown.querySelector('input')) ||
                        self.control_input;
                    if (typeof self.setTextboxValue === 'function') self.setTextboxValue('');
                    if (typeof self.onSearchChange === 'function') self.onSearchChange('');
                    if (typeof self.refreshOptions === 'function') self.refreshOptions(false);
                    if (input) {
                        input.value = '';
                        safeFocus(input);
                        try { input.setSelectionRange(0, 0); } catch (e) {}
                        input.scrollLeft = 0;
                    }
                    if (helper && modalEl) {
                        helper.keepScroll(modalEl, self._modalDropdownState);
                    } else if (modalBody && self._modalDropdownState && typeof self._modalDropdownState.scrollTop === 'number') {
                        modalBody.scrollTop = self._modalDropdownState.scrollTop;
                    }
                }
                self.clear(true);
                clearInputAndCursor();
                setTimeout(function () {
                    self.clear(true);
                    clearInputAndCursor();
                }, 0);
                setTimeout(function () {
                    self.clear(true);
                    clearInputAndCursor();
                }, 50);
                setTimeout(function () {
                    self.clear(true);
                    clearInputAndCursor();
                }, 100);
                if (dropdown) {
                    setTimeout(function () {
                        var opts = dropdown.querySelectorAll('.option.active, .option.selected, .option[aria-selected="true"], .choices__item--selectable[aria-selected="true"]');
                        opts.forEach(function (opt) {
                            opt.classList.remove('active');
                            opt.classList.remove('selected');
                            opt.setAttribute('aria-selected', 'false');
                        });
                    }, 0);
                }
            }
        });
    }

    // Cache original Client Name options so we can rebuild the select per Client Type.
    var clientNameOptionsAdd = [];
    var clientNameOptionsEdit = [];
    function cacheClientNameOptions() {
        clientNameOptionsAdd = [];
        clientNameOptionsEdit = [];
        var addSel = document.getElementById('modalClientNameSelect');
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
        var editSel = document.getElementById('editClientNameSelect');
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
    }
    cacheClientNameOptions();

    var addModalTomSelectInstances = { payment: null, client: null, store: null };
    var editModalTomSelectInstances = { payment: null, client: null, store: null };

    function rebuildClientNameSelect(selectEl, optionsList, slug) {
        if (!selectEl || !Array.isArray(optionsList)) return;
        var slugLower = (slug || '').toLowerCase().trim();
        var filtered = optionsList.filter(function(o) { return (o.type || '').toLowerCase().trim() === slugLower; });

        // Preserve a valid selection if possible; otherwise clear.
        var preserved = '';
        if (selectEl.tomselect) preserved = selectEl.tomselect.getValue() || '';
        else preserved = selectEl.value || '';

        if (selectEl.tomselect) { try { selectEl.tomselect.destroy(); } catch (e) {} }
        if (selectEl.id === 'modalClientNameSelect') addModalTomSelectInstances.client = null;
        if (selectEl.id === 'editClientNameSelect') editModalTomSelectInstances.client = null;

        selectEl.innerHTML = '<option value="">Select Client Name</option>';
        filtered.forEach(function(o) {
            var opt = document.createElement('option');
            opt.value = o.value;
            opt.textContent = o.text;
            opt.setAttribute('data-type', (o.type || '').toLowerCase().trim());
            opt.setAttribute('data-client-name', (o.clientName || '').toLowerCase().trim());
            selectEl.appendChild(opt);
        });

        var inst = null;
        if (typeof Choices !== 'undefined') {
            inst = createChoicesInstance(selectEl, createBlankSearchConfig({
                placeholder: 'Select Client Name',
                clearOnOpen: true
            }));
            if (selectEl.id === 'modalClientNameSelect') addModalTomSelectInstances.client = inst;
            if (selectEl.id === 'editClientNameSelect') editModalTomSelectInstances.client = inst;
        }

        // Restore preserved selection if it still exists.
        if (preserved) {
            var stillExists = Array.from(selectEl.options).some(function(o) { return String(o.value) === String(preserved); });
            if (stillExists) {
                if (selectEl.tomselect) selectEl.tomselect.setValue(preserved, true);
                else selectEl.value = preserved;
            }
        }
    }

    function setSelectValue(selectEl, value) {
        if (!selectEl) return;
        var v = (value === null || value === undefined) ? '' : String(value);
        if (selectEl.tomselect) selectEl.tomselect.setValue(v);
        else selectEl.value = v;
    }

    /** After Choices.js init on Edit Selling Voucher modal, push API values into instances (store/payment/client/course/name). */
    function syncEditSellingVoucherChoicesFromVoucher(v, editSlug) {
        editSlug = String(editSlug || 'employee').toLowerCase();
        var paySel = document.querySelector('#editSellingVoucherModal select.edit-payment-type');
        if (paySel && paySel.tomselect) {
            try { paySel.tomselect.setValue(String(v.payment_type ?? 1)); } catch (e) {}
        }
        var stSel = document.querySelector('#editSellingVoucherModal select.edit-store');
        var sid = v.store_id || v.inve_store_master_pk || '';
        if (stSel && stSel.tomselect && sid !== '') {
            try { stSel.tomselect.setValue(String(sid)); } catch (e) {}
        }
        var ecs = document.getElementById('editClientNameSelect');
        if (ecs && ecs.tomselect && editSlug !== 'ot' && editSlug !== 'course' && v.client_type_pk != null && String(v.client_type_pk) !== '') {
            try { ecs.tomselect.setValue(String(v.client_type_pk)); } catch (e) {}
        }
        var eot = document.getElementById('editModalOtCourseSelect');
        if (eot && eot.tomselect && editSlug === 'ot' && v.client_type_pk != null && String(v.client_type_pk) !== '') {
            try { eot.tomselect.setValue(String(v.client_type_pk)); } catch (e) {}
        }
        var crs = document.getElementById('editModalCourseSelect');
        if (crs && crs.tomselect && editSlug === 'course' && v.client_type_pk != null && String(v.client_type_pk) !== '') {
            try { crs.tomselect.setValue(String(v.client_type_pk)); } catch (e) {}
        }
        var cn = String(v.client_name || '').trim();
        if (cn) {
            ['editModalFacultySelect', 'editModalAcademyStaffSelect', 'editModalMessStaffSelect'].forEach(function(id) {
                var el = document.getElementById(id);
                if (!el || !el.tomselect) return;
                try { el.tomselect.setValue(cn); } catch (e) {}
            });
        }
    }

    // When user clicks any Cancel/Close button in a modal (secondary button),
    // close the modal and refresh the page to reset all filters/state (only for Add/Edit Selling Voucher modals).
    document.querySelectorAll('#addSellingVoucherModal button.btn-secondary[data-bs-dismiss="modal"], #editSellingVoucherModal button.btn-secondary[data-bs-dismiss="modal"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            window.location.reload();
        });
    });

    // Filter dropdowns (Choices.js): same exact word-token search as selling voucher modals
    if (typeof Choices !== 'undefined') {
        var filterStatus = document.getElementById('filter_status');
        var filterStore = document.getElementById('filter_store');

        if (filterStatus) {
            try {
                if (filterStatus.tomselect) {
                    filterStatus.tomselect.destroy();
                }
                if (filterStatus.choicesInstance) {
                    try { filterStatus.choicesInstance.destroy(); } catch (e) {}
                }
                createChoicesInstance(filterStatus, {
                    allowEmptyOption: true,
                    dropdownParent: 'body',
                    placeholder: 'All Status',
                    searchField: ['text'],
                    controlInput: '<input>',
                    highlight: false,
                    removeItemButton: true,
                    exactWordTokenSearch: true,
                    searchFields: ['label'],
                    searchPlaceholderValue: 'Search status...',
                    onInitialize: function () {
                        this.activeOption = null;
                    },
                    onDropdownOpen: function (dropdown) {
                        var self = this;
                        function clearInputAndCursor() {
                            var input = self.control_input || (dropdown && dropdown.querySelector('input'));
                            if (typeof self.setTextboxValue === 'function') self.setTextboxValue('');
                            if (typeof self.onSearchChange === 'function') self.onSearchChange('');
                            if (typeof self.refreshOptions === 'function') self.refreshOptions(false);
                            if (input) {
                                input.value = '';
                                safeFocus(input);
                                try { input.setSelectionRange(0, 0); } catch (e) {}
                                input.scrollLeft = 0;
                            }
                        }
                        // selection + search dono ko blank karo har open par
                        self.clear(true);
                        clearInputAndCursor();
                        setTimeout(function () {
                            self.clear(true);
                            clearInputAndCursor();
                        }, 0);
                        setTimeout(function () {
                            self.clear(true);
                            clearInputAndCursor();
                        }, 50);
                        setTimeout(function () {
                            self.clear(true);
                            clearInputAndCursor();
                        }, 100);
                        setTimeout(function () {
                            var opts = dropdown.querySelectorAll('.option.active, .option.selected, .option[aria-selected="true"]');
                            opts.forEach(function (opt) {
                                opt.classList.remove('active');
                                opt.classList.remove('selected');
                                opt.setAttribute('aria-selected', 'false');
                            });
                        }, 0);
                    }
                });
            } catch (e) {
                console.error('Choices initialization failed for status filter:', e);
            }
        }

        if (filterStore) {
            try {
                if (filterStore.tomselect) {
                    filterStore.tomselect.destroy();
                }
                if (filterStore.choicesInstance) {
                    try { filterStore.choicesInstance.destroy(); } catch (e) {}
                }
                createChoicesInstance(filterStore, {
                    allowEmptyOption: true,
                    dropdownParent: 'body',
                    placeholder: 'All Stores',
                    searchField: ['text'],
                    controlInput: '<input>',
                    highlight: false,
                    removeItemButton: true,
                    exactWordTokenSearch: true,
                    searchFields: ['label'],
                    searchPlaceholderValue: 'Search store...',
                    onInitialize: function () {
                        this.activeOption = null;
                    },
                    onDropdownOpen: function (dropdown) {
                        var self = this;
                        function clearInputAndCursor() {
                            var input = self.control_input || (dropdown && dropdown.querySelector('input'));
                            if (typeof self.setTextboxValue === 'function') self.setTextboxValue('');
                            if (typeof self.onSearchChange === 'function') self.onSearchChange('');
                            if (typeof self.refreshOptions === 'function') self.refreshOptions(false);
                            if (input) {
                                input.value = '';
                                safeFocus(input);
                                try { input.setSelectionRange(0, 0); } catch (e) {}
                                input.scrollLeft = 0;
                            }
                        }
                        // selection + search dono ko blank karo har open par
                        self.clear(true);
                        clearInputAndCursor();
                        setTimeout(function () {
                            self.clear(true);
                            clearInputAndCursor();
                        }, 0);
                        setTimeout(function () {
                            self.clear(true);
                            clearInputAndCursor();
                        }, 50);
                        setTimeout(function () {
                            self.clear(true);
                            clearInputAndCursor();
                        }, 100);
                        setTimeout(function () {
                            var opts = dropdown.querySelectorAll('.option.active, .option.selected, .option[aria-selected="true"]');
                            opts.forEach(function (opt) {
                                opt.classList.remove('active');
                                opt.classList.remove('selected');
                                opt.setAttribute('aria-selected', 'false');
                            });
                        }, 0);
                    }
                });
            } catch (e) {
                console.error('Choices initialization failed for store filter:', e);
            }
        }
    } else {
        console.warn('Choices.js library not loaded on Selling Voucher page');
    }

    function destroyAddModalTomSelects() {
        // Destroy tracked instances (payment, client, store, item selects only)
        if (addModalTomSelectInstances.payment) {
            try { addModalTomSelectInstances.payment.destroy(); } catch (e) {}
            addModalTomSelectInstances.payment = null;
        }
        if (addModalTomSelectInstances.client) {
            try { addModalTomSelectInstances.client.destroy(); } catch (e) {}
            addModalTomSelectInstances.client = null;
        }
        if (addModalTomSelectInstances.store) {
            try { addModalTomSelectInstances.store.destroy(); } catch (e) {}
            addModalTomSelectInstances.store = null;
        }
        document.querySelectorAll('#addSellingVoucherModal select').forEach(function(el) {
            if (el.tomselect) {
                try { el.tomselect.destroy(); } catch (e) {}
            }
            el.tomselect = null;
            el.choicesInstance = null;
        });
    }

    function destroyEditModalTomSelects() {
        // Destroy tracked instances for Edit modal
        if (editModalTomSelectInstances.payment) {
            try { editModalTomSelectInstances.payment.destroy(); } catch (e) {}
            editModalTomSelectInstances.payment = null;
        }
        if (editModalTomSelectInstances.client) {
            try { editModalTomSelectInstances.client.destroy(); } catch (e) {}
            editModalTomSelectInstances.client = null;
        }
        if (editModalTomSelectInstances.store) {
            try { editModalTomSelectInstances.store.destroy(); } catch (e) {}
            editModalTomSelectInstances.store = null;
        }
        document.querySelectorAll('#editSellingVoucherModal select').forEach(function(el) {
            if (el.tomselect) {
                try { el.tomselect.destroy(); } catch (e) {}
            }
        });
    }

    // Show/hide select (or its Choices wrapper) so only one Name dropdown is visible at a time
    function setSelectVisible(select, visible) {
        if (!select) return;
        var wrapper = null;
        if (select.tomselect && select.tomselect.wrapper) wrapper = select.tomselect.wrapper;
        if (!wrapper && select.parentElement) {
            var p = select.parentElement;
            if (p.classList && p.classList.contains('ts-wrapper')) wrapper = p;
            else if (p.parentElement && p.parentElement.classList && p.parentElement.classList.contains('ts-wrapper')) wrapper = p.parentElement;
        }
        if (wrapper) wrapper.style.display = visible ? '' : 'none';
        else select.style.display = visible ? 'block' : 'none';
    }

    function initAddModalTomSelects() {
        if (typeof Choices === 'undefined') return;
        var modal = document.getElementById('addSellingVoucherModal');
        if (!modal) return;

        var paymentSel = modal.querySelector('select[name="payment_type"]');
        if (paymentSel && !paymentSel.tomselect) {
            addModalTomSelectInstances.payment = createChoicesInstance(paymentSel, createBlankSearchConfig({
                placeholder: 'Payment Type',
                clearOnOpen: true
            }));
        }
        var clientSel = document.getElementById('modalClientNameSelect');
        var addRadio = document.querySelector('#addSellingVoucherModal .client-type-radio:checked');
        var addSlug = addRadio ? (addRadio.value || '').toLowerCase().trim() : 'employee';
        if (clientSel && addSlug !== 'ot' && addSlug !== 'course' && clientNameOptionsAdd.length) {
            rebuildClientNameSelect(clientSel, clientNameOptionsAdd, addSlug);
        } else if (clientSel && !clientSel.tomselect) {
            addModalTomSelectInstances.client = createChoicesInstance(clientSel, createBlankSearchConfig({
                placeholder: 'Select Client Name',
                clearOnOpen: true
            }));
        }
        var storeSel = modal.querySelector('select[name="store_id"]');
        if (storeSel && !storeSel.tomselect) {
            addModalTomSelectInstances.store = createChoicesInstance(storeSel, createBlankSearchConfig({
                placeholder: 'Select Store',
                clearOnOpen: true
            }));
        }
        var nameSelectIds = ['modalFacultySelect', 'modalAcademyStaffSelect', 'modalMessStaffSelect', 'modalOtStudentSelect', 'modalOtCourseSelect', 'modalCourseSelect', 'modalCourseNameSelect'];
        nameSelectIds.forEach(function(id) {
            var sel = document.getElementById(id);
            if (sel && !sel.tomselect) {
                createChoicesInstance(sel, createBlankSearchConfig({
                    placeholder: sel.id.indexOf('Faculty') !== -1 ? 'Select Faculty'
                        : sel.id.indexOf('Academy') !== -1 ? 'Select Academy Staff'
                        : sel.id.indexOf('Mess') !== -1 ? 'Select Mess Staff'
                        : sel.id.indexOf('OtStudent') !== -1 ? 'Select Student'
                        : 'Select Course',
                    clearOnOpen: true
                }));
            }
        });
        modal.querySelectorAll('#modalItemsBody .sv-item-select').forEach(function(select) {
            if (select.tomselect) return;
            var hadValue = !!select.value;
            var ts = createChoicesInstance(select, createItemSelectConfig());
            if (!hadValue && ts) ts.clear(true);
        });
        var clientNameWrap = document.getElementById('modalClientNameWrap');
        var nameFieldWrap = document.getElementById('modalNameFieldWrap');
        var clientTypeChecked = document.querySelector('#addSellingVoucherModal .client-type-radio:checked');
        if (clientNameWrap && nameFieldWrap) {
            if (clientTypeChecked) {
                clientNameWrap.style.display = '';
                nameFieldWrap.style.display = '';
            } else {
                clientNameWrap.style.display = 'none';
                nameFieldWrap.style.display = 'none';
            }
        }
        if (typeof updateModalNameField === 'function') {
            updateModalNameField();
        }
    }

    function initEditModalTomSelects() {
        if (typeof Choices === 'undefined') return;
        var modal = document.getElementById('editSellingVoucherModal');
        if (!modal) return;

        var paymentSel = modal.querySelector('select.edit-payment-type');
        if (paymentSel && !paymentSel.tomselect) {
            editModalTomSelectInstances.payment = createChoicesInstance(paymentSel, createBlankSearchConfig({
                placeholder: 'Payment Type',
                clearOnOpen: true
            }));
        }

        var clientSel = document.getElementById('editClientNameSelect');
        var editRadio = document.querySelector('#editSellingVoucherModal .edit-client-type-radio:checked');
        var editSlug = editRadio ? (editRadio.value || '').toLowerCase().trim() : 'employee';
        if (clientSel && editSlug !== 'ot' && editSlug !== 'course' && clientNameOptionsEdit.length) {
            rebuildClientNameSelect(clientSel, clientNameOptionsEdit, editSlug);
        } else if (clientSel && !clientSel.tomselect) {
            editModalTomSelectInstances.client = createChoicesInstance(clientSel, createBlankSearchConfig({
                placeholder: 'Select Client Name',
                clearOnOpen: true
            }));
        }

        var storeSel = modal.querySelector('select.edit-store');
        if (storeSel && !storeSel.tomselect) {
            editModalTomSelectInstances.store = createChoicesInstance(storeSel, createBlankSearchConfig({
                placeholder: 'Select Store',
                clearOnOpen: true
            }));
        }

        var editNameSelectIds = ['editModalFacultySelect', 'editModalAcademyStaffSelect', 'editModalMessStaffSelect', 'editModalOtCourseSelect', 'editModalCourseSelect', 'editModalCourseNameSelect'];
        editNameSelectIds.forEach(function(id) {
            var sel = document.getElementById(id);
            if (sel && !sel.tomselect) {
                var placeholder = id.indexOf('Faculty') !== -1 ? 'Select Faculty'
                    : id.indexOf('Academy') !== -1 ? 'Select Academy Staff'
                    : id.indexOf('Mess') !== -1 ? 'Select Mess Staff'
                    : 'Select Course';
                createChoicesInstance(sel, createBlankSearchConfig({ placeholder: placeholder, clearOnOpen: true }));
            }
        });
    }

    // After Choices init in Edit modal: show only the active dropdown in Client Name column (hide OT Course / Course when Client Name is active, and vice versa)
    function applyEditModalClientNameColumnVisibility() {
        var radio = document.querySelector('#editSellingVoucherModal .edit-client-type-radio:checked');
        var clientSelect = document.getElementById('editClientNameSelect');
        var otCourseSelect = document.getElementById('editModalOtCourseSelect');
        var editCourseSelect = document.getElementById('editModalCourseSelect');
        if (!radio || !clientSelect) return;
        var isOt = (radio.value || '').toLowerCase() === 'ot';
        var isCourse = (radio.value || '').toLowerCase() === 'course';
        if (isOt) {
            setSelectVisible(clientSelect, false);
            if (otCourseSelect) setSelectVisible(otCourseSelect, true);
            if (editCourseSelect) setSelectVisible(editCourseSelect, false);
        } else if (isCourse) {
            setSelectVisible(clientSelect, false);
            if (otCourseSelect) setSelectVisible(otCourseSelect, false);
            if (editCourseSelect) setSelectVisible(editCourseSelect, true);
        } else {
            setSelectVisible(clientSelect, true);
            if (otCourseSelect) setSelectVisible(otCourseSelect, false);
            if (editCourseSelect) setSelectVisible(editCourseSelect, false);
        }
    }

    // Filter: End Date must not be before Start Date
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

    /** Client Type: force Employee and fix defaultChecked so form.reset() cannot restore OT/Course from initial page HTML. */
    function resetAddModalClientTypeToEmployee(modalEl) {
        if (!modalEl) return;
        var empRadio = null;
        modalEl.querySelectorAll('.client-type-radio').forEach(function(r) {
            var isEmp = String(r.value || '').toLowerCase() === 'employee';
            r.checked = isEmp;
            r.defaultChecked = isEmp;
            if (isEmp) empRadio = r;
        });
        if (empRadio) {
            empRadio.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    /** Transfer From Store: clear selection and set only the empty option as defaultSelected (avoids form.reset() restoring old('store_id')). */
    function resetAddModalStoreSelectToEmpty(modalEl) {
        var storeSel = modalEl && modalEl.querySelector('select[name="store_id"]');
        if (!storeSel) return;
        storeSel.querySelectorAll('option').forEach(function(opt) {
            opt.defaultSelected = String(opt.value) === '';
        });
        storeSel.value = '';
    }

    /** Rebuild Choices + item rows from current `filteredItems` (call after reset and/or fetchStoreItems). */
    function reinitAddSellingVoucherModalItemGrid() {
        initAddModalTomSelects();
        if (typeof updateModalNameField === 'function') updateModalNameField();
        updateAddItemDropdowns();
        refreshAllAvailable();
        document.querySelectorAll('#modalItemsBody .sv-item-row').forEach(function(row) { calcRow(row); });
        updateGrandTotal();
        syncAddModalChoicesToNative();
    }

    // Helper: reset Add Selling Voucher modal form (without closing modal).
    // Keeps modal open; clears fields, item rows, and store-scoped item cache so the next entry starts fresh.
    // @param {boolean} skipDeferredReinit — if true, caller will refetch inventory and call reinitAddSellingVoucherModalItemGrid (e.g. after AJAX save).
    function resetSellingVoucherModalForm(skipDeferredReinit) {
        var modalEl = document.getElementById('addSellingVoucherModal');
        if (!modalEl) return;

        currentStoreId = null;
        filteredItems = itemSubcategories;

        destroyAddModalTomSelects();

        var form = document.getElementById('sellingVoucherModalForm');
        if (form) {
            form.reset();
            form.classList.remove('was-validated');
            form.querySelectorAll('.is-invalid').forEach(function(el) { el.classList.remove('is-invalid'); });
        }
        modalEl.querySelectorAll('.modal-body .alert.alert-danger').forEach(function(a) { a.remove(); });
        resetAddModalStoreSelectToEmpty(modalEl);
        var issueDateInp = modalEl.querySelector('input[name="issue_date"]');
        if (issueDateInp) issueDateInp.value = new Date().toISOString().slice(0, 10);
        var paymentSel = modalEl.querySelector('select[name="payment_type"]');
        if (paymentSel) paymentSel.value = '1';
        // Clear client / name UI on native selects BEFORE firing client-type change (rebuildClientNameSelect preserves current value).
        var clientPkSel = modalEl.querySelector('#modalClientNameSelect');
        if (clientPkSel) clientPkSel.value = '';
        var clientNameInp = document.getElementById('modalClientNameInput');
        if (clientNameInp) clientNameInp.value = '';
        modalEl.querySelectorAll('#modalClientNameWrap select, #modalNameFieldWrap select').forEach(function(s) {
            if (s && typeof s.value !== 'undefined') s.value = '';
        });
        resetAddModalClientTypeToEmployee(modalEl);
        var billInput = document.getElementById('addSvBillFileInput');
        if (billInput) billInput.value = '';
        var billWrap = document.getElementById('addSvBillFileChosenWrap');
        var billName = document.getElementById('addSvBillFileChosenName');
        if (billWrap) billWrap.classList.add('d-none');
        if (billName) billName.textContent = '';
        var tbody = document.getElementById('modalItemsBody');
        if (tbody) {
            tbody.innerHTML = getRowHtml(0);
            rowIndex = 1;
            updateRemoveButtons();
        }
        var grandTotalEl = document.getElementById('modalGrandTotal');
        if (grandTotalEl) grandTotalEl.textContent = '₹0.00';

        if (skipDeferredReinit) {
            return;
        }

        // Modal stays open after AJAX save; re-init dropdowns and item grid (defer so DOM + destroy settle).
        window.requestAnimationFrame(function () {
            window.setTimeout(function () {
                try {
                    reinitAddSellingVoucherModalItemGrid();
                } catch (err) {
                    console.error('resetSellingVoucherModalForm re-init failed', err);
                }
            }, 10);
        });
    }

    /** Force Choices.js UI to match underlying <select> values (fixes stale labels after reset). */
    function syncAddModalChoicesToNative() {
        var modal = document.getElementById('addSellingVoucherModal');
        if (!modal) return;
        modal.querySelectorAll('select').forEach(function(sel) {
            if (!sel.tomselect || typeof sel.tomselect.clear !== 'function') return;
            try {
                var v = sel.value;
                if (v === null || v === undefined || v === '') {
                    sel.tomselect.clear();
                } else {
                    sel.tomselect.setValue(String(v));
                }
            } catch (e) {}
        });
    }

    // After AJAX save (add/edit), refresh the listing DataTable so new rows show immediately.
    // This fetches the current page HTML and swaps DataTable rows (preserves search/paging).
    var isRefreshingSellingVouchersTable = false;
    function refreshSellingVouchersTable() {
        if (isRefreshingSellingVouchersTable) return;
        if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable) return;

        var $ = window.jQuery;
        var $table = $('#sellingVouchersTable');
        if (!$table.length || !$.fn.DataTable.isDataTable($table)) return;

        var dt = $table.DataTable();
        var expectedCols = $table.find('thead tr:first th').length;
        var url = window.location.pathname + window.location.search;

        isRefreshingSellingVouchersTable = true;

        fetch(url, { headers: { 'Accept': 'text/html' } })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var newTbody = doc.querySelector('#sellingVouchersTable tbody');
                if (!newTbody) return;

                var newRowData = [];
                newTbody.querySelectorAll('tr').forEach(function(tr) {
                    var cells = Array.from(tr.querySelectorAll('td,th'));
                    if (expectedCols && cells.length !== expectedCols) return; // skip colspan/empty rows
                    newRowData.push(cells.map(function(td) { return td.innerHTML; }));
                });

                dt.clear();
                if (newRowData.length) dt.rows.add(newRowData);
                dt.draw(false);
            })
            .catch(function(err) {
                console.error('Failed to refresh selling vouchers table', err);
            })
            .finally(function() {
                isRefreshingSellingVouchersTable = false;
            });
    }

    // Prevent double submit on Add Selling Voucher form (stops double entry) + AJAX submit
    var sellingVoucherModalForm = document.getElementById('sellingVoucherModalForm');
    if (sellingVoucherModalForm) {
        sellingVoucherModalForm.addEventListener('submit', function(e) {
            // If invalid, the capture validation listener will have prevented default.
            if (!this.checkValidity()) return;

            e.preventDefault();

            var form = this;
            var btn = form.querySelector('button[type="submit"]');
            if (btn && btn.disabled) return;
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
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf ? csrf.value : '',
                    Accept: 'application/json, text/javascript, */*;q=0.01'
                },
                body: formData
            })
                .then(function(response) {
                    return response.text().then(function(text) {
                        var data = null;
                        if (text) {
                            try {
                                data = JSON.parse(text);
                            } catch (e) {
                                data = null;
                            }
                        }
                        return {
                            ok: response.ok,
                            status: response.status,
                            data: data,
                            raw: text
                        };
                    });
                })
                .then(function(res) {
                    var data = res.data;
                    var success = !!(data && (data.success === true || data.success === 1 || data.success === '1' || data.voucher_id != null));
                    if (res.ok && success) {
                        var modalRoot = document.getElementById('addSellingVoucherModal');
                        var storeSel = modalRoot ? modalRoot.querySelector('select[name="store_id"]') : null;
                        var savedStoreId = '';
                        if (storeSel) {
                            if (storeSel.tomselect && typeof storeSel.tomselect.getValue === 'function') {
                                var gv = storeSel.tomselect.getValue();
                                savedStoreId = Array.isArray(gv) ? (gv[0] || '') : (gv == null ? '' : String(gv));
                            } else {
                                savedStoreId = storeSel.value || '';
                            }
                        }

                        resetSellingVoucherModalForm(true);

                        function finishAddModalAfterSave() {
                            try {
                                reinitAddSellingVoucherModalItemGrid();
                            } catch (err) {
                                console.error('reinit after save failed', err);
                            }
                            var body = modalRoot && modalRoot.querySelector('.modal-body');
                            if (body) body.scrollTop = 0;
                        }

                        if (savedStoreId) {
                            if (storeSel) {
                                storeSel.value = String(savedStoreId);
                            }
                            currentStoreId = String(savedStoreId);
                            fetchStoreItems(String(savedStoreId), function() {
                                finishAddModalAfterSave();
                            });
                        } else {
                            currentStoreId = null;
                            filteredItems = itemSubcategories;
                            window.requestAnimationFrame(function() {
                                window.setTimeout(finishAddModalAfterSave, 10);
                            });
                        }

                        refreshSellingVouchersTable();
                        if (window.toastr && data.message) {
                            toastr.success(data.message);
                        } else if (data.message) {
                            alert(data.message);
                        }
                    } else {
                        var msg = (data && data.message) ? data.message : 'Failed to save voucher. Please try again.';
                        if (res.status === 422 && data && data.errors) {
                            try {
                                var firstKey = Object.keys(data.errors)[0];
                                if (firstKey && data.errors[firstKey] && data.errors[firstKey][0]) {
                                    msg = data.errors[firstKey][0];
                                }
                            } catch (e) {}
                        }
                        if (!data && res.raw && res.raw.indexOf('<!DOCTYPE') !== -1) {
                            msg = 'Server returned a page instead of JSON. Try refreshing the page or check your session.';
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

    // Prevent double submit on Edit Selling Voucher form
    var editSellingVoucherForm = document.getElementById('editSellingVoucherForm');
    if (editSellingVoucherForm) {
        editSellingVoucherForm.addEventListener('submit', function() {
            var btn = this.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.textContent = 'Updating...';
            }
        });
    }
    
    let itemSubcategories = @json($itemSubcategories);
    let filteredItems = itemSubcategories;
    const editSvBaseUrl = "{{ url('admin/mess/material-management') }}";
    const viewSvBaseUrl = "{{ url('admin/mess/material-management') }}";
    const returnSvBaseUrl = "{{ url('admin/mess/material-management') }}";
    let rowIndex = 1;
    let editRowIndex = 0;
    let currentStoreId = null;
    let editCurrentStoreId = null;

    function enforceQtyWithinAvailable(row) {
        if (!row) return;
        const availEl = row.querySelector('.sv-avail');
        const qtyEl = row.querySelector('.sv-qty');
        if (!availEl || !qtyEl) return;

        let avail = parseFloat(availEl.value) || 0;
        const qtyRaw = qtyEl.value;
        const qty = parseFloat(qtyRaw);

        // In edit modal: effective available = current stock + this row's original issue qty
        // (so saving without changes does not fail when current stock already reflects the voucher)
        const isEditRow = row.closest('#editModalItemsBody') !== null;
        const originalQty = isEditRow ? (parseFloat(row.getAttribute('data-original-qty')) || 0) : 0;
        const effectiveAvail = isEditRow ? (avail + originalQty) : avail;

        // Keep browser constraint in sync
        qtyEl.max = String(effectiveAvail);

        // If empty, don't force an error yet
        if (qtyRaw === '' || Number.isNaN(qty)) {
            qtyEl.setCustomValidity('');
            qtyEl.classList.remove('is-invalid');
            return;
        }

        if (qty > effectiveAvail) {
            qtyEl.setCustomValidity('Issue Qty cannot exceed Available Qty.');
            qtyEl.classList.add('is-invalid');
        } else {
            qtyEl.setCustomValidity('');
            qtyEl.classList.remove('is-invalid');
        }
    }

    function getBaseAvailableForItem(itemId) {
        if (!itemId) return 0;
        const item = filteredItems.find(function(i) { return String(i.id) === String(itemId); });
        return item ? (parseFloat(item.available_quantity) || 0) : 0;
    }

    function refreshAllAvailable() {
        const rows = document.querySelectorAll('#modalItemsBody .sv-item-row');
        const usedByItem = {};

        rows.forEach(function(row) {
            const select = row.querySelector('.sv-item-select');
            const itemId = select ? select.value : '';
            const availInp = row.querySelector('.sv-avail');
            const leftInp = row.querySelector('.sv-left');
            if (!itemId || !availInp) return;

            const base = getBaseAvailableForItem(itemId);
            const alreadyUsed = usedByItem[itemId] || 0;
            const availableForRow = Math.max(0, base - alreadyUsed);

            availInp.value = availableForRow.toFixed(2);

            const qty = parseFloat(row.querySelector('.sv-qty').value) || 0;
            if (leftInp) {
                leftInp.value = Math.max(0, availableForRow - qty).toFixed(2);
            }

            usedByItem[itemId] = alreadyUsed + qty;
            enforceQtyWithinAvailable(row);
        });
    }

    function fetchStoreItems(storeId, callback) {
        if (!storeId) {
            filteredItems = itemSubcategories;
            if (callback) callback();
            return;
        }
        
        fetch(editSvBaseUrl + '/store/' + storeId + '/items', {
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
            filteredItems = itemSubcategories || [];
            if (callback) callback();
        });
    }

    function updateAddItemDropdowns() {
        const rows = document.querySelectorAll('#modalItemsBody .sv-item-row');
        console.log('Updating dropdowns, found rows:', rows.length); // Debug log
        rows.forEach(row => {
            const select = row.querySelector('.sv-item-select');
            if (!select) return;
            if (select.tomselect) {
                select.tomselect.destroy();
            }
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
            if (typeof Choices !== 'undefined') {
                createChoicesInstance(select, createItemSelectConfig());
            }
            updateUnit(row);
        });
    }

    function getRowHtml(index) {
        const options = filteredItems.map(s => {
            let attrs = 'data-unit="' + (s.unit_measurement || '').replace(/"/g, '&quot;') + '" data-rate="' + (s.standard_cost || 0) + '" data-available="' + (s.available_quantity || 0) + '"';
            if (s.price_tiers && s.price_tiers.length > 0) {
                attrs += ' data-price-tiers="' + (JSON.stringify(s.price_tiers) || '').replace(/"/g, '&quot;') + '"';
            }
            return '<option value="' + s.id + '" ' + attrs + '>' + (s.item_name || '—').replace(/</g, '&lt;') + '</option>';
        }).join('');
        return '<tr class="sv-item-row">' +
            '<td><select name="items[' + index + '][item_subcategory_id]" class="form-select form-select-sm sv-item-select" required><option value="">Select Item</option>' + options + '</select></td>' +
            '<td><input type="text" name="items[' + index + '][unit]" class="form-control  sv-unit" readonly placeholder="—"></td>' +
            '<td><input type="text" name="items[' + index + '][available_quantity]" class="form-control  sv-avail bg-light" readonly></td>' +
            '<td><input type="text" name="items[' + index + '][quantity]" class="form-control  sv-qty" step="0.01" min="0.01" required><div class="invalid-feedback">Issue Qty cannot exceed Available Qty.</div></td>' +
            '<td><input type="text" class="form-control  sv-left bg-light" readonly></td>' +
            '<td><input type="text" name="items[' + index + '][rate]" class="form-control  sv-rate" step="0.01" min="0" required></td>' +
            '<td><input type="text" class="form-control  sv-total bg-light" readonly placeholder="0.00"></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger sv-remove-row" title="Remove">×</button></td>' +
            '</tr>';
    }

    function updateUnit(row) {
        const sel = row.querySelector('.sv-item-select');
        const opt = sel && sel.options[sel.selectedIndex];
        const unitInp = row.querySelector('.sv-unit');
        const rateInp = row.querySelector('.sv-rate');
        const availInp = row.querySelector('.sv-avail');
        if (unitInp) unitInp.value = opt && opt.dataset.unit ? opt.dataset.unit : '';
        // Only auto-set rate if user has not manually overridden it
        if (rateInp && rateInp.dataset.manualRate !== '1' && opt && opt.dataset.rate) {
            rateInp.value = opt.dataset.rate;
        }
        if (availInp && opt && opt.dataset.available) availInp.value = opt.dataset.available;
        if (availInp) availInp.readOnly = true;
        if (row.closest('#editModalItemsBody')) {
            refreshEditAllAvailable();
        } else {
            refreshAllAvailable();
        }
        enforceQtyWithinAvailable(row);
    }

    function calcFifoAmount(tiers, qty) {
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

    function calcRow(row) {
        const avail = parseFloat(row.querySelector('.sv-avail').value) || 0;
        const qty = parseFloat(row.querySelector('.sv-qty').value) || 0;
        const rateInp = row.querySelector('.sv-rate');
        let rate = parseFloat(rateInp.value) || 0;
        const isManualRate = rateInp && rateInp.dataset.manualRate === '1';
        const sel = row.querySelector('.sv-item-select');
        const opt = sel && sel.options[sel.selectedIndex];
        const tiersJson = opt && opt.getAttribute('data-price-tiers');
        const tiers = tiersJson ? (function(){ try { return JSON.parse(tiersJson); } catch(e) { return null; } })() : null;
        let total;
        if (!isManualRate && tiers && tiers.length > 0 && qty > 0) {
            const fifoAmount = calcFifoAmount(tiers, qty);
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
        const left = Math.max(0, avail - qty);
        row.querySelector('.sv-left').value = left;
        row.querySelector('.sv-total').value = (total || 0).toFixed(2);
        enforceQtyWithinAvailable(row);
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

    // Store selection change in ADD modal
    const addModalStoreSelect = document.querySelector('#addSellingVoucherModal select[name="store_id"]');
    if (addModalStoreSelect) {
        addModalStoreSelect.addEventListener('change', function() {
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

    function appendModalItemRow() {
        const tbody = document.getElementById('modalItemsBody');
        if (!tbody) return;
        tbody.insertAdjacentHTML('beforeend', getRowHtml(rowIndex));
        rowIndex++;
        updateRemoveButtons();

        var newRow = tbody.querySelector('.sv-item-row:last-child');
        var newSelect = newRow ? newRow.querySelector('.sv-item-select') : null;
        if (newSelect && typeof Choices !== 'undefined') {
            createChoicesInstance(newSelect, createItemSelectConfig());
        }
    }

    function appendEditModalItemRow() {
        const tbody = document.getElementById('editModalItemsBody');
        if (!tbody) return;
        tbody.insertAdjacentHTML('beforeend', getEditRowHtml(editRowIndex, null));
        editRowIndex++;
        const newRow = tbody.querySelector('.sv-item-row:last-child');
        const newSelect = newRow ? newRow.querySelector('.sv-item-select') : null;
        if (newSelect && typeof Choices !== 'undefined') {
            if (newSelect.tomselect) {
                try { newSelect.tomselect.destroy(); } catch (e) {}
            }
            createChoicesInstance(newSelect, createEditModalItemSelectConfig());
        }
        updateEditRemoveButtons();
        refreshEditAllAvailable();
        updateEditGrandTotal();
    }

    /** Enter appends item row everywhere in modal except Choices dropdowns, buttons/links, and submit controls. */
    function svEnterShouldAppendItemRow(modalEl, activeEl) {
        if (!modalEl || !activeEl || !modalEl.contains(activeEl)) return false;
        if (activeEl.tagName === 'TEXTAREA') return false;
        if (activeEl.closest('button, a')) return false;
        if (activeEl.closest('.choices')) return false;
        if (activeEl.matches && activeEl.matches('select')) return false;
        if (activeEl.tagName === 'INPUT') {
            var it = (activeEl.type || '').toLowerCase();
            if (it === 'submit' || it === 'button' || it === 'reset' || it === 'image') return false;
        }
        return true;
    }

    const modalAddItemBtn = document.getElementById('modalAddItemRow');
    if (modalAddItemBtn) {
        modalAddItemBtn.addEventListener('click', function() {
            appendModalItemRow();
        });
    }

    const modalItemsBody = document.getElementById('modalItemsBody');
    const addSvModal = document.getElementById('addSellingVoucherModal');
    if (modalItemsBody) {
        modalItemsBody.addEventListener('change', function(e) {
            if (e.target.classList.contains('sv-item-select')) {
                const row = e.target.closest('.sv-item-row');
                if (row) {
                    const rateInp = row.querySelector('.sv-rate');
                    if (rateInp) rateInp.dataset.manualRate = '';
                    updateUnit(row);
                    calcRow(row);
                    updateGrandTotal();
                }
            }
        });

        modalItemsBody.addEventListener('input', function(e) {
            if (e.target.classList.contains('sv-avail') || e.target.classList.contains('sv-qty') || e.target.classList.contains('sv-rate')) {
                const row = e.target.closest('.sv-item-row');
                if (row) {
                    if (e.target.classList.contains('sv-rate')) {
                        const rateInp = row.querySelector('.sv-rate');
                        if (rateInp) rateInp.dataset.manualRate = '1';
                    }
                    refreshAllAvailable();
                    enforceQtyWithinAvailable(row);
                    calcRow(row);
                    updateGrandTotal();
                }
            }
        });

        modalItemsBody.addEventListener('click', function(e) {
            if (e.target.classList.contains('sv-remove-row')) {
                const row = e.target.closest('.sv-item-row');
                if (row && document.querySelectorAll('#modalItemsBody .sv-item-row').length > 1) {
                    row.remove();
                    refreshAllAvailable();
                    updateGrandTotal();
                    updateRemoveButtons();
                }
                return;
            }
        });
    }

    // Delegate input/change from modal so qty/rate updates always run (Left Qty + Total)
    if (addSvModal) {
        function onAddModalQtyOrRateInput(e) {
            if (!e.target.matches('.sv-avail, .sv-qty, .sv-rate')) return;
            const row = e.target.closest('.sv-item-row');
            if (!row) return;
            refreshAllAvailable();
            calcRow(row);
            updateGrandTotal();
        }
        addSvModal.addEventListener('input', onAddModalQtyOrRateInput);
        addSvModal.addEventListener('change', onAddModalQtyOrRateInput);
    }

    // Enter (outside Choices + buttons): append item row anywhere in Add modal; prevents accidental form submit
    if (addSvModal) {
        addSvModal.addEventListener('keydown', function(e) {
            if (e.key !== 'Enter') return;
            var activeEl = document.activeElement;
            if (!svEnterShouldAppendItemRow(addSvModal, activeEl)) return;
            e.preventDefault();
            appendModalItemRow();
        });
    }

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

        // Pehle high-level Client Name / OT Course / Course select ko control karo
        if (isOt) {
            // OT: sirf OT Course + OT Student dikhna chahiye
            setSelectVisible(clientNameSelect, false);
            if (courseSelect) setSelectVisible(courseSelect, false);
            if (otStudentSelect) { setSelectVisible(otStudentSelect, true); }
            const otCourseSelect = document.getElementById('modalOtCourseSelect');
            if (otCourseSelect) setSelectVisible(otCourseSelect, true);
            nameInput.style.display = 'none';
            nameInput.removeAttribute('required');
        } else if (isCourse) {
            // Course: sirf Course select + text Name field
            setSelectVisible(clientNameSelect, false);
            const otCourseSelect = document.getElementById('modalOtCourseSelect');
            if (otCourseSelect) setSelectVisible(otCourseSelect, false);
            if (otStudentSelect) { setSelectVisible(otStudentSelect, false); }
            if (courseSelect) setSelectVisible(courseSelect, true);
            nameInput.style.display = 'block';
            nameInput.placeholder = 'Course name';
            nameInput.setAttribute('required', 'required');
        } else {
            // Employee / Section / Other: sirf Client Name + (Faculty/Staff/Mess) dropdown ya text field
            setSelectVisible(clientNameSelect, true);
            const otCourseSelect = document.getElementById('modalOtCourseSelect');
            if (otCourseSelect) setSelectVisible(otCourseSelect, false);
            if (otStudentSelect) { setSelectVisible(otStudentSelect, false); }
            if (courseSelect) setSelectVisible(courseSelect, false);
            nameInput.style.display = showAny ? 'none' : 'block';
        }

        // Ab niche ke detailed faculty/academy/mess/course-name dropdowns handle karo
        [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) {
            if (!sel) return;
            const show = sel === facultySelect ? showFaculty : (sel === academyStaffSelect ? showAcademyStaff : showMessStaff);
            setSelectVisible(sel, show);
            sel.removeAttribute('required');
            if (show) {
                sel.setAttribute('required', 'required');
                sel.value = nameInput.value || '';
                if (sel.value) nameInput.value = sel.value;
            } else {
                sel.value = '';
            }
        });
        if (otStudentSelect) { setSelectVisible(otStudentSelect, isOt); if (!isOt) { otStudentSelect.value = ''; otStudentSelect.removeAttribute('required'); } }
        if (courseNameSelect) { setSelectVisible(courseNameSelect, false); courseNameSelect.value = ''; courseNameSelect.removeAttribute('required'); }
        if (!showAny && !isOt && !isCourse) {
            nameInput.setAttribute('required', 'required');
        }
    }

    function loadAddModalGenericBuyerNames() {
        const clientTypeRadio = document.querySelector('#addSellingVoucherModal .client-type-radio:checked');
        const clientNameSelect = document.getElementById('modalClientNameSelect');
        const nameInput = document.getElementById('modalClientNameInput');
        const dataList = document.getElementById('modalGenericBuyerNames');
        if (!clientTypeRadio || !clientNameSelect || !nameInput || !dataList) return;

        const slug = (clientTypeRadio.value || '').toLowerCase();
        if (slug !== 'section' && slug !== 'other') {
            nameInput.removeAttribute('list');
            dataList.innerHTML = '';
            return;
        }

        const pk = clientNameSelect.value || '';
        nameInput.setAttribute('list', 'modalGenericBuyerNames');
        dataList.innerHTML = '';
        if (!pk) return;

        fetch(editSvBaseUrl + '/buyer-names?client_type_slug=' + encodeURIComponent(slug) + '&client_type_pk=' + encodeURIComponent(pk), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(r => r.json())
            .then(function(data) {
                dataList.innerHTML = '';
                (data.buyers || []).forEach(function(b) {
                    const opt = document.createElement('option');
                    opt.value = b;
                    dataList.appendChild(opt);
                });
            })
            .catch(function() {
                dataList.innerHTML = '';
            });
    }
    document.querySelectorAll('#addSellingVoucherModal .client-type-radio').forEach(function(radio) {
        radio.addEventListener('change', function() {
            // Show Client Name & Name columns as soon as a Client Type is selected
            var clientNameWrap = document.getElementById('modalClientNameWrap');
            var nameFieldWrap = document.getElementById('modalNameFieldWrap');
            if (clientNameWrap) clientNameWrap.style.display = '';
            if (nameFieldWrap) nameFieldWrap.style.display = '';
            const isOt = (this.value || '').toLowerCase() === 'ot';
            const isCourse = (this.value || '').toLowerCase() === 'course';
            const clientSelect = document.getElementById('modalClientNameSelect');
            const otCourseSelect = document.getElementById('modalOtCourseSelect');
            const otStudentSelect = document.getElementById('modalOtStudentSelect');
            const courseSelect = document.getElementById('modalCourseSelect');
            const courseNameSelect = document.getElementById('modalCourseNameSelect');
            const nameInput = document.getElementById('modalClientNameInput');
            if (isOt) {
                if (clientSelect) { setSelectVisible(clientSelect, false); clientSelect.removeAttribute('required'); clientSelect.value = ''; clientSelect.removeAttribute('name'); }
                if (otCourseSelect) { setSelectVisible(otCourseSelect, true); otCourseSelect.setAttribute('required', 'required'); otCourseSelect.setAttribute('name', 'client_type_pk'); otCourseSelect.value = ''; }
                if (otStudentSelect) { setSelectVisible(otStudentSelect, true); otStudentSelect.innerHTML = '<option value="">Select course first</option>'; otStudentSelect.setAttribute('required', 'required'); otStudentSelect.value = ''; }
                if (courseSelect) { setSelectVisible(courseSelect, false); courseSelect.removeAttribute('required'); courseSelect.removeAttribute('name'); courseSelect.value = ''; }
                if (courseNameSelect) { setSelectVisible(courseNameSelect, false); courseNameSelect.removeAttribute('required'); courseNameSelect.value = ''; }
                if (nameInput) { nameInput.style.display = 'none'; nameInput.value = ''; nameInput.removeAttribute('required'); }
            } else if (isCourse) {
                if (clientSelect) { setSelectVisible(clientSelect, false); clientSelect.removeAttribute('required'); clientSelect.value = ''; clientSelect.removeAttribute('name'); }
                if (otCourseSelect) { setSelectVisible(otCourseSelect, false); otCourseSelect.removeAttribute('required'); otCourseSelect.removeAttribute('name'); otCourseSelect.value = ''; }
                if (otStudentSelect) { setSelectVisible(otStudentSelect, false); otStudentSelect.removeAttribute('required'); otStudentSelect.innerHTML = '<option value="">Select Student</option>'; otStudentSelect.value = ''; }
                if (courseSelect) { setSelectVisible(courseSelect, true); courseSelect.setAttribute('required', 'required'); courseSelect.setAttribute('name', 'client_type_pk'); courseSelect.value = ''; }
                if (courseNameSelect) { setSelectVisible(courseNameSelect, false); courseNameSelect.removeAttribute('required'); courseNameSelect.value = ''; }
                if (nameInput) {
                    nameInput.style.display = 'block';
                    nameInput.value = '';
                    nameInput.placeholder = 'Name';
                    nameInput.setAttribute('required', 'required');
                    nameInput.setAttribute('list', 'modalCourseBuyerNames');
                }
                const dl = document.getElementById('modalCourseBuyerNames');
                if (dl) dl.innerHTML = '';
            } else {
                if (clientSelect) { setSelectVisible(clientSelect, true); clientSelect.setAttribute('required', 'required'); clientSelect.setAttribute('name', 'client_type_pk'); }
                if (otCourseSelect) { setSelectVisible(otCourseSelect, false); otCourseSelect.removeAttribute('required'); otCourseSelect.removeAttribute('name'); otCourseSelect.value = ''; }
                if (otStudentSelect) { setSelectVisible(otStudentSelect, false); otStudentSelect.removeAttribute('required'); otStudentSelect.innerHTML = '<option value="">Select Student</option>'; otStudentSelect.value = ''; }
                if (courseSelect) { setSelectVisible(courseSelect, false); courseSelect.removeAttribute('required'); courseSelect.value = ''; }
                if (courseNameSelect) { setSelectVisible(courseNameSelect, false); courseNameSelect.removeAttribute('required'); courseNameSelect.value = ''; }
                if (clientSelect && clientNameOptionsAdd.length) {
                    rebuildClientNameSelect(clientSelect, clientNameOptionsAdd, this.value);
                }
                if (nameInput) {
                    nameInput.style.display = 'block';
                    nameInput.placeholder = 'Client / section / role name';
                    nameInput.setAttribute('required', 'required');
                    nameInput.removeAttribute('list');
                }
                const dl = document.getElementById('modalCourseBuyerNames');
                if (dl) dl.innerHTML = '';
            }
            updateModalNameField();
            loadAddModalGenericBuyerNames();
        });
    });
    function reinitNameSelectTomSelect(select, placeholder) {
        if (!select || typeof Choices === 'undefined') return;
        if (select.tomselect) {
            try { select.tomselect.destroy(); } catch (e) {}
        }
        createChoicesInstance(select, createBlankSearchConfig({
            placeholder: placeholder || 'Select',
            clearOnOpen: false
        }));
    }
    const modalOtCourseSelect = document.getElementById('modalOtCourseSelect');
    if (modalOtCourseSelect) {
        modalOtCourseSelect.addEventListener('change', function() {
            const coursePk = this.value;
            const otStudentSelect = document.getElementById('modalOtStudentSelect');
            const nameInput = document.getElementById('modalClientNameInput');
            if (!otStudentSelect || !nameInput) return;
            if (otStudentSelect.tomselect) { try { otStudentSelect.tomselect.destroy(); } catch (e) {} }
            otStudentSelect.innerHTML = '<option value="">Loading...</option>';
            otStudentSelect.value = '';
            const selectedOpt = this.options[this.selectedIndex];
            nameInput.value = (selectedOpt && selectedOpt.dataset.courseName) ? selectedOpt.dataset.courseName : '';
            if (!coursePk) {
                otStudentSelect.innerHTML = '<option value="">Select course first</option>';
                reinitNameSelectTomSelect(otStudentSelect, 'Select Student');
                setSelectVisible(otStudentSelect, true);
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
                    reinitNameSelectTomSelect(otStudentSelect, 'Select Student');
                    setSelectVisible(otStudentSelect, true);
                })
                .catch(function() {
                    otStudentSelect.innerHTML = '<option value="">Error loading students</option>';
                    reinitNameSelectTomSelect(otStudentSelect, 'Select Student');
                    setSelectVisible(otStudentSelect, true);
                });
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
            const coursePk = this.value;
            const nameInput = document.getElementById('modalClientNameInput');
            const dataList = document.getElementById('modalCourseBuyerNames');
            if (!nameInput || !dataList) return;

            nameInput.setAttribute('list', 'modalCourseBuyerNames');
            dataList.innerHTML = '';

            if (!coursePk) return;
            fetch(editSvBaseUrl + '/buyer-names?client_type_slug=course&client_type_pk=' + encodeURIComponent(coursePk), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(function(data) {
                    dataList.innerHTML = '';
                    (data.buyers || []).forEach(function(b) {
                        const opt = document.createElement('option');
                        opt.value = b;
                        dataList.appendChild(opt);
                    });
                })
                .catch(function() {
                    dataList.innerHTML = '';
                });
        });
    }
    
    const modalClientNameSelect = document.getElementById('modalClientNameSelect');
    if (modalClientNameSelect) {
        modalClientNameSelect.addEventListener('change', function() {
            updateModalNameField();
            loadAddModalGenericBuyerNames();
        });
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
        if (isOt) {
            nameInput.style.display = 'block';
            nameInput.readOnly = true;
            nameInput.removeAttribute('required');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) { if (sel) { setSelectVisible(sel, false); sel.value = ''; sel.removeAttribute('required'); } });
            if (editCourseSelect) { setSelectVisible(editCourseSelect, false); editCourseSelect.value = ''; editCourseSelect.removeAttribute('required'); }
            if (editCourseNameSelect) { setSelectVisible(editCourseNameSelect, false); editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
        } else if (isCourse) {
            nameInput.style.display = 'block';
            nameInput.placeholder = 'Course name';
            nameInput.removeAttribute('readonly');
            nameInput.readOnly = false;
            nameInput.setAttribute('required', 'required');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) { if (sel) { setSelectVisible(sel, false); sel.value = ''; sel.removeAttribute('required'); } });
            if (editCourseSelect) { setSelectVisible(editCourseSelect, true); }
            if (editCourseNameSelect) { setSelectVisible(editCourseNameSelect, false); editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
        } else {
            nameInput.style.display = showAny ? 'none' : 'block';
            nameInput.removeAttribute('required');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) {
                if (!sel) return;
                const show = sel === facultySelect ? showFaculty : (sel === academyStaffSelect ? showAcademyStaff : showMessStaff);
                setSelectVisible(sel, show);
                sel.removeAttribute('required');
                if (show) { sel.setAttribute('required', 'required'); sel.value = nameInput.value || ''; if (sel.value) nameInput.value = sel.value; } else sel.value = '';
            });
            if (editCourseSelect) { setSelectVisible(editCourseSelect, false); editCourseSelect.value = ''; editCourseSelect.removeAttribute('required'); }
            if (editCourseNameSelect) { setSelectVisible(editCourseNameSelect, false); editCourseNameSelect.value = ''; editCourseNameSelect.removeAttribute('required'); }
            if (!showAny) nameInput.setAttribute('required', 'required');
        }
    }

    function loadEditModalGenericBuyerNames() {
        const clientTypeRadio = document.querySelector('#editSellingVoucherModal .edit-client-type-radio:checked');
        const clientNameSelect = document.getElementById('editClientNameSelect');
        const nameInput = document.getElementById('editModalClientNameInput');
        const dataList = document.getElementById('editGenericBuyerNames');
        if (!clientTypeRadio || !clientNameSelect || !nameInput || !dataList) return;

        const slug = (clientTypeRadio.value || '').toLowerCase();
        if (slug !== 'section' && slug !== 'other') {
            nameInput.removeAttribute('list');
            dataList.innerHTML = '';
            return;
        }

        const pk = clientNameSelect.value || '';
        nameInput.setAttribute('list', 'editGenericBuyerNames');
        dataList.innerHTML = '';
        if (!pk) return;

        fetch(editSvBaseUrl + '/buyer-names?client_type_slug=' + encodeURIComponent(slug) + '&client_type_pk=' + encodeURIComponent(pk), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(r => r.json())
            .then(function(data) {
                dataList.innerHTML = '';
                (data.buyers || []).forEach(function(b) {
                    const opt = document.createElement('option');
                    opt.value = b;
                    dataList.appendChild(opt);
                });
            })
            .catch(function() {
                dataList.innerHTML = '';
            });
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
                if (clientSelect) { setSelectVisible(clientSelect, false); clientSelect.removeAttribute('required'); clientSelect.value = ''; clientSelect.removeAttribute('name'); }
                if (otCourseSelect) { setSelectVisible(otCourseSelect, true); otCourseSelect.setAttribute('required', 'required'); otCourseSelect.setAttribute('name', 'client_type_pk'); otCourseSelect.value = ''; }
                if (editCourseSelect) { setSelectVisible(editCourseSelect, false); editCourseSelect.removeAttribute('required'); editCourseSelect.removeAttribute('name'); editCourseSelect.value = ''; }
                if (editCourseNameSelect) { setSelectVisible(editCourseNameSelect, false); editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
                if (nameInput) { nameInput.style.display = 'block'; nameInput.readOnly = true; nameInput.placeholder = 'Select course above'; nameInput.value = nameInput.value || ''; nameInput.removeAttribute('required'); }
            } else if (isCourse) {
                if (clientSelect) { setSelectVisible(clientSelect, false); clientSelect.removeAttribute('required'); clientSelect.value = ''; clientSelect.removeAttribute('name'); }
                if (otCourseSelect) { setSelectVisible(otCourseSelect, false); otCourseSelect.removeAttribute('required'); otCourseSelect.removeAttribute('name'); otCourseSelect.value = ''; }
                if (editCourseSelect) { setSelectVisible(editCourseSelect, true); editCourseSelect.setAttribute('required', 'required'); editCourseSelect.setAttribute('name', 'client_type_pk'); editCourseSelect.value = ''; }
                if (editCourseNameSelect) { setSelectVisible(editCourseNameSelect, false); editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
                if (nameInput) {
                    nameInput.style.display = 'block';
                    nameInput.readOnly = false;
                    nameInput.placeholder = 'Name';
                    nameInput.value = nameInput.value || '';
                    nameInput.setAttribute('required', 'required');
                    nameInput.setAttribute('list', 'editCourseBuyerNames');
                }
                const dl = document.getElementById('editCourseBuyerNames');
                if (dl) dl.innerHTML = '';
            } else {
                if (clientSelect) { setSelectVisible(clientSelect, true); clientSelect.setAttribute('required', 'required'); clientSelect.setAttribute('name', 'client_type_pk'); }
                if (otCourseSelect) { setSelectVisible(otCourseSelect, false); otCourseSelect.removeAttribute('required'); otCourseSelect.removeAttribute('name'); otCourseSelect.value = ''; }
                if (editCourseSelect) { setSelectVisible(editCourseSelect, false); editCourseSelect.removeAttribute('required'); editCourseSelect.removeAttribute('name'); editCourseSelect.value = ''; }
                if (editCourseNameSelect) { setSelectVisible(editCourseNameSelect, false); editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
                if (clientSelect && clientNameOptionsEdit.length) {
                    rebuildClientNameSelect(clientSelect, clientNameOptionsEdit, this.value);
                }
                if (nameInput) {
                    nameInput.style.display = 'block';
                    nameInput.readOnly = false;
                    nameInput.placeholder = 'Client / section / role name';
                    nameInput.setAttribute('required', 'required');
                    nameInput.removeAttribute('list');
                }
                const dl = document.getElementById('editCourseBuyerNames');
                if (dl) dl.innerHTML = '';
            }
            updateEditModalNameField();
            loadEditModalGenericBuyerNames();
        });
    });
    const editModalOtCourseSelect = document.getElementById('editModalOtCourseSelect');
    if (editModalOtCourseSelect) {
        editModalOtCourseSelect.addEventListener('change', function() {
            const selectedOpt = this.options[this.selectedIndex];
            const inp = document.getElementById('editModalClientNameInput');
            if (inp) inp.value = (selectedOpt && selectedOpt.dataset.courseName) ? selectedOpt.dataset.courseName : '';
        });
    }
    
    const editModalCourseSelect = document.getElementById('editModalCourseSelect');
    if (editModalCourseSelect) {
        editModalCourseSelect.addEventListener('change', function() {
            const inp = document.getElementById('editModalClientNameInput');
            const coursePk = this.value;
            const dataList = document.getElementById('editCourseBuyerNames');
            if (!inp || !dataList) return;

            inp.setAttribute('list', 'editCourseBuyerNames');
            dataList.innerHTML = '';

            if (!coursePk) return;
            fetch(editSvBaseUrl + '/buyer-names?client_type_slug=course&client_type_pk=' + encodeURIComponent(coursePk), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(function(data) {
                    dataList.innerHTML = '';
                    (data.buyers || []).forEach(function(b) {
                        const opt = document.createElement('option');
                        opt.value = b;
                        dataList.appendChild(opt);
                    });
                })
                .catch(function() {
                    dataList.innerHTML = '';
                });
        });
    }
    
    const editClientNameSelect = document.getElementById('editClientNameSelect');
    if (editClientNameSelect) {
        editClientNameSelect.addEventListener('change', function() {
            updateEditModalNameField();
            loadEditModalGenericBuyerNames();
        });
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
        const sourceItems = Array.isArray(filteredItems) && filteredItems.length > 0 ? filteredItems : itemSubcategories;
        const options = sourceItems.map(s => {
            let attrs = 'data-unit="' + (s.unit_measurement || '').replace(/"/g, '&quot;') + '" data-rate="' + (s.standard_cost || 0) + '" data-available="' + (s.available_quantity || 0) + '"';
            if (s.price_tiers && s.price_tiers.length > 0) {
                attrs += ' data-price-tiers="' + (JSON.stringify(s.price_tiers) || '').replace(/"/g, '&quot;') + '"';
            }
            return '<option value="' + s.id + '" ' + attrs + (item && item.item_subcategory_id == s.id ? ' selected' : '') + '>' + (s.item_name || '—').replace(/</g, '&lt;') + '</option>';
        }).join('');
        const qty = item ? item.quantity : '';
        const avail = item ? item.available_quantity : 0;
        const rate = item ? item.rate : '';
        const total = item ? item.amount : '';
        const unit = item ? (item.unit || '') : '';
        const left = item && (avail - qty) >= 0 ? (avail - qty) : 0;
        const originalQtyAttr = item ? (' data-original-qty="' + (parseFloat(item.quantity) || 0) + '"') : '';
        return '<tr class="sv-item-row edit-sv-item-row"' + originalQtyAttr + '>' +
            '<td><select name="items[' + index + '][item_subcategory_id]" class="form-select form-select-sm sv-item-select" required><option value="">Select Item</option>' + options + '</select></td>' +
            '<td><input type="text" name="items[' + index + '][unit]" class="form-control  sv-unit" readonly placeholder="—" value="' + (unit || '') + '"></td>' +
            '<td><input type="number" name="items[' + index + '][available_quantity]" class="form-control  sv-avail bg-light" step="0.01" min="0" value="' + avail + '" placeholder="0" readonly></td>' +
            '<td><input type="number" name="items[' + index + '][quantity]" class="form-control  sv-qty" step="0.01" min="0.01" placeholder="0" value="' + qty + '" required><div class="invalid-feedback">Issue Qty cannot exceed Available Qty.</div></td>' +
            '<td><input type="text" class="form-control  sv-left bg-light" readonly placeholder="0" value="' + left + '"></td>' +
            '<td><input type="number" name="items[' + index + '][rate]" class="form-control  sv-rate" step="0.01" min="0" placeholder="0" value="' + rate + '" required></td>' +
            '<td><input type="text" class="form-control  sv-total bg-light" readonly placeholder="0.00" value="' + (total ? total.toFixed(2) : '') + '"></td>' +
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

    /**
     * Recalculate Available Qty and Left Qty for all rows in the Edit modal.
     * Effective base per item = current stock + sum of original qtys (from this voucher) for that item.
     * Then each row gets available = base - already used in previous rows (same logic as Add mode).
     */
    function refreshEditAllAvailable() {
        const rows = document.querySelectorAll('#editModalItemsBody .sv-item-row');
        if (!rows.length) return;

        const effectiveBaseByItem = {};
        rows.forEach(function(row) {
            const select = row.querySelector('.sv-item-select');
            const itemId = select ? select.value : '';
            if (!itemId) return;
            const originalQty = parseFloat(row.getAttribute('data-original-qty')) || 0;
            if (!effectiveBaseByItem.hasOwnProperty(itemId)) {
                effectiveBaseByItem[itemId] = getBaseAvailableForItem(itemId);
            }
            effectiveBaseByItem[itemId] += originalQty;
        });

        const usedByItem = {};
        rows.forEach(function(row) {
            const select = row.querySelector('.sv-item-select');
            const itemId = select ? select.value : '';
            const availInp = row.querySelector('.sv-avail');
            const leftInp = row.querySelector('.sv-left');
            if (!itemId || !availInp) return;

            const effectiveBase = effectiveBaseByItem[itemId] != null ? effectiveBaseByItem[itemId] : getBaseAvailableForItem(itemId);
            const alreadyUsed = usedByItem[itemId] || 0;
            const availableForRow = Math.max(0, effectiveBase - alreadyUsed);

            availInp.value = availableForRow.toFixed(2);

            const qty = parseFloat(row.querySelector('.sv-qty').value) || 0;
            if (leftInp) {
                leftInp.value = Math.max(0, availableForRow - qty).toFixed(2);
            }

            usedByItem[itemId] = alreadyUsed + qty;
            enforceQtyWithinAvailable(row);
        });
    }

    function updateEditItemDropdowns() {
        const rows = document.querySelectorAll('#editModalItemsBody .sv-item-row');
        rows.forEach(row => {
            const select = row.querySelector('.sv-item-select');
            if (!select) return;

            const currentValue = select.value;
            select.innerHTML = '<option value="">Select Item</option>';

            const sourceItems = Array.isArray(filteredItems) && filteredItems.length > 0 ? filteredItems : itemSubcategories;
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
            if (typeof Choices !== 'undefined') {
                if (select.tomselect) {
                    try { select.tomselect.destroy(); } catch (e) {}
                }
                createChoicesInstance(select, createItemSelectConfig());
            }
            updateUnit(row);
        });
        updateEditGrandTotal();
    }

    function buildEditItemsTable(items) {
        const tbody = document.getElementById('editModalItemsBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        if (!Array.isArray(items) || items.length === 0) {
            tbody.insertAdjacentHTML('beforeend', getEditRowHtml(0, null));
            editRowIndex = 1;
        } else {
            items.forEach((item, i) => {
                tbody.insertAdjacentHTML('beforeend', getEditRowHtml(i, item));
            });
            editRowIndex = items.length;
        }
        if (typeof Choices !== 'undefined') {
            tbody.querySelectorAll('.sv-item-select').forEach(function(select) {
                if (select.tomselect) {
                    try { select.tomselect.destroy(); } catch (e) {}
                }
                createChoicesInstance(select, createEditModalItemSelectConfig());
            });
        }
        updateEditRemoveButtons();
        refreshEditAllAvailable();
        updateEditGrandTotal();
    }

    // View button handler (mousedown ensures single-tap works with DataTables)
    document.addEventListener('mousedown', function(e) {
        const btn = e.target.closest('.btn-view-sv');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const voucherId = btn.getAttribute('data-voucher-id');
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
                    document.getElementById('viewReferenceNumber').textContent = v.reference_number || '—';
                    document.getElementById('viewOrderBy').textContent = v.order_by || '—';
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
                    // Bill display removed; keep view logic resilient if elements are absent
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
    }, true);

    // Return button handler (mousedown ensures single-tap works with DataTables)
    document.addEventListener('mousedown', function(e) {
        const btn = e.target.closest('.btn-return-sv');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const voucherId = btn.getAttribute('data-voucher-id');
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
                        const name = (item.item_name || '—').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                        const qty = item.quantity != null ? item.quantity : '';
                        const unit = (item.unit || '—').replace(/</g, '&lt;');
                        const retQty = item.return_quantity != null ? item.return_quantity : 0;
                        const retDate = item.return_date || '';
                        const issuedQty = parseFloat(qty) || 0;
                        const rowIssueYmd = (item.issue_date || issueDate || '').trim();
                        const issueDisp = ymdToDmY(rowIssueYmd);
                        tbody.insertAdjacentHTML('beforeend',
                            '<tr><td>' + name + '<input type="hidden" name="items[' + i + '][id]" value="' + id + '"></td><td>' + qty + '</td><td>' + unit + '</td><td class="text-nowrap">' + issueDisp + '</td>' +
                            '<td><input type="number" name="items[' + i + '][return_quantity]" class="form-control  sv-return-qty" step="0.01" min="0" max="' + issuedQty + '" data-issued="' + issuedQty + '" value="' + retQty + '"><div class="invalid-feedback">Return Qty cannot exceed Issued Qty.</div></td>' +
                            '<td><input type="date" name="items[' + i + '][return_date]" class="form-control  sv-return-date" max="' + todayYmd + '" ' + (rowIssueYmd ? ('min="' + rowIssueYmd + '" data-issue-date="' + rowIssueYmd + '"') : '') + ' value="' + retDate + '"><div class="invalid-feedback">Return date must be between issue date and today.</div></td></tr>');
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

    function enforceReturnDateWithinRange(inputEl) {
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
            return;
        }

        inputEl.setCustomValidity('');
        inputEl.classList.remove('is-invalid');
    }

    const returnItemModalBody = document.getElementById('returnItemModalBody');
    if (returnItemModalBody) {
        returnItemModalBody.addEventListener('input', function(e) {
            if (e.target && e.target.classList.contains('sv-return-qty')) {
                enforceReturnQtyWithinIssued(e.target);
            }
            if (e.target && e.target.classList.contains('sv-return-date')) {
                enforceReturnDateWithinRange(e.target);
            }
        });
    }

    const returnItemForm = document.getElementById('returnItemForm');
    if (returnItemForm) {
        returnItemForm.addEventListener('submit', function(e) {
            this.querySelectorAll('.sv-return-qty').forEach(enforceReturnQtyWithinIssued);
            this.querySelectorAll('.sv-return-date').forEach(enforceReturnDateWithinRange);
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
            }
        }, true);
    }

    // Edit button handler (mousedown ensures single-tap works with DataTables)
    document.addEventListener('mousedown', function(e) {
        const btn = e.target.closest('.btn-edit-sv');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const voucherId = btn.getAttribute('data-voucher-id');
            if (!voucherId) {
                console.error('No voucher ID found for edit');
                return;
            }
            console.log('Loading edit data for voucher:', voucherId);
            fetch(editSvBaseUrl + '/' + voucherId + '/edit', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json().then(data => ({ ok: r.ok, status: r.status, data })))
                .then(({ ok, status, data }) => {
                    if (!ok) {
                        alert(data && data.error ? data.error : 'Failed to load voucher (HTTP ' + status + ').');
                        return;
                    }
                    console.log('Edit data:', data);
                    if (data.error) { alert(data.error); return; }
                    destroyEditModalTomSelects();
                    const v = data.voucher;
                    const items = data.items || [];
                    document.getElementById('editSellingVoucherForm').action = editSvBaseUrl + '/' + voucherId;
                    
                    // Set client type radio (do not dispatch "change" — it resets fields and fights this loader)
                    const clientTypeRadio = document.querySelector('#editSellingVoucherModal input[name="client_type_slug"][value="' + (v.client_type_slug || 'employee') + '"]');
                    if (clientTypeRadio) {
                        clientTypeRadio.checked = true;
                    }
                    
                    document.querySelector('#editSellingVoucherModal select.edit-payment-type').value = String(v.payment_type ?? 1);
                    const editSlug = (v.client_type_slug || 'employee');
                    
                    document.getElementById('editModalClientNameInput').value = v.client_name || '';
                    document.getElementById('editModalFacultySelect').value = v.client_name || '';
                    const editAcademyEl = document.getElementById('editModalAcademyStaffSelect');
                    if (editAcademyEl) editAcademyEl.value = v.client_name || '';
                    const editMessEl = document.getElementById('editModalMessStaffSelect');
                    if (editMessEl) editMessEl.value = v.client_name || '';
                    const editOtCourseEl = document.getElementById('editModalOtCourseSelect');
                    if (editOtCourseEl) editOtCourseEl.value = v.client_type_pk || '';
                    const editCourseEl = document.getElementById('editModalCourseSelect');
                    if (editCourseEl) {
                        editCourseEl.value = v.client_type_pk || '';
                        if ((v.client_type_slug || '') === 'course') {
                            editCourseEl.dispatchEvent(new Event('change'));
                        }
                    }
                    const editCourseNameEl = document.getElementById('editModalCourseNameSelect');
                    if (editCourseNameEl) editCourseNameEl.value = v.client_type_pk || '';
                    document.querySelector('#editSellingVoucherModal input.edit-issue-date').value = v.issue_date || '';
                    
                    const storeSelect = document.querySelector('#editSellingVoucherModal select.edit-store');
                    if (storeSelect) storeSelect.value = v.inve_store_master_pk || v.store_id || '';
                    
                    document.querySelector('#editSellingVoucherModal input.edit-remarks').value = v.remarks || '';
                    const editRefNum = document.querySelector('#editSellingVoucherModal input.edit-reference-number');
                    if (editRefNum) editRefNum.value = v.reference_number || '';
                    const editOrderBy = document.querySelector('#editSellingVoucherModal input.edit-order-by');
                    if (editOrderBy) editOrderBy.value = v.order_by || '';
                    var editBillFileNameEl = document.getElementById('editBillCurrentFileName');
                    if (editBillFileNameEl) {
                        if (v.bill_path) {
                            var billFileName = v.bill_path.split('/').pop() || v.bill_path;
                            editBillFileNameEl.textContent = billFileName;
                            editBillFileNameEl.setAttribute('title', billFileName);
                        } else {
                            editBillFileNameEl.textContent = 'No file chosen';
                            editBillFileNameEl.removeAttribute('title');
                        }
                    }
                    var editSvBillFileInputEl = document.getElementById('editSvBillFileInput');
                    if (editSvBillFileInputEl) editSvBillFileInputEl.value = '';
                    var editRemoveBillFlagEl = document.getElementById('editRemoveBillFlag');
                    if (editRemoveBillFlagEl) editRemoveBillFlagEl.value = '0';
                    var editBillLinkEl = document.getElementById('editCurrentBillLink');
                    if (editBillLinkEl) {
                        if (v.bill_url) {
                            editBillLinkEl.innerHTML = 'Current bill: <a href="' + (v.bill_url || '').replace(/"/g, '&quot;') + '" target="_blank" rel="noopener" class="text-primary">View Bill</a>';
                        } else {
                            editBillLinkEl.innerHTML = '';
                        }
                    }
                    editCurrentStoreId = storeSelect ? storeSelect.value || '' : null;

                    // Align native fields / visibility BEFORE Choices init (same for store + no-store paths)
                    (function applyEditSvClientTypeLayout() {
                        const isOt = (v.client_type_slug || '') === 'ot';
                        const isCourse = (v.client_type_slug || '') === 'course';
                        const editClientSelect = document.getElementById('editClientNameSelect');
                        const editOtSelect = document.getElementById('editModalOtCourseSelect');
                        const editCourseSelect = document.getElementById('editModalCourseSelect');
                        const editCourseNameSelect = document.getElementById('editModalCourseNameSelect');
                        const editNameInp = document.getElementById('editModalClientNameInput');
                        if (isOt) {
                            if (editClientSelect) { editClientSelect.style.display = 'none'; editClientSelect.removeAttribute('required'); editClientSelect.removeAttribute('name'); }
                            if (editOtSelect) { editOtSelect.style.display = 'block'; editOtSelect.setAttribute('required', 'required'); editOtSelect.setAttribute('name', 'client_type_pk'); editOtSelect.value = v.client_type_pk || ''; }
                            if (editCourseSelect) { editCourseSelect.style.display = 'none'; editCourseSelect.removeAttribute('required'); editCourseSelect.removeAttribute('name'); editCourseSelect.value = ''; }
                            if (editCourseNameSelect) { editCourseNameSelect.style.display = 'none'; editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
                            if (editNameInp) { editNameInp.style.display = 'block'; editNameInp.readOnly = true; editNameInp.placeholder = 'Name (from course/student)'; editNameInp.value = v.client_name || ''; editNameInp.removeAttribute('required'); }
                        } else if (isCourse) {
                            if (editClientSelect) { editClientSelect.style.display = 'none'; editClientSelect.removeAttribute('required'); editClientSelect.removeAttribute('name'); }
                            if (editOtSelect) { editOtSelect.style.display = 'none'; editOtSelect.removeAttribute('required'); editOtSelect.removeAttribute('name'); editOtSelect.value = ''; }
                            if (editCourseSelect) { editCourseSelect.style.display = 'block'; editCourseSelect.setAttribute('required', 'required'); editCourseSelect.setAttribute('name', 'client_type_pk'); editCourseSelect.value = v.client_type_pk || ''; }
                            if (editCourseNameSelect) { editCourseNameSelect.style.display = 'none'; editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
                            if (editNameInp) { editNameInp.style.display = 'block'; editNameInp.readOnly = false; editNameInp.placeholder = 'Course name'; editNameInp.value = v.client_name || ''; editNameInp.setAttribute('required', 'required'); }
                        } else {
                            if (editClientSelect) {
                                editClientSelect.style.display = 'block';
                                editClientSelect.setAttribute('required', 'required');
                                editClientSelect.setAttribute('name', 'client_type_pk');
                                if (clientNameOptionsEdit.length) {
                                    rebuildClientNameSelect(editClientSelect, clientNameOptionsEdit, (v.client_type_slug || 'employee'));
                                }
                                setSelectValue(document.getElementById('editClientNameSelect'), v.client_type_pk || '');
                            }
                            if (editOtSelect) { editOtSelect.style.display = 'none'; editOtSelect.removeAttribute('required'); editOtSelect.removeAttribute('name'); editOtSelect.value = ''; }
                            if (editCourseSelect) { editCourseSelect.style.display = 'none'; editCourseSelect.removeAttribute('required'); editCourseSelect.removeAttribute('name'); editCourseSelect.value = ''; }
                            if (editCourseNameSelect) { editCourseNameSelect.style.display = 'none'; editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
                            if (editNameInp) { editNameInp.style.display = 'block'; editNameInp.readOnly = false; editNameInp.placeholder = 'Client / section / role name'; editNameInp.setAttribute('required', 'required'); }
                        }
                    })();

                    const openEditModalWithItems = function() {
                        buildEditItemsTable(items);
                        // Initialize Choices in Edit modal (payment, client, store, name dropdowns, item selects)
                        if (typeof initEditModalTomSelects === 'function') {
                            initEditModalTomSelects();
                        }
                        // Ensure Client Name dropdown is rebuilt for current Client Type, then select saved value.
                        if (editSlug !== 'ot' && editSlug !== 'course') {
                            const editClientSelect = document.getElementById('editClientNameSelect');
                            if (editClientSelect && clientNameOptionsEdit.length) {
                                rebuildClientNameSelect(editClientSelect, clientNameOptionsEdit, editSlug);
                            }
                            setSelectValue(document.getElementById('editClientNameSelect'), v.client_type_pk || '');
                        }
                        // After Choices init, show only the active dropdowns in Client Name column and Name column
                        if (typeof applyEditModalClientNameColumnVisibility === 'function') {
                            applyEditModalClientNameColumnVisibility();
                        }
                        if (typeof updateEditModalNameField === 'function') {
                            updateEditModalNameField();
                        }
                        syncEditSellingVoucherChoicesFromVoucher(v, editSlug);
                        const modal = new bootstrap.Modal(document.getElementById('editSellingVoucherModal'));
                        modal.show();
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
                    console.error('Error loading voucher for edit:', err); 
                    alert('Failed to load selling voucher: ' + err.message); 
                });
        }
    }, true);

    const editModalAddItemRow = document.getElementById('editModalAddItemRow');
    if (editModalAddItemRow) {
        editModalAddItemRow.addEventListener('click', function() {
            appendEditModalItemRow();
        });
    }

    // Add modal: show selected bill file name and Remove button
    var addSvBillFileInputEl = document.getElementById('addSvBillFileInput');
    if (addSvBillFileInputEl) {
        addSvBillFileInputEl.addEventListener('change', function() {
            var wrap = document.getElementById('addSvBillFileChosenWrap');
            var nameEl = document.getElementById('addSvBillFileChosenName');
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
    var addSvBillFileRemoveEl = document.getElementById('addSvBillFileRemove');
    if (addSvBillFileRemoveEl) {
        addSvBillFileRemoveEl.addEventListener('click', function() {
            var input = document.getElementById('addSvBillFileInput');
            var wrap = document.getElementById('addSvBillFileChosenWrap');
            var nameEl = document.getElementById('addSvBillFileChosenName');
            if (input) input.value = '';
            if (nameEl) nameEl.textContent = '';
            if (wrap) wrap.classList.add('d-none');
        });
    }

    // Edit modal: show selected file name when user picks a new bill (same as Selling Voucher with Date Range)
    var editSvBillFileInputEl = document.getElementById('editSvBillFileInput');
    if (editSvBillFileInputEl) {
        editSvBillFileInputEl.addEventListener('change', function() {
            var pathEl = document.getElementById('editBillCurrentFileName');
            var removeFlag = document.getElementById('editRemoveBillFlag');
            if (pathEl) pathEl.textContent = this.files && this.files[0] ? this.files[0].name : 'No file chosen';
            if (removeFlag) removeFlag.value = '0';
        });
    }
    var editSvBillFileRemoveEl = document.getElementById('editSvBillFileRemove');
    if (editSvBillFileRemoveEl) {
        editSvBillFileRemoveEl.addEventListener('click', function() {
            var input = document.getElementById('editSvBillFileInput');
            var pathEl = document.getElementById('editBillCurrentFileName');
            var removeFlag = document.getElementById('editRemoveBillFlag');
            if (input) input.value = '';
            if (pathEl) pathEl.textContent = 'No file chosen';
            if (removeFlag) removeFlag.value = '1';
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
                if (row) {
                    if (e.target.classList.contains('sv-rate')) {
                        const rateInp = row.querySelector('.sv-rate');
                        if (rateInp) rateInp.dataset.manualRate = '1';
                    }
                    refreshEditAllAvailable();
                    calcRow(row);
                    updateEditGrandTotal();
                }
            }
        });
        
        editModalItemsBody.addEventListener('click', function(e) {
            if (e.target.classList.contains('sv-remove-row')) {
                const row = e.target.closest('.sv-item-row');
                if (row && document.querySelectorAll('#editModalItemsBody .sv-item-row').length > 1) {
                    row.remove();
                    refreshEditAllAvailable();
                    updateEditGrandTotal();
                    updateEditRemoveButtons();
                }
            }
        });
    }

    // Enter (outside Choices + buttons): append item row anywhere in Edit modal
    const editSvModalEl = document.getElementById('editSellingVoucherModal');
    if (editSvModalEl) {
        editSvModalEl.addEventListener('keydown', function(e) {
            if (e.key !== 'Enter') return;
            var activeEl = document.activeElement;
            if (!svEnterShouldAppendItemRow(editSvModalEl, activeEl)) return;
            e.preventDefault();
            appendEditModalItemRow();
        });
    }

    // Store selection change in EDIT modal
    const editStoreSelect = document.querySelector('#editSellingVoucherModal select.edit-store');
    if (editStoreSelect) {
        editStoreSelect.addEventListener('change', function() {
            const storeId = this.value;
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

    // Reset add selling voucher modal when closed (so next open starts fresh)
    const addSellingVoucherModal = document.getElementById('addSellingVoucherModal');
    if (addSellingVoucherModal) {
        addSellingVoucherModal.addEventListener('hidden.bs.modal', function() {
            addSellingVoucherModal.classList.remove('sv-choices-dropdown-open');
            currentStoreId = null;
            filteredItems = itemSubcategories;
            destroyAddModalTomSelects();
            const form = document.getElementById('sellingVoucherModalForm');
            if (form) {
                form.reset();
                form.classList.remove('was-validated');
                form.querySelectorAll('.is-invalid').forEach(function(el) { el.classList.remove('is-invalid'); });
            }
            resetAddModalStoreSelectToEmpty(addSellingVoucherModal);
            const issueDateInp = addSellingVoucherModal.querySelector('input[name="issue_date"]');
            if (issueDateInp) issueDateInp.value = new Date().toISOString().slice(0, 10);
            const paymentSel = addSellingVoucherModal.querySelector('select[name="payment_type"]');
            if (paymentSel) paymentSel.value = '1';
            const clientPkSel = addSellingVoucherModal.querySelector('#modalClientNameSelect');
            if (clientPkSel) clientPkSel.value = '';
            const clientNameInp = document.getElementById('modalClientNameInput');
            if (clientNameInp) clientNameInp.value = '';
            addSellingVoucherModal.querySelectorAll('#modalClientNameWrap select, #modalNameFieldWrap select').forEach(function(s) { if (s.value !== undefined) s.value = ''; });
            resetAddModalClientTypeToEmployee(addSellingVoucherModal);
            const billInput = document.getElementById('addSvBillFileInput');
            if (billInput) billInput.value = '';
            const billWrap = document.getElementById('addSvBillFileChosenWrap');
            const billName = document.getElementById('addSvBillFileChosenName');
            if (billWrap) billWrap.classList.add('d-none');
            if (billName) billName.textContent = '';
            const tbody = document.getElementById('modalItemsBody');
            if (tbody) {
                tbody.innerHTML = getRowHtml(0);
                rowIndex = 1;
                updateRemoveButtons();
            }
            const grandTotalEl = document.getElementById('modalGrandTotal');
            if (grandTotalEl) grandTotalEl.textContent = '₹0.00';
        });

        var editSellingVoucherModalEl = document.getElementById('editSellingVoucherModal');
        if (editSellingVoucherModalEl) {
            editSellingVoucherModalEl.addEventListener('hidden.bs.modal', function() {
                editSellingVoucherModalEl.classList.remove('sv-choices-dropdown-open');
            });
        }

        addSellingVoucherModal.addEventListener('show.bs.modal', function() {
            const storeSelect = addSellingVoucherModal.querySelector('select[name="store_id"]');
            const preSelectedStore = storeSelect ? storeSelect.value : null;
            
            console.log('Modal opening, pre-selected store:', preSelectedStore); // Debug log
            
            // If there's a pre-selected store, fetch its items
            if (preSelectedStore) {
                currentStoreId = preSelectedStore;
                fetchStoreItems(preSelectedStore, function() {
                    console.log('Pre-fetched items for store:', preSelectedStore, 'Count:', filteredItems.length);
                    updateAddItemDropdowns();
                    refreshAllAvailable();
                    document.querySelectorAll('#modalItemsBody .sv-item-row').forEach(function(row) { calcRow(row); });
                    updateGrandTotal();
                });
            } else {
                currentStoreId = null;
                filteredItems = itemSubcategories;
                if (storeSelect) storeSelect.value = '';
            }
        });
        addSellingVoucherModal.addEventListener('shown.bs.modal', function() {
            initAddModalTomSelects();
            refreshAllAvailable();
            document.querySelectorAll('#modalItemsBody .sv-item-row').forEach(function(row) { calcRow(row); });
            updateGrandTotal();
        });
    }

    // Before disabling submit buttons, ensure form is valid (includes qty <= available)
    if (sellingVoucherModalForm) {
        sellingVoucherModalForm.addEventListener('submit', function(e) {
            // sync validity for all rows
            document.querySelectorAll('#modalItemsBody .sv-item-row').forEach(enforceQtyWithinAvailable);
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
                return;
            }
        }, true);
    }

    if (editSellingVoucherForm) {
        editSellingVoucherForm.addEventListener('submit', function(e) {
            document.querySelectorAll('#editModalItemsBody .sv-item-row').forEach(enforceQtyWithinAvailable);
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
                return;
            }
        }, true);
    }

    @if($errors->any() || session('open_selling_voucher_modal'))
    var modal = document.getElementById('addSellingVoucherModal');
    if (modal && typeof bootstrap !== 'undefined') {
        (new bootstrap.Modal(modal)).show();
    }
    @endif

    // Print View modal content (Selling Voucher) – correct design with standard header
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
        if (!win) { alert('Please allow popups to print.'); return; }
        var title = (modalEl.querySelector('.modal-title') || {}).textContent || 'Selling Voucher';
        var printedOn = new Date();
        var dateStr = printedOn.getDate().toString().padStart(2,'0') + '/' + (printedOn.getMonth()+1).toString().padStart(2,'0') + '/' + printedOn.getFullYear() + ', ' + printedOn.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true });
        var bodyContent = content.innerHTML.replace(/<button[^>]*btn-close[^>]*>[\s\S]*?<\/button>/gi, '');
        var printHeader = '<div class="print-doc-header" style="text-align:center;margin-bottom:16px;padding-bottom:12px;border-bottom:2px solid #2c3e50;">' +
            '<div style="font-size:16px;font-weight:700;color:#1a1a1a;margin-bottom:4px;">OFFICER\'S MESS LBSNAA MUSSOORIE</div>' +
            '<div style="background:#495057;color:#fff;padding:6px 12px;font-size:13px;display:inline-block;margin:4px 0;">Selling Voucher</div>' +
            '<div style="font-size:11px;color:#6c757d;margin-top:6px;">Printed on ' + dateStr + '</div></div>';
        var printCss = '<style>@page{size:A4;margin:14mm;}body{font-family:Arial,sans-serif;font-size:12px;color:#212529;padding:0 12px;margin:0;background:#fff;}.print-doc-header{-webkit-print-color-adjust:exact;print-color-adjust:exact;}.modal-header{border-bottom:1px solid #dee2e6;padding-bottom:8px;margin-bottom:12px;}.modal-body{color:#212529;}.card{margin-bottom:14px;page-break-inside:avoid;}.card-header{font-weight:600;font-size:12px;margin-bottom:8px;}.card-body table th,.card-body table td{border:1px solid #adb5bd;padding:6px 8px;}table{width:100%;border-collapse:collapse;font-size:11px;}thead th{background:#af2910!important;color:#fff!important;border-color:#8b2009;font-weight:600;-webkit-print-color-adjust:exact;print-color-adjust:exact;}.card-footer{font-weight:600;padding-top:8px;}.btn-close,.modal-footer{display:none!important;}@media print{body{padding:0;}}</style>';
        win.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>' + title.replace(/</g, '&lt;') + '</title>' + printCss + '</head><body>' + printHeader + '<div class="modal-content-wrap">' + bodyContent + '</div></body></html>');
        win.document.close();
        win.focus();
        setTimeout(function() { win.print(); win.close(); }, 350);
    });

    document.addEventListener('shown.bs.tab', function (e) {
        var t = e.target;
        if (!t || !t.getAttribute || t.getAttribute('href') !== '#tab-setup') return;
        var wrap = document.querySelector('#sellingVouchersTable_wrapper');
        if (!wrap || wrap.offsetParent === null) return;
        var inp = wrap.querySelector('.dataTables_filter input[type="search"]');
        if (inp) window.setTimeout(function () { inp.focus(); }, 120);
    });
});
</script>
@endsection