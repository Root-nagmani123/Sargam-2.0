@extends('admin.layouts.master')

@section('title', 'Component Master')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('setup_content')
<div class="container-fluid component-master-page">
    <x-breadcrum title="Component Master">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="cmAddBtn" data-bs-toggle="modal" data-bs-target="#cmFormModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Component</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="cmBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#cmColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="cmDtSearch" class="programme-dt-search" data-dt-search-for="componentmaster-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="cmDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="componentmaster-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Add / Edit Component Modal -->
<div class="modal fade" id="cmFormModal" tabindex="-1" aria-labelledby="cmFormModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form id="cmForm" action="{{ route('master.component.store') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="pk" id="cmPk" value="">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="cmFormModalLabel">Add Component</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="cmFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <div class="mb-3">
                        <label for="cmName" class="form-label fw-semibold">Component <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cmName" name="component_name"
                               placeholder="eg. Discipline" maxlength="100" required>
                        <div class="invalid-feedback" data-field="component_name"></div>
                    </div>

                    <div class="mb-0">
                        <label for="cmStatus" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="cmStatus" name="active_inactive" required>
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <div class="invalid-feedback" data-field="active_inactive"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-1 px-4" id="cmSubmitBtn">Add Component</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="cmColumnVisibilityModal" tabindex="-1" aria-labelledby="cmColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="cmColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="cmColumnToggleGrid"></div>
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
        var TABLE_ID = '#componentmaster-table';
        var table;

        var cmColStorageKey = 'cmGrid:hiddenColumns:v1';

        function cmGetHiddenCols() {
            try {
                var raw = localStorage.getItem(cmColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return [];
            }
        }

        function cmPersistHiddenCols(arr) {
            try { localStorage.setItem(cmColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }

        function setupCmColumns(dt) {
            if (!dt) {
                return;
            }
            var hidden = cmGetHiddenCols();

            dt.columns().every(function () {
                var idx = this.index();
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();

            var $grid = $('#cmColumnToggleGrid');
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

                var inputId = 'cmcolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function () {
                    var h = cmGetHiddenCols();
                    var pos = h.indexOf(idx);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(idx);
                    }
                    cmPersistHiddenCols(h);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                });

                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }

        setTimeout(function () {
            if (!$.fn.DataTable.isDataTable(TABLE_ID)) {
                return;
            }
            table = $(TABLE_ID).DataTable();

            setupCmColumns(table);
        }, 150);

        document.addEventListener('change', function (e) {
            if (!e.target.matches('#componentmaster-table .status-toggle')) {
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

        $(document).on('submit', '#componentmaster-table .cm-delete-form', function (e) {
            if (!confirm('Are you sure you want to delete this component?')) {
                e.preventDefault();
            }
        });

        var $form = $('#cmForm');
        var $alert = $('#cmFormAlert');

        function cmClearErrors() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            $alert.addClass('d-none').removeClass('alert-danger alert-success').empty();
        }

        function cmResetForm() {
            $form[0].reset();
            $('#cmPk').val('');
            cmClearErrors();
        }

        $('#cmAddBtn').on('click', function () {
            cmResetForm();
            $('#cmFormModalLabel').text('Add Component');
            $('#cmSubmitBtn').text('Add Component');
            $('#cmStatus').val('1');
        });

        $(document).on('click', '#componentmaster-table .cm-edit-btn', function () {
            var $btn = $(this);
            cmResetForm();
            $('#cmFormModalLabel').text('Edit Component');
            $('#cmSubmitBtn').text('Update');

            $('#cmPk').val($btn.data('id'));
            $('#cmName').val($btn.data('name'));
            $('#cmStatus').val(String($btn.data('status')));

            bootstrap.Modal.getOrCreateInstance(document.getElementById('cmFormModal')).show();
        });

        $form.on('submit', function (e) {
            e.preventDefault();
            cmClearErrors();

            var $submit = $('#cmSubmitBtn');
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
                        bootstrap.Modal.getInstance(document.getElementById('cmFormModal'))?.hide();
                        cmResetForm();
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

        document.getElementById('cmFormModal').addEventListener('hidden.bs.modal', function () {
            cmResetForm();
            $('#cmFormModalLabel').text('Add Component');
            $('#cmSubmitBtn').text('Add Component');
        });
    });
</script>
@endpush
