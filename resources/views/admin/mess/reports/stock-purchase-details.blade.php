@extends('admin.layouts.master')
@section('title', 'Stock Purchase Details Report')
@section('setup_content')
@php
    $selectedVendorIds = $selectedVendors->pluck('id')->map(fn ($id) => (int) $id)->all();
    $selectedStoreIds = $selectedStores->pluck('id')->map(fn ($id) => (int) $id)->all();
    $stockPurchasePrintDateRange = 'Stock Purchase Details Report Between '
        . date('d-F-Y', strtotime($fromDate))
        . ' To '
        . date('d-F-Y', strtotime($toDate));
    $stockPurchasePrintVendorLine = $selectedVendors->isEmpty()
        ? 'All Vendors'
        : $selectedVendors->pluck('name')->implode(', ');
    $stockPurchasePrintVendorDetailRows = $selectedVendors->isEmpty()
        ? []
        : $selectedVendors->map(function ($v) {
            return [
                'name' => $v->name ?? '—',
                'contact_person' => $v->contact_person ?? '—',
                'phone' => $v->phone ?? '—',
                'email' => $v->email ?? '—',
                'address' => $v->address ?? '—',
            ];
        })->values()->all();
    $stockPurchasePrintStoreDetails = $selectedStores->isEmpty()
        ? 'All Stores'
        : $selectedStores->pluck('store_name')->implode(', ');
    $stockPurchasePrintVendorHeaderLabel = $selectedVendors->isEmpty() || $selectedVendors->count() === 1
        ? 'Vendor:'
        : 'Filtered vendors:';
    $stockPurchasePrintConfigJson = json_encode([
        'dateRange' => $stockPurchasePrintDateRange,
        'vendorLine' => $stockPurchasePrintVendorLine,
        'vendorHeaderLabel' => $stockPurchasePrintVendorHeaderLabel,
        'vendorDetailRows' => $stockPurchasePrintVendorDetailRows,
        'storeDetails' => $stockPurchasePrintStoreDetails,
    ], JSON_THROW_ON_ERROR);
@endphp
<div class="container-fluid stock-purchase-report py-3 py-md-4">
    <div id="stock-purchase-print-config" class="d-none" hidden
         data-config="{{ htmlspecialchars($stockPurchasePrintConfigJson, ENT_QUOTES, 'UTF-8') }}"></div>
    <x-breadcrum title="Stock Purchase Details Report"></x-breadcrum>

    <div class="card mb-4 border-0 rounded-4 shadow-sm no-print spr-filter-card">
        <div class="card-header bg-body border-0 border-bottom border-light-subtle py-3 px-3 px-lg-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 gap-sm-3">
                    <div class="spr-filter-icon-circle bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                        <span class="material-symbols-rounded text-primary fs-5 lh-1" aria-hidden="true">filter_list</span>
                    </div>
                    <div class="min-w-0">
                        <h2 class="h5 mb-0 fw-semibold text-body lh-sm">Filter purchases</h2>
                        <span class="text-body-tertiary small d-block">Refine results by date range, vendor, and store</span>
                    </div>
                </div>
                <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle fw-medium px-3 py-2 rounded-1 d-none d-md-inline-flex align-items-center gap-1">
                    <span class="material-symbols-rounded fs-6 lh-1" aria-hidden="true">tune</span>
                    Quick filters
                </span>
            </div>
        </div>
        <div class="card-body pt-3 pb-3 px-3 px-lg-4">
            <form id="stockPurchaseDetailsFilterForm" method="GET" action="{{ route('admin.mess.reports.stock-purchase-details') }}">
                <div class="row g-3">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <label class="form-label fw-medium small text-uppercase text-body-secondary mb-1" for="spr_from_date">From date</label>
                        <input type="date" name="from_date" id="spr_from_date" class="form-control" value="{{ $fromDate }}" required autocomplete="off">
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <label class="form-label fw-medium small text-uppercase text-body-secondary mb-1" for="spr_to_date">To date</label>
                        <input type="date" name="to_date" id="spr_to_date" class="form-control" value="{{ $toDate }}" required autocomplete="off">
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <label class="form-label fw-medium small text-uppercase text-body-secondary mb-1" for="stock_purchase_vendor_id">Vendor name</label>
                        <select name="vendor_id[]" id="stock_purchase_vendor_id" class="form-select form-select-sm choices-select" multiple data-placeholder="All vendors">
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected(in_array((int) $vendor->id, $selectedVendorIds, true))>{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <label class="form-label fw-medium small text-uppercase text-body-secondary mb-1" for="stock_purchase_store_id">Store name</label>
                        <select name="store_id[]" id="stock_purchase_store_id" class="form-select form-select-sm choices-select" multiple data-placeholder="All stores">
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected(in_array((int) $store->id, $selectedStoreIds, true))>{{ $store->store_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center column-gap-2 row-gap-2 pt-3 mt-3 border-top border-secondary-subtle">
                    <button type="submit" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2 px-3">
                        <span class="material-symbols-rounded fs-6 lh-1" aria-hidden="true">filter_list</span>
                        <span>Apply filters</span>
                    </button>
                    <a href="{{ route('admin.mess.reports.stock-purchase-details') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2 px-3">
                        <span class="material-symbols-rounded fs-6 lh-1" aria-hidden="true">refresh</span>
                        <span>Reset</span>
                    </a>
                    <div class="vr d-none d-md-block text-body-secondary opacity-50 mx-1 align-self-stretch my-1 flex-shrink-0" role="separator" aria-hidden="true"></div>
                    <div class="btn-group shadow-sm" role="group" aria-label="Print or download PDF">
                        <button type="button" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center justify-content-center gap-2 px-3 rounded-start" onclick="printStockPurchaseTable()" title="Print report or choose Save as PDF in print dialog">
                            <span class="material-symbols-rounded fs-6 lh-1" aria-hidden="true">print</span>
                            <span>Print</span>
                        </button>
                        <a href="{{ route('admin.mess.reports.stock-purchase-details.pdf', request()->query()) }}" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center justify-content-center gap-2 px-3 rounded-end" title="Download PDF">
                            <span class="material-symbols-rounded fs-6 lh-1" aria-hidden="true">picture_as_pdf</span>
                            <span>PDF</span>
                        </a>
                    </div>
                    <a href="{{ route('admin.mess.reports.stock-purchase-details.excel', request()->query()) }}" class="btn btn-success btn-sm d-inline-flex align-items-center gap-2 px-3" title="Export to Excel">
                        <span class="material-symbols-rounded fs-6 lh-1" aria-hidden="true">table_view</span>
                        <span>Export Excel</span>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Area (full width below filters) -->
    <div class="report-area">
            <div class="report-content card border-0 shadow-sm rounded-4">
                <div class="card-body p-3 p-lg-4">
                    <div class="report-header text-center mb-4 rounded-3 p-4 px-lg-5 bg-body-tertiary border border-secondary-subtle">
                        <div class="d-flex align-items-center justify-content-center gap-2 gap-sm-3 mb-3 flex-wrap">
                            <span class="material-symbols-rounded text-primary fs-3 lh-1" aria-hidden="true">receipt_long</span>
                            <h3 class="h4 fw-bold mb-0 text-body text-uppercase mess-title-tracking">Stock purchase details</h3>
                        </div>
                        <div class="d-flex flex-column flex-sm-row flex-wrap align-items-center justify-content-center gap-2 mb-2">
                            <span class="badge rounded-1 bg-body text-body-emphasis fw-normal px-3 py-2 shadow-sm border border-secondary-subtle">
                                <span class="material-symbols-rounded align-middle me-1 fs-6 lh-1" aria-hidden="true">date_range</span>
                                {{ date('d-F-Y', strtotime($fromDate)) }} to {{ date('d-F-Y', strtotime($toDate)) }}
                            </span>
                        </div>
                        <div class="d-flex flex-column flex-sm-row flex-wrap align-items-stretch align-items-sm-center justify-content-center gap-2">
                            <span class="badge rounded-1 bg-primary-subtle text-primary-emphasis border border-primary-subtle fw-normal px-3 py-2 shadow-sm text-wrap text-start spr-meta-badge">
                                <span class="material-symbols-rounded align-middle me-1 fs-6 lh-1" aria-hidden="true">person</span>
                                <span class="fw-semibold">{{ $stockPurchasePrintVendorHeaderLabel }}</span> {{ $stockPurchasePrintVendorLine }}
                            </span>
                            <span class="badge rounded-1 bg-success-subtle text-success-emphasis border border-success-subtle fw-normal px-3 py-2 shadow-sm text-wrap text-sm-start">
                                <span class="material-symbols-rounded align-middle me-1 fs-6 lh-1" aria-hidden="true">store</span>
                                <span class="fw-semibold">Store:</span> {{ $stockPurchasePrintStoreDetails }}
                            </span>
                        </div>
                    </div>

                    <div class="table-responsive rounded-3 border border-secondary-subtle shadow-sm bg-body stock-purchase-table-wrapper" role="region" aria-label="Stock purchase table" tabindex="0">
                        <table class="table table-sm table-bordered align-middle mb-0 w-100 stock-purchase-table">
                            <thead class="stock-purchase-thead">
                                <tr>
                                    <th class="spr-th">Item</th>
                                    <th class="spr-th">Item Code</th>
                                    <th class="spr-th text-end">Unit</th>
                                    <th class="spr-th text-end">Quantity</th>
                                    <th class="spr-th text-end">Rate</th>
                                    <th class="spr-th text-end">Tax %</th>
                                    <th class="spr-th text-end">Tax Amount</th>
                                    <th class="spr-th text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $grandTotalAmount = 0; @endphp
                                @forelse($purchaseOrdersByVendor as $vendorGroup)
                                    <tr class="vendor-section-header-row">
                                        <td colspan="8" class="vendor-section-header small fw-semibold">
                                            <span class="d-inline-flex align-items-center gap-1">
                                                <span class="material-symbols-rounded fs-6 lh-1 opacity-75" aria-hidden="true">person</span>
                                                VENDOR : {{ $vendorGroup['vendor_name'] }}
                                            </span>
                                        </td>
                                    </tr>
                                    @php $vendorSectionTotal = 0; @endphp
                                    @foreach($vendorGroup['orders'] as $order)
                                        @php
                                            $storeName = $order->store ? $order->store->store_name : 'N/A';
                                            $billLabel = $storeName . '(Primary) Bill No. ' . ($order->po_number ?? $order->id) . ' (' . $order->po_date->format('d-m-Y') . ')';
                                            $billSubtotal = 0;
                                            $billTaxTotal = 0;
                                        @endphp
                                        <tr class="bill-header-row">
                                            <td colspan="8" class="bill-header small fw-semibold text-white">
                                                <span class="d-inline-flex align-items-center gap-1">
                                                    <span class="material-symbols-rounded fs-6 lh-1" aria-hidden="true">receipt</span>
                                                    {{ $billLabel }}
                                                </span>
                                            </td>
                                        </tr>
                                        @foreach($order->items as $item)
                                            @php
                                                $qty = $item->quantity ?? 0;
                                                $rate = $item->unit_price ?? 0;
                                                $taxPercent = $item->tax_percent ?? 0;
                                                $subtotal = $qty * $rate;
                                                $taxAmount = round($subtotal * ($taxPercent / 100), 2);
                                                $total = $subtotal + $taxAmount;
                                                $billSubtotal += $subtotal;
                                                $billTaxTotal += $taxAmount;
                                                $grandTotalAmount += $total;
                                            @endphp
                                            <tr class="spr-item-row">
                                                <td class="fw-medium">{{ $item->itemSubcategory->item_name ?? $item->itemSubcategory->subcategory_name ?? $item->itemSubcategory->name ?? 'N/A' }}</td>
                                                <td class="text-body-secondary">{{ $item->itemSubcategory->item_code ?? '—' }}</td>
                                                <td class="text-end text-body-secondary">{{ $item->unit ?? '—' }}</td>
                                                <td class="text-end spr-num">{{ number_format($qty, 2) }}</td>
                                                <td class="text-end spr-num">₹{{ number_format($rate, 1) }}</td>
                                                <td class="text-end spr-num">{{ number_format($taxPercent, 2) }}%</td>
                                                <td class="text-end spr-num">₹{{ number_format($taxAmount, 2) }}</td>
                                                <td class="text-end spr-num fw-semibold">₹{{ number_format($total, 2) }}</td>
                                            </tr>
                                        @endforeach
                                        @php
                                            $billTotal = $billSubtotal + $billTaxTotal;
                                            $vendorSectionTotal += $billTotal;
                                        @endphp
                                        <tr class="bill-total-row fw-semibold">
                                            <td colspan="7" class="text-end fw-bold small">Bill Total:</td>
                                            <td class="text-end fw-bold spr-num">₹{{ number_format($billTotal, 2) }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="vendor-total-row fw-semibold">
                                        <td colspan="7" class="text-end small">
                                            <span class="d-inline-flex align-items-center gap-1">
                                                <span class="material-symbols-rounded fs-6 lh-1" aria-hidden="true">functions</span>
                                                Vendor Total ({{ $vendorGroup['vendor_name'] }}):
                                            </span>
                                        </td>
                                        <td class="text-end spr-num">₹{{ number_format($vendorSectionTotal, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="p-0 border-0">
                                            <div class="spr-empty-state text-center py-5 px-3 m-2 rounded-3 border border-secondary-subtle border-dashed bg-body-secondary bg-opacity-10">
                                                <span class="material-symbols-rounded d-block mx-auto mb-3 fs-1 text-secondary lh-1" aria-hidden="true">shopping_cart_off</span>
                                                <h6 class="fw-semibold text-body-secondary mb-2">No purchase details found</h6>
                                                <p class="small text-body-tertiary mb-3 mb-sm-4">No records match the selected period, vendor, and store filters.</p>
                                                <span class="badge bg-body-secondary text-body-emphasis rounded-1 px-3 py-2 fw-normal border border-secondary-subtle">
                                                    <span class="material-symbols-rounded align-middle me-1 fs-6 lh-1" aria-hidden="true">lightbulb</span>
                                                    Try adjusting date range or filters
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                                @if($grandTotalAmount > 0)
                                    <tr class="grand-total-row fw-bold">
                                        <td colspan="7" class="text-end">
                                            <span class="d-inline-flex align-items-center gap-1">
                                                <span class="material-symbols-rounded fs-6 lh-1" aria-hidden="true">payments</span>
                                                Grand Total:
                                            </span>
                                        </td>
                                        <td class="text-end spr-num">₹{{ number_format($grandTotalAmount, 2) }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

    </div>

    {{-- Tom Select for vendor & store dropdowns (shared with other mess screens) --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.TomSelect === 'undefined') return;

            var repositionQueued = false;
            function queueRepositionOpenSprTomSelects() {
                if (repositionQueued) return;
                repositionQueued = true;
                requestAnimationFrame(function () {
                    repositionQueued = false;
                    document.querySelectorAll('.stock-purchase-report select.choices-select').forEach(function (sel) {
                        if (sel.tomselect && sel.tomselect.isOpen && typeof sel.tomselect.positionDropdown === 'function') {
                            sel.tomselect.positionDropdown();
                        }
                    });
                });
            }

            /** Portaled to body + position:fixed so theme .page-wrapper { overflow-x:hidden } cannot clip the list */
            function pinStockPurchaseDropdownBelow(ts) {
                var control = ts.control;
                var dd = ts.dropdown;
                if (!control || !dd) return;
                var rect = control.getBoundingClientRect();
                var gap = 2;
                var w = Math.max(rect.width, 160);
                var left = rect.left;
                if (left + w > window.innerWidth - 8) {
                    left = Math.max(8, window.innerWidth - w - 8);
                }
                dd.style.position = 'fixed';
                dd.style.top = (rect.bottom + gap) + 'px';
                dd.style.left = left + 'px';
                dd.style.width = w + 'px';
                dd.style.right = 'auto';
                dd.style.bottom = 'auto';
                var content = ts.dropdown_content;
                if (content) {
                    var maxH = Math.max(120, window.innerHeight - rect.bottom - gap - 12);
                    content.style.maxHeight = maxH + 'px';
                }
            }

            window.addEventListener('scroll', queueRepositionOpenSprTomSelects, true);
            window.addEventListener('resize', queueRepositionOpenSprTomSelects);

            document
                .querySelectorAll('.stock-purchase-report select.choices-select')
                .forEach(function (el) {
                    if (el.dataset.tomselectInitialized === 'true') return;

                    var placeholder = el.getAttribute('data-placeholder') || 'Select';

                    var ts = new TomSelect(el, {
                        placeholder: placeholder,
                        maxItems: null,
                        maxOptions: 500,
                        plugins: ['remove_button', 'dropdown_input'],
                        sortField: {
                            field: 'text',
                            direction: 'asc'
                        },
                        dropdownParent: 'body',
                        dropdownClass: 'stock-purchase-ts-dropdown-portal'
                    });

                    ts.positionDropdown = function () {
                        pinStockPurchaseDropdownBelow(this);
                    };

                    el.dataset.tomselectInitialized = 'true';
                });
        });
    </script>
<script>
function printStockPurchaseTable() {
    var tableEl = document.querySelector('.stock-purchase-report .stock-purchase-table-wrapper table.stock-purchase-table');
    if (!tableEl) {
        tableEl = document.querySelector('.stock-purchase-report .report-content table');
    }
    if (!tableEl) {
        alert('No table data found to print.');
        return;
    }
    var table = tableEl.cloneNode(true);

    // Clean clone for print
    var clonedThead = table.querySelector('thead');
    if (clonedThead) {
        clonedThead.style.display = '';
        clonedThead.style.visibility = 'visible';
        clonedThead.removeAttribute('hidden');
        clonedThead.querySelectorAll('th').forEach(function(th) {
            th.style.position = 'static';
            th.style.boxShadow = 'none';
            th.style.top = '';
            th.style.zIndex = '';
        });
    }
    // Remove the JS-cloned sticky header if present in the clone
    var stickyClone = table.querySelector('.spr-sticky-head');
    if (stickyClone) stickyClone.remove();

    // Force border-collapse for print
    table.style.borderCollapse = 'collapse';
    table.style.borderSpacing = '0';
    table.style.width = '100%';

    table.querySelectorAll('tr').forEach(function(tr) {
        tr.style.display = '';
        tr.removeAttribute('hidden');
    });

    // Remove Material Symbols icons from clone (they don't render in print popup)
    table.querySelectorAll('.material-symbols-rounded, .material-icons').forEach(function(icon) {
        icon.remove();
    });

    var printWindow = window.open('', '_blank');
    if (!printWindow) {
        window.print();
        return;
    }

    var title = 'Stock Purchase Details';
    var cfgEl = document.getElementById('stock-purchase-print-config');
    var printCfg = {};
    try {
        printCfg = cfgEl && cfgEl.getAttribute('data-config')
            ? JSON.parse(cfgEl.getAttribute('data-config'))
            : {};
    } catch (e) {
        printCfg = {};
    }
    var dateRange = printCfg.dateRange || @json($stockPurchasePrintDateRange);
    var vendorLine = printCfg.vendorLine || @json($stockPurchasePrintVendorLine);
    var vendorHeaderLabel = printCfg.vendorHeaderLabel || @json($stockPurchasePrintVendorHeaderLabel);
    var vendorDetailRows = Array.isArray(printCfg.vendorDetailRows) ? printCfg.vendorDetailRows : @json($stockPurchasePrintVendorDetailRows);
    var storeDetails = printCfg.storeDetails || @json($stockPurchasePrintStoreDetails);

    var vendorDetailsHtml = '';
    if (vendorDetailRows.length > 0) {
        vendorDetailsHtml =
            '<table class="vendor-detail-table">' +
            '<thead><tr><th>Vendor</th><th>Contact</th><th>Phone</th><th>Email</th><th>Address</th></tr></thead>' +
            '<tbody>' +
            vendorDetailRows.map(function (row) {
                return '<tr>' +
                    '<td>' + (row.name || '\u2014') + '</td>' +
                    '<td>' + (row.contact_person || '\u2014') + '</td>' +
                    '<td>' + (row.phone || '\u2014') + '</td>' +
                    '<td>' + (row.email || '\u2014') + '</td>' +
                    '<td>' + (row.address || '\u2014') + '</td>' +
                '</tr>';
            }).join('') +
            '</tbody></table>';
    }

    var emblemUrl = '{{ asset("images/ashoka.png") }}';
    var logoUrl = '{{ asset("admin_assets/images/logos/logo.png") }}';

    printWindow.document.open();
    printWindow.document.write('<!doctype html>\n' +
'<html lang="en">\n' +
'<head>\n' +
'    <meta charset="utf-8">\n' +
'    <title>' + title + ' - OFFICER\'S MESS LBSNAA MUSSOORIE</title>\n' +
'    <style>\n' +
'        *, *::before, *::after { box-sizing: border-box; }\n' +
'        body {\n' +
'            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;\n' +
'            font-size: 11px;\n' +
'            color: #212529;\n' +
'            -webkit-print-color-adjust: exact;\n' +
'            print-color-adjust: exact;\n' +
'            margin: 0;\n' +
'            padding: 12mm 10mm;\n' +
'        }\n' +
'\n' +
'        /* ── Print Header ── */\n' +
'        .print-header {\n' +
'            display: flex;\n' +
'            align-items: center;\n' +
'            gap: 12px;\n' +
'            border-bottom: 3px solid #004a93;\n' +
'            padding-bottom: 10px;\n' +
'            margin-bottom: 12px;\n' +
'        }\n' +
'        .print-header img { height: 48px; width: auto; object-fit: contain; }\n' +
'        .header-text { flex: 1; }\n' +
'        .header-text .line1 { font-size: 9px; text-transform: uppercase; letter-spacing: 0.08em; color: #004a93; font-weight: 600; margin: 0; }\n' +
'        .header-text .line2 { font-size: 14px; font-weight: 700; text-transform: uppercase; color: #1a1a1a; margin: 2px 0 0; }\n' +
'        .header-text .line3 { font-size: 9px; color: #555; margin: 1px 0 0; }\n' +
'\n' +
'        /* ── Report Title & Meta ── */\n' +
'        .report-title-block { text-align: center; margin-bottom: 10px; }\n' +
'        .report-title-block h2 { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; margin: 0 0 4px; color: #1a1a1a; }\n' +
'        .date-pill {\n' +
'            display: inline-block;\n' +
'            background: #004a93;\n' +
'            color: #fff;\n' +
'            padding: 3px 14px;\n' +
'            border-radius: 10px;\n' +
'            font-size: 10px;\n' +
'            font-weight: 500;\n' +
'            -webkit-print-color-adjust: exact;\n' +
'            print-color-adjust: exact;\n' +
'            border: 1px solid #004a93;\n' +
'        }\n' +
'        @media print {\n' +
'            .date-pill {\n' +
'                background: #004a93 !important;\n' +
'                color: #fff !important;\n' +
'                -webkit-print-color-adjust: exact !important;\n' +
'                print-color-adjust: exact !important;\n' +
'            }\n' +
'            /* Fallback if browser ignores background */\n' +
'            .date-pill-fallback {\n' +
'                display: block;\n' +
'                text-align: center;\n' +
'                font-size: 10px;\n' +
'                font-weight: 700;\n' +
'                color: #004a93;\n' +
'                margin-top: 2px;\n' +
'            }\n' +
'        }\n' +
'        .date-pill-fallback {\n' +
'            display: none;\n' +
'        }\n' +
'        .report-meta {\n' +
'            font-size: 10px;\n' +
'            line-height: 1.7;\n' +
'            margin: 8px 0 10px;\n' +
'            color: #333;\n' +
'        }\n' +
'        .report-meta strong { color: #1a1a1a; }\n' +
'\n' +
'        /* ── Vendor Detail Table ── */\n' +
'        .vendor-detail-table { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 10px; }\n' +
'        .vendor-detail-table th, .vendor-detail-table td { border: 1px solid #ccc; padding: 3px 6px; vertical-align: top; }\n' +
'        .vendor-detail-table th { background: #f0f0f0; font-weight: 600; }\n' +
'\n' +
'        /* ── Data Table ── */\n' +
'        .data-table { width: 100%; border-collapse: collapse; font-size: 10px; }\n' +
'        .data-table th, .data-table td { padding: 4px 6px; border: 1px solid #bbb; vertical-align: middle; }\n' +
'        .data-table thead th { background: #004a93; color: #fff; font-weight: 600; font-size: 10px; text-align: left; }\n' +
'        .data-table thead th.text-end { text-align: right; }\n' +
'        .data-table .text-end { text-align: right; }\n' +
'\n' +
'        /* Vendor header */\n' +
'        .data-table .vendor-section-header-row td,\n' +
'        .data-table td.vendor-section-header {\n' +
'            background: #e9ecef;\n' +
'            color: #111;\n' +
'            font-weight: 700;\n' +
'            font-size: 10px;\n' +
'            text-transform: uppercase;\n' +
'            letter-spacing: 0.03em;\n' +
'            border-color: #adb5bd;\n' +
'        }\n' +
'\n' +
'        /* Bill header */\n' +
'        .data-table .bill-header-row td,\n' +
'        .data-table td.bill-header {\n' +
'            background: #5a6268;\n' +
'            color: #fff;\n' +
'            font-weight: 600;\n' +
'        }\n' +
'\n' +
'        /* Bill total */\n' +
'        .data-table .bill-total-row td {\n' +
'            background: #f4f5f6;\n' +
'            font-weight: 700;\n' +
'            border-top: 1px dashed #aaa;\n' +
'        }\n' +
'\n' +
'        /* Vendor total */\n' +
'        .data-table .vendor-total-row td {\n' +
'            background: #dee2e6;\n' +
'            font-weight: 700;\n' +
'            border-top: 2px solid #004a93;\n' +
'            color: #004a93;\n' +
'        }\n' +
'\n' +
'        /* Grand total */\n' +
'        .data-table .grand-total-row td {\n' +
'            background: #004a93;\n' +
'            color: #fff;\n' +
'            font-weight: 700;\n' +
'            font-size: 11px;\n' +
'            border-top: 3px double #002d5e;\n' +
'        }\n' +
'\n' +
'        /* Alternating item rows */\n' +
'        .data-table .spr-item-row:nth-child(even) td { background: #f9fafb; }\n' +
'\n' +
'        /* ── Print-specific ── */\n' +
'        @page { size: A4 landscape; margin: 8mm; }\n' +
'        @media print {\n' +
'            body { padding: 0; }\n' +
'            thead { display: table-header-group; }\n' +
'            tr { page-break-inside: avoid; }\n' +
'        }\n' +
'    </style>\n' +
'</head>\n' +
'<body>\n' +
'\n' +
'<div class="print-header">\n' +
'    <img src="' + emblemUrl + '" alt="Emblem">\n' +
'    <div class="header-text">\n' +
'        <p class="line1">Government of India</p>\n' +
'        <p class="line2">OFFICER\'S MESS LBSNAA MUSSOORIE</p>\n' +
'        <p class="line3">Lal Bahadur Shastri National Academy of Administration</p>\n' +
'    </div>\n' +
'    <img src="' + logoUrl + '" alt="LBSNAA Logo" onerror="this.style.display=\'none\'">\n' +
'</div>\n' +
'\n' +
'<div class="report-title-block">\n' +
'    <h2>' + title + '</h2>\n' +
'    <p style="font-size:11px;font-weight:700;color:#004a93;margin:4px 0 0;text-align:center;">' + dateRange + '</p>\n' +
'</div>\n' +
'\n' +
'<div class="report-meta">\n' +
'    <strong>' + vendorHeaderLabel + '</strong> ' + vendorLine + ' &nbsp;&nbsp;|&nbsp;&nbsp; ' +
'    <strong>Store:</strong> ' + storeDetails + ' &nbsp;&nbsp;|&nbsp;&nbsp; ' +
'    <strong>Printed:</strong> ' + new Date().toLocaleDateString('en-IN') + ' ' + new Date().toLocaleTimeString('en-IN', {hour:'2-digit',minute:'2-digit'}) + '\n' +
'</div>\n' +
'\n' +
    (vendorDetailsHtml ? '<p style="font-size:10px;font-weight:600;margin:0 0 4px;">Vendor Details</p>\n' + vendorDetailsHtml + '\n' : '') +
'\n' +
'<table class="data-table">\n' + table.innerHTML + '\n</table>\n' +
'\n' +
'<script>\n' +
'    window.addEventListener("load", function() {\n' +
'        setTimeout(function() { window.print(); }, 300);\n' +
'    });\n' +
'<\/script>\n' +
'</body>\n' +
'</html>');
    printWindow.document.close();
}
</script>

<style>
/* Matdash .page-wrapper uses overflow-x:hidden; clip avoids extra scrollport that clips Tom Select (same idea as Item Report) */
@media screen {
    .page-wrapper:has(.stock-purchase-report) {
        overflow-x: clip !important;
    }
}

.stock-purchase-report .spr-filter-card,
.stock-purchase-report .spr-filter-card .card-body,
.stock-purchase-report .spr-filter-card .row,
.stock-purchase-report .spr-filter-card [class*='col-'] {
    overflow: visible;
}

/* Body-portaled Tom Select; z-index above report card / sticky chrome */
.stock-purchase-ts-dropdown-portal {
    z-index: 1100 !important;
}

.stock-purchase-report .spr-filter-icon-circle {
    width: 2.375rem;
    height: 2.375rem;
}
.stock-purchase-report .spr-meta-badge {
    max-width: min(100%, 42rem);
}

/* Auto height and width for report container and content */
.stock-purchase-report {
    width: 100%;
    max-width: 100%;
    min-height: 0;
    height: auto;
}
.stock-purchase-report .report-area {
    width: 100%;
    height: auto;
}
.stock-purchase-report .report-content {
    width: 100%;
    height: auto;
}
.stock-purchase-report .report-content .card-body {
    width: 100%;
    height: auto;
}
.stock-purchase-report .table-responsive {
    width: 100%;
    overflow-x: auto;
}
@media screen {
    .stock-purchase-report .stock-purchase-table-wrapper {
        max-height: min(72vh, 760px);
        overflow: auto !important;
        position: relative;
    }
    /* border-collapse: separate is required for position:sticky to work */
    .stock-purchase-report .stock-purchase-table-wrapper .stock-purchase-table {
        border-collapse: separate !important;
        border-spacing: 0;
        width: 100%;
    }
    .stock-purchase-report .stock-purchase-table-wrapper .stock-purchase-thead th {
        position: sticky !important;
        top: 0;
        z-index: 10;
        background: #0b4a7e !important;
        color: #fff !important;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        border-bottom: 2px solid #004a93;
        /* fill gap caused by border-collapse:separate */
        border-top: none;
        border-left: 1px solid rgba(255,255,255,0.15);
        border-right: 1px solid rgba(255,255,255,0.15);
    }
    .stock-purchase-report .stock-purchase-table-wrapper .stock-purchase-thead th:first-child {
        border-left: none;
    }
    .stock-purchase-report .stock-purchase-table-wrapper .stock-purchase-thead th:last-child {
        border-right: none;
    }
}
.stock-purchase-report .table-responsive table {
    width: 100%;
    height: auto;
}

.mess-title-tracking { letter-spacing: 0.04em; }
.mess-report-date-pill { background-color: #003366 !important; }
.stock-purchase-report .stock-purchase-table { font-size: clamp(11.5px, 0.7rem + 0.22vw, 13px); }

/* Table header */
.stock-purchase-report .spr-th {
    background: #0b4a7e;
    color: #fff;
    font-weight: 600;
    padding: 0.6rem 0.75rem;
    text-align: left;
    white-space: nowrap;
    border-color: rgba(255, 255, 255, 0.15);
    font-size: 0.8125rem;
    letter-spacing: 0.01em;
}
.stock-purchase-report .spr-th.text-end { text-align: right; }

/* Tabular numbers */
.stock-purchase-report .spr-num {
    font-variant-numeric: tabular-nums;
}

/* Vendor section header */
.stock-purchase-report .vendor-section-header-row .vendor-section-header {
    background: linear-gradient(135deg, #eef2f6 0%, #e8edf2 100%);
    color: #0f172a;
    font-weight: 700;
    padding: 0.6rem 0.75rem;
    border-top: 2px solid #cbd5e1;
    border-bottom: 1px solid #d8dee6;
    font-size: 0.8125rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

/* Vendor total row */
.stock-purchase-report .vendor-total-row td {
    background: #e8edf4 !important;
    padding: 0.5rem 0.75rem;
    border-top: 2px solid #0b4a7e;
    color: #0b4a7e;
    font-weight: 700;
}

/* Bill header */
.stock-purchase-report .bill-header-row .bill-header {
    background: linear-gradient(135deg, #475569 0%, #334155 100%);
    color: #fff;
    font-weight: 600;
    padding: 0.5rem 0.75rem;
    border-color: #475569;
}

/* Bill total row */
.stock-purchase-report .bill-total-row { background-color: #f8fafc; }
.stock-purchase-report .bill-total-row td {
    padding: 0.4rem 0.75rem;
    border-top: 1px dashed #cbd5e1;
    color: #334155;
}

/* Grand total row */
.stock-purchase-report .grand-total-row td {
    padding: 0.6rem 0.75rem;
    border-top: 3px double #0b4a7e !important;
    background: linear-gradient(135deg, #0b4a7e 0%, #1a6fa0 100%) !important;
    color: #fff !important;
    font-size: 0.875rem;
}

/* Item rows */
.stock-purchase-report .spr-item-row td {
    padding: 0.4rem 0.75rem;
    vertical-align: middle;
    transition: background-color 0.15s ease;
    white-space: nowrap;
}
.stock-purchase-report .spr-item-row td:first-child {
    white-space: normal;
    word-break: break-word;
}
.stock-purchase-report .spr-item-row:nth-child(even) td {
    background-color: #fafbfc;
}
.stock-purchase-report .spr-item-row:hover td {
    background-color: #eef4fb !important;
}

/* Scrollbar */
.stock-purchase-report .stock-purchase-table-wrapper::-webkit-scrollbar { height: 10px; width: 10px; }
.stock-purchase-report .stock-purchase-table-wrapper::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 8px; }
.stock-purchase-report .stock-purchase-table-wrapper::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }
.stock-purchase-report .stock-purchase-table-wrapper::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

/* Focus visible on scroll container */
.stock-purchase-report .stock-purchase-table-wrapper:focus-visible {
    box-shadow: 0 0 0 3px rgba(11, 74, 126, 0.28);
    outline: none;
}

.stock-purchase-report .page-input { display: inline-block; }
.report-date-bar { background: #004a93; color: #fff; font-size: 0.9rem; text-align: center; }
.report-vendor-name { font-size: 1rem; }

/* Transitions */
.stock-purchase-report .btn {
    transition: all 0.2s ease-in-out;
}
.stock-purchase-report .btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}
.stock-purchase-report .btn:active:not(:disabled) {
    transform: translateY(0);
    box-shadow: none;
}
.stock-purchase-report .form-control,
.stock-purchase-report .form-select {
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.stock-purchase-report .form-control:focus,
.stock-purchase-report .form-select:focus {
    border-color: #0b4a7e;
    box-shadow: 0 0 0 0.2rem rgba(11, 74, 126, 0.12);
}
.stock-purchase-report .badge {
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.stock-purchase-report .report-header .badge:hover {
    transform: scale(1.03);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
.stock-purchase-report .card-header .material-symbols-rounded {
    transition: transform 0.3s ease;
}
.stock-purchase-report .card-header:hover .material-symbols-rounded {
    transform: rotate(15deg);
}

@keyframes spr-fade-in {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
.stock-purchase-report > .card,
.stock-purchase-report > .report-area {
    animation: spr-fade-in 0.35s ease-out both;
}
.stock-purchase-report > .report-area {
    animation-delay: 0.1s;
}

@media print {
    /* Hide entire app chrome – only report content prints */
    .topbar,
    #main-wrapper > .topbar,
    .page-wrapper > #sidebarTabContent,
    .page-wrapper > div:first-child:not(.body-wrapper),
    .sidebarmenu,
    .sargam-loader { display: none !important; }
    .no-print { display: none !important; }
    .stock-purchase-report .report-toolbar { display: none !important; }
    .stock-purchase-report .report-area > .mt-3 { display: none !important; }
    /* Full width, no sidebar offset */
    body, html { margin: 0 !important; padding: 0 !important; }
    #main-wrapper { padding: 0 !important; }
    .page-wrapper { padding: 0 !important; }
    .body-wrapper { margin: 0 !important; margin-left: 0 !important; width: 100% !important; max-width: 100% !important; }
    main#main-content { margin: 0 !important; padding: 0 !important; }
    .tab-content { padding: 0 !important; }
    .tab-pane { display: block !important; }
    /* Report only – no extra padding */
    .stock-purchase-report { padding: 0 !important; margin: 0 !important; }
    .report-content { box-shadow: none !important; border: none !important; }
    .report-content .card-body { padding: 0 !important; }
    .report-header { margin-top: 0 !important; margin-bottom: 1rem !important; }
    html { font-size: 11pt !important; }
    .report-title-center { font-size: 11pt !important; color: #000 !important; text-align: center !important; }
    .report-date-bar { background: #5a6268 !important; color: #fff !important; text-align: center !important; font-size: 11pt !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .report-vendor-name { font-size: 11pt !important; color: #000 !important; margin-bottom: 0.75rem !important; }
    .report-store-name { font-size: 11pt !important; }
    body { font-size: 11pt !important; font-family: "DejaVu Sans", Arial, Helvetica, sans-serif !important; }
    .stock-purchase-table { font-size: 11pt !important; border-collapse: collapse !important; }
    .stock-purchase-table th, .stock-purchase-table td { font-size: 11pt !important; }
    .stock-purchase-table td, .stock-purchase-table th { border: 1px solid #333 !important; }
    .stock-purchase-thead th, .stock-purchase-thead .spr-th { background: #0b4a7e !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; position: static !important; box-shadow: none !important; }
    .stock-purchase-report .stock-purchase-table-wrapper { max-height: none !important; overflow: visible !important; }
    .stock-purchase-report .bill-header-row .bill-header { background: #475569 !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .stock-purchase-report .vendor-section-header { background: #eef2f6 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .stock-purchase-report .vendor-total-row td { background: #e8edf4 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .stock-purchase-report .grand-total-row td { background: #0b4a7e !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .stock-purchase-report .spr-sticky-head { display: none !important; }
}
</style>

<script>
(function () {
    function initPurchaseStickyHeader() {
        var scroller = document.querySelector('.stock-purchase-report .stock-purchase-table-wrapper');
        var table = scroller ? scroller.querySelector('table.stock-purchase-table') : null;
        var thead = table ? table.querySelector('thead') : null;
        if (!scroller || !table || !thead) return;

        // Remove any previously created sticky header
        var old = scroller.querySelector('.spr-sticky-head');
        if (old) old.remove();

        // Create sticky wrapper
        var stickyWrap = document.createElement('div');
        stickyWrap.className = 'spr-sticky-head';
        stickyWrap.style.cssText = 'position:sticky;top:0;z-index:20;overflow:hidden;background:#0b4a7e;';

        var stickyTable = document.createElement('table');
        stickyTable.style.cssText = 'width:100%;border-collapse:separate;border-spacing:0;margin:0;';

        var clonedThead = thead.cloneNode(true);
        // Force styles on cloned th
        clonedThead.querySelectorAll('th').forEach(function(th) {
            th.style.cssText = 'background:#0b4a7e !important;color:#fff !important;font-weight:600;padding:0.6rem 0.75rem;border:1px solid rgba(255,255,255,0.15);border-bottom:2px solid #004a93;box-shadow:0 2px 4px rgba(0,0,0,0.1);font-size:0.8125rem;white-space:nowrap;';
        });
        // Preserve text-end alignment
        clonedThead.querySelectorAll('th.text-end').forEach(function(th) {
            th.style.textAlign = 'right';
        });

        stickyTable.appendChild(clonedThead);
        stickyWrap.appendChild(stickyTable);

        // Insert clone before table
        scroller.insertBefore(stickyWrap, table);

        function syncWidths() {
            stickyTable.style.width = table.offsetWidth + 'px';
            var origThs = thead.querySelectorAll('th');
            var stickyThs = stickyTable.querySelectorAll('th');
            if (!origThs.length || origThs.length !== stickyThs.length) return;
            for (var i = 0; i < origThs.length; i++) {
                var w = origThs[i].getBoundingClientRect().width;
                stickyThs[i].style.width = w + 'px';
                stickyThs[i].style.minWidth = w + 'px';
                stickyThs[i].style.maxWidth = w + 'px';
            }
            // Overlap the real header exactly
            stickyWrap.style.marginBottom = '-' + thead.offsetHeight + 'px';
        }

        syncWidths();

        // Hide real thead visually but keep for column sizing
        thead.style.visibility = 'hidden';

        // Sync horizontal scroll
        scroller.addEventListener('scroll', function () {
            stickyTable.style.transform = 'translateX(' + (-scroller.scrollLeft) + 'px)';
        });

        // Re-sync widths on resize
        var resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                if (!document.body.contains(scroller)) return;
                thead.style.visibility = 'visible';
                syncWidths();
                thead.style.visibility = 'hidden';
            }, 150);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPurchaseStickyHeader);
    } else {
        initPurchaseStickyHeader();
    }
})();
</script>
@endsection