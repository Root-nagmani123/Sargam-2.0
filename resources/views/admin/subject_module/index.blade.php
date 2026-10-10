@extends('admin.layouts.master')

@section('title', 'Subject Module')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('css/subject-module-admin.css') }}?v={{ @filemtime(public_path('css/subject-module-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid mst-page sm-module-page">
    <x-breadcrum title="Subject Module" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                data-bs-toggle="modal" data-bs-target="#smAddModuleModal" id="smOpenAddModuleBtn">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Subject Module</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="smModuleBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#smModuleColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    {{-- Server-side search (SubjectModuleController::index reads ?search=).
                         Submits itself 600 ms after typing stops; per_page is carried
                         so a search does not reset the page size. --}}
                    <form method="GET" id="smModuleSearchForm" class="programme-dt-search m-0" role="search">
                        @if (request()->filled('per_page'))
                            <input type="hidden" name="per_page" value="{{ $modules->perPage() }}">
                        @endif
                        <div class="dataTables_filter">
                            <label class="mb-0 w-100">
                                <input type="search" name="search" id="smModuleSearch"
                                       class="form-control shadow-none" placeholder="Search"
                                       value="{{ request('search') }}"
                                       aria-label="Search subject modules" autocomplete="off">
                            </label>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Server-paginated (SubjectModuleController::index). The client
                 DataTable on #zero_config only sorts the current page and drives
                 the Columns modal, so it opts out of the global enhancer
                 (data-sargam-dt-ui="false") — otherwise it empties the
                 hand-written footer below (docs/new-design-index-page.md §5). --}}
            <div class="programme-dt-panel" id="zero_config_table">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table"
                           id="zero_config" data-sargam-dt-ui="false">
                        <caption class="visually-hidden">Subject modules</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap">S. No.</th>
                                <th scope="col">Module Name</th>
                                <th scope="col" class="text-nowrap">Status</th>
                                <th scope="col" class="text-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($modules as $key => $module)
                                @php $isActive = (int) $module->active_inactive === 1; @endphp
                                <tr class="sm-module-row" data-module-id="{{ $module->pk }}"
                                    data-search="{{ strtolower(trim($module->module_name)) }}">
                                    <td>{{ $modules->firstItem() + $key }}</td>
                                    <td>{{ $module->module_name }}</td>
                                    <td class="sm-module-status-cell" data-order="{{ $isActive ? 1 : 0 }}">
                                        @include('admin.master.partials.grid-status', ['active' => $isActive])
                                    </td>
                                    <td>
                                        @include('admin.master.partials.grid-actions', [
                                            'name'   => $module->module_name,
                                            'edit'   => [
                                                'class' => 'sm-edit-module-btn',
                                                'attrs' => ['data-id' => $module->pk],
                                            ],
                                            'toggle' => [
                                                'active' => $isActive,
                                                'table'  => 'subject_module_master',
                                                'column' => 'active_inactive',
                                                'id'     => $module->pk,
                                                'attrs'  => ['data-reload-page' => true],
                                            ],
                                            'delete' => $isActive
                                                ? ['disabled' => true, 'reason' => 'Cannot delete an active subject module. Deactivate it first.']
                                                : [
                                                    'action'     => route('subject-module.destroy', $module->pk),
                                                    'form_class' => 'sm-delete-form',
                                                ],
                                        ])
                                    </td>
                                </tr>
                            @empty
                                <tr class="mst-empty sm-module-empty-row">
                                    <td colspan="4">No subject modules found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
                    <div class="programme-dt-pagination">
                        {{ $modules->appends(request()->query())->links('vendor.pagination.custom') }}
                    </div>
                    <form method="GET" id="smModulePerPageForm"
                          class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto mb-0">
                        @if (request('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif
                        <div class="dataTables_length">
                            <label class="mb-0" for="smModulePerPage">Showing
                                <select name="per_page" id="smModulePerPage"
                                        class="form-select form-select-sm"
                                        aria-label="Rows per page" onchange="this.form.submit()">
                                    @foreach ([10, 25, 50, 100, 200] as $pp)
                                        <option value="{{ $pp }}" {{ (int) $modules->perPage() === $pp ? 'selected' : '' }}>{{ $pp }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                        <div class="dataTables_info" aria-live="polite">of {{ number_format($modules->total()) }} items</div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Column Visibility --}}
<div class="modal fade" id="smModuleColumnVisibilityModal" tabindex="-1"
     aria-labelledby="smModuleColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="smModuleColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3 mst-colvis-grid" id="smModuleColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
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
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
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

        // Only a table with real rows gets a DataTable: the empty-state row is
        // a single colspan cell, which DataTables rejects.
        if ($table.length && $.fn.DataTable && !$table.find('tbody tr.mst-empty').length) {
            var moduleTable = $table.DataTable({
                responsive: true,
                paging: false,
                searching: false,
                info: false,
                lengthChange: false,
                ordering: true,
                order: [],
                // Action holds buttons, not data — no sort caret on it.
                columnDefs: [{ targets: -1, orderable: false }],
                dom: 't',
                autoWidth: false
            });
            setupModuleColumns(moduleTable);
        }

        bindModuleSearch();
    }

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
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-1 px-3 py-2 mb-0 w-100"></label>')
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

    /* ---------- Search (server-side via ?search= param) ---------- */
    function bindModuleSearch() {
        var $ = jQuery;
        var $input = $('#smModuleSearch');
        var form = document.getElementById('smModuleSearchForm');
        if (!$input.length || !form) {
            return;
        }
        var timer = null;
        $input.off('input.smmodule').on('input.smmodule', function () {
            clearTimeout(timer);
            timer = setTimeout(function () { form.submit(); }, 600);
        });
    }

    // The badge (Status) and the switch (Action) live in different columns,
    // and Delete is only offered on inactive rows — reload once custom.js has
    // saved the new status so all three agree.
    if (window.MstAdmin) {
        window.MstAdmin.reloadPageOnStatusToggle();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModuleTable);
    } else {
        initModuleTable();
    }
})();
</script>
@endpush
