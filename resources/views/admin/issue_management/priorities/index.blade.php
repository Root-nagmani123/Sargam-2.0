@extends('admin.layouts.master')

@section('title', 'Manage Priorities')

@push('styles')
{{-- Shared Centcom index chrome — the same file Manage Categories / Sub-Categories
     use, so the three pages cannot drift apart. See docs/new-design-index-page.md. --}}
<link rel="stylesheet"
      href="{{ asset('css/issue-management-admin.css') }}?v={{ @filemtime(public_path('css/issue-management-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    // Search, sort and paging are DataTables' now; ?cols= is appended to these
    // links by ipUpdateExportCols().
    $exportQuery = [];
@endphp
<div class="container-fluid ic-page">
    <x-breadcrum title="Manage Priorities" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="ipAddBtn" data-bs-toggle="modal" data-bs-target="#addPriorityModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Priority</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Secondary actions (Download / Print) — above the card, per §1 --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 ic-secondary-actions">
        {{-- More than one download format → dropdown, per §1 of the doc. --}}
        <div class="dropdown">
            <button type="button" id="ipDownloadToggle"
                    class="btn programme-dt-btn-columns border-0 text-primary dropdown-toggle"
                    data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="ipDownloadToggle">
                <li><a class="dropdown-item" id="ipDownloadLink"
                       href="{{ route('admin.issue-priorities.export', array_merge(['format' => 'csv'], $exportQuery)) }}">
                        <i class="bi bi-filetype-csv me-1" aria-hidden="true"></i> CSV</a></li>
                <li><a class="dropdown-item" id="ipExcelLink"
                       href="{{ route('admin.issue-priorities.export', array_merge(['format' => 'excel'], $exportQuery)) }}">
                        <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel (.xlsx)</a></li>
                <li><a class="dropdown-item" id="ipPdfLink"
                       href="{{ route('admin.issue-priorities.export', array_merge(['format' => 'pdf'], $exportQuery)) }}">
                        <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> PDF</a></li>
            </ul>
        </div>
        <a href="{{ route('admin.issue-priorities.export', array_merge(['format' => 'print'], $exportQuery)) }}"
           id="ipPrintLink"
           target="_blank" rel="noopener" class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </a>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar: columns + search on the right (§2) --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 ic-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="ipBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#ipColumnVisibilityModal"
                            title="Show / hide columns"
                            style="border: 1px solid #d0d5dd; background: #fff; color: #344054;">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    {{-- Always-visible search box: datatable-global-ui.js moves DataTables'
                         own filter in here, so it filters as you type instead of
                         reloading the page on Enter. --}}
                    <div id="ipDtSearch" class="programme-dt-search" data-dt-search-for="issuePrioritiesTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {{-- No data-sargam-dt-ui opt-out: DataTables paginates this grid now. --}}
                    <table id="issuePrioritiesTable"
                           class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Priority</th>
                                <th scope="col">Description</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($priorities as $priority)
                                @php
                                    $isActive = (int) $priority->status === 1;
                                    // destroy() refuses a priority that any issue log references.
                                    $inUse = (int) ($priority->issue_logs_count ?? 0) > 0;
                                @endphp
                                <tr>
                                    {{-- Renumbered on every draw (see the JS). --}}
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $priority->priority }}</td>
                                    <td class="ic-col-wrap">{{ $priority->description ?: '—' }}</td>
                                    <td data-order="{{ (int) $priority->status }}">
                                        <span class="status-pill badge rounded-1 {{ $isActive ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                                            {{ $isActive ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        {{-- Edit · status switch · Delete — the canonical stack (§3b).
                                             Status is still editable from the Edit modal too. --}}
                                        <div class="ic-act-group" role="group" aria-label="Row actions">
                                            <button type="button" class="ic-act ic-act--edit ip-edit-btn" aria-label="Edit priority"
                                                    data-id="{{ $priority->pk }}"
                                                    data-name="{{ $priority->priority }}"
                                                    data-description="{{ $priority->description }}"
                                                    data-status="{{ (int) $priority->status }}">
                                                <span class="ic-act__icon"><i class="bi bi-pencil-square" aria-hidden="true"></i></span>
                                                <span class="ic-act__label">Edit</span>
                                            </button>

                                            {{-- No .form-check/.form-switch wrapper: custom.css:107 pulls the
                                                 input -2.375rem left inside one, which breaks the
                                                 switch-above-caption layout. custom.js binds .status-toggle
                                                 globally, so there is no toggle JS to write here.
                                                 The caption names the ACTION, not the state (§3b). --}}
                                            <label class="ic-act ic-act--toggle">
                                                <span class="ic-act__icon">
                                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                           data-table="issue_priority_master" data-column="status"
                                                           data-id="{{ $priority->pk }}" {{ $isActive ? 'checked' : '' }}>
                                                </span>
                                                <span class="ic-act__label">{{ $isActive ? 'Activate' : 'Deactivate' }}</span>
                                            </label>

                                            @if($inUse)
                                                <span class="ic-act ic-act--del is-disabled" aria-disabled="true"
                                                      title="In use by {{ $priority->issue_logs_count }} issue(s) — cannot be deleted">
                                                    <span class="ic-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                    <span class="ic-act__label">Delete</span>
                                                </span>
                                            @else
                                                <form action="{{ route('admin.issue-priorities.destroy', $priority->pk) }}"
                                                      method="POST" class="ic-delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="ic-act ic-act--del" aria-label="Delete priority"
                                                            data-name="{{ $priority->priority }}">
                                                        <span class="ic-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                        <span class="ic-act__label">Delete</span>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Footer variant A — DataTables paginates; the global UI fills this in. --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="issuePrioritiesTable"></div>
            </div>

        </div>
    </div>
</div>

<!-- Add Priority Modal -->
<div class="modal fade" id="addPriorityModal" tabindex="-1" aria-labelledby="addPriorityModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ic-modal border-0 shadow">
            <form action="{{ route('admin.issue-priorities.store') }}" method="POST" id="addPriorityForm">
                @csrf
                <div class="modal-header ic-modal-header">
                    <h5 class="modal-title" id="addPriorityModalLabel">Add Priority</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body ic-modal-body">
                    <div class="ic-field-card">
                        <div class="mb-3">
                            <label for="ip_priority" class="ic-form-label">Priority Name<span class="ic-req">*</span></label>
                            <input type="text" class="form-control ic-control" id="ip_priority" name="priority"
                                   placeholder="e.g. High" maxlength="100" required>
                        </div>
                        <div class="mb-0">
                            <label for="ip_description" class="ic-form-label">Description</label>
                            <textarea class="form-control ic-control" id="ip_description" name="description" rows="3"
                                      placeholder="e.g. Lorem Ipsum dolor sit amet"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer ic-modal-footer">
                    <button type="button" class="btn ic-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ic-btn-submit">Add Priority</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Priority Modal -->
<div class="modal fade" id="editPriorityModal" tabindex="-1" aria-labelledby="editPriorityModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ic-modal border-0 shadow">
            <form id="editPriorityForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header ic-modal-header">
                    <h5 class="modal-title" id="editPriorityModalLabel">Edit Priority</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                {{-- Same card / label / control language as Add, plus Status —
                     this grid has no inline switch, so Edit is the only way to set it. --}}
                <div class="modal-body ic-modal-body">
                    <div class="ic-field-card">
                        <div class="mb-3">
                            <label for="edit_ip_priority" class="ic-form-label">Priority Name<span class="ic-req">*</span></label>
                            <input type="text" class="form-control ic-control" id="edit_ip_priority" name="priority"
                                   placeholder="e.g. High" maxlength="100" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_ip_description" class="ic-form-label">Description</label>
                            <textarea class="form-control ic-control" id="edit_ip_description" name="description" rows="3"
                                      placeholder="e.g. Lorem Ipsum dolor sit amet"></textarea>
                        </div>
                        <div class="mb-0">
                            <label for="edit_ip_status" class="ic-form-label">Status<span class="ic-req">*</span></label>
                            <select class="form-select ic-control" id="edit_ip_status" name="status" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer ic-modal-footer">
                    <button type="button" class="btn ic-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ic-btn-submit">Update Priority</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="ipColumnVisibilityModal" tabindex="-1" aria-labelledby="ipColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="ipColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="issuePriorityColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    'use strict';

    /* ── DataTable ───────────────────────────────────────────────────────────
       Search, sort, paging and the footer are DataTables' now;
       datatable-global-ui.js supplies the defaults and moves the filter/pager
       into the toolbar and footer slots. ── */
    var $table = $('#issuePrioritiesTable');

    var dt = $table.DataTable({
        order: [[1, 'asc']],
        columnDefs: [
            { targets: 0, orderable: false, searchable: false, className: 'text-center' },
            { targets: -1, orderable: false, searchable: false }
        ],
        language: {
            emptyTable: '<div class="ic-empty">' +
                '<i class="bi bi-flag d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Priorities Found</h6>' +
                '<p class="mb-0 small">Get started by adding your first priority.</p>' +
                '</div>',
            zeroRecords: '<div class="ic-empty">' +
                '<i class="bi bi-search d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Priorities Found</h6>' +
                '<p class="mb-0 small">No priority matches your search.</p>' +
                '</div>'
        }
    });

    // S. No. follows what is on screen, not the original row order.
    function renumberSerial() {
        var start = dt.page.info().start;
        dt.column(0, { search: 'applied', order: 'applied', page: 'current' })
          .nodes()
          .each(function (cell, i) { cell.innerHTML = start + i + 1; });
    }
    dt.on('draw.dt', renumberSerial);
    renumberSerial();

    /* ── Column visibility (DataTables column API) ────────────────────────
       Stored by LABEL, not index — an index points at a different column the
       moment one is added, silently hiding the wrong one. ── */
    var COL_KEY = 'issuePriorityGrid:hiddenColumns:v2';

    /* Header index -> export key (IssuePriorityController::exportColumnDefs()).
       Positional: '' marks a column that is not in the export (Action).
       ⚠️ Adding a table column means adding an entry here too. */
    var IP_EXPORT_COLUMN_KEYS = ['sno', 'priority', 'description', 'status', ''];
    var IP_EXPORT_COL_COUNT = IP_EXPORT_COLUMN_KEYS.filter(Boolean).length;

    /* Keep every export link carrying exactly the columns still on screen, plus
       the search term currently applied to it. */
    function ipUpdateExportCols() {
        var keys = [];
        dt.columns().every(function () {
            var key = IP_EXPORT_COLUMN_KEYS[this.index()];
            if (key && this.visible()) { keys.push(key); }
        });

        // This grid searches client-side, so the term lives only in DataTables.
        // Without carrying it the export returns every row and its header cannot
        // name the filter that was applied.
        var term = dt.search() || '';

        ['ipDownloadLink', 'ipExcelLink', 'ipPdfLink', 'ipPrintLink'].forEach(function (id) {
            var link = document.getElementById(id);
            if (!link) { return; }
            var base = link.href.split('?')[0];
            var params = new URLSearchParams(link.href.split('?')[1] || '');
            params.delete('q');
            if (term !== '') { params.set('q', term); }
            params.delete('cols');
            // Omit ?cols= while nothing is hidden — the server reads that as "all".
            if (keys.length !== IP_EXPORT_COL_COUNT) { params.set('cols', keys.join(',')); }
            var qs = params.toString();
            link.href = base + (qs ? '?' + qs : '');
        });
    }

    // Search-as-you-type has to re-stamp the links, not just redraw the grid.
    dt.on('search.dt', ipUpdateExportCols);

    function getHiddenCols() {
        try {
            var parsed = JSON.parse(localStorage.getItem(COL_KEY) || '[]');
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) { return []; }
    }

    function persistHiddenCols(cols) {
        try { localStorage.setItem(COL_KEY, JSON.stringify(cols)); } catch (e) { /* noop */ }
    }

    function buildColumnToggles() {
        var $grid = $('#issuePriorityColumnToggleGrid');
        var hidden = getHiddenCols();

        dt.columns().every(function () {
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (title) { this.visible(hidden.indexOf(title) === -1, false); }
        });
        dt.columns.adjust();

        if (!$grid.length) { return; }
        $grid.empty();

        dt.columns().every(function () {
            var index = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }

            var inputId = 'ipcolvis_' + index;
            var $checkbox = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(title) === -1);

            $checkbox.on('change', function () {
                var cols = getHiddenCols();
                var pos = cols.indexOf(title);
                if (this.checked) {
                    if (pos !== -1) { cols.splice(pos, 1); }
                } else if (pos === -1) {
                    cols.push(title);
                }
                persistHiddenCols(cols);
                dt.column(index).visible(this.checked, false);
                dt.columns.adjust();
                renumberSerial();
                ipUpdateExportCols();
            });

            $('<div class="col-12 col-sm-6 col-md-4"></div>').append(
                $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId)
                    .append($checkbox)
                    .append($('<span></span>').text(title))
            ).appendTo($grid);
        });
    }

    buildColumnToggles();
    // Stamp the restored column state onto the export links on first paint too.
    ipUpdateExportCols();


    /* ── Delete: confirm before submitting ───────────────────────────────── */
    $(document).on('submit', '.ic-delete-form', function (e) {
        var form = this;
        if ($(form).data('confirmed')) { return; }
        e.preventDefault();

        var name = $(form).find('.ic-act--del').data('name') || 'this priority';

        if (typeof Swal === 'undefined' || typeof Swal.fire !== 'function') {
            if (window.confirm('Delete "' + name + '"? This cannot be undone.')) {
                $(form).data('confirmed', true);
                form.submit();
            }
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: 'Delete "' + name + '"? This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d92d20',
            reverseButtons: true
        }).then(function (result) {
            if (result.isConfirmed) {
                $(form).data('confirmed', true);
                form.submit();
            }
        });
    });

    /* ── Edit modal ──────────────────────────────────────────────────────── */
    $(document).on('click', '.ip-edit-btn', function () {
        var $btn = $(this);
        $('#edit_ip_priority').val($btn.data('name'));
        $('#edit_ip_description').val($btn.data('description') || '');
        $('#edit_ip_status').val(String($btn.data('status')) === '1' ? '1' : '0');
        $('#editPriorityForm').attr('action', "{{ url('admin/issue-priorities') }}/" + $btn.data('id'));

        bootstrap.Modal.getOrCreateInstance(document.getElementById('editPriorityModal')).show();
    });

    /* ── Add modal: reset on close so a stale entry can't leak back in ───── */
    document.getElementById('addPriorityModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('addPriorityForm').reset();
    });
});
</script>
@endpush
