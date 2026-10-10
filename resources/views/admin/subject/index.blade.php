@extends('admin.layouts.master')

@section('title', 'Subject Master')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('css/subject-master-admin.css') }}?v={{ @filemtime(public_path('css/subject-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid mst-page sm-subject-page">
    <x-breadcrum title="Subject Master" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                data-bs-toggle="modal" data-bs-target="#smAddSubjectModal" id="smOpenAddSubjectBtn">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Subject</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="smBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#smColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    {{-- Server-side search (SubjectMasterController::index reads ?search=).
                         Submits itself 600 ms after typing stops; per_page is carried
                         so a search does not reset the page size. --}}
                    <form method="GET" id="smSearchForm" class="programme-dt-search m-0" role="search">
                        @if (request()->filled('per_page'))
                            <input type="hidden" name="per_page" value="{{ $subjects->perPage() }}">
                        @endif
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
            </div>

            {{-- Server-paginated (SubjectMasterController::index). The client
                 DataTable on #zero_config only sorts the current page and drives
                 the Columns modal, so it opts out of the global enhancer
                 (data-sargam-dt-ui="false") — otherwise it empties the
                 hand-written footer below (docs/new-design-index-page.md §5). --}}
            <div class="programme-dt-panel" id="zero_config_table">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table"
                           id="zero_config" data-sargam-dt-ui="false">
                        <caption class="visually-hidden">Subjects</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap">S. No.</th>
                                <th scope="col">Major Subject Name</th>
                                <th scope="col" class="text-nowrap">Short Name</th>
                                <th scope="col" class="text-nowrap">Status</th>
                                <th scope="col" class="text-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($subjects as $key => $subject)
                                @php $isActive = (int) $subject->active_inactive === 1; @endphp
                                <tr class="sm-subject-row" data-subject-id="{{ $subject->pk }}">
                                    <td>{{ $subjects->firstItem() + $key }}</td>
                                    <td>{{ $subject->subject_name }}</td>
                                    <td>{{ $subject->sub_short_name }}</td>
                                    <td class="sm-subject-status-cell" data-order="{{ $isActive ? 1 : 0 }}">
                                        @include('admin.master.partials.grid-status', ['active' => $isActive])
                                    </td>
                                    <td>
                                        @include('admin.master.partials.grid-actions', [
                                            'name'   => $subject->subject_name,
                                            'edit'   => [
                                                'class' => 'sm-edit-subject-btn',
                                                'attrs' => ['data-id' => $subject->pk],
                                            ],
                                            'toggle' => [
                                                'active' => $isActive,
                                                'table'  => 'subject_master',
                                                'column' => 'active_inactive',
                                                'id'     => $subject->pk,
                                                'attrs'  => ['data-reload-page' => true],
                                            ],
                                            'delete' => $isActive
                                                ? ['disabled' => true, 'reason' => 'Cannot delete an active subject. Deactivate it first.']
                                                : [
                                                    'action'     => route('subject.destroy', $subject->pk),
                                                    'form_class' => 'sm-delete-form',
                                                ],
                                        ])
                                    </td>
                                </tr>
                            @empty
                                <tr class="mst-empty sm-subject-empty-row">
                                    <td colspan="5">No subjects found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
                    <div class="programme-dt-pagination">
                        {{ $subjects->appends(request()->query())->links('vendor.pagination.custom') }}
                    </div>
                    <form method="GET" id="smPerPageForm"
                          class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto mb-0">
                        @if (request('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif
                        <div class="dataTables_length">
                            <label class="mb-0" for="smPerPage">Showing
                                <select name="per_page" id="smPerPage"
                                        class="form-select form-select-sm"
                                        aria-label="Rows per page" onchange="this.form.submit()">
                                    @foreach ([10, 25, 50, 100, 200] as $pp)
                                        <option value="{{ $pp }}" {{ (int) $subjects->perPage() === $pp ? 'selected' : '' }}>{{ $pp }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                        <div class="dataTables_info" aria-live="polite">of {{ number_format($subjects->total()) }} items</div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Column Visibility --}}
<div class="modal fade" id="smColumnVisibilityModal" tabindex="-1"
     aria-labelledby="smColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="smColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3 mst-colvis-grid" id="smColumnToggleGrid"></div>
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
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
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

        // Only a table with real rows gets a DataTable: the empty-state row is
        // a single colspan cell, which DataTables rejects.
        if ($table.length && $.fn.DataTable && !$table.find('tbody tr.mst-empty').length) {
            var subjectTable = $table.DataTable({
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
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
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

    // The badge (Status) and the switch (Action) live in different columns,
    // and Delete is only offered on inactive rows — reload once custom.js has
    // saved the new status so all three agree.
    if (window.MstAdmin) {
        window.MstAdmin.reloadPageOnStatusToggle();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSubjectTable);
    } else {
        initSubjectTable();
    }
})();
</script>
@endpush
