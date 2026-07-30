@extends('admin.layouts.master')

@section('title', 'Timetable Session Report')

@section('setup_content')
@php
$activeCourseCount = $activeCourses->count();
$archivedCourseCount = $archivedCourses->count();
@endphp

<div class="container-fluid ttr-master-page py-3 px-3 px-lg-4">
    <x-breadcrum title="Timetable Session Report"></x-breadcrum>
    <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-between gap-3 mb-3">
            <ul class="nav nav-pills gap-2 programme-status-tabs ttr-status-tabs mb-0"
                id="timetableReportTab"
                role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-2 px-4 py-2 fw-semibold programme-status-pill active mb-0"
                        id="active-tab-btn"
                        type="button"
                        role="tab"
                        aria-selected="true"
                        data-mode="active">
                        Active: {{ $activeCourseCount }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-2 px-4 py-2 fw-semibold programme-status-pill mb-0"
                        id="archive-tab-btn"
                        type="button"
                        role="tab"
                        aria-selected="false"
                        data-mode="archive">
                        Archived: {{ $archivedCourseCount }}
                    </button>
                </li>
            </ul>

            <button type="button"
                class="btn border ttr-btn-download d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold flex-shrink-0"
                id="ttrTopDownload"
                title="Download PDF report">
                <i class="bi bi-download flex-shrink-0" aria-hidden="true"></i>
                <span>Download</span>
            </button>
        </div>
    <div class="card ttr-dt-card border-0 shadow-sm rounded-3 overflow-hidden no-print">

        <div class="card-body p-0">
            <div id="timetableReportFilterForm" class="px-3 px-md-4 pt-3 pb-0">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 pb-3 programme-dt-toolbar ttr-dt-toolbar w-100">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <span class="programme-dt-filters-label">Filters</span>

                        <div class="programme-dt-filter-select ttr-filter-course">
                            <label for="filter_course" class="visually-hidden">Course Name</label>
                            <select class="form-select select2-filter" id="filter_course" name="course_pk" aria-label="Course Name">
                                <option value="">Course Name</option>
                                @foreach($activeCourses as $course)
                                <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="programme-dt-filter-select">
                            <label for="filter_faculty_type" class="visually-hidden">Faculty Type</label>
                            <select class="form-select select2-filter" id="filter_faculty_type" name="faculty_type" aria-label="Faculty Type">
                                <option value="">Faculty Type</option>
                                <option value="1">Internal</option>
                                <option value="2">Guest</option>
                                <option value="3">Research</option>
                            </select>
                        </div>

                        <div class="programme-dt-filter-select">
                            <label for="filter_faculty" class="visually-hidden">Faculty</label>
                            <select class="form-select select2-filter ttr-filter-faculty" id="filter_faculty" name="faculty_pk" aria-label="Faculty">
                                <option value="">Faculty</option>
                                @foreach($faculties as $faculty)
                                <option value="{{ $faculty->pk }}">{{ $faculty->full_name }} ({{ $faculty->faculty_code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="button"
                            class="btn btn-link p-0 ttr-more-filters-link"
                            id="ttrMoreFiltersToggle"
                            data-bs-toggle="collapse"
                            data-bs-target="#ttrMoreFilters"
                            aria-expanded="false"
                            aria-controls="ttrMoreFilters">
                            +2 Filters
                        </button>

                        <button type="button" class="btn programme-dt-btn-reset flex-shrink-0" id="btnReset">
                            Reset Filters
                        </button>
                    </div>

                    <div id="ttrToolbarMount" class="d-flex flex-wrap align-items-center justify-content-lg-end gap-2 ms-lg-auto flex-shrink-0">
                        <div id="timetableReportToolbar" class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button"
                                class="btn ttr-columns-btn d-inline-flex align-items-center gap-2 border position-relative"
                                id="btnColumns"
                                data-bs-toggle="modal"
                                data-bs-target="#ttrColumnVisibilityModal"
                                title="Column visibility"
                                disabled
                                aria-controls="ttrColumnVisibilityModal">
                                <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                                <span>Columns</span>
                                <span class="ttr-columns-badge position-absolute top-0 start-100 translate-middle badge rounded-pill d-none"
                                    id="ttrColumnsBadge"
                                    aria-hidden="true">0</span>
                            </button>
                        </div>

                        <div class="dropdown ttr-search-slot">
                            <button type="button"
                                class="btn ttr-search-trigger"
                                id="ttrSearchTrigger"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false"
                                aria-label="Search table">
                                <i class="bi bi-search" aria-hidden="true"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 p-3 ttr-search-menu">
                                <label class="form-label small text-secondary mb-2">Search</label>
                                <div id="ttrDtSearchHost" class="ttr-dt-search-host" data-dt-search-for="timetableReportTable"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="collapse mb-3" id="ttrMoreFilters">
                    <div class="row g-3 ttr-extra-filters pt-2 border-top">
                        <div class="col-12 col-md-6 col-lg-3">
                            <label for="filter_venue" class="form-label">Venue</label>
                            <select class="form-select select2-filter" id="filter_venue" name="venue_id">
                                <option value="">-- All Venues --</option>
                                @foreach($venues as $venue)
                                <option value="{{ $venue->venue_id }}">{{ $venue->venue_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <label for="filter_subject_topic" class="form-label">Subject Topic</label>
                            <input type="text" class="form-control" id="filter_subject_topic" name="subject_topic" placeholder="Search topic...">
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <label for="filter_module_name" class="form-label">Subject Module</label>
                            <input type="text" class="form-control" id="filter_module_name" name="module_name" placeholder="Search module...">
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <label for="filter_date_from" class="form-label">Date From</label>
                            <input type="date" class="form-control" id="filter_date_from" name="date_from">
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <label for="filter_date_to" class="form-label">Date To</label>
                            <input type="date" class="form-control" id="filter_date_to" name="date_to">
                        </div>
                    </div>
                </div>
            </div>

            <div class="programme-dt-panel ttr-dt-panel border-0 rounded-0">
                <div class="table-responsive ttr-dt-scroll">
                    <table class="table align-middle mb-0 w-100 programme-dt-table ttr-dt-table" id="timetableReportTable" data-dt-footer="#ttrDtFooter">
                        <thead>
                            <tr>
                                <th scope="col" class="ttr-col-sno text-nowrap">S. No.</th>
                                <th scope="col" class="ttr-col-course">Course</th>
                                <th scope="col" class="text-nowrap">Course Group Type</th>
                                <th scope="col" class="text-nowrap">Group</th>
                                <th scope="col">Subject</th>
                                <th scope="col">Module</th>
                                <th scope="col" class="ttr-col-topic">Topic</th>
                                <th scope="col" class="text-nowrap">Faculty Code</th>
                                <th scope="col">Faculty</th>
                                <th scope="col" class="text-nowrap">Faculty Type</th>
                                <th scope="col" class="text-nowrap">Session</th>
                                <th scope="col" class="text-nowrap">Start Date</th>
                                <th scope="col" class="text-nowrap">End Date</th>
                                <th scope="col">Venue</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div id="ttrDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 px-3 px-md-4"
                    data-dt-footer-for="timetableReportTable"></div>
            </div>
        </div>
    </div>

    <div class="modal fade ttr-columns-modal-root" id="ttrColumnVisibilityModal" tabindex="-1"
        aria-labelledby="ttrColumnVisibilityLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold mb-0 text-dark" id="ttrColumnVisibilityLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <div id="columnToggleMenu" class="d-flex flex-wrap gap-2 ttr-column-toggle-grid" role="group" aria-label="Toggle table columns"></div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex flex-wrap align-items-center justify-content-between gap-2 w-100">
                    <div class="d-flex flex-wrap align-items-center gap-3 ttr-modal-export-links">
                        <button type="button" class="btn btn-link btn-sm text-primary text-decoration-none p-0 fw-semibold" id="ttrModalPrint">
                            <i class="bi bi-printer me-1" aria-hidden="true"></i>Print
                        </button>
                        <button type="button" class="btn btn-link btn-sm text-success text-decoration-none p-0 fw-semibold" id="ttrModalExcel">
                            <i class="bi bi-file-earmark-spreadsheet me-1" aria-hidden="true"></i>Export Excel
                        </button>
                    </div>
                    <button type="button" class="btn btn-outline-primary px-4 py-2 rounded-2 fw-semibold ttr-columns-close-btn ms-auto"
                        data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-none" aria-hidden="true">
        <button type="button" id="btnPrint" disabled>Print</button>
        <a href="#" id="btnPdf">PDF</a>
        <a href="#" id="btnExcel">Excel</a>
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<link rel="stylesheet" href="{{ asset('css/timetable-report-admin.css') }}?v={{ @filemtime(public_path('css/timetable-report-admin.css')) ?: time() }}">
@endpush
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        var reportDt = null;
        var dataUrl = "{{ route('timetable-report.data') }}";
        var columnStorageKey = 'timetableReportGrid:columns:v1';
        var inputDebounceTimer = null;
        var currentMode = 'active';

        var activeCourseOptions = '<option value="">Course Name</option>'
        @foreach($activeCourses as $course) +
            '<option value="{{ $course->pk }}">{{ addslashes($course->course_name) }}</option>'
        @endforeach
        ;
        var archivedCourseOptions = '<option value="">Course Name</option>'
        @foreach($archivedCourses as $course) +
            '<option value="{{ $course->pk }}">{{ addslashes($course->course_name) }}</option>'
        @endforeach
        ;

        $('#timetableReportTab .nav-link').on('click', function() {
            var $btn = $(this);
            if ($btn.hasClass('active')) return;

            $('#timetableReportTab .nav-link').removeClass('active').attr('aria-selected', 'false');
            $btn.addClass('active').attr('aria-selected', 'true');

            currentMode = $btn.data('mode');

            var $courseSelect = $('#filter_course');
            if ($courseSelect.hasClass('select2-hidden-accessible')) {
                $courseSelect.select2('destroy');
            }
            $courseSelect.html(currentMode === 'archive' ? archivedCourseOptions : activeCourseOptions);
            $courseSelect.select2({
                placeholder: 'Course Name',
                allowClear: true,
                width: '100%',
                dropdownParent: $('body'),
                dropdownAutoWidth: false
            });

            reloadTable();
        });

        $('.select2-filter').each(function() {
            $(this).select2({
                placeholder: $(this).find('option:first').text(),
                allowClear: true,
                width: '100%',
                dropdownParent: $('body'),
                dropdownAutoWidth: false
            });
        });

        initOrReload();

        $('.select2-filter').on('change', function() {
            reloadTable();
        });

        $('#filter_date_from, #filter_date_to').on('change', function() {
            reloadTable();
        });

        $('#filter_subject_topic, #filter_module_name').on('input', function() {
            clearTimeout(inputDebounceTimer);
            inputDebounceTimer = setTimeout(function() {
                reloadTable();
            }, 500);
        });

        $('#ttrTopDownload').on('click', function(e) {
            e.preventDefault();
            $('#btnPdf').trigger('click');
        });

        function reloadTable() {
            if (!reportDt) {
                initOrReload();
            } else {
                reportDt.ajax.reload(null, true);
            }
        }

        function updateColumnsBadge() {
            if (!reportDt) return;
            var hidden = 0;
            reportDt.columns().every(function() {
                if (!this.visible()) hidden++;
            });
            var $badge = $('#ttrColumnsBadge');
            if (hidden > 0) {
                $badge.text(hidden).removeClass('d-none');
            } else {
                $badge.addClass('d-none');
            }
        }

        function buildColumnToggle() {
            if (!reportDt) return;
            var menu = $('#columnToggleMenu');
            menu.empty();
            reportDt.columns().every(function(i) {
                var col = this;
                var header = ($(col.header()).text() || '').trim();
                if (!header) return;
                var $chip = $(
                    '<label class="ttr-column-chip d-inline-flex align-items-center gap-2 mb-0">' +
                    '<input type="checkbox" class="form-check-input col-toggle flex-shrink-0" data-column="' + i + '">' +
                    '<span class="ttr-column-chip-label">' + header + '</span>' +
                    '</label>'
                );
                $chip.find('input.col-toggle').prop('checked', col.visible());
                $chip.toggleClass('ttr-column-chip--on', col.visible());
                $chip.find('input.col-toggle').on('change', function() {
                    if (!reportDt) return;
                    var checked = $(this).prop('checked');
                    $(this).closest('.ttr-column-chip').toggleClass('ttr-column-chip--on', checked);
                    reportDt.column($(this).data('column')).visible(checked);
                    persistColumns();
                    updateColumnsBadge();
                });
                menu.append($chip);
            });
            updateColumnsBadge();
        }

        $('#ttrColumnVisibilityModal').on('show.bs.modal', function() {
            buildColumnToggle();
        });

        $('#ttrModalPrint').on('click', function() {
            $('#btnPrint').trigger('click');
        });

        $('#ttrModalExcel').on('click', function(e) {
            e.preventDefault();
            $('#btnExcel').trigger('click');
        });

        function persistColumns() {
            if (!reportDt) return;
            var state = {};
            reportDt.columns().every(function(i) {
                state[i] = this.visible();
            });
            try {
                localStorage.setItem(columnStorageKey, JSON.stringify(state));
            } catch (e) {}
        }

        function restoreColumns() {
            if (!reportDt) return;
            var raw = null;
            try {
                raw = localStorage.getItem(columnStorageKey);
            } catch (e) {
                raw = null;
            }
            if (!raw) return;
            var state = null;
            try {
                state = JSON.parse(raw);
            } catch (e) {
                state = null;
            }
            if (!state || typeof state !== 'object') return;
            Object.keys(state).forEach(function(k) {
                var idx = parseInt(k, 10);
                if (isNaN(idx)) return;
                reportDt.column(idx).visible(!!state[k], false);
            });
            reportDt.columns.adjust().draw(false);
            updateColumnsBadge();
        }

        function buildPrintableTableHtml() {
            if (!reportDt) return '';
            var vis = [];
            reportDt.columns().every(function(i) {
                if (this.visible()) vis.push(i);
            });

            var html = '<thead><tr>';
            vis.forEach(function(ci) {
                html += '<th>' + ($(reportDt.column(ci).header()).text() || '').trim() + '</th>';
            });
            html += '</tr></thead><tbody>';

            reportDt.rows({
                search: 'applied'
            }).nodes().each(function(rowNode) {
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
            if (!printWindow) {
                window.print();
                return;
            }

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
                '        .date-pill-fallback { display: none; }\n' +
                '        .report-meta {\n' +
                '            font-size: 10px;\n' +
                '            line-height: 1.7;\n' +
                '            margin: 8px 0 10px;\n' +
                '            color: #333;\n' +
                '        }\n' +
                '        .report-meta strong { color: #1a1a1a; }\n' +
                '        .data-table { width: 100%; border-collapse: collapse; font-size: 10px; }\n' +
                '        .data-table th, .data-table td { padding: 4px 6px; border: 1px solid #bbb; vertical-align: middle; word-break: break-word; white-space: normal; }\n' +
                '        .data-table thead th { background: #004a93; color: #fff; font-weight: 600; font-size: 10px; text-align: left; }\n' +
                '        .data-table tbody tr:nth-child(even) td { background: #f9fafb; }\n' +
                '        .footer { border-top: 1px solid #dee2e6; font-size: 8px; color: #666; text-align: center; padding-top: 4px; margin-top: 8px; }\n' +
                '        @page { size: A4 landscape; margin: 8mm; }\n' +
                '        @media print {\n' +
                '            body { padding: 0; }\n' +
                '            thead { display: table-header-group; }\n' +
                '            tr { page-break-inside: avoid; }\n' +
                '        }\n' +
                '    </style>\n' +
                '</head>\n' +
                '<body>\n' +
                '<div class="print-header">\n' +
                '    <img src="' + emblemUrl + '" alt="Emblem">\n' +
                '    <div class="header-text">\n' +
                '        <p class="line1">Government of India</p>\n' +
                '        <p class="line2">LBSNAA MUSSOORIE</p>\n' +
                '        <p class="line3">Lal Bahadur Shastri National Academy of Administration</p>\n' +
                '    </div>\n' +
                '    <img src="' + logoUrl + '" alt="LBSNAA Logo" onerror="this.style.display=\'none\'">\n' +
                '</div>\n' +
                '<div class="report-title-block">\n' +
                '    <h2>' + title + '</h2>\n' +
                '    <span class="date-pill">' + filterLine + '</span>\n' +
                '    <span class="date-pill-fallback">' + filterLine + '</span>\n' +
                '</div>\n' +
                '<div class="report-meta">\n' +
                '    <strong>Course Mode:</strong> ' + (currentMode === 'archive' ? 'Archived' : 'Active') + ' &nbsp;&nbsp;|&nbsp;&nbsp; ' +
                '    <strong>Printed:</strong> ' + new Date().toLocaleDateString('en-IN') + ' ' + new Date().toLocaleTimeString('en-IN', {
                    hour: '2-digit',
                    minute: '2-digit'
                }) + '\n' +
                '</div>\n' +
                '<table class="data-table">\n' + tableHtml + '\n</table>\n' +
                '<div class="footer"><small>LBSNAA Mussoorie &mdash; Timetable Session Report</small></div>\n' +
                '<script>\n' +
                '    window.addEventListener("load", function() {\n' +
                '        setTimeout(function() { window.print(); }, 300);\n' +
                '    });\n' +
                '<\/script>\n' +
                '</body>\n' +
                '</html>');
            printWindow.document.close();
        }

        function initOrReload() {
            if (!reportDt) {
                reportDt = $('#timetableReportTable').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: true,
                    pageLength: 10,
                    lengthChange: true,
                    lengthMenu: [
                        [10, 25, 50, 100, 200],
                        [10, 25, 50, 100, 200]
                    ],
                    pagingType: 'full_numbers',
                    ajax: {
                        url: dataUrl,
                        data: function(d) {
                            d.course_pk = $('#filter_course').val();
                            d.course_mode = currentMode;
                            d.faculty_pk = $('#filter_faculty').val();
                            d.faculty_type = $('#filter_faculty_type').val();
                            d.venue_id = $('#filter_venue').val();
                            d.subject_topic = $('#filter_subject_topic').val();
                            d.module_name = $('#filter_module_name').val();
                            d.date_from = $('#filter_date_from').val();
                            d.date_to = $('#filter_date_to').val();
                        }
                    },
                    columns: [{
                            data: 'sno',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'course_name'
                        },
                        {
                            data: 'course_group_type'
                        },
                        {
                            data: 'group_name'
                        },
                        {
                            data: 'subject_name'
                        },
                        {
                            data: 'module_name'
                        },
                        {
                            data: 'subject_topic'
                        },
                        {
                            data: 'faculty_code'
                        },
                        {
                            data: 'faculty_name'
                        },
                        {
                            data: 'faculty_type'
                        },
                        {
                            data: 'class_session'
                        },
                        {
                            data: 'start_date'
                        },
                        {
                            data: 'end_date'
                        },
                        {
                            data: 'venue_name'
                        },
                    ],
                    order: [
                        [11, 'desc']
                    ],
                    responsive: false,
                    autoWidth: false,
                    scrollX: false
                });

                reportDt.on('draw', function() {
                    buildColumnToggle();
                    if (window.SargamDataTableUI) {
                        window.SargamDataTableUI.enhance(reportDt);
                    }
                });
                restoreColumns();
                buildColumnToggle();

                $('#btnColumns').prop('disabled', false);
                $('#btnPrint').prop('disabled', false);

                if (window.SargamDataTableUI) {
                    window.SargamDataTableUI.enhance(reportDt);
                }
            } else {
                reportDt.ajax.reload(null, true);
            }
        }

        $('#btnReset').on('click', function() {
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
                    placeholder: 'Course Name',
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

        $('#btnPrint').on('click', function() {
            if (!reportDt) {
                window.print();
                return;
            }
            var originalLen = reportDt.page.len();
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

        function buildExportQueryString() {
            var params = {
                course_mode: currentMode,
                course_pk: $('#filter_course').val() || '',
                faculty_pk: $('#filter_faculty').val() || '',
                faculty_type: $('#filter_faculty_type').val() || '',
                venue_id: $('#filter_venue').val() || '',
                subject_topic: $('#filter_subject_topic').val() || '',
                module_name: $('#filter_module_name').val() || '',
                date_from: $('#filter_date_from').val() || '',
                date_to: $('#filter_date_to').val() || ''
            };
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

        $('#btnPdf').on('click', function(e) {
            e.preventDefault();
            window.location.href = "{{ route('timetable-report.pdf') }}" + buildExportQueryString();
        });

        $('#btnExcel').on('click', function(e) {
            e.preventDefault();
            window.location.href = "{{ route('timetable-report.excel') }}" + buildExportQueryString();
        });
    });
</script>
@endpush