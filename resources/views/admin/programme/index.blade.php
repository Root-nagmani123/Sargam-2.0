@extends('admin.layouts.master')

@section('title', 'Course Master - Sargam | Lal Bahadur')

@section('setup_content')
<style>
/* Programme Index - Bootstrap 5.3 enhanced UI */
:root {
    --programme-primary: #004a93;
    --programme-primary-subtle: rgba(0, 74, 147, 0.08);
}

/* Fix dropdown visibility in table */
.table-responsive {
    overflow: visible !important;
}

.table td {
    overflow: visible !important;
    vertical-align: middle;
}

.action-dropdown { position: static; }

.dropdown-menu {
    z-index: 1050 !important;
    position: fixed !important;
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.dropdown-item { cursor: pointer; }

.btn-group[role="group"] .btn {
    transition: all 0.25s ease;
    border-radius: 0;
}

.btn-group[role="group"] .btn:first-child {
    border-top-left-radius: 50rem !important;
    border-bottom-left-radius: 50rem !important;
}

.btn-group[role="group"] .btn:last-child {
    border-top-right-radius: 50rem !important;
    border-bottom-right-radius: 50rem !important;
}

.btn-group .btn:hover { transform: translateY(-1px); }

.btn-group .btn.active {
    box-shadow: inset 0 0 0 2px #fff, 0 0 0 3px rgba(0, 74, 147, 0.25);
}

.btn:focus-visible {
    outline: 3px solid var(--programme-primary);
    outline-offset: 2px;
}

/* Table enhancements */
#coursemaster-table_wrapper .dataTables_wrapper {
    padding: 0;
}

#coursemaster-table thead th {
    background: linear-gradient(180deg, var(--programme-primary) 0%, #003d7a 100%);
    color: #fff !important;
    font-weight: 600;
    padding: 0.875rem 1rem;
    border: none;
    white-space: nowrap;
}

#coursemaster-table tbody tr:hover {
    background-color: var(--programme-primary-subtle) !important;
}

#coursemaster-table .form-check-input:checked {
    background-color: var(--programme-primary);
    border-color: var(--programme-primary);
}

@media (prefers-reduced-motion: reduce) {
    .btn-group .btn:hover { transform: none; }
}

/* Responsive - tablet and below */
@media (max-width: 991.98px) {
    .programme-index.container-fluid { padding-left: 0.75rem !important; padding-right: 0.75rem !important; }
    .programme-index .card-body.p-4.p-lg-5 { padding: 1rem 1.25rem !important; }
    .programme-index .datatables .row.g-3 > [class*="col-"] { margin-bottom: 0.5rem; }
}

@media (max-width: 767.98px) {
    .programme-index.container-fluid { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
    .programme-index .card-body.p-4.p-lg-5 { padding: 1rem !important; }
    .programme-index #coursemaster-table_wrapper .dataTables_length,
    .programme-index #coursemaster-table_wrapper .dataTables_filter {
        text-align: left !important;
    }
    .programme-index #coursemaster-table_wrapper .dataTables_length select {
        margin: 0 0.5rem 0 0;
    }
    .programme-index .btn-group[role="group"] .btn span { display: inline; }
    /* Stack header: title above actions */
    .programme-index section[role="region"] .col-12.col-md-4 { margin-bottom: 0.75rem; }
    .programme-index section[role="region"] .d-flex.flex-wrap { justify-content: flex-start !important; }
    /* Filter row: full width, stacked */
    .programme-index .row.g-3.mb-4.align-items-end .col-12 { margin-bottom: 0.5rem; }
    .programme-index .row.g-3.mb-4 .btn-outline-secondary { width: 100%; justify-content: center; }
    /* Table horizontal scroll */
    .programme-index .table-responsive { overflow-x: auto !important; overflow-y: visible; -webkit-overflow-scrolling: touch; margin: 0 -0.5rem; }
    .programme-index .table-responsive .table { min-width: 600px; margin-bottom: 0; }
    .programme-index #coursemaster-table thead th { padding: 0.625rem 0.75rem; font-size: 0.8125rem; }
    .programme-index #coursemaster-table tbody td { padding: 0.625rem 0.75rem; font-size: 0.875rem; }
}

@media (max-width: 575.98px) {
    .programme-index.container-fluid { padding-left: 0.375rem !important; padding-right: 0.375rem !important; }
    .programme-index .card-body.p-4.p-lg-5 { padding: 0.75rem !important; }
    .programme-index .btn-group[role="group"] { flex-direction: column; width: 100%; }
    .programme-index .btn-group[role="group"] .btn { width: 100%; border-radius: 0.375rem !important; padding: 0.5rem 1rem; }
    .programme-index .btn-group[role="group"] .btn:first-child,
    .programme-index .btn-group[role="group"] .btn:last-child { border-radius: 0.375rem !important; }
    .programme-index a.btn.btn-primary[href*="programme.create"] { width: 100%; justify-content: center; }
    .programme-index .d-flex.flex-wrap.gap-3 { gap: 0.5rem !important; }
    #viewCourseModal .modal-dialog { margin: 0.5rem; max-width: calc(100% - 1rem); }
    #viewCourseModal .modal-body .row.g-3 .col-md-6 { flex: 0 0 100%; max-width: 100%; }
    #viewCourseModal .modal-body .d-flex.align-items-center { flex-wrap: wrap; }
}
</style>
<div class="container-fluid px-3 px-md-4 py-3 programme-index">
    <x-breadcrum title="Course Master" />
    <div class="datatables">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden border-start border-4 border-primary">
            <div class="card-body p-4 p-lg-5">

                <section class="row align-items-center mb-4 g-3" role="region" aria-labelledby="courseMasterHeading">
                    <div class="col-12 col-md-4 col-lg-3">
                        <h1 id="courseMasterHeading" class="h4 fw-bold mb-2 mb-md-0 d-flex align-items-center gap-2">
                            <span class="rounded-2 p-2 bg-primary bg-opacity-10">
                                <i class="bi bi-journal-bookmark-fill text-primary"></i>
                            </span>
                            Course Master
                        </h1>
                    </div>
                    <div class="col-12 col-md-8 col-lg-9">
                        <div class="d-flex flex-wrap justify-content-md-end align-items-center gap-3">
                            <div class="btn-group shadow-sm rounded-pill" role="group" aria-label="Filter courses by status">
                                <button type="button" class="btn btn-success px-4 fw-semibold active" id="filterActive"
                                    aria-pressed="true" aria-current="true">
                                    <i class="bi bi-check-circle me-1" aria-hidden="true"></i>
                                    <span>Active</span>
                                </button>
                                <button type="button" class="btn btn-outline-secondary px-4 fw-semibold" id="filterArchive"
                                    aria-pressed="false">
                                    <i class="bi bi-archive me-1" aria-hidden="true"></i>
                                    <span>Archived</span>
                                </button>
                            </div>
                            <a href="{{ route('programme.create') }}"
                                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 shadow-sm"
                                aria-label="Add a new course">
                                <iconify-icon icon="ep:circle-plus-filled" aria-hidden="true"></iconify-icon>
                                <span class="fw-semibold">Add Course</span>
                            </a>
                        </div>
                    </div>
                </section>

                <div class="border-top pt-4 mt-2"></div>

                <!-- Filters - Bootstrap 5.3 -->
                <div class="row g-3 mb-4 align-items-end">
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label for="courseFilter" class="form-label fw-semibold small text-body-secondary text-uppercase mb-2">Course Name</label>
                        <select id="courseFilter" class="form-select">
                            <option value="">All Courses</option>
                            @foreach($courses ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" id="resetFilters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            <span>Reset Filters</span>
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table align-middle mb-0']) !!}
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Course View Modal - Bootstrap 5.3 enhanced -->
<div class="modal fade" id="viewCourseModal" tabindex="-1" aria-labelledby="viewCourseModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <div class="modal-header border-0 py-4 text-white" style="background: linear-gradient(135deg, #0dcaf0 0%, #0aa2c0 100%);">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="viewCourseModalLabel">
                    <i class="bi bi-info-circle-fill"></i>
                    Course Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="courseDetailsContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-body-secondary">Loading course details...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-body-tertiary py-3">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection
@push('scripts')
{!! $dataTable->scripts() !!}

<script>
$(document).ready(function() {
    var table;
    var currentFilter = 'active'; // Set Active as default

    // Wait for DataTable to be initialized
    setTimeout(function() {
        table = $('#coursemaster-table').DataTable();
        
        // Initialize dropdowns after table loads
        initializeDropdowns();

        // Set initial active state - Active button is already styled as active in HTML
        // No need to change styling initially

        // Function to load courses by status
        function loadCoursesByStatus(status) {
            $.ajax({
                url: '{{ route("programme.get.courses.by.status") }}',
                type: 'GET',
                data: { status: status },
                success: function(response) {
                    if (response.success) {
                        var courseFilter = $('#courseFilter');
                        var currentValue = courseFilter.val();
                        
                        // Clear existing options except "All Courses"
                        courseFilter.find('option:not(:first)').remove();
                        
                        // Add new course options
                        $.each(response.courses, function(pk, name) {
                            courseFilter.append($('<option>', {
                                value: pk,
                                text: name
                            }));
                        });
                        
                        // Reset to "All Courses" when status changes
                        courseFilter.val('');
                        
                        // Reload table
                        table.ajax.reload();
                        initializeDropdowns();
                    }
                },
                error: function(xhr) {
                    console.error('Error loading courses:', xhr);
                }
            });
        }

        // Filter button click handlers
        $('#filterActive').on('click', function() {
            setActiveButton($(this));
            currentFilter = 'active';
            loadCoursesByStatus('active');
        });

        $('#filterArchive').on('click', function() {
            setActiveButton($(this));
            currentFilter = 'archive';
            loadCoursesByStatus('archive');
        });

        // Function to initialize dropdowns
        function initializeDropdowns() {
            var dropdownElementList = document.querySelectorAll('[data-bs-toggle="dropdown"]');
            dropdownElementList.forEach(function (dropdownToggleEl) {
                // Dispose of existing dropdown instance if any
                try {
                    var existingDropdown = bootstrap.Dropdown.getInstance(dropdownToggleEl);
                    if (existingDropdown) {
                        existingDropdown.dispose();
                    }
                } catch (e) {
                    // Instance doesn't exist, continue
                }
                
                // Create new dropdown instance
                try {
                    new bootstrap.Dropdown(dropdownToggleEl);
                } catch (e) {
                    console.error('Error initializing dropdown:', e);
                }
            });
        }

        // Function to set active button styling
        function setActiveButton(activeBtn) {
            // Reset all buttons to outline style
            $('#filterActive')
                .removeClass('btn-success active text-white')
                .addClass('btn-outline-success')
                .attr('aria-pressed', 'false');

            $('#filterArchive')
                .removeClass('btn-secondary active text-white')
                .addClass('btn-outline-secondary')
                .attr('aria-pressed', 'false');

            // Set the active button
            if (activeBtn.attr('id') === 'filterActive') {
                activeBtn.removeClass('btn-outline-success')
                    .addClass('btn-success text-white active')
                    .attr('aria-pressed', 'true');
            } else if (activeBtn.attr('id') === 'filterArchive') {
                activeBtn.removeClass('btn-outline-secondary')
                    .addClass('btn-secondary text-white active')
                    .attr('aria-pressed', 'true');
            }
        }

        // Pass filter parameter to server
        $('#coursemaster-table').on('preXhr.dt', function(e, settings, data) {
            data.status_filter = currentFilter;
            var courseFilter = $('#courseFilter').val();
            if (courseFilter) {
                data.course_filter = courseFilter;
            }
        });

        // Reinitialize dropdowns after table draw
        $('#coursemaster-table').on('draw.dt', function() {
            initializeDropdowns();
        });

        // Handle dropdown toggle with event delegation
        $(document).on('click', '[data-bs-toggle="dropdown"]', function(e) {
            // Bootstrap will handle the toggle, just ensure it's initialized
            var el = this;
            if (!bootstrap.Dropdown.getInstance(el)) {
                new bootstrap.Dropdown(el);
            }
        });

        // Handle course filter change
        $('#courseFilter').on('change', function () {
            table.ajax.reload();
            initializeDropdowns();
        });

        // Handle reset filters
        $('#resetFilters').on('click', function () {
            $('#courseFilter').val('');
            currentFilter = 'active'; // Reset to active by default
            setActiveButton($('#filterActive'));
            loadCoursesByStatus('active');
        });

        // Handle view course button click
        $(document).on('click', '.view-course-btn', function() {
            var courseId = $(this).data('id');
            console.log('Course ID:', courseId); // Debug log
            loadCourseDetails(courseId);
        });
    }, 100);

    // Function to load course details
    function loadCourseDetails(courseId) {
        var url = '{{ route("programme.view", ":id") }}'.replace(':id', courseId);
        console.log('Request URL:', url); // Debug log

        $.ajax({
            url: url,
            type: 'GET',
            beforeSend: function() {
                $('#courseDetailsContent').html(`
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading course details...</p>
                        </div>
                    `);
            },
            success: function(response) {
                if (response.success) {
                    var course = response.course;
                    var content = `
                            <div class="mb-4">
                                <h4 class="fw-bold text-primary mb-0">${course.course_name}</h4>
                                <span class="badge bg-primary bg-opacity-10 text-primary mt-2">${course.course_short_name || 'N/A'}</span>
                            </div>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="p-3 bg-body-tertiary rounded-3 border-start border-3 border-primary">
                                        <label class="form-label small text-body-secondary text-uppercase fw-semibold mb-1">Course Short Name</label>
                                        <p class="mb-0 fw-medium">${course.course_short_name || 'Not Available'}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 bg-body-tertiary rounded-3 border-start border-3 border-primary">
                                        <label class="form-label small text-body-secondary text-uppercase fw-semibold mb-1">Course Year</label>
                                        <p class="mb-0 fw-medium">${course.course_year || 'Not Available'}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 bg-body-tertiary rounded-3 border-start border-3 border-info">
                                        <label class="form-label small text-body-secondary text-uppercase fw-semibold mb-1">Start Date</label>
                                        <p class="mb-0 fw-medium">${course.start_date}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 bg-body-tertiary rounded-3 border-start border-3 border-info">
                                        <label class="form-label small text-body-secondary text-uppercase fw-semibold mb-1">End Date</label>
                                        <p class="mb-0 fw-medium">${course.end_date}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="mb-4">
                                <h6 class="fw-semibold text-body-secondary mb-3 d-flex align-items-center gap-2">
                                    <i class="bi bi-person-badge-fill text-primary"></i>Course Coordinator
                                </h6>
                                <div class="d-flex align-items-center gap-3 p-3 bg-body-tertiary rounded-3">
                                    ${course.coordinator_photo ? 
                                        `<img src="${course.coordinator_photo}" alt="Coordinator" class="rounded-circle object-fit-cover" style="width: 48px; height: 48px;">` : 
                                        `<div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                            <i class="bi bi-person text-white"></i>
                                        </div>`
                                    }
                                    <span class="fw-semibold">${course.coordinator_name}</span>
                                </div>
                            </div>
                            
                            <div>
                                <h6 class="fw-semibold text-body-secondary mb-3 d-flex align-items-center gap-2">
                                    <i class="bi bi-people-fill text-primary"></i>Assistant Coordinators
                                </h6>
                                <div class="d-flex flex-column gap-2">
                        `;

                    if (course.assistant_coordinators && course.assistant_coordinators.length > 0) {
                        course.assistant_coordinators.forEach(function(coordinator, index) {
                            var photo = course.assistant_coordinator_photos[index] || null;
                            content += `
                                    <div class="d-flex align-items-center gap-3 p-2 bg-body-tertiary rounded-3">
                                        ${photo ? 
                                            `<img src="${photo}" alt="Assistant" class="rounded-circle object-fit-cover" style="width: 36px; height: 36px;">` : 
                                            `<div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                                                <i class="bi bi-person text-white" style="font-size: 0.875rem;"></i>
                                            </div>`
                                        }
                                        <span>${coordinator}</span>
                                    </div>
                                `;
                        });
                    } else {
                        content += '<p class="text-muted mb-0 py-2">No Assistant Coordinators assigned</p>';
                    }

                    content += `
                                </div>
                            </div>
                        `;

                    $('#courseDetailsContent').html(content);
                } else {
                    $('#courseDetailsContent').html(`
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i>
                                ${response.message || 'Failed to load course details'}
                            </div>
                        `);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error
                });

                var errorMessage = 'Error loading course details. Please try again.';
                if (xhr.status === 404) {
                    errorMessage = 'Course not found.';
                } else if (xhr.status === 400) {
                    errorMessage = 'Invalid course ID.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error. Please try again later.';
                }

                $('#courseDetailsContent').html(`
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i>
                            ${errorMessage}
                        </div>
                    `);
            }
        });
    }
});
</script>
@endpush