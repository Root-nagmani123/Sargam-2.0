{{-- resources/views/admin/courseAttendanceNoticeMap/memo_notice_index.blade.php --}}

@extends('admin.layouts.master')

@section('title', 'Memo/Notice Template Management - Sargam | Lal Bahadur Shastri National Academy of Administration')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css">
<link rel="stylesheet" href="{{ asset('css/memo-notice-management-admin.css') }}?v={{ @filemtime(public_path('css/memo-notice-management-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid mnm-master-page mnm-template-index-page">
    <x-breadcrum title="Memo/Notice Template Management">
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm"
            data-bs-toggle="modal"
            data-bs-target="#mnmAddTemplateModal"
            id="mnmOpenAddTemplateBtn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Memo/Notice Template</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card mnm-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex justify-content-end mb-3 mnm-template-toolbar-top">
                <button type="button" class="btn mnm-btn-download" id="mnmTemplateExportCsv" aria-label="Download table data">
                    <i class="bi bi-download" aria-hidden="true"></i>
                    <span>Download</span>
                </button>
            </div>

            <form action="{{ route('admin.memo-notice.index') }}" method="GET" id="filterForm">
                <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3 mb-4 programme-dt-toolbar mnm-dt-toolbar w-100">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <span class="programme-dt-filters-label flex-shrink-0">Filters</span>

                        <div class="programme-dt-filter-select flex-shrink-0">
                            <label for="course_master_pk" class="visually-hidden">Program Name</label>
                            <select name="course_master_pk" id="course_master_pk" class="form-select" aria-label="Program Name">
                                <option value="">Program Name</option>
                                @foreach ($courses as $course)
                                <option value="{{ $course->pk }}"
                                    {{ request('course_master_pk') == $course->pk ? 'selected' : '' }}>
                                    {{ $course->course_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="programme-dt-filter-select flex-shrink-0">
                            <label for="mnm_template_status_filter" class="visually-hidden">Status</label>
                            <select id="mnm_template_status_filter" class="form-select" aria-label="Filter by status (current page)">
                                <option value="">Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <a href="{{ route('admin.memo-notice.index') }}" class="btn programme-dt-btn-reset flex-shrink-0">
                            Reset Filters
                        </a>
                    </div>

                    <div class="mnm-table-search-slot ms-xl-auto flex-shrink-0">
                        <div class="dropdown">
                            <button type="button"
                                class="btn mnm-search-trigger"
                                id="mnmTemplateSearchTrigger"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false"
                                aria-label="Search templates on this page">
                                <i class="bi bi-search" aria-hidden="true"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 p-3 mnm-search-menu">
                                <label for="mnm_template_search" class="form-label small text-secondary mb-2">Search (current page)</label>
                                <input type="search"
                                    class="form-control mnm-search-input shadow-none"
                                    id="mnm_template_search"
                                    placeholder="Course, title, type..."
                                    autocomplete="off"
                                    aria-label="Search templates on current page">
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            @if ($templates->isEmpty())
            <div class="mnm-empty-state rounded-3 border bg-light">
                <i class="bi bi-inbox" aria-hidden="true"></i>
                <p class="mb-0 fw-medium">No templates found. Create your first template!</p>
            </div>
            @else
            <div class="programme-dt-panel mnm-dt-panel">
                <div class="table-responsive mnm-dt-scroll">
                    <table id="mnmTemplateTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table mnm-dt-table mnm-template-table">
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap">S. No.</th>
                                <th scope="col">Course Name</th>
                                <th scope="col">Title</th>
                                <th scope="col" class="text-nowrap">Type</th>
                                <th scope="col" class="text-nowrap">Status</th>
                                <th scope="col" class="text-nowrap text-end mnm-col-actions">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($templates as $template)
                            <tr class="mnm-template-row"
                                data-status="{{ $template->active_inactive }}"
                                data-search="{{ strtolower(trim(($template->course->course_name ?? 'General') . ' ' . $template->title . ' ' . $template->memo_notice_type)) }}">
                                <td class="text-muted fw-medium">{{ $loop->iteration + ($templates->currentPage() - 1) * $templates->perPage() }}</td>
                                <td class="fw-medium mnm-col-course">
                                    @if ($template->course)
                                    {{ $template->course->course_name }}
                                    @else
                                    General
                                    @endif
                                </td>
                                <td class="mnm-col-title">{{ $template->title }}</td>
                                <td class="mnm-user-type">{{ $template->memo_notice_type }}</td>
                                <td class="mnm-template-status-cell">
                                    @if ($template->active_inactive == 1)
                                    <span class="badge rounded-pill mnm-status-badge mnm-status-active">Active</span>
                                    @else
                                    <span class="badge rounded-pill mnm-status-badge mnm-status-inactive">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end mnm-col-actions">
                                    <div class="mnm-template-actions" role="group" aria-label="Memo notice template actions">
                                        <button type="button"
                                            class="btn btn-sm mnm-action-btn mnm-action-edit mnm-edit-template-btn"
                                            data-id="{{ $template->pk }}"
                                            aria-label="Edit memo notice template">
                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                        </button>

                                        <span class="mnm-action-switch-wrap">
                                            <div class="form-check form-switch mnm-action-switch mb-0">
                                                <input class="form-check-input status-toggle-data"
                                                    type="checkbox"
                                                    role="switch"
                                                    data-id="{{ $template->pk }}"
                                                    data-course="{{ $template->course_master_pk }}"
                                                    data-type="{{ $template->memo_notice_type }}"
                                                    {{ $template->active_inactive == 1 ? 'checked' : '' }}
                                                    aria-label="Toggle template status">
                                            </div>
                                        </span>

                                        <form action="{{ route('admin.memo-notice.destroy', $template->pk) }}"
                                            method="POST"
                                            class="d-inline m-0"
                                            onsubmit="return confirm('Are you sure you want to delete this template?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="btn btn-sm mnm-action-btn mnm-action-delete"
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

                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="mnm-pagination-nav">
                        {{ $templates->appends(request()->query())->links('vendor.pagination.custom') }}
                    </div>
                    <div class="programme-dt-count mnm-dt-count mb-0">
                        Showing {{ $templates->firstItem() ?? 0 }}
                        to {{ $templates->lastItem() ?? 0 }}
                        of {{ $templates->total() }} items
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@include('admin.courseAttendanceNoticeMap.partials.add_template_modal')
@include('admin.courseAttendanceNoticeMap.partials.edit_template_modal')
@endsection

@push('scripts')
@include('admin.courseAttendanceNoticeMap.partials.template_modals_scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof jQuery === 'undefined') {
        return;
    }
    var $ = jQuery;

    $('#course_master_pk').on('change', function () {
        $('#filterForm').submit();
    });

    $('#mnmTemplateExportCsv').on('click', function () {
        var table = document.getElementById('mnmTemplateTable');
        if (!table) {
            return;
        }
        var rows = table.querySelectorAll('tr');
        var csv = [];
        rows.forEach(function (row) {
            var cols = row.querySelectorAll('th, td');
            var rowData = [];
            cols.forEach(function (col, index) {
                if (index === cols.length - 1) {
                    return;
                }
                var text = (col.innerText || '').replace(/\s+/g, ' ').trim().replace(/"/g, '""');
                rowData.push('"' + text + '"');
            });
            if (rowData.length) {
                csv.push(rowData.join(','));
            }
        });
        var blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'memo-notice-templates.csv';
        link.click();
        URL.revokeObjectURL(link.href);
    });

    function applyClientFilters() {
        var statusVal = $('#mnm_template_status_filter').val();
        var searchVal = ($('#mnm_template_search').val() || '').toLowerCase().trim();

        $('.mnm-template-row').each(function () {
            var $row = $(this);
            var rowStatus = String($row.data('status'));
            var rowSearch = String($row.data('search') || '');
            var showStatus = statusVal === '' || rowStatus === String(statusVal);
            var showSearch = !searchVal || rowSearch.indexOf(searchVal) !== -1;
            $row.toggle(showStatus && showSearch);
        });
    }

    window.applyMnmTemplateClientFilters = applyClientFilters;

    $('#mnm_template_status_filter, #mnm_template_search').on('input change', applyClientFilters);

    $(document).on('change', '.status-toggle-data', function () {

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
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#004a93'
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
            success: function (res) {

                if (res.status === "success") {

                    if (newStatus == 1) {
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

                    var $badgeCell = checkbox.closest('tr').find('.mnm-template-status-cell');
                    if (newStatus == 1) {
                        $badgeCell.html('<span class="badge rounded-pill mnm-status-badge mnm-status-active">Active</span>');
                        checkbox.closest('tr').attr('data-status', '1');
                    } else {
                        $badgeCell.html('<span class="badge rounded-pill mnm-status-badge mnm-status-inactive">Inactive</span>');
                        checkbox.closest('tr').attr('data-status', '0');
                    }
                    if (typeof window.applyMnmTemplateClientFilters === 'function') {
                        window.applyMnmTemplateClientFilters();
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
});
</script>
@endpush
