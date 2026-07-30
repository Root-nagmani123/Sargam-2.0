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

            <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 mb-4">
                <button type="button" class="btn programme-dt-btn-columns" id="smModuleBtnColumns"
                    data-bs-toggle="modal" data-bs-target="#smModuleColumnVisibilityModal"
                    title="Show / hide columns">
                    <span>Columns</span>
                    <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                </button>
                <div class="programme-dt-search"></div>
            </div>

            <div class="programme-dt-panel sm-dt-panel">
                <div class="table-responsive sm-dt-scroll">
                    {!! $dataTable->table() !!}
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="smModuleColumnVisibilityModal" tabindex="-1" aria-labelledby="smModuleColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="smModuleColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="smModuleColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
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
{!! $dataTable->scripts() !!}
@include('admin.subject_module.partials.module_modals_scripts')
<script>
(function () {
    /* ---------- Column show / hide (DataTables API) ---------- */
    var moduleColStorageKey = 'subjectModuleGrid:hiddenColumns:v1';

    function moduleGetHiddenCols() {
        try {
            var raw = localStorage.getItem(moduleColStorageKey);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }

    function modulePersistHiddenCols(arr) {
        try { localStorage.setItem(moduleColStorageKey, JSON.stringify(arr)); } catch (e) {}
    }

    function setupModuleColumns(dt) {
        var $ = jQuery;
        if (!dt) {
            return;
        }
        var hidden = moduleGetHiddenCols();

        dt.columns().every(function () {
            var idx = this.index();
            this.visible(hidden.indexOf(idx) === -1, false);
        });
        dt.columns.adjust();

        var $grid = $('#smModuleColumnToggleGrid');
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

            var inputId = 'modulecolvis_' + idx;
            var $cell = $('<div class="col-12 col-sm-6"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(idx) === -1);

            $cb.on('change', function () {
                var h = moduleGetHiddenCols();
                var pos = h.indexOf(idx);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else {
                    if (pos === -1) h.push(idx);
                }
                modulePersistHiddenCols(h);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
            });

            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label);
            $grid.append($cell);
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

    function updateModuleStatusBadge(id, status) {
        var $row = $('.sm-module-row[data-module-id="' + id + '"]');
        if (!$row.length) {
            return;
        }
        var $cell = $row.find('.sm-module-status-cell');
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
            updateModuleStatusBadge(params.id, params.status);
        });
    }

    /* ---------- Wait for Yajra's own DataTable init, then wire the Columns modal ---------- */
    function initModuleColumnsWhenReady(attempts) {
        if (typeof jQuery === 'undefined' || !jQuery.fn.DataTable) {
            return;
        }
        if (!jQuery.fn.DataTable.isDataTable('#zero_config')) {
            if (attempts > 0) {
                setTimeout(function () { initModuleColumnsWhenReady(attempts - 1); }, 100);
            }
            return;
        }
        setupModuleColumns(jQuery('#zero_config').DataTable());
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initModuleColumnsWhenReady(50); });
    } else {
        initModuleColumnsWhenReady(50);
    }
})();
</script>
@endpush
