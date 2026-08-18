@extends('admin.layouts.master')

@section('title', 'Escalation Matrix')

@push('styles')
{{-- Shared Centcom chrome — same file the other Centcom screens use. --}}
<link rel="stylesheet"
      href="{{ asset('css/issue-management-admin.css') }}?v={{ @filemtime(public_path('css/issue-management-admin.css')) ?: time() }}">
{{-- Select2 (JS is global in footer.blade.php:66; the CSS is per-page by convention). --}}
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
@endpush

@section('setup_content')
@php
    // Search, sort and paging are the server's now; ?q= and ?cols= are appended
    // to the export links by emUpdateExportCols() so a download matches the grid.
    $exportQuery = [];
@endphp
<div class="container-fluid ic-page">
    <x-breadcrum title="Escalation Matrix" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                data-bs-toggle="modal" data-bs-target="#addMatrixModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Escalation Matrix</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Secondary actions (Download / Print) — above the card, per §1 --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 ic-secondary-actions">
        {{-- More than one download format → dropdown, per §1 of the doc. --}}
        <div class="dropdown">
            <button type="button" id="emDownloadToggle"
                    class="btn programme-dt-btn-columns border-0 text-primary dropdown-toggle"
                    data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="emDownloadToggle">
                <li><a class="dropdown-item" id="emDownloadLink"
                       href="{{ route('admin.issue-escalation-matrix.export', array_merge(['format' => 'csv'], $exportQuery)) }}">
                        <i class="bi bi-filetype-csv me-1" aria-hidden="true"></i> CSV</a></li>
                <li><a class="dropdown-item" id="emExcelLink"
                       href="{{ route('admin.issue-escalation-matrix.export', array_merge(['format' => 'excel'], $exportQuery)) }}">
                        <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel (.xlsx)</a></li>
                <li><a class="dropdown-item" id="emPdfLink"
                       href="{{ route('admin.issue-escalation-matrix.export', array_merge(['format' => 'pdf'], $exportQuery)) }}">
                        <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> PDF</a></li>
            </ul>
        </div>
        <a href="{{ route('admin.issue-escalation-matrix.export', array_merge(['format' => 'print'], $exportQuery)) }}"
           id="emPrintLink"
           target="_blank" rel="noopener" class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </a>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar: columns + search on the right (§2) --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 ic-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="emBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#emColumnVisibilityModal"
                            title="Show / hide columns"
                            style="border: 1px solid #d0d5dd; background: #fff; color: #344054;">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    {{-- Always-visible search box: datatable-global-ui.js moves DataTables'
                         own filter in here, so it filters as you type instead of
                         reloading the page on Enter. --}}
                    <div id="emDtSearch" class="programme-dt-search" data-dt-search-for="escalationMatrixTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {{-- Server-side: search, sort and the page slice all run on the
                         server, so only the page on screen reaches the browser. --}}
                    <table id="escalationMatrixTable"
                           class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Complaint Category</th>
                                @foreach([1, 2, 3] as $n)
                                    <th scope="col">Level {{ $n }} (Escalation Days)</th>
                                @endforeach
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                {{-- Footer variant A — DataTables paginates; the global UI fills this in. --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="escalationMatrixTable"></div>
            </div>

        </div>
    </div>
</div>

<!-- View Mapping Modal -->
<div class="modal fade" id="viewMatrixModal" tabindex="-1" aria-labelledby="viewMatrixModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ic-modal border-0 shadow">
            <div class="modal-header ic-modal-header">
                <h5 class="modal-title" id="viewMatrixModalLabel">Escalation Mapping</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body ic-modal-body">
                <div class="ic-field-card">
                    <div class="ic-facts" style="grid-template-columns:1fr;">
                        <div>
                            <span class="ic-fact__label">Complaint Category</span>
                            <span class="ic-fact__value" id="vm_category">—</span>
                        </div>
                        <div>
                            <span class="ic-fact__label">Level 1 (Escalation Days)</span>
                            <span class="ic-fact__value" id="vm_level1">—</span>
                        </div>
                        <div>
                            <span class="ic-fact__label">Level 2 (Escalation Days)</span>
                            <span class="ic-fact__value" id="vm_level2">—</span>
                        </div>
                        <div>
                            <span class="ic-fact__label">Level 3 (Escalation Days)</span>
                            <span class="ic-fact__value" id="vm_level3">—</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer ic-modal-footer">
                <button type="button" class="btn ic-btn-cancel" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn ic-btn-submit" id="vm_edit_btn">Edit Mapping</button>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="emColumnVisibilityModal" tabindex="-1" aria-labelledby="emColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="emColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="escalationColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Mapping Modal -->
<div class="modal fade" id="addMatrixModal" tabindex="-1" aria-labelledby="addMatrixModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ic-modal border-0 shadow">
            <form action="{{ route('admin.issue-escalation-matrix.store') }}" method="POST">
                @csrf
                <div class="modal-header ic-modal-header">
                    <h5 class="modal-title" id="addMatrixModalLabel">Add Escalation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body ic-modal-body">
                    @include('admin.issue_management.escalation_matrix._form', ['employees' => $employees])
                </div>
                <div class="modal-footer ic-modal-footer">
                    <button type="button" class="btn ic-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ic-btn-submit">Add Escalation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Mapping Modal -->
<div class="modal fade" id="editMatrixModal" tabindex="-1" aria-labelledby="editMatrixModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ic-modal border-0 shadow">
            <form id="editMatrixForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header ic-modal-header">
                    <h5 class="modal-title" id="editMatrixModalLabel">Edit Escalation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body ic-modal-body">
                    @include('admin.issue_management.escalation_matrix._form', ['employees' => $employees, 'isEdit' => true])
                </div>
                <div class="modal-footer ic-modal-footer">
                    <button type="button" class="btn ic-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ic-btn-submit">Update Escalation</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function() {
    // Single copy for the whole page — editMatrix() used to embed a second one.
    var escalationEmployees = @json($employees);
    window.escalationEmployees = escalationEmployees;

    function optionHtml(emp) {
        return '<option value="' + emp.employee_pk + '">' + (emp.employee_name || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</option>';
    }

    function rebuildLevel2Add(excludePk) {
        var sel = document.getElementById('level2_employee');
        if (!sel) return;
        var current = sel.value;
        sel.innerHTML = '<option value="">- Select -</option>';
        escalationEmployees.forEach(function(emp) {
            if (String(emp.employee_pk) !== String(excludePk)) {
                sel.insertAdjacentHTML('beforeend', optionHtml(emp));
            }
        });
        sel.value = '';
    }

    function rebuildLevel3Add(excludePk1, excludePk2) {
        var sel = document.getElementById('level3_employee');
        if (!sel) return;
        sel.innerHTML = '<option value="">- Select -</option>';
        escalationEmployees.forEach(function(emp) {
            var pk = String(emp.employee_pk);
            if (pk !== String(excludePk1) && pk !== String(excludePk2)) {
                sel.insertAdjacentHTML('beforeend', optionHtml(emp));
            }
        });
        sel.value = '';
    }

    function rebuildLevel2Edit(excludePk) {
        var sel = document.getElementById('edit_level2_employee');
        if (!sel) return;
        var current = sel.value;
        sel.innerHTML = '<option value="">- Select -</option>';
        escalationEmployees.forEach(function(emp) {
            if (String(emp.employee_pk) !== String(excludePk)) {
                sel.insertAdjacentHTML('beforeend', optionHtml(emp));
            }
        });
        if (current && String(excludePk) !== current) sel.value = current;
    }

    function rebuildLevel3Edit(excludePk1, excludePk2) {
        var sel = document.getElementById('edit_level3_employee');
        if (!sel) return;
        var current = sel.value;
        sel.innerHTML = '<option value="">- Select -</option>';
        escalationEmployees.forEach(function(emp) {
            var pk = String(emp.employee_pk);
            if (pk !== String(excludePk1) && pk !== String(excludePk2)) {
                sel.insertAdjacentHTML('beforeend', optionHtml(emp));
            }
        });
        if (current && current !== String(excludePk1) && current !== String(excludePk2)) sel.value = current;
    }

    document.addEventListener('DOMContentLoaded', function() {
        var level1Add = document.getElementById('level1_employee');
        var level2Add = document.getElementById('level2_employee');
        var level3Add = document.getElementById('level3_employee');
        if (level1Add) {
            level1Add.addEventListener('change', function() {
                var pk = this.value;
                rebuildLevel2Add(pk);
                rebuildLevel3Add(pk, null);
            });
        }
        if (level2Add) {
            level2Add.addEventListener('change', function() {
                var pk1 = level1Add && level1Add.value ? level1Add.value : null;
                var pk2 = this.value;
                rebuildLevel3Add(pk1, pk2);
            });
        }

        var level1Edit = document.getElementById('edit_level1_employee');
        var level2Edit = document.getElementById('edit_level2_employee');
        var level3Edit = document.getElementById('edit_level3_employee');
        if (level1Edit) {
            level1Edit.addEventListener('change', function() {
                var pk = this.value;
                rebuildLevel2Edit(pk);
                rebuildLevel3Edit(pk, level2Edit && level2Edit.value ? level2Edit.value : null);
            });
        }
        if (level2Edit) {
            level2Edit.addEventListener('change', function() {
                var pk1 = level1Edit && level1Edit.value ? level1Edit.value : null;
                var pk2 = this.value;
                rebuildLevel3Edit(pk1, pk2);
            });
        }
    });
})();

function editMatrix(categoryId, categoryName, emp1, days1, emp2, days2, emp3, days3) {
    document.getElementById('edit_category_pk').value = categoryId;
    document.getElementById('edit_category_name').value = categoryName;
    document.getElementById('edit_level1_employee').value = emp1 != null ? String(emp1) : '';
    document.getElementById('edit_level1_days').value = days1 || 0;
    document.getElementById('edit_level2_employee').value = emp2 != null ? String(emp2) : '';
    document.getElementById('edit_level2_days').value = days2 || 0;
    document.getElementById('edit_level3_employee').value = emp3 != null ? String(emp3) : '';
    document.getElementById('edit_level3_days').value = days3 || 0;

    document.getElementById('editMatrixForm').action = "{{ url('admin/issue-escalation-matrix') }}/" + categoryId;

    var escalationEmployees = window.escalationEmployees || [];
    function opt(emp) { return '<option value="' + emp.employee_pk + '">' + (emp.employee_name || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</option>'; }
    var L1 = document.getElementById('edit_level1_employee');
    var L2 = document.getElementById('edit_level2_employee');
    var L3 = document.getElementById('edit_level3_employee');
    var v1 = L1 && L1.value ? L1.value : '';
    var v2 = L2 && L2.value ? L2.value : '';
    L2.innerHTML = '<option value="">- Select -</option>';
    escalationEmployees.forEach(function(e) { if (String(e.employee_pk) !== v1) L2.insertAdjacentHTML('beforeend', opt(e)); });
    L2.value = v2;
    L3.innerHTML = '<option value="">- Select -</option>';
    escalationEmployees.forEach(function(e) {
        var p = String(e.employee_pk);
        if (p !== v1 && p !== v2) L3.insertAdjacentHTML('beforeend', opt(e));
    });
    L3.value = (emp3 != null && String(emp3) !== v1 && String(emp3) !== v2) ? String(emp3) : '';

    new bootstrap.Modal(document.getElementById('editMatrixModal')).show();
}

/* ── Page chrome: DataTable, column visibility, View modal ── */
$(function () {
    'use strict';

    /* ── DataTable (server-side) ─────────────────────────────────────────────
       Search, sort, paging and the footer are DataTables', and every one is
       answered by the server: a draw fetches only the page being shown.
       `sargamServerOrder` keeps ordering on the server too, so a header click
       re-sorts the WHOLE matrix rather than the visible page. ── */
    var $table = $('#escalationMatrixTable');

    var dt = $table.DataTable({
        serverSide: true,
        processing: true,
        sargamServerOrder: true,
        searching: true,
        // 400ms after the last keystroke — search as you type, one query per pause.
        searchDelay: 400,
        order: [[1, 'asc']],
        ajax: {
            url: '{{ route('admin.issue-escalation-matrix.data') }}'
        },
        /* `name` is the sort key the controller whitelists (SORTABLE_COLUMNS);
           an empty one means "not sortable". */
        columns: [
            { data: 'sno', name: '', orderable: false, searchable: false, className: 'text-center' },
            { data: 'category', name: 'category' },
            { data: 'level1', name: 'level1' },
            { data: 'level2', name: 'level2' },
            { data: 'level3', name: 'level3' },
            { data: 'action', name: '', orderable: false, searchable: false }
        ],
        language: {
            emptyTable: '<div class="ic-empty">' +
                '<i class="bi bi-diagram-3 d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Mappings Found</h6>' +
                '<p class="mb-0 small">Add a mapping to get started.</p>' +
                '</div>',
            zeroRecords: '<div class="ic-empty">' +
                '<i class="bi bi-search d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Mappings Found</h6>' +
                '<p class="mb-0 small">No category or employee matches your search.</p>' +
                '</div>'
        }
    });

    /* ── Column visibility (DataTables column API) ────────────────────────
       Stored by LABEL, not index — an index points at a different column the
       moment one is added, silently hiding the wrong one. ── */
    var COL_KEY = 'escalationGrid:hiddenColumns:v2';

    /* Header index -> export key (IssueEscalationMatrixController::exportColumnDefs()).
       Positional: '' marks a column that is not in the export (Action).
       ⚠️ Adding a table column means adding an entry here too. */
    var EM_EXPORT_COLUMN_KEYS = ['sno', 'category', 'level1', 'level2', 'level3', ''];
    var EM_EXPORT_COL_COUNT = EM_EXPORT_COLUMN_KEYS.filter(Boolean).length;

    /* Keep every export link carrying exactly the columns still on screen, plus
       the search term currently applied to it. */
    function emUpdateExportCols() {
        var keys = [];
        dt.columns().every(function () {
            var key = EM_EXPORT_COLUMN_KEYS[this.index()];
            if (key && this.visible()) { keys.push(key); }
        });

        // The term lives in DataTables, which sends it to the grid feed as
        // search[value]; the export reads ?q=. Without carrying it the download
        // returns every row and its header cannot name the filter applied.
        var term = dt.search() || '';

        ['emDownloadLink', 'emExcelLink', 'emPdfLink', 'emPrintLink'].forEach(function (id) {
            var link = document.getElementById(id);
            if (!link) { return; }
            var base = link.href.split('?')[0];
            var params = new URLSearchParams(link.href.split('?')[1] || '');
            params.delete('q');
            if (term !== '') { params.set('q', term); }
            params.delete('cols');
            // Omit ?cols= while nothing is hidden — the server reads that as "all".
            if (keys.length !== EM_EXPORT_COL_COUNT) { params.set('cols', keys.join(',')); }
            var qs = params.toString();
            link.href = base + (qs ? '?' + qs : '');
        });
    }

    // Search-as-you-type has to re-stamp the links, not just redraw the grid.
    dt.on('search.dt', emUpdateExportCols);

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
        var $grid = $('#escalationColumnToggleGrid');
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

            var inputId = 'emcolvis_' + index;
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
                emUpdateExportCols();
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
    emUpdateExportCols();

    /* View modal — read-only, with a hand-off to the existing edit modal. */
    var pending = null;

    function levelText(name, days) {
        if (!name) { return 'Not mapped'; }
        var d = parseInt(days, 10);
        return name + ' - ' + (isNaN(d) ? 0 : d) + ' ' + (d === 1 ? 'Day' : 'Days');
    }

    $(document).on('click', '.em-view-btn', function () {
        var d = $(this).data();
        pending = d;
        $('#vm_category').text(d.category || '—');
        $('#vm_level1').text(levelText(d.l1Name, d.l1Days));
        $('#vm_level2').text(levelText(d.l2Name, d.l2Days));
        $('#vm_level3').text(levelText(d.l3Name, d.l3Days));
        bootstrap.Modal.getOrCreateInstance(document.getElementById('viewMatrixModal')).show();
    });

    $('#vm_edit_btn').on('click', function () {
        if (!pending) { return; }
        bootstrap.Modal.getInstance(document.getElementById('viewMatrixModal'))?.hide();
        // Options must exist before editMatrix() assigns values, or the
        // assignments land on empty selects and are silently dropped.
        if (typeof window.icFillLevels === 'function') { window.icFillLevels('edit_'); }
        // Reuse the page's existing edit modal wiring verbatim.
        editMatrix(
            pending.categoryId,
            pending.category,
            pending.l1Emp === '' ? null : pending.l1Emp,
            pending.l1Days || 0,
            pending.l2Emp === '' ? null : pending.l2Emp,
            pending.l2Days || 0,
            pending.l3Emp === '' ? null : pending.l3Emp,
            pending.l3Days || 0
        );
    });
});

/* ── Searchable employee selects (Select2) ──
   ~1,800 employees: a plain <select> is unusable, so each modal's selects get a
   type-ahead. Options are injected from the shared `escalationEmployees` array
   on open rather than server-rendered six times over.

   Select2 must be told when we replace <option>s ourselves: the page's own
   level-exclusion handlers rewrite L2/L3 innerHTML, and the widget only
   re-renders its *selection* on `change.select2`. This block is registered
   after that IIFE, so its change listener runs after the rebuild. */
$(function () {
    'use strict';
    if (typeof $.fn.select2 === 'undefined') { return; }

    var MODALS = ['addMatrixModal', 'editMatrixModal'];

    function employees() { return window.escalationEmployees || []; }

    function optionsHtml(excluded) {
        var html = '<option value="">Select Employee</option>';
        employees().forEach(function (emp) {
            var pk = String(emp.employee_pk);
            if (excluded.indexOf(pk) !== -1) { return; }
            var name = String(emp.employee_name || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            html += '<option value="' + pk + '">' + name + '</option>';
        });
        return html;
    }

    /* Fill L1/L2/L3, honouring the "same person can't hold two levels" rule and
       keeping any value already set (edit prefill runs before this). */
    window.icFillLevels = fillLevels;

    function fillLevels(prefix) {
        var ids = [1, 2, 3].map(function (n) { return document.getElementById(prefix + 'level' + n + '_employee'); });
        var keep = ids.map(function (el) { return el && el.value ? String(el.value) : ''; });

        ids.forEach(function (el, i) {
            if (!el) { return; }
            var excluded = keep.filter(function (v, j) { return v !== '' && j !== i; });
            el.innerHTML = optionsHtml(excluded);
            if (keep[i]) { el.value = keep[i]; }
        });
    }

    function initSelect2(modalId) {
        var $modal = $('#' + modalId);
        $modal.find('select').each(function () {
            var $sel = $(this);
            if ($sel.data('select2')) { $sel.select2('destroy'); }
            $sel.select2({
                width: '100%',
                placeholder: $sel.find('option').first().text() || 'Select',
                allowClear: false,
                // Without this the search box inside a Bootstrap modal cannot be focused.
                dropdownParent: $modal,
                minimumResultsForSearch: 10
            });
        });
    }

    function refresh(modalId) {
        $('#' + modalId).find('select').each(function () {
            if ($(this).data('select2')) { $(this).trigger('change.select2'); }
        });
    }

    MODALS.forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) { return; }
        var prefix = id === 'editMatrixModal' ? 'edit_' : '';

        el.addEventListener('shown.bs.modal', function () {
            fillLevels(prefix);
            initSelect2(id);
        });

        /* Select2 signals a pick with jQuery's .trigger('change'), which does NOT
           run listeners registered via addEventListener — and the level-exclusion
           logic above uses exactly those. Re-dispatch a native event (guarded
           against the re-entry that dispatch itself causes), then re-render. */
        $('#' + id).on('change', 'select', function () {
            var el = this;
            if (el.__icBridging) { return; }
            el.__icBridging = true;
            el.dispatchEvent(new Event('change', { bubbles: true }));
            el.__icBridging = false;
            setTimeout(function () { refresh(id); }, 0);
        });

        // Drop the widgets on close so the next open rebuilds from current data.
        el.addEventListener('hidden.bs.modal', function () {
            $('#' + id).find('select').each(function () {
                if ($(this).data('select2')) { $(this).select2('destroy'); }
            });
        });
    });
});
</script>
@endsection
