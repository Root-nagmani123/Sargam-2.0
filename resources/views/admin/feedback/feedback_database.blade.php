@extends('admin.layouts.master')

@section('title', 'Feedback Database - Sargam | Lal Bahadur')

@section('setup_content')
    <style>
        /* ── Variables ── */
        :root {
            --fb-primary: #0b4f8a;
            --fb-primary-light: #eef4fb;
            --fb-border: #d0d7de;
        }

        /* ── Filter Card ── */
        .filter-card {
            border: 0;
            border-radius: var(--bs-border-radius-lg);
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            overflow: hidden;
        }

        .filter-card .card-header {
            background: var(--fb-primary);
            color: #fff;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0.7rem 1rem;
            border: 0;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .filter-card .card-body {
            padding: 1.1rem 1rem;
        }

        .filter-card .form-label {
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--bs-secondary-color);
            margin-bottom: 0.25rem;
        }

        .filter-card .form-select,
        .filter-card .form-control {
            font-size: 0.85rem;
            border-color: var(--fb-border);
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        }

        .filter-card .form-select:focus,
        .filter-card .form-control:focus {
            border-color: var(--fb-primary);
            box-shadow: 0 0 0 0.2rem rgba(11,79,138,.12);
        }

        /* ── Content Card ── */
        .content-card {
            border: 0;
            border-radius: var(--bs-border-radius-lg);
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            overflow: hidden;
        }

        .content-card .card-header {
            background: var(--fb-primary-light);
            font-weight: 600;
            font-size: 0.95rem;
            padding: 0.7rem 1rem;
            border-bottom: 1px solid rgba(11,79,138,.1);
        }

        /* ── Data Table ── */
        #feedbackTable {
            font-size: 0.85rem;
            margin-bottom: 0;
        }

        #feedbackTable thead th {
            font-weight: 600;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--bs-secondary-color);
            border-bottom: 2px solid rgba(11,79,138,.15);
            padding: 0.65rem 0.75rem;
            white-space: nowrap;
            vertical-align: middle;
        }

        #feedbackTable tbody td {
            padding: 0.65rem 0.75rem;
            vertical-align: middle;
            border-color: var(--bs-border-color-translucent);
        }

        #feedbackTable tbody tr {
            transition: background-color 0.15s ease;
        }

        #feedbackTable tbody tr:hover {
            background-color: rgba(11,79,138,.03) !important;
        }

        /* ── Loading Overlay ── */
        .loading-overlay {
            display: none;
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,.75);
            z-index: 10;
            justify-content: center;
            align-items: center;
        }

        .loading-overlay.active {
            display: flex;
        }

        .loading-spinner {
            width: 44px;
            height: 44px;
            border: 4px solid var(--fb-primary-light);
            border-top: 4px solid var(--fb-primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ── Percentage Badges ── */
        .percentage-badge {
            display: inline-block;
            min-width: 3.6rem;
            padding: 0.25em 0.55em;
            border-radius: var(--bs-border-radius-pill);
            font-size: 0.8rem;
            font-weight: 700;
            text-align: center;
        }

        .percentage-excellent { background: rgba(25,135,84,.1); color: #146c43; }
        .percentage-good      { background: rgba(180,83,9,.1);  color: #92400e; }
        .percentage-average   { background: rgba(220,53,69,.1);  color: #b02a37; }

        .filter-card .card-footer .disabled {
            pointer-events: none;
            opacity: 0.55;
        }

        /* ── Dynamic Filter Transition ── */
        .dynamic-filter-container {
            transition: all 0.3s ease;
        }

        /* ── Pagination ── */
        .pagination .page-link {
            font-size: 0.82rem;
            color: var(--fb-primary);
            border-color: var(--fb-border);
        }

        .pagination .page-item.active .page-link {
            background-color: var(--fb-primary);
            border-color: var(--fb-primary);
            color: #fff;
        }

        .pagination .page-link:hover {
            background-color: var(--fb-primary-light);
        }

        /* ── Table Controls ── */
        .table-controls .form-select,
        .table-controls .form-control {
            font-size: 0.82rem;
            border-color: var(--fb-border);
        }

        .table-controls .form-select:focus,
        .table-controls .form-control:focus {
            border-color: var(--fb-primary);
            box-shadow: 0 0 0 0.2rem rgba(11,79,138,.12);
        }

        /* ── Button Overrides ── */
        .btn-primary { background: var(--fb-primary); border-color: var(--fb-primary); }
        .btn-primary:hover { background: #083e6c; border-color: #083e6c; }

        .record-count {
            font-size: 0.8rem;
            color: var(--bs-secondary-color);
        }
    </style>

    <div class="container-fluid py-3 feedback-database-page">
        <x-breadcrum title="Feedback Database"></x-breadcrum>

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- ── TOP FILTER BAR ── --}}
        <div class="card filter-card mb-3">
            <div class="card-header">
                <i class="fas fa-sliders-h"></i> Filters
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    @php
                        $courseType = $courseType ?? 'current';
                    @endphp
                    {{-- Active / Archived (same logic as Faculty Feedback Average) --}}
                    <div class="col-12">
                        <label class="form-label">Course list</label>
                        <div class="d-flex flex-wrap gap-4">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="radio" name="course_type" value="current"
                                       id="fdbCourseCurrent" {{ $courseType === 'current' ? 'checked' : '' }}>
                                <label class="form-check-label" for="fdbCourseCurrent">Active (current) courses</label>
                            </div>
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="radio" name="course_type" value="archived"
                                       id="fdbCourseArchived" {{ $courseType === 'archived' ? 'checked' : '' }}>
                                <label class="form-check-label" for="fdbCourseArchived">Archived courses</label>
                            </div>
                        </div>
                    </div>

                    {{-- Program Name --}}
                    <div class="col-lg-3 col-md-4">
                        <label class="form-label">Program Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="courseSelect" name="course_id">
                            <option value="">Select Program</option>
                            @if (isset($courses) && $courses->count() > 0)
                                @foreach ($courses as $course)
                                    <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                @endforeach
                            @else
                                <option value="" disabled>No courses available</option>
                            @endif
                        </select>
                    </div>

                    {{-- Filter By --}}
                    <div class="col-lg-2 col-md-3">
                        <label class="form-label">Filter By</label>
                        <select class="form-select" id="searchParam" name="search_param">
                            <option value="all">All Records</option>
                            <option value="faculty">Faculty</option>
                            <option value="topic">Topic</option>
                        </select>
                    </div>

                    {{-- Faculty Filter (Hidden by default) --}}
                    <div class="col-lg-3 col-md-3 dynamic-filter-container d-none" id="facultyFilterContainer">
                        <label class="form-label">Select Faculty</label>
                        <select class="form-select" id="facultyFilter" name="faculty_id">
                            <option value="">All Faculties</option>
                            @if (isset($faculties) && $faculties->count() > 0)
                                @foreach ($faculties as $faculty)
                                    <option value="{{ $faculty->pk }}">{{ $faculty->full_name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    {{-- Topic Filter (Hidden by default) --}}
                    <div class="col-lg-3 col-md-3 dynamic-filter-container d-none" id="topicFilterContainer">
                        <label class="form-label">Enter Topic</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="topicFilter" name="topic_value"
                                placeholder="Type topic name...">
                            <button class="btn btn-outline-secondary btn-sm" type="button" id="clearTopicBtn">
                                <i class="material-icons menu-icon material-symbols-rounded">close</i>
                            </button>
                        </div>
                    </div>

                    {{-- Clear Filters --}}
                    <div class="col-lg-2 col-md-2">
                        <button type="button" class="btn btn-outline-secondary w-100" id="clearFiltersBtn">
                            <i class="bi bi-x-circle me-1"></i> Clear Filters
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-body-tertiary bg-opacity-50 border-top py-3 px-3 d-flex flex-wrap gap-2 align-items-center justify-content-end">
                <button type="button" id="feedbackDbPrintBtn"
                        class="btn btn-outline-primary rounded-1 px-3 d-inline-flex align-items-center gap-1"
                        title="Print report (LBSNAA layout)">
                    <span class="material-symbols-rounded" style="font-size: 1.1rem;">print</span>
                    <span>Print</span>
                </button>
                <a href="#" id="feedbackDbPdfLink" target="_blank" rel="noopener"
                   class="btn btn-outline-danger rounded-1 px-3 d-inline-flex align-items-center gap-1"
                   title="Download PDF">
                    <span class="material-symbols-rounded" style="font-size: 1.1rem;">picture_as_pdf</span>
                    <span>PDF</span>
                </a>
                <a href="#" id="feedbackDbExcelLink"
                   class="btn btn-success rounded-1 px-3 d-inline-flex align-items-center gap-1 shadow-sm"
                   title="Export to Excel">
                    <span class="material-symbols-rounded" style="font-size: 1.1rem;">table_view</span>
                    <span>Export Excel</span>
                </a>
            </div>
        </div>

        {{-- ── CONTENT TABLE CARD ── --}}
        <div class="card content-card">
            <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                <span class="d-flex align-items-center gap-2">
                    <i class="fas fa-database text-primary"></i>
                    Faculty Feedback Database
                </span>
            </div>
            <div class="card-body p-0">
                {{-- TABLE CONTROLS --}}
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 px-3 py-2 table-controls border-bottom"
                     style="background: #fafbfc;">
                    <div class="d-flex align-items-center gap-2">
                        <label class="text-muted mb-0" style="font-size: 0.82rem;">Show</label>
                        <select class="form-select form-select-sm d-inline-block w-auto" id="perPageSelect">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <label class="text-muted mb-0" style="font-size: 0.82rem;">entries</label>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <label class="text-muted mb-0" style="font-size: 0.82rem;"><i class="fas fa-search"></i></label>
                        <input type="text" class="form-control form-control-sm" id="tableSearch"
                            placeholder="Search within table..." style="min-width: 180px;">
                    </div>
                </div>

                {{-- TABLE --}}
                <div class="table-responsive position-relative" id="tableContainer">
                    {{-- Loading Overlay --}}
                    <div class="loading-overlay" id="loadingOverlay">
                        <div class="loading-spinner"></div>
                    </div>
                    <table class="table table-hover align-middle mb-0" id="feedbackTable">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:50px">S.No.</th>
                                <th>Faculty Name</th>
                                <th>Course Name</th>
                                <th>Faculty Address</th>
                                <th>Topic</th>
                                <th class="text-center">Content (%)</th>
                                <th class="text-center">Presentation (%)</th>
                                <th class="text-center">Participants</th>
                                <th class="text-center">Session Date</th>
                                <th class="text-center">Comments</th>
                            </tr>
                        </thead>
                        <tbody id="feedbackTableBody">
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-5">
                                            <div class="py-3">
                                                <i class="fas fa-database fa-2x mb-2 opacity-25 d-block"></i>
                                                Select a program to load feedback data
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- PAGINATION --}}
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center px-3 py-2 border-top"
                             id="paginationSection" style="display: none; background: #fafbfc;">
                            <small class="text-muted record-count" id="paginationInfo">Showing 0 to 0 of 0 entries</small>
                            <nav aria-label="Feedback pagination">
                                <ul class="pagination pagination-sm mb-0 mt-2 mt-sm-0" id="paginationLinks">
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
    </div>

    {{-- Comments Modal --}}
    <div class="modal fade" id="commentsModal" tabindex="-1" aria-labelledby="commentsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header" style="background: var(--fb-primary); color: #fff;">
                    <h6 class="modal-title mb-0" id="commentsModalLabel"><i class="fas fa-comments me-2"></i>Feedback Comments</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="commentsContent"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const FEEDBACK_DB_EXPORT_ROUTES = {
            print: @json(route('admin.feedback.database.print')),
            pdf: @json(route('admin.feedback.database.export.pdf')),
            excel: @json(route('admin.feedback.database.export.excel')),
        };
        const FEEDBACK_DB_COURSES_URL = @json(route('admin.feedback.database.courses'));

        $(document).ready(function() {
            // Prevent duplicate execution
            if (window.feedbackPageLoaded) {
                console.log('Script already loaded, skipping');
                return;
            }
            window.feedbackPageLoaded = true;

            console.log('=== FEEDBACK PAGE INITIALIZATION ===');

            let currentPage = 1;
            let perPage = 10;
            let totalRecords = 0;
            let currentFilters = {
                course_id: '',
                search_param: 'all',
                faculty_id: '',
                topic_value: ''
            };
            let courseType = @json($courseType ?? 'current');
            let debounceTimer;

            // Check if required elements exist
            if (!checkRequiredElements()) {
                console.error('Required elements not found');
                return;
            }

            // Initialize
            initializeEventListeners();
            autoSelectFirstCourse();
            syncFeedbackDbExportLinks();
            syncFeedbackDbCourseTypeUrl();

            function checkRequiredElements() {
                const requiredElements = [
                    '#courseSelect',
                    '#searchParam',
                    '#feedbackTableBody',
                    '#loadingOverlay'
                ];

                for (const selector of requiredElements) {
                    if (!$(selector).length) {
                        console.error(`Required element not found: ${selector}`);
                        return false;
                    }
                }
                return true;
            }

            function autoSelectFirstCourse() {
                const courseSelect = $('#courseSelect');
                if (!courseSelect.length) return;

                const firstCourseOption = courseSelect.find('option:not(:first):not([disabled])').first();

                if (firstCourseOption.length > 0) {
                    const courseId = firstCourseOption.val();
                    const courseName = firstCourseOption.text();

                    courseSelect.val(courseId);
                    currentFilters.course_id = courseId;

                    $('#feedbackTableBody').html(`
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        Loading feedback data for <strong>${courseName}</strong>...
                    </td>
                </tr>
            `);

                    loadFeedbackData();
                } else {
                    showInitialMessage();
                    syncFeedbackDbExportLinks();
                }
            }

            function initializeEventListeners() {
                // Safely bind events only if elements exist
                safeBind('#courseSelect', 'change', function(e) {
                    e.preventDefault();
                    const courseId = $(this).val();
                    if (courseId) {
                        currentFilters.course_id = courseId;
                        currentPage = 1;
                        loadFeedbackData();
                    } else {
                        showInitialMessage();
                        syncFeedbackDbExportLinks();
                    }
                });

                safeBind('#searchParam', 'change', function(e) {
                    e.preventDefault();
                    const searchParam = $(this).val();
                    currentFilters.search_param = searchParam;

                    $('.dynamic-filter-container').addClass('d-none').removeClass('d-block');

                    if (searchParam === 'faculty') {
                        showElement('#facultyFilterContainer');
                        currentFilters.faculty_id = $('#facultyFilter').val();
                        currentFilters.topic_value = '';
                        $('#topicFilter').val('');
                    } else if (searchParam === 'topic') {
                        showElement('#topicFilterContainer');
                        currentFilters.topic_value = $('#topicFilter').val();
                        currentFilters.faculty_id = '';
                        $('#facultyFilter').val('');
                    } else {
                        currentFilters.faculty_id = '';
                        currentFilters.topic_value = '';
                        $('#facultyFilter').val('');
                        $('#topicFilter').val('');
                    }

                    if (currentFilters.course_id) {
                        currentPage = 1;
                        loadFeedbackData();
                    }
                });

                safeBind('#facultyFilter', 'change', function(e) {
                    e.preventDefault();
                    currentFilters.faculty_id = $(this).val();
                    if (currentFilters.course_id) {
                        currentPage = 1;
                        loadFeedbackData();
                    }
                });

                safeBind('#topicFilter', 'input', function(e) {
                    e.preventDefault();
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        currentFilters.topic_value = $(this).val();
                        if (currentFilters.course_id && currentFilters.topic_value.length >= 2) {
                            currentPage = 1;
                            loadFeedbackData();
                        }
                    }, 500);
                });

                safeBind('#clearTopicBtn', 'click', function(e) {
                    e.preventDefault();
                    $('#topicFilter').val('');
                    currentFilters.topic_value = '';
                    if (currentFilters.course_id) {
                        currentPage = 1;
                        loadFeedbackData();
                    }
                });

                safeBind('#clearFiltersBtn', 'click', function(e) {
                    e.preventDefault();
                    clearAllFilters();
                });

                safeBind('#perPageSelect', 'change', function(e) {
                    e.preventDefault();
                    perPage = $(this).val();
                    currentPage = 1;
                    if (currentFilters.course_id) {
                        loadFeedbackData();
                    }
                });

                safeBind('#tableSearch', 'keyup', function(e) {
                    e.preventDefault();
                    const searchText = $(this).val().toLowerCase();
                    $('#feedbackTableBody tr').each(function() {
                        const rowText = $(this).text().toLowerCase();
                        $(this).toggle(rowText.includes(searchText));
                    });
                });

                safeBind('#feedbackDbPrintBtn', 'click', function(e) {
                    e.preventDefault();
                    if (!currentFilters.course_id) {
                        alert('Please select a program first.');
                        return;
                    }
                    const q = buildFeedbackDbExportQuery();
                    window.open(FEEDBACK_DB_EXPORT_ROUTES.print + (q ? ('?' + q) : ''), '_blank', 'noopener');
                });

                $('input[name="course_type"]').on('change', function() {
                    reloadCourseListForType();
                });
            }

            function reloadCourseListForType() {
                const ct = document.querySelector('input[name="course_type"]:checked')?.value || 'current';
                courseType = ct;
                showLoading(true);
                const url = FEEDBACK_DB_COURSES_URL + '?course_type=' + encodeURIComponent(ct);
                fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Bad response');
                        }
                        return response.json();
                    })
                    .then(data => {
                        showLoading(false);
                        if (!data.success) {
                            alert('Could not load programs for this course type.');
                            return;
                        }
                        const sel = $('#courseSelect');
                        sel.empty().append('<option value="">Select Program</option>');
                        if (data.courses && data.courses.length > 0) {
                            data.courses.forEach(function(c) {
                                const label = $('<div/>').text(c.course_name || '').html();
                                sel.append('<option value="' + c.pk + '">' + label + '</option>');
                            });
                        } else {
                            sel.append('<option value="" disabled>No courses available</option>');
                        }
                        currentFilters.course_id = '';
                        currentFilters.search_param = 'all';
                        currentFilters.faculty_id = '';
                        currentFilters.topic_value = '';
                        $('#searchParam').val('all');
                        $('#facultyFilter').val('');
                        $('#topicFilter').val('');
                        $('.dynamic-filter-container').addClass('d-none').removeClass('d-block');
                        currentPage = 1;
                        autoSelectFirstCourse();
                        syncFeedbackDbExportLinks();
                        syncFeedbackDbCourseTypeUrl();
                    })
                    .catch(function(err) {
                        console.error(err);
                        showLoading(false);
                        alert('Could not load programs.');
                    });
            }

            function syncFeedbackDbCourseTypeUrl() {
                try {
                    const u = new URL(window.location.href);
                    u.searchParams.set('course_type', courseType || 'current');
                    window.history.replaceState({}, '', u.toString());
                } catch (e) { /* ignore */ }
            }

            function buildFeedbackDbExportQuery() {
                if (!currentFilters.course_id) return '';
                const params = new URLSearchParams();
                params.set('course_id', currentFilters.course_id);
                params.set('search_param', currentFilters.search_param || 'all');
                if (currentFilters.faculty_id) {
                    params.set('faculty_id', currentFilters.faculty_id);
                }
                if (currentFilters.topic_value) {
                    params.set('topic_value', currentFilters.topic_value);
                }
                return params.toString();
            }

            function syncFeedbackDbExportLinks() {
                const q = buildFeedbackDbExportQuery();
                const $pdf = $('#feedbackDbPdfLink');
                const $excel = $('#feedbackDbExcelLink');
                const $print = $('#feedbackDbPrintBtn');
                if (!q) {
                    $pdf.attr('href', '#').addClass('disabled');
                    $excel.attr('href', '#').addClass('disabled');
                    $print.prop('disabled', true).addClass('disabled');
                    return;
                }
                $pdf.attr('href', FEEDBACK_DB_EXPORT_ROUTES.pdf + '?' + q).removeClass('disabled');
                $excel.attr('href', FEEDBACK_DB_EXPORT_ROUTES.excel + '?' + q).removeClass('disabled');
                $print.prop('disabled', false).removeClass('disabled');
            }

            // Helper function to safely bind events
            function safeBind(selector, event, handler) {
                const element = $(selector);
                if (element.length) {
                    element.off(event).on(event, handler);
                } else {
                    console.warn(`Element not found for binding: ${selector}`);
                }
            }

            // Helper function to safely show elements
            function showElement(selector) {
                const element = $(selector);
                if (element.length) {
                    element.removeClass('d-none').addClass('d-block');
                }
            }

            function clearAllFilters() {
                $('#courseSelect').val('');
                $('#searchParam').val('all');
                $('#facultyFilter').val('');
                $('#topicFilter').val('');

                $('.dynamic-filter-container').addClass('d-none').removeClass('d-block');

                currentFilters = {
                    course_id: '',
                    search_param: 'all',
                    faculty_id: '',
                    topic_value: ''
                };
                currentPage = 1;

                showInitialMessage();
                syncFeedbackDbExportLinks();
            }

            function showInitialMessage() {
                const hasCourses = $('#courseSelect option').length > 1;

                if (hasCourses) {
                    $('#feedbackTableBody').html(`
                <tr>
                    <td colspan="10" class="text-center text-muted py-5">
                        <div class="py-3">
                            <i class="fas fa-database fa-2x mb-2 opacity-25 d-block"></i>
                            Select a program to view feedback data
                        </div>
                    </td>
                </tr>
            `);
                } else {
                    $('#feedbackTableBody').html(`
                <tr>
                    <td colspan="10" class="text-center text-muted py-5">
                        <div class="py-3">
                            <i class="fas fa-exclamation-circle fa-2x mb-2 opacity-25 d-block"></i>
                            No programs available. Please add courses first.
                        </div>
                    </td>
                </tr>
            `);
                }
                $('#paginationSection').hide();
            }

            function loadFeedbackData() {
                if (!currentFilters.course_id) {
                    showInitialMessage();
                    syncFeedbackDbExportLinks();
                    return;
                }

                showLoading(true);

                const params = new URLSearchParams({
                    ...currentFilters,
                    page: currentPage,
                    per_page: perPage
                });

                const apiUrl = `/faculty/database/data?${params.toString()}`;

                fetch(apiUrl)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            renderTable(data.data);
                            updatePagination(data);
                        } else {
                            showErrorMessage(data.error || 'Error loading data');
                        }
                        showLoading(false);
                        syncFeedbackDbExportLinks();
                    })
                    .catch(error => {
                        console.error('Error loading feedback data:', error);
                        showErrorMessage('Error loading data. Please try again.');
                        showLoading(false);
                        syncFeedbackDbExportLinks();
                    });
            }

            function renderTable(data) {
                const tbody = $('#feedbackTableBody');
                if (!tbody.length) return;

                tbody.empty();

                if (!data || data.length === 0) {
                    showNoDataMessage();
                    $('#paginationSection').hide();
                    return;
                }

                data.forEach((item, index) => {
                    const row = `
                <tr>
                    <td class="text-center">${((currentPage - 1) * perPage) + index + 1}</td>
                    <td>
                        <a href="javascript:void(0)" class="link-primary fw-semibold faculty-link" 
                           data-faculty-id="${item.faculty_enc_id || ''}"
                           title="View faculty details" style="color: var(--fb-primary);">
                            ${item.faculty_name}
                        </a>
                    </td>
                    <td>${item.course_name}</td>
                    <td>
                        <small class="text-body-secondary">
                            ${item.faculty_address || 'N/A'}
                            ${item.faculty_email ? `<br><a href="mailto:${item.faculty_email}" class="text-muted">${item.faculty_email}</a>` : ''}
                        </small>
                    </td>
                    <td>
                        <small class="text-truncate d-block" style="max-width: 200px;" 
                               title="${item.subject_topic}">
                            ${item.subject_topic}
                        </small>
                    </td>
                    <td class="text-center">
                        <span class="percentage-badge ${getPercentageClass(item.avg_content_percent)}">
                            ${formatPercentage(item.avg_content_percent)}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="percentage-badge ${getPercentageClass(item.avg_presentation_percent)}">
                            ${formatPercentage(item.avg_presentation_percent)}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold">${item.participant_count}</span>
                    </td>
                    <td class="text-center">
                        <small>${formatDate(item.session_date)}</small>
                    </td>
                    <td class="text-center">
                        ${item.all_comments ? 
                            `<button class="btn btn-sm btn-outline-primary view-comments-btn" 
                                     data-comments="${escapeHtml(item.all_comments)}"
                                     style="border-radius: 20px; font-size: 0.75rem;">
                                <i class="fas fa-comment-dots"></i> View
                            </button>` : 
                            '<span class="text-muted" style="font-size: 0.8rem;">—</span>'
                        }
                    </td>
                </tr>
            `;
                    tbody.append(row);
                });

                // Safely bind comments modal
                $('.view-comments-btn').off('click').on('click', function(e) {
                    e.preventDefault();
                    const comments = $(this).data('comments');
                    const modalElement = document.getElementById('commentsModal');
                    if (modalElement) {
                        $('#commentsContent').html(`
                    <div style="max-height: 400px; overflow-y: auto;">
                        ${comments.split(' | ').map((comment, i) => `
                            <div class="d-flex gap-2 align-items-start border-bottom pb-2 mb-2">
                                <span class="badge bg-primary bg-opacity-10 text-primary mt-1" style="min-width: 22px;">${i + 1}</span>
                                <p class="mb-0 text-body-secondary" style="font-size: 0.88rem;">${comment}</p>
                            </div>
                        `).join('')}
                    </div>
                `);
                        new bootstrap.Modal(modalElement).show();
                    }
                });

                // Faculty link handlers
                $('.faculty-link').off('click').on('click', function(e) {
                    e.preventDefault();
                    const facultyId = $(this).data('faculty-id');
                    if (facultyId) {
                        window.open(`/faculty/show/${facultyId}`, '_blank');
                    }
                });
            }

            function updatePagination(data) {
                const paginationSection = $('#paginationSection');
                if (!paginationSection.length) return;

                totalRecords = data.total;
                const totalPages = Math.ceil(totalRecords / perPage);

                $('#paginationInfo').text(
                    `Showing ${((currentPage - 1) * perPage) + 1} to ${Math.min(currentPage * perPage, totalRecords)} of ${totalRecords} entries`
                );

                const paginationLinks = $('#paginationLinks');
                if (!paginationLinks.length) return;

                paginationLinks.empty();

                if (totalPages <= 1) {
                    paginationSection.hide();
                    return;
                }

                paginationSection.show();

                const prevLi = $(`<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" data-page="${currentPage - 1}">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>`);
                paginationLinks.append(prevLi);

                // Page numbers
                const maxPagesToShow = 5;
                let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
                let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

                if (endPage - startPage + 1 < maxPagesToShow) {
                    startPage = Math.max(1, endPage - maxPagesToShow + 1);
                }

                for (let i = startPage; i <= endPage; i++) {
                    const pageLi = $(`<li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="javascript:void(0)" data-page="${i}">${i}</a>
            </li>`);
                    paginationLinks.append(pageLi);
                }

                // Next button
                const nextLi = $(`<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" data-page="${currentPage + 1}">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>`);
                paginationLinks.append(nextLi);

                // Add click handlers
                $('.page-link').off('click').on('click', function(e) {
                    e.preventDefault();
                    const page = $(this).data('page');
                    if (page && page >= 1 && page <= totalPages) {
                        currentPage = page;
                        loadFeedbackData();
                    }
                });
            }

            function showLoading(show) {
                if (show) {
                    $('#loadingOverlay').addClass('active');
                } else {
                    $('#loadingOverlay').removeClass('active');
                }
            }

            function showNoDataMessage() {
                $('#feedbackTableBody').html(`
            <tr>
                <td colspan="10" class="text-center text-muted py-5">
                    <div class="py-3">
                        <i class="fas fa-search fa-2x mb-2 opacity-25 d-block"></i>
                        No feedback data found for the selected criteria
                    </div>
                </td>
            </tr>
        `);
            }

            function showErrorMessage(message) {
                $('#feedbackTableBody').html(`
            <tr>
                <td colspan="10" class="text-center text-danger py-5">
                    <div class="py-3">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2 opacity-50 d-block"></i>
                        ${message}
                    </div>
                </td>
            </tr>
        `);
            }

            function formatPercentage(value) {
                const num = parseFloat(value) || 0;
                return num.toFixed(2) + '%';
            }

            function getPercentageClass(value) {
                const num = parseFloat(value) || 0;
                if (num >= 90) return 'percentage-excellent';
                if (num >= 80) return 'percentage-good';
                return 'percentage-average';
            }

            function formatDate(dateString) {
                if (!dateString) return 'N/A';
                const date = new Date(dateString);
                return date.toLocaleDateString('en-IN', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        });
    </script>
@endsection
