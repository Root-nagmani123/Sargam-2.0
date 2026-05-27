@extends('admin.layouts.master')

@section('title', 'Course Group Type')

@section('setup_content')
<style>
.disabled-link {
    pointer-events: none;
    /* click बंद */
    opacity: 0.5;
    /* disabled look */
    cursor: not-allowed;
}

    <div class="card cgt-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div id="cgtDtSearch" class="programme-dt-search ms-lg-auto" data-dt-search-for="coursegrouptype"></div>
            </div>

.dropdown-item:hover {
    background-color: #f5f6f8;
}

.dropdown-item i {
    font-size: 18px;
}
</style>
<div class="container-fluid">
    <x-breadcrum title="Course Group Type"></x-breadcrum>
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" >
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h4 class="mb-0">Course Group Type</h4>
                        </div>

                        <div class="col-6">
                            <div class="d-flex justify-content-end gap-2 align-items-center">
                                <button id="showAlert" class="btn btn-primary">Add Course Group Type</button>
                                <!-- Add Button -->
                                <!-- <a href="{{ route('master.course.group.type.create') }}" class="btn btn-primary">
                                    <i class="material-icons menu-icon me-1">add</i> Add Course Group Type
                                </a> -->


                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        <table class="table" id="coursegrouptype">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th class="col">S.No.</th>
                                    <th class="col">Type Name</th>
                                    <th class="col">Status</th>
                                    <th class="col">Action</th>
                                </tr>
                                <!-- end row -->
                            </thead>
                        </table>
                    </div>
                </div>
                <div id="cgtDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="coursegrouptype"></div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>
<input type="hidden" id="pk" value="">
<input type="hidden" id="active_inactive" value="">
@endsection
@section('script')
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
            ajax: {
                url: "{{ route('master.course.group.type.grouptypeview') }}",
                data: function(d) {
                    d.pk = $('#pk').val();
                    d.active_inactive = $('#active_inactive').val();
                    //  console.log(d.pk);

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
                processing: '<span class="spinner-border spinner-border-sm text-primary me-2" role="status" aria-hidden="true"></span>Loading…',
                emptyTable: 'No course group types found.',
                zeroRecords: 'No matching course group types found.'
            },
            initComplete: function() {
                decorateCgtRows();
            },
            drawCallback: function() {
                decorateCgtRows();
            }
        });
    }

    $(document).on('change', '.plain-status-toggle', function() {
        var checkbox = $(this);
        var previousState = !checkbox.is(':checked'); // save previous state
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
                table.ajax.reload(null, false);

                Swal.fire({
                    icon: 'success',
                    title: 'Updated!',
                    text: 'Status has been updated successfully.',
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                // revert checkbox to previous state
                checkbox.prop('checked', previousState);

                Swal.fire({
                    icon: 'info',
                    title: 'Cancelled',
                    text: 'Status change has been cancelled.',
                    timer: 1200,
                    showConfirmButton: false
                });
            }
        });
    });




    $(document).on('click', '.delete-btn', function(e) {
        e.preventDefault();
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
                // Revert the checkbox state
                checkbox.prop('checked', !active_inactive);
                // Show cancel message
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

}); //endclose
</script>
<script>
document.getElementById('showAlert').addEventListener('click', function() {

    Swal.fire({
        title: '<strong><small>Add Course Group Type</small></strong>',
        html: `
        <form id="courseGroupTypeForm">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">

            <div class="row mb-1 align-items-center">
                <label class="col-auto col-form-label fw-semibold">
                    Type Name <span class="text-danger">*</span>
                </label>
                <div class="col">
                    <input type="text" 
                           name="type_name" 
                           id="type_name" 
                           class="form-control">
                    <small class="text-danger d-none" id="type_name_error">
                        Type Name is required
                    </small>
                </div>
            </div>
        </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Submit',
        focusConfirm: false,

        preConfirm: () => {
            const typeNameInput = Swal.getPopup().querySelector('#type_name');
            const errorMsg = Swal.getPopup().querySelector('#type_name_error');

            typeNameInput.classList.remove('is-invalid');
            errorMsg.classList.add('d-none');

            if (!typeNameInput.value.trim()) {
                typeNameInput.classList.add('is-invalid');
                errorMsg.classList.remove('d-none');
                return false;
            }

            return {
                type_name: typeNameInput.value.trim()
            };
        }
    }).then((result) => {

        if (result.isConfirmed) {

            fetch(`{{ route('master.course.group.type.store') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(result.value)
                })
                .then(response => response.json())
                .then(data => {

                    if (data.status === true) {

                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message
                        });
                        $('#coursegrouptype').DataTable().ajax.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message
                        });
                    }

                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'Something went wrong!'
                    });
                });
        }
    });
});
</script>

<!-- EDIT FORM  -->

<script>
$(document).on('click', '.edit-btn', function() {

    let pk = $(this).data('id'); // encrypted id
    let typeName = $(this).data('type-name');

    let url = "{{ route('master.course.group.type.store') }}";

    Swal.fire({
        title: '<strong><small>Edit Course Group Type</small></strong>',
        html: `
        <form id="courseGroupTypeEditForm">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="id" value="${pk}">

            <div class="row mb-1 align-items-center">
                <label class="col-auto col-form-label fw-semibold">
                    Type Name <span class="text-danger">*</span>
                </label>
                <div class="col">
                    <input type="text"
                           name="type_name"
                           id="type_name"
                           class="form-control"
                           value="${typeName}">
                    <small class="text-danger d-none" id="type_name_error">
                        Type Name is required
                    </small>
                </div>
            </div>
        </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Update',
        focusConfirm: false,

        preConfirm: () => {
            const typeNameInput = Swal.getPopup().querySelector('#type_name');
            const errorMsg = Swal.getPopup().querySelector('#type_name_error');

            typeNameInput.classList.remove('is-invalid');
            errorMsg.classList.add('d-none');

            if (!typeNameInput.value.trim()) {
                typeNameInput.classList.add('is-invalid');
                errorMsg.classList.remove('d-none');
                return false;
            }

            return {
                id: pk,
                type_name: typeNameInput.value.trim()
            };
        }
    }).then((result) => {

        if (result.isConfirmed) {

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: result.value.id,
                    type_name: result.value.type_name
                },
                success: function(response) {

                    if (response.status === true) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated',
                            text: response.message
                        });

                        // 🔁 Reload DataTable if exists
                        $('#coursegrouptype').DataTable().ajax.reload();

                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function(xhr) {

                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        Swal.fire('Validation Error', errors.type_name[0], 'error');
                    } else {
                        Swal.fire('Error', 'Something went wrong!', 'error');
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
@endsection