@extends('admin.layouts.master')

@section('title', 'Course Group Type')

@section('setup_content')
<div class="container-fluid cgt-master-page">
    <x-breadcrum title="Course Group Type"
        buttonText="Add Course Group Type"
        buttonId="showAlert"
        buttonIcon="add"
        buttonClass="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-2 fw-semibold shadow-sm" />

    <div class="card cgt-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div id="cgtDtSearch" class="programme-dt-search ms-lg-auto"></div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 w-100 programme-dt-table" id="coursegrouptype">
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap" style="width: 5.5rem;">S.No.</th>
                                <th scope="col">Type Name</th>
                                <th scope="col" class="text-center text-nowrap" style="width: 7.5rem;">Status</th>
                                <th scope="col" class="text-center text-nowrap" style="width: 8.5rem;">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div id="cgtDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"></div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="pk" value="">
<input type="hidden" id="active_inactive" value="">

<!-- Activate / Deactivate / Delete confirmation -->
<div class="modal fade programme-confirm-modal-root" id="cgtConfirmModal" tabindex="-1"
    aria-labelledby="cgtConfirmTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered programme-confirm-dialog">
        <div class="modal-content programme-confirm-modal border-0 shadow-lg rounded-5 overflow-hidden">
            <div class="modal-body text-center px-4 px-md-5 py-5">
                <div id="cgtConfirmIcon" class="programme-confirm-icon programme-confirm-icon--warning mb-4"
                    role="img" aria-hidden="true">
                    <i id="cgtConfirmIconBi" class="bi bi-exclamation-lg"></i>
                </div>
                <h2 class="programme-confirm-title h4 fw-bold mb-3" id="cgtConfirmTitle">Confirm</h2>
                <p class="programme-confirm-message mb-4 mb-md-5" id="cgtConfirmMessage"></p>
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center align-items-stretch programme-confirm-actions">
                    <button type="button" class="btn btn-lg rounded-3 programme-confirm-btn" id="cgtConfirmCancel">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-lg rounded-3 programme-confirm-btn" id="cgtConfirmOk">
                        Confirm
                    </button>
                </div>
                <div id="cgtDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="coursegrouptype"></div>
            </div>
        </div>
    </div>
</div>

<!-- Add Course Group Type -->
<div class="modal fade" id="cgtAddModal" tabindex="-1" aria-labelledby="cgtAddModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cgt-form-modal border-0">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="cgtAddModalLabel">Add Course Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="courseGroupTypeForm" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <label for="type_name" class="form-label cgt-field-label">
                        Course Group Name <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           name="type_name"
                           id="type_name"
                           class="form-control"
                           placeholder="eg. MCTP Group"
                           autocomplete="off">
                    <small class="text-danger d-none mt-1" id="type_name_error">
                        Type Name is required
                    </small>
                </form>
            </div>
            <div class="modal-footer border-0 gap-2">
                <button type="button" class="btn btn-outline-primary rounded-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-3" id="cgtAddSubmit">Create Course Group</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Course Group Type -->
<div class="modal fade" id="cgtEditModal" tabindex="-1" aria-labelledby="cgtEditModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cgt-form-modal border-0">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="cgtEditModalLabel">Edit Course Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="courseGroupTypeEditForm" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" value="">
                    <label for="type_name" class="form-label cgt-field-label">
                        Course Group Name <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           name="type_name"
                           id="type_name"
                           class="form-control"
                           placeholder="eg. MCTP Group"
                           autocomplete="off">
                    <small class="text-danger d-none mt-1" id="type_name_error">
                        Type Name is required
                    </small>
                </form>
            </div>
            <div class="modal-footer border-0 gap-2">
                <button type="button" class="btn btn-outline-primary rounded-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-3" id="cgtEditSubmit">Update Course Group</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function() {
    const tableSelector = '#coursegrouptype';
    let table;
    let cgtEditPk = null;

    const cgtConfirmModalEl = document.getElementById('cgtConfirmModal');
    const cgtConfirmModal = cgtConfirmModalEl ? bootstrap.Modal.getOrCreateInstance(cgtConfirmModalEl) : null;
    const cgtAddModalEl = document.getElementById('cgtAddModal');
    const cgtAddModal = cgtAddModalEl ? bootstrap.Modal.getOrCreateInstance(cgtAddModalEl) : null;
    const cgtEditModalEl = document.getElementById('cgtEditModal');
    const cgtEditModal = cgtEditModalEl ? bootstrap.Modal.getOrCreateInstance(cgtEditModalEl) : null;

    let cgtConfirmOnOk = null;
    let cgtConfirmOnCancel = null;
    let cgtConfirmDismissViaAction = false;

    const cgtConfirmBtnClasses = [
        'programme-confirm-cancel--primary',
        'programme-confirm-cancel--danger',
        'programme-confirm-ok--primary',
        'programme-confirm-ok--danger',
        'btn-primary',
        'btn-danger',
        'btn-outline-primary',
        'btn-outline-danger'
    ];

    const cgtConfirmConfigs = {
        activate: {
            iconWrap: 'programme-confirm-icon--success',
            icon: 'bi-info-lg',
            title: 'Activate Course Group?',
            message: 'Are you sure you want to activate this course group?',
            messageClass: 'programme-confirm-message--success',
            cancelLines: ['Cancel,', 'Keep it deactive'],
            confirmLines: ['Yes,', 'Activate'],
            cancelClass: 'programme-confirm-cancel--primary',
            confirmClass: 'programme-confirm-ok--primary'
        },
        deactivate: {
            iconWrap: 'programme-confirm-icon--warning',
            icon: 'bi-exclamation-lg',
            title: 'Deactivate Course Group?',
            message: 'Are you sure you want to deactivate this course group?',
            messageClass: 'programme-confirm-message--info',
            cancelLines: ['Cancel,', 'Keep it active'],
            confirmLines: ['Yes,', 'Deactivate'],
            cancelClass: 'programme-confirm-cancel--primary',
            confirmClass: 'programme-confirm-ok--primary'
        },
        delete: {
            iconWrap: 'programme-confirm-icon--danger',
            icon: 'bi-exclamation-lg',
            title: 'Delete Course Group?',
            message: 'Are you sure you want to delete this course group?',
            messageClass: 'programme-confirm-message--danger',
            cancelLines: ['Cancel,', 'Keep it'],
            confirmLines: ['Yes,', 'Delete'],
            cancelClass: 'programme-confirm-cancel--danger',
            confirmClass: 'programme-confirm-ok--danger'
        }
    };

    function setCgtConfirmButtonLines($btn, lines) {
        $btn.empty();
        (lines || []).forEach(function(line) {
            $('<span>', { class: 'programme-confirm-btn-line', text: line }).appendTo($btn);
        });
    }

    function showCgtConfirm(type, onConfirm, onCancel) {
        if (!cgtConfirmModal) {
            if (onConfirm) {
                onConfirm();
            }
            return;
        }

        const cfg = cgtConfirmConfigs[type];
        if (!cfg) {
            return;
        }

        $('#cgtConfirmIcon')
            .removeClass('programme-confirm-icon--success programme-confirm-icon--warning programme-confirm-icon--danger')
            .addClass(cfg.iconWrap);
        $('#cgtConfirmIconBi').attr('class', 'bi ' + cfg.icon);
        $('#cgtConfirmTitle').text(cfg.title);

        $('#cgtConfirmMessage')
            .removeClass('programme-confirm-message--info programme-confirm-message--success programme-confirm-message--danger')
            .addClass(cfg.messageClass || 'programme-confirm-message--info')
            .text(cfg.message);

        const $cancel = $('#cgtConfirmCancel');
        const $ok = $('#cgtConfirmOk');
        $cancel.removeClass(cgtConfirmBtnClasses.join(' '))
            .addClass('btn programme-confirm-btn ' + cfg.cancelClass);
        $ok.removeClass(cgtConfirmBtnClasses.join(' '))
            .addClass('btn programme-confirm-btn ' + cfg.confirmClass);
        setCgtConfirmButtonLines($cancel, cfg.cancelLines);
        setCgtConfirmButtonLines($ok, cfg.confirmLines);

        cgtConfirmOnOk = onConfirm || null;
        cgtConfirmOnCancel = onCancel || null;
        cgtConfirmDismissViaAction = false;
        cgtConfirmModal.show();
    }

    $('#cgtConfirmOk').on('click', function() {
        const onOk = cgtConfirmOnOk;
        cgtConfirmOnOk = null;
        cgtConfirmOnCancel = null;
        cgtConfirmDismissViaAction = true;
        cgtConfirmModal.hide();
        if (typeof onOk === 'function') {
            onOk();
        }
    });

    $('#cgtConfirmCancel').on('click', function() {
        const onCancel = cgtConfirmOnCancel;
        cgtConfirmOnOk = null;
        cgtConfirmOnCancel = null;
        cgtConfirmDismissViaAction = true;
        cgtConfirmModal.hide();
        if (typeof onCancel === 'function') {
            onCancel();
        }
    });

    if (cgtConfirmModalEl) {
        cgtConfirmModalEl.addEventListener('hidden.bs.modal', function() {
            if (!cgtConfirmDismissViaAction && typeof cgtConfirmOnCancel === 'function') {
                cgtConfirmOnCancel();
            }
            cgtConfirmOnOk = null;
            cgtConfirmOnCancel = null;
            cgtConfirmDismissViaAction = false;
        });
    }

    function resetCgtAddForm() {
        const $form = $('#courseGroupTypeForm');
        const $input = $form.find('#type_name');
        const $error = $('#type_name_error');
        $input.val('').removeClass('is-invalid');
        $error.addClass('d-none');
    }

    function validateCgtTypeName($input, $error) {
        $input.removeClass('is-invalid');
        $error.addClass('d-none');
        if (!$input.val().trim()) {
            $input.addClass('is-invalid');
            $error.removeClass('d-none');
            return false;
        }
        return true;
    }

    function updateCgtRowBadge($checkbox, isActive) {
        const $badge = $checkbox.closest('tr').find('.cgt-status-badge');
        if ($badge.length) {
            $badge
                .removeClass('programme-status-badge--active programme-status-badge--inactive')
                .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
                .text(isActive ? 'Active' : 'Inactive');
        }
    }

    function enhanceCgtDtControls() {
        const $wrapper = $('#coursegrouptype_wrapper');
        if (!$wrapper.length) {
            return;
        }

        const $searchSlot = $('#cgtDtSearch');
        const $footer = $('#cgtDtFooter');

        if (!$searchSlot.find('.dataTables_filter').length) {
            const $filter = $wrapper.find('.dataTables_filter').first();
            if ($filter.length) {
                $filter.find('input')
                    .addClass('form-control shadow-none')
                    .attr('placeholder', 'Search')
                    .attr('aria-label', 'Search course group types');
                $filter.find('label').contents().filter(function() {
                    return this.nodeType === 3;
                }).remove();
                $searchSlot.append($filter);
            }
        }

        if ($footer.data('dtReady')) {
            updateCgtDtCount();
            return;
        }

        const $paginate = $wrapper.find('.dataTables_paginate').first();
        const $length = $wrapper.find('.dataTables_length').first();
        const $info = $wrapper.find('.dataTables_info').first();

        if (!$footer.length) {
            return;
        }

        const $pagCol = $('<div class="programme-dt-pagination"></div>');
        const $countCol = $('<div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto"></div>');

        if ($paginate.length) {
            $paginate.find('.pagination').addClass('mb-0');
            $pagCol.append($paginate);
        }

        if ($length.length) {
            $length.find('select').addClass('form-select form-select-sm');
            $length.find('label')
                .empty()
                .append(document.createTextNode('Showing '))
                .append($length.find('select'))
                .append(document.createTextNode(' '));
            $countCol.append($length);
        }

        if ($info.length) {
            $info.addClass('mb-0');
            $countCol.append($info);
        }

        $footer.append($pagCol).append($countCol);
        $footer.data('dtReady', true);
    }

    function updateCgtDtCount() {
        if (!table) {
            return;
        }
        const info = table.page.info();
        const $info = $('#cgtDtFooter .dataTables_info');
        if ($info.length && info.recordsDisplay !== undefined) {
            $info.text('of ' + info.recordsDisplay.toLocaleString() + ' items');
        }
    }

    function decorateCgtRows() {
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
                if ($switchWrap.length && $actionGroup.length && $editBtn.length) {
                    $switchWrap.addClass('programme-action-switch m-0 d-inline-flex align-items-center');
                    $editBtn.after($switchWrap);
                }

                $statusCell.empty().append(
                    $('<span>', {
                        class: 'badge rounded-pill programme-status-badge cgt-status-badge ' + badgeClass,
                        text: label
                    })
                );
            }

            $actionCell.find('.edit-btn').each(function() {
                const $btn = $(this);
                if (!$btn.find('.bi').length) {
                    $btn.append('<i class="bi bi-pencil" aria-hidden="true"></i>');
                }
            });

            $actionCell.find('.delete-btn').each(function() {
                const $btn = $(this);
                if (!$btn.find('.bi').length) {
                    $btn.append('<i class="bi bi-trash3" aria-hidden="true"></i>');
                }
            });
        });
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
            order: [[1, 'asc']],
            ajax: {
                url: "{{ route('master.course.group.type.grouptypeview') }}",
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
                    data: 'type_name',
                    name: 'type_name'
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
                    className: 'text-center'
                },
                {
                    targets: 3,
                    className: 'text-center'
                }
            ],
            language: {
                search: '',
                searchPlaceholder: 'Search',
                processing: '<span class="spinner-border spinner-border-sm text-primary me-2" role="status" aria-hidden="true"></span>Loading…',
                emptyTable: 'No course group types found.',
                zeroRecords: 'No matching course group types found.'
            },
            dom: 'rt<"row d-none"<"col-sm-12"ilp>>',
            initComplete: function() {
                enhanceCgtDtControls();
                decorateCgtRows();
                updateCgtDtCount();
            },
            drawCallback: function() {
                const $wrapper = $('#coursegrouptype_wrapper');
                if ($wrapper.find('.dataTables_paginate').length && !$('#cgtDtFooter .dataTables_paginate').length) {
                    $('#cgtDtFooter').empty().data('dtReady', false);
                }
                enhanceCgtDtControls();
                decorateCgtRows();
                updateCgtDtCount();
            }
        });
    }

    $(document).on('change', '.plain-status-toggle', function() {
        const checkbox = $(this);
        const previousState = !checkbox.is(':checked');
        const pk = checkbox.data('id');
        const active_inactive = checkbox.is(':checked') ? 1 : 0;
        const confirmType = active_inactive ? 'activate' : 'deactivate';

        showCgtConfirm(confirmType, function() {
            $('#pk').val(pk);
            $('#active_inactive').val(active_inactive);
            table.ajax.reload(null, false);

            Swal.fire({
                icon: 'success',
                title: 'Updated!',
                text: 'Status has been updated successfully.',
                timer: 1500,
                showConfirmButton: false
            });
        }, function() {
            checkbox.prop('checked', previousState);
            updateCgtRowBadge(checkbox, previousState);
        });
    });

    $(document).on('click', '.delete-btn', function(e) {
        e.preventDefault();
        if ($(this).attr('aria-disabled') === 'true' || $(this).hasClass('disabled')) {
            return;
        }

        const pk = $(this).data('id');

        showCgtConfirm('delete', function() {
            $('#pk').val(pk);
            $('#active_inactive').val(2);
            table.ajax.reload(null, false);
            Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                text: 'Record has been deleted successfully.',
                timer: 1500,
                showConfirmButton: false
            });
        });
    });

    $(document).on('click', '#showAlert', function(e) {
        e.preventDefault();
        resetCgtAddForm();
        if (cgtAddModal) {
            cgtAddModal.show();
            setTimeout(function() {
                $('#courseGroupTypeForm #type_name').trigger('focus');
            }, 300);
        }
    });

    $('#cgtAddSubmit').on('click', function() {
        const $input = $('#courseGroupTypeForm #type_name');
        const $error = $('#type_name_error');

        if (!validateCgtTypeName($input, $error)) {
            return;
        }

        const payload = { type_name: $input.val().trim() };

        fetch("{{ route('master.course.group.type.store') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.status === true) {
                if (cgtAddModal) {
                    cgtAddModal.hide();
                }
                resetCgtAddForm();
                table.ajax.reload();
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message
                });
            }
        })
        .catch(function() {
            Swal.fire({
                icon: 'error',
                title: 'Server Error',
                text: 'Something went wrong!'
            });
        });
    });

    $(document).on('click', '.edit-btn', function() {
        cgtEditPk = $(this).data('id');
        const typeName = $(this).data('type-name') || '';

        $('#courseGroupTypeEditForm input[name="id"]').val(cgtEditPk);
        const $input = $('#courseGroupTypeEditForm #type_name');
        const $error = $('#courseGroupTypeEditForm #type_name_error');
        $input.val(typeName).removeClass('is-invalid');
        $error.addClass('d-none');

        if (cgtEditModal) {
            cgtEditModal.show();
            setTimeout(function() {
                $input.trigger('focus');
            }, 300);
        }
    });

    $('#cgtEditSubmit').on('click', function() {
        const $input = $('#courseGroupTypeEditForm #type_name');
        const $error = $('#courseGroupTypeEditForm #type_name_error');

        if (!validateCgtTypeName($input, $error)) {
            return;
        }

        $.ajax({
            url: "{{ route('master.course.group.type.store') }}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: cgtEditPk,
                type_name: $input.val().trim()
            },
            success: function(response) {
                if (response.status === true) {
                    if (cgtEditModal) {
                        cgtEditModal.hide();
                    }
                    table.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated',
                        text: response.message
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    Swal.fire('Validation Error', errors.type_name[0], 'error');
                } else {
                    Swal.fire('Error', 'Something went wrong!', 'error');
                }
            }
        });
    });

    $('#courseGroupTypeForm').on('submit', function(e) {
        e.preventDefault();
        $('#cgtAddSubmit').trigger('click');
    });

    $('#courseGroupTypeEditForm').on('submit', function(e) {
        e.preventDefault();
        $('#cgtEditSubmit').trigger('click');
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
@endsection
