@extends('admin.layouts.master')

@section('title', ($student->display_name ?? 'Officer Trainee') . '’s Medical Exemption Report')

@section('setup_content')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}?v={{ filemtime(public_path('css/select2-theme.css')) }}">
<style>
/* Medical Exemption Report — OT detail. Tokens from sargam-app.css (--ds-*). */

/* --- Stat cards -------------------------------------------------- */
.mer-stats { display: grid; grid-template-columns: repeat(5, 1fr); gap: var(--ds-space-3); }
.mer-stat-card {
    background: #fff; border: 1px solid var(--ds-line); border-radius: var(--ds-radius-2);
    padding: 0.85rem 1.1rem; box-shadow: var(--ds-shadow-sm);
}
.mer-stat-label { font-size: 0.8125rem; font-weight: 500; color: var(--ds-ink-muted); margin-bottom: 0.35rem; }
.mer-stat-value { font-size: 1.5rem; font-weight: 700; color: var(--ds-ink); line-height: 1; }
@media (max-width: 991.98px) { .mer-stats { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 575.98px) { .mer-stats { grid-template-columns: repeat(2, 1fr); } }

/* --- Toolbar buttons --------------------------------------------- */
.mer-util-btn {
    height: 42px; display: inline-flex; align-items: center; gap: var(--ds-space-2);
    padding: 0 1rem; font-weight: 600; font-size: 0.9rem; color: #004a93;
    background: #fff; border: 0; border-radius: var(--ds-radius-1);
    transition: border-color .15s ease, box-shadow .15s ease, color .15s ease;
}
.mer-util-btn:hover { color: var(--bs-primary); box-shadow: var(--ds-shadow-sm); }
.mer-util-btn.dropdown-toggle::after { margin-left: 0.35rem; }
.mer-download-menu { min-width: 11rem; border-radius: var(--ds-radius-1); }

/* --- Filters ----------------------------------------------------- */
.mer-filterbar { display: flex; flex-wrap: wrap; align-items: center; gap: var(--ds-space-2); }
.mer-filters-label { font-weight: 600; font-size: 0.9rem; color: var(--ds-ink); margin-right: var(--ds-space-1); }
.mer-filter-control {
    height: 42px; display: inline-flex; align-items: center; gap: var(--ds-space-1);
    padding: 0 0.85rem; font-size: 0.875rem; font-weight: 500; color: var(--ds-ink);
    background: #fff; border: 1px solid var(--ds-line); border-radius: var(--ds-radius-1); line-height: 1;
}
select.mer-filter-control { display: inline-block; min-width: 170px; max-width: 230px; min-height: 42px; padding-right: 2.25rem; text-overflow: ellipsis; }
.mer-filter-control:hover { border-color: #c4ccd6; }
.mer-filter-control.dropdown-toggle::after { margin-left: auto; }
#merResetFilters.mer-filter-control { color: var(--bs-danger); border-color: var(--bs-danger); font-weight: 600; }
#merResetFilters.mer-filter-control:hover { background: var(--bs-danger); color: #fff; }
.mer-period-menu { min-width: auto; }

/* --- Calendar ---------------------------------------------------- */
.mer-cal { padding: var(--ds-space-3); }
.mer-cal-months { display: flex; gap: var(--ds-space-4); }
@media (max-width: 575.98px) { .mer-cal-months { flex-direction: column; gap: var(--ds-space-3); } }
.mer-cal-month { width: 232px; }
.mer-cal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--ds-space-2); }
.mer-cal-title { font-weight: 600; font-size: 0.875rem; color: var(--ds-ink); }
.mer-cal-nav { border: 0; background: transparent; width: 28px; height: 28px; border-radius: var(--ds-radius-1); color: var(--ds-ink-muted); display: inline-flex; align-items: center; justify-content: center; }
.mer-cal-nav:hover { background: var(--ds-surface-2); color: var(--ds-ink); }
.mer-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
.mer-cal-dow { text-align: center; font-size: 0.7rem; font-weight: 600; color: var(--ds-ink-muted); padding: 4px 0; }
.mer-cal-day { aspect-ratio: 1 / 1; border: 0; background: transparent; border-radius: var(--ds-radius-1); font-size: 0.8125rem; color: var(--ds-ink); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }
.mer-cal-day:hover { background: rgba(var(--bs-primary-rgb, 0 74 147), 0.1); }
.mer-cal-day.in-range { background: rgba(var(--bs-primary-rgb, 0 74 147), 0.12); border-radius: 0; }
.mer-cal-day.is-start, .mer-cal-day.is-end { background: var(--bs-primary); color: #fff; }
.mer-cal-day.is-start { border-radius: var(--ds-radius-1) 0 0 var(--ds-radius-1); }
.mer-cal-day.is-end { border-radius: 0 var(--ds-radius-1) var(--ds-radius-1) 0; }
.mer-cal-day.is-start.is-end { border-radius: var(--ds-radius-1); }
.mer-cal-footer { display: flex; align-items: center; justify-content: space-between; gap: var(--ds-space-2); margin-top: var(--ds-space-3); padding-top: var(--ds-space-3); border-top: 1px solid var(--ds-line); }
.mer-cal-range { font-size: 0.8125rem; color: var(--ds-ink-muted); }

/* --- Column modal ------------------------------------------------ */
.mer-col-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--ds-space-3); }
.mer-col-chip { display: flex; align-items: center; gap: var(--ds-space-2); margin: 0; padding: 0.65rem 0.85rem; border: 1px solid var(--ds-line); border-radius: var(--ds-radius-1); background: #fff; cursor: pointer; font-size: 0.9rem; font-weight: 500; color: var(--ds-ink); user-select: none; transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease; }
.mer-col-chip:hover { border-color: #c4ccd6; background: var(--ds-surface-2); }
.mer-col-chip.is-checked { border-color: var(--bs-primary); box-shadow: inset 0 0 0 1px var(--bs-primary); }
.mer-col-chip .form-check-input { margin: 0; flex-shrink: 0; cursor: pointer; }
@media (max-width: 479.98px) { .mer-col-grid { grid-template-columns: 1fr; } }

/* --- Search ------------------------------------------------------ */
.mer-search-box { position: relative; display: inline-flex; align-items: center; }
.mer-search-ico { position: absolute; left: 12px; font-size: 18px; color: var(--ds-ink-muted); pointer-events: none; }
.mer-search-field { height: 42px; width: 220px; padding-left: 38px; border: 1px solid var(--ds-line); border-radius: var(--ds-radius-1); font-size: 0.875rem; }
.mer-search-field:focus { border-color: #86b7fe; box-shadow: var(--ds-focus-ring); }
@media (max-width: 575.98px) { .mer-search-field { width: 150px; } }

/* --- Table ------------------------------------------------------- */
.datatables .mer-scroll { max-height: 65vh; overflow: auto; -webkit-overflow-scrolling: touch; }
.datatables .table-responsive { overflow: visible; }
.datatables #merDetailTable { min-width: 100%; width: 100%; margin-bottom: 0; }
.datatables #merDetailTable thead th { position: sticky; top: 0; z-index: 10; background: var(--ds-surface-2); border-bottom: 1px solid var(--ds-line); font-size: 0.8125rem; text-transform: uppercase; letter-spacing: 0.02em; white-space: nowrap; padding: 12px 14px; vertical-align: middle; }
.datatables #merDetailTable td { padding: 12px 14px; vertical-align: middle; font-size: 0.9rem; color: var(--ds-ink); }
.datatables #merDetailTable td.mer-remarks { white-space: normal; min-width: 200px; max-width: 360px; word-break: break-word; }

/* Bottom bar */
.datatables .mer-table-footer { margin-top: var(--ds-space-3); }
.datatables .mer-count { gap: var(--ds-space-2); color: var(--ds-ink-muted); font-size: 0.875rem; }
.datatables .dataTables_length, .datatables .dataTables_info { margin: 0; padding: 0; color: var(--ds-ink-muted); font-size: 0.875rem; white-space: nowrap; }
.datatables .dataTables_length label { margin: 0; display: inline-flex; align-items: center; gap: var(--ds-space-2); }
.datatables .dataTables_length select.form-select { width: auto; min-width: 76px; display: inline-block; border-radius: var(--ds-radius-1); }
.datatables .dataTables_paginate { margin: 0; }
.datatables .pagination { margin: 0; gap: var(--ds-space-1); flex-wrap: wrap; }
.datatables .pagination .page-item .page-link { margin-left: 0; min-width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; padding: 0 0.5rem; border: 1px solid var(--ds-line); border-radius: var(--ds-radius-1); color: var(--ds-ink); font-size: 0.875rem; background: #fff; }
.datatables .pagination .page-item .page-link:hover { background: var(--ds-surface-2); border-color: #c4ccd6; }
.datatables .pagination .page-item.active .page-link { background: var(--bs-primary); border-color: var(--bs-primary); color: #fff; }
.datatables .pagination .page-item.disabled .page-link { color: var(--ds-ink-muted); background: var(--ds-surface-2); opacity: 0.6; }
</style>

@php
    $otName = trim((string) ($student->display_name ?? '')) ?: 'Officer Trainee';
    $detailUrl = route('medical.exemption.report.detail', ['student' => $studentToken, 'course' => $courseToken]);
    $detailExportUrl = route('medical.exemption.report.detail.export', ['student' => $studentToken, 'course' => $courseToken]);
@endphp

<div class="container-fluid">

    <x-breadcrum :title="$otName . '’s Medical Exemption Report'" :showBack="true" :items="[
        ['label' => 'Home', 'url' => route('admin.dashboard')],
        ['label' => 'Academic'],
        ['label' => 'Medical Exemption Report', 'url' => route('medical.exemption.report.index')],
        ['label' => $otName . '’s Medical Exemption Report'],
    ]" />

    {{-- Stat cards --}}
    <div class="mer-stats mb-3">
        @foreach($stats as $stat)
        <div class="mer-stat-card">
            <div class="mer-stat-label">{{ $stat['label'] }}</div>
            <div class="mer-stat-value">{{ str_pad((string) $stat['count'], 2, '0', STR_PAD_LEFT) }}</div>
        </div>
        @endforeach
    </div>

    {{-- Toolbar --}}
    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 mb-3">
        <button type="button" class="mer-util-btn" onclick="merPrintTable()">
            <i class="material-icons material-symbols-rounded" style="font-size:20px;" aria-hidden="true">print</i>
            <span class="d-none d-sm-inline">Print</span>
        </button>
        <div class="dropdown">
            <button type="button" class="mer-util-btn dropdown-toggle" id="merDownloadBtn" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="material-icons material-symbols-rounded" style="font-size:20px;" aria-hidden="true">download</i>
                <span class="d-none d-sm-inline">Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm mer-download-menu py-2" aria-labelledby="merDownloadBtn">
                <li><button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" id="merExportPdf">
                    <i class="material-icons material-symbols-rounded text-danger" style="font-size:18px;" aria-hidden="true">picture_as_pdf</i><span>Download PDF</span>
                </button></li>
                <li><button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" id="merExportCsv">
                    <i class="material-icons material-symbols-rounded text-success" style="font-size:18px;" aria-hidden="true">table_chart</i><span>Download Excel</span>
                </button></li>
            </ul>
        </div>
    </div>

    <div class="datatables">
        <div class="ds-card">
            <div class="ds-card-body">

                {{-- Filters --}}
                <div class="mer-filterbar mb-3">
                    <span class="mer-filters-label">Filters</span>

                    {{-- Time Period --}}
                    <div class="dropdown">
                        <button type="button" class="mer-filter-control dropdown-toggle" id="merTimePeriodToggle"
                                data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">calendar_month</i>
                            <span id="merTimePeriodLabel">Time Period</span>
                        </button>
                        <div class="dropdown-menu p-0 mer-period-menu">
                            <div class="mer-cal" id="merCalendar">
                                <div class="mer-cal-months">
                                    <div class="mer-cal-month" data-month="0"></div>
                                    <div class="mer-cal-month" data-month="1"></div>
                                </div>
                                <div class="mer-cal-footer">
                                    <span class="mer-cal-range" id="merCalRange">Select a date range</span>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="merClearPeriod">Clear</button>
                                        <button type="button" class="btn btn-sm btn-primary" id="merApplyPeriod">Apply</button>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="mer_from_date_filter" value="">
                            <input type="hidden" id="mer_to_date_filter" value="">
                        </div>
                    </div>

                    {{-- Exemption Category --}}
                    <select id="mer_category_filter" class="form-select mer-filter-control" aria-label="Exemption Category">
                        <option value="">Exemption Category</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->pk }}">{{ $cat->exemp_category_name }}</option>
                        @endforeach
                    </select>

                    {{-- Medical Case --}}
                    <select id="mer_case_filter" class="form-select mer-filter-control" aria-label="Medical Case">
                        <option value="">Medical Case</option>
                        @foreach($medicalCases as $case)
                        <option value="{{ $case }}">{{ $case }}</option>
                        @endforeach
                    </select>

                    <a href="javascript:void(0)" id="merResetFilters" class="mer-filter-control">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">restart_alt</i>
                        Reset Filters
                    </a>

                    <div class="ms-auto d-flex align-items-center gap-2">
                        <button type="button" class="mer-filter-control" id="merColumnsToggle" data-bs-toggle="modal" data-bs-target="#merColumnsModal">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">view_column</i>
                            <span class="d-none d-md-inline">Columns</span>
                        </button>
                        <div class="mer-search-box">
                            <i class="material-icons material-symbols-rounded mer-search-ico" aria-hidden="true">search</i>
                            <input type="text" id="mer_search" class="form-control mer-search-field" placeholder="Search case, category, remarks..." aria-label="Search">
                        </div>
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table class="table align-middle" id="merDetailTable">
                        <thead>
                            <tr>
                                <th class="col">Date</th>
                                <th class="col">Medical Case</th>
                                <th class="col">Exemption Category</th>
                                <th class="col">Remarks</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Column Visibility modal --}}
    <div class="modal fade" id="merColumnsModal" tabindex="-1" aria-labelledby="merColumnsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="merColumnsModalLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body"><div class="mer-col-grid" id="merColumnsGrid"></div></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {

    if ($.fn.select2) {
        $('#mer_category_filter, #mer_case_filter').select2({ width: '190px', minimumResultsForSearch: 6, allowClear: false });
    }

    var table = $('#merDetailTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        scrollX: false,
        autoWidth: false,
        dom: "<'mer-scroll't>" +
             "<'mer-table-footer row align-items-center g-2 mt-3'" +
                 "<'col-12 col-md-auto me-md-auto order-2 order-md-1'p>" +
                 "<'col-12 col-md-auto order-1 order-md-2 d-flex justify-content-md-end align-items-center mer-count'li>" +
             ">" +
             "<'mer-processing'r>",
        lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
        pageLength: 10,
        order: [[0, 'desc']],
        language: {
            lengthMenu: "Showing _MENU_",
            info: "of _TOTAL_ items",
            infoEmpty: "of 0 items",
            infoFiltered: "",
            zeroRecords: "No matching records found",
            emptyTable: "No records available",
            paginate: { previous: "<span aria-hidden='true'>&lsaquo;</span>", next: "<span aria-hidden='true'>&rsaquo;</span>" },
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading…</span></div>'
        },
        ajax: {
            url: @json($detailUrl),
            data: function (d) {
                d.custom_search = $('#mer_search').val();
                d.category_id = $('#mer_category_filter').val();
                d.medical_case = $('#mer_case_filter').val();
                d.from_date = $('#mer_from_date_filter').val();
                d.to_date = $('#mer_to_date_filter').val();
            }
        },
        columnDefs: [{ defaultContent: '—', targets: '_all' }],
        columns: [
            { data: 'date', name: 'from_date' },
            { data: 'medical_case', name: 'opd_category' },
            { data: 'category', name: 'category', orderable: false },
            { data: 'remarks', name: 'Description', orderable: false, className: 'mer-remarks' }
        ]
    });

    $('#mer_category_filter, #mer_case_filter, #mer_from_date_filter, #mer_to_date_filter').on('change', function () { table.ajax.reload(null, false); });

    var delayTimer;
    $('#mer_search').on('keyup', function () {
        clearTimeout(delayTimer);
        delayTimer = setTimeout(function () { table.ajax.reload(null, false); }, 400);
    });

    $('#merResetFilters').on('click', function () {
        $('#mer_search').val('');
        $('#mer_category_filter').val('').trigger('change.select2');
        $('#mer_case_filter').val('').trigger('change.select2');
        $('#mer_from_date_filter').val('');
        $('#mer_to_date_filter').val('');
        updateTimePeriodLabel();
        table.ajax.reload(null, false);
    });

    function updateTimePeriodLabel() {
        var from = $('#mer_from_date_filter').val();
        var to = $('#mer_to_date_filter').val();
        $('#merTimePeriodLabel').text((from || to) ? ((from || '…') + ' → ' + (to || '…')) : 'Time Period');
    }

    // ===== Dual-month range calendar =====
    (function initRangeCalendar() {
        var MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        var DOW = ['Mo','Tu','We','Th','Fr','Sa','Su'];
        var view = new Date(); view.setDate(1);
        var startD = null, endD = null;
        function pad(n){ return (n < 10 ? '0' : '') + n; }
        function ymd(d){ return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
        function sameDay(a, b){ return a && b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate(); }
        function buildMonth(base){
            var year = base.getFullYear(), month = base.getMonth();
            var startWeekday = (new Date(year, month, 1).getDay() + 6) % 7;
            var daysInMonth = new Date(year, month + 1, 0).getDate();
            var html = '<div class="mer-cal-head">' +
                '<button type="button" class="mer-cal-nav" data-nav="prev" aria-label="Previous month">&lsaquo;</button>' +
                '<span class="mer-cal-title">' + MONTHS[month] + ' ' + year + '</span>' +
                '<button type="button" class="mer-cal-nav" data-nav="next" aria-label="Next month">&rsaquo;</button>' +
                '</div><div class="mer-cal-grid">';
            DOW.forEach(function(d){ html += '<span class="mer-cal-dow">' + d + '</span>'; });
            for (var i = 0; i < startWeekday; i++) html += '<span></span>';
            for (var day = 1; day <= daysInMonth; day++){
                var d = new Date(year, month, day);
                var cls = 'mer-cal-day';
                if (startD && endD && d > startD && d < endD) cls += ' in-range';
                if (sameDay(d, startD)) cls += ' is-start';
                if (sameDay(d, endD)) cls += ' is-end';
                html += '<button type="button" class="' + cls + '" data-date="' + ymd(d) + '">' + day + '</button>';
            }
            return html + '</div>';
        }
        function render(){
            var left = new Date(view.getFullYear(), view.getMonth(), 1);
            var right = new Date(view.getFullYear(), view.getMonth() + 1, 1);
            $('#merCalendar .mer-cal-month[data-month="0"]').html(buildMonth(left));
            $('#merCalendar .mer-cal-month[data-month="1"]').html(buildMonth(right));
            $('#merCalendar .mer-cal-month[data-month="0"] [data-nav="next"]').css('visibility', 'hidden');
            $('#merCalendar .mer-cal-month[data-month="1"] [data-nav="prev"]').css('visibility', 'hidden');
            var label = 'Select a date range';
            if (startD && endD) label = ymd(startD) + '  ->  ' + ymd(endD);
            else if (startD) label = ymd(startD) + '  -> ...';
            $('#merCalRange').text(label);
        }
        $('#merCalendar').on('click', '.mer-cal-nav', function(){
            var dir = $(this).data('nav') === 'prev' ? -1 : 1;
            view = new Date(view.getFullYear(), view.getMonth() + dir, 1); render();
        });
        $('#merCalendar').on('click', '.mer-cal-day', function(){
            var p = String($(this).data('date')).split('-');
            var d = new Date(+p[0], +p[1] - 1, +p[2]);
            if (!startD || (startD && endD)) { startD = d; endD = null; }
            else if (d < startD) { startD = d; }
            else { endD = d; }
            render();
        });
        $('#merApplyPeriod').on('click', function(){
            $('#mer_from_date_filter').val(startD ? ymd(startD) : '');
            $('#mer_to_date_filter').val(endD ? ymd(endD) : (startD ? ymd(startD) : ''));
            updateTimePeriodLabel();
            table.ajax.reload(null, false);
            if (window.bootstrap) { bootstrap.Dropdown.getOrCreateInstance(document.getElementById('merTimePeriodToggle')).hide(); }
        });
        $('#merClearPeriod').on('click', function(){
            startD = null; endD = null; render();
            $('#mer_from_date_filter').val(''); $('#mer_to_date_filter').val('');
            updateTimePeriodLabel(); table.ajax.reload(null, false);
        });
        render();
    })();

    // ===== Column Visibility =====
    var $columnsGrid = $('#merColumnsGrid');
    table.columns().every(function (idx) {
        var title = $.trim($(this.header()).text()) || ('Column ' + (idx + 1));
        var visible = this.visible();
        $columnsGrid.append(
            '<label class="mer-col-chip' + (visible ? ' is-checked' : '') + '" for="merColToggle' + idx + '">' +
                '<input class="form-check-input mer-col-toggle" type="checkbox" ' + (visible ? 'checked ' : '') +
                       'id="merColToggle' + idx + '" data-column="' + idx + '">' +
                '<span>' + title + '</span>' +
            '</label>'
        );
    });
    $columnsGrid.on('change', '.mer-col-toggle', function () {
        table.column($(this).data('column')).visible(this.checked);
        $(this).closest('.mer-col-chip').toggleClass('is-checked', this.checked);
    });

    // ===== Export =====
    var merExportBase = @json($detailExportUrl);
    function merExportUrl(format) {
        var params = new URLSearchParams();
        params.set('format', format);
        var search = $('#mer_search').val();
        var category = $('#mer_category_filter').val();
        var medicalCase = $('#mer_case_filter').val();
        var from = $('#mer_from_date_filter').val();
        var to = $('#mer_to_date_filter').val();
        if (search) params.set('custom_search', search);
        if (category) params.set('category_id', category);
        if (medicalCase) params.set('medical_case', medicalCase);
        if (from) params.set('from_date', from);
        if (to) params.set('to_date', to);
        var visibleCols = [];
        table.columns().every(function (idx) { if (this.visible()) visibleCols.push(idx); });
        params.set('columns', visibleCols.join(','));
        return merExportBase + '?' + params.toString();
    }
    $('#merExportPdf').on('click', function (e) { e.preventDefault(); window.location.href = merExportUrl('pdf'); });
    $('#merExportCsv').on('click', function (e) { e.preventDefault(); window.location.href = merExportUrl('excel'); });
});

// ===== Print =====
function merPrintTable() {
    var table = document.getElementById('merDetailTable');
    if (!table) { alert('Table not found!'); return; }
    var printWindow = window.open('', '_blank');
    if (!printWindow) { alert('Please allow pop-ups for this site to print the report.'); return; }

    var tableHTML = table.cloneNode(true).outerHTML;
    var dateStr = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
    var logoLeft  = @json(asset('admin_assets/images/logos/logo_new.png'));
    var logoRight = @json(file_exists(public_path('admin_assets/images/logos/constitution-75.png'))
        ? asset('admin_assets/images/logos/constitution-75.png')
        : asset('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png'));
    var titleHindi = @json(asset('admin_assets/images/logos/lbsnaa-title-hi.png'));
    var reportTitle = @json($otName . '’s Medical Exemption Report');
    var courseName = @json(optional($course)->course_name ?? '');

    var printContent =
        '<!DOCTYPE html><html><head><title>' + reportTitle + ' - Print</title><style>' +
        'body{font-family:Arial,sans-serif;margin:16px;color:#1f2937;}' +
        '.pdf-hdr{width:100%;border-collapse:collapse;margin-bottom:4px;}' +
        '.pdf-hdr td{vertical-align:middle;} .pdf-hdr .logo{width:90px;text-align:center;}' +
        '.pdf-hdr .logo img{max-height:64px;max-width:84px;} .pdf-hdr .center{text-align:center;padding:0 8px;}' +
        '.pdf-hdr .inst-hi-img{height:18px;width:auto;margin-bottom:2px;}' +
        '.pdf-hdr .inst-en{font-size:16px;font-weight:bold;color:#102a43;line-height:1.25;}' +
        '.pdf-hdr .course-line{font-size:12px;font-weight:bold;color:#243b53;margin-top:4px;}' +
        '.report-title{text-align:center;font-size:20px;font-weight:bold;color:#004a93;margin:8px 0 6px;padding-bottom:8px;border-bottom:2px solid #004a93;}' +
        '.print-info{margin-bottom:12px;font-size:11px;color:#666;text-align:center;}' +
        'table{width:100%;border-collapse:collapse;margin-top:10px;}' +
        'table th,table td{border:1px solid #8fa3bd;padding:6px 8px;text-align:left;font-size:12px;}' +
        'table thead th{font-weight:bold;background-color:#004a93 !important;color:#fff !important;text-align:center;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
        'table tbody tr:nth-child(even){background-color:#eef2f8;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
        '.print-footer{margin-top:18px;text-align:center;font-size:10px;color:#666;border-top:1px solid #ccc;padding-top:10px;}' +
        '@media print{@page{size:A4 landscape;margin:10mm;} body{margin:0;}}' +
        '</style></head><body onload="window.focus();window.print();">' +
        '<table class="pdf-hdr"><tr>' +
            '<td class="logo"><img src="' + logoLeft + '" alt=""></td>' +
            '<td class="center"><img class="inst-hi-img" src="' + titleHindi + '" alt="">' +
                '<div class="inst-en">Lal Bahadur Shastri National Academy of Administration, Mussoorie</div>' +
                (courseName ? '<div class="course-line">' + courseName + '</div>' : '') +
            '</td>' +
            '<td class="logo"><img src="' + logoRight + '" alt=""></td>' +
        '</tr></table>' +
        '<div class="report-title">' + reportTitle + '</div>' +
        '<div class="print-info"><div>Print Date: ' + dateStr + '</div></div>' +
        tableHTML +
        '<div class="print-footer"><p>Generated on ' + new Date().toLocaleString() + '</p></div>' +
        '</body></html>';

    printWindow.document.open();
    printWindow.document.write(printContent);
    printWindow.document.close();
}
</script>
@endpush
