@extends('admin.layouts.master')

@section('title', 'Pending Feedback - Students')

@section('setup_content')
    <div class="container-fluid px-2 px-sm-3 px-md-4 pb-4 pb-lg-5 pending-feedback-page">
        <x-breadcrum title="Pending Feedback – Students"></x-breadcrum>

        <x-session_message />

        <!-- Filters -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex flex-wrap align-items-center gap-3">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <div class="flex-shrink-0 rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center pending-fb-filter-icon" aria-hidden="true">
                        <i class="material-symbols-rounded">tune</i>
                    </div>
                    <div class="min-w-0">
                        <h2 class="h6 fw-semibold text-body mb-0">Filters</h2>
                        <p class="small text-body-secondary mb-0 text-truncate">Choose program scope, then refine by course, session, dates, and feedback status</p>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 g-lg-4 align-items-end">
                    <!-- Active / Archive: before Course -->
                    <div class="col-12 col-lg-6 col-xl-5 col-xxl-4">
                        <fieldset class="border-0 m-0 p-0 course-scope-in-filters h-100" id="courseScopeFieldset">
                            <legend class="form-label small fw-semibold text-body-secondary text-uppercase letter-spacing-tight mb-2 float-none w-100">Program scope</legend>
                            <div class="d-flex flex-row flex-nowrap gap-2 align-items-stretch" id="courseTypeTabs" role="radiogroup" aria-label="Program scope">
                                <div class="course-scope-option flex-fill rounded-3 border bg-body shadow-sm min-w-0">
                                    <div class="form-check m-0 p-2 ps-2 d-flex align-items-center gap-1 h-100">
                                        <input class="form-check-input flex-shrink-0 ms-1 mt-0" type="radio" name="course_type_scope" id="course_scope_active" value="active" checked>
                                        <label class="form-check-label flex-grow-1 cursor-pointer mb-0 small text-truncate" for="course_scope_active">
                                            <span class="d-flex align-items-center justify-content-between gap-1 min-w-0">
                                                <span class="d-inline-flex align-items-center gap-1 fw-semibold text-body text-truncate">
                                                    <i class="material-symbols-rounded fs-6 text-primary flex-shrink-0" aria-hidden="true">school</i>
                                                    <span class="text-truncate">Active</span>
                                                </span>
                                                <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary border-opacity-25 flex-shrink-0" id="activeCourseBadge">{{ count($activeCourses ?? []) }}</span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                <div class="course-scope-option flex-fill rounded-3 border bg-body shadow-sm min-w-0">
                                    <div class="form-check m-0 p-2 ps-2 d-flex align-items-center gap-1 h-100">
                                        <input class="form-check-input flex-shrink-0 ms-1 mt-0" type="radio" name="course_type_scope" id="course_scope_archive" value="archive">
                                        <label class="form-check-label flex-grow-1 cursor-pointer mb-0 small text-truncate" for="course_scope_archive">
                                            <span class="d-flex align-items-center justify-content-between gap-1 min-w-0">
                                                <span class="d-inline-flex align-items-center gap-1 fw-semibold text-body text-truncate">
                                                    <i class="material-symbols-rounded fs-6 text-body-secondary flex-shrink-0" aria-hidden="true">inventory_2</i>
                                                    <span class="text-truncate">Archive</span>
                                                </span>
                                                <span class="badge rounded-pill bg-body-secondary text-body border flex-shrink-0" id="archiveCourseBadge">{{ count($archiveCourses ?? []) }}</span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <label for="filter_course_pk" class="form-label small fw-semibold text-body-secondary text-uppercase letter-spacing-tight mb-2">Course</label>
                        <select class="form-select select2-course shadow-sm" id="filter_course_pk" aria-describedby="hint-filter-course">
                            <option value="">— All Courses —</option>
                            @foreach ($courses ?? [] as $id => $name)
                                <option value="{{ $id }}" {{ isset($activeCourse) && $activeCourse == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <label for="filter_session_id" class="form-label small fw-semibold text-body-secondary text-uppercase letter-spacing-tight mb-2">Session</label>
                        <select class="form-select select2-session shadow-sm" id="filter_session_id">
                            <option value="">— All Sessions —</option>
                            @foreach ($sessions ?? [] as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                        <label for="filter_from_date" class="form-label small fw-semibold text-body-secondary text-uppercase letter-spacing-tight mb-2">From date</label>
                        <input type="date" class="form-control shadow-sm" id="filter_from_date" autocomplete="off">
                    </div>

                    <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                        <label for="filter_to_date" class="form-label small fw-semibold text-body-secondary text-uppercase letter-spacing-tight mb-2">To date</label>
                        <input type="date" class="form-control shadow-sm" id="filter_to_date" autocomplete="off">
                    </div>

                    <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                        <label for="filter_feedback_state" class="form-label small fw-semibold text-body-secondary text-uppercase letter-spacing-tight mb-2">Feedback</label>
                        <select class="form-select shadow-sm" id="filter_feedback_state">
                            <option value="not_given" selected>Not given</option>
                            <option value="given">Given</option>
                        </select>
                    </div>

                    <div class="col-12 col-xl-12 col-xxl-auto d-flex flex-wrap gap-2 align-items-end pt-xl-2 pt-xxl-0">
                        <button type="button" id="btnApplyFilters"
                            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-3 shadow-sm">
                            <i class="material-symbols-rounded fs-6" aria-hidden="true">search</i>
                            <span>Apply filters</span>
                        </button>
                        <button type="button" id="btnResetFilters"
                            class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 px-4 rounded-3">
                            <i class="material-symbols-rounded fs-6" aria-hidden="true">refresh</i>
                            <span>Reset</span>
                        </button>
                    </div>
                </div>

                <hr class="border-secondary border-opacity-25 my-4">

                <!-- Summary + exports -->
                <div class="row g-3 align-items-stretch align-items-lg-center">
                    <div class="col-12 col-lg-5 col-xl-4">
                        <div class="d-flex align-items-center gap-3 p-3 rounded-4 bg-primary bg-opacity-10 border border-primary border-opacity-25 h-100">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0 pending-fb-stat-dot" aria-hidden="true">
                                <i class="material-symbols-rounded fs-5">groups</i>
                            </div>
                            <div>
                                <p class="small text-body-secondary text-uppercase fw-semibold mb-1 letter-spacing-tight">Total students</p>
                                <p class="mb-0 d-flex align-items-baseline gap-2 flex-wrap">
                                    <span id="totalRecordsCount" class="badge bg-primary fs-5 px-3 py-2 rounded-pill">0</span>
                                    <span class="small text-body-secondary">in current view</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-7 col-xl-8">
                        <p class="small text-body-secondary fw-semibold text-uppercase letter-spacing-tight mb-2">Export</p>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" id="exportPDF" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-2 rounded-3 px-3 shadow-sm">
                                <i class="material-symbols-rounded fs-6" aria-hidden="true">picture_as_pdf</i>
                                PDF
                            </button>
                            <button type="button" id="exportExcelSummary"
                                class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-2 rounded-3 px-3 shadow-sm">
                                <i class="material-symbols-rounded fs-6" aria-hidden="true">table_view</i>
                                Not given feedback count
                            </button>
                            <button type="button" id="exportExcelDetailed"
                                class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-2 rounded-3 px-3">
                                <i class="material-symbols-rounded fs-6" aria-hidden="true">view_list</i>
                               Not givern Feedback details
                            </button>
                            <button type="button" id="btnPrint"
                                class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2 rounded-3 px-3">
                                <i class="material-symbols-rounded fs-6" aria-hidden="true">print</i>
                                Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-2">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-between gap-3">
                    <div class="d-flex align-items-start gap-3 min-w-0">
                        <div class="flex-shrink-0 rounded-3 bg-warning bg-opacity-15 text-warning d-flex align-items-center justify-content-center pending-fb-results-icon text-white" aria-hidden="true">
                            <i class="material-symbols-rounded">assignment_late</i>
                        </div>
                        <div class="min-w-0">
                            <h2 class="h5 fw-bold text-body mb-1">Pending feedback</h2>
                            <p class="text-body-secondary small mb-0">Expand a row for session-level detail, or use Expand all for this page. Column headers sort the list.</p>
                        </div>
                    </div>
                    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 w-100 flex-lg-grow-0" style="max-width: 36rem;">
                        <div class="btn-group shadow-sm rounded-3 overflow-hidden border flex-shrink-0" role="group" aria-label="Expand or collapse all student rows">
                            <button type="button" class="btn btn-sm btn-light px-3 d-inline-flex align-items-center gap-1" id="btnExpandAllStudents" title="Open every row on this page">
                                <i class="material-symbols-rounded fs-6" aria-hidden="true">unfold_more</i>
                                <span>Expand all</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-light px-3 border-start d-inline-flex align-items-center gap-1" id="btnCollapseAllStudents" title="Close every row on this page">
                                <i class="material-symbols-rounded fs-6" aria-hidden="true">unfold_less</i>
                                <span>Collapse all</span>
                            </button>
                        </div>
                        <div class="flex-grow-1" style="min-width: 12rem;">
                            <label for="studentSearch" class="visually-hidden">Search students</label>
                            <div class="input-group shadow-sm rounded-3 overflow-hidden border">
                                <span class="input-group-text bg-body-secondary bg-opacity-25 border-0 text-body-secondary">
                                    <i class="material-symbols-rounded" aria-hidden="true">search</i>
                                </span>
                                <input type="search" class="form-control border-0 shadow-none" id="studentSearch"
                                    placeholder="Name, email, or OT code…" autocomplete="off" enterkeyhint="search">
                                <button class="btn btn-light border-0 border-start px-3" type="button" id="clearSearch"
                                    title="Clear search" aria-label="Clear search" style="display:none">
                                    <i class="material-symbols-rounded fs-6" aria-hidden="true">close</i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0 bg-body-tertiary bg-opacity-25">
                <div id="studentAccordionContainer" class="px-2 px-sm-3 px-lg-4 py-3 py-lg-4">
                    <div class="text-center py-5 px-3 rounded-4 bg-body-secondary bg-opacity-25 border border-secondary border-opacity-10">
                        <div class="spinner-border text-primary" role="status" aria-label="Loading">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 mb-0 text-body-secondary small fw-medium">Loading student data…</p>
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
        /* ── Page utilities ── */
        .pending-feedback-page .letter-spacing-tight { letter-spacing: 0.04em; }
        .pending-fb-filter-icon,
        .pending-fb-results-icon {
            width: 2.75rem;
            height: 2.75rem;
            font-size: 1.35rem;
        }
        .pending-fb-stat-dot { width: 3rem; height: 3rem; }

        #studentSearch:focus {
            box-shadow: none;
        }

        @media (prefers-reduced-motion: reduce) {
            .accordion-toggle,
            .accordion-toggle .accordion-icon,
            .sortable-header,
            .sortable-header .sort-icon {
                transition: none !important;
            }
        }

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
            border-bottom: 1px solid var(--bs-border-color);
            white-space: nowrap;
            padding: 0.75rem 0.85rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--bs-secondary-color);
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
            padding: 1rem 1.25rem;
            background: linear-gradient(180deg, var(--bs-tertiary-bg) 0%, var(--bs-body-bg) 100%);
            border-top: 1px dashed var(--bs-border-color);
            border-left: 3px solid var(--bs-primary);
            margin-left: 0.75rem;
            border-radius: 0 0 var(--bs-border-radius-lg) 0;
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

        /* ── Program scope (radio cards) ── */
        .pending-feedback-page #courseTypeTabs .course-scope-option {
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
            border-color: var(--bs-border-color) !important;
        }

        .pending-feedback-page #courseTypeTabs .course-scope-option:has(.form-check-input:checked) {
            border-color: var(--bs-primary) !important;
            box-shadow: 0 0.25rem 0.75rem rgba(var(--bs-primary-rgb), 0.2);
            background-color: rgba(var(--bs-primary-rgb), 0.06);
        }

        .pending-feedback-page #courseTypeTabs .course-scope-option .form-check-input:focus {
            box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.25);
        }

        .pending-feedback-page #courseTypeTabs label.cursor-pointer {
            cursor: pointer;
        }

        /* ── Empty / loading (dynamic) ── */
        .state-empty { text-align: center; max-width: 28rem; margin-inline: auto; }
        .state-empty .material-symbols-rounded { font-size: 2.75rem; color: var(--bs-secondary-color); opacity: 0.55; }

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
                var $el = $('#filter_session_id');
                destroySelect2IfAny($el);
                $el.select2({
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

            // Snapshot server-rendered options before Select2 wraps the selects
            var originalSessions = $('#filter_session_id').html();
            var originalCourses = $('#filter_course_pk').html();

            initCourseSelect2();
            initSessionSelect2();

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
                var tab = $(this).val() === 'archive' ? 'archive' : 'active';
                switchCourseList(tab);
                var $sess = $('#filter_session_id');
                destroySelect2IfAny($sess);
                $sess.html('<option value="">— All Sessions —</option>');
                initSessionSelect2();
                $sess.val('').trigger('change');
                originalSessions = $sess.html();
                $('#filter_from_date, #filter_to_date').val('');
                $('#filter_feedback_state').val('not_given');
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
                    filter_feedback_state: $('#filter_feedback_state').val(),
                    page: page,
                    per_page: perPage,
                    sort_by: sortBy,
                    sort_dir: sortDir,
                    search: searchTerm
                };

                $('#studentAccordionContainer').html(
                    '<div class="text-center py-5 px-3 rounded-4 bg-body-secondary bg-opacity-25 border border-secondary border-opacity-10">' +
                    '<div class="spinner-border text-primary" role="status" aria-label="Loading"><span class="visually-hidden">Loading...</span></div>' +
                    '<p class="mt-3 mb-0 text-body-secondary small fw-medium">Loading student data…</p></div>'
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
                            '<div class="state-empty rounded-4 border border-danger border-opacity-25 bg-danger bg-opacity-10 p-4 p-md-5">' +
                            '<i class="material-symbols-rounded d-block mb-3 text-danger" style="font-size:2.75rem">error_outline</i>' +
                            '<p class="fw-semibold text-body mb-1">Failed to load data</p>' +
                            '<p class="text-body-secondary small mb-0">Please try again or adjust your filters.</p></div>'
                        );
                        updateTotalCount(0);
                    }
                });
            }

            function renderAccordion(data) {
                if (!data.students || data.students.length === 0) {
                    $('#studentAccordionContainer').html(
                        '<div class="state-empty rounded-4 border border-secondary border-opacity-25 bg-body-secondary bg-opacity-25 p-4 p-md-5">' +
                        '<i class="material-symbols-rounded d-block mb-3 text-body-secondary">search_off</i>' +
                        '<p class="fw-semibold text-body mb-1">No records found</p>' +
                        '<p class="text-body-secondary small mb-0">Try adjusting your filters or search.</p></div>'
                    );
                    return;
                }

                var html = '<div class="table-responsive rounded-3 border shadow-sm bg-body"><table class="table table-hover student-table align-middle mb-0">';
                html += '<thead class="table-light"><tr>';
                html += '<th class="text-center" style="width:50px">#</th>';
                html += buildSortHeader('Student Name', 'student_name');
                html += buildSortHeader('Course', 'course_summary');
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
                    html += '<td><small class="text-body">' + escapeHtml(student.course_summary || '—') + '</small></td>';
                    html += '<td class="text-center"><span class="badge rounded-pill bg-success-subtle text-success badge-count">' + student.feedback_given + '</span></td>';
                    html += '<td class="text-center"><span class="badge rounded-pill bg-danger-subtle text-danger badge-count">' + student.feedback_not_given + '</span></td>';
                    html += '<td class="text-center"><i class="material-symbols-rounded accordion-icon" aria-hidden="true">expand_more</i></td>';
                    html += '</tr>';

                    // Collapsible session details row
                    html += '<tr><td colspan="6" class="collapse-cell"><div class="collapse" id="' + collapseId + '">';
                    html += '<div class="collapse-inner">';
                    html += '<table class="table table-sm table-bordered table-striped session-detail-table mb-0">';
                    html += '<thead><tr>';
                    html += '<th>Course</th>';
                    html += '<th>Session Name</th>';
                    html += '<th>Date</th>';
                    html += '<th>Time</th>';
                    html += '<th class="text-center">Feedback Status</th>';
                    html += '</tr></thead><tbody>';

                    $.each(student.sessions, function(si, session) {
                        html += '<tr>';
                        html += '<td>' + escapeHtml(session.course_name || '—') + '</td>';
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

            // Expand / collapse all rows on the current page (data already loaded; no extra requests)
            function forEachStudentCollapse(fn) {
                document.querySelectorAll('#studentAccordionContainer .collapse').forEach(function(el) {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                        fn(bootstrap.Collapse.getOrCreateInstance(el, { toggle: false }));
                    }
                });
            }
            $('#btnExpandAllStudents').on('click', function() {
                forEachStudentCollapse(function(c) { c.show(); });
            });
            $('#btnCollapseAllStudents').on('click', function() {
                forEachStudentCollapse(function(c) { c.hide(); });
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

                var html = '<div class="pagination-wrap d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 mt-3 pt-3 px-2 border-top border-secondary border-opacity-10">';

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
                $('#filter_feedback_state').val('not_given');

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

            // Auto-reload on date changes (debounced)
            var dateFilterTimeout;
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
                    to_date: $('#filter_to_date').val(),
                    course_type: currentTab,
                    filter_feedback_state: $('#filter_feedback_state').val()
                };

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
                submitExportForm("{{ route('admin.feedback.export.pdf') }}");
            });

            $('#exportExcelSummary').on('click', function(e) {
                e.preventDefault();
                submitExportForm("{{ route('admin.feedback.export.excel') }}");
            });

            $('#exportExcelDetailed').on('click', function(e) {
                e.preventDefault();
                submitExportForm("{{ route('admin.feedback.export.excel.detailed') }}");
            });

            // Print — opens the same template in a new window
            $('#btnPrint').on('click', function(e) {
                e.preventDefault();
                var params = $.param({
                    course_pk: $('#filter_course_pk').val(),
                    session_id: $('#filter_session_id').val(),
                    from_date: $('#filter_from_date').val(),
                    to_date: $('#filter_to_date').val(),
                    course_type: currentTab,
                    filter_feedback_state: $('#filter_feedback_state').val()
                });
                window.open("{{ route('admin.feedback.print') }}?" + params, '_blank');
            });

            // ── Initial Load ──
            // Active tab + course list; pre-selected course triggers change → loads sessions + data
            var activeCourseId = '{{ $activeCourse ?? '' }}';
            switchCourseList('active', activeCourseId);

            if (!activeCourseId) {
                loadGroupedData();
            }

            console.log('Ready - Pending Feedback with Accordion View');
        });
    </script>
@endpush