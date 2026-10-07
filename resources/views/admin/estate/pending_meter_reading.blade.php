@extends('admin.layouts.master')

@section('title', 'Pending Meter Reading - Sargam')

@section('setup_content')
<div class="container-fluid er-report pmr-page">
    <x-breadcrum title="Pending Meter Reading" :showBack="false" />
    <x-session_message />

    {{-- Print sits above the card (new-design-index-page.md §1); enabled once data loads. --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 er-export">
        <button type="button" class="btn programme-dt-btn-columns border-0 text-primary" id="btnPrintPendingMeter" title="Print the filtered list" disabled>
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </button>
    </div>

    {{-- KPI tiles — counts of the rows the selected month returned. --}}
    <div class="er-stats mb-4" aria-live="polite">
        <div class="ds-stat-card">
            <div>
                <p class="ds-stat-label">Pending readings</p>
                <div class="ds-stat-value" id="pmrStatTotal">—</div>
            </div>
            <span class="ds-stat-icon"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
        </div>
        <div class="ds-stat-card">
            <div>
                <p class="ds-stat-label">LBSNAA</p>
                <div class="ds-stat-value" id="pmrStatLbsnaa">—</div>
            </div>
            <span class="ds-stat-icon"><i class="bi bi-person-badge" aria-hidden="true"></i></span>
        </div>
        <div class="ds-stat-card er-stat--other">
            <div>
                <p class="ds-stat-label">Other employees</p>
                <div class="ds-stat-value" id="pmrStatOther">—</div>
            </div>
            <span class="ds-stat-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
        </div>
        <div class="ds-stat-card er-stat--renovation">
            <div>
                <p class="ds-stat-label">Bill month</p>
                <div class="ds-stat-value fs-5" id="pmrStatMonth">—</div>
            </div>
            <span class="ds-stat-icon"><i class="bi bi-calendar3" aria-hidden="true"></i></span>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-1">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3 mb-3 programme-dt-toolbar">
                <form id="pmrFilterForm" class="er-filters" novalidate>
                    <div class="programme-dt-filter-select">
                        <label for="bill_month" class="er-filter-label">Bill Month <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="month" class="form-control" id="bill_month" name="bill_month"
                            value="{{ date('Y-m') }}" max="{{ date('Y-m') }}" required aria-required="true">
                    </div>
                    <div class="programme-dt-filter-select">
                        <label for="employee_type_filter" class="er-filter-label">Employee Type</label>
                        <select class="form-select" id="employee_type_filter" name="employee_type_filter" data-searchable="true">
                            <option value="all" selected>All</option>
                            <option value="lbsnaa">LBSNAA</option>
                            <option value="other">OTHER</option>
                        </select>
                    </div>
                    <button type="button" class="btn programme-dt-btn-reset" id="pmrResetBtn">Reset Filters</button>
                </form>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="pmrColumnsBtn"
                        data-bs-toggle="modal" data-bs-target="#pmrColumnModal" title="Show / hide columns" disabled>
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="pmrDtSearch" class="programme-dt-search" data-dt-search-for="pendingMeterReadingTable"></div>
                </div>
            </div>

            <p class="er-summary mb-3" id="pmrSummary" aria-live="polite">Readings not yet entered for the selected bill month.</p>

            <div class="programme-dt-panel">
                <div class="table-responsive er-scroll">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table ds-table-sticky" id="pendingMeterReadingTable"
                        aria-describedby="pmrSummary">
                        <thead>
                            <tr>
                                <th scope="col" class="no-sort">S. No.</th>
                                <th scope="col">Employee Type</th>
                                <th scope="col">Name</th>
                                <th scope="col">Designation</th>
                                <th scope="col">House No.</th>
                                <th scope="col">Meter Reading Date</th>
                                <th scope="col" class="er-num">Last Meter Reading</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="no-sort">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                data-dt-footer-for="pendingMeterReadingTable"></div>
        </div>
    </div>

    <div class="modal fade" id="pmrColumnModal" tabindex="-1" aria-labelledby="pmrColumnModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-1 border-0 shadow">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="pmrColumnModalLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <hr class="mt-0">
                    <div class="row g-3" id="pmrColumnToggleGrid"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn ds-btn-cancel--primary ds-btn-cancel" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/estate-request-admin.css') }}?v={{ @filemtime(public_path('css/estate-request-admin.css')) ?: time() }}">
@endpush

@push('scripts')
@include('admin.estate.partials.report_print_assets')
<script>
$(function () {
    var G = window.EstateReportGrid;
    var esc = G.esc;
    var $table = $('#pendingMeterReadingTable');
    var dt = null;
    var request = null;
    var dataUrl = @json(route('admin.estate.reports.pending-meter-reading.data'));
    // "Required action" — where a pending reading is entered (same pages as the sidebar).
    var entryUrl = {
        LBSNAA: @json(route('admin.estate.update-meter-reading')),
        OTHER: @json(route('admin.estate.update-meter-reading-of-other'))
    };

    function monthLabel(ym) {
        var p = String(ym || '').split('-');
        if (p.length < 2) return '—';
        var d = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, 1);
        return d.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });
    }

    function setStats(rows, ym) {
        var lbsnaa = rows.filter(function (r) { return String(r.employee_type).toUpperCase() === 'LBSNAA'; }).length;
        $('#pmrStatTotal').text(rows ? rows.length : '—');
        $('#pmrStatLbsnaa').text(rows ? lbsnaa : '—');
        $('#pmrStatOther').text(rows ? rows.length - lbsnaa : '—');
        $('#pmrStatMonth').text(monthLabel(ym));
    }

    function destroyTable() {
        if ($.fn.DataTable.isDataTable($table)) $table.DataTable().destroy();
        dt = null;
        $('#btnPrintPendingMeter, #pmrColumnsBtn').prop('disabled', true);
        $('[data-dt-footer-for="pendingMeterReadingTable"], #pmrDtSearch').empty();
    }

    function rowHtml(row, i) {
        var type = String(row.employee_type || '').toUpperCase();
        var typeBadge = type === 'OTHER'
            ? '<span class="er-badge er-badge--other">OTHER</span>'
            : (type === 'LBSNAA' ? '<span class="er-badge er-badge--lbsnaa">LBSNAA</span>' : '<span class="er-badge er-badge--neutral">' + esc(type || 'N/A') + '</span>');
        var url = entryUrl[type];
        return '<tr>' +
            '<td>' + esc(row.sno || (i + 1)) + '</td>' +
            '<td data-search="' + esc(type) + '">' + typeBadge + '</td>' +
            '<td class="er-col-wrap fw-medium">' + esc(row.name || 'N/A') + '</td>' +
            '<td class="er-col-wrap">' + esc(row.designation || 'N/A') + '</td>' +
            '<td>' + esc(row.house_no || 'N/A') + '</td>' +
            '<td>' + esc(row.meter_reading_date || '—') + '</td>' +
            '<td class="er-num">' + esc(row.last_meter_reading || 'N/A') + '</td>' +
            '<td><span class="er-badge er-badge--pending">Pending</span></td>' +
            '<td>' + (url ? '<a class="er-row-action" href="' + esc(url) + '" aria-label="Enter reading for ' + esc(row.name || '') + '">' +
                '<i class="bi bi-pencil-square" aria-hidden="true"></i>Enter Reading</a>' : '') + '</td>' +
            '</tr>';
    }

    function load() {
        var billMonth = $('#bill_month').val();
        if (!billMonth) {
            destroyTable();
            setStats([], '');
            $('#pmrSummary').text('Select a bill month to see pending readings.');
            G.stateRow($table, 'idle', 'Select a bill month to see pending readings.');
            $('#bill_month').trigger('focus');
            return;
        }
        var employeeType = $('#employee_type_filter').val() || 'all';
        if (request) request.abort();
        destroyTable();
        G.stateRow($table, 'loading', 'Loading pending meter readings…');
        $('#pmrSummary').text('Loading…');

        request = $.ajax({
            url: dataUrl,
            type: 'GET',
            // Unchanged request contract: Y-m month, its year, and the employee type.
            data: { bill_month: billMonth, bill_year: billMonth.split('-')[0], employee_type: employeeType },
            dataType: 'json'
        }).done(function (res) {
            var rows = (res && res.status && Array.isArray(res.data)) ? res.data : [];
            setStats(rows, billMonth);
            if (!rows.length) {
                $('#pmrSummary').text('No pending readings for ' + monthLabel(billMonth) + '.');
                G.stateRow($table, 'empty', (res && res.message) || 'No pending meter readings for the selected month.');
                return;
            }
            $('#pmrSummary').html('<strong>' + rows.length + '</strong> reading' + (rows.length === 1 ? '' : 's') +
                ' pending for <strong>' + esc(monthLabel(billMonth)) + '</strong>.');
            $table.find('tbody').html(rows.map(rowHtml).join(''));
            dt = $table.DataTable({
                order: [],
                responsive: false, // the panel scrolls; never fold columns into child rows
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                columnDefs: [{ targets: [0, -1], orderable: false, searchable: false }]
            });
            // S. No. follows what is on screen after a sort or search.
            dt.on('draw.dt', function () {
                var start = dt.page.info().start;
                dt.column(0, { page: 'current' }).nodes().each(function (cell, i) { cell.textContent = start + i + 1; });
            });
            G.columnVisibility(dt, {
                grid: '#pmrColumnToggleGrid',
                storageKey: 'sargam.pendingMeterReading.hiddenCols.' + @json(auth()->id() ?? 'guest'),
                skip: ['Action']
            });
            $('#btnPrintPendingMeter, #pmrColumnsBtn').prop('disabled', false);
        }).fail(function (xhr, status) {
            if (status === 'abort') return;
            setStats([], billMonth);
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to load data. Please try again.';
            $('#pmrSummary').text('');
            G.stateRow($table, 'error', msg);
        }).always(function () { request = null; });
    }

    // Filters apply as soon as they change; Enter in the month box does the same.
    $('#bill_month').on('change', load);
    $('#employee_type_filter').on('change', load); // jQuery handler: Select2 fires a jQuery change
    $('#pmrFilterForm').on('submit', function (e) { e.preventDefault(); load(); });

    $('#pmrResetBtn').on('click', function () {
        $('#bill_month').val(@json(date('Y-m')));
        $('#employee_type_filter').val('all').trigger('change.select2');
        load();
    });

    $('#btnPrintPendingMeter').on('click', function () {
        if (!dt) return;
        var data = G.collectRows(dt, { skip: ['Action', 'Status'] });
        if (!data.headers.length) {
            alert('At least one column must be visible to print.');
            return;
        }
        var type = $('#employee_type_filter option:selected').text();
        G.printGrid({
            title: 'Pending Meter Reading',
            meta: ['Bill Month: ' + monthLabel($('#bill_month').val()) + '  |  Employee Type: ' + type],
            headers: data.headers,
            rows: data.rows
        });
    });

    load(); // current month on open
});
</script>
@endpush
