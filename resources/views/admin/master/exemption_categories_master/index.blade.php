@extends('admin.layouts.master')

@section('title', 'Exemption categories')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid mst-page eccm-page">
    <x-breadcrum title="Exemption categories" :showBack="false">
        <button type="button" id="showAlert"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                data-bs-toggle="modal" data-bs-target="#eccmAddModal" aria-controls="eccmAddModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Exemption Category</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="eccmColumnsToggle"
                            data-bs-toggle="modal" data-bs-target="#eccmColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div class="programme-dt-search" data-dt-search-for="exceptiongetcategory"></div>
                </div>
            </div>

            {{-- Search, pager and "Showing N of M items" are relocated into the
                 slots by public/js/datatable-global-ui.js. --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="exceptiongetcategory">
                        <caption class="visually-hidden">Exemption categories</caption>
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Name</th>
                                <th scope="col">Short Name</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="exceptiongetcategory"></div>
            </div>

        </div>
    </div>
</div>

{{-- Add / Edit modals (appended to body on load for correct stacking) --}}
<div class="modal fade mst-modal eccm-form-modal" id="eccmAddModal" tabindex="-1" aria-labelledby="eccmAddModalLabel"
     aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold mb-0" id="eccmAddModalLabel">Add Exemption Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="exemptionCategoryForm" novalidate>
                    <div class="mst-field-card">
                        <div class="mb-3">
                            <label for="exemp_cat_short_name" class="mst-form-label d-block">
                                Short Name <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="exemp_cat_short_name"
                                   class="form-control mst-control"
                                   placeholder="eg. EC082" autocomplete="off"
                                   required aria-required="true">
                            <span class="mst-field-error d-none" id="exemp_cat_short_name_error">Required</span>
                        </div>

                        <div class="mb-3">
                            <label for="exemp_category_name" class="mst-form-label d-block">
                                Category Name <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="exemp_category_name"
                                   class="form-control mst-control"
                                   placeholder="eg. Category Pre" autocomplete="off"
                                   required aria-required="true">
                            <span class="mst-field-error d-none" id="exemp_category_name_error">Required</span>
                        </div>

                        <div class="mb-0">
                            <label for="status" class="mst-form-label d-block">
                                Status <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <select id="status" class="form-select mst-control mst-searchable"
                                    data-placeholder="Select Status" required aria-required="true">
                                <option value="">Select Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <span class="mst-field-error d-none" id="status_error">Required</span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 gap-2 justify-content-end">
                <button type="button" class="btn mst-btn-cancel px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn mst-btn-submit px-4" id="eccmAddSubmit">Save</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade mst-modal eccm-form-modal" id="eccmEditModal" tabindex="-1" aria-labelledby="eccmEditModalLabel"
     aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold mb-0" id="eccmEditModalLabel">Edit Exemption Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="exemptionCategoryeditForm" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="pk" value="">

                    {{-- Field ids are eccm_edit_* so they don't collide with the Add
                         modal (the old markup reused #exemp_cat_short_name etc., so
                         every <label for> here pointed at the Add modal's field). --}}
                    <div class="mst-field-card">
                        <div class="mb-3">
                            <label for="eccm_edit_short_name" class="mst-form-label d-block">
                                Short Name <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <input type="text" name="exemp_cat_short_name" id="eccm_edit_short_name"
                                   class="form-control mst-control"
                                   placeholder="eg. EC082" autocomplete="off"
                                   required aria-required="true">
                            <span class="mst-field-error d-none" id="eccm_edit_short_name_error">Required</span>
                        </div>

                        <div class="mb-3">
                            <label for="eccm_edit_category_name" class="mst-form-label d-block">
                                Category Name <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <input type="text" name="exemp_category_name" id="eccm_edit_category_name"
                                   class="form-control mst-control"
                                   placeholder="eg. Category Pre" autocomplete="off"
                                   required aria-required="true">
                            <span class="mst-field-error d-none" id="eccm_edit_category_name_error">Required</span>
                        </div>

                        <div class="mb-0">
                            <label for="eccm_edit_status" class="mst-form-label d-block">
                                Status <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <select name="status" id="eccm_edit_status" class="form-select mst-control mst-searchable"
                                    data-placeholder="Select Status" required aria-required="true">
                                <option value="">Select Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <span class="mst-field-error d-none" id="eccm_edit_status_error">Required</span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 gap-2 justify-content-end">
                <button type="button" class="btn mst-btn-cancel px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn mst-btn-submit px-4" id="eccmEditSubmit">Update</button>
            </div>
        </div>
    </div>
</div>

{{-- Column Visibility --}}
<div class="modal fade" id="eccmColumnVisibilityModal" tabindex="-1"
     aria-labelledby="eccmColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="eccmColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3 mst-colvis-grid" id="eccmColumnToggleGrid"></div>
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
            $form.find('#status').val('').removeClass('is-invalid').trigger('change.select2');
            $form.find('.mst-field-error').addClass('d-none');
        }

        if (eccmAddModalEl) {
            eccmAddModalEl.addEventListener('show.bs.modal', function() {
                resetEccmAddForm();
            });
            eccmAddModalEl.addEventListener('shown.bs.modal', function() {
                $('#exemptionCategoryForm #exemp_cat_short_name').trigger('focus');
            });
        }

        /* ---------- Row markup ----------
         * The feed (ExemptionCategoryController@getcategory) still returns its
         * old `status` / `action` HTML; the cells are rebuilt here from the row
         * data with the exact markup of admin/master/partials/grid-status and
         * grid-actions. The switch keeps this page's own hook
         * (.plain-status-toggle + data-id) instead of .status-toggle, so the
         * global custom.js toggle handler does not fire as well. */
        // Yajra HTML-escapes every string in the JSON (escape '*'); undo that
        // before a value goes into an attribute or it is escaped twice.
        function eccmDecode(value) {
            const el = document.createElement('textarea');
            el.innerHTML = value == null ? '' : String(value);
            return el.value;
        }

        function eccmStatusBadge(row) {
            const isActive = String(row.active_inactive) === '1';
            return $('<span></span>')
                .attr('class', 'status-pill badge rounded-1 ' + (isActive ? 'bg-success-subtle' : 'bg-danger-subtle'))
                .text(isActive ? 'Active' : 'Inactive')
                .prop('outerHTML');
        }

        function eccmActStack(cls, icon, label, title) {
            return $('<button type="button"></button>').attr({ 'class': 'mst-act ' + cls, title: title })
                .append($('<span class="mst-act__icon"></span>').append($('<i aria-hidden="true"></i>').addClass('bi ' + icon)))
                .append($('<span class="mst-act__label"></span>').text(label));
        }

        function eccmActions(row) {
            const isActive = String(row.active_inactive) === '1';
            const name = eccmDecode(row.exemp_category_name);
            const $group = $('<div class="mst-act-group" role="group" aria-label="Row actions"></div>');

            $group.append(
                eccmActStack('mst-act--edit edit-btn', 'bi-pencil', 'Edit', $.trim('Edit ' + name)).attr({
                    'data-id': row.pk,
                    'data-exemp_category_name': name,
                    'data-exemp_cat_short_name': eccmDecode(row.exemp_cat_short_name),
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
                    eccmActStack('mst-act--del delete-btn', 'bi-trash', 'Delete', $.trim('Delete ' + name))
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
                        searchable: false,
                        render: function(data, type, row) {
                            return type === 'display' ? eccmStatusBadge(row) : row.active_inactive;
                        }
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return type === 'display' ? eccmActions(row) : '';
                        }
                    }
                ],
                columnDefs: [
                    { targets: [0, 2, 3, 4], className: 'text-nowrap' }
                ],
                language: {
                    processing: '<span class="spinner-border spinner-border-sm text-primary me-2" role="status" aria-hidden="true"></span>Loading…',
                    emptyTable: 'No exemption categories found.',
                    zeroRecords: 'No matching exemption categories found.'
                }
            });
        }

        MstAdmin.columnVisibility({
            table: tableSelector,
            grid: '#eccmColumnToggleGrid',
            storageKey: 'sargam.exemptionCategory.hiddenCols.{{ auth()->id() ?? 'guest' }}'
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

        $('#eccmAddSubmit').on('click', function() {
            const $form = $('#exemptionCategoryForm');
            const name = $form.find('#exemp_category_name');
            const shortName = $form.find('#exemp_cat_short_name');
            const status = $form.find('#status');

            let isValid = true;
            $form.find('.mst-field-error').addClass('d-none');
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

            // attr(), not data(): jQuery's data() turns a name like "1e3" into a number.
            let pk = $(this).attr('data-id');
            let exemp_category_name = $(this).attr('data-exemp_category_name');
            let exemp_cat_short_name = $(this).attr('data-exemp_cat_short_name');
            let status = $(this).attr('data-active_inactive');

            const $form = $('#exemptionCategoryeditForm');
            $form.find('input[name="pk"]').val(pk);
            $form.find('#eccm_edit_category_name').val(exemp_category_name || '');
            $form.find('#eccm_edit_short_name').val(exemp_cat_short_name || '');
            $form.find('#eccm_edit_status')
                .val(status === '0' ? '0' : (status === '1' ? '1' : ''))
                .trigger('change.select2');
            $form.find('.mst-field-error').addClass('d-none');
            $form.find('.form-control, .form-select').removeClass('is-invalid');

            showEccmModal(eccmEditModalEl);

            if (eccmEditModalEl) {
                eccmEditModalEl.addEventListener('shown.bs.modal', function onShown() {
                    $form.find('#eccm_edit_short_name').trigger('focus');
                    eccmEditModalEl.removeEventListener('shown.bs.modal', onShown);
                });
            }
        });

        $('#eccmEditSubmit').on('click', function() {
            const form = document.getElementById('exemptionCategoryeditForm');
            const typeName = form.querySelector('#eccm_edit_category_name');
            const shortName = form.querySelector('#eccm_edit_short_name');
            const statusEl = form.querySelector('#eccm_edit_status');

            form.querySelectorAll('.mst-field-error').forEach(function(el) {
                el.classList.add('d-none');
            });
            typeName.classList.remove('is-invalid');
            shortName.classList.remove('is-invalid');
            statusEl.classList.remove('is-invalid');

            let valid = true;

            if (!typeName.value.trim()) {
                form.querySelector('#eccm_edit_category_name_error').classList.remove('d-none');
                typeName.classList.add('is-invalid');
                typeName.focus();
                valid = false;
            } else if (!shortName.value.trim()) {
                form.querySelector('#eccm_edit_short_name_error').classList.remove('d-none');
                shortName.classList.add('is-invalid');
                shortName.focus();
                valid = false;
            } else if (!statusEl.value) {
                form.querySelector('#eccm_edit_status_error').classList.remove('d-none');
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

        // Kept from the original page. Nothing on this grid renders .deleteBtn
        // (it reloads #memotypemaster-table, which is not here) — unreachable.
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
@endpush
