@extends('admin.layouts.master')

@section('title', 'Course Master - Sargam | Lal Bahadur')

@section('content')
<style>
    /* ---- Modern Action Icon Buttons ---- */
.action-btn {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: 1px solid #d8d8d8;
    background: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: 0.25s ease;
    cursor: pointer;
}

.action-btn span.material-icons {
    font-size: 20px;
    color: #4a4a4a;
}

/* ---- EDIT BUTTON ---- */
.edit-btn:hover {
    background: #0d6efd;
    border-color: #0d6efd;
}

.edit-btn:hover .material-icons {
    color: #ffffff;
}

/* ---- DELETE BUTTON ---- */
.delete-btn:hover {
    background: #dc3545;
    border-color: #dc3545;
}

.delete-btn:hover .material-icons {
    color: #ffffff;
}

/* ---- Disabled Delete Button ---- */
.disabled-btn {
    opacity: 0.5;
    cursor: not-allowed !important;
    background: #f3f3f3;
}

.disabled-btn .material-icons {
    color: #999 !important;
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

/* Better contrast for GIGW compliance */
.btn-outline-secondary {
    color: #333;
    border-color: #999;
}

.btn-outline-secondary:hover {
    background-color: #f8f9fa;
    border-color: #666;
}
/* ===========================
   CARD LOOK (LIKE ATTACHED)
   =========================== */
.custom-table-card {
    border-left: 4px solid #b72a2a; /* same red as screenshot */
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

/* ===========================
   TABLE HEADER (ROUNDED, RED)
   =========================== */
.mdodutytypemaster-table thead th {
    background-color: #b72a2a !important;
    color: #fff !important;
    padding: 14px 12px !important;
    font-weight: 600 !important;
    border: none !important;
    white-space: nowrap;
}

.mdodutytypemaster-table thead tr:first-child th:first-child {
    border-top-left-radius: 10px !important;
}
.mdodutytypemaster-table thead tr:first-child th:last-child {
    border-top-right-radius: 10px !important;
}

/* ===========================
   ROW STYLING
   =========================== */
.mdodutytypemaster-table tbody tr:nth-child(even) {
    background-color: #f8f9fa !important;
}

.mdodutytypemaster-table tbody tr:nth-child(odd) {
    background-color: #ffffff !important;
}

.mdodutytypemaster-table tbody td {
    vertical-align: middle !important;
    padding: 10px 12px !important;
    border-color: #dee2e6 !important;
}

/* ===========================
   ACTION BUTTONS (MATCHING UI)
   =========================== */
.action-btn {
    width: 34px;
    height: 34px;
    display: inline-flex;
    justify-content: center;
    align-items: center;
    border-radius: 8px;
    background: #fff;
    border: 1px solid #cfd4da;
    transition: 0.25s;
    cursor: pointer;
}

.action-btn:hover {
    transform: translateY(-2px);
}

.edit-btn:hover {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
.edit-btn:hover .material-icons {
    color: #fff;
}

.delete-btn:hover {
    background-color: #dc3545;
    border-color: #dc3545;
}
.delete-btn:hover .material-icons {
    color: #fff;
}

.action-btn .material-icons {
    font-size: 20px;
    color: #444;
}

.disabled-btn {
    opacity: 0.4;
    cursor: not-allowed;
}

/* ===========================
   SWITCH (MATCHING STYLE)
   =========================== */
.form-check-input {
    width: 48px !important;
    height: 20px !important;
    cursor: pointer;
}

.form-check-input:checked {
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
}
/* Card styling */
.custom-table-card {
    border-radius: 12px;
    background: #fff;
}

/* Table header styling */
.modern-table thead th {
    background-color: #b72a2a !important;
    color: #fff !important;
    border: none !important;
    padding: 14px 12px !important;
    font-weight: 600;
}

.modern-table thead tr:first-child th:first-child {
    border-top-left-radius: 10px;
}
.modern-table thead tr:first-child th:last-child {
    border-top-right-radius: 10px;
}

/* Row styling */
.modern-table tbody tr:nth-child(even) {
    background-color: #f8f9fa !important;
}

.modern-table tbody td {
    padding: 12px 14px !important;
    border-color: #e1e4e8 !important;
}

/* Filter buttons */
.btn-group .btn {
    border-radius: 0 !important;
}

.btn-group .btn:first-child {
    border-top-left-radius: 50px !important;
    border-bottom-left-radius: 50px !important;
}

.btn-group .btn:last-child {
    border-top-right-radius: 50px !important;
    border-bottom-right-radius: 50px !important;
}

/* Focus outlines for GIGW accessibility */
.btn:focus, .form-control:focus, .btn-group .btn:focus {
    outline: 2px solid #004a93 !important;
    outline-offset: 2px;
}

/* Table hover */
.modern-table tbody tr:hover {
    background-color: #eef3f7 !important;
}


</style>
<style>
/* ---------- CARD STYLING ---------- */
.custom-table-card {
    border-radius: 18px;
    border: none !important;
    overflow: hidden;
}

.custom-table-card .card-body {
    padding: 1.75rem;
}

/* ---------- TABLE HEADER ---------- */
.modern-table thead th {
    background: #b22727 !important;
    color: #ffffff !important;
    font-weight: 600;
    padding: 14px 18px !important;
    border: none !important;
    font-size: 15px;
}

.modern-table thead tr:first-child th:first-child {
    border-top-left-radius: 12px;
}
.modern-table thead tr:first-child th:last-child {
    border-top-right-radius: 12px;
}

/* ---------- TABLE ROWS ---------- */
.modern-table tbody tr {
    background: #ffffff;
    border-bottom: 1px solid #eee !important;
    transition: all .2s ease;
}

.modern-table tbody tr:hover {
    background: #fafafa !important;
}

.modern-table td {
    padding: 14px 18px !important;
    vertical-align: middle !important;
    font-size: 15px;
    color: #333;
    font-weight: 500;
}

/* ---------- STATUS TOGGLE CLEAN LOOK ---------- */
.form-check-input {
    width: 2.3rem;
    height: 1.2rem;
    cursor: pointer;
}

/* ---------- ACTION BUTTONS ---------- */
.table-action-btn {
    background: #f4f4f4;
    border-radius: 8px;
    padding: 6px 12px;
    border: 1px solid #ddd;
    transition: .2s;
}

.table-action-btn:hover {
    background: #e9e9e9;
    border-color: #ccc;
}
</style>

<div class="container-fluid">

    <div class="card shadow-sm custom-table-card">
        <div class="card-body">

            <!-- Title -->
           <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-semibold text-dark mb-0">Duty Type Master</h4>

    <div class="d-flex align-items-center gap-2">

        <!-- Add New Button -->
        <a href="{{ route('programme.create') }}"
            class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
            <i class="material-icons menu-icon material-symbols-rounded"
               style="font-size: 20px; vertical-align: middle;">add</i>
            Add New
        </a>

        <!-- Search Box + Icon -->
        <div class="position-relative">

            <!-- Hidden Search Input -->
            <form action="{{ route('programme.index') }}" method="GET"
                  class="search-box d-none" id="searchBox">
                <input type="text" name="search"
                       class="form-control"
                       placeholder="Search..."
                       style="width: 220px;">
            </form>

            <!-- Search Icon Button -->
            <button type="button" class="btn btn-outline-primary"
                    id="searchToggleBtn"
                    style="padding: 7px 10px;">
                <i class="material-icons material-symbols-rounded"
                   style="font-size: 22px;">search</i>
            </button>

        </div>

    </div>
</div>


            <!-- DataTable -->
            <div class="table-responsive">
                {!! $dataTable->table(['class' => 'table modern-table align-middle']) !!}
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
    document.getElementById("searchToggleBtn").addEventListener("click", function () {
    const box = document.getElementById("searchBox");
    box.classList.toggle("d-none");

    if (!box.classList.contains("d-none")) {
        box.querySelector("input").focus();
    }
});

</script>
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