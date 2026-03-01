{{-- resources/views/admin/courseAttendanceNoticeMap/memo_notice_index.blade.php --}}

@extends('admin.layouts.master')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">

@section('title', 'Memo/Notice Templates - Sargam | LBSNAA')

@section('setup_content')
<div class="container-fluid py-2 py-md-3 px-3 px-md-4">
    <x-breadcrum title="Memo/Notice Template Management" />

    <x-session_message />

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 border-start border-primary border-4 rounded-3 mb-4 overflow-hidden">
        <div class="card-header bg-white border-0 border-bottom border-2 border-primary border-opacity-25 py-3 px-3 px-md-4">
            <h6 class="mb-0 fw-semibold">
                Filters
            </h6>
        </div>
        <div class="card-body p-3 p-md-4">
            <form id="filterForm" class="row g-3 g-md-4 align-items-end">
                <div class="col-12 col-sm-6 col-lg-4">
                    <label for="course_master_pk" class="form-label small fw-semibold">
                        <span>Course</span>
                    </label>
                    <select name="course_master_pk" id="course_master_pk" class="form-select form-select-sm rounded-2 border-primary border-opacity-25">
                        <option value="">All Courses</option>
                        @foreach ($courses as $course)
                        <option value="{{ $course->pk }}"
                            {{ request('course_master_pk') == $course->pk ? 'selected' : '' }}>
                            {{ $course->course_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4 d-flex flex-wrap gap-2 justify-content-start justify-content-sm-end justify-content-lg-start">
                    <button type="submit" class="btn btn-primary px-4 rounded-1 d-inline-flex align-items-center gap-2">
                        <i class="fas fa-search"></i>
                        <span>Apply</span>
                    </button>
                    <a href="{{ route('admin.memo-notice.index') }}" class="btn btn-outline-secondary px-4 rounded-1 d-inline-flex align-items-center gap-2 filter-reset-btn" role="button">
                        <i class="fas fa-times"></i>
                        <span>Clear</span>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="card shadow-sm border-0 border-start border-primary border-4 rounded-3 overflow-hidden">
        <div class="card-header bg-white border-0 border-bottom border-2 border-primary border-opacity-25 py-3 px-3 px-md-4 d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-2">
            <h5 class="card-title mb-0 fw-semibold">
                Memo/Notice Templates
            </h5>
            <a href="{{ route('admin.memo-notice.create') }}" class="btn btn-primary px-4 rounded-1 d-inline-flex align-items-center gap-2 justify-content-center flex-shrink-0">
                <i class="fas fa-plus"></i>
                <span>Create New Template</span>
            </a>
        </div>
        <div class="card-body p-3 p-md-4">
            <div class="table-responsive">
                {!! $dataTable->table(['class' => 'table text-nowrap', 'id' => 'memo-notice-template-table']) !!}
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
$(document).ready(function() {
    // Choices.js for Course filter (searchable)
    var courseEl = document.getElementById('course_master_pk');
    if (typeof Choices !== 'undefined' && courseEl && !courseEl.dataset.choicesInitialized) {
        window.memoNoticeIndexChoices = new Choices(courseEl, {
            searchEnabled: true,
            searchPlaceholderValue: 'Search courses...',
            itemSelectText: '',
            shouldSort: false,
            placeholder: true,
            placeholderValue: 'All Courses'
        });
        courseEl.dataset.choicesInitialized = 'true';
    }
});

$(document).on('change', '.status-toggle-data', function () {
    var checkbox = $(this);
    var id = checkbox.data('id');
    var newStatus = checkbox.is(':checked') ? 1 : 0;
    var courseId = checkbox.data('course');
    var type = checkbox.data('type');
    var oldStatus = newStatus === 1 ? 0 : 1;

    Swal.fire({
        title: 'Are you sure?',
        text: newStatus == 1 ? "Do you want to activate this template?" : "Do you want to deactivate this template?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Continue',
        cancelButtonText: 'Cancel'
    }).then(function (result) {
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
                        $('.status-toggle-data').each(function () {
                            var other = $(this);
                            if (other.data('id') != id && other.data('course') == courseId && other.data('type') == type) {
                                other.prop('checked', false);
                            }
                        });
                    }
                    Swal.fire({ icon: 'success', title: 'Success!', text: 'Status updated successfully.', timer: 1500, showConfirmButton: false });
                    if ($.fn.DataTable && $.fn.DataTable.isDataTable('#memo-notice-template-table')) {
                        $('#memo-notice-template-table').DataTable().ajax.reload(null, false);
                    }
                }
                checkbox.prop('disabled', false);
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error!', text: 'Something went wrong. Please try again.' });
                checkbox.prop('disabled', false);
                checkbox.prop('checked', oldStatus == 1);
            }
        });
    });
});

$('#filterForm').on('submit', function(e) {
    e.preventDefault();
    if ($.fn.DataTable && $.fn.DataTable.isDataTable('#memo-notice-template-table')) {
        $('#memo-notice-template-table').DataTable().ajax.reload();
    }
});

$('.filter-reset-btn').on('click', function(e) {
    e.preventDefault();
    $('#course_master_pk').val('');
    if (window.memoNoticeIndexChoices) {
        window.memoNoticeIndexChoices.setChoiceByValue('', true);
    }
    if ($.fn.DataTable && $.fn.DataTable.isDataTable('#memo-notice-template-table')) {
        $('#memo-notice-template-table').DataTable().ajax.reload();
    }
});
</script>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
@endpush
