@extends('admin.layouts.master')

@section('title', 'Course Master - Sargam | Lal Bahadur')

@section('content')
<style>
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

/* Better contrast for GIGW compliance */
.btn-outline-secondary {
    color: #333;
    border-color: #999;
}

.btn-outline-secondary:hover {
    background-color: #f8f9fa;
    border-color: #666;
}
</style>
<div class="container-fluid">
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">

                <div class="row">
                    <div class="col-6">
                        <h4>Course Master</h4>
                    </div>
                    <div class="col-6">
                        <div class="d-flex justify-content-end align-items-center gap-2">

                            <!-- Add Group Mapping -->
                            <a href="{{route('programme.create')}}"
                                class="btn btn-primary d-flex align-items-center">
                                <iconify-icon icon="ep:circle-plus-filled" class="me-1"></iconify-icon>
                                Add Course
                            </a>

                            <!-- Search Expand -->
                            <div class="search-expand d-flex align-items-center">
                                <a href="javascript:void(0)" id="searchToggle">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">search</i>
                                </a>

                                <input type="text" class="form-control search-input ms-2" id="searchInput"
                                    placeholder="Search…" aria-label="Search">
                            </div>

                        </div>
                    </div>
                </div>
                <hr>

                <!-- Filter Buttons -->
                <div class="row mb-3">
                    <div class="col-12 text-end">
                        <div class="btn-group shadow-sm rounded-pill overflow-hidden" role="group"
                            aria-label="Course Status Filter">
                            <button type="button" class="btn btn-success px-4 fw-semibold active" id="filterActive"
                                aria-pressed="true">
                                <i class="bi bi-check-circle me-1"></i> Active
                            </button>
                            <button type="button" class="btn btn-outline-secondary px-4 fw-semibold" id="filterArchive"
                                aria-pressed="false">
                                <i class="bi bi-archive me-1"></i> Archive
                            </button>
                        </div>
                    </div>

                </div>
                <div class="table-responsive">

                    {!! $dataTable->table(['class' => 'table']) !!}
                </div>

            </div>
        </div>
        <!-- end Zero Configuration -->
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

<script>
$(document).ready(function() {
    var table;
    var currentFilter = 'active'; // Set Active as default

    // Wait for DataTable to be initialized
    setTimeout(function() {
        table = $('#coursemaster-table').DataTable();

        // Set initial active state - Active button is already styled as active in HTML
        // No need to change styling initially

        // Filter button click handlers
        $('#filterActive').on('click', function() {
            setActiveButton($(this));
            currentFilter = 'active';
            table.ajax.reload();
        });

        $('#filterArchive').on('click', function() {
            setActiveButton($(this));
            currentFilter = 'archive';
            table.ajax.reload();
        });

        // Function to set active button styling
        function setActiveButton(activeBtn) {
            $('#filterActive, #filterArchive').each(function() {
                $(this).removeClass('btn-success btn-secondary')
                    .addClass('btn-outline-success btn-outline-secondary');
            });

            if (activeBtn.attr('id') === 'filterActive') {
                activeBtn.removeClass('btn-outline-success').addClass('btn-success');
            } else if (activeBtn.attr('id') === 'filterArchive') {
                activeBtn.removeClass('btn-outline-secondary').addClass('btn-secondary');
            }
        }

        // Pass filter parameter to server
        $('#coursemaster-table').on('preXhr.dt', function(e, settings, data) {
            data.status_filter = currentFilter;
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