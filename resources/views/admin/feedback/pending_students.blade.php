@extends('admin.layouts.master')

@section('title', 'Pending Feedback - Students')

@section('setup_content')
    <div class="container-fluid px-2 px-sm-3 px-md-4">
        <x-breadcrum title="Pending Feedback – Students"></x-breadcrum>

        <x-session_message />

        <!-- Active / Archive Tabs -->
        <ul class="nav nav-pills nav-fill gap-2 mb-4 p-1 bg-body-tertiary rounded-3" id="courseTypeTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-2 d-inline-flex align-items-center justify-content-center gap-2 px-4 py-2" id="tab-active" data-bs-toggle="tab"
                    data-bs-target="#pane-active" type="button" role="tab" aria-controls="pane-active" aria-selected="true">
                    <i class="material-symbols-rounded" style="font-size:1.15rem">school</i>
                    Active Courses
                    <span class="badge rounded-pill bg-white text-primary shadow-sm" id="activeCourseBadge">{{ count($activeCourses ?? []) }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-2 d-inline-flex align-items-center justify-content-center gap-2 px-4 py-2" id="tab-archive" data-bs-toggle="tab"
                    data-bs-target="#pane-archive" type="button" role="tab" aria-controls="pane-archive" aria-selected="false">
                    <i class="material-symbols-rounded" style="font-size:1.15rem">inventory_2</i>
                    Archive Courses
                    <span class="badge rounded-pill bg-body-secondary text-body" id="archiveCourseBadge">{{ count($archiveCourses ?? []) }}</span>
                </button>
            </li>
        </ul>

        <!-- Filters Card -->
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-body-tertiary border-0 rounded-top-4 py-3 px-4">
                <h2 class="h6 fw-semibold text-body mb-0 d-flex align-items-center gap-2">
                    <i class="material-symbols-rounded fs-5 text-primary">filter_list</i>
                    Filters
                </h2>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 g-md-4 align-items-end">
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <label for="filter_course_pk" class="form-label fw-medium">Course</label>
                        <select class="form-select select2-course" id="filter_course_pk">
                            <option value="">— All Courses —</option>
                            @foreach ($courses ?? [] as $id => $name)
                                <option value="{{ $id }}" {{ isset($activeCourse) && $activeCourse == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <label for="filter_session_id" class="form-label fw-medium">Session</label>
                        <select class="form-select select2-session" id="filter_session_id">
                            <option value="">— All Sessions —</option>
                            @foreach ($sessions ?? [] as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                        <label for="filter_from_date" class="form-label fw-medium">From Date</label>
                        <input type="date" class="form-control" id="filter_from_date">
                    </div>

                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                        <label for="filter_to_date" class="form-label fw-medium">To Date</label>
                        <input type="date" class="form-control" id="filter_to_date">
                    </div>

                    <div class="col-12 col-sm-12 col-md-12 col-lg-2 d-flex flex-wrap gap-2 align-items-end">
                        <button type="button" id="btnApplyFilters"
                            class="btn btn-primary d-inline-flex align-items-center gap-1 rounded-pill px-3">
                            <i class="material-symbols-rounded" style="font-size:1rem">search</i>
                            Apply
                        </button>
                        <button type="button" id="btnResetFilters"
                            class="btn btn-outline-secondary d-inline-flex align-items-center gap-1 rounded-pill px-3">
                            <i class="material-symbols-rounded" style="font-size:1rem">refresh</i>
                            Reset
                        </button>
                    </div>
                </div>
                <!-- Total Records + Export Actions -->
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mt-4 pt-3 border-top">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-body-secondary fw-medium small">Total Students:</span>
                        <span id="totalRecordsCount" class="badge rounded-pill bg-primary fs-6 px-3">0</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" id="exportPDF" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1 rounded-pill px-3">
                            <i class="material-symbols-rounded" style="font-size:1rem">picture_as_pdf</i>
                            PDF
                        </button>
                        <button type="button" id="exportExcel"
                            class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-1 rounded-pill px-3">
                            <i class="material-symbols-rounded" style="font-size:1rem">table_view</i>
                            Excel
                        </button>
                        <button type="button" id="btnPrint"
                            class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1 rounded-pill px-3">
                            <i class="material-symbols-rounded" style="font-size:1rem">print</i>
                            Print
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Grouped Accordion Card -->
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-body-tertiary border-0 rounded-top-4 py-3 px-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <div>
                        <h2 class="h5 fw-bold text-body mb-1 d-flex align-items-center gap-2">
                            <i class="material-symbols-rounded text-primary" style="font-size:1.4rem">assignment_late</i>
                            Pending Feedback
                        </h2>
                        <p class="text-body-secondary small mb-0">Click on a student row to expand session-level details.</p>
                    </div>
                    <div class="flex-shrink-0" style="min-width:260px; max-width:320px; width:100%">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-body-tertiary">
                                <i class="material-symbols-rounded" style="font-size:1.15rem">search</i>
                            </span>
                            <input type="text" class="form-control border-start-0 ps-0" id="studentSearch"
                                placeholder="Search by name, email or OT code..." autocomplete="off">
                            <button class="btn btn-light border" type="button" id="clearSearch"
                                title="Clear search" style="display:none">
                                <i class="material-symbols-rounded" style="font-size:1rem">close</i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="studentAccordionContainer" class="px-3 px-lg-4 py-3">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-body-secondary small">Loading student data...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />

    <style>
        /* ── Select2 ── */
        .select2-container { width: 100% !important; display: block !important; }
        .select2-container--open { z-index: 9999 !important; }
        .select2-dropdown { z-index: 9999 !important; max-height: 300px; overflow-y: auto; }

        .select2-container--default .select2-selection--single {
            height: 38px;
            padding: 0.375rem 0.75rem;
            border: 1px solid var(--bs-border-color);
            border-radius: var(--bs-border-radius);
            background-color: var(--bs-body-bg);
            font-size: 0.875rem;
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        }

        .select2-container--default .select2-selection--single:hover {
            border-color: var(--bs-primary);
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.5; color: var(--bs-body-color); padding-left: 0;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px; right: 8px;
        }

        /* ── Layout ── */
        .card, .card-body, .card-header { overflow: visible !important; }

        .table-responsive {
            overflow-x: auto !important;
            overflow-y: visible !important;
        }

        @media (max-width: 768px) {
            .table-responsive { -webkit-overflow-scrolling: touch; }
        }

        /* ── Forms ── */
        .form-label { font-size: 0.8rem; font-weight: 500; margin-bottom: 0.3rem; color: var(--bs-secondary-color); text-transform: uppercase; letter-spacing: 0.03em; }
        .form-select, .form-control { font-size: 0.875rem; height: 38px; }

        /* ── Student Table ── */
        .student-table { font-size: 0.875rem; margin-bottom: 0; }

        .student-table thead th {
            font-weight: 600;
            border-bottom: 2px solid var(--bs-primary-border-subtle);
            white-space: nowrap;
            padding: 0.65rem 0.75rem;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--bs-primary-color);
        }

        .student-table tbody td {
            vertical-align: middle;
            padding: 0.7rem 0.75rem;
            font-size: 0.875rem;
            border-color: var(--bs-border-color-translucent);
        }

        /* ── Accordion Rows ── */
        .accordion-toggle {
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .accordion-toggle:hover {
            background-color: rgba(var(--bs-primary-rgb), 0.04) !important;
        }

        .accordion-toggle .accordion-icon {
            transition: transform 0.3s cubic-bezier(.4,0,.2,1);
            font-size: 1.3rem;
            color: var(--bs-secondary-color);
        }

        .accordion-toggle.expanded {
            background-color: rgba(var(--bs-primary-rgb), 0.06) !important;
        }

        .accordion-toggle.expanded .accordion-icon {
            transform: rotate(180deg);
            color: var(--bs-primary);
        }

        /* ── Session Detail (Collapsed Panel) ── */
        .session-detail-table { font-size: 0.8125rem; }

        .session-detail-table thead th {
            background-color: var(--bs-tertiary-bg);
            font-weight: 600;
            padding: 0.5rem 0.75rem;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--bs-secondary-color);
        }

        .session-detail-table tbody td { padding: 0.5rem 0.75rem; }

        .collapse-cell { padding: 0 !important; border-top: 0 !important; }

        .collapse-inner {
            padding: 0.875rem 1.25rem;
            background: linear-gradient(180deg, #f8f9fb 0%, #fff 100%);
            border-top: 1px dashed var(--bs-border-color);
            border-left: 3px solid var(--bs-primary);
            margin-left: 1rem;
            border-radius: 0 0 0.5rem 0;
        }

        /* ── Badges (feedback counts) ── */
        .badge-count {
            min-width: 2rem;
            font-weight: 600;
            font-size: 0.78rem;
            padding: 0.3em 0.6em;
        }

        .badge-status {
            font-size: 0.72rem;
            padding: 0.35em 0.8em;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        /* ── Pagination ── */
        .pagination-wrap { padding: 0.75rem 0 0.25rem; }
        .pagination-info { font-size: 0.8rem; color: var(--bs-secondary-color); }

        .pagination .page-link {
            font-size: 0.8rem;
            padding: 0.3rem 0.65rem;
            border-radius: var(--bs-border-radius) !important;
            margin: 0 1px;
            color: var(--bs-body-color);
            border-color: transparent;
        }

        .pagination .page-link:hover { background-color: var(--bs-tertiary-bg); }

        .pagination .page-item.active .page-link {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
            color: #fff;
            box-shadow: 0 2px 6px rgba(var(--bs-primary-rgb), 0.3);
        }

        .pagination .page-item.disabled .page-link { color: var(--bs-tertiary-color); }

        /* ── Sortable Headers ── */
        .sortable-header {
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
            transition: all 0.15s ease;
            position: relative;
        }

        .sortable-header:hover { background: rgba(var(--bs-primary-rgb), 0.08) !important; }

        .sortable-header .sort-icon {
            font-size: 0.85rem;
            vertical-align: middle;
            opacity: 0.25;
            margin-left: 3px;
            transition: all 0.2s ease;
        }

        .sortable-header:hover .sort-icon { opacity: 0.6; }

        .sortable-header.sort-active .sort-icon {
            opacity: 1;
            color: var(--bs-primary);
        }

        /* ── Search ── */
        #studentSearch { font-size: 0.875rem; }
        #studentSearch:focus { box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.12); border-color: var(--bs-primary); }
        #studentSearch::placeholder { color: var(--bs-tertiary-color); }

        /* ── Pill Tabs ── */
        #courseTypeTabs { max-width: 480px; }

        #courseTypeTabs .nav-link {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--bs-secondary-color);
            border: none;
            padding: 0.5rem 1.25rem;
            transition: all 0.2s ease;
        }

        #courseTypeTabs .nav-link.active {
            font-weight: 600;
            color: #fff;
            background-color: var(--bs-primary);
            box-shadow: 0 2px 8px rgba(var(--bs-primary-rgb), 0.25);
        }

        #courseTypeTabs .nav-link:not(.active):hover {
            color: var(--bs-primary);
            background-color: rgba(var(--bs-primary-rgb), 0.06);
        }

        /* ── Empty / Loading States ── */
        .state-empty { padding: 3rem 1rem; text-align: center; }
        .state-empty .material-symbols-rounded { font-size: 2.5rem; color: var(--bs-secondary-color); opacity: 0.5; }

        /* ── Misc ── */
        .per-page-ctrl { max-width: 72px; }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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

            // Initialize Select2 for Course filter
            function initCourseSelect2() {
                $('#filter_course_pk').select2({
                    placeholder: "— All Courses —",
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
                $('#filter_session_id').select2({
                    placeholder: "— All Sessions —",
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

            // Initialize both Select2
            initCourseSelect2();
            initSessionSelect2();

            // Store original sessions for reset
            var originalSessions = $('#filter_session_id').html();
            var originalCourses = $('#filter_course_pk').html();

            // ── Course lists by tab ──
            var activeCoursesData = @json($activeCourses ?? []);
            var archiveCoursesData = @json($archiveCourses ?? []);
            var currentTab = 'active';

            function buildCourseOptions(courseMap, preselectPk) {
                var html = '<option value="">— All Courses —</option>';
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
                $('#filter_course_pk').html(opts).trigger('change.select2');
                // Update stored originals for reset
                originalCourses = opts;
            }

            // Tab switch handler
            $('#courseTypeTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                var tab = $(e.target).attr('id') === 'tab-active' ? 'active' : 'archive';
                switchCourseList(tab);
                // Reset session & dates
                $('#filter_session_id').html('<option value="">— All Sessions —</option>').val('').trigger('change.select2');
                originalSessions = $('#filter_session_id').html();
                $('#filter_from_date, #filter_to_date').val('');
                loadGroupedData();
            });

            // ── Accordion Data Loading ──
            var currentPage = 1;
            var perPage = 20;
            var sortBy = 'student_name';
            var sortDir = 'asc';
            var searchTerm = '';

            function loadGroupedData(page) {
                page = page || 1;
                currentPage = page;

                var params = {
                    course_pk: $('#filter_course_pk').val(),
                    session_id: $('#filter_session_id').val(),
                    from_date: $('#filter_from_date').val(),
                    to_date: $('#filter_to_date').val(),
                    course_type: currentTab,
                    page: page,
                    per_page: perPage,
                    sort_by: sortBy,
                    sort_dir: sortDir,
                    search: searchTerm
                };

                $('#studentAccordionContainer').html(
                    '<div class="text-center py-5">' +
                    '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>' +
                    '<p class="mt-2 text-body-secondary small">Loading student data...</p></div>'
                );

                $.ajax({
                    url: "{{ route('admin.feedback.pending.grouped') }}",
                    type: "GET",
                    data: params,
                    dataType: 'json',
                    success: function(response) {
                        renderAccordion(response);
                        updateTotalCount(response.total || 0);
                        renderPagination(response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Grouped data error:', error);
                        $('#studentAccordionContainer').html(
                            '<div class="state-empty">' +
                            '<i class="material-symbols-rounded d-block mb-2 text-danger" style="font-size:2.5rem">error_outline</i>' +
                            '<p class="fw-medium text-body-secondary mb-1">Failed to load data</p>' +
                            '<p class="text-body-tertiary small">Please try again or adjust your filters.</p></div>'
                        );
                        updateTotalCount(0);
                    }
                });
            }

            function renderAccordion(data) {
                if (!data.students || data.students.length === 0) {
                    $('#studentAccordionContainer').html(
                        '<div class="state-empty">' +
                        '<i class="material-symbols-rounded d-block mb-2">search_off</i>' +
                        '<p class="fw-medium text-body-secondary mb-1">No records found</p>' +
                        '<p class="text-body-tertiary small">Try adjusting your filters or search term.</p></div>'
                    );
                    return;
                }

                var html = '<div class="table-responsive"><table class="table student-table align-middle mb-0">';
                html += '<thead><tr>';
                html += '<th class="text-center" style="width:50px">#</th>';
                html += buildSortHeader('Student Name', 'student_name');
                html += buildSortHeader('Feedback Given', 'feedback_given', true);
                html += buildSortHeader('Feedback Not Given', 'feedback_not_given', true);
                html += '<th class="text-center" style="width:60px">Details</th>';
                html += '</tr></thead><tbody>';

                var startIndex = ((data.page || 1) - 1) * (data.per_page || 20);

                $.each(data.students, function(index, student) {
                    var globalIndex = startIndex + index;
                    var collapseId = 'studentCollapse_' + globalIndex;

                    // Main student row
                    html += '<tr class="accordion-toggle" data-bs-toggle="collapse" data-bs-target="#' + collapseId + '" aria-expanded="false" aria-controls="' + collapseId + '">';
                    html += '<td class="text-center">' + (globalIndex + 1) + '</td>';
                    html += '<td class="fw-medium">' + escapeHtml(student.student_name);
                    if (student.email) {
                        html += '<br><small class="text-body-secondary">' + escapeHtml(student.email) + '</small>';
                    }
                    html += '</td>';
                    html += '<td class="text-center"><span class="badge rounded-pill bg-success-subtle text-success badge-count">' + student.feedback_given + '</span></td>';
                    html += '<td class="text-center"><span class="badge rounded-pill bg-danger-subtle text-danger badge-count">' + student.feedback_not_given + '</span></td>';
                    html += '<td class="text-center"><i class="material-symbols-rounded accordion-icon">expand_more</i></td>';
                    html += '</tr>';

                    // Collapsible session details row
                    html += '<tr><td colspan="5" class="collapse-cell"><div class="collapse" id="' + collapseId + '">';
                    html += '<div class="collapse-inner">';
                    html += '<table class="table table-sm table-bordered session-detail-table mb-0">';
                    html += '<thead><tr>';
                    html += '<th>Session Name</th>';
                    html += '<th>Date</th>';
                    html += '<th>Time</th>';
                    html += '<th class="text-center">Feedback Status</th>';
                    html += '</tr></thead><tbody>';

                    $.each(student.sessions, function(si, session) {
                        html += '<tr>';
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

                    html += '</tbody></table></div></div></td></tr>';
                });

                html += '</tbody></table></div>';
                html += '<div id="paginationContainer"></div>';
                $('#studentAccordionContainer').html(html);

                // Toggle icon rotation on expand/collapse
                $('#studentAccordionContainer').off('show.bs.collapse hide.bs.collapse');
                $('#studentAccordionContainer').on('show.bs.collapse', '.collapse', function() {
                    $(this).closest('tr').prev('.accordion-toggle').addClass('expanded');
                });
                $('#studentAccordionContainer').on('hide.bs.collapse', '.collapse', function() {
                    $(this).closest('tr').prev('.accordion-toggle').removeClass('expanded');
                });
            }

            function updateTotalCount(total) {
                $('#totalRecordsCount').text(total.toLocaleString());
                var $badge = $('#totalRecordsCount');
                $badge.removeClass('bg-primary bg-warning bg-danger');
                if (total > 10000) {
                    $badge.addClass('bg-danger');
                } else if (total > 5000) {
                    $badge.addClass('bg-warning');
                } else {
                    $badge.addClass('bg-primary');
                }
            }

            // ── Sort helpers ──
            function buildSortHeader(label, field, center) {
                var cls = center ? 'text-center' : '';
                var icon = 'swap_vert';
                var activeClass = '';
                if (sortBy === field) {
                    icon = sortDir === 'asc' ? 'arrow_upward' : 'arrow_downward';
                    activeClass = ' sort-active';
                }
                return '<th class="sortable-header ' + cls + activeClass + '" data-sort="' + field + '">' +
                    label + ' <i class="material-symbols-rounded sort-icon">' + icon + '</i></th>';
            }

            // Click handler for sortable headers (delegated)
            $(document).on('click', '.sortable-header', function() {
                var field = $(this).data('sort');
                if (sortBy === field) {
                    sortDir = sortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    sortBy = field;
                    sortDir = 'asc';
                }
                loadGroupedData(1);
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
                var pp = data.per_page || 20;

                if (totalPages <= 1) {
                    $('#paginationContainer').html('');
                    return;
                }

                var from = ((page - 1) * pp) + 1;
                var to = Math.min(page * pp, total);

                var html = '<div class="pagination-wrap d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 mt-3 px-1">';

                // Info text
                html += '<div class="pagination-info">Showing <strong>' + from + '</strong> to <strong>' + to + '</strong> of <strong>' + total.toLocaleString() + '</strong> students</div>';

                // Per-page selector + page buttons
                html += '<div class="d-flex align-items-center gap-3">';
                html += '<div class="d-flex align-items-center gap-1"><label class="small text-body-secondary me-1">Per page:</label>';
                html += '<select class="form-select form-select-sm per-page-ctrl" id="perPageSelect">';
                [10, 20, 50, 100].forEach(function(v) {
                    html += '<option value="' + v + '"' + (v === pp ? ' selected' : '') + '>' + v + '</option>';
                });
                html += '</select></div>';

                html += '<nav aria-label="Pagination"><ul class="pagination pagination-sm mb-0">';

                // Previous
                html += '<li class="page-item' + (page <= 1 ? ' disabled' : '') + '">';
                html += '<a class="page-link" href="#" data-page="' + (page - 1) + '" aria-label="Previous">&laquo;</a></li>';

                // Page numbers with ellipsis
                var startP = Math.max(1, page - 2);
                var endP = Math.min(totalPages, page + 2);

                if (startP > 1) {
                    html += '<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>';
                    if (startP > 2) html += '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
                }

                for (var p = startP; p <= endP; p++) {
                    html += '<li class="page-item' + (p === page ? ' active' : '') + '">';
                    html += '<a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
                }

                if (endP < totalPages) {
                    if (endP < totalPages - 1) html += '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
                    html += '<li class="page-item"><a class="page-link" href="#" data-page="' + totalPages + '">' + totalPages + '</a></li>';
                }

                // Next
                html += '<li class="page-item' + (page >= totalPages ? ' disabled' : '') + '">';
                html += '<a class="page-link" href="#" data-page="' + (page + 1) + '" aria-label="Next">&raquo;</a></li>';

                html += '</ul></nav></div></div>';

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
                $('#filter_course_pk').html(originalCourses);
                $('#filter_course_pk').val('').trigger('change.select2');

                $('#filter_session_id').html(originalSessions);
                $('#filter_session_id').val('').trigger('change.select2');

                $('#filter_from_date, #filter_to_date').val('');

                loadGroupedData();
            });

            // Course change handler - Load sessions for selected course & reload data
            $('#filter_course_pk').on('change', function() {
                var courseId = $(this).val();
                var $sessionSelect = $('#filter_session_id');

                if (!courseId) {
                    $sessionSelect.html(originalSessions);
                    $sessionSelect.val('').trigger('change.select2');
                    loadGroupedData();
                    return;
                }

                $sessionSelect.html('<option value="">Loading sessions...</option>');
                $sessionSelect.val('').trigger('change.select2');

                $.ajax({
                    url: "{{ route('admin.get.sessions.by.course') }}",
                    type: "GET",
                    data: { course_pk: courseId },
                    dataType: 'json',
                    success: function(response) {
                        var options = '<option value="">— All Sessions —</option>';
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
                        $sessionSelect.html(options);
                        $sessionSelect.val('').trigger('change.select2');
                        loadGroupedData();
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        $sessionSelect.html('<option value="">Error loading sessions</option>');
                        $sessionSelect.val('').trigger('change.select2');
                        loadGroupedData();
                    }
                });
            });

            // Auto-reload on session change
            $('#filter_session_id').on('change', function() {
                loadGroupedData();
            });

            // Auto-reload on date changes (debounced)
            var dateFilterTimeout;
            $('#filter_from_date, #filter_to_date').on('change', function() {
                clearTimeout(dateFilterTimeout);
                dateFilterTimeout = setTimeout(function() {
                    loadGroupedData();
                }, 500);
            });

            // ── Export Handlers ──

            function submitExportForm(actionUrl) {
                var form = $('<form>', {
                    method: 'POST',
                    action: actionUrl,
                    style: 'display: none'
                });

                $('<input>').attr({ type: 'hidden', name: '_token', value: "{{ csrf_token() }}" }).appendTo(form);

                var filters = {
                    course_pk: $('#filter_course_pk').val(),
                    session_id: $('#filter_session_id').val(),
                    from_date: $('#filter_from_date').val(),
                    to_date: $('#filter_to_date').val()
                };

                $.each(filters, function(key, value) {
                    if (value && value !== '') {
                        $('<input>').attr({ type: 'hidden', name: key, value: value }).appendTo(form);
                    }
                });

                $('body').append(form);
                form.submit();
                setTimeout(function() { form.remove(); }, 1000);
            }

            $('#exportPDF').on('click', function(e) {
                e.preventDefault();
                submitExportForm("{{ route('admin.feedback.export.pdf') }}");
            });

            $('#exportExcel').on('click', function(e) {
                e.preventDefault();
                submitExportForm("{{ route('admin.feedback.export.excel') }}");
            });

            // Print — opens the same template in a new window
            $('#btnPrint').on('click', function(e) {
                e.preventDefault();
                var params = $.param({
                    course_pk: $('#filter_course_pk').val(),
                    session_id: $('#filter_session_id').val(),
                    from_date: $('#filter_from_date').val(),
                    to_date: $('#filter_to_date').val()
                });
                window.open("{{ route('admin.feedback.print') }}?" + params, '_blank');
            });

            // ── Initial Load ──
            // Active tab is default; set course list for active courses with pre-selected active course
            var activeCourseId = '{{ $activeCourse ?? '' }}';
            switchCourseList('active', activeCourseId);

            if (activeCourseId) {
                // Load sessions for active course, then load grouped data
                var $sessionSelect = $('#filter_session_id');
                $sessionSelect.html('<option value="">Loading sessions...</option>');
                $.ajax({
                    url: "{{ route('admin.get.sessions.by.course') }}",
                    type: "GET",
                    data: { course_pk: activeCourseId },
                    dataType: 'json',
                    success: function(response) {
                        var options = '<option value="">— All Sessions —</option>';
                        if (response && response.length > 0) {
                            $.each(response, function(index, session) {
                                var label = session.subject_topic;
                                if (session.START_DATE) {
                                    label += ' (' + session.START_DATE + ')';
                                }
                                options += '<option value="' + session.pk + '">' + $('<span>').text(label).html() + '</option>';
                            });
                        }
                        $sessionSelect.html(options);
                        $sessionSelect.val('').trigger('change.select2');
                        originalSessions = options;
                        loadGroupedData();
                    },
                    error: function() {
                        $sessionSelect.html('<option value="">— All Sessions —</option>');
                        loadGroupedData();
                    }
                });
            } else {
                loadGroupedData();
            }

            console.log('Ready - Pending Feedback with Accordion View');
        });
    </script>
@endpush