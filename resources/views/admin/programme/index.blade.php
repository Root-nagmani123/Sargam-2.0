/************* ✨ Windsurf Command 🌟 *************/
btn-outline-success btn-outline-secondary" style="border-color: #0dcaf7; background-color: transparent;">
@extends('admin.layouts.master')

@section('title', 'Course Master - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Course Master" />
    <x-session_message />

    <div>

        <div class="card border-0 shadow-sm rounded-4" style="border-left: 4px solid #004a93;">
            <div class="card-body py-4">

                <!-- Header -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <h3 class="fw-bold text-dark mb-2 mb-md-0">
                        <i class="bi bi-journal-text me-1"></i> Course Master
                    </h3>

                    <a href="{{ route('programme.create') }}" class="btn btn-primary btn-lg px-4 fw-semibold shadow-sm"
                        aria-label="Add new course">
                        <i class="bi bi-plus-circle me-2"></i>Add Course
                    </a>
                </div>

                <hr class="mt-0">

                <!-- Filter Buttons -->
                <div class="row mb-4">
                    <div class="col-12 d-flex justify-content-end">

                        <div class="btn-group shadow-sm rounded-pill overflow-hidden" role="group"
                            aria-label="Course Status Filter">

                            <!-- Active Button -->
                            <button type="button" id="filterActive" class="btn px-4 py-2 fw-semibold filter-btn"
                                aria-pressed="true" aria-label="Show active courses">
                                <i class="bi bi-check-circle me-2"></i> Active
                            </button>

                            <!-- Archive Button -->
                            <button type="button" id="filterArchive" class="btn px-4 py-2 fw-semibold filter-btn"
                                aria-pressed="false" aria-label="Show archived courses">
                                <i class="bi bi-archive me-2"></i> Archive
                            </button>
                        </div>

                    </div>
                </div>


                <!-- Table -->
                <div class="table-responsive-lg">
                    {!! $dataTable->table([
                    'class' => 'table table-hover table-striped align-middle mb-0'
                    ]) !!}
                </div>

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
                $(this).removeClass('btn-success btn-secondary ')
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
/******* af534823-a433-4e3f-83eb-6e41ccaafd86 *******/