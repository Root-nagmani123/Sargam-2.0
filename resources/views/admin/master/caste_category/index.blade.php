@extends('admin.layouts.master')

@section('title', 'Caste Category Master')

@push('styles')
<style>
    /* Canonical country/index look (new-design-index-page.md §3b) — scoped to .caste-category-page. */
    .caste-category-page .status-pill { padding: 0.4em 0.85em; font-weight: 600; }
    .caste-category-page .status-pill.bg-success-subtle { color: #146c43; }
    .caste-category-page .status-pill.bg-danger-subtle  { color: #b02a37; }

    .caste-category-page .caste-act {
        display: inline-flex; flex-direction: column; align-items: center; gap: 2px;
        font-size: 0.72rem; font-weight: 500; line-height: 1;
        text-decoration: none; background: transparent; border: 0; padding: 0;
    }
    .caste-category-page .caste-act i { font-size: 1.1rem; }
    .caste-category-page .caste-act--edit { color: #2563eb; }
</style>
@endpush

@section('setup_content')
<div class="container-fluid caste-category-page">
    <x-breadcrum title="Caste Category Master" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="casteAddBtn" data-bs-toggle="modal" data-bs-target="#casteFormModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Caste Category</span>
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
                <li><a class="dropdown-item" href="{{ route('master.caste.category.export', 'csv') }}"><i class="bi bi-filetype-csv me-2" aria-hidden="true"></i>CSV</a></li>
                <li><a class="dropdown-item" href="{{ route('master.caste.category.export', 'pdf') }}"><i class="bi bi-filetype-pdf me-2" aria-hidden="true"></i>PDF</a></li>
            </ul>
        </div>
        <a href="{{ route('master.caste.category.export', 'print') }}" target="_blank" rel="noopener" class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </a>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="casteBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#casteColumnVisibilityModal"
                        title="Show / hide columns" style="border: 1px solid #d0d5dd; background: #fff; color: #344054;">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="casteDtSearch" class="programme-dt-search" data-dt-search-for="castecategorymaster-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="casteDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="castecategorymaster-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Add / Edit Caste Category Modal -->
<div class="modal fade" id="casteFormModal" tabindex="-1" aria-labelledby="casteFormModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form id="casteForm" action="{{ route('master.caste.category.store') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="pk" id="castePk" value="">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="casteFormModalLabel">Add Caste Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="casteFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <div class="mb-3">
                        <label for="casteName" class="form-label fw-semibold">Category/Caste name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="casteName" name="Seat_name"
                               placeholder="eg. General" maxlength="255" required>
                        <div class="invalid-feedback" data-field="Seat_name"></div>
                    </div>

                    <div class="mb-3">
                        <label for="casteNameHi" class="form-label fw-semibold">Category/Caste name (Hindi) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="casteNameHi" name="Seat_name_hindi"
                               placeholder="eg. सामान्य" maxlength="255" required>
                        <div class="invalid-feedback" data-field="Seat_name_hindi"></div>
                    </div>

                    <div class="mb-0">
                        <label for="casteStatus" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="casteStatus" name="active_inactive" required>
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <div class="invalid-feedback" data-field="active_inactive"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-1 px-4" id="casteSubmitBtn">Add Caste Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="casteColumnVisibilityModal" tabindex="-1" aria-labelledby="casteColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="casteColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="casteColumnToggleGrid"></div>
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
        var TABLE_ID = '#castecategorymaster-table';
        var table;

        /* ---- Relocate search + build footer (pagination + count) ---- */
        function enhanceCasteDtControls() {
            var $wrapper = $(TABLE_ID + '_wrapper');
            if (!$wrapper.length) { return; }

            var $searchSlot = $('#casteDtSearch');
            var $footer = $('#casteDtFooter');

            if (!$searchSlot.find('.dataTables_filter').length) {
                var $filter = $wrapper.find('.dataTables_filter').first();
                if ($filter.length) {
                    $filter.find('input').addClass('form-control shadow-none').attr('placeholder', 'Search').attr('aria-label', 'Search caste categories');
                    $filter.find('label').contents().filter(function () { return this.nodeType === 3; }).remove();
                    $searchSlot.append($filter);
                }
            }

            if ($footer.data('dtReady')) { updateCasteDtCount(); return; }

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
            updateCasteDtCount();
        }

        function updateCasteDtCount() {
            if (!table) { return; }
            var info = table.page.info();
            var $info = $('#casteDtFooter .dataTables_info');
            if ($info.length && info && info.recordsDisplay !== undefined) {
                $info.text('of ' + info.recordsDisplay.toLocaleString() + ' items');
            }
        }

        /* ---- Column show / hide ---- */
        var casteColStorageKey = 'casteGrid:hiddenColumns:v1';
        function casteGetHiddenCols() { try { var a = JSON.parse(localStorage.getItem(casteColStorageKey) || '[]'); return Array.isArray(a) ? a : []; } catch (e) { return []; } }
        function castePersistHiddenCols(a) { try { localStorage.setItem(casteColStorageKey, JSON.stringify(a)); } catch (e) {} }
        function setupCasteColumns(dt) {
            if (!dt) { return; }
            var hidden = casteGetHiddenCols();
            dt.columns().every(function () { this.visible(hidden.indexOf(this.index()) === -1, false); });
            dt.columns.adjust();
            var $grid = $('#casteColumnToggleGrid'); if (!$grid.length) { return; }
            $grid.empty();
            dt.columns().every(function () {
                var idx = this.index(), title = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!title) { return; }
                var id = 'castecolvis_' + idx;
                var $cb = $('<input type="checkbox" class="form-check-input m-0">').attr('id', id).prop('checked', hidden.indexOf(idx) === -1);
                $cb.on('change', function () {
                    var h = casteGetHiddenCols(), pos = h.indexOf(idx);
                    if (this.checked) { if (pos !== -1) h.splice(pos, 1); } else if (pos === -1) { h.push(idx); }
                    castePersistHiddenCols(h); dt.column(idx).visible(this.checked, false); dt.columns.adjust();
                });
                $('<div class="col-12 col-sm-6 col-md-4"></div>').append(
                    $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                        .attr('for', id).append($cb).append($('<span></span>').text(title))
                ).appendTo($grid);
            });
        }

        /* ---- Wait for Yajra DataTable init. POLL — ajax can take >150ms; a one-shot bail would
               leave the default DataTables chrome un-enhanced. ---- */
        (function waitForCasteDt(tries) {
            tries = tries || 0;
            if (!$.fn.DataTable.isDataTable(TABLE_ID)) {
                if (tries < 80) { setTimeout(function () { waitForCasteDt(tries + 1); }, 150); }
                return;
            }
            table = $(TABLE_ID).DataTable();
            enhanceCasteDtControls();
            updateCasteDtCount();
            setupCasteColumns(table);
            var $wrapper = $(TABLE_ID + '_wrapper');
            $(TABLE_ID).on('draw.dt', function () {
                if ($wrapper.find('.dataTables_paginate').length && !$('#casteDtFooter .dataTables_paginate').length) {
                    $('#casteDtFooter').empty().data('dtReady', false);
                    enhanceCasteDtControls();
                }
                updateCasteDtCount();
            });
            setTimeout(function () { enhanceCasteDtControls(); updateCasteDtCount(); }, 300);
        })();

        /* ---- Add / Edit modal ---- */
        var $form = $('#casteForm');
        var $alert = $('#casteFormAlert');
        function casteClearErrors() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            $alert.addClass('d-none').removeClass('alert-danger alert-success').empty();
        }
        function casteResetForm() { $form[0].reset(); $('#castePk').val(''); casteClearErrors(); }

        $('#casteAddBtn').on('click', function () {
            casteResetForm();
            $('#casteFormModalLabel').text('Add Caste Category');
            $('#casteSubmitBtn').text('Add Caste Category');
            $('#casteStatus').val('1');
        });

        $(document).on('click', '#castecategorymaster-table .caste-edit-btn', function () {
            var $btn = $(this);
            casteResetForm();
            $('#casteFormModalLabel').text('Edit Caste Category');
            $('#casteSubmitBtn').text('Update');
            $('#castePk').val($btn.data('id'));
            $('#casteName').val($btn.data('name'));
            $('#casteNameHi').val($btn.data('name-hi'));
            $('#casteStatus').val(String($btn.data('status')) === '1' ? '1' : '0');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('casteFormModal')).show();
        });

        $form.on('submit', function (e) {
            e.preventDefault();
            casteClearErrors();
            var $submit = $('#casteSubmitBtn');
            var originalText = $submit.text();
            $submit.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');
            $.ajax({
                url: $form.attr('action'), type: 'POST', data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function (response) {
                    $alert.removeClass('d-none alert-danger').addClass('alert-success').html('<i class="bi bi-check-circle me-1"></i>' + (response.message || 'Saved successfully.'));
                    if ($.fn.DataTable.isDataTable(TABLE_ID)) { $(TABLE_ID).DataTable().ajax.reload(null, false); }
                    setTimeout(function () { bootstrap.Modal.getInstance(document.getElementById('casteFormModal'))?.hide(); casteResetForm(); }, 1000);
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

        document.getElementById('casteFormModal').addEventListener('hidden.bs.modal', function () {
            casteResetForm();
            $('#casteFormModalLabel').text('Add Caste Category');
            $('#casteSubmitBtn').text('Add Caste Category');
        });
    });
</script>
@endpush
