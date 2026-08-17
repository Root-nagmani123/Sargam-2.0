@extends('admin.layouts.master')

@section('title', 'Exemption categories')

@push('styles')
{{-- Module stylesheet (docs/new-design-index-page.md §7) — this page and its
     modals used to carry the same rules in an inline <style> block. --}}
<link rel="stylesheet"
    href="{{ asset('css/exemption-masters-admin.css') }}?v={{ @filemtime(public_path('css/exemption-masters-admin.css')) }}">
@endpush

@section('setup_content')
<div class="container-fluid eccm-master-page">
    <x-breadcrum title="Exemption categories" :showBack="false">
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

    <div class="card eccm-dt-card border-0 shadow-sm rounded-1 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            {{-- Toolbar (§2). The search is the shared .programme-dt-search
                 markup — the structure datatable-global-ui.js would relocate into
                 that slot — so the skin matches every other index page even though
                 this table drives its own server-side search. --}}
            <div class="eccm-toolbar programme-dt-toolbar d-flex flex-wrap align-items-center justify-content-end gap-2 mb-4">
                <button type="button" class="btn programme-dt-btn-columns" id="eccmColumnsToggle"
                    data-bs-toggle="modal" data-bs-target="#eccmColumnsModal"
                    title="Show / hide columns">
                    <span>Columns</span>
                    <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                </button>
                <div class="programme-dt-search">
                    <div class="dataTables_filter">
                        <label>
                            <input type="search" id="eccmTableSearch" class="form-control form-control-sm"
                                placeholder="Search" autocomplete="off" aria-label="Search exemption categories">
                        </label>
                    </div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="exceptiongetcategory">
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap">S. No.</th>
                                <th scope="col">Name</th>
                                <th scope="col" class="text-nowrap">Short Name</th>
                                <th scope="col" class="text-center text-nowrap">Status</th>
                                <th scope="col" class="text-center text-nowrap">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add / Edit modals (appended to body on load for correct stacking) -->
<!-- Add Exemption Category -->
<div class="modal fade exm-modal eccm-form-modal" id="eccmAddModal" tabindex="-1" aria-labelledby="eccmAddModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="exm-modal-header">
                <h5 class="exm-modal-title" id="eccmAddModalLabel">Add Exemption Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="exm-modal-body">
                <form id="exemptionCategoryForm" novalidate>
                    <div class="exm-field-card">
                        <div class="exm-field">
                            <label for="exemp_cat_short_name" class="exm-field-label">
                                Short Name <span class="exm-req">*</span>
                            </label>
                            <input type="text"
                                   id="exemp_cat_short_name"
                                   class="exm-control"
                                   placeholder="eg. EC082"
                                   maxlength="50"
                                   autocomplete="off">
                            <small class="text-danger d-none mt-1" id="exemp_cat_short_name_error">Required</small>
                        </div>

                        <div class="exm-field">
                            <label for="exemp_category_name" class="exm-field-label">
                                Category Name <span class="exm-req">*</span>
                            </label>
                            <input type="text"
                                   id="exemp_category_name"
                                   class="exm-control"
                                   placeholder="eg. Category Pre"
                                   maxlength="100"
                                   autocomplete="off">
                            <small class="text-danger d-none mt-1" id="exemp_category_name_error">Required</small>
                        </div>

                        <div class="exm-field">
                            <label for="status" class="exm-field-label">
                                Status <span class="exm-req">*</span>
                            </label>
                            <select id="status" class="exm-control">
                                <option value="">Select Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <small class="text-danger d-none mt-1" id="status_error">Required</small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="exm-modal-footer">
                <button type="button" class="btn exm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn exm-btn-submit" id="eccmAddSubmit">Add Exemption Category</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Exemption Category -->
<div class="modal fade exm-modal eccm-form-modal" id="eccmEditModal" tabindex="-1" aria-labelledby="eccmEditModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="exm-modal-header">
                <h5 class="exm-modal-title" id="eccmEditModalLabel">Edit Exemption Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="exm-modal-body">
                {{-- The ids carry an "edit_" prefix: the Add modal owns the bare
                     ones, and with both in the DOM every label here pointed at the
                     other modal's input. --}}
                <form id="exemptionCategoryeditForm" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="pk" value="">

                    <div class="exm-field-card">
                        <div class="exm-field">
                            <label for="edit_exemp_cat_short_name" class="exm-field-label">
                                Short Name <span class="exm-req">*</span>
                            </label>
                            <input type="text"
                                   name="exemp_cat_short_name"
                                   id="edit_exemp_cat_short_name"
                                   class="exm-control"
                                   placeholder="eg. EC082"
                                   maxlength="50"
                                   autocomplete="off">
                            <small class="text-danger d-none mt-1" id="edit_exemp_cat_short_name_error">Required</small>
                        </div>

                        <div class="exm-field">
                            <label for="edit_exemp_category_name" class="exm-field-label">
                                Category Name <span class="exm-req">*</span>
                            </label>
                            <input type="text"
                                   name="exemp_category_name"
                                   id="edit_exemp_category_name"
                                   class="exm-control"
                                   placeholder="eg. Category Pre"
                                   maxlength="100"
                                   autocomplete="off">
                            <small class="text-danger d-none mt-1" id="edit_exemp_category_name_error">Required</small>
                        </div>

                        <div class="exm-field">
                            <label for="edit_status" class="exm-field-label">
                                Status <span class="exm-req">*</span>
                            </label>
                            <select name="status" id="edit_status" class="exm-control">
                                <option value="">Select Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <small class="text-danger d-none mt-1" id="edit_status_error">Required</small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="exm-modal-footer">
                <button type="button" class="btn exm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn exm-btn-submit" id="eccmEditSubmit">Update Exemption Category</button>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility -->
<div class="modal fade exm-modal" id="eccmColumnsModal" tabindex="-1" aria-labelledby="eccmColumnsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="exm-modal-header">
                <h5 class="exm-modal-title" id="eccmColumnsModalLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="exm-modal-body">
                <div class="exm-col-grid" id="eccmColumnsGrid"></div>
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

        // The status badge and the Edit · switch · Delete group are rendered
        // server-side now (ExemptionCategoryController::getcategory, §3b). This
        // used to move the switch out of the Status cell into the action group,
        // rebuild the badge and strip the button captions on every single draw —
        // and stripping those captions is what left Edit and Delete as blank
        // 28px squares, because the material-icon glyph they wrapped computes to
        // display:none on this layout. Kept as a no-op so the DataTables
        // callbacks below keep their shape.
        function decorateEccmRows() {}
        // Only used when the user CANCELS the confirm dialog: the switch has
        // already flipped visually, so the badge is put back in step with it.
        // A confirmed change reloads the table, which re-renders both from the
        // server.
        function updateEccmRowBadge($checkbox, isActive) {
            const $badge = $checkbox.closest('tr').find('.eccm-status-badge');
            if ($badge.length) {
                $badge
                    .removeClass('bg-success-subtle bg-danger-subtle')
                    .addClass(isActive ? 'bg-success-subtle' : 'bg-danger-subtle')
                    .text(isActive ? 'Active' : 'Inactive');
            }
            // The caption names the ACTION, so it is the inverse of the state.
            $checkbox.attr('aria-label', (isActive ? 'Deactivate' : 'Activate') + ' category')
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
                // ‹ 1 2 3 › — First/Last are not part of the shared footer (§4).
                pagingType: 'simple_numbers',
                lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
                order: [[0, 'desc']],
                // Bottom bar = pagination (left) + "Showing [n] of N items"
                // (right), wearing the shared .programme-dt-footer chrome (§4).
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
                    lengthMenu: 'Showing _MENU_',
                    info: 'of _TOTAL_ items',
                    infoEmpty: 'of 0 items',
                    infoFiltered: '',
                    processing: '<span class="spinner-border spinner-border-sm text-primary me-2" role="status" aria-hidden="true"></span>Loading…',
                    emptyTable: 'No exemption categories found.',
                    zeroRecords: 'No matching exemption categories found.',
                    paginate: {
                        previous: '<span aria-hidden="true">&lsaquo;</span>',
                        next: '<span aria-hidden="true">&rsaquo;</span>'
                    }
                },
                initComplete: function() {
                    decorateEccmRows();
                },
                drawCallback: function() {
                    decorateEccmRows();
                }
            });
        }

        // 🔍 Toolbar search → server-side global search
        var eccmSearchTimer;
        $('#eccmTableSearch').on('keyup', function() {
            var value = this.value;
            clearTimeout(eccmSearchTimer);
            eccmSearchTimer = setTimeout(function() {
                table.search(value).draw();
            }, 400);
        });

        // 🧱 Column Visibility modal (chips built from the live DataTable)
        var $eccmColGrid = $('#eccmColumnsGrid');
        table.columns().every(function(idx) {
            var title = $.trim($(this.header()).text()) || ('Column ' + (idx + 1));
            var visible = this.visible();
            $eccmColGrid.append(
                '<label class="exm-col-chip' + (visible ? ' is-checked' : '') + '" for="eccmColToggle' + idx + '">' +
                    '<input class="form-check-input eccm-col-toggle" type="checkbox" ' + (visible ? 'checked ' : '') +
                           'id="eccmColToggle' + idx + '" data-column="' + idx + '">' +
                    '<span>' + title + '</span>' +
                '</label>'
            );
        });
        $eccmColGrid.on('change', '.eccm-col-toggle', function() {
            table.column($(this).data('column')).visible(this.checked);
            $(this).closest('.exm-col-chip').toggleClass('is-checked', this.checked);
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
            $form.find('#edit_exemp_category_name').val(exemp_category_name || '');
            $form.find('#edit_exemp_cat_short_name').val(exemp_cat_short_name || '');
            $form.find('#edit_status').val(status === 0 || status === '0' ? '0' : (status === 1 || status === '1' ? '1' : ''));
            $form.find('small.text-danger').addClass('d-none');
            $form.find('.exm-control').removeClass('is-invalid');

            showEccmModal(eccmEditModalEl);

            if (eccmEditModalEl) {
                eccmEditModalEl.addEventListener('shown.bs.modal', function onShown() {
                    $form.find('#edit_exemp_cat_short_name').trigger('focus');
                    eccmEditModalEl.removeEventListener('shown.bs.modal', onShown);
                });
            }
        });

        $('#eccmEditSubmit').on('click', function() {
            const popup = document.getElementById('exemptionCategoryeditForm');
            const form = popup;
            const typeName = form.querySelector('#edit_exemp_category_name');
            const shortName = form.querySelector('#edit_exemp_cat_short_name');
            const statusEl = form.querySelector('#edit_status');

            form.querySelectorAll('small.text-danger').forEach(function(el) {
                el.classList.add('d-none');
            });
            typeName.classList.remove('is-invalid');
            shortName.classList.remove('is-invalid');
            statusEl.classList.remove('is-invalid');

            let valid = true;

            if (!typeName.value.trim()) {
                form.querySelector('#edit_exemp_category_name_error').classList.remove('d-none');
                typeName.classList.add('is-invalid');
                typeName.focus();
                valid = false;
            } else if (!shortName.value.trim()) {
                form.querySelector('#edit_exemp_cat_short_name_error').classList.remove('d-none');
                shortName.classList.add('is-invalid');
                shortName.focus();
                valid = false;
            } else if (!statusEl.value) {
                form.querySelector('#edit_status_error').classList.remove('d-none');
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
