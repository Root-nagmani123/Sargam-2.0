@extends('admin.layouts.master')
@section('title', 'Stock Purchase Details Report')
@section('content')
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
<div class="container-fluid stock-purchase-report">
    <div id="stock-purchase-print-config" class="d-none" hidden
         data-config="{{ htmlspecialchars($stockPurchasePrintConfigJson, ENT_QUOTES, 'UTF-8') }}"></div>
    <x-breadcrum title="Stock Purchase Details Report" :showBack="false"></x-breadcrum>
    {{-- Download / Print bar --}}
    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        <a href="{{ route('admin.mess.reports.stock-purchase-details.excel', request()->query()) }}" class="btn spr-export-btn" title="Download (Excel)">
            <i class="material-symbols-rounded">download</i><span>Download</span>
        </a>
        <button type="button" class="btn spr-export-btn" onclick="printStockPurchaseTable()" title="Print (or Save as PDF)">
            <i class="material-symbols-rounded">print</i><span>Print</span>
        </button>
    </div>

    <!-- Report Area (full width below filters) -->
    <div class="report-area">
            <!-- Report content -->
            <div class="report-content card border-0 shadow-sm rounded-3">
                <div class="card-body p-3 p-lg-4">
                    {{-- Filter toolbar (single row, auto-apply) --}}
                    <div class="d-flex align-items-center gap-2 mb-4 spr-toolbar no-print">
                        <form method="GET" action="{{ route('admin.mess.reports.stock-purchase-details') }}" id="sprFilterForm" class="d-flex align-items-center gap-2 spr-filter-form">
                            <input type="hidden" name="refresh" value="1">
                            <span class="programme-dt-filters-label flex-shrink-0">Filter</span>
                            <div class="spr-filter-item">
                                <input type="text" id="spr_date_range" class="form-control spr-filter-range" placeholder="Select date range" autocomplete="off" readonly>
                                {{-- Range picker fills these; names preserved for the backend. --}}
                                <input type="hidden" name="from_date" id="from_date" value="{{ $fromDate }}">
                                <input type="hidden" name="to_date" id="to_date" value="{{ $toDate }}">
                            </div>
                            <div class="spr-filter-item">
                                <select name="vendor_id[]" class="form-select choices-select spr-auto-filter" multiple data-placeholder="All Vendors">
                                    @foreach($vendors as $vendor)
                                        <option value="{{ $vendor->id }}" @selected(in_array((int) $vendor->id, $selectedVendorIds, true))>{{ $vendor->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="spr-filter-item">
                                <select name="store_id[]" class="form-select choices-select spr-auto-filter" multiple data-placeholder="All Stores">
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}" @selected(in_array((int) $store->id, $selectedStoreIds, true))>{{ $store->store_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            {{-- Rows-per-page lives in the filter form so sprApplyFilters() carries it
                                 (and resets to page 1) exactly like every other filter. --}}
                            <input type="hidden" name="per_page" id="sprPerPageHidden" value="{{ (int) request('per_page', 50) }}">
                            <a href="{{ route('admin.mess.reports.stock-purchase-details') }}" id="sprRemoveFilter" class="programme-dt-btn-reset flex-shrink-0 d-inline-flex align-items-center justify-content-center text-decoration-none" title="Remove all filters">Remove Filter</a>
                            {{-- Search sits INSIDE the filter form: sprApplyFilters() serialises the
                                 form, so the term reaches the server and narrows the whole report
                                 (line count, grand total and pager included), not just this page. --}}
                            <input type="search" name="search" id="sprSearch"
                                   class="form-control spr-search-input {{ filled(request('search')) ? '' : 'd-none' }}"
                                   placeholder="Search item, vendor, bill…" autocomplete="off" value="{{ request('search') }}">
                        </form>
                        <div class="d-flex align-items-center gap-2 ms-auto flex-shrink-0">
                            <button type="button" class="btn programme-dt-btn-columns" id="sprColumnsBtn"
                                    data-bs-toggle="modal" data-bs-target="#sprColumnsModal" title="Show / hide columns">
                                <i class="material-symbols-rounded">view_column</i><span>Columns</span>
                            </button>
                            @include('mess.partials.search-toggle', ['inputId' => 'sprSearch'])
                        </div>
                    </div>

                    {{-- Column visibility modal. The table is hand-written (not a DataTable),
                         so the toggles hide the tagged header + body cells directly and the
                         grouped vendor / bill rows have their colspan recomputed. --}}
                    <div class="modal fade" id="sprColumnsModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <div class="modal-header border-0 pb-2">
                                    <h5 class="modal-title fw-bold">Column Visibility</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body pt-0">
                                    <hr class="mt-0">
                                    <div class="d-flex flex-column gap-2">
                                        <label class="spr-colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0"><input type="checkbox" class="form-check-input m-0 spr-col-toggle" data-col="code" checked> <span>Item Code</span></label>
                                        <label class="spr-colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0"><input type="checkbox" class="form-check-input m-0 spr-col-toggle" data-col="unit" checked> <span>Unit</span></label>
                                        <label class="spr-colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0"><input type="checkbox" class="form-check-input m-0 spr-col-toggle" data-col="rate" checked> <span>Rate</span></label>
                                        <label class="spr-colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0"><input type="checkbox" class="form-check-input m-0 spr-col-toggle" data-col="taxpc" checked> <span>Tax %</span></label>
                                        <label class="spr-colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0"><input type="checkbox" class="form-check-input m-0 spr-col-toggle" data-col="taxamt" checked> <span>Tax Amount</span></label>
                                    </div>
                                </div>
                                <div class="modal-footer border-0"><button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button></div>
                            </div>
                        </div>
                    </div>

                    <div id="stock-purchase-table-wrap">
                        @include('admin.mess.reports.partials.stock-purchase-details-table', [
                            'purchaseDetailLines' => $purchaseDetailLines,
                            'reportPage' => $reportPage,
                            'reportGrandTotalAmount' => $reportGrandTotalAmount,
                            'reportLineCount' => $reportLineCount,
                        ])
                    </div>
                </div>
            </div>

    </div>

    {{-- Tom Select for vendor & store dropdowns (deferred so table paints first) --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js" defer></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @if(isset($reportTimingMs))
            console.info(
                '[Stock Purchase Details] server {{ $reportTimingMs }} ms'
                @if(isset($reportCacheStatus))
                + ', cache {{ $reportCacheStatus }}'
                @endif
                @if(isset($reportLineCount))
                + ', {{ $reportLineCount }} lines total'
                @endif
            );
            @endif

            var tableWrap = document.getElementById('stock-purchase-table-wrap');

            function hookPagination() {
                if (!tableWrap) return;
                tableWrap.querySelectorAll('.pagination a').forEach(function (a) {
                    a.addEventListener('click', function (e) {
                        e.preventDefault();
                        ajaxLoad(this.href);
                    });
                });
            }

            function ajaxLoad(url) {
                if (!tableWrap || !url) return;
                var targetUrl = url;
                if (!/[?&]ajax=1(?:&|$)/.test(url)) {
                    var sep = url.indexOf('?') === -1 ? '?' : '&';
                    targetUrl = url + sep + 'ajax=1';
                }
                tableWrap.style.opacity = '0.55';
                fetch(targetUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) {
                        var ms = r.headers.get('X-Stock-Purchase-Details-Ms');
                        var cache = r.headers.get('X-Stock-Purchase-Details-Cache');
                        if (ms) {
                            console.info('[Stock Purchase Details] page ' + ms + ' ms' + (cache ? ', cache ' + cache : ''));
                        }
                        return r.text();
                    })
                    .then(function (html) {
                        tableWrap.innerHTML = html;
                        tableWrap.style.opacity = '';
                        hookPagination();
                        if (typeof window.sprApplyColumnVisibility === 'function') window.sprApplyColumnVisibility();
                    })
                    .catch(function (e) {
                        tableWrap.style.opacity = '';
                        console.error('Stock purchase pagination failed', e);
                    });
            }

            // Apply the filter form via AJAX — updates the table in place, no full page reload.
            function sprApplyFilters() {
                var form = document.getElementById('sprFilterForm');
                if (!form || !tableWrap) return;
                if (form.reportValidity && !form.reportValidity()) return;
                var params = new URLSearchParams();
                new FormData(form).forEach(function (v, k) {
                    if (v === '' || v === null) return;
                    params.append(k, v);
                });
                var qs = params.toString();
                var url = form.action + (qs ? '?' + qs : '');
                if (window.history && window.history.pushState) {
                    try { window.history.pushState({ sprFilter: true }, '', url); } catch (e) {}
                }
                ajaxLoad(url);
            }

            window.addEventListener('popstate', function () {
                if (tableWrap) ajaxLoad(window.location.href);
            });

            // Rows-per-page select — re-rendered by every AJAX swap, so listen on the document.
            document.addEventListener('change', function (e) {
                if (!e.target || e.target.id !== 'sprPerPage') return;
                var hidden = document.getElementById('sprPerPageHidden');
                if (hidden) hidden.value = e.target.value;
                sprApplyFilters();
            });

            var sprSearchEl = document.getElementById('sprSearch');
            if (sprSearchEl) {
                var sprSearchTimer = null;
                sprSearchEl.addEventListener('input', function () {
                    if (sprSearchTimer) clearTimeout(sprSearchTimer);
                    sprSearchTimer = setTimeout(sprApplyFilters, 400);
                });
            }

            // Column visibility: hide the tagged cells, then fix up the colspan on the
            // vendor / bill / total rows so they still span the full table.
            function sprApplyColumnVisibility() {
                var hidden = [];
                document.querySelectorAll('.spr-col-toggle').forEach(function (cb) {
                    var col = cb.getAttribute('data-col');
                    if (!cb.checked) hidden.push(col);
                    document.querySelectorAll('.stock-purchase-report [data-col="' + col + '"]').forEach(function (el) {
                        el.style.display = cb.checked ? '' : 'none';
                    });
                });

                var table = document.querySelector('.stock-purchase-report table.stock-purchase-table');
                if (!table) return;
                var headRow = table.querySelector('thead tr');
                if (!headRow) return;
                var headCells = Array.prototype.slice.call(headRow.children);
                table.querySelectorAll('tbody td[colspan]').forEach(function (td) {
                    if (!td.getAttribute('data-colspan-base')) {
                        td.setAttribute('data-colspan-base', td.getAttribute('colspan'));
                    }
                    // Every spanning cell here starts at column 0, so it loses one
                    // column for each hidden header cell inside its span.
                    var base = parseInt(td.getAttribute('data-colspan-base'), 10) || 1;
                    var gone = headCells.slice(0, base).filter(function (th) {
                        return th.style.display === 'none';
                    }).length;
                    td.setAttribute('colspan', Math.max(1, base - gone));
                });
            }

            document.querySelectorAll('.spr-col-toggle').forEach(function (cb) {
                cb.addEventListener('change', sprApplyColumnVisibility);
            });
            // The table is re-rendered by ajaxLoad(), so re-apply after every swap.
            window.sprApplyColumnVisibility = sprApplyColumnVisibility;

            hookPagination();

            function initTomSelect() {
                if (typeof window.TomSelect === 'undefined') return;
                document.querySelectorAll('.stock-purchase-report select.choices-select').forEach(function (el) {
                    if (el.dataset.tomselectInitialized === 'true') return;
                    new TomSelect(el, {
                        placeholder: el.getAttribute('data-placeholder') || 'Select',
                        maxItems: null,
                        maxOptions: 500,
                        plugins: ['remove_button', 'dropdown_input'],
                        sortField: { field: 'text', direction: 'asc' }
                    });
                    el.dataset.tomselectInitialized = 'true';
                });
            }

            if ('requestIdleCallback' in window) {
                requestIdleCallback(initTomSelect, { timeout: 1500 });
            } else {
                setTimeout(initTomSelect, 100);
            }

            // Combined From–To dual-month range picker → fills hidden from_date/to_date, then auto-applies.
            (function initSprRangePicker(tries) {
                if (typeof flatpickr === 'undefined') {
                    if ((tries || 0) < 20) { setTimeout(function () { initSprRangePicker((tries || 0) + 1); }, 100); }
                    return;
                }
                var rangeEl = document.getElementById('spr_date_range');
                var fromEl = document.getElementById('from_date');
                var toEl = document.getElementById('to_date');
                if (!rangeEl) return;
                var defaults = (fromEl && fromEl.value && toEl && toEl.value) ? [fromEl.value, toEl.value] : null;
                flatpickr(rangeEl, {
                    mode: 'range',
                    showMonths: 2,
                    dateFormat: 'Y-m-d',
                    allowInput: false,
                    defaultDate: defaults,
                    locale: { rangeSeparator: ' – ' },
                    onChange: function (selectedDates, dateStr, instance) {
                        if (selectedDates.length === 2) {
                            if (fromEl) fromEl.value = instance.formatDate(selectedDates[0], 'Y-m-d');
                            if (toEl) toEl.value = instance.formatDate(selectedDates[1], 'Y-m-d');
                            sprApplyFilters();
                        }
                    }
                });
            })(0);

            // New-design filter toolbar: debounced auto-apply on any filter change.
            var sprForm = document.getElementById('sprFilterForm');
            if (sprForm) {
                var sprTimer = null;
                sprForm.addEventListener('change', function (e) {
                    var t = e.target;
                    if (!t || !t.classList || !t.classList.contains('spr-auto-filter')) return;
                    if (sprTimer) clearTimeout(sprTimer);
                    sprTimer = setTimeout(sprApplyFilters, 500);
                });
            }

            // Reset / Remove filters via AJAX — clears controls, no page reload.
            var sprRemove = document.getElementById('sprRemoveFilter');
            if (sprRemove) {
                sprRemove.addEventListener('click', function (e) {
                    e.preventDefault();
                    var baseUrl = this.getAttribute('href');
                    // Clear vendor / store tom-selects
                    document.querySelectorAll('.stock-purchase-report select.choices-select').forEach(function (el) {
                        if (el.tomselect) { el.tomselect.clear(true); }
                        else { Array.prototype.forEach.call(el.options, function (o) { o.selected = false; }); }
                    });
                    // Reset date range to the server default (today–today)
                    var td = new Date();
                    var todayStr = td.getFullYear() + '-' + String(td.getMonth() + 1).padStart(2, '0') + '-' + String(td.getDate()).padStart(2, '0');
                    var fromEl = document.getElementById('from_date');
                    var toEl = document.getElementById('to_date');
                    if (fromEl) fromEl.value = todayStr;
                    if (toEl) toEl.value = todayStr;
                    var rangeEl = document.getElementById('spr_date_range');
                    if (rangeEl && rangeEl._flatpickr) rangeEl._flatpickr.setDate([todayStr, todayStr], false);
                    // Base URL carries no per_page → the server falls back to 50; keep the hidden field in step.
                    var perPageHidden = document.getElementById('sprPerPageHidden');
                    if (perPageHidden) perPageHidden.value = '50';
                    if (window.history && window.history.pushState) { try { window.history.pushState({ sprFilter: true }, '', baseUrl); } catch (e2) {} }
                    ajaxLoad(baseUrl);
                });
            }
        });
    </script>
<script>
// Print prints the WHOLE filtered report, not just the page on screen: it re-requests
// the table partial with print_all=1 (server returns every line for the active filters)
// and prints that. Falls back to the on-screen page if the fetch fails.
function printStockPurchaseTable() {
    var onScreenTable = document.querySelector('.stock-purchase-report .stock-purchase-table-wrapper table.stock-purchase-table')
        || document.querySelector('.stock-purchase-report .report-content table');
    if (!onScreenTable) {
        alert('No table data found to print.');
        return;
    }

    // Open the window on the click itself — opening it later, inside the fetch
    // callback, trips pop-up blockers.
    var printWindow = window.open('', '_blank');
    if (printWindow) {
        printWindow.document.open();
        printWindow.document.write('<!doctype html><html><head><meta charset="utf-8"><title>Preparing print…</title></head>'
            + '<body style="font-family:system-ui,sans-serif;padding:24px;color:#334155;">'
            + 'Preparing the full report for printing…</body></html>');
        printWindow.document.close();
    }

    var url = new URL(window.location.href);
    url.searchParams.set('ajax', '1');
    url.searchParams.set('print_all', '1');
    url.searchParams.delete('page');

    fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } })
        .then(function (r) { return r.text(); })
        .then(function (html) {
            var doc = new DOMParser().parseFromString(html, 'text/html');
            var fullTable = doc.querySelector('table.stock-purchase-table');
            renderStockPurchasePrint(printWindow, fullTable || onScreenTable, !fullTable);
        })
        .catch(function (e) {
            console.error('Stock purchase print (full data) failed; printing current page', e);
            renderStockPurchasePrint(printWindow, onScreenTable, true);
        });
}

// Drop the columns the user unchecked in the Columns modal, then repair the
// colspan on the vendor / bill / grand-total rows so they still span the table.
function sprStripHiddenColumnsForPrint(table) {
    var hidden = [];
    document.querySelectorAll('.spr-col-toggle').forEach(function (cb) {
        if (!cb.checked) hidden.push(cb.getAttribute('data-col'));
    });
    if (!hidden.length) return;

    var headRow = table.querySelector('thead tr');
    if (!headRow) return;
    var removedIdx = [];
    Array.prototype.slice.call(headRow.children).forEach(function (th, i) {
        if (hidden.indexOf(th.getAttribute('data-col')) !== -1) removedIdx.push(i);
    });

    // Recompute spans before removing cells — every spanning cell starts at column 0.
    table.querySelectorAll('tbody td[colspan]').forEach(function (td) {
        var base = parseInt(td.getAttribute('data-colspan-base') || td.getAttribute('colspan'), 10) || 1;
        var gone = removedIdx.filter(function (i) { return i < base; }).length;
        td.removeAttribute('data-colspan-base');
        td.setAttribute('colspan', Math.max(1, base - gone));
    });

    hidden.forEach(function (col) {
        table.querySelectorAll('[data-col="' + col + '"]').forEach(function (el) { el.remove(); });
    });
}

function renderStockPurchasePrint(printWindow, tableEl, isCurrentPageOnly) {
    var table = tableEl.cloneNode(true);
    sprStripHiddenColumnsForPrint(table);

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

    if (!printWindow) {
        // Pop-up blocked — fall back to printing the page itself.
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
'        .header-text .line1 { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #004a93; font-weight: 600; margin: 0; }\n' +
'        .header-text .line2 { font-size: 16px; font-weight: 700; text-transform: uppercase; color: #1a1a1a; margin: 2px 0 0; }\n' +
'        .header-text .line3 { font-size: 11px; color: #555; margin: 1px 0 0; }\n' +
'\n' +
'        /* ── Report Title & Meta ── */\n' +
'        .report-title-block { text-align: center; margin-bottom: 10px; }\n' +
'        .report-title-block h2 { font-size: 15px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; margin: 0 0 4px; color: #1a1a1a; }\n' +
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
'        @page { size: A4 portrait; margin: 8mm; }\n' +
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
'    <strong>Printed:</strong> ' + new Date().toLocaleDateString('en-IN') + ' ' + new Date().toLocaleTimeString('en-IN', {hour:'2-digit',minute:'2-digit'}) +
    (isCurrentPageOnly ? ' &nbsp;&nbsp;|&nbsp;&nbsp; <strong>Note:</strong> current page only' : '') + '\n' +
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
    overflow-y: visible;
}
@media screen {
    .stock-purchase-report .stock-purchase-table-wrapper {
        overflow-x: auto !important;
        overflow-y: auto !important;
        max-height: min(72vh, calc(100dvh - 12rem));
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
        background: #E8E8E8 !important;
        color: #323232 !important;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        border-bottom: 2px solid #E8E8E8;
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
    background: #E4EBFF !important;
    color: #004D9D !important;
    font-weight: 700;
    padding: 0.6rem 0.75rem;
    border-top: 2px solid #E4EBFF;
    border-bottom: 1px solid #E4EBFF;
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
    background: #E4EBFF !important;
    color: #323232 !important;
    font-weight: 600;
    padding: 0.5rem 0.75rem;
    border-color: #E4EBFF !important;
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

/* Empty state */
.stock-purchase-report .spr-empty-state {
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 0.5rem;
    border: 1px dashed #cbd5e1;
    margin: 0.5rem;
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

/* ── New-design chrome: Download/Print bar + single-row filter toolbar ── */
.stock-purchase-report .spr-export-btn {
    background: var(--ds-surface, #fff);
    border: 1px solid var(--ds-line, #e5e7eb);
    color: var(--ds-primary, #004a93);
    border-radius: var(--ds-radius, 4px);
    min-height: var(--ds-control-h, 40px);
    padding: 0 1rem;
    font-weight: 500;
    font-size: .875rem;
    display: inline-flex;
    align-items: center;
    gap: .4rem;
}
.stock-purchase-report .spr-export-btn:hover { background: var(--ds-surface-2, #f8fafc); border-color: var(--ds-primary, #004a93); }
.stock-purchase-report .spr-export-btn .material-symbols-rounded { font-size: 1.15rem; }
.stock-purchase-report .spr-toolbar,
.stock-purchase-report .spr-filter-form { flex-wrap: wrap; gap: .5rem; }
.stock-purchase-report .spr-filter-item { flex-shrink: 0; }
.stock-purchase-report .spr-filter-date {
    min-height: var(--ds-control-h, 40px); height: var(--ds-control-h, 40px);
    width: 9.5rem; border-radius: var(--ds-radius, 4px); border: 1px solid var(--ds-line, #e5e7eb); font-size: .85rem;
}
.stock-purchase-report .spr-filter-dash { color: var(--ds-ink-muted, #667085); }
.stock-purchase-report .spr-filter-range {
    min-width: 15rem;
    min-height: var(--ds-control-h, 40px); height: var(--ds-control-h, 40px);
    border-radius: var(--ds-radius, 4px); border: 1px solid var(--ds-line, #e5e7eb);
    font-size: .85rem; background: var(--ds-surface, #fff); cursor: pointer;
}
.stock-purchase-report .spr-toolbar .ts-wrapper,
.stock-purchase-report .spr-toolbar .form-select { min-width: 11rem; }
.stock-purchase-report .spr-search-input {
    min-height: var(--ds-control-h, 40px); height: var(--ds-control-h, 40px); width: 14rem;
    border-radius: var(--ds-radius, 4px); border: 1px solid var(--ds-line, #e5e7eb); font-size: .85rem;
}
/* Footer bar: pager left, "Showing [n] of N items" right — same chrome as the other report pages. */
.stock-purchase-report .ssr-pagination-bar {
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    font-size: 0.8125rem;
}
.stock-purchase-report .ssr-pagination-bar .pagination {
    margin-bottom: 0;
    --bs-pagination-font-size: 0.8125rem;
}
.stock-purchase-report .ssr-pagination-bar .page-link { transition: all 0.15s ease; }
.stock-purchase-report .ssr-pagination-bar .page-link:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}
.stock-purchase-report .ssr-perpage-select { width: auto; min-width: 4.25rem; }
/* Keep only the page-number links from Laravel's paginator (its own "Showing X to Y of Z results"
   text is replaced by the count we render on the right). */
.stock-purchase-report .ssr-pagination-links p { display: none !important; }
.stock-purchase-report .ssr-pagination-links nav > div { justify-content: flex-start !important; }
.stock-purchase-report .spr-colvis-item { cursor: pointer; transition: border-color .15s ease, background-color .15s ease; }
.stock-purchase-report .spr-colvis-item:hover { border-color: var(--ds-primary, #004384); background-color: rgba(0, 67, 132, .04); }
/* Report context strip — clean, token-based (print/PDF export keeps its own template) */
.stock-purchase-report .spr-report-header {
    background: var(--ds-surface-2, #f8fafc);
    border: 1px solid var(--ds-line, #e5e7eb);
    border-radius: var(--ds-radius-card, 8px);
    padding: var(--ds-space-3, 1rem);
    text-align: left;
}
.stock-purchase-report .spr-context-chip {
    display: inline-flex;
    align-items: center;
    gap: var(--ds-space-1, 0.25rem);
    background: var(--ds-surface, #fff);
    border: 1px solid var(--ds-line, #e5e7eb);
    border-radius: var(--ds-radius, 4px);
    padding: 0.35rem 0.75rem;
    font-size: 0.82rem;
    color: var(--ds-ink, #1f2937);
}
.stock-purchase-report .spr-context-chip--wrap { max-width: min(100%, 42rem); }
.stock-purchase-report .spr-context-chip .material-symbols-rounded {
    font-size: 1rem;
    color: var(--ds-primary, #004a93);
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