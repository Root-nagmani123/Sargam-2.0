
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>


<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

@extends('admin.layouts.master')
@section('title', 'Peer Evaluation - Admin Panel | Sargam Admin')
@section('setup_content')
<style>
    /* ✅ Modern Bootstrap 5 Improvements */
    :root {
        --primary-color: #0d6efd;
        --success-color: #198754;
        --warning-color: #ffc107;
        --danger-color: #dc3545;
        --info-color: #0dcaf0;
        --card-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        --card-shadow-hover: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    /* ✅ Improve focus visibility */
    .table a:focus, .btn:focus, .form-check-input:focus {
        outline: 2px solid var(--primary-color);
        outline-offset: 2px;
        box-shadow: none !important;
    }

    /* ✅ Custom toggle color for clarity */
    .form-switch .form-check-input:checked {
        background-color: var(--success-color) !important;
        border-color: var(--success-color) !important;
    }

    /* ✅ Hover feedback for rows */
    .table-hover tbody tr:hover {
        background-color: #f8f9fa !important;
        transition: background-color 0.15s ease-in-out;
    }

    /* ✅ High contrast for badges */
    .badge.bg-info { color: #000; }
	
	#courseMessage {
        font-size: 0.85rem;
        font-weight: 500;
        padding: 0.5rem 0;
        display: inline-block;
	}
	
	#coursesAccordion .badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.375rem 0.625rem;
	}

    /* ✅ Modern Card Styling */
    .card {
        border: none;
        box-shadow: var(--card-shadow);
        border-radius: 0.5rem;
        transition: box-shadow 0.3s ease, transform 0.3s ease;
    }

    .card:hover {
        box-shadow: var(--card-shadow-hover);
        transform: translateY(-2px);
    }

    /* ✅ Section Cards */
    .section-card {
        background: #fff;
        border-left: 4px solid var(--primary-color);
        padding: 2rem;
        margin-bottom: 2rem;
        border-radius: 0.5rem;
        box-shadow: var(--card-shadow);
    }

    .section-card.course-section { border-left-color: var(--primary-color); }
    .section-card.group-section { border-left-color: #6f42c1; }
    .section-card.column-section { border-left-color: #20c997; }
    .section-card.reflection-section { border-left-color: #fd7e14; }

    /* ✅ Modern Form Styling */
    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .form-control, .form-select {
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
        font-size: 0.95rem;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }

    /* ✅ Input Group Styling */
    .input-group-lg .form-control,
    .input-group-lg .btn {
        border-radius: 0.375rem;
    }

    /* ✅ Button Styling */
    .btn {
        font-weight: 600;
        border-radius: 0.375rem;
        transition: all 0.15s ease;
        padding: 0.5rem 1rem;
        font-size: 0.95rem;
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
    }

    .btn-sm {
        padding: 0.35rem 0.75rem;
        font-size: 0.85rem;
    }

    .btn-group-sm > .btn {
        padding: 0.35rem 0.5rem;
        border-radius: 0.25rem;
    }

    /* ✅ Accordion Improvements */
    .accordion-item {
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        margin-bottom: 1rem;
        transition: box-shadow 0.3s ease;
    }

    .accordion-item:hover {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .accordion-button {
        font-weight: 600;
        color: #495057;
        padding: 1rem;
    }

    .accordion-button:not(.collapsed) {
        color: var(--primary-color);
        background-color: #f0f6ff;
    }

    .accordion-button:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    .accordion-body {
        padding: 1.5rem;
        background-color: #fafbfc;
    }

    /* ✅ Table Improvements */
    .table {
        margin-bottom: 0;
        font-size: 0.95rem;
    }

    .table thead {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }

    .table thead th {
        font-weight: 600;
        color: #495057;
        border-bottom: 2px solid #dee2e6;
        padding: 1rem 0.75rem;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table tbody td {
        padding: 1rem 0.75rem;
        vertical-align: middle;
    }

    .table-responsive {
        border-radius: 0.375rem;
        box-shadow: var(--card-shadow);
    }

    /* ✅ Badge Improvements */
    .badge {
        font-weight: 600;
        padding: 0.45rem 0.75rem;
        font-size: 0.8rem;
        border-radius: 0.25rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* ✅ Alert Improvements */
    .alert {
        border: none;
        border-left: 4px solid;
        border-radius: 0.375rem;
        font-size: 0.95rem;
    }

    .alert-info {
        border-left-color: var(--info-color);
        background-color: #f0f6ff;
        color: #084298;
    }

    .alert-success {
        border-left-color: var(--success-color);
        background-color: #f0fdf4;
        color: #155e3b;
    }

    .alert-danger {
        border-left-color: var(--danger-color);
        background-color: #fdf0f0;
        color: #8b2e2e;
    }

    /* ✅ Section Heading */
    .section-heading {
        font-size: 1.35rem;
        font-weight: 700;
        color: #212529;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #e9ecef;
    }

    .subsection-heading {
        font-size: 1.05rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 1.25rem;
    }

    /* ✅ Modal Improvements */
    .modal-header {
        border-bottom: 1px solid #dee2e6;
        padding: 1.5rem;
    }

    .modal-title {
        font-weight: 700;
        color: #212529;
    }

    .modal-body {
        padding: 2rem;
    }

    .modal-footer {
        border-top: 1px solid #dee2e6;
        padding: 1.5rem;
    }

    /* ✅ Breadcrumb Improvements */
    .breadcrumb {
        background-color: transparent;
        padding: 0;
        margin-bottom: 1.5rem;
    }

    .breadcrumb-item.active {
        color: #6c757d;
        font-weight: 600;
    }

    /* ✅ Responsive Improvements */
    @media (max-width: 768px) {
        .section-card {
            padding: 1.5rem;
        }

        .section-heading {
            font-size: 1.15rem;
        }

        .btn-group-sm > .btn {
            padding: 0.25rem 0.35rem;
            font-size: 0.75rem;
        }

        .badge {
            font-size: 0.7rem;
            padding: 0.35rem 0.5rem;
        }
    }
</style>


<div class="container-fluid py-4">
     <x-breadcrum title="Peer Evaluation - Admin Panel" />
    <div class="mb-4">
        <h1 class="section-heading mb-0">
            <i class="fa-solid fa-graduation-cap me-2" style="color: var(--primary-color);"></i>
            Peer Evaluation - Admin Panel
        </h1>
        <p class="text-muted small">Manage courses, groups, evaluation columns, and reflection fields</p>
    </div>

        {{-- Manage Courses Section --}}
        <div class="section-card course-section">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h4 class="subsection-heading mb-0">
                    <i class="fa-solid fa-book me-2" style="color: var(--primary-color);"></i>
                    Manage Courses
                </h4>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-8">
                    <label class="form-label">Course Name</label>
                    <input type="text" id="course_name" class="form-control form-control-lg" placeholder="Enter a new course name..." autocomplete="off">
                </div>
                <div class="col-12 col-md-4 d-flex align-items-end">
                    <button class="btn btn-primary btn-lg w-100" id="addCourseBtn">
                        <i class="fa-solid fa-plus me-2"></i>Add Course
                    </button>
                </div>
            </div>
			
			<div id="courseMessage" class="alert alert-danger alert-dismissible fade show" style="display:none;" role="alert"></div>

            {{-- Courses List --}}
            <div class="mt-4">
                <h5 class="subsection-heading">
                    <i class="fa-solid fa-list me-2" style="color: #6f42c1;"></i>
                    Existing Courses <span id="successMessage" class="badge bg-success small ms-2" style="display:none;"></span>
                </h5>

                <div class="accordion" id="coursesAccordion">
		@foreach ($courses as $course)
    <div class="accordion-item mb-2">

        <h2 class="accordion-header d-flex align-items-center justify-content-between px-3 py-2"
            id="heading{{ $course->id }}">

            <!-- Accordion Toggle -->
            <button class="accordion-button collapsed flex-grow-1 me-2" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapse{{ $course->id }}"
                    aria-expanded="false"
                    aria-controls="collapse{{ $course->id }}">
                <strong>{{ $course->course_name }}</strong>
			</button>

            <!-- Actions -->
            <div class="d-flex align-items-center gap-2">

                <span class="badge bg-primary">{{ $course->events_count }} Events</span>
                <span class="badge bg-secondary">{{ $course->groups_count }} Groups</span>

                <!-- Edit -->
                <button type="button"
					class="btn btn-sm btn-outline-warning edit-course-btn"
					data-id="{{ $course->id }}"
					data-name="{{ $course->course_name }}"
					title="Edit Course">
					<i class="fa-solid fa-pen"></i>
				</button>

                <!-- Delete -->
                <button type="button"
					class="btn btn-sm btn-outline-danger delete-course-btn"
					data-id="{{ $course->id }}"
					title="Delete Course">
					<i class="fa-solid fa-trash"></i>
				</button>
								
            </div>
		
		

        </h2>

        <!-- COLLAPSE BODY -->
        <div id="collapse{{ $course->id }}"
             class="accordion-collapse collapse"
             aria-labelledby="heading{{ $course->id }}"
             data-bs-parent="#coursesAccordion">

            <div class="accordion-body">

                <!-- Add Event -->
                <div class="input-group input-group-sm mb-3">
                    <input type="text"
                           class="form-control event-input"
                           placeholder="Add Event to {{ $course->course_name }}"
                           data-course-id="{{ $course->id }}">
                    <button class="btn btn-outline-primary add-event-btn"
                            data-course-id="{{ $course->id }}">
                        Add Event
                    </button>
                </div>

                <!-- Events List -->
                @foreach ($course->events as $event)
                    <div class="mb-2 p-2 border rounded d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $event->event_name }}</strong>
                            <span class="badge bg-secondary ms-2">
                                {{ $event->groups->count() }} Groups
                            </span>
                        </div>
                        <small class="text-muted">Event ID: {{ $event->id }}</small>
                    </div>
                @endforeach

            </div>
        </div>
    </div>
@endforeach
</div>
<div class="mt-3 d-flex justify-content-center">
     {{ $courses->onEachSide(1)->links('pagination::bootstrap-5') }}
</div>
</div>

        </div>

        {{-- Manage Groups Section --}}
        <div class="section-card group-section">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h4 class="subsection-heading mb-0">
                    <i class="fa-solid fa-users me-2" style="color: #6f42c1;"></i>
                    Manage Groups
                </h4>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-3">
                    <label class="form-label">Course</label>
                    <select class="form-select" id="group_course_id">
                        <option value="">Select Course</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}">{{ $course->course_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Event</label>
                    <select class="form-select" id="group_event_id" disabled>
                        <option value="">Select Event</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Group Name</label>
                    <input type="text" id="group_name" class="form-control" placeholder="e.g., Group A, Team 1">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">Max Marks</label>
                    <input type="number" id="max_marks" class="form-control" placeholder="10" value="10"
                        step="0.01" min="1" max="100">
                </div>
                <div class="col-12 col-md-1 d-flex align-items-end">
                    <button class="btn btn-success w-100" id="addGroupBtn">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
            </div>

            {{-- Groups List --}}
            <div class="mt-4">
                <h5 class="subsection-heading">
                    <i class="fa-solid fa-table me-2" style="color: #6f42c1;"></i>
                    Groups List
                </h5>
                <div class="table-responsive">
    <table class="table table-hover align-middle" id="datatable-courses">
        <thead>
            <tr>
                <th scope="col"><i class="fa-solid fa-book-open me-2"></i>Course</th>
                <th scope="col"><i class="fa-solid fa-calendar me-2"></i>Event</th>
                <th scope="col"><i class="fa-solid fa-tag me-2"></i>Group Name</th>
                <th scope="col"><i class="fa-solid fa-star me-2"></i>Max Marks</th>
                <th scope="col" class="text-center"><i class="fa-solid fa-toggle-on me-2"></i>Status</th>
                <th scope="col" class="text-center"><i class="fa-solid fa-users me-2"></i>Members</th>
                <th scope="col" class="text-center"><i class="fa-solid fa-gear me-2"></i>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($groups as $group)
                <tr>
                    <td>
                        <span class="badge bg-info text-dark fw-semibold px-2 py-1">
                            {{ $group->course->course_name ?? 'N/A' }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-secondary fw-semibold px-2 py-1">
                            {{ $group->event->event_name ?? 'N/A' }}
                        </span>
                    </td>
                    <td class="fw-medium text-dark">{{ $group->group_name }}</td>

                    <td>
                        <div class="d-flex align-items-center">
                            <label for="maxMarks{{ $group->id }}" class="visually-hidden">Max Marks</label>
                            <input type="number" id="maxMarks{{ $group->id }}"
                                class="form-control form-control-sm max-marks-input"
                                data-id="{{ $group->id }}" value="{{ $group->max_marks ?? 10 }}"
                                step="0.01" min="1" max="100" style="width: 90px;"
                                aria-label="Enter Max Marks">
                            <button class="btn btn-sm btn-outline-primary update-marks ms-2"
                                data-id="{{ $group->id }}" title="Save Marks" aria-label="Save Max Marks">
                                <i class="fas fa-save"></i>
                            </button>
                        </div>
                    </td>

                    <td class="text-center">
                        <div class="d-flex justify-content-center align-items-center gap-2">
                            <div class="form-check form-switch m-0">
                                <input type="checkbox" role="switch" 
                                    class="form-check-input toggle-form"
                                    id="toggleForm{{ $group->id }}"
                                    data-id="{{ $group->id }}"
                                    {{ $group->is_form_active ? 'checked' : '' }}
                                    aria-label="Toggle Form Status">
                            </div>
                            <span class="badge {{ $group->is_form_active ? 'bg-success' : 'bg-danger' }}">
                                {{ $group->is_form_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </td>

                    <td class="text-center">
                        <span class="badge bg-dark text-white fw-semibold px-2 py-1">
                            {{ $group->members_count }} Members
                        </span>
                    </td>

                    <td class="text-center">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Group Actions">
                            <a href="{{ route('admin.peer.group.members', $group->id) }}"
                                class="btn btn-outline-info" title="View Members" aria-label="View Members">
                                <i class="fas fa-users"></i>
                            </a>
                            <a href="{{ route('admin.peer.group.import', $group->id) }}"
                                class="btn btn-outline-warning" title="Import Users" aria-label="Import Users">
                                <i class="fas fa-upload"></i>
                            </a>
                            <a href="{{ route('admin.peer.group.submissions', $group->id) }}"
                                class="btn btn-outline-primary" title="View Submissions" aria-label="View Submissions">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button class="btn btn-outline-danger delete-group"
                                data-id="{{ $group->id }}" title="Delete Group" aria-label="Delete Group">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

            </div>
        </div>

        {{-- Manage Columns Section --}}
        <div class="section-card column-section">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h4 class="subsection-heading mb-0">
                    <i class="fa-solid fa-columns me-2" style="color: #20c997;"></i>
                    Manage Evaluation Columns
                </h4>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-3">
                    <label class="form-label">Course (Optional)</label>
                    <select class="form-select" id="column_course_id">
                        <option value="">Global Column</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}">{{ $course->course_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Event (Optional)</label>
                    <select class="form-select" id="column_event_id" disabled>
                        <option value="">Select Event</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Column Name</label>
                    <input type="text" id="column_name" class="form-control" placeholder="e.g., Communication, Teamwork">
                </div>
                <div class="col-12 col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100" id="addColumnBtn">
                        <i class="fa-solid fa-plus me-1"></i>Add Column
                    </button>
                </div>
            </div>

            {{-- Columns List --}}
          <div class="mt-4">
                <h5 class="subsection-heading">
                    <i class="fa-solid fa-list me-2" style="color: #20c997;"></i>
                    Existing Columns
                </h5>
    <div class="table-responsive">
    <table class="table table-hover align-middle" id="datatable-groups">
            <thead>
                <tr>
                    <th scope="col"><i class="fa-solid fa-heading me-2"></i>Column Name</th>
                    <th scope="col"><i class="fa-solid fa-book me-2"></i>Course</th>
                    <th scope="col"><i class="fa-solid fa-calendar-check me-2"></i>Event</th>
                    <th scope="col" class="text-center"><i class="fa-solid fa-eye me-2"></i>Visible</th>
                    <th scope="col" class="text-center"><i class="fa-solid fa-gear me-2"></i>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($columns as $column)
                    <tr>
                        <td>
                            <span
                                class="badge {{ $column->is_visible ? 'bg-success' : 'bg-secondary' }} me-1">
                                {{ $column->column_name }}
                            </span>
                        </td>
                        <td>
                            @if ($column->course)
                                <small class="text-muted">{{ $column->course->course_name }}</small>
                            @else
                                <small class="text-muted">—</small>
                            @endif
                        </td>
                        <td>
                            @if ($column->event)
                                <small class="text-muted">{{ $column->event->event_name }}</small>
                            @else
                                <small class="text-muted">—</small>
                            @endif
                        </td>
                        <td class="text-center">
    <div class="form-check form-switch d-inline-block">
        <input type="checkbox" 
            class="form-check-input toggle-column"
            data-id="{{ $column->id }}"
            id="toggleColumn{{ $column->id }}"
            {{ $column->is_visible ? 'checked' : '' }}
            title="Toggle Visibility">
    </div>
</td>

                        <td class="text-center">
                            <button class="btn btn-sm btn-danger delete-column"
                                data-id="{{ $column->id }}" title="Delete">
                                <iconify-icon icon="solar:trash-bin-minimalistic-bold" class="fs-7">
                                                     </iconify-icon>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

        </div>

        {{-- Manage Reflection Fields --}}
        <div class="section-card reflection-section">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h4 class="subsection-heading mb-0">
                    <i class="fa-solid fa-lightbulb me-2" style="color: #fd7e14;"></i>
                    Manage Reflection Fields
                </h4>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-3">
                    <label class="form-label">Course (Optional)</label>
                    <select class="form-select" id="reflection_course_id">
                        <option value="">Global Field</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}">{{ $course->course_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Event (Optional)</label>
                    <select class="form-select" id="reflection_event_id" disabled>
                        <option value="">Select Event</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Reflection Field Name</label>
                    <input type="text" id="reflection_field" class="form-control"
                        placeholder="e.g., What did you learn?, Key insights">
                </div>
                <div class="col-12 col-md-2 d-flex align-items-end">
                    <button class="btn btn-warning btn-light w-100" id="addReflectionBtn">
                        <i class="fa-solid fa-plus me-1"></i>Add Field
                    </button>
                </div>
            </div>  
            {{-- Reflection Fields List --}}
            <div class="mt-4">
                <h5 class="subsection-heading">
                    <i class="fa-solid fa-list-check me-2" style="color: #fd7e14;"></i>
                    Existing Reflection Fields
                </h5>
                <div class="mt-3">
    <div class="table-responsive">
    <table class="table table-hover align-middle" id="datatable-columns">

            <thead>
                <tr>
                    <th scope="col"><i class="fa-solid fa-tag me-2"></i>Field Label</th>
                    <th scope="col"><i class="fa-solid fa-book me-2"></i>Course</th>
                    <th scope="col"><i class="fa-solid fa-calendar-check me-2"></i>Event</th>
                    <th scope="col" class="text-center"><i class="fa-solid fa-toggle-on me-2"></i>Active</th>
                    <th scope="col" class="text-center"><i class="fa-solid fa-trash me-2"></i>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($reflectionFields as $field)
                    <tr>
                        <td>
                            <span class="badge {{ $field->is_active ? 'bg-success' : 'bg-secondary' }} me-1">
                                {{ $field->field_label }}
                            </span>
                        </td>
                        <td>
                            @if ($field->course_name)
                                <small class="text-muted">{{ $field->course_name }}</small>
                            @else
                                <small class="text-muted">—</small>
                            @endif
                        </td>
                        <td>
                            @if ($field->event_name)
                                <small class="text-muted">{{ $field->event_name }}</small>
                            @else
                                <small class="text-muted">—</small>
                            @endif
                        </td>
                        <td class="text-center">
    <div class="form-check form-switch d-inline-block">
        <input type="checkbox"
            class="form-check-input toggle-reflection"
            data-id="{{ $field->id }}"
            id="toggleReflection{{ $field->id }}"
            {{ $field->is_active ? 'checked' : '' }}
            title="Toggle Active">
    </div>
</td>

                        <td class="text-center">
                            <button class="btn btn-sm btn-danger delete-reflection"
                                data-id="{{ $field->id }}" title="Delete"><iconify-icon icon="solar:trash-bin-minimalistic-bold" class="fs-7">
                                                     </iconify-icon></button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

                </div>
            </div>

            <div class="alert alert-info mt-5" role="alert">
                <div class="d-flex align-items-start">
                    <i class="fa-solid fa-circle-info me-3 mt-1" style="font-size: 1.2rem; color: var(--info-color);"></i>
                    <div>
                        <strong>How This Works:</strong>
                        <p class="mb-0 mt-2 text-muted">This admin panel allows you to configure the peer evaluation system. Users will see the evaluation form on the user portal based on your configurations here.</p>
                    </div>
                </div>
            </div>
        </div>

       
      
        <script>
            $(document).ready(function() {
                // Load events when course is selected for groups
                $('#group_course_id').change(function() {
                    const courseId = $(this).val();
                    const eventSelect = $('#group_event_id');

                    if (courseId) {
                        $.get('/admin/peer/events/' + courseId, function(events) {
                            eventSelect.empty().append('<option value="">Select Event</option>');
                            events.forEach(event => {
                                eventSelect.append(
                                    `<option value="${event.id}">${event.event_name}</option>`
                                );
                            });
                            eventSelect.prop('disabled', false);
                        }).fail(function() {
                            alert('Error loading events');
                        });
                    } else {
                        eventSelect.empty().append('<option value="">Select Event</option>').prop('disabled',
                            true);
                    }
                });

                // Load events when course is selected for columns
                $('#column_course_id').change(function() {
                    const courseId = $(this).val();
                    const eventSelect = $('#column_event_id');

                    if (courseId) {
                        $.get('/admin/peer/events/' + courseId, function(events) {
                            eventSelect.empty().append('<option value="">Select Event</option>');
                            events.forEach(event => {
                                eventSelect.append(
                                    `<option value="${event.id}">${event.event_name}</option>`
                                );
                            });
                            eventSelect.prop('disabled', false);
                        }).fail(function() {
                            alert('Error loading events');
                        });
                    } else {
                        eventSelect.empty().append('<option value="">Select Event</option>').prop('disabled',
                            true);
                    }
                });

                // Load events when course is selected for reflection fields
                $('#reflection_course_id').change(function() {
                    const courseId = $(this).val();
                    const eventSelect = $('#reflection_event_id');

                    if (courseId) {
                        $.get('/admin/peer/events/' + courseId, function(events) {
                            eventSelect.empty().append('<option value="">Select Event</option>');
                            events.forEach(event => {
                                eventSelect.append(
                                    `<option value="${event.id}">${event.event_name}</option>`
                                );
                            });
                            eventSelect.prop('disabled', false);
                        }).fail(function() {
                            alert('Error loading events');
                        });
                    } else {
                        eventSelect.empty().append('<option value="">Select Event</option>').prop('disabled',
                            true);
                    }
                });

                // Add Course
				
           	function validateCourseName() {
			const courseName = $('#course_name').val().trim();
			
			if (!courseName) {
				$('#courseMessage')
					.removeClass('alert-success')
					.addClass('alert-danger')
					.html(`
                        <div class="d-flex align-items-start">
                            <i class="fa-solid fa-circle-exclamation me-2 mt-1" style="color: var(--danger-color);"></i>
                            <div>
                                <strong>Validation Error!</strong> Please enter a course name
                            </div>
                        </div>
                    `)
				return true;
			}

			$('#course_name').on('keyup', function () {
				if ($(this).val().trim().length > 0) {
					$('#courseMessage').fadeOut();
				}
			});

			$('#course_name').on('blur', function () {
				validateCourseName();
			});

		//$('#addCourseBtn').click(function () {
		$(document).on('click', '#addCourseBtn', function () {

		const courseName = $('#course_name').val().trim();

		$('#courseMessage').hide().removeClass('text-success text-danger').text('');

	   /* if (!courseName) {
			$('#courseMessage').addClass('text-danger')
				.text('Please enter course name')
				.show();
			return;
		}*/
		
		if (!validateCourseName()) {
			return;
		}

		$.post('{{ route('admin.peer.course.add') }}', {
			_token: '{{ csrf_token() }}',
			course_name: courseName
		}, function (response) {

		if (response.success) {

           $('#courseMessage')
           .removeClass('alert-danger')
           .addClass('alert-success')
           .html(`
               <div class="d-flex align-items-start">
                   <i class="fa-solid fa-check-circle me-2 mt-1" style="color: var(--success-color);"></i>
                   <div>
                       <strong>Success!</strong> ${response.message}
                   </div>
               </div>
           `)
           .show();

           $('#course_name').val('');

           setTimeout(() => $('#courseMessage').fadeOut(), 3000);
           const course = response.course;

            const html = `
<div class="accordion-item mb-2">

    <h2 class="accordion-header d-flex align-items-center justify-content-between px-3 py-2"
        id="heading${course.id}">

        <button class="accordion-button collapsed flex-grow-1 me-2"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#collapse${course.id}">
            <strong>${course.course_name}</strong>
        </button>

        <div class="d-flex align-items-center gap-2">

            <span class="badge bg-primary">0 Events</span>
            <span class="badge bg-secondary">0 Groups</span>

            <button type="button"
                class="btn btn-sm btn-outline-warning edit-course-btn"
                data-id="${course.id}"
                data-name="${course.course_name}"
                title="Edit Course">
                <i class="fa-solid fa-pen"></i>
            </button>

            <button type="button"
                class="btn btn-sm btn-outline-danger delete-course-btn"
                data-id="${course.id}"
                title="Delete Course">
                <i class="fa-solid fa-trash"></i>
            </button>

        </div>
    </h2>

    <div id="collapse${course.id}"
        class="accordion-collapse collapse"
        data-bs-parent="#coursesAccordion">

        <div class="accordion-body">
            <div class="input-group input-group-sm mb-3">
                <input type="text"
                    class="form-control event-input"
                    placeholder="Add Event to ${course.course_name}"
                    data-course-id="${course.id}">
                <button class="btn btn-outline-primary add-event-btn"
                    data-course-id="${course.id}">
                    Add Event
                </button>
            </div>
        </div>
    </div>
</div>
`;
$('#coursesAccordion').prepend(html);
        }

    }).fail(function () {
        $('#courseMessage')
            .removeClass('alert-success')
            .addClass('alert-danger')
            .html(`
                <div class="d-flex align-items-start">
                    <i class="fa-solid fa-exclamation-circle me-2 mt-1" style="color: var(--danger-color);"></i>
                    <div>
                        <strong>Error!</strong> Error adding course
                    </div>
                </div>
            `)
            .show();
    });
});

          // Add Event to Course
                //$('.add-event-btn').click(function() {
			$(document).on('click', '.add-event-btn', function () {
                    const courseId = $(this).data('course-id');
                    const eventInput = $(`.event-input[data-course-id="${courseId}"]`);
                    const eventName = eventInput.val();

                    if (!eventName) {
                        alert('Please enter event name');
                        return;
                    }

                    $.post('{{ route('admin.peer.event.add') }}', {
                        _token: '{{ csrf_token() }}',
                        event_name: eventName,
                        course_id: courseId
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }).fail(function() {
                        alert('Error adding event');
                    });
                });

                // Add Group with Course and Event
                $('#addGroupBtn').click(function() {
                    const courseId = $('#group_course_id').val();
                    const eventId = $('#group_event_id').val();
                    const groupName = $('#group_name').val();
                    const maxMarks = $('#max_marks').val();

                    if (!courseId || !eventId || !groupName) {
                        alert('Please select course, event and enter group name');
                        return;
                    }

                    if (!maxMarks || maxMarks <= 0) {
                        alert('Please enter valid max marks');
                        return;
                    }

                    $.post('{{ route('admin.peer.group.add') }}', {
                        _token: '{{ csrf_token() }}',
                        course_id: courseId,
                        event_id: eventId,
                        group_name: groupName,
                        max_marks: maxMarks
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }).fail(function() {
                        alert('Error adding group');
                    });
                });

                // Add Column with Course and Event
                $('#addColumnBtn').click(function() {
                    const courseId = $('#column_course_id').val();
                    const eventId = $('#column_event_id').val();
                    const columnName = $('#column_name').val();

                    if (!columnName) {
                        alert('Please enter column name');
                        return;
                    }

                    $.post('{{ route('admin.peer.column.add') }}', {
                        _token: '{{ csrf_token() }}',
                        course_id: courseId || null,
                        event_id: eventId || null,
                        column_name: columnName
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }).fail(function() {
                        alert('Error adding column');
                    });
                });

                // Update Max Marks
                $('.update-marks').click(function() {
                    const groupId = $(this).data('id');
                    const input = $(`.max-marks-input[data-id="${groupId}"]`);
                    const maxMarks = input.val();

                    if (!maxMarks || maxMarks <= 0) {
                        alert('Please enter valid max marks');
                        return;
                    }

                    $.post('{{ route('admin.peer.groups.update-marks') }}', {
                        _token: '{{ csrf_token() }}',
                        group_id: groupId,
                        max_marks: parseFloat(maxMarks)
                    }, function(response) {
                        if (response.success) {
                            alert('Max marks updated successfully');
                            input.val(parseFloat(maxMarks));
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }).fail(function() {
                        alert('Error updating max marks');
                    });
                });

                // Toggle Form Status
                $('.toggle-form').change(function() {
                    const checkbox = $(this);
                    const id = checkbox.data('id');
                    const isChecked = checkbox.is(':checked') ? 1 : 0;

                    const originalState = !isChecked;
                    checkbox.prop('disabled', true);

                    $.post('/admin/peer/group/toggle-form/' + id, {
                            _token: '{{ csrf_token() }}',
                            is_form_active: isChecked
                        })
                        .done(function(response) {
                            if (response.status === 'success') {
                                const badge = checkbox.closest('td').find('.badge');
                                if (badge.length) {
                                    if (isChecked) {
                                        badge.removeClass('bg-danger').addClass('bg-success').text(
                                            'Active');
                                    } else {
                                        badge.removeClass('bg-success').addClass('bg-danger').text(
                                            'Inactive');
                                    }
                                }
                            } else {
                                const errorMessage = response.message || 'Error updating form status';
                                alert(errorMessage);
                                checkbox.prop('checked', originalState);
                            }
                        })
                        .fail(function(xhr, status, error) {
                            let errorMessage = 'Error updating form status';
                            try {
                                const response = JSON.parse(xhr.responseText);
                                errorMessage = response.message || errorMessage;
                            } catch (e) {}
                            alert(errorMessage);
                            checkbox.prop('checked', originalState);
                        })
                        .always(function() {
                            checkbox.prop('disabled', false);
                        });
                });

                // Delete Group
                $('.delete-group').click(function() {
                    if (confirm('Are you sure you want to delete this group?')) {
                        const button = $(this);
                        const id = button.data('id');

                        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                        $.post('/admin/peer/group/delete/' + id, {
                                _token: '{{ csrf_token() }}'
                            })
                            .done(function(response) {
                                if (response.success) {
                                    alert(response.message || 'Group deleted successfully!');
                                    location.reload();
                                } else {
                                    alert('Error: ' + response.message);
                                    button.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                                }
                            })
                            .fail(function(xhr, status, error) {
                                alert('Error deleting group: ' + error);
                                button.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                            });
                    }
                });

                // Toggle Column Visibility
                $('.toggle-column').change(function() {
                    const checkbox = $(this);
                    const id = checkbox.data('id');

                    $.post('/admin/peer/column/toggle/' + id, {
                        _token: '{{ csrf_token() }}'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                            checkbox.prop('checked', !checkbox.is(':checked'));
                        }
                    }).fail(function() {
                        alert('Error updating column visibility');
                        checkbox.prop('checked', !checkbox.is(':checked'));
                    });
                });

                // Delete Column
                $('.delete-column').click(function() {
                    if (confirm('Are you sure you want to delete this column?')) {
                        const id = $(this).data('id');
                        $.post('/admin/peer/column/delete/' + id, {
                            _token: '{{ csrf_token() }}'
                        }, function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert('Error: ' + response.message);
                            }
                        }).fail(function() {
                            alert('Error deleting column');
                        });
                    }
                });

                // Add Reflection Field with Course and Event
                $('#addReflectionBtn').click(function() {
                    const courseId = $('#reflection_course_id').val();
                    const eventId = $('#reflection_event_id').val();
                    const fieldLabel = $('#reflection_field').val();

                    if (!fieldLabel) {
                        alert('Please enter reflection field label');
                        return;
                    }

                    $.post('{{ route('admin.peer.reflection.add') }}', {
                        _token: '{{ csrf_token() }}',
                        field_label: fieldLabel,
                        course_id: courseId || null,
                        event_id: eventId || null
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }).fail(function() {
                        alert('Error adding reflection field');
                    });
                });

                // Toggle Reflection Field
                $('.toggle-reflection').change(function() {
                    const checkbox = $(this);
                    const id = checkbox.data('id');

                    $.post('/admin/peer/reflection/toggle/' + id, {
                        _token: '{{ csrf_token() }}'
                    }, function(response) {
                        if (response.success) {
                            const badge = checkbox.closest('.card').find('.badge');
                            if (response.new_state) {
                                badge.removeClass('bg-secondary').addClass('bg-success');
                            } else {
                                badge.removeClass('bg-success').addClass('bg-secondary');
                            }
                        } else {
                            alert('Error: ' + response.message);
                            checkbox.prop('checked', !checkbox.is(':checked'));
                        }
                    }).fail(function() {
                        alert('Error updating reflection field');
                        checkbox.prop('checked', !checkbox.is(':checked'));
                    });
                });

                // Delete Reflection Field
                $('.delete-reflection').click(function() {
                    if (confirm('Are you sure you want to delete this reflection field?')) {
                        const button = $(this);
                        const id = button.data('id');

                        $.post('/admin/peer/reflection/delete/' + id, {
                            _token: '{{ csrf_token() }}'
                        }, function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert('Error: ' + response.message);
                            }
                        }).fail(function() {
                            alert('Error deleting reflection field');
                        });
                    }
                });
            });
        </script>

        <style>
            .card-header {
                background: linear-gradient(135deg, #0d6efd 0%, #0056b3 100%);
                color: white;
                font-weight: 600;
            }

            .table th {
                vertical-align: middle;
                font-weight: 600;
            }

            .badge {
                font-size: 0.8em;
                font-weight: 600;
            }

            .btn-group-sm > .btn {
                padding: 0.35rem 0.5rem;
                border-radius: 0.25rem;
            }

            .event-input,
            .course-input {
                max-width: 300px;
            }

            /* ✅ Enhanced Input Group Styling */
            .input-group {
                border-radius: 0.375rem;
                overflow: hidden;
            }

            /* ✅ Improved Action Buttons */
            .btn-outline-info:hover,
            .btn-outline-warning:hover,
            .btn-outline-danger:hover {
                transform: translateY(-2px);
                box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
            }

            /* ✅ Better Delete Button */
            .delete-course-btn,
            .delete-group,
            .delete-column,
            .delete-reflection {
                transition: all 0.15s ease;
            }

            .delete-course-btn:hover,
            .delete-group:hover,
            .delete-column:hover,
            .delete-reflection:hover {
                background-color: #dc3545 !important;
                color: white !important;
                border-color: #dc3545 !important;
            }

            /* ✅ Accordion Item Hover */
            .accordion-item {
                transition: all 0.3s ease;
            }

            .accordion-item:hover {
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            }
        </style>
		
		
<!-- Edit Course Modal -->
<div class="modal fade" id="editCourseModal" tabindex="-1" aria-labelledby="editCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title" id="editCourseModalLabel">
                    <i class="fa-solid fa-pen-to-square me-2" style="color: var(--primary-color);"></i>
                    Edit Course
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="editCourseId">
                <div class="mb-3">
                    <label for="editCourseName" class="form-label">Course Name</label>
                    <input type="text" class="form-control form-control-lg" id="editCourseName" placeholder="Enter course name">
                </div>
            </div>

            <div class="modal-footer border-top">
                <button class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-solid fa-times me-1"></i>Cancel
                </button>
                <button class="btn btn-primary" id="updateCourseBtn">
                    <i class="fa-solid fa-check me-1"></i>Update Course
                </button>
            </div>
        </div>
    </div>
</div>
		
    @endsection
	
@section('scripts')

<script>
// EDIT COURSE
$(document).on('click', '.edit-course-btn', function (e) {
    e.stopPropagation(); // Prevent accordion toggle
    const id = $(this).data('id');
    const name = $(this).data('name');
    $('#editCourseId').val(id);
    $('#editCourseName').val(name);

    const modal = new bootstrap.Modal(document.getElementById('editCourseModal'));
    modal.show();
});

// UPDATE COURSE
$(document).on('click', '#updateCourseBtn', function () {
    const id = $('#editCourseId').val();
    const name = $('#editCourseName').val();

    if (!name.trim()) {
        alert('Course name is required');
        return;
    }

    $.ajax({
        url: "{{ route('admin.peer.course.update') }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            course_id: id,
            course_name: name
        },
        success: function (res) {
    const heading = $('#heading' + id);

    // Update accordion button text
		heading.find('.accordion-button strong').text(name);

		// Update edit button's data-name attribute for future edits
		heading.find('.edit-course-btn').data('name', name);

		// Close modal
		const modalEl = document.getElementById('editCourseModal');
		const modal = bootstrap.Modal.getInstance(modalEl);
		modal.hide();

		// Show success message
		$('#successMessage')
			.removeClass('badge-danger')
			.addClass('badge-success')
			.html('<i class="fa-solid fa-check-circle me-1"></i>Course updated successfully!')
			.fadeIn();

		// Hide after 3 seconds
		setTimeout(() => $('#successMessage').fadeOut(), 3000);
	},
        error: function (xhr) {
            alert(xhr.responseJSON?.message || 'Update failed');
        }
    });
});

// DELETE COURSE
$(document).on('click', '.delete-course-btn', function (e) {
    e.stopPropagation();

    const id = $(this).data('id');

    if (!confirm('Are you sure you want to delete this course?')) return;

    $.ajax({
        url: "{{ route('admin.peer.course.delete', ':id') }}".replace(':id', id),
        type: "DELETE",
        data: {
            _token: "{{ csrf_token() }}"
        },
        success: function (res) {

            // remove row
            $('#heading' + id).closest('.accordion-item').remove();

            // success message
            $('#successMessage')
                .removeClass('badge-danger')
                .addClass('badge-success')
                .html('<i class="fa-solid fa-trash-check me-1"></i>Course deleted successfully!')
                .fadeIn();

            
            setTimeout(() => {
                $('#successMessage').fadeOut();
            }, 3000);
        },
        error: function (xhr) {
            $('#successMessage')
                .removeClass('badge-success')
                .addClass('badge-danger')
                .html('<i class="fa-solid fa-exclamation-triangle me-1"></i>' + (xhr.responseJSON?.message || 'Delete failed'))
                .fadeIn();
        }
    });
});


</script>

<script>
$(document).ready(function() {
	 setTimeout(() => {
    $('#datatable-courses').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        lengthMenu: [5, 10, 25, 50],
        pageLength: 10,
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            paginate: { previous: "Prev", next: "Next" }
        }
    });
    $('#datatable-groups').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        lengthMenu: [5, 10, 25, 50],
        pageLength: 10
    });
    $('#datatable-columns').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        lengthMenu: [5, 10, 25, 50],
        pageLength: 10
    });
	
}, 200);
	
});
</script>

@endsection