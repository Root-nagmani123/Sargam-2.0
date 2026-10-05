@extends('admin.layouts.master')

@section('title', 'Hostel Floor')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid mst-page hostel-floor-page">
    <x-breadcrum title="Hostel Floor" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="hfAddBtn" data-bs-toggle="modal" data-bs-target="#hfFormModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Hostel Floor</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Secondary actions (Download / Print) — above the card (§1). Download is
         the controller's one .xlsx export; Print prints this screen, and the
         master-admin.css print rules drop the toolbar, pager and Action column. --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 mst-secondary-actions">
        <a href="{{ route('master.hostel.floor.export') }}"
           class="btn programme-dt-btn-columns border-0 text-primary" title="Download as Excel (.xlsx)">
            <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
        </a>
        <button type="button" class="btn programme-dt-btn-columns border-0 text-primary" id="hfPrintBtn" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="hfBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#hfColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="hfDtSearch" class="programme-dt-search" data-dt-search-for="hostelfloormaster-table"></div>
                </div>
            </div>

            {{-- Search, pager and "Showing N of M items" are relocated into the
                 slots by public/js/datatable-global-ui.js. --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="hfDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="hostelfloormaster-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Add / Edit Hostel Floor -->
<div class="modal fade mst-modal" id="hfFormModal" tabindex="-1" aria-labelledby="hfFormModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="hfFloorForm" action="{{ route('master.hostel.floor.store') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="pk" id="hfPk" value="">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="hfFormModalLabel">Add Hostel Floor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="hfFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <div class="mst-field-card">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="hfFloorName" class="mst-form-label d-block">
                                    Floor Name <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <input type="text" class="form-control mst-control" id="hfFloorName" name="floor_name"
                                       placeholder="eg. B1" maxlength="255" required aria-required="true">
                                <div class="invalid-feedback" data-field="floor_name"></div>
                            </div>

                            <div class="col-12">
                                <label for="hfStatus" class="mst-form-label d-block">
                                    Floor Status <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <select class="form-select mst-control mst-searchable" id="hfStatus" name="active_inactive"
                                        data-placeholder="Select Status" required aria-required="true">
                                    <option value="">Select Status</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                                <div class="invalid-feedback" data-field="active_inactive"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn mst-btn-cancel px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn mst-btn-submit px-4" id="hfSubmitBtn">Add Hostel Floor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility -->
<div class="modal fade" id="hfColumnVisibilityModal" tabindex="-1" aria-labelledby="hfColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="hfColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3 mst-colvis-grid" id="hfColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
<script>
    $(document).ready(function () {
        var TABLE_ID = '#hostelfloormaster-table';

        /* Search box, pagination and the "Showing N of M items" count are relocated
           into #hfDtSearch / #hfDtFooter by the global enhancer
           (public/js/datatable-global-ui.js). Do NOT rebuild them here. */

        /* ---- Column show / hide (shared Columns modal) ---- */
        MstAdmin.columnVisibility({
            table: TABLE_ID,
            grid: '#hfColumnToggleGrid',
            storageKey: 'sargam.hostelFloorMaster.hiddenCols.{{ auth()->id() ?? 'guest' }}'
        });

        /* ---- Print ---- */
        $('#hfPrintBtn').on('click', function () {
            window.print();
        });

        /* ---- Add / Edit modal ---- */
        var $form = $('#hfFloorForm');
        var $alert = $('#hfFormAlert');

        // Selects are Select2 (.mst-searchable): after any value change made
        // here, repaint the rendered selection.
        function hfSyncSelects() {
            $form.find('select.mst-searchable').trigger('change.select2');
        }

        function hfClearErrors() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            $alert.addClass('d-none').removeClass('alert-danger alert-success').empty();
        }

        function hfResetForm() {
            $form[0].reset();
            $('#hfPk').val('');
            hfClearErrors();
            hfSyncSelects();
        }

        // Open for "Add"
        $('#hfAddBtn').on('click', function () {
            hfResetForm();
            $('#hfFormModalLabel').text('Add Hostel Floor');
            $('#hfSubmitBtn').text('Add Hostel Floor');
            $('#hfStatus').val('1');
            hfSyncSelects();
        });

        // Open for "Edit"
        $(document).on('click', '#hostelfloormaster-table .hf-edit-btn', function () {
            var $btn = $(this);
            hfResetForm();
            $('#hfFormModalLabel').text('Edit Hostel Floor');
            $('#hfSubmitBtn').text('Update');

            $('#hfPk').val($btn.data('id'));
            $('#hfFloorName').val($btn.data('name'));
            $('#hfStatus').val(String($btn.data('status')));
            hfSyncSelects();

            bootstrap.Modal.getOrCreateInstance(document.getElementById('hfFormModal')).show();
        });

        // AJAX submit (create + update share the store route)
        $form.on('submit', function (e) {
            e.preventDefault();
            hfClearErrors();

            var $submit = $('#hfSubmitBtn');
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
                        bootstrap.Modal.getInstance(document.getElementById('hfFormModal'))?.hide();
                        hfResetForm();
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
        document.getElementById('hfFormModal').addEventListener('hidden.bs.modal', function () {
            hfResetForm();
            $('#hfFormModalLabel').text('Add Hostel Floor');
            $('#hfSubmitBtn').text('Add Hostel Floor');
        });
    });
</script>
@endpush
