@php
    $pr = $pendingReportRoutes ?? \App\Support\FeedbackReportRouteRegistry::pendingForRequest();
    $pageTitle = $pendingPageTitle ?? 'Pending Feedback – Students';
@endphp
@extends('admin.layouts.master')

@section('title', $pageTitle . ' - Sargam | Lal Bahadur')

@section('setup_content')
    <div class="container-fluid pending-feedback-page psf-master-page py-3 px-3 px-lg-4">
        <x-breadcrum :title="$pageTitle"></x-breadcrum>

        <x-session_message />

        <div id="psfListView">
        <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-between gap-3 mb-4">
            <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white shadow-sm mb-0 psf-status-tabs" role="group" aria-label="Program scope" id="courseTypeTabs">
                <li class="nav-item" role="presentation">
                    <input class="btn-check" type="radio" name="course_type_scope" id="course_scope_active" value="active" checked>
                    <label class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill mb-0" for="course_scope_active">
                        Active: <span id="activeCourseBadge">{{ count($activeCourses ?? []) }}</span>
                    </label>
                </li>
                <li class="nav-item" role="presentation">
                    <input class="btn-check" type="radio" name="course_type_scope" id="course_scope_archive" value="archive">
                    <label class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill mb-0" for="course_scope_archive">
                        Archived: <span id="archiveCourseBadge">{{ count($archiveCourses ?? []) }}</span>
                    </label>
                </li>
            </ul>

            <div class="d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
                <button type="button" id="btnPrint"
                    class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-3 py-2 rounded-1 fw-semibold shadow-sm"
                    title="Print report">
                    <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
                </button>
                <button type="button" id="exportPDF"
                    class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-3 py-2 rounded-1 fw-semibold shadow-sm"
                    title="Download PDF">
                    <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
                </button>
                <div class="dropdown">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-1 px-2 py-2" data-bs-toggle="dropdown" aria-label="More export options">
                        <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-2">
                        <li>
                            <button type="button" id="exportExcelSummary"
                                class="dropdown-item rounded-1 mx-2 py-2 d-inline-flex align-items-center border-0 bg-transparent w-100 text-start">
                                <i class="bi bi-file-earmark-spreadsheet me-2 text-primary" aria-hidden="true"></i>Not given feedback count
                            </button>
                        </li>
                        <li>
                            <button type="button" id="exportExcelDetailed"
                                class="dropdown-item rounded-1 mx-2 py-2 d-inline-flex align-items-center border-0 bg-transparent w-100 text-start">
                                <i class="bi bi-file-earmark-spreadsheet me-2 text-primary" aria-hidden="true"></i>Not given feedback details
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <fieldset class="d-none" id="courseScopeFieldset" aria-hidden="true">
            <legend class="visually-hidden">Program scope</legend>
        </fieldset>

        <div class="card psf-dt-card border-0 shadow-sm rounded-3">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3 mb-4 programme-dt-toolbar psf-filters-row w-100">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <span class="programme-dt-filters-label">Filters</span>

                        <div class="programme-dt-filter-select psf-course-filter">
                            <label for="filter_course_pk" class="visually-hidden">Courses</label>
                            <select class="form-select select2-course" id="filter_course_pk" aria-describedby="hint-filter-course">
                                <option value="">Courses</option>
                                @foreach ($courses ?? [] as $id => $name)
                                    <option value="{{ $id }}" {{ isset($activeCourse) && $activeCourse == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="programme-dt-filter-select">
                            <label for="filter_session_id" class="visually-hidden">Session</label>
                            <select class="form-select select2-session" id="filter_session_id">
                                <option value="">Session</option>
                                @foreach ($sessions ?? [] as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="programme-dt-filter-select psf-period-filter position-relative">
                            <input type="hidden" id="filter_from_date" value="" autocomplete="off">
                            <input type="hidden" id="filter_to_date" value="" autocomplete="off">
                            <label for="psf_time_period_picker" class="visually-hidden">Time Period</label>
                            <input type="text"
                                id="psf_time_period_picker"
                                class="form-control psf-time-period-input w-100"
                                placeholder="Time Period"
                                value=""
                                readonly
                                autocomplete="off"
                                aria-label="Filter by time period">
                            <i class="bi bi-chevron-down psf-filter-chevron" aria-hidden="true"></i>
                        </div>

                        <div class="programme-dt-filter-select">
                            <label for="filter_feedback_state" class="visually-hidden">Feedback</label>
                            <select class="form-select psf-filter-select" id="filter_feedback_state">
                                <option value="not_given" selected>Feedback</option>
                                <option value="given">Given</option>
                            </select>
                        </div>

                        <button type="button" class="btn programme-dt-btn-reset flex-shrink-0" id="btnResetFilters">Reset Filters</button>
                        <button type="button" class="visually-hidden" id="btnApplyFilters" tabindex="-1" aria-hidden="true">Apply filters</button>
                    </div>

                    <div class="psf-table-search ms-xl-auto flex-shrink-0">
                        <div class="dropdown psf-search-slot">
                            <button type="button"
                                class="btn psf-search-trigger"
                                id="psfSearchTrigger"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false"
                                aria-label="Search students">
                                <i class="bi bi-search" aria-hidden="true"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 p-3 psf-table-search-menu">
                                <label for="studentSearch" class="form-label small text-secondary mb-2">Search</label>
                                <div class="input-group">
                                    <input type="search" class="form-control shadow-none" id="studentSearch"
                                        placeholder="Name, email, or OT code…" autocomplete="off" enterkeyhint="search">
                                    <button class="btn btn-outline-secondary" type="button" id="clearSearch"
                                        title="Clear search" aria-label="Clear search" style="display:none">
                                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="programme-dt-panel psf-table-panel">
                    <div id="studentAccordionContainer">
                        <div class="text-center py-5 px-3 psf-loading-state">
                            <div class="spinner-border text-primary" role="status" aria-label="Loading">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-3 mb-0 text-secondary small fw-medium">Loading student data…</p>
                        </div>
                    </div>
                    <div id="paginationContainer"></div>
                </div>
            </div>
        </div>
        </div>

        <div id="psfDetailView" class="d-none" aria-hidden="true">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
                <div class="d-flex align-items-center gap-2 gap-md-3 min-w-0">
                    <button type="button" class="btn btn-link text-body p-0 psf-back-btn flex-shrink-0" id="psfBackToList" aria-label="Back to student list">
                        <i class="bi bi-arrow-left fs-4" aria-hidden="true"></i>
                    </button>
                    <h1 class="h4 fw-bold text-body mb-0 text-truncate" id="psfDetailTitle">Student Feedback Status</h1>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <button type="button" id="btnPrintDetail"
                        class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-3 py-2 rounded-1 fw-semibold shadow-sm"
                        title="Print report">
                        <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
                    </button>
                    <button type="button" id="exportPDFDetail"
                        class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-3 py-2 rounded-1 fw-semibold shadow-sm"
                        title="Download PDF">
                        <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
                    </button>
                </div>
            </div>

            <div class="card psf-dt-card border-0 shadow-sm rounded-3">
                <div class="card-body p-3 p-md-4">
                    <div class="programme-dt-panel psf-detail-table-panel">
                        <div id="psfDetailTableContainer"></div>
                        <div id="psfDetailPagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        $(document).ready(function() {
            // CSRF Setup
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            // Utility: escape HTML to prevent XSS
            function escapeHtml(text) {
                if (!text) return '—';
                var div = document.createElement('div');
                div.appendChild(document.createTextNode(text));
                return div.innerHTML;
            }

            function destroySelect2IfAny($el) {
                if ($el && $el.length && $el.data('select2')) {
                    $el.select2('destroy');
                }
            }

            // Initialize Select2 for Course filter (safe if already initialized)
            function initCourseSelect2() {
                var $el = $('#filter_course_pk');
                destroySelect2IfAny($el);
                $el.select2({
                    placeholder: "Courses",
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('body'),
                    dropdownAutoWidth: false,
                    language: {
                        noResults: function() {
                            return "No courses found";
                        }
                    }
                });
            }

            // Initialize Select2 for Session filter
            function initSessionSelect2() {
                var $el = $('#filter_session_id');
                destroySelect2IfAny($el);
                $el.select2({
                    placeholder: "Session",
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('body'),
                    dropdownAutoWidth: false,
                    language: {
                        noResults: function() {
                            return "No sessions found";
                        }
                    }
                });
            }

            // Snapshot server-rendered options before Select2 wraps the selects
            var originalSessions = $('#filter_session_id').html();
            var originalCourses = $('#filter_course_pk').html();

            initCourseSelect2();
            initSessionSelect2();

            var psfTimePeriodPicker = null;
            if (typeof flatpickr !== 'undefined') {
                psfTimePeriodPicker = flatpickr('#psf_time_period_picker', {
                    mode: 'range',
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/m/Y',
                    showMonths: 2,
                    locale: { rangeSeparator: ' - ' },
                    onChange: function(selectedDates) {
                        if (selectedDates.length === 2) {
                            $('#filter_from_date').val(psfTimePeriodPicker.formatDate(selectedDates[0], 'Y-m-d'));
                            $('#filter_to_date').val(psfTimePeriodPicker.formatDate(selectedDates[1], 'Y-m-d'));
                            clearTimeout(dateFilterTimeout);
                            dateFilterTimeout = setTimeout(function() {
                                loadGroupedData();
                            }, 500);
                        } else if (selectedDates.length === 0) {
                            $('#filter_from_date, #filter_to_date').val('');
                            clearTimeout(dateFilterTimeout);
                            dateFilterTimeout = setTimeout(function() {
                                loadGroupedData();
                            }, 500);
                        }
                    }
                });
            }

            var searchTrigger = document.getElementById('psfSearchTrigger');
            if (searchTrigger) {
                var searchDropdown = searchTrigger.closest('.dropdown');
                if (searchDropdown) {
                    searchDropdown.addEventListener('shown.bs.dropdown', function() {
                        var studentSearch = document.getElementById('studentSearch');
                        if (studentSearch) {
                            studentSearch.focus();
                        }
                    });
                }
            }

            // ── Course lists by tab ──
            var activeCoursesData = @json($activeCourses ?? []);
            var archiveCoursesData = @json($archiveCourses ?? []);
            var currentTab = 'active';

            function buildCourseOptions(courseMap, preselectPk) {
                var html = '<option value="">Courses</option>';
                $.each(courseMap, function(pk, name) {
                    var sel = (preselectPk && String(pk) === String(preselectPk)) ? ' selected' : '';
                    html += '<option value="' + pk + '"' + sel + '>' + $('<span>').text(name).html() + '</option>';
                });
                return html;
            }

            function switchCourseList(tab, preselectPk) {
                currentTab = tab;
                var courseMap = (tab === 'active') ? activeCoursesData : archiveCoursesData;
                var opts = buildCourseOptions(courseMap, preselectPk || null);
                var $course = $('#filter_course_pk');
                destroySelect2IfAny($course);
                $course.html(opts);
                initCourseSelect2();
                var pk = preselectPk != null && preselectPk !== '' ? String(preselectPk) : '';
                var toSelect = (pk && courseMap[pk] !== undefined) ? pk : '';
                $course.val(toSelect).trigger('change');
                originalCourses = $course.html();
            }

            // Active / archive radio (same behavior as former tabs)
            $('input[name="course_type_scope"]').on('change', function() {
                if (activeStudentDetail) {
                    showListView();
                }
                var tab = $(this).val() === 'archive' ? 'archive' : 'active';
                switchCourseList(tab);
                var $sess = $('#filter_session_id');
                destroySelect2IfAny($sess);
                $sess.html('<option value="">Session</option>');
                initSessionSelect2();
                $sess.val('').trigger('change');
                originalSessions = $sess.html();
                $('#filter_from_date, #filter_to_date').val('');
                if (psfTimePeriodPicker) {
                    psfTimePeriodPicker.clear();
                }
                $('#filter_feedback_state').val('not_given');
                loadGroupedData();
            });

            // ── List & detail view state ──
            var currentPage = 1;
            var perPage = 10;
            var sortBy = 'student_name';
            var sortDir = 'asc';
            var searchTerm = '';
            var dateFilterTimeout;
            var activeStudentDetail = null;
            var detailSessionsPage = 1;
            var detailSessionsPerPage = 10;

            function buildGroupedRequestParams(extra) {
                var params = {
                    course_pk: $('#filter_course_pk').val(),
                    session_id: $('#filter_session_id').val(),
                    from_date: $('#filter_from_date').val(),
                    to_date: $('#filter_to_date').val(),
                    course_type: currentTab,
                    filter_feedback_state: $('#filter_feedback_state').val(),
                    page: currentPage,
                    per_page: perPage,
                    sort_by: sortBy,
                    sort_dir: sortDir,
                    search: searchTerm
                };
                if (extra) {
                    $.extend(params, extra);
                }
                return params;
            }

            function loadGroupedData(page, options) {
                options = options || {};
                page = page || 1;
                currentPage = page;

                var params = buildGroupedRequestParams({
                    page: page,
                    per_page: options.per_page || perPage
                });
                if (options.student_pk) {
                    params.student_pk = options.student_pk;
                }

                if (!options.student_pk_only) {
                    $('#studentAccordionContainer').html(
                        '<div class="text-center py-5 px-3 psf-loading-state">' +
                        '<div class="spinner-border text-primary" role="status" aria-label="Loading"><span class="visually-hidden">Loading...</span></div>' +
                        '<p class="mt-3 mb-0 text-secondary small fw-medium">Loading student data…</p></div>'
                    );
                }

                $.ajax({
                    url: "{{ $pr['grouped'] }}",
                    type: "GET",
                    data: params,
                    dataType: 'json',
                    success: function(response) {
                        if (options.student_pk_only) {
                            if (response.students && response.students.length) {
                                openStudentDetail(response.students[0], { pushState: options.pushState !== false });
                            }
                            return;
                        }
                        window._psfLastListStudents = response.students || [];
                        renderStudentList(response);
                        renderPagination(response);
                        if (options.afterLoad) {
                            options.afterLoad(response);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Grouped data error:', error);
                        $('#studentAccordionContainer').html(
                            '<div class="state-empty rounded-4 border border-danger border-opacity-25 bg-danger bg-opacity-10 p-4 p-md-5">' +
                            '<i class="bi bi-exclamation-triangle d-block mb-3 text-danger state-empty-icon"></i>' +
                            '<p class="fw-semibold text-body mb-1">Failed to load data</p>' +
                            '<p class="text-body-secondary small mb-0">Please try again or adjust your filters.</p></div>'
                        );
                    }
                });
            }

            function renderStudentList(data) {
                if (!data.students || data.students.length === 0) {
                    $('#studentAccordionContainer').html(
                        '<div class="state-empty rounded-4 border border-secondary border-opacity-25 bg-body-secondary bg-opacity-25 p-4 p-md-5">' +
                        '<i class="bi bi-search d-block mb-3 text-secondary state-empty-icon"></i>' +
                        '<p class="fw-semibold text-body mb-1">No records found</p>' +
                        '<p class="text-body-secondary small mb-0">Try adjusting your filters or search.</p></div>'
                    );
                    return;
                }

                var html = '<div class="table-responsive"><table class="table table-hover student-table programme-dt-table align-middle mb-0 w-100">';
                html += '<thead><tr>';
                html += '<th scope="col" class="psf-col-sno text-center">S. No.</th>';
                html += buildSortHeader('Student Name', 'student_name');
                html += buildSortHeader('Course', 'course_summary');
                html += buildSortHeader('Feedback Given', 'feedback_given', true);
                html += buildSortHeader('Feedback Not Given', 'feedback_not_given', true);
                html += '<th scope="col" class="text-center">Action</th>';
                html += '</tr></thead><tbody>';

                var startIndex = ((data.page || 1) - 1) * (data.per_page || perPage);

                $.each(data.students, function(index, student) {
                    var globalIndex = startIndex + index;
                    var studentPk = student.student_pk || '';
                    var nameHtml = '<a href="#" class="psf-student-link" data-student-pk="' + studentPk + '">' +
                        escapeHtml(student.student_name) + '</a>';

                    html += '<tr class="psf-student-row">';
                    html += '<td class="text-center text-secondary psf-col-sno">' + (globalIndex + 1) + '</td>';
                    html += '<td>' + nameHtml;
                    if (student.email) {
                        html += '<br><small class="text-secondary">' + escapeHtml(student.email) + '</small>';
                    }
                    html += '</td>';
                    html += '<td class="psf-col-course">' + escapeHtml(student.course_summary || '—') + '</td>';
                    html += '<td class="text-center"><span class="psf-count psf-count-given">' + student.feedback_given + '</span></td>';
                    html += '<td class="text-center"><span class="psf-count psf-count-pending">' + student.feedback_not_given + '</span></td>';
                    html += '<td class="text-center">';
                    html += '<button type="button" class="btn btn-link p-0 border-0 psf-view-student" data-student-pk="' + studentPk + '" aria-label="View feedback status">';
                    html += '<i class="bi bi-eye psf-row-action" aria-hidden="true"></i></button>';
                    html += '</td>';
                    html += '</tr>';
                });

                html += '</tbody></table></div>';
                $('#studentAccordionContainer').html(html);
            }

            function showListView() {
                $('#psfDetailView').addClass('d-none').attr('aria-hidden', 'true');
                $('#psfListView').removeClass('d-none');
                activeStudentDetail = null;
                var url = new URL(window.location.href);
                url.searchParams.delete('student_pk');
                window.history.replaceState({ view: 'list' }, '', url.pathname + url.search);
            }

            function showDetailView() {
                $('#psfListView').addClass('d-none');
                $('#psfDetailView').removeClass('d-none').attr('aria-hidden', 'false');
            }

            function openStudentDetail(student, options) {
                options = options || {};
                if (!student) {
                    return;
                }
                activeStudentDetail = student;
                detailSessionsPage = 1;
                var displayName = student.student_name || 'Student';
                $('#psfDetailTitle').text(displayName + "'s Feedback Database");
                showDetailView();
                renderStudentDetailTable();

                if (options.pushState !== false && student.student_pk) {
                    var url = new URL(window.location.href);
                    url.searchParams.set('student_pk', student.student_pk);
                    window.history.pushState({ view: 'detail', student_pk: student.student_pk }, '', url.pathname + url.search);
                }
            }

            function renderStudentDetailTable() {
                if (!activeStudentDetail) {
                    return;
                }
                var sessions = activeStudentDetail.sessions || [];
                if (!sessions.length) {
                    $('#psfDetailTableContainer').html(
                        '<div class="state-empty text-center py-5 px-3">' +
                        '<i class="bi bi-inbox d-block mb-3 text-secondary state-empty-icon"></i>' +
                        '<p class="fw-semibold text-body mb-1">No session records found</p>' +
                        '<p class="text-body-secondary small mb-0">Try adjusting your filters.</p></div>'
                    );
                    $('#psfDetailPagination').html('');
                    return;
                }

                var total = sessions.length;
                var totalPages = Math.max(1, Math.ceil(total / detailSessionsPerPage));
                if (detailSessionsPage > totalPages) {
                    detailSessionsPage = totalPages;
                }
                var start = (detailSessionsPage - 1) * detailSessionsPerPage;
                var pageSessions = sessions.slice(start, start + detailSessionsPerPage);

                var html = '<div class="table-responsive"><table class="table table-hover psf-detail-table programme-dt-table align-middle mb-0 w-100">';
                html += '<thead><tr>';
                html += '<th scope="col" class="psf-col-sno text-center">S. No.</th>';
                html += '<th scope="col">Session Name</th>';
                html += '<th scope="col">Date</th>';
                html += '<th scope="col">Time</th>';
                html += '<th scope="col" class="text-center">Feedback Status</th>';
                html += '</tr></thead><tbody>';

                $.each(pageSessions, function(index, session) {
                    var sno = start + index + 1;
                    html += '<tr>';
                    html += '<td class="text-center text-secondary psf-col-sno">' + sno + '</td>';
                    html += '<td>' + escapeHtml(session.session_name) + '</td>';
                    html += '<td>' + escapeHtml(session.date) + '</td>';
                    html += '<td>' + escapeHtml(session.time) + '</td>';
                    if (session.feedback_status === 'given') {
                        html += '<td class="text-center"><span class="badge rounded-pill bg-success-subtle text-success badge-status">Given</span></td>';
                    } else {
                        html += '<td class="text-center"><span class="badge rounded-pill bg-danger-subtle text-danger badge-status">Not Given</span></td>';
                    }
                    html += '</tr>';
                });

                html += '</tbody></table></div>';
                $('#psfDetailTableContainer').html(html);
                renderDetailPagination(total, totalPages);
            }

            function renderDetailPagination(total, totalPages) {
                if (!total) {
                    $('#psfDetailPagination').html('');
                    return;
                }

                var page = detailSessionsPage;
                var html = '<div class="psf-table-footer programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 pt-3 border-top">';

                if (totalPages > 1) {
                    html += '<nav aria-label="Session pagination" class="psf-pagination-wrap"><ul class="pagination psf-pagination mb-0">';
                    html += '<li class="page-item' + (page <= 1 ? ' disabled' : '') + '">';
                    html += '<a class="page-link" href="#" data-detail-page="' + (page - 1) + '" aria-label="Previous"><i class="bi bi-chevron-left"></i></a></li>';

                    var startP = Math.max(1, page - 2);
                    var endP = Math.min(totalPages, page + 2);

                    if (startP > 1) {
                        html += '<li class="page-item"><a class="page-link" href="#" data-detail-page="1">1</a></li>';
                        if (startP > 2) {
                            html += '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
                        }
                    }

                    for (var p = startP; p <= endP; p++) {
                        html += '<li class="page-item' + (p === page ? ' active' : '') + '">';
                        html += '<a class="page-link" href="#" data-detail-page="' + p + '">' + p + '</a></li>';
                    }

                    if (endP < totalPages) {
                        if (endP < totalPages - 1) {
                            html += '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
                        }
                        html += '<li class="page-item"><a class="page-link" href="#" data-detail-page="' + totalPages + '">' + totalPages + '</a></li>';
                    }

                    html += '<li class="page-item' + (page >= totalPages ? ' disabled' : '') + '">';
                    html += '<a class="page-link" href="#" data-detail-page="' + (page + 1) + '" aria-label="Next"><i class="bi bi-chevron-right"></i></a></li>';
                    html += '</ul></nav>';
                } else {
                    html += '<div></div>';
                }

                html += '<div class="psf-records-info programme-dt-count text-secondary small d-flex align-items-center gap-2 flex-wrap justify-content-end">';
                html += '<span>Showing</span>';
                html += '<select class="form-select form-select-sm psf-page-size-select shadow-none" id="psfDetailPerPageSelect" aria-label="Sessions per page">';
                [50, 100, 200].forEach(function(v) {
                    html += '<option value="' + v + '"' + (v === detailSessionsPerPage ? ' selected' : '') + '>' + v + '</option>';
                });
                html += '</select>';
                html += '<span>of <strong class="text-body">' + total.toLocaleString() + '</strong> items</span>';
                html += '</div></div>';

                $('#psfDetailPagination').html(html);
            }

            function loadStudentDetailOnly(studentPk, pushState) {
                loadGroupedData(1, {
                    student_pk: studentPk,
                    student_pk_only: true,
                    per_page: 1,
                    pushState: pushState
                });
            }

            function openStudentDetailFromRow(studentPk) {
                var pk = parseInt(studentPk, 10);
                if (!pk) {
                    return;
                }
                var cached = null;
                if (window._psfLastListStudents) {
                    cached = window._psfLastListStudents.find(function(s) {
                        return parseInt(s.student_pk, 10) === pk;
                    });
                }
                if (cached) {
                    openStudentDetail(cached);
                } else {
                    loadStudentDetailOnly(pk, true);
                }
            }

            // ── Sort helpers ──
            function buildSortHeader(label, field, center) {
                var cls = center ? 'text-center' : '';
                var icon = 'bi-arrow-down-up';
                var activeClass = '';
                if (sortBy === field) {
                    icon = sortDir === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down';
                    activeClass = ' sort-active';
                }
                return '<th scope="col" class="sortable-header ' + cls + activeClass + '" data-sort="' + field + '">' +
                    label + ' <i class="bi ' + icon + ' sort-icon" aria-hidden="true"></i></th>';
            }

            // Click handler for sortable headers (delegated, list view only)
            $(document).on('click', '#studentAccordionContainer .sortable-header', function() {
                var field = $(this).data('sort');
                if (sortBy === field) {
                    sortDir = sortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    sortBy = field;
                    sortDir = 'asc';
                }
                loadGroupedData(1);
            });

            $(document).on('click', '.psf-student-link, .psf-view-student', function(e) {
                e.preventDefault();
                e.stopPropagation();
                openStudentDetailFromRow($(this).data('student-pk'));
            });

            $('#psfBackToList').on('click', function(e) {
                e.preventDefault();
                showListView();
            });

            window.addEventListener('popstate', function() {
                var url = new URL(window.location.href);
                var pk = url.searchParams.get('student_pk');
                if (pk) {
                    loadStudentDetailOnly(pk, false);
                } else {
                    showListView();
                }
            });

            $(document).on('click', '#psfDetailPagination .page-link[data-detail-page]', function(e) {
                e.preventDefault();
                var pg = parseInt($(this).data('detail-page'), 10);
                var total = (activeStudentDetail && activeStudentDetail.sessions) ? activeStudentDetail.sessions.length : 0;
                var totalPages = Math.max(1, Math.ceil(total / detailSessionsPerPage));
                if (pg >= 1 && pg <= totalPages && pg !== detailSessionsPage) {
                    detailSessionsPage = pg;
                    renderStudentDetailTable();
                }
            });

            $(document).on('change', '#psfDetailPerPageSelect', function() {
                detailSessionsPerPage = parseInt($(this).val(), 10) || 10;
                detailSessionsPage = 1;
                renderStudentDetailTable();
            });

            // ── Search ──
            var searchTimeout;
            $('#studentSearch').on('input', function() {
                var val = $(this).val().trim();
                $('#clearSearch').toggle(val.length > 0);
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    searchTerm = val;
                    loadGroupedData(1);
                }, 400);
            });

            $('#clearSearch').on('click', function() {
                $('#studentSearch').val('').trigger('input');
            });

            function renderPagination(data) {
                var totalPages = data.total_pages || 1;
                var page = data.page || 1;
                var total = data.total || 0;
                var pp = data.per_page || 10;

                if (!total) {
                    $('#paginationContainer').html('');
                    return;
                }

                var html = '<div class="psf-table-footer programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 pt-3 border-top">';

                if (totalPages > 1) {
                    html += '<nav aria-label="Pagination" class="psf-pagination-wrap"><ul class="pagination psf-pagination mb-0">';
                    html += '<li class="page-item' + (page <= 1 ? ' disabled' : '') + '">';
                    html += '<a class="page-link" href="#" data-page="' + (page - 1) + '" aria-label="Previous"><i class="bi bi-chevron-left"></i></a></li>';

                    var startP = Math.max(1, page - 2);
                    var endP = Math.min(totalPages, page + 2);

                    if (startP > 1) {
                        html += '<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>';
                        if (startP > 2) {
                            html += '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
                        }
                    }

                    for (var p = startP; p <= endP; p++) {
                        html += '<li class="page-item' + (p === page ? ' active' : '') + '">';
                        html += '<a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
                    }

                    if (endP < totalPages) {
                        if (endP < totalPages - 1) {
                            html += '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
                        }
                        html += '<li class="page-item"><a class="page-link" href="#" data-page="' + totalPages + '">' + totalPages + '</a></li>';
                    }

                    html += '<li class="page-item' + (page >= totalPages ? ' disabled' : '') + '">';
                    html += '<a class="page-link" href="#" data-page="' + (page + 1) + '" aria-label="Next"><i class="bi bi-chevron-right"></i></a></li>';
                    html += '</ul></nav>';
                } else {
                    html += '<div></div>';
                }

                html += '<div class="psf-records-info programme-dt-count text-secondary small d-flex align-items-center gap-2 flex-wrap justify-content-end">';
                html += '<span>Showing</span>';
                html += '<select class="form-select form-select-sm psf-page-size-select shadow-none" id="perPageSelect" aria-label="Items per page">';
                [10, 20, 50, 100, 200].forEach(function(v) {
                    html += '<option value="' + v + '"' + (v === pp ? ' selected' : '') + '>' + v + '</option>';
                });
                html += '</select>';
                html += '<span>of <strong class="text-body">' + total.toLocaleString() + '</strong> items</span>';
                html += '</div></div>';

                $('#paginationContainer').html(html);

                // Page click
                $('#paginationContainer').off('click', '.page-link').on('click', '.page-link', function(e) {
                    e.preventDefault();
                    var pg = $(this).data('page');
                    if (pg && pg >= 1 && pg <= totalPages && pg !== page) {
                        loadGroupedData(pg);
                        $('html, body').animate({ scrollTop: $('#studentAccordionContainer').offset().top - 80 }, 200);
                    }
                });

                // Per-page change
                $('#paginationContainer').off('change', '#perPageSelect').on('change', '#perPageSelect', function() {
                    perPage = parseInt($(this).val());
                    loadGroupedData(1);
                });
            }

            // ── Filter Handlers ──

            // Apply filters button
            $('#btnApplyFilters').on('click', function() {
                loadGroupedData();
            });

            // Reset filters button
            $('#btnResetFilters').on('click', function() {
                if (activeStudentDetail) {
                    showListView();
                }
                var $course = $('#filter_course_pk');
                destroySelect2IfAny($course);
                $course.html(originalCourses);
                initCourseSelect2();
                $course.val('').trigger('change');

                var $sess = $('#filter_session_id');
                destroySelect2IfAny($sess);
                $sess.html(originalSessions);
                initSessionSelect2();
                $sess.val('').trigger('change');

                $('#filter_from_date, #filter_to_date').val('');
                if (psfTimePeriodPicker) {
                    psfTimePeriodPicker.clear();
                }
                $('#filter_feedback_state').val('not_given');
                $('#studentSearch').val('');
                searchTerm = '';

                loadGroupedData();
            });

            // Course change handler - Load sessions for selected course & reload data
            $('#filter_course_pk').on('change', function() {
                var courseId = $(this).val();
                var $sessionSelect = $('#filter_session_id');

                if (!courseId) {
                    destroySelect2IfAny($sessionSelect);
                    $sessionSelect.html(originalSessions);
                    initSessionSelect2();
                    $sessionSelect.val('').trigger('change');
                    loadGroupedData();
                    return;
                }

                destroySelect2IfAny($sessionSelect);
                $sessionSelect.html('<option value="">Loading sessions...</option>');
                initSessionSelect2();
                $sessionSelect.val('').trigger('change');

                $.ajax({
                    url: "{{ $pr['sessions_by_course'] }}",
                    type: "GET",
                    data: { course_pk: courseId },
                    dataType: 'json',
                    success: function(response) {
                        var options = '<option value="">Session</option>';
                        if (response && response.length > 0) {
                            $.each(response, function(index, session) {
                                var label = session.subject_topic;
                                if (session.START_DATE) {
                                    label += ' (' + session.START_DATE + ')';
                                }
                                options += '<option value="' + session.pk + '">' + $('<span>').text(label).html() + '</option>';
                            });
                        } else {
                            options = '<option value="">No sessions found</option>';
                        }
                        destroySelect2IfAny($sessionSelect);
                        $sessionSelect.html(options);
                        initSessionSelect2();
                        $sessionSelect.val('').trigger('change');
                        loadGroupedData();
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        destroySelect2IfAny($sessionSelect);
                        $sessionSelect.html('<option value="">Error loading sessions</option>');
                        initSessionSelect2();
                        $sessionSelect.val('').trigger('change');
                        loadGroupedData();
                    }
                });
            });

            // Auto-reload on session change
            $('#filter_session_id').on('change', function() {
                loadGroupedData();
            });

            // Auto-reload on date changes (debounced; flatpickr also updates hidden fields)
            $('#filter_from_date, #filter_to_date').on('change', function() {
                clearTimeout(dateFilterTimeout);
                dateFilterTimeout = setTimeout(function() {
                    loadGroupedData();
                }, 500);
            });

            $('#filter_feedback_state').on('change', function() {
                loadGroupedData(1);
            });

            // ── Export Handlers ──

            function buildExportQueryParams() {
                var params = {
                    course_pk: $('#filter_course_pk').val(),
                    session_id: $('#filter_session_id').val(),
                    from_date: $('#filter_from_date').val(),
                    to_date: $('#filter_to_date').val(),
                    course_type: currentTab,
                    filter_feedback_state: $('#filter_feedback_state').val()
                };
                if (activeStudentDetail && activeStudentDetail.student_pk) {
                    params.student_pk = activeStudentDetail.student_pk;
                }
                return params;
            }

            function submitExportForm(actionUrl) {
                var form = $('<form>', {
                    method: 'POST',
                    action: actionUrl,
                    style: 'display: none'
                });

                $('<input>').attr({ type: 'hidden', name: '_token', value: "{{ csrf_token() }}" }).appendTo(form);

                var filters = buildExportQueryParams();

                $.each(filters, function(key, value) {
                    if (value !== undefined && value !== null && value !== '') {
                        $('<input>').attr({ type: 'hidden', name: key, value: value }).appendTo(form);
                    }
                });

                $('body').append(form);
                form.submit();
                setTimeout(function() { form.remove(); }, 1000);
            }

            $('#exportPDF').on('click', function(e) {
                e.preventDefault();
                submitExportForm("{{ $pr['export_pdf'] }}");
            });

            $('#exportExcelSummary').on('click', function(e) {
                e.preventDefault();
                submitExportForm("{{ $pr['export_excel'] }}");
            });

            $('#exportExcelDetailed').on('click', function(e) {
                e.preventDefault();
                submitExportForm("{{ $pr['export_excel_detailed'] }}");
            });

            $('#btnPrint').on('click', function(e) {
                e.preventDefault();
                window.open("{{ $pr['print'] }}?" + $.param(buildExportQueryParams()), '_blank');
            });

            $('#btnPrintDetail').on('click', function(e) {
                e.preventDefault();
                window.open("{{ $pr['print'] }}?" + $.param(buildExportQueryParams()), '_blank');
            });

            $('#exportPDFDetail').on('click', function(e) {
                e.preventDefault();
                submitExportForm("{{ $pr['export_pdf'] }}");
            });

            // ── Initial Load ──
            window._psfPendingDeepLink = new URL(window.location.href).searchParams.get('student_pk');

            var activeCourseId = '{{ $activeCourse ?? '' }}';
            switchCourseList('active', activeCourseId);

            if (window._psfPendingDeepLink) {
                loadStudentDetailOnly(window._psfPendingDeepLink, false);
            }

            if (!activeCourseId) {
                loadGroupedData();
            }
        });
    </script>
@endpush
