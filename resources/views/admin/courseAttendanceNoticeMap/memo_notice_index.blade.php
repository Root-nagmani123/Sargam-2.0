{{-- resources/views/admin/courseAttendanceNoticeMap/memo_notice_index.blade.php --}}

@extends('admin.layouts.master')

@section('title', 'Memo/Notice Templates - Sargam | LBSNAA')

@section('setup_content')
<div class="container-fluid memo-notice-index-page">
    <x-breadcrum title="Memo/Notice Template Management" />

    <x-session_message />

    <!-- Filter Card -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.memo-notice.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Filter by Course</label>
                    <select name="course_master_pk" class="form-select">
                        <option value="">All Courses</option>
                        @foreach ($courses as $course)
                        <option value="{{ $course->pk }}"
                            {{ request('course_master_pk') == $course->pk ? 'selected' : '' }}>
                            {{ $course->course_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                    <a href="{{ route('admin.memo-notice.index') }}" class="btn btn-secondary">
                        <i class="fas fa-redo me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Buttons -->


        </form>

    </div>
    <!-- Main Content Card -->
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Memo/Notice Templates</h5>
            <a href="{{ route('admin.memo-notice.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Create New Template
            </a>
        </div>
        <div class="card-body">
            @if ($templates->isEmpty())
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> No templates found. Create your first template!
            </div>
            @else
            <div class="table-responsive">
                <table class="table text-nowrap">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Course</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($templates as $template)
                        <tr>
                            <td>{{ $loop->iteration + ($templates->currentPage() - 1) * $templates->perPage() }}
                            </td>
                            <td>
                                @if ($template->course)
                                <span class="badge bg-info">{{ $template->course->course_name }}</span>
                                @else
                                <span class="text-muted">General</span>
                                @endif
                            </td>
                            <td>{{ $template->title }}</td>
                          <td>{{ $template->memo_notice_type }}</td>
                            <td>
                                <div class="form-check form-switch d-inline-block ms-2">
                                    <input class="form-check-input status-toggle-data" 
                                        type="checkbox"
                                        role="switch"
                                        data-id="{{ $template->pk }}"
                                        data-course="{{ $template->course_master_pk }}"
                                        data-type="{{ $template->memo_notice_type }}"
                                        {{ $template->active_inactive == 1 ? 'checked' : '' }}>

                                </div>
                            </td>
<td>
    <div class="d-inline-flex align-items-center gap-2"
     role="group"
     aria-label="Memo notice template actions">

    <!-- Edit -->
    <a href="{{ route('admin.memo-notice.edit', $template->pk) }}"
       class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
       aria-label="Edit memo notice template">
        <i class="material-icons material-symbols-rounded"
           style="font-size:18px;"
           aria-hidden="true">edit</i>
        <span class="d-none d-md-inline">Edit</span>
    </a>

    <!-- Delete -->
    <form action="{{ route('admin.memo-notice.destroy', $template->pk) }}"
          method="POST"
          class="d-inline"
          onsubmit="return confirm('Are you sure you want to delete this template?')">
        @csrf
        @method('DELETE')

        <button type="submit"
                class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1"
                aria-label="Delete memo notice template">
            <i class="material-icons material-symbols-rounded"
               style="font-size:18px;"
               aria-hidden="true">delete</i>
            <span class="d-none d-md-inline">Delete</span>
        </button>
    </form>

</div>

</td>

                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $templates->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.memo-notice-index-page .table th {
    background-color: #f8f9fa;
}

.memo-notice-index-page .badge {
    font-size: 0.8em;
}

.memo-notice-index-page .btn-group .btn {
    margin-right: 2px;
}

/* Responsive: tablet and below only - desktop unchanged */
@media (max-width: 991.98px) {
    .memo-notice-index-page .card-header.d-flex {
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    .memo-notice-index-page .card-header .btn-primary {
        width: 100%;
        justify-content: center;
    }
    .memo-notice-index-page .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .memo-notice-index-page .table {
        min-width: 600px;
    }
}

@media (max-width: 575.98px) {
    .memo-notice-index-page .container-fluid {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    .memo-notice-index-page .card-body {
        padding: 0.75rem;
    }
    .memo-notice-index-page .row.g-3 > [class*="col-"] {
        width: 100%;
    }
    .memo-notice-index-page .col-md-4.d-flex.align-items-end {
        flex-direction: column;
        align-items: stretch !important;
    }
    .memo-notice-index-page .col-md-4.d-flex .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    .memo-notice-index-page .col-md-4.d-flex .btn.me-2 {
        margin-right: 0 !important;
    }
    .memo-notice-index-page .card-header .btn-primary {
        font-size: 0.9rem;
        padding: 0.5rem 0.75rem;
    }
    .memo-notice-index-page .table td,
    .memo-notice-index-page .table th {
        padding: 0.5rem 0.35rem;
        font-size: 0.85rem;
    }
    .memo-notice-index-page .d-inline-flex.gap-2 {
        flex-wrap: wrap;
        gap: 0.35rem !important;
    }
    .memo-notice-index-page .d-inline-flex .btn {
        padding: 0.35rem 0.5rem;
    }
    .memo-notice-index-page .form-check-input {
        margin-left: 0 !important;
    }
    .memo-notice-index-page .pagination {
        flex-wrap: wrap;
        justify-content: center;
    }
    .memo-notice-index-page .pagination .page-link {
        padding: 0.35rem 0.5rem;
        font-size: 0.85rem;
    }
}
</style>
@endpush


@section('scripts')
<script>
$(document).on('change', '.status-toggle-data', function () {

    let checkbox = $(this);
    let id = checkbox.data('id');
    let newStatus = checkbox.is(':checked') ? 1 : 0;

    // extra data
    let courseId = checkbox.data('course');
    let type = checkbox.data('type'); // Memo / Notice

    // Old status
    let oldStatus = newStatus === 1 ? 0 : 1;

    Swal.fire({
        title: 'Are you sure?',
        text: newStatus == 1 
            ? "Do you want to activate this template?" 
            : "Do you want to deactivate this template?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Continue',
        cancelButtonText: 'Cancel'
    }).then((result) => {

        if (!result.isConfirmed) {
            checkbox.prop('checked', oldStatus == 1);
            return;
        }

        checkbox.prop('disabled', true);

        $.ajax({
            url: "/admin/memo-notice/" + id + "/status/" + newStatus,
            type: "POST",
            data: { _token: "{{ csrf_token() }}" },
            success: function (res) {

                if (res.status === "success") {

                    if (newStatus == 1) {
                        // 🔥 Deactivate only SAME COURSE & SAME TYPE in UI
                        $('.status-toggle-data').each(function () {
                            let other = $(this);

                            if (
                                other.data('id') != id &&
                                other.data('course') == courseId &&
                                other.data('type') == type
                            ) {
                                other.prop('checked', false);
                            }
                        });
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Status updated successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }

                checkbox.prop('disabled', false);
            },
            error: function () {

                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Something went wrong. Please try again.',
                });

                checkbox.prop('disabled', false);
                checkbox.prop('checked', oldStatus == 1);
            }
        });

    });

});


</script>
@endsection