@extends('admin.layouts.master')

@section('title', 'Exemption medical speciality')

@push('styles')
{{-- Shared with Exemption Categories: same controller, same design
     (docs/new-design-index-page.md §7). This page used to carry the same rules
     in an inline <style> block. --}}
<link rel="stylesheet"
    href="{{ asset('css/exemption-masters-admin.css') }}?v={{ @filemtime(public_path('css/exemption-masters-admin.css')) }}">
@endpush

@section('setup_content')
<div class="container-fluid ems-master-page">
    <x-breadcrum title="Exemption medical speciality">
        <button type="button"
            id="showAlert"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-2 fw-semibold shadow-sm"
            data-bs-toggle="modal"
            data-bs-target="#emsAddModal"
            aria-controls="emsAddModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Exemption medical speciality</span>
        </button>
    </x-breadcrum>

    <div class="card ems-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            {{-- Toolbar (§2). The search is the shared .programme-dt-search
                 markup — the structure datatable-global-ui.js would relocate into
                 that slot — so the skin matches every other index page even though
                 this table drives its own server-side search. --}}
            <div class="ems-toolbar programme-dt-toolbar d-flex flex-wrap align-items-center justify-content-end gap-2 mb-4">
                <button type="button" class="btn programme-dt-btn-columns" id="emsColumnsToggle"
                    data-bs-toggle="modal" data-bs-target="#emsColumnsModal"
                    title="Show / hide columns">
                    <span>Columns</span>
                    <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                </button>
                <div class="programme-dt-search">
                    <div class="dataTables_filter">
                        <label>
                            <input type="search" id="emsTableSearch" class="form-control form-control-sm"
                                placeholder="Search" autocomplete="off" aria-label="Search medical specialities">
                        </label>
                    </div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="exemptionMedicalSpecialityTable">
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap" style="width: 5.5rem;">S. No.</th>
                                <th scope="col">Speciality Name</th>
                                <th scope="col" class="text-center text-nowrap" style="width: 8.5rem;">Created Date</th>
                                <th scope="col" class="text-center text-nowrap" style="width: 7.5rem;">Status</th>
                                <th scope="col" class="text-center text-nowrap" style="width: 10.5rem;">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Exemption Medical Speciality -->
<div class="modal fade exm-modal ems-form-modal" id="emsAddModal" tabindex="-1" aria-labelledby="emsAddModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="exm-modal-header">
                <h5 class="exm-modal-title" id="emsAddModalLabel">Add Exemption Medical Speciality</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="exm-modal-body">
                <form id="exemptionCategoryForm" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">

                    <div class="exm-field-card">
                        <div class="exm-field">
                            <label for="ems_add_speciality_name" class="exm-field-label">
                                Speciality Name <span class="exm-req">*</span>
                            </label>
                            <input type="text"
                                   name="speciality_name"
                                   id="ems_add_speciality_name"
                                   class="exm-control"
                                   placeholder="eg. General Medicine"
                                   maxlength="100"
                                   autocomplete="off">
                            <small class="text-danger d-none mt-1" id="ems_add_speciality_name_error">Speciality Name is required</small>
                        </div>

                        <div class="exm-field">
                            <label for="ems_add_status" class="exm-field-label">
                                Status <span class="exm-req">*</span>
                            </label>
                            <select name="active_inactive" id="ems_add_status" class="exm-control">
                                <option value="">Select Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <small class="text-danger d-none mt-1" id="ems_add_status_error">Status is required</small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="exm-modal-footer">
                <button type="button" class="btn exm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn exm-btn-submit" id="emsAddSubmit">Add Medical Speciality</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Exemption Medical Speciality -->
<div class="modal fade exm-modal ems-form-modal" id="emsEditModal" tabindex="-1" aria-labelledby="emsEditModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="exm-modal-header">
                <h5 class="exm-modal-title" id="emsEditModalLabel">Edit Exemption Medical Speciality</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="exm-modal-body">
                <form id="exemptionCategoryeditForm" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" id="id" value="">

                    <div class="exm-field-card">
                        <div class="exm-field">
                            <label for="ems_edit_speciality_name" class="exm-field-label">
                                Speciality Name <span class="exm-req">*</span>
                            </label>
                            <input type="text"
                                   name="speciality_name"
                                   id="ems_edit_speciality_name"
                                   class="exm-control"
                                   placeholder="eg. General Medicine"
                                   maxlength="100"
                                   autocomplete="off">
                            <small class="text-danger d-none mt-1" id="ems_edit_speciality_name_error">Speciality Name is required</small>
                        </div>

                        <div class="exm-field">
                            <label for="ems_edit_status" class="exm-field-label">
                                Status <span class="exm-req">*</span>
                            </label>
                            <select name="status" id="ems_edit_status" class="exm-control">
                                <option value="">Select Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <small class="text-danger d-none mt-1" id="ems_edit_status_error">Status is required</small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="exm-modal-footer">
                <button type="button" class="btn exm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn exm-btn-submit" id="emsEditSubmit">Update Medical Speciality</button>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility -->
<div class="modal fade exm-modal" id="emsColumnsModal" tabindex="-1" aria-labelledby="emsColumnsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="exm-modal-header">
                <h5 class="exm-modal-title" id="emsColumnsModalLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="exm-modal-body">
                <div class="exm-col-grid" id="emsColumnsGrid"></div>
            </div>
            <div class="exm-modal-footer">
                <button type="button" class="btn exm-btn-cancel" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="pk" value="">
<input type="hidden" id="active_inactive" value="">
@endsection

@push('scripts')
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
            $form.find('#ems_add_status').val('');
            $form.find('small.text-danger').addClass('d-none');
        }

        if (emsAddModalEl) {
            emsAddModalEl.addEventListener('show.bs.modal', function() {
                resetEmsAddForm();
            });
            emsAddModalEl.addEventListener('shown.bs.modal', function() {
                $('#ems_add_speciality_name').trigger('focus');
            });
        }

        // The status badge and the Edit · switch · Delete group are rendered
        // server-side now (ExemptionCategoryController::exemption_med_spec_mst,
        // §3b). This used to move the switch out of the Status cell into the
        // action group, rebuild the badge and strip the button captions on every
        // draw — and stripping those captions is what left Edit and Delete as
        // blank squares, because the material-icon glyph they wrapped computes
        // to display:none on this layout. Kept as a no-op so the DataTables
        // callbacks below keep their shape.
        function decorateEmsRows() {}
        // Only used when the user CANCELS the confirm dialog: the switch has
        // already flipped visually, so the badge and the caption are put back in
        // step with it. A confirmed change reloads the table, which re-renders
        // both from the server.
        function updateEmsRowBadge($checkbox, isActive) {
            const $badge = $checkbox.closest('tr').find('.ems-status-badge');
            if ($badge.length) {
                $badge
                    .removeClass('bg-success-subtle bg-danger-subtle')
                    .addClass(isActive ? 'bg-success-subtle' : 'bg-danger-subtle')
                    .text(isActive ? 'Active' : 'Inactive');
            }
            // The caption names the ACTION, so it is the inverse of the state.
            $checkbox.attr('aria-label', (isActive ? 'Deactivate' : 'Activate') + ' speciality')
                     .closest('.exm-act--toggle')
                     .attr('title', isActive ? 'Deactivate' : 'Activate')
                     .find('.exm-act__label')
                     .text(isActive ? 'Deactivate' : 'Activate');
        }

        if ($.fn.DataTable.isDataTable(tableSelector)) {
            table = $(tableSelector).DataTable();
        } else {
            table = $(tableSelector).DataTable({
                processing: true,
                serverSide: true,
                searching: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
                order: [[0, 'desc']],
                // ‹ 1 2 3 › — First/Last are not part of the shared footer (§4).
                pagingType: 'simple_numbers',
                // Bottom bar = pagination (left) + "Showing [n] of N items"
                // (right). The row wears .programme-dt-footer (§4);
                // datatable-global-ui.js then fills the two slots and applies its
                // own .programme-dt-pagination / .programme-dt-count classes.
                // ⚠️ No Bootstrap row/col here: datatable-global-ui.js rewrites
                // the className of both slots when it fills them, which strips
                // any col-* and leaves the two halves stacked. A plain flex
                // container survives that (§4).
                dom: "<'row'<'col-12'tr>>" +
                     "<'programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3'" +
                         "<'programme-dt-pagination'p>" +
                         "<'programme-dt-count d-flex align-items-center gap-2'li>" +
                     ">",
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
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                columnDefs: [{
                        targets: 0,
                        className: 'text-nowrap'
                    },
                    {
                        targets: 2,
                        className: 'text-center text-nowrap'
                    },
                    {
                        targets: 3,
                        className: 'text-center'
                    },
                    {
                        targets: 4,
                        className: 'text-center'
                    }
                ],
                language: {
                    lengthMenu: 'Showing _MENU_',
                    info: 'of _TOTAL_ items',
                    infoEmpty: 'of 0 items',
                    infoFiltered: '',
                    processing: '<span class="spinner-border spinner-border-sm text-primary me-2" role="status" aria-hidden="true"></span>Loading…',
                    emptyTable: 'No medical specialities found.',
                    zeroRecords: 'No matching medical specialities found.',
                    paginate: {
                        previous: '<span aria-hidden="true">&lsaquo;</span>',
                        next: '<span aria-hidden="true">&rsaquo;</span>'
                    }
                },
                initComplete: function() {
                    decorateEmsRows();
                },
                drawCallback: function() {
                    decorateEmsRows();
                }
            });
        }

        // 🔍 Toolbar search → server-side global search
        var emsSearchTimer;
        $('#emsTableSearch').on('keyup', function() {
            var value = this.value;
            clearTimeout(emsSearchTimer);
            emsSearchTimer = setTimeout(function() {
                table.search(value).draw();
            }, 400);
        });

        // 🧱 Column Visibility modal (chips built from the live DataTable)
        var $emsColGrid = $('#emsColumnsGrid');
        table.columns().every(function(idx) {
            var title = $.trim($(this.header()).text()) || ('Column ' + (idx + 1));
            var visible = this.visible();
            $emsColGrid.append(
                '<label class="exm-col-chip' + (visible ? ' is-checked' : '') + '" for="emsColToggle' + idx + '">' +
                    '<input class="form-check-input ems-col-toggle" type="checkbox" ' + (visible ? 'checked ' : '') +
                           'id="emsColToggle' + idx + '" data-column="' + idx + '">' +
                    '<span>' + title + '</span>' +
                '</label>'
            );
        });
        $emsColGrid.on('change', '.ems-col-toggle', function() {
            table.column($(this).data('column')).visible(this.checked);
            $(this).closest('.exm-col-chip').toggleClass('is-checked', this.checked);
        });

        $('#emsAddSubmit').on('click', function() {
            const $form = $('#exemptionCategoryForm');
            const name = $form.find('#ems_add_speciality_name');
            const status = $form.find('#ems_add_status');

            let isValid = true;
            $form.find('small.text-danger').addClass('d-none');
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

            const id = $(this).data('id');
            const specialityName = $(this).data('speciality_name');
            const status = $(this).data('active_inactive');

            const $form = $('#exemptionCategoryeditForm');
            $form.find('#id').val(id);
            $form.find('#ems_edit_speciality_name').val(specialityName || '');
            $form.find('#ems_edit_status').val(
                status === 0 || status === '0' ? '0' : (status === 1 || status === '1' ? '1' : '')
            );
            $form.find('small.text-danger').addClass('d-none');
            $form.find('.exm-control').removeClass('is-invalid');

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
            $form.find('small.text-danger').addClass('d-none');
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
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    checkbox.prop('checked', !active_inactive);
                    updateEmsRowBadge(checkbox, !active_inactive);
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

        // An active row renders Delete as an inert <span> without .delete-btn, so
        // this handler cannot fire for it; the guard stays as a second line of
        // defence.
        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            if ($(this).attr('aria-disabled') === 'true' || $(this).hasClass('is-disabled')) {
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
                    table.ajax.reload(null, false);
                    Swal.fire({
                        icon: 'success',
                        title: 'Delete!',
                        text: 'Delete has been successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    Swal.fire({
                        icon: 'danger',
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

@if(session('success'))
<script>
    Swal.fire({
        icon: 'success',
        title: 'Success',
        text: "{{ session('success') }}"
    });
</script>
@endif

@if(session('error'))
<script>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: "{{ session('error') }}"
    });
</script>
@endif
@endpush
