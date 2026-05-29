@extends('admin.layouts.master')

@section('title', 'Subject - Sargam | Lal Bahadur')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/subject-master-admin.css') }}?v={{ @filemtime(public_path('css/subject-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid sm-subject-page">
    <x-breadcrum title="Subject Master">
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm"
            data-bs-toggle="modal"
            data-bs-target="#smAddSubjectModal"
            id="smOpenAddSubjectBtn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Subject</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card sm-dt-card overflow-hidden">
        <div class="card-body p-3 p-md-4">

            <div id="zero_config_table">
                <div class="programme-dt-panel sm-dt-panel">
                    <div class="table-responsive sm-dt-scroll">
                        <table class="table table-hover align-middle mb-0 w-100 programme-dt-table sm-subject-table" id="zero_config">
                            <thead>
                                <tr>
                                    <th scope="col" class="text-nowrap">S. No.</th>
                                    <th scope="col">Major Subject Name</th>
                                    <th scope="col" class="text-nowrap">Short Name</th>
                                    <th scope="col" class="text-nowrap">Status</th>
                                    <th scope="col" class="text-nowrap text-end sm-col-actions">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($subjects as $key => $subject)
                                <tr class="sm-subject-row" data-subject-id="{{ $subject->pk }}">
                                    <td class="text-muted fw-medium">{{ $subjects->firstItem() + $key }}</td>
                                    <td class="sm-col-name">{{ $subject->subject_name }}</td>
                                    <td class="sm-col-short">{{ $subject->sub_short_name }}</td>
                                    <td class="sm-subject-status-cell">
                                        @if ($subject->active_inactive == 1)
                                        <span class="badge rounded-pill programme-status-badge programme-status-badge--active">Active</span>
                                        @else
                                        <span class="badge rounded-pill programme-status-badge programme-status-badge--inactive">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end sm-col-actions">
                                        <div class="sm-subject-actions" role="group" aria-label="Subject actions">
                                            <button type="button"
                                                class="btn btn-sm sm-action-btn sm-action-edit sm-edit-subject-btn"
                                                data-id="{{ $subject->pk }}"
                                                aria-label="Edit subject">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                            </button>

                                            <span class="sm-action-switch-wrap">
                                                <div class="form-check form-switch sm-action-switch mb-0">
                                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                        data-table="subject_master" data-column="active_inactive"
                                                        data-id="{{ $subject->pk }}"
                                                        {{ $subject->active_inactive == 1 ? 'checked' : '' }}
                                                        aria-label="Toggle subject status">
                                                </div>
                                            </span>

                                            @if ($subject->active_inactive == 1)
                                            <button type="button"
                                                class="btn btn-sm sm-action-btn sm-action-delete"
                                                disabled
                                                aria-disabled="true"
                                                title="Cannot delete active subject"
                                                aria-label="Delete subject (disabled while active)">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                            @else
                                            <form action="{{ route('subject.destroy', $subject->pk) }}" method="POST"
                                                class="d-inline m-0 sm-delete-form"
                                                onsubmit="return confirm('Are you sure you want to delete this subject?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-sm sm-action-btn sm-action-delete"
                                                    aria-label="Delete subject">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr class="sm-subject-empty-row">
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50" aria-hidden="true"></i>
                                        <span class="fw-medium">No subjects found.</span>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($subjects->isNotEmpty())
                    <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="sm-pagination-nav">
                            {{ $subjects->appends(request()->query())->links('vendor.pagination.custom') }}
                        </div>
                        <div class="programme-dt-count mb-0">
                            Showing
                            <select class="form-select form-select-sm sm-per-page-select d-inline-block"
                                disabled
                                aria-label="Items per page">
                                <option selected>{{ $subjects->perPage() }}</option>
                            </select>
                            of {{ $subjects->total() }} items
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.subject.partials.add_subject_modal')
@include('admin.subject.partials.edit_subject_modal')

<script>
window.statusToggleUrl = "{{ route('admin.toggleStatus') }}";
</script>
@endsection

@push('scripts')
@include('admin.subject.partials.subject_modals_scripts')
<script>
(function () {
    function initSubjectTable() {
        if (typeof jQuery === 'undefined') {
            return;
        }
        var $ = jQuery;
        var $table = $('#zero_config');

        if ($table.length && $.fn.DataTable && $.fn.DataTable.isDataTable($table)) {
            $table.DataTable().destroy();
        }

        if ($table.length && $.fn.DataTable) {
            $table.DataTable({
                responsive: true,
                paging: false,
                searching: false,
                info: false,
                lengthChange: false,
                ordering: true,
                order: [],
                dom: 't',
                autoWidth: false
            });
        }
    }

    function parseAjaxData(data) {
        if (!data) {
            return {};
        }
        if (typeof data === 'object') {
            return data;
        }
        var params = {};
        String(data).split('&').forEach(function (pair) {
            var parts = pair.split('=');
            if (parts[0]) {
                params[decodeURIComponent(parts[0])] = decodeURIComponent((parts[1] || '').replace(/\+/g, ' '));
            }
        });
        return params;
    }

    function updateSubjectStatusBadge(id, status) {
        var $row = $('.sm-subject-row[data-subject-id="' + id + '"]');
        if (!$row.length) {
            return;
        }
        var $cell = $row.find('.sm-subject-status-cell');
        if (String(status) === '1') {
            $cell.html('<span class="badge rounded-pill programme-status-badge programme-status-badge--active">Active</span>');
        } else {
            $cell.html('<span class="badge rounded-pill programme-status-badge programme-status-badge--inactive">Inactive</span>');
        }
    }

    if (typeof jQuery !== 'undefined') {
        jQuery(document).ajaxSuccess(function (event, xhr, settings) {
            if (!settings.url || settings.url.indexOf('toggle-status') === -1) {
                return;
            }
            var params = parseAjaxData(settings.data);
            if (!params.id) {
                return;
            }
            updateSubjectStatusBadge(params.id, params.status);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSubjectTable);
    } else {
        initSubjectTable();
    }
})();
</script>
@endpush
