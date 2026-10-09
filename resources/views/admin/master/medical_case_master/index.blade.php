@extends('admin.layouts.master')

@section('title', 'Medical Case Master')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid mst-page mcm-page">
    <x-breadcrum title="Medical Case Master" :showBack="false">
        <button type="button" id="showAlert"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                data-bs-toggle="modal" data-bs-target="#mcmAddModal" aria-controls="mcmAddModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Medical Case</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="mcmColumnsToggle"
                            data-bs-toggle="modal" data-bs-target="#mcmColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div class="programme-dt-search" data-dt-search-for="medicalCaseMasterTable"></div>
                </div>
            </div>

            {{-- Search, pager and "Showing N of M items" are relocated into the
                 slots by public/js/datatable-global-ui.js. --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="medicalCaseMasterTable">
                        <caption class="visually-hidden">Medical cases</caption>
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Case Name</th>
                                <th scope="col">Created Date</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="medicalCaseMasterTable"></div>
            </div>

        </div>
    </div>
</div>

{{-- Add / Edit modals (appended to body on load for correct stacking) --}}
<div class="modal fade mst-modal mcm-form-modal" id="mcmAddModal" tabindex="-1" aria-labelledby="mcmAddModalLabel"
     aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold mb-0" id="mcmAddModalLabel">Add Medical Case</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="medicalCaseForm" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">

                    <div class="mst-field-card">
                        <div class="mb-3">
                            <label for="mcm_add_case_name" class="mst-form-label d-block">
                                Case Name <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <input type="text" name="case_name" id="mcm_add_case_name"
                                   class="form-control mst-control"
                                   placeholder="eg. IPD" autocomplete="off"
                                   required aria-required="true">
                            <span class="mst-field-error d-none" id="mcm_add_case_name_error">Case Name is required</span>
                        </div>

                        <div class="mb-0">
                            <label for="mcm_add_status" class="mst-form-label d-block">
                                Status <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <select name="active_inactive" id="mcm_add_status" class="form-select mst-control mst-searchable"
                                    data-placeholder="Select Status" required aria-required="true">
                                <option value="">Select Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <span class="mst-field-error d-none" id="mcm_add_status_error">Status is required</span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 gap-2 justify-content-end">
                <button type="button" class="btn mst-btn-cancel px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn mst-btn-submit px-4" id="mcmAddSubmit">Save</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade mst-modal mcm-form-modal" id="mcmEditModal" tabindex="-1" aria-labelledby="mcmEditModalLabel"
     aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold mb-0" id="mcmEditModalLabel">Edit Medical Case</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="medicalCaseEditForm" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" id="mcm_edit_id" value="">

                    <div class="mst-field-card">
                        <div class="mb-3">
                            <label for="mcm_edit_case_name" class="mst-form-label d-block">
                                Case Name <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <input type="text" name="case_name" id="mcm_edit_case_name"
                                   class="form-control mst-control"
                                   placeholder="eg. IPD" autocomplete="off"
                                   required aria-required="true">
                            <span class="mst-field-error d-none" id="mcm_edit_case_name_error">Case Name is required</span>
                        </div>

                        <div class="mb-0">
                            <label for="mcm_edit_status" class="mst-form-label d-block">
                                Status <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <select name="status" id="mcm_edit_status" class="form-select mst-control mst-searchable"
                                    data-placeholder="Select Status" required aria-required="true">
                                <option value="">Select Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <span class="mst-field-error d-none" id="mcm_edit_status_error">Status is required</span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 gap-2 justify-content-end">
                <button type="button" class="btn mst-btn-cancel px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn mst-btn-submit px-4" id="mcmEditSubmit">Update</button>
            </div>
        </div>
    </div>
</div>

{{-- Column Visibility --}}
<div class="modal fade" id="mcmColumnVisibilityModal" tabindex="-1"
     aria-labelledby="mcmColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="mcmColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3 mst-colvis-grid" id="mcmColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="pk" value="">
<input type="hidden" id="active_inactive" value="">
@endsection

@push('scripts')
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
<script>
    $(function() {
        const tableSelector = '#medicalCaseMasterTable';
        const storeUrl = "{{ route('master.medical.case.master.store') }}";
        const csrfToken = "{{ csrf_token() }}";
        let table;

        const mcmAddModalEl = document.getElementById('mcmAddModal');
        const mcmEditModalEl = document.getElementById('mcmEditModal');

        document.querySelectorAll('.mcm-form-modal').forEach(function(modalEl) {
            if (modalEl.parentElement && modalEl.parentElement !== document.body) {
                document.body.appendChild(modalEl);
            }
        });

        function showMcmModal(modalEl) {
            if (!modalEl) {
                return;
            }
            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            } else if (window.jQuery) {
                $(modalEl).modal('show');
            }
        }

        function hideMcmModal(modalEl) {
            if (!modalEl) {
                return;
            }
            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            } else if (window.jQuery) {
                $(modalEl).modal('hide');
            }
        }

        function resetMcmAddForm() {
            const $form = $('#medicalCaseForm');
            $form.find('#mcm_add_case_name').val('').removeClass('is-invalid');
            $form.find('#mcm_add_status').val('').removeClass('is-invalid').trigger('change.select2');
            $form.find('.mst-field-error').addClass('d-none');
        }

        if (mcmAddModalEl) {
            mcmAddModalEl.addEventListener('show.bs.modal', function() {
                resetMcmAddForm();
            });
            mcmAddModalEl.addEventListener('shown.bs.modal', function() {
                $('#mcm_add_case_name').trigger('focus');
            });
        }

        /* ---------- Row markup ----------
         * The feed (MedicalCaseMasterController@datatable) still
         * returns its old `status` / `action` HTML; the cells are rebuilt here
         * from the row data with the exact markup of
         * admin/master/partials/grid-status and grid-actions. The switch keeps
         * this page's own hook (.plain-status-toggle + data-id) instead of
         * .status-toggle, so the global custom.js toggle handler does not fire
         * as well. */
        // Yajra HTML-escapes every string in the JSON (escape '*'); undo that
        // before a value goes into an attribute or it is escaped twice.
        function mcmDecode(value) {
            const el = document.createElement('textarea');
            el.innerHTML = value == null ? '' : String(value);
            return el.value;
        }

        function mcmStatusBadge(row) {
            const isActive = String(row.active_inactive) === '1';
            return $('<span></span>')
                .attr('class', 'status-pill badge rounded-1 ' + (isActive ? 'bg-success-subtle' : 'bg-danger-subtle'))
                .text(isActive ? 'Active' : 'Inactive')
                .prop('outerHTML');
        }

        function mcmActStack(cls, icon, label, title) {
            return $('<button type="button"></button>').attr({ 'class': 'mst-act ' + cls, title: title })
                .append($('<span class="mst-act__icon"></span>').append($('<i aria-hidden="true"></i>').addClass('bi ' + icon)))
                .append($('<span class="mst-act__label"></span>').text(label));
        }

        function mcmActions(row) {
            const isActive = String(row.active_inactive) === '1';
            const name = mcmDecode(row.case_name);
            const $group = $('<div class="mst-act-group" role="group" aria-label="Row actions"></div>');

            $group.append(
                mcmActStack('mst-act--edit edit-btn', 'bi-pencil', 'Edit', $.trim('Edit ' + name)).attr({
                    'data-id': row.pk,
                    'data-case_name': name,
                    'data-active_inactive': row.active_inactive
                })
            );

            const verb = isActive ? 'Deactivate' : 'Activate';
            $group.append(
                $('<label class="mst-act mst-act--toggle"></label>')
                    .append($('<span class="mst-act__icon"></span>').append(
                        $('<input class="form-check-input plain-status-toggle" type="checkbox" role="switch">')
                            .attr({ 'data-id': row.pk, 'aria-label': $.trim(verb + ' ' + name) })
                            .attr('checked', isActive ? 'checked' : null)
                    ))
                    .append($('<span class="mst-act__label"></span>').text(verb))
            );

            if (isActive) {
                // Same rule the page always had: an active row can't be deleted.
                const reason = 'Active records cannot be deleted. Deactivate it first.';
                $group.append(
                    $('<span class="mst-act mst-act--del is-disabled" aria-disabled="true" tabindex="0"></span>')
                        .attr('title', reason)
                        .append('<span class="mst-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>')
                        .append('<span class="mst-act__label">Delete</span>')
                        .append($('<span class="visually-hidden"></span>').text(reason))
                );
            } else {
                $group.append(
                    mcmActStack('mst-act--del delete-btn', 'bi-trash', 'Delete', $.trim('Delete ' + name))
                        .attr('data-id', row.pk)
                );
            }

            return $group.prop('outerHTML');
        }

        if ($.fn.DataTable.isDataTable(tableSelector)) {
            table = $(tableSelector).DataTable();
        } else {
            table = $(tableSelector).DataTable({
                processing: true,
                serverSide: true,
                searching: true,
                searchDelay: 400,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
                order: [[0, 'desc']],
                ajax: {
                    url: "{{ route('master.medical.case.master.datatable') }}",
                    data: function(d) {
                        d.pk = $('#pk').val();
                        d.active_inactive = $('#active_inactive').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'case_name',
                        name: 'case_name'
                    },
                    {
                        data: 'created_date',
                        name: 'created_date'
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return type === 'display' ? mcmStatusBadge(row) : row.active_inactive;
                        }
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return type === 'display' ? mcmActions(row) : '';
                        }
                    }
                ],
                columnDefs: [
                    { targets: [0, 2, 3, 4], className: 'text-nowrap' }
                ],
                language: {
                    processing: '<span class="spinner-border spinner-border-sm text-primary me-2" role="status" aria-hidden="true"></span>Loading…',
                    emptyTable: 'No medical cases found.',
                    zeroRecords: 'No matching medical cases found.'
                }
            });
        }

        MstAdmin.columnVisibility({
            table: tableSelector,
            grid: '#mcmColumnToggleGrid',
            storageKey: 'sargam.medicalCaseMaster.hiddenCols.{{ auth()->id() ?? 'guest' }}'
        });

        $('#mcmAddSubmit').on('click', function() {
            const $form = $('#medicalCaseForm');
            const name = $form.find('#mcm_add_case_name');
            const status = $form.find('#mcm_add_status');

            let isValid = true;
            $form.find('.mst-field-error').addClass('d-none');
            name.removeClass('is-invalid');
            status.removeClass('is-invalid');

            if (!name.val().trim()) {
                $form.find('#mcm_add_case_name_error').removeClass('d-none');
                name.addClass('is-invalid').focus();
                isValid = false;
            } else if (!status.val()) {
                $form.find('#mcm_add_status_error').removeClass('d-none');
                status.addClass('is-invalid').focus();
                isValid = false;
            }

            if (!isValid) {
                return;
            }

            fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    case_name: name.val().trim(),
                    status: status.val()
                })
            })
            .then(function(response) {
                if (!response.ok) {
                    return response.json().then(function(err) {
                        throw err;
                    });
                }
                return response.json();
            })
            .then(function(result) {
                if (result.status !== false) {
                    hideMcmModal(mcmAddModalEl);
                    resetMcmAddForm();
                    table.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved!',
                        text: result.message || 'Medical case added successfully',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            })
            .catch(function(err) {
                if (err && err.errors) {
                    if (err.errors.case_name) {
                        $form.find('#mcm_add_case_name_error').text(err.errors.case_name[0]).removeClass('d-none');
                        name.addClass('is-invalid');
                    }
                    if (err.errors.status) {
                        $form.find('#mcm_add_status_error').text(err.errors.status[0]).removeClass('d-none');
                        status.addClass('is-invalid');
                    }
                } else {
                    Swal.fire('Error', (err && err.message) ? err.message : 'Server error or session expired', 'error');
                }
            });
        });

        $(document).on('click', '.edit-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();

            // attr(), not data(): jQuery's data() turns a name like "1e3" into a number.
            const id = $(this).attr('data-id');
            const caseName = $(this).attr('data-case_name');
            const status = $(this).attr('data-active_inactive');

            const $form = $('#medicalCaseEditForm');
            $form.find('#mcm_edit_id').val(id);
            $form.find('#mcm_edit_case_name').val(caseName || '');
            $form.find('#mcm_edit_status')
                .val(status === '0' ? '0' : (status === '1' ? '1' : ''))
                .trigger('change.select2');
            $form.find('.mst-field-error').addClass('d-none');
            $form.find('.form-control, .form-select').removeClass('is-invalid');

            showMcmModal(mcmEditModalEl);

            if (mcmEditModalEl) {
                mcmEditModalEl.addEventListener('shown.bs.modal', function onShown() {
                    $form.find('#mcm_edit_case_name').trigger('focus');
                    mcmEditModalEl.removeEventListener('shown.bs.modal', onShown);
                });
            }
        });

        $('#mcmEditSubmit').on('click', function() {
            const $form = $('#medicalCaseEditForm');
            const id = $form.find('#mcm_edit_id').val();
            const name = $form.find('#mcm_edit_case_name');
            const status = $form.find('#mcm_edit_status');

            let isValid = true;
            $form.find('.mst-field-error').addClass('d-none');
            name.removeClass('is-invalid');
            status.removeClass('is-invalid');

            if (!name.val().trim()) {
                $form.find('#mcm_edit_case_name_error').removeClass('d-none');
                name.addClass('is-invalid').focus();
                isValid = false;
            } else if (!status.val()) {
                $form.find('#mcm_edit_status_error').removeClass('d-none');
                status.addClass('is-invalid').focus();
                isValid = false;
            }

            if (!isValid) {
                return;
            }

            $.ajax({
                url: storeUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: csrfToken,
                    id: id,
                    case_name: name.val().trim(),
                    status: status.val()
                }
            })
            .done(function(result) {
                if (result.status) {
                    hideMcmModal(mcmEditModalEl);
                    table.ajax.reload(null, false);
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        text: result.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            })
            .fail(function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    if (errors.case_name) {
                        $form.find('#mcm_edit_case_name_error').text(errors.case_name[0]).removeClass('d-none');
                        name.addClass('is-invalid');
                    }
                    if (errors.status) {
                        $form.find('#mcm_edit_status_error').text(errors.status[0]).removeClass('d-none');
                        status.addClass('is-invalid');
                    }
                } else {
                    Swal.fire('Error', 'Something went wrong!', 'error');
                }
            });
        });

        $(document).on('change', '.plain-status-toggle', function() {
            var checkbox = $(this);
            var pk = checkbox.data('id');
            var active_inactive = checkbox.is(':checked') ? 1 : 0;
            var actionText = active_inactive ? 'activate' : 'deactivate';
            var confirmBtnText = active_inactive ? 'Yes, activate' : 'Yes, deactivate';
            var confirmBtnColor = active_inactive ? '#28a745' : '#d33';

            Swal.fire({
                title: 'Are you sure?',
                text: 'Are you sure? You want to ' + actionText + ' this item?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: confirmBtnColor,
                cancelButtonColor: '#3085d6',
                confirmButtonText: confirmBtnText,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#pk').val(pk);
                    $('#active_inactive').val(active_inactive);
                    table.ajax.reload(function() {
                        $('#pk').val('');
                        $('#active_inactive').val('');
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: 'Status has been updated successfully.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }, false);
                    return;
                }
                // Nothing was saved — put the switch back whatever closed the
                // dialog (Cancel, Esc or a backdrop click).
                checkbox.prop('checked', !active_inactive);
                if (result.dismiss === Swal.DismissReason.cancel) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Cancelled',
                        text: 'Status change has been cancelled.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            });
        });

        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            if ($(this).attr('aria-disabled') === 'true' || $(this).hasClass('disabled')) {
                return;
            }

            let pk = $(this).data('id');
            Swal.fire({
                title: 'Are you sure?',
                text: "This record will be permanently deleted!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#pk').val(pk);
                    $('#active_inactive').val(2);
                    table.ajax.reload(function() {
                        // Don't re-send the delete with every later draw.
                        $('#pk').val('');
                        $('#active_inactive').val('');
                    }, false);
                    Swal.fire({
                        icon: 'success',
                        title: 'Delete!',
                        text: 'Delete has been successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Cancelled',
                        text: 'Delete has been cancelled.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            });
        });
    });
</script>
@endpush
