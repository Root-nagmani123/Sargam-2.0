@extends('admin.layouts.master')

@section('title', 'Course Master - Sargam | Lal Bahadur')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
@endpush

@section('setup_content')
<style>
/* Fix dropdown visibility in table */
.table-responsive {
    overflow: visible !important;
}

.table td {
    overflow: visible !important;
    vertical-align: middle;
}

.action-dropdown {
    position: static;
}

.action-dropdown .dropdown-menu {
    z-index: 1050 !important;
    position: fixed !important;
}

/* Ensure dropdown items are clickable */
.dropdown-item {
    cursor: pointer;
}

.btn-group[role="group"] .btn {
    transition: all 0.3s ease-in-out;
    border-radius: 0;
    /* Reset for pill-style container */
}

.btn-group[role="group"] .btn:first-child {
    border-top-left-radius: 50rem !important;
    border-bottom-left-radius: 50rem !important;
}

.btn-group[role="group"] .btn:last-child {
    border-top-right-radius: 50rem !important;
    border-bottom-right-radius: 50rem !important;
}

/* Hover + Active States */
.btn-group .btn:hover {
    transform: translateY(-1px);
}

.btn-group .btn.active {
    box-shadow: inset 0 0 0 2px #fff, 0 0 0 3px rgba(0, 123, 255, 0.3);
}

/* Accessibility: Focus ring */
.btn:focus-visible {
    outline: 3px solid #0d6efd;
    outline-offset: 2px;
}

/* Choices + Bootstrap integration for course filter */
.programme-choices-bootstrap .choices__inner.form-select {
    background-color: var(--bs-body-bg);
    border: var(--bs-border-width) solid var(--bs-border-color);
    min-height: calc(1.5em + 0.75rem + var(--bs-border-width) * 2);
    padding-top: 0.375rem;
    padding-bottom: 0.375rem;
    background-image: none !important;
    padding-inline-end: 2.25rem;
}

.programme-choices-bootstrap .choices.is-focused .choices__inner.form-select,
.programme-choices-bootstrap .choices.is-open .choices__inner.form-select {
    border-color: var(--bs-focus-border-color);
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(var(--bs-focus-ring-rgb), 0.25);
}

.programme-choices-bootstrap .choices__list--dropdown.dropdown-menu,
.programme-choices-bootstrap .choices__list[aria-expanded].dropdown-menu {
    border: var(--bs-border-width) solid var(--bs-border-color);
}

.programme-status-pill {
    color: var(--bs-primary);
    background: #fff;
    border: 1px solid transparent;
    transition: background-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
}

.programme-status-pill:not(.active):hover {
    background: #fff;
    border-color: rgba(var(--bs-primary-rgb), 0.35);
}

.programme-status-pill.active {
    background: var(--bs-primary);
    color: #fff;
    box-shadow: 0 2px 8px rgba(0, 74, 147, 0.25);
}
</style>
<div class="container-fluid">
    <x-breadcrum
        title="Course Master"
        buttonText="Add Course"
        :buttonUrl="route('programme.create')"
        buttonIcon="add"
        buttonClass="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-2 fw-semibold shadow-sm"
    />

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <ul class="nav nav-pills gap-2 p-1 bg-light rounded-pill border" role="group" aria-label="Filter courses by status">
                    <li class="nav-item" role="presentation">
                        <button type="button"
                            class="nav-link rounded-pill px-4 py-2 fw-semibold programme-status-pill active"
                            id="filterActive"
                            aria-pressed="true"
                            aria-current="true">
                            Active
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button"
                            class="nav-link rounded-pill px-4 py-2 fw-semibold programme-status-pill"
                            id="filterArchive"
                            aria-pressed="false">
                            Archived
                        </button>
                    </li>
                </ul>
            </div>

            <div class="row mb-3 g-3 programme-choices-bootstrap">
                    <div class="col-4">
                        <label for="courseFilter" class="form-label mb-1">Course Name</label>
                        <select id="courseFilter" class="form-control js-programme-choice rounded-1">
                            <option value="">All Courses</option>
                            @foreach($courses ?? [] as $pk => $name)
                            <option value="{{ $pk }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-2">
                        <button type="button" class="btn btn-outline-secondary mt-4" id="resetFilters">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                        </button>
                    </div>
                </div>
            <div class="table-responsive rounded-2 border">
                {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0']) !!}
            </div>

        </div>
    </div>
</div>

<!-- Course View Modal -->
<div class="modal fade" id="viewCourseModal" tabindex="-1" aria-labelledby="viewCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="viewCourseModalLabel">Course Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="courseDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading course details...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection
@push('scripts')
{!! $dataTable->scripts() !!}

<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
$(document).ready(function() {
    var table;
    var currentFilter = 'active'; // Set Active as default
    var courseChoices = null;

    var programmeChoiceOpts = {
        searchEnabled: true,
        shouldSort: false,
        itemSelectText: '',
        allowHTML: false,
        classNames: {
            containerOuter: ['choices', 'w-100'],
            containerInner: ['choices__inner', 'form-select', 'shadow-sm'],
            input: ['choices__input', 'form-control', 'form-control-sm', 'border-0', 'shadow-none', 'my-1'],
            inputCloned: ['choices__input--cloned'],
            list: ['choices__list'],
            listItems: ['choices__list--multiple'],
            listSingle: ['choices__list--single'],
            listDropdown: ['choices__list--dropdown', 'dropdown-menu', 'mt-1', 'p-0', 'shadow-sm', 'w-100'],
            item: ['choices__item', 'dropdown-item', 'rounded-0'],
            itemSelectable: ['choices__item--selectable'],
            itemDisabled: ['choices__item--disabled', 'disabled'],
            itemChoice: ['choices__item--choice'],
            description: ['choices__description', 'small', 'text-muted'],
            placeholder: ['choices__placeholder', 'text-muted', 'opacity-75'],
            group: ['choices__group'],
            groupHeading: ['choices__heading', 'dropdown-header', 'text-uppercase', 'small'],
            button: ['choices__button'],
            activeState: ['is-active'],
            focusState: ['is-focused'],
            openState: ['is-open'],
            disabledState: ['is-disabled'],
            highlightedState: ['is-highlighted', 'active'],
            flippedState: ['is-flipped'],
            loadingState: ['is-loading'],
            invalidState: ['is-invalid'],
            notice: ['choices__notice', 'dropdown-item-text', 'text-muted', 'small', 'py-2'],
            addChoice: ['choices__item--selectable', 'add-choice'],
            noResults: ['has-no-results'],
            noChoices: ['has-no-choices'],
        }
    };

    function initCourseFilterChoices() {
        if (typeof Choices === 'undefined') {
            return;
        }

        var courseFilterEl = document.getElementById('courseFilter');
        if (!courseFilterEl || courseFilterEl.dataset.choicesInitialized === 'true') {
            return;
        }

        courseChoices = new Choices(courseFilterEl, programmeChoiceOpts);
        courseFilterEl._choicesInstance = courseChoices;
        courseFilterEl.dataset.choicesInitialized = 'true';
    }

    function rebuildCourseFilterChoices() {
        if (typeof Choices === 'undefined') {
            return;
        }

        var courseFilterEl = document.getElementById('courseFilter');
        if (!courseFilterEl || !courseFilterEl._choicesInstance) {
            initCourseFilterChoices();
            return;
        }

        courseFilterEl._choicesInstance.destroy();
        courseFilterEl.dataset.choicesInitialized = 'false';
        courseFilterEl._choicesInstance = null;
        courseChoices = null;
        initCourseFilterChoices();
    }

    // Wait for DataTable to be initialized
    setTimeout(function() {
        table = $('#coursemaster-table').DataTable();
        initCourseFilterChoices();

        // Initialize dropdowns after table loads
        initializeDropdowns();

        // Set initial active state - Active button is already styled as active in HTML
        // No need to change styling initially

        // Function to load courses by status
        function loadCoursesByStatus(status) {
            $.ajax({
                url: '{{ route("programme.get.courses.by.status") }}',
                type: 'GET',
                data: {
                    status: status
                },
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
                        rebuildCourseFilterChoices();

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
            dropdownElementList.forEach(function(dropdownToggleEl) {
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
            $('#filterActive, #filterArchive')
                .removeClass('active')
                .attr('aria-pressed', 'false')
                .removeAttr('aria-current');

            activeBtn
                .addClass('active')
                .attr('aria-pressed', 'true')
                .attr('aria-current', 'true');
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
        $('#courseFilter').on('change', function() {
            table.ajax.reload();
            initializeDropdowns();
        });

        // Handle reset filters
        $('#resetFilters').on('click', function() {
            $('#courseFilter').val('');
            rebuildCourseFilterChoices();
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

        // ── Custom SweetAlert: Delete Course ──
        $(document).on('click', '.programme-delete-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var deleteUrl = $btn.data('delete-url');
            var csrfToken = $btn.data('csrf');

            Swal.fire({
                html: '<div style="text-align:center;">' +
                    '<div style="margin:0 auto 16px;width:72px;height:72px;border-radius:50%;border:4px solid #dc3545;display:flex;align-items:center;justify-content:center;">' +
                    '<span class="material-icons material-symbols-rounded" style="font-size:36px;color:#dc3545;">priority_high</span></div>' +
                    '<h4 style="font-weight:700;margin-bottom:4px;">Delete Course?</h4>' +
                    '<p style="color:#6c757d;margin:0;">Are you sure you want to delete this course?</p></div>',
                showCancelButton: true,
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel, Keep it',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#fff',
                customClass: {
                    cancelButton: 'btn btn-outline-dark border-2 fw-semibold px-4',
                    confirmButton: 'btn btn-danger fw-semibold px-4',
                    actions: 'gap-3 mt-2'
                },
                buttonsStyling: false,
                reverseButtons: true,
                showCloseButton: false,
                focusCancel: true
            }).then(function(result) {
                if (result.isConfirmed) {
                    var $form = $('<form>', {
                        action: deleteUrl,
                        method: 'POST'
                    });
                    $form.append($('<input>', {
                        type: 'hidden',
                        name: '_token',
                        value: csrfToken
                    }));
                    $form.append($('<input>', {
                        type: 'hidden',
                        name: '_method',
                        value: 'DELETE'
                    }));
                    $('body').append($form);
                    $form.submit();
                }
            });
        });

        // Status toggle uses global .status-toggle handler in custom.js (form-switch)
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
                            <div class="row">
                                <div class="col-12">
                                    <h4 class="text-primary mb-4">${course.course_name}</h4>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Course Short Name:</strong>
                                    <p class="text-muted">${course.course_short_name || 'Not Available'}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Course Year:</strong>
                                    <p class="text-muted">${course.course_year || 'Not Available'}</p>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Start Date:</strong>
                                    <p class="text-muted">${course.start_date}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>End Date:</strong>
                                    <p class="text-muted">${course.end_date}</p>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Course Coordinator:</strong>
                                    <div class="d-flex align-items-center mt-2">
                                        ${course.coordinator_photo ? 
                                            `<img src="${course.coordinator_photo}" alt="Coordinator Photo" class="rounded-circle me-2" style="width: 50px; height: 50px; object-fit: cover;">` : 
                                            `<div class="bg-secondary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                <i class="bi bi-person text-white"></i>
                                            </div>`
                                        }
                                        <span>${course.coordinator_name}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-12">
                                    <strong>Assistant Coordinators:</strong>
                                    <div class="mt-2">
                        `;

                    if (course.assistant_coordinators && course.assistant_coordinators.length > 0) {
                        course.assistant_coordinators.forEach(function(coordinator, index) {
                            var photo = course.assistant_coordinator_photos[index] || null;
                            content += `
                                    <div class="d-flex align-items-center mb-2">
                                        ${photo ? 
                                            `<img src="${photo}" alt="Assistant Coordinator Photo" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">` : 
                                            `<div class="bg-secondary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-person text-white"></i>
                                            </div>`
                                        }
                                        <span>${coordinator}</span>
                                    </div>
                                `;
                        });
                    } else {
                        content += '<p class="text-muted">No Assistant Coordinators assigned</p>';
                    }

                    content += `
                                    </div>
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