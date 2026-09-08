@extends('admin.layouts.master')

@section('title', 'Define Club/ Society')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('setup_content')
<div class="container-fluid cs-master-page">
    <x-breadcrum title="Define Club/ Society">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="csAddBtn" data-bs-toggle="modal" data-bs-target="#csFormModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Club Society</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Secondary actions (Download / Print) --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
        <a href="{{ route('master.club.society.export') }}" class="btn programme-dt-btn-columns border-0 text-primary" title="Download as Excel">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </a>
        <a href="{{ route('master.club.society.print') }}" target="_blank" rel="noopener"
           class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </a>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="csBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#csColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span> <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="csDtSearch" class="programme-dt-search" data-dt-search-for="clubsocietymaster-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="csDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="clubsocietymaster-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Add / Edit Club Society Modal -->
<div class="modal fade" id="csFormModal" tabindex="-1" aria-labelledby="csFormModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form id="csClubSocietyForm" action="{{ route('master.club.society.store') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="pk" id="csPk" value="">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="csFormModalLabel">Add Club/ Society</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="csFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <div class="mb-0">
                        <label for="csName" class="form-label fw-semibold">Club/ Society Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="csName" name="club_society_name"
                               placeholder="eg. Officer&#039;s Mess" maxlength="255" autocomplete="off" required>
                        <div class="invalid-feedback" data-field="club_society_name"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-danger rounded-1 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-1 px-4" id="csSubmitBtn">Add Club/ Society</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="csColumnVisibilityModal" tabindex="-1" aria-labelledby="csColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="csColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="csColumnToggleGrid"></div>
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
        var TABLE_ID = '#clubsocietymaster-table';

        /* The search box, pagination and the "Showing N of M items" count are
           relocated into #csDtSearch / #csDtFooter by the global enhancer
           (public/js/datatable-global-ui.js) through the data-dt-search-for /
           data-dt-footer-for hooks on those slots. Do NOT rebuild them here — a
           second enhancer duplicates the global one and can race it. */

        /* ---- Column show / hide (DataTables API) ---- */
        var csColStorageKey = 'csGrid:hiddenColumns:v1';

        function csGetHiddenCols() {
            try {
                var raw = localStorage.getItem(csColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return [];
            }
        }

        function csPersistHiddenCols(arr) {
            try { localStorage.setItem(csColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }

        function setupCsColumns(dt) {
            if (!dt) {
                return;
            }
            var hidden = csGetHiddenCols();

            dt.columns().every(function () {
                var idx = this.index();
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();

            var $grid = $('#csColumnToggleGrid');
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

                var inputId = 'cscolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function () {
                    var h = csGetHiddenCols();
                    var pos = h.indexOf(idx);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(idx);
                    }
                    csPersistHiddenCols(h);
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
            setupCsColumns($(TABLE_ID).DataTable());
        }, 150);

        /* ---- Add / Edit modal ---- */
        var $form  = $('#csClubSocietyForm');
        var $alert = $('#csFormAlert');

        function csClearErrors() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            $alert.addClass('d-none').removeClass('alert-danger alert-success').empty();
        }

        function csResetForm() {
            $form[0].reset();
            $('#csPk').val('');
            csClearErrors();
        }

        function csSetMode(mode) {
            var isEdit = mode === 'edit';
            $('#csFormModalLabel').text(isEdit ? 'Edit Club/ Society' : 'Add Club/ Society');
            $('#csSubmitBtn').text(isEdit ? 'Update' : 'Add Club/ Society');
        }

        // Open for "Add"
        $('#csAddBtn').on('click', function () {
            csResetForm();
            csSetMode('add');
        });

        // Open for "Edit" (populated from the row's data attributes)
        $(document).on('click', TABLE_ID + ' .cs-edit-btn', function () {
            var $btn = $(this);
            csResetForm();
            csSetMode('edit');

            $('#csPk').val($btn.data('id'));
            $('#csName').val($btn.data('name'));

            bootstrap.Modal.getOrCreateInstance(document.getElementById('csFormModal')).show();
        });

        // AJAX submit (create and update share the store route)
        $form.on('submit', function (e) {
            e.preventDefault();
            csClearErrors();

            var $submit = $('#csSubmitBtn');
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
                        var instance = bootstrap.Modal.getInstance(document.getElementById('csFormModal'));
                        if (instance) { instance.hide(); }
                        csResetForm();
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
        document.getElementById('csFormModal').addEventListener('hidden.bs.modal', function () {
            csResetForm();
            csSetMode('add');
        });

        /* ---- Delete ---- */
        var deleteUrlTemplate = "{{ route('master.club.society.destroy', ['id' => '__ID__']) }}";

        $(document).on('click', TABLE_ID + ' .cs-delete-btn', function () {
            var $btn = $(this);
            var id   = $btn.data('id');
            var name = $btn.data('name') || 'this club/ society';

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
                        Swal.fire('Deleted!', (res && res.message) || 'Club/ Society deleted successfully.', 'success');
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
