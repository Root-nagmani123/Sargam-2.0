@php
    $fr = $fr ?? $feedbackReportRoutes ?? \App\Support\FeedbackReportRouteRegistry::forRequest();
@endphp
@extends('admin.layouts.master')

@section('title', 'Feedback Database')

@section('setup_content')
    @php
        $courseType = $courseType ?? 'current';
    @endphp

    <div class="container-fluid fdb-master-page py-3 px-3 px-lg-4">
        <x-breadcrum title="Feedback Database"></x-breadcrum>

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-3 rounded-3" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-between gap-3 mb-4">
            <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white shadow-sm mb-0 fdb-status-tabs" role="group" aria-label="Course status">
                <li class="nav-item" role="presentation">
                    <input class="btn-check" type="radio" name="course_type" value="current" id="fdbCourseCurrent"
                        {{ $courseType === 'current' ? 'checked' : '' }}>
                    <label class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill mb-0" for="fdbCourseCurrent">Active</label>
                </li>
                <li class="nav-item" role="presentation">
                    <input class="btn-check" type="radio" name="course_type" value="archived" id="fdbCourseArchived"
                        {{ $courseType === 'archived' ? 'checked' : '' }}>
                    <label class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill mb-0" for="fdbCourseArchived">Archived</label>
                </li>
            </ul>

            <div class="d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
                <button type="button" id="feedbackDbPrintBtn"
                    class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-3 py-2 rounded-1 fw-semibold shadow-sm"
                    title="Print report (LBSNAA layout)">
                    <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
                </button>
                <a href="#" id="feedbackDbPdfLink" target="_blank" rel="noopener"
                    class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-3 py-2 rounded-1 fw-semibold shadow-sm"
                    title="Download PDF">
                    <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
                </a>
                <div class="dropdown">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-1 px-2 py-2" data-bs-toggle="dropdown" aria-label="More export options">
                        <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-2">
                        <li>
                            <a href="#" id="feedbackDbExcelLink"
                                class="dropdown-item rounded-1 mx-2 py-2 d-inline-flex align-items-center"
                                title="Export to Excel">
                                <i class="bi bi-file-earmark-spreadsheet me-2 text-primary" aria-hidden="true"></i>Export Excel
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="card fdb-dt-card border-0 shadow-sm rounded-3">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3 mb-4 programme-dt-toolbar fdb-filters-row w-100">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <span class="programme-dt-filters-label">Filters</span>

                        <div class="programme-dt-filter-select">
                            <label for="courseSelect" class="visually-hidden">Program Name</label>
                            <select class="form-select fdb-filter-select" id="courseSelect" name="course_id" aria-label="Program name">
                                <option value="">Program Name</option>
                                @if (isset($courses) && $courses->count() > 0)
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                    @endforeach
                                @else
                                    <option value="" disabled>No courses available</option>
                                @endif
                            </select>
                        </div>

                        <div class="programme-dt-filter-select">
                            <label for="searchParam" class="visually-hidden">Filter By</label>
                            <select class="form-select fdb-filter-select" id="searchParam" name="search_param" aria-label="Filter by">
                                <option value="all">Filter By</option>
                                <option value="faculty">Faculty</option>
                                <option value="topic">Topic</option>
                            </select>
                        </div>

                        <div class="programme-dt-filter-select dynamic-filter-container d-none" id="facultyFilterContainer">
                            <label for="facultyFilter" class="visually-hidden">Select Faculty</label>
                            <select class="form-select fdb-filter-select" id="facultyFilter" name="faculty_id" aria-label="Select faculty">
                                <option value="">All Faculties</option>
                                @if (isset($faculties) && $faculties->count() > 0)
                                    @foreach ($faculties as $faculty)
                                        <option value="{{ $faculty->pk }}">{{ $faculty->full_name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="programme-dt-filter-select fdb-topic-filter dynamic-filter-container d-none" id="topicFilterContainer">
                            <label for="topicFilter" class="visually-hidden">Enter Topic</label>
                            <div class="input-group fdb-topic-input-group">
                                <input type="text" class="form-control fdb-filter-input" id="topicFilter" name="topic_value"
                                    placeholder="Topic name..." aria-label="Enter topic">
                                <button class="btn btn-outline-secondary fdb-topic-clear" type="button" id="clearTopicBtn" aria-label="Clear topic">
                                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <button type="button" class="btn programme-dt-btn-reset flex-shrink-0" id="clearFiltersBtn">Reset Filters</button>
                    </div>

                    <div class="fdb-table-search ms-xl-auto flex-shrink-0">
                        <div class="dropdown fdb-search-slot">
                            <button type="button"
                                class="btn fdb-search-trigger"
                                id="fdbSearchTrigger"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false"
                                aria-label="Search table">
                                <i class="bi bi-search" aria-hidden="true"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 p-3 fdb-table-search-menu">
                                <label for="tableSearch" class="form-label small text-secondary mb-2">Search</label>
                                <input type="search"
                                    class="form-control shadow-none"
                                    id="tableSearch"
                                    placeholder="Search table..."
                                    autocomplete="off"
                                    aria-label="Search within table">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="programme-dt-panel fdb-table-panel position-relative">
                    <div class="loading-overlay" id="loadingOverlay" aria-hidden="true">
                        <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>

                    <div class="table-responsive" id="tableContainer">
                        <table class="table align-middle mb-0 w-100 programme-dt-table fdb-feedback-table" id="feedbackTable">
                            <thead>
                                <tr>
                                    <th scope="col" class="fdb-col-sno">S. No.</th>
                                    <th scope="col">Faculty Name</th>
                                    <th scope="col">Course</th>
                                    <th scope="col">Faculty Address</th>
                                    <th scope="col">Topic</th>
                                    <th scope="col" class="text-center">Content %</th>
                                    <th scope="col" class="text-center">Presentation %</th>
                                    <th scope="col" class="text-center d-none d-lg-table-cell">Participants</th>
                                    <th scope="col" class="text-center d-none d-xl-table-cell">Session Date</th>
                                    <th scope="col" class="text-center">Comments</th>
                                </tr>
                            </thead>
                            <tbody id="feedbackTableBody">
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-5">
                                        <div class="py-3 fdb-empty-state">
                                            <i class="bi bi-database d-block mb-3 fdb-empty-icon" aria-hidden="true"></i>
                                            <p class="mb-0 fw-medium text-secondary">Select a program to load feedback data</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="fdb-table-footer programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 pt-3 border-top"
                    id="paginationSection" style="display: none;">
                    <nav aria-label="Feedback pagination" class="fdb-pagination-wrap">
                        <ul class="pagination fdb-pagination mb-0" id="paginationLinks"></ul>
                    </nav>
                    <div class="fdb-records-info programme-dt-count text-secondary small d-flex align-items-center gap-2 flex-wrap justify-content-end">
                        <span>Showing</span>
                        <select class="form-select form-select-sm fdb-page-size-select shadow-none" id="perPageSelect" aria-label="Items per page">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                        </select>
                        <span>of <strong class="text-body" id="paginationInfo">0</strong> items</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="commentsModal" tabindex="-1" aria-labelledby="commentsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-3 fdb-comments-modal">
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-semibold mb-0" id="commentsModalLabel">Feedback Comments</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <div id="commentsContent" class="fdb-comments-content"></div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0 justify-content-end">
                    <button type="button" class="btn btn-primary px-4 rounded-2 fdb-modal-close-btn" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
{{ $dataTable->scripts() }}
<script>
    const FEEDBACK_DB_COURSES_URL = @json($fr['database_courses']);
    const FEEDBACK_DB_FACULTIES_URL = @json($fr['database_faculties']);
    const FEEDBACK_DB_EXPORT_ROUTES = {
        print: @json($fr['database_print']),
        pdf: @json($fr['database_export_pdf']),
        excel: @json($fr['database_export_excel']),
    };

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
            fdbUpdateFilterSelectStyles();
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
                    fdbUpdateFilterSelectStyles();
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

                const searchTrigger = document.getElementById('fdbSearchTrigger');
                if (searchTrigger) {
                    const searchDropdown = searchTrigger.closest('.dropdown');
                    if (searchDropdown) {
                        searchDropdown.addEventListener('shown.bs.dropdown', function() {
                            const tableSearch = document.getElementById('tableSearch');
                            if (tableSearch) {
                                tableSearch.focus();
                            }
                        });
                    }
                }
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
                        sel.empty().append('<option value="">Program Name</option>');
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
                        fdbUpdateFilterSelectStyles();
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
                    u.searchParams.set('course_type', courseType);
                    window.history.replaceState({}, '', u.toString());
                } catch(e) {}
            })
            .catch(err => console.error(err));
        });

        // ── Comments modal handler (delegated) ──
        $(document).on('click', '.view-comments-btn', function(e) {
            e.preventDefault();
            const comments = $(this).data('comments');
            if (!comments) return;
            const modalEl = document.getElementById('commentsModal');
            $('#commentsContent').html(
                '<div style="max-height:400px;overflow-y:auto;">' +
                String(comments).split(' | ').map((c, i) =>
                    '<div class="d-flex gap-2 align-items-start border-bottom pb-2 mb-2">' +
                        '<span class="badge bg-primary bg-opacity-10 text-primary mt-1" style="min-width:22px;">' + (i+1) + '</span>' +
                        '<p class="mb-0 text-body-secondary" style="font-size:0.88rem;">' + $('<span>').text(c).html() + '</p>' +
                    '</div>'
                ).join('') + '</div>'
            );
            new bootstrap.Modal(modalEl).show();
        });

        // ── Export links ──
        function buildExportQuery() {
            const params = new URLSearchParams();
            const courseId = $('#courseSelect').val();
            const facultyId = $('#facultyFilter').val();
            const topicVal = $('#topicFilter').val();
            const condField = $('#conditionalField').val();
            const condOp = $('#conditionalOperator').val();
            const condVal = $('#conditionalValue').val();
            if (courseId) params.set('course_id', courseId);
            if (facultyId) params.set('faculty_id', facultyId);
            if (topicVal) params.set('topic_value', topicVal);
            if (condField) params.set('cond_field', condField);
            if (condOp) params.set('cond_operator', condOp);
            if (condVal) params.set('cond_value', condVal);

            // Pass DataTable global search term
            const searchVal = dtTable.search();
            if (searchVal) params.set('search_term', searchVal);

            // Pass visible columns
            const visibleCols = [];
            dtTable.columns().every(function(index) {
                if (this.visible()) {
                    const title = $(this.header()).text().trim();
                    if (title) visibleCols.push(index);
                }
            });
            params.set('visible_columns', visibleCols.join(','));

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
                $('#tableSearch').val('');

                $('.dynamic-filter-container').addClass('d-none').removeClass('d-block');

                currentFilters = {
                    course_id: '',
                    search_param: 'all',
                    faculty_id: '',
                    topic_value: ''
                };
                currentPage = 1;

                fdbUpdateFilterSelectStyles();
                showInitialMessage();
                syncFeedbackDbExportLinks();
            }

            function fdbUpdateFilterSelectStyles() {
                $('#courseSelect, #searchParam, #facultyFilter').each(function() {
                    $(this).toggleClass('fdb-filter-empty', !$(this).val());
                });
            }

            function showInitialMessage() {
                const hasCourses = $('#courseSelect option').length > 1;

                if (hasCourses) {
                    $('#feedbackTableBody').html(`
                <tr>
                    <td colspan="10" class="text-center text-muted py-5">
                        <div class="py-3">
                            <i class="bi bi-database d-block mb-3 fdb-empty-icon opacity-50"></i>
                            <p class="mb-0 fw-medium text-secondary">Select a program to view feedback data</p>
                        </div>
                    </td>
                </tr>
            `);
                } else {
                    $('#feedbackTableBody').html(`
                <tr>
                    <td colspan="10" class="text-center text-muted py-5">
                        <div class="py-3">
                            <i class="bi bi-exclamation-circle d-block mb-3 fdb-empty-icon opacity-50"></i>
                            <p class="mb-0 fw-medium text-secondary">No programs available. Please add courses first.</p>
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

                const apiUrl = `{{ $fr['database_data'] }}?${params.toString()}`;

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
                    <td class="text-secondary fdb-col-sno">${((currentPage - 1) * perPage) + index + 1}</td>
                    <td>
                        <a href="javascript:void(0)" class="fdb-faculty-name faculty-link"
                           data-faculty-id="${item.faculty_enc_id || ''}"
                           title="View faculty details">
                            ${item.faculty_name}
                        </a>
                    </td>
                    <td class="fdb-col-course">${item.course_name}</td>
                    <td class="fdb-col-address">
                        <span class="text-body-secondary small">
                            ${item.faculty_address || 'N/A'}
                            ${item.faculty_email ? `<br><a href="mailto:${item.faculty_email}" class="text-muted text-decoration-none">${item.faculty_email}</a>` : ''}
                        </span>
                    </td>
                    <td class="fdb-col-topic">
                        <span class="d-inline-block text-truncate fdb-topic-text" title="${escapeHtml(item.subject_topic || '')}">
                            ${escapeHtml(item.subject_topic || '')}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="fdb-pct ${getPercentageClass(item.avg_content_percent)}">
                            ${formatPercentage(item.avg_content_percent)}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="fdb-pct ${getPercentageClass(item.avg_presentation_percent)}">
                            ${formatPercentage(item.avg_presentation_percent)}
                        </span>
                    </td>
                    <td class="text-center d-none d-lg-table-cell">
                        <span class="badge rounded-pill fdb-participants-badge">${item.participant_count}</span>
                    </td>
                    <td class="text-center text-secondary text-nowrap d-none d-xl-table-cell">
                        <small>${formatDate(item.session_date)}</small>
                    </td>
                    <td class="text-center">
                        ${item.all_comments ?
                            `<button type="button" class="btn btn-link btn-sm p-0 fdb-view-comments view-comments-btn"
                                     data-comments="${escapeHtml(item.all_comments)}"
                                     title="View comments"
                                     aria-label="View comments">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>` :
                            '<span class="text-muted">—</span>'
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
                        const parts = comments.split(' | ').filter(Boolean);
                        $('#commentsContent').html(
                            parts.length === 1
                                ? `<p class="mb-0 text-body">${parts[0]}</p>`
                                : `<div class="fdb-comments-list">${parts.map((comment, i) => `
                                    <div class="fdb-comment-item ${i < parts.length - 1 ? 'border-bottom pb-3 mb-3' : ''}">
                                        <p class="mb-0 text-body">${comment}</p>
                                    </div>
                                `).join('')}</div>`
                        );
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

                $('#paginationInfo').text(totalRecords.toLocaleString());
                $('#perPageSelect').val(String(perPage));

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
                        <i class="bi bi-search d-block mb-3 fdb-empty-icon opacity-50"></i>
                        <p class="mb-0 fw-medium text-secondary">No feedback data found for the selected criteria</p>
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
                        <i class="bi bi-exclamation-triangle d-block mb-3 fdb-empty-icon opacity-50"></i>
                        <p class="mb-0 fw-medium text-danger">${message}</p>
                    </div>
                </td>
            </tr>
        `);
            }

            function formatPercentage(value) {
                const num = parseFloat(value) || 0;
                return num.toFixed(2);
            }

            function getPercentageClass(value) {
                const num = parseFloat(value) || 0;
                if (num >= 70) return 'fdb-pct-good';
                if (num >= 25) return 'fdb-pct-average';
                return 'fdb-pct-low';
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
