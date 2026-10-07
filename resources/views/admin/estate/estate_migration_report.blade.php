@extends('admin.layouts.master')

@section('title', 'Estate Migration Report (1998–2026) - Sargam')

{{--
    Filters keep their original ids, option sources and cascade endpoint
    (migration-report/filter-options). Nine filters do not fit one toolbar row,
    so the four primary ones stay in the toolbar and the rest sit in a
    "More Filters" panel (new-design-index-page.md §2 "Filter overflow").

    "1998–2026" is the source dataset's own name (table
    estate_migration_report_1998_2026); the Year filter itself is built from the
    data, not from this label.
--}}
@php
    $primaryFilters = [
        ['id' => 'filter_allotment_year', 'label' => 'Allotment Year', 'all' => '— All Years —', 'options' => $years ?? []],
        ['id' => 'filter_campus_name', 'label' => 'Campus Name', 'all' => '— All Campuses —', 'options' => $campuses ?? []],
        ['id' => 'filter_building_name', 'label' => 'Building Name', 'all' => '— All Buildings —', 'options' => $buildings ?? []],
        ['id' => 'filter_employee_type', 'label' => 'Employee Type', 'all' => '— All Types —', 'options' => $employeeTypes ?? []],
    ];
    $moreFilters = [
        ['id' => 'filter_type_of_building', 'label' => 'Type of Building', 'all' => '— All Types —', 'options' => $buildingTypes ?? []],
        ['id' => 'filter_house_no', 'label' => 'House No.', 'all' => '— All Houses —', 'options' => $houseNos ?? []],
        ['id' => 'filter_employee_name', 'label' => 'Employee Name', 'all' => '— All Employees —', 'options' => $employeeNames ?? []],
        ['id' => 'filter_department_name', 'label' => 'Department', 'all' => '— All Departments —', 'options' => $departments ?? []],
        ['id' => 'filter_stay_period_text', 'label' => 'Stay Period', 'all' => '— All Periods —', 'options' => $stayPeriods ?? []],
    ];
@endphp

@section('setup_content')
<div class="container-fluid er-report emr-page">
    <x-breadcrum title="Estate Migration Report (1998–2026)" :showBack="false"></x-breadcrum>

    <x-session_message />

    <div class="card border-0 shadow-sm rounded-1">
        <div class="card-body p-3 p-md-4">
            <p class="er-summary mb-3">Historical estate allotment records. Narrow them by year, campus, building, house, employee, department and employee type; every filter narrows the options of the ones after it.</p>

            <div class="d-flex flex-column flex-xl-row align-items-xl-end justify-content-between gap-3 mb-3 programme-dt-toolbar">
                <div class="er-filters">
                    @foreach($primaryFilters as $f)
                    <div class="programme-dt-filter-select">
                        <label for="{{ $f['id'] }}" class="er-filter-label">{{ $f['label'] }}</label>
                        <select class="form-select" id="{{ $f['id'] }}">
                            <option value="">{{ $f['all'] }}</option>
                            @foreach($f['options'] as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endforeach

                    <button type="button" class="btn programme-dt-btn-columns" id="emrMoreFiltersBtn"
                        data-bs-toggle="collapse" data-bs-target="#emrMoreFilters" aria-expanded="false" aria-controls="emrMoreFilters">
                        <i class="bi bi-sliders" aria-hidden="true"></i>
                        <span>More Filters</span>
                        <span class="badge text-bg-primary rounded-1 d-none" id="emrMoreCount" aria-label="active"></span>
                    </button>
                    <button type="button" id="btnResetFilters" class="btn programme-dt-btn-reset">Reset Filters</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-xl-auto">
                    <button type="button" class="btn programme-dt-btn-columns" data-bs-toggle="modal"
                        data-bs-target="#emrColumnModal" title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="emrDtSearch" class="programme-dt-search" data-dt-search-for="estateMigrationReportTable"></div>
                </div>
            </div>

            <div class="collapse" id="emrMoreFilters">
                <div class="er-filters p-3 mb-3 bg-light border rounded-1">
                    @foreach($moreFilters as $f)
                    <div class="programme-dt-filter-select">
                        <label for="{{ $f['id'] }}" class="er-filter-label">{{ $f['label'] }}</label>
                        <select class="form-select" id="{{ $f['id'] }}">
                            <option value="">{{ $f['all'] }}</option>
                            @foreach($f['options'] as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive er-scroll">
                    {!! $dataTable->table([
                        'class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table ds-table-sticky',
                        'aria-describedby' => 'estate-migration-report-caption'
                    ]) !!}
                </div>
            </div>
            <div id="estate-migration-report-caption" class="visually-hidden">Estate Migration Report list</div>
            <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                data-dt-footer-for="estateMigrationReportTable"></div>
        </div>
    </div>

    <div class="modal fade" id="emrColumnModal" tabindex="-1" aria-labelledby="emrColumnModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-1 border-0 shadow">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="emrColumnModalLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <hr class="mt-0">
                    <div class="row g-3" id="emrColumnToggleGrid"></div>
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
@endpush

@push('scripts')
@include('admin.estate.partials.report_print_assets')
<script>
$(function () {
    var G = window.EstateReportGrid;
    var esc = G.esc;
    var filterSelectIds = ['filter_allotment_year', 'filter_campus_name', 'filter_building_name', 'filter_type_of_building', 'filter_house_no', 'filter_employee_name', 'filter_department_name', 'filter_employee_type', 'filter_stay_period_text'];
    var moreFilterIds = ['filter_type_of_building', 'filter_house_no', 'filter_employee_name', 'filter_department_name', 'filter_stay_period_text'];

    function initFilterSelects() {
        if (typeof $.fn.select2 === 'undefined') return;
        filterSelectIds.forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            if ($(el).data('select2')) { try { $(el).select2('destroy'); } catch (e) {} }
            $(el).select2({ allowClear: false, width: '100%', dropdownParent: $(el).closest('.card-body') });
        });
    }

    function destroyFilterSelects() {
        if (typeof $.fn.select2 === 'undefined') return;
        filterSelectIds.forEach(function (id) {
            var el = document.getElementById(id);
            if (el && $(el).data('select2')) { try { $(el).select2('destroy'); } catch (e) {} }
        });
    }

    // How many "More Filters" are set — shown on the button so a hidden filter is never forgotten.
    function syncMoreCount() {
        var n = moreFilterIds.filter(function (id) { return !!$('#' + id).val(); }).length;
        $('#emrMoreCount').text(n).toggleClass('d-none', n === 0);
    }

    initFilterSelects();

    // Yajra HTML-escapes every non-raw column on the server, so the values below are
    // already safe markup — escaping them again would print "&amp;" literally.
    function occupancyBadge(v) {
        var s = String(v || '').trim();
        if (!s || s === '—') return '<span class="text-muted">—</span>';
        var tone = /exit/i.test(s) ? 'exited' : (/occup/i.test(s) ? 'occupied' : 'neutral');
        return '<span class="er-badge er-badge--' + tone + '">' + s + '</span>';
    }

    function text(v, t) {
        return t === 'display' ? ((v == null || v === '') ? '—' : v) : v;
    }

    var table = $('#estateMigrationReportTable').DataTable({
        processing: true,
        serverSide: true,
        // Server-side ordering across the whole dataset, not just the loaded page.
        sargamServerOrder: true,
        searching: true,
        responsive: false,
        autoWidth: false,
        order: [[1, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        ajax: {
            url: @json(route('admin.estate.reports.migration-report')),
            type: 'GET',
            data: function (d) {
                filterSelectIds.forEach(function (id) { d[id] = $('#' + id).val(); });
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'no-sort' },
            { data: 'allotment_year', name: 'allotment_year', className: 'fw-medium', render: text },
            { data: 'campus_name', name: 'campus_name', render: text },
            { data: 'building_name', name: 'building_name', render: text },
            { data: 'type_of_building', name: 'type_of_building', render: text },
            { data: 'house_no', name: 'house_no', render: text },
            { data: 'employee_name', name: 'employee_name', className: 'er-col-wrap', render: text },
            { data: 'department_name', name: 'department_name', className: 'er-col-wrap', render: text },
            { data: 'employee_type', name: 'employee_type', render: text },
            { data: 'date_of_allotment', name: 'date_of_allotment', orderable: true, searchable: false, render: text },
            { data: 'date_of_exit', name: 'date_of_exit', orderable: true, searchable: false, render: text },
            { data: 'occupancy_status', name: 'occupancy_status', render: function (v, t) { return t === 'display' ? occupancyBadge(v) : v; } },
            { data: 'stay_period_text', name: 'stay_period_text', render: text }
        ]
        // No `language` / `dom` / `scrollX`: datatable-global-ui.js owns the chrome,
        // and scrollX cloned the header into a second, duplicate row.
    });

    G.columnVisibility(table, {
        grid: '#emrColumnToggleGrid',
        storageKey: 'sargam.estateMigrationReport.hiddenCols.' + @json(auth()->id() ?? 'guest')
    });

    var filterOptionsUrl = @json(route('admin.estate.reports.migration-report.filter-options'));

    function fillSelect($el, items, defaultText) {
        var html = '<option value="">' + esc(defaultText) + '</option>';
        (items || []).forEach(function (v) {
            html += '<option value="' + esc(v) + '">' + esc(v) + '</option>';
        });
        $el.html(html);
        // Repaint Select2 without firing the app's cascade handlers (namespaced event).
        $el.trigger('change.select2');
    }

    function getFilterParams() {
        return {
            year: $('#filter_allotment_year').val(),
            campus: $('#filter_campus_name').val(),
            building: $('#filter_building_name').val(),
            type: $('#filter_type_of_building').val(),
            house_no: $('#filter_house_no').val(),
            employee_name: $('#filter_employee_name').val(),
            department: $('#filter_department_name').val(),
            employee_type: $('#filter_employee_type').val(),
            stay_period: $('#filter_stay_period_text').val()
        };
    }

    // Cascade order (unchanged): year → campus → building → type → house → employee →
    // department → employee type → stay period. Changing one resets those after it.
    var CASCADE = [
        null,
        ['filter_campus_name', 'campuses', '— All Campuses —'],
        ['filter_building_name', 'buildings', '— All Buildings —'],
        ['filter_type_of_building', 'buildingTypes', '— All Types —'],
        ['filter_house_no', 'houseNos', '— All Houses —'],
        ['filter_employee_name', 'employeeNames', '— All Employees —'],
        ['filter_department_name', 'departments', '— All Departments —'],
        ['filter_employee_type', 'employeeTypes', '— All Types —'],
        ['filter_stay_period_text', 'stayPeriods', '— All Periods —']
    ];

    function refreshCascadingFilters(resetFrom, opts) {
        for (var i = Math.max(resetFrom, 1); i < CASCADE.length; i++) {
            var c = CASCADE[i];
            $('#' + c[0]).val('');
            fillSelect($('#' + c[0]), opts[c[1]] || [], c[2]);
        }
        syncMoreCount();
        table.ajax.reload(null, false);
    }

    function fetchAndUpdateFilters(resetFrom) {
        $.get(filterOptionsUrl, getFilterParams(), function (opts) {
            refreshCascadingFilters(resetFrom, opts || {});
        });
    }

    // jQuery handlers on purpose: Select2 signals a pick with a jQuery change event.
    $('#filter_allotment_year').on('change', function () { fetchAndUpdateFilters(1); });
    $('#filter_campus_name').on('change', function () { fetchAndUpdateFilters(2); });
    $('#filter_building_name').on('change', function () { fetchAndUpdateFilters(3); });
    $('#filter_type_of_building').on('change', function () { fetchAndUpdateFilters(4); });
    $('#filter_house_no').on('change', function () { fetchAndUpdateFilters(5); });
    $('#filter_employee_name').on('change', function () { fetchAndUpdateFilters(6); });
    $('#filter_department_name').on('change', function () { fetchAndUpdateFilters(7); });
    $('#filter_employee_type').on('change', function () { fetchAndUpdateFilters(8); });
    $('#filter_stay_period_text').on('change', function () {
        syncMoreCount();
        table.ajax.reload(null, false);
    });

    $('#btnResetFilters').on('click', function () {
        destroyFilterSelects();
        filterSelectIds.forEach(function (id) { $('#' + id).val(''); });
        $.get(filterOptionsUrl, {}, function (opts) {
            opts = opts || {};
            fillSelect($('#filter_allotment_year'), opts.years || [], '— All Years —');
            for (var i = 1; i < CASCADE.length; i++) {
                fillSelect($('#' + CASCADE[i][0]), opts[CASCADE[i][1]] || [], CASCADE[i][2]);
            }
            initFilterSelects();
            syncMoreCount();
            table.search('').ajax.reload(null, false);
        });
    });
});
</script>
@endpush
