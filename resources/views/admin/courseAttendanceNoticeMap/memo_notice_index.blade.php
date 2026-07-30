{{-- resources/views/admin/courseAttendanceNoticeMap/memo_notice_index.blade.php --}}

@extends('admin.layouts.master')

@section('title', 'Memo/Notice Templates - Sargam | LBSNAA')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Memo/Notice Template Management" />

    <x-session_message />

    <!-- Filter Card -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="memoTemplateFilterForm" class="row g-3" onsubmit="return false;">
                <div class="col-md-4">
                    <label class="form-label">Filter by Course</label>
                    <select id="memoTemplateCourseFilter" class="form-select">
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
                    <button type="button" id="memoTemplateFilterApply" class="btn btn-primary me-2">
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
    <div class="card" >
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Memo/Notice Templates</h5>
            <a href="{{ route('admin.memo-notice.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Create New Template
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                {!! $dataTable->table(['class' => 'table text-nowrap']) !!}
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .table th {
        background-color: #f8f9fa;
    }

    .badge {
        font-size: 0.8em;
    }

    .btn-group .btn {
        margin-right: 2px;
    }
</style>
@endpush


@section('scripts')
{!! $dataTable->scripts() !!}
<script>
$(document).ready(function () {
    $('#memoTemplatesTable').on('preXhr.dt', function (e, settings, data) {
        data.course_master_pk = $('#memoTemplateCourseFilter').val() || '';
    });

    function reloadMemoTemplatesTable() {
        if ($.fn.DataTable.isDataTable('#memoTemplatesTable')) {
            $('#memoTemplatesTable').DataTable().ajax.reload();
        }
    }

    $('#memoTemplateFilterApply').on('click', reloadMemoTemplatesTable);
    $('#memoTemplateFilterForm').on('submit', function (e) {
        e.preventDefault();
        reloadMemoTemplatesTable();
    });
});
</script>
<script>
    $(document).on('change', '.status-toggle-data', function() {

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
                            // 🔥 Deactivate only SAME COURSE & SAME TYPE in UI
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
@endsection