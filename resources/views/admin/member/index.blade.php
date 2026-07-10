@extends('admin.layouts.master')

@section('title', 'Employee Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/member-master-admin.css') }}?v={{ @filemtime(public_path('css/member-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid em-member-page">
    <x-breadcrum title="Employee Master">
        <a href="{{ route('member.create') }}"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold text-nowrap shadow-sm">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Employee Master</span>
        </a>
    </x-breadcrum>

    <x-session_message />

    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 mb-3 em-toolbar-actions">
        <a href="{{ route('member.excel.export') }}"
            class="em-btn-outline border-0"
            aria-label="Download employee data">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </a>
    </div>

    <div class="card em-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnEmColumns"
                        data-bs-toggle="modal" data-bs-target="#emColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="emDtSearch" class="programme-dt-search" data-dt-search-for="member-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel em-dt-panel">
                <div class="table-responsive em-dt-scroll">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="emDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="member-table"></div>
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
                    <div class="row g-3" id="emColumnToggleGrid"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
(function () {
    var tableSelector = '#member-table';

    function iconOnlyLink($link, iconClass, extraClass, label) {
        $link.addClass('em-action-btn ' + (extraClass || ''));
        $link.attr('aria-label', label || $link.text().trim());
        $link.empty().append('<i class="bi ' + iconClass + '" aria-hidden="true"></i>');
    }

    function iconOnlyBtn($btn, iconClass, extraClass, label) {
        $btn.removeClass('btn btn-sm btn-primary btn-success btn-danger btn-outline-primary btn-outline-danger btn-outline-secondary');
        $btn.addClass('em-action-btn ' + (extraClass || ''));
        $btn.attr('aria-label', label || $btn.text().trim());
        $btn.empty().append('<i class="bi ' + iconClass + '" aria-hidden="true"></i>');
    }

    function decorateMemberRows() {
        if (typeof jQuery === 'undefined') {
            return;
        }
        var $ = jQuery;

        $(tableSelector + ' tbody tr').each(function () {
            var $row = $(this);
            if ($row.hasClass('em-row-decorated')) {
                return;
            }

            var $cells = $row.find('td');
            if (!$cells.length) {
                return;
            }

            var $actionCell = $cells.last();
            var $editLink = $actionCell.find('a.btn-primary, a[href*="edit"]').first().detach();
            var $viewLink = $actionCell.find('a.btn-success, a[href*="show"]').first().detach();
            var $deleteForm = $actionCell.find('form').first().detach();
            var $deleteBtn = $deleteForm.length ? $deleteForm.find('button[type="submit"]').first() : $();

            var $group = $('<div>', {
                class: 'em-member-actions',
                role: 'group',
                'aria-label': 'Employee actions'
            });

            if ($editLink.length) {
                iconOnlyLink($editLink, 'bi-pencil', 'em-action-edit', 'Edit employee');
                $group.append($editLink);
            }

            if ($viewLink.length) {
                iconOnlyLink($viewLink, 'bi-eye', 'em-action-view', 'View employee');
                $group.append($viewLink);
            }

            if ($deleteForm.length && $deleteBtn.length) {
                iconOnlyBtn($deleteBtn, 'bi-trash', 'em-action-delete', 'Delete employee');
                $deleteForm.addClass('d-inline m-0');
                $group.append($deleteForm);
            }

            $actionCell.empty().append($group);
            $row.addClass('em-row-decorated');
        });
    }

    function bindMemberTableUi() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.dataTable) {
            return;
        }
        var $ = jQuery;

        $(tableSelector).on('draw.dt init.dt', function () {
            $(tableSelector + ' tbody tr').removeClass('em-row-decorated');
            decorateMemberRows();
        });

        if ($.fn.DataTable.isDataTable(tableSelector)) {
            decorateMemberRows();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindMemberTableUi);
    } else {
        bindMemberTableUi();
    }
})();
</script>

<script>
/* ---- Column Visibility (drives the live Yajra DataTable via its API) ----
   A Bootstrap modal of checkboxes built from the live table headers, persisted
   to localStorage. We never touch the table's dom/init. */
$(function () {
    var emColStorageKey = 'employeeMaster:hiddenColumns:v1';

    function emGetHiddenCols() {
        try {
            var raw = localStorage.getItem(emColStorageKey);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) { return []; }
    }
    function emPersistHiddenCols(arr) {
        try { localStorage.setItem(emColStorageKey, JSON.stringify(arr)); } catch (e) {}
    }

    function setupEmColumns(dt) {
        if (!dt) { return; }
        var hidden = emGetHiddenCols();

        // Apply saved visibility (persists across ajax reloads / redraws).
        dt.columns().every(function () {
            var idx = this.index();
            this.visible(hidden.indexOf(idx) === -1, false);
        });
        dt.columns.adjust();

        var $grid = $('#emColumnToggleGrid');
        if (!$grid.length) { return; }
        $grid.empty();

        dt.columns().every(function () {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }

            var inputId = 'emcolvis_' + idx;
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(idx) === -1);

            $cb.on('change', function () {
                var h = emGetHiddenCols();
                var pos = h.indexOf(idx);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else {
                    if (pos === -1) h.push(idx);
                }
                emPersistHiddenCols(h);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
            });

            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label);
            $grid.append($cell);
        });
    }

    // Wait for Yajra to finish initializing the table, then wire columns.
    (function whenReady(tries) {
        tries = tries || 0;
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#member-table')) {
            setupEmColumns($('#member-table').DataTable());
        } else if (tries < 100) {
            setTimeout(function () { whenReady(tries + 1); }, 100);
        }
    })();
});
</script>
@endpush
