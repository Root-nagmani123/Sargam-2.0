@extends('admin.layouts.master')

@section('title', 'MDO Duty Type')

@section('setup_content')
<div class="container-fluid mdt-master-page">
    <x-breadcrum title="MDO Duty Type">
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-2 fw-semibold shadow-sm add-btn"
            aria-controls="mdtDutyTypeModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add MDO Duty Type</span>
        </button>
    </x-breadcrum>

    <div class="card mdt-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div id="mdtDtSearch" class="programme-dt-search ms-lg-auto" data-dt-search-for="mdodutytypemaster-table"></div>
            </div>

            <div class="programme-dt-panel mdt-dt-scroll">
                {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                <div id="mdtDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="mdodutytypemaster-table"></div>
            </div>
        </div>
    </div>
</div>

<!-- Add / Edit MDO Duty Type (shared modal) -->
<div class="modal fade mdt-form-modal" id="mdtDutyTypeModal" tabindex="-1" aria-labelledby="mdtDutyTypeModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cgt-form-modal border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="mdtDutyTypeModalLabel">Add MDO Duty Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="AddMDODutyTypeForm" class="mdt-modal-form mdt-modal-form--add" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">

                    <div class="mb-3">
                        <label for="mdt_add_duty_type_name" class="form-label cgt-field-label mb-2">
                            Duty Type Name <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               name="mdo_duty_type_name"
                               id="mdt_add_duty_type_name"
                               class="form-control rounded-3"
                               placeholder="eg. General Medicine"
                               autocomplete="off">
                        <small class="text-danger d-none mt-1" id="mdt_add_duty_type_name_error">Duty Type Name is required</small>
                    </div>

                    <div class="mb-0">
                        <label for="mdt_add_active_inactive" class="form-label cgt-field-label mb-2">
                            Status <span class="text-danger">*</span>
                        </label>
                        <select name="active_inactive" id="mdt_add_active_inactive" class="form-select rounded-3">
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <small class="text-danger d-none mt-1" id="mdt_add_active_inactive_error">Status is required</small>
                    </div>
                </form>

                <form id="EditMDODutyTypeForm" class="mdt-modal-form mdt-modal-form--edit d-none" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" id="mdt_edit_id" value="">

                    <div class="mb-3">
                        <label for="mdt_edit_duty_type_name" class="form-label cgt-field-label mb-2">
                            Duty Type Name <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               name="mdo_duty_type_name"
                               id="mdt_edit_duty_type_name"
                               class="form-control rounded-3"
                               placeholder="eg. General Medicine"
                               autocomplete="off">
                        <small class="text-danger d-none mt-1" id="mdt_edit_duty_type_name_error">Duty Type Name is required</small>
                    </div>

                    <div class="mb-0">
                        <label for="mdt_edit_active_inactive" class="form-label cgt-field-label mb-2">
                            Status <span class="text-danger">*</span>
                        </label>
                        <select name="active_inactive" id="mdt_edit_active_inactive" class="form-select rounded-3">
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <small class="text-danger d-none mt-1" id="mdt_edit_active_inactive_error">Status is required</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-3 px-4" id="mdtFormSubmit">Add</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
    $(function() {
        const tableSelector = '#mdodutytypemaster-table';
        const storeUrl = "{{ route('master.mdo_duty_type.store') }}";
        const csrfToken = "{{ csrf_token() }}";

        const mdtModalEl = document.getElementById('mdtDutyTypeModal');
        let mdtModalMode = 'add';

        if (mdtModalEl && mdtModalEl.parentElement && mdtModalEl.parentElement !== document.body) {
            document.body.appendChild(mdtModalEl);
        }

        function showMdtModal() {
            if (!mdtModalEl) {
                return;
            }
            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(mdtModalEl).show();
            } else if (window.jQuery) {
                $(mdtModalEl).modal('show');
            }
        }

        function hideMdtModal() {
            if (!mdtModalEl) {
                return;
            }
            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(mdtModalEl).hide();
            } else if (window.jQuery) {
                $(mdtModalEl).modal('hide');
            }
        }

        function resetMdtAddForm() {
            const $form = $('#AddMDODutyTypeForm');
            $form.find('#mdt_add_duty_type_name').val('').removeClass('is-invalid');
            $form.find('#mdt_add_active_inactive').val('');
            $form.find('small.text-danger').addClass('d-none');
        }

        function openMdtModal(mode, data) {
            mdtModalMode = mode;
            const isAdd = mode === 'add';

            $('#mdtDutyTypeModalLabel').text(isAdd ? 'Add MDO Duty Type' : 'Edit MDO Duty Type');
            $('#mdtFormSubmit').text(isAdd ? 'Add' : 'Update');
            $('#AddMDODutyTypeForm').toggleClass('d-none', !isAdd);
            $('#EditMDODutyTypeForm').toggleClass('d-none', isAdd);

            if (isAdd) {
                resetMdtAddForm();
            } else {
                const $form = $('#EditMDODutyTypeForm');
                $form.find('#mdt_edit_id').val(data.id || '');
                $form.find('#mdt_edit_duty_type_name').val(data.name || '').removeClass('is-invalid');
                $form.find('#mdt_edit_active_inactive').val(
                    data.status === 0 || data.status === '0' ? '0' :
                    (data.status === 1 || data.status === '1' ? '1' : '')
                );
                $form.find('small.text-danger').addClass('d-none');
                $form.find('.form-control, .form-select').removeClass('is-invalid');
            }

            showMdtModal();
        }

        if (mdtModalEl) {
            mdtModalEl.addEventListener('shown.bs.modal', function() {
                if (mdtModalMode === 'add') {
                    $('#mdt_add_duty_type_name').trigger('focus');
                } else {
                    $('#mdt_edit_duty_type_name').trigger('focus');
                }
            });
        }

        function decorateMdtRows() {
            $(tableSelector + ' tbody tr').each(function() {
                const $row = $(this);
                const $cells = $row.find('td');
                if ($cells.length < 4) {
                    return;
                }

                const $statusCell = $cells.eq(2);
                const $actionCell = $cells.eq(3);
                const $toggle = $statusCell.find('.plain-status-toggle').add($actionCell.find('.plain-status-toggle')).first();

                if ($toggle.length) {
                    const isActive = $toggle.is(':checked');
                    const badgeClass = isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive';
                    const label = isActive ? 'Active' : 'Inactive';

                    const $switchWrap = $toggle.closest('.form-check');
                    const $actionGroup = $actionCell.find('.d-inline-flex[role="group"]');
                    const $editBtn = $actionGroup.find('.edit-btn').first();

                    if ($switchWrap.length && $actionGroup.length) {
                        $switchWrap.addClass('programme-action-switch m-0 d-inline-flex align-items-center');
                        if ($editBtn.length) {
                            $editBtn.after($switchWrap);
                        } else {
                            $actionGroup.prepend($switchWrap);
                        }
                    }

                    $statusCell.empty().append(
                        $('<span>', {
                            class: 'badge rounded-pill programme-status-badge mdt-status-badge ' + badgeClass,
                            text: label
                        })
                    );
                }

                $actionCell.find('.edit-btn').each(function() {
                    const $btn = $(this);
                    if (!$btn.find('.bi').length) {
                        $btn.find('.material-icons').remove();
                        $btn.find('span.d-none').remove();
                        $btn.append('<i class="bi bi-pencil" aria-hidden="true"></i>');
                    }
                });

                $actionCell.find('.delete-btn').each(function() {
                    const $btn = $(this);
                    if (!$btn.find('.bi').length) {
                        $btn.find('.material-icons').remove();
                        $btn.find('span.d-none').remove();
                        $btn.append('<i class="bi bi-trash3" aria-hidden="true"></i>');
                    }
                });
            });
        }

        function updateMdtRowBadge($checkbox, isActive) {
            const $badge = $checkbox.closest('tr').find('.mdt-status-badge');
            if ($badge.length) {
                $badge
                    .removeClass('programme-status-badge--active programme-status-badge--inactive')
                    .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
                    .text(isActive ? 'Active' : 'Inactive');
            }
        }

        function reloadMdtTable() {
            if ($.fn.DataTable.isDataTable(tableSelector)) {
                $(tableSelector).DataTable().ajax.reload(null, false);
            }
        }

        $(tableSelector).on('draw.dt', decorateMdtRows);

        $(tableSelector).on('init.dt', function() {
            const api = $(tableSelector).DataTable();
            if (api.settings()[0].oScroll.sX) {
                api.columns.adjust();
            }
        });

        if ($.fn.DataTable.isDataTable(tableSelector)) {
            decorateMdtRows();
            $(tableSelector).DataTable().columns.adjust();
        }

        $(document).on('change', '.plain-status-toggle', function() {
            const checkbox = $(this);
            const pk = checkbox.data('id');
            const active_inactive = checkbox.is(':checked') ? 1 : 0;
            const actionText = active_inactive ? 'activate' : 'deactivate';
            const confirmBtnText = active_inactive ? 'Yes, activate' : 'Yes, deactivate';
            const confirmBtnColor = active_inactive ? '#28a745' : '#d33';

            Swal.fire({
                title: 'Are you sure?',
                text: 'Are you sure you want to ' + actionText + ' this item?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: confirmBtnColor,
                cancelButtonColor: '#3085d6',
                confirmButtonText: confirmBtnText,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('master.mdo_duty_type.status') }}",
                        type: 'POST',
                        data: {
                            pk: pk,
                            active_inactive: active_inactive,
                            _token: csrfToken
                        },
                        success: function(response) {
                            reloadMdtTable();
                            Swal.fire({
                                icon: 'success',
                                title: 'Updated!',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                        }
                    });
                } else {
                    checkbox.prop('checked', !active_inactive);
                    updateMdtRowBadge(checkbox, !active_inactive);
                }
            });
        });

        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            if ($(this).attr('aria-disabled') === 'true' || $(this).hasClass('disabled')) {
                return;
            }

            const pk = $(this).data('id');
            Swal.fire({
                title: 'Are you sure?',
                text: 'This record will be permanently deleted!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('master.mdo_duty_type.delete') }}",
                        type: 'POST',
                        data: {
                            id: pk,
                            _token: csrfToken
                        },
                        success: function(response) {
                            reloadMdtTable();
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                        }
                    });
                }
            });
        });

        $(document).on('click', '.edit-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();

            openMdtModal('edit', {
                id: $(this).data('id'),
                name: $(this).data('mdo_duty_type_name'),
                status: $(this).data('active_inactive')
            });
        });

        $(document).on('click', '.add-btn', function(e) {
            e.preventDefault();
            openMdtModal('add');
        });

        $('#mdtFormSubmit').on('click', function() {
            if (mdtModalMode === 'add') {
                const $form = $('#AddMDODutyTypeForm');
                const name = $form.find('#mdt_add_duty_type_name');
                const status = $form.find('#mdt_add_active_inactive');

                let isValid = true;
                $form.find('small.text-danger').addClass('d-none');
                name.removeClass('is-invalid');
                status.removeClass('is-invalid');

                if (!name.val().trim()) {
                    $form.find('#mdt_add_duty_type_name_error').removeClass('d-none');
                    name.addClass('is-invalid').focus();
                    isValid = false;
                } else if (!status.val()) {
                    $form.find('#mdt_add_active_inactive_error').removeClass('d-none');
                    status.addClass('is-invalid').focus();
                    isValid = false;
                }

                if (!isValid) {
                    return;
                }

                $.ajax({
                    url: storeUrl,
                    type: 'POST',
                    data: {
                        mdo_duty_type_name: name.val().trim(),
                        active_inactive: status.val(),
                        _token: csrfToken
                    },
                    success: function(response) {
                        hideMdtModal();
                        resetMdtAddForm();
                        reloadMdtTable();
                        Swal.fire({
                            icon: 'success',
                            title: 'Saved!',
                            text: response.message ?? 'Record added successfully',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message ?? 'Something went wrong';
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            const errors = xhr.responseJSON.errors;
                            if (errors.mdo_duty_type_name) {
                                $form.find('#mdt_add_duty_type_name_error').text(errors.mdo_duty_type_name[0]).removeClass('d-none');
                                name.addClass('is-invalid');
                            }
                            if (errors.active_inactive) {
                                $form.find('#mdt_add_active_inactive_error').text(errors.active_inactive[0]).removeClass('d-none');
                                status.addClass('is-invalid');
                            }
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: message });
                        }
                    }
                });
            } else {
                const $form = $('#EditMDODutyTypeForm');
                const id = $form.find('#mdt_edit_id').val();
                const name = $form.find('#mdt_edit_duty_type_name');
                const status = $form.find('#mdt_edit_active_inactive');

                let isValid = true;
                $form.find('small.text-danger').addClass('d-none');
                name.removeClass('is-invalid');
                status.removeClass('is-invalid');

                if (!name.val().trim()) {
                    $form.find('#mdt_edit_duty_type_name_error').removeClass('d-none');
                    name.addClass('is-invalid').focus();
                    isValid = false;
                } else if (!status.val()) {
                    $form.find('#mdt_edit_active_inactive_error').removeClass('d-none');
                    status.addClass('is-invalid').focus();
                    isValid = false;
                }

                if (!isValid) {
                    return;
                }

                $.ajax({
                    url: storeUrl,
                    type: 'POST',
                    data: {
                        id: id,
                        mdo_duty_type_name: name.val().trim(),
                        active_inactive: status.val(),
                        _token: csrfToken
                    },
                    success: function(response) {
                        hideMdtModal();
                        reloadMdtTable();
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: response.message ?? 'Record updated successfully',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message ?? 'Something went wrong';
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            const errors = xhr.responseJSON.errors;
                            if (errors.mdo_duty_type_name) {
                                $form.find('#mdt_edit_duty_type_name_error').text(errors.mdo_duty_type_name[0]).removeClass('d-none');
                                name.addClass('is-invalid');
                            }
                            if (errors.active_inactive) {
                                $form.find('#mdt_edit_active_inactive_error').text(errors.active_inactive[0]).removeClass('d-none');
                                status.addClass('is-invalid');
                            }
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: message });
                        }
                    }
                });
            }
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
