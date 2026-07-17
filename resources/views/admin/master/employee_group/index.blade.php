@extends('admin.layouts.master')

@section('title', 'Employee Group Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/employee-group-master-admin.css') }}?v={{ @filemtime(public_path('css/employee-group-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid egm-master-page">
    <x-breadcrum title="Employee Group Master">
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm egm-open-add-btn"
            aria-controls="egmGroupModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Employee Group</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card egm-dt-card shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnEgmColumns"
                        data-bs-toggle="modal" data-bs-target="#egmColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="egmDtSearch" class="programme-dt-search" data-dt-search-for="employeegroupmaster-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel egm-dt-panel">
                <div class="table-responsive egm-dt-scroll">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table border-0']) !!}
                </div>
                <div id="egmDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="employeegroupmaster-table"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="egmColumnVisibilityModal" tabindex="-1" aria-labelledby="egmColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="egmColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="egmColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade egm-group-modal" id="egmGroupModal" tabindex="-1" aria-labelledby="egmGroupModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered egm-group-modal-dialog">
        <div class="modal-content cgt-form-modal egm-group-modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="egmGroupModalLabel">Add Employee Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="egmGroupForm" class="egm-group-modal-form" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="pk" id="egm_pk" value="">

                    <label for="egm_group_name" class="form-label cgt-field-label mb-2">
                        Employee Group Name <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                        name="group_name"
                        id="egm_group_name"
                        class="form-control rounded-3"
                        placeholder="eg. General Medicine"
                        autocomplete="off">
                    <small class="text-danger d-none mt-1" id="egm_group_name_error">
                        Employee Group Name is required
                    </small>
                </form>
            </div>
            <div class="modal-footer gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-3 px-4" id="egmFormSubmit">Create Employee Group</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
(function () {
    var tableSelector = '#employeegroupmaster-table';
    var storeUrl = "{{ route('master.employee.group.store') }}";
    var csrfToken = "{{ csrf_token() }}";
    var colStorageKey = 'egm_hidden_columns_v1';
    var egmModalMode = 'add';

    var egmModalEl = document.getElementById('egmGroupModal');
    if (egmModalEl && egmModalEl.parentElement && egmModalEl.parentElement !== document.body) {
        document.body.appendChild(egmModalEl);
    }

    function showEgmModal() {
        if (!egmModalEl) {
            return;
        }
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(egmModalEl).show();
        } else if (window.jQuery) {
            jQuery(egmModalEl).modal('show');
        }
    }

    function hideEgmModal() {
        if (!egmModalEl) {
            return;
        }
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(egmModalEl).hide();
        } else if (window.jQuery) {
            jQuery(egmModalEl).modal('hide');
        }
    }

    function clearEgmFieldErrors() {
        jQuery('#egm_group_name_error').addClass('d-none').text('Employee Group Name is required');
        jQuery('#egm_group_name').removeClass('is-invalid');
    }

    function showEgmFieldError(message) {
        jQuery('#egm_group_name_error').text(message || 'Employee Group Name is required').removeClass('d-none');
        jQuery('#egm_group_name').addClass('is-invalid');
    }

    function openEgmModal(mode, data) {
        egmModalMode = mode;
        var isAdd = mode === 'add';

        jQuery('#egmGroupModalLabel').text(isAdd ? 'Add Employee Group' : 'Edit Employee Group');
        jQuery('#egmFormSubmit').text(isAdd ? 'Create Employee Group' : 'Update Employee Group');
        jQuery('#egm_pk').val(isAdd ? '' : (data.pk || ''));
        jQuery('#egm_group_name').val(isAdd ? '' : (data.name || ''));
        clearEgmFieldErrors();
        showEgmModal();

        window.setTimeout(function () {
            jQuery('#egm_group_name').trigger('focus');
        }, 200);
    }

    function getEgmTable() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.DataTable || !jQuery.fn.DataTable.isDataTable(tableSelector)) {
            return null;
        }
        return jQuery(tableSelector).DataTable();
    }

    function reloadEgmTable() {
        var dt = getEgmTable();
        if (dt) {
            dt.ajax.reload(null, false);
        }
    }

    function egmGetHiddenCols() {
        try {
            var raw = JSON.parse(localStorage.getItem(colStorageKey));
            return Array.isArray(raw) ? raw : [];
        } catch (e) {
            return [];
        }
    }

    function egmPersistHiddenCols(arr) {
        try {
            localStorage.setItem(colStorageKey, JSON.stringify(arr));
        } catch (e) { /* storage unavailable — visibility just won't persist */ }
    }

    function setupEgmColumns(dt) {
        var $grid = jQuery('#egmColumnToggleGrid');
        if (!dt || !$grid.length || $grid.data('egm-colvis-ready')) {
            return;
        }

        var hidden = egmGetHiddenCols();

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

            var inputId = 'egmcolvis_' + idx;
            var $cell = jQuery('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = jQuery('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            var $cb = jQuery('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(idx) === -1);

            $cb.on('change', function () {
                var h = egmGetHiddenCols();
                var pos = h.indexOf(idx);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else if (pos === -1) {
                    h.push(idx);
                }
                egmPersistHiddenCols(h);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
            });

            $label.append($cb).append(jQuery('<span></span>').text(title));
            $grid.append($cell.append($label));
        });

        $grid.data('egm-colvis-ready', true);
    }

    function deleteEgmGroup($btn) {
        var url = $btn.data('url');
        var name = $btn.data('name') || 'this employee group';
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
                    reloadEgmTable();
                    var message = (response && response.message) || 'Employee Group deleted successfully.';
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
                        : 'Unable to delete this Employee Group. Please try again.';

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

    function submitEgmForm() {
        var name = jQuery('#egm_group_name').val().trim();
        clearEgmFieldErrors();

        if (!name) {
            showEgmFieldError('Employee Group Name is required');
            jQuery('#egm_group_name').trigger('focus');
            return;
        }

        var payload = {
            _token: csrfToken,
            group_name: name
        };

        if (egmModalMode === 'edit') {
            payload.pk = jQuery('#egm_pk').val();
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
                hideEgmModal();
                reloadEgmTable();

                var message = response.message || (egmModalMode === 'edit'
                    ? 'Employee Group updated successfully.'
                    : 'Employee Group created successfully.');

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: egmModalMode === 'edit' ? 'Updated!' : 'Created!',
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
                    if (errors.group_name && errors.group_name[0]) {
                        showEgmFieldError(errors.group_name[0]);
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

    function initEgmPage() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.dataTable) {
            return;
        }

        jQuery(document).on('click', '.egm-open-add-btn', function (e) {
            e.preventDefault();
            openEgmModal('add');
        });

        if (egmModalEl) {
            egmModalEl.addEventListener('hidden.bs.modal', function () {
                clearEgmFieldErrors();
                jQuery('#egm_pk').val('');
                jQuery('#egm_group_name').val('');
            });
        }

        jQuery('#egmFormSubmit').on('click', submitEgmForm);

        jQuery('#egmGroupForm').on('submit', function (e) {
            e.preventDefault();
            submitEgmForm();
        });

        jQuery(document).on('click', tableSelector + ' .egm-edit-btn', function (e) {
            e.preventDefault();
            var $btn = jQuery(this);
            openEgmModal('edit', {
                pk: $btn.data('pk'),
                name: $btn.data('name')
            });
        });

        jQuery(document).on('click', tableSelector + ' .egm-delete-btn', function (e) {
            e.preventDefault();
            deleteEgmGroup(jQuery(this));
        });

        // init.dt may already have fired by the time this runs, so cover both cases.
        jQuery(tableSelector).on('init.dt', function (e, settings) {
            setupEgmColumns(new jQuery.fn.dataTable.Api(settings));
        });
        setupEgmColumns(getEgmTable());

        var params = new URLSearchParams(window.location.search);
        if (params.get('open_egm_modal') === 'add') {
            openEgmModal('add');
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        } else if (params.get('open_egm_modal') === 'edit') {
            var egmName = params.get('egm_name') || '';
            try {
                egmName = decodeURIComponent(egmName.replace(/\+/g, ' '));
            } catch (e) { /* keep raw */ }
            openEgmModal('edit', {
                pk: params.get('egm_pk') || '',
                name: egmName
            });
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEgmPage);
    } else {
        initEgmPage();
    }
})();
</script>
@endpush
