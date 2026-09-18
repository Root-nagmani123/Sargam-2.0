@extends('admin.layouts.master')

@section('title', 'Term Master')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('setup_content')
<div class="container-fluid term-master-page">
    <x-breadcrum title="Term Master">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="tmAddBtn" data-bs-toggle="modal" data-bs-target="#tmFormModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Term</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="tmBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#tmColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="tmDtSearch" class="programme-dt-search" data-dt-search-for="termmaster-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="tmDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="termmaster-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Add / Edit Term Modal -->
<div class="modal fade" id="tmFormModal" tabindex="-1" aria-labelledby="tmFormModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form id="tmForm" action="{{ route('master.term.store') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="pk" id="tmPk" value="">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="tmFormModalLabel">Add Term</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="tmFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <div class="mb-3">
                        <label for="tmName" class="form-label fw-semibold">Term <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="tmName" name="term_name"
                               placeholder="eg. Mid-Term" maxlength="100" required>
                        <div class="invalid-feedback" data-field="term_name"></div>
                    </div>

                    <div class="mb-0">
                        <label for="tmStatus" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="tmStatus" name="active_inactive" required>
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <div class="invalid-feedback" data-field="active_inactive"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-1 px-4" id="tmSubmitBtn">Add Term</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="tmColumnVisibilityModal" tabindex="-1" aria-labelledby="tmColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="tmColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="tmColumnToggleGrid"></div>
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
        var TABLE_ID = '#termmaster-table';
        var table;

        /* Search box, pagination and the "Showing N of M items" count are relocated
           into #tmDtSearch / #tmDtFooter by the global enhancer
           (public/js/datatable-global-ui.js) via the data-dt-search-for /
           data-dt-footer-for hooks on those slots. Do NOT rebuild them here — a
           second enhancer duplicates the global one and can race it. */

        /* ---- Column show / hide (DataTables API) ---- */
        var tmColStorageKey = 'tmGrid:hiddenColumns:v1';

        function tmGetHiddenCols() {
            try {
                var raw = localStorage.getItem(tmColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return [];
            }
        }

        function tmPersistHiddenCols(arr) {
            try { localStorage.setItem(tmColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }

        function setupTmColumns(dt) {
            if (!dt) {
                return;
            }
            var hidden = tmGetHiddenCols();

            dt.columns().every(function () {
                var idx = this.index();
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();

            var $grid = $('#tmColumnToggleGrid');
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

                var inputId = 'tmcolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function () {
                    var h = tmGetHiddenCols();
                    var pos = h.indexOf(idx);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(idx);
                    }
                    tmPersistHiddenCols(h);
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

            setupTmColumns(table);
        }, 150);

        /* ---- Status toggle (shared /admin/toggle-status endpoint) ---- */
        document.addEventListener('change', function (e) {
            if (!e.target.matches('#termmaster-table .status-toggle')) {
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
        $(document).on('submit', '#termmaster-table .tm-delete-form', function (e) {
            if (!confirm('Are you sure you want to delete this term?')) {
                e.preventDefault();
            }
        });

        /* ---- Add / Edit modal ---- */
        var $form = $('#tmForm');
        var $alert = $('#tmFormAlert');

        function tmClearErrors() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            $alert.addClass('d-none').removeClass('alert-danger alert-success').empty();
        }

        function tmResetForm() {
            $form[0].reset();
            $('#tmPk').val('');
            tmClearErrors();
        }

        // Open for "Add"
        $('#tmAddBtn').on('click', function () {
            tmResetForm();
            $('#tmFormModalLabel').text('Add Term');
            $('#tmSubmitBtn').text('Add Term');
            $('#tmStatus').val('1');
        });

        // Open for "Edit"
        $(document).on('click', '#termmaster-table .tm-edit-btn', function () {
            var $btn = $(this);
            tmResetForm();
            $('#tmFormModalLabel').text('Edit Term');
            $('#tmSubmitBtn').text('Update');

            $('#tmPk').val($btn.data('id'));
            $('#tmName').val($btn.data('name'));
            $('#tmStatus').val(String($btn.data('status')));

            bootstrap.Modal.getOrCreateInstance(document.getElementById('tmFormModal')).show();
        });

        // AJAX submit (create + update share the store route)
        $form.on('submit', function (e) {
            e.preventDefault();
            tmClearErrors();

            var $submit = $('#tmSubmitBtn');
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
                        bootstrap.Modal.getInstance(document.getElementById('tmFormModal'))?.hide();
                        tmResetForm();
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
        document.getElementById('tmFormModal').addEventListener('hidden.bs.modal', function () {
            tmResetForm();
            $('#tmFormModalLabel').text('Add Term');
            $('#tmSubmitBtn').text('Add Term');
        });
    });
</script>
@endpush
