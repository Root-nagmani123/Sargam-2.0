@extends('admin.layouts.master')

@section('title', 'Subject Module - Sargam | Lal Bahadur')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/subject-module-admin.css') }}?v={{ @filemtime(public_path('css/subject-module-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid sm-module-page">
    <x-breadcrum title="Subject Module">
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm"
            data-bs-toggle="modal"
            data-bs-target="#smAddModuleModal"
            id="smOpenAddModuleBtn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Subject Module</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card sm-dt-card overflow-hidden">
        <div class="card-body p-3 p-md-4">

            <div id="zero_config_table">
                <div class="programme-dt-panel sm-dt-panel">
                    <div class="table-responsive sm-dt-scroll">
                        <table class="table table-hover align-middle mb-0 w-100 programme-dt-table sm-module-table" id="zero_config">
                            <thead>
                                <tr>
                                    <th scope="col" class="text-nowrap">S. No.</th>
                                    <th scope="col">Module Name</th>
                                    <th scope="col" class="text-nowrap">Status</th>
                                    <th scope="col" class="text-nowrap text-end sm-col-actions">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($modules as $key => $module)
                                <tr class="sm-module-row"
                                    data-module-id="{{ $module->pk }}"
                                    data-search="{{ strtolower(trim($module->module_name)) }}">
                                    <td class="text-muted fw-medium">{{ $modules->firstItem() + $key }}</td>
                                    <td class="sm-col-name">{{ $module->module_name }}</td>
                                    <td class="sm-module-status-cell">
                                        @if ($module->active_inactive == 1)
                                        <span class="badge rounded-pill programme-status-badge programme-status-badge--active">Active</span>
                                        @else
                                        <span class="badge rounded-pill programme-status-badge programme-status-badge--inactive">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end sm-col-actions">
                                        <div class="sm-module-actions" role="group" aria-label="Subject module actions">
                                            <button type="button"
                                                class="btn btn-sm sm-action-btn sm-action-edit sm-edit-module-btn"
                                                data-id="{{ $module->pk }}"
                                                aria-label="Edit subject module">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                            </button>

                                            <span class="sm-action-switch-wrap">
                                                <div class="form-check form-switch sm-action-switch mb-0">
                                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                        data-table="subject_module_master" data-column="active_inactive"
                                                        data-id="{{ $module->pk }}"
                                                        {{ $module->active_inactive == 1 ? 'checked' : '' }}
                                                        aria-label="Toggle subject module status">
                                                </div>
                                            </span>

                                            @if ($module->active_inactive == 1)
                                            <button type="button"
                                                class="btn btn-sm sm-action-btn sm-action-delete"
                                                disabled
                                                aria-disabled="true"
                                                title="Cannot delete active subject module"
                                                aria-label="Delete subject module (disabled while active)">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                            @else
                                            <form action="{{ route('subject-module.destroy', $module->pk) }}" method="POST"
                                                class="d-inline m-0 sm-delete-form"
                                                onsubmit="return confirm('Are you sure you want to delete this Subject Module?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-sm sm-action-btn sm-action-delete"
                                                    aria-label="Delete subject module">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr class="sm-module-empty-row">
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50" aria-hidden="true"></i>
                                        <span class="fw-medium">No subject modules found.</span>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($modules->isNotEmpty())
                    <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="sm-pagination-nav">
                            {{ $modules->appends(request()->query())->links('vendor.pagination.custom') }}
                        </div>
                        <div class="programme-dt-count mb-0">
                            Showing
                            <select class="form-select form-select-sm sm-per-page-select d-inline-block"
                                disabled
                                aria-label="Items per page">
                                <option selected>{{ $modules->perPage() }}</option>
                            </select>
                            of {{ $modules->total() }} items
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.subject_module.partials.add_module_modal')
@include('admin.subject_module.partials.edit_module_modal')

<script>
window.statusToggleUrl = "{{ route('admin.toggleStatus') }}";
</script>
@endsection

@push('scripts')
@include('admin.subject_module.partials.module_modals_scripts')
<script>
(function () {
    function initModuleTable() {
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

    function updateModuleStatusBadge(id, status) {
        var $row = $('.sm-module-row[data-module-id="' + id + '"]');
        if (!$row.length) {
            return;
        }
        var $cell = $row.find('.sm-module-status-cell');
        if (String(status) === '1') {
            $cell.html('<span class="badge rounded-pill programme-status-badge programme-status-badge--active">Active</span>');
        } else {
            $cell.html('<span class="badge rounded-pill programme-status-badge programme-status-badge--inactive">Inactive</span>');
        }
    }

    function applyModuleSearch() {
        if (typeof jQuery === 'undefined') {
            return;
        }
        var searchVal = (jQuery('#sm_module_search').val() || '').toLowerCase().trim();
        jQuery('.sm-module-row').each(function () {
            var $row = jQuery(this);
            var rowSearch = String($row.data('search') || '');
            $row.toggle(!searchVal || rowSearch.indexOf(searchVal) !== -1);
        });
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
            updateModuleStatusBadge(params.id, params.status);
        });

        jQuery('#sm_module_search').on('input', applyModuleSearch);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModuleTable);
    } else {
        initModuleTable();
    }
})();
</script>
@endpush
