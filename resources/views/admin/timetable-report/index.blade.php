@extends('admin.layouts.master')

@section('title', 'Timetable Session Report - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <!-- Breadcrumb -->
    <x-breadcrum title="Timetable Session Report"></x-breadcrum>

    <!-- Active / Archive Toggle Tabs -->
    <div class="timetable-report-tabs mb-4">
        <ul class="nav nav-tabs border-0" id="timetableReportTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="active-tab-btn" type="button" role="tab"
                        aria-selected="true" data-mode="active">
                    <i class="material-icons me-2" aria-hidden="true" style="font-size:18px;vertical-align:middle">check_circle</i>
                    Active Courses
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="archive-tab-btn" type="button" role="tab"
                        aria-selected="false" data-mode="archive">
                    <i class="material-icons me-2" aria-hidden="true" style="font-size:18px;vertical-align:middle">archive</i>
                    Archived Courses
                </button>
            </li>
        </ul>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 rounded-3 mb-4 no-print">
        <div class="card-body p-4">
            <h1 class="h4 fw-bold text-dark mb-1">Timetable Session Report</h1>
            <p class="text-muted small mb-4">Select any filter to load and refine timetable data.</p>
            <div id="timetableReportFilterForm">
                <div class="row g-3">
                    <!-- Course -->
                    <div class="col-12 col-md-4 col-lg-3">
                        <label for="filter_course" class="form-label">Course</label>
                        <select class="form-select select2-filter" id="filter_course" name="course_pk">
                            <option value="">-- All Courses --</option>
                            @foreach($activeCourses as $course)
                                <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Faculty -->
                    <div class="col-12 col-md-4 col-lg-3">
                        <label for="filter_faculty" class="form-label">Faculty</label>
                        <select class="form-select select2-filter" id="filter_faculty" name="faculty_pk">
                            <option value="">-- All Faculty --</option>
                            @foreach($faculties as $faculty)
                                <option value="{{ $faculty->pk }}">{{ $faculty->full_name }} ({{ $faculty->faculty_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Faculty Type -->
                    <div class="col-12 col-md-4 col-lg-3">
                        <label for="filter_faculty_type" class="form-label">Faculty Type</label>
                        <select class="form-select select2-filter" id="filter_faculty_type" name="faculty_type">
                            <option value="">-- All Types --</option>
                            <option value="1">Internal</option>
                            <option value="2">Guest</option>
                            <option value="3">Research</option>
                        </select>
                    </div>

                    <!-- Venue -->
                    <div class="col-12 col-md-4 col-lg-3">
                        <label for="filter_venue" class="form-label">Venue</label>
                        <select class="form-select select2-filter" id="filter_venue" name="venue_id">
                            <option value="">-- All Venues --</option>
                            @foreach($venues as $venue)
                                <option value="{{ $venue->venue_id }}">{{ $venue->venue_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Subject Topic (input) -->
                    <div class="col-12 col-md-4 col-lg-3">
                        <label for="filter_subject_topic" class="form-label">Subject Topic</label>
                        <input type="text" class="form-control" id="filter_subject_topic" name="subject_topic" placeholder="Search topic...">
                    </div>

                    <!-- Subject Module (input) -->
                    <div class="col-12 col-md-4 col-lg-3">
                        <label for="filter_module_name" class="form-label">Subject Module</label>
                        <input type="text" class="form-control" id="filter_module_name" name="module_name" placeholder="Search module...">
                    </div>

                    <!-- Date From -->
                    <div class="col-12 col-md-4 col-lg-3">
                        <label for="filter_date_from" class="form-label">Date From</label>
                        <input type="date" class="form-control" id="filter_date_from" name="date_from">
                    </div>

                    <!-- Date To -->
                    <div class="col-12 col-md-4 col-lg-3">
                        <label for="filter_date_to" class="form-label">Date To</label>
                        <input type="date" class="form-control" id="filter_date_to" name="date_to">
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-secondary rounded-1 px-4" id="btnReset">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </button>
                    </div>
                </div>
            </div>

            {{-- Toolbar placeholder (Columns + Print/PDF/Excel) --}}
            <div class="d-none" id="timetableReportToolbar">
                <div class="dropdown d-inline-block ms-2" data-bs-auto-close="outside">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-1 dropdown-toggle d-inline-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false" id="btnColumns" title="Show / hide columns" disabled>
                        <i class="material-icons material-symbols-rounded" style="font-size:18px">view_column</i>
                        <span class="ms-1">Columns</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end py-2" id="columnToggleMenu"></ul>
                </div>
                <div class="btn-group shadow-sm rounded-2 ms-2" role="group" aria-label="Print or download PDF">
                    <button type="button" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center justify-content-center gap-1 px-3 rounded-0 rounded-start-2" id="btnPrint" title="Print report" disabled>
                        <i class="material-icons material-symbols-rounded" style="font-size:18px">print</i>
                        <span>Print</span>
                    </button>
                    <a href="#" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center justify-content-center gap-1 px-3 rounded-0 rounded-end-2" id="btnPdf" title="Download PDF">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px">picture_as_pdf</i>
                        <span>PDF</span>
                    </a>
                </div>
                <a href="#" class="btn btn-success btn-sm rounded-2 d-inline-flex align-items-center gap-1 px-3 ms-2" id="btnExcel" title="Export to Excel">
                    <i class="material-icons material-symbols-rounded" style="font-size:18px">table_view</i>
                    <span>Excel</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table text-nowrap mb-0" id="timetableReportTable">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Course</th>
                            <th>Course Group Type</th>
                            <th>Group</th>
                            <th>Subject</th>
                            <th>Module</th>
                            <th>Topic</th>
                            <th>Faculty</th>
                            <th>Faculty Code</th>
                            <th>Faculty Type</th>
                            <th>Session</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Venue</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
/* Active/Archive Tab Navigation */
.timetable-report-tabs { border-bottom: 2px solid #e5e7eb; }
.timetable-report-tabs .nav-link {
    padding: 0.875rem 1.75rem;
    font-weight: 600;
    font-size: 0.95rem;
    color: #6b7280;
    border: none;
    border-radius: 12px 12px 0 0;
    background: transparent;
    margin-right: 0.5rem;
    transition: all 0.3s ease;
    cursor: pointer;
}
.timetable-report-tabs .nav-link:hover {
    color: #004a93;
    background-color: rgba(0, 74, 147, 0.05);
}
.timetable-report-tabs .nav-link.active {
    color: #004a93;
    background-color: white;
    border-bottom: 3px solid #004a93;
    box-shadow: 0 -2px 10px rgba(0, 74, 147, 0.1);
}
/* Select2 fixes to match form-select height & style */
.select2-container { width: 100% !important; display: block !important; }
.select2-container--open { z-index: 9999 !important; }
.select2-dropdown { z-index: 9999 !important; max-height: 300px; overflow-y: auto; }
.select2-container--default .select2-selection--single {
    height: 38px;
    padding: 0.375rem 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    background-color: #fff;
    font-size: 0.875rem;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 1.5;
    color: #212529;
    padding-left: 0;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
    right: 8px;
}
.select2-container--default .select2-selection--single .select2-selection__clear {
    position: absolute;
    right: 28px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1rem;
    line-height: 1;
    height: auto;
    margin: 0;
    padding: 0 4px;
}
#timetableReportTable .dtr-control,
#timetableReportTable th.dtr-control,
#timetableReportTable td.dtr-control { display: none !important; }
@media (max-width: 767.98px) {
    .table-scroll-vertical-sm { max-height: 65vh; overflow-y: auto; overflow-x: auto; -webkit-overflow-scrolling: touch; }
}
</style>
@endpush
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var reportDt = null;
    var dataUrl  = "{{ route('timetable-report.data') }}";
    var columnStorageKey = 'timetableReportGrid:columns:v1';
    var inputDebounceTimer = null;
    var currentMode = 'active';

    // ── Course data for Active / Archive switching ──
    var activeCourseOptions = '<option value="">-- All Courses --</option>'
        @foreach($activeCourses as $course)
            + '<option value="{{ $course->pk }}">{{ addslashes($course->course_name) }}</option>'
        @endforeach
        ;
    var archivedCourseOptions = '<option value="">-- All Courses --</option>'
        @foreach($archivedCourses as $course)
            + '<option value="{{ $course->pk }}">{{ addslashes($course->course_name) }}</option>'
        @endforeach
        ;

    // ── Active / Archive tab toggle ──
    $('#timetableReportTab .nav-link').on('click', function() {
        var $btn = $(this);
        if ($btn.hasClass('active')) return;

        $('#timetableReportTab .nav-link').removeClass('active').attr('aria-selected', 'false');
        $btn.addClass('active').attr('aria-selected', 'true');

        currentMode = $btn.data('mode');

        // Destroy Select2, swap options, re-init
        var $courseSelect = $('#filter_course');
        if ($courseSelect.hasClass('select2-hidden-accessible')) {
            $courseSelect.select2('destroy');
        }
        $courseSelect.html(currentMode === 'archive' ? archivedCourseOptions : activeCourseOptions);
        $courseSelect.select2({
            placeholder: '-- All Courses --',
            allowClear: true,
            width: '100%',
            dropdownParent: $('body'),
            dropdownAutoWidth: false
        });

        reloadTable();
    });

    // ── Init Select2 on all searchable dropdowns ──
    $('.select2-filter').each(function() {
        $(this).select2({
            placeholder: $(this).find('option:first').text(),
            allowClear: true,
            width: '100%',
            dropdownParent: $('body'),
            dropdownAutoWidth: false
        });
    });

    // ── Auto-load table on page load (no filters = show all) ──
    initOrReload();

    // ── Live AJAX: dropdowns ──
    $('.select2-filter').on('change', function() {
        reloadTable();
    });

    // ── Live AJAX: date inputs ──
    $('#filter_date_from, #filter_date_to').on('change', function() {
        reloadTable();
    });

    // ── Live AJAX: text inputs with debounce ──
    $('#filter_subject_topic, #filter_module_name').on('input', function() {
        clearTimeout(inputDebounceTimer);
        inputDebounceTimer = setTimeout(function() {
            reloadTable();
        }, 500);
    });

    function reloadTable() {
        if (!reportDt) {
            initOrReload();
        } else {
            reportDt.ajax.reload(null, true);
        }
    }

    // ── Column toggle helpers ──
    function buildColumnToggle() {
        if (!reportDt) return;
        var menu = $('#columnToggleMenu');
        menu.empty();
        reportDt.columns().every(function(i) {
            var col = this;
            var header = ($(col.header()).text() || '').trim();
            if (!header) return;
            var $li = $('<li>' +
                '<div class="dropdown-item px-3 py-1">' +
                    '<div class="form-check d-flex align-items-center mb-0">' +
                        '<input type="checkbox" class="form-check-input me-2 col-toggle" data-column="' + i + '">' +
                        '<label class="form-check-label cursor-pointer">' + header + '</label>' +
                    '</div>' +
                '</div>' +
            '</li>');
            $li.find('input.col-toggle').prop('checked', col.visible());
            $li.find('input.col-toggle').on('change', function(e) {
                e.stopPropagation();
                if (!reportDt) return;
                reportDt.column($(this).data('column')).visible($(this).prop('checked'));
                persistColumns();
            });
            $li.find('label').on('click', function(e) {
                e.preventDefault();
                var $cb = $(this).closest('.form-check').find('input.col-toggle');
                $cb.prop('checked', !$cb.prop('checked')).trigger('change');
            });
            menu.append($li);
        });
    }

    function persistColumns() {
        if (!reportDt) return;
        var state = {};
        reportDt.columns().every(function(i) { state[i] = this.visible(); });
        try { localStorage.setItem(columnStorageKey, JSON.stringify(state)); } catch(e) {}
    }

    function restoreColumns() {
        if (!reportDt) return;
        var raw = null;
        try { raw = localStorage.getItem(columnStorageKey); } catch(e) { raw = null; }
        if (!raw) return;
        var state = null;
        try { state = JSON.parse(raw); } catch(e) { state = null; }
        if (!state || typeof state !== 'object') return;
        Object.keys(state).forEach(function(k) {
            var idx = parseInt(k, 10);
            if (isNaN(idx)) return;
            reportDt.column(idx).visible(!!state[k], false);
        });
        reportDt.columns.adjust().draw(false);
    }

    // ── Print helpers ──
    function buildPrintableTableHtml() {
        if (!reportDt) return '';
        var vis = [];
        reportDt.columns().every(function(i) { if (this.visible()) vis.push(i); });

        var html = '<thead><tr>';
        vis.forEach(function(ci) {
            html += '<th>' + ($(reportDt.column(ci).header()).text() || '').trim() + '</th>';
        });
        html += '</tr></thead><tbody>';

        reportDt.rows({ search: 'applied' }).nodes().each(function(rowNode) {
            var $row = $(rowNode);
            if ($row.hasClass('child')) return;
            html += '<tr>';
            vis.forEach(function(ci) {
                var cellNode = reportDt.cell(rowNode, ci).node();
                var cellHtml = '';
                if (cellNode) {
                    var $cell = $(cellNode).clone();
                    $cell.find('input,button,select,textarea').remove();
                    cellHtml = ($cell.html() || '').trim();
                }
                html += '<td>' + cellHtml + '</td>';
            });
            html += '</tr>';
        });

        html += '</tbody>';
        return html;
    }

    function openPrintWindow(tableHtml) {
        var title = 'Timetable Session Report';
        var emblemUrl = '{{ asset("images/ashoka.png") }}';
        var logoUrl = '{{ asset("admin_assets/images/logos/logo.png") }}';

        // Build filter summary line
        var filterParts = [];
        var courseText = $('#filter_course option:selected').text().trim();
        if ($('#filter_course').val()) filterParts.push('Course: ' + courseText);
        var facultyText = $('#filter_faculty option:selected').text().trim();
        if ($('#filter_faculty').val()) filterParts.push('Faculty: ' + facultyText);
        var ftText = $('#filter_faculty_type option:selected').text().trim();
        if ($('#filter_faculty_type').val()) filterParts.push('Faculty Type: ' + ftText);
        var venueText = $('#filter_venue option:selected').text().trim();
        if ($('#filter_venue').val()) filterParts.push('Venue: ' + venueText);
        if ($('#filter_subject_topic').val()) filterParts.push('Topic: ' + $('#filter_subject_topic').val());
        if ($('#filter_module_name').val()) filterParts.push('Module: ' + $('#filter_module_name').val());
        if ($('#filter_date_from').val()) filterParts.push('From: ' + $('#filter_date_from').val());
        if ($('#filter_date_to').val()) filterParts.push('To: ' + $('#filter_date_to').val());
        var filterLine = filterParts.length ? filterParts.join(' | ') : 'No filters applied';

        var printWindow = window.open('', '_blank');
        if (!printWindow) { window.print(); return; }

        printWindow.document.open();
        printWindow.document.write('<!doctype html>\n' +
'<html lang="en">\n' +
'<head>\n' +
'    <meta charset="utf-8">\n' +
'    <title>' + title + ' - LBSNAA MUSSOORIE</title>\n' +
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
'        /* ── Data Table ── */\n' +
'        .data-table { width: 100%; border-collapse: collapse; font-size: 10px; }\n' +
'        .data-table th, .data-table td { padding: 4px 6px; border: 1px solid #bbb; vertical-align: middle; word-break: break-word; white-space: normal; }\n' +
'        .data-table thead th { background: #004a93; color: #fff; font-weight: 600; font-size: 10px; text-align: left; }\n' +
'\n' +
'        /* Alternating item rows */\n' +
'        .data-table tbody tr:nth-child(even) td { background: #f9fafb; }\n' +
'\n' +
'        /* ── Footer ── */\n' +
'        .footer { border-top: 1px solid #dee2e6; font-size: 8px; color: #666; text-align: center; padding-top: 4px; margin-top: 8px; }\n' +
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
'        <p class="line2">LBSNAA MUSSOORIE</p>\n' +
'        <p class="line3">Lal Bahadur Shastri National Academy of Administration</p>\n' +
'    </div>\n' +
'    <img src="' + logoUrl + '" alt="LBSNAA Logo" onerror="this.style.display=\'none\'">\n' +
'</div>\n' +
'\n' +
'<div class="report-title-block">\n' +
'    <h2>' + title + '</h2>\n' +
'    <span class="date-pill">' + filterLine + '</span>\n' +
'    <span class="date-pill-fallback">' + filterLine + '</span>\n' +
'</div>\n' +
'\n' +
'<div class="report-meta">\n' +
'    <strong>Course Mode:</strong> ' + (currentMode === 'archive' ? 'Archived' : 'Active') + ' &nbsp;&nbsp;|&nbsp;&nbsp; ' +
'    <strong>Printed:</strong> ' + new Date().toLocaleDateString('en-IN') + ' ' + new Date().toLocaleTimeString('en-IN', {hour:'2-digit',minute:'2-digit'}) + '\n' +
'</div>\n' +
'\n' +
'<table class="data-table">\n' + tableHtml + '\n</table>\n' +
'\n' +
'<div class="footer"><small>LBSNAA Mussoorie &mdash; Timetable Session Report</small></div>\n' +
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

    // ── DataTable init / reload ──
    function initOrReload() {
        if (!reportDt) {
            reportDt = $('#timetableReportTable').DataTable({
                processing: true,
                serverSide: true,
                searching: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                ajax: {
                    url: dataUrl,
                    data: function(d) {
                        d.course_pk     = $('#filter_course').val();
                        d.course_mode   = currentMode;
                        d.faculty_pk    = $('#filter_faculty').val();
                        d.faculty_type  = $('#filter_faculty_type').val();
                        d.venue_id      = $('#filter_venue').val();
                        d.subject_topic = $('#filter_subject_topic').val();
                        d.module_name   = $('#filter_module_name').val();
                        d.date_from     = $('#filter_date_from').val();
                        d.date_to       = $('#filter_date_to').val();
                    }
                },
                columns: [
                    { data: 'sno',              orderable: false, searchable: false },
                    { data: 'course_name' },
                    { data: 'course_group_type' },
                    { data: 'group_name' },
                    { data: 'subject_name' },
                    { data: 'module_name' },
                    { data: 'subject_topic' },
                    { data: 'faculty_name' },
                    { data: 'faculty_code' },
                    { data: 'faculty_type' },
                    { data: 'class_session' },
                    { data: 'start_date' },
                    { data: 'end_date' },
                    { data: 'venue_name' },
                ],
                order: [[11, 'desc']],
                responsive: false,
                autoWidth: false,
                scrollX: true,
                dom: '<"row flex-nowrap align-items-center py-2"<"col-12 col-sm-6 col-md-6 mb-2 mb-md-0"l><"col-12 col-sm-6 col-md-6"f>>rt<"row align-items-center py-2"<"col-12 col-sm-5 col-md-5"i><"col-12 col-sm-7 col-md-7"p>>'
            });

            reportDt.on('draw', function() { buildColumnToggle(); });
            restoreColumns();
            buildColumnToggle();

            $('#btnColumns').prop('disabled', false);
            $('#btnPrint').prop('disabled', false);

            // Move toolbar buttons into DataTables search row
            var $wrapper = $('#timetableReportTable').closest('.dataTables_wrapper');
            var $filter  = $wrapper.find('.dataTables_filter');
            var $toolbar = $('#timetableReportToolbar').children().detach();
            if ($filter.length && $toolbar.length) {
                $filter.addClass('d-flex align-items-center justify-content-end flex-wrap gap-2');
                $filter.append($toolbar);
            }
        } else {
            reportDt.ajax.reload(null, true);
        }
    }

    // ── Reset ──
    $('#btnReset').on('click', function() {
        // Reset to Active tab
        if (currentMode !== 'active') {
            $('#timetableReportTab .nav-link').removeClass('active').attr('aria-selected', 'false');
            $('#active-tab-btn').addClass('active').attr('aria-selected', 'true');
            currentMode = 'active';

            var $courseSelect = $('#filter_course');
            if ($courseSelect.hasClass('select2-hidden-accessible')) {
                $courseSelect.select2('destroy');
            }
            $courseSelect.html(activeCourseOptions);
            $courseSelect.select2({
                placeholder: '-- All Courses --',
                allowClear: true,
                width: '100%',
                dropdownParent: $('body'),
                dropdownAutoWidth: false
            });
        }

        $('.select2-filter').val('').trigger('change.select2');
        $('#filter_subject_topic, #filter_module_name').val('');
        $('#filter_date_from, #filter_date_to').val('');
        if (reportDt) {
            reportDt.ajax.reload(null, true);
        }
    });

    // ── Print ──
    $('#btnPrint').on('click', function() {
        if (!reportDt) { window.print(); return; }
        var originalLen  = reportDt.page.len();
        var originalPage = reportDt.page();
        var restored = false;

        var restore = function() {
            if (restored) return;
            restored = true;
            reportDt.page.len(originalLen);
            reportDt.page(originalPage);
            reportDt.draw(false);
        };

        reportDt.one('draw', function() {
            setTimeout(function() {
                openPrintWindow(buildPrintableTableHtml());
                setTimeout(restore, 800);
            }, 250);
        });

        reportDt.page.len(-1).draw();
    });

    // ── Build export query string from current filters ──
    function buildExportQueryString() {
        var params = {
            course_mode:   currentMode,
            course_pk:     $('#filter_course').val()       || '',
            faculty_pk:    $('#filter_faculty').val()       || '',
            faculty_type:  $('#filter_faculty_type').val()  || '',
            venue_id:      $('#filter_venue').val()         || '',
            subject_topic: $('#filter_subject_topic').val() || '',
            module_name:   $('#filter_module_name').val()   || '',
            date_from:     $('#filter_date_from').val()     || '',
            date_to:       $('#filter_date_to').val()       || ''
        };
        // Include visible column indices
        if (reportDt) {
            var visCols = [];
            reportDt.columns().every(function(i) {
                if (this.visible()) visCols.push(i);
            });
            params.visible_columns = visCols.join(',');
        }
        var qs = [];
        for (var key in params) {
            if (params[key] !== '') {
                qs.push(encodeURIComponent(key) + '=' + encodeURIComponent(params[key]));
            }
        }
        return qs.length ? '?' + qs.join('&') : '';
    }

    // ── PDF Export ──
    $('#btnPdf').on('click', function(e) {
        e.preventDefault();
        window.location.href = "{{ route('timetable-report.pdf') }}" + buildExportQueryString();
    });

    // ── Excel Export ──
    $('#btnExcel').on('click', function(e) {
        e.preventDefault();
        window.location.href = "{{ route('timetable-report.excel') }}" + buildExportQueryString();
    });
});
</script>
@endpush
