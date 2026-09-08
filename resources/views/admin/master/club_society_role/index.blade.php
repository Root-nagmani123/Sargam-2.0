@extends('admin.layouts.master')

@section('title', 'Define Club/ Society Role')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('setup_content')
{{-- Shares the .cs-master-page CSS scope with Define Club/ Society: identical
     icon-over-label Edit/Delete buttons, defined once in public/css/custom.css. --}}
<div class="container-fluid cs-master-page">
    <x-breadcrum title="Define Club/ Society Role">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="csrAddBtn" data-bs-toggle="modal" data-bs-target="#csrFormModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Club/ Society Role</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Secondary actions (Download / Print) --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
        <a href="{{ route('master.club.society.role.export') }}" class="btn programme-dt-btn-columns border-0 text-primary" title="Download as Excel">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </a>
        <a href="{{ route('master.club.society.role.print') }}" target="_blank" rel="noopener"
           class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </a>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="csrBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#csrColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span> <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="csrDtSearch" class="programme-dt-search" data-dt-search-for="clubsocietyrolemaster-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="csrDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="clubsocietyrolemaster-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Add / Edit Club Society Role Modal -->
<div class="modal fade" id="csrFormModal" tabindex="-1" aria-labelledby="csrFormModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form id="csrRoleForm" action="{{ route('master.club.society.role.store') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="pk" id="csrPk" value="">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="csrFormModalLabel">Add Club/ Society Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="csrFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <div class="mb-0">
                        <label for="csrName" class="form-label fw-semibold">Club/ Society Role Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="csrName" name="club_society_role_name"
                               placeholder="eg. Director" maxlength="255" autocomplete="off" required>
                        <div class="invalid-feedback" data-field="club_society_role_name"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-danger rounded-1 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-1 px-4" id="csrSubmitBtn">Add Club/ Society Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="csrColumnVisibilityModal" tabindex="-1" aria-labelledby="csrColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="csrColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="csrColumnToggleGrid"></div>
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
        var TABLE_ID = '#clubsocietyrolemaster-table';

        /* The search box, pagination and the "Showing N of M items" count are
           relocated into #csrDtSearch / #csrDtFooter by the global enhancer
           (public/js/datatable-global-ui.js) through the data-dt-search-for /
           data-dt-footer-for hooks on those slots. Do NOT rebuild them here — a
           second enhancer duplicates the global one and can race it. */

        /* ---- Column show / hide (DataTables API) ---- */
        var csrColStorageKey = 'csrGrid:hiddenColumns:v1';

        function csrGetHiddenCols() {
            try {
                var raw = localStorage.getItem(csrColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return [];
            }
        }

        function csrPersistHiddenCols(arr) {
            try { localStorage.setItem(csrColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }

        function setupCsrColumns(dt) {
            if (!dt) {
                return;
            }
            var hidden = csrGetHiddenCols();

            dt.columns().every(function () {
                var idx = this.index();
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();

            var $grid = $('#csrColumnToggleGrid');
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

                var inputId = 'csrcolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function () {
                    var h = csrGetHiddenCols();
                    var pos = h.indexOf(idx);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(idx);
                    }
                    csrPersistHiddenCols(h);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                });

                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }

        /* ---- Wait for the Yajra DataTable to finish initialising ---- */
        setTimeout(function () {
            if (!$.fn.DataTable.isDataTable(TABLE_ID)) {
                return;
            }
            setupCsrColumns($(TABLE_ID).DataTable());
        }, 150);

        /* ---- Add / Edit modal ---- */
        var $form  = $('#csrRoleForm');
        var $alert = $('#csrFormAlert');

        function csrClearErrors() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            $alert.addClass('d-none').removeClass('alert-danger alert-success').empty();
        }

        function csrResetForm() {
            $form[0].reset();
            $('#csrPk').val('');
            csrClearErrors();
        }

        function csrSetMode(mode) {
            var isEdit = mode === 'edit';
            $('#csrFormModalLabel').text(isEdit ? 'Edit Club/ Society Role' : 'Add Club/ Society Role');
            $('#csrSubmitBtn').text(isEdit ? 'Update' : 'Add Club/ Society Role');
        }

        // Open for "Add"
        $('#csrAddBtn').on('click', function () {
            csrResetForm();
            csrSetMode('add');
        });

        // Open for "Edit" (populated from the row's data attributes)
        $(document).on('click', TABLE_ID + ' .csr-edit-btn', function () {
            var $btn = $(this);
            csrResetForm();
            csrSetMode('edit');

            $('#csrPk').val($btn.data('id'));
            $('#csrName').val($btn.data('name'));

            bootstrap.Modal.getOrCreateInstance(document.getElementById('csrFormModal')).show();
        });

        // AJAX submit (create and update share the store route)
        $form.on('submit', function (e) {
            e.preventDefault();
            csrClearErrors();

            var $submit = $('#csrSubmitBtn');
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
                        var instance = bootstrap.Modal.getInstance(document.getElementById('csrFormModal'));
                        if (instance) { instance.hide(); }
                        csrResetForm();
                    }, 900);
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

        // Reset whenever the modal closes so a stale edit can't leak into Add
        document.getElementById('csrFormModal').addEventListener('hidden.bs.modal', function () {
            csrResetForm();
            csrSetMode('add');
        });

        /* ---- Delete ---- */
        var deleteUrlTemplate = "{{ route('master.club.society.role.destroy', ['id' => '__ID__']) }}";

        $(document).on('click', TABLE_ID + ' .csr-delete-btn', function () {
            var $btn = $(this);
            var id   = $btn.data('id');
            var name = $btn.data('name') || 'this role';

            Swal.fire({
                title: 'Are you sure?',
                text: 'Delete "' + name + '"? This cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                $btn.prop('disabled', true);

                $.ajax({
                    url: deleteUrlTemplate.replace('__ID__', encodeURIComponent(id)),
                    type: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    headers: { 'Accept': 'application/json' },
                    success: function (res) {
                        Swal.fire('Deleted!', (res && res.message) || 'Club/ Society role deleted successfully.', 'success');
                        if ($.fn.DataTable.isDataTable(TABLE_ID)) {
                            $(TABLE_ID).DataTable().ajax.reload(null, false);
                        }
                    },
                    error: function (xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message)
                            ? xhr.responseJSON.message
                            : 'Something went wrong while deleting.';
                        Swal.fire('Error!', msg, 'error');
                        $btn.prop('disabled', false);
                    }
                });
            });
        });
    });
</script>
@endpush
