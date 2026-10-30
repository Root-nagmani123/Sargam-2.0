@extends('admin.layouts.master')

@section('title', 'Exemption medical speciality')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid mst-page ems-page">
    <x-breadcrum title="Exemption medical speciality" :showBack="false">
        <button type="button" id="showAlert"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                data-bs-toggle="modal" data-bs-target="#emsAddModal" aria-controls="emsAddModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Exemption Medical Speciality</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="emsColumnsToggle"
                            data-bs-toggle="modal" data-bs-target="#emsColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div class="programme-dt-search" data-dt-search-for="exemptionMedicalSpecialityTable"></div>
                </div>
            </div>

            {{-- Search, pager and "Showing N of M items" are relocated into the
                 slots by public/js/datatable-global-ui.js. --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="exemptionMedicalSpecialityTable">
                        <caption class="visually-hidden">Exemption medical specialities</caption>
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Speciality Name</th>
                                <th scope="col">Created Date</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="exemptionMedicalSpecialityTable"></div>
            </div>

        </div>
    </div>
</div>

{{-- Add / Edit modals (appended to body on load for correct stacking) --}}
<div class="modal fade mst-modal ems-form-modal" id="emsAddModal" tabindex="-1" aria-labelledby="emsAddModalLabel"
     aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold mb-0" id="emsAddModalLabel">Add Exemption Medical Speciality</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="exemptionCategoryForm" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">

                    <div class="mst-field-card">
                        <div class="mb-3">
                            <label for="ems_add_speciality_name" class="mst-form-label d-block">
                                Speciality Name <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <input type="text" name="speciality_name" id="ems_add_speciality_name"
                                   class="form-control mst-control"
                                   placeholder="eg. General Medicine" autocomplete="off"
                                   required aria-required="true">
                            <span class="mst-field-error d-none" id="ems_add_speciality_name_error">Speciality Name is required</span>
                        </div>

                        <div class="mb-0">
                            <label for="ems_add_status" class="mst-form-label d-block">
                                Status <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <select name="active_inactive" id="ems_add_status" class="form-select mst-control mst-searchable"
                                    data-placeholder="Select Status" required aria-required="true">
                                <option value="">Select Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <span class="mst-field-error d-none" id="ems_add_status_error">Status is required</span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 gap-2 justify-content-end">
                <button type="button" class="btn mst-btn-cancel px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn mst-btn-submit px-4" id="emsAddSubmit">Save</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade mst-modal ems-form-modal" id="emsEditModal" tabindex="-1" aria-labelledby="emsEditModalLabel"
     aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold mb-0" id="emsEditModalLabel">Edit Exemption Medical Speciality</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="exemptionCategoryeditForm" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" id="id" value="">

                    <div class="mst-field-card">
                        <div class="mb-3">
                            <label for="ems_edit_speciality_name" class="mst-form-label d-block">
                                Speciality Name <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <input type="text" name="speciality_name" id="ems_edit_speciality_name"
                                   class="form-control mst-control"
                                   placeholder="eg. General Medicine" autocomplete="off"
                                   required aria-required="true">
                            <span class="mst-field-error d-none" id="ems_edit_speciality_name_error">Speciality Name is required</span>
                        </div>

                        <div class="mb-0">
                            <label for="ems_edit_status" class="mst-form-label d-block">
                                Status <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <select name="status" id="ems_edit_status" class="form-select mst-control mst-searchable"
                                    data-placeholder="Select Status" required aria-required="true">
                                <option value="">Select Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <span class="mst-field-error d-none" id="ems_edit_status_error">Status is required</span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 gap-2 justify-content-end">
                <button type="button" class="btn mst-btn-cancel px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn mst-btn-submit px-4" id="emsEditSubmit">Update</button>
            </div>
        </div>
    </div>
</div>

{{-- Column Visibility --}}
<div class="modal fade" id="emsColumnVisibilityModal" tabindex="-1"
     aria-labelledby="emsColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="emsColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3 mst-colvis-grid" id="emsColumnToggleGrid"></div>
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
        const tableSelector = '#exemptionMedicalSpecialityTable';
        const storeUrl = "{{ route('master.exemption.medical.speciality.store') }}";
        const csrfToken = "{{ csrf_token() }}";
        let table;

        const emsAddModalEl = document.getElementById('emsAddModal');
        const emsEditModalEl = document.getElementById('emsEditModal');

        document.querySelectorAll('.ems-form-modal').forEach(function(modalEl) {
            if (modalEl.parentElement && modalEl.parentElement !== document.body) {
                document.body.appendChild(modalEl);
            }
        });

        function showEmsModal(modalEl) {
            if (!modalEl) {
                return;
            }
            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            } else if (window.jQuery) {
                $(modalEl).modal('show');
            }
        }

        function hideEmsModal(modalEl) {
            if (!modalEl) {
                return;
            }
            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            } else if (window.jQuery) {
                $(modalEl).modal('hide');
            }
        }

        function resetEmsAddForm() {
            const $form = $('#exemptionCategoryForm');
            $form.find('#ems_add_speciality_name').val('').removeClass('is-invalid');
            $form.find('#ems_add_status').val('').removeClass('is-invalid').trigger('change.select2');
            $form.find('.mst-field-error').addClass('d-none');
        }

        if (emsAddModalEl) {
            emsAddModalEl.addEventListener('show.bs.modal', function() {
                resetEmsAddForm();
            });
            emsAddModalEl.addEventListener('shown.bs.modal', function() {
                $('#ems_add_speciality_name').trigger('focus');
            });
        }

        /* ---------- Row markup ----------
         * The feed (ExemptionCategoryController@exemption_med_spec_mst) still
         * returns its old `status` / `action` HTML; the cells are rebuilt here
         * from the row data with the exact markup of
         * admin/master/partials/grid-status and grid-actions. The switch keeps
         * this page's own hook (.plain-status-toggle + data-id) instead of
         * .status-toggle, so the global custom.js toggle handler does not fire
         * as well. */
        // Yajra HTML-escapes every string in the JSON (escape '*'); undo that
        // before a value goes into an attribute or it is escaped twice.
        function emsDecode(value) {
            const el = document.createElement('textarea');
            el.innerHTML = value == null ? '' : String(value);
            return el.value;
        }

        function emsStatusBadge(row) {
            const isActive = String(row.active_inactive) === '1';
            return $('<span></span>')
                .attr('class', 'status-pill badge rounded-1 ' + (isActive ? 'bg-success-subtle' : 'bg-danger-subtle'))
                .text(isActive ? 'Active' : 'Inactive')
                .prop('outerHTML');
        }

        function emsActStack(cls, icon, label, title) {
            return $('<button type="button"></button>').attr({ 'class': 'mst-act ' + cls, title: title })
                .append($('<span class="mst-act__icon"></span>').append($('<i aria-hidden="true"></i>').addClass('bi ' + icon)))
                .append($('<span class="mst-act__label"></span>').text(label));
        }

        function emsActions(row) {
            const isActive = String(row.active_inactive) === '1';
            const name = emsDecode(row.speciality_name);
            const $group = $('<div class="mst-act-group" role="group" aria-label="Row actions"></div>');

            $group.append(
                emsActStack('mst-act--edit edit-btn', 'bi-pencil', 'Edit', $.trim('Edit ' + name)).attr({
                    'data-id': row.pk,
                    'data-speciality_name': name,
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
                    emsActStack('mst-act--del delete-btn', 'bi-trash', 'Delete', $.trim('Delete ' + name))
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
                    url: "{{ route('master.exemption.medical.speciality.exemption_med_spec_mst') }}",
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
                        data: 'speciality_name',
                        name: 'speciality_name'
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
                            return type === 'display' ? emsStatusBadge(row) : row.active_inactive;
                        }
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return type === 'display' ? emsActions(row) : '';
                        }
                    }
                ],
                columnDefs: [
                    { targets: [0, 2, 3, 4], className: 'text-nowrap' }
                ],
                language: {
                    processing: '<span class="spinner-border spinner-border-sm text-primary me-2" role="status" aria-hidden="true"></span>Loading…',
                    emptyTable: 'No medical specialities found.',
                    zeroRecords: 'No matching medical specialities found.'
                }
            });
        }

        MstAdmin.columnVisibility({
            table: tableSelector,
            grid: '#emsColumnToggleGrid',
            storageKey: 'sargam.exemptionMedicalSpeciality.hiddenCols.{{ auth()->id() ?? 'guest' }}'
        });

        $('#emsAddSubmit').on('click', function() {
            const $form = $('#exemptionCategoryForm');
            const name = $form.find('#ems_add_speciality_name');
            const status = $form.find('#ems_add_status');

            let isValid = true;
            $form.find('.mst-field-error').addClass('d-none');
            name.removeClass('is-invalid');
            status.removeClass('is-invalid');

            if (!name.val().trim()) {
                $form.find('#ems_add_speciality_name_error').removeClass('d-none');
                name.addClass('is-invalid').focus();
                isValid = false;
            } else if (!status.val()) {
                $form.find('#ems_add_status_error').removeClass('d-none');
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
                    speciality_name: name.val().trim(),
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
                    hideEmsModal(emsAddModalEl);
                    resetEmsAddForm();
                    table.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved!',
                        text: result.message || 'Exemption medical speciality added successfully',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            })
            .catch(function(err) {
                if (err && err.errors) {
                    if (err.errors.speciality_name) {
                        $form.find('#ems_add_speciality_name_error').text(err.errors.speciality_name[0]).removeClass('d-none');
                        name.addClass('is-invalid');
                    }
                    if (err.errors.status) {
                        $form.find('#ems_add_status_error').text(err.errors.status[0]).removeClass('d-none');
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
            const specialityName = $(this).attr('data-speciality_name');
            const status = $(this).attr('data-active_inactive');

            const $form = $('#exemptionCategoryeditForm');
            $form.find('#id').val(id);
            $form.find('#ems_edit_speciality_name').val(specialityName || '');
            $form.find('#ems_edit_status')
                .val(status === '0' ? '0' : (status === '1' ? '1' : ''))
                .trigger('change.select2');
            $form.find('.mst-field-error').addClass('d-none');
            $form.find('.form-control, .form-select').removeClass('is-invalid');

            showEmsModal(emsEditModalEl);

            if (emsEditModalEl) {
                emsEditModalEl.addEventListener('shown.bs.modal', function onShown() {
                    $form.find('#ems_edit_speciality_name').trigger('focus');
                    emsEditModalEl.removeEventListener('shown.bs.modal', onShown);
                });
            }
        });

        $('#emsEditSubmit').on('click', function() {
            const $form = $('#exemptionCategoryeditForm');
            const id = $form.find('#id').val();
            const name = $form.find('#ems_edit_speciality_name');
            const status = $form.find('#ems_edit_status');

            let isValid = true;
            $form.find('.mst-field-error').addClass('d-none');
            name.removeClass('is-invalid');
            status.removeClass('is-invalid');

            if (!name.val().trim()) {
                $form.find('#ems_edit_speciality_name_error').removeClass('d-none');
                name.addClass('is-invalid').focus();
                isValid = false;
            } else if (!status.val()) {
                $form.find('#ems_edit_status_error').removeClass('d-none');
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
                    speciality_name: name.val().trim(),
                    status: status.val()
                }
            })
            .done(function(result) {
                if (result.status) {
                    hideEmsModal(emsEditModalEl);
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
                    if (errors.speciality_name) {
                        $form.find('#ems_edit_speciality_name_error').text(errors.speciality_name[0]).removeClass('d-none');
                        name.addClass('is-invalid');
                    }
                    if (errors.status) {
                        $form.find('#ems_edit_status_error').text(errors.status[0]).removeClass('d-none');
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
