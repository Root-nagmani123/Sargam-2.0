@extends('admin.layouts.master')

@section('title', 'Hostel Building')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid mst-page hostel-building-page">
    <x-breadcrum title="Building Master" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="hbAddBtn" data-bs-toggle="modal" data-bs-target="#hbFormModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Hostel Building</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Secondary actions (Download / Print) — above the card (§1). Download is
         the controller's one .xlsx export; Print prints this screen, and the
         master-admin.css print rules drop the toolbar, pager and Action column. --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 mst-secondary-actions">
        <a href="{{ route('master.hostel.building.export') }}"
           class="btn programme-dt-btn-columns border-0 text-primary" title="Download as Excel (.xlsx)">
            <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
        </a>
        <button type="button" class="btn programme-dt-btn-columns border-0 text-primary" id="hbPrintBtn" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="hbBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#hbColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="hbDtSearch" class="programme-dt-search" data-dt-search-for="hostelbuildingmaster-table"></div>
                </div>
            </div>

            {{-- Search, pager and "Showing N of M items" are relocated into the
                 slots by public/js/datatable-global-ui.js. --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="hbDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="hostelbuildingmaster-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Add / Edit Hostel Building -->
<div class="modal fade mst-modal" id="hbFormModal" tabindex="-1" aria-labelledby="hbFormModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="hbBuildingForm" action="{{ route('master.hostel.building.store') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="pk" id="hbPk" value="">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="hbFormModalLabel">Add Hostel Building</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="hbFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <div class="mst-field-card">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="hbBuildingName" class="mst-form-label d-block">
                                    Building Name <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <input type="text" class="form-control mst-control" id="hbBuildingName" name="building_name"
                                       placeholder="eg. Naramada Hostel" maxlength="255" required aria-required="true">
                                <div class="invalid-feedback" data-field="building_name"></div>
                            </div>

                            <div class="col-sm-6">
                                <label for="hbFloors" class="mst-form-label d-block">
                                    Number of Floors <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <input type="number" class="form-control mst-control" id="hbFloors" name="no_of_floors"
                                       placeholder="eg. 25" min="0" required aria-required="true">
                                <div class="invalid-feedback" data-field="no_of_floors"></div>
                            </div>

                            <div class="col-sm-6">
                                <label for="hbRooms" class="mst-form-label d-block">
                                    Number of Rooms <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <input type="number" class="form-control mst-control" id="hbRooms" name="no_of_rooms"
                                       placeholder="eg. 24" min="0" required aria-required="true">
                                <div class="invalid-feedback" data-field="no_of_rooms"></div>
                            </div>

                            <div class="col-sm-6">
                                <label for="hbType" class="mst-form-label d-block">
                                    Building Type <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <select class="form-select mst-control mst-searchable" id="hbType" name="building_type"
                                        data-placeholder="Select Type" required aria-required="true">
                                    <option value="">Select Type</option>
                                    @foreach(($buildingType ?? []) as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" data-field="building_type"></div>
                            </div>

                            <div class="col-sm-6">
                                <label for="hbStatus" class="mst-form-label d-block">
                                    Building Status <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <select class="form-select mst-control mst-searchable" id="hbStatus" name="active_inactive"
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
                    <button type="submit" class="btn mst-btn-submit px-4" id="hbSubmitBtn">Add Hostel Building</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility -->
<div class="modal fade" id="hbColumnVisibilityModal" tabindex="-1" aria-labelledby="hbColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="hbColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3 mst-colvis-grid" id="hbColumnToggleGrid"></div>
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
        var TABLE_ID = '#hostelbuildingmaster-table';

        /* Search box, pagination and the "Showing N of M items" count are relocated
           into #hbDtSearch / #hbDtFooter by the global enhancer
           (public/js/datatable-global-ui.js). Do NOT rebuild them here. */

        /* ---- Column show / hide (shared Columns modal) ---- */
        MstAdmin.columnVisibility({
            table: TABLE_ID,
            grid: '#hbColumnToggleGrid',
            storageKey: 'sargam.hostelBuildingMaster.hiddenCols.{{ auth()->id() ?? 'guest' }}'
        });

        /* ---- Print ---- */
        $('#hbPrintBtn').on('click', function () {
            window.print();
        });

        /* ---- Add / Edit modal ---- */
        var $form = $('#hbBuildingForm');
        var $alert = $('#hbFormAlert');

        // Selects are Select2 (.mst-searchable): after any value change made
        // here, repaint the rendered selection.
        function hbSyncSelects() {
            $form.find('select.mst-searchable').trigger('change.select2');
        }

        function hbClearErrors() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            $alert.addClass('d-none').removeClass('alert-danger alert-success').empty();
        }

        function hbResetForm() {
            $form[0].reset();
            $('#hbPk').val('');
            hbClearErrors();
            hbSyncSelects();
        }

        // Open for "Add"
        $('#hbAddBtn').on('click', function () {
            hbResetForm();
            $('#hbFormModalLabel').text('Add Hostel Building');
            $('#hbSubmitBtn').text('Add Hostel Building');
            $('#hbStatus').val('1'); // sensible default for new records
            hbSyncSelects();
        });

        // Open for "Edit" (populate from row data attributes)
        $(document).on('click', '#hostelbuildingmaster-table .hb-edit-btn', function () {
            var $btn = $(this);
            hbResetForm();
            $('#hbFormModalLabel').text('Edit Hostel Building');
            $('#hbSubmitBtn').text('Update');

            $('#hbPk').val($btn.data('id'));
            $('#hbBuildingName').val($btn.data('name'));
            $('#hbFloors').val($btn.data('floors'));
            $('#hbRooms').val($btn.data('rooms'));
            $('#hbType').val(String($btn.data('type')));
            $('#hbStatus').val(String($btn.data('status')));
            hbSyncSelects();

            var modalEl = document.getElementById('hbFormModal');
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });

        // AJAX submit (create + update share the store route)
        $form.on('submit', function (e) {
            e.preventDefault();
            hbClearErrors();

            var $submit = $('#hbSubmitBtn');
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
                        bootstrap.Modal.getInstance(document.getElementById('hbFormModal'))?.hide();
                        hbResetForm();
                    }, 1000);
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(function (field) {
                            var $input = $form.find('[name="' + field + '"]');
                            $input.addClass('is-invalid');
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

        // Reset the form whenever the modal closes (so a stale edit can't leak into Add)
        document.getElementById('hbFormModal').addEventListener('hidden.bs.modal', function () {
            hbResetForm();
            $('#hbFormModalLabel').text('Add Hostel Building');
            $('#hbSubmitBtn').text('Add Hostel Building');
        });
    });
</script>
@endpush
