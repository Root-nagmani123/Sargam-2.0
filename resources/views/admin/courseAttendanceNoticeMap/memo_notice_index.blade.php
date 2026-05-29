{{-- resources/views/admin/courseAttendanceNoticeMap/memo_notice_index.blade.php --}}

@extends('admin.layouts.master')

@section('title', 'Memo/Notice Templates - Sargam | LBSNAA')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="{{ asset('css/memo-notice-management-admin.css') }}?v={{ @filemtime(public_path('css/memo-notice-management-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid mnm-master-page py-3 px-3 px-lg-4">
    <x-breadcrum title="Memo/Notice Template Management" />

    <x-session_message />

    <div class="card mnm-filter-card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-3 p-md-4">
            <form action="{{ route('admin.memo-notice.index') }}" method="GET" id="filterForm">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3 programme-dt-toolbar mnm-dt-toolbar w-100">
                    <span class="programme-dt-filters-label flex-shrink-0">Filters</span>
                    <div class="programme-dt-filter-select flex-shrink-0">
                        <label class="visually-hidden">Filter by Course</label>
                        <select name="course_master_pk" class="form-select" aria-label="Filter by Course">
                            <option value="">All Courses</option>
                            @foreach ($courses as $course)
                            <option value="{{ $course->pk }}"
                                {{ request('course_master_pk') == $course->pk ? 'selected' : '' }}>
                                {{ $course->course_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-3 rounded-2 fw-semibold flex-shrink-0">
                        <i class="bi bi-funnel" aria-hidden="true"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('admin.memo-notice.index') }}" class="btn programme-dt-btn-reset flex-shrink-0">
                        Reset Filters
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mnm-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mnm-template-header">
                <h2 class="mnm-page-title mb-0">Memo/Notice Templates</h2>
                <a href="{{ route('admin.memo-notice.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 px-3 rounded-2 fw-semibold flex-shrink-0">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    <span>Create New Template</span>
                </a>
            </div>

            @if ($templates->isEmpty())
            <div class="alert alert-info border-0 rounded-3 d-flex align-items-center gap-2 mb-0">
                <i class="bi bi-info-circle flex-shrink-0" aria-hidden="true"></i>
                <span>No templates found. Create your first template!</span>
            </div>
            @else
            <div class="programme-dt-panel mnm-dt-panel">
                <div class="table-responsive mnm-dt-scroll">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table mnm-dt-table">
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap">#</th>
                                <th scope="col">Course</th>
                                <th scope="col">Title</th>
                                <th scope="col" class="text-nowrap">Type</th>
                                <th scope="col" class="text-nowrap">Status</th>
                                <th scope="col" class="text-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($templates as $template)
                            <tr>
                                <td>{{ $loop->iteration + ($templates->currentPage() - 1) * $templates->perPage() }}</td>
                                <td class="fw-medium">
                                    @if ($template->course)
                                    {{ $template->course->course_name }}
                                    @else
                                    General
                                    @endif
                                </td>
                                <td>{{ $template->title }}</td>
                                <td class="mnm-user-type">{{ $template->memo_notice_type }}</td>
                                <td>
                                    <div class="form-check form-switch d-inline-block mb-0">
                                        <input class="form-check-input status-toggle-data"
                                            type="checkbox"
                                            role="switch"
                                            data-id="{{ $template->pk }}"
                                            data-course="{{ $template->course_master_pk }}"
                                            data-type="{{ $template->memo_notice_type }}"
                                            {{ $template->active_inactive == 1 ? 'checked' : '' }}
                                            aria-label="Toggle template status">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-inline-flex align-items-center gap-2" role="group" aria-label="Memo notice template actions">
                                        <a href="{{ route('admin.memo-notice.edit', $template->pk) }}"
                                            class="btn btn-sm btn-light border d-inline-flex align-items-center text-primary"
                                            aria-label="Edit memo notice template">
                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                        </a>

                                        <form action="{{ route('admin.memo-notice.destroy', $template->pk) }}"
                                            method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this template?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="btn btn-sm btn-light border d-inline-flex align-items-center text-danger"
                                                aria-label="Delete memo notice template">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-0">
                    <div class="mnm-pagination-nav">
                        {{ $templates->links('vendor.pagination.custom') }}
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).on('change', '.status-toggle-data', function() {

        let checkbox = $(this);
        let id = checkbox.data('id');
        let newStatus = checkbox.is(':checked') ? 1 : 0;

        let courseId = checkbox.data('course');
        let type = checkbox.data('type');

        let oldStatus = newStatus === 1 ? 0 : 1;

        Swal.fire({
            title: 'Are you sure?',
            text: newStatus == 1 ?
                "Do you want to activate this template?" :
                "Do you want to deactivate this template?",
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
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(res) {

                    if (res.status === "success") {

                        if (newStatus == 1) {
                            $('.status-toggle-data').each(function() {
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
                error: function() {

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
@endpush
