@extends('admin.layouts.master')

@section('title', 'Estate Bill Grid - Sargam')

@section('setup_content')
<div class="container-fluid er-report ebr-page">
    <x-breadcrum title="Estate Bill Grid" :showBack="false"></x-breadcrum>

    {{-- Download + Print sit above the card (Figma 1:43933); both take every row for the month. --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 er-export">
        <button type="button" class="btn estate-export-btn" id="btnBillReportDownload" title="Download every row for the selected month (CSV)" disabled>
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </button>
        <button type="button" class="btn estate-export-btn" id="btnBillReportPrint" title="Print every row for the selected month" disabled>
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-1">
        <div class="card-body p-3 p-md-4">
            {{-- Toolbar: "Filter" · pills (placeholder option = label) · Remove Filter ·
                 Columns + search on the right (Figma 1:43933). --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3 programme-dt-toolbar">
                <form id="billReportGridFilterForm" class="er-filters align-items-center" novalidate>
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <input type="month" class="form-control" id="bill_month" name="bill_month" aria-label="Bill Month"
                            value="{{ request('bill_month', date('Y-m')) }}" max="{{ date('Y-m') }}" required aria-required="true"
                            title="Bill Month (current month or earlier)">
                    </div>
                    <div class="programme-dt-filter-select">
                        <select class="form-select" id="employee_type_filter" name="employee_type" data-searchable="true" aria-label="Employee Type">
                            <option value="all" selected>Employee Type</option>
                            <option value="lbsnaa">LBSNAA</option>
                            <option value="other">Other Employee</option>
                        </select>
                    </div>
                    <button type="button" class="btn programme-dt-btn-reset" id="btnBillReportReset">Remove Filter</button>
                </form>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnBillReportColumns"
                        data-bs-toggle="modal" data-bs-target="#estateBillReportColumnModal"
                        title="Show / hide columns" disabled>
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="billReportDtSearch" class="programme-dt-search" data-dt-search-for="estateBillReportTable"></div>
                </div>
            </div>

            {{-- Business caveat kept from the old screen (not in Figma). --}}
            <p class="er-summary mb-3" id="billReportNote">Only notified bills are listed for the selected month, except for other employees.</p>

            <div class="programme-dt-panel">
                <div class="table-responsive er-scroll">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table ds-table-sticky" id="estateBillReportTable" aria-describedby="billReportNote">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Employee Type</th>
                                <th scope="col">Name</th>
                                <th scope="col">Section</th>
                                <th scope="col">Building Name</th>
                                <th scope="col">House Number</th>
                                <th scope="col">From</th>
                                <th scope="col">To</th>
                                <th scope="col">Meter No.</th>
                                <th scope="col" class="er-num">Previous Reading</th>
                                <th scope="col" class="er-num">Current Reading</th>
                                <th scope="col" class="er-num">Units</th>
                                <th scope="col" class="er-num">Total Charge</th>
                                <th scope="col" class="er-num">Licence</th>
                                <th scope="col" class="er-num">Water</th>
                                <th scope="col" class="er-num">Grand Total</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            {{-- DataTables paginates (server-side), so the global UI fills this slot (§4A). --}}
            <div id="billReportDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                data-dt-footer-for="estateBillReportTable"></div>
        </div>
    </div>

    <!-- Column Visibility Modal -->
    <div class="modal fade" id="estateBillReportColumnModal" tabindex="-1" aria-labelledby="estateBillReportColumnLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-1 border-0 shadow">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="estateBillReportColumnLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <hr class="mt-0">
                    <div class="row g-3" id="estateBillReportColumnGrid"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn ds-btn-cancel ds-btn-cancel--primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/estate-request-admin.css') }}?v={{ @filemtime(public_path('css/estate-request-admin.css')) ?: time() }}">
<style>
    /* Keeps Filter · pills · Columns · search on one row at 1440 (Figma uses a 40px search icon). */
    .ebr-page .programme-dt-search { width: 200px; }
</style>
@endpush

@push('scripts')
@include('admin.estate.partials.report_print_assets')
<script>
$(function () {
    var G = window.EstateReportGrid;
    var esc = G.esc;
    var billReportDt = null;
    var dataUrl = @json(route('admin.estate.reports.bill-report-grid.data'));

    // Display only — the amounts arrive already calculated from the server.
    function formatMoney(n) {
        if (n == null || n === '' || isNaN(n)) return '—';
        return '₹ ' + parseFloat(n).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    function text(v) {
        return (v == null || v === '') ? '—' : esc(v);
    }

    // Dual-meter rows carry "\n"-separated values — one line per meter.
    function lines(v) {
        return (v == null || v === '' ? '—' : String(v)).split(/\n/).map(function (s) { return esc(s.trim() || '—'); }).join('<br>');
    }

    function initOrReloadBillReportGrid() {
        if (!$('#bill_month').val()) {
            $('#bill_month').trigger('focus');
            return;
        }

        if (billReportDt) {
            billReportDt.ajax.reload(null, true);
            return;
        }

        billReportDt = $('#estateBillReportTable').DataTable({
            processing: true,
            serverSide: true,
            // Keep DataTables' native server ordering so a header click sorts the WHOLE
            // month, not just the loaded page (datatable-global-ui.js opt-in).
            sargamServerOrder: true,
            searching: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            ajax: {
                url: dataUrl,
                data: function (d) {
                    d.bill_month = $('#bill_month').val();
                    d.employee_type = $('#employee_type_filter').val();
                }
            },
            columns: [
                { data: 'sno', orderable: false, searchable: false },
                { data: 'employee_type', render: function (v, t) { return t === 'display' ? text(v) : v; } }, // plain text, as in Figma
                { data: 'name', className: 'er-col-wrap fw-medium', render: function (v, t) { return t === 'display' ? text(v) : v; } },
                { data: 'section', className: 'er-col-wrap', render: function (v, t) { return t === 'display' ? text(v) : v; } },
                { data: 'building_name', render: function (v, t) { return t === 'display' ? text(v) : v; } },
                { data: 'house_no', render: function (v, t) { return t === 'display' ? text(v) : v; } },
                { data: 'from_date', searchable: false, render: function (v, t) { return t === 'display' ? text(v) : v; } },
                { data: 'to_date', searchable: false, render: function (v, t) { return t === 'display' ? text(v) : v; } },
                { data: 'meter_no', searchable: false, render: function (v) {
                    return (v == null || v === '') ? '—' : esc(v.toString().split(/\n+/).map(function (s) { return s.trim(); }).filter(Boolean).join(', '));
                } },
                { data: 'prev_reading', searchable: false, className: 'er-num', render: lines },
                { data: 'curr_reading', searchable: false, className: 'er-num', render: lines },
                { data: 'unit_consumed', searchable: false, className: 'er-num', render: function (v) { return text(v); } },
                { data: 'total_charge', searchable: false, className: 'er-num', render: formatMoney },
                { data: 'licence_fee', searchable: false, className: 'er-num', render: formatMoney },
                { data: 'water_charges', searchable: false, className: 'er-num', render: formatMoney },
                { data: 'grand_total', searchable: false, className: 'er-num er-total', render: formatMoney }
            ],
            order: [[2, 'asc']], // Name A→Z across the whole month (server-side)
            responsive: false,
            autoWidth: false
            // No `language` and no custom `dom`: datatable-global-ui.js owns both
            // (a page override breaks the "Showing N of M items" footer), and it moves
            // search / length / pager into the .programme-dt-search / .programme-dt-footer slots.
        });

        G.columnVisibility(billReportDt, {
            grid: '#estateBillReportColumnGrid',
            // v2: remembered by column label (v1 stored indexes).
            storageKey: 'estateBillReportGrid:columns:v2'
        });

        $('#btnBillReportColumns, #btnBillReportPrint, #btnBillReportDownload').prop('disabled', false);
    }

    $('#billReportGridFilterForm').on('submit', function (e) {
        e.preventDefault();
        initOrReloadBillReportGrid();
    });

    // Filters apply on change — no Show button needed.
    $('#bill_month').on('change', initOrReloadBillReportGrid);
    $('#employee_type_filter').on('change', initOrReloadBillReportGrid); // jQuery: Select2 fires a jQuery change

    $('#btnBillReportReset').on('click', function () {
        $('#bill_month').val(@json(date('Y-m')));
        $('#employee_type_filter').val('all').trigger('change.select2');
        if (billReportDt) billReportDt.search('');
        initOrReloadBillReportGrid();
    });

    // Both fetch every row (server honours length = -1) and then restore the pager.
    $('#btnBillReportPrint').on('click', function () {
        if (!billReportDt) return;
        G.withAllRows(billReportDt, function () {
            var data = G.collectRows(billReportDt);
            var type = { all: 'All', lbsnaa: 'LBSNAA', other: 'Other Employee' }[$('#employee_type_filter').val()] || 'All';
            G.printGrid({
                title: 'Estate Bill Grid',
                meta: ['Bill Month: ' + ($('#bill_month').val() || '') + '  |  Employee Type: ' + type],
                headers: data.headers,
                rows: data.rows,
                numericCols: data.headers.map(function (h, i) {
                    return ['Previous Reading', 'Current Reading', 'Units', 'Total Charge', 'Licence', 'Water', 'Grand Total'].indexOf(h) !== -1 ? i : -1;
                }).filter(function (i) { return i !== -1; })
            });
        });
    });

    $('#btnBillReportDownload').on('click', function () {
        if (!billReportDt) return;
        G.withAllRows(billReportDt, function () {
            var data = G.collectRows(billReportDt);
            G.downloadCsv('Estate Bill Grid ' + ($('#bill_month').val() || ''), data.headers, data.rows);
        });
    });

    // Load the default / requested month on open.
    initOrReloadBillReportGrid();
});
</script>
@endpush
