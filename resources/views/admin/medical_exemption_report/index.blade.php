@extends('admin.layouts.master')

@section('title', 'Medical Exemption Report')

@push('styles')
{{-- Module stylesheet (docs/new-design-index-page.md §7) — this page used to
     carry ~130 lines of the same rules in an inline <style> block. --}}
<link rel="stylesheet"
    href="{{ asset('css/medical-exemption-report-admin.css') }}?v={{ @filemtime(public_path('css/medical-exemption-report-admin.css')) }}">
@endpush

@section('setup_content')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}?v={{ filemtime(public_path('css/select2-theme.css')) }}">

<div class="container-fluid mer-page">

    <x-breadcrum title="Medical Exemption Report" :items="[
        ['label' => 'Home', 'url' => route('admin.dashboard')],
        ['label' => 'Academic'],
        ['label' => 'Medical Exemption Report'],
    ]" />

    {{-- Toolbar: status segment (left) + Print / Download (right) --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">

        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0" role="group"
            aria-label="Course Status Filter">
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill active"
                    id="merFilterActive" aria-pressed="true">Active</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                    id="merFilterArchive" aria-pressed="false">Archived</button>
            </li>
        </ul>

        <div class="d-flex flex-wrap align-items-center gap-2">
        <button type="button" class="btn mer-export-btn" onclick="merPrintTable()">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </button>
        <div class="dropdown">
            <button type="button" class="btn mer-export-btn dropdown-toggle" id="merDownloadBtn" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download" aria-hidden="true"></i>
                <span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm mer-download-menu py-2" aria-labelledby="merDownloadBtn">
                <li>
                    <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" id="merExportPdf">
                        <i class="bi bi-filetype-pdf text-danger" aria-hidden="true"></i>
                        <span>Download PDF</span>
                    </button>
                </li>
                <li>
                    <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" id="merExportCsv">
                        <i class="bi bi-file-earmark-excel text-success" aria-hidden="true"></i>
                        <span>Download Excel</span>
                    </button>
                </li>
            </ul>
        </div>
        </div>
    </div>

    <div class="datatables">
        <div class="ds-card">
            <div class="ds-card-body">

                {{-- Filters --}}
                <div class="mer-filterbar programme-dt-toolbar d-flex flex-wrap align-items-center gap-3 mb-4">
                    <span class="programme-dt-filters-label">Filters</span>

                    <div class="programme-dt-filter-select">
                    <select name="course_filter" id="mer_course_filter" class="form-select form-select-sm" aria-label="Course Name">
                        <option value="">Course Name</option>
                        @foreach($courses as $course)
                        <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                        @endforeach
                    </select>
                    </div>

                    {{-- Time Period --}}
                    <div class="dropdown">
                        <button type="button" class="btn mer-period-toggle dropdown-toggle" id="merTimePeriodToggle"
                                data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            <i class="bi bi-calendar3" aria-hidden="true"></i>
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

                    {{-- Reset is a <button>, not a link (§2) --}}
                    <button type="button" id="merResetFilters" class="btn programme-dt-btn-reset">Reset Filters</button>

                    {{-- The search is the shared .programme-dt-search markup — the
                         structure datatable-global-ui.js would relocate into that
                         slot — so the skin matches every other index page even
                         though this table drives its own server-side search. --}}
                    <div class="ms-auto d-flex flex-wrap align-items-center gap-2">
                        <button type="button" class="btn programme-dt-btn-columns" id="merColumnsToggle"
                            data-bs-toggle="modal" data-bs-target="#merColumnsModal" title="Show / hide columns">
                            <span>Columns</span>
                            <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                        </button>
                        <div class="programme-dt-search">
                            <div class="dataTables_filter">
                                <label>
                                    <input type="search" id="mer_search" class="form-control form-control-sm"
                                        placeholder="Search OT, code, course..." autocomplete="off" aria-label="Search">
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Table (§3) --}}
                <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="medicalExemptionReportTable">
                        <thead>
                            <tr>
                                <th class="col">S. No.</th>
                                <th class="col">OT Name</th>
                                <th class="col">Course Name</th>
                                <th class="col">Medical Exemptions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Column Visibility modal --}}
    <div class="modal fade mer-modal" id="merColumnsModal" tabindex="-1" aria-labelledby="merColumnsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="mer-modal-header">
                    <h5 class="mer-modal-title" id="merColumnsModalLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="mer-modal-body">
                    <div class="mer-col-grid" id="merColumnsGrid"></div>
                </div>
                <div class="mer-modal-footer">
                    <button type="button" class="btn mer-btn-cancel" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {

    // Active = running courses, Archived = ended courses (mirrors Course Master).
    let courseStatus = 'active';
    const merCourseLists = {
        active: @json($courses->map(fn ($c) => ['pk' => $c->pk, 'name' => $c->course_name])->values()),
        archive: @json(($archivedCourses ?? collect())->map(fn ($c) => ['pk' => $c->pk, 'name' => $c->course_name])->values()),
    };

    let courseFilterSelect2 = false;
    if ($.fn.select2 && $('#mer_course_filter').length) {
        $('#mer_course_filter').select2({ width: '210px', placeholder: 'Course Name', allowClear: false });
        courseFilterSelect2 = true;
    }

    function populateCourseFilter(status) {
        const list = merCourseLists[status] || [];
        const $sel = $('#mer_course_filter');
        if (!$sel.length) { return; }
        $sel.empty().append($('<option>').val('').text('Course Name'));
        list.forEach(function (c) { $sel.append($('<option>').val(String(c.pk)).text(c.name)); });
        $sel.val('');
        if (courseFilterSelect2) { $sel.trigger('change.select2'); }
    }

    var table = $('#medicalExemptionReportTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        scrollX: false,
        autoWidth: false,
        // ‹ 1 2 3 › — First/Last are not part of the shared footer (§4).
        pagingType: 'simple_numbers',
        // ⚠️ No Bootstrap row/col in the footer: datatable-global-ui.js rewrites
        // the className of both slots when it fills them, which strips any col-*
        // and leaves the two halves stacked. A plain flex container survives.
        dom: "<'mer-scroll't>" +
             "<'programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3'" +
                 "<'programme-dt-pagination'p>" +
                 "<'programme-dt-count d-flex align-items-center gap-2'li>" +
             ">" +
             "<'mer-processing'r>",
        lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
        pageLength: 10,
        order: [[1, 'asc']],
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
            url: "{{ route('medical.exemption.report.index') }}",
            data: function (d) {
                d.course_id = $('#mer_course_filter').val();
                d.custom_search = $('#mer_search').val();
                d.from_date = $('#mer_from_date_filter').val();
                d.to_date = $('#mer_to_date_filter').val();
                d.status = courseStatus;
            }
        },
        columnDefs: [{ defaultContent: '—', targets: '_all' }],
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { data: 'ot_name', name: 'display_name' },
            { data: 'course_name', name: 'course_name' },
            { data: 'exemptions', name: 'exemption_count', className: 'text-center', orderable: true }
        ]
    });

    $('#mer_course_filter').on('change', function () { table.ajax.reload(null, false); });
    $('#mer_from_date_filter, #mer_to_date_filter').on('change', function () { table.ajax.reload(null, false); });

    var delayTimer;
    $('#mer_search').on('keyup', function () {
        clearTimeout(delayTimer);
        delayTimer = setTimeout(function () { table.ajax.reload(null, false); }, 400);
    });

    $('#merResetFilters').on('click', function () {
        $('#mer_search').val('');
        $('#mer_course_filter').val('');
        if (courseFilterSelect2) { $('#mer_course_filter').trigger('change.select2'); }
        $('#mer_from_date_filter').val('');
        $('#mer_to_date_filter').val('');
        updateTimePeriodLabel();
        table.ajax.reload(null, false);
    });

    // Active / Archived tabs
    $('#merFilterActive').on('click', function () {
        courseStatus = 'active';
        populateCourseFilter('active');
        $(this).addClass('active').attr('aria-pressed', 'true');
        $('#merFilterArchive').removeClass('active').attr('aria-pressed', 'false');
        table.ajax.reload(null, false);
    });
    $('#merFilterArchive').on('click', function () {
        courseStatus = 'archive';
        populateCourseFilter('archive');
        $(this).addClass('active').attr('aria-pressed', 'true');
        $('#merFilterActive').removeClass('active').attr('aria-pressed', 'false');
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
            view = new Date(view.getFullYear(), view.getMonth() + dir, 1);
            render();
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

    // ===== Export (PDF / Excel) =====
    var merExportBase = @json(route('medical.exemption.report.export'));
    function merExportUrl(format) {
        var params = new URLSearchParams();
        params.set('format', format);
        params.set('status', courseStatus);
        var course = $('#mer_course_filter').val();
        var search = $('#mer_search').val();
        var from = $('#mer_from_date_filter').val();
        var to = $('#mer_to_date_filter').val();
        if (course) params.set('course_id', course);
        if (search) params.set('custom_search', search);
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

// ===== Print (client-side, honours column visibility via the live DOM) =====
function merPrintTable() {
    var table = document.getElementById('medicalExemptionReportTable');
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

    var courseName = '';
    var selCourse = $('#mer_course_filter option:selected');
    if (selCourse.val()) { courseName = (selCourse.text() || '').trim(); }

    var printContent =
        '<!DOCTYPE html><html><head><title>Medical Exemption Report - Print</title><style>' +
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
        '@media print{@page{size:A4 portrait;margin:10mm;} body{margin:0;}}' +
        '</style></head><body onload="window.focus();window.print();">' +
        '<table class="pdf-hdr"><tr>' +
            '<td class="logo"><img src="' + logoLeft + '" alt=""></td>' +
            '<td class="center"><img class="inst-hi-img" src="' + titleHindi + '" alt="">' +
                '<div class="inst-en">Lal Bahadur Shastri National Academy of Administration, Mussoorie</div>' +
                (courseName ? '<div class="course-line">' + courseName + '</div>' : '') +
            '</td>' +
            '<td class="logo"><img src="' + logoRight + '" alt=""></td>' +
        '</tr></table>' +
        '<div class="report-title">Medical Exemption Report</div>' +
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
