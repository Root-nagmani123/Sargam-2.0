@extends('admin.layouts.master')

@section('title', 'Subject Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/subject-master-admin.css') }}?v={{ @filemtime(public_path('css/subject-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid sm-subject-page">
    <x-breadcrum title="Subject Master">
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold text-nowrap shadow-sm"
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

            <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 mb-4">
                <button type="button" class="btn programme-dt-btn-columns" id="smBtnColumns"
                    data-bs-toggle="modal" data-bs-target="#smColumnVisibilityModal"
                    title="Show / hide columns">
                    <span>Columns</span>
                    <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                </button>
                <form method="GET" id="smSearchForm" class="programme-dt-search m-0" role="search">
                    <div class="dataTables_filter">
                        <label class="mb-0 w-100">
                            <input type="search" name="search" id="smCustomSearch"
                                class="form-control shadow-none" placeholder="Search"
                                value="{{ request('search') }}"
                                aria-label="Search subjects" autocomplete="off">
                        </label>
                    </div>
                </form>
            </div>

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
                                        <span class="badge rounded-1 programme-status-badge programme-status-badge--active">Active</span>
                                        @else
                                        <span class="badge rounded-1 programme-status-badge programme-status-badge--inactive">Inactive</span>
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
                        <form method="GET" id="smPerPageForm" class="programme-dt-count mb-0 d-inline-flex align-items-center gap-2">
                            @if (request('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            <span>Showing</span>
                            <select name="per_page"
                                class="form-select form-select-sm sm-per-page-select d-inline-block"
                                aria-label="Items per page"
                                onchange="this.form.submit()">
                                @foreach ([10, 25, 50, 100, 200] as $pp)
                                <option value="{{ $pp }}" {{ (int) $subjects->perPage() === $pp ? 'selected' : '' }}>{{ $pp }}</option>
                                @endforeach
                            </select>
                            <span>of {{ $subjects->total() }} items</span>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="smColumnVisibilityModal" tabindex="-1" aria-labelledby="smColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="smColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="smColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
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
            var subjectTable = $table.DataTable({
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
            setupSubjectColumns(subjectTable);
        }

        bindSubjectSearch();
    }

    /* ---------- Column show / hide (DataTables API) ---------- */
    var subjectColStorageKey = 'subjectGrid:hiddenColumns:v1';

    function subjectGetHiddenCols() {
        try {
            var raw = localStorage.getItem(subjectColStorageKey);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }

    function subjectPersistHiddenCols(arr) {
        try { localStorage.setItem(subjectColStorageKey, JSON.stringify(arr)); } catch (e) {}
    }

    function setupSubjectColumns(dt) {
        var $ = jQuery;
        if (!dt) {
            return;
        }
        var hidden = subjectGetHiddenCols();

        dt.columns().every(function () {
            var idx = this.index();
            this.visible(hidden.indexOf(idx) === -1, false);
        });
        dt.columns.adjust();

        var $grid = $('#smColumnToggleGrid');
        if (!$grid.length) {
            return;
        }
        $grid.empty();

        dt.columns().every(function () {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) {
                return;
            }

            var inputId = 'subjectcolvis_' + idx;
            var $cell = $('<div class="col-12 col-sm-6"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-1 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(idx) === -1);

            $cb.on('change', function () {
                var h = subjectGetHiddenCols();
                var pos = h.indexOf(idx);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else {
                    if (pos === -1) h.push(idx);
                }
                subjectPersistHiddenCols(h);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
            });

            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label);
            $grid.append($cell);
        });
    }

    /* ---------- Search (server-side via existing ?search= param) ---------- */
    function bindSubjectSearch() {
        var $ = jQuery;
        var $input = $('#smCustomSearch');
        var form = document.getElementById('smSearchForm');
        if (!$input.length || !form) {
            return;
        }
        var timer = null;
        $input.off('input.sm').on('input.sm', function () {
            clearTimeout(timer);
            timer = setTimeout(function () { form.submit(); }, 600);
        });
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
            $cell.html('<span class="badge rounded-1 programme-status-badge programme-status-badge--active">Active</span>');
        } else {
            $cell.html('<span class="badge rounded-1 programme-status-badge programme-status-badge--inactive">Inactive</span>');
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
