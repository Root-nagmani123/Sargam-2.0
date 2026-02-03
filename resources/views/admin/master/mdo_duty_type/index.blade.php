@extends('admin.layouts.master')

@section('title', 'MDO Duty Type')

@section('setup_content')
<style>
/* MDO Duty Type - responsive (mobile/tablet only, desktop unchanged) */

/* Responsive - Tablet (max 991px) */
@media (max-width: 991.98px) {
    .mdo-duty-type-index .datatables .table-responsive {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
    }

    .mdo-duty-type-index .datatables #mdodutytypemaster-table {
        min-width: 400px;
    }

    .mdo-duty-type-index .datatables #mdodutytypemaster-table th,
    .mdo-duty-type-index .datatables #mdodutytypemaster-table td {
        padding: 8px 10px;
        font-size: 0.9rem;
    }
}

/* Responsive - Small tablet / large phone (max 767px) */
@media (max-width: 767.98px) {
    .mdo-duty-type-index .datatables .card-body {
        padding: 1rem !important;
    }

    .mdo-duty-type-index .datatables #mdodutytypemaster-table th,
    .mdo-duty-type-index .datatables #mdodutytypemaster-table td {
        padding: 6px 8px;
        font-size: 0.85rem;
    }
}

/* Responsive - Phone (max 575px) */
@media (max-width: 575.98px) {
    .mdo-duty-type-index.container-fluid {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }

    .mdo-duty-type-index .mdo-duty-type-header-row {
        flex-direction: column;
        gap: 0.5rem;
    }

    .mdo-duty-type-index .mdo-duty-type-header-row .col-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }

    .mdo-duty-type-index .mdo-duty-type-header-row .d-flex.justify-content-end {
        justify-content: stretch !important;
    }

    .mdo-duty-type-index .mdo-duty-type-header-row .add-btn {
        width: 100%;
        justify-content: center;
    }

    .mdo-duty-type-index .datatables .card-body {
        padding: 0.75rem !important;
    }

    .mdo-duty-type-index .datatables .table-responsive {
        margin-left: -0.5rem;
        margin-right: -0.5rem;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    .mdo-duty-type-index .datatables #mdodutytypemaster-table th,
    .mdo-duty-type-index .datatables #mdodutytypemaster-table td {
        padding: 6px 8px;
        font-size: 0.8125rem;
    }

    .mdo-duty-type-index #dutyTypeModal .modal-dialog {
        margin: 0.5rem;
        max-width: calc(100% - 1rem);
    }
}

/* Responsive - Very small phone (max 375px) */
@media (max-width: 375px) {
    .mdo-duty-type-index.container-fluid {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    .mdo-duty-type-index .datatables .card-body {
        padding: 0.5rem !important;
    }

    .mdo-duty-type-index .mdo-duty-type-header-row h4 {
        font-size: 1rem;
    }
}
</style>
<div class="container-fluid mdo-duty-type-index">
    <x-breadcrum title="MDO Duty Type"></x-breadcrum>
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row mdo-duty-type-header-row">
                        <div class="col-6">
                            <!-- left column empty or header title above -->
                            <h4>MDO Duty Type</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <!-- Add Group Mapping -->
                                <a href="javascript:void(0)" 
                                    class="btn btn-primary add-btn d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#mdoDutyTypeModal">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">add</i>
                                    Add MDO Duty Type
                                </a>
                            </div>
                        </div>
                    </div>
                    <hr>

                    {!! $dataTable->table(['class' => 'table']) !!}
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const createBtn = document.getElementById('openCreateDutyType');
        const editLinks = document.querySelectorAll('.openEditDutyType');

        function openModalWithUrl(url, title) {
            const modalEl = document.getElementById('dutyTypeModal');
            const modalTitle = modalEl.querySelector('.modal-title');
            const modalBody = modalEl.querySelector('.modal-body');
            modalTitle.textContent = title || 'MDO Duty Type';
            modalBody.innerHTML = '<div class="text-center p-4">Loading...</div>';

            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.text())
                .then(html => {
                    modalBody.innerHTML = html;
                })
                .catch(() => {
                    modalBody.innerHTML = '<div class="text-danger">Failed to load form.</div>';
                });

            const bsModal = new bootstrap.Modal(modalEl);
            bsModal.show();
        }

        if (createBtn) {
            createBtn.addEventListener('click', function(e) {
                e.preventDefault();
                openModalWithUrl(this.getAttribute('href'), 'Create MDO Duty Type');
            });
        }

        editLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                openModalWithUrl(this.getAttribute('href'), 'Edit MDO Duty Type');
            });

            // Handle AJAX form submit inside modal
            document.getElementById('dutyTypeModal')?.addEventListener('submit', function(e) {
                const form = e.target;
                if (form && form.tagName === 'FORM') {
                    e.preventDefault();
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) submitBtn.disabled = true;

                    fetch(form.action, {
                            method: form.method || 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: new FormData(form)
                        })
                        .then(async (res) => {
                            if (res.ok) {
                                // Try to parse JSON; fallback to text
                                const ct = res.headers.get('content-type') || '';
                                if (ct.includes('application/json')) {
                                    const data = await res.json();
                                    if (data.success || data.status === true) {
                                        // Update table without full reload
                                        updateTableAfterSave(data);
                                        bootstrap.Modal.getInstance(document.getElementById('dutyTypeModal'))?.hide();
                                        return;
                                    }
                                }
                                // Non-JSON success fallback
                                updateTableAfterSave(null);
                                bootstrap.Modal.getInstance(document.getElementById('dutyTypeModal'))?.hide();
                            } else if (res.status === 422) {
                                // Validation errors: re-render returned HTML into modal
                                const html = await res.text();
                                const modalBody = document.querySelector('#dutyTypeModal .modal-body');
                                modalBody.innerHTML = html;
                            } else {
                                const modalBody = document.querySelector('#dutyTypeModal .modal-body');
                                modalBody.insertAdjacentHTML('afterbegin', '<div class="alert alert-danger">Save failed. Please try again.</div>');
                            }
                        })
                        .catch(() => {
                            const modalBody = document.querySelector('#dutyTypeModal .modal-body');
                            modalBody.insertAdjacentHTML('afterbegin', '<div class="alert alert-danger">Network error. Please try again.</div>');
                        })
                        .finally(() => {
                            if (submitBtn) submitBtn.disabled = false;
                        });
                }
            });
        });
    });

    function buildEditUrl(encryptedPk) {
        return `${window.location.origin}/master/mdo_duty_type/edit/${encodeURIComponent(encryptedPk)}`;
    }

    function escapeHtml(str) {
        if (typeof str !== 'string') return '';
        return str.replace(/[&<>"']/g, function(ch) {
            return ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                '\'': '&#39;'
            } [ch]);
        });
    }

    function interceptEditLink(e) {
        e.preventDefault();
        openModalWithUrl(this.getAttribute('href'), 'Edit MDO Duty Type');
    }

    function updateTableAfterSave(payload) {
        // Reload DataTable after create/update
        if (typeof $.fn.DataTable !== 'undefined') {
            const table = $('#mdodutytypemaster-table').DataTable();
            if (table) {
                table.ajax.reload(null, false); // false = don't reset pagination
            }
        }
    }


    $(document).on('change', '.plain-status-toggle', function() {
        let checkbox = $(this);
        let pk = checkbox.data('id');
        let active_inactive = checkbox.is(':checked') ? 1 : 0;
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
            if (result.isConfirmed){
                $.ajax({
                    url: "{{ route('master.mdo_duty_type.status') }}", // route
                    type: "POST",
                    data: {
                        pk: pk,
                        active_inactive: active_inactive,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {

                        $('#mdodutytypemaster-table').DataTable().ajax.reload(null, false);

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
                // revert checkbox
                checkbox.prop('checked', !active_inactive);
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
            if (result.isConfirmed){
                $.ajax({
                    url: "{{ route('master.mdo_duty_type.delete') }}", // route
                    type: "POST",
                    data: {
                        id: pk,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        $('#mdodutytypemaster-table').DataTable().ajax.reload(null, false);
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                });

            } else {
                // revert checkbox
                checkbox.prop('checked', !active_inactive);
            }
        });
    });
</script>

<script>
    
    $(document).on('click', '.edit-btn', function () {
    let  pk = $(this).data('id');
    let  mdo_duty_type_name = $(this).data('mdo_duty_type_name');
    let  active_inactive = $(this).data('active_inactive');
    let  url = "{{ route('master.mdo_duty_type.store') }}";

    Swal.fire({
        title: '<strong><small>Edit MDO Duty Type</small></strong>',
        html: `
            <form id="EditMDODutyTypeForm">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="id" value="${pk}">

                <div class="row mb-2 align-items-center">
                    <label class="col-4 col-form-label fw-semibold">
                        Duty Type Name <span class="text-danger">*</span>
                    </label>
                    <div class="col-8">
                        <input type="text"
                            name="mdo_duty_type_name"
                            id="mdo_duty_type_name"
                            class="form-control"
                            value="${mdo_duty_type_name}">
                        <small class="text-danger d-none" id="mdo_duty_type_name_error">
                            Duty Type Name is required
                        </small>
                    </div>
                </div>

                <div class="row mb-2 align-items-center">
                    <label class="col-4 col-form-label fw-semibold">
                        Status <span class="text-danger">*</span>
                    </label>
                    <div class="col-8">
                        <select name="active_inactive"
                                id="active_inactive"
                                class="form-select">
                            <option value="">-- Select Status --</option>
                            <option value="1" ${active_inactive == 1 ? 'selected' : ''}>Active</option>
                            <option value="0" ${active_inactive == 0 ? 'selected' : ''}>Inactive</option>
                        </select>

                        <small class="text-danger d-none" id="active_inactive_error">
                            Status is required
                        </small>
                    </div>
                </div>

            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Update',
        focusConfirm: false,

        preConfirm: () => {
            const typeNameInput = Swal.getPopup().querySelector('#mdo_duty_type_name');
            const active_inactiveInput = Swal.getPopup().querySelector('#active_inactive');
            const errorMsg = Swal.getPopup().querySelector('#mdo_duty_type_name_error');
            const active_inactiveMsg = Swal.getPopup().querySelector('#active_inactive_error');

            typeNameInput.classList.remove('is-invalid');
            errorMsg.classList.add('d-none');

            active_inactiveInput.classList.remove('is-invalid');
            active_inactiveMsg.classList.add('d-none');
            

            if (!typeNameInput.value.trim()) {
                typeNameInput.classList.add('is-invalid');
                errorMsg.classList.remove('d-none');
                return false;
            }

            if (!active_inactiveInput.value.trim()) {
                active_inactiveInput.classList.add('is-invalid');
                active_inactiveMsg.classList.remove('d-none');
                return false;
            }

            return {
                id: pk,
                mdo_duty_type_name: typeNameInput.value.trim(),
                active_inactive: active_inactiveInput.value.trim(),
                _token: "{{ csrf_token() }}"
            };
        }
    }).then((result) => {

        if (result.isConfirmed) {

            $.ajax({
                url: url,
                type: "POST",
                data: result.value,
                beforeSend: function () {
                    Swal.showLoading();
                },
                success: function (response) {

                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        text: response.message ?? 'Record updated successfully',
                        timer: 1500,
                        showConfirmButton: false
                    });

                    $('.dataTable').DataTable().ajax.reload(null, false);
                },
                error: function (xhr) {

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.message ?? 'Something went wrong'
                    });
                }
            });
        }
    });
});

// add form//

$(document).on('click', '.add-btn', function () {

    const url  = "{{ route('master.mdo_duty_type.store') }}";
    const csrf = "{{ csrf_token() }}";

    Swal.fire({
        title: '<strong><small>Add MDO Duty Type</small></strong>',
        html: `
            <form id="AddMDODutyTypeForm">
                <input type="hidden" name="_token" value="${csrf}">

                <div class="row mb-2 align-items-center">
                    <label class="col-auto col-form-label fw-semibold">
                        Duty Type Name <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <input type="text"
                               name="mdo_duty_type_name"
                               class="form-control">
                        <small class="text-danger d-none" id="mdo_duty_type_name_error">
                            Duty Type Name is required
                        </small>
                    </div>
                </div>

                <div class="row mb-2 align-items-center">
                    <label class="col-auto col-form-label fw-semibold">
                        Status <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <select name="active_inactive" class="form-select">
                            <option value="">-- Select Status --</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <small class="text-danger d-none" id="active_inactive_error">
                            Status is required
                        </small>
                    </div>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Save',
        focusConfirm: false,

        preConfirm: () => {

            const form = $('#AddMDODutyTypeForm');
            const nameInput   = form.find('[name="mdo_duty_type_name"]');
            const statusInput = form.find('[name="active_inactive"]');

            let valid = true;

            form.find('.is-invalid').removeClass('is-invalid');
            $('#mdo_duty_type_name_error, #active_inactive_error').addClass('d-none');

            if (!nameInput.val().trim()) {
                nameInput.addClass('is-invalid');
                $('#mdo_duty_type_name_error').removeClass('d-none');
                valid = false;
            }

            if (!statusInput.val()) {
                statusInput.addClass('is-invalid');
                $('#active_inactive_error').removeClass('d-none');
                valid = false;
            }

            if (!valid) return false;

            return {
                mdo_duty_type_name: nameInput.val().trim(),
                active_inactive: statusInput.val(),
                _token: csrf
            };
        }

    }).then((result) => {

        if (!result.isConfirmed) return;

        $.ajax({
            url: url,
            type: "POST",
            data: result.value,
            beforeSend: () => Swal.showLoading(),

            success: (response) => {
                Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    text: response.message ?? 'Record added successfully',
                    timer: 1500,
                    showConfirmButton: false
                });

                $('.dataTable').DataTable().ajax.reload(null, false);
            },

            error: (xhr) => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON?.message ?? 'Something went wrong'
                });

            const bsModal = new bootstrap.Modal(modalEl);
            bsModal.show();
        }

        if (createBtn) {
            createBtn.addEventListener('click', function(e) {
                e.preventDefault();
                openModalWithUrl(this.getAttribute('href'), 'Create MDO Duty Type');
            });
        }

        editLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                openModalWithUrl(this.getAttribute('href'), 'Edit MDO Duty Type');
            });

            // Handle AJAX form submit inside modal
            document.getElementById('dutyTypeModal')?.addEventListener('submit', function(e) {
                const form = e.target;
                if (form && form.tagName === 'FORM') {
                    e.preventDefault();
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) submitBtn.disabled = true;

                    fetch(form.action, {
                            method: form.method || 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: new FormData(form)
                        })
                        .then(async (res) => {
                            if (res.ok) {
                                // Try to parse JSON; fallback to text
                                const ct = res.headers.get('content-type') || '';
                                if (ct.includes('application/json')) {
                                    const data = await res.json();
                                    if (data.success || data.status === true) {
                                        // Update table without full reload
                                        updateTableAfterSave(data);
                                        bootstrap.Modal.getInstance(document.getElementById('dutyTypeModal'))?.hide();
                                        return;
                                    }
                                }
                                // Non-JSON success fallback
                                updateTableAfterSave(null);
                                bootstrap.Modal.getInstance(document.getElementById('dutyTypeModal'))?.hide();
                            } else if (res.status === 422) {
                                // Validation errors: re-render returned HTML into modal
                                const html = await res.text();
                                const modalBody = document.querySelector('#dutyTypeModal .modal-body');
                                modalBody.innerHTML = html;
                            } else {
                                const modalBody = document.querySelector('#dutyTypeModal .modal-body');
                                modalBody.insertAdjacentHTML('afterbegin', '<div class="alert alert-danger">Save failed. Please try again.</div>');
                            }
                        })
                        .catch(() => {
                            const modalBody = document.querySelector('#dutyTypeModal .modal-body');
                            modalBody.insertAdjacentHTML('afterbegin', '<div class="alert alert-danger">Network error. Please try again.</div>');
                        })
                        .finally(() => {
                            if (submitBtn) submitBtn.disabled = false;
                        });
                }
            });
        });
    });

    function buildEditUrl(encryptedPk) {
        return `${window.location.origin}/master/mdo_duty_type/edit/${encodeURIComponent(encryptedPk)}`;
    }

    function escapeHtml(str) {
        if (typeof str !== 'string') return '';
        return str.replace(/[&<>"']/g, function(ch) {
            return ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                '\'': '&#39;'
            } [ch]);
        });
    }

    function interceptEditLink(e) {
        e.preventDefault();
        openModalWithUrl(this.getAttribute('href'), 'Edit MDO Duty Type');
    }

    function updateTableAfterSave(payload) {
        // Reload DataTable after create/update
        if (typeof $.fn.DataTable !== 'undefined') {
            const table = $('#mdodutytypemaster-table').DataTable();
            if (table) {
                table.ajax.reload(null, false); // false = don't reset pagination
            }
        }
    }


    $(document).on('change', '.plain-status-toggle', function() {
        let checkbox = $(this);
        let pk = checkbox.data('id');
        let active_inactive = checkbox.is(':checked') ? 1 : 0;

        Swal.fire({
            title: 'Are you sure?',
            text: 'Are you sure? You want to deactivate this item?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, deactivate',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed){
                $.ajax({
                    url: "{{ route('master.mdo_duty_type.status') }}", // route
                    type: "POST",
                    data: {
                        pk: pk,
                        active_inactive: active_inactive,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {

                        $('#mdodutytypemaster-table').DataTable().ajax.reload(null, false);

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
                // revert checkbox
                checkbox.prop('checked', !active_inactive);
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
<!-- Modal -->
<div class="modal fade" id="dutyTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">MDO Duty Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Form content will be loaded here via fetch -->
            </div>
        </div>
    </div>
</div>
@endpush