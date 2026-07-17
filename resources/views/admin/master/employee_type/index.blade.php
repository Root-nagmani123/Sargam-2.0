@extends('admin.layouts.master')

@section('title', 'Employee Type Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/employee-type-master-admin.css') }}?v={{ @filemtime(public_path('css/employee-type-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid etm-master-page">
    <x-breadcrum title="Employee Type Master" :showBack="false">
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm etm-open-add-btn"
            aria-controls="etmTypeModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Employee Type</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card etm-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnEtmColumns"
                        data-bs-toggle="modal" data-bs-target="#etmColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="etmDtSearch" class="programme-dt-search" data-dt-search-for="employeetypemaster-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel etm-dt-panel">
                <div class="table-responsive etm-dt-scroll">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="etmDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="employeetypemaster-table"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="etmColumnVisibilityModal" tabindex="-1" aria-labelledby="etmColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="etmColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="etmColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="etmTypeModal" tabindex="-1" aria-labelledby="etmTypeModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content etm-form-modal border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="etmTypeModalLabel">Add Employee Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="etmTypeForm" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="pk" id="etm_pk" value="">

                    <label for="etm_employee_type_name" class="form-label etm-field-label">
                        Employee Type Name <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                        name="employee_type_name"
                        id="etm_employee_type_name"
                        class="form-control"
                        placeholder="eg. General Medicine"
                        autocomplete="off">
                    <small class="text-danger d-none mt-1" id="etm_employee_type_name_error">
                        Employee Type Name is required
                    </small>
                </form>
            </div>
            <div class="modal-footer border-0 gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-3 px-4" id="etmFormSubmit">Create Employee Type</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
(function () {
    var tableSelector = '#employeetypemaster-table';
    var storeUrl = "{{ route('master.employee.type.store') }}";
    var csrfToken = "{{ csrf_token() }}";
    var colStorageKey = 'etm_hidden_columns_v1';
    var etmModalMode = 'add';

    var etmModalEl = document.getElementById('etmTypeModal');
    if (etmModalEl && etmModalEl.parentElement && etmModalEl.parentElement !== document.body) {
        document.body.appendChild(etmModalEl);
    }

    function showEtmModal() {
        if (!etmModalEl) {
            return;
        }
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(etmModalEl).show();
        } else if (window.jQuery) {
            jQuery(etmModalEl).modal('show');
        }
    }

    function hideEtmModal() {
        if (!etmModalEl) {
            return;
        }
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(etmModalEl).hide();
        } else if (window.jQuery) {
            jQuery(etmModalEl).modal('hide');
        }
    }

    function clearEtmFieldErrors() {
        jQuery('#etm_employee_type_name_error').addClass('d-none').text('Employee Type Name is required');
        jQuery('#etm_employee_type_name').removeClass('is-invalid');
    }

    function showEtmFieldError(message) {
        jQuery('#etm_employee_type_name_error').text(message || 'Employee Type Name is required').removeClass('d-none');
        jQuery('#etm_employee_type_name').addClass('is-invalid');
    }

    function openEtmModal(mode, data) {
        etmModalMode = mode;
        var isAdd = mode === 'add';

        jQuery('#etmTypeModalLabel').text(isAdd ? 'Add Employee Type' : 'Edit Employee Type');
        jQuery('#etmFormSubmit').text(isAdd ? 'Create Employee Type' : 'Update Employee Type');
        jQuery('#etm_pk').val(isAdd ? '' : (data.pk || ''));
        jQuery('#etm_employee_type_name').val(isAdd ? '' : (data.name || ''));
        clearEtmFieldErrors();
        showEtmModal();

        window.setTimeout(function () {
            jQuery('#etm_employee_type_name').trigger('focus');
        }, 200);
    }

    function getEtmTable() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.DataTable || !jQuery.fn.DataTable.isDataTable(tableSelector)) {
            return null;
        }
        return jQuery(tableSelector).DataTable();
    }

    function reloadEtmTable() {
        var dt = getEtmTable();
        if (dt) {
            dt.ajax.reload(null, false);
        }
    }

    function etmGetHiddenCols() {
        try {
            var raw = JSON.parse(localStorage.getItem(colStorageKey));
            return Array.isArray(raw) ? raw : [];
        } catch (e) {
            return [];
        }
    }

    function etmPersistHiddenCols(arr) {
        try {
            localStorage.setItem(colStorageKey, JSON.stringify(arr));
        } catch (e) { /* storage unavailable — visibility just won't persist */ }
    }

    function setupEtmColumns(dt) {
        var $grid = jQuery('#etmColumnToggleGrid');
        if (!dt || !$grid.length || $grid.data('etm-colvis-ready')) {
            return;
        }

        var hidden = etmGetHiddenCols();

        dt.columns().every(function () {
            this.visible(hidden.indexOf(this.index()) === -1, false);
        });
        dt.columns.adjust();

        $grid.empty();

        dt.columns().every(function () {
            var idx = this.index();
            var title = jQuery(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) {
                return;
            }

            var inputId = 'etmcolvis_' + idx;
            var $cell = jQuery('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = jQuery('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            var $cb = jQuery('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(idx) === -1);

            $cb.on('change', function () {
                var h = etmGetHiddenCols();
                var pos = h.indexOf(idx);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else if (pos === -1) {
                    h.push(idx);
                }
                etmPersistHiddenCols(h);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
            });

            $label.append($cb).append(jQuery('<span></span>').text(title));
            $grid.append($cell.append($label));
        });

        $grid.data('etm-colvis-ready', true);
    }

    function deleteEtmType($btn) {
        var url = $btn.data('url');
        var name = $btn.data('name') || 'this employee type';
        if (!url) {
            return;
        }

        function performDelete() {
            jQuery.ajax({
                url: url,
                method: 'POST',
                data: { _token: csrfToken, _method: 'DELETE' },
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function (response) {
                    reloadEtmTable();
                    var message = (response && response.message) || 'Employee Type deleted successfully.';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else if (typeof toastr !== 'undefined') {
                        toastr.success(message);
                    }
                },
                error: function (xhr) {
                    var message = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Unable to delete this Employee Type. Please try again.';

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Error', text: message });
                    } else if (typeof toastr !== 'undefined') {
                        toastr.error(message);
                    }
                }
            });
        }

        var confirmText = 'Are you sure you want to delete "' + name + '"? This cannot be undone.';

        if (typeof Swal === 'undefined' || typeof Swal.fire !== 'function') {
            if (window.confirm(confirmText)) {
                performDelete();
            }
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: confirmText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then(function (result) {
            if (result.isConfirmed) {
                performDelete();
            }
        });
    }

    function submitEtmForm() {
        var name = jQuery('#etm_employee_type_name').val().trim();
        clearEtmFieldErrors();

        if (!name) {
            showEtmFieldError('Employee Type Name is required');
            jQuery('#etm_employee_type_name').trigger('focus');
            return;
        }

        var payload = {
            _token: csrfToken,
            employee_type_name: name
        };

        if (etmModalMode === 'edit') {
            payload.pk = jQuery('#etm_pk').val();
        }

        jQuery.ajax({
            url: storeUrl,
            method: 'POST',
            data: payload,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function (response) {
                hideEtmModal();
                reloadEtmTable();

                var message = response.message || (etmModalMode === 'edit'
                    ? 'Employee Type updated successfully.'
                    : 'Employee Type created successfully.');

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: etmModalMode === 'edit' ? 'Updated!' : 'Created!',
                        text: message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else if (typeof toastr !== 'undefined') {
                    toastr.success(message);
                }
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var errors = xhr.responseJSON.errors;
                    if (errors.employee_type_name && errors.employee_type_name[0]) {
                        showEtmFieldError(errors.employee_type_name[0]);
                    }
                    return;
                }

                var message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Something went wrong. Please try again.';

                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Error', text: message });
                } else if (typeof toastr !== 'undefined') {
                    toastr.error(message);
                }
            }
        });
    }

    function initEtmPage() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.dataTable) {
            return;
        }

        jQuery(document).on('click', '.etm-open-add-btn', function (e) {
            e.preventDefault();
            openEtmModal('add');
        });

        if (etmModalEl) {
            etmModalEl.addEventListener('hidden.bs.modal', function () {
                clearEtmFieldErrors();
                jQuery('#etm_pk').val('');
                jQuery('#etm_employee_type_name').val('');
            });
        }

        jQuery('#etmFormSubmit').on('click', submitEtmForm);

        jQuery('#etmTypeForm').on('submit', function (e) {
            e.preventDefault();
            submitEtmForm();
        });

        jQuery(document).on('click', tableSelector + ' .etm-edit-btn', function (e) {
            e.preventDefault();
            var $btn = jQuery(this);
            openEtmModal('edit', {
                pk: $btn.data('pk'),
                name: $btn.data('name')
            });
        });

        jQuery(document).on('click', tableSelector + ' .etm-delete-btn', function (e) {
            e.preventDefault();
            deleteEtmType(jQuery(this));
        });

        // init.dt may already have fired by the time this runs, so cover both cases.
        jQuery(tableSelector).on('init.dt', function (e, settings) {
            setupEtmColumns(new jQuery.fn.dataTable.Api(settings));
        });
        setupEtmColumns(getEtmTable());

        var params = new URLSearchParams(window.location.search);
        if (params.get('open_etm_modal') === 'add') {
            openEtmModal('add');
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        } else if (params.get('open_etm_modal') === 'edit') {
            var etmName = params.get('etm_name') || '';
            try {
                etmName = decodeURIComponent(etmName.replace(/\+/g, ' '));
            } catch (e) { /* keep raw */ }
            openEtmModal('edit', {
                pk: params.get('etm_pk') || '',
                name: etmName
            });
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEtmPage);
    } else {
        initEtmPage();
    }
})();
</script>
@endpush
