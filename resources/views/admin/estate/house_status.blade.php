@extends('admin.layouts.master')

@section('title', 'House Status - Sargam')

@section('setup_content')
<div class="container-fluid er-report hs-page">
    <x-breadcrum title="House Status" :showBack="false" />

    {{-- KPI tiles — counted from the rows the report returns. --}}
    <div class="er-stats mb-4" aria-live="polite">
        <div class="ds-stat-card">
            <div>
                <p class="ds-stat-label">Total houses</p>
                <div class="ds-stat-value" id="hsStatTotal">—</div>
            </div>
            <span class="ds-stat-icon"><i class="bi bi-houses" aria-hidden="true"></i></span>
        </div>
        <div class="ds-stat-card er-stat--occupied">
            <div>
                <p class="ds-stat-label">Occupied</p>
                <div class="ds-stat-value" id="hsStatOccupied">—</div>
            </div>
            <span class="ds-stat-icon"><i class="bi bi-person-check" aria-hidden="true"></i></span>
        </div>
        <div class="ds-stat-card er-stat--vacant">
            <div>
                <p class="ds-stat-label">Vacant</p>
                <div class="ds-stat-value" id="hsStatVacant">—</div>
            </div>
            <span class="ds-stat-icon"><i class="bi bi-door-open" aria-hidden="true"></i></span>
        </div>
        <div class="ds-stat-card er-stat--renovation">
            <div>
                <p class="ds-stat-label">Under renovation</p>
                <div class="ds-stat-value" id="hsStatRenovation">—</div>
            </div>
            <span class="ds-stat-icon"><i class="bi bi-tools" aria-hidden="true"></i></span>
        </div>
    </div>

    {{-- Status pills + Print, above the card (new-design-index-page.md §1). --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs er-status-tabs bg-white mb-0"
            role="group" aria-label="Filter houses by status">
            @foreach(['' => 'All', 'Occupied' => 'Occupied', 'Vacant' => 'Vacant', 'Under Renovation' => 'Under Renovation'] as $value => $label)
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-3 py-2 fw-semibold programme-status-pill {{ $value === '' ? 'active' : '' }}"
                    data-hs-status="{{ $value }}" aria-pressed="{{ $value === '' ? 'true' : 'false' }}">
                    {{ $label }}<span class="er-tab-count" data-hs-count="{{ $value === '' ? 'all' : $value }}"></span>
                </button>
            </li>
            @endforeach
        </ul>

        <div class="d-flex flex-wrap gap-2 er-export">
            <button type="button" class="btn programme-dt-btn-columns border-0 text-primary" id="btnPrintHouseStatus" title="Print the filtered list" disabled>
                <i class="bi bi-printer" aria-hidden="true"></i>
                <span>Print</span>
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-1">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3 programme-dt-toolbar">
                <p class="er-summary" id="hsSummary">House-wise status: Occupied, Vacant, or Under Renovation.</p>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="hsColumnsBtn"
                        data-bs-toggle="modal" data-bs-target="#hsColumnModal" title="Show / hide columns" disabled>
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="hsDtSearch" class="programme-dt-search" data-dt-search-for="houseStatusTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive er-scroll">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table ds-table-sticky" id="houseStatusTable"
                        aria-describedby="hsSummary">
                        <thead>
                            <tr>
                                <th scope="col" class="no-sort">S. No.</th>
                                <th scope="col">Qtr No.</th>
                                <th scope="col">Building Name</th>
                                <th scope="col">Type</th>
                                <th scope="col">Allottee Name (Ms/Mr/Mrs.)</th>
                                <th scope="col">Section/Designation</th>
                                <th scope="col">Mobile Number</th>
                                <th scope="col">Alloted Date</th>
                                <th scope="col">Occupied Date</th>
                                <th scope="col">Vacated Date</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                data-dt-footer-for="houseStatusTable"></div>
        </div>
    </div>

    <div class="modal fade" id="hsColumnModal" tabindex="-1" aria-labelledby="hsColumnModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-1 border-0 shadow">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="hsColumnModalLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <hr class="mt-0">
                    <div class="row g-3" id="hsColumnToggleGrid"></div>
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
<link rel="stylesheet" href="{{ asset('css/estate-request-admin.css') }}?v={{ @filemtime(public_path('css/estate-request-admin.css')) ?: time() }}">
@endpush

@push('scripts')
@include('admin.estate.partials.report_print_assets')
<script>
$(function () {
    var G = window.EstateReportGrid;
    var esc = G.esc;
    var $table = $('#houseStatusTable');
    var STATUS_COL = 10;
    var dt = null;
    var statusFilter = '';

    // The report's three states (EstateController::getHouseStatusData) → badge tone.
    var TONE = { 'Occupied': 'occupied', 'Vacant': 'vacant', 'Under Renovation': 'renovation' };

    function badge(status) {
        var s = String(status || '').trim();
        return '<span class="er-badge er-badge--' + (TONE[s] || 'neutral') + '">' + esc(s || '—') + '</span>';
    }

    function blank(v) {
        return (v === null || v === undefined || v === '') ? '<span class="text-muted">—</span>' : esc(v);
    }

    // d/m/Y → sortable key, so date columns sort by date rather than as text.
    function dateKey(v) {
        var m = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(String(v || ''));
        return m ? m[3] + m[2] + m[1] : '';
    }

    function setCounts(rows) {
        var c = { all: rows.length, 'Occupied': 0, 'Vacant': 0, 'Under Renovation': 0 };
        rows.forEach(function (r) { if (c[r.status] !== undefined) c[r.status]++; });
        $('#hsStatTotal').text(c.all);
        $('#hsStatOccupied').text(c['Occupied']);
        $('#hsStatVacant').text(c['Vacant']);
        $('#hsStatRenovation').text(c['Under Renovation']);
        $('[data-hs-count]').each(function () {
            $(this).text('(' + (c[$(this).data('hs-count')] || 0) + ')');
        });
    }

    function applyStatusFilter() {
        if (!dt) return;
        // Exact match on the Status column (search value is the plain label via data-search).
        dt.column(STATUS_COL).search(statusFilter ? '^' + $.fn.dataTable.util.escapeRegex(statusFilter) + '$' : '', true, false).draw();
    }

    function load() {
        G.stateRow($table, 'loading', 'Loading house status…');
        $.ajax({
            url: @json(route('admin.estate.reports.house-status.data')),
            type: 'GET',
            dataType: 'json'
        }).done(function (res) {
            var rows = (res && res.status && Array.isArray(res.data)) ? res.data : [];
            setCounts(rows);
            if (!rows.length) {
                G.stateRow($table, 'empty', 'No houses found.');
                return;
            }
            $table.find('tbody').html(rows.map(function (row, i) {
                var vacant = !row.allottee_name || row.allottee_name === 'VACANT';
                return '<tr>' +
                    '<td>' + esc(row.sno != null ? row.sno : i + 1) + '</td>' +
                    '<td class="fw-medium">' + blank(row.qtr_no) + '</td>' +
                    '<td>' + blank(row.building_name) + '</td>' +
                    '<td>' + blank(row.type) + '</td>' +
                    '<td class="er-col-wrap">' + (vacant ? '<span class="text-muted">VACANT</span>' : esc(row.allottee_name)) + '</td>' +
                    '<td class="er-col-wrap">' + blank(row.section_designation) + '</td>' +
                    '<td>' + blank(row.mobile_number) + '</td>' +
                    '<td data-order="' + dateKey(row.alloted_date) + '">' + blank(row.alloted_date) + '</td>' +
                    '<td data-order="' + dateKey(row.occupied_date) + '">' + blank(row.occupied_date) + '</td>' +
                    '<td data-order="' + dateKey(row.vacated_date) + '">' + blank(row.vacated_date) + '</td>' +
                    '<td data-search="' + esc(row.status || '') + '">' + badge(row.status) + '</td>' +
                    '</tr>';
            }).join(''));

            dt = $table.DataTable({
                order: [],
                responsive: false, // the panel scrolls; never fold columns into child rows
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                columnDefs: [{ targets: 0, orderable: false, searchable: false }]
            });
            dt.on('draw.dt', function () {
                var start = dt.page.info().start;
                dt.column(0, { page: 'current' }).nodes().each(function (cell, i) { cell.textContent = start + i + 1; });
            });
            G.columnVisibility(dt, {
                grid: '#hsColumnToggleGrid',
                storageKey: 'sargam.houseStatus.hiddenCols.' + @json(auth()->id() ?? 'guest')
            });
            applyStatusFilter();
            $('#btnPrintHouseStatus, #hsColumnsBtn').prop('disabled', false);
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to load data. Please try again.';
            G.stateRow($table, 'error', msg);
        });
    }

    $('[data-hs-status]').on('click', function () {
        statusFilter = String($(this).data('hs-status') || '');
        $('[data-hs-status]').removeClass('active').attr('aria-pressed', 'false');
        $(this).addClass('active').attr('aria-pressed', 'true');
        applyStatusFilter();
    });

    $('#btnPrintHouseStatus').on('click', function () {
        if (!dt) return;
        var data = G.collectRows(dt);
        G.printGrid({
            title: 'House Status',
            meta: ['Status: ' + (statusFilter || 'All') + (dt.search() ? '  |  Search: "' + dt.search() + '"' : '')],
            headers: data.headers,
            rows: data.rows
        });
    });

    load();
});
</script>
@endpush
