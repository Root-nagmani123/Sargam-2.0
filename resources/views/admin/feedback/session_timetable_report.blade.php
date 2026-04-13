@extends('admin.layouts.master')

@section('title', 'Timetable Sessions Report - Sargam | Lal Bahadur')

@section('setup_content')
    @include('admin.partials.choices-bootstrap5')
    <style>
        :root {
            --str-primary: #0b4f8a;
            --str-primary-light: #eef4fb;
        }

        .str-filter-card {
            border: 0;
            border-radius: var(--bs-border-radius-lg);
            box-shadow: 0 1px 4px rgba(0, 0, 0, .06);
        }

        .str-filter-card .card-header {
            background: var(--str-primary);
            color: #fff;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0.7rem 1rem;
            border: 0;
        }

        .str-filter-card .form-label {
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--bs-secondary-color);
            margin-bottom: 0.25rem;
        }

        .str-content-card {
            border: 0;
            border-radius: var(--bs-border-radius-lg);
            box-shadow: 0 1px 4px rgba(0, 0, 0, .06);
        }

        .str-content-card .card-header {
            background: var(--str-primary-light);
            font-weight: 600;
            font-size: 0.95rem;
            padding: 0.7rem 1rem;
            border-bottom: 1px solid rgba(11, 79, 138, .1);
        }

        #sessionTimetableReportTable_wrapper .dataTables_filter,
        #sessionTimetableReportTable_wrapper .dataTables_length {
            margin-bottom: 0.75rem;
        }

        #sessionTimetableReportTable_wrapper table.dataTable thead th {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--bs-secondary-color);
            vertical-align: middle;
        }

        #sessionTimetableReportTable_wrapper table.dataTable tbody td {
            font-size: 0.82rem;
            vertical-align: middle;
        }

        .session-timetable-report-page .card-body {
            overflow-x: auto;
        }
    </style>

    <div class="container-fluid py-3 session-timetable-report-page">
        <x-breadcrum title="Timetable Sessions Report" />
        <x-session_message />

        <div class="choices-bs-scope" id="strChoicesScope">
            <div class="card str-filter-card mb-3">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="fas fa-sliders-h"></i> Filters
                </div>
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        @php
                            $courseType = $courseType ?? 'current';
                            $defaultCourseId = isset($defaultCourseId) ? $defaultCourseId : null;
                        @endphp
                        <div class="col-12">
                            <label class="form-label">Course list</label>
                            <div class="d-flex flex-wrap gap-4">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="str_course_type" value="current"
                                        id="strCourseCurrent" {{ $courseType === 'current' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="strCourseCurrent">Active (current) courses</label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="str_course_type" value="archived"
                                        id="strCourseArchived" {{ $courseType === 'archived' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="strCourseArchived">Archived courses</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <label class="form-label" for="filter_course">Course</label>
                            <select class="form-select" id="filter_course" name="course_id"
                                data-placeholder="All programs in list">
                                <option value="" @selected($defaultCourseId === null)>All programs in list</option>
                                @foreach ($courses ?? [] as $course)
                                    <option value="{{ $course->pk }}" @selected($defaultCourseId !== null && (int) $course->pk === (int) $defaultCourseId)>
                                        {{ $course->course_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <label class="form-label" for="filter_faculty">Faculty</label>
                            <select class="form-select" id="filter_faculty" name="faculty_id"
                                data-placeholder="Any faculty">
                                <option value="">Any faculty</option>
                                @foreach ($faculties ?? [] as $faculty)
                                    <option value="{{ $faculty->pk }}">{{ $faculty->full_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-4">
                            <label class="form-label" for="filter_faculty_type">Faculty type</label>
                            <select class="form-select" id="filter_faculty_type" name="faculty_type"
                                data-placeholder="Any type">
                                <option value="">Any type</option>
                                <option value="1">Internal</option>
                                <option value="2">Guest</option>
                                <option value="3">Research</option>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-4">
                            <label class="form-label" for="filter_venue">Venue</label>
                            <select class="form-select" id="filter_venue" name="venue_id"
                                data-placeholder="Any venue">
                                <option value="">Any venue</option>
                                @foreach ($venues ?? [] as $venue)
                                    <option value="{{ $venue->venue_id }}">{{ $venue->venue_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-4">
                            <label class="form-label" for="filter_date_from">Date from</label>
                            <input type="date" class="form-control" id="filter_date_from" name="date_from"
                                autocomplete="off">
                        </div>

                        <div class="col-lg-2 col-md-4">
                            <label class="form-label" for="filter_date_to">Date to</label>
                            <input type="date" class="form-control" id="filter_date_to" name="date_to"
                                autocomplete="off">
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <label class="form-label" for="filter_subject_topic">Subject topic</label>
                            <input type="text" class="form-control" id="filter_subject_topic" name="subject_topic"
                                placeholder="Contains text…" maxlength="500" autocomplete="off">
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <label class="form-label" for="filter_subject_module">Subject module</label>
                            <input type="text" class="form-control" id="filter_subject_module" name="subject_module"
                                placeholder="Module name contains…" maxlength="500" autocomplete="off">
                        </div>

                        <div class="col-lg-4 col-md-12 d-flex flex-wrap gap-2 align-items-end">
                            <p class="small text-muted mb-0 me-auto align-self-center">
                                Server-side DataTable reloads when you change a filter.
                            </p>
                            <button type="button" class="btn btn-outline-secondary" id="strClearBtn">
                                Clear filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card str-content-card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span><i class="fas fa-table text-primary me-2"></i> Sessions</span>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a href="#" id="strPrintBtn" target="_blank" rel="noopener"
                        class="btn btn-outline-primary btn-sm rounded-1 d-inline-flex align-items-center gap-1">
                        <span class="material-symbols-rounded" style="font-size: 1.1rem;">print</span>
                        Print
                    </a>
                    <a href="#" id="strPdfBtn" target="_blank" rel="noopener"
                        class="btn btn-outline-danger btn-sm rounded-1 d-inline-flex align-items-center gap-1">
                        <span class="material-symbols-rounded" style="font-size: 1.1rem;">picture_as_pdf</span>
                        PDF
                    </a>
                    <a href="#" id="strExcelBtn"
                        class="btn btn-success btn-sm rounded-1 shadow-sm d-inline-flex align-items-center gap-1">
                        <span class="material-symbols-rounded" style="font-size: 1.1rem;">table_view</span>
                        Excel
                    </a>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="table-responsive">
                    {!! $dataTable->table([
                        'class' => 'table table-hover align-middle text-nowrap w-100',
                        'width' => '100%',
                        'id' => 'sessionTimetableReportTable',
                    ]) !!}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    {!! $dataTable->scripts() !!}
    <script>
        (function() {
            const COURSES_URL = @json(route('admin.feedback.database.courses'));
            const STR_EXPORT_PRINT = @json(route('admin.feedback.session_timetable_report.print'));
            const STR_EXPORT_PDF = @json(route('admin.feedback.session_timetable_report.export.pdf'));
            const STR_EXPORT_EXCEL = @json(route('admin.feedback.session_timetable_report.export.excel'));
            const STR_DEFAULT_COURSE_ID = @json($defaultCourseId ?? null);

            function destroyChoicesEl(el) {
                if (!el) return;
                if (el._choicesBs) {
                    try {
                        el._choicesBs.destroy();
                    } catch (e) {}
                    el._choicesBs = null;
                }
            }

            function strCourseType() {
                var r = document.querySelector('input[name="str_course_type"]:checked');
                return r ? r.value : 'current';
            }

            function buildSessionTimetableExportQuery() {
                var p = new URLSearchParams();
                p.set('filter_course_type', strCourseType());
                p.set('filter_course_id', $('#filter_course').val() || '');
                p.set('filter_faculty_id', $('#filter_faculty').val() || '');
                p.set('filter_faculty_type', $('#filter_faculty_type').val() || '');
                p.set('filter_venue_id', $('#filter_venue').val() || '');
                p.set('filter_subject_topic', ($('#filter_subject_topic').val() || '').trim());
                p.set('filter_subject_module', ($('#filter_subject_module').val() || '').trim());
                p.set('filter_date_from', $('#filter_date_from').val() || '');
                p.set('filter_date_to', $('#filter_date_to').val() || '');
                return p.toString();
            }

            function syncSessionTimetableExportLinks() {
                var q = buildSessionTimetableExportQuery();
                $('#strPrintBtn').attr('href', STR_EXPORT_PRINT + (q ? '?' + q : ''));
                $('#strPdfBtn').attr('href', STR_EXPORT_PDF + (q ? '?' + q : ''));
                $('#strExcelBtn').attr('href', STR_EXPORT_EXCEL + (q ? '?' + q : ''));
            }

            function reloadCourseOptionsThenTable(dt) {
                var ct = strCourseType();
                fetch(COURSES_URL + '?course_type=' + encodeURIComponent(ct), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(function(res) {
                        return res.json();
                    })
                    .then(function(data) {
                        var sel = document.getElementById('filter_course');
                        if (!sel) return;
                        destroyChoicesEl(sel);
                        sel.innerHTML = '';
                        var opt0 = document.createElement('option');
                        opt0.value = '';
                        opt0.textContent = 'All programs in list';
                        sel.appendChild(opt0);
                        if (data.success && data.courses && data.courses.length) {
                            data.courses.forEach(function(c) {
                                var o = document.createElement('option');
                                o.value = c.pk;
                                o.textContent = c.course_name || '';
                                sel.appendChild(o);
                            });
                            sel.value = String(data.courses[0].pk);
                        }
                        var scope = document.getElementById('strChoicesScope');
                        if (scope && typeof window.initChoicesBootstrap5In === 'function') {
                            window.initChoicesBootstrap5In(scope);
                        }
                        if (dt) {
                            dt.ajax.reload(null, false);
                        }
                        syncSessionTimetableExportLinks();
                    })
                    .catch(function() {
                        alert('Could not refresh course list.');
                    });
            }

            function debounce(fn, ms) {
                var t;
                return function() {
                    clearTimeout(t);
                    var ctx = this,
                        args = arguments;
                    t = setTimeout(function() {
                        fn.apply(ctx, args);
                    }, ms);
                };
            }

            $(document).ready(function() {
                var dt = window.LaravelDataTables && window.LaravelDataTables['sessionTimetableReportTable'];
                if (!dt) {
                    return;
                }

                function reloadDt() {
                    dt.ajax.reload(null, false);
                    syncSessionTimetableExportLinks();
                }

                var debouncedText = debounce(reloadDt, 400);

                if (STR_DEFAULT_COURSE_ID) {
                    var fcEl = document.getElementById('filter_course');
                    var cur = $('#filter_course').val() || '';
                    if (String(cur) !== String(STR_DEFAULT_COURSE_ID) && fcEl && fcEl._choicesBs) {
                        try {
                            fcEl._choicesBs.setChoiceByValue(String(STR_DEFAULT_COURSE_ID));
                        } catch (e) {}
                        dt.ajax.reload(null, false);
                    }
                }

                syncSessionTimetableExportLinks();

                $('#filter_course,#filter_faculty,#filter_faculty_type,#filter_venue,#filter_date_from,#filter_date_to')
                    .on('change', function() {
                        syncSessionTimetableExportLinks();
                        reloadDt();
                    });
                $('#filter_subject_topic,#filter_subject_module').on('input', function() {
                    syncSessionTimetableExportLinks();
                    debouncedText();
                });

                $('input[name="str_course_type"]').on('change', function() {
                    reloadCourseOptionsThenTable(dt);
                });

                $('#strClearBtn').on('click', function() {
                    $('#filter_course').val('');
                    $('#filter_faculty').val('');
                    $('#filter_faculty_type').val('');
                    $('#filter_venue').val('');
                    $('#filter_date_from').val('');
                    $('#filter_date_to').val('');
                    $('#filter_subject_topic').val('');
                    $('#filter_subject_module').val('');
                    ['filter_course', 'filter_faculty', 'filter_faculty_type', 'filter_venue'].forEach(function(id) {
                        var el = document.getElementById(id);
                        if (el && el._choicesBs) {
                            el._choicesBs.setChoiceByValue('');
                        }
                    });
                    reloadDt();
                });

                try {
                    var u = new URL(window.location.href);
                    u.searchParams.set('course_type', strCourseType());
                    window.history.replaceState({}, '', u);
                } catch (e) {}
            });
        })();
    </script>
@endpush
