@extends('admin.layouts.master')

@section('title', 'Examination Type Master')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('setup_content')
<div class="container-fluid examination-type-page">
    <x-breadcrum title="Examination Type Master">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="etAddBtn" data-bs-toggle="modal" data-bs-target="#etFormModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Examination Type</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="etBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#etColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="etDtSearch" class="programme-dt-search" data-dt-search-for="examinationtypemaster-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="etDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="examinationtypemaster-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Add / Edit Examination Type Modal -->
<div class="modal fade" id="etFormModal" tabindex="-1" aria-labelledby="etFormModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form id="etForm" action="{{ route('master.examination.type.store') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="pk" id="etPk" value="">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="etFormModalLabel">Add Examination Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="etFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <div class="mb-3">
                        <label for="etName" class="form-label fw-semibold">Examination Type <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="etName" name="exam_type_name"
                               placeholder="eg. Director Assessment" maxlength="100" required>
                        <div class="invalid-feedback" data-field="exam_type_name"></div>
                    </div>

                    <div class="mb-0">
                        <label for="etStatus" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="etStatus" name="active_inactive" required>
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <div class="invalid-feedback" data-field="active_inactive"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-1 px-4" id="etSubmitBtn">Add Examination Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="etColumnVisibilityModal" tabindex="-1" aria-labelledby="etColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="etColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="etColumnToggleGrid"></div>
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
        var TABLE_ID = '#examinationtypemaster-table';
        var table;

        /* Search box, pagination and the "Showing N of M items" count are relocated
           into #etDtSearch / #etDtFooter by the global enhancer
           (public/js/datatable-global-ui.js) via the data-dt-search-for /
           data-dt-footer-for hooks on those slots. Do NOT rebuild them here — a
           second enhancer duplicates the global one and can race it. */

        /* ---- Column show / hide (DataTables API) ---- */
        var etColStorageKey = 'etGrid:hiddenColumns:v1';

        function etGetHiddenCols() {
            try {
                var raw = localStorage.getItem(etColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return [];
            }
        }

        function etPersistHiddenCols(arr) {
            try { localStorage.setItem(etColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }

        function setupEtColumns(dt) {
            if (!dt) {
                return;
            }
            var hidden = etGetHiddenCols();

            dt.columns().every(function () {
                var idx = this.index();
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();

            var $grid = $('#etColumnToggleGrid');
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

                var inputId = 'etcolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function () {
                    var h = etGetHiddenCols();
                    var pos = h.indexOf(idx);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(idx);
                    }
                    etPersistHiddenCols(h);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                });

                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }

        /* ---- Wait for Yajra DataTable init ---- */
        setTimeout(function () {
            if (!$.fn.DataTable.isDataTable(TABLE_ID)) {
                return;
            }
            table = $(TABLE_ID).DataTable();

            setupEtColumns(table);
        }, 150);

        /* ---- Status toggle (shared /admin/toggle-status endpoint) ---- */
        document.addEventListener('change', function (e) {
            if (!e.target.matches('#examinationtypemaster-table .status-toggle')) {
                return;
            }

            var checkbox = e.target;
            var status = checkbox.checked ? 1 : 0;

            $.ajax({
                url: typeof routes !== 'undefined' ? routes.toggleStatus : '',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    table: checkbox.dataset.table,
                    column: checkbox.dataset.column,
                    id: checkbox.dataset.id,
                    status: status
                },
                error: function () {
                    checkbox.checked = !status;
                }
            });
        }, true);

        /* ---- Delete confirmation ---- */
        $(document).on('submit', '#examinationtypemaster-table .et-delete-form', function (e) {
            if (!confirm('Are you sure you want to delete this examination type?')) {
                e.preventDefault();
            }
        });

        /* ---- Add / Edit modal ---- */
        var $form = $('#etForm');
        var $alert = $('#etFormAlert');

        function etClearErrors() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            $alert.addClass('d-none').removeClass('alert-danger alert-success').empty();
        }

        function etResetForm() {
            $form[0].reset();
            $('#etPk').val('');
            etClearErrors();
        }

        // Open for "Add"
        $('#etAddBtn').on('click', function () {
            etResetForm();
            $('#etFormModalLabel').text('Add Examination Type');
            $('#etSubmitBtn').text('Add Examination Type');
            $('#etStatus').val('1');
        });

        // Open for "Edit"
        $(document).on('click', '#examinationtypemaster-table .et-edit-btn', function () {
            var $btn = $(this);
            etResetForm();
            $('#etFormModalLabel').text('Edit Examination Type');
            $('#etSubmitBtn').text('Update');

            $('#etPk').val($btn.data('id'));
            $('#etName').val($btn.data('name'));
            $('#etStatus').val(String($btn.data('status')));

            bootstrap.Modal.getOrCreateInstance(document.getElementById('etFormModal')).show();
        });

        // AJAX submit (create + update share the store route)
        $form.on('submit', function (e) {
            e.preventDefault();
            etClearErrors();

            var $submit = $('#etSubmitBtn');
            var originalText = $submit.text();
            $submit.prop('disabled', true)
                   .html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function (response) {
                    $alert.removeClass('d-none alert-danger').addClass('alert-success')
                          .html('<i class="bi bi-check-circle me-1"></i>' + (response.message || 'Saved successfully.'));

                    if ($.fn.DataTable.isDataTable(TABLE_ID)) {
                        $(TABLE_ID).DataTable().ajax.reload(null, false);
                    }

                    setTimeout(function () {
                        bootstrap.Modal.getInstance(document.getElementById('etFormModal'))?.hide();
                        etResetForm();
                    }, 1000);
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(function (field) {
                            $form.find('[name="' + field + '"]').addClass('is-invalid');
                            $form.find('.invalid-feedback[data-field="' + field + '"]').text(errors[field][0]);
                        });
                    } else {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message)
                            ? xhr.responseJSON.message
                            : 'An error occurred while saving. Please try again.';
                        $alert.removeClass('d-none alert-success').addClass('alert-danger')
                              .html('<i class="bi bi-exclamation-circle me-1"></i>' + msg);
                    }
                },
                complete: function () {
                    $submit.prop('disabled', false).text(originalText);
                }
            });
        });

        // Reset on close so a stale edit can't leak into Add
        document.getElementById('etFormModal').addEventListener('hidden.bs.modal', function () {
            etResetForm();
            $('#etFormModalLabel').text('Add Examination Type');
            $('#etSubmitBtn').text('Add Examination Type');
        });
    });
</script>
@endpush
