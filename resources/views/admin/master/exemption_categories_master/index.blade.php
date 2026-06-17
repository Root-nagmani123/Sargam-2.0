@extends('admin.layouts.master')

@section('title', 'Exemption categories')

@section('setup_content')
<div class="container-fluid eccm-master-page">
    <x-breadcrum title="Exemption categories">
        <button type="button"
            id="showAlert"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-2 fw-semibold shadow-sm"
            data-bs-toggle="modal"
            data-bs-target="#eccmAddModal"
            aria-controls="eccmAddModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Exemption Category</span>
        </button>
    </x-breadcrum>

<div class="container-fluid">
    <x-breadcrum title="Exemption categories" />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" >
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="exceptiongetcategory">
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap" style="width: 5.5rem;">S.No.</th>
                                <th scope="col">Name</th>
                                <th scope="col" class="text-nowrap">Short Name</th>
                                <th scope="col" class="text-center text-nowrap" style="width: 7.5rem;">Status</th>
                                <th scope="col" class="text-center text-nowrap" style="width: 9rem;">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div id="eccmDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="exceptiongetcategory"></div>
            </div>
        </div>
    </div>
</div>

<!-- Add / Edit modals (appended to body on load for correct stacking) -->
<!-- Add Exemption Category -->
<div class="modal fade eccm-form-modal" id="eccmAddModal" tabindex="-1" aria-labelledby="eccmAddModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cgt-form-modal border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="eccmAddModalLabel">Add Exemption Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="exemptionCategoryForm" novalidate>
                    <div class="mb-3">
                        <label for="exemp_cat_short_name" class="form-label cgt-field-label mb-2">
                            Short Name <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               id="exemp_cat_short_name"
                               class="form-control rounded-3"
                               placeholder="eg. EC082"
                               autocomplete="off">
                        <small class="text-danger d-none mt-1" id="exemp_cat_short_name_error">Required</small>
                    </div>

                    <div class="mb-3">
                        <label for="exemp_category_name" class="form-label cgt-field-label mb-2">
                            Category Name <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               id="exemp_category_name"
                               class="form-control rounded-3"
                               placeholder="eg. Category Pre"
                               autocomplete="off">
                        <small class="text-danger d-none mt-1" id="exemp_category_name_error">Required</small>
                    </div>

                    <div class="mb-0">
                        <label for="status" class="form-label cgt-field-label mb-2">
                            Status <span class="text-danger">*</span>
                        </label>
                        <select id="status" class="form-select rounded-3">
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <small class="text-danger d-none mt-1" id="status_error">Required</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-3 px-4" id="eccmAddSubmit">Add</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Exemption Category -->
<div class="modal fade eccm-form-modal" id="eccmEditModal" tabindex="-1" aria-labelledby="eccmEditModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cgt-form-modal border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="eccmEditModalLabel">Edit Exemption Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="exemptionCategoryeditForm" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="pk" value="">

                    <div class="mb-3">
                        <label for="exemp_cat_short_name" class="form-label cgt-field-label mb-2">
                            Short Name <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               name="exemp_cat_short_name"
                               id="exemp_cat_short_name"
                               class="form-control rounded-3"
                               placeholder="eg. EC082"
                               autocomplete="off">
                        <small class="text-danger d-none mt-1" id="exemp_cat_short_name_error">Required</small>
                    </div>

                    <div class="mb-3">
                        <label for="exemp_category_name" class="form-label cgt-field-label mb-2">
                            Category Name <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               name="exemp_category_name"
                               id="exemp_category_name"
                               class="form-control rounded-3"
                               placeholder="eg. Category Pre"
                               autocomplete="off">
                        <small class="text-danger d-none mt-1" id="exemp_category_name_error">Required</small>
                    </div>

                    <div class="mb-0">
                        <label for="status" class="form-label cgt-field-label mb-2">
                            Status <span class="text-danger">*</span>
                        </label>
                        <select name="status" id="status" class="form-select rounded-3">
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <small class="text-danger d-none mt-1" id="status_error">Required</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-3 px-4" id="eccmEditSubmit">Update</button>
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
        const tableSelector = '#exceptiongetcategory';
        let table;

        const eccmAddModalEl = document.getElementById('eccmAddModal');
        const eccmEditModalEl = document.getElementById('eccmEditModal');

        document.querySelectorAll('.eccm-form-modal').forEach(function(modalEl) {
            if (modalEl.parentElement && modalEl.parentElement !== document.body) {
                document.body.appendChild(modalEl);
            }
        });

        function showEccmModal(modalEl) {
            if (!modalEl) {
                return;
            }
            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            } else if (window.jQuery) {
                $(modalEl).modal('show');
            }
        }

        function hideEccmModal(modalEl) {
            if (!modalEl) {
                return;
            }
            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            } else if (window.jQuery) {
                $(modalEl).modal('hide');
            }
        }

        function resetEccmAddForm() {
            const $form = $('#exemptionCategoryForm');
            $form.find('#exemp_category_name, #exemp_cat_short_name').val('').removeClass('is-invalid');
            $form.find('#status').val('');
            $form.find('small.text-danger').addClass('d-none');
        }

        if (eccmAddModalEl) {
            eccmAddModalEl.addEventListener('show.bs.modal', function() {
                resetEccmAddForm();
            });
            eccmAddModalEl.addEventListener('shown.bs.modal', function() {
                $('#exemptionCategoryForm #exemp_cat_short_name').trigger('focus');
            });
        }

        function decorateEccmRows() {
            $(tableSelector + ' tbody tr').each(function() {
                const $row = $(this);
                const $cells = $row.find('td');
                if ($cells.length < 5) {
                    return;
                }

                const $statusCell = $cells.eq(3);
                const $actionCell = $cells.eq(4);
                const $toggle = $statusCell.find('.plain-status-toggle').add($actionCell.find('.plain-status-toggle')).first();

                if ($toggle.length) {
                    const isActive = $toggle.is(':checked');
                    const badgeClass = isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive';
                    const label = isActive ? 'Active' : 'Inactive';

                    const $switchWrap = $toggle.closest('.form-check');
                    const $actionGroup = $actionCell.find('.d-inline-flex[role="group"]');
                    const $editBtn = $actionGroup.find('.edit-btn').first();

                    if ($switchWrap.length && $actionGroup.length && $editBtn.length) {
                        $switchWrap.addClass('programme-action-switch m-0 d-inline-flex align-items-center');
                        $editBtn.after($switchWrap);
                    }

                    $statusCell.empty().append(
                        $('<span>', {
                            class: 'badge rounded-pill programme-status-badge eccm-status-badge ' + badgeClass,
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

        function updateEccmRowBadge($checkbox, isActive) {
            const $badge = $checkbox.closest('tr').find('.eccm-status-badge');
            if ($badge.length) {
                $badge
                    .removeClass('programme-status-badge--active programme-status-badge--inactive')
                    .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
                    .text(isActive ? 'Active' : 'Inactive');
            }
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
                ajax: {
                    url: "{{ route('master.exemption.category.master.getcategory') }}",
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
                        data: 'exemp_category_name',
                        name: 'exemp_category_name'
                    },
                    {
                        data: 'ShortName',
                        name: 'ShortName'
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
                        className: 'text-nowrap'
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
                    processing: '<span class="spinner-border spinner-border-sm text-primary me-2" role="status" aria-hidden="true"></span>Loading…',
                    emptyTable: 'No exemption categories found.',
                    zeroRecords: 'No matching exemption categories found.'
                },
                initComplete: function() {
                    decorateEccmRows();
                },
                drawCallback: function() {
                    decorateEccmRows();
                }
            });
        }

        $(document).on('change', '.plain-status-toggle', function() {
            var checkbox = $(this);
            var pk = checkbox.data('id');
            var active_inactive = checkbox.is(':checked') ? 1 : 0;
            var actionText = active_inactive ? 'activate' : 'deactivate';
            var confirmBtnText = active_inactive ? 'Yes, activate' : 'Yes, deactivate';
            var confirmBtnColor = active_inactive ? '#28a745' : '#d33';

            Swal.fire({
                title: 'Are you sure?',
                text: `Are you sure you want to ${actionText} this item?`,
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
                    updateEccmRowBadge(checkbox, !active_inactive);
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
                        icon: 'error',
                        title: 'Cancelled',
                        text: 'Delete has been cancelled.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            });
        });

        $('#eccmAddSubmit').on('click', function() {
            const $form = $('#exemptionCategoryForm');
            const name = $form.find('#exemp_category_name');
            const shortName = $form.find('#exemp_cat_short_name');
            const status = $form.find('#status');

            let isValid = true;
            $form.find('small.text-danger').addClass('d-none');
            name.removeClass('is-invalid');
            shortName.removeClass('is-invalid');
            status.removeClass('is-invalid');

            if (!name.val().trim()) {
                $form.find('#exemp_category_name_error').removeClass('d-none');
                name.addClass('is-invalid').focus();
                isValid = false;
            } else if (!shortName.val().trim()) {
                $form.find('#exemp_cat_short_name_error').removeClass('d-none');
                shortName.addClass('is-invalid').focus();
                isValid = false;
            } else if (!status.val()) {
                $form.find('#status_error').removeClass('d-none');
                status.addClass('is-invalid').focus();
                isValid = false;
            }

            if (!isValid) {
                return;
            }

            const formData = new FormData();
            formData.append('exemp_category_name', name.val());
            formData.append('exemp_cat_short_name', shortName.val());
            formData.append('status', status.val());

            fetch("{{ route('master.exemption.category.master.store') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => res.json())
            .then(function(result) {
                if (result.status) {
                    hideEccmModal(eccmAddModalEl);
                    resetEccmAddForm();
                    table.ajax.reload();
                    Swal.fire('Success', result.message, 'success');
                }
            })
            .catch(function() {
                Swal.fire('Error', 'Server Error or Session Expired', 'error');
            });
        });

        $(document).on('click', '.edit-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();

            let pk = $(this).data('id');
            let exemp_category_name = $(this).data('exemp_category_name');
            let exemp_cat_short_name = $(this).data('exemp_cat_short_name');
            let status = $(this).data('active_inactive');

            const $form = $('#exemptionCategoryeditForm');
            $form.find('input[name="pk"]').val(pk);
            $form.find('#exemp_category_name').val(exemp_category_name || '');
            $form.find('#exemp_cat_short_name').val(exemp_cat_short_name || '');
            $form.find('#status').val(status === 0 || status === '0' ? '0' : (status === 1 || status === '1' ? '1' : ''));
            $form.find('small.text-danger').addClass('d-none');
            $form.find('.form-control, .form-select').removeClass('is-invalid');

            showEccmModal(eccmEditModalEl);

            if (eccmEditModalEl) {
                eccmEditModalEl.addEventListener('shown.bs.modal', function onShown() {
                    $form.find('#exemp_cat_short_name').trigger('focus');
                    eccmEditModalEl.removeEventListener('shown.bs.modal', onShown);
                });
            }
        });

        $('#eccmEditSubmit').on('click', function() {
            const popup = document.getElementById('exemptionCategoryeditForm');
            const form = popup;
            const typeName = form.querySelector('#exemp_category_name');
            const shortName = form.querySelector('#exemp_cat_short_name');
            const statusEl = form.querySelector('#status');

            form.querySelectorAll('small.text-danger').forEach(function(el) {
                el.classList.add('d-none');
            });
            typeName.classList.remove('is-invalid');
            shortName.classList.remove('is-invalid');
            statusEl.classList.remove('is-invalid');

            let valid = true;

            if (!typeName.value.trim()) {
                form.querySelector('#exemp_category_name_error').classList.remove('d-none');
                typeName.classList.add('is-invalid');
                typeName.focus();
                valid = false;
            } else if (!shortName.value.trim()) {
                form.querySelector('#exemp_cat_short_name_error').classList.remove('d-none');
                shortName.classList.add('is-invalid');
                shortName.focus();
                valid = false;
            } else if (!statusEl.value) {
                form.querySelector('#status_error').classList.remove('d-none');
                statusEl.classList.add('is-invalid');
                statusEl.focus();
                valid = false;
            }

            if (!valid) {
                return;
            }

            const formData = new FormData(form);

            fetch("{{ route('master.exemption.category.master.store') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => res.text())
            .then(text => {
                try {
                    return JSON.parse(text);
                } catch {
                    throw new Error(text);
                }
            })
            .then(function(result) {
                if (result.status) {
                    hideEccmModal(eccmEditModalEl);
                    table.ajax.reload();
                    Swal.fire('Updated!', result.message, 'success');
                }
            })
            .catch(function() {
                Swal.fire('Error', 'Server error or session expired', 'error');
            });
        });

        $(document).on('click', '.deleteBtn', function(e) {
            e.preventDefault();

            const btn = $(this);
            const url = btn.data('url');
            const pk = btn.data('pk');

            Swal.fire({
                title: 'Are you sure?',
                text: 'This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            _method: 'DELETE',
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        beforeSend: function() {
                            btn.prop('disabled', true);
                        },
                        success: function(res) {
                            if (res.status) {
                                Swal.fire('Deleted!', res.message, 'success');
                                $('#memotypemaster-table')
                                    .DataTable()
                                    .ajax.reload(null, false);
                            } else {
                                Swal.fire('Error!', res.message, 'error');
                                btn.prop('disabled', false);
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'Something went wrong.', 'error');
                            btn.prop('disabled', false);
                        }
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
