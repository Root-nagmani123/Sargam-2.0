@extends('admin.layouts.master')

@section('title', 'Exemption categories')

@section('setup_content')
<style>
/* ===== Exemption Categories — reference-matched polish (presentation only) ===== */

/* Toolbar: Columns + Search (top-right of card) */
.eccm-toolbar { gap: 0.5rem; }
.eccm-tool-btn {
    height: 42px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0 14px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #1f2937;
    background: #fff;
    border: 1px solid #d0d5dd;
    border-radius: 8px;
    line-height: 1;
}
.eccm-tool-btn:hover { border-color: #b6c0cc; }
.eccm-search-box { position: relative; display: inline-flex; align-items: center; }
.eccm-search-ico { position: absolute; left: 14px; color: #667085; font-size: 16px; pointer-events: none; }
.eccm-search-input {
    height: 42px;
    width: 300px;
    max-width: 100%;
    padding-left: 40px;
    border: 1px solid #d0d5dd;
    border-radius: 8px;
    font-size: 0.9rem;
    background: #fff;
}
.eccm-search-input:focus { border-color: #86b7fe; box-shadow: 0 0 0 0.2rem rgba(13,110,253,0.18); outline: none; }

/* Table header */
.eccm-master-page .programme-dt-table thead th {
    background: #f8fafc;
    color: #667085;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.02em;
    font-weight: 600;
    border-bottom: 1px solid #e5e7eb;
    padding: 12px 14px;
}
.eccm-master-page .programme-dt-table tbody td { padding: 14px; vertical-align: middle; }

/* Status pills */
.eccm-master-page .programme-status-badge,
.eccm-master-page .eccm-status-badge {
    display: inline-block;
    padding: 0.35rem 0.95rem;
    border-radius: 50rem;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1.2;
}
.eccm-master-page .programme-status-badge--active { color: #0f7b3e; background: #e3f5ea; }
.eccm-master-page .programme-status-badge--inactive { color: #c0392b; background: #fde6e4; }

/* Row action icons: edit (indigo) · toggle (amber) · delete (red) */
.eccm-master-page .edit-btn,
.eccm-master-page .delete-btn {
    border: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
    padding: 4px 6px !important;
    line-height: 1;
}
.eccm-master-page .edit-btn { color: #4f46e5 !important; }
.eccm-master-page .delete-btn { color: #dc3545 !important; }
.eccm-master-page .edit-btn .bi,
.eccm-master-page .delete-btn .bi { font-size: 18px; }
.eccm-master-page .programme-action-switch .form-check-input { cursor: pointer; }
.eccm-master-page .programme-action-switch .form-check-input:checked {
    background-color: #f0a500;
    border-color: #f0a500;
}

/* Bottom bar: pagination (left) + "Showing [n] of N items" (right) */
.eccm-master-page .eccm-count,
.eccm-master-page .eccm-count .dataTables_info,
.eccm-master-page .eccm-count .dataTables_length { color: #667085; font-size: 0.875rem; }
.eccm-master-page .eccm-count .dataTables_length,
.eccm-master-page .eccm-count .dataTables_info { margin: 0; padding: 0; white-space: nowrap; }
.eccm-master-page .eccm-count .dataTables_length label { margin: 0; display: inline-flex; align-items: center; gap: 0.5rem; }
.eccm-master-page .eccm-count .dataTables_length select {
    width: auto; min-width: 76px; display: inline-block; border-radius: 6px; margin: 0 0.25rem;
}
.eccm-master-page .pagination { gap: 4px; margin: 0; flex-wrap: wrap; }
.eccm-master-page .pagination .page-link {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    min-width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #1f2937;
    margin-left: 0;
    background: #fff;
}
.eccm-master-page .pagination .page-link:hover { background: #f8fafc; }
.eccm-master-page .pagination .page-item.active .page-link { background: var(--bs-primary); border-color: var(--bs-primary); color: #fff; }
.eccm-master-page .pagination .page-item.disabled .page-link { color: #98a2b3; background: #f8fafc; }

/* Column Visibility modal grid */
.eccm-col-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
.eccm-col-chip {
    display: flex; align-items: center; gap: 8px; margin: 0;
    padding: 0.6rem 0.85rem; border: 1px solid #e2e8f0; border-radius: 8px;
    background: #fff; cursor: pointer; font-size: 0.9rem; font-weight: 500; color: #1f2937; user-select: none;
}
.eccm-col-chip:hover { border-color: #b6c0cc; background: #f8fafc; }
.eccm-col-chip.is-checked { border-color: var(--bs-primary); box-shadow: inset 0 0 0 1px var(--bs-primary); }
@media (max-width: 479.98px) { .eccm-col-grid { grid-template-columns: 1fr; } }
</style>
<div class="container-fluid eccm-master-page">
    <x-breadcrum title="Exemption categories">
        <button type="button"
            id="showAlert"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-2 fw-semibold shadow-sm"
            data-bs-toggle="modal"
            data-bs-target="#eccmAddModal"
            aria-controls="eccmAddModal">
            <i class="material-icons material-symbols-rounded" aria-hidden="true">add</i>
            <span>Add Exemption Category</span>
        </button>
    </x-breadcrum>

    <div class="card eccm-dt-card border-0 shadow-sm rounded-1 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="eccm-toolbar d-flex flex-wrap align-items-center justify-content-end mb-4">
                <button type="button" class="eccm-tool-btn" id="eccmColumnsToggle"
                    data-bs-toggle="modal" data-bs-target="#eccmColumnsModal">
                    <i class="material-icons material-symbols-rounded" aria-hidden="true">view_column</i>
                    <span class="d-none d-sm-inline">Columns</span>
                </button>
                <div class="eccm-search-box">
                    <i class="material-icons material-symbols-rounded eccm-search-ico" aria-hidden="true">search</i>
                    <input type="text" id="eccmTableSearch" class="form-control eccm-search-input"
                        placeholder="Search" autocomplete="off" aria-label="Search exemption categories">
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="exceptiongetcategory">
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap" style="width: 5.5rem;">S. No.</th>
                                <th scope="col">Name</th>
                                <th scope="col" class="text-nowrap">Short Name</th>
                                <th scope="col" class="text-center text-nowrap" style="width: 7.5rem;">Status</th>
                                <th scope="col" class="text-center text-nowrap" style="width: 9rem;">Action</th>
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
                               class="form-control rounded-1"
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
                               class="form-control rounded-1"
                               placeholder="eg. Category Pre"
                               autocomplete="off">
                        <small class="text-danger d-none mt-1" id="exemp_category_name_error">Required</small>
                    </div>

                    <div class="mb-0">
                        <label for="status" class="form-label cgt-field-label mb-2">
                            Status <span class="text-danger">*</span>
                        </label>
                        <select id="status" class="form-select rounded-1">
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <small class="text-danger d-none mt-1" id="status_error">Required</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-1 px-4" id="eccmAddSubmit">Add</button>
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
                               class="form-control rounded-1"
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
                               class="form-control rounded-1"
                               placeholder="eg. Category Pre"
                               autocomplete="off">
                        <small class="text-danger d-none mt-1" id="exemp_category_name_error">Required</small>
                    </div>

                    <div class="mb-0">
                        <label for="status" class="form-label cgt-field-label mb-2">
                            Status <span class="text-danger">*</span>
                        </label>
                        <select name="status" id="status" class="form-select rounded-1">
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <small class="text-danger d-none mt-1" id="status_error">Required</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-1 px-4" id="eccmEditSubmit">Update</button>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility -->
<div class="modal fade" id="eccmColumnsModal" tabindex="-1" aria-labelledby="eccmColumnsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold mb-0" id="eccmColumnsModalLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="eccm-col-grid" id="eccmColumnsGrid"></div>
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
                        $switchWrap.addClass('programme-action-switch m-0 d-inline-flex align-items-center p-0');
                        $editBtn.after($switchWrap);
                    }

                    $statusCell.empty().append(
                        $('<span>', {
                            class: 'badge rounded-1 programme-status-badge eccm-status-badge ' + badgeClass,
                            text: label
                        })
                    );
                }

                // Keep the server's material-icons (Bootstrap Icons font isn't
                // loaded on this layout); just drop the text label for icon-only.
                $actionCell.find('.edit-btn, .delete-btn').each(function() {
                    $(this).find('span.d-none').remove();
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
                dom: "<'row'<'col-12'tr>>" +
                     "<'row mt-3 align-items-center'" +
                         "<'col-12 col-md-auto me-md-auto'p>" +
                         "<'col-12 col-md-auto d-flex justify-content-md-end align-items-center eccm-count'li>" +
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
                '<label class="eccm-col-chip' + (visible ? ' is-checked' : '') + '" for="eccmColToggle' + idx + '">' +
                    '<input class="form-check-input eccm-col-toggle" type="checkbox" ' + (visible ? 'checked ' : '') +
                           'id="eccmColToggle' + idx + '" data-column="' + idx + '">' +
                    '<span>' + title + '</span>' +
                '</label>'
            );
        });
        $eccmColGrid.on('change', '.eccm-col-toggle', function() {
            table.column($(this).data('column')).visible(this.checked);
            $(this).closest('.eccm-col-chip').toggleClass('is-checked', this.checked);
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
