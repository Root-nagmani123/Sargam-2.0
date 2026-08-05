@extends('admin.layouts.master')

@section('title', 'Employee Group Master')

@push('styles')
<style>
    /* Canonical country/index look (new-design-index-page.md §3b) — scoped to .employee-group-page. */
    .employee-group-page .status-pill { padding: 0.4em 0.85em; font-weight: 600; }
    .employee-group-page .status-pill.bg-success-subtle { color: #146c43; }
    .employee-group-page .status-pill.bg-danger-subtle  { color: #b02a37; }

    .employee-group-page .empgroup-act {
        display: inline-flex; flex-direction: column; align-items: center; gap: 2px;
        font-size: 0.72rem; font-weight: 500; line-height: 1;
        text-decoration: none; background: transparent; border: 0; padding: 0;
    }
    .employee-group-page .empgroup-act i { font-size: 1.1rem; }
    .employee-group-page .empgroup-act--edit { color: #2563eb; }
</style>
@endpush

@section('setup_content')
<div class="container-fluid employee-group-page">
    <x-breadcrum title="Employee Group Master" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="empgroupAddBtn" data-bs-toggle="modal" data-bs-target="#empgroupFormModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Employee Group</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Branded exports — Download (CSV·PDF) dropdown + Print link (new-design-index-page.md §4b) --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
        <div class="dropdown">
            <button type="button" class="btn programme-dt-btn-columns border-0 text-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="{{ route('master.employee.group.export', 'csv') }}"><i class="bi bi-filetype-csv me-2" aria-hidden="true"></i>CSV</a></li>
                <li><a class="dropdown-item" href="{{ route('master.employee.group.export', 'pdf') }}"><i class="bi bi-filetype-pdf me-2" aria-hidden="true"></i>PDF</a></li>
            </ul>
        </div>
        <a href="{{ route('master.employee.group.export', 'print') }}" target="_blank" rel="noopener" class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </a>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="empgroupBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#empgroupColumnVisibilityModal"
                        title="Show / hide columns" style="border: 1px solid #d0d5dd; background: #fff; color: #344054;">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="empgroupDtSearch" class="programme-dt-search" data-dt-search-for="employeegroupmaster-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="empgroupDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="employeegroupmaster-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Add / Edit Employee Group Modal -->
<div class="modal fade" id="empgroupFormModal" tabindex="-1" aria-labelledby="empgroupFormModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form id="empgroupForm" action="{{ route('master.employee.group.store') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="pk" id="empgroupPk" value="">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="empgroupFormModalLabel">Add Employee Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="empgroupFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <div class="mb-3">
                        <label for="empgroupName" class="form-label fw-semibold">Employee Group Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="empgroupName" name="group_name"
                               placeholder="eg. Faculty" maxlength="255" required>
                        <div class="invalid-feedback" data-field="group_name"></div>
                    </div>

                    <div class="mb-0">
                        <label for="empgroupStatus" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="empgroupStatus" name="active_inactive" required>
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <div class="invalid-feedback" data-field="active_inactive"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-1 px-4" id="empgroupSubmitBtn">Add Employee Group</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="empgroupColumnVisibilityModal" tabindex="-1" aria-labelledby="empgroupColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="empgroupColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="empgroupColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
    $(document).ready(function () {
        var TABLE_ID = '#employeegroupmaster-table';
        var table;

        /* ---- Relocate search + build footer (pagination + count) ---- */
        function enhanceEmpgroupDtControls() {
            var $wrapper = $(TABLE_ID + '_wrapper');
            if (!$wrapper.length) { return; }

            var $searchSlot = $('#empgroupDtSearch');
            var $footer = $('#empgroupDtFooter');

            if (!$searchSlot.find('.dataTables_filter').length) {
                var $filter = $wrapper.find('.dataTables_filter').first();
                if ($filter.length) {
                    $filter.find('input').addClass('form-control shadow-none').attr('placeholder', 'Search').attr('aria-label', 'Search employee groups');
                    $filter.find('label').contents().filter(function () { return this.nodeType === 3; }).remove();
                    $searchSlot.append($filter);
                }
            }

            if ($footer.data('dtReady')) { updateEmpgroupDtCount(); return; }

            var $paginate = $wrapper.find('.dataTables_paginate').first();
            var $length = $wrapper.find('.dataTables_length').first();
            var $info = $wrapper.find('.dataTables_info').first();
            if (!$footer.length || (!$paginate.length && !$length.length)) { return; }

            var $pagCol = $('<div class="programme-dt-pagination"></div>');
            var $countCol = $('<div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto"></div>');
            if ($paginate.length) { $paginate.find('.pagination').addClass('mb-0'); $pagCol.append($paginate); }
            if ($length.length) {
                var $select = $length.find('select').addClass('form-select form-select-sm').detach();
                $length.find('label').empty().append(document.createTextNode('Showing ')).append($select).append(document.createTextNode(' '));
                $countCol.append($length);
            }
            if ($info.length) { $info.addClass('mb-0'); $countCol.append($info); }
            $footer.append($pagCol).append($countCol);
            $footer.data('dtReady', true);
            updateEmpgroupDtCount();
        }

        function updateEmpgroupDtCount() {
            if (!table) { return; }
            var info = table.page.info();
            var $info = $('#empgroupDtFooter .dataTables_info');
            if ($info.length && info && info.recordsDisplay !== undefined) {
                $info.text('of ' + info.recordsDisplay.toLocaleString() + ' items');
            }
        }

        /* ---- Column show / hide ---- */
        var empgroupColStorageKey = 'empgroupGrid:hiddenColumns:v1';
        function empgroupGetHiddenCols() { try { var a = JSON.parse(localStorage.getItem(empgroupColStorageKey) || '[]'); return Array.isArray(a) ? a : []; } catch (e) { return []; } }
        function empgroupPersistHiddenCols(a) { try { localStorage.setItem(empgroupColStorageKey, JSON.stringify(a)); } catch (e) {} }
        function setupEmpgroupColumns(dt) {
            if (!dt) { return; }
            var hidden = empgroupGetHiddenCols();
            dt.columns().every(function () { this.visible(hidden.indexOf(this.index()) === -1, false); });
            dt.columns.adjust();
            var $grid = $('#empgroupColumnToggleGrid'); if (!$grid.length) { return; }
            $grid.empty();
            dt.columns().every(function () {
                var idx = this.index(), title = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!title) { return; }
                var id = 'empgroupcolvis_' + idx;
                var $cb = $('<input type="checkbox" class="form-check-input m-0">').attr('id', id).prop('checked', hidden.indexOf(idx) === -1);
                $cb.on('change', function () {
                    var h = empgroupGetHiddenCols(), pos = h.indexOf(idx);
                    if (this.checked) { if (pos !== -1) h.splice(pos, 1); } else if (pos === -1) { h.push(idx); }
                    empgroupPersistHiddenCols(h); dt.column(idx).visible(this.checked, false); dt.columns.adjust();
                });
                $('<div class="col-12 col-sm-6 col-md-4"></div>').append(
                    $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                        .attr('for', id).append($cb).append($('<span></span>').text(title))
                ).appendTo($grid);
            });
        }

        /* ---- Wait for Yajra DataTable init. POLL — ajax can take >150ms; a one-shot bail would
               leave the default DataTables chrome un-enhanced. ---- */
        (function waitForEmpgroupDt(tries) {
            tries = tries || 0;
            if (!$.fn.DataTable.isDataTable(TABLE_ID)) {
                if (tries < 80) { setTimeout(function () { waitForEmpgroupDt(tries + 1); }, 150); }
                return;
            }
            table = $(TABLE_ID).DataTable();
            enhanceEmpgroupDtControls();
            updateEmpgroupDtCount();
            setupEmpgroupColumns(table);
            var $wrapper = $(TABLE_ID + '_wrapper');
            $(TABLE_ID).on('draw.dt', function () {
                if ($wrapper.find('.dataTables_paginate').length && !$('#empgroupDtFooter .dataTables_paginate').length) {
                    $('#empgroupDtFooter').empty().data('dtReady', false);
                    enhanceEmpgroupDtControls();
                }
                updateEmpgroupDtCount();
            });
            setTimeout(function () { enhanceEmpgroupDtControls(); updateEmpgroupDtCount(); }, 300);
        })();

        /* ---- Add / Edit modal ---- */
        var $form = $('#empgroupForm');
        var $alert = $('#empgroupFormAlert');
        function empgroupClearErrors() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            $alert.addClass('d-none').removeClass('alert-danger alert-success').empty();
        }
        function empgroupResetForm() { $form[0].reset(); $('#empgroupPk').val(''); empgroupClearErrors(); }

        $('#empgroupAddBtn').on('click', function () {
            empgroupResetForm();
            $('#empgroupFormModalLabel').text('Add Employee Group');
            $('#empgroupSubmitBtn').text('Add Employee Group');
            $('#empgroupStatus').val('1');
        });

        $(document).on('click', '#employeegroupmaster-table .empgroup-edit-btn', function () {
            var $btn = $(this);
            empgroupResetForm();
            $('#empgroupFormModalLabel').text('Edit Employee Group');
            $('#empgroupSubmitBtn').text('Update');
            $('#empgroupPk').val($btn.data('id'));
            $('#empgroupName').val($btn.data('name'));
            $('#empgroupStatus').val(String($btn.data('status')) === '1' ? '1' : '0');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('empgroupFormModal')).show();
        });

        $form.on('submit', function (e) {
            e.preventDefault();
            empgroupClearErrors();
            var $submit = $('#empgroupSubmitBtn');
            var originalText = $submit.text();
            $submit.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');
            $.ajax({
                url: $form.attr('action'), type: 'POST', data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function (response) {
                    $alert.removeClass('d-none alert-danger').addClass('alert-success').html('<i class="bi bi-check-circle me-1"></i>' + (response.message || 'Saved successfully.'));
                    if ($.fn.DataTable.isDataTable(TABLE_ID)) { $(TABLE_ID).DataTable().ajax.reload(null, false); }
                    setTimeout(function () { bootstrap.Modal.getInstance(document.getElementById('empgroupFormModal'))?.hide(); empgroupResetForm(); }, 1000);
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        Object.keys(xhr.responseJSON.errors).forEach(function (field) {
                            $form.find('[name="' + field + '"]').addClass('is-invalid');
                            $form.find('.invalid-feedback[data-field="' + field + '"]').text(xhr.responseJSON.errors[field][0]);
                        });
                    } else {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'An error occurred while saving. Please try again.';
                        $alert.removeClass('d-none alert-success').addClass('alert-danger').html('<i class="bi bi-exclamation-circle me-1"></i>' + msg);
                    }
                },
                complete: function () { $submit.prop('disabled', false).text(originalText); }
            });
        });

        document.getElementById('empgroupFormModal').addEventListener('hidden.bs.modal', function () {
            empgroupResetForm();
            $('#empgroupFormModalLabel').text('Add Employee Group');
            $('#empgroupSubmitBtn').text('Add Employee Group');
        });
    });
</script>
@endpush
